<?php

/**
 * Dashboard Header Component
 * PT. Sarana Sentra Teknologi Utama - Admin Dashboard
 */
// Pengecekan otentikasi pengguna
if (!isset($_SESSION['user_id']) && basename($_SERVER['PHP_SELF']) !== 'login.php') {
    $admin_path_for_redirect = str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME']));
    $admin_pos = strpos($admin_path_for_redirect, '/admin');
    if ($admin_pos !== false) {
        $base_admin_path = substr($admin_path_for_redirect, 0, $admin_pos + 6);
    } else {
        $base_admin_path = '/admin'; 
    }
    header('Location: ' . $base_admin_path . '/login.php');
    exit;
}

/**
 * ---Fungsi Base URL 
 */
function getBaseUrl()
{
    $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https://' : 'http://';
    $host = $_SERVER['HTTP_HOST'];
    $script_dir = str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME']));
    $path_parts = explode('/', trim($script_dir, '/'));
    $admin_pos = array_search('admin', $path_parts);
    if ($admin_pos !== false) {
        $base_path = implode('/', array_slice($path_parts, 0, $admin_pos + 1));
    } else {
        $base_path = $script_dir;
    }
    $base_path = '/' . trim($base_path, '/');
    return rtrim($protocol . $host . $base_path, '/') . '/';
}

$base_url = getBaseUrl();
$assets_url = $base_url . 'assets';

$current_dir = dirname(__FILE__);
$config_path = realpath($current_dir . '/../config');

if (file_exists($config_path . 'config/database.php')) {
    require_once $config_path . 'config/database.php';
    $db = Database::getInstance();
    $user_id = $_SESSION['user_id'] ?? null;
    $user_profile_image = null;

    if ($user_id) {
        $user_data = $db->fetchOne("SELECT profile_image FROM users WHERE id = ?", [$user_id]);
        if ($user_data && !empty($user_data['profile_image'])) {
            $user_profile_image = $user_data['profile_image'];
        }
    }

    if ($user_profile_image && file_exists(__DIR__ . '/../../' . $user_profile_image)) {
        $profile_image_src = $base_url . $user_profile_image;
    } else {
        $profile_image_src = $assets_url . '/img/avatars/avatar.png';
    }
} else {
    $profile_image_src = $assets_url . '/img/avatars/avatar.png';
}
?>
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="Dashboard Admin PT. Sarana Sentra Teknologi Utama">
    <meta name="author" content="PT. Sarana Sentra Teknologi Utama">
    <title><?= isset($page_title) ? htmlspecialchars($page_title) . ' - ' : '' ?>Admin Dashboard | PT. Sarana Sentra Teknologi Utama</title>
    <link rel="icon" type="image/x-icon" href="<?= htmlspecialchars($assets_url) ?>/img/favicon.ico">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.7/css/dataTables.bootstrap5.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11.10.1/dist/sweetalert2.min.css">
    <link href="https://cdn.quilljs.com/1.3.6/quill.snow.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/cropperjs/1.5.13/cropper.min.css" rel="stylesheet">
    <link rel="stylesheet" href="<?= htmlspecialchars($assets_url) ?>/css/admin-style.css">
    <?php if (isset($additional_css)): ?>
        <?= $additional_css ?>
    <?php endif; ?>

    <script src="<?= htmlspecialchars($assets_url) ?>/js/admin-script.js" defer></script>
    <style>
        :root {
            --primary-color: #6f42c1;
            --secondary-color: #20c997;
            --background-color: #f8f9fa;
        }

        body {
            background-color: var(--background-color);
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            padding-top: 76px;
        }

        .navbar-custom {
            background: white;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.05);
            border-bottom: 1px solid #e9ecef;
            height: 76px;
        }

        .navbar-brand {
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .company-logo {
            height: 48px;
            width: 48px;
            object-fit: contain;
        }

        .brand-text {
            line-height: 1.2;
        }

        .company-name {
            font-size: 14px;
            font-weight: 700;
            color: var(--primary-color);
            margin: 0;
            white-space: nowrap;
        }

        .company-subtitle {
            font-size: 12px;
            font-weight: 500;
            color: #6c757d;
            margin: 0;
            white-space: nowrap;
        }

        .user-avatar {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            object-fit: cover;
            border: 2px solid #e9ecef;
            transition: all 0.2s ease;
        }

        .user-avatar:hover {
            border-color: var(--primary-color);
            transform: scale(1.05);
        }

        .dropdown-menu {
            border: 1px solid #dee2e6;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
        }

        #headerSidebarToggle {
            color: var(--primary-color);
            font-size: 1.2rem;
            padding: 0.375rem 0.75rem;
            transition: all 0.3s ease;
            background: transparent;
        }

        #headerSidebarToggle:hover {
            background: var(--primary-color);
            color: white;
            transform: scale(1.05);
            box-shadow: 0 2px 8px rgba(111, 66, 193, 0.3);
        }

        @media (max-width: 576px) {
            .brand-text {
                display: none;
            }
        }

        #headerSidebarToggle {
            display: inline-block !important;
            visibility: visible !important;
        }

        .user-dropdown .nav-link {
            padding: 0.25rem 0.5rem;
            border-radius: 50px;
            transition: all 0.2s ease;
        }

        .user-dropdown .nav-link:hover {
            background-color: rgba(111, 66, 193, 0.1);
        }

        .user-dropdown .dropdown-menu {
            min-width: 220px;
            padding: 0.5rem 0;
            margin-top: 0.5rem;
        }

        .user-dropdown .dropdown-header {
            padding: 0.5rem 1rem;
            font-weight: 600;
        }

        .user-dropdown .dropdown-item {
            padding: 0.5rem 1rem;
            display: flex;
            align-items: center;
        }

        .user-dropdown .dropdown-item i {
            width: 20px;
            text-align: center;
            margin-right: 0.5rem;
        }

        .user-dropdown .dropdown-item:hover {
            background-color: rgba(111, 66, 193, 0.05);
        }
    </style>
</head>

<body>
    <nav class="navbar navbar-expand-lg navbar-light navbar-custom fixed-top">
        <div class="container-fluid">
            <div class="d-flex align-items-center">
                <a class="navbar-brand me-3" href="<?= htmlspecialchars($base_url) ?>index.php">
                    <img src="<?= htmlspecialchars($assets_url) ?>/img/logo/logo.png" alt="Logo SST" class="company-logo">
                    <div class="brand-text d-none d-sm-block">
                        <div class="company-name">PT. Sarana Sentra</div>
                        <div class="company-subtitle">Teknologi Utama</div>
                    </div>
                </a>
                <button class="btn btn-outline-primary" type="button" id="headerSidebarToggle" aria-label="Toggle sidebar">
                    <i class="bi bi-list"></i>
                </button>
            </div>
            <div class="flex-grow-1"></div>
            <ul class="navbar-nav">
                <li class="nav-item dropdown user-dropdown">
                    <a class="nav-link d-flex align-items-center" href="#" id="userDropdown" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                        <img src="<?= htmlspecialchars($profile_image_src) ?>" alt="Avatar" class="user-avatar me-2">
                        <span class="d-none d-md-inline fw-semibold"><?= htmlspecialchars($_SESSION['username'] ?? 'User') ?></span>
                        <i class="bi bi-chevron-down ms-1 d-none d-md-inline"></i>
                    </a>
                    <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="userDropdown">
                        <li>
                            <h6 class="dropdown-header"><?= htmlspecialchars($_SESSION['username'] ?? 'User') ?></h6>
                        </li>
                        <li><small class="dropdown-header text-muted"><?= ucfirst(htmlspecialchars($_SESSION['user_role'] ?? 'Role')) ?></small></li>
                        <li>
                            <hr class="dropdown-divider">
                        </li>
                        <li>
                            <a class="dropdown-item" href="<?= htmlspecialchars($base_url) ?>modules/profile/profile.php">
                                <i class="bi bi-person me-2"></i>Edit Profile
                            </a>
                        </li>
                        <li>
                            <hr class="dropdown-divider">
                        </li>
                        <li>
                            <a class="dropdown-item text-danger" href="<?= htmlspecialchars($base_url) ?>logout.php" onclick="return confirm('Apakah Anda yakin ingin keluar?')">
                                <i class="bi bi-box-arrow-right me-2"></i>Keluar
                            </a>
                        </li>
                    </ul>
                </li>
            </ul>
        </div>
    </nav>
    <?php include 'sidebar.php'; ?>
    <!-- Main Content Area -->
    <div class="main-content">
        <div class="container-fluid">