<?php

// Include your database configuration
require_once 'config.php';
include 'includes/nav.php';
// Check for success/error messages
$success_message = '';
$error_message = '';

if (isset($_SESSION['message'])) {
    $success_message = $_SESSION['message'];
    unset($_SESSION['message']);
}

if (isset($_SESSION['error'])) {
    $error_message = $_SESSION['error'];
    unset($_SESSION['error']);
}

// Pre-fill form if there was an error
$form_data = $_SESSION['form_data'] ?? [];
unset($_SESSION['form_data']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Contact Us - FarmHub</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/animate.css/4.1.1/animate.min.css">
    <link rel="stylesheet" href="css/style.css">

    <style>
        /* Same CSS as above, but with blue theme */
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }

        body {
            background-color: #f8f9fa;
            color: #333;
            line-height: 1.6;
        }

        .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 20px;
        }

        header {
            background: linear-gradient(135deg, #1565c0, #42a5f5);
            color: white;
            padding: 60px 0;
            text-align: center;
            border-radius: 0 0 20px 20px;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
        }

        header h1 {
            font-size: 2.8rem;
            margin-bottom: 15px;
            text-shadow: 1px 1px 3px rgba(0, 0, 0, 0.2);
        }

        header p {
            font-size: 1.2rem;
            max-width: 700px;
            margin: 0 auto;
            opacity: 0.9;
        }

        .contact-section {
            display: flex;
            flex-wrap: wrap;
            gap: 40px;
            margin: 60px 0;
        }

        .contact-info {
            flex: 1;
            min-width: 300px;
            background: white;
            padding: 30px;
            border-radius: 15px;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.05);
        }

        .contact-form {
            flex: 1;
            min-width: 300px;
            background: white;
            padding: 30px;
            border-radius: 15px;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.05);
        }

        .section-title {
            color: #1565c0;
            margin-bottom: 25px;
            padding-bottom: 10px;
            border-bottom: 2px solid #e3f2fd;
            font-size: 1.8rem;
        }

        .info-item {
            display: flex;
            align-items: flex-start;
            margin-bottom: 25px;
        }

        .info-icon {
            background: #e3f2fd;
            color: #1565c0;
            width: 50px;
            height: 50px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-right: 15px;
            font-size: 1.2rem;
        }

        .info-content h3 {
            color: #1565c0;
            margin-bottom: 5px;
        }

        .hours-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 10px;
        }

        .hours-table td {
            padding: 8px 0;
            border-bottom: 1px solid #eee;
        }

        .hours-table tr:last-child td {
            border-bottom: none;
        }

        .hours-table .day {
            font-weight: 600;
        }

        .hours-table .closed {
            color: #f44336;
        }

        .form-group {
            margin-bottom: 20px;
        }

        .form-group label {
            display: block;
            margin-bottom: 8px;
            font-weight: 600;
            color: #1565c0;
        }

        .form-control {
            width: 100%;
            padding: 12px 15px;
            border: 1px solid #ddd;
            border-radius: 8px;
            font-size: 1rem;
            transition: all 0.3s;
        }

        .form-control:focus {
            border-color: #42a5f5;
            box-shadow: 0 0 0 2px rgba(66, 165, 245, 0.2);
            outline: none;
        }

        textarea.form-control {
            min-height: 150px;
            resize: vertical;
        }

        .btn {
            background: #1565c0;
            color: white;
            border: none;
            padding: 14px 25px;
            border-radius: 8px;
            cursor: pointer;
            font-size: 1rem;
            font-weight: 600;
            transition: all 0.3s;
            display: inline-block;
            text-align: center;
        }

        .btn:hover {
            background: #0d47a1;
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
        }

        .btn i {
            margin-right: 8px;
        }

        .map-container {
            margin-top: 40px;
            border-radius: 15px;
            overflow: hidden;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.05);
        }

        .map-placeholder {
            background: #e3f2fd;
            height: 300px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #1565c0;
            font-size: 1.2rem;
        }

        footer {
            text-align: center;
            padding: 30px 0;
            margin-top: 60px;
            color: #666;
            border-top: 1px solid #eee;
        }

        .social-links {
            display: flex;
            justify-content: center;
            gap: 20px;
            margin-top: 20px;
        }

        .social-links a {
            color: #1565c0;
            font-size: 1.5rem;
            transition: all 0.3s;
        }

        .social-links a:hover {
            color: #0d47a1;
            transform: translateY(-3px);
        }

        .alert {
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 20px;
        }

        .alert-success {
            background-color: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }

        .alert-error {
            background-color: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }

        @media (max-width: 768px) {
            .contact-section {
                flex-direction: column;
            }
            
            header h1 {
                font-size: 2.2rem;
            }
            
            header p {
                font-size: 1rem;
            }
        }
    </style>
</head>
<body>
    <?php ?>
    <header>
        <div class="container">
            <h1>Contact Us</h1>
            <p>We'd love to hear from you! Whether you're a farmer looking to trade your products, a customer searching for fresh produce, or simply curious about our farm, our doors are always open.</p>
        </div>
    </header>

    <div class="container">
        <?php if ($success_message): ?>
            <div class="alert alert-success">
                <?php echo htmlspecialchars($success_message); ?>
            </div>
        <?php endif; ?>
        
        <?php if ($error_message): ?>
            <div class="alert alert-error">
                <?php echo htmlspecialchars($error_message); ?>
            </div>
        <?php endif; ?>
        
        <div class="contact-section">
            <div class="contact-info">
                <h2 class="section-title">Get In Touch</h2>
                
                <div class="info-item">
                    <div class="info-icon">
                        <i class="fas fa-map-marker-alt"></i>
                    </div>
                    <div class="info-content">
                        <h3>Address</h3>
                        <p>FarmHub<br>Beside NNPC Filling Station, Arab Road, Kubwa, Abuja.</p>
                    </div>
                </div>
                
                <div class="info-item">
                    <div class="info-icon">
                        <i class="fas fa-phone"></i>
                    </div>
                    <div class="info-content">
                        <h3>Phone</h3>
                        <p>+234 8152703310</p>
                    </div>
                </div>
                
                <div class="info-item">
                    <div class="info-icon">
                        <i class="fas fa-envelope"></i>
                    </div>
                    <div class="info-content">
                        <h3>Email</h3>
                        <p>info@farmhub.com</p>
                    </div>
                </div>
                
                <div class="info-item">
                    <div class="info-icon">
                        <i class="fas fa-clock"></i>
                    </div>
                    <div class="info-content">
                        <h3>Opening Hours</h3>
                        <table class="hours-table">
                            <tr>
                                <td class="day">Monday – Saturday</td>
                                <td>8:00 AM – 6:00 PM</td>
                            </tr>
                            <tr>
                                <td class="day">Sunday</td>
                                <td class="closed">Closed</td>
                            </tr>
                        </table>
                    </div>
                </div>
            </div>
            
            <div class="contact-form">
                <h2 class="section-title">Send Us a Message</h2>
                <form id="contactForm" action="process_contact.php" method="POST">
                    <div class="form-group">
                        <label for="name">Your Name</label>
                        <input type="text" id="name" name="name" class="form-control" 
                               placeholder="Enter your name" required 
                               value="<?php echo htmlspecialchars($form_data['name'] ?? ''); ?>">
                    </div>
                    
                    <div class="form-group">
                        <label for="email">Email Address</label>
                        <input type="email" id="email" name="email" class="form-control" 
                               placeholder="Enter your email" required
                               value="<?php echo htmlspecialchars($form_data['email'] ?? ''); ?>">
                    </div>
                    
                    <div class="form-group">
                        <label for="phone">Phone Number</label>
                        <input type="tel" id="phone" name="phone" class="form-control" 
                               placeholder="Enter your phone number"
                               value="<?php echo htmlspecialchars($form_data['phone'] ?? ''); ?>">
                    </div>
                    
                    <div class="form-group">
                        <label for="subject">Subject</label>
                        <input type="text" id="subject" name="subject" class="form-control" 
                               placeholder="What is this regarding?" required
                               value="<?php echo htmlspecialchars($form_data['subject'] ?? ''); ?>">
                    </div>
                    
                    <div class="form-group">
                        <label for="message">Your Message</label>
                        <textarea id="message" name="message" class="form-control" 
                                  placeholder="How can we help you?" required><?php echo htmlspecialchars($form_data['message'] ?? ''); ?></textarea>
                    </div>
                    
                    <button type="submit" class="btn"><i class="fas fa-paper-plane"></i> Send Message</button>
                </form>
            </div>
        </div>
        
        <div class="map-container">
            <div class="map-placeholder">
                <i class="fas fa-map-marked-alt"></i> Map Location: FarmHub, Beside NNPC Filling Station, Arab Road, Kubwa, Abuja
            </div>
        </div>
    </div>

    <footer>
        <div class="container">
            <p>&copy; 2023 FarmHub. All rights reserved.</p>
            <div class="social-links">
                <a href="#"><i class="fab fa-face