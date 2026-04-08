<?php
include 'includes/admin_nav.php';

// Start session and check admin authentication

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../login.php");
    exit();
}

// Include the admin navigation

require_once '../config.php';

// Get all sellers for the filter dropdown
$sellers_stmt = $pdo->prepare("SELECT id, username FROM users WHERE role = 'seller' ORDER BY username");
$sellers_stmt->execute();
$sellers = $sellers_stmt->fetchAll(PDO::FETCH_ASSOC);

// Filter parameters
$filter_seller_id = isset($_GET['seller_id']) ? $_GET['seller_id'] : '';
$filter_date_from = isset($_GET['date_from']) ? $_GET['date_from'] : date('Y-m-01');
$filter_date_to = isset($_GET['date_to']) ? $_GET['date_to'] : date('Y-m-d');
$filter_status = isset($_GET['status']) ? $_GET['status'] : '';

// Calculate summary statistics for all transactions
$summary_query = "SELECT 
                    COUNT(t.id) as total_transactions,
                    SUM(t.amount) as total_revenue,
                    AVG(t.amount) as average_sale,
                    COUNT(DISTINCT o.buyer_id) as unique_customers,
                    COUNT(DISTINCT l.seller_id) as active_sellers
                  FROM transactions t
                  JOIN orders o ON t.order_id = o.id
                  JOIN livestock l ON o.livestock_id = l.id
                  WHERE 1=1";

// Add seller filter if provided
if (!empty($filter_seller_id)) {
    $summary_query .= " AND l.seller_id = :seller_id";
}

// Add date filter if provided
if (!empty($filter_date_from) && !empty($filter_date_to)) {
    $summary_query .= " AND DATE(t.created_at) BETWEEN :date_from AND :date_to";
}

// Add status filter if provided
if (!empty($filter_status)) {
    $summary_query .= " AND t.status = :status";
}

$stmt = $pdo->prepare($summary_query);

if (!empty($filter_seller_id)) {
    $stmt->bindValue(':seller_id', $filter_seller_id, PDO::PARAM_INT);
}

if (!empty($filter_date_from) && !empty($filter_date_to)) {
    $stmt->bindValue(':date_from', $filter_date_from, PDO::PARAM_STR);
    $stmt->bindValue(':date_to', $filter_date_to, PDO::PARAM_STR);
}

if (!empty($filter_status)) {
    $stmt->bindValue(':status', $filter_status, PDO::PARAM_STR);
}

$stmt->execute();
$summary = $stmt->fetch(PDO::FETCH_ASSOC);

// Get all transactions with pagination
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$per_page = 10;
$offset = ($page - 1) * $per_page;

$transactions_query = "SELECT 
                        t.*,
                        o.quantity,
                        o.total_price as order_amount,
                        l.title as livestock_name,
                        l.image_path,
                        u.username as buyer_name,
                        u.email as buyer_email,
                        s.username as seller_name,
                        s.username as seller_username
                      FROM transactions t
                      JOIN orders o ON t.order_id = o.id
                      JOIN livestock l ON o.livestock_id = l.id
                      JOIN users u ON o.buyer_id = u.id
                      JOIN users s ON l.seller_id = s.id
                      WHERE 1=1";

// Add seller filter if provided
if (!empty($filter_seller_id)) {
    $transactions_query .= " AND l.seller_id = :seller_id";
}

// Add date filter if provided
if (!empty($filter_date_from) && !empty($filter_date_to)) {
    $transactions_query .= " AND DATE(t.created_at) BETWEEN :date_from AND :date_to";
}

// Add status filter if provided
if (!empty($filter_status)) {
    $transactions_query .= " AND t.status = :status";
}

$transactions_query .= " ORDER BY t.created_at DESC LIMIT :offset, :per_page";

$stmt = $pdo->prepare($transactions_query);

if (!empty($filter_seller_id)) {
    $stmt->bindValue(':seller_id', $filter_seller_id, PDO::PARAM_INT);
}

if (!empty($filter_date_from) && !empty($filter_date_to)) {
    $stmt->bindValue(':date_from', $filter_date_from, PDO::PARAM_STR);
    $stmt->bindValue(':date_to', $filter_date_to, PDO::PARAM_STR);
}

if (!empty($filter_status)) {
    $stmt->bindValue(':status', $filter_status, PDO::PARAM_STR);
}

$stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
$stmt->bindValue(':per_page', $per_page, PDO::PARAM_INT);
$stmt->execute();
$transactions = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get total count for pagination
$count_query = "SELECT COUNT(t.id) as total
                FROM transactions t
                JOIN orders o ON t.order_id = o.id
                JOIN livestock l ON o.livestock_id = l.id
                WHERE 1=1";

if (!empty($filter_seller_id)) {
    $count_query .= " AND l.seller_id = :seller_id";
}

if (!empty($filter_date_from) && !empty($filter_date_to)) {
    $count_query .= " AND DATE(t.created_at) BETWEEN :date_from AND :date_to";
}

if (!empty($filter_status)) {
    $count_query .= " AND t.status = :status";
}

$stmt = $pdo->prepare($count_query);

if (!empty($filter_seller_id)) {
    $stmt->bindValue(':seller_id', $filter_seller_id, PDO::PARAM_INT);
}

if (!empty($filter_date_from) && !empty($filter_date_to)) {
    $stmt->bindValue(':date_from', $filter_date_from, PDO::PARAM_STR);
    $stmt->bindValue(':date_to', $filter_date_to, PDO::PARAM_STR);
}

if (!empty($filter_status)) {
    $stmt->bindValue(':status', $filter_status, PDO::PARAM_STR);
}

$stmt->execute();
$total_count = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
$total_pages = ceil($total_count / $per_page);

// Get status counts for filter options
$status_query = "SELECT 
                  t.status,
                  COUNT(t.id) as count
                FROM transactions t
                JOIN orders o ON t.order_id = o.id
                JOIN livestock l ON o.livestock_id = l.id
                WHERE 1=1";

if (!empty($filter_seller_id)) {
    $status_query .= " AND l.seller_id = :seller_id";
}

$status_query .= " GROUP BY t.status";

$stmt = $pdo->prepare($status_query);

if (!empty($filter_seller_id)) {
    $stmt->bindValue(':seller_id', $filter_seller_id, PDO::PARAM_INT);
}

$stmt->execute();
$status_counts = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Transactions - Admin Dashboard</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    <style>
        .summary-card {
            border-radius: 10px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            transition: transform 0.3s;
        }
        .summary-card:hover {
            transform: translateY(-5px);
        }
        .status-badge {
            font-size: 0.8rem;
            padding: 0.4em 0.6em;
        }
        .transaction-row {
            transition: background-color 0.2s;
        }
        .transaction-row:hover {
            background-color: #f8f9fa;
        }
        .filter-section {
            background-color: #f8f9fa;
            border-radius: 8px;
            padding: 1.5rem;
        }
        .main-content {
            background-color: #f8f9fa;
            min-height: 100vh;
            padding: 20px 0;
        }
    </style>
</head>
<body>
 <div class="container-fluid">
        <div class="row">
            <!-- Sidebar will be included here -->
            <?php include 'includes/admin_sidebar.php'; ?>
            
            <!-- Main Content -->
            <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4 main-content">
            <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                <h1 class="h2">Transaction Management</h1>
                <div class="btn-toolbar mb-2 mb-md-0">
                    <div class="btn-group me-2">
                        <button type="button" class="btn btn-sm btn-outline-secondary" onclick="window.print()">
                            <i class="bi bi-printer me-1"></i> Print Report
                        </button>
                        <button type="button" class="btn btn-sm btn-outline-secondary">
                            <i class="bi bi-download me-1"></i> Export Data
                        </button>
                    </div>
                </div>
            </div>
            
            <!-- Filter Section -->
            <div class="card filter-section mb-4">
                <div class="card-body">
                    <h5 class="card-title mb-3">Filter Transactions</h5>
                    <form method="GET" class="row g-3">
                        <div class="col-md-3">
                            <label for="seller_id" class="form-label">Seller</label>
                            <select class="form-select" id="seller_id" name="seller_id">
                                <option value="">All Sellers</option>
                                <?php foreach ($sellers as $seller): ?>
                                    <option value="<?= $seller['id'] ?>" <?= $filter_seller_id == $seller['id'] ? 'selected' : '' ?>>
                                        <?= htmlspecialchars($seller['username'] ?: $seller['username']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-2">
                            <label for="date_from" class="form-label">From Date</label>
                            <input type="date" class="form-control" id="date_from" name="date_from" value="<?= $filter_date_from ?>">
                        </div>
                        <div class="col-md-2">
                            <label for="date_to" class="form-label">To Date</label>
                            <input type="date" class="form-control" id="date_to" name="date_to" value="<?= $filter_date_to ?>">
                        </div>
                        <div class="col-md-2">
                            <label for="status" class="form-label">Status</label>
                            <select class="form-select" id="status" name="status">
                                <option value="">All Statuses</option>
                                <?php foreach ($status_counts as $status): ?>
                                    <option value="<?= $status['status'] ?>" <?= $filter_status == $status['status'] ? 'selected' : '' ?>>
                                        <?= ucfirst($status['status']) ?> (<?= $status['count'] ?>)
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-3 d-flex align-items-end">
                            <button type="submit" class="btn btn-primary me-2">Apply Filters</button>
                            <a href="transaction.php" class="btn btn-outline-secondary">Reset</a>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Summary Cards -->
            <div class="row mb-4">
                <div class="col-md-2">
                    <div class="card summary-card text-white bg-primary mb-3">
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-center">
                                <div>
                                    <h6 class="card-title">Total Revenue</h6>
                                    <h3 class="card-text">₦<?= number_format($summary['total_revenue'] ?? 0, 2) ?></h3>
                                </div>
                                <i class="bi bi-currency-exchange fs-1 opacity-50"></i>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-md-2">
                    <div class="card summary-card text-white bg-success mb-3">
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-center">
                                <div>
                                    <h6 class="card-title">Transactions</h6>
                                    <h3 class="card-text"><?= number_format($summary['total_transactions'] ?? 0) ?></h3>
                                </div>
                                <i class="bi bi-receipt fs-1 opacity-50"></i>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-md-2">
                    <div class="card summary-card text-white bg-info mb-3">
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-center">
                                <div>
                                    <h6 class="card-title">Average Sale</h6>
                                    <h3 class="card-text">₦<?= number_format($summary['average_sale'] ?? 0, 2) ?></h3>
                                </div>
                                <i class="bi bi-graph-up fs-1 opacity-50"></i>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-md-2">
                    <div class="card summary-card text-white bg-warning mb-3">
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-center">
                                <div>
                                    <h6 class="card-title">Customers</h6>
                                    <h3 class="card-text"><?= number_format($summary['unique_customers'] ?? 0) ?></h3>
                                </div>
                                <i class="bi bi-people fs-1 opacity-50"></i>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-md-2">
                    <div class="card summary-card text-white bg-secondary mb-3">
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-center">
                                <div>
                                    <h6 class="card-title">Active Sellers</h6>
                                    <h3 class="card-text"><?= number_format($summary['active_sellers'] ?? 0) ?></h3>
                                </div>
                                <i class="bi bi-shop fs-1 opacity-50"></i>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-md-2">
                    <div class="card summary-card text-white bg-dark mb-3">
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-center">
                                <div>
                                    <h6 class="card-title">Total Pages</h6>
                                    <h3 class="card-text"><?= $total_pages ?></h3>
                                </div>
                                <i class="bi bi-file-text fs-1 opacity-50"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Transactions Table -->
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">Transaction Details</h5>
                    <span class="badge bg-secondary"><?= $total_count ?> records found</span>
                </div>
                <div class="card-body">
                    <?php if (count($transactions) > 0): ?>
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th>Transaction ID</th>
                                        <th>Livestock</th>
                                        <th>Buyer</th>
                                        <th>Seller</th>
                                        <th>Amount</th>
                                        <th>Reference</th>
                                        <th>Status</th>
                                        <th>Date</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($transactions as $transaction): ?>
                                        <tr class="transaction-row">
                                            <td>#<?= $transaction['id'] ?></td>
                                            <td>
                                                <div class="d-flex align-items-center">
                                                    <img src="<?= htmlspecialchars($transaction['image_path'] ?? '../images/default-livestock.jpg') ?>" 
                                                         alt="<?= htmlspecialchars($transaction['livestock_name']) ?>" 
                                                         class="rounded me-2" width="40" height="40" style="object-fit: cover;">
                                                    <div>
                                                        <div class="fw-bold"><?= htmlspecialchars($transaction['livestock_name']) ?></div>
                                                        <small class="text-muted">Qty: <?= $transaction['quantity'] ?></small>
                                                    </div>
                                                </div>
                                            </td>
                                            <td>
                                                <div><?= htmlspecialchars($transaction['buyer_name']) ?></div>
                                                <small class="text-muted"><?= htmlspecialchars($transaction['buyer_email']) ?></small>
                                            </td>
                                            <td>
                                                <div class="fw-bold"><?= htmlspecialchars($transaction['seller_name'] ?: $transaction['seller_username']) ?></div>
                                            </td>
                                            <td class="fw-bold">₦<?= number_format($transaction['amount'], 2) ?></td>
                                            <td><code><?= $transaction['transaction_ref'] ?></code></td>
                                            <td>
                                                <?php 
                                                $status_class = [
                                                    'success' => 'bg-success',
                                                    'pending' => 'bg-warning',
                                                    'failed' => 'bg-danger',
                                                    'cancelled' => 'bg-secondary'
                                                ][$transaction['status']] ?? 'bg-secondary';
                                                ?>
                                                <span class="badge <?= $status_class ?> status-badge"><?= ucfirst($transaction['status']) ?></span>
                                            </td>
                                            <td><?= date('M j, Y g:i A', strtotime($transaction['created_at'])) ?></td>
                                            <td>
                                                <button class="btn btn-sm btn-outline-primary" data-bs-toggle="modal" data-bs-target="#transactionModal<?= $transaction['id'] ?>">
                                                    <i class="bi bi-eye"></i> View
                                                </button>
                                            </td>
                                        </tr>

                                        <!-- Transaction Detail Modal -->
                                        <div class="modal fade" id="transactionModal<?= $transaction['id'] ?>" tabindex="-1" aria-hidden="true">
                                            <div class="modal-dialog modal-lg">
                                                <div class="modal-content">
                                                    <div class="modal-header">
                                                        <h5 class="modal-title">Transaction Details #<?= $transaction['id'] ?></h5>
                                                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                                    </div>
                                                    <div class="modal-body">
                                                        <div class="row">
                                                            <div class="col-md-6">
                                                                <h6>Transaction Information</h6>
                                                                <table class="table table-sm">
                                                                    <tr>
                                                                        <th>Transaction ID:</th>
                                                                        <td>#<?= $transaction['id'] ?></td>
                                                                    </tr>
                                                                    <tr>
                                                                        <th>Reference:</th>
                                                                        <td><code><?= $transaction['transaction_ref'] ?></code></td>
                                                                    </tr>
                                                                    <tr>
                                                                        <th>Amount:</th>
                                                                        <td class="fw-bold">₦<?= number_format($transaction['amount'], 2) ?></td>
                                                                    </tr>
                                                                    <tr>
                                                                        <th>Status:</th>
                                                                        <td>
                                                                            <span class="badge <?= $status_class ?>">
                                                                                <?= ucfirst($transaction['status']) ?>
                                                                            </span>
                                                                        </td>
                                                                    </tr>
                                                                    <tr>
                                                                        <th>Payment Gateway:</th>
                                                                        <td><?= ucfirst($transaction['payment_gateway'] ?? 'paystack') ?></td>
                                                                    </tr>
                                                                    <tr>
                                                                        <th>Date:</th>
                                                                        <td><?= date('F j, Y, g:i a', strtotime($transaction['created_at'])) ?></td>
                                                                    </tr>
                                                                </table>
                                                            </div>
                                                            <div class="col-md-6">
                                                                <h6>Order Details</h6>
                                                                <table class="table table-sm">
                                                                    <tr>
                                                                        <th>Livestock:</th>
                                                                        <td><?= htmlspecialchars($transaction['livestock_name']) ?></td>
                                                                    </tr>
                                                                    <tr>
                                                                        <th>Quantity:</th>
                                                                        <td><?= $transaction['quantity'] ?></td>
                                                                    </tr>
                                                                    <tr>
                                                                        <th>Order Amount:</th>
                                                                        <td>₦<?= number_format($transaction['order_amount'], 2) ?></td>
                                                                    </tr>
                                                                    <tr>
                                                                        <th>Buyer Name:</th>
                                                                        <td><?= htmlspecialchars($transaction['buyer_name']) ?></td>
                                                                    </tr>
                                                                    <tr>
                                                                        <th>Buyer Email:</th>
                                                                        <td><?= htmlspecialchars($transaction['buyer_email']) ?></td>
                                                                    </tr>
                                                                    <tr>
                                                                        <th>Seller:</th>
                                                                        <td><?= htmlspecialchars($transaction['seller_name'] ?: $transaction['seller_username']) ?></td>
                                                                    </tr>
                                                                </table>
                                                            </div>
                                                        </div>
                                                    </div>
                                                    <div class="modal-footer">
                                                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                                                        <?php if ($transaction['status'] === 'pending'): ?>
                                                            <button type="button" class="btn btn-warning">Mark as Processing</button>
                                                        <?php endif; ?>
                                                        <?php if ($transaction['status'] === 'failed'): ?>
                                                            <button type="button" class="btn btn-info">Retry Payment</button>
                                                        <?php endif; ?>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>

                        <!-- Pagination -->
                        <?php if ($total_pages > 1): ?>
                            <nav aria-label="Transaction pagination">
                                <ul class="pagination justify-content-center">
                                    <li class="page-item <?= $page <= 1 ? 'disabled' : '' ?>">
                                        <a class="page-link" href="?page=<?= $page - 1 ?>&seller_id=<?= $filter_seller_id ?>&date_from=<?= $filter_date_from ?>&date_to=<?= $filter_date_to ?>&status=<?= $filter_status ?>">Previous</a>
                                    </li>
                                    
                                    <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                                        <li class="page-item <?= $i == $page ? 'active' : '' ?>">
                                            <a class="page-link" href="?page=<?= $i ?>&seller_id=<?= $filter_seller_id ?>&date_from=<?= $filter_date_from ?>&date_to=<?= $filter_date_to ?>&status=<?= $filter_status ?>"><?= $i ?></a>
                                        </li>
                                    <?php endfor; ?>
                                    
                                    <li class="page-item <?= $page >= $total_pages ? 'disabled' : '' ?>">
                                        <a class="page-link" href="?page=<?= $page + 1 ?>&seller_id=<?= $filter_seller_id ?>&date_from=<?= $filter_date_from ?>&date_to=<?= $filter_date_to ?>&status=<?= $filter_status ?>">Next</a>
                                    </li>
                                </ul>
                            </nav>
                        <?php endif; ?>

                    <?php else: ?>
                        <div class="text-center py-5">
                            <i class="bi bi-receipt-cutoff display-1 text-muted"></i>
                            <h4 class="mt-3">No transactions found</h4>
                            <p class="text-muted">No transactions match your filters.</p>
                            <a href="transaction.php" class="btn btn-primary">Clear Filters</a>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </main>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>