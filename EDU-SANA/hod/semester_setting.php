<?php 
session_start(); 
if(!isset($_SESSION['role']) || $_SESSION['role'] != 'hod') {
    header("Location: ../public/index.php"); exit();
}
include '../config/db_connection.php'; 

// 🔥 SEMESTER FORM PROCESSING
$message = '';
if(isset($_POST['submit'])) {
    $semester_name = $_POST['semester_name'];
    $start_date = $_POST['start_date'];
    $end_date = $_POST['end_date'];
    $is_active = isset($_POST['is_active']) ? 1 : 0;
    
    $stmt = $pdo->prepare("INSERT INTO semesters (semester_name, start_date, end_date, is_active, created_by) VALUES (?, ?, ?, ?, ?)");
    if($stmt->execute([$semester_name, $start_date, $end_date, $is_active, $_SESSION['name']])) {
        $message = '✅ Semester added successfully!';
    } else {
        $message = '❌ Error adding semester!';
    }
}

// 🔥 UPDATE ACTIVE STATUS
if(isset($_POST['toggle_active'])) {
    $semester_id = $_POST['semester_id'];
    $stmt = $pdo->query("UPDATE semesters SET is_active = 0");
    $stmt = $pdo->prepare("UPDATE semesters SET is_active = 1 WHERE id = ?");
    $stmt->execute([$semester_id]);
    $message = '✅ Active semester updated!';
}

// 🔥 GET ALL SEMESTERS
$semesters = $pdo->query("SELECT * FROM semesters ORDER BY end_date DESC")->fetchAll();
?>

<!DOCTYPE html>
<html>
<head>
    <title>HOD Semester Settings</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css" rel="stylesheet">
    
    <style>
        /* 🔥 SAME PERFECT DESIGN AS DASHBOARD */
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        .main-container { min-height: 100vh; padding: 20px 0; }
        
        /* 🔥 SAME SIDEBAR */
        .sidebar {
            background: linear-gradient(180deg, #1e3a8a, #1e40af);
            border-radius: 20px;
            box-shadow: 0 20px 40px rgba(0,0,0,0.4);
            height: fit-content;
            position: sticky;
            top: 20px;
        }
        .sidebar .card-header {
            background: linear-gradient(135deg, #3b82f6, #1d4ed8);
            color: white !important;
            border-radius: 20px 20px 0 0 !important;
            text-align: center;
            padding: 25px;
        }
        .sidebar h5 { color: white !important; font-weight: 800; font-size: 1.5rem; margin-bottom: 8px; }
        .hod-badge {
            background: rgba(255,255,255,0.95) !important;
            color: #1e40af !important;
            font-weight: 800 !important;
            padding: 8px 20px;
            border-radius: 25px;
            font-size: 0.9rem;
            box-shadow: 0 4px 12px rgba(0,0,0,0.2);
        }
        .list-group-item {
            border: none !important;
            padding: 18px 25px !important;
            transition: all 0.3s ease !important;
            color: #f8fafc !important;
            cursor: pointer !important;
            font-weight: 500;
        }
        .list-group-item:hover { background: rgba(255,255,255,0.15) !important; color: white !important; transform: translateX(8px); }
        .list-group-item.active { background: linear-gradient(135deg, #10b981, #059669) !important; color: white !important; }
        .list-group-item i { color: #e2e8f0 !important; margin-right: 12px; }
        .list-group-item:hover i, .list-group-item.active i { color: white !important; }
        .list-group-item[href*="logout"] { background: linear-gradient(135deg, #ef4444, #dc2626); margin-top: 10px; border-radius: 0 0 20px 20px !important; }
        
        /* 🔥 SEMESTER FORM */
        .form-card {
            border-radius: 25px;
            box-shadow: 0 25px 50px rgba(0,0,0,0.25);
            backdrop-filter: blur(10px);
            background: rgba(255,255,255,0.95);
            border: 1px solid rgba(255,255,255,0.2);
        }
        .form-card .card-header {
            background: linear-gradient(135deg, #8b5cf6, #7c3aed);
            color: white;
            padding: 25px;
            border-radius: 25px 25px 0 0;
            text-align: center;
        }
        
        /* 🔥 SEMESTER TABLE */
        .semester-table thead th {
            background: linear-gradient(135deg, #2d3436, #636e72);
            color: white;
            border: none;
            padding: 20px 15px;
            font-weight: 600;
        }
        .semester-table tbody tr {
            transition: all 0.3s ease;
        }
        .semester-table tbody tr:hover {
            background: linear-gradient(135deg, #e0f2fe, #b8e6fc);
            transform: scale(1.01);
        }
        
        /* 🔥 COURSES BUTTON */
        .courses-btn {
            background: linear-gradient(135deg, #f59e0b, #d97706);
            border: none;
            padding: 8px 16px;
            border-radius: 20px;
            color: white;
            font-weight: 600;
            transition: all 0.3s ease;
            text-decoration: none;
        }
        .courses-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 20px rgba(245,158,11,0.4);
            color: white;
        }
        
        /* 🔥 STATUS TOGGLE */
        .status-toggle {
            position: relative;
            width: 60px;
            height: 30px;
            background: #ddd;
            border-radius: 15px;
            cursor: pointer;
            transition: all 0.3s ease;
        }
        .status-toggle.active {
            background: linear-gradient(135deg, #10b981, #059669);
        }
        .status-toggle .slider {
            position: absolute;
            top: 3px;
            left: 3px;
            width: 24px;
            height: 24px;
            background: white;
            border-radius: 50%;
            transition: all 0.3s ease;
            box-shadow: 0 2px 5px rgba(0,0,0,0.2);
        }
        .status-toggle.active .slider {
            transform: translateX(30px);
        }
        
        /* 🔥 ALERT */
        .alert-custom {
            border: none;
            border-radius: 15px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.2);
            animation: slideInDown 0.5s ease;
        }
        
        @keyframes slideInDown {
            from { transform: translateY(-50px); opacity: 0; }
            to { transform: translateY(0); opacity: 1; }
        }
        
        @media (max-width: 768px) {
            .sidebar { margin-bottom: 20px; position: static; }
        }
    </style>
</head>
<body>
    <div class="main-container">
        <div class="container-fluid">
            <div class="row">
                <!-- 🔥 SAME PERFECT SIDEBAR - ADDED COURSES LINK -->
                <div class="col-md-3">
                    <div class="sidebar">
                        <div class="card">
                            <div class="card-header">
                                <h5><?php echo htmlspecialchars($_SESSION['name']); ?></h5>
                                <span class="hod-badge">👑 HOD PANEL</span>
                            </div>
                            <div class="list-group list-group-flush">
                                <a href="dashboard.php" class="list-group-item">
                                    <i class="fas fa-tachometer-alt"></i> 📊 Dashboard
                                </a>
                                <a href="attendance_requests.php" class="list-group-item">
                                    <i class="fas fa-file-invoice"></i> 📝 Requests
                                </a>
                                <a href="semester_settings.php" class="list-group-item active">
                                    <i class="fas fa-calendar-alt"></i> 📚 Semester Settings
                                </a>
                                <a href="courses.php" class="list-group-item">
                                    <i class="fas fa-book"></i> 📖 Courses
                                </a>
                                <a href="../auth/logout.php" class="list-group-item">
                                    <i class="fas fa-sign-out-alt"></i> 🚪 Logout
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- 🔥 MAIN CONTENT -->
                <div class="col-md-9">
                    <?php if($message): ?>
                    <div class="alert alert-success alert-custom alert-dismissible fade show mb-4">
                        <i class="fas fa-check-circle me-2"></i><?php echo htmlspecialchars($message); ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                    <?php endif; ?>

                    <!-- 🔥 ADD NEW SEMESTER FORM -->
                    <div class="card form-card mb-4">
                        <div class="card-header">
                            <h3><i class="fas fa-plus-circle me-2"></i>➕ Add New Semester</h3>
                        </div>
                        <div class="card-body">
                            <form method="POST">
                                <div class="row">
                                    <div class="col-md-4 mb-3">
                                        <label class="form-label fw-bold">Semester Name</label>
                                        <input type="text" name="semester_name" class="form-control" 
                                               placeholder="Ex: Even Semester 2026" required>
                                    </div>
                                    <div class="col-md-4 mb-3">
                                        <label class="form-label fw-bold">Start Date</label>
                                        <input type="date" name="start_date" class="form-control" required>
                                    </div>
                                    <div class="col-md-4 mb-3">
                                        <label class="form-label fw-bold">End Date</label>
                                        <input type="date" name="end_date" class="form-control" required>
                                    </div>
                                </div>
                                <div class="form-check mb-4">
                                    <input type="checkbox" name="is_active" class="form-check-input" id="active">
                                    <label class="form-check-label fw-bold" for="active">
                                        ✅ Set as Active Semester
                                    </label>
                                </div>
                                <button type="submit" name="submit" class="btn btn-primary btn-lg px-5">
                                    <i class="fas fa-save me-2"></i>Save Semester
                                </button>
                            </form>
                        </div>
                    </div>

                    <!-- 🔥 ALL SEMESTERS TABLE - COURSES LINKS ADDED -->
                    <div class="card form-card">
                        <div class="card-header d-flex justify-content-between align-items-center">
                            <h3><i class="fas fa-list me-2"></i>📋 All Semesters (<?php echo count($semesters); ?>)</h3>
                            <a href="courses.php" class="btn btn-warning">
                                <i class="fas fa-book me-2"></i>📖 View All Courses
                            </a>
                        </div>
                        <div class="card-body p-0">
                            <?php if(!empty($semesters)): ?>
                            <div class="table-responsive">
                                <table class="table table-hover semester-table mb-0">
                                    <thead>
                                        <tr>
                                            <th>ID</th>
                                            <th>Semester Name</th>
                                            <th>Period</th>
                                            <th>Status</th>
                                            <th>Courses</th>
                                            <th>Created By</th>
                                            <th>Action</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach($semesters as $sem): ?>
                                        <tr>
                                            <td><strong>#<?php echo $sem['id']; ?></strong></td>
                                            <td>
                                                <strong><?php echo htmlspecialchars($sem['semester_name']); ?></strong>
                                            </td>
                                            <td>
                                                <span class="badge bg-info fs-6 px-3 py-2">
                                                    <?php echo date('d-M-Y', strtotime($sem['start_date'])); ?> 
                                                    → <?php echo date('d-M-Y', strtotime($sem['end_date'])); ?>
                                                </span>
                                            </td>
                                            <td>
                                                <form method="POST" class="d-inline">
                                                    <input type="hidden" name="semester_id" value="<?php echo $sem['id']; ?>">
                                                    <label class="status-toggle <?php echo $sem['is_active'] ? 'active' : ''; ?>" 
                                                           data-bs-toggle="tooltip" title="Click to change">
                                                        <span class="slider"></span>
                                                        <input type="submit" name="toggle_active" style="display:none;">
                                                    </label>
                                                </form>
                                                <small class="text-muted d-block"><?php echo $sem['is_active'] ? '🟢 Active' : '🔴 Inactive'; ?></small>
                                            </td>
                                            <td>
                                                <?php 
                                                // 🔥 GET COURSE COUNT FOR THIS SEMESTER
                                                $course_count = $pdo->prepare("SELECT COUNT(*) FROM courses WHERE semester_id = ?");
                                                $course_count->execute([$sem['id']]);
                                                $count = $course_count->fetchColumn();
                                                ?>
                                                <a href="courses.php?semester=<?php echo $sem['id']; ?>" 
                                                   class="courses-btn btn btn-sm" title="View <?php echo $count; ?> courses">
                                                    <i class="fas fa-book me-1"></i><?php echo $count; ?>
                                                </a>
                                            </td>
                                            <td><?php echo htmlspecialchars($sem['created_by']); ?></td>
                                            <td>
                                                <span class="badge bg-success"><?php echo date('M Y', strtotime($sem['end_date'])); ?></span>
                                            </td>
                                        </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                            <?php else: ?>
                            <div class="text-center py-5">
                                <i class="fas fa-calendar-times fa-4x text-muted mb-4"></i>
                                <h4>No Semesters Found</h4>
                                <p class="text-muted">Add your first semester using the form above</p>
                                <div class="mt-4">
                                    <a href="courses.php" class="btn btn-outline-warning">
                                        <i class="fas fa-book me-2"></i>📖 Go to Courses
                                    </a>
                                </div>
                            </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // 🔥 Toggle tooltip
        var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
        var tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
            return new bootstrap.Tooltip(tooltipTriggerEl);
        });
    </script>
</body>
</html>
