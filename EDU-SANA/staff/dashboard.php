<?php 
session_start(); 
if(!isset($_SESSION['role']) || $_SESSION['role'] != 'staff') {
    header("Location: ../index.php");
    exit();
}
include '../config/db_connection.php'; 

// Today's attendance stats (PHP 5.3 SAFE)
$today = date('Y-m-d');
$stmt = $pdo->prepare("
    SELECT 
        COUNT(CASE WHEN status='Present' THEN 1 END) as present_count,
        COUNT(CASE WHEN status='Absent' THEN 1 END) as absent_count,
        COUNT(*) as total_count
    FROM attendance 
    WHERE date = ? AND staff_id = ?
");
$stmt->execute(array($today, $_SESSION['user_id']));  // ✅ FIXED
$today_stats_result = $stmt->fetch();
$today_stats = $today_stats_result ? $today_stats_result : array('present_count' => 0, 'absent_count' => 0, 'total_count' => 0);

// Weekly stats (PHP 5.3 SAFE)
$stmt = $pdo->prepare("
    SELECT 
        COUNT(CASE WHEN status='Present' THEN 1 END) as weekly_present,
        COUNT(*) as weekly_total
    FROM attendance 
    WHERE date >= DATE_SUB(?, INTERVAL 7 DAY) AND staff_id = ?
");
$stmt->execute(array($today, $_SESSION['user_id']));  // ✅ FIXED
$weekly_stats_result = $stmt->fetch();
$weekly_stats = $weekly_stats_result ? $weekly_stats_result : array('weekly_present' => 0, 'weekly_total' => 0);

// Long absent students (PHP 5.3 SAFE)
$long_absent_count = 0;
$class_name = isset($_SESSION['class']) ? $_SESSION['class'] : 'III Year';  // ✅ FIXED ?? → isset
try {
    $stmt = $pdo->prepare("
        SELECT COUNT(*) as long_absent_count
        FROM students s 
        WHERE s.class = ? 
        AND NOT EXISTS (
            SELECT 1 FROM attendance a 
            WHERE a.student_roll = s.roll_no 
            AND a.status = 'Present' 
            AND a.class = ?
            AND a.date >= DATE_SUB(?, INTERVAL 7 DAY)
        )
    ");
    $stmt->execute(array($class_name, $class_name, $today));  // ✅ FIXED
    $long_absent_result = $stmt->fetch();
    $long_absent_count = isset($long_absent_result['long_absent_count']) ? $long_absent_result['long_absent_count'] : 0;  // ✅ FIXED
} catch(PDOException $e) {
    $long_absent_count = 0;
}

// Total students in class (PHP 5.3 SAFE)
$stmt = $pdo->prepare("SELECT COUNT(*) as total_students FROM students WHERE class = ?");
$stmt->execute(array($class_name));  // ✅ FIXED
$total_students_result = $stmt->fetch();
$total_students = isset($total_students_result['total_students']) ? $total_students_result['total_students'] : 48;  // ✅ FIXED

// Today's day order
$stmt = $pdo->prepare("SELECT day_order FROM day_order WHERE schedule_date = ?");
$stmt->execute(array($today));  // ✅ FIXED
$day_order_result = $stmt->fetch();
$auto_day_order = $day_order_result ? $day_order_result['day_order'] : date('D');
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($_SESSION['name']); ?> - Staff Dashboard</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    
    <style>
        body { 
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        .dashboard-card {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(20px);
            border-radius: 20px;
            border: 1px solid rgba(255, 255, 255, 0.2);
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.15);
            transition: all 0.4s ease;
            overflow: hidden;
            animation: fadeInUp 0.6s ease-out;
        }
        .dashboard-card:hover {
            transform: translateY(-10px);
            box-shadow: 0 30px 80px rgba(0, 0, 0, 0.2);
        }
        .stat-card {
            padding: 30px;
            text-align: center;
            height: 160px;
            cursor: pointer;
            transition: all 0.3s ease;
            position: relative;
            overflow: hidden;
        }
        .stat-icon {
            width: 70px; height: 70px; border-radius: 20px;
            display: flex; align-items: center; justify-content: center;
            margin: 0 auto 20px; font-size: 24px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.2);
            transition: all 0.3s ease;
        }
        .stat-card:hover .stat-icon {
            transform: scale(1.1) rotate(10deg);
        }
        .bg-present { background: linear-gradient(135deg, #00b894, #00cec9); color: white; }
        .bg-absent { background: linear-gradient(135deg, #e74c3c, #f39c12); color: white; }
        .bg-long-absent { background: linear-gradient(135deg, #f39c12, #f1c40f); color: white; }
        .bg-total { background: linear-gradient(135deg, #3498db, #2980b9); color: white; }
        .stat-number { font-size: 36px; font-weight: 800; margin: 0; text-shadow: 2px 2px 4px rgba(0,0,0,0.1); }
        .stat-label { font-size: 14px; font-weight: 600; opacity: 0.9; text-transform: uppercase; letter-spacing: 1px; margin-top: 10px; }
        .quick-action-btn {
            width: 100%; height: 120px; border: none; border-radius: 15px;
            font-size: 16px; font-weight: 700; text-transform: uppercase;
            letter-spacing: 1px; transition: all 0.4s ease; position: relative;
            overflow: hidden; margin-bottom: 20px;
        }
        .quick-action-btn:hover { transform: translateY(-8px) scale(1.02); box-shadow: 0 20px 50px rgba(0,0,0,0.25); }
        .btn-class { background: linear-gradient(135deg, #3498db, #2980b9); color: white; }
        .btn-reports { background: linear-gradient(135deg, #e67e22, #d35400); color: white; }
        .btn-subject { background: linear-gradient(135deg, #9b59b6, #8e44ad); color: white; }
        .dashboard-sidebar .list-group-item.active {
            background: linear-gradient(135deg, #00b894, #00cec9) !important;
            box-shadow: 0 10px 30px rgba(0,184,148,0.4);
        }
        .dashboard-sidebar .list-group-item {
            border-radius: 12px; margin: 8px 12px; transition: all 0.3s ease;
        }
        .dashboard-sidebar .list-group-item:hover {
            transform: translateX(8px); box-shadow: 0 8px 25px rgba(0,0,0,0.15);
        }
        @keyframes fadeInUp {
            from { opacity: 0; transform: translateY(30px); }
            to { opacity: 1; transform: translateY(0); }
        }
        @media (max-width: 768px) {
            .stat-number { font-size: 28px; }
            .stat-card { height: 140px; padding: 20px; }
        }
    </style>
</head>
<body>
    <?php include '../includes/header.php'; ?>
    
    <div class="container-fluid mt-4">
        <div class="row">
            <!-- Sidebar -->
            <div class="col-md-3 dashboard-sidebar">
                <div class="card sticky-top dashboard-card" style="top:20px;">
                    <div class="card-header bg-primary text-white text-center">
                        <h5 class="mb-1"><?php echo htmlspecialchars($_SESSION['name']); ?></h5>
                        <small>Class: <?php echo htmlspecialchars($class_name); ?></small>
                    </div>
                    <div class="list-group list-group-flush">
                        <a href="dashboard.php" class="list-group-item list-group-item-action active">📊 Dashboard</a>
                        <a href="attendance.php" class="list-group-item list-group-item-action">✅ Mark Attendance</a>
                        <a href="attendance_request.php" class="list-group-item list-group-item-action">📝 Attendance Request</a>
                        <a href="upload_material.php" class="list-group-item list-group-item-action">📚 Upload Study Material</a>
                        <a href="../auth/logout.php" class="list-group-item list-group-item-action text-danger">🚪 Logout</a>
                    </div>
                </div>
            </div>
            
            <!-- Main Content -->
            <div class="col-md-9">
                <!-- WELCOME HEADER -->
                <div class="dashboard-card mb-4 p-4">
                    <h1 class="mb-4 text-primary">
                        <i class="fas fa-tachometer-alt me-3"></i>
                        Welcome, <?php echo htmlspecialchars($_SESSION['name']); ?>!
                    </h1>
                    
                    <?php if(isset($_SESSION['first_login']) && $_SESSION['first_login']): ?>
                    <div class="alert alert-warning dashboard-card mb-4 p-3">
                        🔒 Please change your password on first login!
                        <a href="../auth/change_password.php" class="btn btn-warning btn-sm ms-2">Change Now</a>
                    </div>
                    <?php endif; ?>
                </div>

                <!-- LIVE STATS (4 CLEAN CARDS) -->
                <div class="row mb-5">
                    <div class="col-md-3 mb-4">
                        <a href="attendance.php" style="text-decoration: none;">
                            <div class="card stat-card dashboard-card bg-present">
                                <div class="stat-icon"><i class="fas fa-check-circle"></i></div>
                                <h1 class="stat-number"><?php echo $today_stats['present_count']; ?></h1>
                                <p class="stat-label">Present Today</p>
                            </div>
                        </a>
                    </div>
                    <div class="col-md-3 mb-4">
                        <div class="card stat-card dashboard-card bg-absent">
                            <div class="stat-icon"><i class="fas fa-times-circle"></i></div>
                            <h1 class="stat-number"><?php echo $today_stats['absent_count']; ?></h1>
                            <p class="stat-label">Absent Today</p>
                        </div>
                    </div>
                    <div class="col-md-3 mb-4">
                        <div class="card stat-card dashboard-card bg-long-absent">
                            <div class="stat-icon"><i class="fas fa-exclamation-triangle"></i></div>
                            <h1 class="stat-number"><?php echo $long_absent_count; ?></h1>
                            <p class="stat-label">Long Absent</p>
                        </div>
                    </div>
                    <div class="col-md-3 mb-4">
                        <div class="card stat-card dashboard-card bg-total">
                            <div class="stat-icon"><i class="fas fa-users"></i></div>
                            <h1 class="stat-number"><?php echo $total_students; ?></h1>
                            <p class="stat-label">Total Students</p>
                        </div>
                    </div>
                </div>

                <!-- QUICK ACTIONS -->
                <div class="row">
                    <div class="col-md-4 mb-4">
                        <div class="card dashboard-card">
                            <div class="card-body">
                                <h5 class="card-title text-primary mb-4">
                                    <i class="fas fa-bolt me-2"></i>Quick Actions
                                </h5>
                                <a href="attendance.php" class="quick-action-btn btn-class">
                                    <i class="fas fa-calendar-check me-2"></i>Mark Attendance
                                </a>
                                <a href="attendance.php" class="quick-action-btn btn-subject">
                                    <i class="fas fa-users me-2"></i><?php echo htmlspecialchars($class_name); ?>
                                </a>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- SINGLE QUICK ACTION BUTTON -->
                <div class="text-center">
                    <div class="alert alert-info dashboard-card d-inline-block">
                        <i class="fas fa-info-circle me-2 fs-4"></i>
                        Quick Action: 
                        <a href="attendance.php" class="btn btn-primary btn-lg px-5">
                            <i class="fas fa-calendar-check me-2"></i>Mark Today's Attendance
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <?php include '../includes/footer.php'; ?>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
