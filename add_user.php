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

// Get and validate input data
$username = trim($_POST['username'] ?? '');
$email = trim($_POST['email'] ?? '');
$phone = trim($_POST['phone'] ?? '');
$role = trim($_POST['role'] ?? '');
$password = $_POST['password'] ?? '';
$confirm_password = $_POST['confirm_password'] ?? '';
$is_active = isset($_POST['is_active']) ? 1 : 0;

// Validate required fields
if (empty($username) || empty($email) || empty($password) || empty($confirm_password) || empty($role)) {
    $_SESSION['error_message'] = 'All required fields must be filled.';
    header('Location: users.php');
    exit();
}

// Validate username format
if (!preg_match('/^[a-zA-Z0-9_]+$/', $username)) {
    $_SESSION['error_message'] = 'Username can only contain letters, numbers, and underscores.';
    header('Location: users.php');
    exit();
}

// Validate email format
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    $_SESSION['error_message'] = 'Invalid email format.';
    header('Location: users.php');
    exit();
}

// Validate password
if (strlen($password) < 8 || !preg_match('/[a-zA-Z]/', $password) || !preg_match('/[0-9]/', $password)) {
    $_SESSION['error_message'] = 'Password must be at least 8 characters long and contain both letters and numbers.';
    header('Location: users.php');
    exit();
}

if ($password !== $confirm_password) {
    $_SESSION['error_message'] = 'Passwords do not match.';
    header('Location: users.php');
    exit();
}

// Validate role
if (!in_array($role, ['buyer', 'seller', 'admin'])) {
    $_SESSION['error_message'] = 'Invalid role selected.';
    header('Location: users.php');
    exit();
}

try {
    // Check if username already exists
    $stmt = $pdo->prepare("SELECT id FROM users WHERE username = ?");
    $stmt->execute([$username]);
    if ($stmt->fetch()) {
        $_SESSION['error_message'] = 'Username already exists.';
        header('Location: users.php');
        exit();
    }

    // Check if email already exists
    $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
    $stmt->execute([$email]);
    if ($stmt->fetch()) {
        $_SESSION['error_message'] = 'Email already exists.';
        header('Location: users.php');
        exit();
    }

    // Handle profile picture upload
    $profile_picture = 'images/default-avatar.jpg';
    if (isset($_FILES['profile_picture']) && $_FILES['profile_picture']['error'] === UPLOAD_ERR_OK) {
        $file = $_FILES['profile_picture'];
        
        // Validate file type
        $allowed_types = ['image/jpeg', 'image/png', 'image/gif'];
        $file_info = finfo_open(FILEINFO_MIME_TYPE);
        $mime_type = finfo_file($file_info, $file['tmp_name']);
        finfo_close($file_info);
        
        if (!in_array($mime_type, $allowed_types)) {
            $_SESSION['error_message'] = 'Only JPG, PNG, and GIF images are allowed.';
            header('Location: users.php');
            exit();
        }
        
        // Validate file size (2MB max)
        if ($file['size'] > 2 * 1024 * 1024) {
            $_SESSION['error_message'] = 'Profile picture must be less than 2MB.';
            header('Location: users.php');
            exit();
        }
        
        // Generate unique filename
        $extension = pathinfo($file['name'], PATHINFO_EXTENSION);
        $filename = 'profile_' . time() . '_' . uniqid() . '.' . $extension;
        $upload_path = '../images/profiles/' . $filename;
        
        // Create directory if it doesn't exist
        if (!file_exists('../images/profiles/')) {
            mkdir('../images/profiles/', 0755, true);
        }
        
        // Move uploaded file
        if (move_uploaded_file($file['tmp_name'], $upload_path)) {
            $profile_picture = 'images/profiles/' . $filename;
        }
    }

    // Insert new user
    $stmt = $pdo->prepare("
        INSERT INTO users (username, email, phone, password, role, is_verified, profile_picture, created_at) 
        VALUES (?, ?, ?, ?, ?, ?, ?, NOW())
    ");
    
    $hashed_password = password_hash($password, PASSWORD_DEFAULT);
    $success = $stmt->execute([$username, $email, $phone ?: null, $hashed_password, $role, $is_active, $profile_picture]);

    if ($success) {
        $_SESSION['success_message'] = 'User created successfully.';
    } else {
        $_SESSION['error_message'] = 'Failed to create user. Please try again.';
    }

    header('Location: users.php');
    exit();

} catch (PDOException $e) {
    error_log('Database error in add_user.php: ' . $e->getMessage());
    $_SESSION['error_message'] = 'An error occurred while creating the user. Please try again.';
    header('Location: users.php');
    exit();
}