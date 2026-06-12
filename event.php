<?php
/**
 * THE 4 STAIRS - DEDICATED EVENT PAGE
 * ----------------------------------
 * Menampilkan jadwal pertunjukan minggu ini dan riwayat jadwal/event sebelumnya.
 */

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

include 'koneksi.php';

$is_logged_in = isset($_SESSION['user_logged_in']) && $_SESSION['user_logged_in'] === true;

$schedules_this_week = [];
$past_schedules = [];

if (isset($conn) && !$conn->connect_error && $db_selected) {
    // 1. Ambil jadwal 7 hari (Minggu Ini: Senin - Minggu)
    $sql_this_week = "SELECT * FROM `jadwal` 
                      WHERE `tanggal` BETWEEN DATE_ADD(CURDATE(), INTERVAL 0 - WEEKDAY(CURDATE()) DAY) 
                                          AND DATE_ADD(CURDATE(), INTERVAL 6 - WEEKDAY(CURDATE()) DAY)
                      ORDER BY `tanggal` ASC";
    $res_this_week = $conn->query($sql_this_week);
    if ($res_this_week) {
        while ($row = $res_this_week->fetch_assoc()) {
            $schedules_this_week[] = $row;
        }
    }

    // 2. Ambil riwayat jadwal sebelumnya (tanggal sebelum Senin minggu ini)
    $sql_past = "SELECT * FROM `jadwal` 
                 WHERE `tanggal` < DATE_ADD(CURDATE(), INTERVAL 0 - WEEKDAY(CURDATE()) DAY) 
                 ORDER BY `tanggal` DESC";
    $res_past = $conn->query($sql_past);
    if ($res_past) {
        while ($row = $res_past->fetch_assoc()) {
            $past_schedules[] = $row;
        }
    }
}
?>
<!DOCTYPE html>
<html lang="id" class="scroll-smooth">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Jadwal & Riwayat Event - The 4 Stairs Music Hall</title>
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
        
        <!-- Header -->
        <div class="text-center mb-16">
            <span class="text-jazz-gold text-xs font-semibold uppercase tracking-widest mb-3 block">Live Music Schedules</span>
            <h1 class="font-heading text-4xl md:text-5xl font-bold text-white mb-4">Pertunjukan & Event</h1>
            <p class="text-jazz-muted text-sm md:text-base max-w-2xl mx-auto font-light leading-relaxed">
                Temukan jadwal lengkap penampilan band minggu ini atau jelajahi riwayat pertunjukan spektakuler yang pernah diadakan di The 4 Stairs.
            </p>
        </div>

        <!-- SECTION 1: MINGGU INI -->
        <section class="mb-20">
            <div class="flex flex-col md:flex-row md:items-center justify-between gap-4 mb-10 border-b border-white/5 pb-4">
                <div>
                    <h2 class="font-heading text-2xl md:text-3xl text-white tracking-wide">Jadwal Minggu Ini</h2>
                    <p class="text-jazz-muted text-xs md:text-sm mt-1">Konser live terjadwal untuk periode hari Senin sampai Minggu pekan ini</p>
                </div>
                <div class="text-xs text-jazz-muted bg-jazz-card/80 border border-white/5 px-3 py-1.5 rounded-lg">
                    Monday to Sunday Stage
                </div>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-6">
                <?php if (empty($schedules_this_week)): ?>
                    <div class="col-span-4 text-center py-10 text-jazz-muted italic">
                        Belum ada jadwal pertunjukan untuk minggu ini.
                    </div>
                <?php else: ?>
                    <?php foreach ($schedules_this_week as $row): 
                        $sisa_kuota = $row['kuota'] - $row['terjual'];
                        $persen_terisi = ($row['terjual'] / $row['kuota']) * 100;
                        $is_special = ($row['is_special'] == 1);
                        $is_closed = (strtolower($row['status']) === 'closed');
                        $text_color = $is_special ? 'text-amber-500' : 'text-jazz-gold';
                        $icon_color = $is_special ? 'text-amber-500/70' : 'text-jazz-gold/70';
                        $note_style = $is_special ? 'bg-amber-500/5 border-amber-500/20 text-amber-500' : 'bg-jazz-gold/5 border-jazz-gold/20 text-jazz-gold';
                        $btn_style = $is_special ? 'bg-gradient-to-r from-amber-500 to-yellow-600 text-jazz-darkest font-bold shadow-amber-500/10 hover:brightness-110' : 'bg-jazz-gold hover:bg-jazz-goldDark text-white shadow-jazz-gold/10';
                    ?>
                        <div class="glass-card rounded-xl p-5 flex flex-col justify-between relative overflow-hidden transition-all duration-300 <?php echo $is_special ? 'special-event-glow' : 'hover:border-jazz-gold/30'; ?>">
                            
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
                                </div>
                                
                                <!-- Action Button -->
                                <div>
                                    <?php if ($is_closed): ?>
                                        <button disabled class="w-full bg-jazz-crimson/10 border border-jazz-crimson/30 text-jazz-crimson/70 text-center font-bold uppercase tracking-wider py-2 rounded-lg text-xs cursor-not-allowed">Closed</button>
                                    <?php elseif ($sisa_kuota <= 0): ?>
                                        <button disabled class="w-full bg-neutral-900 border border-neutral-800 text-neutral-600 text-center font-bold uppercase tracking-wider py-2 rounded-lg text-xs cursor-not-allowed">Sold Out</button>
                                    <?php elseif (!$is_logged_in): ?>
                                        <a href="login?redirect=pesan%3Fid_jadwal%3D<?php echo $row['id']; ?>" 
                                           class="block w-full <?php echo $btn_style; ?> text-center uppercase tracking-wider py-2 rounded-lg text-xs transform hover:-translate-y-0.5 transition-all duration-300 shadow-md">
                                            Pesan Tiket
                                        </a>
                                    <?php else: ?>
                                        <a href="pesan?id_jadwal=<?php echo $row['id']; ?>" 
                                           class="block w-full <?php echo $btn_style; ?> text-center uppercase tracking-wider py-2 rounded-lg text-xs transform hover:-translate-y-0.5 transition-all duration-300 shadow-md">
                                            Pesan Tiket
                                        </a>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </section>

        <!-- SECTION 2: RIWAYAT EVENT -->
        <section>
            <div class="mb-10 border-b border-white/5 pb-4">
                <h2 class="font-heading text-2xl md:text-3xl text-white tracking-wide">Riwayat Event</h2>
                <p class="text-jazz-muted text-xs md:text-sm mt-1">Daftar jadwal pertunjukan dan pagelaran musik yang sudah berlalu</p>
            </div>

            <?php if (empty($past_schedules)): ?>
                <p class="text-jazz-muted italic text-sm">Belum ada riwayat pertunjukan sebelumnya yang tercatat di database.</p>
            <?php else: ?>
                <div class="overflow-x-auto bg-jazz-card/30 border border-white/5 rounded-2xl shadow-2xl">
                    <table class="w-full text-left border-collapse text-xs md:text-sm">
                        <thead>
                            <tr class="bg-black/35 border-b border-white/5 text-jazz-gold font-semibold uppercase tracking-wider text-[10px] md:text-xs">
                                <th class="px-6 py-4">Hari & Tanggal</th>
                                <th class="px-6 py-4">Nama Event</th>
                                <th class="px-6 py-4">Jam</th>
                                <th class="px-6 py-4">Statistik Penjualan</th>
                                <th class="px-6 py-4">Catatan Acara</th>
                                <th class="px-6 py-4 text-right">Status</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-white/5 text-zinc-300">
                            <?php foreach ($past_schedules as $past): 
                                $tgl_fmt = date('d M Y', strtotime($past['tanggal']));
                                $persen_sold = ($past['terjual'] / $past['kuota']) * 100;
                            ?>
                                <tr class="hover:bg-white/[0.01] transition-colors">
                                    <td class="px-6 py-4">
                                        <div class="font-semibold text-white"><?php echo $past['hari']; ?></div>
                                        <div class="text-[10px] text-jazz-muted mt-0.5"><?php echo $tgl_fmt; ?></div>
                                    </td>
                                    <td class="px-6 py-4 font-semibold text-white"><?php echo htmlspecialchars($past['nama_event']); ?></td>
                                    <td class="px-6 py-4 font-mono text-zinc-400"><?php echo htmlspecialchars($past['jam']); ?></td>
                                    <td class="px-6 py-4">
                                        <div class="font-medium text-white"><?php echo $past['terjual']; ?> / <?php echo $past['kuota']; ?> Tiket</div>
                                        <div class="w-24 h-1 bg-neutral-900 rounded-full mt-1.5 overflow-hidden">
                                            <div class="h-full bg-jazz-gold rounded-full" style="width: <?php echo $persen_sold; ?>%"></div>
                                        </div>
                                    </td>
                                    <td class="px-6 py-4 text-xs text-jazz-muted max-w-xs truncate">
                                        <?php echo !empty($past['special_notes']) ? htmlspecialchars($past['special_notes']) : '-'; ?>
                                    </td>
                                    <td class="px-6 py-4 text-right">
                                        <span class="inline-flex px-2 py-0.5 text-[9px] uppercase tracking-wider font-extrabold border border-zinc-700/60 text-zinc-500 rounded bg-zinc-950/20">Selesai</span>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </section>

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
