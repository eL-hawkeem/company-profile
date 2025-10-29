<?php

declare(strict_types=1);

require_once '../../../config/database.php';
require_once '../../../config/auth.php';
require_once '../../../includes/functions.php';

// Check authentication
requireAuth();

// Set content type to JSON
header('Content-Type: application/json');

// Check if it's a POST request
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['status' => 'error', 'message' => 'Method not allowed']);
    exit;
}

// Get database connection
$db = Database::getInstance()->getConnection();

// Get POST data
$comment_id = filter_input(INPUT_POST, 'id', FILTER_VALIDATE_INT);
$action = filter_input(INPUT_POST, 'action', FILTER_SANITIZE_STRING);

// Validate input
if (!$comment_id) {
    echo json_encode(['status' => 'error', 'message' => 'Invalid comment ID']);
    exit;
}

if (!$action) {
    echo json_encode(['status' => 'error', 'message' => 'Invalid action']);
    exit;
}

try {
    // Check if comment exists
    $stmt = $db->prepare("SELECT id, status FROM comments WHERE id = ?");
    $stmt->execute([$comment_id]);
    $comment = $stmt->fetch();

    if (!$comment) {
        echo json_encode(['status' => 'error', 'message' => 'Comment not found']);
        exit;
    }

    // Determine new status based on action
    switch ($action) {
        case 'approve':
            $new_status = 'approved';
            break;
        case 'pending':  // TAMBAHKAN INI
            $new_status = 'pending';
            break;
        case 'spam':
            $new_status = 'spam';
            break;
        case 'delete':
            // Delete comment
            $stmt = $db->prepare("DELETE FROM comments WHERE id = ?");
            $result = $stmt->execute([$comment_id]);

            if ($result) {
                echo json_encode([
                    'status' => 'success',
                    'message' => 'Comment deleted successfully'
                ]);
            } else {
                echo json_encode(['status' => 'error', 'message' => 'Failed to delete comment']);
            }
            exit;
        default:
            echo json_encode(['status' => 'error', 'message' => 'Invalid action']);
            exit;
    }

    // Update comment status
    $stmt = $db->prepare("UPDATE comments SET status = ? WHERE id = ?");
    $result = $stmt->execute([$new_status, $comment_id]);

    if ($result) {
        echo json_encode([
            'status' => 'success',
            'message' => 'Comment status updated successfully',
            'new_status' => $new_status
        ]);
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Failed to update comment status']);
    }
} catch (PDOException $e) {
    error_log("Database error in process_comments.php: " . $e->getMessage());
    echo json_encode(['status' => 'error', 'message' => 'Database error occurred']);
} catch (Exception $e) {
    error_log("General error in process_comments.php: " . $e->getMessage());
    echo json_encode(['status' => 'error', 'message' => 'An error occurred']);
}
