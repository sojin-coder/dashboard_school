<?php
include 'db.php';

// Fetch attendance data
$sql = "SELECT * FROM attendantbusiness ORDER BY id DESC";
$result = mysqli_query($conn, $sql);

// === Attendance Counting Functions ===

// Today's date
$today = date('Y-m-d');

// 1. Count today's total attendance
$sql_today = "SELECT COUNT(*) as total FROM attendantbusiness WHERE attendance_date = '$today'";
$result_today = mysqli_query($conn, $sql_today);
$today_count = mysqli_fetch_assoc($result_today)['total'];

// 2. Count by status for today
$sql_present = "SELECT COUNT(*) as count FROM attendantbusiness WHERE attendance_date = '$today' AND status = 'Active'";
$result_present = mysqli_query($conn, $sql_present);
$present_count = mysqli_fetch_assoc($result_present)['count'];

$sql_absent = "SELECT COUNT(*) as count FROM attendantbusiness WHERE attendance_date = '$today' AND status = 'A'";
$result_absent = mysqli_query($conn, $sql_absent);
$absent_count = mysqli_fetch_assoc($result_absent)['count'];

$sql_late = "SELECT COUNT(*) as count FROM attendantbusiness WHERE attendance_date = '$today' AND status = 'P'";
$result_late = mysqli_query($conn, $sql_late);
$late_count = mysqli_fetch_assoc($result_late)['count'];

// 3. Search by date functionality
$search_date = isset($_GET['search_date']) ? $_GET['search_date'] : $today;
$sql_search = "SELECT COUNT(*) as total FROM attendantbusiness WHERE attendance_date = '$search_date'";
$result_search = mysqli_query($conn, $sql_search);
$search_count = mysqli_fetch_assoc($result_search)['total'];

$sql_search_present = "SELECT COUNT(*) as count FROM attendantbusiness WHERE attendance_date = '$search_date' AND status = 'Active'";
$result_search_present = mysqli_query($conn, $sql_search_present);
$search_present = mysqli_fetch_assoc($result_search_present)['count'];

$sql_search_absent = "SELECT COUNT(*) as count FROM attendantbusiness WHERE attendance_date = '$search_date' AND status = 'A'";
$result_search_absent = mysqli_query($conn, $sql_search_absent);
$search_absent = mysqli_fetch_assoc($result_search_absent)['count'];

$sql_search_late = "SELECT COUNT(*) as count FROM attendantbusiness WHERE attendance_date = '$search_date' AND status = 'P'";
$result_search_late = mysqli_query($conn, $sql_search_late);
$search_late = mysqli_fetch_assoc($result_search_late)['count'];

// 4. Total count in database
$sql_total_all = "SELECT COUNT(*) as total FROM attendantbusiness";
$result_total_all = mysqli_query($conn, $sql_total_all);
$total_all_count = mysqli_fetch_assoc($result_total_all)['total'];

// ========== NEW: Year-based Statistics ==========

// Function to get year statistics
function getYearStats($conn, $year, $shift = null, $status = null) {
    $sql = "SELECT COUNT(*) as total FROM attendantbusiness WHERE year = '$year'";
    if ($shift) $sql .= " AND shift = '$shift'";
    if ($status) $sql .= " AND status = '$status'";
    $result = mysqli_query($conn, $sql);
    $row = mysqli_fetch_assoc($result);
    return $row['total'];
}

// Get total students per year (distinct student_id)
$yearly_totals = [];
$sql_yearly_total = "SELECT year, COUNT(DISTINCT student_id) as total FROM attendantbusiness GROUP BY year";
$result_yearly_total = mysqli_query($conn, $sql_yearly_total);
while ($row = mysqli_fetch_assoc($result_yearly_total)) {
    $yearly_totals[$row['year']] = $row['total'];
}

// Get morning attendance per year
$yearly_morning_active = [];
$sql_morning = "SELECT year, COUNT(*) as total FROM attendantbusiness WHERE shift = 'morning' AND status = 'Active' GROUP BY year";
$result_morning = mysqli_query($conn, $sql_morning);
while ($row = mysqli_fetch_assoc($result_morning)) {
    $yearly_morning_active[$row['year']] = $row['total'];
}

// Get evening attendance per year
$yearly_evening_active = [];
$sql_evening = "SELECT year, COUNT(*) as total FROM attendantbusiness WHERE shift = 'Evening' AND status = 'Active' GROUP BY year";
$result_evening = mysqli_query($conn, $sql_evening);
while ($row = mysqli_fetch_assoc($result_evening)) {
    $yearly_evening_active[$row['year']] = $row['total'];
}

// Helper function to safely get value
function getValue($array, $key) {
    return isset($array[$key]) ? $array[$key] : 0;
}

// 5. Weekly data for chart (last 7 days)
$sql_weekly = "SELECT 
                    attendance_date,
                    COUNT(*) as total,
                    SUM(CASE WHEN status = 'Active' THEN 1 ELSE 0 END) as Active,
                    SUM(CASE WHEN status = 'A' THEN 1 ELSE 0 END) as A,
                    SUM(CASE WHEN status = 'P' THEN 1 ELSE 0 END) as P
                FROM attendantbusiness 
                WHERE attendance_date >= DATE_SUB('$today', INTERVAL 6 DAY)
                GROUP BY attendance_date
                ORDER BY attendance_date ASC";
$result_weekly = mysqli_query($conn, $sql_weekly);

// Store weekly data in arrays for JavaScript
$weekly_dates = [];
$weekly_present = [];
$weekly_absent = [];
$weekly_late = [];

while($row = mysqli_fetch_assoc($result_weekly)) {
    $weekly_dates[] = $row['attendance_date'];
    $weekly_present[] = $row['Active'];
    $weekly_absent[] = $row['A'];
    $weekly_late[] = $row['P'];
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
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <title>KRaksa Education Suite - IT Attendance</title>
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
        
        /* New style for year cards */
        .year-card {
            background: white;
            border-radius: 20px;
            padding: 20px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.08);
            transition: transform 0.3s ease;
            border-bottom: 4px solid;
            text-align: left;
        }
        .year-card:hover {
            transform: translateY(-5px);
        }
        .year-title {
            font-size: 1.5rem;
            font-weight: bold;
            margin-bottom: 15px;
            padding-bottom: 10px;
            border-bottom: 2px solid #e2e8f0;
            text-align: center;
        }
        .year-stats {
            margin-top: 10px;
        }
        .year-stat-item {
            display: flex;
            justify-content: space-between;
            padding: 8px 0;
            border-bottom: 1px dashed #e2e8f0;
        }
        .year-stat-label {
            color: #64748b;
            font-weight: 500;
        }
        .year-stat-value {
            font-weight: bold;
            color: #1e293b;
        }
        .stat-percent {
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
        
        .back-btn {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 8px 20px;
            background: linear-gradient(135deg, #667eea, #764ba2);
            color: white;
            border-radius: 30px;
            text-decoration: none;
            font-weight: 500;
            transition: 0.3s;
        }
        
        .back-btn:hover {
            transform: translateX(-3px);
            color: white;
            box-shadow: 0 4px 12px rgba(102, 126, 234, 0.4);
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
                <a href="forteacher.php" class="nav-item">
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
                        <a href="list_for_teacher.php" class="nav-item sub-menu">
                            <i class="fas fa-users"></i> <span>Student List</span>
                        </a>
                        <a href="view_for_teacher.php" class="nav-item sub-menu">
                            <i class="fas fa-eye"></i> <span>Student View</span>
                        </a>
                        <a href="score_for_teacher.php" class="nav-item sub-menu">
                            <i class="fas fa-chart-line"></i> <span>Student Scores</span>
                        </a>
                    </div>
                </div>
                
                <a href="teacher_fortea.php" class="nav-item active">
                    <i class="fas fa-chalkboard-teacher"></i> <span>Teachers</span>
                </a>
                
                <a href="courses_tea.php" class="nav-item">
                    <i class="fas fa-graduation-cap"></i> <span>Department</span>
                </a>
                
                <a href="attendanceAll_tea.php" class="nav-item">
                    <i class="fas fa-calendar-check"></i> <span>Attendance</span>
                </a>
                
                <div class="nav-bottom">
                    <a href="logout.php" class="nav-item">
                        <i class="fas fa-sign-out-alt"></i> <span>Logout</span>
                    </a>
                </div>
            </div>
        </div>

        <div class="main-content">
            <div class="top-bar">
                <div class="page-title"><h2>📊   Attendance Management Business</h2></div>
                <div><span class="badge bg-primary">Today: <?php echo date('d/m/Y'); ?></span></div>
                <a href="attendanceAll_tea.php" class="back-btn">
                    <i class="fas fa-arrow-left"></i> Back
                </a>
            </div>

            <!-- ========== Search Date Box ========== -->
            <div class="date-filter-box">
                <form method="GET" action="" class="row g-3 align-items-end">
                    <div class="col-md-8">
                        <label class="form-label fw-bold">🔍 Search Attendance by Date</label>
                        <input type="date" name="search_date" class="form-control" value="<?php echo htmlspecialchars($search_date); ?>">
                    </div>
                    <div class="col-md-4">
                        <button type="submit" class="btn btn-primary w-100"><i class="fas fa-search"></i> Show Report</button>
                    </div>
                </form>
                
                <?php if(isset($_GET['search_date'])): ?>
                <div class="mt-3 alert alert-info">
                    <strong>Search Results for <?php echo htmlspecialchars($search_date); ?>:</strong><br>
                    Total: <?php echo $search_count; ?> | 
                    Present: <?php echo $search_present; ?> | 
                    Absent: <?php echo $search_absent; ?> | 
                    Late: <?php echo $search_late; ?>
                </div> 
                <?php endif; ?>
            </div>

            <!-- ========== Statistics Cards (All Time) ========== -->
            <div class="stats-grid">
                <div class="stat-card" style="border-bottom-color: #8b5cf6;">
                    <div class="stat-icon"><i class="fas fa-database" style="color: #8b5cf6;"></i></div>
                    <div class="stat-label">Total All</div>
                    <div class="stat-number"><?php echo $total_all_count; ?></div>
                    <div class="stat-percent">Total records</div>
                </div>
                <div class="stat-card" style="border-bottom-color: #10b981;">
                    <div class="stat-icon"><i class="fas fa-chart-line" style="color: #10b981;"></i></div>
                    <div class="stat-label">Today's Present Rate</div>
                    <div class="stat-number">
                        <?php 
                        $present_rate = $today_count > 0 ? round(($present_count / $today_count) * 100) : 0;
                        echo $present_rate; ?>%
                    </div>
                    <div class="stat-percent"><?php echo $present_count; ?> / <?php echo $today_count; ?> present</div>
                </div>
            </div>
            
            <!-- ========== Year-based Statistics Cards ========== -->
            <div class="stats-grid">
                <!-- Year 1 Card -->
                <div class="year-card" style="border-bottom-color: #8b5cf6;">
                    <div class="year-title">📘 Year 1</div>
                    <div class="year-stats">
                        <div class="year-stat-item">
                            <span class="year-stat-label"><i class="fas fa-users"></i> Total Students:</span>
                            <span class="year-stat-value"><?php echo getValue($yearly_totals, 'year 1'); ?></span>
                        </div>
                        <div class="year-stat-item">
                            <span class="year-stat-label"><i class="fas fa-sun"></i> Morning (Active):</span>
                            <span class="year-stat-value"><?php echo getValue($yearly_morning_active, 'year 1'); ?></span>
                        </div>
                        <div class="year-stat-item">
                            <span class="year-stat-label"><i class="fas fa-moon"></i> Evening (Active):</span>
                            <span class="year-stat-value"><?php echo getValue($yearly_evening_active, 'year 1'); ?></span>
                        </div>
                        <div class="year-stat-item">
                            <span class="year-stat-label"><i class="fas fa-chart-line"></i> Attendance Rate:</span>
                            <span class="year-stat-value">
                                <?php 
                                $total_records = getValue($yearly_morning_active, 'year 1') + getValue($yearly_evening_active, 'year 1');
                                $total_students = getValue($yearly_totals, 'year 1');
                                $rate = $total_students > 0 ? round(($total_records / $total_students) * 100) : 0;
                                echo $rate . '%';
                                ?>
                            </span>
                        </div>
                    </div>
                </div>
                
                <!-- Year 2 Card -->
                <div class="year-card" style="border-bottom-color: #fc04ef;">
                    <div class="year-title">📗 Year 2</div>
                    <div class="year-stats">
                        <div class="year-stat-item">
                            <span class="year-stat-label"><i class="fas fa-users"></i> Total Students:</span>
                            <span class="year-stat-value"><?php echo getValue($yearly_totals, 'year 2'); ?></span>
                        </div>
                        <div class="year-stat-item">
                            <span class="year-stat-label"><i class="fas fa-sun"></i> Morning (Active):</span>
                            <span class="year-stat-value"><?php echo getValue($yearly_morning_active, 'year 2'); ?></span>
                        </div>
                        <div class="year-stat-item">
                            <span class="year-stat-label"><i class="fas fa-moon"></i> Evening (Active):</span>
                            <span class="year-stat-value"><?php echo getValue($yearly_evening_active, 'year 2'); ?></span>
                        </div>
                        <div class="year-stat-item">
                            <span class="year-stat-label"><i class="fas fa-chart-line"></i> Attendance Rate:</span>
                            <span class="year-stat-value">
                                <?php 
                                $total_records = getValue($yearly_morning_active, 'year 2') + getValue($yearly_evening_active, 'year 2');
                                $total_students = getValue($yearly_totals, 'year 2');
                                $rate = $total_students > 0 ? round(($total_records / $total_students) * 100) : 0;
                                echo $rate . '%';
                                ?>
                            </span>
                        </div>
                    </div>
                </div>
                
                <!-- Year 3 Card -->
                <div class="year-card" style="border-bottom-color: #1cebfa;">
                    <div class="year-title">📙 Year 3</div>
                    <div class="year-stats">
                        <div class="year-stat-item">
                            <span class="year-stat-label"><i class="fas fa-users"></i> Total Students:</span>
                            <span class="year-stat-value"><?php echo getValue($yearly_totals, 'year 3'); ?></span>
                        </div>
                        <div class="year-stat-item">
                            <span class="year-stat-label"><i class="fas fa-sun"></i> Morning (Active):</span>
                            <span class="year-stat-value"><?php echo getValue($yearly_morning_active, 'year 3'); ?></span>
                        </div>
                        <div class="year-stat-item">
                            <span class="year-stat-label"><i class="fas fa-moon"></i> Evening (Active):</span>
                            <span class="year-stat-value"><?php echo getValue($yearly_evening_active, 'year 3'); ?></span>
                        </div>
                        <div class="year-stat-item">
                            <span class="year-stat-label"><i class="fas fa-chart-line"></i> Attendance Rate:</span>
                            <span class="year-stat-value">
                                <?php 
                                $total_records = getValue($yearly_morning_active, 'year 3') + getValue($yearly_evening_active, 'year 3');
                                $total_students = getValue($yearly_totals, 'year 3');
                                $rate = $total_students > 0 ? round(($total_records / $total_students) * 100) : 0;
                                echo $rate . '%';
                                ?>
                            </span>
                        </div>
                    </div>
                </div>
                
                <!-- Year 4 Card -->
                <div class="year-card" style="border-bottom-color: #f89615;">
                    <div class="year-title">📕 Year 4</div>
                    <div class="year-stats">
                        <div class="year-stat-item">
                            <span class="year-stat-label"><i class="fas fa-users"></i> Total Students:</span>
                            <span class="year-stat-value"><?php echo getValue($yearly_totals, 'year 4'); ?></span>
                        </div>
                        <div class="year-stat-item">
                            <span class="year-stat-label"><i class="fas fa-sun"></i> Morning (Active):</span>
                            <span class="year-stat-value"><?php echo getValue($yearly_morning_active, 'year 4'); ?></span>
                        </div>
                        <div class="year-stat-item">
                            <span class="year-stat-label"><i class="fas fa-moon"></i> Evening (Active):</span>
                            <span class="year-stat-value"><?php echo getValue($yearly_evening_active, 'year 4'); ?></span>
                        </div>
                        <div class="year-stat-item">
                            <span class="year-stat-label"><i class="fas fa-chart-line"></i> Attendance Rate:</span>
                            <span class="year-stat-value">
                                <?php 
                                $total_records = getValue($yearly_morning_active, 'year 4') + getValue($yearly_evening_active, 'year 4');
                                $total_students = getValue($yearly_totals, 'year 4');
                                $rate = $total_students > 0 ? round(($total_records / $total_students) * 100) : 0;
                                echo $rate . '%';
                                ?>
                            </span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- ========== Weekly Chart ========== -->
            <?php if(!empty($weekly_dates)): ?>
            <div class="chart-container">
                <h5 class="mb-3"><i class="fas fa-chart-bar"></i> Weekly Attendance Trend (Last 7 Days)</h5>
                <canvas id="attendanceChart" height="100"></canvas>
            </div>
            <?php endif; ?>

              <!-- ========== Attendance Records Table ========== -->
            <div class="data-table-wrapper mt-4 mb-5">
                <div class="header-banner" style="border-radius: 20px 20px 0 0;">📋 Attendance Records</div>
                <div class="table-container">
                    <table class="table table-bordered mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>ID</th>
                                <th>Student ID</th>                              
                                <th>Attendance Date</th>
                                <th>Subject</th>
                                <th>Year</th>
                                <th>Shift</th>
                                <th>Status</th>
                                <th>Note</th>
                               
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (mysqli_num_rows($result) > 0): ?>
                                <?php while($row = mysqli_fetch_assoc($result)): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($row['id']); ?></td>
                                        <td><?php echo htmlspecialchars($row['student_id']); ?></td>                                      
                                        <td><?php echo htmlspecialchars($row['attendance_date']); ?></td>
                                        <td><?php echo htmlspecialchars($row['subject']); ?></td>
                                        <td><?php echo htmlspecialchars($row['year']); ?></td>
                                        <td><?php echo htmlspecialchars($row['shift']); ?></td>
                                        <td>
                                            <span class="badge bg-<?php 
                                                echo $row['status'] == 'Active' ? 'success' : 
                                                    ($row['status'] == 'A' ? 'danger' : 
                                                    ($row['status'] == 'P' ? 'warning' : 'secondary')); 
                                            ?>">
                                                <?php 
                                                $status_text = $row['status'] == 'Active' ? 'Present' : 
                                                              ($row['status'] == 'A' ? 'Absent' : 
                                                              ($row['status'] == 'P' ? 'Late' : $row['status']));
                                                echo htmlspecialchars($status_text);
                                                ?>
                                            </span>
                                        </td>
                                        <td><?php echo htmlspecialchars($row['note']); ?></td>
                                       
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
        const ctx = document.getElementById('attendanceChart');
        
        if(ctx) {
            const dates = <?php echo json_encode($weekly_dates); ?>;
            const presentData = <?php echo json_encode($weekly_present); ?>;
            const absentData = <?php echo json_encode($weekly_absent); ?>;
            const lateData = <?php echo json_encode($weekly_late); ?>;
            
            new Chart(ctx, {
                type: 'bar',
                data: {
                    labels: dates,
                    datasets: [
                        {
                            label: 'Present',
                            data: presentData,
                            backgroundColor: 'rgba(16, 185, 129, 0.7)',
                            borderColor: '#10b981',
                            borderWidth: 1
                        },
                        {
                            label: 'Absent',
                            data: absentData,
                            backgroundColor: 'rgba(239, 68, 68, 0.7)',
                            borderColor: '#ef4444',
                            borderWidth: 1
                        },
                        {
                            label: 'Late',
                            data: lateData,
                            backgroundColor: 'rgba(245, 158, 11, 0.7)',
                            borderColor: '#f59e0b',
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
                        },
                        tooltip: {
                            callbacks: {
                                label: function(context) {
                                    let label = context.dataset.label || '';
                                    let value = context.raw || 0;
                                    return `${label}: ${value} students`;
                                }
                            }
                        }
                    },
                    scales: {
                        y: {
                            beginAtZero: true,
                            ticks: {
                                stepSize: 1,
                                precision: 0
                            },
                            title: {
                                display: true,
                                text: 'Number of Students'
                            }
                        },
                        x: {
                            title: {
                                display: true,
                                text: 'Date'
                            }
                        }
                    }
                }
            });
        }
    </script>
</body>
</html>