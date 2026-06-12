<?php
/**
 * THE 4 STAIRS - DEDICATED BERITA LISTING PAGE
 * -------------------------------------------
 * Menampilkan seluruh daftar berita dan pengumuman dari database.
 */

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

include 'koneksi.php';

$berita_list = [];
if (isset($conn) && !$conn->connect_error && $db_selected) {
    // Ambil semua berita diurutkan berdasarkan tanggal posting terbaru
    $res = $conn->query("SELECT * FROM `berita` ORDER BY `tanggal_post` DESC");
    if ($res) {
        while ($row = $res->fetch_assoc()) {
            $berita_list[] = $row;
        }
    }
}
?>
<!DOCTYPE html>
<html lang="id" class="scroll-smooth">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Berita & Pengumuman - The 4 Stairs Music Hall</title>
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
</head>
<body class="bg-jazz-darkest text-jazz-light font-body flex flex-col min-h-screen overflow-x-hidden pt-24">

    <!-- Reusable Navbar -->
    <?php include 'navbar.php'; ?>

    <!-- Main Container -->
    <main class="max-w-6xl mx-auto px-6 py-12 flex-grow w-full">
        
        <!-- Header Section -->
        <div class="text-center mb-16">
            <span class="text-jazz-gold text-xs font-semibold uppercase tracking-widest mb-3 block">News & Updates</span>
            <h1 class="font-heading text-4xl md:text-5xl font-bold text-white mb-4">Berita Terbaru</h1>
            <p class="text-jazz-muted text-sm md:text-base max-w-2xl mx-auto font-light leading-relaxed">
                Ikuti terus kabar terbaru mengenai konser, jadwal musisi tamu, dan informasi penting lainnya langsung dari The 4 Stairs Music Hall.
            </p>
        </div>

        <!-- Berita Grid List -->
        <?php if (empty($berita_list)): ?>
            <div class="text-center py-20 text-jazz-muted italic">
                <p>Belum ada berita atau pengumuman yang diterbitkan saat ini.</p>
                <a href="index" class="text-jazz-gold hover:text-white font-semibold text-xs mt-4 inline-block underline">&larr; Kembali ke Beranda</a>
            </div>
        <?php else: ?>
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8">
                <?php foreach ($berita_list as $berita): 
                    $target_link = !empty($berita['file_path']) ? str_replace('.php', '', $berita['file_path']) : 'articles/berita_' . $berita['id'];
                    $target_link = htmlspecialchars($target_link);
                ?>
                    <div class="glass-card rounded-2xl border border-jazz-gold/10 hover:border-jazz-gold/30 hover:shadow-xl transition-all duration-300 flex flex-col justify-between overflow-hidden relative group">
                        
                        <!-- Image Block -->
                        <?php if (!empty($berita['gambar']) && file_exists(__DIR__ . '/' . $berita['gambar'])): ?>
                            <div class="w-full h-52 overflow-hidden relative">
                                <a href="<?php echo $target_link; ?>">
                                    <img src="<?php echo htmlspecialchars($berita['gambar']); ?>" alt="<?php echo htmlspecialchars($berita['judul']); ?>" class="w-full h-full object-cover transform group-hover:scale-105 transition-transform duration-500">
                                </a>
                            </div>
                        <?php else: ?>
                            <!-- Fallback empty/decorative block -->
                            <div class="w-full h-36 bg-gradient-to-br from-jazz-card to-jazz-darkest border-b border-white/5 flex items-center justify-center">
                                <svg class="w-8 h-8 text-jazz-gold/20" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1" d="M19 20H5a2 2 0 01-2-2V6a2 2 0 012-2h10a2 2 0 012 2v1m2 13a2 2 0 01-2-2V7m2 13a2 2 0 002-2V9a2 2 0 00-2-2h-2m-4-3H9M7 16h6M7 8h6v4H7V8z" />
                                </svg>
                            </div>
                        <?php endif; ?>

                        <!-- Content Block -->
                        <div class="p-6 flex-grow flex flex-col justify-between">
                            <div>
                                <span class="text-[10px] text-jazz-gold font-bold tracking-widest uppercase mb-2.5 block">
                                    <?php echo date('d F Y', strtotime($berita['tanggal_post'])); ?>
                                </span>
                                <h3 class="text-white font-heading font-bold text-lg mb-3 tracking-wide group-hover:text-jazz-gold transition-colors duration-300">
                                    <a href="<?php echo $target_link; ?>">
                                        <?php echo htmlspecialchars($berita['judul']); ?>
                                    </a>
                                </h3>
                                <p class="text-jazz-muted text-xs md:text-sm font-light leading-relaxed mb-6">
                                    <?php 
                                        $konten_plain = strip_tags($berita['konten']);
                                        echo (strlen($konten_plain) > 160) ? substr($konten_plain, 0, 157) . '...' : $konten_plain; 
                                    ?>
                                </p>
                            </div>
                            <div>
                                <a href="<?php echo $target_link; ?>" class="text-jazz-gold hover:text-white text-xs font-semibold tracking-wide inline-flex items-center gap-1 transition-colors duration-300">
                                    Baca Artikel Lengkap &rarr;
                                </a>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

    </main>

    <!-- Footer Section -->
    <footer class="bg-jazz-darkest border-t border-jazz-gold/10 py-12">
        <div class="max-w-6xl mx-auto px-6 flex flex-col md:flex-row items-center justify-between gap-6">
            <div class="text-center md:text-left">
                <span class="font-heading text-lg font-bold tracking-wider text-jazz-gold">
                    THE 4 <span class="text-white">STAIRS</span>
                </span>
                <p class="text-jazz-muted text-[10px] mt-1 font-light">Live Music Venue & Concert Hall</p>
            </div>
            <div class="text-center md:text-right">
                <p class="text-jazz-muted text-xs font-light">&copy; <?php echo date('Y'); ?> The 4 Stairs Music Hall. All rights reserved.</p>
            </div>
        </div>
    </footer>

    <script src="assets/js/main.js"></script>
</body>
</html>
