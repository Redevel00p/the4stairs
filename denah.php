<?php
/**
 * THE 4 STAIRS MUSIC HALL - ROOM LAYOUT PLAN (DENAH RUANGAN)
 * ---------------------------------------------------------
 * Halaman terpisah yang menampilkan tata letak area penonton eksklusif.
 */
include 'koneksi.php';
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Denah Ruangan - The 4 Stairs Music Hall</title>
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
</head>
<body class="bg-jazz-darkest text-jazz-light font-body flex flex-col min-h-screen overflow-x-hidden pt-24">

    <!-- Premium Navigation Bar -->
    <?php include 'navbar.php'; ?>

    <!-- Main Content -->
    <main class="max-w-6xl mx-auto px-6 py-12 flex-grow w-full">
        
        <div class="text-center mb-12">
            <span class="text-jazz-gold text-xs font-semibold uppercase tracking-widest mb-3 block">Exclusive Live Venue Layout</span>
            <h1 class="font-heading text-4xl md:text-5xl font-bold text-white mb-4">Denah Ruangan & Tata Letak</h1>
            <p class="text-jazz-muted text-sm md:text-base max-w-2xl mx-auto font-light leading-relaxed">
                Setiap zona kursi dirancang secara akustik untuk memberikan kedekatan optimal dengan panggung utama demi suasana konser yang intim dan megah.
            </p>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-12 gap-8 items-start mb-16">
            
            <!-- Floor Plan Image Box -->
            <div class="lg:col-span-7 bg-jazz-card/50 border border-jazz-gold/15 rounded-2xl p-6 md:p-8 shadow-2xl flex flex-col items-center">
                <span class="text-[10px] text-jazz-gold tracking-widest uppercase font-semibold mb-4 block align-self-start">Floor Plan Visual</span>
                <div class="w-full bg-jazz-darkest rounded-xl border border-white/5 p-4 flex items-center justify-center shadow-inner relative overflow-hidden group">
                    <img src="assets/img/denah.png" alt="Denah Seating Area - The 4 Stairs Music Hall" class="max-h-[500px] object-contain rounded-lg transition-transform duration-500 group-hover:scale-105">
                    
                    <!-- Decorative scanning effect -->
                    <div class="absolute inset-0 bg-gradient-to-b from-jazz-gold/0 via-jazz-gold/5 to-jazz-gold/0 pointer-events-none opacity-40"></div>
                </div>
            </div>
            
            <!-- Explanations & CTA -->
            <div class="lg:col-span-5 flex flex-col gap-6">
                <div class="glass-card rounded-2xl p-6 md:p-8 border border-jazz-gold/10">
                    <h3 class="font-heading text-xl font-semibold text-white mb-6 border-b border-white/5 pb-3">Informasi Zona Kursi</h3>
                    
                    <div class="flex flex-col gap-5">
                        <div class="flex gap-4">
                            <div class="w-8 h-8 rounded-lg bg-jazz-gold/15 border border-jazz-gold flex items-center justify-center font-bold text-jazz-gold text-xs shrink-0">A</div>
                            <div>
                                <h4 class="text-white text-xs font-semibold uppercase tracking-wider mb-1">Zona VIP Front Row (Kursi 1 - 15)</h4>
                                <p class="text-jazz-muted text-xs font-light leading-relaxed">Terletak tepat di depan panggung utama. Posisi premium untuk menikmati penampilan band secara langsung dengan kualitas suara paling optimal.</p>
                            </div>
                        </div>
                        
                        <div class="flex gap-4">
                            <div class="w-8 h-8 rounded-lg bg-jazz-crimson/15 border border-jazz-crimson flex items-center justify-center font-bold text-jazz-crimson text-xs shrink-0">B</div>
                            <div>
                                <h4 class="text-white text-xs font-semibold uppercase tracking-wider mb-1">Zona Sofa Lounge (Kursi 16 - 30)</h4>
                                <p class="text-jazz-muted text-xs font-light leading-relaxed">Terletak di sayap kiri dan kanan panggung. Menggunakan sofa empuk melingkar yang sangat nyaman untuk menonton konser bersama teman.</p>
                            </div>
                        </div>
                        
                        <div class="flex gap-4">
                            <div class="w-8 h-8 rounded-lg bg-jazz-muted/15 border border-jazz-muted flex items-center justify-center font-bold text-jazz-muted text-xs shrink-0">C</div>
                            <div>
                                <h4 class="text-white text-xs font-semibold uppercase tracking-wider mb-1">Zona Balcony & Side Seats (Kursi 31 - 50)</h4>
                                <p class="text-jazz-muted text-xs font-light leading-relaxed">Berada di area barisan belakang dan samping. Sangat cocok bagi penonton yang ingin menikmati konser dengan pandangan menyeluruh ke arah panggung.</p>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="glass-card rounded-2xl p-6 md:p-8 border border-jazz-gold/15 relative overflow-hidden flex flex-col justify-between">
                    <div class="absolute top-0 right-0 bg-jazz-gold/10 text-jazz-gold text-[9px] uppercase tracking-widest font-semibold px-3 py-1 rounded-bl-lg">Maximum 50 Seats</div>
                    <div>
                        <h3 class="font-heading text-lg font-semibold text-white mb-2">Reservasi Tiket Anda</h3>
                        <p class="text-jazz-muted text-xs font-light leading-relaxed mb-6">
                            Karena kapasitas tempat duduk kami yang terbatas demi menjaga atmosfer intim dan kualitas akustik pertunjukan, kami menyarankan pemesanan tiket jauh-jauh hari sebelum show dimulai.
                        </p>
                    </div>
                    <a href="pesan" class="block w-full bg-gradient-to-r from-jazz-gold to-jazz-goldDark text-white text-center font-bold uppercase tracking-wider py-3 rounded-lg text-xs hover:shadow-lg hover:shadow-jazz-gold/20 transform hover:-translate-y-0.5 transition-all duration-300">Pesan Tiket Sekarang</a>
                </div>
            </div>
            
        </div>
        
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

</body>
</html>
