<?php
require_once 'config/db.php';
// Get database connection
$db = getDB();
// Pagination settings
$articles_per_page = 6; 
$page = isset($_GET['page']) ? intval($_GET['page']) : 1;
$offset = ($page - 1) * $articles_per_page;
// Category filter
$category_filter = isset($_GET['category_id']) ? intval($_GET['category_id']) : 0;
$category_name_filter = null;
try {
    // Get total articles count
    $count_sql = "SELECT COUNT(*) FROM articles a 
                  LEFT JOIN article_category_map acm ON a.id = acm.article_id 
                  WHERE a.status = 'published'";
    $count_params = [];
    if ($category_filter > 0) {
        $count_sql .= " AND acm.category_id = ?";
        $count_params[] = $category_filter;
        // Get category name for display
        $stmt_cat = $db->prepare("SELECT name FROM categories WHERE id = ?");
        $stmt_cat->execute([$category_filter]);
        $category_name_filter = $stmt_cat->fetchColumn();
    }
    $stmt = $db->prepare($count_sql);
    $stmt->execute($count_params);
    $total_articles = $stmt->fetchColumn();
    $total_pages = ceil($total_articles / $articles_per_page);
    // Get articles
    $sql = "SELECT DISTINCT a.*, u.username as author_name,
                   (SELECT c.name FROM categories c 
                    JOIN article_category_map acm ON c.id = acm.category_id 
                    WHERE acm.article_id = a.id LIMIT 1) as category_name
            FROM articles a 
            LEFT JOIN users u ON a.author_id = u.id
            LEFT JOIN article_category_map acm ON a.id = acm.article_id
            WHERE a.status = 'published'";
    $params = [];
    if ($category_filter > 0) {
        $sql .= " AND acm.category_id = ?";
        $params[] = $category_filter;
    }
    $sql .= " ORDER BY a.created_at DESC LIMIT ? OFFSET ?";
    $params[] = $articles_per_page;
    $params[] = $offset;
    $stmt = $db->prepare($sql);
    $stmt->execute($params);
    $articles = $stmt->fetchAll();
    // Get categories for sidebar
    $stmt = $db->prepare("SELECT c.id as category_id, c.name as category_name, COUNT(acm.article_id) as post_count 
                          FROM categories c
                          LEFT JOIN article_category_map acm ON c.id = acm.category_id
                          LEFT JOIN articles a ON acm.article_id = a.id AND a.status = 'published'
                          GROUP BY c.id, c.name
                          ORDER BY post_count DESC");
    $stmt->execute();
    $categories = $stmt->fetchAll();
    // Get recent posts for sidebar
    $stmt = $db->prepare("SELECT a.title, a.slug, a.created_at, a.image_path
                          FROM articles a
                          WHERE a.status = 'published' 
                          ORDER BY a.created_at DESC 
                          LIMIT 5");
    $stmt->execute();
    $recent_posts = $stmt->fetchAll();
} catch (PDOException $e) {
    $error = "Error fetching articles: " . $e->getMessage();
}
$page_title = 'Blog' . ($category_name_filter ? " - Kategori: " . htmlspecialchars($category_name_filter) : "");
include 'includes/header.php';
?>
<style>
    /* Page Hero - Updated to match produk.php */
    .page-hero {
        background: url("assets/img/blog-hero-bg.jpg") center/cover no-repeat;
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

    /* Blog Posts Styles */
    .blog-posts {
        padding: 4rem 0;
        background: white;
    }

    .post-img {
        height: 220px;
        overflow: hidden;
        border-radius: 15px;
        margin-bottom: 20px;
        box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
        transition: all 0.3s ease;
    }

    .post-img img {
        width: 100%;
        height: 100%;
        object-fit: cover;
        transition: transform 0.3s ease;
    }

    article:hover .post-img img {
        transform: scale(1.05);
    }

    article:hover .post-img {
        transform: translateY(-5px);
        box-shadow: 0 15px 30px rgba(0, 0, 0, 0.1);
    }

    .post-category {
        color: var(--primary-color);
        font-weight: 600;
        font-size: 14px;
        margin-bottom: 10px;
        text-transform: uppercase;
        letter-spacing: 0.5px;
    }

    .title {
        font-size: 22px;
        font-weight: 700;
        line-height: 1.3;
        margin-bottom: 15px;
        color: var(--text-dark);
    }

    .title a {
        color: var(--text-dark);
        text-decoration: none;
        transition: color 0.3s ease;
    }

    .title a:hover {
        color: #47b2e4;
    }

    .post-meta {
        display: flex;
        gap: 20px;
        color: var(--text-light);
        font-size: 14px;
    }

    .post-author,
    .post-date {
        margin: 0;
        display: flex;
        align-items: center;
    }

    .post-excerpt {
        color: var(--text-light);
        line-height: 1.6;
        margin-bottom: 1.5rem;
    }

    .read-more {
        color: var(--primary-color);
        text-decoration: none;
        font-weight: 600;
        transition: all 0.3s ease;
        display: inline-flex;
        align-items: center;
    }

    .read-more:hover {
        color: #47b2e4;
    }

    .read-more i {
        margin-left: 5px;
        transition: transform 0.3s ease;
    }

    .read-more:hover i {
        transform: translateX(3px);
    }

    .sidebar {
        padding-top: 2rem;
    }

    .widget-item {
        background: white;
        border-radius: 15px;
        padding: 25px;
        margin-bottom: 30px;
        border: 1px solid var(--border-color);
        box-shadow: 0 5px 15px rgba(0, 0, 0, 0.05);
        transition: all 0.3s ease;
    }

    .widget-item:hover {
        transform: translateY(-5px);
        box-shadow: 0 15px 30px rgba(0, 0, 0, 0.1);
    }

    .widget-title {
        font-size: 20px;
        font-weight: 700;
        position: relative;
        padding-bottom: 15px;
        margin-bottom: 20px;
        color: var(--text-dark);
        -webkit-font-smoothing: antialiased;
        -moz-osx-font-smoothing: grayscale;
    }

    .widget-title::after {
        content: '';
        position: absolute;
        left: 0;
        bottom: 0;
        width: 50px;
        height: 3px;
        background: var(--gradient-soft);
    }

    .categories-widget ul {
        list-style: none;
        padding: 0;
        margin: 0;
    }

    .categories-widget li {
        margin-bottom: 10px;
        padding-bottom: 10px;
        border-bottom: 1px dashed var(--border-color);
    }

    .categories-widget li:last-child {
        border-bottom: none;
        margin-bottom: 0;
        padding-bottom: 0;
    }

    .categories-widget a {
        display: flex;
        justify-content: space-between;
        color: var(--text-dark);
        text-decoration: none;
        transition: color 0.3s ease;
    }

    .categories-widget a:hover {
        color: #47b2e4;
    }

    .categories-widget span {
        background: var(--secondary-color);
        color: var(--primary-color);
        padding: 2px 8px;
        border-radius: 12px;
        font-size: 12px;
        font-weight: 600;
    }

    .post-item {
        display: flex;
        gap: 15px;
        margin-bottom: 20px;
        padding-bottom: 20px;
        border-bottom: 1px dashed var(--border-color);
        transition: all 0.3s ease;
    }

    .post-item:hover {
        transform: translateX(5px);
    }

    .post-item:last-child {
        margin-bottom: 0;
        padding-bottom: 0;
        border-bottom: none;
    }

    .post-item img {
        width: 80px;
        height: 80px;
        object-fit: cover;
        border-radius: 10px;
        flex-shrink: 0;
        transition: transform 0.3s ease;
    }

    .post-item:hover img {
        transform: scale(1.05);
    }

    .post-item h4 {
        font-size: 16px;
        margin-bottom: 5px;
        line-height: 1.4;
        color: var(--text-dark);
    }

    .post-item h4 a {
        color: var(--text-dark);
        text-decoration: none;
        transition: color 0.3s ease;
    }

    .post-item h4 a:hover {
        color: var(--primary-color);
        color: #47b2e4;
    }

    .post-item time {
        font-size: 13px;
        color: var(--text-light);
    }

    /* Category Filter Info */
    .category-filter-info {
        background: var(--secondary-color);
        border-radius: 15px;
        padding: 1rem 1.5rem;
        margin-bottom: 2rem;
        border: 1px solid var(--border-color);
        display: flex;
        justify-content: space-between;
        align-items: center;
        transition: all 0.3s ease;
    }

    .category-filter-info:hover {
        transform: translateY(-3px);
        box-shadow: 0 10px 20px rgba(0, 0, 0, 0.05);
    }

    .category-filter-info span {
        color: var(--text-dark);
        font-weight: 500;
    }

    .category-filter-info strong {
        color: var(--primary-color);
    }

    .category-filter-info .btn {
        border-radius: 50px;
        padding: 0.4rem 1rem;
        font-size: 0.875rem;
        font-weight: 500;
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

    /* No Articles State */
    .no-articles {
        min-height: 300px;
        display: flex;
        flex-direction: column;
        justify-content: center;
        align-items: center;
        text-align: center;
        padding: 3rem;
        background: var(--secondary-color);
        border-radius: 20px;
        border: 1px solid var(--border-color);
    }

    .no-articles i {
        font-size: 3rem;
        color: var(--text-light);
        margin-bottom: 1rem;
    }

    .no-articles h3 {
        color: var(--text-dark);
        margin-bottom: 1rem;
    }

    .no-articles p {
        color: var(--text-light);
        margin-bottom: 1.5rem;
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

    /* Responsive Styles */
    @media (max-width: 992px) {
        .page-hero h1 {
            font-size: 2.5rem;
        }

        .page-hero p {
            font-size: 1.1rem;
        }

        .sidebar {
            padding-top: 2rem;
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

        .post-img {
            height: 200px;
        }

        .title {
            font-size: 20px;
        }

        .post-meta {
            flex-direction: column;
            gap: 5px;
        }

        .pagination {
            font-size: 0.875rem;
        }

        .page-link {
            padding: 0.6rem 0.8rem;
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

        .post-img {
            height: 180px;
        }

        .title {
            font-size: 18px;
        }

        .widget-item {
            padding: 1.5rem;
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
            <h1>Blog & Artikel</h1>
            <p>Temukan informasi terbaru dan inspirasi dari artikel kami</p>
            <nav class="breadcrumb-nav">
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="index.php">Beranda</a></li>
                    <li class="breadcrumb-item active">Blog</li>
                </ol>
            </nav>
        </div>
    </div>
</section>
<div class="container">
    <div class="row">
        <div class="col-lg-8">
            <section id="blog-posts" class="blog-posts section">
                <div class="container">
                    <!-- Category Filter Info -->
                    <?php if ($category_name_filter): ?>
                        <div class="category-filter-info" data-aos="fade-up">
                            <span>Menampilkan artikel dalam kategori: <strong><?= htmlspecialchars($category_name_filter) ?></strong></span>
                            <a href="artikel.php" class="btn btn-outline-primary">Tampilkan Semua</a>
                        </div>
                    <?php endif; ?>
                    <div class="row gy-4">
                        <?php if (!empty($articles)): ?>
                            <?php foreach ($articles as $article): ?>
                                <div class="col-lg-6" data-aos="fade-up" data-aos-delay="<?php echo (array_search($article, $articles)) * 100; ?>">
                                    <article>
                                        <div class="post-img">
                                            <?php if (!empty($article['image_path']) && file_exists('admin/uploads/articles/' . $article['image_path'])): ?>
                                                <img src="admin/uploads/articles/<?php echo htmlspecialchars($article['image_path']); ?>"
                                                    alt="<?php echo htmlspecialchars($article['title']); ?>"
                                                    class="img-fluid">
                                            <?php else: ?>
                                                <img src="assets/img/blog/blog-1.jpg"
                                                    alt="<?php echo htmlspecialchars($article['title']); ?>"
                                                    class="img-fluid">
                                            <?php endif; ?>
                                        </div>
                                        <p class="post-category"><?php echo htmlspecialchars($article['category_name'] ?? 'Berita'); ?></p>
                                        <h2 class="title">
                                            <a href="artikel-detail.php?slug=<?php echo htmlspecialchars($article['slug']); ?>">
                                                <?php echo htmlspecialchars($article['title']); ?>
                                            </a>
                                        </h2>
                                        <p class="post-excerpt">
                                            <?php echo htmlspecialchars(truncateText($article['content'], 150)); ?>
                                        </p>
                                        <div class="d-flex align-items-center justify-content-between">
                                            <div class="post-meta">
                                                <p class="post-author">
                                                    <i class="bi bi-person-fill me-1"></i>
                                                    <?php echo htmlspecialchars($article['author_name'] ?? 'Admin'); ?>
                                                </p>
                                                <p class="post-date">
                                                    <i class="bi bi-calendar-event me-1"></i>
                                                    <time datetime="<?php echo date('Y-m-d', strtotime($article['created_at'])); ?>">
                                                        <?php echo date('d M Y', strtotime($article['created_at'])); ?>
                                                    </time>
                                                </p>
                                            </div>
                                            <a href="artikel-detail.php?slug=<?php echo htmlspecialchars($article['slug']); ?>" class="read-more">
                                                Baca Selengkapnya <i class="bi bi-arrow-right"></i>
                                            </a>
                                        </div>
                                    </article>
                                </div>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <div class="col-12">
                                <div class="no-articles" data-aos="fade-up">
                                    <i class="bi bi-journal-text"></i>
                                    <h3>Tidak Ada Artikel</h3>
                                    <p>Tidak ada artikel yang ditemukan untuk kategori ini.</p>
                                    <a href="artikel.php" class="btn btn-primary">Lihat Semua Artikel</a>
                                </div>
                            </div>
                        <?php endif; ?>
                    </div>
                    <!-- Pagination -->
                    <?php if ($total_pages > 1): ?>
                        <div class="pagination-wrapper">
                            <nav aria-label="Blog pagination">
                                <ul class="pagination">
                                    <!-- Previous Page -->
                                    <?php if ($page > 1): ?>
                                        <li class="page-item">
                                            <a class="page-link" href="artikel.php?page=<?php echo $page - 1; ?><?php echo $category_filter > 0 ? '&category_id=' . $category_filter : ''; ?>">
                                                <i class="bi bi-chevron-left"></i>
                                            </a>
                                        </li>
                                    <?php endif; ?>
                                    <!-- Page Numbers -->
                                    <?php
                                    $start_page = max(1, $page - 2);
                                    $end_page = min($total_pages, $page + 2);
                                    for ($i = $start_page; $i <= $end_page; $i++):
                                    ?>
                                        <li class="page-item <?php echo $i == $page ? 'active' : ''; ?>">
                                            <a class="page-link" href="artikel.php?page=<?php echo $i; ?><?php echo $category_filter > 0 ? '&category_id=' . $category_filter : ''; ?>">
                                                <?php echo $i; ?>
                                            </a>
                                        </li>
                                    <?php endfor; ?>
                                    <!-- Next Page -->
                                    <?php if ($page < $total_pages): ?>
                                        <li class="page-item">
                                            <a class="page-link" href="artikel.php?page=<?php echo $page + 1; ?><?php echo $category_filter > 0 ? '&category_id=' . $category_filter : ''; ?>">
                                                <i class="bi bi-chevron-right"></i>
                                            </a>
                                        </li>
                                    <?php endif; ?>
                                </ul>
                            </nav>
                        </div>
                    <?php endif; ?>
                </div>
            </section>
        </div>
        <div class="col-lg-4 sidebar">
            <div class="widgets-container">
                <!-- Categories Widget -->
                <div class="categories-widget widget-item" data-aos="fade-up" data-aos-delay="100">
                    <h3 class="widget-title" style="color:black;">Kategori</h3>
                    <ul class="mt-3">
                        <?php foreach ($categories as $category): ?>
                            <li>
                                <a href="artikel.php?category_id=<?php echo $category['category_id']; ?>">
                                    <?php echo htmlspecialchars($category['category_name']); ?>
                                    <span>(<?php echo $category['post_count']; ?>)</span>
                                </a>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                </div>
                <!-- Recent Posts Widget -->
                <div class="recent-posts-widget widget-item" data-aos="fade-up" data-aos-delay="200">
                    <h3 class="widget-title" style="color:black;">Artikel Terbaru</h3>
                    <?php foreach ($recent_posts as $post): ?>
                        <div class="post-item">
                            <?php if (!empty($post['image_path']) && file_exists('admin/uploads/articles/' . $post['image_path'])): ?>
                                <img src="admin/uploads/articles/<?php echo htmlspecialchars($post['image_path']); ?>"
                                    alt=""
                                    class="flex-shrink-0">
                            <?php else: ?>
                                <img src="assets/img/blog/blog-recent-1.jpg"
                                    alt=""
                                    class="flex-shrink-0">
                            <?php endif; ?>
                            <div>
                                <h4>
                                    <a href="artikel-detail.php?slug=<?php echo htmlspecialchars($post['slug']); ?>">
                                        <?php echo htmlspecialchars($post['title']); ?>
                                    </a>
                                </h4>
                                <time datetime="<?php echo date('Y-m-d', strtotime($post['created_at'])); ?>">
                                    <?php echo date('d M Y', strtotime($post['created_at'])); ?>
                                </time>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
    </div>
</div>
<!-- Call to Action -->
<section class="cta-section">
    <div class="container">
        <div class="cta-content text-center" data-aos="fade-up">
            <h2 class="cta-title">Butuh Informasi Lebih Lanjut?</h2>
            <p class="cta-text">
                Tim kami siap membantu Anda menemukan solusi teknologi yang tepat sesuai dengan kebutuhan spesifik bisnis Anda
            </p>
            <div class="d-flex gap-3 justify-content-center flex-wrap">
                <a href="kontak.php" class="btn btn-hero-primary btn-lg">
                    <i class="bi bi-telephone me-2"></i>Hubungi Kami
                </a>
                <a href="layanan.php" class="btn btn-hero-secondary btn-lg">
                    <i class="bi bi-gear me-2"></i>Lihat Layanan
                </a>
            </div>
        </div>
    </div>
</section>
<?php include 'includes/footer.php'; ?>