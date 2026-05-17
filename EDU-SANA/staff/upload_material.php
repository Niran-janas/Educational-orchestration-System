<?php 
session_start(); 
if(!isset($_SESSION['role']) || $_SESSION['role'] != 'staff') {
    header("Location: ../index.php"); exit();
}
include '../config/db_connection.php'; 

$classes_result = $pdo->query("SELECT DISTINCT class FROM students ORDER BY class")->fetchAll(PDO::FETCH_COLUMN);
$classes = is_array($classes_result) ? $classes_result : array();  // ✅ FIXED
$message = '';

// 🔥 UPLOAD HANDLER
if(isset($_POST['upload_material'])) {
    $title = $_POST['title'];
    $class = $_POST['class'];
    $subject = $_POST['subject'];
    
    if(isset($_FILES['material_file']) && $_FILES['material_file']['error'] == 0) {
        $file = $_FILES['material_file'];
        $fileExt = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        $allowed = array('pdf', 'doc', 'docx', 'ppt', 'pptx', 'zip');  // ✅ FIXED: [] → array()
        
        if(in_array($fileExt, $allowed) && $file['size'] <= 100*1024*1024) { // 100MB
            $newName = time() . '_' . md5($file['name']) . '.' . $fileExt;
            $uploadPath = '../uploads/study_materials/' . $newName;
            
            if(move_uploaded_file($file['tmp_name'], $uploadPath)) {
                $stmt = $pdo->prepare("INSERT INTO study_materials (title, class, subject, filename, filesize, uploaded_by) VALUES (?, ?, ?, ?, ?, ?)");
                $stmt->execute(array($title, $class, $subject, $newName, formatBytes($file['size']), $_SESSION['name']));  // ✅ FIXED: [] → array()
                $message = "✅ Material uploaded successfully!";
            } else {
                $message = "❌ Upload failed!";
            }
        } else {
            $message = "❌ Invalid file type or too large (Max 100MB)!";
        }
    }
}

function formatBytes($size) {
    $units = array('B', 'KB', 'MB', 'GB');  // ✅ FIXED: [] → array()
    for($i=0; $size > 1024 && $i < 3; $i++) $size /= 1024;
    return round($size, 2) . ' ' . $units[$i];
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Study Materials - Staff</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        .upload-card { border: 3px dashed #007bff; transition: all 0.3s; }
        .upload-card:hover { border-color: #28a745; background: #f8f9ff; transform: scale(1.02); }
        .file-drag { min-height: 200px; }
        .sidebar { position: sticky; top: 20px; }
    </style>
</head>
<body class="bg-light">
    <?php include '../includes/header.php'; ?>
    
    <div class="container-fluid mt-4">
        <div class="row">
            <div class="col-md-3">
                <div class="card sidebar">
                    <div class="card-header bg-success text-white text-center">
                        <h5><?php echo htmlspecialchars($_SESSION['name']); ?></h5>
                        <small>Study Materials</small>
                    </div>
                    <div class="list-group list-group-flush">
                        <a href="dashboard.php" class="list-group-item list-group-item-action">📊 Dashboard</a>
                        <a href="attendance.php" class="list-group-item list-group-item-action">✅ Attendance</a>
                        <a href="attendance_request.php" class="list-group-item list-group-item-action">🔧 Corrections</a>
                        <a href="upload_material.php" class="list-group-item list-group-item-action active">📚 Study Materials</a>
                        <a href="../auth/logout.php" class="list-group-item list-group-item-action text-danger">🚪 Logout</a>
                    </div>
                </div>
            </div>

            <div class="col-md-9">
                <?php if($message): ?>
                <div class="alert alert-success alert-dismissible fade show mb-4">
                    <i class="fas fa-check-circle me-2"></i><?php echo htmlspecialchars($message); ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
                <?php endif; ?>

                <!-- 🔥 UPLOAD FORM -->
                <div class="card shadow-lg upload-card mb-4">
                    <div class="card-header bg-primary text-white text-center py-4">
                        <h3><i class="fas fa-cloud-upload-alt me-3"></i>Upload Study Material</h3>
                        <small class="opacity-75">PDF, DOC, PPT, ZIP (Max 100MB)</small>
                    </div>
                    <div class="card-body file-drag text-center p-5">
                        <form method="POST" enctype="multipart/form-data">
                            <div class="row g-4">
                                <div class="col-md-6">
                                    <label class="form-label fw-bold fs-5">Title</label>
                                    <input type="text" name="title" class="form-control form-control-lg" required 
                                        placeholder="Ex: DBMS Unit 1 Notes">
                                </div>
                                <div class="col-md-3">
                                    <label class="form-label fw-bold fs-5">Class</label>
                                    <select name="class" class="form-select form-select-lg" required>
                                        <option value="">Select Class</option>
                                        <?php foreach($classes as $class): ?>
                                        <option value="<?php echo htmlspecialchars($class); ?>"><?php echo htmlspecialchars($class); ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div class="col-md-3">
                                    <label class="form-label fw-bold fs-5">Subject</label>
                                    <input type="text" name="subject" class="form-control form-control-lg" required 
                                        placeholder="Ex: Database Management System">
                                </div>
                                <div class="col-12">
                                    <label class="form-label fw-bold fs-5">Upload File</label>
                                    <input type="file" name="material_file" class="form-control form-control-lg" required 
                                        accept=".pdf,.doc,.docx,.ppt,.pptx,.zip">
                                    <small class="text-muted">📎 Max 100MB - PDF/DOC/PPT/ZIP only</small>
                                </div>
                                <div class="col-12 text-center">
                                    <button type="submit" name="upload_material" class="btn btn-success btn-lg px-5">
                                        <i class="fas fa-upload me-2"></i>🚀 UPLOAD MATERIAL
                                    </button>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>

                <!-- 🔥 RECENT UPLOADS -->
                <?php
                $recent_result = $pdo->query("SELECT * FROM study_materials ORDER BY upload_date DESC LIMIT 10")->fetchAll();
                $recent = is_array($recent_result) ? $recent_result : array();
                ?>
                <?php if(!empty($recent)): ?>
                <div class="card shadow">
                    <div class="card-header bg-info text-white">
                        <h5><i class="fas fa-history me-2"></i>Recent Uploads (Last 10)</h5>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <?php foreach($recent as $material): ?>
                            <div class="col-md-6 col-lg-4 mb-3">
                                <div class="card h-100 border-0 shadow-sm">
                                    <div class="card-body">
                                        <h6 class="card-title"><?php echo htmlspecialchars($material['title']); ?></h6>
                                        <div class="mb-2">
                                            <span class="badge bg-primary"><?php echo htmlspecialchars($material['class']); ?></span>
                                            <span class="badge bg-warning text-dark"><?php echo htmlspecialchars($material['subject']); ?></span>
                                        </div>
                                        <small class="text-muted">
                                            📁 <?php echo htmlspecialchars($material['filesize']); ?> | 
                                            👤 <?php echo htmlspecialchars($material['uploaded_by']); ?> | 
                                            📅 <?php echo date('d-m-Y H:i', strtotime($material['upload_date'])); ?>
                                        </small>
                                    </div>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
