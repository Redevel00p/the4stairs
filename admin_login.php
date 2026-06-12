<?php
/**
 * THE 4 STAIRS MUSIC HALL - ADMIN LOGIN
 * -------------------------------------
 * Halaman otentikasi admin. Memverifikasi kredensial
 * terhadap tabel 'admin' menggunakan enkripsi password_verify.
 */

// Memulai session
session_start();

// Jika admin sudah login, langsung alihkan ke dashboard
if (isset($_SESSION['admin_logged_in']) && $_SESSION['admin_logged_in'] === true) {
    header("Location: admin_dashboard");
    exit;
}

// Sertakan file koneksi database
include 'koneksi.php';

$error_msg = "";

if (isset($_POST['login'])) {
    $username = $conn->real_escape_string(trim($_POST['username']));
    $password = trim($_POST['password']);

    if (empty($username) || empty($password)) {
        $error_msg = "Username dan password tidak boleh kosong.";
    } else {
        // Cari user admin di database
        $stmt = $conn->prepare("SELECT * FROM `admin` WHERE `username` = ?");
        $stmt->bind_param("s", $username);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result && $result->num_rows > 0) {
            $admin_data = $result->fetch_assoc();
            
            // Cocokkan password terenkripsi
            if (password_verify($password, $admin_data['password'])) {
                // Simpan status login di session
                $_SESSION['admin_logged_in'] = true;
                $_SESSION['admin_username'] = $admin_data['username'];
                
                // Alihkan ke halaman dashboard admin
                header("Location: admin_dashboard");
                exit;
            } else {
                $error_msg = "Password yang Anda masukkan salah.";
            }
        } else {
            $error_msg = "Username tidak terdaftar.";
        }
        $stmt->close();
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login Panitia - The 4 Stairs Music Hall</title>
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
    </style>
</head>
<body class="bg-retro-black text-retro-light font-body flex items-center justify-center min-h-screen p-4">

    <div class="w-full max-w-md bg-retro-card border border-stone-800/80 rounded-2xl p-8 md:p-10 shadow-2xl relative overflow-hidden">
        <!-- Top decorative color bar -->
        <div class="absolute top-0 left-0 w-full h-[4px] bg-gradient-to-r from-retro-red via-retro-brown to-retro-red"></div>

        <div class="text-center mb-8">
            <h2 class="font-heading text-3xl font-bold tracking-wide text-white mb-2">PANITIA LOGIN</h2>
            <p class="text-retro-red font-semibold text-xs tracking-widest uppercase">The 4 Stairs Dashboard</p>
        </div>

        <?php if (!empty($error_msg)): ?>
            <div class="bg-retro-red/10 border border-retro-red/30 rounded-lg p-4 mb-6 text-retro-red text-sm text-center font-medium">
                <?php echo $error_msg; ?>
            </div>
        <?php endif; ?>

        <form action="admin_login" method="POST" class="space-y-6">
            <div class="space-y-2">
                <label for="username" class="block text-xs font-semibold uppercase tracking-wider text-retro-muted">Username</label>
                <input type="text" name="username" id="username" placeholder="Masukkan username" required autocomplete="off"
                       class="w-full px-4 py-3 bg-retro-input border border-stone-800 rounded-lg text-white placeholder-stone-600 focus:outline-none focus:border-retro-red focus:ring-1 focus:ring-retro-red transition-all duration-300">
            </div>

            <div class="space-y-2">
                <label for="password" class="block text-xs font-semibold uppercase tracking-wider text-retro-muted">Password</label>
                <input type="password" name="password" id="password" placeholder="Masukkan password" required
                       class="w-full px-4 py-3 bg-retro-input border border-stone-800 rounded-lg text-white placeholder-stone-600 focus:outline-none focus:border-retro-red focus:ring-1 focus:ring-retro-red transition-all duration-300">
            </div>

            <button type="submit" name="login" 
                    class="w-full py-3.5 bg-retro-red hover:bg-retro-redAccent active:scale-[0.98] text-white font-bold uppercase tracking-wider text-xs rounded-lg shadow-lg hover:shadow-retro-red/10 transition-all duration-300">
                Masuk Ke Dashboard
            </button>
            
            <div class="text-center pt-4">
                <a href="index" class="text-retro-muted hover:text-white text-xs transition-colors duration-300 flex items-center justify-center gap-1">
                    <span>&larr;</span> Buka Website Utama
                </a>
            </div>
        </form>
    </div>

</body>
</html>
