<?php

declare(strict_types=1);

require_once '../../config/database.php';
require_once '../../config/auth.php';
require_once '../../includes/functions.php';

requireAuth();

// Verify CSRF token
if (!isset($_POST['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) {
    http_response_code(403);
    die('CSRF token mismatch');
}

$product_id = filter_input(INPUT_POST, 'product_id', FILTER_VALIDATE_INT);

if (!$product_id) {
    http_response_code(400);
    die('Invalid product ID');
}

$db = Database::getInstance()->getConnection();

try {
    // Get product info
    $stmt = $db->prepare("SELECT name FROM products WHERE id = ?");
    $stmt->execute([$product_id]);
    $product = $stmt->fetch();

    if (!$product) {
        http_response_code(404);
        die('Product not found');
    }

    // Get stock history
    $stmt = $db->prepare("
        SELECT 
            sh.*,
            u.username as user_name
        FROM stock_history sh
        LEFT JOIN users u ON sh.user_id = u.id
        WHERE sh.product_id = ?
        ORDER BY sh.created_at DESC
        LIMIT 50
    ");
    $stmt->execute([$product_id]);
    $history = $stmt->fetchAll();

    if (empty($history)) {
        echo '<div class="text-center py-4">
                <i class="fas fa-history fa-3x text-muted mb-3"></i>
                <h5>No Stock History</h5>
                <p class="text-muted">No stock history found for this product.</p>
              </div>';
        exit;
    }

    // Display history
    ?>
    <div class="table-responsive">
        <table class="table table-sm">
            <thead class="table-light">
                <tr>
                    <th>Date</th>
                    <th>Operation</th>
                    <th>Before</th>
                    <th>Change</th>
                    <th>After</th>
                    <th>User</th>
                    <th>Notes</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($history as $record): ?>
                    <tr>
                        <td>
                            <small><?= date('M d, Y H:i', strtotime($record['created_at'])) ?></small>
                        </td>
                        <td>
                            <?php
                            $operation_class = match ($record['operation_type']) {
                                'in' => 'text-success',
                                'out' => 'text-danger',
                                'adjustment' => 'text-warning',
                                default => 'text-muted'
                            };
                            $operation_text = match ($record['operation_type']) {
                                'in' => 'Stock In',
                                'out' => 'Stock Out',
                                'adjustment' => 'Adjustment',
                                default => ucfirst($record['operation_type'])
                            };
                            ?>
                            <span class="badge bg-light <?= $operation_class ?>">
                                <?= $operation_text ?>
                            </span>
                        </td>
                        <td>
                            <strong><?= $record['quantity_before'] ?></strong>
                        </td>
                        <td>
                            <?php
                            $change_class = $record['quantity_change'] > 0 ? 'text-success' : 'text-danger';
                            $change_symbol = $record['quantity_change'] > 0 ? '+' : '';
                            ?>
                            <span class="<?= $change_class ?>">
                                <?= $change_symbol . $record['quantity_change'] ?>
                            </span>
                        </td>
                        <td>
                            <strong><?= $record['quantity_after'] ?></strong>
                        </td>
                        <td>
                            <small class="text-muted"><?= htmlspecialchars($record['user_name'] ?? 'Unknown') ?></small>
                        </td>
                        <td>
                            <?php if ($record['notes']): ?>
                                <small class="text-muted"><?= htmlspecialchars($record['notes']) ?></small>
                            <?php else: ?>
                                <small class="text-muted">-</small>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>

    <div class="mt-3">
        <small class="text-muted">
            Showing last 50 records. Total records: <?= count($history) ?>
        </small>
    </div>
    <?php

} catch (Exception $e) {
    error_log("Stock history error: " . $e->getMessage());
    echo '<div class="alert alert-danger">
            <i class="fas fa-exclamation-triangle"></i>
            Error loading stock history. Please try again.
          </div>';
}
?> 