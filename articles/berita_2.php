<?php
/**
 * STANDALONE ARTICLE PAGE (GENERATED AUTOMATICALLY)
 * ------------------------------------------------
 * Artikel: Malam Minggu Romantis: Tribute to Swing Era
 * Template: retro
 */
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Malam Minggu Romantis: Tribute to Swing Era - The 4 Stairs Music Hall</title>
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
<body class="bg-[#0f0a08] text-[#f7f2ea] font-body flex flex-col min-h-screen overflow-x-hidden pt-24">

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

        <article class="bg-[#141211] border-2 border-dashed border-jazz-gold/45 rounded-none shadow-[8px_8px_0px_0px_rgba(139,30,34,0.2)] p-8 md:p-12">
            
            <div class="border-b-2 border-dashed border-jazz-gold/20 pb-6 mb-8">
                <span class="px-3 py-1 bg-jazz-crimson text-white text-[10px] uppercase font-bold tracking-widest inline-block mb-4 rounded-sm">RETRO BAND ARCHIVE</span>
                <h1 class="font-heading text-3xl md:text-5xl font-bold tracking-normal text-jazz-light leading-tight mb-3">Malam Minggu Romantis: Tribute to Swing Era</h1>
                <div class="text-xs text-jazz-muted font-mono">Diterbitkan pada: 11 Jun 2026 - 23:32 &bull; The 4 Stairs Music Hall</div>
            </div>
        
            
            
            
            <div class="prose prose-invert max-w-none text-justify text-gray-300 leading-relaxed font-light">
                <p class="mb-6 leading-relaxed text-sm md:text-base font-light">Kembali ke era kejayaan swing bersama alunan brass section yang megah dan vokal syahdu. Acara ini akan menampilkan kolaborasi khusus antara komposer internal kami dengan bintang tamu saxophone terkenal. Reservasi tiket masuk Anda sekarang!</p>
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