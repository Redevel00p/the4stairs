<?php
/**
 * THE 4 STAIRS MUSIC HALL - DYNAMIC NAVIGATION BAR
 * -----------------------------------------------
 * Menampilkan menu navigasi secara dinamis berdasarkan status session (Admin, User, Guest).
 * Mendukung path prefix untuk file yang berada di subfolder (seperti berita/).
 */

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Prefix path untuk navigasi halaman (misal '../' jika dipanggil dari subfolder)
$prefix = isset($nav_prefix) ? $nav_prefix : '';

// Ambil nama file saat ini untuk menentukan link aktif
$current_page = pathinfo(basename($_SERVER['PHP_SELF']), PATHINFO_FILENAME);

// Fungsi pembantu mengecek halaman aktif
function is_active($page, $current) {
    return ($page === $current) ? 'text-jazz-gold' : 'text-gray-400 hover:text-jazz-gold';
}

// Proses nama user untuk menu navigasi
$menu_auth_label = "LOGIN";
$menu_auth_link = $prefix . "login";
$menu_auth_active_class = is_active("login", $current_page);

if (isset($_SESSION['admin_logged_in']) && $_SESSION['admin_logged_in'] === true) {
    $menu_auth_label = "ADMIN PANEL";
    $menu_auth_link = $prefix . "admin_dashboard";
    $menu_auth_active_class = is_active("admin_dashboard", $current_page);
} elseif (isset($_SESSION['user_logged_in']) && $_SESSION['user_logged_in'] === true) {
    $raw_name = $_SESSION['user_name'];
    if (strlen($raw_name) > 12) {
        $menu_auth_label = substr($raw_name, 0, 10) . "...";
    } else {
        $menu_auth_label = $raw_name;
    }
    $menu_auth_link = $prefix . "profile";
    $menu_auth_active_class = is_active("profile", $current_page);
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

/* ===== DESKTOP MENU - HIDDEN BY DEFAULT (mobile first) ===== */
.nav-links-desktop {
    display: none !important;
    list-style: none;
    margin: 0;
    padding: 0;
    gap: 20px;
    align-items: center; /* <-- FIX: biar rata tengah vertikal */
}
.nav-links-desktop li { 
    display: inline-block;
    line-height: 1; /* <-- FIX: hapus extra spacing */
}
.nav-links-desktop a {
    text-decoration: none;
    font-size: 11px;
    font-weight: 600;
    text-transform: uppercase;
    letter-spacing: 0.05em;
    transition: color 0.3s ease;
    display: inline-flex; /* <-- FIX: biar icon + text rata */
    align-items: center;
    gap: 6px;
    line-height: 1;
}

/* ===== DROPDOWN DESKTOP ===== */
.dropdown-desktop { position: relative; }
.dropdown-desktop button {
    background: none;
    border: none;
    cursor: pointer;
    font-size: 11px;
    font-weight: 600;
    text-transform: uppercase;
    letter-spacing: 0.05em;
    display: inline-flex;
    align-items: center;
    gap: 6px;
    transition: color 0.3s ease;
    line-height: 1;
    padding: 0;
}
.dropdown-menu-desktop {
    position: absolute;
    left: 0;
    top: 100%;
    margin-top: 8px;
    width: 192px;
    background: #1a1a24;
    border: 1px solid rgba(212, 175, 55, 0.15);
    border-radius: 12px;
    padding: 8px 0;
    box-shadow: 0 10px 40px rgba(0,0,0,0.5);
    opacity: 0;
    pointer-events: none;
    transform: translateY(-10px);
    transition: all 0.3s ease;
    z-index: 50;
    list-style: none;
}
.dropdown-desktop:hover .dropdown-menu-desktop {
    opacity: 1;
    pointer-events: auto;
    transform: translateY(0);
}
.dropdown-menu-desktop a {
    display: block;
    padding: 10px 16px;
    font-size: 11px;
    font-weight: 600;
    text-decoration: none;
    transition: all 0.2s ease;
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
            </a>
        </div>

        <!-- Desktop Menu (HANYA muncul di layar >= 1024px) -->
        <ul class="nav-links-desktop">
            <li>
                <a href="<?php echo $prefix; ?>profil" class="<?php echo is_active('profil', $current_page); ?>">PROFIL</a>
            </li>
            <li>
                <a href="<?php echo $prefix; ?>event" class="<?php echo is_active('event', $current_page); ?>">EVENT</a>
            </li>
            <li><a href="<?php echo $prefix; ?>pesan" class="<?php echo is_active('pesan', $current_page); ?>">TIKET</a></li>
            <li><a href="<?php echo $prefix; ?>komposisi" class="<?php echo is_active('komposisi', $current_page); ?>">KOMPOSISI</a></li>
            <li><a href="<?php echo $prefix; ?>berita" class="<?php echo is_active('berita', $current_page); ?>">BERITA</a></li>
            <li>
                <a href="<?php echo $menu_auth_link; ?>" class="<?php echo $menu_auth_active_class; ?>" style="font-weight:bold;">
                    <?php if (isset($_SESSION['user_logged_in']) && $_SESSION['user_logged_in'] === true): ?>
                        <svg class="w-3.5 h-3.5 fill-current" viewBox="0 0 20 20" style="display:inline-block;vertical-align:middle;">
                            <path fill-rule="evenodd" d="M10 9a3 3 0 100-6 3 3 0 000 6zm-7 9a7 7 0 1114 0H3z" clip-rule="evenodd" />
                        </svg>
                    <?php endif; ?>
                    <?php echo htmlspecialchars($menu_auth_label); ?>
                </a>
            </li>
        </ul>

        <!-- Hamburger Button (HANYA muncul di layar < 1024px) -->
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

    <a href="<?php echo $prefix; ?>profil" class="<?php echo is_active('profil', $current_page); ?>">PROFIL</a>
    <a href="<?php echo $prefix; ?>event" class="<?php echo is_active('event', $current_page); ?>">EVENT</a>
    <a href="<?php echo $prefix; ?>pesan" class="<?php echo is_active('pesan', $current_page); ?>">TIKET</a>
    <a href="<?php echo $prefix; ?>komposisi" class="<?php echo is_active('komposisi', $current_page); ?>">KOMPOSISI</a>
    <a href="<?php echo $prefix; ?>berita" class="<?php echo is_active('berita', $current_page); ?>">BERITA</a>
    <a href="<?php echo $menu_auth_link; ?>" class="<?php echo $menu_auth_active_class; ?>" style="display:flex;align-items:center;justify-content:center;gap:8px;font-weight:bold;">
        <?php if (isset($_SESSION['user_logged_in']) && $_SESSION['user_logged_in'] === true): ?>
            <svg class="w-5 h-5 fill-current" viewBox="0 0 20 20" style="display:inline-block;">
                <path fill-rule="evenodd" d="M10 9a3 3 0 100-6 3 3 0 000 6zm-7 9a7 7 0 1114 0H3z" clip-rule="evenodd" />
            </svg>
        <?php endif; ?>
        <?php echo htmlspecialchars($menu_auth_label); ?>
    </a>
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