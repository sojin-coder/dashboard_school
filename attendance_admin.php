<?php
// session_start();
include "db.php";

// ========== CHECK ADMIN LOGIN ==========
if(!isset($_SESSION['id'])){
    header("Location: login.php");
    exit();
}

$message = '';
$error = '';
$selected_class_id = null;
$selected_date = date('Y-m-d');
$selected_department = '';
$attendance_data = array();
$students_list = array();
$class_info = null;

// ========== GET ALL TEACHERS ==========
$teachers_sql = "SELECT id, name FROM teacher ORDER BY name ASC";
$teachers_result = mysqli_query($conn, $teachers_sql);

// ========== GET ALL DEPARTMENTS ==========
$departments = array();

// Get departments from teacher_classes
$dept_sql = "SELECT DISTINCT subject FROM teacher_classes WHERE subject IS NOT NULL AND subject != '' ORDER BY subject ASC";
$dept_result = mysqli_query($conn, $dept_sql);
if($dept_result) {
    while($row = mysqli_fetch_assoc($dept_result)) {
        $departments[] = $row['subject'];
    }
}

// Get departments from schedule_class
$dept_sql2 = "SELECT DISTINCT department FROM schedule_class WHERE department IS NOT NULL AND department != '' ORDER BY department ASC";
$dept_result2 = mysqli_query($conn, $dept_sql2);
if($dept_result2) {
    while($row = mysqli_fetch_assoc($dept_result2)) {
        if(!in_array($row['department'], $departments)) {
            $departments[] = $row['department'];
        }
    }
}

sort($departments);

// ========== FILTER HANDLING ==========
$filter_department = isset($_GET['department']) ? mysqli_real_escape_string($conn, $_GET['department']) : '';
$filter_class_id = isset($_GET['class_id']) ? mysqli_real_escape_string($conn, $_GET['class_id']) : '';
$filter_teacher_id = isset($_GET['teacher_id']) ? mysqli_real_escape_string($conn, $_GET['teacher_id']) : '';

// ========== DATE HANDLING ==========
if(isset($_GET['date'])) {
    $input_date = $_GET['date'];
    $date_formats = ['Y-m-d', 'd-m-Y', 'm-d-Y', 'd/m/Y', 'm/d/Y'];
    $valid_date = false;
    
    foreach($date_formats as $format) {
        $date_obj = DateTime::createFromFormat($format, $input_date);
        if($date_obj && $date_obj->format($format) === $input_date) {
            $selected_date = $date_obj->format('Y-m-d');
            $valid_date = true;
            break;
        }
    }
    
    if(!$valid_date) {
        $selected_date = date('Y-m-d');
    }
} else {
    $selected_date = date('Y-m-d');
}

// ========== GET CLASSES ==========
$all_classes = array();

// Get classes from teacher_classes
$classes_sql = "SELECT 
                    tc.id, 
                    tc.class_name, 
                    tc.subject, 
                    tc.year, 
                    tc.shift,
                    tc.teacher_name,
                    tc.teacher_id,
                    'teacher_classes' as source
                FROM teacher_classes tc";
if(!empty($filter_department)) {
    $classes_sql .= " WHERE LOWER(tc.subject) = LOWER('" . mysqli_real_escape_string($conn, $filter_department) . "')";
}
if(!empty($filter_teacher_id)) {
    if(strpos($classes_sql, 'WHERE') !== false) {
        $classes_sql .= " AND tc.teacher_id = '" . mysqli_real_escape_string($conn, $filter_teacher_id) . "'";
    } else {
        $classes_sql .= " WHERE tc.teacher_id = '" . mysqli_real_escape_string($conn, $filter_teacher_id) . "'";
    }
}
$classes_sql .= " ORDER BY tc.class_name ASC";
$classes_result = mysqli_query($conn, $classes_sql);

if($classes_result) {
    while($class = mysqli_fetch_assoc($classes_result)) {
        $all_classes[] = $class;
    }
}

// Also get from schedule_class
$classes_sql2 = "SELECT 
                    sc.id, 
                    sc.subject as class_name, 
                    sc.department as subject, 
                    sc.year, 
                    sc.shift,
                    t.name as teacher_name,
                    t.id as teacher_id,
                    'schedule_class' as source
                FROM schedule_class sc
                LEFT JOIN teacher t ON sc.teacher_id = t.id";
if(!empty($filter_department)) {
    $classes_sql2 .= " WHERE LOWER(sc.department) = LOWER('" . mysqli_real_escape_string($conn, $filter_department) . "')";
}
if(!empty($filter_teacher_id)) {
    if(strpos($classes_sql2, 'WHERE') !== false) {
        $classes_sql2 .= " AND sc.teacher_id = '" . mysqli_real_escape_string($conn, $filter_teacher_id) . "'";
    } else {
        $classes_sql2 .= " WHERE sc.teacher_id = '" . mysqli_real_escape_string($conn, $filter_teacher_id) . "'";
    }
}
$classes_sql2 .= " ORDER BY sc.subject ASC";
$classes_result2 = mysqli_query($conn, $classes_sql2);

if($classes_result2) {
    while($class = mysqli_fetch_assoc($classes_result2)) {
        $exists = false;
        foreach($all_classes as $existing) {
            if($existing['id'] == $class['id'] && $existing['source'] == $class['source']) {
                $exists = true;
                break;
            }
        }
        if(!$exists) {
            $all_classes[] = $class;
        }
    }
}

// ========== GET STUDENTS FOR CLASS ==========
function getStudentsForClassAdmin($class_id, $conn) {
    // Get class info
    $class_sql = "SELECT subject, year, shift FROM teacher_classes WHERE id = '$class_id'";
    $class_result = mysqli_query($conn, $class_sql);
    if(!$class_result || mysqli_num_rows($class_result) == 0) {
        $class_sql = "SELECT department as subject, year, shift FROM schedule_class WHERE id = '$class_id'";
        $class_result = mysqli_query($conn, $class_sql);
    }
    if(!$class_result || mysqli_num_rows($class_result) == 0) return array();
    $class_info = mysqli_fetch_assoc($class_result);
    
    $sql = "SELECT * FROM students WHERE 1=1";
    
    if(!empty($class_info['subject'])) {
        $sql .= " AND LOWER(college) = LOWER('" . mysqli_real_escape_string($conn, $class_info['subject']) . "')";
    }
    
    if(!empty($class_info['year'])) {
        $year_map = ['1' => 'year 1', '2' => 'year 2', '3' => 'year 3', '4' => 'year 4'];
        $year_value = isset($year_map[$class_info['year']]) ? $year_map[$class_info['year']] : $class_info['year'];
        $sql .= " AND LOWER(year) = LOWER('" . mysqli_real_escape_string($conn, $year_value) . "')";
    }
    
    if(!empty($class_info['shift'])) {
        $sql .= " AND LOWER(Shift) = LOWER('" . mysqli_real_escape_string($conn, $class_info['shift']) . "')";
    }
    
    $sql .= " ORDER BY name ASC";
    
    $result = mysqli_query($conn, $sql);
    if(!$result) return array();
    
    $students = array();
    while($row = mysqli_fetch_assoc($result)) {
        $students[] = $row;
    }
    return $students;
}

// ========== GET ATTENDANCE DATA FROM DATABASE ==========
function getAttendanceByDateAdmin($class_id, $date, $conn) {
    $sql = "SELECT * FROM attendance_student 
            WHERE class_id = '$class_id' AND attendance_date = '$date'";
    $result = mysqli_query($conn, $sql);
    
    if(!$result) return array();
    
    $attendance = array();
    while($row = mysqli_fetch_assoc($result)) {
        $attendance[$row['student_id']] = $row;
    }
    return $attendance;
}

// ========== GET ATTENDANCE HISTORY ==========
function getAttendanceHistory($class_id, $conn) {
    $sql = "SELECT attendance_date, COUNT(*) as total,
                SUM(CASE WHEN status = 'present' THEN 1 ELSE 0 END) as present,
                SUM(CASE WHEN status = 'absent' THEN 1 ELSE 0 END) as absent,
                SUM(CASE WHEN status = 'late' THEN 1 ELSE 0 END) as late
            FROM attendance_student 
            WHERE class_id = '$class_id'
            GROUP BY attendance_date 
            ORDER BY attendance_date DESC 
            LIMIT 10";
    $result = mysqli_query($conn, $sql);
    
    if(!$result) return array();
    
    $history = array();
    while($row = mysqli_fetch_assoc($result)) {
        $history[] = $row;
    }
    return $history;
}

// ========== GET ATTENDANCE STATS ==========
function getAttendanceStats($class_id, $date, $conn) {
    $sql = "SELECT 
                SUM(CASE WHEN status = 'present' THEN 1 ELSE 0 END) as present,
                SUM(CASE WHEN status = 'absent' THEN 1 ELSE 0 END) as absent,
                SUM(CASE WHEN status = 'late' THEN 1 ELSE 0 END) as late,
                COUNT(*) as total
            FROM attendance_student 
            WHERE class_id = '$class_id' AND attendance_date = '$date'";
    $result = mysqli_query($conn, $sql);
    if($result) {
        return mysqli_fetch_assoc($result);
    }
    return array('present' => 0, 'absent' => 0, 'late' => 0, 'total' => 0);
}

// ========== GET ATTENDANCE DATA ==========
if(!empty($filter_class_id)) {
    $selected_class_id = $filter_class_id;
    
    // Get class info
    $class_sql = "SELECT tc.*, 'teacher_classes' as source FROM teacher_classes tc WHERE tc.id = '$selected_class_id'";
    $class_result = mysqli_query($conn, $class_sql);
    if(!$class_result || mysqli_num_rows($class_result) == 0) {
        $class_sql = "SELECT sc.*, t.name as teacher_name, 'schedule_class' as source 
                      FROM schedule_class sc 
                      LEFT JOIN teacher t ON sc.teacher_id = t.id 
                      WHERE sc.id = '$selected_class_id'";
        $class_result = mysqli_query($conn, $class_sql);
    }
    
    if($class_result) {
        $class_info = mysqli_fetch_assoc($class_result);
        if($class_info) {
            // Get students
            $students_list = getStudentsForClassAdmin($selected_class_id, $conn);
            // Get attendance data from database (what teacher has saved)
            $attendance_data = getAttendanceByDateAdmin($selected_class_id, $selected_date, $conn);
        }
    }
}

// ========== UPDATE ATTENDANCE (Admin edits) ==========
if(isset($_POST['update_attendance'])) {
    $class_id = mysqli_real_escape_string($conn, $_POST['class_id']);
    $attendance_date = mysqli_real_escape_string($conn, $_POST['attendance_date']);
    
    // Update attendance for each student
    if(isset($_POST['student_status'])) {
        foreach($_POST['student_status'] as $student_id => $status) {
            $student_id = mysqli_real_escape_string($conn, $student_id);
            $status = mysqli_real_escape_string($conn, $status);
            $student_name = mysqli_real_escape_string($conn, $_POST['student_name'][$student_id]);
            
            // Check if attendance exists
            $check_sql = "SELECT id FROM attendance_student 
                         WHERE class_id = '$class_id' 
                         AND student_id = '$student_id' 
                         AND attendance_date = '$attendance_date'";
            $check_result = mysqli_query($conn, $check_sql);
            
            if($check_result && mysqli_num_rows($check_result) > 0) {
                // Update existing attendance
                $update_sql = "UPDATE attendance_student SET 
                    status = '$status',
                    student_name = '$student_name',
                    updated_at = CURRENT_TIMESTAMP
                    WHERE class_id = '$class_id' 
                    AND student_id = '$student_id' 
                    AND attendance_date = '$attendance_date'";
                mysqli_query($conn, $update_sql);
            } else {
                // Insert new attendance (if teacher didn't save yet)
                $insert_sql = "INSERT INTO attendance_student 
                              (class_id, student_id, student_name, attendance_date, status) 
                              VALUES ('$class_id', '$student_id', '$student_name', '$attendance_date', '$status')";
                mysqli_query($conn, $insert_sql);
            }
        }
        $message = "✅ Attendance updated successfully!";
    }
    
    header("Location: attendance_admin.php?class_id=$class_id&date=$attendance_date&success=updated");
    exit();
}

// ========== DELETE ATTENDANCE ==========
if(isset($_GET['delete_attendance']) && is_numeric($_GET['delete_attendance'])) {
    $attendance_id = $_GET['delete_attendance'];
    $delete_sql = "DELETE FROM attendance_student WHERE id = '$attendance_id'";
    if(mysqli_query($conn, $delete_sql)) {
        header("Location: attendance_admin.php?class_id=$filter_class_id&date=$selected_date&success=deleted");
        exit();
    }
}

// ========== DELETE ALL ATTENDANCE FOR A DATE ==========
if(isset($_GET['delete_date']) && isset($_GET['class_id'])) {
    $class_id = mysqli_real_escape_string($conn, $_GET['class_id']);
    $delete_date = mysqli_real_escape_string($conn, $_GET['delete_date']);
    
    $delete_sql = "DELETE FROM attendance_student 
                   WHERE class_id = '$class_id' AND attendance_date = '$delete_date'";
    if(mysqli_query($conn, $delete_sql)) {
        header("Location: attendance_admin.php?class_id=$class_id&success=deleted");
        exit();
    }
}

// ========== SUCCESS MESSAGES ==========
if(isset($_GET['success'])) {
    switch($_GET['success']) {
        case 'updated':
            $message = "✅ Attendance updated successfully!";
            break;
        case 'deleted':
            $message = "✅ Attendance record deleted!";
            break;
    }
}

// ========== CREATE/UPDATE ATTENDANCE TABLE ==========
$check_columns = "SHOW COLUMNS FROM attendance_student";
$check_result = mysqli_query($conn, $check_columns);
$has_student_name = false;
$has_attendance_date = false;
$has_class_id = false;

if($check_result) {
    while($col = mysqli_fetch_assoc($check_result)) {
        if($col['Field'] == 'student_name') $has_student_name = true;
        if($col['Field'] == 'attendance_date') $has_attendance_date = true;
        if($col['Field'] == 'class_id') $has_class_id = true;
    }
}

// Add missing columns
if(!$has_class_id) {
    mysqli_query($conn, "ALTER TABLE attendance_student ADD COLUMN class_id INT(11) NOT NULL AFTER id");
}
if(!$has_student_name) {
    mysqli_query($conn, "ALTER TABLE attendance_student ADD COLUMN student_name VARCHAR(100) NOT NULL AFTER student_id");
}
if(!$has_attendance_date) {
    mysqli_query($conn, "ALTER TABLE attendance_student ADD COLUMN attendance_date DATE NOT NULL AFTER student_name");
}

// Add unique key
$check_key = "SHOW INDEX FROM attendance_student WHERE Key_name = 'unique_attendance'";
$key_result = mysqli_query($conn, $check_key);
if(mysqli_num_rows($key_result) == 0) {
    mysqli_query($conn, "ALTER TABLE attendance_student ADD UNIQUE KEY unique_attendance (class_id, student_id, attendance_date)");
}

// ========== GET ATTENDANCE HISTORY FOR DISPLAY ==========
$attendance_history = array();
if(!empty($filter_class_id)) {
    $attendance_history = getAttendanceHistory($filter_class_id, $conn);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Attendance Management - Admin</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        /* Same styles as before */
        * { margin: 0; padding: 0; box-sizing: border-box; }
        a { text-decoration: none; }
        body { font-family: "Inter", sans-serif; background: #c5e1fc; color: #0f172a; overflow-x: hidden; }
        
        .container { display: flex; min-height: 100vh; max-width: 100%; }
        
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
        .sidebar-header { padding: 28px 24px; border-bottom: 1px solid rgba(255, 255, 255, 0.1); margin-bottom: 24px; text-align: center; }
        .sidebar-header img { width: 100px; height: 100px; border-radius: 50%; margin: auto; display: block; border: 4px solid white; object-fit: cover; }
        .sidebar-header h1 { font-size: 1.8rem; font-weight: 700; color: white; margin-top: 10px; }
        .sidebar-header p { font-size: 0.85rem; opacity: 0.8; }
        .nav-menu { flex: 1; padding: 0 16px; }
        .nav-item { display: flex; align-items: center; gap: 15px; padding: 12px 16px; margin-bottom: 8px; border-radius: 14px; cursor: pointer; transition: 0.3s; color: #cbd5e6; text-decoration: none; }
        .nav-item:hover { background: rgba(255, 255, 255, 0.1); color: white; }
        .nav-item.active { background: #fd0054; color: white; box-shadow: 0 4px 8px rgba(0, 0, 0, 0.2); }
        .nav-bottom { margin-top: 30px; padding-top: 20px; border-top: 1px solid rgba(255,255,255,0.1); }
        
        .main-content { flex: 1; padding: 28px 32px; background: #f8fafc; overflow-y: auto; height: 100vh; }
        .top-bar { display: flex; justify-content: space-between; align-items: center; padding: 12px 24px; background: white; border-radius: 60px; box-shadow: 0 1px 3px rgba(0,0,0,0.05); margin-bottom: 28px; }
        .page-title h2 { font-size: 1.3rem; font-weight: 600; color: #1e293b; margin: 0; }
        
        .table-container {
            max-height: 400px;
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
        
        .content-box {
            background: white;
            border-radius: 20px;
            padding: 25px;
            box-shadow: 0 5px 20px rgba(0,0,0,0.05);
            margin-bottom: 25px;
        }
        
        .btn-primary-custom {
            background: #4f46e5;
            color: white;
            border: none;
            padding: 10px 25px;
            border-radius: 10px;
            font-weight: 600;
            transition: 0.3s;
        }
        .btn-primary-custom:hover {
            background: #4338ca;
            color: white;
            transform: translateY(-2px);
        }
        
        .btn-update {
            background: #f59e0b;
            color: white;
            border: none;
            padding: 12px 30px;
            border-radius: 10px;
            font-weight: 600;
            transition: 0.3s;
        }
        .btn-update:hover {
            background: #d97706;
            color: white;
            transform: translateY(-2px);
        }
        
        .btn-danger-custom {
            background: #ef4444;
            color: white;
            border: none;
            padding: 10px 25px;
            border-radius: 10px;
            font-weight: 600;
            transition: 0.3s;
        }
        .btn-danger-custom:hover {
            background: #dc2626;
            color: white;
            transform: translateY(-2px);
        }
        
        .form-control, .form-select {
            border-radius: 10px;
            border: 1px solid #e2e8f0;
            padding: 10px 15px;
        }
        .form-control:focus, .form-select:focus {
            border-color: #4f46e5;
            box-shadow: 0 0 0 3px rgba(79, 70, 229, 0.1);
        }
        
        .badge-status {
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 0.75rem;
            font-weight: 600;
        }
        .badge-present { background: #dcfce7; color: #166534; }
        .badge-absent { background: #fee2e2; color: #991b1b; }
        .badge-late { background: #fef3c7; color: #92400e; }
        
        .attendance-options .btn-group .btn {
            padding: 6px 16px;
            font-size: 0.8rem;
            border-width: 2px;
            border-radius: 8px;
            font-weight: 500;
            min-width: 80px;
            text-align: center;
        }
        .attendance-options .btn-group .btn-att-present {
            background: white;
            color: #22c55e;
            border: 2px solid #22c55e;
        }
        .attendance-options .btn-group .btn-att-absent {
            background: white;
            color: #ef4444;
            border: 2px solid #ef4444;
        }
        .attendance-options .btn-group .btn-att-late {
            background: white;
            color: #f59e0b;
            border: 2px solid #f59e0b;
        }
        .attendance-options .btn-group .btn-check:checked + .btn-att-present {
            background: #22c55e !important;
            color: white !important;
        }
        .attendance-options .btn-group .btn-check:checked + .btn-att-absent {
            background: #ef4444 !important;
            color: white !important;
        }
        .attendance-options .btn-group .btn-check:checked + .btn-att-late {
            background: #f59e0b !important;
            color: white !important;
        }
        
        .class-info-card {
            background: linear-gradient(135deg, #4f46e5, #7c3aed);
            color: white;
            border-radius: 16px;
            padding: 20px 25px;
            margin-bottom: 20px;
        }
        .class-info-card h4 { font-weight: 700; margin: 0; }
        .class-info-card .teacher-name { opacity: 0.9; }
        .class-info-card .badge { background: rgba(255,255,255,0.2); color: white; padding: 5px 12px; }
        
        .date-navigation {
            display: flex;
            align-items: center;
            gap: 10px;
            flex-wrap: wrap;
        }
        .date-navigation .btn {
            border-radius: 8px;
            padding: 8px 15px;
        }
        
        .alert-custom {
            border-radius: 12px;
            padding: 15px 20px;
            margin-bottom: 20px;
        }
        
        .class-list-item {
            transition: all 0.3s;
            border-left: 4px solid transparent;
        }
        .class-list-item:hover {
            background: #f8fafc;
            border-left-color: #4f46e5;
        }
        .class-list-item .class-badge {
            background: #eef2ff;
            color: #4f46e5;
            padding: 2px 10px;
            border-radius: 20px;
            font-size: 0.75rem;
        }
        
        .source-badge {
            font-size: 0.6rem;
            padding: 2px 8px;
            border-radius: 20px;
            background: #e2e8f0;
            color: #475569;
        }
        
        .student-count-badge {
            background: #eef2ff;
            color: #4f46e5;
            padding: 2px 10px;
            border-radius: 20px;
            font-size: 0.7rem;
        }
        
        .filter-section {
            background: #f8fafc;
            padding: 20px;
            border-radius: 12px;
            border: 1px solid #e2e8f0;
        }
        
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
        
        .attendance-info {
            background: #f0fdf4;
            border: 1px solid #86efac;
            border-radius: 10px;
            padding: 12px 20px;
            margin-bottom: 15px;
        }
        .attendance-info i {
            color: #22c55e;
        }
        
        @media (max-width: 768px) {
            .sidebar { width: 80px; }
            .sidebar-header h1, .sidebar-header p, .nav-item span { display: none; }
            .nav-item { justify-content: center; }
            .main-content { padding: 15px; }
            .attendance-options .btn-group .btn {
                padding: 4px 10px;
                font-size: 0.7rem;
                min-width: 60px;
            }
        }
    </style>
</head>
<body>

<div class="container">
    <!-- ========== SIDEBAR ========== -->
    <div class="sidebar">
        <div class="sidebar-header">
            <img src="https://i.pinimg.com/736x/6a/33/e1/6a33e1b0b8fbb948cbf04b7397c9b381.jpg" alt="Logo" />
            <h1>KRaksa</h1>
            <p>Education Suite</p>
        </div>
        <div class="nav-menu">
            <a href="/" class="nav-item"><i class="fas fa-tachometer-alt"></i> <span>Main Dashboard</span></a>
            
            <div class="dropdown-container">
                <div class="nav-item dropdown-btn" onclick="toggleDropdown()">
                    <div>
                        <i class="fas fa-user-graduate"></i>
                        <span class="m-2">Students</span>
                    </div>
                    <i class="fas fa-chevron-down dropdown-icon" id="dropdownIcon"></i>
                </div>
                <div class="dropdown-menus" id="studentDropdown">
                    <a href="student.php" class="nav-item sub-menu"><i class="fas fa-users"></i><span>Student List</span></a>
                    <a href="stutype.php" class="nav-item sub-menu"><i class="fas fa-tags"></i><span>Student Type</span></a>
                    <a href="stuviwe.php" class="nav-item sub-menu"><i class="fas fa-eye"></i><span>Student View</span></a>
                    <a href="grade.php" class="nav-item sub-menu"><i class="fas fa-layer-group"></i><span>Student Grades</span></a>
                    <a href="score.php" class="nav-item sub-menu"><i class="fas fa-chart-line"></i><span>Student Scores</span></a>
                    <a href="student_payments.php" class="nav-item sub-menu"><i class="fas fa-money-bill-wave"></i><span>Student Payments</span></a>
                    <a href="card_stuIT.php" class="nav-item sub-menu"><i class="fas fa-id-card"></i><span>ID Card</span></a>
                </div>
            </div>
            
            <a href="teacher.php" class="nav-item"><i class="fas fa-chalkboard-teacher"></i> <span>Teachers</span></a>
            <a href="Courses.php" class="nav-item"><i class="fas fa-graduation-cap"></i> <span>Department</span></a>
            <a href="schedule.php" class="nav-item"><i class="fas fa-calendar-alt"></i> <span>Schedule class</span></a>
            
            <div class="dropdown-container">
                <div class="nav-item dropdown-btn" onclick="toggleRequestDropdown()">
                    <div>
                        <i class="fas fa-file-pdf"></i> 
                        <span class="m-2">Request</span>
                    </div>
                    <i class="fas fa-chevron-down dropdown-icon" id="RequestDropdownIcon"></i>
                </div>
                <div class="dropdown-menus" id="RequestDropdownMenu">
                    <a href="Request_teacher.php" class="nav-item sub-menu"><i class="fas fa-chalkboard-teacher"></i><span>Teacher</span></a>
                    <a href="Request_student.php" class="nav-item sub-menu"><i class="fas fa-users"></i><span>Student</span></a>
                </div>
            </div>
            
            <div class="dropdown-container">
                <div class="nav-item dropdown-btn" onclick="toggleReportDropdown()">
                    <div>
                        <i class="fas fa-file-pdf"></i> 
                        <span class="m-2">Report</span>
                    </div>
                    <i class="fas fa-chevron-down dropdown-icon" id="reportDropdownIcon"></i>
                </div>
                <div class="dropdown-menus" id="reportDropdownMenu">
                    <a href="report_teacher.php" class="nav-item sub-menu"><i class="fas fa-chalkboard-teacher"></i><span>Teacher Report</span></a>
                    <a href="report_student.php" class="nav-item sub-menu"><i class="fas fa-users"></i><span>Student Report</span></a>
                    <a href="report_month.php" class="nav-item sub-menu"><i class="fas fa-chart-line"></i><span>Monthly Report</span></a>
                </div>
            </div>
            
            <a href="Employees.php" class="nav-item"><i class="fas fa-user-friends"></i> <span>Employees</span></a>
            <!-- <a href="StudentAttendance.php" class="nav-item"><i class="fas fa-calendar-check"></i> <span>Attendance</span></a> -->
            <a href="attendance_admin.php" class="nav-item active"><i class="fas fa-clipboard-check"></i> <span>Attendance Admin</span></a>
            
            <div class="nav-bottom">
                <a href="logout.php" class="nav-item" style="padding-left:8px;">
                    <i class="fas fa-sign-out-alt"></i> <span>Logout</span>
                </a>
            </div>
        </div>
    </div>

    <!-- ========== MAIN CONTENT ========== -->
    <div class="main-content">
        <div class="top-bar">
            <div class="page-title">
                <h2><i class="fas fa-clipboard-check"></i> Attendance Management</h2>
            </div>
            <div class="date-time" id="currentDateTime"></div>
        </div>

        <!-- ========== MESSAGES ========== -->
        <?php if(!empty($message)): ?>
            <div class="alert alert-success alert-custom">
                <i class="fas fa-check-circle"></i> <?php echo $message; ?>
            </div>
        <?php endif; ?>
        <?php if(!empty($error)): ?>
            <div class="alert alert-danger alert-custom">
                <i class="fas fa-exclamation-circle"></i> <?php echo $error; ?>
            </div>
        <?php endif; ?>

        <!-- ========== FILTER SECTION ========== -->
        <div class="content-box">
            <h5><i class="fas fa-filter"></i> Filter</h5>
            <hr>
            <form method="GET" class="filter-section">
                <div class="row g-3">
                    <div class="col-md-3">
                        <label class="form-label"><i class="fas fa-building"></i> Department</label>
                        <select class="form-select" name="department">
                            <option value="">All Departments</option>
                            <?php foreach($departments as $dept): ?>
                                <option value="<?php echo htmlspecialchars($dept); ?>" <?php echo ($filter_department == $dept) ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($dept); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label"><i class="fas fa-chalkboard-teacher"></i> Teacher</label>
                        <select class="form-select" name="teacher_id">
                            <option value="">All Teachers</option>
                            <?php 
                            if($teachers_result) {
                                while($teacher = mysqli_fetch_assoc($teachers_result)):
                            ?>
                                <option value="<?php echo $teacher['id']; ?>" <?php echo ($filter_teacher_id == $teacher['id']) ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($teacher['name']); ?>
                                </option>
                            <?php 
                                endwhile;
                            }
                            ?>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label"><i class="fas fa-calendar"></i> Date</label>
                        <input type="date" class="form-control" name="date" value="<?php echo $selected_date; ?>">
                    </div>
                    <div class="col-md-3 d-flex align-items-end">
                        <button type="submit" class="btn-primary-custom w-100">
                            <i class="fas fa-search"></i> Filter
                        </button>
                    </div>
                </div>
            </form>
        </div>

        <!-- ========== CLASSES LIST ========== -->
        <div class="content-box">
            <h5><i class="fas fa-school"></i> Classes (<?php echo count($all_classes); ?>)</h5>
            <hr>
            
            <?php if(!empty($all_classes)): ?>
                <div class="row g-3">
                    <?php foreach($all_classes as $class): 
                        $student_count = count(getStudentsForClassAdmin($class['id'], $conn));
                        $source_label = ($class['source'] ?? '') == 'teacher_classes' ? '👨‍🏫 Teacher' : '📋 Schedule';
                    ?>
                        <div class="col-md-6 col-lg-4">
                            <a href="?class_id=<?php echo $class['id']; ?>&date=<?php echo $selected_date; ?><?php echo !empty($filter_department) ? '&department=' . urlencode($filter_department) : ''; ?><?php echo !empty($filter_teacher_id) ? '&teacher_id=' . urlencode($filter_teacher_id) : ''; ?>" 
                               class="text-decoration-none">
                                <div class="class-list-item content-box" 
                                     style="<?php echo ($filter_class_id == $class['id']) ? 'border-left-color: #4f46e5; background: #eef2ff;' : ''; ?>">
                                    <div class="d-flex justify-content-between align-items-start">
                                        <div>
                                            <h6 class="mb-1">
                                                <?php echo htmlspecialchars($class['class_name'] ?? $class['subject']); ?>
                                                <span class="source-badge ms-1"><?php echo $source_label; ?></span>
                                            </h6>
                                            <small class="text-muted">
                                                <i class="fas fa-chalkboard-teacher"></i> 
                                                <?php echo htmlspecialchars($class['teacher_name'] ?? 'N/A'); ?>
                                            </small>
                                        </div>
                                        <span class="class-badge">
                                            <?php if(!empty($class['year'])): ?>
                                                Year <?php echo htmlspecialchars($class['year']); ?>
                                            <?php endif; ?>
                                        </span>
                                    </div>
                                    <div class="mt-2 d-flex gap-2 flex-wrap">
                                        <?php if(!empty($class['shift'])): ?>
                                            <span class="badge <?php echo $class['shift'] == 'morning' ? 'bg-warning' : 'bg-info'; ?>">
                                                <?php echo ucfirst(htmlspecialchars($class['shift'])); ?>
                                            </span>
                                        <?php endif; ?>
                                        <span class="student-count-badge">
                                            <i class="fas fa-users"></i> <?php echo $student_count; ?> Students
                                        </span>
                                    </div>
                                </div>
                            </a>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <div class="text-center py-4">
                    <i class="fas fa-school" style="font-size: 48px; color: #cbd5e1;"></i>
                    <p class="text-muted mt-3">No classes found.</p>
                </div>
            <?php endif; ?>
        </div>

        <!-- ========== ATTENDANCE SECTION ========== -->
        <?php if(!empty($filter_class_id) && $class_info): ?>
            <!-- Class Info -->
            <div class="class-info-card">
                <div class="row align-items-center">
                    <div class="col-md-7">
                        <h4>
                            <i class="fas fa-school"></i> 
                            <?php echo htmlspecialchars($class_info['class_name'] ?? $class_info['subject']); ?>
                            <span class="badge ms-2" style="background: rgba(255,255,255,0.2);">
                                <?php echo ($class_info['source'] ?? '') == 'teacher_classes' ? '👨‍🏫 Teacher Class' : '📋 Schedule Class'; ?>
                            </span>
                        </h4>
                        <p class="teacher-name">
                            <i class="fas fa-chalkboard-teacher"></i> Teacher: <?php echo htmlspecialchars($class_info['teacher_name'] ?? 'N/A'); ?>
                            &nbsp;|&nbsp;
                            <i class="fas fa-building"></i> <?php echo htmlspecialchars($class_info['subject'] ?? $class_info['department']); ?>
                            <?php if(!empty($class_info['year'])): ?>
                                <span class="badge">Year <?php echo htmlspecialchars($class_info['year']); ?></span>
                            <?php endif; ?>
                            <?php if(!empty($class_info['shift'])): ?>
                                <span class="badge"><?php echo ucfirst(htmlspecialchars($class_info['shift'])); ?></span>
                            <?php endif; ?>
                            <span class="badge">
                                <i class="fas fa-calendar"></i> <?php echo date('d M Y', strtotime($selected_date)); ?>
                            </span>
                        </p>
                    </div>
                    <div class="col-md-5 text-md-end">
                        <span class="badge" style="background: rgba(255,255,255,0.2); font-size: 1rem;">
                            <i class="fas fa-users"></i> <?php echo count($students_list); ?> Students
                        </span>
                        <?php 
                        $stats = getAttendanceStats($filter_class_id, $selected_date, $conn);
                        ?>
                        <span class="badge" style="background: rgba(255,255,255,0.2); font-size: 0.9rem;">
                            <i class="fas fa-check-circle"></i> <?php echo $stats['present']; ?> Present
                        </span>
                        <span class="badge" style="background: rgba(255,255,255,0.2); font-size: 0.9rem;">
                            <i class="fas fa-times-circle"></i> <?php echo $stats['absent']; ?> Absent
                        </span>
                        <span class="badge" style="background: rgba(255,255,255,0.2); font-size: 0.9rem;">
                            <i class="fas fa-clock"></i> <?php echo $stats['late']; ?> Late
                        </span>
                    </div>
                </div>
            </div>

            <!-- Attendance Info - Show if teacher has saved -->
            <div class="attendance-info">
                <i class="fas fa-info-circle"></i> 
                <strong>Attendance Status:</strong> 
                <?php 
                $has_attendance = !empty($attendance_data);
                if($has_attendance): 
                ?>
                    <span class="badge bg-success">✅ Teacher has saved attendance for this date</span>
                    <span class="text-muted ms-2">(You can review and edit if needed)</span>
                <?php else: ?>
                    <span class="badge bg-warning">⏳ No attendance saved yet for this date</span>
                    <span class="text-muted ms-2">(You can add attendance)</span>
                <?php endif; ?>
            </div>

            <!-- Attendance Form -->
            <div class="content-box">
                <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-3">
                    <h4>
                        <i class="fas fa-clipboard-check"></i> 
                        <?php echo $has_attendance ? 'Review & Edit Attendance' : 'Add Attendance'; ?>
                        <span class="text-muted fs-6">(<?php echo htmlspecialchars($class_info['class_name'] ?? 'Class'); ?>)</span>
                    </h4>
                    <div>
                        <a href="attendance_admin.php" class="btn btn-secondary btn-sm">
                            <i class="fas fa-arrow-left"></i> Back
                        </a>
                    </div>
                </div>
                
                <div class="alert alert-info alert-custom py-2 mb-3">
                    <i class="fas fa-info-circle"></i> 
                    Showing students from <strong>College: <?php echo htmlspecialchars($class_info['subject'] ?? $class_info['department']); ?></strong>
                    <?php if(!empty($class_info['year'])): ?>
                        for <strong><?php echo ucfirst($class_info['year']); ?></strong>
                    <?php endif; ?>
                    <?php if(!empty($class_info['shift'])): ?>
                        in <strong><?php echo ucfirst($class_info['shift']); ?> Shift</strong>
                    <?php endif; ?>
                    <?php if($has_attendance): ?>
                        <br>
                        <span class="text-success">✅ This attendance was saved by the teacher. You can edit it below.</span>
                    <?php endif; ?>
                </div>
                
                <hr>
                
                <form method="POST">
                    <input type="hidden" name="class_id" value="<?php echo $filter_class_id; ?>">
                    
                    <div class="row mb-4">
                        <div class="col-md-6">
                            <label class="form-label"><i class="fas fa-calendar"></i> Date</label>
                            <input type="date" class="form-control" name="attendance_date" 
                                   value="<?php echo $selected_date; ?>" required>
                        </div>
                        <div class="col-md-6 d-flex align-items-end">
                            <button type="submit" name="update_attendance" class="btn-update w-100">
                                <i class="fas fa-save"></i> <?php echo $has_attendance ? 'Update Attendance' : 'Save Attendance'; ?>
                            </button>
                        </div>
                    </div>
                    
                    <?php if(empty($students_list)): ?>
                        <div class="alert alert-warning">
                            <i class="fas fa-exclamation-triangle"></i> 
                            No students found for this class.
                            <hr>
                            <p class="mb-0"><strong>Please add students to the database first.</strong></p>
                        </div>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th>#</th>
                                        <th>Student Name</th>
                                        <th>Code</th>
                                        <th>Email</th>
                                        <th>College</th>
                                        <th>Year</th>
                                        <th>Shift</th>
                                        <th>Status</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php 
                                    $count = 1;
                                    foreach($students_list as $student):
                                        $existing = isset($attendance_data[$student['id']]) ? $attendance_data[$student['id']] : null;
                                        $status = $existing ? $existing['status'] : 'present';
                                    ?>
                                    <tr>
                                        <td><?php echo $count++; ?></td>
                                        <td>
                                            <?php echo htmlspecialchars($student['name']); ?>
                                            <input type="hidden" name="student_name[<?php echo $student['id']; ?>]" 
                                                   value="<?php echo htmlspecialchars($student['name']); ?>">
                                        </td>
                                        <td><?php echo htmlspecialchars($student['student_code'] ?? 'N/A'); ?></td>
                                        <td><?php echo htmlspecialchars($student['email'] ?? '-'); ?></td>
                                        <td>
                                            <span class="badge bg-info bg-opacity-10 text-info">
                                                <?php echo htmlspecialchars($student['college'] ?? '-'); ?>
                                            </span>
                                        </td>
                                        <td><?php echo htmlspecialchars($student['year'] ?? '-'); ?></td>
                                        <td>
                                            <span class="badge <?php echo strtolower($student['Shift'] ?? '') == 'morning' ? 'bg-warning bg-opacity-10 text-warning' : 'bg-primary bg-opacity-10 text-primary'; ?>">
                                                <?php echo htmlspecialchars($student['Shift'] ?? '-'); ?>
                                            </span>
                                        </td>
                                        <td>
                                            <div class="attendance-options">
                                                <div class="btn-group btn-group-sm gap-2" role="group">
                                                    <input type="radio" class="btn-check" 
                                                           name="student_status[<?php echo $student['id']; ?>]" 
                                                           id="present_<?php echo $student['id']; ?>" 
                                                           value="present" <?php echo ($status == 'present') ? 'checked' : ''; ?>>
                                                    <label class="btn btn-att-present" 
                                                           for="present_<?php echo $student['id']; ?>">
                                                        <i class="fas fa-check"></i> Present
                                                    </label>
                                                    
                                                    <input type="radio" class="btn-check" 
                                                           name="student_status[<?php echo $student['id']; ?>]" 
                                                           id="absent_<?php echo $student['id']; ?>" 
                                                           value="absent" <?php echo ($status == 'absent') ? 'checked' : ''; ?>>
                                                    <label class="btn btn-att-absent" 
                                                           for="absent_<?php echo $student['id']; ?>">
                                                        <i class="fas fa-times"></i> Absent
                                                    </label>
                                                    
                                                    <input type="radio" class="btn-check" 
                                                           name="student_status[<?php echo $student['id']; ?>]" 
                                                           id="late_<?php echo $student['id']; ?>" 
                                                           value="late" <?php echo ($status == 'late') ? 'checked' : ''; ?>>
                                                    <label class="btn btn-att-late" 
                                                           for="late_<?php echo $student['id']; ?>">
                                                        <i class="fas fa-clock"></i> Late
                                                    </label>
                                                </div>
                                            </div>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                        
                        <div class="mt-3 d-flex justify-content-between align-items-center flex-wrap gap-2">
                            <span class="text-muted small">
                                <i class="fas fa-info-circle"></i> 
                                Showing <?php echo count($students_list); ?> students
                                <?php if($has_attendance): ?>
                                    | <i class="fas fa-check-circle text-success"></i> Attendance already saved
                                <?php endif; ?>
                            </span>
                            <div class="d-flex gap-2">
                                <button type="submit" name="update_attendance" class="btn-update">
                                    <i class="fas fa-save"></i> <?php echo $has_attendance ? 'Update Attendance' : 'Save Attendance'; ?>
                                </button>
                                <?php if($has_attendance): ?>
                                <a href="?delete_date=<?php echo $selected_date; ?>&class_id=<?php echo $filter_class_id; ?>" 
                                   class="btn-danger-custom" 
                                   onclick="return confirm('Are you sure you want to delete all attendance for <?php echo date('d M Y', strtotime($selected_date)); ?>?')">
                                    <i class="fas fa-trash"></i> Delete This Day
                                </a>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endif; ?>
                </form>
            </div>
            
            <!-- ========== ATTENDANCE HISTORY ========== -->
            <div class="content-box">
                <h4><i class="fas fa-history"></i> Attendance History</h4>
                <hr>
                
                <?php if(!empty($attendance_history)): ?>
                    <div class="table-responsive">
                        <table class="table table-sm table-hover">
                            <thead>
                                <tr>
                                    <th>Date</th>
                                    <th>Present</th>
                                    <th>Absent</th>
                                    <th>Late</th>
                                    <th>Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach($attendance_history as $history): ?>
                                <tr>
                                    <td><?php echo date('d M Y', strtotime($history['attendance_date'])); ?></td>
                                    <td><span class="badge badge-present"><?php echo $history['present']; ?></span></td>
                                    <td><span class="badge badge-absent"><?php echo $history['absent']; ?></span></td>
                                    <td><span class="badge badge-late"><?php echo $history['late']; ?></span></td>
                                    <td>
                                        <a href="?class_id=<?php echo $filter_class_id; ?>&date=<?php echo $history['attendance_date']; ?>" 
                                           class="btn btn-sm btn-outline-primary">
                                            <i class="fas fa-eye"></i> View
                                        </a>
                                        <a href="?delete_date=<?php echo $history['attendance_date']; ?>&class_id=<?php echo $filter_class_id; ?>" 
                                           class="btn btn-sm btn-outline-danger"
                                           onclick="return confirm('Delete all attendance for <?php echo date('d M Y', strtotime($history['attendance_date'])); ?>?')">
                                            <i class="fas fa-trash"></i>
                                        </a>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php else: ?>
                    <div class="text-center py-3">
                        <p class="text-muted">No attendance records yet.</p>
                    </div>
                <?php endif; ?>
            </div>
        <?php endif; ?>
    </div>
</div>

<!-- ========== JAVASCRIPT ========== -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
function updateDateTime() {
    const now = new Date();
    const formatted = now.toLocaleString('en-US', { 
        weekday: 'long', 
        year: 'numeric', 
        month: 'long', 
        day: 'numeric',
        hour: '2-digit',
        minute: '2-digit'
    });
    const el = document.getElementById('currentDateTime');
    if(el) el.innerHTML = '<i class="fas fa-calendar-alt"></i> ' + formatted;
}
updateDateTime();
setInterval(updateDateTime, 1000);

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

function toggleRequestDropdown() {
    let menu = document.getElementById("RequestDropdownMenu");
    let icon = document.getElementById("RequestDropdownIcon");
    
    if (menu.style.display === "block") {
        menu.style.display = "none";
        icon.style.transform = "rotate(0deg)";
    } else {
        menu.style.display = "block";
        icon.style.transform = "rotate(180deg)";
    }
}

function toggleReportDropdown() {
    let menu = document.getElementById("reportDropdownMenu");
    let icon = document.getElementById("reportDropdownIcon");
    
    if (menu.style.display === "block") {
        menu.style.display = "none";
        icon.style.transform = "rotate(0deg)";
    } else {
        menu.style.display = "block";
        icon.style.transform = "rotate(180deg)";
    }
}
</script>

</body>
</html>