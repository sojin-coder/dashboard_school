<?php
            include 'db.php'; // Database connection
            
            $totals = []; // For storing total students per subject
            $yearly_totals = []; // For storing year counts per subject
            
            // Query 1: Get total students per subject
            $query = "SELECT SUBJECT, COUNT(*) AS total FROM studenattenden GROUP BY SUBJECT";
            $result = mysqli_query($conn, $query);
            
            while ($row = mysqli_fetch_assoc($result)) {
                $totals[$row['SUBJECT']] = $row['total'];
            }
            
            // Query 2: Get year counts per subject
            $query = "SELECT SUBJECT, year, COUNT(*) AS total FROM studenattenden GROUP BY SUBJECT, year";
            $results = mysqli_query($conn, $query);
            
            while ($row = mysqli_fetch_assoc($results)) {
                $subject = $row['SUBJECT'];
                $year = $row['year'];
                $yearly_totals[$subject][$year] = $row['total'];
            }
            
            // Helper function to get total students by subject
            function get_total($subject, $totals) {
                return isset($totals[$subject]) ? $totals[$subject] : 0;
            }
            
            // Helper function to get year count for a specific subject and year
            function get_year_total($subject, $year, $yearly_totals) {
                return isset($yearly_totals[$subject][$year]) ? $yearly_totals[$subject][$year] : 0;
            }
 ?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0, user-scalable=yes" />
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <title>KRaksa - Attendance Dashboard | Employee & Student Attendance</title>
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

        /* Your existing CSS styles here (keeping them as is) */
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: "Inter", sans-serif;
            background: #c5e1fc;
            color: #0f172a;
            overflow-x: hidden;
        }

        .container {
            display: flex;
            min-height: 100vh;
            max-width: 100%;
        }

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

        .sidebar-header {
            padding: 28px 24px;
            border-bottom: 1px solid rgba(255, 255, 255, 0.15);
            margin-bottom: 24px;
            text-align: center;
        }

        .sidebar-header img {
            width: 100px;
            height: 100px;
            border-radius: 50%;
            margin: auto;
            display: block;
            border: 4px solid white;
            object-fit: cover;
            background: white;
        }

        .sidebar-header h1 {
            font-size: 1.8rem;
            font-weight: 700;
            color: white;
            margin-top: 12px;
            letter-spacing: -0.3px;
        }

        .sidebar-header p {
            font-size: 0.85rem;
            opacity: 0.8;
        }

        .nav-menu {
            flex: 1;
            padding: 0 16px 20px;
        }

        .nav-item {
            display: flex;
            align-items: center;
            gap: 15px;
            padding: 12px 16px;
            margin-bottom: 8px;
            border-radius: 14px;
            cursor: pointer;
            transition: 0.25s ease;
            color: #e0e7ff;
            text-decoration: none;
            font-weight: 500;
        }

        .nav-item:hover {
            background: rgba(255, 255, 255, 0.12);
            color: white;
            transform: translateX(4px);
        }

        .nav-item.active {
            background: #fd0054;
            color: white;
            box-shadow: 0 6px 12px rgba(253, 0, 84, 0.25);
        }

        .nav-bottom {
            margin-top: 30px;
            padding-top: 16px;
            border-top: 1px solid rgba(255, 255, 255, 0.15);
        }

        .main-content {
            flex: 1;
            padding: 28px 36px;
            background: #f0f6fe;
            overflow-y: auto;
            height: 100vh;
        }

        .top-bar {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 12px 28px;
            background: rgba(255, 255, 255, 0.85);
            backdrop-filter: blur(4px);
            border-radius: 50px;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.02), 0 1px 2px rgba(0, 0, 0, 0.05);
            margin-bottom: 32px;
            border: 1px solid rgba(255,255,255,0.6);
        }

        .page-title h2 {
            font-size: 1.5rem;
            font-weight: 700;
            background: linear-gradient(135deg, #1e293b, #2d3a4e);
            background-clip: text;
            -webkit-background-clip: text;
            color: transparent;
            margin: 0;
        }

        .grid {
            display: flex;
            flex-wrap: wrap;
            gap: 40px;
            justify-content: flex-start;
            align-items: stretch;
        }

        .card {
            width: 340px;
            max-width: 100%;
            background: white;
            border-radius: 28px;
            overflow: hidden;
            box-shadow: 0 20px 35px -12px rgba(0, 0, 0, 0.12);
            transition: transform 0.25s ease, box-shadow 0.3s;
            border: 1px solid rgba(0, 0, 0, 0.04);
        }

        .card:hover {
            transform: translateY(-8px);
            box-shadow: 0 25px 40px -12px rgba(0, 0, 0, 0.2);
        }

        .card-img img {
            width: 100%;
            height: 200px;
            object-fit: cover;
            transition: transform 0.4s;
        }

        .card:hover .card-img img {
            transform: scale(1.02);
        }

        .body-card h1 {
            font-size: 1.65rem;
            font-weight: 700;
            padding: 12px 16px 0;
            text-align: center;
            color: #0f2b3d;
            letter-spacing: -0.3px;
        }

        .college-body {
            padding: 12px 20px 24px;
        }

        .college-stats {
            display: flex;
            justify-content: space-around;
            margin: 12px 0 18px;
            background: #f8fafc;
            border-radius: 24px;
            padding: 12px 5px;
        }

        .stat {
            text-align: center;
            flex: 1;
        }

        .stat-number {
            font-size: 28px;
            font-weight: 800;
            color: #1e2a5e;
            line-height: 1.2;
        }

        .stat-label {
            font-size: 12px;
            font-weight: 500;
            color: #4a5b7a;
            margin-top: 6px;
            letter-spacing: 0.3px;
        }

        .view-btn {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 12px;
            padding: 12px 16px;
            background: #eef2ff;
            border-radius: 40px;
            color: #1f3a6b;
            font-weight: 600;
            transition: 0.25s;
            font-size: 0.95rem;
            border: none;
        }

        .view-btn:hover {
            background: #1e2a5e;
            color: white;
            transform: scale(0.98);
        }

        a {
            text-decoration: none;
        }

        .att-badge {
            font-size: 0.7rem;
            background: #e6f0ff;
            border-radius: 30px;
            padding: 2px 10px;
            display: inline-block;
        }

        @media (max-width: 860px) {
            .sidebar {
                width: 90px;
            }
            .sidebar-header h1, .sidebar-header p, .nav-item span {
                display: none;
            }
            .nav-item {
                justify-content: center;
            }
            .main-content {
                padding: 20px;
            }
            .grid {
                justify-content: center;
            }
            .card {
                width: 100%;
                max-width: 420px;
            }
        }

        @media (max-width: 550px) {
            .sidebar {
                width: 70px;
            }
            .main-content {
                padding: 16px;
            }
            .top-bar {
                padding: 8px 18px;
            }
        }

        .no-data-msg {
            text-align: center;
            color: #5c6f8c;
            font-weight: 500;
            padding: 12px;
        }
        
        .alert-light {
            background-color: #ffffffcc !important;
            backdrop-filter: blur(4px);
            font-size: 0.9rem;
        }
        
        .nav-item i {
            width: 24px;
            text-align: center;
        }
        
        ::-webkit-scrollbar {
            width: 6px;
        }
        
        ::-webkit-scrollbar-track {
            background: #e2e8f0;
            border-radius: 10px;
        }
        
        ::-webkit-scrollbar-thumb {
            background: #8b9dc3;
            border-radius: 10px;
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
    </style>
</head>
<body>
<div class="container">
    <!-- SIDEBAR -->
    <div class="sidebar">
        <div class="sidebar-header">
            <img src="https://i.pinimg.com/736x/6a/33/e1/6a33e1b0b8fbb948cbf04b7397c9b381.jpg" alt="KRaksa Logo" />
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
                
                <a href="teacher_fortea.php" class="nav-item ">
                    <i class="fas fa-chalkboard-teacher"></i> <span>Teachers</span>
                </a>
                
                <a href="courses_tea.php" class="nav-item">
                    <i class="fas fa-graduation-cap"></i> <span>Department</span>
                </a>
                
                <a href="attendanceAll_tea.php" class="nav-item active">
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
            <div class="page-title">
                <h2><i class="fas fa-fingerprint me-2" style="color:#2c5282;"></i> Attendance Overview</h2>
            </div>
            <div>
                <span class="att-badge"><i class="far fa-calendar-alt"></i> Today: <?php echo date('M d, Y'); ?></span>
            </div>
            <a href="attendance_top_teacher.php" class="back-btn">
                <i class="fas fa-arrow-left"></i> Back
            </a>
        </div>

        <div class="grid">
            <!-- Card: Attendance of IT/Employee -->
            <a href="attit_tea.php" style="text-decoration: none;">
                <div class="card">
                    <div class="card-img">
                        <img src="https://i.pinimg.com/736x/ed/c8/c0/edc8c0539c1470492c6a149878a6082d.jpg" alt="Employee Attendance">
                    </div>
                    <div class="body-card">
                        <h1><i class="fas fa-user-tie me-2"></i> Attendance of IT</h1>
                        <div class="college-body">
                            <div class="college-stats">
                                <div class="stat">
                                    <div class="stat-number">
                                        <?php echo get_total('it', $totals); ?>
                                    </div>
                                    <div class="stat-label">Total Staff</div>
                                </div>
                            </div>
                            <div class="college-stats">
                                <div class="stat">
                                    <div class="stat-number">
                                        <?php echo get_year_total('it', 'year 1', $yearly_totals); ?>
                                    </div>
                                    <div class="stat-label">Year 1</div>
                                </div>
                                <div class="stat">
                                    <div class="stat-number">
                                        <?php echo get_year_total('it', 'year 2', $yearly_totals); ?>
                                    </div>
                                    <div class="stat-label">Year 2</div>
                                </div>
                                <div class="stat">
                                    <div class="stat-number">
                                        <?php echo get_year_total('it', 'year 3', $yearly_totals); ?>
                                    </div>
                                    <div class="stat-label">Year 3</div>
                                </div>
                                <div class="stat">
                                    <div class="stat-number">
                                        <?php echo get_year_total('it', 'year 4', $yearly_totals); ?>
                                    </div>
                                    <div class="stat-label">Year 4</div>
                                </div>
                            </div>
                            <div class="view-btn">
                                <i class="fas fa-clock"></i> View Employee Attendance
                            </div>
                        </div>
                    </div>
                </div>
            </a>

            <!-- Card: Attendance of Civil Engineering -->
            <a href="attcivil_tea.php" style="text-decoration: none;">
                <div class="card">
                    <div class="card-img">
                        <img src="https://i.pinimg.com/736x/31/6a/e7/316ae7253e1ed6ebd583cb2f072507a4.jpg" alt="Student Attendance">
                    </div>
                    <div class="body-card">
                        <h1><i class="fas fa-users me-2"></i> Attendance Of Civil Engineering</h1>
                        <div class="college-body">
                            <div class="college-stats">
                                <div class="stat">
                                    <div class="stat-number">
                                        <?php echo get_total('Civil Engineering', $totals); ?>
                                    </div>
                                    <div class="stat-label">Total Students</div>
                                </div>
                            </div>
                            <div class="college-stats">
                                <div class="stat">
                                    <div class="stat-number">
                                        <?php echo get_year_total('Civil Engineering', 'year 1', $yearly_totals); ?>
                                    </div>
                                    <div class="stat-label">Year 1</div>
                                </div>
                                <div class="stat">
                                    <div class="stat-number">
                                        <?php echo get_year_total('Civil Engineering', 'year 2', $yearly_totals); ?>
                                    </div>
                                    <div class="stat-label">Year 2</div>
                                </div>
                                <div class="stat">
                                    <div class="stat-number">
                                        <?php echo get_year_total('Civil Engineering', 'year 3', $yearly_totals); ?>
                                    </div>
                                    <div class="stat-label">Year 3</div>
                                </div>
                                <div class="stat">
                                    <div class="stat-number">
                                        <?php echo get_year_total('Civil Engineering', 'year 4', $yearly_totals); ?>
                                    </div>
                                    <div class="stat-label">Year 4</div>
                                </div>
                            </div>
                            <div class="view-btn">
                                <i class="fas fa-id-card"></i> Manage Student Attendance
                            </div>
                        </div>
                    </div>
                </div>
            </a>

            <!-- Card: Attendance of Electronics -->
            <a href="attElectronics_tea.php" style="text-decoration: none;">
                <div class="card">
                    <div class="card-img">
                        <img src="https://i.pinimg.com/736x/c6/5c/3d/c65c3dfc933ee85b957735f878f6cf87.jpg" alt="Student Attendance">
                    </div>
                    <div class="body-card">
                        <h1><i class="fas fa-users me-2"></i> Attendance Of Electronics</h1>
                        <div class="college-body">
                            <div class="college-stats">
                                <div class="stat">
                                    <div class="stat-number">
                                        <?php echo get_total('Electronics', $totals); ?>
                                    </div>
                                    <div class="stat-label">Total Students</div>
                                </div>
                            </div>
                            <div class="college-stats">
                                <div class="stat">
                                    <div class="stat-number">
                                        <?php echo get_year_total('Electronics', 'year 1', $yearly_totals); ?>
                                    </div>
                                    <div class="stat-label">Year 1</div>
                                </div>
                                <div class="stat">
                                    <div class="stat-number">
                                        <?php echo get_year_total('Electronics', 'year 2', $yearly_totals); ?>
                                    </div>
                                    <div class="stat-label">Year 2</div>
                                </div>
                                <div class="stat">
                                    <div class="stat-number">
                                        <?php echo get_year_total('Electronics', 'year 3', $yearly_totals); ?>
                                    </div>
                                    <div class="stat-label">Year 3</div>
                                </div>
                                <div class="stat">
                                    <div class="stat-number">
                                        <?php echo get_year_total('Electronics', 'year 4', $yearly_totals); ?>
                                    </div>
                                    <div class="stat-label">Year 4</div>
                                </div>
                            </div>
                            <div class="view-btn">
                                <i class="fas fa-id-card"></i> Manage Student Attendance
                            </div>
                        </div>
                    </div>
                </div>
            </a>

            <!-- Card: Attendance of Business -->
            <a href="attBusiness_tea.php" style="text-decoration: none;">
                <div class="card">
                    <div class="card-img">
                        <img src="https://i.pinimg.com/736x/70/52/ad/7052ad5f76a69b85133af4569959dc32.jpg" alt="Student Attendance">
                    </div>
                    <div class="body-card">
                        <h1><i class="fas fa-users me-2"></i> Attendance Of Business</h1>
                        <div class="college-body">
                            <div class="college-stats">
                                <div class="stat">
                                    <div class="stat-number">
                                        <?php echo get_total('Business', $totals); ?>
                                    </div>
                                    <div class="stat-label">Total Students</div>
                                </div>
                            </div>
                            <div class="college-stats">
                                <div class="stat">
                                    <div class="stat-number">
                                        <?php echo get_year_total('Business', 'year 1', $yearly_totals); ?>
                                    </div>
                                    <div class="stat-label">Year 1</div>
                                </div>
                                <div class="stat">
                                    <div class="stat-number">
                                        <?php echo get_year_total('Business', 'year 2', $yearly_totals); ?>
                                    </div>
                                    <div class="stat-label">Year 2</div>
                                </div>
                                <div class="stat">
                                    <div class="stat-number">
                                        <?php echo get_year_total('Business', 'year 3', $yearly_totals); ?>
                                    </div>
                                    <div class="stat-label">Year 3</div>
                                </div>
                                <div class="stat">
                                    <div class="stat-number">
                                        <?php echo get_year_total('Business', 'year 4', $yearly_totals); ?>
                                    </div>
                                    <div class="stat-label">Year 4</div>
                                </div>
                            </div>
                            <div class="view-btn">
                                <i class="fas fa-id-card"></i> Manage Student Attendance
                            </div>
                        </div>
                    </div>
                </div>
            </a>

            <!-- Card: Attendance of Electrical Engineering -->
            <a href="attElectrical_tea.php" style="text-decoration: none;">
                <div class="card">
                    <div class="card-img">
                        <img src="https://i.pinimg.com/1200x/26/8f/a3/268fa3ac168d8137714dd14af25aa9ce.jpg" alt="Student Attendance">
                    </div>
                    <div class="body-card">
                        <h1><i class="fas fa-users me-2"></i> Attendance Of Electrical Engineering</h1>
                        <div class="college-body">
                            <div class="college-stats">
                                <div class="stat">
                                    <div class="stat-number">
                                        <?php echo get_total('Electrical Engineering', $totals); ?>
                                    </div>
                                    <div class="stat-label">Total Students</div>
                                </div>
                            </div>
                            <div class="college-stats">
                                <div class="stat">
                                    <div class="stat-number">
                                        <?php echo get_year_total('Electrical Engineering', 'year 1', $yearly_totals); ?>
                                    </div>
                                    <div class="stat-label">Year 1</div>
                                </div>
                                <div class="stat">
                                    <div class="stat-number">
                                        <?php echo get_year_total('Electrical Engineering', 'year 2', $yearly_totals); ?>
                                    </div>
                                    <div class="stat-label">Year 2</div>
                                </div>
                                <div class="stat">
                                    <div class="stat-number">
                                        <?php echo get_year_total('Electrical Engineering', 'year 3', $yearly_totals); ?>
                                    </div>
                                    <div class="stat-label">Year 3</div>
                                </div>
                                <div class="stat">
                                    <div class="stat-number">
                                        <?php echo get_year_total('Electrical Engineering', 'year 4', $yearly_totals); ?>
                                    </div>
                                    <div class="stat-label">Year 4</div>
                                </div>
                            </div>
                            <div class="view-btn">
                                <i class="fas fa-id-card"></i> Manage Student Attendance
                            </div>
                        </div>
                    </div>
                </div>
            </a>
        </div>

        <!-- Quick info section -->
        <div class="row mt-5">
            <div class="col-12">
                <div class="alert alert-light shadow-sm border-0" style="border-radius: 28px; background: #ffffffdd;">
                    <i class="fas fa-info-circle me-2 text-primary"></i> 
                    <strong>Attendance modules:</strong> Click on any card to access detailed attendance logs, mark attendance, or generate reports.
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

</script>
<script>
    // Helper to safely format numbers
    function formatNumber(value) {
        if (value === undefined || value === null) return '0';
        let num = Number(value);
        if (isNaN(num)) return '0';
        return num.toLocaleString();
    }
    
    document.addEventListener('DOMContentLoaded', function() {
        console.log("Attendance dashboard ready — showing realtime overview with year counts by subject");
    });
</script>
</body>
</html>