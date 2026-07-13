<?php
// Secure version with all fixes - NO JQUERY DEPENDENCY
include 'db.php';

// 1. Enable session securely
if (session_status() === PHP_SESSION_NONE) {
    session_start([
        'cookie_httponly' => true,
        'cookie_secure' => false,  // Set to true in production with HTTPS
        'cookie_samesite' => 'Strict',
        'use_strict_mode' => true
    ]);
}

// 2. Disable error display in production
if (getenv('APP_ENV') === 'production') {
    error_reporting(0);
    ini_set('display_errors', 0);
} else {
    error_reporting(E_ALL);
    ini_set('display_errors', 1);
}

// 3. Generate CSRF token with proper expiry
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    $_SESSION['csrf_token_time'] = time();
} else if (time() - $_SESSION['csrf_token_time'] > 3600) {
    // Regenerate token every hour
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    $_SESSION['csrf_token_time'] = time();
}

// 4. Sanitize search input
$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$search = htmlspecialchars($search, ENT_QUOTES, 'UTF-8');

// 5. Validate search length
if (strlen($search) > 255) {
    $search = substr($search, 0, 255);
}

$search_condition = '';
$params = [];
$types = "";

if (!empty($search)) {
    $search_condition = "WHERE subject LIKE ? OR department LIKE ? OR teacher_name LIKE ? OR class LIKE ? OR shift LIKE ?";
    $search_param = "%$search%";
    $params = [$search_param, $search_param, $search_param, $search_param, $search_param];
    $types = "sssss";
}

// Get total schedules count securely
$total_query = "SELECT COUNT(*) as total FROM schedule_class";
$stmt = $conn->prepare($total_query);
$stmt->execute();
$total_result = $stmt->get_result();
$total_schedules = $total_result ? $total_result->fetch_assoc()['total'] : 0;

// 6. Secure AJAX endpoints
if(isset($_GET['ajax']) && $_GET['ajax'] == 'get_schedule' && isset($_GET['id'])) {
    // Verify request is AJAX
    if(empty($_SERVER['HTTP_X_REQUESTED_WITH']) || 
       strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) != 'xmlhttprequest') {
        http_response_code(403);
        exit;
    }
    
    $id = filter_var($_GET['id'], FILTER_VALIDATE_INT);
    if ($id === false || $id <= 0) {
        http_response_code(400);
        exit;
    }
    
    $stmt = $conn->prepare("SELECT * FROM schedule_class WHERE id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $result = $stmt->get_result();
    if($row = $result->fetch_assoc()) {
        header('Content-Type: application/json');
        echo json_encode($row);
    }
    exit;
}

// 7. Secure update endpoint with CSRF and validation
if($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_GET['ajax']) && $_GET['ajax'] == 'update_schedule') {
    // Verify CSRF token with timing-safe comparison
    if (!isset($_POST['csrf_token']) || 
        !hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) {
        http_response_code(403);
        echo json_encode(['success' => false, 'message' => 'CSRF token validation failed']);
        exit;
    }
    
    // Rate limiting
    if (!isset($_SESSION['last_update_time'])) {
        $_SESSION['last_update_time'] = 0;
    }
    if (time() - $_SESSION['last_update_time'] < 2) {
        http_response_code(429);
        echo json_encode(['success' => false, 'message' => 'Too many requests. Please wait.']);
        exit;
    }
    $_SESSION['last_update_time'] = time();
    
    // Validate and sanitize all inputs
    $id = filter_var($_POST['id'], FILTER_VALIDATE_INT);
    if ($id === false || $id <= 0) {
        echo json_encode(['success' => false, 'message' => 'Invalid ID']);
        exit;
    }
    
    $time_star = trim($_POST['time_star']);
    $time_end = trim($_POST['time_end']);
    $subject = trim($_POST['subject']);
    $department = trim($_POST['department']);
    $teacher_name = trim($_POST['teacher_name']);
    $class = trim($_POST['class']);
    $shift = trim($_POST['shift']);
    $year = trim($_POST['year']);
    $semester = trim($_POST['semester']);
    $date = trim($_POST['date']);
    
    $errors = [];
    
    // Validate date
    $date_obj = DateTime::createFromFormat('Y-m-d', $date);
    if (!$date_obj || $date_obj->format('Y-m-d') !== $date) {
        $errors[] = "Invalid date format";
    }
    
    // Validate time with proper regex
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
    
    // Validate against XSS and SQL injection
    $subject = htmlspecialchars($subject, ENT_QUOTES, 'UTF-8');
    $department = htmlspecialchars($department, ENT_QUOTES, 'UTF-8');
    $teacher_name = htmlspecialchars($teacher_name, ENT_QUOTES, 'UTF-8');
    $class = htmlspecialchars($class, ENT_QUOTES, 'UTF-8');
    $shift = htmlspecialchars($shift, ENT_QUOTES, 'UTF-8');
    $year = htmlspecialchars($year, ENT_QUOTES, 'UTF-8');
    $semester = htmlspecialchars($semester, ENT_QUOTES, 'UTF-8');
    
    // Validate teacher name
    if (empty($teacher_name) || strlen($teacher_name) < 2) {
        $errors[] = "Teacher name must be at least 2 characters";
    }
    if (strlen($teacher_name) > 100) {
        $errors[] = "Teacher name must be less than 100 characters";
    }
    $teacher_name = preg_replace('/[^a-zA-Z\s\.\-]/', '', $teacher_name);
    
    // Validate allowed values using whitelist - ALL SUBJECTS INCLUDED
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
        $errors[] = "Invalid subject";
    }
    
    $allowed_departments = ['it', 'Marketing', 'Electronics', 'Management', 'Electrical Engineering', 
                            'Accounting', 'Civil Engineering'];
    if (!in_array($department, $allowed_departments)) {
        $errors[] = "Invalid department";
    }
    
    // Class validation - allow com 1-5
    $class = strtolower($class);
    $allowed_classes = ['com 1', 'com 2', 'com 3', 'com 4', 'com 5'];
    
    if (!in_array($class, $allowed_classes)) {
        if (preg_match('/com\s*(\d+)/i', $class, $matches)) {
            $class_num = $matches[1];
            $class = 'com ' . $class_num;
            if (!in_array($class, $allowed_classes)) {
                $errors[] = "Invalid class";
            }
        } else {
            $errors[] = "Invalid class";
        }
    }
    
    // Shift validation
    $allowed_shifts = ['morning', 'evening'];
    if (!in_array($shift, $allowed_shifts)) {
        $errors[] = "Invalid shift";
    }
    
    // Year validation
    $allowed_years = ['year1', 'year2', 'year3', 'year4'];
    if (!in_array($year, $allowed_years)) {
        $errors[] = "Invalid year";
    }
    
    // Semester validation
    $allowed_semesters = ['1', '2'];
    if (!in_array($semester, $allowed_semesters)) {
        $errors[] = "Invalid semester";
    }
    
    if (!empty($errors)) {
        echo json_encode(['success' => false, 'message' => implode(', ', $errors)]);
        exit;
    }
    
    // Check for schedule conflicts with proper time overlap logic
    $conflict_stmt = $conn->prepare("SELECT id FROM schedule_class 
                                     WHERE date = ? 
                                     AND id != ?
                                     AND ((time_star <= ? AND time_end > ?) OR 
                                          (time_star < ? AND time_end >= ?) OR
                                          (time_star >= ? AND time_end <= ?))");
    $conflict_stmt->bind_param("sissssss", $date, $id, $time_end, $time_star, $time_end, $time_star, $time_star, $time_end);
    $conflict_stmt->execute();
    $conflict_result = $conflict_stmt->get_result();
    
    if ($conflict_result->num_rows > 0) {
        echo json_encode(['success' => false, 'message' => 'Schedule conflict detected! Please choose different time.']);
        exit;
    }
    
    // Update with prepared statement - INCLUDING TEACHER NAME
    $update_sql = "UPDATE schedule_class SET 
                   time_star=?, 
                   time_end=?, 
                   subject=?, 
                   department=?, 
                   teacher_name=?,
                   class=?, 
                   shift=?, 
                   year=?, 
                   semester=?, 
                   date=?
                   WHERE id=?";
    
    $stmt = $conn->prepare($update_sql);
    $stmt->bind_param("ssssssssssi", 
        $time_star, 
        $time_end, 
        $subject, 
        $department, 
        $teacher_name,
        $class, 
        $shift, 
        $year, 
        $semester, 
        $date, 
        $id
    );
    
    if($stmt->execute()) {
        // Log the update for audit
        error_log("Schedule updated: ID $id by user " . ($_SESSION['user_id'] ?? 'unknown'));
        echo json_encode(['success' => true, 'message' => 'Schedule updated successfully']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Error: ' . $stmt->error]);
    }
    exit;
}

// 8. Secure DELETE endpoint
if(isset($_GET['ajax']) && $_GET['ajax'] == 'delete_schedule' && isset($_GET['id'])) {
    // Verify CSRF token
    if (!isset($_GET['csrf_token']) || 
        !hash_equals($_SESSION['csrf_token'], $_GET['csrf_token'])) {
        http_response_code(403);
        echo json_encode(['success' => false, 'message' => 'CSRF token validation failed']);
        exit;
    }
    
    $id = filter_var($_GET['id'], FILTER_VALIDATE_INT);
    if ($id === false || $id <= 0) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Invalid ID']);
        exit;
    }
    
    // Use prepared statement for deletion
    $stmt = $conn->prepare("DELETE FROM schedule_class WHERE id = ?");
    $stmt->bind_param("i", $id);
    if($stmt->execute()) {
        error_log("Schedule deleted: ID $id by user " . ($_SESSION['user_id'] ?? 'unknown'));
        echo json_encode(['success' => true, 'message' => 'Schedule deleted successfully']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Error deleting schedule']);
    }
    exit;
}

// Build the main query with prepared statement
if (!empty($search)) {
    $sql = "SELECT * FROM schedule_class $search_condition ORDER BY date DESC, time_star ASC";
    $stmt = $conn->prepare($sql);
    if (!empty($params)) {
        $stmt->bind_param($types, ...$params);
    }
    $stmt->execute();
    $result = $stmt->get_result();
} else {
    $sql = "SELECT * FROM schedule_class ORDER BY date DESC, time_star ASC";
    $result = mysqli_query($conn, $sql);
}

// Check for query error
if (!$result) {
    die("Query failed: " . mysqli_error($conn));
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
    <title>KRaksa Education Suite - Class Schedule</title>
    <style>
        :root {
            --primary-gradient: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            --secondary-gradient: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);
            --third-gradient: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%);
            --sidebar-bg: linear-gradient(90deg, rgba(117, 82, 243, 1) 19%, rgba(64, 24, 157, 1) 95%);
        }

        * { margin: 0; padding: 0; box-sizing: border-box; }
        a { text-decoration: none; }
        
        body { 
            font-family: "Inter", sans-serif; 
            background: #c5e1fc; 
            color: #0f172a; 
            overflow-x: hidden; 
        }
        
        .container { display: flex; min-height: 100vh; max-width: 100%; }
        
        /* Sidebar Styles */
        .sidebar { 
            width: 300px; 
            background: var(--sidebar-bg);
            color: #e2e8f0; 
            flex-shrink: 0; 
            position: sticky; 
            top: 0; 
            height: 100vh; 
            overflow-y: auto; 
            box-shadow: 4px 0 20px rgba(0, 0, 0, 0.08); 
            z-index: 10;
            margin-left: -12px;
            transition: all 0.3s ease;
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
        
        .nav-menu { flex: 1; padding: 0 16px; }
        
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
        
        .dropdown-container {
            margin-bottom: 10px;
        }
        
        .dropdown-btn {
            display: flex;
            justify-content: space-between;
            align-items: center;
            cursor: pointer;
        }
        
        .dropdown-menus {
            display: none;
            padding-left: 15px;
            margin-top: 5px;
        }
        
        .sub-menu {
            font-size: 14px;
            padding: 10px 15px;
            margin-bottom: 5px;
            background: rgba(88, 30, 248, 0.61);
        }
        
        .sub-menu:hover {
            background: rgba(110, 41, 238, 0.6);
        }
        
        .dropdown-icon {
            transition: 0.3s;
        }
        
        .nav-bottom { 
            margin-top: 30px; 
            padding-top: 20px; 
            border-top: 1px solid rgba(255,255,255,0.1); 
        }
        
        /* Main Content */
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
        
        /* KPI Cards */
        .kpi-row { 
            display: flex; 
            flex-wrap: wrap; 
            gap: 20px; 
            margin-bottom: 32px; 
        }
        
        .kpi-card { 
            background: var(--primary-gradient);
            border-radius: 20px; 
            padding: 20px 24px; 
            flex: 1; 
            min-width: 180px;
            color: white;
            box-shadow: 0 10px 25px rgba(0,0,0,0.1);
            transition: transform 0.3s ease;
        }
        
        .kpi-card:hover {
            transform: translateY(-5px);
        }
        
        .kpi-card:nth-child(2) { background: var(--secondary-gradient); }
        .kpi-card:nth-child(3) { background: var(--third-gradient); }
        
        .kpi-title { 
            font-size: 14px; 
            text-transform: uppercase; 
            letter-spacing: 1px; 
            opacity: 0.9; 
            margin-bottom: 10px; 
            font-weight: 500; 
        }
        
        .kpi-number { 
            font-size: 32px; 
            font-weight: bold; 
            margin-bottom: 5px; 
        }
        
        .kpi-subtitle {
            font-size: 12px;
            opacity: 0.8;
        }
        
        /* Search Box */
        .search-box {
            display: flex;
            gap: 10px;
            align-items: center;
            margin-bottom: 20px;
            padding: 10px;
            background: white;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
        }
        
        .search-box input {
            border-radius: 30px;
            padding: 8px 20px;
            border: 1px solid #e0e4e8;
            width: 100%;
            transition: all 0.2s ease;
        }
        
        .search-box input:focus {
            border-color: #667eea;
            box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
            outline: none;
        }
        
        .search-box button {
            border-radius: 30px;
            padding: 8px 20px;
            background: var(--primary-gradient);
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
        
        .result-count {
            font-size: 14px;
            color: #6c757d;
            margin-left: 15px;
        }
        
        /* Table */
        .table-container {
            max-height: 500px;
            overflow-y: auto;
            border: 1px solid #dee2e6;
            border-radius: 8px;
            background: white;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
        }

        .table-container thead th {
            position: sticky;
            top: 0;
            background: var(--primary-gradient);
            color: white;
            z-index: 1;
            border-bottom: 2px solid #dee2e6;
            padding: 15px;
            font-weight: 600;
            font-size: 14px;
            border: none;
        }
        
        .table {
            margin-bottom: 0;
        }
        
        .table tbody tr:hover {
            background: #f8f9fc;
        }
        
        .table tbody td {
            padding: 12px 15px;
            vertical-align: middle;
            border-bottom: 1px solid #eef2f7;
            color: #2c3e50;
        }
        
        .schedule-badge {
            display: inline-block;
            padding: 5px 10px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
        }
        
        .badge-morning { background: #e3f2fd; color: #1976d2; }
        .badge-evening { background: #fff3e0; color: #f57c00; }
        
        /* Form */
        .form-container {
            background: white;
            border-radius: 20px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.08);
            margin-top: 40px;
            overflow: hidden;
        }
        
        .header-banner {
            background: var(--primary-gradient);
            color: white;
            padding: 18px 25px;
            font-size: 20px;
            font-weight: 600;
        }
        
        .form-body {
            padding: 25px;
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
        
        .btn-primary {
            background: var(--primary-gradient);
            border: none;
            padding: 10px 25px;
            border-radius: 10px;
            font-weight: 600;
        }
        
        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(102, 126, 234, 0.4);
        }
        
        .btn-warning, .btn-danger {
            padding: 5px 12px;
            font-size: 12px;
            border-radius: 8px;
            margin: 0 3px;
        }
        
        /* Modal */
        .modal-custom .modal-content {
            border-radius: 20px;
            border: none;
        }
        
        .modal-custom .modal-header {
            background: var(--primary-gradient);
            color: white;
            border-radius: 20px 20px 0 0;
            border: none;
        }
        
        .modal-custom .modal-header .btn-close {
            filter: brightness(0) invert(1);
        }
        
        .modal-custom .modal-body {
            padding: 25px;
        }
        
        .loading-opacity { 
            opacity: 0.6; 
            pointer-events: none; 
        }
        
        /* Toast notifications */
        .toast-container {
            position: fixed;
            top: 20px;
            right: 20px;
            z-index: 9999;
        }
        
        @keyframes slideIn {
            from {
                transform: translateX(100%);
                opacity: 0;
            }
            to {
                transform: translateX(0);
                opacity: 1;
            }
        }
        
        /* Mobile responsive */
        .hamburger-menu {
            display: none;
            background: none;
            border: none;
            font-size: 24px;
            color: #1e293b;
            cursor: pointer;
        }
        
        @media (max-width: 768px) {
            .sidebar {
                width: 80px;
                margin-left: 0;
            }
            
            .sidebar-header h1, 
            .sidebar-header p, 
            .nav-item span { 
                display: none; 
            }
            
            .nav-item { 
                justify-content: center; 
            }
            
            .main-content { 
                padding: 15px; 
            }
            
            .hamburger-menu {
                display: block;
            }
            
            .search-box {
                flex-wrap: wrap;
            }
            
            .search-box input {
                width: 100%;
            }
            
            .kpi-card {
                min-width: 150px;
            }
        }
        
        /* Scrollbar styling */
        .table-container::-webkit-scrollbar {
            width: 8px;
            height: 8px;
        }
        
        .table-container::-webkit-scrollbar-track {
            background: #f1f1f1;
            border-radius: 10px;
        }
        
        .table-container::-webkit-scrollbar-thumb {
            background: #888;
            border-radius: 10px;
        }
        
        .table-container::-webkit-scrollbar-thumb:hover {
            background: #555;
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
                <a href="index.php" class="nav-item"><i class="fas fa-tachometer-alt"></i> <span>Main Dashboard</span></a>
                
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
                        <a href="score.php" class="nav-item sub-menu">
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
                
                <a href="teacher.php" class="nav-item"><i class="fas fa-chalkboard-teacher"></i> <span>Teachers</span></a>
                <a href="Courses.php" class="nav-item"><i class="fas fa-graduation-cap"></i> <span>Department</span></a>
                <a href="schedule.php" class="nav-item active"><i class="fas fa-calendar-alt"></i> <span>Schedule class</span></a>
                
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
                 <a href="attendance_admin.php" class="nav-item "><i class="fas fa-clipboard-check"></i> <span>Attendance Admin</span></a>
                
                <div class="nav-bottom">
                    <a href="logout.php" class="nav-item" style="padding-left:8px;">
                        <i class="fas fa-sign-out-alt"></i> <span>Logout</span>
                    </a>
                </div>
            </div>
        </div>
        
        <!-- Main Content -->
        <div class="main-content">
            <div class="top-bar">
                <div class="page-title">
                    <h2><i class="fas fa-calendar-alt"></i> Class Schedule Management</h2>
                </div>
                <button class="hamburger-menu" onclick="toggleSidebar()">
                    <i class="fas fa-bars"></i>
                </button>
            </div>
            
            <!-- Display Success/Error Messages -->
            <?php if(isset($_GET['success'])): ?>
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    <i class="fas fa-check-circle"></i> <?php echo htmlspecialchars($_GET['success']); ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            <?php endif; ?>

            <?php if(isset($_GET['error'])): ?>
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    <i class="fas fa-exclamation-circle"></i> <?php echo htmlspecialchars($_GET['error']); ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            <?php endif; ?>
            
            <!-- KPI Cards -->
            <div class="kpi-row">
                <div class="kpi-card">
                    <div class="kpi-title">Total Classes</div>
                    <div class="kpi-number"><?php echo $total_schedules; ?></div>
                    <div class="kpi-subtitle">Scheduled Classes</div>
                </div>
                <div class="kpi-card">
                    <div class="kpi-title">Departments</div>
                    <div class="kpi-number">7</div>
                    <div class="kpi-subtitle">IT, Marketing, Electronics, Management, Electrical Engineering, Accounting, Civil Engineering</div>
                </div>
                <div class="kpi-card">
                    <div class="kpi-title">Subjects</div>
                    <div class="kpi-number">45+</div>
                    <div class="kpi-subtitle">All Subjects Available</div>
                </div>
            </div>
            
            <!-- Search Box -->
            <div class="search-box">
                <form method="GET" action="" style="display: flex; gap: 10px; width: 100%; flex-wrap: wrap;">
                    <input type="text" name="search" placeholder="🔍 Search by subject, department, teacher, class or shift..." 
                           value="<?php echo htmlspecialchars($search); ?>" autocomplete="off" style="flex: 1; min-width: 200px;">
                    <button type="submit"><i class="fas fa-search"></i> Search</button>
                    <?php if(!empty($search)): ?>
                        <a href="schedule.php" class="clear-search"><i class="fas fa-times"></i> Clear</a>
                    <?php endif; ?>
                </form>
            </div>
            
            <?php if(!empty($search)): ?>
                <div class="result-count mb-3">
                    <i class="fas fa-search"></i> Search results for: <strong>"<?php echo htmlspecialchars($search); ?>"</strong>
                    (<?php echo mysqli_num_rows($result); ?> records found)
                </div>
            <?php endif; ?>
            
            <!-- Table -->
            <div class="table-container">
                <table class="table table-bordered table-striped mb-0">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Date</th>
                            <th>Start Time</th>
                            <th>End Time</th>
                            <th>Subject</th>
                            <th>Department</th>
                            <th>Teacher Name</th>
                            <th>Class</th>
                            <th>Shift</th>
                            <th>Year</th>
                            <th>Semester</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        if (mysqli_num_rows($result) > 0) {
                            while($row = mysqli_fetch_assoc($result)) {
                                $shiftClass = ($row['shift'] == 'morning' || $row['shift'] == '1') ? 'Morning' : 'Evening';
                                $shiftBadge = ($row['shift'] == 'morning' || $row['shift'] == '1') ? 'badge-morning' : 'badge-evening';
                                echo "<tr>";
                                echo "<td>" . $row['id'] . "</td>";
                                echo "<td>" . date('d-m-Y', strtotime($row['date'])) . "</td>";
                                echo "<td>" . date('h:i A', strtotime($row['time_star'])) . "</td>";
                                echo "<td>" . date('h:i A', strtotime($row['time_end'])) . "</td>";
                                echo "<td><span class='schedule-badge' style='background:#e8eaf6; color:#3949ab;'>" . strtoupper(htmlspecialchars($row['subject'])) . "</span></td>";
                                echo "<td>" . htmlspecialchars($row['department']) . "</td>";
                                echo "<td><strong>" . htmlspecialchars($row['teacher_name']) . "</strong></td>";
                                echo "<td><span class='schedule-badge' style='background:#e0f2f1; color:#00695c;'>" . htmlspecialchars($row['class']) . "</span></td>";
                                echo "<td><span class='schedule-badge $shiftBadge'>" . $shiftClass . "</span></td>";
                                echo "<td>" . htmlspecialchars($row['year']) . "</td>";
                                echo "<td>Semester " . htmlspecialchars($row['semester']) . "</td>";
                                echo "<td>
                                        <button type='button' class='btn btn-warning btn-sm edit-btn mb-2' data-id='" . $row['id'] . "'>
                                            <i class='fas fa-edit'></i>
                                        </button>
                                        <button type='button' class='btn btn-danger btn-sm delete-btn' 
                                                data-id='" . $row['id'] . "' 
                                                data-csrf='" . $_SESSION['csrf_token'] . "'>
                                            <i class='fas fa-trash'></i>
                                        </button>
                                       ";
                                echo "</tr>";
                            }
                        } else {
                            echo "<tr><td colspan='12' class='text-center py-4'>No schedule found</td></tr>";
                        }
                        ?>
                    </tbody>
                </table>
            </div>
            
            <!-- Add Schedule Form -->
            <div class="form-container">
                <div class="header-banner">
                    <i class="fas fa-plus-circle"></i> Add New Schedule
                </div>
                
                <form action="process_schedule.php" method="POST" class="form-body" id="addScheduleForm">
                    <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
                    
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label class="form-label">Date <span class="text-danger">*</span></label>
                            <input type="date" name="date" class="form-control" required min="<?php echo date('Y-m-d'); ?>">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Start Time <span class="text-danger">*</span></label>
                            <input type="time" name="time_star" class="form-control" required>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">End Time <span class="text-danger">*</span></label>
                            <input type="time" name="time_end" class="form-control" required>
                        </div>
                    </div>
                    
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label class="form-label">Subject <span class="text-danger">*</span></label>
                            <select name="subject" class="form-select" required>
                                <option selected disabled>Select Subject</option>
                                <optgroup label="Programming & IT">
                                    <option value="php">PHP</option>
                                    <option value="Java">Java</option>
                                    <option value="Cisco">Cisco</option>
                                    <option value="js">JavaScript</option>
                                    <option value="html/css">HTML/CSS</option>
                                    <option value="c#">C#</option>
                                    <option value="mysql">MySQL</option>
                                    <option value="c++">C++</option>
                                    <option value="vb.net">VB.NET</option>
                                </optgroup>
                                <optgroup label="Engineering">
                                    <option value="Calculus">Calculus</option>
                                    <option value="Physics">Physics</option>
                                    <option value="Chemistry">Chemistry</option>
                                    <option value="Engineering Drawing">Engineering Drawing</option>
                                    <option value="Computer Programming">Computer Programming</option>
                                    <option value="Engineering Mechanics">Engineering Mechanics</option>
                                    <option value="Communication Skills">Communication Skills</option>
                                    <option value="Electronic Circuits">Electronic Circuits</option>
                                    <option value="Analog Electronics">Analog Electronics</option>
                                    <option value="Digital Electronics">Digital Electronics</option>
                                    <option value="Microprocessors">Microprocessors</option>
                                    <option value="Embedded Systems">Embedded Systems</option>
                                    <option value="Sensors and Instrumentation">Sensors and Instrumentation</option>
                                    <option value="Communication Systems">Communication Systems</option>
                                </optgroup>
                                <optgroup label="Business & Marketing">
                                    <option value="Principles of Marketing">Principles of Marketing</option>
                                    <option value="Consumer Behavior">Consumer Behavior</option>
                                    <option value="Digital Marketing">Digital Marketing</option>
                                    <option value="Social Media Marketing">Social Media Marketing</option>
                                    <option value="Market Research">Market Research</option>
                                    <option value="Brand Management">Brand Management</option>
                                    <option value="Sales Management">Sales Management</option>
                                    <option value="Advertising">Advertising</option>
                                </optgroup>
                                <optgroup label="Management">
                                    <option value="Principles of Management">Principles of Management</option>
                                    <option value="Human Resource Management">Human Resource Management</option>
                                    <option value="Organizational Behavior">Organizational Behavior</option>
                                    <option value="Business Strategy">Business Strategy</option>
                                    <option value="Leadership">Leadership</option>
                                    <option value="Operations Management">Operations Management</option>
                                    <option value="Entrepreneurship">Entrepreneurship</option>
                                </optgroup>
                                <optgroup label="Accounting & Finance">
                                    <option value="Financial Accounting">Financial Accounting</option>
                                    <option value="Managerial Accounting">Managerial Accounting</option>
                                    <option value="Cost Accounting">Cost Accounting</option>
                                    <option value="Auditing">Auditing</option>
                                    <option value="Taxation">Taxation</option>
                                    <option value="Financial Management">Financial Management</option>
                                </optgroup>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Department <span class="text-danger">*</span></label>
                            <select name="department" class="form-select" required>
                                <option selected disabled>Select Department</option>
                                <option value="it">Information Technology</option>
                                <option value="Marketing">Marketing</option>
                                <option value="Electronics">Electronics</option>
                                <option value="Management">Management</option>
                                <option value="Electrical Engineering">Electrical Engineering</option>
                                <option value="Accounting">Accounting</option>
                                <option value="Civil Engineering">Civil Engineering</option>
                            </select>
                        </div>
                    </div>
                    
                    <div class="row mb-3">
                        <div class="col-md-12">
                            <label class="form-label">Teacher Name <span class="text-danger">*</span></label>
                            <input type="text" name="teacher_name" class="form-control" placeholder="Enter teacher name (e.g., Mr. John Doe)" required>
                            <small class="text-muted">Enter the full name of the teacher</small>
                        </div>
                    </div>
                    
                    <div class="row mb-3">
                        <div class="col-md-4">
                            <label class="form-label">Class <span class="text-danger">*</span></label>
                            <select name="class" class="form-select" required>
                                <option selected disabled>Select Class</option>
                                <option value="com 1">Com 1</option>
                                <option value="com 2">Com 2</option>
                                <option value="com 3">Com 3</option>
                                <option value="com 4">Com 4</option>
                                <option value="com 5">Com 5</option>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Shift <span class="text-danger">*</span></label>
                            <select name="shift" class="form-select" required>
                                <option selected disabled>Select Shift</option>
                                <option value="morning">Morning</option>
                                <option value="evening">Evening</option>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Year <span class="text-danger">*</span></label>
                            <select name="year" class="form-select" required>
                                <option selected disabled>Select Year</option>
                                <option value="year1">Year 1</option>
                                <option value="year2">Year 2</option>
                                <option value="year3">Year 3</option>
                                <option value="year4">Year 4</option>
                            </select>
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Semester <span class="text-danger">*</span></label>
                        <select name="semester" class="form-select" required>
                            <option selected disabled>Select Semester</option>
                            <option value="1">Semester 1</option>
                            <option value="2">Semester 2</option>
                        </select>
                    </div>
                    
                    <div class="mt-4">
                        <button type="submit" class="btn btn-primary px-4">
                            <i class="fas fa-save"></i> Save Schedule
                        </button>
                        <button type="reset" class="btn btn-secondary px-4">
                            <i class="fas fa-undo"></i> Clear
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    
    <!-- Edit Schedule Modal -->
    <div class="modal fade modal-custom" id="editScheduleModal" tabindex="-1" aria-labelledby="editScheduleModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">
                        <i class="fas fa-edit"></i> Edit Schedule
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <form id="editScheduleForm">
                        <input type="hidden" id="edit_id" name="id">
                        <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
                        
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label class="form-label">Date</label>
                                <input type="date" id="edit_date" name="date" class="form-control">
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">Start Time</label>
                                <input type="time" id="edit_time_star" name="time_star" class="form-control">
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">End Time</label>
                                <input type="time" id="edit_time_end" name="time_end" class="form-control">
                            </div>
                        </div>
                        
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label class="form-label">Subject</label>
                                <select id="edit_subject" name="subject" class="form-select">
                                    <optgroup label="Programming & IT">
                                        <option value="php">PHP</option>
                                        <option value="Java">Java</option>
                                        <option value="Cisco">Cisco</option>
                                        <option value="js">JavaScript</option>
                                        <option value="html/css">HTML/CSS</option>
                                        <option value="c#">C#</option>
                                        <option value="mysql">MySQL</option>
                                        <option value="c++">C++</option>
                                        <option value="vb.net">VB.NET</option>
                                    </optgroup>
                                    <optgroup label="Engineering">
                                        <option value="Calculus">Calculus</option>
                                        <option value="Physics">Physics</option>
                                        <option value="Chemistry">Chemistry</option>
                                        <option value="Engineering Drawing">Engineering Drawing</option>
                                        <option value="Computer Programming">Computer Programming</option>
                                        <option value="Engineering Mechanics">Engineering Mechanics</option>
                                        <option value="Communication Skills">Communication Skills</option>
                                        <option value="Electronic Circuits">Electronic Circuits</option>
                                        <option value="Analog Electronics">Analog Electronics</option>
                                        <option value="Digital Electronics">Digital Electronics</option>
                                        <option value="Microprocessors">Microprocessors</option>
                                        <option value="Embedded Systems">Embedded Systems</option>
                                        <option value="Sensors and Instrumentation">Sensors and Instrumentation</option>
                                        <option value="Communication Systems">Communication Systems</option>
                                    </optgroup>
                                    <optgroup label="Business & Marketing">
                                        <option value="Principles of Marketing">Principles of Marketing</option>
                                        <option value="Consumer Behavior">Consumer Behavior</option>
                                        <option value="Digital Marketing">Digital Marketing</option>
                                        <option value="Social Media Marketing">Social Media Marketing</option>
                                        <option value="Market Research">Market Research</option>
                                        <option value="Brand Management">Brand Management</option>
                                        <option value="Sales Management">Sales Management</option>
                                        <option value="Advertising">Advertising</option>
                                    </optgroup>
                                    <optgroup label="Management">
                                        <option value="Principles of Management">Principles of Management</option>
                                        <option value="Human Resource Management">Human Resource Management</option>
                                        <option value="Organizational Behavior">Organizational Behavior</option>
                                        <option value="Business Strategy">Business Strategy</option>
                                        <option value="Leadership">Leadership</option>
                                        <option value="Operations Management">Operations Management</option>
                                        <option value="Entrepreneurship">Entrepreneurship</option>
                                    </optgroup>
                                    <optgroup label="Accounting & Finance">
                                        <option value="Financial Accounting">Financial Accounting</option>
                                        <option value="Managerial Accounting">Managerial Accounting</option>
                                        <option value="Cost Accounting">Cost Accounting</option>
                                        <option value="Auditing">Auditing</option>
                                        <option value="Taxation">Taxation</option>
                                        <option value="Financial Management">Financial Management</option>
                                    </optgroup>
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Department</label>
                                <select id="edit_department" name="department" class="form-select">
                                    <option value="it">Information Technology</option>
                                    <option value="Marketing">Marketing</option>
                                    <option value="Electronics">Electronics</option>
                                    <option value="Management">Management</option>
                                    <option value="Electrical Engineering">Electrical Engineering</option>
                                    <option value="Accounting">Accounting</option>
                                    <option value="Civil Engineering">Civil Engineering</option>
                                </select>
                            </div>
                        </div>
                        
                        <div class="row mb-3">
                            <div class="col-md-12">
                                <label class="form-label">Teacher Name</label>
                                <input type="text" id="edit_teacher_name" name="teacher_name" class="form-control" placeholder="Enter teacher name">
                            </div>
                        </div>
                        
                        <div class="row mb-3">
                            <div class="col-md-3">
                                <label class="form-label">Class</label>
                                <select id="edit_class" name="class" class="form-select">
                                    <option value="com 1">Com 1</option>
                                    <option value="com 2">Com 2</option>
                                    <option value="com 3">Com 3</option>
                                    <option value="com 4">Com 4</option>
                                    <option value="com 5">Com 5</option>
                                </select>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">Shift</label>
                                <select id="edit_shift" name="shift" class="form-select">
                                    <option value="morning">Morning</option>
                                    <option value="evening">Evening</option>
                                </select>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">Year</label>
                                <select id="edit_year" name="year" class="form-select">
                                    <option value="year1">Year 1</option>
                                    <option value="year2">Year 2</option>
                                    <option value="year3">Year 3</option>
                                    <option value="year4">Year 4</option>
                                </select>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">Semester</label>
                                <select id="edit_semester" name="semester" class="form-select">
                                    <option value="1">Semester 1</option>
                                    <option value="2">Semester 2</option>
                                </select>
                            </div>
                        </div>
                        
                        <div class="mt-4 d-flex justify-content-end gap-2">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                                <i class="fas fa-times"></i> Cancel
                            </button>
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-save"></i> Update Schedule
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Toast Container -->
    <div class="toast-container"></div>
    
    <!-- Bootstrap JS only - No jQuery dependency -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    
    <!-- All JavaScript - Pure Vanilla, No jQuery -->
    <script>
         function toggleReportDropdown() {
            let menu = document.getElementById("reportDropdownMenu");
            let icon = document.getElementById("reportDropdownIcon");
            
            if (menu.style.display === "block") {
                menu.style.display = "none";
                icon.style.transform = "rotate(0deg)";
            } else {
                // Close other dropdowns first
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
        // Toggle Student Dropdown
        function toggleDropdown() {
            let menu = document.getElementById("studentDropdown");
            let icon = document.getElementById("dropdownIcon");
            
            if(menu.style.display === "block") {
                menu.style.display = "none";
                icon.style.transform = "rotate(0deg)";
            } else {
                menu.style.display = "block";
                icon.style.transform = "rotate(180deg)";
            }
        }
        
        // Toggle Request Dropdown
        function toggleRequestDropdown() {
            let menu = document.getElementById("RequestDropdownMenu");
            let icon = document.getElementById("RequestDropdownIcon");
            
            if (menu.style.display === "block") {
                menu.style.display = "none";
                icon.style.transform = "rotate(0deg)";
            } else {
                let studentMenu = document.getElementById("studentDropdown");
                let studentIcon = document.getElementById("dropdownIcon");
                if (studentMenu) {
                    studentMenu.style.display = "none";
                    if (studentIcon) studentIcon.style.transform = "rotate(0deg)";
                }
                menu.style.display = "block";
                icon.style.transform = "rotate(180deg)";
            }
        }
        
        // Toggle Sidebar on Mobile
        function toggleSidebar() {
            let sidebar = document.querySelector('.sidebar');
            if (sidebar.style.display === "none" || sidebar.style.display === "") {
                sidebar.style.display = "block";
                sidebar.style.width = "300px";
            } else {
                sidebar.style.display = "none";
            }
        }
        
        // Show Toast Notification
        function showToast(message, type = 'success') {
            const container = document.querySelector('.toast-container');
            const colors = {
                success: '#28a745',
                error: '#dc3545',
                warning: '#ffc107'
            };
            
            const toast = document.createElement('div');
            toast.className = 'toast show';
            toast.style.cssText = `
                background: ${colors[type] || '#28a745'};
                color: white;
                padding: 12px 20px;
                border-radius: 8px;
                margin-bottom: 10px;
                box-shadow: 0 4px 12px rgba(0,0,0,0.15);
                animation: slideIn 0.3s ease;
            `;
            toast.textContent = message;
            container.appendChild(toast);
            
            setTimeout(() => {
                toast.style.opacity = '0';
                toast.style.transition = 'opacity 0.3s ease';
                setTimeout(() => toast.remove(), 300);
            }, 4000);
        }
        
        // Wait for DOM to be fully loaded
        document.addEventListener('DOMContentLoaded', function() {
            // Handle edit button click - using event delegation for dynamic elements
            document.addEventListener('click', function(e) {
                // Edit button click
                if (e.target.closest('.edit-btn')) {
                    const btn = e.target.closest('.edit-btn');
                    const scheduleId = btn.dataset.id;
                    const modalBody = document.querySelector('#editScheduleModal .modal-body');
                    
                    if (modalBody) {
                        modalBody.classList.add('loading-opacity');
                    }
                    
                    // Use fetch API instead of jQuery AJAX
                    fetch(window.location.pathname + '?ajax=get_schedule&id=' + scheduleId, {
                        headers: {
                            'X-Requested-With': 'XMLHttpRequest'
                        }
                    })
                    .then(response => {
                        if (!response.ok) {
                            throw new Error('Network response was not ok');
                        }
                        return response.json();
                    })
                    .then(data => {
                        // Set values for all fields
                        document.getElementById('edit_id').value = data.id || '';
                        document.getElementById('edit_date').value = data.date || '';
                        document.getElementById('edit_time_star').value = data.time_star || '';
                        document.getElementById('edit_time_end').value = data.time_end || '';
                        
                        // Set select values - make sure they match the option values
                        if (data.subject) {
                            const subjectSelect = document.getElementById('edit_subject');
                            if (subjectSelect) {
                                subjectSelect.value = data.subject;
                            }
                        }
                        
                        if (data.department) {
                            const deptSelect = document.getElementById('edit_department');
                            if (deptSelect) {
                                deptSelect.value = data.department;
                            }
                        }
                        
                        if (data.teacher_name) {
                            document.getElementById('edit_teacher_name').value = data.teacher_name;
                        }
                        
                        if (data.class) {
                            const classSelect = document.getElementById('edit_class');
                            if (classSelect) {
                                classSelect.value = data.class;
                            }
                        }
                        
                        if (data.shift) {
                            const shiftSelect = document.getElementById('edit_shift');
                            if (shiftSelect) {
                                shiftSelect.value = data.shift;
                            }
                        }
                        
                        if (data.year) {
                            const yearSelect = document.getElementById('edit_year');
                            if (yearSelect) {
                                yearSelect.value = data.year;
                            }
                        }
                        
                        if (data.semester) {
                            const semesterSelect = document.getElementById('edit_semester');
                            if (semesterSelect) {
                                semesterSelect.value = data.semester;
                            }
                        }
                        
                        // Show modal using Bootstrap JS
                        var modal = new bootstrap.Modal(document.getElementById('editScheduleModal'));
                        modal.show();
                        
                        if (modalBody) {
                            modalBody.classList.remove('loading-opacity');
                        }
                    })
                    .catch(error => {
                        console.error('Error:', error);
                        showToast('Error loading schedule data. Please try again.', 'error');
                        if (modalBody) {
                            modalBody.classList.remove('loading-opacity');
                        }
                    });
                }
                
                // Delete button click
                if (e.target.closest('.delete-btn')) {
                    const btn = e.target.closest('.delete-btn');
                    const scheduleId = btn.dataset.id;
                    const csrfToken = btn.dataset.csrf;
                    
                    if (confirm('Are you sure you want to delete this schedule?')) {
                        fetch(window.location.pathname + '?ajax=delete_schedule&id=' + scheduleId + '&csrf_token=' + csrfToken, {
                            headers: {
                                'X-Requested-With': 'XMLHttpRequest'
                            }
                        })
                        .then(response => response.json())
                        .then(data => {
                            if (data.success) {
                                showToast(data.message, 'success');
                                setTimeout(() => location.reload(), 1000);
                            } else {
                                showToast('Error: ' + data.message, 'error');
                            }
                        })
                        .catch(error => {
                            console.error('Error:', error);
                            showToast('Error deleting schedule. Please try again.', 'error');
                        });
                    }
                }
            });
            
            // Handle form submission for update
            const editForm = document.getElementById('editScheduleForm');
            if (editForm) {
                editForm.addEventListener('submit', function(e) {
                    e.preventDefault();
                    
                    var submitBtn = this.querySelector('button[type="submit"]');
                    var originalText = submitBtn.innerHTML;
                    submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Updating...';
                    submitBtn.disabled = true;
                    
                    // Create FormData object
                    var formData = new FormData(this);
                    
                    // Use fetch API
                    fetch(window.location.pathname + '?ajax=update_schedule', {
                        method: 'POST',
                        body: formData,
                        headers: {
                            'X-Requested-With': 'XMLHttpRequest'
                        }
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            showToast(data.message, 'success');
                            setTimeout(() => location.reload(), 1000);
                        } else {
                            showToast('Error: ' + data.message, 'error');
                            submitBtn.innerHTML = originalText;
                            submitBtn.disabled = false;
                        }
                    })
                    .catch(error => {
                        console.error('Error:', error);
                        showToast('Error updating schedule. Please try again.', 'error');
                        submitBtn.innerHTML = originalText;
                        submitBtn.disabled = false;
                    });
                });
            }
            
            // Reset form on modal close
            const editModal = document.getElementById('editScheduleModal');
            if (editModal) {
                editModal.addEventListener('hidden.bs.modal', function() {
                    const editForm = document.getElementById('editScheduleForm');
                    if (editForm) {
                        editForm.reset();
                    }
                });
            }
            
            // Validate time fields in add form
            const addForm = document.getElementById('addScheduleForm');
            if (addForm) {
                addForm.addEventListener('submit', function(e) {
                    var timeStar = this.querySelector('input[name="time_star"]').value;
                    var timeEnd = this.querySelector('input[name="time_end"]').value;
                    
                    if (timeStar && timeEnd && timeStar >= timeEnd) {
                        e.preventDefault();
                        showToast('End time must be after start time!', 'error');
                        return false;
                    }
                });
            }
            
            // Validate time fields in edit form
            const editTimeStar = document.getElementById('edit_time_star');
            const editTimeEnd = document.getElementById('edit_time_end');
            
            if (editTimeStar) {
                editTimeStar.addEventListener('change', validateEditTime);
            }
            if (editTimeEnd) {
                editTimeEnd.addEventListener('change', validateEditTime);
            }
            
            function validateEditTime() {
                var timeStar = document.getElementById('edit_time_star').value;
                var timeEnd = document.getElementById('edit_time_end').value;
                
                if (timeStar && timeEnd && timeStar >= timeEnd) {
                    showToast('End time must be after start time!', 'warning');
                }
            }
        });
    </script>
</body>
</html>