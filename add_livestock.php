<?php
include 'includes/admin_nav.php';
require_once '../config.php';

// Check admin authentication
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'admin') {
    header("Location: ../login.php");
    exit();
}

// Get categories for dropdown
$categories = $pdo->query("SELECT * FROM categories ORDER BY name")->fetchAll(PDO::FETCH_ASSOC);

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = trim($_POST['title']);
    $description = trim($_POST['description']);
    $price = trim($_POST['price']);
    $category_id = (int)$_POST['category_id'];
    $quantity = (int)$_POST['is_available'];
    $seller_id = (int)$_POST['seller_id'];
    $is_available = isset($_POST['is_available']) ? 1 : 0;

    // Validate inputs
    $errors = [];
    
    if (empty($title)) {
        $errors[] = 'Title is required.';
    }
    
    if (empty($description)) {
        $errors[] = 'Description is required.';
    }
    
    if (!is_numeric($price) || $price <= 0) {
        $errors[] = 'Valid price is required.';
    }
    
    if ($category_id <= 0) {
        $errors[] = 'Please select a category.';
    }
    
    if ($quantity <= 0) {
        $errors[] = 'Valid quantity is required.';
    }
    
    if ($seller_id <= 0) {
        $errors[] = 'Please select a seller.';
    }

    // Handle image upload
    $image_path = null;
    if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
        $file = $_FILES['image'];
        
        // Validate file type
        $allowed_types = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
        $file_info = finfo_open(FILEINFO_MIME_TYPE);
        $mime_type = finfo_file($file_info, $file['tmp_name']);
        finfo_close($file_info);
        
        if (!in_array($mime_type, $allowed_types)) {
            $errors[] = 'Only JPG, PNG, GIF, and WEBP images are allowed.';
        }
        
        // Validate file size (5MB max)
        if ($file['size'] > 5 * 1024 * 1024) {
            $errors[] = 'Image must be less than 5MB.';
        }
        
        if (empty($errors)) {
            // Generate unique filename
            $extension = pathinfo($file['name'], PATHINFO_EXTENSION);
            $filename = 'livestock_' . time() . '_' . uniqid() . '.' . $extension;
            $upload_path = '../images/livestock/' . $filename;
            
            // Create directory if it doesn't exist
            if (!file_exists('../images/livestock/')) {
                mkdir('../images/livestock/', 0755, true);
            }
            
            // Move uploaded file
            if (move_uploaded_file($file['tmp_name'], $upload_path)) {
                $image_path = 'images/livestock/' . $filename;
            } else {
                $errors[] = 'Failed to upload image.';
            }
        }
    }

    if (empty($errors)) {
        try {
            // Insert new livestock
            $stmt = $pdo->prepare("
                INSERT INTO livestock (title, description, price, category_id, quantity, seller_id, image_path, is_available, posted_at) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, NOW())
            ");
            
            $success = $stmt->execute([
                $title, 
                $description, 
                $price, 
                $category_id, 
                $quantity, 
                $seller_id, 
                $image_path, 
                $is_available
            ]);

            if ($success) {
                $_SESSION['success_message'] = 'Livestock added successfully!';
                header('Location: livestock.php');
                exit();
            } else {
                $errors[] = 'Failed to add livestock. Please try again.';
            }
        } catch (PDOException $e) {
            $errors[] = 'Database error: ' . $e->getMessage();
        }
    }
}

// Get sellers for dropdown
$sellers = $pdo->query("SELECT id, username FROM users WHERE role = 'seller' ORDER BY username")->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Add Livestock - Admin Panel</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
</head>
<body>
    <!-- Navigation -->
    <?php  ?>
    
    <div class="container-fluid">
        <div class="row">
            <!-- Sidebar -->
            <?php include 'includes/admin_sidebar.php'; ?>
            
            <!-- Main Content -->
            <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4">
                <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                    <h1 class="h2">Add New Livestock</h1>
                    <div class="btn-toolbar mb-2 mb-md-0">
                        <a href="livestock.php" class="btn btn-secondary">
                            <i class="bi bi-arrow-left"></i> Back to List
                        </a>
                    </div>
                </div>

                <?php if (!empty($errors)): ?>
                    <div class="alert alert-danger">
                        <ul class="mb-0">
                            <?php foreach ($errors as $error): ?>
                                <li><?= htmlspecialchars($error) ?></li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                <?php endif; ?>

                <div class="card">
                    <div class="card-body">
                        <form method="POST" enctype="multipart/form-data">
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label for="title" class="form-label">Title *</label>
                                        <input type="text" class="form-control" id="title" name="title" required 
                                               value="<?= htmlspecialchars($_POST['title'] ?? '') ?>">
                                    </div>
                                    
                                    <div class="mb-3">
                                        <label for="price" class="form-label">Price (₦) *</label>
                                        <input type="number" class="form-control" id="price" name="price" required 
                                               step="0.01" min="0" value="<?= htmlspecialchars($_POST['price'] ?? '') ?>">
                                    </div>
                                    
                                    <div class="mb-3">
                                        <label for="category_id" class="form-label">Category *</label>
                                        <select class="form-select" id="category_id" name="category_id" required>
                                            <option value="">Select Category</option>
                                            <?php foreach ($categories as $cat): ?>
                                                <option value="<?= $cat['id'] ?>" 
                                                    <?= ($_POST['category_id'] ?? '') == $cat['id'] ? 'selected' : '' ?>>
                                                    <?= htmlspecialchars($cat['name']) ?>
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                    
                                    <div class="mb-3">
                                        <label for="seller_id" class="form-label">Seller *</label>
                                        <select class="form-select" id="seller_id" name="seller_id" required>
                                            <option value="">Select Seller</option>
                                            <?php foreach ($sellers as $seller): ?>
                                                <option value="<?= $seller['id'] ?>" 
                                                    <?= ($_POST['seller_id'] ?? '') == $seller['id'] ? 'selected' : '' ?>>
                                                    <?= htmlspecialchars($seller['username']) ?>
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                </div>
                                
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label for="quantity" class="form-label">Quantity *</label>
                                        <input type="number" class="form-control" id="quantity" name="quantity" required 
                                               min="1" value="<?= htmlspecialchars($_POST['is_available'] ?? '1') ?>">
                                    </div>
                                    
                                    <div class="mb-3">
                                        <label for="image" class="form-label">Image</label>
                                        <input type="file" class="form-control" id="image" name="image" accept="image/*">
                                        <div class="form-text">Max size: 5MB. Allowed formats: JPG, PNG, GIF, WEBP</div>
                                    </div>
                                    
                                    <div class="mb-3">
                                        <div class="form-check form-switch">
                                            <input class="form-check-input" type="checkbox" id="is_available" name="is_available" value="1" checked>
                                            <label class="form-check-label" for="is_available">Available for sale</label>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="mb-3">
                                <label for="description" class="form-label">Description *</label>
                                <textarea class="form-control" id="description" name="description" rows="5" required><?= htmlspecialchars($_POST['description'] ?? '') ?></textarea>
                            </div>
                            
                            <button type="submit" class="btn btn-primary">Add Livestock</button>
                            <a href="livestock.php" class="btn btn-secondary">Cancel</a>
                        </form>
                    </div>
                </div>
            </main>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>