<?php

declare(strict_types=1);
// session_start();
// Include required files
require_once '../../config/database.php';
require_once '../../config/auth.php';
// Require authentication
requireAuth();
// Get database connection
$db = Database::getInstance()->getConnection();
// Handle bulk actions
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['bulk_action'])) {
    // CSRF validation
    if (!hash_equals($_SESSION['csrf_token'] ?? '', $_POST['csrf_token'] ?? '')) {
        $_SESSION['error_message'] = "Token CSRF tidak valid.";
    } else {
        $action = $_POST['bulk_action'];
        $selected_ids = $_POST['selected_articles'] ?? [];
        if (!empty($selected_ids) && in_array($action, ['delete', 'publish', 'draft'])) {
            $placeholders = str_repeat('?,', count($selected_ids) - 1) . '?';
            try {
                switch ($action) {
                    case 'delete':
                        // Get image paths for deletion
                        $stmt = $db->prepare("SELECT image_path FROM articles WHERE id IN ($placeholders)");
                        $stmt->execute($selected_ids);
                        $articles_to_delete = $stmt->fetchAll();
                        // Delete from category mapping first
                        $stmt = $db->prepare("DELETE FROM article_category_map WHERE article_id IN ($placeholders)");
                        $stmt->execute($selected_ids);
                        // Delete articles
                        $stmt = $db->prepare("DELETE FROM articles WHERE id IN ($placeholders)");
                        $stmt->execute($selected_ids);
                        // Delete image files if they exist
                        foreach ($articles_to_delete as $article) {
                            if ($article['image_path']) {
                                $image_path = '../../uploads/articles/' . $article['image_path'];
                                if (file_exists($image_path)) {
                                    unlink($image_path);
                                }
                            }
                        }
                        $_SESSION['success_message'] = count($selected_ids) . " artikel berhasil dihapus.";
                        break;
                    case 'publish':
                        $stmt = $db->prepare("UPDATE articles SET status = 'published', updated_at = NOW() WHERE id IN ($placeholders)");
                        $stmt->execute($selected_ids);
                        $_SESSION['success_message'] = count($selected_ids) . " artikel berhasil dipublikasikan.";
                        break;
                    case 'draft':
                        $stmt = $db->prepare("UPDATE articles SET status = 'draft', updated_at = NOW() WHERE id IN ($placeholders)");
                        $stmt->execute($selected_ids);
                        $_SESSION['success_message'] = count($selected_ids) . " artikel berhasil diubah ke draft.";
                        break;
                }
            } catch (PDOException $e) {
                error_log("Bulk action error: " . $e->getMessage());
                $_SESSION['error_message'] = "Terjadi kesalahan saat memproses aksi bulk.";
            }
        }
    }
    header('Location: articles.php');
    exit;
}
// Handle single delete
if (isset($_GET['delete']) && is_numeric($_GET['delete'])) {
    $article_id = (int)$_GET['delete'];
    try {
        $db->beginTransaction();
        // Get image path for deletion
        $stmt = $db->prepare("SELECT image_path FROM articles WHERE id = ?");
        $stmt->execute([$article_id]);
        $article = $stmt->fetch();
        // Delete from category mapping first
        $stmt = $db->prepare("DELETE FROM article_category_map WHERE article_id = ?");
        $stmt->execute([$article_id]);
        // Delete article
        $stmt = $db->prepare("DELETE FROM articles WHERE id = ?");
        $stmt->execute([$article_id]);
        $db->commit();
        // Delete image file if exists
        if ($article && $article['image_path']) {
            $image_path = '../../uploads/articles/' . $article['image_path'];
            if (file_exists($image_path)) {
                unlink($image_path);
            }
        }
        $_SESSION['success_message'] = "Artikel berhasil dihapus.";
    } catch (PDOException $e) {
        $db->rollback();
        error_log("Delete article error: " . $e->getMessage());
        $_SESSION['error_message'] = "Terjadi kesalahan saat menghapus artikel.";
    }
    header('Location: articles.php');
    exit;
}
// Pagination and search setup
$page = max(1, (int)($_GET['page'] ?? 1));
$per_page = 10;
$offset = ($page - 1) * $per_page;
$search = trim($_GET['search'] ?? '');
$status_filter = $_GET['status'] ?? '';
$category_filter = $_GET['category'] ?? '';
// Build WHERE clause
$where_conditions = [];
$params = [];
if (!empty($search)) {
    $where_conditions[] = "(a.title LIKE ? OR a.content LIKE ?)";
    $params[] = "%$search%";
    $params[] = "%$search%";
}
if (!empty($status_filter)) {
    $where_conditions[] = "a.status = ?";
    $params[] = $status_filter;
}
if (!empty($category_filter)) {
    $where_conditions[] = "EXISTS (SELECT 1 FROM article_category_map acm WHERE acm.article_id = a.id AND acm.category_id = ?)";
    $params[] = $category_filter;
}
$where_clause = !empty($where_conditions) ? 'WHERE ' . implode(' AND ', $where_conditions) : '';
// Get total count
$count_sql = "
    SELECT COUNT(DISTINCT a.id) as total
    FROM articles a
    $where_clause
";
try {
    $stmt = $db->prepare($count_sql);
    $stmt->execute($params);
    $total_articles = $stmt->fetchColumn();
    $total_pages = ceil($total_articles / $per_page);
} catch (PDOException $e) {
    error_log("Count articles error: " . $e->getMessage());
    $total_articles = 0;
    $total_pages = 1;
}
// Get articles
$sql = "
    SELECT 
        a.*,
        u.username as author_name,
        GROUP_CONCAT(c.name SEPARATOR ', ') as categories
    FROM articles a
    LEFT JOIN users u ON a.author_id = u.id
    LEFT JOIN article_category_map acm ON a.id = acm.article_id
    LEFT JOIN categories c ON acm.category_id = c.id
    $where_clause
    GROUP BY a.id
    ORDER BY a.created_at DESC
    LIMIT $per_page OFFSET $offset
";
try {
    $stmt = $db->prepare($sql);
    $stmt->execute($params);
    $articles = $stmt->fetchAll();
} catch (PDOException $e) {
    error_log("Get articles error: " . $e->getMessage());
    $articles = [];
}
// Get categories for filter
try {
    $stmt = $db->query("SELECT id, name FROM categories ORDER BY name");
    $categories = $stmt->fetchAll();
} catch (PDOException $e) {
    error_log("Get categories error: " . $e->getMessage());
    $categories = [];
}
// Generate CSRF token
if (!isset($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}
$pageTitle = "Kelola Artikel";
include '../../includes/header.php';
?>
<div class="container-fluid">
    <!-- Page Header -->
    <div class="d-sm-flex align-items-center justify-content-between mb-4">
        <h1 class="h3 mb-0 text-gray-800">
            <i class="fas fa-newspaper"></i> Kelola Artikel
        </h1>
        <div class="btn-group" role="group">
            <a href="add.php" class="btn btn-primary">
                <i class="fas fa-plus"></i> Tambah Artikel
            </a>
            <a href="categories.php" class="btn btn-secondary">
                <i class="fas fa-tags"></i> Kategori
            </a>
        </div>
    </div>
    <!-- Success/Error Messages -->
    <?php if (isset($_SESSION['success_message'])): ?>
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <i class="fas fa-check-circle"></i>
            <?php echo htmlspecialchars($_SESSION['success_message']); ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
        <?php unset($_SESSION['success_message']); ?>
    <?php endif; ?>
    <?php if (isset($_SESSION['error_message'])): ?>
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <i class="fas fa-exclamation-circle"></i>
            <?php echo htmlspecialchars($_SESSION['error_message']); ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
        <?php unset($_SESSION['error_message']); ?>
    <?php endif; ?>
    <!-- Filters Card -->
    <div class="card shadow mb-4">
        <div class="card-body">
            <form method="GET" class="row g-3">
                <div class="col-md-4">
                    <label for="search" class="form-label">Pencarian</label>
                    <input type="text"
                        class="form-control"
                        id="search"
                        name="search"
                        value="<?php echo htmlspecialchars($search); ?>"
                        placeholder="Cari judul atau konten...">
                </div>
                <div class="col-md-3">
                    <label for="status" class="form-label">Status</label>
                    <select class="form-control" id="status" name="status">
                        <option value="">Semua Status</option>
                        <option value="published" <?php echo $status_filter === 'published' ? 'selected' : ''; ?>>
                            Published
                        </option>
                        <option value="draft" <?php echo $status_filter === 'draft' ? 'selected' : ''; ?>>
                            Draft
                        </option>
                    </select>
                </div>
                <div class="col-md-3">
                    <label for="category" class="form-label">Kategori</label>
                    <select class="form-control" id="category" name="category">
                        <option value="">Semua Kategori</option>
                        <?php foreach ($categories as $category): ?>
                            <option value="<?php echo $category['id']; ?>"
                                <?php echo $category_filter == $category['id'] ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($category['name']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label">&nbsp;</label>
                    <div class="d-grid gap-2">
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-search"></i> Filter
                        </button>
                        <a href="articles.php" class="btn btn-secondary">
                            <i class="fas fa-times"></i> Reset
                        </a>
                    </div>
                </div>
            </form>
        </div>
    </div>
    <!-- Articles Card -->
    <div class="card shadow mb-4">
        <div class="card-header py-3 d-flex flex-row align-items-center justify-content-between">
            <h6 class="m-0 font-weight-bold text-primary">
                <i class="fas fa-list"></i> Daftar Artikel (<?php echo $total_articles; ?>)
            </h6>
        </div>
        <div class="card-body">
            <?php if (!empty($articles)): ?>
                <form method="POST" id="bulkForm">
                    <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
                    <!-- Bulk Actions -->
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <div class="input-group">
                                <select class="form-control" name="bulk_action" required>
                                    <option value="">Pilih Aksi</option>
                                    <option value="publish">Publikasikan</option>
                                    <option value="draft">Jadikan Draft</option>
                                    <option value="delete">Hapus</option>
                                </select>
                                <button type="submit" class="btn btn-secondary" onclick="return confirmBulkAction()">
                                    <i class="fas fa-play"></i> Jalankan
                                </button>
                            </div>
                        </div>
                        <div class="col-md-6 text-end">
                            <button type="button" class="btn btn-sm btn-outline-primary" onclick="selectAll()">
                                Pilih Semua
                            </button>
                            <button type="button" class="btn btn-sm btn-outline-secondary" onclick="selectNone()">
                                Batal Pilih
                            </button>
                        </div>
                    </div>
                    <!-- Articles Table -->
                    <div class="table-responsive">
                        <table class="table table-bordered table-hover">
                            <thead class="table-light">
                                <tr>
                                    <th width="40">
                                        <input type="checkbox" id="selectAllCheckbox" onchange="toggleSelectAll()">
                                    </th>
                                    <th width="80">Gambar</th>
                                    <th>Judul</th>
                                    <th width="120">Status</th>
                                    <th width="150">Kategori</th>
                                    <th width="120">Penulis</th>
                                    <th width="120">Tanggal</th>
                                    <th width="120">Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($articles as $article): ?>
                                    <tr>
                                        <td>
                                            <input type="checkbox"
                                                name="selected_articles[]"
                                                value="<?php echo $article['id']; ?>"
                                                class="article-checkbox">
                                        </td>
                                        <td>
                                            <?php if ($article['image_path']): ?>
                                                <img src="../../uploads/articles/<?php echo htmlspecialchars($article['image_path']); ?>"
                                                    alt="Article Image"
                                                    class="img-thumbnail"
                                                    style="width: 60px; height: 40px; object-fit: cover;">
                                            <?php else: ?>
                                                <div class="bg-light d-flex align-items-center justify-content-center"
                                                    style="width: 60px; height: 40px;">
                                                    <i class="fas fa-image text-muted"></i>
                                                </div>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <div>
                                                <strong><?php echo htmlspecialchars($article['title']); ?></strong>
                                                <?php if ($article['excerpt']): ?>
                                                    <br><small class="text-muted">
                                                        <?php echo htmlspecialchars(substr($article['excerpt'], 0, 100)); ?>
                                                        <?php echo strlen($article['excerpt']) > 100 ? '...' : ''; ?>
                                                    </small>
                                                <?php endif; ?>
                                            </div>
                                        </td>
                                        <td>
                                            <?php if ($article['status'] === 'published'): ?>
                                                <span class="badge bg-success">
                                                    <i class="fas fa-check"></i> Published
                                                </span>
                                            <?php else: ?>
                                                <span class="badge bg-warning">
                                                    <i class="fas fa-edit"></i> Draft
                                                </span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <?php if ($article['categories']): ?>
                                                <small class="text-muted">
                                                    <?php echo htmlspecialchars($article['categories']); ?>
                                                </small>
                                            <?php else: ?>
                                                <small class="text-muted">-</small>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <small><?php echo htmlspecialchars($article['author_name'] ?? 'Unknown'); ?></small>
                                        </td>
                                        <td>
                                            <small>
                                                <?php echo date('d/m/Y', strtotime($article['created_at'])); ?>
                                                <br>
                                                <span class="text-muted">
                                                    <?php echo date('H:i', strtotime($article['created_at'])); ?>
                                                </span>
                                            </small>
                                        </td>
                                        <td>
                                            <div class="btn-group btn-group-sm" role="group">
                                                <a href="edit.php?id=<?php echo $article['id']; ?>"
                                                    class="btn btn-outline-primary"
                                                    title="Edit">
                                                    <i class="fas fa-edit"></i>
                                                </a>
                                                <a href="?delete=<?php echo $article['id']; ?>"
                                                    class="btn btn-outline-danger"
                                                    title="Hapus"
                                                    onclick="return confirm('Yakin ingin menghapus artikel ini?')">
                                                    <i class="fas fa-trash"></i>
                                                </a>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </form>
                <!-- Pagination -->
                <?php if ($total_pages > 1): ?>
                    <nav aria-label="Page navigation">
                        <ul class="pagination justify-content-center">
                            <!-- Previous Page -->
                            <?php if ($page > 1): ?>
                                <li class="page-item">
                                    <a class="page-link" href="?page=<?php echo $page - 1; ?>&search=<?php echo urlencode($search); ?>&status=<?php echo urlencode($status_filter); ?>&category=<?php echo urlencode($category_filter); ?>">
                                        <i class="fas fa-chevron-left"></i> Previous
                                    </a>
                                </li>
                            <?php endif; ?>
                            <!-- Page Numbers -->
                            <?php
                            $start_page = max(1, $page - 2);
                            $end_page = min($total_pages, $page + 2);
                            for ($i = $start_page; $i <= $end_page; $i++):
                            ?>
                                <li class="page-item <?php echo $i === $page ? 'active' : ''; ?>">
                                    <a class="page-link" href="?page=<?php echo $i; ?>&search=<?php echo urlencode($search); ?>&status=<?php echo urlencode($status_filter); ?>&category=<?php echo urlencode($category_filter); ?>">
                                        <?php echo $i; ?>
                                    </a>
                                </li>
                            <?php endfor; ?>
                            <!-- Next Page -->
                            <?php if ($page < $total_pages): ?>
                                <li class="page-item">
                                    <a class="page-link" href="?page=<?php echo $page + 1; ?>&search=<?php echo urlencode($search); ?>&status=<?php echo urlencode($status_filter); ?>&category=<?php echo urlencode($category_filter); ?>">
                                        Next <i class="fas fa-chevron-right"></i>
                                    </a>
                                </li>
                            <?php endif; ?>
                        </ul>
                    </nav>
                <?php endif; ?>
            <?php else: ?>
                <!-- Empty State -->
                <div class="text-center py-5">
                    <i class="fas fa-newspaper fa-3x text-muted mb-3"></i>
                    <h5 class="text-muted">Belum ada artikel</h5>
                    <p class="text-muted">Mulai dengan membuat artikel pertama Anda.</p>
                    <a href="add.php" class="btn btn-primary">
                        <i class="fas fa-plus"></i> Tambah Artikel
                    </a>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>
<script>
    document.addEventListener('DOMContentLoaded', function() {
        // Auto-submit form when filter changes
        document.getElementById('status').addEventListener('change', function() {
            this.form.submit();
        });
        document.getElementById('category').addEventListener('change', function() {
            this.form.submit();
        });
    });

    function toggleSelectAll() {
        const selectAllCheckbox = document.getElementById('selectAllCheckbox');
        const checkboxes = document.querySelectorAll('.article-checkbox');
        checkboxes.forEach(checkbox => {
            checkbox.checked = selectAllCheckbox.checked;
        });
    }

    function selectAll() {
        const checkboxes = document.querySelectorAll('.article-checkbox');
        checkboxes.forEach(checkbox => {
            checkbox.checked = true;
        });
        document.getElementById('selectAllCheckbox').checked = true;
    }

    function selectNone() {
        const checkboxes = document.querySelectorAll('.article-checkbox');
        checkboxes.forEach(checkbox => {
            checkbox.checked = false;
        });
        document.getElementById('selectAllCheckbox').checked = false;
    }

    function confirmBulkAction() {
        const selectedCheckboxes = document.querySelectorAll('.article-checkbox:checked');
        const action = document.querySelector('select[name="bulk_action"]').value;
        if (selectedCheckboxes.length === 0) {
            alert('Pilih minimal satu artikel.');
            return false;
        }
        if (!action) {
            alert('Pilih aksi yang akan dilakukan.');
            return false;
        }
        let confirmMessage = '';
        switch (action) {
            case 'delete':
                confirmMessage = `Yakin ingin menghapus ${selectedCheckboxes.length} artikel yang dipilih?`;
                break;
            case 'publish':
                confirmMessage = `Yakin ingin mempublikasikan ${selectedCheckboxes.length} artikel yang dipilih?`;
                break;
            case 'draft':
                confirmMessage = `Yakin ingin mengubah ${selectedCheckboxes.length} artikel yang dipilih menjadi draft?`;
                break;
        }
        return confirm(confirmMessage);
    }
</script>
<?php include '../../includes/footer.php'; ?>