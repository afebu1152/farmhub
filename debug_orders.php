<?php
session_start();
require_once '../config.php';

// Check admin authentication
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'admin') {
    die("Access denied");
}

echo "<h2>Database Diagnostic</h2>";

// 1. Check if orders table exists and has data
try {
    $order_count = $pdo->query("SELECT COUNT(*) FROM orders")->fetchColumn();
    echo "<p>Total orders: $order_count</p>";
    
    if ($order_count > 0) {
        $orders = $pdo->query("SELECT * FROM orders LIMIT 5")->fetchAll(PDO::FETCH_ASSOC);
        echo "<h3>Sample Orders:</h3>";
        echo "<pre>" . htmlspecialchars(json_encode($orders, JSON_PRETTY_PRINT)) . "</pre>";
    }
} catch (PDOException $e) {
    echo "<p>Error reading orders: " . $e->getMessage() . "</p>";
}

// 2. Check livestock table
try {
    $livestock_count = $pdo->query("SELECT COUNT(*) FROM livestock")->fetchColumn();
    echo "<p>Total livestock: $livestock_count</p>";
    
    if ($livestock_count > 0) {
        $livestock = $pdo->query("SELECT id, title, seller_id FROM livestock LIMIT 5")->fetchAll(PDO::FETCH_ASSOC);
        echo "<h3>Sample Livestock:</h3>";
        echo "<pre>" . htmlspecialchars(json_encode($livestock, JSON_PRETTY_PRINT)) . "</pre>";
    }
} catch (PDOException $e) {
    echo "<p>Error reading livestock: " . $e->getMessage() . "</p>";
}

// 3. Check users table
try {
    $users_count = $pdo->query("SELECT COUNT(*) FROM users")->fetchColumn();
    echo "<p>Total users: $users_count</p>";
} catch (PDOException $e) {
    echo "<p>Error reading users: " . $e->getMessage() . "</p>";
}

// 4. Test the join query
try {
    $test_query = "
        SELECT o.*, l.title, u.username 
        FROM orders o 
        LEFT JOIN livestock l ON o.livestock_id = l.id 
        LEFT JOIN users u ON o.buyer_id = u.id 
        LIMIT 5
    ";
    
    $result = $pdo->query($test_query);
    if ($result) {
        $joined_data = $result->fetchAll(PDO::FETCH_ASSOC);
        echo "<h3>Join Test Result:</h3>";
        echo "<pre>" . htmlspecialchars(json_encode($joined_data, JSON_PRETTY_PRINT)) . "</pre>";
    } else {
        echo "<p>Join query failed</p>";
    }
} catch (PDOException $e) {
    echo "<p>Join error: " . $e->getMessage() . "</p>";
}

// 5. Check column names
try {
    $orders_columns = $pdo->query("SHOW COLUMNS FROM orders")->fetchAll(PDO::FETCH_COLUMN);
    $livestock_columns = $pdo->query("SHOW COLUMNS FROM livestock")->fetchAll(PDO::FETCH_COLUMN);
    $users_columns = $pdo->query("SHOW COLUMNS FROM users")->fetchAll(PDO::FETCH_COLUMN);
    
    echo "<h3>Orders Columns:</h3>";
    echo "<pre>" . implode("\n", $orders_columns) . "</pre>";
    
    echo "<h3>Livestock Columns:</h3>";
    echo "<pre>" . implode("\n", $livestock_columns) . "</pre>";
    
    echo "<h3>Users Columns:</h3>";
    echo "<pre>" . implode("\n", $users_columns) . "</pre>";
    
} catch (PDOException $e) {
    echo "<p>Column check error: " . $e->getMessage() . "</p>";
}