<?php
include "config.php";

$employee_id = $_POST['employee_id'];
$schedule_id = $_POST['schedule_id'];
$attendance_date = $_POST['attendance_date'];
$check_in = $_POST['check_in'];
$check_out = $_POST['check_out'];
$status = $_POST['status'];
$note = $_POST['note'];



$sql = "INSERT INTO attendance(employee_id,schedule_id,attendance_date,check_in,check_out,status,note)
VALUES ('$employee_id','$schedule_id','$attendance_date','$check_in','$check_out','$status','$note')";

if (mysqli_query($conn, $sql)) {
    header("Location: Attendance.php");
} else {
    echo "Error: " . mysqli_error($conn);
}
?>