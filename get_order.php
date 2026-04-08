<?php
session_start();
require_once '../config.php';

// Check admin authentication
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'admin') {
    header('HTTP/1.1 403 Forbidden');
    echo json_encode(['success' => false, 'message' => 'Access denied']);
    exit();
}

// Validate and sanitize the order ID
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header('HTTP/1.1 400 Bad Request');
    echo json_encode(['success' => false, 'message' => 'Invalid order ID']);
    exit();
}

$order_id = (int)$_GET['id'];

try {
    // Prepare and execute the query to get order details
    $stmt = $pdo->prepare("
        SELECT 
            o.*,
            l.title as livestock_title,
            l.image_path as livestock_image,
            l.price as unit_price,
            l.quantity as livestock_quantity,
            u.username as buyer_name,
            u.email as buyer_email,
            u.phone as buyer_phone,
            s.username as seller_name,
            s.email as seller_email
        FROM orders o
        JOIN livestock l ON o.livestock_id = l.id
        JOIN users u ON o.buyer_id = u.id
        JOIN users s ON l.seller_id = s.id
        WHERE o.id = ?
    ");
    $stmt->execute([$order_id]);
    $order = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$order) {
        header('HTTP/1.1 404 Not Found');
        echo json_encode(['success' => false, 'message' => 'Order not found']);
        exit();
    }

    // Return order data as JSON
    header('Content-Type: application/json');
    echo json_encode(['success' => true, 'data' => $order]);
    
} catch (PDOException $e) {
    header('HTTP/1.1 500 Internal Server Error');
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
}