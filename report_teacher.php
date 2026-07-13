<?php
include 'db.php';
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Get filters
$search = isset($_GET['search']) ? mysqli_real_escape_string($conn, $_GET['search']) : '';
$gender = isset($_GET['gender']) ? mysqli_real_escape_string($conn, $_GET['gender']) : '';
$subject = isset($_GET['subject']) ? mysqli_real_escape_string($conn, $_GET['subject']) : '';

// Build base query
$base_sql = "FROM teachers t
             LEFT JOIN courses c ON t.id = c.teacher_id
             WHERE 1=1";

if (!empty($search)) {
    $base_sql .= " AND (t.name LIKE '%$search%' OR t.email LIKE '%$search%' OR t.phone LIKE '%$search%')";
}
if (!empty($gender)) {
    $base_sql .= " AND t.gender = '$gender'";
}
if (!empty($subject)) {
    $base_sql .= " AND t.subject LIKE '%$subject%'";
}

// Get total subjects count
$total_subjects_query = "SELECT COUNT(DISTINCT subject) as total_subjects FROM teachers WHERE subject IS NOT NULL AND subject != ''";
$total_subjects_result = mysqli_query($conn, $total_subjects_query);
$total_subjects = mysqli_fetch_assoc($total_subjects_result)['total_subjects'] ?? 0;

// Query for summary statistics
$summary_sql = "SELECT 
                COUNT(DISTINCT t.id) as total_teachers,
                SUM(CASE WHEN t.gender = 'Male' THEN 1 ELSE 0 END) as male_count,
                SUM(CASE WHEN t.gender = 'Female' THEN 1 ELSE 0 END) as female_count,
                SUM(CASE WHEN LOWER(t.Status) = 'active' THEN 1 ELSE 0 END) as active_count,
                SUM(CASE WHEN LOWER(t.Status) = 'inactive' THEN 1 ELSE 0 END) as inactive_count,
                SUM(CASE WHEN LOWER(t.Status) = 'on leave' THEN 1 ELSE 0 END) as on_leave_count,
                COUNT(DISTINCT c.id) as total_courses_taught,
                COUNT(DISTINCT CASE WHEN t.subject IS NOT NULL AND t.subject != '' THEN t.subject END) as filtered_subjects
                $base_sql";

$summary_result = mysqli_query($conn, $summary_sql);
if (!$summary_result) {
    die("Query failed: " . mysqli_error($conn));
}
$stats = mysqli_fetch_assoc($summary_result);

$total_teachers = $stats['total_teachers'] ?? 0;
$male_count = $stats['male_count'] ?? 0;
$female_count = $stats['female_count'] ?? 0;
$active_count = $stats['active_count'] ?? 0;
$inactive_count = $stats['inactive_count'] ?? 0;
$on_leave_count = $stats['on_leave_count'] ?? 0;
$total_courses_taught = $stats['total_courses_taught'] ?? 0;
$filtered_subjects = $stats['filtered_subjects'] ?? 0;

// Query for detailed teacher data with teaching log
$sql = "SELECT t.*, 
        COUNT(DISTINCT c.id) as total_courses,
        GROUP_CONCAT(DISTINCT c.name SEPARATOR ', ') as course_names,
        (SELECT COUNT(*) FROM teaching_log tl WHERE tl.teacher_id = t.id) as total_teaching_sessions,
        (SELECT COUNT(*) FROM teaching_log tl WHERE tl.teacher_id = t.id AND tl.status = 'completed') as completed_sessions,
        (SELECT COUNT(*) FROM teaching_log tl WHERE tl.teacher_id = t.id AND tl.status = 'pending') as pending_sessions
        $base_sql
        GROUP BY t.id 
        ORDER BY t.id DESC";

$result = mysqli_query($conn, $sql);
if (!$result) {
    die("Query failed: " . mysqli_error($conn));
}

// Get unique subjects for filter
$subjects_list = mysqli_query($conn, "SELECT DISTINCT subject FROM teachers WHERE subject IS NOT NULL AND subject != '' ORDER BY subject");
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>KRaksa - Teacher Report</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.4/css/dataTables.bootstrap5.min.css">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        a { text-decoration: none; }
        body { font-family: "Inter", sans-serif; background: #c5e1fc; color: #0f172a; }
        .container { display: flex; min-height: 100vh; max-width: 100%; }
        .sidebar { 
            width: 280px; 
            background: linear-gradient(90deg,rgba(117, 82, 243, 1) 19%, rgba(64, 24, 157, 1) 95%);
            color: #e2e8f0; 
            flex-shrink: 0; 
            position: sticky; 
            top: 0; 
            height: 100vh; 
            overflow-y: auto; 
            margin-left: -12px;
        }
        .dropdown-container { margin-bottom: 10px; }
        .dropdown-btn { display: flex; justify-content: space-between; align-items: center; cursor: pointer; }
        .dropdown-menus { display: none; padding-left: 15px; margin-top: 5px; }
        .sub-menu { font-size: 14px; padding: 10px 15px; margin-bottom: 5px; background: rgba(88, 30, 248, 0.61); border-radius: 12px; }
        .sub-menu:hover { background: rgba(110, 41, 238, 0.8); }
        .dropdown-icon { transition: 0.3s; }
        .sidebar-header { padding: 28px 24px; border-bottom: 1px solid rgba(255, 255, 255, 0.1); text-align: center; }
        .sidebar-header img { width: 90px; height: 90px; border-radius: 50%; border: 3px solid white; }
        .sidebar-header h1 { font-size: 1.5rem; color: white; margin-top: 10px; }
        .nav-item { display: flex; align-items: center; gap: 12px; padding: 12px 16px; margin: 5px 10px; border-radius: 12px; color: #cbd5e6; transition: 0.3s; }
        .nav-item:hover, .nav-item.active { background: #fd0054; color: white; }
        .main-content { flex: 1; padding: 25px 30px; background: #f8fafc; overflow-y: auto; height: 100vh; }
        .top-bar { background: white; border-radius: 50px; padding: 12px 25px; margin-bottom: 25px; box-shadow: 0 2px 5px rgba(0,0,0,0.05); display: flex; justify-content: space-between; align-items: center; }
        
        .kpi-row { display: flex; gap: 20px; margin-bottom: 25px; flex-wrap: wrap; }
        .kpi-card { background: white; border-radius: 15px; padding: 20px; flex: 1; min-width: 150px; box-shadow: 0 2px 10px rgba(0,0,0,0.05); border-left: 4px solid #667eea; transition: transform 0.2s; }
        .kpi-card:hover { transform: translateY(-5px); }
        .kpi-card:nth-child(1) { border-left-color: #667eea; }
        .kpi-card:nth-child(2) { border-left-color: #28a745; }
        .kpi-card:nth-child(3) { border-left-color: #ffc107; }
        .kpi-card:nth-child(4) { border-left-color: #17a2b8; }
        .kpi-card:nth-child(5) { border-left-color: #fd7e14; }
        .kpi-number { font-size: 28px; font-weight: bold; color: #2c3e50; }
        .kpi-title { color: #6c757d; font-size: 14px; margin-bottom: 10px; }
        .student-stat-item { display: flex; justify-content: space-between; padding: 5px 0; }
        .student-stat-label { color: #6c757d; font-size: 13px; }
        .student-stat-value { font-weight: bold; color: #2c3e50; }
        .filter-badge { font-size: 11px; color: #6c757d; margin-top: 5px; display: block; }
        
        .filter-section { background: white; border-radius: 15px; padding: 20px; margin-bottom: 25px; box-shadow: 0 2px 10px rgba(0,0,0,0.05); }
        .report-container { background: white; border-radius: 15px; padding: 20px; box-shadow: 0 2px 10px rgba(0,0,0,0.05); }
        
        .btn-print { background: #17a2b8; color: white; border: none; padding: 8px 20px; border-radius: 10px; margin-left: 10px; transition: all 0.2s; }
        .btn-excel { background: #28a745; color: white; border: none; padding: 8px 20px; border-radius: 10px; transition: all 0.2s; }
        .btn-pdf { background: #dc3545; color: white; border: none; padding: 8px 20px; border-radius: 10px; margin-left: 10px; transition: all 0.2s; }
        .btn-print:hover, .btn-excel:hover, .btn-pdf:hover { transform: translateY(-2px); opacity: 0.9; }
        
        .teacher-img { width: 40px; height: 40px; border-radius: 50%; object-fit: cover; }
        
        /* Action buttons style */
        .action-btn {
            padding: 4px 10px;
            margin: 2px;
            border-radius: 20px;
            font-size: 12px;
            border: none;
            transition: all 0.2s;
        }
        .action-btn:hover {
            transform: scale(1.05);
        }
        .btn-view-activity {
            background: #4f46e5;
            color: white;
        }
        .btn-view-activity:hover {
            background: #4338ca;
            color: white;
        }
        .btn-view-schedule {
            background: #0ea5e9;
            color: white;
        }
        .btn-view-schedule:hover {
            background: #0284c7;
            color: white;
        }
        .btn-view-classes {
            background: #10b981;
            color: white;
        }
        .btn-view-classes:hover {
            background: #059669;
            color: white;
        }
        
        /* Activity log styles */
        .activity-timeline {
            position: relative;
            padding-left: 30px;
        }
        .activity-timeline::before {
            content: '';
            position: absolute;
            left: 10px;
            top: 0;
            bottom: 0;
            width: 2px;
            background: #e5e7eb;
        }
        .activity-item {
            position: relative;
            padding: 12px 0;
            border-bottom: 1px solid #f3f4f6;
        }
        .activity-item::before {
            content: '';
            position: absolute;
            left: -24px;
            top: 16px;
            width: 12px;
            height: 12px;
            border-radius: 50%;
            background: #4f46e5;
            border: 2px solid white;
            box-shadow: 0 0 0 3px #4f46e5;
        }
        .activity-item.completed::before {
            background: #10b981;
            box-shadow: 0 0 0 3px #10b981;
        }
        .activity-item.pending::before {
            background: #f59e0b;
            box-shadow: 0 0 0 3px #f59e0b;
        }
        .activity-item .activity-time {
            font-size: 12px;
            color: #6b7280;
        }
        .activity-item .activity-status {
            font-size: 12px;
            font-weight: 600;
        }
        
        @media print {
            .sidebar, .top-bar, .filter-section, .btn-print, .btn-excel, .btn-pdf, .dataTables_filter, .dataTables_length, .dataTables_paginate {
                display: none !important;
            }
            .main-content { padding: 0; margin: 0; }
            .kpi-card { break-inside: avoid; }
        }
        
        @media (max-width: 768px) {
            .sidebar { width: 80px; }
            .sidebar-header h1, .sidebar-header p, .nav-item span { display: none; }
            .main-content { padding: 15px; }
            .kpi-card { min-width: 120px; }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="sidebar">
            <div class="sidebar-header">
                <img src="https://i.pinimg.com/736x/6a/33/e1/6a33e1b0b8fbb948cbf04b7397c9b381.jpg" alt="Logo">
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
                
                <a href="teacher.php" class="nav-item" data-page="teachers">
                    <i class="fas fa-chalkboard-teacher"></i> <span>Teachers</span>
                </a>
                <a href="Courses.php" class="nav-item" data-page="courses">
                    <i class="fas fa-graduation-cap"></i> <span>Department</span>
                </a>
                <a href="schedule.php" class="nav-item" data-page="schedule">
                    <i class="fas fa-calendar-alt"></i> <span>Schedule class</span>
                </a>
                
                <!-- Report Dropdown -->
                <div class="dropdown-container">
                    <div class="nav-item dropdown-btn" onclick="toggleReportDropdown()">
                        <div>
                            <i class="fas fa-file-pdf"></i> 
                            <span>Report</span>
                        </div>
                        <i class="fas fa-chevron-down dropdown-icon" id="reportDropdownIcon"></i>
                    </div>
                    <div class="dropdown-menus" id="reportDropdownMenu">
                        <a href="report_teacher.php" class="nav-item sub-menu active">
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
                <a href="StudentAttendance.php" class="nav-item" data-page="attendance">
                    <i class="fas fa-calendar-check"></i> <span>Attendance</span>
                </a>
                
                <div class="nav-bottom">
                    <a href="logout.php" class="nav-item" style="padding-left:8px;">
                        <i class="fas fa-sign-out-alt"></i> <span>Logout</span>
                    </a>
                </div>
            </div>
        </div>
        
        <div class="main-content">
            <div class="top-bar">
                <h4><i class="fas fa-chalkboard-teacher"></i> Teacher Report</h4>
                <small>Total Teachers: <?php echo $total_teachers; ?></small>
            </div>
            
            <!-- KPI Summary Cards -->
            <div class="kpi-row">
                <div class="kpi-card">
                    <div class="kpi-title">Total Teachers</div>
                    <div class="kpi-number"><?php echo $total_teachers; ?></div>
                    <small class="filter-badge">
                        <?php if(!empty($search) || !empty($gender) || !empty($subject)): ?>
                            <i class="fas fa-filter"></i> Filtered
                        <?php endif; ?>
                    </small>
                </div>
                <div class="kpi-card">
                    <div class="kpi-title">Male Teachers</div>
                    <div class="kpi-number"><?php echo $male_count; ?></div>
                    <small><?php echo $total_teachers > 0 ? round(($male_count/$total_teachers)*100, 1) : 0; ?>% of total</small>
                </div>
                <div class="kpi-card">
                    <div class="kpi-title">Female Teachers</div>
                    <div class="kpi-number"><?php echo $female_count; ?></div>
                    <small><?php echo $total_teachers > 0 ? round(($female_count/$total_teachers)*100, 1) : 0; ?>% of total</small>
                </div>
            </div>
            
            <!-- KPI Summary Cards Row 2 -->
            <div class="kpi-row">
                <div class="kpi-card">
                    <div class="kpi-title">Total Courses</div>
                    <div class="kpi-number"><?php echo $total_courses_taught; ?></div>
                    <small>Courses being taught</small>
                </div>
                <div class="kpi-card">
                    <div class="kpi-title">Total Subjects</div>
                    <div class="kpi-number"><?php echo $total_subjects; ?></div>
                    <?php if($filtered_subjects != $total_subjects && $filtered_subjects > 0): ?>
                        <small class="text-muted">Filtered: <?php echo $filtered_subjects; ?></small>
                    <?php else: ?>
                        <small>Unique subjects offered</small>
                    <?php endif; ?>
                </div>
                <div class="kpi-card">
                    <div class="kpi-title"><i class="fas fa-chart-line"></i> Teacher Status</div>
                    <div class="student-stat-item">
                        <span class="student-stat-label"><i class="fas fa-check-circle text-success"></i> Active</span>
                        <span class="student-stat-value"><?php echo $active_count; ?></span>
                    </div>
                    <div class="student-stat-item">
                        <span class="student-stat-label"><i class="fas fa-ban text-secondary"></i> Inactive</span>
                        <span class="student-stat-value"><?php echo $inactive_count; ?></span>
                    </div>
                    <div class="student-stat-item">
                        <span class="student-stat-label"><i class="fas fa-clock text-danger"></i> On Leave</span>
                        <span class="student-stat-value"><?php echo $on_leave_count; ?></span>
                    </div>
                </div>
            </div>
            
            <!-- Filter Section -->
            <div class="filter-section">
                <form method="GET" action="" class="row g-3" id="filterForm">
                    <div class="col-md-4">
                        <label class="form-label"><i class="fas fa-search"></i> Search</label>
                        <input type="text" name="search" class="form-control" placeholder="Name, Email, Phone..." value="<?php echo htmlspecialchars($search); ?>">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label"><i class="fas fa-venus-mars"></i> Gender</label>
                        <select name="gender" class="form-select">
                            <option value="">All</option>
                            <option value="Male" <?php echo $gender == 'Male' ? 'selected' : ''; ?>>Male</option>
                            <option value="Female" <?php echo $gender == 'Female' ? 'selected' : ''; ?>>Female</option>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label"><i class="fas fa-book"></i> Subject</label>
                        <select name="subject" class="form-select">
                            <option value="">All Subjects</option>
                            <?php while($sub = mysqli_fetch_assoc($subjects_list)): ?>
                                <option value="<?php echo htmlspecialchars($sub['subject']); ?>" <?php echo $subject == $sub['subject'] ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($sub['subject']); ?>
                                </option>
                            <?php endwhile; ?>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <label class="form-label">&nbsp;</label>
                        <button type="submit" class="btn btn-primary form-control">
                            <i class="fas fa-search"></i> Filter
                        </button>
                    </div>
                    <div class="col-md-12 mt-2">
                        <a href="report_teacher.php" class="btn btn-secondary">
                            <i class="fas fa-sync"></i> Reset
                        </a>
                        <button type="button" class="btn btn-excel" onclick="exportToExcel()">
                            <i class="fas fa-file-excel"></i> Excel
                        </button>
                        <button type="button" class="btn btn-pdf" onclick="window.print()">
                            <i class="fas fa-file-pdf"></i> PDF
                        </button>
                        <button type="button" class="btn btn-print" onclick="window.print()">
                            <i class="fas fa-print"></i> Print
                        </button>
                    </div>
                </form>
            </div>
            
            <!-- Report Table with Actions -->
            <div class="report-container">
                <h5><i class="fas fa-table"></i> Teachers Details</h5>
                <hr>
                <div class="table-responsive">
                    <table class="table table-bordered table-striped table-hover" id="reportTable">
                        <thead class="table-light">
                            <tr>
                                <th>ID</th>
                                <th>Image</th>
                                <th>Name</th>
                                <th>Gender</th>
                                <th>Email</th>
                                <th>Phone</th>
                                <th>Subject</th>
                                <th>Classes</th>
                                <th>Status</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if($result && mysqli_num_rows($result) > 0): ?>
                                <?php while($row = mysqli_fetch_assoc($result)): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($row['id']); ?></td>
                                        <td>
                                            <?php if(!empty($row['image'])): ?>
                                                <img src="<?php echo htmlspecialchars($row['image']); ?>" class="teacher-img" onerror="this.src='https://ui-avatars.com/api/?name=<?php echo urlencode($row['name']); ?>&background=667eea&color=fff'">
                                            <?php else: ?>
                                                <img src="https://ui-avatars.com/api/?name=<?php echo urlencode($row['name']); ?>&background=667eea&color=fff" class="teacher-img">
                                            <?php endif; ?>
                                        </td>
                                        <td><strong><?php echo htmlspecialchars($row['name']); ?></strong></td>
                                        <td>
                                            <?php if($row['gender'] == 'Male'): ?>
                                                <i class="fas fa-mars text-primary"></i>
                                            <?php elseif($row['gender'] == 'Female'): ?>
                                                <i class="fas fa-venus text-danger"></i>
                                            <?php endif; ?>
                                            <?php echo htmlspecialchars($row['gender']); ?>
                                        </td>
                                        <td><?php echo htmlspecialchars($row['email']); ?></td>
                                        <td><?php echo htmlspecialchars($row['phone']); ?></td>
                                        <td><span class="badge bg-primary"><?php echo htmlspecialchars($row['subject']); ?></span></td>
                                        <td>
                                            <span class="badge bg-success"><?php echo $row['total_courses']; ?> Courses</span>
                                            <br>
                                            <small class="text-muted">Sessions: <?php echo $row['total_teaching_sessions'] ?? 0; ?></small>
                                            <br>
                                            <small class="text-success">✓ <?php echo $row['completed_sessions'] ?? 0; ?></small>
                                            <small class="text-warning">⏳ <?php echo $row['pending_sessions'] ?? 0; ?></small>
                                        </td>
                                        <td>
                                            <?php 
                                            $status_field = isset($row['Status']) ? $row['Status'] : (isset($row['status']) ? $row['status'] : '');
                                            $status = strtolower(trim($status_field));
                                            $badgeClass = '';
                                            $icon = '';
                                            
                                            if($status == 'active') {
                                                $badgeClass = 'badge bg-success';
                                                $icon = '<i class="fas fa-check-circle"></i> ';
                                            } 
                                            elseif($status == 'inactive') {
                                                $badgeClass = 'badge bg-secondary';
                                                $icon = '<i class="fas fa-ban"></i> ';
                                            } 
                                            elseif($status == 'on leave') {
                                                $badgeClass = 'badge bg-danger';
                                                $icon = '<i class="fas fa-clock"></i> ';
                                            } 
                                            else {
                                                $badgeClass = 'badge bg-warning';
                                                $icon = '<i class="fas fa-question-circle"></i> ';
                                                $status = $status_field ?: 'Unknown';
                                            }
                                            ?>
                                            <span class="<?php echo $badgeClass; ?>"><?php echo $icon . ucfirst($status); ?></span>
                                        </td>
                                        <td>
                                            <!-- View Activity Button -->
                                            <button type="button" class="action-btn btn-view-activity" 
                                                    onclick="viewTeacherActivity(<?php echo $row['id']; ?>, '<?php echo htmlspecialchars($row['name']); ?>')">
                                                <i class="fas fa-history"></i> Activity
                                            </button>
                                            
                                            <!-- View Schedule Button -->
                                            <button type="button" class="action-btn btn-view-schedule" 
                                                    onclick="viewTeacherSchedule(<?php echo $row['id']; ?>, '<?php echo htmlspecialchars($row['name']); ?>')">
                                                <i class="fas fa-calendar-alt"></i> Schedule
                                            </button>
                                            
                                            <!-- View Classes Button -->
                                            <button type="button" class="action-btn btn-view-classes" 
                                                    onclick="viewTeacherClasses(<?php echo $row['id']; ?>, '<?php echo htmlspecialchars($row['name']); ?>')">
                                                <i class="fas fa-chalkboard"></i> Classes
                                            </button>
                                        </td>
                                    </tr>
                                <?php endwhile; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="10" class="text-center">
                                        <i class="fas fa-info-circle"></i> No teachers found
                                    </td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
    
    <!-- ============================================ -->
    <!-- ACTIVITY LOG MODAL                           -->
    <!-- ============================================ -->
    <div class="modal fade" id="activityModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header" style="background: #4f46e5; color: white;">
                    <h5 class="modal-title"><i class="fas fa-history"></i> Teacher Activity Log</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div id="activityContent">
                        <div class="text-center py-4">
                            <div class="spinner-border text-primary" role="status">
                                <span class="visually-hidden">Loading...</span>
                            </div>
                            <p class="mt-2">Loading activity data...</p>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                </div>
            </div>
        </div>
    </div>
    
    <!-- ============================================ -->
    <!-- SCHEDULE MODAL                               -->
    <!-- ============================================ -->
    <div class="modal fade" id="scheduleModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header" style="background: #0ea5e9; color: white;">
                    <h5 class="modal-title"><i class="fas fa-calendar-alt"></i> Teacher Schedule</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body" id="scheduleContent">
                    <div class="text-center py-4">
                        <div class="spinner-border text-primary" role="status">
                            <span class="visually-hidden">Loading...</span>
                        </div>
                        <p class="mt-2">Loading schedule data...</p>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                </div>
            </div>
        </div>
    </div>
    
    <!-- ============================================ -->
    <!-- CLASSES MODAL                                -->
    <!-- ============================================ -->
    <div class="modal fade" id="classesModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header" style="background: #10b981; color: white;">
                    <h5 class="modal-title"><i class="fas fa-chalkboard"></i> Teacher Classes</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body" id="classesContent">
                    <div class="text-center py-4">
                        <div class="spinner-border text-primary" role="status">
                            <span class="visually-hidden">Loading...</span>
                        </div>
                        <p class="mt-2">Loading class data...</p>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                </div>
            </div>
        </div>
    </div>
    
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.4/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.4/js/dataTables.bootstrap5.min.js"></script>
    <script>
        // Toggle Student Dropdown
        function toggleDropdown() {
            let menu = document.getElementById("studentDropdown");
            let icon = document.getElementById("dropdownIcon");
            if(menu.style.display === "block") {
                menu.style.display = "none";
                icon.style.transform = "rotate(0deg)";
            } else {
                let reportMenu = document.getElementById("reportDropdownMenu");
                let reportIcon = document.getElementById("reportDropdownIcon");
                if(reportMenu) {
                    reportMenu.style.display = "none";
                    if(reportIcon) reportIcon.style.transform = "rotate(0deg)";
                }
                menu.style.display = "block";
                icon.style.transform = "rotate(180deg)";
            }
        }
        
        // Toggle Report Dropdown
        function toggleReportDropdown() {
            let menu = document.getElementById("reportDropdownMenu");
            let icon = document.getElementById("reportDropdownIcon");
            
            if(menu.style.display === "block") {
                menu.style.display = "none";
                icon.style.transform = "rotate(0deg)";
            } else {
                let studentMenu = document.getElementById("studentDropdown");
                let studentIcon = document.getElementById("dropdownIcon");
                if(studentMenu) {
                    studentMenu.style.display = "none";
                    if(studentIcon) studentIcon.style.transform = "rotate(0deg)";
                }
                menu.style.display = "block";
                icon.style.transform = "rotate(180deg)";
            }
        }
        
        // Close dropdowns when clicking outside
        document.addEventListener('click', function(event) {
            const studentContainer = document.querySelector('.dropdown-container:first-child');
            if(studentContainer && !studentContainer.contains(event.target)) {
                const studentMenu = document.getElementById("studentDropdown");
                const studentIcon = document.getElementById("dropdownIcon");
                if(studentMenu && studentMenu.style.display === "block") {
                    studentMenu.style.display = "none";
                    if(studentIcon) studentIcon.style.transform = "rotate(0deg)";
                }
            }
            
            const reportContainers = document.querySelectorAll('.dropdown-container');
            if(reportContainers.length > 1) {
                const reportContainer = reportContainers[1];
                if(reportContainer && !reportContainer.contains(event.target)) {
                    const reportMenu = document.getElementById("reportDropdownMenu");
                    const reportIcon = document.getElementById("reportDropdownIcon");
                    if(reportMenu && reportMenu.style.display === "block") {
                        reportMenu.style.display = "none";
                        if(reportIcon) reportIcon.style.transform = "rotate(0deg)";
                    }
                }
            }
        });
        
        // View Teacher Activity
        function viewTeacherActivity(teacherId, teacherName) {
            const modal = new bootstrap.Modal(document.getElementById('activityModal'));
            const content = document.getElementById('activityContent');
            
            // Show loading
            content.innerHTML = `
                <div class="text-center py-4">
                    <div class="spinner-border text-primary" role="status">
                        <span class="visually-hidden">Loading...</span>
                    </div>
                    <p class="mt-2">Loading activity for ${teacherName}...</p>
                </div>
            `;
            
            modal.show();
            
            // Fetch activity data via AJAX
            $.ajax({
                url: 'get_teacher_activity.php',
                type: 'GET',
                data: { teacher_id: teacherId },
                dataType: 'json',
                success: function(data) {
                    if(data.success && data.activities.length > 0) {
                        let html = `
                            <div class="mb-3">
                                <h6><i class="fas fa-user"></i> Teacher: <strong>${teacherName}</strong></h6>
                                <p><i class="fas fa-chart-simple"></i> Total Sessions: ${data.total_sessions}</p>
                            </div>
                            <div class="activity-timeline">
                        `;
                        
                        data.activities.forEach(function(activity) {
                            const statusClass = activity.status === 'completed' ? 'completed' : 'pending';
                            const statusIcon = activity.status === 'completed' ? '✅' : '⏳';
                            const statusText = activity.status === 'completed' ? 'Completed' : 'Pending';
                            
                            html += `
                                <div class="activity-item ${statusClass}">
                                    <div class="d-flex justify-content-between align-items-start">
                                        <div>
                                            <strong>${activity.class_name}</strong>
                                            <br>
                                            <small class="text-muted">
                                                <i class="fas fa-book"></i> ${activity.subject}
                                            </small>
                                            <br>
                                            <small class="text-muted">
                                                <i class="fas fa-calendar-day"></i> ${activity.teaching_date}
                                            </small>
                                            ${activity.notes ? `<br><small class="text-muted"><i class="fas fa-comment"></i> ${activity.notes}</small>` : ''}
                                        </div>
                                        <span class="activity-status badge ${activity.status === 'completed' ? 'bg-success' : 'bg-warning'}">
                                            ${statusIcon} ${statusText}
                                        </span>
                                    </div>
                                    <div class="activity-time">
                                        <i class="fas fa-clock"></i> ${activity.updated_at}
                                    </div>
                                </div>
                            `;
                        });
                        
                        html += `</div>`;
                        content.innerHTML = html;
                    } else {
                        content.innerHTML = `
                            <div class="text-center py-4">
                                <i class="fas fa-inbox" style="font-size: 48px; color: #d1d5db;"></i>
                                <p class="mt-2">No teaching activities found for ${teacherName}</p>
                                <small class="text-muted">The teacher hasn't logged any teaching sessions yet.</small>
                            </div>
                        `;
                    }
                },
                error: function() {
                    content.innerHTML = `
                        <div class="text-center py-4 text-danger">
                            <i class="fas fa-exclamation-circle" style="font-size: 48px;"></i>
                            <p class="mt-2">Failed to load activity data. Please try again.</p>
                        </div>
                    `;
                }
            });
        }
        
        // View Teacher Schedule
       // View Teacher Schedule
function viewTeacherSchedule(teacherId, teacherName) {
    const modal = new bootstrap.Modal(document.getElementById('scheduleModal'));
    const content = document.getElementById('scheduleContent');
    
    content.innerHTML = `
        <div class="text-center py-4">
            <div class="spinner-border text-primary" role="status">
                <span class="visually-hidden">Loading...</span>
            </div>
            <p class="mt-2">Loading schedule for ${teacherName}...</p>
        </div>
    `;
    
    modal.show();
    
    $.ajax({
        url: 'get_teacher_schedule.php',
        type: 'GET',
        data: { teacher_id: teacherId },
        dataType: 'json',
        success: function(data) {
            if(data.success && data.schedule.length > 0) {
                let html = `
                    <h6><i class="fas fa-user"></i> Teacher: <strong>${teacherName}</strong></h6>
                    <p><i class="fas fa-calendar-alt"></i> Total Classes: ${data.schedule.length}</p>
                    <div class="table-responsive mt-3">
                        <table class="table table-bordered table-striped">
                            <thead>
                                <tr>
                                    <th>#</th>
                                    <th>Date</th>
                                    <th>Day</th>
                                    <th>Subject</th>
                                    <th>Class</th>
                                    <th>Start Time</th>
                                    <th>End Time</th>
                                    <th>Department</th>
                                    <th>Shift</th>
                                </tr>
                            </thead>
                            <tbody>
                `;
                
                let count = 1;
                data.schedule.forEach(function(item) {
                    html += `
                        <tr>
                            <td>${count++}</td>
                            <td><span class="badge bg-primary">${item.date_formatted || item.date}</span></td>
                            <td>${item.day_of_week || '-'}</td>
                            <td><strong>${item.subject}</strong></td>
                            <td>${item.class || '-'}</td>
                            <td>${item.time_star || '-'}</td>
                            <td>${item.time_end || '-'}</td>
                            <td>${item.department || '-'}</td>
                            <td>${item.shift || '-'}</td>
                        </tr>
                    `;
                });
                
                html += `
                            </tbody>
                        </table>
                    </div>
                `;
                content.innerHTML = html;
            } else {
                content.innerHTML = `
                    <div class="text-center py-4">
                        <i class="fas fa-calendar-times" style="font-size: 48px; color: #d1d5db;"></i>
                        <p class="mt-2">No schedule found for ${teacherName}</p>
                        <small class="text-muted">This teacher hasn't been assigned to any classes yet.</small>
                    </div>
                `;
            }
        },
        error: function() {
            content.innerHTML = `
                <div class="text-center py-4 text-danger">
                    <i class="fas fa-exclamation-circle" style="font-size: 48px;"></i>
                    <p class="mt-2">Failed to load schedule data. Please try again.</p>
                </div>
            `;
        }
    });
}
        
        // View Teacher Classes
        function viewTeacherClasses(teacherId, teacherName) {
            const modal = new bootstrap.Modal(document.getElementById('classesModal'));
            const content = document.getElementById('classesContent');
            
            content.innerHTML = `
                <div class="text-center py-4">
                    <div class="spinner-border text-primary" role="status">
                        <span class="visually-hidden">Loading...</span>
                    </div>
                    <p class="mt-2">Loading classes for ${teacherName}...</p>
                </div>
            `;
            
            modal.show();
            
            $.ajax({
                url: 'get_teacher_classes.php',
                type: 'GET',
                data: { teacher_id: teacherId },
                dataType: 'json',
                success: function(data) {
                    if(data.success && data.classes.length > 0) {
                        let html = `
                            <h6><i class="fas fa-user"></i> Teacher: <strong>${teacherName}</strong></h6>
                            <div class="table-responsive mt-3">
                                <table class="table table-bordered table-striped">
                                    <thead>
                                        <tr>
                                            <th>Class Name</th>
                                            <th>Subject</th>
                                            <th>Room</th>
                                            <th>Status</th>
                                            <th>Schedule Day</th>
                                            <th>Time</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                        `;
                        
                        data.classes.forEach(function(cls) {
                            const statusBadge = cls.status === 'active' ? 'success' : 'secondary';
                            html += `
                                <tr>
                                    <td><strong>${cls.class_name}</strong></td>
                                    <td>${cls.subject}</td>
                                    <td>${cls.room || '-'}</td>
                                    <td><span class="badge bg-${statusBadge}">${cls.status}</span></td>
                                    <td>${cls.schedule_day || '-'}</td>
                                    <td>${cls.start_time || '-'} - ${cls.end_time || '-'}</td>
                                </tr>
                            `;
                        });
                        
                        html += `
                                    </tbody>
                                </table>
                            </div>
                        `;
                        content.innerHTML = html;
                    } else {
                        content.innerHTML = `
                            <div class="text-center py-4">
                                <i class="fas fa-chalkboard" style="font-size: 48px; color: #d1d5db;"></i>
                                <p class="mt-2">No classes found for ${teacherName}</p>
                                <small class="text-muted">This teacher hasn't been assigned to any classes yet.</small>
                            </div>
                        `;
                    }
                },
                error: function() {
                    content.innerHTML = `
                        <div class="text-center py-4 text-danger">
                            <i class="fas fa-exclamation-circle" style="font-size: 48px;"></i>
                            <p class="mt-2">Failed to load class data. Please try again.</p>
                        </div>
                    `;
                }
            });
        }
        
        // Export to Excel
        function exportToExcel() {
            var table = document.getElementById("reportTable");
            var html = table.outerHTML;
            var url = 'data:application/vnd.ms-excel,' + encodeURIComponent(html);
            var link = document.createElement('a');
            link.download = 'teacher_report_' + new Date().toISOString().slice(0,10) + '.xls';
            link.href = url;
            link.click();
        }
        
        // Initialize DataTable
        $(document).ready(function() {
            $('#reportTable').DataTable({
                pageLength: 10,
                order: [[0, 'desc']],
                language: { 
                    search: "<i class='fas fa-search'></i> Search:",
                    searchPlaceholder: "Type to filter...",
                    lengthMenu: "Show _MENU_ entries",
                    info: "Showing _START_ to _END_ of _TOTAL_ entries",
                    paginate: {
                        first: "First",
                        last: "Last",
                        next: "Next",
                        previous: "Previous"
                    }
                },
                dom: 'Bfrtip',
                responsive: true
            });
        });
    </script>
</body>
</html>