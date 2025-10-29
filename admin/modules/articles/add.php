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
        die('CSRF token mismatch');
    }
    // Validate and sanitize input
    $title = trim($_POST['title'] ?? '');
    $slug = trim($_POST['slug'] ?? '');
    $excerpt = trim($_POST['excerpt'] ?? '');
    $content = $_POST['content'] ?? '';
    $status = $_POST['status'] ?? 'draft';
    $categories = $_POST['categories'] ?? [];
    $errors = [];
    // Validation
    if (empty($title)) {
        $errors[] = "Judul artikel wajib diisi.";
    }
    if (empty($slug)) {
        $slug = generateSlug($title);
    } else {
        $slug = generateSlug($slug);
    }
    if (empty($content)) {
        $errors[] = "Konten artikel wajib diisi.";
    }
    if (!in_array($status, ['published', 'draft'])) {
        $status = 'draft';
    }
    // Check slug uniqueness
    if (!empty($slug)) {
        $stmt = $db->prepare("SELECT id FROM articles WHERE slug = ?");
        $stmt->execute([$slug]);
        if ($stmt->fetch()) {
            $slug = $slug . '-' . time();
        }
    }
    // Handle image upload
    $image_path = null;
    if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
        $upload_result = handleImageUpload($_FILES['image']);
        if ($upload_result['success']) {
            $image_path = $upload_result['filename'];
        } else {
            $errors[] = $upload_result['error'];
        }
    }
    // If no errors, save article
    if (empty($errors)) {
        try {
            $db->beginTransaction();
            // Generate excerpt if empty
            if (empty($excerpt)) {
                $excerpt = generateExcerpt($content);
            }
            // Insert article
            $stmt = $db->prepare("
                INSERT INTO articles (title, slug, content, excerpt, image_path, author_id, status, created_at, updated_at)
                VALUES (?, ?, ?, ?, ?, ?, ?, NOW(), NOW())
            ");
            $stmt->execute([
                $title,
                $slug,
                $content,
                $excerpt,
                $image_path,
                $_SESSION['user_id'],
                $status
            ]);
            $article_id = $db->lastInsertId();
            // Insert categories
            if (!empty($categories)) {
                $stmt = $db->prepare("INSERT INTO article_category_map (article_id, category_id) VALUES (?, ?)");
                foreach ($categories as $category_id) {
                    if (is_numeric($category_id)) {
                        $stmt->execute([$article_id, $category_id]);
                    }
                }
            }
            $db->commit();
            $_SESSION['success_message'] = "Artikel berhasil " . ($status === 'published' ? 'dipublikasikan' : 'disimpan sebagai draft') . ".";
            header('Location: articles.php');
            exit;
        } catch (PDOException $e) {
            $db->rollback();
            error_log("Add article error: " . $e->getMessage());
            $errors[] = "Terjadi kesalahan saat menyimpan artikel.";
        }
    }
}
// Get categories for form
try {
    $stmt = $db->query("SELECT id, name FROM categories ORDER BY name");
    $categories = $stmt->fetchAll();
} catch (PDOException $e) {
    error_log("Get categories error: " . $e->getMessage());
    $categories = [];
}
// Helper functions
function generateSlug(string $text): string
{
    $text = strtolower($text);
    $text = preg_replace('/[^a-z0-9\s-]/', '', $text);
    $text = preg_replace('/[\s-]+/', '-', $text);
    return trim($text, '-');
}
function generateExcerpt(string $content, int $length = 160): string
{
    $text = strip_tags($content);
    if (strlen($text) <= $length) {
        return $text;
    }
    return substr($text, 0, $length) . '...';
}
function handleImageUpload(array $file): array
{
    $allowed_types = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
    $max_size = 2 * 1024 * 1024; // 2MB 
    $upload_dir = __DIR__ . '/../../uploads/articles/';

    // Check file type
    if (!in_array($file['type'], $allowed_types)) {
        return ['success' => false, 'error' => 'Tipe file tidak diizinkan. Gunakan JPG, PNG, GIF, atau WebP.'];
    }
    // Check file size
    if ($file['size'] > $max_size) {
        return ['success' => false, 'error' => 'Ukuran file terlalu besar. Maksimal 2MB.'];
    }
    // Create upload directory if not exists
    if (!is_dir($upload_dir)) {
        mkdir($upload_dir, 0755, true);
    }
    // Generate unique filename
    $extension = pathinfo($file['name'], PATHINFO_EXTENSION);
    $filename = uniqid('article_') . '.' . $extension;
    $filepath = $upload_dir . $filename;
    // Move uploaded file
    if (move_uploaded_file($file['tmp_name'], $filepath)) {
        // Resize image if needed
        resizeImage($filepath, 800, 600);
        return ['success' => true, 'filename' => $filename];
    }
    return ['success' => false, 'error' => 'Gagal mengupload file.'];
}
function resizeImage(string $filepath, int $max_width, int $max_height): void
{
    $image_info = getimagesize($filepath);
    if (!$image_info) return;
    list($original_width, $original_height, $image_type) = $image_info;
    // Calculate new dimensions
    $ratio = min($max_width / $original_width, $max_height / $original_height);
    if ($ratio >= 1) return; // No need to resize
    $new_width = (int)($original_width * $ratio);
    $new_height = (int)($original_height * $ratio);
    // Create image resource
    switch ($image_type) {
        case IMAGETYPE_JPEG:
            $source = imagecreatefromjpeg($filepath);
            break;
        case IMAGETYPE_PNG:
            $source = imagecreatefrompng($filepath);
            break;
        case IMAGETYPE_GIF:
            $source = imagecreatefromgif($filepath);
            break;
        default:
            return;
    }
    // Create new image
    $destination = imagecreatetruecolor($new_width, $new_height);
    // Preserve transparency for PNG and GIF
    if ($image_type == IMAGETYPE_PNG || $image_type == IMAGETYPE_GIF) {
        imagealphablending($destination, false);
        imagesavealpha($destination, true);
    }
    // Resize
    imagecopyresampled($destination, $source, 0, 0, 0, 0, $new_width, $new_height, $original_width, $original_height);
    // Save
    switch ($image_type) {
        case IMAGETYPE_JPEG:
            imagejpeg($destination, $filepath, 85);
            break;
        case IMAGETYPE_PNG:
            imagepng($destination, $filepath);
            break;
        case IMAGETYPE_GIF:
            imagegif($destination, $filepath);
            break;
    }
    // Clean up
    imagedestroy($source);
    imagedestroy($destination);
}
$pageTitle = "Tambah Artikel";
include '../../includes/header.php';
?>
<div class="container-fluid">
    <!-- Page Header -->
    <div class="d-sm-flex align-items-center justify-content-between mb-4">
        <h1 class="h3 mb-0 text-gray-800">
            <i class="fas fa-plus"></i> Tambah Artikel Baru
        </h1>
        <div class="btn-group" role="group">
            <a href="articles.php" class="btn btn-secondary">
                <i class="fas fa-arrow-left"></i> Kembali
            </a>
        </div>
    </div>
    <!-- Error Messages -->
    <?php if (!empty($errors)): ?>
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <i class="fas fa-exclamation-circle"></i>
            <strong>Terjadi kesalahan:</strong>
            <ul class="mb-0 mt-2">
                <?php foreach ($errors as $error): ?>
                    <li><?php echo htmlspecialchars($error); ?></li>
                <?php endforeach; ?>
            </ul>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>
    <form method="POST" enctype="multipart/form-data" id="articleForm">
        <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
        <div class="row">
            <!-- Main Content -->
            <div class="col-lg-8">
                <!-- Basic Information Card -->
                <div class="card shadow mb-4">
                    <div class="card-header py-3">
                        <h6 class="m-0 font-weight-bold text-primary">
                            <i class="fas fa-edit"></i> Informasi Artikel
                        </h6>
                    </div>
                    <div class="card-body">
                        <!-- Title -->
                        <div class="mb-3">
                            <label for="title" class="form-label">Judul Artikel <span class="text-danger">*</span></label>
                            <input type="text"
                                class="form-control"
                                id="title"
                                name="title"
                                value="<?php echo htmlspecialchars($_POST['title'] ?? ''); ?>"
                                maxlength="255"
                                required>
                            <div class="form-text">Maksimal 255 karakter</div>
                        </div>
                        <!-- Slug -->
                        <div class="mb-3">
                            <label for="slug" class="form-label">URL Slug</label>
                            <div class="input-group">
                                <span class="input-group-text">
                                    <i class="fas fa-link"></i>
                                </span>
                                <input type="text"
                                    class="form-control"
                                    id="slug"
                                    name="slug"
                                    value="<?php echo htmlspecialchars($_POST['slug'] ?? ''); ?>"
                                    maxlength="255">
                            </div>
                            <div class="form-text">Kosongkan untuk generate otomatis dari judul</div>
                        </div>
                        <!-- Excerpt -->
                        <div class="mb-3">
                            <label for="excerpt" class="form-label">Ringkasan</label>
                            <textarea class="form-control"
                                id="excerpt"
                                name="excerpt"
                                rows="3"
                                maxlength="500"><?php echo htmlspecialchars($_POST['excerpt'] ?? ''); ?></textarea>
                            <div class="form-text">Maksimal 500 karakter. Kosongkan untuk generate otomatis dari konten.</div>
                        </div>
                        <!-- Content -->
                        <div class="mb-3">
                            <label for="content" class="form-label">Konten Artikel <span class="text-danger">*</span></label>
                            <div id="editor-container" style="height: 400px;">
                                <?php echo $_POST['content'] ?? ''; ?>
                            </div>
                            <textarea name="content" id="content" style="display: none;"></textarea>
                            <div class="form-text">Gunakan editor di atas untuk menulis konten artikel</div>
                        </div>
                    </div>
                </div>
            </div>
            <!-- Sidebar -->
            <div class="col-lg-4">
                <!-- Publish Card -->
                <div class="card shadow mb-4">
                    <div class="card-header py-3">
                        <h6 class="m-0 font-weight-bold text-success">
                            <i class="fas fa-paper-plane"></i> Publikasi
                        </h6>
                    </div>
                    <div class="card-body">
                        <!-- Status -->
                        <div class="mb-3">
                            <label for="status" class="form-label">Status</label>
                            <select class="form-control" id="status" name="status">
                                <option value="draft" <?php echo ($_POST['status'] ?? '') === 'draft' ? 'selected' : ''; ?>>
                                    Draft
                                </option>
                                <option value="published" <?php echo ($_POST['status'] ?? '') === 'published' ? 'selected' : ''; ?>>
                                    Published
                                </option>
                            </select>
                        </div>
                        <!-- Submit Buttons -->
                        <div class="d-grid gap-2">
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-save"></i> Simpan Artikel
                            </button>
                        </div>
                    </div>
                </div>
                <!-- Featured Image Card -->
                <div class="card shadow mb-4">
                    <div class="card-header py-3">
                        <h6 class="m-0 font-weight-bold text-info">
                            <i class="fas fa-image"></i> Gambar Unggulan
                        </h6>
                    </div>
                    <div class="card-body">
                        <div class="mb-3">
                            <input type="file"
                                class="form-control"
                                id="image"
                                name="image"
                                accept="image/*">
                            <div class="form-text">Format: JPG, PNG, GIF, WebP. Maksimal 2MB.</div>
                        </div>
                        <div id="image-preview" class="text-center" style="display: none;">
                            <img id="preview-img" src="" alt="Preview" class="img-fluid rounded" style="max-height: 200px;">
                            <div class="mt-2">
                                <button type="button" class="btn btn-sm btn-danger" onclick="removePreview()">
                                    <i class="fas fa-trash"></i> Hapus
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
                <!-- Categories Card -->
                <div class="card shadow mb-4">
                    <div class="card-header py-3">
                        <h6 class="m-0 font-weight-bold text-warning">
                            <i class="fas fa-tags"></i> Kategori
                        </h6>
                    </div>
                    <div class="card-body">
                        <?php if (!empty($categories)): ?>
                            <?php foreach ($categories as $category): ?>
                                <div class="form-check">
                                    <input class="form-check-input"
                                        type="checkbox"
                                        name="categories[]"
                                        value="<?php echo $category['id']; ?>"
                                        id="cat_<?php echo $category['id']; ?>"
                                        <?php echo in_array($category['id'], $_POST['categories'] ?? []) ? 'checked' : ''; ?>>
                                    <label class="form-check-label" for="cat_<?php echo $category['id']; ?>">
                                        <?php echo htmlspecialchars($category['name']); ?>
                                    </label>
                                </div>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <p class="text-muted">Belum ada kategori.</p>
                            <a href="categories.php" class="btn btn-sm btn-outline-primary">
                                <i class="fas fa-plus"></i> Tambah Kategori
                            </a>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </form>
</div>
<!-- Quill Editor Scripts -->
<script src="https://cdnjs.cloudflare.com/ajax/libs/quill/1.3.7/quill.min.js"></script>
<link href="https://cdnjs.cloudflare.com/ajax/libs/quill/1.3.7/quill.snow.min.css" rel="stylesheet">
<script>
    document.addEventListener('DOMContentLoaded', function() {
        // Initialize Quill editor
        const quill = new Quill('#editor-container', {
            theme: 'snow',
            modules: {
                toolbar: [
                    [{
                        'header': [1, 2, 3, 4, 5, 6, false]
                    }],
                    ['bold', 'italic', 'underline', 'strike'],
                    [{
                        'color': []
                    }, {
                        'background': []
                    }],
                    [{
                        'align': []
                    }],
                    ['blockquote', 'code-block'],
                    [{
                        'list': 'ordered'
                    }, {
                        'list': 'bullet'
                    }],
                    ['link', 'image'],
                    ['clean']
                ]
            }
        });
        // Auto-generate slug from title
        document.getElementById('title').addEventListener('input', function() {
            const title = this.value;
            const slugField = document.getElementById('slug');
            if (!slugField.value) {
                slugField.value = generateSlug(title);
            }
        });
        // Image preview
        document.getElementById('image').addEventListener('change', function(e) {
            const file = e.target.files[0];
            if (file) {
                const reader = new FileReader();
                reader.onload = function(e) {
                    document.getElementById('preview-img').src = e.target.result;
                    document.getElementById('image-preview').style.display = 'block';
                };
                reader.readAsDataURL(file);
            }
        });
        // Form submission
        document.getElementById('articleForm').addEventListener('submit', function(e) {
            const content = quill.root.innerHTML;
            if (!content.trim() || content === '<p><br></p>') {
                e.preventDefault();
                alert('Konten artikel wajib diisi');
                return;
            }
            document.getElementById('content').value = content;
            if (!document.getElementById('title').value.trim()) {
                e.preventDefault();
                alert('Judul artikel wajib diisi');
                return;
            }
        });
    });

    function generateSlug(text) {
        return text
            .toLowerCase()
            .replace(/[^a-z0-9\s-]/g, '')
            .replace(/[\s-]+/g, '-')
            .replace(/^-+|-+$/g, '');
    }

    function removePreview() {
        document.getElementById('image').value = '';
        document.getElementById('image-preview').style.display = 'none';
    }
</script>
<?php include '../../includes/footer.php'; ?>