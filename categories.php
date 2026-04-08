<?php
require_once 'config.php';

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Initialize language session
if (!isset($_SESSION['language'])) {
    $_SESSION['language'] = 'english';
}

// Handle language switch
if (isset($_GET['lang'])) {
    $_SESSION['language'] = ($_GET['lang'] === 'hausa') ? 'hausa' : 'english';
    // Redirect to same page without lang parameter to avoid duplicate parameters
    $current_url = strtok($_SERVER['REQUEST_URI'], '?');
    header('Location: ' . $current_url);
    exit;
}

// Language translations
$translations = [
    'english' => [
        'trusted_marketplace' => 'Trusted Livestock Marketplace',
        'find_quality_animals' => 'Find quality animals from trusted sellers across the country',
        'search_placeholder' => 'Search for cattle, goats, poultry...',
        'search_button' => 'Search',
        'active_listings' => 'Active Listings',
        'happy_farmers' => 'Happy Farmers',
        'regions_covered' => 'Regions Covered',
        'browse_by_category' => 'Browse by Category',
        'select_category' => 'Select a category to view available livestock',
        'view_animals' => 'View Animals',
        'listings' => 'listings',
        'no_categories' => 'No Categories Available',
        'no_categories_message' => 'There are currently no active livestock categories. Please check back later.',
        'default_image_alt' => 'Default category image'
    ],
    'hausa' => [
        'trusted_marketplace' => 'Kasuwar Dabbobi Amince',
        'find_quality_animals' => 'Nemi ingantattun dabbobi daga amintattun masu sayarwa a duk fadin kasar',
        'search_placeholder' => 'Nemo shanu, awaki, kaji...',
        'search_button' => 'Bincika',
        'active_listings' => 'Lissafin Aiki',
        'happy_farmers' => 'Manoma Farin Ciki',
        'regions_covered' => 'Yankunan da aka Rufe',
        'browse_by_category' => 'Bincika ta Rukuni',
        'select_category' => 'Zabi rukuni don duba dabbobin da ake samu',
        'view_animals' => 'Dubi Dabbobi',
        'listings' => 'lissafin',
        'no_categories' => 'Babu Rukuni da Ake Samu',
        'no_categories_message' => 'A halin yanzu babu rukunin dabbobi da ake aiki. Da fatan za a sake duba daga baya.',
        'default_image_alt' => 'Tsohon hoton rukuni'
    ]
];

// Get current language
$current_language = $_SESSION['language'];

// Get all active categories with count of active listings
$categories = $pdo->query("
    SELECT c.*, COUNT(l.id) as listing_count 
    FROM categories c
    LEFT JOIN listings l ON c.id = l.category_id AND l.status = 'active'
    WHERE c.is_active = 1 
    GROUP BY c.id
    ORDER BY c.name
")->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>FarmHub - <?= $current_language === 'hausa' ? 'Kasuwar Dabbobi' : 'Livestock Marketplace' ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        :root {
            --primary-color: #4e73df;
            --secondary-color: #f8f9fc;
            --accent-color: #2e59d9;
            --success-color: #1cc88a;
            --light-color: #ffffff;
        }
        
        .hero-section {
            background: linear-gradient(135deg, rgba(78, 115, 223, 0.85) 0%, rgba(46, 89, 217, 0.8) 100%), url('https://images.unsplash.com/photo-1544551763-46a013bb70d5?ixlib=rb-4.0.3&ixid=M3wxMjA3fDB8MHxwaG90by1wYWdlfHx8fGVufDB8fHx8fA%3D%3D&auto=format&fit=crop&w=2070&q=80');
            background-size: cover;
            background-position: center;
            background-attachment: fixed;
            color: white;
            padding: 120px 0 80px;
            margin-bottom: 50px;
            position: relative;
            overflow: hidden;
        }
        
        .hero-section::before {
            content: '';
            position: absolute;
            bottom: 0;
            left: 0;
            width: 100%;
            height: 100px;
            background: linear-gradient(to bottom, transparent, var(--secondary-color));
            z-index: 1;
        }
        
        .hero-content {
            position: relative;
            z-index: 2;
        }
        
        .hero-badge {
            background: rgba(255, 255, 255, 0.2);
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 255, 255, 0.3);
            border-radius: 50px;
            padding: 8px 20px;
            font-size: 0.9rem;
            margin-bottom: 20px;
            display: inline-block;
        }
        
        .hero-title {
            font-size: 3.5rem;
            font-weight: 800;
            margin-bottom: 1.5rem;
            text-shadow: 2px 2px 4px rgba(0, 0, 0, 0.3);
            background: linear-gradient(45deg, #fff, #e3f2fd);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }
        
        .hero-subtitle {
            font-size: 1.3rem;
            margin-bottom: 2.5rem;
            opacity: 0.95;
            max-width: 600px;
            margin-left: auto;
            margin-right: auto;
        }
        
        .search-container {
            max-width: 700px;
            margin: 0 auto;
            position: relative;
        }
        
        .search-box {
            background: rgba(255, 255, 255, 0.15);
            backdrop-filter: blur(15px);
            border: 1px solid rgba(255, 255, 255, 0.3);
            border-radius: 15px;
            padding: 5px;
            box-shadow: 0 8px 32px rgba(0, 0, 0, 0.1);
        }
        
        .search-input {
            background: transparent;
            border: none;
            color: white;
            font-size: 1.1rem;
            padding: 15px 20px;
        }
        
        .search-input::placeholder {
            color: rgba(255, 255, 255, 0.8);
        }
        
        .search-input:focus {
            background: transparent;
            border: none;
            box-shadow: none;
            color: white;
        }
        
        .search-btn {
            background: var(--light-color);
            border: none;
            border-radius: 12px;
            padding: 15px 30px;
            font-weight: 600;
            color: var(--primary-color);
            transition: all 0.3s ease;
        }
        
        .search-btn:hover {
            background: #f0f0f0;
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(255, 255, 255, 0.3);
            color: var(--accent-color);
        }
        
        .hero-stats {
            margin-top: 60px;
            display: flex;
            justify-content: center;
            gap: 40px;
            flex-wrap: wrap;
        }
        
        .stat-item {
            text-align: center;
        }
        
        .stat-number {
            font-size: 2rem;
            font-weight: 700;
            color: var(--light-color);
            display: block;
        }
        
        .stat-label {
            font-size: 0.9rem;
            opacity: 0.9;
        }
        
        .language-selector {
            position: absolute;
            top: 30px;
            right: 30px;
            z-index: 1000;
        }
        
        .language-btn {
            background: rgba(255, 255, 255, 0.15);
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 255, 255, 0.3);
            color: white;
            border-radius: 10px;
            padding: 8px 15px;
            transition: all 0.3s ease;
        }
        
        .language-btn:hover {
            background: rgba(255, 255, 255, 0.25);
            transform: translateY(-2px);
        }
        
        .category-card {
            transition: all 0.3s ease;
            border: none;
            border-radius: 15px;
            overflow: hidden;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
            margin-bottom: 25px;
            border: 1px solid rgba(0, 0, 0, 0.05);
        }
        
        .category-card:hover {
            transform: translateY(-10px);
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.15);
        }
        
        .category-img-container {
            height: 200px;
            overflow: hidden;
            position: relative;
        }
        
        .category-img {
            height: 100%;
            width: 100%;
            object-fit: cover;
            transition: transform 0.5s ease;
        }
        
        .category-card:hover .category-img {
            transform: scale(1.1);
        }
        
        .category-badge {
            position: absolute;
            top: 10px;
            right: 10px;
            background: linear-gradient(45deg, var(--primary-color), var(--accent-color));
            color: white;
            border-radius: 20px;
            padding: 5px 12px;
            font-size: 0.8rem;
            font-weight: 600;
            box-shadow: 0 4px 15px rgba(78, 115, 223, 0.3);
        }
        
        .category-title {
            font-weight: 700;
            color: var(--primary-color);
            margin-bottom: 10px;
            font-size: 1.3rem;
        }
        
        .view-btn {
            background: linear-gradient(45deg, var(--primary-color), var(--accent-color));
            border: none;
            padding: 10px 25px;
            font-weight: 600;
            border-radius: 8px;
            transition: all 0.3s;
            box-shadow: 0 4px 15px rgba(78, 115, 223, 0.3);
        }
        
        .view-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(78, 115, 223, 0.4);
        }
        
        .no-categories {
            padding: 50px 0;
            text-align: center;
        }
        
        .category-description {
            color: #6c757d;
            font-size: 0.95rem;
            margin-bottom: 15px;
            line-height: 1.5;
        }
        
        .language-flag {
            width: 20px;
            height: 15px;
            margin-right: 5px;
            border: 1px solid #ddd;
        }
        
        @media (max-width: 768px) {
            .hero-title {
                font-size: 2.5rem;
            }
            
            .hero-subtitle {
                font-size: 1.1rem;
            }
            
            .hero-stats {
                gap: 20px;
            }
            
            .stat-number {
                font-size: 1.5rem;
            }
        }
    </style>
</head>
<body>
    <?php 
    // Debug info - remove in production
    if (isset($_GET['debug'])) {
        echo "<div style='background: #f8d7da; padding: 10px; margin: 10px; border-radius: 5px;'>";
        echo "<strong>Debug Info:</strong><br>";
        echo "Current Language: " . $current_language . "<br>";
        echo "Session Language: " . ($_SESSION['language'] ?? 'not set') . "<br>";
        echo "GET Parameters: " . print_r($_GET, true) . "<br>";
        echo "</div>";
    }
    ?>
    
    <?php include 'includes/nav.php'; ?>
    
    <section class="hero-section">
        <div class="language-selector dropdown">
            <button class="btn language-btn dropdown-toggle" type="button" id="languageDropdown" data-bs-toggle="dropdown">
                <img src="data:image/svg+xml;base64,<?= base64_encode($current_language == 'hausa' ? '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 3 2"><path fill="#009A00" d="M0 0h3v2H0z"/><path fill="#FFF" d="M0 0h3v1H0z"/><path fill="#DA1A30" d="M0 0h1v2H0z"/></svg>' : '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 60 30"><path fill="#012169" d="M0 0v30h60V0z"/><path fill="#FFF" d="M0 0v30h60V0z" transform="scale(2)"/><path fill="#C8102E" d="M0 0v30h60V0z" transform="scale(3)"/><path fill="#FFF" d="M0 0l60 30m0-30L0 30" stroke="#FFF" stroke-width="6"/><path fill="#C8102E" d="M0 0l60 30m0-30L0 30" stroke="#C8102E" stroke-width="4"/></svg>') ?>" class="language-flag" alt="<?= $current_language ?>">
                <?= $current_language == 'hausa' ? 'Hausa' : 'English' ?>
            </button>
            <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="languageDropdown">
                <li>
                    <a class="dropdown-item" href="?lang=english">
                        <img src="data:image/svg+xml;base64,PHN2ZyB4bWxucz0iaHR0cDovL3d3dy53My5vcmcvMjAwMC9zdmciIHZpZXdCb3g9IjAgMCA2MCAzMCI+PHBhdGggZmlsbD0iIzAxMjE2OSIgZD0iTTAgMHYzMGg2MFYweiIvPjxwYXRoIGZpbGw9IiNGRkYiIGQ9Ik0wIDB2MzBoNjBWMHoiIHRyYW5zZm9ybT0ic2NhbGUoMikiLz48cGF0aCBmaWxsPSIjQzgxMDJFIiBkPSJNMCAwdjMwaDYwVjB6IiB0cmFuc2Zvcm09InNjYWxlKDMpIi8+PHBhdGggZmlsbD0iI0ZGRiIgZD0iTTAgMGw2MCAzMG0wLTMwTDAgMzAiIHN0cm9rZT0iI0ZGRiIgc3Ryb2tlLXdpZHRoPSI2Ii8+PHBhdGggZmlsbD0iI0M4MTAyRSIgZD0iTTAgMGw2MCAzMG0wLTMwTDAgMzEiIHN0cm9rZT0iI0M4MTAyRSIgc3Ryb2tlLXdpZHRoPSI0Ii8+PC9zdmc+" class="language-flag me-2" alt="English">
                        English
                    </a>
                </li>
                <li>
                    <a class="dropdown-item" href="?lang=hausa">
                        <img src="data:image/svg+xml;base64,PHN2ZyB4bWxucz0iaHR0cDovL3d3dy53My5vcmcvMjAwMC9zdmciIHZpZXdCb3g9IjAgMCAzIDIiPjxwYXRoIGZpbGw9IiMwMDlBMDAiIGQ9Ik0wIDBoM3YySDB6Ii8+PHBhdGggZmlsbD0iI0ZGRiIgZD0iTTAgMGgzdjFIMHoiLz48cGF0aCBmaWxsPSIjREExQTMwIiBkPSJNMCAwaDF2MkgweiIvPjwvc3ZnPg==" class="language-flag me-2" alt="Hausa">
                        Hausa
                    </a>
                </li>
            </ul>
        </div>
        
        <div class="container text-center hero-content">
            <div class="hero-badge">
                <i class="fas fa-paw me-2"></i> 
                <?= $translations[$current_language]['trusted_marketplace'] ?>
            </div>
            <h1 class="hero-title">FarmHub</h1>
            <p class="hero-subtitle">
                <?= $translations[$current_language]['find_quality_animals'] ?>
            </p>
            
            <div class="search-container">
                <div class="search-box">
                    <div class="input-group">
                        <input type="text" class="form-control search-input" placeholder="<?= $translations[$current_language]['search_placeholder'] ?>">
                        <button class="btn search-btn" type="button">
                            <i class="fas fa-search me-2"></i> 
                            <?= $translations[$current_language]['search_button'] ?>
                        </button>
                    </div>
                </div>
            </div>
            
               </div>
    </section>
    
    <section class="py-5">
        <div class="container">
            <div class="text-center mb-5">
                <h2 class="fw-bold">
                    <?= $translations[$current_language]['browse_by_category'] ?>
                </h2>
                <p class="lead text-muted">
                    <?= $translations[$current_language]['select_category'] ?>
                </p>
            </div>
            
            <?php if (empty($categories)): ?>
                <div class="no-categories">
                    <div class="alert alert-info">
                        <h4 class="alert-heading">
                            <?= $translations[$current_language]['no_categories'] ?>
                        </h4>
                        <p>
                            <?= $translations[$current_language]['no_categories_message'] ?>
                        </p>
                    </div>
                </div>
            <?php else: ?>
                <div class="row">
                    <?php foreach ($categories as $category): ?>
                        <div class="col-md-6 col-lg-4">
                            <div class="card category-card h-100">
                                <div class="category-img-container">
                                    <?php if ($category['image_path']): ?>
                                        <img src="images/ani1.png" class="category-img" alt="<?= htmlspecialchars($category['name']) ?>">
                                    <?php else: ?>
                                        <img src="images/pawprint.png" class="category-img" alt="<?= $translations[$current_language]['default_image_alt'] ?>">
                                    <?php endif; ?>
                                    <span class="category-badge">
                                        <i class="fas fa-paw me-1"></i> 
                                        <?= $category['listing_count'] ?> 
                                        <?= $translations[$current_language]['listings'] ?>
                                    </span>
                                </div>
                                <div class="card-body">
                                    <h3 class="category-title"><?= htmlspecialchars($category['name']) ?></h3>
                                    <p class="category-description">
                                        <?= htmlspecialchars($category['description']) ?>
                                    </p>
                                    <div class="d-grid">
                                        <a href="browse.php?category=<?= $category['id'] ?>" class="btn view-btn">
                                            <i class="fas fa-eye me-2"></i> 
                                            <?= $translations[$current_language]['view_animals'] ?>
                                        </a>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </section>
    
    <?php include 'includes/footer.php'; ?>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Simple animation for cards when page loads
        document.addEventListener('DOMContentLoaded', function() {
            const cards = document.querySelectorAll('.category-card');
            cards.forEach((card, index) => {
                card.style.opacity = '0';
                card.style.transform = 'translateY(20px)';
                setTimeout(() => {
                    card.style.transition = 'all 0.5s ease';
                    card.style.opacity = '1';
                    card.style.transform = 'translateY(0)';
                }, index * 100);
            });

            // Add search functionality
            const searchInput = document.querySelector('.search-input');
            const searchButton = document.querySelector('.search-btn');
            
            function performSearch() {
                const query = searchInput.value.trim();
                if (query) {
                    window.location.href = `browse.php?search=${encodeURIComponent(query)}`;
                }
            }
            
            searchButton.addEventListener('click', performSearch);
            searchInput.addEventListener('keypress', function(e) {
                if (e.key === 'Enter') {
                    performSearch();
                }
            });
        });
    </script>
</body>
</html>