<?php include '../config/db_connection.php'; ?>
<!DOCTYPE html>
<html>
<head>
    <title>Department Timetable - Print</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { 
            font-family: Arial, sans-serif; 
            font-size: 12px; 
            margin: 15px; 
            background: white;
            color: #333;
        }
        .header { 
            text-align: center; 
            margin-bottom: 30px; 
            padding-bottom: 20px;
            border-bottom: 4px solid #2E7D32;
        }
        .timetable { 
            width: 100%; 
            border-collapse: collapse; 
            margin-bottom: 30px; 
            page-break-inside: avoid;
        }
        th, td { 
            border: 2px solid #333; 
            padding: 8px 4px; 
            text-align: center; 
            font-size: 11px; 
            height: 50px;
            vertical-align: middle;
        }
        th { 
            background: #4CAF50 !important; 
            color: white !important; 
            font-weight: bold; 
            font-size: 12px;
        }
        .hour-col { 
            background: #2196F3 !important; 
            color: white !important; 
            width: 60px; 
            font-weight: bold;
        }
        .class-header { 
            background: #FF9800 !important; 
            color: white !important; 
            font-size: 14px; 
            text-align: center;
        }
        .free-slot { 
            background: #ffebee !important; 
            color: #d32f2f !important; 
            font-style: italic;
        }
        @page { 
            margin: 10mm; 
            size: A4 landscape; 
        }
        @media print { 
            body { -webkit-print-color-adjust: exact !important; print-color-adjust: exact !important; }
            button, .no-print { display: none !important; }
        }
        .print-btn { 
            padding: 15px 35px; 
            background: #FF5722; 
            color: white; 
            border: none; 
            font-size: 18px; 
            border-radius: 8px; 
            cursor: pointer; 
            margin: 20px auto;
            display: block;
        }
        .print-btn:hover { background: #E64A19; }
    </style>
</head>
<body>
    <div class="header">
        <h1 style="color: #2E7D32; font-size: 28px; margin-bottom: 10px;">ALAGAPPA GOVERNMENT ARTS COLLEGE</h1>
        <h2 style="color: #1976D2; font-size: 22px; margin-bottom: 5px;">DEPARTMENT TIMETABLE</h2>
        <p style="font-size: 15px; color: #666;"><?php echo date('d F Y, H:i A'); ?></p>
        <button class="print-btn no-print" onclick="window.print()">🖨️ PRINT / SAVE AS PDF</button>
    </div>

<?php
$classes = array('I Year', 'II Year', 'III Year', 'I MSc', 'II MSc');
$days = array('D1','D2','D3','D4','D5','D6');

foreach($classes as $cls) {
    echo "<table class='timetable'>";
    echo "<tr><th colspan='7' class='class-header'>" . htmlspecialchars($cls) . " - Weekly Schedule (D1-D6)</th></tr>";
    echo "<tr>";
    echo "<th class='hour-col'>Hour</th>";
    foreach($days as $day) echo "<th>" . $day . "</th>";
    echo "</tr>";
    
    for($h=1; $h<=5; $h++) {
        echo "<tr>";
        echo "<td class='hour-col'>H{$h}</td>";
        for($d=1; $d<=6; $d++) {
            $stmt = $pdo->prepare("SELECT subject, staff_initial FROM manual_timetable WHERE class_name=? AND day_num=? AND hour_num=? LIMIT 1");
            $stmt->execute(array($cls, $d, $h));
            $slot = $stmt->fetch(PDO::FETCH_ASSOC);
            $content = $slot ? $slot['subject'].'<br><small style="font-size:10px;">('.$slot['staff_initial'].')</small>' : 'FREE';
            $cell_class = ($content == 'FREE') ? 'free-slot' : '';
            echo "<td class='$cell_class'>" . $content . "</td>";
        }
        echo "</tr>";
    }
    echo "</table>";
}
?>

</body>
</html>







