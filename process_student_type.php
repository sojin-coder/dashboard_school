<?php
include 'db.php';
error_reporting(E_ALL);
ini_set('display_errors', 1);

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Get form data
    $student_id = mysqli_real_escape_string($conn, $_POST['student_id']);
    $student_type = mysqli_real_escape_string($conn, $_POST['student_type']);
    $date = mysqli_real_escape_string($conn, $_POST['date']);
    
    // Validate data
    if (empty($student_id) || empty($student_type)) {
        header("Location: stutype.php?error=Please fill in all required fields");
        exit();
    }
    
    // Insert query
    $sql = "INSERT INTO student_type (student_id, student_type, date) 
            VALUES ('$student_id', '$student_type', '$date')";
    
    if (mysqli_query($conn, $sql)) {
        // Success - redirect back to stutype.php
        header("Location: stutype.php?success=Student type added successfully");
    } else {
        // Error - show error message
        echo "Error: " . mysqli_error($conn);
        // header("Location: stutype.php?error=" . urlencode(mysqli_error($conn)));
    }
} else {
    // If not POST request, redirect back
    header("Location: stutype.php");
}
?>