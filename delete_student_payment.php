<?php
include 'db.php';
error_reporting(E_ALL);
ini_set('display_errors', 1);

if (isset($_GET['id'])) {
    $id = intval($_GET['id']);
    
    $delete_sql = "DELETE FROM student_payments WHERE id = $id";
    
    if (mysqli_query($conn, $delete_sql)) {
        header("Location: student_payments.php?success=Payment record deleted successfully");
    } else {
        header("Location: student_payments.php?error=" . urlencode(mysqli_error($conn)));
    }
} else {
    header("Location: student_payments.php");
}
exit();
?>