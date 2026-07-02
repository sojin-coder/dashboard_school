<?php
include 'db.php';
// process_schedule.php - Updated with Teacher Name

// 1. Enable session securely
if (session_status() === PHP_SESSION_NONE) {
    session_start([
        'cookie_httponly' => true,
        'cookie_secure' => false,
        'cookie_samesite' => 'Strict',
        'use_strict_mode' => true
    ]);
}

// 2. Include database connection


// 3. Check if form was submitted via POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: schedule.php?error=Invalid request method");
    exit;
}

// 4. Verify CSRF token
if (!isset($_POST['csrf_token']) || !isset($_SESSION['csrf_token']) || 
    !hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) {
    header("Location: schedule.php?error=Security validation failed. Please try again.");
    exit;
}

// 5. Get and validate inputs
$errors = [];

// Validate date
$date = trim($_POST['date'] ?? '');
$date_obj = DateTime::createFromFormat('Y-m-d', $date);
if (!$date_obj || $date_obj->format('Y-m-d') !== $date) {
    $errors[] = "Invalid date format";
}

// Validate time
$time_star = trim($_POST['time_star'] ?? '');
$time_end = trim($_POST['time_end'] ?? '');

if (!preg_match('/^(?:2[0-3]|[01][0-9]):[0-5][0-9]$/', $time_star)) {
    $errors[] = "Invalid start time format (HH:MM)";
}
if (!preg_match('/^(?:2[0-3]|[01][0-9]):[0-5][0-9]$/', $time_end)) {
    $errors[] = "Invalid end time format (HH:MM)";
}

// Validate time logic
if (!empty($time_star) && !empty($time_end) && $time_star >= $time_end) {
    $errors[] = "End time must be after start time";
}

// ===== SUBJECT VALIDATION =====
$subject = trim($_POST['subject'] ?? '');
$allowed_subjects = ['php', 'Java', 'Cisco', 'js', 'html/css', 'c#', 'mysql', 'c++', 'vb.net', 
                     'Calculus', 'Physics', 'Chemistry', 'Engineering Drawing', 'Computer Programming',
                     'Engineering Mechanics', 'Communication Skills', 'Principles of Marketing',
                     'Consumer Behavior', 'Digital Marketing', 'Social Media Marketing', 'Market Research',
                     'Brand Management', 'Sales Management', 'Advertising', 'Electronic Circuits',
                     'Analog Electronics', 'Digital Electronics', 'Microprocessors', 'Embedded Systems',
                     'Sensors and Instrumentation', 'Communication Systems', 'Principles of Management',
                     'Human Resource Management', 'Organizational Behavior', 'Business Strategy',
                     'Leadership', 'Operations Management', 'Entrepreneurship', 'Financial Accounting',
                     'Managerial Accounting', 'Cost Accounting', 'Auditing', 'Taxation', 'Financial Management'];

if (!in_array($subject, $allowed_subjects)) {
    $errors[] = "Invalid subject selected";
}

// ===== DEPARTMENT VALIDATION =====
$department = trim($_POST['department'] ?? '');
$allowed_departments = ['it', 'Marketing', 'Electronics', 'Management', 'Electrical Engineering', 
                        'Accounting', 'Civil Engineering'];
if (!in_array($department, $allowed_departments)) {
    $errors[] = "Invalid department selected";
}

// ===== TEACHER NAME VALIDATION =====
$teacher_name = trim($_POST['teacher_name'] ?? '');
if (empty($teacher_name)) {
    $errors[] = "Teacher name is required";
} else {
    // Sanitize teacher name
    $teacher_name = htmlspecialchars($teacher_name, ENT_QUOTES, 'UTF-8');
    // Remove any unwanted characters (allow letters, spaces, dots, hyphens)
    $teacher_name = preg_replace('/[^a-zA-Z\s\.\-]/', '', $teacher_name);
    if (strlen($teacher_name) < 2) {
        $errors[] = "Teacher name must be at least 2 characters";
    }
    if (strlen($teacher_name) > 100) {
        $errors[] = "Teacher name must be less than 100 characters";
    }
}

// ===== CLASS VALIDATION =====
$class = trim($_POST['class'] ?? '');
$class = strtolower($class);
$allowed_classes = ['com 1', 'com 2', 'com 3', 'com 4', 'com 5'];

if (!in_array($class, $allowed_classes)) {
    if (preg_match('/com\s*(\d+)/i', $class, $matches)) {
        $class_num = $matches[1];
        $class = 'com ' . $class_num;
        if (!in_array($class, $allowed_classes)) {
            $errors[] = "Invalid class selected. Please select a valid class.";
        }
    } else {
        $errors[] = "Invalid class selected. Please select a valid class.";
    }
}

// ===== SHIFT VALIDATION =====
$shift = trim($_POST['shift'] ?? '');
$allowed_shifts = ['morning', 'evening'];
if (!in_array($shift, $allowed_shifts)) {
    $errors[] = "Invalid shift selected";
}

// ===== YEAR VALIDATION =====
$year = trim($_POST['year'] ?? '');
$allowed_years = ['year1', 'year2', 'year3', 'year4'];
if (!in_array($year, $allowed_years)) {
    $errors[] = "Invalid year selected";
}

// ===== SEMESTER VALIDATION =====
$semester = trim($_POST['semester'] ?? '');
$allowed_semesters = ['1', '2'];
if (!in_array($semester, $allowed_semesters)) {
    $errors[] = "Invalid semester selected";
}

// 6. If there are errors, redirect back with error messages
if (!empty($errors)) {
    $error_message = implode(', ', $errors);
    header("Location: schedule.php?error=" . urlencode($error_message));
    exit;
}

// 7. Check for schedule conflicts
$conflict_stmt = $conn->prepare("SELECT id FROM schedule_class 
                                 WHERE date = ? 
                                 AND ((time_star <= ? AND time_end > ?) OR 
                                      (time_star < ? AND time_end >= ?) OR
                                      (time_star >= ? AND time_end <= ?))");
$conflict_stmt->bind_param("sssssss", $date, $time_end, $time_star, $time_end, $time_star, $time_star, $time_end);
$conflict_stmt->execute();
$conflict_result = $conflict_stmt->get_result();

if ($conflict_result->num_rows > 0) {
    header("Location: schedule.php?error=Schedule conflict detected! Please choose different time.");
    exit;
}

// 8. Insert data using prepared statement - INCLUDING TEACHER NAME
$sql = "INSERT INTO schedule_class (date, time_star, time_end, subject, department, teacher_name, class, shift, year, semester) 
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";

$stmt = $conn->prepare($sql);
if (!$stmt) {
    header("Location: schedule.php?error=Database error: " . urlencode($conn->error));
    exit;
}

$stmt->bind_param("ssssssssss", 
    $date, 
    $time_star, 
    $time_end, 
    $subject, 
    $department, 
    $teacher_name, 
    $class, 
    $shift, 
    $year, 
    $semester
);

// 9. Execute and check result
if ($stmt->execute()) {
    error_log("Schedule added: Date $date, Subject $subject, Teacher $teacher_name, Class $class, Year $year");
    header("Location: schedule.php?success=Schedule added successfully");
} else {
    error_log("Failed to insert schedule: " . $stmt->error);
    header("Location: schedule.php?error=" . urlencode("Failed to add schedule. Please try again."));
}

// 10. Close statement and connection
$stmt->close();
$conn->close();
exit;
?>