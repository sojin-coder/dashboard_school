<?php
include 'db.php';

if(isset($_GET['id'])) {
    $id = intval($_GET['id']);
    $sql = "DELETE FROM schedule WHERE id = $id";
    
    if(mysqli_query($conn, $sql)) {
        header("Location: schedule.php?success=Schedule deleted successfully");
    } else {
        header("Location: schedule.php?error=" . mysqli_error($conn));
    }
} else {
    header("Location: schedule.php");
}
?>