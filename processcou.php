<?php
include 'db.php';

$name = $_POST['name'];
$time_star = $_POST['time_star'];
$time_end = $_POST['time_end'];
$description = $_POST['description'];
$teacher_id = $_POST['teacher_id'];
$teacher_name = $_POST['teacher_name'];
$teacher_phone = $_POST['teacher_phone'];
$price = $_POST['price'];
$shift = $_POST['Shift'];
$duration = $_POST['duration'];

$sql = "INSERT INTO courses (name, time_star, time_end, description, teacher_id, teacher_name, teacher_phone, price, Shift, duration) 
        VALUES ('$name', '$time_star', '$time_end', '$description', '$teacher_id', '$teacher_name', '$teacher_phone', '$price', '$shift', '$duration')";

if(mysqli_query($conn, $sql)) {
    header("Location: Courses.php?success=Course added successfully");
} else {
    header("Location: Courses.php?error=" . mysqli_error($conn));
}
?>