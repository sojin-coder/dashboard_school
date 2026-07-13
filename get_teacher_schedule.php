<?php
include 'db.php';
header('Content-Type: application/json');

$teacher_id = isset($_GET['teacher_id']) ? mysqli_real_escape_string($conn, $_GET['teacher_id']) : 0;

if($teacher_id > 0) {
    // ទាញឈ្មោះគ្រូ
    $teacher_query = "SELECT name FROM teachers WHERE id = '$teacher_id'";
    $teacher_result = mysqli_query($conn, $teacher_query);
    $teacher = mysqli_fetch_assoc($teacher_result);
    
    if($teacher) {
        $teacher_name = $teacher['name'];
        
        // ទាញទិន្នន័យពី schedule_class តាមឈ្មោះគ្រូ រួមទាំង Date
        $query = "SELECT 
                    id,
                    subject,
                    class,
                    time_star,
                    time_end,
                    date,
                    department,
                    shift,
                    year,
                    semester,
                    teacher_name
                  FROM schedule_class 
                  WHERE teacher_name = '$teacher_name' 
                  ORDER BY date DESC, time_star ASC";
        $result = mysqli_query($conn, $query);
        
        $schedule = [];
        while($row = mysqli_fetch_assoc($result)) {
            // Format date ឱ្យស្អាត
            $row['date_formatted'] = date('d-m-Y', strtotime($row['date']));
            $row['day_of_week'] = date('l', strtotime($row['date']));
            $schedule[] = $row;
        }
        
        echo json_encode(['success' => true, 'schedule' => $schedule]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Teacher not found']);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Invalid teacher ID']);
}
?>