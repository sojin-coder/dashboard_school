<?php

include 'db.php';
error_reporting(E_ALL);
ini_set('display_errors', 1);

// ការពារ User មិនទាន់ Login បើកចូលលីងនេះផ្ទាល់
if(!isset($_SESSION['id'])){
    header("Location: login.php");
    exit();
}

// ២. បង្កើត Variable ឱ្យបានត្រឹមត្រូវសម្រាប់ដំណើរការ SQL របស់សិស្ស
$logged_in_user_id = $_SESSION['id'];
$user_id = $logged_in_user_id; 

// ទាញទិន្នន័យសិស្សដើម្បីបង្ហាញឈ្មោះនៅលើ Sidebar (ប្រើ $conn ដូចគ្នាទាំងអស់)
$student_query = "SELECT * FROM students WHERE id = '$user_id'";
$student_result = mysqli_query($conn, $student_query);

if(!$student_result){
    die("Query Error (students): " . mysqli_error($conn));
}
$student = mysqli_fetch_assoc($student_result);


// ៣. ដំណើរការប្រអប់ស្វែងរកវគ្គសិក្សា (Course Search)
$search = isset($_GET['search']) ? mysqli_real_escape_string($conn, $_GET['search']) : '';

// បង្កើត SQL query សម្រាប់ទាញទិន្នន័យវគ្គសិក្សា (Courses)
if (!empty($search)) {
    $sql = "SELECT * FROM courses 
            WHERE name LIKE '%$search%' 
               OR teacher_name LIKE '%$search%' 
               OR description LIKE '%$search%' 
            ORDER BY id DESC";
} else {
    $sql = "SELECT * FROM courses ORDER BY id DESC";
}

// Execute logic សម្រាប់តារាង Courses
$result = mysqli_query($conn, $sql);

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
        a { text-decoration: none; }
        html { scroll-behavior: auto; }
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
        .nav-item { display: flex; align-items: center; gap: 15px; padding: 12px 16px; margin-bottom: 8px; border-radius: 14px; cursor: pointer; transition: 0.3s; color: #cbd5e6; }
        .nav-item:hover { background: rgba(255, 255, 255, 0.1); color: white; }
        .nav-item.active { background: #fd0054; color: white; box-shadow: 0 4px 8px rgba(0, 0, 0, 0.2); }
        .nav-bottom { margin-top: 30px; padding-top: 20px; border-top: 1px solid rgba(255,255,255,0.1); }
        .main-content { flex: 1; padding: 28px 32px; background: #f8fafc; overflow-y: auto; height: 100vh; }
        .top-bar { display: flex; justify-content: space-between; align-items: center; padding: 12px 24px; background: white; border-radius: 60px; box-shadow: 0 1px 3px rgba(0,0,0,0.05); margin-bottom: 28px; }
        .page-title h2 { font-size: 1.3rem; font-weight: 600; color: #1e293b; margin: 0; }
        
        /* Table Styles */
        .data-table-wrapper { background: white; border-radius: 20px; overflow-x: auto; box-shadow: 0 5px 20px rgba(0,0,0,0.05); }
        .table { margin-bottom: 0; border-radius: 20px; overflow: hidden; }
        .table thead { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; }
        .table thead th { padding: 15px; font-weight: 600; font-size: 14px; border: none; }
        .table tbody tr:hover { background: #f8f9fc; }
        .table tbody td { padding: 12px 15px; vertical-align: middle; border-bottom: 1px solid #eef2f7; color: #2c3e50; }
        
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
                <img src="https://i.pinimg.com/736x/be/dd/b8/beddb8c8c3c4c967cb821aae0cb796e3.jpg" alt="Logo" />
                <h1><?php 
                    // បង្ហាញឈ្មោះពិតរបស់សិស្សដែលបាន Login
                    echo htmlspecialchars($student['name'] ?? 'Student'); 
                ?></h1>
                <p>Student Dashboard</p>
            </div>
            <div class="nav-menu">
                <a href="forstudent.php" class="nav-item">
                    <i class="fas fa-tachometer-alt"></i> <span>Main Dashboard</span>
                </a>
                <a href="courses_tea.php" class="nav-item active">
                    <i class="fas fa-graduation-cap"></i> <span>Department</span>
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

                <form method="GET" action="" class="search-box mb-3">
                    <input type="text" name="search" placeholder="🔍 Search by name, teacher or description..." 
                           value="<?php echo htmlspecialchars($search); ?>" autocomplete="off">
                    <button type="submit"><i class="fas fa-search"></i> Search</button>
                    <?php if(!empty($search)): ?>
                        <a href="courses_tea.php" class="clear-search"><i class="fas fa-times"></i> Clear</a>
                    <?php endif; ?>
                </form>

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
                                    echo "<td>" . htmlspecialchars($row['name'] ?? '') . "</td>";
                                    echo "<td>" . htmlspecialchars($row['time_star'] ?? '') . "</td>";
                                    echo "<td>" . htmlspecialchars($row['time_end'] ?? '') . "</td>";
                                    echo "<td>" . htmlspecialchars($row['description'] ?? '') . "</td>";
                                    echo "<td>" . ($row['teacher_id'] ?? '') . "</td>";
                                    echo "<td>" . htmlspecialchars($row['teacher_name'] ?? '') . "</td>";
                                    echo "<td>" . htmlspecialchars($row['teacher_phone'] ?? '') . "</td>";
                                    echo "<td>" . htmlspecialchars($row['Shift'] ?? '') . "</td>";
                                    echo "<td>" . htmlspecialchars($row['duration'] ?? '') . "</td>";
                                    echo "</tr>";
                                }
                            } else {
                                echo "<tr><td colspan='10' class='text-center'>No data found</td></tr>";
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