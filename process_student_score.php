<?php
include "config.php";

$name = $_POST['name'];
$subject = $_POST['subject'];
$score = $_POST['score'];
$Grade = $_POST['Grade'];



$sql = "INSERT INTO scores(name,subject,Grade,score)
VALUES ('$name','$subject','$Grade','$score')";

if (mysqli_query($conn, $sql)) {
    header("Location: score.php");
} else {
    echo "Error: " . mysqli_error($conn);
}
?>