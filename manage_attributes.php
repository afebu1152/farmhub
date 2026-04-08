<?php
include 'includes/admin_nav.php';
require_once '../config.php';

// Check admin authentication
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'admin') {
    header("Location: ../login.php");
    exit();
}

// Check if category ID is provided
if (!isset($_GET['category_id']) || !is_numeric($_GET['category_id'])) {
    $_SESSION['error'] = "Invalid category ID";
    header("Location: categories.php");
    exit();
}

$category_id = (int)$_GET['category_id'];

// Get category details
$stmt = $pdo->prepare("SELECT * FROM categories WHERE id = ?");
$stmt->execute([$category_id]);
$category = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$category) {
    $_SESSION['error'] = "Category not found";
    header("Location: categories.php");
    exit();
}

// Handle attribute addition
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['add_attribute'])) {
    $name = trim($_POST['name']);
    $type = $_POST['type'];
    $is_required = isset($_POST['is_required']) ? 1 : 0;
    $is_filterable = isset($_POST['is_filterable']) ? 1 : 0;
    $display_order = (int)$_POST['display_order'];
    
    try {
        if (empty($name)) {
            throw new Exception("Attribute name is required");
        }
        
        $stmt = $pdo->prepare("
            INSERT INTO category_attributes (category_id, name, type, is_required, is_filterable, display_order, created_at)
            VALUES (?, ?, ?, ?, ?, ?, NOW())
        ");
        $stmt->execute([$category_id, $name, $type, $is_required, $is_filterable, $display_order]);
        
        $_SESSION['message'] = "Attribute added successfully!";
        header("Location: manage_attributes.php?category_id=" . $category_id);
        exit();
        
    } catch (Exception $e) {
        $_SESSION['error'] = "Error adding attribute: " . $e->getMessage();
    }
}

// Handle attribute deletion
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['delete_attribute'])) {
    $attribute_id = (int)$_POST['attribute_id'];
    
    try {
        $stmt = $pdo->prepare("DELETE FROM category_attributes WHERE id = ? AND category_id = ?");
        $stmt->execute([$attribute_id, $category_id]);
        
        $_SESSION['message'] = "Attribute deleted successfully!";
        header("Location: manage_attributes.php?category_id=" . $category_id);
        exit();
        
    } catch (Exception $e) {
        $_SESSION['error'] = "Error deleting attribute: " . $e->getMessage();
    }
}

// Handle attribute updates
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['update_attributes'])) {
    $attributes = $_POST['attributes'];
    
    try {
        $pdo->beginTransaction();
        
        foreach ($attributes as $id => $data) {
            $is_required = isset($data['is_required']) ? 1 : 0;
            $is_filterable = isset($data['is_filterable']) ? 1 : 0;
            $display_order = (int)$data['display_order'];
            
            $stmt = $pdo->prepare("
                UPDATE category_attributes 
                SET name = ?, type = ?, is_required = ?, is_filterable = ?, display_order = ?
                WHERE id = ? AND category_id = ?
            ");
            $stmt->execute([
                trim($data['name']),
                $data['type'],
                $is_required,
                $is_filterable,
                $display_order,
                $id,
                $category_id
            ]);
        }
        
        $pdo->commit();
        $_SESSION['message'] = "Attributes updated successfully!";
        header("Location: manage_attributes.php?category_id=" . $category_id);
        exit();
        
    } catch (Exception $e) {
        $pdo->rollBack();
        $_SESSION['error'] = "Error updating attributes: " . $e->getMessage();
    }
}

// Get all attributes for this category
$attributes = $pdo->prepare("
    SELECT * FROM category_attributes 
    WHERE category_id = ? 
    ORDER BY display_order, name
");
$attributes->execute([$category_id]);
$attributes = $attributes->fetchAll(PDO::FETCH_ASSOC);

// Attribute types
$attribute_types = [
    'text' => 'Text',
    'number' => 'Number',
    'textarea' => 'Text Area',
    'select' => 'Dropdown',
    'checkbox' => 'Checkbox',
    'radio' => 'Radio Button',
    'date' => 'Date'
];
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Attributes - Admin Panel</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    <link rel="stylesheet" href="../css/admin.css">
</head>
<body>
    <?php include 'includes/admin_sidebar.php'; ?>
    
    <div class="container-fluid">
        <div class="row">
            <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4">
                <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                    <h1 class="h2">
                        Manage Attributes for: <?= htmlspecialchars($category['name']) ?>
                    </h1>
                    <div class="btn-toolbar mb-2 mb-md-0">
                        <a href="categories.php" class="btn btn-sm btn-outline-secondary">
                            <i class="bi bi-arrow-left"></i> Back to Categories
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
                
                <div class="row">
                    <div class="col-md-5">
                        <div class="card mb-4">
                            <div class="card-header">
                                <i class="bi bi-plus-circle me-2"></i> Add New Attribute
                            </div>
                            <div class="card-body">
                                <form method="POST">
                                    <input type="hidden" name="add_attribute" value="1">
                                    
                                    <div class="mb-3">
                                        <label for="name" class="form-label">Attribute Name *</label>
                                        <input type="text" class="form-control" id="name" name="name" required>
                                    </div>
                                    
                                    <div class="mb-3">
                                        <label for="type" class="form-label">Attribute Type *</label>
                                        <select class="form-select" id="type" name="type" required>
                                            <?php foreach ($attribute_types as $value => $label): ?>
                                                <option value="<?= $value ?>"><?= $label ?></option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                    
                                    <div class="row mb-3">
                                        <div class="col-md-6">
                                            <label for="display_order" class="form-label">Display Order</label>
                                            <input type="number" class="form-control" id="display_order" name="display_order" min="0" value="0">
                                        </div>
                                        <div class="col-md-6">
                                            <div class="form-check mt-4">
                                                <input class="form-check-input" type="checkbox" id="is_required" name="is_required" value="1">
                                                <label class="form-check-label" for="is_required">Required</label>
                                            </div>
                                            <div class="form-check">
                                                <input class="form-check-input" type="checkbox" id="is_filterable" name="is_filterable" value="1" checked>
                                                <label class="form-check-label" for="is_filterable">Filterable</label>
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <button type="submit" class="btn btn-primary">Add Attribute</button>
                                </form>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-md-7">
                        <div class="card">
                            <div class="card-header">
                                <i class="bi bi-list-check me-2"></i> Category Attributes
                                <span class="badge bg-primary ms-2"><?= count($attributes) ?></span>
                            </div>
                            <div class="card-body">
                                <?php if (empty($attributes)): ?>
                                    <div class="alert alert-info">No attributes found for this category.</div>
                                <?php else: ?>
                                    <form method="POST">
                                        <input type="hidden" name="update_attributes" value="1">
                                        
                                        <div class="table-responsive">
                                            <table class="table table-hover align-middle">
                                                <thead class="table-light">
                                                    <tr>
                                                        <th>Name</th>
                                                        <th>Type</th>
                                                        <th width="80">Order</th>
                                                        <th width="80">Required</th>
                                                        <th width="80">Filterable</th>
                                                        <th width="80">Actions</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    <?php foreach ($attributes as $attr): ?>
                                                        <tr>
                                                            <td>
                                                                <input type="text" class="form-control form-control-sm" 
                                                                       name="attributes[<?= $attr['id'] ?>][name]" 
                                                                       value="<?= htmlspecialchars($attr['name']) ?>" required>
                                                            </td>
                                                            <td>
                                                                <select class="form-select form-select-sm" name="attributes[<?= $attr['id'] ?>][type]">
                                                                    <?php foreach ($attribute_types as $value => $label): ?>
                                                                        <option value="<?= $value ?>" <?= $attr['type'] == $value ? 'selected' : '' ?>>
                                                                            <?= $label ?>
                                                                        </option>
                                                                    <?php endforeach; ?>
                                                                </select>
                                                            </td>
                                                            <td>
                                                                <input type="number" class="form-control form-control-sm" 
                                                                       name="attributes[<?= $attr['id'] ?>][display_order]" 
                                                                       value="<?= $attr['display_order'] ?>" min="0">
                                                            </td>
                                                            <td class="text-center">
                                                                <input type="checkbox" class="form-check-input" 
                                                                       name="attributes[<?= $attr['id'] ?>][is_required]" 
                                                                       value="1" <?= $attr['is_required'] ? 'checked' : '' ?>>
                                                            </td>
                                                            <td class="text-center">
                                                                <input type="checkbox" class="form-check-input" 
                                                                       name="attributes[<?= $attr['id'] ?>][is_filterable]" 
                                                                       value="1" <?= $attr['is_filterable'] ? 'checked' : '' ?>>
                                                            </td>
                                                            <td class="text-center">
                                                                <button type="button" class="btn btn-sm btn-outline-danger" 
                                                                        data-bs-toggle="modal" 
                                                                        data-bs-target="#deleteAttributeModal"
                                                                        data-attribute-id="<?= $attr['id'] ?>"
                                                                        data-attribute-name="<?= htmlspecialchars($attr['name']) ?>">
                                                                    <i class="bi bi-trash"></i>
                                                                </button>
                                                            </td>
                                                        </tr>
                                                    <?php endforeach; ?>
                                                </tbody>
                                            </table>
                                        </div>
                                        
                                        <div class="d-grid">
                                            <button type="submit" class="btn btn-success">Save All Changes</button>
                                        </div>
                                    </form>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>
            </main>
        </div>
    </div>

    <!-- Delete Attribute Modal -->
    <div class="modal fade" id="deleteAttributeModal" tabindex="-1" aria-labelledby="deleteAttributeModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header bg-danger text-white">
                    <h5 class="modal-title" id="deleteAttributeModalLabel">Delete Attribute</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form method="POST">
                    <input type="hidden" name="delete_attribute" value="1">
                    <input type="hidden" id="delete_attribute_id" name="attribute_id">
                    
                    <div class="modal-body">
                        <p>Are you sure you want to delete the attribute: <strong id="delete_attribute_name"></strong>?</p>
                        <p class="text-danger">This action cannot be undone.</p>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-danger">Delete Attribute</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Initialize delete modal
        const deleteModal = document.getElementById('deleteAttributeModal');
        deleteModal.addEventListener('show.bs.modal', function (event) {
            const button = event.relatedTarget;
            const attributeId = button.getAttribute('data-attribute-id');
            const attributeName = button.getAttribute('data-attribute-name');
            
            document.getElementById('delete_attribute_id').value = attributeId;
            document.getElementById('delete_attribute_name').textContent = attributeName;
        });
    </script>
</body>
</html>