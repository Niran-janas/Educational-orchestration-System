<?php
session_start();

if(!isset($_SESSION['role']) || !in_array($_SESSION['role'], array('hod','admin'))) {
    header("Location: ../index.php");
    exit();
}

include '../config/db_connection.php';

// 🔥 SET UTF8MB4 FOR EMOJI SUPPORT (WAMP SAFE)
$pdo->exec("SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci");

/* SAFE CLASS KEYS (NO SPACE ISSUE) - PHP 5.3 COMPATIBLE */
$classes = array(
    'I_Year'   => 'I Year',
    'II_Year'  => 'II Year',
    'III_Year' => 'III Year',
    'I_MSc'    => 'I MSc',
    'II_MSc'   => 'II MSc'
);

$message = "";

/* ================= SAVE LOGIC ================= */
if(isset($_POST['save_only']) || isset($_POST['save_pdf'])) {

    foreach($classes as $key => $class_name) {

        /* Delete only that class */
        $del = $pdo->prepare("DELETE FROM manual_timetable WHERE class_name=? COLLATE utf8mb4_unicode_ci");
        $del->execute(array($class_name));

        /* Prepare insert once */
        $insert = $pdo->prepare(
            "INSERT INTO manual_timetable(class_name,day_num,hour_num,subject,staff_initial)
             VALUES(?,?,?,?,?)"
        );

        for($day=1;$day<=6;$day++){
            for($hour=1;$hour<=5;$hour++){

                $sub_field   = $key."_D".$day."_H".$hour."_sub";
                $staff_field = $key."_D".$day."_H".$hour."_staff";

                $subject = isset($_POST[$sub_field]) ? trim($_POST[$sub_field]) : '';
                $staff   = isset($_POST[$staff_field]) ? trim($_POST[$staff_field]) : '';

                if($subject !== '' && $staff !== '') {
                    $insert->execute(array($class_name,$day,$hour,$subject,$staff));
                }
            }
        }
    }

    if(isset($_POST['save_pdf'])) {
        header("Location: print_timetable.php");
        exit();
    } else {
        $message = "<div class='alert alert-success text-center animate__animated animate__bounceIn'>
                        <b>✅ Timetable Saved Successfully!</b>
                    </div>";
    }
}

/* ================= LOAD SAVED DATA ================= */
$data = array();

foreach($classes as $key => $class_name){

    $data[$key] = array();

    $stmt = $pdo->prepare(
        "SELECT * FROM manual_timetable 
         WHERE class_name=? COLLATE utf8mb4_unicode_ci
         ORDER BY day_num,hour_num"
    );
    $stmt->execute(array($class_name));  
    
    while($row = $stmt->fetch(PDO::FETCH_ASSOC)){
        $data[$key][$row['day_num']][$row['hour_num']] = array(
            'subject' => $row['subject'],
            'staff'   => $row['staff_initial']
        );
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Timetable Creator</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/animate.css/4.1.1/animate.min.css" rel="stylesheet">
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap');
        
        * {
            font-family: 'Poppins', sans-serif;
        }
        
        body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            animation: gradientShift 10s ease infinite;
        }
        
        @keyframes gradientShift {
            0%, 100% { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); }
            50% { background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%); }
        }
        
        .container-fluid {
            perspective: 1000px;
        }
        
        .card {
            background: rgba(255,255,255,0.95);
            backdrop-filter: blur(10px);
            border: none;
            border-radius: 20px;
            box-shadow: 0 20px 40px rgba(0,0,0,0.1);
            transform-style: preserve-3d;
            transition: all 0.4s cubic-bezier(0.175, 0.885, 0.32, 1.275);
        }
        
        .card:hover {
            transform: translateY(-10px) rotateX(5deg) rotateY(5deg);
            box-shadow: 0 30px 60px rgba(0,0,0,0.2);
        }
        
        .card-header {
            border-radius: 20px 20px 0 0 !important;
            background: linear-gradient(45deg, #667eea, #764ba2) !important;
            transform: translateZ(30px);
            position: relative;
            overflow: hidden;
        }
        
        .card-header::before {
            content: '';
            position: absolute;
            top: -50%;
            left: -50%;
            width: 200%;
            height: 200%;
            background: linear-gradient(45deg, transparent, rgba(255,255,255,0.3), transparent);
            transform: rotate(45deg);
            transition: all 0.6s;
        }
        
        .card:hover .card-header::before {
            animation: shine 1.5s infinite;
        }
        
        @keyframes shine {
            0% { transform: translateX(-100%) translateY(-100%) rotate(45deg); }
            100% { transform: translateX(100%) translateY(100%) rotate(45deg); }
        }
        
        .input-field { 
            height: 28px !important; 
            font-size: 11px !important; 
            padding: 4px 6px !important;
            border-radius: 8px;
            border: 2px solid #e9ecef;
            transition: all 0.3s ease;
        }
        
        .input-field:focus {
            border-color: #667eea;
            box-shadow: 0 0 15px rgba(102, 126, 234, 0.3);
            transform: scale(1.02);
        }
        
        .saved-cell {
            background: linear-gradient(45deg, #d4edda, #c3e6cb) !important;
            border: 3px solid #28a745 !important;
            position: relative;
            overflow: hidden;
        }
        
        .saved-cell::after {
            content: '✓';
            position: absolute;
            top: 2px;
            right: 2px;
            color: #28a745;
            font-weight: bold;
            font-size: 12px;
            animation: pulse 2s infinite;
        }
        
        @keyframes pulse {
            0%, 100% { transform: scale(1); opacity: 1; }
            50% { transform: scale(1.2); opacity: 0.7; }
        }
        
        .table td { 
            padding: 4px !important; 
            vertical-align: top;
            position: relative;
            transition: all 0.3s ease;
        }
        
        .table td:hover {
            transform: scale(1.02);
            z-index: 10;
        }
        
        .btn {
            border-radius: 50px;
            padding: 12px 30px;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 1px;
            position: relative;
            overflow: hidden;
            transform: translateZ(10px);
        }
        
        .btn-success {
            background: linear-gradient(45deg, #28a745, #20c997);
            border: none;
            box-shadow: 0 10px 30px rgba(40, 167, 69, 0.4);
        }
        
        .btn-danger {
            background: linear-gradient(45deg, #dc3545, #fd7e14);
            border: none;
            box-shadow: 0 10px 30px rgba(220, 53, 69, 0.4);
        }
        
        .btn:hover {
            transform: translateY(-3px) translateZ(20px);
            box-shadow: 0 20px 40px rgba(0,0,0,0.3);
        }
        
        .btn::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255,255,255,0.3), transparent);
            transition: left 0.5s;
        }
        
        .btn:hover::before {
            left: 100%;
        }
        
        .table-dark th {
            background: linear-gradient(45deg, #343a40, #495057) !important;
        }
        
        .table-info {
            background: linear-gradient(45deg, #17a2b8, #20c997) !important;
            color: white !important;
        }
        
        h2 {
            text-shadow: 0 2px 10px rgba(0,0,0,0.3);
            background: linear-gradient(45deg, #667eea, #764ba2);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            animation: textGlow 3s ease-in-out infinite alternate;
        }
        
        @keyframes textGlow {
            from { filter: drop-shadow(0 0 5px rgba(102, 126, 234, 0.5)); }
            to { filter: drop-shadow(0 0 20px rgba(102, 126, 234, 0.8)); }
        }
        
        @media (max-width: 768px) {
            .card { margin: 10px; transform: none !important; }
            .btn { margin: 5px; padding: 10px 20px; }
        }
    </style>
</head>

<body>
<div class="container-fluid">
    <div class="text-center mb-5 animate__animated animate__fadeInDown">
        <h2 class="display-4">📚 DEPARTMENT TIMETABLE CREATOR</h2>
        <?php echo $message; ?>
    </div>

    <form method="POST">
        <?php foreach($classes as $key => $class_name): ?>
        <div class="card mb-5 animate__animated animate__fadeInUp animate__delay-1s">
            <div class="card-header">
                <b><?php echo $class_name; ?> - D1 to D6 Schedule ✨</b>
            </div>

            <div class="card-body p-3">
                <div class="table-responsive">
                    <table class="table table-bordered table-sm">
                        <thead class="table-dark">
                            <tr>
                                <th style="width:60px;">Hour</th>
                                <?php for($d=1;$d<=6;$d++): ?>
                                    <th>D<?php echo $d; ?></th>
                                <?php endfor; ?>
                            </tr>
                        </thead>

                        <?php for($h=1;$h<=5;$h++): ?>
                        <tr>
                            <td class="table-info text-center fw-bold">H<?php echo $h; ?></td>

                            <?php for($d=1;$d<=6;$d++): 
                                $sub   = isset($data[$key][$d][$h]['subject']) ? $data[$key][$d][$h]['subject'] : '';
                                $staff = isset($data[$key][$d][$h]['staff']) ? $data[$key][$d][$h]['staff'] : '';
                            ?>
                                <td class="<?php echo ($sub && $staff) ? 'saved-cell' : ''; ?>">
                                    <input 
                                        type="text"
                                        name="<?php echo $key; ?>_D<?php echo $d; ?>_H<?php echo $h; ?>_sub"
                                        class="form-control input-field"
                                        placeholder="Subject"
                                        value="<?php echo htmlspecialchars($sub); ?>">

                                    <input 
                                        type="text"
                                        name="<?php echo $key; ?>_D<?php echo $d; ?>_H<?php echo $h; ?>_staff"
                                        class="form-control input-field"
                                        placeholder="SM/AA/RP"
                                        value="<?php echo htmlspecialchars($staff); ?>">
                                </td>
                            <?php endfor; ?>

                        </tr>
                        <?php endfor; ?>

                    </table>
                </div>
            </div>
        </div>
        <?php endforeach; ?>

        <div class="text-center py-5">
            <button type="submit" name="save_only" class="btn btn-success btn-lg px-5 me-4 animate__animated">
                💾 SAVE TIMETABLE
            </button>

            <button type="submit" name="save_pdf" class="btn btn-danger btn-lg px-5 animate__animated">
                💾 SAVE + 📄 PRINT PDF
            </button>
        </div>
    </form>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
    // Add floating animation to cards
    document.querySelectorAll('.card').forEach((card, index) => {
        card.style.animationDelay = `${index * 0.1}s`;
    });
</script>
</body>
</html>
