<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
include 'includes/admin_nav.php'; 
require_once '../config.php';

// Check admin authentication
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'admin') {
    header("Location: ../login.php");
    exit();
}

// Get statistics for dashboard with error handling
try {
    $stats = [
        'total_users' => $pdo->query("SELECT COUNT(*) FROM users")->fetchColumn(),
        'total_sellers' => $pdo->query("SELECT COUNT(*) FROM users WHERE role = 'seller'")->fetchColumn(),
        'pending_sellers' => $pdo->query("SELECT COUNT(*) FROM users WHERE role = 'seller' AND is_verified = 0")->fetchColumn(),
        'total_livestock' => $pdo->query("SELECT COUNT(*) FROM livestock")->fetchColumn(),
        'active_livestock' => $pdo->query("SELECT COUNT(*) FROM livestock WHERE is_available = 1")->fetchColumn(),
        'total_orders' => $pdo->query("SELECT COUNT(*) FROM orders")->fetchColumn(),
        'pending_orders' => $pdo->query("SELECT COUNT(*) FROM orders WHERE status = 'pending'")->fetchColumn(),
        'completed_orders' => $pdo->query("SELECT COUNT(*) FROM orders WHERE status = 'delivered'")->fetchColumn()
    ];
    
    // Calculate verified sellers
    $stats['verified_sellers'] = $stats['total_sellers'] - $stats['pending_sellers'];
    
    // Get revenue stats
    $revenue_stmt = $pdo->query("SELECT SUM(total_price + transport_fee) as total_revenue FROM orders WHERE status = 'delivered'");
    $stats['total_revenue'] = $revenue_stmt->fetchColumn() ?? 0;

} catch (PDOException $e) {
    error_log("Dashboard stats error: " . $e->getMessage());
    $stats = array_fill_keys(['total_users', 'total_sellers', 'pending_sellers', 'total_livestock', 'active_livestock', 'total_orders', 'pending_orders', 'completed_orders', 'verified_sellers', 'total_revenue'], 0);
}

// Get pending sellers for approval
try {
    $pending_sellers = $pdo->query("
        SELECT u.id, u.username, u.email, u.created_at, 
               sp.company_name, sp.nin, sp.phone, sp.created_at as profile_created
        FROM users u
        LEFT JOIN seller_profiles sp ON u.id = sp.user_id
        WHERE u.role = 'seller' AND u.is_verified = 0
        ORDER BY u.created_at DESC
        LIMIT 10
    ")->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    error_log("Pending sellers error: " . $e->getMessage());
    $pending_sellers = [];
}

// Get recent orders
try {
    $recent_orders = $pdo->query("
        SELECT o.id, o.total_price, o.transport_fee, o.status as order_status, o.created_at,
               l.title as livestock_title, 
               u.username as buyer_name, 
               s.username as seller_name
        FROM orders o
        JOIN livestock l ON o.livestock_id = l.id
        JOIN users u ON o.buyer_id = u.id
        JOIN users s ON l.seller_id = s.id
        ORDER BY o.created_at DESC 
        LIMIT 8
    ")->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    error_log("Recent orders error: " . $e->getMessage());
    $recent_orders = [];
}

// Get recent livestock
try {
    $recent_livestock = $pdo->query("
        SELECT l.id, l.title, l.price, l.is_available, l.posted_at,
               c.name as category_name, u.username as seller_name
        FROM livestock l
        JOIN categories c ON l.category_id = c.id
        JOIN users u ON l.seller_id = u.id
        ORDER BY l.posted_at DESC 
        LIMIT 8
    ")->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    error_log("Recent livestock error: " . $e->getMessage());
    $recent_livestock = [];
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - FarmHub</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    <link href="../css/admin.css" rel="stylesheet">
</head>
<body>
    <!-- Admin Navigation -->
    <?php ?>
    
    <div class="container-fluid">
        <div class="row">
            <!-- Sidebar -->
            <?php include 'includes/admin_sidebar.php'; ?>
            
            <!-- Main Content -->
            <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4">
                <!-- Header -->
                <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                    <div>
                        <h1 class="h2">Dashboard Overview</h1>
                        <p class="text-muted mb-0">Welcome back, <?= htmlspecialchars($_SESSION['username'] ?? 'Admin') ?>! Here's what's happening today.</p>
                    </div>
                    <div class="btn-toolbar mb-2 mb-md-0">
                        <div class="btn-group me-2">
                            <button type="button" class="btn btn-sm btn-outline-primary">
                                <i class="bi bi-download me-1"></i> Export Report
                            </button>
                            <button type="button" class="btn btn-sm btn-outline-secondary">
                                <i class="bi bi-calendar me-1"></i> This Month
                            </button>
                        </div>
                    </div>
                </div>
                
                <!-- Stats Cards -->
                <div class="row mb-4">
                    <div class="col-xl-3 col-md-6 mb-4">
                        <div class="card border-left-primary shadow h-100 py-2">
                            <div class="card-body">
                                <div class="row no-gutters align-items-center">
                                    <div class="col mr-2">
                                        <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">
                                            Total Users</div>
                                        <div class="h5 mb-0 font-weight-bold text-gray-800"><?= number_format($stats['total_users']) ?></div>
                                    </div>
                                    <div class="col-auto">
                                        <i class="bi bi-people fa-2x text-gray-300"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="col-xl-3 col-md-6 mb-4">
                        <div class="card border-left-success shadow h-100 py-2">
                            <div class="card-body">
                                <div class="row no-gutters align-items-center">
                                    <div class="col mr-2">
                                        <div class="text-xs font-weight-bold text-success text-uppercase mb-1">
                                            Verified Sellers</div>
                                        <div class="h5 mb-0 font-weight-bold text-gray-800"><?= number_format($stats['verified_sellers']) ?></div>
                                    </div>
                                    <div class="col-auto">
                                        <i class="bi bi-patch-check fa-2x text-gray-300"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="col-xl-3 col-md-6 mb-4">
                        <div class="card border-left-warning shadow h-100 py-2">
                            <div class="card-body">
                                <div class="row no-gutters align-items-center">
                                    <div class="col mr-2">
                                        <div class="text-xs font-weight-bold text-warning text-uppercase mb-1">
                                            Pending Sellers</div>
                                        <div class="h5 mb-0 font-weight-bold text-gray-800"><?= number_format($stats['pending_sellers']) ?></div>
                                    </div>
                                    <div class="col-auto">
                                        <i class="bi bi-clock-history fa-2x text-gray-300"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="col-xl-3 col-md-6 mb-4">
                        <div class="card border-left-info shadow h-100 py-2">
                            <div class="card-body">
                                <div class="row no-gutters align-items-center">
                                    <div class="col mr-2">
                                        <div class="text-xs font-weight-bold text-info text-uppercase mb-1">
                                            Total Revenue</div>
                                        <div class="h5 mb-0 font-weight-bold text-gray-800">?<?= number_format($stats['total_revenue'], 2) ?></div>
                                    </div>
                                    <div class="col-auto">
                                        <i class="bi bi-currency-dollar fa-2x text-gray-300"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="row mb-4">
                    <div class="col-xl-3 col-md-6 mb-4">
                        <div class="card border-left-secondary shadow h-100 py-2">
                            <div class="card-body">
                                <div class="row no-gutters align-items-center">
                                    <div class="col mr-2">
                                        <div class="text-xs font-weight-bold text-secondary text-uppercase mb-1">
                                            Total Livestock</div>
                                        <div class="h5 mb-0 font-weight-bold text-gray-800"><?= number_format($stats['total_livestock']) ?></div>
                                    </div>
                                    <div class="col-auto">
                                        <i class="bi bi-egg-fried fa-2x text-gray-300"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="col-xl-3 col-md-6 mb-4">
                        <div class="card border-left-success shadow h-100 py-2">
                            <div class="card-body">
                                <div class="row no-gutters align-items-center">
                                    <div class="col mr-2">
                                        <div class="text-xs font-weight-bold text-success text-uppercase mb-1">
                                            Available Livestock</div>
                                        <div class="h5 mb-0 font-weight-bold text-gray-800"><?= number_format($stats['active_livestock']) ?></div>
                                    </div>
                                    <div class="col-auto">
                                        <i class="bi bi-check-circle fa-2x text-gray-300"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="col-xl-3 col-md-6 mb-4">
                        <div class="card border-left-danger shadow h-100 py-2">
                            <div class="card-body">
                                <div class="row no-gutters align-items-center">
                                    <div class="col mr-2">
                                        <div class="text-xs font-weight-bold text-danger text-uppercase mb-1">
                                            Total Orders</div>
                                        <div class="h5 mb-0 font-weight-bold text-gray-800"><?= number_format($stats['total_orders']) ?></div>
                                    </div>
                                    <div class="col-auto">
                                        <i class="bi bi-cart fa-2x text-gray-300"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="col-xl-3 col-md-6 mb-4">
                        <div class="card border-left-warning shadow h-100 py-2">
                            <div class="card-body">
                                <div class="row no-gutters align-items-center">
                                    <div class="col mr-2">
                                        <div class="text-xs font-weight-bold text-warning text-uppercase mb-1">
                                            Pending Orders</div>
                                        <div class="h5 mb-0 font-weight-bold text-gray-800"><?= number_format($stats['pending_orders']) ?></div>
                                    </div>
                                    <div class="col-auto">
                                        <i class="bi bi-hourglass-split fa-2x text-gray-300"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Quick Actions -->
                <div class="row mb-4">
                    <div class="col-12">
                        <div class="card shadow">
                            <div class="card-header bg-white py-3">
                                <h5 class="mb-0"><i class="bi bi-lightning me-2"></i>Quick Actions</h5>
                            </div>
                            <div class="card-body">
                                <div class="row text-center">
                                    <div class="col-md-3 mb-3">
                                        <a href="add_seller.php" class="btn btn-outline-primary btn-lg w-100 py-3">
                                            <i class="bi bi-person-plus display-6 d-block mb-2"></i>
                                            Add Seller
                                        </a>
                                    </div>
                                    <div class="col-md-3 mb-3">
                                        <a href="livestock.php" class="btn btn-outline-success btn-lg w-100 py-3">
                                            <i class="bi bi-plus-circle display-6 d-block mb-2"></i>
                                            Manage Livestock
                                        </a>
                                    </div>
                                    <div class="col-md-3 mb-3">
                                        <a href="orders.php" class="btn btn-outline-info btn-lg w-100 py-3">
                                            <i class="bi bi-cart-check display-6 d-block mb-2"></i>
                                            View Orders
                                        </a>
                                    </div>
                                    <div class="col-md-3 mb-3">
                                        <a href="reports.php" class="btn btn-outline-warning btn-lg w-100 py-3">
                                            <i class="bi bi-graph-up display-6 d-block mb-2"></i>
                                            Generate Reports
                                        </a>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="row">
                    <!-- Pending Sellers Section -->
                    <div class="col-lg-6 mb-4">
                        <div class="card shadow">
                            <div class="card-header bg-white d-flex justify-content-between align-items-center py-3">
                                <h5 class="mb-0"><i class="bi bi-clock-history me-2"></i>Pending Seller Approvals</h5>
                                <?php if (!empty($pending_sellers)): ?>
                                    <span class="badge bg-danger rounded-pill"><?= count($pending_sellers) ?> pending</span>
                                <?php endif; ?>
                            </div>
                            <div class="card-body">
                                <?php if (empty($pending_sellers)): ?>
                                    <div class="text-center py-4">
                                        <i class="bi bi-check-circle display-4 text-success mb-3"></i>
                                        <p class="text-muted">No pending seller approvals. Great job!</p>
                                    </div>
                                <?php else: ?>
                                    <div class="table-responsive">
                                        <table class="table table-hover">
                                            <thead class="table-light">
                                                <tr>
                                                    <th>Company</th>
                                                    <th>Contact</th>
                                                    <th>Registered</th>
                                                    <th>Actions</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php foreach ($pending_sellers as $seller): ?>
                                                    <tr>
                                                        <td>
                                                            <div class="fw-bold"><?= htmlspecialchars($seller['company_name'] ?? 'N/A') ?></div>
                                                            <small class="text-muted"><?= htmlspecialchars($seller['username']) ?></small>
                                                        </td>
                                                        <td>
                                                            <div><?= htmlspecialchars($seller['email']) ?></div>
                                                            <small class="text-muted"><?= htmlspecialchars($seller['phone'] ?? 'N/A') ?></small>
                                                        </td>
                                                        <td><?= date('M d, Y', strtotime($seller['created_at'])) ?></td>
                                                        <td>
                                                            <a href="view_seller.php?id=<?= $seller['id'] ?>" class="btn btn-sm btn-primary">
                                                                <i class="bi bi-eye"></i> Review
                                                            </a>
                                                        </td>
                                                    </tr>
                                                <?php endforeach; ?>
                                            </tbody>
                                        </table>
                                    </div>
                                    <div class="text-end mt-3">
                                        <a href="sellers.php?filter=pending" class="btn btn-outline-primary">View All Pending Sellers</a>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>

                    <!-- Recent Orders Section -->
                    <div class="col-lg-6 mb-4">
                        <div class="card shadow">
                            <div class="card-header bg-white d-flex justify-content-between align-items-center py-3">
                                <h5 class="mb-0"><i class="bi bi-cart me-2"></i>Recent Orders</h5>
                                <a href="orders.php" class="btn btn-sm btn-outline-primary">View All</a>
                            </div>
                            <div class="card-body">
                                <?php if (empty($recent_orders)): ?>
                                    <div class="text-center py-4">
                                        <i class="bi bi-cart-x display-4 text-muted mb-3"></i>
                                        <p class="text-muted">No recent orders found.</p>
                                    </div>
                                <?php else: ?>
                                    <div class="table-responsive">
                                        <table class="table table-hover">
                                            <thead class="table-light">
                                                <tr>
                                                    <th>Order #</th>
                                                    <th>Product</th>
                                                    <th>Amount</th>
                                                    <th>Status</th>
                                                    <th>Action</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php foreach ($recent_orders as $order): ?>
                                                    <tr>
                                                        <td class="fw-bold">#<?= $order['id'] ?></td>
                                                        <td>
                                                            <div class="fw-bold"><?= htmlspecialchars($order['livestock_title']) ?></div>
                                                            <small class="text-muted">Buyer: <?= htmlspecialchars($order['buyer_name']) ?></small>
                                                        </td>
                                                        <td>?<?= number_format($order['total_price'] + $order['transport_fee'], 2) ?></td>
                                                        <td>
                                                            <span class="badge 
                                                                <?= $order['order_status'] == 'pending' ? 'bg-warning' : 
                                                                   ($order['order_status'] == 'delivered' ? 'bg-success' : 
                                                                   ($order['order_status'] == 'cancelled' ? 'bg-danger' : 'bg-info')) ?>">
                                                                <?= ucfirst($order['order_status']) ?>
                                                            </span>
                                                        </td>
                                                        <td>
                                                            <a href="order_details.php?id=<?= $order['id'] ?>" class="btn btn-sm btn-outline-primary">
                                                                <i class="bi bi-eye"></i>
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
                    </div>
                </div>

                <!-- Recent Livestock Section -->
                <div class="row">
                    <div class="col-12">
                        <div class="card shadow">
                            <div class="card-header bg-white d-flex justify-content-between align-items-center py-3">
                                <h5 class="mb-0"><i class="bi bi-egg-fried me-2"></i>Recently Added Livestock</h5>
                                <a href="livestock.php" class="btn btn-sm btn-outline-primary">View All</a>
                            </div>
                            <div class="card-body">
                                <?php if (empty($recent_livestock)): ?>
                                    <div class="text-center py-4">
                                        <i class="bi bi-inbox display-4 text-muted mb-3"></i>
                                        <p class="text-muted">No livestock found.</p>
                                    </div>
                                <?php else: ?>
                                    <div class="table-responsive">
                                        <table class="table table-hover">
                                            <thead class="table-light">
                                                <tr>
                                                    <th>Product</th>
                                                    <th>Category</th>
                                                    <th>Seller</th>
                                                    <th>Price</th>
                                                    <th>Status</th>
                                                    <th>Posted</th>
                                                    <th>Actions</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php foreach ($recent_livestock as $item): ?>
                                                    <tr>
                                                        <td class="fw-bold"><?= htmlspecialchars($item['title']) ?></td>
                                                        <td><?= htmlspecialchars($item['category_name']) ?></td>
                                                        <td><?= htmlspecialchars($item['seller_name']) ?></td>
                                                        <td class="fw-bold">?<?= number_format($item['price'], 2) ?></td>
                                                        <td>
                                                            <span class="badge <?= $item['is_available'] ? 'bg-success' : 'bg-secondary' ?>">
                                                                <?= $item['is_available'] ? 'Available' : 'Sold' ?>
                                                            </span>
                                                        </td>
                                                        <td><?= date('M d, Y', strtotime($item['posted_at'])) ?></td>
                                                        <td>
                                                            <div class="btn-group btn-group-sm">
                                                                <a href="../livestock_details.php?id=<?= $item['id'] ?>" class="btn btn-outline-primary" title="View">
                                                                    <i class="bi bi-eye"></i>
                                                                </a>
                                                                <a href="edit_livestock.php?id=<?= $item['id'] ?>" class="btn btn-outline-warning" title="Edit">
                                                                    <i class="bi bi-pencil"></i>
                                                                </a>
                                                            </div>
                                                        </td>
                                                    </tr>
                                                <?php endforeach; ?>
                                            </tbody>
                                        </table>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>
            </main>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="../js/admin.js"></script>
    
    <script>
        // Auto-refresh dashboard every 5 minutes
        setInterval(function() {
            window.location.reload();
        }, 300000);
        
        // Add some interactive features
        document.addEventListener('DOMContentLoaded', function() {
            // Add hover effects to cards
            const cards = document.querySelectorAll('.card');
            cards.forEach(card => {
                card.addEventListener('mouseenter', function() {
                    this.style.transform = 'translateY(-2px)';
                    this.style.transition = 'all 0.3s ease';
                });
                card.addEventListener('mouseleave', function() {
                    this.style.transform = 'translateY(0)';
                });
            });
        });
    </script>
</body>
</html>