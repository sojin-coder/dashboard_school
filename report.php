<?php
include 'db.php';
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Get filter
$date_from = isset($_GET['date_from']) ? mysqli_real_escape_string($conn, $_GET['date_from']) : '';
$date_to = isset($_GET['date_to']) ? mysqli_real_escape_string($conn, $_GET['date_to']) : '';
$department = isset($_GET['department']) ? mysqli_real_escape_string($conn, $_GET['department']) : '';
$status_filter = isset($_GET['status']) ? mysqli_real_escape_string($conn, $_GET['status']) : '';

// Check which attendance tables actually exist
$tables_exist = [];
$available_tables = ['attendantcivil', 'attendantelectrical', 'attendantelectronic', 'attendantit', 'attendantbusiness', 'attendance'];

foreach ($available_tables as $table) {
    $check = mysqli_query($conn, "SHOW TABLES LIKE '$table'");
    if (mysqli_num_rows($check) > 0) {
        // Check if table has required columns
        $cols = mysqli_query($conn, "SHOW COLUMNS FROM $table");
        $has_id = false;
        $has_student_id = false;
        $has_date = false;
        $has_status = false;
        while ($col = mysqli_fetch_assoc($cols)) {
            if ($col['Field'] == 'id') $has_id = true;
            if ($col['Field'] == 'student_id') $has_student_id = true;
            if ($col['Field'] == 'date') $has_date = true;
            if ($col['Field'] == 'status') $has_status = true;
        }
        if ($has_id && $has_student_id && $has_date && $has_status) {
            $tables_exist[] = $table;
        }
    }
}

// Build UNION query only from existing tables
$union_parts = [];
foreach ($tables_exist as $table) {
    $dept_name = str_replace(['attendant', 'attendance'], '', $table);
    if ($dept_name == 'civil') $dept = 'Civil Engineering';
    elseif ($dept_name == 'electrical') $dept = 'Electrical Engineering';
    elseif ($dept_name == 'electronic') $dept = 'Electronics';
    elseif ($dept_name == 'it') $dept = 'Information Technology';
    elseif ($dept_name == 'business') $dept = 'Business';
    elseif ($dept_name == '') $dept = 'General';
    else $dept = $dept_name;
    
    $union_parts[] = "(SELECT '$dept' as department, id, student_id, date, status, check_in_time FROM $table)";
}

if (count($union_parts) > 0) {
    $sql = implode(" UNION ALL ", $union_parts);
    
    $sql = "SELECT a.*, 
            s.name as student_name, 
            s.gender, 
            s.phone, 
            s.class,
            c.name as course_name
            FROM ($sql) a
            LEFT JOIN students s ON a.student_id = s.id
            LEFT JOIN courses c ON c.name = a.department
            WHERE 1=1";
    
    if (!empty($date_from)) {
        $sql .= " AND a.date >= '$date_from'";
    }
    if (!empty($date_to)) {
        $sql .= " AND a.date <= '$date_to'";
    }
    if (!empty($department)) {
        $sql .= " AND a.department = '$department'";
    }
    if (!empty($status_filter)) {
        $sql .= " AND a.status = '$status_filter'";
    }
    
    $sql .= " ORDER BY a.date DESC";
    
    // Execute query with error checking
    $result = mysqli_query($conn, $sql);
    
    if (!$result) {
        die("Query failed: " . mysqli_error($conn) . "<br><br>SQL: " . htmlspecialchars($sql));
    }
} else {
    // No attendance tables found, create empty result
    $result = false;
    $error_msg = "No attendance tables found in database.";
}

// Get summary statistics
$total_records = 0;
$present_count = 0;
$absent_count = 0;
$late_count = 0;

if ($result && mysqli_num_rows($result) > 0) {
    $total_records = mysqli_num_rows($result);
    
    // Reset and calculate
    $temp_result = mysqli_query($conn, $sql);
    if ($temp_result) {
        while($row_temp = mysqli_fetch_assoc($temp_result)) {
            if(isset($row_temp['status'])) {
                if($row_temp['status'] == 'Present') $present_count++;
                elseif($row_temp['status'] == 'Absent') $absent_count++;
                elseif($row_temp['status'] == 'Late') $late_count++;
            }
        }
        mysqli_data_seek($result, 0);
    }
}

// Get list of departments from courses table for filter
$courses_list = mysqli_query($conn, "SELECT DISTINCT name FROM courses ORDER BY name");
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.4/css/dataTables.bootstrap5.min.css">
    <title>KRaksa - Attendance Report</title>
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
        .sub-menu { font-size: 14px; padding: 10px 15px; margin-bottom: 5px; background: rgba(88, 30, 248, 0.61); }
        .sub-menu:hover { background: rgba(110, 41, 238, 0.6); }
        .dropdown-icon { transition: 0.3s; }
        .sidebar-header { padding: 28px 24px; border-bottom: 1px solid rgba(255, 255, 255, 0.1); text-align: center; }
        .sidebar-header img { width: 90px; height: 90px; border-radius: 50%; border: 3px solid white; }
        .sidebar-header h1 { font-size: 1.5rem; color: white; margin-top: 10px; }
        .nav-item { display: flex; align-items: center; gap: 12px; padding: 12px 16px; margin: 5px 10px; border-radius: 12px; color: #cbd5e6; transition: 0.3s; }
        .nav-item:hover, .nav-item.active { background: #fd0054; color: white; }
        .main-content { flex: 1; padding: 25px 30px; background: #f8fafc; overflow-y: auto; height: 100vh; }
        .top-bar { background: white; border-radius: 50px; padding: 12px 25px; margin-bottom: 25px; box-shadow: 0 2px 5px rgba(0,0,0,0.05); display: flex; justify-content: space-between; align-items: center; }
        
        .kpi-row { display: flex; gap: 20px; margin-bottom: 25px; flex-wrap: wrap; }
        .kpi-card { background: white; border-radius: 15px; padding: 20px; flex: 1; min-width: 150px; box-shadow: 0 2px 10px rgba(0,0,0,0.05); border-left: 4px solid #667eea; }
        .kpi-card:nth-child(2) { border-left-color: #28a745; }
        .kpi-card:nth-child(3) { border-left-color: #ffc107; }
        .kpi-card:nth-child(4) { border-left-color: #dc3545; }
        .kpi-card:nth-child(5) { border-left-color: #17a2b8; }
        .kpi-number { font-size: 28px; font-weight: bold; color: #2c3e50; }
        .kpi-title { color: #6c757d; font-size: 14px; }
        
        .filter-section { background: white; border-radius: 15px; padding: 20px; margin-bottom: 25px; }
        .report-container { background: white; border-radius: 15px; padding: 20px; }
        
        .status-present { background: #d4edda; color: #155724; padding: 5px 12px; border-radius: 20px; font-size: 12px; font-weight: 600; display: inline-block; }
        .status-absent { background: #f8d7da; color: #721c24; padding: 5px 12px; border-radius: 20px; font-size: 12px; font-weight: 600; display: inline-block; }
        .status-late { background: #fff3cd; color: #856404; padding: 5px 12px; border-radius: 20px; font-size: 12px; font-weight: 600; display: inline-block; }
        
        .badge-department { padding: 5px 10px; border-radius: 20px; font-size: 11px; font-weight: 600; display: inline-block; }
        .badge-civil { background: #e3f2fd; color: #1565c0; }
        .badge-electrical { background: #fff3e0; color: #e65100; }
        .badge-electronic { background: #e8f5e9; color: #2e7d32; }
        .badge-it { background: #f3e5f5; color: #6a1b9a; }
        .badge-business { background: #fce4ec; color: #c62828; }
        
        .table-responsive { overflow-x: auto; }
        .table thead { background: #f8f9fa; }
        .btn-print { background: #17a2b8; color: white; border: none; padding: 8px 20px; border-radius: 10px; margin-left: 10px; }
        .btn-excel { background: #28a745; color: white; border: none; padding: 8px 20px; border-radius: 10px; }
        
        @media (max-width: 768px) {
            .sidebar { width: 80px; }
            .sidebar-header h1, .sidebar-header p, .nav-item span { display: none; }
            .main-content { padding: 15px; }
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
                <a href="index.php" class="nav-item"><i class="fas fa-tachometer-alt"></i> <span>Main Dashboard</span></a>
                
                <div class="dropdown-container">
                    <div class="nav-item dropdown-btn" onclick="toggleDropdown()">
                        <div><i class="fas fa-user-graduate"></i><span>Students</span></div>
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
                <a href="report.php" class="nav-item active"><i class="fas fa-chart-bar"></i> <span>Report</span></a>
                <a href="Employees.php" class="nav-item"><i class="fas fa-user-friends"></i> <span>Employees</span></a>
                <a href="StudentAttendance.php" class="nav-item"><i class="fas fa-calendar-check"></i> <span>Attendance</span></a>
                
                <div class="nav-bottom">
                    <a href="logout.php" class="nav-item" style="padding-left:8px;">
                        <i class="fas fa-sign-out-alt"></i> <span>Logout</span>
                    </a>
                </div>
            </div>
        </div>
        
        <div class="main-content">
            <div class="top-bar">
                <h4><i class="fas fa-chart-bar"></i> Attendance Report</h4>
                <small>Found <?php echo count($tables_exist); ?> attendance tables</small>
            </div>
            
            <!-- KPI Summary Cards -->
            <div class="kpi-row">
                <div class="kpi-card">
                    <div class="kpi-title">Total Records</div>
                    <div class="kpi-number"><?php echo $total_records; ?></div>
                </div>
                <div class="kpi-card">
                    <div class="kpi-title">Present</div>
                    <div class="kpi-number"><?php echo $present_count; ?></div>
                </div>
                <div class="kpi-card">
                    <div class="kpi-title">Late</div>
                    <div class="kpi-number"><?php echo $late_count; ?></div>
                </div>
                <div class="kpi-card">
                    <div class="kpi-title">Absent</div>
                    <div class="kpi-number"><?php echo $absent_count; ?></div>
                </div>
                <div class="kpi-card">
                    <div class="kpi-title">Attendance Rate</div>
                    <div class="kpi-number"><?php echo $total_records > 0 ? round(($present_count / $total_records) * 100, 1) : 0; ?>%</div>
                </div>
            </div>
            
            <!-- Filter Section -->
            <div class="filter-section">
                <form method="GET" action="" class="row g-3">
                    <div class="col-md-3">
                        <label class="form-label">Date From</label>
                        <input type="date" name="date_from" class="form-control" value="<?php echo $date_from; ?>">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Date To</label>
                        <input type="date" name="date_to" class="form-control" value="<?php echo $date_to; ?>">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Department</label>
                        <select name="department" class="form-select">
                            <option value="">All Departments</option>
                            <option value="Civil Engineering" <?php echo $department == 'Civil Engineering' ? 'selected' : ''; ?>>Civil Engineering</option>
                            <option value="Electrical Engineering" <?php echo $department == 'Electrical Engineering' ? 'selected' : ''; ?>>Electrical Engineering</option>
                            <option value="Electronics" <?php echo $department == 'Electronics' ? 'selected' : ''; ?>>Electronics</option>
                            <option value="Information Technology" <?php echo $department == 'Information Technology' ? 'selected' : ''; ?>>Information Technology</option>
                            <option value="Business" <?php echo $department == 'Business' ? 'selected' : ''; ?>>Business</option>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Status</label>
                        <select name="status" class="form-select">
                            <option value="">All Status</option>
                            <option value="Present" <?php echo $status_filter == 'Present' ? 'selected' : ''; ?>>Present</option>
                            <option value="Absent" <?php echo $status_filter == 'Absent' ? 'selected' : ''; ?>>Absent</option>
                            <option value="Late" <?php echo $status_filter == 'Late' ? 'selected' : ''; ?>>Late</option>
                        </select>
                    </div>
                    <div class="col-md-12 mt-2">
                        <button type="submit" class="btn btn-primary"><i class="fas fa-search"></i> Filter</button>
                        <a href="report.php" class="btn btn-secondary"><i class="fas fa-sync"></i> Reset</a>
                        <button type="button" class="btn btn-excel" onclick="exportToExcel()"><i class="fas fa-file-excel"></i> Export Excel</button>
                        <button type="button" class="btn btn-print" onclick="window.print()"><i class="fas fa-print"></i> Print</button>
                    </div>
                </form>
            </div>
            
            <!-- Report Table -->
            <div class="report-container">
                <h5><i class="fas fa-table"></i> Attendance Details</h5>
                <?php if(isset($error_msg)): ?>
                    <div class="alert alert-warning"><?php echo $error_msg; ?></div>
                <?php endif; ?>
                <hr>
                
                <div class="table-responsive">
                    <table class="table table-bordered table-striped" id="reportTable">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Date</th>
                                <th>Student Name</th>
                                <th>Gender</th>
                                <th>Class</th>
                                <th>Department</th>
                                <th>Status</th>
                                <th>Check In Time</th>
                                <th>Phone</th>
                                <th>Course</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            if ($result && mysqli_num_rows($result) > 0) {
                                while($row = mysqli_fetch_assoc($result)) {
                                    $status_class = '';
                                    $status_text = isset($row['status']) ? $row['status'] : 'Unknown';
                                    
                                    if($status_text == 'Present') {
                                        $status_class = 'status-present';
                                    } elseif($status_text == 'Absent') {
                                        $status_class = 'status-absent';
                                    } elseif($status_text == 'Late') {
                                        $status_class = 'status-late';
                                    }
                                    
                                    $dept_badge = 'badge-civil';
                                    $dept_name = isset($row['department']) ? $row['department'] : 'N/A';
                                    if(strpos($dept_name, 'Electrical') !== false) $dept_badge = 'badge-electrical';
                                    elseif(strpos($dept_name, 'Electronics') !== false) $dept_badge = 'badge-electronic';
                                    elseif(strpos($dept_name, 'Information') !== false) $dept_badge = 'badge-it';
                                    elseif(strpos($dept_name, 'Business') !== false) $dept_badge = 'badge-business';
                                    
                                    echo "<tr>";
                                    echo "<td>" . (isset($row['id']) ? $row['id'] : 'N/A') . "</td>";
                                    echo "<td>" . (isset($row['date']) ? date('d-m-Y', strtotime($row['date'])) : 'N/A') . "</td>";
                                    echo "<td><strong>" . (isset($row['student_name']) ? htmlspecialchars($row['student_name']) : 'N/A') . "</strong></td>";
                                    echo "<td>" . (isset($row['gender']) ? htmlspecialchars($row['gender']) : 'N/A') . "</td>";
                                    echo "<td>" . (isset($row['class']) ? htmlspecialchars($row['class']) : 'N/A') . "</td>";
                                    echo "<td><span class='badge-department $dept_badge'>" . htmlspecialchars($dept_name) . "</span></td>";
                                    echo "<td><span class='$status_class'><i class='fas " . ($status_text == 'Present' ? 'fa-check-circle' : ($status_text == 'Absent' ? 'fa-times-circle' : 'fa-clock')) . "'></i> {$status_text}</span></td>";
                                    echo "<td>" . (!empty($row['check_in_time']) && $row['check_in_time'] != '00:00:00' ? date('h:i A', strtotime($row['check_in_time'])) : '-') . "</td>";
                                    echo "<td>" . (isset($row['phone']) ? htmlspecialchars($row['phone']) : 'N/A') . "</td>";
                                    echo "<td>" . (isset($row['course_name']) ? htmlspecialchars($row['course_name']) : $dept_name) . "</td>";
                                    echo "</tr>";
                                }
                            } else {
                                echo "<tr><td colspan='10' class='text-center'>";
                                if(empty($tables_exist)) {
                                    echo "<i class='fas fa-exclamation-triangle'></i> No attendance tables found in database. Please check your table names.";
                                } else {
                                    echo "<i class='fas fa-info-circle'></i> No attendance records found";
                                }
                                echo "</td></tr>";
                            }
                            ?>
                        </tbody>
                        <?php if($result && mysqli_num_rows($result) > 0): ?>
                        <tfoot>
                            <tr class="table-secondary">
                                <th colspan="6" class="text-end">Summary:</th>
                                <th colspan="4">
                                    <i class="fas fa-check-circle text-success"></i> Present: <?php echo $present_count; ?> | 
                                    <i class="fas fa-clock text-warning"></i> Late: <?php echo $late_count; ?> | 
                                    <i class="fas fa-times-circle text-danger"></i> Absent: <?php echo $absent_count; ?>
                                </th>
                            </tr>
                        </tfoot>
                        <?php endif; ?>
                    </table>
                </div>
            </div>
        </div>
    </div>
    
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.4/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.4/js/dataTables.bootstrap5.min.js"></script>
    
    <script>
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
        
        function exportToExcel() {
            var table = document.getElementById("reportTable");
            var html = table.outerHTML;
            var url = 'data:application/vnd.ms-excel,' + encodeURIComponent(html);
            var link = document.createElement('a');
            link.download = 'attendance_report_' + new Date().toISOString().slice(0,10) + '.xls';
            link.href = url;
            link.click();
        }
        
        $(document).ready(function() {
            if($('#reportTable tbody tr td').length > 0 && $('#reportTable tbody tr td').text().indexOf('No attendance') === -1) {
                $('#reportTable').DataTable({
                    pageLength: 10,
                    order: [[1, 'desc']],
                    language: {
                        search: "Search:",
                        lengthMenu: "Show _MENU_ entries",
                        info: "Showing _START_ to _END_ of _TOTAL_ entries"
                    }
                });
            }
        });
    </script>
</body>
</html>