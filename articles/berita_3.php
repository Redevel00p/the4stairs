<?php
/**
 * STANDALONE ARTICLE PAGE (GENERATED AUTOMATICALLY)
 * ------------------------------------------------
 * Artikel: Info Protokol Kenyamanan Show
 * Template: classic
 */
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Info Protokol Kenyamanan Show - The 4 Stairs Music Hall</title>
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
    <!-- Custom CSS -->
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body class="bg-[#090706] text-jazz-light font-body flex flex-col min-h-screen overflow-x-hidden pt-24">

    <?php
    $nav_prefix = '../';
    include '../navbar.php';
    ?>

    <!-- Main Content Container -->
    <main class="max-w-4xl mx-auto px-6 py-12 flex-grow w-full">
        <div class="mb-6">
            <a href="../index" class="inline-flex items-center gap-2 text-xs text-jazz-gold hover:text-white transition-colors duration-300">
                &larr; Kembali ke Beranda
            </a>
        </div>

        <article class="glass-card rounded-2xl border border-jazz-gold/20 p-8 md:p-12 shadow-2xl">
            
            <div class="text-center pb-8 mb-8 border-b border-jazz-gold/10">
                <span class="text-jazz-gold text-xs font-semibold uppercase tracking-widest mb-3 block">Berita</span>
                <h1 class="font-heading text-3xl md:text-5xl font-bold tracking-wide text-white leading-tight mb-4">Info Protokol Kenyamanan Show</h1>
                <div class="text-xs text-jazz-muted font-light flex items-center justify-center gap-1.5">
                    <span>The 4 Stairs Music Hall</span>
                    <span>&bull;</span>
                    <span>11 Jun 2026 - 23:32</span>
                </div>
            </div>
        
            
            
            
            <div class="prose prose-invert max-w-none text-justify text-gray-300 leading-relaxed font-light">
                <p class="mb-6 leading-relaxed text-sm md:text-base font-light">Demi menjaga atmosfer intim dan kenyamanan akustik di The 4 Stairs Music Hall, kami menghimbau para tamu untuk mengenakan pakaian Smart Casual. Pintu ruang pertunjukan akan dibuka 30 menit sebelum acara dimulai.</p>
            </div>

            <!-- Decorative signature/footer for retro/classic -->
            <div class="mt-12 pt-6 border-t border-white/5 flex flex-col sm:flex-row justify-between items-center gap-4 text-xs text-jazz-muted">
                <div>The 4 Stairs &bull; Live Music Stage</div>
                <div class="flex items-center gap-2">
                    <a href="../pesan" class="px-4 py-2 bg-jazz-gold/10 hover:bg-jazz-gold text-jazz-gold hover:text-jazz-darkest font-semibold rounded-md transition-all duration-300 uppercase tracking-wider text-[10px]">Reservasi Tiket &rarr;</a>
                </div>
            </div>
        </article>
    </main>

    <!-- Footer Section -->
    <footer class="bg-jazz-darkest border-t border-jazz-gold/10 py-10 mt-20">
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
</body>
</html>