<?php
// review_seller.php
// Start session at the very top with output buffering
ob_start();
include 'includes/admin_nav.php';

include '../config.php';
// Check authentication
$is_admin = isset($_SESSION['user_id']) && isset($_SESSION['role']) && $_SESSION['role'] == 'admin';

// Check if seller ID is provided
if (!isset($_GET['id'])) {
    die("Seller ID not provided.");
}

$seller_id = (int)$_GET['id'];

// Get seller details with profile information
$stmt = $pdo->prepare("
    SELECT u.*, sp.company_name, sp.nin, sp.cac_number, sp.document_path, 
           sp.verification_status, sp.rejection_reason, sp.created_at as profile_created
    FROM users u 
    LEFT JOIN seller_profiles sp ON u.id = sp.user_id 
    WHERE u.id = ? AND u.role = 'seller'
");
$stmt->execute([$seller_id]);
$seller = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$seller) {
    die("Seller not found.");
}

// Process verification actions
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $action = $_POST['action'];
    
    if ($action == 'approve') {
        $stmt = $pdo->prepare("UPDATE users SET is_verified = 1 WHERE id = ?");
        $stmt->execute([$seller_id]);
        
        $stmt = $pdo->prepare("UPDATE seller_profiles SET verification_status = 'approved' WHERE user_id = ?");
        $stmt->execute([$seller_id]);
        
        $_SESSION['message'] = "Seller account has been approved successfully!";
        header("Location: view_seller.php");
        exit();
    } elseif ($action == 'reject') {
        $reason = $_POST['rejection_reason'];
        
        $stmt = $pdo->prepare("UPDATE seller_profiles SET verification_status = 'rejected', rejection_reason = ? WHERE user_id = ?");
        $stmt->execute([$reason, $seller_id]);
        
        $_SESSION['message'] = "Seller application has been rejected.";
        header("Location: view_seller.php");
        exit();
    }
}

// Clear output buffer
ob_end_clean();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Review Seller - Admin Panel</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    <style>
        :root {
            --primary-color: #4e73df;
            --secondary-color: #6f42c1;
            --success-color: #1cc88a;
            --danger-color: #e74a3b;
            --warning-color: #f6c23e;
        }
        
        body {
            background-color: #f8f9fc;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        
        .main-content {
           
            padding: 20px;
          
        }
        
        .card {
            border: none;
            border-radius: 10px;
            box-shadow: 0 0.15rem 1.75rem 0 rgba(58, 59, 69, 0.1);
            margin-bottom: 20px;
        }
        
        .document-viewer {
            border: 1px solid #e3e6f0;
            border-radius: 5px;
            height: 600px;
            background-color: #f8f9fc;
        }
        
        .status-badge {
            padding: 5px 10px;
            border-radius: 20px;
            font-size: 0.8rem;
            font-weight: 600;
        }
        
        .action-btn {
            padding: 10px 20px;
            font-weight: 600;
            border-radius: 8px;
            transition: all 0.3s;
        }
        
        .seller-info dt {
            font-weight: 500;
            color: #5a5c69;
        }
        
        .seller-info dd {
            margin-bottom: 15px;
        }
    </style>
</head>
<body>
    <!-- Include Navbar -->
    <?php  ?>
    
    <!-- Include Sidebar -->
    <?php  ?>

    <!-- Main Content -->
    <div class="main-content">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h1 class="h3 mb-0">Review Seller Application</h1>
            <a href="view_seller.php" class="btn btn-outline-secondary">
                <i class="bi bi-arrow-left me-1"></i> Back to Sellers
            </a>
        </div>

        <!-- Display messages -->
        <?php if (isset($_SESSION['message'])): ?>
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <?= $_SESSION['message'] ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
            <?php unset($_SESSION['message']); ?>
        <?php endif; ?>

        <!-- Authentication Error -->
        <?php if (!$is_admin): ?>
            <div class="alert alert-danger text-center">
                <h4><i class="bi bi-exclamation-triangle"></i> Access Denied</h4>
                <p>Admin privileges required to view this page.</p>
                <a href="login.php" class="btn btn-primary">Login as Admin</a>
            </div>
        <?php else: ?>

        <div class="row">
            <!-- Seller Information -->
            <div class="col-md-6">
                <div class="card mb-4">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h5 class="mb-0">Seller Information</h5>
                        <span class="status-badge 
                            <?= $seller['verification_status'] == 'approved' ? 'bg-success' : 
                               ($seller['verification_status'] == 'rejected' ? 'bg-danger' : 'bg-warning') ?>">
                            <?= ucfirst($seller['verification_status'] ?? 'pending') ?>
                        </span>
                    </div>
                    <div class="card-body">
                        <dl class="row seller-info">
                            <dt class="col-sm-4">User ID:</dt>
                            <dd class="col-sm-8">#<?= $seller['id'] ?></dd>
                            
                            <dt class="col-sm-4">Username:</dt>
                            <dd class="col-sm-8"><?= htmlspecialchars($seller['username']) ?></dd>
                            
                            <dt class="col-sm-4">Email:</dt>
                            <dd class="col-sm-8"><?= htmlspecialchars($seller['email']) ?></dd>
                            
                            <dt class="col-sm-4">Company Name:</dt>
                            <dd class="col-sm-8"><?= $seller['company_name'] ? htmlspecialchars($seller['company_name']) : 'Not provided' ?></dd>
                            
                            <dt class="col-sm-4">NIN:</dt>
                            <dd class="col-sm-8"><?= $seller['nin'] ? htmlspecialchars($seller['nin']) : 'Not provided' ?></dd>
                            
                            <dt class="col-sm-4">CAC Number:</dt>
                            <dd class="col-sm-8"><?= $seller['cac_number'] ? htmlspecialchars($seller['cac_number']) : 'Not provided' ?></dd>
                            
                            <dt class="col-sm-4">Registered On:</dt>
                            <dd class="col-sm-8"><?= date('M d, Y H:i', strtotime($seller['created_at'])) ?></dd>
                            
                            <dt class="col-sm-4">Verification Status:</dt>
                            <dd class="col-sm-8">
                                <span class="badge 
                                    <?= $seller['is_verified'] ? 'bg-success' : 'bg-warning' ?>">
                                    <?= $seller['is_verified'] ? 'Verified' : 'Pending Verification' ?>
                                </span>
                            </dd>
                            
                            <?php if ($seller['verification_status'] == 'rejected' && $seller['rejection_reason']): ?>
                                <dt class="col-sm-4">Rejection Reason:</dt>
                                <dd class="col-sm-8 text-danger"><?= htmlspecialchars($seller['rejection_reason']) ?></dd>
                            <?php endif; ?>
                        </dl>
                    </div>
                </div>
            </div>

            <!-- Document Viewer -->
            <div class="col-md-6">
                <div class="card mb-4">
                    <div class="card-header">
                        <h5 class="mb-0">Business Document</h5>
                    </div>
                    <div class="card-body">
                        <?php if ($seller['document_path'] && file_exists($seller['document_path'])): ?>
                            <div class="document-viewer mb-3">
                                <?php
                                $file_extension = strtolower(pathinfo($seller['document_path'], PATHINFO_EXTENSION));
                                if (in_array($file_extension, ['jpg', 'jpeg', 'png', 'gif'])): ?>
                                    <img src="<?= $seller['document_path'] ?>" 
                                         alt="Seller Document" 
                                         style="width: 100%; height: 100%; object-fit: contain;">
                                <?php elseif (in_array($file_extension, ['pdf','jpg', 'jpeg', 'png', 'gif'])): ?>
                                    <iframe src="../<?= $seller['document_path'] ?>#toolbar=0" 
                                            style="width: 100%; height: 100%; border: none;"></iframe>
                              
                                    <div class="d-flex justify-content-center align-items-center h-100">
                                        <div class="text-center">
                                            <i class="bi bi-file-earmark-text display-1 text-muted"></i>
                                            <h5 class="mt-3">Document Preview Not Available</h5>
                                            <p class="text-muted">Please download the document to view it.</p>
                                        </div>
                                    </div>
                                <?php endif; ?>
                            </div>
                            <div class="text-center">
                                <a href="<?= $seller['document_path'] ?>" 
                                   class="btn btn-primary" 
                                   download="seller_document_<?= $seller['id'] ?>.<?= $file_extension ?>">
                                    <i class="bi bi-download me-1"></i> Download Document
                                </a>
                            </div>
                        <?php else: ?>
                            <div class="alert alert-warning text-center py-5">
                                <i class="bi bi-exclamation-triangle display-4"></i>
                                <h5 class="mt-3">No document uploaded by seller</h5>
                                <p class="text-muted">The seller has not uploaded any verification document.</p>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>

        <!-- Review Actions -->
        <?php if (!$seller['is_verified'] && $seller['verification_status'] != 'rejected'): ?>
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0">Review Actions</h5>
            </div>
            <div class="card-body">
                <form method="POST" action="">
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <button type="submit" name="action" value="approve" class="btn btn-success w-100 action-btn">
                                <i class="bi bi-check-circle me-1"></i> Approve Seller
                            </button>
                        </div>
                        <div class="col-md-6 mb-3">
                            <button type="button" class="btn btn-danger w-100 action-btn" data-bs-toggle="modal" data-bs-target="#rejectModal">
                                <i class="bi bi-x-circle me-1"></i> Reject Application
                            </button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
        <?php elseif ($seller['verification_status'] == 'rejected'): ?>
            <div class="alert alert-info">
                <h5><i class="bi bi-info-circle me-2"></i>Application Already Reviewed</h5>
                <p>This seller application has been rejected on <?= date('M d, Y', strtotime($seller['profile_created'])) ?>.</p>
            </div>
        <?php else: ?>
            <div class="alert alert-success">
                <h5><i class="bi bi-check-circle me-2"></i>Seller Already Verified</h5>
                <p>This seller has been verified on <?= date('M d, Y', strtotime($seller['profile_created'])) ?>.</p>
            </div>
        <?php endif; ?>
        <?php endif; ?>
    </div>

    <!-- Rejection Modal -->
    <div class="modal fade" id="rejectModal" tabindex="-1" aria-labelledby="rejectModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <form method="POST" action="">
                    <div class="modal-header">
                        <h5 class="modal-title" id="rejectModalLabel">Reject Seller Application</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <div class="alert alert-warning">
                            <i class="bi bi-exclamation-triangle me-1"></i>
                            This action cannot be undone. The seller will be notified of the rejection.
                        </div>
                        <div class="mb-3">
                            <label for="rejection_reason" class="form-label">Reason for Rejection</label>
                            <textarea class="form-control" id="rejection_reason" name="rejection_reason" rows="4" 
                                      placeholder="Please provide a clear reason for rejecting this application..." required></textarea>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" name="action" value="reject" class="btn btn-danger">Confirm Rejection</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>