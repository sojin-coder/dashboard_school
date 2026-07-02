<?php
// ============================================
// student_auth.php - Authentication for Student Pages
// ដាក់ include នេះនៅដើម page ទាំងអស់របស់ Student
// ============================================

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    // session_start();
}

include "db.php";

// Database connection
$db_conn = $conn ?? $connection;

// Check if user is logged in
if (!isset($_SESSION['id'])) {
    header("Location: login.php");
    exit();
}

// Check if user has student role
if (isset($_SESSION['role']) && $_SESSION['role'] != 'student') {
    // If role is not student, redirect to appropriate dashboard
    if ($_SESSION['role'] == 'teacher') {
        header("Location: forteacher.php");
    } else {
        header("Location: index.php");
    }
    exit();
}

// ============================================
// Get student info from session
// ============================================
$logged_in_email = $_SESSION['email'] ?? '';
$logged_in_user_id = (int)$_SESSION['id'];

// ============================================
// Try to find student by email first
// ============================================
if (!empty($logged_in_email)) {
    $sql_student_info = "SELECT * FROM students WHERE email = '$logged_in_email'";
    $result_student_info = mysqli_query($db_conn, $sql_student_info);
    $student_info = mysqli_fetch_assoc($result_student_info);
}

// ============================================
// If not found by email, try by name
// ============================================
if (empty($student_info)) {
    $logged_in_name = $_SESSION['name'] ?? '';
    if (!empty($logged_in_name)) {
        $sql_student_info = "SELECT * FROM students WHERE name = '$logged_in_name'";
        $result_student_info = mysqli_query($db_conn, $sql_student_info);
        $student_info = mysqli_fetch_assoc($result_student_info);
    }
}

// ============================================
// If still not found, try by ID
// ============================================
if (empty($student_info) && $logged_in_user_id > 0) {
    $sql_student_info = "SELECT * FROM students WHERE id = '$logged_in_user_id'";
    $result_student_info = mysqli_query($db_conn, $sql_student_info);
    $student_info = mysqli_fetch_assoc($result_student_info);
}

// ============================================
// If student not found, show error
// ============================================
if (!$student_info) {
    // Show debug information
    echo "<div style='padding:30px; background:#f8d7da; color:#721c24; border-radius:10px; margin:20px; font-family:Arial;'>
        <h3>⚠️ Student Not Found!</h3>
        <p><strong>Email from Session:</strong> " . htmlspecialchars($logged_in_email) . "</p>
        <p><strong>Name from Session:</strong> " . htmlspecialchars($_SESSION['name'] ?? 'N/A') . "</p>
        <p><strong>ID from Session:</strong> " . htmlspecialchars($logged_in_user_id) . "</p>
        <p><strong>Role:</strong> " . htmlspecialchars($_SESSION['role'] ?? 'N/A') . "</p>
        <hr>
        <p><strong>Available students in database:</strong></p>
        <ul>";
    
    $all_students = mysqli_query($db_conn, "SELECT id, name, email FROM students LIMIT 20");
    if (mysqli_num_rows($all_students) > 0) {
        while ($t = mysqli_fetch_assoc($all_students)) {
            echo "<li>ID: {$t['id']} - {$t['name']} ({$t['email']})</li>";
        }
    } else {
        echo "<li>No students found in database!</li>";
    }
    
    echo "</ul>
        <p><strong>Session Data:</strong></p>
        <pre style='background:#fff; padding:10px; border-radius:5px;'>";
    print_r($_SESSION);
    echo "</pre>
        <a href='logout.php' style='display:inline-block; padding:10px 20px; background:#dc3545; color:white; text-decoration:none; border-radius:5px; margin-top:10px;'>Logout</a>
    </div>";
    exit();
}

// ============================================
// Get all student data
// ============================================
$logged_in_student = $student_info['name'];
$logged_in_id = (int)$student_info['id'];
$student_department = $student_info['college'] ?? 'N/A';
$student_grade = $student_info['grade'] ?? 'N/A';
$student_phone = $student_info['phone'] ?? '';
$student_email_db = $student_info['email'] ?? '';
$student_gender = $student_info['gender'] ?? '';
$student_dob = $student_info['dob'] ?? '';
$student_address = $student_info['address'] ?? '';
$student_shift = $student_info['Shift'] ?? 'N/A';
$student_year = $student_info['year'] ?? 'N/A';
$student_skill = $student_info['skill'] ?? 'N/A';
$student_image = $student_info['image'] ?? '';

// ============================================
// Update session with latest student info
// ============================================
$_SESSION['id'] = $logged_in_id;
$_SESSION['name'] = $logged_in_student;
$_SESSION['email'] = $student_email_db;
$_SESSION['role'] = 'student';

// ============================================
// Function to get student data by ID (for search)
// ============================================
function getStudentById($db_conn, $student_id) {
    $query = "SELECT * FROM students WHERE id = '$student_id'";
    $result = mysqli_query($db_conn, $query);
    return mysqli_fetch_assoc($result);
}

// ============================================
// Function to sanitize output
// ============================================
function sanitize($data) {
    return htmlspecialchars($data ?? 'N/A', ENT_QUOTES, 'UTF-8');
}

// ============================================
// Student Information Array (for easy access)
// ============================================
$STUDENT = [
    'id' => $logged_in_id,
    'name' => $logged_in_student,
    'email' => $student_email_db,
    'phone' => $student_phone,
    'gender' => $student_gender,
    'dob' => $student_dob,
    'address' => $student_address,
    'college' => $student_department,
    'grade' => $student_grade,
    'shift' => $student_shift,
    'year' => $student_year,
    'skill' => $student_skill,
    'image' => $student_image
];
?>