<?php
include 'includes/admin_nav.php';
require_once '../config.php';

// Check admin authentication
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'admin') {
    header("Location: ../login.php");
    exit();
}

// Pagination setup
$per_page = 10;
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
if ($page < 1) $page = 1;
$start = ($page > 1) ? ($page * $per_page) - $per_page : 0;

// Filter values
$status_filter = isset($_GET['status']) ? $_GET['status'] : 'all';
$search_query = isset($_GET['search']) ? trim($_GET['search']) : '';

// Build simpler query - get orders first, then get related data separately
$sql = "SELECT SQL_CALC_FOUND_ROWS * FROM orders WHERE 1=1";
$params = [];

// Add status filter
if ($status_filter !== 'all') {
    $sql .= " AND status = ?";
    $params[] = $status_filter;
}

// Add search filter (search by order ID)
if (!empty($search_query) && is_numeric($search_query)) {
    $sql .= " AND id = ?";
    $params[] = (int)$search_query;
}

$sql .= " ORDER BY created_at DESC LIMIT " . (int)$start . ", " . (int)$per_page;

// Execute query
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$orders = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get total count
$total = $pdo->query("SELECT FOUND_ROWS()")->fetchColumn();
$pages = ceil($total / $per_page);

// Status options
$status_options = [
    'all' => 'All Statuses',
    'pending' => 'Pending',
    'confirmed' => 'Confirmed',
    'shipped' => 'Shipped',
    'delivered' => 'Delivered',
    'cancelled' => 'Cancelled'
];

// Function to get livestock details
function getLivestockDetails($pdo, $livestock_id) {
    $stmt = $pdo->prepare("SELECT title, image_path, seller_id FROM livestock WHERE id = ?");
    $stmt->execute([$livestock_id]);
    return $stmt->fetch(PDO::FETCH_ASSOC);
}

// Function to get user details
function getUserDetails($pdo, $user_id) {
    $stmt = $pdo->prepare("SELECT username, email, phone FROM users WHERE id = ?");
    $stmt->execute([$user_id]);
    return $stmt->fetch(PDO::FETCH_ASSOC);
}

// Function to get seller details
function getSellerDetails($pdo, $seller_id) {
    $stmt = $pdo->prepare("SELECT username, email FROM users WHERE id = ?");
    $stmt->execute([$seller_id]);
    return $stmt->fetch(PDO::FETCH_ASSOC);
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Orders - Admin Panel</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    <style>
        .order-img {
            width: 60px;
            height: 60px;
            object-fit: cover;
            border-radius: 4px;
        }
        .status-badge {
            font-size: 0.75rem;
        }
        .table-responsive {
            overflow-x: auto;
        }
        .loading-spinner {
            display: inline-block;
            width: 1rem;
            height: 1rem;
            border: 2px solid #f3f3f3;
            border-top: 2px solid #3498db;
            border-radius: 50%;
            animation: spin 1s linear infinite;
        }
        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }
    </style>
</head>
<body>
    <?php  ?>
    
    <div class="container-fluid">
        <div class="row">
            <?php include 'includes/admin_sidebar.php'; ?>
            
            <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4">
                <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                    <h1 class="h2">Manage Orders</h1>
                </div>

                <!-- Success/Error Messages -->
                <?php if (isset($_SESSION['success_message'])): ?>
                    <div class="alert alert-success alert-dismissible fade show" role="alert">
                        <?= $_SESSION['success_message'] ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                    <?php unset($_SESSION['success_message']); ?>
                <?php endif; ?>

                <?php if (isset($_SESSION['error_message'])): ?>
                    <div class="alert alert-danger alert-dismissible fade show" role="alert">
                        <?= $_SESSION['error_message'] ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                    <?php unset($_SESSION['error_message']); ?>
                <?php endif; ?>

                <!-- Filters -->
                <div class="card mb-4">
                    <div class="card-body">
                        <form method="GET" class="row g-3">
                            <div class="col-md-4">
                                <input type="text" class="form-control" name="search" 
                                       value="<?= htmlspecialchars($search_query) ?>" placeholder="Search by Order ID...">
                                <div class="form-text">Enter order ID to search</div>
                            </div>
                            <div class="col-md-3">
                                <select class="form-select" name="status">
                                    <?php foreach ($status_options as $value => $label): ?>
                                        <option value="<?= $value ?>" <?= $status_filter === $value ? 'selected' : '' ?>>
                                            <?= $label ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-2">
                                <button type="submit" class="btn btn-primary w-100">Filter</button>
                            </div>
                            <div class="col-md-2">
                                <a href="orders.php" class="btn btn-outline-secondary w-100">Reset</a>
                            </div>
                        </form>
                    </div>
                </div>

                <!-- Orders Table -->
                <div class="card">
                    <div class="card-body">
                        <?php if (empty($orders)): ?>
                            <div class="alert alert-info text-center py-4">
                                <i class="bi bi-info-circle fs-4"></i>
                                <h4>No orders found</h4>
                                <p class="mb-0">Try adjusting your search criteria</p>
                            </div>
                        <?php else: ?>
                            <div class="table-responsive">
                                <table class="table table-hover align-middle">
                                    <thead class="table-light">
                                        <tr>
                                            <th>Order ID</th>
                                            <th>Livestock</th>
                                            <th>Buyer</th>
                                            <th>Amount</th>
                                            <th>Status</th>
                                            <th>Date</th>
                                            <th>Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($orders as $order): 
                                            // Get related data
                                            $livestock = getLivestockDetails($pdo, $order['livestock_id']);
                                            $buyer = getUserDetails($pdo, $order['buyer_id']);
                                            $seller = $livestock ? getSellerDetails($pdo, $livestock['seller_id']) : null;
                                        ?>
                                            <tr>
                                                <td>#<?= $order['id'] ?></td>
                                                <td>
                                                    <?php if ($livestock): ?>
                                                        <div class="d-flex align-items-center">
                                                            <img src="<?= htmlspecialchars($livestock['image_path'] ?? 'images/default-livestock.jpg') ?>" 
                                                                 class="order-img me-3" alt="<?= htmlspecialchars($livestock['title']) ?>">
                                                            <div>
                                                                <strong><?= htmlspecialchars($livestock['title']) ?></strong>
                                                                <br>
                                                                <small>Qty: <?= $order['quantity'] ?></small>
                                                            </div>
                                                        </div>
                                                    <?php else: ?>
                                                        <span class="text-muted">Livestock not found</span>
                                                    <?php endif; ?>
                                                </td>
                                                <td>
                                                    <?php if ($buyer): ?>
                                                        <strong><?= htmlspecialchars($buyer['username']) ?></strong>
                                                        <br>
                                                        <small class="text-muted"><?= htmlspecialchars($buyer['email']) ?></small>
                                                        <?php if ($buyer['phone']): ?>
                                                            <br>
                                                            <small class="text-muted"><?= htmlspecialchars($buyer['phone']) ?></small>
                                                        <?php endif; ?>
                                                    <?php else: ?>
                                                        <span class="text-muted">Buyer not found</span>
                                                    <?php endif; ?>
                                                </td>
                                                <td>₦<?= number_format($order['total_price'], 2) ?></td>
                                                <td>
                                                    <span class="badge 
                                                        <?= $order['status'] == 'pending' ? 'bg-warning' : 
                                                           ($order['status'] == 'confirmed' ? 'bg-info' : 
                                                           ($order['status'] == 'shipped' ? 'bg-primary' : 
                                                           ($order['status'] == 'delivered' ? 'bg-success' : 'bg-danger'))) ?> status-badge">
                                                        <?= ucfirst($order['status']) ?>
                                                    </span>
                                                </td>
                                                <td><?= date('M d, Y', strtotime($order['created_at'])) ?></td>
                                                <td>
                                                    <button class="btn btn-sm btn-outline-primary view-order-btn" 
                                                            data-order-id="<?= $order['id'] ?>">
                                                        <i class="bi bi-eye"></i> View
                                                    </button>
                                                    <button class="btn btn-sm btn-outline-info edit-order-btn" 
                                                            data-order-id="<?= $order['id'] ?>"
                                                            data-order-status="<?= $order['status'] ?>">
                                                        <i class="bi bi-pencil"></i> Edit
                                                    </button>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>

                            <!-- Pagination -->
                            <nav aria-label="Page navigation" class="mt-4">
                                <ul class="pagination justify-content-center">
                                    <?php if ($page > 1): ?>
                                        <li class="page-item">
                                            <a class="page-link" href="?<?= http_build_query(array_merge($_GET, ['page' => $page - 1])) ?>">
                                                &laquo; Previous
                                            </a>
                                        </li>
                                    <?php endif; ?>

                                    <?php for ($i = 1; $i <= $pages; $i++): ?>
                                        <li class="page-item <?= $page == $i ? 'active' : '' ?>">
                                            <a class="page-link" href="?<?= http_build_query(array_merge($_GET, ['page' => $i])) ?>">
                                                <?= $i ?>
                                            </a>
                                        </li>
                                    <?php endfor; ?>

                                    <?php if ($page < $pages): ?>
                                        <li class="page-item">
                                            <a class="page-link" href="?<?= http_build_query(array_merge($_GET, ['page' => $page + 1])) ?>">
                                                Next &raquo;
                                            </a>
                                        </li>
                                    <?php endif; ?>
                                </ul>
                            </nav>
                        <?php endif; ?>
                    </div>
                </div>
            </main>
        </div>
    </div>

    <!-- Order Details Modal -->
    <div class="modal fade" id="orderDetailsModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header bg-primary text-white">
                    <h5 class="modal-title">Order Details #<span id="orderIdHeader"></span></h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body" id="orderDetailsContent">
                    <div class="text-center py-4">
                        <div class="loading-spinner"></div>
                        <span class="ms-2">Loading order details...</span>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Edit Order Modal -->
    <div class="modal fade" id="editOrderModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header bg-info text-white">
                    <h5 class="modal-title">Update Order Status</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form id="editOrderForm" method="POST" action="update_order.php">
                    <input type="hidden" name="order_id" id="edit_order_id">
                    <div class="modal-body">
                        <div class="mb-3">
                            <label class="form-label">Current Status</label>
                            <p id="currentStatus" class="form-control-plaintext fw-bold"></p>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Update Status</label>
                            <select class="form-select" name="status" required>
                                <option value="pending">Pending</option>
                                <option value="confirmed">Confirmed</option>
                                <option value="shipped">Shipped</option>
                                <option value="delivered">Delivered</option>
                                <option value="cancelled">Cancelled</option>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Notes (Optional)</label>
                            <textarea class="form-control" name="notes" rows="3" placeholder="Add any notes about this order..."></textarea>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary">Update Status</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
    document.addEventListener('DOMContentLoaded', function() {
        // View order details
        document.querySelectorAll('.view-order-btn').forEach(btn => {
            btn.addEventListener('click', function() {
                const orderId = this.getAttribute('data-order-id');
                showOrderDetails(orderId);
            });
        });

        // Edit order status
        document.querySelectorAll('.edit-order-btn').forEach(btn => {
            btn.addEventListener('click', function() {
                const orderId = this.getAttribute('data-order-id');
                const currentStatus = this.getAttribute('data-order-status');
                
                document.getElementById('edit_order_id').value = orderId;
                document.getElementById('currentStatus').textContent = 
                    currentStatus.charAt(0).toUpperCase() + currentStatus.slice(1);
                document.querySelector('select[name="status"]').value = currentStatus;
                
                const modal = new bootstrap.Modal(document.getElementById('editOrderModal'));
                modal.show();
            });
        });

        // Handle form submission
        document.getElementById('editOrderForm').addEventListener('submit', function(e) {
            e.preventDefault();
            
            const formData = new FormData(this);
            const submitBtn = this.querySelector('button[type="submit"]');
            const originalText = submitBtn.innerHTML;
            
            // Show loading state
            submitBtn.innerHTML = '<span class="loading-spinner"></span> Updating...';
            submitBtn.disabled = true;
            
            fetch('update_order.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    alert('Order updated successfully!');
                    // Close the modal and reload page
                    const modal = bootstrap.Modal.getInstance(document.getElementById('editOrderModal'));
                    modal.hide();
                    window.location.reload();
                } else {
                    alert('Error: ' + data.message);
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('Error updating order. Please try again.');
            })
            .finally(() => {
                // Restore button state
                submitBtn.innerHTML = originalText;
                submitBtn.disabled = false;
            });
        });
    });

    function showOrderDetails(orderId) {
        const modal = new bootstrap.Modal(document.getElementById('orderDetailsModal'));
        const content = document.getElementById('orderDetailsContent');
        
        // Show loading state
        content.innerHTML = `
            <div class="text-center py-4">
                <div class="loading-spinner"></div>
                <span class="ms-2">Loading order details...</span>
            </div>
        `;
        
        // Set order ID in header
        document.getElementById('orderIdHeader').textContent = orderId;
        
        // Show modal
        modal.show();
        
        // Load details via AJAX
        fetch(`get_order.php?id=${orderId}`)
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    content.innerHTML = createOrderDetailsHTML(data.data);
                } else {
                    content.innerHTML = `
                        <div class="alert alert-danger">
                            Error loading order details: ${data.message}
                        </div>
                    `;
                }
            })
            .catch(error => {
                console.error('Error:', error);
                content.innerHTML = `
                    <div class="alert alert-danger">
                        Error loading order details. Please try again.
                    </div>
                `;
            });
    }

    function createOrderDetailsHTML(order) {
        return `
            <div class="row mb-4">
                <div class="col-md-6">
                    <h6>Order Information</h6>
                    <p><strong>Order ID:</strong> #${order.id}</p>
                    <p><strong>Date:</strong> ${new Date(order.created_at).toLocaleDateString()}</p>
                    <p><strong>Status:</strong> <span class="badge bg-${getStatusColor(order.status)}">${order.status}</span></p>
                </div>
                <div class="col-md-6">
                    <h6>Payment Details</h6>
                    <p><strong>Total Amount:</strong> ₦${parseFloat(order.total_price).toFixed(2)}</p>
                    <p><strong>Quantity:</strong> ${order.quantity}</p>
                </div>
            </div>
            
            <div class="row mb-4">
                <div class="col-md-6">
                    <h6>Buyer Information</h6>
                    <p><strong>Name:</strong> ${order.buyer_name || 'N/A'}</p>
                    <p><strong>Email:</strong> ${order.buyer_email || 'N/A'}</p>
                    ${order.buyer_phone ? `<p><strong>Phone:</strong> ${order.buyer_phone}</p>` : ''}
                </div>
            </div>
            
            ${order.notes ? `
            <div class="mb-3">
                <h6>Order Notes</h6>
                <p class="text-muted">${order.notes}</p>
            </div>
            ` : ''}
        `;
    }

    function getStatusColor(status) {
        const colors = {
            'pending': 'warning',
            'confirmed': 'info',
            'shipped': 'primary',
            'delivered': 'success',
            'cancelled': 'danger'
        };
        return colors[status] || 'secondary';
    }
    </script>
</body>
</html>