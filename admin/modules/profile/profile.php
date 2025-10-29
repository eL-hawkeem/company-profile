<?php

/**
 * Halaman Edit Profile
 * PT. Sarana Sentra Teknologi Utama - Admin Dashboard
 * Path: admin/modules/profile/index.php
 */

declare(strict_types=1);
require_once '../../config/auth.php';
require_once '../../config/database.php';
// Initialize auth and require authentication
$auth = new Auth();
$auth->requireAuth();
// Initialize database
$db = Database::getInstance();
$page_title = 'Edit Profile';
$success_message = '';
$error_message = '';
// Get current user data
$user = $auth->getCurrentUser();
if (!$user) {
    $error_message = "Tidak dapat mengambil data user. Silakan login ulang.";
    $auth->logout();
    header('Location: ../../login.php');
    exit;
}
// Proses update profile
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Verify CSRF token
    $csrf_token = $_POST['csrf_token'] ?? '';
    if (!$auth->verifyCSRFToken($csrf_token)) {
        $error_message = "Token keamanan tidak valid. Silakan refresh halaman.";
    } else {
        $username = trim($_POST['username'] ?? '');
        $current_password = $_POST['current_password'] ?? '';
        $new_password = $_POST['new_password'] ?? '';
        $confirm_password = $_POST['confirm_password'] ?? '';
        // Handle profile image upload
        $profile_image = $user['profile_image'] ?? null; // Default to current image
        if (isset($_FILES['profile_image']) && $_FILES['profile_image']['error'] === UPLOAD_ERR_OK) {
            $file = $_FILES['profile_image'];
            // Validate file type
            $allowedTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
            if (!in_array($file['type'], $allowedTypes)) {
                $error_message = "Hanya file gambar (JPG, PNG, GIF, WEBP) yang diperbolehkan.";
            } else {
                // Validate file size (max 2MB)
                if ($file['size'] > 2 * 1024 * 1024) {
                    $error_message = "Ukuran file maksimal 2MB.";
                } else {
                    // Create upload directory if it doesn't exist
                    $uploadDir = __DIR__ . '/../../uploads/profiles/';
                    if (!is_dir($uploadDir)) {
                        mkdir($uploadDir, 0755, true);
                    }
                    // Generate unique filename
                    $fileExt = pathinfo($file['name'], PATHINFO_EXTENSION);
                    $fileName = 'profile_' . $user['id'] . '_' . time() . '.' . $fileExt;
                    $filePath = $uploadDir . $fileName;
                    // Move uploaded file
                    if (move_uploaded_file($file['tmp_name'], $filePath)) {
                        // Delete old profile image if exists
                        if (!empty($user['profile_image'])) {
                            $oldImagePath = __DIR__ . '/../../uploads/profiles/' . $user['profile_image'];
                            if (file_exists($oldImagePath)) {
                                unlink($oldImagePath);
                            }
                        }
                        // Simpan hanya nama file di database
                        $profile_image = $fileName;
                    } else {
                        $error_message = "Gagal mengupload gambar profil.";
                    }
                }
            }
        }
        // Only continue with form validation if there was no image upload error
        if (empty($error_message)) {
            // Validasi input
            if (empty($username)) {
                $error_message = "Username tidak boleh kosong.";
            } elseif (strlen($username) < 3) {
                $error_message = "Username minimal 3 karakter.";
            } elseif (!preg_match('/^[a-zA-Z0-9_]+$/', $username)) {
                $error_message = "Username hanya boleh mengandung huruf, angka, dan underscore.";
            } else {
                try {
                    $db->beginTransaction();
                    // Check if username is already taken by another user
                    $existingUser = $db->fetchOne(
                        "SELECT id FROM users WHERE username = ? AND id != ?",
                        [$username, $user['id']]
                    );
                    if ($existingUser) {
                        throw new Exception("Username sudah digunakan oleh user lain.");
                    }
                    // If changing password
                    if (!empty($new_password)) {
                        // Verify current password
                        if (empty($current_password)) {
                            throw new Exception("Password saat ini harus diisi untuk mengubah password.");
                        }
                        // Get current password hash from database
                        $currentUser = $db->fetchOne("SELECT password FROM users WHERE id = ?", [$user['id']]);
                        if (!password_verify($current_password, $currentUser['password'])) {
                            throw new Exception("Password saat ini tidak benar.");
                        }
                        // Validate new password strength
                        $passwordErrors = Auth::validatePassword($new_password);
                        if (!empty($passwordErrors)) {
                            throw new Exception("Password baru tidak memenuhi kriteria: " . implode(", ", $passwordErrors));
                        }
                        if ($new_password !== $confirm_password) {
                            throw new Exception("Konfirmasi password tidak cocok.");
                        }
                        // Update username, password, and profile image
                        $hashedPassword = Auth::hashPassword($new_password);
                        $updateData = [
                            'username' => $username,
                            'password' => $hashedPassword
                        ];
                        if ($profile_image !== null) {
                            $updateData['profile_image'] = $profile_image;
                        }
                        $affectedRows = $db->update(
                            'users',
                            $updateData,
                            'id = :user_id',
                            ['user_id' => $user['id']]
                        );
                        if ($affectedRows === 0) {
                            throw new Exception("Tidak ada perubahan yang disimpan.");
                        }
                        $success_message = "Profile dan password berhasil diperbarui.";
                    } else {
                        // Update username and profile image only
                        $updateData = [
                            'username' => $username
                        ];
                        if ($profile_image !== null) {
                            $updateData['profile_image'] = $profile_image;
                        }
                        $affectedRows = $db->update(
                            'users',
                            $updateData,
                            'id = :user_id',
                            ['user_id' => $user['id']]
                        );
                        if ($affectedRows === 0) {
                            throw new Exception("Tidak ada perubahan yang disimpan.");
                        }
                        $success_message = "Profile berhasil diperbarui.";
                    }
                    // Update session username if changed
                    if ($username !== $user['username']) {
                        $_SESSION['username'] = $username;
                    }
                    // Refresh user data
                    $user = $auth->getCurrentUser();
                    $db->commit();
                    // Log activity
                    error_log("Profile updated successfully for user ID: " . $user['id']);
                } catch (Exception $e) {
                    $db->rollback();
                    $error_message = $e->getMessage();
                    error_log("Profile update error for user ID " . $user['id'] . ": " . $e->getMessage());
                }
            }
        }
    }
}
// Generate CSRF token for form
$csrf_token = $auth->getCSRFToken();
include '../../includes/header.php';
?>
<style>
    .profile-container {
        max-width: 900px;
        margin: 0 auto;
    }

    .profile-card {
        border: none;
        border-radius: 12px;
        box-shadow: 0 4px 20px rgba(0, 0, 0, 0.08);
        overflow: hidden;
    }

    .profile-header {
        background: linear-gradient(135deg, #6a11cb 0%, #2575fc 100%);
        color: white;
        padding: 2rem;
        text-align: center;
        position: relative;
    }

    .profile-image-container {
        position: relative;
        width: 120px;
        height: 120px;
        margin: 0 auto 1rem;
    }

    .profile-image {
        width: 100%;
        height: 100%;
        border-radius: 50%;
        object-fit: cover;
        border: 4px solid white;
        box-shadow: 0 4px 8px rgba(0, 0, 0, 0.2);
    }

    .profile-image-placeholder {
        width: 100%;
        height: 100%;
        border-radius: 50%;
        background-color: rgba(255, 255, 255, 0.3);
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 3rem;
        color: white;
        border: 4px solid white;
    }

    .profile-image-upload {
        position: absolute;
        bottom: 0;
        right: 0;
        background-color: #2575fc;
        border-radius: 50%;
        width: 36px;
        height: 36px;
        display: flex;
        align-items: center;
        justify-content: center;
        cursor: pointer;
        box-shadow: 0 2px 4px rgba(0, 0, 0, 0.2);
        transition: all 0.2s ease;
    }

    .profile-image-upload:hover {
        background-color: #1a5fcc;
        transform: scale(1.05);
    }

    .profile-tabs {
        display: flex;
        border-bottom: 1px solid #e9ecef;
        margin-bottom: 1.5rem;
    }

    .profile-tab {
        padding: 0.75rem 1.5rem;
        cursor: pointer;
        border-bottom: 3px solid transparent;
        font-weight: 500;
        color: #6c757d;
        transition: all 0.2s ease;
    }

    .profile-tab:hover {
        color: #495057;
    }

    .profile-tab.active {
        color: #2575fc;
        border-bottom-color: #2575fc;
    }

    .profile-tab-content {
        display: none;
    }

    .profile-tab-content.active {
        display: block;
        animation: fadeIn 0.3s ease;
    }

    @keyframes fadeIn {
        from {
            opacity: 0;
            transform: translateY(10px);
        }

        to {
            opacity: 1;
            transform: translateY(0);
        }
    }

    .form-label {
        font-weight: 600;
        color: #495057;
        margin-bottom: 0.5rem;
    }

    .form-control,
    .form-select {
        border-radius: 8px;
        border: 1px solid #ced4da;
        padding: 0.75rem;
        transition: all 0.2s ease;
    }

    .form-control:focus,
    .form-select:focus {
        border-color: #86b7fe;
        box-shadow: 0 0 0 0.25rem rgba(37, 117, 252, 0.25);
    }

    .btn-primary {
        background-color: #2575fc;
        border-color: #2575fc;
        border-radius: 8px;
        padding: 0.75rem 1.5rem;
        font-weight: 500;
        transition: all 0.2s ease;
    }

    .btn-primary:hover {
        background-color: #1a5fcc;
        border-color: #1a5fcc;
        transform: translateY(-2px);
        box-shadow: 0 4px 8px rgba(37, 117, 252, 0.3);
    }

    .btn-outline-secondary {
        border-radius: 8px;
        padding: 0.75rem 1.5rem;
        font-weight: 500;
        transition: all 0.2s ease;
    }

    .btn-outline-secondary:hover {
        transform: translateY(-2px);
    }

    .password-strength-meter {
        height: 5px;
        background-color: #e9ecef;
        border-radius: 3px;
        margin-top: 0.5rem;
        overflow: hidden;
    }

    .password-strength-meter-fill {
        height: 100%;
        width: 0;
        border-radius: 3px;
        transition: all 0.3s ease;
    }

    .strength-weak {
        background-color: #dc3545;
        width: 25%;
    }

    .strength-fair {
        background-color: #ffc107;
        width: 50%;
    }

    .strength-good {
        background-color: #0dcaf0;
        width: 75%;
    }

    .strength-strong {
        background-color: #198754;
        width: 100%;
    }

    .info-card {
        background-color: #f8f9fa;
        border-radius: 8px;
        padding: 1rem;
        margin-bottom: 1rem;
    }

    .info-item {
        display: flex;
        justify-content: space-between;
        padding: 0.5rem 0;
        border-bottom: 1px solid #e9ecef;
    }

    .info-item:last-child {
        border-bottom: none;
    }

    .info-label {
        font-weight: 500;
        color: #6c757d;
    }

    .info-value {
        font-weight: 600;
        color: #495057;
    }

    @media (max-width: 768px) {
        .profile-header {
            padding: 1.5rem;
        }

        .profile-tabs {
            overflow-x: auto;
            -webkit-overflow-scrolling: touch;
        }

        .profile-tab {
            padding: 0.5rem 1rem;
            white-space: nowrap;
        }
    }
</style>
<div class="content-wrapper">
    <!-- Page Header -->
    <div class="content-header">
        <div class="d-flex justify-content-between align-items-center">
            <div>
                <h1 class="content-title">Edit Profile</h1>
                <nav aria-label="breadcrumb">
                    <ol class="breadcrumb">
                        <li class="breadcrumb-item"><a href="../../index.php">Dashboard</a></li>
                        <li class="breadcrumb-item active">Edit Profile</li>
                    </ol>
                </nav>
            </div>
        </div>
    </div>
    <!-- Main Content -->
    <div class="content-body">
        <div class="profile-container">
            <div class="card profile-card">
                <!-- Profile Header -->
                <div class="profile-header">
                    <div class="profile-image-container">
                        <?php if (!empty($user['profile_image'])): ?>
                            <!-- PERBAIKAN: Menggunakan logika yang sama dengan product.php -->
                            <img src="..\..\uploads\profiles\<?= htmlspecialchars(basename($user['profile_image'])) ?>" alt="Profile Image" class="profile-image">
                        <?php else: ?>
                            <div class="profile-image-placeholder">
                                <i class="bi bi-person-fill"></i>
                            </div>
                        <?php endif; ?>
                        <label for="profile_image_input" class="profile-image-upload">
                            <i class="bi bi-camera-fill"></i>
                        </label>
                        <input type="file" id="profile_image_input" name="profile_image" accept="image/*" style="display: none;">
                    </div>
                    <h3 class="mb-1"><?= htmlspecialchars($user['username']) ?></h3>
                    <p class="mb-0 opacity-75"><?= ucfirst(htmlspecialchars($user['role'])) ?></p>
                </div>
                <div class="card-body p-4">
                    <?php if ($success_message): ?>
                        <div class="alert alert-success alert-dismissible fade show" role="alert">
                            <i class="bi bi-check-circle me-2"></i>
                            <?= htmlspecialchars($success_message) ?>
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                    <?php endif; ?>
                    <?php if ($error_message): ?>
                        <div class="alert alert-danger alert-dismissible fade show" role="alert">
                            <i class="bi bi-exclamation-triangle me-2"></i>
                            <?= htmlspecialchars($error_message) ?>
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                    <?php endif; ?>
                    <!-- Profile Tabs -->
                    <div class="profile-tabs">
                        <div class="profile-tab active" data-tab="info-tab">
                            <i class="bi bi-info-circle me-2"></i>Informasi Akun
                        </div>
                        <div class="profile-tab" data-tab="edit-tab">
                            <i class="bi bi-pencil-square me-2"></i>Edit Profile
                        </div>
                        <div class="profile-tab" data-tab="password-tab">
                            <i class="bi bi-shield-lock me-2"></i>Ubah Password
                        </div>
                    </div>
                    <form method="POST" id="profileForm" enctype="multipart/form-data">
                        <!-- CSRF Token -->
                        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf_token) ?>">
                        <!-- Account Information Tab -->
                        <div id="info-tab" class="profile-tab-content active">
                            <div class="info-card">
                                <div class="info-item">
                                    <span class="info-label">User ID</span>
                                    <span class="info-value"><?= htmlspecialchars((string)$user['id']) ?></span>
                                </div>
                                <div class="info-item">
                                    <span class="info-label">Username</span>
                                    <span class="info-value"><?= htmlspecialchars($user['username']) ?></span>
                                </div>
                                <div class="info-item">
                                    <span class="info-label">Role</span>
                                    <span class="info-value">
                                        <span class="badge bg-<?= $user['role'] === 'admin' ? 'danger' : 'info' ?>">
                                            <?= ucfirst(htmlspecialchars($user['role'])) ?>
                                        </span>
                                    </span>
                                </div>
                                <div class="info-item">
                                    <span class="info-label">Terdaftar Sejak</span>
                                    <span class="info-value"><?= date('d F Y', strtotime($user['created_at'])) ?></span>
                                </div>
                            </div>
                        </div>
                        <!-- Edit Profile Tab -->
                        <div id="edit-tab" class="profile-tab-content">
                            <div class="mb-4">
                                <label for="username" class="form-label">
                                    Username <span class="text-danger">*</span>
                                </label>
                                <input type="text"
                                    class="form-control"
                                    id="username"
                                    name="username"
                                    value="<?= htmlspecialchars($user['username']) ?>"
                                    required
                                    minlength="3"
                                    maxlength="50"
                                    pattern="[a-zA-Z0-9_]+"
                                    placeholder="Masukkan username">
                                <div class="form-text">Minimal 3 karakter. Hanya huruf, angka, dan underscore yang diperbolehkan.</div>
                            </div>
                            <div class="mb-4">
                                <label class="form-label">Foto Profil</label>
                                <div class="d-flex align-items-center">
                                    <div class="me-3">
                                        <?php if (!empty($user['profile_image'])): ?>
                                            <!-- PERBAIKAN: Menggunakan logika yang sama dengan product.php -->
                                            <img src="..\..\uploads\profiles\<?= htmlspecialchars(basename($user['profile_image'])) ?>" alt="Profile Image" class="rounded-circle" width="80" height="80" style="object-fit: cover;">
                                        <?php else: ?>
                                            <div class="rounded-circle bg-secondary d-flex align-items-center justify-content-center" style="width: 80px; height: 80px;">
                                                <i class="bi bi-person-fill text-white" style="font-size: 2rem;"></i>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                    <div>
                                        <label for="profile_image" class="btn btn-outline-secondary btn-sm">
                                            <i class="bi bi-upload me-1"></i> Pilih Foto
                                        </label>
                                        <input type="file" id="profile_image" name="profile_image" accept="image/*" class="d-none">
                                        <div class="form-text mt-2">Format: JPG, PNG, GIF, WEBP. Maksimal: 2MB</div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <!-- Change Password Tab -->
                        <div id="password-tab" class="profile-tab-content">
                            <div class="mb-4">
                                <label for="current_password" class="form-label">Password Saat Ini</label>
                                <div class="input-group">
                                    <input type="password"
                                        class="form-control"
                                        id="current_password"
                                        name="current_password"
                                        placeholder="Masukkan password saat ini">
                                    <button class="btn btn-outline-secondary" type="button" onclick="togglePassword('current_password')">
                                        <i class="bi bi-eye" id="current_password_icon"></i>
                                    </button>
                                </div>
                                <div class="form-text">Wajib diisi jika ingin mengubah password.</div>
                            </div>
                            <div class="mb-4">
                                <label for="new_password" class="form-label">Password Baru</label>
                                <div class="input-group">
                                    <input type="password"
                                        class="form-control"
                                        id="new_password"
                                        name="new_password"
                                        minlength="8"
                                        placeholder="Masukkan password baru">
                                    <button class="btn btn-outline-secondary" type="button" onclick="togglePassword('new_password')">
                                        <i class="bi bi-eye" id="new_password_icon"></i>
                                    </button>
                                </div>
                                <div class="form-text">
                                    Minimal 8 karakter dengan kombinasi huruf besar, kecil, angka, dan simbol.
                                    <div id="password-strength" class="mt-1"></div>
                                    <div class="password-strength-meter">
                                        <div id="password-strength-meter-fill" class="password-strength-meter-fill"></div>
                                    </div>
                                </div>
                            </div>
                            <div class="mb-4">
                                <label for="confirm_password" class="form-label">Konfirmasi Password Baru</label>
                                <div class="input-group">
                                    <input type="password"
                                        class="form-control"
                                        id="confirm_password"
                                        name="confirm_password"
                                        placeholder="Ulangi password baru">
                                    <button class="btn btn-outline-secondary" type="button" onclick="togglePassword('confirm_password')">
                                        <i class="bi bi-eye" id="confirm_password_icon"></i>
                                    </button>
                                </div>
                                <div class="form-text">Harus sama dengan password baru.</div>
                                <div id="password-match" class="mt-1"></div>
                            </div>
                        </div>
                        <!-- Action Buttons -->
                        <div class="d-flex justify-content-between mt-4">
                            <a href="../../index.php" class="btn btn-outline-secondary">
                                <i class="bi bi-arrow-left me-2"></i>Kembali ke Dashboard
                            </a>
                            <button type="submit" class="btn btn-primary" id="submitBtn">
                                <i class="bi bi-check-lg me-2"></i>Simpan Perubahan
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
<script>
    // Tab functionality
    document.querySelectorAll('.profile-tab').forEach(tab => {
        tab.addEventListener('click', function() {
            // Remove active class from all tabs and contents
            document.querySelectorAll('.profile-tab').forEach(t => t.classList.remove('active'));
            document.querySelectorAll('.profile-tab-content').forEach(c => c.classList.remove('active'));
            // Add active class to clicked tab
            this.classList.add('active');
            // Show corresponding content
            const tabId = this.getAttribute('data-tab');
            document.getElementById(tabId).classList.add('active');
        });
    });
    // Profile image upload preview
    document.getElementById('profile_image_input').addEventListener('change', function(e) {
        if (this.files && this.files[0]) {
            const reader = new FileReader();
            reader.onload = function(e) {
                // Check if there's already an image or placeholder
                const profileImage = document.querySelector('.profile-image');
                const profilePlaceholder = document.querySelector('.profile-image-placeholder');
                if (profileImage) {
                    profileImage.src = e.target.result;
                } else if (profilePlaceholder) {
                    // Replace placeholder with image
                    const container = document.querySelector('.profile-image-container');
                    container.innerHTML = `
                        <img src="${e.target.result}" alt="Profile Image" class="profile-image">
                        <label for="profile_image_input" class="profile-image-upload">
                            <i class="bi bi-camera-fill"></i>
                        </label>
                        <input type="file" id="profile_image_input" name="profile_image" accept="image/*" style="display: none;">
                    `;
                    // Re-attach event listener to the new input
                    document.getElementById('profile_image_input').addEventListener('change', arguments.callee);
                }
                // Also update the image in the edit tab
                const editTabImage = document.querySelector('#edit-tab .rounded-circle');
                if (editTabImage && editTabImage.tagName === 'IMG') {
                    editTabImage.src = e.target.result;
                } else {
                    // Replace placeholder with image in edit tab
                    const editTabContainer = document.querySelector('#edit-tab .d-flex.align-items-center div:first-child');
                    editTabContainer.innerHTML = `<img src="${e.target.result}" alt="Profile Image" class="rounded-circle" width="80" height="80" style="object-fit: cover;">`;
                }
            }
            reader.readAsDataURL(this.files[0]);
        }
    });
    // Also handle the profile image input in the edit tab
    document.getElementById('profile_image').addEventListener('change', function(e) {
        if (this.files && this.files[0]) {
            const reader = new FileReader();
            reader.onload = function(e) {
                // Update the image in the edit tab
                const editTabImage = document.querySelector('#edit-tab .rounded-circle');
                if (editTabImage && editTabImage.tagName === 'IMG') {
                    editTabImage.src = e.target.result;
                } else {
                    // Replace placeholder with image in edit tab
                    const editTabContainer = document.querySelector('#edit-tab .d-flex.align-items-center div:first-child');
                    editTabContainer.innerHTML = `<img src="${e.target.result}" alt="Profile Image" class="rounded-circle" width="80" height="80" style="object-fit: cover;">`;
                }
                // Also update the header image
                const headerImage = document.querySelector('.profile-image');
                const headerPlaceholder = document.querySelector('.profile-image-placeholder');
                if (headerImage) {
                    headerImage.src = e.target.result;
                } else if (headerPlaceholder) {
                    // Replace placeholder with image
                    const container = document.querySelector('.profile-image-container');
                    container.innerHTML = `
                        <img src="${e.target.result}" alt="Profile Image" class="profile-image">
                        <label for="profile_image_input" class="profile-image-upload">
                            <i class="bi bi-camera-fill"></i>
                        </label>
                        <input type="file" id="profile_image_input" name="profile_image" accept="image/*" style="display: none;">
                    `;
                    // Re-attach event listener to the new input
                    document.getElementById('profile_image_input').addEventListener('change', arguments.callee);
                }
            }
            reader.readAsDataURL(this.files[0]);
        }
    });
    // Toggle password visibility
    function togglePassword(fieldId) {
        const field = document.getElementById(fieldId);
        const icon = document.getElementById(fieldId + '_icon');
        if (field.type === 'password') {
            field.type = 'text';
            icon.className = 'bi bi-eye-slash';
        } else {
            field.type = 'password';
            icon.className = 'bi bi-eye';
        }
    }
    // Password strength indicator
    function checkPasswordStrength(password) {
        let strength = 0;
        let feedback = [];
        if (password.length >= 8) strength++;
        else feedback.push('minimal 8 karakter');
        if (/[A-Z]/.test(password)) strength++;
        else feedback.push('huruf besar');
        if (/[a-z]/.test(password)) strength++;
        else feedback.push('huruf kecil');
        if (/[0-9]/.test(password)) strength++;
        else feedback.push('angka');
        if (/[^A-Za-z0-9]/.test(password)) strength++;
        else feedback.push('karakter khusus');
        const strengthElement = document.getElementById('password-strength');
        const meterFill = document.getElementById('password-strength-meter-fill');
        if (password.length === 0) {
            strengthElement.innerHTML = '';
            meterFill.className = 'password-strength-meter-fill';
            return;
        }
        let strengthText = '';
        let strengthClass = '';
        let meterClass = '';
        if (strength < 3) {
            strengthText = 'Lemah';
            strengthClass = 'text-danger';
            meterClass = 'strength-weak';
        } else if (strength < 4) {
            strengthText = 'Sedang';
            strengthClass = 'text-warning';
            meterClass = 'strength-fair';
        } else if (strength < 5) {
            strengthText = 'Kuat';
            strengthClass = 'text-info';
            meterClass = 'strength-good';
        } else {
            strengthText = 'Sangat Kuat';
            strengthClass = 'text-success';
            meterClass = 'strength-strong';
        }
        strengthElement.innerHTML = `<small class="${strengthClass}">Kekuatan password: ${strengthText}</small>`;
        if (feedback.length > 0) {
            strengthElement.innerHTML += `<br><small class="text-muted">Perlu: ${feedback.join(', ')}</small>`;
        }
        meterFill.className = 'password-strength-meter-fill ' + meterClass;
    }
    // Password match indicator
    function checkPasswordMatch() {
        const newPassword = document.getElementById('new_password').value;
        const confirmPassword = document.getElementById('confirm_password').value;
        const matchElement = document.getElementById('password-match');
        if (confirmPassword.length === 0) {
            matchElement.innerHTML = '';
            return;
        }
        if (newPassword === confirmPassword) {
            matchElement.innerHTML = '<small class="text-success"><i class="bi bi-check-circle me-1"></i>Password cocok</small>';
        } else {
            matchElement.innerHTML = '<small class="text-danger"><i class="bi bi-x-circle me-1"></i>Password tidak cocok</small>';
        }
    }
    // Event listeners
    document.getElementById('new_password').addEventListener('input', function() {
        checkPasswordStrength(this.value);
        checkPasswordMatch();
    });
    document.getElementById('confirm_password').addEventListener('input', checkPasswordMatch);
    // Form validation
    document.getElementById('profileForm').addEventListener('submit', function(e) {
        const newPassword = document.getElementById('new_password').value;
        const confirmPassword = document.getElementById('confirm_password').value;
        const currentPassword = document.getElementById('current_password').value;
        const username = document.getElementById('username').value.trim();
        // Username validation
        if (!username) {
            e.preventDefault();
            alert('Username tidak boleh kosong.');
            return;
        }
        if (username.length < 3) {
            e.preventDefault();
            alert('Username minimal 3 karakter.');
            return;
        }
        if (!/^[a-zA-Z0-9_]+$/.test(username)) {
            e.preventDefault();
            alert('Username hanya boleh mengandung huruf, angka, dan underscore.');
            return;
        }
        // Password validation if changing password
        if (newPassword) {
            if (!currentPassword) {
                e.preventDefault();
                alert('Password saat ini harus diisi untuk mengubah password.');
                return;
            }
            if (newPassword.length < 8) {
                e.preventDefault();
                alert('Password baru minimal 8 karakter.');
                return;
            }
            if (newPassword !== confirmPassword) {
                e.preventDefault();
                alert('Konfirmasi password tidak cocok.');
                return;
            }
            // Check password strength
            let strength = 0;
            if (/[A-Z]/.test(newPassword)) strength++;
            if (/[a-z]/.test(newPassword)) strength++;
            if (/[0-9]/.test(newPassword)) strength++;
            if (/[^A-Za-z0-9]/.test(newPassword)) strength++;
            if (strength < 4) {
                if (!confirm('Password Anda tidak memenuhi semua kriteria keamanan. Tetap lanjutkan?')) {
                    e.preventDefault();
                    return;
                }
            }
        }
        // Show loading state
        const submitBtn = document.getElementById('submitBtn');
        submitBtn.disabled = true;
        submitBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Menyimpan...';
    });
    // Auto-hide alerts after 5 seconds
    setTimeout(function() {
        const alerts = document.querySelectorAll('.alert');
        alerts.forEach(function(alert) {
            if (typeof bootstrap !== 'undefined') {
                const bsAlert = new bootstrap.Alert(alert);
                bsAlert.close();
            }
        });
    }, 5000);
</script>
<?php include '../../includes/footer.php'; ?>