<?php
include 'db.php';
error_reporting(E_ALL);
ini_set('display_errors', 1);

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Get form data
    $student_id = mysqli_real_escape_string($conn, $_POST['student_id']);
    $status = mysqli_real_escape_string($conn, $_POST['status']);
    $created_at = mysqli_real_escape_string($conn, $_POST['created_at']);
    
    // Validate data
    if (empty($student_id) || empty($status) || empty($created_at)) {
        header("Location: stuviwe.php?error=Please fill in all required fields");
        exit();
    }
    
    // Insert query
    $sql = "INSERT INTO datastu (student_id, status, created_at) 
            VALUES ('$student_id', '$status', '$created_at')";
    
    if (mysqli_query($conn, $sql)) {
        // Success - redirect back to stuviwe.php
        header("Location: stuviwe.php?success=Student type added successfully");
    } else {
        // Error - show error message
        echo "Error: " . mysqli_error($conn);
        // header("Location: stutype.php?error=" . urlencode(mysqli_error($conn)));
    }
} else {
    // If not POST request, redirect back
    header("Location: stuviwe.php");
}
?>