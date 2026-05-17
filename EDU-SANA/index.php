<?php 
session_start(); 
include 'config/db_connection.php'; 

// 🔥 AUTO CLEANUP EXPIRED NOTIFICATIONS
try {
    $pdo->exec("UPDATE notifications SET is_active = 0 WHERE expires_at <= NOW()");
} catch(PDOException $e) {}

// 🔥 FETCH MARQUEE NOTIFICATIONS WITH TITLE + DESCRIPTION
$marquee_text = '';
try {
    $stmt = $pdo->query("
        SELECT CONCAT(title, ' - ', description) AS full_text 
        FROM notifications 
        WHERE is_active = 1 AND expires_at > NOW()
        ORDER BY id DESC LIMIT 10
    ");
    $active_notifs = $stmt->fetchAll(PDO::FETCH_COLUMN);
    
    foreach($active_notifs as $full_notification) {
        $marquee_text .= '🔔 ' . trim($full_notification) . ' | ';
    }
    $marquee_text = rtrim($marquee_text, ' | ');
} catch(PDOException $e) {
    $marquee_text = '';
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Alagappa Govt Arts College - Department of Computer Science</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/animate.css@4.1.1/animate.min.css" rel="stylesheet">
    <link href="assets/css/style.css" rel="stylesheet">
    <style>
        /* 🔥 LOGIN CORNER BUTTON - NEW */
        .login-corner {
            position: fixed;
            top: 20px;
            right: 20px;
            z-index: 9999;
        }
        
        .login-btn {
            background: linear-gradient(135deg, #dc3545, #c82333);
            border: none;
            border-radius: 50px;
            padding: 12px 24px;
            color: white;
            font-weight: 600;
            font-size: 16px;
            box-shadow: 0 8px 25px rgba(220, 53, 69, 0.4);
            transition: all 0.3s ease;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            backdrop-filter: blur(10px);
        }
        
        .login-btn:hover {
            transform: translateY(-3px) scale(1.05);
            box-shadow: 0 12px 35px rgba(220, 53, 69, 0.6);
            color: white;
            text-decoration: none;
            background: linear-gradient(135deg, #c82333, #a71e2a);
        }
        
        .login-btn i {
            font-size: 18px;
        }

        /* 🔥 HERO SLIDER CAROUSEL */
        .hero-section-slider {
            padding: 0 !important;
            min-height: 700px;
            position: relative;
            overflow: hidden;
        }

        /* ... rest of your existing styles remain the same ... */
        .slider-container {
            position: relative;
            width: 100%;
            height: 700px;
            overflow: hidden;
        }

        .slider-slide {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background-size: cover;
            background-position: center;
            opacity: 0;
            transform: scale(1.1);
            transition: all 0.8s ease-in-out;
        }

        .slider-slide.active {
            opacity: 1;
            transform: scale(1);
        }

        .slide-overlay {
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: linear-gradient(135deg, rgba(0, 0, 0, 0.5) 0%, rgba(0, 0, 0, 0.2) 100%);
        }

        .slide-content {
            position: relative;
            z-index: 2;
            height: 100%;
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
            padding: 0 20px;
            color: white;
        }

        /* 🔥 NAVIGATION DOTS */
        .slider-dots {
            text-align: center;
            position: absolute;
            bottom: 40px;
            left: 50%;
            transform: translateX(-50%);
            z-index: 3;
        }

        .dot {
            height: 16px;
            width: 16px;
            margin: 0 10px;
            background-color: rgba(255,255,255,0.5);
            border-radius: 50%;
            display: inline-block;
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .dot.active, .dot:hover {
            background-color: #fff;
            transform: scale(1.3);
            box-shadow: 0 0 10px rgba(255,255,255,0.8);
        }

        /* 🔥 ARROW BUTTONS */
        .prev, .next {
            cursor: pointer;
            position: absolute;
            top: 50%;
            transform: translateY(-50%);
            width: 65px;
            height: 65px;
            background: rgba(0, 231, 200, 0.99);
            color: white;
            font-size: 26px;
            font-weight: bold;
            border: none;
            border-radius: 50%;
            user-select: none;
            z-index: 3;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            justify-content: center;
            backdrop-filter: blur(15px);
            border: 2px solid rgba(255,255,255,0.3);
        }

        .prev:hover, .next:hover {
            background: rgba(255,255,255,0.4);
            transform: translateY(-50%) scale(1.1);
            box-shadow: 0 10px 25px rgba(0,0,0,0.3);
        }

        .prev { left: 40px; }
        .next { right: 40px; }

        /* 🔥 MARQUEE */
        .marquee-section {
            background: linear-gradient(135deg, #10b981 0%, #34d399 100%);
            overflow: hidden;
            padding: 12px 0;
        }
        .marquee-wrapper {
            display: flex;
            width: max-content;
            animation: scroll-left 35s linear infinite;
        }
        .marquee-wrapper:hover { animation-play-state: paused; }
        @keyframes scroll-left {
            0% { transform: translateX(100%); }
            100% { transform: translateX(-100%); }
        }
        .marquee-text {
            font-size: 1.25rem;
            font-weight: 700;
            color: white;
            padding: 0 60px;
            white-space: nowrap;
            text-shadow: 2px 2px 4px rgba(0,0,0,0.5);
            letter-spacing: 1.2px;
        }

        /* 🔥 STUDY CARDS */
        .study-card { 
            transition: all 0.4s ease; 
            border: none; 
            background: linear-gradient(145deg, #ffffff, #f0f0f0);
        }
        .study-card:hover { 
            transform: translateY(-12px); 
            box-shadow: 0 25px 50px rgba(0,0,0,0.25);
        }
        .material-grid { gap: 1.8rem; }
        .empty-materials { min-height: 320px; }
        .stats-card { 
            background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);
            border-radius: 20px;
        }

        /* 🔥 RESPONSIVE */
        @media (max-width: 768px) {
            .slider-container, .hero-section-slider { height: 550px; min-height: 550px; }
            .hero-section-slider { padding-bottom: 20px !important; }
            .prev, .next { width: 55px; height: 55px; font-size: 22px; }
            .prev { left: 20px; }
            .next { right: 20px; }
            .marquee-text { font-size: 1.1rem; padding: 0 40px; }
            .slider-dots { bottom: 25px; }
            .dot { height: 14px; width: 14px; margin: 0 8px; }
            /* 🔥 MOBILE LOGIN BUTTON */
            .login-corner { top: 15px; right: 15px; }
            .login-btn { padding: 10px 20px; font-size: 14px; }
        }

        @media (max-width: 576px) {
            .slider-container, .hero-section-slider { height: 450px; min-height: 450px; }
        }
    </style>
</head>
<body>
    <!-- 🔥 LOGIN CORNER BUTTON - POSITIONED TOP RIGHT -->
    <div class="login-corner">
        <a href="auth/login.php" class="login-btn animate__animated animate__pulse animate__infinite">
            <i class="fas fa-lock"></i>
            🔐 Login
        </a>
    </div>

    <?php include 'includes/header.php'; ?>

    <!-- 🔥 DATABASE MARQUEE -->
    <?php if(!empty($marquee_text)): ?>
    <div class="marquee-section">
        <div class="marquee-wrapper">
            <span class="marquee-text">
                📢 <i class="fas fa-volume-up me-3"></i><?php echo htmlspecialchars($marquee_text); ?>
            </span>
        </div>
    </div>
    <?php endif; ?>

    <!-- 🔥 HERO SLIDER SECTION (unchanged) -->
    <section class="hero-section-slider">
        <div class="slider-container">
            <!-- SLIDE 1 -->
            <div class="slider-slide active" style="background-image: url('assets/images/image1.jpg');">
                <div class="slide-overlay"></div>
                <div class="slide-content">
                    <h1 class="display-3 fw-bold mb-4 animate__animated animate__bounceIn">
                        Welcome to <span class="text-warning">Computer Science</span> Department
                    </h1>
                    <p class="lead mb-5 opacity-90 fs-4">Alagappa Government Arts College - Excellence in Education</p>
                   
                </div>
            </div>

            <!-- SLIDE 2 -->
            <div class="slider-slide" style="background-image: url('assets/images/image2.jpeg');">
                <div class="slide-overlay"></div>
                <div class="slide-content">
                    <h1 class="display-3 fw-bold mb-4 animate__animated">
                        Quality  <span class="text-warning">teaching monitering</span>
                    </h1>
                    <p class="lead mb-5 opacity-90 fs-4">Learn cutting-edge technologies with expert faculty</p>
                    
                </div>
            </div>

            <!-- SLIDE 3 -->
            <div class="slider-slide" style="background-image: url('assets/images/image3.jpeg');">
                <div class="slide-overlay"></div>
                <div class="slide-content">
                    <h1 class="display-3 fw-bold mb-4 animate__animated">
                        Maintain<span class="text-warning"> academic DISCIPLINE</span>
                    </h1>
                    <p class="lead mb-5 opacity-90 fs-4">Check your class schedule and stay organized</p>
                    
                </div>
            </div>

            <!-- SLIDE 4 -->
            <div class="slider-slide" style="background-image: url('assets/images/image4.jpg');">
                <div class="slide-overlay"></div>
                <div class="slide-content">
                    <h1 class="display-3 fw-bold mb-4 animate__animated">
                        Students<span class="text-warning"> performance TRACKING</span>
                    </h1>
                    <p class="lead mb-5 opacity-90 fs-4">Be part of Alagappa's premier CS department</p>
                    <a href="auth/login.php" class="btn btn-light btn-lg px-6 fs-4 shadow-lg">
                        <i class="fas fa-user-plus me-2"></i>🔐 Login Now
                    </a>
                </div>
            </div>
        </div>

        <!-- 🔥 NAVIGATION DOTS -->
        <div class="slider-dots">
            <span class="dot active" onclick="currentSlide(1)"></span>
            <span class="dot" onclick="currentSlide(2)"></span>
            <span class="dot" onclick="currentSlide(3)"></span>
            <span class="dot" onclick="currentSlide(4)"></span>
        </div>

        <!-- 🔥 NAVIGATION ARROWS -->
        <a class="prev" onclick="changeSlide(-1)">❮</a>
        <a class="next" onclick="changeSlide(1)">❯</a>
    </section>

    <!-- 🔥 REST OF YOUR CONTENT REMAINS EXACTLY THE SAME -->
    <div class="container-fluid py-5">
        <!-- Stats Cards -->
        <div class="row g-4 mb-5">
            <div class="col-lg-6">
                <div class="card text-center border-0 shadow stats-card h-100">
                    <div class="card-body d-flex flex-column align-items-center justify-content-center">
                        <?php $total_materials = $pdo->query("SELECT COUNT(*) FROM study_materials")->fetchColumn(); ?>
                        <i class="fas fa-book fa-4x text-white mb-3"></i>
                        <h2 class="text-white mb-1 fw-bold"><?php echo $total_materials; ?></h2>
                        <p class="text-white-50 h5 mb-0">Study Materials</p>
                    </div>
                </div>
            </div>
            <div class="col-lg-6">
                <div class="card text-center border-0 shadow h-100" style="background: linear-gradient(135deg, #fa709a 0%, #fee140 100%); border-radius: 20px;">
                    <div class="card-body d-flex flex-column align-items-center justify-content-center">
                        <i class="fas fa-calendar-alt fa-4x text-white mb-3"></i>
                        <h2 class="text-white mb-1 fw-bold"><?php echo date('M Y'); ?></h2>
                        <p class="text-white-50 h5 mb-0">Current Month</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Study Materials Section (unchanged) -->
        <div class="row mb-5">
            <div class="col-12">
                <div class="text-center mb-5">
                    <h2 class="display-4 fw-bold text-primary mb-3">
                        <i class="fas fa-book-open me-3"></i>📚 Study Materials
                    </h2>
                    <p class="lead text-muted fs-5">Download notes, question papers & important materials</p>
                </div>

                <?php 
                $materials = $pdo->query("
                    SELECT * FROM study_materials 
                    ORDER BY upload_date DESC 
                    LIMIT 12
                ")->fetchAll();
                ?>

                <?php if(!empty($materials)): ?>
                <div class="row g-4 material-grid">
                    <?php foreach($materials as $material): ?>
                    <div class="col-md-6 col-lg-4 col-xl-3">
                        <div class="card study-card h-100 shadow-lg border-0">
                            <div class="card-body d-flex flex-column p-4">
                                <div class="mb-3">
                                    <span class="badge bg-success fs-6 px-3 py-2 me-2"><?php echo htmlspecialchars($material['class']); ?></span>
                                    <span class="badge bg-info fs-6 px-3 py-2"><?php echo htmlspecialchars($material['subject']); ?></span>
                                </div>
                                <h5 class="card-title fw-bold text-dark mb-3 flex-grow-1"><?php echo htmlspecialchars($material['title']); ?></h5>
                                <div class="d-flex justify-content-between align-items-end mt-auto">
                                    <div>
                                        <small class="text-muted d-block mb-1"><i class="fas fa-file-pdf me-1"></i><?php echo htmlspecialchars($material['filesize']); ?></small>
                                        <small class="text-muted"><i class="fas fa-clock me-1"></i><?php echo date('d M Y', strtotime($material['upload_date'])); ?></small>
                                    </div>
                                    <a href="uploads/study_materials/<?php echo htmlspecialchars($material['filename']); ?>" class="btn btn-primary px-4" download>
                                        <i class="fas fa-download me-2"></i>Download
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
                <?php else: ?>
                <div class="text-center empty-materials">
                    <div class="card border-0 shadow">
                        <div class="card-body py-5">
                            <i class="fas fa-book fa-5x text-muted mb-4"></i>
                            <h3 class="text-muted mb-3">No Study Materials Yet</h3>
                            <p class="lead text-muted mb-4">Staff will upload materials soon...</p>
                            <a href="#" class="btn btn-outline-primary btn-lg">Refresh Page</a>
                        </div>
                    </div>
                </div>
                <?php endif; ?>

                <?php if(count($materials) == 12): ?>
                <div class="text-center mt-5">
                    <a href="public/study_material.php" class="btn btn-outline-primary btn-lg px-5">
                        <i class="fas fa-eye me-2"></i>View All Materials
                    </a>
                </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Main Buttons (unchanged) -->
        <div class="row g-4 mb-5">
            <div class="col-lg-6">
                <a href="public/study_material.php" class="btn btn-success btn-lg w-100 fs-4 shadow-lg h-100 d-flex flex-column align-items-center justify-content-center p-5 text-decoration-none border-radius-20">
                    <i class="fas fa-book fa-3x mb-3 text-white"></i>
                    <span class="fs-3 fw-bold text-white"></span>
                    <small class="text-white-50">Download all materials</small>
                </a>
            </div>
           
    </div>

    <?php include 'includes/footer.php'; ?>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script src="assets/js/main.js"></script>
    
    <!-- 🔥 SLIDER JAVASCRIPT (unchanged) -->
    <script>
        let slideIndex = 1;
        showSlide(slideIndex);

        function changeSlide(n) {
            showSlide(slideIndex += n);
        }

        function currentSlide(n) {
            showSlide(slideIndex = n);
        }

        function showSlide(n) {
            let slides = document.getElementsByClassName("slider-slide");
            let dots = document.getElementsByClassName("dot");
            
            if (n > slides.length) { slideIndex = 1 }
            if (n < 1) { slideIndex = slides.length }
            
            for (let i = 0; i < slides.length; i++) {
                slides[i].classList.remove("active");
            }
            for (let i = 0; i < dots.length; i++) {
                dots[i].classList.remove("active");
            }
            
            slides[slideIndex-1].classList.add("active");
            dots[slideIndex-1].classList.add("active");
        }

        setInterval(() => {
            changeSlide(1);
        }, 6000);

        document.querySelectorAll('.study-card').forEach(card => {
            card.addEventListener('mouseenter', () => {
                card.style.transform = 'translateY(-12px)';
            });
            card.addEventListener('mouseleave', () => {
                card.style.transform = 'translateY(0)';
            });
        });
    </script>
</body>
</html>
