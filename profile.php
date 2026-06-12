<?php
/**
 * THE 4 STAIRS MUSIC HALL - USER PROFILE & PURCHASE HISTORY DASHBOARD
 * -----------------------------------------------------------------
 * Dashboard untuk penonton yang telah masuk.
 * Menampilkan histori pemesanan tiket, status pembayaran, profil pengguna,
 * ganti username, dan simulasi reset password ke email.
 */

// Memulai session
session_start();

// Jika belum login, alihkan ke login
if (!isset($_SESSION['user_logged_in']) || $_SESSION['user_logged_in'] !== true) {
    header("Location: login");
    exit;
}

// Sertakan file koneksi database
include 'koneksi.php';

$success_profile = "";
$error_profile = "";
$success_reset = "";

// Tangani Update Profile (Ubah Nama dan WhatsApp)
if (isset($_POST['update_profile'])) {
    $new_name = $conn->real_escape_string(trim($_POST['name']));
    $new_wa = $conn->real_escape_string(trim($_POST['no_wa']));
    $user_id = $_SESSION['user_id'];
    
    if (empty($new_name) || empty($new_wa)) {
        $error_profile = "Nama dan Nomor WhatsApp tidak boleh kosong.";
    } else {
        $stmt_upd = $conn->prepare("UPDATE `users` SET `name` = ?, `no_wa` = ? WHERE `id` = ?");
        $stmt_upd->bind_param("ssi", $new_name, $new_wa, $user_id);
        if ($stmt_upd->execute()) {
            $_SESSION['user_name'] = $new_name;
            $_SESSION['user_wa'] = $new_wa;
            $success_profile = "Profil berhasil diperbarui.";
        } else {
            $error_profile = "Gagal memperbarui profil: " . $conn->error;
        }
        $stmt_upd->close();
    }
}

// Tangani Reset Password (Simulasi)
if (isset($_POST['reset_password_sim'])) {
    $user_email = $_SESSION['user_email'];
    $success_reset = "Simulasi: Link reset password berhasil dikirim ke email <strong>" . htmlspecialchars($user_email) . "</strong>. Silakan periksa kotak masuk atau spam Anda.";
}

// Ambil riwayat pemesanan berdasarkan email user
$user_email = $_SESSION['user_email'];
$tickets = [];
$stmt_tix = $conn->prepare("SELECT p.*, j.nama_event, j.hari, j.tanggal, j.jam FROM `pesanan` p 
                            JOIN `jadwal` j ON p.id_jadwal = j.id 
                            WHERE p.email = ? 
                            ORDER BY p.waktu_pesan DESC");
$stmt_tix->bind_param("s", $user_email);
$stmt_tix->execute();
$res_tix = $stmt_tix->get_result();
if ($res_tix) {
    while ($row = $res_tix->fetch_assoc()) {
        $tickets[] = $row;
    }
}
$stmt_tix->close();
?>
<!DOCTYPE html>
<html lang="id" class="scroll-smooth">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard Saya - The 4 Stairs Music Hall</title>
    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700&family=Playfair+Display:ital,wght@0,400;0,600;0,700;1,400&display=swap" rel="stylesheet">
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
                            gold: '#8b1e22',       // velvet crimson red
                            goldDark: '#78350f',   // warm mahogany brown
                            crimson: '#991b1b',    // velvet crimson accent
                            muted: '#a89f91',      // warm stone/muted grey
                            light: '#ece6dc'       // warm vintage paper cream
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
    <style>
        ::-webkit-scrollbar {
            width: 6px;
        }
        ::-webkit-scrollbar-track {
            background: #090706;
        }
        ::-webkit-scrollbar-thumb {
            background: #2c2523;
            border-radius: 3px;
        }
        ::-webkit-scrollbar-thumb:hover {
            background: #8b1e22;
        }
    </style>
</head>
<body class="bg-jazz-darkest text-jazz-light font-body flex flex-col min-h-screen overflow-x-hidden pt-24">

    <!-- Reusable Navbar -->
    <?php include 'navbar.php'; ?>

    <!-- Main Container -->
    <main class="max-w-6xl mx-auto px-6 py-12 flex-grow w-full">
        
        <!-- Header Dashboard -->
        <div class="mb-10 text-center md:text-left flex flex-col md:flex-row justify-between items-center gap-4 border-b border-white/5 pb-6">
            <div>
                <span class="text-jazz-gold text-xs font-semibold uppercase tracking-widest mb-1.5 block">Customer Portal</span>
                <h1 class="font-heading text-3xl md:text-4xl font-bold text-white tracking-wide">Halo, <?php echo htmlspecialchars($_SESSION['user_name']); ?>!</h1>
                <p class="text-jazz-muted text-xs md:text-sm mt-1">Kelola informasi profil dan pantau riwayat pembelian tiket Anda.</p>
            </div>
            <div>
                <a href="logout" class="inline-block bg-zinc-900 hover:bg-jazz-gold/10 border border-white/10 hover:border-jazz-gold text-white font-bold uppercase tracking-wider text-xs px-5 py-2.5 rounded-lg transition-all duration-300">
                    Keluar / Logout
                </a>
            </div>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-12 gap-8 items-start">
            
            <!-- COLUMN 1: Profile Settings (4 cols) -->
            <section class="lg:col-span-4 space-y-6">
                <!-- Profile Settings Card -->
                <div class="glass-card rounded-2xl p-6 border border-jazz-gold/10 relative overflow-hidden">
                    <div class="absolute top-0 left-0 w-full h-[3px] bg-gradient-to-r from-jazz-gold to-jazz-goldDark"></div>
                    <h3 class="font-heading text-lg font-semibold text-white mb-6 border-b border-white/5 pb-2">Pengaturan Profil</h3>
                    
                    <?php if (!empty($success_profile)): ?>
                        <div class="bg-emerald-600/10 border border-emerald-600/30 rounded-lg p-3 mb-4 text-emerald-400 text-xs text-center font-medium">
                            <?php echo $success_profile; ?>
                        </div>
                    <?php elseif (!empty($error_profile)): ?>
                        <div class="bg-jazz-crimson/10 border border-jazz-crimson/30 rounded-lg p-3 mb-4 text-jazz-crimson text-xs text-center font-medium">
                            <?php echo $error_profile; ?>
                        </div>
                    <?php endif; ?>

                    <form action="profile" method="POST" class="space-y-4">
                        <div class="space-y-1">
                            <label for="name" class="block text-[10px] font-semibold uppercase tracking-wider text-jazz-muted">Nama Pengguna</label>
                            <input type="text" name="name" id="name" required
                                   value="<?php echo htmlspecialchars($_SESSION['user_name']); ?>"
                                   class="w-full px-3 py-2 bg-jazz-input border border-white/10 rounded-lg text-white placeholder-stone-600 focus:outline-none focus:border-jazz-gold transition-colors duration-300 text-xs md:text-sm">
                        </div>

                        <div class="space-y-1">
                            <label for="email" class="block text-[10px] font-semibold uppercase tracking-wider text-jazz-muted">Alamat Email (Tetap)</label>
                            <input type="email" name="email" id="email" disabled
                                   value="<?php echo htmlspecialchars($_SESSION['user_email']); ?>"
                                   class="w-full px-3 py-2 bg-zinc-950/60 border border-white/5 rounded-lg text-stone-500 cursor-not-allowed text-xs md:text-sm">
                        </div>

                        <div class="space-y-1">
                            <label for="no_wa" class="block text-[10px] font-semibold uppercase tracking-wider text-jazz-muted">No. WhatsApp</label>
                            <input type="text" name="no_wa" id="no_wa" required
                                   value="<?php echo htmlspecialchars($_SESSION['user_wa']); ?>"
                                   class="w-full px-3 py-2 bg-jazz-input border border-white/10 rounded-lg text-white placeholder-stone-600 focus:outline-none focus:border-jazz-gold transition-colors duration-300 text-xs md:text-sm">
                        </div>

                        <button type="submit" name="update_profile" 
                                class="w-full py-2.5 bg-jazz-gold hover:bg-jazz-goldDark text-white font-bold uppercase tracking-wider text-xs rounded-lg shadow hover:shadow-jazz-gold/15 transition-all duration-300 cursor-pointer">
                            Simpan Perubahan
                        </button>
                    </form>
                </div>

                <!-- Password Reset Card -->
                <div class="glass-card rounded-2xl p-6 border border-jazz-gold/10 relative overflow-hidden">
                    <h3 class="font-heading text-lg font-semibold text-white mb-3 border-b border-white/5 pb-2">Ubah Kata Sandi</h3>
                    <p class="text-jazz-muted text-xs leading-relaxed mb-4">
                        Untuk keamanan, instruksi penggantian password akan dikirim ke alamat email Anda yang terdaftar.
                    </p>

                    <?php if (!empty($success_reset)): ?>
                        <div class="bg-emerald-600/10 border border-emerald-600/30 rounded-lg p-3 mb-4 text-emerald-400 text-xs text-left font-light leading-relaxed">
                            <?php echo $success_reset; ?>
                        </div>
                    <?php endif; ?>

                    <form action="profile" method="POST">
                        <button type="submit" name="reset_password_sim" 
                                class="w-full py-2.5 bg-zinc-900 hover:bg-zinc-800 border border-white/10 text-white font-bold uppercase tracking-wider text-xs rounded-lg transition-all duration-300 cursor-pointer">
                            Kirim Link Reset Ke Email
                        </button>
                    </form>
                </div>
            </section>

            <!-- COLUMN 2: Ticket Purchase History (8 cols) -->
            <section class="lg:col-span-8">
                <div class="glass-card rounded-2xl p-6 border border-jazz-gold/10 min-h-[400px]">
                    <h3 class="font-heading text-xl font-semibold text-white mb-6 border-b border-white/5 pb-3">Riwayat Pembelian Tiket</h3>
                    
                    <?php if (empty($tickets)): ?>
                        <div class="flex flex-col items-center justify-center py-20 text-center text-jazz-muted">
                            <svg class="w-12 h-12 text-stone-700 mb-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M15 5v2m0 4v2m0 4v2M5 5a2 2 0 00-2 2v3a2 2 0 110 4v3a2 2 0 002 2h14a2 2 0 002-2v-3a2 2 0 110-4V7a2 2 0 00-2-2H5z" />
                            </svg>
                            <p class="text-sm font-medium">Anda belum pernah melakukan pemesanan tiket.</p>
                            <a href="pesan" class="text-jazz-gold hover:text-white font-semibold text-xs mt-3 underline transition-colors">Pesan Kursi Pertama Anda &rarr;</a>
                        </div>
                    <?php else: ?>
                        <div class="space-y-4">
                            <?php foreach ($tickets as $tix): 
                                $harga_satuan = 75000;
                                $total_bayar = $tix['jumlah_tiket'] * $harga_satuan;
                                $status = $tix['status_pembayaran'];
                                $is_lunas = (strtolower($status) !== 'pending');
                            ?>
                                <div class="bg-jazz-input/40 border border-white/5 hover:border-jazz-gold/30 rounded-xl p-5 flex flex-col md:flex-row justify-between items-start md:items-center gap-4 transition-all duration-300 hover:shadow-lg">
                                    <div class="space-y-1">
                                        <div class="flex items-center gap-2">
                                            <span class="text-xs font-bold text-jazz-gold font-heading tracking-wider"><?php echo $tix['id_pesanan']; ?></span>
                                            <span class="text-[9px] text-zinc-500 font-light"><?php echo date('d M Y, H:i', strtotime($tix['waktu_pesan'])); ?></span>
                                        </div>
                                        <h4 class="text-white text-sm font-semibold tracking-wide"><?php echo htmlspecialchars($tix['nama_event']); ?></h4>
                                        <p class="text-jazz-muted text-xs font-light">
                                            <?php echo $tix['hari']; ?>, <?php echo date('d M Y', strtotime($tix['tanggal'])); ?> &bull; <?php echo $tix['jam']; ?>
                                        </p>
                                        <p class="text-[10px] text-jazz-muted">
                                            <?php echo $tix['jumlah_tiket']; ?> Tiket &bull; Total: <span class="text-white font-semibold">Rp <?php echo number_format($total_bayar, 0, ',', '.'); ?></span>
                                        </p>
                                    </div>
                                    
                                    <div class="flex items-center gap-3 w-full md:w-auto justify-between md:justify-end">
                                        <?php if ($is_lunas): ?>
                                            <?php if (strtolower($status) === 'sudah dipakai'): ?>
                                                <span class="text-[10px] bg-zinc-600/10 border border-zinc-600/30 text-zinc-500 font-bold uppercase tracking-wider px-2.5 py-1 rounded">Telah Terpakai</span>
                                            <?php else: ?>
                                                <span class="text-[10px] bg-emerald-600/10 border border-emerald-600/30 text-emerald-400 font-bold uppercase tracking-wider px-2.5 py-1 rounded">Lunas</span>
                                            <?php endif; ?>
                                            <!-- Button Details triggers JS modal popup -->
                                            <button onclick="openTicketModal(<?php echo htmlspecialchars(json_encode($tix)); ?>, <?php echo $total_bayar; ?>)"
                                                    class="px-4 py-2 bg-jazz-gold hover:bg-jazz-goldDark text-white text-xs font-bold uppercase tracking-wider rounded-lg shadow transition-all duration-300 cursor-pointer">
                                                Detail Tiket
                                            </button>
                                        <?php else: ?>
                                            <span class="text-[10px] bg-amber-500/10 border border-amber-500/30 text-amber-500 font-bold uppercase tracking-wider px-2.5 py-1 rounded">Pending</span>
                                            <!-- Redirect to checkout timer page -->
                                            <a href="pesan?sukses=<?php echo urlencode($tix['id_pesanan']); ?>"
                                               class="px-4 py-2 bg-amber-500 hover:bg-amber-600 text-white text-xs font-bold uppercase tracking-wider rounded-lg shadow transition-all duration-300 inline-block text-center font-bold">
                                                Bayar Sekarang
                                            </a>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </section>
        </div>

    </main>

    <!-- TICKET DETAIL MODAL (Intimate Glass Overlay) -->
    <div id="ticketModal" class="fixed inset-0 z-50 bg-black/80 backdrop-blur-sm flex items-center justify-center p-4 opacity-0 pointer-events-none transition-all duration-300">
        <div class="bg-jazz-darkest border border-jazz-gold/30 rounded-2xl w-full max-w-md overflow-hidden relative shadow-2xl scale-95 transition-transform duration-300 flex flex-col">
            
            <!-- Modal Header -->
            <div class="border-b border-white/5 p-5 flex justify-between items-center">
                <h3 class="font-heading text-lg font-bold text-white tracking-wide">Detail Pemesanan</h3>
                <button onclick="closeTicketModal()" class="text-jazz-muted hover:text-white text-2xl font-bold focus:outline-none">&times;</button>
            </div>
            
            <!-- Modal Body (Scrollable if content overflows) -->
            <div class="p-6 overflow-y-auto max-h-[75vh]" id="modalContent">
                <!-- Content injected dynamically via JS -->
            </div>
        </div>
    </div>

    <!-- Footer Section -->
    <footer class="bg-jazz-darkest border-t border-jazz-gold/10 py-12 mt-auto">
        <div class="max-w-6xl mx-auto px-6 flex flex-col md:flex-row items-center justify-between gap-6">
            <div class="text-center md:text-left">
                <span class="font-heading text-lg font-bold tracking-wider text-jazz-gold">
                    THE 4 <span class="text-white">STAIRS</span>
                </span>
                <p class="text-jazz-muted text-[10px] mt-1 font-light">Music Hall & Live Performance Venue</p>
            </div>
            <div class="text-center md:text-right">
                <p class="text-jazz-muted text-xs font-light">&copy; <?php echo date('Y'); ?> The 4 Stairs Music Hall. All rights reserved.</p>
            </div>
        </div>
    </footer>

    <!-- Interactive Ticket Modal Script -->
    <script>
        const modal = document.getElementById('ticketModal');
        const modalContent = document.getElementById('modalContent');
        const modalInner = modal.querySelector('.max-w-md');

        function openTicketModal(tix, total) {
            // Bangun konten modal secara dinamis berdasarkan status pembayaran
            const isLunas = tix.status_pembayaran.toLowerCase() !== 'pending';
            const formattedTotal = new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR', minimumFractionDigits: 0 }).format(total);
            const isUsed = tix.status_pembayaran.toLowerCase() === 'sudah dipakai';
            
            let contentHtml = '';

            if (isLunas) {
                // RENDER TIKET STUB SKEUOMORPHIC (Lunas)
                contentHtml = `
                    <div class="bg-jazz-light border border-jazz-gold/40 rounded-xl shadow-2xl relative overflow-hidden text-jazz-darkest p-5 space-y-4">
                        ${isUsed 
                            ? `<div class="absolute inset-0 flex items-center justify-center pointer-events-none opacity-[0.08] select-none z-10">
                                 <span class="text-3xl font-extrabold border-4 border-zinc-950 text-zinc-950 px-4 py-2 uppercase tracking-widest rounded-lg transform -rotate-12">SUDAH DIPAKAI</span>
                               </div>` 
                            : ''
                        }
                        <!-- Top header -->
                        <div class="text-center border-b border-jazz-gold/20 pb-3">
                            <h3 class="font-heading text-lg font-bold text-jazz-gold tracking-widest">THE 4 STAIRS</h3>
                            <p class="text-[8px] uppercase tracking-wider text-jazz-goldDark/80 font-medium">Music Hall Entry Pass</p>
                        </div>
                        
                        <!-- Row 1 -->
                        <div class="flex justify-between gap-2 text-xs">
                            <div>
                                <span class="block text-[8px] text-jazz-goldDark/70 uppercase font-semibold">Nama Pemesan</span>
                                <span class="font-bold">${tix.nama}</span>
                            </div>
                            <div class="text-right">
                                <span class="block text-[8px] text-jazz-goldDark/70 uppercase font-semibold">ID Tiket</span>
                                <span class="font-heading font-extrabold text-jazz-gold text-sm">${tix.id_pesanan}</span>
                            </div>
                        </div>

                        <!-- Row 2 -->
                        <div class="flex justify-between gap-2 text-xs">
                            <div>
                                <span class="block text-[8px] text-jazz-goldDark/70 uppercase font-semibold">Event / Acara</span>
                                <span class="font-bold text-zinc-900">${tix.nama_event}</span>
                            </div>
                        </div>

                        <!-- Row 3 -->
                        <div class="flex justify-between gap-2 text-xs">
                            <div>
                                <span class="block text-[8px] text-jazz-goldDark/70 uppercase font-semibold">Hari & Tanggal</span>
                                <span class="font-bold">${tix.hari}, ${formatDate(tix.tanggal)}</span>
                            </div>
                            <div class="text-right">
                                <span class="block text-[8px] text-jazz-goldDark/70 uppercase font-semibold">Waktu</span>
                                <span class="font-bold">${tix.jam}</span>
                            </div>
                        </div>

                        <!-- Row 4 -->
                        <div class="flex justify-between gap-2 text-xs">
                            <div>
                                <span class="block text-[8px] text-jazz-goldDark/70 uppercase font-semibold">Jumlah Kursi</span>
                                <span class="font-bold">${tix.jumlah_tiket} Orang (Kursi Penonton)</span>
                            </div>
                            <div class="text-right">
                                <span class="block text-[8px] text-jazz-goldDark/70 uppercase font-semibold">Status</span>
                                ${isUsed
                                    ? `<span class="text-[9px] bg-blue-600/10 border border-blue-600/30 text-blue-700 font-extrabold uppercase px-1.5 py-0.5 rounded">Telah Terpakai</span>`
                                    : `<span class="text-[9px] bg-emerald-600/10 border border-emerald-600/30 text-emerald-700 font-extrabold uppercase px-1.5 py-0.5 rounded">Lunas</span>`
                                }
                            </div>
                        </div>

                        <!-- Skeuomorphic Notches -->
                        <div class="relative py-2">
                            <div class="absolute left-0 top-1/2 -translate-y-1/2 -ml-8 w-6 h-6 rounded-full bg-jazz-darkest border-r border-jazz-gold/30"></div>
                            <div class="absolute right-0 top-1/2 -translate-y-1/2 -mr-8 w-6 h-6 rounded-full bg-jazz-darkest border-l border-jazz-gold/30"></div>
                            <div class="border-t border-dashed border-jazz-gold/45 w-full"></div>
                        </div>

                        <!-- QR Code -->
                        <div class="flex flex-col items-center justify-center">
                            <div class="bg-white p-2.5 rounded-lg shadow border border-stone-200 mb-2">
                                <img src="https://api.qrserver.com/v1/create-qr-code/?size=120x120&data=${encodeURIComponent(tix.id_pesanan)}" alt="QR Code Tiket" class="w-28 h-28">
                            </div>
                            <p class="text-[9px] text-jazz-goldDark/70 font-semibold italic">*Scan QR di pintu masuk Music Hall</p>
                        </div>
                    </div>
                `;
            } else {
                // RENDER INSTRUKSI PEMBAYARAN (Pending)
                const waText = `Halo Admin The 4 Stairs Music Hall, saya ingin mengonfirmasi pembayaran untuk tiket tertunda.\n\nDetail Pesanan:\n- ID Tiket: ${tix.id_pesanan}\n- Nama: ${tix.nama}\n- Jadwal: ${tix.hari}, ${formatDate(tix.tanggal)} (${tix.jam})\n- Jumlah Tiket: ${tix.jumlah_tiket} orang\n- Total Pembayaran: ${formattedTotal}\n\nSaya melampirkan bukti transfer e-wallet DANA saya. Mohon segera dikonfirmasi. Terima kasih!`;
                const waLink = `https://wa.me/6281234567890?text=${encodeURIComponent(waText)}`;

                contentHtml = `
                    <div class="space-y-6 text-center">
                        <div class="bg-jazz-input border border-white/5 rounded-xl p-4 text-left">
                            <div class="flex justify-between items-center mb-3">
                                <span class="text-xs text-jazz-muted">ID Pemesanan:</span>
                                <span class="text-xs font-bold text-jazz-gold">${tix.id_pesanan}</span>
                            </div>
                            <h4 class="text-white text-sm font-semibold tracking-wide mb-1">${tix.nama_event}</h4>
                            <p class="text-jazz-muted text-xs font-light">${tix.hari}, ${formatDate(tix.tanggal)} &bull; ${tix.jam}</p>
                            <p class="text-jazz-muted text-xs font-light mt-1">${tix.jumlah_tiket} Orang (Kursi Penonton)</p>
                            <div class="border-t border-white/5 my-3 pt-3 flex justify-between items-center">
                                <span class="text-xs text-jazz-muted">Total Pembayaran:</span>
                                <span class="text-lg font-bold text-jazz-gold">${formattedTotal}</span>
                            </div>
                        </div>

                        <div class="space-y-4">
                            <p class="text-xs text-jazz-light leading-relaxed">
                                Pembayaran belum dikonfirmasi. Silakan scan QR DANA di bawah ini dan selesaikan transfer dana sebesar <strong>${formattedTotal}</strong>:
                            </p>
                            
                            <!-- DANA QR Code -->
                            <div class="relative w-40 h-40 mx-auto bg-white p-3 rounded-lg shadow-xl overflow-hidden border-2 border-sky-600/30">
                                <img src="assets/img/dana_qr_placeholder.png" alt="DANA QR Code" class="w-full h-full object-contain">
                            </div>

                            <div class="bg-jazz-gold/5 border border-jazz-gold/20 rounded-lg p-4 text-[10px] text-jazz-gold font-light leading-relaxed text-left max-w-sm mx-auto">
                                <strong>PENTING:</strong> Kirimkan screenshot bukti transfer pembayaran dan detail pemesanan ke WhatsApp Admin dengan tombol di bawah untuk aktivasi tiket LUNAS.
                            </div>

                            <a href="${waLink}" target="_blank" class="inline-flex w-full items-center justify-center gap-2 bg-gradient-to-r from-emerald-600 to-green-500 hover:from-emerald-500 hover:to-green-400 text-white font-bold uppercase tracking-wider text-xs px-5 py-3 rounded-lg shadow transition-all duration-300">
                                <svg class="w-4 h-4 fill-current" viewBox="0 0 24 24">
                                    <path d="M12.012 2c-5.506 0-9.989 4.478-9.99 9.984a9.96 9.96 0 0 0 1.333 4.982L2 22l5.163-1.355a9.95 9.95 0 0 0 4.845 1.258h.005c5.507 0 9.99-4.478 9.99-9.986 0-2.669-1.037-5.176-2.922-7.062A9.92 9.92 0 0 0 12.012 2zm5.792 14.283c-.319.893-1.578 1.639-2.171 1.716-.525.068-1.205.105-3.551-.827-2.996-1.192-4.912-4.229-5.061-4.428-.15-.199-1.201-1.597-1.201-3.047 0-1.45.76-2.164 1.032-2.454.273-.29.596-.363.796-.363.2 0 .399.002.573.01.181.009.424-.035.664.542.247.596.843 2.057.915 2.203.072.146.12.316.022.512-.097.195-.147.316-.293.487-.146.171-.307.382-.439.513-.146.146-.3.307-.129.6.171.293.76 1.252 1.63 2.029.932.83 1.716 1.087 1.959 1.21.244.122.385.102.528-.063.143-.166.611-.711.776-.955.165-.244.33-.205.556-.122.227.083 1.442.678 1.69.8.247.122.412.183.473.287.061.104.061.602-.258 1.495z"/>
                                </svg>
                                Kirim Bukti Bayar Ke WhatsApp
                            </a>
                        </div>
                    </div>
                `;
            }

            modalContent.innerHTML = contentHtml;

            // Tampilkan Modal
            modal.classList.remove('opacity-0', 'pointer-events-none');
            modalInner.classList.remove('scale-95');
            modalInner.classList.add('scale-100');
        }

        function closeTicketModal() {
            modal.classList.add('opacity-0', 'pointer-events-none');
            modalInner.classList.remove('scale-100');
            modalInner.classList.add('scale-95');
        }

        // Close modal when clicking outside content
        modal.addEventListener('click', function(e) {
            if (e.target === modal) {
                closeTicketModal();
            }
        });

        // Helper formatting date
        function formatDate(dateStr) {
            const date = new Date(dateStr);
            const months = ["Januari", "Februari", "Maret", "April", "Mei", "Juni", "Juli", "Agustus", "September", "Oktober", "November", "Desember"];
            return `${date.getDate()} ${months[date.getMonth()]} ${date.getFullYear()}`;
        }
    </script>
</body>
</html>
