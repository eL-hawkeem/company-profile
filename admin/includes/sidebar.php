<?php

/**
 * Dashboard Sidebar Component
 * PT. Sarana Sentra Teknologi Utama - Admin Dashboard
 *
 * @version 3.0
 * @author eL
 */

// Get current page active menu
$current_page = basename($_SERVER['PHP_SELF'], '.php');
$current_dir = basename(dirname($_SERVER['PHP_SELF']));
$script_name = $_SERVER['SCRIPT_NAME'];

function isActiveMenu($page, $module = '')
{
    global $current_page, $current_dir, $script_name;
    $current_url = $_SERVER['REQUEST_URI'];
    $current_path = parse_url($current_url, PHP_URL_PATH);

    if ($module) {
        if (strpos($current_path, "/modules/$module/") !== false) {
            if ($page === $current_page || ($page === 'index' && strpos($current_path, "/$module/index.php"))) {
                return 'active';
            }
            if ($page === 'stock' && strpos($current_path, "/$module/stock.php")) {
                return 'active';
            }
        }
        return '';
    }

    if ($page === 'index' && ($current_page === 'index' || strpos($script_name, '/admin/index.php') !== false)) {
        return 'active';
    }

    if ($current_page === $page && $current_dir === 'admin') {
        return 'active';
    }

    return '';
}

// Function check if submenu should be shown
function isActiveSubmenu($modules = [])
{
    global $current_dir;
    $current_url = $_SERVER['REQUEST_URI'];
    $current_path = parse_url($current_url, PHP_URL_PATH);

    foreach ($modules as $module) {
        if ($current_dir === $module || strpos($current_path, "/modules/$module/") !== false) {
            return 'show';
        }
    }
    return '';
}

// Function get base admin URL
function getAdminBaseUrl()
{
    $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https://' : 'http://';
    $host = $_SERVER['HTTP_HOST'];
    $script_name = $_SERVER['SCRIPT_NAME'];
    $request_uri = $_SERVER['REQUEST_URI'];

    if (strpos($script_name, '/admin/') !== false) {
        $admin_path = substr($script_name, 0, strpos($script_name, '/admin/') + 7);
    } elseif (strpos($request_uri, '/admin/') !== false) {
        $admin_path = substr($request_uri, 0, strpos($request_uri, '/admin/') + 7);
    } else {
        $admin_path = dirname($_SERVER['PHP_SELF']);
        if (!str_ends_with($admin_path, '/')) {
            $admin_path .= '/';
        }
    }
    return $protocol . $host . $admin_path;
}

$base_url = getAdminBaseUrl();
?>

<style>
    :root {
        --primary-color: #435ebe;
        --sidebar-bg: #ffffff;
        --sidebar-link-color: #555555;
        --sidebar-link-hover-bg: #f0f1f5;
        --sidebar-link-active-color: #ffffff;
        --sidebar-link-active-bg: var(--primary-color);
    }

    .sidebar {
        min-height: 100vh;
        background: var(--sidebar-bg);
        position: fixed;
        top: 0;
        left: 0;
        width: 260px;
        z-index: 1000;
        transition: all 0.3s ease;
        box-shadow: 0 0 15px rgba(0, 0, 0, 0.05);
        border-right: 1px solid #e9ecef;
    }

    /* Desktop collapsed state */
    .sidebar.collapsed {
        width: 60px;
    }

    .sidebar.collapsed .sidebar-header {
        padding: 1rem 0.5rem;
        text-align: center;
    }

    .sidebar.collapsed .sidebar-header .logo {
        font-size: 1rem;
    }

    .sidebar.collapsed .nav-link {
        padding: 0.9rem 0.5rem;
        text-align: center;
    }

    .sidebar.collapsed .nav-link span {
        display: none;
    }

    .sidebar.collapsed .nav-link i {
        margin-right: 0;
        font-size: 1.4rem;
        width: auto;
    }

    .sidebar.collapsed .nav-link .bi-chevron-down {
        display: none;
    }

    .sidebar.collapsed .submenu {
        display: none;
    }

    .sidebar.collapsed .sidebar-nav {
        padding: 0 0.5rem;
    }

    .sidebar-header {
        padding: 1.5rem;
        text-align: center;
        border-bottom: 1px solid #e9ecef;
        margin-bottom: 1rem;
    }

    .sidebar-header .logo {
        font-size: 1.5rem;
        font-weight: 700;
        color: var(--primary-color);
        text-decoration: none;
    }

    .sidebar-nav {
        padding: 0 1rem;
        height: calc(100vh - 120px);
        overflow-y: auto;
    }

    /* Style link navigasi */
    .nav-link {
        color: var(--sidebar-link-color) !important;
        padding: 0.9rem 1rem;
        border-radius: 8px;
        transition: all 0.2s ease-in-out;
        display: flex;
        align-items: center;
        text-decoration: none;
        font-weight: 500;
        margin-bottom: 0.25rem;
        cursor: pointer;
    }

    .nav-link:not(.active):not(.collapsed):hover {
        background: var(--sidebar-link-hover-bg) !important;
        color: var(--primary-color) !important;
    }

    /* Style link AKTIF (submenu/menu utama non-collapse) */
    .nav-link.active {
        background: var(--sidebar-link-active-bg) !important;
        color: var(--sidebar-link-active-color) !important;
        box-shadow: 0 4px 10px -2px rgba(67, 94, 190, 0.5);
    }

    .nav-link.active i {
        color: var(--sidebar-link-active-color) !important;
    }

    /* Style submenu aktif */
    .nav-link.collapsed[aria-expanded="true"] {
        background: var(--sidebar-link-hover-bg) !important;
        color: var(--primary-color) !important;
    }

    .nav-link i {
        width: 24px;
        text-align: center;
        margin-right: 12px;
        font-size: 1.2rem;
        color: #888888;
        transition: color 0.2s ease-in-out;
    }

    .nav-link:hover i {
        color: var(--primary-color);
    }

    /* Panah dropdown */
    .nav-link .bi-chevron-down {
        margin-left: auto;
        transition: transform 0.3s ease;
    }

    .nav-link[aria-expanded="true"] .bi-chevron-down {
        transform: rotate(180deg);
    }

    /* STYLING SUBMENU */
    .submenu {
        margin-left: 1rem;
        padding-left: 1rem;
        border-left: 2px solid #e9ecef;
    }

    .submenu .nav-link {
        padding: 0.7rem 1rem;
        font-size: 0.9rem;
    }

    .submenu .nav-link.active {
        background: var(--sidebar-link-active-bg) !important;
        color: var(--sidebar-link-active-color) !important;
    }

    .submenu .nav-link:not(.active):hover {
        background: var(--sidebar-link-hover-bg) !important;
    }

    /* Mobile responsive */
    @media (max-width: 991.98px) {
        .sidebar {
            transform: translateX(-100%);
            z-index: 1001;
            width: 280px;
        }

        .sidebar.show {
            transform: translateX(0);
        }

        .sidebar-overlay.show {
            display: block;
            opacity: 1;
        }
    }

    /* Custom Scrollbar */
    .sidebar-nav::-webkit-scrollbar {
        width: 5px;
    }

    .sidebar-nav::-webkit-scrollbar-track {
        background: transparent;
    }

    .sidebar-nav::-webkit-scrollbar-thumb {
        background: #ced4da;
        border-radius: 10px;
    }

    /* Sidebar Overlay */
    .sidebar-overlay {
        display: none;
        position: fixed;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        background: rgba(0, 0, 0, 0.4);
        z-index: 1000;
        opacity: 0;
        transition: opacity 0.3s ease;
    }

    .sidebar-overlay.show {
        display: block;
        opacity: 1;
    }
</style>

<div class="sidebar-overlay" id="sidebarOverlay"></div>

<nav class="sidebar" id="sidebar">

    <div class="sidebar-header">
        <a href="<?= $base_url ?>index.php" class="logo">Admin Panel</a>
    </div>

    <div class="sidebar-nav">
        <ul class="nav flex-column">

            <li class="nav-item">
                <a class="nav-link <?= isActiveMenu('index') ?>" href="<?= $base_url ?>index.php">
                    <i class="bi bi-grid-1x2-fill"></i>
                    <span>Dashboard</span>
                </a>
            </li>

            <li class="nav-item">
                <a class="nav-link collapsed" data-bs-toggle="collapse" data-bs-target="#contentSubmenu"
                    aria-expanded="<?= isActiveSubmenu(['articles']) ? 'true' : 'false' ?>">
                    <i class="bi bi-newspaper"></i>
                    <span>Konten</span>
                    <i class="bi bi-chevron-down"></i>
                </a>
                <div class="collapse submenu <?= isActiveSubmenu(['articles']) ?>" id="contentSubmenu">
                    <ul class="nav flex-column">
                        <li class="nav-item">
                            <a class="nav-link <?= isActiveMenu('articles', 'articles') ?>" href="<?= $base_url ?>modules/articles/articles.php">Artikel</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link <?= isActiveMenu('add', 'articles') ?>" href="<?= $base_url ?>modules/articles/add.php">Tambah Artikel</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link <?= isActiveMenu('categories', 'articles') ?>" href="<?= $base_url ?>modules/articles/categories.php">Kategori</a>
                        </li>
                    </ul>
                </div>
            </li>

            <li class="nav-item">
                <a class="nav-link collapsed" data-bs-toggle="collapse" data-bs-target="#productsSubmenu"
                    aria-expanded="<?= isActiveSubmenu(['products']) ? 'true' : 'false' ?>">
                    <i class="bi bi-archive-fill"></i>
                    <span>Produk</span>
                    <i class="bi bi-chevron-down"></i>
                </a>
                <div class="collapse submenu <?= isActiveSubmenu(['products']) ?>" id="productsSubmenu">
                    <ul class="nav flex-column">
                        <li class="nav-item">
                            <a class="nav-link <?= isActiveMenu('products', 'products') ?>" href="<?= $base_url ?>modules/products/products.php">Daftar Produk</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link <?= isActiveMenu('add', 'products') ?>" href="<?= $base_url ?>modules/products/add.php">Tambah Produk</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link <?= isActiveMenu('categories', 'products') ?>" href="<?= $base_url ?>modules/products/categories.php">Kategori</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link <?= isActiveMenu('stock', 'products') ?>" href="<?= $base_url ?>modules/products/stock.php">Stock Management</a>
                        </li>
                    </ul>
                </div>
            </li>

            <li class="nav-item">
                <a class="nav-link <?= isActiveMenu('messages', 'messages') ?>" href="<?= $base_url ?>modules/messages/messages.php">
                    <i class="bi bi-envelope-fill"></i>
                    <span>Pesan Masuk</span>
                </a>
            </li>

            <li class="nav-item">
                <a class="nav-link <?= isActiveMenu('comments', 'comments') ?>" href="<?= $base_url ?>modules/comments/comments.php">
                    <i class="bi bi-chat-left-dots-fill"></i>
                    <span>Komentar</span>
                </a>
            </li>

            <li class="nav-item">
                <a class="nav-link <?= isActiveMenu('settings', 'settings') ?>" href="<?= $base_url ?>modules/settings/settings.php">
                    <i class="bi bi-gear-fill"></i>
                    <span>Pengaturan Website</span>
                </a>
            </li>

            <li class="nav-item mt-4">
                <a class="nav-link" href="../../../index.php" target="_blank">
                    <i class="bi bi-box-arrow-up-right"></i>
                    <span>Lihat Website</span>
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link" href="<?= $base_url ?>logout.php" onclick="return confirm('Yakin ingin logout?')">
                    <i class="bi bi-box-arrow-right"></i>
                    <span>Logout</span>
                </a>
            </li>
        </ul>
    </div>
</nav>