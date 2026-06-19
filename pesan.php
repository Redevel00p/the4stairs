<?php
/**
 * THE 4 STAIRS MUSIC HALL - TICKET BOOKING & DISPLAY
 * -------------------------------------------------
 * Mengelola form pendaftaran reservasi tiket, validasi kuota,
 * penyimpanan ke database, pembuat digital ticket dengan QR code,
 * instruksi pembayaran DANA, dan integrasi WhatsApp Admin.
 *
 * UPDATE: Email dan nomor WA diambil otomatis dari session user yang sedang login.
 * Hanya nama pemesan yang bisa diubah (untuk kasus beli tiket atas nama orang lain).
 * Jika belum login, user diarahkan ke halaman login terlebih dahulu.
 */

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// --- PROTEKSI HALAMAN: Wajib login ---
if (!isset($_SESSION['user_logged_in']) || $_SESSION['user_logged_in'] !== true) {
    // Simpan URL tujuan dan arahkan ke login
    $redirect_url = 'pesan.php' . (!empty($_SERVER['QUERY_STRING']) ? '?' . $_SERVER['QUERY_STRING'] : '');
    $_SESSION['redirect_after_login'] = $redirect_url;
    header("Location: login");
    exit;
}

// Data user dari session (sudah pasti ada karena cek di atas)
$session_email = $_SESSION['user_email'];
$session_no_wa = $_SESSION['user_wa'];
$session_nama  = $_SESSION['user_name'];

// Sertakan file koneksi database
include 'koneksi.php';

$error_msg = "";
$success_booking = null;

// Inisialisasi variabel jadwal untuk dropdown form (hanya event hari ini & mendatang)
$schedules = [];
if (isset($conn) && !$conn->connect_error && $db_selected) {
    $result_sched = $conn->query("SELECT * FROM `jadwal` WHERE `tanggal` >= CURDATE() ORDER BY `tanggal` ASC");
    if ($result_sched) {
        while ($row = $result_sched->fetch_assoc()) {
            $schedules[] = $row;
        }
    }
}

// --------------------------------------------------------------------------
// PROSES SUBMIT RESERVASI (POST METHOD)
// --------------------------------------------------------------------------
if (isset($_POST['submit_booking'])) {
    $id_jadwal     = intval($_POST['id_jadwal']);
    $nama          = $conn->real_escape_string(trim($_POST['nama']));
    $jumlah_tiket  = intval($_POST['jumlah_tiket']);

    // Email dan no WA diambil dari session, bukan dari form
    $email = $conn->real_escape_string($session_email);
    $no_wa = $conn->real_escape_string($session_no_wa);

    // Validasi data input
    if (empty($nama) || $jumlah_tiket <= 0 || $id_jadwal <= 0) {
        $error_msg = "Harap isi semua kolom form dengan benar.";
    } else {
        // Cek kuota tersisa di database terlebih dahulu (mencegah race condition)
        $stmt_check = $conn->prepare("SELECT `kuota`, `terjual`, `status` FROM `jadwal` WHERE `id` = ?");
        $stmt_check->bind_param("i", $id_jadwal);
        $stmt_check->execute();
        $res_check = $stmt_check->get_result()->fetch_assoc();
        $stmt_check->close();

        if (!$res_check) {
            $error_msg = "Jadwal pertunjukan tidak ditemukan.";
        } elseif (strtolower($res_check['status']) === 'closed') {
            $error_msg = "Maaf, pertunjukan terpilih telah ditutup untuk reservasi.";
        } else {
            $sisa_kuota = $res_check['kuota'] - $res_check['terjual'];
            if ($jumlah_tiket > $sisa_kuota) {
                $error_msg = "Maaf, sisa kuota kursi untuk hari tersebut tidak mencukupi (Tersisa: $sisa_kuota kursi).";
            } else {
                // Generate Booking ID Unik (Format: T4S-XXXXXX)
                $characters = '0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZ';
                $random_str = '';
                for ($i = 0; $i < 6; $i++) {
                    $random_str .= $characters[rand(0, strlen($characters) - 1)];
                }
                $id_pesanan = "T4S-" . $random_str;

                // Mulai Database Transaction untuk keamanan
                $conn->begin_transaction();
                try {
                    // Simpan data pemesan ke tabel 'pesanan'
                    $stmt_insert = $conn->prepare("INSERT INTO `pesanan` (`id_pesanan`, `id_jadwal`, `nama`, `email`, `no_wa`, `jumlah_tiket`, `status_pembayaran`) VALUES (?, ?, ?, ?, ?, ?, 'Pending')");
                    $stmt_insert->bind_param("sisssi", $id_pesanan, $id_jadwal, $nama, $email, $no_wa, $jumlah_tiket);
                    $stmt_insert->execute();
                    $stmt_insert->close();

                    // Update data terjual di tabel 'jadwal'
                    $stmt_update = $conn->prepare("UPDATE `jadwal` SET `terjual` = `terjual` + ? WHERE `id` = ?");
                    $stmt_update->bind_param("ii", $jumlah_tiket, $id_jadwal);
                    $stmt_update->execute();
                    $stmt_update->close();

                    // Commit transaction jika tidak ada error
                    $conn->commit();

                    // Redirect ke halaman sukses dengan ID pemesanan
                    header("Location: pesan?sukses=" . $id_pesanan);
                    exit;

                } catch (Exception $e) {
                    $conn->rollback();
                    $error_msg = "Gagal menyimpan reservasi ke database. Silakan coba beberapa saat lagi.";
                }
            }
        }
    }
}

// --------------------------------------------------------------------------
// PROSES MENAMPILKAN TIKET & PEMBAYARAN (GET METHOD - sukses)
// --------------------------------------------------------------------------
if (isset($_GET['sukses'])) {
    $id_pesanan_get = $conn->real_escape_string(trim($_GET['sukses']));
    
    $sql_success = "SELECT p.*, j.hari, j.tanggal, j.jam, j.is_special,
                           TIMESTAMPDIFF(SECOND, NOW(), DATE_ADD(p.waktu_pesan, INTERVAL 15 MINUTE)) as remaining_seconds 
                    FROM `pesanan` p 
                    JOIN `jadwal` j ON p.id_jadwal = j.id 
                    WHERE p.id_pesanan = '$id_pesanan_get'";
    
    $result_success = $conn->query($sql_success);
    if ($result_success && $result_success->num_rows > 0) {
        $success_booking = $result_success->fetch_assoc();
        
        // Jika status pending dan waktu habis, hapus dari database
        if ($success_booking['status_pembayaran'] === 'Pending' && intval($success_booking['remaining_seconds']) <= 0) {
            $id_jadwal_exp = $success_booking['id_jadwal'];
            $jumlah_tiket_exp = intval($success_booking['jumlah_tiket']);
            
            // Revert kuota
            $conn->query("UPDATE `jadwal` SET `terjual` = GREATEST(0, `terjual` - $jumlah_tiket_exp) WHERE `id` = $id_jadwal_exp");
            
            // Hapus pesanan
            $conn->query("DELETE FROM `pesanan` WHERE `id_pesanan` = '$id_pesanan_get'");
            
            $success_booking = null;
            $error_msg = "Batas waktu pembayaran 15 menit telah habis. Reservasi Anda telah dibatalkan dan dihapus secara otomatis.";
        }
    } else {
        $error_msg = "ID Tiket / Pemesanan tidak terdaftar.";
    }
}
?>
<!DOCTYPE html>
<html lang="id" class="scroll-smooth">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pemesanan Tiket - The 4 Stairs Music Hall</title>
    <!-- Tailwind CSS CDN -->
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        jazz: {
                            darkest: '#090706',
                            card: '#141211',
                            input: '#1e1917',
                            gold: '#8b1e22',
                            goldDark: '#78350f',
                            crimson: '#991b1b',
                            muted: '#a89f91',
                            light: '#ece6dc'
                        }
                    },
                    fontFamily: {
                        heading: ['Playfair Display', 'serif'],
                        body: ['Outfit', 'sans-serif']
                    }
                }
            }
        }
    </script>
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body class="bg-jazz-darkest text-jazz-light font-body flex flex-col min-h-screen overflow-x-hidden">

    <?php include 'navbar.php'; ?>

    <main class="max-w-6xl mx-auto px-6 pt-32 pb-16 flex-grow w-full">
        
        <?php if ($success_booking): ?>
            <!-- ===============================================================
                 STATE 2: SUCCESS STATE - DIGITAL TICKET & DANA INSTRUCTIONS
                 =============================================================== -->
            <div class="text-center mb-6">
                <h2 class="font-heading text-3xl text-white tracking-wide">Reservasi Berhasil</h2>
                <p class="text-jazz-muted text-xs md:text-sm mt-2">
                    Tiket Anda telah dibukukan. Silakan lakukan pembayaran untuk mengonfirmasi tiket Anda.
                </p>
            </div>

            <!-- Countdown Timer Banner -->
            <?php if ($success_booking['status_pembayaran'] === 'Pending'): ?>
            <div class="max-w-md mx-auto mb-8 bg-red-950/20 border border-red-900/40 text-red-400 p-4 rounded-xl flex items-center justify-between shadow-lg shadow-red-950/10">
                <div class="flex items-center gap-3">
                    <span class="relative flex h-3 w-3">
                        <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-red-400 opacity-75"></span>
                        <span class="relative inline-flex rounded-full h-3 w-3 bg-red-500"></span>
                    </span>
                    <div>
                        <span class="block text-[10px] uppercase font-bold tracking-widest text-red-500/80">Sisa Waktu Pembayaran</span>
                        <span class="text-xs text-jazz-muted">Bayar sebelum tiket otomatis terhapus</span>
                    </div>
                </div>
                <div class="font-mono text-2xl font-bold tracking-wider" id="countdown-timer">
                    --:--
                </div>
            </div>

            <script>
                document.addEventListener("DOMContentLoaded", function() {
                    let remainingSeconds = <?php echo intval($success_booking['remaining_seconds']); ?>;
                    const timerDisplay = document.getElementById('countdown-timer');
                    
                    function updateTimer() {
                        if (remainingSeconds <= 0) {
                            clearInterval(timerInterval);
                            timerDisplay.textContent = "00:00";
                            alert("Batas waktu pembayaran 15 menit telah habis. Reservasi Anda dibatalkan.");
                            window.location.reload();
                            return;
                        }
                        
                        const minutes = Math.floor(remainingSeconds / 60);
                        const seconds = remainingSeconds % 60;
                        
                        timerDisplay.textContent = 
                            (minutes < 10 ? "0" : "") + minutes + ":" + 
                            (seconds < 10 ? "0" : "") + seconds;
                        
                        remainingSeconds--;
                    }
                    
                    updateTimer();
                    const timerInterval = setInterval(updateTimer, 1000);
                });
            </script>
            <?php endif; ?>

            <!-- Skeumorphic Digital Ticket -->
            <div class="max-w-md mx-auto mb-12">
                <?php 
                    $is_special = (isset($success_booking['is_special']) && $success_booking['is_special'] == 1);
                    $ticket_border = $is_special ? 'special-event-glow' : 'border-jazz-gold/40';
                    $ticket_title_color = $is_special ? 'text-amber-500' : 'text-jazz-gold';
                    $ticket_subtitle_color = $is_special ? 'text-amber-600/80' : 'text-jazz-goldDark/80';
                ?>
                <div class="bg-jazz-light border <?php echo $ticket_border; ?> rounded-2xl shadow-2xl relative overflow-hidden">
                    <!-- Ticket Header -->
                    <div class="bg-gradient-to-b <?php echo $is_special ? 'from-amber-500/10' : 'from-jazz-gold/10'; ?> to-transparent p-6 text-center border-b <?php echo $is_special ? 'border-amber-500/20' : 'border-jazz-gold/20'; ?>">
                        <h3 class="font-heading text-xl font-bold <?php echo $ticket_title_color; ?> tracking-widest">THE 4 STAIRS</h3>
                        <p class="text-[9px] uppercase tracking-wider <?php echo $ticket_subtitle_color; ?> mt-1"><?php echo $is_special ? 'Special Concert entry pass' : 'Chamber Music Entry Pass'; ?></p>
                    </div>
                    
                    <div class="p-6 space-y-4">
                        <div class="flex justify-between items-start gap-4">
                            <div>
                                <span class="block text-[10px] <?php echo $is_special ? 'text-amber-700/70' : 'text-jazz-goldDark/70'; ?> uppercase tracking-wider">Nama Pemesan</span>
                                <span class="text-sm font-semibold text-jazz-darkest"><?php echo htmlspecialchars($success_booking['nama']); ?></span>
                            </div>
                            <div class="text-right">
                                <span class="block text-[10px] <?php echo $is_special ? 'text-amber-700/70' : 'text-jazz-goldDark/70'; ?> uppercase tracking-wider">ID Tiket</span>
                                <span class="text-sm font-heading font-bold <?php echo $ticket_title_color; ?>"><?php echo $success_booking['id_pesanan']; ?></span>
                            </div>
                        </div>

                        <div class="flex justify-between items-start gap-4">
                            <div>
                                <span class="block text-[10px] text-jazz-goldDark/70 uppercase tracking-wider">Hari & Tanggal</span>
                                <span class="text-sm font-semibold text-jazz-darkest">
                                    <?php echo $success_booking['hari'] . ", " . date('d F Y', strtotime($success_booking['tanggal'])); ?>
                                </span>
                            </div>
                            <div class="text-right">
                                <span class="block text-[10px] text-jazz-goldDark/70 uppercase tracking-wider">Waktu Show</span>
                                <span class="text-sm font-semibold text-jazz-darkest"><?php echo $success_booking['jam']; ?></span>
                            </div>
                        </div>

                        <div class="flex justify-between items-start gap-4">
                            <div>
                                <span class="block text-[10px] text-jazz-goldDark/70 uppercase tracking-wider">Jumlah Tiket</span>
                                <span class="text-sm font-semibold text-jazz-darkest"><?php echo $success_booking['jumlah_tiket']; ?> Orang (Kursi Penonton)</span>
                            </div>
                            <div class="text-right">
                                <span class="block text-[10px] text-jazz-goldDark/70 uppercase tracking-wider">Status</span>
                                <span class="text-xs font-bold text-amber-700 uppercase tracking-wide">
                                    <?php echo $success_booking['status_pembayaran']; ?>
                                </span>
                            </div>
                        </div>

                        <!-- Ticket Divider with Notch Left & Right -->
                        <div class="relative py-4 my-2">
                            <div class="absolute left-0 top-1/2 -translate-y-1/2 -ml-3 w-6 h-6 rounded-full bg-jazz-darkest border-r border-jazz-gold/40"></div>
                            <div class="absolute right-0 top-1/2 -translate-y-1/2 -mr-3 w-6 h-6 rounded-full bg-jazz-darkest border-l border-jazz-gold/40"></div>
                            <div class="border-t border-dashed border-jazz-gold/45 w-full"></div>
                        </div>

                        <!-- Ticket QR Code -->
                        <div class="flex flex-col items-center justify-center">
                            <div class="bg-white p-3 rounded-lg shadow-lg mb-3">
                                <img src="https://api.qrserver.com/v1/create-qr-code/?size=150x150&data=<?php echo urlencode($success_booking['id_pesanan']); ?>" alt="QR Code Tiket" class="w-32 h-32">
                            </div>
                            <p class="text-[10px] text-jazz-goldDark/70 text-center italic font-medium">*Silakan simpan/screenshot tiket ini</p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Payment instructions via DANA QR code -->
            <div class="max-w-xl mx-auto bg-jazz-card/30 border <?php echo $is_special ? 'border-amber-500/10' : 'border-jazz-gold/10'; ?> rounded-2xl p-8 text-center">
                <h3 class="font-heading text-lg text-white mb-6">Sistem Pembayaran</h3>
                <?php 
                    $harga_tiket = 75000;
                    $total_bayar = $success_booking['jumlah_tiket'] * $harga_tiket;
                ?>
                <p class="text-jazz-muted text-xs md:text-sm mb-1">Total Pembayaran:</p>
                <p class="text-3xl font-bold <?php echo $is_special ? 'text-amber-500' : 'text-jazz-gold'; ?> mb-2">Rp <?php echo number_format($total_bayar, 0, ',', '.'); ?></p>
                <p class="text-[11px] text-jazz-muted mb-8">
                    (Tarif: Rp <?php echo number_format($harga_tiket, 0, ',', '.'); ?> / Kursi Penonton)
                </p>

                <p class="text-xs md:text-sm text-jazz-light leading-relaxed max-w-md mx-auto mb-6">
                    Silakan scan QR DANA di bawah ini menggunakan aplikasi DANA atau e-wallet lain untuk melakukan transfer pembayaran:
                </p>

                <div class="relative w-48 h-48 mx-auto bg-white p-4 rounded-xl shadow-xl overflow-hidden mb-8 border-4 border-sky-600/30">
                    <div class="scan-line"></div>
                    <img src="assets/img/dana_qr_placeholder.png" alt="QR DANA" class="w-full h-full object-contain">
                </div>

                <div class="bg-jazz-gold/5 border border-jazz-gold/20 rounded-xl p-5 mb-8 text-xs text-jazz-gold font-light leading-relaxed text-left max-w-md mx-auto">
                    <strong class="font-semibold block mb-1">PENTING:</strong>
                    Harap kirimkan screenshot bukti transfer dan foto tiket digital Anda ke nomor WhatsApp Admin di bawah ini untuk aktivasi status LUNAS.
                </div>

                <?php
                    $wa_text = "Halo Admin The 4 Stairs Music Hall, saya sudah memesan tiket.\n\nBerikut detail pesanan saya:\n- ID Tiket: " . $success_booking['id_pesanan'] . "\n- Nama: " . $success_booking['nama'] . "\n- Jadwal: " . $success_booking['hari'] . ", " . date('d F Y', strtotime($success_booking['tanggal'])) . " (" . $success_booking['jam'] . ")\n- Jumlah Tiket: " . $success_booking['jumlah_tiket'] . " orang\n- Total Pembayaran: Rp " . number_format($total_bayar, 0, ',', '.') . "\n\nBerikut saya lampirkan bukti pembayaran e-wallet saya. Mohon konfirmasinya. Terima kasih!";
                    $wa_link = "https://wa.me/6281234567890?text=" . rawurlencode($wa_text);
                ?>
                <div class="mb-6">
                    <a href="<?php echo $wa_link; ?>" target="_blank" class="inline-flex items-center gap-2 bg-gradient-to-r from-emerald-600 to-green-500 hover:from-emerald-500 hover:to-green-400 text-white font-bold uppercase tracking-wider text-xs px-6 py-3.5 rounded-lg shadow-lg hover:shadow-green-500/20 transform hover:-translate-y-0.5 transition-all duration-300">
                        <svg class="w-4 h-4 fill-current" viewBox="0 0 24 24">
                            <path d="M12.012 2c-5.506 0-9.989 4.478-9.99 9.984a9.96 9.96 0 0 0 1.333 4.982L2 22l5.163-1.355a9.95 9.95 0 0 0 4.845 1.258h.005c5.507 0 9.99-4.478 9.99-9.986 0-2.669-1.037-5.176-2.922-7.062A9.92 9.92 0 0 0 12.012 2zm5.792 14.283c-.319.893-1.578 1.639-2.171 1.716-.525.068-1.205.105-3.551-.827-2.996-1.192-4.912-4.229-5.061-4.428-.15-.199-1.201-1.597-1.201-3.047 0-1.45.76-2.164 1.032-2.454.273-.29.596-.363.796-.363.2 0 .399.002.573.01.181.009.424-.035.664.542.247.596.843 2.057.915 2.203.072.146.12.316.022.512-.097.195-.147.316-.293.487-.146.171-.307.382-.439.513-.146.146-.3.307-.129.6.171.293.76 1.252 1.63 2.029.932.83 1.716 1.087 1.959 1.21.244.122.385.102.528-.063.143-.166.611-.711.776-.955.165-.244.33-.205.556-.122.227.083 1.442.678 1.69.8.247.122.412.183.473.287.061.104.061.602-.258 1.495z"/>
                        </svg>
                        Kirim Bukti Pembayaran ke WhatsApp Admin
                    </a>
                </div>
                
                <div>
                    <a href="index" class="text-jazz-muted hover:text-white text-xs transition-colors duration-300">&larr; Kembali Ke Halaman Utama</a>
                </div>
            </div>

        <?php else: ?>
            <!-- ===============================================================
                 STATE 1: FORM RESERVASI KURSI PERTUNJUKAN
                 =============================================================== -->
            <div class="text-center mb-10">
                <h2 class="font-heading text-3xl text-white tracking-wide">Reservasi Kursi Pertunjukan</h2>
                <p class="text-jazz-muted text-xs md:text-sm mt-2">
                    Silakan isi form di bawah ini untuk pemesanan kursi pertunjukan di The 4 Stairs Music Hall.
                </p>
            </div>

            <?php if (!empty($error_msg)): ?>
                <div class="bg-jazz-crimson/10 border border-jazz-crimson/30 rounded-xl p-4 max-w-xl mx-auto mb-8 text-jazz-crimson text-xs md:text-sm text-center">
                    <strong class="font-semibold">Peringatan:</strong> <?php echo $error_msg; ?>
                </div>
            <?php endif; ?>

            <div class="max-w-xl mx-auto glass-card rounded-2xl p-6 md:p-8">

                <!-- Info akun yang sedang login -->
                <div class="bg-jazz-gold/5 border border-jazz-gold/15 rounded-xl p-4 mb-6 flex items-start gap-3">
                    <svg class="w-4 h-4 text-jazz-gold mt-0.5 shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                    </svg>
                    <div class="text-xs text-jazz-muted leading-relaxed">
                        <span class="text-jazz-gold font-semibold block mb-1">Pemesanan atas akun:</span>
                        <span class="text-white"><?php echo htmlspecialchars($session_email); ?></span>
                        <span class="mx-1.5 text-jazz-gold/40">&bull;</span>
                        <span class="text-white"><?php echo htmlspecialchars($session_no_wa); ?></span>
                        <p class="mt-1.5 text-jazz-muted/80">Email dan nomor WA diambil otomatis dari akun Anda. Hanya nama pemesan yang perlu diisi.</p>
                    </div>
                </div>

                <form action="pesan" method="POST" class="space-y-6">
                    
                    <!-- Form: Pilih Jadwal -->
                    <div class="flex flex-col gap-2">
                        <label for="id_jadwal" class="text-xs font-semibold uppercase tracking-wider text-jazz-gold">Pilih Hari & Jadwal Acara</label>
                        <div class="relative">
                            <select name="id_jadwal" id="id_jadwal" required class="w-full bg-jazz-input border border-jazz-gold/20 rounded-lg py-3 px-4 text-xs md:text-sm text-white focus:outline-none focus:border-jazz-gold transition-colors duration-300 appearance-none cursor-pointer">
                                <option value="" class="bg-jazz-darkest">-- Pilih Jadwal Pertunjukan --</option>
                                <?php 
                                $preset_id = isset($_GET['id_jadwal']) ? intval($_GET['id_jadwal']) : 0;
                                foreach ($schedules as $sch): 
                                    $sisa = $sch['kuota'] - $sch['terjual'];
                                    if ($sisa <= 0 || strtolower($sch['status']) === 'closed') continue; 
                                    
                                    $selected = ($preset_id == $sch['id']) ? "selected" : "";
                                    $tgl_fmt = date('d M Y', strtotime($sch['tanggal']));
                                    $label_spec = ($sch['is_special'] == 1) ? " [SPECIAL SHOW]" : "";
                                ?>
                                    <option value="<?php echo $sch['id']; ?>" <?php echo $selected; ?> class="bg-jazz-darkest">
                                        <?php echo htmlspecialchars($sch['nama_event']); ?><?php echo $label_spec; ?> - <?php echo $sch['hari']; ?>, <?php echo $tgl_fmt; ?> (<?php echo $sch['jam']; ?>) - Sisa <?php echo $sisa; ?> Kursi
                                    </option>
                                <?php endforeach; ?>
                            </select>
                            <div class="pointer-events-none absolute inset-y-0 right-0 flex items-center px-4 text-jazz-gold">
                                <svg class="fill-current h-4 w-4" viewBox="0 0 20 20">
                                    <path d="M9.293 12.95l.707.707L15.657 8l-1.414-1.414L10 10.828 5.757 6.586 4.343 8z"/>
                                </svg>
                            </div>
                        </div>
                    </div>

                    <!-- Form: Nama Pemesan (bisa diubah, misal beli untuk teman) -->
                    <div class="flex flex-col gap-2">
                        <label for="nama" class="text-xs font-semibold uppercase tracking-wider text-jazz-gold">
                            Atas Nama
                        </label>
                        <input type="text" name="nama" id="nama"
                               placeholder="Nama pemesan / penerima tiket"
                               value="<?php echo htmlspecialchars(isset($_POST['nama']) ? $_POST['nama'] : $session_nama); ?>"
                               required
                               class="w-full bg-jazz-input border border-jazz-gold/20 rounded-lg py-3 px-4 text-xs md:text-sm text-white placeholder-gray-600 focus:outline-none focus:border-jazz-gold transition-colors duration-300">
                        <span class="text-[10px] text-jazz-muted leading-tight">Boleh nama Anda atau nama orang yang akan menggunakan tiket.</span>
                    </div>

                    <!-- Form: Jumlah Tiket -->
                    <div class="flex flex-col gap-2">
                        <label for="jumlah_tiket" class="text-xs font-semibold uppercase tracking-wider text-jazz-gold">Jumlah Kursi/Tiket</label>
                        <input type="number" name="jumlah_tiket" id="jumlah_tiket" min="1" max="50" value="1" required class="w-full bg-jazz-input border border-jazz-gold/20 rounded-lg py-3 px-4 text-xs md:text-sm text-white focus:outline-none focus:border-jazz-gold transition-colors duration-300">
                        <span class="text-[10px] text-jazz-muted leading-tight">Maksimal pembelian 50 kursi per transaksi (sesuai kuota venue).</span>
                    </div>

                    <button type="submit" name="submit_booking" class="w-full bg-gradient-to-r from-jazz-gold to-jazz-goldDark text-white font-bold uppercase tracking-wider text-xs py-3.5 rounded-lg shadow-lg hover:shadow-jazz-gold/20 transform hover:-translate-y-0.5 transition-all duration-300 cursor-pointer">
                        Pesan & Lanjut Pembayaran
                    </button>
                    
                    <div class="text-center">
                        <a href="index" class="text-jazz-muted hover:text-white text-xs transition-colors duration-300">&larr; Batalkan dan Kembali</a>
                    </div>
                </form>
            </div>
        <?php endif; ?>

    </main>

    <!-- Footer Section -->
    <footer class="bg-jazz-darkest border-t border-jazz-gold/10 pt-16 pb-8 relative overflow-hidden">
        <!-- Background decorative glow -->
        <div class="absolute -bottom-24 -left-24 w-80 h-80 rounded-full bg-jazz-gold/5 filter blur-[80px] pointer-events-none"></div>
        
        <div class="max-w-6xl mx-auto px-6 grid grid-cols-1 md:grid-cols-12 gap-8 mb-12">
            <!-- Col 1: Brand (4 cols) -->
            <div class="md:col-span-4 text-left">
                <a href="<?php echo $prefix; ?>index" class="flex items-center gap-2 font-heading text-xl font-bold tracking-wider text-jazz-gold mb-4">
                    <img src="<?php echo $prefix; ?>4stairswhite.png" alt="Logo" class="w-8 h-8 object-contain">
                    <span>THE 4 <span class="text-white">STAIRS</span></span>
                </a>
                <p class="text-jazz-muted text-xs font-light leading-relaxed mb-6 max-w-sm">
                    Live Music Venue & Concert Hall terbaik di Yogyakarta. Menghadirkan pertunjukan musik berkelas dengan atmosfer klasik yang intim dan akustik premium.
                </p>
                <div class="flex items-center gap-3">
                    <!-- WhatsApp -->
                    <a href="https://wa.me/628123456789" target="_blank" class="w-8 h-8 rounded-full bg-white/5 border border-white/10 hover:border-green-500 hover:bg-green-500/10 hover:text-green-500 flex items-center justify-center text-gray-400 transition-all duration-300" title="WhatsApp">
                        <svg class="w-4 h-4 fill-current" viewBox="0 0 24 24">
                            <path d="M.057 24l1.687-6.163c-1.041-1.804-1.588-3.849-1.587-5.946C.06 5.348 5.397.01 12.008.01c3.202.001 6.212 1.246 8.477 3.514 2.266 2.268 3.507 5.28 3.505 8.484-.004 6.657-5.34 11.997-11.953 11.997-2.005-.001-3.973-.502-5.724-1.455L0 24zm6.59-4.846c1.6.95 3.488 1.459 5.407 1.46h.007c5.855 0 10.618-4.761 10.621-10.619.002-2.837-1.102-5.505-3.108-7.513C17.569 4.475 14.9 3.37c-5.86 0-10.627 4.76-10.63 10.619-.001 1.884.49 3.73 1.42 5.34L1.968 22.25l4.679-1.256zM17.47 15.1c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347z"/>
                        </svg>
                    </a>
                    <!-- Email -->
                    <a href="mailto:info@the4stairs.com" class="w-8 h-8 rounded-full bg-white/5 border border-white/10 hover:border-amber-500 hover:bg-amber-500/10 hover:text-amber-500 flex items-center justify-center text-gray-400 transition-all duration-300" title="Email">
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/>
                        </svg>
                    </a>
                    <!-- Facebook -->
                    <a href="https://facebook.com/the4stairs" target="_blank" class="w-8 h-8 rounded-full bg-white/5 border border-white/10 hover:border-blue-500 hover:bg-blue-500/10 hover:text-blue-500 flex items-center justify-center text-gray-400 transition-all duration-300" title="Facebook">
                        <svg class="w-4 h-4 fill-current" viewBox="0 0 24 24">
                            <path d="M24 12.073c0-6.627-5.373-12-12-12s-12 5.373-12 12c0 5.99 4.388 10.954 10.125 11.854v-8.385H7.078v-3.47h3.047V9.43c0-3.007 1.792-4.669 4.533-4.669 1.312 0 2.686.235 2.686.235v2.953H15.83c-1.491 0-1.956.925-1.956 1.874v2.25h3.328l-.532 3.47h-2.796v8.385C19.612 23.027 24 18.062 24 12.073z"/>
                        </svg>
                    </a>
                    <!-- Instagram -->
                    <a href="https://instagram.com/the4stairs" target="_blank" class="w-8 h-8 rounded-full bg-white/5 border border-white/10 hover:border-pink-500 hover:bg-pink-500/10 hover:text-pink-500 flex items-center justify-center text-gray-400 transition-all duration-300" title="Instagram">
                        <svg class="w-4 h-4 fill-current" viewBox="0 0 24 24">
                            <path d="M12 2.163c3.204 0 3.584.012 4.85.07 3.252.148 4.771 1.691 4.919 4.919.058 1.265.069 1.645.069 4.849 0 3.205-.012 3.584-.069 4.849-.149 3.225-1.664 4.771-4.919 4.919-1.266.058-1.644.07-4.85.07-3.204 0-3.584-.012-4.849-.07-3.26-.149-4.771-1.699-4.919-4.92-.058-1.265-.07-1.644-.07-4.849 0-3.204.013-3.583.07-4.849.149-3.227 1.664-4.771 4.919-4.919 1.266-.057 1.645-.069 4.849-.069zM12 0C8.741 0 8.333.014 7.053.072 2.695.272.273 2.69.073 7.051.014 8.333 0 8.741 0 12c0 3.259.014 3.668.072 4.948.2 4.358 2.618 6.78 6.98 6.98 1.281.058 1.689.072 4.948.072 3.259 0 3.668-.014 4.948-.072 4.354-.2 6.782-2.618 6.979-6.98.059-1.28.073-1.689.073-4.948 0-3.259-.014-3.667-.072-4.947-.196-4.354-2.617-6.78-6.979-6.98C15.668.014 15.259 0 12 0zm0 5.838a6.162 6.162 0 100 12.324 6.162 6.162 0 000-12.324zM12 16a4 4 0 110-8 4 4 0 010 8zm6.406-11.845a1.44 1.44 0 100 2.881 1.44 1.44 0 000-2.881z"/>
                        </svg>
                    </a>
                    <!-- YouTube -->
                    <a href="https://youtube.com/the4stairs" target="_blank" class="w-8 h-8 rounded-full bg-white/5 border border-white/10 hover:border-red-600 hover:bg-red-600/10 hover:text-red-600 flex items-center justify-center text-gray-400 transition-all duration-300" title="YouTube">
                        <svg class="w-4 h-4 fill-current" viewBox="0 0 24 24">
                            <path d="M23.498 6.163a3.003 3.003 0 0 0-2.11-2.11C19.517 3.545 12 3.545 12 3.545s-7.517 0-9.388.508a3.003 3.003 0 0 0-2.11 2.11C0 8.033 0 12 0 12s0 3.967.502 5.837a3.003 3.003 0 0 0 2.11 2.11c1.871.508 9.388.508 9.388.508s7.517 0 9.388-.508a3.003 3.003 0 0 0 2.11-2.11C24 15.967 24 12 24 12s0-3.967-.502-5.837zM9.545 15.568V8.432L15.818 12l-6.273 3.568z"/>
                        </svg>
                    </a>
                    <!-- X -->
                    <a href="https://x.com/the4stairs" target="_blank" class="w-8 h-8 rounded-full bg-white/5 border border-white/10 hover:border-white hover:bg-white/10 hover:text-white flex items-center justify-center text-gray-400 transition-all duration-300" title="X (Twitter)">
                        <svg class="w-3.5 h-3.5 fill-current" viewBox="0 0 24 24">
                            <path d="M18.244 2.25h3.308l-7.227 8.26 8.502 11.24H16.17l-5.214-6.817L4.99 21.75H1.68l7.73-8.835L1.254 2.25H8.08l4.713 6.231zm-1.161 17.52h1.833L7.084 4.126H5.117z"/>
                        </svg>
                    </a>
                </div>
            </div>
            
            <!-- Col 2: Quick Links (3 cols) -->
            <div class="md:col-span-3 text-left">
                <h4 class="text-white font-semibold text-xs uppercase tracking-wider mb-4 border-l-2 border-jazz-gold pl-2">Navigasi Cepat</h4>
                <ul class="space-y-2.5 text-xs text-jazz-muted font-light">
                    <li><a href="<?php echo $prefix; ?>index" class="hover:text-jazz-gold transition-colors duration-200 block">&rarr; Beranda</a></li>
                    <li><a href="<?php echo $prefix; ?>profil" class="hover:text-jazz-gold transition-colors duration-200 block">&rarr; Profil & Venue</a></li>
                    <li><a href="<?php echo $prefix; ?>event" class="hover:text-jazz-gold transition-colors duration-200 block">&rarr; Jadwal Event</a></li>
                    <li><a href="<?php echo $prefix; ?>pesan" class="hover:text-jazz-gold transition-colors duration-200 block">&rarr; Pesan Tiket</a></li>
                    <li><a href="<?php echo $prefix; ?>komposisi" class="hover:text-jazz-gold transition-colors duration-200 block">&rarr; Repertoar Lagu</a></li>
                    <li><a href="<?php echo $prefix; ?>berita" class="hover:text-jazz-gold transition-colors duration-200 block">&rarr; Berita & Warta</a></li>
                </ul>
            </div>
            
            <!-- Col 3: Operating Hours (3 cols) -->
            <div class="md:col-span-3 text-left">
                <h4 class="text-white font-semibold text-xs uppercase tracking-wider mb-4 border-l-2 border-jazz-gold pl-2">Jam Operasional</h4>
                <ul class="space-y-2 text-xs text-jazz-muted font-light">
                    <li>
                        <span class="text-white/80 block font-medium">Senin - Kamis:</span>
                        18:00 - 23:00 WIB
                    </li>
                    <li>
                        <span class="text-white/80 block font-medium">Jumat - Sabtu:</span>
                        18:00 - 00:00 WIB
                    </li>
                    <li>
                        <span class="text-white/80 block font-medium">Minggu:</span>
                        17:00 - 23:00 WIB
                    </li>
                </ul>
            </div>
            
            <!-- Col 4: Contact & Location (2 cols) -->
            <div class="md:col-span-2 text-left">
                <h4 class="text-white font-semibold text-xs uppercase tracking-wider mb-4 border-l-2 border-jazz-gold pl-2">Alamat & Kontak</h4>
                <address class="not-italic text-xs text-jazz-muted font-light space-y-2">
                    <p>
                        Jl. Malioboro No. 44, Lantai 4,<br>
                        Gedong Tengen, Yogyakarta
                    </p>
                    <p class="pt-2 text-jazz-gold font-medium">
                        WA: +62 812-3456-789<br>
                        Email: info@the4stairs.com
                    </p>
                </address>
            </div>
        </div>
        
        <!-- Bottom Divider & Copyright -->
        <div class="max-w-6xl mx-auto px-6 border-t border-white/5 pt-6 flex flex-col md:flex-row items-center justify-between gap-4">
            <p class="text-jazz-muted text-xs font-light text-center md:text-left">
                &copy; <?php echo date('Y'); ?> The 4 Stairs Music Hall. Tugas Projek Kuliah.
            </p>
            <p class="text-[9px] text-neutral-600 text-center md:text-right font-light">
                Classy Live Music &copy; Real Composers Association
            </p>
        </div>
    </footer>

    <script src="assets/js/main.js"></script>
</body>
</html>
