<?php
// calculate_attendance.php - FIXED FOR PHP 5.3+ (ONLY SYNTAX ERRORS CORRECTED)
session_start();
include '../config/db_connection.php';

$selected_year = isset($_GET['year']) ? $_GET['year'] : '';
$selected_semester = isset($_GET['semester']) ? $_GET['semester'] : '';

function getDateRange($semester) {
    if($semester == 'odd') return array('2025-06-16', '2025-10-24');  // Jun-Oct ODD
    else return array('2025-12-04', '2026-04-17');                   // Dec-Apr EVEN
}

// Initialize variables
$perfect_count = $good_count = $medical_count = $fine_count = $redo_count = 0;
$total_days = 0;
$students_list = array();
$error = '';

// PDF EXPORT (SEMESTER FILTER ADDED)
if(isset($_GET['category']) && isset($_GET['export']) && $_GET['export'] == 'pdf') {
    $category = $_GET['category'];
    
    if($selected_semester) {
        $range = getDateRange($selected_semester);
        $stmt = $pdo->prepare("SELECT COUNT(DISTINCT date) as total_days FROM attendance WHERE class = ? AND date BETWEEN ? AND ?");
        $stmt->execute(array($selected_year, $range[0], $range[1]));
        $total_days_result = $stmt->fetch(PDO::FETCH_ASSOC);
        $total_days = isset($total_days_result['total_days']) ? $total_days_result['total_days'] : 0;
        
        $stmt = $pdo->prepare("
            SELECT student_roll, COUNT(*) as total_classes, 
                   SUM(CASE WHEN status = 'Present' THEN 1 ELSE 0 END) as present_days,
                   ROUND((SUM(CASE WHEN status = 'Present' THEN 1 ELSE 0 END) / COUNT(*)) * 100, 1) as percentage
            FROM attendance WHERE class = ? AND date BETWEEN ? AND ? GROUP BY student_roll ORDER BY student_roll ASC
        ");
        $stmt->execute(array($selected_year, $range[0], $range[1]));
    } else {
        $stmt = $pdo->prepare("SELECT COUNT(DISTINCT date) as total_days FROM attendance WHERE class = ?");
        $stmt->execute(array($selected_year));
        $total_days_result = $stmt->fetch(PDO::FETCH_ASSOC);
        $total_days = isset($total_days_result['total_days']) ? $total_days_result['total_days'] : 0;
        
        $stmt = $pdo->prepare("
            SELECT student_roll, COUNT(*) as total_classes, 
                   SUM(CASE WHEN status = 'Present' THEN 1 ELSE 0 END) as present_days,
                   ROUND((SUM(CASE WHEN status = 'Present' THEN 1 ELSE 0 END) / COUNT(*)) * 100, 1) as percentage
            FROM attendance WHERE class = ? GROUP BY student_roll ORDER BY student_roll ASC
        ");
        $stmt->execute(array($selected_year));
    }
    
    $students = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    $category_students = array();
    foreach($students as $student) {
        $percentage = $student['percentage'];
        $absent = $student['total_classes'] - $student['present_days'];
        
        if(($category == 'PERFECT' && $percentage == 100) ||
           ($category == 'GOOD' && $percentage >= 75 && $percentage < 100) ||
           ($category == 'MEDICAL' && $percentage >= 70 && $percentage < 75) ||
           ($category == 'FINE' && $percentage >= 60 && $percentage < 70) ||
           ($category == 'REDO' && $percentage < 60)) {
            $student['absent_days'] = $absent;
            $category_students[] = $student;
        }
    }
    ?>
    <!DOCTYPE html>
    <html><head>
        <title><?php echo $category ?> Report - <?php echo $selected_year ?> <?php echo $selected_semester ? '(' . strtoupper($selected_semester) . ')' : '' ?></title>
        <style>* { margin: 0; padding
        0; padding: 0; font-family: 'Segoe UI', sans-serif; }
            body { padding: 10px; background: white; line-height: 1.4; }
            .header { text-align: center; margin-bottom: 10px; }
            .header h1 { color: #007bff; font-size: 20px; margin-bottom: 5px; }
            .header h2 { color: #28a745; font-size: 24px; margin-bottom: 10px; }
            .header p { font-size: 16px; color: #333; margin: 5px 0; }
            .info-box { background: #e9ecef; padding: 5px; border-radius: 5px; margin: 10px 0; text-align: center; }
            table { width: 100%; border-collapse: collapse; margin-top: 20px; font-size: 14px; }
            th { background: #007bff !important; color: white !important; padding: 15px 10px; text-align: center; font-weight: bold; }
            td { padding: 12px 10px; text-align: center; border: 1px solid #ddd; }
            tr:nth-child(even) { background: #f8f9fa; }
            .perfect-row { background: #d4edda !important; }
            .good-row { background: #d1ecf1 !important; }
            .medical-row { background: #fff3cd !important; }
            .fine-row { background: #ffeaa7 !important; }
            .redo-row { background: #f8d7da !important; }
            .percentage { font-weight: bold; font-size: 16px; color: #007bff; }
            .footer { margin-top: 40px; text-align: center; font-size: 12px; color: #666; }
            @media print { body { padding: 20px; } .no-print { display: none; } }
            @page { margin: 2cm; }
        </style>
    </head>
    <body onload="window.print(); setTimeout(function() { window.close(); }, 1000);">
        <div class="header">
            <h1>📊 <?php echo $category ?> ATTENDANCE REPORT</h1>
            <h2><?php echo htmlspecialchars($selected_year) ?> <?php echo $selected_semester ? '- '.strtoupper($selected_semester) : '' ?></h2>
            <p class="info-box"><strong>Total Working Days: <?php echo $total_days ?></strong> | Students: <?php echo count($category_students) ?></p>
            <p>Generated on: <?php echo date('d F Y, H:i:s') ?> | Page 1 of 1</p>
        </div>
        <table>
            <thead><tr><th style="width: 20%;">Roll No</th><th style="width: 15%;">Total Days</th><th style="width: 15%;">Present</th><th style="width: 15%;">Absent</th><th style="width: 20%;">Percentage</th></tr></thead>
            <tbody>
                <?php foreach($category_students as $student): ?>
                <tr class="<?php echo strtolower($category) ?>-row">
                    <td><strong><?php echo htmlspecialchars($student['student_roll']) ?></strong></td>
                    <td><?php echo $student['total_classes'] ?></td>
                    <td><?php echo $student['present_days'] ?></td>
                    <td><?php echo $student['absent_days'] ?></td>
                    <td class="percentage"><?php echo $student['percentage'] ?>%</td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        <div class="footer"><p>EDU-SANA Attendance Management System | HOD Report</p></div>
    </body></html>
    <?php exit(); }

// MAIN DATA CALCULATION (SEMESTER FILTER)
if($selected_year) {
    try {
        if($selected_semester) {
            $range = getDateRange($selected_semester);
            $stmt = $pdo->prepare("SELECT COUNT(DISTINCT date) as total_days FROM attendance WHERE class = ? AND date BETWEEN ? AND ?");
            $stmt->execute(array($selected_year, $range[0], $range[1]));
            $total_days_result = $stmt->fetch(PDO::FETCH_ASSOC);
            $total_days = isset($total_days_result['total_days']) ? $total_days_result['total_days'] : 0;
            
            $stmt = $pdo->prepare("
                SELECT student_roll, COUNT(*) as total_classes, 
                       SUM(CASE WHEN status = 'Present' THEN 1 ELSE 0 END) as present_days,
                       ROUND((SUM(CASE WHEN status = 'Present' THEN 1 ELSE 0 END) / COUNT(*)) * 100, 1) as percentage
                FROM attendance WHERE class = ? AND date BETWEEN ? AND ? GROUP BY student_roll ORDER BY student_roll ASC
            ");
            $stmt->execute(array($selected_year, $range[0], $range[1]));
        } else {
            $stmt = $pdo->prepare("SELECT COUNT(DISTINCT date) as total_days FROM attendance WHERE class = ?");
            $stmt->execute(array($selected_year));
            $total_days_result = $stmt->fetch(PDO::FETCH_ASSOC);
            $total_days = isset($total_days_result['total_days']) ? $total_days_result['total_days'] : 0;
            
            $stmt = $pdo->prepare("
                SELECT student_roll, COUNT(*) as total_classes, 
                       SUM(CASE WHEN status = 'Present' THEN 1 ELSE 0 END) as present_days,
                       ROUND((SUM(CASE WHEN status = 'Present' THEN 1 ELSE 0 END) / COUNT(*)) * 100, 1) as percentage
                FROM attendance WHERE class = ? GROUP BY student_roll ORDER BY student_roll ASC
            ");
            $stmt->execute(array($selected_year));
        }
        
        $students_list = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        foreach($students_list as $student) {
            $percentage = $student['percentage'];
            if($percentage == 100) $perfect_count++;
            elseif($percentage >= 75) $good_count++;
            elseif($percentage >= 70) $medical_count++;
            elseif($percentage >= 60) $fine_count++;
            else $redo_count++;
        }
    } catch(Exception $e) {
        $error = $e->getMessage();
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>HOD Attendance Report - 5 Categories + PDF + SEMESTER</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <style>
        body { background: linear-gradient(135deg, #f5f7fa 0%, #c3cfe2 100%); min-height: 50vh; }
        .category-card { border
            border-radius: 20px; box-shadow: 0 15px 35px rgba(0,0,0,0.1); transition: all 0.3s ease; height: 100%; position: relative; overflow: hidden; }
        .category-card:hover { transform: translateY(-8px); box-shadow: 0 25px 50px rgba(0,0,0,0.15); }
        .perfect-card { border-top: 8px solid #28a745; background: linear-gradient(135deg, #d4edda, #c3e6cb); }
        .good-card { border-top: 8px solid #17a2b8; background: linear-gradient(135deg, #d1ecf1, #bee5eb); }
        .medical-card { border-top: 8px solid #ffc107; background: linear-gradient(135deg, #fff3cd, #ffeaa7); }
        .fine-card { border-top: 8px solid #fd7e14; background: linear-gradient(135deg, #fff3cd, #ffeaa7); }
        .redo-card { border-top: 8px solid #dc3545; background: linear-gradient(135deg, #f8d7da, #f5c6cb); }
        .pdf-btn { position: absolute; top: 15px; right: 15px; z-index: 10; background: rgba(255,255,255,0.95); border-radius: 25px; padding: 8px 15px; box-shadow: 0 4px 15px rgba(0,0,0,0.1); }
        .action-badge { font-weight: bold; font-size: 11px; padding: 6px 12px; border-radius: 15px; min-width: 120px; text-align: center; display: inline-block; }
        
        .total-days-card { 
            background: linear-gradient(135deg, #28a745, #20c997); 
            border-radius: 15px !important; 
            color: white; 
            box-shadow: 0 10px 30px rgba(40,167,69,0.4) !important;
            width: 180px !important; 
            border: none;
        }
        
        .semester-btn { height: 120px; width: 220px; font-size: 1.4rem; border-radius: 20px; margin: 15px; border: none; transition: all 0.3s ease; box-shadow: 0 8px 25px rgba(0,0,0,0.15); }
        .semester-btn:hover { transform: translateY(-5px) scale(1.03); box-shadow: 0 15px 35px rgba(0,0,0,0.25); }
        .btn-odd { background: linear-gradient(135deg, #ff1493, #ff69b4); color: white; box-shadow: 0 5px 15px rgba(255,20,147,0.4); }
        .btn-even { background: linear-gradient(135deg, #ff69b4, #ffb6c1); color: white; font-weight: bold; box-shadow: 0 5px 15px rgba(255,105,180,0.4); }
        @media (max-width: 768px) { 
            .pdf-btn { position: static; margin: 10px auto; display: block; width: 150px; } 
            .total-days-card { position: static !important; margin: 0 auto 20px !important; width: 150px !important; }
        }
    </style>
</head>
<body>
<div class="container py-5 px-3">

    <div class="mb-4 text-center">
        <a href="dashboard.php" class="btn btn-secondary btn-lg px-5 py-3 shadow-lg">
            <i class="fas fa-arrow-left me-2"></i>← Back to HOD Dashboard
        </a>
    </div>

    <div class="text-center mb-5">
        <h1 class="display-4 fw-bold text-primary mb-4" style="text-shadow: 2px 2px 4px rgba(0,0,0,0.1);">
            📊 HOD ATTENDANCE REPORT
        </h1>
        
        <?php if(!$selected_year): ?>
            <div class="d-flex justify-content-center flex-wrap gap-3">
                <a href="?year=I Year" class="btn btn-primary btn-lg px-5 py-3 fs-4 shadow-lg">📚 I Year</a>
                <a href="?year=II Year" class="btn btn-primary btn-lg px-5 py-3 fs-4 shadow-lg">📚 II Year</a>
                <a href="?year=III Year" class="btn btn-primary btn-lg px-5 py-3 fs-4 shadow-lg">📚 III Year</a>
            </div>
            
        <?php elseif(!$selected_semester): ?>
            <div class="mb-4">
                <a href="?" class="btn btn-secondary btn-lg px-5 py-3 shadow-lg"><i class="fas fa-arrow-left me-2"></i>← Back</a>
            </div>
            <h2 class="display-5 fw-bold text-danger mb-4"><?php echo htmlspecialchars($selected_year) ?> - Select Semester</h2>
            <div class="row justify-content-center">
                <div class="col-md-5 col-sm-6">
                    <a href="?year=<?php echo urlencode($selected_year) ?>&semester=odd" class="btn semester-btn btn-odd w-100 p-4 text-decoration-none d-flex flex-column align-items-center justify-content-center">
                        <i class="fas fa-2x fa-list-ol mb-2"></i><div>ODD</div><small class="fw-bold">(Jun-Oct)</small>
                    </a>
                </div>
                <div class="col-md-5 col-sm-6">
                    <a href="?year=<?php echo urlencode($selected_year) ?>&semester=even" class="btn semester-btn btn-even w-100 p-4 text-decoration-none d-flex flex-column align-items-center justify-content-center">
                        <i class="fas fa-2x fa-list mb-2"></i><div>EVEN</div><small class="fw-bold">(Dec-Apr)</small>
                    </a>
                </div>
            </div>
            
        <?php else: ?>
            <?php $range = getDateRange($selected_semester); ?>
            
            <div class="position-fixed top-0 end-0 m-4 z-3" style="width: 180px;">
                <div class="card total-days-card shadow-lg border-0">
                    <div class="card-body p-3 text-center">
                        <i class="fas fa-calendar-day fa-2x mb-2 opacity-90"></i>
                        <h2 class="fw-bold mb-1"><?php echo $total_days ?></h2>
                        <small class="text-white-50"><?php echo strtoupper($selected_semester) ?></small>
                    </div>
                </div>
            </div>

            <div class="alert alert-success fs-3 fw-bold mb-3 mt-5">
                <i class="fas fa-calendar-check me-2"></i><?php echo strtoupper($selected_semester) ?> SEMESTER 
                <span class="badge bg-dark fs-6"><?php echo date('d-m-Y', strtotime($range[0])) ?> → <?php echo date('d-m-Y', strtotime($range[1])) ?></span>
            </div>
            <a href="?year=<?php echo urlencode($selected_year) ?>" class="btn btn-warning btn-lg px-4 py-2 mb-4">
                <i class="fas fa-layer-group me-2"></i>← Change Semester
            </a>

            <div class="row g-4">
                <!-- PERFECT -->
                <div class="col-xl-6 col-lg-6 col-md-12">
                    <div class="card category-card perfect-card h-100 position-relative">
                        <a href="?year=<?php echo urlencode($selected_year) ?>&semester=<?php echo $selected_semester ?>&category=PERFECT&export=pdf" class="pdf-btn btn btn-outline-success btn-sm shadow-sm" target="_blank">
                            <i class="fas fa-file-pdf me-1"></i>PDF
                        </a>
                        <div class="card-header bg-success text-white py-4">
                            <h4 class="mb-0 fw-bold">
                                <i class="fas fa-medal-star me-2"></i>✅ PERFECT (100%)
                                <span class="badge bg-white text-success fs-6 ms-2 py-2 px-3"><?php echo $perfect_count ?> Students</span>
                            </h4>
                        </div>
                        <div class="card-body p-0">
                            <?php 
                            $perfect_students = array();
                            foreach($students_list as $s) {
                                if($s['percentage'] == 100) $perfect_students[] = $s;
                            }
                            ?>
                            <?php if(!empty($perfect_students)): ?>
                            <div class="table-responsive" style="max-height: 350px;">
                                <table class="table table-hover table-sm mb-0">
                                    <thead class="table-success"><tr><th>Roll No</th><th>Total</th><th>Present</th><th>Status</th></tr></thead>
                                    <tbody>
                                        <?php foreach($perfect_students as $student): ?>
                                        <tr><td><strong><?php echo htmlspecialchars($student['student_roll']) ?></strong></td><td class="text-center"><?php echo $student['total_classes'] ?></td><td class="text-center text-success"><?php echo $student['present_days'] ?></td><td><span class="action-badge bg-success text-white">PERFECT 100%</span></td></tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                            <?php else: ?>
                            <div class="text-center py-5 bg-light">
                                <i class="fas fa-medal fa-3x text-success mb-3 opacity-75"></i>
                                <h5 class="text-success">No Perfect Attendance Students</h5>
                            </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>

                <!-- GOOD -->
                <div class="col-xl-6 col-lg-6 col-md-12">
                    <div class="card category-card good-card h-100 position-relative">
                        <a href="?year=<?php echo urlencode($selected_year) ?>&semester=<?php echo $selected_semester ?>&category=GOOD&export=pdf" class="pdf-btn btn btn-outline-info btn-sm shadow-sm" target="_blank">
                            <i class="fas fa-file-pdf me-1"></i>PDF
                        </a>
                        <div class="card-header bg-info text-white py-4">
                            <h4 class="mb-0 fw-bold">
                                <i class="fas fa-thumbs-up me-2"></i>👍 GOOD (75-99%)
                                <span class="badge bg-white text-info fs-6 ms-2 py-2 px-3"><?php echo $good_count ?> Students</span>
                            </h4>
                        </div>
                        <div class="card-body p-0">
                            <?php 
                            $good_students = array();
                            foreach($students_list as $s) {
                                if($s['percentage'] >= 75 && $s['percentage'] < 100) $good_students[] = $s;
                            }
                            ?>
                            <?php if(!empty($good_students)): ?>
                            <div class="table-responsive" style="max-height: 350px;">
                                <table class="table table-hover table-sm mb-0">
                                    <thead class="table-info"><tr><th>Roll No</th><th>Total</th><th>Present</th><th>%</th></tr></thead>
                                    <tbody>
                                        <?php foreach($good_students as $student): ?>
                                        <tr><td><strong><?php echo htmlspecialchars($student['student_roll']) ?></strong></td><td class="text-center"><?php echo $student['total_classes'] ?></td><td class="text-center"><?php echo $student['present_days'] ?></td><td><span class="badge bg-info"><?php echo $student['percentage'] ?>%</span></td></tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                            <?php else: ?>
                            <div class="text-center py-5 bg-light">
                                <i class="fas fa-thumbs-up fa-3x text-info mb-3 opacity-75"></i>
                                <h5 class="text-info">No Good Attendance Students</h5>
                            </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>

                <!-- MEDICAL -->
                <div class="col-xl-6 col-lg-6 col-md-12">
                    <div class="card category-card medical-card h-100 position-relative">
                        <a href="?year=<?php echo urlencode($selected_year) ?>&semester=<?php echo $selected_semester ?>&category=MEDICAL&export=pdf" class="pdf-btn btn btn-outline-warning btn-sm shadow-sm" target="_blank">
                            <i class="fas fa-file-pdf me-1"></i>PDF
                        </a>
                        <div class="card-header bg-warning text-dark py-4">
                            <h4 class="mb-0 fw-bold">
                                <i class="fas fa-heartbeat me-2"></i>🩺 MEDICAL (70-74%)
                                <span class="badge bg-dark text-warning fs-6 ms-2 py-2 px-3"><?php echo $medical_count ?> Students</span>
                            </h4>
                        </div>
                        <div class="card-body p-0">
                            <?php 
                            $medical_students = array();
                            foreach($students_list as $s) {
                                if($s['percentage'] >= 70 && $s['percentage'] < 75) $medical_students[] = $s;
                            }
                            ?>
                            <?php if(!empty($medical_students)): ?>
                            <div class="table-responsive" style="max-height: 350px;">
                                <table class="table table-hover table-sm mb-0">
                                    <thead class="table-warning"><tr><th>Roll No</th><th>Total</th><th>Present</th><th>%</th></tr></thead>
                                    <tbody>
                                        <?php foreach($medical_students as $student): ?>
                                        <tr><td><strong><?php echo htmlspecialchars($student['student_roll']) ?></strong></td><td class="text-center"><?php echo $student['total_classes'] ?></td><td class="text-center"><?php echo $student['present_days'] ?></td><td><span class="badge bg-warning text-dark"><?php echo $student['percentage'] ?>% - Medical</span></td></tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                            <?php else: ?>
                            <div class="text-center py-5 bg-light">
                                <i class="fas fa-heartbeat fa-3x text-warning mb-3 opacity-75"></i>
                                <h5 class="text-warning">No Medical Certificate Students</h5>
                            </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>

                <!-- FINE -->
                <div class="col-xl-6 col-lg-6 col-md-12">
                    <div class="card
                    category-card fine-card h-100 position-relative">
                        <a href="?year=<?php echo urlencode($selected_year) ?>&semester=<?php echo $selected_semester ?>&category=FINE&export=pdf" class="pdf-btn btn btn-outline-warning btn-sm shadow-sm" target="_blank">
                            <i class="fas fa-file-pdf me-1"></i>PDF
                        </a>
                        <div class="card-header bg-warning text-dark py-4">
                            <h4 class="mb-0 fw-bold">
                                <i class="fas fa-exclamation-triangle me-2"></i>💰 FINE (60-69%)
                                <span class="badge bg-dark text-warning fs-6 ms-2 py-2 px-3"><?php echo $fine_count ?> Students</span>
                            </h4>
                        </div>
                        <div class="card-body p-0">
                            <?php 
                            $fine_students = array();
                            foreach($students_list as $s) {
                                if($s['percentage'] >= 60 && $s['percentage'] < 70) $fine_students[] = $s;
                            }
                            ?>
                            <?php if(!empty($fine_students)): ?>
                            <div class="table-responsive" style="max-height: 350px;">
                                <table class="table table-hover table-sm mb-0">
                                    <thead class="table-warning"><tr><th>Roll No</th><th>Total</th><th>Present</th><th>%</th></tr></thead>
                                    <tbody>
                                        <?php foreach($fine_students as $student): ?>
                                        <tr><td><strong><?php echo htmlspecialchars($student['student_roll']) ?></strong></td><td class="text-center"><?php echo $student['total_classes'] ?></td><td class="text-center"><?php echo $student['present_days'] ?></td><td><span class="badge bg-warning text-dark"><?php echo $student['percentage'] ?>% - Fine</span></td></tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                            <?php else: ?>
                            <div class="text-center py-5 bg-light">
                                <i class="fas fa-exclamation-triangle fa-3x text-warning mb-3 opacity-75"></i>
                                <h5 class="text-warning">No Fine Students</h5>
                            </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>

                <!-- REDO - FULL WIDTH -->
                <div class="col-12">
                    <div class="card category-card redo-card h-100 position-relative">
                        <a href="?year=<?php echo urlencode($selected_year) ?>&semester=<?php echo $selected_semester ?>&category=REDO&export=pdf" class="pdf-btn btn btn-outline-danger btn-sm shadow-sm" target="_blank">
                            <i class="fas fa-file-pdf me-1"></i>PDF
                        </a>
                        <div class="card-header bg-danger text-white py-4">
                            <h4 class="mb-0 fw-bold">
                                <i class="fas fa-exclamation-circle me-2"></i>🚨 REDO / REPEAT (<60%)
                                <span class="badge bg-white text-danger fs-6 ms-2 py-2 px-3"><?php echo $redo_count ?> Students</span>
                            </h4>
                        </div>
                        <div class="card-body p-0">
                            <?php 
                            $redo_students = array();
                            foreach($students_list as $s) {
                                if($s['percentage'] < 60) $redo_students[] = $s;
                            }
                            ?>
                            <?php if(!empty($redo_students)): ?>
                            <div class="table-responsive" style="max-height: 350px;">
                                <table class="table table-hover table-sm mb-0">
                                    <thead class="table-danger"><tr><th>Roll No</th><th>Total</th><th>Present</th><th>Absent</th><th>Percentage</th></tr></thead>
                                    <tbody>
                                        <?php foreach($redo_students as $student): ?>
                                        <tr class="table-danger">
                                            <td><strong class="text-danger"><?php echo htmlspecialchars($student['student_roll']) ?></strong></td>
                                            <td class="text-center"><?php echo $student['total_classes'] ?></td>
                                            <td class="text-center text-danger"><?php echo $student['present_days'] ?></td>
                                            <td class="text-center text-danger"><?php echo $student['total_classes'] - $student['present_days'] ?></td>
                                            <td class="text-center"><span class="badge bg-danger fs-6"><?php echo $student['percentage'] ?>%</span></td>
                                        </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                            <?php else: ?>
                            <div class="text-center py-5 bg-light">
                                <i class="fas fa-check-circle fa-3x text-success mb-3"></i>
                                <h5 class="text-success">🎉 No Redo Students - Excellent!</h5>
                            </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>

        <?php endif; ?>
    </div>

</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
