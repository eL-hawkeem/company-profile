<?php

declare(strict_types=1);

require_once '../../config/database.php';
require_once '../../config/auth.php';
require_once '../../includes/functions.php';

// Check authentication
requireAuth();

$db = Database::getInstance()->getConnection();
$page_title = 'Products Management';

// Handle bulk actions
if ($_POST && isset($_POST['bulk_action'])) {
    if (!hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) {
        die('CSRF token mismatch');
    }

    $action = $_POST['bulk_action'];
    $selected_ids = $_POST['selected_products'] ?? [];

    if (!empty($selected_ids) && in_array($action, ['delete'])) {
        try {
            $placeholders = str_repeat('?,', count($selected_ids) - 1) . '?';

            switch ($action) {
                case 'delete':
                    $stmt = $db->prepare("DELETE FROM products WHERE id IN ($placeholders)");
                    break;
            }

            $stmt->execute($selected_ids);
            $_SESSION['success_message'] = ucfirst($action) . ' completed successfully for ' . count($selected_ids) . ' products.';
        } catch (PDOException $e) {
            error_log($e->getMessage());
            $_SESSION['error_message'] = 'Database error occurred. Please try again.';
        }
    }

    header('Location: products.php');
    exit;
}

// Pagination and search
$search = $_GET['search'] ?? '';
$category_filter = $_GET['category'] ?? '';
$page = max(1, (int)($_GET['page'] ?? 1));
$per_page = 10;
$offset = ($page - 1) * $per_page;

// Build query conditions
$where_conditions = [];
$params = [];

if (!empty($search)) {
    $where_conditions[] = "(p.name LIKE ? OR p.description LIKE ? OR p.tags LIKE ?)";
    $search_term = '%' . $search . '%';
    $params = array_merge($params, [$search_term, $search_term, $search_term]);
}

if (!empty($category_filter)) {
    $where_conditions[] = "p.category_id = ?";
    $params[] = $category_filter;
}

$where_clause = !empty($where_conditions) ? 'WHERE ' . implode(' AND ', $where_conditions) : '';

// Get total count
$count_query = "SELECT COUNT(*) FROM products p $where_clause";
$count_stmt = $db->prepare($count_query);
$count_stmt->execute($params);
$total_products = $count_stmt->fetchColumn();
$total_pages = ceil($total_products / $per_page);

// Get products with category information
$query = "SELECT p.*, pc.name as category_name 
          FROM products p 
          LEFT JOIN product_categories pc ON p.category_id = pc.id 
          $where_clause 
          ORDER BY p.created_at DESC 
          LIMIT ? OFFSET ?";

$stmt = $db->prepare($query);
$stmt->execute(array_merge($params, [$per_page, $offset]));
$products = $stmt->fetchAll();

// Get categories for filter
$categories_stmt = $db->query("SELECT id, name FROM product_categories ORDER BY name");
$categories = $categories_stmt->fetchAll();

include '../../includes/header.php';
?>

<div class="container-fluid">
    <!-- Breadcrumb -->
    <div class="row">
        <div class="col-12">
            <nav aria-label="breadcrumb" class="mb-4">
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="../../index.php">Dashboard</a></li>
                    <li class="breadcrumb-item active">Products</li>
                </ol>
            </nav>
        </div>
    </div>

    <!-- Alert Messages -->
    <?php if (isset($_SESSION['success_message'])): ?>
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <?php echo htmlspecialchars($_SESSION['success_message']);
            unset($_SESSION['success_message']); ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <?php if (isset($_SESSION['error_message'])): ?>
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <?php echo htmlspecialchars($_SESSION['error_message']);
            unset($_SESSION['error_message']); ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <!-- Header Section -->
    <div class="row mb-4">
        <div class="col-md-8">
            <h2 class="mb-0">Products Management</h2>
            <p class="text-muted">Manage your products, categories, and inventory</p>
        </div>
        <div class="col-md-4 text-end">
            <a href="add.php" class="btn btn-primary">
                <i class="fas fa-plus"></i> Add New Product
            </a>
            <a href="categories.php" class="btn btn-outline-secondary">
                <i class="fas fa-tags"></i> Categories
            </a>
        </div>
    </div>

    <!-- Filters Section -->
    <div class="card mb-4">
        <div class="card-body">
            <form method="GET" class="row g-3">
                <div class="col-md-5">
                    <label for="search" class="form-label">Search</label>
                    <input type="text" class="form-control" id="search" name="search"
                        value="<?php echo htmlspecialchars($search); ?>"
                        placeholder="Search products...">
                </div>
                <div class="col-md-5">
                    <label for="category" class="form-label">Category</label>
                    <select class="form-select" id="category" name="category">
                        <option value="">All Categories</option>
                        <?php foreach ($categories as $category): ?>
                            <option value="<?php echo $category['id']; ?>"
                                <?php echo $category_filter == $category['id'] ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($category['name']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-2 d-flex align-items-end">
                    <button type="submit" class="btn btn-outline-primary me-2">Filter</button>
                    <a href="products.php" class="btn btn-outline-secondary">Reset</a>
                </div>
            </form>
        </div>
    </div>

    <!-- Products Table -->
    <div class="card">
        <div class="card-header">
            <div class="row align-items-center">
                <div class="col">
                    <h5 class="mb-0">Products (<?php echo $total_products; ?>)</h5>
                </div>
                <div class="col-auto">
                    <form method="POST" id="bulkActionForm" class="d-flex">
                        <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
                        <select class="form-select form-select-sm me-2" name="bulk_action" required>
                            <option value="">Bulk Actions</option>
                            <option value="delete">Delete</option>
                        </select>
                        <button type="submit" class="btn btn-sm btn-outline-primary" disabled id="bulkActionBtn">
                            Apply
                        </button>
                    </form>
                </div>
            </div>
        </div>
        <div class="card-body p-0">
            <?php if (empty($products)): ?>
                <div class="text-center py-5">
                    <i class="fas fa-box-open fa-3x text-muted mb-3"></i>
                    <h5>No products found</h5>
                    <p class="text-muted">Start by adding your first product.</p>
                    <a href="add.php" class="btn btn-primary">Add Product</a>
                </div>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead class="table-light">
                            <tr>
                                <th width="50">
                                    <input type="checkbox" class="form-check-input" id="selectAll">
                                </th>
                                <th width="80">Image</th>
                                <th>Name</th>
                                <th>Category</th>
                                <th>Stock</th>
                                <th>Created</th>
                                <th width="150">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($products as $product): ?>
                                <tr>
                                    <td>
                                        <input type="checkbox" class="form-check-input product-checkbox"
                                            name="selected_products[]" value="<?php echo $product['id']; ?>">
                                    </td>
                                    <td>
                                        <?php if ($product['image_path']): ?>
                                            <img src="..\..\uploads\products\<?php echo htmlspecialchars(basename($product['image_path'])); ?>"
                                                alt="Product Image" class="img-thumbnail" style="width: 50px; height: 50px; object-fit: cover;">
                                        <?php else: ?>
                                            <div class="bg-light d-flex align-items-center justify-content-center"
                                                style="width: 50px; height: 50px; border-radius: 4px;">
                                                <i class="fas fa-image text-muted"></i>
                                            </div>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <strong><?php echo htmlspecialchars($product['name']); ?></strong>
                                        <?php if ($product['tags']): ?>
                                            <div class="small text-muted mt-1">
                                                <?php
                                                $tags = explode(',', $product['tags']);
                                                foreach (array_slice($tags, 0, 3) as $tag):
                                                ?>
                                                    <span class="badge bg-light text-dark me-1"><?php echo htmlspecialchars(trim($tag)); ?></span>
                                                <?php endforeach; ?>
                                                <?php if (count($tags) > 3): ?>
                                                    <span class="badge bg-light text-dark">+<?php echo count($tags) - 3; ?></span>
                                                <?php endif; ?>
                                            </div>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?php echo $product['category_name'] ? htmlspecialchars($product['category_name']) : '<span class="text-muted">Uncategorized</span>'; ?>
                                    </td>
                                    <td>
                                        <span class="badge <?php echo $product['stock'] > 0 ? 'bg-success' : 'bg-danger'; ?>">
                                            <?php echo $product['stock']; ?>
                                        </span>
                                    </td>
                                    <td>
                                        <small class="text-muted">
                                            <?php echo date('M j, Y', strtotime($product['created_at'])); ?>
                                        </small>
                                    </td>
                                    <td>
                                        <div class="btn-group btn-group-sm">
                                            <a href="edit.php?id=<?php echo intval($product['id']); ?>"
                                                class="btn btn-outline-primary" title="Edit">
                                                <i class="fas fa-edit"></i>
                                            </a>
                                            <a href="stock.php?id=<?php echo $product['id']; ?>"
                                                class="btn btn-outline-info" title="Manage Stock">
                                                <i class="fas fa-warehouse"></i>
                                            </a>
                                            <button class="btn btn-outline-danger delete-product"
                                                data-id="<?php echo $product['id']; ?>"
                                                data-name="<?php echo htmlspecialchars($product['name']); ?>"
                                                title="Delete">
                                                <i class="fas fa-trash"></i>
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>

                <!-- Pagination -->
                <?php if ($total_pages > 1): ?>
                    <div class="card-footer">
                        <nav aria-label="Products pagination">
                            <ul class="pagination pagination-sm justify-content-center mb-0">
                                <?php if ($page > 1): ?>
                                    <li class="page-item">
                                        <a class="page-link" href="?page=<?php echo $page - 1; ?>&search=<?php echo urlencode($search); ?>&category=<?php echo urlencode($category_filter); ?>">
                                            Previous
                                        </a>
                                    </li>
                                <?php endif; ?>

                                <?php for ($i = max(1, $page - 2); $i <= min($total_pages, $page + 2); $i++): ?>
                                    <li class="page-item <?php echo $i === $page ? 'active' : ''; ?>">
                                        <a class="page-link" href="?page=<?php echo $i; ?>&search=<?php echo urlencode($search); ?>&category=<?php echo urlencode($category_filter); ?>">
                                            <?php echo $i; ?>
                                        </a>
                                    </li>
                                <?php endfor; ?>

                                <?php if ($page < $total_pages): ?>
                                    <li class="page-item">
                                        <a class="page-link" href="?page=<?php echo $page + 1; ?>&search=<?php echo urlencode($search); ?>&category=<?php echo urlencode($category_filter); ?>">
                                            Next
                                        </a>
                                    </li>
                                <?php endif; ?>
                            </ul>
                        </nav>
                    </div>
                <?php endif; ?>
            <?php endif; ?>
        </div>
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        // Select All functionality
        const selectAll = document.getElementById('selectAll');
        const productCheckboxes = document.querySelectorAll('.product-checkbox');
        const bulkActionBtn = document.getElementById('bulkActionBtn');
        const bulkActionForm = document.getElementById('bulkActionForm');

        selectAll?.addEventListener('change', function() {
            productCheckboxes.forEach(checkbox => {
                checkbox.checked = this.checked;
            });
            updateBulkActionButton();
        });

        productCheckboxes.forEach(checkbox => {
            checkbox.addEventListener('change', updateBulkActionButton);
        });

        function updateBulkActionButton() {
            const checkedBoxes = document.querySelectorAll('.product-checkbox:checked');
            bulkActionBtn.disabled = checkedBoxes.length === 0;

            // Update select all checkbox state
            if (selectAll) {
                selectAll.checked = checkedBoxes.length === productCheckboxes.length;
                selectAll.indeterminate = checkedBoxes.length > 0 && checkedBoxes.length < productCheckboxes.length;
            }
        }

        // Bulk action form submission
        bulkActionForm?.addEventListener('submit', function(e) {
            const checkedBoxes = document.querySelectorAll('.product-checkbox:checked');
            const action = this.querySelector('[name="bulk_action"]').value;

            if (checkedBoxes.length === 0) {
                e.preventDefault();
                return;
            }

            if (action === 'delete') {
                if (!confirm(`Are you sure you want to delete ${checkedBoxes.length} selected product(s)? This action cannot be undone.`)) {
                    e.preventDefault();
                    return;
                }
            }

            // Add selected IDs to form
            checkedBoxes.forEach(checkbox => {
                const input = document.createElement('input');
                input.type = 'hidden';
                input.name = 'selected_products[]';
                input.value = checkbox.value;
                this.appendChild(input);
            });
        });

        // Delete single product
        document.querySelectorAll('.delete-product').forEach(button => {
            button.addEventListener('click', function() {
                const productId = this.dataset.id;
                const productName = this.dataset.name;

                if (confirm(`Are you sure you want to delete "${productName}"? This action cannot be undone.`)) {
                    // Create form for single delete
                    const form = document.createElement('form');
                    form.method = 'POST';
                    form.innerHTML = `
                    <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
                    <input type="hidden" name="bulk_action" value="delete">
                    <input type="hidden" name="selected_products[]" value="${productId}">
                `;
                    document.body.appendChild(form);
                    form.submit();
                }
            });
        });
    });
</script>

<?php include '../../includes/footer.php'; ?>