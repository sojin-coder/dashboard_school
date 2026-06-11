                  

<?php
                  include 'db.php';
                  error_reporting(E_ALL);
                  ini_set('display_errors', 1);
                  
                  // Handle search
                  $search = isset($_GET['search']) ? mysqli_real_escape_string($conn, $_GET['search']) : '';
                //   $sql = "SELECT * FROM students ORDER BY id DESC";
                $sql = "SELECT * FROM students 
                         WHERE college = 'it'
                         ORDER BY id DESC";
               
                  
                  if (!empty($search)) {
                      $sql = "SELECT * FROM students WHERE name LIKE '%$search%' OR email LIKE '%$search%' OR phone LIKE '%$search%' ORDER BY id DESC";
                  }
                  
                  // Handle AJAX request for getting student data
                  if(isset($_GET['ajax']) && $_GET['ajax'] == 'get_student' && isset($_GET['id'])) {
                      $id = intval($_GET['id']);
                      $query = "SELECT * FROM students  WHERE id = $id";
                    // $query = "SELECT * FROM students WHERE college IN ('it')";
                      $result = mysqli_query($conn, $query);
                      if($row = mysqli_fetch_assoc($result)) {
                          header('Content-Type: application/json');
                          echo json_encode($row);
                      }
                      exit;
                  }
                  
                 
                  
                  // Fetch all students from database
                  $result = mysqli_query($conn, $sql);
                  if (!$result) {
                      echo "<div class='alert alert-danger mt-3'>Error: " . mysqli_error($conn) . "</div>";
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
    <title>KRaksa Education Suite</title>
    <style>
        /* Dropdown */
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

        /* ----table---- */
        .table-container {
            max-height: 500px;
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
            z-index: 100;
        }
        
        * { 
            margin: 0; 
            padding: 0; 
            box-sizing: border-box; 
        }
        
        a {
            text-decoration: none;
        }
        
        html {
            scroll-behavior: auto;
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
            /* background: linear-gradient(90deg,rgba(180, 58, 143, 1) 0%, rgba(253, 29, 29, 1) 50%, rgba(255, 154, 13, 1) 100%); */
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
            border-bottom: 1px solid rgba(255, 255, 255, 0.1); 
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
        }
        
        .sidebar-header h1 { 
            font-size: 1.8rem; 
            font-weight: 700; 
            color: white; 
            margin-top: 10px; 
        }
        
        .sidebar-header p { 
            font-size: 0.85rem; 
            opacity: 0.8; 
        }
        
        .nav-menu { 
            flex: 1; 
            padding: 0 16px; 
        }
        
        .nav-item { 
            display: flex; 
            align-items: center; 
            gap: 15px; 
            padding: 12px 16px; 
            margin-bottom: 8px; 
            border-radius: 14px; 
            cursor: pointer; 
            transition: 0.3s; 
            color: #cbd5e6; 
        }
        
        .nav-item:hover { 
            background: rgba(255, 255, 255, 0.1); 
            color: white; 
        }
        
        .nav-item.active { 
            background: #fd0054; 
            color: white; 
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.2); 
        }
        
        .nav-bottom { 
            margin-top: 30px; 
            padding-top: 20px; 
            border-top: 1px solid rgba(255,255,255,0.1); 
        }
        
        .main-content { 
            flex: 1; 
            padding: 28px 32px; 
            background: #f8fafc; 
            overflow-y: auto; 
            height: 100vh; 
        }
        
        .top-bar { 
            display: flex; 
            justify-content: space-between; 
            align-items: center; 
            padding: 12px 24px; 
            background: white; 
            border-radius: 60px; 
            box-shadow: 0 1px 3px rgba(0,0,0,0.05); 
            margin-bottom: 28px; 
        }
        
        .page-title h2 { 
            font-size: 1.3rem; 
            font-weight: 600; 
            color: #1e293b; 
            margin: 0; 
        }
        
        .search-box {
            display: flex;
            gap: 10px;
            align-items: center;
            margin: 15px 0;
        }
        
        .search-box input {
            border-radius: 30px;
            padding: 8px 20px;
            border: 1px solid #e0e4e8;
            width: 500px;
            transition: all 0.2s ease;
        }
        
        .search-box input:focus {
            border-color: #667eea;
            box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
            outline: none;
        }
        
        .search-box button {
            border-radius: 30px;
            padding: 8px 20px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border: none;
            transition: all 0.2s ease;
        }
        
        .search-box button:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(102, 126, 234, 0.4);
        }
        
        .clear-search {
            border-radius: 30px;
            padding: 8px 20px;
            background: #6c757d;
            color: white;
            text-decoration: none;
            transition: all 0.2s ease;
        }
        
        .clear-search:hover {
            background: #5a6268;
            color: white;
        }
        
        .result-count {
            font-size: 14px;
            color: #6c757d;
            margin-left: 15px;
        }
        
        .btn-primary {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border: none;
            padding: 10px 25px;
            border-radius: 10px;
            font-weight: 600;
        }
        
        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(102, 126, 234, 0.4);
        }
        
        .btn-warning, .btn-danger {
            padding: 5px 12px;
            font-size: 12px;
            border-radius: 8px;
            margin: 0 3px;
        }
        
        .kpi-row { 
            display: flex; 
            flex-wrap: wrap; 
            gap: 20px; 
            margin-bottom: 32px; 
        }
        
        .kpi-card { 
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border-radius: 20px; 
            padding: 20px 24px; 
            flex: 1; 
            min-width: 180px;
            color: white;
            box-shadow: 0 10px 25px rgba(0,0,0,0.1);
        }
        
        .kpi-card:nth-child(2) { 
            background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%); 
        }
        
        .kpi-card:nth-child(3) { 
            background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%); 
        }
        
        .kpi-title { 
            font-size: 14px; 
            text-transform: uppercase; 
            letter-spacing: 1px; 
            opacity: 0.9; 
            margin-bottom: 10px; 
            font-weight: 500; 
        }
        
        .kpi-number { 
            font-size: 32px; 
            font-weight: bold; 
            margin-bottom: 5px; 
        }
        
        .alert-info {
            background: #e3f2fd;
            border: none;
            border-radius: 12px;
            padding: 15px 20px;
            color: #0c5460;
        }
        
        .loading {
            text-align: center;
            padding: 40px;
            color: #667eea;
        }
        
        /* Modal styles */
        .modal-custom .modal-content {
            border-radius: 20px;
            border: none;
        }
        
        .modal-custom .modal-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border-radius: 20px 20px 0 0;
            border: none;
        }
        
        .modal-custom .modal-header .btn-close {
            filter: brightness(0) invert(1);
        }
        
        .modal-custom .modal-body {
            padding: 25px;
            max-height: 70vh;
            overflow-y: auto;
        }
        .more{
            text-align: right;
            margin-bottom: 15px;
        }
        .more button {
            width: 150px;
            padding: 10px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border: none;
            border-radius: 10px;
            transition: all 0.2s ease;
        }
        
        .more button:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(102, 126, 234, 0.4);
        }
        
        .form-label {
            font-weight: 600;
            color: #2c3e50;
            margin-bottom: 8px;
            font-size: 14px;
        }
        
        .form-control, .form-select {
            border-radius: 10px;
            border: 1px solid #e0e4e8;
            padding: 10px 15px;
            transition: all 0.2s ease;
        }
        
        .form-control:focus, .form-select:focus {
            border-color: #667eea;
            box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
            outline: none;
        }
        
        @media (max-width: 768px) {
            .sidebar { width: 80px; }
            .sidebar-header h1, .sidebar-header p, .nav-item span { display: none; }
            .nav-item { justify-content: center; }
            .main-content { padding: 15px; }
            .top-bar { flex-direction: column; gap: 10px; }
            .search-box { width: 100%; }
            .search-box input { flex: 1; }
        }
        
        .loading-opacity {
            opacity: 0.6;
            pointer-events: none;
        }
    </style>
</head>
<body>
    <div class="container">
        <!-- Sidebar -->
        <div class="sidebar">
           <div class="sidebar-header">
            <img src="https://i.pinimg.com/1200x/0c/34/fe/0c34fe7dd3df6d5a90703a3e871db5ef.jpg" alt="Logo" />
            <h1>Teacher</h1>
            <p>Education Suite</p>
        </div>
            <div class="nav-menu">
                <a href="forteacher.php" class="nav-item" data-page="dashboard">
                    <i class="fas fa-tachometer-alt"></i> <span>Main Dashboard</span>
                </a>
                <!-- Students Dropdown -->
                    <div class="dropdown-container">

                <div class="nav-item dropdown-btn" onclick="toggleDropdown()">
                    <div>
                        <i class="fas fa-user-graduate"></i>
                        <span>Students</span>
                    </div>
            
                    <i class="fas fa-chevron-down dropdown-icon" id="dropdownIcon"></i>
                </div>

                <div class="dropdown-menus" id="studentDropdown">
            
                    <a href="list_for_teacher.php" class="nav-item sub-menu active">
                        <i class="fas fa-users"></i>
                        <span>Student List</span>
                    </a>
            
                    
            
                    <a href="view_for_teacher.php" class="nav-item sub-menu">
                        <i class="fas fa-eye"></i>
                        <span>Student View</span>
                    </a>
            
                   
            
                    <a href="score_for_teacher.php" class="nav-item sub-menu">
                        <i class="fas fa-chart-line"></i>
                        <span>Student Scores</span>
                    </a>
            
                </div>
                </div>

                
                <a href="courses_tea.php" class="nav-item" data-page="courses">
                    <i class="fas fa-graduation-cap"></i> <span>Department</span>
                </a>
                
                
                <a href="attendanceAll_tea.php" class="nav-item" data-page="attendance">
                    <i class="fas fa-calendar-check"></i> <span>Attendance</span>
                </a>
                <div class="nav-bottom">
                    <a href="logout.php" class="nav-item" style="padding-left:8px;">
                        <i class="fas fa-sign-out-alt"></i> <span>Logout</span>
                    </a>
                </div>
            </div>
        </div>

        <!-- Main Content -->
        <div class="main-content">
            <!-- Top Bar -->
            <div class="top-bar">
                <div class="page-title">
                    <h2 id="dynamicTitle">Student Management</h2>
                </div>
            </div>

           

            <!-- Search Box -->
            <form method="GET" action="" class="search-box">
                <input type="text" name="search" placeholder="🔍 Search by name, email or phone..." 
                       value="<?php echo htmlspecialchars($search); ?>" autocomplete="off">
                <button type="submit"><i class="fas fa-search"></i> Search</button>
                <?php if(!empty($search)): ?>
                    <a href="student.php" class="clear-search"><i class="fas fa-times"></i> Clear</a>
                <?php endif; ?>
            </form>

            <!-- Result count display -->
            <?php if(!empty($search)): ?>
                <div class="result-count">
                    <i class="fas fa-search"></i> Search results for: <strong>"<?php echo htmlspecialchars($search); ?>"</strong>
                    (<?php echo mysqli_num_rows($result); ?> records found)
                </div>
            <?php endif; ?>

            <!-- Students Table -->
            <div class="table-container">
                <table class="table table-bordered table-striped">
                    <thead class="table-light">
                        <tr>
                            <th>ID</th>
                            <th>Name</th>
                            <th>Email</th>
                            <th>Phone</th>
                            <th>Gender</th>
                            <th>Age</th>
                            <th>Date of Birth</th>
                            <th>Address</th>
                            <th>Grade</th>
                            <th>College</th>
                            <th>Skill</th>
                            <th> Year </th>
                            <th>Shift</th>
                            <!-- <th>Actions</th> -->
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (isset($result) && mysqli_num_rows($result) > 0) {
                            while($row = mysqli_fetch_assoc($result)) { ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($row['id']); ?></td>
                                    <td><?php echo htmlspecialchars($row['name']); ?></td>
                                    <td><?php echo htmlspecialchars($row['email']); ?></td>
                                    <td><?php echo htmlspecialchars($row['phone']); ?></td>
                                    <td><?php echo htmlspecialchars($row['gender']); ?></td>
                                    <td><?php echo htmlspecialchars($row['age']); ?></td>
                                    <td><?php echo htmlspecialchars($row['dob']); ?></td>
                                    <td><?php echo htmlspecialchars($row['address']); ?></td>
                                    <td><?php echo htmlspecialchars($row['grade']); ?></td>
                                    <td><?php echo htmlspecialchars($row['college']); ?></td>
                                    <td><?php echo htmlspecialchars($row['skill']); ?></td>
                                    <td><?php echo htmlspecialchars($row['year']); ?></td>
                                    <td><?php echo htmlspecialchars($row['Shift']); ?></td>
                                    
                                </tr>
                            <?php }
                        } else { ?>
                            <tr>
                                <td colspan='14' class='text-center'>
                                    <i class='fas fa-user-slash'></i> No students found. 
                                    <?php echo !empty($search) ? "Please try another search term." : "Click 'Add More' to add a student."; ?>
                                </td>
                            </tr>
                        <?php } ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    
   
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
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
   
</body>
</html>