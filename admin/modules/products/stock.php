<?php

declare(strict_types=1);

require_once '../../config/database.php';
require_once '../../config/auth.php';
require_once '../../includes/functions.php';

requireAuth();

$db = Database::getInstance()->getConnection();

// Generate CSRF token if not exists
if (!isset($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// Handle stock update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    if (!hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) {
        die('CSRF token mismatch');
    }

    try {
        if ($_POST['action'] === 'update_stock') {
            $product_id = filter_input(INPUT_POST, 'product_id', FILTER_VALIDATE_INT);
            $quantity_change = filter_input(INPUT_POST, 'quantity_change', FILTER_VALIDATE_INT);
            $operation_type = filter_input(INPUT_POST, 'operation_type', FILTER_SANITIZE_STRING);
            $notes = trim(filter_input(INPUT_POST, 'notes', FILTER_SANITIZE_STRING));

            // Validation
            if (!$product_id) {
                throw new Exception('Invalid product ID');
            }
            
            if (!$quantity_change || $quantity_change <= 0) {
                throw new Exception('Quantity must be a positive number');
            }
            
            if (!in_array($operation_type, ['in', 'out', 'adjustment'])) {
                throw new Exception('Invalid operation type');
            }

            $db->beginTransaction();

            // Get current stock
            $stmt = $db->prepare("SELECT stock, name FROM products WHERE id = ?");
            $stmt->execute([$product_id]);
            $product = $stmt->fetch();

            if (!$product) {
                throw new Exception('Product not found');
            }

            // Calculate new stock
            $new_stock = $product['stock'];
            switch ($operation_type) {
                case 'in':
                    $new_stock += $quantity_change;
                    break;
                case 'out':
                    $new_stock -= $quantity_change;
                    break;
                case 'adjustment':
                    $new_stock = $quantity_change;
                    break;
            }

            if ($new_stock < 0) {
                throw new Exception('Stock cannot be negative');
            }

            // Update product stock
            $stmt = $db->prepare("UPDATE products SET stock = ?, updated_at = NOW() WHERE id = ?");
            $stmt->execute([$new_stock, $product_id]);

            // Record stock history (if table exists)
            try {
                $stmt = $db->prepare("
                    INSERT INTO stock_history (product_id, operation_type, quantity_before, quantity_change, quantity_after, notes, user_id, created_at) 
                    VALUES (?, ?, ?, ?, ?, ?, ?, NOW())
                ");
                $stmt->execute([
                    $product_id,
                    $operation_type,
                    $product['stock'],
                    $quantity_change,
                    $new_stock,
                    $notes,
                    $_SESSION['user_id'] ?? 1
                ]);
            } catch (PDOException $e) {
                // If stock_history table doesn't exist, just log the error but don't fail
                error_log("Stock history table not found: " . $e->getMessage());
            }

            $db->commit();

            $_SESSION['success_message'] = "Stock updated successfully for {$product['name']}";
            header('Location: stock.php');
            exit;
        }
    } catch (Exception $e) {
        if ($db->inTransaction()) {
            $db->rollBack();
        }
        $_SESSION['error_message'] = $e->getMessage();
        header('Location: stock.php');
        exit;
    }
}

// Get products with stock info
$search = filter_input(INPUT_GET, 'search', FILTER_SANITIZE_STRING) ?? '';
$category_filter = filter_input(INPUT_GET, 'category', FILTER_VALIDATE_INT) ?? '';
$stock_status = filter_input(INPUT_GET, 'stock_status', FILTER_SANITIZE_STRING) ?? '';

$where_conditions = [];
$params = [];

if ($search) {
    $where_conditions[] = "(p.name LIKE ? OR p.description LIKE ?)";
    $params[] = "%{$search}%";
    $params[] = "%{$search}%";
}

if ($category_filter) {
    $where_conditions[] = "p.category_id = ?";
    $params[] = $category_filter;
}

if ($stock_status) {
    switch ($stock_status) {
        case 'low':
            $where_conditions[] = "p.stock <= 10";
            break;
        case 'out':
            $where_conditions[] = "p.stock = 0";
            break;
        case 'available':
            $where_conditions[] = "p.stock > 10";
            break;
    }
}

$where_clause = $where_conditions ? 'WHERE ' . implode(' AND ', $where_conditions) : '';

$sql = "
    SELECT p.*, pc.name as category_name,
           CASE 
               WHEN p.stock = 0 THEN 'out-of-stock'
               WHEN p.stock <= 10 THEN 'low-stock'
               ELSE 'in-stock'
           END as stock_status
    FROM products p
    LEFT JOIN product_categories pc ON p.category_id = pc.id
    {$where_clause}
    ORDER BY p.stock ASC, p.name ASC
";

$stmt = $db->prepare($sql);
$stmt->execute($params);
$products = $stmt->fetchAll();

// Get categories for filter
$categories_stmt = $db->query("SELECT * FROM product_categories ORDER BY name");
$categories = $categories_stmt->fetchAll();

$pageTitle = "Stock Management";
include '../../includes/header.php';
?>

<style>
    .stock-status-badge {
        font-size: 0.75rem;
        padding: 0.25rem 0.5rem;
    }
    
    .stock-quantity {
        font-size: 1.1rem;
        font-weight: 600;
    }
    
    .product-image {
        width: 40px;
        height: 40px;
        object-fit: cover;
        border-radius: 4px;
    }
    
    .product-info {
        max-width: 200px;
    }
    
    .product-name {
        font-weight: 600;
        margin-bottom: 2px;
    }
    
    .product-description {
        font-size: 0.75rem;
        color: #6c757d;
        line-height: 1.2;
    }
    
    .table-actions {
        white-space: nowrap;
    }
    
    .table-actions .btn {
        margin-right: 0.25rem;
    }
    
    .table-actions .btn:last-child {
        margin-right: 0;
    }
    
    .preview-alert {
        margin-top: 1rem;
        padding: 0.75rem;
        border-radius: 0.375rem;
    }
    
    .quantity-help {
        font-size: 0.875rem;
        margin-top: 0.25rem;
    }
    
    .quantity-help.text-danger {
        color: #dc3545 !important;
    }
    
    .quantity-help.text-muted {
        color: #6c757d !important;
    }
    
    @media (max-width: 768px) {
        .table-actions .btn {
            display: block;
            margin-bottom: 0.25rem;
            width: 100%;
        }
        
        .product-info {
            max-width: 150px;
        }
    }
</style>

<!-- Page Header -->
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h1 class="h3 mb-0 text-gray-800">
            <i class="fas fa-boxes"></i> Stock Management
        </h1>
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="../../index.php">Dashboard</a></li>
                <li class="breadcrumb-item"><a href="index.php">Products</a></li>
                <li class="breadcrumb-item active">Stock</li>
            </ol>
        </nav>
    </div>
    <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#stockHistoryModal">
        <i class="fas fa-history"></i> View History
    </button>
</div>

<!-- Alert Messages -->
<?php if (isset($_SESSION['success_message'])): ?>
    <div class="alert alert-success alert-dismissible fade show" role="alert">
        <?= htmlspecialchars($_SESSION['success_message']) ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
    <?php unset($_SESSION['success_message']); ?>
<?php endif; ?>

<?php if (isset($_SESSION['error_message'])): ?>
    <div class="alert alert-danger alert-dismissible fade show" role="alert">
        <?= htmlspecialchars($_SESSION['error_message']) ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
    <?php unset($_SESSION['error_message']); ?>
<?php endif; ?>

<!-- Stock Summary Cards -->
<div class="row mb-4">
    <?php
    $total_products = count($products);
    $out_of_stock = count(array_filter($products, fn($p) => $p['stock'] == 0));
    $low_stock = count(array_filter($products, fn($p) => $p['stock'] > 0 && $p['stock'] <= 10));
    $in_stock = count(array_filter($products, fn($p) => $p['stock'] > 10));
    ?>

    <div class="col-xl-3 col-md-6 mb-4">
        <div class="card border-left-primary shadow h-100 py-2">
            <div class="card-body">
                <div class="row no-gutters align-items-center">
                    <div class="col mr-2">
                        <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">Total Products</div>
                        <div class="h5 mb-0 font-weight-bold text-gray-800"><?= $total_products ?></div>
                    </div>
                    <div class="col-auto">
                        <i class="fas fa-boxes fa-2x text-gray-300"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="col-xl-3 col-md-6 mb-4">
        <div class="card border-left-success shadow h-100 py-2">
            <div class="card-body">
                <div class="row no-gutters align-items-center">
                    <div class="col mr-2">
                        <div class="text-xs font-weight-bold text-success text-uppercase mb-1">In Stock</div>
                        <div class="h5 mb-0 font-weight-bold text-gray-800"><?= $in_stock ?></div>
                    </div>
                    <div class="col-auto">
                        <i class="fas fa-check-circle fa-2x text-gray-300"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="col-xl-3 col-md-6 mb-4">
        <div class="card border-left-warning shadow h-100 py-2">
            <div class="card-body">
                <div class="row no-gutters align-items-center">
                    <div class="col mr-2">
                        <div class="text-xs font-weight-bold text-warning text-uppercase mb-1">Low Stock</div>
                        <div class="h5 mb-0 font-weight-bold text-gray-800"><?= $low_stock ?></div>
                    </div>
                    <div class="col-auto">
                        <i class="fas fa-exclamation-triangle fa-2x text-gray-300"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="col-xl-3 col-md-6 mb-4">
        <div class="card border-left-danger shadow h-100 py-2">
            <div class="card-body">
                <div class="row no-gutters align-items-center">
                    <div class="col mr-2">
                        <div class="text-xs font-weight-bold text-danger text-uppercase mb-1">Out of Stock</div>
                        <div class="h5 mb-0 font-weight-bold text-gray-800"><?= $out_of_stock ?></div>
                    </div>
                    <div class="col-auto">
                        <i class="fas fa-times-circle fa-2x text-gray-300"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Filters -->
<div class="card shadow mb-4">
    <div class="card-header py-3">
        <h6 class="m-0 font-weight-bold text-primary">Filters</h6>
    </div>
    <div class="card-body">
        <form method="GET" class="row g-3">
            <div class="col-md-4">
                <label for="search" class="form-label">Search Products</label>
                <input type="text" class="form-control" id="search" name="search"
                    value="<?= htmlspecialchars($search) ?>" placeholder="Product name or description...">
            </div>
            <div class="col-md-3">
                <label for="category" class="form-label">Category</label>
                <select class="form-select" id="category" name="category">
                    <option value="">All Categories</option>
                    <?php foreach ($categories as $category): ?>
                        <option value="<?= $category['id'] ?>" <?= $category_filter == $category['id'] ? 'selected' : '' ?>>
                            <?= htmlspecialchars($category['name']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-3">
                <label for="stock_status" class="form-label">Stock Status</label>
                <select class="form-select" id="stock_status" name="stock_status">
                    <option value="">All Status</option>
                    <option value="available" <?= $stock_status === 'available' ? 'selected' : '' ?>>Available (>10)</option>
                    <option value="low" <?= $stock_status === 'low' ? 'selected' : '' ?>>Low Stock (≤10)</option>
                    <option value="out" <?= $stock_status === 'out' ? 'selected' : '' ?>>Out of Stock</option>
                </select>
            </div>
            <div class="col-md-2">
                <label class="form-label">&nbsp;</label>
                <div class="d-grid">
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-search"></i> Filter
                    </button>
                </div>
            </div>
        </form>
    </div>
</div>

<!-- Products Stock Table -->
<div class="card shadow">
    <div class="card-header py-3">
        <h6 class="m-0 font-weight-bold text-primary">Products Stock</h6>
    </div>
    <div class="card-body">
        <?php if (empty($products)): ?>
            <div class="text-center py-5">
                <i class="fas fa-box-open fa-3x text-muted mb-3"></i>
                <h5>No products found</h5>
                <p class="text-muted">No products match your current filters.</p>
            </div>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table table-bordered table-hover" id="stockTable">
                    <thead class="table-light">
                        <tr>
                            <th>Product</th>
                            <th>Category</th>
                            <th>Current Stock</th>
                            <th>Status</th>
                            <th>Last Updated</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($products as $product): ?>
                            <tr class="<?= $product['stock_status'] === 'out-of-stock' ? 'table-danger' : ($product['stock_status'] === 'low-stock' ? 'table-warning' : '') ?>">
                                <td>
                                    <div class="d-flex align-items-center">
                                        <?php if ($product['image_path']): ?>
                                            <img src="../../uploads/products/<?= htmlspecialchars(basename($product['image_path'])) ?>"
                                                alt="Product" class="product-image me-2">
                                        <?php else: ?>
                                            <div class="bg-light rounded me-2 d-flex align-items-center justify-content-center product-image">
                                                <i class="fas fa-image text-muted"></i>
                                            </div>
                                        <?php endif; ?>
                                        <div class="product-info">
                                            <div class="product-name"><?= htmlspecialchars($product['name']) ?></div>
                                            <div class="product-description"><?= htmlspecialchars(substr($product['description'], 0, 50)) ?>...</div>
                                        </div>
                                    </div>
                                </td>
                                <td><?= htmlspecialchars($product['category_name'] ?? 'Uncategorized') ?></td>
                                <td>
                                    <span class="stock-quantity"><?= $product['stock'] ?></span>
                                </td>
                                <td>
                                    <?php
                                    $badge_class = match ($product['stock_status']) {
                                        'out-of-stock' => 'bg-danger',
                                        'low-stock' => 'bg-warning',
                                        default => 'bg-success'
                                    };
                                    $status_text = match ($product['stock_status']) {
                                        'out-of-stock' => 'Out of Stock',
                                        'low-stock' => 'Low Stock',
                                        default => 'In Stock'
                                    };
                                    ?>
                                    <span class="badge stock-status-badge <?= $badge_class ?>"><?= $status_text ?></span>
                                </td>
                                <td><?= date('M d, Y H:i', strtotime($product['updated_at'])) ?></td>
                                <td class="table-actions">
                                    <button class="btn btn-sm btn-primary update-stock-btn"
                                        data-product-id="<?= $product['id'] ?>"
                                        data-product-name="<?= htmlspecialchars($product['name']) ?>"
                                        data-current-stock="<?= $product['stock'] ?>">
                                        <i class="fas fa-edit"></i> Update Stock
                                    </button>
                                    <button class="btn btn-sm btn-info view-history-btn"
                                        data-product-id="<?= $product['id'] ?>"
                                        data-product-name="<?= htmlspecialchars($product['name']) ?>">
                                        <i class="fas fa-history"></i> History
                                    </button>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
</div>

<!-- Update Stock Modal -->
<div class="modal fade" id="updateStockModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Update Stock</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST" id="updateStockForm">
                <div class="modal-body">
                    <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
                    <input type="hidden" name="action" value="update_stock">
                    <input type="hidden" name="product_id" id="modal_product_id">

                    <div class="mb-3">
                        <label class="form-label">Product</label>
                        <input type="text" class="form-control" id="modal_product_name" readonly>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Current Stock</label>
                        <input type="number" class="form-control" id="modal_current_stock" readonly>
                    </div>

                    <div class="mb-3">
                        <label for="operation_type" class="form-label">Operation Type <span class="text-danger">*</span></label>
                        <select class="form-select" name="operation_type" id="operation_type" required>
                            <option value="">Select Operation</option>
                            <option value="in">Stock In (+)</option>
                            <option value="out">Stock Out (-)</option>
                            <option value="adjustment">Stock Adjustment (=)</option>
                        </select>
                    </div>

                    <div class="mb-3">
                        <label for="quantity_change" class="form-label">Quantity <span class="text-danger">*</span></label>
                        <input type="number" class="form-control" name="quantity_change" id="quantity_change"
                            min="1" required>
                        <div class="form-text" id="quantity_help"></div>
                    </div>

                    <div class="mb-3">
                        <label for="notes" class="form-label">Notes</label>
                        <textarea class="form-control" name="notes" id="notes" rows="3"
                            placeholder="Optional notes about this stock operation..."></textarea>
                    </div>

                    <div class="alert alert-info" id="preview_result" style="display: none;">
                        <strong>Preview:</strong> <span id="preview_text"></span>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary" id="submitBtn">Update Stock</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Stock History Modal -->
<div class="modal fade" id="stockHistoryModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Stock History</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div id="history_content">
                    <div class="text-center">
                        <div class="spinner-border" role="status">
                            <span class="visually-hidden">Loading...</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Toast Container -->
<div class="toast-container position-fixed top-0 end-0 p-3" style="z-index: 9999;"></div>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        // Update stock button handlers
        document.querySelectorAll('.update-stock-btn').forEach(btn => {
            btn.addEventListener('click', function() {
                const productId = this.dataset.productId;
                const productName = this.dataset.productName;
                const currentStock = this.dataset.currentStock;

                document.getElementById('modal_product_id').value = productId;
                document.getElementById('modal_product_name').value = productName;
                document.getElementById('modal_current_stock').value = currentStock;

                // Reset form
                document.getElementById('operation_type').value = '';
                document.getElementById('quantity_change').value = '';
                document.getElementById('notes').value = '';
                document.getElementById('preview_result').style.display = 'none';
                document.getElementById('quantity_help').textContent = '';

                new bootstrap.Modal(document.getElementById('updateStockModal')).show();
            });
        });

        // Operation type and quantity change handlers for preview
        const operationType = document.getElementById('operation_type');
        const quantityChange = document.getElementById('quantity_change');
        const quantityHelp = document.getElementById('quantity_help');
        const previewResult = document.getElementById('preview_result');
        const previewText = document.getElementById('preview_text');
        const submitBtn = document.getElementById('submitBtn');

        function updatePreview() {
            const operation = operationType.value;
            const quantity = parseInt(quantityChange.value) || 0;
            const currentStock = parseInt(document.getElementById('modal_current_stock').value) || 0;

            if (!operation || !quantity) {
                previewResult.style.display = 'none';
                quantityHelp.textContent = '';
                submitBtn.disabled = true;
                return;
            }

            let newStock;
            let helpText = '';

            switch (operation) {
                case 'in':
                    newStock = currentStock + quantity;
                    helpText = `Add ${quantity} units to current stock`;
                    break;
                case 'out':
                    newStock = currentStock - quantity;
                    helpText = `Remove ${quantity} units from current stock`;
                    if (newStock < 0) {
                        helpText += ' (Warning: This will result in negative stock!)';
                        quantityHelp.className = 'form-text quantity-help text-danger';
                    } else {
                        quantityHelp.className = 'form-text quantity-help text-muted';
                    }
                    break;
                case 'adjustment':
                    newStock = quantity;
                    helpText = `Set stock to exactly ${quantity} units`;
                    break;
            }

            quantityHelp.textContent = helpText;
            previewText.textContent = `Current: ${currentStock} → New: ${newStock}`;
            previewResult.style.display = 'block';

            // Change preview color based on result
            if (newStock < 0) {
                previewResult.className = 'alert alert-danger preview-alert';
                submitBtn.disabled = true;
            } else if (newStock <= 10) {
                previewResult.className = 'alert alert-warning preview-alert';
                submitBtn.disabled = false;
            } else {
                previewResult.className = 'alert alert-success preview-alert';
                submitBtn.disabled = false;
            }
        }

        operationType.addEventListener('change', updatePreview);
        quantityChange.addEventListener('input', updatePreview);

        // Form validation
        const updateStockForm = document.getElementById('updateStockForm');
        updateStockForm.addEventListener('submit', function(e) {
            const operation = operationType.value;
            const quantity = parseInt(quantityChange.value) || 0;

            if (!operation) {
                e.preventDefault();
                showNotification('Please select an operation type.', 'warning');
                operationType.focus();
                return;
            }

            if (!quantity || quantity <= 0) {
                e.preventDefault();
                showNotification('Please enter a valid quantity (must be greater than 0).', 'warning');
                quantityChange.focus();
                return;
            }

            // Additional validation for stock out
            if (operation === 'out') {
                const currentStock = parseInt(document.getElementById('modal_current_stock').value) || 0;
                if (quantity > currentStock) {
                    e.preventDefault();
                    if (!confirm(`Warning: You are trying to remove ${quantity} units but current stock is only ${currentStock}. This will result in negative stock. Continue anyway?`)) {
                        return;
                    }
                }
            }

            // Show loading state
            submitBtn.disabled = true;
            submitBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Updating...';
        });

        // Function to show notifications
        function showNotification(message, type = 'info') {
            const toastContainer = document.querySelector('.toast-container');
            if (!toastContainer) return;

            const toastId = 'toast_' + Date.now();
            const bgClass = {
                'success': 'bg-success',
                'error': 'bg-danger',
                'warning': 'bg-warning',
                'info': 'bg-info'
            }[type] || 'bg-info';

            const toast = document.createElement('div');
            toast.className = `toast ${bgClass} text-white`;
            toast.id = toastId;
            toast.innerHTML = `
                <div class="toast-header ${bgClass} text-white border-0">
                    <strong class="me-auto">Stock Management</strong>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="toast"></button>
                </div>
                <div class="toast-body">
                    ${message}
                </div>
            `;

            toastContainer.appendChild(toast);
            const bsToast = new bootstrap.Toast(toast, { delay: 5000 });
            bsToast.show();

            toast.addEventListener('hidden.bs.toast', () => {
                toast.remove();
            });
        }

        // View history button handlers
        document.querySelectorAll('.view-history-btn').forEach(btn => {
            btn.addEventListener('click', function() {
                const productId = this.dataset.productId;
                const productName = this.dataset.productName;

                document.querySelector('#stockHistoryModal .modal-title').textContent = `Stock History - ${productName}`;

                // Load history via AJAX
                loadStockHistory(productId);

                new bootstrap.Modal(document.getElementById('stockHistoryModal')).show();
            });
        });

        async function loadStockHistory(productId) {
            const historyContent = document.getElementById('history_content');
            historyContent.innerHTML = '<div class="text-center"><div class="spinner-border" role="status"><span class="visually-hidden">Loading...</span></div></div>';

            try {
                const response = await fetch('stock-history.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: `product_id=${productId}&csrf_token=<?= $_SESSION['csrf_token'] ?>`
                });

                if (response.ok) {
                    const html = await response.text();
                    historyContent.innerHTML = html;
                } else {
                    const errorText = await response.text();
                    console.error('Server error:', errorText);
                    historyContent.innerHTML = `
                        <div class="alert alert-danger">
                            <i class="fas fa-exclamation-triangle"></i>
                            Failed to load stock history. Server returned: ${response.status}
                        </div>
                    `;
                }
            } catch (error) {
                console.error('Error loading stock history:', error);
                historyContent.innerHTML = `
                    <div class="alert alert-danger">
                        <i class="fas fa-exclamation-triangle"></i>
                        Network error: ${error.message}
                    </div>
                `;
            }
        }

        // Initialize DataTable if available
        if (typeof DataTable !== 'undefined' && document.getElementById('stockTable')) {
            new DataTable('#stockTable', {
                pageLength: 25,
                order: [
                    [2, 'asc']
                ], // Sort by stock ascending
                columnDefs: [{
                        orderable: false,
                        targets: [5]
                    } // Disable sorting on actions column
                ]
            });
        }
    });
</script>

<?php include '../../includes/footer.php'; ?>