<?php
include "config.php";

$name = $_POST['name'];
$email = $_POST['email'];
$phone = $_POST['phone'];
$gender = $_POST['gender'];
$positive = $_POST['positive'];
$salary = $_POST['salary'];
$hire_date = $_POST['hire_date'];
$address = $_POST['address'];
$status = $_POST['status'];




$sql = "INSERT INTO employees(name,email,phone,gender,positive,salary,hire_date,address,status)
VALUES ('$name','$email','$phone','$gender','$positive','$salary','$hire_date','$address','$status')";

if (mysqli_query($conn, $sql)) {
    header("Location: Employees.php");
} else {
    echo "Error: " . mysqli_error($conn);
}
?>