<?php
require_once 'config/db.php';
// Function to determine if menu is active
function isMenuActive($currentPage, $targetPage, $section = null)
{
    if ($section) {
        // Logic for dropdown items - matches layanan.php section parameter
        return $currentPage == 'layanan.php' && isset($_GET['section']) && $_GET['section'] == $section;
    }
    return $currentPage == $targetPage;
}
// Get current page for navigation highlighting
$current_page = basename($_SERVER['PHP_SELF']);
?>
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo isset($page_title) ? $page_title . ' - PT Sarana Sentra Teknologi Utama' : 'PT Sarana Sentra Teknologi Utama - Solusi IT Profesional'; ?></title>
    <meta name="description" content="PT Sarana Sentra Teknologi Utama - Solusi IT profesional untuk kebutuhan CCTV, jaringan, dan pemeliharaan sistem teknologi bisnis Anda.">
    <!-- Favicon -->
    <link rel="icon" type="image/png" href="assets/img/logo_pt.png">
    <link rel="shortcut icon" type="image/png" href="assets/img/logo_pt.png">
    <link rel="apple-touch-icon" href="assets/img/logo_pt.png">
    <!-- CDN Stylesheets -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&family=Playfair+Display:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/swiper@10/swiper-bundle.min.css">
    <link href="https://unpkg.com/aos@2.3.1/dist/aos.css" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/style.css">
    <style>
        /* Color Variables */
        :root {
            --primary-color: #224647ff;
            --primary-dark: #1a3637;
            --primary-light: #2a5456;
            --accent-color: #5d8a8c;
            --secondary-color: #f8fafa;
            --text-dark: #2c3e50;
            --text-light: #5a6c7d;
            --border-color: #e1e8eb;
            --success-color: #4a7c7e;
            --gradient-primary: linear-gradient(135deg, #224647ff 0%, #1a3637 100%);
            --gradient-soft: linear-gradient(135deg, #2a5456 0%, #224647ff 100%);
            --shadow-soft: 0 10px 40px rgba(34, 70, 71, 0.15);
            --shadow-medium: 0 15px 50px rgba(34, 70, 71, 0.2);
            --shadow-light: 0 2px 10px rgba(34, 70, 71, 0.1);
        }

        /* Enhanced Navbar Brand Styling with Logo */
        .navbar-brand {
            padding: 0;
            margin-right: 2rem;
            text-decoration: none;
            display: flex;
            align-items: center;
            /* gap: 0.35rem; */
        }

        .brand-logo {
            height: 60px;
            width: auto;
            border: none !important;
            outline: none !important;
        }

        .brand-container {
            display: flex;
            flex-direction: column;
            align-items: flex-start;
            line-height: 1;
        }

        .brand-main {
            font-family: "Inter", sans-serif;
            font-size: 1.2rem;
            font-weight: 700;
            color: #d32f2f;
            letter-spacing: -0.5px;
            position: relative;
            margin: 0;
            white-space: nowrap;
        }

        .brand-sub {
            font-family: "Inter", sans-serif;
            font-size: 0.7rem;
            font-weight: 500;
            color: #666666;
            text-transform: none;
            letter-spacing: 0.5px;
            margin-top: 2px;
            padding-left: 2px;
            margin: 0;
            white-space: nowrap;
        }

        .navbar-brand:hover .brand-main {
            color: #b71c1c;
        }

        .navbar-brand:hover .brand-sub {
            color: #444444;
        }

        /* Enhanced Navbar Styling */
        .navbar {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            border-bottom: 1px solid var(--border-color);
            transition: all 0.3s ease;
            box-shadow: var(--shadow-light);
            z-index: 1055;
            padding: 1rem 0;
        }

        .navbar-scrolled {
            background: rgba(255, 255, 255, 0.98);
            box-shadow: var(--shadow-medium);
        }

        .navbar .container-fluid {
            overflow: visible;
        }

        .navbar-toggler {
            border: none !important;
            padding: 0.25rem 0.5rem;
            background: var(--secondary-color);
            border-radius: 8px;
            transition: all 0.3s ease;
            z-index: 1051;
            position: relative;
            outline: none !important;
            box-shadow: none !important;
            cursor: pointer;
        }

        .navbar-toggler:hover {
            background: var(--primary-light);
        }

        .navbar-toggler:focus,
        .navbar-toggler:active {
            box-shadow: 0 0 0 0.2rem rgba(34, 70, 71, 0.25) !important;
            outline: none !important;
        }

        .navbar-toggler-icon {
            background-image: url("data:image/svg+xml,%3csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 30 30'%3e%3cpath stroke='rgba%2834, 70, 71, 1%29' stroke-linecap='round' stroke-miterlimit='10' stroke-width='2' d='M4 7h22M4 15h22M4 23h22'/%3e%3c/svg%3e");
            width: 1.5em;
            height: 1.5em;
            background-repeat: no-repeat;
            background-position: center;
            background-size: 100%;
        }

        /* Navigation Links */
        .navbar-nav {
            align-items: center;
        }

        .nav-link {
            font-weight: 500;
            color: var(--text-dark) !important;
            padding: 0.75rem 1rem !important;
            position: relative;
            transition: all 0.3s ease;
            border-radius: 8px;
            margin: 0 0.25rem;
        }

        .nav-link::before {
            content: "";
            position: absolute;
            bottom: 0;
            left: 50%;
            transform: translateX(-50%);
            width: 0;
            height: 2px;
            background: var(--gradient-soft);
            transition: width 0.3s ease;
            border-radius: 1px;
        }

        .nav-link:hover {
            color: var(--primary-color) !important;
            background: var(--secondary-color);
        }

        .nav-link:hover::before,
        .nav-link.active::before {
            width: 70%;
        }

        .navbar-nav .nav-link.active {
            color: var(--primary-color) !important;
            font-weight: 600;
            background: var(--secondary-color);
        }

        /* Dropdown Styling */
        .navbar-nav .nav-item.dropdown {
            position: relative;
        }

        .dropdown-toggle {
            display: flex;
            align-items: center;
            gap: 0.4rem;
            position: relative;
        }

        .dropdown-toggle::after {
            display: inline-block;
            margin-left: 0;
            vertical-align: 0;
            content: "";
            border-top: 0.25em solid;
            border-right: 0.25em solid transparent;
            border-bottom: 0;
            border-left: 0.25em solid transparent;
            transition: transform 0.3s ease;
            flex-shrink: 0;
            opacity: 0.7;
        }

        .dropdown-toggle[aria-expanded="true"]::after {
            transform: rotate(180deg);
        }

        /* Main Dropdown Menu */
        .dropdown-menu {
            display: none;
            position: absolute;
            top: 100%;
            left: 0;
            z-index: 1060;
            background-color: rgba(255, 255, 255, 0.98);
            border: 1px solid rgba(34, 70, 71, 0.1);
            border-radius: 12px;
            box-shadow: var(--shadow-medium);
            padding: 1rem 0;
            margin-top: 0.5rem;
            min-width: 280px;
            backdrop-filter: blur(15px);
            opacity: 0;
            visibility: hidden;
            transition: all 0.3s cubic-bezier(0.175, 0.885, 0.32, 1.275);
        }

        .dropdown-menu.show {
            display: block;
            opacity: 1;
            visibility: visible;
        }

        /* Dropdown Headers */
        .dropdown-header {
            color: var(--primary-color);
            font-weight: 600;
            font-size: 0.8rem;
            text-transform: uppercase;
            letter-spacing: 1px;
            padding: 0.5rem 1.5rem;
            margin-bottom: 0.5rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .dropdown-divider {
            margin: 0.75rem 1rem;
            border-top: 1px solid var(--border-color);
        }

        /* Dropdown Items */
        .dropdown-item {
            color: var(--text-dark);
            padding: 0.75rem 1.5rem;
            font-weight: 500;
            transition: all 0.3s ease;
            background-color: transparent;
            border-radius: 8px;
            margin: 0.25rem 1rem;
            display: flex;
            align-items: center;
            position: relative;
            overflow: hidden;
            text-decoration: none;
            border: none;
            font-size: 0.95rem;
        }

        .dropdown-item::before {
            content: "";
            position: absolute;
            left: 0;
            top: 0;
            width: 0;
            height: 100%;
            background: var(--gradient-soft);
            transition: width 0.3s ease;
            z-index: -1;
            border-radius: 8px;
        }

        .dropdown-item i {
            width: 20px;
            margin-right: 0.75rem;
            color: var(--primary-color);
            transition: transform 0.3s ease;
            opacity: 0.8;
            font-size: 0.9rem;
        }

        .dropdown-item:hover,
        .dropdown-item:focus {
            color: white;
            background-color: transparent;
        }

        .dropdown-item:hover::before,
        .dropdown-item:focus::before {
            width: 90%;
        }

        .dropdown-item:hover i,
        .dropdown-item:focus i {
            transform: scale(1.1);
            opacity: 1;
            color: white;
        }

        .dropdown-item.active {
            color: white !important;
            font-weight: 600 !important;
            background: var(--gradient-primary) !important;
            width: auto;
            margin: 0.25rem 1rem;
        }

        .dropdown-item.active i {
            color: white;
        }

        /* Desktop hover behavior */
        @media (min-width: 992px) {
            .navbar-nav .nav-item.dropdown:hover>.dropdown-menu {
                display: block;
                opacity: 1;
                visibility: visible;
            }

            .navbar-nav .nav-item.dropdown:hover>.dropdown-toggle::after {
                transform: rotate(180deg);
            }
        }

        /* Contact Button */
        .btn-contact {
            background: var(--gradient-primary);
            color: white;
            border: none;
            border-radius: 50px;
            padding: 0.75rem 1.5rem;
            font-weight: 600;
            font-size: 0.875rem;
            transition: all 0.3s cubic-bezier(0.175, 0.885, 0.32, 1.275);
            box-shadow: 0 4px 15px rgba(34, 70, 71, 0.3);
            position: relative;
            overflow: hidden;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            margin-left: 1rem;
        }

        .btn-contact::before {
            content: "";
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.2), transparent);
            transition: left 0.5s ease;
        }

        .btn-contact:hover::before {
            left: 100%;
        }

        .btn-contact:hover {
            background: var(--gradient-soft);
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(34, 70, 71, 0.4);
            color: white;
            text-decoration: none;
        }

        .btn-contact i {
            transition: transform 0.3s ease;
        }

        .btn-contact:hover i {
            transform: translateX(3px);
        }

        /* Mobile Responsive */
        @media (max-width: 991.98px) {
            .navbar-nav {
                margin-top: 1rem;
                padding-top: 1rem;
                border-top: 1px solid var(--border-color);
                text-align: center;
                width: 100%;
            }

            .nav-link {
                margin: 0.25rem 0;
                text-align: center;
                justify-content: center;
            }

            .dropdown-menu {
                position: static !important;
                transform: none !important;
                box-shadow: none;
                border: 1px solid var(--border-color);
                border-radius: 8px;
                margin: 0.5rem 0;
                background: var(--secondary-color);
                backdrop-filter: none;
                opacity: 1;
                visibility: visible;
                display: none;
                min-width: auto;
                width: 100%;
                margin-left: auto;
                margin-right: auto;
            }

            .dropdown-menu.show {
                display: block !important;
            }

            .dropdown-item {
                margin: 0.25rem 0.5rem;
                padding: 0.75rem 1rem;
                text-align: center;
                justify-content: center;
                border-radius: 6px;
                width: auto;
                /* Mengatur lebar otomatis */
                display: flex;
                align-items: center;
                justify-content: center;
                /* Menyelaraskan item ke tengah */
            }

            .dropdown-item:hover,
            .dropdown-item:focus {
                color: var(--primary-color);
                background-color: rgba(34, 70, 71, 0.1);
            }

            .dropdown-item:hover::before,
            .dropdown-item:focus::before {
                width: 0;
            }

            .dropdown-item.active {
                width: auto;
                /* Mengatur lebar otomatis */
                margin: 0.25rem 0.5rem;
                /* Margin yang sama dengan item lain */
                padding: 0.75rem 1rem;
                /* Padding yang sama dengan item lain */
                display: flex;
                align-items: center;
                justify-content: center;
                /* Menyelaraskan item aktif ke tengah */
                background: var(--gradient-primary) !important;
                border-radius: 6px;
                /* Border radius yang sama */
            }

            .dropdown-item.active i {
                margin-right: 0.5rem;
                /* Jarak antara icon dan text */
            }

            .dropdown-header {
                text-align: center;
                justify-content: center;
                padding: 0.5rem 1rem;
                display: flex;
                align-items: center;
                justify-content: center;
            }

            .btn-contact {
                margin-top: 1rem;
                margin-left: 0;
                width: auto;
                min-width: 200px;
                justify-content: center;
            }

            /* Mobile brand adjustments */
            .brand-logo {
                height: 40px;
            }

            .brand-main {
                font-size: 0.8rem;
            }

            .brand-sub {
                font-size: 0.55rem;
            }
        }

        @media (max-width: 576px) {
            .brand-logo {
                height: 35px;
            }

            .brand-main {
                font-size: 0.9rem;
            }

            .brand-sub {
                font-size: 0.6rem;
            }

            .navbar {
                padding: 0.75rem 0;
            }

            .dropdown-item {
                padding: 0.6rem 0.8rem;
                /* Mengurangi padding untuk layar lebih kecil */
                font-size: 0.9rem;
                /* Mengurangi ukuran font */
            }

            .dropdown-item.active {
                padding: 0.6rem 0.8rem;
                /* Mengurangi padding untuk item aktif */
            }

            .dropdown-item i {
                font-size: 0.8rem;
                /* Mengurangi ukuran icon */
                margin-right: 0.5rem;
                /* Mengurangi jarak */
            }
        }

        /* Smooth scroll behavior */
        html {
            scroll-behavior: smooth;
        }

        /* Fix navbar collapse transition */
        .navbar-collapse {
            transition: height 0.35s ease;
        }

        .navbar-collapse.collapsing {
            height: 0;
            overflow: hidden;
            transition: height 0.35s ease;
        }

        .navbar-collapse.show {
            height: auto;
        }
    </style>
</head>

<body>
    <!-- Main Header -->
    <header class="main-header">
        <nav class="navbar navbar-expand-lg fixed-top">
            <div class="container-fluid px-3">
                <!-- Brand Logo -->
                <a class="navbar-brand" href="index.php">
                    <img src="assets/img/logo_pt.png" alt="PT Sarana Sentra Teknologi Utama Logo" class="brand-logo">
                    <div class="brand-container">
                        <h1 class="brand-main">PT Sarana Sentra Teknologi Utama</h1>
                        <p class="brand-sub">General IT Solution</p>
                    </div>
                </a>
                <!-- Mobile Toggle Button -->
                <button class="navbar-toggler" type="button" id="mobile-menu-button" aria-expanded="false" aria-label="Toggle navigation">
                    <span class="navbar-toggler-icon"></span>
                </button>
                <!-- Navigation Menu -->
                <div class="collapse navbar-collapse" id="navbarNav">
                    <ul class="navbar-nav ms-auto align-items-center">
                        <li class="nav-item">
                            <a class="nav-link <?php echo isMenuActive($current_page, 'index.php') ? 'active' : ''; ?>" href="index.php">Beranda</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link <?php echo isMenuActive($current_page, 'tentang.php') ? 'active' : ''; ?>" href="tentang.php">Tentang kami</a>
                        </li>
                        <!-- Service Dropdown -->
                        <li class="nav-item dropdown">
                            <a class="nav-link dropdown-toggle <?php echo isMenuActive($current_page, 'layanan.php') ? 'active' : ''; ?>"
                                href="#"
                                id="navbarDropdownServices"
                                role="button"
                                data-bs-toggle="dropdown"
                                aria-expanded="false">
                                Layanan
                            </a>
                            <ul class="dropdown-menu" aria-labelledby="navbarDropdownServices">
                                <!-- Pengadaan Section -->
                                <li>
                                    <h6 class="dropdown-header">
                                        <i class="fas fa-shopping-cart"></i> Pengadaan
                                    </h6>
                                </li>
                                <li>
                                    <a class="dropdown-item <?php echo isMenuActive($current_page, 'layanan.php', 'bisnis') ? 'active' : ''; ?>"
                                        href="layanan.php?section=bisnis">
                                        <i class="fas fa-building"></i>
                                        Pengadaan Berbasis Bisnis
                                    </a>
                                </li>
                                <li>
                                    <a class="dropdown-item <?php echo isMenuActive($current_page, 'layanan.php', 'retail') ? 'active' : ''; ?>"
                                        href="layanan.php?section=retail">
                                        <i class="fas fa-store"></i>
                                        Pengadaan Berbasis Retail
                                    </a>
                                </li>
                                <li>
                                    <hr class="dropdown-divider">
                                </li>
                                <!-- Perawatan Section -->
                                <li>
                                    <h6 class="dropdown-header">
                                        <i class="fas fa-tools"></i> Perawatan
                                    </h6>
                                </li>
                                <li>
                                    <a class="dropdown-item <?php echo isMenuActive($current_page, 'layanan.php', 'kontrak') ? 'active' : ''; ?>"
                                        href="layanan.php?section=kontrak">
                                        <i class="fas fa-handshake"></i>
                                        Perawatan Berbasis Kontrak
                                    </a>
                                </li>
                                <li>
                                    <a class="dropdown-item <?php echo isMenuActive($current_page, 'layanan.php', 'layanan') ? 'active' : ''; ?>"
                                        href="layanan.php?section=layanan">
                                        <i class="fas fa-wrench"></i>
                                        Perawatan Berbasis Layanan
                                    </a>
                                </li>
                            </ul>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link <?php echo isMenuActive($current_page, 'produk.php') ? 'active' : ''; ?>" href="produk.php">Produk</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link <?php echo isMenuActive($current_page, 'artikel.php') ? 'active' : ''; ?>" href="artikel.php">Artikel</a>
                        </li>
                        <!-- Contact Button -->
                        <li class="nav-item">
                            <a class="btn-contact" href="kontak.php">
                                Hubungi Kami
                                <i class="fas fa-arrow-right"></i>
                            </a>
                        </li>
                    </ul>
                </div>
            </div>
        </nav>
    </header>
    <!-- Spacer for fixed navbar -->
    <div style="padding-top: 80px;"></div>
    <!-- JavaScript -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://unpkg.com/aos@2.3.1/dist/aos.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/swiper@10/swiper-bundle.min.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Initialize AOS
            if (typeof AOS !== 'undefined') {
                AOS.init({
                    duration: 800,
                    easing: 'ease-in-out',
                    once: true,
                    offset: 50
                });
            }
            // Variables
            const navbarToggler = document.querySelector('#mobile-menu-button');
            const navbarCollapse = document.querySelector('#navbarNav');
            const mainDropdownToggle = document.querySelector('#navbarDropdownServices');
            const mainDropdownMenu = document.querySelector('.dropdown-menu');
            const navbar = document.querySelector('.navbar');
            // Mobile menu toggle
            if (navbarToggler) {
                navbarToggler.addEventListener('click', function() {
                    const isExpanded = this.getAttribute('aria-expanded') === 'true';
                    if (isExpanded) {
                        navbarCollapse.classList.remove('show');
                        this.setAttribute('aria-expanded', 'false');
                    } else {
                        navbarCollapse.classList.add('show');
                        this.setAttribute('aria-expanded', 'true');
                    }
                });
            }
            // Mobile dropdown toggle
            if (mainDropdownToggle && window.innerWidth <= 991) {
                mainDropdownToggle.addEventListener('click', function(e) {
                    e.preventDefault();
                    e.stopPropagation();
                    const isExpanded = this.getAttribute('aria-expanded') === 'true';
                    if (isExpanded) {
                        mainDropdownMenu.classList.remove('show');
                        this.setAttribute('aria-expanded', 'false');
                    } else {
                        mainDropdownMenu.classList.add('show');
                        this.setAttribute('aria-expanded', 'true');
                    }
                });
            }
            // Close dropdowns when clicking outside
            document.addEventListener('click', function(event) {
                const isClickInsideNavbar = navbar && navbar.contains(event.target);
                if (!isClickInsideNavbar) {
                    if (mainDropdownMenu) {
                        mainDropdownMenu.classList.remove('show');
                        mainDropdownToggle.setAttribute('aria-expanded', 'false');
                    }
                }
            });
            // Close mobile menu when clicking on a link
            const navLinks = document.querySelectorAll('.nav-link:not(.dropdown-toggle)');
            navLinks.forEach(link => {
                link.addEventListener('click', function() {
                    if (window.innerWidth <= 991 && navbarCollapse.classList.contains('show')) {
                        navbarCollapse.classList.remove('show');
                        navbarToggler.setAttribute('aria-expanded', 'false');
                    }
                });
            });
            // Close mobile menu when clicking on dropdown items
            const dropdownItems = document.querySelectorAll('.dropdown-item');
            dropdownItems.forEach(item => {
                item.addEventListener('click', function() {
                    if (window.innerWidth <= 991 && navbarCollapse.classList.contains('show')) {
                        navbarCollapse.classList.remove('show');
                        navbarToggler.setAttribute('aria-expanded', 'false');
                    }
                });
            });
            // Navbar scroll effect
            window.addEventListener('scroll', function() {
                if (window.scrollY > 50) {
                    navbar.classList.add('navbar-scrolled');
                } else {
                    navbar.classList.remove('navbar-scrolled');
                }
            });
            // Handle window resize
            let resizeTimeout;
            window.addEventListener('resize', function() {
                clearTimeout(resizeTimeout);
                resizeTimeout = setTimeout(() => {
                    // Close dropdowns on resize
                    if (mainDropdownMenu) {
                        mainDropdownMenu.classList.remove('show');
                        mainDropdownToggle.setAttribute('aria-expanded', 'false');
                    }
                    // Close mobile menu on resize to desktop
                    if (window.innerWidth > 991 && navbarCollapse.classList.contains('show')) {
                        navbarCollapse.classList.remove('show');
                        navbarToggler.setAttribute('aria-expanded', 'false');
                    }
                }, 250);
            });
        });
    </script>
</body>

</html>