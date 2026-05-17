<?php 
session_start(); 
if(!isset($_SESSION['role']) || $_SESSION['role'] != 'hod') {
    header("Location: ../public/index.php"); exit();
}
include '../config/db_connection.php'; 

$message = '';
$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$editing_staff_id = isset($_GET['edit']) ? $_GET['edit'] : '';

// 🔥 PROCESS FORMS FIRST
if(isset($_POST['delete_staff'])) {
    $staff_id = $_POST['staff_id'];
    try {
        $stmt = $pdo->prepare("DELETE FROM staff WHERE staff_id = ?");
        $stmt->bindValue(1, $staff_id, PDO::PARAM_STR);
        $stmt->execute();
        $message = '✅ Staff <strong>' . $staff_id . '</strong> deleted successfully!';
        $editing_staff_id = ''; // Exit edit mode
    } catch(PDOException $e) {
        $message = '❌ Delete failed!';
    }
}

if(isset($_POST['update_staff'])) {
    $staff_id = $_POST['staff_id'];
    $name = trim($_POST['name']);
    $email = trim($_POST['email']);
    
    if(!empty($name) && !empty($email)) {
        try {
            $stmt = $pdo->prepare("UPDATE staff SET name = ?, email = ? WHERE staff_id = ?");
            $stmt->bindValue(1, $name, PDO::PARAM_STR);
            $stmt->bindValue(2, $email, PDO::PARAM_STR);
            $stmt->bindValue(3, $staff_id, PDO::PARAM_STR);
            $stmt->execute();
            $message = '✅ Staff <strong>' . $staff_id . '</strong> updated successfully!';
            $editing_staff_id = ''; // Exit edit mode
        } catch(PDOException $e) {
            $message = '❌ Update failed!';
        }
    } else {
        $message = '❌ Name and Email required!';
    }
}

if(isset($_POST['cancel_edit'])) {
    $editing_staff_id = ''; // Exit edit mode
}

// 🔥 FETCH STAFF DATA
$where_clause = array();
$params = array();
if(!empty($search)) {
    $where_clause[] = "staff_id LIKE ?";
    $where_clause[] = "OR name LIKE ?";
    $where_clause[] = "OR class LIKE ?";
    $params = array("%$search%", "%$search%", "%$search%");
}

$sql = "SELECT * FROM staff";
if(!empty($where_clause)) {
    $sql .= " WHERE " . implode(' ', $where_clause);
}
$sql .= " ORDER BY created_at DESC";

$stmt = $pdo->prepare($sql);
if(!empty($params)) {
    foreach($params as $index => $param) {
        $stmt->bindValue($index + 1, $param, PDO::PARAM_STR);
    }
}
$stmt->execute();
$all_staff = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html>
<head>
    <title>HOD - Manage Staff</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css" rel="stylesheet">
    <style>
        body { 
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh; padding: 20px 0;
            font-family: 'Segoe UI', Tahoma, Geneva, sans-serif;
        }
        .main-card {
            max-width: 1200px; margin: 0 auto;
            border-radius: 25px; box-shadow: 0 25px 50px rgba(0,0,0,0.3);
            background: rgba(255,255,255,0.95);
        }
        .card-header {
            background: linear-gradient(135deg, #3b82f6, #1d4ed8);
            color: white; border-radius: 25px 25px 0 0;
            padding: 30px; text-align: center;
        }
        .back-btn {
            position: fixed; top: 20px; left: 20px; z-index: 1000;
            background: linear-gradient(135deg, #10b981, #059669);
        }
        .staff-id {
            font-weight: 800; color: #1e40af;
            background: linear-gradient(135deg, #dbeafe, #bfdbfe);
            padding: 10px 20px; border-radius: 25px;
        }
        .table thead th {
            background: linear-gradient(135deg, #2d3436, #636e72);
            color: white; border: none; padding: 20px 15px;
        }
        .edit-row {
            background: linear-gradient(135deg, #fef3c7, #fde68a) !important;
            border: 4px solid #f59e0b !important;
            box-shadow: 0 0 20px rgba(245, 158, 11, 0.4);
            border-radius: 15px;
        }
        .action-btn {
            padding: 8px 12px; border-radius: 15px;
            font-size: 0.9rem; border: none;
            transition: all 0.3s ease; margin: 1px;
        }
        .btn-edit { background: linear-gradient(135deg, #f59e0b, #d97706); color: white; }
        .btn-save { background: linear-gradient(135deg, #10b981, #059669); color: white; }
        .btn-cancel-edit { background: linear-gradient(135deg, #6b7280, #4b5563); color: white; }
        .btn-delete { background: linear-gradient(135deg, #ef4444, #dc2626); color: white; }
        .search-box {
            max-width: 400px; border-radius: 25px;
            border: 3px solid #10b981; background: rgba(255,255,255,0.9);
        }
        .alert-custom {
            max-width: 900px; margin: 20px auto;
            border-radius: 20px; border: none;
            animation: slideDown 0.5s ease;
        }
        @keyframes slideDown {
            from { transform: translateY(-20px); opacity: 0; }
            to { transform: translateY(0); opacity: 1; }
        }
        .edit-input {
            border: 2px solid #f59e0b !important;
            background: white;
        }
    </style>
</head>
<body>
    <a href="dashboard.php" class="btn back-btn px-4 py-2 shadow-lg">
        <i class="fas fa-arrow-left me-2"></i>Back to Dashboard
    </a>

    <div class="container-fluid">
        <div class="row justify-content-center">
            <div class="col-12">
                <?php if($message): ?>
                <div class="alert <?php echo strpos($message, '✅') !== false ? 'alert-success' : 'alert-danger'; ?> alert-dismissible fade show alert-custom mx-auto">
                    <i class="fas fa-<?php echo strpos($message, '✅') !== false ? 'check-circle' : 'exclamation-triangle'; ?> me-2"></i>
                    <?php echo $message; ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
                <?php endif; ?>

                <div class="main-card">
                    <div class="card-header">
                        <h1><i class="fas fa-users-cog me-2"></i>Manage Staff Members</h1>
                        <p class="mb-0 opacity-75 mt-2">
                            Total: <?php echo count($all_staff); ?> 
                            <?php if($editing_staff_id): ?>
                            | <span class="badge bg-warning">EDITING: <?php echo $editing_staff_id; ?></span>
                            <?php endif; ?>
                        </p>
                    </div>
                    <div class="card-body p-4">
                        <!-- SEARCH -->
                        <div class="row mb-4">
                            <div class="col-md-6">
                                <form method="GET" class="d-flex">
                                    <input type="text" name="search" class="form-control search-box me-2" 
                                           placeholder="🔍 Search by ID, Name, Class..." 
                                           value="<?php echo htmlspecialchars($search); ?>">
                                    <button type="submit" class="btn btn-success px-4">
                                        <i class="fas fa-search"></i>
                                    </button>
                                    <?php if($search): ?>
                                    <a href="manage_staff.php" class="btn btn-outline-secondary ms-2">Clear</a>
                                    <?php endif; ?>
                                </form>
                            </div>
                        </div>

                        <!-- STAFF TABLE -->
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th width="8%">#</th>
                                        <th width="15%">Staff ID</th>
                                        <th width="20%">Name</th>
                                        <th width="12%">Class</th>
                                        <th width="18%">Email</th>
                                        <th width="17%">Qualification</th>
                                        <th width="10%">Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if(count($all_staff) > 0): ?>
                                        <?php foreach($all_staff as $index => $staff): ?>
                                        <?php $is_editing = ($editing_staff_id === $staff['staff_id']); ?>
                                        <tr class="<?php echo $is_editing ? 'edit-row' : ''; ?>">
                                            <td><strong>#<?php echo $index + 1; ?></strong></td>
                                            
                                            <!-- Staff ID (Never Editable) -->
                                            <td><span class="staff-id"><?php echo htmlspecialchars($staff['staff_id']); ?></span></td>
                                            
                                            <!-- Name -->
                                            <td>
                                                <?php if($is_editing): ?>
                                                    <form method="POST" style="display: inline;">
                                                        <input type="hidden" name="staff_id" value="<?php echo $staff['staff_id']; ?>">
                                                        <input type="text" name="name" class="form-control edit-input mb-1" 
                                                               value="<?php echo htmlspecialchars($staff['name']); ?>" required 
                                                               style="font-weight: bold;">
                                                <?php else: ?>
                                                    <strong><?php echo htmlspecialchars($staff['name']); ?></strong>
                                                <?php endif; ?>
                                            </td>
                                            
                                            <!-- Class -->
                                            <td>
                                                <span class="badge bg-info fs-6 px-3 py-2">
                                                    <?php echo htmlspecialchars($staff['class']); ?>
                                                </span>
                                            </td>
                                            
                                            <!-- Email -->
                                            <td>
                                                <?php if($is_editing): ?>
                                                    <input type="email" name="email" class="form-control edit-input" 
                                                           value="<?php echo htmlspecialchars($staff['email']); ?>" required>
                                                <?php else: ?>
                                                    <?php echo htmlspecialchars($staff['email']); ?>
                                                <?php endif; ?>
                                            </td>
                                            
                                            <!-- Qualification -->
                                            <td><?php echo isset($staff['qualification']) && $staff['qualification'] != '' ? $staff['qualification'] : 'N/A'; ?></td>
                                            
                                            <!-- ACTIONS -->
                                            <td>
                                                <?php if($is_editing): ?>
                                                    <!-- EDIT MODE: Save / Cancel -->
                                                    <button type="submit" name="update_staff" class="btn btn-save action-btn" title="Save">
                                                        <i class="fas fa-save"></i>
                                                    </button>
                                                    <button type="submit" name="cancel_edit" class="btn btn-cancel-edit action-btn" title="Cancel">
                                                        <i class="fas fa-times"></i>
                                                    </button>
                                                    </form>
                                                <?php else: ?>
                                                    <!-- NORMAL MODE: Edit / Delete -->
                                                    <a href="manage_staff.php?edit=<?php echo $staff['staff_id']; ?><?php echo $search ? '&search=' . urlencode($search) : ''; ?>" 
                                                       class="btn btn-edit action-btn" title="Edit">
                                                        <i class="fas fa-edit"></i>
                                                    </a>
                                                    <form method="POST" style="display: inline;" class="d-inline" 
                                                          onsubmit="return confirm('Delete <?php echo addslashes($staff['name']); ?>?')">
                                                        <input type="hidden" name="staff_id" value="<?php echo $staff['staff_id']; ?>">
                                                        <button type="submit" name="delete_staff" class="btn btn-delete action-btn" title="Delete">
                                                            <i class="fas fa-trash"></i>
                                                        </button>
                                                    </form>
                                                <?php endif; ?>
                                            </td>
                                        </tr>
                                        <?php endforeach; ?>
                                    <?php else: ?>
                                        <tr>
                                            <td colspan="7" class="text-center py-5">
                                                <i class="fas fa-users fa-4x text-muted mb-4"></i>
                                                <h4 class="text-muted"><?php echo $search ? 'No matching staff' : 'No staff yet'; ?></h4>
                                                <a href="staff_register.php" class="btn btn-success btn-lg mt-3">
                                                    <i class="fas fa-plus me-2"></i>Add First Staff
                                                </a>
                                            </td>
                                        </tr>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
