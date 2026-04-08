<?php
include 'includes/admin_nav.php'; 
require_once '../config.php';

// Check admin authentication
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'admin') {
    header("Location: ../login.php");
    exit();
}

// Generate CSRF token if not exists
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// Get users with pagination and filtering
$per_page = 10;
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
if ($page < 1) $page = 1;
$start = ($page > 1) ? ($page * $per_page) - $per_page : 0;

// Filter by role
$role_filter = isset($_GET['role']) ? $_GET['role'] : 'all';
$search_query = isset($_GET['search']) ? trim($_GET['search']) : '';

// Build query with parameterization - UPDATED TO MATCH YOUR TABLE STRUCTURE
$sql = "SELECT SQL_CALC_FOUND_ROWS 
        u.*, 
        COUNT(DISTINCT l.id) as livestock_count,
        COUNT(DISTINCT o.id) as orders_count
        FROM users u
        LEFT JOIN livestock l ON u.id = l.seller_id
        LEFT JOIN orders o ON u.id = o.buyer_id";

$where = [];
$params = [];

// Add filters
if ($role_filter !== 'all') {
    $where[] = "u.role = :role";
}

if (!empty($search_query)) {
    // Updated to only search columns that exist in your table
    $where[] = "(u.username LIKE :search OR u.email LIKE :search2)";
}

if (!empty($where)) {
    $sql .= " WHERE " . implode(" AND ", $where);
}

$sql .= " GROUP BY u.id ORDER BY u.created_at DESC LIMIT :start, :per_page";

// Execute query
$stmt = $pdo->prepare($sql);

// Bind parameters
if ($role_filter !== 'all') {
    $stmt->bindValue(':role', $role_filter, PDO::PARAM_STR);
}

if (!empty($search_query)) {
    $search_param = "%$search_query%";
    $stmt->bindValue(':search', $search_param, PDO::PARAM_STR);
    $stmt->bindValue(':search2', $search_param, PDO::PARAM_STR);
}

$stmt->bindValue(':start', $start, PDO::PARAM_INT);
$stmt->bindValue(':per_page', $per_page, PDO::PARAM_INT);
$stmt->execute();

$users = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get total count
$total = $pdo->query("SELECT FOUND_ROWS()")->fetchColumn();
$pages = ceil($total / $per_page);

// Role options
$roles = ['all' => 'All Roles', 'buyer' => 'Buyers', 'seller' => 'Sellers', 'admin' => 'Admins'];
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Users - Admin Panel</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    <style>
        .user-avatar {
            width: 50px;
            height: 50px;
            border-radius: 50%;
            object-fit: cover;
        }
        .status-badge {
            font-size: 0.75rem;
        }
        .stats-badge {
            font-size: 0.7rem;
        }
        .table-responsive {
            overflow-x: auto;
        }
        .action-buttons .btn {
            padding: 0.25rem 0.5rem;
            font-size: 0.875rem;
        }
        @media (max-width: 768px) {
            .table-responsive {
                font-size: 0.875rem;
            }
            .action-buttons {
                display: flex;
                flex-direction: column;
                gap: 0.25rem;
            }
            .action-buttons .btn {
                width: 100%;
            }
        }
        .toast-container {
            position: fixed;
            top: 20px;
            right: 20px;
            z-index: 9999;
        }
    </style>
</head>
<body>
    <!-- Navigation -->
    <?php ?>
    
    <div class="container-fluid">
        <div class="row">
            <!-- Sidebar -->
            <?php include 'includes/admin_sidebar.php'; ?>
            
            <!-- Main Content -->
            <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4">
                <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                    <h1 class="h2">Manage Users</h1>
                    <div class="btn-toolbar mb-2 mb-md-0">
                        <button class="btn btn-success" data-bs-toggle="modal" data-bs-target="#addUserModal">
                            <i class="bi bi-person-plus"></i> Add New User
                        </button>
                    </div>
                </div>

                <!-- Toast notifications -->
                <div class="toast-container">
                    <?php if (isset($_SESSION['success_message'])): ?>
                    <div class="toast align-items-center text-white bg-success border-0" role="alert" aria-live="assertive" aria-atomic="true" data-bs-autohide="true" data-bs-delay="5000">
                        <div class="d-flex">
                            <div class="toast-body">
                                <i class="bi bi-check-circle-fill me-2"></i>
                                <?= htmlspecialchars($_SESSION['success_message']) ?>
                            </div>
                            <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast" aria-label="Close"></button>
                        </div>
                    </div>
                    <?php unset($_SESSION['success_message']); ?>
                    <?php endif; ?>
                    
                    <?php if (isset($_SESSION['error_message'])): ?>
                    <div class="toast align-items-center text-white bg-danger border-0" role="alert" aria-live="assertive" aria-atomic="true" data-bs-autohide="true" data-bs-delay="5000">
                        <div class="d-flex">
                            <div class="toast-body">
                                <i class="bi bi-exclamation-triangle-fill me-2"></i>
                                <?= htmlspecialchars($_SESSION['error_message']) ?>
                            </div>
                            <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast" aria-label="Close"></button>
                        </div>
                    </div>
                    <?php unset($_SESSION['error_message']); ?>
                    <?php endif; ?>
                </div>

                <!-- Filters -->
                <div class="card mb-4">
                    <div class="card-body">
                        <form method="GET" class="row g-3">
                            <div class="col-md-4">
                                <input type="text" class="form-control" name="search" 
                                       value="<?= htmlspecialchars($search_query) ?>" placeholder="Search by username or email...">
                            </div>
                            <div class="col-md-3">
                                <select class="form-select" name="role">
                                    <?php foreach ($roles as $value => $label): ?>
                                        <option value="<?= $value ?>" <?= $role_filter === $value ? 'selected' : '' ?>>
                                            <?= $label ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-2">
                                <button type="submit" class="btn btn-primary w-100">Filter</button>
                            </div>
                            <div class="col-md-2">
                                <a href="users.php" class="btn btn-outline-secondary w-100">Reset</a>
                            </div>
                        </form>
                    </div>
                </div>

                <!-- Users Table -->
                <div class="card">
                    <div class="card-body">
                        <?php if (empty($users)): ?>
                            <div class="alert alert-info text-center py-4">
                                <i class="bi bi-info-circle fs-4"></i>
                                <h4>No users found</h4>
                                <p class="mb-0">Try adjusting your search criteria</p>
                            </div>
                        <?php else: ?>
                            <div class="table-responsive">
                                <table class="table table-hover align-middle">
                                    <thead class="table-light">
                                        <tr>
                                            <th>User</th>
                                            <th>Contact</th>
                                            <th>Role</th>
                                            <th>Status</th>
                                            <th>Stats</th>
                                            <th>Joined</th>
                                            <th>Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($users as $user): ?>
                                            <tr>
                                                <td>
                                                    <div class="d-flex align-items-center">
                                                        <img src="../<?= htmlspecialchars($user['profile_picture'] ?? 'images/default-avatar.jpg') ?>" 
                                                             class="user-avatar me-3" alt="<?= htmlspecialchars($user['username']) ?>">
                                                        <div>
                                                            <strong><?= htmlspecialchars($user['username']) ?></strong>
                                                            <br>
                                                            <small class="text-muted">ID: <?= htmlspecialchars($user['id']) ?></small>
                                                        </div>
                                                    </div>
                                                </td>
                                                <td>
                                                    <small>
                                                        <i class="bi bi-envelope"></i> <?= htmlspecialchars($user['email']) ?><br>
                                                        <?php if ($user['phone']): ?>
                                                            <i class="bi bi-phone"></i> <?= htmlspecialchars($user['phone']) ?>
                                                        <?php endif; ?>
                                                    </small>
                                                </td>
                                                <td>
                                                    <span class="badge 
                                                        <?= $user['role'] == 'admin' ? 'bg-danger' : 
                                                           ($user['role'] == 'seller' ? 'bg-success' : 'bg-primary') ?>">
                                                        <?= ucfirst($user['role']) ?>
                                                    </span>
                                                </td>
                                                <td>
                                                    <span class="badge 
                                                        <?= $user['is_verified'] ? 'bg-success' : 'bg-secondary' ?> status-badge">
                                                        <?= $user['is_verified'] ? 'Active' : 'Inactive' ?>
                                                    </span>
                                                </td>
                                                <td>
                                                    <?php if ($user['role'] == 'seller'): ?>
                                                        <span class="badge bg-info stats-badge">
                                                            <?= $user['livestock_count'] ?> listings
                                                        </span>
                                                    <?php endif; ?>
                                                    <?php if ($user['role'] == 'buyer'): ?>
                                                        <span class="badge bg-warning stats-badge">
                                                            <?= $user['orders_count'] ?> orders
                                                        </span>
                                                    <?php endif; ?>
                                                </td>
                                                                                                <!-- In the users table row, add this after the status badge -->
                                               <td>
    <span class="badge 
        <?= $user['is_verified'] ? 'bg-success' : 'bg-secondary' ?> status-badge">
        <?= $user['is_verified'] ? 'Verified' : 'Unverified' ?>
    </span>
    <?php if ($user['role'] == 'seller'): ?>
        <br>
        <small class="<?= $user['is_verified'] ? 'text-success' : 'text-warning' ?>">
            <i class="bi bi-<?= $user['is_verified'] ? 'patch-check' : 'patch-question' ?>"></i>
            <?= $user['is_verified'] ? 'Verified Seller' : 'Needs Verification' ?>
        </small>
    <?php endif; ?>
</td>
                                                                                                <td>
                                                    <small><?= date('M d, Y', strtotime($user['created_at'])) ?></small>
                                                </td>
                                                <td class="action-buttons">
                                                    <button class="btn btn-sm btn-outline-primary edit-user-btn" 
                                                            data-user-id="<?= $user['id'] ?>">
                                                        <i class="bi bi-pencil"></i> <span class="d-none d-md-inline">Edit</span>
                                                    </button>
                                                    <button class="btn btn-sm btn-outline-danger delete-user-btn" 
                                                            data-user-id="<?= $user['id'] ?>"
                                                            data-username="<?= htmlspecialchars($user['username']) ?>">
                                                        <i class="bi bi-trash"></i> <span class="d-none d-md-inline">Delete</span>
                                                    </button>
                                                    <td class="action-buttons">
    <button class="btn btn-sm btn-outline-primary edit-user-btn" 
            data-user-id="<?= $user['id'] ?>">
        <i class="bi bi-pencil"></i> <span class="d-none d-md-inline">Edit</span>
    </button>
    
    <?php if ($user['role'] == 'seller' && !$user['is_verified']): ?>
        <button class="btn btn-sm btn-outline-success verify-user-btn" 
                data-user-id="<?= $user['id'] ?>"
                data-username="<?= htmlspecialchars($user['username']) ?>">
            <i class="bi bi-patch-check"></i> Verify
        </button>
    <?php endif; ?>
    
    <button class="btn btn-sm btn-outline-danger delete-user-btn" 
            data-user-id="<?= $user['id'] ?>"
            data-username="<?= htmlspecialchars($user['username']) ?>">
        <i class="bi bi-trash"></i> <span class="d-none d-md-inline">Delete</span>
    </button>
</td>
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

                                    <?php for ($i = max(1, $page - 2); $i <= min($pages, $page + 2); $i++): ?>
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

    <!-- Add User Modal -->
    <div class="modal fade" id="addUserModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header bg-success text-white">
                    <h5 class="modal-title">Add New User</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form id="addUserForm" method="POST" action="add_user.php" enctype="multipart/form-data">
                    <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
                    <div class="modal-body">
                        <div class="mb-3">
                            <label class="form-label">Username *</label>
                            <input type="text" class="form-control" name="username" required pattern="[a-zA-Z0-9_]+" minlength="3" maxlength="30">
                            <div class="form-text">Only letters, numbers and underscores allowed</div>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Email *</label>
                            <input type="email" class="form-control" name="email" required maxlength="100">
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Password *</label>
                            <input type="password" class="form-control" name="password" required minlength="8" id="addPassword">
                            <div class="form-text">Minimum 8 characters with at least one number and one letter</div>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Confirm Password *</label>
                            <input type="password" class="form-control" name="confirm_password" required minlength="8">
                        </div>
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label class="form-label">Phone</label>
                                <input type="tel" class="form-control" name="phone" pattern="[0-9+\-\s()]+" maxlength="20">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Role *</label>
                                <select class="form-select" name="role" required>
                                    <option value="buyer">Buyer</option>
                                    <option value="seller">Seller</option>
                                    <option value="admin">Admin</option>
                                </select>
                            </div>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Profile Picture</label>
                            <input type="file" class="form-control" name="profile_picture" accept="image/jpeg,image/png,image/gif">
                            <div class="form-text">Max size 2MB. JPG, PNG or GIF only.</div>
                        </div>
                        <div class="form-check form-switch mb-3">
                            <input class="form-check-input" type="checkbox" name="is_active" id="addIsActive" checked>
                            <label class="form-check-label" for="addIsActive">Active Account</label>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-success">Add User</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
<!-- Verify User Modal -->
<div class="modal fade" id="verifyUserModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header bg-success text-white">
                <h5 class="modal-title">Verify Seller</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <p>Are you sure you want to verify <strong id="verifyUsername"></strong> as a seller?</p>
                <p class="text-success">This will grant them full seller privileges on the platform.</p>
                <div class="mb-3">
                    <label class="form-label">Verification Notes (Optional)</label>
                    <textarea class="form-control" id="verification_notes" rows="3" placeholder="Add any notes about this verification..."></textarea>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-success" id="confirmVerify">Verify Seller</button>
            </div>
        </div>
    </div>
</div>
    <!-- Edit User Modal -->
    <div class="modal fade" id="editUserModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header bg-primary text-white">
                    <h5 class="modal-title">Edit User</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form id="editUserForm" method="POST" action="update_user.php" enctype="multipart/form-data">
                    <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
                    <input type="hidden" name="user_id" id="edit_user_id">
                    <div class="modal-body">
                        <!-- Content will be loaded via AJAX -->
                        <div class="text-center py-4">
                            <div class="spinner-border" role="status">
                                <span class="visually-hidden">Loading...</span>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary">Save Changes</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Delete Confirmation Modal -->
    <div class="modal fade" id="deleteUserModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header bg-danger text-white">
                    <h5 class="modal-title">Confirm Delete</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <p>Are you sure you want to delete user <strong id="deleteUsername"></strong>?</p>
                    <p class="text-danger">This action cannot be undone. All associated data will also be removed.</p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <form id="deleteUserForm" method="POST" action="delete_user.php">
                        <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
                        <input type="hidden" name="user_id" id="delete_user_id">
                        <button type="submit" class="btn btn-danger">Delete User</button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
    document.addEventListener('DOMContentLoaded', function() {
        // Initialize toast notifications
        const toastElList = document.querySelectorAll('.toast');
        const toastList = [...toastElList].map(toastEl => new bootstrap.Toast(toastEl));
        toastList.forEach(toast => toast.show());
        
        // Edit user modal
        const editModal = document.getElementById('editUserModal');
        const editUserBtns = document.querySelectorAll('.edit-user-btn');
        
        editUserBtns.forEach(btn => {
            btn.addEventListener('click', function() {
                const userId = this.getAttribute('data-user-id');
                loadUserData(userId);
                const modal = new bootstrap.Modal(editModal);
                modal.show();
            });
        });
        
        // Delete user modal
        const deleteModal = document.getElementById('deleteUserModal');
        const deleteUserBtns = document.querySelectorAll('.delete-user-btn');
        
        deleteUserBtns.forEach(btn => {
            btn.addEventListener('click', function() {
                const userId = this.getAttribute('data-user-id');
                const username = this.getAttribute('data-username');
                
                document.getElementById('delete_user_id').value = userId;
                document.getElementById('deleteUsername').textContent = username;
                
                const modal = new bootstrap.Modal(deleteModal);
                modal.show();
            });
        });
        
        // Form validation
        const addUserForm = document.getElementById('addUserForm');
        if (addUserForm) {
            addUserForm.addEventListener('submit', function(e) {
                const password = document.getElementById('addPassword').value;
                if (!validatePassword(password)) {
                    e.preventDefault();
                    alert('Password must be at least 8 characters long and contain at least one number and one letter');
                    return false;
                }
            });
        }
        
        // Password validation
        function validatePassword(password) {
            const minLength = 8;
            const hasLetter = /[a-zA-Z]/.test(password);
            const hasNumber = /[0-9]/.test(password);
            
            return password.length >= minLength && hasLetter && hasNumber;
        }
    });

    function loadUserData(userId) {
    fetch(`get_user.php?id=${userId}`)
        .then(response => {
            if (!response.ok) {
                throw new Error('Network response was not ok');
            }
            return response.json();
        })
        .then(data => {
            console.log('User data received:', data); // Debug log
            if (data.success) {
                // Check if is_active field exists, default to true if not
                const isActive = data.data.is_verified !== undefined ? data.data.is_verified : true;
                
                const form = `
                    <div class="mb-3">
                        <label class="form-label">Username *</label>
                        <input type="text" class="form-control" name="username" value="${escapeHtml(data.data.username)}" required pattern="[a-zA-Z0-9_]+" minlength="3" maxlength="30">
                        <div class="form-text">Only letters, numbers and underscores allowed</div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Email *</label>
                        <input type="email" class="form-control" name="email" value="${escapeHtml(data.data.email)}" required maxlength="100">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">New Password (leave blank to keep current)</label>
                        <input type="password" class="form-control" name="password" minlength="8" id="editPassword">
                        <div class="form-text">Minimum 8 characters with at least one number and one letter</div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Confirm New Password</label>
                        <input type="password" class="form-control" name="confirm_password" minlength="8">
                    </div>
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label class="form-label">Phone</label>
                            <input type="tel" class="form-control" name="phone" value="${escapeHtml(data.data.phone || '')}" pattern="[0-9+\-\s()]+" maxlength="20">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Role *</label>
                            <select class="form-select" name="role" required>
                                <option value="buyer" ${data.data.role === 'buyer' ? 'selected' : ''}>Buyer</option>
                                <option value="seller" ${data.data.role === 'seller' ? 'selected' : ''}>Seller</option>
                                <option value="admin" ${data.data.role === 'admin' ? 'selected' : ''}>Admin</option>
                            </select>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Profile Picture</label>
                        <input type="file" class="form-control" name="profile_picture" accept="image/jpeg,image/png,image/gif">
                        <div class="form-text">Max size 2MB. JPG, PNG or GIF only.</div>
                        ${data.data.profile_picture ? `<div class="mt-2"><img src="../${escapeHtml(data.data.profile_picture)}" class="user-avatar" alt="Current profile picture"></div>` : ''}
                    </div>
                    <div class="form-check form-switch">
                        <input class="form-check-input" type="checkbox" name="is_active" id="editIsActive" ${isActive ? 'checked' : ''}>
                        <label class="form-check-label" for="editIsActive">Active Account</label>
                    </div>
                `;
                document.getElementById('edit_user_id').value = data.data.id;
                document.querySelector('#editUserModal .modal-body').innerHTML = form;
                
                // Add password validation for edit form
                const editForm = document.getElementById('editUserForm');
                const editPassword = document.getElementById('editPassword');
                
                if (editForm && editPassword) {
                    editForm.addEventListener('submit', function(e) {
                        if (editPassword.value && !validatePassword(editPassword.value)) {
                            e.preventDefault();
                            alert('Password must be at least 8 characters long and contain at least one number and one letter');
                            return false;
                        }
                    });
                }
            } else {
                document.querySelector('#editUserModal .modal-body').innerHTML = 
                    '<div class="alert alert-danger">Error loading user data: ' + escapeHtml(data.message) + '</div>';
            }
        })
        .catch(error => {
            console.error('Error:', error);
            document.querySelector('#editUserModal .modal-body').innerHTML = 
                '<div class="alert alert-danger">Error loading user data. Please check if get_user.php exists and is accessible.</div>';
        });
}
    function escapeHtml(unsafe) {
        if (unsafe === null || unsafe === undefined) return '';
        return unsafe
            .toString()
            .replace(/&/g, "&amp;")
            .replace(/</g, "&lt;")
            .replace(/>/g, "&gt;")
            .replace(/"/g, "&quot;")
            .replace(/'/g, "&#039;");
    }
    
    function validatePassword(password) {
        if (!password) return true; // Empty password is allowed in edit form
        const minLength = 8;
        const hasLetter = /[a-zA-Z]/.test(password);
        const hasNumber = /[0-9]/.test(password);
        
        return password.length >= minLength && hasLetter && hasNumber;
    }
    // Add this to your existing JavaScript
document.addEventListener('DOMContentLoaded', function() {
    // ... existing code ...
    
    // Verify user modal
    const verifyModal = document.getElementById('verifyUserModal');
    const verifyUserBtns = document.querySelectorAll('.verify-user-btn');
    
    verifyUserBtns.forEach(btn => {
        btn.addEventListener('click', function() {
            const userId = this.getAttribute('data-user-id');
            const username = this.getAttribute('data-username');
            
            document.getElementById('verifyUsername').textContent = username;
            
            // Store user ID in a data attribute on the confirm button
            document.getElementById('confirmVerify').setAttribute('data-user-id', userId);
            
            const modal = new bootstrap.Modal(verifyModal);
            modal.show();
        });
    });
    
    // Handle verification confirmation
    document.getElementById('confirmVerify').addEventListener('click', function() {
        const userId = this.getAttribute('data-user-id');
        const notes = document.getElementById('verification_notes').value;
        
        verifyUser(userId, notes);
    });
});

// Function to verify user
function verifyUser(userId, notes) {
    const formData = new FormData();
    formData.append('user_id', userId);
    formData.append('notes', notes);
    formData.append('csrf_token', '<?= $_SESSION['csrf_token'] ?>');
    
    fetch('verify_user.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            alert('Seller verified successfully!');
            // Close modal and reload page
            const modal = bootstrap.Modal.getInstance(document.getElementById('verifyUserModal'));
            modal.hide();
            window.location.reload();
        } else {
            alert('Error verifying seller: ' + data.message);
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('Error verifying seller');
    });
}
// Add this to your existing JavaScript
document.addEventListener('DOMContentLoaded', function() {
    // Verify user modal
    const verifyModal = document.getElementById('verifyUserModal');
    const verifyUserBtns = document.querySelectorAll('.verify-user-btn');
    
    verifyUserBtns.forEach(btn => {
        btn.addEventListener('click', function() {
            const userId = this.getAttribute('data-user-id');
            const username = this.getAttribute('data-username');
            
            document.getElementById('verifyUsername').textContent = username;
            document.getElementById('verification_notes').value = '';
            
            // Store user ID in a data attribute on the confirm button
            document.getElementById('confirmVerify').setAttribute('data-user-id', userId);
            
            const modal = new bootstrap.Modal(verifyModal);
            modal.show();
        });
    });
    
    // Handle verification confirmation
    document.getElementById('confirmVerify').addEventListener('click', function() {
        const userId = this.getAttribute('data-user-id');
        const notes = document.getElementById('verification_notes').value;
        
        verifyUser(userId, notes);
    });
});

// Function to verify user
function verifyUser(userId, notes) {
    const formData = new FormData();
    formData.append('user_id', userId);
    formData.append('notes', notes);
    formData.append('csrf_token', '<?= $_SESSION['csrf_token'] ?>');
    
    // Show loading state
    const verifyBtn = document.getElementById('confirmVerify');
    const originalText = verifyBtn.innerHTML;
    verifyBtn.innerHTML = '<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Verifying...';
    verifyBtn.disabled = true;
    
    fetch('verify_user.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            alert('Seller verified successfully!');
            // Close modal and reload page
            const modal = bootstrap.Modal.getInstance(document.getElementById('verifyUserModal'));
            modal.hide();
            window.location.reload();
        } else {
            alert('Error verifying seller: ' + data.message);
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('Error verifying seller');
    })
    .finally(() => {
        // Restore button state
        verifyBtn.innerHTML = originalText;
        verifyBtn.disabled = false;
    });
}
    </script>
</body>
</html>