<?php
include 'db.php';
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Check if form was submitted
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    
    // Get form data and sanitize
    $courses_id = mysqli_real_escape_string($conn, $_POST['courses_id']);
    $Grade_type = mysqli_real_escape_string($conn, $_POST['Grade_type']);
    $price = mysqli_real_escape_string($conn, $_POST['price']);
    
    // Validate required fields
    $errors = array();
    
    if (empty($courses_id)) {
        $errors[] = "Course ID is required";
    }
    
    if (empty($Grade_type)) {
        $errors[] = "Grade type is required";
    }
    
    if (empty($price) || !is_numeric($price) || $price < 0) {
        $errors[] = "Valid price is required";
    }
    
    // If no errors, insert into database
    if (empty($errors)) {
        // Insert query
        $sql = "INSERT INTO grade (courses_id, Grade_type, price) 
                VALUES ('$courses_id', '$Grade_type', '$price')";
        
        if (mysqli_query($conn, $sql)) {
            // Success - redirect back to grade page with success message
            $last_id = mysqli_insert_id($conn);
            $_SESSION['success_message'] = "Grade record added successfully! ID: " . $last_id;
            header("Location: grade.php?success=1");
            exit();
        } else {
            // Database error
            $_SESSION['error_message'] = "Database error: " . mysqli_error($conn);
            header("Location: grade.php?error=1");
            exit();
        }
    } else {
        // Validation errors - store in session and redirect back
        $_SESSION['errors'] = $errors;
        $_SESSION['form_data'] = $_POST;
        header("Location: grade.php?error=1");
        exit();
    }
    
} else {
    // Not a POST request - redirect back
    header("Location: grade.php");
    exit();
}

// Close connection
mysqli_close($conn);
?>