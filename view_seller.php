<?php
// view_seller.php
// Start session at the very top with output buffering
ob_start();


include '../config.php';
session_start();
// Check authentication without causing redirect loops
$is_admin = isset($_SESSION['user_id']) && isset($_SESSION['role']) && $_SESSION['role'] == 'admin';

// If not admin, show error message instead of redirecting
if (!$is_admin) {
    $auth_error = "Access denied. Admin privileges required.";
} else {
    // Process any actions
    if (isset($_GET['action']) && isset($_GET['id'])) {
        $action = $_GET['action'];
        $seller_id = (int)$_GET['id'];
        
        if ($action == 'delete') {
            $stmt = $pdo->prepare("DELETE FROM seller_profiles WHERE user_id = ?");
            $stmt->execute([$seller_id]);
            
            $stmt = $pdo->prepare("DELETE FROM users WHERE id = ?");
            $stmt->execute([$seller_id]);
            
            $_SESSION['message'] = "Seller has been deleted successfully.";
        }
    }
    
    // Get all sellers with pagination
    $page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
    $per_page = 10;
    $offset = ($page - 1) * $per_page;
    
    // Get total count
    $count_stmt = $pdo->prepare("SELECT COUNT(*) as total FROM users WHERE role = 'seller'");
    $count_stmt->execute();
    $total_sellers = $count_stmt->fetch(PDO::FETCH_ASSOC)['total'];
    $total_pages = ceil($total_sellers / $per_page);
    
    // Get sellers with their profiles
    $stmt = $pdo->prepare("
        SELECT u.*, sp.company_name, sp.verification_status
        FROM users u 
        LEFT JOIN seller_profiles sp ON u.id = sp.user_id 
        WHERE u.role = 'seller' 
        ORDER BY u.created_at DESC 
        LIMIT :offset, :per_page
    ");
    $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
    $stmt->bindValue(':per_page', $per_page, PDO::PARAM_INT);
    $stmt->execute();
    $sellers = $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// Clear output buffer to prevent any accidental output
ob_end_clean();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Sellers - Admin Panel</title>
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
        
        .admin-header {
            background: linear-gradient(135deg, var(--primary-color) 0%, var(--secondary-color) 100%);
            color: white;
            padding: 20px;
            border-radius: 10px;
            margin-bottom: 20px;
        }
        
        .card {
            border: none;
            border-radius: 10px;
            box-shadow: 0 0.15rem 1.75rem 0 rgba(58, 59, 69, 0.1);
            margin-bottom: 20px;
        }
        
        .stats-card {
            transition: transform 0.3s;
        }
        
        .stats-card:hover {
            transform: translateY(-5px);
        }
        
        .status-badge {
            padding: 5px 10px;
            border-radius: 20px;
            font-size: 0.8rem;
            font-weight: 600;
        }
        
        .action-btn {
            padding: 5px 10px;
            border-radius: 5px;
            font-size: 0.85rem;
        }
        
        .pagination {
            justify-content: center;
        }
        
        .filter-box {
            background-color: white;
            border-radius: 10px;
            padding: 20px;
            box-shadow: 0 0.15rem 1.75rem 0 rgba(58, 59, 69, 0.1);
            margin-bottom: 20px;
        }
        
        .table-responsive {
            border-radius: 10px;
            overflow: hidden;
        }
    </style>
</head>
<body>
    <!-- Include Navbar -->
   <nav class="navbar navbar-dark sticky-top bg-dark flex-md-nowrap p-0 shadow">
    <a class="navbar-brand col-md-3 col-lg-2 me-0 px-3" href="../index.php">FarmHub</a>
    <button class="navbar-toggler position-absolute d-md-none collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#sidebarMenu">
        <span class="navbar-toggler-icon"></span>
    </button>
    <div class="navbar-nav">
        <div class="nav-item text-nowrap d-flex align-items-center">
            <span class="px-3 text-white">Welcome, <?= htmlspecialchars($_SESSION['username']) ?></span>
            <a class="nav-link px-3" href="../logout.php">Sign out</a>
        </div>
    </div>
    <img src="<?= htmlspecialchars($user['profile_picture']) ?>" alt="Profile Picture" class="rounded-circle" width="40" height="40">
</nav>
    
    <!-- Include Sidebar -->
    <?php  ?>

    <!-- Main Content -->
    <div class="main-content">
        <!-- Admin Header -->
         <a class="btn btn-outline-secondary m-1" href="dashboard.php">Back to Dashboard</a>
        <div class="admin-header">
            <h1><i class="bi bi-people"></i> Seller Management</h1>
            <p>Manage all seller accounts and verifications</p>
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
        <?php if (isset($auth_error)): ?>
            <div class="alert alert-danger text-center">
                <h4><i class="bi bi-exclamation-triangle"></i> Access Denied</h4>
                <p><?= $auth_error ?></p>
                <a href="login.php" class="btn btn-primary">Login as Admin</a>
            </div>
        <?php elseif ($is_admin): ?>
        
        <!-- Stats Cards -->
        <div class="row mb-4">
            <div class="col-md-3">
                <div class="card stats-card text-white bg-primary">
                    <div class="card-body">
                        <div class="d-flex justify-content-between">
                            <div>
                                <h6 class="card-title">Total Sellers</h6>
                                <h3 class="card-text"><?= $total_sellers ?></h3>
                            </div>
                            <i class="bi bi-people fs-1 opacity-50"></i>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card stats-card text-white bg-success">
                    <div class="card-body">
                        <div class="d-flex justify-content-between">
                            <div>
                                <h6 class="card-title">Verified Sellers</h6>
                                <h3 class="card-text">
                                    <?php
                                    $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM users WHERE role = 'seller' AND is_verified = 1");
                                    $stmt->execute();
                                    echo $stmt->fetch(PDO::FETCH_ASSOC)['count'];
                                    ?>
                                </h3>
                            </div>
                            <i class="bi bi-patch-check fs-1 opacity-50"></i>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card stats-card text-white bg-warning">
                    <div class="card-body">
                        <div class="d-flex justify-content-between">
                            <div>
                                <h6 class="card-title">Pending Verification</h6>
                                <h3 class="card-text">
                                    <?php
                                    $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM users WHERE role = 'seller' AND is_verified = 0");
                                    $stmt->execute();
                                    echo $stmt->fetch(PDO::FETCH_ASSOC)['count'];
                                    ?>
                                </h3>
                            </div>
                            <i class="bi bi-clock-history fs-1 opacity-50"></i>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card stats-card text-white bg-info">
                    <div class="card-body">
                        <div class="d-flex justify-content-between">
                            <div>
                                <h6 class="card-title">This Month</h6>
                                <h3 class="card-text">
                                    <?php
                                    $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM users WHERE role = 'seller' AND created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)");
                                    $stmt->execute();
                                    echo $stmt->fetch(PDO::FETCH_ASSOC)['count'];
                                    ?>
                                </h3>
                            </div>
                            <i class="bi bi-calendar-month fs-1 opacity-50"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Filter Box -->
        <div class="filter-box">
            <div class="row">
                <div class="col-md-6">
                    <div class="input-group">
                        <input type="text" class="form-control" placeholder="Search sellers..." id="searchInput">
                        <button class="btn btn-outline-secondary" type="button" id="searchButton">
                            <i class="bi bi-search"></i>
                        </button>
                    </div>
                </div>
                <div class="col-md-3">
                    <select class="form-select" id="statusFilter">
                        <option value="">All Statuses</option>
                        <option value="verified">Verified</option>
                        <option value="pending">Pending</option>
                    </select>
                </div>
                <div class="col-md-3">
                    <select class="form-select" id="sortFilter">
                        <option value="newest">Newest First</option>
                        <option value="oldest">Oldest First</option>
                        <option value="company">Company Name</option>
                    </select>
                </div>
            </div>
        </div>
        
        <!-- Sellers Table -->
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="mb-0">All Sellers</h5>
                <span class="badge bg-secondary"><?= $total_sellers ?> sellers</span>
            </div>
            <div class="card-body">
                <?php if (count($sellers) > 0): ?>
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Company Name</th>
                                    <th>Username</th>
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
                                            <?= $seller['company_name'] ? htmlspecialchars($seller['company_name']) : 'N/A' ?>
                                        </td>
                                        <td><?= htmlspecialchars($seller['username']) ?></td>
                                        <td><?= htmlspecialchars($seller['email']) ?></td>
                                        <td>
                                            <?php if ($seller['is_verified']): ?>
                                                <span class="badge bg-success status-badge">Verified</span>
                                            <?php else: ?>
                                                <span class="badge bg-warning status-badge">Pending</span>
                                            <?php endif; ?>
                                        </td>
                                        <td><?= date('M d, Y', strtotime($seller['created_at'])) ?></td>
                                        <td>
                                            <div class="btn-group">
                                                <a href="review_seller.php?id=<?= $seller['id'] ?>" class="btn btn-sm btn-primary action-btn">
                                                    <i class="bi bi-eye"></i> View
                                                </a>
                                                <?php if (!$seller['is_verified']): ?>
                                                    <a href="review_seller.php?id=<?= $seller['id'] ?>" class="btn btn-sm btn-warning action-btn">
                                                        <i class="bi bi-clipboard-check"></i> Review
                                                    </a>
                                                <?php endif; ?>
                                                <button type="button" class="btn btn-sm btn-danger action-btn" data-bs-toggle="modal" data-bs-target="#deleteModal<?= $seller['id'] ?>">
                                                    <i class="bi bi-trash"></i> Delete
                                                </button>
                                            </div>
                                            
                                            <!-- Delete Modal -->
                                            <div class="modal fade" id="deleteModal<?= $seller['id'] ?>" tabindex="-1" aria-hidden="true">
                                                <div class="modal-dialog">
                                                    <div class="modal-content">
                                                        <div class="modal-header">
                                                            <h5 class="modal-title">Confirm Delete</h5>
                                                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                                        </div>
                                                        <div class="modal-body">
                                                            <p>Are you sure you want to delete the seller account for <strong><?= htmlspecialchars($seller['company_name'] ?: $seller['username']) ?></strong>?</p>
                                                            <p class="text-danger">This action cannot be undone and will remove all associated data.</p>
                                                        </div>
                                                        <div class="modal-footer">
                                                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                                                            <a href="?action=delete&id=<?= $seller['id'] ?>" class="btn btn-danger">Delete Seller</a>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                    
                    <!-- Pagination -->
                    <?php if ($total_pages > 1): ?>
                        <nav aria-label="Seller pagination">
                            <ul class="pagination">
                                <li class="page-item <?= $page <= 1 ? 'disabled' : '' ?>">
                                    <a class="page-link" href="?page=<?= $page - 1 ?>">Previous</a>
                                </li>
                                
                                <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                                    <li class="page-item <?= $i == $page ? 'active' : '' ?>">
                                        <a class="page-link" href="?page=<?= $i ?>"><?= $i ?></a>
                                    </li>
                                <?php endfor; ?>
                                
                                <li class="page-item <?= $page >= $total_pages ? 'disabled' : '' ?>">
                                    <a class="page-link" href="?page=<?= $page + 1 ?>">Next</a>
                                </li>
                            </ul>
                        </nav>
                    <?php endif; ?>
                    
                <?php else: ?>
                    <div class="text-center py-5">
                        <i class="bi bi-people display-1 text-muted"></i>
                        <h4 class="mt-3">No sellers found</h4>
                        <p class="text-muted">There are no seller accounts in the system yet.</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>
        
        <?php endif; ?>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Simple search functionality
        document.getElementById('searchButton').addEventListener('click', function() {
            const searchTerm = document.getElementById('searchInput').value.toLowerCase();
            const rows = document.querySelectorAll('tbody tr');
            
            rows.forEach(row => {
                const text = row.textContent.toLowerCase();
                row.style.display = text.includes(searchTerm) ? '' : 'none';
            });
        });
        
        // Status filter functionality
        document.getElementById('statusFilter').addEventListener('change', function() {
            const status = this.value;
            const rows = document.querySelectorAll('tbody tr');
            
            rows.forEach(row => {
                if (!status) {
                    row.style.display = '';
                    return;
                }
                
                const statusBadge = row.querySelector('.status-badge').textContent.toLowerCase();
                row.style.display = statusBadge.includes(status) ? '' : 'none';
            });
        });
    </script>
</body>
</html>