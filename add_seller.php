 <?php include 'includes/admin_nav.php'; ?>
<?php

// Check admin authentication
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'admin') {
    header("Location: ../login.php");
    exit();
}

// Get all sellers
$sellers = $pdo->query("
    SELECT u.id, u.username, u.email, u.profile_picture, u.is_verified, u.created_at,
           sp.company_name, sp.nin, sp.cac_number
    FROM users u
    JOIN seller_profiles sp ON u.id = sp.user_id
    WHERE u.role = 'seller'
    ORDER BY u.created_at DESC
")->fetchAll(PDO::FETCH_ASSOC);

// Handle seller deletion
if (isset($_GET['delete']) && is_numeric($_GET['delete'])) {
    $seller_id = (int)$_GET['delete'];
    
    try {
        $pdo->beginTransaction();
        
        // Get seller info for file cleanup
        $stmt = $pdo->prepare("SELECT profile_picture FROM users WHERE id = ?");
        $stmt->execute([$seller_id]);
        $seller = $stmt->fetch();
        
        $stmt = $pdo->prepare("SELECT document_path FROM seller_profiles WHERE user_id = ?");
        $stmt->execute([$seller_id]);
        $profile = $stmt->fetch();
        
        // Delete seller profile first
        $stmt = $pdo->prepare("DELETE FROM seller_profiles WHERE user_id = ?");
        $stmt->execute([$seller_id]);
        
        // Then delete user
        $stmt = $pdo->prepare("DELETE FROM users WHERE id = ?");
        $stmt->execute([$seller_id]);
        
        $pdo->commit();
        
        // Clean up files
        if ($seller && $seller['profile_picture'] != 'default-profile.jpg' && file_exists($seller['profile_picture'])) {
            unlink($seller['profile_picture']);
        }
        if ($profile && !empty($profile['document_path']) && file_exists($profile['document_path'])) {
            unlink($profile['document_path']);
        }
        
        $_SESSION['message'] = "Seller deleted successfully!";
        header("Location: sellers.php");
        exit();
    } catch (PDOException $e) {
        $pdo->rollBack();
        $error = "Error deleting seller: " . $e->getMessage();
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Sellers - Admin Panel</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    <link rel="stylesheet" href="../css/admin.css">
    <style>
        .seller-img {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            object-fit: cover;
        }
        .verified-badge {
            color: #28a745;
            font-size: 0.8rem;
        }
    </style>
</head>
<body>
    <!-- Admin Navigation -->
   
    
    <div class="container-fluid">
        <div class="row">
            <!-- Sidebar -->
            <?php include 'includes/admin_sidebar.php'; ?>
            
            <!-- Main Content -->
            <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4">
                <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                    <h1 class="h2">Manage Sellers</h1>
                    <div class="btn-toolbar mb-2 mb-md-0">
                        <button type="button" class="btn btn-success" data-bs-toggle="modal" data-bs-target="#addSellerModal">
                            <i class="bi bi-plus-circle"></i> Add New Seller
                        </button>
                    </div>
                </div>
                
                <?php if (isset($error)): ?>
                    <div class="alert alert-danger"><?= $error ?></div>
                <?php endif; ?>
                
                <?php if (isset($_SESSION['message'])): ?>
                    <div class="alert alert-success"><?= $_SESSION['message'] ?></div>
                    <?php unset($_SESSION['message']); ?>
                <?php endif; ?>
                
                <div class="card">
                    <div class="card-header">
                        <h5 class="mb-0">All Sellers</h5>
                    </div>
                    <div class="card-body">
                        <?php if (empty($sellers)): ?>
                            <div class="alert alert-info">No sellers found.</div>
                        <?php else: ?>
                            <div class="table-responsive">
                                <table class="table table-striped table-hover">
                                    <thead>
                                        <tr>
                                            <th>ID</th>
                                            <th>Profile</th>
                                            <th>Company</th>
                                            <th>Email</th>
                                            <th>Status</th>
                                            <th>Registered</th>
                                            <th>Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($sellers as $seller): ?>
                                            <tr>
                                                <td><?= $seller['id'] ?></td>
                                                <td>
                                                    <img src="../<?= htmlspecialchars($seller['profile_picture']) ?>" 
                                                         class="seller-img me-2" 
                                                         alt="<?= htmlspecialchars($seller['username']) ?>">
                                                    <?= htmlspecialchars($seller['username']) ?>
                                                </td>
                                                <td><?= htmlspecialchars($seller['company_name']) ?></td>
                                                <td><?= htmlspecialchars($seller['email']) ?></td>
                                                <td>
                                                    <?php if ($seller['is_verified']): ?>
                                                        <span class="verified-badge">
                                                            <i class="bi bi-check-circle-fill"></i> Verified
                                                        </span>
                                                    <?php else: ?>
                                                        <span class="text-warning">
                                                            <i class="bi bi-exclamation-triangle-fill"></i> Pending
                                                        </span>
                                                    <?php endif; ?>
                                                </td>
                                                <td><?= date('M d, Y', strtotime($seller['created_at'])) ?></td>
                                                <td>
                                                    <button class="btn btn-sm btn-primary view-seller" 
                                                            data-bs-toggle="modal" 
                                                            data-bs-target="#viewSellerModal"
                                                            data-seller='<?= json_encode($seller) ?>'>
                                                        <i class="bi bi-eye"></i> View
                                                    </button>
                                                    <a href="?delete=<?= $seller['id'] ?>" 
                                                       class="btn btn-sm btn-danger"
                                                       onclick="return confirm('Are you sure you want to delete this seller?')">
                                                        <i class="bi bi-trash"></i> Delete
                                                    </a>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </main>
        </div>
    </div>

    <!-- View Seller Modal -->
    <div class="modal fade" id="viewSellerModal" tabindex="-1" aria-labelledby="viewSellerModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="viewSellerModalLabel">Seller Details</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="row">
                        <div class="col-md-4 text-center">
                            <img id="sellerProfileImg" src="" class="img-thumbnail mb-3" alt="Seller Profile" width="150">
                            <h5 id="sellerUsername" class="mb-1"></h5>
                            <p class="text-muted mb-3" id="sellerEmail"></p>
                            <div class="badge bg-success mb-3" id="sellerStatus"></div>
                        </div>
                        <div class="col-md-8">
                            <div class="mb-3">
                                <h6>Company Information</h6>
                                <hr class="mt-1">
                                <p><strong>Company Name:</strong> <span id="sellerCompany"></span></p>
                                <p><strong>NIN:</strong> <span id="sellerNIN"></span></p>
                                <p><strong>CAC Number:</strong> <span id="sellerCAC"></span></p>
                            </div>
                            <div class="mb-3">
                                <h6>Registration Details</h6>
                                <hr class="mt-1">
                                <p><strong>Registered On:</strong> <span id="sellerRegistered"></span></p>
                            </div>
                            <div>
                                <h6>Business Document</h6>
                                <hr class="mt-1">
                                <div id="sellerDocument" class="text-center">
                                    <p class="text-muted">No document uploaded</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Add Seller Modal -->
    <div class="modal fade" id="addSellerModal" tabindex="-1" aria-labelledby="addSellerModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <form method="POST" action="add_seller.php" enctype="multipart/form-data">
                    <div class="modal-header">
                        <h5 class="modal-title" id="addSellerModalLabel">Add New Seller</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label for="username" class="form-label">Username *</label>
                                <input type="text" class="form-control" id="username" name="username" required>
                            </div>
                            <div class="col-md-6">
                                <label for="email" class="form-label">Email *</label>
                                <input type="email" class="form-control" id="email" name="email" required>
                            </div>
                        </div>
                        
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label for="password" class="form-label">Password *</label>
                                <input type="password" class="form-control" id="password" name="password" minlength="8" required>
                            </div>
                            <div class="col-md-6">
                                <label for="confirm_password" class="form-label">Confirm Password *</label>
                                <input type="password" class="form-control" id="confirm_password" name="confirm_password" required>
                            </div>
                        </div>
                        
                        <div class="mb-3">
                            <label for="profile_picture" class="form-label">Profile Picture</label>
                            <input type="file" class="form-control" id="profile_picture" name="profile_picture" accept="image/*">
                            <div class="form-text">Optional. Max 2MB. JPG, PNG, or GIF.</div>
                        </div>
                        
                        <hr>
                        
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label for="company_name" class="form-label">Company Name *</label>
                                <input type="text" class="form-control" id="company_name" name="company_name" required>
                            </div>
                            <div class="col-md-6">
                                <label for="nin" class="form-label">National ID Number (NIN) *</label>
                                <input type="text" class="form-control" id="nin" name="nin" required>
                            </div>
                        </div>
                        
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label for="cac_number" class="form-label">CAC Number (if registered)</label>
                                <input type="text" class="form-control" id="cac_number" name="cac_number">
                            </div>
                            <div class="col-md-6">
                                <label for="document" class="form-label">Business Document</label>
                                <input type="file" class="form-control" id="document" name="document" 
                                       accept=".pdf,.doc,.docx,.jpg,.jpeg,.png">
                                <div class="form-text">Max 5MB. PDF, DOC, DOCX, JPG, or PNG.</div>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary">Create Seller</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // View Seller Modal
        document.querySelectorAll('.view-seller').forEach(button => {
            button.addEventListener('click', function() {
                const seller = JSON.parse(this.getAttribute('data-seller'));
                
                // Populate modal with seller data
                document.getElementById('sellerProfileImg').src = '../' + seller.profile_picture;
                document.getElementById('sellerUsername').textContent = seller.username;
                document.getElementById('sellerEmail').textContent = seller.email;
                document.getElementById('sellerCompany').textContent = seller.company_name;
                document.getElementById('sellerNIN').textContent = seller.nin;
                document.getElementById('sellerCAC').textContent = seller.cac_number || 'Not provided';
                document.getElementById('sellerRegistered').textContent = new Date(seller.created_at).toLocaleDateString('en-US', {
                    year: 'numeric',
                    month: 'long',
                    day: 'numeric'
                });
                
                // Set status badge
                const statusBadge = document.getElementById('sellerStatus');
                if (seller.is_verified) {
                    statusBadge.textContent = 'Verified';
                    statusBadge.className = 'badge bg-success mb-3';
                } else {
                    statusBadge.textContent = 'Pending Verification';
                    statusBadge.className = 'badge bg-warning mb-3';
                }
                
                // Handle document display
                const docContainer = document.getElementById('sellerDocument');
                if (seller.document_path) {
                    const ext = seller.document_path.split('.').pop().toLowerCase();
                    if (['jpg', 'jpeg', 'png', 'gif'].includes(ext)) {
                        docContainer.innerHTML = `<img src="../${seller.document_path}" class="img-fluid" alt="Business Document">`;
                    } else if (ext === 'pdf') {
                        docContainer.innerHTML = `
                            <embed src="../${seller.document_path}" type="application/pdf" width="100%" height="300px">
                            <a href="../${seller.document_path}" class="btn btn-sm btn-primary mt-2" download>
                                <i class="bi bi-download"></i> Download Document
                            </a>
                        `;
                    } else {
                        docContainer.innerHTML = `
                            <a href="../${seller.document_path}" class="btn btn-primary" download>
                                <i class="bi bi-download"></i> Download Document
                            </a>
                        `;
                    }
                } else {
                    docContainer.innerHTML = '<p class="text-muted">No document uploaded</p>';
                }
            });
        });

        // Form validation for add seller
        document.querySelector('#addSellerModal form').addEventListener('submit', function(e) {
            const password = document.getElementById('password');
            const confirmPassword = document.getElementById('confirm_password');
            
            if (password.value !== confirmPassword.value) {
                e.preventDefault();
                alert('Passwords do not match!');
                confirmPassword.focus();
            }
        });
    </script>
</body>
</html>