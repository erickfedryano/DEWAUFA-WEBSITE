<?php
require_once __DIR__ . "/config/db.php";

// Fetch destinations by category
$waterfallDestinations = [];
$sunsetDestinations = [];
$sunriseDestinations = [];

// Get Waterfall destinations
$sqlWaterfall = "
    SELECT id, title, location, image, description,
           best_time, duration,
           highlight1, highlight2, highlight3, highlight4,
           rating, category
    FROM destinations
    WHERE status = 'approved' AND category = 'waterfall'
    ORDER BY id DESC
    LIMIT 3
";
$resWaterfall = $conn->query($sqlWaterfall);
if ($resWaterfall) {
    while ($row = $resWaterfall->fetch_assoc()) {
        $waterfallDestinations[] = $row;
    }
}

// Get Sunset destinations
$sqlSunset = "
    SELECT id, title, location, image, description,
           best_time, duration,
           highlight1, highlight2, highlight3, highlight4,
           rating, category
    FROM destinations
    WHERE status = 'approved' AND category = 'sunset'
    ORDER BY id DESC
    LIMIT 3
";
$resSunset = $conn->query($sqlSunset);
if ($resSunset) {
    while ($row = $resSunset->fetch_assoc()) {
        $sunsetDestinations[] = $row;
    }
}

// Get Sunrise destinations
$sqlSunrise = "
    SELECT id, title, location, image, description,
           best_time, duration,
           highlight1, highlight2, highlight3, highlight4,
           rating, category
    FROM destinations
    WHERE status = 'approved' AND category = 'sunrise'
    ORDER BY id DESC
    LIMIT 3
";
$resSunrise = $conn->query($sqlSunrise);
if ($resSunrise) {
    while ($row = $resSunrise->fetch_assoc()) {
        $sunriseDestinations[] = $row;
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>DEWASUFA - Discover Bali's Hidden Gems</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            overflow-x: hidden;
            scroll-behavior: smooth;
            background: #f5f7fa;
        }

        /* Navigation */
        nav {
            position: fixed;
            top: 0;
            width: 100%;
            background: linear-gradient(to bottom, rgba(30, 50, 70, 0.95), rgba(30, 50, 70, 0.85));
            padding: 1rem 5%;
            display: flex;
            justify-content: space-between;
            align-items: center;
            z-index: 1000;
            backdrop-filter: blur(10px);
            box-shadow: 0 2px 20px rgba(0, 0, 0, 0.3);
        }

        .logo {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            font-size: 1.5rem;
            font-weight: bold;
            color: white;
            text-decoration: none;
        }

        .logo-icon {
            width: 35px;
            height: 35px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border-radius: 8px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.5rem;
        }

        .nav-links {
            display: flex;
            gap: 2rem;
            list-style: none;
        }

        .nav-links a {
            color: white;
            text-decoration: none;
            font-size: 0.95rem;
            transition: color 0.3s;
            font-weight: 500;
        }

        .nav-links a:hover {
            color: #4ade80;
        }

        .nav-buttons {
            display: flex;
            gap: 1rem;
        }

        .btn {
            padding: 0.6rem 1.5rem;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            font-size: 0.9rem;
            font-weight: 600;
            transition: all 0.3s;
        }

        .btn-login {
            background: transparent;
            color: white;
            border: 2px solid rgba(255, 255, 255, 0.3);
        }

        .btn-login:hover {
            background: rgba(255, 255, 255, 0.1);
            border-color: white;
        }

        .btn-signup {
            background: linear-gradient(135deg, #4ade80 0%, #22c55e 100%);
            color: white;
            box-shadow: 0 4px 15px rgba(74, 222, 128, 0.4);
        }

        .btn-signup:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(74, 222, 128, 0.6);
        }

        /* Hero Section */
        .hero {
            height: 100vh;
            background: linear-gradient(rgba(0, 0, 0, 0.4), rgba(0, 0, 0, 0.4)), 
                        url('assets/img/sekumpul.jpg') center/cover no-repeat;
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
            text-align: center;
            color: white;
            position: relative;
            overflow: hidden;
        }

        .hero::before {
            content: '';
            position: absolute;
            bottom: 0;
            left: 0;
            right: 0;
            height: 150px;
            background: linear-gradient(to top, rgba(0, 0, 0, 0.6), transparent);
        }

        .hero-content {
            z-index: 1;
            max-width: 800px;
            padding: 0 2rem;
            animation: fadeInUp 1s ease-out;
        }

        .hero h1 {
            font-size: 4.5rem;
            font-weight: 800;
            margin-bottom: 1.5rem;
            text-shadow: 0 4px 20px rgba(0, 0, 0, 0.5);
            letter-spacing: 2px;
        }

        .hero p {
            font-size: 1.25rem;
            margin-bottom: 2.5rem;
            line-height: 1.8;
            text-shadow: 0 2px 10px rgba(0, 0, 0, 0.5);
            font-weight: 300;
        }

        .btn-explore {
            background: linear-gradient(135deg, #4ade80 0%, #22c55e 100%);
            padding: 1rem 3rem;
            font-size: 1.1rem;
            box-shadow: 0 8px 25px rgba(74, 222, 128, 0.5);
        }

        .btn-explore:hover {
            transform: translateY(-3px);
            box-shadow: 0 12px 35px rgba(74, 222, 128, 0.7);
        }

        /* Categories Section */
        .categories {
            padding: 6rem 5%;
            background: linear-gradient(to bottom, #f8fafc, #e2e8f0);
        }

        .section-title {
            text-align: center;
            font-size: 3rem;
            font-weight: 800;
            color: #1e3a5f;
            margin-bottom: 1rem;
            letter-spacing: 1px;
        }

        .feature-cards {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 2.5rem;
            margin: 4rem auto;
            max-width: 1200px;
        }

        .feature-card {
            background: white;
            padding: 2.5rem;
            border-radius: 20px;
            box-shadow: 0 10px 40px rgba(0, 0, 0, 0.1);
            transition: all 0.4s;
            text-align: center;
        }

        .feature-card:hover {
            transform: translateY(-10px);
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.15);
        }

        .feature-icon {
            width: 80px;
            height: 80px;
            margin: 0 auto 1.5rem;
            border-radius: 16px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 2.5rem;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            box-shadow: 0 8px 20px rgba(102, 126, 234, 0.4);
        }

        .feature-card h3 {
            font-size: 1.5rem;
            color: #1e3a5f;
            margin-bottom: 1rem;
            font-weight: 700;
        }

        .feature-card p {
            color: #64748b;
            line-height: 1.7;
            font-size: 1rem;
        }

        /* Destinations Section */
        .destinations {
            padding: 6rem 5%;
            background: #f5f7fa;
        }

        .destination-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(340px, 1fr));
            gap: 2rem;
            max-width: 1200px;
            margin: 3rem auto 0;
        }

        .destination-card {
            background: white;
            border-radius: 24px;
            overflow: hidden;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.08);
            transition: all 0.3s;
            display: flex;
            flex-direction: column;
        }

        .destination-card:hover {
            transform: translateY(-8px);
            box-shadow: 0 12px 40px rgba(0, 0, 0, 0.15);
        }

        .destination-image {
            width: 100%;
            height: 280px;
            background-size: cover;
            background-position: center;
            position: relative;
        }

        .destination-info {
            padding: 1.5rem;
            display: flex;
            flex-direction: column;
            flex-grow: 1;
        }

        .destination-info h3 {
            font-size: 1.5rem;
            color: #1e293b;
            margin-bottom: 0.5rem;
            font-weight: 700;
        }

        .destination-location {
            display: flex;
            align-items: center;
            gap: 0.4rem;
            color: #64748b;
            font-size: 0.95rem;
            margin-bottom: 0.8rem;
        }

        .destination-location::before {
            content: '📍';
            font-size: 1rem;
        }

        .destination-meta {
            font-size: 0.85rem;
            color: #94a3b8;
            margin-bottom: 1rem;
        }

        .destination-description {
            color: #475569;
            line-height: 1.6;
            font-size: 0.95rem;
            margin-bottom: 1.5rem;
            flex-grow: 1;
        }

        .btn-detail {
            background: linear-gradient(135deg, #10b981 0%, #059669 100%);
            color: white;
            border: none;
            padding: 0.9rem 1.5rem;
            border-radius: 12px;
            cursor: pointer;
            font-weight: 600;
            font-size: 0.95rem;
            transition: all 0.3s;
            width: 100%;
        }

        .btn-detail:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 20px rgba(16, 185, 129, 0.4);
        }

        /* Modal Styles */
        .modal {
            display: none;
            position: fixed;
            z-index: 2000;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0, 0, 0, 0.7);
            overflow-y: auto;
            animation: fadeIn 0.3s;
        }

        .modal-content {
            background: white;
            margin: 3% auto;
            padding: 0;
            max-width: 900px;
            border-radius: 24px;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
            animation: slideUp 0.4s;
            overflow: hidden;
        }

        .close-detail {
            position: absolute;
            right: 20px;
            top: 20px;
            color: white;
            font-size: 32px;
            font-weight: bold;
            cursor: pointer;
            z-index: 10;
            width: 40px;
            height: 40px;
            background: rgba(0, 0, 0, 0.5);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: all 0.3s;
        }

        .close-detail:hover {
            background: rgba(0, 0, 0, 0.7);
            transform: rotate(90deg);
        }

        .detail-header {
            position: relative;
            height: 400px;
            background-size: cover;
            background-position: center;
        }

        .detail-header::after {
            content: '';
            position: absolute;
            bottom: 0;
            left: 0;
            right: 0;
            height: 150px;
            background: linear-gradient(to top, rgba(0, 0, 0, 0.8), transparent);
        }

        .detail-header-info {
            position: absolute;
            bottom: 0;
            left: 0;
            right: 0;
            padding: 2rem;
            color: white;
            z-index: 5;
        }

        .detail-header-info h2 {
            font-size: 2.5rem;
            margin-bottom: 0.5rem;
            font-weight: 800;
        }

        .detail-location {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            font-size: 1.1rem;
            margin-bottom: 1rem;
            opacity: 0.95;
        }

        .detail-meta-row {
            display: flex;
            gap: 2rem;
            align-items: center;
            flex-wrap: wrap;
        }

        .detail-meta-item {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            background: rgba(255, 255, 255, 0.2);
            padding: 0.5rem 1rem;
            border-radius: 20px;
            backdrop-filter: blur(10px);
        }

        .detail-body {
            padding: 2rem;
        }

        .detail-section {
            margin-bottom: 2rem;
        }

        .detail-section h3 {
            font-size: 1.5rem;
            color: #1e293b;
            margin-bottom: 1rem;
            font-weight: 700;
        }

        .detail-description {
            color: #475569;
            line-height: 1.8;
            font-size: 1.05rem;
        }

        .highlight-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1rem;
            list-style: none;
        }

        .highlight-item {
            display: flex;
            align-items: center;
            gap: 0.8rem;
            padding: 1rem;
            background: #f1f5f9;
            border-radius: 12px;
            color: #334155;
            font-size: 0.95rem;
        }

        .highlight-item::before {
            content: '●';
            color: #10b981;
            font-size: 1.5rem;
        }

        .comment-area {
            padding: 2rem;
            background: #f8fafc;
            border-top: 1px solid #e2e8f0;
        }

        .comment-area h3 {
            font-size: 1.5rem;
            color: #1e293b;
            margin-bottom: 1rem;
            font-weight: 700;
        }

        .comment-login-info {
            color: #64748b;
            font-size: 1rem;
            padding: 2rem;
            text-align: center;
            background: white;
            border-radius: 12px;
            border: 2px dashed #cbd5e1;
        }

        @keyframes fadeIn {
            from { opacity: 0; }
            to { opacity: 1; }
        }

        @keyframes slideUp {
            from { 
                opacity: 0; 
                transform: translateY(50px); 
            }
            to { 
                opacity: 1; 
                transform: translateY(0); 
            }
        }

        @keyframes fadeInUp {
            from { opacity: 0; transform: translateY(30px); }
            to { opacity: 1; transform: translateY(0); }
        }

        @media (max-width: 768px) {
            .hero h1 { font-size: 2.5rem; }
            .hero p { font-size: 1rem; }
            .nav-links { display: none; }
            .section-title { font-size: 2rem; }
            .destination-grid {
                grid-template-columns: 1fr;
            }
            .detail-header {
                height: 300px;
            }
            .detail-header-info h2 {
                font-size: 1.8rem;
            }
        }
    </style>
</head>
<body>
    <!-- Navigation -->
    <nav>
        <a href="#home" class="logo">
            <div class="logo-icon">🌊</div>
            <span>DEWASUFA</span>
        </a>
        <ul class="nav-links">
            <li><a href="#destinations">Destinations</a></li>
            <li><a href="#categories">Categories</a></li>
            <li><a href="about_us.php#about">About</a></li>
            <li><a href="contact_us.php#contact">Contact</a></li>
        </ul>
        <div class="nav-buttons">
            <button class="btn btn-login" id="btnLogin">Login</button>
            <button class="btn btn-signup" id="btnSignup">Sign Up</button>
        </div>
    </nav>

    <!-- Hero Section -->
    <section class="hero" id="home">
        <div class="hero-content">
            <h1>DEWASUFA</h1>
            <p>Witness the magic of nature as you chase Bali's hidden waterfalls, bask in golden sunsets, and welcome the first light of sunrise over serene landscapes.</p>
            <button class="btn btn-explore" id="btnExplore">Explore Now</button>
        </div>
    </section>

    <!-- Categories Section -->
    <section class="categories" id="categories">
        <h2 class="section-title">CATEGORIES</h2>

        <div class="feature-cards">
            <div class="feature-card">
                <div class="feature-icon">🌊</div>
                <h3>Waterfall</h3>
                <p>Bali is home to some of the world's most mesmerizing waterfalls, each surrounded by emerald green forests and tranquil streams.</p>
            </div>
            <div class="feature-card">
                <div class="feature-icon">🌅</div>
                <h3>Sunset</h3>
                <p>There's nothing quite like watching the sunset in Bali. As the sky comes alive with hues of orange, pink, and purple.</p>
            </div>
            <div class="feature-card">
                <div class="feature-icon">🌄</div>
                <h3>Sunrise</h3>
                <p>Wake up to Bali's breathtaking sunrise where the first light touches the ocean and awakens the island's beauty.</p>
            </div>
        </div>
    </section>

    <!-- Destinations Section -->
<section class="destinations" id="destinations">
    <h2 class="section-title">DESTINATIONS</h2>

    <!-- Waterfall Category -->
    <div class="category-section">
        <div class="category-header">
            <div class="category-icon-wrapper">
                <div class="category-icon">🌊</div>
                <h3 class="category-title">Waterfall</h3>
            </div>
        </div>
        <div class="destination-grid-3">
            <?php foreach ($waterfallDestinations as $dest): ?>
                <div class="destination-card">
                    <div class="destination-image" style="background-image: url('<?php echo htmlspecialchars($dest['image']); ?>')"></div>

                    <div class="destination-info">
                        <h3><?php echo htmlspecialchars($dest['title']); ?></h3>
                        <p class="destination-location"><?php echo htmlspecialchars($dest['location']); ?></p>
                        <p class="destination-meta">
                            Uploaded by admin • <?php echo date('Y-m-d H:i:s'); ?>
                        </p>
                        <p class="destination-description">
                            <?php echo htmlspecialchars(mb_strimwidth($dest['description'], 0, 100, '...')); ?>
                        </p>

                        <button class="btn-detail"
                                data-id="<?php echo $dest['id']; ?>"
                                data-title="<?php echo htmlspecialchars($dest['title']); ?>"
                                data-location="<?php echo htmlspecialchars($dest['location']); ?>"
                                data-image="<?php echo htmlspecialchars($dest['image']); ?>"
                                data-description="<?php echo htmlspecialchars($dest['description']); ?>"
                                data-besttime="<?php echo htmlspecialchars($dest['best_time']); ?>"
                                data-duration="<?php echo htmlspecialchars($dest['duration']); ?>"
                                data-h1="<?php echo htmlspecialchars($dest['highlight1']); ?>"
                                data-h2="<?php echo htmlspecialchars($dest['highlight2']); ?>"
                                data-h3="<?php echo htmlspecialchars($dest['highlight3']); ?>"
                                data-h4="<?php echo htmlspecialchars($dest['highlight4']); ?>"
                                data-rating="<?php echo htmlspecialchars($dest['rating']); ?>">
                            Lihat Detail
                        </button>
                    </div>
                </div>
            <?php endforeach; ?>
            
            <?php if (count($waterfallDestinations) == 0): ?>
                <div class="no-destinations">Tidak ada destinasi waterfall tersedia</div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Sunset Category -->
    <div class="category-section">
        <div class="category-header">
            <div class="category-icon-wrapper">
                <div class="category-icon">🌅</div>
                <h3 class="category-title">Sunset</h3>
            </div>
        </div>
        <div class="destination-grid-3">
            <?php foreach ($sunsetDestinations as $dest): ?>
                <div class="destination-card">
                    <div class="destination-image" style="background-image: url('<?php echo htmlspecialchars($dest['image']); ?>')"></div>

                    <div class="destination-info">
                        <h3><?php echo htmlspecialchars($dest['title']); ?></h3>
                        <p class="destination-location"><?php echo htmlspecialchars($dest['location']); ?></p>
                        <p class="destination-meta">
                            Uploaded by admin • <?php echo date('Y-m-d H:i:s'); ?>
                        </p>
                        <p class="destination-description">
                            <?php echo htmlspecialchars(mb_strimwidth($dest['description'], 0, 100, '...')); ?>
                        </p>

                        <button class="btn-detail"
                                data-id="<?php echo $dest['id']; ?>"
                                data-title="<?php echo htmlspecialchars($dest['title']); ?>"
                                data-location="<?php echo htmlspecialchars($dest['location']); ?>"
                                data-image="<?php echo htmlspecialchars($dest['image']); ?>"
                                data-description="<?php echo htmlspecialchars($dest['description']); ?>"
                                data-besttime="<?php echo htmlspecialchars($dest['best_time']); ?>"
                                data-duration="<?php echo htmlspecialchars($dest['duration']); ?>"
                                data-h1="<?php echo htmlspecialchars($dest['highlight1']); ?>"
                                data-h2="<?php echo htmlspecialchars($dest['highlight2']); ?>"
                                data-h3="<?php echo htmlspecialchars($dest['highlight3']); ?>"
                                data-h4="<?php echo htmlspecialchars($dest['highlight4']); ?>"
                                data-rating="<?php echo htmlspecialchars($dest['rating']); ?>">
                            Lihat Detail
                        </button>
                    </div>
                </div>
            <?php endforeach; ?>
            
            <?php if (count($sunsetDestinations) == 0): ?>
                <div class="no-destinations">Tidak ada destinasi sunset tersedia</div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Sunrise Category -->
    <div class="category-section">
        <div class="category-header">
            <div class="category-icon-wrapper">
                <div class="category-icon">🌄</div>
                <h3 class="category-title">Sunrise</h3>
            </div>
        </div>
        <div class="destination-grid-3">
            <?php foreach ($sunriseDestinations as $dest): ?>
                <div class="destination-card">
                    <div class="destination-image" style="background-image: url('<?php echo htmlspecialchars($dest['image']); ?>')"></div>

                    <div class="destination-info">
                        <h3><?php echo htmlspecialchars($dest['title']); ?></h3>
                        <p class="destination-location"><?php echo htmlspecialchars($dest['location']); ?></p>
                        <p class="destination-meta">
                            Uploaded by admin • <?php echo date('Y-m-d H:i:s'); ?>
                        </p>
                        <p class="destination-description">
                            <?php echo htmlspecialchars(mb_strimwidth($dest['description'], 0, 100, '...')); ?>
                        </p>

                        <button class="btn-detail"
                                data-id="<?php echo $dest['id']; ?>"
                                data-title="<?php echo htmlspecialchars($dest['title']); ?>"
                                data-location="<?php echo htmlspecialchars($dest['location']); ?>"
                                data-image="<?php echo htmlspecialchars($dest['image']); ?>"
                                data-description="<?php echo htmlspecialchars($dest['description']); ?>"
                                data-besttime="<?php echo htmlspecialchars($dest['best_time']); ?>"
                                data-duration="<?php echo htmlspecialchars($dest['duration']); ?>"
                                data-h1="<?php echo htmlspecialchars($dest['highlight1']); ?>"
                                data-h2="<?php echo htmlspecialchars($dest['highlight2']); ?>"
                                data-h3="<?php echo htmlspecialchars($dest['highlight3']); ?>"
                                data-h4="<?php echo htmlspecialchars($dest['highlight4']); ?>"
                                data-rating="<?php echo htmlspecialchars($dest['rating']); ?>">
                            Lihat Detail
                        </button>
                    </div>
                </div>
            <?php endforeach; ?>
            
            <?php if (count($sunriseDestinations) == 0): ?>
                <div class="no-destinations">Tidak ada destinasi sunrise tersedia</div>
            <?php endif; ?>
        </div>
    </div>
</section>

   <!-- Detail Modal - Structure matching detail view -->
<div id="detailModal" class="modal">
    <div class="modal-content-wrapper">
        <button class="back-button">
            <span>←</span>
            <span>Kembali</span>
        </button>
        
        <div class="max-w-6xl mx-auto p-6">
            <div class="bg-white rounded-3xl shadow-xl overflow-hidden">
                <div class="grid md:grid-cols-2 gap-8 p-8">
                    <!-- Left Column -->
                    <div>
                        <img id="detailImage" src="" alt="" class="w-full h-80 object-cover rounded-2xl shadow-lg">
                        <h1 id="detailTitle" class="text-3xl font-bold text-gray-800 mb-2 mt-6"></h1>

                        <!-- Uploader info -->
                        <p id="detailUploader" class="text-xs text-gray-400 mb-2">Uploaded by admin</p>

                        <div class="flex items-center gap-2 text-gray-600 mb-4">
                            <span class="text-green-600">📍</span>
                            <span id="detailLocation"></span>
                        </div>
                        
                        <div class="flex items-center gap-6 mb-6">
                            <div class="flex items-center gap-2 bg-green-100 px-4 py-2 rounded-full">
                                <span class="text-green-600">🕐</span>
                                <span id="detailDuration" class="text-sm font-medium"></span>
                            </div>
                            <div id="detailRatingDiv" class="flex items-center gap-2 bg-yellow-100 px-4 py-2 rounded-full">
                                <span class="text-yellow-600">⭐</span>
                                <span id="detailRating" class="text-sm font-bold"></span>
                            </div>
                        </div>

                        <div class="bg-blue-50 p-4 rounded-xl">
                            <h3 class="font-semibold text-gray-800 mb-2 flex items-center gap-2">
                                <span class="text-blue-600">🕐</span>
                                Waktu Terbaik
                            </h3>
                            <p id="detailBestTime" class="text-gray-700"></p>
                        </div>
                    </div>

                    <!-- Right Column -->
                    <div>
                        <h2 class="text-xl font-bold text-gray-800 mb-4">Description</h2>
                        <p id="detailDescription" class="text-gray-600 leading-relaxed mb-6"></p>

                        <h2 class="text-xl font-bold text-gray-800 mb-4">Highlight</h2>
                        <div id="detailHighlights" class="grid grid-cols-2 gap-3 mb-6"></div>

                        <h2 class="text-xl font-bold text-gray-800 mb-4 flex items-center gap-2">
                            Comment
                        </h2>
                        
                        <div id="commentsList" class="space-y-4 mb-4 max-h-60 overflow-y-auto"></div>

                        <!-- Login message for non-authenticated users -->
                        <div id="commentLoginMessage" class="p-6 bg-gray-50 border-2 border-dashed border-gray-300 rounded-xl text-center">
                            <p class="text-gray-600 mb-3">You must login to post a comment.</p>
                            <button onclick="window.location.href='login.php'" 
                                    class="px-6 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700 transition">
                                Login Now
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
/* Modal Base Styles */
.modal {
    display: none;
    position: fixed;
    z-index: 2000;
    left: 0;
    top: 0;
    width: 100%;
    height: 100%;
    background-color: rgba(0, 0, 0, 0.7);
    overflow-y: auto;
    animation: fadeIn 0.3s;
}

.modal-content-wrapper {
    position: relative;
    background: transparent;
    margin: 2rem auto;
    max-width: 1400px;
    animation: slideUp 0.4s;
}

.close-detail {
    position: fixed;
    right: 40px;
    top: 40px;
    color: white;
    font-size: 32px;
    font-weight: bold;
    cursor: pointer;
    z-index: 2001;
    width: 45px;
    height: 45px;
    background: rgba(0, 0, 0, 0.6);
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    transition: all 0.3s;
    border: 2px solid rgba(255, 255, 255, 0.3);
}

.close-detail:hover {
    background: rgba(0, 0, 0, 0.8);
    transform: rotate(90deg);
    border-color: rgba(255, 255, 255, 0.6);
}

/* Back Button */
.back-button {
    position: fixed;
    left: 40px;
    top: 40px;
    display: flex;
    align-items: center;
    gap: 0.5rem;
    padding: 0.6rem 1.2rem;
    background: white;
    color: #16a34a;
    border: none;
    border-radius: 8px;
    font-size: 0.95rem;
    font-weight: 600;
    cursor: pointer;
    z-index: 2001;
    transition: all 0.3s;
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
}

.back-button:hover {
    background: #f0fdf4;
    color: #15803d;
    transform: translateX(-3px);
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
}

.back-button span:first-child {
    font-size: 1.2rem;
    font-weight: bold;
}

/* Grid styles */
.grid {
    display: grid;
}

.md\:grid-cols-2 {
    grid-template-columns: repeat(2, minmax(0, 1fr));
}

.grid-cols-2 {
    grid-template-columns: repeat(2, minmax(0, 1fr));
}

.gap-2 { gap: 0.5rem; }
.gap-3 { gap: 0.75rem; }
.gap-6 { gap: 1.5rem; }
.gap-8 { gap: 2rem; }

/* Flex-shrink */
.flex-shrink-0 { flex-shrink: 0; }

/* Utility classes */
.hidden { display: none; }
.flex { display: flex; }
.items-center { align-items: center; }
.justify-center { justify-content: center; }
.min-h-screen { min-height: 100vh; }
.max-w-6xl { max-width: 72rem; }
.mx-auto { margin-left: auto; margin-right: auto; }
.mb-2 { margin-bottom: 0.5rem; }
.mb-3 { margin-bottom: 0.75rem; }
.mb-4 { margin-bottom: 1rem; }
.mb-6 { margin-bottom: 1.5rem; }
.mt-6 { margin-top: 1.5rem; }
.p-4 { padding: 1rem; }
.p-6 { padding: 1.5rem; }
.p-8 { padding: 2rem; }
.px-4 { padding-left: 1rem; padding-right: 1rem; }
.py-2 { padding-top: 0.5rem; padding-bottom: 0.5rem; }
.px-6 { padding-left: 1.5rem; padding-right: 1.5rem; }

/* Border and Shadow */
.rounded-xl { border-radius: 0.75rem; }
.rounded-2xl { border-radius: 1rem; }
.rounded-3xl { border-radius: 1.5rem; }
.rounded-full { border-radius: 9999px; }
.rounded-lg { border-radius: 0.5rem; }
.shadow-lg { box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1); }
.shadow-xl { box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1); }
.overflow-hidden { overflow: hidden; }
.overflow-y-auto { overflow-y: auto; }

/* Background colors */
.bg-white { background-color: white; }
.bg-gray-50 { background-color: #f9fafb; }
.bg-green-50 { background-color: #f0fdf4; }
.bg-green-100 { background-color: #dcfce7; }
.bg-yellow-100 { background-color: #fef3c7; }
.bg-blue-50 { background-color: #eff6ff; }
.bg-green-600 { background-color: #16a34a; }
.bg-green-700 { background-color: #15803d; }

/* Text colors */
.text-gray-400 { color: #9ca3af; }
.text-gray-600 { color: #4b5563; }
.text-gray-700 { color: #374151; }
.text-gray-800 { color: #1f2937; }
.text-green-600 { color: #16a34a; }
.text-yellow-600 { color: #ca8a04; }
.text-blue-600 { color: #2563eb; }
.text-white { color: white; }

/* Text sizes */
.text-xs { font-size: 0.75rem; line-height: 1rem; }
.text-sm { font-size: 0.875rem; line-height: 1.25rem; }
.text-xl { font-size: 1.25rem; line-height: 1.75rem; }
.text-3xl { font-size: 1.875rem; line-height: 2.25rem; }

/* Font weights */
.font-medium { font-weight: 500; }
.font-semibold { font-weight: 600; }
.font-bold { font-weight: 700; }

/* Border */
.border-2 { border-width: 2px; }
.border-dashed { border-style: dashed; }
.border-gray-300 { border-color: #d1d5db; }

/* Image styles */
.w-full { width: 100%; }
.h-80 { height: 20rem; }
.object-cover { object-fit: cover; }

/* Max height */
.max-h-60 { max-height: 15rem; }

/* Text alignment */
.text-center { text-align: center; }
.leading-relaxed { line-height: 1.625; }

/* Space-y utility */
.space-y-4 > * + * {
    margin-top: 1rem;
}

/* Comments list empty state */
#commentsList:empty::before {
    content: 'Belum ada komentar.';
    display: block;
    color: #9ca3af;
    text-align: center;
    padding: 1.5rem;
    font-style: italic;
    font-size: 0.875rem;
}

/* Scrollbar styling */
#commentsList {
    scrollbar-width: thin;
    scrollbar-color: #d1d5db #f3f4f6;
}

#commentsList::-webkit-scrollbar {
    width: 6px;
}

#commentsList::-webkit-scrollbar-track {
    background: #f3f4f6;
    border-radius: 10px;
}

#commentsList::-webkit-scrollbar-thumb {
    background: #d1d5db;
    border-radius: 10px;
}

#commentsList::-webkit-scrollbar-thumb:hover {
    background: #9ca3af;
}

/* Hover effects */
.hover\:bg-green-700:hover {
    background-color: #15803d;
}

.transition {
    transition-property: all;
    transition-timing-function: cubic-bezier(0.4, 0, 0.2, 1);
    transition-duration: 150ms;
}

/* Responsive */
@media (max-width: 768px) {
    .md\:grid-cols-2 {
        grid-template-columns: repeat(1, minmax(0, 1fr));
    }
    
    .back-button {
        left: 20px;
        top: 20px;
        padding: 0.5rem 1rem;
        font-size: 0.875rem;
    }
    
    .modal-content-wrapper {
        margin: 1rem;
    }
}

/* Destinations Section */
.destinations {
    padding: 6rem 5%;
    background: #f5f7fa;
}

.section-title {
    text-align: center;
    font-size: 3rem;
    font-weight: 800;
    color: #1e3a5f;
    margin-bottom: 4rem;
    letter-spacing: 1px;
}

/* Category Section */
.category-section {
    margin-bottom: 5rem;
    max-width: 1400px;
    margin-left: auto;
    margin-right: auto;
}

.category-header {
    display: flex;
    justify-content: center;
    align-items: center;
    margin-bottom: 3rem;
}

.category-icon-wrapper {
    display: flex;
    align-items: center;
    gap: 1.5rem;
    padding: 1rem 3rem;
    background: white;
    border-radius: 20px;
    box-shadow: 0 4px 20px rgba(0, 0, 0, 0.08);
}

.category-icon {
    width: 60px;
    height: 60px;
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    border-radius: 16px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 2rem;
    box-shadow: 0 4px 15px rgba(102, 126, 234, 0.3);
}

.category-title {
    font-size: 2rem;
    font-weight: 700;
    color: #1e3a5f;
    margin: 0;
}

/* Grid for 3 columns */
.destination-grid-3 {
    display: grid;
    grid-template-columns: repeat(3, 1fr);
    gap: 2rem;
}

.destination-card {
    background: white;
    border-radius: 24px;
    overflow: hidden;
    box-shadow: 0 4px 20px rgba(0, 0, 0, 0.08);
    transition: all 0.3s;
    display: flex;
    flex-direction: column;
}

.destination-card:hover {
    transform: translateY(-8px);
    box-shadow: 0 12px 40px rgba(0, 0, 0, 0.15);
}

.destination-image {
    width: 100%;
    height: 280px;
    background-size: cover;
    background-position: center;
    position: relative;
}

.destination-info {
    padding: 1.5rem;
    display: flex;
    flex-direction: column;
    flex-grow: 1;
}

.destination-info h3 {
    font-size: 1.5rem;
    color: #1e293b;
    margin-bottom: 0.5rem;
    font-weight: 700;
}

.destination-location {
    display: flex;
    align-items: center;
    gap: 0.4rem;
    color: #64748b;
    font-size: 0.95rem;
    margin-bottom: 0.8rem;
}

.destination-location::before {
    content: '📍';
    font-size: 1rem;
}

.destination-meta {
    font-size: 0.85rem;
    color: #94a3b8;
    margin-bottom: 1rem;
}

.destination-description {
    color: #475569;
    line-height: 1.6;
    font-size: 0.95rem;
    margin-bottom: 1.5rem;
    flex-grow: 1;
}

.btn-detail {
    background: linear-gradient(135deg, #10b981 0%, #059669 100%);
    color: white;
    border: none;
    padding: 0.9rem 1.5rem;
    border-radius: 12px;
    cursor: pointer;
    font-weight: 600;
    font-size: 0.95rem;
    transition: all 0.3s;
    width: 100%;
}

.btn-detail:hover {
    transform: translateY(-2px);
    box-shadow: 0 8px 20px rgba(16, 185, 129, 0.4);
}

.no-destinations {
    grid-column: 1 / -1;
    text-align: center;
    padding: 3rem;
    color: #94a3b8;
    font-size: 1.1rem;
    font-style: italic;
}

/* Responsive Design */
@media (max-width: 1200px) {
    .destination-grid-3 {
        grid-template-columns: repeat(2, 1fr);
    }
}

@media (max-width: 768px) {
    .destinations {
        padding: 4rem 5%;
    }
    
    .section-title {
        font-size: 2rem;
        margin-bottom: 3rem;
    }
    
    .category-section {
        margin-bottom: 3rem;
    }
    
    .category-icon-wrapper {
        padding: 0.8rem 2rem;
        gap: 1rem;
    }
    
    .category-icon {
        width: 50px;
        height: 50px;
        font-size: 1.5rem;
    }
    
    .category-title {
        font-size: 1.5rem;
    }
    
    .destination-grid-3 {
        grid-template-columns: 1fr;
        gap: 1.5rem;
    }
    
    .destination-image {
        height: 220px;
    }
}

/* Animation keyframes */
@keyframes fadeIn {
    from { opacity: 0; }
    to { opacity: 1; }
}

@keyframes slideUp {
    from { 
        opacity: 0; 
        transform: translateY(30px); 
    }
    to { 
        opacity: 1; 
        transform: translateY(0); 
    }
}
</style>

<script>
// Update modal functionality script
document.addEventListener('DOMContentLoaded', function () {
    const modal = document.getElementById('detailModal');
    const backBtn = document.querySelector('.back-button');

    document.querySelectorAll('.btn-detail').forEach(btn => {
        btn.addEventListener('click', function () {
            // Set image
            document.getElementById('detailImage').src = this.dataset.image;
            
            // Set text content
            document.getElementById('detailTitle').textContent = this.dataset.title;
            document.getElementById('detailLocation').textContent = this.dataset.location;
            document.getElementById('detailRating').textContent = this.dataset.rating || 'N/A';
            document.getElementById('detailDuration').textContent = this.dataset.duration || '-';
            document.getElementById('detailBestTime').textContent = this.dataset.besttime || '-';
            document.getElementById('detailDescription').textContent = this.dataset.description;

            // Set highlights
            const highlightsContainer = document.getElementById('detailHighlights');
            highlightsContainer.innerHTML = '';
            
            ['h1', 'h2', 'h3', 'h4'].forEach(key => {
                const val = this.dataset[key];
                if (val && val.trim() !== '') {
                    const div = document.createElement('div');
                    div.className = 'p-4 bg-green-50 rounded-xl flex items-center gap-3';
                    div.innerHTML = `
                        <span style="width: 8px; height: 8px; background-color: #10b981; border-radius: 50%; flex-shrink: 0;"></span>
                        <span class="text-gray-700 text-sm">${val}</span>
                    `;
                    highlightsContainer.appendChild(div);
                }
            });

            // Show modal
            modal.style.display = 'block';
            document.body.style.overflow = 'hidden';
        });
    });

    // Close modal handlers
    backBtn.addEventListener('click', () => {
        modal.style.display = 'none';
        document.body.style.overflow = 'auto';
    });

    window.addEventListener('click', (e) => {
        if (e.target === modal) {
            modal.style.display = 'none';
            document.body.style.overflow = 'auto';
        }
    });
});
</script>

    <script>
        // Smooth scrolling
        document.querySelectorAll('a[href^="#"]').forEach(anchor => {
            anchor.addEventListener('click', function (e) {
                const href = this.getAttribute('href');
                if (href === '#') return;
                e.preventDefault();
                const target = document.querySelector(href);
                if (target) {
                    target.scrollIntoView({
                        behavior: 'smooth',
                        block: 'start'
                    });
                }
            });
        });

        // Navigation scroll effect
        const nav = document.querySelector('nav');
        window.addEventListener('scroll', () => {
            if (window.pageYOffset > 100) {
                nav.style.padding = '0.7rem 5%';
            } else {
                nav.style.padding = '1rem 5%';
            }
        });

        // Button redirects
        document.getElementById('btnLogin').addEventListener('click', () => {
            window.location.href = 'login.php';
        });

        document.getElementById('btnSignup').addEventListener('click', () => {
            window.location.href = 'register.php';
        });

        document.getElementById('btnExplore').addEventListener('click', () => {
            window.location.href = 'login.php';
        });

        // Modal functionality
        document.addEventListener('DOMContentLoaded', function () {
            const modal = document.getElementById('detailModal');
            const closeBtn = document.querySelector('.close-detail');

            document.querySelectorAll('.btn-detail').forEach(btn => {
                btn.addEventListener('click', function () {
                    const headerBg = document.getElementById('detailHeaderBg');
                    headerBg.style.backgroundImage = `url('${this.dataset.image}')`;
                    
                    document.getElementById('detailTitle').textContent = this.dataset.title;
                    document.getElementById('detailLocation').textContent = this.dataset.location;
                    document.getElementById('detailRating').textContent = this.dataset.rating || 'N/A';
                    document.getElementById('detailDuration').textContent = this.dataset.duration || '-';
                    document.getElementById('detailBestTime').textContent = this.dataset.besttime || '-';
                    document.getElementById('detailDescription').textContent = this.dataset.description;

                    const ul = document.getElementById('detailHighlights');
                    ul.innerHTML = '';
                    ['h1', 'h2', 'h3', 'h4'].forEach(key => {
                        const val = this.dataset[key];
                        if (val && val.trim() !== '') {
                            const li = document.createElement('li');
                            li.className = 'highlight-item';
                            li.textContent = val;
                            ul.appendChild(li);
                        }
                    });

                    modal.style.display = 'block';
                    document.body.style.overflow = 'hidden';
                });
            });

            closeBtn.addEventListener('click', () => {
                modal.style.display = 'none';
                document.body.style.overflow = 'auto';
            });

            window.addEventListener('click', (e) => {
                if (e.target === modal) {
                    modal.style.display = 'none';
                    document.body.style.overflow = 'auto';
                }
            });
        });
    </script>
</body>
</html>