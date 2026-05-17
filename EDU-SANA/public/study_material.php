<?php 
session_start(); 
include '../config/db_connection.php'; 
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>📚 Study Materials - Computer Science Department</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/animate.css@4.1.1/animate.min.css" rel="stylesheet">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        
        body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }

        .page-header {
            background: rgba(255,255,255,0.95);
            backdrop-filter: blur(20px);
            box-shadow: 0 20px 60px rgba(0,0,0,0.1);
            padding: 2rem 0;
            margin-bottom: 3rem;
        }

        .material-card {
            background: rgba(255,255,255,0.95);
            backdrop-filter: blur(20px);
            border: none;
            border-radius: 25px;
            box-shadow: 0 20px 60px rgba(0,0,0,0.1);
            transition: all 0.4s cubic-bezier(0.175, 0.885, 0.32, 1.275);
            overflow: hidden;
            height: 100%;
            position: relative;
        }

        .material-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 5px;
            background: linear-gradient(90deg, #10b981, #34d399, #059669);
        }

        .material-card:hover {
            transform: translateY(-15px) scale(1.02);
            box-shadow: 0 35px 80px rgba(0,0,0,0.2);
        }

        .material-card .card-body {
            padding: 2rem;
        }

        .download-btn {
            background: linear-gradient(135deg, #10b981 0%, #34d399 50%, #059669 100%);
            border: none;
            border-radius: 15px;
            color: white;
            font-weight: 700;
            font-size: 1.1rem;
            padding: 1rem 2rem;
            width: 100%;
            transition: all 0.3s ease;
            box-shadow: 0 10px 30px rgba(16,185,129,0.4);
            position: relative;
            overflow: hidden;
        }

        .download-btn:hover {
            transform: translateY(-3px) scale(1.05);
            box-shadow: 0 20px 40px rgba(16,185,129,0.6);
            color: white;
        }

        .download-btn:active {
            transform: translateY(-1px);
        }

        .badge {
            padding: 0.75rem 1.25rem;
            font-size: 0.95rem;
            font-weight: 600;
            border-radius: 50px;
            border: none;
        }

        .badge-success { background: linear-gradient(135deg, #10b981, #34d399); }
        .badge-info { background: linear-gradient(135deg, #3b82f6, #60a5fa); }

        .material-info {
            background: linear-gradient(135deg, #f8fafc 0%, #e2e8f0 100%);
            padding: 1.25rem;
            border-radius: 15px;
            margin-top: 1.5rem;
            border-left: 5px solid #10b981;
        }

        .stats-section {
            background: rgba(255,255,255,0.9);
            backdrop-filter: blur(20px);
            border-radius: 25px;
            padding: 2.5rem;
            margin-bottom: 3rem;
            box-shadow: 0 20px 60px rgba(0,0,0,0.1);
        }

        .stat-card {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border-radius: 20px;
            padding: 2rem;
            text-align: center;
            transition: transform 0.3s ease;
        }

        .stat-card:hover {
            transform: translateY(-10px);
        }

        .no-materials {
            min-height: 400px;
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
            color: rgba(255,255,255,0.9);
        }

        .search-box {
            background: rgba(255,255,255,0.95);
            backdrop-filter: blur(20px);
            border-radius: 25px;
            padding: 1rem 2rem;
            box-shadow: 0 15px 40px rgba(0,0,0,0.1);
            margin-bottom: 2rem;
        }

        @media (max-width: 768px) {
            .material-card .card-body { padding: 1.5rem; }
            .download-btn { padding: 0.9rem 1.5rem; font-size: 1rem; }
            .page-header { padding: 1.5rem 0; }
        }

        @keyframes fadeInUp {
            from {
                opacity: 0;
                transform: translateY(30px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .material-card {
            animation: fadeInUp 0.6s ease forwards;
        }
    </style>
</head>
<body>
    <?php include '../includes/header.php'; ?>

    <div class="page-header">
        <div class="container">
            <div class="row align-items-center">
                <div class="col-lg-8">
                    <h1 class="display-3 fw-bold text-primary mb-3 animate__animated animate__bounceIn">
                        <i class="fas fa-book-open me-3"></i>📚 Study Materials
                    </h1>
                    <p class="lead fs-4 text-muted mb-0">Download notes, question papers & important study materials</p>
                </div>
                <div class="col-lg-4 text-lg-end mt-3 mt-lg-0">
                    <a href="../index.php" class="btn btn-outline-primary btn-lg px-5">
                        <i class="fas fa-home me-2"></i>🏠 Home
                    </a>
                </div>
            </div>
        </div>
    </div>

    <div class="container-fluid">
        <!-- 🔥 SEARCH & STATS -->
        <div class="row mb-5">
            <div class="col-lg-8 mx-auto">
                <div class="search-box">
                    <div class="row align-items-center">
                        <div class="col-md-8">
                            <div class="input-group">
                                <span class="input-group-text bg-transparent border-0">
                                    <i class="fas fa-search text-primary fs-5"></i>
                                </span>
                                <input type="text" class="form-control border-0 fs-5 shadow-none" 
                                       placeholder="🔍 Search materials by subject or class..." id="searchInput">
                            </div>
                        </div>
                        <div class="col-md-4 text-md-end mt-2 mt-md-0">
                            <span class="badge bg-success fs-6 px-4 py-2 me-2">
                                <i class="fas fa-file-pdf me-1"></i><?php echo $pdo->query("SELECT COUNT(*) FROM study_materials")->fetchColumn(); ?> Materials
                            </span>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- 🔥 MATERIALS GRID -->
        <div class="row g-4 mb-5" id="materialsContainer">
            <?php 
            $materials = $pdo->query("
                SELECT * FROM study_materials 
                ORDER BY upload_date DESC
            ")->fetchAll();
            
            if(!empty($materials)): 
                $index = 0;
                foreach($materials as $material): 
            ?>
            <div class="col-xl-3 col-lg-4 col-md-6 col-sm-12 material-item" 
                 style="animation-delay: <?php echo $index * 0.1; ?>s;">
                <div class="material-card h-100">
                    <div class="card-body">
                        <div class="mb-3">
                            <span class="badge badge-success me-2">
                                <i class="fas fa-graduation-cap me-1"></i><?php echo htmlspecialchars($material['class']); ?>
                            </span>
                            <span class="badge badge-info">
                                <i class="fas fa-book me-1"></i><?php echo htmlspecialchars($material['subject']); ?>
                            </span>
                        </div>
                        <h5 class="card-title fw-bold text-dark mb-4 fs-5">
                            <?php echo htmlspecialchars($material['title']); ?>
                        </h5>
                        
                        <div class="material-info">
                            <div class="row text-center text-sm-start">
                                <div class="col-sm-6 mb-2">
                                    <i class="fas fa-file-pdf text-success me-2"></i>
                                    <strong><?php echo htmlspecialchars($material['filesize']); ?></strong>
                                </div>
                                <div class="col-sm-6 mb-2">
                                    <i class="fas fa-clock text-muted me-2"></i>
                                    <?php echo date('d M Y', strtotime($material['upload_date'])); ?>
                                </div>
                            </div>
                        </div>

                        <a href="../uploads/study_materials/<?php echo htmlspecialchars($material['filename']); ?>" 
                           class="btn download-btn mt-3 text-white fw-bold shadow-lg" download>
                            <i class="fas fa-download me-2"></i>📥 DOWNLOAD NOW
                            <div class="position-absolute top-0 start-0 w-100 h-100 opacity-0"></div>
                        </a>
                    </div>
                </div>
            </div>
            <?php 
                $index++;
                endforeach; 
            else: 
            ?>
            <div class="col-12">
                <div class="no-materials">
                    <div class="text-center py-5">
                        <i class="fas fa-book fa-6x text-white mb-5 opacity-75"></i>
                        <h2 class="display-4 fw-bold text-white mb-4">No Study Materials Yet</h2>
                        <p class="lead fs-3 text-white-50 mb-5">Faculty members will upload materials soon...</p>
                        <a href="../index.php" class="btn btn-light btn-lg px-5 fs-5">
                            <i class="fas fa-home me-2"></i>← Back to Home
                        </a>
                    </div>
                </div>
            </div>
            <?php endif; ?>
        </div>
    </div>

    <?php include '../includes/footer.php'; ?>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // 🔥 SEARCH FUNCTIONALITY
        document.getElementById('searchInput').addEventListener('keyup', function() {
            let filter = this.value.toLowerCase();
            let materials = document.querySelectorAll('.material-item');
            
            materials.forEach(function(material) {
                let title = material.querySelector('.card-title').textContent.toLowerCase();
                let className = material.querySelector('.badge-success').textContent.toLowerCase();
                let subject = material.querySelector('.badge-info').textContent.toLowerCase();
                
                if (title.includes(filter) || className.includes(filter) || subject.includes(filter)) {
                    material.style.display = '';
                } else {
                    material.style.display = 'none';
                }
            });
        });

        // 🔥 DOWNLOAD COUNTER EFFECT
        document.querySelectorAll('.download-btn').forEach(btn => {
            btn.addEventListener('click', function(e) {
                let ripple = document.createElement('span');
                ripple.classList.add('ripple');
                this.appendChild(ripple);
                
                setTimeout(() => {
                    ripple.remove();
                }, 600);
            });
        });
    </script>

    <style>
        .ripple {
            position: absolute;
            border-radius: 50%;
            background: rgba(255,255,255,0.6);
            transform: scale(0);
            animation: ripple-animation 0.6s linear;
            pointer-events: none;
        }

        @keyframes ripple-animation {
            to {
                transform: scale(4);
                opacity: 0;
            }
        }
    </style>
</body>
</html>
