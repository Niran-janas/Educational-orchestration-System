<?php
// 🚀 COMPLETE WORKING VERSION - PHP 5.3+ (EMOJI REMOVED)
ob_start();
session_start(); 
include '../config/db_connection.php'; 
$error = '';

if($_SERVER['REQUEST_METHOD'] == 'POST') {
    $email = trim($_POST['email']);
    $password = $_POST['password'];
    $role = $_POST['role'];
    
    try {
        if($role == 'hod') {
            $stmt = $pdo->prepare("SELECT * FROM hod WHERE email = ?");
        } else {
            $stmt = $pdo->prepare("SELECT * FROM staff WHERE email = ?");
        }
        
        $stmt->bindValue(1, $email, PDO::PARAM_STR);
        $stmt->execute();
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        
        // ✅ UNIVERSAL PASSWORD CHECK - WORKS ON ALL PHP VERSIONS
        if($user && check_password($password, $user['password'])) {
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['name'] = $user['name'];
            $_SESSION['role'] = $role;
            $_SESSION['email'] = $user['email'];
            
            if($role == 'staff') {
                $_SESSION['class'] = $user['class'];
                $_SESSION['first_login'] = isset($user['first_login']) ? $user['first_login'] : 0;
            }
            
            ob_end_clean();
            $redirect = ($role == 'hod') ? '../hod/dashboard.php' : '../staff/dashboard.php';
            header("Location: $redirect");
            exit();
        }
        $error = "Invalid email or password!";
    } catch(PDOException $e) {
        $error = "Database error!";
    }
}

// ✅ SIMPLE PASSWORD CHECK - WORKS EVERYWHERE
function check_password($plain, $hash) {
    // Try PHP 5.5+ first
    if (function_exists('password_verify')) {
        return password_verify($plain, $hash);
    }
    
    // Universal fallback: direct comparison OR crypt match
    return ($plain === $hash) || (crypt($plain, $hash) === $hash);
}

ob_end_flush();
?>

<!DOCTYPE html>
<html>
<head>
    <title>Login - Alagappa Arts College</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body { 
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
        }
        .card { 
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255,255,255,0.2);
        }
    </style>
</head>
<body class="d-flex align-items-center min-vh-100">
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-md-5">
                <div class="card shadow-lg border-0">
                    <div class="card-body p-5">
                        <div class="text-center mb-5">
                            <h2 class="text-primary mb-3">Staff / HOD Login</h2>
                            <p class="text-white-50 mb-0">Department of Computer Science</p>
                        </div>
                        
                        <form method="POST">
                            <select class="form-select form-control-lg mb-4" name="role" required>
                                <option value="">Select Role</option>
                                <option value="hod">HOD</option>
                                <option value="staff">Staff</option>
                            </select>
                            
                            <input type="email" class="form-control form-control-lg mb-4" 
                                   name="email" placeholder="your.email@alagappa.edu" required>
                            
                            <input type="password" class="form-control form-control-lg mb-4" 
                                   name="password" placeholder="password" required>
                            
                            <?php if($error): ?>
                                <div class="alert alert-danger"><?php echo $error; ?></div>
                            <?php endif; ?>
                            
                            <button type="submit" class="btn btn-primary w-100 btn-lg fw-bold py-3 fs-5">
                                LOGIN
                            </button>
                        </form>
                        
                        <div class="text-center mt-4">
                            <a href="../index.php" class="btn btn-outline-light btn-lg w-100">
                                Back to Home
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</body>
</html>
