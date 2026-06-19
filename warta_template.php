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
    <footer class=\"bg-jazz-darkest border-t border-jazz-gold/10 pt-16 pb-8 relative overflow-hidden\">
        <!-- Background decorative glow -->
        <div class=\"absolute -bottom-24 -left-24 w-80 h-80 rounded-full bg-jazz-gold/5 filter blur-[80px] pointer-events-none\"></div>
        
        <div class=\"max-w-6xl mx-auto px-6 grid grid-cols-1 md:grid-cols-12 gap-8 mb-12\">
            <!-- Col 1: Brand (4 cols) -->
            <div class=\"md:col-span-4 text-left\">
                <a href=\"<?php echo \$prefix; ?>index\" class=\"flex items-center gap-2 font-heading text-xl font-bold tracking-wider text-jazz-gold mb-4\">
                    <img src=\"<?php echo \$prefix; ?>4stairswhite.png\" alt=\"Logo\" class=\"w-8 h-8 object-contain\">
                    <span>THE 4 <span class=\"text-white\">STAIRS</span></span>
                </a>
                <p class=\"text-jazz-muted text-xs font-light leading-relaxed mb-6 max-w-sm\">
                    Live Music Venue & Concert Hall terbaik di Yogyakarta. Menghadirkan pertunjukan musik berkelas dengan atmosfer klasik yang intim dan akustik premium.
                </p>
                <div class=\"flex items-center gap-3\">
                    <!-- WhatsApp -->
                    <a href=\"https://wa.me/628123456789\" target=\"_blank\" class=\"w-8 h-8 rounded-full bg-white/5 border border-white/10 hover:border-green-500 hover:bg-green-500/10 hover:text-green-500 flex items-center justify-center text-gray-400 transition-all duration-300\" title=\"WhatsApp\">
                        <svg class=\"w-4 h-4 fill-current\" viewBox=\"0 0 24 24\">
                            <path d=\"M.057 24l1.687-6.163c-1.041-1.804-1.588-3.849-1.587-5.946C.06 5.348 5.397.01 12.008.01c3.202.001 6.212 1.246 8.477 3.514 2.266 2.268 3.507 5.28 3.505 8.484-.004 6.657-5.34 11.997-11.953 11.997-2.005-.001-3.973-.502-5.724-1.455L0 24zm6.59-4.846c1.6.95 3.488 1.459 5.407 1.46h.007c5.855 0 10.618-4.761 10.621-10.619.002-2.837-1.102-5.505-3.108-7.513C17.569 4.475 14.9 3.37c-5.86 0-10.627 4.76-10.63 10.619-.001 1.884.49 3.73 1.42 5.34L1.968 22.25l4.679-1.256zM17.47 15.1c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347z\"/>
                        </svg>
                    </a>
                    <!-- Email -->
                    <a href=\"mailto:info@the4stairs.com\" class=\"w-8 h-8 rounded-full bg-white/5 border border-white/10 hover:border-amber-500 hover:bg-amber-500/10 hover:text-amber-500 flex items-center justify-center text-gray-400 transition-all duration-300\" title=\"Email\">
                        <svg class=\"w-4 h-4\" fill=\"none\" viewBox=\"0 0 24 24\" stroke=\"currentColor\">
                            <path stroke-linecap=\"round\" stroke-linejoin=\"round\" stroke-width=\"2\" d=\"M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z\"/>
                        </svg>
                    </a>
                    <!-- Facebook -->
                    <a href=\"https://facebook.com/the4stairs\" target=\"_blank\" class=\"w-8 h-8 rounded-full bg-white/5 border border-white/10 hover:border-blue-500 hover:bg-blue-500/10 hover:text-blue-500 flex items-center justify-center text-gray-400 transition-all duration-300\" title=\"Facebook\">
                        <svg class=\"w-4 h-4 fill-current\" viewBox=\"0 0 24 24\">
                            <path d=\"M24 12.073c0-6.627-5.373-12-12-12s-12 5.373-12 12c0 5.99 4.388 10.954 10.125 11.854v-8.385H7.078v-3.47h3.047V9.43c0-3.007 1.792-4.669 4.533-4.669 1.312 0 2.686.235 2.686.235v2.953H15.83c-1.491 0-1.956.925-1.956 1.874v2.25h3.328l-.532 3.47h-2.796v8.385C19.612 23.027 24 18.062 24 12.073z\"/>
                        </svg>
                    </a>
                    <!-- Instagram -->
                    <a href=\"https://instagram.com/the4stairs\" target=\"_blank\" class=\"w-8 h-8 rounded-full bg-white/5 border border-white/10 hover:border-pink-500 hover:bg-pink-500/10 hover:text-pink-500 flex items-center justify-center text-gray-400 transition-all duration-300\" title=\"Instagram\">
                        <svg class=\"w-4 h-4 fill-current\" viewBox=\"0 0 24 24\">
                            <path d=\"M12 2.163c3.204 0 3.584.012 4.85.07 3.252.148 4.771 1.691 4.919 4.919.058 1.265.069 1.645.069 4.849 0 3.205-.012 3.584-.069 4.849-.149 3.225-1.664 4.771-4.919 4.919-1.266.058-1.644.07-4.85.07-3.204 0-3.584-.012-4.849-.07-3.26-.149-4.771-1.699-4.919-4.92-.058-1.265-.07-1.644-.07-4.849 0-3.204.013-3.583.07-4.849.149-3.227 1.664-4.771 4.919-4.919 1.266-.057 1.645-.069 4.849-.069zM12 0C8.741 0 8.333.014 7.053.072 2.695.272.273 2.69.073 7.051.014 8.333 0 8.741 0 12c0 3.259.014 3.668.072 4.948.2 4.358 2.618 6.78 6.98 6.98 1.281.058 1.689.072 4.948.072 3.259 0 3.668-.014 4.948-.072 4.354-.2 6.782-2.618 6.979-6.98.059-1.28.073-1.689.073-4.948 0-3.259-.014-3.667-.072-4.947-.196-4.354-2.617-6.78-6.979-6.98C15.668.014 15.259 0 12 0zm0 5.838a6.162 6.162 0 100 12.324 6.162 6.162 0 000-12.324zM12 16a4 4 0 110-8 4 4 0 010 8zm6.406-11.845a1.44 1.44 0 100 2.881 1.44 1.44 0 000-2.881z\"/>
                        </svg>
                    </a>
                    <!-- YouTube -->
                    <a href=\"https://youtube.com/the4stairs\" target=\"_blank\" class=\"w-8 h-8 rounded-full bg-white/5 border border-white/10 hover:border-red-600 hover:bg-red-600/10 hover:text-red-600 flex items-center justify-center text-gray-400 transition-all duration-300\" title=\"YouTube\">
                        <svg class=\"w-4 h-4 fill-current\" viewBox=\"0 0 24 24\">
                            <path d=\"M23.498 6.163a3.003 3.003 0 0 0-2.11-2.11C19.517 3.545 12 3.545 12 3.545s-7.517 0-9.388.508a3.003 3.003 0 0 0-2.11 2.11C0 8.033 0 12 0 12s0 3.967.502 5.837a3.003 3.003 0 0 0 2.11 2.11c1.871.508 9.388.508 9.388.508s7.517 0 9.388-.508a3.003 3.003 0 0 0 2.11-2.11C24 15.967 24 12 24 12s0-3.967-.502-5.837zM9.545 15.568V8.432L15.818 12l-6.273 3.568z\"/>
                        </svg>
                    </a>
                    <!-- X -->
                    <a href=\"https://x.com/the4stairs\" target=\"_blank\" class=\"w-8 h-8 rounded-full bg-white/5 border border-white/10 hover:border-white hover:bg-white/10 hover:text-white flex items-center justify-center text-gray-400 transition-all duration-300\" title=\"X (Twitter)\">
                        <svg class=\"w-3.5 h-3.5 fill-current\" viewBox=\"0 0 24 24\">
                            <path d=\"M18.244 2.25h3.308l-7.227 8.26 8.502 11.24H16.17l-5.214-6.817L4.99 21.75H1.68l7.73-8.835L1.254 2.25H8.08l4.713 6.231zm-1.161 17.52h1.833L7.084 4.126H5.117z\"/>
                        </svg>
                    </a>
                </div>
            </div>
            
            <!-- Col 2: Quick Links (3 cols) -->
            <div class=\"md:col-span-3 text-left\">
                <h4 class=\"text-white font-semibold text-xs uppercase tracking-wider mb-4 border-l-2 border-jazz-gold pl-2\">Navigasi Cepat</h4>
                <ul class=\"space-y-2.5 text-xs text-jazz-muted font-light\">
                    <li><a href=\"<?php echo \$prefix; ?>index\" class=\"hover:text-jazz-gold transition-colors duration-200 block\">&rarr; Beranda</a></li>
                    <li><a href=\"<?php echo \$prefix; ?>profil\" class=\"hover:text-jazz-gold transition-colors duration-200 block\">&rarr; Profil & Venue</a></li>
                    <li><a href=\"<?php echo \$prefix; ?>event\" class=\"hover:text-jazz-gold transition-colors duration-200 block\">&rarr; Jadwal Event</a></li>
                    <li><a href=\"<?php echo \$prefix; ?>pesan\" class=\"hover:text-jazz-gold transition-colors duration-200 block\">&rarr; Pesan Tiket</a></li>
                    <li><a href=\"<?php echo \$prefix; ?>komposisi\" class=\"hover:text-jazz-gold transition-colors duration-200 block\">&rarr; Repertoar Lagu</a></li>
                    <li><a href=\"<?php echo \$prefix; ?>berita\" class=\"hover:text-jazz-gold transition-colors duration-200 block\">&rarr; Berita & Warta</a></li>
                </ul>
            </div>
            
            <!-- Col 3: Operating Hours (3 cols) -->
            <div class=\"md:col-span-3 text-left\">
                <h4 class=\"text-white font-semibold text-xs uppercase tracking-wider mb-4 border-l-2 border-jazz-gold pl-2\">Jam Operasional</h4>
                <ul class=\"space-y-2 text-xs text-jazz-muted font-light\">
                    <li>
                        <span class=\"text-white/80 block font-medium\">Senin - Kamis:</span>
                        18:00 - 23:00 WIB
                    </li>
                    <li>
                        <span class=\"text-white/80 block font-medium\">Jumat - Sabtu:</span>
                        18:00 - 00:00 WIB
                    </li>
                    <li>
                        <span class=\"text-white/80 block font-medium\">Minggu:</span>
                        17:00 - 23:00 WIB
                    </li>
                </ul>
            </div>
            
            <!-- Col 4: Contact & Location (2 cols) -->
            <div class=\"md:col-span-2 text-left\">
                <h4 class=\"text-white font-semibold text-xs uppercase tracking-wider mb-4 border-l-2 border-jazz-gold pl-2\">Alamat & Kontak</h4>
                <address class=\"not-italic text-xs text-jazz-muted font-light space-y-2\">
                    <p>
                        Jl. Malioboro No. 44, Lantai 4,<br>
                        Gedong Tengen, Yogyakarta
                    </p>
                    <p class=\"pt-2 text-jazz-gold font-medium\">
                        WA: +62 812-3456-789<br>
                        Email: info@the4stairs.com
                    </p>
                </address>
            </div>
        </div>
        
        <!-- Bottom Divider & Copyright -->
        <div class=\"max-w-6xl mx-auto px-6 border-t border-white/5 pt-6 flex flex-col md:flex-row items-center justify-between gap-4\">
            <p class=\"text-jazz-muted text-xs font-light text-center md:text-left\">
                &copy; <?php echo date('Y'); ?> The 4 Stairs Music Hall. Tugas Projek Kuliah.
            </p>
            <p class=\"text-[9px] text-neutral-600 text-center md:text-right font-light\">
                Classy Live Music &copy; Real Composers Association
            </p>
        </div>
    </footer>
</body>
</html>";

    return $html_code;
}
