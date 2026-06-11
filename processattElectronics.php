<?php
// processatt.php - Enhanced version with session messages
session_start();
include 'db.php';

// Check if form was submitted
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    
    // Get form data and sanitize inputs
    $student_id = mysqli_real_escape_string($conn, $_POST['student_id']);
    $attendance_date = mysqli_real_escape_string($conn, $_POST['attendance_date']);
    $subject = mysqli_real_escape_string($conn, $_POST['subject']);
    $status = mysqli_real_escape_string($conn, $_POST['status']);
    $note = isset($_POST['note']) ? mysqli_real_escape_string($conn, $_POST['note']) : '';
    
    // Validate required fields
    $errors = [];
    $success = false;
    
    // Validation
    if (empty($student_id)) {
        $errors[] = "Student ID is required";
    } elseif (!is_numeric($student_id)) {
        $errors[] = "Student ID must be a number";
    }
    
    if (empty($attendance_date)) {
        $errors[] = "Attendance date is required";
    }
    
    if (empty($subject)) {
        $errors[] = "Subject is required";
    }
    
    if (empty($status)) {
        $errors[] = "Status is required";
    }
    
    // Validate status value
    $valid_statuses = ['Active', 'A', 'P'];
    if (!in_array($status, $valid_statuses)) {
        $errors[] = "Invalid status value";
    }
    
    // Validate subject
    $valid_subjects = ['it'];
    if (!in_array($subject, $valid_subjects)) {
        $errors[] = "Invalid subject value";
    }
    
    // Check for duplicate attendance record
    $check_duplicate = "SELECT id FROM attendantElectronics
                        WHERE student_id = '$student_id' 
                        AND attendance_date = '$attendance_date' 
                        AND subject = '$subject'";
    $result_duplicate = mysqli_query($conn, $check_duplicate);
    
    if ($result_duplicate && mysqli_num_rows($result_duplicate) > 0) {
        $errors[] = "Attendance record already exists for Student ID: $student_id on $attendance_date for subject: $subject";
    }
    
    // If there are errors, store in session and redirect
    if (!empty($errors)) {
        $_SESSION['attendance_errors'] = $errors;
        $_SESSION['attendance_form_data'] = $_POST;
        header("Location: attElectronics.php");
        exit();
    }
    
    // Insert attendance record
    $sql = "INSERT INTO attendantElectronics (student_id, attendance_date, subject, status, note) 
            VALUES ('$student_id', '$attendance_date', '$subject', '$status', '$note')";
    
    if (mysqli_query($conn, $sql)) {
        $record_id = mysqli_insert_id($conn);
        $_SESSION['attendance_success'] = "Attendance record added successfully for Student ID: $student_id";
        header("Location: attElectronics.php");
        exit();
    } else {
        $_SESSION['attendance_errors'] = ["Database error: " . mysqli_error($conn)];
        $_SESSION['attendance_form_data'] = $_POST;
        header("Location: attElectronics.php");
        exit();
    }
    
} else {
    // If not POST request, redirect back to attendance page
    header("Location: attElectronics.php");
    exit();
}

mysqli_close($conn);
?>