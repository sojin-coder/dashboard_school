<?php
include 'db.php';

error_reporting(E_ALL);
ini_set('display_errors', 1);

// Handle search
$search = isset($_GET['search']) ? mysqli_real_escape_string($conn, $_GET['search']) : '';
$sql = "SELECT * FROM teachers ORDER BY id DESC";

if (!empty($search)) {
    $sql = "SELECT * FROM teachers WHERE name LIKE '%$search%' OR email LIKE '%$search%' OR phone LIKE '%$search%' ORDER BY id DESC";
}

// Handle AJAX request for getting teacher data
if(isset($_GET['ajax']) && $_GET['ajax'] == 'get_teacher' && isset($_GET['id'])) {
    $id = intval($_GET['id']);
    $query = "SELECT * FROM teachers WHERE id = $id";
    $result = mysqli_query($conn, $query);
    if($row = mysqli_fetch_assoc($result)) {
        header('Content-Type: application/json');
        echo json_encode($row);
    }
    exit;
}

// Handle AJAX request for updating teacher
if($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_GET['ajax']) && $_GET['ajax'] == 'update_teacher') {
    $id = intval($_POST['id']);
    $name = mysqli_real_escape_string($conn, $_POST['name']);
    $gender = mysqli_real_escape_string($conn, $_POST['gender']);
    $image = mysqli_real_escape_string($conn, $_POST['image']);
    $email = mysqli_real_escape_string($conn, $_POST['email']);
    $phone = mysqli_real_escape_string($conn, $_POST['phone']);
    $address = mysqli_real_escape_string($conn, $_POST['address']);
    $subject = mysqli_real_escape_string($conn, $_POST['subject']);
    
    $update_sql = "UPDATE teachers SET 
                   name='$name', 
                   gender='$gender', 
                   image='$image', 
                   email='$email', 
                   phone='$phone', 
                   address='$address', 
                   subject='$subject'
                   WHERE id=$id";
    
    if(mysqli_query($conn, $update_sql)) {
        echo json_encode(['success' => true, 'message' => 'Teacher updated successfully']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Error: ' . mysqli_error($conn)]);
    }
    exit;
}

// Get total teachers count for display
$total_teachers_query = "SELECT COUNT(*) as total FROM teachers";
$total_result = mysqli_query($conn, $total_teachers_query);
$total_teachers = mysqli_fetch_assoc($total_result)['total'];

// Get the filtered result for display
$result = mysqli_query($conn, $sql);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <title>KRaksa Education Suite - Teachers</title>
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

        /* ----search------- */
        .search-box {
            display: flex;
            gap: 10px;
            align-items: center;
            margin-bottom: 20px;
            padding: 10px;
            background: white;
            border-radius: 10px;
        }
        
        .search-box input {
            border-radius: 30px;
            padding: 8px 20px;
            border: 1px solid #e0e4e8;
            width: 300px;
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
        
        /* ----table---- */
        .table-container {
            max-height: 600px;
            overflow-y: auto;
            border: 1px solid #dee2e6;
            border-radius: 8px;
            background: white;
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
        .nav-bottom { margin-top: 30px; padding-top: 20px; border-top: 1px solid rgba(255,255,255,0.1); text-decoration: none;}
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
        
        /* Image Styles */
        .teacher-img {
            width: 50px;
            height: 50px;
            object-fit: cover;
            border-radius: 10px;
            border: 2px solid #e0e4e8;
        }
        
        .teacher-img-placeholder {
            width: 50px;
            height: 50px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 20px;
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
        }
        
        .loading-opacity { 
            opacity: 0.6; 
            pointer-events: none; 
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
                <h1>Teacher</h1>
                <p>Education Suite</p>
            </div>
            <div class="nav-menu">
                <a href="index.php" class="nav-item"><i class="fas fa-tachometer-alt"></i> <span>Main Dashboard</span></a>
               
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

                <a href="teacher.php" class="nav-item active"><i class="fas fa-chalkboard-teacher"></i> <span>Teachers</span></a>
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
                <div class="nav-bottom" href="logout.php">
                    <div class="nav-item" style="padding-left:8px;">
                        <i class="fas fa-sign-out-alt"></i> <span>Logout</span>
                    </div>
                </div>
            </div>
        </div>

        <div class="main-content">
            <div class="top-bar">
                <div class="page-title"><h2 id="dynamicTitle">Teachers Management</h2></div>
            </div>
            
            <!-- KPI Cards -->
            <div class="kpi-row">
                <div class="kpi-card">
                    <div class="kpi-title">Total Teachers</div>
                    <div class="kpi-number"><?php echo $total_teachers; ?></div>
                    <div class="kpi-subtitle">Active Faculty Members</div>
                </div>
                <div class="kpi-card">
                    <div class="kpi-title">Departments</div>
                    <div class="kpi-number">7</div>
                    <div class="kpi-subtitle">Academic Departments</div>
                </div>
                <div class="kpi-card">
                    <div class="kpi-title">Subjects</div>
                    <div class="kpi-number">15+</div>
                    <div class="kpi-subtitle">Courses Offered</div>
                </div>
            </div>
            <!-- Search Box -->
            <div class="search-box">
                <form method="GET" action="" style="display: flex; gap: 10px; width: 100%;">
                    <input type="text" name="search" placeholder="🔍 Search by name, email or phone..." 
                           value="<?php echo htmlspecialchars($search); ?>" autocomplete="off" style="flex: 1;">
                    <button type="submit"><i class="fas fa-search"></i> Search</button>
                    <?php if(!empty($search)): ?>
                        <a href="teacher.php" class="clear-search"><i class="fas fa-times"></i> Clear</a>
                    <?php endif; ?>
                </form>
            </div>
            
            <!-- Result count display -->
            <?php if(!empty($search)): ?>
                <div class="result-count mb-3">
                    <i class="fas fa-search"></i> Search results for: <strong>"<?php echo htmlspecialchars($search); ?>"</strong>
                    (<?php echo mysqli_num_rows($result); ?> records found)
                </div>
            <?php endif; ?>

            <!-- Teachers Table -->
            <div class="table-container">
                <table class="table table-bordered table-striped mb-0">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Image</th>
                            <th>Name</th>
                            <th>Email</th>
                            <th>Phone</th>
                            <th>Gender</th>
                            <th>Subject</th>
                            <th>Address</th>
                            <th>Created At</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        if (mysqli_num_rows($result) > 0) {
                            while($row = mysqli_fetch_assoc($result)) {
                                echo "</tr>";
                                echo "<td>".$row['id']."</td>";
                                echo "<td>";
                                // Show image if exists, otherwise show placeholder
                                if(!empty($row['image'])) {
                                    echo "<img src='".htmlspecialchars($row['image'])."' alt='".htmlspecialchars($row['name'])."' class='teacher-img' onerror=\"this.onerror=null; this.src='https://ui-avatars.com/api/?background=667eea&color=fff&bold=true&size=50&name=".urlencode($row['name'])."'; this.classList.add('teacher-img-placeholder');\">";
                                } else {
                                    echo "<div class='teacher-img-placeholder'><i class='fas fa-user'></i></div>";
                                }
                                echo "</td>";
                                echo "<td>".htmlspecialchars($row['name'])."</td>";
                                echo "<td>".htmlspecialchars($row['email'])."</td>";
                                echo "<td>".htmlspecialchars($row['phone'])."</td>";
                                echo "<td>".htmlspecialchars($row['gender'])."</td>";
                                echo "<td>".htmlspecialchars($row['subject'])."</td>";
                                echo "<td>".htmlspecialchars($row['address'])."</td>";
                                echo "<td>".htmlspecialchars($row['created_at'])."</td>";
                                echo "<td>
                                        <button type='button' class='btn btn-warning btn-sm edit-btn mb-2' data-id='" . $row['id'] . "'>
                                            <i class='fas fa-edit'></i> Edit
                                        </button>
                                        <a href='delete_teacher.php?id=" . $row['id'] . "' class='btn btn-danger btn-sm' onclick='return confirm(\"Are you sure you want to delete this teacher?\")'>
                                            <i class='fas fa-trash'></i> Delete
                                        </a>
                                       ";
                                echo "</tr>";
                            }
                        } else {
                            echo "<tr><td colspan='10' class='text-center'>No teachers found</td></tr>";
                        }
                        ?>
                    </tbody>
                </table>
            </div>
            <br>

            <div class="form-container">
                <div class="header-banner">
                    <i class="fas fa-chalkboard-teacher"></i> Teachers Registration
                </div>
                
                <form action="processtea.php" method="POST" class="form-body">
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label class="form-label">Name <span class="text-danger">*</span></label>
                            <input type="text" name="name" class="form-control" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Gender <span class="text-danger">*</span></label>
                            <select name="gender" class="form-select" required>
                                <option selected disabled>Please Select Gender</option>
                                <option value="Male">Male</option>
                                <option value="Female">Female</option>
                                <option value="Other">Other</option>
                            </select>
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Image (URL)</label>
                        <input name="image" class="form-control" placeholder="https://example.com/photo.jpg">
                    </div>
                    
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label class="form-label">Email <span class="text-danger">*</span></label>
                            <input type="email" name="email" class="form-control" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Phone <span class="text-danger">*</span></label>
                            <input type="text" name="phone" class="form-control" required>
                        </div>
                    </div>
                     <div class="mb-3">
                            <label class="form-label">Subject</label>
                            <input  name="subject" class="form-select">
                                
                        </div>

                    <div class="mb-3">
                        <label class="form-label"> department<span class="text-danger">*</span></label>
                        <select name="department" class="form-select" required>
                            <option selected disabled>Please Select Subject</option>
                            <option value="Civil Engineering">សំណង់ស៊ីវិល (Civil Engineering)</option>
                            <option value="Electronics">អេឡិចត្រូនិក (Electronics)</option>
                            <option value="Electrical Engineering">អគ្គិសនី (Electrical Engineering)</option>
                            <option value="Accounting">គណនេយ្យ (Accounting)</option>
                            <option value="Marketing">ទីផ្សារ (Marketing)</option>
                            <option value="Management">គ្រប់គ្រង (Management)</option>
                            <option value="IT">ពត៏មានវិទ្យា (IT)</option>
                        </select>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Address</label>
                        <textarea name="address" class="form-control" rows="3"></textarea>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Created At</label>
                        <input type="date" name="created_at" class="form-control">
                    </div>

                    <div class="mt-4">
                        <button type="submit" class="btn btn-primary px-4">
                            <i class="fas fa-save"></i> Save Teacher
                        </button>
                        <button type="reset" class="btn btn-secondary px-4">
                            <i class="fas fa-undo"></i> Clear
                        </button>
                    </div>
                </form>
            </div>

            
        </div>
    </div>

    <!-- Edit Teacher Modal -->
    <div class="modal fade modal-custom" id="editTeacherModal" tabindex="-1" aria-labelledby="editTeacherModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">
                        <i class="fas fa-user-edit"></i> Edit Teacher Information
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <form id="editTeacherForm">
                        <input type="hidden" id="edit_id" name="id">
                        
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label class="form-label">Name <span class="text-danger">*</span></label>
                                <input type="text" id="edit_name" name="name" class="form-control" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Gender</label>
                                <select id="edit_gender" name="gender" class="form-select">
                                    <option value="Male">Male</option>
                                    <option value="Female">Female</option>
                                    <option value="Other">Other</option>
                                </select>
                            </div>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">Image URL</label>
                            <input type="text" id="edit_image" name="image" class="form-control" placeholder="https://example.com/image.jpg">
                        </div>
                        
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label class="form-label">Email</label>
                                <input type="email" id="edit_email" name="email" class="form-control">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Phone Number</label>
                                <input type="text" id="edit_phone" name="phone" class="form-control">
                            </div>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">Address</label>
                            <textarea id="edit_address" name="address" class="form-control" rows="2"></textarea>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">Subject</label>
                            <input id="edit_subject" name="subject" class="form-select">
                                
                        </div>
                        <div class="mb-3">
                            <label class="form-label">department</label>
                            <select id="edit_department" name="department" class="form-select">
                                <option value="Civil Engineering">សំណង់ស៊ីវិល (Civil Engineering)</option>
                                <option value="Electronics">អេឡិចត្រូនិក (Electronics)</option>
                                <option value="Electrical Engineering">អគ្គិសនី (Electrical Engineering)</option>
                                <option value="Accounting">គណនេយ្យ (Accounting)</option>
                                <option value="Marketing">ទីផ្សារ (Marketing)</option>
                                <option value="Management">គ្រប់គ្រង (Management)</option>
                                <option value="IT">ពត៏មានវិទ្យា (IT)</option>
                            </select>
                        </div>
                        
                        <div class="mt-4 d-flex justify-content-end gap-2">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                                <i class="fas fa-times"></i> Cancel
                            </button>
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-save"></i> Update Teacher
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
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
        
        $(document).ready(function() {
            // Handle edit button click
            $('.edit-btn').click(function() {
                var teacherId = $(this).data('id');
                
                // Show loading state
                $('#editTeacherModal .modal-body').addClass('loading-opacity');
                
                // Fetch teacher data via AJAX
                $.ajax({
                    url: window.location.pathname + '?ajax=get_teacher&id=' + teacherId,
                    type: 'GET',
                    dataType: 'json',
                    success: function(data) {
                        // Populate modal fields with teacher data
                        $('#edit_id').val(data.id);
                        $('#edit_name').val(data.name);
                        $('#edit_gender').val(data.gender);
                        $('#edit_image').val(data.image || '');
                        $('#edit_email').val(data.email);
                        $('#edit_phone').val(data.phone);
                        $('#edit_address').val(data.address);
                        $('#edit_subject').val(data.subject);
                        $('#edit_department').val(data.department);
                        
                        // Show the modal
                        $('#editTeacherModal').modal('show');
                        $('#editTeacherModal .modal-body').removeClass('loading-opacity');
                    },
                    error: function(xhr, status, error) {
                        console.error('AJAX Error:', error);
                        alert('Error loading teacher data. Please try again.');
                        $('#editTeacherModal .modal-body').removeClass('loading-opacity');
                    }
                });
            });
            
            // Handle form submission for update
            $('#editTeacherForm').submit(function(e) {
                e.preventDefault();
                
                // Show loading
                var submitBtn = $(this).find('button[type="submit"]');
                var originalText = submitBtn.html();
                submitBtn.html('<i class="fas fa-spinner fa-spin"></i> Updating...').prop('disabled', true);
                
                $.ajax({
                    url: window.location.pathname + '?ajax=update_teacher',
                    type: 'POST',
                    data: $(this).serialize(),
                    dataType: 'json',
                    success: function(response) {
                        if (response.success) {
                            alert(response.message);
                            location.reload();
                        } else {
                            alert('Error: ' + response.message);
                            submitBtn.html(originalText).prop('disabled', false);
                        }
                    },
                    error: function(xhr, status, error) {
                        console.error('AJAX Error:', error);
                        alert('Error updating teacher. Please try again.');
                        submitBtn.html(originalText).prop('disabled', false);
                    }
                });
            });
            
            // Clear modal when closed
            $('#editTeacherModal').on('hidden.bs.modal', function() {
                $('#editTeacherForm')[0].reset();
            });
        });
</script>
</body>
</html>