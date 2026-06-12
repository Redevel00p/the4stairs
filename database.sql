-- ==========================================================================
-- GRAND 5 STAIRS HALL - DATABASE MIGRATION SCRIPT (UPDATED)
-- ==========================================================================
-- Gunakan script SQL ini untuk mengimpor tabel secara manual di phpMyAdmin XAMPP
-- atau pada cPanel hosting InfinityFree Anda.
-- ==========================================================================

-- 1. MEMBUAT DATABASE (Hanya untuk Lokal XAMPP)
-- CREATE DATABASE IF NOT EXISTS `db_grand5stairs` DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;
-- USE `db_grand5stairs`;

-- HAPUS TABEL JIKA SUDAH ADA (Menghindari error kolom lama ketika re-import di phpMyAdmin)
-- Drop pesanan terlebih dahulu karena memiliki FOREIGN KEY ke tabel jadwal
DROP TABLE IF EXISTS `pesanan`;
DROP TABLE IF EXISTS `jadwal`;
DROP TABLE IF EXISTS `berita`;
DROP TABLE IF EXISTS `admin`;
DROP TABLE IF EXISTS `users`;

-- --------------------------------------------------------------------------
-- 2. STRUKTUR TABEL: `jadwal`
-- --------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `jadwal` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `hari` VARCHAR(20) NOT NULL,
  `tanggal` DATE NOT NULL,
  `jam` VARCHAR(50) NOT NULL,
  `kuota` INT DEFAULT 50,
  `terjual` INT DEFAULT 0,
  `nama_event` VARCHAR(100) DEFAULT 'Jazz Night Show',
  `status` VARCHAR(20) DEFAULT 'Open',
  `special_notes` TEXT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- MEMASUKKAN DATA JADWAL BARU DENGAN SPECIAL NOTES
INSERT INTO `jadwal` (`id`, `hari`, `tanggal`, `jam`, `kuota`, `terjual`, `nama_event`, `status`, `special_notes`) VALUES
(1, 'Senin', DATE_ADD(CURDATE(), INTERVAL 0 - WEEKDAY(CURDATE()) DAY), '19:00 - 22:00 WIB', 50, 0, 'Acoustic Monday Melodies', 'Open', 'Live Acoustic Performance'),
(2, 'Selasa', DATE_ADD(CURDATE(), INTERVAL 1 - WEEKDAY(CURDATE()) DAY), '19:00 - 22:00 WIB', 50, 0, 'Jazz Fusion Session', 'Open', 'Fusion Brass Ensemble'),
(3, 'Rabu', DATE_ADD(CURDATE(), INTERVAL 2 - WEEKDAY(CURDATE()) DAY), '19:00 - 22:00 WIB', 50, 0, 'Blues & Soul Midweek', 'Open', 'Classic Rhythm & Blues'),
(4, 'Kamis', DATE_ADD(CURDATE(), INTERVAL 3 - WEEKDAY(CURDATE()) DAY), '19:00 - 22:00 WIB', 50, 0, 'Classical Ensemble Night', 'Open', 'Strings & Piano Duet'),
(5, 'Jumat', DATE_ADD(CURDATE(), INTERVAL 4 - WEEKDAY(CURDATE()) DAY), '20:00 - 23:00 WIB', 50, 0, 'Midnight Swing & Saxophone Tribute', 'Open', 'Special Saxophone Soloist'),
(6, 'Sabtu', DATE_ADD(CURDATE(), INTERVAL 5 - WEEKDAY(CURDATE()) DAY), '20:00 - 23:00 WIB', 50, 0, 'The 4 Stairs Anniversary Live', 'Open', 'Special Guest Star: Velvet Blue Jazz Trio!'),
(7, 'Minggu', DATE_ADD(CURDATE(), INTERVAL 6 - WEEKDAY(CURDATE()) DAY), '19:00 - 22:00 WIB', 50, 0, 'Cozy Acoustic Sunday Jazz', 'Open', 'Relaxing Acoustic Melodies')
ON DUPLICATE KEY UPDATE 
  `hari` = VALUES(`hari`),
  `tanggal` = VALUES(`tanggal`),
  `jam` = VALUES(`jam`),
  `nama_event` = VALUES(`nama_event`),
  `status` = VALUES(`status`),
  `special_notes` = VALUES(`special_notes`);

-- --------------------------------------------------------------------------
-- 3. STRUKTUR TABEL: `berita` (NEW)
-- --------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `berita` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `judul` VARCHAR(255) NOT NULL,
  `konten` TEXT NOT NULL,
  `template` VARCHAR(50) DEFAULT 'classic',
  `file_path` VARCHAR(255) DEFAULT NULL,
  `gambar` VARCHAR(255) DEFAULT NULL,
  `tanggal_post` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- SEED DATA BERITA FIKTIF
INSERT INTO `berita` (`id`, `judul`, `konten`, `template`, `file_path`) VALUES
(1, 'The 4 Stairs Band 5th Anniversary Special Show', 'Dalam rangka merayakan hari jadi yang ke-5, The 4 Stairs Band akan mengadakan pertunjukan spesial yang intim sepanjang akhir pekan ini. Kami akan mengundang beberapa rekan musisi jazz indie ternama untuk ikut menyumbang melodi di panggung utama kami. Tiket sangat terbatas!', 'concert', 'articles/berita_1.php'),
(2, 'Malam Minggu Romantis: Tribute to Swing Era', 'Kembali ke era kejayaan swing bersama alunan brass section yang megah dan vokal syahdu. Acara ini akan menampilkan kolaborasi khusus antara komposer internal kami dengan bintang tamu saxophone terkenal. Reservasi tiket masuk Anda sekarang!', 'retro', 'articles/berita_2.php'),
(3, 'Info Protokol Kenyamanan Show', 'Demi menjaga atmosfer intim dan kenyamanan akustik di The 4 Stairs Music Hall, kami menghimbau para tamu untuk mengenakan pakaian Smart Casual. Pintu ruang pertunjukan akan dibuka 30 menit sebelum acara dimulai.', 'classic', 'articles/berita_3.php')
ON DUPLICATE KEY UPDATE `judul` = VALUES(`judul`), `konten` = VALUES(`konten`), `template` = VALUES(`template`), `file_path` = VALUES(`file_path`);

-- --------------------------------------------------------------------------
-- 4. STRUKTUR TABEL: `pesanan`
-- --------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `pesanan` (
  `id_pesanan` VARCHAR(30) PRIMARY KEY,
  `id_jadwal` INT NOT NULL,
  `nama` VARCHAR(100) NOT NULL,
  `email` VARCHAR(100) NOT NULL,
  `no_wa` VARCHAR(20) NOT NULL,
  `jumlah_tiket` INT NOT NULL,
  `status_pembayaran` VARCHAR(30) DEFAULT 'Pending',
  `waktu_pesan` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`id_jadwal`) REFERENCES `jadwal`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;


-- --------------------------------------------------------------------------
-- 5. STRUKTUR TABEL: `admin`
-- --------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `admin` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `username` VARCHAR(50) NOT NULL UNIQUE,
  `password` VARCHAR(255) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- MEMASUKKAN USER ADMIN DEFAULT (Username: admin | Password: admin123)
INSERT INTO `admin` (`id`, `username`, `password`) VALUES
(1, 'admin', '$2y$10$vNphP6U/bO3B5dYfKk8P4O085Y67P1vLd3hS6d83p40K8XzR5L4qG')
ON DUPLICATE KEY UPDATE `username` = 'admin';

-- --------------------------------------------------------------------------
-- 6. STRUKTUR TABEL: `users` (NEW)
-- --------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `users` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `name` VARCHAR(100) NOT NULL,
  `email` VARCHAR(100) UNIQUE NOT NULL,
  `password` VARCHAR(255) NOT NULL,
  `no_wa` VARCHAR(20) NOT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------------------------
-- 7. STRUKTUR TABEL: `komposisi` (NEW)
-- --------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `komposisi` (
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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- MEMASUKKAN DATA KOMPOSISI LAGU DEFAULT
INSERT INTO `komposisi` (`id`, `title`, `artist`, `src`, `cover`, `duration`) VALUES
(1, 'Alennya', 'Redevelop', 'alennya.mp3', 'assets/img/alennya.png', '03:54'),
(2, 'Dorogoi Dlinnoyu', 'Dessy Dobreva', 'dorogoi.mp3', 'assets/img/dorogoi.png', '03:31'),
(3, 'Fly me to the moon', 'The Jazz Woman', 'flyme.mp3', 'assets/img/flyme.png', '03:06'),
(4, 'Tri Belikh Konya (Три белых коня)', 'Kvarto', 'tribelikh.mp3', 'assets/img/tribelikh.png', '03:04'),
(5, 'Kaze ga Fuite', 'Redevelop', 'kazega.mp3', 'assets/img/kazega.png', '02:43'),
(6, 'Pesenka Frontovogo Shofyora', 'Timur Vedernikov', 'pesenka.mp3', 'assets/img/pesenka.png', '02:55')
ON DUPLICATE KEY UPDATE
  `title` = VALUES(`title`),
  `artist` = VALUES(`artist`),
  `src` = VALUES(`src`),
  `cover` = VALUES(`cover`),
  `duration` = VALUES(`duration`);
