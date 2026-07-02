<?php
include "db.php";

// ============================================
// CHECK ADMIN LOGIN
// ============================================
if(!isset($_SESSION['id']) || $_SESSION['role'] !== 'admin') {
    header("Location: login.php");
    exit();
}

$message = '';
$error = '';

// ============================================
// CHECK AND UPDATE TABLE STRUCTURE
// ============================================
$check_column = "SHOW COLUMNS FROM rules LIKE 'request_type'";
$column_result = mysqli_query($conn, $check_column);
if(mysqli_num_rows($column_result) == 0) {
    $alter_sql = "ALTER TABLE rules 
                  ADD COLUMN request_type VARCHAR(100) DEFAULT 'general' AFTER teacher_name,
                  ADD COLUMN admin_comment TEXT AFTER status,
                  ADD COLUMN request_date DATE AFTER description,
                  ADD COLUMN updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP AFTER created_at,
                  ADD INDEX idx_status (status),
                  ADD INDEX idx_teacher_id (teacher_id)";
    mysqli_query($conn, $alter_sql);
}

// ============================================
// UPDATE DATA FOR OLD RECORDS
// ============================================
// កំណត់ request_type និង request_date សម្រាប់ records ចាស់
$update_old = "UPDATE rules SET request_type = 'general', request_date = apply_date WHERE request_type IS NULL OR request_date IS NULL";
mysqli_query($conn, $update_old);

// ============================================
// PROCESS APPROVE/REJECT
// ============================================
if(isset($_POST['action']) && isset($_POST['rule_id'])) {
    $rule_id = mysqli_real_escape_string($conn, $_POST['rule_id']);
    $action = mysqli_real_escape_string($conn, $_POST['action']);
    $admin_comment = mysqli_real_escape_string($conn, $_POST['admin_comment'] ?? '');
    
    if($action === 'approve') {
        $update_sql = "UPDATE rules SET status = 'active', admin_comment = '$admin_comment' WHERE id = '$rule_id'";
        if(mysqli_query($conn, $update_sql)) {
            $message = "✅ Rule approved successfully!";
        } else {
            $error = "❌ Error approving rule: " . mysqli_error($conn);
        }
    } elseif($action === 'reject') {
        $update_sql = "UPDATE rules SET status = 'inactive', admin_comment = '$admin_comment' WHERE id = '$rule_id'";
        if(mysqli_query($conn, $update_sql)) {
            $message = "✅ Rule rejected successfully!";
        } else {
            $error = "❌ Error rejecting rule: " . mysqli_error($conn);
        }
    }
}

// ============================================
// DELETE RULE
// ============================================
if(isset($_GET['delete']) && is_numeric($_GET['delete'])) {
    $rule_id = $_GET['delete'];
    $delete_sql = "DELETE FROM rules WHERE id = '$rule_id'";
    
    if(mysqli_query($conn, $delete_sql)) {
        $message = "✅ Rule deleted successfully!";
    } else {
        $error = "❌ Error deleting rule: " . mysqli_error($conn);
    }
}

// ============================================
// GET ALL RULES WITH TEACHER INFO
// ============================================
$query = "SELECT r.*, 
          (SELECT COUNT(*) FROM rule_days WHERE rule_id = r.id) as day_count
          FROM rules r 
          ORDER BY 
          CASE WHEN r.status = 'pending' THEN 0 
               WHEN r.status = 'active' THEN 1 
               ELSE 2 END, 
          r.created_at DESC";
$result = mysqli_query($conn, $query);

// ============================================
// GET RULE DAYS
// ============================================
$rule_days = [];
$all_rules = mysqli_query($conn, "SELECT id FROM rules");
while($rule = mysqli_fetch_assoc($all_rules)) {
    $days_query = "SELECT day_of_week FROM rule_days WHERE rule_id = '{$rule['id']}'";
    $days_result = mysqli_query($conn, $days_query);
    $days = [];
    while($day = mysqli_fetch_assoc($days_result)) {
        $days[] = $day['day_of_week'];
    }
    $rule_days[$rule['id']] = $days;
}

// ============================================
// GET STATISTICS
// ============================================
$stats_query = "SELECT 
                COUNT(CASE WHEN status = 'pending' THEN 1 END) as pending,
                COUNT(CASE WHEN status = 'active' THEN 1 END) as approved,
                COUNT(CASE WHEN status = 'inactive' THEN 1 END) as rejected,
                COUNT(CASE WHEN status = 'expired' THEN 1 END) as expired,
                COUNT(*) as total
                FROM rules";
$stats_result = mysqli_query($conn, $stats_query);
$stats = mysqli_fetch_assoc($stats_result);

// ============================================
// GET TEACHERS LIST FOR FILTER
// ============================================
$teachers_query = "SELECT DISTINCT teacher_id, teacher_name FROM rules ORDER BY teacher_name";
$teachers_result = mysqli_query($conn, $teachers_query);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Admin - Teacher Request Management</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        /* Table Container */
        .table-container {
            max-height: 340px;
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
        
        .kpi-row { 
            display: flex; 
            flex-wrap: wrap; 
            gap: 20px; 
            margin-bottom: 32px; 
            color:black;
        }
        .kpi-card { 
            border-radius: 20px; 
            padding: 20px 24px; 
            flex: 1; 
            min-width: 160px;
            box-shadow: 0 10px 25px rgba(0,0,0,0.1);
            transition: transform 0.3s ease;
            color:black;
            background: white;
            border-bottom: 8px solid;
            text-align: left;
        }
        .kpi-card:hover { transform: translateY(-5px); }
        .kpi-title { 
            font-size: 14px; 
            text-transform: uppercase; 
            letter-spacing: 1px; 
            opacity: 0.9; 
            margin-bottom: 10px; 
            font-weight: 500; 
            color:black;
        }
        .kpi-number { 
            font-size: 24px; 
            font-weight: bold; 
            margin-bottom: 5px; 
        }
        
        .rule-card {
            background: white;
            border-radius: 12px;
            padding: 18px 22px;
            margin-bottom: 15px;
            border-left: 5px solid #6366f1;
            box-shadow: 0 2px 8px rgba(0,0,0,0.06);
            transition: all 0.3s;
        }
        .rule-card:hover {
            transform: translateX(5px);
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
        }
        .rule-card.pending { border-left-color: #f59e0b; }
        .rule-card.active { border-left-color: #10b981; }
        .rule-card.inactive { border-left-color: #ef4444; }
        .rule-card.expired { border-left-color: #8b5cf6; }
        
        .badge-status {
            padding: 4px 14px;
            border-radius: 20px;
            font-size: 0.75rem;
            font-weight: 600;
        }
        .badge-pending { background: #fef3c7; color: #92400e; }
        .badge-active { background: #d1fae5; color: #065f46; }
        .badge-inactive { background: #fee2e2; color: #991b1b; }
        .badge-expired { background: #ede9fe; color: #5b21b6; }
        
        .btn-approve {
            background: #10b981;
            color: white;
            border: none;
            padding: 6px 18px;
            border-radius: 8px;
            font-weight: 500;
            transition: 0.3s;
        }
        .btn-approve:hover { background: #059669; color: white; }
        
        .btn-reject {
            background: #ef4444;
            color: white;
            border: none;
            padding: 6px 18px;
            border-radius: 8px;
            font-weight: 500;
            transition: 0.3s;
        }
        .btn-reject:hover { background: #dc2626; color: white; }
        
        .btn-delete {
            background: #6b7280;
            color: white;
            border: none;
            padding: 6px 18px;
            border-radius: 8px;
            font-weight: 500;
            transition: 0.3s;
        }
        .btn-delete:hover { background: #4b5563; color: white; }
        
        .filter-section {
            background: white;
            padding: 15px 20px;
            border-radius: 12px;
            margin-bottom: 20px;
            display: flex;
            gap: 15px;
            flex-wrap: wrap;
            align-items: center;
            box-shadow: 0 2px 8px rgba(0,0,0,0.05);
        }
        .filter-section select, .filter-section input {
            padding: 8px 15px;
            border-radius: 8px;
            border: 1px solid #d1d5db;
            font-size: 0.9rem;
        }
        .filter-section select:focus, .filter-section input:focus {
            outline: none;
            border-color: #6366f1;
            box-shadow: 0 0 0 3px rgba(99, 102, 241, 0.1);
        }
        
        .tabs {
            display: flex;
            gap: 5px;
            margin-bottom: 20px;
            background: white;
            padding: 5px;
            border-radius: 12px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.05);
            flex-wrap: wrap;
        }
        .tab {
            padding: 10px 25px;
            border-radius: 10px;
            border: none;
            background: transparent;
            font-weight: 500;
            color: #64748b;
            transition: all 0.3s;
            cursor: pointer;
        }
        .tab:hover { background: #f1f5f9; }
        .tab.active { background: #6366f1; color: white; }
        .tab-badge {
            background: #e2e8f0;
            color: #475569;
            padding: 2px 8px;
            border-radius: 20px;
            font-size: 0.7rem;
            margin-left: 5px;
        }
        .tab.active .tab-badge {
            background: rgba(255,255,255,0.2);
            color: white;
        }
        
        .dropdown-container { margin-bottom: 10px; }
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
        .sub-menu:hover { background: rgba(110, 41, 238, 0.6); }
        .dropdown-icon { transition: 0.3s; }
        
        .day-badge {
            background: #e2e8f0;
            padding: 2px 10px;
            border-radius: 20px;
            font-size: 0.75rem;
            color: #334155;
            display: inline-block;
            margin: 2px;
        }
        .day-badge.active-day {
            background: #4f46e5;
            color: white;
        }
        
        .rule-days {
            display: flex;
            gap: 5px;
            flex-wrap: wrap;
            margin-top: 5px;
        }
        
        @media (max-width: 768px) {
            .sidebar { width: 80px; }
            .sidebar-header h1, .sidebar-header p, .nav-item span { display: none; }
            .nav-item { justify-content: center; }
            .main-content { padding: 15px; }
            .kpi-card { min-width: 120px; }
            .kpi-number { font-size: 18px; }
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
    <div class="sidebar">
        <div class="sidebar-header">
            <img src="https://i.pinimg.com/736x/6a/33/e1/6a33e1b0b8fbb948cbf04b7397c9b381.jpg" alt="Logo" />
            <h1>KRaksa</h1>
            <p>Education Suite</p>
        </div>
        <div class="nav-menu">
            <a href="index.php" class="nav-item" data-page="dashboard"><i class="fas fa-tachometer-alt"></i> <span>Main Dashboard</span></a>
            
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
            
            <a href="teacher.php" class="nav-item" data-page="teachers"><i class="fas fa-chalkboard-teacher"></i> <span>Teachers</span></a>
            <a href="Courses.php" class="nav-item" data-page="courses"><i class="fas fa-graduation-cap"></i> <span>Department</span></a>
            <a href="schedule.php" class="nav-item" data-page="schedule"> <i class="fas fa-calendar-alt"></i> <span>Schedule class</span></a>
            
            <!-- Request Dropdown -->
            <div class="dropdown-container">
                <div class="nav-item dropdown-btn" onclick="toggleRequestDropdown()">
                    <div>
                        <i class="fas fa-file-pdf"></i> 
                        <span class="m-2"> Request</span>
                    </div>
                    <i class="fas fa-chevron-down dropdown-icon" id="RequestDropdownIcon"></i>
                </div>
                <div class="dropdown-menus" id="RequestDropdownMenu">
                    <a href="Request_teacher_admin.php" class="nav-item sub-menu active"><i class="fas fa-chalkboard-teacher"></i><span>Teacher</span></a>
                    <a href="Request_student.php" class="nav-item sub-menu"><i class="fas fa-users"></i><span>Student</span></a>
                </div>
            </div>
            
            <!-- Report Dropdown -->
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
            <a href="Employees.php" class="nav-item" data-page="employees"><i class="fas fa-user-friends"></i> <span>Employees</span></a>
            <a href="StudentAttendance.php" class="nav-item" data-page="attendance"><i class="fas fa-calendar-check"></i> <span>Attendance</span></a>
            
            <div class="nav-bottom">
                <a href="logout.php" class="nav-item" style="padding-left:8px;">
                    <i class="fas fa-sign-out-alt"></i> <span>Logout</span>
                </a>
            </div>
        </div>
    </div>

    <div class="main-content">
        <div class="top-bar">
            <div class="page-title"><h2 id="dynamicTitle"><i class="fas fa-clipboard-list"></i> Teacher Request Management</h2></div>
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
            <div class="kpi-card" style="border-bottom-color: #f59e0b;">
                <div class="kpi-title"><i class="fas fa-clock"></i> Pending</div>
                <div class="kpi-number" style="color: #f59e0b;"><?php echo $stats['pending'] ?? 0; ?></div>
            </div>
            <div class="kpi-card" style="border-bottom-color: #10b981;">
                <div class="kpi-title"><i class="fas fa-check-circle"></i> Approved</div>
                <div class="kpi-number" style="color: #10b981;"><?php echo $stats['approved'] ?? 0; ?></div>
            </div>
            <div class="kpi-card" style="border-bottom-color: #ef4444;">
                <div class="kpi-title"><i class="fas fa-times-circle"></i> Rejected</div>
                <div class="kpi-number" style="color: #ef4444;"><?php echo $stats['rejected'] ?? 0; ?></div>
            </div>
            <div class="kpi-card" style="border-bottom-color: #8b5cf6;">
                <div class="kpi-title"><i class="fas fa-calendar-times"></i> Expired</div>
                <div class="kpi-number" style="color: #8b5cf6;"><?php echo $stats['expired'] ?? 0; ?></div>
            </div>
            <div class="kpi-card" style="border-bottom-color: #6366f1;">
                <div class="kpi-title"><i class="fas fa-tasks"></i> Total</div>
                <div class="kpi-number" style="color: #6366f1;"><?php echo $stats['total'] ?? 0; ?></div>
            </div>
        </div>

        <!-- ============================================ -->
        <!-- TABS                                         -->
        <!-- ============================================ -->
        <div class="tabs">
            <button class="tab active" onclick="filterRules('all')">
                All <span class="tab-badge"><?php echo $stats['total'] ?? 0; ?></span>
            </button>
            <button class="tab" onclick="filterRules('pending')">
                Pending <span class="tab-badge"><?php echo $stats['pending'] ?? 0; ?></span>
            </button>
            <button class="tab" onclick="filterRules('active')">
                Approved <span class="tab-badge"><?php echo $stats['approved'] ?? 0; ?></span>
            </button>
            <button class="tab" onclick="filterRules('inactive')">
                Rejected <span class="tab-badge"><?php echo $stats['rejected'] ?? 0; ?></span>
            </button>
            <button class="tab" onclick="filterRules('expired')">
                Expired <span class="tab-badge"><?php echo $stats['expired'] ?? 0; ?></span>
            </button>
        </div>

        <!-- ============================================ -->
        <!-- FILTER SECTION                               -->
        <!-- ============================================ -->
        <div class="filter-section">
            <i class="fas fa-search text-muted"></i>
            <input type="text" class="form-control form-control-sm" style="width: 200px;" 
                   placeholder="Search teacher..." onkeyup="searchRules(this.value)">
            
            <i class="fas fa-filter text-muted ms-3"></i>
            <select class="form-select form-select-sm" style="width: auto;" onchange="filterByType(this.value)">
                <option value="all">All Types</option>
                <option value="attendance">Attendance</option>
                <option value="behavior">Behavior</option>
                <option value="uniform">Uniform</option>
                <option value="homework">Homework</option>
                <option value="exam">Exam</option>
                <option value="general">General</option>
                <option value="other">Other</option>
            </select>
            
            <i class="fas fa-user text-muted ms-3"></i>
            <select class="form-select form-select-sm" style="width: auto;" onchange="filterByTeacher(this.value)">
                <option value="all">All Teachers</option>
                <?php while($teacher = mysqli_fetch_assoc($teachers_result)): ?>
                    <option value="<?php echo htmlspecialchars($teacher['teacher_name']); ?>">
                        <?php echo htmlspecialchars($teacher['teacher_name']); ?>
                    </option>
                <?php endwhile; ?>
            </select>
        </div>

        <!-- ============================================ -->
        <!-- RULES LIST                                   -->
        <!-- ============================================ -->
        <div id="rulesList">
            <?php if($result && mysqli_num_rows($result) > 0): ?>
                <?php while($rule = mysqli_fetch_assoc($result)): 
                    $days = $rule_days[$rule['id']] ?? [];
                    $status_class = $rule['status'] == 'active' ? 'badge-active' : 
                                   ($rule['status'] == 'pending' ? 'badge-pending' :
                                   ($rule['status'] == 'expired' ? 'badge-expired' : 'badge-inactive'));
                ?>
                    <div class="rule-card <?php echo $rule['status']; ?>" 
                         data-status="<?php echo $rule['status']; ?>"
                         data-teacher="<?php echo strtolower($rule['teacher_name']); ?>"
                         data-type="<?php echo $rule['rule_type'] ?? 'general'; ?>">
                        
                        <div class="d-flex justify-content-between align-items-start flex-wrap">
                            <div style="flex: 1;">
                                <div class="d-flex align-items-center gap-3 flex-wrap">
                                    <h5 class="mb-1">
                                        <?php echo htmlspecialchars($rule['title']); ?>
                                        <?php if($rule['status'] == 'pending'): ?>
                                            <span class="badge bg-warning text-dark ms-2">New</span>
                                        <?php endif; ?>
                                    </h5>
                                    <span class="badge bg-secondary"><?php echo ucfirst($rule['rule_type'] ?? 'General'); ?></span>
                                </div>
                                
                                <div class="text-muted small mt-1">
                                    <i class="fas fa-user"></i> <?php echo htmlspecialchars($rule['teacher_name']); ?>
                                    <i class="fas fa-calendar ms-3"></i> 
                                    Apply: <?php echo date('d/m/Y', strtotime($rule['apply_date'])); ?>
                                    <?php if($rule['end_date']): ?>
                                        <i class="fas fa-calendar-end ms-2"></i> 
                                        Until: <?php echo date('d/m/Y', strtotime($rule['end_date'])); ?>
                                    <?php endif; ?>
                                    <i class="fas fa-clock ms-3"></i> <?php echo date('H:i', strtotime($rule['created_at'])); ?>
                                </div>
                                
                                <?php if(!empty($rule['description'])): ?>
                                    <div class="mt-2 p-2 bg-light rounded text-muted small">
                                        <i class="fas fa-align-left"></i> <?php echo nl2br(htmlspecialchars($rule['description'])); ?>
                                    </div>
                                <?php endif; ?>
                                
                                <?php if(!empty($days)): ?>
                                    <div class="rule-days">
                                        <i class="fas fa-calendar-week text-muted small"></i>
                                        <?php foreach($days as $day): ?>
                                            <span class="day-badge active-day"><?php echo substr($day, 0, 3); ?></span>
                                        <?php endforeach; ?>
                                    </div>
                                <?php endif; ?>
                                
                                <?php if(!empty($rule['admin_comment'])): ?>
                                    <div class="mt-2 text-muted small">
                                        <i class="fas fa-comment text-primary"></i> 
                                        <strong>Admin Comment:</strong> <?php echo htmlspecialchars($rule['admin_comment']); ?>
                                    </div>
                                <?php endif; ?>
                            </div>
                            
                            <div class="text-end">
                                <span class="badge-status <?php echo $status_class; ?>">
                                    <i class="fas fa-circle" style="font-size: 8px;"></i>
                                    <?php echo ucfirst($rule['status']); ?>
                                </span>
                                
                                <?php if($rule['status'] == 'pending'): ?>
                                    <div class="mt-2 d-flex gap-2 flex-wrap">
                                        <button class="btn-approve" onclick="openModal('<?php echo $rule['id']; ?>', 'approve')">
                                            <i class="fas fa-check"></i> Approve
                                        </button>
                                        <button class="btn-reject" onclick="openModal('<?php echo $rule['id']; ?>', 'reject')">
                                            <i class="fas fa-times"></i> Reject
                                        </button>
                                    </div>
                                <?php else: ?>
                                    <div class="mt-2">
                                        <a href="?delete=<?php echo $rule['id']; ?>" 
                                           class="btn-delete" 
                                           onclick="return confirm('Are you sure you want to delete this rule?')">
                                            <i class="fas fa-trash"></i> Delete
                                        </a>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                <?php endwhile; ?>
            <?php else: ?>
                <div class="text-center py-5 bg-white rounded">
                    <i class="fas fa-inbox" style="font-size: 48px; color: #d1d5db;"></i>
                    <h4 class="mt-3">No Rules Found</h4>
                    <p class="text-muted">There are no teacher rules to display at the moment.</p>
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
                    <h5 class="modal-title" id="modalTitle">Approve Rule</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" name="rule_id" id="ruleId">
                    <input type="hidden" name="action" id="actionType">
                    
                    <div class="mb-3">
                        <label class="form-label"><i class="fas fa-comment"></i> Admin Comment (Optional)</label>
                        <textarea class="form-control" name="admin_comment" rows="3" 
                                  placeholder="Add any comments about this rule..."></textarea>
                    </div>
                    
                    <div class="alert alert-info">
                        <i class="fas fa-info-circle"></i> 
                        <span id="confirmMessage">Are you sure you want to approve this rule?</span>
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
    // FILTER FUNCTIONS
    // ============================================
    function filterRules(status) {
        const cards = document.querySelectorAll('.rule-card');
        const tabs = document.querySelectorAll('.tab');
        
        tabs.forEach(tab => tab.classList.remove('active'));
        event.target.classList.add('active');
        
        cards.forEach(card => {
            if(status === 'all' || card.dataset.status === status) {
                card.style.display = 'block';
            } else {
                card.style.display = 'none';
            }
        });
    }
    
    function searchRules(query) {
        const cards = document.querySelectorAll('.rule-card');
        const search = query.toLowerCase().trim();
        const currentStatus = document.querySelector('.tab.active')?.textContent?.toLowerCase().trim() || 'all';
        
        cards.forEach(card => {
            const teacher = card.dataset.teacher || '';
            const statusMatch = currentStatus === 'all' || card.dataset.status === currentStatus;
            const searchMatch = search === '' || teacher.includes(search);
            
            if(statusMatch && searchMatch) {
                card.style.display = 'block';
            } else {
                card.style.display = 'none';
            }
        });
    }
    
    function filterByType(type) {
        const cards = document.querySelectorAll('.rule-card');
        const currentStatus = document.querySelector('.tab.active')?.textContent?.toLowerCase().trim() || 'all';
        
        cards.forEach(card => {
            const cardType = card.dataset.type || 'general';
            const statusMatch = currentStatus === 'all' || card.dataset.status === currentStatus;
            const typeMatch = type === 'all' || cardType === type;
            
            if(statusMatch && typeMatch) {
                card.style.display = 'block';
            } else {
                card.style.display = 'none';
            }
        });
    }
    
    function filterByTeacher(teacher) {
        const cards = document.querySelectorAll('.rule-card');
        const currentStatus = document.querySelector('.tab.active')?.textContent?.toLowerCase().trim() || 'all';
        
        cards.forEach(card => {
            const cardTeacher = card.dataset.teacher || '';
            const statusMatch = currentStatus === 'all' || card.dataset.status === currentStatus;
            const teacherMatch = teacher === 'all' || cardTeacher === teacher.toLowerCase();
            
            if(statusMatch && teacherMatch) {
                card.style.display = 'block';
            } else {
                card.style.display = 'none';
            }
        });
    }
    
    // ============================================
    // MODAL FUNCTIONS
    // ============================================
    function openModal(ruleId, action) {
        document.getElementById('ruleId').value = ruleId;
        document.getElementById('actionType').value = action;
        
        const modal = new bootstrap.Modal(document.getElementById('actionModal'));
        const title = document.getElementById('modalTitle');
        const btn = document.getElementById('submitBtn');
        const message = document.getElementById('confirmMessage');
        
        if(action === 'approve') {
            title.textContent = '✅ Approve Rule';
            btn.className = 'btn btn-success';
            btn.textContent = 'Approve';
            message.textContent = 'Are you sure you want to approve this rule? This action cannot be undone.';
        } else {
            title.textContent = '❌ Reject Rule';
            btn.className = 'btn btn-danger';
            btn.textContent = 'Reject';
            message.textContent = 'Are you sure you want to reject this rule? This action cannot be undone.';
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