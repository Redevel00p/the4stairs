<?php
/**
 * THE 4 STAIRS MUSIC HALL - USER REGISTRATION
 * -------------------------------------------
 * Halaman registrasi untuk penonton/pemesan tiket biasa.
 * Menyimpan data pemesan dengan password terenkripsi.
 */

// Memulai session
session_start();

// Jika sudah login, langsung alihkan
if (isset($_SESSION['user_logged_in']) && $_SESSION['user_logged_in'] === true) {
    header("Location: index");
    exit;
} elseif (isset($_SESSION['admin_logged_in']) && $_SESSION['admin_logged_in'] === true) {
    header("Location: admin_dashboard");
    exit;
}

// Sertakan file koneksi database
include 'koneksi.php';

$error_msg = "";
$success_msg = "";

if (isset($_POST['register'])) {
    $name = $conn->real_escape_string(trim($_POST['name']));
    $email = $conn->real_escape_string(trim($_POST['email']));
    $no_wa = $conn->real_escape_string(trim($_POST['no_wa']));
    $password = trim($_POST['password']);
    $confirm_password = trim($_POST['confirm_password']);

    if (empty($name) || empty($email) || empty($no_wa) || empty($password)) {
        $error_msg = "Harap isi semua kolom pendaftaran.";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error_msg = "Format alamat email tidak valid.";
    } elseif ($password !== $confirm_password) {
        $error_msg = "Konfirmasi password tidak cocok.";
    } elseif (strlen($password) < 6) {
        $error_msg = "Password harus minimal 6 karakter.";
    } else {
        // Cek apakah email sudah terdaftar
        $stmt_check = $conn->prepare("SELECT `id` FROM `users` WHERE `email` = ?");
        $stmt_check->bind_param("s", $email);
        $stmt_check->execute();
        $res_check = $stmt_check->get_result();
        
        if ($res_check && $res_check->num_rows > 0) {
            $error_msg = "Alamat email ini sudah terdaftar. Silakan login.";
        } else {
            // Hash password
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);
            
            // Simpan ke database
            $stmt_insert = $conn->prepare("INSERT INTO `users` (`name`, `email`, `password`, `no_wa`) VALUES (?, ?, ?, ?)");
            $stmt_insert->bind_param("ssss", $name, $email, $hashed_password, $no_wa);
            
            if ($stmt_insert->execute()) {
                $user_id = $stmt_insert->insert_id;
                
                // Set session agar langsung login
                $_SESSION['user_logged_in'] = true;
                $_SESSION['user_id'] = $user_id;
                $_SESSION['user_name'] = $name;
                $_SESSION['user_email'] = $email;
                $_SESSION['user_wa'] = $no_wa;
                
                header("Location: index");
                exit;
            } else {
                $error_msg = "Terjadi kesalahan saat menyimpan data. Silakan coba lagi.";
            }
            $stmt_insert->close();
        }
        $stmt_check->close();
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pendaftaran Akun - The 4 Stairs Music Hall</title>
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
        /* Custom scrollbar */
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
    </style>
</head>
<body class="bg-retro-black text-retro-light font-body flex items-center justify-center min-h-screen p-4">

    <div class="w-full max-w-md bg-retro-card border border-stone-800/80 rounded-2xl p-8 md:p-10 shadow-2xl relative overflow-hidden my-8">
        <!-- Top decorative color bar -->
        <div class="absolute top-0 left-0 w-full h-[4px] bg-gradient-to-r from-retro-red via-retro-brown to-retro-red"></div>

        <div class="text-center mb-8">
            <h2 class="font-heading text-3xl font-bold tracking-wide text-white mb-2">BUAT AKUN</h2>
            <p class="text-retro-red font-semibold text-xs tracking-widest uppercase">Registrasi Penonton The 4 Stairs</p>
        </div>

        <?php if (!empty($error_msg)): ?>
            <div class="bg-retro-red/10 border border-retro-red/30 rounded-lg p-4 mb-6 text-retro-red text-sm text-center font-medium">
                <?php echo $error_msg; ?>
            </div>
        <?php endif; ?>

        <form action="register" method="POST" class="space-y-4">
            <div class="space-y-1">
                <label for="name" class="block text-xs font-semibold uppercase tracking-wider text-retro-muted">Nama Lengkap</label>
                <input type="text" name="name" id="name" placeholder="Nama lengkap Anda" required autocomplete="off"
                       value="<?php echo isset($_POST['name']) ? htmlspecialchars($_POST['name']) : ''; ?>"
                       class="w-full px-4 py-2.5 bg-retro-input border border-stone-800 rounded-lg text-white placeholder-stone-600 focus:outline-none focus:border-retro-red focus:ring-1 focus:ring-retro-red transition-all duration-300 text-sm">
            </div>

            <div class="space-y-1">
                <label for="email" class="block text-xs font-semibold uppercase tracking-wider text-retro-muted">Alamat Email</label>
                <input type="email" name="email" id="email" placeholder="contoh@email.com" required autocomplete="off"
                       value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : ''; ?>"
                       class="w-full px-4 py-2.5 bg-retro-input border border-stone-800 rounded-lg text-white placeholder-stone-600 focus:outline-none focus:border-retro-red focus:ring-1 focus:ring-retro-red transition-all duration-300 text-sm">
            </div>

            <div class="space-y-1">
                <label for="no_wa" class="block text-xs font-semibold uppercase tracking-wider text-retro-muted">Nomor WhatsApp</label>
                <input type="text" name="no_wa" id="no_wa" placeholder="Contoh: 08123456789" required autocomplete="off"
                       value="<?php echo isset($_POST['no_wa']) ? htmlspecialchars($_POST['no_wa']) : ''; ?>"
                       class="w-full px-4 py-2.5 bg-retro-input border border-stone-800 rounded-lg text-white placeholder-stone-600 focus:outline-none focus:border-retro-red focus:ring-1 focus:ring-retro-red transition-all duration-300 text-sm">
            </div>

            <div class="space-y-1">
                <label for="password" class="block text-xs font-semibold uppercase tracking-wider text-retro-muted">Password</label>
                <input type="password" name="password" id="password" placeholder="Minimal 6 karakter" required
                       class="w-full px-4 py-2.5 bg-retro-input border border-stone-800 rounded-lg text-white placeholder-stone-600 focus:outline-none focus:border-retro-red focus:ring-1 focus:ring-retro-red transition-all duration-300 text-sm">
            </div>

            <div class="space-y-1">
                <label for="confirm_password" class="block text-xs font-semibold uppercase tracking-wider text-retro-muted">Konfirmasi Password</label>
                <input type="password" name="confirm_password" id="confirm_password" placeholder="Ulangi password" required
                       class="w-full px-4 py-2.5 bg-retro-input border border-stone-800 rounded-lg text-white placeholder-stone-600 focus:outline-none focus:border-retro-red focus:ring-1 focus:ring-retro-red transition-all duration-300 text-sm">
            </div>

            <button type="submit" name="register" 
                    class="w-full py-3 bg-retro-red hover:bg-retro-redAccent active:scale-[0.98] text-white font-bold uppercase tracking-wider text-xs rounded-lg shadow-lg hover:shadow-retro-red/10 transition-all duration-300 mt-2 cursor-pointer">
                Daftar Akun
            </button>
            
            <div class="text-center pt-3 text-xs text-retro-muted">
                Sudah punya akun? <a href="login" class="text-retro-red hover:underline font-semibold">Masuk disini</a>
            </div>
            
            <div class="text-center pt-2">
                <a href="index" class="text-retro-muted hover:text-white text-xs transition-colors duration-300 flex items-center justify-center gap-1">
                    <span>&larr;</span> Kembali ke Beranda
                </a>
            </div>
        </form>
    </div>

</body>
</html>
