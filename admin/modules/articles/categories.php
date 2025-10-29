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

// Generate CSRF token
if (!isset($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // CSRF validation
    if (!hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'] ?? '')) {
        $_SESSION['error_message'] = "Token CSRF tidak valid.";
    } else {
        $action = $_POST['action'] ?? '';

        switch ($action) {
            case 'add':
                $name = trim($_POST['name'] ?? '');

                if (empty($name)) {
                    $_SESSION['error_message'] = "Nama kategori wajib diisi.";
                } else {
                    try {
                        // Check if category exists
                        $stmt = $db->prepare("SELECT id FROM categories WHERE name = ?");
                        $stmt->execute([$name]);
                        if ($stmt->fetch()) {
                            $_SESSION['error_message'] = "Kategori dengan nama tersebut sudah ada.";
                        } else {
                            // Add category
                            $stmt = $db->prepare("INSERT INTO categories (name) VALUES (?)");
                            $stmt->execute([$name]);
                            $_SESSION['success_message'] = "Kategori berhasil ditambahkan.";
                        }
                    } catch (PDOException $e) {
                        error_log("Add category error: " . $e->getMessage());
                        $_SESSION['error_message'] = "Terjadi kesalahan saat menambahkan kategori.";
                    }
                }
                break;

            case 'edit':
                $category_id = (int)($_POST['category_id'] ?? 0);
                $name = trim($_POST['name'] ?? '');

                if (empty($name)) {
                    $_SESSION['error_message'] = "Nama kategori wajib diisi.";
                } elseif ($category_id <= 0) {
                    $_SESSION['error_message'] = "ID kategori tidak valid.";
                } else {
                    try {
                        // Check if category exists (exclude current)
                        $stmt = $db->prepare("SELECT id FROM categories WHERE name = ? AND id != ?");
                        $stmt->execute([$name, $category_id]);
                        if ($stmt->fetch()) {
                            $_SESSION['error_message'] = "Kategori dengan nama tersebut sudah ada.";
                        } else {
                            // Update category
                            $stmt = $db->prepare("UPDATE categories SET name = ? WHERE id = ?");
                            $stmt->execute([$name, $category_id]);
                            $_SESSION['success_message'] = "Kategori berhasil diperbarui.";
                        }
                    } catch (PDOException $e) {
                        error_log("Update category error: " . $e->getMessage());
                        $_SESSION['error_message'] = "Terjadi kesalahan saat memperbarui kategori.";
                    }
                }
                break;
        }
    }

    header('Location: categories.php');
    exit;
}

// Handle delete
if (isset($_GET['delete']) && is_numeric($_GET['delete'])) {
    $category_id = (int)$_GET['delete'];

    try {
        // Check if category is used
        $stmt = $db->prepare("SELECT COUNT(*) FROM article_category_map WHERE category_id = ?");
        $stmt->execute([$category_id]);
        $usage_count = $stmt->fetchColumn();

        if ($usage_count > 0) {
            $_SESSION['error_message'] = "Kategori tidak dapat dihapus karena masih digunakan oleh $usage_count artikel.";
        } else {
            // Delete category
            $stmt = $db->prepare("DELETE FROM categories WHERE id = ?");
            $stmt->execute([$category_id]);
            $_SESSION['success_message'] = "Kategori berhasil dihapus.";
        }
    } catch (PDOException $e) {
        error_log("Delete category error: " . $e->getMessage());
        $_SESSION['error_message'] = "Terjadi kesalahan saat menghapus kategori.";
    }

    header('Location: categories.php');
    exit;
}

// Get categories with article count
try {
    $stmt = $db->query("
        SELECT 
            c.id,
            c.name,
            COUNT(acm.article_id) as article_count
        FROM categories c
        LEFT JOIN article_category_map acm ON c.id = acm.category_id
        GROUP BY c.id, c.name
        ORDER BY c.name
    ");
    $categories = $stmt->fetchAll();
} catch (PDOException $e) {
    error_log("Get categories error: " . $e->getMessage());
    $categories = [];
}

$pageTitle = "Kelola Kategori";
include '../../includes/header.php';
?>

<div class="container-fluid">
    <!-- Page Header -->
    <div class="d-sm-flex align-items-center justify-content-between mb-4">
        <h1 class="h3 mb-0 text-gray-800">
            <i class="fas fa-tags"></i> Kelola Kategori
        </h1>
        <div class="btn-group" role="group">
            <a href="articles.php" class="btn btn-secondary">
                <i class="fas fa-arrow-left"></i> Kembali ke Artikel
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

    <div class="row">
        <!-- Add Category Form -->
        <div class="col-lg-4">
            <div class="card shadow mb-4">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">
                        <i class="fas fa-plus"></i> Tambah Kategori Baru
                    </h6>
                </div>
                <div class="card-body">
                    <form method="POST" id="addCategoryForm">
                        <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
                        <input type="hidden" name="action" value="add">

                        <div class="mb-3">
                            <label for="name" class="form-label">Nama Kategori <span class="text-danger">*</span></label>
                            <input type="text"
                                class="form-control"
                                id="name"
                                name="name"
                                maxlength="100"
                                required>
                            <div class="form-text">Maksimal 100 karakter</div>
                        </div>

                        <div class="d-grid">
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-plus"></i> Tambah Kategori
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <!-- Categories List -->
        <div class="col-lg-8">
            <div class="card shadow mb-4">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">
                        <i class="fas fa-list"></i> Daftar Kategori (<?php echo count($categories); ?>)
                    </h6>
                </div>
                <div class="card-body">
                    <?php if (!empty($categories)): ?>
                        <div class="table-responsive">
                            <table class="table table-bordered table-hover">
                                <thead class="table-light">
                                    <tr>
                                        <th>Nama Kategori</th>
                                        <th width="120">Jumlah Artikel</th>
                                        <th width="120">Aksi</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($categories as $category): ?>
                                        <tr id="category-<?php echo $category['id']; ?>">
                                            <td>
                                                <span class="category-name">
                                                    <?php echo htmlspecialchars($category['name']); ?>
                                                </span>
                                                <div class="edit-form" style="display: none;">
                                                    <form method="POST" class="d-flex">
                                                        <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
                                                        <input type="hidden" name="action" value="edit">
                                                        <input type="hidden" name="category_id" value="<?php echo $category['id']; ?>">
                                                        <input type="text"
                                                            class="form-control form-control-sm me-2"
                                                            name="name"
                                                            value="<?php echo htmlspecialchars($category['name']); ?>"
                                                            maxlength="100"
                                                            required>
                                                        <button type="submit" class="btn btn-sm btn-success me-1">
                                                            <i class="fas fa-check"></i>
                                                        </button>
                                                        <button type="button" class="btn btn-sm btn-secondary" onclick="cancelEdit(<?php echo $category['id']; ?>)">
                                                            <i class="fas fa-times"></i>
                                                        </button>
                                                    </form>
                                                </div>
                                            </td>
                                            <td class="text-center">
                                                <span class="badge bg-info">
                                                    <?php echo $category['article_count']; ?>
                                                </span>
                                            </td>
                                            <td>
                                                <div class="btn-group btn-group-sm" role="group">
                                                    <button type="button"
                                                        class="btn btn-outline-primary"
                                                        onclick="editCategory(<?php echo $category['id']; ?>)"
                                                        title="Edit">
                                                        <i class="fas fa-edit"></i>
                                                    </button>
                                                    <?php if ($category['article_count'] == 0): ?>
                                                        <a href="?delete=<?php echo $category['id']; ?>"
                                                            class="btn btn-outline-danger"
                                                            title="Hapus"
                                                            onclick="return confirm('Yakin ingin menghapus kategori ini?')">
                                                            <i class="fas fa-trash"></i>
                                                        </a>
                                                    <?php else: ?>
                                                        <button type="button"
                                                            class="btn btn-outline-danger"
                                                            title="Tidak dapat dihapus - masih digunakan"
                                                            disabled>
                                                            <i class="fas fa-trash"></i>
                                                        </button>
                                                    <?php endif; ?>
                                                </div>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php else: ?>
                        <!-- Empty State -->
                        <div class="text-center py-5">
                            <i class="fas fa-tags fa-3x text-muted mb-3"></i>
                            <h5 class="text-muted">Belum ada kategori</h5>
                            <p class="text-muted">Mulai dengan membuat kategori pertama Anda.</p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
    function editCategory(categoryId) {
        const row = document.getElementById('category-' + categoryId);
        const nameSpan = row.querySelector('.category-name');
        const editForm = row.querySelector('.edit-form');

        nameSpan.style.display = 'none';
        editForm.style.display = 'block';

        // Focus on input
        const input = editForm.querySelector('input[name="name"]');
        input.focus();
        input.select();
    }

    function cancelEdit(categoryId) {
        const row = document.getElementById('category-' + categoryId);
        const nameSpan = row.querySelector('.category-name');
        const editForm = row.querySelector('.edit-form');

        nameSpan.style.display = 'block';
        editForm.style.display = 'none';
    }

    // Auto-focus on add form after page load
    document.addEventListener('DOMContentLoaded', function() {
        document.getElementById('name').focus();
    });
</script>

<?php include '../../includes/footer.php'; ?>