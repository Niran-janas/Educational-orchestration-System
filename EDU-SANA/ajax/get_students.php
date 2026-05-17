<?php
include '../config/db_connection.php';
$class_name = $_GET['class_name'];
$subject = $_GET['subject'];
$date = $_GET['date'];

// 🔥 NEW QUERY - Match BOTH class AND subject from subjects_mapping
$stmt = $pdo->prepare("
    SELECT DISTINCT s.roll_no, s.student_name 
    FROM students s
    INNER JOIN subjects_mapping sm ON s.class = sm.class_name AND s.subject = sm.subject
    WHERE sm.class_name = ? AND sm.subject = ?
    ORDER BY s.roll_no
");
$stmt->execute([$class_name, $subject]);
$students = $stmt->fetchAll(PDO::FETCH_ASSOC);
echo json_encode($students);
?>
