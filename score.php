<?php
include 'db.php';
error_reporting(E_ALL);
ini_set('display_errors', 1);

// ============================================
// Handle Search & Filter
// ============================================
$search = isset($_GET['search']) ? mysqli_real_escape_string($conn, $_GET['search']) : '';
$dept_filter = isset($_GET['department']) ? mysqli_real_escape_string($conn, $_GET['department']) : '';
$subject_filter = isset($_GET['subject']) ? mysqli_real_escape_string($conn, $_GET['subject']) : '';

// ============================================
// Get list of subjects from scores table
// ============================================
$subject_query = "SELECT DISTINCT subject FROM scores WHERE subject IS NOT NULL AND subject != '' ORDER BY subject";
$subject_result = mysqli_query($conn, $subject_query);
$subjects_from_db = [];
if ($subject_result) {
    while ($row = mysqli_fetch_assoc($subject_result)) {
        $subjects_from_db[] = $row['subject'];
    }
}

// ============================================
// Get list of all departments from scores table
// ============================================
$dept_query = "SELECT DISTINCT department FROM scores WHERE department IS NOT NULL AND department != '' ORDER BY department";
$dept_result = mysqli_query($conn, $dept_query);
$departments_from_db = [];
if ($dept_result) {
    while ($row = mysqli_fetch_assoc($dept_result)) {
        $departments_from_db[] = $row['department'];
    }
}

// ============================================
// All departments list
// ============================================
$all_departments = [
    'it' => 'IT',
    'Marketing' => 'Marketing',
    'Management' => 'Management',
    'Electrical Engineering' => 'Electrical Engineering',
    'Accounting' => 'Accounting',
    'Civil Engineering' => 'Civil Engineering',
    'Electronics' => 'Electronics'
];

// Merge departments
$departments = [];
foreach ($all_departments as $key => $value) {
    $departments[] = $key;
}
foreach ($departments_from_db as $dept) {
    if (!in_array($dept, $departments)) {
        $departments[] = $dept;
    }
}
sort($departments);

// ============================================
// Department Color Mapping
// ============================================
$dept_colors = [
    'it' => 'dept-it',
    'Marketing' => 'dept-marketing',
    'Management' => 'dept-management',
    'Electrical Engineering' => 'dept-electrical',
    'Accounting' => 'dept-accounting',
    'Civil Engineering' => 'dept-civil',
    'Electronics' => 'dept-electronics'
];

// ============================================
// Handle Bulk Score Update (Block Subject)
// ============================================
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['bulk_update'])) {
    $subject = isset($_POST['subject']) ? mysqli_real_escape_string($conn, $_POST['subject']) : '';
    $department = isset($_POST['department']) ? mysqli_real_escape_string($conn, $_POST['department']) : '';
    $type = isset($_POST['type']) ? mysqli_real_escape_string($conn, $_POST['type']) : '';
    $semester = isset($_POST['semester']) ? mysqli_real_escape_string($conn, $_POST['semester']) : '';
    $academic_year = isset($_POST['academic_year']) ? mysqli_real_escape_string($conn, $_POST['academic_year']) : '';
    $auto_grade = isset($_POST['auto_grade']) ? $_POST['auto_grade'] : '1';
    
    if (empty($subject)) {
        $error = "Please select a subject.";
    } else {
        $success_count = 0;
        $error_count = 0;
        
        // Get all students for this subject
        $student_query = "SELECT id, name, department FROM scores WHERE subject = '$subject'";
        if (!empty($department)) {
            $student_query .= " AND department = '$department'";
        }
        $student_result = mysqli_query($conn, $student_query);
        
        while ($student = mysqli_fetch_assoc($student_result)) {
            $student_id = $student['id'];
            $score_key = "score_" . $student_id;
            $grade_key = "grade_" . $student_id;
            
            if (isset($_POST[$score_key]) && $_POST[$score_key] !== '') {
                $score = floatval($_POST[$score_key]);
                
                // Get grade from form or auto-calculate
                if ($auto_grade == '1') {
                    // Auto-calculate grade
                    if ($score >= 90) $grade = 'A';
                    else if ($score >= 80) $grade = 'B';
                    else if ($score >= 70) $grade = 'C';
                    else if ($score >= 60) $grade = 'D';
                    else if ($score >= 50) $grade = 'E';
                    else $grade = 'F';
                } else {
                    // Manual grade from form
                    $grade = isset($_POST[$grade_key]) ? mysqli_real_escape_string($conn, $_POST[$grade_key]) : '';
                    if (empty($grade)) {
                        // If no grade selected, auto-calculate
                        if ($score >= 90) $grade = 'A';
                        else if ($score >= 80) $grade = 'B';
                        else if ($score >= 70) $grade = 'C';
                        else if ($score >= 60) $grade = 'D';
                        else if ($score >= 50) $grade = 'E';
                        else $grade = 'F';
                    }
                }
                
                $update_sql = "UPDATE scores SET 
                               score = '$score',
                               Grade = '$grade',
                               type = '$type',
                               semester = '$semester',
                               academic_year = '$academic_year'
                               WHERE id = $student_id";
                
                if (mysqli_query($conn, $update_sql)) {
                    $success_count++;
                } else {
                    $error_count++;
                }
            }
        }
        
        if ($success_count > 0) {
            $success = "Updated scores for $success_count student(s) successfully!";
            if ($error_count > 0) {
                $success .= " (Failed to update $error_count student(s))";
            }
        } else {
            $error = "No scores were updated. Please check your input.";
        }
    }
}

// ============================================
// Build SQL query for displaying scores
// ============================================
$sql = "SELECT * FROM scores WHERE 1=1";

if (!empty($search)) {
    $sql .= " AND (name LIKE '%$search%' 
                  OR subject LIKE '%$search%' 
                  OR score LIKE '%$search%' 
                  OR Grade LIKE '%$search%' 
                  OR student_id LIKE '%$search%'
                  OR department LIKE '%$search%'
                  OR type LIKE '%$search%'
                  OR semester LIKE '%$search%'
                  OR academic_year LIKE '%$search%')";
}

if (!empty($dept_filter)) {
    $sql .= " AND department = '$dept_filter'";
}

if (!empty($subject_filter)) {
    $sql .= " AND subject = '$subject_filter'";
}

$sql .= " ORDER BY id DESC";

$result = mysqli_query($conn, $sql);
if (!$result) {
    echo "<div class='alert alert-danger mt-3'>Error: " . mysqli_error($conn) . "</div>";
}

// ============================================
// Get students for the block subject form
// ============================================
$students_for_block = [];
if (isset($_GET['block_subject']) && !empty($_GET['block_subject'])) {
    $block_subject = mysqli_real_escape_string($conn, $_GET['block_subject']);
    $block_dept = isset($_GET['block_dept']) ? mysqli_real_escape_string($conn, $_GET['block_dept']) : '';
    
    $student_query = "SELECT * FROM scores WHERE subject = '$block_subject'";
    if (!empty($block_dept)) {
        $student_query .= " AND department = '$block_dept'";
    }
    $student_query .= " ORDER BY name";
    $student_result = mysqli_query($conn, $student_query);
    while ($row = mysqli_fetch_assoc($student_result)) {
        $students_for_block[] = $row;
    }
}

// ============================================
// Handle Single Score Update via AJAX
// ============================================
if (isset($_POST['ajax']) && $_POST['ajax'] == 'update_single_score') {
    $id = isset($_POST['id']) ? intval($_POST['id']) : 0;
    $name = isset($_POST['name']) ? mysqli_real_escape_string($conn, $_POST['name']) : '';
    $subject = isset($_POST['subject']) ? mysqli_real_escape_string($conn, $_POST['subject']) : '';
    $score = isset($_POST['score']) ? floatval($_POST['score']) : 0;
    $Grade = isset($_POST['Grade']) ? mysqli_real_escape_string($conn, $_POST['Grade']) : '';
    $department = isset($_POST['department']) ? mysqli_real_escape_string($conn, $_POST['department']) : '';
    $type = isset($_POST['type']) ? mysqli_real_escape_string($conn, $_POST['type']) : '';
    $semester = isset($_POST['semester']) ? mysqli_real_escape_string($conn, $_POST['semester']) : '';
    $academic_year = isset($_POST['academic_year']) ? mysqli_real_escape_string($conn, $_POST['academic_year']) : '';
    
    if(empty($name) || empty($subject) || $score === '') {
        echo json_encode(['success' => false, 'message' => 'Please fill all required fields']);
        exit;
    }
    
    // Auto-calculate grade if not provided
    if (empty($Grade)) {
        if ($score >= 90) $Grade = 'A';
        else if ($score >= 80) $Grade = 'B';
        else if ($score >= 70) $Grade = 'C';
        else if ($score >= 60) $Grade = 'D';
        else if ($score >= 50) $Grade = 'E';
        else $Grade = 'F';
    }
    
    $update_sql = "UPDATE scores SET 
                   name='$name',
                   subject='$subject',
                   score='$score',
                   Grade='$Grade',
                   department='$department',
                   type='$type',
                   semester='$semester',
                   academic_year='$academic_year'
                   WHERE id=$id";
    
    if(mysqli_query($conn, $update_sql)) {
        echo json_encode(['success' => true, 'message' => 'Score record updated successfully']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Error: ' . mysqli_error($conn)]);
    }
    exit;
}

// ============================================
// Handle Single Score Add
// ============================================
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['add_single_score'])) {
    $name = isset($_POST['name']) ? mysqli_real_escape_string($conn, $_POST['name']) : '';
    $subject = isset($_POST['subject']) ? mysqli_real_escape_string($conn, $_POST['subject']) : '';
    $score = isset($_POST['score']) ? floatval($_POST['score']) : 0;
    $Grade = isset($_POST['Grade']) ? mysqli_real_escape_string($conn, $_POST['Grade']) : '';
    $department = isset($_POST['department']) ? mysqli_real_escape_string($conn, $_POST['department']) : '';
    $type = isset($_POST['type']) ? mysqli_real_escape_string($conn, $_POST['type']) : '';
    $semester = isset($_POST['semester']) ? mysqli_real_escape_string($conn, $_POST['semester']) : '';
    $academic_year = isset($_POST['academic_year']) ? mysqli_real_escape_string($conn, $_POST['academic_year']) : '';
    
    if(empty($name) || empty($subject) || $score === '') {
        $error = "Please fill all required fields";
    } else {
        // Auto-calculate grade if not provided
        if (empty($Grade)) {
            if ($score >= 90) $Grade = 'A';
            else if ($score >= 80) $Grade = 'B';
            else if ($score >= 70) $Grade = 'C';
            else if ($score >= 60) $Grade = 'D';
            else if ($score >= 50) $Grade = 'E';
            else $Grade = 'F';
        }
        
        $insert_sql = "INSERT INTO scores (name, subject, score, Grade, department, type, semester, academic_year) 
                       VALUES ('$name', '$subject', '$score', '$Grade', '$department', '$type', '$semester', '$academic_year')";
        
        if(mysqli_query($conn, $insert_sql)) {
            $success = "Score record added successfully!";
            // Refresh the page to show new record
            echo "<script>window.location.href = 'score.php?success=" . urlencode($success) . "';</script>";
            exit;
        } else {
            $error = "Error: " . mysqli_error($conn);
        }
    }
}

// Handle AJAX request for getting student data
if(isset($_GET['ajax']) && $_GET['ajax'] == 'get_student' && isset($_GET['id'])) {
    $id = intval($_GET['id']);
    $query = "SELECT * FROM scores WHERE id = $id";
    $result = mysqli_query($conn, $query);
    if($row = mysqli_fetch_assoc($result)) {
        header('Content-Type: application/json');
        echo json_encode($row);
    }
    exit;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <title>KRaksa Education Suite - Block Subject Score Entry</title>
<style>
    .dropdown-container{
        margin-bottom:10px;
    }
    
    .dropdown-btn{
        display:flex;
        justify-content:space-between;
        align-items:center;
        cursor:pointer;
    }
    
    .dropdown-menus{
        display:none;
        padding-left:15px;
        margin-top:5px;
    }
    
    .sub-menu{
        font-size:14px;
        padding:10px 15px;
        margin-bottom:5px;
        background:rgba(88, 30, 248, 0.61);
    }
    
    .sub-menu:hover{
        background:rgba(110, 41, 238, 0.6);
    }
    
    .dropdown-icon{
        transition:0.3s;
    }

    .table-container {
        max-height: 500px;
        overflow-y: auto;
        border: 1px solid #dee2e6;
        border-radius: 8px;
    }

    .table-container thead th {
        position: sticky;
        top: 0;
        background-color: #f8f9fa;
        z-index: 1;
        border-bottom: 2px solid #dee2e6;
    }
    
    * { 
        margin: 0; 
        padding: 0; 
        box-sizing: border-box; 
    }
    
    a {
        text-decoration: none;
    }
    
    body { 
        font-family: "Inter", sans-serif; 
        background: #c5e1fc; 
        color: #0f172a; 
        overflow-x: hidden; 
    }
    
    .container { 
        display: flex; 
        min-height: 100vh; 
        max-width: 100%; 
    }
    
    .sidebar { 
        width: 300px; 
        background: linear-gradient(90deg,rgba(117, 82, 243, 1) 19%, rgba(64, 24, 157, 1) 95%);
        color: #e2e8f0; 
        flex-shrink: 0; 
        position: sticky; 
        top: 0; 
        height: 100vh; 
        overflow-y: auto; 
        box-shadow: 4px 0 20px rgba(0, 0, 0, 0.08); 
        z-index: 10;
        margin-left: -12px;
    }
    
    .sidebar-header { 
        padding: 28px 24px; 
        border-bottom: 1px solid rgba(255, 255, 255, 0.1); 
        margin-bottom: 24px; 
        text-align: center; 
    }
    
    .sidebar-header img { 
        width: 100px; 
        height: 100px; 
        border-radius: 50%; 
        margin: auto; 
        display: block; 
        border: 4px solid white; 
        object-fit: cover; 
    }
    
    .sidebar-header h1 { 
        font-size: 1.8rem; 
        font-weight: 700; 
        color: white; 
        margin-top: 10px; 
    }
    
    .sidebar-header p { 
        font-size: 0.85rem; 
        opacity: 0.8; 
    }
    
    .nav-menu { 
        flex: 1; 
        padding: 0 16px; 
    }
    
    .nav-item { 
        display: flex; 
        align-items: center; 
        gap: 15px; 
        padding: 12px 16px; 
        margin-bottom: 8px; 
        border-radius: 14px; 
        cursor: pointer; 
        transition: 0.3s; 
        color: #cbd5e6; 
    }
    
    .nav-item:hover { 
        background: rgba(255, 255, 255, 0.1); 
        color: white; 
    }
    
    .nav-item.active { 
        background: #fd0054; 
        color: white; 
        box-shadow: 0 4px 8px rgba(0, 0, 0, 0.2); 
    }
    
    .nav-bottom { 
        margin-top: 30px; 
        padding-top: 20px; 
        border-top: 1px solid rgba(255,255,255,0.1); 
    }
    
    .main-content { 
        flex: 1; 
        padding: 28px 32px; 
        background: #f8fafc; 
        overflow-y: auto; 
        height: 100vh; 
    }
    
    .top-bar { 
        display: flex; 
        justify-content: space-between; 
        align-items: center; 
        padding: 12px 24px; 
        background: white; 
        border-radius: 60px; 
        box-shadow: 0 1px 3px rgba(0,0,0,0.05); 
        margin-bottom: 28px; 
    }
    
    .page-title h2 { 
        font-size: 1.3rem; 
        font-weight: 600; 
        color: #1e293b; 
        margin: 0; 
    }
    
    .search-box {
        display: flex;
        gap: 10px;
        align-items: center;
        flex-wrap: wrap;
        margin-bottom: 20px;
    }
    
    .search-box input {
        border-radius: 30px;
        padding: 8px 20px;
        border: 1px solid #e0e4e8;
        flex: 1;
        min-width: 200px;
        transition: all 0.2s ease;
    }
    
    .search-box input:focus {
        border-color: #667eea;
        box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
        outline: none;
    }
    
    .search-box select {
        border-radius: 30px;
        padding: 8px 20px;
        border: 1px solid #e0e4e8;
        min-width: 180px;
        background: white;
        transition: all 0.2s ease;
    }
    
    .search-box select:focus {
        border-color: #667eea;
        box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
        outline: none;
    }
    
    .search-box button {
        border-radius: 30px;
        padding: 8px 20px;
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        color: white;
        border: none;
        transition: all 0.2s ease;
        white-space: nowrap;
    }
    
    .search-box button:hover {
        transform: translateY(-2px);
        box-shadow: 0 5px 15px rgba(102, 126, 234, 0.4);
    }
    
    .clear-search {
        border-radius: 30px;
        padding: 8px 20px;
        background: #6c757d;
        color: white;
        text-decoration: none;
        transition: all 0.2s ease;
        white-space: nowrap;
    }
    
    .clear-search:hover {
        background: #5a6268;
        color: white;
    }
    
    .filter-badge {
        display: inline-block;
        background: #e9ecef;
        padding: 4px 12px;
        border-radius: 20px;
        font-size: 13px;
        margin-right: 10px;
    }
    
    .filter-badge .remove-filter {
        color: #dc3545;
        margin-left: 5px;
        cursor: pointer;
        text-decoration: none;
    }
    
    .filter-badge .remove-filter:hover {
        color: #a71d2a;
    }
    
    .result-count {
        font-size: 14px;
        color: #6c757d;
        margin-bottom: 15px;
    }
    
    .btn-primary {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        border: none;
        padding: 10px 25px;
        border-radius: 10px;
        font-weight: 600;
    }
    
    .btn-primary:hover {
        transform: translateY(-2px);
        box-shadow: 0 5px 15px rgba(102, 126, 234, 0.4);
    }
    
    .btn-success {
        background: linear-gradient(135deg, #28a745 0%, #20c997 100%);
        border: none;
        padding: 10px 25px;
        border-radius: 10px;
        font-weight: 600;
    }
    
    .btn-success:hover {
        transform: translateY(-2px);
        box-shadow: 0 5px 15px rgba(40, 167, 69, 0.4);
    }
    
    .btn-warning, .btn-danger {
        padding: 5px 12px;
        font-size: 12px;
        border-radius: 8px;
        margin: 0 3px;
    }
    
    .modal-custom .modal-content {
        border-radius: 20px;
        border: none;
    }
    
    .modal-custom .modal-header {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        color: white;
        border-radius: 20px 20px 0 0;
        border: none;
    }
    
    .modal-custom .modal-header .btn-close {
        filter: brightness(0) invert(1);
    }
    
    .modal-custom .modal-body {
        padding: 25px;
        max-height: 70vh;
        overflow-y: auto;
    }
    
    .more {
        display: flex;
        gap: 10px;
        margin-bottom: 15px;
        flex-wrap: wrap;
    }
    
    .more button, .more a {
        padding: 10px 20px;
        border-radius: 10px;
        transition: all 0.2s ease;
        border: none;
        font-weight: 600;
        text-decoration: none;
        display: inline-flex;
        align-items: center;
        gap: 8px;
    }
    
    .more .btn-block {
        background: linear-gradient(135deg, #28a745 0%, #20c997 100%);
        color: white;
        cursor: pointer;
    }
    
    .more .btn-block:hover {
        transform: translateY(-2px);
        box-shadow: 0 5px 15px rgba(40, 167, 69, 0.4);
        color: white;
    }
    
    .more .btn-add {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        color: white;
        cursor: pointer;
    }
    
    .more .btn-add:hover {
        transform: translateY(-2px);
        box-shadow: 0 5px 15px rgba(102, 126, 234, 0.4);
        color: white;
    }
    
    .form-label {
        font-weight: 600;
        color: #2c3e50;
        margin-bottom: 8px;
        font-size: 14px;
    }
    
    .form-control, .form-select {
        border-radius: 10px;
        border: 1px solid #e0e4e8;
        padding: 10px 15px;
        transition: all 0.2s ease;
    }
    
    .form-control:focus, .form-select:focus {
        border-color: #667eea;
        box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
        outline: none;
    }
    
    .loading-opacity {
        opacity: 0.6;
        pointer-events: none;
    }
    
    .badge {
        padding: 5px 10px;
        font-size: 12px;
    }
    
    .score-excellent {
            color: green;
            font-weight: bold;
        }
        
        .score-good {
            color: blue;
            font-weight: bold;
        }
        
        .score-average {
            color: orange;
            font-weight: bold;
        }
        
        .score-warning {
            color: goldenrod;
            font-weight: bold;
        }
        
        .score-low {
            color: brown;
            font-weight: bold;
        }
        
        .score-poor {
            color: red;
            font-weight: bold;
        }
    
    .department-badge {
        display: inline-block;
        padding: 3px 12px;
        border-radius: 20px;
        font-size: 12px;
        font-weight: 600;
    }
    .dept-it { background: #dbeafe; color: #1e40af; }
    .dept-marketing { background: #fce7f3; color: #9d174d; }
    .dept-management { background: #d1fae5; color: #065f46; }
    .dept-electrical { background: #fef3c7; color: #92400e; }
    .dept-accounting { background: #e0e7ff; color: #3730a3; }
    .dept-civil { background: #fed7aa; color: #9a3412; }
    .dept-electronics { background: #cffafe; color: #0e7490; }
    .dept-default { background: #e2e8f0; color: #475569; }
    
    .score-input {
        width: 80px;
        padding: 5px 8px;
        border-radius: 6px;
        border: 1px solid #ced4da;
        text-align: center;
    }
    
    .score-input:focus {
        border-color: #667eea;
        outline: none;
        box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
    }
    
    .block-subject-form {
        background: white;
        padding: 25px;
        border-radius: 16px;
        box-shadow: 0 2px 10px rgba(0,0,0,0.05);
        margin-bottom: 25px;
    }
    
    .block-subject-form .form-row {
        display: flex;
        flex-wrap: wrap;
        gap: 15px;
        align-items: end;
    }
    
    .block-subject-form .form-group {
        flex: 1;
        min-width: 180px;
    }
    
    .block-subject-form .form-group label {
        font-weight: 600;
        font-size: 14px;
        margin-bottom: 5px;
        display: block;
        color: #2c3e50;
    }
    
    .grade-select {
        width: 70px;
        padding: 5px 5px;
        border-radius: 6px;
        border: 1px solid #ced4da;
    }
    
    @media (max-width: 768px) {
        .sidebar { width: 80px; }
        .sidebar-header h1, .sidebar-header p, .nav-item span { display: none; }
        .nav-item { justify-content: center; }
        .main-content { padding: 15px; }
        .top-bar { flex-direction: column; gap: 10px; }
        .search-box { flex-direction: column; width: 100%; }
        .search-box input, .search-box select { width: 100%; }
        .more { flex-direction: column; }
    }
</style>
</head>
<body>
    <div class="container">
        <!-- Sidebar -->
        <div class="sidebar">
            <div class="sidebar-header">
                <img src="https://i.pinimg.com/736x/6a/33/e1/6a33e1b0b8fbb948cbf04b7397c9b381.jpg" alt="Logo" />
                <h1>KRaksa</h1>
                <p>Education Suite</p>
            </div>
            <div class="nav-menu">
                <a href="index.php" class="nav-item">
                    <i class="fas fa-tachometer-alt"></i> <span>Main Dashboard</span>
                </a>
                <div class="dropdown-container">
                    <div class="nav-item dropdown-btn" onclick="toggleDropdown()">
                        <div>
                            <i class="fas fa-user-graduate"></i>
                            <span>Students</span>
                        </div>
                        <i class="fas fa-chevron-down dropdown-icon" id="dropdownIcon"></i>
                    </div>
                    <div class="dropdown-menus" id="studentDropdown">
                        <a href="student.php" class="nav-item sub-menu">
                            <i class="fas fa-users"></i>
                            <span>Student List</span>
                        </a>
                        <a href="stutype.php" class="nav-item sub-menu">
                            <i class="fas fa-tags"></i>
                            <span>Student Type</span>
                        </a>
                        <a href="stuviwe.php" class="nav-item sub-menu">
                            <i class="fas fa-eye"></i>
                            <span>Student View</span>
                        </a>
                        <a href="grade.php" class="nav-item sub-menu">
                            <i class="fas fa-layer-group"></i>
                            <span>Student Grades</span>
                        </a>
                        <a href="score.php" class="nav-item sub-menu active">
                            <i class="fas fa-chart-line"></i>
                            <span>Student Scores</span>
                        </a>
                        <a href="student_payments.php" class="nav-item sub-menu">
                            <i class="fas fa-money-bill-wave"></i>
                            <span>Student Payments</span>
                        </a>
                        <a href="card_stuIT.php" class="nav-item sub-menu">
                            <i class="fas fa-id-card"></i>
                            <span>ID Card</span>
                        </a>
                    </div>
                </div>
                <a href="teacher.php" class="nav-item">
                    <i class="fas fa-chalkboard-teacher"></i> <span>Teachers</span>
                </a>
                <a href="Courses.php" class="nav-item">
                    <i class="fas fa-graduation-cap"></i> <span>Department</span>
                </a>
                 <a href="schedule.php" class="nav-item" data-page="schedule"> <i class="fas fa-calendar-alt"></i> <span>Schedule class</span></a>
                 <div class="dropdown-container">
                <div class="nav-item dropdown-btn" onclick="toggleRequestDropdown()">
                    <div>
                        <i class="fas fa-file-pdf"></i> 
                        <span class="m-2"> Request</span>
                    </div>
                    <i class="fas fa-chevron-down dropdown-icon" id="RequestDropdownIcon"></i>
                </div>
                <div class="dropdown-menus" id="RequestDropdownMenu">
                    <a href=" Request_teacher.php" class="nav-item sub-menu"><i class="fas fa-chalkboard-teacher"></i><span>Teacher</span></a>
                    <a href="Request_student.php" class="nav-item sub-menu"><i class="fas fa-users"></i><span>Student </span></a>
                   
                </div>
            </div>
                <a href="Employees.php" class="nav-item">
                    <i class="fas fa-user-friends"></i> <span>Employees</span>
                </a>
                <a href="StudentAttendance.php" class="nav-item">
                    <i class="fas fa-calendar-check"></i> <span>Attendance</span>
                </a>
                <div class="nav-bottom">
                    <a href="logout.php" class="nav-item">
                        <i class="fas fa-sign-out-alt"></i> <span>Logout</span>
                    </a>
                </div>
            </div>
        </div>

        <!-- Main Content -->
        <div class="main-content">
            <div class="top-bar">
                <div class="page-title">
                    <h2><i class="fas fa-percent"></i> Block Subject Score Entry</h2>
                </div>
                <div class="date-time" id="currentDateTime"></div>
            </div>

            <?php if(isset($success)): ?>
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    <i class="fas fa-check-circle"></i> <?php echo htmlspecialchars($success); ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>

            <?php if(isset($error)): ?>
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    <i class="fas fa-exclamation-circle"></i> <?php echo htmlspecialchars($error); ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>

            <?php if(isset($_GET['success'])): ?>
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    <i class="fas fa-check-circle"></i> <?php echo htmlspecialchars($_GET['success']); ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>

            <?php if(isset($_GET['error'])): ?>
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    <i class="fas fa-exclamation-circle"></i> <?php echo htmlspecialchars($_GET['error']); ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>

            <div class="more">
                <a href="?block=1" class="btn-block" id="blockSubjectBtn">
                    <i class="fas fa-layer-group"></i> Block Subject Entry
                </a>
                <button class="btn-add" id="addMoreBtn">
                    <i class="fas fa-plus"></i> Add Single Score
                </button>
            </div>

            <!-- ===== BLOCK SUBJECT ENTRY FORM ===== -->
            <?php if(isset($_GET['block']) || isset($_GET['block_subject'])): ?>
            <div class="block-subject-form">
                <h5><i class="fas fa-pen-fancy text-success"></i> Block Subject Score Entry</h5>
                <p class="text-muted small">Select a subject to view all students and enter scores in bulk.</p>
                
                <form method="GET" action="" class="form-row" id="blockSubjectSelectForm">
                    <div class="form-group">
                        <label>Select Subject <span class="text-danger">*</span></label>
                        <select name="block_subject" class="form-select" required>
                            <option value="">-- Choose Subject --</option>
                            <?php foreach ($subjects_from_db as $subj): ?>
                                <option value="<?php echo htmlspecialchars($subj); ?>" 
                                    <?php echo (isset($_GET['block_subject']) && $_GET['block_subject'] == $subj) ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($subj); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Filter by Department (Optional)</label>
                        <select name="block_dept" class="form-select">
                            <option value="">All Departments</option>
                            <?php foreach ($departments as $dept): ?>
                                <option value="<?php echo htmlspecialchars($dept); ?>" 
                                    <?php echo (isset($_GET['block_dept']) && $_GET['block_dept'] == $dept) ? 'selected' : ''; ?>>
                                    <?php 
                                    $display_name = $dept;
                                    if ($dept == 'it') $display_name = 'IT';
                                    echo htmlspecialchars($display_name); 
                                    ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>&nbsp;</label>
                        <button type="submit" class="btn btn-success">
                            <i class="fas fa-users"></i> Load Students
                        </button>
                    </div>
                </form>

                <?php if (!empty($students_for_block)): ?>
                <hr>
                <form method="POST" action="" id="blockScoreForm">
                    <input type="hidden" name="bulk_update" value="1">
                    <input type="hidden" name="subject" value="<?php echo htmlspecialchars($_GET['block_subject']); ?>">
                    <input type="hidden" name="department" value="<?php echo htmlspecialchars($_GET['block_dept'] ?? ''); ?>">
                    
                    <div class="row mb-3">
                        <div class="col-md-3">
                            <label class="form-label">Subject</label>
                            <input type="text" class="form-control" value="<?php echo htmlspecialchars($_GET['block_subject']); ?>" disabled>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Type</label>
                            <select name="type" class="form-select">
                                <option value="midterm">Midterm</option>
                                <option value="final">Final</option>
                                <option value="quiz">Quiz</option>
                                <option value="assignment">Assignment</option>
                                <option value="other">Other</option>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Semester</label>
                            <input type="text" name="semester" class="form-control" placeholder="e.g. Semester 1">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Academic Year</label>
                            <input type="text" name="academic_year" class="form-control" placeholder="e.g. 2024-2025">
                        </div>
                    </div>
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label class="form-label">Auto-calculate Grade?</label>
                            <select name="auto_grade" class="form-select" id="autoGradeSelect">
                                <option value="1">Yes (Auto-calculate from Score)</option>
                                <option value="0">No (Manual Grade Selection)</option>
                            </select>
                            <small class="text-muted">Grade scale: A(90-100), B(80-89), C(70-79), D(60-69), E(50-59), F(0-49)</small>
                        </div>
                    </div>
                    
                    <div class="table-container">
                        <table class="table table-bordered table-striped">
                            <thead class="table-light">
                                <tr>
                                    <th style="width:50px;">ID</th>
                                    <th>Student Name</th>
                                    <th style="width:120px;">Department</th>
                                    <th style="width:120px;">Score (0-100)</th>
                                    <th style="width:100px;">Grade</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($students_for_block as $student): 
                                    $dept = $student['department'] ?? '';
                                    $dept_class = 'dept-default';
                                    if (isset($dept_colors[$dept])) {
                                        $dept_class = $dept_colors[$dept];
                                    }
                                    $display_dept = $dept;
                                    if ($dept == 'it') $display_dept = 'IT';
                                ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($student['id']); ?></td>
                                    <td><?php echo htmlspecialchars($student['name']); ?></td>
                                    <td>
                                        <?php if (!empty($dept)): ?>
                                            <span class="department-badge <?php echo $dept_class; ?>">
                                                <?php echo htmlspecialchars($display_dept); ?>
                                            </span>
                                        <?php else: ?>
                                            <span class="text-muted">N/A</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <input type="number" step="0.01" min="0" max="100" 
                                               name="score_<?php echo $student['id']; ?>" 
                                               class="score-input score-input-<?php echo $student['id']; ?>"
                                               value="<?php echo htmlspecialchars($student['score'] ?? ''); ?>"
                                               onchange="autoGrade(<?php echo $student['id']; ?>)">
                                    </td>
                                    <td>
                                        <select name="grade_<?php echo $student['id']; ?>" 
                                                class="grade-select grade-select-<?php echo $student['id']; ?>"
                                                id="grade_<?php echo $student['id']; ?>">
                                            <option value="">Auto</option>
                                            <option value="A" <?php echo ($student['Grade'] ?? '') == 'A' ? 'selected' : ''; ?>>A</option>
                                            <option value="B" <?php echo ($student['Grade'] ?? '') == 'B' ? 'selected' : ''; ?>>B</option>
                                            <option value="C" <?php echo ($student['Grade'] ?? '') == 'C' ? 'selected' : ''; ?>>C</option>
                                            <option value="D" <?php echo ($student['Grade'] ?? '') == 'D' ? 'selected' : ''; ?>>D</option>
                                            <option value="E" <?php echo ($student['Grade'] ?? '') == 'E' ? 'selected' : ''; ?>>E</option>
                                            <option value="F" <?php echo ($student['Grade'] ?? '') == 'F' ? 'selected' : ''; ?>>F</option>
                                        </select>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                    
                    <div class="mt-3 d-flex justify-content-end gap-2">
                        <button type="submit" class="btn btn-success">
                            <i class="fas fa-save"></i> Save All Scores
                        </button>
                    </div>
                </form>
                <?php elseif (isset($_GET['block_subject'])): ?>
                    <div class="alert alert-info mt-3">
                        <i class="fas fa-info-circle"></i> No students found for this subject.
                        <?php if (!empty($_GET['block_dept'])): ?>
                            <br><small>Try removing the department filter.</small>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>
            </div>
            <?php endif; ?>

            <!-- ===== SEARCH & FILTER FORM ===== -->
            <form method="GET" action="" class="search-box" id="searchForm">
                <input type="text" name="search" placeholder="Search by name, subject, score, grade, department..." 
                       value="<?php echo htmlspecialchars($search); ?>">
                
                <select name="department" id="deptFilter">
                    <option value="">All Departments</option>
                    <?php foreach ($departments as $dept): ?>
                        <option value="<?php echo htmlspecialchars($dept); ?>" 
                                <?php echo ($dept_filter == $dept) ? 'selected' : ''; ?>>
                            <?php 
                            $display_name = $dept;
                            if ($dept == 'it') $display_name = 'IT';
                            echo htmlspecialchars($display_name); 
                            ?>
                        </option>
                    <?php endforeach; ?>
                </select>
                
                <select name="subject" id="subjectFilter">
                    <option value="">All Subjects</option>
                    <?php foreach ($subjects_from_db as $subj): ?>
                        <option value="<?php echo htmlspecialchars($subj); ?>" 
                                <?php echo ($subject_filter == $subj) ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($subj); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
                
                <button type="submit"><i class="fas fa-search"></i> Search</button>
                
                <?php if(!empty($search) || !empty($dept_filter) || !empty($subject_filter)): ?>
                    <a href="score.php" class="clear-search"><i class="fas fa-times"></i> Clear</a>
                <?php endif; ?>
            </form>

            <!-- ===== ACTIVE FILTERS ===== -->
            <?php if(!empty($dept_filter) || !empty($subject_filter)): ?>
                <div class="mb-3">
                    <?php if(!empty($dept_filter)): ?>
                        <span class="filter-badge">
                            <i class="fas fa-filter"></i> Department: 
                            <?php 
                            $display_name = $dept_filter;
                            if ($dept_filter == 'it') $display_name = 'IT';
                            echo htmlspecialchars($display_name); 
                            ?>
                            <a href="?<?php echo !empty($search) ? 'search=' . urlencode($search) : ''; ?><?php echo !empty($subject_filter) ? '&subject=' . urlencode($subject_filter) : ''; ?>" class="remove-filter">
                                <i class="fas fa-times-circle"></i>
                            </a>
                        </span>
                    <?php endif; ?>
                    <?php if(!empty($subject_filter)): ?>
                        <span class="filter-badge">
                            <i class="fas fa-book"></i> Subject: <?php echo htmlspecialchars($subject_filter); ?>
                            <a href="?<?php echo !empty($search) ? 'search=' . urlencode($search) : ''; ?><?php echo !empty($dept_filter) ? '&department=' . urlencode($dept_filter) : ''; ?>" class="remove-filter">
                                <i class="fas fa-times-circle"></i>
                            </a>
                        </span>
                    <?php endif; ?>
                </div>
            <?php endif; ?>

            <!-- ===== RESULT COUNT ===== -->
            <div class="result-count">
                <i class="fas fa-chart-bar"></i> 
                <?php 
                $count = isset($result) ? mysqli_num_rows($result) : 0;
                echo $count . ' record(s) found';
                ?>
            </div>

            <!-- ===== TABLE ===== -->
            <div class="table-container">
                <table class="table table-bordered table-striped">
                    <thead class="table-light">
                        <tr>
                            <th style="width:50px;">ID</th>
                            <th>Name</th>
                            <th style="width:120px;">Department</th>
                            <th>Subject</th>
                            <th style="width:80px;">Score</th>
                            <th style="width:70px;">Grade</th>
                            <th style="width:100px;">Type</th>
                            <th style="width:100px;">Semester</th>
                            <th style="width:150px;">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (isset($result) && mysqli_num_rows($result) > 0) {
                            while($row = mysqli_fetch_assoc($result)) { 
                                $dept = $row['department'] ?? '';
                                $dept_class = 'dept-default';
                                if (isset($dept_colors[$dept])) {
                                    $dept_class = $dept_colors[$dept];
                                }
                                $display_dept = $dept;
                                if ($dept == 'it') $display_dept = 'IT';
                            ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($row['id']); ?></td>
                                    <td><?php echo htmlspecialchars($row['name']); ?></td>
                                    <td>
                                        <?php if (!empty($dept)): ?>
                                            <span class="department-badge <?php echo $dept_class; ?>">
                                                <?php echo htmlspecialchars($display_dept); ?>
                                            </span>
                                        <?php else: ?>
                                            <span class="text-muted">N/A</span>
                                        <?php endif; ?>
                                    </td>
                                    <td><?php echo htmlspecialchars($row['subject']); ?></td>
                                    <td>
                                        <?php
                                        $score = floatval($row['score']);
                                        
                                        if ($score >= 90) {
                                            echo "<span class='score-excellent'>$score</span>"; // Green
                                        } elseif ($score >= 80) {
                                            echo "<span class='score-good'>$score</span>";      // Blue
                                        } elseif ($score >= 70) {
                                            echo "<span class='score-average'>$score</span>";   // Orange
                                        } elseif ($score >= 60) {
                                            echo "<span class='score-warning'>$score</span>";   // Yellow
                                        } elseif ($score >= 50) {
                                            echo "<span class='score-low'>$score</span>";       // Brown
                                        } else {
                                            echo "<span class='score-poor'>$score</span>";      // Red
                                        }
                                        ?>
                                        <!-- <?php 
                                        $score = floatval($row['score']);
                                        if($score >= 80) {
                                            echo "<span class='score-excellent'><i class='fas fa-award'></i> $score</span>";
                                        } elseif($score >= 60) {
                                            echo "<span class='score-good'><i class='fas fa-check-circle'></i> $score</span>";
                                        } elseif($score >= 40) {
                                            echo "<span class='score-average'><i class='fas fa-exclamation-triangle'></i> $score</span>";
                                        } else {
                                            echo "<span class='score-poor'><i class='fas fa-times-circle'></i> $score</span>";
                                        }
                                        ?> -->
                                    </td>
                                    <td>
                                        <?php 
                                        $grade = strtoupper($row['Grade'] ?? '');
                                        $grade_colors = [
                                            'A' => 'success',
                                            'B' => 'primary',
                                            'C' => 'warning',
                                            'D' => 'info',
                                            'E' => 'secondary',
                                            'F' => 'danger'
                                        ];
                                        $color = $grade_colors[$grade] ?? 'secondary';
                                        ?>
                                        <span class="badge bg-<?php echo $color; ?>">
                                            <?php echo htmlspecialchars($grade); ?>
                                        </span>
                                    </td>
                                    <td>
                                        <?php 
                                        $type = strtolower($row['type'] ?? '');
                                        if (strpos($type, 'mid') !== false) {
                                            echo '<span class="badge bg-primary">Midterm</span>';
                                        } elseif (strpos($type, 'final') !== false) {
                                            echo '<span class="badge bg-warning text-dark">Final</span>';
                                        } else {
                                            echo '<span class="badge bg-secondary">' . htmlspecialchars($row['type'] ?? 'N/A') . '</span>';
                                        }
                                        ?>
                                    </td>
                                    <td><?php echo htmlspecialchars($row['semester'] ?? 'N/A'); ?></td>
                                    <td>
                                        <button type='button' class='btn btn-warning btn-sm edit-btn' data-id='<?php echo $row['id']; ?>'>
                                            <i class='fas fa-edit'></i>
                                        </button>
                                        <a href='delete_score.php?id=<?php echo $row['id']; ?>' class='btn btn-danger btn-sm' onclick='return confirm("Are you sure you want to delete this score record?")'>
                                            <i class='fas fa-trash'></i>
                                        </a>
                                    </td>
                                </tr>
                            <?php }
                        } else { ?>
                            <tr>
                                <td colspan='9' class='text-center'>
                                    <i class='fas fa-chart-line'></i> No score records found.
                                </td>
                            </tr>
                        <?php } ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    
    <!-- Add Score Modal -->
    <div class="modal fade modal-custom" id="addStudentModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title"><i class="fas fa-plus-circle"></i> Add New Score Record</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <form action="" method="POST" id="addStudentForm">
                        <input type="hidden" name="add_single_score" value="1">
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label class="form-label">Name <span class="text-danger">*</span></label>
                                <input type="text" name="name" class="form-control" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Department</label>
                                <select name="department" class="form-select">
                                    <option value="">Select Department</option>
                                    <option value="it">IT</option>
                                    <option value="Marketing">Marketing</option>
                                    <option value="Management">Management</option>
                                    <option value="Electrical Engineering">Electrical Engineering</option>
                                    <option value="Accounting">Accounting</option>
                                    <option value="Civil Engineering">Civil Engineering</option>
                                    <option value="Electronics">Electronics</option>
                                </select>
                            </div>
                        </div>
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label class="form-label">Subject <span class="text-danger">*</span></label>
                                <input type="text" name="subject" class="form-control" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Type</label>
                                <select name="type" class="form-select">
                                    <option value="midterm">Midterm</option>
                                    <option value="final">Final</option>
                                    <option value="quiz">Quiz</option>
                                    <option value="assignment">Assignment</option>
                                    <option value="other">Other</option>
                                </select>
                            </div>
                        </div>
                        <div class="row mb-3">
                            <div class="col-md-4">
                                <label class="form-label">Score <span class="text-danger">*</span></label>
                                <input type="number" step="0.01" name="score" class="form-control" required>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Grade</label>
                                <select name="Grade" class="form-select">
                                    <option value="">Auto-calculate</option>
                                    <option value="A">A</option>
                                    <option value="B">B</option>
                                    <option value="C">C</option>
                                    <option value="D">D</option>
                                    <option value="E">E</option>
                                    <option value="F">F</option>
                                </select>
                                <small class="text-muted">Leave empty for auto-calculation</small>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Semester</label>
                                <input type="text" name="semester" class="form-control" placeholder="e.g. Semester 1">
                            </div>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Academic Year</label>
                            <input type="text" name="academic_year" class="form-control" placeholder="e.g. 2024-2025">
                        </div>
                        
                        <div class="mt-4 d-flex justify-content-end gap-2">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                            <button type="submit" class="btn btn-primary">Save Record</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Edit Score Modal -->
    <div class="modal fade modal-custom" id="editStudentModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title"><i class="fas fa-edit"></i> Edit Score Record</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <form id="editStudentForm">
                        <input type="hidden" name="ajax" value="update_single_score">
                        <input type="hidden" name="id" id="edit_record_id">
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label class="form-label">Name <span class="text-danger">*</span></label>
                                <input type="text" name="name" id="edit_name" class="form-control" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Department</label>
                                <select name="department" id="edit_department" class="form-select">
                                    <option value="">Select Department</option>
                                    <option value="it">IT</option>
                                    <option value="Marketing">Marketing</option>
                                    <option value="Management">Management</option>
                                    <option value="Electrical Engineering">Electrical Engineering</option>
                                    <option value="Accounting">Accounting</option>
                                    <option value="Civil Engineering">Civil Engineering</option>
                                    <option value="Electronics">Electronics</option>
                                </select>
                            </div>
                        </div>
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label class="form-label">Subject <span class="text-danger">*</span></label>
                                <input type="text" name="subject" id="edit_subject" class="form-control" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Type</label>
                                <select name="type" id="edit_type" class="form-select">
                                    <option value="midterm">Midterm</option>
                                    <option value="final">Final</option>
                                    <option value="quiz">Quiz</option>
                                    <option value="assignment">Assignment</option>
                                    <option value="other">Other</option>
                                </select>
                            </div>
                        </div>
                        <div class="row mb-3">
                            <div class="col-md-4">
                                <label class="form-label">Score <span class="text-danger">*</span></label>
                                <input type="number" step="0.01" name="score" id="edit_score" class="form-control" required>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Grade</label>
                                <select name="Grade" id="edit_Grade" class="form-select">
                                    <option value="">Auto-calculate</option>
                                    <option value="A">A</option>
                                    <option value="B">B</option>
                                    <option value="C">C</option>
                                    <option value="D">D</option>
                                    <option value="E">E</option>
                                    <option value="F">F</option>
                                </select>
                                <small class="text-muted">Leave empty for auto-calculation</small>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Semester</label>
                                <input type="text" name="semester" id="edit_semester" class="form-control" placeholder="e.g. Semester 1">
                            </div>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Academic Year</label>
                            <input type="text" name="academic_year" id="edit_academic_year" class="form-control" placeholder="e.g. 2024-2025">
                        </div>
                       
                        <div class="mt-4 d-flex justify-content-end gap-2">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                            <button type="submit" class="btn btn-primary">Update Record</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script>
        // ===== UPDATE DATE/TIME =====
        function updateDateTime() {
            const now = new Date();
            const options = { 
                weekday: 'long', 
                year: 'numeric', 
                month: 'long', 
                day: 'numeric', 
                hour: '2-digit', 
                minute: '2-digit',
                second: '2-digit'
            };
            const dateTimeElement = document.getElementById('currentDateTime');
            if (dateTimeElement) {
                dateTimeElement.innerHTML = now.toLocaleDateString('en-US', options);
            }
        }
        updateDateTime();
        setInterval(updateDateTime, 1000);

        function toggleRequestDropdown() {
            let menu = document.getElementById("RequestDropdownMenu");
            let icon = document.getElementById("RequestDropdownIcon");
            
            if (menu.style.display === "block") {
                menu.style.display = "none";
                icon.style.transform = "rotate(0deg)";
            } else {
                let studentMenu = document.getElementById("studentDropdownMenu");
                let studentIcon = document.getElementById("studentDropdownIcon");
                if (studentMenu) {
                    studentMenu.style.display = "none";
                    if (studentIcon) studentIcon.style.transform = "rotate(0deg)";
                }
                menu.style.display = "block";
                icon.style.transform = "rotate(180deg)";
            }
        }
        
        function toggleDropdown(){
            let menu = document.getElementById("studentDropdown");
            let icon = document.getElementById("dropdownIcon");
            if(menu.style.display === "block"){
                menu.style.display = "none";
                icon.style.transform = "rotate(0deg)";
            }else{
                menu.style.display = "block";
                icon.style.transform = "rotate(180deg)";
            }
        }

        // ===== AUTO GRADE FUNCTION =====
        function autoGrade(studentId) {
            var scoreInput = document.querySelector('.score-input-' + studentId);
            var gradeSelect = document.getElementById('grade_' + studentId);
            var autoGradeSelect = document.getElementById('autoGradeSelect');
            
            if (autoGradeSelect && autoGradeSelect.value == '1') {
                var score = parseFloat(scoreInput.value);
                if (!isNaN(score) && score >= 0 && score <= 100) {
                    var grade = '';
                    if (score >= 90) grade = 'A';
                    else if (score >= 80) grade = 'B';
                    else if (score >= 70) grade = 'C';
                    else if (score >= 60) grade = 'D';
                    else if (score >= 50) grade = 'E';
                    else grade = 'F';
                    gradeSelect.value = grade;
                }
            }
        }

        // ===== AUTO GRADE ON AUTO_GRADE CHANGE =====
        $(document).ready(function() {
            $('#autoGradeSelect').change(function() {
                if ($(this).val() == '1') {
                    $('.score-input').each(function() {
                        var studentId = $(this).attr('name').replace('score_', '');
                        autoGrade(studentId);
                    });
                }
            });
            
            // Trigger auto grade on page load for existing values
            setTimeout(function() {
                $('.score-input').each(function() {
                    var studentId = $(this).attr('name').replace('score_', '');
                    autoGrade(studentId);
                });
            }, 500);
        });

        // ===== ADD MODAL =====
        $(document).ready(function() {
            $('#addMoreBtn').click(function() {
                $('#addStudentModal').modal('show');
            });
            
            $('.edit-btn').click(function() {
                var recordId = $(this).data('id');
                $('#editStudentModal .modal-body').addClass('loading-opacity');
                
                $.ajax({
                    url: window.location.href,
                    type: 'GET',
                    data: {
                        ajax: 'get_student',
                        id: recordId
                    },
                    dataType: 'json',
                    success: function(data) {
                        $('#edit_record_id').val(data.id);
                        $('#edit_name').val(data.name);
                        $('#edit_department').val(data.department || '');
                        $('#edit_subject').val(data.subject);
                        $('#edit_score').val(data.score);
                        $('#edit_Grade').val(data.Grade || '');
                        $('#edit_type').val(data.type || 'midterm');
                        $('#edit_semester').val(data.semester || '');
                        $('#edit_academic_year').val(data.academic_year || '');
                        
                        $('#editStudentModal').modal('show');
                        $('#editStudentModal .modal-body').removeClass('loading-opacity');
                    },
                    error: function(xhr, status, error) {
                        alert('Error loading score data. Please try again.');
                        $('#editStudentModal .modal-body').removeClass('loading-opacity');
                    }
                });
            });
            
            $('#editStudentForm').submit(function(e) {
                e.preventDefault();
                
                var submitBtn = $(this).find('button[type="submit"]');
                var originalText = submitBtn.html();
                submitBtn.html('<i class="fas fa-spinner fa-spin"></i> Updating...').prop('disabled', true);
                
                $.ajax({
                    url: window.location.href,
                    type: 'POST',
                    data: $(this).serialize(),
                    dataType: 'json',
                    success: function(response) {
                        if (response.success) {
                            alert(response.message);
                            location.reload();
                        } else {
                            alert('Error: ' + response.message);
                            submitBtn.html(originalText).prop('disabled', false);
                        }
                    },
                    error: function() {
                        alert('Error updating score record. Please try again.');
                        submitBtn.html(originalText).prop('disabled', false);
                    }
                });
            });
        });
    </script>
</body>
</html>