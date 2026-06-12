<?php
/**
 * STANDALONE ARTICLE PAGE (GENERATED AUTOMATICALLY)
 * ------------------------------------------------
 * Artikel: The 4 Stairs Band 5th Anniversary Special Show
 * Template: concert
 */
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>The 4 Stairs Band 5th Anniversary Special Show - The 4 Stairs Music Hall</title>
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
<body class="bg-[#050506] text-[#eef2f7] font-body flex flex-col min-h-screen overflow-x-hidden pt-24">

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

        <article class="bg-jazz-card/90 border border-jazz-gold/30 rounded-3xl shadow-[0_0_50px_rgba(139,30,34,0.05)] p-8 md:p-12 relative overflow-hidden">
            
            <div class="absolute top-0 left-0 w-full h-1.5 bg-gradient-to-r from-jazz-gold via-jazz-crimson to-jazz-goldDark"></div>
            <div class="pb-6 mb-8 border-b border-white/10">
                <span class="px-2.5 py-1 bg-gradient-to-r from-jazz-gold to-jazz-goldDark text-white text-[10px] uppercase font-extrabold tracking-widest inline-block mb-4 rounded-full shadow-lg shadow-jazz-gold/20">CONCERT SPECIAL EVENT</span>
                <h1 class="font-heading text-3xl md:text-5xl font-bold tracking-tight text-white leading-tight mb-4 drop-shadow-[0_2px_10px_rgba(0,0,0,0.5)]">The 4 Stairs Band 5th Anniversary Special Show</h1>
                <div class="flex items-center gap-2 text-xs text-jazz-muted">
                    <svg class="h-4 w-4 text-jazz-gold" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                    </svg>
                    <span>11 Jun 2026 - 23:32</span>
                    <span class="text-white/20">&bull;</span>
                    <span class="text-jazz-gold font-medium">Exclusive Live Stage</span>
                </div>
            </div>
        
            
            
            
            <div class="prose prose-invert max-w-none text-justify text-gray-300 leading-relaxed font-light">
                <p class="mb-6 leading-relaxed text-sm md:text-base font-light">Dalam rangka merayakan hari jadi yang ke-5, The 4 Stairs Band akan mengadakan pertunjukan spesial yang intim sepanjang akhir pekan ini. Kami akan mengundang beberapa rekan musisi jazz indie ternama untuk ikut menyumbang melodi di panggung utama kami. Tiket sangat terbatas!</p>
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