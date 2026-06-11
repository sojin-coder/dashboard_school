<?php
// processatt.php - Fixed for table 'attendantit'
session_start();
error_reporting(E_ALL);
ini_set('display_errors', 1);

include 'db.php';

// Check if form was submitted
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    
    // Debug: Print received data
    error_log("=== PROCESSATT.PHP RECEIVED DATA ===");
    error_log(print_r($_POST, true));
    
    // Get form data
    $student_id = trim($_POST['student_id']);
    $attendance_date = trim($_POST['attendance_date']);
    $subject = trim($_POST['subject']);
    $year = trim($_POST['year']);
    $shift = trim($_POST['shift']);
    $status = trim($_POST['status']);
    $note = isset($_POST['note']) ? trim($_POST['note']) : '';
    
    $errors = [];
    
    // === VALIDATION ===
    if (empty($student_id)) {
        $errors[] = "Student ID is required";
    } elseif (!is_numeric($student_id) || $student_id <= 0) {
        $errors[] = "Student ID must be a positive number";
    }
    
    if (empty($attendance_date)) {
        $errors[] = "Attendance date is required";
    } else {
        $date_obj = DateTime::createFromFormat('Y-m-d', $attendance_date);
        if (!$date_obj || $date_obj->format('Y-m-d') !== $attendance_date) {
            $errors[] = "Invalid date format. Please use YYYY-MM-DD";
        }
    }
    
    $valid_subjects = ['Electronics'];
    if (empty($subject)) {
        $errors[] = "Subject is required";
    } elseif (!in_array($subject, $valid_subjects)) {
        $errors[] = "Invalid subject value";
    }
    
    $valid_years = ['1', '2', '3', '4'];
    if (empty($year)) {
        $errors[] = "Year is required";
    } elseif (!in_array($year, $valid_years)) {
        $errors[] = "Year must be 1, 2, 3, or 4 (received: $year)";
    }
    
    $valid_shifts = ['morning', 'evening'];
    if (empty($shift)) {
        $errors[] = "Shift is required";
    } elseif (!in_array($shift, $valid_shifts)) {
        $errors[] = "Invalid shift value (received: $shift)";
    }
    
    $valid_statuses = ['Active', 'A', 'P'];
    if (empty($status)) {
        $errors[] = "Status is required";
    } elseif (!in_array($status, $valid_statuses)) {
        $errors[] = "Invalid status value: $status";
    }
    
    if (strlen($note) > 500) {
        $errors[] = "Note cannot exceed 500 characters";
    }
    
    // === CHECK FOR DUPLICATE (using correct table name: attendantit) ===
    if (empty($errors)) {
        $check_sql = "SELECT id FROM attendantelectronic
                      WHERE student_id = ? 
                      AND attendance_date = ? 
                      AND subject = ?
                      AND year = ?
                      AND shift = ?";
        
        $check_stmt = mysqli_prepare($conn, $check_sql);
        if ($check_stmt) {
            mysqli_stmt_bind_param($check_stmt, "issss", $student_id, $attendance_date, $subject, $year, $shift);
            mysqli_stmt_execute($check_stmt);
            mysqli_stmt_store_result($check_stmt);
            
            if (mysqli_stmt_num_rows($check_stmt) > 0) {
                $errors[] = "Attendance record already exists for Student ID: $student_id on $attendance_date";
            }
            mysqli_stmt_close($check_stmt);
        }
    }
    
    // === IF ERRORS ===
    if (!empty($errors)) {
        $_SESSION['attendance_errors'] = $errors;
        $_SESSION['attendance_form_data'] = $_POST;
        header("Location: attElectronics.php");
        exit();
    }
    
    // === INSERT RECORD (using correct table name: attendantit) ===
    $sql = "INSERT INTO attendantelectronic (student_id, attendance_date, subject, year, shift, status, note) 
            VALUES (?, ?, ?, ?, ?, ?, ?)";
    
    $stmt = mysqli_prepare($conn, $sql);
    
    if ($stmt === false) {
        $_SESSION['attendance_errors'] = ["Database prepare error: " . mysqli_error($conn)];
        $_SESSION['attendance_form_data'] = $_POST;
        header("Location: attit.php");
        exit();
    }
    
    mysqli_stmt_bind_param($stmt, "issssss", 
        $student_id, 
        $attendance_date, 
        $subject, 
        $year, 
        $shift, 
        $status, 
        $note
    );
    
    if (mysqli_stmt_execute($stmt)) {
        $record_id = mysqli_stmt_insert_id($stmt);
        error_log("SUCCESS: Inserted record ID: $record_id into attendantelectronic");
        $_SESSION['attendance_success'] = "✅ Attendance record added successfully for Student ID: $student_id";
    } else {
        $error_msg = "Database error: " . mysqli_stmt_error($stmt);
        error_log($error_msg);
        $_SESSION['attendance_errors'] = [$error_msg];
        $_SESSION['attendance_form_data'] = $_POST;
        mysqli_stmt_close($stmt);
        mysqli_close($conn);
        header("Location: attElectronics.php");
        exit();
    }
    
    mysqli_stmt_close($stmt);
    mysqli_close($conn);
    
    header("Location: attElectronics.php");
    exit();
    
} else {
    header("Location: attElectronics.php");
    exit();
}
?>