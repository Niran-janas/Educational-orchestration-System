<?php 
session_start(); 
if(!isset($_SESSION['role']) || $_SESSION['role'] != 'hod') {
    header("Location: ../index.php"); exit();
}
include '../config/db_connection.php'; 

// 🔥 SET UTF8MB4 FOR EMOJI SUPPORT (WAMP SAFE)
$pdo->exec("SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci");

// 🔥 AUTO CLEANUP EXPIRED NOTIFICATIONS (CRITICAL)
try {
    $pdo->exec("UPDATE notifications SET is_active = 0 WHERE expires_at <= NOW()");
} catch(PDOException $e) {}

// 🔥 HANDLE UPLOAD
if($_POST && isset($_POST['title'])) {
    $title = trim($_POST['title']);
    $description = trim($_POST['description']);
    
    if($title && $description) {
        try {
            // 🔥 PROPER INSERT with ALL columns - PHP 5.3 COMPATIBLE
            $stmt = $pdo->prepare("
                INSERT INTO notifications (title, description, created_at, expires_at, is_active, created_by) 
                VALUES (?, ?, NOW(), DATE_ADD(NOW(), INTERVAL 15 DAY), 1, 'hod')
            ");
            $stmt->execute(array($title, $description));
            $success = "✅ Notification UPLOADED! Shows on index marquee!";
        } catch(PDOException $e) {
            $error = "❌ Database error: " . $e->getMessage();
        }
    }
}

// 🔥 FETCH ALL HOD NOTIFICATIONS - PHP 5.3 COMPATIBLE
$notifications = array();
try {
    $stmt = $pdo->query("
        SELECT * FROM notifications 
        WHERE created_by = 'hod' COLLATE utf8mb4_unicode_ci
        ORDER BY created_at DESC 
        LIMIT 20
    ");
    $notifications = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch(PDOException $e) {
    $notifications = array();
}
?>

<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>HOD - Notifications</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css" rel="stylesheet">
    <style>
        body { 
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); 
            min-height: 100vh; 
            padding: 50px 20px; 
        }
        .main-card {
            background: rgba(255,255,255,0.95);
            backdrop-filter: blur(20px);
            border-radius: 25px;
            box-shadow: 0 25px 50px rgba(0,0,0,0.2);
        }
        .card-header {
            background: linear-gradient(45deg, #667eea, #764ba2) !important;
            border-radius: 25px 25px 0 0 !important;
        }
        .form-control:focus {
            border-color: #667eea;
            box-shadow: 0 0 0 0.2rem rgba(102,126,234,0.25);
        }
        .notification-card {
            transition: all 0.3s ease;
            border: none;
            border-radius: 15px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
        }
        .notification-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 15px 30px rgba(0,0,0,0.2);
        }
        .days-badge {
            font-size: 0.75rem;
            padding: 0.4em 0.8em;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-md-10 col-lg-8">
                <div class="main-card">
                    <div class="card-header text-white text-center py-4">
                        <h2 class="mb-2">
                            <i class="fas fa-bell me-3"></i>🔔 HOD Notifications Panel
                        </h2>
                        <p class="mb-2 opacity-75">Create announcements that appear on student dashboard marquee</p>
                        <a href="dashboard.php" class="btn btn-light btn-sm px-4">
                            <i class="fas fa-arrow-left me-2"></i>← Back to Dashboard
                        </a>
                    </div>
                    
                    <div class="card-body p-5">
                        <?php if(isset($success)): ?>
                            <div class="alert alert-success alert-dismissible fade show" role="alert">
                                <i class="fas fa-check-circle me-2"></i><?php echo $success; ?>
                                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                            </div>
                        <?php endif; ?>
                        
                        <?php if(isset($error)): ?>
                            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                                <i class="fas fa-exclamation-triangle me-2"></i><?php echo $error; ?>
                                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                            </div>
                        <?php endif; ?>

                        <!-- 🔥 UPLOAD FORM -->
                        <div class="row mb-5">
                            <div class="col-md-12">
                                <div class="card border-0 shadow-sm mb-4" style="border-radius: 20px;">
                                    <div class="card-header bg-success text-white py-3">
                                        <h5 class="mb-0">
                                            <i class="fas fa-plus-circle me-2"></i>📢 Create New Notification
                                        </h5>
                                    </div>
                                    <div class="card-body p-4">
                                        <form method="POST">
                                            <div class="mb-4">
                                                <label class="form-label fw-bold fs-5">📌 Notification Title</label>
                                                <input type="text" name="title" class="form-control form-control-lg" 
                                                       placeholder="e.g. Exam postponed, Assignment due date..." required maxlength="100">
                                            </div>
                                            <div class="mb-4">
                                                <label class="form-label fw-bold fs-5">📝 Description</label>
                                                <textarea name="description" rows="4" class="form-control form-control-lg" 
                                                          placeholder="Detailed message for students..." required maxlength="500"></textarea>
                                            </div>
                                            <button type="submit" class="btn btn-success btn-lg w-100 py-3 fs-5">
                                                <i class="fas fa-paper-plane me-3"></i>🚀 SEND NOTIFICATION (Auto expires in 15 days)
                                            </button>
                                        </form>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- 🔥 NOTIFICATIONS LIST -->
                        <?php if(!empty($notifications)): ?>
                            <h4 class="fw-bold mb-4 text-center">
                                📋 Your Active Notifications (<?php echo count($notifications); ?>)
                            </h4>
                            <div class="row g-4">
                                <?php foreach($notifications as $notif): 
                                    $days_left = round((strtotime($notif['expires_at']) - time()) / 86400, 1);
                                ?>
                                    <div class="col-md-6 col-lg-6">
                                        <div class="card notification-card h-100">
                                            <div class="card-body">
                                                <div class="d-flex justify-content-between align-items-start mb-2">
                                                    <h6 class="fw-bold mb-0" style="color: #2c3e50;">
                                                        <?php echo htmlspecialchars($notif['title']); ?>
                                                    </h6>
                                                    <span class="badge days-badge 
                                                        <?php echo $days_left > 3 ? 'bg-success' : ($days_left > 0 ? 'bg-warning' : 'bg-danger'); ?>">
                                                        <?php echo $days_left > 0 ? $days_left . ' days' : 'EXPIRED'; ?>
                                                    </span>
                                                </div>
                                                <p class="text-muted small mb-3">
                                                    <?php echo htmlspecialchars($notif['description']); ?>
                                                </p>
                                                <div class="d-flex justify-content-between">
                                                    <small class="text-muted">
                                                        <i class="fas fa-clock me-1"></i>
                                                        <?php echo date('M j, Y', strtotime($notif['created_at'])); ?>
                                                    </small>
                                                    <?php if($notif['is_active']): ?>
                                                        <span class="badge bg-success">
                                                            <i class="fas fa-eye me-1"></i>Active
                                                        </span>
                                                    <?php else: ?>
                                                        <span class="badge bg-secondary">
                                                            <i class="fas fa-pause me-1"></i>Paused
                                                        </span>
                                                    <?php endif; ?>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php else: ?>
                            <div class="text-center py-5 my-5">
                                <i class="fas fa-bell-slash fa-5x text-muted mb-4 opacity-50"></i>
                                <h4 class="text-muted mb-3">No notifications yet</h4>
                                <p class="text-muted lead">Create your first announcement above!</p>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
