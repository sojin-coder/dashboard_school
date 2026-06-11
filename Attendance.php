<?php
include 'db.php';

// Fetch attendance data
$sql = "SELECT * FROM attendance ORDER BY id DESC";
$result = mysqli_query($conn, $sql);

// === បន្ថែមកូដសម្រាប់រាប់ចំនួន Attendance ប្រចាំថ្ងៃ ===

// 1. រាប់ចំនួនសរុបនៃ attendance ថ្ងៃនេះ
$today = date('Y-m-d');
$sql_today = "SELECT COUNT(*) as total FROM attendance WHERE attendance_date = '$today'";
$result_today = mysqli_query($conn, $sql_today);
$today_count = mysqli_fetch_assoc($result_today)['total'];

// 2. រាប់ចំនួនតាម status ថ្ងៃនេះ
$sql_present = "SELECT COUNT(*) as count FROM attendance WHERE attendance_date = '$today' AND status = 'Present'";
$result_present = mysqli_query($conn, $sql_present);
$present_count = mysqli_fetch_assoc($result_present)['count'];

$sql_absent = "SELECT COUNT(*) as count FROM attendance WHERE attendance_date = '$today' AND status = 'Absent'";
$result_absent = mysqli_query($conn, $sql_absent);
$absent_count = mysqli_fetch_assoc($result_absent)['count'];

$sql_late = "SELECT COUNT(*) as count FROM attendance WHERE attendance_date = '$today' AND status = 'Late'";
$result_late = mysqli_query($conn, $sql_late);
$late_count = mysqli_fetch_assoc($result_late)['count'];

$sql_leave = "SELECT COUNT(*) as count FROM attendance WHERE attendance_date = '$today' AND status = 'Leave'";
$result_leave = mysqli_query($conn, $sql_leave);
$leave_count = mysqli_fetch_assoc($result_leave)['count'];

// 3. ស្វែងរកតាមកាលបរិច្ឆេទ (ប្រសិនបើមានការបញ្ជូន)
$search_date = isset($_GET['search_date']) ? $_GET['search_date'] : $today;
$sql_search = "SELECT COUNT(*) as total FROM attendance WHERE attendance_date = '$search_date'";
$result_search = mysqli_query($conn, $sql_search);
$search_count = mysqli_fetch_assoc($result_search)['total'];

$sql_search_present = "SELECT COUNT(*) as count FROM attendance WHERE attendance_date = '$search_date' AND status = 'Present'";
$result_search_present = mysqli_query($conn, $sql_search_present);
$search_present = mysqli_fetch_assoc($result_search_present)['count'];

$sql_search_absent = "SELECT COUNT(*) as count FROM attendance WHERE attendance_date = '$search_date' AND status = 'Absent'";
$result_search_absent = mysqli_query($conn, $sql_search_absent);
$search_absent = mysqli_fetch_assoc($result_search_absent)['count'];

$sql_search_late = "SELECT COUNT(*) as count FROM attendance WHERE attendance_date = '$search_date' AND status = 'Late'";
$result_search_late = mysqli_query($conn, $sql_search_late);
$search_late = mysqli_fetch_assoc($result_search_late)['count'];

$sql_search_leave = "SELECT COUNT(*) as count FROM attendance WHERE attendance_date = '$search_date' AND status = 'Leave'";
$result_search_leave = mysqli_query($conn, $sql_search_leave);
$search_leave = mysqli_fetch_assoc($result_search_leave)['count'];

// 4. រាប់ចំនួនសរុបទាំងអស់ក្នុង database
$sql_total_all = "SELECT COUNT(*) as total FROM attendance";
$result_total_all = mysqli_query($conn, $sql_total_all);
$total_all_count = mysqli_fetch_assoc($result_total_all)['total'];

// 5. ទិន្នន័យសម្រាប់បង្ហាញក្រាហ្វ (7 ថ្ងៃចុងក្រោយ)
$sql_weekly = "SELECT 
                    attendance_date,
                    COUNT(*) as total,
                    SUM(CASE WHEN status = 'Present' THEN 1 ELSE 0 END) as present,
                    SUM(CASE WHEN status = 'Absent' THEN 1 ELSE 0 END) as absent,
                    SUM(CASE WHEN status = 'Late' THEN 1 ELSE 0 END) as late,
                    SUM(CASE WHEN status = 'Leave' THEN 1 ELSE 0 END) as leave
                FROM attendance 
                WHERE attendance_date >= DATE_SUB('$today', INTERVAL 6 DAY)
                GROUP BY attendance_date
                ORDER BY attendance_date DESC";
$result_weekly = mysqli_query($conn, $sql_weekly);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <title>KRaksa Education Suite - Attendance</title>
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
        }
        
        /* KPI Cards Styles */
        .kpi-daily {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border-radius: 20px;
            padding: 25px;
            margin-bottom: 30px;
            color: white;
        }
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }
        .stat-card {
            background: white;
            border-radius: 20px;
            padding: 20px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.08);
            transition: transform 0.3s ease;
            text-align: center;
            border-bottom: 4px solid;
        }
        .stat-card:hover {
            transform: translateY(-5px);
        }
        .stat-card .stat-icon {
            font-size: 2.5rem;
            margin-bottom: 10px;
        }
        .stat-card .stat-label {
            color: #64748b;
            font-size: 0.85rem;
            text-transform: uppercase;
            letter-spacing: 1px;
            margin-bottom: 8px;
        }
        .stat-card .stat-number {
            font-size: 2rem;
            font-weight: bold;
            color: #1e293b;
            margin-bottom: 5px;
        }
        .stat-card .stat-percent {
            font-size: 0.8rem;
            color: #94a3b8;
        }
        
        .date-filter-box {
            background: white;
            border-radius: 15px;
            padding: 20px;
            margin-bottom: 30px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.08);
        }
        
        .summary-box {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border-radius: 15px;
            padding: 20px;
            margin-bottom: 20px;
            color: white;
        }
        
        .chart-container {
            background: white;
            border-radius: 20px;
            padding: 20px;
            margin-bottom: 30px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.08);
        }
        
        * { margin: 0; padding: 0; box-sizing: border-box; }
        a { text-decoration: none; }
        body { font-family: "Inter", sans-serif; background: #c5e1fc; color: #0f172a; }
        .container { display: flex; min-height: 100vh; max-width: 100%; }
        .sidebar { 
            width: 300px; 
            /* background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); */
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
        .sidebar-header { padding: 28px 24px; border-bottom: 1px solid rgba(255, 255, 255, 0.1); text-align: center; }
        .sidebar-header img { width: 100px; height: 100px; border-radius: 50%; margin: auto; display: block; border: 4px solid white; object-fit: cover; }
        .sidebar-header h1 { font-size: 1.8rem; font-weight: 700; color: white; margin-top: 10px; }
        .nav-menu { flex: 1; padding: 0 16px; }
        .nav-item { display: flex; align-items: center; gap: 15px; padding: 12px 16px; margin-bottom: 8px; border-radius: 14px; transition: 0.3s; color: #cbd5e6; }
        .nav-item:hover { background: rgba(255, 255, 255, 0.1); color: white; }
        .nav-item.active { background: #fd0054; color: white; box-shadow: 0 4px 8px rgba(0, 0, 0, 0.2); }
        .main-content { flex: 1; padding: 28px 32px; background: #f8fafc; overflow-y: auto; height: 100vh; }
        .top-bar { display: flex; justify-content: space-between; align-items: center; padding: 12px 24px; background: white; border-radius: 60px; box-shadow: 0 1px 3px rgba(0,0,0,0.05); margin-bottom: 28px; }
        .page-title h2 { font-size: 1.3rem; font-weight: 600; color: #1e293b; margin: 0; }
        .form-container { background: white; border-radius: 20px; box-shadow: 0 10px 30px rgba(0,0,0,0.08); margin-bottom: 40px; overflow: hidden; }
        .header-banner { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; padding: 18px 25px; font-size: 20px; font-weight: 600; }
        .btn-primary { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); border: none; padding: 10px 25px; border-radius: 10px; font-weight: 600; }
        @media (max-width: 768px) {
            .sidebar { width: 80px; }
            .sidebar-header h1, .sidebar-header p, .nav-item span { display: none; }
            .main-content { padding: 15px; }
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
                 <a href="schedule.php" class="nav-item" data-page="schedule"> <i class="fas fa-calendar-alt"></i> <span>Schedule class</span></a>
                
                <a href="Employees.php" class="nav-item"><i class="fas fa-user-friends"></i> <span>Employees</span></a>
                <a href="#" class="nav-item active"><i class="fas fa-calendar-check"></i> <span>Attendance</span></a>
                <div class="nav-bottom">
                    <a href="logout.php" class="nav-item"><i class="fas fa-sign-out-alt"></i> <span>Logout</span></a>
                </div>
            </div>
        </div>

        <div class="main-content">
            <div class="top-bar">
                <div class="page-title"><h2>📊 Attendance Management</h2></div>
                <div><span class="badge bg-primary">Today: <?php echo date('d/m/Y'); ?></span></div>
                 <a href="StudentAttendance.php" class="back-btn">
                        <i class="fas fa-arrow-left"></i> Back
                    </a>
            </div>

            <!-- ========== បង្ហាញចំនួន Attendance ថ្ងៃនេះ ========== -->
            <div class="kpi-daily">
                <div class="row align-items-center">
                    <div class="col-md-6">
                        <h3 class="mb-2"><i class="fas fa-calendar-day"></i> Attendance Today</h3>
                        <h1 style="font-size: 48px; font-weight: bold;"><?php echo $today_count; ?> <span style="font-size: 20px;">people</span></h1>
                        <p class="mb-0">📅 <?php echo date('l, d F Y'); ?></p>
                    </div>
                    <div class="col-md-6">
                        <div class="row text-center">
                            <div class="col-3">
                                <i class="fas fa-check-circle" style="font-size: 30px;"></i>
                                <h4><?php echo $present_count; ?></h4>
                                <small>Present</small>
                            </div>
                            <div class="col-3">
                                <i class="fas fa-clock" style="font-size: 30px;"></i>
                                <h4><?php echo $late_count; ?></h4>
                                <small>Late</small>
                            </div>
                            <div class="col-3">
                                <i class="fas fa-times-circle" style="font-size: 30px;"></i>
                                <h4><?php echo $absent_count; ?></h4>
                                <small>Absent</small>
                            </div>
                            <div class="col-3">
                                <i class="fas fa-umbrella-beach" style="font-size: 30px;"></i>
                                <h4><?php echo $leave_count; ?></h4>
                                <small>Leave</small>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- ========== ប្រអប់ស្វែងរកតាមកាលបរិច្ឆេទ ========== -->
            <div class="date-filter-box">
                <form method="GET" action="" class="row g-3 align-items-end">
                    <div class="col-md-8">
                        <label class="form-label fw-bold">🔍 Search Attendance by Date</label>
                        <input type="date" name="search_date" class="form-control" value="<?php echo $search_date; ?>">
                    </div>
                    <div class="col-md-4">
                        <button type="submit" class="btn btn-primary w-100"><i class="fas fa-search"></i> Show Report</button>
                    </div>
                </form>
            </div>

            <!-- ========== បង្ហាញលទ្ធផលតាមកាលបរិច្ឆេទដែលបានស្វែងរក ========== -->
            <div class="summary-box">
                <div class="row">
                    <div class="col-12">
                        <h4><i class="fas fa-chart-simple"></i> Summary for <?php echo date('d/m/Y', strtotime($search_date)); ?></h4>
                        <hr style="background: rgba(255,255,255,0.3);">
                    </div>
                </div>
                <div class="row text-center">
                    <div class="col-md-3">
                        <div class="border-end border-white">
                            <h2><?php echo $search_count; ?></h2>
                            <p>Total Attendance</p>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="border-end border-white">
                            <h2><?php echo $search_present; ?></h2>
                            <p>✅ Present</p>
                        </div>
                    </div>
                    <div class="col-md-2">
                        <div class="border-end border-white">
                            <h2><?php echo $search_late; ?></h2>
                            <p>⏰ Late</p>
                        </div>
                    </div>
                    <div class="col-md-2">
                        <div class="border-end border-white">
                            <h2><?php echo $search_absent; ?></h2>
                            <p>❌ Absent</p>
                        </div>
                    </div>
                    <div class="col-md-2">
                        <div>
                            <h2><?php echo $search_leave; ?></h2>
                            <p>🏖️ Leave</p>
                        </div>
                    </div>
                </div>
                <?php if($search_count > 0): ?>
                <div class="row mt-3">
                    <div class="col-12">
                        <div class="progress" style="height: 30px;">
                            <div class="progress-bar bg-success" style="width: <?php echo ($search_present/$search_count)*100; ?>%">
                                Present <?php echo round(($search_present/$search_count)*100); ?>%
                            </div>
                            <div class="progress-bar bg-warning" style="width: <?php echo ($search_late/$search_count)*100; ?>%">
                                Late <?php echo round(($search_late/$search_count)*100); ?>%
                            </div>
                            <div class="progress-bar bg-danger" style="width: <?php echo ($search_absent/$search_count)*100; ?>%">
                                Absent <?php echo round(($search_absent/$search_count)*100); ?>%
                            </div>
                            <div class="progress-bar bg-info" style="width: <?php echo ($search_leave/$search_count)*100; ?>%">
                                Leave <?php echo round(($search_leave/$search_count)*100); ?>%
                            </div>
                        </div>
                    </div>
                </div>
                <?php endif; ?>
            </div>

            <!-- ========== Statistics Cards (All Time) ========== -->
            <div class="stats-grid">
                <div class="stat-card" style="border-bottom-color: #3b82f6;">
                    <div class="stat-icon"><i class="fas fa-users" style="color: #3b82f6;"></i></div>
                    <div class="stat-label">សរុបទាំងអស់</div>
                    <div class="stat-number"><?php echo $total_all_count; ?></div>
                    <div class="stat-percent">Total records</div>
                </div>
                <div class="stat-card" style="border-bottom-color: #10b981;">
                    <div class="stat-icon"><i class="fas fa-check-circle" style="color: #10b981;"></i></div>
                    <div class="stat-label">Present Rate</div>
                    <div class="stat-number">
                        <?php 
                        $present_rate = $total_all_count > 0 ? round(($present_count/$total_all_count)*100) : 0;
                        echo $present_rate; ?>%
                    </div>
                    <div class="stat-percent"><?php echo $present_count; ?> records</div>
                </div>
                <div class="stat-card" style="border-bottom-color: #ef4444;">
                    <div class="stat-icon"><i class="fas fa-exclamation-triangle" style="color: #ef4444;"></i></div>
                    <div class="stat-label">Absent Rate</div>
                    <div class="stat-number">
                        <?php 
                        $absent_rate = $total_all_count > 0 ? round(($absent_count/$total_all_count)*100) : 0;
                        echo $absent_rate; ?>%
                    </div>
                    <div class="stat-percent"><?php echo $absent_count; ?> records</div>
                </div>
            </div>

            <!-- ========== Chart 7 Days ========== -->
            <!-- <div class="chart-container">
                <h5 class="mb-3"><i class="fas fa-chart-line"></i> Attendance Statistics (Last 7 Days)</h5>
                <canvas id="attendanceChart" style="max-height: 300px;"></canvas>
            </div> -->

            <!-- ========== Attendance Form ========== -->
            <div class="form-container">
                <div class="header-banner">✍️ Attendance Registration</div>
                <form action="processatt.php" method="POST" class="form-body" style="padding: 25px;">
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label class="form-label">Employee ID:</label>
                            <input type="text" name="employee_id" class="form-control" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Schedule ID:</label>
                            <input type="text" name="schedule_id" class="form-control" required>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Attendance Date:</label>
                        <input type="date" name="attendance_date" class="form-control" value="<?php echo $today; ?>" required>
                    </div>
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label class="form-label">Check In:</label>
                            <input type="time" name="check_in" class="form-control">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Check Out:</label>
                            <input type="time" name="check_out" class="form-control">
                        </div>
                    </div>
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label class="form-label">Status:</label>
                            <select name="status" class="form-select" required>
                                <option selected disabled>Please Select status</option>
                                <option value="Present">✅ Present</option>
                                <option value="Absent">❌ Absent</option>
                                <option value="Leave">🏖️ Leave</option>
                                <option value="Late">⏰ Late</option>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Note:</label>
                            <input type="text" name="note" class="form-control" placeholder="Optional note">
                        </div>
                    </div>
                    <div class="mt-4">
                        <button type="submit" class="btn btn-primary px-4"><i class="fas fa-save"></i> Save</button>
                        <button type="reset" class="btn btn-secondary px-4"><i class="fas fa-undo"></i> Clear</button>
                    </div>
                </form>
            </div>

            <!-- ========== Attendance Records Table ========== -->
            <div class="data-table-wrapper mt-4">
                <div class="header-banner" style="border-radius: 20px 20px 0 0;">📋 Attendance Records</div>
                <div class="table-container">
                    <table class="table table-bordered mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>ID</th>
                                <th>Employee ID</th>
                                <th>Schedule ID</th>
                                <th>Attendance Date</th>
                                <th>Check In</th>
                                <th>Check Out</th>
                                <th>Status</th>
                                <th>Note</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (mysqli_num_rows($result) > 0): ?>
                                <?php while($row = mysqli_fetch_assoc($result)): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($row['id']); ?></td>
                                        <td><?php echo htmlspecialchars($row['employee_id']); ?></td>
                                        <td><?php echo htmlspecialchars($row['schedule_id']); ?></td>
                                        <td><?php echo htmlspecialchars($row['attendance_date']); ?></td>
                                        <td><?php echo htmlspecialchars($row['check_in']); ?></td>
                                        <td><?php echo htmlspecialchars($row['check_out']); ?></td>
                                        <td>
                                            <span class="badge bg-<?php 
                                                echo $row['status'] == 'Present' ? 'success' : 
                                                    ($row['status'] == 'Absent' ? 'danger' : 
                                                    ($row['status'] == 'Late' ? 'warning' : 'info')); 
                                            ?>">
                                                <?php echo htmlspecialchars($row['status']); ?>
                                            </span>
                                        </td>
                                        <td><?php echo htmlspecialchars($row['note']); ?></td>
                                        <td>
                                            <a href='edit_Attendance.php?id=<?php echo $row['id']; ?>' class='btn btn-warning btn-sm'><i class='fas fa-edit'></i></a>
                                            <a href='delete_Attendance.php?id=<?php echo $row['id']; ?>' class='btn btn-danger btn-sm' onclick='return confirm("Are you sure?")'><i class='fas fa-trash'></i></a>
                                        </td>
                                    </tr>
                                <?php endwhile; ?>
                            <?php else: ?>
                                <tr><td colspan="9" class="text-center">No attendance records found</td></tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
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

</script>
    <script>
        // Chart.js for attendance visualization
        const ctx = document.getElementById('attendanceChart').getContext('2d');
        
        // Prepare data from PHP
        const dates = [];
        const presentData = [];
        const absentData = [];
        const lateData = [];
        const leaveData = [];
        
        <?php 
        mysqli_data_seek($result_weekly, 0);
        while($row = mysqli_fetch_assoc($result_weekly)): 
        ?>
            dates.push('<?php echo $row['attendance_date']; ?>');
            presentData.push(<?php echo $row['present']; ?>);
            absentData.push(<?php echo $row['absent']; ?>);
            lateData.push(<?php echo $row['late']; ?>);
            leaveData.push(<?php echo $row['leave']; ?>);
        <?php endwhile; ?>
        
        new Chart(ctx, {
            type: 'bar',
            data: {
                labels: dates.reverse(),
                datasets: [
                    {
                        label: 'Present',
                        data: presentData.reverse(),
                        backgroundColor: 'rgba(16, 185, 129, 0.7)',
                        borderColor: '#10b981',
                        borderWidth: 1
                    },
                    {
                        label: 'Absent',
                        data: absentData.reverse(),
                        backgroundColor: 'rgba(239, 68, 68, 0.7)',
                        borderColor: '#ef4444',
                        borderWidth: 1
                    },
                    {
                        label: 'Late',
                        data: lateData.reverse(),
                        backgroundColor: 'rgba(245, 158, 11, 0.7)',
                        borderColor: '#f59e0b',
                        borderWidth: 1
                    },
                    {
                        label: 'Leave',
                        data: leaveData.reverse(),
                        backgroundColor: 'rgba(139, 92, 246, 0.7)',
                        borderColor: '#8b5cf6',
                        borderWidth: 1
                    }
                ]
            },
            options: {
                responsive: true,
                maintainAspectRatio: true,
                plugins: {
                    legend: {
                        position: 'top',
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: {
                            stepSize: 1
                        }
                    }
                }
            }
        });
    </script>
</body>
</html>