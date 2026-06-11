<?php
include "config.php";

$name = $_POST['name'];
$email = $_POST['email'];
$image = $_POST['image'];
$phone = $_POST['phone'];
$gender = $_POST['gender'];
$subject = $_POST['subject'];
$address = $_POST['address'];
$created_at = $_POST['created_at'];



$sql = "INSERT INTO teachers(name,email,image,phone, gender,subject,  address, created_at)
VALUES ('$name','$email','$image','$phone','$gender','$subject','$address','$created_at')";

if (mysqli_query($conn, $sql)) {
    header("Location: teacher.php");
} else {
    echo "Error: " . mysqli_error($conn);
}
?>