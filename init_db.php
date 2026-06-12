<?php
/**
 * DATABASE INITIALIZER & SEEDER (UPDATED)
 * ---------------------------------------
 * File ini membantu Anda me-migrasi database MySQL secara otomatis.
 * Cukup buka file ini di browser Anda untuk menerapkan perubahan database terbaru.
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

include 'koneksi.php';

$steps = [];

if ($conn->connect_error) {
    $steps[] = ["status" => "error", "message" => "Gagal terhubung ke MySQL Server: " . $conn->connect_error];
} else {
    $steps[] = ["status" => "success", "message" => "Berhasil terhubung ke database server MySQL."];
    
    // Pastikan database terpilih
    $db_selected_init = $conn->select_db($db_name);
    if (!$db_selected_init) {
        // Coba buat database (terutama berguna untuk lokal XAMPP)
        if ($conn->query("CREATE DATABASE IF NOT EXISTS `$db_name`")) {
            $db_selected_init = $conn->select_db($db_name);
            if ($db_selected_init) {
                $steps[] = ["status" => "success", "message" => "Berhasil membuat dan memilih database `$db_name`."];
            }
        }
    } else {
        $steps[] = ["status" => "success", "message" => "Berhasil memilih database `$db_name`."];
    }
    
    if ($db_selected_init) {
        
        // 0. BUAT TABEL `jadwal` JIKA BELUM ADA
        $query_create_jadwal = "CREATE TABLE IF NOT EXISTS `jadwal` (
            `id` INT AUTO_INCREMENT PRIMARY KEY,
            `hari` VARCHAR(20) NOT NULL,
            `tanggal` DATE NOT NULL,
            `jam` VARCHAR(50) NOT NULL,
            `kuota` INT DEFAULT 50,
            `terjual` INT DEFAULT 0,
            `nama_event` VARCHAR(100) DEFAULT 'Jazz Night Show',
            `status` VARCHAR(20) DEFAULT 'Open',
            `special_notes` TEXT NULL,
            `is_special` TINYINT DEFAULT 0
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;";
        
        if ($conn->query($query_create_jadwal)) {
            $steps[] = ["status" => "success", "message" => "Tabel `jadwal` berhasil diinisialisasi (dibuat jika belum ada)."];
        } else {
            $steps[] = ["status" => "error", "message" => "Gagal menginisialisasi tabel `jadwal`: " . $conn->error];
        }
        
        // 1. UPDATE TABEL `jadwal` (Tambah kolom baru jika belum ada)
        // Mari kita lakukan pengecekan kolom secara aman dengan query.
        $columns_res = $conn->query("SHOW COLUMNS FROM `jadwal`");
        $existing_cols = [];
        while($c = $columns_res->fetch_assoc()) {
            $existing_cols[] = $c['Field'];
        }
        
        if (!in_array('nama_event', $existing_cols)) {
            if ($conn->query("ALTER TABLE `jadwal` ADD `nama_event` VARCHAR(100) DEFAULT 'Jazz Night Show' AFTER `terjual`")) {
                $steps[] = ["status" => "success", "message" => "Kolom `nama_event` berhasil ditambahkan ke tabel `jadwal`."];
            } else {
                $steps[] = ["status" => "error", "message" => "Gagal menambahkan kolom `nama_event`: " . $conn->error];
            }
        }
        
        if (!in_array('status', $existing_cols)) {
            if ($conn->query("ALTER TABLE `jadwal` ADD `status` VARCHAR(20) DEFAULT 'Open' AFTER `nama_event`")) {
                $steps[] = ["status" => "success", "message" => "Kolom `status` berhasil ditambahkan ke tabel `jadwal`."];
            } else {
                $steps[] = ["status" => "error", "message" => "Gagal menambahkan kolom `status`: " . $conn->error];
            }
        }
        
        if (!in_array('special_notes', $existing_cols)) {
            if ($conn->query("ALTER TABLE `jadwal` ADD `special_notes` TEXT NULL AFTER `status`")) {
                $steps[] = ["status" => "success", "message" => "Kolom `special_notes` berhasil ditambahkan ke tabel `jadwal`."];
            } else {
                $steps[] = ["status" => "error", "message" => "Gagal menambahkan kolom `special_notes`: " . $conn->error];
            }
        }
        
        if (!in_array('is_special', $existing_cols)) {
            if ($conn->query("ALTER TABLE `jadwal` ADD `is_special` TINYINT DEFAULT 0 AFTER `special_notes`")) {
                $steps[] = ["status" => "success", "message" => "Kolom `is_special` berhasil ditambahkan ke tabel `jadwal`."];
            } else {
                $steps[] = ["status" => "error", "message" => "Gagal menambahkan kolom `is_special`: " . $conn->error];
            }
        }

        // 2. BUAT TABEL `berita` (NEW)
        $query_berita = "CREATE TABLE IF NOT EXISTS `berita` (
            `id` INT AUTO_INCREMENT PRIMARY KEY,
            `judul` VARCHAR(255) NOT NULL,
            `konten` TEXT NOT NULL,
            `template` VARCHAR(50) DEFAULT 'classic',
            `file_path` VARCHAR(255) DEFAULT NULL,
            `tanggal_post` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;";
        
        if ($conn->query($query_berita)) {
            $steps[] = ["status" => "success", "message" => "Tabel `berita` berhasil dibuat atau sudah ada."];
            
            // Check and add template/file_path columns dynamically if they aren't there yet
            $columns_berita_res = $conn->query("SHOW COLUMNS FROM `berita`");
            $existing_berita_cols = [];
            while($c = $columns_berita_res->fetch_assoc()) {
                $existing_berita_cols[] = $c['Field'];
            }
            if (!in_array('template', $existing_berita_cols)) {
                if ($conn->query("ALTER TABLE `berita` ADD `template` VARCHAR(50) DEFAULT 'classic' AFTER `konten`")) {
                    $steps[] = ["status" => "success", "message" => "Kolom `template` berhasil ditambahkan ke tabel `berita`."];
                }
            }
            if (!in_array('file_path', $existing_berita_cols)) {
                if ($conn->query("ALTER TABLE `berita` ADD `file_path` VARCHAR(255) DEFAULT NULL AFTER `template`")) {
                    $steps[] = ["status" => "success", "message" => "Kolom `file_path` berhasil ditambahkan ke tabel `berita`."];
                }
            }
            if (!in_array('gambar', $existing_berita_cols)) {
                if ($conn->query("ALTER TABLE `berita` ADD `gambar` VARCHAR(255) DEFAULT NULL AFTER `file_path`")) {
                    $steps[] = ["status" => "success", "message" => "Kolom `gambar` berhasil ditambahkan ke tabel `berita`."];
                }
            }
            
            // Lakukan seeder untuk Berita fiktif jika masih kosong
            $check_berita = $conn->query("SELECT COUNT(*) as total FROM `berita`")->fetch_assoc();
            if ($check_berita['total'] == 0) {
                $seeder_berita = "INSERT INTO `berita` (`id`, `judul`, `konten`, `template`, `file_path`) VALUES
                    (1, 'The 4 Stairs Band 5th Anniversary Special Show', 'Dalam rangka merayakan hari jadi yang ke-5, The 4 Stairs Band akan mengadakan pertunjukan spesial yang intim sepanjang akhir pekan ini. Kami akan mengundang beberapa rekan musisi jazz indie ternama untuk ikut menyumbang melodi di panggung utama kami. Tiket sangat terbatas!', 'concert', 'articles/berita_1.php'),
                    (2, 'Malam Minggu Romantis: Tribute to Swing Era', 'Kembali ke era kejayaan swing bersama alunan brass section yang megah dan vokal syahdu. Acara ini akan menampilkan kolaborasi khusus antara komposer internal kami dengan bintang tamu saxophone terkenal. Reservasi tiket masuk Anda sekarang!', 'retro', 'articles/berita_2.php'),
                    (3, 'Info Protokol Kenyamanan Show', 'Demi menjaga atmosfer intim dan kenyamanan akustik di The 4 Stairs Music Hall, kami menghimbau para tamu untuk mengenakan pakaian Smart Casual. Pintu ruang pertunjukan akan dibuka 30 menit sebelum acara dimulai.', 'classic', 'articles/berita_3.php')";
                
                if ($conn->query($seeder_berita)) {
                    $steps[] = ["status" => "success", "message" => "Data berita awal berhasil dimasukkan (anniversary, tribute, protokol)."];
                    
                    // Hasilkan file fisik berita
                    if (!is_dir('articles')) {
                        mkdir('articles', 0777, true);
                    }
                    include_once 'warta_template.php';
                    
                    $seeded_articles = [
                        [
                            'judul' => 'The 4 Stairs Band 5th Anniversary Special Show',
                            'konten' => 'Dalam rangka merayakan hari jadi yang ke-5, The 4 Stairs Band akan mengadakan pertunjukan spesial yang intim sepanjang akhir pekan ini. Kami akan mengundang beberapa rekan musisi jazz indie ternama untuk ikut menyumbang melodi di panggung utama kami. Tiket sangat terbatas!',
                            'template' => 'concert',
                            'file' => 'articles/berita_1.php'
                        ],
                        [
                            'judul' => 'Malam Minggu Romantis: Tribute to Swing Era',
                            'konten' => 'Kembali ke era kejayaan swing bersama alunan brass section yang megah dan vokal syahdu. Acara ini akan menampilkan kolaborasi khusus antara komposer internal kami dengan bintang tamu saxophone terkenal. Reservasi tiket masuk Anda sekarang!',
                            'template' => 'retro',
                            'file' => 'articles/berita_2.php'
                        ],
                        [
                            'judul' => 'Info Protokol Kenyamanan Show',
                            'konten' => 'Demi menjaga atmosfer intim dan kenyamanan akustik di The 4 Stairs Music Hall, kami menghimbau para tamu untuk mengenakan pakaian Smart Casual. Pintu ruang pertunjukan akan dibuka 30 menit sebelum acara dimulai.',
                            'template' => 'classic',
                            'file' => 'articles/berita_3.php'
                        ]
                    ];
                    
                    foreach ($seeded_articles as $art) {
                        $html = generate_article_html($art['judul'], $art['konten'], date('Y-m-d H:i:s'), $art['template']);
                        file_put_contents($art['file'], $html);
                    }
                    $steps[] = ["status" => "success", "message" => "File artikel fisik berhasil dihasilkan di folder `articles/`."];
                } else {
                    $steps[] = ["status" => "error", "message" => "Gagal memasukkan data berita awal: " . $conn->error];
                }
            } else {
                // Jika data berita sudah ada, pastikan file fisik berita_*.php ada.
                if (!is_dir('articles')) {
                    mkdir('articles', 0777, true);
                }
                include_once 'warta_template.php';
                $all_berita_res = $conn->query("SELECT * FROM `berita`");
                if ($all_berita_res) {
                    while ($b = $all_berita_res->fetch_assoc()) {
                        $f_path = $b['file_path'];
                        if (empty($f_path) || strpos($f_path, 'warta/') === 0 || strpos($f_path, 'berita/') === 0) {
                            $f_path = 'articles/berita_' . $b['id'] . '.php';
                            $conn->query("UPDATE `berita` SET `file_path` = '" . $conn->real_escape_string($f_path) . "' WHERE `id` = " . $b['id']);
                        }
                        if (!file_exists(__DIR__ . '/' . $f_path)) {
                            $html = generate_article_html($b['judul'], $b['konten'], $b['tanggal_post'], $b['template'] ?? 'classic', $b['gambar']);
                            file_put_contents(__DIR__ . '/' . $f_path, $html);
                        }
                    }
                }
                $steps[] = ["status" => "success", "message" => "Pengecekan integritas file artikel fisik selesai."];
            }
        } else {
            $steps[] = ["status" => "error", "message" => "Gagal membuat tabel `berita`: " . $conn->error];
        }
        // 3. Pastikan seeder jadwal terupdate dengan title & info khusus
        $seeder_jadwal = "INSERT INTO `jadwal` (`id`, `hari`, `tanggal`, `jam`, `kuota`, `terjual`, `nama_event`, `status`, `special_notes`, `is_special`) VALUES
            (1, 'Senin', DATE_ADD(CURDATE(), INTERVAL 0 - WEEKDAY(CURDATE()) DAY), '19:00 - 22:00 WIB', 50, 0, 'Acoustic Monday Melodies', 'Open', 'Live Acoustic Performance', 0),
            (2, 'Selasa', DATE_ADD(CURDATE(), INTERVAL 1 - WEEKDAY(CURDATE()) DAY), '19:00 - 22:00 WIB', 50, 0, 'Jazz Fusion Session', 'Open', 'Fusion Brass Ensemble', 0),
            (3, 'Rabu', DATE_ADD(CURDATE(), INTERVAL 2 - WEEKDAY(CURDATE()) DAY), '19:00 - 22:00 WIB', 50, 0, 'Blues & Soul Midweek', 'Open', 'Classic Rhythm & Blues', 0),
            (4, 'Kamis', DATE_ADD(CURDATE(), INTERVAL 3 - WEEKDAY(CURDATE()) DAY), '19:00 - 22:00 WIB', 50, 0, 'Classical Ensemble Night', 'Open', 'Strings & Piano Duet', 0),
            (5, 'Jumat', DATE_ADD(CURDATE(), INTERVAL 4 - WEEKDAY(CURDATE()) DAY), '20:00 - 23:00 WIB', 50, 0, 'Midnight Swing & Saxophone Tribute', 'Open', 'Special Saxophone Soloist', 1),
            (6, 'Sabtu', DATE_ADD(CURDATE(), INTERVAL 5 - WEEKDAY(CURDATE()) DAY), '20:00 - 23:00 WIB', 50, 0, 'The 4 Stairs Anniversary Live', 'Open', 'Special Guest Star: Velvet Blue Jazz Trio!', 1),
            (7, 'Minggu', DATE_ADD(CURDATE(), INTERVAL 6 - WEEKDAY(CURDATE()) DAY), '19:00 - 22:00 WIB', 50, 0, 'Cozy Acoustic Sunday Jazz', 'Open', 'Relaxing Acoustic Melodies', 0)
            ON DUPLICATE KEY UPDATE
            `hari` = VALUES(`hari`),
            `tanggal` = VALUES(`tanggal`),
            `jam` = VALUES(`jam`),
            `nama_event` = VALUES(`nama_event`),
            `status` = VALUES(`status`),
            `special_notes` = VALUES(`special_notes`),
            `is_special` = VALUES(`is_special`);";
        if ($conn->query($seeder_jadwal)) {
            $steps[] = ["status" => "success", "message" => "Jadwal 7 hari (Minggu Ini) berhasil diinisialisasi/diperbarui."];
        } else {
            $steps[] = ["status" => "error", "message" => "Gagal menginisialisasi jadwal 7 hari: " . $conn->error];
        }

        // 4. Pastikan tabel pesanan & admin terbuat dengan aman
        // (Diulang demi konsistensi jika user menjalankan ini pada DB kosong)
        $conn->query("CREATE TABLE IF NOT EXISTS `pesanan` (
            `id_pesanan` VARCHAR(30) PRIMARY KEY,
            `id_jadwal` INT NOT NULL,
            `nama` VARCHAR(100) NOT NULL,
            `email` VARCHAR(100) NOT NULL,
            `no_wa` VARCHAR(20) NOT NULL,
            `jumlah_tiket` INT NOT NULL,
            `status_pembayaran` VARCHAR(30) DEFAULT 'Pending',
            `waktu_pesan` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (`id_jadwal`) REFERENCES `jadwal`(`id`) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");

        // Periksa dan ubah panjang kolom status_pembayaran jika masih VARCHAR(20)
        $columns_pesanan_res = $conn->query("SHOW COLUMNS FROM `pesanan`");
        if ($columns_pesanan_res) {
            $status_col_type = "";
            while($c = $columns_pesanan_res->fetch_assoc()) {
                if ($c['Field'] === 'status_pembayaran') {
                    $status_col_type = $c['Type'];
                }
            }
            if (strpos(strtolower($status_col_type), 'varchar(20)') !== false) {
                if ($conn->query("ALTER TABLE `pesanan` MODIFY `status_pembayaran` VARCHAR(30) DEFAULT 'Pending'")) {
                    $steps[] = ["status" => "success", "message" => "Kolom `status_pembayaran` pada tabel `pesanan` berhasil diubah menjadi VARCHAR(30)."];
                } else {
                    $steps[] = ["status" => "error", "message" => "Gagal mengubah kolom `status_pembayaran`: " . $conn->error];
                }
            }
        }

        $conn->query("CREATE TABLE IF NOT EXISTS `admin` (
            `id` INT AUTO_INCREMENT PRIMARY KEY,
            `username` VARCHAR(50) NOT NULL UNIQUE,
            `password` VARCHAR(255) NOT NULL
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");
        
        $check_admin = $conn->query("SELECT COUNT(*) as total FROM `admin`")->fetch_assoc();
        if ($check_admin['total'] == 0) {
            $hash_pass = password_hash("admin123", PASSWORD_DEFAULT);
            $conn->query("INSERT INTO `admin` (`username`, `password`) VALUES ('admin', '$hash_pass');");
            $steps[] = ["status" => "success", "message" => "User admin default berhasil dibuat! Username: <strong>admin</strong> | Password: <strong>admin123</strong>"];
        }

        // 5. Buat tabel `users` jika belum ada
        $conn->query("CREATE TABLE IF NOT EXISTS `users` (
            `id` INT AUTO_INCREMENT PRIMARY KEY,
            `name` VARCHAR(100) NOT NULL,
            `email` VARCHAR(100) UNIQUE NOT NULL,
            `password` VARCHAR(255) NOT NULL,
            `no_wa` VARCHAR(20) NOT NULL,
            `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");
        $steps[] = ["status" => "success", "message" => "Tabel `users` berhasil diinisialisasi."];

        // 6. Buat tabel `komposisi` jika belum ada
        $query_komposisi = "CREATE TABLE IF NOT EXISTS `komposisi` (
            `id` INT AUTO_INCREMENT PRIMARY KEY,
            `title` VARCHAR(255) NOT NULL,
            `artist` VARCHAR(255) NOT NULL,
            `src` VARCHAR(255) NOT NULL,
            `cover` VARCHAR(255) NOT NULL,
            `duration` VARCHAR(50) DEFAULT '03:00',
            `youtube_url` VARCHAR(255) DEFAULT NULL,
            `soundcloud_url` VARCHAR(255) DEFAULT NULL,
            `spotify_url` VARCHAR(255) DEFAULT NULL,
            `lyrics` TEXT DEFAULT NULL,
            `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;";
        
        if ($conn->query($query_komposisi)) {
            $steps[] = ["status" => "success", "message" => "Tabel `komposisi` berhasil diinisialisasi (dibuat jika belum ada)."];
            
            // Check and add new columns dynamically if they aren't there yet
            $columns_comp_res = $conn->query("SHOW COLUMNS FROM `komposisi`");
            $existing_comp_cols = [];
            if ($columns_comp_res) {
                while($c = $columns_comp_res->fetch_assoc()) {
                    $existing_comp_cols[] = $c['Field'];
                }
            }
            if (!in_array('youtube_url', $existing_comp_cols)) {
                if ($conn->query("ALTER TABLE `komposisi` ADD `youtube_url` VARCHAR(255) DEFAULT NULL AFTER `duration`")) {
                    $steps[] = ["status" => "success", "message" => "Kolom `youtube_url` berhasil ditambahkan ke tabel `komposisi`."];
                }
            }
            if (!in_array('soundcloud_url', $existing_comp_cols)) {
                if ($conn->query("ALTER TABLE `komposisi` ADD `soundcloud_url` VARCHAR(255) DEFAULT NULL AFTER `youtube_url`")) {
                    $steps[] = ["status" => "success", "message" => "Kolom `soundcloud_url` berhasil ditambahkan ke tabel `komposisi`."];
                }
            }
            if (!in_array('spotify_url', $existing_comp_cols)) {
                if ($conn->query("ALTER TABLE `komposisi` ADD `spotify_url` VARCHAR(255) DEFAULT NULL AFTER `soundcloud_url`")) {
                    $steps[] = ["status" => "success", "message" => "Kolom `spotify_url` berhasil ditambahkan ke tabel `komposisi`."];
                }
            }
            if (!in_array('lyrics', $existing_comp_cols)) {
                if ($conn->query("ALTER TABLE `komposisi` ADD `lyrics` TEXT DEFAULT NULL AFTER `spotify_url`")) {
                    $steps[] = ["status" => "success", "message" => "Kolom `lyrics` berhasil ditambahkan ke tabel `komposisi`."];
                }
            }
            
            // Seed default tracks if empty
            $check_komposisi = $conn->query("SELECT COUNT(*) as total FROM `komposisi`")->fetch_assoc();
            if ($check_komposisi['total'] == 0) {
                $seeder_komposisi = "INSERT INTO `komposisi` (`title`, `artist`, `src`, `cover`, `duration`) VALUES
                    ('Alennya', 'Redevelop', 'alennya.mp3', 'assets/img/alennya.png', '03:54'),
                    ('Dorogoi Dlinnoyu', 'Dessy Dobreva', 'dorogoi.mp3', 'assets/img/dorogoi.png', '03:31'),
                    ('Fly me to the moon', 'The Jazz Woman', 'flyme.mp3', 'assets/img/flyme.png', '03:06'),
                    ('Tri Belikh Konya (Три белых коня)', 'Kvarto', 'tribelikh.mp3', 'assets/img/tribelikh.png', '03:04'),
                    ('Kaze ga Fuite', 'Redevelop', 'kazega.mp3', 'assets/img/kazega.png', '02:43'),
                    ('Pesenka Frontovogo Shofyora', 'Timur Vedernikov', 'pesenka.mp3', 'assets/img/pesenka.png', '02:55')";
                
                if ($conn->query($seeder_komposisi)) {
                    $steps[] = ["status" => "success", "message" => "Data awal lagu (6 lagu) berhasil dimasukkan ke tabel `komposisi`."];
                } else {
                    $steps[] = ["status" => "error", "message" => "Gagal memasukkan data awal lagu: " . $conn->error];
                }
            }
        } else {
            $steps[] = ["status" => "error", "message" => "Gagal menginisialisasi tabel `komposisi`: " . $conn->error];
        }

    } else {
        $steps[] = ["status" => "error", "message" => "Koneksi terjalin, tetapi gagal memilih database `$db_name`."];
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Database Migration Setup - The 4 Stairs Music Hall</title>
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;600&family=Playfair+Display:ital,wght@0,600;1,400&display=swap" rel="stylesheet">
    <style>
        body {
            background-color: #090706;
            color: #f3ede2;
            font-family: 'Outfit', sans-serif;
            margin: 0;
            padding: 40px 20px;
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
            box-sizing: border-box;
        }
        .setup-container {
            background: #14110f;
            border: 1px solid #dfb15b;
            border-radius: 12px;
            padding: 40px;
            max-width: 600px;
            width: 100%;
            box-shadow: 0 10px 30px rgba(0,0,0,0.5), 0 0 40px rgba(223, 177, 91, 0.1);
        }
        h1 {
            font-family: 'Playfair Display', serif;
            color: #dfb15b;
            text-align: center;
            margin-top: 0;
            margin-bottom: 5px;
            letter-spacing: 2px;
            font-size: 2rem;
        }
        .subtitle {
            text-align: center;
            color: #a69886;
            margin-bottom: 30px;
            font-weight: 300;
        }
        .log-list {
            list-style: none;
            padding: 0;
            margin: 0 0 30px 0;
        }
        .log-item {
            padding: 12px 16px;
            border-radius: 6px;
            margin-bottom: 10px;
            font-size: 0.95rem;
            display: flex;
            align-items: flex-start;
            gap: 12px;
            line-height: 1.5;
        }
        .log-item.success {
            background: rgba(46, 204, 113, 0.08);
            border-left: 4px solid #2ecc71;
            color: #2ecc71;
        }
        .log-item.warning {
            background: rgba(241, 196, 15, 0.08);
            border-left: 4px solid #f1c40f;
            color: #f1c40f;
        }
        .log-item.error {
            background: rgba(231, 76, 60, 0.08);
            border-left: 4px solid #e74c3c;
            color: #e74c3c;
        }
        .badge {
            font-size: 0.75rem;
            text-transform: uppercase;
            padding: 2px 6px;
            border-radius: 4px;
            font-weight: 600;
            letter-spacing: 0.5px;
            margin-top: 2px;
            flex-shrink: 0;
        }
        .badge.success { background: #2ecc71; color: #000; }
        .badge.warning { background: #f1c40f; color: #000; }
        .badge.error { background: #e74c3c; color: #fff; }
        
        .action-box {
            text-align: center;
            border-top: 1px solid rgba(223, 177, 91, 0.2);
            padding-top: 25px;
            margin-top: 25px;
        }
        .btn {
            display: inline-block;
            background: linear-gradient(135deg, #dfb15b, #bfa045);
            color: #090706;
            text-decoration: none;
            padding: 14px 28px;
            border-radius: 6px;
            font-weight: 600;
            font-size: 1rem;
            letter-spacing: 1px;
            transition: all 0.3s ease;
            box-shadow: 0 4px 15px rgba(223, 177, 91, 0.2);
        }
        .btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(223, 177, 91, 0.4);
        }
    </style>
</head>
<body>
    <div class="setup-container">
        <h1>THE 4 STAIRS</h1>
        <div class="subtitle">Database Migration (Carousel & Special Events)</div>
        
        <ul class="log-list">
            <?php foreach ($steps as $step): ?>
                <li class="log-item <?php echo $step['status']; ?>">
                    <span class="badge <?php echo $step['status']; ?>">
                        <?php echo $step['status']; ?>
                    </span>
                    <div><?php echo $step['message']; ?></div>
                </li>
            <?php endforeach; ?>
        </ul>

        <div class="action-box">
            <p style="color: #2ecc71; margin-bottom: 20px; font-weight: 600;">Migrasi Struktur & Data Berhasil Diterapkan!</p>
            <a href="index" class="btn">Buka Halaman Utama</a>
        </div>
    </div>
</body>
</html>
