<?php
/**
 * THE 4 STAIRS MUSIC HALL - ADMIN NAVIGATION BAR
 * -----------------------------------------------
 * Navigation bar khusus untuk halaman admin, terinspirasi penuh dari navbar.php
 */

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Prefix path untuk navigasi halaman jika dipanggil dari subfolder
$prefix = isset($nav_prefix) ? $nav_prefix : '';

// Ambil nama file saat ini untuk menentukan link aktif
$current_page = pathinfo(basename($_SERVER['PHP_SELF']), PATHINFO_FILENAME);

// Fungsi pembantu mengecek halaman aktif
function is_active($page, $current) {
    return ($page === $current) ? 'text-jazz-gold' : 'text-gray-400 hover:text-jazz-gold';
}
?>

<!-- NAVBAR RESPONSIVE CSS -->
<style>
/* ===== BASE NAVBAR ===== */
.glass-nav {
    background: rgba(10, 10, 15, 0.6);
    backdrop-filter: blur(12px);
    -webkit-backdrop-filter: blur(12px);
    border-bottom: 1px solid rgba(212, 175, 55, 0.08);
}

/* ===== LOGO ===== */
.nav-logo {
    color: #d4af37;
    text-decoration: none;
}
.nav-logo span { color: #ffffff; }

/* ===== DESKTOP MENU ===== */
.nav-links-desktop {
    display: none !important;
    list-style: none;
    margin: 0;
    padding: 0;
    gap: 20px;
    align-items: center;
}
.nav-links-desktop li { 
    display: inline-block;
    line-height: 1;
}
.nav-links-desktop a {
    text-decoration: none;
    font-size: 11px;
    font-weight: 600;
    text-transform: uppercase;
    letter-spacing: 0.05em;
    transition: color 0.3s ease;
    display: inline-flex;
    align-items: center;
    gap: 6px;
    line-height: 1;
}

/* ===== HAMBURGER BUTTON ===== */
.hamburger-btn {
    display: flex;
    background: none;
    border: none;
    cursor: pointer;
    color: #9ca3af;
    padding: 8px;
    transition: color 0.3s ease;
    align-items: center;
}
.hamburger-btn:hover { color: #ffffff; }

/* ===== MOBILE OVERLAY ===== */
.mobile-overlay {
    display: none;
    position: fixed;
    inset: 0;
    z-index: 50;
    background: rgba(5, 5, 10, 0.95);
    backdrop-filter: blur(12px);
    -webkit-backdrop-filter: blur(12px);
    flex-direction: column;
    align-items: center;
    justify-content: center;
    gap: 12px;
    opacity: 0;
    pointer-events: none;
    transition: opacity 0.3s ease;
}
.mobile-overlay.active {
    display: flex;
    opacity: 1;
    pointer-events: auto;
}
.mobile-overlay a {
    text-decoration: none;
    font-size: 18px;
    font-weight: 500;
    letter-spacing: 0.05em;
    padding: 8px 0;
    transition: color 0.3s ease;
}
.mobile-close-btn {
    position: absolute;
    top: 20px;
    right: 20px;
    background: none;
    border: none;
    color: #9ca3af;
    font-size: 36px;
    font-weight: bold;
    cursor: pointer;
    padding: 8px;
    line-height: 1;
    transition: color 0.3s ease;
    display: flex;
    align-items: center;
}
.mobile-close-btn:hover { color: #ffffff; }

/* ===== COLORS ===== */
.text-gold { color: #d4af37; }
.text-gray { color: #9ca3af; }
.text-gray:hover { color: #d4af37; }
.text-white { color: #ffffff; }

/* ===== RESPONSIVE: DESKTOP >= 1024px ===== */
@media (min-width: 1024px) {
    .nav-links-desktop { display: flex !important; }
    .hamburger-btn { display: none !important; }
    .mobile-overlay { display: none !important; }
}
</style>

<!-- Premium Navigation Bar -->
<nav class="glass-nav fixed top-0 left-0 w-full z-40 transition-all duration-300">
    <div class="max-w-6xl mx-auto px-4 sm:px-6 h-16 sm:h-20 flex items-center justify-between w-full">

        <!-- Logo -->
        <div class="flex items-center gap-2 shrink-0">
            <a href="<?php echo $prefix; ?>index" class="flex items-center gap-1.5 sm:gap-2 font-heading text-base sm:text-lg lg:text-xl font-bold tracking-wider nav-logo">
                <img src="<?php echo $prefix; ?>4stairswhite.png" alt="Logo" class="w-6 h-6 sm:w-7 sm:h-7 lg:w-8 lg:h-8 object-contain">
                <span class="whitespace-nowrap">THE 4 <span class="text-white">STAIRS</span></span>
                <span class="text-[10px] tracking-widest font-body font-bold uppercase bg-retro-red/20 text-retro-red border border-retro-red/30 px-2 py-0.5 rounded ml-2">PANITIA</span>
            </a>
        </div>

        <!-- Desktop Menu (Dashboard, Web Utama, Logout) -->
        <ul class="nav-links-desktop">
            <li><a href="<?php echo $prefix; ?>index" class="text-gray hover:text-gold">WEB UTAMA</a></li>
            <li><a href="<?php echo $prefix; ?>admin_dashboard" class="<?php echo is_active('admin_dashboard', $current_page); ?>">DASHBOARD</a></li>
            <li><a href="<?php echo $prefix; ?>logout" class="text-red-500 hover:text-red-400 font-bold" style="color: #ef4444;">LOGOUT</a></li>
        </ul>

        <!-- Hamburger Button -->
        <button id="mobile-menu-btn" class="hamburger-btn" aria-label="Buka Menu">
            <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16m-7 6h7" />
            </svg>
        </button>
    </div>
</nav>

<!-- Mobile Navigation Overlay Menu -->
<div id="mobile-menu" class="mobile-overlay">
    <button id="mobile-menu-close" class="mobile-close-btn">&times;</button>

    <a href="<?php echo $prefix; ?>index" class="text-gray hover:text-gold">WEB UTAMA</a>
    <a href="<?php echo $prefix; ?>admin_dashboard" class="<?php echo is_active('admin_dashboard', $current_page); ?>">DASHBOARD</a>
    <a href="<?php echo $prefix; ?>logout" class="text-red-500 font-bold" style="color: #ef4444;">LOGOUT</a>
</div>

<!-- Dynamic Navigation Scripts -->
<script>
    document.addEventListener("DOMContentLoaded", function() {
        const mobileMenuBtn = document.getElementById('mobile-menu-btn');
        const mobileMenu = document.getElementById('mobile-menu');
        const mobileMenuClose = document.getElementById('mobile-menu-close');

        if (mobileMenuBtn && mobileMenu && mobileMenuClose) {
            const openMenu = () => {
                mobileMenu.classList.add('active');
                document.body.style.overflow = 'hidden';
            };
            const closeMenu = () => {
                mobileMenu.classList.remove('active');
                document.body.style.overflow = '';
            };

            mobileMenuBtn.addEventListener('click', openMenu);
            mobileMenuClose.addEventListener('click', closeMenu);

            mobileMenu.querySelectorAll('a').forEach(link => {
                link.addEventListener('click', closeMenu);
            });
        }

        // Sticky Header scroll effect
        const nav = document.querySelector('.glass-nav');
        if (nav) {
            window.addEventListener('scroll', () => {
                if (window.scrollY > 20) {
                    nav.style.background = 'rgba(5, 5, 10, 0.95)';
                    nav.style.boxShadow = '0 4px 30px rgba(0,0,0,0.5)';
                } else {
                    nav.style.background = 'rgba(10, 10, 15, 0.6)';
                    nav.style.boxShadow = 'none';
                }
            });
        }
    });
</script>
