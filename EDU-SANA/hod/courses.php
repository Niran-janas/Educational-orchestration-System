<?php 
session_start(); 
if(!isset($_SESSION['role']) || $_SESSION['role'] != 'hod') {
    header("Location: ../public/index.php"); exit();
}
include '../config/db_connection.php'; 

// 🔥 NEW SUBJECT FORM PROCESSING
$message = '';
if(isset($_POST['submit_subject'])) {
    $subject_code = strtoupper($_POST['subject_code']);
    $subject_name = $_POST['subject_name'];
    $semester_type = $_POST['semester_type']; // odd/even
    $year = $_POST['year']; // 1,2,3
    
    $stmt = $pdo->prepare("INSERT INTO subjects (subject_code, subject_name, semester_type, year, created_by) VALUES (?, ?, ?, ?, ?)");
    if($stmt->execute([$subject_code, $subject_name, $semester_type, $year, $_SESSION['name']])) {
        $message = '✅ Subject added successfully!';
    } else {
        $message = '❌ Error adding subject!';
    }
}

// 🔥 DELETE SUBJECT
if(isset($_GET['delete'])) {
    $subject_id = $_GET['delete'];
    $stmt = $pdo->prepare("DELETE FROM subjects WHERE id = ?");
    $stmt->execute([$subject_id]);
    $message = '✅ Subject deleted!';
}

// 🔥 GET ALL SUBJECTS WITH FILTERS
$semester_filter = $_POST['semester_filter'] ?? $_GET['semester'] ?? '';
$year_filter = $_POST['year_filter'] ?? $_GET['year'] ?? '';

$where_conditions = [];
$params = [];

if($semester_filter) {
    $where_conditions[] = "semester_type = ?";
    $params[] = $semester_filter;
}
if($year_filter) {
    $where_conditions[] = "year = ?";
    $params[] = $year_filter;
}

$where_clause = !empty($where_conditions) ? 'WHERE ' . implode(' AND ', $where_conditions) : '';

$subjects = $pdo->query("
    SELECT * FROM subjects 
    $where_clause
    ORDER BY 
        CASE semester_type WHEN 'odd' THEN 1 ELSE 2 END,
        year ASC,
        subject_code ASC
")->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html>
<head>
    <title>HOD Subject Management</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css" rel="stylesheet">
    
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        .main-container { min-height: 100vh; padding: 20px 0; }
        
        /* 🔥 SAME PERFECT SIDEBAR */
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
        
        /* 🔥 FORM & TABLE */
        .form-card {
            border-radius: 25px;
            box-shadow: 0 25px 50px rgba(0,0,0,0.25);
            backdrop-filter: blur(10px);
            background: rgba(255,255,255,0.95);
        }
        .form-card .card-header {
            background: linear-gradient(135deg, #f59e0b, #d97706);
            color: white;
            padding: 25px;
            border-radius: 25px 25px 0 0;
            text-align: center;
        }
        .subject-table thead th {
            background: linear-gradient(135deg, #2d3436, #636e72);
            color: white;
            border: none;
            padding: 20px 15px;
            font-weight: 600;
        }
        .subject-table tbody tr:hover {
            background: linear-gradient(135deg, #fef3c7, #fde68a);
            transform: scale(1.01);
        }
        .subject-code { font-size: 1.2rem; font-weight: 800; color: #1e40af; }
        .year-badge { font-size: 0.9rem; padding: 6px 12px; }
        .btn-delete {
            background: linear-gradient(135deg, #ef4444, #dc2626);
            border: none;
            padding: 8px 15px;
            border-radius: 20px;
        }
        .filter-card {
            background: linear-gradient(135deg, #8b5cf6, #7c3aed);
            border-radius: 20px;
            padding: 20px;
            margin-bottom: 20px;
        }
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
    </style>
</head>
<body>
    <div class="main-container">
        <div class="container-fluid">
            <div class="row">
                <!-- 🔥 SIDEBAR -->
                <div class="col-md-3">
                    <div class="sidebar">
                        <div class="card">
                            <div class="card-header">
                                <h5><?php echo htmlspecialchars($_SESSION['name']); ?></h5>
                                <span class="hod-badge">👑 HOD PANEL</span>
                            </div>
                            <div class="list-group list-group-flush">
                                <a href="dashboard.php" class="list-group-item"><i class="fas fa-tachometer-alt"></i> 📊 Dashboard</a>
                                <a href="attendance_requests.php" class="list-group-item"><i class="fas fa-file-invoice"></i> 📝 Requests</a>
                                <a href="semester_settings.php" class="list-group-item"><i class="fas fa-calendar-alt"></i> 📚 Semesters</a>
                                <a href="courses.php" class="list-group-item active"><i class="fas fa-book"></i> 📖 Subjects</a>
                                <a href="../auth/logout.php" class="list-group-item"><i class="fas fa-sign-out-alt"></i> 🚪 Logout</a>
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

                    <!-- 🔥 ADD NEW SUBJECT FORM - 1st,2nd,3rd YEAR ONLY -->
                    <div class="card form-card mb-4">
                        <div class="card-header">
                            <h3><i class="fas fa-plus-circle me-2"></i>➕ Add New Subject</h3>
                        </div>
                        <div class="card-body">
                            <form method="POST">
                                <div class="row">
                                    <div class="col-md-3 mb-3">
                                        <label class="form-label fw-bold">Subject Code</label>
                                        <input type="text" name="subject_code" class="form-control" 
                                               placeholder="Ex: CS101" maxlength="10" required>
                                    </div>
                                    <div class="col-md-4 mb-3">
                                        <label class="form-label fw-bold">Subject Name</label>
                                        <input type="text" name="subject_name" class="form-control" 
                                               placeholder="Ex: Database Management" required>
                                    </div>
                                    <div class="col-md-2 mb-3">
                                        <label class="form-label fw-bold">Semester</label>
                                        <select name="semester_type" class="form-select" required>
                                            <option value="">Select</option>
                                            <option value="odd">📚 Odd Semester</option>
                                            <option value="even">📚 Even Semester</option>
                                        </select>
                                    </div>
                                    <div class="col-md-3 mb-3">
                                        <label class="form-label fw-bold">Year</label>
                                        <select name="year" class="form-select" required>
                                            <option value="">Select</option>
                                            <option value="1">1st Year</option>
                                            <option value="2">2nd Year</option>
                                            <option value="3">3rd Year</option>
                                        </select>
                                    </div>
                                </div>
                                <button type="submit" name="submit_subject" class="btn btn-warning btn-lg px-5">
                                    <i class="fas fa-save me-2"></i>Save Subject
                                </button>
                            </form>
                        </div>
                    </div>

                    <!-- 🔥 FILTER SUBJECTS -->
                    <div class="card filter-card text-white mb-4">
                        <div class="row">
                            <div class="col-md-5">
                                <h5><i class="fas fa-filter me-2"></i>Filter Subjects</h5>
                            </div>
                            <div class="col-md-7">
                                <form method="GET" class="row g-2">
                                    <div class="col-md-4">
                                        <select name="semester" class="form-select">
                                            <option value="">All Semesters</option>
                                            <option value="odd" <?php echo ($_GET['semester']??'')=='odd'?'selected':'';?>>Odd</option>
                                            <option value="even" <?php echo ($_GET['semester']??'')=='even'?'selected':'';?>>Even</option>
                                        </select>
                                    </div>
                                    <div class="col-md-4">
                                        <select name="year" class="form-select">
                                            <option value="">All Years</option>
                                            <option value="1" <?php echo ($_GET['year']??'')=='1'?'selected':'';?>>1st Year</option>
                                            <option value="2" <?php echo ($_GET['year']??'')=='2'?'selected':'';?>>2nd Year</option>
                                            <option value="3" <?php echo ($_GET['year']??'')=='3'?'selected':'';?>>3rd Year</option>
                                        </select>
                                    </div>
                                    <div class="col-md-4">
                                        <button type="submit" class="btn btn-light w-100">
                                            <i class="fas fa-search me-2"></i>Filter
                                        </button>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>

                    <!-- 🔥 ALL SUBJECTS TABLE -->
                    <div class="card form-card">
                        <div class="card-header">
                            <h3><i class="fas fa-list me-2"></i>📋 All Subjects (<?php echo count($subjects); ?>)</h3>
                        </div>
                        <div class="card-body p-0">
                            <?php if(!empty($subjects)): ?>
                            <div class="table-responsive">
                                <table class="table table-hover subject-table mb-0">
                                    <thead>
                                        <tr>
                                            <th>ID</th>
                                            <th>Subject Code</th>
                                            <th>Subject Name</th>
                                            <th>Semester</th>
                                            <th>Year</th>
                                            <th>Created By</th>
                                            <th>Action</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach($subjects as $subject): ?>
                                        <tr>
                                            <td><strong>#<?php echo $subject['id']; ?></strong></td>
                                            <td><span class="subject-code"><?php echo htmlspecialchars($subject['subject_code']); ?></span></td>
                                            <td><strong><?php echo htmlspecialchars($subject['subject_name']); ?></strong></td>
                                            <td>
                                                <span class="badge bg-<?php echo $subject['semester_type']=='odd'?'success':'warning'; ?>">
                                                    <?php echo ucfirst($subject['semester_type']); ?>
                                                </span>
                                            </td>
                                            <td>
                                                <span class="badge bg-primary year-badge">
                                                    <?php echo $subject['year']; ?><?php echo ['1'=>'st','2'=>'nd','3'=>'rd'][$subject['year']]; ?> Year
                                                </span>
                                            </td>
                                            <td><?php echo htmlspecialchars($subject['created_by']); ?></td>
                                            <td>
                                                <a href="?delete=<?php echo $subject['id']; ?>" 
                                                   class="btn btn-delete btn-sm text-white" 
                                                   onclick="return confirm('Delete this subject?')">
                                                    <i class="fas fa-trash"></i>
                                                </a>
                                            </td>
                                        </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                            <?php else: ?>
                            <div class="text-center py-5">
                                <i class="fas fa-book fa-4x text-muted mb-4"></i>
                                <h4>No Subjects Found</h4>
                                <p class="text-muted">Add your first subject using the form above</p>
                            </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
