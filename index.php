<?php
/**
 * THE 4 STAIRS MUSIC HALL - LANDING PAGE (REBUILT WITH TAILWIND CSS)
 * -----------------------------------------------------------------
 * Halaman utama dengan sliding Hero Carousel, pengumuman/berita terbaru,
 * pemutar lagu CD Sleeve, modal denah ruangan eksklusif, dan jadwal show
 * dinamis yang dapat dikontrol oleh admin.
 */

// Mulai session agar bisa cek status login
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Cek apakah user sudah login
$is_logged_in = isset($_SESSION['user_logged_in']) && $_SESSION['user_logged_in'] === true;

// Sertakan file koneksi database
include 'koneksi.php';

$db_connected = false;
$jadwal_list = [];
$berita_list = [];

// Periksa koneksi database
if (isset($conn) && !$conn->connect_error && $db_selected) {
    $db_connected = true;
    
    // Ambil jadwal 7 hari (Minggu Ini)
    $sql_jadwal = "SELECT * FROM `jadwal` 
                   WHERE `tanggal` BETWEEN DATE_ADD(CURDATE(), INTERVAL 0 - WEEKDAY(CURDATE()) DAY) 
                                       AND DATE_ADD(CURDATE(), INTERVAL 6 - WEEKDAY(CURDATE()) DAY)
                   ORDER BY `tanggal` ASC";
    $res_jad = $conn->query($sql_jadwal);
    if ($res_jad) {
        while ($row = $res_jad->fetch_assoc()) {
            $jadwal_list[] = $row;
        }
    }
    
    // Ambil berita terbaru (maksimal 3 berita)
    $sql_berita = "SELECT * FROM `berita` ORDER BY `tanggal_post` DESC LIMIT 3";
    $res_ber = $conn->query($sql_berita);
    if ($res_ber) {
        while ($row = $res_ber->fetch_assoc()) {
            $berita_list[] = $row;
        }
    }
    
    // Ambil event spesial mendatang (maksimal 3 event)
    $upcoming_special_list = [];
    $sql_upcoming = "SELECT * FROM `jadwal` WHERE `is_special` = 1 AND `tanggal` >= CURDATE() ORDER BY `tanggal` ASC LIMIT 3";
    $res_upc = $conn->query($sql_upcoming);
    if ($res_upc) {
        while ($row = $res_upc->fetch_assoc()) {
            $upcoming_special_list[] = $row;
        }
    }
}
?>
<!DOCTYPE html>
<html lang="id" class="scroll-smooth">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>The 4 Stairs Music Hall - Live Performance & Event Venue</title>
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
                            gold: '#8b1e22',       // Mapping gold to deep crimson red
                            goldDark: '#78350f',   // Mapping goldDark to warm leather brown
                            crimson: '#991b1b',    // Mapping crimson to velvet crimson
                            muted: '#a89f91',      // Mapping muted to warm stone/muted grey
                            light: '#ece6dc'       // Mapping light to warm cream
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
    <!-- Custom animations and scrollbar stylesheet -->
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body class="bg-jazz-darkest text-jazz-light font-body flex flex-col min-h-screen overflow-x-hidden">

    <!-- Premium Navigation Bar -->
    <?php include 'navbar.php'; ?>

    <!-- Hero Carousel Section -->
    <header class="relative w-full h-[580px] overflow-hidden pt-20">
        <!-- Slide 1 -->
        <div class="carousel-slide active absolute inset-0 w-full h-full">
            <div class="absolute inset-0 bg-cover bg-center" style="background-image: url('assets/img/carousel_stage.png');"></div>
            <div class="absolute inset-0 bg-gradient-to-r from-jazz-darkest via-jazz-darkest/75 to-transparent"></div>
            <div class="absolute inset-0 bg-gradient-to-t from-jazz-darkest to-transparent"></div>
            <div class="relative z-10 max-w-6xl mx-auto h-full flex flex-col justify-center px-6 md:px-8">
                <span class="text-jazz-gold text-xs font-semibold uppercase tracking-widest mb-3 block">Exclusive Live Venue</span>
                <h1 class="font-heading text-4xl md:text-6xl font-bold tracking-tight text-white mb-4 leading-tight">The 4 Stairs Music Hall</h1>
                <p class="text-jazz-muted text-sm md:text-base max-w-xl mb-8 font-light leading-relaxed">
                    Sebuah gedung pertunjukan eksklusif yang intim untuk menikmati penampilan musik berkualitas tinggi secara langsung dari band-band pilihan.
                </p>
                <div>
                    <a href="#schedules-section" class="inline-block bg-gradient-to-r from-jazz-gold to-jazz-goldDark text-white font-bold uppercase tracking-wider text-xs px-8 py-3.5 rounded-lg shadow-lg hover:shadow-jazz-gold/20 transform hover:-translate-y-0.5 transition-all duration-300">Pesan Tiket Sekarang</a>
                </div>
            </div>
        </div>
        
        <!-- Slide 2 -->
        <div class="carousel-slide absolute inset-0 w-full h-full">
            <div class="absolute inset-0 bg-cover bg-center" style="background-image: url('assets/img/carousel_musicians.png');"></div>
            <div class="absolute inset-0 bg-gradient-to-r from-jazz-darkest via-jazz-darkest/75 to-transparent"></div>
            <div class="absolute inset-0 bg-gradient-to-t from-jazz-darkest to-transparent"></div>
            <div class="relative z-10 max-w-6xl mx-auto h-full flex flex-col justify-center px-6 md:px-8">
                <span class="text-jazz-gold text-xs font-semibold uppercase tracking-widest mb-3 block">Acoustic Excellence</span>
                <h1 class="font-heading text-4xl md:text-6xl font-bold tracking-tight text-white mb-4 leading-tight">Live Performance & Stage</h1>
                <p class="text-jazz-muted text-sm md:text-base max-w-xl mb-8 font-light leading-relaxed">
                    Dengarkan karya aransemen orisinal, musik kontemporer, dan improvisasi memukau dari komposer internal kami serta penampilan band tamu terbaik.
                </p>
                <div>
                    <a href="#schedules-section" class="inline-block bg-gradient-to-r from-jazz-gold to-jazz-goldDark text-white font-bold uppercase tracking-wider text-xs px-8 py-3.5 rounded-lg shadow-lg hover:shadow-jazz-gold/20 transform hover:-translate-y-0.5 transition-all duration-300">Pesan Tiket Sekarang</a>
                </div>
            </div>
        </div>
        
        <!-- Slide 3 -->
        <div class="carousel-slide absolute inset-0 w-full h-full">
            <div class="absolute inset-0 bg-cover bg-center" style="background-image: url('assets/img/carousel_seating.png');"></div>
            <div class="absolute inset-0 bg-gradient-to-r from-jazz-darkest via-jazz-darkest/75 to-transparent"></div>
            <div class="absolute inset-0 bg-gradient-to-t from-jazz-darkest to-transparent"></div>
            <div class="relative z-10 max-w-6xl mx-auto h-full flex flex-col justify-center px-6 md:px-8">
                <span class="text-jazz-gold text-xs font-semibold uppercase tracking-widest mb-3 block">Limited Intimate Seating</span>
                <h1 class="font-heading text-4xl md:text-6xl font-bold tracking-tight text-white mb-4 leading-tight">Intimate Concert Seating</h1>
                <p class="text-jazz-muted text-sm md:text-base max-w-xl mb-8 font-light leading-relaxed">
                    Kapasitas terbatas hanya 50 kursi per pertunjukan. Tata ruang konser melingkar mendekatkan Anda dengan panggung untuk atmosfer pertunjukan yang tak terlupakan.
                </p>
                <div>
                    <a href="#schedules-section" class="inline-block bg-gradient-to-r from-jazz-gold to-jazz-goldDark text-white font-bold uppercase tracking-wider text-xs px-8 py-3.5 rounded-lg shadow-lg hover:shadow-jazz-gold/20 transform hover:-translate-y-0.5 transition-all duration-300">Pesan Tiket Sekarang</a>
                </div>
            </div>
        </div>

        <!-- Navigation Arrows -->
        <button id="carousel-prev-btn" class="absolute left-4 top-1/2 -translate-y-1/2 z-30 w-10 h-10 md:w-12 h-12 rounded-full bg-black/40 hover:bg-jazz-gold/20 text-white hover:text-jazz-gold border border-white/10 hover:border-jazz-gold/45 hidden md:flex items-center justify-center transition-all duration-300 focus:outline-none text-base md:text-lg select-none" aria-label="Slide Sebelumnya">&larr;</button>
        <button id="carousel-next-btn" class="absolute right-4 top-1/2 -translate-y-1/2 z-30 w-10 h-10 md:w-12 h-12 rounded-full bg-black/40 hover:bg-jazz-gold/20 text-white hover:text-jazz-gold border border-white/10 hover:border-jazz-gold/45 hidden md:flex items-center justify-center transition-all duration-300 focus:outline-none text-base md:text-lg select-none" aria-label="Slide Berikutnya">&rarr;</button>
        
        <!-- Indicator dots -->
        <div class="absolute bottom-6 left-1/2 -translate-x-1/2 z-30 flex gap-3">
            <button class="carousel-dot active focus:outline-none" aria-label="Slide 1"></button>
            <button class="carousel-dot focus:outline-none" aria-label="Slide 2"></button>
            <button class="carousel-dot focus:outline-none" aria-label="Slide 3"></button>
        </div>
    </header>

    <!-- Main Section -->
    <main class="max-w-6xl mx-auto px-6 py-16 flex-grow w-full">
        
        <!-- 1. Berita Terbaru -->
        <?php if (!empty($berita_list)): ?>
        <section class="mb-20">
            <h2 class="font-heading text-2xl md:text-3xl text-white mb-2 tracking-wide text-center md:text-left">Berita Terbaru</h2>
            <p class="text-jazz-muted text-xs md:text-sm mb-8 text-center md:text-left">Kabar terbaru pertunjukan dan penawaran spesial kami</p>
            
            <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                <?php foreach ($berita_list as $berita): 
                    $target_link = !empty($berita['file_path']) ? str_replace('.php', '', $berita['file_path']) : 'articles/berita_' . $berita['id'];
                    $target_link = htmlspecialchars($target_link);
                ?>
                    <div class="glass-card rounded-xl hover:border-jazz-gold/30 hover:shadow-xl transition-all duration-300 flex flex-col justify-between overflow-hidden">
                        <?php if (!empty($berita['gambar']) && file_exists(__DIR__ . '/' . $berita['gambar'])): ?>
                            <div class="w-full h-48 overflow-hidden relative group">
                                <a href="<?php echo $target_link; ?>">
                                    <img src="<?php echo htmlspecialchars($berita['gambar']); ?>" alt="<?php echo htmlspecialchars($berita['judul']); ?>" class="w-full h-full object-cover transform hover:scale-110 transition-transform duration-500">
                                </a>
                            </div>
                        <?php endif; ?>
                        
                        <div class="p-6 flex-grow flex flex-col justify-between">
                            <div>
                                <span class="text-[10px] text-jazz-gold tracking-widest uppercase font-semibold mb-2 block">
                                    <?php echo date('d M Y', strtotime($berita['tanggal_post'])); ?>
                                </span>
                                <h3 class="text-white font-medium text-lg mb-3 tracking-wide">
                                    <a href="<?php echo $target_link; ?>" class="hover:text-jazz-gold transition-colors duration-300">
                                        <?php echo htmlspecialchars($berita['judul']); ?>
                                    </a>
                                </h3>
                                <p class="text-jazz-muted text-xs md:text-sm font-light leading-relaxed mb-4">
                                    <?php 
                                        $konten_plain = strip_tags($berita['konten']);
                                        echo (strlen($konten_plain) > 150) ? substr($konten_plain, 0, 147) . '...' : $konten_plain; 
                                    ?>
                                </p>
                            </div>
                            <div class="mt-2">
                                <a href="<?php echo $target_link; ?>" class="text-jazz-gold hover:text-white text-xs font-semibold transition-colors duration-300 focus:outline-none inline-flex items-center gap-1">
                                    Baca Selengkapnya &rarr;
                                </a>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
            
            <div class="text-center mt-10">
                <a href="berita" class="inline-block bg-zinc-900 hover:bg-jazz-gold/10 border border-white/10 hover:border-jazz-gold text-white font-bold uppercase tracking-wider text-xs px-8 py-3.5 rounded-lg transition-all duration-300">Lihat Selengkapnya</a>
            </div>
        </section>
        <?php endif; ?>

        <!-- 2. Jadwal & Pemesanan -->
        <section id="schedules-section" class="mb-20">
            <h2 class="font-heading text-2xl md:text-3xl text-white mb-2 tracking-wide text-center">Jadwal Minggu Ini</h2>
            <p class="text-jazz-muted text-xs md:text-sm mb-10 text-center">Reservasi tiket pertunjukan eksklusif (Kapasitas Maksimal 50 Kursi)</p>
            
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-6">
                <?php if (empty($jadwal_list)): ?>
                    <div class="col-span-4 text-center py-10 text-jazz-muted">
                        Belum ada jadwal pertunjukan minggu ini.
                    </div>
                <?php else: ?>
                    <?php foreach ($jadwal_list as $row): 
                        $sisa_kuota = $row['kuota'] - $row['terjual'];
                        $persen_terisi = ($row['terjual'] / $row['kuota']) * 100;
                        $is_special = ($row['is_special'] == 1);
                        $is_closed = (strtolower($row['status']) === 'closed');
                        $text_color = $is_special ? 'text-amber-500' : 'text-jazz-gold';
                        $icon_color = $is_special ? 'text-amber-500/70' : 'text-jazz-gold/70';
                        $note_style = $is_special ? 'bg-amber-500/5 border-amber-500/20 text-amber-500' : 'bg-jazz-gold/5 border-jazz-gold/20 text-jazz-gold';
                        $btn_style = $is_special ? 'bg-gradient-to-r from-amber-500 to-yellow-600 text-jazz-darkest font-bold shadow-amber-500/10 hover:brightness-110' : 'bg-jazz-gold hover:bg-jazz-goldDark text-white shadow-jazz-gold/10';
                    ?>
                        <div class="glass-card rounded-xl p-6 flex flex-col justify-between relative overflow-hidden transition-all duration-300 <?php echo $is_special ? 'special-event-glow' : 'hover:border-jazz-gold/30'; ?>">
                            
                            <div>
                                <div class="flex justify-between items-start mb-4">
                                    <span class="<?php echo $text_color; ?> font-bold text-xs tracking-wider uppercase"><?php echo $row['hari']; ?></span>
                                    <span class="text-jazz-muted text-[10px]"><?php echo date('d M Y', strtotime($row['tanggal'])); ?></span>
                                </div>
                                
                                <h3 class="text-white text-base font-heading font-semibold mb-2 tracking-wide leading-snug">
                                    <?php echo htmlspecialchars($row['nama_event']); ?>
                                </h3>
                                
                                <div class="text-xs text-gray-400 mb-6 flex items-center gap-1.5 font-light">
                                    <svg class="h-3.5 w-3.5 <?php echo $icon_color; ?>" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                                    </svg>
                                    Open: <?php echo htmlspecialchars($row['jam']); ?>
                                </div>
                                
                                <?php if (!empty($row['special_notes'])): ?>
                                    <div class="<?php echo $note_style; ?> border rounded-md p-3 mb-6 text-[11px] font-light leading-relaxed">
                                        <strong>Notes:</strong> <?php echo htmlspecialchars($row['special_notes']); ?>
                                    </div>
                                <?php endif; ?>
                            </div>
                            
                            <div>
                                <!-- Quota Bar -->
                                <div class="mb-5">
                                    <div class="flex justify-between text-[11px] text-jazz-muted mb-1">
                                        <span>Tiket Terjual</span>
                                        <span class="font-medium text-white"><?php echo $row['terjual']; ?> / <?php echo $row['kuota']; ?></span>
                                    </div>
                                    <div class="w-full h-1.5 bg-jazz-darkest rounded-full overflow-hidden border border-white/5">
                                        <?php 
                                            $bar_color = "bg-green-500";
                                            if ($sisa_kuota <= 0) $bar_color = "bg-jazz-crimson";
                                            elseif ($sisa_kuota <= 15) $bar_color = "bg-yellow-500";
                                        ?>
                                        <div class="h-full rounded-full <?php echo $bar_color; ?>" style="width: <?php echo $persen_terisi; ?>%"></div>
                                    </div>
                                    <?php if ($sisa_kuota > 0 && $sisa_kuota <= 15 && !$is_closed): ?>
                                        <span class="text-[9px] text-yellow-500/80 mt-1 block font-medium">Sisa tiket menipis!</span>
                                    <?php endif; ?>
                                </div>
                                
                                <!-- Action Button -->
                                <div>
                                    <?php if ($is_closed): ?>
                                        <button disabled class="w-full bg-jazz-crimson/10 border border-jazz-crimson/30 text-jazz-crimson/70 text-center font-bold uppercase tracking-wider py-2.5 rounded-lg text-xs cursor-not-allowed">Closed</button>
                                    <?php elseif ($sisa_kuota <= 0): ?>
                                        <button disabled class="w-full bg-neutral-900 border border-neutral-800 text-neutral-600 text-center font-bold uppercase tracking-wider py-2.5 rounded-lg text-xs cursor-not-allowed">Sold Out</button>
                                    <?php elseif (!$is_logged_in): ?>
                                        <!-- Belum login: arahkan ke login dengan parameter redirect -->
                                        <a href="login?redirect=pesan%3Fid_jadwal%3D<?php echo $row['id']; ?>" 
                                           class="block w-full <?php echo $btn_style; ?> text-center uppercase tracking-wider py-2.5 rounded-lg text-xs transform hover:-translate-y-0.5 transition-all duration-300 shadow-md">
                                            Pesan Tiket
                                        </a>
                                    <?php else: ?>
                                        <!-- Sudah login: langsung ke halaman pesan -->
                                        <a href="pesan?id_jadwal=<?php echo $row['id']; ?>" 
                                           class="block w-full <?php echo $btn_style; ?> text-center uppercase tracking-wider py-2.5 rounded-lg text-xs transform hover:-translate-y-0.5 transition-all duration-300 shadow-md">
                                            Pesan Tiket
                                        </a>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
            
            <div class="text-center mt-12">
                <a href="event" class="inline-block bg-zinc-900 hover:bg-jazz-gold/10 border border-white/10 hover:border-jazz-gold text-white font-bold uppercase tracking-wider text-xs px-8 py-3.5 rounded-lg transition-all duration-300">Lihat Selengkapnya &amp; Riwayat Event</a>
            </div>
        </section>

        <!-- Upcoming Special Shows Section -->
        <section id="upcoming-specials-section" class="mb-20">
            <h2 class="font-heading text-2xl md:text-3xl text-white mb-2 tracking-wide text-center">Upcoming Special Shows</h2>
            <p class="text-jazz-muted text-xs md:text-sm mb-10 text-center text-amber-500">Pertunjukan Spesial Eksklusif Yang Akan Datang (Dapat Dipesan Jauh Hari)</p>
            
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8">
                <?php if (empty($upcoming_special_list)): ?>
                    <div class="col-span-3 text-center py-12 text-jazz-muted bg-jazz-card/10 border border-white/5 rounded-xl">
                        Belum ada pertunjukan spesial terjadwal berikutnya.
                    </div>
                <?php else: ?>
                    <?php foreach ($upcoming_special_list as $row): 
                        $sisa_kuota = $row['kuota'] - $row['terjual'];
                        $persen_terisi = ($row['terjual'] / $row['kuota']) * 100;
                        $is_closed = (strtolower($row['status']) === 'closed');
                    ?>
                        <div class="glass-card rounded-xl p-6 flex flex-col justify-between relative overflow-hidden transition-all duration-300 special-event-glow">
                            <div>
                                <div class="flex justify-between items-start mb-4 border-b border-white/5 pb-2">
                                    <span class="text-amber-500 font-bold text-xs tracking-wider uppercase"><?php echo $row['hari']; ?></span>
                                    <span class="text-jazz-muted text-[10px]"><?php echo date('d M Y', strtotime($row['tanggal'])); ?></span>
                                </div>
                                
                                <h3 class="text-white text-lg font-heading font-semibold mb-3 tracking-wide leading-snug">
                                    <?php echo htmlspecialchars($row['nama_event']); ?>
                                </h3>
                                
                                <div class="text-xs text-gray-400 mb-6 flex items-center gap-1.5 font-light">
                                    <svg class="h-3.5 w-3.5 text-amber-500/70" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                                    </svg>
                                    Open: <?php echo htmlspecialchars($row['jam']); ?>
                                </div>
                                
                                <?php if (!empty($row['special_notes'])): ?>
                                    <div class="bg-amber-500/5 border border-amber-500/20 text-amber-500 rounded-md p-3 mb-6 text-[11px] font-light leading-relaxed font-body">
                                        <strong>Detail Show:</strong> <?php echo htmlspecialchars($row['special_notes']); ?>
                                    </div>
                                <?php endif; ?>
                            </div>
                            
                            <div>
                                <!-- Quota Bar -->
                                <div class="mb-5">
                                    <div class="flex justify-between text-[11px] text-jazz-muted mb-1">
                                        <span>Tiket Terjual</span>
                                        <span class="font-medium text-white"><?php echo $row['terjual']; ?> / <?php echo $row['kuota']; ?></span>
                                    </div>
                                    <div class="w-full h-1.5 bg-jazz-darkest rounded-full overflow-hidden border border-white/5">
                                        <?php 
                                            $bar_color = "bg-green-500";
                                            if ($sisa_kuota <= 0) $bar_color = "bg-jazz-crimson";
                                            elseif ($sisa_kuota <= 15) $bar_color = "bg-yellow-500";
                                        ?>
                                        <div class="h-full rounded-full <?php echo $bar_color; ?>" style="width: <?php echo $persen_terisi; ?>%"></div>
                                    </div>
                                </div>
                                
                                <!-- Action Button -->
                                <div>
                                    <?php if ($is_closed): ?>
                                        <button disabled class="w-full bg-jazz-crimson/10 border border-jazz-crimson/30 text-jazz-crimson/70 text-center font-bold uppercase tracking-wider py-2.5 rounded-lg text-xs cursor-not-allowed">Closed</button>
                                    <?php elseif ($sisa_kuota <= 0): ?>
                                        <button disabled class="w-full bg-neutral-900 border border-neutral-800 text-neutral-600 text-center font-bold uppercase tracking-wider py-2.5 rounded-lg text-xs cursor-not-allowed">Sold Out</button>
                                    <?php elseif (!$is_logged_in): ?>
                                        <a href="login?redirect=pesan%3Fid_jadwal%3D<?php echo $row['id']; ?>" 
                                           class="block w-full bg-gradient-to-r from-amber-500 to-yellow-600 text-jazz-darkest font-bold text-center uppercase tracking-wider py-2.5 rounded-lg text-xs transform hover:-translate-y-0.5 transition-all duration-300 shadow-md shadow-amber-500/10 hover:brightness-110">
                                            Pesan Tiket Sekarang
                                        </a>
                                    <?php else: ?>
                                        <a href="pesan?id_jadwal=<?php echo $row['id']; ?>" 
                                           class="block w-full bg-gradient-to-r from-amber-500 to-yellow-600 text-jazz-darkest font-bold text-center uppercase tracking-wider py-2.5 rounded-lg text-xs transform hover:-translate-y-0.5 transition-all duration-300 shadow-md shadow-amber-500/10 hover:brightness-110">
                                            Pesan Tiket Sekarang
                                        </a>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
            
            <div class="text-center mt-12">
                <a href="special_events" class="inline-block bg-zinc-900 hover:bg-amber-500/10 border border-white/10 hover:border-amber-500 text-white font-bold uppercase tracking-wider text-xs px-8 py-3.5 rounded-lg transition-all duration-300">Lihat Semua Event Spesial</a>
            </div>
        </section>
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
                <p class="text-jazz-muted text-xs font-light">&copy; <?php echo date('Y'); ?> The 4 Stairs Music Hall. Tugas Projek Kuliah.</p>
                <p class="text-[9px] text-neutral-600 mt-1 font-light">Classy Live Music &copy; Real Composers Association</p>
            </div>
        </div>
    </footer>

    <!-- Javascript Handlers -->
    <script src="assets/js/main.js"></script>
</body>
</html>
