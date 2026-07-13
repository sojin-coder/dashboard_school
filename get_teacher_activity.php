<?php
include 'db.php';
header('Content-Type: application/json');

$teacher_id = isset($_GET['teacher_id']) ? mysqli_real_escape_string($conn, $_GET['teacher_id']) : 0;

if($teacher_id > 0) {
    $query = "SELECT * FROM teaching_log WHERE teacher_id = '$teacher_id' ORDER BY teaching_date DESC, created_at DESC";
    $result = mysqli_query($conn, $query);
    
    $activities = [];
    while($row = mysqli_fetch_assoc($result)) {
        $activities[] = $row;
    }
    
    echo json_encode([
        'success' => true,
        'activities' => $activities,
        'total_sessions' => count($activities)
    ]);
} else {
    echo json_encode(['success' => false, 'message' => 'Invalid teacher ID']);
}
?>