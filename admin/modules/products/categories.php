<?php

declare(strict_types=1);

require_once '../../config/database.php';
require_once '../../config/auth.php';
require_once '../../includes/functions.php';

// Check authentication
requireAuth();

$db = Database::getInstance()->getConnection();
$page_title = 'Product Categories';

// Handle form submissions
if ($_POST) {
    if (!hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) {
        die('CSRF token mismatch');
    }

    $action = $_POST['action'] ?? '';

    try {
        switch ($action) {
            case 'add':
                $name = trim(filter_input(INPUT_POST, 'name', FILTER_SANITIZE_STRING));
                if (empty($name)) {
                    throw new Exception('Category name is required.');
                }

                // Check for duplicate names
                $check_stmt = $db->prepare("SELECT COUNT(*) FROM product_categories WHERE name = ?");
                $check_stmt->execute([$name]);
                if ($check_stmt->fetchColumn() > 0) {
                    throw new Exception('Category name already exists.');
                }

                $stmt = $db->prepare("INSERT INTO product_categories (name) VALUES (?)");
                $stmt->execute([$name]);
                $_SESSION['success_message'] = 'Category added successfully.';
                break;

            case 'edit':
                $id = filter_input(INPUT_POST, 'id', FILTER_VALIDATE_INT);
                $name = trim(filter_input(INPUT_POST, 'name', FILTER_SANITIZE_STRING));

                if (!$id || empty($name)) {
                    throw new Exception('Invalid category data.');
                }

                // Check for duplicate names (excluding current category)
                $check_stmt = $db->prepare("SELECT COUNT(*) FROM product_categories WHERE name = ? AND id != ?");
                $check_stmt->execute([$name, $id]);
                if ($check_stmt->fetchColumn() > 0) {
                    throw new Exception('Category name already exists.');
                }

                $stmt = $db->prepare("UPDATE product_categories SET name = ? WHERE id = ?");
                $stmt->execute([$name, $id]);
                $_SESSION['success_message'] = 'Category updated successfully.';
                break;

            case 'delete':
                $id = filter_input(INPUT_POST, 'id', FILTER_VALIDATE_INT);
                if (!$id) {
                    throw new Exception('Invalid category ID.');
                }

                // Check if category is being used by products
                $usage_stmt = $db->prepare("SELECT COUNT(*) FROM products WHERE category_id = ?");
                $usage_stmt->execute([$id]);
                $usage_count = $usage_stmt->fetchColumn();

                if ($usage_count > 0) {
                    throw new Exception("Cannot delete category. It is currently used by {$usage_count} product(s).");
                }

                $stmt = $db->prepare("DELETE FROM product_categories WHERE id = ?");
                $stmt->execute([$id]);
                $_SESSION['success_message'] = 'Category deleted successfully.';
                break;

            case 'bulk_delete':
                $selected_ids = $_POST['selected_categories'] ?? [];
                if (empty($selected_ids)) {
                    throw new Exception('No categories selected.');
                }

                // Check if any selected categories are being used
                $placeholders = str_repeat('?,', count($selected_ids) - 1) . '?';
                $usage_stmt = $db->prepare("
                    SELECT pc.name, COUNT(p.id) as product_count 
                    FROM product_categories pc 
                    LEFT JOIN products p ON pc.id = p.category_id 
                    WHERE pc.id IN ($placeholders) 
                    GROUP BY pc.id, pc.name 
                    HAVING product_count > 0
                ");
                $usage_stmt->execute($selected_ids);
                $used_categories = $usage_stmt->fetchAll();

                if (!empty($used_categories)) {
                    $used_names = array_map(function ($cat) {
                        return $cat['name'] . ' (' . $cat['product_count'] . ' products)';
                    }, $used_categories);
                    throw new Exception('Cannot delete categories that are in use: ' . implode(', ', $used_names));
                }

                $stmt = $db->prepare("DELETE FROM product_categories WHERE id IN ($placeholders)");
                $stmt->execute($selected_ids);
                $_SESSION['success_message'] = count($selected_ids) . ' categories deleted successfully.';
                break;
        }
    } catch (Exception $e) {
        $_SESSION['error_message'] = $e->getMessage();
    } catch (PDOException $e) {
        error_log($e->getMessage());
        $_SESSION['error_message'] = 'Database error occurred. Please try again.';
    }

    header('Location: categories.php');
    exit;
}

// Get all categories with product counts
$stmt = $db->query("
    SELECT pc.*, COUNT(p.id) as product_count 
    FROM product_categories pc 
    LEFT JOIN products p ON pc.id = p.category_id 
    GROUP BY pc.id, pc.name 
    ORDER BY pc.name
");
$categories = $stmt->fetchAll();

include '../../includes/header.php';
?>

<div class="container-fluid">
    <!-- Breadcrumb -->
    <div class="row">
        <div class="col-12">
            <nav aria-label="breadcrumb" class="mb-4">
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="../../products.php">Dashboard</a></li>
                    <li class="breadcrumb-item"><a href="products.php">Products</a></li>
                    <li class="breadcrumb-item active">Categories</li>
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
            <h2 class="mb-0">Product Categories</h2>
            <p class="text-muted">Manage product categories and classifications</p>
        </div>
        <div class="col-md-4 text-end">
            <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addCategoryModal">
                <i class="fas fa-plus"></i> Add Category
            </button>
            <a href="products.php" class="btn btn-outline-secondary">
                <i class="fas fa-arrow-left"></i> Back to Products
            </a>
        </div>
    </div>

    <!-- Categories Table -->
    <div class="card">
        <div class="card-header">
            <div class="row align-items-center">
                <div class="col">
                    <h5 class="mb-0">Categories (<?php echo count($categories); ?>)</h5>
                </div>
                <div class="col-auto">
                    <form method="POST" id="bulkDeleteForm" class="d-flex">
                        <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
                        <input type="hidden" name="action" value="bulk_delete">
                        <button type="submit" class="btn btn-sm btn-outline-danger" disabled id="bulkDeleteBtn">
                            <i class="fas fa-trash"></i> Delete Selected
                        </button>
                    </form>
                </div>
            </div>
        </div>
        <div class="card-body p-0">
            <?php if (empty($categories)): ?>
                <div class="text-center py-5">
                    <i class="fas fa-tags fa-3x text-muted mb-3"></i>
                    <h5>No categories found</h5>
                    <p class="text-muted">Start by adding your first product category.</p>
                    <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addCategoryModal">
                        Add Category
                    </button>
                </div>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead class="table-light">
                            <tr>
                                <th width="50">
                                    <input type="checkbox" class="form-check-input" id="selectAll">
                                </th>
                                <th>Name</th>
                                <th width="150">Products</th>
                                <th width="150">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($categories as $category): ?>
                                <tr>
                                    <td>
                                        <input type="checkbox" class="form-check-input category-checkbox"
                                            name="selected_categories[]" value="<?php echo $category['id']; ?>"
                                            <?php echo $category['product_count'] > 0 ? 'disabled title="Cannot delete: category in use"' : ''; ?>>
                                    </td>
                                    <td>
                                        <strong><?php echo htmlspecialchars($category['name']); ?></strong>
                                    </td>
                                    <td>
                                        <?php if ($category['product_count'] > 0): ?>
                                            <a href="products.php?category=<?php echo $category['id']; ?>" class="text-decoration-none">
                                                <span class="badge bg-primary"><?php echo $category['product_count']; ?> products</span>
                                            </a>
                                        <?php else: ?>
                                            <span class="text-muted">No products</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <div class="btn-group btn-group-sm">
                                            <button class="btn btn-outline-primary edit-category"
                                                data-id="<?php echo $category['id']; ?>"
                                                data-name="<?php echo htmlspecialchars($category['name']); ?>"
                                                title="Edit">
                                                <i class="fas fa-edit"></i>
                                            </button>
                                            <?php if ($category['product_count'] == 0): ?>
                                                <button class="btn btn-outline-danger delete-category"
                                                    data-id="<?php echo $category['id']; ?>"
                                                    data-name="<?php echo htmlspecialchars($category['name']); ?>"
                                                    title="Delete">
                                                    <i class="fas fa-trash"></i>
                                                </button>
                                            <?php else: ?>
                                                <button class="btn btn-outline-secondary" disabled
                                                    title="Cannot delete: category is in use">
                                                    <i class="fas fa-trash"></i>
                                                </button>
                                            <?php endif; ?>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- Add Category Modal -->
<div class="modal fade" id="addCategoryModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Add New Category</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST" id="addCategoryForm">
                <div class="modal-body">
                    <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
                    <input type="hidden" name="action" value="add">

                    <div class="mb-3">
                        <label for="add_name" class="form-label">Category Name <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="add_name" name="name" required maxlength="100">
                        <div class="form-text">Enter a unique category name</div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Add Category</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Edit Category Modal -->
<div class="modal fade" id="editCategoryModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Edit Category</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST" id="editCategoryForm">
                <div class="modal-body">
                    <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
                    <input type="hidden" name="action" value="edit">
                    <input type="hidden" name="id" id="edit_id">

                    <div class="mb-3">
                        <label for="edit_name" class="form-label">Category Name <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="edit_name" name="name" required maxlength="100">
                        <div class="form-text">Enter a unique category name</div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Update Category</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Delete Confirmation Modal -->
<div class="modal fade" id="deleteCategoryModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Confirm Delete</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <p>Are you sure you want to delete the category <strong id="delete_category_name"></strong>?</p>
                <p class="text-danger"><small>This action cannot be undone.</small></p>
            </div>
            <div class="modal-footer">
                <form method="POST" id="deleteCategoryForm">
                    <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
                    <input type="hidden" name="action" value="delete">
                    <input type="hidden" name="id" id="delete_id">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-danger">Delete Category</button>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        // Select All functionality
        const selectAll = document.getElementById('selectAll');
        const categoryCheckboxes = document.querySelectorAll('.category-checkbox:not([disabled])');
        const bulkDeleteBtn = document.getElementById('bulkDeleteBtn');
        const bulkDeleteForm = document.getElementById('bulkDeleteForm');

        selectAll?.addEventListener('change', function() {
            categoryCheckboxes.forEach(checkbox => {
                if (!checkbox.disabled) {
                    checkbox.checked = this.checked;
                }
            });
            updateBulkDeleteButton();
        });

        categoryCheckboxes.forEach(checkbox => {
            checkbox.addEventListener('change', updateBulkDeleteButton);
        });

        function updateBulkDeleteButton() {
            const checkedBoxes = document.querySelectorAll('.category-checkbox:checked:not([disabled])');
            bulkDeleteBtn.disabled = checkedBoxes.length === 0;

            // Update select all checkbox state
            if (selectAll) {
                const enabledCheckboxes = document.querySelectorAll('.category-checkbox:not([disabled])');
                selectAll.checked = checkedBoxes.length === enabledCheckboxes.length && enabledCheckboxes.length > 0;
                selectAll.indeterminate = checkedBoxes.length > 0 && checkedBoxes.length < enabledCheckboxes.length;
            }
        }

        // Bulk delete form submission
        bulkDeleteForm?.addEventListener('submit', function(e) {
            const checkedBoxes = document.querySelectorAll('.category-checkbox:checked:not([disabled])');

            if (checkedBoxes.length === 0) {
                e.preventDefault();
                return;
            }

            if (!confirm(`Are you sure you want to delete ${checkedBoxes.length} selected categories? This action cannot be undone.`)) {
                e.preventDefault();
                return;
            }

            // Add selected IDs to form
            checkedBoxes.forEach(checkbox => {
                const input = document.createElement('input');
                input.type = 'hidden';
                input.name = 'selected_categories[]';
                input.value = checkbox.value;
                this.appendChild(input);
            });
        });

        // Edit category functionality
        document.querySelectorAll('.edit-category').forEach(button => {
            button.addEventListener('click', function() {
                const categoryId = this.dataset.id;
                const categoryName = this.dataset.name;

                document.getElementById('edit_id').value = categoryId;
                document.getElementById('edit_name').value = categoryName;

                const modal = new bootstrap.Modal(document.getElementById('editCategoryModal'));
                modal.show();
            });
        });

        // Delete category functionality
        document.querySelectorAll('.delete-category').forEach(button => {
            button.addEventListener('click', function() {
                const categoryId = this.dataset.id;
                const categoryName = this.dataset.name;

                document.getElementById('delete_id').value = categoryId;
                document.getElementById('delete_category_name').textContent = categoryName;

                const modal = new bootstrap.Modal(document.getElementById('deleteCategoryModal'));
                modal.show();
            });
        });

        // Form validation
        document.getElementById('addCategoryForm')?.addEventListener('submit', function(e) {
            const name = document.getElementById('add_name').value.trim();
            if (!name) {
                e.preventDefault();
                alert('Category name is required.');
                document.getElementById('add_name').focus();
            }
        });

        document.getElementById('editCategoryForm')?.addEventListener('submit', function(e) {
            const name = document.getElementById('edit_name').value.trim();
            if (!name) {
                e.preventDefault();
                alert('Category name is required.');
                document.getElementById('edit_name').focus();
            }
        });

        // Reset forms when modals are hidden
        document.getElementById('addCategoryModal')?.addEventListener('hidden.bs.modal', function() {
            document.getElementById('addCategoryForm').reset();
        });

        document.getElementById('editCategoryModal')?.addEventListener('hidden.bs.modal', function() {
            document.getElementById('editCategoryForm').reset();
        });
    });
</script>

<?php include '../../includes/footer.php'; ?>