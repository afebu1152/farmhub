<?php
include 'includes/admin_nav.php';
require_once '../config.php';

// Check admin authentication
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'admin') {
    header("Location: ../login.php");
    exit();
}

// Handle AJAX request for category data
if ($_SERVER['REQUEST_METHOD'] == 'GET' && isset($_GET['ajax']) && $_GET['ajax'] == 'get_category') {
    header('Content-Type: application/json');
    
    if (!isset($_GET['id'])) {
        echo json_encode(['success' => false, 'message' => 'Category ID required']);
        exit();
    }

    $category_id = (int)$_GET['id'];

    try {
        $stmt = $pdo->prepare("SELECT * FROM categories WHERE id = ?");
        $stmt->execute([$category_id]);
        $category = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($category) {
            echo json_encode([
                'success' => true,
                'id' => $category['id'],
                'name' => $category['name'],
                'slug' => $category['slug'],
                'description' => $category['description'],
                'image_path' => $category['image_path'],
                'display_order' => $category['display_order'],
                'is_active' => $category['is_active']
            ]);
        } else {
            echo json_encode(['success' => false, 'message' => 'Category not found']);
        }
    } catch (PDOException $e) {
        echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
    }
    exit();
}

// Handle category edits
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['update_category'])) {
    $category_id = (int)$_POST['category_id'];
    $name = trim($_POST['name']);
    $slug = trim($_POST['slug']);
    $description = trim($_POST['description']);
    $display_order = (int)$_POST['display_order'];
    $is_active = isset($_POST['is_active']) ? 1 : 0;
    $remove_image = isset($_POST['remove_image']) ? 1 : 0;
    
    try {
        // Check if category exists
        $stmt = $pdo->prepare("SELECT * FROM categories WHERE id = ?");
        $stmt->execute([$category_id]);
        $category = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$category) {
            $_SESSION['error'] = "Category not found!";
            header("Location: categories.php");
            exit();
        }
        
        // Handle image upload
        $image_path = $category['image_path'];
        
        if ($remove_image && $image_path) {
            // Remove current image
            if (file_exists("../$image_path")) {
                unlink("../$image_path");
            }
            $image_path = null;
        }
        
        if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
            // Remove old image if exists
            if ($image_path && file_exists("../$image_path")) {
                unlink("../$image_path");
            }
            
            // Upload new image
            $upload_dir = '../images/categories/';
            if (!file_exists($upload_dir)) {
                mkdir($upload_dir, 0777, true);
            }
            
            $file_extension = pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION);
            $filename = 'category_' . $category_id . '_' . time() . '.' . $file_extension;
            $target_path = $upload_dir . $filename;
            
            if (move_uploaded_file($_FILES['image']['tmp_name'], $target_path)) {
                $image_path = 'images/categories/' . $filename;
            } else {
                throw new Exception("Failed to upload image");
            }
        }
        
        // Update category
        $stmt = $pdo->prepare("
            UPDATE categories 
            SET name = ?, slug = ?, description = ?, image_path = ?, display_order = ?, is_active = ?, updated_at = NOW()
            WHERE id = ?
        ");
        $stmt->execute([$name, $slug, $description, $image_path, $display_order, $is_active, $category_id]);
        
        $_SESSION['message'] = "Category updated successfully!";
        header("Location: categories.php");
        exit();
        
    } catch (Exception $e) {
        $_SESSION['error'] = "Error updating category: " . $e->getMessage();
        header("Location: categories.php");
        exit();
    }
}

// Handle category status toggle
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['toggle_status'])) {
    $category_id = (int)$_POST['category_id'];
    
    try {
        $pdo->beginTransaction();
        
        // Toggle status
        $stmt = $pdo->prepare("UPDATE categories SET is_active = NOT is_active WHERE id = ?");
        $stmt->execute([$category_id]);
        
        // If deactivating, also deactivate all subcategories
        $stmt = $pdo->prepare("UPDATE categories SET is_active = 0 WHERE parent_id = ?");
        $stmt->execute([$category_id]);
        
        $pdo->commit();
        $_SESSION['message'] = "Category status updated successfully!";
        header("Location: categories.php");
        exit();
    } catch (PDOException $e) {
        $pdo->rollBack();
        $_SESSION['error'] = "Error updating category status: " . $e->getMessage();
        header("Location: categories.php");
        exit();
    }
}

// Get all categories with their attributes count and subcategories count
$categories = $pdo->query("
    SELECT c.*, 
           u.username as created_by_name,
           (SELECT COUNT(*) FROM categories sc WHERE sc.parent_id = c.id) as subcategories_count,
           (SELECT COUNT(*) FROM category_attributes ca WHERE ca.category_id = c.id) as attributes_count,
           (SELECT COUNT(*) FROM listings l WHERE l.category_id = c.id AND l.status = 'active') as listings_count
    FROM categories c
    LEFT JOIN users u ON c.created_by = u.id
    ORDER BY c.parent_id IS NULL DESC, c.name
")->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Categories - Admin Panel</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    <link rel="stylesheet" href="../css/admin.css">
    <style>
        .category-img-preview {
            max-width: 80px;
            max-height: 80px;
            border-radius: 4px;
        }
        .badge-count {
            font-size: 0.75rem;
            margin-left: 5px;
        }
        .tree-view {
            list-style: none;
            padding-left: 20px;
        }
        .tree-view li {
            position: relative;
            padding-left: 25px;
            margin-bottom: 5px;
        }
        .tree-view li:before {
            content: "";
            position: absolute;
            left: 0;
            top: 10px;
            width: 15px;
            height: 1px;
            background-color: #dee2e6;
        }
        .tree-view li:after {
            content: "";
            position: absolute;
            left: 0;
            top: 0;
            bottom: 0;
            width: 1px;
            background-color: #dee2e6;
        }
        .tree-view li:last-child:after {
            height: 10px;
        }
        .action-buttons .btn {
            padding: 0.25rem 0.5rem;
            font-size: 0.875rem;
        }
        .table-responsive {
            overflow-x: auto;
        }
        .status-toggle {
            cursor: pointer;
        }
    </style>
</head>
<body>
    <?php  ?>
    
    <div class="container-fluid">
        <div class="row">
            <?php include 'includes/admin_sidebar.php'; ?>
            
            <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4">
                <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                    <h1 class="h2">Manage Categories</h1>
                    <div class="btn-toolbar mb-2 mb-md-0">
                        <a href="add_category.php" class="btn btn-sm btn-success">
                            <i class="bi bi-plus-circle"></i> Add New Category
                        </a>
                    </div>
                </div>
                
                <?php if (isset($_SESSION['error'])): ?>
                    <div class="alert alert-danger"><?= $_SESSION['error'] ?></div>
                    <?php unset($_SESSION['error']); ?>
                <?php endif; ?>
                
                <?php if (isset($_SESSION['message'])): ?>
                    <div class="alert alert-success"><?= $_SESSION['message'] ?></div>
                    <?php unset($_SESSION['message']); ?>
                <?php endif; ?>
                
                <div class="card mb-4">
                    <div class="card-header">
                        <i class="bi bi-diagram-3 me-2"></i> Category Structure
                    </div>
                    <div class="card-body">
                        <?php if (empty($categories)): ?>
                            <div class="alert alert-info">No categories found.</div>
                        <?php else: ?>
                            <div class="table-responsive">
                                <table class="table table-hover align-middle">
                                    <thead class="table-light">
                                        <tr>
                                            <th width="50">ID</th>
                                            <th>Category Name</th>
                                            <th width="100">Image</th>
                                            <th width="120">Status</th>
                                            <th width="120">Attributes</th>
                                            <th width="120">Listings</th>
                                            <th width="120">Subcategories</th>
                                            <th width="180">Created</th>
                                            <th width="200">Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php 
                                        $parent_categories = array_filter($categories, function($cat) {
                                            return $cat['parent_id'] === null;
                                        });
                                        
                                        foreach ($parent_categories as $parent): 
                                            $subcategories = array_filter($categories, function($cat) use ($parent) {
                                                return $cat['parent_id'] == $parent['id'];
                                            });
                                        ?>
                                            <tr class="table-primary">
                                                <td><?= $parent['id'] ?></td>
                                                <td>
                                                    <strong><?= htmlspecialchars($parent['name']) ?></strong>
                                                </td>
                                                <td>
                                                    <?php if ($parent['image_path']): ?>
                                                        <img src="../<?= htmlspecialchars($parent['image_path']) ?>" 
                                                             class="category-img-preview" 
                                                             alt="<?= htmlspecialchars($parent['name']) ?>">
                                                    <?php else: ?>
                                                        <span class="text-muted">No image</span>
                                                    <?php endif; ?>
                                                </td>
                                                <td>
                                                    <form method="POST" class="d-inline">
                                                        <input type="hidden" name="category_id" value="<?= $parent['id'] ?>">
                                                        <input type="hidden" name="toggle_status" value="1">
                                                        <span class="badge rounded-pill status-toggle <?= $parent['is_active'] ? 'bg-success' : 'bg-secondary' ?>"
                                                              onclick="this.closest('form').submit()">
                                                            <?= $parent['is_active'] ? 'Active' : 'Inactive' ?>
                                                        </span>
                                                    </form>
                                                </td>
                                                <td>
                                                    <span class="badge bg-info">
                                                        <?= $parent['attributes_count'] ?>
                                                        <span class="badge-count">attrs</span>
                                                    </span>
                                                </td>
                                                <td>
                                                    <span class="badge bg-primary">
                                                        <?= $parent['listings_count'] ?>
                                                        <span class="badge-count">items</span>
                                                    </span>
                                                </td>
                                                <td>
                                                    <span class="badge bg-warning text-dark">
                                                        <?= $parent['subcategories_count'] ?>
                                                        <span class="badge-count">subcats</span>
                                                    </span>
                                                </td>
                                                <td>
                                                    <small>
                                                        <?= date('M d, Y', strtotime($parent['created_at'])) ?><br>
                                                        by <?= htmlspecialchars($parent['created_by_name'] ?? 'System') ?>
                                                    </small>
                                                </td>
                                                <td class="action-buttons">
                                                    <a href="#" class="btn btn-sm btn-outline-primary" 
                                                        data-bs-toggle="modal" 
                                                        data-bs-target="#editCategoryModal"
                                                        data-category-id="<?= $parent['id'] ?>">
                                                        <i class="bi bi-pencil"></i> Edit
                                                    </a>
                                                    <a href="manage_attributes.php?category_id=<?= $parent['id'] ?>" 
                                                       class="btn btn-sm btn-outline-info">
                                                        <i class="bi bi-tags"></i> Attributes
                                                    </a>
                                                    <a href="delete_category.php?id=<?= $parent['id'] ?>" 
                                                       class="btn btn-sm btn-outline-danger"
                                                       onclick="return confirm('Are you sure you want to delete this category and all its subcategories?')">
                                                        <i class="bi bi-trash"></i> Delete
                                                    </a>
                                                </td>
                                            </tr>
                                            
                                            <?php foreach ($subcategories as $subcategory): ?>
                                                <tr>
                                                    <td><?= $subcategory['id'] ?></td>
                                                    <td>
                                                        <i class="bi bi-arrow-return-right text-muted me-2"></i>
                                                        <?= htmlspecialchars($subcategory['name']) ?>
                                                    </td>
                                                    <td>
                                                        <?php if ($subcategory['image_path']): ?>
                                                            <img src="../<?= htmlspecialchars($subcategory['image_path']) ?>" 
                                                                 class="category-img-preview" 
                                                                 alt="<?= htmlspecialchars($subcategory['name']) ?>">
                                                        <?php else: ?>
                                                            <span class="text-muted">No image</span>
                                                        <?php endif; ?>
                                                    </td>
                                                    <td>
                                                        <form method="POST" class="d-inline">
                                                            <input type="hidden" name="category_id" value="<?= $subcategory['id'] ?>">
                                                            <input type="hidden" name="toggle_status" value="1">
                                                            <span class="badge rounded-pill status-toggle <?= $subcategory['is_active'] ? 'bg-success' : 'bg-secondary' ?>"
                                                                  onclick="this.closest('form').submit()">
                                                                <?= $subcategory['is_active'] ? 'Active' : 'Inactive' ?>
                                                            </span>
                                                        </form>
                                                    </td>
                                                    <td>
                                                        <span class="badge bg-info">
                                                            <?= $subcategory['attributes_count'] ?>
                                                            <span class="badge-count">attrs</span>
                                                        </span>
                                                    </td>
                                                    <td>
                                                        <span class="badge bg-primary">
                                                            <?= $subcategory['listings_count'] ?>
                                                            <span class="badge-count">items</span>
                                                        </span>
                                                    </td>
                                                    <td>
                                                        <span class="badge bg-light text-dark">
                                                            0
                                                            <span class="badge-count">subcats</span>
                                                        </span>
                                                    </td>
                                                    <td>
                                                        <small>
                                                            <?= date('M d, Y', strtotime($subcategory['created_at'])) ?><br>
                                                            by <?= htmlspecialchars($subcategory['created_by_name'] ?? 'System') ?>
                                                        </small>
                                                    </td>
                                                    <td class="action-buttons">
                                                        <a href="#" class="btn btn-sm btn-outline-primary" 
                                                            data-bs-toggle="modal" 
                                                            data-bs-target="#editCategoryModal"
                                                            data-category-id="<?= $subcategory['id'] ?>">
                                                            <i class="bi bi-pencil"></i> Edit
                                                        </a>
                                                        <a href="manage_attributes.php?category_id=<?= $subcategory['id'] ?>" 
                                                           class="btn btn-sm btn-outline-info">
                                                            <i class="bi bi-tags"></i> Attributes
                                                        </a>
                                                        <a href="delete_category.php?id=<?= $subcategory['id'] ?>" 
                                                           class="btn btn-sm btn-outline-danger"
                                                           onclick="return confirm('Are you sure you want to delete this subcategory?')">
                                                            <i class="bi bi-trash"></i> Delete
                                                        </a>
                                                    </td>
                                                </tr>
                                            <?php endforeach; ?>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </main>
        </div>
    </div>

<!-- Edit Category Modal -->
<div class="modal fade" id="editCategoryModal" tabindex="-1" aria-labelledby="editCategoryModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title" id="editCategoryModalLabel">Edit Category</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="editCategoryForm" method="POST" enctype="multipart/form-data">
                <div class="modal-body">
                    <input type="hidden" name="update_category" value="1">
                    <input type="hidden" id="edit_category_id" name="category_id">
                    
                    <div class="mb-3">
                        <label for="edit_name" class="form-label">Category Name *</label>
                        <input type="text" class="form-control" id="edit_name" name="name" required>
                    </div>
                    
                    <div class="mb-3">
                        <label for="edit_slug" class="form-label">URL Slug *</label>
                        <input type="text" class="form-control" id="edit_slug" name="slug" required>
                        <div class="form-text">SEO-friendly URL (lowercase, hyphens, no spaces)</div>
                    </div>
                    
                    <div class="mb-3">
                        <label for="edit_description" class="form-label">Description</label>
                        <textarea class="form-control" id="edit_description" name="description" rows="3"></textarea>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Current Image</label>
                        <div class="current-image-container mb-2">
                            <img id="edit_current_image" src="" class="img-thumbnail" style="max-height: 150px; display: none;">
                            <div id="edit_no_image" class="text-muted">No image uploaded</div>
                        </div>
                        <div class="form-check mb-2">
                            <input class="form-check-input" type="checkbox" id="edit_remove_image" name="remove_image">
                            <label class="form-check-label" for="edit_remove_image">
                                Remove current image
                            </label>
                        </div>
                        <label for="edit_image" class="form-label">New Image</label>
                        <input type="file" class="form-control" id="edit_image" name="image" accept="image/*">
                        <div class="form-text">Recommended: 500x500px, JPG/PNG/WEBP, max 2MB</div>
                        <div class="image-preview mt-2">
                            <img id="edit_image_preview" src="#" class="img-thumbnail" style="max-height: 150px; display: none;">
                        </div>
                    </div>
                    
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label for="edit_display_order" class="form-label">Display Order</label>
                            <input type="number" class="form-control" id="edit_display_order" name="display_order" min="0" value="0">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Status</label>
                            <div class="form-check form-switch">
                                <input class="form-check-input" type="checkbox" id="edit_is_active" name="is_active" value="1" checked>
                                <label class="form-check-label" for="edit_is_active">Active</label>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Save Changes</button>
                </div>
            </form>
        </div>
    </div>
</div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Function to load category data into modal
        function loadCategoryData(categoryId) {
            // Fetch category data from the same page with AJAX parameter
            fetch('categories.php?ajax=get_category&id=' + categoryId)
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        // Populate form fields
                        document.getElementById('edit_category_id').value = data.id;
                        document.getElementById('edit_name').value = data.name;
                        document.getElementById('edit_slug').value = data.slug;
                        document.getElementById('edit_description').value = data.description;
                        document.getElementById('edit_display_order').value = data.display_order;
                        document.getElementById('edit_is_active').checked = data.is_active == 1;
                        
                        // Handle image display
                        const currentImg = document.getElementById('edit_current_image');
                        const noImgMsg = document.getElementById('edit_no_image');
                        
                        if (data.image_path) {
                            currentImg.src = '../' + data.image_path;
                            currentImg.style.display = 'block';
                            noImgMsg.style.display = 'none';
                        } else {
                            currentImg.style.display = 'none';
                            noImgMsg.style.display = 'block';
                        }
                    } else {
                        alert('Error loading category data: ' + (data.message || 'Unknown error'));
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('Error loading category data. Please check console for details.');
                });
        }

        // Initialize modal when edit buttons are clicked
        document.addEventListener('DOMContentLoaded', function() {
            const editModal = document.getElementById('editCategoryModal');
            
            editModal.addEventListener('show.bs.modal', function (event) {
                const button = event.relatedTarget;
                const categoryId = button.getAttribute('data-category-id');
                loadCategoryData(categoryId);
            });

            // Image preview for new image upload
            document.getElementById('edit_image').addEventListener('change', function(e) {
                const preview = document.getElementById('edit_image_preview');
                const file = e.target.files[0];
                
                if (file) {
                    const reader = new FileReader();
                    reader.onload = function(e) {
                        preview.src = e.target.result;
                        preview.style.display = 'block';
                    }
                    reader.readAsDataURL(file);
                } else {
                    preview.style.display = 'none';
                }
            });

            // Auto-generate slug from name
            document.getElementById('edit_name').addEventListener('blur', function() {
                const name = this.value;
                const slugInput = document.getElementById('edit_slug');
                
                if (name && !slugInput.value) {
                    slugInput.value = name.toLowerCase()
                        .replace(/\s+/g, '-')     // Replace spaces with -
                        .replace(/[^\w\-]+/g, '') // Remove all non-word chars
                        .replace(/\-\-+/g, '-')   // Replace multiple - with single -
                        .replace(/^-+/, '')       // Trim - from start of text
                        .replace(/-+$/, '');      // Trim - from end of text
                }
            });
        });

        // Confirm before deleting categories with listings
        document.querySelectorAll('a[href*="delete_category.php"]').forEach(link => {
            link.addEventListener('click', function(e) {
                const listingsCount = parseInt(this.closest('tr').querySelector('.badge.bg-primary').textContent);
                if (listingsCount > 0) {
                    if (!confirm(`This category has ${listingsCount} active listings. Deleting it will remove all associated listings. Are you sure?`)) {
                        e.preventDefault();
                    }
                }
            });
        });
    </script>
</body>
</html>