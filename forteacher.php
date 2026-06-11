<?php
        include "db.php";
        
        // ការពារ user ប្តូរ link
       if(!isset($_SESSION['id'])){
           header("Location: login.php");
            exit();
         }
        
        // ទាញយកព័ត៌មាន teacher ពី search
        $teacher_info = null;
        $teacher_department = null;
        $total_students_by_college = 0;
        $college_name = '';
        $is_searching = false;
        
        $search_id = isset($_GET['search_id']) ? mysqli_real_escape_string($conn, $_GET['search_id']) : '';
        
        if(!empty($search_id)) {
            $is_searching = true;
            // Search teacher by ID or Name
            $sql_teacher = mysqli_query($conn, "SELECT * FROM teachers WHERE id = '$search_id' OR name LIKE '%$search_id%'");
            $teacher_info = mysqli_fetch_assoc($sql_teacher);
            
            if($teacher_info) {
                // Get teacher's department/college
                $teacher_department = isset($teacher_info['department']) ? $teacher_info['department'] : 'it';
                $college_name = $teacher_department;
                
                // Count total students by teacher's college from students table
                $sql_students_count = mysqli_query($conn, "SELECT COUNT(*) as total FROM students WHERE college = '$teacher_department'");
                $total_students_by_college = mysqli_fetch_assoc($sql_students_count)['total'];
            }
        }
        
        // ទាញយក total students ទាំងអស់
        $sql_total_students = mysqli_query($conn, "SELECT COUNT(*) as total FROM students");
        $total_students = mysqli_fetch_assoc($sql_total_students)['total'];
        
        // ទាញយក total colleges
        $sql_total_college = mysqli_query($conn, "SELECT COUNT(DISTINCT college) as total FROM students");
        $total_colleges = mysqli_fetch_assoc($sql_total_college)['total'];
        
        // ទាញយក teacher's info for display in top bar
        $teacher_display_name = "";
        if($teacher_info) {
            $teacher_display_name = $teacher_info['name'];
        } else if(isset($_SESSION['name'])) {
            $teacher_display_name = $_SESSION['name'];
        }
        
        // Get all colleges/distinct departments from students table for dropdown/chart
        $sql_colleges = mysqli_query($conn, "SELECT DISTINCT college FROM students WHERE college IS NOT NULL AND college != ''");
        $colleges_list = [];
        while($col = mysqli_fetch_assoc($sql_colleges)) {
            $colleges_list[] = $col['college'];
        }

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <title>KRaksa Education Suite - Teacher Dashboard</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        a{ text-decoration: none; }
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
        .top-bar { display: flex; justify-content: space-between; align-items: center; padding: 12px 24px; background: white; border-radius: 60px; box-shadow: 0 1px 3px rgba(0,0,0,0.05); margin-bottom: 28px; flex-wrap: wrap; gap: 15px; }
        .page-title h2 { font-size: 1.3rem; font-weight: 600; color: #1e293b; margin: 0; }
        
        /* Search Container */
        .search-container {
            display: flex;
            gap: 10px;
            align-items: center;
        }
        
        /* Search Bar Styling */
        .search-box { display: flex; gap: 5px; }
        .search-box input { 
            border-radius: 10px 0 0 10px; 
            border: 1px solid #cbd5e1; 
            padding: 8px 15px; 
            font-size: 14px; 
            width: 250px;
            outline: none;
            transition: all 0.3s ease;
        }
        .search-box input:focus {
            border-color: #4f46e5;
            box-shadow: 0 0 0 2px rgba(79, 70, 229, 0.2);
        }
        .search-box button { 
            border-radius: 0 10px 10px 0; 
            background: #4f46e5; 
            color: white; 
            border: none; 
            padding: 8px 18px; 
            font-size: 14px; 
            transition: 0.2s;
            cursor: pointer;
        }
        .search-box button:hover { 
            background: #3b35b3; 
        }
        
        /* Cancel Search Button */
        .cancel-search-btn {
            background: #ef4444;
            color: white;
            border: none;
            padding: 8px 18px;
            border-radius: 10px;
            font-size: 14px;
            cursor: pointer;
            transition: 0.2s;
            display: inline-flex;
            align-items: center;
            gap: 8px;
        }
        .cancel-search-btn:hover {
            background: #dc2626;
            transform: translateY(-2px);
        }
        
        /* Teacher Info Card */
        .teacher-info-card {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border-radius: 15px;
            padding: 15px 25px;
            margin-bottom: 20px;
            color: white;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
            animation: slideDown 0.5s ease;
        }
        
        /* College Stats Card */
        .college-stats-card {
            background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);
            border-radius: 15px;
            padding: 15px 25px;
            margin-bottom: 20px;
            color: white;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
            animation: slideDown 0.5s ease;
        }
        
        @keyframes slideDown {
            from {
                opacity: 0;
                transform: translateY(-20px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
        
        .teacher-info-card h4, .college-stats-card h4 {
            margin: 0 0 10px 0;
            font-size: 1.1rem;
            opacity: 0.9;
        }
        .teacher-info-card h3 {
            margin: 0;
            font-size: 1.5rem;
            font-weight: 600;
        }
        .teacher-info-card .details, .college-stats-card .details {
            display: flex;
            gap: 20px;
            margin-top: 10px;
            flex-wrap: wrap;
        }
        .teacher-info-card .details span, .college-stats-card .details span {
            font-size: 0.9rem;
            opacity: 0.9;
        }
        .college-stats-card .stats-number {
            font-size: 2rem;
            font-weight: bold;
            margin: 10px 0;
        }
        
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
            min-width: 200px;
            box-shadow: 0 10px 25px rgba(0,0,0,0.1);
            transition: transform 0.3s ease;
            color:black;
            background: white;
            border-bottom: 8px solid;
            text-align: left;
        }
        .kpi-card:hover {
            transform: translateY(-5px);
        }
        
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
            font-size: 30px; 
            font-weight: bold; 
            margin-bottom: 5px; 
        }
        
        .chart-box {
            background: white;
            padding: 25px;
            border-radius: 20px;
            box-shadow: 0 5px 20px rgba(0,0,0,0.05);
            margin-top: 20px;
        }
        
        .stats-row {
            display: flex;
            gap: 20px;
            margin-top: 20px;
        }
        
        .stats-col {
            flex: 1;
        }
        
        canvas {
            max-height: 400px;
            width: 100%;
        }
        
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
        
        .table {
            font-size: 13px;
            margin-top: 10px;
        }
        .table th {
            background: #f1f5f9;
            font-weight: 600;
        }
        
        .alert {
            padding: 12px 20px;
            border-radius: 10px;
            margin-bottom: 20px;
        }
        .alert-warning {
            background: #fef3c7;
            color: #92400e;
            border-left: 4px solid #f59e0b;
        }
        .alert-info {
            background: #dbeafe;
            color: #1e40af;
            border-left: 4px solid #3b82f6;
        }
        
        @media (max-width: 768px) {
            .sidebar { width: 80px; }
            .sidebar-header h1, .sidebar-header p, .nav-item span { display: none; }
            .nav-item { justify-content: center; }
            .main-content { padding: 15px; }
            .kpi-card { min-width: 150px; }
            .kpi-number { font-size: 24px; }
            .stats-row { flex-direction: column; }
            .search-box input { width: 150px; }
        }
    </style>
</head>
<body>

<div class="container">
    <div class="sidebar">
        <div class="sidebar-header">
            <img src="https://i.pinimg.com/1200x/0c/34/fe/0c34fe7dd3df6d5a90703a3e871db5ef.jpg" alt="Logo" />
            <h1>Teacher</h1>
            <p>Education Suite</p>
        </div>
        <div class="nav-menu">
            <a href="/" class="nav-item active" data-page="dashboard"><i class="fas fa-tachometer-alt"></i> <span>Main Dashboard</span></a>
            
            <div class="dropdown-container">
                <div class="nav-item dropdown-btn" onclick="toggleDropdown()">
                    <div>
                        <i class="fas fa-user-graduate"></i>
                        <span>Students</span>
                    </div>
                    <i class="fas fa-chevron-down dropdown-icon" id="dropdownIcon"></i>
                </div>
                <div class="dropdown-menus" id="studentDropdown">
                    <a href="list_for_teacher.php" class="nav-item sub-menu"><i class="fas fa-users"></i><span>Student List</span></a>
                    <a href="stuviwe.php" class="nav-item sub-menu"><i class="fas fa-eye"></i><span>Student View</span></a>
                    <a href="score.php" class="nav-item sub-menu"><i class="fas fa-chart-line"></i><span>Student Scores</span></a>
                </div>
            </div>
            

            <a href="courses_tea.php" class="nav-item" data-page="courses"><i class="fas fa-graduation-cap"></i> <span>Department</span></a>
            <a href="attendance_top_teacher.php" class="nav-item" data-page="attendance"><i class="fas fa-calendar-check"></i> <span>Attendance</span></a>
            
            <div class="nav-bottom">
                <a href="logout.php" class="nav-item" style="padding-left:8px;">
                    <i class="fas fa-sign-out-alt"></i> <span>Logout</span>
                </a>
            </div>
        </div>
    </div>

    <div class="main-content">
        <div class="top-bar">
            <div class="page-title"><h2 id="dynamicTitle">Teacher Dashboard Overview</h2></div>
            <div class="date-time" id="currentDateTime"></div>
            <!-- Search Bar with Cancel Button -->
            <div class="search-container">
                <form method="GET" action="" class="search-box" id="searchForm">
                    <input type="text" name="search_id" id="searchInput" placeholder="Search Teacher by ID or Name..." value="<?php echo isset($_GET['search_id']) ? htmlspecialchars($_GET['search_id']) : ''; ?>">
                    <button type="submit"><i class="fas fa-search"></i> Search</button>
                </form>
                <?php if($is_searching): ?>
                <button type="button" class="cancel-search-btn" onclick="cancelSearch()">
                    <i class="fas fa-times"></i> Cancel
                </button>
                <?php endif; ?>
            </div>
        </div>
        
        <!-- Teacher Information Display Card -->
        <?php if($teacher_info): ?>
        <div class="teacher-info-card">
            <h4><i class="fas fa-chalkboard-teacher"></i> Teacher Information</h4>
            <h3><?php echo htmlspecialchars($teacher_info['name']); ?></h3>
            <div class="details">
                <span><i class="fas fa-id-card"></i> ID: <?php echo htmlspecialchars($teacher_info['id']); ?></span>
                <span><i class="fas fa-envelope"></i> Email: <?php echo htmlspecialchars($teacher_info['email'] ?? 'N/A'); ?></span>
                <span><i class="fas fa-phone"></i> Phone: <?php echo htmlspecialchars($teacher_info['phone'] ?? 'N/A'); ?></span>
                <span><i class="fas fa-graduation-cap"></i> College/Department: <?php echo htmlspecialchars($teacher_info['department'] ?? 'IT'); ?></span>
                <span><i class="fas fa-calendar"></i> Join Date: <?php echo htmlspecialchars($teacher_info['join_date'] ?? 'N/A'); ?></span>
            </div>
        </div>
        
        <!-- College/Department Student Statistics -->
        <div class="college-stats-card">
            <h4><i class="fas fa-university"></i> College Statistics: <?php echo strtoupper(htmlspecialchars($college_name)); ?></h4>
            <div class="stats-number"><?php echo $total_students_by_college; ?></div>
            <div class="stats-label">Total Students in <?php echo htmlspecialchars($college_name); ?> College</div>
            <small><i class="fas fa-info-circle"></i> From students table where college = '<?php echo htmlspecialchars($college_name); ?>'</small>
        </div>
        <?php elseif($is_searching && !$teacher_info): ?>
        <div class="alert alert-warning">
            <i class="fas fa-exclamation-triangle"></i> No teacher found with ID/Name: <strong><?php echo htmlspecialchars($search_id); ?></strong>
        </div>
        <?php endif; ?>

        <!-- KPI Cards Row 1 - FIXED: Show different stats based on search state -->
        <div class="kpi-row">
            <?php if($is_searching && $teacher_info): ?>
                <!-- When searching and teacher found - Show college specific stats -->
                <div class="kpi-card" style="border-bottom-color: #4f46e5;">
                    <div class="kpi-title"><i class="fas fa-users"></i> Students in <?php echo strtoupper(htmlspecialchars($college_name)); ?> College</div>
                    <div class="kpi-number"><?php echo $total_students_by_college; ?></div>
                    <small>Students from college: <?php echo htmlspecialchars($college_name); ?></small>
                </div>
                <div class="kpi-card" style="border-bottom-color: #10b981;">
                    <div class="kpi-title"><i class="fas fa-book"></i> Total Colleges</div>
                    <div class="kpi-number"><?php echo $total_colleges; ?></div>
                    <small>All colleges in system</small>
                </div>
                <div class="kpi-card" style="border-bottom-color: #f59e0b;">
                    <div class="kpi-title"><i class="fas fa-chalkboard-teacher"></i> Today's Classes</div>
                    <div class="kpi-number" id="teachersCount">0</div>
                    <small>Active teachers today</small>
                </div>
            <?php elseif($is_searching && !$teacher_info): ?>
                <!-- When searching and teacher NOT found - Show total stats with warning -->
                <div class="kpi-card" style="border-bottom-color: #4f46e5;">
                    <div class="kpi-title"><i class="fas fa-users"></i> Total Students (All)</div>
                    <div class="kpi-number"><?php echo $total_students; ?></div>
                    <small>All students in system</small>
                </div>
                <div class="kpi-card" style="border-bottom-color: #10b981;">
                    <div class="kpi-title"><i class="fas fa-book"></i> Total Colleges</div>
                    <div class="kpi-number"><?php echo $total_colleges; ?></div>
                    <small>All colleges</small>
                </div>
                <div class="kpi-card" style="border-bottom-color: #f59e0b;">
                    <div class="kpi-title"><i class="fas fa-chalkboard-teacher"></i> Today's Classes</div>
                    <div class="kpi-number" id="teachersCount">0</div>
                    <small>Active teachers today</small>
                </div>
            <?php else: ?>
                <!-- Default view (no search) - Show total stats -->
                <div class="kpi-card" style="border-bottom-color: #4f46e5;">
                    <div class="kpi-title"><i class="fas fa-users"></i> Total Students (All)</div>
                    <div class="kpi-number"><?php echo $total_students; ?></div>
                    <small>All students in system</small>
                </div>
                <div class="kpi-card" style="border-bottom-color: #10b981;">
                    <div class="kpi-title"><i class="fas fa-book"></i> Total Colleges</div>
                    <div class="kpi-number"><?php echo $total_colleges; ?></div>
                    <small>All colleges</small>
                </div>
                <div class="kpi-card" style="border-bottom-color: #f59e0b;">
                    <div class="kpi-title"><i class="fas fa-chalkboard-teacher"></i> Today's Classes</div>
                    <div class="kpi-number" id="teachersCount">0</div>
                    <small>Active teachers today</small>
                </div>
            <?php endif; ?>
        </div>
        
        <!-- Class Schedule Card - FIXED: Show schedule based on search -->
        <div class="kpi-row">
            <div class="kpi-card" style="border-bottom-color: #ef553b; width: 100%;">
                <div class="kpi-title">
                    <i class="fas fa-calendar-alt"></i> 
                    <?php if($is_searching && $teacher_info): ?>
                        Class Schedule for <?php echo htmlspecialchars($teacher_info['department']); ?> Department
                    <?php else: ?>
                        All Class Schedules
                    <?php endif; ?>
                </div>
                <small>
                    <?php if($is_searching && $teacher_info): ?>
                        <i class="fas fa-filter"></i> Showing schedule for department: <?php echo htmlspecialchars($teacher_info['department']); ?>
                    <?php elseif($is_searching && !$teacher_info): ?>
                        <i class="fas fa-exclamation-triangle"></i> No teacher found. Showing all schedules.
                    <?php else: ?>
                        <i class="fas fa-info-circle"></i> Showing all schedules (Search for a teacher to filter by department)
                    <?php endif; ?>
                </small>
                <div class="table-responsive">
                    <table class="table table-bordered">
                        <thead>
                            <tr>
                                <th>Time Start</th>
                                <th>Time End</th>
                                <th>Subject</th>
                                <th>Department</th>
                                <th>Classroom</th>
                                <th>Shift</th>
                                <th>Year</th>
                                <th>Semester</th>
                                <th>Date</th>
                            </tr>
                        </thead>
                        <tbody>
                        <?php
                        // If teacher has department, show schedule for that department, otherwise show all
                        $schedule_query = "SELECT * FROM schedule_class";
                        if($teacher_department) {
                            $schedule_query .= " WHERE department = '$teacher_department'";
                        }
                        $schedule_query .= " ORDER BY date DESC, time_star ASC LIMIT 10";
                        
                        $sql = mysqli_query($conn, $schedule_query);
                        if(mysqli_num_rows($sql) > 0){
                            while($row = mysqli_fetch_assoc($sql)){
                        ?>
                        <tr>
                            <td><?= htmlspecialchars($row['time_star'] ?? ''); ?></td>
                            <td><?= htmlspecialchars($row['time_end'] ?? ''); ?></td>
                            <td><?= htmlspecialchars($row['subject'] ?? ''); ?></td>
                            <td><?= htmlspecialchars($row['department'] ?? ''); ?></td>
                            <td><?= htmlspecialchars($row['class'] ?? ''); ?></td>
                            <td><?= htmlspecialchars($row['shift'] ?? ''); ?></td>
                            <td><?= htmlspecialchars($row['year'] ?? ''); ?></td>
                            <td><?= htmlspecialchars($row['semester'] ?? ''); ?></td>
                            <td><?= htmlspecialchars($row['date'] ?? ''); ?></td>
                        </tr>
                        <?php
                            }
                        } else {
                            echo '<tr><td colspan="9" class="text-center">No schedule found</td></tr>';
                        }
                        ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        
        <!-- Student Progress and Top Performing Students - FIXED: Show data based on search -->
        <div class="kpi-row">
            <div class="kpi-card" style="border-bottom-color: #eb668c;">
                <div class="kpi-title">
                    <i class="fas fa-user-graduate"></i> 
                    <?php if($is_searching && $teacher_info): ?>
                        Student Progress (<?php echo htmlspecialchars($teacher_info['department']); ?> Department)
                    <?php else: ?>
                        Student Progress (All Departments)
                    <?php endif; ?>
                </div>
                <div class="table-responsive">
                    <table class="table table-bordered">
                        <thead>
                            <tr>
                                <th>Name</th>
                                <th>Subject</th>
                                <th>Score</th>
                                <th>Grade</th>
                                <th>Department</th>
                            </tr>
                        </thead>
                        <tbody>
                        <?php
                        // Modified query to join with students table for department filtering
                        if($is_searching && $teacher_info) {
                            $progress_query = "SELECT s.*, st.college 
                                             FROM scores s 
                                             JOIN students st ON s.name = st.name 
                                             WHERE st.college = '$teacher_department' 
                                             ORDER BY s.score DESC 
                                             LIMIT 5";
                        } else {
                            $progress_query = "SELECT s.*, '' as college FROM scores s ORDER BY s.score DESC LIMIT 5";
                        }
                        
                        $sql = mysqli_query($conn, $progress_query);
                        if(mysqli_num_rows($sql) > 0){
                            while($row = mysqli_fetch_assoc($sql)){
                        ?>
                        <tr>
                            <td><?= htmlspecialchars($row['name'] ?? ''); ?></td>
                            <td><?= htmlspecialchars($row['subject'] ?? ''); ?></td>
                            <td><?= htmlspecialchars($row['score'] ?? ''); ?></td>
                            <td><?= htmlspecialchars($row['Grade'] ?? ''); ?></td>
                            <td><?= $is_searching && $teacher_info ? htmlspecialchars($row['college'] ?? '') : 'All'; ?></td>
                        </tr>
                        <?php
                            }
                        } else {
                            echo '<tr><td colspan="5" class="text-center">No data found</td></tr>';
                        }
                        ?>
                        </tbody>
                    </table>
                </div>
            </div>
            
            <div class="kpi-card" style="border-bottom-color: #40189D;">
                <div class="kpi-title">
                    <i class="fas fa-trophy"></i> 
                    <?php if($is_searching && $teacher_info): ?>
                        Top Students (<?php echo htmlspecialchars($teacher_info['department']); ?> Department)
                    <?php else: ?>
                        Top Students (All Departments)
                    <?php endif; ?>
                </div>
                <div class="table-responsive">
                    <table class="table table-bordered">
                        <thead>
                            <tr>
                                <th>Name</th>
                                <th>Subject</th>
                                <th>Score</th>
                                <th>Grade</th>
                                <th>Department</th>
                            </tr>
                        </thead>
                        <tbody>
                        <?php
                        if($is_searching && $teacher_info) {
                            $top_query = "SELECT s.*, st.college 
                                         FROM scores s 
                                         JOIN students st ON s.name = st.name 
                                         WHERE s.Grade IN ('A','B') 
                                         AND st.college = '$teacher_department' 
                                         ORDER BY s.score DESC 
                                         LIMIT 5";
                        } else {
                            $top_query = "SELECT s.*, '' as college FROM scores s WHERE s.Grade IN ('A','B') ORDER BY s.score DESC LIMIT 5";
                        }
                        
                        $sql = mysqli_query($conn, $top_query);
                        if(mysqli_num_rows($sql) > 0){
                            while($row = mysqli_fetch_assoc($sql)){
                        ?>
                        <tr>
                            <td><?= htmlspecialchars($row['name'] ?? ''); ?></td>
                            <td><?= htmlspecialchars($row['subject'] ?? ''); ?></td>
                            <td><?= htmlspecialchars($row['score'] ?? ''); ?></td>
                            <td><?= htmlspecialchars($row['Grade'] ?? ''); ?></td>
                            <td><?= $is_searching && $teacher_info ? htmlspecialchars($row['college'] ?? '') : 'All'; ?></td>
                        </tr>
                        <?php
                            }
                        } else {
                            echo '<tr><td colspan="5" class="text-center">No data found</td></tr>';
                        }
                        ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        
        <!-- CHARTS ROW -->
        <div class="stats-row">
            <div class="stats-col">
                <div class="chart-box">
                    <h4><i class="fas fa-chart-bar"></i> Overall Statistics</h4>
                    <canvas id="myChart" height="300"></canvas>
                </div>
            </div>
            <div class="stats-col">
                <div class="chart-box">
                    <h4><i class="fas fa-chart-pie"></i> Students by College</h4>
                    <canvas id="collegeChart" height="300"></canvas>
                </div>
            </div>
        </div>
        
        <!-- Additional Chart Row -->
        <div class="stats-row">
            <div class="stats-col">
                <div class="chart-box">
                    <h4><i class="fas fa-chart-line"></i> Student Status Distribution</h4>
                    <canvas id="statusChart" height="300"></canvas>
                </div>
            </div>
        </div>
    </div>
</div>

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

// Cancel search function - redirect to same page without search parameter
function cancelSearch() {
    window.location.href = window.location.pathname;
}

// Update date and time
function updateDateTime() {
    const now = new Date();
    const formatted = now.toLocaleString('en-US', { 
        weekday: 'long', 
        year: 'numeric', 
        month: 'long', 
        day: 'numeric',
        hour: '2-digit',
        minute: '2-digit'
    });
    const dateTimeEl = document.getElementById('currentDateTime');
    if(dateTimeEl) dateTimeEl.innerHTML = '<i class="fas fa-calendar-alt"></i> ' + formatted;
}
updateDateTime();
setInterval(updateDateTime, 1000);

// Allow Enter key to submit search
document.getElementById('searchInput')?.addEventListener('keypress', function(e) {
    if (e.key === 'Enter') {
        document.getElementById('searchForm').submit();
    }
});

// Charts
document.addEventListener('DOMContentLoaded', function() {
    const myChartCanvas = document.getElementById('myChart');
    const collegeChartCanvas = document.getElementById('collegeChart');
    const statusChartCanvas = document.getElementById('statusChart');
    
    if (!myChartCanvas || !collegeChartCanvas) {
        console.error("Canvas elements not found!");
        return;
    }
    
    myChartCanvas.style.opacity = '0.5';
    collegeChartCanvas.style.opacity = '0.5';
    if(statusChartCanvas) statusChartCanvas.style.opacity = '0.5';
    
    fetch("chart_data_teacher.php")
    .then(res => {
        if (!res.ok) {
            throw new Error('Network response was not ok: ' + res.status);
        }
        return res.json();
    })
    .then(data => {
        console.log("Data received:", data);
        
        const setText = (id, value) => {
            const el = document.getElementById(id);
            if (el) el.innerText = value || 0;
        };
        
        setText("teachersCount", data.teachers);
        
        if (window.mainChart) {
            try { window.mainChart.destroy(); } catch(e) {}
            window.mainChart = null;
        }
        if (window.collegeChart) {
            try { window.collegeChart.destroy(); } catch(e) {}
            window.collegeChart = null;
        }
        if (window.statusChart) {
            try { window.statusChart.destroy(); } catch(e) {}
            window.statusChart = null;
        }
        
        // Chart 1: Bar Chart
        const ctxMain = myChartCanvas.getContext('2d');
        window.mainChart = new Chart(ctxMain, {
            type: 'bar',
            data: {
                labels: ["Students", "Teachers", "Majors"],
                datasets: [{
                    label: "Total Count",
                    data: [data.students || 0, data.teachers || 0, data.courses || 0],
                    backgroundColor: ["#4f46e5", "#f59e0b", "#10b981"],
                    borderRadius: 10,
                    borderWidth: 0
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: true,
                plugins: {
                    legend: { position: 'top' },
                    tooltip: { callbacks: { label: function(context) { 
                        return `${context.dataset.label}: ${context.parsed.y}`; 
                    } } }
                },
                scales: { y: { beginAtZero: true, ticks: { stepSize: 1, precision: 0 } } }
            }
        });
        
        // Chart 2: Pie Chart - Students by College
        const ctxCollege = collegeChartCanvas.getContext('2d');
        window.collegeChart = new Chart(ctxCollege, {
            type: 'pie',
            data: {
                labels: ["IT", "Civil Engineering", "Electronics", "Business", "Electrical"],
                datasets: [{
                    data: [data.it || 0, data.civil || 0, data.electronics || 0, data.business || 0, data.electrical || 0],
                    backgroundColor: ['#4f46e5', '#8b5cf6', '#ec489a', '#f59e0b', '#10b981'],
                    borderWidth: 2,
                    borderColor: '#fff'
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: true,
                plugins: {
                    legend: { position: 'bottom' },
                    tooltip: { callbacks: { label: function(context) {
                        const label = context.label || '';
                        const value = context.parsed || 0;
                        const total = context.dataset.data.reduce((a, b) => a + b, 0);
                        const percentage = total > 0 ? ((value / total) * 100).toFixed(1) : 0;
                        return `${label}: ${value} (${percentage}%)`;
                    } } }
                }
            }
        });
        
        // Chart 3: Status Chart
        if (statusChartCanvas) {
            const ctxStatus = statusChartCanvas.getContext('2d');
            window.statusChart = new Chart(ctxStatus, {
                type: 'doughnut',
                data: {
                    labels: ["Active", "Inactive", "Transfer", "Old", "Dropped"],
                    datasets: [{
                        data: [data.Active || 0, data.Inactive || 0, data.Transfer || 0, data.old || 0, data.Dropped || 0],
                        backgroundColor: ['#10b981', '#ef4444', '#f59e0b', '#8b5cf6', '#6b7280'],
                        borderWidth: 2,
                        borderColor: '#fff'
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: true,
                    plugins: { legend: { position: 'bottom' } }
                }
            });
        }
        
        myChartCanvas.style.opacity = '1';
        collegeChartCanvas.style.opacity = '1';
        if(statusChartCanvas) statusChartCanvas.style.opacity = '1';
        
        console.log("All charts created successfully!");
    })
    .catch(err => {
        console.error("Error loading data:", err);
        const ctxMain = myChartCanvas.getContext('2d');
        ctxMain.font = '16px Arial';
        ctxMain.fillStyle = 'red';
        ctxMain.fillText('Error loading data: ' + err.message, 50, 150);
        myChartCanvas.style.opacity = '1';
        collegeChartCanvas.style.opacity = '1';
    });
});
</script>
</body>
</html>