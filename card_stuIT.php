<?php 
include 'db.php';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <title>KRaksa - Select College for Student Card</title>
    <style>
        /* Dropdown Styles */
        .dropdown-container {
            margin-bottom: 10px;
        }
        
        .dropdown-btn {
            display: flex;
            justify-content: space-between;
            align-items: center;
            cursor: pointer;
        }
        
        .dropdown-menus {
            display: none;
            padding-left: 15px;
            margin-top: 5px;
        }
        
        .dropdown-menus.show {
            display: block;
        }
        
        .sub-menu {
            font-size: 14px;
            padding: 10px 15px;
            margin-bottom: 5px;
            background: rgba(88, 30, 248, 0.61);
        }
        
        .sub-menu:hover {
            background: rgba(110, 41, 238, 0.6);
        }
        
        .dropdown-icon {
            transition: transform 0.3s ease;
        }
        
        .dropdown-icon.rotated {
            transform: rotate(180deg);
        }
        
        * { margin: 0; padding: 0; box-sizing: border-box; }
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
        
        .college-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 30px;
            margin-top: 30px;
        }
        
        .college-card {
            background: white;
            border-radius: 20px;
            overflow: hidden;
            box-shadow: 0 10px 30px rgba(0,0,0,0.1);
            transition: transform 0.3s ease, box-shadow 0.3s ease;
            cursor: pointer;
            text-decoration: none;
            display: block;
        }
        
        .college-card:hover {
            transform: translateY(-10px);
            box-shadow: 0 20px 40px rgba(0,0,0,0.15);
        }
        
        .college-header {
            padding: 40px 20px;
            text-align: center;
            color: white;
        }
        
        .college-header.it { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); }
        .college-header.civil { background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%); }
        .college-header.electronics { background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%); }
        .college-header.business { background: linear-gradient(135deg, #43e97b 0%, #38f9d7 100%); }
        .college-header.electrical { background: linear-gradient(135deg, #fa709a 0%, #fee140 100%); }
        
        .college-header i {
            font-size: 60px;
            margin-bottom: 15px;
        }
        
        .college-header h3 {
            font-size: 24px;
            font-weight: 700;
            margin: 10px 0;
        }
        
        .college-header p {
            font-size: 14px;
            opacity: 0.9;
            margin: 0;
        }
        
        .college-body {
            padding: 20px;
            background: white;
        }
        
        .college-stats {
            display: flex;
            justify-content: space-around;
            margin-bottom: 15px;
        }
        
        .stat {
            text-align: center;
        }
        
        .stat-number {
            font-size: 24px;
            font-weight: 700;
            color: #1e293b;
        }
        
        .stat-label {
            font-size: 12px;
            color: #64748b;
            margin-top: 5px;
        }
        
        .view-btn {
            display: block;
            text-align: center;
            padding: 10px;
            background: #f1f5f9;
            border-radius: 10px;
            color: #1e293b;
            font-weight: 600;
            transition: 0.3s;
        }
        
        .view-btn:hover {
            background: #e2e8f0;
        }
        
        @media (max-width: 768px) {
            .sidebar { width: 80px; }
            .sidebar-header h1, .sidebar-header p, .nav-item span { display: none; }
            .nav-item { justify-content: center; }
            .main-content { padding: 15px; }
            .college-grid { grid-template-columns: 1fr; }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="sidebar">
            <div class="sidebar-header">
                <img src="https://i.pinimg.com/736x/6a/33/e1/6a33e1b0b8fbb948cbf04b7397c9b381.jpg" alt="Logo" />
                <h1>KRaksa</h1>
                <p>Education Suite</p>
            </div>
            <div class="nav-menu">
                <a href="index.php" class="nav-item">
                    <i class="fas fa-tachometer-alt"></i> 
                    <span>Main Dashboard</span>
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
                        <a href="card_stuIT.php" class="nav-item sub-menu active">
                            <i class="fas fa-id-card"></i>
                            <span>ID Card</span>
                        </a>
                    </div>
                </div>

                <a href="teacher.php" class="nav-item">
                    <i class="fas fa-chalkboard-teacher"></i> 
                    <span>Teachers</span>
                </a>
                <a href="Courses.php" class="nav-item">
                    <i class="fas fa-graduation-cap"></i> 
                    <span>Department</span>
                </a>
                 <a href="schedule.php" class="nav-item" data-page="schedule"> <i class="fas fa-calendar-alt"></i> <span>Schedule class</span></a>               
                <a href="Employees.php" class="nav-item">
                    <i class="fas fa-user-friends"></i> 
                    <span>Employees</span>
                </a>
                <a href="StudentAttendance.php" class="nav-item">
                    <i class="fas fa-calendar-check"></i> 
                    <span>Attendance</span>
                </a>
                <div class="nav-bottom">
                    <a href="logout.php" class="nav-item">
                        <i class="fas fa-sign-out-alt"></i> 
                        <span>Logout</span>
                    </a>
                </div>
            </div>
        </div>

        <div class="main-content">
            <div class="top-bar">
                <div class="page-title">
                    <h2>Student ID Cards by College</h2>
                </div>
            </div>
            
            <div class="college-grid">
                <?php
                // Get college statistics
                $colleges = ['IT', 'Civil Engineering', 'Electronics', 'Business Science', 'Electrical'];
                $colors = ['it', 'civil', 'electronics', 'business', 'electrical'];
                $icons = ['fa-laptop-code', 'fa-building', 'fa-microchip', 'fa-chart-line', 'fa-bolt'];
                
                for($i = 0; $i < count($colleges); $i++) {
                    $college = $colleges[$i];
                    $color = $colors[$i];
                    $icon = $icons[$i];
                    
                    // Count students in this college
                    $count_query = "SELECT COUNT(*) as total FROM students WHERE college = ?";
                    $stmt = $conn->prepare($count_query);
                    $stmt->bind_param("s", $college);
                    $stmt->execute();
                    $count_result = $stmt->get_result();
                    $count_data = $count_result->fetch_assoc();
                    $total_students = $count_data['total'] ?? 0;
                    
                    // Count male and female
                    $male_query = "SELECT COUNT(*) as male FROM students WHERE college = ? AND gender = 'Male'";
                    $stmt = $conn->prepare($male_query);
                    $stmt->bind_param("s", $college);
                    $stmt->execute();
                    $male_result = $stmt->get_result();
                    $male_data = $male_result->fetch_assoc();
                    $male_count = $male_data['male'] ?? 0;
                    
                    $female_count = $total_students - $male_count;
                    ?>
                    
                    <a href="card_student_college.php?college=<?php echo urlencode($college); ?>" class="college-card">
                        <div class="college-header <?php echo $color; ?>">
                            <i class="fas <?php echo $icon; ?>"></i>
                            <h3><?php echo htmlspecialchars($college); ?></h3>
                            <p>Department of <?php echo htmlspecialchars($college); ?></p>
                        </div>
                        <div class="college-body">
                            <div class="college-stats">
                                <div class="stat">
                                    <div class="stat-number"><?php echo $total_students; ?></div>
                                    <div class="stat-label">Total Students</div>
                                </div>
                                <div class="stat">
                                    <div class="stat-number"><?php echo $male_count; ?></div>
                                    <div class="stat-label">Male</div>
                                </div>
                                <div class="stat">
                                    <div class="stat-number"><?php echo $female_count; ?></div>
                                    <div class="stat-label">Female</div>
                                </div>
                            </div>
                            <div class="view-btn">
                                <i class="fas fa-id-card"></i> View ID Cards
                            </div>
                        </div>
                    </a>
                    
                <?php } ?>
            </div>
        </div>
    </div>

    <script>
        // Simple toggle dropdown function
        function toggleDropdown() {
            let menu = document.getElementById("studentDropdown");
            let icon = document.getElementById("dropdownIcon");
            
            if (menu.classList.contains("show")) {
                menu.classList.remove("show");
                icon.classList.remove("rotated");
            } else {
                menu.classList.add("show");
                icon.classList.add("rotated");
            }
        }
        
        // Close dropdown when clicking outside
        document.addEventListener('click', function(event) {
            let dropdownContainer = document.querySelector('.dropdown-container');
            let menu = document.getElementById("studentDropdown");
            let icon = document.getElementById("dropdownIcon");
            
            // Check if click is outside the dropdown container
            if (dropdownContainer && !dropdownContainer.contains(event.target)) {
                if (menu && menu.classList.contains("show")) {
                    menu.classList.remove("show");
                    if (icon) icon.classList.remove("rotated");
                }
            }
        });
        
        // Initialize - ensure dropdown is closed on page load
        document.addEventListener('DOMContentLoaded', function() {
            let menu = document.getElementById("studentDropdown");
            let icon = document.getElementById("dropdownIcon");
            if (menu) {
                menu.classList.remove("show");
            }
            if (icon) {
                icon.classList.remove("rotated");
            }
        });
    </script>
</body>
</html>