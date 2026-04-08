<?php
// Start session only if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once '../config.php';

// Check authentication and role
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'seller') {
    header("Location: ../login.php");
    exit();
}

// Handle language switching
if (isset($_GET['lang'])) {
    $allowed_languages = ['english', 'hausa'];
    $lang = $_GET['lang'];
    
    if (in_array($lang, $allowed_languages)) {
        $_SESSION['language'] = $lang;
    }
    header("Location: guide.php");
    exit();
}

// Set default language if not set
if (!isset($_SESSION['language'])) {
    $_SESSION['language'] = 'english';
}

// Translation function
function t($english, $hausa) {
    return ($_SESSION['language'] == 'hausa') ? $hausa : $english;
}

$user_id = $_SESSION['user_id'];

// Get seller profile
$stmt = $pdo->prepare("SELECT u.*, sp.company_name, sp.verification_status 
                      FROM users u 
                      JOIN seller_profiles sp ON u.id = sp.user_id 
                      WHERE u.id = ?");
$stmt->execute([$user_id]);
$seller = $stmt->fetch(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="<?= $_SESSION['language'] == 'hausa' ? 'ha' : 'en' ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= t('Seller Guide - FarmHub', 'Jagorar Mai Sayarwa - FarmHub') ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    <style>
        :root {
            --primary-color: #4e73df;
            --secondary-color: #f8f9fc;
            --accent-blue: #4e73df;
            --accent-dark-blue: #224abe;
            --accent-light-blue: #e3ebff;
        }
        
        .guide-hero {
            background: linear-gradient(135deg, var(--accent-blue), var(--accent-dark-blue));
            color: white;
            padding: 60px 0;
            margin-bottom: 40px;
        }
        
        .step-card {
            border-left: 4px solid var(--accent-blue);
            transition: transform 0.3s ease, box-shadow 0.3s ease;
            margin-bottom: 20px;
        }
        
        .step-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 25px rgba(0,0,0,0.1);
        }
        
        .step-number {
            background: var(--accent-blue);
            color: white;
            width: 40px;
            height: 40px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: bold;
            margin-right: 15px;
        }
        
        .guide-section {
            margin-bottom: 50px;
        }
        
        .section-title {
            border-bottom: 2px solid var(--accent-blue);
            padding-bottom: 10px;
            margin-bottom: 30px;
        }
        
        .tip-card {
            background: var(--accent-light-blue);
            border-radius: 10px;
            padding: 20px;
            margin-bottom: 20px;
        }
        
        .feature-icon {
            font-size: 2rem;
            color: var(--accent-blue);
            margin-bottom: 15px;
        }
        
        .verification-steps {
            background: #f8f9fa;
            border-radius: 10px;
            padding: 30px;
            margin: 30px 0;
        }
        
        .nav-pills .nav-link.active {
            background-color: var(--accent-blue);
        }
        
        .language-selector {
            position: absolute;
            top: 15px;
            right: 15px;
            z-index: 1000;
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
    <!-- Include Navigation -->
    <?php include 'includes/seller_nav.php'; ?>
    
    <!-- Hero Section -->
    <section class="guide-hero">
        <div class="container">
            <div class="row">
                <div class="col-lg-8 mx-auto text-center">
                    <h1 class="display-4 fw-bold mb-4">
                        <i class="bi bi-journal-bookmark-fill me-3"></i>
                        <?= t('Seller Success Guide', 'Jagorar Nasarar Mai Sayarwa') ?>
                    </h1>
                    <p class="lead mb-4">
                        <?= t('Everything you need to know to succeed as a seller on FarmHub', 
                              'Duk abin da kuke bukata don yin nasara a matsayin mai sayarwa akan FarmHub') ?>
                    </p>
                    <div class="d-flex justify-content-center gap-3 flex-wrap">
                        <span class="badge bg-light text-dark fs-6 p-3">
                            <i class="bi bi-check-circle-fill text-success me-2"></i>
                            <?= t('Step-by-Step Process', 'Tsari Mataki-mataki') ?>
                        </span>
                        <span class="badge bg-light text-dark fs-6 p-3">
                            <i class="bi bi-lightbulb-fill text-warning me-2"></i>
                            <?= t('Pro Tips', 'Shawarwari na Kwararru') ?>
                        </span>
                        <span class="badge bg-light text-dark fs-6 p-3">
                            <i class="bi bi-shield-check text-primary me-2"></i>
                            <?= t('Best Practices', 'Mafi kyawun Ayyuka') ?>
                        </span>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <div class="container">
        <!-- Quick Navigation -->
        <div class="row mb-5">
            <div class="col-12">
                <div class="card">
                    <div class="card-body">
                        <h5 class="card-title">
                            <i class="bi bi-compass me-2"></i>
                            <?= t('Quick Navigation', 'Navigation Sauri') ?>
                        </h5>
                        <div class="nav nav-pills justify-content-center flex-wrap">
                            <a class="nav-link mx-2 my-1" href="#getting-started">
                                <i class="bi bi-play-circle me-1"></i>
                                <?= t('Getting Started', 'Fara Farawa') ?>
                            </a>
                            <a class="nav-link mx-2 my-1" href="#verification">
                                <i class="bi bi-patch-check me-1"></i>
                                <?= t('Verification', 'Tabbatarwa') ?>
                            </a>
                            <a class="nav-link mx-2 my-1" href="#listing">
                                <i class="bi bi-plus-circle me-1"></i>
                                <?= t('Creating Listings', 'Æ˜irÆ˜irar Lissafi') ?>
                            </a>
                            <a class="nav-link mx-2 my-1" href="#selling">
                                <i class="bi bi-currency-dollar me-1"></i>
                                <?= t('Selling Process', 'Tsarin Sayarwa') ?>
                            </a>
                            <a class="nav-link mx-2 my-1" href="#best-practices">
                                <i class="bi bi-star me-1"></i>
                                <?= t('Best Practices', 'Mafi kyawun Ayyuka') ?>
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Getting Started Section -->
        <section id="getting-started" class="guide-section">
            <h2 class="section-title">
                <i class="bi bi-play-circle-fill me-2"></i>
                <?= t('Getting Started', 'Fara Farawa') ?>
            </h2>
            
            <div class="row">
                <div class="col-lg-6 mb-4">
                    <div class="card step-card h-100">
                        <div class="card-body">
                            <div class="d-flex align-items-center mb-3">
                                <div class="step-number">1</div>
                                <h4 class="card-title mb-0"><?= t('Complete Your Profile', 'Cika Bayanin Kai') ?></h4>
                            </div>
                            <p class="card-text">
                                <?= t('Fill out your seller profile completely with accurate information. Include your company name, contact details, and business registration information.', 
                                      'Cika cikakken bayanin mai sayarwa tare da ingantaccen bayani. Haɗa da sunan kamfanin ku, cikakkun bayanan tuntuɓar, da bayanin rajistar kasuwancin ku.') ?>
                            </p>
                            <ul>
                                <li><?= t('Upload a professional profile picture', 'Loda hoton bayanan mai ƙwararru') ?></li>
                                <li><?= t('Provide valid contact information', 'Bayar da ingantaccen bayanin tuntuɓar') ?></li>
                                <li><?= t('Add your business location', 'Æ˜ara wurin kasuwancin ku') ?></li>
                            </ul>
                            <a href="profile.php" class="btn btn-primary">
                                <i class="bi bi-person-gear me-1"></i>
                                <?= t('Update Profile', 'Sabunta Bayanin Kai') ?>
                            </a>
                        </div>
                    </div>
                </div>
                
                <div class="col-lg-6 mb-4">
                    <div class="card step-card h-100">
                        <div class="card-body">
                            <div class="d-flex align-items-center mb-3">
                                <div class="step-number">2</div>
                                <h4 class="card-title mb-0"><?= t('Get Verified', 'Samun Tabbatarwa') ?></h4>
                            </div>
                            <p class="card-text">
                                <?= t('Complete the seller verification process to unlock all selling features. This builds trust with buyers and increases your sales potential.', 
                                      'Cika tsarin tabbatarwar mai sayarwa don buɗe duk fasalin sayarwa. Wannan yana gina aminci tare da masu siye kuma yana ƙara yuwuwar siyarwar ku.') ?>
                            </p>
                            <div class="alert alert-info">
                                <i class="bi bi-info-circle me-2"></i>
                                <strong><?= t('Verification Required:', 'Ana Bukatar Tabbatarwa:') ?></strong>
                                <?= t('You need to be verified to list livestock and receive orders.', 
                                      'Kuna buƙatar tabbatarwa don jera dabbobi da karbar umarni.') ?>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <!-- Verification Process Section -->
        <section id="verification" class="guide-section">
            <h2 class="section-title">
                <i class="bi bi-patch-check-fill me-2"></i>
                <?= t('Seller Verification Process', 'Tsarin Tabbatarwar Mai Sayarwa') ?>
            </h2>
            
            <div class="verification-steps">
                <div class="row">
                    <div class="col-md-4 mb-4">
                        <div class="text-center">
                            <div class="feature-icon">
                                <i class="bi bi-person-badge"></i>
                            </div>
                            <h5><?= t('Submit Documents', 'Ɗora Takardu') ?></h5>
                            <p><?= t('Upload required identification and business registration documents', 
                                      'Loda buƙatattun takardun tantancewa da rajistar kasuwancin') ?></p>
                        </div>
                    </div>
                    <div class="col-md-4 mb-4">
                        <div class="text-center">
                            <div class="feature-icon">
                                <i class="bi bi-clock-history"></i>
                            </div>
                            <h5><?= t('Review Process', 'Tsarin Bita') ?></h5>
                            <p><?= t('Our team reviews your application within 2-3 business days', 
                                      'Æ˜ungiyarmu tana bitar neman ku cikin kwanaki 2-3 na kasuwanci') ?></p>
                        </div>
                    </div>
                    <div class="col-md-4 mb-4">
                        <div class="text-center">
                            <div class="feature-icon">
                                <i class="bi bi-check-all"></i>
                            </div>
                            <h5><?= t('Get Approved', 'Samun Amincewa') ?></h5>
                            <p><?= t('Start listing livestock and receiving orders immediately after approval', 
                                      'Fara jera dabbobi da karbar umarni nan da nan bayan amincewa') ?></p>
                        </div>
                    </div>
                </div>
                
                <?php if (!$seller['is_verified']): ?>
                <div class="text-center mt-4">
                    <a href="profile.php#verification" class="btn btn-success btn-lg">
                        <i class="bi bi-patch-check me-2"></i>
                        <?= t('Start Verification Process', 'Fara Tsarin Tabbatarwa') ?>
                    </a>
                </div>
                <?php else: ?>
                <div class="text-center mt-4">
                    <div class="alert alert-success">
                        <i class="bi bi-patch-check-fill me-2"></i>
                        <strong><?= t('Congratulations!', 'Taya murna!') ?></strong>
                        <?= t('Your seller account is verified and ready for business.', 
                              'Asusun mai sayarwa an tabbatar da shi kuma yana shirye don kasuwanci.') ?>
                    </div>
                </div>
                <?php endif; ?>
            </div>
        </section>

        <!-- Creating Listings Section -->
        <section id="listing" class="guide-section">
            <h2 class="section-title">
                <i class="bi bi-plus-circle-fill me-2"></i>
                <?= t('Creating Effective Listings', 'Æ˜irÆ˜irar Lissafi Masu Tasiri') ?>
            </h2>
            
            <div class="row">
                <div class="col-lg-8">
                    <div class="card">
                        <div class="card-body">
                            <h4><?= t('Listing Best Practices', 'Mafi kyawun Ayyuka na Lissafi') ?></h4>
                            
                            <div class="tip-card">
                                <h6><i class="bi bi-camera-fill text-primary me-2"></i> <?= t('High-Quality Photos', 'Hotuna Masu Inganci') ?></h6>
                                <p><?= t('Use clear, well-lit photos from multiple angles. Show the animal in good condition and highlight unique features.', 
                                          'Yi amfani da hotuna masu haske, masu haske daga kusurwoyi da yawa. Nuna dabbar cikin kyakkyawan yanayi kuma ku haskaka siffofi na musamman.') ?></p>
                            </div>
                            
                            <div class="tip-card">
                                <h6><i class="bi bi-file-text-fill text-success me-2"></i> <?= t('Detailed Descriptions', 'Cikakkun Bayanai') ?></h6>
                                <p><?= t('Include breed, age, weight, health status, vaccination history, and any special characteristics. Be honest and transparent.', 
                                          'Haɗa da iri, shekaru, nauyi, yanayin lafiya, tarihin alurar riga kafi, da kowane siffa na musamman. Ku kasance masu gaskiya da bayyana gaskiya.') ?></p>
                            </div>
                            
                            <div class="tip-card">
                                <h6><i class="bi bi-tags-fill text-warning me-2"></i> <?= t('Competitive Pricing', 'Farashi Mai Gasawa') ?></h6>
                                <p><?= t('Research market prices and set fair, competitive rates. Consider offering discounts for bulk purchases.', 
                                          'Bincika farashin kasuwa kuma saita adalci, farashi masu gasa. Yi la'akari da bayar da rangwame don siyan girma.') ?></p>
                            </div>
                            
                            <div class="tip-card">
                                <h6><i class="bi bi-geo-alt-fill text-danger me-2"></i> <?= t('Location & Delivery', 'Wuri & Bayarwa') ?></h6>
                                <p><?= t('Clearly state your location and delivery options. Be upfront about any delivery charges or pickup requirements.', 
                                          'Bayyana sarai wurin ku da zaɓuɓɓukan bayarwa. Bayyana sarai game da kowane cajin bayarwa ko buƙatun ɗauka.') ?></p>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="col-lg-4">
                    <div class="card bg-light">
                        <div class="card-body">
                            <h5><?= t('Quick Checklist', 'Lissafin Bincike Sauri') ?></h5>
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox">
                                <label class="form-check-label"><?= t('Clear photos from multiple angles', 'Sharef hotuna daga kusurwoyi da yawa') ?></label>
                            </div>
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox">
                                <label class="form-check-label"><?= t('Accurate breed and age information', 'Ingantaccen bayanin iri da shekaru') ?></label>
                            </div>
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox">
                                <label class="form-check-label"><?= t('Health and vaccination details', 'Cikakkun bayanai na lafiya da alurar riga kafi') ?></label>
                            </div>
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox">
                                <label class="form-check-label"><?= t('Fair and competitive pricing', 'Adalci da farashi masu gasa') ?></label>
                            </div>
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox">
                                <label class="form-check-label"><?= t('Clear delivery/pickup options', 'Bayyanannun zaɓuɓɓukan bayarwa/ɗauka') ?></label>
                            </div>
                            
                            <div class="mt-3">
                                <a href="add_livestock.php" class="btn btn-primary w-100" <?= !$seller['is_verified'] ? 'disabled' : '' ?>>
                                    <i class="bi bi-plus-circle me-2"></i>
                                    <?= t('Create New Listing', 'Æ˜irƙiri Sabon Lissafi') ?>
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <!-- Selling Process Section -->
        <section id="selling" class="guide-section">
            <h2 class="section-title">
                <i class="bi bi-currency-dollar me-2"></i>
                <?= t('Selling Process & Order Management', 'Tsarin Sayarwa & Gudanar da Umarni') ?>
            </h2>
            
            <div class="row">
                <div class="col-md-6 mb-4">
                    <div class="card h-100">
                        <div class="card-body">
                            <h5 class="card-title">
                                <i class="bi bi-bell-fill text-primary me-2"></i>
                                <?= t('Order Notifications', 'Sanarwar Umarni') ?>
                            </h5>
                            <p><?= t('You will receive instant notifications when:', 'Za ku karɓi sanarwar nan take lokacin:') ?></p>
                            <ul>
                                <li><?= t('A buyer places an order', 'Mai siye ya sanya oda') ?></li>
                                <li><?= t('Order status changes', 'Matsayin oda ya canza') ?></li>
                                <li><?= t('Buyer sends a message', 'Mai siye ya aika saƙo') ?></li>
                                <li><?= t('Payment is confirmed', 'An tabbatar da biyan kuɗi') ?></li>
                            </ul>
                        </div>
                    </div>
                </div>
                
                <div class="col-md-6 mb-4">
                    <div class="card h-100">
                        <div class="card-body">
                            <h5 class="card-title">
                                <i class="bi bi-clock-history text-success me-2"></i>
                                <?= t('Response Time', 'Lokacin Amsa') ?>
                            </h5>
                            <p><?= t('Quick response times improve buyer satisfaction:', 'Saurin lokutan amsa yana inganta gamsuwar mai siye:') ?></p>
                            <ul>
                                <li><?= t('Aim to respond within 2 hours', 'Yi niyya don amsa cikin sa'o'i 2') ?></li>
                                <li><?= t('Update order status promptly', 'Sabunta matsayin oda da sauri') ?></li>
                                <li><?= t('Communicate any delays immediately', 'Sanar da duk wani jinkiri nan da nan') ?></li>
                                <li><?= t('Provide tracking information when available', 'Bayar da bayanan bin sawuni idan akwai') ?></li>
                            </ul>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <!-- Best Practices Section -->
        <section id="best-practices" class="guide-section">
            <h2 class="section-title">
                <i class="bi bi-star-fill me-2"></i>
                <?= t('Seller Best Practices', 'Mafi kyawun Ayyuka na Mai Sayarwa') ?>
            </h2>
            
            <div class="row">
                <div class="col-md-4 mb-4">
                    <div class="card text-center h-100">
                        <div class="card-body">
                            <div class="feature-icon">
                                <i class="bi bi-chat-dots"></i>
                            </div>
                            <h5><?= t('Communication', 'Sadarwa') ?></h5>
                            <p><?= t('Be responsive, professional, and clear in all communications with buyers.', 
                                      'Kasance mai amsa, ƙwararren, kuma bayyananne a duk hanyoyin sadarwa tare da masu siye.') ?></p>
                        </div>
                    </div>
                </div>
                
                <div class="col-md-4 mb-4">
                    <div class="card text-center h-100">
                        <div class="card-body">
                            <div class="feature-icon">
                                <i class="bi bi-shield-check"></i>
                            </div>
                            <h5><?= t('Trust & Safety', 'Amincewa & Aminci') ?></h5>
                            <p><?= t('Always prioritize animal welfare and follow ethical farming practices.', 
                                      'Koyaushe ku ba da fifiko ga jin daɗin dabbobi kuma ku bi ka'idojin noma na ɗa'a.') ?></p>
                        </div>
                    </div>
                </div>
                
                <div class="col-md-4 mb-4">
                    <div class="card text-center h-100">
                        <div class="card-body">
                            <div class="feature-icon">
                                <i class="bi bi-award"></i>
                            </div>
                            <h5><?= t('Quality Service', 'Sabis mai Inganci') ?></h5>
                            <p><?= t('Provide excellent customer service to build reputation and receive positive reviews.', 
                                      'Bayar da ingantaccen sabis na abokin ciniki don gina suna da karɓar kyakkyawan bita.') ?></p>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="card bg-warning bg-opacity-10">
                <div class="card-body">
                    <h5><i class="bi bi-lightbulb-fill text-warning me-2"></i> <?= t('Pro Tips for Success', 'Shawarwari na Kwararru don Nasarar') ?></h5>
                    <div class="row">
                        <div class="col-md-6">
                            <ul>
                                <li><?= t('Take professional-looking photos of your livestock', 'Æ˜i hotuna masu kama da ƙwararru na dabbobinku') ?></li>
                                <li><?= t('Be honest about animal conditions and health', 'Ku kasance masu gaskiya game da yanayin dabbobi da lafiya') ?></li>
                                <li><?= t('Offer competitive but fair pricing', 'Bayar da farashi mai gasa amma na gaskiya') ?></li>
                            </ul>
                        </div>
                        <div class="col-md-6">
                            <ul>
                                <li><?= t('Respond quickly to buyer inquiries', 'Amsa da sauri ga tambayoyin mai siye') ?></li>
                                <li><?= t('Maintain high standards of animal care', 'Kula da manyan ma'auni na kula da dabbobi') ?></li>
                                <li><?= t('Ask satisfied customers for reviews', 'Tambayi abokan ciniki masu gamsuwa don bita') ?></li>
                            </ul>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <!-- Call to Action -->
        <div class="text-center mt-5 mb-5">
            <div class="card bg-primary text-white">
                <div class="card-body py-5">
                    <h3 class="card-title mb-4">
                        <i class="bi bi-rocket-takeoff me-2"></i>
                        <?= t('Ready to Start Selling?', 'Shirye kuwe don Fara Sayarwa?') ?>
                    </h3>
                    <p class="card-text lead mb-4">
                        <?= t('Join thousands of successful sellers on FarmHub and grow your business today!', 
                              'Shiga dubban masu sayarwa masu nasara akan FarmHub kuma ku haɓaka kasuwancin ku yau!') ?>
                    </p>
                    <div class="d-flex justify-content-center gap-3 flex-wrap">
                        <?php if ($seller['is_verified']): ?>
                            <a href="add_livestock.php" class="btn btn-light btn-lg">
                                <i class="bi bi-plus-circle me-2"></i>
                                <?= t('Add Your First Listing', 'Æ˜ara Lissafinku na Farko') ?>
                            </a>
                        <?php else: ?>
                            <a href="profile.php#verification" class="btn btn-light btn-lg">
                                <i class="bi bi-patch-check me-2"></i>
                                <?= t('Get Verified to Start Selling', 'Samun Tabbatarwa don Fara Sayarwa') ?>
                            </a>
                        <?php endif; ?>
                        <a href="dashboard.php" class="btn btn-outline-light btn-lg">
                            <i class="bi bi-speedometer2 me-2"></i>
                            <?= t('Go to Dashboard', 'Je zuwa Dashboard') ?>
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Smooth scrolling for navigation links
        document.querySelectorAll('a[href^="#"]').forEach(anchor => {
            anchor.addEventListener('click', function (e) {
                e.preventDefault();
                const target = document.querySelector(this.getAttribute('href'));
                if (target) {
                    target.scrollIntoView({
                        behavior: 'smooth',
                        block: 'start'
                    });
                }
            });
        });

        // Update active nav pill based on scroll position
        window.addEventListener('scroll', function() {
            const sections = document.querySelectorAll('section');
            const navLinks = document.querySelectorAll('.nav-pills .nav-link');
            
            let current = '';
            sections.forEach(section => {
                const sectionTop = section.offsetTop;
                const sectionHeight = section.clientHeight;
                if (pageYOffset >= sectionTop - 100) {
                    current = section.getAttribute('id');
                }
            });

            navLinks.forEach(link => {
                link.classList.remove('active');
                if (link.getAttribute('href') === `#${current}`) {
                    link.classList.add('active');
                }
            });
        });
    </script>
</body>
</html>