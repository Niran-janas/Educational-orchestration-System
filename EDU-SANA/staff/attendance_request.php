

<?php 
session_start(); 
if(!isset($_SESSION['role']) || $_SESSION['role'] != 'staff') {
    header("Location: ../index.php"); 
    exit();
}
include '../config/db_connection.php'; 

// 🔥 COLLATION BULLETPROOF FIX - 100% WORKING
$pdo->exec("SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci");
$pdo->exec("SET collation_connection = utf8mb4_unicode_ci");
$pdo->exec("SET SESSION character_set_client = utf8mb4");
$pdo->exec("SET SESSION character_set_results = utf8mb4");
$pdo->exec("SET SESSION character_set_connection = utf8mb4");

$message = '';
$records = array();
$classes = $pdo->query("SELECT DISTINCT class COLLATE utf8mb4_unicode_ci FROM students ORDER BY class")->fetchAll(PDO::FETCH_COLUMN);
$subjects = $pdo->query("SELECT DISTINCT subject COLLATE utf8mb4_unicode_ci FROM attendance ORDER BY subject")->fetchAll(PDO::FETCH_COLUMN);

// 🔥 FIXED: PHP 7.0+ COMPATIBLE (No ?? operator)
$selected_class = isset($_GET['class']) ? $_GET['class'] : '';
$selected_subject = isset($_GET['subject']) ? $_GET['subject'] : '';
$selected_date = isset($_GET['date']) ? $_GET['date'] : '';

// 🔥 COLLATION-PROOF MAIN QUERY
if($selected_class && $selected_subject && $selected_date) {
    $stmt = $pdo->prepare("
        SELECT a.*, s.name, s.roll_no 
        FROM attendance a 
        LEFT JOIN students s ON BINARY a.student_roll = BINARY s.roll_no
        WHERE BINARY a.class = ? 
        AND BINARY a.subject = ? 
        AND a.date = ?
        ORDER BY s.name, a.student_roll
    ");
    $stmt->execute(array($selected_class, $selected_subject, $selected_date));
    $records = $stmt->fetchAll();
}

// 🔥 SEND REQUESTS TO HOD
if(isset($_POST['send_correction']) && isset($_POST['corrections'])) {
    $sent_count = 0;
    foreach($_POST['corrections'] as $roll_no => $data) {
        if(isset($data['request_correction']) && $data['request_correction'] == '1') {
            $check = $pdo->prepare("SELECT id FROM attendance_requests WHERE BINARY student_roll = ? AND `date` = ? AND DATE(request_date) = CURDATE()");
            $check->execute(array($roll_no, $data['date']));
            
            if(!$check->rowCount()) {
                $stmt = $pdo->prepare("
                    INSERT INTO attendance_requests 
                    (student_roll, class, subject, `date`, reason, status, request_date) 
                    VALUES (?, ?, ?, ?, ?, 'pending_hod', NOW())
                ");
                $stmt->execute(array(
                    $roll_no, $data['class'], $data['subject'], $data['date'],
                    $data['original_status'] . " → " . $data['requested_status']
                ));
                $sent_count++;
            }
        }
    }
    $message = $sent_count > 0 ? "✅ $sent_count requests sent to HOD!" : "⚠️ No new requests";
}

// 🔥 HOD DECISIONS
$stmt_hod = $pdo->query("
    SELECT ar.*, s.name 
    FROM attendance_requests ar 
    LEFT JOIN students s ON BINARY ar.student_roll = BINARY s.roll_no
    WHERE ar.status IN ('approved', 'rejected') AND ar.request_date > DATE_SUB(NOW(), INTERVAL 7 DAY)
    ORDER BY ar.request_date DESC
");
$hod_decisions = $stmt_hod->fetchAll();

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <title>Attendance Correction Requests</title>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css" rel="stylesheet">
    <style>
        body { background: linear-gradient(135deg, #f5f7fa 0%, #c3cfe2 100%); }
        .sidebar { background: linear-gradient(180deg, #7ecef9 0%, #764ba2 100%); color: white; }
        .table-hover tbody tr:hover { background-color: rgba(246, 248, 250, 0.07); }
        .status-present { background: #d4edda !important; color: #155724 !important; }
        .status-absent { background: #f8d7da !important; color: #721c24 !important; }
        .status-od { background: #fff3cd !important; color: #856404 !important; }
        .card { border: none; box-shadow: 0 10px 30px rgba(0,0,0,0.1); border-radius: 15px; }
        @media (max-width: 768px) { .sidebar { position: relative !important; margin-bottom: 20px; } }
    </style>
</head>
<body>
    <?php include '../includes/header.php'; ?>
    
    <div class="container-fluid mt-4">
        <div class="row">
            <!-- SIDEBAR -->
            <div class="col-md-3">
                <div class="card sidebar p-3 shadow-lg" style="border-radius: 20px;">
                    <div class="text-center mb-4">
                        <h5 class="fw-bold mb-1"><?php echo htmlspecialchars($_SESSION['name']); ?></h5>
                        <small class="opacity-75">Staff Panel</small>
                    </div>
                    <div class="list-group list-group-flush">
                        <a href="dashboard.php" class="list-group-item list-group-item-action bg-transparent text-white border-0">
                            <i class="fas fa-tachometer-alt me-2"></i>📊 Dashboard
                        </a>
                        
                        <a href="../auth/logout.php" class="list-group-item list-group-item-action bg-transparent text-white border-0 mt-3">
                            <i class="fas fa-sign-out-alt me-2"></i>🚪 Logout
                        </a>
                    </div>
                </div>
            </div>
            
            <!-- MAIN CONTENT -->
            <div class="col-md-9">
                <?php if($message): ?>
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    <i class="fas fa-check-circle me-2"></i><?php echo htmlspecialchars($message); ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
                <?php endif; ?>

                <!-- FILTER FORM -->
                <div class="card mb-4">
                    <div class="card-header bg-primary text-white rounded-top">
                        <h5 class="mb-0"><i class="fas fa-filter me-2"></i>🔍 Filter Attendance Records</h5>
                    </div>
                    <div class="card-body">
                        <form method="GET" class="row g-3">
                            <div class="col-md-4">
                                <label class="form-label fw-bold">Class</label>
                                <select name="class" class="form-select" required>
                                    <option value="">Select Class</option>
                                    <?php foreach($classes as $class): ?>
                                    <option <?php echo $selected_class==$class?'selected':''; ?>><?php echo htmlspecialchars($class); ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label fw-bold">Subject</label>
                                <select name="subject" class="form-select" required>
                                    <option value="">Select Subject</option>
                                    <?php foreach($subjects as $subject): ?>
                                    <option <?php echo $selected_subject==$subject?'selected':''; ?>><?php echo htmlspecialchars($subject); ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label fw-bold">Date</label>
                                <input type="date" name="date" class="form-control" value="<?php echo htmlspecialchars($selected_date); ?>" required>
                            </div>
                            <div class="col-12">
                                <button type="submit" class="btn btn-primary btn-lg px-4">
                                    <i class="fas fa-search me-2"></i>🔍 Filter Records
                                </button>
                                <a href="attendance_requests.php" class="btn btn-secondary btn-lg px-4 ms-2">
                                    <i class="fas fa-times me-2"></i>Clear Filter
                                </a>
                            </div>
                        </form>
                    </div>
                </div>

                <!-- RECORDS TABLE -->
                <?php if(!empty($records)): ?>
                <form method="POST">
                <div class="card">
                    <div class="card-header bg-success text-white rounded-top">
                        <h5 class="mb-0">
                            <i class="fas fa-list me-2"></i>
                            <?php echo count($records); ?> Records Found 
                            <span class="badge bg-light text-success fs-6"><?php echo htmlspecialchars($selected_class); ?></span>
                            <span class="badge bg-light text-dark fs-6"><?php echo htmlspecialchars($selected_subject); ?></span>
                            <span class="badge bg-light text-primary fs-6"><?php echo date('d M Y', strtotime($selected_date)); ?></span>
                        </h5>
                    </div>
                    <div class="table-responsive">
                        <table class="table table-hover mb-0">
                            <thead class="table-dark">
                                <tr>
                                    <th width="50"><input type="checkbox" id="select_all" class="form-check-input"></th>
                                    <th>Student Name</th>
                                    <th>Roll No</th>
                                    <th>Current Status</th>
                                    <th>New Status</th>
                                    <th>Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach($records as $record): ?>
                                <tr>
                                    <td>
                                        <input type="checkbox" 
                                               name="corrections[<?php echo htmlspecialchars($record['student_roll']); ?>][request_correction]" 
                                               value="1" class="form-check-input">
                                        <input type="hidden" 
                                               name="corrections[<?php echo htmlspecialchars($record['student_roll']); ?>][class]" 
                                               value="<?php echo htmlspecialchars($selected_class); ?>">
                                        <input type="hidden" 
                                               name="corrections[<?php echo htmlspecialchars($record['student_roll']); ?>][subject]" 
                                               value="<?php echo htmlspecialchars($selected_subject); ?>">
                                        <input type="hidden" 
                                               name="corrections[<?php echo htmlspecialchars($record['student_roll']); ?>][date]" 
                                               value="<?php echo htmlspecialchars($record['date']); ?>">
                                        <input type="hidden" 
                                               name="corrections[<?php echo htmlspecialchars($record['student_roll']); ?>][original_status]" 
                                               value="<?php echo htmlspecialchars($record['status']); ?>">
                                    </td>
                                    <td>
                                        <strong><?php echo htmlspecialchars(isset($record['name']) ? $record['name'] : 'N/A'); ?></strong>
                                    </td>
                                    <td><code><?php echo htmlspecialchars($record['student_roll']); ?></code></td>
                                    <td>
                                        <span class="badge fs-6 px-3 py-2 fw-bold 
                                            <?php 
                                            echo $record['status']=='Present' ? 'bg-success status-present' : 
                                                 ($record['status']=='OD' ? 'bg-warning status-od' : 'bg-danger status-absent'); 
                                            ?>">
                                            <?php echo htmlspecialchars($record['status']); ?>
                                        </span>
                                    </td>
                                    <td>
                                        <select name="corrections[<?php echo htmlspecialchars($record['student_roll']); ?>][requested_status]" 
                                                class="form-select form-select-sm">
                                            <option value="Present" <?php echo ($record['status']=='Present')?'selected':'';?>>Present</option>
                                            <option value="Absent" <?php echo ($record['status']=='Absent')?'selected':'';?>>Absent</option>
                                            <option value="OD">OD</option>
                                        </select>
                                    </td>
                                    <td>
                                        <?php 
                                        $change = ($record['status'] != 'Present') ? 'Mark Present' : 'Mark Absent/OD';
                                        echo "<small class='text-muted'>{$change}</small>";
                                        ?>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                    <div class="card-footer bg-light">
                        <button type="button" id="select_all_btn" class="btn btn-success me-2">
                            <i class="fas fa-check-double me-1"></i>Select All
                        </button>
                        <button type="button" id="deselect_all_btn" class="btn btn-secondary me-2">
                            <i class="fas fa-times me-1"></i>Deselect All
                        </button>
                        <div class="float-end">
                            <button type="submit" name="send_correction" class="btn btn-danger btn-lg px-4">
                                <i class="fas fa-paper-plane me-2"></i>🚀 SEND TO HOD (<?php echo count($records); ?>)
                            </button>
                        </div>
                    </div>
                </div>
                </form>
                <?php else: ?>
                <div class="text-center py-5">
                    <i class="fas fa-search fa-3x text-muted mb-4"></i>
                    <h4 class="text-muted">No records found</h4>
                    <p class="text-muted">Please use the filter above to find attendance records</p>
                </div>
                <?php endif; ?>

                <!-- HOD DECISIONS -->
                <?php if(!empty($hod_decisions)): ?>
                <div class="card mt-4">
                    <div class="card-header bg-info text-white rounded-top">
                        <h5 class="mb-0">
                            <i class="fas fa-clipboard-check me-2"></i>
                            HOD Decisions (Last 7 Days) - <?php echo count($hod_decisions); ?> Updates
                        </h5>
                    </div>
                    <div class="table-responsive">
                        <table class="table table-hover mb-0">
                            <thead class="table-info">
                                <tr>
                                    <th>Student</th>
                                    <th>Request</th>
                                    <th>Date</th>
                                    <th>Status</th>
                                    <th>Decision Time</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach($hod_decisions as $decision): ?>
                                <tr>
                                    <td>
                                        <strong><?php echo htmlspecialchars($decision['student_roll']); ?></strong>
                                        <?php if($decision['name']): ?>
                                        <br><small class="text-muted"><?php echo htmlspecialchars($decision['name']); ?></small>
                                        <?php endif; ?>
                                    </td>
                                    <td><?php echo htmlspecialchars($decision['reason']); ?></td>
                                    <td><strong><?php echo date('d-m-Y', strtotime($decision['date'])); ?></strong></td>
                                    <td>
                                        <span class="badge fs-6 px-3 py-2 fw-bold 
                                            <?php echo $decision['status']=='approved' ? 'bg-success' : 'bg-danger'; ?>">
                                            <i class="fas <?php echo $decision['status']=='approved' ? 'fa-check-circle' : 'fa-times-circle'; ?> me-1"></i>
                                            <?php echo ucfirst($decision['status']); ?>
                                        </span>
                                    </td>
                                    <td><small><?php echo date('H:i', strtotime($decision['request_date'])); ?></small></td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Select All / Deselect All
        document.getElementById('select_all').addEventListener('change', function() {
            document.querySelectorAll('input[name*="request_correction"]').forEach(cb => {
                cb.checked = this.checked;
            });
        });

        document.getElementById('select_all_btn').addEventListener('click', function() {
            document.getElementById('select_all').checked = true;
            document.querySelectorAll('input[name*="request_correction"]').forEach(cb => {
                cb.checked = true;
            });
        });

        document.getElementById('deselect_all_btn').addEventListener('click', function() {
            document.getElementById('select_all').checked = false;
            document.querySelectorAll('input[name*="request_correction"]').forEach(cb => {
                cb.checked = false;
            });
        });
    </script>
</body>
</html>
