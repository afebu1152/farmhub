<?php
require_once '../config.php';
session_start();

// Check admin authentication
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'admin') {
    header("Location: ../login.php");
    exit();
}

// Check if category ID is provided
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    $_SESSION['error'] = "Invalid category ID";
    header("Location: categories.php");
    exit();
}

$category_id = (int)$_GET['id'];

try {
    // Begin transaction
    $pdo->beginTransaction();
    
    // Check if category exists
    $stmt = $pdo->prepare("SELECT * FROM categories WHERE id = ?");
    $stmt->execute([$category_id]);
    $category = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$category) {
        throw new Exception("Category not found");
    }
    
    // Check if category has subcategories
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM categories WHERE parent_id = ?");
    $stmt->execute([$category_id]);
    $subcategories_count = $stmt->fetchColumn();
    
    if ($subcategories_count > 0) {
        throw new Exception("Cannot delete category with subcategories. Please delete subcategories first.");
    }
    
    // Check if category has active listings
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM livestock WHERE category_id = ?");
    $stmt->execute([$category_id]);
    $listings_count = $stmt->fetchColumn();
    
    if ($listings_count > 0) {
        // Instead of throwing an error, we'll offer to reassign listings to another category
        if (isset($_POST['confirm_delete']) && isset($_POST['reassign_category_id'])) {
            $reassign_category_id = (int)$_POST['reassign_category_id'];
            
            // Verify the reassign category exists
            $stmt = $pdo->prepare("SELECT id FROM categories WHERE id = ?");
            $stmt->execute([$reassign_category_id]);
            if (!$stmt->fetch()) {
                throw new Exception("Selected reassignment category does not exist");
            }
            
            // Reassign all listings to the new category
            $stmt = $pdo->prepare("UPDATE livestock SET category_id = ? WHERE category_id = ?");
            $stmt->execute([$reassign_category_id, $category_id]);
            
            // Delete category attributes
            $stmt = $pdo->prepare("DELETE FROM category_attributes WHERE category_id = ?");
            $stmt->execute([$category_id]);
            
            // Delete category
            $stmt = $pdo->prepare("DELETE FROM categories WHERE id = ?");
            $stmt->execute([$category_id]);
            
            // Delete category image if exists
            if ($category['image_path'] && file_exists("../" . $category['image_path'])) {
                unlink("../" . $category['image_path']);
            }
            
            $pdo->commit();
            
            $_SESSION['message'] = "Category deleted successfully! " . $listings_count . " listings were reassigned to another category.";
            header("Location: categories.php");
            exit();
        } else {
            // Show reassignment form
            $parent_categories = $pdo->query("
                SELECT id, name 
                FROM categories 
                WHERE id != $category_id AND parent_id IS NULL
                ORDER BY name
            ")->fetchAll(PDO::FETCH_ASSOC);
            
            // Display the reassignment form
            displayReassignmentForm($category, $listings_count, $parent_categories);
            exit();
        }
    } else {
        // No listings - safe to delete
        // Delete category attributes
        $stmt = $pdo->prepare("DELETE FROM category_attributes WHERE category_id = ?");
        $stmt->execute([$category_id]);
        
        // Delete category
        $stmt = $pdo->prepare("DELETE FROM categories WHERE id = ?");
        $stmt->execute([$category_id]);
        
        // Delete category image if exists
        if ($category['image_path'] && file_exists("../" . $category['image_path'])) {
            unlink("../" . $category['image_path']);
        }
        
        $pdo->commit();
        
        $_SESSION['message'] = "Category deleted successfully!";
        header("Location: categories.php");
        exit();
    }
} catch (Exception $e) {
    $pdo->rollBack();
    $_SESSION['error'] = "Error deleting category: " . $e->getMessage();
    header("Location: categories.php");
    exit();
}

function displayReassignmentForm($category, $listings_count, $parent_categories) {
    ?>
    <!DOCTYPE html>
    <html lang="en">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Reassign Listings - Admin Panel</title>
        <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
        <link rel="stylesheet" href="../css/admin.css">
    </head>
    <body>
        <div class="container mt-5">
            <div class="row justify-content-center">
                <div class="col-md-6">
                    <div class="card">
                        <div class="card-header bg-warning">
                            <h5 class="card-title mb-0">Reassign Listings Before Deletion</h5>
                        </div>
                        <div class="card-body">
                            <p>The category <strong><?= htmlspecialchars($category['name']) ?></strong> has 
                            <strong><?= $listings_count ?></strong> listings associated with it.</p>
                            
                            <p>Please select a category to reassign these listings to before deletion:</p>
                            
                            <form method="POST" action="delete_category.php?id=<?= $category['id'] ?>">
                                <input type="hidden" name="confirm_delete" value="1">
                                
                                <div class="mb-3">
                                    <label for="reassign_category_id" class="form-label">Select Category to Reassign Listings To:</label>
                                    <select class="form-select" id="reassign_category_id" name="reassign_category_id" required>
                                        <option value="">-- Select Category --</option>
                                        <?php foreach ($parent_categories as $cat): ?>
                                            <option value="<?= $cat['id'] ?>"><?= htmlspecialchars($cat['name']) ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                
                                <div class="alert alert-danger">
                                    <strong>Warning:</strong> This action cannot be undone. All listings will be moved to the selected category.
                                </div>
                                
                                <div class="d-flex justify-content-between">
                                    <a href="categories.php" class="btn btn-secondary">Cancel</a>
                                    <button type="submit" class="btn btn-danger">Confirm Deletion and Reassign Listings</button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </body>
    </html>
    <?php
}