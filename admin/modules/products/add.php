<?php

declare(strict_types=1);

require_once '../../config/database.php';
require_once '../../config/auth.php';
require_once '../../includes/functions.php';

// Check authentication
requireAuth();

$db = Database::getInstance()->getConnection();
$pageTitle = 'Tambah Produk Baru';

// Initialize variables
$errors = [];
$success_message = '';
$categories = [];

// Get categories for dropdown
try {
    $categories_stmt = $db->query("SELECT id, name FROM product_categories ORDER BY name ASC");
    $categories = $categories_stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    error_log("Database error in add_product.php: " . $e->getMessage());
    $errors[] = 'Terjadi kesalahan saat mengambil data kategori.';
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

        // Handle image upload
        $image_path = null;
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
                // We'll update filename after getting the product ID
                $temp_filename = 'temp_' . time() . '.' . $file_extension;
                $temp_path = $upload_dir . $temp_filename;

                if (move_uploaded_file($_FILES['image_file']['tmp_name'], $temp_path)) {
                    $image_path = $temp_filename; // Temporary, will be renamed after insert
                } else {
                    $errors[] = 'Gagal mengupload gambar.';
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
                    $temp_filename = 'temp_' . time() . '.' . $file_extension;
                    $temp_path = $upload_dir . $temp_filename;

                    if (file_put_contents($temp_path, $image_data)) {
                        $image_path = $temp_filename; // Temporary, will be renamed after insert
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

        // If no validation errors, save to database
        if (empty($errors)) {
            try {
                $stmt = $db->prepare("
                    INSERT INTO products (name, description, category_id, tags, stock, image_path, created_at, updated_at) 
                    VALUES (?, ?, ?, ?, ?, ?, NOW(), NOW())
                ");

                $stmt->execute([
                    $name,
                    $description,
                    $category_id,
                    $tags,
                    $stock,
                    $image_path
                ]);

                $product_id = $db->lastInsertId();

                // Rename image file with proper product ID if image was uploaded
                if ($image_path && file_exists($upload_dir . $image_path)) {
                    $file_extension = pathinfo($image_path, PATHINFO_EXTENSION);
                    $final_filename = 'product_' . $product_id . '_' . time() . '.' . $file_extension;
                    $final_path = $upload_dir . $final_filename;

                    if (rename($upload_dir . $image_path, $final_path)) {
                        // Update database with final filename
                        $update_stmt = $db->prepare("UPDATE products SET image_path = ? WHERE id = ?");
                        $update_stmt->execute([$final_filename, $product_id]);
                        $image_path = $final_filename;
                    }
                }

                $_SESSION['success_message'] = 'Produk "' . htmlspecialchars($name) . '" berhasil ditambahkan.';
                
                // Check if user wants to add another product
                if (isset($_POST['save_and_add'])) {
                    header('Location: add.php');
                } else {
                    header('Location: products.php');
                }
                exit;

            } catch (PDOException $e) {
                error_log("Insert error in add_product.php: " . $e->getMessage());
                $errors[] = 'Terjadi kesalahan saat menyimpan data. Silakan coba lagi.';
                
                // Clean up uploaded file if database insert failed
                if ($image_path && file_exists($upload_dir . $image_path)) {
                    unlink($upload_dir . $image_path);
                }
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
        cursor: pointer;
    }

    .no-image-placeholder:hover {
        border-color: #007bff;
        background: linear-gradient(135deg, #e3f2fd 0%, #bbdefb 100%);
        color: #1976d2;
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

    .drag-drop-zone {
        border: 2px dashed #dee2e6;
        border-radius: 8px;
        padding: 20px;
        text-align: center;
        transition: all 0.3s ease;
        cursor: pointer;
    }

    .drag-drop-zone:hover {
        border-color: #007bff;
        background-color: #f8f9fa;
    }

    .drag-drop-zone.drag-over {
        border-color: #007bff;
        background-color: #e3f2fd;
    }
</style>

<div class="container-fluid">
    <!-- Page Header -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h2 class="mb-0">Tambah Produk Baru</h2>
            <p class="text-muted mb-0">Buat entri produk baru</p>
        </div>
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb mb-0">
                <li class="breadcrumb-item"><a href="../../index.php">Dashboard</a></li>
                <li class="breadcrumb-item"><a href="products.php">Produk</a></li>
                <li class="breadcrumb-item active">Tambah Baru</li>
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

    <?php if (isset($_SESSION['success_message'])): ?>
        <div class="alert alert-success alert-custom alert-dismissible fade show" role="alert">
            <i class="fas fa-check-circle me-2"></i>
            <?= htmlspecialchars($_SESSION['success_message']) ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
        <?php unset($_SESSION['success_message']); ?>
    <?php endif; ?>

    <!-- Main Form -->
    <form method="POST" id="productForm" enctype="multipart/form-data" novalidate>
        <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
        <input type="hidden" name="cropped_image" id="croppedImageData">

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
                                value="<?= htmlspecialchars($_POST['name'] ?? '') ?>"
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
                                required><?= htmlspecialchars($_POST['description'] ?? '') ?></textarea>
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
                                            <?= ($_POST['category_id'] ?? '') == $category['id'] ? 'selected' : '' ?>>
                                            <?= htmlspecialchars($category['name']) ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                                <div class="form-text">
                                    <a href="categories.php" target="_blank">Kelola Kategori</a>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <label for="productStock" class="form-label">
                                    Stok Awal <span class="text-danger">*</span>
                                </label>
                                <input type="number"
                                    class="form-control"
                                    id="productStock"
                                    name="stock"
                                    value="<?= htmlspecialchars($_POST['stock'] ?? '0') ?>"
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
                                value="<?= htmlspecialchars($_POST['tags'] ?? '') ?>"
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
                        <!-- Image Preview/Placeholder -->
                        <div class="mb-3">
                            <div id="imagePlaceholder" class="no-image-placeholder" onclick="document.getElementById('imageUpload').click()">
                                <svg class="w-16 h-16 text-gray-400 mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24" width="64" height="64">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z" />
                                </svg>
                                <p class="mb-2">Klik untuk upload gambar</p>
                                <p class="text-sm text-muted">atau drag & drop file di sini</p>
                            </div>
                            <img id="imagePreview" src="" alt="Product Preview" class="image-preview" style="display: none;">
                        </div>

                        <!-- File Upload -->
                        <input type="file"
                            class="form-control mb-3"
                            id="imageUpload"
                            name="image_file"
                            accept="image/*"
                            onchange="handleImageUpload(this)"
                            style="display: none;">

                        <!-- Image Controls -->
                        <div class="image-controls">
                            <div class="btn-group-vertical d-grid gap-2">
                                <button type="button"
                                    class="btn btn-outline-primary btn-sm"
                                    id="selectImageBtn"
                                    onclick="document.getElementById('imageUpload').click()">
                                    <i class="fas fa-upload me-1"></i> Pilih Gambar
                                </button>

                                <button type="button"
                                    class="btn btn-outline-primary btn-sm"
                                    id="editImageBtn"
                                    onclick="openImageEditor()"
                                    style="display: none;">
                                    <i class="fas fa-edit me-1"></i> Edit Gambar
                                </button>

                                <button type="button"
                                    class="btn btn-outline-danger btn-sm"
                                    id="removeImageBtn"
                                    onclick="removeImage()"
                                    style="display: none;">
                                    <i class="fas fa-trash me-1"></i> Hapus Gambar
                                </button>
                            </div>
                        </div>

                        <!-- Upload Info -->
                        <div class="mt-3 p-2 bg-light rounded">
                            <small class="text-muted">
                                <strong>Format:</strong> JPG, PNG, GIF<br>
                                <strong>Ukuran maks:</strong> 5MB<br>
                                <strong>Rekomendasi:</strong> 800x800px
                            </small>
                        </div>
                    </div>
                </div>

                <!-- Action Buttons -->
                <div class="card shadow-sm mt-3">
                    <div class="card-body">
                        <div class="d-grid gap-2">
                            <button type="submit" class="btn btn-success">
                                <i class="fas fa-save me-2"></i> Simpan Produk
                            </button>
                            <button type="button" class="btn btn-outline-secondary" onclick="resetForm()">
                                <i class="fas fa-undo me-2"></i> Reset Form
                            </button>
                            <a href="products.php" class="btn btn-secondary">
                                <i class="fas fa-arrow-left me-2"></i> Kembali ke Daftar
                            </a>
                        </div>

                        <hr>

                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" id="saveAndAdd" name="save_and_add">
                            <label class="form-check-label" for="saveAndAdd">
                                Simpan dan tambah produk lain
                            </label>
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
        const selectBtn = document.getElementById('selectImageBtn');

        preview.src = imageData;
        preview.style.display = 'block';
        placeholder.style.display = 'none';

        editBtn.style.display = 'block';
        removeBtn.style.display = 'block';
        selectBtn.innerHTML = '<i class="fas fa-upload me-1"></i> Ganti Gambar';
    }

    // Open image editor
    function openImageEditor() {
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
            const selectBtn = document.getElementById('selectImageBtn');
            const uploadInput = document.getElementById('imageUpload');

            preview.src = '';
            preview.style.display = 'none';
            placeholder.style.display = 'flex';

            editBtn.style.display = 'none';
            removeBtn.style.display = 'none';
            selectBtn.innerHTML = '<i class="fas fa-upload me-1"></i> Pilih Gambar';
            uploadInput.value = '';

            currentImageData = '';
            hasImageChanged = true;

            // Clear cropped image data
            document.getElementById('croppedImageData').value = '';
        }
    }

    // Reset form
    function resetForm() {
        if (confirm('Apakah Anda yakin ingin mereset form? Semua data yang dimasukkan akan hilang.')) {
            document.getElementById('productForm').reset();
            removeImage();
        }
    }

    // Image editor controls
    document.addEventListener('DOMContentLoaded', function() {
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

            // Clear file input since we're using cropped data
            document.getElementById('imageUpload').value = '';

            // Close modal
            const modal = bootstrap.Modal.getInstance(document.getElementById('imageEditorModal'));
            modal.hide();

            hasImageChanged = true;
        });

        // Drag and drop functionality
        const imagePlaceholder = document.getElementById('imagePlaceholder');
        const imageUpload = document.getElementById('imageUpload');
        
        if (imagePlaceholder) {
            // Drag and drop events
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
                imagePlaceholder.classList.add('drag-over');
            }

            function unhighlight(e) {
                imagePlaceholder.classList.remove('drag-over');
            }

            imagePlaceholder.addEventListener('drop', handleDrop, false);

            function handleDrop(e) {
                const dt = e.dataTransfer;
                const files = dt.files;

                if (files.length > 0) {
                    imageUpload.files = files;
                    handleImageUpload(imageUpload);
                }
            }
        }

        // Tags input enhancement
        const tagsInput = document.getElementById('productTags');
        tagsInput.addEventListener('blur', function() {
            // Clean up tags: remove extra spaces, duplicates
            let tags = this.value.split(',').map(tag => tag.trim()).filter(tag => tag);
            tags = [...new Set(tags)]; // Remove duplicates
            this.value = tags.join(', ');
        });

        // Auto-resize textarea
        const textarea = document.getElementById('productDescription');
        textarea.addEventListener('input', function() {
            this.style.height = 'auto';
            this.style.height = this.scrollHeight + 'px';
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
            document.getElementById('productName').focus();
            return false;
        }

        if (name.length > 255) {
            alert('Nama produk tidak boleh lebih dari 255 karakter.');
            e.preventDefault();
            document.getElementById('productName').focus();
            return false;
        }

        if (!description) {
            alert('Deskripsi produk harus diisi.');
            e.preventDefault();
            document.getElementById('productDescription').focus();
            return false;
        }

        if (stock === '' || parseInt(stock) < 0) {
            alert('Stok harus berupa angka dan tidak boleh negatif.');
            e.preventDefault();
            document.getElementById('productStock').focus();
            return false;
        }

        // Show loading state
        const submitBtn = this.querySelector('button[type="submit"]');
        const originalText = submitBtn.innerHTML;
        submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i>Menyimpan...';
        submitBtn.disabled = true;

        // Re-enable button after 5 seconds as fallback
        setTimeout(() => {
            submitBtn.innerHTML = originalText;
            submitBtn.disabled = false;
        }, 5000);
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

    // Character counter for product name
    document.getElementById('productName').addEventListener('input', function() {
        const maxLength = 255;
        const currentLength = this.value.length;
        const remaining = maxLength - currentLength;
        
        // Find or create counter element
        let counter = document.querySelector('#productName + .form-text .char-counter');
        if (!counter) {
            const formText = document.querySelector('#productName + .form-text');
            if (formText) {
                counter = document.createElement('span');
                counter.className = 'char-counter float-end';
                formText.appendChild(counter);
            }
        }
        
        if (counter) {
            counter.textContent = `${currentLength}/${maxLength}`;
            counter.className = `char-counter float-end ${remaining < 20 ? 'text-warning' : ''}${remaining < 0 ? 'text-danger' : ''}`;
        }
    });

    // Initialize character counter on page load
    document.addEventListener('DOMContentLoaded', function() {
        document.getElementById('productName').dispatchEvent(new Event('input'));
    });
</script>

<?php include '../../includes/footer.php'; ?>