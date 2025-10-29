<?php

declare(strict_types=1);

require_once '../../config/database.php';
require_once '../../config/auth.php';
require_once '../../includes/functions.php';

// Check authentication
requireAuth();

$db = Database::getInstance()->getConnection();
$pageTitle = 'Edit Produk';

// Initialize variables
$errors = [];
$success_message = '';
$product = null;
$categories = [];

// Validate product ID from URL
$product_id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
if (!$product_id || $product_id <= 0) {
    $_SESSION['error_message'] = 'ID produk tidak valid.';
    header('Location: products.php');
    exit;
}

try {
    // Get product data
    $stmt = $db->prepare("SELECT * FROM products WHERE id = ?");
    $stmt->execute([$product_id]);
    $product = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$product) {
        $_SESSION['error_message'] = 'Produk dengan ID ' . $product_id . ' tidak ditemukan.';
        header('Location: products.php');
        exit;
    }

    // GUNAKAN ID DARI DATABASE, BUKAN DARI URL
    $actual_product_id = (int)$product['id'];
    $old_image_path = $product['image_path'];

    // Get categories for dropdown
    $categories_stmt = $db->query("SELECT id, name FROM product_categories ORDER BY name ASC");
    $categories = $categories_stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    error_log("Database error in edit_product.php: " . $e->getMessage());
    $_SESSION['error_message'] = 'Terjadi kesalahan database. Silakan coba lagi.';
    header('Location: products.php');
    exit;
}

// Process form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    // Validate CSRF token
    if (!isset($_SESSION['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'] ?? '')) {
        $errors[] = 'Token keamanan tidak valid. Silakan refresh halaman dan coba lagi.';
    } else {

        // Sanitize and validate input
        $name = trim($_POST['name'] ?? '');
        $description = trim($_POST['description'] ?? '');
        $category_id = filter_input(INPUT_POST, 'category_id', FILTER_VALIDATE_INT);
        $tags = trim($_POST['tags'] ?? '');
        $stock = filter_input(INPUT_POST, 'stock', FILTER_VALIDATE_INT);

        // Validation
        if (empty($name)) {
            $errors[] = 'Nama produk harus diisi.';
        } elseif (strlen($name) > 255) {
            $errors[] = 'Nama produk tidak boleh lebih dari 255 karakter.';
        }

        if (empty($description)) {
            $errors[] = 'Deskripsi produk harus diisi.';
        }

        if ($stock === false || $stock < 0) {
            $errors[] = 'Stok harus berupa angka dan tidak boleh negatif.';
        }

        if ($category_id === false) {
            $category_id = null;
        }

        // Handle image upload/update
        $image_path = $old_image_path; // Keep existing image by default
        $upload_dir = __DIR__ . '/../../uploads/products/';

        // Create upload directory if it doesn't exist
        if (!is_dir($upload_dir)) {
            if (!mkdir($upload_dir, 0755, true)) {
                $errors[] = 'Gagal membuat direktori upload.';
            }
        }

        // Handle file upload from normal input
        if (isset($_FILES['image_file']) && $_FILES['image_file']['error'] === UPLOAD_ERR_OK) {
            // Validate file type
            $file_extension = strtolower(pathinfo($_FILES['image_file']['name'], PATHINFO_EXTENSION));
            $allowed_extensions = ['jpg', 'jpeg', 'png', 'gif'];

            if (in_array($file_extension, $allowed_extensions)) {
                // Generate unique filename using database ID
                $new_filename = 'product_' . $actual_product_id . '_' . time() . '.' . $file_extension;
                $upload_path = $upload_dir . $new_filename;

                if (move_uploaded_file($_FILES['image_file']['tmp_name'], $upload_path)) {
                    // Delete old image if exists
                    if (!empty($old_image_path) && $old_image_path !== 'placeholder.jpg') {
                        $old_file_path = $upload_dir . basename($old_image_path);
                        if (file_exists($old_file_path)) {
                            unlink($old_file_path);
                        }
                    }
                    $image_path = $new_filename;
                } else {
                    $errors[] = 'Gagal mengupload gambar baru.';
                }
            } else {
                $errors[] = 'Format gambar tidak valid. Gunakan JPG, JPEG, PNG, atau GIF.';
            }
        }
        // Handle cropped image from JavaScript
        elseif (!empty($_POST['cropped_image'])) {
            $cropped_data = $_POST['cropped_image'];

            // Validate base64 image data
            if (preg_match('/^data:image\/(jpeg|jpg|png|gif);base64,(.+)$/', $cropped_data, $matches)) {
                $image_data = base64_decode($matches[2]);

                if ($image_data !== false) {
                    $file_extension = ($matches[1] === 'jpeg') ? 'jpg' : $matches[1];
                    // GUNAKAN ID DARI DATABASE
                    $filename = 'product_' . $actual_product_id . '_' . time() . '.' . $file_extension;
                    $file_path = $upload_dir . $filename;

                    if (file_put_contents($file_path, $image_data)) {
                        // Delete old image if it exists and is not the default
                        if (!empty($old_image_path) && $old_image_path !== 'placeholder.jpg') {
                            $old_file = $upload_dir . basename($old_image_path);
                            if (file_exists($old_file)) {
                                unlink($old_file);
                            }
                        }
                        $image_path = $filename;
                    } else {
                        $errors[] = 'Gagal menyimpan gambar. Periksa permission folder.';
                    }
                } else {
                    $errors[] = 'Data gambar tidak valid.';
                }
            } else {
                $errors[] = 'Format gambar tidak valid.';
            }
        }

        // Handle image removal
        if (isset($_POST['remove_image']) && $_POST['remove_image'] === '1') {
            if (!empty($old_image_path) && $old_image_path !== 'placeholder.jpg') {
                $old_file = $upload_dir . basename($old_image_path);
                if (file_exists($old_file)) {
                    unlink($old_file);
                }
            }
            $image_path = null;
        }

        // If no validation errors, update the product
        if (empty($errors)) {
            try {
                $update_sql = "UPDATE products 
                      SET name = ?, description = ?, category_id = ?, tags = ?, stock = ?, image_path = ?, updated_at = NOW()
                      WHERE id = ?";

                $update_stmt = $db->prepare($update_sql);
                $update_result = $update_stmt->execute([
                    $name,
                    $description,
                    $category_id,
                    $tags,
                    $stock,
                    $image_path,
                    $actual_product_id  // GUNAKAN ID DARI DATABASE
                ]);

                if ($update_result) {
                    $_SESSION['success_message'] = 'Produk "' . htmlspecialchars($name) . '" berhasil diperbarui.';
                    header('Location: products.php');
                    exit;
                } else {
                    $errors[] = 'Gagal memperbarui produk. Silakan coba lagi.';
                }
            } catch (PDOException $e) {
                error_log("Update error in edit_product.php: " . $e->getMessage());
                $errors[] = 'Terjadi kesalahan saat memperbarui data. Silakan coba lagi.';
            }
        }
    }
}

// Generate CSRF token
if (!isset($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

include '../../includes/header.php';
?>

<link href="https://cdnjs.cloudflare.com/ajax/libs/cropperjs/1.5.13/cropper.min.css" rel="stylesheet">
<style>
    .image-preview {
        max-width: 250px;
        max-height: 250px;
        border-radius: 8px;
        border: 2px solid #e9ecef;
        object-fit: cover;
        box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
    }

    .no-image-placeholder {
        width: 250px;
        height: 200px;
        background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
        border: 2px dashed #ced4da;
        display: flex;
        align-items: center;
        justify-content: center;
        flex-direction: column;
        border-radius: 8px;
        color: #6c757d;
        transition: all 0.3s ease;
    }

    .no-image-placeholder:hover {
        border-color: #adb5bd;
        background: linear-gradient(135deg, #e9ecef 0%, #dee2e6 100%);
    }

    .image-controls {
        margin-top: 15px;
    }

    .btn-image-action {
        margin-right: 5px;
        margin-bottom: 5px;
    }

    .modal-body img {
        max-width: 100%;
        display: block;
    }

    .cropper-controls {
        padding: 15px;
        background-color: #f8f9fa;
        border-radius: 8px;
    }

    .control-group {
        margin-bottom: 15px;
    }

    .control-group label {
        display: block;
        margin-bottom: 5px;
        font-weight: 600;
        color: #495057;
    }

    .form-range {
        margin-bottom: 10px;
    }

    .alert-custom {
        border-left: 4px solid;
        padding-left: 15px;
    }

    .alert-danger {
        border-left-color: #dc3545;
    }

    .alert-success {
        border-left-color: #28a745;
    }
</style>

<div class="container-fluid">
    <!-- Page Header -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h2 class="mb-0">Edit Produk</h2>
            <p class="text-muted mb-0">Ubah informasi produk #<?= $actual_product_id ?></p>
        </div>
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb mb-0">
                <li class="breadcrumb-item"><a href="../../index.php">Dashboard</a></li>
                <li class="breadcrumb-item"><a href="products.php">Produk</a></li>
                <li class="breadcrumb-item active">Edit</li>
            </ol>
        </nav>
    </div>

    <!-- Alert Messages -->
    <?php if (!empty($errors)): ?>
        <div class="alert alert-danger alert-custom alert-dismissible fade show" role="alert">
            <i class="fas fa-exclamation-triangle me-2"></i>
            <strong>Terdapat kesalahan:</strong>
            <ul class="mb-0 mt-2">
                <?php foreach ($errors as $error): ?>
                    <li><?= htmlspecialchars($error) ?></li>
                <?php endforeach; ?>
            </ul>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <!-- Main Form -->
    <form method="POST" id="productForm" enctype="multipart/form-data" novalidate>
        <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
        <input type="hidden" name="cropped_image" id="croppedImageData">
        <input type="hidden" name="remove_image" id="removeImageFlag" value="0">

        <div class="row">
            <!-- Product Information -->
            <div class="col-lg-8">
                <div class="card shadow-sm">
                    <div class="card-header bg-primary text-white">
                        <h5 class="mb-0"><i class="fas fa-info-circle me-2"></i>Informasi Produk</h5>
                    </div>
                    <div class="card-body">
                        <!-- Product Name -->
                        <div class="mb-3">
                            <label for="productName" class="form-label">
                                Nama Produk <span class="text-danger">*</span>
                            </label>
                            <input type="text"
                                class="form-control"
                                id="productName"
                                name="name"
                                value="<?= htmlspecialchars($product['name']) ?>"
                                required
                                maxlength="255">
                            <div class="form-text">Maksimal 255 karakter</div>
                        </div>

                        <!-- Product Description -->
                        <div class="mb-3">
                            <label for="productDescription" class="form-label">
                                Deskripsi <span class="text-danger">*</span>
                            </label>
                            <textarea class="form-control"
                                id="productDescription"
                                name="description"
                                rows="5"
                                required><?= htmlspecialchars($product['description']) ?></textarea>
                            <div class="form-text">Deskripsikan produk secara detail</div>
                        </div>

                        <!-- Category and Stock -->
                        <div class="row">
                            <div class="col-md-6">
                                <label for="productCategory" class="form-label">Kategori</label>
                                <select class="form-select" id="productCategory" name="category_id">
                                    <option value="">-- Pilih Kategori --</option>
                                    <?php foreach ($categories as $category): ?>
                                        <option value="<?= $category['id'] ?>"
                                            <?= ($product['category_id'] == $category['id']) ? 'selected' : '' ?>>
                                            <?= htmlspecialchars($category['name']) ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label for="productStock" class="form-label">
                                    Stok <span class="text-danger">*</span>
                                </label>
                                <input type="number"
                                    class="form-control"
                                    id="productStock"
                                    name="stock"
                                    value="<?= $product['stock'] ?>"
                                    min="0"
                                    required>
                            </div>
                        </div>

                        <!-- Tags -->
                        <div class="mb-3 mt-3">
                            <label for="productTags" class="form-label">Tags</label>
                            <input type="text"
                                class="form-control"
                                id="productTags"
                                name="tags"
                                value="<?= htmlspecialchars($product['tags']) ?>"
                                placeholder="contoh: laptop, gaming, portabel">
                            <div class="form-text">Pisahkan dengan koma untuk multiple tags</div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Product Image -->
            <div class="col-lg-4">
                <div class="card shadow-sm">
                    <div class="card-header bg-secondary text-white">
                        <h5 class="mb-0"><i class="fas fa-image me-2"></i>Gambar Produk</h5>
                    </div>
                    <div class="card-body text-center">
                        <!-- Current Image Display -->
                        <div class="mb-3">
                            <?php
                            $current_image_url = '';
                            $show_image = false;
                            
                            // Define upload_dir here to fix the undefined variable error
                            $upload_dir_display = __DIR__ . '/../../uploads/products/';

                            if (!empty($old_image_path)) {
                                // Server path untuk file_exists
                                $server_image_path = $upload_dir_display . basename($old_image_path);
                                // Web path untuk src img  
                                $web_image_path = '../../uploads/products/' . basename($old_image_path);
                                
                                if (file_exists($server_image_path)) {
                                    $current_image_url = $web_image_path;
                                    $show_image = true;
                                }
                            }
                            ?>

                            <?php if ($show_image): ?>
                                <img id="imagePreview"
                                    src="<?= htmlspecialchars($current_image_url) ?>"
                                    alt="<?= htmlspecialchars($product['name']) ?>"
                                    class="image-preview">
                            <?php else: ?>
                                <div id="imagePlaceholder" class="no-image-placeholder">
                                    <svg class="w-16 h-16 text-gray-400 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" width="64" height="64">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z" />
                                    </svg>
                                    <p class="mb-2">Tidak ada gambar</p>
                                    <p class="text-sm text-muted">Upload gambar produk</p>
                                </div>
                            <?php endif; ?>
                        </div>

                        <!-- Image Controls -->
                        <div class="image-controls">
                            <!-- File Upload -->
                            <input type="file"
                                class="form-control mb-3"
                                id="imageUpload"
                                name="image_file"
                                accept="image/*"
                                onchange="handleImageUpload(this)">

                            <div class="btn-group-vertical d-grid gap-2">
                                <button type="button"
                                    class="btn btn-outline-primary btn-sm"
                                    id="editImageBtn"
                                    onclick="openImageEditor()"
                                    style="<?= $show_image ? '' : 'display: none;' ?>">
                                    <i class="fas fa-edit me-1"></i> Edit Gambar
                                </button>

                                <button type="button"
                                    class="btn btn-outline-danger btn-sm"
                                    id="removeImageBtn"
                                    onclick="removeImage()"
                                    style="<?= $show_image ? '' : 'display: none;' ?>">
                                    <i class="fas fa-trash me-1"></i> Hapus Gambar
                                </button>
                            </div>
                        </div>

                        <!-- Image Info -->
                        <?php if ($show_image): ?>
                        <div class="mt-3 p-2 bg-light rounded">
                            <small class="text-muted">
                                <strong>File:</strong> <?= htmlspecialchars(basename($old_image_path)) ?><br>
                                <strong>Produk ID:</strong> #<?= $actual_product_id ?>
                            </small>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Action Buttons -->
                <div class="card shadow-sm mt-3">
                    <div class="card-body">
                        <div class="d-grid gap-2">
                            <button type="submit" class="btn btn-success">
                                <i class="fas fa-save me-2"></i> Simpan Perubahan
                            </button>
                            <a href="products.php" class="btn btn-secondary">
                                <i class="fas fa-arrow-left me-2"></i> Kembali ke Daftar
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </form>
</div>

<!-- Image Editor Modal -->
<div class="modal fade" id="imageEditorModal" tabindex="-1" aria-labelledby="imageEditorLabel" aria-hidden="true">
    <div class="modal-dialog modal-xl">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="imageEditorLabel">
                    <i class="fas fa-edit me-2"></i>Edit Gambar Produk
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="row">
                    <!-- Image Editor Area -->
                    <div class="col-lg-8">
                        <div class="text-center">
                            <img id="cropperImage" src="" style="max-width: 100%;">
                        </div>
                    </div>

                    <!-- Controls -->
                    <div class="col-lg-4">
                        <div class="cropper-controls">
                            <!-- Rotation Controls -->
                            <div class="control-group">
                                <label>Rotasi</label>
                                <div class="btn-group w-100" role="group">
                                    <button type="button" class="btn btn-outline-primary" id="rotateLeft">
                                        <i class="fas fa-undo"></i> 90° Kiri
                                    </button>
                                    <button type="button" class="btn btn-outline-primary" id="rotateRight">
                                        <i class="fas fa-redo"></i> 90° Kanan
                                    </button>
                                </div>
                            </div>

                            <!-- Brightness Control -->
                            <div class="control-group">
                                <label for="brightnessRange">Kecerahan</label>
                                <input type="range" class="form-range" id="brightnessRange" min="0" max="200" value="100">
                                <div class="d-flex justify-content-between">
                                    <small>Gelap</small>
                                    <small>Terang</small>
                                </div>
                            </div>

                            <!-- Contrast Control -->
                            <div class="control-group">
                                <label for="contrastRange">Kontras</label>
                                <input type="range" class="form-range" id="contrastRange" min="0" max="200" value="100">
                                <div class="d-flex justify-content-between">
                                    <small>Rendah</small>
                                    <small>Tinggi</small>
                                </div>
                            </div>

                            <!-- Reset Button -->
                            <div class="control-group">
                                <button type="button" class="btn btn-outline-warning w-100" id="resetFilters">
                                    <i class="fas fa-undo me-1"></i> Reset Filter
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                    <i class="fas fa-times me-1"></i> Batal
                </button>
                <button type="button" class="btn btn-primary" id="applyImageEdit">
                    <i class="fas fa-check me-1"></i> Terapkan
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Scripts -->
<script src="https://cdnjs.cloudflare.com/ajax/libs/cropperjs/1.5.13/cropper.min.js"></script>
<script>
    let cropper = null;
    let currentImageData = '';
    let hasImageChanged = false;

    // Handle image upload
    function handleImageUpload(input) {
        if (!input.files || !input.files[0]) return;

        const file = input.files[0];

        // Validate file type
        if (!file.type.match(/^image\/(jpeg|jpg|png|gif)$/)) {
            alert('Hanya file gambar (JPEG, PNG, GIF) yang diperbolehkan.');
            input.value = '';
            return;
        }

        // Validate file size (max 5MB)
        if (file.size > 5 * 1024 * 1024) {
            alert('Ukuran file terlalu besar. Maksimal 5MB.');
            input.value = '';
            return;
        }

        const reader = new FileReader();
        reader.onload = function(e) {
            currentImageData = e.target.result;
            showImagePreview(currentImageData);
            hasImageChanged = true;
        };
        reader.readAsDataURL(file);
    }

    // Show image preview
    function showImagePreview(imageData) {
        const preview = document.getElementById('imagePreview');
        const placeholder = document.getElementById('imagePlaceholder');
        const editBtn = document.getElementById('editImageBtn');
        const removeBtn = document.getElementById('removeImageBtn');

        // Create image element if doesn't exist
        if (!preview) {
            const img = document.createElement('img');
            img.id = 'imagePreview';
            img.className = 'image-preview';
            img.alt = 'Product Image';
            
            if (placeholder) {
                placeholder.parentNode.insertBefore(img, placeholder);
            }
        }

        const imagePreview = document.getElementById('imagePreview');
        imagePreview.src = imageData;
        imagePreview.style.display = 'block';

        if (placeholder) {
            placeholder.style.display = 'none';
        }

        editBtn.style.display = 'block';
        removeBtn.style.display = 'block';
    }

    // Open image editor
    function openImageEditor() {
        const existingImage = document.getElementById('imagePreview');

        // Jika tidak ada currentImageData, ambil dari existing image
        if (!currentImageData && existingImage && existingImage.src && existingImage.style.display !== 'none') {
            // Convert existing image to base64 first
            convertImageToBase64(existingImage.src)
                .then(base64Data => {
                    currentImageData = base64Data;
                    proceedWithEditor();
                })
                .catch(error => {
                    console.error('Error converting image:', error);
                    alert('Error loading image. Please try uploading a new image.');
                });
            return;
        }

        proceedWithEditor();
    }

    // Helper function to convert image URL to base64
    function convertImageToBase64(imageUrl) {
        return new Promise((resolve, reject) => {
            const canvas = document.createElement('canvas');
            const ctx = canvas.getContext('2d');
            const img = new Image();

            img.onload = function() {
                canvas.width = img.naturalWidth;
                canvas.height = img.naturalHeight;
                ctx.drawImage(img, 0, 0);
                try {
                    const base64Data = canvas.toDataURL('image/jpeg', 0.9);
                    resolve(base64Data);
                } catch (error) {
                    reject(error);
                }
            };

            img.onerror = function() {
                reject(new Error('Failed to load image'));
            };

            // Handle CORS issues
            img.crossOrigin = 'anonymous';
            
            // Add timestamp to prevent caching issues
            const separator = imageUrl.includes('?') ? '&' : '?';
            img.src = imageUrl + separator + '_t=' + Date.now();
        });
    }

    function proceedWithEditor() {
        if (!currentImageData) {
            alert('Pilih gambar terlebih dahulu.');
            return;
        }

        const cropperImage = document.getElementById('cropperImage');
        cropperImage.src = currentImageData;

        // Initialize modal
        const modal = new bootstrap.Modal(document.getElementById('imageEditorModal'));
        modal.show();

        // Initialize cropper after modal is shown
        const modalElement = document.getElementById('imageEditorModal');
        modalElement.addEventListener('shown.bs.modal', function initializeCropper() {
            // Remove previous event listener to prevent multiple initializations
            modalElement.removeEventListener('shown.bs.modal', initializeCropper);
            
            if (cropper) {
                cropper.destroy();
                cropper = null;
            }

            // Small delay to ensure modal is fully rendered
            setTimeout(() => {
                cropper = new Cropper(cropperImage, {
                    aspectRatio: 1,
                    viewMode: 2,
                    autoCropArea: 1,
                    responsive: true,
                    background: false,
                    guides: true,
                    center: true,
                    highlight: false,
                    dragMode: 'move',
                    ready: function() {
                        console.log('Cropper initialized successfully');
                    }
                });
            }, 100);
        });
    }

    // Remove image
    function removeImage() {
        if (confirm('Apakah Anda yakin ingin menghapus gambar ini?')) {
            const preview = document.getElementById('imagePreview');
            const placeholder = document.getElementById('imagePlaceholder');
            const editBtn = document.getElementById('editImageBtn');
            const removeBtn = document.getElementById('removeImageBtn');
            const uploadInput = document.getElementById('imageUpload');
            const removeFlag = document.getElementById('removeImageFlag');

            if (preview) {
                preview.src = '';
                preview.style.display = 'none';
            }

            if (placeholder) {
                placeholder.style.display = 'flex';
            }

            editBtn.style.display = 'none';
            removeBtn.style.display = 'none';
            uploadInput.value = '';
            removeFlag.value = '1';

            currentImageData = '';
            hasImageChanged = true;

            // Clear cropped image data
            document.getElementById('croppedImageData').value = '';
        }
    }

    // Image editor controls
    document.addEventListener('DOMContentLoaded', function() {
        // Get current image if exists
        const existingImage = document.getElementById('imagePreview');
        if (existingImage && existingImage.src && existingImage.style.display !== 'none') {
            // Convert relative path to absolute URL atau fetch sebagai base64
            fetch(existingImage.src)
                .then(response => response.blob())
                .then(blob => {
                    const reader = new FileReader();
                    reader.readAsDataURL(blob);
                })
                .catch(error => {
                    console.error('Error loading existing image:', error);
                });
        }

        // Rotation controls
        document.getElementById('rotateLeft').addEventListener('click', function() {
            if (cropper) cropper.rotate(-90);
        });

        document.getElementById('rotateRight').addEventListener('click', function() {
            if (cropper) cropper.rotate(90);
        });

        // Filter controls
        document.getElementById('brightnessRange').addEventListener('input', applyFilters);
        document.getElementById('contrastRange').addEventListener('input', applyFilters);

        // Reset filters
        document.getElementById('resetFilters').addEventListener('click', function() {
            document.getElementById('brightnessRange').value = 100;
            document.getElementById('contrastRange').value = 100;
            applyFilters();
        });

        // Apply image edit
        document.getElementById('applyImageEdit').addEventListener('click', function() {
            if (!cropper) return;

            const canvas = cropper.getCroppedCanvas({
                width: 800,
                height: 800,
                imageSmoothingEnabled: true,
                imageSmoothingQuality: 'high'
            });

            const croppedImageData = canvas.toDataURL('image/jpeg', 0.9);

            // Update preview
            showImagePreview(croppedImageData);
            currentImageData = croppedImageData;

            // Store cropped data
            document.getElementById('croppedImageData').value = croppedImageData;
            document.getElementById('removeImageFlag').value = '0';

            // Clear file input since we're using cropped data
            document.getElementById('imageUpload').value = '';

            // Close modal
            const modal = bootstrap.Modal.getInstance(document.getElementById('imageEditorModal'));
            modal.hide();

            hasImageChanged = true;
        });
    });

    // Apply filters
    function applyFilters() {
        const brightness = document.getElementById('brightnessRange').value;
        const contrast = document.getElementById('contrastRange').value;
        const cropperImage = document.getElementById('cropperImage');

        cropperImage.style.filter = `brightness(${brightness}%) contrast(${contrast}%)`;
    }

    // Form validation
    document.getElementById('productForm').addEventListener('submit', function(e) {
        const name = document.getElementById('productName').value.trim();
        const description = document.getElementById('productDescription').value.trim();
        const stock = document.getElementById('productStock').value;

        if (!name) {
            alert('Nama produk harus diisi.');
            e.preventDefault();
            return false;
        }

        if (!description) {
            alert('Deskripsi produk harus diisi.');
            e.preventDefault();
            return false;
        }

        if (stock === '' || parseInt(stock) < 0) {
            alert('Stok harus berupa angka dan tidak boleh negatif.');
            e.preventDefault();
            return false;
        }
    });

    // Clean up cropper when modal is hidden
    document.getElementById('imageEditorModal').addEventListener('hidden.bs.modal', function() {
        if (cropper) {
            cropper.destroy();
            cropper = null;
        }
        
        // Reset filters
        document.getElementById('brightnessRange').value = 100;
        document.getElementById('contrastRange').value = 100;
        const cropperImage = document.getElementById('cropperImage');
        cropperImage.style.filter = 'brightness(100%) contrast(100%)';
    });

    // Image preview for file upload with drag & drop support
    const imageUpload = document.getElementById('imageUpload');
    const imagePlaceholder = document.getElementById('imagePlaceholder');
    
    if (imagePlaceholder) {
        // Drag and drop functionality
        ['dragenter', 'dragover', 'dragleave', 'drop'].forEach(eventName => {
            imagePlaceholder.addEventListener(eventName, preventDefaults, false);
        });

        function preventDefaults(e) {
            e.preventDefault();
            e.stopPropagation();
        }

        ['dragenter', 'dragover'].forEach(eventName => {
            imagePlaceholder.addEventListener(eventName, highlight, false);
        });

        ['dragleave', 'drop'].forEach(eventName => {
            imagePlaceholder.addEventListener(eventName, unhighlight, false);
        });

        function highlight(e) {
            imagePlaceholder.style.borderColor = '#007bff';
            imagePlaceholder.style.backgroundColor = 'rgba(0, 123, 255, 0.1)';
        }

        function unhighlight(e) {
            imagePlaceholder.style.borderColor = '#ced4da';
            imagePlaceholder.style.backgroundColor = '';
        }

        imagePlaceholder.addEventListener('drop', handleDrop, false);
        imagePlaceholder.addEventListener('click', () => imageUpload.click());

        function handleDrop(e) {
            const dt = e.dataTransfer;
            const files = dt.files;

            if (files.length > 0) {
                imageUpload.files = files;
                handleImageUpload(imageUpload);
            }
        }
    }
</script>

<?php include '../../includes/footer.php'; ?>