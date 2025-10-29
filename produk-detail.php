<?php
require_once 'config/db.php';
// Get database connection
$db = getDB();
// Get product ID from URL
$product_id = isset($_GET['id']) ? intval($_GET['id']) : 0;
if ($product_id <= 0) {
    header('Location: produk.php');
    exit();
}
// Fetch product details
try {
    $stmt = $db->prepare("
        SELECT p.*, pc.name as category_name 
        FROM products p 
        LEFT JOIN product_categories pc ON p.category_id = pc.id 
        WHERE p.id = ?
    ");
    $stmt->execute([$product_id]);
    $product = $stmt->fetch();
    if (!$product) {
        header('Location: produk.php');
        exit();
    }
    // Fetch related products (same category)
    $stmt = $db->prepare("
        SELECT * FROM products 
        WHERE category_id = ? AND id != ? 
        ORDER BY RAND() 
        LIMIT 4
    ");
    $stmt->execute([$product['category_id'], $product_id]);
    $related_products = $stmt->fetchAll();
} catch (PDOException $e) {
    $error = "Error fetching product details: " . $e->getMessage();
}
// Ambil settings WhatsApp dari database
try {
    $stmt = $db->prepare("SELECT setting_key, setting_value FROM site_settings WHERE setting_key IN ('contact_whatsapp', 'whatsapp_message_template')");
    $stmt->execute();
    $whatsapp_settings = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);
} catch (PDOException $e) {
    $whatsapp_settings = [];
}

// Set default values
$whatsapp_number = $whatsapp_settings['contact_whatsapp'] ?? '+6221123456789';
$whatsapp_template = $whatsapp_settings['whatsapp_message_template'] ?? 'Halo, saya ingin bertanya tentang layanan PT Sarana Sentra Teknologi Utama';
$page_title = $product['name'] ?? 'Produk Tidak Ditemukan';
include 'includes/header.php';
?>
<style>
    /* === DESAIN HERO-SECTION */
    .page-hero {
        background: url("assets/img/products-hero-bg.jpg") center/cover no-repeat;
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
        max-width: 800px;
        /* Diperlebar agar sesuai judul produk */
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
        transition: color 0.3s ease;
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

    /* === DESAIN HERO-SECTION BARU END === */

    .product-detail {
        padding: 80px 0;
    }

    .product-image-container {
        position: relative;
        width: 100%;
        background: #f8f9fa;
        border-radius: 10px;
        overflow: hidden;
        box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
    }

    .product-image {
        width: 100%;
        height: auto;
        display: block;
        border-radius: 10px;
    }

    .product-image-zoom {
        position: relative;
        overflow: hidden;
        cursor: zoom-in;
        border-radius: 10px;
    }

    .product-image-zoom img {
        transition: transform 0.5s ease;
        width: 100%;
        height: auto;
        display: block;
    }

    .product-image-zoom:hover img {
        transform: scale(1.05);
    }

    .no-image {
        height: 400px;
        border: 2px dashed #dee2e6;
        display: flex;
        align-items: center;
        justify-content: center;
        background: #f8f9fa;
        border-radius: 10px;
    }

    .product-title {
        color: #2c4964;
        margin-bottom: 1rem;
        -webkit-font-smoothing: antialiased;
        -moz-osx-font-smoothing: grayscale;
    }

    .product-meta .badge {
        padding: 0.5rem 0.75rem;
        font-size: 0.875rem;
    }

    .product-tags .badge {
        background-color: transparent !important;
        border: 1px solid #dee2e6;
        color: #6c757d;
    }

    .product-actions .btn {
        min-width: 180px;
    }

    .related-products {
        padding: 80px 0;
    }

    .product-card {
        border: none;
        box-shadow: 0 0 30px rgba(0, 0, 0, 0.1);
        transition: all 0.3s ease;
    }

    .product-card:hover {
        transform: translateY(-5px);
        box-shadow: 0 5px 40px rgba(0, 0, 0, 0.15);
    }

    .product-card .card-img-top {
        height: 200px;
        object-fit: cover;
    }

    /* Lightbox Styles */
    .lightbox {
        display: none;
        position: fixed;
        z-index: 1000;
        left: 0;
        top: 0;
        width: 100%;
        height: 100%;
        background-color: rgba(0, 0, 0, 0.9);
        overflow: auto;
    }

    .lightbox-content {
        position: relative;
        margin: auto;
        display: block;
        max-width: 90%;
        max-height: 90%;
        margin-top: 40px;
    }

    .close-lightbox {
        position: absolute;
        top: 15px;
        right: 35px;
        color: #f1f1f1;
        font-size: 40px;
        font-weight: bold;
        cursor: pointer;
    }

    .close-lightbox:hover {
        color: #47b2e4;
    }

    /* Call to Action Section */
    .cta-section {
        position: relative;
        padding: 6rem 0;
        overflow: hidden;
        background: linear-gradient(135deg, var(--primary-dark) 0%, var(--primary-color) 100%);
    }

    .cta-section::before {
        content: '';
        position: absolute;
        top: 0;
        left: 0;
        right: 0;
        bottom: 0;
        background: url('../assets/img/about-page-title-bg.jpg') center/cover no-repeat;
        opacity: 0.15;
        z-index: 1;
    }

    .cta-section::after {
        content: '';
        position: absolute;
        top: 0;
        left: 0;
        right: 0;
        bottom: 0;
        background: linear-gradient(135deg, rgba(0, 0, 0, 0.7) 0%, rgba(0, 0, 0, 0.3) 100%);
        z-index: 2;
    }

    .cta-content {
        position: relative;
        z-index: 3;
        color: white;
        max-width: 800px;
        margin: 0 auto;
        text-align: center;
    }

    .cta-title {
        font-size: 2.5rem;
        font-weight: 700;
        margin-bottom: 1.5rem;
        color: white;
        -webkit-font-smoothing: antialiased;
        -moz-osx-font-smoothing: grayscale;
    }

    .cta-text {
        font-size: 1.25rem;
        margin-bottom: 2rem;
        opacity: 0.9;
    }

    .cta-buttons {
        display: flex;
        gap: 1rem;
        justify-content: center;
        flex-wrap: wrap;
    }

    .btn-cta-primary {
        background-color: var(--accent-color);
        border-color: var(--accent-color);
        color: white;
        transition: all 0.3s ease;
        padding: 0.75rem 1.5rem;
        border-radius: 50px;
        font-weight: 600;
        text-decoration: none;
    }

    .btn-cta-primary:hover {
        background-color: var(--accent-dark);
        border-color: var(--accent-dark);
        transform: translateY(-3px);
        box-shadow: 0 10px 20px rgba(0, 0, 0, 0.2);
    }

    .btn-cta-secondary {
        background-color: transparent;
        border: 2px solid white;
        color: white;
        transition: all 0.3s ease;
        padding: 0.75rem 1.5rem;
        border-radius: 50px;
        font-weight: 600;
        text-decoration: none;
    }

    .btn-cta-secondary:hover {
        background-color: rgba(255, 255, 255, 0.1);
        transform: translateY(-3px);
        box-shadow: 0 10px 20px rgba(0, 0, 0, 0.2);
    }

    @media (max-width: 768px) {
        .page-hero h1 {
            font-size: 2.2rem;
        }

        .page-hero p {
            font-size: 1rem;
        }

        .product-actions {
            text-align: center;
        }

        .product-actions .btn {
            width: 100%;
            margin-bottom: 10px;
        }

        .product-actions .me-3 {
            margin-right: 0 !important;
        }

        .thumbnail {
            width: 60px;
            height: 60px;
        }

        .cta-title {
            font-size: 2rem;
        }

        .cta-text {
            font-size: 1.1rem;
        }

        .cta-buttons {
            flex-direction: column;
            align-items: center;
        }

        .btn-cta-primary,
        .btn-cta-secondary {
            width: 100%;
            max-width: 300px;
        }
    }
</style>
<main class="main">
    <section class="page-hero">
        <div class="container">
            <div class="page-hero-content" data-aos="fade-up">
                <h1><?php echo htmlspecialchars($product['name']); ?></h1>
                <p>Detail spesifikasi dan informasi lengkap produk</p>
                <nav class="breadcrumb-nav">
                    <ol class="breadcrumb">
                        <li class="breadcrumb-item"><a href="index.php">Beranda</a></li>
                        <li class="breadcrumb-item"><a href="produk.php">Produk</a></li>
                        <li class="breadcrumb-item active"><?php echo htmlspecialchars($product['name']); ?></li>
                    </ol>
                </nav>
            </div>
        </div>
    </section>
    <section id="product-detail" class="product-detail section">
        <div class="container">
            <div class="row gy-4">
                <div class="col-lg-6">
                    <div class="product-image-container">
                        <?php if (!empty($product['image_path']) && file_exists('admin/uploads/products/' . $product['image_path'])): ?>
                            <div class="product-image-zoom" onclick="openLightbox()">
                                <img src="admin/uploads/products/<?php echo htmlspecialchars($product['image_path']); ?>"
                                    alt="<?php echo htmlspecialchars($product['name']); ?>"
                                    class="product-image"
                                    id="main-product-image">
                            </div>
                        <?php else: ?>
                            <div class="no-image">
                                <i class="bi bi-image fs-1 text-muted"></i>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>

                <div class="col-lg-6">
                    <div class="product-info">
                        <h2 class="product-title"><?php echo htmlspecialchars($product['name']); ?></h2>
                        <div class="product-meta mb-4">
                            <span class="badge bg-primary me-2">
                                <i class="bi bi-tag-fill me-1"></i>
                                <?php echo htmlspecialchars($product['category_name']); ?>
                            </span>
                            <span class="badge bg-success">
                                <i class="bi bi-check-circle-fill me-1"></i>
                                Stok: <?php echo $product['stock']; ?> unit
                            </span>
                        </div>

                        <?php if (!empty($product['tags'])): ?>
                            <div class="product-tags mb-4">
                                <strong>Tags: </strong>
                                <?php
                                $tags = explode(',', $product['tags']);
                                foreach ($tags as $tag):
                                    $tag = trim($tag);
                                    if (!empty($tag)):
                                ?>
                                        <span class="badge bg-outline-secondary me-1"><?php echo htmlspecialchars($tag); ?></span>
                                <?php
                                    endif;
                                endforeach;
                                ?>
                            </div>
                        <?php endif; ?>

                        <div class="product-description mb-4">
                            <h5>Deskripsi Produk</h5>
                            <p><?php echo nl2br(htmlspecialchars($product['description'])); ?></p>
                        </div>

                        <div class="product-actions">
                            <?php
                            $formatted_whatsapp = preg_replace('/[^\d+]/', '', $whatsapp_number);
                            $quotation_message = "Halo, saya ingin meminta penawaran resmi untuk produk:\n\n*" . $product['name'] . "*\n\nMohon dikirimkan detail harga, spesifikasi lengkap, dan syarat pembelian. Terima kasih.";
                            $whatsapp_quotation_url = "https://wa.me/" . $formatted_whatsapp . "?text=" . urlencode($quotation_message);
                            ?>
                            <a href="<?php echo $whatsapp_quotation_url; ?>" target="_blank"
                                class="btn btn-primary btn-lg me-3">
                                <i class="bi bi-whatsapp me-2"></i>
                                Minta Penawaran
                            </a>
                            <?php
                            $formatted_whatsapp = preg_replace('/[^\d+]/', '', $whatsapp_number);
                            $product_inquiry_message = "Halo, saya tertarik dengan produk *" . $product['name'] . "* dan ingin mendapatkan informasi lebih lanjut mengenai spesifikasi dan harga.";
                            $whatsapp_product_url = "https://wa.me/" . $formatted_whatsapp . "?text=" . urlencode($product_inquiry_message);
                            ?>
                            <a href="<?php echo $whatsapp_product_url; ?>"
                                target="_blank"
                                class="btn btn-success btn-lg">
                                <i class="bi bi-whatsapp me-2"></i>
                                WhatsApp
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <?php if (!empty($related_products)): ?>
        <section id="related-products" class="related-products section light-background">
            <div class="container">
                <div class="section-title" data-aos="fade-up">
                    <h2>Produk Serupa</h2>
                    <p>Produk lain yang mungkin Anda minati</p>
                </div>
                <div class="row gy-4">
                    <?php foreach ($related_products as $related): ?>
                        <div class="col-lg-3 col-md-6" data-aos="fade-up" data-aos-delay="100">
                            <div class="card product-card h-100">
                                <?php if (!empty($related['image_path']) && file_exists('admin/uploads/products/' . $related['image_path'])): ?>
                                    <img src="admin/uploads/products/<?php echo htmlspecialchars($related['image_path']); ?>"
                                        class="card-img-top" alt="<?php echo htmlspecialchars($related['name']); ?>">
                                <?php else: ?>
                                    <div class="card-img-top d-flex align-items-center justify-content-center bg-light" style="height: 200px;">
                                        <i class="bi bi-image fs-1 text-muted"></i>
                                    </div>
                                <?php endif; ?>
                                <div class="card-body d-flex flex-column">
                                    <h5 class="card-title"><?php echo htmlspecialchars($related['name']); ?></h5>
                                    <p class="card-text flex-grow-1">
                                        <?php echo htmlspecialchars(substr($related['description'], 0, 100)) . '...'; ?>
                                    </p>
                                    <div class="mt-auto">
                                        <a href="produk-detail.php?id=<?php echo $related['id']; ?>"
                                            class="btn btn-outline-primary w-100">Lihat Detail</a>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </section>
    <?php endif; ?>
</main>

<div id="imageLightbox" class="lightbox">
    <span class="close-lightbox" onclick="closeLightbox()">&times;</span>
    <img class="lightbox-content" id="lightboxImage">
</div>

<script>
    // Function to change main image when thumbnail is clicked
    function changeImage(thumbnail) {
        // Remove active class from all thumbnails
        const thumbnails = document.querySelectorAll('.thumbnail');
        thumbnails.forEach(thumb => thumb.classList.remove('active'));

        // Add active class to clicked thumbnail
        thumbnail.classList.add('active');

        // Change main image source
        const mainImage = document.getElementById('main-product-image');
        mainImage.src = thumbnail.src;
    }

    // Function to open lightbox
    function openLightbox() {
        const mainImage = document.getElementById('main-product-image');
        const lightbox = document.getElementById('imageLightbox');
        const lightboxImage = document.getElementById('lightboxImage');

        lightboxImage.src = mainImage.src;
        lightbox.style.display = 'block';
    }

    // Function to close lightbox
    function closeLightbox() {
        const lightbox = document.getElementById('imageLightbox');
        lightbox.style.display = 'none';
    }

    // Close lightbox when clicking outside the image
    window.onclick = function(event) {
        const lightbox = document.getElementById('imageLightbox');
        if (event.target == lightbox) {
            lightbox.style.display = 'none';
        }
    }
</script>

<?php include 'includes/footer.php'; ?>