<?php
session_start();
require_once '../config.php';

header('Content-Type: application/json');

// Admin check
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'admin') {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit();
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request']);
    exit();
}

// Required fields
$required = ['id', 'name', 'slug'];
foreach ($required as $field) {
    if (empty($_POST[$field])) {
        echo json_encode(['success' => false, 'message' => "Missing $field"]);
        exit();
    }
}

try {
    $pdo->beginTransaction();
    
    // Handle image upload
    $image_path = null;
    if (!empty($_FILES['image']['tmp_name'])) {
        $upload_dir = '../uploads/categories/';
        if (!is_dir($upload_dir)) mkdir($upload_dir, 0755, true);
        
        $ext = strtolower(pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION));
        $allowed = ['jpg', 'jpeg', 'png', 'webp'];
        
        if (!in_array($ext, $allowed)) {
            throw new Exception('Invalid image format');
        }
        
        $new_filename = 'cat_'.$_POST['id'].'_'.time().'.'.$ext;
        $image_path = 'uploads/categories/'.$new_filename;
        
        if (!move_uploaded_file($_FILES['image']['tmp_name'], "../$image_path")) {
            throw new Exception('Failed to upload image');
        }
    }
    
    // Update query
    $stmt = $pdo->prepare("UPDATE categories SET
        name = :name,
        slug = :slug,
        description = :description,
        image_path = COALESCE(:image_path, image_path),
        is_active = :is_active,
        display_order = :display_order
        WHERE id = :id");
        
    $stmt->execute([
        ':name' => $_POST['name'],
        ':slug' => $_POST['slug'],
        ':description' => $_POST['description'] ?? null,
        ':image_path' => $image_path,
        ':is_active' => isset($_POST['is_active']) ? 1 : 0,
        ':display_order' => (int)$_POST['display_order'],
        ':id' => (int)$_POST['id']
    ]);
    
    $pdo->commit();
    echo json_encode(['success' => true]);
    
} catch (Exception $e) {
    $pdo->rollBack();
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}