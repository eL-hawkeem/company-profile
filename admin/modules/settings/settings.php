<?php

/**
 * Website Settings Management
 * PT. Sarana Sentra Teknologi Utama - Admin Dashboard
 * 
 * Mengelola pengaturan website, banner, team members, testimonial, dan layanan
 */
require_once '../../config/auth.php';
require_once '../../config/database.php';
require_once '../../includes/functions.php';
require_once 'process/process_settings.php';

requireAuth();

$page_title = 'Pengaturan Website';
$db = Database::getInstance();
$settingsProcessor = new SettingsProcessor($db);
$message = '';
$message_type = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $result = $settingsProcessor->handleRequest($_POST, $_FILES);
    $message = $result['message'];
    $message_type = $result['type'];
}
// Get current data
$data = $settingsProcessor->getAllData();
include '../../includes/header.php';
?>
<style>
    :root {
        --primary-color: #4e73df;
        --success-color: #1cc88a;
        --warning-color: #f6c23e;
        --info-color: #36b9cc;
        --danger-color: #e74a3b;
    }

    .settings-tabs {
        display: flex;
        gap: 0.5rem;
        margin-bottom: 1.5rem;
        flex-wrap: wrap;
    }

    .tab-button {
        background: #f8f9fc;
        border: 1px solid #e3e6f0;
        padding: 0.75rem 1.5rem;
        border-radius: 0.35rem;
        font-weight: 400;
        color: #5a5c69;
        cursor: pointer;
        transition: all 0.15s ease-in-out;
        display: flex;
        align-items: center;
        gap: 0.5rem;
        font-size: 0.875rem;
    }

    .tab-button:hover {
        background: #eaecf4;
        border-color: #d1d3e2;
        color: #3a3b45;
    }

    .tab-button.active {
        background: var(--primary-color);
        border-color: var(--primary-color);
        color: white;
    }

    .tab-content {
        display: none;
    }

    .tab-content.active {
        display: block;
    }

    .form-section-title {
        color: var(--primary-color);
        font-weight: 700;
        font-size: 1rem;
        margin-bottom: 1rem;
        padding-bottom: 0.5rem;
        border-bottom: 1px solid #e3e6f0;
    }

    .dynamic-list-item {
        background: #f8f9fc;
        border: 1px solid #e3e6f0;
        border-radius: 0.35rem;
        padding: 0.75rem;
        margin-bottom: 0.5rem;
        display: flex;
        align-items: center;
        gap: 0.75rem;
    }

    .dynamic-list-item input {
        flex: 1;
        border: none;
        background: transparent;
        font-size: 0.875rem;
    }

    .dynamic-list-item input:focus {
        outline: none;
        background: white;
        border: 1px solid #d1d3e2;
        border-radius: 0.25rem;
        padding: 0.375rem;
    }

    .item-card {
        background: white;
        border: 1px solid #e3e6f0;
        border-radius: 0.35rem;
        padding: 1.5rem;
        margin-bottom: 1rem;
        transition: box-shadow 0.15s ease-in-out;
        position: relative;
    }

    .item-card:hover {
        box-shadow: 0 0.15rem 1.75rem 0 rgba(58, 59, 69, 0.15);
    }

    .item-actions {
        position: absolute;
        top: 1rem;
        right: 1rem;
        display: flex;
        gap: 0.25rem;
    }

    .btn-icon {
        width: 32px;
        height: 32px;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        border: none;
        font-size: 0.75rem;
        transition: all 0.15s ease-in-out;
    }

    .btn-edit {
        background: var(--info-color);
        color: white;
    }

    .btn-edit:hover {
        background: #2c9faf;
    }

    .btn-delete {
        background: var(--danger-color);
        color: white;
    }

    .btn-delete:hover {
        background: #c0392b;
    }

    .grid-container {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
        gap: 1.5rem;
        margin-top: 1.5rem;
    }

    .add-button {
        background: var(--success-color);
        border: 1px solid var(--success-color);
        color: white;
        padding: 0.5rem 1rem;
        border-radius: 0.35rem;
        font-size: 0.875rem;
        display: flex;
        align-items: center;
        gap: 0.5rem;
        transition: all 0.15s ease-in-out;
        text-decoration: none;
    }

    .add-button:hover {
        background: #17a673;
        border-color: #169b6b;
        color: white;
    }

    .image-preview-custom {
        border: 2px dashed #d1d3e2;
        border-radius: 0.35rem;
        padding: 1.5rem;
        text-align: center;
        background: #f8f9fc;
        margin-top: 1rem;
    }

    .image-preview-custom img {
        max-width: 200px;
        max-height: 200px;
        border-radius: 0.35rem;
        box-shadow: 0 0.15rem 1.75rem 0 rgba(58, 59, 69, 0.15);
    }

    .modal-content {
        border-radius: 0.35rem;
        border: none;
        box-shadow: 0 0.15rem 1.75rem 0 rgba(58, 59, 69, 0.15);
    }

    .modal-header-custom {
        background: #f8f9fc;
        border-bottom: 1px solid #e3e6f0;
        padding: 1.5rem;
    }

    .modal-header-custom h5 {
        color: #5a5c69;
        font-weight: 700;
        margin: 0;
    }

    .alert-custom {
        border: none;
        border-radius: 0.35rem;
        padding: 1rem 1.25rem;
        margin-bottom: 1.5rem;
    }

    @media (max-width: 768px) {
        .settings-tabs {
            flex-direction: column;
        }

        .grid-container {
            grid-template-columns: 1fr;
        }

        .item-actions {
            position: static;
            margin-top: 1rem;
            justify-content: center;
        }
    }
</style>
<div class="container-fluid">
    <div class="d-sm-flex align-items-center justify-content-between mb-4">
        <h1 class="h3 mb-0 text-gray-800">
            <i class="fas fa-cog"></i> Pengaturan Website
        </h1>
        <div class="text-muted">
            Kelola semua elemen dan konfigurasi website company profile Anda
        </div>
    </div>
    <?php if ($message): ?>
        <div class="alert alert-<?= $message_type ?> alert-dismissible fade show" role="alert">
            <i class="fas fa-<?= $message_type === 'success' ? 'check-circle' : 'exclamation-triangle' ?>"></i>
            <?= htmlspecialchars($message) ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>
    <div class="settings-tabs">
        <button class="tab-button active" data-tab="general">
            <i class="fas fa-cog"></i> Pengaturan Umum
        </button>
        <button class="tab-button" data-tab="banners">
            <i class="fas fa-images"></i> Banner
        </button>
        <button class="tab-button" data-tab="services">
            <i class="fas fa-concierge-bell"></i> Layanan
        </button>
        <button class="tab-button" data-tab="team">
            <i class="fas fa-users"></i> Tim Kami
        </button>
        <button class="tab-button" data-tab="testimonials">
            <i class="fas fa-quote-left"></i> Testimonial
        </button>
    </div>
    <div class="tab-content active" id="general">
        <div class="card shadow mb-4">
            <div class="card-header py-3">
                <h6 class="m-0 font-weight-bold text-primary">
                    <i class="fas fa-cog"></i> Konfigurasi Website
                </h6>
            </div>
            <div class="card-body">
                <form method="POST" action="">
                    <input type="hidden" name="action" value="update_site_settings">
                    <div class="row">
                        <div class="col-12">
                            <h6 class="form-section-title">
                                <i class="fas fa-eye"></i> Visi & Misi
                            </h6>
                        </div>
                        <div class="col-md-12 mb-4">
                            <label for="visi" class="form-label font-weight-bold">Visi Perusahaan *</label>
                            <textarea class="form-control" name="visi" id="visi" rows="3" required><?= htmlspecialchars($data['siteSettings']['visi'] ?? '') ?></textarea>
                        </div>
                        <div class="col-md-12">
                            <label class="form-label font-weight-bold">Misi Perusahaan *</label>
                            <div id="misi-container">
                                <?php
                                $misiList = $data['siteSettings']['misi'] ?? [];
                                if (is_array($misiList)):
                                    foreach ($misiList as $index => $misi): ?>
                                        <div class="dynamic-list-item">
                                            <i class="fas fa-arrow-right text-primary"></i>
                                            <input type="text" class="form-control" name="misi[]" value="<?= htmlspecialchars($misi) ?>" required>
                                            <button type="button" class="btn btn-outline-danger btn-sm" onclick="removeItem(this)">
                                                <i class="fas fa-trash"></i>
                                            </button>
                                        </div>
                                <?php endforeach;
                                endif; ?>
                            </div>
                            <button type="button" class="btn btn-success btn-sm mt-2" onclick="addMisi()">
                                <i class="fas fa-plus"></i> Tambah Misi
                            </button>
                        </div>
                    </div>
                    <hr class="my-4">
                    <div class="row">
                        <div class="col-12 mb-3">
                            <h6 class="form-section-title">
                                <i class="fas fa-phone"></i> Informasi Kontak
                            </h6>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="address" class="form-label font-weight-bold">Alamat Perusahaan</label>
                                <textarea class="form-control" name="contact_address" id="address" rows="3"><?= htmlspecialchars($data['siteSettings']['contact_address'] ?? '') ?></textarea>
                            </div>
                            <div class="mb-3">
                                <label for="phone" class="form-label font-weight-bold">No. Telepon</label>
                                <input type="text" class="form-control" name="contact_phone" id="phone" value="<?= htmlspecialchars($data['siteSettings']['contact_phone'] ?? '') ?>">
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="phone" class="form-label font-weight-bold">Nomor WhatsApp</label>
                                <input type="text" class="form-control" name="contact_whatsapp" id="phone" value="<?= htmlspecialchars($data['siteSettings']['contact_whatsapp'] ?? '') ?>">
                            </div>
                            <div class="mb-3">
                                <label for="email" class="form-label font-weight-bold">Email</label>
                                <input type="email" class="form-control" name="contact_email" id="email" value="<?= htmlspecialchars($data['siteSettings']['contact_email'] ?? '') ?>">
                            </div>
                            <div class="mb-3">
                                <label for="hours" class="form-label font-weight-bold">Jam Operasional</label>
                                <input type="text" class="form-control" name="contact_hours" id="hours" value="<?= htmlspecialchars($data['siteSettings']['contact_hours'] ?? '') ?>">
                            </div>
                        </div>
                        <div class="col-md-12">
                            <div class="mb-3">
                                <label for="maps" class="form-label font-weight-bold">Google Maps Embed URL</label>
                                <input type="url" class="form-control" name="Maps_url" id="maps" value="<?= htmlspecialchars($data['siteSettings']['Maps_url'] ?? '') ?>">
                            </div>
                        </div>
                    </div>
                    <hr class="my-4">
                    <div class="row">
                        <div class="col-12 mb-3">
                            <h6 class="form-section-title">
                                <i class="fas fa-bullhorn"></i> Call to Action
                            </h6>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="cta_title" class="form-label font-weight-bold">Judul CTA</label>
                                <input type="text" class="form-control" name="cta_title" id="cta_title" value="<?= htmlspecialchars($data['siteSettings']['cta_title'] ?? '') ?>">
                            </div>
                            <div class="mb-3">
                                <label for="cta_btn_text" class="form-label font-weight-bold">Teks Tombol</label>
                                <input type="text" class="form-control" name="cta_button_text" id="cta_btn_text" value="<?= htmlspecialchars($data['siteSettings']['cta_button_text'] ?? '') ?>">
                            </div>
                            <div class="mb-3">
                                <label for="cta_btn_link" class="form-label font-weight-bold">Link Tombol</label>
                                <input type="text" class="form-control" name="cta_button_link" id="cta_btn_link" value="<?= htmlspecialchars($data['siteSettings']['cta_button_link'] ?? '') ?>">
                                <small class="form-text text-muted">Contoh: layanan.php, kontak.php, https://example.com</small>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="cta_text" class="form-label font-weight-bold">Deskripsi CTA</label>
                                <textarea class="form-control" name="cta_text" id="cta_text" rows="4"><?= htmlspecialchars($data['siteSettings']['cta_text'] ?? '') ?></textarea>
                            </div>
                        </div>
                    </div>
                    <hr class="my-4">
                    <div class="row">
                        <div class="col-12 mb-3">
                            <h6 class="form-section-title">
                                <i class="fas fa-info-circle"></i> Tentang Perusahaan
                            </h6>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="about_title" class="form-label font-weight-bold">Judul Section About</label>
                                <input type="text" class="form-control" name="about_title" id="about_title" value="<?= htmlspecialchars($data['siteSettings']['about_title'] ?? '') ?>">
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="about_text" class="form-label font-weight-bold">Deskripsi About</label>
                                <textarea class="form-control" name="about_text" id="about_text" rows="4"><?= htmlspecialchars($data['siteSettings']['about_text'] ?? '') ?></textarea>
                            </div>
                        </div>
                        <div class="col-md-12">
                            <label class="form-label font-weight-bold">Fitur/Keunggulan</label>
                            <div id="features-container">
                                <?php
                                $aboutFeatures = $data['siteSettings']['about_features'] ?? [];
                                if (is_array($aboutFeatures)):
                                    foreach ($aboutFeatures as $feature): ?>
                                        <div class="dynamic-list-item">
                                            <i class="fas fa-check-circle text-success"></i>
                                            <input type="text" class="form-control" name="about_features[]" value="<?= htmlspecialchars($feature) ?>" required>
                                            <button type="button" class="btn btn-outline-danger btn-sm" onclick="removeItem(this)">
                                                <i class="fas fa-trash"></i>
                                            </button>
                                        </div>
                                <?php endforeach;
                                endif; ?>
                            </div>
                            <button type="button" class="btn btn-success btn-sm mt-2" onclick="addFeature()">
                                <i class="fas fa-plus"></i> Tambah Fitur
                            </button>
                        </div>
                    </div>
                    <div class="text-right mt-4">
                        <button type="submit" class="btn btn-primary btn-lg">
                            <i class="fas fa-save"></i> Simpan Semua Perubahan
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    <div class="tab-content" id="banners">
        <div class="card shadow mb-4">
            <div class="card-header py-3">
                <h6 class="m-0 font-weight-bold text-primary">
                    <i class="fas fa-images"></i> Manajemen Banner
                </h6>
            </div>
            <div class="card-body">
                <div class="grid-container">
                    <?php foreach ($data['banners'] as $banner): ?>
                        <div class="item-card">
                            <div class="item-actions">
                                <button type="button" class="btn btn-icon btn-edit"
                                    onclick="editBanner(<?= htmlspecialchars(json_encode($banner)) ?>)">
                                    <i class="fas fa-edit"></i>
                                </button>
                            </div>
                            <div class="text-center mb-3">
                                <?php if ($banner['image_path']): ?>
                                    <img src="../../uploads/banners/<?= htmlspecialchars(basename($banner['image_path'])) ?>"
                                        alt="Banner" class="img-fluid rounded" style="max-height: 150px;">
                                <?php elseif ($banner['image_data']): ?>
                                    <img src="data:image/jpeg;base64,<?= htmlspecialchars($banner['image_data']) ?>"
                                        alt="Banner" class="img-fluid rounded" style="max-height: 150px;">
                                <?php else: ?>
                                    <div class="bg-light rounded p-4">
                                        <i class="fas fa-image fa-3x text-muted"></i>
                                    </div>
                                <?php endif; ?>
                            </div>
                            <h6 class="font-weight-bold mb-2"><?= htmlspecialchars($banner['title']) ?></h6>
                            <p class="text-muted small mb-2"><?= htmlspecialchars(substr($banner['subtitle'], 0, 100)) ?>...</p>
                            <?php if ($banner['button_text']): ?>
                                <span class="badge badge-primary"><?= htmlspecialchars($banner['button_text']) ?></span>
                            <?php endif; ?>
                            <div class="mt-2">
                                <span class="badge <?= $banner['is_active'] ? 'badge-success' : 'badge-secondary' ?>">
                                    <?= $banner['is_active'] ? 'Aktif' : 'Non-aktif' ?>
                                </span>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
    </div>
    <div class="tab-content" id="services">
        <div class="card shadow mb-4">
            <div class="card-header py-3 d-flex justify-content-between align-items-center">
                <h6 class="m-0 font-weight-bold text-primary">
                    <i class="fas fa-concierge-bell"></i> Manajemen Layanan
                </h6>
                <button type="button" class="add-button" data-bs-toggle="modal" data-bs-target="#addServiceModal">
                    <i class="fas fa-plus"></i> Tambah Layanan
                </button>
            </div>
            <div class="card-body">
                <div class="grid-container">
                    <?php foreach ($data['services'] as $service): ?>
                        <div class="item-card">
                            <div class="item-actions">
                                <button type="button" class="btn btn-icon btn-edit"
                                    onclick="editService(<?= htmlspecialchars(json_encode($service)) ?>)">
                                    <i class="fas fa-edit"></i>
                                </button>
                                <button type="button" class="btn btn-icon btn-delete"
                                    onclick="deleteService(<?= $service['id'] ?>)">
                                    <i class="fas fa-trash"></i>
                                </button>
                            </div>
                            <div class="text-center mb-3">
                                <?php if ($service['image_path']): ?>
                                    <img src="../../uploads/services/<?= htmlspecialchars(basename($service['image_path'])) ?>"
                                        alt="<?= htmlspecialchars($service['title']) ?>"
                                        class="img-fluid rounded" style="max-height: 150px;">
                                <?php elseif ($service['image_data']): ?>
                                    <img src="data:image/jpeg;base64,<?= htmlspecialchars($service['image_data']) ?>"
                                        alt="<?= htmlspecialchars($service['title']) ?>"
                                        class="img-fluid rounded" style="max-height: 150px;">
                                <?php else: ?>
                                    <div class="bg-light rounded p-4">
                                        <i class="fas fa-concierge-bell fa-3x text-muted"></i>
                                    </div>
                                <?php endif; ?>
                            </div>
                            <h6 class="font-weight-bold mb-2"><?= htmlspecialchars($service['title']) ?></h6>
                            <p class="text-muted small mb-2"><?= htmlspecialchars(substr($service['description'], 0, 100)) ?>...</p>
                            <div class="mt-2">
                                <span class="badge badge-info">Slug: <?= htmlspecialchars($service['service_slug']) ?></span>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
    </div>
    <div class="tab-content" id="team">
        <div class="card shadow mb-4">
            <div class="card-header py-3 d-flex justify-content-between align-items-center">
                <h6 class="m-0 font-weight-bold text-primary">
                    <i class="fas fa-users"></i> Manajemen Tim
                </h6>
                <button type="button" class="add-button" data-bs-toggle="modal" data-bs-target="#addTeamModal">
                    <i class="fas fa-plus"></i> Tambah Anggota
                </button>
            </div>
            <div class="card-body">
                <div class="grid-container">
                    <?php foreach ($data['teamMembers'] as $member): ?>
                        <div class="item-card">
                            <div class="item-actions">
                                <button type="button" class="btn btn-icon btn-edit"
                                    onclick="editTeamMember(<?= htmlspecialchars(json_encode($member)) ?>)">
                                    <i class="fas fa-edit"></i>
                                </button>
                                <button type="button" class="btn btn-icon btn-delete"
                                    onclick="deleteTeamMember(<?= $member['id'] ?>)">
                                    <i class="fas fa-trash"></i>
                                </button>
                            </div>
                            <div class="text-center">
                                <?php if ($member['image_path']): ?>
                                    <img src="../../uploads/team/<?= htmlspecialchars(basename($member['image_path'])) ?>"
                                        alt="<?= htmlspecialchars($member['name']) ?>"
                                        class="rounded-circle mb-3" style="width: 100px; height: 100px; object-fit: cover;">
                                <?php elseif ($member['image_data']): ?>
                                    <img src="data:image/jpeg;base64,<?= htmlspecialchars($member['image_data']) ?>"
                                        alt="<?= htmlspecialchars($member['name']) ?>"
                                        class="rounded-circle mb-3" style="width: 100px; height: 100px; object-fit: cover;">
                                <?php else: ?>
                                    <div class="bg-light rounded-circle mb-3 mx-auto d-flex align-items-center justify-content-center"
                                        style="width: 100px; height: 100px;">
                                        <i class="fas fa-user fa-2x text-muted"></i>
                                    </div>
                                <?php endif; ?>
                                <h6 class="font-weight-bold mb-1"><?= htmlspecialchars($member['name']) ?></h6>
                                <p class="text-muted mb-2"><?= htmlspecialchars($member['position']) ?></p>
                                <span class="badge badge-info">Urutan: <?= $member['display_order'] ?></span>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
    </div>
    <div class="tab-content" id="testimonials">
        <div class="card shadow mb-4">
            <div class="card-header py-3 d-flex justify-content-between align-items-center">
                <h6 class="m-0 font-weight-bold text-primary">
                    <i class="fas fa-quote-left"></i> Manajemen Testimonial
                </h6>
                <button type="button" class="add-button" data-bs-toggle="modal" data-bs-target="#addTestimonialModal">
                    <i class="fas fa-plus"></i> Tambah Testimonial
                </button>
            </div>
            <div class="card-body">
                <div class="grid-container">
                    <?php foreach ($data['testimonials'] as $testimonial): ?>
                        <div class="item-card">
                            <div class="item-actions">
                                <button type="button" class="btn btn-icon btn-edit"
                                    onclick="editTestimonial(<?= htmlspecialchars(json_encode($testimonial)) ?>)">
                                    <i class="fas fa-edit"></i>
                                </button>
                                <button type="button" class="btn btn-icon btn-delete"
                                    onclick="deleteTestimonial(<?= $testimonial['id'] ?>)">
                                    <i class="fas fa-trash"></i>
                                </button>
                            </div>
                            <div class="d-flex align-items-start">
                                <?php if ($testimonial['image_path']): ?>
                                    <!-- PERBAIKAN: Menampilkan gambar dengan path yang benar -->
                                    <img src="../../uploads/testimonials/<?= htmlspecialchars(basename($testimonial['image_path'])) ?>"
                                        alt="<?= htmlspecialchars($testimonial['client_name']) ?>"
                                        class="rounded-circle me-3" style="width: 80px; height: 80px; object-fit: cover;">
                                <?php else: ?>
                                    <div class="bg-light rounded-circle me-3 d-flex align-items-center justify-content-center"
                                        style="width: 80px; height: 80px;">
                                        <i class="fas fa-user fa-2x text-muted"></i>
                                    </div>
                                <?php endif; ?>
                                <div class="flex-grow-1">
                                    <h6 class="font-weight-bold mb-1"><?= htmlspecialchars($testimonial['client_name']) ?></h6>
                                    <p class="text-muted small mb-2"><?= htmlspecialchars($testimonial['client_position']) ?></p>
                                    <p class="mb-2">"<?= htmlspecialchars(substr($testimonial['quote'], 0, 120)) ?>..."</p>
                                    <span class="badge <?= $testimonial['is_active'] ? 'badge-success' : 'badge-secondary' ?>">
                                        <?= $testimonial['is_active'] ? 'Aktif' : 'Non-aktif' ?>
                                    </span>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
    </div>
</div>
<!-- Modal untuk Banner -->
<div class="modal fade" id="editBannerModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">
                    <i class="fas fa-edit"></i> Edit Banner
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST" action="" enctype="multipart/form-data">
                <input type="hidden" name="action" value="update_banner">
                <input type="hidden" name="banner_id" id="edit_banner_id">
                <div class="modal-body">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label font-weight-bold">Judul Banner *</label>
                                <input type="text" class="form-control" name="title" id="edit_banner_title" required>
                            </div>
                            <div class="mb-3">
                                <label class="form-label font-weight-bold">Subtitle</label>
                                <textarea class="form-control" name="subtitle" id="edit_banner_subtitle" rows="3"></textarea>
                            </div>
                            <div class="mb-3">
                                <label class="form-label font-weight-bold">Teks Tombol</label>
                                <input type="text" class="form-control" name="button_text" id="edit_banner_btn_text">
                            </div>
                            <div class="mb-3">
                                <label class="form-label font-weight-bold">Link Tombol</label>
                                <input type="text" class="form-control" name="button_link" id="edit_banner_btn_link">
                                <small class="form-text text-muted">Contoh: layanan.php, kontak.php, https://example.com</small>
                            </div>
                            <div class="mb-3">
                                <div class="form-check form-switch">
                                    <input class="form-check-input" type="checkbox" name="is_active" id="edit_banner_active">
                                    <label class="form-check-label font-weight-bold" for="edit_banner_active">
                                        Status Aktif
                                    </label>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label font-weight-bold">Gambar Banner</label>
                            <input type="file" class="form-control mb-3" name="banner_image" accept="image/*"
                                onchange="previewImage(this, 'edit-banner-preview')">
                            <div class="image-preview-custom" id="edit-banner-preview">
                                <i class="fas fa-image fa-3x text-muted"></i>
                                <p class="mt-2 mb-0 text-muted">Pilih gambar untuk preview</p>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save"></i> Update Banner
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
<!-- Modal untuk Layanan -->
<div class="modal fade" id="addServiceModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">
                    <i class="fas fa-plus"></i> Tambah Layanan
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST" action="" enctype="multipart/form-data">
                <input type="hidden" name="action" value="add_service">
                <div class="modal-body">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label font-weight-bold">Slug Layanan *</label>
                                <input type="text" class="form-control" name="service_slug" id="add_service_slug" required>
                                <small class="form-text text-muted">Gunakan huruf kecil, tanpa spasi, contoh: pengadaan</small>
                            </div>
                            <div class="mb-3">
                                <label class="form-label font-weight-bold">Judul Layanan *</label>
                                <input type="text" class="form-control" name="title" id="add_service_title" required>
                            </div>
                            <div class="mb-3">
                                <label class="form-label font-weight-bold">Deskripsi Layanan *</label>
                                <textarea class="form-control" name="description" id="add_service_description" rows="4" required></textarea>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label font-weight-bold">Gambar Layanan</label>
                            <input type="file" class="form-control mb-3" name="service_image" accept="image/*"
                                onchange="previewImage(this, 'add-service-preview')">
                            <div class="image-preview-custom" id="add-service-preview">
                                <i class="fas fa-concierge-bell fa-3x text-muted"></i>
                                <p class="mt-2 mb-0 text-muted">Pilih gambar untuk preview</p>
                            </div>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label font-weight-bold">Fitur Layanan</label>
                        <div id="add-features-container">
                            <div class="dynamic-list-item">
                                <i class="fas fa-check-circle text-success"></i>
                                <input type="text" class="form-control" name="features[]" placeholder="Masukkan fitur layanan" required>
                                <button type="button" class="btn btn-outline-danger btn-sm" onclick="removeItem(this)">
                                    <i class="fas fa-trash"></i>
                                </button>
                            </div>
                        </div>
                        <button type="button" class="btn btn-success btn-sm mt-2" onclick="addServiceFeature()">
                            <i class="fas fa-plus"></i> Tambah Fitur
                        </button>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-plus"></i> Tambah Layanan
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
<div class="modal fade" id="editServiceModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">
                    <i class="fas fa-edit"></i> Edit Layanan
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST" action="" enctype="multipart/form-data">
                <input type="hidden" name="action" value="update_service">
                <input type="hidden" name="service_id" id="edit_service_id">
                <div class="modal-body">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label font-weight-bold">Slug Layanan *</label>
                                <input type="text" class="form-control" name="service_slug" id="edit_service_slug" required>
                                <small class="form-text text-muted">Gunakan huruf kecil, tanpa spasi, contoh: pengadaan</small>
                            </div>
                            <div class="mb-3">
                                <label class="form-label font-weight-bold">Judul Layanan *</label>
                                <input type="text" class="form-control" name="title" id="edit_service_title" required>
                            </div>
                            <div class="mb-3">
                                <label class="form-label font-weight-bold">Deskripsi Layanan *</label>
                                <textarea class="form-control" name="description" id="edit_service_description" rows="4" required></textarea>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label font-weight-bold">Gambar Layanan</label>
                            <input type="file" class="form-control mb-3" name="service_image" accept="image/*"
                                onchange="previewImage(this, 'edit-service-preview')">
                            <div class="image-preview-custom" id="edit-service-preview">
                                <i class="fas fa-concierge-bell fa-3x text-muted"></i>
                                <p class="mt-2 mb-0 text-muted">Pilih gambar untuk preview</p>
                            </div>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label font-weight-bold">Fitur Layanan</label>
                        <div id="edit-features-container">
                            <!-- Fitur akan diisi melalui JavaScript -->
                        </div>
                        <button type="button" class="btn btn-success btn-sm mt-2" onclick="addEditServiceFeature()">
                            <i class="fas fa-plus"></i> Tambah Fitur
                        </button>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save"></i> Update Layanan
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
<!-- Modal untuk Tim -->
<div class="modal fade" id="addTeamModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">
                    <i class="fas fa-user-plus"></i> Tambah Anggota Tim
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST" action="" enctype="multipart/form-data">
                <input type="hidden" name="action" value="add_team_member">
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label font-weight-bold">Nama Lengkap *</label>
                        <input type="text" class="form-control" name="name" id="add_team_name" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label font-weight-bold">Posisi/Jabatan *</label>
                        <input type="text" class="form-control" name="position" id="add_team_position" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label font-weight-bold">Urutan Tampil</label>
                        <input type="number" class="form-control" name="display_order" id="add_team_order" value="1" min="1">
                    </div>
                    <label class="form-label font-weight-bold">Foto Profil</label>
                    <input type="file" class="form-control mb-3" name="team_image" accept="image/*"
                        onchange="previewImage(this, 'add-team-preview')">
                    <div class="image-preview-custom" id="add-team-preview">
                        <i class="fas fa-user fa-3x text-muted"></i>
                        <p class="mt-2 mb-0 text-muted">Pilih foto untuk preview</p>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-plus"></i> Tambah Anggota
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
<div class="modal fade" id="editTeamModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">
                    <i class="fas fa-edit"></i> Edit Anggota Tim
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST" action="" enctype="multipart/form-data">
                <input type="hidden" name="action" value="update_team_member">
                <input type="hidden" name="member_id" id="edit_team_id">
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label font-weight-bold">Nama Lengkap *</label>
                        <input type="text" class="form-control" name="name" id="edit_team_name" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label font-weight-bold">Posisi/Jabatan *</label>
                        <input type="text" class="form-control" name="position" id="edit_team_position" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label font-weight-bold">Urutan Tampil</label>
                        <input type="number" class="form-control" name="display_order" id="edit_team_order" min="1">
                    </div>
                    <label class="form-label font-weight-bold">Foto Profil</label>
                    <input type="file" class="form-control mb-3" name="team_image" accept="image/*"
                        onchange="previewImage(this, 'edit-team-preview')">
                    <div class="image-preview-custom" id="edit-team-preview">
                        <i class="fas fa-user fa-3x text-muted"></i>
                        <p class="mt-2 mb-0 text-muted">Pilih foto untuk preview</p>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save"></i> Update Anggota
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
<!-- Modal untuk Testimonial -->
<div class="modal fade" id="addTestimonialModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">
                    <i class="fas fa-quote-left"></i> Tambah Testimonial
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST" action="" enctype="multipart/form-data">
                <input type="hidden" name="action" value="add_testimonial">
                <div class="modal-body">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label font-weight-bold">Nama Klien *</label>
                                <input type="text" class="form-control" name="client_name" id="add_testimonial_name" required>
                            </div>
                            <div class="mb-3">
                                <label class="form-label font-weight-bold">Posisi/Jabatan</label>
                                <input type="text" class="form-control" name="client_position" id="add_testimonial_position">
                            </div>
                            <div class="form-check form-switch mt-3">
                                <input class="form-check-input" type="checkbox" name="is_active" id="add_testimonial_active" checked>
                                <label class="form-check-label font-weight-bold" for="add_testimonial_active">
                                    Status Aktif
                                </label>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label font-weight-bold">Foto Klien</label>
                            <input type="file" class="form-control mb-3" name="testimonial_image" accept="image/*"
                                onchange="previewImage(this, 'add-testimonial-preview')">
                            <div class="image-preview-custom" id="add-testimonial-preview">
                                <i class="fas fa-user fa-3x text-muted"></i>
                                <p class="mt-2 mb-0 text-muted">Pilih foto untuk preview</p>
                            </div>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label font-weight-bold">Testimoni *</label>
                        <textarea class="form-control" name="quote" id="add_testimonial_quote" rows="4" required></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-plus"></i> Tambah Testimonial
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
<div class="modal fade" id="editTestimonialModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">
                    <i class="fas fa-edit"></i> Edit Testimonial
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST" action="" enctype="multipart/form-data">
                <input type="hidden" name="action" value="update_testimonial">
                <input type="hidden" name="testimonial_id" id="edit_testimonial_id">
                <div class="modal-body">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label font-weight-bold">Nama Klien *</label>
                                <input type="text" class="form-control" name="client_name" id="edit_testimonial_name" required>
                            </div>
                            <div class="mb-3">
                                <label class="form-label font-weight-bold">Posisi/Jabatan</label>
                                <input type="text" class="form-control" name="client_position" id="edit_testimonial_position">
                            </div>
                            <div class="form-check form-switch mt-3">
                                <input class="form-check-input" type="checkbox" name="is_active" id="edit_testimonial_active">
                                <label class="form-check-label font-weight-bold" for="edit_testimonial_active">
                                    Status Aktif
                                </label>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label font-weight-bold">Foto Klien</label>
                            <input type="file" class="form-control mb-3" name="testimonial_image" accept="image/*"
                                onchange="previewImage(this, 'edit-testimonial-preview')">
                            <div class="image-preview-custom" id="edit-testimonial-preview">
                                <i class="fas fa-user fa-3x text-muted"></i>
                                <p class="mt-2 mb-0 text-muted">Pilih foto untuk preview</p>
                            </div>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label font-weight-bold">Testimoni *</label>
                        <textarea class="form-control" name="quote" id="edit_testimonial_quote" rows="4" required></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save"></i> Update Testimonial
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
<script>
    document.addEventListener('DOMContentLoaded', function() {
        const tabButtons = document.querySelectorAll('.tab-button');
        const tabContents = document.querySelectorAll('.tab-content');
        tabButtons.forEach(button => {
            button.addEventListener('click', function() {
                const tabId = this.getAttribute('data-tab');
                tabButtons.forEach(btn => btn.classList.remove('active'));
                tabContents.forEach(content => content.classList.remove('active'));
                this.classList.add('active');
                document.getElementById(tabId).classList.add('active');
            });
        });
    });

    function addMisi() {
        const container = document.getElementById('misi-container');
        const div = document.createElement('div');
        div.className = 'dynamic-list-item';
        div.innerHTML = `
        <i class="fas fa-arrow-right text-primary"></i>
        <input type="text" class="form-control" name="misi[]" required>
        <button type="button" class="btn btn-outline-danger btn-sm" onclick="removeItem(this)">
            <i class="fas fa-trash"></i>
        </button>
    `;
        container.appendChild(div);
    }

    function addFeature() {
        const container = document.getElementById('features-container');
        const div = document.createElement('div');
        div.className = 'dynamic-list-item';
        div.innerHTML = `
        <i class="fas fa-check-circle text-success"></i>
        <input type="text" class="form-control" name="about_features[]" required>
        <button type="button" class="btn btn-outline-danger btn-sm" onclick="removeItem(this)">
            <i class="fas fa-trash"></i>
        </button>
    `;
        container.appendChild(div);
    }

    function addServiceFeature() {
        const container = document.getElementById('add-features-container');
        const div = document.createElement('div');
        div.className = 'dynamic-list-item';
        div.innerHTML = `
        <i class="fas fa-check-circle text-success"></i>
        <input type="text" class="form-control" name="features[]" placeholder="Masukkan fitur layanan" required>
        <button type="button" class="btn btn-outline-danger btn-sm" onclick="removeItem(this)">
            <i class="fas fa-trash"></i>
        </button>
    `;
        container.appendChild(div);
    }

    function addEditServiceFeature() {
        const container = document.getElementById('edit-features-container');
        const div = document.createElement('div');
        div.className = 'dynamic-list-item';
        div.innerHTML = `
        <i class="fas fa-check-circle text-success"></i>
        <input type="text" class="form-control" name="features[]" placeholder="Masukkan fitur layanan" required>
        <button type="button" class="btn btn-outline-danger btn-sm" onclick="removeItem(this)">
            <i class="fas fa-trash"></i>
        </button>
    `;
        container.appendChild(div);
    }

    function removeItem(button) {
        button.closest('.dynamic-list-item').remove();
    }

    function previewImage(input, previewId) {
        const preview = document.getElementById(previewId);
        if (input.files && input.files[0]) {
            const reader = new FileReader();
            reader.onload = function(e) {
                preview.innerHTML = `
                <img src="${e.target.result}" alt="Preview" class="img-fluid rounded mb-2" style="max-height: 200px;">
                <p class="mt-2 mb-0 text-muted">Preview gambar</p>
            `;
            };
            reader.readAsDataURL(input.files[0]);
        }
    }

    function editBanner(banner) {
        if (!banner || !banner.id) {
            console.error('Invalid banner data:', banner);
            return;
        }
        document.getElementById('edit_banner_id').value = banner.id;
        document.getElementById('edit_banner_title').value = banner.title || '';
        document.getElementById('edit_banner_subtitle').value = banner.subtitle || '';
        document.getElementById('edit_banner_btn_text').value = banner.button_text || '';
        document.getElementById('edit_banner_btn_link').value = banner.button_link || '';
        document.getElementById('edit_banner_active').checked = banner.is_active == 1;
        const preview = document.getElementById('edit-banner-preview');
        if (banner.image_path) {
            // PERBAIKAN: Menampilkan gambar dengan path yang benar
            preview.innerHTML = `
            <img src="../../uploads/banners/${basename(banner.image_path)}" alt="Current Image" class="img-fluid rounded mb-2" style="max-height: 200px;">
            <p class="mt-2 mb-0 text-muted">Gambar saat ini</p>
        `;
        } else if (banner.image_data) {
            preview.innerHTML = `
            <img src="data:image/jpeg;base64,${banner.image_data}" alt="Current Image" class="img-fluid rounded mb-2" style="max-height: 200px;">
            <p class="mt-2 mb-0 text-muted">Gambar saat ini</p>
        `;
        } else {
            preview.innerHTML = `
            <i class="fas fa-image fa-3x text-muted"></i>
            <p class="mt-2 mb-0 text-muted">Tidak ada gambar</p>
        `;
        }
        new bootstrap.Modal(document.getElementById('editBannerModal')).show();
    }

    function editService(service) {
        if (!service || !service.id) {
            console.error('Invalid service data:', service);
            return;
        }
        document.getElementById('edit_service_id').value = service.id;
        document.getElementById('edit_service_slug').value = service.service_slug || '';
        document.getElementById('edit_service_title').value = service.title || '';
        document.getElementById('edit_service_description').value = service.description || '';
        // Load features
        const featuresContainer = document.getElementById('edit-features-container');
        featuresContainer.innerHTML = '';
        if (service.features && Array.isArray(service.features)) {
            service.features.forEach(feature => {
                const div = document.createElement('div');
                div.className = 'dynamic-list-item';
                div.innerHTML = `
                <i class="fas fa-check-circle text-success"></i>
                <input type="text" class="form-control" name="features[]" value="${feature.replace(/"/g, '&quot;')}" required>
                <button type="button" class="btn btn-outline-danger btn-sm" onclick="removeItem(this)">
                    <i class="fas fa-trash"></i>
                </button>
            `;
                featuresContainer.appendChild(div);
            });
        } else {
            // Add at least one empty feature input
            const div = document.createElement('div');
            div.className = 'dynamic-list-item';
            div.innerHTML = `
            <i class="fas fa-check-circle text-success"></i>
            <input type="text" class="form-control" name="features[]" placeholder="Masukkan fitur layanan" required>
            <button type="button" class="btn btn-outline-danger btn-sm" onclick="removeItem(this)">
                <i class="fas fa-trash"></i>
            </button>
        `;
            featuresContainer.appendChild(div);
        }
        const preview = document.getElementById('edit-service-preview');
        if (service.image_path) {
            // PERBAIKAN: Menampilkan gambar dengan path yang benar
            preview.innerHTML = `
            <img src="../../uploads/services/${basename(service.image_path)}" alt="Current Image" class="img-fluid rounded mb-2" style="max-height: 200px;">
            <p class="mt-2 mb-0 text-muted">Gambar saat ini</p>
        `;
        } else if (service.image_data) {
            preview.innerHTML = `
            <img src="data:image/jpeg;base64,${service.image_data}" alt="Current Image" class="img-fluid rounded mb-2" style="max-height: 200px;">
            <p class="mt-2 mb-0 text-muted">Gambar saat ini</p>
        `;
        } else {
            preview.innerHTML = `
            <i class="fas fa-concierge-bell fa-3x text-muted"></i>
            <p class="mt-2 mb-0 text-muted">Tidak ada gambar</p>
        `;
        }
        new bootstrap.Modal(document.getElementById('editServiceModal')).show();
    }

    function deleteService(id) {
        if (!id) {
            console.error('Invalid service ID:', id);
            return;
        }
        if (confirm('Apakah Anda yakin ingin menghapus layanan ini?')) {
            const form = document.createElement('form');
            form.method = 'POST';
            form.innerHTML = `
            <input type="hidden" name="action" value="delete_service">
            <input type="hidden" name="id" value="${id}">
        `;
            document.body.appendChild(form);
            form.submit();
        }
    }

    function editTeamMember(member) {
        if (!member || !member.id) {
            console.error('Invalid member data:', member);
            return;
        }
        document.getElementById('edit_team_id').value = member.id;
        document.getElementById('edit_team_name').value = member.name || '';
        document.getElementById('edit_team_position').value = member.position || '';
        document.getElementById('edit_team_order').value = member.display_order || 1;
        const preview = document.getElementById('edit-team-preview');
        if (member.image_path) {
            // PERBAIKAN: Menampilkan gambar dengan path yang benar
            preview.innerHTML = `
            <img src="../../uploads/team/${basename(member.image_path)}" alt="Current Image" class="img-fluid rounded mb-2" style="max-height: 200px;">
            <p class="mt-2 mb-0 text-muted">Foto saat ini</p>
        `;
        } else if (member.image_data) {
            preview.innerHTML = `
            <img src="data:image/jpeg;base64,${member.image_data}" alt="Current Image" class="img-fluid rounded mb-2" style="max-height: 200px;">
            <p class="mt-2 mb-0 text-muted">Foto saat ini</p>
        `;
        } else {
            preview.innerHTML = `
            <i class="fas fa-user fa-3x text-muted"></i>
            <p class="mt-2 mb-0 text-muted">Tidak ada foto</p>
        `;
        }
        new bootstrap.Modal(document.getElementById('editTeamModal')).show();
    }

    function deleteTeamMember(id) {
        if (!id) {
            console.error('Invalid member ID:', id);
            return;
        }
        if (confirm('Apakah Anda yakin ingin menghapus anggota tim ini?')) {
            const form = document.createElement('form');
            form.method = 'POST';
            form.innerHTML = `
            <input type="hidden" name="action" value="delete_team_member">
            <input type="hidden" name="id" value="${id}">
        `;
            document.body.appendChild(form);
            form.submit();
        }
    }

    function editTestimonial(testimonial) {
        if (!testimonial || !testimonial.id) {
            console.error('Invalid testimonial data:', testimonial);
            return;
        }
        document.getElementById('edit_testimonial_id').value = testimonial.id;
        document.getElementById('edit_testimonial_name').value = testimonial.client_name || '';
        document.getElementById('edit_testimonial_position').value = testimonial.client_position || '';
        document.getElementById('edit_testimonial_quote').value = testimonial.quote || '';
        document.getElementById('edit_testimonial_active').checked = testimonial.is_active == 1;
        const preview = document.getElementById('edit-testimonial-preview');
        if (testimonial.image_path) {
            // PERBAIKAN: Menampilkan gambar dengan path yang benar
            preview.innerHTML = `
            <img src="../../uploads/testimonials/${basename(testimonial.image_path)}" alt="Current Image" class="img-fluid rounded mb-2" style="max-height: 200px;">
            <p class="mt-2 mb-0 text-muted">Foto saat ini</p>
        `;
        } else {
            preview.innerHTML = `
            <i class="fas fa-user fa-3x text-muted"></i>
            <p class="mt-2 mb-0 text-muted">Tidak ada foto</p>
        `;
        }
        new bootstrap.Modal(document.getElementById('editTestimonialModal')).show();
    }

    function deleteTestimonial(id) {
        if (!id) {
            console.error('Invalid testimonial ID:', id);
            return;
        }
        if (confirm('Apakah Anda yakin ingin menghapus testimonial ini?')) {
            const form = document.createElement('form');
            form.method = 'POST';
            form.innerHTML = `
            <input type="hidden" name="action" value="delete_testimonial">
            <input type="hidden" name="id" value="${id}">
        `;
            document.body.appendChild(form);
            form.submit();
        }
    }
    // Fungsi helper untuk mendapatkan nama file dari path
    function basename(path) {
        return path.split('/').pop();
    }
</script>
<?php include '../../includes/footer.php'; ?>