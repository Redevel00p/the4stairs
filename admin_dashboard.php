<?php
/**
 * THE 4 STAIRS MUSIC HALL - ADMIN DASHBOARD (UPDATED)
 * --------------------------------------------------
 * Halaman administrasi utama untuk melihat pesanan tiket, mengubah status bayar,
 * pengelolaan jadwal pertunjukan (Open/Closed, title, notes), dan CRUD berita.
 */

session_start();

// Periksa apakah admin sudah login
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header("Location: admin_login");
    exit;
}

// Sertakan file koneksi database
include 'koneksi.php';

// Inisialisasi variabel filter pencarian (untuk tab reservasi)
$search_query = "";
if (isset($_GET['search'])) {
    $search_query = trim($_GET['search']);
}

// Menentukan tab mana yang sedang aktif setelah reload/proses
$active_tab = isset($_GET['tab']) ? $_GET['tab'] : 'reservations';

// --------------------------------------------------------------------------
// 1. QUERY STATISTIK RINGKAS
// --------------------------------------------------------------------------
$total_pesanan = 0;
$total_tiket_terjual = 0;
$total_tiket_lunas = 0;
$total_pendapatan = 0;
$harga_tiket = 75000; // Harga fiktif per tiket: Rp 75.000

if (isset($conn) && !$conn->connect_error) {
    // Total transaksi pemesanan
    $res = $conn->query("SELECT COUNT(*) as total FROM `pesanan`");
    $total_pesanan = $res ? $res->fetch_assoc()['total'] : 0;

    // Total tiket dipesan (lunas + pending)
    $res = $conn->query("SELECT SUM(`jumlah_tiket`) as total FROM `pesanan`");
    $total_tiket_terjual = $res ? intval($res->fetch_assoc()['total']) : 0;

    // Total tiket yang statusnya lunas (belum/sudah dipakai)
    $res = $conn->query("SELECT SUM(`jumlah_tiket`) as total FROM `pesanan` WHERE `status_pembayaran` IN ('Lunas - Belum Dipakai', 'Sudah Dipakai')");
    $total_tiket_lunas = $res ? intval($res->fetch_assoc()['total']) : 0;

    // Total Pendapatan fiktif terkonfirmasi
    $total_pendapatan = $total_tiket_lunas * $harga_tiket;
}

// --------------------------------------------------------------------------
// 2. QUERY JADWAL & SISA KUOTA KURSI
// --------------------------------------------------------------------------
$jadwal_status = [];
$jadwal_minggu_ini = [];
$jadwal_spesial_mendatang = [];
$jadwal_riwayat = [];
if (isset($conn) && !$conn->connect_error) {
    // Ambil jadwal 7 hari minggu ini (Senin - Minggu)
    $sql_minggu_ini = "SELECT * FROM `jadwal` 
                       WHERE `tanggal` BETWEEN DATE_ADD(CURDATE(), INTERVAL 0 - WEEKDAY(CURDATE()) DAY) 
                                           AND DATE_ADD(CURDATE(), INTERVAL 6 - WEEKDAY(CURDATE()) DAY)
                       ORDER BY `tanggal` ASC";
    $res_minggu_ini = $conn->query($sql_minggu_ini);
    if ($res_minggu_ini) {
        while ($row = $res_minggu_ini->fetch_assoc()) {
            $jadwal_minggu_ini[] = $row;
        }
    }
    
    // Ambil semua event spesial mendatang (is_special = 1 dan tanggal >= CURDATE())
    $sql_spesial = "SELECT * FROM `jadwal` WHERE `is_special` = 1 AND `tanggal` >= CURDATE() ORDER BY `tanggal` ASC";
    $res_spesial = $conn->query($sql_spesial);
    if ($res_spesial) {
        while ($row = $res_spesial->fetch_assoc()) {
            $jadwal_spesial_mendatang[] = $row;
        }
    }
    
    // Riwayat jadwal (masa lalu - sebelum Senin minggu ini)
    $sql_riwayat = "SELECT * FROM `jadwal` 
                    WHERE `tanggal` < DATE_ADD(CURDATE(), INTERVAL 0 - WEEKDAY(CURDATE()) DAY) 
                    ORDER BY `tanggal` DESC";
    $res_jadwal_riwayat = $conn->query($sql_riwayat);
    if ($res_jadwal_riwayat) {
        while ($row = $res_jadwal_riwayat->fetch_assoc()) {
            $jadwal_riwayat[] = $row;
        }
    }
    
    // Untuk display kondisi kuota di tab reservasi
    $jadwal_status = $jadwal_minggu_ini;
}

// --------------------------------------------------------------------------
// 3. QUERY DATA PEMESAN (overview: 10 booking terbaru)
// --------------------------------------------------------------------------
$pesanan_list = [];
if (isset($conn) && !$conn->connect_error) {
    $sql_pesanan = "SELECT p.*, j.hari, j.tanggal, j.jam FROM `pesanan` p 
                    JOIN `jadwal` j ON p.id_jadwal = j.id
                    ORDER BY p.waktu_pesan DESC LIMIT 10";
    $res_pesanan = $conn->query($sql_pesanan);
    if ($res_pesanan) {
        while ($row = $res_pesanan->fetch_assoc()) {
            $pesanan_list[] = $row;
        }
    }
}

// --------------------------------------------------------------------------
// 3b. QUERY DATA PEMESAN DENGAN FILTER DETIL (untuk tab Reservasi Tiket)
// --------------------------------------------------------------------------
$filter_status = isset($_GET['filter_status']) ? trim($_GET['filter_status']) : '';
$filter_day = isset($_GET['filter_day']) ? trim($_GET['filter_day']) : '';
$filter_date = isset($_GET['filter_date']) ? trim($_GET['filter_date']) : '';
$filter_start_date = isset($_GET['filter_start_date']) ? trim($_GET['filter_start_date']) : '';
$filter_end_date = isset($_GET['filter_end_date']) ? trim($_GET['filter_end_date']) : '';
$filter_search = isset($_GET['filter_search']) ? trim($_GET['filter_search']) : '';

$filtered_reservations = [];
if (isset($conn) && !$conn->connect_error && $db_selected) {
    $sql_filt = "SELECT p.*, j.hari, j.tanggal, j.jam, j.nama_event FROM `pesanan` p 
                 JOIN `jadwal` j ON p.id_jadwal = j.id WHERE 1=1";
    
    if ($filter_status !== '') {
        $sql_filt .= " AND p.status_pembayaran = '" . $conn->real_escape_string($filter_status) . "'";
    }
    if ($filter_day !== '') {
        $sql_filt .= " AND j.hari = '" . $conn->real_escape_string($filter_day) . "'";
    }
    if ($filter_date !== '') {
        $sql_filt .= " AND j.tanggal = '" . $conn->real_escape_string($filter_date) . "'";
    }
    if ($filter_start_date !== '' && $filter_end_date !== '') {
        $sql_filt .= " AND j.tanggal BETWEEN '" . $conn->real_escape_string($filter_start_date) . "' AND '" . $conn->real_escape_string($filter_end_date) . "'";
    } elseif ($filter_start_date !== '') {
        $sql_filt .= " AND j.tanggal >= '" . $conn->real_escape_string($filter_start_date) . "'";
    } elseif ($filter_end_date !== '') {
        $sql_filt .= " AND j.tanggal <= '" . $conn->real_escape_string($filter_end_date) . "'";
    }
    
    if ($filter_search !== '') {
        $search_esc = $conn->real_escape_string($filter_search);
        $sql_filt .= " AND (p.id_pesanan LIKE '%$search_esc%' 
                      OR p.nama LIKE '%$search_esc%' 
                      OR p.email LIKE '%$search_esc%' 
                      OR p.no_wa LIKE '%$search_esc%'
                      OR j.nama_event LIKE '%$search_esc%')";
    }
    
    $sql_filt .= " ORDER BY p.waktu_pesan DESC";
    $res_filt = $conn->query($sql_filt);
    if ($res_filt) {
        while ($row = $res_filt->fetch_assoc()) {
            $filtered_reservations[] = $row;
        }
    }
}

// --------------------------------------------------------------------------
// 3c. QUERY DATA GRAFIK (Chart.js: 14 hari terakhir)
// --------------------------------------------------------------------------
$chart_labels = [];
$chart_tickets = [];
$chart_earnings = [];

if (isset($conn) && !$conn->connect_error && $db_selected) {
    $sql_chart = "SELECT j.tanggal, SUM(p.jumlah_tiket) as total_tiket, SUM(p.jumlah_tiket * 75000) as total_pendapatan 
                  FROM pesanan p
                  JOIN jadwal j ON p.id_jadwal = j.id
                  WHERE p.status_pembayaran IN ('Lunas - Belum Dipakai', 'Sudah Dipakai') 
                    AND j.tanggal BETWEEN DATE_SUB(CURDATE(), INTERVAL 7 DAY) AND DATE_ADD(CURDATE(), INTERVAL 7 DAY)
                  GROUP BY j.tanggal
                  ORDER BY j.tanggal ASC";
    $res_chart = $conn->query($sql_chart);
    if ($res_chart) {
        while ($row = $res_chart->fetch_assoc()) {
            $chart_labels[] = date('d M', strtotime($row['tanggal']));
            $chart_tickets[] = intval($row['total_tiket']);
            $chart_earnings[] = intval($row['total_pendapatan']);
        }
    }
}

if (empty($chart_labels)) {
    // Fallback data jika belum ada transaksi lunas
    for ($i = 6; $i >= 0; $i--) {
        $chart_labels[] = date('d M', strtotime("-$i days"));
        $chart_tickets[] = 0;
        $chart_earnings[] = 0;
    }
}

// --------------------------------------------------------------------------
// 4. QUERY DATA BERITA
// --------------------------------------------------------------------------
$berita_list = [];
if (isset($conn) && !$conn->connect_error) {
    $res_berita = $conn->query("SELECT * FROM `berita` ORDER BY `tanggal_post` DESC");
    if ($res_berita) {
        while ($row = $res_berita->fetch_assoc()) {
            $berita_list[] = $row;
        }
    }
}

// Ambil pesan alert dari URL jika ada
$alert_msg = "";
$alert_class = "success";
if (isset($_GET['msg'])) {
    if ($_GET['msg'] === 'status_updated') {
        $alert_msg = "Status pembayaran tiket berhasil diubah!";
    } elseif ($_GET['msg'] === 'deleted') {
        $alert_msg = "Data pemesanan tiket berhasil dihapus. Kuota kursi telah dikembalikan.";
    } elseif ($_GET['msg'] === 'reset_success') {
        $alert_msg = "Seluruh data pemesanan telah dibersihkan. Kuota kursi kembali penuh!";
    } elseif ($_GET['msg'] === 'schedule_updated') {
        $alert_msg = "Jadwal pertunjukan berhasil diperbarui!";
    } elseif ($_GET['msg'] === 'special_event_added') {
        $alert_msg = "Event spesial mendatang berhasil ditambahkan!";
    } elseif ($_GET['msg'] === 'schedule_deleted') {
        $alert_msg = "Jadwal pertunjukan / Event spesial berhasil dihapus!";
    } elseif ($_GET['msg'] === 'news_added') {
        $alert_msg = "Pengumuman / Berita baru berhasil diterbitkan!";
    } elseif ($_GET['msg'] === 'news_deleted') {
        $alert_msg = "Pengumuman / Berita berhasil dihapus!";
    } elseif ($_GET['msg'] === 'komposisi_added') {
        $alert_msg = "Lagu komposisi baru berhasil ditambahkan!";
    } elseif ($_GET['msg'] === 'komposisi_deleted') {
        $alert_msg = "Lagu komposisi berhasil dihapus dari database dan server!";
    } elseif ($_GET['msg'] === 'upload_failed') {
        $alert_msg = "Gagal mengunggah file. Pastikan file valid.";
        $alert_class = "error";
    } elseif ($_GET['msg'] === 'invalid_file') {
        $alert_msg = "Format file tidak valid atau melebihi batas ukuran.";
        $alert_class = "error";
    } elseif ($_GET['msg'] === 'error') {
        $alert_msg = "Terjadi kesalahan sistem database.";
        $alert_class = "error";
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - The 4 Stairs Music Hall</title>
    <!-- HTML5 QR Code CDN -->
    <script src="https://unpkg.com/html5-qrcode"></script>
    <!-- Cropper.js CDN -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/cropperjs/1.6.1/cropper.min.css">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/cropperjs/1.6.1/cropper.min.js"></script>
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
                        retro: {
                            black: '#0c0a09',
                            card: '#161211',
                            input: '#1f1a18',
                            red: '#8b1e22',
                            redAccent: '#b91c1c',
                            brown: '#78350f',
                            brownAccent: '#a16207',
                            light: '#f5f5f4',
                            muted: '#a8a29e'
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
    <style>
        /* Custom scrollbar and animations */
        ::-webkit-scrollbar {
            width: 6px;
        }
        ::-webkit-scrollbar-track {
            background: #0c0a09;
        }
        ::-webkit-scrollbar-thumb {
            background: #2c2523;
            border-radius: 3px;
        }
        ::-webkit-scrollbar-thumb:hover {
            background: #8b1e22;
        }

        /* Tab content display */
        .tab-content {
            display: none;
        }
        .tab-content.active {
            display: block;
        }

        /* Modal Overlay animations */
        .modal-overlay {
            opacity: 0;
            pointer-events: none;
            transition: opacity 0.3s ease-in-out;
        }
        .modal-overlay.open {
            opacity: 1;
            pointer-events: auto;
        }

        /* Active tab button styling */
        .tab-btn {
            border-bottom: 2px solid transparent;
            transition: all 0.3s ease;
        }
        .tab-btn.active {
            color: #ffffff;
            border-bottom-color: #8b1e22;
        }
    </style>
</head>
<body class="bg-retro-black text-retro-light font-body flex flex-col min-h-screen pt-24">

    <!-- Premium Navigation Bar -->
    <?php include 'adminnavbar.php'; ?>

    <!-- Admin Panel Container -->
    <main class="flex-grow max-w-7xl w-full mx-auto px-6 py-10">
        
        <div class="flex flex-col md:flex-row md:items-center justify-between gap-6 mb-10 pb-6 border-b border-stone-800/60">
            <div>
                <h1 class="font-heading text-4xl font-bold text-white mb-2">Dashboard Panitia</h1>
                <p class="text-retro-muted text-sm">Selamat datang, <strong class="text-white font-semibold"><?php echo htmlspecialchars($_SESSION['admin_username']); ?></strong>. Pantau penjualan tiket dan kelola event.</p>
            </div>
            
            <!-- Reset Button for Testing -->
            <div>
                <a href="admin_process?action=reset" 
                   class="inline-flex px-4 py-2 bg-stone-900 border border-stone-850 hover:bg-stone-850 text-retro-muted hover:text-retro-red font-semibold text-xs tracking-wider uppercase rounded-lg transition-all duration-300" 
                   onclick="return confirm('APAKAH ANDA YAKIN? Semua data tiket pesanan di database akan dihapus permanen dan kuota akan di-reset ke 50!')">
                    Reset Semua Data (Testing)
                </a>
            </div>
        </div>

        <!-- Alert Notification -->
        <?php if (!empty($alert_msg)): ?>
            <div class="mb-10 p-4 border rounded-lg text-center text-sm font-medium transition-all duration-300 <?php echo ($alert_class === 'success') ? 'bg-emerald-950/20 border-emerald-900/60 text-emerald-400' : 'bg-retro-red/10 border-retro-red/30 text-retro-red'; ?>">
                <?php echo $alert_msg; ?>
            </div>
        <?php endif; ?>

        <!-- Tabs Selector -->
        <div class="flex border-b border-stone-800/80 mb-8 gap-2 overflow-x-auto">
            <button class="tab-btn py-3 px-6 text-retro-muted hover:text-white font-semibold text-xs uppercase tracking-widest <?php echo ($active_tab === 'reservations') ? 'active' : ''; ?>" data-tab="reservations">Beranda</button>
            <button class="tab-btn py-3 px-6 text-retro-muted hover:text-white font-semibold text-xs uppercase tracking-widest <?php echo ($active_tab === 'manage_reservations') ? 'active' : ''; ?>" data-tab="manage_reservations">Reservasi Tiket</button>
            <button class="tab-btn py-3 px-6 text-retro-muted hover:text-white font-semibold text-xs uppercase tracking-widest <?php echo ($active_tab === 'checkin') ? 'active' : ''; ?>" data-tab="checkin">Scan & Kehadiran</button>
            <button class="tab-btn py-3 px-6 text-retro-muted hover:text-white font-semibold text-xs uppercase tracking-widest <?php echo ($active_tab === 'schedules') ? 'active' : ''; ?>" data-tab="schedules">Kelola Jadwal</button>
            <button class="tab-btn py-3 px-6 text-retro-muted hover:text-white font-semibold text-xs uppercase tracking-widest <?php echo ($active_tab === 'history') ? 'active' : ''; ?>" data-tab="history">Riwayat Jadwal</button>
            <button class="tab-btn py-3 px-6 text-retro-muted hover:text-white font-semibold text-xs uppercase tracking-widest <?php echo ($active_tab === 'news') ? 'active' : ''; ?>" data-tab="news">Kelola Berita</button>
            <button class="tab-btn py-3 px-6 text-retro-muted hover:text-white font-semibold text-xs uppercase tracking-widest <?php echo ($active_tab === 'compositions') ? 'active' : ''; ?>" data-tab="compositions">Kelola Lagu</button>
        </div>

        <!-- ===================================================================
             TAB CONTENT: RESERVATIONS
             =================================================================== -->
        <div class="tab-content <?php echo ($active_tab === 'reservations') ? 'active' : ''; ?>" id="reservations">
            <!-- Grid 1: Ringkasan Statistik -->
            <section class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6 mb-10">
                <div class="bg-retro-card border border-stone-850/80 p-6 rounded-xl hover:border-retro-brown/40 transition-all duration-300">
                    <span class="block text-[10px] font-semibold text-retro-muted uppercase tracking-widest mb-2">Total Pesanan</span>
                    <span class="block font-heading text-4xl font-bold text-white mb-1"><?php echo $total_pesanan; ?></span>
                    <span class="block text-xs text-retro-muted">Transaksi Booking Masuk</span>
                </div>
                
                <div class="bg-retro-card border border-stone-850/80 p-6 rounded-xl hover:border-retro-brown/40 transition-all duration-300">
                    <span class="block text-[10px] font-semibold text-retro-muted uppercase tracking-widest mb-2">Kursi Terbooking</span>
                    <span class="block font-heading text-4xl font-bold text-white mb-1"><?php echo $total_tiket_terjual; ?></span>
                    <span class="block text-xs text-retro-muted">Total kursi direservasi</span>
                </div>
                
                <div class="bg-retro-card border border-stone-850/80 p-6 rounded-xl hover:border-retro-brown/40 transition-all duration-300">
                    <span class="block text-[10px] font-semibold text-retro-muted uppercase tracking-widest mb-2">Pembayaran Lunas</span>
                    <span class="block font-heading text-4xl font-bold text-emerald-450 mb-1" style="color: #2ecc71;"><?php echo $total_tiket_lunas; ?></span>
                    <span class="block text-xs text-retro-muted">Tiket aktif / siap masuk</span>
                </div>
                
                <div class="bg-retro-card border border-retro-red/35 p-6 rounded-xl shadow-lg shadow-retro-red/5 hover:border-retro-red/60 transition-all duration-300">
                    <span class="block text-[10px] font-semibold text-retro-red uppercase tracking-widest mb-2 font-bold">Total Pendapatan</span>
                    <span class="block font-heading text-4xl font-bold text-white mb-1">Rp <?php echo number_format($total_pendapatan, 0, ',', '.'); ?></span>
                    <span class="block text-xs text-retro-muted">Dari tiket LUNAS (Rp 75k/tiket)</span>
                </div>
            </section>

            <!-- Grid 2: Status Kuota Hari -->
            <section class="mb-12">
                <h3 class="font-heading text-2xl font-bold text-white mb-6 tracking-wide">Kondisi Kuota Harian (Maks 50)</h3>
                <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                    <?php foreach ($jadwal_status as $jad): 
                        $sisa_kuota = $jad['kuota'] - $jad['terjual'];
                        $persen_sisa = ($sisa_kuota / $jad['kuota']) * 100;
                        
                        $bar_color = "bg-emerald-600"; // Hijau
                        if ($sisa_kuota <= 0) {
                            $bar_color = "bg-retro-red"; // Merah
                        } elseif ($sisa_kuota <= 15) {
                            $bar_color = "bg-retro-brown"; // Coklat / Oranye
                        }
                    ?>
                        <div class="bg-retro-card border border-stone-850 p-5 rounded-xl">
                            <div class="flex justify-between items-center mb-3">
                                <strong class="text-white text-base font-semibold"><?php echo $jad['hari']; ?> (<?php echo date('d M', strtotime($jad['tanggal'])); ?>)</strong>
                                <span class="text-xs text-retro-muted font-medium"><?php echo $sisa_kuota; ?> / 50 Kursi Tersisa</span>
                            </div>
                            <div class="w-full h-2 bg-stone-900 rounded-full overflow-hidden">
                                <div class="h-full rounded-full transition-all duration-500 <?php echo $bar_color; ?>" style="width: <?php echo $persen_sisa; ?>%;"></div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </section>

            <!-- Grid 3: Tabel Data Booking Terbaru -->
            <section>
                <div class="flex flex-col md:flex-row md:items-center justify-between gap-4 mb-6">
                    <div>
                        <h3 class="font-heading text-2xl font-bold text-white tracking-wide">10 Transaksi Reservasi Terbaru</h3>
                        <p class="text-retro-muted text-xs mt-1">Daftar transaksi pesanan tiket masuk terbaru. Untuk filter lengkap, silakan buka tab Reservasi Tiket.</p>
                    </div>
                    <div>
                        <button onclick="document.querySelector('[data-tab=manage_reservations]').click()" class="bg-retro-brown hover:bg-orange-850 text-white font-bold uppercase tracking-wider text-xs px-5 py-2.5 rounded-lg transition-all duration-300 cursor-pointer">
                            Kelola Semua Reservasi & Filter &rarr;
                        </button>
                    </div>
                </div>

                <!-- Responsive Table Wrapper -->
                <div class="overflow-x-auto bg-retro-card border border-stone-800/80 rounded-xl shadow-xl">
                    <table class="w-full text-left border-collapse text-sm">
                        <thead>
                            <tr class="bg-stone-950/40 border-b border-stone-800 text-retro-red font-semibold text-xs tracking-wider uppercase">
                                <th class="px-6 py-4">ID Tiket</th>
                                <th class="px-6 py-4">Data Pemesan</th>
                                <th class="px-6 py-4">Jadwal Acara</th>
                                <th class="px-6 py-4">Jumlah</th>
                                <th class="px-6 py-4">Status Bayar</th>
                                <th class="px-6 py-4 text-right">Aksi Operasi</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-stone-850/40">
                            <?php if (empty($pesanan_list)): ?>
                                <tr>
                                    <td colspan="6" class="text-center text-retro-muted py-10 italic">
                                        Tidak ada data reservasi tiket terbaru.
                                    </td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($pesanan_list as $pesanan): 
                                    $tgl_show_fmt = date('d M Y', strtotime($pesanan['tanggal']));
                                    $status_lower = strtolower($pesanan['status_pembayaran']);
                                    
                                    // Setup link eksternal WA
                                    $clean_wa = preg_replace('/[^0-9]/', '', $pesanan['no_wa']);
                                    if (substr($clean_wa, 0, 2) === '08') {
                                        $clean_wa = '62' . substr($clean_wa, 1);
                                    }
                                    $wa_chat_link = "https://wa.me/" . $clean_wa;
                                ?>
                                    <tr class="hover:bg-white/[0.01] transition-colors">
                                        <td class="px-6 py-4 font-heading font-semibold text-retro-brown text-base tracking-wide"><?php echo $pesanan['id_pesanan']; ?></td>
                                        <td class="px-6 py-4">
                                            <div class="font-semibold text-white"><?php echo htmlspecialchars($pesanan['nama']); ?></div>
                                            <div class="text-xs text-retro-muted mt-0.5"><?php echo htmlspecialchars($pesanan['email']); ?></div>
                                            <div class="text-xs mt-1">
                                                <a href="<?php echo $wa_chat_link; ?>" target="_blank" class="text-emerald-500 hover:text-emerald-400 font-medium inline-flex items-center gap-1">
                                                    <svg width="12" height="12" viewBox="0 0 24 24" fill="currentColor">
                                                        <path d="M12.012 2c-5.506 0-9.989 4.478-9.99 9.984a9.96 9.96 0 0 0 1.333 4.982L2 22l5.163-1.355a9.95 9.95 0 0 0 4.845 1.258h.005c5.507 0 9.99-4.478 9.99-9.986 0-2.669-1.037-5.176-2.922-7.062A9.92 9.92 0 0 0 12.012 2zm5.792 14.283c-.319.893-1.578 1.639-2.171 1.716-.525.068-1.205.105-3.551-.827-2.996-1.192-4.912-4.229-5.061-4.428-.15-.199-1.201-1.597-1.201-3.047 0-1.45.76-2.164 1.032-2.454.273-.29.596-.363.796-.363.2 0 .399.002.573.01.181.009.424-.035.664.542.247.596.843 2.057.915 2.203.072.146.12.316.022.512-.097.195-.147.316-.293.487-.146.171-.307.382-.439.513-.146.146-.3.307-.129.6.171.293.76 1.252 1.63 2.029.932.83 1.716 1.087 1.959 1.21.244.122.385.102.528-.063.143-.166.611-.711.776-.955.165-.244.33-.205.556-.122.227.083 1.442.678 1.69.8.247.122.412.183.473.287.061.104.061.602-.258 1.495z"/>
                                                    </svg>
                                                    <?php echo htmlspecialchars($pesanan['no_wa']); ?>
                                                </a>
                                            </div>
                                        </td>
                                        <td class="px-6 py-4">
                                            <div class="font-semibold text-white"><?php echo $pesanan['hari']; ?></div>
                                            <div class="text-xs text-retro-muted mt-0.5"><?php echo $tgl_show_fmt; ?> &bull; <?php echo $pesanan['jam']; ?></div>
                                        </td>
                                        <td class="px-6 py-4 font-semibold text-white"><?php echo $pesanan['jumlah_tiket']; ?> Kursi</td>
                                        <td class="px-6 py-4">
                                            <?php if ($status_lower === 'lunas - belum dipakai'): ?>
                                                <span class="inline-flex items-center px-2.5 py-1 rounded-full text-[10px] font-semibold uppercase tracking-wider bg-emerald-950/40 text-emerald-450 border border-emerald-900/40" style="color: #2ecc71;">Lunas</span>
                                            <?php elseif ($status_lower === 'sudah dipakai'): ?>
                                                <span class="inline-flex items-center px-2.5 py-1 rounded-full text-[10px] font-semibold uppercase tracking-wider bg-blue-950/40 text-blue-400 border border-blue-900/40">Telah Terpakai</span>
                                            <?php else: ?>
                                                <span class="inline-flex items-center px-2.5 py-1 rounded-full text-[10px] font-semibold uppercase tracking-wider bg-amber-950/40 text-amber-400 border border-amber-900/40">Pending</span>
                                            <?php endif; ?>
                                        </td>
                                        <td class="px-6 py-4 text-right">
                                            <div class="inline-flex gap-2">
                                                <?php if ($pesanan['status_pembayaran'] === 'Pending'): ?>
                                                    <a href="admin_process?action=status&id=<?php echo $pesanan['id_pesanan']; ?>&to=Lunas+-+Belum+Dipakai&tab=reservations" 
                                                       class="px-2.5 py-1.5 border border-emerald-700/50 hover:bg-emerald-750 text-emerald-400 hover:text-white rounded-lg text-[10px] font-semibold uppercase tracking-wider transition-colors duration-300">
                                                        Set Lunas
                                                    </a>
                                                <?php elseif ($pesanan['status_pembayaran'] === 'Lunas - Belum Dipakai'): ?>
                                                    <a href="admin_process?action=status&id=<?php echo $pesanan['id_pesanan']; ?>&to=Pending&tab=reservations" 
                                                       class="px-2.5 py-1.5 border border-amber-700/50 hover:bg-amber-750 text-amber-500 hover:text-white rounded-lg text-[10px] font-semibold uppercase tracking-wider transition-colors duration-300">
                                                        Set Pending
                                                    </a>
                                                    <a href="admin_process?action=status&id=<?php echo $pesanan['id_pesanan']; ?>&to=Sudah+Dipakai&tab=reservations" 
                                                       class="px-2.5 py-1.5 border border-blue-700/50 hover:bg-blue-750 text-blue-400 hover:text-white rounded-lg text-[10px] font-semibold uppercase tracking-wider transition-colors duration-300">
                                                        Set Terpakai
                                                    </a>
                                                <?php elseif ($pesanan['status_pembayaran'] === 'Sudah Dipakai'): ?>
                                                    <a href="admin_process?action=status&id=<?php echo $pesanan['id_pesanan']; ?>&to=Lunas+-+Belum+Dipakai&tab=reservations" 
                                                       class="px-2.5 py-1.5 border border-emerald-700/50 hover:bg-emerald-750 text-emerald-400 hover:text-white rounded-lg text-[10px] font-semibold uppercase tracking-wider transition-colors duration-300">
                                                        Belum Pakai
                                                    </a>
                                                    <a href="admin_process?action=status&id=<?php echo $pesanan['id_pesanan']; ?>&to=Pending&tab=reservations" 
                                                       class="px-2.5 py-1.5 border border-amber-700/50 hover:bg-amber-750 text-amber-500 hover:text-white rounded-lg text-[10px] font-semibold uppercase tracking-wider transition-colors duration-300">
                                                        Set Pending
                                                    </a>
                                                <?php endif; ?>

                                                <a href="admin_process?action=delete&id=<?php echo $pesanan['id_pesanan']; ?>&tab=reservations" 
                                                   class="px-3 py-1.5 border border-retro-red/50 hover:bg-retro-red text-retro-red hover:text-white rounded-lg text-xs font-semibold uppercase tracking-wider transition-colors duration-300"
                                                   onclick="return confirm('Hapus pesanan tiket atas nama <?php echo htmlspecialchars($pesanan['nama']); ?>? Kuota kursi akan dikembalikan.')">
                                                    Hapus
                                                </a>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </section>
        </div>

        <!-- ===================================================================
             TAB CONTENT: MANAGE RESERVATIONS (RESERVASI TIKET)
             =================================================================== -->
        <div class="tab-content <?php echo ($active_tab === 'manage_reservations') ? 'active' : ''; ?>" id="manage_reservations">
            
            <!-- Filters Card -->
            <div class="bg-retro-card border border-stone-800 rounded-xl p-6 mb-8 shadow-xl">
                <h4 class="font-heading text-xl font-bold text-white mb-4 tracking-wide text-retro-brownAccent">Penyaringan Reservasi Tiket</h4>
                <form action="admin_dashboard" method="GET" class="space-y-4">
                    <input type="hidden" name="tab" value="manage_reservations">
                    
                    <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                        <!-- Search Text -->
                        <div class="md:col-span-2 space-y-1.5">
                            <label for="filter_search" class="block text-[10px] font-bold uppercase tracking-wider text-retro-muted">Cari Kata Kunci</label>
                            <input type="text" name="filter_search" id="filter_search" value="<?php echo htmlspecialchars($filter_search); ?>" placeholder="Nama, ID Tiket, Email, Event..." 
                                   class="w-full bg-retro-input border border-stone-855 rounded-lg px-4 py-2.5 text-xs text-white placeholder-stone-600 focus:outline-none focus:border-retro-red transition-all">
                        </div>
                        
                        <!-- Status Filter -->
                        <div class="space-y-1.5">
                            <label for="filter_status" class="block text-[10px] font-bold uppercase tracking-wider text-retro-muted">Status Pembayaran</label>
                            <select name="filter_status" id="filter_status" class="w-full bg-retro-input border border-stone-855 rounded-lg px-4 py-2.5 text-xs text-white focus:outline-none focus:border-retro-red transition-all cursor-pointer">
                                <option value="">-- Semua Status --</option>
                                <option value="Lunas - Belum Dipakai" <?php echo ($filter_status === 'Lunas - Belum Dipakai') ? 'selected' : ''; ?>>Lunas - Belum Dipakai</option>
                                <option value="Sudah Dipakai" <?php echo ($filter_status === 'Sudah Dipakai') ? 'selected' : ''; ?>>Sudah Dipakai</option>
                                <option value="Pending" <?php echo ($filter_status === 'Pending') ? 'selected' : ''; ?>>Pending</option>
                            </select>
                        </div>
                        
                        <!-- Day Filter -->
                        <div class="space-y-1.5">
                            <label for="filter_day" class="block text-[10px] font-bold uppercase tracking-wider text-retro-muted">Hari Pertunjukan</label>
                            <select name="filter_day" id="filter_day" class="w-full bg-retro-input border border-stone-855 rounded-lg px-4 py-2.5 text-xs text-white focus:outline-none focus:border-retro-red transition-all cursor-pointer">
                                <option value="">-- Semua Hari --</option>
                                <?php 
                                $days_list = ['Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat', 'Sabtu', 'Minggu'];
                                foreach ($days_list as $d) {
                                    $sel = ($filter_day === $d) ? 'selected' : '';
                                    echo "<option value=\"$d\" $sel>$d</option>";
                                }
                                ?>
                            </select>
                        </div>
                    </div>
                    
                    <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                        <!-- Specific Date -->
                        <div class="space-y-1.5">
                            <label for="filter_date" class="block text-[10px] font-bold uppercase tracking-wider text-retro-muted">Tanggal Spesifik</label>
                            <input type="date" name="filter_date" id="filter_date" value="<?php echo htmlspecialchars($filter_date); ?>" 
                                   class="w-full bg-retro-input border border-stone-855 rounded-lg px-4 py-2 text-xs text-white focus:outline-none focus:border-retro-red transition-all">
                        </div>
                        
                        <!-- Start Date -->
                        <div class="space-y-1.5">
                            <label for="filter_start_date" class="block text-[10px] font-bold uppercase tracking-wider text-retro-muted">Tanggal Awal Rentang</label>
                            <input type="date" name="filter_start_date" id="filter_start_date" value="<?php echo htmlspecialchars($filter_start_date); ?>" 
                                   class="w-full bg-retro-input border border-stone-855 rounded-lg px-4 py-2 text-xs text-white focus:outline-none focus:border-retro-red transition-all">
                        </div>
                        
                        <!-- End Date -->
                        <div class="space-y-1.5">
                            <label for="filter_end_date" class="block text-[10px] font-bold uppercase tracking-wider text-retro-muted">Tanggal Akhir Rentang</label>
                            <input type="date" name="filter_end_date" id="filter_end_date" value="<?php echo htmlspecialchars($filter_end_date); ?>" 
                                   class="w-full bg-retro-input border border-stone-855 rounded-lg px-4 py-2 text-xs text-white focus:outline-none focus:border-retro-red transition-all">
                        </div>
                        
                        <!-- Filter Action Buttons -->
                        <div class="flex items-end gap-2">
                            <button type="submit" class="flex-grow bg-retro-brown hover:bg-orange-850 text-white font-bold uppercase tracking-wider text-xs px-4 py-2.5 rounded-lg transition-all duration-300 h-[38px] flex items-center justify-center cursor-pointer">
                                Cari & Filter
                            </button>
                            <a href="admin_dashboard?tab=manage_reservations" class="bg-stone-900 border border-stone-800 text-retro-light hover:bg-stone-850 font-bold uppercase tracking-wider text-xs px-4 py-2.5 rounded-lg transition-all duration-300 h-[38px] flex items-center justify-center">
                                Reset
                            </a>
                        </div>
                    </div>
                </form>
            </div>

            <!-- Analytics and Export Row -->
            <div class="grid grid-cols-1 lg:grid-cols-12 gap-8 mb-8">
                <!-- Analytics Chart -->
                <div class="lg:col-span-7 bg-retro-card border border-stone-800 rounded-xl p-6 shadow-xl flex flex-col justify-between">
                    <div>
                        <h4 class="font-heading text-xl font-bold text-white mb-2 tracking-wide text-retro-brownAccent">Grafik Tren Reservasi Lunas</h4>
                        <p class="text-retro-muted text-xs mb-4">Menampilkan perbandingan kuantitas tiket terjual (Lunas) dan omset nominal pendapatan harian.</p>
                    </div>
                    <div class="relative w-full h-[230px]">
                        <canvas id="reservationsChart"></canvas>
                    </div>
                </div>

                <!-- Export Drawer Card -->
                <div class="lg:col-span-5 bg-retro-card border border-stone-800 rounded-xl p-6 shadow-xl flex flex-col justify-between">
                    <div>
                        <h4 class="font-heading text-xl font-bold text-white mb-2 tracking-wide text-retro-brownAccent">Pusat Unduhan & Ekspor Data</h4>
                        <p class="text-retro-muted text-xs mb-4">Ekspor data Anda ke file Excel (CSV) yang kompatibel, atau print ke layout PDF yang rapi.</p>
                    </div>
                    
                    <form action="export" method="GET" target="_blank" class="space-y-4">
                        <!-- Range Tanggal Ekspor -->
                        <div class="grid grid-cols-2 gap-3">
                            <div class="space-y-1.5">
                                <label for="export_start" class="block text-[10px] font-semibold uppercase tracking-wider text-retro-muted">Dari Tanggal</label>
                                <input type="date" name="start_date" id="export_start" value="<?php echo htmlspecialchars($filter_start_date); ?>" 
                                       class="w-full bg-retro-input border border-stone-850 rounded-lg px-3 py-2 text-xs text-white focus:outline-none focus:border-retro-red">
                            </div>
                            <div class="space-y-1.5">
                                <label for="export_end" class="block text-[10px] font-semibold uppercase tracking-wider text-retro-muted">Sampai Tanggal</label>
                                <input type="date" name="end_date" id="export_end" value="<?php echo htmlspecialchars($filter_end_date); ?>" 
                                       class="w-full bg-retro-input border border-stone-850 rounded-lg px-3 py-2 text-xs text-white focus:outline-none focus:border-retro-red">
                            </div>
                        </div>
                        
                        <!-- Jenis Laporan -->
                        <div class="space-y-1.5">
                            <label for="export_type" class="block text-[10px] font-semibold uppercase tracking-wider text-retro-muted">Jenis Data Laporan</label>
                            <select name="type" id="export_type" required class="w-full bg-retro-input border border-stone-850 rounded-lg px-3 py-2 text-xs text-white focus:outline-none focus:border-retro-red cursor-pointer">
                                <option value="buyers">Data Pembeli Tiket (Daftar Reservasi)</option>
                                <option value="finance">Laporan Keuangan (Pembayaran Lunas)</option>
                                <option value="events">Daftar Jadwal & Keterisian Event</option>
                            </select>
                        </div>
                        
                        <!-- Submit Buttons -->
                        <div class="grid grid-cols-2 gap-3 pt-2">
                            <button type="submit" name="format" value="csv" class="w-full py-2.5 bg-emerald-600 hover:bg-emerald-550 text-white font-bold uppercase tracking-wider text-[10px] rounded-lg transition-all duration-300 flex items-center justify-center gap-1.5 cursor-pointer">
                                <svg class="w-3.5 h-3.5 fill-current" viewBox="0 0 24 24">
                                    <path d="M14 2H6c-1.1 0-1.99.9-1.99 2L4 20c0 1.1.89 2 1.99 2H18c1.1 0 2-.9 2-2V8l-6-6zm2 16H8v-2h8v2zm0-4H8v-2h8v2zm-3-5V3.5L18.5 9H13z"/>
                                </svg>
                                Ekspor Excel
                            </button>
                            <button type="submit" name="format" value="pdf" class="w-full py-2.5 bg-retro-red hover:bg-retro-redAccent text-white font-bold uppercase tracking-wider text-[10px] rounded-lg transition-all duration-300 flex items-center justify-center gap-1.5 cursor-pointer">
                                <svg class="w-3.5 h-3.5 fill-current" viewBox="0 0 24 24">
                                    <path d="M19 8H5c-1.66 0-3 1.34-3 3v6h4v4h12v-4h4v-6c0-1.66-1.34-3-3-3zm-3 11H8v-5h8v5zm3-7c-.55 0-1-.45-1-1s.45-1 1-1 1 .45 1 1-.45 1-1 1zm-1-9H6v4h12V3z"/>
                                </svg>
                                Cetak PDF
                            </button>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Filter Results Table -->
            <section>
                <div class="mb-6">
                    <h3 class="font-heading text-2xl font-bold text-white tracking-wide">Hasil Penyaringan Reservasi Tiket</h3>
                    <p class="text-retro-muted text-xs mt-1">Ditemukan <strong><?php echo count($filtered_reservations); ?></strong> data reservasi tiket yang cocok dengan filter aktif.</p>
                </div>

                <!-- Responsive Table Wrapper -->
                <div class="overflow-x-auto bg-retro-card border border-stone-800/80 rounded-xl shadow-xl">
                    <table class="w-full text-left border-collapse text-sm">
                        <thead>
                            <tr class="bg-stone-950/40 border-b border-stone-800 text-retro-red font-semibold text-xs tracking-wider uppercase">
                                <th class="px-6 py-4">ID Tiket</th>
                                <th class="px-6 py-4">Data Pemesan</th>
                                <th class="px-6 py-4">Jadwal Acara</th>
                                <th class="px-6 py-4">Jumlah</th>
                                <th class="px-6 py-4">Status Bayar</th>
                                <th class="px-6 py-4 text-right">Aksi Operasi</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-stone-850/40">
                            <?php if (empty($filtered_reservations)): ?>
                                <tr>
                                    <td colspan="6" class="text-center text-retro-muted py-10 italic">
                                        Tidak ada data reservasi tiket yang cocok dengan penyaringan aktif.
                                    </td>
                                </tr>
                            <?php else: ?>
                                <?php 
                                // Build query string to append back to status/delete redirects
                                $filt_query = "&tab=manage_reservations";
                                if (!empty($filter_status)) $filt_query .= "&filter_status=" . urlencode($filter_status);
                                if (!empty($filter_day)) $filt_query .= "&filter_day=" . urlencode($filter_day);
                                if (!empty($filter_date)) $filt_query .= "&filter_date=" . urlencode($filter_date);
                                if (!empty($filter_start_date)) $filt_query .= "&filter_start_date=" . urlencode($filter_start_date);
                                if (!empty($filter_end_date)) $filt_query .= "&filter_end_date=" . urlencode($filter_end_date);
                                if (!empty($filter_search)) $filt_query .= "&filter_search=" . urlencode($filter_search);
                                
                                foreach ($filtered_reservations as $pesanan): 
                                    $tgl_show_fmt = date('d M Y', strtotime($pesanan['tanggal']));
                                    $status_lower = strtolower($pesanan['status_pembayaran']);
                                    
                                    // Setup link eksternal WA
                                    $clean_wa = preg_replace('/[^0-9]/', '', $pesanan['no_wa']);
                                    if (substr($clean_wa, 0, 2) === '08') {
                                        $clean_wa = '62' . substr($clean_wa, 1);
                                    }
                                    $wa_chat_link = "https://wa.me/" . $clean_wa;
                                ?>
                                    <tr class="hover:bg-white/[0.01] transition-colors">
                                        <td class="px-6 py-4 font-heading font-semibold text-retro-brown text-base tracking-wide"><?php echo $pesanan['id_pesanan']; ?></td>
                                        <td class="px-6 py-4">
                                            <div class="font-semibold text-white"><?php echo htmlspecialchars($pesanan['nama']); ?></div>
                                            <div class="text-xs text-retro-muted mt-0.5"><?php echo htmlspecialchars($pesanan['email']); ?></div>
                                            <div class="text-xs mt-1">
                                                <a href="<?php echo $wa_chat_link; ?>" target="_blank" class="text-emerald-500 hover:text-emerald-400 font-medium inline-flex items-center gap-1">
                                                    <svg width="12" height="12" viewBox="0 0 24 24" fill="currentColor">
                                                        <path d="M12.012 2c-5.506 0-9.989 4.478-9.99 9.984a9.96 9.96 0 0 0 1.333 4.982L2 22l5.163-1.355a9.95 9.95 0 0 0 4.845 1.258h.005c5.507 0 9.99-4.478 9.99-9.986 0-2.669-1.037-5.176-2.922-7.062A9.92 9.92 0 0 0 12.012 2zm5.792 14.283c-.319.893-1.578 1.639-2.171 1.716-.525.068-1.205.105-3.551-.827-2.996-1.192-4.912-4.229-5.061-4.428-.15-.199-1.201-1.597-1.201-3.047 0-1.45.76-2.164 1.032-2.454.273-.29.596-.363.796-.363.2 0 .399.002.573.01.181.009.424-.035.664.542.247.596.843 2.057.915 2.203.072.146.12.316.022.512-.097.195-.147.316-.293.487-.146.171-.307.382-.439.513-.146.146-.3.307-.129.6.171.293.76 1.252 1.63 2.029.932.83 1.716 1.087 1.959 1.21.244.122.385.102.528-.063.143-.166.611-.711.776-.955.165-.244.33-.205.556-.122.227.083 1.442.678 1.69.8.247.122.412.183.473.287.061.104.061.602-.258 1.495z"/>
                                                    </svg>
                                                    <?php echo htmlspecialchars($pesanan['no_wa']); ?>
                                                </a>
                                            </div>
                                        </td>
                                        <td class="px-6 py-4">
                                            <div class="font-semibold text-white"><?php echo $pesanan['hari']; ?></div>
                                            <div class="text-[11px] text-retro-muted mt-0.5"><?php echo $tgl_show_fmt; ?> &bull; <?php echo $pesanan['jam']; ?></div>
                                            <div class="text-[11px] text-retro-brownAccent mt-0.5 italic max-w-[150px] truncate"><?php echo htmlspecialchars($pesanan['nama_event']); ?></div>
                                        </td>
                                        <td class="px-6 py-4 font-semibold text-white"><?php echo $pesanan['jumlah_tiket']; ?> Kursi</td>
                                        <td class="px-6 py-4">
                                            <?php if ($status_lower === 'lunas - belum dipakai'): ?>
                                                <span class="inline-flex items-center px-2.5 py-1 rounded-full text-[10px] font-semibold uppercase tracking-wider bg-emerald-950/40 text-emerald-450 border border-emerald-900/40" style="color: #2ecc71;">Lunas</span>
                                            <?php elseif ($status_lower === 'sudah dipakai'): ?>
                                                <span class="inline-flex items-center px-2.5 py-1 rounded-full text-[10px] font-semibold uppercase tracking-wider bg-blue-950/40 text-blue-400 border border-blue-900/40">Telah Terpakai</span>
                                            <?php else: ?>
                                                <span class="inline-flex items-center px-2.5 py-1 rounded-full text-[10px] font-semibold uppercase tracking-wider bg-amber-950/40 text-amber-400 border border-amber-900/40">Pending</span>
                                            <?php endif; ?>
                                        </td>
                                        <td class="px-6 py-4 text-right">
                                            <div class="inline-flex gap-2">
                                                <?php if ($pesanan['status_pembayaran'] === 'Pending'): ?>
                                                    <a href="admin_process?action=status&id=<?php echo $pesanan['id_pesanan']; ?>&to=Lunas+-+Belum+Dipakai<?php echo $filt_query; ?>" 
                                                       class="px-2.5 py-1.5 border border-emerald-700/50 hover:bg-emerald-750 text-emerald-400 hover:text-white rounded-lg text-[10px] font-semibold uppercase tracking-wider transition-colors duration-300">
                                                        Set Lunas
                                                    </a>
                                                <?php elseif ($pesanan['status_pembayaran'] === 'Lunas - Belum Dipakai'): ?>
                                                    <a href="admin_process?action=status&id=<?php echo $pesanan['id_pesanan']; ?>&to=Pending<?php echo $filt_query; ?>" 
                                                       class="px-2.5 py-1.5 border border-amber-700/50 hover:bg-amber-750 text-amber-500 hover:text-white rounded-lg text-[10px] font-semibold uppercase tracking-wider transition-colors duration-300">
                                                        Set Pending
                                                    </a>
                                                    <a href="admin_process?action=status&id=<?php echo $pesanan['id_pesanan']; ?>&to=Sudah+Dipakai<?php echo $filt_query; ?>" 
                                                       class="px-2.5 py-1.5 border border-blue-700/50 hover:bg-blue-750 text-blue-400 hover:text-white rounded-lg text-[10px] font-semibold uppercase tracking-wider transition-colors duration-300">
                                                        Set Terpakai
                                                    </a>
                                                <?php elseif ($pesanan['status_pembayaran'] === 'Sudah Dipakai'): ?>
                                                    <a href="admin_process?action=status&id=<?php echo $pesanan['id_pesanan']; ?>&to=Lunas+-+Belum+Dipakai<?php echo $filt_query; ?>" 
                                                       class="px-2.5 py-1.5 border border-emerald-700/50 hover:bg-emerald-750 text-emerald-400 hover:text-white rounded-lg text-[10px] font-semibold uppercase tracking-wider transition-colors duration-300">
                                                        Belum Pakai
                                                    </a>
                                                    <a href="admin_process?action=status&id=<?php echo $pesanan['id_pesanan']; ?>&to=Pending<?php echo $filt_query; ?>" 
                                                       class="px-2.5 py-1.5 border border-amber-700/50 hover:bg-amber-750 text-amber-500 hover:text-white rounded-lg text-[10px] font-semibold uppercase tracking-wider transition-colors duration-300">
                                                        Set Pending
                                                    </a>
                                                <?php endif; ?>

                                                <a href="admin_process?action=delete&id=<?php echo $pesanan['id_pesanan']; ?><?php echo $filt_query; ?>" 
                                                   class="px-3 py-1.5 border border-retro-red/50 hover:bg-retro-red text-retro-red hover:text-white rounded-lg text-xs font-semibold uppercase tracking-wider transition-colors duration-300"
                                                   onclick="return confirm('Hapus pesanan tiket atas nama <?php echo htmlspecialchars($pesanan['nama']); ?>? Kuota kursi akan dikembalikan.')">
                                                    Hapus
                                                </a>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </section>
        </div>

        <!-- ===================================================================
             TAB CONTENT: SCAN & KEHADIRAN (CHECKIN)
             =================================================================== -->
        <div class="tab-content <?php echo ($active_tab === 'checkin') ? 'active' : ''; ?>" id="checkin">
            <div class="mb-6">
                <h3 class="font-heading text-2xl font-bold text-white tracking-wide">Pencatatan Kehadiran (Check-In)</h3>
                <p class="text-retro-muted text-sm mt-1">Gunakan scanner QR Code webcam atau ketik kode tiket secara manual untuk memverifikasi kehadiran.</p>
            </div>
            
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
                <!-- SCANNER AREA -->
                <div class="bg-retro-card border border-stone-800/80 rounded-xl p-6 shadow-xl flex flex-col items-center justify-center">
                    <h4 class="text-white font-semibold text-sm mb-4 uppercase tracking-wider text-retro-red">Scanner Kamera Webcam</h4>
                    
                    <div id="qr-error-msg" class="hidden mb-4 p-3 bg-rose-950/20 border border-rose-900/30 text-rose-400 text-xs rounded-lg w-full text-center"></div>
                    
                    <div class="relative w-full max-w-sm aspect-square bg-stone-950 rounded-xl border border-stone-850 overflow-hidden flex items-center justify-center">
                        <!-- Camera frame decorator -->
                        <div class="absolute inset-4 border-2 border-dashed border-stone-850 pointer-events-none rounded-lg z-0"></div>
                        <div class="absolute top-2 left-2 w-4 h-4 border-t-2 border-l-2 border-retro-red pointer-events-none z-10"></div>
                        <div class="absolute top-2 right-2 w-4 h-4 border-t-2 border-r-2 border-retro-red pointer-events-none z-10"></div>
                        <div class="absolute bottom-2 left-2 w-4 h-4 border-b-2 border-l-2 border-retro-red pointer-events-none z-10"></div>
                        <div class="absolute bottom-2 right-2 w-4 h-4 border-b-2 border-r-2 border-retro-red pointer-events-none z-10"></div>
                        
                        <!-- Scanner element -->
                        <div id="qr-reader" class="w-full h-full z-10 bg-transparent"></div>
                    </div>
                    
                    <button type="button" id="toggle-scan-btn" onclick="handleScanBtnClick()"
                            class="mt-4 w-full max-w-xs bg-retro-red hover:bg-retro-redAccent text-white font-bold uppercase tracking-wider text-xs py-3 rounded-lg shadow-lg hover:shadow-retro-red/10 transition-all duration-300 flex items-center justify-center gap-2 cursor-pointer">
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v1m6 11h2m-6 0h-2v4m0-11v3m0 0h.01M12 12h.01M16 12h.01M8 12h.01" />
                        </svg>
                        <span id="scan-btn-text">Mulai Scan QR</span>
                    </button>
                    
                    <p class="text-[10px] text-retro-muted mt-4 text-center italic">*Arahkan QR Code tiket pada kamera setelah tombol scan diaktifkan.</p>
                </div>
                
                <!-- MANUAL CHECK-IN AREA -->
                <div class="bg-retro-card border border-stone-800/80 rounded-xl p-6 shadow-xl flex flex-col justify-start">
                    <h4 class="text-white font-semibold text-sm mb-6 uppercase tracking-wider text-retro-red">Input Kode Tiket Manual</h4>
                    
                    <form id="manual-checkin-form" onsubmit="event.preventDefault(); submitManualCheckin();" class="space-y-4">
                        <div class="space-y-2">
                            <label for="checkin_code" class="block text-[10px] font-bold uppercase tracking-wider text-retro-muted">ID Tiket (T4S-XXXXXX)</label>
                            <div class="flex flex-col sm:flex-row gap-2">
                                <input type="text" id="checkin_code" placeholder="T4S-A1B2C3" required autocomplete="off"
                                       class="w-full sm:flex-grow bg-retro-input border border-stone-800 rounded-lg px-4 py-3 text-white placeholder-stone-700 font-semibold focus:outline-none focus:border-retro-red uppercase tracking-wider">
                                <button type="submit" 
                                        class="w-full sm:w-auto bg-retro-red hover:bg-retro-redAccent text-white font-bold uppercase tracking-wider text-xs px-6 py-3 rounded-lg shadow-lg hover:shadow-retro-red/10 transition-all duration-300 cursor-pointer whitespace-nowrap">
                                    Check In
                                </button>
                            </div>
                        </div>
                    </form>
                    
                    <!-- Result Display Area -->
                    <div id="checkin-result" class="mt-6 p-5 rounded-xl border border-stone-850/50 bg-stone-950/20 text-retro-muted text-xs flex flex-col items-center justify-center min-h-[160px] text-center">
                        <svg class="w-10 h-10 text-stone-700 mb-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                        <p class="font-medium">Menunggu pemindaian atau input kode manual...</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- ===================================================================
             TAB CONTENT: MANAGE SCHEDULES (KELOLA JADWAL)
             =================================================================== -->
        <div class="tab-content <?php echo ($active_tab === 'schedules') ? 'active' : ''; ?>" id="schedules">
            <div class="mb-6">
                <h3 class="font-heading text-2xl font-bold text-white tracking-wide">Pengaturan Jadwal Pertunjukan</h3>
                <p class="text-retro-muted text-sm mt-1">Sesuaikan judul, jam operasional, status reservasi, dan pengisi acara untuk minggu ini.</p>
            </div>
            
            <div class="overflow-x-auto bg-retro-card border border-stone-800/80 rounded-xl shadow-xl">
                <table class="w-full text-left border-collapse text-sm">
                    <thead>
                        <tr class="bg-stone-950/40 border-b border-stone-800 text-retro-red font-semibold text-xs tracking-wider uppercase">
                            <th class="px-6 py-4">Hari & Tanggal</th>
                            <th class="px-6 py-4">Jam Buka</th>
                            <th class="px-6 py-4">Nama Event (Show Title)</th>
                            <th class="px-6 py-4">Status Reservasi</th>
                            <th class="px-6 py-4">Catatan Khusus (Special Notes)</th>
                            <th class="px-6 py-4">Terisi / Kuota</th>
                            <th class="px-6 py-4 text-right">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-stone-850/40">
                        <?php foreach ($jadwal_minggu_ini as $sch): 
                            $tgl_fmt = date('d M Y', strtotime($sch['tanggal']));
                            $status_class = (strtolower($sch['status']) === 'open') ? 'bg-emerald-950/40 text-emerald-400 border-emerald-900/40' : 'bg-retro-red/15 text-retro-red border-retro-red/25';
                            if ($sch['is_special'] == 1) {
                                $status_class = (strtolower($sch['status']) === 'open') ? 'bg-amber-950/40 text-amber-400 border-amber-900/40' : 'bg-retro-red/15 text-retro-red border-retro-red/25';
                            }
                        ?>
                            <tr class="hover:bg-white/[0.01] transition-colors <?php echo ($sch['is_special'] == 1) ? 'border-l-4 border-amber-500 bg-amber-500/[0.01]' : ''; ?>">
                                <td class="px-6 py-4">
                                    <strong class="text-white"><?php echo $sch['hari']; ?></strong>
                                    <?php if ($sch['is_special'] == 1): ?>
                                        <span class="text-[9px] tracking-wide bg-amber-500/20 text-amber-400 px-1.5 py-0.5 rounded ml-1 font-semibold uppercase">Special</span>
                                    <?php endif; ?>
                                    <div class="text-xs text-retro-muted mt-0.5"><?php echo $tgl_fmt; ?></div>
                                </td>
                                <td class="px-6 py-4 text-retro-light"><?php echo htmlspecialchars($sch['jam']); ?></td>
                                <td class="px-6 py-4 font-semibold text-white"><?php echo htmlspecialchars($sch['nama_event']); ?></td>
                                <td class="px-6 py-4">
                                    <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-semibold uppercase tracking-wider border <?php echo $status_class; ?>">
                                        <?php echo $sch['status']; ?>
                                    </span>
                                </td>
                                <td class="px-6 py-4 text-xs text-retro-muted max-w-[200px] truncate">
                                    <?php echo !empty($sch['special_notes']) ? htmlspecialchars($sch['special_notes']) : '-'; ?>
                                </td>
                                <td class="px-6 py-4 font-semibold text-white"><?php echo $sch['terjual']; ?> / <?php echo $sch['kuota']; ?></td>
                                <td class="px-6 py-4 text-right">
                                    <!-- Trigger JS function to populate and open modal -->
                                    <button class="px-3 py-1.5 border border-retro-brown text-retro-brown hover:bg-retro-brown hover:text-white rounded-lg text-xs font-semibold uppercase tracking-wider transition-colors duration-300" 
                                            onclick="openEditScheduleModal(
                                                <?php echo $sch['id']; ?>,
                                                '<?php echo $sch['hari']; ?>',
                                                '<?php echo $sch['tanggal']; ?>',
                                                '<?php echo htmlspecialchars($sch['jam'], ENT_QUOTES); ?>',
                                                '<?php echo htmlspecialchars($sch['nama_event'], ENT_QUOTES); ?>',
                                                '<?php echo $sch['status']; ?>',
                                                '<?php echo htmlspecialchars($sch['special_notes'] ?? '', ENT_QUOTES); ?>'
                                            )">Edit</button>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>

            <div class="grid grid-cols-1 lg:grid-cols-12 gap-8 mt-12 pt-12 border-t border-stone-800/60">
                <!-- Form Tambah Event Spesial -->
                <div class="lg:col-span-5 bg-retro-card border border-stone-800/80 rounded-xl p-6 md:p-8 shadow-xl">
                    <h3 class="font-heading text-2xl font-bold text-white mb-6 tracking-wide text-amber-500">Tambah Event Spesial Baru</h3>
                    
                    <form action="admin_process" method="POST" class="space-y-5">
                        <input type="hidden" name="action" value="add_special_event">
                        
                        <div class="space-y-1.5">
                            <label for="spec-nama" class="block text-xs font-semibold uppercase tracking-wider text-retro-muted font-bold">Nama Event / Artis / Band</label>
                            <input type="text" name="nama_event" id="spec-nama" placeholder="Contoh: Velvet Blue Jazz Quartet Live" required
                                   class="w-full px-4 py-2.5 bg-retro-input border border-stone-800 rounded-lg text-white placeholder-stone-600 focus:outline-none focus:border-retro-red transition-all duration-300">
                        </div>
                        
                        <div class="grid grid-cols-2 gap-4">
                            <div class="space-y-1.5">
                                <label for="spec-tanggal" class="block text-xs font-semibold uppercase tracking-wider text-retro-muted font-bold">Tanggal Event</label>
                                <input type="date" name="tanggal" id="spec-tanggal" required
                                       class="w-full px-4 py-2.5 bg-retro-input border border-stone-800 rounded-lg text-white focus:outline-none focus:border-retro-red transition-all duration-300">
                            </div>
                            <div class="space-y-1.5">
                                <label for="spec-jam" class="block text-xs font-semibold uppercase tracking-wider text-retro-muted font-bold">Jam Show</label>
                                <input type="text" name="jam" id="spec-jam" placeholder="20:00 - 23:00 WIB" required
                                       class="w-full px-4 py-2.5 bg-retro-input border border-stone-800 rounded-lg text-white placeholder-stone-600 focus:outline-none focus:border-retro-red transition-all duration-300">
                            </div>
                        </div>
                        
                        <div class="space-y-1.5">
                            <label for="spec-notes" class="block text-xs font-semibold uppercase tracking-wider text-retro-muted font-bold">Catatan Acara / Deskripsi Tiket</label>
                            <textarea name="special_notes" id="spec-notes" rows="4" placeholder="Ketik deskripsi acara atau info tamu khusus..."
                                      class="w-full px-4 py-2.5 bg-retro-input border border-stone-800 rounded-lg text-white placeholder-stone-600 focus:outline-none focus:border-retro-red transition-all duration-300"></textarea>
                        </div>
                        
                        <button type="submit" class="w-full py-3 bg-gradient-to-r from-amber-500 to-yellow-600 hover:brightness-110 text-stone-950 font-bold uppercase tracking-wider text-xs rounded-lg transition-all duration-300">
                            Tambah Event Spesial
                        </button>
                    </form>
                </div>

                <!-- Daftar Event Spesial Mendatang -->
                <div class="lg:col-span-7 space-y-6">
                    <h3 class="font-heading text-2xl font-bold text-white tracking-wide text-amber-500">Daftar Event Spesial Mendatang</h3>
                    
                    <div class="overflow-x-auto bg-retro-card border border-stone-800/80 rounded-xl shadow-xl">
                        <table class="w-full text-left border-collapse text-sm">
                            <thead>
                                <tr class="bg-stone-950/40 border-b border-stone-800 text-amber-500 font-semibold text-xs tracking-wider uppercase">
                                    <th class="px-4 py-3">Hari & Tanggal</th>
                                    <th class="px-4 py-3">Nama Event</th>
                                    <th class="px-4 py-3">Jam</th>
                                    <th class="px-4 py-3">Terjual</th>
                                    <th class="px-4 py-3 text-right">Aksi</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-stone-850/40">
                                <?php if (empty($jadwal_spesial_mendatang)): ?>
                                    <tr>
                                        <td colspan="5" class="px-4 py-6 text-center text-retro-muted italic">Tidak ada event spesial mendatang yang terjadwal.</td>
                                    </tr>
                                <?php else: ?>
                                    <?php foreach ($jadwal_spesial_mendatang as $sch_spec): 
                                        $spec_tgl_fmt = date('d M Y', strtotime($sch_spec['tanggal']));
                                    ?>
                                        <tr class="hover:bg-white/[0.01] transition-colors border-l-4 border-amber-500 bg-amber-500/[0.01]">
                                            <td class="px-4 py-3">
                                                <strong class="text-white"><?php echo $sch_spec['hari']; ?></strong>
                                                <div class="text-[10px] text-retro-muted mt-0.5"><?php echo $spec_tgl_fmt; ?></div>
                                            </td>
                                            <td class="px-4 py-3 font-semibold text-white text-xs max-w-[150px] truncate"><?php echo htmlspecialchars($sch_spec['nama_event']); ?></td>
                                            <td class="px-4 py-3 text-xs text-retro-light"><?php echo htmlspecialchars($sch_spec['jam']); ?></td>
                                            <td class="px-4 py-3 text-xs text-white font-medium"><?php echo $sch_spec['terjual']; ?> / 50</td>
                                            <td class="px-4 py-3 text-right">
                                                <div class="inline-flex gap-1.5">
                                                    <button class="px-2 py-1 border border-stone-750 hover:border-amber-500 text-retro-muted hover:text-amber-500 rounded text-[10px] font-semibold uppercase tracking-wider transition-colors duration-300"
                                                            onclick="openEditScheduleModal(
                                                                <?php echo $sch_spec['id']; ?>,
                                                                '<?php echo $sch_spec['hari']; ?>',
                                                                '<?php echo $sch_spec['tanggal']; ?>',
                                                                '<?php echo htmlspecialchars($sch_spec['jam'], ENT_QUOTES); ?>',
                                                                '<?php echo htmlspecialchars($sch_spec['nama_event'], ENT_QUOTES); ?>',
                                                                '<?php echo $sch_spec['status']; ?>',
                                                                '<?php echo htmlspecialchars($sch_spec['special_notes'] ?? '', ENT_QUOTES); ?>'
                                                            )">Edit</button>
                                                    <a href="admin_process?action=delete_schedule&id=<?php echo $sch_spec['id']; ?>"
                                                       class="px-2 py-1 border border-retro-red/40 hover:bg-retro-red text-retro-red hover:text-white rounded text-[10px] font-semibold uppercase tracking-wider transition-colors duration-300"
                                                       onclick="return confirm('Hapus event spesial ini secara permanen?')">Hapus</a>
                                                </div>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <!-- ===================================================================
             TAB CONTENT: HISTORY SCHEDULES (RIWAYAT JADWAL)
             =================================================================== -->
        <div class="tab-content <?php echo ($active_tab === 'history') ? 'active' : ''; ?>" id="history">
            <div class="mb-6">
                <h3 class="font-heading text-2xl font-bold text-white tracking-wide">Riwayat Jadwal Pertunjukan</h3>
                <p class="text-retro-muted text-sm mt-1">Daftar pertunjukan musik yang telah terlaksana sebelumnya.</p>
            </div>
            
            <div class="overflow-x-auto bg-retro-card border border-stone-800/80 rounded-xl shadow-xl">
                <table class="w-full text-left border-collapse text-sm">
                    <thead>
                        <tr class="bg-stone-950/40 border-b border-stone-800 text-retro-red font-semibold text-xs tracking-wider uppercase">
                            <th class="px-6 py-4">Hari & Tanggal</th>
                            <th class="px-6 py-4">Jam Show</th>
                            <th class="px-6 py-4">Nama Event (Show Title)</th>
                            <th class="px-6 py-4">Catatan Khusus</th>
                            <th class="px-6 py-4">Terjual / Kuota</th>
                            <th class="px-6 py-4 text-right">Status</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-stone-850/40 text-zinc-300">
                        <?php if (empty($jadwal_riwayat)): ?>
                            <tr>
                                <td colspan="6" class="px-6 py-8 text-center text-retro-muted italic">Belum ada riwayat pertunjukan sebelumnya.</td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($jadwal_riwayat as $sch_past): 
                                $tgl_fmt = date('d M Y', strtotime($sch_past['tanggal']));
                            ?>
                                <tr class="hover:bg-white/[0.01] transition-colors">
                                    <td class="px-6 py-4">
                                        <strong class="text-white"><?php echo $sch_past['hari']; ?></strong>
                                        <div class="text-xs text-retro-muted mt-0.5"><?php echo $tgl_fmt; ?></div>
                                    </td>
                                    <td class="px-6 py-4 text-retro-light"><?php echo htmlspecialchars($sch_past['jam']); ?></td>
                                    <td class="px-6 py-4 font-semibold text-white"><?php echo htmlspecialchars($sch_past['nama_event']); ?></td>
                                    <td class="px-6 py-4 text-xs text-retro-muted max-w-[200px] truncate">
                                        <?php echo !empty($sch_past['special_notes']) ? htmlspecialchars($sch_past['special_notes']) : '-'; ?>
                                    </td>
                                    <td class="px-6 py-4 font-semibold text-white"><?php echo $sch_past['terjual']; ?> / <?php echo $sch_past['kuota']; ?></td>
                                    <td class="px-6 py-4 text-right">
                                        <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-semibold uppercase tracking-wider border border-stone-800 bg-stone-900/50 text-stone-500">
                                            Selesai
                                        </span>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- ===================================================================
             TAB CONTENT: MANAGE NEWS (KELOLA BERITA)
             =================================================================== -->
        <div class="tab-content <?php echo ($active_tab === 'news') ? 'active' : ''; ?>" id="news">
            <div class="grid grid-cols-1 lg:grid-cols-12 gap-8 items-start">
                
                <!-- Form Add News -->
                <div class="lg:col-span-5 bg-retro-card border border-stone-800/80 rounded-xl p-6 md:p-8 shadow-xl">
                    <h3 class="font-heading text-2xl font-bold text-white mb-6 tracking-wide">Terbitkan Berita / Pengumuman</h3>
                    
                    <form id="news-form" action="admin_process" method="POST" enctype="multipart/form-data" class="space-y-5">
                        <input type="hidden" name="action" value="add_berita">
                        
                        <div class="space-y-1.5">
                            <label for="judul" class="block text-xs font-semibold uppercase tracking-wider text-retro-muted">Judul Pengumuman</label>
                            <input type="text" name="judul" id="judul" placeholder="Contoh: 5 Stairs Band Anniversary Show" required
                                   class="w-full px-4 py-2.5 bg-retro-input border border-stone-800 rounded-lg text-white placeholder-stone-600 focus:outline-none focus:border-retro-red transition-all duration-300">
                        </div>
                        
                        <div class="space-y-1.5">
                            <label class="block text-xs font-semibold uppercase tracking-wider text-retro-muted">Konten / Isi Pengumuman</label>
                            
                            <!-- Custom Text Editor Toolbar -->
                            <div class="flex flex-wrap gap-1 bg-stone-900 border border-stone-800 border-b-0 rounded-t-lg p-2 text-xs">
                                <button type="button" onclick="formatDoc('bold')" class="px-2.5 py-1 bg-stone-850 hover:bg-retro-red/20 text-white rounded font-bold border border-white/5" title="Bold">B</button>
                                <button type="button" onclick="formatDoc('italic')" class="px-2.5 py-1 bg-stone-850 hover:bg-retro-red/20 text-white rounded italic border border-white/5" title="Italic">I</button>
                                <button type="button" onclick="formatDoc('underline')" class="px-2.5 py-1 bg-stone-850 hover:bg-retro-red/20 text-white rounded underline border border-white/5" title="Underline">U</button>
                                <button type="button" onclick="formatDoc('insertOrderedList')" class="px-2.5 py-1 bg-stone-850 hover:bg-retro-red/20 text-white rounded border border-white/5" title="Ordered List">1.</button>
                                <button type="button" onclick="formatDoc('insertUnorderedList')" class="px-2.5 py-1 bg-stone-850 hover:bg-retro-red/20 text-white rounded border border-white/5" title="Unordered List">&bull;</button>
                                <button type="button" onclick="formatDoc('createLink')" class="px-2.5 py-1 bg-stone-850 hover:bg-retro-red/20 text-white rounded border border-white/5" title="Insert Link">Link</button>
                                <button type="button" onclick="formatDoc('unlink')" class="px-2.5 py-1 bg-stone-850 hover:bg-retro-red/20 text-white rounded border border-white/5" title="Remove Link">Unlink</button>
                                <button type="button" onclick="formatDoc('removeFormat')" class="px-2.5 py-1 bg-stone-850 hover:bg-retro-red/20 text-white rounded border border-white/5" title="Clean Format">Clean</button>
                            </div>
                            
                            <!-- ContentEditable Editor -->
                            <div id="rich-editor" contenteditable="true" placeholder="Ketik isi pengumuman atau detail acara di sini..."
                                 class="w-full min-h-[160px] max-h-[300px] overflow-y-auto px-4 py-3 bg-retro-input border border-stone-800 rounded-b-lg text-white focus:outline-none focus:border-retro-red transition-all duration-300 text-sm leading-relaxed whitespace-pre-wrap"></div>
                                 
                            <!-- Hidden input to hold the actual HTML content -->
                            <input type="hidden" name="konten" id="konten-input">
                        </div>
                        
                        <div class="space-y-1.5">
                            <label for="template" class="block text-xs font-semibold uppercase tracking-wider text-retro-muted">Template Tampilan</label>
                            <select name="template" id="template" required 
                                    class="w-full px-4 py-2.5 bg-retro-input border border-stone-800 rounded-lg text-white focus:outline-none focus:border-retro-red transition-all duration-300">
                                <option value="classic">Classic (Serif, Cozy Gold Decor)</option>
                                <option value="retro">Retro (Maroon gradient, Typewriter)</option>
                                <option value="concert">Concert (Neon Concert Glow, Bold Header)</option>
                            </select>
                        </div>

                        <div class="space-y-1.5">
                            <label for="gambar" class="block text-xs font-semibold uppercase tracking-wider text-retro-muted">Gambar Pendukung (Opsional)</label>
                            <input type="file" name="gambar" id="gambar" accept="image/*" 
                                   class="w-full text-xs text-retro-muted file:mr-4 file:py-2 file:px-4 file:rounded-md file:border-0 file:text-xs file:font-semibold file:bg-stone-900 file:text-retro-light hover:file:bg-stone-800 transition-all cursor-pointer">
                            <small class="text-[10px] text-retro-muted block mt-1">Format yang didukung: JPG, JPEG, PNG, GIF. Maksimal 2MB.</small>
                            <!-- Cropped Preview -->
                            <div id="news-crop-preview-container" class="hidden mt-3 flex items-center gap-4 p-3 bg-stone-900/50 border border-stone-850 rounded-lg">
                                <img id="news-crop-preview" class="w-24 aspect-[16/9] object-cover rounded border border-stone-800" src="">
                                <div class="text-xs">
                                    <p class="text-emerald-400 font-semibold">Gambar telah disesuaikan (16:9)</p>
                                    <button type="button" id="news-crop-reset" class="text-retro-red hover:underline mt-1 block font-semibold uppercase tracking-wider text-[10px]">Reset Potongan</button>
                                </div>
                            </div>
                            <input type="hidden" name="cropped_news_img" id="cropped_news_img">
                        </div>
                        
                        <button type="submit" class="w-full py-3 bg-retro-red hover:bg-retro-redAccent text-white font-bold uppercase tracking-wider text-xs rounded-lg transition-colors duration-300">
                            Terbitkan Pengumuman
                        </button>
                    </form>
                </div>

                <!-- News Grid List -->
                <div class="lg:col-span-7 space-y-6">
                    <h3 class="font-heading text-2xl font-bold text-white tracking-wide mb-6">Berita Aktif Saat Ini</h3>
                    
                    <?php if (empty($berita_list)): ?>
                        <p class="text-retro-muted italic text-sm">Belum ada berita atau pengumuman yang diterbitkan.</p>
                    <?php else: ?>
                        <div class="space-y-4">
                            <?php foreach ($berita_list as $ber): ?>
                                <div class="bg-retro-card border border-stone-850/80 p-6 rounded-xl hover:border-retro-brown/30 transition-all duration-300 relative overflow-hidden">
                                    <span class="block text-xs text-retro-muted mb-2"><?php echo date('d M Y H:i', strtotime($ber['tanggal_post'])); ?></span>
                                    <h3 class="font-heading text-xl font-bold text-white mb-2"><?php echo htmlspecialchars($ber['judul']); ?></h3>
                                    <p class="text-sm text-retro-muted leading-relaxed line-clamp-3 mb-4"><?php echo nl2br(htmlspecialchars($ber['konten'])); ?></p>
                                    
                                    <div class="flex items-center justify-between border-t border-stone-850/50 pt-4 mt-2">
                                        <span class="text-xs text-retro-brown font-semibold uppercase tracking-wider">Template: <?php echo htmlspecialchars($ber['template']); ?></span>
                                        <div class="flex gap-2">
                                            <?php if (!empty($ber['file_path']) && file_exists(__DIR__ . '/' . $ber['file_path'])): ?>
                                                <a href="<?php echo htmlspecialchars(str_replace('.php', '', $ber['file_path'])); ?>" target="_blank" 
                                                   class="px-2.5 py-1.5 border border-retro-brown hover:bg-retro-brown text-retro-brown hover:text-white rounded-md text-xs font-semibold uppercase tracking-wider transition-colors duration-300">
                                                    Lihat Artikel
                                                </a>
                                            <?php endif; ?>
                                            <button type="button" 
                                                    onclick="openEditNewsModal(<?php echo $ber['id']; ?>, <?php echo htmlspecialchars(json_encode($ber['judul'])); ?>, <?php echo htmlspecialchars(json_encode($ber['konten'])); ?>, '<?php echo htmlspecialchars($ber['template']); ?>', '<?php echo htmlspecialchars($ber['gambar'] ?? ''); ?>')"
                                                    class="px-2.5 py-1.5 border border-amber-500 hover:bg-amber-500 text-amber-500 hover:text-black rounded-md text-xs font-semibold uppercase tracking-wider transition-colors duration-300">
                                                Edit
                                            </button>
                                            <a href="admin_process?action=delete_berita&id=<?php echo $ber['id']; ?>" 
                                               class="px-2.5 py-1.5 border border-retro-red hover:bg-retro-red text-retro-red hover:text-white rounded-md text-xs font-semibold uppercase tracking-wider transition-colors duration-300"
                                               onclick="return confirm('Hapus berita/pengumuman ini? File artikel fisik juga akan dihapus.')">
                                                Hapus
                                            </a>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>

            </div>
        </div>

        <!-- ===================================================================
             TAB CONTENT: COMPOSITIONS (KELOLA LAGU)
             =================================================================== -->
        <div class="tab-content <?php echo ($active_tab === 'compositions') ? 'active' : ''; ?>" id="compositions">
            <div class="grid grid-cols-1 lg:grid-cols-12 gap-8">
                
                <!-- Form: Tambah Lagu -->
                <div class="lg:col-span-5 bg-retro-card border border-stone-850 p-6 md:p-8 rounded-2xl shadow-xl space-y-6">
                    <div>
                        <h3 class="font-heading text-xl md:text-2xl font-bold text-white tracking-wide">Unggah Lagu Baru</h3>
                        <p class="text-retro-muted text-xs font-light mt-1">Tambahkan lagu orisinal atau aransemen baru ke pemutar musik website</p>
                    </div>

                    <form action="admin_process" method="POST" enctype="multipart/form-data" class="space-y-4">
                        <input type="hidden" name="action" value="add_komposisi">
                        <input type="hidden" name="tab" value="compositions">

                        <div class="space-y-1.5">
                            <label for="title" class="block text-xs font-semibold uppercase tracking-wider text-retro-muted">Judul Lagu</label>
                            <input type="text" name="title" id="title" required placeholder="Contoh: Fly Me to the Moon"
                                   class="w-full px-4 py-2.5 bg-retro-input border border-stone-800 rounded-lg text-white placeholder-stone-600 focus:outline-none focus:border-retro-red transition-all duration-300">
                        </div>

                        <div class="space-y-1.5">
                            <label for="artist" class="block text-xs font-semibold uppercase tracking-wider text-retro-muted">Artis / Komposer</label>
                            <input type="text" name="artist" id="artist" required placeholder="Contoh: The Jazz Ensemble"
                                   class="w-full px-4 py-2.5 bg-retro-input border border-stone-800 rounded-lg text-white placeholder-stone-600 focus:outline-none focus:border-retro-red transition-all duration-300">
                        </div>

                        <div class="space-y-1.5">
                            <label for="duration" class="block text-xs font-semibold uppercase tracking-wider text-retro-muted">Durasi Lagu (Otomatis)</label>
                            <input type="text" name="duration" id="duration" readonly required placeholder="Terisi otomatis setelah pilih audio"
                                   class="w-full px-4 py-2.5 bg-stone-900 border border-stone-850 rounded-lg text-stone-400 placeholder-stone-600 focus:outline-none cursor-not-allowed font-mono transition-all duration-300">
                        </div>

                        <!-- Audio Source Selection -->
                        <div class="space-y-1.5">
                            <label class="block text-xs font-semibold uppercase tracking-wider text-retro-muted">Sumber Audio</label>
                            <div class="flex gap-4 mb-2">
                                <label class="flex items-center gap-2 text-xs text-retro-light cursor-pointer">
                                    <input type="radio" name="audio_source_type" value="file" checked class="accent-retro-red" onclick="toggleAudioInput('file')">
                                    Upload File MP3
                                </label>
                                <label class="flex items-center gap-2 text-xs text-retro-light cursor-pointer">
                                    <input type="radio" name="audio_source_type" value="url" class="accent-retro-red" onclick="toggleAudioInput('url')">
                                    Link URL / CDN MP3
                                </label>
                            </div>
                        </div>

                        <div id="audio-file-container" class="space-y-1.5">
                            <label for="audio_file" class="block text-xs font-semibold uppercase tracking-wider text-retro-muted">File Audio MP3</label>
                            <input type="file" name="audio_file" id="audio_file" accept="audio/mpeg, audio/mp3" required
                                   class="w-full text-xs text-retro-muted file:mr-4 file:py-2 file:px-4 file:rounded-md file:border-0 file:text-xs file:font-semibold file:bg-stone-900 file:text-retro-light hover:file:bg-stone-800 transition-all cursor-pointer">
                            <small class="text-[10px] text-retro-muted block mt-1">Hanya mendukung format MP3. Maksimal 10MB.</small>
                        </div>

                        <div id="audio-url-container" class="space-y-1.5 hidden">
                            <label for="audio_url" class="block text-xs font-semibold uppercase tracking-wider text-retro-muted">Link URL Audio MP3</label>
                            <input type="url" name="audio_url" id="audio_url" placeholder="Contoh: https://cdn1.suno.ai/xxx.mp3"
                                   class="w-full px-4 py-2.5 bg-retro-input border border-stone-800 rounded-lg text-white placeholder-stone-600 focus:outline-none focus:border-retro-red transition-all duration-300">
                            <small class="text-[10px] text-retro-muted block mt-1">Masukkan URL langsung ke file MP3 eksternal.</small>
                        </div>

                        <div class="space-y-1.5">
                            <label for="cover_file" class="block text-xs font-semibold uppercase tracking-wider text-retro-muted">Gambar Cover Art</label>
                            <input type="file" name="cover_file" id="cover_file" accept="image/*" required
                                   class="w-full text-xs text-retro-muted file:mr-4 file:py-2 file:px-4 file:rounded-md file:border-0 file:text-xs file:font-semibold file:bg-stone-900 file:text-retro-light hover:file:bg-stone-800 transition-all cursor-pointer">
                            <small class="text-[10px] text-retro-muted block mt-1">Format yang didukung: JPG, JPEG, PNG, WEBP. Maksimal 2MB.</small>
                            <!-- Cropped Preview -->
                            <div id="cover-crop-preview-container" class="hidden mt-3 flex items-center gap-4 p-3 bg-stone-900/50 border border-stone-850 rounded-lg">
                                <img id="cover-crop-preview" class="w-16 aspect-square object-cover rounded border border-stone-800" src="">
                                <div class="text-xs">
                                    <p class="text-emerald-400 font-semibold">Cover telah disesuaikan (1:1)</p>
                                    <button type="button" id="cover-crop-reset" class="text-retro-red hover:underline mt-1 block font-semibold uppercase tracking-wider text-[10px]">Reset Potongan</button>
                                </div>
                            </div>
                            <input type="hidden" name="cropped_cover_img" id="cropped_cover_img">
                        </div>

                        <div class="space-y-1.5">
                            <label for="youtube_url" class="block text-xs font-semibold uppercase tracking-wider text-retro-muted">Link YouTube (Opsional)</label>
                            <input type="url" name="youtube_url" id="youtube_url" placeholder="Contoh: https://youtube.com/watch?v=..."
                                   class="w-full px-4 py-2.5 bg-retro-input border border-stone-800 rounded-lg text-white placeholder-stone-600 focus:outline-none focus:border-retro-red transition-all duration-300">
                        </div>

                        <div class="space-y-1.5">
                            <label for="soundcloud_url" class="block text-xs font-semibold uppercase tracking-wider text-retro-muted">Link SoundCloud (Opsional)</label>
                            <input type="url" name="soundcloud_url" id="soundcloud_url" placeholder="Contoh: https://soundcloud.com/..."
                                   class="w-full px-4 py-2.5 bg-retro-input border border-stone-800 rounded-lg text-white placeholder-stone-600 focus:outline-none focus:border-retro-red transition-all duration-300">
                        </div>

                        <div class="space-y-1.5">
                            <label for="spotify_url" class="block text-xs font-semibold uppercase tracking-wider text-retro-muted">Link Spotify (Opsional)</label>
                            <input type="url" name="spotify_url" id="spotify_url" placeholder="Contoh: https://open.spotify.com/track/..."
                                   class="w-full px-4 py-2.5 bg-retro-input border border-stone-800 rounded-lg text-white placeholder-stone-600 focus:outline-none focus:border-retro-red transition-all duration-300">
                        </div>

                        <div class="space-y-1.5">
                            <label for="lyrics" class="block text-xs font-semibold uppercase tracking-wider text-retro-muted">Lirik Lagu (Opsional)</label>
                            <textarea name="lyrics" id="lyrics" rows="4" placeholder="Ketik atau paste lirik lagu di sini..."
                                      class="w-full px-4 py-2.5 bg-retro-input border border-stone-800 rounded-lg text-white placeholder-stone-600 focus:outline-none focus:border-retro-red transition-all duration-300 resize-y text-xs leading-relaxed"></textarea>
                        </div>

                        <button type="submit" class="w-full py-3 bg-retro-red hover:bg-retro-redAccent text-white font-bold uppercase tracking-wider text-xs rounded-lg transition-colors duration-300">
                            Unggah & Simpan Lagu
                        </button>
                    </form>
                </div>

                <!-- Table: Daftar Lagu -->
                <div class="lg:col-span-7 bg-retro-card border border-stone-850 p-6 md:p-8 rounded-2xl shadow-xl space-y-6">
                    <h3 class="font-heading text-xl md:text-2xl font-bold text-white tracking-wide">Daftar Lagu Terdaftar</h3>
                    
                    <?php
                    // Ambil daftar lagu
                    $sql_compositions = "SELECT * FROM `komposisi` ORDER BY `id` DESC";
                    $res_comp = $conn->query($sql_compositions);
                    $comp_list = [];
                    if ($res_comp) {
                        while ($row = $res_comp->fetch_assoc()) {
                            $comp_list[] = $row;
                        }
                    }
                    ?>
                    
                    <?php if (empty($comp_list)): ?>
                        <p class="text-retro-muted italic text-sm">Belum ada lagu yang terdaftar di database.</p>
                    <?php else: ?>
                        <div class="overflow-x-auto">
                            <table class="w-full text-left border-collapse">
                                <thead>
                                    <tr class="border-b border-stone-800 text-[10px] uppercase tracking-widest text-retro-muted font-semibold">
                                        <th class="pb-3 w-16">Cover</th>
                                        <th class="pb-3 pl-4">Judul Lagu</th>
                                        <th class="pb-3">Artis</th>
                                        <th class="pb-3 w-20 text-center">Durasi</th>
                                        <th class="pb-3 w-20 text-center">Aksi</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-stone-800 text-xs font-light">
                                    <?php foreach ($comp_list as $comp): ?>
                                        <tr class="hover:bg-stone-900/30 transition-colors duration-200">
                                            <td class="py-3">
                                                <div class="w-10 h-10 rounded overflow-hidden border border-stone-800">
                                                    <img src="<?php echo htmlspecialchars($comp['cover']); ?>" alt="Cover" class="w-full h-full object-cover">
                                                </div>
                                            </td>
                                            <td class="py-3 pl-4 text-white font-medium max-w-[150px] truncate">
                                                <?php echo htmlspecialchars($comp['title']); ?>
                                            </td>
                                            <td class="py-3 text-retro-muted max-w-[120px] truncate">
                                                <?php echo htmlspecialchars($comp['artist']); ?>
                                            </td>
                                            <td class="py-3 text-center text-retro-muted font-mono">
                                                <?php echo htmlspecialchars($comp['duration']); ?>
                                            </td>
                                            <td class="py-3 text-center">
                                                <div class="flex justify-center gap-1.5">
                                                    <button type="button" 
                                                            onclick="openEditKomposisiModal(<?php echo htmlspecialchars(json_encode($comp)); ?>)"
                                                            class="px-2 py-1 border border-amber-500 hover:bg-amber-500 text-amber-500 hover:text-black rounded-md text-[10px] font-semibold uppercase tracking-wider transition-colors duration-300 font-medium">
                                                        Edit
                                                    </button>
                                                    <a href="admin_process?action=delete_komposisi&id=<?php echo $comp['id']; ?>&tab=compositions"
                                                       class="px-2 py-1 border border-retro-red hover:bg-retro-red text-retro-red hover:text-white rounded-md text-[10px] font-semibold uppercase tracking-wider transition-colors duration-300"
                                                       onclick="return confirm('Apakah Anda yakin ingin menghapus lagu ini? File fisik audio dan cover juga akan dihapus permanen.')">
                                                        Hapus
                                                    </a>
                                                </div>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php endif; ?>
                </div>

            </div>
        </div>

    </main>

    <!-- Glassmorphic Modal: Edit Schedule -->
    <div class="modal-overlay fixed inset-0 z-50 bg-black/80 backdrop-blur-sm flex items-center justify-center p-4" id="edit-schedule-modal">
        <div class="bg-retro-card border border-stone-800 max-w-lg w-full rounded-2xl p-6 md:p-8 relative shadow-2xl">
            <span class="absolute top-4 right-5 text-2xl text-retro-muted hover:text-white cursor-pointer transition-colors" id="schedule-modal-close">&times;</span>
            <h3 class="font-heading text-2xl font-bold text-white mb-6 tracking-wide">Edit Jadwal Pertunjukan</h3>
            
            <form action="admin_process" method="POST" class="space-y-4">
                <input type="hidden" name="action" value="edit_jadwal">
                <input type="hidden" name="id" id="edit-id">
                
                <div class="space-y-1.5">
                    <label for="edit-hari-tgl" class="block text-xs font-semibold uppercase tracking-wider text-retro-muted">Hari Show (Sistem Informasi)</label>
                    <input type="text" id="edit-hari-tgl" readonly 
                           class="w-full px-4 py-2.5 bg-stone-900 border border-stone-850 rounded-lg text-retro-muted cursor-not-allowed focus:outline-none">
                </div>
                
                <div class="grid grid-cols-2 gap-4">
                    <div class="space-y-1.5">
                        <label for="edit-tanggal" class="block text-xs font-semibold uppercase tracking-wider text-retro-muted">Tanggal Pertunjukan</label>
                        <input type="date" name="tanggal" id="edit-tanggal" required
                               class="w-full px-4 py-2.5 bg-retro-input border border-stone-800 rounded-lg text-white focus:outline-none focus:border-retro-red">
                    </div>
                    <div class="space-y-1.5">
                        <label for="edit-jam" class="block text-xs font-semibold uppercase tracking-wider text-retro-muted">Jam Buka Show</label>
                        <input type="text" name="jam" id="edit-jam" required
                               class="w-full px-4 py-2.5 bg-retro-input border border-stone-800 rounded-lg text-white focus:outline-none focus:border-retro-red">
                    </div>
                </div>
                
                <div class="space-y-1.5">
                    <label for="edit-nama-event" class="block text-xs font-semibold uppercase tracking-wider text-retro-muted">Judul Pertunjukan (Show Title)</label>
                    <input type="text" name="nama_event" id="edit-nama-event" required
                           class="w-full px-4 py-2.5 bg-retro-input border border-stone-800 rounded-lg text-white focus:outline-none focus:border-retro-red">
                </div>
                
                <div class="space-y-1.5">
                    <label for="edit-status" class="block text-xs font-semibold uppercase tracking-wider text-retro-muted">Status Reservasi</label>
                    <select name="status" id="edit-status" required
                            class="w-full px-4 py-2.5 bg-retro-input border border-stone-800 rounded-lg text-white focus:outline-none focus:border-retro-red">
                        <option value="Open">Open (Buka Pemesanan)</option>
                        <option value="Closed">Closed (Tutup Pemesanan)</option>
                    </select>
                </div>
                
                <div class="space-y-1.5">
                    <label for="edit-special-notes" class="block text-xs font-semibold uppercase tracking-wider text-retro-muted">Catatan Khusus / Band Tamu (Opsional)</label>
                    <textarea name="special_notes" id="edit-special-notes" rows="3" placeholder="Contoh: Bintang Tamu: Velvet Blue Trio (Swing)"
                              class="w-full px-4 py-2.5 bg-retro-input border border-stone-800 rounded-lg text-white placeholder-stone-600 focus:outline-none focus:border-retro-red"></textarea>
                </div>
                
                <button type="submit" class="w-full py-3 bg-retro-red hover:bg-retro-redAccent text-white font-bold uppercase tracking-wider text-xs rounded-lg transition-colors duration-300 mt-2">
                    Simpan Perubahan
                </button>
            </form>
        </div>
    </div>

    <!-- Footer -->
    <footer class="bg-stone-950/40 border-t border-stone-900/60 py-6 mt-16 text-center text-xs text-retro-muted font-medium">
        <p>&copy; <?php echo date('Y'); ?> The 4 Stairs Music Hall. Management Panel.</p>
    </footer>

    <!-- Chart.js CDN and Script -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', () => {
            // Device Detection (checks user agent)
            if (/Mobi|Android|iPhone|iPad|iPod/i.test(navigator.userAgent)) {
                document.body.classList.add('is-mobile');
            }

            const ctx = document.getElementById('reservationsChart');
            if (ctx) {
                new Chart(ctx, {
                    type: 'line',
                    data: {
                        labels: <?php echo json_encode($chart_labels); ?>,
                        datasets: [
                            {
                                label: 'Tiket Terjual (Pcs)',
                                data: <?php echo json_encode($chart_tickets); ?>,
                                borderColor: '#8b1e22',
                                backgroundColor: 'rgba(139, 30, 34, 0.1)',
                                borderWidth: 2,
                                tension: 0.3,
                                yAxisID: 'y'
                            },
                            {
                                label: 'Pendapatan (IDR)',
                                data: <?php echo json_encode($chart_earnings); ?>,
                                borderColor: '#dfb15b',
                                backgroundColor: 'rgba(223, 177, 91, 0.1)',
                                borderWidth: 2,
                                tension: 0.3,
                                yAxisID: 'y1'
                            }
                        ]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        scales: {
                            x: {
                                grid: {
                                    color: 'rgba(255, 255, 255, 0.05)'
                                },
                                ticks: {
                                    color: '#a8a29e',
                                    font: {
                                        family: 'Outfit'
                                    }
                                }
                            },
                            y: {
                                type: 'linear',
                                display: true,
                                position: 'left',
                                grid: {
                                    color: 'rgba(255, 255, 255, 0.05)'
                                },
                                ticks: {
                                    color: '#a8a29e',
                                    stepSize: 1
                                },
                                title: {
                                    display: true,
                                    text: 'Jumlah Tiket',
                                    color: '#8b1e22'
                                }
                            },
                            y1: {
                                type: 'linear',
                                display: true,
                                position: 'right',
                                grid: {
                                    drawOnChartArea: false
                                },
                                ticks: {
                                    color: '#a8a29e',
                                    callback: function(value) {
                                        return 'Rp ' + value.toLocaleString('id-ID');
                                    }
                                },
                                title: {
                                    display: true,
                                    text: 'Pendapatan (IDR)',
                                    color: '#dfb15b'
                                }
                            }
                        },
                        plugins: {
                            legend: {
                                labels: {
                                    color: '#f5f5f4',
                                    font: {
                                        family: 'Outfit',
                                        size: 11
                                    }
                                }
                            }
                        }
                    }
                });
            }
        });
    </script>

    <!-- Tab Control Javascript & QR Scanner Client -->
    <script>
        document.addEventListener('DOMContentLoaded', () => {
            // Tab Selection Logic
            const tabBtns = document.querySelectorAll('.tab-btn');
            const tabContents = document.querySelectorAll('.tab-content');

            tabBtns.forEach(btn => {
                btn.addEventListener('click', () => {
                    const tabName = btn.getAttribute('data-tab');

                    // Nonaktifkan semua button & content
                    tabBtns.forEach(b => b.classList.remove('active'));
                    tabContents.forEach(c => c.classList.remove('active'));

                    // Aktifkan button & content yang sesuai
                    btn.classList.add('active');
                    const targetContent = document.getElementById(tabName);
                    if (targetContent) {
                        targetContent.classList.add('active');
                    }

                    // Start/Stop Webcam Scanner based on tab state
                    if (tabName !== 'checkin') {
                        stopScanner();
                    }

                    // Update URL search query untuk menyimpan tab aktif (mencegah reset pas refresh)
                    const url = new URL(window.location);
                    url.searchParams.set('tab', tabName);
                    // Hapus parameter search jika pindah tab selain reservations
                    if (tabName !== 'reservations') {
                        url.searchParams.delete('search');
                    }
                    window.history.pushState({}, '', url);
                });
            });

            // Schedule Modal Popup Logic
            const modal = document.getElementById('edit-schedule-modal');
            const modalCloseBtn = document.getElementById('schedule-modal-close');

            window.openEditScheduleModal = function(id, hari, tanggal, jam, nama_event, status, special_notes) {
                document.getElementById('edit-id').value = id;
                document.getElementById('edit-hari-tgl').value = hari;
                document.getElementById('edit-tanggal').value = tanggal;
                document.getElementById('edit-jam').value = jam;
                document.getElementById('edit-nama-event').value = nama_event;
                document.getElementById('edit-status').value = status;
                document.getElementById('edit-special-notes').value = special_notes;

                modal.classList.add('open');
                document.body.style.overflow = 'hidden';
            }

            if (modalCloseBtn) {
                modalCloseBtn.addEventListener('click', () => {
                    modal.classList.remove('open');
                    document.body.style.overflow = 'auto';
                });
            }

            modal.addEventListener('click', (e) => {
                if (e.target === modal) {
                    modal.classList.remove('open');
                    document.body.style.overflow = 'auto';
                }
            });

            // QR Code Scanner State & Logic
            let html5QrCode = null;
            const qrConfig = { fps: 15, qrbox: { width: 250, height: 250 } };

            window.handleScanBtnClick = function() {
                if (html5QrCode && html5QrCode.isScanning) {
                    stopScanner();
                } else {
                    startScanner();
                }
            }

            window.startScanner = function() {
                const qrReaderElem = document.getElementById("qr-reader");
                if (!qrReaderElem) return;
                
                // Reset error message
                const errorMsg = document.getElementById("qr-error-msg");
                errorMsg.classList.add("hidden");
                errorMsg.innerText = "";
                
                const btn = document.getElementById('toggle-scan-btn');
                const btnText = document.getElementById('scan-btn-text');

                if (html5QrCode === null) {
                    html5QrCode = new Html5Qrcode("qr-reader");
                }

                if (!html5QrCode.isScanning) {
                    btn.disabled = true;
                    btnText.innerText = "Mengaktifkan Kamera...";
                    
                    html5QrCode.start(
                        { facingMode: "environment" },
                        qrConfig,
                        (decodedText) => {
                            playCheckinBeep();
                            stopScanner();
                            checkInTicket(decodedText);
                        },
                        (errorMessage) => {
                            // Verbose, ignore
                        }
                    ).then(() => {
                        btn.disabled = false;
                        btnText.innerText = "Hentikan Scan";
                        btn.classList.remove('bg-retro-red', 'hover:bg-retro-redAccent');
                        btn.classList.add('bg-stone-850', 'hover:bg-stone-800');
                    }).catch(err => {
                        console.error("Gagal memulai kamera: ", err);
                        btn.disabled = false;
                        btnText.innerText = "Mulai Scan QR";
                        btn.classList.add('bg-retro-red', 'hover:bg-retro-redAccent');
                        btn.classList.remove('bg-stone-850', 'hover:bg-stone-800');
                        errorMsg.innerText = "Gagal mengakses kamera. Pastikan perangkat Anda memiliki kamera dan Anda telah memberikan izin akses kamera.";
                        errorMsg.classList.remove("hidden");
                    });
                }
            }

            window.stopScanner = function() {
                const btn = document.getElementById('toggle-scan-btn');
                const btnText = document.getElementById('scan-btn-text');
                
                if (html5QrCode && html5QrCode.isScanning) {
                    btn.disabled = true;
                    btnText.innerText = "Menonaktifkan Kamera...";
                    
                    html5QrCode.stop().then(() => {
                        btn.disabled = false;
                        btnText.innerText = "Mulai Scan QR";
                        btn.classList.add('bg-retro-red', 'hover:bg-retro-redAccent');
                        btn.classList.remove('bg-stone-850', 'hover:bg-stone-800');
                        console.log("Scanner stopped.");
                    }).catch(err => {
                        console.error("Gagal menghentikan kamera: ", err);
                        btn.disabled = false;
                        btnText.innerText = "Hentikan Scan";
                        btn.classList.remove('bg-retro-red', 'hover:bg-retro-redAccent');
                        btn.classList.add('bg-stone-850', 'hover:bg-stone-800');
                    });
                }
            }

            function playCheckinBeep() {
                try {
                    const audioCtx = new (window.AudioContext || window.webkitAudioContext)();
                    const oscillator = audioCtx.createOscillator();
                    const gainNode = audioCtx.createGain();

                    oscillator.connect(gainNode);
                    gainNode.connect(audioCtx.destination);

                    oscillator.type = 'sine';
                    oscillator.frequency.setValueAtTime(880, audioCtx.currentTime); // A5 tone
                    gainNode.gain.setValueAtTime(0.1, audioCtx.currentTime);

                    oscillator.start();
                    oscillator.stop(audioCtx.currentTime + 0.15); // beep duration 150ms
                } catch (e) {
                    console.log("AudioContext not supported or blocked: " + e);
                }
            }

            // AJAX Check-in Ticket Request
            window.checkInTicket = function(code) {
                const resultDiv = document.getElementById('checkin-result');
                resultDiv.className = "mt-6 p-5 rounded-xl border border-yellow-600/30 bg-yellow-950/20 text-yellow-400 text-sm animate-pulse flex flex-col items-center text-center w-full";
                resultDiv.innerHTML = `<p class="font-semibold">Memproses tiket ${code}...</p>`;
                
                const formData = new FormData();
                formData.append('action', 'check_in_ticket');
                formData.append('code', code);

                fetch('admin_process.php', {
                    method: 'POST',
                    body: formData
                })
                .then(response => {
                    if (!response.ok) {
                        throw new Error('Sistem error');
                    }
                    return response.json();
                })
                .then(data => {
                    resultDiv.classList.remove('animate-pulse');
                    if (data.status === 'success') {
                        resultDiv.className = "mt-6 p-5 rounded-xl border border-emerald-600/30 bg-emerald-950/20 text-emerald-400 text-sm w-full text-left";
                        resultDiv.innerHTML = `
                            <div class="flex items-start gap-3">
                                <div class="p-2 bg-emerald-600/20 rounded-full text-emerald-400 shrink-0">
                                    <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                                    </svg>
                                </div>
                                <div class="flex-grow">
                                    <h4 class="font-bold text-base text-white">Check-In Berhasil!</h4>
                                    <p class="mt-1 font-medium text-emerald-300">Kehadiran berhasil dicatat.</p>
                                    <div class="mt-3 space-y-1.5 text-xs text-stone-300 border-t border-white/5 pt-2.5">
                                        <div><span class="text-stone-500 font-semibold uppercase tracking-wider text-[10px]">ID Tiket:</span> <strong class="text-retro-brown font-mono text-sm ml-1">${data.ticket.id}</strong></div>
                                        <div><span class="text-stone-500 font-semibold uppercase tracking-wider text-[10px]">Nama:</span> <strong class="text-white ml-1">${data.ticket.nama}</strong></div>
                                        <div><span class="text-stone-500 font-semibold uppercase tracking-wider text-[10px]">Acara:</span> <strong class="text-white ml-1">${data.ticket.nama_event}</strong></div>
                                        <div><span class="text-stone-500 font-semibold uppercase tracking-wider text-[10px]">Hari:</span> <strong class="text-white ml-1">${data.ticket.hari} &bull; ${data.ticket.jam}</strong></div>
                                        <div><span class="text-stone-500 font-semibold uppercase tracking-wider text-[10px]">Jumlah:</span> <strong class="text-white ml-1">${data.ticket.jumlah} Kursi</strong></div>
                                    </div>
                                </div>
                            </div>
                        `;
                    } else {
                        resultDiv.className = "mt-6 p-5 rounded-xl border border-rose-600/30 bg-rose-950/20 text-rose-400 text-sm w-full text-left";
                        resultDiv.innerHTML = `
                            <div class="flex items-start gap-3">
                                <div class="p-2 bg-rose-600/20 rounded-full text-rose-400 shrink-0">
                                    <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                                    </svg>
                                </div>
                                <div class="flex-grow">
                                    <h4 class="font-bold text-base text-white">Check-In Gagal</h4>
                                    <p class="mt-1 font-medium text-rose-350">${data.message}</p>
                                </div>
                            </div>
                        `;
                    }
                })
                .catch(err => {
                    resultDiv.classList.remove('animate-pulse');
                    resultDiv.className = "mt-6 p-5 rounded-xl border border-rose-600/30 bg-rose-950/20 text-rose-400 text-sm w-full text-left";
                    resultDiv.innerHTML = `
                        <h4 class="font-bold text-base text-white">Error Koneksi</h4>
                        <p class="mt-1 text-rose-350">Gagal memproses check-in. Periksa koneksi internet atau status login admin.</p>
                    `;
                });
            }

            window.submitManualCheckin = function() {
                const codeField = document.getElementById('checkin_code');
                const code = codeField.value.trim().toUpperCase();
                if (code !== '') {
                    checkInTicket(code);
                    codeField.value = '';
                }
            }

            // Custom Text Editor Format Document command
            window.formatDoc = function(cmd, value = null) {
                if (cmd === 'createLink') {
                    let url = prompt("Masukkan URL link:");
                    if (url) {
                        document.execCommand(cmd, false, url);
                    }
                } else {
                    document.execCommand(cmd, false, value);
                }
            }

            // News form rich text editor copy logic
            const newsForm = document.getElementById('news-form');
            if (newsForm) {
                const richEditor = document.getElementById('rich-editor');
                const kontenInput = document.getElementById('konten-input');
                
                newsForm.addEventListener('submit', function(e) {
                    kontenInput.value = richEditor.innerHTML.trim();
                    const plainText = richEditor.innerText.trim();
                    if (plainText === '') {
                        alert('Konten berita tidak boleh kosong!');
                        e.preventDefault();
                        return false;
                    }
                });
            }

            // Image Cropping & Audio Duration Logic
            let cropper = null;
            let currentCropSource = '';
            const cropperModal = document.getElementById('cropper-modal');
            const cropperImage = document.getElementById('cropper-image');
            const cropperClose = document.getElementById('cropper-modal-close');
            const cropperCancel = document.getElementById('cropper-cancel-btn');
            const cropperSave = document.getElementById('cropper-save-btn');
            
            const gambarInput = document.getElementById('gambar');
            const coverInput = document.getElementById('cover_file');
            const editGambarInput = document.getElementById('edit_gambar');
            const editCoverInput = document.getElementById('edit_cover_file');
            
            function initCropper(file, type) {
                const reader = new FileReader();
                reader.onload = function(e) {
                    cropperImage.src = e.target.result;
                    cropperModal.classList.remove('hidden');
                    cropperModal.classList.add('flex');
                    
                    if (cropper) {
                        cropper.destroy();
                    }
                    
                    const ratio = (type === 'news' || type === 'edit_news') ? 16 / 9 : 1 / 1;
                    cropper = new Cropper(cropperImage, {
                        aspectRatio: ratio,
                        viewMode: 1,
                        autoCropArea: 1,
                        responsive: true,
                        restore: false,
                        checkOrientation: false,
                        modal: true,
                        guides: true,
                        highlight: false,
                        cropBoxMovable: true,
                        cropBoxResizable: true,
                        toggleDragModeOnDblclick: false,
                    });
                    
                    currentCropSource = type;
                };
                reader.readAsDataURL(file);
            }
            
            if (gambarInput) {
                gambarInput.addEventListener('change', function(e) {
                    if (e.target.files && e.target.files[0]) {
                        initCropper(e.target.files[0], 'news');
                    }
                });
            }
            
            if (coverInput) {
                coverInput.addEventListener('change', function(e) {
                    if (e.target.files && e.target.files[0]) {
                        initCropper(e.target.files[0], 'cover');
                    }
                });
            }

            if (editGambarInput) {
                editGambarInput.addEventListener('change', function(e) {
                    if (e.target.files && e.target.files[0]) {
                        initCropper(e.target.files[0], 'edit_news');
                    }
                });
            }
            
            if (editCoverInput) {
                editCoverInput.addEventListener('change', function(e) {
                    if (e.target.files && e.target.files[0]) {
                        initCropper(e.target.files[0], 'edit_cover');
                    }
                });
            }
            
            function closeCropper() {
                cropperModal.classList.add('hidden');
                cropperModal.classList.remove('flex');
                if (cropper) {
                    cropper.destroy();
                    cropper = null;
                }
                if (currentCropSource === 'news' && !document.getElementById('cropped_news_img').value) {
                    gambarInput.value = '';
                } else if (currentCropSource === 'edit_news' && !document.getElementById('edit_cropped_news_img').value) {
                    editGambarInput.value = '';
                } else if (currentCropSource === 'cover' && !document.getElementById('cropped_cover_img').value) {
                    coverInput.value = '';
                } else if (currentCropSource === 'edit_cover' && !document.getElementById('edit_cropped_cover_img').value) {
                    editCoverInput.value = '';
                }
            }
            
            if (cropperClose) cropperClose.addEventListener('click', closeCropper);
            if (cropperCancel) cropperCancel.addEventListener('click', closeCropper);
            
            if (cropperSave) {
                cropperSave.addEventListener('click', function() {
                    if (!cropper) return;
                    
                    const ratioWidth = (currentCropSource === 'news' || currentCropSource === 'edit_news') ? 1280 : 800;
                    const ratioHeight = (currentCropSource === 'news' || currentCropSource === 'edit_news') ? 720 : 800;
                    
                    const canvas = cropper.getCroppedCanvas({
                        width: ratioWidth,
                        height: ratioHeight,
                        imageSmoothingEnabled: true,
                        imageSmoothingQuality: 'high'
                    });
                    
                    const base64Data = canvas.toDataURL('image/jpeg', 0.9);
                    
                    if (currentCropSource === 'news') {
                        document.getElementById('cropped_news_img').value = base64Data;
                        document.getElementById('news-crop-preview').src = base64Data;
                        document.getElementById('news-crop-preview-container').classList.remove('hidden');
                    } else if (currentCropSource === 'edit_news') {
                        document.getElementById('edit_cropped_news_img').value = base64Data;
                        document.getElementById('edit-news-crop-preview').src = base64Data;
                        document.getElementById('edit-news-crop-preview-container').classList.remove('hidden');
                    } else if (currentCropSource === 'cover') {
                        document.getElementById('cropped_cover_img').value = base64Data;
                        document.getElementById('cover-crop-preview').src = base64Data;
                        document.getElementById('cover-crop-preview-container').classList.remove('hidden');
                    } else if (currentCropSource === 'edit_cover') {
                        document.getElementById('edit_cropped_cover_img').value = base64Data;
                        document.getElementById('edit-cover-crop-preview').src = base64Data;
                        document.getElementById('edit-cover-crop-preview-container').classList.remove('hidden');
                    }
                    
                    cropperModal.classList.add('hidden');
                    cropperModal.classList.remove('flex');
                    cropper.destroy();
                    cropper = null;
                });
            }
            
            const newsCropReset = document.getElementById('news-crop-reset');
            if (newsCropReset) {
                newsCropReset.addEventListener('click', function() {
                    document.getElementById('cropped_news_img').value = '';
                    gambarInput.value = '';
                    document.getElementById('news-crop-preview-container').classList.add('hidden');
                });
            }
            
            const coverCropReset = document.getElementById('cover-crop-reset');
            if (coverCropReset) {
                coverCropReset.addEventListener('click', function() {
                    document.getElementById('cropped_cover_img').value = '';
                    coverInput.value = '';
                    document.getElementById('cover-crop-preview-container').classList.add('hidden');
                });
            }

            const editNewsCropReset = document.getElementById('edit-news-crop-reset');
            if (editNewsCropReset) {
                editNewsCropReset.addEventListener('click', function() {
                    document.getElementById('edit_cropped_news_img').value = '';
                    editGambarInput.value = '';
                    document.getElementById('edit-news-crop-preview-container').classList.add('hidden');
                });
            }
            
            const editCoverCropReset = document.getElementById('edit-cover-crop-reset');
            if (editCoverCropReset) {
                editCoverCropReset.addEventListener('click', function() {
                    document.getElementById('edit_cropped_cover_img').value = '';
                    editCoverInput.value = '';
                    document.getElementById('edit-cover-crop-preview-container').classList.add('hidden');
                });
            }
            
            // Audio Source Toggle & Detection Logic
            window.toggleAudioInput = function(type) {
                const fileContainer = document.getElementById('audio-file-container');
                const urlContainer = document.getElementById('audio-url-container');
                const fileInput = document.getElementById('audio_file');
                const urlInput = document.getElementById('audio_url');
                const durationInput = document.getElementById('duration');
                
                if (type === 'url') {
                    fileContainer.classList.add('hidden');
                    urlContainer.classList.remove('hidden');
                    fileInput.removeAttribute('required');
                    fileInput.value = '';
                    urlInput.setAttribute('required', 'required');
                } else {
                    fileContainer.classList.remove('hidden');
                    urlContainer.classList.add('hidden');
                    urlInput.removeAttribute('required');
                    urlInput.value = '';
                    fileInput.setAttribute('required', 'required');
                    
                    durationInput.setAttribute('readonly', 'readonly');
                    durationInput.classList.add('bg-stone-900', 'text-stone-400', 'cursor-not-allowed');
                    durationInput.classList.remove('bg-retro-input', 'text-white', 'focus:border-retro-red');
                }
            }

            window.toggleEditAudioInput = function(type) {
                const fileContainer = document.getElementById('edit-audio-file-container');
                const urlContainer = document.getElementById('edit-audio-url-container');
                const fileInput = document.getElementById('edit_audio_file');
                const urlInput = document.getElementById('edit_audio_url');
                
                if (type === 'url') {
                    fileContainer.classList.add('hidden');
                    urlContainer.classList.remove('hidden');
                    fileInput.value = '';
                } else {
                    fileContainer.classList.remove('hidden');
                    urlContainer.classList.add('hidden');
                    urlInput.value = '';
                }
            }

            // Audio Duration Detection (Add and Edit Forms)
            const audioFileInput = document.getElementById('audio_file');
            const audioUrlInput = document.getElementById('audio_url');
            const durationInput = document.getElementById('duration');
            
            if (audioFileInput && durationInput) {
                audioFileInput.addEventListener('change', function(e) {
                    if (e.target.files && e.target.files[0]) {
                        const file = e.target.files[0];
                        const audioObj = new Audio();
                        const objectUrl = URL.createObjectURL(file);
                        
                        audioObj.addEventListener('loadedmetadata', function() {
                            const durationSeconds = audioObj.duration;
                            if (durationSeconds) {
                                const minutes = Math.floor(durationSeconds / 60);
                                const seconds = Math.floor(durationSeconds % 60);
                                const formattedDuration = 
                                    (minutes < 10 ? '0' + minutes : minutes) + ':' + 
                                    (seconds < 10 ? '0' + seconds : seconds);
                                durationInput.value = formattedDuration;
                            }
                            URL.revokeObjectURL(objectUrl);
                        });
                        
                        audioObj.addEventListener('error', function() {
                            alert('Gagal mendeteksi durasi audio. Pastikan file audio valid.');
                            durationInput.value = '';
                            URL.revokeObjectURL(objectUrl);
                        });
                        
                        audioObj.src = objectUrl;
                    }
                });
            }

            if (audioUrlInput && durationInput) {
                audioUrlInput.addEventListener('change', function() {
                    const url = audioUrlInput.value.trim();
                    if (url) {
                        durationInput.value = 'Mendeteksi...';
                        
                        const audioObj = new Audio();
                        audioObj.addEventListener('loadedmetadata', function() {
                            const durationSeconds = audioObj.duration;
                            if (durationSeconds) {
                                const minutes = Math.floor(durationSeconds / 60);
                                const seconds = Math.floor(durationSeconds % 60);
                                const formattedDuration = 
                                    (minutes < 10 ? '0' + minutes : minutes) + ':' + 
                                    (seconds < 10 ? '0' + seconds : seconds);
                                durationInput.value = formattedDuration;
                                
                                durationInput.setAttribute('readonly', 'readonly');
                                durationInput.classList.add('bg-stone-900', 'text-stone-400', 'cursor-not-allowed');
                                durationInput.classList.remove('bg-retro-input', 'text-white', 'focus:border-retro-red');
                            }
                        });
                        
                        audioObj.addEventListener('error', function() {
                            alert('Gagal mendeteksi durasi audio secara otomatis (kemungkinan karena CORS restriction dari provider/CDN link Anda). Harap masukkan durasi secara manual.');
                            durationInput.value = '';
                            
                            durationInput.removeAttribute('readonly');
                            durationInput.classList.remove('bg-stone-900', 'text-stone-400', 'cursor-not-allowed');
                            durationInput.classList.add('bg-retro-input', 'text-white', 'focus:border-retro-red');
                            durationInput.focus();
                        });
                        
                        audioObj.src = url;
                    }
                });
            }

            const editAudioFileInput = document.getElementById('edit_audio_file');
            const editAudioUrlInput = document.getElementById('edit_audio_url');
            const editDurationInput = document.getElementById('edit-comp-duration');
            
            if (editAudioFileInput && editDurationInput) {
                editAudioFileInput.addEventListener('change', function(e) {
                    if (e.target.files && e.target.files[0]) {
                        const file = e.target.files[0];
                        const audioObj = new Audio();
                        const objectUrl = URL.createObjectURL(file);
                        
                        audioObj.addEventListener('loadedmetadata', function() {
                            const durationSeconds = audioObj.duration;
                            if (durationSeconds) {
                                const minutes = Math.floor(durationSeconds / 60);
                                const seconds = Math.floor(durationSeconds % 60);
                                const formattedDuration = 
                                    (minutes < 10 ? '0' + minutes : minutes) + ':' + 
                                    (seconds < 10 ? '0' + seconds : seconds);
                                editDurationInput.value = formattedDuration;
                                
                                editDurationInput.setAttribute('readonly', 'readonly');
                                editDurationInput.classList.add('bg-stone-900', 'text-stone-400', 'cursor-not-allowed');
                                editDurationInput.classList.remove('bg-retro-input', 'text-white', 'focus:border-retro-red');
                            }
                            URL.revokeObjectURL(objectUrl);
                        });
                        
                        audioObj.addEventListener('error', function() {
                            alert('Gagal mendeteksi durasi audio. Pastikan file audio valid.');
                            editDurationInput.value = '';
                            URL.revokeObjectURL(objectUrl);
                        });
                        
                        audioObj.src = objectUrl;
                    }
                });
            }
            
            if (editAudioUrlInput && editDurationInput) {
                editAudioUrlInput.addEventListener('change', function() {
                    const url = editAudioUrlInput.value.trim();
                    if (url) {
                        editDurationInput.value = 'Mendeteksi...';
                        
                        const audioObj = new Audio();
                        audioObj.addEventListener('loadedmetadata', function() {
                            const durationSeconds = audioObj.duration;
                            if (durationSeconds) {
                                const minutes = Math.floor(durationSeconds / 60);
                                const seconds = Math.floor(durationSeconds % 60);
                                const formattedDuration = 
                                    (minutes < 10 ? '0' + minutes : minutes) + ':' + 
                                    (seconds < 10 ? '0' + seconds : seconds);
                                editDurationInput.value = formattedDuration;
                                
                                editDurationInput.setAttribute('readonly', 'readonly');
                                editDurationInput.classList.add('bg-stone-900', 'text-stone-400', 'cursor-not-allowed');
                                editDurationInput.classList.remove('bg-retro-input', 'text-white', 'focus:border-retro-red');
                            }
                        });
                        
                        audioObj.addEventListener('error', function() {
                            alert('Gagal mendeteksi durasi audio secara otomatis (kemungkinan karena CORS restriction dari provider/CDN link Anda). Harap masukkan durasi secara manual.');
                            editDurationInput.value = '';
                            
                            editDurationInput.removeAttribute('readonly');
                            editDurationInput.classList.remove('bg-stone-900', 'text-stone-400', 'cursor-not-allowed');
                            editDurationInput.classList.add('bg-retro-input', 'text-white', 'focus:border-retro-red');
                            editDurationInput.focus();
                        });
                        
                        audioObj.src = url;
                    }
                });
            }

            // News Modal hooks and actions
            window.openEditNewsModal = function(id, judul, konten, template, gambar) {
                document.getElementById('edit-news-id').value = id;
                document.getElementById('edit-news-judul').value = judul;
                document.getElementById('edit-rich-editor').innerHTML = konten;
                document.getElementById('edit-news-template').value = template;
                
                const existingImgContainer = document.getElementById('edit-news-existing-img-container');
                const existingImg = document.getElementById('edit-news-existing-img');
                if (gambar) {
                    existingImg.src = gambar;
                    existingImgContainer.classList.remove('hidden');
                } else {
                    existingImgContainer.classList.add('hidden');
                    existingImg.src = '';
                }
                
                document.getElementById('edit_cropped_news_img').value = '';
                document.getElementById('edit_gambar').value = '';
                document.getElementById('edit-news-crop-preview-container').classList.add('hidden');
                
                const modal = document.getElementById('edit-news-modal');
                modal.classList.remove('hidden');
                modal.classList.add('flex');
            }

            const newsModalClose = document.getElementById('news-modal-close');
            if (newsModalClose) {
                newsModalClose.addEventListener('click', function() {
                    const modal = document.getElementById('edit-news-modal');
                    modal.classList.add('hidden');
                    modal.classList.remove('flex');
                });
            }
            
            const editNewsModal = document.getElementById('edit-news-modal');
            if (editNewsModal) {
                editNewsModal.addEventListener('click', function(e) {
                    if (e.target === editNewsModal) {
                        editNewsModal.classList.add('hidden');
                        editNewsModal.classList.remove('flex');
                    }
                });
            }

            const editNewsForm = document.getElementById('edit-news-form');
            if (editNewsForm) {
                const editRichEditor = document.getElementById('edit-rich-editor');
                const editKontenInput = document.getElementById('edit-news-konten-input');
                
                editNewsForm.addEventListener('submit', function(e) {
                    editKontenInput.value = editRichEditor.innerHTML.trim();
                    const plainText = editRichEditor.innerText.trim();
                    if (plainText === '') {
                        alert('Konten berita tidak boleh kosong!');
                        e.preventDefault();
                        return false;
                    }
                });
            }

            // Composition Modal hooks and actions
            window.openEditKomposisiModal = function(comp) {
                document.getElementById('edit-comp-id').value = comp.id;
                document.getElementById('edit-comp-title').value = comp.title;
                document.getElementById('edit-comp-artist').value = comp.artist;
                document.getElementById('edit-comp-duration').value = comp.duration;
                
                const durationInput = document.getElementById('edit-comp-duration');
                durationInput.setAttribute('readonly', 'readonly');
                durationInput.classList.add('bg-stone-900', 'text-stone-400', 'cursor-not-allowed');
                durationInput.classList.remove('bg-retro-input', 'text-white', 'focus:border-retro-red');
                
                document.getElementById('edit-comp-audio-path').textContent = comp.src;
                
                const isUrl = comp.src.startsWith('http://') || comp.src.startsWith('https://');
                if (isUrl) {
                    document.getElementById('edit-audio-type-url').checked = true;
                    toggleEditAudioInput('url');
                    document.getElementById('edit_audio_url').value = comp.src;
                } else {
                    document.getElementById('edit-audio-type-file').checked = true;
                    toggleEditAudioInput('file');
                    document.getElementById('edit_audio_file').value = '';
                }
                
                const existingCoverContainer = document.getElementById('edit-comp-existing-cover-container');
                const existingCover = document.getElementById('edit-comp-existing-cover');
                if (comp.cover) {
                    existingCover.src = comp.cover;
                    existingCoverContainer.classList.remove('hidden');
                } else {
                    existingCoverContainer.classList.add('hidden');
                    existingCover.src = '';
                }
                
                document.getElementById('edit-comp-yt-url').value = comp.youtube_url || '';
                document.getElementById('edit-comp-sc-url').value = comp.soundcloud_url || '';
                document.getElementById('edit-comp-sp-url').value = comp.spotify_url || '';
                document.getElementById('edit-comp-lyrics').value = comp.lyrics || '';
                
                document.getElementById('edit_cropped_cover_img').value = '';
                document.getElementById('edit_cover_file').value = '';
                document.getElementById('edit-cover-crop-preview-container').classList.add('hidden');
                
                const modal = document.getElementById('edit-komposisi-modal');
                modal.classList.remove('hidden');
                modal.classList.add('flex');
            }

            const compModalClose = document.getElementById('komposisi-modal-close');
            if (compModalClose) {
                compModalClose.addEventListener('click', function() {
                    const modal = document.getElementById('edit-komposisi-modal');
                    modal.classList.add('hidden');
                    modal.classList.remove('flex');
                });
            }
            
            const editKomposisiModal = document.getElementById('edit-komposisi-modal');
            if (editKomposisiModal) {
                editKomposisiModal.addEventListener('click', function(e) {
                    if (e.target === editKomposisiModal) {
                        editKomposisiModal.classList.add('hidden');
                        editKomposisiModal.classList.remove('flex');
                    }
                });
            }

            // Upload Overlay Loader Logic
            const overlay = document.getElementById('upload-overlay');
            document.querySelectorAll('form').forEach(form => {
                if (form.getAttribute('enctype') === 'multipart/form-data') {
                    form.addEventListener('submit', function(e) {
                        // Prevent overlay if validation fails
                        if (form.checkValidity()) {
                            setTimeout(() => {
                                overlay.classList.remove('hidden');
                                overlay.classList.add('flex');
                            }, 50);
                        }
                    });
                }
            });
        });
    </script>

    <!-- Glassmorphic Modal: Edit News -->
    <div class="modal-overlay fixed inset-0 z-50 bg-black/80 backdrop-blur-sm hidden items-center justify-center p-4" id="edit-news-modal">
        <div class="bg-retro-card border border-stone-800 max-w-xl w-full rounded-2xl p-6 md:p-8 relative shadow-2xl flex flex-col max-h-[90vh]">
            <span class="absolute top-4 right-5 text-2xl text-retro-muted hover:text-white cursor-pointer transition-colors" id="news-modal-close">&times;</span>
            <h3 class="font-heading text-2xl font-bold text-white mb-4 tracking-wide">Edit Berita / Pengumuman</h3>
            
            <form id="edit-news-form" action="admin_process" method="POST" enctype="multipart/form-data" class="space-y-4 overflow-y-auto pr-2 playlist-container flex-grow">
                <input type="hidden" name="action" value="edit_berita">
                <input type="hidden" name="id" id="edit-news-id">
                
                <div class="space-y-1.5">
                    <label for="edit-news-judul" class="block text-xs font-semibold uppercase tracking-wider text-retro-muted font-medium">Judul Pengumuman</label>
                    <input type="text" name="judul" id="edit-news-judul" required
                           class="w-full px-4 py-2.5 bg-retro-input border border-stone-800 rounded-lg text-white focus:outline-none focus:border-retro-red">
                </div>
                
                <div class="space-y-1.5">
                    <label class="block text-xs font-semibold uppercase tracking-wider text-retro-muted font-medium">Konten / Isi Pengumuman</label>
                    <!-- Toolbar -->
                    <div class="flex flex-wrap gap-1 bg-stone-900 border border-stone-800 border-b-0 rounded-t-lg p-2 text-xs">
                        <button type="button" onclick="formatDoc('bold')" class="px-2.5 py-1 bg-stone-850 hover:bg-retro-red/20 text-white rounded font-bold border border-white/5">B</button>
                        <button type="button" onclick="formatDoc('italic')" class="px-2.5 py-1 bg-stone-850 hover:bg-retro-red/20 text-white rounded italic border border-white/5">I</button>
                        <button type="button" onclick="formatDoc('underline')" class="px-2.5 py-1 bg-stone-850 hover:bg-retro-red/20 text-white rounded underline border border-white/5">U</button>
                        <button type="button" onclick="formatDoc('removeFormat')" class="px-2.5 py-1 bg-stone-850 hover:bg-retro-red/20 text-white rounded border border-white/5">Clean</button>
                    </div>
                    <!-- Editor -->
                    <div id="edit-rich-editor" contenteditable="true"
                         class="w-full min-h-[120px] max-h-[200px] overflow-y-auto px-4 py-3 bg-retro-input border border-stone-800 rounded-b-lg text-white focus:outline-none focus:border-retro-red text-sm leading-relaxed whitespace-pre-wrap"></div>
                    <input type="hidden" name="konten" id="edit-news-konten-input">
                </div>
                
                <div class="space-y-1.5">
                    <label for="edit-news-template" class="block text-xs font-semibold uppercase tracking-wider text-retro-muted font-medium">Template Tampilan</label>
                    <select name="template" id="edit-news-template" required 
                            class="w-full px-4 py-2.5 bg-retro-input border border-stone-800 rounded-lg text-white focus:outline-none focus:border-retro-red">
                        <option value="classic">Classic (Serif, Cozy Gold Decor)</option>
                        <option value="retro">Retro (Maroon gradient, Typewriter)</option>
                        <option value="concert">Concert (Neon Concert Glow, Bold Header)</option>
                    </select>
                </div>
                
                <div class="space-y-1.5">
                    <label for="edit_gambar" class="block text-xs font-semibold uppercase tracking-wider text-retro-muted font-medium">Ubah Gambar (Opsional)</label>
                    <input type="file" name="gambar" id="edit_gambar" accept="image/*" 
                           class="w-full text-xs text-retro-muted file:mr-4 file:py-2 file:px-4 file:rounded-md file:border-0 file:text-xs file:font-semibold file:bg-stone-900 file:text-retro-light hover:file:bg-stone-800 transition-all cursor-pointer">
                    
                    <!-- Pre-existing Image Info -->
                    <div id="edit-news-existing-img-container" class="mt-2 text-xs text-retro-muted flex items-center gap-3">
                        <span>Gambar saat ini:</span>
                        <img id="edit-news-existing-img" class="w-12 h-12 object-cover rounded border border-stone-800" src="">
                    </div>
                    
                    <!-- Cropped Preview -->
                    <div id="edit-news-crop-preview-container" class="hidden mt-3 flex items-center gap-4 p-3 bg-stone-900/50 border border-stone-850 rounded-lg">
                        <img id="edit-news-crop-preview" class="w-24 aspect-[16/9] object-cover rounded border border-stone-800" src="">
                        <div class="text-xs">
                            <p class="text-emerald-400 font-semibold">Gambar baru telah disesuaikan (16:9)</p>
                            <button type="button" id="edit-news-crop-reset" class="text-retro-red hover:underline mt-1 block font-semibold uppercase tracking-wider text-[10px]">Reset Potongan</button>
                        </div>
                    </div>
                    <input type="hidden" name="cropped_news_img" id="edit_cropped_news_img">
                </div>
                
                <button type="submit" class="w-full py-3 bg-retro-red hover:bg-retro-redAccent text-white font-bold uppercase tracking-wider text-xs rounded-lg transition-colors duration-300 mt-2">
                    Simpan Perubahan
                </button>
            </form>
        </div>
    </div>

    <!-- Glassmorphic Modal: Edit Composition -->
    <div class="modal-overlay fixed inset-0 z-50 bg-black/80 backdrop-blur-sm hidden items-center justify-center p-4" id="edit-komposisi-modal">
        <div class="bg-retro-card border border-stone-800 max-w-xl w-full rounded-2xl p-6 md:p-8 relative shadow-2xl flex flex-col max-h-[90vh]">
            <span class="absolute top-4 right-5 text-2xl text-retro-muted hover:text-white cursor-pointer transition-colors" id="komposisi-modal-close">&times;</span>
            <h3 class="font-heading text-2xl font-bold text-white mb-4 tracking-wide">Edit Lagu / Komposisi</h3>
            
            <form id="edit-komposisi-form" action="admin_process" method="POST" enctype="multipart/form-data" class="space-y-4 overflow-y-auto pr-2 playlist-container flex-grow">
                <input type="hidden" name="action" value="edit_komposisi">
                <input type="hidden" name="id" id="edit-comp-id">
                
                <div class="space-y-1.5">
                    <label for="edit-comp-title" class="block text-xs font-semibold uppercase tracking-wider text-retro-muted font-medium">Judul Lagu</label>
                    <input type="text" name="title" id="edit-comp-title" required
                           class="w-full px-4 py-2.5 bg-retro-input border border-stone-800 rounded-lg text-white focus:outline-none focus:border-retro-red">
                </div>
                
                <div class="space-y-1.5">
                    <label for="edit-comp-artist" class="block text-xs font-semibold uppercase tracking-wider text-retro-muted font-medium">Artis / Komposer</label>
                    <input type="text" name="artist" id="edit-comp-artist" required
                           class="w-full px-4 py-2.5 bg-retro-input border border-stone-800 rounded-lg text-white focus:outline-none focus:border-retro-red">
                </div>
                
                <!-- Audio Source Options for Edit -->
                <div class="space-y-1.5">
                    <label class="block text-xs font-semibold uppercase tracking-wider text-retro-muted font-medium">Sumber Audio</label>
                    <div class="flex gap-4 mb-2">
                        <label class="flex items-center gap-2 text-xs text-retro-light cursor-pointer"><input type="radio" name="edit_audio_source_type" id="edit-audio-type-file" value="file" class="accent-retro-red" onclick="toggleEditAudioInput('file')"> File Baru</label>
                        <label class="flex items-center gap-2 text-xs text-retro-light cursor-pointer"><input type="radio" name="edit_audio_source_type" id="edit-audio-type-url" value="url" class="accent-retro-red" onclick="toggleEditAudioInput('url')"> Link URL / CDN</label>
                    </div>
                    
                    <div id="edit-audio-file-container" class="space-y-1.5">
                        <label for="edit_audio_file" class="block text-xs text-stone-500 font-medium">Pilih File MP3 Baru (Biarkan kosong jika tidak ingin mengubah audio saat ini)</label>
                        <input type="file" name="audio_file" id="edit_audio_file" accept="audio/mpeg, audio/mp3"
                               class="w-full text-xs text-retro-muted file:mr-4 file:py-2 file:px-4 file:rounded-md file:border-0 file:text-xs file:font-semibold file:bg-stone-900 file:text-retro-light hover:file:bg-stone-800 transition-all cursor-pointer">
                    </div>
                    
                    <div id="edit-audio-url-container" class="space-y-1.5 hidden">
                        <label for="edit_audio_url" class="block text-xs text-stone-500 font-medium">Link URL Audio MP3</label>
                        <input type="url" name="audio_url" id="edit_audio_url" placeholder="Contoh: https://cdn1.suno.ai/xxx.mp3"
                               class="w-full px-4 py-2.5 bg-retro-input border border-stone-800 rounded-lg text-white focus:outline-none focus:border-retro-red">
                    </div>
                    
                    <div id="edit-comp-existing-audio" class="text-[10px] text-retro-muted mt-1 truncate">
                        Audio saat ini: <span id="edit-comp-audio-path" class="font-mono text-emerald-400"></span>
                    </div>
                </div>
                
                <div class="space-y-1.5">
                    <label for="edit-comp-duration" class="block text-xs font-semibold uppercase tracking-wider text-retro-muted font-medium">Durasi Lagu (Otomatis / Manual)</label>
                    <input type="text" name="duration" id="edit-comp-duration" required readonly
                           class="w-full px-4 py-2.5 bg-stone-900 border border-stone-850 rounded-lg text-stone-400 focus:outline-none font-mono cursor-not-allowed">
                </div>
                
                <div class="space-y-1.5">
                    <label for="edit_cover_file" class="block text-xs font-semibold uppercase tracking-wider text-retro-muted font-medium">Ubah Cover Art (Opsional)</label>
                    <input type="file" name="cover_file" id="edit_cover_file" accept="image/*" 
                           class="w-full text-xs text-retro-muted file:mr-4 file:py-2 file:px-4 file:rounded-md file:border-0 file:text-xs file:font-semibold file:bg-stone-900 file:text-retro-light hover:file:bg-stone-800 transition-all cursor-pointer">
                    
                    <!-- Pre-existing Cover Info -->
                    <div id="edit-comp-existing-cover-container" class="mt-2 text-xs text-retro-muted flex items-center gap-3">
                        <span>Cover saat ini:</span>
                        <img id="edit-comp-existing-cover" class="w-12 h-12 object-cover rounded border border-stone-800" src="">
                    </div>
                    
                    <!-- Cropped Preview -->
                    <div id="edit-cover-crop-preview-container" class="hidden mt-3 flex items-center gap-4 p-3 bg-stone-900/50 border border-stone-850 rounded-lg">
                        <img id="edit-cover-crop-preview" class="w-16 aspect-square object-cover rounded border border-stone-800" src="">
                        <div class="text-xs">
                            <p class="text-emerald-400 font-semibold">Cover baru telah disesuaikan (1:1)</p>
                            <button type="button" id="edit-cover-crop-reset" class="text-retro-red hover:underline mt-1 block font-semibold uppercase tracking-wider text-[10px]">Reset Potongan</button>
                        </div>
                    </div>
                    <input type="hidden" name="cropped_cover_img" id="edit_cropped_cover_img">
                </div>
                
                <div class="space-y-1.5">
                    <label for="edit-comp-yt-url" class="block text-xs font-semibold uppercase tracking-wider text-retro-muted font-medium">Link YouTube (Opsional)</label>
                    <input type="url" name="youtube_url" id="edit-comp-yt-url"
                           class="w-full px-4 py-2.5 bg-retro-input border border-stone-800 rounded-lg text-white focus:outline-none focus:border-retro-red">
                </div>
                
                <div class="space-y-1.5">
                    <label for="edit-comp-sc-url" class="block text-xs font-semibold uppercase tracking-wider text-retro-muted font-medium">Link SoundCloud (Opsional)</label>
                    <input type="url" name="soundcloud_url" id="edit-comp-sc-url"
                           class="w-full px-4 py-2.5 bg-retro-input border border-stone-800 rounded-lg text-white focus:outline-none focus:border-retro-red">
                </div>
                
                <div class="space-y-1.5">
                    <label for="edit-comp-sp-url" class="block text-xs font-semibold uppercase tracking-wider text-retro-muted font-medium">Link Spotify (Opsional)</label>
                    <input type="url" name="spotify_url" id="edit-comp-sp-url"
                           class="w-full px-4 py-2.5 bg-retro-input border border-stone-800 rounded-lg text-white focus:outline-none focus:border-retro-red">
                </div>
                
                <div class="space-y-1.5">
                    <label for="edit-comp-lyrics" class="block text-xs font-semibold uppercase tracking-wider text-retro-muted font-medium">Lirik Lagu (Opsional)</label>
                    <textarea name="lyrics" id="edit-comp-lyrics" rows="4"
                              class="w-full px-4 py-2.5 bg-retro-input border border-stone-800 rounded-lg text-white focus:outline-none focus:border-retro-red resize-y text-xs leading-relaxed"></textarea>
                </div>
                
                <button type="submit" class="w-full py-3 bg-retro-red hover:bg-retro-redAccent text-white font-bold uppercase tracking-wider text-xs rounded-lg transition-colors duration-300 mt-2">
                    Simpan Perubahan
                </button>
            </form>
        </div>
    </div>

    <!-- Glassmorphic Modal: Image Cropper -->
    <div id="cropper-modal" class="modal-overlay fixed inset-0 z-50 bg-black/90 backdrop-blur-sm hidden flex items-center justify-center p-4">
        <div class="bg-retro-card border border-stone-800 max-w-2xl w-full rounded-2xl p-6 relative shadow-2xl flex flex-col max-h-[90vh]">
            <span class="absolute top-4 right-5 text-2xl text-retro-muted hover:text-white cursor-pointer transition-colors" id="cropper-modal-close">&times;</span>
            <h3 class="font-heading text-2xl font-bold text-white mb-4 tracking-wide">Sesuaikan Gambar</h3>
            
            <div class="flex-grow overflow-hidden bg-black/40 rounded-xl border border-stone-850 p-2 flex items-center justify-center min-h-[300px] max-h-[55vh]">
                <img id="cropper-image" src="" alt="Source Image" class="max-w-full max-h-full block">
            </div>
            
            <div class="flex justify-end gap-3 mt-6">
                <button type="button" id="cropper-cancel-btn" class="px-5 py-2.5 bg-stone-900 border border-white/10 hover:bg-stone-800 text-white rounded-lg text-xs font-semibold uppercase tracking-wider transition-colors duration-300">
                    Batal
                </button>
                <button type="button" id="cropper-save-btn" class="px-5 py-2.5 bg-retro-red hover:bg-retro-redAccent text-white rounded-lg text-xs font-semibold uppercase tracking-wider transition-colors duration-300">
                    Potong & Simpan
                </button>
            </div>
        </div>
    </div>

    <!-- Uploading Overlay Modal -->
    <div id="upload-overlay" class="fixed inset-0 z-50 bg-black/80 backdrop-blur-md hidden flex-col items-center justify-center p-6 text-center">
        <!-- Premium Spinner -->
        <div class="w-16 h-16 border-4 border-retro-red/20 border-t-retro-red rounded-full animate-spin mb-6"></div>
        <h3 class="font-heading text-2xl font-bold text-white mb-2">Mengunggah File ke Server...</h3>
        <p class="text-retro-muted text-xs md:text-sm max-w-sm font-light leading-relaxed">
            Harap tunggu dan jangan menutup halaman ini. Mengunggah file besar (seperti MP3) dapat memakan waktu beberapa menit tergantung koneksi internet Anda.
        </p>
    </div>
</body>
</html>
