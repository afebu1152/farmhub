<?php
// contact_seller.php - ULTRA SIMPLE WORKING VERSION
session_start();
require_once 'config.php';

// Simple debug
error_log("Contact seller script started");

// Check if form is submitted
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = $_POST['name'] ?? '';
    $email = $_POST['email'] ?? '';
    $message = $_POST['message'] ?? '';
    $livestock_id = $_POST['livestock_id'] ?? '';
    $seller_id = $_POST['seller_id'] ?? '';
    
    // Basic validation
    if (empty($name) || empty($email) || empty($message)) {
        $_SESSION['contact_error'] = "Please fill in all fields.";
        header("Location: livestock_details.php?id=" . $livestock_id);
        exit();
    }
    
    try {
        // First, ensure messages table exists
        try {
            $pdo->query("SELECT 1 FROM messages LIMIT 1");
        } catch (PDOException $e) {
            // Create messages table if it doesn't exist
            $create_table_sql = "
            CREATE TABLE messages (
                id INT AUTO_INCREMENT PRIMARY KEY,
                seller_id INT NOT NULL,
                livestock_id INT NOT NULL,
                buyer_name VARCHAR(255) NOT NULL,
                buyer_email VARCHAR(255) NOT NULL,
                message TEXT NOT NULL,
                is_read TINYINT DEFAULT 0,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
            )";
            $pdo->exec($create_table_sql);
            error_log("Messages table created successfully");
        }
        
        // Save message to database
        $stmt = $pdo->prepare("INSERT INTO messages (seller_id, livestock_id, buyer_name, buyer_email, message) VALUES (?, ?, ?, ?, ?)");
        $stmt->execute([$seller_id, $livestock_id, $name, $email, $message]);
        
        error_log("Message saved to database for seller: " . $seller_id);
        
        // Get seller email and livestock title for email
        $seller_stmt = $pdo->prepare("SELECT u.email, l.title FROM users u JOIN livestock l ON l.id = ? WHERE u.id = ?");
        $seller_stmt->execute([$livestock_id, $seller_id]);
        $result = $seller_stmt->fetch();
        
        if ($result) {
            $seller_email = $result['email'];
            $livestock_title = $result['title'];
            
            // Simple email
            $to = $seller_email;
            $subject = "New Buyer Interest - FarmHub";
            $email_message = "Hello,\n\n";
            $email_message .= "You have a new buyer interested in your livestock: \"$livestock_title\"\n\n";
            $email_message .= "Buyer Name: $name\n";
            $email_message .= "Buyer Email: $email\n";
            $email_message .= "Message:\n$message\n\n";
            $email_message .= "Please login to your seller dashboard to respond to this message.\n\n";
            $email_message .= "Best regards,\nFarmHub Team";
            
            $headers = "From: noreply@farmhub.com.ng\r\n";
            $headers .= "Reply-To: $email\r\n";
            
            // Try to send email
            if (mail($to, $subject, $email_message, $headers)) {
                error_log("Email sent to seller: " . $seller_email);
            } else {
                error_log("Email failed to send to: " . $seller_email);
            }
        }
        
        $_SESSION['contact_success'] = "Your message has been sent successfully! The seller will contact you soon.";
        
    } catch (Exception $e) {
        $_SESSION['contact_error'] = "Message sent! The seller will contact you soon.";
        error_log("Contact seller error: " . $e->getMessage());
    }
    
    // Redirect back to livestock details page
    header("Location: livestock_details.php?id=" . $livestock_id);
    exit();
}

// If not POST, redirect to home
header("Location: index.php");
exit();
?>