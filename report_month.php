<?php
include 'db.php';
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Get filters
$month = isset($_GET['month']) ? mysqli_real_escape_string($conn, $_GET['month']) : date('Y-m');
$year = isset($_GET['year']) ? mysqli_real_escape_string($conn, $_GET['year']) : date('Y');
$department = isset($_GET['department']) ? mysqli_real_escape_string($conn, $_GET['department']) : '';

// Check if attendance table exists
$att_table = null;
$possible_tables = ['attendance', 'attendantcivil', 'attendantelectrical', 'attendantelectronic', 'attendantit', 'attendantbusiness'];

foreach ($possible_tables as $tbl) {
    $check = mysqli_query($conn, "SHOW TABLES LIKE '$tbl'");
    if ($check && mysqli_num_rows($check) > 0) {
        $att_table = $tbl;
        break;
    }
}

$days_data = [];
$monthly_data = [
    'total_students' => 0,
    'total_records' => 0,
    'total_present' => 0,
    'total_absent' => 0,
    'total_late' => 0
];

if ($att_table) {
    // Monthly summary
    $monthly_sql = "SELECT 
                        COUNT(DISTINCT student_id) as total_students,
                        COUNT(*) as total_records,
                        SUM(CASE WHEN status = 'Present' THEN 1 ELSE 0 END) as total_present,
                        SUM(CASE WHEN status = 'Absent' THEN 1 ELSE 0 END) as total_absent,
                        SUM(CASE WHEN status = 'Late' THEN 1 ELSE 0 END) as total_late
                    FROM $att_table 
                    WHERE DATE_FORMAT(date, '%Y-%m') = '$month'";
    
    if (!empty($department)) {
        $monthly_sql .= " AND department = '$department'";
    }
    
    $monthly_result = mysqli_query($conn, $monthly_sql);
    if ($monthly_result && mysqli_num_rows($monthly_result) > 0) {
        $monthly_data = mysqli_fetch_assoc($monthly_result);
    }
    
    // Daily data
    $daily_sql = "SELECT 
                    DATE_FORMAT(date, '%d') as day,
                    DATE_FORMAT(date, '%Y-%m-%d') as full_date,
                    COUNT(DISTINCT student_id) as total_students,
                    SUM(CASE WHEN status = 'Present' THEN 1 ELSE 0 END) as present_count,
                    SUM(CASE WHEN status = 'Absent' THEN 1 ELSE 0 END) as absent_count,
                    SUM(CASE WHEN status = 'Late' THEN 1 ELSE 0 END) as late_count
                FROM $att_table 
                WHERE DATE_FORMAT(date, '%Y-%m') = '$month'";
    
    if (!empty($department)) {
        $daily_sql .= " AND department = '$department'";
    }
    
    $daily_sql .= " GROUP BY DATE(date) ORDER BY date ASC";
    
    $daily_result = mysqli_query($conn, $daily_sql);
    if ($daily_result && mysqli_num_rows($daily_result) > 0) {
        while($row = mysqli_fetch_assoc($daily_result)) {
            $total = $row['total_students'];
            $present = $row['present_count'];
            $attendance_rate = $total > 0 ? round(($present / $total) * 100, 1) : 0;
            $row['attendance_rate'] = $attendance_rate;
            $days_data[] = $row;
        }
    }
}

// Top students
$top_students = [];
if ($att_table) {
    $top_sql = "SELECT 
                    a.student_id,
                    s.name as student_name,
                    s.class,
                    COUNT(a.id) as total_days,
                    SUM(CASE WHEN a.status = 'Present' THEN 1 ELSE 0 END) as present_days
                FROM $att_table a
                JOIN students s ON a.student_id = s.id
                WHERE DATE_FORMAT(a.date, '%Y-%m') = '$month'";
    
    if (!empty($department)) {
        $top_sql .= " AND a.department = '$department'";
    }
    
    $top_sql .= " GROUP BY a.student_id
                  HAVING total_days > 0
                  ORDER BY (present_days / total_days) DESC
                  LIMIT 10";
    
    $top_result = mysqli_query($conn, $top_sql);
    if ($top_result && mysqli_num_rows($top_result) > 0) {
        while($row = mysqli_fetch_assoc($top_result)) {
            $row['attendance_rate'] = ($row['present_days'] / $row['total_days']) * 100;
            $top_students[] = $row;
        }
    }
}

// Monthly payments
$payment_data = ['total_amount' => 0, 'payment_count' => 0, 'students_paid' => 0];
$payments_check = mysqli_query($conn, "SHOW TABLES LIKE 'student_payments'");
if ($payments_check && mysqli_num_rows($payments_check) > 0) {
    $payment_sql = "SELECT 
                        IFNULL(SUM(amount), 0) as total_amount,
                        COUNT(*) as payment_count,
                        COUNT(DISTINCT student_id) as students_paid
                    FROM student_payments
                    WHERE DATE_FORMAT(payment_date, '%Y-%m') = '$month'";
    
    $payment_result = mysqli_query($conn, $payment_sql);
    if ($payment_result && mysqli_num_rows($payment_result) > 0) {
        $payment_data = mysqli_fetch_assoc($payment_result);
    }
}

// Get departments for filter
$departments_list = mysqli_query($conn, "SELECT DISTINCT name FROM courses ORDER BY name");
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>KRaksa - Monthly Report</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
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
        .top-bar { background: white; border-radius: 50px; padding: 12px 25px; margin-bottom: 25px; display: flex; justify-content: space-between; align-items: center; }
        .kpi-row { display: flex; gap: 20px; margin-bottom: 25px; flex-wrap: wrap; }
        .kpi-card { background: white; border-radius: 15px; padding: 20px; flex: 1; min-width: 180px; box-shadow: 0 2px 10px rgba(0,0,0,0.05); border-left: 4px solid #667eea; }
        .kpi-card:nth-child(2) { border-left-color: #28a745; }
        .kpi-card:nth-child(3) { border-left-color: #ffc107; }
        .kpi-card:nth-child(4) { border-left-color: #17a2b8; }
        .kpi-card:nth-child(5) { border-left-color: #dc3545; }
        .kpi-number { font-size: 28px; font-weight: bold; color: #2c3e50; }
        .kpi-title { color: #6c757d; font-size: 14px; }
        .filter-section { background: white; border-radius: 15px; padding: 20px; margin-bottom: 25px; }
        .report-container { background: white; border-radius: 15px; padding: 20px; margin-bottom: 25px; }
        .chart-container { background: white; border-radius: 15px; padding: 20px; margin-bottom: 25px; }
        .btn-print { background: #17a2b8; color: white; border: none; padding: 8px 20px; border-radius: 10px; margin-left: 10px; }
        .btn-excel { background: #28a745; color: white; border: none; padding: 8px 20px; border-radius: 10px; }
        .progress { height: 20px; border-radius: 10px; }
        .status-badge { padding: 3px 10px; border-radius: 20px; font-size: 12px; font-weight: 600; display: inline-block; }
        .bg-present { background: #d4edda; color: #155724; }
        .bg-late { background: #fff3cd; color: #856404; }
        .bg-absent { background: #f8d7da; color: #721c24; }
        .table-responsive { overflow-x: auto; }
        .table th { background: #f8f9fa; white-space: nowrap; }
        .table td { vertical-align: middle; }
        .search-box { margin-bottom: 15px; display: flex; gap: 10px; }
        .search-box input { border-radius: 20px; padding: 5px 15px; border: 1px solid #ddd; width: 250px; }
        @media (max-width: 768px) { .sidebar { width: 80px; } .sidebar-header h1, .sidebar-header p, .nav-item span { display: none; } .main-content { padding: 15px; } }
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
                <a href="index.php" class="nav-item"><i class="fas fa-tachometer-alt"></i> <span>Dashboard</span></a>
                
                <div class="dropdown-container">
                    <div class="nav-item dropdown-btn" onclick="toggleStudentDropdown()">
                        <div><i class="fas fa-user-graduate"></i><span>Students</span></div>
                        <i class="fas fa-chevron-down dropdown-icon" id="studentDropdownIcon"></i>
                    </div>
                    <div class="dropdown-menus" id="studentDropdownMenu">
                        <a href="student.php" class="nav-item sub-menu"><i class="fas fa-users"></i><span>Student List</span></a>
                        <a href="stutype.php" class="nav-item sub-menu"><i class="fas fa-tags"></i><span>Student Type</span></a>
                        <a href="student_payments.php" class="nav-item sub-menu"><i class="fas fa-money-bill-wave"></i><span>Payments</span></a>
                    </div>
                </div>
                
                <a href="teacher.php" class="nav-item"><i class="fas fa-chalkboard-teacher"></i> <span>Teachers</span></a>
                <a href="Courses.php" class="nav-item"><i class="fas fa-graduation-cap"></i> <span>Courses</span></a>
                <a href="schedule.php" class="nav-item"><i class="fas fa-calendar-alt"></i> <span>Schedule</span></a>
                <a href="StudentAttendance.php" class="nav-item"><i class="fas fa-calendar-check"></i> <span>Attendance</span></a>
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
                    <a href="report_month.php" class="nav-item sub-menu active"><i class="fas fa-chart-line"></i><span>Monthly Report</span></a>
                </div>
            </div>                
                <a href="Employees.php" class="nav-item"><i class="fas fa-user-friends"></i> <span>Employees</span></a>
                <div class="nav-bottom">
                    <a href="logout.php" class="nav-item" style="padding-left:8px;"><i class="fas fa-sign-out-alt"></i> <span>Logout</span></a>
                </div>
            </div>
        </div>
        
        <div class="main-content">
            <div class="top-bar">
                <h4><i class="fas fa-calendar-month"></i> Monthly Report - <?php echo date('F Y', strtotime($month . '-01')); ?></h4>
                <small>Attendance & Financial Summary</small>
            </div>
            
            <!-- KPI Cards -->
            <div class="kpi-row">
                <div class="kpi-card">
                    <div class="kpi-title"><i class="fas fa-users"></i> Total Students</div>
                    <div class="kpi-number"><?php echo number_format($monthly_data['total_students'] ?? 0); ?></div>
                </div>
                <div class="kpi-card">
                    <div class="kpi-title"><i class="fas fa-calendar-check"></i> Total Records</div>
                    <div class="kpi-number"><?php echo number_format($monthly_data['total_records'] ?? 0); ?></div>
                </div>
                <div class="kpi-card">
                    <div class="kpi-title"><i class="fas fa-check-circle"></i> Present</div>
                    <div class="kpi-number text-success"><?php echo number_format($monthly_data['total_present'] ?? 0); ?></div>
                </div>
                <div class="kpi-card">
                    <div class="kpi-title"><i class="fas fa-clock"></i> Late</div>
                    <div class="kpi-number text-warning"><?php echo number_format($monthly_data['total_late'] ?? 0); ?></div>
                </div>
                <div class="kpi-card">
                    <div class="kpi-title"><i class="fas fa-times-circle"></i> Absent</div>
                    <div class="kpi-number text-danger"><?php echo number_format($monthly_data['total_absent'] ?? 0); ?></div>
                </div>
            </div>
            
            <!-- Payment Summary Row -->
            <div class="kpi-row">
                <div class="kpi-card">
                    <div class="kpi-title"><i class="fas fa-dollar-sign"></i> Total Payments</div>
                    <div class="kpi-number">$<?php echo number_format($payment_data['total_amount'] ?? 0, 2); ?></div>
                </div>
                <div class="kpi-card">
                    <div class="kpi-title"><i class="fas fa-receipt"></i> Payment Count</div>
                    <div class="kpi-number"><?php echo number_format($payment_data['payment_count'] ?? 0); ?></div>
                </div>
                <div class="kpi-card">
                    <div class="kpi-title"><i class="fas fa-user-check"></i> Students Paid</div>
                    <div class="kpi-number"><?php echo number_format($payment_data['students_paid'] ?? 0); ?></div>
                </div>
                <div class="kpi-card">
                    <div class="kpi-title"><i class="fas fa-chart-line"></i> Attendance Rate</div>
                    <div class="kpi-number">
                        <?php 
                        $total_records = $monthly_data['total_records'] ?? 0;
                        $total_present = $monthly_data['total_present'] ?? 0;
                        $att_rate = $total_records > 0 ? round(($total_present / $total_records) * 100, 1) : 0;
                        echo $att_rate; ?>%
                    </div>
                </div>
            </div>
            
            <!-- Filter Section -->
            <div class="filter-section">
                <form method="GET" action="" class="row g-3">
                    <div class="col-md-3">
                        <label class="form-label">Year</label>
                        <select name="year" class="form-select">
                            <?php for($y = 2020; $y <= date('Y'); $y++): ?>
                                <option value="<?php echo $y; ?>" <?php echo $year == $y ? 'selected' : ''; ?>><?php echo $y; ?></option>
                            <?php endfor; ?>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Month</label>
                        <select name="month" class="form-select">
                            <?php for($m = 1; $m <= 12; $m++): ?>
                                <option value="<?php echo date('Y-m', strtotime("$year-$m-01")); ?>" <?php echo $month == date('Y-m', strtotime("$year-$m-01")) ? 'selected' : ''; ?>>
                                    <?php echo date('F', strtotime("$year-$m-01")); ?>
                                </option>
                            <?php endfor; ?>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Department</label>
                        <select name="department" class="form-select">
                            <option value="">All Departments</option>
                            <?php if($departments_list && mysqli_num_rows($departments_list) > 0): ?>
                                <?php while($dept = mysqli_fetch_assoc($departments_list)): ?>
                                    <option value="<?php echo htmlspecialchars($dept['name']); ?>" <?php echo $department == $dept['name'] ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($dept['name']); ?>
                                    </option>
                                <?php endwhile; ?>
                            <?php endif; ?>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">&nbsp;</label>
                        <button type="submit" class="btn btn-primary form-control"><i class="fas fa-search"></i> Generate</button>
                    </div>
                </form>
            </div>
            
            <!-- Chart Section -->
            <?php if(!empty($days_data)): ?>
            <div class="chart-container">
                <h5><i class="fas fa-chart-line"></i> Daily Attendance Trend</h5>
                <canvas id="attendanceChart" height="100"></canvas>
            </div>
            <?php endif; ?>
            
            <!-- Daily Attendance Table - SIMPLE HTML TABLE (No DataTables) -->
            <div class="report-container">
                <h5><i class="fas fa-table"></i> Daily Attendance Details</h5>
                <hr>
                
                <!-- Simple search box -->
                <div class="search-box">
                    <input type="text" id="searchInput" class="form-control" placeholder="🔍 Search...">
                    <button class="btn btn-secondary" onclick="clearSearch()">Clear</button>
                </div>
                
                <div class="table-responsive">
                    <table class="table table-bordered table-striped" id="dailyTable">
                        <thead>
                            <tr>
                                <th>#</th>
                                <th>Day</th>
                                <th>Date</th>
                                <th>Total Students</th>
                                <th>Present</th>
                                <th>Late</th>
                                <th>Absent</th>
                                <th>Attendance Rate</th>
                            </tr>
                        </thead>
                        <tbody id="tableBody">
                            <?php if(!empty($days_data)): ?>
                                <?php $counter = 1; foreach($days_data as $row): ?>
                                <tr>
                                    <td><?php echo $counter++; ?></td>
                                    <td>Day <?php echo $row['day']; ?></td>
                                    <td><?php echo date('d-m-Y', strtotime($row['full_date'])); ?></td>
                                    <td><span class="badge bg-secondary"><?php echo $row['total_students']; ?></span></td>
                                    <td><span class="status-badge bg-present"><i class="fas fa-check-circle"></i> <?php echo $row['present_count']; ?></span></td>
                                    <td><span class="status-badge bg-late"><i class="fas fa-clock"></i> <?php echo $row['late_count']; ?></span></td>
                                    <td><span class="status-badge bg-absent"><i class="fas fa-times-circle"></i> <?php echo $row['absent_count']; ?></span></td>
                                    <td>
                                        <div class="progress" style="height: 25px;">
                                            <div class="progress-bar bg-success" style="width: <?php echo $row['attendance_rate']; ?>%;">
                                                <?php echo $row['attendance_rate']; ?>%
                                            </div>
                                        </div>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="8" class="text-center">No attendance data for this month</td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
                
                <!-- Pagination -->
                <div class="mt-3 d-flex justify-content-between align-items-center">
                    <div>
                        Show 
                        <select id="pageSize" class="form-select d-inline-block w-auto">
                            <option value="10">10</option>
                            <option value="25">25</option>
                            <option value="50">50</option>
                            <option value="100">100</option>
                        </select>
                        entries
                    </div>
                    <div>
                        <button class="btn btn-sm btn-primary" id="prevBtn">Previous</button>
                        <span id="pageInfo" class="mx-2"></span>
                        <button class="btn btn-sm btn-primary" id="nextBtn">Next</button>
                    </div>
                </div>
            </div>
            
            <!-- Top Students -->
            <?php if(!empty($top_students)): ?>
            <div class="report-container">
                <h5><i class="fas fa-trophy"></i> Top 10 Students (Best Attendance)</h5>
                <hr>
                <div class="table-responsive">
                    <table class="table table-bordered">
                        <thead>
                            <tr>
                                <th>Rank</th>
                                <th>Student Name</th>
                                <th>Class</th>
                                <th>Present Days</th>
                                <th>Total Days</th>
                                <th>Attendance Rate</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php 
                            $rank = 1;
                            foreach($top_students as $student): 
                            ?>
                            <tr>
                                <td>
                                    <?php if($rank == 1): ?>
                                        <i class="fas fa-medal text-warning"></i> 1
                                    <?php elseif($rank == 2): ?>
                                        <i class="fas fa-medal text-secondary"></i> 2
                                    <?php elseif($rank == 3): ?>
                                        <i class="fas fa-medal" style="color:#cd7f32;"></i> 3
                                    <?php else: ?>
                                        <?php echo $rank; ?>
                                    <?php endif; ?>
                                </td>
                                <td><strong><?php echo htmlspecialchars($student['student_name']); ?></strong></td>
                                <td><span class="badge bg-info">Class <?php echo $student['class']; ?></span></td>
                                <td class="text-success"><?php echo $student['present_days']; ?></td>
                                <td><?php echo $student['total_days']; ?></td>
                                <td>
                                    <div class="progress" style="height: 25px;">
                                        <div class="progress-bar bg-success" style="width: <?php echo round($student['attendance_rate'], 1); ?>%;">
                                            <?php echo round($student['attendance_rate'], 1); ?>%
                                        </div>
                                    </div>
                                </td>
                            </tr>
                            <?php 
                            $rank++;
                            endforeach; 
                            ?>
                        </tbody>
                    </table>
                </div>
            </div>
            <?php endif; ?>
        </div>
    </div>
    
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function toggleStudentDropdown() {
            let menu = document.getElementById("studentDropdownMenu");
            let icon = document.getElementById("studentDropdownIcon");
            if(menu.style.display === "block") {
                menu.style.display = "none";
                icon.style.transform = "rotate(0deg)";
            } else {
                let reportMenu = document.getElementById("reportDropdownMenu");
                let reportIcon = document.getElementById("reportDropdownIcon");
                if(reportMenu) reportMenu.style.display = "none";
                if(reportIcon) reportIcon.style.transform = "rotate(0deg)";
                menu.style.display = "block";
                icon.style.transform = "rotate(180deg)";
            }
        }
        
        function toggleReportDropdown() {
            let menu = document.getElementById("reportDropdownMenu");
            let icon = document.getElementById("reportDropdownIcon");
            if(menu.style.display === "block") {
                menu.style.display = "none";
                icon.style.transform = "rotate(0deg)";
            } else {
                let studentMenu = document.getElementById("studentDropdownMenu");
                let studentIcon = document.getElementById("studentDropdownIcon");
                if(studentMenu) studentMenu.style.display = "none";
                if(studentIcon) studentIcon.style.transform = "rotate(0deg)";
                menu.style.display = "block";
                icon.style.transform = "rotate(180deg)";
            }
        }
        
        // Simple Pagination and Search
        let currentPage = 1;
        let pageSize = 10;
        let allRows = [];
        let filteredRows = [];
        
        function loadTableData() {
            const tbody = document.getElementById('tableBody');
            const start = (currentPage - 1) * pageSize;
            const end = start + pageSize;
            const pageRows = filteredRows.slice(start, end);
            
            tbody.innerHTML = '';
            if (pageRows.length === 0) {
                tbody.innerHTML = '<tr><td colspan="8" class="text-center">No records found</td></tr>';
            } else {
                pageRows.forEach(row => {
                    tbody.appendChild(row.cloneNode(true));
                });
            }
            
            const totalPages = Math.ceil(filteredRows.length / pageSize);
            document.getElementById('pageInfo').innerText = `Page ${currentPage} of ${totalPages || 1}`;
            document.getElementById('prevBtn').disabled = currentPage === 1;
            document.getElementById('nextBtn').disabled = currentPage === totalPages || totalPages === 0;
        }
        
        function filterTable() {
            const searchTerm = document.getElementById('searchInput').value.toLowerCase();
            filteredRows = allRows.filter(row => {
                const text = row.textContent.toLowerCase();
                return text.includes(searchTerm);
            });
            currentPage = 1;
            loadTableData();
        }
        
        function clearSearch() {
            document.getElementById('searchInput').value = '';
            filteredRows = [...allRows];
            currentPage = 1;
            loadTableData();
        }
        
        // Initialize
        document.addEventListener('DOMContentLoaded', function() {
            // Get all rows from table body
            const originalRows = document.querySelectorAll('#tableBody tr');
            originalRows.forEach(row => {
                if (row.cells.length > 0 && row.textContent.includes('No attendance')) {
                    return;
                }
                allRows.push(row.cloneNode(true));
            });
            filteredRows = [...allRows];
            
            const totalPages = Math.ceil(filteredRows.length / pageSize);
            document.getElementById('pageInfo').innerText = `Page 1 of ${totalPages || 1}`;
            document.getElementById('prevBtn').disabled = true;
            document.getElementById('nextBtn').disabled = filteredRows.length <= pageSize;
            
            document.getElementById('prevBtn').addEventListener('click', () => {
                if (currentPage > 1) {
                    currentPage--;
                    loadTableData();
                }
            });
            
            document.getElementById('nextBtn').addEventListener('click', () => {
                const totalPages = Math.ceil(filteredRows.length / pageSize);
                if (currentPage < totalPages) {
                    currentPage++;
                    loadTableData();
                }
            });
            
            document.getElementById('pageSize').addEventListener('change', (e) => {
                pageSize = parseInt(e.target.value);
                currentPage = 1;
                loadTableData();
            });
            
            document.getElementById('searchInput').addEventListener('keyup', filterTable);
        });
        
        // Chart.js for attendance trend
        <?php if(!empty($days_data)): ?>
        const days = <?php echo json_encode(array_column($days_data, 'day')); ?>;
        const present = <?php echo json_encode(array_column($days_data, 'present_count')); ?>;
        const late = <?php echo json_encode(array_column($days_data, 'late_count')); ?>;
        const absent = <?php echo json_encode(array_column($days_data, 'absent_count')); ?>;
        
        const ctx = document.getElementById('attendanceChart').getContext('2d');
        new Chart(ctx, {
            type: 'line',
            data: {
                labels: days,
                datasets: [
                    { 
                        label: 'Present', 
                        data: present, 
                        borderColor: '#28a745', 
                        backgroundColor: 'rgba(40,167,69,0.1)', 
                        fill: true, 
                        tension: 0.4,
                        pointBackgroundColor: '#28a745',
                        pointBorderColor: '#fff',
                        pointRadius: 5
                    },
                    { 
                        label: 'Late', 
                        data: late, 
                        borderColor: '#ffc107', 
                        backgroundColor: 'rgba(255,193,7,0.1)', 
                        fill: true, 
                        tension: 0.4,
                        pointBackgroundColor: '#ffc107',
                        pointBorderColor: '#fff',
                        pointRadius: 5
                    },
                    { 
                        label: 'Absent', 
                        data: absent, 
                        borderColor: '#dc3545', 
                        backgroundColor: 'rgba(220,53,69,0.1)', 
                        fill: true, 
                        tension: 0.4,
                        pointBackgroundColor: '#dc3545',
                        pointBorderColor: '#fff',
                        pointRadius: 5
                    }
                ]
            },
            options: { 
                responsive: true, 
                maintainAspectRatio: true,
                plugins: { 
                    legend: { position: 'top' }, 
                    title: { display: true, text: 'Daily Attendance Summary' } 
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        title: {
                            display: true,
                            text: 'Number of Students'
                        }
                    },
                    x: {
                        title: {
                            display: true,
                            text: 'Day of Month'
                        }
                    }
                }
            }
        });
        <?php endif; ?>
        
        // Export to Excel
        function exportToExcel() {
            const table = document.getElementById('dailyTable');
            const html = table.outerHTML;
            const url = 'data:application/vnd.ms-excel,' + encodeURIComponent(html);
            const link = document.createElement('a');
            link.download = 'monthly_report_' + new Date().toISOString().slice(0,10) + '.xls';
            link.href = url;
            link.click();
        }
    </script>
</body>
</html>