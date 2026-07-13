<?php
include 'db.php';
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Handle search
$search = isset($_GET['search']) ? mysqli_real_escape_string($conn, $_GET['search']) : '';
$year_filter = isset($_GET['year_filter']) ? mysqli_real_escape_string($conn, $_GET['year_filter']) : '';
$college_filter = isset($_GET['college_filter']) ? mysqli_real_escape_string($conn, $_GET['college_filter']) : '';

$sql = "SELECT * FROM students WHERE 1=1";

if (!empty($search)) {
    $sql .= " AND (name LIKE '%$search%' OR email LIKE '%$search%' OR phone LIKE '%$search%')";
}

if (!empty($year_filter)) {
    $sql .= " AND year = '$year_filter'";
}

if (!empty($college_filter)) {
    $sql .= " AND college = '$college_filter'";
}

$sql .= " ORDER BY id ";

// Handle AJAX request for getting student data
if(isset($_GET['ajax']) && $_GET['ajax'] == 'get_student' && isset($_GET['id'])) {
    $id = intval($_GET['id']);
    $query = "SELECT * FROM students WHERE id = $id";
    $result = mysqli_query($conn, $query);
    if($row = mysqli_fetch_assoc($result)) {
        header('Content-Type: application/json');
        echo json_encode($row);
    }
    exit;
}

// Handle AJAX request for updating student
if($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_GET['ajax']) && $_GET['ajax'] == 'update_student') {
    $id = intval($_POST['id']);
    $name = mysqli_real_escape_string($conn, $_POST['name']);
    $gender = mysqli_real_escape_string($conn, $_POST['gender']);
    $image = mysqli_real_escape_string($conn, $_POST['image']);
    $age = intval($_POST['age']);
    $dob = mysqli_real_escape_string($conn, $_POST['dob']);
    $email = mysqli_real_escape_string($conn, $_POST['email']);
    $phone = mysqli_real_escape_string($conn, $_POST['phone']);
    $address = mysqli_real_escape_string($conn, $_POST['address']);
    $grade = mysqli_real_escape_string($conn, $_POST['grade']);
    $college = mysqli_real_escape_string($conn, $_POST['college']);
    $skill = mysqli_real_escape_string($conn, $_POST['skill']);
    $year = mysqli_real_escape_string($conn, $_POST['year']);
    $shift = mysqli_real_escape_string($conn, $_POST['Shift']);
    
    $update_sql = "UPDATE students SET 
                   name='$name', 
                   gender='$gender', 
                   image='$image', 
                   age=$age, 
                   dob='$dob', 
                   email='$email', 
                   phone='$phone', 
                   address='$address', 
                   grade='$grade', 
                   college='$college', 
                   skill='$skill', 
                   year='$year', 
                   Shift='$shift' 
                   WHERE id=$id";
    
    if(mysqli_query($conn, $update_sql)) {
        echo json_encode(['success' => true, 'message' => 'Student updated successfully']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Error: ' . mysqli_error($conn)]);
    }
    exit;
}

// Handle AJAX request for getting college details
if(isset($_GET['ajax']) && $_GET['ajax'] == 'get_college_details' && isset($_GET['college'])) {
    $college = mysqli_real_escape_string($conn, $_GET['college']);
    $year = isset($_GET['year']) ? mysqli_real_escape_string($conn, $_GET['year']) : '';
    
    $where = "college = '$college'";
    if(!empty($year)) {
        $where .= " AND year = '$year'";
    }
    
    $query = "SELECT COUNT(*) as total FROM students WHERE $where";
    $result = mysqli_query($conn, $query);
    $total = mysqli_fetch_assoc($result)['total'];
    
    // Get students by year
    $year_query = "SELECT year, COUNT(*) as count FROM students WHERE college = '$college' GROUP BY year ORDER BY year";
    $year_result = mysqli_query($conn, $year_query);
    $year_stats = [];
    while($row = mysqli_fetch_assoc($year_result)) {
        $year_stats[] = $row;
    }
    
    // Get students by gender
    $gender_query = "SELECT gender, COUNT(*) as count FROM students WHERE college = '$college' GROUP BY gender";
    $gender_result = mysqli_query($conn, $gender_query);
    $gender_stats = [];
    while($row = mysqli_fetch_assoc($gender_result)) {
        $gender_stats[] = $row;
    }
    
    header('Content-Type: application/json');
    echo json_encode([
        'total' => $total,
        'by_year' => $year_stats,
        'by_gender' => $gender_stats,
        'college_name' => $college
    ]);
    exit;
}

// Fetch all students from database
$result = mysqli_query($conn, $sql);
if (!$result) {
    echo "<div class='alert alert-danger mt-3'>Error: " . mysqli_error($conn) . "</div>";
}

// Get all colleges and their counts dynamically
$college_counts = [];
$college_query = "SELECT college, COUNT(*) as count FROM students WHERE college IS NOT NULL AND college != '' GROUP BY college ORDER BY college";
$college_result = mysqli_query($conn, $college_query);
while($row = mysqli_fetch_assoc($college_result)) {
    $college_counts[$row['college']] = $row['count'];
}

// Define college display names and icons
$college_config = [
    'it' => ['name' => 'Information Technology (IT)', 'icon' => 'fa-laptop-code', 'color' => 'it-card'],
    'Civil' => ['name' => 'Civil Engineering', 'icon' => 'fa-building', 'color' => 'civil-card'],
    'Electronics' => ['name' => 'Electronics', 'icon' => 'fa-microchip', 'color' => 'electronics-card'],
    'Electrical' => ['name' => 'Electrical Engineering', 'icon' => 'fa-bolt', 'color' => 'electrical-card'],
    'Business Science' => ['name' => 'Business Science', 'icon' => 'fa-chart-line', 'color' => 'business-card']
];

// Get college list for filter
$college_list = array_keys($college_counts);

// Handle success message
if(isset($_GET['success']) && $_GET['success'] == 1) {
    $message = isset($_GET['message']) ? $_GET['message'] : 'Student added successfully';
    echo "<div class='alert alert-success alert-dismissible fade show' role='alert'>
            <i class='fas fa-check-circle'></i> $message
            <button type='button' class='btn-close' data-bs-dismiss='alert' aria-label='Close'></button>
          </div>";
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
    <title>KRaksa Education Suite - Student Management</title>
    <style>
        /* Dropdown */
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

        /* ----table---- */
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
        
        html {
            scroll-behavior: auto;
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
            text-decoration: none;
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
            margin: 15px 0;
            flex-wrap: wrap;
        }
        
        .search-box input {
            border-radius: 30px;
            padding: 8px 20px;
            border: 1px solid #e0e4e8;
            width: 300px;
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
            background: white;
            min-width: 150px;
        }
        
        .search-box button {
            border-radius: 30px;
            padding: 8px 20px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border: none;
            transition: all 0.2s ease;
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
        }
        
        .clear-search:hover {
            background: #5a6268;
            color: white;
        }
        
        .result-count {
            font-size: 14px;
            color: #6c757d;
            margin: 10px 0;
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
        
        .btn-warning, .btn-danger {
            padding: 5px 12px;
            font-size: 12px;
            border-radius: 8px;
            margin: 0 3px;
        }
        
        .kpi-row { 
            display: flex; 
            flex-wrap: wrap; 
            gap: 20px; 
            margin-bottom: 32px; 
        }
        
        .kpi-card { 
            border-radius: 20px; 
            padding: 20px 24px; 
            flex: 1; 
            min-width: 180px;
            color: white;
            box-shadow: 0 10px 25px rgba(0,0,0,0.1);
            cursor: pointer;
            transition: transform 0.3s ease;
        }
        
        .kpi-card:hover {
            transform: translateY(-5px);
        }
        
        /* Different colors for different colleges */
        .it-card { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); }
        .civil-card { background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%); }
        .electronics-card { background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%); }
        .electrical-card { background: linear-gradient(135deg, #43e97b 0%, #38f9d7 100%); }
        .business-card { background: linear-gradient(135deg, #fa709a 0%, #fee140 100%); }
        
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
        
        .filter-badge {
            display: inline-block;
            background: #e0e7ff;
            color: #4f46e5;
            padding: 5px 12px;
            border-radius: 20px;
            font-size: 12px;
            margin-right: 10px;
            margin-bottom: 10px;
        }
        
        .filter-badge .remove-filter {
            cursor: pointer;
            margin-left: 8px;
            color: #ef4444;
        }
        
        .more{
            text-align: right;
            margin-bottom: 15px;
        }
        
        .more button {
            width: 150px;
            padding: 10px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border: none;
            border-radius: 10px;
            transition: all 0.2s ease;
        }
        
        .more button:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(102, 126, 234, 0.4);
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
        
        /* College Details Modal */
        .college-details-modal .modal-content {
            border-radius: 20px;
        }
        
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 15px;
            margin-bottom: 20px;
        }
        
        .stat-box {
            background: #f8fafc;
            padding: 15px;
            border-radius: 12px;
            text-align: center;
        }
        
        .stat-box .stat-value {
            font-size: 28px;
            font-weight: bold;
            color: #4f46e5;
        }
        
        .stat-box .stat-label {
            font-size: 12px;
            color: #64748b;
            margin-top: 5px;
        }
        
        .year-badge {
            display: inline-block;
            background: #e0e7ff;
            color: #4f46e5;
            padding: 8px 15px;
            border-radius: 20px;
            margin: 5px;
            cursor: pointer;
            transition: all 0.2s ease;
        }
        
        .year-badge:hover {
            background: #4f46e5;
            color: white;
            transform: scale(1.05);
        }
        
        .view-students-btn {
            width: 100%;
            margin-top: 15px;
            padding: 10px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border: none;
            border-radius: 10px;
            font-weight: 600;
            transition: all 0.2s ease;
        }
        
        .view-students-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(102, 126, 234, 0.4);
        }
        
        @media (max-width: 768px) {
            .sidebar { width: 80px; }
            .sidebar-header h1, .sidebar-header p, .nav-item span { display: none; }
            .nav-item { justify-content: center; }
            .main-content { padding: 15px; }
            .top-bar { flex-direction: column; gap: 10px; }
            .search-box { flex-direction: column; }
            .search-box input, .search-box select { width: 100%; }
            .stats-grid { grid-template-columns: 1fr; }
        }
        
        .loading-opacity {
            opacity: 0.6;
            pointer-events: none;
        }
        
        .no-data-message {
            text-align: center;
            padding: 40px;
            color: #64748b;
        }
        
        .no-data-message i {
            font-size: 48px;
            margin-bottom: 15px;
            opacity: 0.5;
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
                <!-- Students Dropdown -->
                <div class="dropdown-container">
                    <div class="nav-item dropdown-btn" onclick="toggleDropdown()">
                        <div>
                            <i class="fas fa-user-graduate"></i>
                            <span>Students</span>
                        </div>
                        <i class="fas fa-chevron-down dropdown-icon" id="dropdownIcon"></i>
                    </div>
                    <div class="dropdown-menus" id="studentDropdown">
                        <a href="student.php" class="nav-item sub-menu active">
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
                <a href="teacher.php" class="nav-item">
                    <i class="fas fa-chalkboard-teacher"></i> <span>Teachers</span>
                </a>
                <a href="Courses.php" class="nav-item">
                    <i class="fas fa-graduation-cap"></i> <span>Department</span>
                </a>
                 <a href="schedule.php" class="nav-item" > <i class="fas fa-calendar-alt"></i> <span>Schedule class</span></a>
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
            </div>
                <a href="Employees.php" class="nav-item">
                    <i class="fas fa-user-friends"></i> <span>Employees</span>
                </a>
                <!-- <a href="StudentAttendance.php" class="nav-item">
                    <i class="fas fa-calendar-check"></i> <span>Attendance</span>
                </a> -->
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
            <!-- Top Bar -->
            <div class="top-bar">
                <div class="page-title">
                    <h2 id="dynamicTitle">Student Management</h2>
                </div>
                <div class="date-time" id="currentDateTime"></div>
            </div>
            
            <!-- KPI Cards Row 1 - Dynamically generated from database -->
            <div class="kpi-row">
                <?php 
                $college_order = ['it', 'Civil', 'Electronics', 'Electrical', 'Business Science'];
                $displayed = 0;
                foreach($college_order as $college_key):
                    if(isset($college_counts[$college_key]) && $college_counts[$college_key] > 0):
                        $displayed++;
                        $config = isset($college_config[$college_key]) ? $college_config[$college_key] : ['name' => $college_key, 'icon' => 'fa-university', 'color' => 'it-card'];
                ?>
                <div class="kpi-card <?php echo $config['color']; ?>" onclick="showCollegeDetails('<?php echo $college_key; ?>')">
                    <div class="kpi-title"><i class="fas <?php echo $config['icon']; ?>"></i> <?php echo $config['name']; ?></div>
                    <div class="kpi-number"><?php echo $college_counts[$college_key]; ?></div>
                    <small>Click to view details</small>
                </div>
                <?php 
                    endif;
                endforeach;
                
                // If no colleges have data, show message
                if($displayed == 0):
                ?>
                <div class="alert alert-info w-100 text-center">
                    <i class="fas fa-info-circle"></i> No student data available. Please add students first.
                </div>
                <?php endif; ?>
            </div>

            <!-- Add More Button -->
            <div class="more">
                <button id="addMoreBtn">
                    <i class="fas fa-plus"></i> Add More
                </button>
            </div>

            <!-- Active Filters -->
            <?php if(!empty($year_filter) || !empty($college_filter)): ?>
            <div class="mb-3">
                <strong>Active Filters:</strong>
                <?php if(!empty($year_filter)): ?>
                    <span class="filter-badge">
                        Year: <?php echo $year_filter; ?>
                        <a href="?<?php echo http_build_query(array_merge($_GET, ['year_filter' => '', 'college_filter' => $college_filter])); ?>" class="remove-filter">&times;</a>
                    </span>
                <?php endif; ?>
                <?php if(!empty($college_filter)): ?>
                    <span class="filter-badge">
                        College: <?php 
                            $display_name = isset($college_config[$college_filter]) ? $college_config[$college_filter]['name'] : $college_filter;
                            echo htmlspecialchars($display_name); 
                        ?>
                        <a href="?<?php echo http_build_query(array_merge($_GET, ['college_filter' => '', 'year_filter' => $year_filter])); ?>" class="remove-filter">&times;</a>
                    </span>
                <?php endif; ?>
            </div>
            <?php endif; ?>

            <!-- Search and Filter Box -->
            <form method="GET" action="" class="search-box">
                <input type="text" name="search" placeholder="🔍 Search by name, email or phone..." 
                       value="<?php echo htmlspecialchars($search); ?>" autocomplete="off">
                <select name="year_filter">
                    <option value="">All Years</option>
                    <option value="1" <?php echo $year_filter == '1' ? 'selected' : ''; ?>>Year 1</option>
                    <option value="2" <?php echo $year_filter == '2' ? 'selected' : ''; ?>>Year 2</option>
                    <option value="3" <?php echo $year_filter == '3' ? 'selected' : ''; ?>>Year 3</option>
                    <option value="4" <?php echo $year_filter == '4' ? 'selected' : ''; ?>>Year 4</option>
                </select>
                <select name="college_filter">
                    <option value="">All Colleges</option>
                    <?php foreach($college_list as $college): ?>
                        <?php $display_name = isset($college_config[$college]) ? $college_config[$college]['name'] : $college; ?>
                        <option value="<?php echo htmlspecialchars($college); ?>" <?php echo $college_filter == $college ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($display_name); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
                <button type="submit"><i class="fas fa-search"></i> Filter</button>
                <?php if(!empty($search) || !empty($year_filter) || !empty($college_filter)): ?>
                    <a href="student.php" class="clear-search"><i class="fas fa-times"></i> Clear All</a>
                <?php endif; ?>
            </form>

            <!-- Result count display -->
            <div class="result-count">
                <i class="fas fa-users"></i> Total: <?php echo mysqli_num_rows($result); ?> students found
                <?php if(!empty($year_filter)): echo " | Year: " . $year_filter; endif; ?>
                <?php if(!empty($college_filter)): 
                    $display_name = isset($college_config[$college_filter]) ? $college_config[$college_filter]['name'] : $college_filter;
                    echo " | College: " . htmlspecialchars($display_name); 
                endif; ?>
            </div>

            <!-- Students Table -->
            <div class="table-container">
                <table class="table table-bordered table-striped">
                    <thead class="table-light">
                        <tr>
                            <th>ID</th>
                            <th>Name</th>
                            <th>Email</th>
                            <th>Phone</th>
                            <th>Gender</th>
                            <th>Age</th>
                            <th>Date of Birth</th>
                            <th>Address</th>
                            <th>Grade</th>
                            <th>College</th>
                            <th>Skill</th>
                            <th>Year</th>
                            <th>Shift</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (isset($result) && mysqli_num_rows($result) > 0) {
                            while($row = mysqli_fetch_assoc($result)) { 
                                // Get display name for college
                                $college_display = isset($college_config[$row['college']]) ? $college_config[$row['college']]['name'] : $row['college'];
                        ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($row['id']); ?></td>
                                    <td><?php echo htmlspecialchars($row['name']); ?></td>
                                    <td><?php echo htmlspecialchars($row['email']); ?></td>
                                    <td><?php echo htmlspecialchars($row['phone']); ?></td>
                                    <td><?php echo htmlspecialchars($row['gender']); ?></td>
                                    <td><?php echo htmlspecialchars($row['age']); ?></td>
                                    <td><?php echo htmlspecialchars($row['dob']); ?></td>
                                    <td><?php echo htmlspecialchars($row['address']); ?></td>
                                    <td><?php echo htmlspecialchars($row['grade']); ?></td>
                                    <td><?php echo htmlspecialchars($college_display); ?></td>
                                    <td><?php echo htmlspecialchars($row['skill']); ?></td>
                                    <td><?php echo htmlspecialchars($row['year']); ?></td>
                                    <td><?php echo htmlspecialchars($row['Shift']); ?></td>
                                    <td>
                                        <button type='button' class='btn btn-warning btn-sm mb-2 edit-btn' data-id='<?php echo $row['id']; ?>'>
                                            <i class='fas fa-edit'></i> Edit
                                        </button>
                                        <a href='delete_student.php?id=<?php echo $row['id']; ?>' class='btn btn-danger btn-sm' onclick='return confirm("Are you sure you want to delete this student?")'>
                                            <i class='fas fa-trash'></i> Delete
                                        </a>
                                    </td>
                                </tr>
                            <?php }
                        } else { ?>
                            <tr>
                                <td colspan='14' class='text-center'>
                                    <i class='fas fa-user-slash'></i> No students found. 
                                    <?php echo !empty($search) ? "Please try another search term." : "Click 'Add More' to add a student."; ?>
                                </td>
                            </tr>
                        <?php } ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    
    <!-- College Details Modal -->
    <div class="modal fade college-details-modal" id="collegeDetailsModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-md">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="collegeModalTitle">
                        <i class="fas fa-university"></i> College Details
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body" id="collegeDetailsBody">
                    <div class="text-center py-4">
                        <div class="spinner-border text-primary" role="status">
                            <span class="visually-hidden">Loading...</span>
                        </div>
                        <p class="mt-2">Loading college details...</p>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Add Student Modal -->
    <div class="modal fade modal-custom" id="addStudentModal" tabindex="-1" aria-labelledby="addStudentModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="addStudentModalLabel">
                        <i class="fas fa-user-plus"></i> Add New Student
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <form action="processstu.php" method="POST" id="addStudentForm">
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label class="form-label">Student Name <span class="text-danger">*</span></label>
                                <input type="text" name="name" class="form-control" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Gender</label>
                                <select name="gender" class="form-select">
                                    <option value="Male">Male</option>
                                    <option value="Female">Female</option>
                                    <option value="Other">Other</option>
                                </select>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Image (URL)</label>
                            <input name="image" class="form-control" placeholder="https://example.com/image.jpg">
                        </div>

                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label class="form-label">Age</label>
                                <input type="number" name="age" class="form-control">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Date of Birth</label>
                                <input type="date" name="dob" class="form-control">
                            </div>
                        </div>
                        
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label class="form-label">Email</label>
                                <input type="email" name="email" class="form-control">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Phone Number</label>
                                <input type="text" name="phone" class="form-control">
                            </div>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Address</label>
                            <textarea name="address" class="form-control" rows="3"></textarea>
                        </div>

                        <div class="row mb-3">
                            <div class="col-md-4">
                                <label class="form-label">Grade</label>
                                <select name="grade" class="form-select">
                                    <option selected disabled>Please Select Grade</option>
                                    <option value="Master's Degree">Master's Degree</option>
                                    <option value="Associate Degree">Associate Degree</option>
                                    <option value="Bachelor's Degree">Bachelor's Degree</option>
                                    <option value="High School">High School</option>
                                </select>
                            </div>
                            
                            <div class="col-md-4">
                                <label class="form-label">College</label>
                                <select name="college" class="form-select">
                                    <option selected disabled>Please Select college</option>
                                    <option value="Electrical">អគ្គិសនី (Electrical Engineering)</option>
                                    <option value="Business Science">ផ្នែកវិទ្យាសាស្ត្រធុរកិច្ច (Business Science)</option>
                                    <option value="Electronics">អេឡិចត្រូនិក (Electronics)</option>
                                    <option value="Civil">សំណង់ស៊ីវិល (Civil Engineering)</option>
                                    <option value="it">ពត៏មានវិទ្យា (IT)</option>
                                </select>
                            </div>

                            <div class="col-md-4">
                                <label class="form-label">Skill</label>
                                <select name="skill" class="form-select" required>
                                    <option value="">Please Select skill</option>
                                    <option value="Civil">សំណង់ស៊ីវិល (Civil Engineering)</option>
                                    <option value="Electronics">អេឡិចត្រូនិក (Electronics )</option>
                                    <option value="Electrical">អគ្គិសនី (Electrical Engineering)</option>
                                    <option value="Accounting">គណនេយ្យ (Accounting)</option>
                                    <option value="Marketing">ទីផ្សារ (Marketing)</option>
                                    <option value="Management">គ្រប់គ្រង (Management)</option>
                                    <option value="it">ពត៏មានវិទ្យា (IT)</option>
                                </select>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Year:</label>
                            <select name="year" class="form-select">
                                <option selected disabled>Please Select year</option>
                                <option value="1">year 1</option>
                                <option value="2">year 2</option>
                                <option value="3">year 3</option>
                                <option value="4">year 4</option>
                            </select>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">Shift</label>
                            <select name="Shift" class="form-select">
                                <option selected disabled>Please Select Shift</option>
                                <option value="morning">Morning</option>
                                <option value="evening">Evening</option>
                            </select>
                        </div>

                        <div class="mt-4 d-flex justify-content-end gap-2">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                                <i class="fas fa-times"></i> Cancel
                            </button>
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-save"></i> Save Student
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Edit Student Modal -->
    <div class="modal fade modal-custom" id="editStudentModal" tabindex="-1" aria-labelledby="editStudentModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="editStudentModalLabel">
                        <i class="fas fa-user-edit"></i> Edit Student Information
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <form id="editStudentForm">
                        <input type="hidden" id="edit_id" name="id">
                        
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label class="form-label">Student Name</label>
                                <input type="text" id="edit_name" name="name" class="form-control" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Gender</label>
                                <select id="edit_gender" name="gender" class="form-select">
                                    <option value="Male">Male</option>
                                    <option value="Female">Female</option>
                                    <option value="Other">Other</option>
                                </select>
                            </div>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">Image URL</label>
                            <input type="text" id="edit_image" name="image" class="form-control" placeholder="https://example.com/image.jpg">
                        </div>
                        
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label class="form-label">Age</label>
                                <input type="number" id="edit_age" name="age" class="form-control">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Date of Birth</label>
                                <input type="date" id="edit_dob" name="dob" class="form-control">
                            </div>
                        </div>
                        
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label class="form-label">Email</label>
                                <input type="email" id="edit_email" name="email" class="form-control">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Phone Number</label>
                                <input type="text" id="edit_phone" name="phone" class="form-control">
                            </div>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">Address</label>
                            <textarea id="edit_address" name="address" class="form-control" rows="2"></textarea>
                        </div>
                        
                        <div class="row mb-3">
                            <div class="col-md-4">
                                <label class="form-label">Grade</label>
                                <select id="edit_grade" name="grade" class="form-select">
                                    <option value="Master's Degree">Master's Degree</option>
                                    <option value="Associate Degree">Associate Degree</option>
                                    <option value="Bachelor's Degree">Bachelor's Degree</option>
                                    <option value="High School">High School</option>
                                </select>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">College</label>
                                <select id="edit_college" name="college" class="form-select">
                                    <option value="Electrical">អគ្គិសនី (Electrical Engineering)</option>
                                    <option value="Business Science">ផ្នែកវិទ្យាសាស្ត្រធុរកិច្ច (Business Science)</option>
                                    <option value="Electronics">អេឡិចត្រូនិក (Electronics)</option>
                                    <option value="Civil">សំណង់ស៊ីវិល (Civil Engineering)</option>
                                    <option value="it">ពត៏មានវិទ្យា (IT)</option>
                                </select>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Skill</label>
                                <select id="edit_skill" name="skill" class="form-select">
                                    <option value="Civil">សំណង់ស៊ីវិល (Civil Engineering)</option>
                                    <option value="Electronics">អេឡិចត្រូនិក (Electronics)</option>
                                    <option value="Electrical">អគ្គិសនី (Electrical Engineering)</option>
                                    <option value="Accounting">គណនេយ្យ (Accounting)</option>
                                    <option value="Marketing">ទីផ្សារ (Marketing)</option>
                                    <option value="Management">គ្រប់គ្រង (Management)</option>
                                    <option value="it">ពត៏មានវិទ្យា (IT)</option>
                                </select>
                            </div>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">Year:</label>
                            <select id="edit_year" name="year" class="form-select">
                                <option value="1">year 1</option>
                                <option value="2">year 2</option>
                                <option value="3">year 3</option>
                                <option value="4">year 4</option>
                            </select>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">Shift</label>
                            <select id="edit_Shift" name="Shift" class="form-select">
                                <option value="morning">Morning</option>
                                <option value="evening">Evening</option>
                            </select>
                        </div>
                        
                        <div class="mt-4 d-flex justify-content-end gap-2">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-save"></i> Update Student
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script>
         function toggleRequestDropdown() {
            let menu = document.getElementById("RequestDropdownMenu");
            let icon = document.getElementById("RequestDropdownIcon");
            
            if (menu.style.display === "block") {
                menu.style.display = "none";
                icon.style.transform = "rotate(0deg)";
            } else {
                // Close other dropdowns first
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
        // Update date/time
        function updateDateTime() {
            const now = new Date();
            const options = { weekday: 'long', year: 'numeric', month: 'long', day: 'numeric', hour: '2-digit', minute: '2-digit' };
            const dateTimeElement = document.getElementById('currentDateTime');
            if(dateTimeElement) {
                dateTimeElement.innerHTML = now.toLocaleDateString('en-US', options);
            }
        }
        updateDateTime();
        setInterval(updateDateTime, 60000);

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
        
        // Show college details modal
        function showCollegeDetails(college) {
            $('#collegeDetailsModal').modal('show');
            
            // Get college display name
            var collegeNames = {
                'it': 'Information Technology (IT)',
                'Civil': 'Civil Engineering',
                'Electronics': 'Electronics',
                'Electrical': 'Electrical Engineering',
                'Business Science': 'Business Science'
            };
            var collegeName = collegeNames[college] || college;
            $('#collegeModalTitle').html('<i class="fas fa-university"></i> ' + collegeName);
            
            $.ajax({
                url: 'student.php?ajax=get_college_details&college=' + encodeURIComponent(college),
                type: 'GET',
                dataType: 'json',
                success: function(data) {
                    var html = '';
                    
                    if(data.total > 0) {
                        // Total students
                        html += '<div class="stats-grid">';
                        html += '<div class="stat-box">';
                        html += '<div class="stat-value">' + data.total + '</div>';
                        html += '<div class="stat-label">Total Students</div>';
                        html += '</div>';
                        html += '</div>';
                        
                        // Students by year
                        if(data.by_year && data.by_year.length > 0) {
                            html += '<h6 class="mt-3 mb-2"><i class="fas fa-calendar-alt"></i> Students by Year:</h6>';
                            html += '<div class="mb-3">';
                            data.by_year.forEach(function(item) {
                                var yearText = '';
                                switch(item.year) {
                                    case '1': yearText = 'Year 1'; break;
                                    case '2': yearText = 'Year 2'; break;
                                    case '3': yearText = 'Year 3'; break;
                                    case '4': yearText = 'Year 4'; break;
                                    default: yearText = 'Year ' + item.year;
                                }
                                html += '<span class="year-badge" onclick="filterByCollegeAndYear(\'' + college + '\', \'' + item.year + '\')">';
                                html += yearText + ': ' + item.count + ' students';
                                html += '</span>';
                            });
                            html += '</div>';
                        } else {
                            html += '<div class="alert alert-info">No students grouped by year.</div>';
                        }
                        
                        // Students by gender
                        if(data.by_gender && data.by_gender.length > 0) {
                            html += '<h6 class="mt-3 mb-2"><i class="fas fa-venus-mars"></i> Students by Gender:</h6>';
                            html += '<div class="stats-grid">';
                            data.by_gender.forEach(function(item) {
                                var genderIcon = item.gender == 'Male' ? 'fa-mars' : (item.gender == 'Female' ? 'fa-venus' : 'fa-genderless');
                                html += '<div class="stat-box">';
                                html += '<div class="stat-value"><i class="fas ' + genderIcon + '"></i> ' + item.count + '</div>';
                                html += '<div class="stat-label">' + item.gender + '</div>';
                                html += '</div>';
                            });
                            html += '</div>';
                        }
                        
                        // View all students button
                        html += '<button class="view-students-btn" onclick="filterByCollege(\'' + college + '\')">';
                        html += '<i class="fas fa-users"></i> View All ' + collegeName + ' Students';
                        html += '</button>';
                    } else {
                        html += '<div class="no-data-message">';
                        html += '<i class="fas fa-user-graduate"></i>';
                        html += '<p>No students found in ' + collegeName + '</p>';
                        html += '</div>';
                    }
                    
                    $('#collegeDetailsBody').html(html);
                },
                error: function(xhr, status, error) {
                    console.error("AJAX Error:", error);
                    $('#collegeDetailsBody').html('<div class="text-center py-4 text-danger"><i class="fas fa-exclamation-circle fa-3x mb-3"></i><p>Error loading college details. Please try again.</p></div>');
                }
            });
        }
        
        // Filter by college
        function filterByCollege(college) {
            window.location.href = '?college_filter=' + encodeURIComponent(college);
        }
        
        // Filter by college and year
        function filterByCollegeAndYear(college, year) {
            window.location.href = '?college_filter=' + encodeURIComponent(college) + '&year_filter=' + year;
        }

        $(document).ready(function() {
            // Show Add Student Modal when clicking Add More button
            $('#addMoreBtn').click(function() {
                $('#addStudentModal').modal('show');
            });
            
            // Handle edit button click
            $('.edit-btn').click(function() {
                var studentId = $(this).data('id');
                
                // Show loading state
                $('#editStudentModal .modal-body').addClass('loading-opacity');
                
                // Fetch student data via AJAX
                $.ajax({
                    url: 'student.php?ajax=get_student&id=' + studentId,
                    type: 'GET',
                    dataType: 'json',
                    success: function(data) {
                        // Populate modal fields with student data
                        $('#edit_id').val(data.id);
                        $('#edit_name').val(data.name);
                        $('#edit_gender').val(data.gender);
                        $('#edit_image').val(data.image || '');
                        $('#edit_age').val(data.age);
                        $('#edit_dob').val(data.dob);
                        $('#edit_email').val(data.email);
                        $('#edit_phone').val(data.phone);
                        $('#edit_address').val(data.address);
                        $('#edit_grade').val(data.grade);
                        $('#edit_college').val(data.college);
                        $('#edit_skill').val(data.skill);
                        $('#edit_year').val(data.year);
                        $('#edit_Shift').val(data.Shift);
                        
                        // Show the modal
                        $('#editStudentModal').modal('show');
                        $('#editStudentModal .modal-body').removeClass('loading-opacity');
                    },
                    error: function(xhr, status, error) {
                        console.error("AJAX Error:", error);
                        alert('Error loading student data. Please try again.');
                        $('#editStudentModal .modal-body').removeClass('loading-opacity');
                    }
                });
            });
            
            // Handle edit form submission
            $('#editStudentForm').submit(function(e) {
                e.preventDefault();
                
                // Show loading
                var submitBtn = $(this).find('button[type="submit"]');
                var originalText = submitBtn.html();
                submitBtn.html('<i class="fas fa-spinner fa-spin"></i> Updating...').prop('disabled', true);
                
                $.ajax({
                    url: 'student.php?ajax=update_student',
                    type: 'POST',
                    data: $(this).serialize(),
                    dataType: 'json',
                    success: function(response) {
                        if (response.success) {
                            // Show success message
                            alert(response.message);
                            // Reload page to see updates
                            location.reload();
                        } else {
                            alert('Error: ' + response.message);
                            submitBtn.html(originalText).prop('disabled', false);
                        }
                    },
                    error: function(xhr, status, error) {
                        console.error("AJAX Error:", error);
                        alert('Error updating student. Please try again.');
                        submitBtn.html(originalText).prop('disabled', false);
                    }
                });
            });
            
            // Clear edit modal when closed
            $('#editStudentModal').on('hidden.bs.modal', function() {
                $('#editStudentForm')[0].reset();
            });
            
            // Clear add modal when closed
            $('#addStudentModal').on('hidden.bs.modal', function() {
                $('#addStudentForm')[0].reset();
            });
        });
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
    
    </script>
</body>
</html>