<?php 
session_start(); 
if(!isset($_SESSION['role']) || !in_array($_SESSION['role'], ['hod','admin'])) {
    header("Location: ../index.php"); exit();
}
include '../config/db_connection.php'; 

$semester_type = $_GET['semester'] ?? '';
$message = '';

// 🔥 FETCH STAFF SHOWING staff_initial + name
$stmt = $pdo->prepare("SELECT staff_id, staff_initial, name as staff_name FROM staff WHERE staff_initial IS NOT NULL ORDER BY staff_initial");
$stmt->execute();
$all_staff = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Subjects logic (same)
$year_subjects = [];
if($semester_type) {
    $semester_map = [
        'ODD' => ['I Year'=>'I', 'II Year'=>'III', 'III Year'=>'V'],
        'EVEN' => ['I Year'=>'II', 'II Year'=>'IV', 'III Year'=>'VI']
    ];
    
    foreach($semester_map[$semester_type] as $year_name => $target_semester) {
        $stmt = $pdo->prepare("SELECT id, class_name, subject, semester FROM subjects_mapping WHERE class_name=? AND semester=? ORDER BY subject");
        $stmt->execute([$year_name, $target_semester]);
        $subjects = $stmt->fetchAll(PDO::FETCH_ASSOC);
        if($subjects) $year_subjects[$year_name] = $subjects;
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Staff Allocation - SHOW INITIALS</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
    <div class="container mt-5">
        <div class="card shadow-lg">
            <div class="card-header bg-primary text-white text-center py-4">
                <h2>📋 Staff Allocation - <?=$semester_type?> (<?=count($all_staff)?> Staff)</h2>
                <a href="generate_timetable.php" class="btn btn-secondary">&larr; Back</a>
            </div>
            
            <div class="card-body">
                <?php if($message): ?><div class="alert alert-success"><?=$message?></div><?php endif; ?>

                <form method="POST">
                    <?php foreach($year_subjects as $year => $subjects): ?>
                        <div class="card mb-4 shadow">
                            <div class="card-header bg-info text-white">
                                <h5><?=$year?> (<?=count($subjects)?> Subjects)</h5>
                            </div>
                            <div class="card-body">
                                <table class="table table-bordered">
                                    <thead class="table-dark">
                                        <tr>
                                            <th>Subject</th>
                                            <th>Hours</th>
                                            <th>Staff (Initial)</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach($subjects as $sub): ?>
                                            <tr>
                                                <td><strong><?=htmlspecialchars($sub['subject'])?></strong></td>
                                                <td>
                                                    <input type="number" name="allocations[<?=$year?>][<?=$sub['id']?>][hours]" 
                                                           value="4" min="1" max="6" class="form-control" required>
                                                </td>
                                                <td>
                                                    <!-- 🔥 SHOW staff_initial prominently -->
                                                    <select name="allocations[<?=$year?>][<?=$sub['id']?>][staff]" class="form-select" required>
                                                        <option value="">Select Staff</option>
                                                        <?php foreach($all_staff as $staff): ?>
                                                            <option value="<?=htmlspecialchars($staff['staff_id'])?>">
                                                                <strong><?=htmlspecialchars($staff['staff_initial'])?></strong> 
                                                                - <?=htmlspecialchars($staff['staff_name'])?>
                                                            </option>
                                                        <?php endforeach; ?>
                                                    </select>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    <?php endforeach; ?>
                    <!-- 🔥 STEP 3 END - REDIRECT TO timetable_generator.php -->
           <div class="text-center mt-5 p-4 border rounded bg-light">
             <h4>✅ All <?=$semester_type?> Semester Subjects Loaded!</h4>
           <p class="lead">Ready for staff allocation → timetable generation</p>
           <a href="timetable_generator.php?semester=<?=$semester_type?>" 
       class="btn btn-success btn-lg px-5 py-2 shadow-lg">
        📋 STAFF ALLOCATION → GENERATE TIMETABLE
    </a>
</div>

                   
                </form>
            </div>
        </div>
    </div>
</body>
</html>
