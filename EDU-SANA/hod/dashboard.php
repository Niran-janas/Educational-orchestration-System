<?php 
session_start(); 
if(!isset($_SESSION['role']) || $_SESSION['role'] != 'hod') {
    header("Location: ../index.php");
    exit();
}
include '../config/db_connection.php'; 

// 🔥 SET UTF8MB4 FOR EMOJI SUPPORT (WAMP SAFE)
$pdo->exec("SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci");

// 🔥 REAL STATS - ACTUAL DB COUNTS
$total_staff = 0; 
$total_classes = 0;
$total_requests = 0;
$total_subjects = 0;

try {
    // 🔥 TOTAL STAFF FROM staff TABLE
    $stmt = $pdo->query("SELECT COUNT(*) FROM staff");
    $total_staff = $stmt->fetchColumn() ?: 0;
    
    // 🔥 TOTAL CLASSES
    $stmt = $pdo->query("SELECT COUNT(DISTINCT class) FROM students");
    $total_classes = $stmt->fetchColumn() ?: 0;
    
    // 🔥 PENDING REQUESTS
    $stmt = $pdo->query("SELECT COUNT(*) FROM attendance_requests WHERE status='pending_hod'");
    $total_requests = $stmt->fetchColumn() ?: 0;
    
    // 🔥 TOTAL SUBJECTS
    $stmt = $pdo->query("SELECT COUNT(DISTINCT subject) FROM attendance");
    $total_subjects = $stmt->fetchColumn() ?: 0;
    
} catch(PDOException $e) {
    $total_staff = $total_classes = $total_requests = $total_subjects = 0;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title><?php echo htmlspecialchars($_SESSION['name']); ?> - HOD Dashboard</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        :root {
            --primary-gradient: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            --success-gradient: linear-gradient(135deg, #11998e 0%, #38ef7d 100%);
            --warning-gradient: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);
            --danger-gradient: linear-gradient(135deg, #ff6b6b 0%, #ee5a52 100%);
            --info-gradient: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%);
        }

        * { font-family: 'Poppins', sans-serif; }
        body { 
            background: linear-gradient(135deg, #f5f7fa 0%, #c3cfe2 100%);
            min-height: 100vh;
        }

        /* 🔥 GLASSMORPHISM SIDEBAR */
        .sidebar-glass {
            background: rgba(255, 255, 255, 0.25);
            backdrop-filter: blur(20px);
            border: 1px solid rgba(255, 255, 255, 0.18);
            border-radius: 25px;
            box-shadow: 0 25px 45px rgba(0,0,0,0.1);
            transition: all 0.4s ease;
        }
        .sidebar-glass:hover { transform: translateY(-5px); box-shadow: 0 35px 60px rgba(0,0,0,0.2); }

        /* 🔥 HOD HEADER */
        .hod-header {
            background: var(--primary-gradient);
            border-radius: 20px 20px 0 0;
            position: relative;
            overflow: hidden;
        }
        .hod-header::before {
            content: '';
            position: absolute;
            top: -50%; left: -50%; width: 200%; height: 200%;
            background: linear-gradient(45deg, transparent, rgba(255,255,255,0.3), transparent);
            transform: rotate(45deg);
            animation: shine 4s infinite;
        }
        @keyframes shine {
            0% { transform: translateX(-100%) translateY(-100%) rotate(45deg); }
            100% { transform: translateX(100%) translateY(100%) rotate(45deg); }
        }

        /* 🔥 STAT CARDS */
        .stat-card {
            border: none;
            border-radius: 20px;
            height: 140px;
            position: relative;
            overflow: hidden;
            transition: all 0.4s cubic-bezier(0.175, 0.885, 0.32, 1.275);
        }
        .stat-card:hover {
            transform: translateY(-10px) scale(1.02);
            box-shadow: 0 25px 50px rgba(0,0,0,0.15) !important;
        }
        .stat-icon {
            font-size: 3rem;
            opacity: 0.1;
            position: absolute;
            top: 20px;
            right: 20px;
        }

        /* 🔥 BUTTONS */
        .action-btn {
            border-radius: 15px;
            padding: 15px 30px;
            font-weight: 600;
            font-size: 1.1rem;
            transition: all 0.3s ease;
            border: none;
            height: 70px;
        }
        .action-btn:hover {
            transform: translateY(-3px);
            box-shadow: 0 15px 35px rgba(0,0,0,0.2);
        }

        /* 🔥 ANIMATIONS */
        .pulse-stat { animation: pulse 2s infinite; }
        @keyframes pulse {
            0%, 100% { transform: scale(1); }
            50% { transform: scale(1.05); }
        }

        /* 🔥 LIST ITEMS */
        .list-group-item {
            border: none;
            padding: 15px 20px;
            transition: all 0.3s ease;
            border-radius: 12px;
            margin: 5px 10px;
            background: rgba(255,255,255,0.7);
        }
        .list-group-item:hover {
            background: rgba(102, 126, 234, 0.2);
            transform: translateX(10px);
            box-shadow: 0 10px 25px rgba(0,0,0,0.1);
        }
        .list-group-item.active {
            background: var(--primary-gradient);
            color: white;
            transform: scale(1.02);
        }

        @media (max-width: 768px) {
            .stat-card { height: 120px; margin-bottom: 1rem; }
            .action-btn { height: 60px; font-size: 1rem; }
        }
    </style>
</head>
<body>
    <?php include '../includes/header.php'; ?>
    
    <div class="container-fluid mt-5 px-4">
        <div class="row">
            <!-- 🔥 PREMIUM GLASS SIDEBAR -->
            <div class="col-xl-3 col-lg-4">
                <div class="sidebar-glass sticky-top" style="top:20px;">
                    <div class="hod-header text-white text-center p-4 position-relative">
                        <div class="position-absolute top-50 start-50 translate-middle">
                            <i class="fas fa-crown fa-2x mb-2 d-block"></i>
                            <h4 class="mb-2 fw-bold fs-4"></h4>
                            <h6 class="mb-0 opacity-90"><?php echo htmlspecialchars($_SESSION['name']); ?></h6>
                            <span class="badge bg-light fs-6 px-3 py-2 mt-2">👑 HEAD</span>
                        </div>
                    </div>
                    
                    <div class="list-group list-group-flush px-2 pb-3">
                        <a href="dashboard.php" class="list-group-item active pulse-stat">
                            <i class="fas fa-tachometer-alt me-3 text-primary"></i>📊 Dashboard
                        </a>
                        <a href="staff_register.php" class="list-group-item">
                            <i class="fas fa-user-plus me-3 text-warning"></i>📝 Register staff (<?php echo $total_staff; ?>)
                        </a>
                        <a href="manage_staff.php" class="list-group-item">
                            <i class="fas fa-users-cog me-3 text-warning"></i>👥 Manage staff 
                        </a>
                        <a href="calculate_attendance.php" class="list-group-item">
                            <i class="fas fa-calculator me-3 text-warning"></i>📈 Calculate attendance
                        </a>
                        <a href="generate_timetable.php" class="list-group-item">
                            <i class="fas fa-calendar-alt me-3 text-warning"></i>📅 Generate timetable 
                        </a>
                        <a href="attendance_records.php" class="list-group-item">
                            <i class="fas fa-file-alt me-3 text-warning"></i>📋 Attendance records 
                        </a>
                        <!-- 🔥 NEW NOTIFICATIONS LINK -->
                        <a href="notification.php" class="list-group-item">
                            <i class="fas fa-bell me-3 text-warning"></i>🔔 Notifications
                        </a>
                        
                        <a href="attendance_requests.php" class="list-group-item">
                            <i class="fas fa-clipboard-list me-3 text-warning"></i>📝 Requests (<?php echo $total_requests; ?>)
                        </a>
                        <a href="../auth/logout.php" class="list-group-item text-danger">
                            <i class="fas fa-sign-out-alt me-3"></i>🚪 Logout
                        </a>
                    </div>
                </div>
            </div>

            <!-- 🔥 MAIN DASHBOARD CONTENT -->
            <div class="col-xl-9 col-lg-8">
                <!-- 🔥 WELCOME SECTION -->
                <div class="glass-card mb-5 p-5 text-center" style="background: rgba(255,255,255,0.4); border-radius: 25px;">
                    <h1 class="display-4 fw-bold mb-1" style="background: var(--primary-gradient); -webkit-background-clip: text; -webkit-text-fill-color: transparent; background-clip: text;">
                        Welcome Back, <?php echo htmlspecialchars($_SESSION['name']); ?>!
                    </h1>
                    <p class="lead text-muted mb-0">Manage your department like a PRO 👑</p>
                </div>

                <!-- 🔥 QUICK ACTION BUTTONS -->
                <div class="row g-4 mb-5">
                    <div class="col-md-6 col-xl-3">
                        <a href="staff_register.php" class="btn btn-success action-btn w-100 pulse-stat">
                            <i class="fas fa-user-plus fa-2x d-block mb-2"></i>
                            <span class="fs-5 fw-bold d-block">➕ Register New Staff</span>
                        </a>
                    </div>
                    <div class="col-md-6 col-xl-3">
                        <a href="manage_staff.php" class="btn btn-info action-btn w-100">
                            <i class="fas fa-users-cog fa-2x d-block mb-2"></i>
                            <span class="fs-5 fw-bold d-block">⚙️ Manage Staff</span>
                        </a>
                    </div>
                    <div class="col-md-6 col-xl-3">
                        <a href="attendance_requests.php" class="btn btn-warning action-btn w-100">
                            <i class="fas fa-clipboard-list fa-2x d-block mb-2"></i>
                            <span class="fs-5 fw-bold d-block">📝 Pending Requests (<?php echo $total_requests; ?>)</span>
                        </a>
                    </div>
                    <div class="col-md-6 col-xl-3">
                        <a href="generate_timetable.php" class="btn btn-danger action-btn w-100">
                            <i class="fas fa-table fa-2x d-block mb-2"></i>
                            <span class="fs-5 fw-bold d-block">📅 Generate Timetable</span>
                        </a>
                    </div>
                </div>

                <!-- 🔥 FEATURE HIGHLIGHTS -->
                <div class="row g-4">
                    <div class="col-md-6">
                        <div class="glass-card p-4 h-100" style="background: rgba(255,255,255,0.6); border-radius: 20px;">
                            <h5 class="fw-bold mb-4"><i class="fas fa-rocket me-2 text-primary"></i>🚀 Quick Stats</h5>
                            <div class="row text-center">
                                <div class="col-6 border-end pb-3">
                                    <h3 class="text-primary"><?php echo date('d'); ?></h3>
                                    <small class="text-muted">Today</small>
                                </div>
                                <div class="col-6 pb-3">
                                    <h3 class="text-success"><?php echo date('M Y'); ?></h3>
                                    <small class="text-muted">Current Month</small>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="glass-card p-4 h-100" style="background: rgba(255,255,255,0.6); border-radius: 20px;">
                            <h5 class="fw-bold mb-3"><i class="fas fa-lightbulb me-2 text-warning"></i>💡 Recent Activity</h5>
                            <ul class="list-unstyled">
                                <li class="mb-2"><i class="fas fa-circle text-success me-2"></i>Dashboard loaded successfully</li>
                                <li class="mb-2"><i class="fas fa-circle text-info me-2"></i><?php echo $total_staff; ?> staff members registered</li>
                                <li class="mb-2"><i class="fas fa-circle text-warning me-2"></i><?php echo $total_requests; ?> pending requests</li>
                            </ul>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // 🔥 HOVER EFFECTS
        document.querySelectorAll('.stat-card, .action-btn').forEach(el => {
            el.addEventListener('mouseenter', () => el.style.transform = 'translateY(-8px)');
            el.addEventListener('mouseleave', () => el.style.transform = 'translateY(0)');
        });

        // 🔥 SIDEBAR ACTIVE SCROLL
        document.querySelectorAll('.list-group-item').forEach(item => {
            item.addEventListener('click', function() {
                document.querySelectorAll('.list-group-item').forEach(i => i.classList.remove('active'));
                this.classList.add('active');
            });
        });
    </script>
</body>
</html>
