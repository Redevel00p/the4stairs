<?php
/**
 * THE 4 STAIRS MUSIC HALL - ADMIN PROCESS OPERATIONS
 * --------------------------------------------------
 * Memproses aksi dari admin dashboard seperti mengedit status pembayaran,
 * menghapus pesanan (dan mengembalikan kuota kursi), serta me-reset data testing.
 */

session_start();

// Periksa apakah admin sudah login
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header("Location: admin_login");
    exit;
}

// Sertakan file koneksi database
include 'koneksi.php';

// --------------------------------------------------------------------------
// PROSES AKSI POST (UPDATE/INSERT)
// --------------------------------------------------------------------------
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        $action = $_POST['action'];
        
        // POST 1: EDIT JADWAL PERTUNJUKAN
        if ($action === 'edit_jadwal') {
            $id = intval($_POST['id']);
            $nama_event = $conn->real_escape_string(trim($_POST['nama_event']));
            $tanggal = $conn->real_escape_string(trim($_POST['tanggal']));
            $jam = $conn->real_escape_string(trim($_POST['jam']));
            $status = $conn->real_escape_string(trim($_POST['status']));
            $special_notes = $conn->real_escape_string(trim($_POST['special_notes']));
            
            if (empty($nama_event)) {
                $nama_event = 'Jazz Night Show';
            }
            
            $stmt = $conn->prepare("UPDATE `jadwal` SET `nama_event` = ?, `tanggal` = ?, `jam` = ?, `status` = ?, `special_notes` = ? WHERE `id` = ?");
            $stmt->bind_param("sssssi", $nama_event, $tanggal, $jam, $status, $special_notes, $id);
            
            if ($stmt->execute()) {
                header("Location: admin_dashboard?msg=schedule_updated&tab=schedules");
            } else {
                header("Location: admin_dashboard?msg=error&tab=schedules");
            }
            $stmt->close();
            exit;
        }
        
        // POST 2: ADD BERITA (PENGUMUMAN)
        if ($action === 'add_berita') {
            $judul = trim($_POST['judul']);
            $konten = trim($_POST['konten']);
            $template = isset($_POST['template']) ? trim($_POST['template']) : 'classic';
            
            if (empty($judul) || empty($konten)) {
                header("Location: admin_dashboard?msg=error&tab=news");
                exit;
            }
            
            $gambar_dest = null;
            if (isset($_POST['cropped_news_img']) && strpos($_POST['cropped_news_img'], 'data:image/') === 0) {
                $base64_data = $_POST['cropped_news_img'];
                list($type, $data) = explode(';', $base64_data);
                list(, $data)      = explode(',', $data);
                $decoded_image = base64_decode($data);
                
                if ($decoded_image !== false) {
                    $file_ext = 'png';
                    if (preg_match('/data:image\/(\w+);base64/', $base64_data, $matches)) {
                        $file_ext = strtolower($matches[1]);
                    }
                    $target_dir = 'assets/img/berita/';
                    if (!is_dir($target_dir)) {
                        mkdir($target_dir, 0777, true);
                    }
                    $unique_name = 'berita_' . time() . '_' . uniqid() . '.' . $file_ext;
                    $gambar_dest = $target_dir . $unique_name;
                    if (!file_put_contents($gambar_dest, $decoded_image)) {
                        $gambar_dest = null;
                    }
                }
            } else if (isset($_FILES['gambar']) && $_FILES['gambar']['error'] === UPLOAD_ERR_OK) {
                $file_tmp = $_FILES['gambar']['tmp_name'];
                $file_name = $_FILES['gambar']['name'];
                $file_size = $_FILES['gambar']['size'];
                
                // Validasi ekstensi
                $file_ext = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));
                $allowed_exts = ['jpg', 'jpeg', 'png', 'gif'];
                
                if (in_array($file_ext, $allowed_exts) && $file_size <= 2 * 1024 * 1024) {
                    // Buat folder assets/img/berita jika belum ada
                    $target_dir = 'assets/img/berita/';
                    if (!is_dir($target_dir)) {
                        mkdir($target_dir, 0777, true);
                    }
                    
                    $unique_name = 'berita_' . time() . '_' . uniqid() . '.' . $file_ext;
                    $gambar_dest = $target_dir . $unique_name;
                    
                    if (!move_uploaded_file($file_tmp, $gambar_dest)) {
                        $gambar_dest = null;
                    }
                }
            }
            
            $judul_esc = $conn->real_escape_string($judul);
            $konten_esc = $conn->real_escape_string($konten);
            $template_esc = $conn->real_escape_string($template);
            
            $stmt = $conn->prepare("INSERT INTO `berita` (`judul`, `konten`, `template`, `gambar`) VALUES (?, ?, ?, ?)");
            $stmt->bind_param("ssss", $judul_esc, $konten_esc, $template_esc, $gambar_dest);
            
            if ($stmt->execute()) {
                $inserted_id = $conn->insert_id;
                $file_path = "articles/berita_" . $inserted_id . ".php";
                
                // Cari info tanggal post demi akurasi isi template
                $res = $conn->query("SELECT `tanggal_post` FROM `berita` WHERE `id` = $inserted_id");
                $tanggal_post = date('Y-m-d H:i:s');
                if ($res && $res->num_rows > 0) {
                    $row = $res->fetch_assoc();
                    $tanggal_post = $row['tanggal_post'];
                }
                
                // Load generator engine
                require_once 'warta_template.php';
                $html_content = generate_article_html($judul, $konten, $tanggal_post, $template, $gambar_dest);
                
                // Buat folder articles jika belum ada
                if (!is_dir('articles')) {
                    mkdir('articles', 0777, true);
                }
                
                // Tulis file
                file_put_contents($file_path, $html_content);
                
                // Update file_path di database
                $stmt_update = $conn->prepare("UPDATE `berita` SET `file_path` = ? WHERE `id` = ?");
                $stmt_update->bind_param("si", $file_path, $inserted_id);
                $stmt_update->execute();
                $stmt_update->close();
                
                header("Location: admin_dashboard?msg=news_added&tab=news");
            } else {
                header("Location: admin_dashboard?msg=error&tab=news");
            }
            $stmt->close();
            exit;
        }
        
        // POST 3: ADD SPECIAL EVENT
        if ($action === 'add_special_event') {
            $nama_event = $conn->real_escape_string(trim($_POST['nama_event']));
            $tanggal = $conn->real_escape_string(trim($_POST['tanggal']));
            $jam = $conn->real_escape_string(trim($_POST['jam']));
            $special_notes = $conn->real_escape_string(trim($_POST['special_notes']));
            
            if (empty($nama_event) || empty($tanggal) || empty($jam)) {
                header("Location: admin_dashboard?msg=error&tab=schedules");
                exit;
            }
            
            // Calculate Indonesian day name
            $dayOfWeek = date('l', strtotime($tanggal));
            $days = [
                'Monday' => 'Senin',
                'Tuesday' => 'Selasa',
                'Wednesday' => 'Rabu',
                'Thursday' => 'Kamis',
                'Friday' => 'Jumat',
                'Saturday' => 'Sabtu',
                'Sunday' => 'Minggu'
            ];
            $hari = isset($days[$dayOfWeek]) ? $days[$dayOfWeek] : 'Senin';
            
            $stmt = $conn->prepare("INSERT INTO `jadwal` (`hari`, `tanggal`, `jam`, `kuota`, `terjual`, `nama_event`, `status`, `special_notes`, `is_special`) VALUES (?, ?, ?, 50, 0, ?, 'Open', ?, 1)");
            $stmt->bind_param("sssss", $hari, $tanggal, $jam, $nama_event, $special_notes);
            
            if ($stmt->execute()) {
                header("Location: admin_dashboard?msg=special_event_added&tab=schedules");
            } else {
                header("Location: admin_dashboard?msg=error&tab=schedules");
            }
            $stmt->close();
            exit;
        }

        // POST 4: ADD KOMPOSISI LAGU
        if ($action === 'add_komposisi') {
            $title = trim($_POST['title']);
            $artist = trim($_POST['artist']);
            $duration = trim($_POST['duration']);
            
            if (empty($title) || empty($artist) || empty($duration)) {
                header("Location: admin_dashboard?msg=invalid_file&tab=compositions");
                exit;
            }
            
            $audio_dest = null;
            $cover_dest = null;
            
            // 1. Process Audio File or URL
            $audio_source_type = isset($_POST['audio_source_type']) ? $_POST['audio_source_type'] : 'file';
            if ($audio_source_type === 'url') {
                $audio_url = isset($_POST['audio_url']) ? trim($_POST['audio_url']) : '';
                if (!empty($audio_url)) {
                    $audio_dest = $audio_url;
                }
            } else {
                if (isset($_FILES['audio_file']) && $_FILES['audio_file']['error'] === UPLOAD_ERR_OK) {
                    $file_tmp = $_FILES['audio_file']['tmp_name'];
                    $file_name = $_FILES['audio_file']['name'];
                    $file_size = $_FILES['audio_file']['size'];
                    
                    $file_ext = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));
                    $allowed_exts = ['mp3', 'mpeg'];
                    
                    if (in_array($file_ext, $allowed_exts) && $file_size <= 10 * 1024 * 1024) {
                        $target_dir = 'assets/audio/';
                        if (!is_dir($target_dir)) {
                            mkdir($target_dir, 0777, true);
                        }
                        $unique_name = 'audio_' . time() . '_' . uniqid() . '.' . $file_ext;
                        $audio_dest = $target_dir . $unique_name;
                        
                        if (!move_uploaded_file($file_tmp, $audio_dest)) {
                            $audio_dest = null;
                        }
                    }
                }
            }
            
            // 2. Process Cover File (Cropped base64 or normal upload)
            if (isset($_POST['cropped_cover_img']) && strpos($_POST['cropped_cover_img'], 'data:image/') === 0) {
                $base64_data = $_POST['cropped_cover_img'];
                list($type, $data) = explode(';', $base64_data);
                list(, $data)      = explode(',', $data);
                $decoded_image = base64_decode($data);
                
                if ($decoded_image !== false) {
                    $file_ext = 'png';
                    if (preg_match('/data:image\/(\w+);base64/', $base64_data, $matches)) {
                        $file_ext = strtolower($matches[1]);
                    }
                    $target_dir = 'assets/img/covers/';
                    if (!is_dir($target_dir)) {
                        mkdir($target_dir, 0777, true);
                    }
                    $unique_name = 'cover_' . time() . '_' . uniqid() . '.' . $file_ext;
                    $cover_dest = $target_dir . $unique_name;
                    if (!file_put_contents($cover_dest, $decoded_image)) {
                        $cover_dest = null;
                    }
                }
            } else if (isset($_FILES['cover_file']) && $_FILES['cover_file']['error'] === UPLOAD_ERR_OK) {
                $file_tmp = $_FILES['cover_file']['tmp_name'];
                $file_name = $_FILES['cover_file']['name'];
                $file_size = $_FILES['cover_file']['size'];
                
                $file_ext = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));
                $allowed_exts = ['jpg', 'jpeg', 'png', 'webp', 'gif'];
                
                if (in_array($file_ext, $allowed_exts) && $file_size <= 2 * 1024 * 1024) {
                    $target_dir = 'assets/img/covers/';
                    if (!is_dir($target_dir)) {
                        mkdir($target_dir, 0777, true);
                    }
                    $unique_name = 'cover_' . time() . '_' . uniqid() . '.' . $file_ext;
                    $cover_dest = $target_dir . $unique_name;
                    
                    if (!move_uploaded_file($file_tmp, $cover_dest)) {
                        $cover_dest = null;
                    }
                }
            }
            
            if ($audio_dest === null || $cover_dest === null) {
                // Clean up any partially uploaded files
                if ($audio_dest !== null && strpos($audio_dest, 'http') !== 0 && file_exists($audio_dest)) @unlink($audio_dest);
                if ($cover_dest !== null && strpos($cover_dest, 'http') !== 0 && file_exists($cover_dest)) @unlink($cover_dest);
                
                header("Location: admin_dashboard?msg=upload_failed&tab=compositions");
                exit;
            }
            
            $youtube_url = isset($_POST['youtube_url']) ? trim($_POST['youtube_url']) : '';
            $soundcloud_url = isset($_POST['soundcloud_url']) ? trim($_POST['soundcloud_url']) : '';
            $spotify_url = isset($_POST['spotify_url']) ? trim($_POST['spotify_url']) : '';
            $lyrics = isset($_POST['lyrics']) ? trim($_POST['lyrics']) : '';
 
            $title_esc = $conn->real_escape_string($title);
            $artist_esc = $conn->real_escape_string($artist);
            $duration_esc = $conn->real_escape_string($duration);
            $audio_esc = $conn->real_escape_string($audio_dest);
            $cover_esc = $conn->real_escape_string($cover_dest);
            
            $youtube_esc = $conn->real_escape_string($youtube_url);
            $soundcloud_esc = $conn->real_escape_string($soundcloud_url);
            $spotify_esc = $conn->real_escape_string($spotify_url);
            $lyrics_esc = $conn->real_escape_string($lyrics);
            
            $stmt = $conn->prepare("INSERT INTO `komposisi` (`title`, `artist`, `src`, `cover`, `duration`, `youtube_url`, `soundcloud_url`, `spotify_url`, `lyrics`) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
            $stmt->bind_param("sssssssss", $title_esc, $artist_esc, $audio_esc, $cover_esc, $duration_esc, $youtube_esc, $soundcloud_esc, $spotify_esc, $lyrics_esc);
            
            if ($stmt->execute()) {
                header("Location: admin_dashboard?msg=komposisi_added&tab=compositions");
            } else {
                // Clean up physical files on SQL insert failure
                if (strpos($audio_dest, 'http') !== 0 && file_exists($audio_dest)) @unlink($audio_dest);
                if (strpos($cover_dest, 'http') !== 0 && file_exists($cover_dest)) @unlink($cover_dest);
                header("Location: admin_dashboard?msg=error&tab=compositions");
            }
            $stmt->close();
            exit;
        }

        // POST: EDIT BERITA (PENGUMUMAN)
        if ($action === 'edit_berita') {
            $id = intval($_POST['id']);
            $judul = trim($_POST['judul']);
            $konten = trim($_POST['konten']);
            $template = isset($_POST['template']) ? trim($_POST['template']) : 'classic';
            
            if (empty($id) || empty($judul) || empty($konten)) {
                header("Location: admin_dashboard?msg=error&tab=news");
                exit;
            }
            
            // Get existing image to handle deletion if updated
            $existing_gambar = null;
            $tanggal_post = date('Y-m-d H:i:s');
            $res = $conn->query("SELECT `gambar`, `tanggal_post` FROM `berita` WHERE `id` = $id");
            if ($res && $res->num_rows > 0) {
                $row = $res->fetch_assoc();
                $existing_gambar = $row['gambar'];
                $tanggal_post = $row['tanggal_post'];
            } else {
                header("Location: admin_dashboard?msg=error&tab=news");
                exit;
            }
            
            $gambar_dest = $existing_gambar;
            $new_img_uploaded = false;
            
            // Process Image Crop or normal upload
            if (isset($_POST['cropped_news_img']) && strpos($_POST['cropped_news_img'], 'data:image/') === 0) {
                $base64_data = $_POST['cropped_news_img'];
                list($type, $data) = explode(';', $base64_data);
                list(, $data)      = explode(',', $data);
                $decoded_image = base64_decode($data);
                
                if ($decoded_image !== false) {
                    $file_ext = 'png';
                    if (preg_match('/data:image\/(\w+);base64/', $base64_data, $matches)) {
                        $file_ext = strtolower($matches[1]);
                    }
                    $target_dir = 'assets/img/berita/';
                    if (!is_dir($target_dir)) {
                        mkdir($target_dir, 0777, true);
                    }
                    $unique_name = 'berita_' . time() . '_' . uniqid() . '.' . $file_ext;
                    $gambar_dest = $target_dir . $unique_name;
                    if (file_put_contents($gambar_dest, $decoded_image)) {
                        $new_img_uploaded = true;
                    } else {
                        $gambar_dest = $existing_gambar;
                    }
                }
            } else if (isset($_FILES['gambar']) && $_FILES['gambar']['error'] === UPLOAD_ERR_OK) {
                $file_tmp = $_FILES['gambar']['tmp_name'];
                $file_name = $_FILES['gambar']['name'];
                $file_size = $_FILES['gambar']['size'];
                
                $file_ext = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));
                $allowed_exts = ['jpg', 'jpeg', 'png', 'gif'];
                
                if (in_array($file_ext, $allowed_exts) && $file_size <= 2 * 1024 * 1024) {
                    $target_dir = 'assets/img/berita/';
                    if (!is_dir($target_dir)) {
                        mkdir($target_dir, 0777, true);
                    }
                    
                    $unique_name = 'berita_' . time() . '_' . uniqid() . '.' . $file_ext;
                    $gambar_dest = $target_dir . $unique_name;
                    
                    if (move_uploaded_file($file_tmp, $gambar_dest)) {
                        $new_img_uploaded = true;
                    } else {
                        $gambar_dest = $existing_gambar;
                    }
                }
            }
            
            $judul_esc = $conn->real_escape_string($judul);
            $konten_esc = $conn->real_escape_string($konten);
            $template_esc = $conn->real_escape_string($template);
            $gambar_esc = $conn->real_escape_string($gambar_dest);
            
            $stmt = $conn->prepare("UPDATE `berita` SET `judul` = ?, `konten` = ?, `template` = ?, `gambar` = ? WHERE `id` = ?");
            $stmt->bind_param("ssssi", $judul_esc, $konten_esc, $template_esc, $gambar_esc, $id);
            
            if ($stmt->execute()) {
                // Delete old image if a new one was uploaded and existing image is local
                if ($new_img_uploaded && !empty($existing_gambar) && file_exists(__DIR__ . '/' . $existing_gambar)) {
                    @unlink(__DIR__ . '/' . $existing_gambar);
                }
                
                $file_path = "articles/berita_" . $id . ".php";
                
                // Load generator engine
                require_once 'warta_template.php';
                $html_content = generate_article_html($judul, $konten, $tanggal_post, $template, $gambar_dest);
                
                if (!is_dir('articles')) {
                    mkdir('articles', 0777, true);
                }
                file_put_contents($file_path, $html_content);
                
                // Update file_path if it was null (should not be, but just in case)
                $stmt_update = $conn->prepare("UPDATE `berita` SET `file_path` = ? WHERE `id` = ?");
                $stmt_update->bind_param("si", $file_path, $id);
                $stmt_update->execute();
                $stmt_update->close();
                
                header("Location: admin_dashboard?msg=news_updated&tab=news");
            } else {
                // Clean up new image if database insert failed
                if ($new_img_uploaded && file_exists($gambar_dest)) {
                    @unlink($gambar_dest);
                }
                header("Location: admin_dashboard?msg=error&tab=news");
            }
            $stmt->close();
            exit;
        }

        // POST: EDIT KOMPOSISI LAGU
        if ($action === 'edit_komposisi') {
            $id = intval($_POST['id']);
            $title = trim($_POST['title']);
            $artist = trim($_POST['artist']);
            $duration = trim($_POST['duration']);
            
            if (empty($id) || empty($title) || empty($artist) || empty($duration)) {
                header("Location: admin_dashboard?msg=error&tab=compositions");
                exit;
            }
            
            // Get existing audio and cover
            $existing_audio = null;
            $existing_cover = null;
            $res = $conn->query("SELECT `src`, `cover` FROM `komposisi` WHERE `id` = $id");
            if ($res && $res->num_rows > 0) {
                $row = $res->fetch_assoc();
                $existing_audio = $row['src'];
                $existing_cover = $row['cover'];
            } else {
                header("Location: admin_dashboard?msg=error&tab=compositions");
                exit;
            }
            
            $audio_dest = $existing_audio;
            $cover_dest = $existing_cover;
            $new_audio_uploaded = false;
            $new_cover_uploaded = false;
            
            $audio_source_type = isset($_POST['edit_audio_source_type']) ? $_POST['edit_audio_source_type'] : 'file';
            
            if ($audio_source_type === 'url') {
                $audio_url = isset($_POST['audio_url']) ? trim($_POST['audio_url']) : '';
                if (!empty($audio_url)) {
                    $audio_dest = $audio_url;
                    $new_audio_uploaded = true;
                }
            } else {
                if (isset($_FILES['audio_file']) && $_FILES['audio_file']['error'] === UPLOAD_ERR_OK) {
                    $file_tmp = $_FILES['audio_file']['tmp_name'];
                    $file_name = $_FILES['audio_file']['name'];
                    $file_size = $_FILES['audio_file']['size'];
                    
                    $file_ext = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));
                    $allowed_exts = ['mp3', 'mpeg'];
                    
                    if (in_array($file_ext, $allowed_exts) && $file_size <= 10 * 1024 * 1024) {
                        $target_dir = 'assets/audio/';
                        if (!is_dir($target_dir)) {
                            mkdir($target_dir, 0777, true);
                        }
                        $unique_name = 'audio_' . time() . '_' . uniqid() . '.' . $file_ext;
                        $audio_dest = $target_dir . $unique_name;
                        
                        if (move_uploaded_file($file_tmp, $audio_dest)) {
                            $new_audio_uploaded = true;
                        } else {
                            $audio_dest = $existing_audio;
                        }
                    }
                }
            }
            
            // 2. Process Cover File (Cropped base64 or normal upload)
            if (isset($_POST['cropped_cover_img']) && strpos($_POST['cropped_cover_img'], 'data:image/') === 0) {
                $base64_data = $_POST['cropped_cover_img'];
                list($type, $data) = explode(';', $base64_data);
                list(, $data)      = explode(',', $data);
                $decoded_image = base64_decode($data);
                
                if ($decoded_image !== false) {
                    $file_ext = 'png';
                    if (preg_match('/data:image\/(\w+);base64/', $base64_data, $matches)) {
                        $file_ext = strtolower($matches[1]);
                    }
                    $target_dir = 'assets/img/covers/';
                    if (!is_dir($target_dir)) {
                        mkdir($target_dir, 0777, true);
                    }
                    $unique_name = 'cover_' . time() . '_' . uniqid() . '.' . $file_ext;
                    $cover_dest = $target_dir . $unique_name;
                    if (file_put_contents($cover_dest, $decoded_image)) {
                        $new_cover_uploaded = true;
                    } else {
                        $cover_dest = $existing_cover;
                    }
                }
            } else if (isset($_FILES['cover_file']) && $_FILES['cover_file']['error'] === UPLOAD_ERR_OK) {
                $file_tmp = $_FILES['cover_file']['tmp_name'];
                $file_name = $_FILES['cover_file']['name'];
                $file_size = $_FILES['cover_file']['size'];
                
                $file_ext = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));
                $allowed_exts = ['jpg', 'jpeg', 'png', 'webp', 'gif'];
                
                if (in_array($file_ext, $allowed_exts) && $file_size <= 2 * 1024 * 1024) {
                    $target_dir = 'assets/img/covers/';
                    if (!is_dir($target_dir)) {
                        mkdir($target_dir, 0777, true);
                    }
                    $unique_name = 'cover_' . time() . '_' . uniqid() . '.' . $file_ext;
                    $cover_dest = $target_dir . $unique_name;
                    
                    if (move_uploaded_file($file_tmp, $cover_dest)) {
                        $new_cover_uploaded = true;
                    } else {
                        $cover_dest = $existing_cover;
                    }
                }
            }
            
            $youtube_url = isset($_POST['youtube_url']) ? trim($_POST['youtube_url']) : '';
            $soundcloud_url = isset($_POST['soundcloud_url']) ? trim($_POST['soundcloud_url']) : '';
            $spotify_url = isset($_POST['spotify_url']) ? trim($_POST['spotify_url']) : '';
            $lyrics = isset($_POST['lyrics']) ? trim($_POST['lyrics']) : '';
            
            $title_esc = $conn->real_escape_string($title);
            $artist_esc = $conn->real_escape_string($artist);
            $duration_esc = $conn->real_escape_string($duration);
            $audio_esc = $conn->real_escape_string($audio_dest);
            $cover_esc = $conn->real_escape_string($cover_dest);
            
            $youtube_esc = $conn->real_escape_string($youtube_url);
            $soundcloud_esc = $conn->real_escape_string($soundcloud_url);
            $spotify_esc = $conn->real_escape_string($spotify_url);
            $lyrics_esc = $conn->real_escape_string($lyrics);
            
            $stmt = $conn->prepare("UPDATE `komposisi` SET `title` = ?, `artist` = ?, `src` = ?, `cover` = ?, `duration` = ?, `youtube_url` = ?, `soundcloud_url` = ?, `spotify_url` = ?, `lyrics` = ? WHERE `id` = ?");
            $stmt->bind_param("sssssssssi", $title_esc, $artist_esc, $audio_esc, $cover_esc, $duration_esc, $youtube_esc, $soundcloud_esc, $spotify_esc, $lyrics_esc, $id);
            
            if ($stmt->execute()) {
                // Clean up old audio if updated and old audio is local
                if ($new_audio_uploaded && !empty($existing_audio) && strpos($existing_audio, 'http') !== 0 && file_exists(__DIR__ . '/' . $existing_audio)) {
                    @unlink(__DIR__ . '/' . $existing_audio);
                }
                // Clean up old cover if updated and old cover is local
                if ($new_cover_uploaded && !empty($existing_cover) && strpos($existing_cover, 'http') !== 0 && file_exists(__DIR__ . '/' . $existing_cover)) {
                    @unlink(__DIR__ . '/' . $existing_cover);
                }
                
                header("Location: admin_dashboard?msg=komposisi_updated&tab=compositions");
            } else {
                // Clean up newly uploaded files on SQL update failure
                if ($new_audio_uploaded && strpos($audio_dest, 'http') !== 0 && file_exists($audio_dest)) @unlink($audio_dest);
                if ($new_cover_uploaded && strpos($cover_dest, 'http') !== 0 && file_exists($cover_dest)) @unlink($cover_dest);
                header("Location: admin_dashboard?msg=error&tab=compositions");
            }
            $stmt->close();
            exit;
        }
        
        // POST 5: AJAX TICKET CHECK-IN
        if ($action === 'check_in_ticket') {
            header('Content-Type: application/json');
            
            $code = isset($_POST['code']) ? trim($_POST['code']) : '';
            if (empty($code)) {
                echo json_encode(['status' => 'error', 'message' => 'ID tiket tidak boleh kosong.']);
                exit;
            }
            
            $code_esc = $conn->real_escape_string($code);
            
            // Query ticket details with event day of week
            $query = "SELECT p.*, j.hari, j.tanggal, j.jam, j.nama_event 
                      FROM `pesanan` p 
                      JOIN `jadwal` j ON p.id_jadwal = j.id 
                      WHERE p.id_pesanan = '$code_esc'";
            $res = $conn->query($query);
            
            if (!$res || $res->num_rows === 0) {
                echo json_encode(['status' => 'error', 'message' => 'Tiket dengan kode ' . htmlspecialchars($code) . ' tidak ditemukan.']);
                exit;
            }
            
            $pesanan = $res->fetch_assoc();
            $status = strtolower($pesanan['status_pembayaran']);
            $hari_ticket = $pesanan['hari']; // e.g. "Jumat"
            
            // 1. Cek status pembayaran
            if ($status === 'pending') {
                echo json_encode(['status' => 'error', 'message' => 'Tiket belum lunas (status masih pending). Silakan selesaikan pembayaran terlebih dahulu.']);
                exit;
            }
            
            if ($status === 'sudah dipakai') {
                echo json_encode(['status' => 'error', 'message' => 'Tiket ini sudah pernah digunakan sebelumnya (Check-in ganda tidak diizinkan).']);
                exit;
            }
            
            // 2. Cek apakah hari saat ini sama dengan hari pertunjukan tiket
            $day_eng = date('l');
            $days_map = [
                'Monday'    => 'Senin',
                'Tuesday'   => 'Selasa',
                'Wednesday' => 'Rabu',
                'Thursday'  => 'Kamis',
                'Friday'    => 'Jumat',
                'Saturday'  => 'Sabtu',
                'Sunday'    => 'Minggu'
            ];
            $hari_sekarang = isset($days_map[$day_eng]) ? $days_map[$day_eng] : '';
            
            if (strtolower($hari_ticket) !== strtolower($hari_sekarang)) {
                echo json_encode([
                    'status' => 'error', 
                    'message' => 'Tiket ini hanya berlaku untuk pertunjukan hari ' . $hari_ticket . '. Hari ini adalah hari ' . $hari_sekarang . '.'
                ]);
                exit;
            }
            
            // 3. Update status tiket menjadi 'Sudah Dipakai'
            $update_query = "UPDATE `pesanan` SET `status_pembayaran` = 'Sudah Dipakai' WHERE `id_pesanan` = '$code_esc'";
            if ($conn->query($update_query)) {
                echo json_encode([
                    'status' => 'success',
                    'message' => 'Check-in berhasil.',
                    'ticket' => [
                        'id' => $pesanan['id_pesanan'],
                        'nama' => htmlspecialchars($pesanan['nama']),
                        'email' => htmlspecialchars($pesanan['email']),
                        'nama_event' => htmlspecialchars($pesanan['nama_event']),
                        'hari' => $pesanan['hari'],
                        'jam' => $pesanan['jam'],
                        'jumlah' => $pesanan['jumlah_tiket']
                    ]
                ]);
                exit;
            } else {
                echo json_encode(['status' => 'error', 'message' => 'Gagal memperbarui status check-in di database: ' . $conn->error]);
                exit;
            }
        }
    }
}

if (isset($_GET['action'])) {
    $action = $_GET['action'];
    
    // Kumpulkan parameter filter & tab tambahan dari URL untuk melestarikan filter setelah aksi
    $extra_params = "";
    foreach ($_GET as $key => $val) {
        if ($key !== 'action' && $key !== 'id') {
            $extra_params .= "&" . urlencode($key) . "=" . urlencode($val);
        }
    }

    // ----------------------------------------------------------------------
    // AKSI 1: UBAH STATUS PEMBAYARAN (PENDING <=> LUNAS - BELUM DIPAKAI <=> SUDAH DIPAKAI)
    // ----------------------------------------------------------------------
    if ($action === 'status' && isset($_GET['id'])) {
        $id_pesanan = $conn->real_escape_string($_GET['id']);
        $to = isset($_GET['to']) ? trim($_GET['to']) : '';
        
        // Ambil status saat ini
        $res = $conn->query("SELECT `status_pembayaran` FROM `pesanan` WHERE `id_pesanan` = '$id_pesanan'");
        if ($res && $res->num_rows > 0) {
            $data = $res->fetch_assoc();
            $current_status = $data['status_pembayaran'];
            
            if ($to !== '') {
                $status_baru = $conn->real_escape_string($to);
            } else {
                // Toggle behavior
                if ($current_status === 'Pending') {
                    $status_baru = 'Lunas - Belum Dipakai';
                } elseif ($current_status === 'Lunas - Belum Dipakai') {
                    $status_baru = 'Sudah Dipakai';
                } else {
                    $status_baru = 'Pending';
                }
            }
            
            // Update status pembayaran
            $conn->query("UPDATE `pesanan` SET `status_pembayaran` = '$status_baru' WHERE `id_pesanan` = '$id_pesanan'");
            
            header("Location: admin_dashboard?msg=status_updated" . $extra_params);
            exit;
        }
    }
    
    // ----------------------------------------------------------------------
    // AKSI 2: HAPUS PEMESANAN (MENGEMBALIKAN KUOTA KURSI)
    // ----------------------------------------------------------------------
    if ($action === 'delete' && isset($_GET['id'])) {
        $id_pesanan = $conn->real_escape_string($_GET['id']);
        
        // Ambil info pesanan untuk merestorasi kuota
        $res = $conn->query("SELECT `id_jadwal`, `jumlah_tiket` FROM `pesanan` WHERE `id_pesanan` = '$id_pesanan'");
        if ($res && $res->num_rows > 0) {
            $data = $res->fetch_assoc();
            $id_jadwal = $data['id_jadwal'];
            $jumlah_tiket = $data['jumlah_tiket'];
            
            // Mulai transaksi database
            $conn->begin_transaction();
            try {
                // Hapus baris pesanan
                $conn->query("DELETE FROM `pesanan` WHERE `id_pesanan` = '$id_pesanan'");
                
                // Kurangi kolom terjual di jadwal pertunjukan (mengembalikan kuota)
                $conn->query("UPDATE `jadwal` SET `terjual` = GREATEST(0, `terjual` - $jumlah_tiket) WHERE `id` = $id_jadwal");
                
                $conn->commit();
                header("Location: admin_dashboard?msg=deleted" . $extra_params);
                exit;
            } catch (Exception $e) {
                $conn->rollback();
                header("Location: admin_dashboard?msg=error" . $extra_params);
                exit;
            }
        }
    }
    
    // ----------------------------------------------------------------------
    // AKSI 3: HAPUS BERITA
    // ----------------------------------------------------------------------
    if ($action === 'delete_berita' && isset($_GET['id'])) {
        $id = intval($_GET['id']);
        
        // Ambil info file_path dan gambar untuk menghapus file fisik
        $res = $conn->query("SELECT `file_path`, `gambar` FROM `berita` WHERE `id` = $id");
        if ($res && $res->num_rows > 0) {
            $row = $res->fetch_assoc();
            $file_path = $row['file_path'];
            if (!empty($file_path) && file_exists(__DIR__ . '/' . $file_path)) {
                @unlink(__DIR__ . '/' . $file_path);
            }
            $gambar_path = $row['gambar'];
            if (!empty($gambar_path) && file_exists(__DIR__ . '/' . $gambar_path)) {
                @unlink(__DIR__ . '/' . $gambar_path);
            }
        }
        
        $stmt = $conn->prepare("DELETE FROM `berita` WHERE `id` = ?");
        $stmt->bind_param("i", $id);
        
        if ($stmt->execute()) {
            header("Location: admin_dashboard?msg=news_deleted&tab=news");
        } else {
            header("Location: admin_dashboard?msg=error&tab=news");
        }
        $stmt->close();
        exit;
    }

    // ----------------------------------------------------------------------
    // AKSI 5: HAPUS JADWAL / EVENT SPESIAL
    // ----------------------------------------------------------------------
    if ($action === 'delete_schedule' && isset($_GET['id'])) {
        $id = intval($_GET['id']);
        
        $stmt = $conn->prepare("DELETE FROM `jadwal` WHERE `id` = ?");
        $stmt->bind_param("i", $id);
        
        if ($stmt->execute()) {
            header("Location: admin_dashboard?msg=schedule_deleted&tab=schedules");
        } else {
            header("Location: admin_dashboard?msg=error&tab=schedules");
        }
        $stmt->close();
        exit;
    }

    // ----------------------------------------------------------------------
    // AKSI 6: HAPUS LAGU (KOMPOSISI)
    // ----------------------------------------------------------------------
    if ($action === 'delete_komposisi' && isset($_GET['id'])) {
        $id = intval($_GET['id']);
        
        // Ambil info src dan cover untuk menghapus file fisik
        $res = $conn->query("SELECT `src`, `cover` FROM `komposisi` WHERE `id` = $id");
        if ($res && $res->num_rows > 0) {
            $row = $res->fetch_assoc();
            
            $audio_path = $row['src'];
            if (!empty($audio_path) && file_exists(__DIR__ . '/' . $audio_path)) {
                @unlink(__DIR__ . '/' . $audio_path);
            }
            
            $cover_path = $row['cover'];
            if (!empty($cover_path) && file_exists(__DIR__ . '/' . $cover_path)) {
                @unlink(__DIR__ . '/' . $cover_path);
            }
        }
        
        $stmt = $conn->prepare("DELETE FROM `komposisi` WHERE `id` = ?");
        $stmt->bind_param("i", $id);
        
        if ($stmt->execute()) {
            header("Location: admin_dashboard?msg=komposisi_deleted&tab=compositions");
        } else {
            header("Location: admin_dashboard?msg=error&tab=compositions");
        }
        $stmt->close();
        exit;
    }

    // ----------------------------------------------------------------------
    // AKSI 4: RESET DATABASE (MEMBERSIHKAN SEMUA DATA UNTUK DEMO/TESTING)
    // ----------------------------------------------------------------------
    if ($action === 'reset') {
        $conn->begin_transaction();
        try {
            // Hapus seluruh data pesanan
            $conn->query("DELETE FROM `pesanan`");
            
            // Reset kolom terjual jadwal ke 0
            $conn->query("UPDATE `jadwal` SET `terjual` = 0");
            
            $conn->commit();
            header("Location: admin_dashboard?msg=reset_success");
            exit;
        } catch (Exception $e) {
            $conn->rollback();
            header("Location: admin_dashboard?msg=error");
            exit;
        }
    }
}

// Jika parameter tidak valid, balikkan ke dashboard
header("Location: admin_dashboard");
exit;
?>
