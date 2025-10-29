<?php
$page_title = "Layanan";
include 'includes/header.php';
$db = getDB();

// Get all services
$stmt = $db->prepare("SELECT * FROM services ORDER BY id ASC");
$stmt->execute();
$services = $stmt->fetchAll();

// Get all products with categories
$stmt = $db->prepare("
    SELECT p.*, pc.name as category_name 
    FROM products p 
    LEFT JOIN product_categories pc ON p.category_id = pc.id 
    ORDER BY p.created_at DESC
");
$stmt->execute();
$products = $stmt->fetchAll();

// Group products by category
$products_by_category = [];
foreach ($products as $product) {
    $products_by_category[$product['category_name']][] = $product;
}

// Get site settings for WhatsApp
try {
    $stmt = $db->prepare("SELECT setting_key, setting_value FROM site_settings WHERE setting_key IN ('contact_whatsapp', 'whatsapp_message_template')");
    $stmt->execute();
    $settings = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);
} catch (PDOException $e) {
    $settings = [];
}
?>

<style>
    :root {
        --primary-color: #224647ff;
        --primary-light: #2a5456;
        --primary-dark: #1a3637;
        --accent-blue: #3498db;
        --accent-orange: #e67e22;
        --text-dark: #2c3e50;
        --text-light: #7f8c8d;
        --text-muted: #95a5a6;
        --bg-light: #f8f9fa;
        --bg-white: #ffffff;
        --border-color: #e9ecef;
        --shadow-light: 0 2px 10px rgba(34, 70, 71, 0.1);
        --shadow-medium: 0 5px 25px rgba(34, 70, 71, 0.15);
        --shadow-heavy: 0 10px 40px rgba(34, 70, 71, 0.2);
    }

    * {
        margin: 0;
        padding: 0;
        box-sizing: border-box;
    }

    body {
        font-family: 'Inter', 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        line-height: 1.6;
        color: var(--text-dark);
        background: var(--bg-white);
    }

    /* Hero Section */
    .hero-section {
        background: url("assets/img/hero-tech.jpg") center/cover no-repeat;
        padding: 4rem 0;
        position: relative;
        overflow: hidden;
        min-height: 60vh;
        display: flex;
        align-items: center;
    }

    .hero-section::before {
        content: '';
        position: absolute;
        top: 0;
        left: 0;
        right: 0;
        bottom: 0;
        background: linear-gradient(135deg,
                rgba(2, 55, 78, 0.85) 0%,
                rgba(27, 53, 66, 0.9) 100%);
        z-index: 1;
    }

    .hero-section::after {
        content: '';
        position: absolute;
        top: 0;
        left: 0;
        right: 0;
        bottom: 0;
        background: url("data:image/svg+xml,%3Csvg width='60' height='60' viewBox='0 0 60 60' xmlns='http://www.w3.org/2000/svg'%3E%3Cg fill='none' fill-rule='evenodd'%3E%3Cg fill='%23ffffff' fill-opacity='0.05'%3E%3Ccircle cx='30' cy='30' r='2'/%3E%3C/g%3E%3C/g%3E%3C/svg%3E") repeat;
        animation: float 20s ease-in-out infinite;
    }

    @keyframes float {

        0%,
        100% {
            transform: translateY(0px);
        }

        50% {
            transform: translateY(-20px);
        }
    }

    .hero-content {
        position: relative;
        z-index: 2;
        text-align: center;
        max-width: 800px;
        margin: 0 auto;
        color: white;
    }

    .hero-title {
        font-size: 3.5rem;
        font-weight: 700;
        margin-bottom: 1.5rem;
        text-shadow: 2px 2px 4px rgba(0, 0, 0, 0.3);
        letter-spacing: -0.02em;
    }

    .hero-subtitle {
        font-size: 1.25rem;
        font-weight: 300;
        opacity: 0.9;
        line-height: 1.6;
        margin-bottom: 2rem;
    }

    /* Service Navigation Header */
    .service-nav-header {
        background: white;
        padding: 0;
        box-shadow: var(--shadow-light);
        position: relative;
        top: 0;
        z-index: 1000;
    }

    .service-nav-container {
        display: grid;
        grid-template-columns: repeat(4, 1fr);
        max-width: 1200px;
        margin: 0 auto;
    }

    .service-nav-item {
        padding: 2rem 1.5rem;
        text-align: center;
        cursor: pointer;
        transition: all 0.3s ease;
        border-right: 1px solid var(--border-color);
        position: relative;
        background: white;
    }

    .service-nav-item:last-child {
        border-right: none;
    }

    .service-nav-item:hover {
        background: var(--bg-light);
        transform: translateY(-2px);
    }

    .service-nav-item.active {
        background: var(--primary-color);
        color: white;
        border: 3px solid rgba(255, 255, 255, 0.8);
        box-shadow: 0 0 15px rgba(34, 70, 71, 0.3);
    }

    .service-nav-item.active:hover {
        background: var(--primary-light);
    }

    .service-nav-icon {
        width: 60px;
        height: 60px;
        margin: 0 auto 1rem;
        border-radius: 15px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 1.8rem;
        transition: all 0.3s ease;
        background: var(--bg-light);
        color: var(--primary-color);
    }

    .service-nav-item.active .service-nav-icon {
        background: rgba(255, 255, 255, 0.2);
        color: white;
    }

    .service-nav-title {
        font-size: 1.1rem;
        font-weight: 600;
        margin-bottom: 0.5rem;
        color: var(--text-dark);
    }

    .service-nav-item.active .service-nav-title {
        color: white;
    }

    .service-nav-desc {
        font-size: 0.85rem;
        color: var(--text-light);
        line-height: 1.4;
    }

    .service-nav-item.active .service-nav-desc {
        color: rgba(255, 255, 255, 0.8);
    }

    /* Main Content */
    .main-content {
        padding: 80px 0;
        background: var(--bg-light);
    }

    .container {
        max-width: 1200px;
        margin: 0 auto;
        padding: 0 20px;
    }

    /* Service Section */
    .service-section {
        display: none;
        animation: fadeInUp 0.6s ease-out;
    }

    .service-section.active {
        display: block;
    }

    @keyframes fadeInUp {
        from {
            opacity: 0;
            transform: translateY(30px);
        }

        to {
            opacity: 1;
            transform: translateY(0);
        }
    }

    .service-header {
        text-align: center;
        margin-bottom: 4rem;
    }

    .service-header h2 {
        font-size: 2.5rem;
        font-weight: 700;
        color: var(--text-dark);
        margin-bottom: 1rem;
        position: relative;
    }

    .service-header h2::after {
        content: '';
        position: absolute;
        bottom: -15px;
        left: 50%;
        transform: translateX(-50%);
        width: 80px;
        height: 4px;
        background: var(--primary-color);
        border-radius: 2px;
    }

    .service-header p {
        font-size: 1.1rem;
        color: var(--text-light);
        max-width: 600px;
        margin: 2rem auto 0;
    }

    /* Service Content Grid */
    .service-content-grid {
        display: grid;
        grid-template-columns: 1fr 400px;
        gap: 4rem;
        margin-bottom: 4rem;
        align-items: start;
    }

    .service-main-content {
        background: white;
        border-radius: 20px;
        padding: 3rem;
        box-shadow: var(--shadow-light);
        border: 1px solid var(--border-color);
    }

    .service-sidebar {
        display: flex;
        flex-direction: column;
        gap: 2rem;
    }

    .sidebar-card {
        background: white;
        border-radius: 15px;
        padding: 2rem;
        box-shadow: var(--shadow-light);
        border: 1px solid var(--border-color);
    }

    .sidebar-card h4 {
        font-size: 1.3rem;
        font-weight: 600;
        color: var(--text-dark);
        margin-bottom: 1rem;
    }

    /* Feature Cards */
    .features-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
        gap: 2rem;
        margin: 3rem 0;
    }

    .feature-card {
        background: white;
        border-radius: 15px;
        padding: 2rem;
        text-align: center;
        transition: all 0.3s ease;
        border: 2px solid transparent;
        position: relative;
        overflow: hidden;
    }

    .feature-card::before {
        content: '';
        position: absolute;
        top: 0;
        left: -100%;
        width: 100%;
        height: 4px;
        background: var(--primary-color);
        transition: left 0.3s ease;
    }

    .feature-card:hover::before {
        left: 0;
    }

    .feature-card:hover {
        transform: translateY(-5px);
        box-shadow: var(--shadow-medium);
        border-color: var(--primary-color);
    }

    .feature-icon {
        width: 80px;
        height: 80px;
        margin: 0 auto 1.5rem;
        border-radius: 20px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 2rem;
        color: white;
        background: linear-gradient(135deg, var(--primary-color), var(--primary-light));
        transition: transform 0.3s ease;
    }

    .feature-card:hover .feature-icon {
        transform: scale(1.1) rotate(5deg);
    }

    .feature-card h3 {
        font-size: 1.3rem;
        font-weight: 600;
        color: var(--text-dark);
        margin-bottom: 1rem;
    }

    .feature-card p {
        color: var(--text-light);
        line-height: 1.6;
        font-size: 0.95rem;
    }

    /* Stats Cards */
    .stats-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
        gap: 1.5rem;
        margin: 3rem 0;
    }

    .stat-card {
        background: var(--primary-color);
        color: white;
        padding: 2rem;
        border-radius: 15px;
        text-align: center;
        position: relative;
        overflow: hidden;
    }

    .stat-card::before {
        content: '';
        position: absolute;
        top: 0;
        left: 0;
        right: 0;
        bottom: 0;
        background: url("data:image/svg+xml,%3Csvg width='40' height='40' viewBox='0 0 40 40' xmlns='http://www.w3.org/2000/svg'%3E%3Cg fill='%23ffffff' fill-opacity='0.1'%3E%3Ccircle cx='20' cy='20' r='2'/%3E%3C/g%3E%3C/svg%3E") repeat;
    }

    .stat-content {
        position: relative;
        z-index: 2;
    }

    .stat-number {
        font-size: 2.5rem;
        font-weight: 700;
        margin-bottom: 0.5rem;
    }

    .stat-label {
        font-size: 1rem;
        opacity: 0.9;
        font-weight: 500;
    }

    /* Products Section */
    .products-section {
        background: white;
        padding: 4rem 0;
        margin-top: 4rem;
        border-radius: 30px 30px 0 0;
    }

    .products-grid {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
        gap: 2rem;
        margin-top: 3rem;
    }

    .product-card {
        background: white;
        border-radius: 15px;
        overflow: hidden;
        box-shadow: var(--shadow-light);
        transition: all 0.3s ease;
        border: 1px solid var(--border-color);
        position: relative;
    }

    .product-card:hover {
        transform: translateY(-8px);
        box-shadow: var(--shadow-medium);
    }

    .product-image {
        height: 220px;
        background: var(--bg-light);
        display: flex;
        align-items: center;
        justify-content: center;
        overflow: hidden;
        position: relative;
    }

    .product-image img {
        width: 100%;
        height: 100%;
        object-fit: cover;
        transition: transform 0.3s ease;
    }

    .product-card:hover .product-image img {
        transform: scale(1.05);
    }

    .product-info {
        padding: 1.5rem;
        position: relative;
    }

    .product-info h4 {
        font-size: 1.2rem;
        font-weight: 600;
        color: var(--text-dark);
        margin-bottom: 0.5rem;
        line-height: 1.4;
    }

    .product-info p {
        color: var(--text-light);
        font-size: 0.9rem;
        line-height: 1.5;
        margin-bottom: 1rem;
    }

    .product-stock {
        display: inline-block;
        background: linear-gradient(135deg, #27ae60, #2ecc71);
        color: white;
        padding: 0.3rem 1rem;
        border-radius: 20px;
        font-size: 0.8rem;
        font-weight: 600;
    }

    /* WhatsApp Button Styles */
    .whatsapp-btn {
        background: #25d366 !important;
        color: white !important;
        padding: 0.5rem 1rem;
        border-radius: 20px;
        font-size: 0.8rem;
        text-decoration: none;
        display: inline-flex;
        align-items: center;
        gap: 0.3rem;
        transition: all 0.3s ease;
        border: none;
        cursor: pointer;
    }

    .whatsapp-btn:hover {
        background: #128c7e !important;
        transform: translateY(-2px);
        box-shadow: 0 4px 12px rgba(37, 211, 102, 0.3);
        color: white !important;
        text-decoration: none;
    }

    .whatsapp-btn i {
        font-size: 1rem;
    }

    /* Product Card Enhancements */
    .product-actions {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-top: 1rem;
        flex-wrap: wrap;
        gap: 0.5rem;
    }

    /* CTA Section */
    .cta-section {
        background: var(--primary-color);
        color: white;
        padding: 5rem 0;
        text-align: center;
        position: relative;
        overflow: hidden;
    }

    .cta-section::before {
        content: '';
        position: absolute;
        top: 0;
        left: 0;
        right: 0;
        bottom: 0;
        background: url("data:image/svg+xml,%3Csvg width='60' height='60' viewBox='0 0 60 60' xmlns='http://www.w3.org/2000/svg'%3E%3Cg fill='none' fill-rule='evenodd'%3E%3Cg fill='%23ffffff' fill-opacity='0.08'%3E%3Ccircle cx='30' cy='30' r='3'/%3E%3C/g%3E%3C/g%3E%3C/svg%3E") repeat;
    }

    .cta-content {
        position: relative;
        z-index: 2;
        max-width: 800px;
        margin: 0 auto;
    }

    .cta-title {
        font-size: 2.5rem;
        font-weight: 700;
        margin-bottom: 1.5rem;
    }

    .cta-text {
        font-size: 1.2rem;
        margin-bottom: 2.5rem;
        opacity: 0.9;
        line-height: 1.6;
    }

    .cta-buttons {
        display: flex;
        gap: 1rem;
        justify-content: center;
        flex-wrap: wrap;
    }

    .btn {
        padding: 1rem 2rem;
        border-radius: 50px;
        font-weight: 600;
        text-decoration: none;
        transition: all 0.3s ease;
        border: none;
        cursor: pointer;
        font-size: 1rem;
        display: inline-flex;
        align-items: center;
        gap: 0.5rem;
    }

    .btn-primary {
        background: white;
        color: var(--primary-color);
    }

    .btn-primary:hover {
        transform: translateY(-3px);
        box-shadow: 0 10px 20px rgba(255, 255, 255, 0.2);
    }

    .btn-outline {
        background: transparent;
        color: white;
        border: 2px solid white;
    }

    .btn-outline:hover {
        background: white;
        color: var(--primary-color);
        transform: translateY(-3px);
        box-shadow: 0 10px 20px rgba(255, 255, 255, 0.2);
    }

    /* Responsive Design */
    @media (max-width: 1024px) {
        .service-content-grid {
            grid-template-columns: 1fr;
            gap: 2rem;
        }

        .service-nav-container {
            grid-template-columns: repeat(2, 1fr);
        }

        .service-nav-item {
            padding: 1.5rem 1rem;
        }
    }

    @media (max-width: 768px) {
        .hero-section {
            padding: 3rem 0;
            min-height: 50vh;
        }

        .hero-title {
            font-size: 2.5rem;
        }

        .hero-subtitle {
            font-size: 1rem;
        }

        .service-nav-header {
            position: sticky;
            top: 0;
        }

        .service-nav-container {
            grid-template-columns: repeat(4, 1fr);
            overflow-x: auto;
            -webkit-overflow-scrolling: touch;
            scrollbar-width: none;
        }

        .service-nav-container::-webkit-scrollbar {
            display: none;
        }

        .service-nav-item {
            min-width: 120px;
            padding: 1.2rem 0.6rem;
        }

        .service-nav-icon {
            width: 50px;
            height: 50px;
            font-size: 1.5rem;
        }

        .service-nav-title {
            font-size: 0.9rem;
        }

        .service-nav-desc {
            font-size: 0.75rem;
            display: none;
        }

        .features-grid {
            grid-template-columns: 1fr;
        }

        .products-grid {
            grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
        }

        .cta-title {
            font-size: 2rem;
        }

        .cta-buttons {
            flex-direction: column;
            align-items: center;
        }

        .btn {
            width: 100%;
            max-width: 300px;
            justify-content: center;
        }
    }

    @media (max-width: 576px) {
        .hero-section {
            padding: 2rem 0;
            min-height: 40vh;
        }

        .hero-title {
            font-size: 2rem;
        }

        .hero-subtitle {
            font-size: 0.9rem;
        }

        .service-nav-container {
            grid-template-columns: repeat(2, 1fr);
        }

        .service-main-content {
            padding: 2rem;
        }

        .sidebar-card {
            padding: 1.5rem;
        }

        .product-actions {
            flex-direction: column;
            align-items: stretch;
        }

        .whatsapp-btn {
            justify-content: center;
            width: 100%;
        }

        .product-stock {
            text-align: center;
            margin-bottom: 0.5rem;
        }
    }

    @media (max-width: 480px) {
        .service-main-content {
            padding: 2rem;
        }

        .sidebar-card {
            padding: 1.5rem;
        }

        .hero-title {
            font-size: 2rem;
        }

        .hero-subtitle {
            font-size: 1rem;
        }

        .service-nav-container {
            grid-template-columns: repeat(4, 1fr);
        }

        .service-nav-item {
            min-width: 100px;
            padding: 1rem 0.4rem;
        }

        .service-nav-icon {
            width: 40px;
            height: 40px;
            font-size: 1.2rem;
        }

        .service-nav-title {
            font-size: 0.8rem;
        }
    }
</style>

<!-- Hero Section -->
<section class="hero-section">
    <div class="container">
        <div class="hero-content" data-aos="fade-up">
            <h1 class="hero-title">Solusi Teknologi Terpadu</h1>
            <p class="hero-subtitle">Layanan pengadaan dan perawatan teknologi untuk kebutuhan personal dan bisnis dengan dukungan profesional</p>
        </div>
    </div>
</section>

<!-- Service Navigation Header -->
<section class="service-nav-header">
    <div class="service-nav-container">
        <div class="service-nav-item active" data-service="bisnis">
            <div class="service-nav-icon">
                <i class="fas fa-building"></i>
            </div>
            <h3 class="service-nav-title">Pengadaan Bisnis</h3>
            <p class="service-nav-desc">Solusi teknologi untuk kebutuhan korporat</p>
        </div>
        <div class="service-nav-item" data-service="retail">
            <div class="service-nav-icon">
                <i class="fas fa-store"></i>
            </div>
            <h3 class="service-nav-title">Pengadaan Retail</h3>
            <p class="service-nav-desc">Perangkat untuk kebutuhan personal</p>
        </div>
        <div class="service-nav-item" data-service="kontrak">
            <div class="service-nav-icon">
                <i class="fas fa-file-contract"></i>
            </div>
            <h3 class="service-nav-title">Perawatan Kontrak</h3>
            <p class="service-nav-desc">Maintenance berkala dan terjadwal</p>
        </div>
        <div class="service-nav-item" data-service="layanan">
            <div class="service-nav-icon">
                <i class="fas fa-tools"></i>
            </div>
            <h3 class="service-nav-title">Perawatan Layanan</h3>
            <p class="service-nav-desc">Support on-demand dan emergency</p>
        </div>
    </div>
</section>

<!-- Main Content -->
<section class="main-content">
    <div class="container">
        <!-- Pengadaan Bisnis Section -->
        <div class="service-section active" id="bisnis-section">
            <div class="service-header">
                <h2>Pengadaan Berbasis Bisnis</h2>
                <p>Menyediakan perangkat teknologi berkualitas tinggi untuk kebutuhan korporat dan institusi besar</p>
            </div>

            <div class="service-content-grid">
                <div class="service-main-content">
                    <?php
                    $service_bisnis = array_filter($services, function ($s) {
                        return $s['service_slug'] == 'pengadaan1';
                    });
                    $service_bisnis = reset($service_bisnis);
                    if ($service_bisnis):
                        $features_bisnis = json_decode($service_bisnis['features'], true) ?? [];
                    ?>
                        <h3 style="margin-bottom: 1.5rem; color: var(--primary-color); font-size: 1.8rem;">Mengapa Memilih Layanan Pengadaan Bisnis?</h3>
                        <p style="font-size: 1.1rem; line-height: 1.7; color: var(--text-light); margin-bottom: 2rem;">
                            <?php echo htmlspecialchars($service_bisnis['description']); ?>
                        </p>

                        <div class="features-grid">
                            <?php
                            $feature_icons = ['fa-shield-alt', 'fa-clock', 'fa-users-cog', 'fa-chart-line', 'fa-headset', 'fa-award'];
                            foreach ($features_bisnis as $index => $feature):
                                $icon = $feature_icons[$index % count($feature_icons)];
                            ?>
                                <div class="feature-card">
                                    <div class="feature-icon">
                                        <i class="fas <?php echo $icon; ?>"></i>
                                    </div>
                                    <h3><?php echo htmlspecialchars($feature); ?></h3>
                                    <p>Layanan profesional yang dirancang khusus untuk mendukung kebutuhan bisnis enterprise Anda.</p>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>

                <div class="service-sidebar">
                    <div class="sidebar-card">
                        <h4>Konsultasi Gratis</h4>
                        <p style="color: var(--text-light); margin-bottom: 1.5rem;">Dapatkan analisis kebutuhan teknologi untuk bisnis Anda</p>
                        <a href="kontak.php" class="btn btn-primary" style="width: 100%; justify-content: center;">
                            <i class="fas fa-phone"></i>
                            Hubungi Kami
                        </a>
                    </div>

                    <div class="sidebar-card">
                        <h4>Keunggulan Layanan</h4>
                        <ul style="list-style: none; padding: 0;">
                            <li style="padding: 0.5rem 0; border-bottom: 1px solid var(--border-color); display: flex; align-items: center;">
                                <i class="fas fa-check-circle" style="color: var(--primary-color); margin-right: 0.5rem;"></i>
                                Konsultasi mendalam
                            </li>
                            <li style="padding: 0.5rem 0; border-bottom: 1px solid var(--border-color); display: flex; align-items: center;">
                                <i class="fas fa-check-circle" style="color: var(--primary-color); margin-right: 0.5rem;"></i>
                                Implementasi cepat
                            </li>
                            <li style="padding: 0.5rem 0; border-bottom: 1px solid var(--border-color); display: flex; align-items: center;">
                                <i class="fas fa-check-circle" style="color: var(--primary-color); margin-right: 0.5rem;"></i>
                                Training komprehensif
                            </li>
                            <li style="padding: 0.5rem 0; display: flex; align-items: center;">
                                <i class="fas fa-check-circle" style="color: var(--primary-color); margin-right: 0.5rem;"></i>
                                Support berkelanjutan
                            </li>
                        </ul>
                    </div>

                    <div class="stats-grid">
                        <div class="stat-card">
                            <div class="stat-content">
                                <div class="stat-number">200+</div>
                                <div class="stat-label">Klien Korporat</div>
                            </div>
                        </div>
                        <div class="stat-card">
                            <div class="stat-content">
                                <div class="stat-number">99.5%</div>
                                <div class="stat-label">Uptime</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Products Section for Business -->
            <div class="products-section">
                <div class="service-header">
                    <h2>Produk Unggulan untuk Bisnis</h2>
                    <p>Pilihan teknologi terbaik untuk mendukung operasional perusahaan</p>
                </div>

                <div class="products-grid">
                    <?php
                    $business_products = isset($products_by_category['Office Needs']) ?
                        array_slice($products_by_category['Office Needs'], 0, 6) :
                        array_slice($products, 0, 6);
                    foreach ($business_products as $product): ?>
                        <div class="product-card">
                            <div class="product-image">
                                <?php if ($product['image_path']): ?>
                                    <img src="admin/uploads/products/<?php echo htmlspecialchars($product['image_path']); ?>"
                                        alt="<?php echo htmlspecialchars($product['name']); ?>">
                                <?php else: ?>
                                    <i class="fas fa-image fa-3x" style="color: var(--text-muted);"></i>
                                <?php endif; ?>
                            </div>
                            <div class="product-info">
                                <h4><?php echo htmlspecialchars($product['name']); ?></h4>
                                <p><?php echo htmlspecialchars(substr($product['description'], 0, 100)); ?>...</p>
                                <div class="product-actions">
                                    <span class="product-stock">Stok: <?php echo $product['stock']; ?></span>
                                    <?php
                                    $whatsapp_number = $settings['contact_whatsapp'] ?? '+6221123456789';
                                    $formatted_whatsapp = preg_replace('/[^\d+]/', '', $whatsapp_number);
                                    $product_message = "Halo, saya tertarik dengan produk bisnis *" . $product['name'] . "* untuk kebutuhan perusahaan. Bisakah saya mendapat informasi lebih lanjut mengenai harga, spesifikasi, dan paket instalasi?";
                                    $whatsapp_url = "https://wa.me/" . $formatted_whatsapp . "?text=" . urlencode($product_message);
                                    ?>
                                    <a href="<?php echo $whatsapp_url; ?>" target="_blank" class="whatsapp-btn">
                                        <i class="fab fa-whatsapp"></i>
                                        Pesan Sekarang
                                    </a>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>

        <!-- Pengadaan Retail Section -->
        <div class="service-section" id="retail-section">
            <div class="service-header">
                <h2>Pengadaan Berbasis Retail</h2>
                <p>Menyediakan perangkat teknologi untuk kebutuhan personal dan rumah dengan kualitas terjamin</p>
            </div>

            <div class="service-content-grid">
                <div class="service-main-content">
                    <?php
                    $service_retail = array_filter($services, function ($s) {
                        return $s['service_slug'] == 'pengadaan2';
                    });
                    $service_retail = reset($service_retail);
                    if ($service_retail):
                        $features_retail = json_decode($service_retail['features'], true) ?? [];
                    ?>
                        <h3 style="margin-bottom: 1.5rem; color: var(--primary-color); font-size: 1.8rem;">Solusi Retail Terpadu</h3>
                        <p style="font-size: 1.1rem; line-height: 1.7; color: var(--text-light); margin-bottom: 2rem;">
                            <?php echo htmlspecialchars($service_retail['description']); ?>
                        </p>

                        <div class="features-grid">
                            <?php
                            $retail_icons = ['fa-cash-register', 'fa-barcode', 'fa-mobile-alt', 'fa-wifi', 'fa-credit-card', 'fa-shopping-cart'];
                            foreach ($features_retail as $index => $feature):
                                $icon = $retail_icons[$index % count($retail_icons)];
                            ?>
                                <div class="feature-card">
                                    <div class="feature-icon">
                                        <i class="fas <?php echo $icon; ?>"></i>
                                    </div>
                                    <h3><?php echo htmlspecialchars($feature); ?></h3>
                                    <p>Teknologi retail modern untuk meningkatkan pengalaman belanja dan efisiensi operasional toko.</p>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>

                <div class="service-sidebar">
                    <div class="sidebar-card">
                        <h4>Demo Produk</h4>
                        <p style="color: var(--text-light); margin-bottom: 1.5rem;">Lihat langsung bagaimana teknologi kami bekerja</p>
                        <a href="kontak.php" class="btn btn-primary" style="width: 100%; justify-content: center;">
                            <i class="fas fa-play"></i>
                            Hubungi Kami
                        </a>
                    </div>

                    <div class="sidebar-card">
                        <h4>Paket Retail</h4>
                        <ul style="list-style: none; padding: 0;">
                            <li style="padding: 0.5rem 0; border-bottom: 1px solid var(--border-color); display: flex; align-items: center;">
                                <i class="fas fa-check-circle" style="color: var(--accent-blue); margin-right: 0.5rem;"></i>
                                POS System terintegrasi
                            </li>
                            <li style="padding: 0.5rem 0; border-bottom: 1px solid var(--border-color); display: flex; align-items: center;">
                                <i class="fas fa-check-circle" style="color: var(--accent-blue); margin-right: 0.5rem;"></i>
                                Inventory management
                            </li>
                            <li style="padding: 0.5rem 0; border-bottom: 1px solid var(--border-color); display: flex; align-items: center;">
                                <i class="fas fa-check-circle" style="color: var(--accent-blue); margin-right: 0.5rem;"></i>
                                Customer analytics
                            </li>
                            <li style="padding: 0.5rem 0; display: flex; align-items: center;">
                                <i class="fas fa-check-circle" style="color: var(--accent-blue); margin-right: 0.5rem;"></i>
                                Multi-channel integration
                            </li>
                        </ul>
                    </div>

                    <div class="stats-grid">
                        <div class="stat-card" style="background: var(--accent-blue);">
                            <div class="stat-content">
                                <div class="stat-number">500+</div>
                                <div class="stat-label">Pelanggan Retail</div>
                            </div>
                        </div>
                        <div class="stat-card" style="background: var(--accent-blue);">
                            <div class="stat-content">
                                <div class="stat-number">24/7</div>
                                <div class="stat-label">Support</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Products Section for Retail -->
            <div class="products-section">
                <div class="service-header">
                    <h2>Produk Retail Terpilih</h2>
                    <p>Perangkat dan sistem khusus untuk mengoptimalkan kebutuhan personal dan rumah</p>
                </div>

                <div class="products-grid">
                    <?php
                    $retail_products = isset($products_by_category['Customer-Retail']) ?
                        array_slice($products_by_category['Customer-Retail'], 0, 6) :
                        array_slice($products, 6, 6);
                    foreach ($retail_products as $product): ?>
                        <div class="product-card">
                            <div class="product-image">
                                <?php if ($product['image_path']): ?>
                                    <img src="admin/uploads/products/<?php echo htmlspecialchars($product['image_path']); ?>"
                                        alt="<?php echo htmlspecialchars($product['name']); ?>">
                                <?php else: ?>
                                    <i class="fas fa-image fa-3x" style="color: var(--text-muted);"></i>
                                <?php endif; ?>
                            </div>
                            <div class="product-info">
                                <h4><?php echo htmlspecialchars($product['name']); ?></h4>
                                <p><?php echo htmlspecialchars(substr($product['description'], 0, 100)); ?>...</p>
                                <div class="product-actions">
                                    <span class="product-stock">Stok: <?php echo $product['stock']; ?></span>
                                    <?php
                                    $whatsapp_number = $settings['contact_whatsapp'] ?? '+6221123456789';
                                    $formatted_whatsapp = preg_replace('/[^\d+]/', '', $whatsapp_number);
                                    $product_message = "Halo, saya tertarik dengan produk retail *" . $product['name'] . "* untuk kebutuhan personal/rumah. Bisakah saya mendapat informasi mengenai harga dan ketersediaan produk?";
                                    $whatsapp_url = "https://wa.me/" . $formatted_whatsapp . "?text=" . urlencode($product_message);
                                    ?>
                                    <a href="<?php echo $whatsapp_url; ?>" target="_blank" class="whatsapp-btn">
                                        <i class="fab fa-whatsapp"></i>
                                        Pesan Sekarang
                                    </a>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>

        <!-- Perawatan Kontrak Section -->
        <div class="service-section" id="kontrak-section">
            <div class="service-header">
                <h2>Perawatan Berbasis Kontrak</h2>
                <p>Layanan maintenance berkala dengan jaminan SLA dan biaya yang dapat diprediksi untuk bisnis</p>
            </div>

            <div class="service-content-grid">
                <div class="service-main-content">
                    <?php
                    $service_kontrak = array_filter($services, function ($s) {
                        return $s['service_slug'] == 'perawatan1';
                    });
                    $service_kontrak = reset($service_kontrak);
                    if ($service_kontrak):
                        $features_kontrak = json_decode($service_kontrak['features'], true) ?? [];
                    ?>
                        <h3 style="margin-bottom: 1.5rem; color: var(--primary-color); font-size: 1.8rem;">Kontrak Maintenance Profesional</h3>
                        <p style="font-size: 1.1rem; line-height: 1.7; color: var(--text-light); margin-bottom: 2rem;">
                            <?php echo htmlspecialchars($service_kontrak['description']); ?>
                        </p>

                        <!-- Process Timeline -->
                        <div style="margin: 3rem 0;">
                            <h4 style="color: var(--text-dark); margin-bottom: 2rem; text-align: center;">Proses Kerja Kami</h4>
                            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 1.5rem;">
                                <div style="text-align: center; position: relative;">
                                    <div style="width: 60px; height: 60px; background: var(--primary-color); border-radius: 50%; display: flex; align-items: center; justify-content: center; margin: 0 auto 1rem; color: white; font-size: 1.5rem; font-weight: 700;">1</div>
                                    <h5 style="color: var(--text-dark); margin-bottom: 0.5rem;">Assessment</h5>
                                    <p style="color: var(--text-light); font-size: 0.9rem;">Evaluasi infrastruktur IT existing</p>
                                </div>
                                <div style="text-align: center;">
                                    <div style="width: 60px; height: 60px; background: var(--primary-color); border-radius: 50%; display: flex; align-items: center; justify-content: center; margin: 0 auto 1rem; color: white; font-size: 1.5rem; font-weight: 700;">2</div>
                                    <h5 style="color: var(--text-dark); margin-bottom: 0.5rem;">Planning</h5>
                                    <p style="color: var(--text-light); font-size: 0.9rem;">Penyusunan kontrak dan SLA</p>
                                </div>
                                <div style="text-align: center;">
                                    <div style="width: 60px; height: 60px; background: var(--primary-color); border-radius: 50%; display: flex; align-items: center; justify-content: center; margin: 0 auto 1rem; color: white; font-size: 1.5rem; font-weight: 700;">3</div>
                                    <h5 style="color: var(--text-dark); margin-bottom: 0.5rem;">Implementation</h5>
                                    <p style="color: var(--text-light); font-size: 0.9rem;">Pelaksanaan maintenance rutin</p>
                                </div>
                                <div style="text-align: center;">
                                    <div style="width: 60px; height: 60px; background: var(--primary-color); border-radius: 50%; display: flex; align-items: center; justify-content: center; margin: 0 auto 1rem; color: white; font-size: 1.5rem; font-weight: 700;">4</div>
                                    <h5 style="color: var(--text-dark); margin-bottom: 0.5rem;">Monitoring</h5>
                                    <p style="color: var(--text-light); font-size: 0.9rem;">Pemantauan dan pelaporan berkala</p>
                                </div>
                            </div>
                        </div>

                        <div class="features-grid">
                            <?php
                            $kontrak_icons = ['fa-file-contract', 'fa-calendar-check', 'fa-shield-alt', 'fa-chart-bar', 'fa-headset', 'fa-tools'];
                            foreach ($features_kontrak as $index => $feature):
                                $icon = $kontrak_icons[$index % count($kontrak_icons)];
                            ?>
                                <div class="feature-card">
                                    <div class="feature-icon" style="background: linear-gradient(135deg, #27ae60, #2ecc71);">
                                        <i class="fas <?php echo $icon; ?>"></i>
                                    </div>
                                    <h3><?php echo htmlspecialchars($feature); ?></h3>
                                    <p>Layanan kontrak yang memberikan kepastian dan jaminan untuk kontinuitas operasional IT Anda.</p>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>

                <div class="service-sidebar">
                    <div class="sidebar-card">
                        <h4>Konsultasi Kontrak</h4>
                        <p style="color: var(--text-light); margin-bottom: 1.5rem;">Dapatkan penawaran kontrak sesuai kebutuhan</p>
                        <a href="kontak.php" class="btn btn-primary" style="width: 100%; justify-content: center;">
                            <i class="fas fa-envelope"></i>
                            Hubungi Kami
                        </a>
                    </div>

                    <div class="sidebar-card">
                        <h4>Keuntungan Kontrak</h4>
                        <ul style="list-style: none; padding: 0;">
                            <li style="padding: 0.5rem 0; border-bottom: 1px solid var(--border-color); display: flex; align-items: center;">
                                <i class="fas fa-check-circle" style="color: #27ae60; margin-right: 0.5rem;"></i>
                                Biaya terprediksi
                            </li>
                            <li style="padding: 0.5rem 0; border-bottom: 1px solid var(--border-color); display: flex; align-items: center;">
                                <i class="fas fa-check-circle" style="color: #27ae60; margin-right: 0.5rem;"></i>
                                Prioritas penanganan
                            </li>
                            <li style="padding: 0.5rem 0; border-bottom: 1px solid var(--border-color); display: flex; align-items: center;">
                                <i class="fas fa-check-circle" style="color: #27ae60; margin-right: 0.5rem;"></i>
                                SLA terjamin
                            </li>
                            <li style="padding: 0.5rem 0; display: flex; align-items: center;">
                                <i class="fas fa-check-circle" style="color: #27ae60; margin-right: 0.5rem;"></i>
                                Laporan berkala
                            </li>
                        </ul>
                    </div>

                    <div class="stats-grid">
                        <div class="stat-card" style="background: #27ae60;">
                            <div class="stat-content">
                                <div class="stat-number">99.9%</div>
                                <div class="stat-label">SLA Achievement</div>
                            </div>
                        </div>
                        <div class="stat-card" style="background: #27ae60;">
                            <div class="stat-content">
                                <div class="stat-number">150+</div>
                                <div class="stat-label">Kontrak Aktif</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Perawatan Layanan Section -->
        <div class="service-section" id="layanan-section">
            <div class="service-header">
                <h2>Perawatan Berbasis Layanan</h2>
                <p>Solusi maintenance fleksibel dengan pembayaran per layanan dan dukungan emergency 24/7 untuk personal dan bisnis</p>
            </div>

            <div class="service-content-grid">
                <div class="service-main-content">
                    <?php
                    $service_layanan = array_filter($services, function ($s) {
                        return $s['service_slug'] == 'perawatan2';
                    });
                    $service_layanan = reset($service_layanan);
                    if ($service_layanan):
                        $features_layanan = json_decode($service_layanan['features'], true) ?? [];
                    ?>
                        <h3 style="margin-bottom: 1.5rem; color: var(--primary-color); font-size: 1.8rem;">Layanan On-Demand</h3>
                        <p style="font-size: 1.1rem; line-height: 1.7; color: var(--text-light); margin-bottom: 2rem;">
                            <?php echo htmlspecialchars($service_layanan['description']); ?>
                        </p>

                        <!-- Service Types -->
                        <div style="margin: 3rem 0;">
                            <h4 style="color: var(--text-dark); margin-bottom: 2rem;">Jenis Layanan Tersedia</h4>
                            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 1.5rem;">
                                <div style="background: white; border: 2px solid var(--border-color); border-radius: 15px; padding: 1.5rem; transition: all 0.3s ease;" class="service-type-card">
                                    <div style="display: flex; align-items: center; margin-bottom: 1rem;">
                                        <div style="width: 50px; height: 50px; background: var(--accent-orange); border-radius: 10px; display: flex; align-items: center; justify-content: center; margin-right: 1rem;">
                                            <i class="fas fa-wrench" style="color: white; font-size: 1.2rem;"></i>
                                        </div>
                                        <h5 style="color: var(--text-dark); margin: 0;">Emergency Repair</h5>
                                    </div>
                                    <p style="color: var(--text-light); font-size: 0.9rem;">Perbaikan darurat untuk masalah kritis yang membutuhkan penanganan segera.</p>
                                </div>
                                <div style="background: white; border: 2px solid var(--border-color); border-radius: 15px; padding: 1.5rem; transition: all 0.3s ease;" class="service-type-card">
                                    <div style="display: flex; align-items: center; margin-bottom: 1rem;">
                                        <div style="width: 50px; height: 50px; background: var(--accent-orange); border-radius: 10px; display: flex; align-items: center; justify-content: center; margin-right: 1rem;">
                                            <i class="fas fa-cogs" style="color: white; font-size: 1.2rem;"></i>
                                        </div>
                                        <h5 style="color: var(--text-dark); margin: 0;">Maintenance Rutin</h5>
                                    </div>
                                    <p style="color: var(--text-light); font-size: 0.9rem;">Perawatan berkala tanpa kontrak untuk menjaga performa optimal perangkat.</p>
                                </div>
                                <div style="background: white; border: 2px solid var(--border-color); border-radius: 15px; padding: 1.5rem; transition: all 0.3s ease;" class="service-type-card">
                                    <div style="display: flex; align-items: center; margin-bottom: 1rem;">
                                        <div style="width: 50px; height: 50px; background: var(--accent-orange); border-radius: 10px; display: flex; align-items: center; justify-content: center; margin-right: 1rem;">
                                            <i class="fas fa-laptop-medical" style="color: white; font-size: 1.2rem;"></i>
                                        </div>
                                        <h5 style="color: var(--text-dark); margin: 0;">Diagnosis & Troubleshooting</h5>
                                    </div>
                                    <p style="color: var(--text-light); font-size: 0.9rem;">Analisis mendalam untuk mengidentifikasi dan mengatasi masalah teknis.</p>
                                </div>
                                <div style="background: white; border: 2px solid var(--border-color); border-radius: 15px; padding: 1.5rem; transition: all 0.3s ease;" class="service-type-card">
                                    <div style="display: flex; align-items: center; margin-bottom: 1rem;">
                                        <div style="width: 50px; height: 50px; background: var(--accent-orange); border-radius: 10px; display: flex; align-items: center; justify-content: center; margin-right: 1rem;">
                                            <i class="fas fa-shipping-fast" style="color: white; font-size: 1.2rem;"></i>
                                        </div>
                                        <h5 style="color: var(--text-dark); margin: 0;">Sparepart & Upgrade</h5>
                                    </div>
                                    <p style="color: var(--text-light); font-size: 0.9rem;">Penyediaan komponen pengganti dan layanan upgrade perangkat.</p>
                                </div>
                            </div>
                        </div>

                        <div class="features-grid">
                            <?php
                            $layanan_icons = ['fa-clock', 'fa-mobile-alt', 'fa-user-cog', 'fa-credit-card', 'fa-certificate', 'fa-handshake'];
                            foreach ($features_layanan as $index => $feature):
                                $icon = $layanan_icons[$index % count($layanan_icons)];
                            ?>
                                <div class="feature-card">
                                    <div class="feature-icon" style="background: linear-gradient(135deg, var(--accent-orange), #d35400);">
                                        <i class="fas <?php echo $icon; ?>"></i>
                                    </div>
                                    <h3><?php echo htmlspecialchars($feature); ?></h3>
                                    <p>Layanan fleksibel yang dapat disesuaikan dengan kebutuhan spesifik dan budget Anda.</p>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>

                <div class="service-sidebar">
                    <div class="sidebar-card" style="border: 2px solid var(--accent-orange); background: #fff5f5;">
                        <h4 style="color: var(--accent-orange);">Hubungi kami</h4>
                        <p style="color: var(--accent-orange); margin-bottom: 1.5rem;">Butuh bantuan perawatan? Hubungi kami sekarang!</p>
                        <a href="kontak.php" class="btn" style="background: var(--accent-orange); color: white; width: 100%; justify-content: center;">
                            <i class="fas fa-phone"></i>
                            Hubungi Kami
                        </a>
                    </div>     

                    <div class="sidebar-card">
                        <h4>Cara Pemesanan</h4>
                        <div style="padding: 0;">
                            <div style="display: flex; align-items: center; padding: 0.75rem 0; border-bottom: 1px solid var(--border-color);">
                                <div style="width: 30px; height: 30px; background: var(--accent-orange); border-radius: 50%; display: flex; align-items: center; justify-content: center; margin-right: 1rem; color: white; font-weight: 600; font-size: 0.9rem;">1</div>
                                <span style="color: var(--text-dark); font-size: 0.9rem;">Hubungi customer service</span>
                            </div>
                            <div style="display: flex; align-items: center; padding: 0.75rem 0; border-bottom: 1px solid var(--border-color);">
                                <div style="width: 30px; height: 30px; background: var(--accent-orange); border-radius: 50%; display: flex; align-items: center; justify-content: center; margin-right: 1rem; color: white; font-weight: 600; font-size: 0.9rem;">2</div>
                                <span style="color: var(--text-dark); font-size: 0.9rem;">Jelaskan masalah Anda</span>
                            </div>
                            <div style="display: flex; align-items: center; padding: 0.75rem 0; border-bottom: 1px solid var(--border-color);">
                                <div style="width: 30px; height: 30px; background: var(--accent-orange); border-radius: 50%; display: flex; align-items: center; justify-content: center; margin-right: 1rem; color: white; font-weight: 600; font-size: 0.9rem;">3</div>
                                <span style="color: var(--text-dark); font-size: 0.9rem;">Dapatkan estimasi biaya</span>
                            </div>
                            <div style="display: flex; align-items: center; padding: 0.75rem 0;">
                                <div style="width: 30px; height: 30px; background: var(--accent-orange); border-radius: 50%; display: flex; align-items: center; justify-content: center; margin-right: 1rem; color: white; font-weight: 600; font-size: 0.9rem;">4</div>
                                <span style="color: var(--text-dark); font-size: 0.9rem;">Teknisi datang ke lokasi</span>
                            </div>
                        </div>
                    </div>

                    <div class="stats-grid">
                        <div class="stat-card" style="background: var(--accent-orange);">
                            <div class="stat-content">
                                <div class="stat-number">
                                    < 2H</div>
                                        <div class="stat-label">Response Time</div>
                                </div>
                            </div>
                            <div class="stat-card" style="background: var(--accent-orange);">
                                <div class="stat-content">
                                    <div class="stat-number">95%</div>
                                    <div class="stat-label">First Call Resolution</div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
</section>

<!-- Call to Action Section -->
<section class="cta-section">
    <div class="container">
        <div class="cta-content">
            <h2 class="cta-title">Siap Mengoptimalkan Teknologi Anda?</h2>
            <p class="cta-text">Hubungi tim ahli kami untuk konsultasi gratis dan dapatkan solusi teknologi yang tepat untuk kebutuhan personal dan bisnis Anda. Kami berkomitmen memberikan layanan terbaik dengan teknologi terdepan.</p>
            <div class="cta-buttons">
                <a href="kontak.php" class="btn btn-primary">
                    <i class="fas fa-phone"></i>
                    Konsultasi Gratis
                </a>
                <a href="produk.php" class="btn btn-outline">
                    <i class="fas fa-shopping-cart"></i>
                    Lihat Produk
                </a>
            </div>
        </div>
    </div>
</section>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        // Service Navigation
        const navItems = document.querySelectorAll('.service-nav-item');
        const sections = document.querySelectorAll('.service-section');

        // Function to switch to a specific section
        function switchToSection(sectionName) {
            // Remove active from all nav items
            navItems.forEach(nav => nav.classList.remove('active'));

            // Find and activate the correct nav item
            const targetNavItem = document.querySelector(`[data-service="${sectionName}"]`);
            if (targetNavItem) {
                targetNavItem.classList.add('active');
            }

            // Hide all sections
            sections.forEach(section => section.classList.remove('active'));

            // Show target section
            const targetSection = document.getElementById(sectionName + '-section');
            if (targetSection) {
                targetSection.classList.add('active');
            }
        }

        // Handle URL parameters on page load
        function handleInitialSection() {
            const urlParams = new URLSearchParams(window.location.search);
            const section = urlParams.get('section');
            if (section && ['bisnis', 'retail', 'kontrak', 'layanan'].includes(section)) {
                switchToSection(section);

                // Smooth scroll to the section after a short delay
                setTimeout(() => {
                    const serviceNavHeader = document.querySelector('.service-nav-header');
                    if (serviceNavHeader) {
                        serviceNavHeader.scrollIntoView({
                            behavior: 'smooth',
                            block: 'start'
                        });
                    }
                }, 300);
            }
        }

        // Handle clicks on service navigation items
        navItems.forEach(item => {
            item.addEventListener('click', function() {
                const service = this.dataset.service;
                switchToSection(service);

                // Update URL without page reload
                const newUrl = new URL(window.location);
                newUrl.searchParams.set('section', service);
                window.history.replaceState({}, '', newUrl);

                // Smooth scroll to main content
                document.querySelector('.main-content').scrollIntoView({
                    behavior: 'smooth',
                    block: 'start'
                });
            });
        });

        // Initialize the correct section based on URL
        handleInitialSection();

        // Hover effects for service type cards
        const serviceTypeCards = document.querySelectorAll('.service-type-card');
        serviceTypeCards.forEach(card => {
            card.addEventListener('mouseenter', function() {
                this.style.transform = 'translateY(-5px)';
                this.style.boxShadow = 'var(--shadow-medium)';
                this.style.borderColor = 'var(--accent-orange)';
            });

            card.addEventListener('mouseleave', function() {
                this.style.transform = 'translateY(0)';
                this.style.boxShadow = '';
                this.style.borderColor = 'var(--border-color)';
            });
        });

        // Initialize AOS if available
        if (typeof AOS !== 'undefined') {
            AOS.init({
                duration: 800,
                easing: 'ease-in-out',
                once: true,
                offset: 50
            });
        }

        // Counter animation for stats
        function animateCounters() {
            const counters = document.querySelectorAll('.stat-number');
            counters.forEach(counter => {
                const target = counter.textContent.replace(/[^\d.]/g, '');
                const isDecimal = target.includes('.');
                const targetNum = parseFloat(target);
                const increment = targetNum / 50;
                let current = 0;

                const timer = setInterval(() => {
                    current += increment;
                    if (current >= targetNum) {
                        counter.textContent = counter.textContent.replace(/[\d.]+/, target);
                        clearInterval(timer);
                    } else {
                        const displayValue = isDecimal ? current.toFixed(1) : Math.floor(current);
                        counter.textContent = counter.textContent.replace(/[\d.]+/, displayValue);
                    }
                }, 20);
            });
        }

        // Trigger counter animation when stats are visible
        const observerOptions = {
            threshold: 0.5
        };

        const observer = new IntersectionObserver((entries) => {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    const counters = entry.target.querySelectorAll('.stat-number');
                    if (counters.length > 0) {
                        animateCounters();
                    }
                }
            });
        }, observerOptions);

        // Observe all stat cards
        const statCards = document.querySelectorAll('.stat-card');
        statCards.forEach(card => observer.observe(card));

        // Handle browser back/forward buttons
        window.addEventListener('popstate', function(event) {
            handleInitialSection();
        });

        // WhatsApp button tracking (optional analytics)
        const whatsappBtns = document.querySelectorAll('.whatsapp-btn');
        whatsappBtns.forEach(btn => {
            btn.addEventListener('click', function() {
                // Optional: Add analytics tracking here
                console.log('WhatsApp product inquiry button clicked');
            });
        });

        // Enhanced hover effects for product cards
        const productCards = document.querySelectorAll('.product-card');
        productCards.forEach(card => {
            card.addEventListener('mouseenter', function() {
                const whatsappBtn = this.querySelector('.whatsapp-btn');
                if (whatsappBtn) {
                    whatsappBtn.style.transform = 'scale(1.05)';
                }
            });

            card.addEventListener('mouseleave', function() {
                const whatsappBtn = this.querySelector('.whatsapp-btn');
                if (whatsappBtn) {
                    whatsappBtn.style.transform = 'scale(1)';
                }
            });
        });

        // Contact method hover effects
        const contactMethods = document.querySelectorAll('.contact-method, .sidebar-card');
        contactMethods.forEach(method => {
            method.addEventListener('mouseenter', function() {
                this.style.transform = 'translateY(-3px) scale(1.01)';
            });

            method.addEventListener('mouseleave', function() {
                this.style.transform = 'translateY(0) scale(1)';
            });
        });
    });

    // Function to format WhatsApp number (utility function)
    function formatWhatsAppNumber(number) {
        // Remove all non-numeric characters except +
        let formatted = number.replace(/[^\d+]/g, '');

        // If starts with +62, keep as is
        if (formatted.startsWith('+62')) {
            return formatted;
        }

        // If starts with 62, add +
        if (formatted.startsWith('62')) {
            return '+' + formatted;
        }

        // If starts with 08, replace with +628
        if (formatted.startsWith('08')) {
            return '+62' + formatted.substring(1);
        }

        // If starts with 8, add +62
        if (formatted.startsWith('8')) {
            return '+62' + formatted;
        }

        return formatted;
    }
</script>

<?php include 'includes/footer.php'; ?>