<?php
include "config.php";

$student_id = $_POST['student_id'];
$student_name = $_POST['student_name'];
$course_id = $_POST['course_id'];
$course_name = $_POST['course_name'];
$score = $_POST['score'];
$grade = $_POST['grade'];
$remark = $_POST['remark'];
$created_at = $_POST['created_at'];


$sql = "INSERT INTO Statistics(student_id,student_name,course_id,course_name,score,grade,remark,created_at)
VALUES ('$student_id','$student_name','$course_id','$course_name','$score','$grade','$remark','$created_at')";

if (mysqli_query($conn, $sql)) {
    header("Location: Grades.php");
} else {
    echo "Error: " . mysqli_error($conn);
}
?>