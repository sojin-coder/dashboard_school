<?php
include 'db.php';
header('Content-Type: application/json');

$teacher_id = isset($_GET['teacher_id']) ? mysqli_real_escape_string($conn, $_GET['teacher_id']) : 0;

if($teacher_id > 0) {
    $query = "SELECT * FROM teacher_classes WHERE teacher_id = '$teacher_id' ORDER BY class_name";
    $result = mysqli_query($conn, $query);
    
    $classes = [];
    while($row = mysqli_fetch_assoc($result)) {
        $classes[] = $row;
    }
    
    echo json_encode(['success' => true, 'classes' => $classes]);
} else {
    echo json_encode(['success' => false, 'message' => 'Invalid teacher ID']);
}
?>