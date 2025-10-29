<?php

declare(strict_types=1);

ini_set('display_errors', '0');
ob_start();

require_once '../../../config/database.php';
require_once '../../../config/auth.php';
require_once '../../../includes/functions.php';

// Check authentication
requireAuth();

// Set content type to JSON
header('Content-Type: application/json');

function send_json(array $payload, int $statusCode = 200): void
{
    http_response_code($statusCode);
    if (ob_get_level() > 0) {
        ob_clean();
    }
    echo json_encode($payload);
    exit;
}

// Check if it's a POST request
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    send_json(['status' => 'error', 'message' => 'Method not allowed'], 405);
}

// Get database connection
$db = Database::getInstance()->getConnection();

// Get POST data
$message_id = filter_input(INPUT_POST, 'id', FILTER_VALIDATE_INT);
$action = isset($_POST['action']) ? trim((string)$_POST['action']) : '';

// Validate input
if (!$message_id) {
    send_json(['status' => 'error', 'message' => 'Invalid message ID'], 400);
}

if ($action === '') {
    send_json(['status' => 'error', 'message' => 'Invalid action'], 400);
}

try {
    // Check if message exists
    $stmt = $db->prepare("SELECT id, name, email, subject, message, status, submitted_at FROM contact_messages WHERE id = ?");
    $stmt->execute([$message_id]);
    $message = $stmt->fetch();

    if (!$message) {
        send_json(['status' => 'error', 'message' => 'Message not found'], 404);
    }

    // Handle special actions first
    if ($action === 'gmail_reply') {
        // Log that Gmail reply was initiated (optional)
        error_log("Gmail reply initiated for message ID: {$message_id}, To: {$message['email']}");

        // You could add tracking here if needed
        // For now, we just return success to acknowledge the action
        send_json([
            'status' => 'success',
            'message' => 'Gmail reply initiated',
            'action' => 'gmail_reply',
            'recipient' => $message['email']
        ]);
    }

    // Determine new status based on action
    switch ($action) {
        case 'mark_read':
            $new_status = 'read';
            break;
        case 'mark_unread':
            $new_status = 'unread';
            break;
        case 'mark_replied':
            $new_status = 'replied';
            break;
        case 'archive':
            $new_status = 'archived';
            break;
        case 'unarchive':
            $new_status = 'read';
            break;
        default:
            send_json(['status' => 'error', 'message' => 'Invalid action'], 400);
    }

    // Update message status
    $stmt = $db->prepare("UPDATE contact_messages SET status = ?, updated_at = NOW() WHERE id = ?");
    $result = $stmt->execute([$new_status, $message_id]);

    if ($result) {
        // Log the status change
        error_log("Message status updated: ID {$message_id} changed to '{$new_status}' by admin");

        send_json([
            'status' => 'success',
            'message' => 'Status updated successfully',
            'new_status' => $new_status,
            'message_id' => $message_id
        ]);
    }

    send_json(['status' => 'error', 'message' => 'Failed to update status'], 500);
} catch (PDOException $e) {
    error_log("Database error in api_update_pesan.php: " . $e->getMessage());
    send_json(['status' => 'error', 'message' => 'Database error occurred'], 500);
} catch (Exception $e) {
    error_log("General error in api_update_pesan.php: " . $e->getMessage());
    send_json(['status' => 'error', 'message' => 'An error occurred'], 500);
}
