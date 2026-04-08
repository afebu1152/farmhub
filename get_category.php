<?php
session_start();
require_once '../config.php';

header('Content-Type: application/json');

// Admin check
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'admin') {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit();
}

// Validate ID
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    echo json_encode(['success' => false, 'message' => 'Invalid ID']);
    exit();
}

$category_id = (int)$_GET['id'];

try {
    $stmt = $pdo->prepare("SELECT 
        id, name, slug, description, image_path, 
        is_active, display_order
        FROM categories WHERE id = ?");
    $stmt->execute([$category_id]);
    
    if (!$category = $stmt->fetch(PDO::FETCH_ASSOC)) {
        throw new Exception('Category not found');
    }

    echo json_encode([
        'success' => true,
        'data' => [
            'id' => $category['id'],
            'name' => $category['name'],
            'slug' => $category['slug'],
            'description' => $category['description'] ?? '',
            'image_path' => $category['image_path'],
            'is_active' => (bool)$category['is_active'],
            'display_order' => (int)$category['display_order']
        ]
    ]);

} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}