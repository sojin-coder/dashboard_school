<?php
include 'db.php';

$date = $_POST['date'];
$time_star = $_POST['time_star'];
$time_end = $_POST['time_end'];
$subject = $_POST['subject'];
$department = $_POST['department'];
$class = $_POST['class'];
$shift = $_POST['shift'];
$year = $_POST['year'];
$semester = $_POST['semester'];

$sql = "INSERT INTO schedule (date, time_star, time_end, subject, department, class, shift, year, semester) 
        VALUES ('$date', '$time_star', '$time_end', '$subject', '$department', '$class', '$shift', '$year', '$semester')";

if(mysqli_query($conn, $sql)) {
    header("Location: schedule.php?success=Schedule added successfully");
} else {
    header("Location: schedule.php?error=" . mysqli_error($conn));
}
?>