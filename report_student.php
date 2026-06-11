<?php
include 'db.php';
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Get filters
$search = isset($_GET['search']) ? mysqli_real_escape_string($conn, $_GET['search']) : '';
$gender = isset($_GET['gender']) ? mysqli_real_escape_string($conn, $_GET['gender']) : '';
$class = isset($_GET['class']) ? mysqli_real_escape_string($conn, $_GET['class']) : '';

// First, check if the students table exists and has data
$table_check = mysqli_query($conn, "SHOW TABLES LIKE 'students'");
if (mysqli_num_rows($table_check) == 0) {
    die("Error: 'students' table does not exist in the database.");
}

// Build the query - check if columns exist first
$columns = mysqli_query($conn, "SHOW COLUMNS FROM students");
$existing_columns = [];
while ($col = mysqli_fetch_assoc($columns)) {
    $existing_columns[] = $col['Field'];
}

// Build SELECT part dynamically based on existing columns
$select_fields = "s.id, s.name";
if (in_array('gender', $existing_columns)) $select_fields .= ", s.gender";
if (in_array('class', $existing_columns)) $select_fields .= ", s.class";
if (in_array('email', $existing_columns)) $select_fields .= ", s.email";
if (in_array('phone', $existing_columns)) $select_fields .= ", s.phone";
if (in_array('created_at', $existing_columns)) $select_fields .= ", s.created_at";
// Don't select status from students table anymore
// if (in_array('status', $existing_columns)) $select_fields .= ", s.status";

// Simple query without JOINs first to avoid errors
$sql = "SELECT $select_fields FROM students s WHERE 1=1";

if (!empty($search)) {
    $sql .= " AND (s.name LIKE '%$search%'";
    if (in_array('email', $existing_columns)) $sql .= " OR s.email LIKE '%$search%'";
    if (in_array('phone', $existing_columns)) $sql .= " OR s.phone LIKE '%$search%'";
    $sql .= ")";
}
if (!empty($gender) && in_array('gender', $existing_columns)) {
    $sql .= " AND s.gender = '$gender'";
}
if (!empty($class) && in_array('class', $existing_columns)) {
    $sql .= " AND s.class = '$class'";
}

$sql .= " ORDER BY s.id DESC";

// Execute query with error checking
$result = mysqli_query($conn, $sql);

if (!$result) {
    die("Query failed: " . mysqli_error($conn) . "<br><br>SQL: " . htmlspecialchars($sql));
}

// Get summary statistics
$total_students = mysqli_num_rows($result);
$male_count = 0;
$female_count = 0;

// Calculate gender counts
$temp_result = mysqli_query($conn, $sql);
if ($temp_result && mysqli_num_rows($temp_result) > 0) {
    while($row = mysqli_fetch_assoc($temp_result)) {
        if(isset($row['gender'])) {
            if($row['gender'] == 'Male') $male_count++;
            if($row['gender'] == 'Female') $female_count++;
        }
    }
    mysqli_data_seek($result, 0);
}

// Get class distribution if class column exists
$class_counts = [];
if (in_array('class', $existing_columns)) {
    $class_query = "SELECT class, COUNT(*) as count FROM students WHERE class IS NOT NULL AND class != '' GROUP BY class";
    $class_result = mysqli_query($conn, $class_query);
    if ($class_result) {
        while($row = mysqli_fetch_assoc($class_result)) {
            $class_counts[$row['class']] = $row['count'];
        }
    }
}

// Get payment summary if payments table exists
$payment_check = mysqli_query($conn, "SHOW TABLES LIKE 'student_payments'");
$has_payments = mysqli_num_rows($payment_check) > 0;
$total_paid = 0;
$paid_count = 0;
$unpaid_count = 0;

if ($has_payments) {
    $payment_query = "SELECT SUM(amount) as total, COUNT(*) as count FROM student_payments";
    $payment_result = mysqli_query($conn, $payment_query);
    if ($payment_result && mysqli_num_rows($payment_result) > 0) {
        $payment_data = mysqli_fetch_assoc($payment_result);
        $total_paid = $payment_data['total'] ?? 0;
        $paid_count = $payment_data['count'] ?? 0;
    }
}

// ========== FIXED: Get status counts from 'datastu' table ==========
$active_count = 0;
$inactive_count = 0;
$dropped_count = 0;

// Check if datastu table exists
$datastu_check = mysqli_query($conn, "SHOW TABLES LIKE 'datastu'");
if (mysqli_num_rows($datastu_check) > 0) {
    // Get status counts from datastu table
    $status_query = "SELECT status, COUNT(*) as count FROM datastu GROUP BY status";
    $status_result = mysqli_query($conn, $status_query);
    if ($status_result) {
        while($row = mysqli_fetch_assoc($status_result)) {
            $status_val = strtolower(trim($row['status']));
            if($status_val == 'active') {
                $active_count = $row['count'];
            } elseif($status_val == 'inactive') {
                $inactive_count = $row['count'];
            } elseif($status_val == 'dropped') {
                $dropped_count = $row['count'];
            }
        }
    }
} else {
    // If datastu table doesn't exist, show error message
    echo "<!-- Warning: 'datastu' table does not exist -->";
}

// Get student type counts
$old_students = 0;
$transfer_students = 0;
$new_students = 0;

$type_check = mysqli_query($conn, "SHOW TABLES LIKE 'student_type'");
if (mysqli_num_rows($type_check) > 0) {
    $type_columns = mysqli_query($conn, "SHOW COLUMNS FROM student_type");
    $type_col_names = [];
    while ($col = mysqli_fetch_assoc($type_columns)) {
        $type_col_names[] = $col['Field'];
    }
    
    if (in_array('student_type', $type_col_names)) {
        $old_query = "SELECT COUNT(*) as count FROM student_type WHERE LOWER(student_type) = 'old'";
        $old_result = mysqli_query($conn, $old_query);
        if ($old_result) {
            $old_students = mysqli_fetch_assoc($old_result)['count'];
        }
        
        $transfer_query = "SELECT COUNT(*) as count FROM student_type WHERE LOWER(student_type) = 'transfer'";
        $transfer_result = mysqli_query($conn, $transfer_query);
        if ($transfer_result) {
            $transfer_students = mysqli_fetch_assoc($transfer_result)['count'];
        }
        
        $new_query = "SELECT COUNT(*) as count FROM student_type WHERE LOWER(student_type) = 'new'";
        $new_result = mysqli_query($conn, $new_query);
        if ($new_result) {
            $new_students = mysqli_fetch_assoc($new_result)['count'];
        }
    }
}

// Get payment status counts
$paid_students = 0;
$unpaid_students = 0;

if ($has_payments) {
    $payment_columns = mysqli_query($conn, "SHOW COLUMNS FROM student_payments");
    $payment_col_names = [];
    while ($col = mysqli_fetch_assoc($payment_columns)) {
        $payment_col_names[] = $col['Field'];
    }
    
    if (in_array('status', $payment_col_names)) {
        $paid_query = "SELECT COUNT(*) as count FROM student_payments WHERE LOWER(status) = 'paid'";
        $paid_result = mysqli_query($conn, $paid_query);
        if ($paid_result) {
            $paid_students = mysqli_fetch_assoc($paid_result)['count'];
        }
        
        $unpaid_query = "SELECT COUNT(*) as count FROM student_payments WHERE LOWER(status) = 'unpaid'";
        $unpaid_result = mysqli_query($conn, $unpaid_query);
        if ($unpaid_result) {
            $unpaid_students = mysqli_fetch_assoc($unpaid_result)['count'];
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>KRaksa - Student Report</title>
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
        .top-bar { background: white; border-radius: 50px; padding: 12px 25px; margin-bottom: 25px; display: flex; justify-content: space-between; align-items: center; box-shadow: 0 2px 5px rgba(0,0,0,0.05); }
        .kpi-row { display: flex; gap: 20px; margin-bottom: 25px; flex-wrap: wrap; }
        .kpi-card { background: white; border-radius: 15px; padding: 20px; flex: 1; min-width: 180px; box-shadow: 0 2px 10px rgba(0,0,0,0.05); border-left: 4px solid #667eea; transition: transform 0.2s; }
        .kpi-card:hover { transform: translateY(-5px); }
        .kpi-card:nth-child(2) { border-left-color: #28a745; }
        .kpi-card:nth-child(3) { border-left-color: #ffc107; }
        .kpi-card:nth-child(4) { border-left-color: #17a2b8; }
        .kpi-number { font-size: 28px; font-weight: bold; color: #2c3e50; }
        .kpi-title { color: #6c757d; font-size: 14px; margin-bottom: 10px; }
        .student-stat-item { display: flex; justify-content: space-between; padding: 5px 0; }
        .student-stat-label { color: #6c757d; font-size: 13px; }
        .student-stat-value { font-weight: bold; color: #2c3e50; }
        .filter-section { background: white; border-radius: 15px; padding: 20px; margin-bottom: 25px; box-shadow: 0 2px 10px rgba(0,0,0,0.05); }
        .report-container { background: white; border-radius: 15px; padding: 20px; box-shadow: 0 2px 10px rgba(0,0,0,0.05); }
        .btn-print { background: #17a2b8; color: white; border: none; padding: 8px 20px; border-radius: 10px; margin-left: 10px; }
        .btn-excel { background: #28a745; color: white; border: none; padding: 8px 20px; border-radius: 10px; }
        .status-active { background: #d4edda; color: #155724; padding: 5px 12px; border-radius: 20px; font-size: 12px; font-weight: 500; display: inline-block; }
        .status-inactive { background: #f8d7da; color: #721c24; padding: 5px 12px; border-radius: 20px; font-size: 12px; font-weight: 500; display: inline-block; }
        .status-dropped { background: #fff3cd; color: #856404; padding: 5px 12px; border-radius: 20px; font-size: 12px; font-weight: 500; display: inline-block; }
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
                        <a href="report_student.php" class="nav-item sub-menu active"><i class="fas fa-users"></i><span>Student Report</span></a>
                        <a href="report_month.php" class="nav-item sub-menu"><i class="fas fa-chart-line"></i><span>Monthly Report</span></a>
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
                <h4><i class="fas fa-users"></i> Student Report</h4>
                <small>Total Students: <?php echo $total_students; ?></small>
            </div>
            
            <!-- KPI Summary Cards Row 1 -->
            <div class="kpi-row">
                <div class="kpi-card">
                    <div class="kpi-title">Total Students</div>
                    <div class="kpi-number"><?php echo $total_students; ?></div>
                </div>
                <div class="kpi-card">
                    <div class="kpi-title">Male Students</div>
                    <div class="kpi-number"><?php echo $male_count; ?></div>
                </div>
                <div class="kpi-card">
                    <div class="kpi-title">Female Students</div>
                    <div class="kpi-number"><?php echo $female_count; ?></div>
                </div>
                <div class="kpi-card">
                    <div class="kpi-title">Total Payments</div>
                    <div class="kpi-number">$<?php echo number_format($total_paid, 2); ?></div>
                </div>
            </div>
            
            <!-- KPI Summary Cards Row 2 - Status (Now Fixed with datastu table) -->
            <div class="kpi-row">
                <div class="kpi-card">
                    <div class="kpi-title"><i class="fas fa-chart-line"></i> Status Students</div>
                    <div class="student-stat-item">
                        <span class="student-stat-label"><i class="fas fa-check-circle text-success"></i> Active :</span>
                        <span class="student-stat-value"><?php echo $active_count; ?></span>
                    </div>
                    <div class="student-stat-item">
                        <span class="student-stat-label"><i class="fas fa-ban text-secondary"></i> Inactive :</span>
                        <span class="student-stat-value"><?php echo $inactive_count; ?></span>
                    </div>
                    <div class="student-stat-item">
                        <span class="student-stat-label"><i class="fas fa-user-graduate text-warning"></i> Dropped :</span>
                        <span class="student-stat-value"><?php echo $dropped_count; ?></span>
                    </div>
                </div>
                
                <div class="kpi-card">
                    <div class="kpi-title"><i class="fas fa-tags"></i> Students Type</div>
                    <div class="student-stat-item">
                        <span class="student-stat-label">Student Old :</span>
                        <span class="student-stat-value"><?php echo $old_students; ?></span>
                    </div>
                    <div class="student-stat-item">
                        <span class="student-stat-label">Student Transfer :</span>
                        <span class="student-stat-value"><?php echo $transfer_students; ?></span>
                    </div>
                    <div class="student-stat-item">
                        <span class="student-stat-label">Student New :</span>
                        <span class="student-stat-value"><?php echo $new_students; ?></span>
                    </div>
                </div>
                
                <div class="kpi-card">
                    <div class="kpi-title"><i class="fas fa-money-bill-wave"></i> Students Payment</div>
                    <div class="student-stat-item">
                        <span class="student-stat-label"><i class="fas fa-check-circle text-success"></i> Paid :</span>
                        <span class="student-stat-value"><?php echo $paid_students; ?></span>
                    </div>
                    <div class="student-stat-item">
                        <span class="student-stat-label"><i class="fas fa-clock text-warning"></i> Unpaid :</span>
                        <span class="student-stat-value"><?php echo $unpaid_students; ?></span>
                    </div>
                </div>
            </div>
            
            <!-- Class Distribution -->
            <?php if(!empty($class_counts)): ?>
            <div class="kpi-row">
                <?php foreach($class_counts as $class_name => $count): ?>
                <div class="kpi-card">
                    <div class="kpi-title">Class <?php echo htmlspecialchars($class_name); ?></div>
                    <div class="kpi-number"><?php echo $count; ?></div>
                    <small><?php echo $total_students > 0 ? round(($count/$total_students)*100, 1) : 0; ?>% of total</small>
                </div>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>
            
            <!-- Filter Section -->
            <div class="filter-section">
                <form method="GET" action="" class="row g-3">
                    <div class="col-md-4">
                        <label class="form-label"><i class="fas fa-search"></i> Search</label>
                        <input type="text" name="search" class="form-control" placeholder="Name, Email, Phone..." value="<?php echo htmlspecialchars($search); ?>">
                    </div>
                    <?php if(in_array('gender', $existing_columns)): ?>
                    <div class="col-md-3">
                        <label class="form-label"><i class="fas fa-venus-mars"></i> Gender</label>
                        <select name="gender" class="form-select">
                            <option value="">All</option>
                            <option value="Male" <?php echo $gender == 'Male' ? 'selected' : ''; ?>>Male</option>
                            <option value="Female" <?php echo $gender == 'Female' ? 'selected' : ''; ?>>Female</option>
                        </select>
                    </div>
                    <?php endif; ?>
                    <?php if(in_array('class', $existing_columns)): ?>
                    <div class="col-md-3">
                        <label class="form-label"><i class="fas fa-graduation-cap"></i> Class</label>
                        <select name="class" class="form-select">
                            <option value="">All Classes</option>
                            <option value="1" <?php echo $class == '1' ? 'selected' : ''; ?>>Class 1</option>
                            <option value="2" <?php echo $class == '2' ? 'selected' : ''; ?>>Class 2</option>
                            <option value="3" <?php echo $class == '3' ? 'selected' : ''; ?>>Class 3</option>
                            <option value="4" <?php echo $class == '4' ? 'selected' : ''; ?>>Class 4</option>
                            <option value="5" <?php echo $class == '5' ? 'selected' : ''; ?>>Class 5</option>
                            <option value="6" <?php echo $class == '6' ? 'selected' : ''; ?>>Class 6</option>
                        </select>
                    </div>
                    <?php endif; ?>
                    <div class="col-md-2">
                        <label class="form-label">&nbsp;</label>
                        <button type="submit" class="btn btn-primary form-control"><i class="fas fa-search"></i> Filter</button>
                    </div>
                    <div class="col-md-12 mt-2">
                        <a href="report_student.php" class="btn btn-secondary"><i class="fas fa-sync"></i> Reset</a>
                        <button type="button" class="btn btn-excel" onclick="exportToExcel()"><i class="fas fa-file-excel"></i> Excel</button>
                        <button type="button" class="btn btn-print" onclick="window.print()"><i class="fas fa-print"></i> Print</button>
                    </div>
                </form>
            </div>
            
            <!-- Report Table -->
            <div class="report-container">
                <h5><i class="fas fa-table"></i> Students Details</h5>
                <hr>
                <div class="table-responsive">
                    <table class="table table-bordered table-striped table-hover" id="reportTable">
                        <thead class="table-dark">
                            <tr>
                                <th>ID</th>
                                <th>Name</th>
                                <?php if(in_array('gender', $existing_columns)): ?><th>Gender</th><?php endif; ?>
                                <?php if(in_array('class', $existing_columns)): ?><th>Class</th><?php endif; ?>
                                <?php if(in_array('email', $existing_columns)): ?><th>Email</th><?php endif; ?>
                                <?php if(in_array('phone', $existing_columns)): ?><th>Phone</th><?php endif; ?>
                             
                                <?php if(in_array('created_at', $existing_columns)): ?><th>Registered Date</th><?php endif; ?>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if($result && mysqli_num_rows($result) > 0): ?>
                                <?php while($row = mysqli_fetch_assoc($result)): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($row['id']); ?></td>
                                    <td><strong><?php echo htmlspecialchars($row['name']); ?></strong></td>
                                    <?php if(in_array('gender', $existing_columns)): ?>
                                    <td>
                                        <?php if(($row['gender'] ?? '') == 'Male'): ?>
                                            <i class="fas fa-mars text-primary"></i>
                                        <?php elseif(($row['gender'] ?? '') == 'Female'): ?>
                                            <i class="fas fa-venus text-danger"></i>
                                        <?php endif; ?>
                                        <?php echo htmlspecialchars($row['gender'] ?? 'N/A'); ?>
                                    </td>
                                    <?php endif; ?>
                                    <?php if(in_array('class', $existing_columns)): ?>
                                    <td><span class="badge bg-info">Class <?php echo htmlspecialchars($row['class'] ?? 'N/A'); ?></span></td>
                                    <?php endif; ?>
                                    <?php if(in_array('email', $existing_columns)): ?>
                                    <td><?php echo htmlspecialchars($row['email'] ?? 'N/A'); ?></td>
                                    <?php endif; ?>
                                    <?php if(in_array('phone', $existing_columns)): ?>
                                    <td><?php echo htmlspecialchars($row['phone'] ?? 'N/A'); ?></td>
                                    <?php endif; ?>
                                    
                                    <?php if(in_array('created_at', $existing_columns)): ?>
                                    <td><?php echo !empty($row['created_at']) ? date('d-m-Y', strtotime($row['created_at'])) : 'N/A'; ?></td>
                                    <?php endif; ?>
                                </tr>
                                <?php endwhile; ?>
                            <?php else: ?>
                                <tr><td colspan="10" class="text-center">No students found</td></td>
                            <?php endif; ?>
                        </tbody>
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
        
        function exportToExcel() {
            var table = document.getElementById("reportTable");
            var html = table.outerHTML;
            var url = 'data:application/vnd.ms-excel,' + encodeURIComponent(html);
            var link = document.createElement('a');
            link.download = 'student_report_' + new Date().toISOString().slice(0,10) + '.xls';
            link.href = url;
            link.click();
        }
        
        $(document).ready(function() {
            $('#reportTable').DataTable({
                pageLength: 10,
                order: [[0, 'desc']],
                language: {
                    search: "Search:",
                    lengthMenu: "Show _MENU_ entries",
                    info: "Showing _START_ to _END_ of _TOTAL_ entries"
                }
            });
        });
        
        // Close dropdowns when clicking outside
        document.addEventListener('click', function(event) {
            const studentContainer = document.querySelector('.dropdown-container:first-child');
            const reportContainer = document.querySelectorAll('.dropdown-container')[1];
            
            if(studentContainer && !studentContainer.contains(event.target)) {
                const studentMenu = document.getElementById("studentDropdownMenu");
                const studentIcon = document.getElementById("studentDropdownIcon");
                if(studentMenu && studentMenu.style.display === "block") {
                    studentMenu.style.display = "none";
                    if(studentIcon) studentIcon.style.transform = "rotate(0deg)";
                }
            }
            
            if(reportContainer && !reportContainer.contains(event.target)) {
                const reportMenu = document.getElementById("reportDropdownMenu");
                const reportIcon = document.getElementById("reportDropdownIcon");
                if(reportMenu && reportMenu.style.display === "block") {
                    reportMenu.style.display = "none";
                    if(reportIcon) reportIcon.style.transform = "rotate(0deg)";
                }
            }
        });
    </script>
</body>
</html>