<?php
include '../config/db_connection.php';
$class_name = $_GET['class_name'];

// 🔥 NEW QUERY - FROM subjects_mapping table
$stmt = $pdo->prepare("
    SELECT subject FROM subjects_mapping 
    WHERE class_name = ? 
    ORDER BY subject
");
$stmt->execute([$class_name]);
$subjects = $stmt->fetchAll(PDO::FETCH_ASSOC);
echo json_encode($subjects);
?>
