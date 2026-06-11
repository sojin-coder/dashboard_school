<?php
// delete_student_type.php
include 'db.php';
session_start();

if (isset($_GET['id'])) {
    $id = intval($_GET['id']);
    
    // Use prepared statement for security
    $sql = "DELETE FROM student_type WHERE id = ?";
    $stmt = mysqli_prepare($conn, $sql);
    mysqli_stmt_bind_param($stmt, "i", $id);
    
    if (mysqli_stmt_execute($stmt)) {
        $_SESSION['success_message'] = "Record deleted successfully!";
    } else {
        $_SESSION['error_messages'] = array("Error deleting record: " . mysqli_stmt_error($stmt));
    }
    
    mysqli_stmt_close($stmt);
} else {
    $_SESSION['error_messages'] = array("No ID specified for deletion");
}

header("Location: student_type.php");
exit();
?>