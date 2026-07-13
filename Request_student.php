<?php
include "db.php";
if(!isset($_SESSION['id']) || $_SESSION['role'] !== 'admin') {
    header("Location: login.php");
    exit();
}

$message = '';
$error = '';

// ============================================
// CHECK AND UPDATE TABLE STRUCTURE
// ============================================
$check_column = "SHOW COLUMNS FROM requests LIKE 'admin_comment'";
$column_result = mysqli_query($conn, $check_column);
if(mysqli_num_rows($column_result) == 0) {
    $alter_sql = "ALTER TABLE requests 
                  ADD COLUMN admin_comment TEXT AFTER status,
                  ADD COLUMN updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP AFTER created_at,
                  ADD INDEX idx_status (status),
                  ADD INDEX idx_student_id (student_id),
                  ADD INDEX idx_created_at (created_at)";
    mysqli_query($conn, $alter_sql);
}

if(isset($_POST['action']) && isset($_POST['request_id'])) {
    $request_id = mysqli_real_escape_string($conn, $_POST['request_id']);
    $action = mysqli_real_escape_string($conn, $_POST['action']);
    $admin_comment = mysqli_real_escape_string($conn, $_POST['admin_comment'] ?? '');
    
    if($action === 'approve') {
        $update_sql = "UPDATE requests SET status = 'approved', admin_comment = '$admin_comment', updated_at = NOW() WHERE id = '$request_id'";
        if(mysqli_query($conn, $update_sql)) {
            $message = "✅ Request approved successfully!";
        } else {
            $error = "❌ Error approving request: " . mysqli_error($conn);
        }
    } elseif($action === 'reject') {
        $update_sql = "UPDATE requests SET status = 'rejected', admin_comment = '$admin_comment', updated_at = NOW() WHERE id = '$request_id'";
        if(mysqli_query($conn, $update_sql)) {
            $message = "✅ Request rejected successfully!";
        } else {
            $error = "❌ Error rejecting request: " . mysqli_error($conn);
        }
    }
}

// ============================================
// DELETE REQUEST
// ============================================
if(isset($_GET['delete']) && is_numeric($_GET['delete'])) {
    $request_id = $_GET['delete'];
    $delete_sql = "DELETE FROM requests WHERE id = '$request_id'";
    
    if(mysqli_query($conn, $delete_sql)) {
        $message = "✅ Request deleted successfully!";
    } else {
        $error = "❌ Error deleting request: " . mysqli_error($conn);
    }
}

// ============================================
// GET FILTERS FROM URL
// ============================================
$status_filter = isset($_GET['status']) ? mysqli_real_escape_string($conn, $_GET['status']) : '';
$date_from = isset($_GET['date_from']) ? mysqli_real_escape_string($conn, $_GET['date_from']) : '';
$date_to = isset($_GET['date_to']) ? mysqli_real_escape_string($conn, $_GET['date_to']) : '';
$search = isset($_GET['search']) ? mysqli_real_escape_string($conn, $_GET['search']) : '';
$type_filter = isset($_GET['type']) ? mysqli_real_escape_string($conn, $_GET['type']) : '';
$student_filter = isset($_GET['student']) ? mysqli_real_escape_string($conn, $_GET['student']) : '';

// ============================================
// BUILD QUERY WITH FILTERS
// ============================================
$where = "WHERE 1=1";

if (!empty($status_filter)) {
    $where .= " AND r.status = '$status_filter'";
}

if (!empty($date_from)) {
    $where .= " AND DATE(r.created_at) >= '$date_from'";
}

if (!empty($date_to)) {
    $where .= " AND DATE(r.created_at) <= '$date_to'";
}

if (!empty($search)) {
    $where .= " AND (r.student_name LIKE '%$search%' OR r.subject LIKE '%$search%' OR r.request_type LIKE '%$search%' OR r.description LIKE '%$search%')";
}

if (!empty($type_filter)) {
    $where .= " AND r.request_type = '$type_filter'";
}

if (!empty($student_filter)) {
    $where .= " AND r.student_id = '$student_filter'";
}

// ============================================
// GET ALL REQUESTS WITH STUDENT INFO
// ============================================
$query = "SELECT r.*, 
          s.name as student_name,
          s.id as student_code
          FROM requests r
          LEFT JOIN students s ON r.student_id = s.id
          $where
          ORDER BY 
          CASE WHEN r.status = 'pending' THEN 0 
               WHEN r.status = 'approved' THEN 1 
               WHEN r.status = 'completed' THEN 2
               ELSE 3 END, 
          r.created_at DESC";

$result = mysqli_query($conn, $query);

// ✅ DEBUG: ពិនិត្យកំហុស Query
if (!$result) {
    $error = "Query Error: " . mysqli_error($conn);
    echo "<div class='alert alert-danger'>$error</div>";
    echo "<pre>Query: " . htmlspecialchars($query) . "</pre>";
    exit();
}

// ✅ Fetch data
$requests = [];
if (mysqli_num_rows($result) > 0) {
    while ($row = mysqli_fetch_assoc($result)) {
        $requests[] = $row;
    }
}

// ============================================
// GET STATISTICS (ទាំងអស់)
// ============================================
$stats_query = "SELECT 
                COUNT(CASE WHEN status = 'pending' THEN 1 END) as pending,
                COUNT(CASE WHEN status = 'approved' THEN 1 END) as approved,
                COUNT(CASE WHEN status = 'rejected' THEN 1 END) as rejected,
                COUNT(CASE WHEN status = 'completed' THEN 1 END) as completed,
                COUNT(*) as total
                FROM requests";
$stats_result = mysqli_query($conn, $stats_query);
$stats = mysqli_fetch_assoc($stats_result);

// ============================================
// GET STATISTICS WITH FILTERS
// ============================================
$filtered_stats_query = "SELECT 
                COUNT(CASE WHEN status = 'pending' THEN 1 END) as pending,
                COUNT(CASE WHEN status = 'approved' THEN 1 END) as approved,
                COUNT(CASE WHEN status = 'rejected' THEN 1 END) as rejected,
                COUNT(CASE WHEN status = 'completed' THEN 1 END) as completed,
                COUNT(*) as total
                FROM requests r
                $where";
$filtered_stats_result = mysqli_query($conn, $filtered_stats_query);
$filtered_stats = mysqli_fetch_assoc($filtered_stats_result);

// ============================================
// GET STUDENTS LIST FOR FILTER
// ============================================
$students_query = "SELECT DISTINCT r.student_id, s.name as student_name 
                   FROM requests r
                   LEFT JOIN students s ON r.student_id = s.id
                   WHERE s.name IS NOT NULL
                   ORDER BY s.name";
$students_result = mysqli_query($conn, $students_query);

// ============================================
// GET REQUEST TYPES FOR FILTER
// ============================================
$types_query = "SELECT DISTINCT request_type FROM requests WHERE request_type IS NOT NULL AND request_type != '' ORDER BY request_type";
$types_result = mysqli_query($conn, $types_query);

// ============================================
// GET TOTAL RECORDS IN DATABASE (FOR DEBUG)
// ============================================
$count_all_query = "SELECT COUNT(*) as total FROM requests";
$count_all_result = mysqli_query($conn, $count_all_query);
$total_all = mysqli_fetch_assoc($count_all_result);
$total_records = $total_all['total'] ?? 0;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Admin - Student Request Management</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
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
        .dropdown-container { margin-bottom: 10px; }
        .dropdown-btn { display: flex; justify-content: space-between; align-items: center; cursor: pointer; }
        .dropdown-menus { display: none; padding-left: 15px; margin-top: 5px; }
        .sub-menu { font-size: 14px; padding: 10px 15px; margin-bottom: 5px; background: rgba(88, 30, 248, 0.61); }
        .sub-menu:hover { background: rgba(110, 41, 238, 0.6); }
        .dropdown-icon { transition: 0.3s; }
        .main-content { flex: 1; padding: 28px 32px; background: #f8fafc; overflow-y: auto; height: 100vh; }
        .top-bar { display: flex; justify-content: space-between; align-items: center; padding: 12px 24px; background: white; border-radius: 60px; box-shadow: 0 1px 3px rgba(0,0,0,0.05); margin-bottom: 28px; }
        .page-title h2 { font-size: 1.3rem; font-weight: 600; color: #1e293b; margin: 0; }
        
        .kpi-row { display: flex; flex-wrap: wrap; gap: 20px; margin-bottom: 32px; color:black; }
        .kpi-card { border-radius: 20px; padding: 20px 24px; flex: 1; min-width: 160px; box-shadow: 0 10px 25px rgba(0,0,0,0.1); transition: transform 0.3s ease; color:black; background: white; border-bottom: 8px solid; text-align: left; cursor: pointer; }
        .kpi-card:hover { transform: translateY(-5px); }
        .kpi-title { font-size: 14px; text-transform: uppercase; letter-spacing: 1px; opacity: 0.9; margin-bottom: 10px; font-weight: 500; color:black; }
        .kpi-number { font-size: 28px; font-weight: bold; margin-bottom: 5px; }
        .kpi-card .kpi-sub { font-size: 12px; opacity: 0.7; }
        
        .request-card {
            background: white;
            border-radius: 12px;
            padding: 18px 22px;
            margin-bottom: 15px;
            border-left: 5px solid #6366f1;
            box-shadow: 0 2px 8px rgba(0,0,0,0.06);
            transition: all 0.3s;
        }
        .request-card:hover { transform: translateX(5px); box-shadow: 0 4px 15px rgba(0,0,0,0.1); }
        .request-card.pending { border-left-color: #f59e0b; }
        .request-card.approved { border-left-color: #10b981; }
        .request-card.rejected { border-left-color: #ef4444; }
        .request-card.completed { border-left-color: #8b5cf6; }
        
        .badge-status { padding: 4px 14px; border-radius: 20px; font-size: 0.75rem; font-weight: 600; }
        .badge-pending { background: #fef3c7; color: #92400e; }
        .badge-approved { background: #d1fae5; color: #065f46; }
        .badge-rejected { background: #fee2e2; color: #991b1b; }
        .badge-completed { background: #ede9fe; color: #5b21b6; }
        
        .btn-approve { background: #10b981; color: white; border: none; padding: 6px 18px; border-radius: 8px; font-weight: 500; transition: 0.3s; }
        .btn-approve:hover { background: #059669; color: white; }
        .btn-reject { background: #ef4444; color: white; border: none; padding: 6px 18px; border-radius: 8px; font-weight: 500; transition: 0.3s; }
        .btn-reject:hover { background: #dc2626; color: white; }
        .btn-delete { background: #6b7280; color: white; border: none; padding: 6px 18px; border-radius: 8px; font-weight: 500; transition: 0.3s; }
        .btn-delete:hover { background: #4b5563; color: white; }
        
        .filter-section { background: white; padding: 20px; border-radius: 12px; margin-bottom: 20px; box-shadow: 0 2px 8px rgba(0,0,0,0.05); }
        .filter-section .filter-row { display: flex; gap: 15px; flex-wrap: wrap; align-items: flex-end; }
        .filter-section .filter-group { flex: 1; min-width: 150px; }
        .filter-section label { font-size: 12px; font-weight: 600; color: #64748b; text-transform: uppercase; letter-spacing: 0.5px; display: block; margin-bottom: 5px; }
        .filter-section select, .filter-section input { width: 100%; padding: 8px 12px; border-radius: 8px; border: 1px solid #d1d5db; font-size: 0.9rem; background: white; }
        .filter-section select:focus, .filter-section input:focus { outline: none; border-color: #6366f1; box-shadow: 0 0 0 3px rgba(99, 102, 241, 0.1); }
        .filter-section .filter-actions { display: flex; gap: 10px; align-items: center; }
        .filter-section .filter-actions .btn-apply { background: #6366f1; color: white; padding: 8px 20px; border-radius: 8px; border: none; font-weight: 500; cursor: pointer; }
        .filter-section .filter-actions .btn-apply:hover { background: #4f46e5; }
        .filter-section .filter-actions .btn-reset { background: #e2e8f0; color: #475569; padding: 8px 20px; border-radius: 8px; border: none; font-weight: 500; cursor: pointer; text-decoration: none; display: inline-block; }
        .filter-section .filter-actions .btn-reset:hover { background: #cbd5e1; }
        
        .tabs { display: flex; gap: 5px; margin-bottom: 20px; background: white; padding: 5px; border-radius: 12px; box-shadow: 0 2px 8px rgba(0,0,0,0.05); flex-wrap: wrap; }
        .tab { padding: 10px 25px; border-radius: 10px; border: none; background: transparent; font-weight: 500; color: #64748b; transition: all 0.3s; cursor: pointer; }
        .tab:hover { background: #f1f5f9; }
        .tab.active { background: #6366f1; color: white; }
        .tab-badge { background: #e2e8f0; color: #475569; padding: 2px 8px; border-radius: 20px; font-size: 0.7rem; margin-left: 5px; }
        .tab.active .tab-badge { background: rgba(255,255,255,0.2); color: white; }
        
        .type-badge { background: #e2e8f0; padding: 2px 12px; border-radius: 20px; font-size: 0.75rem; color: #334155; display: inline-block; margin: 2px; }
        .type-badge.leave { background: #fef3c7; color: #92400e; }
        .type-badge.exam { background: #dbeafe; color: #1e40af; }
        .type-badge.certificate { background: #d1fae5; color: #065f46; }
        .type-badge.transcript { background: #ede9fe; color: #5b21b6; }
        .type-badge.scholarship { background: #fce7f3; color: #9d174d; }
        
        .filter-summary { background: #f8fafc; padding: 8px 15px; border-radius: 8px; margin-bottom: 15px; font-size: 13px; color: #475569; }
        .filter-summary strong { color: #1e293b; }
        
        @media (max-width: 768px) {
            .sidebar { width: 80px; }
            .sidebar-header h1, .sidebar-header p, .nav-item span { display: none; }
            .nav-item { justify-content: center; }
            .main-content { padding: 15px; }
            .kpi-card { min-width: 120px; }
            .kpi-number { font-size: 18px; }
            .filter-section .filter-row { flex-direction: column; }
            .filter-section .filter-group { min-width: 100%; }
        }
    </style>
</head>
<body>

<script>
    if(sessionStorage.getItem('login') == null){
        window.location='login.php';
    }
</script>

<div class="container">
    <!-- ============================================ -->
    <!-- SIDEBAR                                      -->
    <!-- ============================================ -->
    <div class="sidebar">
        <div class="sidebar-header">
            <img src="https://i.pinimg.com/736x/6a/33/e1/6a33e1b0b8fbb948cbf04b7397c9b381.jpg" alt="Logo" />
            <h1>KRaksa</h1>
            <p>Education Suite</p>
        </div>
        <div class="nav-menu">
            <a href="index.php" class="nav-item" data-page="dashboard">
                <i class="fas fa-tachometer-alt"></i> <span>Main Dashboard</span>
            </a>
            
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
            
            <a href="teacher.php" class="nav-item" data-page="teachers">
                <i class="fas fa-chalkboard-teacher"></i> <span>Teachers</span>
            </a>
            <a href="Courses.php" class="nav-item" data-page="courses">
                <i class="fas fa-graduation-cap"></i> <span>Department</span>
            </a>
            <a href="schedule.php" class="nav-item" data-page="schedule">
                <i class="fas fa-calendar-alt"></i> <span>Schedule class</span>
            </a>
            
            <div class="dropdown-container">
                <div class="nav-item dropdown-btn" onclick="toggleRequestDropdown()">
                    <div>
                        <i class="fas fa-file-pdf"></i> 
                        <span class="m-2"> Request</span>
                    </div>
                    <i class="fas fa-chevron-down dropdown-icon" id="RequestDropdownIcon"></i>
                </div>
                <div class="dropdown-menus" id="RequestDropdownMenu">
                    <a href="Request_teacher_admin.php" class="nav-item sub-menu">
                        <i class="fas fa-chalkboard-teacher"></i><span>Teacher</span>
                    </a>
                    <a href="Request_student.php" class="nav-item sub-menu active">
                        <i class="fas fa-users"></i><span>Student</span>
                    </a>
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
                    <a href="report_teacher.php" class="nav-item sub-menu">
                        <i class="fas fa-chalkboard-teacher"></i><span>Teacher Report</span>
                    </a>
                    <a href="report_student.php" class="nav-item sub-menu">
                        <i class="fas fa-users"></i><span>Student Report</span>
                    </a>
                    <a href="report_month.php" class="nav-item sub-menu">
                        <i class="fas fa-chart-line"></i><span>Monthly Report</span>
                    </a>
                </div>
            </div>
            
            <a href="Employees.php" class="nav-item" data-page="employees">
                <i class="fas fa-user-friends"></i> <span>Employees</span>
            </a>
            <!-- <a href="StudentAttendance.php" class="nav-item" data-page="attendance">
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

    <!-- ============================================ -->
    <!-- MAIN CONTENT                                 -->
    <!-- ============================================ -->
    <div class="main-content">
        <div class="top-bar">
            <div class="page-title">
                <h2 id="dynamicTitle"><i class="fas fa-users"></i> Student Request Management</h2>
            </div>
            <div class="date-time" id="currentDateTime"></div>
        </div>
        
        <!-- ============================================ -->
        <!-- MESSAGES                                     -->
        <!-- ============================================ -->
        <?php if(!empty($message)): ?>
            <div class="alert alert-success alert-dismissible fade show">
                <i class="fas fa-check-circle"></i> <?php echo $message; ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>
        <?php if(!empty($error)): ?>
            <div class="alert alert-danger alert-dismissible fade show">
                <i class="fas fa-exclamation-circle"></i> <?php echo $error; ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <!-- ============================================ -->
        <!-- KPI STATISTICS                               -->
        <!-- ============================================ -->
        <div class="kpi-row">
            <div class="kpi-card" style="border-bottom-color: #f59e0b;" onclick="location.href='?status=pending'">
                <div class="kpi-title"><i class="fas fa-clock"></i> Pending</div>
                <div class="kpi-number" style="color: #f59e0b;"><?php echo $stats['pending'] ?? 0; ?></div>
                <div class="kpi-sub">Waiting for review</div>
            </div>
            <div class="kpi-card" style="border-bottom-color: #10b981;" onclick="location.href='?status=approved'">
                <div class="kpi-title"><i class="fas fa-check-circle"></i> Approved</div>
                <div class="kpi-number" style="color: #10b981;"><?php echo $stats['approved'] ?? 0; ?></div>
                <div class="kpi-sub">Approved requests</div>
            </div>
            <div class="kpi-card" style="border-bottom-color: #ef4444;" onclick="location.href='?status=rejected'">
                <div class="kpi-title"><i class="fas fa-times-circle"></i> Rejected</div>
                <div class="kpi-number" style="color: #ef4444;"><?php echo $stats['rejected'] ?? 0; ?></div>
                <div class="kpi-sub">Rejected requests</div>
            </div>
            <div class="kpi-card" style="border-bottom-color: #8b5cf6;" onclick="location.href='?status=completed'">
                <div class="kpi-title"><i class="fas fa-check-double"></i> Completed</div>
                <div class="kpi-number" style="color: #8b5cf6;"><?php echo $stats['completed'] ?? 0; ?></div>
                <div class="kpi-sub">Completed requests</div>
            </div>
            <div class="kpi-card" style="border-bottom-color: #6366f1;" onclick="location.href='Request_student.php'">
                <div class="kpi-title"><i class="fas fa-tasks"></i> Total</div>
                <div class="kpi-number" style="color: #6366f1;"><?php echo $stats['total'] ?? 0; ?></div>
                <div class="kpi-sub">All requests</div>
            </div>
        </div>

        <!-- ============================================ -->
        <!-- FILTER SECTION                               -->
        <!-- ============================================ -->
        <div class="filter-section">
            <form method="GET" action="" id="filterForm">
                <div class="filter-row">
                    <div class="filter-group">
                        <label><i class="fas fa-search"></i> Search</label>
                        <input type="text" name="search" placeholder="Search by name, subject..." 
                               value="<?php echo htmlspecialchars($search); ?>">
                    </div>
                    
                    <div class="filter-group">
                        <label><i class="fas fa-filter"></i> Status</label>
                        <select name="status">
                            <option value="">All Status</option>
                            <option value="pending" <?php echo $status_filter == 'pending' ? 'selected' : ''; ?>>Pending</option>
                            <option value="approved" <?php echo $status_filter == 'approved' ? 'selected' : ''; ?>>Approved</option>
                            <option value="rejected" <?php echo $status_filter == 'rejected' ? 'selected' : ''; ?>>Rejected</option>
                            <option value="completed" <?php echo $status_filter == 'completed' ? 'selected' : ''; ?>>Completed</option>
                        </select>
                    </div>
                    
                    <div class="filter-group">
                        <label><i class="fas fa-tag"></i> Request Type</label>
                        <select name="type">
                            <option value="">All Types</option>
                            <?php 
                            if ($types_result) {
                                mysqli_data_seek($types_result, 0);
                            }
                            while($type = mysqli_fetch_assoc($types_result)): ?>
                                <option value="<?php echo htmlspecialchars($type['request_type']); ?>" <?php echo $type_filter == $type['request_type'] ? 'selected' : ''; ?>>
                                    <?php echo ucfirst(str_replace('_', ' ', $type['request_type'])); ?>
                                </option>
                            <?php endwhile; ?>
                        </select>
                    </div>
                    
                    <div class="filter-group">
                        <label><i class="fas fa-user"></i> Student</label>
                        <select name="student">
                            <option value="">All Students</option>
                            <?php 
                            if ($students_result) {
                                mysqli_data_seek($students_result, 0);
                            }
                            while($student = mysqli_fetch_assoc($students_result)): ?>
                                <option value="<?php echo $student['student_id']; ?>" <?php echo $student_filter == $student['student_id'] ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($student['student_name'] ?? 'Unknown'); ?>
                                </option>
                            <?php endwhile; ?>
                        </select>
                    </div>
                </div>
                
                <div class="filter-row mt-3">
                    <div class="filter-group">
                        <label><i class="fas fa-calendar-alt"></i> Date From</label>
                        <input type="date" name="date_from" value="<?php echo htmlspecialchars($date_from); ?>">
                    </div>
                    
                    <div class="filter-group">
                        <label><i class="fas fa-calendar-alt"></i> Date To</label>
                        <input type="date" name="date_to" value="<?php echo htmlspecialchars($date_to); ?>">
                    </div>
                    
                    <div class="filter-group" style="flex: 0.5;">
                        <label>&nbsp;</label>
                        <div class="filter-actions">
                            <button type="submit" class="btn-apply"><i class="fas fa-filter"></i> Apply</button>
                            <a href="Request_student.php" class="btn-reset">
                                <i class="fas fa-times"></i> Reset
                            </a>
                        </div>
                    </div>
                </div>
            </form>
        </div>

        <!-- ============================================ -->
        <!-- FILTER SUMMARY                              -->
        <!-- ============================================ -->
        <?php if(!empty($status_filter) || !empty($date_from) || !empty($date_to) || !empty($search) || !empty($type_filter) || !empty($student_filter)): ?>
            <div class="filter-summary">
                <i class="fas fa-info-circle"></i> 
                Showing filtered results: 
                <?php 
                $filters = [];
                if(!empty($status_filter)) $filters[] = "<strong>Status:</strong> " . ucfirst($status_filter);
                if(!empty($date_from)) $filters[] = "<strong>From:</strong> " . date('d/m/Y', strtotime($date_from));
                if(!empty($date_to)) $filters[] = "<strong>To:</strong> " . date('d/m/Y', strtotime($date_to));
                if(!empty($search)) $filters[] = "<strong>Search:</strong> " . htmlspecialchars($search);
                if(!empty($type_filter)) $filters[] = "<strong>Type:</strong> " . ucfirst(str_replace('_', ' ', $type_filter));
                if(!empty($student_filter)) {
                    $student_name = '';
                    $student_name_query = "SELECT name FROM students WHERE id = '$student_filter'";
                    $student_name_result = mysqli_query($conn, $student_name_query);
                    if($student_name_result && mysqli_num_rows($student_name_result) > 0) {
                        $student_name_row = mysqli_fetch_assoc($student_name_result);
                        $student_name = $student_name_row['name'];
                    }
                    $filters[] = "<strong>Student:</strong> " . htmlspecialchars($student_name);
                }
                echo implode(' | ', $filters);
                ?>
                <span class="ms-2 text-muted">(<?php echo $filtered_stats['total'] ?? 0; ?> results)</span>
            </div>
        <?php endif; ?>

        <!-- ============================================ -->
        <!-- TABS (Quick Filter)                         -->
        <!-- ============================================ -->
        <div class="tabs">
            <a href="Request_student.php" class="tab <?php echo empty($status_filter) ? 'active' : ''; ?>">
                All <span class="tab-badge"><?php echo $stats['total'] ?? 0; ?></span>
            </a>
            <a href="?status=pending" class="tab <?php echo $status_filter == 'pending' ? 'active' : ''; ?>">
                Pending <span class="tab-badge"><?php echo $stats['pending'] ?? 0; ?></span>
            </a>
            <a href="?status=approved" class="tab <?php echo $status_filter == 'approved' ? 'active' : ''; ?>">
                Approved <span class="tab-badge"><?php echo $stats['approved'] ?? 0; ?></span>
            </a>
            <a href="?status=rejected" class="tab <?php echo $status_filter == 'rejected' ? 'active' : ''; ?>">
                Rejected <span class="tab-badge"><?php echo $stats['rejected'] ?? 0; ?></span>
            </a>
            <a href="?status=completed" class="tab <?php echo $status_filter == 'completed' ? 'active' : ''; ?>">
                Completed <span class="tab-badge"><?php echo $stats['completed'] ?? 0; ?></span>
            </a>
        </div>

        <!-- ============================================ -->
        <!-- REQUESTS LIST                                -->
        <!-- ============================================ -->
        <div id="requestsList">
            <?php if (!empty($requests)): ?>
                <?php foreach ($requests as $request): 
                    $status_class = $request['status'] == 'approved' ? 'badge-approved' : 
                                   ($request['status'] == 'pending' ? 'badge-pending' :
                                   ($request['status'] == 'completed' ? 'badge-completed' : 'badge-rejected'));
                    
                    $type_class = 'type-badge';
                    $type_lower = strtolower($request['request_type'] ?? 'general');
                    if($type_lower == 'leave') $type_class .= ' leave';
                    elseif($type_lower == 'exam') $type_class .= ' exam';
                    elseif($type_lower == 'certificate') $type_class .= ' certificate';
                    elseif($type_lower == 'transcript') $type_class .= ' transcript';
                    elseif($type_lower == 'scholarship') $type_class .= ' scholarship';
                ?>
                    <div class="request-card <?php echo $request['status']; ?>" 
                         data-status="<?php echo $request['status']; ?>"
                         data-student="<?php echo strtolower($request['student_name'] ?? ''); ?>"
                         data-type="<?php echo $request['request_type'] ?? 'general'; ?>">
                        
                        <div class="d-flex justify-content-between align-items-start flex-wrap">
                            <div style="flex: 1;">
                                <div class="d-flex align-items-center gap-3 flex-wrap">
                                    <h5 class="mb-1">
                                        <?php echo htmlspecialchars($request['student_name'] ?? 'Unknown Student'); ?>
                                        <?php if($request['status'] == 'pending'): ?>
                                            <span class="badge bg-warning text-dark ms-2">New</span>
                                        <?php endif; ?>
                                    </h5>
                                    <span class="<?php echo $type_class; ?>">
                                        <?php echo ucfirst(str_replace('_', ' ', $request['request_type'] ?? 'General')); ?>
                                    </span>
                                </div>
                                
                                <div class="text-muted small mt-1">
                                    <i class="fas fa-id-card"></i> ID: <?php echo htmlspecialchars($request['student_code'] ?? 'N/A'); ?>
                                </div>
                                
                                <div class="mt-2">
                                    <strong class="request-subject">Subject:</strong> <?php echo htmlspecialchars($request['subject'] ?? 'N/A'); ?>
                                </div>
                                
                                <?php if(!empty($request['description'])): ?>
                                    <div class="mt-2 p-2 bg-light rounded text-muted small">
                                        <i class="fas fa-align-left"></i> 
                                        <?php echo nl2br(htmlspecialchars(substr($request['description'], 0, 150))); ?>
                                        <?php if(strlen($request['description']) > 150): ?>...<?php endif; ?>
                                    </div>
                                <?php endif; ?>
                                
                                <?php if(!empty($request['admin_comment'])): ?>
                                    <div class="mt-2 text-muted small">
                                        <i class="fas fa-comment text-primary"></i> 
                                        <strong>Admin Comment:</strong> <?php echo htmlspecialchars($request['admin_comment']); ?>
                                    </div>
                                <?php endif; ?>
                                
                                <div class="text-muted small mt-1">
                                    <i class="fas fa-calendar-alt"></i> 
                                    Submitted: <?php echo date('d/m/Y H:i', strtotime($request['created_at'] ?? 'now')); ?>
                                    <?php if(!empty($request['updated_at']) && $request['updated_at'] != $request['created_at']): ?>
                                        <i class="fas fa-edit ms-3"></i> 
                                        Updated: <?php echo date('d/m/Y H:i', strtotime($request['updated_at'])); ?>
                                    <?php endif; ?>
                                </div>
                            </div>
                            
                            <div class="text-end">
                                <span class="badge-status <?php echo $status_class; ?>">
                                    <i class="fas fa-circle" style="font-size: 8px;"></i>
                                    <?php echo ucfirst($request['status']); ?>
                                </span>
                                
                                <?php if($request['status'] == 'pending'): ?>
                                    <div class="mt-2 d-flex gap-2 flex-wrap">
                                        <button class="btn-approve" onclick="openModal('<?php echo $request['id']; ?>', 'approve')">
                                            <i class="fas fa-check"></i> Approve
                                        </button>
                                        <button class="btn-reject" onclick="openModal('<?php echo $request['id']; ?>', 'reject')">
                                            <i class="fas fa-times"></i> Reject
                                        </button>
                                    </div>
                                <?php else: ?>
                                    <div class="mt-2">
                                        <a href="?delete=<?php echo $request['id']; ?>" 
                                           class="btn-delete" 
                                           onclick="return confirm('Are you sure you want to delete this request?')">
                                            <i class="fas fa-trash"></i> Delete
                                        </a>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <div class="text-center py-5 bg-white rounded">
                    <i class="fas fa-inbox" style="font-size: 48px; color: #d1d5db;"></i>
                    <h4 class="mt-3">No Student Requests Found</h4>
                    <p class="text-muted">There are no student requests to display at the moment.</p>
                    <?php if(!empty($status_filter) || !empty($date_from) || !empty($date_to) || !empty($search) || !empty($type_filter) || !empty($student_filter)): ?>
                        <p class="text-muted">Try changing your filter criteria.</p>
                    <?php endif; ?>
                    <small class="text-muted">Total requests in database: <?php echo $total_records; ?></small>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- ============================================ -->
<!-- MODAL FOR APPROVE/REJECT                     -->
<!-- ============================================ -->
<div class="modal fade" id="actionModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST">
                <div class="modal-header">
                    <h5 class="modal-title" id="modalTitle">Approve Request</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" name="request_id" id="requestId">
                    <input type="hidden" name="action" id="actionType">
                    
                    <div class="mb-3">
                        <label class="form-label"><i class="fas fa-comment"></i> Admin Comment (Optional)</label>
                        <textarea class="form-control" name="admin_comment" rows="3" 
                                  placeholder="Add any comments about this request..."></textarea>
                    </div>
                    
                    <div class="alert alert-info">
                        <i class="fas fa-info-circle"></i> 
                        <span id="confirmMessage">Are you sure you want to approve this request?</span>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn" id="submitBtn">Confirm</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
    // ============================================
    // DROPDOWN TOGGLES
    // ============================================
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
    
    // ============================================
    // MODAL FUNCTIONS
    // ============================================
    function openModal(requestId, action) {
        document.getElementById('requestId').value = requestId;
        document.getElementById('actionType').value = action;
        
        const modal = new bootstrap.Modal(document.getElementById('actionModal'));
        const title = document.getElementById('modalTitle');
        const btn = document.getElementById('submitBtn');
        const message = document.getElementById('confirmMessage');
        
        if(action === 'approve') {
            title.textContent = '✅ Approve Request';
            btn.className = 'btn btn-success';
            btn.textContent = 'Approve';
            message.textContent = 'Are you sure you want to approve this request? This action cannot be undone.';
        } else {
            title.textContent = '❌ Reject Request';
            btn.className = 'btn btn-danger';
            btn.textContent = 'Reject';
            message.textContent = 'Are you sure you want to reject this request? This action cannot be undone.';
        }
        
        modal.show();
    }
    
    // ============================================
    // DATE TIME DISPLAY
    // ============================================
    function updateDateTime() {
        const now = new Date();
        const formatted = now.toLocaleString('en-US', { 
            weekday: 'long', 
            year: 'numeric', 
            month: 'long', 
            day: 'numeric',
            hour: '2-digit',
            minute: '2-digit',
            second: '2-digit'
        });
        const dateTimeEl = document.getElementById('currentDateTime');
        if(dateTimeEl) {
            dateTimeEl.innerHTML = '<i class="fas fa-calendar-alt"></i> ' + formatted;
        }
    }
    updateDateTime();
    setInterval(updateDateTime, 1000);
</script>

</body>
</html>