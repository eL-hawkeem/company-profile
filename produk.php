<?php
$page_title = "Produk";
include 'includes/header.php';
$db = getDB();
// Get filter parameters
$category_filter = isset($_GET['category']) ? $_GET['category'] : '';
$search = isset($_GET['search']) ? trim($_GET['search']) : '';
// Pagination
$items_per_page = 12;
$page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$offset = ($page - 1) * $items_per_page;
// Build query
$where_conditions = [];
$params = [];
if ($category_filter) {
    $where_conditions[] = "p.category_id = ?";
    $params[] = $category_filter;
}
if ($search) {
    $where_conditions[] = "(p.name LIKE ? OR p.description LIKE ? OR p.tags LIKE ?)";
    $params[] = "%$search%";
    $params[] = "%$search%";
    $params[] = "%$search%";
}
$where_clause = !empty($where_conditions) ? "WHERE " . implode(" AND ", $where_conditions) : "";
// Get total count
$count_sql = "SELECT COUNT(*) FROM products p 
              LEFT JOIN product_categories pc ON p.category_id = pc.id 
              $where_clause";
$stmt = $db->prepare($count_sql);
$stmt->execute($params);
$total_items = $stmt->fetchColumn();
$total_pages = ceil($total_items / $items_per_page);
// Get products
$sql = "SELECT p.*, pc.name as category_name 
        FROM products p 
        LEFT JOIN product_categories pc ON p.category_id = pc.id 
        $where_clause 
        ORDER BY p.created_at DESC 
        LIMIT $items_per_page OFFSET $offset";
$stmt = $db->prepare($sql);
$stmt->execute($params);
$products = $stmt->fetchAll();
// Get categories for filter
$stmt = $db->prepare("SELECT * FROM product_categories ORDER BY name ASC");
$stmt->execute();
$categories = $stmt->fetchAll();
?>
<style>
    /* Page Hero */
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

    /* Filters Section */
    .filters-section {
        background: var(--secondary-color);
        padding: 2rem 0;
        border-bottom: 1px solid var(--border-color);
    }

    .filter-card {
        background: white;
        border-radius: 15px;
        padding: 1.5rem;
        border: 1px solid var(--border-color);
        box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
    }

    .search-box {
        position: relative;
    }

    .search-box input {
        border: 1px solid var(--border-color);
        border-radius: 50px;
        padding: 0.75rem 3rem 0.75rem 1.5rem;
        width: 100%;
        transition: all 0.3s ease;
    }

    .search-box input:focus {
        border-color: var(--primary-color);
        box-shadow: 0 0 0 0.2rem rgba(30, 64, 175, 0.25);
        outline: none;
    }

    .search-icon {
        position: absolute;
        right: 1rem;
        top: 50%;
        transform: translateY(-50%);
        color: var(--text-light);
    }

    .category-filters {
        display: flex;
        gap: 0.5rem;
        flex-wrap: wrap;
        justify-content: center;
    }

    .category-btn {
        border: 1px solid var(--border-color);
        background: white;
        color: var(--text-dark);
        padding: 0.5rem 1.5rem;
        border-radius: 50px;
        text-decoration: none;
        transition: all 0.3s ease;
        font-weight: 500;
        font-size: 0.9rem;
    }

    .category-btn:hover,
    .category-btn.active {
        background: var(--primary-color);
        color: white;
        border-color: var(--primary-color);
        transform: translateY(-2px);
    }

    .filter-results {
        text-align: center;
        color: var(--text-light);
        margin-top: 1rem;
    }

    /* Products Section */
    .products-section {
        padding: 4rem 0;
        background: white;
    }

    .product-card {
        background: white;
        border-radius: 20px;
        overflow: hidden;
        border: 1px solid var(--border-color);
        transition: all 0.3s ease;
        height: 100%;
        position: relative;
    }

    .product-card:hover {
        transform: translateY(-10px);
        box-shadow: 0 20px 40px rgba(0, 0, 0, 0.1);
    }

    .product-image {
        height: 220px;
        background: var(--secondary-color);
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
        transform: scale(1.1);
    }

    .product-badge {
        position: absolute;
        top: 1rem;
        left: 1rem;
        background: var(--primary-color);
        color: white;
        padding: 0.25rem 0.75rem;
        border-radius: 50px;
        font-size: 0.8rem;
        font-weight: 600;
        text-transform: uppercase;
        letter-spacing: 0.5px;
    }

    .product-info {
        padding: 1.5rem;
    }

    .product-category {
        font-size: 0.85rem;
        color: var(--primary-color);
        font-weight: 600;
        text-transform: uppercase;
        letter-spacing: 0.5px;
        margin-bottom: 0.5rem;
    }

    .product-name {
        font-size: 1.1rem;
        font-weight: 600;
        color: var(--text-dark);
        margin-bottom: 0.75rem;
        line-height: 1.4;
        height: 2.8em;
        overflow: hidden;
        display: -webkit-box;
        -webkit-line-clamp: 2;
        -webkit-box-orient: vertical;
    }

    .product-description {
        color: var(--text-light);
        font-size: 0.9rem;
        line-height: 1.5;
        margin-bottom: 1.5rem;
        height: 3.6em;
        overflow: hidden;
        display: -webkit-box;
        -webkit-line-clamp: 3;
        -webkit-box-orient: vertical;
    }

    .product-actions {
        display: flex;
        gap: 0.5rem;
    }

    .btn-product-detail {
        flex: 1;
        background: var(--primary-color);
        color: white;
        border: none;
        border-radius: 50px;
        padding: 0.75rem 1rem;
        font-weight: 600;
        font-size: 0.9rem;
        transition: all 0.3s ease;
        text-decoration: none;
        text-align: center;
    }

    .btn-product-detail:hover {
        background: var(--primary-dark);
        color: white;
        transform: translateY(-2px);
    }

    .btn-quote {
        background: transparent;
        color: var(--primary-color);
        border: 2px solid var(--primary-color);
        border-radius: 50px;
        padding: 0.75rem;
        font-size: 0.9rem;
        transition: all 0.3s ease;
        text-decoration: none;
        display: flex;
        align-items: center;
        justify-content: center;
        width: 45px;
    }

    .btn-quote:hover {
        background: var(--primary-color);
        color: white;
        transform: translateY(-2px);
    }

    /* Empty State */
    .empty-state {
        text-align: center;
        padding: 4rem 2rem;
    }

    .empty-icon {
        font-size: 4rem;
        color: var(--text-light);
        opacity: 0.5;
        margin-bottom: 1rem;
    }

    .empty-title {
        font-size: 1.5rem;
        font-weight: 600;
        color: var(--text-dark);
        margin-bottom: 0.5rem;
    }

    .empty-text {
        color: var(--text-light);
        margin-bottom: 2rem;
    }

    /* Pagination */
    .pagination-wrapper {
        display: flex;
        justify-content: center;
        margin-top: 3rem;
    }

    .pagination {
        gap: 0.5rem;
    }

    .page-link {
        border: 1px solid var(--border-color);
        color: var(--text-dark);
        padding: 0.75rem 1rem;
        border-radius: 10px;
        text-decoration: none;
        transition: all 0.3s ease;
        font-weight: 500;
    }

    .page-link:hover,
    .page-item.active .page-link {
        background: var(--primary-color);
        color: white;
        border-color: var(--primary-color);
        transform: translateY(-2px);
    }

    .page-item.disabled .page-link {
        opacity: 0.5;
        pointer-events: none;
    }

    /* Call to Action Section */
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

    /* Responsive Design */
    @media (max-width: 991px) {
        .page-hero h1 {
            font-size: 2.5rem;
        }

        .page-hero p {
            font-size: 1.1rem;
        }

        .category-filters {
            justify-content: flex-start;
            overflow-x: auto;
            padding-bottom: 0.5rem;
        }

        .category-btn {
            white-space: nowrap;
            flex-shrink: 0;
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

        .product-actions {
            flex-direction: column;
        }

        .btn-quote {
            width: 100%;
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

        .product-info {
            padding: 1rem;
        }

        .cta-title {
            font-size: 1.75rem;
        }
    }
</style>
<!-- Page Hero Section -->
<section class="page-hero">
    <div class="container">
        <div class="page-hero-content" data-aos="fade-up">
            <h1>Produk Kami</h1>
            <p>Pilihan lengkap perangkat dan solusi teknologi berkualitas tinggi untuk mendukung kebutuhan bisnis Anda</p>
            <nav class="breadcrumb-nav">
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="index.php">Beranda</a></li>
                    <li class="breadcrumb-item active">Produk</li>
                </ol>
            </nav>
        </div>
    </div>
</section>
<!-- Filters Section -->
<section class="filters-section">
    <div class="container">
        <div class="filter-card" data-aos="fade-up">
            <form method="GET" action="produk.php" class="row g-3 align-items-end">
                <div class="col-lg-6">
                    <label class="form-label fw-semibold">Cari Produk</label>
                    <div class="search-box">
                        <input type="text" name="search" class="form-control" placeholder="Masukkan nama produk atau kata kunci..." value="<?php echo htmlspecialchars($search); ?>">
                        <i class="fas fa-search search-icon"></i>
                    </div>
                </div>
                <div class="col-lg-4">
                    <label class="form-label fw-semibold">Kategori</label>
                    <select name="category" class="form-select" style="border-radius: 50px; border: 1px solid var(--border-color);">
                        <option value="">Semua Kategori</option>
                        <?php foreach ($categories as $category): ?>
                            <option value="<?php echo $category['id']; ?>" <?php echo $category_filter == $category['id'] ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($category['name']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-lg-2">
                    <button type="submit" class="btn btn-primary w-100">
                        <i class="fas fa-filter me-2"></i>Filter
                    </button>
                </div>
            </form>
            <!-- Quick Category Filters -->
            <div class="category-filters mt-3">
                <a href="produk.php" class="category-btn <?php echo !$category_filter ? 'active' : ''; ?>">
                    Semua
                </a>
                <?php foreach ($categories as $category): ?>
                    <a href="produk.php?category=<?php echo $category['id']; ?>"
                        class="category-btn <?php echo $category_filter == $category['id'] ? 'active' : ''; ?>">
                        <?php echo htmlspecialchars($category['name']); ?>
                    </a>
                <?php endforeach; ?>
            </div>
            <!-- Filter Results Info -->
            <?php if ($search || $category_filter): ?>
                <div class="filter-results">
                    <span class="badge bg-primary"><?php echo $total_items; ?> produk ditemukan</span>
                    <?php if ($search): ?>
                        <span class="badge bg-secondary">Pencarian: "<?php echo htmlspecialchars($search); ?>"</span>
                    <?php endif; ?>
                    <?php if ($category_filter): ?>
                        <?php
                        $selected_category = array_filter($categories, function ($cat) use ($category_filter) {
                            return $cat['id'] == $category_filter;
                        });
                        if ($selected_category):
                            $selected_category = array_values($selected_category)[0];
                        ?>
                            <span class="badge bg-secondary">Kategori: <?php echo htmlspecialchars($selected_category['name']); ?></span>
                        <?php endif; ?>
                    <?php endif; ?>
                    <a href="produk.php" class="btn btn-sm btn-outline-secondary ms-2">Reset Filter</a>
                </div>
            <?php endif; ?>
        </div>
    </div>
</section>
<!-- Products Section -->
<section class="products-section">
    <div class="container">
        <?php if (!empty($products)): ?>
            <div class="row g-4">
                <?php foreach ($products as $index => $product): ?>
                    <div class="col-lg-3 col-md-6" data-aos="fade-up" data-aos-delay="<?php echo $index * 50; ?>">
                        <div class="product-card">
                            <div class="product-image">
                                <?php if ($product['image_path']): ?>
                                    <img src="admin/uploads/products/<?php echo htmlspecialchars($product['image_path']); ?>"
                                        alt="<?php echo htmlspecialchars($product['name']); ?>">
                                <?php else: ?>
                                    <i class="fas fa-image text-muted" style="font-size: 3rem;"></i>
                                <?php endif; ?>
                                <?php if ($product['stock'] > 0): ?>
                                    <div class="product-badge">Tersedia</div>
                                <?php else: ?>
                                    <div class="product-badge" style="background: #dc3545;">Habis</div>
                                <?php endif; ?>
                            </div>
                            <div class="product-info">
                                <div class="product-category">
                                    <?php echo htmlspecialchars($product['category_name'] ?? 'Uncategorized'); ?>
                                </div>
                                <h5 class="product-name"><?php echo htmlspecialchars($product['name']); ?></h5>
                                <p class="product-description">
                                    <?php echo htmlspecialchars(truncateText($product['description'], 120)); ?>
                                </p>
                                <div class="product-actions">
                                    <a href="produk-detail.php?id=<?php echo $product['id']; ?>" class="btn-product-detail">
                                        <i class="fas fa-eye me-2"></i>Lihat Detail
                                    </a>
                                    <a href="kontak.php?product=<?php echo urlencode($product['name']); ?>"
                                        class="btn-quote" title="Minta Penawaran">
                                        <i class="fas fa-quote-right"></i>
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
            <!-- Pagination -->
            <?php if ($total_pages > 1): ?>
                <div class="pagination-wrapper">
                    <nav aria-label="Products pagination">
                        <ul class="pagination">
                            <!-- Previous Button -->
                            <li class="page-item <?php echo $page <= 1 ? 'disabled' : ''; ?>">
                                <a class="page-link" href="?<?php echo http_build_query(array_merge($_GET, ['page' => $page - 1])); ?>">
                                    <i class="fas fa-chevron-left"></i>
                                </a>
                            </li>
                            <!-- Page Numbers -->
                            <?php
                            $start_page = max(1, $page - 2);
                            $end_page = min($total_pages, $page + 2);
                            if ($start_page > 1): ?>
                                <li class="page-item">
                                    <a class="page-link" href="?<?php echo http_build_query(array_merge($_GET, ['page' => 1])); ?>">1</a>
                                </li>
                                <?php if ($start_page > 2): ?>
                                    <li class="page-item disabled">
                                        <span class="page-link">...</span>
                                    </li>
                                <?php endif; ?>
                            <?php endif; ?>
                            <?php for ($i = $start_page; $i <= $end_page; $i++): ?>
                                <li class="page-item <?php echo $i == $page ? 'active' : ''; ?>">
                                    <a class="page-link" href="?<?php echo http_build_query(array_merge($_GET, ['page' => $i])); ?>">
                                        <?php echo $i; ?>
                                    </a>
                                </li>
                            <?php endfor; ?>
                            <?php if ($end_page < $total_pages): ?>
                                <?php if ($end_page < $total_pages - 1): ?>
                                    <li class="page-item disabled">
                                        <span class="page-link">...</span>
                                    </li>
                                <?php endif; ?>
                                <li class="page-item">
                                    <a class="page-link" href="?<?php echo http_build_query(array_merge($_GET, ['page' => $total_pages])); ?>">
                                        <?php echo $total_pages; ?>
                                    </a>
                                </li>
                            <?php endif; ?>
                            <!-- Next Button -->
                            <li class="page-item <?php echo $page >= $total_pages ? 'disabled' : ''; ?>">
                                <a class="page-link" href="?<?php echo http_build_query(array_merge($_GET, ['page' => $page + 1])); ?>">
                                    <i class="fas fa-chevron-right"></i>
                                </a>
                            </li>
                        </ul>
                    </nav>
                </div>
            <?php endif; ?>
        <?php else: ?>
            <!-- Empty State -->
            <div class="empty-state" data-aos="fade-up">
                <div class="empty-icon">
                    <i class="fas fa-search"></i>
                </div>
                <h3 class="empty-title">Produk Tidak Ditemukan</h3>
                <p class="empty-text">
                    <?php if ($search || $category_filter): ?>
                        Maaf, tidak ada produk yang sesuai dengan kriteria pencarian Anda. Silakan coba dengan kata kunci atau kategori lain.
                    <?php else: ?>
                        Belum ada produk yang tersedia saat ini. Silakan kembali lagi nanti.
                    <?php endif; ?>
                </p>
                <a href="produk.php" class="btn btn-primary">
                    <i class="fas fa-refresh me-2"></i>Lihat Semua Produk
                </a>
            </div>
        <?php endif; ?>
    </div>
</section>
<!-- Call to Action -->
<section class="cta-section">
    <div class="container">
        <div class="cta-content text-center" data-aos="fade-up">
            <h2 class="cta-title">Tidak Menemukan Produk yang Dicari?</h2>
            <p class="cta-text">
                Tim kami siap membantu Anda menemukan solusi teknologi yang tepat sesuai dengan kebutuhan spesifik bisnis Anda
            </p>
            <div class="d-flex gap-3 justify-content-center flex-wrap">
                <a href="kontak.php" class="btn btn-hero-primary btn-lg">
                    <i class="fas fa-phone me-2"></i>Hubungi Kami
                </a>
                <a href="layanan.php" class="btn btn-hero-secondary btn-lg">
                    <i class="fas fa-cogs me-2"></i>Lihat Layanan
                </a>
            </div>
        </div>
    </div>
</section>
<script>
    // Auto-submit search form on category change
    document.querySelector('select[name="category"]').addEventListener('change', function() {
        this.form.submit();
    });
    document.querySelector('input[name="search"]').addEventListener('input', function(e) {
        console.log('Search query:', e.target.value);
    });
</script>
<?php include 'includes/footer.php'; ?>