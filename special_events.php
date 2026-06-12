<?php
/**
 * THE 4 STAIRS - DEDICATED SPECIAL EVENTS PAGE
 * -------------------------------------------
 * Menampilkan seluruh daftar pertunjukan spesial (Special Shows) mendatang
 * yang dijadwalkan di The 4 Stairs Music Hall.
 */

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

include 'koneksi.php';

$is_logged_in = isset($_SESSION['user_logged_in']) && $_SESSION['user_logged_in'] === true;

$special_events = [];
if (isset($conn) && !$conn->connect_error && $db_selected) {
    // Ambil semua event spesial mendatang (is_special = 1 dan tanggal >= CURDATE())
    $res = $conn->query("SELECT * FROM `jadwal` WHERE `is_special` = 1 AND `tanggal` >= CURDATE() ORDER BY `tanggal` ASC");
    if ($res) {
        while ($row = $res->fetch_assoc()) {
            $special_events[] = $row;
        }
    }
}
?>
<!DOCTYPE html>
<html lang="id" class="scroll-smooth">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Event Spesial - The 4 Stairs Music Hall</title>
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
                            gold: '#dfb15b',       // yellow-gold color for special show accents
                            goldDark: '#b58930',   // darker yellow-gold
                            crimson: '#8b1e22',    // regular retro-red
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
            <span class="text-jazz-gold text-xs font-semibold uppercase tracking-widest mb-3 block">Exclusive Concerts</span>
            <h1 class="font-heading text-4xl md:text-5xl font-bold text-white mb-4">Event Spesial & Konser</h1>
            <p class="text-jazz-muted text-sm md:text-base max-w-2xl mx-auto font-light leading-relaxed">
                Jelajahi jajaran pertunjukan khusus, kolaborasi orkestra, tribut musik eksklusif, serta penampilan bintang tamu pilihan di panggung The 4 Stairs.
            </p>
        </div>

        <!-- Special Events Grid -->
        <?php if (empty($special_events)): ?>
            <div class="text-center py-20 text-jazz-muted italic bg-jazz-card/10 border border-white/5 rounded-2xl">
                <p class="mb-4">Belum ada jadwal event spesial yang diumumkan untuk saat ini.</p>
                <a href="index" class="text-jazz-gold hover:text-white font-semibold text-xs underline transition-colors">&larr; Kembali ke Beranda</a>
            </div>
        <?php else: ?>
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8">
                <?php foreach ($special_events as $row): 
                    $sisa_kuota = $row['kuota'] - $row['terjual'];
                    $persen_terisi = ($row['terjual'] / $row['kuota']) * 100;
                    $is_closed = (strtolower($row['status']) === 'closed');
                ?>
                    <div class="glass-card rounded-2xl p-6 flex flex-col justify-between relative overflow-hidden transition-all duration-300 special-event-glow">
                        <div>
                            <!-- Header Info -->
                            <div class="flex justify-between items-start mb-4 border-b border-white/5 pb-3">
                                <span class="text-jazz-gold font-bold text-xs tracking-wider uppercase"><?php echo $row['hari']; ?></span>
                                <span class="text-jazz-muted text-[11px]"><?php echo date('d F Y', strtotime($row['tanggal'])); ?></span>
                            </div>
                            
                            <!-- Title -->
                            <h3 class="text-white text-xl font-heading font-bold mb-3 tracking-wide leading-snug">
                                <?php echo htmlspecialchars($row['nama_event']); ?>
                            </h3>
                            
                            <!-- Operational Hours -->
                            <div class="text-xs text-gray-400 mb-6 flex items-center gap-1.5 font-light">
                                <svg class="h-3.5 w-3.5 text-jazz-gold/80" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                                </svg>
                                Pintu Dibuka: <?php echo htmlspecialchars($row['jam']); ?>
                            </div>
                            
                            <!-- Special Notes Box -->
                            <?php if (!empty($row['special_notes'])): ?>
                                <div class="bg-jazz-gold/5 border border-jazz-gold/25 rounded-xl p-4 mb-6 text-xs text-jazz-gold font-light leading-relaxed">
                                    <strong>Detail Show:</strong> <?php echo htmlspecialchars($row['special_notes']); ?>
                                </div>
                            <?php endif; ?>
                        </div>
                        
                        <!-- Quota & Booking Section -->
                        <div>
                            <div class="mb-5">
                                <div class="flex justify-between text-xs text-jazz-muted mb-1">
                                    <span>Keterisian Tiket</span>
                                    <span class="font-medium text-white"><?php echo $row['terjual']; ?> / <?php echo $row['kuota']; ?> Kursi</span>
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
                                    <button disabled class="w-full bg-jazz-crimson/10 border border-jazz-crimson/30 text-jazz-crimson/70 text-center font-bold uppercase tracking-wider py-2.5 rounded-lg text-xs cursor-not-allowed">Pemesanan Ditutup</button>
                                <?php elseif ($sisa_kuota <= 0): ?>
                                    <button disabled class="w-full bg-neutral-900 border border-neutral-800 text-neutral-600 text-center font-bold uppercase tracking-wider py-2.5 rounded-lg text-xs cursor-not-allowed">Habis Terjual</button>
                                <?php elseif (!$is_logged_in): ?>
                                    <a href="login?redirect=pesan%3Fid_jadwal%3D<?php echo $row['id']; ?>" 
                                       class="block w-full bg-gradient-to-r from-jazz-gold to-jazz-goldDark text-jazz-darkest text-center font-bold uppercase tracking-wider py-2.5 rounded-lg text-xs transform hover:-translate-y-0.5 transition-all duration-300 shadow-md shadow-jazz-gold/15">
                                        Pesan Tiket Sekarang
                                    </a>
                                <?php else: ?>
                                    <a href="pesan?id_jadwal=<?php echo $row['id']; ?>" 
                                       class="block w-full bg-gradient-to-r from-jazz-gold to-jazz-goldDark text-jazz-darkest text-center font-bold uppercase tracking-wider py-2.5 rounded-lg text-xs transform hover:-translate-y-0.5 transition-all duration-300 shadow-md shadow-jazz-gold/15">
                                        Pesan Tiket Sekarang
                                    </a>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

    </main>

    <!-- Footer Section -->
    <footer class="bg-jazz-darkest border-t border-jazz-gold/10 py-12 mt-20">
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
