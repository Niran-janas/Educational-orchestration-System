<?php
session_start();
if(!isset($_SESSION['role']) || $_SESSION['role'] != 'hod') {
    header("Location: ../public/index.php"); exit();
}
include '../config/db_connection.php';

// 🔥 COLLATION FIX
$pdo->exec("SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci");

$selected_year = isset($_GET['year']) ? $_GET['year'] : '';
$selected_semester = isset($_GET['semester']) ? $_GET['semester'] : '';
$selected_date = isset($_GET['date']) ? $_GET['date'] : '';
$records = array();
$dates = array();

// 🔥 DATE RANGE LOGIC
function getDateRange($semester) {
    if($semester == 'odd') {
        return array('2025-06-16', '2025-10-24'); 
    } else {
        return array('2025-12-04', '2026-04-17'); 
    }
}

// 🔥 STEP 3: Class + Semester → Get dates
if($selected_year && $selected_semester && !$selected_date) {
    $range = getDateRange($selected_semester);
    $query = "SELECT DISTINCT date FROM attendance WHERE BINARY class = ? AND date BETWEEN ? AND ? ORDER BY date DESC";
    $stmt = $pdo->prepare($query);
    $stmt->execute(array($selected_year, $range[0], $range[1]));
    $dates = $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// 🔥 STEP 4: Class + Semester + Date → Get records ROLL NO ORDER + REQUEST STATUS
if($selected_year && $selected_semester && $selected_date) {
    $query = "
        SELECT a.id, a.staff_id, a.student_roll, a.class, a.subject, a.status, a.date, a.day_order,
               ar.status as request_status, ar.reason
        FROM attendance a 
        LEFT JOIN attendance_requests ar ON BINARY a.student_roll = BINARY ar.student_roll 
            AND a.date = ar.date AND ar.status = 'pending_hod'
        WHERE BINARY a.class = ? AND a.date = ? 
        ORDER BY a.student_roll ASC
    ";
    $stmt = $pdo->prepare($query);
    $stmt->execute(array($selected_year, $selected_date));
    $records = $stmt->fetchAll(PDO::FETCH_ASSOC);
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>HOD Attendance Records</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        body { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); min-height: 100vh; }
        
        /* 🔥 CLASS BUTTONS */
        .year-btn { height: 220px; width: 380px; font-size: 2.8rem; border-radius: 30px; margin: 35px; }
        .year-i { background: linear-gradient(45deg, #4CAF50, #45a049); color: white; }
        .year-ii { background: linear-gradient(45deg, #2196F3, #1976D2); color: white; }
        .year-iii { background: linear-gradient(45deg, #FF9800, #F57C00); color: white; }
        
        /* 🔥 SEMESTER BUTTONS */
        .semester-btn { height: 120px; width: 220px; font-size: 1.4rem; border-radius: 20px; margin: 15px; }
        .btn-odd { background: linear-gradient(135deg, #ff69b4, #ff1493); color: white; }
        .btn-even { background: linear-gradient(135deg, #ffc1cc, #ffb6c1); color: #333; }
        
        /* 🔥 DATE BUTTONS */
        .date-btn { height: 90px; width: 220px; font-size: 1.4rem; border-radius: 25px; margin: 12px; background: linear-gradient(45deg, #4CAF50, #45a049); color: white; }
        
        /* 🔥 RECORDS TABLE */
        .records-card { background: rgba(244, 8, 8, 0.98); border-radius: 25px; box-shadow: 0 25px 50px rgba(0,0,0,0.15); }
        .request-pending { background: linear-gradient(45deg, #fff3cd, #ffeaa7) !important; border-left: 5px solid #ffc107 !important; animation: pulse 2s infinite; }
        @keyframes pulse { 0% { box-shadow: 0 0 0 0 rgba(255,193,7,0.7); } 70% { box-shadow: 0 0 0 10px rgba(255,193,7,0); } 100% { box-shadow: 0 0 0 0 rgba(255,193,7,0); } }
        .status-present { background: linear-gradient(45deg, #d4edda, #c3e6cb); color: #155724; }
        .status-absent { background: linear-gradient(45deg, #f8d7da, #f5c6cb); color: #721c24; }
        .status-od { background: linear-gradient(45deg, #e2e3e5, #dee2e6); color: #495057; }
        .roll-no-col { font-family: 'Courier New', monospace; font-weight: bold; font-size: 1.1rem; }
    </style>
</head>
<body class="p-5">
    <div class="container">
        <?php if(!$selected_year): ?>
            <!-- 🔥 STEP 1: CLASS BUTTONS + DASHBOARD -->
            <div class="row justify-content-center text-center">
                <div class="col-12">
                    <!-- 🔥 DASHBOARD BUTTON -->
                    <a href="../hod/dashboard.php" class="btn btn-primary btn-lg px-5 py-3 fs-5 mb-5">
                        <i class="fas fa-tachometer-alt me-2"></i>📊 Dashboard
                    </a>
                    
                    <h1 class="display-3 fw-bold text-white mb-5">
                        <i class="fas fa-graduation-cap me-3"></i>HOD Attendance Records
                    </h1>
                    <div class="row justify-content-center">
                        <div class="col-md-4 mb-5">
                            <a href="?year=I Year" class="btn year-btn year-i w-100 p-5 text-decoration-none d-flex flex-column align-items-center justify-content-center">
                                <i class="fas fa-5x fa-user-graduate mb-4"></i>
                                <div style="font-size: 2.8rem;">I YEAR</div>
                            </a>
                        </div>
                        <div class="col-md-4 mb-5">
                            <a href="?year=II Year" class="btn year-btn year-ii w-100 p-5 text-decoration-none d-flex flex-column align-items-center justify-content-center">
                                <i class="fas fa-5x fa-user-graduate mb-4"></i>
                                <div style="font-size: 2.8rem;">II YEAR</div>
                            </a>
                        </div>
                        <div class="col-md-4 mb-5">
                            <a href="?year=III Year" class="btn year-btn year-iii w-100 p-5 text-decoration-none d-flex flex-column align-items-center justify-content-center">
                                <i class="fas fa-5x fa-user-graduate mb-4"></i>
                                <div style="font-size: 2.8rem;">III YEAR</div>
                            </a>
                        </div>
                    </div>
                </div>
            </div>

        <?php elseif(!$selected_semester): ?>
            <!-- 🔥 STEP 2: SEMESTER BUTTONS + DASHBOARD -->
            <div class="row justify-content-center text-center mb-5">
                <div class="col-12">
                    <!-- 🔥 DASHBOARD + BACK BUTTONS -->
                    <a href="../hod/dashboard.php" class="btn btn-primary btn-lg px-5 py-3 mb-4">
                        <i class="fas fa-tachometer-alt me-2"></i>📊 Dashboard
                    </a>
                    <a href="?" class="btn btn-secondary btn-lg px-5 py-3 mb-4 ms-2">
                        <i class="fas fa-arrow-left me-2"></i>← Back to Classes
                    </a>
                    
                    <h1 class="display-4 fw-bold text-white mb-4">
                        <i class="fas fa-layer-group me-3 text-warning"></i><?php echo htmlspecialchars($selected_year); ?>
                    </h1>
                    <p class="text-white-50 fs-4">Select Semester</p>
                </div>
            </div>
            
            <div class="row justify-content-center">
                <div class="col-md-5 col-sm-6 mb-4">
                    <a href="?year=<?php echo urlencode($selected_year); ?>&semester=odd" 
                       class="btn semester-btn btn-odd w-100 p-3 text-decoration-none d-flex flex-column align-items-center justify-content-center">
                        <i class="fas fa-2x fa-list-ol mb-2"></i>
                        <div style="font-size: 1.4rem;">ODD</div>
                        <small class="fw-bold mt-1">(Jun-Oct)</small>
                    </a>
                </div>
                <div class="col-md-5 col-sm-6 mb-4">
                    <a href="?year=<?php echo urlencode($selected_year); ?>&semester=even" 
                       class="btn semester-btn btn-even w-100 p-3 text-decoration-none d-flex flex-column align-items-center justify-content-center">
                        <i class="fas fa-2x fa-list mb-2"></i>
                        <div style="font-size: 1.4rem;">EVEN</div>
                        <small class="text-dark fw-bold mt-1">(Dec-Apr)</small>
                    </a>
                </div>
            </div>

        <?php elseif(!$selected_date): ?>
            <!-- 🔥 STEP 3: DATES + DASHBOARD -->
            <?php $range = getDateRange($selected_semester); ?>
            <div class="row mb-5">
                <div class="col-12 text-center">
                    <!-- 🔥 DASHBOARD + BACK BUTTONS -->
                    <a href="../hod/dashboard.php" class="btn btn-primary btn-lg px-5 py-3 mb-4">
                        <i class="fas fa-tachometer-alt me-2"></i>📊 Dashboard
                    </a>
                    <a href="?year=<?php echo urlencode($selected_year); ?>" class="btn btn-warning btn-lg px-5 py-3 mb-4 ms-2">
                        <i class="fas fa-layer-group me-2"></i>← Back to Semester
                    </a>
                    <a href="?" class="btn btn-secondary btn-lg px-5 py-3 mb-4 ms-2">
                        <i class="fas fa-arrow-left me-2"></i>← Back to Classes
                    </a>
                    
                    <h1 class="text-white mb-0">
                        <i class="fas fa-calendar-alt me-3 text-info"></i>
                        <?php echo htmlspecialchars($selected_year); ?> - <?php echo strtoupper($selected_semester); ?> Dates
                    </h1>
                    <p class="text-white-50 fs-4 mt-2"><?php echo count($dates); ?> dates | <?php echo date('d-m-Y', strtotime($range[0])); ?> to <?php echo date('d-m-Y', strtotime($range[1])); ?></p>
                </div>
            </div>
            <div class="row justify-content-center mb-5">
                <div class="col-lg-10 text-center">
                    <div class="row justify-content-center flex-wrap">
                        <?php foreach($dates as $date_row): ?>
                            <div class="col-md-3 col-sm-6 mb-4">
                                <a href="?year=<?php echo urlencode($selected_year); ?>&semester=<?php echo $selected_semester; ?>&date=<?php echo $date_row['date']; ?>" 
                                   class="btn date-btn w-100 d-flex flex-column align-items-center justify-content-center text-decoration-none">
                                    <i class="fas fa-calendar-day fa-2x mb-2"></i>
                                    <div style="font-size: 1.4rem; font-weight: bold;"><?php echo date('d-m-Y', strtotime($date_row['date'])); ?></div>
                                </a>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>

        <?php else: ?>
            <!-- 🔥 STEP 4: RECORDS + DASHBOARD -->
            <div class="row mb-5">
                <div class="col-12 text-center">
                    <!-- 🔥 DASHBOARD + ALL BACK BUTTONS -->
                    <a href="../hod/dashboard.php" class="btn btn-primary btn-lg px-5 py-3 mb-3">
                        <i class="fas fa-tachometer-alt me-2"></i>📊 Dashboard
                    </a>
                    <a href="?year=<?php echo urlencode($selected_year); ?>&semester=<?php echo $selected_semester; ?>" class="btn btn-info btn-lg px-5 py-3 mb-3 ms-2">
                        <i class="fas fa-calendar-alt me-2"></i>← Back to Dates
                    </a>
                    <a href="?year=<?php echo urlencode($selected_year); ?>" class="btn btn-warning btn-lg px-5 py-3 mb-3 ms-2">
                        <i class="fas fa-layer-group me-2"></i>← Back to Semester
                    </a>
                    <a href="?" class="btn btn-secondary btn-lg px-5 py-3 mb-3 ms-2">
                        <i class="fas fa-arrow-left me-2"></i>← Back to Classes
                    </a>
                    
                    <h1 class="text-white mb-0">
                        <i class="fas fa-table me-3 text-success"></i>
                        <?php echo htmlspecialchars($selected_year); ?> - <?php echo strtoupper($selected_semester); ?> - <?php echo date('d-m-Y', strtotime($selected_date)); ?>
                    </h1>
                    <p class="text-white-50 fs-4 mt-2">
                        Total: <?php echo count($records); ?> | 
                        <span class="badge bg-light text-success fs-6">
                            <i class="fas fa-sort-numeric-up me-1"></i>Roll No Order
                        </span>
                    </p>
                </div>
            </div>
            
            <div class="row">
                <div class="col-xl-11 mx-auto">
                    <div class="card records-card shadow-lg">
                        <div class="card-header bg-gradient text-white text-center py-4" style="background: linear-gradient(45deg, #28a745, #20c997);">
                            <h3 class="mb-0">
                                📋 <?php echo date('d-m-Y', strtotime($selected_date)); ?> Records 
                                <span class="badge bg-light text-dark fs-2 ms-3"><?php echo count($records); ?></span>
                                <?php 
                                $pending_requests = array();
                                foreach($records as $r) {
                                    if($r['request_status'] == 'pending_hod') {
                                        $pending_requests[] = $r;
                                    }
                                }
                                if(count($pending_requests)): 
                                ?>
                                    <span class="badge bg-warning text-dark fs-6 ms-2">
                                        <i class="fas fa-exclamation-triangle me-1"></i>
                                        <?php echo count($pending_requests); ?> Pending Requests
                                    </span>
                                <?php endif; ?>
                            </h3>
                        </div>
                        <div class="card-body p-0">
                            <?php if(empty($records)): ?>
                                <div class="text-center py-5">
                                    <i class="fas fa-calendar-times fa-5x text-muted mb-4"></i>
                                    <h4 class="text-muted">No records</h4>
                                </div>
                            <?php else: ?>
                                <div class="table-responsive">
                                    <table class="table table-hover align-middle mb-0">
                                        <thead class="table-dark sticky-top">
                                            <tr>
                                                <th width="60"><i class="fas fa-hashtag"></i> #</th>
                                                <th><i class="fas fa-id-card"></i> Roll No</th>
                                                <th><i class="fas fa-user"></i> Student</th>
                                                <th><i class="fas fa-user-tie"></i> Staff</th>
                                                <th><i class="fas fa-book"></i> Subject</th>
                                                <th><i class="fas fa-clock"></i> Day</th>
                                                <th><i class="fas fa-info-circle"></i> Status</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach($records as $index => $row): 
                                                $has_request = $row['request_status'] == 'pending_hod';
                                                $row_class = $has_request ? 'request-pending' : '';
                                                
                                                $status_class = strtolower($row['status']) == 'present' ? 'status-present' : 
                                                               (strtolower($row['status']) == 'od' ? 'status-od' : 'status-absent');
                                            ?>
                                                <tr class="<?php echo $row_class; ?>">
                                                    <td class="fw-bold"><?php echo $index+1; ?></td>
                                                    <td>
                                                        <span class="badge bg-primary roll-no-col">
                                                            <?php echo htmlspecialchars($row['student_roll']); ?>
                                                        </span>
                                                    </td>
                                                    <td><?php echo htmlspecialchars($row['student_roll']); ?></td>
                                                    <td><span class="badge bg-secondary"><?php echo htmlspecialchars($row['staff_id']); ?></span></td>
                                                    <td><?php echo htmlspecialchars(substr($row['subject'], 0, 25)); ?></td>
                                                    <td><span class="badge bg-warning"><?php echo htmlspecialchars($row['day_order']); ?></span></td>
                                                    <td>
                                                        <?php if($has_request): ?>
                                                            <div class="alert alert-warning alert-dismissible fade show p-2 mb-0" role="alert" style="font-size: 0.85rem;">
                                                                <i class="fas fa-exclamation-triangle me-1"></i>
                                                                <strong>REQUEST PENDING:</strong> <?php echo htmlspecialchars($row['reason']); ?>
                                                            </div>
                                                        <?php else: ?>
                                                            <span class="badge px-3 py-2 fs-6 fw-bold <?php echo $status_class; ?>">
                                                                <i class="fas <?php echo strtolower($row['status'])=='present' ? 'fa-check-circle text-success' : 'fa-times-circle text-danger'; ?> me-1"></i>
                                                                <?php echo ucfirst(strtolower($row['status'])); ?>
                                                            </span>
                                                        <?php endif; ?>
                                                    </td>
                                                </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        <?php endif; ?>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
