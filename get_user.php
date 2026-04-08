<?php
session_start();
require_once '../config.php';

// Check admin authentication
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'admin') {
    header('HTTP/1.1 403 Forbidden');
    echo json_encode(['success' => false, 'message' => 'Access denied']);
    exit();
}

// Validate and sanitize the user ID
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header('HTTP/1.1 400 Bad Request');
    echo json_encode(['success' => false, 'message' => 'Invalid user ID']);
    exit();
}

$user_id = (int)$_GET['id'];

try {
    // Prepare and execute the query
    $stmt = $pdo->prepare("
        SELECT id, username, email, phone, password, role, created_at, is_verified, profile_picture 
        FROM users 
        WHERE id = ?
    ");
    $stmt->execute([$user_id]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$user) {
        header('HTTP/1.1 404 Not Found');
        echo json_encode(['success' => false, 'message' => 'User not found']);
        exit();
    }

    // Return user data as JSON
    header('Content-Type: application/json');
    echo json_encode(['success' => true, 'data' => $user]);
    
} catch (PDOException $e) {
    header('HTTP/1.1 500 Internal Server Error');
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
}