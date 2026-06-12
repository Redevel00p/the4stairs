<?php
/**
 * THE 4 STAIRS MUSIC HALL - MUSIC PAGE (KOMPOSISI)
 * ------------------------------------------------
 * Halaman khusus untuk mendengarkan lagu-lagu aransemen orisinal
 * dan repertoar internal The 4 Stairs Music Hall.
 * Dilengkapi pemutar musik ala Spotify dengan fungsionalitas penuh.
 */
include 'koneksi.php';

$playlist = [];
if (isset($conn) && !$conn->connect_error && $db_selected) {
    // Ambil daftar lagu dari database
    $sql = "SELECT * FROM `komposisi` ORDER BY `id` ASC";
    $result = $conn->query($sql);
    if ($result) {
        while ($row = $result->fetch_assoc()) {
            $playlist[] = $row;
        }
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Komposisi - The 4 Stairs Music Hall</title>
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
    <link rel="stylesheet" href="assets/css/style.css">
    <style>
        /* CSS custom scrollbar for playlist */
        .playlist-container::-webkit-scrollbar {
            width: 6px;
        }
        .playlist-container::-webkit-scrollbar-track {
            background: rgba(255, 255, 255, 0.02);
            border-radius: 4px;
        }
        .playlist-container::-webkit-scrollbar-thumb {
            background: rgba(139, 30, 34, 0.3);
            border-radius: 4px;
        }
        .playlist-container::-webkit-scrollbar-thumb:hover {
            background: rgba(139, 30, 34, 0.6);
        }
    </style>
</head>
<body class="bg-jazz-darkest text-jazz-light font-body flex flex-col min-h-screen overflow-x-hidden pt-20 pb-28">

    <!-- Premium Navigation Bar -->
    <?php include 'navbar.php'; ?>

    <!-- 1. Hero Section -->
    <header class="relative w-full py-16 md:py-24 bg-gradient-to-b from-jazz-card/30 to-jazz-darkest border-b border-jazz-gold/10">
        <!-- Background decorative glows -->
        <div class="absolute top-1/4 left-1/4 w-72 h-72 rounded-full bg-jazz-gold/5 filter blur-[100px] pointer-events-none"></div>
        <div class="absolute bottom-1/4 right-1/4 w-80 h-80 rounded-full bg-jazz-crimson/5 filter blur-[120px] pointer-events-none"></div>
        
        <div class="max-w-6xl mx-auto px-6 relative z-10 flex flex-col lg:flex-row items-center gap-12">
            <!-- Left: Hero Text -->
            <div class="w-full lg:w-7/12 text-center lg:text-left">
                <span class="text-jazz-gold text-xs font-semibold uppercase tracking-widest mb-3 block">Arrangements & Repertoire</span>
                <h1 class="font-heading text-4xl md:text-5xl lg:text-6xl font-bold text-white mb-6 leading-tight">Komposisi Musik Orisinal</h1>
                <p class="text-jazz-muted text-sm md:text-base font-light leading-relaxed max-w-xl mx-auto lg:mx-0 mb-8">
                    Selamat datang di koleksi repertoar eksklusif The 4 Stairs Music Hall. Dengarkan karya rekaman orisinal dan aransemen klasik kontemporer yang biasa dimainkan oleh komposer internal kami di panggung utama.
                </p>
                <div class="flex flex-wrap justify-center lg:justify-start gap-4">
                    <button onclick="playIndex(0)" class="bg-gradient-to-r from-jazz-gold to-jazz-goldDark text-white font-bold uppercase tracking-wider text-xs px-8 py-3.5 rounded-lg shadow-lg hover:shadow-jazz-gold/20 transform hover:-translate-y-0.5 transition-all duration-300">
                        Putar Lagu Pertama
                    </button>
                    <a href="#playlist-section" class="bg-zinc-900 hover:bg-jazz-gold/10 border border-white/10 hover:border-jazz-gold text-white font-bold uppercase tracking-wider text-xs px-8 py-3.5 rounded-lg transition-all duration-300">
                        Lihat Playlist
                    </a>
                </div>
            </div>
            
            <!-- Right: Feature Card -->
            <div class="w-full lg:w-5/12 max-w-sm">
                <div class="glass-card rounded-2xl p-6 border border-jazz-gold/15 relative overflow-hidden group">
                    <div class="relative w-full aspect-square rounded-xl overflow-hidden mb-6 shadow-2xl">
                        <img id="hero-card-cover" src="assets/img/album_art.png" alt="Album Art" class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-700">
                        <div class="absolute inset-0 bg-black/40 flex items-center justify-center opacity-0 group-hover:opacity-100 transition-opacity duration-300">
                            <div class="w-16 h-16 rounded-full bg-jazz-gold text-white flex items-center justify-center shadow-lg transform scale-90 group-hover:scale-100 transition-transform duration-300">
                                <svg class="w-6 h-6 fill-current" viewBox="0 0 24 24">
                                    <path d="M8 5v14l11-7z"/>
                                </svg>
                            </div>
                        </div>
                    </div>
                    <div>
                        <span class="text-[9px] bg-jazz-gold/20 text-jazz-gold border border-jazz-gold/30 px-2 py-0.5 rounded-full uppercase tracking-wider font-semibold mb-2 inline-block">Featured Composition</span>
                        <h3 id="hero-card-title" class="text-white font-heading text-xl font-bold tracking-wide mb-1 truncate">The 4 Stairs Repertoire</h3>
                        <p id="hero-card-artist" class="text-jazz-muted text-xs font-light truncate">Various Artists</p>
                    </div>
                </div>
            </div>
        </div>
    </header>

    <!-- 2. Playlist & Player UI Section -->
    <main id="playlist-section" class="max-w-6xl mx-auto px-6 py-12 flex-grow w-full">
        <div class="grid grid-cols-1 lg:grid-cols-12 gap-8 items-start">
            
            <!-- Playlist Table (Left) -->
            <section class="lg:col-span-8 bg-jazz-card/40 border border-white/5 rounded-2xl p-6 shadow-xl">
                <div class="flex justify-between items-center mb-6">
                    <h2 class="font-heading text-xl md:text-2xl text-white font-bold tracking-wide">Daftar Komposisi</h2>
                    <span class="text-jazz-muted text-xs font-light"><span id="track-count"><?php echo count($playlist); ?></span> Lagu Tersedia</span>
                </div>
                
                <div class="overflow-x-auto">
                    <table class="w-full text-left border-collapse">
                        <thead>
                            <tr class="border-b border-white/5 text-[11px] uppercase tracking-widest text-jazz-muted font-semibold">
                                <th class="pb-3 w-10 text-center">#</th>
                                <th class="pb-3 w-16">Cover</th>
                                <th class="pb-3 pl-4">Judul Lagu</th>
                                <th class="pb-3">Artis / Komposer</th>
                                <th class="pb-3 w-20 text-right pr-4">Durasi</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-white/5 text-sm font-light">
                            <?php if (empty($playlist)): ?>
                                <tr>
                                    <td colspan="5" class="py-12 text-center text-jazz-muted">
                                        Belum ada lagu yang diunggah ke repositori.
                                    </td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($playlist as $index => $song): ?>
                                    <tr onclick="playIndex(<?php echo $index; ?>)" 
                                        class="track-row group cursor-pointer hover:bg-jazz-gold/5 transition-colors duration-300" 
                                        data-index="<?php echo $index; ?>">
                                        <td class="py-3.5 text-center text-jazz-muted font-medium w-10 group-hover:text-jazz-gold transition-colors">
                                            <span class="track-number"><?php echo $index + 1; ?></span>
                                            <span class="playing-icon hidden text-jazz-gold">&#9658;</span>
                                        </td>
                                        <td class="py-3.5">
                                            <div class="w-10 h-10 rounded-md overflow-hidden border border-white/5 relative">
                                                <img src="<?php echo htmlspecialchars($song['cover']); ?>" alt="Cover" class="w-full h-full object-cover">
                                            </div>
                                        </td>
                                        <td class="py-3.5 pl-4 text-white font-medium group-hover:text-jazz-gold transition-colors tracking-wide max-w-[200px] truncate">
                                            <?php echo htmlspecialchars($song['title']); ?>
                                        </td>
                                        <td class="py-3.5 text-jazz-muted max-w-[150px] truncate">
                                            <?php echo htmlspecialchars($song['artist']); ?>
                                        </td>
                                        <td class="py-3.5 text-right text-jazz-muted pr-4 font-medium">
                                            <?php echo htmlspecialchars($song['duration']); ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </section>
            
            <!-- Side Info & Lyrics Widget (Right) -->
            <section class="lg:col-span-4 flex flex-col gap-6 sticky top-24">
                <div class="glass-card rounded-2xl p-6 border border-jazz-gold/10 flex flex-col items-center">
                    
                    <!-- Cover Art Image (Large, Static) -->
                    <div class="w-full aspect-square rounded-xl overflow-hidden mb-5 shadow-2xl border border-white/5 relative">
                        <img id="right-cover" src="assets/img/album_art.png" alt="Cover Art" class="w-full h-full object-cover">
                    </div>
                    
                    <!-- Title & Artist -->
                    <div class="text-center w-full mb-3 px-2">
                        <h3 id="right-title" class="text-white font-heading text-lg md:text-xl font-bold tracking-wide truncate">Pilih Lagu</h3>
                        <p id="right-artist" class="text-jazz-muted text-xs font-light mt-0.5 truncate">The 4 Stairs Playlist</p>
                    </div>
                    
                    <!-- Social Media Links (Mini Buttons) -->
                    <div id="right-socials" class="flex items-center justify-center gap-3 mb-5">
                        <!-- YouTube Link -->
                        <a id="right-yt-link" href="#" target="_blank" class="hidden w-8 h-8 rounded-full bg-red-600/10 hover:bg-red-600/20 text-red-500 flex items-center justify-center border border-red-600/20 hover:border-red-600 transition-all duration-300" title="Putar di YouTube">
                            <svg class="w-4 h-4 fill-current" viewBox="0 0 24 24">
                                <path d="M23.498 6.163a3.003 3.003 0 0 0-2.11-2.11C19.517 3.545 12 3.545 12 3.545s-7.517 0-9.388.508a3.003 3.003 0 0 0-2.11 2.11C0 8.033 0 12 0 12s0 3.967.502 5.837a3.003 3.003 0 0 0 2.11 2.11c1.871.508 9.388.508 9.388.508s7.517 0 9.388-.508a3.003 3.003 0 0 0 2.11-2.11C24 15.967 24 12 24 12s0-3.967-.502-5.837zM9.545 15.568V8.432L15.818 12l-6.273 3.568z"/>
                            </svg>
                        </a>
                        <!-- SoundCloud Link -->
                        <a id="right-sc-link" href="#" target="_blank" class="hidden w-8 h-8 rounded-full bg-orange-500/10 hover:bg-orange-500/20 text-orange-500 flex items-center justify-center border border-orange-500/20 hover:border-orange-500 transition-all duration-300" title="Dengarkan di SoundCloud">
                            <svg class="w-4 h-4 fill-current" viewBox="0 0 24 24">
                                <path d="M11.56 16.74c-.06 0-.11 0-.15-.02V9.01c.04 0 .09-.01.15-.01.69 0 1.25.56 1.25 1.25v5.24c0 .69-.56 1.25-1.25 1.25zm-2.03 0c-.04 0-.09 0-.13-.01V9.38c.04 0 .09-.01.13-.01.69 0 1.25.56 1.25 1.25v4.87c0 .69-.56 1.25-1.25 1.25zm-2.03-.23c0-.69.56-1.25 1.25-1.25V9.92c-.69 0-1.25.56-1.25 1.25v4.11c0 .09.02.19.05.28-.02-.02-.03-.04-.05-.05zm-2.03-1.02c0-.69.56-1.25 1.25-1.25v-3.14c-.69 0-1.25.56-1.25 1.25v2.89c0 .08.01.17.04.25-.01-.01-.03-.02-.04-.02zM3.44 14.24c0-.69.56-1.25 1.25-1.25V11.8c-.69 0-1.25.56-1.25 1.25v.94c0 .08.02.16.05.23-.02-.01-.03-.02-.05-.02zM1.4 13.23c0-.69.56-1.25 1.25-1.25V10.8c-.69 0-1.25.56-1.25 1.25v.93c0 .08.02.16.05.23-.02-.01-.03-.02-.05-.02zM22.6 12.18c-.46 0-.85.29-.98.7-.27-.85-1.07-1.46-2.02-1.46-.37 0-.71.1-.11.27-.47-.79-1.33-1.31-2.31-1.31-.38 0-.73.08-1.05.23v6.13h6.47c1.1 0 2-1 2-2.18 0-1.2-.9-2.18-2-2.18z"/>
                            </svg>
                        </a>
                        <!-- Spotify Link -->
                        <a id="right-sp-link" href="#" target="_blank" class="hidden w-8 h-8 rounded-full bg-emerald-500/10 hover:bg-emerald-500/20 text-emerald-500 flex items-center justify-center border border-emerald-500/20 hover:border-emerald-500 transition-all duration-300" title="Dengarkan di Spotify">
                            <svg class="w-4 h-4 fill-current" viewBox="0 0 24 24">
                                <path d="M12 0C5.372 0 0 5.372 0 12s5.372 12 12 12 12-5.372 12-12S18.628 0 12 0zm5.49 17.3c-.216.354-.675.467-1.028.251-2.855-1.745-6.45-2.14-10.684-1.173-.404.092-.814-.162-.907-.566-.093-.404.162-.814.566-.907 4.636-1.06 8.587-.613 11.782 1.34.354.217.467.676.251 1.029zm1.465-3.26c-.272.443-.855.586-1.298.314-3.268-2.008-8.25-2.593-12.115-1.42-.497.15-1.02-.132-1.17-.629-.15-.497.132-1.02.63-1.17 4.417-1.34 9.907-.69 13.64 1.61.442.27.585.854.313 1.297zm.126-3.395c-3.92-2.33-10.385-2.543-14.125-1.407-.602.183-1.242-.158-1.424-.76-.182-.603.158-1.243.76-1.425 4.29-1.3 11.42-1.05 15.93 1.63.542.32.72 1.02.4 1.562-.32.54-.102.717-.542.4z"/>
                            </svg>
                        </a>
                    </div>
                    
                    <!-- Lyrics Section -->
                    <div class="w-full border-t border-white/5 pt-4 flex flex-col items-center">
                        <span class="text-jazz-gold text-[10px] font-semibold uppercase tracking-widest mb-2">Lirik Lagu</span>
                        <div id="right-lyrics-container" class="w-full overflow-y-auto max-h-[200px] pr-1 playlist-container text-center text-xs md:text-sm font-light text-jazz-muted leading-relaxed select-none">
                            <p class="italic">Pilih lagu untuk melihat lirik.</p>
                        </div>
                    </div>
                    
                </div>
            </section>
            
        </div>
    </main>

    <!-- 3. Bottom Spotify Player Bar (Fixed & Fully Responsive) -->
    <div id="spotify-player-bar" class="fixed bottom-0 left-0 w-full bg-jazz-card/95 border-t border-jazz-gold/20 py-3 md:py-4 px-4 md:px-8 z-40 backdrop-blur-md flex flex-row items-center justify-between transition-transform duration-300 translate-y-0 shadow-2xl">
        
        <!-- Mobile Top Seekbar (Progress bar running along the top edge of player bar on mobile) -->
        <div id="player-progress-container-mobile" class="absolute top-0 left-0 w-full h-[2px] bg-white/5 cursor-pointer md:hidden">
            <div id="player-progress-bar-mobile" class="h-full bg-jazz-gold w-0"></div>
        </div>

        <!-- Track Info (Takes full width on mobile, and w-1/4 on desktop) -->
        <div class="flex items-center gap-3 md:gap-4 w-2/3 md:w-1/4 min-w-0">
            <div class="w-10 h-10 md:w-12 md:h-12 rounded-lg overflow-hidden border border-white/5 shrink-0 shadow-lg">
                <img id="bar-cover" src="assets/img/album_art.png" alt="Song Cover" class="w-full h-full object-cover">
            </div>
            <div class="min-w-0">
                <h4 id="bar-title" class="text-white font-medium text-xs md:text-sm truncate leading-snug">Pilih Lagu untuk Diputar</h4>
                <p id="bar-artist" class="text-jazz-muted text-[10px] md:text-xs font-light truncate mt-0.5">The 4 Stairs Repertoire</p>
            </div>
        </div>
        
        <!-- Center Controls & Progress Bar (Hidden on Mobile, Displayed on Desktop) -->
        <div class="hidden md:flex flex-col items-center gap-2 w-2/4">
            <div class="flex items-center gap-6">
                <!-- Shuffle button (Visual placeholder) -->
                <button class="text-gray-500 hover:text-white transition-colors focus:outline-none hidden sm:block text-xs" aria-label="Acak">
                    <svg class="w-4 h-4 fill-current" viewBox="0 0 24 24">
                        <path d="M10.59 9.17L5.41 4 4 5.41l5.17 5.17 1.42-1.41zM14.5 4l2.04 2.04L4 18.59 5.41 20 17.96 7.46 20 9.5V4h-5.5zm.73 11.27l-1.41 1.41 3.27 3.27L20 18.5V20h-5.5l2.05-2.05-3.32-3.28z"/>
                    </svg>
                </button>
                
                <button onclick="playPrev()" class="text-gray-400 hover:text-white transition-colors focus:outline-none" aria-label="Lagu Sebelumnya">
                    <svg class="w-5 h-5 fill-current" viewBox="0 0 24 24">
                        <path d="M6 6h2v12H6zm3.5 6l8.5 6V6z"/>
                    </svg>
                </button>
                <button id="bar-play-btn" onclick="togglePlay()" class="w-10 h-10 rounded-full bg-white text-jazz-darkest flex items-center justify-center shadow-lg transition-transform duration-300 hover:scale-105 focus:outline-none" aria-label="Putar / Jeda">
                    <svg class="w-4 h-4 fill-current ml-0.5" id="bar-play-icon" viewBox="0 0 24 24">
                        <path d="M8 5v14l11-7z"/>
                    </svg>
                </button>
                <button onclick="playNext()" class="text-gray-400 hover:text-white transition-colors focus:outline-none" aria-label="Lagu Berikutnya">
                    <svg class="w-5 h-5 fill-current" viewBox="0 0 24 24">
                        <path d="M6 18l8.5-6L6 6zm9-12h2v12h-2z"/>
                    </svg>
                </button>
                
                <!-- Repeat button (Visual placeholder) -->
                <button class="text-gray-500 hover:text-white transition-colors focus:outline-none hidden sm:block text-xs" aria-label="Ulangi">
                    <svg class="w-4 h-4 fill-current" viewBox="0 0 24 24">
                        <path d="M7 7h10v3l4-4-4-4v3H5v6h2V7zm10 10H7v-3l-4 4 4 4v-3h12v-6h-2v4z"/>
                    </svg>
                </button>
            </div>
            
            <!-- Seekbar Slider -->
            <div class="flex items-center gap-3 w-full max-w-lg">
                <span id="player-current-time" class="text-[10px] text-jazz-muted font-medium w-8 text-right">0:00</span>
                <div id="player-progress-container" class="relative flex-grow h-1 bg-white/10 rounded-full cursor-pointer group">
                    <!-- Progress Fill -->
                    <div id="player-progress-bar" class="absolute top-0 left-0 h-full bg-jazz-gold rounded-full w-0 transition-all duration-75"></div>
                    <!-- Drag handle dot -->
                    <div id="player-progress-dot" class="absolute top-1/2 -translate-y-1/2 w-3 h-3 rounded-full bg-white shadow-lg border border-jazz-gold opacity-0 group-hover:opacity-100 transition-opacity duration-150" style="left: 0%;"></div>
                </div>
                <span id="player-total-duration" class="text-[10px] text-jazz-muted font-medium w-8 text-left">0:00</span>
            </div>
        </div>

        <!-- Mobile Controls (Visible only on mobile) -->
        <div class="flex items-center gap-4 md:hidden">
            <button id="mobile-play-btn" onclick="togglePlay()" class="w-9 h-9 rounded-full bg-white text-jazz-darkest flex items-center justify-center shadow-lg focus:outline-none" aria-label="Putar / Jeda">
                <svg class="w-4 h-4 fill-current ml-0.5" id="mobile-play-icon" viewBox="0 0 24 24">
                    <path d="M8 5v14l11-7z"/>
                </svg>
            </button>
            <button onclick="playNext()" class="text-gray-400 hover:text-white transition-colors focus:outline-none" aria-label="Lagu Berikutnya">
                <svg class="w-5 h-5 fill-current" viewBox="0 0 24 24">
                    <path d="M6 18l8.5-6L6 6zm9-12h2v12h-2z"/>
                </svg>
            </button>
        </div>
        
        <!-- Right: Volume (Hidden on Mobile, Displayed on Desktop) -->
        <div class="hidden md:flex items-center justify-end gap-3 w-1/4">
            <svg class="w-4 h-4 text-gray-400 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.536 8.464a5 5 0 010 7.072m2.828-9.9a9 9 0 010 12.728M5.586 15H4a1 1 0 01-1-1v-4a1 1 0 011-1h1.586l4.707-4.707C10.923 3.663 12 4.109 12 5v14c0 .891-1.077 1.337-1.707.707L5.586 15z" />
            </svg>
            <div id="player-volume-container" class="relative w-24 h-1 bg-white/10 rounded-full cursor-pointer group">
                <div id="player-volume-bar" class="absolute top-0 left-0 h-full bg-white rounded-full w-[80%]"></div>
                <div id="player-volume-dot" class="absolute top-1/2 -translate-y-1/2 w-3 h-3 rounded-full bg-white shadow-lg opacity-0 group-hover:opacity-100 transition-opacity duration-150" style="left: 80%;"></div>
            </div>
        </div>
    </div>

    <!-- Background Hidden Audio Element -->
    <audio id="main-audio" preload="auto"></audio>

    <!-- Dynamic Player Javascript Script -->
    <script>
        // Convert PHP Playlist array to JS Array
        const playlist = <?php echo json_encode($playlist); ?>;
        
        let currentTrackIndex = -1;
        let isPlaying = false;
        
        const audio = document.getElementById('main-audio');
        
        // Element references
        const heroCover = document.getElementById('hero-card-cover');
        const heroTitle = document.getElementById('hero-card-title');
        const heroArtist = document.getElementById('hero-card-artist');
        
        const rightCover = document.getElementById('right-cover');
        const rightTitle = document.getElementById('right-title');
        const rightArtist = document.getElementById('right-artist');
        const rightLyricsContainer = document.getElementById('right-lyrics-container');
        
        const rightYtLink = document.getElementById('right-yt-link');
        const rightScLink = document.getElementById('right-sc-link');
        const rightSpLink = document.getElementById('right-sp-link');
        
        const barCover = document.getElementById('bar-cover');
        const barTitle = document.getElementById('bar-title');
        const barArtist = document.getElementById('bar-artist');
        
        const barPlayBtn = document.getElementById('bar-play-btn');
        const barPlayIcon = document.getElementById('bar-play-icon');
        const mobilePlayIcon = document.getElementById('mobile-play-icon');
        
        const progressBar = document.getElementById('player-progress-bar');
        const progressDot = document.getElementById('player-progress-dot');
        const progressContainer = document.getElementById('player-progress-container');
        const progressBarMobile = document.getElementById('player-progress-bar-mobile');
        const progressContainerMobile = document.getElementById('player-progress-container-mobile');
        
        const currentTimeEl = document.getElementById('player-current-time');
        const totalDurationEl = document.getElementById('player-total-duration');
        
        const volumeBar = document.getElementById('player-volume-bar');
        const volumeDot = document.getElementById('player-volume-dot');
        const volumeContainer = document.getElementById('player-volume-container');

        // Escape HTML helper to prevent XSS
        function escapeHTML(str) {
            if (!str) return '';
            return str
                .replace(/&/g, "&amp;")
                .replace(/</g, "&lt;")
                .replace(/>/g, "&gt;")
                .replace(/"/g, "&quot;")
                .replace(/'/g, "&#039;");
        }

        // Initialize Volume (default 80%)
        audio.volume = 0.8;

        // Play track by index
        function playIndex(index) {
            if (playlist.length === 0) return;
            
            // Check boundary
            if (index < 0) index = playlist.length - 1;
            if (index >= playlist.length) index = 0;
            
            const selectedTrack = playlist[index];
            
            // If selecting same track, toggle play
            if (index === currentTrackIndex) {
                togglePlay();
                return;
            }
            
            // Update index
            currentTrackIndex = index;
            
            // Highlight row
            document.querySelectorAll('.track-row').forEach((row, rIdx) => {
                const trackNum = row.querySelector('.track-number');
                const playingIcon = row.querySelector('.playing-icon');
                if (rIdx === index) {
                    row.classList.add('bg-jazz-gold/10', 'text-jazz-gold');
                    if (trackNum) trackNum.classList.add('hidden');
                    if (playingIcon) playingIcon.classList.remove('hidden');
                } else {
                    row.classList.remove('bg-jazz-gold/10', 'text-jazz-gold');
                    if (trackNum) trackNum.classList.remove('hidden');
                    if (playingIcon) playingIcon.classList.add('hidden');
                }
            });
            
            // Set audio source
            audio.src = selectedTrack.src;
            audio.load();
            
            // Update metadata UI (bottom player)
            if (barCover) barCover.src = selectedTrack.cover;
            if (barTitle) barTitle.textContent = selectedTrack.title;
            if (barArtist) barArtist.textContent = selectedTrack.artist;
            
            // Update hero card (top banner)
            if (heroCover) heroCover.src = selectedTrack.cover;
            if (heroTitle) heroTitle.textContent = selectedTrack.title;
            if (heroArtist) heroArtist.textContent = selectedTrack.artist;
            
            // Update Right Side Widget
            if (rightCover) rightCover.src = selectedTrack.cover;
            if (rightTitle) rightTitle.textContent = selectedTrack.title;
            if (rightArtist) rightArtist.textContent = selectedTrack.artist;
            
            // Social buttons dynamic display
            if (rightYtLink) {
                if (selectedTrack.youtube_url && selectedTrack.youtube_url.trim() !== '') {
                    rightYtLink.href = selectedTrack.youtube_url;
                    rightYtLink.classList.remove('hidden');
                } else {
                    rightYtLink.classList.add('hidden');
                }
            }
            if (rightScLink) {
                if (selectedTrack.soundcloud_url && selectedTrack.soundcloud_url.trim() !== '') {
                    rightScLink.href = selectedTrack.soundcloud_url;
                    rightScLink.classList.remove('hidden');
                } else {
                    rightScLink.classList.add('hidden');
                }
            }
            if (rightSpLink) {
                if (selectedTrack.spotify_url && selectedTrack.spotify_url.trim() !== '') {
                    rightSpLink.href = selectedTrack.spotify_url;
                    rightSpLink.classList.remove('hidden');
                } else {
                    rightSpLink.classList.add('hidden');
                }
            }
            
            // Lyrics display
            if (rightLyricsContainer) {
                if (selectedTrack.lyrics && selectedTrack.lyrics.trim() !== '') {
                    const escapedLyrics = escapeHTML(selectedTrack.lyrics).replace(/\n/g, '<br>');
                    rightLyricsContainer.innerHTML = `<p class="whitespace-pre-line text-sm">${escapedLyrics}</p>`;
                } else {
                    rightLyricsContainer.innerHTML = '<p class="italic text-stone-500 text-xs">Lirik tidak tersedia.</p>';
                }
            }
            
            totalDurationEl.textContent = selectedTrack.duration;
            currentTimeEl.textContent = '0:00';
            progressBar.style.width = '0%';
            progressDot.style.left = '0%';
            if (progressBarMobile) progressBarMobile.style.width = '0%';
            
            // Play
            isPlaying = false;
            togglePlay();
        }
        
        // Toggle play/pause
        function togglePlay() {
            if (currentTrackIndex === -1 && playlist.length > 0) {
                playIndex(0);
                return;
            }
            if (currentTrackIndex === -1) return;
            
            const row = document.querySelector(`.track-row[data-index="${currentTrackIndex}"]`);
            const playingIcon = row ? row.querySelector('.playing-icon') : null;
            
            if (isPlaying) {
                audio.pause();
                isPlaying = false;
                
                // SVG Pause to Play Icon Update
                if (barPlayIcon) barPlayIcon.innerHTML = '<path d="M8 5v14l11-7z"/>';
                if (mobilePlayIcon) mobilePlayIcon.innerHTML = '<path d="M8 5v14l11-7z"/>';
                
                if (barPlayBtn) {
                    barPlayBtn.classList.remove('bg-jazz-gold', 'text-white');
                    barPlayBtn.classList.add('bg-white', 'text-jazz-darkest');
                }
                
                if (playingIcon) playingIcon.innerHTML = '&#9658;';
            } else {
                audio.play().then(() => {
                    isPlaying = true;
                    
                    // SVG Play to Pause Icon Update
                    if (barPlayIcon) barPlayIcon.innerHTML = '<path d="M6 19h4V5H6v14zm8-14v14h4V5h-4z"/>';
                    if (mobilePlayIcon) mobilePlayIcon.innerHTML = '<path d="M6 19h4V5H6v14zm8-14v14h4V5h-4z"/>';
                    
                    if (barPlayBtn) {
                        barPlayBtn.classList.remove('bg-white', 'text-jazz-darkest');
                        barPlayBtn.classList.add('bg-jazz-gold', 'text-white');
                    }
                    
                    if (playingIcon) playingIcon.innerHTML = '&#10074;&#10074;';
                }).catch(err => {
                    console.log("Audio playback failed: ", err);
                });
            }
        }
        
        function playNext() {
            playIndex(currentTrackIndex + 1);
        }
        
        // Time & Progress Updates
        audio.addEventListener('timeupdate', () => {
            if (audio.duration) {
                const current = audio.currentTime;
                const duration = audio.duration;
                const percent = (current / duration) * 100;
                
                progressBar.style.width = percent + '%';
                progressDot.style.left = percent + '%';
                if (progressBarMobile) progressBarMobile.style.width = percent + '%';
                
                // Format current time
                const min = Math.floor(current / 60);
                const sec = Math.floor(current % 60);
                currentTimeEl.textContent = `${min}:${sec < 10 ? '0' : ''}${sec}`;
                
                // Update total duration if it changes or loads
                const tMin = Math.floor(duration / 60);
                const tSec = Math.floor(duration % 60);
                totalDurationEl.textContent = `${tMin}:${tSec < 10 ? '0' : ''}${tSec}`;
            }
        });
        
        // Track ended -> auto next
        audio.addEventListener('ended', () => {
            playNext();
        });
        
        // Click to seek progress (Desktop)
        progressContainer.addEventListener('click', (e) => {
            if (currentTrackIndex === -1) return;
            const rect = progressContainer.getBoundingClientRect();
            const clickX = e.clientX - rect.left;
            const width = rect.width;
            const percent = clickX / width;
            
            audio.currentTime = percent * audio.duration;
        });

        // Click to seek progress (Mobile)
        if (progressContainerMobile) {
            progressContainerMobile.addEventListener('click', (e) => {
                if (currentTrackIndex === -1) return;
                const rect = progressContainerMobile.getBoundingClientRect();
                const clickX = e.clientX - rect.left;
                const width = rect.width;
                const percent = clickX / width;
                
                audio.currentTime = percent * audio.duration;
            });
        }
        
        // Click to set volume
        volumeContainer.addEventListener('click', (e) => {
            const rect = volumeContainer.getBoundingClientRect();
            const clickX = e.clientX - rect.left;
            const width = rect.width;
            let percent = clickX / width;
            
            // Limit bounds
            if (percent < 0) percent = 0;
            if (percent > 1) percent = 1;
            
            audio.volume = percent;
            volumeBar.style.width = (percent * 100) + '%';
            volumeDot.style.left = (percent * 100) + '%';
        });
    </script>
</body>
</html>
