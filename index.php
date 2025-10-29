<?php
$page_title = "Beranda";
include 'includes/header.php';
// Get banner data
$db = getDB();
$stmt = $db->prepare("SELECT * FROM banners WHERE is_active = 1 ORDER BY id DESC LIMIT 1");
$stmt->execute();
$banner = $stmt->fetch();

// Get about info
$about_title = getSiteSetting('about_title') ?: 'Tentang Kami';
$about_text = getSiteSetting('about_text') ?: 'Kami adalah partner teknologi terpercaya yang menyediakan solusi IT komprehensif untuk mendukung transformasi digital bisnis Anda.';
$about_features = getSiteSetting('about_features') ?: [
    'Pengalaman lebih dari 10 tahun di industri IT',
    'Tim profesional bersertifikat internasional',
    'Layanan 24/7 support dan maintenance',
    'Teknologi terdepan dan solusi inovatif'
];

// Get featured products (limit to 3)
$stmt = $db->prepare("SELECT p.*, pc.name as category_name FROM products p 
                     LEFT JOIN product_categories pc ON p.category_id = pc.id 
                     ORDER BY RAND() LIMIT 3");
$stmt->execute();
$products = $stmt->fetchAll();

// Get testimonials
$stmt = $db->prepare("SELECT * FROM testimonials WHERE is_active = 1 ORDER BY id ASC");
$stmt->execute();
$testimonials = $stmt->fetchAll();

// Get recent articles
$stmt = $db->prepare("SELECT a.*, u.username as author_name 
                     FROM articles a 
                     LEFT JOIN users u ON a.author_id = u.id 
                     WHERE a.status = 'published' 
                     ORDER BY a.created_at DESC LIMIT 3");
$stmt->execute();
$articles = $stmt->fetchAll();

// Get CTA data
$cta_title = getSiteSetting('cta_title') ?: 'Siap Memulai Transformasi Digital?';
$cta_text = getSiteSetting('cta_text') ?: 'Hubungi kami sekarang untuk konsultasi gratis dan temukan solusi IT yang tepat untuk bisnis Anda.';
$cta_button_text = getSiteSetting('cta_button_text') ?: 'Hubungi Kami Sekarang';
$cta_button_link = getSiteSetting('cta_button_link') ?: 'kontak.php';
?>

<!-- Hero Section -->
<section class="hero-section">
    <div class="container-fluid p-0">
        <div class="row g-0 h-100">
            <div class="col-lg-5 h-100">
                <div class="hero-left h-100">
                    <div class="floating-elements">
                        <i class="fas fa-server floating-icon" style="font-size: 3rem;"></i>
                        <i class="fas fa-video floating-icon" style="font-size: 3rem;"></i>
                        <i class="fas fa-shield-alt floating-icon" style="font-size: 2.5rem;"></i>
                    </div>
                    <div class="container h-100 d-flex align-items-center">
                        <div class="hero-content" data-aos="fade-right">
                            <h1 class="hero-title"><?php echo htmlspecialchars($banner['title'] ?? 'Solusi IT Profesional untuk Bisnis Anda'); ?></h1>
                            <p class="hero-subtitle"><?php echo htmlspecialchars($banner['subtitle'] ?? 'Dari pengadaan perangkat hingga pemeliharaan sistem, kami adalah partner teknologi yang dapat Anda andalkan.'); ?></p>
                            <div class="hero-buttons">
                                <a href="<?php echo htmlspecialchars($banner['button_link'] ?? '#services'); ?>" class="btn btn-hero-primary btn-lg">
                                    <i class="fas fa-rocket me-2"></i>
                                    <?php echo htmlspecialchars($banner['button_text'] ?? 'Jelajahi Layanan'); ?>
                                </a>
                                <a href="kontak.php" class="btn btn-hero-secondary btn-lg">
                                    <i class="fas fa-phone me-2"></i>Hubungi Kami
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-lg-7 h-100">
                <div class="hero-right h-100" data-aos="fade-left">
                    <?php if (!empty($banner['image_path'])): ?>
                        <img src="admin/uploads/banners/<?php echo htmlspecialchars($banner['image_path']); ?>" alt="IT Solutions">
                    <?php else: ?>
                        <img src="assets/img/hero-tech.jpg" alt="IT Solutions">
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- About Section -->
<section class="about-section" id="about">
    <div class="container">
        <div class="section-title" data-aos="fade-up">
            <h2>Keunggulan Kami</h2>
            <p class="section-subtitle">Pengalaman dan dedikasi kami dalam membangun infrastruktur teknologi terdepan</p>
        </div>

        <div class="row g-4">
            <!-- Keunggulan 1: Instalasi Jaringan Desa -->
            <div class="col-lg-4 col-md-6" data-aos="fade-up" data-aos-delay="100">
                <div class="achievement-card village-card">
                    <div class="achievement-icon">
                        <div class="icon-wrapper">
                            <i class="fas fa-map-marked-alt"></i>
                        </div>
                        <div class="icon-decoration">
                            <div class="decoration-circle"></div>
                            <div class="decoration-circle"></div>
                            <div class="decoration-circle"></div>
                        </div>
                    </div>
                    <div class="achievement-content">
                        <div class="achievement-number">20++</div>
                        <div class="achievement-label">Desa Terjangkau</div>
                        <p class="achievement-description">
                            Instalasi jaringan internet berkualitas tinggi di lebih dari 20 desa,
                            menghubungkan komunitas rural dengan teknologi modern.
                        </p>
                        <div class="achievement-link">
                            <span>Lihat Jangkauan</span>
                            <i class="fas fa-arrow-right"></i>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Keunggulan 2: Instalasi Rumah Tangga -->
            <div class="col-lg-4 col-md-6" data-aos="fade-up" data-aos-delay="200">
                <div class="achievement-card household-card">
                    <div class="achievement-icon">
                        <div class="icon-wrapper">
                            <i class="fas fa-home"></i>
                        </div>
                        <div class="icon-decoration">
                            <div class="decoration-circle"></div>
                            <div class="decoration-circle"></div>
                            <div class="decoration-circle"></div>
                        </div>
                    </div>
                    <div class="achievement-content">
                        <div class="achievement-number">200+</div>
                        <div class="achievement-label">Rumah Tangga</div>
                        <p class="achievement-description">
                            Instalasi jaringan internet rumahan dengan layanan maintenance
                            berkualitas untuk lebih dari 200 keluarga di berbagai wilayah.
                        </p>
                        <div class="achievement-link">
                            <span>Lihat Testimoni</span>
                            <i class="fas fa-arrow-right"></i>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Keunggulan 3: Kabel Fiber Optik -->
            <div class="col-lg-4 col-md-6" data-aos="fade-up" data-aos-delay="300">
                <div class="achievement-card fiber-card">
                    <div class="achievement-icon">
                        <div class="icon-wrapper">
                            <i class="fas fa-ethernet"></i>
                        </div>
                        <div class="icon-decoration">
                            <div class="decoration-circle"></div>
                            <div class="decoration-circle"></div>
                            <div class="decoration-circle"></div>
                        </div>
                    </div>
                    <div class="achievement-content">
                        <div class="achievement-number">100 KM</div>
                        <div class="achievement-label">Kabel Fiber Optik</div>
                        <p class="achievement-description">
                            Pemasangan infrastruktur fiber optik sepanjang 100 kilometer,
                            memastikan koneksi internet super cepat dan stabil.
                        </p>
                        <div class="achievement-link">
                            <span>Lihat Teknologi</span>
                            <i class="fas fa-arrow-right"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="text-center mt-5" data-aos="fade-up" data-aos-delay="400">
            <a href="tentang.php" class="btn btn-primary btn-lg">
                <i class="fas fa-info-circle me-2"></i>Selengkapnya Tentang Kami
            </a>
        </div>
    </div>
</section>

<!-- Services Section -->
<section class="services-section" id="services">
    <div class="container">
        <div class="section-title" data-aos="fade-up">
            <h2>Layanan Unggulan Kami</h2>
            <p class="section-subtitle">Solusi teknologi lengkap untuk mendukung kebutuhan personal dan bisnis Anda</p>
        </div>

        <!-- Service Layout 1: Pengadaan -->
        <div class="service-layout" data-aos="fade-up" data-aos-delay="100">
            <div class="row align-items-center g-4">
                <div class="col-lg-6">
                    <div class="service-image-container modern-border">
                        <img src="assets/img/team-page-title-bg.jpg" alt="Pengadaan Teknologi" class="img-fluid">
                        <div class="border-decoration">
                            <div class="corner-element"></div>
                            <div class="corner-element"></div>
                            <div class="corner-element"></div>
                            <div class="corner-element"></div>
                            <div class="border-particle"></div>
                            <div class="border-particle"></div>
                            <div class="border-particle"></div>
                            <div class="border-particle"></div>
                            <div class="border-particle"></div>
                            <div class="border-particle"></div>
                        </div>
                    </div>
                </div>
                <div class="col-lg-6">
                    <div class="service-content">
                        <div class="service-category">Pengadaan</div>
                        <h3 class="service-main-title">Solusi Teknologi Terkini</h3>
                        <p class="service-description">
                            Menyediakan perangkat teknologi berkualitas untuk kebutuhan personal dan bisnis. Dari wifi rumahan hingga peralatan kantor modern, kami hadirkan solusi lengkap dengan garansi keaslian dan performa terbaik.
                        </p>
                        <div class="sub-services-grid">
                            <div class="sub-service-card" data-aos="fade-up" data-aos-delay="200">
                                <div class="sub-service-header">
                                    <div class="sub-service-icon">
                                        <i class="bi bi-building"></i>
                                    </div>
                                    <h4 class="sub-service-name">Business</h4>
                                </div>
                                <div class="partner-brands">
                                    <div class="brand-logo">
                                        <i class="bi bi-fingerprint brand-icon"></i>
                                    </div>
                                    <div class="brand-logo">
                                        <i class="bi bi-cpu brand-icon"></i>
                                    </div>
                                    <div class="brand-logo">
                                        <i class="bi bi-hdd-network brand-icon"></i>
                                    </div>
                                </div>
                            </div>
                            <div class="sub-service-card" data-aos="fade-up" data-aos-delay="300">
                                <div class="sub-service-header">
                                    <div class="sub-service-icon">
                                        <i class="bi bi-person"></i>
                                    </div>
                                    <h4 class="sub-service-name">Personal</h4>
                                </div>
                                <div class="partner-brands">
                                    <div class="brand-logo">
                                        <i class="bi bi-router brand-icon"></i>
                                    </div>
                                    <div class="brand-logo">
                                        <i class="bi bi-laptop brand-icon"></i>
                                    </div>
                                    <div class="brand-logo">
                                        <i class="bi bi-phone brand-icon"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="service-action">
                            <a href="layanan.php?slug=pengadaan" class="btn-service-detail">
                                Lihat Selengkapnya <i class="bi bi-arrow-right"></i>
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Service Layout 2: Perawatan -->
        <div class="service-layout" data-aos="fade-up" data-aos-delay="200">
            <div class="row align-items-center g-4">
                <div class="col-lg-6 order-lg-2">
                    <div class="service-image-container modern-border">
                        <img src="assets/img/contact-hero-bg.jpg" alt="Perawatan Teknologi" class="img-fluid">
                        <div class="border-decoration">
                            <div class="corner-element"></div>
                            <div class="corner-element"></div>
                            <div class="corner-element"></div>
                            <div class="corner-element"></div>
                            <div class="border-particle"></div>
                            <div class="border-particle"></div>
                            <div class="border-particle"></div>
                            <div class="border-particle"></div>
                            <div class="border-particle"></div>
                            <div class="border-particle"></div>
                        </div>
                    </div>
                </div>
                <div class="col-lg-6 order-lg-1">
                    <div class="service-content">
                        <div class="service-category">Perawatan</div>
                        <h3 class="service-main-title">Pemeliharaan Profesional</h3>
                        <p class="service-description">
                            Layanan perawatan berkala dan perbaikan untuk menjaga performa optimal perangkat teknologi Anda. Tersedia dalam paket kontrak untuk bisnis atau layanan sesuai kebutuhan personal.
                        </p>
                        <div class="sub-services-grid">
                            <div class="sub-service-card" data-aos="fade-up" data-aos-delay="300">
                                <div class="sub-service-header">
                                    <div class="sub-service-icon">
                                        <i class="bi bi-file-earmark-text"></i>
                                    </div>
                                    <h4 class="sub-service-name">Kontrak</h4>
                                </div>
                                <div class="partner-brands">
                                    <div class="brand-logo">
                                        <i class="bi bi-calendar-check brand-icon"></i>
                                    </div>
                                    <div class="brand-logo">
                                        <i class="bi bi-clipboard-check brand-icon"></i>
                                    </div>
                                    <div class="brand-logo">
                                        <i class="bi bi-shield-check brand-icon"></i>
                                    </div>
                                </div>
                            </div>
                            <div class="sub-service-card" data-aos="fade-up" data-aos-delay="400">
                                <div class="sub-service-header">
                                    <div class="sub-service-icon">
                                        <i class="bi bi-tools"></i>
                                    </div>
                                    <h4 class="sub-service-name">Layanan</h4>
                                </div>
                                <div class="partner-brands">
                                    <div class="brand-logo">
                                        <i class="bi bi-wrench brand-icon"></i>
                                    </div>
                                    <div class="brand-logo">
                                        <i class="bi bi-gear brand-icon"></i>
                                    </div>
                                    <div class="brand-logo">
                                        <i class="bi bi-hammer brand-icon"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="service-action">
                            <a href="layanan.php?slug=perawatan" class="btn-service-detail">
                                Lihat Selengkapnya <i class="bi bi-arrow-right"></i>
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Products Section -->
<section class="products-section" id="products">
    <div class="container">
        <div class="section-title" data-aos="fade-up">
            <h2>Produk Unggulan</h2>
            <p class="section-subtitle">Pilihan produk berkualitas tinggi untuk mendukung kebutuhan teknologi bisnis Anda</p>
        </div>
        <div class="row g-4">
            <?php foreach ($products as $index => $product): ?>
                <div class="col-lg-4 col-md-6" data-aos="fade-up" data-aos-delay="<?php echo $index * 100; ?>">
                    <div class="product-card">
                        <div class="product-image">
                            <?php if (!empty($product['image_path'])): ?>
                                <img src="admin/uploads/products/<?php echo htmlspecialchars($product['image_path']); ?>" alt="<?php echo htmlspecialchars($product['name']); ?>">
                            <?php else: ?>
                                <i class="fas fa-image text-muted" style="font-size: 3rem;"></i>
                            <?php endif; ?>
                        </div>
                        <div class="product-info">
                            <div class="product-category"><?php echo htmlspecialchars($product['category_name'] ?? 'Uncategorized'); ?></div>
                            <h5><?php echo htmlspecialchars($product['name']); ?></h5>
                            <p class="product-description"><?php echo htmlspecialchars(truncateText($product['description'], 100)); ?></p>
                            <a href="produk-detail.php?id=<?php echo $product['id']; ?>" class="btn btn-outline-primary">
                                <i class="fas fa-eye me-2"></i>Lihat Detail
                            </a>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
        <div class="text-center mt-5" data-aos="fade-up">
            <a href="produk.php" class="btn btn-primary btn-lg">
                <i class="fas fa-th-large me-2"></i>Lihat Semua Produk
            </a>
        </div>
    </div>
</section>

<!-- Testimonials Section -->
<?php if (!empty($testimonials)): ?>
    <section class="testimonials-section" id="testimonials">
        <div class="container">
            <div class="section-title" data-aos="fade-up">
                <h2>Apa Kata Klien Kami</h2>
                <p class="section-subtitle">Kepercayaan dan kepuasan klien adalah prioritas utama kami</p>
            </div>
            <div class="testimonial-slider" data-aos="fade-up">
                <div class="swiper testimonial-swiper">
                    <div class="swiper-wrapper">
                        <?php foreach ($testimonials as $testimonial): ?>
                            <div class="swiper-slide testimonial-slide">
                                <div class="testimonial-card">
                                    <div class="testimonial-avatar">
                                        <?php if (!empty($testimonial['image_path'])): ?>
                                            <img src="admin/uploads/testimonials/<?php echo htmlspecialchars($testimonial['image_path']); ?>" alt="<?php echo htmlspecialchars($testimonial['client_name']); ?>">
                                        <?php else: ?>
                                            <img src="assets/img/avatar-placeholder.jpg" alt="<?php echo htmlspecialchars($testimonial['client_name']); ?>">
                                        <?php endif; ?>
                                    </div>
                                    <p class="testimonial-quote"><?php echo htmlspecialchars($testimonial['quote']); ?></p>
                                    <h6 class="testimonial-name"><?php echo htmlspecialchars($testimonial['client_name']); ?></h6>
                                    <p class="testimonial-position"><?php echo htmlspecialchars($testimonial['client_position']); ?></p>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                    <div class="testimonial-pagination"></div>
                </div>
            </div>
        </div>
    </section>
<?php endif; ?>

<!-- Blog Section -->
<?php if (!empty($articles)): ?>
    <section class="blog-section" id="blog">
        <div class="container">
            <div class="section-title" data-aos="fade-up">
                <h2>Artikel & Berita Terbaru</h2>
                <p class="section-subtitle">Informasi terkini seputar teknologi dan tips untuk bisnis Anda</p>
            </div>
            <div class="row g-4">
                <?php foreach ($articles as $index => $article): ?>
                    <div class="col-lg-4" data-aos="fade-up" data-aos-delay="<?php echo $index * 100; ?>">
                        <div class="blog-card">
                            <div class="blog-image">
                                <?php if (!empty($article['image_path'])): ?>
                                    <img src="admin/uploads/articles/<?php echo htmlspecialchars($article['image_path']); ?>" alt="<?php echo htmlspecialchars($article['title']); ?>">
                                <?php else: ?>
                                    <div class="d-flex align-items-center justify-content-center h-100 bg-light">
                                        <i class="fas fa-newspaper text-muted" style="font-size: 3rem;"></i>
                                    </div>
                                <?php endif; ?>
                            </div>
                            <div class="blog-content">
                                <div class="blog-date"><?php echo formatTanggal($article['created_at']); ?></div>
                                <h5><?php echo htmlspecialchars($article['title']); ?></h5>
                                <p class="blog-excerpt"><?php echo htmlspecialchars(truncateText($article['excerpt'] ?? $article['content'], 120)); ?></p>
                                <a href="artikel-detail.php?slug=<?php echo htmlspecialchars($article['slug']); ?>" class="read-more">
                                    Baca Selengkapnya <i class="fas fa-arrow-right ms-1"></i>
                                </a>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
            <div class="text-center mt-5" data-aos="fade-up">
                <a href="artikel.php" class="btn btn-primary btn-lg">
                    <i class="fas fa-newspaper me-2"></i>Lihat Semua Artikel
                </a>
            </div>
        </div>
    </section>
<?php endif; ?>

<!-- CTA Section -->
<section class="cta-section">
    <div class="container">
        <div class="cta-content" data-aos="fade-up">
            <h2 class="cta-title"><?php echo htmlspecialchars($cta_title); ?></h2>
            <p class="cta-text"><?php echo htmlspecialchars($cta_text); ?></p>
            <a href="<?php echo htmlspecialchars($cta_button_link); ?>" class="btn btn-hero-primary btn-lg">
                <i class="fas fa-phone me-2"></i><?php echo htmlspecialchars($cta_button_text); ?>
            </a>
        </div>
    </div>
</section>

<?php include 'includes/footer.php'; ?>