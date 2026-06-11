<?php
include "db.php";
session_start();

if(!isset($_SESSION['id'])){
    echo json_encode(['error' => 'Unauthorized']);
    exit();
}

$student_id = $_SESSION['id'];
$conn = $connection ?? $conn; // កែតាម db.php របស់អ្នក

// GPA
$gpa_query = "SELECT AVG(sc.score) as gpa, SUM(c.credits) as total_credits 
              FROM scores sc 
              LEFT JOIN subjects s ON sc.subject_id = s.id
              LEFT JOIN courses c ON s.course_id = c.id
              WHERE sc.student_id = '$student_id'";
$gpa_res = mysqli_fetch_assoc(mysqli_query($conn, $gpa_query));

// Courses count
$courses_query = "SELECT COUNT(*) as count FROM enrollments WHERE student_id = '$student_id' AND status = 'active'";
$courses_res = mysqli_fetch_assoc(mysqli_query($conn, $courses_query));

// Subject scores for chart
$scores_query = "SELECT s.subject_name, sc.score 
                 FROM scores sc 
                 JOIN subjects s ON sc.subject_id = s.id 
                 WHERE sc.student_id = '$student_id' 
                 ORDER BY sc.exam_date DESC LIMIT 6";
$scores = mysqli_query($conn, $scores_query);
$subject_names = [];
$subject_scores = [];
if($scores){
    while($row = mysqli_fetch_assoc($scores)){
        $subject_names[] = $row['subject_name'];
        $subject_scores[] = $row['score'];
    }
}

// Attendance trend
$trend_query = "SELECT DATE_FORMAT(att_date, '%M') as month, 
                AVG(CASE WHEN status = 'present' THEN 100 ELSE 0 END) as rate
                FROM attendance WHERE student_id = '$student_id' 
                AND att_date > DATE_SUB(CURRENT_DATE, INTERVAL 6 MONTH)
                GROUP BY MONTH(att_date) ORDER BY att_date";
$trend = mysqli_query($conn, $trend_query);
$months = [];
$rates = [];
if($trend){
    while($row = mysqli_fetch_assoc($trend)){
        $months[] = $row['month'];
        $rates[] = round($row['rate']);
    }
}

echo json_encode([
    'gpa' => round($gpa_res['gpa'] ?? 0, 2),
    'total_credits' => $gpa_res['total_credits'] ?? 0,
    'courses_count' => $courses_res['count'] ?? 0,
    'subject_names' => $subject_names,
    'subject_scores' => $subject_scores,
    'attendance_months' => $months,
    'attendance_rates' => $rates
]);
?>