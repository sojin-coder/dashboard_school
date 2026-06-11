<?php
include "db.php";

if(!isset($_SESSION['id'])){
    header("Location: login.php");
    exit();
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
    <title>KRaksa Education Suite</title>
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
        a{
            text-decoration: none;
        }
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
            font-size: 20px; 
            font-weight: bold; 
            margin-bottom: 5px; 
        }
        .kpi-border{
            display: flex;
            justify-content: space-between;
            padding: 8px 0;
            border-bottom: 2px dashed #e2e8f0;
        }
        .line_btm{
            margin-top: 5px;
            padding-top: 5px;
            border-bottom: 1px solid rgba(0, 0, 0, 0.1);
        }
        
        .student-stats-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 12px;
            margin-top: 10px;
        }
        .student-stat-group {
            background: #f8fafc;
            padding: 10px 12px;
            border-radius: 14px;
        }
        .student-stat-group h4 {
            font-size: 12px;
            font-weight: 600;
            color: #64748b;
            margin-bottom: 10px;
            letter-spacing: 0.5px;
        }
        .student-stat-item {
            display: flex;
            justify-content: space-between;
            padding: 6px 0;
            font-size: 17px;
            border-bottom: 1px solid #e2e8f0;
        }
        .student-stat-item:last-child {
            border-bottom: none;
        }
        .student-stat-label {
            font-weight: 500;
            color: #334155;
        }
        .student-stat-value {
            font-weight: 700;
            color: #1e293b;
        }
        .total-students-header {
            display: flex;
            justify-content: space-between;
            align-items: baseline;
            margin-bottom: 15px;
            padding-bottom: 10px;
            border-bottom: 2px solid #e2e8f0;
        }
        .teacher-subject-list {
            margin-top: 15px;
            padding-top: 10px;
            border-top: 1px solid rgba(0, 0, 0, 0.08);
        }
        
        .teacher-subject-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 5px 0;
            font-size: 13px;
        }
        
        .teacher-subject-name {
            font-weight: 500;
            font-size: 15px;
        }
        
        .teacher-subject-count {
            background: rgba(100, 116, 139, 0.1);
            padding: 2px 8px;
            border-radius: 20px;
            font-size: 15px;
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
        
        .card{
            padding:25px 15px;
            border-radius:12px;
            text-align:center;
            color:white;
        }
    
        .title{
            font-size:17px;
            color:black;
            margin-bottom:20px;
            font-weight:600;
        }
    
        .total{
            font-size:52px;
            font-weight:bold;
            margin-bottom:25px;
            color:black;
        }
    
        .boxes{
            display:flex;
            gap:12px;
        }
    
        .box{
            flex:1;
            padding:18px 10px;
            border-radius:10px;
            color:white;
        }
    
        .completed{
            background:#2563eb;
        }
    
        .upcoming{
            background:#f97316;
        }
    
        .number{
            font-size:28px;
            font-weight:bold;
            margin-bottom:8px;
        }
    
        .text{
            font-size:14px;
            font-weight:500;
        }
        
        @media (max-width: 768px) {
            .sidebar { width: 80px; }
            .sidebar-header h1, .sidebar-header p, .nav-item span { display: none; }
            .nav-item { justify-content: center; }
            .main-content { padding: 15px; }
            .kpi-card { min-width: 150px; }
            .stats-row { flex-direction: column; }
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
            <a href="/" class="nav-item active" data-page="dashboard"><i class="fas fa-tachometer-alt"></i> <span>Main Dashboard</span></a>
            
            <div class="dropdown-container">
                <div class="nav-item dropdown-btn" onclick="toggleDropdown()">
                    <div>
                        <i class="fas fa-user-graduate"></i>
                        <span class="m-2">Students</span>
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
            
            <a href="teacher.php" class="nav-item" data-page="teachers"><i class="fas fa-chalkboard-teacher"></i> <span>Teachers</span></a>
            <a href="Courses.php" class="nav-item" data-page="courses"><i class="fas fa-graduation-cap"></i> <span>Department</span></a>
            <a href="schedule.php" class="nav-item" data-page="schedule"> <i class="fas fa-calendar-alt"></i> <span>Schedule class</span></a>
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
            <div class="page-title"><h2 id="dynamicTitle">Dashboard</h2></div>
        </div>
        
        <!-- KPI CARDS -->
        <div class="kpi-row">
            <!-- Card 1: Student Overview -->
            <div class="kpi-card" style="border-bottom-color: #f8bb12;">
                <div class="total-students-header">
                    <div class="student-stat-label">📋 Total Students : </div>
                    <span id="studentsCount" class="student-stat-value">Loading...</span>
                </div>
                <div class="student-stat-group">
                    <h4><i class="fas fa-venus-mars"></i> By Gender</h4>
                    <div class="student-stat-item">
                        <span class="student-stat-label">Male</span>
                        <span class="student-stat-value" id="maleCount">0</span>
                    </div>
                    <div class="student-stat-item">
                        <span class="student-stat-label">Female</span>
                        <span class="student-stat-value" id="femaleCount">0</span>
                    </div>
                </div>
                <div class="student-stat-group">
                    <h4><i class="fas fa-user-graduate"></i> By Status</h4>
                    <div class="student-stat-item">
                        <span class="student-stat-label">Active</span>
                        <span class="student-stat-value" id="ActiveCount">0</span>
                    </div>
                    <div class="student-stat-item">
                        <span class="student-stat-label">Inactive</span>
                        <span class="student-stat-value" id="InactiveCount">0</span>
                    </div>
                    <div class="student-stat-item">
                        <span class="student-stat-label">Dropped</span>
                        <span class="student-stat-value" id="droppedCount">0</span>
                    </div>
                </div>
            </div>
            
            <!-- Card 2: Teachers Card -->
            <div class="kpi-card" style="border-bottom-color: #fa1e72;">
                <div class="student-stat-label">👩‍🏫 TOTAL TEACHERS :
                <span id="teachersCount" class="student-stat-value">Loading...</span> </div>
                <div class="teacher-subject-list">
                    <div style="font-size: 12px; opacity: 0.9; margin-bottom: 8px;">
                        <i class="fas fa-chalkboard"></i> Teachers by Subject:
                    </div>
                    <div id="teacherSubjectsContainer">
                        <div class="teacher-subject-item">
                            <span class="teacher-subject-name">Loading...</span>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Card 3: Payment & Student Type -->
            <div class="kpi-card" style="border-bottom-color: #2cf9f7;">
                <div class="student-stat-group">
                    <h4><i class="fas fa-money-bill-wave"></i> Payment</h4>
                    <div class="student-stat-item">
                        <span class="student-stat-label">Paid</span>
                        <span class="student-stat-value" id="paidCount">0</span>
                    </div>
                    <div class="student-stat-item">
                        <span class="student-stat-label">Unpaid</span>
                        <span class="student-stat-value" id="unpaidCount">0</span>
                    </div>
                </div>
                <div class="student-stat-group">
                    <h4><i class="fas fa-exchange-alt"></i> Transfer & Old</h4>
                    <div class="student-stat-item">
                        <span class="student-stat-label">Transfer</span>
                        <span class="student-stat-value" id="TransferCount">0</span>
                    </div>
                    <div class="student-stat-item">
                        <span class="student-stat-label">Old Student</span>
                        <span class="student-stat-value" id="oldCount">0</span>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Second Row: Departments -->
        <div class="kpi-row">
            <div class="kpi-card" style="border-bottom-color: #5cd7f6;">
                <div class="kpi-title">📚 TOTAL MAJOR : <span id="majorCount">Loading...</span></div>
                <div class="line_btm"></div>
                <div class="kpi-border">IT: <span id="itCount">0</span> Student</div>
                <div class="kpi-border">Civil: <span id="civilCount">0</span> Student</div>
                <div class="kpi-border">Electronics: <span id="electronicsCount">0</span> Student</div>
                <div class="kpi-border">Business: <span id="businessCount">0</span> Student</div>
                <div class="kpi-border">Electrical: <span id="electricalCount">0</span> Student</div>
            </div>
            
            <div class="kpi-card" style="border-bottom-color: #d212f8;">
                <div class="kpi-title fs-3 line_btm">Departments</div>
                <div class="mb-4">Information Technology</div>
                <div class="mb-4">Business Management</div>
                <div class="mb-4">Engineering</div>
                <div class="mb-4">Marketing</div>
                <div class="mb-5">Departments organize courses and faculty efficiently.</div>
            </div>
            
            <div class="kpi-card" style="border-bottom-color: #f88112;">
                <div class="card">
                    <div class="title">Total Courses</div>
                    <div class="total">128</div>
                    <div class="boxes">
                        <div class="box completed">
                            <div class="number">73</div>
                            <div class="text">Completed</div>
                        </div>
                        <div class="box upcoming">
                            <div class="number">55</div>
                            <div class="text">Upcoming</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
          <!-- Class Schedule Card -->
        <div class="kpi-row">
            <div class="kpi-card" style="border-bottom-color: #f34b2e; width: 100%;">
                <div class="kpi-title"><i class="fas fa-calendar-alt"></i> Class Schedule</div>
                <small>Today's schedule</small>
                <div class="table-responsive table-container">
                    <table class="table table-bordered mb-0">
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
                        
                        $schedule_query = "SELECT * FROM schedule_class";

                        
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
      
        <!-- CHARTS ROW -->
        <div class="stats-row">
            <div class="stats-col">
                <div class="chart-box">
                    <h4>Overall Statistics</h4>
                    <canvas id="myChart" height="300"></canvas>
                </div>
            </div>
            <div class="stats-col">
                <div class="chart-box">
                    <h4>Students by Major</h4>
                    <canvas id="collegeChart" height="300"></canvas>
                </div>
            </div>
        </div>
    </div>
</div>

<script>

            // Toggle Report Dropdown
        function toggleReportDropdown() {
            let menu = document.getElementById("reportDropdownMenu");
            let icon = document.getElementById("reportDropdownIcon");
            
            if (menu.style.display === "block") {
                menu.style.display = "none";
                icon.style.transform = "rotate(0deg)";
            } else {
                // Close other dropdowns first
                let studentMenu = document.getElementById("studentDropdownMenu");
                let studentIcon = document.getElementById("studentDropdownIcon");
                if (studentMenu) {
                    studentMenu.style.display = "none";
                    if (studentIcon) studentIcon.style.transform = "rotate(0deg)";
                }
                menu.style.display = "block";
                icon.style.transform = "rotate(180deg)";
            }
        }
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
        
        function escapeHtml(text) {
            if (!text) return '';
            const div = document.createElement('div');
            div.textContent = text;
            return div.innerHTML;
        }
        
        function displayTeachersBySubject(teachersData) {
            const container = document.getElementById('teacherSubjectsContainer');
            if (!container) return;
            
            if (!teachersData || teachersData.length === 0) {
                container.innerHTML = '<div class="teacher-subject-item"><span class="teacher-subject-name">No teachers data available</span></div>';
                return;
            }
            
            let html = '';
            teachersData.forEach(subject => {
                html += `
                    <div class="teacher-subject-item">
                        <span class="teacher-subject-name"><i class="fas fa-book-open"></i> ${escapeHtml(subject.subject)}</span>
                        <span class="teacher-subject-count">${subject.total} teacher${subject.total > 1 ? 's' : ''}</span>
                    </div>
                `;
            });
            container.innerHTML = html;
        }
        
        // Load data when page loads
        document.addEventListener('DOMContentLoaded', function() {
            fetch("chart-data.php")
            .then(res => {
                if (!res.ok) {
                    throw new Error('Network response was not ok: ' + res.status);
                }
                return res.json();
            })
            .then(data => {
                console.log("Data received:", data);
                
                // Update KPI Cards
                document.getElementById("studentsCount").innerText = data.students || 0;
                document.getElementById("teachersCount").innerText = data.teachers || 0;
                document.getElementById("majorCount").innerText = data.courses || 0;
                
                document.getElementById("maleCount").innerText = data.male || 0;
                document.getElementById("femaleCount").innerText = data.female || 0;
                document.getElementById("TransferCount").innerText = data.Transfer || 0;
                document.getElementById("oldCount").innerText = data.old || 0;
                document.getElementById("ActiveCount").innerText = data.Active || 0;
                document.getElementById("InactiveCount").innerText = data.Inactive || 0;
                document.getElementById("droppedCount").innerText = data.Dropped || 0;
                document.getElementById("paidCount").innerText = data.paid || 0;
                document.getElementById("unpaidCount").innerText = data.unpaid || 0;
                
                // Update major counts
                document.getElementById("itCount").innerText = data.it || 0;
                document.getElementById("civilCount").innerText = data.civil || 0;
                document.getElementById("electronicsCount").innerText = data.electronics || 0;
                document.getElementById("businessCount").innerText = data.business || 0;
                document.getElementById("electricalCount").innerText = data.electrical || 0;
                
                // Display teachers by subject
                if (data.teachersBySubject && Array.isArray(data.teachersBySubject)) {
                    displayTeachersBySubject(data.teachersBySubject);
                } else {
                    // If teachersBySubject is not in the data, fetch it separately
                    fetch("get_teachers_by_subject.php")
                    .then(res => res.json())
                    .then(subjectData => {
                        displayTeachersBySubject(subjectData);
                    })
                    .catch(err => {
                        console.error("Error loading teachers by subject:", err);
                        document.getElementById('teacherSubjectsContainer').innerHTML = 
                            '<div class="teacher-subject-item"><span class="teacher-subject-name">Error loading data</span></div>';
                    });
                }
               
                // Check if canvas elements exist before creating charts
                const myChartCanvas = document.getElementById('myChart');
                const collegeChartCanvas = document.getElementById('collegeChart');
                
                if (!myChartCanvas) {
                    console.error("Canvas element 'myChart' not found!");
                    return;
                }
                
                if (!collegeChartCanvas) {
                    console.error("Canvas element 'collegeChart' not found!");
                    return;
                }
                
                // Bar Chart - Overall Statistics
                const ctxMain = myChartCanvas.getContext('2d');
                new Chart(ctxMain, {
                    type: 'bar',
                    data: {
                        labels: ["Students", "Teachers"],
                        datasets: [{
                            label: "Total Count",
                            data: [data.students || 0, data.teachers || 0],
                            backgroundColor: ["#F72036", "#A32490"],
                            borderRadius: 10
                        }]
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
                
                // Pie Chart - Students by Major (FIXED SYNTAX ERROR)
                const ctxCollege = collegeChartCanvas.getContext('2d');
                new Chart(ctxCollege, {
                    type: 'pie',
                    data: {
                        labels: ["IT", "Civil Engineering", "Electronics", "Business", "Electrical"],
                        datasets: [{
                            data: [data.it || 0, data.civil || 0, data.electronics || 0, data.business || 0, data.electrical || 0],
                            backgroundColor: ['#667eea', '#764ba2', '#f5576c', '#4facfe', '#43e97b'],
                            borderWidth: 2,
                            borderColor: '#fff'
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: true,
                        plugins: {
                            legend: {
                                position: 'bottom',
                            },
                            tooltip: {
                                callbacks: {
                                    label: function(context) {
                                        const label = context.label || '';
                                        const value = context.parsed || 0;
                                        const total = context.dataset.data.reduce((a, b) => a + b, 0);
                                        const percentage = total > 0 ? ((value / total) * 100).toFixed(1) : 0;
                                        return `${label}: ${value} (${percentage}%)`;
                                    }
                                }
                            }
                        }
                    }
                });
            })
            .catch(err => {
                console.error("Error loading data:", err);
                document.getElementById("studentsCount").innerText = "Error";
                document.getElementById("teachersCount").innerText = "Error";
                document.getElementById("teacherSubjectsContainer").innerHTML = 
                    '<div class="teacher-subject-item"><span class="teacher-subject-name">Error loading data</span></div>';
            });
        });
</script>
</body>
</html>