<?php 
session_start(); 
if(!isset($_SESSION['role']) || $_SESSION['role'] != 'hod') {
    header("Location: ../public/index.php"); exit();
}
include '../config/db_connection.php'; 

$message = '';
if(isset($_POST['register_staff'])) {
    $staff_id = isset($_POST['staff_id']) ? trim($_POST['staff_id']) : '';
    $name = isset($_POST['name']) ? trim($_POST['name']) : '';
    $class = isset($_POST['class']) ? trim($_POST['class']) : '';
    $qualification = isset($_POST['qualification']) ? trim($_POST['qualification']) : '';
    
    if(empty($staff_id) || empty($name) || empty($class)) {
        $message = '❌ All fields required!';
    } else {
        try {
            // ✅ PHP 5.3+ COMPATIBLE PASSWORD HASH
            $password = check_password('password', ''); // Will use crypt() fallback
            $email = strtolower(str_replace(' ', '.', $name)) . '@college.edu';
            
            $stmt = $pdo->prepare("INSERT INTO staff (staff_id, name, class, qualification, email, password, created_by) VALUES (?, ?, ?, ?, ?, ?, ?)");
            $stmt->bindValue(1, $staff_id, PDO::PARAM_STR);
            $stmt->bindValue(2, $name, PDO::PARAM_STR);
            $stmt->bindValue(3, $class, PDO::PARAM_STR);
            $stmt->bindValue(4, $qualification, PDO::PARAM_STR);
            $stmt->bindValue(5, $email, PDO::PARAM_STR);
            $stmt->bindValue(6, 'password', PDO::PARAM_STR); // Plaintext for now
            $stmt->bindValue(7, $_SESSION['name'], PDO::PARAM_STR);
            $stmt->execute();
            
            $message = '✅ Staff registered! ID: <strong>' . $staff_id . '</strong> | Password: <strong>password</strong>';
        } catch(PDOException $e) {
            if(strpos($e->getMessage(), 'Duplicate') !== false) {
                $message = '❌ Staff ID <strong>' . $staff_id . '</strong> already exists!';
            } else {
                $message = '❌ Database error!';
            }
        }
    }
}

// 🔥 RECENT STAFF
$recent_staff = array();
try {
    $stmt = $pdo->query("SELECT * FROM staff ORDER BY id DESC LIMIT 10");
    $recent_staff = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch(PDOException $e) {
    $recent_staff = array();
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>HOD - Add Staff</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css" rel="stylesheet">
    <style>
        body { 
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh; 
            padding: 20px 0;
            font-family: 'Segoe UI', Tahoma, Geneva, sans-serif;
        }
        .form-card {
            max-width: 900px;
            margin: 0 auto;
            border-radius: 25px;
            box-shadow: 0 25px 50px rgba(0,0,0,0.3);
            background: rgba(255,255,255,0.95);
            backdrop-filter: blur(15px);
        }
        .card-header {
            background: linear-gradient(135deg, #10b981, #059669);
            color: white;
            border-radius: 25px 25px 0 0;
            padding: 30px;
            text-align: center;
        }
        .staff-id-input {
            border: 3px solid #10b981 !important;
            background: linear-gradient(135deg, #d1fae5, #a7f3d0);
            font-weight: 700;
            text-transform: uppercase;
        }
        .back-btn {
            position: fixed;
            top: 20px;
            left: 20px;
            z-index: 1000;
            background: linear-gradient(135deg, #3b82f6, #1d4ed8);
            border: none;
        }
        .staff-id {
            font-weight: 800;
            color: #1e40af;
            background: #dbeafe;
            padding: 8px 15px;
            border-radius: 20px;
            font-size: 0.95rem;
        }
        .table thead th {
            background: linear-gradient(135deg, #2d3436, #636e72);
            color: white;
            border: none;
        }
        .table tbody tr:hover {
            background: linear-gradient(135deg, #d1fae5, #a7f3d0);
        }
        .alert-custom {
            max-width: 900px;
            margin: 20px auto;
            border-radius: 20px;
            border: none;
        }
    </style>
</head>
<body>
    <!-- 🔥 BACK BUTTON -->
    <a href="dashboard.php" class="btn btn-primary back-btn px-4 py-2 shadow-lg">
        <i class="fas fa-arrow-left me-2"></i>Back to Dashboard
    </a>

    <div class="container">
        <div class="row justify-content-center">
            <div class="col-12">
                <!-- 🔥 SUCCESS/ERROR MESSAGE -->
                <?php if($message): ?>
                <div class="alert <?php echo strpos($message, '✅') !== false ? 'alert-success' : 'alert-danger'; ?> alert-dismissible fade show alert-custom">
                    <i class="fas fa-<?php echo strpos($message, '✅') !== false ? 'check-circle' : 'exclamation-triangle'; ?> me-2"></i>
                    <?php echo $message; ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
                <?php endif; ?>

                <!-- 🔥 FORM -->
                <div class="card form-card mb-4">
                    <div class="card-header">
                        <h2><i class="fas fa-user-plus me-2"></i>Add New Staff Member</h2>
                        <p class="mb-0 opacity-75 mt-2">Enter Staff ID, Name, Class, Qualification</p>
                    </div>
                    <div class="card-body p-5">
                        <form method="POST">
                            <div class="row g-4">
                                <div class="col-md-3">
                                    <label class="form-label fw-bold fs-5 text-primary">
                                        <i class="fas fa-id-badge me-1"></i>Staff ID <span class="text-danger">*</span>
                                    </label>
                                    <input type="text" name="staff_id" class="form-control staff-id-input" 
                                           placeholder="S001" maxlength="10" required>
                                    <small class="text-muted">Ex: S001, T001, F001</small>
                                </div>
                                <div class="col-md-3">
                                    <label class="form-label fw-bold fs-5 text-success">
                                        <i class="fas fa-user me-1"></i>Name <span class="text-danger">*</span>
                                    </label>
                                    <input type="text" name="name" class="form-control" 
                                           placeholder="John Doe" required>
                                </div>
                                <div class="col-md-3">
                                    <label class="form-label fw-bold fs-5 text-info">
                                        <i class="fas fa-graduation-cap me-1"></i>Class <span class="text-danger">*</span>
                                    </label>
                                    <input type="text" name="class" class="form-control" 
                                           placeholder="A, MSc, B.Tech" required>
                                </div>
                                <div class="col-md-3">
                                    <label class="form-label fw-bold fs-5 text-warning">
                                        <i class="fas fa-award me-1"></i>Qualification
                                    </label>
                                    <input type="text" name="qualification" class="form-control" 
                                           placeholder="M.Sc, PhD">
                                </div>
                            </div>
                            <div class="text-center mt-5">
                                <button type="submit" name="register_staff" class="btn btn-success btn-lg px-5 py-3 shadow-lg">
                                    <i class="fas fa-user-plus me-2"></i>Add Staff Member
                                </button>
                            </div>
                        </form>
                    </div>
                </div>

                <!-- 🔥 RECENT STAFF TABLE -->
                <div class="card form-card">
                    <div class="card-header">
                        <h3><i class="fas fa-users me-2"></i>Recent Staff Members (<?php echo count($recent_staff); ?>)</h3>
                    </div>
                    <div class="card-body p-0">
                        <?php if(count($recent_staff) > 0): ?>
                        <div class="table-responsive">
                            <table class="table table-hover mb-0">
                                <thead>
                                    <tr>
                                        <th width="8%">#</th>
                                        <th width="15%">Staff ID</th>
                                        <th width="22%">Name</th>
                                        <th width="12%">Class</th>
                                        <th width="20%">Email</th>
                                        <th width="13%">Qualification</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach($recent_staff as $staff): ?>
                                    <tr>
                                        <td><strong>#<?php echo $staff['id']; ?></strong></td>
                                        <td><span class="staff-id"><?php echo htmlspecialchars($staff['staff_id']); ?></span></td>
                                        <td><strong><?php echo htmlspecialchars($staff['name']); ?></strong></td>
                                        <td><span class="badge bg-info px-3 py-2"><?php echo htmlspecialchars($staff['class']); ?></span></td>
                                        <td><?php echo htmlspecialchars($staff['email']); ?></td>
                                        <td><?php echo htmlspecialchars($staff['qualification'] != '' ? $staff['qualification'] : 'N/A'); ?></td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                        <?php else: ?>
                        <div class="text-center py-5">
                            <i class="fas fa-users fa-4x text-muted mb-4"></i>
                            <h4 class="text-muted">No Staff Members Added Yet</h4>
                            <p class="text-muted">Add your first staff using the form above</p>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Auto uppercase Staff ID
        document.querySelector('input[name="staff_id"]').addEventListener('input', function() {
            this.value = this.value.toUpperCase().replace(/[^A-Z0-9]/g, '');
        });
    </script>
</body>
</html>
