<?php
include 'includes/admin_nav.php';
require_once '../config.php';

// Check admin authentication
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'admin') {
    header("Location: ../login.php");
    exit();
}

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Basic category data
    $name = trim(filter_input(INPUT_POST, 'name', FILTER_SANITIZE_STRING));
    $description = trim(filter_input(INPUT_POST, 'description', FILTER_SANITIZE_STRING));
    $is_active = isset($_POST['is_active']) ? 1 : 0;
    $parent_id = !empty($_POST['parent_id']) ? (int)$_POST['parent_id'] : null;
    
    // Attribute data
    $attribute_names = $_POST['attribute_name'] ?? [];
    $attribute_types = $_POST['attribute_type'] ?? [];
    $attribute_required = $_POST['attribute_required'] ?? [];
    $attribute_searchable = $_POST['attribute_searchable'] ?? [];
    $attribute_filterable = $_POST['attribute_filterable'] ?? [];
    $attribute_options = $_POST['attribute_options'] ?? [];
    
    // Validate inputs
    if (empty($name)) {
        $error = "Category name is required.";
    } else {
        // Check if category already exists
        $stmt = $pdo->prepare("SELECT id FROM categories WHERE name = ?");
        $stmt->execute([$name]);
        if ($stmt->rowCount() > 0) {
            $error = "Category name already exists.";
        }
    }
    
    // Process image upload
    $image_path = null;
    if (empty($error) && isset($_FILES['image']) && $_FILES['image']['error'] == UPLOAD_ERR_OK) {
        $upload_dir = '../uploads/categories/';
        if (!is_dir($upload_dir)) {
            mkdir($upload_dir, 0755, true);
        }
        
        $file_ext = strtolower(pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION));
        $allowed_ext = ['jpg', 'jpeg', 'png', 'gif'];
        
        if (!in_array($file_ext, $allowed_ext)) {
            $error = "Only JPG, PNG, or GIF files are allowed.";
        } elseif ($_FILES['image']['size'] > 20097152) { // 2MB
            $error = "Image must be less than 4MB.";
        } else {
            $image_name = uniqid() . '.' . $file_ext;
            $image_path = $upload_dir . $image_name;
            
            if (!move_uploaded_file($_FILES['image']['tmp_name'], $image_path)) {
                $error = "Failed to upload image.";
            }
        }
    }
    
    // Insert category if no errors
    if (empty($error)) {
        try {
            $pdo->beginTransaction();
            
            // Insert category
            $stmt = $pdo->prepare("INSERT INTO categories 
                                  (name, description, image_path, is_active, parent_id, created_by, created_at) 
                                  VALUES (?, ?, ?, ?, ?, ?, NOW())");
            $stmt->execute([
                $name,
                $description,
                $image_path,
                $is_active,
                $parent_id,
                $_SESSION['user_id']
            ]);
            
            $category_id = $pdo->lastInsertId();
            
            // Insert attributes
            foreach ($attribute_names as $index => $attr_name) {
                if (!empty($attr_name)) {
                    $stmt = $pdo->prepare("INSERT INTO category_attributes 
                                         (category_id, attribute_name, attribute_type, is_required, searchable, filterable, display_order) 
                                         VALUES (?, ?, ?, ?, ?, ?, ?)");
                    $stmt->execute([
                        $category_id,
                        $attr_name,
                        $attribute_types[$index],
                        isset($attribute_required[$index]) ? 1 : 0,
                        isset($attribute_searchable[$index]) ? 1 : 0,
                        isset($attribute_filterable[$index]) ? 1 : 0,
                        $index + 1
                    ]);
                    
                    $attribute_id = $pdo->lastInsertId();
                    
                    // Insert options for select/checkbox attributes
                    if (in_array($attribute_types[$index], ['select', 'checkbox']) && 
                        !empty($attribute_options[$index])) {
                        $options = explode("\n", $attribute_options[$index]);
                        foreach ($options as $opt_index => $option) {
                            $option = trim($option);
                            if (!empty($option)) {
                                $stmt = $pdo->prepare("INSERT INTO category_attribute_options 
                                                      (attribute_id, option_value, display_order) 
                                                      VALUES (?, ?, ?)");
                                $stmt->execute([
                                    $attribute_id,
                                    $option,
                                    $opt_index + 1
                                ]);
                            }
                        }
                    }
                }
            }
            
            $pdo->commit();
            
            $success = "Category added successfully!";
            $_POST = []; // Clear form
        } catch (PDOException $e) {
            $pdo->rollBack();
            // Clean up uploaded file if insertion failed
            if ($image_path && file_exists($image_path)) {
                unlink($image_path);
            }
            $error = "Error adding category: " . $e->getMessage();
            error_log("Category creation error: " . $e->getMessage());
        }
    }
}

// Get parent categories for dropdown
$parent_categories = $pdo->query("SELECT id, name FROM categories WHERE parent_id IS NULL AND is_active = 1 ORDER BY name")->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Add Category - Admin Panel</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    <link rel="stylesheet" href="../css/admin.css">
    <style>
        .attribute-row {
            background-color: #f8f9fa;
            padding: 15px;
            margin-bottom: 15px;
            border-radius: 5px;
            border-left: 4px solid #4e73df;
        }
        .options-textarea {
            min-height: 80px;
        }
        .btn-add-attribute {
            margin-bottom: 20px;
        }
        .attribute-type-select {
            min-width: 120px;
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
                    <h1 class="h2">Add New Category</h1>
                    <div class="btn-toolbar mb-2 mb-md-0">
                        <a href="categories.php" class="btn btn-sm btn-outline-secondary">
                            <i class="bi bi-arrow-left"></i> Back to Categories
                        </a>
                    </div>
                </div>
                
                <?php if ($error): ?>
                    <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
                <?php endif; ?>
                
                <?php if ($success): ?>
                    <div class="alert alert-success"><?= htmlspecialchars($success) ?></div>
                <?php endif; ?>
                
                <div class="card">
                    <div class="card-body">
                        <form method="POST" action="" enctype="multipart/form-data">
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label for="name" class="form-label">Category Name *</label>
                                        <input type="text" class="form-control" id="name" name="name" 
                                               value="<?= htmlspecialchars($_POST['name'] ?? '') ?>" required>
                                    </div>
                                    
                                    <div class="mb-3">
                                        <label for="parent_id" class="form-label">Parent Category</label>
                                        <select class="form-select" id="parent_id" name="parent_id">
                                            <option value="">-- No Parent (Main Category) --</option>
                                            <?php foreach ($parent_categories as $cat): ?>
                                                <option value="<?= $cat['id'] ?>" <?= isset($_POST['parent_id']) && $_POST['parent_id'] == $cat['id'] ? 'selected' : '' ?>>
                                                    <?= htmlspecialchars($cat['name']) ?>
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                    
                                    <div class="mb-3">
                                        <label for="description" class="form-label">Description</label>
                                        <textarea class="form-control" id="description" name="description" rows="3"><?= htmlspecialchars($_POST['description'] ?? '') ?></textarea>
                                    </div>
                                </div>
                                
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label for="image" class="form-label">Category Image</label>
                                        <input type="file" class="form-control" id="image" name="image" accept="image/*">
                                        <div class="form-text">Optional. Max 4MB. JPG, PNG, or GIF.</div>
                                    </div>
                                    
                                    <div class="mb-3 form-check form-switch">
                                        <input type="checkbox" class="form-check-input" id="is_active" name="is_active" 
                                               <?= isset($_POST['is_active']) ? 'checked' : 'checked' ?>>
                                        <label class="form-check-label" for="is_active">Active Category</label>
                                    </div>
                                </div>
                            </div>
                            
                            <hr class="my-4">
                            
                            <h5 class="mb-3">Category Attributes</h5>
                            <p class="text-muted">Define attributes that sellers will fill when listing items in this category.</p>
                            
                            <div id="attributes-container">
                                <!-- Attributes will be added here dynamically -->
                                <?php if (!empty($_POST['attribute_name'])): ?>
                                    <?php foreach ($_POST['attribute_name'] as $index => $attr_name): ?>
                                        <div class="attribute-row">
                                            <div class="row g-3">
                                                <div class="col-md-4">
                                                    <label class="form-label">Attribute Name *</label>
                                                    <input type="text" class="form-control" name="attribute_name[]" 
                                                           value="<?= htmlspecialchars($attr_name) ?>" required>
                                                </div>
                                                <div class="col-md-3">
                                                    <label class="form-label">Type *</label>
                                                    <select class="form-select attribute-type-select" name="attribute_type[]" required>
                                                        <option value="text" <?= ($_POST['attribute_type'][$index] ?? '') == 'text' ? 'selected' : '' ?>>Text</option>
                                                        <option value="number" <?= ($_POST['attribute_type'][$index] ?? '') == 'number' ? 'selected' : '' ?>>Number</option>
                                                        <option value="select" <?= ($_POST['attribute_type'][$index] ?? '') == 'select' ? 'selected' : '' ?>>Select</option>
                                                        <option value="checkbox" <?= ($_POST['attribute_type'][$index] ?? '') == 'checkbox' ? 'selected' : '' ?>>Checkbox</option>
                                                        <option value="range" <?= ($_POST['attribute_type'][$index] ?? '') == 'range' ? 'selected' : '' ?>>Range</option>
                                                    </select>
                                                </div>
                                                <div class="col-md-5">
                                                    <div class="form-check form-check-inline">
                                                        <input class="form-check-input" type="checkbox" name="attribute_required[]" value="<?= $index ?>" 
                                                               <?= isset($_POST['attribute_required'][$index]) ? 'checked' : '' ?>>
                                                        <label class="form-check-label">Required</label>
                                                    </div>
                                                    <div class="form-check form-check-inline">
                                                        <input class="form-check-input" type="checkbox" name="attribute_searchable[]" value="<?= $index ?>" 
                                                               <?= isset($_POST['attribute_searchable'][$index]) ? 'checked' : '' ?>>
                                                        <label class="form-check-label">Searchable</label>
                                                    </div>
                                                    <div class="form-check form-check-inline">
                                                        <input class="form-check-input" type="checkbox" name="attribute_filterable[]" value="<?= $index ?>" 
                                                               <?= isset($_POST['attribute_filterable'][$index]) ? 'checked' : '' ?>>
                                                        <label class="form-check-label">Filterable</label>
                                                    </div>
                                                    <button type="button" class="btn btn-sm btn-danger float-end remove-attribute">
                                                        <i class="bi bi-trash"></i>
                                                    </button>
                                                </div>
                                                <div class="col-12 options-field" style="display: <?= in_array($_POST['attribute_type'][$index] ?? '', ['select', 'checkbox']) ? 'block' : 'none' ?>;">
                                                    <label class="form-label">Options (one per line)</label>
                                                    <textarea class="form-control options-textarea" name="attribute_options[]"><?= 
                                                        isset($_POST['attribute_options'][$index]) ? htmlspecialchars($_POST['attribute_options'][$index]) : '' 
                                                    ?></textarea>
                                                </div>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </div>
                            
                            <button type="button" id="add-attribute" class="btn btn-outline-primary btn-add-attribute">
                                <i class="bi bi-plus-circle"></i> Add Attribute
                            </button>
                            
                            <hr class="my-4">
                            
                            <button type="submit" class="btn btn-primary">
                                <i class="bi bi-save"></i> Save Category
                            </button>
                        </form>
                    </div>
                </div>
            </main>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Add attribute button
            document.getElementById('add-attribute').addEventListener('click', function() {
                const container = document.getElementById('attributes-container');
                const index = container.children.length;
                
                const attributeRow = document.createElement('div');
                attributeRow.className = 'attribute-row';
                attributeRow.innerHTML = `
                    <div class="row g-3">
                        <div class="col-md-4">
                            <label class="form-label">Attribute Name *</label>
                            <input type="text" class="form-control" name="attribute_name[]" required>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Type *</label>
                            <select class="form-select attribute-type-select" name="attribute_type[]" required>
                                <option value="text">Text</option>
                                <option value="number">Number</option>
                                <option value="select">Select</option>
                                <option value="checkbox">Checkbox</option>
                                <option value="range">Range</option>
                            </select>
                        </div>
                        <div class="col-md-5">
                            <div class="form-check form-check-inline">
                                <input class="form-check-input" type="checkbox" name="attribute_required[]" value="${index}">
                                <label class="form-check-label">Required</label>
                            </div>
                            <div class="form-check form-check-inline">
                                <input class="form-check-input" type="checkbox" name="attribute_searchable[]" value="${index}">
                                <label class="form-check-label">Searchable</label>
                            </div>
                            <div class="form-check form-check-inline">
                                <input class="form-check-input" type="checkbox" name="attribute_filterable[]" value="${index}">
                                <label class="form-check-label">Filterable</label>
                            </div>
                            <button type="button" class="btn btn-sm btn-danger float-end remove-attribute">
                                <i class="bi bi-trash"></i>
                            </button>
                        </div>
                        <div class="col-12 options-field" style="display: none;">
                            <label class="form-label">Options (one per line)</label>
                            <textarea class="form-control options-textarea" name="attribute_options[]"></textarea>
                        </div>
                    </div>
                `;
                
                container.appendChild(attributeRow);
                
                // Add event listener for the new type select
                const typeSelect = attributeRow.querySelector('.attribute-type-select');
                typeSelect.addEventListener('change', toggleOptionsField);
                
                // Add event listener for the remove button
                attributeRow.querySelector('.remove-attribute').addEventListener('click', function() {
                    container.removeChild(attributeRow);
                });
            });
            
            // Toggle options field based on attribute type
            function toggleOptionsField(e) {
                const type = e.target.value;
                const optionsField = e.target.closest('.row').querySelector('.options-field');
                
                if (type === 'select' || type === 'checkbox') {
                    optionsField.style.display = 'block';
                } else {
                    optionsField.style.display = 'none';
                }
            }
            
            // Add event listeners to existing type selects
            document.querySelectorAll('.attribute-type-select').forEach(select => {
                select.addEventListener('change', toggleOptionsField);
            });
            
            // Add event listeners to existing remove buttons
            document.querySelectorAll('.remove-attribute').forEach(button => {
                button.addEventListener('click', function() {
                    this.closest('.attribute-row').remove();
                });
            });
        });
    </script>
</body>
</html>