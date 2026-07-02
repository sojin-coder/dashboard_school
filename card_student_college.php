<?php 
    include 'db.php';
    
    // Get college from URL
    $college = isset($_GET['college']) ? $_GET['college'] : 'IT';
    
    // Get all students from selected college
    $query = "SELECT * FROM students WHERE college = ? ORDER BY name";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("s", $college);
    $stmt->execute();
    $result = $stmt->get_result();
    $students = $result->fetch_all(MYSQLI_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <title>KRaksa - <?php echo htmlspecialchars($college); ?> Student Cards</title>
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
        
        .dropdown {
            display: block;
            cursor: pointer;
            position: relative;
        }
        
        .dropdown-toggle {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 12px 16px;
            color: #cbd5e6;
            border-radius: 14px;
            transition: 0.3s;
            gap: 15px;
        }
        
        .dropdown-toggle:hover {
            background: rgba(255, 255, 255, 0.1);
            color: white;
        }
        
        .dropdown-menu {
            display: none;
            flex-direction: column;
            background: rgba(44, 47, 72, 0.95);
            margin-left: 30px;
            border-radius: 10px;
            overflow: hidden;
            margin-top: 5px;
            margin-bottom: 5px;
        }
        
        .dropdown-menu a {
            padding: 10px 16px;
            color: #cbd5e6;
            text-decoration: none;
            font-size: 14px;
            transition: 0.2s;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .dropdown-menu a:hover {
            background: rgba(255, 255, 255, 0.15);
            color: white;
        }
        
        .arrow {
            transition: transform 0.3s ease;
            margin-left: auto;
        }
        
        .dropdown.active .arrow {
            transform: rotate(180deg);
        }
        
        .main-content { flex: 1; padding: 28px 32px; background: #f8fafc; overflow-y: auto; height: 100vh; }
        .top-bar { display: flex; justify-content: space-between; align-items: center; padding: 12px 24px; background: white; border-radius: 60px; box-shadow: 0 1px 3px rgba(0,0,0,0.05); margin-bottom: 28px; }
        .page-title h2 { font-size: 1.3rem; font-weight: 600; color: #1e293b; margin: 0; }
        
        .back-btn {
            background: #e2e8f0;
            border: none;
            padding: 8px 16px;
            border-radius: 8px;
            cursor: pointer;
            text-decoration: none;
            color: #1e293b;
            display: inline-flex;
            align-items: center;
            gap: 8px;
        }
        
        /* Print Button Styles */
        .print-actions {
            display: flex;
            gap: 10px;
            align-items: center;
        }
        
        .print-btn {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border: none;
            padding: 8px 20px;
            border-radius: 8px;
            cursor: pointer;
            font-size: 14px;
            font-weight: 500;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            transition: all 0.3s ease;
        }
        
        .print-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(0,0,0,0.2);
        }
        
        .print-all-btn {
            background: linear-gradient(135deg, #43e97b 0%, #38f9d7 100%);
        }
        
        .cards-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(350px, 1fr));
            gap: 30px;
            margin-top: 30px;
        }
        
        .id-card {
            width: 100%;
            max-width: 350px;
            background: #fbe9c8;
            border: 1px solid #0d47a1;
            border-radius: 10px;
            overflow: hidden;
            position: relative;
            margin: 0 auto;
            box-shadow: 0 12px 28px rgba(0, 0, 0, 0.2);
            display: flex;
            flex-direction: column;
            transition: transform 0.2s ease;
        }
        
        .id-card:hover {
            transform: scale(1.02);
        }
        
        .card-inner {
            display: flex;
            flex-direction: column;
            height: 100%;
            width: 100%;
        }
        
        .card-strip {
            background: linear-gradient(90deg, #1e3c5c 0%, #2d5f8b 100%);
            height: 8px;
            width: 100%;
        }
        
        .card-strip.it { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); }
        .card-strip.civil { background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%); }
        .card-strip.electronics { background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%); }
        .card-strip.business { background: linear-gradient(135deg, #43e97b 0%, #38f9d7 100%); }
        .card-strip.electrical { background: linear-gradient(135deg, #fa709a 0%, #fee140 100%); }
        
        .card-content {
            display: flex;
            flex-direction: column;
            align-items: center;
            padding: 20px 15px;
            position: relative;
        }
        
        .uni-name {
            font-size: 18px;
            font-weight: 700;
            text-transform: uppercase;
            color: #0d47a1;
            text-align: center;
            margin-bottom: 5px;
        }
        
        .id-title {
            margin-bottom: 10px;
        }
        
        .id-title-en {
            font-size: 14px;
            font-weight: 800;
            color: #0b2b44;
            letter-spacing: 1px;
        }
        
        .student-id-badge {
            background: #eef2f7;
            border-radius: 5px;
            padding: 5px 10px;
            margin: 10px 0;
            font-size: 14px;
            font-weight: 600;
            color: #1a4972;
            text-align: center;
        }
        
        .photo-area {
            width: 100%;
            text-align: center;
            margin: 10px 0;
        }
        
        .photo-frame {
            width: 120px;
            height: 140px;
            margin: 0 auto;
            border: 2px solid #0d47a1;
            border-radius: 5px;
            overflow: hidden;
            background: #f0f4fa;
        }
        
        .student-img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }
        
        .details-area {
            width: 100%;
            margin-top: 15px;
        }
        
        .info-row {
            display: flex;
            align-items: baseline;
            font-size: 13px;
            line-height: 1.8;
            border-bottom: 0.5px solid #eef2f8;
            padding: 5px 0;
        }
        
        .info-label {
            width: 35%;
            font-weight: 700;
            color: #5f7f9c;
            text-transform: uppercase;
            font-size: 11px;
        }
        
        .info-value {
            flex: 1;
            font-weight: 500;
            color: #1e2f3d;
            font-size: 12px;
        }
        
        .card-footer-mini {
            background: #0d47a1;
            color: white;
            text-align: center;
            padding: 10px;
            font-size: 12px;
        }
        
        .no-students {
            text-align: center;
            padding: 50px;
            background: white;
            border-radius: 20px;
        }
        
        /* Print Styles - One card per A4 page */
        @media print {
            /* Hide sidebar, buttons, and other UI elements */
            .sidebar, .top-bar, .back-btn, .print-actions, .container > .sidebar, .top-bar, .print-actions {
                display: none !important;
            }
            
            /* Set A4 page size */
            @page {
                size: A4;
                margin: 0.5cm;
            }
            
            /* Reset body for printing */
            body {
                background: white;
                margin: 0;
                padding: 0;
            }
            
            /* Main content takes full width */
            .main-content {
                padding: 0 !important;
                margin: 0 !important;
                background: white;
                width: 100%;
                height: auto;
                overflow: visible;
            }
            
            /* Cards grid becomes block for printing */
            .cards-grid {
                display: block;
                margin: 0;
                padding: 0;
                width: 100%;
            }
            
            /* Each card takes one full page */
            .id-card {
                width: 280px;
                max-width: 280px;
                margin: 40px auto;
                box-shadow: none;
                border: 2px solid #0d47a1;
                page-break-after: always;
                break-inside: avoid;
                position: relative;
            }
            
            /* Last card doesn't need a page break after */
            .id-card:last-child {
                page-break-after: auto;
            }
            
            /* Ensure colors print correctly */
            .card-strip, .card-footer-mini, .student-id-badge {
                print-color-adjust: exact;
                -webkit-print-color-adjust: exact;
            }
            
            /* Adjust font sizes for print */
            .uni-name {
                font-size: 16px;
            }
            
            .id-title-en {
                font-size: 12px;
            }
            
            .student-id-badge {
                font-size: 12px;
            }
            
            .info-label {
                font-size: 10px;
            }
            
            .info-value {
                font-size: 11px;
            }
            
            .card-footer-mini {
                font-size: 10px;
            }
        }
        
        @media (max-width: 768px) {
            .sidebar { width: 80px; }
            .sidebar-header h1, .sidebar-header p, .nav-item span { display: none; }
            .nav-item { justify-content: center; }
            .main-content { padding: 15px; }
            .cards-grid { grid-template-columns: 1fr; }
            .print-actions { flex-direction: column; }
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
                        <a href="card_stuIT.php" class="nav-item sub-menu active">
                            <i class="fas fa-id-card"></i>
                            <span>ID Card</span>
                        </a>
                    </div>
                </div>
                <a href="teacher.php" class="nav-item"><i class="fas fa-chalkboard-teacher"></i> <span>Teachers</span></a>
                <a href="Courses.php" class="nav-item"><i class="fas fa-graduation-cap"></i> <span>Department</span></a>
                 <a href="schedule.php" class="nav-item" data-page="schedule"> <i class="fas fa-calendar-alt"></i> <span>Schedule class</span></a>
                 <div class="dropdown-container">
                <div class="nav-item dropdown-btn" onclick="toggleRequestDropdown()">
                    <div>
                        <i class="fas fa-file-pdf"></i> 
                        <span class="m-2"> Request</span>
                    </div>
                    <i class="fas fa-chevron-down dropdown-icon" id="RequestDropdownIcon"></i>
                </div>
                <div class="dropdown-menus" id="RequestDropdownMenu">
                    <a href=" Request_teacher.php" class="nav-item sub-menu"><i class="fas fa-chalkboard-teacher"></i><span>Teacher</span></a>
                    <a href="Request_student.php" class="nav-item sub-menu"><i class="fas fa-users"></i><span>Student </span></a>
                   
                </div>
            </div>
                <a href="Employees.php" class="nav-item"><i class="fas fa-user-friends"></i> <span>Employees</span></a>
                <a href="StudentAttendance.php" class="nav-item"><i class="fas fa-calendar-check"></i> <span>Attendance</span></a>
                <a href="logout.php" class="nav-bottom">
                    <div class="nav-item" style="padding-left:8px;">
                        <i class="fas fa-sign-out-alt"></i> <span>Logout</span>
                    </div>
                </a>
            </div>
        </div>

        <div class="main-content">
            <div class="top-bar">
                <div class="page-title">
                    <h2><i class="fas fa-id-card"></i> <?php echo htmlspecialchars($college); ?> - Student ID Cards</h2>
                </div>
                <div class="print-actions">
                    <button class="print-btn" onclick="printCurrentPage()">
                        <i class="fas fa-print"></i> Print This Page
                    </button>
                    <button class="print-btn print-all-btn" onclick="printAllCards()">
                        <i class="fas fa-id-card"></i> Print All Cards
                    </button>
                    <a href="card_stuIT.php" class="back-btn">
                        <i class="fas fa-arrow-left"></i> Back
                    </a>
                </div>
            </div>
            
            <?php if(count($students) > 0): ?>
                <div class="cards-grid" id="cardsGrid">
                    <?php foreach($students as $student): 
                        $college_class = '';
                        if(strtolower($student['college']) == 'it') $college_class = 'it';
                        elseif(strtolower($student['college']) == 'civil engineering') $college_class = 'civil';
                        elseif(strtolower($student['college']) == 'electronics') $college_class = 'electronics';
                        elseif(strtolower($student['college']) == 'business science') $college_class = 'business';
                        elseif(strtolower($student['college']) == 'electrical') $college_class = 'electrical';
                        
                        // Get image URL from database
                        $image_url = !empty($student['image']) ? $student['image'] : '';
                        
                        // Get initials for placeholder if no image
                        $name_parts = explode(' ', trim($student['name']));
                        $initials = '';
                        if(count($name_parts) >= 2) {
                            $initials = strtoupper(substr($name_parts[0], 0, 1) . substr($name_parts[count($name_parts)-1], 0, 1));
                        } else {
                            $initials = strtoupper(substr($student['name'], 0, 2));
                        }
                    ?>
                        <div class="id-card" id="card_<?php echo $student['id']; ?>">
                            <div class="card-inner">
                                <div class="card-strip <?php echo $college_class; ?>"></div>
                                <div class="card-content">
                                    <div class="uni-name">
                                        <?php echo htmlspecialchars(ucfirst($student['college'])); ?> DEPARTMENT
                                    </div>
                                    <div class="id-title">
                                        <span class="id-title-en">STUDENT ID CARD</span>
                                    </div>
                                    <div class="student-id-badge">
                                        ID: <?php echo htmlspecialchars($student['student_id'] ?? 'STU-' . str_pad($student['id'], 6, '0', STR_PAD_LEFT)); ?>
                                    </div>
                                    <div class="photo-area">
                                        <div class="photo-frame">
                                            <?php if(!empty($image_url) && filter_var($image_url, FILTER_VALIDATE_URL)): ?>
                                                <img class="student-img"
                                                     src="<?php echo htmlspecialchars($image_url); ?>"
                                                     alt="<?php echo htmlspecialchars($student['name']); ?>"
                                                     onerror="this.onerror=null; this.src='data:image/svg+xml,%3Csvg xmlns=\'http://www.w3.org/2000/svg\' width=\'120\' height=\'140\' viewBox=\'0 0 120 140\'%3E%3Crect width=\'120\' height=\'140\' fill=\'%23667eea\'/%3E%3Ctext x=\'50%25\' y=\'50%25\' text-anchor=\'middle\' dy=\'.3em\' fill=\'white\' font-size=\'40\' font-family=\'Arial\'%3E<?php echo $initials; ?>%3C/text%3E%3C/svg%3E';">
                                            <?php else: ?>
                                                <img class="student-img"
                                                     src="data:image/svg+xml,%3Csvg xmlns=\'http://www.w3.org/2000/svg\' width=\'120\' height=\'140\' viewBox=\'0 0 120 140\'%3E%3Crect width=\'120\' height=\'140\' fill=\'%23667eea\'/%3E%3Ctext x=\'50%25\' y=\'50%25\' text-anchor=\'middle\' dy=\'.3em\' fill=\'white\' font-size=\'40\' font-family=\'Arial\'%3E<?php echo $initials; ?>%3C/text%3E%3C/svg%3E"
                                                     alt="<?php echo htmlspecialchars($student['name']); ?>">
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                    <div class="details-area">
                                        <div class="info-row">
                                            <div class="info-label">Name</div>
                                            <div class="info-value">
                                                <?php echo htmlspecialchars($student['name']); ?>
                                            </div>
                                        </div>
                                        <div class="info-row">
                                            <div class="info-label">Class</div>
                                            <div class="info-value">
                                                <?php echo htmlspecialchars($student['major'] ?? 'N/A'); ?>
                                            </div>
                                        </div>
                                        <div class="info-row">
                                            <div class="info-label">Year</div>
                                            <div class="info-value">
                                                <?php echo htmlspecialchars($student['year'] ?? 'N/A'); ?>
                                            </div>
                                        </div>
                                        <div class="info-row">
                                            <div class="info-label">DOB</div>
                                            <div class="info-value">
                                                <?php echo !empty($student['dob']) ? date('d-m-Y', strtotime($student['dob'])) : 'N/A'; ?>
                                            </div>
                                        </div>
                                        <div class="info-row">
                                            <div class="info-label">Address</div>
                                            <div class="info-value">
                                                <?php echo htmlspecialchars($student['address'] ?? 'N/A'); ?>
                                            </div>
                                        </div>
                                        <div class="info-row">
                                            <div class="info-label">Phone</div>
                                            <div class="info-value">
                                                <?php echo htmlspecialchars($student['phone'] ?? 'N/A'); ?>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div class="card-footer-mini">
                                    Smart Student ID | <?php echo htmlspecialchars($student['email'] ?? 'student@university.edu.kh'); ?>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <div class="no-students">
                    <i class="fas fa-users" style="font-size: 50px; color: #cbd5e1;"></i>
                    <h3 style="margin-top: 20px;">No students found in <?php echo htmlspecialchars($college); ?></h3>
                    <p>Please add students to this college to see ID cards.</p>
                    <a href="student.php" class="btn btn-primary mt-3">Add Students</a>
                </div>
            <?php endif; ?>
        </div>
    </div>
    
    <script>
         function toggleRequestDropdown() {
            let menu = document.getElementById("RequestDropdownMenu");
            let icon = document.getElementById("RequestDropdownIcon");
            
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

        // Function to print current page (all cards)
        function printCurrentPage() {
            window.print();
        }
        
        // Function to print all cards (same as print page)
        function printAllCards() {
            window.print();
        }
        
        document.addEventListener('click', function(event) {
            if (!event.target.closest('.dropdown')) {
                document.querySelectorAll('.dropdown').forEach(dropdown => {
                    dropdown.classList.remove('active');
                    const menu = dropdown.querySelector('.dropdown-menu');
                    if (menu) menu.style.display = 'none';
                });
            }
        });
    </script>
</body>
</html>