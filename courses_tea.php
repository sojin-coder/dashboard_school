<?php
include 'db.php';
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Handle search
$search = isset($_GET['search']) ? mysqli_real_escape_string($conn, $_GET['search']) : '';

// Build the SQL query - ONLY ONCE
if (!empty($search)) {
    // Fixed: Search in name, teacher_name, or description
    $sql = "SELECT * FROM courses 
            WHERE name LIKE '%$search%' 
               OR teacher_name LIKE '%$search%' 
               OR description LIKE '%$search%' 
            ORDER BY id DESC";
} else {
    $sql = "SELECT * FROM courses ORDER BY id DESC";
}

// Execute the query once
$result = mysqli_query($conn, $sql);

// Check for query error
if (!$result) {
    die("Query failed: " . mysqli_error($conn));
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

         /* ----search------- */
         .search-box {
            display: flex;
            gap: 10px;
            align-items: center;
        }
        
        .search-box input {
            border-radius: 30px;
            padding: 8px 20px;
            border: 1px solid #e0e4e8;
            width: 250px;
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
            color: #dc3545;
            text-decoration: none;
            padding: 8px 15px;
            border-radius: 30px;
            background: #fff;
            border: 1px solid #dc3545;
            transition: all 0.2s ease;
        }
        
        .clear-search:hover {
            background: #dc3545;
            color: white;
        }
        
         /* ----table---- */
        .table-container {
            max-height: 400px;
            overflow-y: auto;
            border: 1px solid #dee2e6;
            border-radius: 8px;
        }

        /* Sticky Header */
        .table-container thead th {
            position: sticky;
            top: 0;
            background-color: #f8f9fa;
            z-index: 1;
            border-bottom: 2px solid #dee2e6;
        }
        
        * { margin: 0; padding: 0; box-sizing: border-box; }
        a {
            text-decoration: none;
        }
        html {
            scroll-behavior: auto;
        }
        body { font-family: "Inter", sans-serif; background: #c5e1fc; color: #0f172a; overflow-x: hidden; }
        .container { display: flex; min-height: 100vh; max-width: 100%; }
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
        .sidebar-header { padding: 28px 24px; border-bottom: 1px solid rgba(255, 255, 255, 0.1); margin-bottom: 24px; text-align: center; }
        .sidebar-header img { width: 100px; height: 100px; border-radius: 50%; margin: auto; display: block; border: 4px solid white; object-fit: cover; }
        .sidebar-header h1 { font-size: 1.8rem; font-weight: 700; color: white; margin-top: 10px; }
        .sidebar-header p { font-size: 0.85rem; opacity: 0.8; }
        .nav-menu { flex: 1; padding: 0 16px; }
        .nav-item { display: flex; align-items: center; gap: 15px; padding: 12px 16px; margin-bottom: 8px; border-radius: 14px; cursor: pointer; transition: 0.3s; color: #cbd5e6; }
        .nav-item:hover { background: rgba(255, 255, 255, 0.1); color: white; }
        .nav-item.active { background: #fd0054; color: white; box-shadow: 0 4px 8px rgba(0, 0, 0, 0.2); }
        .nav-bottom { margin-top: 30px; padding-top: 20px; border-top: 1px solid rgba(255,255,255,0.1); }
        .main-content { flex: 1; padding: 28px 32px; background: #f8fafc; overflow-y: auto; height: 100vh; }
        .top-bar { display: flex; justify-content: space-between; align-items: center; padding: 12px 24px; background: white; border-radius: 60px; box-shadow: 0 1px 3px rgba(0,0,0,0.05); margin-bottom: 28px; }
        .page-title h2 { font-size: 1.3rem; font-weight: 600; color: #1e293b; margin: 0; }
        
        /* Form Container Styles */
        .form-container {
            background: white;
            border-radius: 20px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.08);
            margin-bottom: 40px;
            overflow: hidden;
        }
        .header-banner {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 18px 25px;
            font-size: 20px;
            font-weight: 600;
        }
        .form-body {
            padding: 25px;
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
        
        /* Table Styles */
        .data-table-wrapper {
            background: white;
            border-radius: 20px;
            overflow-x: auto;
            box-shadow: 0 5px 20px rgba(0,0,0,0.05);
        }
        .table {
            margin-bottom: 0;
            border-radius: 20px;
            overflow: hidden;
        }
        .table thead {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
        }
        .table thead th {
            padding: 15px;
            font-weight: 600;
            font-size: 14px;
            border: none;
        }
        .table tbody tr:hover {
            background: #f8f9fc;
        }
        .table tbody td {
            padding: 12px 15px;
            vertical-align: middle;
            border-bottom: 1px solid #eef2f7;
            color: #2c3e50;
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
        
        /* KPI Cards */
        .kpi-row { display: flex; flex-wrap: wrap; gap: 20px; margin-bottom: 32px; }
        .kpi-card { 
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border-radius: 20px; 
            padding: 20px 24px; 
            flex: 1; 
            min-width: 180px;
            color: white;
            box-shadow: 0 10px 25px rgba(0,0,0,0.1);
        }
        .kpi-card:nth-child(2) { background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%); }
        .kpi-card:nth-child(3) { background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%); }
        .kpi-title { font-size: 14px; text-transform: uppercase; letter-spacing: 1px; opacity: 0.9; margin-bottom: 10px; font-weight: 500; }
        .kpi-number { font-size: 32px; font-weight: bold; margin-bottom: 5px; }
        
        /* Alert */
        .alert-info {
            background: #e3f2fd;
            border: none;
            border-radius: 12px;
            padding: 15px 20px;
            color: #0c5460;
        }
        
        /* Loading */
        .loading {
            text-align: center;
            padding: 40px;
            color: #667eea;
        }
        
        @media (max-width: 768px) {
            .sidebar { width: 80px; }
            .sidebar-header h1, .sidebar-header p, .nav-item span { display: none; }
            .nav-item { justify-content: center; }
            .main-content { padding: 15px; }
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
                               
                <a href="courses_tea.php" class="nav-item active">
                    <i class="fas fa-graduation-cap"></i> <span>Department</span>
                </a>
                
                <a href="attendance_top_teacher.php" class="nav-item">
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
                <div class="page-title"><h2 id="dynamicTitle">Courses</h2></div>
            </div>
            <div class="container_top">

               

                <!-- Search Box -->
                <form method="GET" action="" class="search-box mb-3">
                    <input type="text" name="search" placeholder="🔍 Search by name, teacher or description..." 
                           value="<?php echo htmlspecialchars($search); ?>" autocomplete="off">
                    <button type="submit"><i class="fas fa-search"></i> Search</button>
                    <?php if(!empty($search)): ?>
                        <a href="Courses.php" class="clear-search"><i class="fas fa-times"></i> Clear</a>
                    <?php endif; ?>
                </form>

                <!-- Table Container -->
                <div class="table-container">
                    <table class="table table-bordered table-striped">
                        <thead class="table-light">
                            <tr>
                                <th>ID</th>
                                <th>Name</th>
                                <th>Time Start</th>
                                <th>Time End</th>
                                <th>Description</th>
                                <th>Teacher ID</th>
                                <th>Teacher Name</th>
                                <th>Teacher Phone</th>
                                <th>Price</th>
                                <th>Shift</th>
                                <th>Duration</th>
                                
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            if (mysqli_num_rows($result) > 0) {
                                while($row = mysqli_fetch_assoc($result)) {
                                    echo "<tr>";
                                    echo "<td>" . $row['id'] . "</td>";
                                    echo "<td>" . htmlspecialchars($row['name']) . "</td>";
                                    echo "<td>" . htmlspecialchars($row['time_star']) . "</td>";
                                    echo "<td>" . htmlspecialchars($row['time_end']) . "</td>";
                                    echo "<td>" . htmlspecialchars($row['description']) . "</td>";
                                    echo "<td>" . $row['teacher_id'] . "</td>";
                                    echo "<td>" . htmlspecialchars($row['teacher_name']) . "</td>";
                                    echo "<td>" . htmlspecialchars($row['teacher_phone']) . "</td>";
                                    echo "<td>" . htmlspecialchars($row['price']) . "</td>";
                                    echo "<td>" . htmlspecialchars($row['Shift']) . "</td>";
                                    echo "<td>" . htmlspecialchars($row['duration']) . "</td>";
                                   
                                    echo "</tr>";
                                }
                            } else {
                                echo "<tr><td colspan='12' class='text-center'>No data found</td></tr>";
                            }
                            ?>
                        </tbody>
                    </table>
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
</body>
</html>