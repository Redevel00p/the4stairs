<?php
/**
 * DATABASE CONNECTION CONFIGURATION
 * ---------------------------------
 * File ini digunakan untuk menghubungkan aplikasi PHP Anda ke database MySQL.
 * 
 * SETUP UNTUK LOCAL (XAMPP / WAMP):
 * - Host: localhost
 * - User: root
 * - Pass: (kosongkan)
 * - DB: db_grand5stairs
 * 
 * SETUP UNTUK INFINITYFREE HOSTING:
 * - Silakan ganti nilai variabel di bawah sesuai dengan informasi yang ada di
 *   cPanel InfinityFree Anda (pada bagian 'MySQL Databases').
 */

$db_host = "sql210.infinityfree.com";                 
$db_user = "if0_41861321";                       
$db_pass = "Imthevilagers";                           
$db_name = "if0_41861321_the4stairs";   

// Set default PHP timezone to Indonesian Time (WIB)
date_default_timezone_set('Asia/Jakarta');

// Membuat koneksi ke MySQL menggunakan mysqli
$conn = new mysqli($db_host, $db_user, $db_pass);

// Memeriksa jika ada error pada koneksi server
if ($conn->connect_error) {
    die("Koneksi ke database server gagal: " . $conn->connect_error);
}

// Set MySQL session time zone to match WIB (UTC+7)
$conn->query("SET time_zone = '+07:00'");

// Mencoba memilih database, jika database sudah ada
$db_selected = $conn->select_db($db_name);

if ($db_selected) {
    // Check if pesanan table exists to prevent errors during initial install
    $table_check = $conn->query("SHOW TABLES LIKE 'pesanan'");
    if ($table_check && $table_check->num_rows > 0) {
        // Cari order pending yang sudah melewati batas 15 menit
        $stmt_expired = $conn->query("SELECT id_pesanan, id_jadwal, jumlah_tiket FROM pesanan WHERE status_pembayaran = 'Pending' AND waktu_pesan < NOW() - INTERVAL 15 MINUTE");
        if ($stmt_expired && $stmt_expired->num_rows > 0) {
            while ($row = $stmt_expired->fetch_assoc()) {
                $id_pesanan_exp = $row['id_pesanan'];
                $id_jadwal_exp = $row['id_jadwal'];
                $jumlah_tiket_exp = intval($row['jumlah_tiket']);
                
                // Revert kuota terjual
                $conn->query("UPDATE `jadwal` SET `terjual` = GREATEST(0, `terjual` - $jumlah_tiket_exp) WHERE `id` = $id_jadwal_exp");
                
                // Hapus tiket dari pesanan
                $conn->query("DELETE FROM `pesanan` WHERE `id_pesanan` = '$id_pesanan_exp'");
            }
        }
    }

    // Self-healing: check if is_special column exists in jadwal table
    $table_check_jadwal = $conn->query("SHOW TABLES LIKE 'jadwal'");
    if ($table_check_jadwal && $table_check_jadwal->num_rows > 0) {
        $columns_check = $conn->query("SHOW COLUMNS FROM `jadwal` LIKE 'is_special'");
        if ($columns_check && $columns_check->num_rows === 0) {
            $conn->query("ALTER TABLE `jadwal` ADD `is_special` TINYINT DEFAULT 0 AFTER `special_notes`");
        }
        
        // Auto-populate schedules for the current week (Monday to Sunday) if they don't exist
        $monday_date = date('Y-m-d', strtotime('monday this week'));
        
        $days_default = [
            0 => ['name' => 'Senin', 'title' => 'Acoustic Monday Melodies', 'jam' => '19:00 - 22:00 WIB', 'notes' => 'Live Acoustic Performance', 'is_special' => 0],
            1 => ['name' => 'Selasa', 'title' => 'Jazz Fusion Session', 'jam' => '19:00 - 22:00 WIB', 'notes' => 'Fusion Brass Ensemble', 'is_special' => 0],
            2 => ['name' => 'Rabu', 'title' => 'Blues & Soul Midweek', 'jam' => '19:00 - 22:00 WIB', 'notes' => 'Classic Rhythm & Blues', 'is_special' => 0],
            3 => ['name' => 'Kamis', 'title' => 'Classical Ensemble Night', 'jam' => '19:00 - 22:00 WIB', 'notes' => 'Strings & Piano Duet', 'is_special' => 0],
            4 => ['name' => 'Jumat', 'title' => 'Midnight Swing & Saxophone Tribute', 'jam' => '20:00 - 23:00 WIB', 'notes' => 'Special Saxophone Soloist', 'is_special' => 1],
            5 => ['name' => 'Sabtu', 'title' => 'The 4 Stairs Anniversary Live', 'jam' => '20:00 - 23:00 WIB', 'notes' => 'Special Guest Star: Velvet Blue Jazz Trio!', 'is_special' => 1],
            6 => ['name' => 'Minggu', 'title' => 'Cozy Acoustic Sunday Jazz', 'jam' => '19:00 - 22:00 WIB', 'notes' => 'Relaxing Acoustic Melodies', 'is_special' => 0]
        ];

        for ($i = 0; $i < 7; $i++) {
            $current_day_date = date('Y-m-d', strtotime("+$i days", strtotime($monday_date)));
            
            // Check if there is already a schedule for this date
            $check_schedule = $conn->query("SELECT id FROM `jadwal` WHERE `tanggal` = '$current_day_date'");
            if ($check_schedule && $check_schedule->num_rows === 0) {
                // Insert default schedule for this date
                $day_info = $days_default[$i];
                $hari_name = $day_info['name'];
                $event_title = $conn->real_escape_string($day_info['title']);
                $event_jam = $conn->real_escape_string($day_info['jam']);
                $event_notes = $day_info['notes'] !== NULL ? "'" . $conn->real_escape_string($day_info['notes']) . "'" : "NULL";
                $is_spec = $day_info['is_special'];
                
                $conn->query("INSERT INTO `jadwal` (`hari`, `tanggal`, `jam`, `kuota`, `terjual`, `nama_event`, `status`, `special_notes`, `is_special`) 
                              VALUES ('$hari_name', '$current_day_date', '$event_jam', 50, 0, '$event_title', 'Open', $event_notes, $is_spec)");
            }
        }
    }
}
?>
