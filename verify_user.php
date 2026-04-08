<?php
session_start();
require_once '../config.php';

// Check admin authentication
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'admin') {
    header('HTTP/1.1 403 Forbidden');
    echo json_encode(['success' => false, 'message' => 'Access denied']);
    exit();
}

// Validate CSRF token
if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
    header('HTTP/1.1 403 Forbidden');
    echo json_encode(['success' => false, 'message' => 'Invalid CSRF token']);
    exit();
}

// Validate user ID
if (!isset($_POST['user_id']) || !is_numeric($_POST['user_id'])) {
    header('HTTP/1.1 400 Bad Request');
    echo json_encode(['success' => false, 'message' => 'Invalid user ID']);
    exit();
}

$user_id = (int)$_POST['user_id'];
$notes = trim($_POST['notes'] ?? '');

try {
    // Check if user exists and is a seller
    $stmt = $pdo->prepare("SELECT id, role, is_verified FROM users WHERE id = ?");
    $stmt->execute([$user_id]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$user) {
        echo json_encode(['success' => false, 'message' => 'User not found']);
        exit();
    }

    if ($user['role'] !== 'seller') {
        echo json_encode(['success' => false, 'message' => 'User is not a seller']);
        exit();
    }

    if ($user['is_verified']) {
        echo json_encode(['success' => false, 'message' => 'User is already verified']);
        exit();
    }

    // Update user verification status
    $stmt = $pdo->prepare("UPDATE users SET is_verified = 1, verified_at = NOW() WHERE id = ?");
    $success = $stmt->execute([$user_id]);

    if ($success) {
        // Log the verification (optional)
        $stmt = $pdo->prepare("INSERT INTO verification_logs (user_id, admin_id, notes) VALUES (?, ?, ?)");
        $stmt->execute([$user_id, $_SESSION['user_id'], $notes]);
        
        echo json_encode(['success' => true, 'message' => 'Seller verified successfully']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Failed to verify seller']);
    }
    
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
}