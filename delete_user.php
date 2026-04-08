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

// Prevent admin from deleting themselves
if ($user_id === (int)$_SESSION['user_id']) {
    $_SESSION['error_message'] = 'You cannot delete your own account.';
    header('Location: users.php');
    exit();
}

try {
    // Get user profile picture for deletion
    $stmt = $pdo->prepare("SELECT profile_picture FROM users WHERE id = ?");
    $stmt->execute([$user_id]);
    $profile_picture = $stmt->fetchColumn();

    // Delete user
    $stmt = $pdo->prepare("DELETE FROM users WHERE id = ?");
    $success = $stmt->execute([$user_id]);

    if ($success && $stmt->rowCount() > 0) {
        // Delete profile picture if it's not the default
        if ($profile_picture && $profile_picture !== 'images/default-avatar.jpg' && file_exists('../' . $profile_picture)) {
            unlink('../' . $profile_picture);
        }
        
        $_SESSION['success_message'] = 'User deleted successfully.';
    } else {
        $_SESSION['error_message'] = 'User not found or could not be deleted.';
    }

    header('Location: users.php');
    exit();

} catch (PDOException $e) {
    error_log('Database error in delete_user.php: ' . $e->getMessage());
    $_SESSION['error_message'] = 'An error occurred while deleting the user. Please try again.';
    header('Location: users.php');
    exit();
}