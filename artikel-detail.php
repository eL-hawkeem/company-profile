<?php
require_once 'config/db.php';
// Get database connection
$db = getDB();
// Get article slug from URL
$slug = isset($_GET['slug']) ? $_GET['slug'] : '';
if (empty($slug)) {
    header('Location: artikel.php');
    exit();
}
try {
    // Get article details
    $stmt = $db->prepare("
        SELECT a.*, u.username as author_name,
               GROUP_CONCAT(c.name SEPARATOR ', ') as categories
        FROM articles a 
        LEFT JOIN users u ON a.author_id = u.id
        LEFT JOIN article_category_map acm ON a.id = acm.article_id
        LEFT JOIN categories c ON acm.category_id = c.id
        WHERE a.slug = ? AND a.status = 'published'
        GROUP BY a.id
    ");
    $stmt->execute([$slug]);
    $article = $stmt->fetch();
    if (!$article) {
        header('Location: artikel.php');
        exit();
    }
    // Get approved comments
    $stmt = $db->prepare("
        SELECT * FROM comments 
        WHERE article_id = ? AND status = 'approved' 
        ORDER BY created_at ASC
    ");
    $stmt->execute([$article['id']]);
    $comments = $stmt->fetchAll();
    // Get related articles
    $stmt = $db->prepare("
        SELECT DISTINCT a.* FROM articles a
        LEFT JOIN article_category_map acm ON a.id = acm.article_id
        WHERE a.status = 'published' 
        AND a.id != ?
        AND acm.category_id IN (
            SELECT category_id FROM article_category_map WHERE article_id = ?
        )
        ORDER BY a.created_at DESC
        LIMIT 3
    ");
    $stmt->execute([$article['id'], $article['id']]);
    $related_articles = $stmt->fetchAll();
    // If no related articles from same category, get latest articles
    if (empty($related_articles)) {
        $stmt = $db->prepare("
            SELECT * FROM articles 
            WHERE status = 'published' AND id != ? 
            ORDER BY created_at DESC 
            LIMIT 3
        ");
        $stmt->execute([$article['id']]);
        $related_articles = $stmt->fetchAll();
    }
    // Handle comment submission
    if ($_POST && isset($_POST['submit_comment'])) {
        $author_name = trim($_POST['author_name'] ?? '');
        $author_email = trim($_POST['author_email'] ?? '');
        $content = trim($_POST['content'] ?? '');
        $errors = [];
        if (empty($author_name)) {
            $errors[] = 'Nama harus diisi.';
        }
        if (empty($author_email) || !filter_var($author_email, FILTER_VALIDATE_EMAIL)) {
            $errors[] = 'Email yang valid harus diisi.';
        }
        if (empty($content)) {
            $errors[] = 'Komentar harus diisi.';
        }
        if (empty($errors)) {
            try {
                $stmt = $db->prepare("
                    INSERT INTO comments (article_id, author_name, author_email, content, status, created_at) 
                    VALUES (?, ?, ?, ?, 'pending', NOW())
                ");
                $stmt->execute([$article['id'], $author_name, $author_email, $content]);
                $success_message = 'Komentar Anda telah dikirim dan menunggu persetujuan admin.';
                // Clear form data
                $_POST = [];
            } catch (PDOException $e) {
                $errors[] = 'Terjadi kesalahan saat mengirim komentar.';
            }
        }
    }
} catch (PDOException $e) {
    $error = "Error fetching article: " . $e->getMessage();
}
$page_title = $article['title'] ?? 'Artikel Tidak Ditemukan';
include 'includes/header.php';
?>

<style>
    /* === DESAIN HERO-SECTION BARU (SESUAI PRODUK.PHP) START === */
    .page-hero {
        background: url("assets/img/blog-hero-bg.jpg") center/cover no-repeat;
        /* Disesuaikan untuk artikel */
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
        /* Diperlebar agar sesuai judul artikel */
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

    .blog-details {
        padding: 80px 0;
    }

    .article-content {
        background: #fff;
        border-radius: 10px;
        padding: 40px;
        box-shadow: 0 0 30px rgba(0, 0, 0, 0.1);
        margin-bottom: 30px;
    }

    .featured-image {
        border-radius: 10px;
        width: 100%;
        height: 400px;
        object-fit: cover;
    }

    .article-meta {
        border-bottom: 1px solid #eee;
        padding-bottom: 20px;
        margin-bottom: 30px;
    }

    .meta-info {
        display: flex;
        flex-wrap: wrap;
        gap: 20px;
    }

    .meta-item {
        color: #6c757d;
        font-size: 0.9rem;
    }

    .article-body {
        line-height: 1.8;
        font-size: 1.1rem;
    }

    .article-body h1,
    .article-body h2,
    .article-body h3,
    .article-body h4,
    .article-body h5,
    .article-body h6 {
        color: #2c4964;
        margin: 30px 0 20px 0;
    }

    .article-body p {
        margin-bottom: 20px;
    }

    .article-body img {
        max-width: 100%;
        height: auto;
        border-radius: 5px;
        margin: 20px 0;
    }

    .share-buttons {
        display: flex;
        gap: 10px;
        flex-wrap: wrap;
    }

    .share-buttons .btn {
        padding: 8px 16px;
        border-radius: 25px;
    }

    .comments-section {
        background: #fff;
        border-radius: 10px;
        padding: 40px;
        box-shadow: 0 0 30px rgba(0, 0, 0, 0.1);
    }

    .comment-item {
        border-bottom: 1px solid #eee;
        padding: 20px 0;
    }

    .comment-item:last-child {
        border-bottom: none;
    }

    .comment-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 10px;
    }

    .comment-date {
        font-size: 0.875rem;
        color: #6c757d;
    }

    .comment-content {
        color: #666;
        line-height: 1.6;
    }

    .comment-form {
        border-top: 1px solid #eee;
        padding-top: 30px;
    }

    .sidebar {
        padding-left: 30px;
    }

    .sidebar-item {
        background: #fff;
        border-radius: 10px;
        padding: 30px;
        margin-bottom: 30px;
        box-shadow: 0 0 30px rgba(0, 0, 0, 0.1);
    }

    .sidebar-item h4 {
        color: #2c4964;
        margin-bottom: 20px;
        -webkit-font-smoothing: antialiased;
        -moz-osx-font-smoothing: grayscale;
    }

    .related-post {
        display: flex;
        gap: 15px;
        padding: 15px 0;
        border-bottom: 1px solid #eee;
    }

    .related-post:last-child {
        border-bottom: none;
    }

    .related-thumb {
        flex-shrink: 0;
        width: 80px;
        height: 60px;
    }

    .related-thumb img {
        width: 100%;
        height: 100%;
        object-fit: cover;
        border-radius: 5px;
    }

    .no-thumb {
        width: 100%;
        height: 100%;
        background: #f8f9fa;
        border: 2px dashed #dee2e6;
        display: flex;
        align-items: center;
        justify-content: center;
        border-radius: 5px;
    }

    .related-content h6 {
        margin-bottom: 5px;
        line-height: 1.3;
    }

    .related-content h6 a {
        color: #2c4964;
        text-decoration: none;
        transition: color 0.3s ease;
    }

    .related-content h6 a:hover {
        color: #47b2e4;
    }

    /* ================================================ */
    /* ===Background CTA di Sidebar Artikel === */
    /* ================================================ */
    .sidebar-item.cta-section {
        background: #ffffff !important;
        padding: 30px !important;
        border: 1px solid var(--border-color);
    }

    .sidebar-item.cta-section::before,
    .sidebar-item.cta-section::after {
        display: none !important;
    }

    .sidebar-item.cta-section .cta-box {
        background: transparent;
        color: var(--text-dark);
        padding: 0;
    }

    .sidebar-item.cta-section .cta-box h5,
    .sidebar-item.cta-section .cta-box p {
        color: var(--text-dark);
    }

    .sidebar-item.cta-section .cta-box .btn {
        background: var(--primary-color);
        border: none;
        color: white;
    }

    .sidebar-item.cta-section .cta-box .btn:hover {
        background: var(--primary-dark);
    }

    .cta-box {
        background: linear-gradient(135deg, #47b2e4 0%, #2c4964 100%);
        color: white;
        padding: 30px 20px;
        border-radius: 10px;
        z-index: 2;
    }

    .cta-box .btn {
        background: rgba(255, 255, 255, 0.2);
        border: 1px solid rgba(255, 255, 255, 0.3);
        color: white;
        border-radius: 25px;
        padding: 10px 25px;
    }

    .cta-box .btn:hover {
        background: white;
        color: #2c4964;
    }

    @media (max-width: 992px) {
        .sidebar {
            padding-left: 0;
            margin-top: 40px;
        }
    }

    @media (max-width: 768px) {
        .page-hero h1 {
            font-size: 2.2rem;
        }

        .page-hero p {
            font-size: 1rem;
        }

        .article-content {
            padding: 20px;
        }

        .comments-section {
            padding: 20px;
        }

        .sidebar-item {
            padding: 20px;
        }

        .meta-info {
            flex-direction: column;
            gap: 10px;
        }

        .share-buttons {
            justify-content: center;
        }

        .comment-header {
            flex-direction: column;
            align-items: flex-start;
            gap: 5px;
        }
    }
</style>

<main class="main">
    <section class="page-hero">
        <div class="container">
            <div class="page-hero-content" data-aos="fade-up">
                <h1><?php echo htmlspecialchars($article['title']); ?></h1>
                <p>Detail artikel dan informasi terkait</p>
                <nav class="breadcrumb-nav">
                    <ol class="breadcrumb">
                        <li class="breadcrumb-item"><a href="index.php">Beranda</a></li>
                        <li class="breadcrumb-item"><a href="artikel.php">Artikel</a></li>
                        <li class="breadcrumb-item active"><?php echo htmlspecialchars(substr($article['title'], 0, 50)) . '...'; ?></li>
                    </ol>
                </nav>
            </div>
        </div>
    </section>
    <section id="blog-details" class="blog-details section">
        <div class="container">
            <div class="row">
                <div class="col-lg-8">
                    <article class="article-content">
                        <div class="article-header">
                            <?php if (!empty($article['image_path']) && file_exists('admin/uploads/articles/' . $article['image_path'])): ?>
                                <img src="admin/uploads/articles/<?php echo htmlspecialchars($article['image_path']); ?>"
                                    alt="<?php echo htmlspecialchars($article['title']); ?>"
                                    class="img-fluid featured-image mb-4">
                            <?php endif; ?>
                            <div class="article-meta">
                                <div class="meta-info">
                                    <span class="meta-item">
                                        <i class="bi bi-calendar-event me-1"></i>
                                        <?php echo date('d F Y', strtotime($article['created_at'])); ?>
                                    </span>
                                    <span class="meta-item">
                                        <i class="bi bi-person-fill me-1"></i>
                                        <?php echo htmlspecialchars($article['author_name']); ?>
                                    </span>
                                    <?php if (!empty($article['categories'])): ?>
                                        <span class="meta-item">
                                            <i class="bi bi-tag-fill me-1"></i>
                                            <?php echo htmlspecialchars($article['categories']); ?>
                                        </span>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                        <div class="article-body">
                            <?php echo $article['content']; ?>
                        </div>
                        <div class="article-share mt-4">
                            <h5>Bagikan Artikel Ini</h5>
                            <div class="share-buttons">
                                <a href="https://www.facebook.com/sharer/sharer.php?u=<?php echo urlencode('http://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI']); ?>"
                                    target="_blank" class="btn btn-primary">
                                    <i class="bi bi-facebook"></i> Facebook
                                </a>
                                <a href="https://twitter.com/intent/tweet?url=<?php echo urlencode('http://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI']); ?>&text=<?php echo urlencode($article['title']); ?>"
                                    target="_blank" class="btn btn-info">
                                    <i class="bi bi-twitter"></i> Twitter
                                </a>
                                <a href="https://wa.me/?text=<?php echo urlencode($article['title'] . ' - ' . 'http://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI']); ?>"
                                    target="_blank" class="btn btn-success">
                                    <i class="bi bi-whatsapp"></i> WhatsApp
                                </a>
                            </div>
                        </div>
                    </article>
                    <div class="comments-section mt-5">
                        <h4>Komentar (<?php echo count($comments); ?>)</h4>
                        <?php if (!empty($comments)): ?>
                            <div class="comments-list mt-4">
                                <?php foreach ($comments as $comment): ?>
                                    <div class="comment-item">
                                        <div class="comment-header">
                                            <strong><?php echo htmlspecialchars($comment['author_name']); ?></strong>
                                            <span class="comment-date">
                                                <?php echo date('d F Y H:i', strtotime($comment['created_at'])); ?>
                                            </span>
                                        </div>
                                        <div class="comment-content">
                                            <?php echo nl2br(htmlspecialchars($comment['content'])); ?>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php else: ?>
                            <p class="text-muted">Belum ada komentar untuk artikel ini.</p>
                        <?php endif; ?>
                        <div class="comment-form mt-5">
                            <h5>Tinggalkan Komentar</h5>
                            <?php if (isset($success_message)): ?>
                                <div class="alert alert-success"><?php echo $success_message; ?></div>
                            <?php endif; ?>
                            <?php if (!empty($errors)): ?>
                                <div class="alert alert-danger">
                                    <ul class="mb-0">
                                        <?php foreach ($errors as $error): ?>
                                            <li><?php echo $error; ?></li>
                                        <?php endforeach; ?>
                                    </ul>
                                </div>
                            <?php endif; ?>
                            <form method="POST" action="">
                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label for="author_name" class="form-label">Nama Lengkap *</label>
                                            <input type="text" class="form-control" id="author_name" name="author_name"
                                                value="<?php echo htmlspecialchars($_POST['author_name'] ?? ''); ?>" required>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label for="author_email" class="form-label">Email *</label>
                                            <input type="email" class="form-control" id="author_email" name="author_email"
                                                value="<?php echo htmlspecialchars($_POST['author_email'] ?? ''); ?>" required>
                                        </div>
                                    </div>
                                </div>
                                <div class="mb-3">
                                    <label for="content" class="form-label">Komentar *</label>
                                    <textarea class="form-control" id="content" name="content" rows="4" required><?php echo htmlspecialchars($_POST['content'] ?? ''); ?></textarea>
                                </div>
                                <button type="submit" name="submit_comment" class="btn btn-primary">
                                    <i class="bi bi-send-fill me-1"></i> Kirim Komentar
                                </button>
                            </form>
                        </div>
                    </div>
                </div>
                <div class="col-lg-4">
                    <div class="sidebar">
                        <?php if (!empty($related_articles)): ?>
                            <div class="sidebar-item related-articles">
                                <h4>Artikel Terkait</h4>
                                <?php foreach ($related_articles as $related): ?>
                                    <div class="related-post">
                                        <div class="related-thumb">
                                            <?php if (!empty($related['image_path']) && file_exists('admin/uploads/articles/' . $related['image_path'])): ?>
                                                <img src="admin/uploads/articles/<?php echo htmlspecialchars($related['image_path']); ?>"
                                                    alt="<?php echo htmlspecialchars($related['title']); ?>">
                                            <?php else: ?>
                                                <div class="no-thumb">
                                                    <i class="bi bi-image"></i>
                                                </div>
                                            <?php endif; ?>
                                        </div>
                                        <div class="related-content">
                                            <h6><a href="artikel-detail.php?slug=<?php echo htmlspecialchars($related['slug']); ?>">
                                                    <?php echo htmlspecialchars($related['title']); ?>
                                                </a></h6>
                                            <small class="text-muted">
                                                <?php echo date('d F Y', strtotime($related['created_at'])); ?>
                                            </small>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                        <div class="sidebar-item cta-section">
                            <div class="cta-box text-center">
                                <h5>Butuh Konsultasi IT?</h5>
                                <p>Tim ahli kami siap membantu kebutuhan teknologi bisnis Anda.</p>
                                <a href="kontak.php" class="btn btn-primary">Hubungi Kami</a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>
</main>

<?php include 'includes/footer.php'; ?>