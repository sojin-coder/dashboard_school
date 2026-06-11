<?php
include 'db.php';
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Check if form was submitted
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    
    // Get form data and sanitize
    $student_id = mysqli_real_escape_string($conn, trim($_POST['student_id']));
    $amount = mysqli_real_escape_string($conn, trim($_POST['amount']));
    $payment_date = mysqli_real_escape_string($conn, trim($_POST['payment_date']));
    $month = mysqli_real_escape_string($conn, trim($_POST['month']));
    $status = mysqli_real_escape_string($conn, trim($_POST['status']));
    $created_at = mysqli_real_escape_string($conn, trim($_POST['created_at']));
    
    // Validation - Check if all required fields are filled
    $errors = array();
    
    if (empty($student_id)) {
        $errors[] = "Student ID is required";
    }
    
    if (empty($amount)) {
        $errors[] = "Amount is required";
    } elseif (!is_numeric($amount) || $amount <= 0) {
        $errors[] = "Amount must be a positive number";
    }
    
    if (empty($payment_date)) {
        $errors[] = "Payment date is required";
    }
    
    if (empty($month)) {
        $errors[] = "Month is required";
    }
    
    if (empty($status)) {
        $errors[] = "Status is required";
    }
    
    if (empty($created_at)) {
        $created_at = date('Y-m-d H:i:s'); // Set current datetime if not provided
    }
    
    // If there are errors, redirect back with error message
    if (!empty($errors)) {
        $error_message = urlencode(implode(", ", $errors));
        header("Location: student_payments.php?error=" . $error_message);
        exit();
    }
    
   
    
    // Insert data into database
    $insert_sql = "INSERT INTO student_payments (student_id, amount, payment_date, month, status, created_at) 
                   VALUES ('$student_id', '$amount', '$payment_date', '$month', '$status', '$created_at')";
    
    if (mysqli_query($conn, $insert_sql)) {
        // Success - redirect with success message
        header("Location: student_payments.php?success=Payment record added successfully");
        exit();
    } else {
        // Error - redirect with error message
        $error_message = "Database Error: " . mysqli_error($conn);
        header("Location: student_payments.php?error=" . urlencode($error_message));
        exit();
    }
    
} else {
    // If not POST request, redirect back to main page
    header("Location: student_payments.php");
    exit();
}
?>