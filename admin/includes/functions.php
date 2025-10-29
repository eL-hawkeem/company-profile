<?php

/**
 * Dashboard Helper Functions
 * PT. Sarana Sentra Teknologi Utama - Admin Dashboard
 */

declare(strict_types=1);

/**
 * Sanitize input
 */
function sanitizeInput(string $input): string
{
    return htmlspecialchars(trim($input), ENT_QUOTES, 'UTF-8');
}

/**
 * Validate email
 */
function isValidEmail(string $email): bool
{
    return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
}

/**
 * Generate secure filename
 */
function generateSecureFilename(string $originalName): string
{
    $extension = pathinfo($originalName, PATHINFO_EXTENSION);
    $filename = uniqid() . '_' . time();
    return $filename . '.' . strtolower($extension);
}

/**
 * File Upload Functions
 */

/**
 * Handle file upload
 */
function handleFileUpload(array $file, string $destination, array $allowedTypes = [], int $maxSize = 2097152): array
{
    try {
        // Check for upload errors
        if ($file['error'] !== UPLOAD_ERR_OK) {
            throw new Exception('File upload error: ' . $file['error']);
        }

        // Check file size
        if ($file['size'] > $maxSize) {
            throw new Exception('File size exceeds maximum allowed size');
        }

        // Check file type
        if (!empty($allowedTypes) && !in_array($file['type'], $allowedTypes)) {
            throw new Exception('File type not allowed');
        }

        // Check if destination directory exists
        if (!is_dir($destination)) {
            if (!mkdir($destination, 0755, true)) {
                throw new Exception('Could not create upload directory');
            }
        }

        // Generate secure filename
        $filename = generateSecureFilename($file['name']);
        $filepath = $destination . '/' . $filename;

        // Move uploaded file
        if (!move_uploaded_file($file['tmp_name'], $filepath)) {
            throw new Exception('Could not move uploaded file');
        }

        return [
            'success' => true,
            'filename' => $filename,
            'filepath' => $filepath,
            'size' => $file['size'],
            'type' => $file['type']
        ];
    } catch (Exception $e) {
        return [
            'success' => false,
            'error' => $e->getMessage()
        ];
    }
}

/**
 * Delete file safely
 */
function deleteFile(string $filepath): bool
{
    if (file_exists($filepath) && is_file($filepath)) {
        return unlink($filepath);
    }
    return false;
}

/**
 * Format file size
 */
function formatFileSize(int $bytes): string
{
    $units = ['B', 'KB', 'MB', 'GB', 'TB'];
    $bytes = max($bytes, 0);
    $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
    $pow = min($pow, count($units) - 1);

    $bytes /= (1 << (10 * $pow));

    return round($bytes, 2) . ' ' . $units[$pow];
}

/**
 * Database Helper Functions
 */

/**
 * Get database connection
 */
function getDB(): PDO
{
    return Database::getInstance()->getConnection();
}

/**
 * Execute query with parameters
 */
function executeQuery(string $sql, array $params = []): PDOStatement
{
    $db = getDB();
    $stmt = $db->prepare($sql);
    $stmt->execute($params);
    return $stmt;
}

/**
 * Get single record
 */
function getSingleRecord(string $sql, array $params = []): ?array
{
    $stmt = executeQuery($sql, $params);
    $result = $stmt->fetch();
    return $result ?: null;
}

/**
 * Get multiple records
 */
function getMultipleRecords(string $sql, array $params = []): array
{
    $stmt = executeQuery($sql, $params);
    return $stmt->fetchAll();
}

/**
 * Get record count
 */
function getRecordCount(string $table, string $where = '', array $params = []): int
{
    $sql = "SELECT COUNT(*) as count FROM {$table}";
    if ($where) {
        $sql .= " WHERE {$where}";
    }

    $result = getSingleRecord($sql, $params);
    return (int)($result['count'] ?? 0);
}

/**
 * Insert record
 */
function insertRecord(string $table, array $data): int
{
    $columns = implode(', ', array_keys($data));
    $placeholders = ':' . implode(', :', array_keys($data));

    $sql = "INSERT INTO {$table} ({$columns}) VALUES ({$placeholders})";
    executeQuery($sql, $data);

    return (int)getDB()->lastInsertId();
}

/**
 * Update record
 */
function updateRecord(string $table, array $data, string $where, array $whereParams = []): int
{
    $setParts = [];
    foreach (array_keys($data) as $column) {
        $setParts[] = "{$column} = :{$column}";
    }
    $setClause = implode(', ', $setParts);

    $sql = "UPDATE {$table} SET {$setClause} WHERE {$where}";
    $params = array_merge($data, $whereParams);

    $stmt = executeQuery($sql, $params);
    return $stmt->rowCount();
}

/**
 * Delete record
 */
function deleteRecord(string $table, string $where, array $params = []): int
{
    $sql = "DELETE FROM {$table} WHERE {$where}";
    $stmt = executeQuery($sql, $params);
    return $stmt->rowCount();
}

/**
 * Utility Functions
 */

/**
 * Generate slug from string
 */
function generateSlug(string $string): string
{
    // Replace non-alphanumeric characters with hyphens
    $slug = preg_replace('/[^a-zA-Z0-9\s]/', '', $string);
    $slug = preg_replace('/\s+/', '-', trim($slug));
    $slug = strtolower($slug);

    // Remove multiple consecutive hyphens
    $slug = preg_replace('/-+/', '-', $slug);

    // Remove hyphens from beginning and end
    return trim($slug, '-');
}

/**
 * Check if slug is unique
 */
function isSlugUnique(string $slug, string $table, string $slugColumn = 'slug', int $excludeId = null): bool
{
    $sql = "SELECT COUNT(*) as count FROM {$table} WHERE {$slugColumn} = :slug";
    $params = ['slug' => $slug];

    if ($excludeId) {
        $sql .= " AND id != :exclude_id";
        $params['exclude_id'] = $excludeId;
    }

    $result = getSingleRecord($sql, $params);
    return (int)$result['count'] === 0;
}

/**
 * Generate unique slug
 */
function generateUniqueSlug(string $string, string $table, string $slugColumn = 'slug', int $excludeId = null): string
{
    $baseSlug = generateSlug($string);
    $slug = $baseSlug;
    $counter = 1;

    while (!isSlugUnique($slug, $table, $slugColumn, $excludeId)) {
        $slug = $baseSlug . '-' . $counter;
        $counter++;
    }

    return $slug;
}

/**
 * Format date for display
 */
function formatDate(string $date, string $format = 'd M Y'): string
{
    try {
        $dateTime = new DateTime($date);
        return $dateTime->format($format);
    } catch (Exception $e) {
        return $date;
    }
}

/**
 * Format date with time for display
 */
function formatDateTime(string $datetime, string $format = 'd M Y H:i'): string
{
    return formatDate($datetime, $format);
}

/**
 * Get time ago format
 */
function timeAgo(string $datetime): string
{
    try {
        $time = time() - strtotime($datetime);

        if ($time < 60) return 'baru saja';
        if ($time < 3600) return floor($time / 60) . ' menit yang lalu';
        if ($time < 86400) return floor($time / 3600) . ' jam yang lalu';
        if ($time < 2592000) return floor($time / 86400) . ' hari yang lalu';
        if ($time < 31536000) return floor($time / 2592000) . ' bulan yang lalu';

        return floor($time / 31536000) . ' tahun yang lalu';
    } catch (Exception $e) {
        return $datetime;
    }
}

/**
 * Truncate text
 */
function truncateText(string $text, int $length = 150, string $suffix = '...'): string
{
    if (strlen($text) <= $length) {
        return $text;
    }

    return substr($text, 0, $length) . $suffix;
}

/**
 * Extract excerpt from content
 */
function extractExcerpt(string $content, int $length = 160): string
{
    // Remove HTML tags
    $text = strip_tags($content);

    // Remove extra whitespace
    $text = preg_replace('/\s+/', ' ', trim($text));

    return truncateText($text, $length);
}

/**
 * Pagination Functions
 */

/**
 * Calculate pagination data
 */
function calculatePagination(int $totalRecords, int $recordsPerPage, int $currentPage = 1): array
{
    $totalPages = (int)ceil($totalRecords / $recordsPerPage);
    $currentPage = max(1, min($currentPage, $totalPages));
    $offset = ($currentPage - 1) * $recordsPerPage;

    return [
        'total_records' => $totalRecords,
        'records_per_page' => $recordsPerPage,
        'total_pages' => $totalPages,
        'current_page' => $currentPage,
        'offset' => $offset,
        'has_previous' => $currentPage > 1,
        'has_next' => $currentPage < $totalPages,
        'previous_page' => max(1, $currentPage - 1),
        'next_page' => min($totalPages, $currentPage + 1)
    ];
}

/**
 * Generate pagination HTML
 */
function generatePaginationHTML(array $pagination, string $baseUrl = ''): string
{
    if ($pagination['total_pages'] <= 1) {
        return '';
    }

    $html = '<nav aria-label="Pagination"><ul class="pagination justify-content-center">';

    // Previous button
    if ($pagination['has_previous']) {
        $html .= '<li class="page-item"><a class="page-link" href="' . $baseUrl . '?page=' . $pagination['previous_page'] . '">‹ Sebelumnya</a></li>';
    } else {
        $html .= '<li class="page-item disabled"><span class="page-link">‹ Sebelumnya</span></li>';
    }

    // Page numbers
    $start = max(1, $pagination['current_page'] - 2);
    $end = min($pagination['total_pages'], $pagination['current_page'] + 2);

    if ($start > 1) {
        $html .= '<li class="page-item"><a class="page-link" href="' . $baseUrl . '?page=1">1</a></li>';
        if ($start > 2) {
            $html .= '<li class="page-item disabled"><span class="page-link">...</span></li>';
        }
    }

    for ($i = $start; $i <= $end; $i++) {
        if ($i == $pagination['current_page']) {
            $html .= '<li class="page-item active"><span class="page-link">' . $i . '</span></li>';
        } else {
            $html .= '<li class="page-item"><a class="page-link" href="' . $baseUrl . '?page=' . $i . '">' . $i . '</a></li>';
        }
    }

    if ($end < $pagination['total_pages']) {
        if ($end < $pagination['total_pages'] - 1) {
            $html .= '<li class="page-item disabled"><span class="page-link">...</span></li>';
        }
        $html .= '<li class="page-item"><a class="page-link" href="' . $baseUrl . '?page=' . $pagination['total_pages'] . '">' . $pagination['total_pages'] . '</a></li>';
    }

    // Next button
    if ($pagination['has_next']) {
        $html .= '<li class="page-item"><a class="page-link" href="' . $baseUrl . '?page=' . $pagination['next_page'] . '">Selanjutnya ›</a></li>';
    } else {
        $html .= '<li class="page-item disabled"><span class="page-link">Selanjutnya ›</span></li>';
    }

    $html .= '</ul></nav>';

    return $html;
}

/**
 * Notification Functions
 */

/**
 * Set flash message
 */
function setFlashMessage(string $type, string $message): void
{
    $_SESSION['flash_messages'][] = [
        'type' => $type,
        'message' => $message,
        'timestamp' => time()
    ];
}

/**
 * Get flash messages
 */
function getFlashMessages(): array
{
    $messages = $_SESSION['flash_messages'] ?? [];
    unset($_SESSION['flash_messages']);
    return $messages;
}

/**
 * Display flash messages HTML
 */
function displayFlashMessages(): string
{
    $messages = getFlashMessages();
    $html = '';

    foreach ($messages as $message) {
        $alertClass = match ($message['type']) {
            'success' => 'alert-success',
            'error' => 'alert-danger',
            'warning' => 'alert-warning',
            'info' => 'alert-info',
            default => 'alert-info'
        };

        $icon = match ($message['type']) {
            'success' => 'bi-check-circle',
            'error' => 'bi-exclamation-triangle',
            'warning' => 'bi-exclamation-circle',
            'info' => 'bi-info-circle',
            default => 'bi-info-circle'
        };

        $html .= '<div class="alert ' . $alertClass . ' alert-dismissible fade show" role="alert">';
        $html .= '<i class="bi ' . $icon . ' me-2"></i>';
        $html .= htmlspecialchars($message['message']);
        $html .= '<button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>';
        $html .= '</div>';
    }

    return $html;
}

/**
 * Get dashboard statistics
 */
function getDashboardStats(): array
{
    try {
        $db = getDB();

        // Get articles count
        $articlesTotal = getRecordCount('articles');
        $articlesPublished = getRecordCount('articles', 'status = ?', ['published']);
        $articlesDraft = getRecordCount('articles', 'status = ?', ['draft']);

        // Get products count
        $productsTotal = getRecordCount('products');
        $productsLowStock = getRecordCount('products', 'stock <= 10');

        // Get messages count
        $messagesTotal = getRecordCount('contact_messages');
        $messagesUnread = getRecordCount('contact_messages', 'status = ?', ['unread']);

        // Get comments count
        $commentsTotal = getRecordCount('comments');
        $commentsPending = getRecordCount('comments', 'status = ?', ['pending']);

        // Get team members count
        $teamMembers = getRecordCount('team_members');

        // Get testimonials count
        $testimonials = getRecordCount('testimonials');
        $testimonialsActive = getRecordCount('testimonials', 'is_active = ?', [1]);

        return [
            'articles' => [
                'total' => $articlesTotal,
                'published' => $articlesPublished,
                'draft' => $articlesDraft
            ],
            'products' => [
                'total' => $productsTotal,
                'low_stock' => $productsLowStock
            ],
            'messages' => [
                'total' => $messagesTotal,
                'unread' => $messagesUnread
            ],
            'comments' => [
                'total' => $commentsTotal,
                'pending' => $commentsPending
            ],
            'team_members' => $teamMembers,
            'testimonials' => [
                'total' => $testimonials,
                'active' => $testimonialsActive
            ]
        ];
    } catch (Exception $e) {
        error_log('Error getting dashboard stats: ' . $e->getMessage());
        return [];
    }
}

/**
 * Get recent activities
 */
function getRecentActivities(int $limit = 10): array
{
    try {
        $db = getDB();

        $activities = [];

        // Recent articles
        $sql = "SELECT id, title, status, created_at, 'article' as type FROM articles 
                ORDER BY created_at DESC LIMIT ?";
        $stmt = $db->prepare($sql);
        $stmt->execute([$limit]);
        $articles = $stmt->fetchAll();

        foreach ($articles as $article) {
            $activities[] = [
                'type' => 'article',
                'action' => $article['status'] === 'published' ? 'published' : 'created',
                'title' => $article['title'],
                'url' => "modules/articles/edit.php?id=" . $article['id'],
                'timestamp' => $article['created_at'],
                'icon' => 'bi-file-text',
                'color' => $article['status'] === 'published' ? 'success' : 'info'
            ];
        }

        // Recent messages
        $sql = "SELECT id, name, subject, submitted_at FROM contact_messages 
                ORDER BY submitted_at DESC LIMIT ?";
        $stmt = $db->prepare($sql);
        $stmt->execute([$limit]);
        $messages = $stmt->fetchAll();

        foreach ($messages as $message) {
            $activities[] = [
                'type' => 'message',
                'action' => 'received',
                'title' => "Pesan dari " . $message['name'],
                'description' => $message['subject'],
                'url' => "modules/messages/view.php?id=" . $message['id'],
                'timestamp' => $message['submitted_at'],
                'icon' => 'bi-envelope',
                'color' => 'primary'
            ];
        }

        // Recent comments
        $sql = "SELECT c.id, c.author_name, c.content, c.created_at, a.title as article_title
                FROM comments c
                JOIN articles a ON c.article_id = a.id
                ORDER BY c.created_at DESC LIMIT ?";
        $stmt = $db->prepare($sql);
        $stmt->execute([$limit]);
        $comments = $stmt->fetchAll();

        foreach ($comments as $comment) {
            $activities[] = [
                'type' => 'comment',
                'action' => 'posted',
                'title' => "Komentar dari " . $comment['author_name'],
                'description' => "pada artikel: " . $comment['article_title'],
                'url' => "modules/comments/moderate.php?id=" . $comment['id'],
                'timestamp' => $comment['created_at'],
                'icon' => 'bi-chat',
                'color' => 'warning'
            ];
        }

        // Sort by timestamp
        usort($activities, function ($a, $b) {
            return strtotime($b['timestamp']) - strtotime($a['timestamp']);
        });

        return array_slice($activities, 0, $limit);
    } catch (Exception $e) {
        error_log('Error getting recent activities: ' . $e->getMessage());
        return [];
    }
}

/**
 * Settings Functions
 */

/**
 * Get site setting value
 */
function getSiteSetting(string $key, string $default = ''): string
{
    static $settings = null;

    if ($settings === null) {
        $settings = [];
        $results = getMultipleRecords("SELECT setting_key, setting_value FROM site_settings");
        foreach ($results as $row) {
            $settings[$row['setting_key']] = $row['setting_value'];
        }
    }

    return $settings[$key] ?? $default;
}

/**
 * Update site setting
 */
function updateSiteSetting(string $key, string $value): bool
{
    try {
        $db = getDB();

        // Check if setting exists
        $existing = getSingleRecord("SELECT id FROM site_settings WHERE setting_key = ?", [$key]);

        if ($existing) {
            // Update existing setting
            $sql = "UPDATE site_settings SET setting_value = ?, updated_at = NOW() WHERE setting_key = ?";
            executeQuery($sql, [$value, $key]);
        } else {
            // Insert new setting
            $sql = "INSERT INTO site_settings (setting_key, setting_value, created_at) VALUES (?, ?, NOW())";
            executeQuery($sql, [$key, $value]);
        }

        return true;
    } catch (Exception $e) {
        error_log('Error updating site setting: ' . $e->getMessage());
        return false;
    }
}

/**
 * Image Processing Functions
 */

/**
 * Resize image
 */
function resizeImage(string $sourcePath, string $destinationPath, int $maxWidth, int $maxHeight, int $quality = 90): bool
{
    try {
        // Get image info
        $imageInfo = getimagesize($sourcePath);
        if (!$imageInfo) {
            return false;
        }

        $originalWidth = $imageInfo[0];
        $originalHeight = $imageInfo[1];
        $imageType = $imageInfo[2];

        // Calculate new dimensions
        $ratio = min($maxWidth / $originalWidth, $maxHeight / $originalHeight);
        $newWidth = (int)($originalWidth * $ratio);
        $newHeight = (int)($originalHeight * $ratio);

        // Create image resource based on type
        switch ($imageType) {
            case IMAGETYPE_JPEG:
                $sourceImage = imagecreatefromjpeg($sourcePath);
                break;
            case IMAGETYPE_PNG:
                $sourceImage = imagecreatefrompng($sourcePath);
                break;
            case IMAGETYPE_GIF:
                $sourceImage = imagecreatefromgif($sourcePath);
                break;
            default:
                return false;
        }

        if (!$sourceImage) {
            return false;
        }

        // Create new image
        $newImage = imagecreatetruecolor($newWidth, $newHeight);

        // Preserve transparency for PNG and GIF
        if ($imageType == IMAGETYPE_PNG || $imageType == IMAGETYPE_GIF) {
            imagealphablending($newImage, false);
            imagesavealpha($newImage, true);
            $transparent = imagecolorallocatealpha($newImage, 255, 255, 255, 127);
            imagefill($newImage, 0, 0, $transparent);
        }

        // Resize image
        imagecopyresampled($newImage, $sourceImage, 0, 0, 0, 0, $newWidth, $newHeight, $originalWidth, $originalHeight);

        // Save image based on type
        $result = false;
        switch ($imageType) {
            case IMAGETYPE_JPEG:
                $result = imagejpeg($newImage, $destinationPath, $quality);
                break;
            case IMAGETYPE_PNG:
                $result = imagepng($newImage, $destinationPath);
                break;
            case IMAGETYPE_GIF:
                $result = imagegif($newImage, $destinationPath);
                break;
        }

        // Clean up
        imagedestroy($sourceImage);
        imagedestroy($newImage);

        return $result;
    } catch (Exception $e) {
        error_log('Error resizing image: ' . $e->getMessage());
        return false;
    }
}

/**
 * Generate thumbnail
 */
function generateThumbnail(string $sourcePath, string $destinationPath, int $size = 150): bool
{
    return resizeImage($sourcePath, $destinationPath, $size, $size);
}

/**
 * Validation Functions
 */

/**
 * Validate article data
 */
function validateArticleData(array $data): array
{
    $errors = [];

    if (empty(trim($data['title'] ?? ''))) {
        $errors['title'] = 'Judul artikel wajib diisi';
    }

    if (empty(trim($data['content'] ?? ''))) {
        $errors['content'] = 'Konten artikel wajib diisi';
    }

    if (empty($data['category_id'] ?? '')) {
        $errors['category_id'] = 'Kategori artikel wajib dipilih';
    }

    if (!in_array($data['status'] ?? '', ['draft', 'published'])) {
        $errors['status'] = 'Status artikel tidak valid';
    }

    return $errors;
}

/**
 * Validate product data
 */
function validateProductData(array $data): array
{
    $errors = [];

    if (empty(trim($data['name'] ?? ''))) {
        $errors['name'] = 'Nama produk wajib diisi';
    }

    if (empty(trim($data['description'] ?? ''))) {
        $errors['description'] = 'Deskripsi produk wajib diisi';
    }

    if (empty($data['category_id'] ?? '')) {
        $errors['category_id'] = 'Kategori produk wajib dipilih';
    }

    $stock = $data['stock'] ?? '';
    if (!is_numeric($stock) || $stock < 0) {
        $errors['stock'] = 'Stok harus berupa angka positif';
    }

    return $errors;
}

/**
 * Validate team member data
 */
function validateTeamMemberData(array $data): array
{
    $errors = [];

    if (empty(trim($data['name'] ?? ''))) {
        $errors['name'] = 'Nama anggota tim wajib diisi';
    }

    if (empty(trim($data['position'] ?? ''))) {
        $errors['position'] = 'Posisi wajib diisi';
    }

    $displayOrder = $data['display_order'] ?? '';
    if (!is_numeric($displayOrder) || $displayOrder < 0) {
        $errors['display_order'] = 'Urutan tampilan harus berupa angka positif';
    }

    return $errors;
}

/**
 * Logging Functions
 */

/**
 * Log admin activity
 */
function logActivity(string $action, string $description, int $userId = null): void
{
    try {
        $currentUser = getCurrentUser();
        $userId = $userId ?? ($currentUser['id'] ?? null);

        $data = [
            'user_id' => $userId,
            'action' => $action,
            'description' => $description,
            'ip_address' => $_SERVER['REMOTE_ADDR'] ?? '',
            'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? '',
            'created_at' => date('Y-m-d H:i:s')
        ];

        // Create activity_logs table if it doesn't exist
        $db = getDB();
        $db->exec("CREATE TABLE IF NOT EXISTS activity_logs (
            id INT AUTO_INCREMENT PRIMARY KEY,
            user_id INT,
            action VARCHAR(100),
            description TEXT,
            ip_address VARCHAR(45),
            user_agent TEXT,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            INDEX idx_user_id (user_id),
            INDEX idx_created_at (created_at)
        )");

        insertRecord('activity_logs', $data);
    } catch (Exception $e) {
        error_log('Error logging activity: ' . $e->getMessage());
    }
}

/**
 * Export Functions
 */

/**
 * Export data to CSV
 */
function exportToCSV(array $data, array $headers, string $filename): void
{
    header('Content-Type: text/csv');
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    header('Cache-Control: must-revalidate, post-check=0, pre-check=0');
    header('Expires: 0');

    $output = fopen('php://output', 'w');

    // Write headers
    fputcsv($output, $headers);

    // Write data
    foreach ($data as $row) {
        fputcsv($output, $row);
    }

    fclose($output);
    exit;
}

/**
 * Backup Functions
 */

/**
 * Create database backup
 */
function createDatabaseBackup(): string
{
    try {
        $db = getDB();
        $backupFile = 'backup_' . date('Y-m-d_H-i-s') . '.sql';
        $backupPath = '../backups/' . $backupFile;

        // Create backups directory if it doesn't exist
        if (!is_dir('../backups')) {
            mkdir('../backups', 0755, true);
        }

        // Get all tables
        $tables = [];
        $result = $db->query("SHOW TABLES");
        while ($row = $result->fetch(PDO::FETCH_NUM)) {
            $tables[] = $row[0];
        }

        $backup = "-- Database Backup\n";
        $backup .= "-- Generated on: " . date('Y-m-d H:i:s') . "\n\n";

        foreach ($tables as $table) {
            // Get table structure
            $result = $db->query("SHOW CREATE TABLE {$table}");
            $row = $result->fetch(PDO::FETCH_NUM);
            $backup .= "-- Table structure for {$table}\n";
            $backup .= "DROP TABLE IF EXISTS {$table};\n";
            $backup .= $row[1] . ";\n\n";

            // Get table data
            $result = $db->query("SELECT * FROM {$table}");
            if ($result->rowCount() > 0) {
                $backup .= "-- Data for table {$table}\n";
                while ($row = $result->fetch(PDO::FETCH_ASSOC)) {
                    $values = array_map(function ($value) {
                        return $value === null ? 'NULL' : "'" . addslashes($value) . "'";
                    }, $row);
                    $backup .= "INSERT INTO {$table} VALUES (" . implode(', ', $values) . ");\n";
                }
                $backup .= "\n";
            }
        }

        file_put_contents($backupPath, $backup);

        return $backupFile;
    } catch (Exception $e) {
        error_log('Error creating database backup: ' . $e->getMessage());
        throw $e;
    }
}

/**
 * System Information Functions
 */

/**
 * Get system information
 */
function getSystemInfo(): array
{
    return [
        'php_version' => PHP_VERSION,
        'server_software' => $_SERVER['SERVER_SOFTWARE'] ?? 'Unknown',
        'memory_limit' => ini_get('memory_limit'),
        'max_execution_time' => ini_get('max_execution_time'),
        'upload_max_filesize' => ini_get('upload_max_filesize'),
        'post_max_size' => ini_get('post_max_size'),
        // 'disk_free_space' => formatFileSize(disk_free_space('.')),
        'current_time' => date('Y-m-d H:i:s')
    ];
}


/**
 * Check system requirements
 */
function checkSystemRequirements(): array
{
    $requirements = [];

    // PHP Version
    $requirements['php_version'] = [
        'name' => 'PHP Version',
        'required' => '7.4.0',
        'current' => PHP_VERSION,
        'status' => version_compare(PHP_VERSION, '7.4.0', '>=')
    ];

    // PDO Extension
    $requirements['pdo'] = [
        'name' => 'PDO Extension',
        'required' => 'Enabled',
        'current' => extension_loaded('pdo') ? 'Enabled' : 'Disabled',
        'status' => extension_loaded('pdo')
    ];

    // GD Extension
    $requirements['gd'] = [
        'name' => 'GD Extension',
        'required' => 'Enabled',
        'current' => extension_loaded('gd') ? 'Enabled' : 'Disabled',
        'status' => extension_loaded('gd')
    ];

    // Uploads directory writable
    $requirements['uploads_writable'] = [
        'name' => 'Uploads Directory Writable',
        'required' => 'Yes',
        'current' => is_writable('uploads') ? 'Yes' : 'No',
        'status' => is_writable('uploads')
    ];

    return $requirements;
}

// Define admin path constant
if (!defined('ADMIN_PATH')) {
    define('ADMIN_PATH', __DIR__);
}
