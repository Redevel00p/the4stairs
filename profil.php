<?php
/**
 * THE 4 STAIRS MUSIC HALL - PROFILE PAGE (PROFIL)
 * -----------------------------------------------
 * Halaman profil yang berisi informasi Tentang Kami, FAQ interaktif,
 * Kontak, dan Denah Ruangan (relokasi dari denah.php).
 */
include 'koneksi.php';
?>
<!DOCTYPE html>
<html lang="id" class="scroll-smooth">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Profil & Denah Ruangan - The 4 Stairs Music Hall</title>
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
<body class="bg-jazz-darkest text-jazz-light font-body flex flex-col min-h-screen overflow-x-hidden pt-20">

    <!-- Premium Navigation Bar -->
    <?php include 'navbar.php'; ?>

    <!-- Header / Hero Section -->
    <header class="relative w-full py-16 md:py-24 bg-gradient-to-b from-jazz-card/30 to-jazz-darkest border-b border-jazz-gold/10">
        <div class="max-w-6xl mx-auto px-6 text-center">
            <span class="text-jazz-gold text-xs font-semibold uppercase tracking-widest mb-3 block">Discover The 4 Stairs</span>
            <h1 class="font-heading text-4xl md:text-5xl lg:text-6xl font-bold text-white mb-6">Profil & Informasi</h1>
            <p class="text-jazz-muted text-sm md:text-base max-w-2xl mx-auto font-light leading-relaxed">
                Pelajari sejarah kami, temukan tata letak kursi eksklusif kami, hubungi tim kami, atau baca daftar pertanyaan umum seputar pertunjukan di The 4 Stairs Music Hall.
            </p>
        </div>
    </header>

    <!-- Main Content -->
    <main class="max-w-6xl mx-auto px-6 py-16 flex-grow w-full">
        
        <!-- Tab Navigation for smooth scroll anchors -->
        <div class="flex flex-wrap justify-center gap-4 mb-16 border-b border-white/5 pb-6">
            <a href="#tentang-kami" class="px-5 py-2.5 rounded-full bg-jazz-card border border-white/5 hover:border-jazz-gold text-xs font-semibold uppercase tracking-wider transition-all duration-300 hover:text-white">Tentang Kami</a>
            <a href="#denah-ruangan" class="px-5 py-2.5 rounded-full bg-jazz-card border border-white/5 hover:border-jazz-gold text-xs font-semibold uppercase tracking-wider transition-all duration-300 hover:text-white">Denah Ruangan</a>
            <a href="#faq" class="px-5 py-2.5 rounded-full bg-jazz-card border border-white/5 hover:border-jazz-gold text-xs font-semibold uppercase tracking-wider transition-all duration-300 hover:text-white">FAQ</a>
            <a href="#kontak" class="px-5 py-2.5 rounded-full bg-jazz-card border border-white/5 hover:border-jazz-gold text-xs font-semibold uppercase tracking-wider transition-all duration-300 hover:text-white">Hubungi Kami</a>
        </div>

        <!-- Section 1: Tentang Kami -->
        <section id="tentang-kami" class="mb-24 scroll-mt-24">
            <div class="grid grid-cols-1 lg:grid-cols-12 gap-12 items-center">
                <div class="lg:col-span-6">
                    <span class="text-jazz-gold text-xs font-semibold uppercase tracking-widest mb-2 block">Our Story</span>
                    <h2 class="font-heading text-2xl md:text-4xl font-bold text-white mb-6">The 4 Stairs Music Hall</h2>
                    <div class="text-jazz-muted text-sm md:text-base font-light leading-relaxed flex flex-col gap-4">
                        <p>
                            Didirikan sebagai wadah apresiasi seni musik yang mendalam, <strong class="text-white font-medium">The 4 Stairs</strong> bukan sekadar kafe atau tempat makan biasa. Kami adalah sebuah <em class="text-jazz-gold font-medium">exclusive concert hall & live venue</em> intim yang dirancang khusus untuk memanjakan telinga para pecinta musik.
                        </p>
                        <p>
                            Dengan konsep tata ruang yang melingkar mendekati panggung utama, kami menghadirkan kedekatan emosional luar biasa antara musisi dan penonton. Di sini, setiap getaran instrumen, bisikan melodi, dan ekspresi vokal dapat dirasakan secara langsung tanpa sekat pembatas yang dingin.
                        </p>
                        <p>
                            Setiap hari dalam seminggu, kami mempersembahkan rangkaian show dengan genre yang dikurasi secara ketat—mulai dari melodi akustik, sesi jazz fusion yang berenergi, hingga pertunjukan blues klasik. Semua dimainkan oleh musisi berbakat dengan aransemen orisinal yang memukau.
                        </p>
                    </div>
                </div>
                <div class="lg:col-span-6 relative">
                    <div class="rounded-2xl overflow-hidden border border-jazz-gold/15 shadow-2xl relative group">
                        <img src="assets/img/jazz_club_bg.png" alt="Interior The 4 Stairs Music Hall" class="w-full h-auto object-cover transform hover:scale-105 transition-transform duration-700">
                        <div class="absolute inset-0 bg-gradient-to-t from-jazz-darkest/70 via-transparent to-transparent pointer-events-none"></div>
                    </div>
                    <!-- Decorative floating badge -->
                    <div class="absolute -bottom-6 -left-6 bg-gradient-to-r from-jazz-gold to-jazz-goldDark text-white p-6 rounded-xl border border-white/10 shadow-xl hidden md:block">
                        <p class="font-heading text-3xl font-bold">50</p>
                        <p class="text-[10px] uppercase tracking-wider font-semibold text-white/80">Kapasitas Kursi Maksimal</p>
                    </div>
                </div>
            </div>
        </section>

        <!-- Section 2: Denah Ruangan (Relocated from denah.php) -->
        <section id="denah-ruangan" class="mb-24 scroll-mt-24">
            <div class="text-center mb-12">
                <span class="text-jazz-gold text-xs font-semibold uppercase tracking-widest mb-3 block">Exclusive Live Venue Layout</span>
                <h2 class="font-heading text-2xl md:text-4xl font-bold text-white mb-4">Denah Ruangan & Tata Letak</h2>
                <p class="text-jazz-muted text-xs md:text-sm max-w-2xl mx-auto font-light leading-relaxed">
                    Setiap zona kursi dirancang secara akustik untuk memberikan kedekatan optimal dengan panggung utama demi suasana konser yang intim dan megah.
                </p>
            </div>

            <div class="grid grid-cols-1 lg:grid-cols-12 gap-8 items-start">
                
                <!-- Floor Plan Image Box -->
                <div class="lg:col-span-7 bg-jazz-card/50 border border-jazz-gold/15 rounded-2xl p-6 md:p-8 shadow-2xl flex flex-col items-center">
                    <span class="text-[10px] text-jazz-gold tracking-widest uppercase font-semibold mb-4 align-self-start">Floor Plan Visual</span>
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
        </section>

        <!-- Section 3: FAQ (Frequently Asked Questions) -->
        <section id="faq" class="mb-24 scroll-mt-24">
            <div class="text-center mb-12">
                <span class="text-jazz-gold text-xs font-semibold uppercase tracking-widest mb-3 block">Got Questions?</span>
                <h2 class="font-heading text-2xl md:text-4xl font-bold text-white mb-4">Frequently Asked Questions</h2>
                <p class="text-jazz-muted text-xs md:text-sm max-w-2xl mx-auto font-light leading-relaxed">
                    Pertanyaan yang sering diajukan mengenai pertunjukan, sistem reservasi, tata cara pembayaran, dan aturan kunjungan di The 4 Stairs Music Hall.
                </p>
            </div>

            <div class="max-w-3xl mx-auto flex flex-col gap-4">
                
                <!-- FAQ Item 1 -->
                <div class="glass-card border border-white/5 rounded-xl overflow-hidden transition-all duration-300">
                    <button class="faq-toggle w-full p-6 text-left flex justify-between items-center text-white font-medium text-sm md:text-base hover:text-jazz-gold transition-colors duration-300 focus:outline-none">
                        <span>Bagaimana cara melakukan reservasi tiket pertunjukan?</span>
                        <span class="faq-icon text-jazz-gold text-lg transition-transform duration-300 font-bold">+</span>
                    </button>
                    <div class="faq-content max-h-0 overflow-hidden transition-all duration-300 ease-in-out bg-jazz-darkest/40">
                        <div class="p-6 pt-0 text-jazz-muted text-xs md:text-sm font-light leading-relaxed border-t border-white/5">
                            Anda dapat memesan tiket langsung melalui website ini. Pilih menu <strong class="text-white font-normal">TIKET</strong>, pilih tanggal pertunjukan yang Anda inginkan, masukkan jumlah tiket (maksimal 4 tiket per akun), isi data diri, lalu pilih metode pembayaran.
                        </div>
                    </div>
                </div>

                <!-- FAQ Item 2 -->
                <div class="glass-card border border-white/5 rounded-xl overflow-hidden transition-all duration-300">
                    <button class="faq-toggle w-full p-6 text-left flex justify-between items-center text-white font-medium text-sm md:text-base hover:text-jazz-gold transition-colors duration-300 focus:outline-none">
                        <span>Berapa lama batas waktu pembayaran setelah pemesanan tiket?</span>
                        <span class="faq-icon text-jazz-gold text-lg transition-transform duration-300 font-bold">+</span>
                    </button>
                    <div class="faq-content max-h-0 overflow-hidden transition-all duration-300 ease-in-out bg-jazz-darkest/40">
                        <div class="p-6 pt-0 text-jazz-muted text-xs md:text-sm font-light leading-relaxed border-t border-white/5">
                            Batas waktu pembayaran adalah <strong class="text-white font-normal">15 menit</strong> terhitung sejak tiket Anda dipesan. Jika dalam waktu tersebut pembayaran belum diselesaikan dan diverifikasi, pesanan Anda akan otomatis dihapus secara permanen dari sistem untuk menjaga ketersediaan kuota bagi pembeli lain.
                        </div>
                    </div>
                </div>

                <!-- FAQ Item 3 -->
                <div class="glass-card border border-white/5 rounded-xl overflow-hidden transition-all duration-300">
                    <button class="faq-toggle w-full p-6 text-left flex justify-between items-center text-white font-medium text-sm md:text-base hover:text-jazz-gold transition-colors duration-300 focus:outline-none">
                        <span>Apakah boleh memilih nomor kursi secara spesifik saat memesan?</span>
                        <span class="faq-icon text-jazz-gold text-lg transition-transform duration-300 font-bold">+</span>
                    </button>
                    <div class="faq-content max-h-0 overflow-hidden transition-all duration-300 ease-in-out bg-jazz-darkest/40">
                        <div class="p-6 pt-0 text-jazz-muted text-xs md:text-sm font-light leading-relaxed border-t border-white/5">
                            Sistem kami menggunakan pembagian zona berdasarkan urutan kedatangan dan pemesanan. Namun, Anda dapat melihat peta visual denah ruangan di bagian atas halaman ini untuk memetakan pembagian zona VIP, Sofa Lounge, atau Balcony. Penentuan kursi detail dilakukan secara ramah oleh staff saat Anda melakukan check-in di venue.
                        </div>
                    </div>
                </div>

                <!-- FAQ Item 4 -->
                <div class="glass-card border border-white/5 rounded-xl overflow-hidden transition-all duration-300">
                    <button class="faq-toggle w-full p-6 text-left flex justify-between items-center text-white font-medium text-sm md:text-base hover:text-jazz-gold transition-colors duration-300 focus:outline-none">
                        <span>Apakah tiket yang sudah dibayar dapat dibatalkan atau direfund?</span>
                        <span class="faq-icon text-jazz-gold text-lg transition-transform duration-300 font-bold">+</span>
                    </button>
                    <div class="faq-content max-h-0 overflow-hidden transition-all duration-300 ease-in-out bg-jazz-darkest/40">
                        <div class="p-6 pt-0 text-jazz-muted text-xs md:text-sm font-light leading-relaxed border-t border-white/5">
                            Mohon maaf, tiket yang sudah berhasil dibayar tidak dapat dibatalkan atau dikembalikan (non-refundable). Namun, Anda dapat mengalihkan tiket Anda kepada orang lain dengan memberikan kode tiket unik (seperti T4S-XXXXXX) serta bukti konfirmasi pemesanan.
                        </div>
                    </div>
                </div>

                <!-- FAQ Item 5 -->
                <div class="glass-card border border-white/5 rounded-xl overflow-hidden transition-all duration-300">
                    <button class="faq-toggle w-full p-6 text-left flex justify-between items-center text-white font-medium text-sm md:text-base hover:text-jazz-gold transition-colors duration-300 focus:outline-none">
                        <span>Apakah ada aturan berpakaian (dress code) untuk menghadiri acara?</span>
                        <span class="faq-icon text-jazz-gold text-lg transition-transform duration-300 font-bold">+</span>
                    </button>
                    <div class="faq-content max-h-0 overflow-hidden transition-all duration-300 ease-in-out bg-jazz-darkest/40">
                        <div class="p-6 pt-0 text-jazz-muted text-xs md:text-sm font-light leading-relaxed border-t border-white/5">
                            Untuk menjaga kenyamanan bersama dan nuansa pertunjukan klasik kami, kami menyarankan pakaian kasual yang rapi (smart casual) atau formal. Harap menghindari celana pendek robek-robek atau sandal jepit.
                        </div>
                    </div>
                </div>

            </div>
        </section>

        <!-- Section 4: Kontak & Lokasi -->
        <section id="kontak" class="mb-12 scroll-mt-24">
            <div class="text-center mb-12">
                <span class="text-jazz-gold text-xs font-semibold uppercase tracking-widest mb-3 block">Get In Touch</span>
                <h2 class="font-heading text-2xl md:text-4xl font-bold text-white mb-4">Kontak & Lokasi Kami</h2>
                <p class="text-jazz-muted text-xs md:text-sm max-w-2xl mx-auto font-light leading-relaxed">
                    Butuh bantuan khusus, kemitraan band, atau pemesanan tempat untuk private event? Jangan ragu untuk menghubungi kami melalui kontak di bawah ini.
                </p>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                
                <!-- Contact Card 1: Address -->
                <div class="glass-card rounded-xl p-8 border border-white/5 text-center flex flex-col items-center">
                    <div class="w-12 h-12 rounded-full bg-jazz-gold/10 border border-jazz-gold/30 flex items-center justify-center mb-6 text-jazz-gold">
                        <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z" />
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z" />
                        </svg>
                    </div>
                    <h3 class="text-white text-base font-semibold uppercase tracking-wider mb-3">Alamat Venue</h3>
                    <p class="text-jazz-muted text-xs font-light leading-relaxed">
                        Jl. Malioboro No. 44, Lantai 4<br>
                        Kawasan Sosromenduran, Gedong Tengen<br>
                        Yogyakarta, Daerah Istimewa Yogyakarta 55271
                    </p>
                </div>

                <!-- Contact Card 2: Operating Hours -->
                <div class="glass-card rounded-xl p-8 border border-white/5 text-center flex flex-col items-center">
                    <div class="w-12 h-12 rounded-full bg-jazz-gold/10 border border-jazz-gold/30 flex items-center justify-center mb-6 text-jazz-gold">
                        <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                    </div>
                    <h3 class="text-white text-base font-semibold uppercase tracking-wider mb-3">Jam Operasional</h3>
                    <p class="text-jazz-muted text-xs font-light leading-relaxed">
                        Senin - Kamis: 18:00 - 23:00 WIB<br>
                        Jumat - Sabtu: 18:00 - 00:00 WIB<br>
                        Minggu: 17:00 - 23:00 WIB<br>
                        <span class="text-jazz-gold mt-2 block font-medium">Open Gate: 1 jam sebelum event dimulai</span>
                    </p>
                </div>

                <!-- Contact Card 3: WhatsApp Support -->
                <div class="glass-card rounded-xl p-8 border border-white/5 text-center flex flex-col items-center">
                    <div class="w-12 h-12 rounded-full bg-jazz-gold/10 border border-jazz-gold/30 flex items-center justify-center mb-6 text-jazz-gold">
                        <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z" />
                        </svg>
                    </div>
                    <h3 class="text-white text-base font-semibold uppercase tracking-wider mb-3">Kontak Admin</h3>
                    <p class="text-jazz-muted text-xs font-light leading-relaxed mb-4">
                        Tanyakan info reservasi tiket grup, penyewaan hall, atau info detail lainnya.
                    </p>
                    <a href="https://wa.me/628123456789" target="_blank" class="inline-flex items-center gap-2 bg-green-600 hover:bg-green-700 text-white font-bold uppercase tracking-wider text-[10px] px-5 py-2.5 rounded-lg shadow-lg hover:shadow-green-600/20 transform hover:-translate-y-0.5 transition-all duration-300">
                        <!-- WhatsApp SVG Icon -->
                        <svg class="w-4 h-4 fill-current" viewBox="0 0 24 24">
                            <path d="M.057 24l1.687-6.163c-1.041-1.804-1.588-3.849-1.587-5.946C.06 5.348 5.397.01 12.008.01c3.202.001 6.212 1.246 8.477 3.514 2.266 2.268 3.507 5.28 3.505 8.484-.004 6.657-5.34 11.997-11.953 11.997-2.005-.001-3.973-.502-5.724-1.455L0 24zm6.59-4.846c1.6.95 3.488 1.459 5.407 1.46h.007c5.855 0 10.618-4.761 10.621-10.619.002-2.837-1.102-5.505-3.108-7.513C17.569 4.475 14.9 3.371 12.06 3.37c-5.86 0-10.627 4.76-10.63 10.619-.001 1.884.49 3.73 1.42 5.34L1.968 22.25l4.679-1.256zM17.47 15.1c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347z"/>
                        </svg>
                        Hubungi via WhatsApp
                    </a>
                </div>

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

    <!-- FAQ Accordion JS Script -->
    <script>
        document.addEventListener("DOMContentLoaded", function() {
            const faqToggles = document.querySelectorAll('.faq-toggle');
            
            faqToggles.forEach(toggle => {
                toggle.addEventListener('click', function() {
                    const card = this.parentElement;
                    const content = card.querySelector('.faq-content');
                    const icon = this.querySelector('.faq-icon');
                    
                    // Close other FAQs
                    document.querySelectorAll('.faq-content').forEach(otherContent => {
                        if (otherContent !== content) {
                            otherContent.style.maxHeight = '0px';
                            otherContent.parentElement.querySelector('.faq-icon').textContent = '+';
                            otherContent.parentElement.classList.remove('border-jazz-gold/25');
                        }
                    });

                    // Toggle current FAQ
                    if (content.style.maxHeight === '0px' || !content.style.maxHeight) {
                        content.style.maxHeight = content.scrollHeight + 'px';
                        icon.textContent = '−';
                        card.classList.add('border-jazz-gold/25');
                    } else {
                        content.style.maxHeight = '0px';
                        icon.textContent = '+';
                        card.classList.remove('border-jazz-gold/25');
                    }
                });
            });
        });
    </script>
</body>
</html>
