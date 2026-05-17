<?php 
session_start(); 
if(!isset($_SESSION['role']) || $_SESSION['role'] != 'staff') {
    header("Location: ../index.php"); exit();
}

include '../config/db_connection.php'; 
$pdo->exec("SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci");  // ✅ WAMP MySQL 5.7 SAFE

$today = date('Y-m-d');
$stmt = $pdo->prepare("SELECT day_order FROM day_order WHERE schedule_date = ?");
$stmt->execute(array($today));
$today_schedule = $stmt->fetch();
$auto_day_order = $today_schedule ? $today_schedule['day_order'] : date('D');
$is_holiday = strpos($auto_day_order, 'HOLIDAY') !== false;

$message = '';
$students = array();
$selected_class = '';
$selected_subject = '';
$class_subjects = array();

// 🔥 STEP 1: SELECT CLASS
if($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['select_class'])) {
    $selected_class = $_POST['class'];
    
    $stmt = $pdo->prepare("
        SELECT DISTINCT subject 
        FROM subjects_mapping 
        WHERE class_name COLLATE utf8mb4_unicode_ci = ?
        ORDER BY subject
    ");
    $stmt->execute(array($selected_class));
    $class_subjects_result = $stmt->fetchAll(PDO::FETCH_COLUMN);
    $class_subjects = is_array($class_subjects_result) ? $class_subjects_result : array();
}

// 🔥 STEP 2: LOAD STUDENTS
if($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['show_students'])) {
    $selected_class = $_POST['class'];
    $selected_subject = $_POST['subject'];
    
    $stmt = $pdo->prepare("
        SELECT DISTINCT s.roll_no, s.name 
        FROM students s
        INNER JOIN subjects_mapping sm ON 
            s.class COLLATE utf8mb4_unicode_ci = sm.class_name COLLATE utf8mb4_unicode_ci
        WHERE sm.class_name COLLATE utf8mb4_unicode_ci = ? 
         AND sm.subject COLLATE utf8mb4_unicode_ci = ?
        ORDER BY s.roll_no
    ");
    $stmt->execute(array($selected_class, $selected_subject));
    $students_result = $stmt->fetchAll();
    $students = is_array($students_result) ? $students_result : array();
}

// 🔥 SAVE ATTENDANCE
if($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['mark_attendance'])) {
    $class = $_POST['class'];
    $subject = $_POST['subject'];
    
    foreach($_POST['status'] as $roll_no => $status) {
        $stmt = $pdo->prepare("
            INSERT INTO attendance (staff_id, class, subject, student_roll, status, date, day_order) 
            VALUES (?, ?, ?, ?, ?, ?, ?) ON DUPLICATE KEY UPDATE status = VALUES(status)
        ");
        $stmt->execute(array($_SESSION['user_id'], $class, $subject, $roll_no, $status, $today, $auto_day_order));
    }
    $message = "✅ Attendance saved for {$class} - {$subject}!";
    $students = array();
}

$classes_result = $pdo->query("SELECT DISTINCT class_name FROM subjects_mapping ORDER BY class_name")->fetchAll(PDO::FETCH_COLUMN);
$classes = is_array($classes_result) ? $classes_result : array();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mark Attendance</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        .class-card { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; }
        .student-card { background: linear-gradient(135deg, #00b894, #00cec9); }
        
        /* 🔥 GREEN DEFAULT BOX */
        .green-present {
            background-color: #28a745 !important;
            color: white !important;
            border: 2px solid #28a745 !important;
            font-weight: bold !important;
        }
        
        .sidebar {
            position: sticky;
            top: 20px;
        }
    </style>
</head>
<body class="bg-light">
    <?php include '../includes/header.php'; ?>
    
    <div class="container-fluid mt-4">
        <div class="row">
            <!-- SIDEBAR -->
            <div class="col-md-3">
                <div class="card sidebar">
                    <div class="card-header bg-success text-white text-center">
                        <h5 class="mb-0"><?php echo htmlspecialchars($_SESSION['name']); ?></h5>
                        <small>Staff Panel</small>
                    </div>
                    <div class="list-group list-group-flush">
                        <a href="dashboard.php" class="list-group-item list-group-item-action">📊 Dashboard</a>
                        <a href="attendance.php" class="list-group-item list-group-item-action active">✅ Mark Attendance</a>
                        <a href="../auth/logout.php" class="list-group-item list-group-item-action text-danger">🚪 Logout</a>
                    </div>
                </div>
            </div>
            
            <!-- MAIN -->
            <div class="col-md-9">
                <div class="d-flex justify-content-between mb-4">
                    <h2 class="text-primary"><i class="fas fa-calendar-day me-2"></i>Mark Attendance</h2>
                    <span class="badge fs-5 px-4 py-2 bg-<?php echo $is_holiday ? 'danger' : 'success'; ?>">
                        <?php echo date('d-m-Y'); ?> (<?php echo htmlspecialchars($auto_day_order); ?>)
                    </span>
                </div>

                <?php if($message): ?>
                <div class="alert alert-success alert-dismissible fade show">
                    <i class="fas fa-check-circle me-2"></i><?php echo htmlspecialchars($message); ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
                <?php endif; ?>

                <?php if($is_holiday): ?>
                <div class="alert alert-warning text-center p-5">
                    <i class="fas fa-calendar-times fa-3x mb-3 text-warning"></i>
                    <h3>TODAY IS HOLIDAY</h3>
                </div>
                <?php else: ?>

                <?php if(empty($selected_class)): ?>
                <!-- 🔥 STEP 1: SELECT CLASS -->
                <div class="card shadow-lg mb-5 class-card">
                    <div class="card-body p-4 text-center">
                        <h4 class="mb-4"><i class="fas fa-graduation-cap me-2"></i>Select Your Class</h4>
                        <form method="POST">
                            <div class="row justify-content-center">
                                <div class="col-md-6">
                                    <select name="class" class="form-select form-control-lg" required>
                                        <option value="">🎓 Choose Class</option>
                                        <?php foreach($classes as $class): ?>
                                            <option value="<?php echo htmlspecialchars($class); ?>">
                                                <?php echo htmlspecialchars($class); ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                    <button type="submit" name="select_class" class="btn btn-light btn-lg w-100 mt-4">
                                        <i class="fas fa-arrow-right me-2"></i>NEXT → Subjects
                                    </button>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>

                <?php elseif(empty($students)): ?>
                <!-- 🔥 STEP 2: SELECT SUBJECT -->
                <div class="card shadow-lg mb-5 class-card">
                    <div class="card-body p-4">
                        <div class="d-flex justify-content-between align-items-center mb-4">
                            <h5><i class="fas fa-book me-2"></i>Class: <?php echo htmlspecialchars($selected_class); ?></h5>
                            <a href="attendance.php" class="btn btn-outline-light btn-sm">
                                <i class="fas fa-redo me-1"></i>Change Class
                            </a>
                        </div>
                        
                        <form method="POST">
                            <input type="hidden" name="class" value="<?php echo htmlspecialchars($selected_class); ?>">
                            <div class="row g-4 align-items-end">
                                <div class="col-md-8">
                                    <label class="form-label fw-bold text-white fs-5">Select Subject</label>
                                    <select name="subject" class="form-select form-control-lg" required>
                                        <option value="">📚 Choose Subject</option>
                                        <?php foreach($class_subjects as $subject): ?>
                                            <option value="<?php echo htmlspecialchars($subject); ?>">
                                                <?php echo htmlspecialchars($subject); ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div class="col-md-4">
                                    <button type="submit" name="show_students" class="btn btn-light btn-lg w-100">
                                        <i class="fas fa-users me-2"></i>LOAD STUDENTS
                                    </button>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>

                <?php endif; ?>

                <?php if(!empty($students)): ?>
                <!-- 🔥 STEP 3: MARK ATTENDANCE -->
                <div class="card shadow-lg student-card mb-4">
                    <div class="card-header text-white p-4">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <h4 class="mb-1">
                                    <i class="fas fa-list me-2"></i><?php echo htmlspecialchars($selected_class); ?>
                                    <span class="badge bg-light text-dark fs-6"><?php echo count($students); ?> Students</span>
                                </h4>
                                <small class="text-white-50"><?php echo htmlspecialchars($selected_subject); ?></small>
                            </div>
                            <a href="attendance.php" class="btn btn-light btn-sm">
                                <i class="fas fa-redo me-1"></i>New Selection
                            </a>
                        </div>
                    </div>
                    
                    <form method="POST">
                        <input type="hidden" name="class" value="<?php echo htmlspecialchars($selected_class); ?>">
                        <input type="hidden" name="subject" value="<?php echo htmlspecialchars($selected_subject); ?>">
                        
                        <div class="table-responsive">
                            <table class="table table-hover mb-0">
                                <thead class="table-dark">
                                    <tr>
                                        <th width="20%">Roll No</th>
                                        <th width="50%">Student Name</th>
                                        <th width="30%">Status</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach($students as $student): ?>
                                    <tr>
                                        <td><strong><?php echo htmlspecialchars($student['roll_no']); ?></strong></td>
                                        <td><?php echo htmlspecialchars($student['name']); ?></td>
                                        <td>
                                            <!-- 🔥 GREEN PRESENT DEFAULT -->
                                            <select name="status[<?php echo $student['roll_no']; ?>]" class="form-select fw-bold green-present">
                                                <option value="Present" selected>✅ Present</option>
                                                <option value="Absent">❌ Absent</option>
                                            </select>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                        
                        <div class="card-footer bg-light p-4">
                            <button type="submit" name="mark_attendance" class="btn btn-success btn-lg w-100">
                                <i class="fas fa-save me-2"></i>✅ SAVE ATTENDANCE (<?php echo count($students); ?> Students)
                            </button>
                        </div>
                    </form>
                </div>
                <?php endif; ?>

                <?php endif; ?>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
