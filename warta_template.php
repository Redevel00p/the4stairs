<?php
/**
 * THE 4 STAIRS MUSIC HALL - ARTICLE LAYOUT TEMPLATES
 * ----------------------------------------------
 * Mendefinisikan template visual (Classic, Retro, Concert) untuk artikel berita.
 * Fungsi generate_article_html menghasilkan kode PHP/HTML lengkap untuk disimpan
 * sebagai file fisik di folder berita/.
 */

function generate_article_html($judul, $konten, $tanggal_post, $template_name = 'classic', $gambar_path = null) {
    $tgl_fmt = date('d M Y - H:i', strtotime($tanggal_post));
    $escaped_judul = htmlspecialchars($judul, ENT_QUOTES, 'UTF-8');
    
    // Construct image HTML if present
    $image_html = '';
    if (!empty($gambar_path)) {
        $resolved_img_path = '../' . $gambar_path;
        if ($template_name === 'retro') {
            $image_html = "
            <div class=\"mb-8 border-4 border-jazz-gold/30 p-2 bg-[#141211] shadow-lg max-w-2xl mx-auto\">
                <img src=\"{$resolved_img_path}\" alt=\"{$escaped_judul}\" class=\"w-full h-auto object-cover grayscale-[20%] sepia-[10%] hover:grayscale-0 transition-all duration-500\">
            </div>
            ";
        } elseif ($template_name === 'concert') {
            $image_html = "
            <div class=\"mb-8 rounded-2xl overflow-hidden shadow-2xl border border-white/10 relative group max-w-2xl mx-auto\">
                <img src=\"{$resolved_img_path}\" alt=\"{$escaped_judul}\" class=\"w-full h-auto object-cover transform group-hover:scale-105 transition-all duration-700\">
                <div class=\"absolute inset-0 bg-gradient-to-t from-black/50 via-transparent to-transparent opacity-60\"></div>
            </div>
            ";
        } else {
            $image_html = "
            <div class=\"mb-8 rounded-xl overflow-hidden border border-jazz-gold/20 shadow-xl max-w-2xl mx-auto\">
                <img src=\"{$resolved_img_path}\" alt=\"{$escaped_judul}\" class=\"w-full h-auto object-cover\">
            </div>
            ";
        }
    }
    
    // Konversi newline di konten ke paragraf atau gunakan HTML langsung
    $konten_html = '';
    if (strip_tags($konten) === $konten) {
        $paragraphs = explode("\n", $konten);
        foreach ($paragraphs as $para) {
            $para = trim($para);
            if ($para !== '') {
                $konten_html .= "<p class=\"mb-6 leading-relaxed text-sm md:text-base font-light\">" . htmlspecialchars($para, ENT_QUOTES, 'UTF-8') . "</p>";
            }
        }
    } else {
        $konten_html = $konten;
    }

    // Pemilihan style berdasarkan template
    $theme_classes = '';
    $header_section = '';
    $card_style = '';

    if ($template_name === 'retro') {
        // RETRO THEME: Maroon-gold gradients, typewriter feel, warm sepia tones
        $theme_classes = 'bg-[#0f0a08] text-[#f7f2ea]';
        $card_style = 'bg-[#141211] border-2 border-dashed border-jazz-gold/45 rounded-none shadow-[8px_8px_0px_0px_rgba(139,30,34,0.2)] p-8 md:p-12';
        $header_section = "
            <div class=\"border-b-2 border-dashed border-jazz-gold/20 pb-6 mb-8\">
                <span class=\"px-3 py-1 bg-jazz-crimson text-white text-[10px] uppercase font-bold tracking-widest inline-block mb-4 rounded-sm\">RETRO BAND ARCHIVE</span>
                <h1 class=\"font-heading text-3xl md:text-5xl font-bold tracking-normal text-jazz-light leading-tight mb-3\">{$escaped_judul}</h1>
                <div class=\"text-xs text-jazz-muted font-mono\">Diterbitkan pada: {$tgl_fmt} &bull; The 4 Stairs Music Hall</div>
            </div>
        ";
    } elseif ($template_name === 'concert') {
        // CONCERT THEME: Neon concert glow, bold sans headers, energetic atmosphere
        $theme_classes = 'bg-[#050506] text-[#eef2f7]';
        $card_style = 'bg-jazz-card/90 border border-jazz-gold/30 rounded-3xl shadow-[0_0_50px_rgba(139,30,34,0.05)] p-8 md:p-12 relative overflow-hidden';
        $header_section = "
            <div class=\"absolute top-0 left-0 w-full h-1.5 bg-gradient-to-r from-jazz-gold via-jazz-crimson to-jazz-goldDark\"></div>
            <div class=\"pb-6 mb-8 border-b border-white/10\">
                <span class=\"px-2.5 py-1 bg-gradient-to-r from-jazz-gold to-jazz-goldDark text-white text-[10px] uppercase font-extrabold tracking-widest inline-block mb-4 rounded-full shadow-lg shadow-jazz-gold/20\">CONCERT SPECIAL EVENT</span>
                <h1 class=\"font-heading text-3xl md:text-5xl font-bold tracking-tight text-white leading-tight mb-4 drop-shadow-[0_2px_10px_rgba(0,0,0,0.5)]\">{$escaped_judul}</h1>
                <div class=\"flex items-center gap-2 text-xs text-jazz-muted\">
                    <svg class=\"h-4 w-4 text-jazz-gold\" fill=\"none\" viewBox=\"0 0 24 24\" stroke=\"currentColor\">
                        <path stroke-linecap=\"round\" stroke-linejoin=\"round\" stroke-width=\"2\" d=\"M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z\" />
                    </svg>
                    <span>{$tgl_fmt}</span>
                    <span class=\"text-white/20\">&bull;</span>
                    <span class=\"text-jazz-gold font-medium\">Exclusive Live Stage</span>
                </div>
            </div>
        ";
    } else {
        // CLASSIC THEME (Default): Elegant serif typography, cozy gold decorations
        $theme_classes = 'bg-[#090706] text-jazz-light';
        $card_style = 'glass-card rounded-2xl border border-jazz-gold/20 p-8 md:p-12 shadow-2xl';
        $header_section = "
            <div class=\"text-center pb-8 mb-8 border-b border-jazz-gold/10\">
                <span class=\"text-jazz-gold text-xs font-semibold uppercase tracking-widest mb-3 block\">Berita</span>
                <h1 class=\"font-heading text-3xl md:text-5xl font-bold tracking-wide text-white leading-tight mb-4\">{$escaped_judul}</h1>
                <div class=\"text-xs text-jazz-muted font-light flex items-center justify-center gap-1.5\">
                    <span>The 4 Stairs Music Hall</span>
                    <span>&bull;</span>
                    <span>{$tgl_fmt}</span>
                </div>
            </div>
        ";
    }

    $html_code = "<?php
/**
 * STANDALONE ARTICLE PAGE (GENERATED AUTOMATICALLY)
 * ------------------------------------------------
 * Artikel: {$escaped_judul}
 * Template: {$template_name}
 */
?>
<!DOCTYPE html>
<html lang=\"id\">
<head>
    <meta charset=\"UTF-8\">
    <meta name=\"viewport\" content=\"width=device-width, initial-scale=1.0\">
    <title>{$escaped_judul} - The 4 Stairs Music Hall</title>
    <!-- Tailwind CSS CDN -->
    <script src=\"https://cdn.tailwindcss.com\"></script>
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
    <link rel=\"stylesheet\" href=\"../assets/css/style.css\">
</head>
<body class=\"{$theme_classes} font-body flex flex-col min-h-screen overflow-x-hidden pt-24\">

    <?php
    \$nav_prefix = '../';
    include '../navbar.php';
    ?>

    <!-- Main Content Container -->
    <main class=\"max-w-4xl mx-auto px-6 py-12 flex-grow w-full\">
        <div class=\"mb-6\">
            <a href=\"../index\" class=\"inline-flex items-center gap-2 text-xs text-jazz-gold hover:text-white transition-colors duration-300\">
                &larr; Kembali ke Beranda
            </a>
        </div>

        <article class=\"{$card_style}\">
            {$header_section}
            
            {$image_html}
            
            <div class=\"prose prose-invert max-w-none text-justify text-gray-300 leading-relaxed font-light\">
                {$konten_html}
            </div>

            <!-- Decorative signature/footer for retro/classic -->
            <div class=\"mt-12 pt-6 border-t border-white/5 flex flex-col sm:flex-row justify-between items-center gap-4 text-xs text-jazz-muted\">
                <div>The 4 Stairs &bull; Live Music Stage</div>
                <div class=\"flex items-center gap-2\">
                    <a href=\"../pesan\" class=\"px-4 py-2 bg-jazz-gold/10 hover:bg-jazz-gold text-jazz-gold hover:text-jazz-darkest font-semibold rounded-md transition-all duration-300 uppercase tracking-wider text-[10px]\">Reservasi Tiket &rarr;</a>
                </div>
            </div>
        </article>
    </main>

    <!-- Footer Section -->
    <footer class=\"bg-jazz-darkest border-t border-jazz-gold/10 py-10 mt-20\">
        <div class=\"max-w-6xl mx-auto px-6 flex flex-col md:flex-row items-center justify-between gap-6\">
            <div class=\"text-center md:text-left\">
                <span class=\"font-heading text-lg font-bold tracking-wider text-jazz-gold\">
                    THE 4 <span class=\"text-white\">STAIRS</span>
                </span>
                <p class=\"text-jazz-muted text-[10px] mt-1 font-light\">Live Music Venue & Concert Hall</p>
            </div>
            <div class=\"text-center md:text-right\">
                <p class=\"text-jazz-muted text-xs font-light\">&copy; <?php echo date('Y'); ?> The 4 Stairs Music Hall. All rights reserved.</p>
            </div>
        </div>
    </footer>
</body>
</html>";

    return $html_code;
}
