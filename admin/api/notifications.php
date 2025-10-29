<?php

/**
 * Notifications API - Fixed Version
 * PT. Sarana Sentra Teknologi Utama
 */

declare(strict_types=1);

// Set JSON headers first
header('Content-Type: application/json');
header('Cache-Control: no-cache, must-revalidate');

require_once __DIR__ . '/../config/auth.php';

try {
    $auth = new Auth();

    if (!$auth->isLoggedIn()) {
        http_response_code(401);
        echo json_encode([
            'success' => false,
            'error' => 'Not authenticated'
        ]);
        exit;
    }

    require_once __DIR__ . '/../config/database.php';
    $db = Database::getInstance()->getConnection();

    $notifications = [
        'total_count' => 0,
        'items' => []
    ];

    $stmt = $db->prepare("SELECT COUNT(*) as count FROM contact_messages WHERE status = 'unread'");
    $stmt->execute();
    $unread_messages = $stmt->fetchColumn();

    if ($unread_messages > 0) {
        $notifications['total_count'] += $unread_messages;
        $notifications['items'][] = [
            'type' => 'message',
            'title' => 'Pesan Baru',
            'description' => "{$unread_messages} pesan masuk dari website",
            'link' => 'modules/messages/',
            'icon' => 'bi-envelope',
            'color' => 'primary',
            'time' => 'baru'
        ];
    }

    $stmt = $db->prepare("SELECT COUNT(*) as count FROM comments WHERE status = 'pending'");
    $stmt->execute();
    $pending_comments = $stmt->fetchColumn();

    if ($pending_comments > 0) {
        $notifications['total_count'] += $pending_comments;
        $notifications['items'][] = [
            'type' => 'comment',
            'title' => 'Komentar Pending',
            'description' => "{$pending_comments} komentar menunggu moderasi",
            'link' => 'modules/comments/',
            'icon' => 'bi-chat',
            'color' => 'warning',
            'time' => 'baru'
        ];
    }

    $stmt = $db->prepare("SELECT COUNT(*) as count FROM articles WHERE status = 'draft'");
    $stmt->execute();
    $draft_articles = $stmt->fetchColumn();

    if ($draft_articles > 0) {
        $notifications['items'][] = [
            'type' => 'article',
            'title' => 'Artikel Draft',
            'description' => "{$draft_articles} artikel dalam status draft",
            'link' => 'modules/articles/',
            'icon' => 'bi-file-earmark-text',
            'color' => 'info',
            'time' => 'draft'
        ];
    }

    $stmt = $db->prepare("SELECT COUNT(*) as count FROM products WHERE stock <= 5 AND stock > 0");
    $stmt->execute();
    $low_stock = $stmt->fetchColumn();

    if ($low_stock > 0) {
        $notifications['items'][] = [
            'type' => 'stock',
            'title' => 'Stok Menipis',
            'description' => "{$low_stock} produk dengan stok rendah",
            'link' => 'modules/products/stock.php',
            'icon' => 'bi-box',
            'color' => 'danger',
            'time' => 'perlu perhatian'
        ];
    }

    echo json_encode([
        'success' => true,
        'data' => $notifications
    ]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Gagal mengambil notifikasi',
        'debug' => $e->getMessage()
    ]);
    error_log("Notifications API error: " . $e->getMessage());
}
