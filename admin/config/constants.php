<?php

/**
 * Application Constants
 * PT. Sarana Sentra Teknologi Utama - Admin Dashboard
 * 
 * Define all application constants, paths, and configuration values
 */

declare(strict_types=1);

// Application Information
define('APP_NAME', 'PT. Sarana Sentra Teknologi Utama - Admin Dashboard');
define('APP_VERSION', '1.0.0');
define('APP_AUTHOR', 'PT. Sarana Sentra Teknologi Utama');
define('APP_URL', 'http://localhost/admin'); // Sesuaikan dengan URL Anda

// Database Configuration
define('DB_HOST', 'localhost');
define('DB_NAME', 'pt_saranasentra_db');
define('DB_USER', 'root'); // Sesuaikan dengan kredensial Anda
define('DB_PASS', '');     // Sesuaikan dengan kredensial Anda
define('DB_CHARSET', 'utf8mb4');

// Path Constants
define('ROOT_PATH', dirname(__DIR__));
define('CONFIG_PATH', ROOT_PATH . '/config');
define('INCLUDES_PATH', ROOT_PATH . '/includes');
define('MODULES_PATH', ROOT_PATH . '/modules');
define('ASSETS_PATH', ROOT_PATH . '/assets');
define('UPLOADS_PATH', ROOT_PATH . '/uploads');
define('LOGS_PATH', ROOT_PATH . '/logs');

// URL Constants
define('BASE_URL', APP_URL);
define('ASSETS_URL', BASE_URL . '/assets');
define('UPLOADS_URL', BASE_URL . '/uploads');
define('API_URL', BASE_URL . '/api');

// Upload Directories
define('UPLOAD_ARTICLES', UPLOADS_PATH . '/articles');
define('UPLOAD_PRODUCTS', UPLOADS_PATH . '/products');
define('UPLOAD_TEAM', UPLOADS_PATH . '/team');
define('UPLOAD_BANNERS', UPLOADS_PATH . '/banners');
define('UPLOAD_TESTIMONIALS', UPLOADS_PATH . '/testimonials');
define('UPLOAD_TEMP', UPLOADS_PATH . '/temp');

// Upload URL paths
define('UPLOAD_ARTICLES_URL', UPLOADS_URL . '/articles');
define('UPLOAD_PRODUCTS_URL', UPLOADS_URL . '/products');
define('UPLOAD_TEAM_URL', UPLOADS_URL . '/team');
define('UPLOAD_BANNERS_URL', UPLOADS_URL . '/banners');
define('UPLOAD_TESTIMONIALS_URL', UPLOADS_URL . '/testimonials');

// File Upload Limits
define('MAX_FILE_SIZE', 2 * 1024 * 1024); // 2MB
define('MAX_IMAGE_WIDTH', 1920);
define('MAX_IMAGE_HEIGHT', 1080);
define('THUMBNAIL_WIDTH', 300);
define('THUMBNAIL_HEIGHT', 200);

// Allowed File Types
define('ALLOWED_IMAGE_TYPES', [
    'image/jpeg',
    'image/jpg',
    'image/png',
    'image/gif',
    'image/webp'
]);

define('ALLOWED_DOCUMENT_TYPES', [
    'application/pdf',
    'application/msword',
    'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
    'text/plain'
]);

// Pagination Settings
define('ITEMS_PER_PAGE', 10);
define('MAX_PAGINATION_LINKS', 5);

// Session Settings
define('SESSION_LIFETIME', 3600); // 1 hour
define('SESSION_NAME', 'sarana_admin_session');

// Security Settings
define('CSRF_TOKEN_LENGTH', 32);
define('PASSWORD_MIN_LENGTH', 8);
define('MAX_LOGIN_ATTEMPTS', 5);
define('LOGIN_LOCKOUT_TIME', 900); // 15 minutes

// Email Settings (untuk fitur reply pesan)
define('SMTP_HOST', 'smtp.gmail.com');
define('SMTP_PORT', 587);
define('SMTP_USERNAME', 'your-email@gmail.com'); // Ganti dengan email Anda
define('SMTP_PASSWORD', 'your-app-password');    // Ganti dengan app password
define('SMTP_FROM_EMAIL', 'info@saranasentra.com');
define('SMTP_FROM_NAME', 'PT. Sarana Sentra Teknologi Utama');

// Date and Time Settings
define('DEFAULT_TIMEZONE', 'Asia/Jakarta');
define('DATE_FORMAT', 'Y-m-d');
define('DATETIME_FORMAT', 'Y-m-d H:i:s');
define('DISPLAY_DATE_FORMAT', 'd/m/Y');
define('DISPLAY_DATETIME_FORMAT', 'd/m/Y H:i');

// Article Settings
define('ARTICLE_EXCERPT_LENGTH', 150);
define('ARTICLE_SLUG_LENGTH', 100);

// Product Settings
define('PRODUCT_STOCK_WARNING', 5); // Low stock warning threshold

// Status Constants
define('STATUS_ACTIVE', 1);
define('STATUS_INACTIVE', 0);

// Article Status
define('ARTICLE_STATUS_DRAFT', 'draft');
define('ARTICLE_STATUS_PUBLISHED', 'published');

// Comment Status
define('COMMENT_STATUS_PENDING', 'pending');
define('COMMENT_STATUS_APPROVED', 'approved');
define('COMMENT_STATUS_SPAM', 'spam');

// Message Status
define('MESSAGE_STATUS_UNREAD', 'unread');
define('MESSAGE_STATUS_READ', 'read');
define('MESSAGE_STATUS_ARCHIVED', 'archived');

// User Roles
define('ROLE_ADMIN', 'admin');
define('ROLE_EDITOR', 'editor');

// Error Messages
define('ERROR_UNAUTHORIZED', 'You are not authorized to perform this action.');
define('ERROR_NOT_FOUND', 'The requested resource was not found.');
define('ERROR_INVALID_REQUEST', 'Invalid request.');
define('ERROR_DATABASE', 'Database error occurred.');
define('ERROR_FILE_UPLOAD', 'File upload failed.');
define('ERROR_CSRF', 'Security token mismatch.');

// Success Messages
define('SUCCESS_SAVED', 'Data has been saved successfully.');
define('SUCCESS_UPDATED', 'Data has been updated successfully.');
define('SUCCESS_DELETED', 'Data has been deleted successfully.');
define('SUCCESS_UPLOADED', 'File has been uploaded successfully.');

// Log Levels
define('LOG_LEVEL_ERROR', 'ERROR');
define('LOG_LEVEL_WARNING', 'WARNING');
define('LOG_LEVEL_INFO', 'INFO');
define('LOG_LEVEL_DEBUG', 'DEBUG');

// Cache Settings
define('CACHE_ENABLED', false); // Set to true for production
define('CACHE_LIFETIME', 3600); // 1 hour

// API Settings
define('API_RATE_LIMIT', 100); // requests per hour
define('API_TOKEN_EXPIRY', 3600); // 1 hour

// Chart Colors (untuk dashboard)
define('CHART_COLORS', [
    'primary' => '#6f42c1',
    'secondary' => '#6c757d',
    'success' => '#198754',
    'info' => '#0dcaf0',
    'warning' => '#ffc107',
    'danger' => '#dc3545',
    'light' => '#f8f9fa',
    'dark' => '#212529'
]);

// Dashboard Statistics
define('DASHBOARD_RECENT_ITEMS', 5);
define('DASHBOARD_CHART_MONTHS', 12);

// Backup Settings
define('BACKUP_PATH', ROOT_PATH . '/backups');
define('BACKUP_RETENTION_DAYS', 30);

// Development Settings
define('DEBUG_MODE', true); // Set to false for production
define('ERROR_REPORTING_LEVEL', E_ALL);
define('DISPLAY_ERRORS', true); // Set to false for production

// Set timezone
date_default_timezone_set(DEFAULT_TIMEZONE);

// Set error reporting based on debug mode
if (DEBUG_MODE) {
    error_reporting(ERROR_REPORTING_LEVEL);
    ini_set('display_errors', DISPLAY_ERRORS ? '1' : '0');
} else {
    error_reporting(0);
    ini_set('display_errors', '0');
}

// Create necessary directories if they don't exist
$directories = [
    UPLOADS_PATH,
    UPLOAD_ARTICLES,
    UPLOAD_PRODUCTS,
    UPLOAD_TEAM,
    UPLOAD_BANNERS,
    UPLOAD_TESTIMONIALS,
    UPLOAD_TEMP,
    LOGS_PATH
];

foreach ($directories as $dir) {
    if (!is_dir($dir)) {
        mkdir($dir, 0755, true);
    }
}

// Create .htaccess for uploads directory security
$htaccess_content = "Options -Indexes\n";
$htaccess_content .= "# Prevent direct access to PHP files\n";
$htaccess_content .= "<Files *.php>\n";
$htaccess_content .= "    Require all denied\n";
$htaccess_content .= "</Files>\n";

$htaccess_file = UPLOADS_PATH . '/.htaccess';
if (!file_exists($htaccess_file)) {
    file_put_contents($htaccess_file, $htaccess_content);
}

// Create logs directory .htaccess
$logs_htaccess = LOGS_PATH . '/.htaccess';
if (!file_exists($logs_htaccess)) {
    file_put_contents($logs_htaccess, "Require all denied\n");
}
