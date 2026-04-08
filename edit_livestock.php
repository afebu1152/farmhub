<?php
include 'includes/admin_nav.php';
require_once '../config.php';

// Check admin authentication
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'admin') {
    header("Location: ../login.php");
    exit();
}

// Get livestock ID from URL
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header("Location: livestock.php");
    exit();
}

$livestock_id = (int)$_GET['id'];

// Get existing livestock data
$stmt = $pdo->prepare("SELECT * FROM livestock WHERE id = ?");
$stmt->execute([$livestock_id]);
$livestock = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$livestock) {
    header("Location: livestock.php");
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
    $quantity = (int)$_POST['quantity'];
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
    $image_path = $livestock['image_path'];
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
                // Delete old image if it exists and is not default
                if ($image_path && file_exists('../' . $image_path) && $image_path !== 'images/default-livestock.jpg') {
                    unlink('../' . $image_path);
                }
                $image_path = 'images/livestock/' . $filename;
            } else {
                $errors[] = 'Failed to upload image.';
            }
        }
    }

    if (empty($errors)) {
        try {
            // Update livestock
            $stmt = $pdo->prepare("
                UPDATE livestock 
                SET title = ?, description = ?, price = ?, category_id = ?, 
                    is_available = ?, seller_id = ?, image_path = ?, is_available = ?
                WHERE id = ?
            ");
            
            $success = $stmt->execute([
                $title, 
                $description, 
                $price, 
                $category_id, 
                $quantity, 
                $seller_id, 
                $image_path, 
                $is_available,
                $livestock_id
            ]);

            if ($success) {
                $_SESSION['success_message'] = 'Livestock updated successfully!';
                header('Location: livestock.php');
                exit();
            } else {
                $errors[] = 'Failed to update livestock. Please try again.';
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
    <title>Edit Livestock - Admin Panel</title>
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
                    <h1 class="h2">Edit Livestock</h1>
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
                                               value="<?= htmlspecialchars($livestock['title']) ?>">
                                    </div>
                                    
                                    <div class="mb-3">
                                        <label for="price" class="form-label">Price (₦) *</label>
                                        <input type="number" class="form-control" id="price" name="price" required 
                                               step="0.01" min="0" value="<?= htmlspecialchars($livestock['price']) ?>">
                                    </div>
                                    
                                    <div class="mb-3">
                                        <label for="category_id" class="form-label">Category *</label>
                                        <select class="form-select" id="category_id" name="category_id" required>
                                            <option value="">Select Category</option>
                                            <?php foreach ($categories as $cat): ?>
                                                <option value="<?= $cat['id'] ?>" 
                                                    <?= $livestock['category_id'] == $cat['id'] ? 'selected' : '' ?>>
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
                                                    <?= $livestock['seller_id'] == $seller['id'] ? 'selected' : '' ?>>
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
                                               min="1" value="<?= htmlspecialchars($livestock['is_available']) ?>">
                                    </div>
                                    
                                    <div class="mb-3">
                                        <label for="image" class="form-label">Image</label>
                                        <input type="file" class="form-control" id="image" name="image" accept="image/*">
                                        <div class="form-text">Max size: 5MB. Allowed formats: JPG, PNG, GIF, WEBP</div>
                                        <?php if ($livestock['image_path']): ?>
                                            <div class="mt-2">
                                                <img src="../<?= htmlspecialchars($livestock['image_url']) ?>" 
                                                     alt="Current image" style="max-width: 200px; max-height: 150px;">
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                    
                                    <div class="mb-3">
                                        <div class="form-check form-switch">
                                            <input class="form-check-input" type="checkbox" id="is_available" name="is_available" value="1" 
                                                <?= $livestock['is_available'] ? 'checked' : '' ?>>
                                            <label class="form-check-label" for="is_available">Available for sale</label>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="mb-3">
                                <label for="description" class="form-label">Description *</label>
                                <textarea class="form-control" id="description" name="description" rows="5" required><?= htmlspecialchars($livestock['description']) ?></textarea>
                            </div>
                            
                            <button type="submit" class="btn btn-primary">Update Livestock</button>
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