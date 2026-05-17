<?php 
session_start(); 
if(!isset($_SESSION['role']) || $_SESSION['role'] != 'hod') {
    header("Location: ../public/index.php"); exit();
}
include '../config/db_connection.php'; 

// 🔥 SET UTF8MB4 FOR EMOJI SUPPORT (WAMP SAFE)
$pdo->exec("SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci");

// 🔥 FIX: Initialize $message FIRST
$message = '';

// 🔥 HOD APPROVE/REJECT - PHP 5.3 COMPATIBLE
if(isset($_POST['action'])) {
    $request_id = $_POST['request_id'];
    $action = $_POST['action'];
    $stmt = $pdo->prepare("UPDATE attendance_requests SET status = ? WHERE id = ?");
    $stmt->execute(array($action, $request_id));
    $message = $action == 'approved' ? '✅ Approved!' : '❌ Rejected!';
}

// 🔥 BULLETPROOF QUERY - Finds ALL possible status variations
$requests = array();
try {
    $stmt = $pdo->query("
        SELECT * FROM attendance_requests 
        WHERE status = 'pending_hod' COLLATE utf8mb4_unicode_ci
         OR status = 'pendinghod' 
         OR status LIKE '%pending%' COLLATE utf8mb4_unicode_ci
         OR status IS NULL 
         OR status = ''
        ORDER BY request_date DESC
    ");
    $requests = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch(Exception $e) {
    $requests = array();
}

// 🔥 DEBUG INFO - PHP 5.3 SAFE
$total = 0; 
$pending = 0;
try {
    $result = $pdo->query("SELECT COUNT(*) FROM attendance_requests");
    $total = $result->fetchColumn();
    $result = $pdo->query("SELECT COUNT(*) FROM attendance_requests WHERE status = 'pending_hod'");
    $pending = $result->fetchColumn();
} catch(Exception $e) {
    $total = 0;
    $pending = 0;
}
?>

<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>HOD Attendance Requests</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css" rel="stylesheet">
    
    <style>
        /* 🔥 HOD DASHBOARD - PROFESSIONAL DESIGN */
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        
        .main-container {
            min-height: 100vh;
            padding: 20px 0;
        }
        
        /* 🔥 FIXED SIDEBAR - WHITE TEXT VISIBLE */
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
            border: none;
        }
        
        .sidebar h5 {
            color: white !important;
            font-weight: 800;
            font-size: 1.5rem;
            margin-bottom: 8px;
            text-shadow: 0 1px 3px rgba(0,0,0,0.5);
        }
        
        .hod-badge {
            background: rgba(255,255,255,0.95) !important;
            color: #1e40af !important;
            font-weight: 800 !important;
            padding: 8px 20px;
            border-radius: 25px;
            font-size: 0.9rem;
            box-shadow: 0 4px 12px rgba(0,0,0,0.2);
        }
        
        /* 🔥 PERFECT LIST ITEMS - WHITE + CURSOR */
        .list-group-item {
            border: none !important;
            padding: 18px 25px !important;
            transition: all 0.3s ease !important;
            border-radius: 0 !important;
            color: #e5e7eb !important;
            cursor: pointer !important;
            font-weight: 500;
            position: relative;
            overflow: hidden;
        }
        
        .list-group-item::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255,255,255,0.1), transparent);
            transition: left 0.5s;
        }
        
        .list-group-item:hover::before {
            left: 100%;
        }
        
        .list-group-item:hover {
            background: rgba(255,255,255,0.15) !important;
            color: white !important;
            transform: translateX(8px);
            box-shadow: inset 0 0 20px rgba(255,255,255,0.1);
        }
        
        .list-group-item.active {
            background: linear-gradient(135deg, #10b981, #059669) !important;
            color: white !important;
            box-shadow: 0 8px 25px rgba(16,185,129,0.4);
            border-left: 5px solid #fff;
        }
        
        .list-group-item.active:hover {
            transform: translateX(8px);
        }
        
        /* 🔥 ICONS WHITE */
        .list-group-item i {
            color: #e2e8f0 !important;
            margin-right: 12px;
            font-size: 1.1rem;
        }
        
        .list-group-item:hover i,
        .list-group-item.active i {
            color: white !important;
        }
        
        /* 🔥 LOGOUT RED */
        .list-group-item[href*="logout"] {
            background: linear-gradient(135deg, #ef4444, #dc2626);
            margin-top: 10px;
            border-radius: 0 0 20px 20px !important;
        }
        
        .list-group-item[href*="logout"]:hover {
            background: linear-gradient(135deg, #dc2626, #b91c1c) !important;
            color: white !important;
        }
        
        /* 🔥 ALERTS */
        .alert-custom {
            border: none;
            border-radius: 15px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.2);
            animation: slideInDown 0.5s ease;
        }
        
        /* 🔥 DEBUG BOX */
        .debug-box {
            background: linear-gradient(135deg, #74b9ff, #0984e3);
            border-radius: 15px;
            box-shadow: 0 10px 30px rgba(116,185,255,0.3);
            border: none;
        }
        
        .debug-box .card-header {
            background: rgba(255,255,255,0.2);
            backdrop-filter: blur(10px);
            border-radius: 15px 15px 0 0;
        }
        
        .debug-number {
            font-size: 2rem;
            font-weight: bold;
            text-shadow: 0 2px 10px rgba(0,0,0,0.3);
        }
        
        /* 🔥 MAIN CARD */
        .requests-card {
            border-radius: 25px;
            overflow: hidden;
            box-shadow: 0 25px 50px rgba(0,0,0,0.25);
            backdrop-filter: blur(10px);
            background: rgba(255,255,255,0.95);
            border: 1px solid rgba(255,255,255,0.2);
        }
        
        .requests-card .card-header {
            background: linear-gradient(135deg, #fdcb6e, #e17055);
            color: white;
            padding: 25px;
            border-radius: 25px 25px 0 0;
            text-align: center;
        }
        
        .requests-card h3 {
            font-size: 2rem;
            font-weight: 700;
            margin: 0;
            text-shadow: 0 2px 10px rgba(0,0,0,0.3);
        }
        
        .count-badge {
            background: linear-gradient(135deg, #00b894, #00cec9);
            color: white;
            font-size: 1.2rem;
            padding: 10px 20px;
            border-radius: 50px;
            font-weight: bold;
            box-shadow: 0 5px 15px rgba(0,184,148,0.4);
        }
        
        /* 🔥 TABLE STYLES */
        .requests-table {
            margin-bottom: 0;
        }
        
        .requests-table thead th {
            background: linear-gradient(135deg, #2d3436, #636e72);
            color: white;
            border: none;
            padding: 20px 15px;
            font-weight: 600;
            text-transform: uppercase;
            font-size: 0.9rem;
            letter-spacing: 1px;
        }
        
        .requests-table tbody tr {
            transition: all 0.3s ease;
        }
        
        .requests-table tbody tr:hover {
            background: linear-gradient(135deg, #ffeaa7, #fab1a0);
            transform: scale(1.01);
            box-shadow: 0 10px 25px rgba(0,0,0,0.15);
        }
        
        .requests-table td {
            padding: 20px 15px;
            vertical-align: middle;
            border-color: rgba(0,0,0,0.1);
        }
        
        /* 🔥 REQUEST BADGES */
        .request-old {
            background: linear-gradient(135deg, #d63031, #ff7675);
            color: white;
            padding: 8px 15px;
            border-radius: 20px;
            font-weight: 600;
        }
        
        .request-new {
            background: linear-gradient(135deg, #00b894, #00cec9);
            color: white;
            padding: 8px 15px;
            border-radius: 20px;
            font-weight: 600;
        }
        
        .status-badge {
            background: linear-gradient(135deg, #fdcb6e, #e17055);
            color: #333;
            padding: 8px 15px;
            border-radius: 20px;
            font-weight: 600;
            font-size: 0.85rem;
        }
        
        /* 🔥 ACTION BUTTONS */
        .action-btn {
            border-radius: 25px;
            padding: 12px 20px;
            font-weight: 700;
            font-size: 1rem;
            border: none;
            transition: all 0.3s ease;
            box-shadow: 0 5px 15px rgba(0,0,0,0.2);
        }
        
        .btn-approve {
            background: linear-gradient(135deg, #00b894, #00cec9);
            color: white;
        }
        
        .btn-approve:hover {
            transform: translateY(-3px);
            box-shadow: 0 10px 25px rgba(0,184,148,0.4);
        }
        
        .btn-reject {
            background: linear-gradient(135deg, #d63031, #ff7675);
            color: white;
        }
        
        .btn-reject:hover {
            transform: translateY(-3px);
            box-shadow: 0 10px 25px rgba(214,48,49,0.4);
        }
        
        /* 🔥 EMPTY STATE */
        .empty-state {
            padding: 60px 20px;
            text-align: center;
            color: #636e72;
        }
        
        .empty-icon {
            font-size: 6rem;
            opacity: 0.5;
            margin-bottom: 20px;
            animation: bounce 2s infinite;
        }
        
        /* 🔥 ANIMATIONS */
        @keyframes slideInDown {
            from { transform: translateY(-50px); opacity: 0; }
            to { transform: translateY(0); opacity: 1; }
        }
        
        @keyframes bounce {
            0%, 20%, 50%, 80%, 100% { transform: translateY(0); }
            40% { transform: translateY(-10px); }
            60% { transform: translateY(-5px); }
        }
        
        /* 🔥 RESPONSIVE */
        @media (max-width: 768px) {
            .sidebar {
                margin-bottom: 20px;
                position: static;
            }
            
            .requests-card h3 {
                font-size: 1.5rem;
            }
            
            .action-btn {
                padding: 10px 15px;
                font-size: 0.9rem;
                margin-bottom: 5px;
                width: 100%;
            }
        }
    </style>
</head>
<body>
    <div class="main-container">
        <div class="container-fluid">
            <div class="row">
                <!-- 🔥 PERFECT HOD SIDEBAR - WHITE TEXT VISIBLE -->
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
                                <a href="attendance_requests.php" class="list-group-item active">
                                    <i class="fas fa-file-invoice"></i> 📝 Requests (<?php echo count($requests); ?>)
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
                    <?php if(isset($message) && !empty($message)): ?>
                    <div class="alert alert-success alert-custom alert-dismissible fade show mb-4">
                        <i class="fas fa-check-circle me-2"></i><?php echo htmlspecialchars($message); ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                    <?php endif; ?>

                    <!-- 🔥 DEBUG INFO -->
                    <div class="card debug-box mb-4">
                        <div class="card-header text-white">
                            <h5><i class="fas fa-bug me-2"></i>🔍 Debug Information</h5>
                        </div>
                        <div class="card-body">
                            <div class="row text-center text-white">
                                <div class="col-md-4">
                                    <div class="debug-number text-info"><?php echo $total; ?></div>
                                    <small>Total Requests</small>
                                </div>
                                <div class="col-md-4">
                                    <div class="debug-number text-warning"><?php echo $pending; ?></div>
                                    <small>Exact 'pending_hod'</small>
                                </div>
                                <div class="col-md-4">
                                    <div class="debug-number text-success"><?php echo count($requests); ?></div>
                                    <small>Showing Now</small>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- 🔥 REQUESTS TABLE -->
                    <div class="card requests-card">
                        <div class="card-header">
                            <h3>
                                <i class="fas fa-clipboard-list me-2"></i>
                                📋 Pending Attendance Requests
                                <span class="count-badge ms-3"><?php echo count($requests); ?></span>
                            </h3>
                        </div>
                        
                        <?php if(!empty($requests)): ?>
                        <div class="table-responsive">
                            <table class="table requests-table mb-0">
                                <thead>
                                    <tr>
                                        <th><i class="fas fa-hashtag"></i> ID</th>
                                        <th><i class="fas fa-user"></i> Student</th>
                                        <th><i class="fas fa-graduation-cap"></i> Class</th>
                                        <th><i class="fas fa-book"></i> Subject</th>
                                        <th><i class="fas fa-calendar"></i> Date</th>
                                        <th><i class="fas fa-exchange-alt"></i> Request</th>
                                        <th><i class="fas fa-info-circle"></i> Status</th>
                                        <th><i class="fas fa-cogs"></i> Action</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach($requests as $req): ?>
                                    <tr>
                                        <td>
                                            <strong class="text-primary">#<?php echo $req['id']; ?></strong>
                                        </td>
                                        <td>
                                            <strong><?php echo htmlspecialchars($req['student_roll']); ?></strong>
                                        </td>
                                        <td>
                                            <span class="badge bg-primary px-3 py-2"><?php echo htmlspecialchars($req['class']); ?></span>
                                        </td>
                                        <td><?php echo htmlspecialchars(substr($req['subject'], 0, 25)); ?></td>
                                        <td>
                                            <span class="badge bg-info"><?php echo date('d-m-Y', strtotime($req['date'])); ?></span>
                                        </td>
                                        <td>
                                            <?php 
                                            $reason = $req['reason'];
                                            if(strpos($reason, '→') !== false) {
                                                $parts = explode('→', $reason);
                                                echo '<span class="request-old me-1">' . trim($parts[0]) . '</span>';
                                                $new_reason = isset($parts[1]) ? trim($parts[1]) : '';
                                                echo '<span class="request-new">' . htmlspecialchars($new_reason) . '</span>';
                                            } else {
                                                echo '<span class="badge bg-secondary">' . htmlspecialchars($reason) . '</span>';
                                            }
                                            ?>
                                        </td>
                                        <td>
                                            <span class="status-badge">
                                                <?php echo htmlspecialchars($req['status'] ? $req['status'] : 'NULL/Empty'); ?>
                                            </span>
                                        </td>
                                        <td>
                                            <form method="POST" class="d-inline">
                                                <input type="hidden" name="request_id" value="<?php echo $req['id']; ?>">
                                                <button name="action" value="approved" class="action-btn btn-approve me-2">
                                                    <i class="fas fa-check me-1"></i>Approve
                                                </button>
                                                <button name="action" value="rejected" class="action-btn btn-reject">
                                                    <i class="fas fa-times me-1"></i>Reject
                                                </button>
                                            </form>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                        <?php else: ?>
                        <div class="empty-state">
                            <div class="empty-icon">
                                <i class="fas fa-inbox"></i>
                            </div>
                            <h4>📭 No Pending Requests</h4>
                            <p class="lead">Staff members haven't sent any attendance correction requests yet.</p>
                            <small class="opacity-75">Requests will appear here when staff submits corrections.</small>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
