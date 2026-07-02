<?php
include 'db.php';

// ===== GET SUBJECT FROM URL =====
$subject = isset($_GET['subject']) ? $_GET['subject'] : 'IT';

// ===== TABLE MAPPING =====
$subject_tables = [
    'IT' => 'attendantit',
    'Business' => 'attendantbusiness',
    'Civil Engineering' => 'attendantcivil',
    'Electronics' => 'attendantelectronic',
    'Electrical Engineering' => 'attendantelectrical'
];

// ===== SUBJECT DISPLAY NAMES =====
$subject_display = [
    'IT' => '💻 IT',
    'Business' => '📊 Business',
    'Civil Engineering' => '🏗️ Civil Engineering',
    'Electronics' => '⚡ Electronics',
    'Electrical Engineering' => '🔌 Electrical Engineering'
];

// Get table name for selected subject
$table_name = isset($subject_tables[$subject]) ? $subject_tables[$subject] : 'attendantit';
$display_name = isset($subject_display[$subject]) ? $subject_display[$subject] : $subject;

// ===== FETCH ATTENDANCE DATA =====
$sql = "SELECT * FROM $table_name ORDER BY id DESC";
$result = mysqli_query($conn, $sql);

// ===== TODAY'S DATE =====
$today = date('Y-m-d');

// ===== COUNT TODAY'S ATTENDANCE =====
$sql_today = "SELECT COUNT(*) as total FROM $table_name WHERE attendance_date = '$today'";
$result_today = mysqli_query($conn, $sql_today);
$today_count = mysqli_fetch_assoc($result_today)['total'] ?? 0;

// ===== COUNT BY STATUS FOR TODAY =====
$sql_present = "SELECT COUNT(*) as count FROM $table_name WHERE attendance_date = '$today' AND status = 'Active'";
$result_present = mysqli_query($conn, $sql_present);
$present_count = mysqli_fetch_assoc($result_present)['count'] ?? 0;

$sql_absent = "SELECT COUNT(*) as count FROM $table_name WHERE attendance_date = '$today' AND status = 'A'";
$result_absent = mysqli_query($conn, $sql_absent);
$absent_count = mysqli_fetch_assoc($result_absent)['count'] ?? 0;

$sql_late = "SELECT COUNT(*) as count FROM $table_name WHERE attendance_date = '$today' AND status = 'P'";
$result_late = mysqli_query($conn, $sql_late);
$late_count = mysqli_fetch_assoc($result_late)['count'] ?? 0;

// ===== SEARCH BY DATE =====
$search_date = isset($_GET['search_date']) ? $_GET['search_date'] : $today;
$sql_search = "SELECT COUNT(*) as total FROM $table_name WHERE attendance_date = '$search_date'";
$result_search = mysqli_query($conn, $sql_search);
$search_count = mysqli_fetch_assoc($result_search)['total'] ?? 0;

$sql_search_present = "SELECT COUNT(*) as count FROM $table_name WHERE attendance_date = '$search_date' AND status = 'Active'";
$result_search_present = mysqli_query($conn, $sql_search_present);
$search_present = mysqli_fetch_assoc($result_search_present)['count'] ?? 0;

$sql_search_absent = "SELECT COUNT(*) as count FROM $table_name WHERE attendance_date = '$search_date' AND status = 'A'";
$result_search_absent = mysqli_query($conn, $sql_search_absent);
$search_absent = mysqli_fetch_assoc($result_search_absent)['count'] ?? 0;

$sql_search_late = "SELECT COUNT(*) as count FROM $table_name WHERE attendance_date = '$search_date' AND status = 'P'";
$result_search_late = mysqli_query($conn, $sql_search_late);
$search_late = mysqli_fetch_assoc($result_search_late)['count'] ?? 0;

// ===== TOTAL COUNT =====
$sql_total_all = "SELECT COUNT(*) as total FROM $table_name";
$result_total_all = mysqli_query($conn, $sql_total_all);
$total_all_count = mysqli_fetch_assoc($result_total_all)['total'] ?? 0;

// ===== YEAR-BASED STATISTICS =====
$yearly_totals = [];
$yearly_morning_active = [];
$yearly_evening_active = [];

// Get total per year
$sql_yearly_total = "SELECT year, COUNT(*) as total FROM $table_name GROUP BY year";
$result_yearly_total = mysqli_query($conn, $sql_yearly_total);
while ($row = mysqli_fetch_assoc($result_yearly_total)) {
    $yearly_totals[$row['year']] = $row['total'];
}

// Get morning attendance
$sql_morning = "SELECT year, COUNT(*) as total FROM $table_name WHERE shift = 'morning' AND status = 'Active' GROUP BY year";
$result_morning = mysqli_query($conn, $sql_morning);
while ($row = mysqli_fetch_assoc($result_morning)) {
    $yearly_morning_active[$row['year']] = $row['total'];
}

// Get evening attendance
$sql_evening = "SELECT year, COUNT(*) as total FROM $table_name WHERE shift = 'Evening' AND status = 'Active' GROUP BY year";
$result_evening = mysqli_query($conn, $sql_evening);
while ($row = mysqli_fetch_assoc($result_evening)) {
    $yearly_evening_active[$row['year']] = $row['total'];
}

function getValue($array, $key) {
    return isset($array[$key]) ? $array[$key] : 0;
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
    <title><?php echo $display_name; ?> - Attendance</title>
    <style>
        .dropdown-container { margin-bottom: 10px; }
        .dropdown-btn { display: flex; justify-content: space-between; align-items: center; cursor: pointer; }
        .dropdown-menus { display: none; padding-left: 15px; margin-top: 5px; }
        .sub-menu { font-size: 14px; padding: 10px 15px; margin-bottom: 5px; background: rgba(88, 30, 248, 0.61); }
        .sub-menu:hover { background: rgba(110, 41, 238, 0.6); }
        .dropdown-icon { transition: 0.3s; }
        
        .table-container { max-height: 400px; overflow-y: auto; border: 1px solid #dee2e6; border-radius: 8px; }
        .table-container thead th { position: sticky; top: 0; background-color: #f8f9fa; z-index: 1; }
        
        /* Subject Navigation */
        .subject-nav {
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
            margin-bottom: 25px;
            background: white;
            padding: 15px 20px;
            border-radius: 20px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.06);
        }
        .subject-nav a {
            padding: 8px 20px;
            border-radius: 30px;
            text-decoration: none;
            color: #4a5b7a;
            font-weight: 500;
            border: 2px solid #e2e8f0;
            transition: 0.3s;
        }
        .subject-nav a:hover { border-color: #667eea; color: #667eea; }
        .subject-nav a.active { background: linear-gradient(135deg, #667eea, #764ba2); color: white; border-color: #667eea; }
        
        .stats-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(180px, 1fr)); gap: 20px; margin-bottom: 30px; }
        .stat-card { background: white; border-radius: 20px; padding: 20px; box-shadow: 0 5px 15px rgba(0,0,0,0.08); transition: transform 0.3s ease; text-align: center; border-bottom: 4px solid; }
        .stat-card:hover { transform: translateY(-5px); }
        .stat-card .stat-icon { font-size: 2.5rem; margin-bottom: 10px; }
        .stat-card .stat-label { color: #64748b; font-size: 0.85rem; text-transform: uppercase; letter-spacing: 1px; margin-bottom: 8px; }
        .stat-card .stat-number { font-size: 2rem; font-weight: bold; color: #1e293b; margin-bottom: 5px; }
        
        .year-card { background: white; border-radius: 20px; padding: 20px; box-shadow: 0 5px 15px rgba(0,0,0,0.08); transition: transform 0.3s ease; border-bottom: 4px solid; text-align: left; }
        .year-card:hover { transform: translateY(-5px); }
        .year-title { font-size: 1.5rem; font-weight: bold; margin-bottom: 15px; padding-bottom: 10px; border-bottom: 2px solid #e2e8f0; text-align: center; }
        .year-stat-item { display: flex; justify-content: space-between; padding: 8px 0; border-bottom: 1px dashed #e2e8f0; }
        .year-stat-label { color: #64748b; font-weight: 500; }
        .year-stat-value { font-weight: bold; color: #1e293b; }
        
        .date-filter-box { background: white; border-radius: 15px; padding: 20px; margin-bottom: 30px; box-shadow: 0 5px 15px rgba(0,0,0,0.08); }
        .back-btn { display: inline-flex; align-items: center; gap: 8px; padding: 8px 20px; background: linear-gradient(135deg, #667eea, #764ba2); color: white; border-radius: 30px; text-decoration: none; font-weight: 500; transition: 0.3s; }
        .back-btn:hover { transform: translateX(-3px); color: white; box-shadow: 0 4px 12px rgba(102, 126, 234, 0.4); }
        .header-banner { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; padding: 18px 25px; font-size: 20px; font-weight: 600; }
        .form-container { background: white; border-radius: 20px; box-shadow: 0 10px 30px rgba(0,0,0,0.08); margin-bottom: 40px; overflow: hidden; }
        .btn-primary { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); border: none; padding: 10px 25px; border-radius: 10px; font-weight: 600; }
        .btn-primary:hover { opacity: 0.9; color: white; }
        
        * { margin: 0; padding: 0; box-sizing: border-box; }
        a { text-decoration: none; }
        body { font-family: "Inter", sans-serif; background: #c5e1fc; color: #0f172a; }
        .container { display: flex; min-height: 100vh; max-width: 100%; }
        .sidebar { 
            width: 300px; 
            background: linear-gradient(90deg, rgba(117,82,243,1) 19%, rgba(64,24,157,1) 95%);
            color: #e2e8f0; 
            flex-shrink: 0; 
            position: sticky; 
            top: 0; 
            height: 100vh; 
            overflow-y: auto; 
            z-index: 10;
            margin-left: -12px;
        }
        .sidebar-header { padding: 28px 24px; border-bottom: 1px solid rgba(255,255,255,0.1); text-align: center; }
        .sidebar-header img { width: 100px; height: 100px; border-radius: 50%; margin: auto; display: block; border: 4px solid white; object-fit: cover; }
        .sidebar-header h1 { font-size: 1.8rem; font-weight: 700; color: white; margin-top: 10px; }
        .nav-menu { flex: 1; padding: 0 16px; }
        .nav-item { display: flex; align-items: center; gap: 15px; padding: 12px 16px; margin-bottom: 8px; border-radius: 14px; transition: 0.3s; color: #cbd5e6; }
        .nav-item:hover { background: rgba(255,255,255,0.1); color: white; }
        .nav-item.active { background: #fd0054; color: white; }
        .main-content { flex: 1; padding: 28px 32px; background: #f8fafc; overflow-y: auto; height: 100vh; }
        .top-bar { display: flex; justify-content: space-between; align-items: center; padding: 12px 24px; background: white; border-radius: 60px; box-shadow: 0 1px 3px rgba(0,0,0,0.05); margin-bottom: 28px; }
        .page-title h2 { font-size: 1.3rem; font-weight: 600; color: #1e293b; margin: 0; }
        
        @media (max-width: 768px) {
            .sidebar { width: 80px; }
            .sidebar-header h1, .sidebar-header p, .nav-item span { display: none; }
            .main-content { padding: 15px; }
        }
    </style>
</head>
<body>
<div class="container">
    <!-- SIDEBAR -->
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
                    <div><i class="fas fa-user-graduate"></i> <span>Students</span></div>
                    <i class="fas fa-chevron-down dropdown-icon" id="dropdownIcon"></i>
                </div>
                <div class="dropdown-menus" id="studentDropdown">
                    <a href="student.php" class="nav-item sub-menu"><i class="fas fa-users"></i> <span>Student List</span></a>
                    <a href="stutype.php" class="nav-item sub-menu"><i class="fas fa-tags"></i> <span>Student Type</span></a>
                    <a href="stuviwe.php" class="nav-item sub-menu"><i class="fas fa-eye"></i> <span>Student View</span></a>
                    <a href="grade.php" class="nav-item sub-menu"><i class="fas fa-layer-group"></i> <span>Student Grades</span></a>
                    <a href="score.php" class="nav-item sub-menu"><i class="fas fa-chart-line"></i> <span>Student Scores</span></a>
                    <a href="student_payments.php" class="nav-item sub-menu"><i class="fas fa-money-bill-wave"></i> <span>Student Payments</span></a>
                    <a href="card_stuIT.php" class="nav-item sub-menu"><i class="fas fa-id-card"></i> <span>ID Card</span></a>
                </div>
            </div>
            <a href="teacher.php" class="nav-item"><i class="fas fa-chalkboard-teacher"></i> <span>Teachers</span></a>
            <a href="Courses.php" class="nav-item"><i class="fas fa-graduation-cap"></i> <span>Department</span></a>
            <a href="schedule.php" class="nav-item"><i class="fas fa-calendar-alt"></i> <span>Schedule class</span></a>
            <a href="Employees.php" class="nav-item"><i class="fas fa-user-friends"></i> <span>Employees</span></a>
            <a href="#" class="nav-item active"><i class="fas fa-calendar-check"></i> <span>Attendance</span></a>
            <div class="nav-bottom">
                <a href="logout.php" class="nav-item"><i class="fas fa-sign-out-alt"></i> <span>Logout</span></a>
            </div>
        </div>
    </div>

    <div class="main-content">
        <!-- TOP BAR -->
        <div class="top-bar">
            <div class="page-title"><h2><?php echo $display_name; ?> Attendance</h2></div>
            <div><span class="badge bg-primary">Today: <?php echo date('d/m/Y'); ?></span></div>
            <a href="attendanceAll.php" class="back-btn"><i class="fas fa-arrow-left"></i> Back</a>
        </div>

        <!-- ===== SUBJECT NAVIGATION ===== -->
        <div class="subject-nav">
            <?php foreach ($subject_display as $key => $label): ?>
                <a href="attendance_subject.php?subject=<?php echo urlencode($key); ?>" 
                   class="<?php echo $subject == $key ? 'active' : ''; ?>">
                    <?php echo $label; ?>
                </a>
            <?php endforeach; ?>
        </div>

        <!-- ===== SEARCH DATE ===== -->
        <div class="date-filter-box">
            <form method="GET" action="" class="row g-3 align-items-end">
                <input type="hidden" name="subject" value="<?php echo htmlspecialchars($subject); ?>">
                <div class="col-md-8">
                    <label class="form-label fw-bold">🔍 Search by Date</label>
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

        <!-- ===== STATS ===== -->
        <div class="stats-grid">
            <div class="stat-card" style="border-bottom-color: #8b5cf6;">
                <div class="stat-icon"><i class="fas fa-database" style="color: #8b5cf6;"></i></div>
                <div class="stat-label">Total Records</div>
                <div class="stat-number"><?php echo $total_all_count; ?></div>
            </div>
            <div class="stat-card" style="border-bottom-color: #10b981;">
                <div class="stat-icon"><i class="fas fa-user-check" style="color: #10b981;"></i></div>
                <div class="stat-label">Today's Present</div>
                <div class="stat-number"><?php echo $present_count; ?></div>
            </div>
            <div class="stat-card" style="border-bottom-color: #ef4444;">
                <div class="stat-icon"><i class="fas fa-user-times" style="color: #ef4444;"></i></div>
                <div class="stat-label">Today's Absent</div>
                <div class="stat-number"><?php echo $absent_count; ?></div>
            </div>
            <div class="stat-card" style="border-bottom-color: #f59e0b;">
                <div class="stat-icon"><i class="fas fa-clock" style="color: #f59e0b;"></i></div>
                <div class="stat-label">Today's Late</div>
                <div class="stat-number"><?php echo $late_count; ?></div>
            </div>
        </div>

        <!-- ===== YEAR CARDS ===== -->
        <div class="stats-grid">
            <?php 
            $year_colors = ['year 1' => '#8b5cf6', 'year 2' => '#fc04ef', 'year 3' => '#1cebfa', 'year 4' => '#f89615'];
            $year_icons = ['year 1' => '📘', 'year 2' => '📗', 'year 3' => '📙', 'year 4' => '📕'];
            foreach ($year_colors as $year => $color): 
            ?>
            <div class="year-card" style="border-bottom-color: <?php echo $color; ?>;">
                <div class="year-title"><?php echo $year_icons[$year]; ?> <?php echo ucfirst($year); ?></div>
                <div class="year-stats">
                    <div class="year-stat-item">
                        <span class="year-stat-label"><i class="fas fa-users"></i> Total:</span>
                        <span class="year-stat-value"><?php echo getValue($yearly_totals, $year); ?></span>
                    </div>
                    <div class="year-stat-item">
                        <span class="year-stat-label"><i class="fas fa-sun"></i> Morning:</span>
                        <span class="year-stat-value"><?php echo getValue($yearly_morning_active, $year); ?></span>
                    </div>
                    <div class="year-stat-item">
                        <span class="year-stat-label"><i class="fas fa-moon"></i> Evening:</span>
                        <span class="year-stat-value"><?php echo getValue($yearly_evening_active, $year); ?></span>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>

        <!-- ===== ATTENDANCE TABLE ===== -->
        <div class="data-table-wrapper mt-4 mb-5">
            <div class="header-banner" style="border-radius: 20px 20px 0 0;">📋 Attendance Records - <?php echo $display_name; ?></div>
            <div class="table-container">
                <table class="table table-bordered mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>ID</th>
                            <th>Student ID</th>
                            <th>Date</th>
                            <th>Subject</th>
                            <th>Year</th>
                            <th>Shift</th>
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
                                    <td><?php echo htmlspecialchars($row['student_id']); ?></td>
                                    <td><?php echo htmlspecialchars($row['attendance_date']); ?></td>
                                    <td><?php echo htmlspecialchars($row['subject']); ?></td>
                                    <td><?php echo htmlspecialchars($row['year']); ?></td>
                                    <td><?php echo htmlspecialchars($row['shift']); ?></td>
                                    <td>
                                        <span class="badge bg-<?php 
                                            $status = $row['status'];
                                            if ($status == 'Active') echo 'success';
                                            elseif ($status == 'A') echo 'danger';
                                            elseif ($status == 'P') echo 'warning';
                                            else echo 'secondary';
                                        ?>">
                                            <?php 
                                            if ($status == 'Active') echo 'Present';
                                            elseif ($status == 'A') echo 'Absent';
                                            elseif ($status == 'P') echo 'Late';
                                            else echo htmlspecialchars($status);
                                            ?>
                                        </span>
                                    </td>
                                    <td><?php echo htmlspecialchars($row['note'] ?? '-'); ?></td>
                                    <td>
                                        <a href='edit_Attendance.php?id=<?php echo $row['id']; ?>&table=<?php echo $table_name; ?>' class='btn btn-warning btn-sm'><i class='fas fa-edit'></i></a>
                                        <a href='delete_Attendance.php?id=<?php echo $row['id']; ?>&table=<?php echo $table_name; ?>' class='btn btn-danger btn-sm' onclick='return confirm("Are you sure?")'><i class='fas fa-trash'></i></a>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr><td colspan="9" class="text-center py-3">No attendance records found for <?php echo $display_name; ?></td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- ===== ATTENDANCE FORM ===== -->
        <div class="form-container">
            <div class="header-banner">✍️ Register Attendance - <?php echo $display_name; ?></div>
            <form action="process_attendance.php" method="POST" class="form-body" style="padding: 25px;">
                <input type="hidden" name="table_name" value="<?php echo $table_name; ?>">
                <input type="hidden" name="subject" value="<?php echo htmlspecialchars($subject); ?>">
                
                <div class="row g-3">
                    <div class="col-md-4">
                        <label class="form-label">Student ID:</label>
                        <input type="text" name="student_id" class="form-control" placeholder="Enter Student ID" required>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Date:</label>
                        <input type="date" name="attendance_date" class="form-control" value="<?php echo $today; ?>" required>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Year:</label>
                        <select name="year" class="form-select" required>
                            <option value="year 1">Year 1</option>
                            <option value="year 2">Year 2</option>
                            <option value="year 3">Year 3</option>
                            <option value="year 4">Year 4</option>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Shift:</label>
                        <select name="shift" class="form-select" required>
                            <option value="morning">Morning</option>
                            <option value="Evening">Evening</option>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Status:</label>
                        <select name="status" class="form-select" required>
                            <option value="Active">✅ Present (Active)</option>
                            <option value="A">❌ Absent (A)</option>
                            <option value="P">⏰ Late (P)</option>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Note:</label>
                        <input type="text" name="note" class="form-control" placeholder="Optional">
                    </div>
                    <div class="col-12 mt-3">
                        <button type="submit" class="btn btn-primary px-4"><i class="fas fa-save"></i> Save</button>
                        <button type="reset" class="btn btn-secondary px-4"><i class="fas fa-undo"></i> Clear</button>
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
function toggleDropdown() {
    let menu = document.getElementById("studentDropdown");
    let icon = document.getElementById("dropdownIcon");
    if (menu.style.display === "block") {
        menu.style.display = "none";
        icon.style.transform = "rotate(0deg)";
    } else {
        menu.style.display = "block";
        icon.style.transform = "rotate(180deg)";
    }
}
</script>
</body>
</html>