<?php
session_start();
require_once '../config.php';

// Check admin authentication
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'admin') {
    header('HTTP/1.1 403 Forbidden');
    echo json_encode(['success' => false, 'message' => 'Access denied']);
    exit();
}

// Validate required fields
if (!isset($_POST['order_id']) || !is_numeric($_POST['order_id'])) {
    header('HTTP/1.1 400 Bad Request');
    echo json_encode(['success' => false, 'message' => 'Invalid order ID']);
    exit();
}

if (!isset($_POST['status'])) {
    header('HTTP/1.1 400 Bad Request');
    echo json_encode(['success' => false, 'message' => 'Status is required']);
    exit();
}

$order_id = (int)$_POST['order_id'];
$status = trim($_POST['status']);
$notes = trim($_POST['notes'] ?? '');

// Validate status
$valid_statuses = ['pending', 'confirmed', 'shipped', 'delivered', 'cancelled'];
if (!in_array($status, $valid_statuses)) {
    header('HTTP/1.1 400 Bad Request');
    echo json_encode(['success' => false, 'message' => 'Invalid status']);
    exit();
}

try {
    // Check if order exists
    $stmt = $pdo->prepare("SELECT id FROM orders WHERE id = ?");
    $stmt->execute([$order_id]);
    $order = $stmt->fetch();

    if (!$order) {
        header('HTTP/1.1 404 Not Found');
        echo json_encode(['success' => false, 'message' => 'Order not found']);
        exit();
    }

    // Update order status
    $stmt = $pdo->prepare("
        UPDATE orders 
        SET status = ?, notes = ?, updated_at = NOW() 
        WHERE id = ?
    ");
    
    $success = $stmt->execute([$status, $notes, $order_id]);

    if ($success) {
        // If order is delivered, update livestock quantity
        if ($status === 'delivered') {
            // Get order details
            $stmt = $pdo->prepare("
                SELECT livestock_id, quantity 
                FROM orders 
                WHERE id = ?
            ");
            $stmt->execute([$order_id]);
            $order_details = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($order_details) {
                // Update livestock quantity
                $stmt = $pdo->prepare("
                    UPDATE livestock 
                    SET quantity = quantity - ? 
                    WHERE id = ? AND quantity >= ?
                ");
                $stmt->execute([
                    $order_details['quantity'], 
                    $order_details['livestock_id'],
                    $order_details['quantity']
                ]);
            }
        }
        
        // If order is cancelled and was previously confirmed/shipped, restore livestock quantity
        if ($status === 'cancelled') {
            // Get current status to check if we need to restore quantity
            $stmt = $pdo->prepare("SELECT status FROM orders WHERE id = ?");
            $stmt->execute([$order_id]);
            $current_status = $stmt->fetchColumn();
            
            // Restore quantity if order was previously confirmed or shipped
            if (in_array($current_status, ['confirmed', 'shipped'])) {
                $stmt = $pdo->prepare("
                    SELECT livestock_id, quantity 
                    FROM orders 
                    WHERE id = ?
                ");
                $stmt->execute([$order_id]);
                $order_details = $stmt->fetch(PDO::FETCH_ASSOC);
                
                if ($order_details) {
                    $stmt = $pdo->prepare("
                        UPDATE livestock 
                        SET quantity = quantity + ? 
                        WHERE id = ?
                    ");
                    $stmt->execute([$order_details['quantity'], $order_details['livestock_id']]);
                }
            }
        }

        echo json_encode(['success' => true, 'message' => 'Order updated successfully']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Failed to update order']);
    }
    
} catch (PDOException $e) {
    header('HTTP/1.1 500 Internal Server Error');
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
}