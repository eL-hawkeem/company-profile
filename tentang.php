<?php
$page_title = "Tentang Kami";
include 'includes/header.php';
// Get about info
$about_title = getSiteSetting('about_title');
$about_text = getSiteSetting('about_text');
$about_features = getSiteSetting('about_features');
$visi = getSiteSetting('visi');
$misi = getSiteSetting('misi');
// Get team members
$db = getDB();
$stmt = $db->prepare("SELECT * FROM team_members ORDER BY display_order ASC, id ASC");
$stmt->execute();
$team_members = $stmt->fetchAll();
?>
<style>
    /* Page Hero */
    .page-hero {
        background: url("assets/img/about-page-title-bg.jpg") center/cover no-repeat;
        padding: 4rem 0;
        position: relative;
        overflow: hidden;
    }

    .page-hero::before {
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

    .page-hero-content {
        position: relative;
        z-index: 2;
        text-align: center;
        color: white;
    }

    .page-hero h1 {
        font-size: 3rem;
        font-weight: 700;
        margin-bottom: 1rem;
    }

    .page-hero p {
        font-size: 1.25rem;
        opacity: 0.9;
        max-width: 600px;
        margin: 0 auto;
    }

    .breadcrumb-nav {
        margin-top: 2rem;
        display: flex;
        justify-content: center;
    }

    .breadcrumb {
        background: rgba(255, 255, 255, 0.1);
        border-radius: 50px;
        padding: 0.5rem 1.5rem;
        margin: 0;
    }

    .breadcrumb-item a {
        color: rgba(255, 255, 255, 0.8);
        text-decoration: none;
    }

    .breadcrumb-item a:hover {
        color: white;
    }

    .breadcrumb-item.active {
        color: #47b2e4;
    }

    .breadcrumb-item+.breadcrumb-item::before {
        color: rgba(255, 255, 255, 0.6);
        content: "/";
        padding: 0 0.5rem;
    }

    /* About Content */
    .about-detail {
        padding: 6rem 0;
        background: white;
    }

    .about-content {
        max-width: 800px;
    }

    .about-content h2 {
        font-size: 2.5rem;
        font-weight: 700;
        color: var(--text-dark);
        margin-bottom: 1.5rem;
        position: relative;
    }

    .about-content h2::after {
        content: "";
        position: absolute;
        bottom: -10px;
        left: 0;
        width: 50px;
        height: 3px;
        background: var(--gradient-soft);
        border-radius: 2px;
    }

    .about-content .lead {
        font-size: 1.25rem;
        color: var(--text-light);
        line-height: 1.8;
        margin-bottom: 2rem;
    }

    .feature-list {
        list-style: none;
        padding: 0;
    }

    .feature-list li {
        margin-bottom: 1rem;
        padding-left: 2rem;
        position: relative;
        color: var(--text-dark);
        line-height: 1.6;
        transition: all 0.3s ease;
    }

    .feature-list li:hover {
        color: var(--primary-color);
        transform: translateX(5px);
    }

    .feature-list li::before {
        content: '';
        position: absolute;
        left: 0;
        top: 0.6rem;
        width: 12px;
        height: 12px;
        background: var(--gradient-primary);
        border-radius: 50%;
        box-shadow: 0 0 0 3px rgba(74, 124, 126, 0.2);
    }

    /* Vision Mission */
    .vision-mission {
        padding: 6rem 0;
        background: var(--secondary-color);
    }

    .vm-card {
        background: white;
        border-radius: 20px;
        padding: 3rem 2rem;
        text-align: center;
        border: 1px solid var(--border-color);
        transition: all 0.3s ease;
        height: 100%;
        position: relative;
        overflow: hidden;
    }

    .vm-card::before {
        content: '';
        position: absolute;
        top: 0;
        left: 0;
        right: 0;
        height: 4px;
        background: var(--gradient-primary);
        transform: scaleX(0);
        transition: transform 0.3s ease;
    }

    .vm-card:hover::before {
        transform: scaleX(1);
    }

    .vm-card:hover {
        transform: translateY(-10px);
        box-shadow: var(--shadow-medium);
        border-color: var(--primary-light);
    }

    .vm-icon {
        width: 80px;
        height: 80px;
        background: var(--gradient-primary);
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        margin: 0 auto 2rem;
        transition: all 0.3s ease;
    }

    .vm-icon i {
        font-size: 2rem;
        color: white;
    }

    .vm-card:hover .vm-icon {
        transform: scale(1.1) rotate(5deg);
    }

    .vm-card h3 {
        font-size: 1.8rem;
        font-weight: 600;
        color: var(--text-dark);
        margin-bottom: 1.5rem;
    }

    .vm-card p {
        color: var(--text-light);
        line-height: 1.8;
        text-align: left;
    }

    .mission-list {
        list-style: none;
        padding: 0;
        text-align: left;
    }

    .mission-list li {
        margin-bottom: 0.75rem;
        padding-left: 1.5rem;
        position: relative;
        color: var(--text-light);
        line-height: 1.6;
        transition: all 0.3s ease;
    }

    .mission-list li:hover {
        color: var(--primary-color);
        transform: translateX(5px);
    }

    .mission-list li::before {
        content: '→';
        position: absolute;
        left: 0;
        color: var(--primary-color);
        font-weight: bold;
    }

    /* Stats Section */
    .stats-section {
        padding: 4rem 0;
        background: var(--secondary-color);
    }

    .stat-item {
        text-align: center;
        padding: 2rem 1rem;
        border-radius: 15px;
        transition: all 0.3s ease;
    }

    .stat-item:hover {
        background: white;
        transform: translateY(-5px);
        box-shadow: var(--shadow-soft);
    }

    .stat-number {
        font-size: 3rem;
        font-weight: 700;
        color: var(--primary-color);
        display: block;
        line-height: 1;
        margin-bottom: 0.5rem;
        transition: all 0.3s ease;
    }

    .stat-item:hover .stat-number {
        transform: scale(1.1);
    }

    .stat-label {
        color: var(--text-light);
        font-weight: 500;
        text-transform: uppercase;
        letter-spacing: 0.5px;
        font-size: 0.9rem;
    }

    /* Timeline Section */
    .timeline-section .container {
        position: relative;
        z-index: 2;
    }

    .timeline-section .section-title h2 {
        color: #ffffff;
    }

    .timeline-section .section-title h2::after {
        background: rgba(255, 255, 255, 0.8);
    }

    .timeline-section .section-title .section-subtitle {
        color: rgba(255, 255, 255, 0.9);
    }

    .timeline-section {
        padding: 6rem 0;
        background: url("assets/img/dddepth-240.png") center/cover no-repeat;
        position: relative;
    }

    .timeline-section::before {
        content: "";
        position: absolute;
        top: 0;
        left: 0;
        right: 0;
        bottom: 0;
        background: linear-gradient(135deg,
                rgba(2, 55, 78, 0.85) 0%,
                rgba(44, 84, 86, 0.9) 100%);
        z-index: 1;
    }

    .timeline {
        position: relative;
        max-width: 800px;
        margin: 0 auto;
        position: relative;
        z-index: 2;
    }

    .timeline::before {
        content: '';
        position: absolute;
        left: 50%;
        transform: translateX(-50%);
        width: 2px;
        height: 100%;
        background: rgba(255, 255, 255, 0.3);
    }

    .timeline-item {
        position: relative;
        margin-bottom: 3rem;
    }

    .timeline-item:nth-child(odd) .timeline-content {
        text-align: right;
        margin-right: calc(50% + 2rem);
    }

    .timeline-item:nth-child(even) .timeline-content {
        text-align: left;
        margin-left: calc(50% + 2rem);
    }

    .timeline-marker {
        position: absolute;
        left: 50%;
        transform: translateX(-50%);
        width: 20px;
        height: 20px;
        background: var(--primary-color);
        border: 4px solid white;
        border-radius: 50%;
        box-shadow: 0 0 0 4px rgba(255, 255, 255, 0.2);
        z-index: 2;
    }

    .timeline-content {
        background: rgba(255, 255, 255, 0.95);
        padding: 1.5rem;
        border-radius: 10px;
        border: 1px solid rgba(255, 255, 255, 0.3);
        backdrop-filter: blur(10px);
        transition: all 0.3s ease;
    }

    .timeline-content:hover {
        transform: translateY(-5px);
        box-shadow: 0 15px 30px rgba(0, 0, 0, 0.2);
    }

    .timeline-year {
        font-size: 1.25rem;
        font-weight: 700;
        color: var(--primary-color);
        margin-bottom: 0.5rem;
    }

    .timeline-title {
        font-weight: 600;
        color: var(--text-dark);
        margin-bottom: 0.5rem;
    }

    .timeline-desc {
        color: var(--text-light);
        line-height: 1.6;
        margin: 0;
    }

    /* Team Section */
    .team-section {
        padding: 6rem 0;
        background: white;
    }

    .team-card {
        background: white;
        border-radius: 20px;
        padding: 2rem;
        text-align: center;
        border: 1px solid var(--border-color);
        transition: all 0.3s ease;
        height: 100%;
        position: relative;
        overflow: hidden;
    }

    .team-card::before {
        content: '';
        position: absolute;
        top: 0;
        left: 0;
        right: 0;
        height: 3px;
        background: var(--gradient-primary);
        transform: scaleX(0);
        transition: transform 0.3s ease;
    }

    .team-card:hover::before {
        transform: scaleX(1);
    }

    .team-card:hover {
        transform: translateY(-10px);
        box-shadow: var(--shadow-medium);
        border-color: var(--primary-light);
    }

    .team-avatar {
        width: 120px;
        height: 120px;
        border-radius: 50%;
        margin: 0 auto 1.5rem;
        overflow: hidden;
        border: 4px solid var(--border-color);
        transition: all 0.3s ease;
        position: relative;
    }

    .team-avatar img {
        width: 100%;
        height: 100%;
        object-fit: cover;
        transition: transform 0.3s ease;
    }

    .team-card:hover .team-avatar {
        border-color: var(--primary-color);
        transform: scale(1.05);
    }

    .team-card:hover .team-avatar img {
        transform: scale(1.1);
    }

    .team-name {
        font-size: 1.25rem;
        font-weight: 600;
        color: var(--text-dark);
        margin-bottom: 0.5rem;
    }

    .team-position {
        color: var(--primary-color);
        font-weight: 500;
        margin-bottom: 1rem;
    }

    .team-social {
        display: flex;
        justify-content: center;
        gap: 0.75rem;
        opacity: 0;
        transform: translateY(10px);
        transition: all 0.3s ease;
    }

    .team-card:hover .team-social {
        opacity: 1;
        transform: translateY(0);
    }

    .social-btn {
        width: 35px;
        height: 35px;
        background: var(--secondary-color);
        border: 1px solid var(--border-color);
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        color: var(--text-light);
        text-decoration: none;
        transition: all 0.3s ease;
        font-size: 0.9rem;
    }

    .social-btn:hover {
        background: var(--primary-color);
        color: white;
        border-color: var(--primary-color);
        transform: translateY(-2px);
    }

    /* Call to Action Section - Updated with new background */
    .cta-section {
        padding: 5rem 0;
        background: url("../img/dddepth-240.jpg") center/cover no-repeat;
        position: relative;
        overflow: hidden;
    }

    .cta-section::before {
        content: "";
        position: absolute;
        top: 0;
        left: 0;
        right: 0;
        bottom: 0;
        background: linear-gradient(135deg,
                rgba(2, 55, 78, 0.85) 0%,
                rgba(44, 84, 86, 0.95) 100%);
        z-index: 1;
    }

    .cta-section::after {
        content: "";
        position: absolute;
        top: -50%;
        left: -50%;
        width: 200%;
        height: 200%;
        background: radial-gradient(circle,
                rgba(255, 255, 255, 0.1) 0%,
                transparent 70%);
        animation: rotate 20s linear infinite;
        z-index: 1;
    }

    @keyframes rotate {
        0% {
            transform: rotate(0deg);
        }

        100% {
            transform: rotate(360deg);
        }
    }

    .cta-content {
        text-align: center;
        position: relative;
        z-index: 2;
    }

    .cta-content::before {
        content: "";
        position: absolute;
        top: -20px;
        left: 50%;
        transform: translateX(-50%);
        width: 100px;
        height: 2px;
        background: rgba(255, 255, 255, 0.5);
        border-radius: 1px;
    }

    .cta-title {
        font-size: 2.5rem;
        font-weight: 700;
        color: white;
        margin-bottom: 1.5rem;
        position: relative;
    }

    .cta-title::after {
        content: "";
        position: absolute;
        bottom: -10px;
        left: 50%;
        transform: translateX(-50%);
        width: 80px;
        height: 3px;
        background: rgba(255, 255, 255, 0.8);
        border-radius: 2px;
    }

    .cta-text {
        font-size: 1.125rem;
        color: rgba(255, 255, 255, 0.9);
        max-width: 700px;
        margin: 0 auto 2.5rem;
        line-height: 1.6;
        padding: 1rem 0;
        border-top: 1px solid rgba(255, 255, 255, 0.2);
        border-bottom: 1px solid rgba(255, 255, 255, 0.2);
    }

    .btn-hero-primary {
        background: linear-gradient(135deg, var(--primary-light), var(--primary-color));
        color: white;
        border: none;
        border-radius: 50px;
        padding: 0.75rem 2rem;
        font-weight: 600;
        transition: all 0.3s ease;
        box-shadow: 0 4px 15px rgba(74, 124, 126, 0.3);
        font-size: 0.9rem;
        position: relative;
        overflow: hidden;
    }

    .btn-hero-primary::before {
        content: "";
        position: absolute;
        top: 0;
        left: -100%;
        width: 100%;
        height: 100%;
        background: linear-gradient(90deg,
                transparent,
                rgba(255, 255, 255, 0.2),
                transparent);
        transition: left 0.5s ease;
    }

    .btn-hero-primary:hover::before {
        left: 100%;
    }

    .btn-hero-primary:hover {
        background: linear-gradient(135deg, var(--primary-color), var(--primary-dark));
        transform: translateY(-3px);
        box-shadow: 0 8px 25px rgba(74, 124, 126, 0.4);
        color: white;
    }

    .btn-hero-secondary {
        background: transparent;
        color: white;
        border: 2px solid rgba(255, 255, 255, 0.8);
        border-radius: 50px;
        padding: 0.75rem 2rem;
        font-weight: 600;
        transition: all 0.3s ease;
        font-size: 0.9rem;
    }

    .btn-hero-secondary:hover {
        background: rgba(255, 255, 255, 0.1);
        color: white;
        transform: translateY(-3px);
        border-color: white;
        box-shadow: 0 8px 25px rgba(255, 255, 255, 0.2);
    }

    /* Section Title */
    .section-title {
        text-align: center;
        margin-bottom: 4rem;
        position: relative;

    }

    .section-title h2 {
        font-size: 2.5rem;
        font-weight: 700;
        color: var(--text-dark);
        margin-bottom: 1rem;
        position: relative;
        display: inline-block;
    }

    .section-title h2::after {
        content: "";
        position: absolute;
        bottom: -8px;
        left: 50%;
        transform: translateX(-50%);
        width: 100%;
        height: 2px;
        background: var(--gradient-soft);
        border-radius: 1px;
    }

    .section-subtitle {
        font-size: 1.125rem;
        color: var(--text-light);
        max-width: 600px;
        margin: 0 auto;
        line-height: 1.6;
        padding-top: 1rem;
    }

    /* Responsive Design */
    @media (max-width: 991px) {
        .page-hero h1 {
            font-size: 2.5rem;
        }

        .page-hero p {
            font-size: 1.1rem;
        }

        .about-content h2 {
            font-size: 2rem;
        }

        .timeline::before {
            left: 2rem;
        }

        .timeline-marker {
            left: 2rem;
        }

        .timeline-item:nth-child(odd) .timeline-content,
        .timeline-item:nth-child(even) .timeline-content {
            text-align: left;
            margin-left: 4rem;
            margin-right: 0;
        }
    }

    @media (max-width: 768px) {
        .page-hero {
            padding: 3rem 0;
        }

        .page-hero h1 {
            font-size: 2.2rem;
        }

        .page-hero p {
            font-size: 1rem;
        }

        .breadcrumb {
            padding: 0.4rem 1rem;
        }

        .about-content h2 {
            font-size: 1.8rem;
        }

        .vm-card h3 {
            font-size: 1.5rem;
        }

        .team-card {
            padding: 1.5rem;
        }

        .team-avatar {
            width: 100px;
            height: 100px;
        }

        .section-title h2 {
            font-size: 2rem;
        }

        .cta-title {
            font-size: 2rem;
        }

        .cta-text {
            font-size: 1rem;
        }
    }

    @media (max-width: 576px) {
        .page-hero h1 {
            font-size: 1.8rem;
        }

        .page-hero p {
            font-size: 0.9rem;
        }

        .about-content h2 {
            font-size: 1.6rem;
        }

        .vm-card {
            padding: 2rem 1.5rem;
        }

        .vm-icon {
            width: 60px;
            height: 60px;
        }

        .vm-icon i {
            font-size: 1.5rem;
        }

        .team-card {
            padding: 1.5rem;
        }

        .team-avatar {
            width: 80px;
            height: 80px;
        }

        .section-title h2 {
            font-size: 1.75rem;
        }

        .cta-title {
            font-size: 1.75rem;
        }
    }
</style>
<!-- Page Hero -->
<section class="page-hero">
    <div class="container">
        <div class="page-hero-content" data-aos="fade-up">
            <h1>Tentang Kami</h1>
            <p>Mengenal lebih dekat PT. Sarana Sentra Teknologi Utama sebagai partner terpercaya dalam solusi teknologi bisnis</p>
            <nav class="breadcrumb-nav">
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="index.php">Beranda</a></li>
                    <li class="breadcrumb-item active">Tentang Kami</li>
                </ol>
            </nav>
        </div>
    </div>
</section>
<!-- About Detail Section -->
<section class="about-detail">
    <div class="container">
        <div class="row align-items-center g-5">
            <div class="col-lg-6" data-aos="fade-right">
                <div class="about-content">
                    <h2><?php echo htmlspecialchars($about_title); ?></h2>
                    <p class="lead"><?php echo htmlspecialchars($about_text); ?></p>
                    <?php if (!empty($about_features)): ?>
                        <ul class="feature-list">
                            <?php foreach ($about_features as $feature): ?>
                                <li><?php echo htmlspecialchars($feature); ?></li>
                            <?php endforeach; ?>
                        </ul>
                    <?php endif; ?>
                </div>
            </div>
            <div class="col-lg-6" data-aos="fade-left">
                <div class="about-image">
                    <img src="assets/img/team-page-title-bg.jpg" alt="Our Team" class="img-fluid rounded-4 shadow">
                </div>
            </div>
        </div>
    </div>
</section>
<!-- Stats Section -->
<section class="stats-section">
    <div class="container">
        <div class="row">
            <div class="col-lg-3 col-6" data-aos="fade-up" data-aos-delay="0">
                <div class="stat-item">
                    <span class="stat-number">6+</span>
                    <span class="stat-label">Tahun Pengalaman</span>
                </div>
            </div>
            <div class="col-lg-3 col-6" data-aos="fade-up" data-aos-delay="100">
                <div class="stat-item">
                    <span class="stat-number">200+</span>
                    <span class="stat-label">Klien Puas</span>
                </div>
            </div>
            <div class="col-lg-3 col-6" data-aos="fade-up" data-aos-delay="200">
                <div class="stat-item">
                    <span class="stat-number">200+</span>
                    <span class="stat-label">Proyek Selesai</span>
                </div>
            </div>
            <div class="col-lg-3 col-6" data-aos="fade-up" data-aos-delay="300">
                <div class="stat-item">
                    <span class="stat-number">24/7</span>
                    <span class="stat-label">Dukungan Teknis</span>
                </div>
            </div>
        </div>
    </div>
</section>
<!-- Vision Mission Section -->
<section class="vision-mission">
    <div class="container">
        <div class="section-title" data-aos="fade-up">
            <h2>Visi & Misi Kami</h2>
            <p class="section-subtitle">Landasan yang mengarahkan setiap langkah kami dalam memberikan layanan terbaik</p>
        </div>
        <div class="row g-4">
            <!-- Visi -->
            <div class="col-lg-6" data-aos="fade-up" data-aos-delay="0">
                <div class="vm-card">
                    <div class="vm-icon">
                        <i class="fas fa-eye"></i>
                    </div>
                    <h3>Visi</h3>
                    <p><?php echo htmlspecialchars($visi); ?></p>
                </div>
            </div>
            <!-- Misi -->
            <div class="col-lg-6" data-aos="fade-up" data-aos-delay="200">
                <div class="vm-card">
                    <div class="vm-icon">
                        <i class="fas fa-bullseye"></i>
                    </div>
                    <h3>Misi</h3>
                    <?php if (!empty($misi)): ?>
                        <ul class="mission-list">
                            <?php foreach ($misi as $item): ?>
                                <li><?php echo htmlspecialchars($item); ?></li>
                            <?php endforeach; ?>
                        </ul>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</section>
<!-- Timeline Section -->
<section class="timeline-section">
    <div class="container">
        <div class="section-title" data-aos="fade-up">
            <h2>Sejarah Perjalanan Kami</h2>
            <p class="section-subtitle">Milestone penting dalam perjalanan perusahaan</p>
        </div>
        <div class="timeline">
            <div class="timeline-item" data-aos="fade-up">
                <div class="timeline-marker"></div>
                <div class="timeline-content">
                    <div class="timeline-year">2019</div>
                    <h5 class="timeline-title">Pendirian Perusahaan</h5>
                    <p class="timeline-desc">PT. Sarana Sentra Teknologi Utama didirikan dengan visi menjadi penyedia solusi IT terdepan di Indonesia.</p>
                </div>
            </div>
            <div class="timeline-item" data-aos="fade-up" data-aos-delay="100">
                <div class="timeline-marker"></div>
                <div class="timeline-content">
                    <div class="timeline-year">2020</div>
                    <h5 class="timeline-title">Ekspansi Layanan CCTV</h5>
                    <p class="timeline-desc">Mengembangkan divisi khusus untuk layanan keamanan dan pengawasan dengan teknologi CCTV terdepan.</p>
                </div>
            </div>
            <div class="timeline-item" data-aos="fade-up" data-aos-delay="200">
                <div class="timeline-marker"></div>
                <div class="timeline-content">
                    <div class="timeline-year">2022</div>
                    <h5 class="timeline-title">Partnership Strategis</h5>
                    <p class="timeline-desc">Menjalin kemitraan dengan berbagai vendor teknologi terkemuka untuk memperluas portfolio produk dan layanan.</p>
                </div>
            </div>
            <div class="timeline-item" data-aos="fade-up" data-aos-delay="300">
                <div class="timeline-marker"></div>
                <div class="timeline-content">
                    <div class="timeline-year">2024</div>
                    <h5 class="timeline-title">Digital Transformation</h5>
                    <p class="timeline-desc">Meluncurkan layanan konsultasi digital transformation untuk membantu bisnis beradaptasi dengan era digital.</p>
                </div>
            </div>
            <div class="timeline-item" data-aos="fade-up" data-aos-delay="400">
                <div class="timeline-marker"></div>
                <div class="timeline-content">
                    <div class="timeline-year">2025</div>
                    <h5 class="timeline-title">Inovasi Berkelanjutan</h5>
                    <p class="timeline-desc">Terus berinovasi dan mengembangkan solusi teknologi terbaru untuk mendukung pertumbuhan bisnis klien.</p>
                </div>
            </div>
        </div>
    </div>
</section>
<!-- Team Section -->
<?php if (!empty($team_members)): ?>
    <section class="team-section">
        <div class="container">
            <div class="section-title" data-aos="fade-up">
                <h2>Tim Profesional Kami</h2>
                <p class="section-subtitle">Didukung oleh tim ahli yang berpengalaman dan bersertifikat di bidangnya</p>
            </div>
            <div class="row g-4">
                <?php foreach ($team_members as $index => $member): ?>
                    <div class="col-lg-3 col-md-6" data-aos="fade-up" data-aos-delay="<?php echo $index * 100; ?>">
                        <div class="team-card">
                            <div class="team-avatar">
                                <?php if ($member['image_path']): ?>
                                    <img src="admin/uploads/team/<?php echo htmlspecialchars($member['image_path']); ?>" alt="<?php echo htmlspecialchars($member['name']); ?>">
                                <?php else: ?>
                                    <div class="d-flex align-items-center justify-content-center h-100 bg-light">
                                        <i class="fas fa-user text-muted" style="font-size: 3rem;"></i>
                                    </div>
                                <?php endif; ?>
                            </div>
                            <h5 class="team-name"><?php echo htmlspecialchars($member['name']); ?></h5>
                            <p class="team-position"><?php echo htmlspecialchars($member['position']); ?></p>
                            <div class="team-social">
                                <a href="#" class="social-btn"><i class="fab fa-linkedin-in"></i></a>
                                <a href="#" class="social-btn"><i class="fab fa-twitter"></i></a>
                                <a href="#" class="social-btn"><i class="fas fa-envelope"></i></a>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    </section>
<?php endif; ?>
<!-- Call to Action -->
<section class="cta-section">
    <div class="container">
        <div class="cta-content text-center" data-aos="fade-up">
            <h2 class="cta-title">Siap Bermitra dengan Kami?</h2>
            <p class="cta-text">Mari bergabung dengan ratusan klien yang telah mempercayakan kebutuhan teknologi mereka kepada kami</p>
            <div class="d-flex gap-3 justify-content-center flex-wrap">
                <a href="kontak.php" class="btn btn-hero-primary btn-lg">
                    <i class="fas fa-handshake me-2"></i>Konsultasi Gratis
                </a>
                <a href="produk.php" class="btn btn-hero-secondary btn-lg">
                    <i class="fas fa-shopping-cart me-2"></i>Lihat Produk
                </a>
            </div>
        </div>
    </div>
</section>
<?php include 'includes/footer.php'; ?>