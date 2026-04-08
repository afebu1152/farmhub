<?php
session_start();
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

// Get livestock data to delete image
$stmt = $pdo->prepare("SELECT image_path FROM livestock WHERE id = ?");
$stmt->execute([$livestock_id]);
$livestock = $stmt->fetch(PDO::FETCH_ASSOC);

if ($livestock) {
    try {
        // Delete livestock
        $stmt = $pdo->prepare("DELETE FROM livestock WHERE id = ?");
        $success = $stmt->execute([$livestock_id]);

        if ($success) {
            // Delete image file if it exists and is not default
            if ($livestock['image_path'] && file_exists('../' . $livestock['image_path']) && $livestock['image_path'] !== 'images/default-livestock.jpg') {
                unlink('../' . $livestock['image_path']);
            }
            
            $_SESSION['success_message'] = 'Livestock deleted successfully!';
        } else {
            $_SESSION['error_message'] = 'Failed to delete livestock.';
        }
    } catch (PDOException $e) {
        $_SESSION['error_message'] = 'Database error: ' . $e->getMessage();
    }
} else {
    $_SESSION['error_message'] = 'Livestock not found.';
}

header("Location: livestock.php");
exit();