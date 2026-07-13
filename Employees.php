<?php
include 'db.php';

error_reporting(E_ALL);
ini_set('display_errors', 1);

// Handle search
$search = isset($_GET['search']) ? mysqli_real_escape_string($conn, $_GET['search']) : '';

// Build SQL query based on search
if (!empty($search)) {
    $sql = "SELECT * FROM employees WHERE name LIKE '%$search%' OR email LIKE '%$search%' OR phone LIKE '%$search%' ORDER BY id DESC";
} else {
    $sql = "SELECT * FROM employees ORDER BY id DESC";
}

// Execute query
$result = mysqli_query($conn, $sql);

// Check if query failed
if (!$result) {
    die("Query failed: " . mysqli_error($conn));
}

// Handle AJAX request for getting teacher data
if(isset($_GET['ajax']) && $_GET['ajax'] == 'get_teacher' && isset($_GET['id'])) {
    $id = intval($_GET['id']);
    $query = "SELECT * FROM employees WHERE id = $id";
    $result_ajax = mysqli_query($conn, $query);
    if($row = mysqli_fetch_assoc($result_ajax)) {
        header('Content-Type: application/json');
        echo json_encode($row);
    }
    exit;
}

// Get message from URL parameters
$msg = isset($_GET['msg']) ? $_GET['msg'] : '';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <title>KRaksa Education Suite - Employees</title>
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
        /* Search Box */
        .search-box {
            display: flex;
            gap: 10px;
            align-items: center;
            margin-bottom: 20px;
            padding: 10px;
            background: white;
            border-radius: 10px;
        }
        
        .search-box form {
            display: flex;
            gap: 10px;
            width: 100%;
            max-width: 500px;
        }
        
        .search-box input {
            border-radius: 30px;
            padding: 8px 20px;
            border: 1px solid #e0e4e8;
            flex: 1;
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
            display: inline-flex;
            align-items: center;
            gap: 5px;
        }
        
        .clear-search:hover {
            background: #5a6268;
            color: white;
        }
        
        .result-count {
            font-size: 14px;
            color: #6c757d;
            margin-left: 15px;
            margin-bottom: 15px;
        }
        
        /* Table Container */
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
        }

        * { margin: 0; padding: 0; box-sizing: border-box; }
        a { text-decoration: none; }
        html { scroll-behavior: auto; }
        
        body { 
            font-family: "Inter", sans-serif; 
            background: #c5e1fc; 
            color: #0f172a; 
            overflow-x: hidden; 
        }
        
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
        
        .sidebar-header h1 { font-size: 1.8rem; font-weight: 700; color: white; margin-top: 10px; }
        .sidebar-header p { font-size: 0.85rem; opacity: 0.8; }
        .nav-menu { flex: 1; padding: 0 16px; }
        
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
        
        .nav-item:hover { background: rgba(255, 255, 255, 0.1); color: white; }
        .nav-item.active { background: #fd0054; color: white; box-shadow: 0 4px 8px rgba(0, 0, 0, 0.2); }
        .nav-bottom { margin-top: 30px; padding-top: 20px; border-top: 1px solid rgba(255,255,255,0.1); }
        
        .main-content { flex: 1; padding: 28px 32px; background: #f8fafc; overflow-y: auto; height: 100vh; }
        
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
        
        /* Alert */
        .alert-info {
            background: #e3f2fd;
            border: none;
            border-radius: 12px;
            padding: 15px 20px;
            color: #0c5460;
        }
        
        .alert-success {
            background: #d4edda;
            border: none;
            border-radius: 12px;
            padding: 12px 20px;
            color: #155724;
            margin-bottom: 20px;
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
                <a href="index.php" class="nav-item" data-page="dashboard"><i class="fas fa-tachometer-alt"></i> <span>Main Dashboard</span></a>
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
                <!-- <a href="card_stuIT.php" class="nav-item" data-page="card"><i class="fas fa-id-card"></i> <span>ID Card</span></a> -->
                <!-- <a href="Reports.php" class="nav-item" data-page="reports"><i class="fas fa-file-alt"></i> <span>Reports</span></a> -->
                <a href="#" class="nav-item active" data-page="employees"><i class="fas fa-user-friends"></i> <span>Employees</span></a>
                <!-- <a href="StudentAttendance.php" class="nav-item" data-page="attendance"><i class="fas fa-calendar-check"></i> <span>Attendance</span></a> -->
                 <a href="attendance_admin.php" class="nav-item "><i class="fas fa-clipboard-check"></i> <span>Attendance Admin</span></a>
                <div class="nav-bottom">
                <a href="logout.php" class="nav-item" style="padding-left:8px;">
                    <i class="fas fa-sign-out-alt"></i> <span>Logout</span>
                </a>
            </div>
            </div>
        </div>

        <div class="main-content">
            <div class="top-bar">
                <div class="page-title"><h2 id="dynamicTitle">Employee Management</h2></div>
            </div>

            <!-- Success/Error Message -->
            <?php if($msg): ?>
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <?php 
                    if($msg == 'added') echo '✅ Employee added successfully!';
                    elseif($msg == 'updated') echo '✅ Employee updated successfully!';
                    elseif($msg == 'deleted') echo '✅ Employee deleted successfully!';
                    elseif($msg == 'error') echo '❌ An error occurred. Please try again!';
                ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
            <?php endif; ?>
           
            <!-- Search Box -->
            <div class="search-box">
                <form method="GET" action="Employees.php">
                    <input type="text" name="search" placeholder="🔍 Search by name, email or phone..." 
                           value="<?php echo htmlspecialchars($search); ?>" autocomplete="off">
                    <button type="submit"><i class="fas fa-search"></i> Search</button>
                    <?php if(!empty($search)): ?>
                        <a href="Employees.php" class="clear-search"><i class="fas fa-times"></i> Clear</a>
                    <?php endif; ?>
                </form>
            </div>
            
            <!-- Result count display -->
            <?php if(!empty($search)): ?>
                <div class="result-count">
                    <i class="fas fa-search"></i> Search results for: <strong>"<?php echo htmlspecialchars($search); ?>"</strong>
                    (<?php echo mysqli_num_rows($result); ?> record(s) found)
                </div>
            <?php endif; ?>
            
            <!-- Employee List Table -->
            <div class="data-table-wrapper mt-4">
                <div style="padding: 15px 20px; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; border-radius: 20px 20px 0 0;">
                    <h5 class="mb-0"><i class="fas fa-users"></i> Employee List</h5>
                </div>
                
                <div class="table-container">
                    <table class="table table-bordered mb-0">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Name</th>
                                <th>Email</th>
                                <th>Phone</th>
                                <th>Gender</th>
                                <th>Position</th>
                                <th>Salary</th>
                                <th>Hire Date</th>
                                <th>Address</th>
                                <th>Status</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            if (mysqli_num_rows($result) > 0) {
                                while($row = mysqli_fetch_assoc($result)) {
                                    // Status badge color
                                    $statusClass = '';
                                    switch($row['status']) {
                                        case 'Active': $statusClass = 'badge bg-success'; break;
                                        case 'Inactive': $statusClass = 'badge bg-secondary'; break;
                                        case 'Resigned': $statusClass = 'badge bg-warning'; break;
                                        case 'Terminated': $statusClass = 'badge bg-danger'; break;
                                        case 'On Leave': $statusClass = 'badge bg-info'; break;
                                        default: $statusClass = 'badge bg-light text-dark';
                                    }
                                    
                                    echo "<tr>";
                                    echo "<td>" . $row['id'] . "</td>";
                                    echo "<td><strong>" . htmlspecialchars($row['name']) . "</strong></td>";
                                    echo "<td>" . htmlspecialchars($row['email']) . "</td>";
                                    echo "<td>" . htmlspecialchars($row['phone']) . "</td>";
                                    echo "<td>" . $row['gender'] . "</td>";
                                    echo "<td>" . htmlspecialchars($row['positive']) . "</td>";
                                    echo "<td>$" . number_format($row['salary'], 2) . "</td>";
                                    echo "<td>" . date('d/m/Y', strtotime($row['hire_date'])) . "</td>";
                                    echo "<td>" . htmlspecialchars($row['address']) . "</td>";
                                    echo "<td><span class='{$statusClass}'>" . $row['status'] . "</span></td>";
                                    echo "<td>
                                            <a href='edit_employee.php?id=" . $row['id'] . "' class='btn btn-warning btn-sm mb-2' title='Edit'>
                                                <i class='fas fa-edit'></i>
                                            </a>
                                            <a href='delete_employee.php?id=" . $row['id'] . "' class='btn btn-danger btn-sm' title='Delete' onclick='return confirm(\"Are you sure you want to delete this employee?\")'>
                                                <i class='fas fa-trash'></i>
                                            </a>
                                           </a>
                                         </i>
                                           
                                           </a>";
                                    echo "</tr>";
                                }
                            } else {
                                echo "<tr><td colspan='11' class='text-center py-4'>📋 No employee data found. Please add an employee above.</td></tr>";
                            }
                            ?>
                        </tbody>
                    </table>
                </div>
            </div>
            
            <!-- Employee Registration Form -->
            <div class="form-container mt-5">
                <div class="header-banner">
                    <i class="fas fa-user-plus"></i> Employee Registration Form
                </div>
                
                <form action="processem.php" method="POST" class="form-body">
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label class="form-label"><i class="fas fa-user"></i> Full Name:</label>
                            <input type="text" name="name" class="form-control" placeholder="Enter full name" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label"><i class="fas fa-venus-mars"></i> Gender:</label>
                            <select name="gender" class="form-select" required>
                                <option selected disabled>-- Select Gender --</option>
                                <option value="Male">Male</option>
                                <option value="Female">Female</option>
                                <option value="Other">Other</option>
                            </select>
                        </div>
                    </div>
                    
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label class="form-label"><i class="fas fa-envelope"></i> Email:</label>
                            <input type="email" name="email" class="form-control" placeholder="example@email.com" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label"><i class="fas fa-phone"></i> Phone:</label>
                            <input type="tel" name="phone" class="form-control" placeholder="012 345 678" pattern="[0-9]{9,12}" required>
                        </div>
                    </div>

                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label class="form-label"><i class="fas fa-calendar-alt"></i> Age:</label>
                            <input type="number" name="age" class="form-control" min="18" max="70" placeholder="18-70">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label"><i class="fas fa-briefcase"></i> Position / Department:</label>
                            <select name="positive" class="form-select" required>
                                <option selected disabled>-- Select Position --</option>
                                <option value="IT">💻 IT (Information Technology)</option>
                                <option value="Management">📊 Management</option>
                                <option value="Marketing">📢 Marketing</option>
                                <option value="Accounting">🧮 Accounting</option>
                                <option value="Electrical Engineering">⚡ Electrical Engineering</option>
                                <option value="Electronics">🔌 Electronics</option>
                                <option value="Civil Engineering">🏗️ Civil Engineering</option>
                                <option value="HR">👥 HR</option>
                                <option value="ACC">💰 ACC</option>
                                <option value="Administration Department">📋 Administration Department</option>
                            </select>
                        </div>
                    </div>

                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label class="form-label"><i class="fas fa-dollar-sign"></i> Salary ($):</label>
                            <input type="number" name="salary" class="form-control" step="0.01" min="0" placeholder="0.00">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label"><i class="fas fa-calendar-day"></i> Hire Date:</label>
                            <input type="date" name="hire_date" class="form-control">
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label"><i class="fas fa-map-marker-alt"></i> Address:</label>
                        <input type="text" name="address" class="form-control" placeholder="Enter address">
                    </div>

                    <div class="mb-3">
                        <label class="form-label"><i class="fas fa-toggle-on"></i> Status:</label>
                        <select name="status" class="form-select" required>
                            <option selected disabled>-- Select Status --</option>
                            <option value="Active">✅ Active</option>
                            <option value="Inactive">⭕ Inactive</option>
                            <option value="Resigned">📤 Resigned</option>
                            <option value="Terminated">❌ Terminated</option>
                            <option value="On Leave">🏖️ On Leave</option>
                        </select>
                    </div>

                    <div class="mt-4">
                        <button type="submit" class="btn btn-primary px-4">
                            <i class="fas fa-save"></i> Save Employee
                        </button>
                        <button type="reset" class="btn btn-danger px-4">
                            <i class="fas fa-eraser"></i> Clear
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
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

</script>
</body>
</html>