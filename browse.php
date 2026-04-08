<?php
require_once 'config.php';
include 'includes/nav.php';
// Initialize variables
$category_id = isset($_GET['category']) ? (int)$_GET['category'] : 0;
$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$min_price = isset($_GET['min_price']) && !empty($_GET['min_price']) ? (float)$_GET['min_price'] : null;
$max_price = isset($_GET['max_price']) && !empty($_GET['max_price']) ? (float)$_GET['max_price'] : null;
$health_status = isset($_GET['health_status']) ? $_GET['health_status'] : '';
$sort = isset($_GET['sort']) ? $_GET['sort'] : 'newest';
$per_page = 12;

// Get category name if filtering by category
$category_name = '';
if ($category_id > 0) {
    $stmt = $pdo->prepare("SELECT name FROM categories WHERE id = ?");
    $stmt->execute([$category_id]);
    $category = $stmt->fetch(PDO::FETCH_ASSOC);
    $category_name = $category ? htmlspecialchars($category['name']) : '';
}

// Build base query
$sql = "SELECT l.*, c.name as category_name, u.username as seller_name,
        (SELECT image_path FROM livestock_images WHERE livestock_id = l.id ORDER BY id LIMIT 1) as image_path
        FROM livestock l 
        JOIN categories c ON l.category_id = c.id 
        JOIN users u ON l.seller_id = u.id 
        WHERE l.is_available = 1";

$params = [];

// Add filters
if ($category_id > 0) {
    $sql .= " AND l.category_id = ?";
    $params[] = $category_id;
}

if (!empty($search)) {
    $sql .= " AND (l.title LIKE ? OR l.description LIKE ?)";
    $params[] = "%$search%";
    $params[] = "%$search%";
}

if ($min_price !== null) {
    $sql .= " AND l.price >= ?";
    $params[] = $min_price;
}

if ($max_price !== null) {
    $sql .= " AND l.price <= ?";
    $params[] = $max_price;
}

if (!empty($health_status)) {
    $sql .= " AND l.health_status = ?";
    $params[] = $health_status;
}

// Add sorting
switch ($sort) {
    case 'price_asc':
        $sql .= " ORDER BY l.price ASC";
        break;
    case 'price_desc':
        $sql .= " ORDER BY l.price DESC";
        break;
    case 'oldest':
        $sql .= " ORDER BY l.posted_at ASC";
        break;
    default: // newest
        $sql .= " ORDER BY l.posted_at DESC";
        break;
}

// Execute query to fetch all records (JavaScript will handle pagination)
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$livestock = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Function to fix image paths with ../ prefix
function fixImagePath($image_path) {
    if (empty($image_path)) {
        return 'images/dog.png';
    }
    
    // Normalize path by replacing backslashes with forward slashes
    $image_path = str_replace('\\', '/', $image_path);
    
    // Remove any leading ../ or ./ patterns
    $image_path = preg_replace('/^(\.\.\/|\.\/)+/', '', $image_path);
    
    // Check if the path still contains ../ in the middle and resolve it
    if (strpos($image_path, '../') !== false) {
        $parts = explode('/', $image_path);
        $resolved = [];
        
        foreach ($parts as $part) {
            if ($part === '..') {
                if (!empty($resolved)) {
                    array_pop($resolved);
                }
            } elseif ($part !== '.' && $part !== '') {
                $resolved[] = $part;
            }
        }
        
        $image_path = implode('/', $resolved);
    }
    
    // Check if file exists with corrected path
    if (file_exists($image_path)) {
        return $image_path;
    }
    
    // Try with images/ prefix if original path doesn't work
    $alt_path = 'images/' . $image_path;
    if (file_exists($alt_path)) {
        return $alt_path;
    }
    
    // Try without any subdirectories (just the filename)
    $filename = basename($image_path);
    if (file_exists('images/' . $filename)) {
        return 'images/' . $filename;
    }
    
    // Return default image if no image found
    return 'images/dog.png';
}

// Process image paths for each livestock item
foreach ($livestock as &$item) {
    $item['image_path'] = fixImagePath($item['image_path'] ?? '');
}
unset($item); // Break the reference

// Count total records for pagination - using a separate query
$count_sql = "SELECT COUNT(*) as total 
              FROM livestock l 
              JOIN categories c ON l.category_id = c.id 
              JOIN users u ON l.seller_id = u.id 
              WHERE l.is_available = 1";

$count_params = [];

// Add the same filters as the main query
if ($category_id > 0) {
    $count_sql .= " AND l.category_id = ?";
    $count_params[] = $category_id;
}

if (!empty($search)) {
    $count_sql .= " AND (l.title LIKE ? OR l.description LIKE ?)";
    $count_params[] = "%$search%";
    $count_params[] = "%$search%";
}

if ($min_price !== null) {
    $count_sql .= " AND l.price >= ?";
    $count_params[] = $min_price;
}

if ($max_price !== null) {
    $count_sql .= " AND l.price <= ?";
    $count_params[] = $max_price;
}

if (!empty($health_status)) {
    $count_sql .= " AND l.health_status = ?";
    $count_params[] = $health_status;
}

$stmt = $pdo->prepare($count_sql);
if ($stmt === false) {
    die("Prepare failed: " . print_r($pdo->errorInfo(), true));
}
$success = $stmt->execute($count_params);
if ($success === false) {
    die("Execute failed: " . print_r($stmt->errorInfo(), true));
}
$result = $stmt->fetch(PDO::FETCH_ASSOC);
$total_records = $result && isset($result['total']) ? $result['total'] : 0;
$total_pages = ceil($total_records / $per_page) ?: 1; // Ensure at least 1 page

// Get categories for filter dropdown
$categories = $pdo->query("SELECT * FROM categories WHERE is_active = 1 ORDER BY name")->fetchAll(PDO::FETCH_ASSOC);

// Health status options
$health_statuses = ['Healthy', 'Minimally Strong', 'To Be Disposed'];

// Function to build query string with current filters
function buildQueryString($exclude = []) {
    $params = $_GET;
    foreach ($exclude as $key) {
        unset($params[$key]);
    }
    return !empty($params) ? '?' . http_build_query($params) : '';
}

// Convert livestock data to JSON for JavaScript
$livestock_json = json_encode($livestock);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>FarmHub</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    <style>
        :root {
            --primary-color: #4e73df;
            --secondary-color: #f8f9fc;
        }
        
        .livestock-card {
            transition: transform 0.3s ease, box-shadow 0.3s ease;
            height: 100%;
        }
        
        .livestock-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 20px rgba(0, 0, 0, 0.1);
        }
        
        .livestock-img {
            height: 200px;
            object-fit: cover;
        }
        
        .price-tag {
            position: absolute;
            top: 10px;
            right: 10px;
            background-color: var(--primary-color);
            color: white;
            padding: 5px 10px;
            border-radius: 20px;
            font-weight: bold;
        }
        
        .health-badge {
            position: absolute;
            top: 10px;
            left: 10px;
        }
        
        .filter-card {
            position: sticky;
            top: 20px;
        }
        
        .sort-dropdown .dropdown-toggle::after {
            display: none;
        }
        
        .default-image {
            background-color: #f8f9fa;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #6c757d;
            font-size: 3rem;
            height: 200px;
        }
        
        .filter-badge {
            background-color: var(--primary-color);
            color: white;
            padding: 0.35em 0.65em;
            border-radius: 0.25rem;
            font-size: 0.75em;
            margin-left: 0.5rem;
        }
        
        .language-selector {
            position: absolute;
            top: 15px;
            right: 15px;
        }
        
        .language-flag {
            width: 20px;
            height: 15px;
            margin-right: 5px;
            border: 1px solid #ddd;
        }
    </style>
</head>
<body>
    <!-- Navigation -->
    <?php  ?>
    
    <!-- Main Content -->
    <div class="container py-5">
        <div class="language-selector dropdown">
            <button class="btn btn-outline-primary dropdown-toggle" type="button" id="languageDropdown" data-bs-toggle="dropdown">
                <img src="data:image/svg+xml;base64,<?= base64_encode($_SESSION['language'] == 'hausa' ? '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 3 2"><path fill="#009A00" d="M0 0h3v2H0z"/><path fill="#FFF" d="M0 0h3v1H0z"/><path fill="#DA1A30" d="M0 0h1v2H0z"/></svg>' : '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 60 30"><path fill="#012169" d="M0 0v30h60V0z"/><path fill="#FFF" d="M0 0v30h60V0z" transform="scale(2)"/><path fill="#C8102E" d="M0 0v30h60V0z" transform="scale(3)"/><path fill="#FFF" d="M0 0l60 30m0-30L0 30" stroke="#FFF" stroke-width="6"/><path fill="#C8102E" d="M0 0l60 30m0-30L0 30" stroke="#C8102E" stroke-width="4"/></svg>') ?>" class="language-flag" alt="<?= $_SESSION['language'] ?>">
                <?= $_SESSION['language'] == 'hausa' ? 'Hausa' : 'English' ?>
            </button>
            <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="languageDropdown">
                <li>
                    <a class="dropdown-item" href="?lang=english<?= isset($_GET['category']) ? '&category=' . $_GET['category'] : '' ?>">
                        <img src="data:image/svg+xml;base64,PHN2ZyB4bWxucz0iaHR0cDovL3d3dy53My5vcmcvMjAwMC9zdmciIHZpZXdCb3g9IjAgMCA2MCAzMCI+PHBhdGggZmlsbD0iIzAxMjE2OSIgZD0iTTAgMHYzMGg2MFYweiIvPjxwYXRoIGZpbGw9IiNGRkYiIGQ9Ik0wIDB2MzBoNjBWMHoiIHRyYW5sYXQ9InNjYWxlKDIpIi8+PHBhdGggZmlsbD0iI0M4MTAyRSIgZD0iTTAgMHYzMGg2MFYweiIgdHJhbnNmb3JtPSJzY2FsZSgzKSIvPjxwYXRoIGZpbGw=" class="language-flag me-2" alt="English">
                        English
                    </a>
                </li>
                <li>
                    <a class="dropdown-item" href="?lang=hausa<?= isset($_GET['category']) ? '&category=' . $_GET['category'] : '' ?>">
                        <img src="data:image/svg+xml;base64,PHN2ZyB4bWxucz0iaHR0cDovL3d3dy53My5vcmcvMjAwMC9zdmciIHZpZXdCb3g9IjAgMCAzIDIiPjxwYXRoIGZpbGw9IiMwMDlBMDAiIGQ9Ik0wIDBoM3YySDB6Ii8+PHBhdGggZmlsbD0iI0ZGRiIgZD0iTTAgMGgzdjFIMHoiLz48cGF0aCBmaWxsPSIjREExQTMwIiBkPSJNMCAwaDF2MkgweiIvPjwvc3ZnPg==" class="language-flag me-2" alt="Hausa">
                        Hausa
                    </a>
                </li>
            </ul>
        </div>
        
        <div class="row">
            <!-- Filters Column -->
            <div class="col-lg-3 mb-4">
                <div class="card filter-card shadow-sm">
                    <div class="card-header bg-primary text-white">
                        <h5 class="mb-0"><i class="bi bi-funnel me-2"></i><?= t('Filters', 'Tacewa') ?></h5>
                    </div>
                    <div class="card-body">
                        <form method="GET" action="browse.php">
                            <!-- Hidden fields to preserve other filters when changing sort -->
                            <input type="hidden" name="sort" value="<?= htmlspecialchars($sort) ?>">
                            
                            <!-- Search -->
                            <div class="mb-3">
                                <label for="search" class="form-label"><?= t('Search', 'Bincike') ?></label>
                                <div class="input-group">
                                    <span class="input-group-text"><i class="bi bi-search"></i></span>
                                    <input type="text" class="form-control" id="search" name="search" 
                                           value="<?= htmlspecialchars($search) ?>" placeholder="<?= t('Search listings...', 'Bincika lissafin...') ?>">
                                </div>
                            </div>
                            
                            <!-- Category -->
                            <div class="mb-3">
                                <label for="category" class="form-label"><?= t('Category', 'Rukuni') ?></label>
                                <select class="form-select" id="category" name="category">
                                    <option value="0"><?= t('All Categories', 'Duk Rukunoni') ?></option>
                                    <?php foreach ($categories as $cat): ?>
                                        <option value="<?= $cat['id'] ?>" <?= $cat['id'] == $category_id ? 'selected' : '' ?>>
                                            <?= htmlspecialchars($cat['name']) ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            
                            <!-- Price Range -->
                            <div class="mb-3">
                                <label class="form-label"><?= t('Price Range', 'Farashin Kewayon') ?></label>
                                <div class="row g-2">
                                    <div class="col-6">
                                        <input type="number" class="form-control" name="min_price" 
                                               placeholder="<?= t('Min', 'Mafi ƙanƙanta') ?>" value="<?= htmlspecialchars($min_price ?? '') ?>">
                                    </div>
                                    <div class="col-6">
                                        <input type="number" class="form-control" name="max_price" 
                                               placeholder="<?= t('Max', 'Mafi girma') ?>" value="<?= htmlspecialchars($max_price ?? '') ?>">
                                    </div>
                                </div>
                            </div>
                            
                            <!-- Health Status -->
                            <div class="mb-3">
                                <label for="health_status" class="form-label"><?= t('Health Status', 'Matsayin Lafiya') ?></label>
                                <select class="form-select" id="health_status" name="health_status">
                                    <option value=""><?= t('Any Status', 'Kowane Matsayi') ?></option>
                                    <?php foreach ($health_statuses as $status): ?>
                                        <option value="<?= $status ?>" <?= $health_status == $status ? 'selected' : '' ?>>
                                            <?= $status ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            
                            <button type="submit" class="btn btn-primary w-100">
                                <i class="bi bi-filter me-2"></i> <?= t('Apply Filters', 'Aiwatar da Tacewa') ?>
                            </button>
                            <?php if ($category_id > 0 || !empty($search) || $min_price !== null || $max_price !== null || !empty($health_status)): ?>
                                <a href="browse.php" class="btn btn-outline-secondary w-100 mt-2">
                                    <i class="bi bi-x-circle me-2"></i> <?= t('Clear Filters', 'Share Tacewa') ?>
                                </a>
                            <?php endif; ?>
                        </form>
                    </div>
                </div>
            </div>
            
            <!-- Listings Column -->
            <div class="col-lg-9">
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <h2 class="mb-0">
                        <?php if ($category_name): ?>
                            <i class="bi bi-tag me-2"></i><?= $category_name ?>
                        <?php else: ?>
                            <i class="bi bi-grid me-2"></i><?= t('Available Livestock', 'Dabbobi Masu Samuwa') ?>
                        <?php endif; ?>
                        
                        <!-- Show active filter count -->
                        <?php 
                        $filter_count = 0;
                        if ($category_id > 0) $filter_count++;
                        if (!empty($search)) $filter_count++;
                        if ($min_price !== null) $filter_count++;
                        if ($max_price !== null) $filter_count++;
                        if (!empty($health_status)) $filter_count++;
                        
                        if ($filter_count > 0): ?>
                            <span class="filter-badge"><?= $filter_count ?> <?= t('filter(s) applied', 'tacewa(n) da aka yi amfani da su') ?></span>
                        <?php endif; ?>
                    </h2>
                    
                    <div class="d-flex">
                        <!-- Sort Dropdown -->
                        <div class="dropdown me-2">
                            <button class="btn btn-outline-secondary dropdown-toggle sort-dropdown" type="button" 
                                    id="sortDropdown" data-bs-toggle="dropdown" aria-expanded="false">
                                <i class="bi bi-sort-down me-1"></i>
                                <?= 
                                    $sort == 'newest' ? t('Newest', 'Sabuwa') :
                                    ($sort == 'oldest' ? t('Oldest', 'Tsoho') :
                                    ($sort == 'price_asc' ? t('Price: Low to High', 'Farashi: Ƙasa zuwa Sama') :
                                    ($sort == 'price_desc' ? t('Price: High to Low', 'Farashi: Sama zuwa Ƙasa') : 
                                    ucfirst(str_replace('_', ' ', $sort)))))
                                ?>
                            </button>
                            <ul class="dropdown-menu" aria-labelledby="sortDropdown">
                                <li><a class="dropdown-item <?= $sort == 'newest' ? 'active' : '' ?>" 
                                       href="browse.php<?= buildQueryString(['sort']) ?>&sort=newest"><?= t('Newest', 'Sabuwa') ?></a></li>
                                <li><a class="dropdown-item <?= $sort == 'oldest' ? 'active' : '' ?>" 
                                       href="browse.php<?= buildQueryString(['sort']) ?>&sort=oldest"><?= t('Oldest', 'Tsoho') ?></a></li>
                                <li><hr class="dropdown-divider"></li>
                                <li><a class="dropdown-item <?= $sort == 'price_asc' ? 'active' : '' ?>" 
                                       href="browse.php<?= buildQueryString(['sort']) ?>&sort=price_asc"><?= t('Price: Low to High', 'Farashi: Ƙasa zuwa Sama') ?></a></li>
                                <li><a class="dropdown-item <?= $sort == 'price_desc' ? 'active' : '' ?>" 
                                       href="browse.php<?= buildQueryString(['sort']) ?>&sort=price_desc"><?= t('Price: High to Low', 'Farashi: Sama zuwa Ƙasa') ?></a></li>
                            </ul>
                        </div>
                        
                        <?php if (isset($_SESSION['user_id']) && $_SESSION['role'] == 'seller'): ?>
                            <a href="seller/add_livestock.php" class="btn btn-success">
                                <i class="bi bi-plus-circle me-2"></i><?= t('Add Listing', 'Ƙara Lissafi') ?>
                            </a>
                        <?php endif; ?>
                    </div>
                </div>
                
                <div id="livestock-container" class="row row-cols-1 row-cols-md-2 row-cols-lg-3 g-4">
                    <!-- Listings will be populated by JavaScript -->
                </div>
                
                <!-- Pagination -->
                <nav aria-label="Page navigation" class="mt-4" id="pagination-container">
                    <ul class="pagination justify-content-center">
                        <!-- Pagination will be populated by JavaScript -->
                    </ul>
                </nav>
            </div>
        </div>
    </div>

    <!-- Footer -->
    <?php include 'includes/footer.php'; ?>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Initialize tooltips
        var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
        var tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
            return new bootstrap.Tooltip(tooltipTriggerEl);
        });
        
        // Price range validation
        document.querySelector('form').addEventListener('submit', function(e) {
            const minPrice = parseFloat(document.querySelector('input[name="min_price"]').value);
            const maxPrice = parseFloat(document.querySelector('input[name="max_price"]').value);
            
            if (!isNaN(minPrice) && !isNaN(maxPrice) && minPrice > maxPrice) {
                alert('<?= t("Minimum price cannot be greater than maximum price", "Farashin mafi ƙanƙanta ba zai iya zama mafi girma ba") ?>');
                e.preventDefault();
            }
        });
        
        // Update hidden sort field when dropdown is used
        document.querySelectorAll('.dropdown-menu a').forEach(link => {
            link.addEventListener('click', function(e) {
                document.querySelector('input[name="sort"]').value = this.getAttribute('href').split('sort=')[1];
            });
        });

        // JavaScript Pagination
        const livestockData = <?= $livestock_json ?>;
        const itemsPerPage = <?= $per_page ?>;
        const totalPages = <?= $total_pages ?>;
        let currentPage = new URLSearchParams(window.location.search).get('page') ? parseInt(new URLSearchParams(window.location.search).get('page')) : 1;

        function formatDate(dateString) {
            const date = new Date(dateString);
            return date.toLocaleDateString('en-US', { month: 'short', day: 'numeric', year: 'numeric' });
        }

        function renderLivestock(page) {
            const container = document.getElementById('livestock-container');
            container.innerHTML = '';

            if (livestockData.length === 0) {
                container.innerHTML = `
                    <div class="alert alert-info text-center py-4">
                        <i class="bi bi-info-circle fs-4 me-2"></i>
                        <h4 class="d-inline"><?= t('No livestock found matching your criteria', 'Babu dabbobi da suka dace da kaidojinku') ?></h4>
                        <p class="mt-2"><?= t('Try adjusting your filters or', 'Gwada daidaita tacewarku ko') ?> <a href="browse.php"><?= t('browse all listings', 'bincika duk lissafin') ?></a></p>
                    </div>
                `;
                document.getElementById('pagination-container').style.display = 'none';
                return;
            }

            const start = (page - 1) * itemsPerPage;
            const end = start + itemsPerPage;
            const paginatedItems = livestockData.slice(start, end);

            paginatedItems.forEach(animal => {
                const cardHtml = `
                    <div class="col">
                        <div class="card livestock-card h-100">
                            <div class="position-relative">
                                ${animal.image_path && animal.image_path !== 'images/dog.png' ? 
                                    `<img src="${animal.image_path}" class="card-img-top livestock-img" alt="${animal.title}" onerror="this.onerror=null; this.src='images/dog.png'">` :
                                    `<div class="livestock-img default-image"><i class="bi bi-image"></i></div>`
                                }
                                <span class="price-tag">₦${Number(animal.price).toFixed(2)}</span>
                                <span class="badge health-badge 
                                    ${animal.health_status === 'Healthy' ? 'bg-success' : 
                                      animal.health_status === 'Minimally Strong' ? 'bg-warning text-dark' : 'bg-danger'}">
                                    ${animal.health_status}
                                </span>
                            </div>
                            <div class="card-body">
                                <h5 class="card-title">${animal.title}</h5>
                                <p class="card-text text-muted">
                                    <i class="bi bi-person me-1"></i>${animal.seller_name}<br>
                                    <i class="bi bi-clock me-1"></i>${formatDate(animal.posted_at)}
                                </p>
                                <ul class="list-group list-group-flush mb-3">
                                    <li class="list-group-item">
                                        <i class="bi bi-tag me-2"></i>${animal.category_name}
                                    </li>
                                    <li class="list-group-item">
                                        <i class="bi bi-calendar me-2"></i>${animal.age} <?= t('months old', 'watanni') ?>
                                    </li>
                                                                   </ul>
                            </div>
                            <div class="card-footer bg-transparent">
                                <a href="livestock_details.php?id=${animal.id}" class="btn btn-primary w-100">
                                    <i class="bi bi-eye me-2"></i><?= t('View Details', 'Duba Bayanai') ?>
                                </a>
                            </div>
                        </div>
                    </div>
                `;
                container.innerHTML += cardHtml;
            });

            renderPagination();
        }

        function renderPagination() {
            const paginationContainer = document.getElementById('pagination-container').querySelector('.pagination');
            paginationContainer.innerHTML = '';

            if (totalPages <= 1) {
                document.getElementById('pagination-container').style.display = 'none';
                return;
            }

            paginationContainer.innerHTML += `
                <li class="page-item ${currentPage <= 1 ? 'disabled' : ''}">
                    <a class="page-link" href="#" data-page="${currentPage - 1}"><?= t('Previous', 'Baya') ?></a>
                </li>
            `;

            for (let i = 1; i <= totalPages; i++) {
                paginationContainer.innerHTML += `
                    <li class="page-item ${i === currentPage ? 'active' : ''}">
                        <a class="page-link" href="#" data-page="${i}">${i}</a>
                    </li>
                `;
            }

            paginationContainer.innerHTML += `
                <li class="page-item ${currentPage >= totalPages ? 'disabled' : ''}">
                    <a class="page-link" href="#" data-page="${currentPage + 1}"><?= t('Next', 'Gaba') ?></a>
                </li>
            `;

            // Add event listeners for pagination links
            document.querySelectorAll('.page-link').forEach(link => {
                link.addEventListener('click', function(e) {
                    e.preventDefault();
                    const newPage = parseInt(this.getAttribute('data-page'));
                    if (newPage >= 1 && newPage <= totalPages) {
                        currentPage = newPage;
                        renderLivestock(currentPage);
                        // Update URL without reloading
                        const url = new URL(window.location);
                        url.searchParams.set('page', currentPage);
                        window.history.pushState({}, '', url);
                    }
                });
            });
        }

        // Initial render
        renderLivestock(currentPage);
    </script>
</body>
</html>