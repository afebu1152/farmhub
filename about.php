<?php
session_start();
// Initialize language session variable if not set
if (!isset($_SESSION['language'])) {
    $_SESSION['language'] = 'english';
}

// Handle language switch
if (isset($_GET['lang'])) {
    $_SESSION['language'] = ($_GET['lang'] == 'hausa') ? 'hausa' : 'english';
}

// Translation function
function t($english, $hausa) {
    return ($_SESSION['language'] == 'hausa') ? $hausa : $english;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= t('About Us - FarmHub', 'Game Mu - FarmHub') ?></title>
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
        
        .hero-section {
            background: linear-gradient(rgba(78, 115, 223, 0.8), rgba(34, 74, 190, 0.8)), url('https://images.unsplash.com/photo-1423666639041-f56000c27a9a?ixlib=rb-4.0.3&auto=format&fit=crop&w=1350&q=80');
            background-size: cover;
            background-position: center;
            color: white;
            padding: 100px 0;
            margin-bottom: 50px;
        }
        
        .mission-card {
            border-left: 4px solid var(--accent-blue);
            padding-left: 20px;
            margin-bottom: 30px;
        }
        
        .value-card {
            border-radius: 10px;
            transition: transform 0.3s ease, box-shadow 0.3s ease;
            height: 100%;
            border: none;
            box-shadow: 0 5px 15px rgba(78, 115, 223, 0.1);
            border-top: 3px solid var(--accent-blue);
        }
        
        .value-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 15px 30px rgba(78, 115, 223, 0.2);
        }
        
        .value-icon {
            font-size: 2.5rem;
            color: var(--accent-blue);
            margin-bottom: 15px;
        }
        
        .team-member {
            text-align: center;
            margin-bottom: 30px;
        }
        
        .team-img {
            width: 150px;
            height: 150px;
            border-radius: 50%;
            object-fit: cover;
            margin-bottom: 15px;
            border: 5px solid var(--secondary-color);
            box-shadow: 0 5px 15px rgba(78, 115, 223, 0.1);
        }
        
        .farm-image {
            border-radius: 10px;
            box-shadow: 0 5px 15px rgba(78, 115, 223, 0.1);
            margin-bottom: 20px;
        }
        
        .section-title {
            position: relative;
            padding-bottom: 15px;
            margin-bottom: 30px;
        }
        
        .section-title:after {
            content: '';
            position: absolute;
            bottom: 0;
            left: 0;
            width: 60px;
            height: 3px;
            background-color: var(--accent-blue);
        }
        
        .center-title .section-title:after {
            left: 50%;
            transform: translateX(-50%);
        }
        
        .stats-number {
            font-size: 2.5rem;
            font-weight: bold;
            color: var(--accent-blue);
        }
        
        .stats-label {
            font-size: 1rem;
            color: #6c757d;
        }
        
        .community-section {
            background-color: var(--accent-light-blue);
            padding: 60px 0;
        }
        
        .btn-farmhub {
            background-color: var(--accent-blue);
            color: white;
            padding: 10px 25px;
            border-radius: 30px;
            font-weight: 600;
            transition: all 0.3s ease;
            border: none;
        }
        
        .btn-farmhub:hover {
            background-color: var(--accent-dark-blue);
            transform: translateY(-2px);
            color: white;
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
        
        .navbar-dark.bg-primary {
            background-color: var(--accent-blue) !important;
        }
        
        .btn-outline-success {
            border-color: var(--accent-blue);
            color: var(--accent-blue);
        }
        
        .btn-outline-success:hover {
            background-color: var(--accent-blue);
            color: white;
        }
        
        .text-primary {
            color: var(--accent-blue) !important;
        }
        
        .bg-light {
            background-color: var(--accent-light-blue) !important;
        }
        
        @media (max-width: 768px) {
            .hero-section {
                padding: 80px 0;
            }
            
            .language-selector {
                position: relative;
                top: 0;
                right: 0;
                text-align: center;
                margin-bottom: 15px;
            }
        }
    </style>
</head>
<body>
    <!-- Language Selector -->
    <div class="language-selector dropdown">
        <button class="btn btn-outline-primary dropdown-toggle" type="button" id="languageDropdown" data-bs-toggle="dropdown">
            <img src="data:image/svg+xml;base64,<?= base64_encode($_SESSION['language'] == 'hausa' ? '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 3 2"><path fill="#009A00" d="M0 0h3v2H0z"/><path fill="#FFF" d="M0 0h3v1H0z"/><path fill="#DA1A30" d="M0 0h1v2H0z"/></svg>' : '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 60 30"><path fill="#012169" d="M0 0v30h60V0z"/><path fill="#FFF" d="M0 0v30h60V0z" transform="scale(2)"/><path fill="#C8102E" d="M0 0v30h60V0z" transform="scale(3)"/><path fill="#FFF" d="M0 0l60 30m0-30L0 30" stroke="#FFF" stroke-width="6"/><path fill="#C8102E" d="M0 0l60 30m0-30L0 30" stroke="#C8102E" stroke-width="4"/></svg>') ?>" class="language-flag" alt="<?= $_SESSION['language'] ?>">
            <?= $_SESSION['language'] == 'hausa' ? 'Hausa' : 'English' ?>
        </button>
        <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="languageDropdown">
            <li>
                <a class="dropdown-item" href="?lang=english">
                    <img src="data:image/svg+xml;base64,PHN2ZyB4bWxucz0iaHR0cDovL3d3dy53My5vcmcvMjAwMC9zdmciIHZpZXdCb3g9IjAgMCA2MCAzMCI+PHBhdGggZmlsbD0iIzAxMjE2OSIgZD0iTTAgMHYzMGg2MFYweiIvPjxwYXRoIGZpbGw9IiNGRkYiIGQ9Ik0wIDB2MzBoNjBWMHoiIHRyYW5sYXQ9InNjYWxlKDIpIi8+PHBhdGggZmlsbD0iI0M4MTAyRSIgZD0iTTAgMHYzMGg2MFYweiIgdHJhbnNmb3JtPSJzY2FsZSgzKSIvPjxwYXRoIGZpbGw9IiNGRkYiIGQ9Ik0wIDBsNjAgMzBtMC0zMEwwIDMwIiBzdHJva2U9IiNGRkYiIHN0cm9rZS13aWR0aD0iNiIvPjxwYXRoIGZpbGw9IiNDODEwMkUiIGQ9Ik0wIDBsNjAgMzBtMC0zMEwwIDMwIiBzdHJva2U9IiNDODEwMkUiIHN0cm9rZS13aWR0aD0iNCIvPjwvc3ZnPg==" class="language-flag me-2" alt="English">
                    English
                </a>
            </li>
            <li>
                <a class="dropdown-item" href="?lang=hausa">
                    <img src="data:image/svg+xml;base64,PHN2ZyB4bWxucz0iaHR0cDovL3d3dy53My5vcmcvMjAwMC9zdmciIHZpZXdCb3g9IjAgMCAzIDIiPjxwYXRoIGZpbGw=" class="language-flag me-2" alt="Hausa">
                    Hausa
                </a>
            </li>
        </ul>
    </div>

    <!-- Navigation -->
    <?php 

    ?>
    <br>
    <br>
    <br>
    <nav class="navbar navbar-expand-lg navbar-dark bg-primary">
        <div class="container">
            <a class="navbar-brand" href="index.php">FarmHub</a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav ms-auto">
                    <li class="nav-item">
                        <a class="nav-link" href="index.php"><?= t('Home', 'Gida') ?></a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link active" href="about.php"><?= t('About', 'Game Mu') ?></a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="browse.php"><?= t('Livestock', 'Dabbobi') ?></a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="contact.php"><?= t('Contact', 'TuntuÉ"i') ?></a>
                    </li>
                </ul>
            </div>
        </div>
    </nav>
    
    <!-- Hero Section -->
    <section class="hero-section">
        <div class="container">
            <div class="row">
                <div class="col-lg-8 mx-auto text-center">
                    <h1 class="display-4 fw-bold"><?= t('About FarmHub', 'Game da FarmHub') ?></h1>
                    <p class="lead"><?= t('Connecting Farmers, Producers, and Consumers in a Thriving Agricultural Community', 
                                          'HaÉ"a Manoma, Masu Kayarwa, da Masu Sayayya a cikin Æ˜ungiyar Noma mai BunÆ"asa') ?></p>
                </div>
            </div>
        </div>
    </section>
    
    <!-- Welcome Section -->
    <section class="welcome-section mb-5">
        <div class="container">
            <div class="row align-items-center">
                <div class="col-lg-6">
                    <h2 class="section-title"><?= t('Welcome to FarmHub', 'Barka da zuwa FarmHub') ?></h2>
                    <p class="lead"><?= t('A community-driven farm and marketplace where farmers, producers, and consumers come together.', 
                                         'Gona da kasuwa mai zaman kanta inda manoma, masu kayarwa, da masu sayayya suke taruwa.') ?></p>
                    <p><?= t('We believe farming is more than growing crops or raising animals â€" it\'s about connection. That\'s why we\'ve created a space that not only showcases our own farm products but also opens the door for others to buy, sell, and trade their animals directly with the public.', 
                            'Mun yi imanin noma ya wuce noman amfanin gona ko kiwon dabbobi â€" yana da alaÆ"a. Shi ya sa muka Æ˜irÆ˜iri wani wuri wanda ba kawai yana nuna kayayyakin gonarmu ba har ma yana buÉ"e kofa ga wasu don siya, sayarwa, da cinikin dabbobinsu kai tsaye da jama\'a.') ?></p>
                    <p><?= t('Our platform makes it easy to support local farmers and enjoy farm-fresh products.', 
                            'Dandalin namu yana sauÆ"aÆ"e tallafawa manoma na cikin gida da jin daÉ"in samfuran gona masu dadi.') ?></p>
                </div>
                <div class="col-lg-6">
                    <img src="https://images.unsplash.com/photo-1586771107445-d3ca888129ce?ixlib=rb-4.0.3&auto=format&fit=crop&w=800&q=80" alt="FarmHub Community" class="img-fluid farm-image">
                </div>
            </div>
        </div>
    </section>
    
    <!-- Mission Statement -->
    <section class="mission-section py-5 bg-light">
        <div class="container">
            <div class="row">
                <div class="col-lg-10 mx-auto">
                    <div class="mission-card">
                        <h3 class="mb-3"><?= t('Our Mission', 'Manufarmu') ?></h3>
                        <p class="mb-0"><?= t('At FarmHub, we are committed to empowering farmers by giving them a trusted place to market their animals, connecting communities with access to healthy, affordable, and locally sourced products, and promoting sustainability through fair trade and responsible farming practices.', 
                                             'A FarmHub, muna da himma don Æ˜arfafa manoma ta hanyar ba su amintaccen wurin kasuwanci don sayar da dabbobinsu, haÉ"a al"ummomi tare da samun damar samun lafiyayyun, araha, da samfuran gida, da haÉ"aka dorewa ta hanyar ciniki na gaskiya da ayyukan noma masu alhaki.') ?></p>
                    </div>
                </div>
            </div>
        </div>
    </section>
    
    <!-- Values Section -->
    <section class="values-section py-5">
        <div class="container">
            <div class="row center-title mb-5">
                <div class="col-lg-8 mx-auto text-center">
                    <h2 class="section-title"><?= t('Our Values', 'Æ˜imar Mu') ?></h2>
                    <p><?= t('These principles guide everything we do at FarmHub', 
                            'WaÉ"annan Æ˜a"idodin suna jagorantar duk abin da muke yi a FarmHub') ?></p>
                </div>
            </div>
            
            <div class="row">
                <div class="col-md-4 mb-4">
                    <div class="value-card card h-100 p-4">
                        <div class="text-center">
                            <i class="bi bi-people-fill value-icon"></i>
                            <h4><?= t('Community First', 'Al"umma Ta Fara') ?></h4>
                        </div>
                        <p><?= t('We prioritize building strong relationships between farmers and consumers, creating a supportive network where everyone benefits.', 
                                'Muna ba da fifiko ga gina Æ˜aÆ˜Æ˜arfan alaÆ"a tsakanin manoma da masu amfani, Æ˜irÆ˜irar hanyar sadarwa mai tallafawa inda kowa ke amfana.') ?></p>
                    </div>
                </div>
                
                <div class="col-md-4 mb-4">
                    <div class="value-card card h-100 p-4">
                        <div class="text-center">
                            <i class="bi bi-shield-check value-icon"></i>
                            <h4><?= t('Trust & Transparency', 'Amincewa & Bayyana Gaskiya') ?></h4>
                        </div>
                        <p><?= t('We maintain open communication and honest practices to ensure a reliable marketplace for all participants.', 
                                'Muna kiyaye buÉ"aÉ"É"en sadarwa da ayyuka na gaskiya don tabbatar da amintaccen kasuwa ga duk mahalarta.') ?></p>
                    </div>
                </div>
                
                <div class="col-md-4 mb-4">
                    <div class="value-card card h-100 p-4">
                        <div class="text-center">
                            <i class="bi bi-tree value-icon"></i>
                            <h4><?= t('Sustainability', 'Dorewa') ?></h4>
                        </div>
                        <p><?= t('We promote environmentally responsible farming practices that protect our land and resources for future generations.', 
                                'Muna inganta ayyukan noma masu alhakin muhalli waÉ"anda ke kare Æ˜asarmu da albarkatunmu ga tsararraki masu zuwa.') ?></p>
                    </div>
                </div>
            </div>
        </div>
    </section>
    
    
    <!-- Community Section -->
    <section class="community-section">
        <div class="container">
            <div class="row align-items-center">
                <div class="col-lg-6 order-lg-2">
                    <h2 class="section-title"><?= t('Join Our Community', 'Shiga Al"ummarmu') ?></h2>
                    <p><?= t('Whether you\'re a farmer looking for a marketplace or a customer searching for farm-fresh products, you\'ll find a home here with us.', 
                            'Ko kai manomi ne neman kasuwa ko kuma abokin ciniki neman sabbin kayayyakin gona, za ka sami gida a nan tare da mu.') ?></p>
                    <p><?= t('Together, we\'re building a vibrant farming community where everyone thrives.', 
                            'Tare, muna gina Æ˜ungiyar noma mai fa"ida inda kowa ke bunÆ"asa.') ?></p>
                    <div class="mt-4">
                        <a href="register.php" class="btn btn-farmhub me-3"><?= t('Join Now', 'Shiga Yanzu') ?></a>
                        <a href="browse.php" class="btn btn-outline-success"><?= t('Explore Listings', 'Bincika Lissafin') ?></a>
                    </div>
                </div>
                <div class="col-lg-6 order-lg-1">
                    <img src="https://images.unsplash.com/photo-1472653525500-97f046b8334f?ixlib=rb-4.0.3&auto=format&fit=crop&w=800&q=80" alt="FarmHub Community" class="img-fluid farm-image">
                </div>
            </div>
        </div>
    </section>
    
    <!-- Footer -->
    <?php 
    // For demonstration, including a simple footer. Replace with your actual footer include.
    // include 'includes/footer.php'; 
    ?>
    <footer class="bg-dark text-white py-4 mt-5">
        <div class="container">
            <div class="row">
                <div class="col-md-6">
                    <h5>FarmHub</h5>
                    <p><?= t('Connecting farmers and consumers through a trusted marketplace.', 
                            'HaÉ"a manoma da masu amfani ta hanyar amintaccen kasuwa.') ?></p>
                </div>
                <div class="col-md-3">
                    <h5><?= t('Quick Links', 'Hanyoyin Saurin') ?></h5>
                    <ul class="list-unstyled">
                        <li><a href="index.php" class="text-white"><?= t('Home', 'Gida') ?></a></li>
                        <li><a href="about.php" class="text-white"><?= t('About', 'Game Mu') ?></a></li>
                        <li><a href="browse.php" class="text-white"><?= t('Livestock', 'Dabbobi') ?></a></li>
                        <li><a href="contact.php" class="text-white"><?= t('Contact', 'TuntuÉ"i') ?></a></li>
                    </ul>
                </div>
                <div class="col-md-3">
                    <h5><?= t('Connect', 'HaÉ"a') ?></h5>
                    <div class="d-flex gap-3">
                        <a href="#" class="text-white"><i class="bi bi-facebook"></i></a>
                        <a href="#" class="text-white"><i class="bi bi-twitter"></i></a>
                        <a href="#" class="text-white"><i class="bi bi-instagram"></i></a>
                        <a href="#" class="text-white"><i class="bi bi-whatsapp"></i></a>
                    </div>
                </div>
            </div>
            <hr>
            <div class="text-center">
                <p class="mb-0">&copy; <?= date('Y') ?> FarmHub. <?= t('All rights reserved.', 'Duk haÆ˜Æ˜oÆ˜in suna taÆ˜aice.') ?></p>
            </div>
        </div>
    </footer>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>