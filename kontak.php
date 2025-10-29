<?php
require_once 'config/db.php';
// Get database connection
$db = getDB();
// Handle form submission
$success_message = '';
$errors = [];
if ($_POST && isset($_POST['submit_contact'])) {
    // Sanitize and validate input
    $name = trim($_POST['name'] ?? '');
    $company = trim($_POST['company'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $subject = trim($_POST['subject'] ?? '');
    $message = trim($_POST['message'] ?? '');
    // Validation
    if (empty($name)) {
        $errors[] = 'Nama lengkap harus diisi.';
    }
    if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'Email yang valid harus diisi.';
    }
    if (empty($subject)) {
        $errors[] = 'Subjek harus diisi.';
    }
    if (empty($message)) {
        $errors[] = 'Pesan harus diisi.';
    }
    // If no errors, save to database
    if (empty($errors)) {
        try {
            // Create final message with company info if provided
            $final_message = $message;
            if (!empty($company)) {
                $final_message = "Nama Perusahaan: " . $company . "\n" .
                    (!empty($phone) ? "Nomor Telepon: " . $phone . "\n" : "") .
                    "\nPesan:\n" . $message;
            } else if (!empty($phone)) {
                $final_message = "Nomor Telepon: " . $phone . "\n\nPesan:\n" . $message;
            }
            $stmt = $db->prepare("
                INSERT INTO contact_messages (name, email, subject, message, submitted_at, status) 
                VALUES (?, ?, ?, ?, NOW(), 'unread')
            ");
            $stmt->execute([$name, $email, $subject, $final_message]);
            $success_message = 'Terima kasih! Pesan Anda telah berhasil dikirim. Tim kami akan menghubungi Anda dalam 1x24 jam.';
            // Clear form data after successful submission
            $_POST = [];
        } catch (PDOException $e) {
            $errors[] = 'Terjadi kesalahan saat mengirim pesan. Silakan coba lagi.';
        }
    }
}
// Get site settings for contact info
try {
    $stmt = $db->prepare("SELECT setting_key, setting_value FROM site_settings WHERE setting_key IN ('contact_address', 'contact_phone', 'contact_email', 'contact_hours', 'Maps_url', 'contact_whatsapp', 'whatsapp_message_template')");
    $stmt->execute();
    $settings = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);
} catch (PDOException $e) {
    $settings = [];
}

try {
    $stmt = $db->prepare("SELECT setting_key FROM site_settings WHERE setting_key IN ('contact_whatsapp', 'whatsapp_message_template')");
    $stmt->execute();
    $existing_settings = $stmt->fetchAll(PDO::FETCH_COLUMN);

    if (!in_array('contact_whatsapp', $existing_settings)) {
        $stmt = $db->prepare("INSERT INTO site_settings (setting_key, setting_value) VALUES ('contact_whatsapp', '+62811234567') ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value)");
        $stmt->execute();
    }

    if (!in_array('whatsapp_message_template', $existing_settings)) {
        $stmt = $db->prepare("INSERT INTO site_settings (setting_key, setting_value) VALUES ('whatsapp_message_template', 'Halo, saya ingin bertanya tentang layanan PT Sarana Sentra Teknologi Utama') ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value)");
        $stmt->execute();
    }
} catch (PDOException $e) {
}

$whatsapp_number = $settings['contact_whatsapp'] ?? '+6221123456789';
$whatsapp_template = $settings['whatsapp_message_template'] ?? 'Halo, saya ingin bertanya tentang layanan PT Sarana Sentra Teknologi Utama';

$product_name = isset($_GET['product']) ? $_GET['product'] : '';
$page_title = 'Kontak Kami';
include 'includes/header.php';
?>
<style>
    /* Page Hero */
    .page-hero {
        background: url("assets/img/dddepth-240.png") center/cover no-repeat;
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

    /* Contact Section */
    .contact {
        padding: 6rem 0;
        background: white;
    }

    .info-wrap {
        background: white;
        border-radius: 20px;
        padding: 2.5rem;
        box-shadow: var(--shadow-soft);
        height: fit-content;
        border: 1px solid var(--border-color);
        transition: all 0.3s ease;
    }

    .info-wrap:hover {
        transform: translateY(-5px);
        box-shadow: var(--shadow-medium);
    }

    .info-item {
        margin-bottom: 2.5rem;
        display: flex;
        align-items: flex-start;
        transition: all 0.3s ease;
    }

    .info-item:hover {
        transform: translateX(5px);
    }

    .info-item:last-child {
        margin-bottom: 0;
    }

    .info-item i {
        font-size: 1.25rem;
        color: white;
        background: var(--gradient-primary);
        width: 50px;
        height: 50px;
        display: flex;
        justify-content: center;
        align-items: center;
        border-radius: 50px;
        margin-right: 1rem;
        flex-shrink: 0;
        transition: all 0.3s ease;
    }

    .info-item:hover i {
        transform: scale(1.1);
    }

    .info-item h3 {
        color: var(--text-dark);
        font-size: 1.25rem;
        font-weight: 600;
        margin: 0 0 0.5rem 0;
        position: relative;
    }

    .info-item h3::after {
        content: "";
        position: absolute;
        bottom: -5px;
        left: 0;
        width: 30px;
        height: 2px;
        background: var(--gradient-soft);
        border-radius: 1px;
    }

    .info-item p {
        padding: 0;
        margin: 0;
        color: var(--text-light);
        line-height: 1.6;
        font-size: 1rem;
    }

    .contact-form {
        background: white;
        border-radius: 20px;
        padding: 2.5rem;
        box-shadow: var(--shadow-soft);
        border: 1px solid var(--border-color);
        transition: all 0.3s ease;
    }

    .contact-form:hover {
        transform: translateY(-5px);
        box-shadow: var(--shadow-medium);
    }

    .contact-form h3 {
        color: var(--text-dark);
        margin-bottom: 1.5rem;
        font-size: 1.5rem;
        font-weight: 600;
        position: relative;
    }

    .contact-form h3::after {
        content: "";
        position: absolute;
        bottom: -10px;
        left: 0;
        width: 50px;
        height: 3px;
        background: var(--gradient-soft);
        border-radius: 2px;
    }

    .contact-form .form-control {
        padding: 0.75rem 1.25rem;
        border: 1px solid var(--border-color);
        border-radius: 10px;
        margin-bottom: 1.5rem;
        font-size: 1rem;
        transition: all 0.3s ease;
    }

    .contact-form .form-control:focus {
        border-color: var(--primary-color);
        box-shadow: 0 0 0 0.2rem rgba(74, 124, 126, 0.25);
        outline: none;
    }

    .contact-form .btn {
        background: var(--gradient-primary);
        border: none;
        color: white;
        padding: 0.75rem 2rem;
        border-radius: 50px;
        font-weight: 600;
        transition: all 0.3s ease;
        box-shadow: var(--shadow-soft);
        font-size: 1rem;
        position: relative;
        overflow: hidden;
    }

    .contact-form .btn::before {
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

    .contact-form .btn:hover::before {
        left: 100%;
    }

    .contact-form .btn:hover {
        background: var(--gradient-soft);
        transform: translateY(-3px);
        box-shadow: var(--shadow-medium);
        color: white;
    }

    /* Map Section */
    .map {
        padding: 6rem 0;
        background: var(--secondary-color);
    }

    .section-title {
        text-align: center;
        margin-bottom: 3rem;
    }

    .section-title h2 {
        font-size: 2rem;
        font-weight: 700;
        color: var(--text-dark);
        margin-bottom: 1rem;
        position: relative;
        display: inline-block;
    }

    .section-title h2::after {
        content: "";
        position: absolute;
        bottom: -8px;
        left: 50%;
        transform: translateX(-50%);
        width: 80px;
        height: 2px;
        background: var(--gradient-soft);
        border-radius: 1px;
    }

    .section-title p {
        color: var(--text-light);
        max-width: 600px;
        margin: 0 auto;
    }

    .map-container {
        border-radius: 20px;
        overflow: hidden;
        box-shadow: var(--shadow-medium);
        transition: all 0.3s ease;
    }

    .map-container:hover {
        transform: translateY(-5px);
        box-shadow: var(--shadow-medium);
    }

    .map-placeholder {
        background: var(--secondary-color);
        height: 400px;
        display: flex;
        align-items: center;
        justify-content: center;
    }

    /* Alternative Contact Methods */
    .alternative-contact {
        padding: 6rem 0;
        background: white;
    }

    .contact-method {
        background: white;
        border-radius: 20px;
        padding: 2.5rem 2rem;
        box-shadow: var(--shadow-soft);
        border: 1px solid var(--border-color);
        height: 100%;
        transition: all 0.3s ease;
        position: relative;
        overflow: hidden;
    }

    .contact-method::before {
        content: '';
        position: absolute;
        top: 0;
        left: 0;
        right: 0;
        height: 3px;
        background: var(--gradient-primary);
        transform: scaleX(0);
        transition: transform 0.3s ease;
    }

    .contact-method:hover::before {
        transform: scaleX(1);
    }

    .contact-method:hover {
        transform: translateY(-10px);
        box-shadow: var(--shadow-medium);
        border-color: var(--primary-light);
    }

    .contact-method .icon {
        width: 80px;
        height: 80px;
        background: var(--gradient-primary);
        border-radius: 50%;
        display: flex;
        justify-content: center;
        align-items: center;
        margin: 0 auto 1.5rem;
        transition: all 0.3s ease;
    }

    .contact-method .icon i {
        font-size: 2rem;
        color: white;
    }

    .contact-method:hover .icon {
        transform: scale(1.1) rotate(5deg);
    }

    .contact-method h4 {
        color: var(--text-dark);
        font-size: 1.25rem;
        font-weight: 600;
        margin-bottom: 1rem;
    }

    .contact-method p {
        color: var(--text-light);
        margin-bottom: 1.5rem;
    }

    .contact-method .btn {
        border-radius: 50px;
        padding: 0.75rem 1.5rem;
        font-weight: 600;
        transition: all 0.3s ease;
    }

    .btn-success {
        background: #28a745;
        border-color: #28a745;
        color: white;
    }

    .btn-success:hover {
        background: #218838;
        border-color: #1e7e34;
        transform: translateY(-2px);
    }

    .btn-info {
        background: #17a2b8;
        border-color: #17a2b8;
        color: white;
    }

    .btn-info:hover {
        background: #138496;
        border-color: #117a8b;
        transform: translateY(-2px);
    }

    /* FAQ Section */
    .faq {
        padding: 6rem 0;
        background: var(--secondary-color);
    }

    .accordion {
        border-radius: 15px;
        overflow: hidden;
        box-shadow: var(--shadow-soft);
    }

    .accordion-item {
        border: none;
        border-bottom: 1px solid var(--border-color);
    }

    .accordion-item:last-child {
        border-bottom: none;
    }

    .accordion-button {
        background: white;
        color: var(--text-dark);
        font-weight: 600;
        padding: 1.25rem;
        border: none;
        transition: all 0.3s ease;
    }

    .accordion-button:not(.collapsed) {
        background: var(--secondary-color);
        color: var(--primary-color);
    }

    .accordion-button:focus {
        box-shadow: none;
    }

    .accordion-button::after {
        background-image: url("data:image/svg+xml,%3csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 16 16' fill='%234a7c7e'%3e%3cpath fill-rule='evenodd' d='M1.646 4.646a.5.5 0 0 1 .708 0L8 10.293l5.646-5.647a.5.5 0 0 1 .708.708l-6 6a.5.5 0 0 1-.708 0l-6-6a.5.5 0 0 1 0-.708z'/%3e%3c/svg%3e");
        transition: transform 0.3s ease;
    }

    .accordion-button:not(.collapsed)::after {
        background-image: url("data:image/svg+xml,%3csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 16 16' fill='%234a7c7e'%3e%3cpath fill-rule='evenodd' d='M7.646 4.646a.5.5 0 0 1 .708 0l6 6a.5.5 0 0 1-.708.708L8 5.707l-5.646 5.647a.5.5 0 0 1-.708-.708l6-6z'/%3e%3c/svg%3e");
        transform: rotate(180deg);
    }

    .accordion-body {
        padding: 1.25rem;
        color: var(--text-light);
        line-height: 1.6;
    }

    /* Alert Styles */
    .alert {
        border-radius: 15px;
        padding: 1rem 1.5rem;
        margin-bottom: 2rem;
        border: none;
        transition: all 0.3s ease;
        position: relative;
        overflow: hidden;
    }

    .alert-success {
        background: rgba(40, 167, 69, 0.1);
        color: #28a745;
        border-left: 4px solid #28a745;
    }

    .alert-danger {
        background: rgba(220, 53, 69, 0.1);
        color: #dc3545;
        border-left: 4px solid #dc3545;
    }

    .alert ul {
        padding-left: 1.5rem;
        margin-bottom: 0;
    }

    /* Alert fade animation */
    .alert.fade-out {
        opacity: 0;
        transform: translateY(-20px);
    }

    /* Responsive Design */
    @media (max-width: 991px) {
        .page-hero h1 {
            font-size: 2.5rem;
        }

        .page-hero p {
            font-size: 1.1rem;
        }

        .info-wrap,
        .contact-form {
            margin-bottom: 2rem;
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

        .info-wrap,
        .contact-form {
            padding: 1.5rem;
        }

        .contact {
            padding: 4rem 0;
        }

        .map,
        .alternative-contact,
        .faq {
            padding: 4rem 0;
        }

        .section-title h2 {
            font-size: 1.75rem;
        }
    }

    @media (max-width: 576px) {
        .page-hero h1 {
            font-size: 1.8rem;
        }

        .page-hero p {
            font-size: 0.9rem;
        }

        .info-item {
            margin-bottom: 1.5rem;
        }

        .info-item i {
            width: 40px;
            height: 40px;
            font-size: 1rem;
        }

        .info-item h3 {
            font-size: 1.1rem;
        }

        .contact-form {
            padding: 1.5rem;
        }

        .contact-method {
            padding: 1.5rem;
        }

        .contact-method .icon {
            width: 60px;
            height: 60px;
        }

        .contact-method .icon i {
            font-size: 1.5rem;
        }
    }

    /* Additional CSS for form validation */
    .form-control.is-invalid {
        border-color: #dc3545;
        box-shadow: 0 0 0 0.2rem rgba(220, 53, 69, 0.25);
    }

    .form-control.is-invalid:focus {
        border-color: #dc3545;
        box-shadow: 0 0 0 0.2rem rgba(220, 53, 69, 0.25);
    }

    /* Enhanced alert animations */
    .alert.auto-hide {
        animation: slideInDown 0.5s ease-out;
    }

    .alert.fade-out {
        animation: slideOutUp 0.3s ease-in forwards;
    }

    @keyframes slideInDown {
        from {
            opacity: 0;
            transform: translateY(-20px);
        }

        to {
            opacity: 1;
            transform: translateY(0);
        }
    }

    @keyframes slideOutUp {
        from {
            opacity: 1;
            transform: translateY(0);
        }

        to {
            opacity: 0;
            transform: translateY(-20px);
        }
    }

    /* Contact method enhanced hover effects */
    .contact-method {
        transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
    }

    .contact-method:hover {
        transform: translateY(-10px);
        box-shadow: 0 20px 40px rgba(0, 0, 0, 0.1);
    }

    .contact-method .btn:hover {
        transform: translateY(-2px);
        box-shadow: 0 8px 15px rgba(0, 0, 0, 0.2);
    }

    /* Loading state for form submission */
    .contact-form .btn:disabled {
        opacity: 0.6;
        cursor: not-allowed;
    }

    .contact-form .btn.loading::after {
        content: '';
        width: 16px;
        height: 16px;
        margin-left: 8px;
        border: 2px solid transparent;
        border-top: 2px solid currentColor;
        border-radius: 50%;
        display: inline-block;
        animation: spin 1s linear infinite;
    }

    @keyframes spin {
        0% {
            transform: rotate(0deg);
        }

        100% {
            transform: rotate(360deg);
        }
    }

    /* Responsive enhancements */
    @media (max-width: 576px) {
        .contact-method .btn {
            font-size: 0.9rem;
            padding: 0.6rem 1.2rem;
        }

        .accordion-button {
            font-size: 0.95rem;
            padding: 1rem;
        }

        .accordion-body {
            font-size: 0.9rem;
        }
    }
</style>

<section class="page-hero">
    <div class="container">
        <div class="page-hero-content" data-aos="fade-up">
            <h1>Kontak Kami</h1>
            <p>Tim ahli kami siap membantu kebutuhan teknologi bisnis Anda</p>
            <nav class="breadcrumb-nav">
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="index.php">Beranda</a></li>
                    <li class="breadcrumb-item active">Kontak</li>
                </ol>
            </nav>
        </div>
    </div>
</section>

<section id="contact" class="contact section">
    <div class="container">
        <div class="row gy-4">
            <div class="col-lg-5" data-aos="fade-right">
                <div class="info-wrap">
                    <div class="info-item">
                        <i class="bi bi-geo-alt"></i>
                        <div>
                            <h3>Alamat</h3>
                            <p><?php echo htmlspecialchars($settings['contact_address'] ?? 'Jl. Jenderal Sudirman Kav. 52-53, Jakarta Selatan'); ?></p>
                        </div>
                    </div>
                    <div class="info-item">
                        <i class="bi bi-telephone"></i>
                        <div>
                            <h3>Telepon</h3>
                            <p><?php echo htmlspecialchars($settings['contact_phone'] ?? '+62 21 1234 5678'); ?></p>
                        </div>
                    </div>
                    <div class="info-item">
                        <i class="bi bi-envelope"></i>
                        <div>
                            <h3>Email</h3>
                            <p><?php echo htmlspecialchars($settings['contact_email'] ?? 'info@saranasentra.com'); ?></p>
                        </div>
                    </div>
                    <div class="info-item">
                        <i class="bi bi-clock"></i>
                        <div>
                            <h3>Jam Operasional</h3>
                            <p><?php echo htmlspecialchars($settings['contact_hours'] ?? 'Senin - Jumat: 08:00 - 17:00 WIB'); ?></p>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-lg-7" data-aos="fade-left">
                <div class="contact-form">
                    <h3>Hubungi Kami</h3>
                    <p class="mb-4">Silakan isi formulir di bawah ini untuk konsultasi gratis atau permintaan penawaran. Tim ahli kami siap membantu kebutuhan IT bisnis Anda.</p>
                    <?php if ($success_message): ?>
                        <div class="alert alert-success auto-hide">
                            <i class="bi bi-check-circle-fill me-2"></i>
                            <?php echo $success_message; ?>
                        </div>
                    <?php endif; ?>
                    <?php if (!empty($errors)): ?>
                        <div class="alert alert-danger auto-hide">
                            <i class="bi bi-exclamation-triangle-fill me-2"></i>
                            <strong>Terjadi kesalahan:</strong>
                            <ul class="mb-0 mt-2">
                                <?php foreach ($errors as $error): ?>
                                    <li><?php echo $error; ?></li>
                                <?php endforeach; ?>
                            </ul>
                        </div>
                    <?php endif; ?>
                    <form method="POST" action="" class="contact-form-inner">
                        <div class="row gy-4">
                            <div class="col-md-6">
                                <input type="text" name="name" class="form-control" placeholder="Nama Lengkap *"
                                    value="<?php echo htmlspecialchars($_POST['name'] ?? ''); ?>" required>
                            </div>
                            <div class="col-md-6">
                                <input type="text" name="company" class="form-control" placeholder="Nama Perusahaan (Opsional)"
                                    value="<?php echo htmlspecialchars($_POST['company'] ?? ''); ?>">
                            </div>
                            <div class="col-md-6">
                                <input type="email" name="email" class="form-control" placeholder="Email *"
                                    value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>" required>
                            </div>
                            <div class="col-md-6">
                                <input type="tel" name="phone" class="form-control" placeholder="Nomor Telepon"
                                    value="<?php echo htmlspecialchars($_POST['phone'] ?? ''); ?>">
                            </div>
                            <div class="col-md-12">
                                <input type="text" name="subject" class="form-control" placeholder="Subjek *"
                                    value="<?php echo htmlspecialchars($_POST['subject'] ?? ($product_name ? 'Permintaan Penawaran untuk Produk: ' . $product_name : '')); ?>" required>
                            </div>
                            <div class="col-md-12">
                                <textarea name="message" class="form-control" rows="6"
                                    placeholder="Jelaskan kebutuhan atau pertanyaan Anda secara detail *" required><?php echo htmlspecialchars($_POST['message'] ?? ''); ?></textarea>
                            </div>
                            <div class="col-md-12 text-center">
                                <button type="submit" name="submit_contact" class="btn">
                                    <i class="bi bi-send-fill me-2"></i>
                                    Kirim Pesan
                                </button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</section>

<section id="map" class="map section">
    <div class="container">
        <div class="section-title" data-aos="fade-up">
            <h2>Lokasi Kami</h2>
            <p>Kunjungi kantor kami untuk konsultasi langsung</p>
        </div>
        <div class="map-container" data-aos="fade-up" data-aos-delay="200">
            <?php if (!empty($settings['Maps_url'])): ?>
                <iframe src="<?php echo htmlspecialchars($settings['Maps_url']); ?>"
                    width="100%" height="400" style="border:0;" allowfullscreen=""
                    loading="lazy" referrerpolicy="no-referrer-when-downgrade">
                </iframe>
            <?php else: ?>
                <div class="map-placeholder">
                    <div class="text-center">
                        <i class="bi bi-geo-alt fs-1 text-muted"></i>
                        <p class="text-muted mt-2">Peta akan ditampilkan di sini</p>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </div>
</section>

<section id="alternative-contact" class="alternative-contact section">
    <div class="container">
        <div class="section-title" data-aos="fade-up">
            <h2>Cara Lain Menghubungi Kami</h2>
            <p>Pilih metode komunikasi yang paling nyaman untuk Anda</p>
        </div>
        <div class="row gy-4">
            <div class="col-lg-4 col-md-6" data-aos="fade-up" data-aos-delay="100">
                <div class="contact-method text-center">
                    <div class="icon">
                        <i class="bi bi-whatsapp"></i>
                    </div>
                    <h4>WhatsApp</h4>
                    <p>Hubungi kami langsung melalui WhatsApp untuk respon yang lebih cepat</p>
                    <?php
                    // Format WhatsApp number (remove non-numeric characters except +)
                    $formatted_whatsapp = preg_replace('/[^\d+]/', '', $whatsapp_number);
                    $whatsapp_url = "https://wa.me/" . $formatted_whatsapp . "?text=" . urlencode($whatsapp_template);
                    ?>
                    <a href="<?php echo $whatsapp_url; ?>" target="_blank" class="btn btn-success">
                        <i class="bi bi-whatsapp me-1"></i> Chat WhatsApp
                    </a>
                </div>
            </div>
            <div class="col-lg-4 col-md-6" data-aos="fade-up" data-aos-delay="200">
                <div class="contact-method text-center">
                    <div class="icon">
                        <i class="bi bi-telephone-fill"></i>
                    </div>
                    <h4>Telepon Langsung</h4>
                    <p>Untuk konsultasi mendalam dan diskusi teknis</p>
                    <a href="tel:<?php echo str_replace([' ', '-'], '', $settings['contact_phone'] ?? '+6221123456789'); ?>"
                        class="btn btn-primary">
                        <i class="bi bi-telephone me-1"></i> Hubungi Sekarang
                    </a>
                </div>
            </div>
            <div class="col-lg-4 col-md-6" data-aos="fade-up" data-aos-delay="300">
                <div class="contact-method text-center">
                    <div class="icon">
                        <i class="bi bi-envelope-fill"></i>
                    </div>
                    <h4>Email Langsung</h4>
                    <p>Kirim email untuk pertanyaan detail atau dokumen penawaran</p>
                    <?php
                    $contact_email = $settings['contact_email'] ?? 'info@saranasentra.com';
                    $email_subject = 'Pertanyaan tentang Layanan PT Sarana Sentra Teknologi Utama';
                    $email_body = 'Halo,%0D%0A%0D%0ASaya ingin mengetahui lebih lanjut tentang layanan yang Anda tawarkan.%0D%0A%0D%0ATerima kasih.';
                    $gmail_url = "https://mail.google.com/mail/?view=cm&fs=1&to=" . $contact_email . "&su=" . urlencode($email_subject) . "&body=" . $email_body;
                    ?>
                    <a href="<?php echo $gmail_url; ?>" target="_blank" class="btn btn-info">
                        <i class="bi bi-envelope me-1"></i> Kirim Email
                    </a>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- FAQ Section -->
<section id="faq" class="faq section">
    <div class="container">
        <div class="section-title" data-aos="fade-up">
            <h2>Pertanyaan Yang Sering Diajukan</h2>
            <p>Temukan jawaban dari pertanyaan yang paling sering ditanyakan tentang layanan kami</p>
        </div>
        <div class="accordion" id="faqAccordion" data-aos="fade-up" data-aos-delay="200">
            <div class="accordion-item">
                <h2 class="accordion-header">
                    <button class="accordion-button" type="button" data-bs-toggle="collapse" data-bs-target="#faq1" aria-expanded="true" aria-controls="faq1">
                        Berapa lama waktu yang dibutuhkan untuk instalasi CCTV?
                    </button>
                </h2>
                <div id="faq1" class="accordion-collapse collapse show" data-bs-parent="#faqAccordion">
                    <div class="accordion-body">
                        Waktu instalasi CCTV bergantung pada jumlah kamera dan kompleksitas lokasi. Untuk kantor kecil (4-8 kamera), biasanya memerlukan waktu 1-2 hari. Untuk proyek besar, kami akan memberikan timeline yang jelas setelah survey lokasi.
                    </div>
                </div>
            </div>
            <div class="accordion-item">
                <h2 class="accordion-header">
                    <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#faq2" aria-expanded="false" aria-controls="faq2">
                        Apakah layanan maintenance tersedia 24/7?
                    </button>
                </h2>
                <div id="faq2" class="accordion-collapse collapse" data-bs-parent="#faqAccordion">
                    <div class="accordion-body">
                        Untuk pelanggan kontrak, kami menyediakan layanan emergency 24/7. Pelanggan retail dapat menghubungi kami pada jam kerja (Senin-Jumat 08:00-17:00 WIB) atau melalui WhatsApp untuk respon yang lebih cepat.
                    </div>
                </div>
            </div>
            <div class="accordion-item">
                <h2 class="accordion-header">
                    <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#faq3" aria-expanded="false" aria-controls="faq3">
                        Bagaimana sistem pembayaran untuk layanan kontrak?
                    </button>
                </h2>
                <div id="faq3" class="accordion-collapse collapse" data-bs-parent="#faqAccordion">
                    <div class="accordion-body">
                        Kami menawarkan fleksibilitas pembayaran bulanan, triwulan, atau tahunan. Pembayaran dapat dilakukan melalui transfer bank, atau sistem pembayaran digital lainnya. Untuk kontrak tahunan, tersedia diskon khusus.
                    </div>
                </div>
            </div>
            <div class="accordion-item">
                <h2 class="accordion-header">
                    <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#faq4" aria-expanded="false" aria-controls="faq4">
                        Apakah ada garansi untuk produk dan instalasi?
                    </button>
                </h2>
                <div id="faq4" class="accordion-collapse collapse" data-bs-parent="#faqAccordion">
                    <div class="accordion-body">
                        Ya, semua produk yang kami jual memiliki garansi resmi dari distributor/brand. Untuk instalasi, kami memberikan garansi kerja selama 1 tahun. Garansi mencakup kerusakan akibat kesalahan instalasi, bukan kerusakan karena bencana alam atau human error.
                    </div>
                </div>
            </div>
            <div class="accordion-item">
                <h2 class="accordion-header">
                    <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#faq5" aria-expanded="false" aria-controls="faq5">
                        Bisakah melakukan konsultasi gratis sebelum membeli?
                    </button>
                </h2>
                <div id="faq5" class="accordion-collapse collapse" data-bs-parent="#faqAccordion">
                    <div class="accordion-body">
                        Tentu saja! Kami menyediakan konsultasi gratis untuk membantu Anda memilih solusi yang tepat sesuai kebutuhan dan budget. Tim ahli kami siap melakukan survey lokasi dan memberikan rekomendasi terbaik tanpa biaya tambahan.
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>
<?php

?>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        // Auto-hide alerts after 5 seconds
        const alerts = document.querySelectorAll('.alert.auto-hide');
        alerts.forEach(function(alert) {
            setTimeout(function() {
                alert.classList.add('fade-out');
                setTimeout(function() {
                    alert.remove();
                }, 300);
            }, 5000);
        });

        // Form validation enhancement
        const contactForm = document.querySelector('.contact-form-inner');
        if (contactForm) {
            contactForm.addEventListener('submit', function(e) {
                const requiredFields = ['name', 'email', 'subject', 'message'];
                let isValid = true;

                requiredFields.forEach(function(fieldName) {
                    const field = contactForm.querySelector(`[name="${fieldName}"]`);
                    if (field && !field.value.trim()) {
                        field.classList.add('is-invalid');
                        isValid = false;
                    } else if (field) {
                        field.classList.remove('is-invalid');
                    }
                });

                // Email validation
                const emailField = contactForm.querySelector('[name="email"]');
                if (emailField && emailField.value.trim()) {
                    const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
                    if (!emailRegex.test(emailField.value.trim())) {
                        emailField.classList.add('is-invalid');
                        isValid = false;
                    }
                }

                if (!isValid) {
                    e.preventDefault();
                }
            });

            // Remove validation classes on input
            const formFields = contactForm.querySelectorAll('input, textarea');
            formFields.forEach(function(field) {
                field.addEventListener('input', function() {
                    this.classList.remove('is-invalid');
                });
            });
        }

        // Smooth scrolling for form errors
        if (window.location.hash === '#contact' || document.querySelector('.alert-danger')) {
            setTimeout(function() {
                document.querySelector('#contact').scrollIntoView({
                    behavior: 'smooth',
                    block: 'start'
                });
            }, 100);
        }

        // WhatsApp number formatting
        function formatWhatsAppNumber(number) {
            // Remove all non-numeric characters except +
            let formatted = number.replace(/[^\d+]/g, '');

            // If starts with +62, keep as is
            if (formatted.startsWith('+62')) {
                return formatted;
            }

            // If starts with 62, add +
            if (formatted.startsWith('62')) {
                return '+' + formatted;
            }

            // If starts with 08, replace with +628
            if (formatted.startsWith('08')) {
                return '+62' + formatted.substring(1);
            }

            // If starts with 8, add +62
            if (formatted.startsWith('8')) {
                return '+62' + formatted;
            }

            return formatted;
        }

        // Update WhatsApp links dynamically if needed
        const whatsappLinks = document.querySelectorAll('a[href*="wa.me"]');
        whatsappLinks.forEach(function(link) {
            link.addEventListener('click', function(e) {
                // Optional: Add analytics tracking here
                console.log('WhatsApp button clicked');
            });
        });

        // Contact method hover effects
        const contactMethods = document.querySelectorAll('.contact-method');
        contactMethods.forEach(function(method) {
            method.addEventListener('mouseenter', function() {
                this.style.transform = 'translateY(-10px) scale(1.02)';
            });

            method.addEventListener('mouseleave', function() {
                this.style.transform = 'translateY(-10px) scale(1)';
            });
        });
    });

    // Function to copy contact info to clipboard (optional enhancement)
    function copyToClipboard(text, element) {
        navigator.clipboard.writeText(text).then(function() {
            const originalText = element.textContent;
            element.textContent = 'Tersalin!';
            element.style.color = '#28a745';

            setTimeout(function() {
                element.textContent = originalText;
                element.style.color = '';
            }, 2000);
        }).catch(function() {
            console.log('Copy to clipboard failed');
        });
    }
</script>

<?php include 'includes/footer.php'; ?>