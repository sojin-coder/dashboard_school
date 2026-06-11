<?php
    include 'db.php';
    error_reporting(E_ALL);
    ini_set('display_errors', 1);
    
    // Handle Add Student Type
    if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['action']) && $_POST['action'] == 'add') {
        $student_id = mysqli_real_escape_string($conn, $_POST['student_id']);
        $status = mysqli_real_escape_string($conn, $_POST['status']);
        $created_at = mysqli_real_escape_string($conn, $_POST['created_at']);
        
        if (!empty($student_id) && !empty($status)) {
            $insert_sql = "INSERT INTO datastu (student_id, status, created_at) 
                           VALUES ('$student_id', '$status', '$created_at')";
            
            if(mysqli_query($conn, $insert_sql)) {
                $success_msg = "Student type added successfully!";
            } else {
                $error_msg = "Error: " . mysqli_error($conn);
            }
        }
    }
    
    // Handle search
    $search = isset($_GET['search']) ? mysqli_real_escape_string($conn, $_GET['search']) : '';
    $sql = "SELECT * FROM datastu ORDER BY id DESC";
    
    if (!empty($search)) {
        $sql = "SELECT * FROM datastu WHERE student_id LIKE '%$search%' OR status LIKE '%$search%' ORDER BY id DESC";
    }
    
    // Handle AJAX request for getting student data
    if(isset($_GET['ajax']) && $_GET['ajax'] == 'get_student' && isset($_GET['id'])) {
        $id = intval($_GET['id']);
        $query = "SELECT * FROM datastu WHERE id = $id";
        $result = mysqli_query($conn, $query);
        if($row = mysqli_fetch_assoc($result)) {
            header('Content-Type: application/json');
            echo json_encode($row);
        }
        exit;
    }
    
    // Handle AJAX request for updating student
    if($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_GET['ajax']) && $_GET['ajax'] == 'update_student') {
        $id = intval($_POST['id']);
        $student_id = mysqli_real_escape_string($conn, $_POST['student_id']);
        $status = mysqli_real_escape_string($conn, $_POST['status']);
        $created_at = mysqli_real_escape_string($conn, $_POST['created_at']);
        
        $update_sql = "UPDATE datastu SET 
                       student_id='$student_id', 
                       status='$status',
                       created_at='$created_at'
                       WHERE id=$id";
        
        if(mysqli_query($conn, $update_sql)) {
            echo json_encode(['success' => true, 'message' => 'Student updated successfully']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Error: ' . mysqli_error($conn)]);
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
    <title>KRaksa Education Suite - Student Type Management</title>
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
            margin-left:85% ;
        }
        .more button {
            width: 150px;
            padding: 10px;
            margin-bottom: 15px;
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
        
        .loading-opacity {
            opacity: 0.6;
            pointer-events: none;
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
            
                    <a href="list_for_teacher.php" class="nav-item sub-menu ">
                        <i class="fas fa-users"></i>
                        <span>Student List</span>
                    </a>
            
                    
            
                    <a href="view_for_teacher.php" class="nav-item sub-menu active">
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
                    <h2 id="dynamicTitle">Student Type Management</h2>
                </div>
            </div>

            <!-- Add More Button -->
            <!-- <div class="more">
                <button id="addMoreBtn">
                    <i class="fas fa-plus"></i> Add More
                </button>
            </div> -->

            <!-- Result count display -->
            <?php if(!empty($search)): ?>
                <div class="result-count mt-3">
                    <i class="fas fa-search"></i> Search results for: <strong>"<?php echo htmlspecialchars($search); ?>"</strong>
                    (<?php echo mysqli_num_rows($result); ?> records found)
                </div>
            <?php endif; ?>

            <!-- Search Box -->
            <form method="GET" action="" class="search-box m-2">
                <input type="text" name="search" placeholder="🔍 Search by student ID or type..." 
                       value="<?php echo htmlspecialchars($search); ?>" autocomplete="off">
                <button type="submit"><i class="fas fa-search"></i> Search</button>
                <?php if(!empty($search)): ?>
                    <a href="stuviwe.php" class="clear-search"><i class="fas fa-times"></i> Clear</a>
                <?php endif; ?>
            </form>

            <!-- Students Table -->
            <div class="table-container mt-4">
                <table class="table table-bordered table-striped">
                    <thead class="table-light">
                        <tr>
                            <th>ID</th>
                            <th>Student ID</th>
                            <th>Status</th>
                            <th>Created At</th>
                            <!-- <th>Actions</th> -->
                        </tr>
                    </thead>
                    <tbody>
                       <?php if (isset($result) && mysqli_num_rows($result) > 0) {
    while($row = mysqli_fetch_assoc($result)) { ?>
        <tr>
            <td><?php echo htmlspecialchars($row['id']); ?></td>
            <td><?php echo htmlspecialchars($row['student_id']); ?></td>
            <td>
                <?php 
                $status = strtolower(htmlspecialchars($row['status'])); // Convert to lowercase for comparison
                $badgeClass = '';
                if($status == 'active') {
                    $badgeClass = 'badge bg-success'; // Changed to green for Active
                } 
                else if($status == 'inactive') {
                    $badgeClass = 'badge bg-secondary';
                } else if($status == 'dropped') {
                    $badgeClass = 'badge bg-danger';
                } else {
                    $badgeClass = 'badge bg-warning'; // Default for unknown status
                }
                ?>
                <span class="<?php echo $badgeClass; ?>"><?php echo ucfirst($status); ?></span>
            </td>
            <td><?php echo htmlspecialchars($row['created_at']); ?></td>
            <!-- <td>
                <button type='button' class='btn btn-warning btn-sm edit-btn' data-id='<?php echo $row['id']; ?>'>
                    <i class='fas fa-edit'></i> Edit
                </button>
                <a href='delete_student_type.php?id=<?php echo $row['id']; ?>' class='btn btn-danger btn-sm' onclick='return confirm("Are you sure you want to delete this student type record?")'>
                    <i class='fas fa-trash'></i> Delete
                </a>
            </td> -->
        </tr>
    <?php }
} else { ?>
    <tr>
        <td colspan='5' class='text-center'> <!-- Changed from colspan='4' to colspan='5' -->
            <i class='fas fa-user-slash'></i> No student type records found. 
            <?php echo !empty($search) ? "Please try another search term." : "Please add a student type record above."; ?>
        </td>
    </tr>
<?php } ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    
    <!-- Add Student Type Modal (Pop-up) -->
    <div class="modal fade modal-custom" id="addStudentModal" tabindex="-1" aria-labelledby="addStudentModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="addStudentModalLabel">
                        <i class="fas fa-user-plus"></i> Add New Student Type
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <form action="process_student_view.php" method="POST" id="addStudentForm">
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label class="form-label">Student ID <span class="text-danger">*</span></label>
                                <input type="text" name="student_id" class="form-control" placeholder="Enter student ID" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">status <span class="text-danger">*</span></label>
                                <select name="status" class="form-select" required>
                                    <option value="">Select Status</option>
                                    <!-- <option value="new">New Student</option> -->
                                    <option value="Active">Active</option>
                                    <option value="Inactive">Inactive</option>
                                    <option value="Dropped">Dropped</option>
                                    
                                </select>
                            </div>
                        </div>
                         <div class="row mb-3">
                                <label class="form-label">Created At <span class="text-danger">*</span></label>
                                <input type="text"  name="created_at" class="form-control" required>
                            </div>

                        <div class="mt-4 d-flex justify-content-end gap-2">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                                <i class="fas fa-times"></i> Cancel
                            </button>
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-save"></i> Save Record
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Edit Student Type Modal -->
    <div class="modal fade modal-custom" id="editStudentModal" tabindex="-1" aria-labelledby="editStudentModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="editStudentModalLabel">
                        <i class="fas fa-user-edit"></i> Edit Student Type Information
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <form id="editStudentForm">
                        <input type="hidden" id="edit_record_id" name="id">
                        
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label class="form-label">Student ID <span class="text-danger">*</span></label>
                                <input type="text" id="edit_student_id" name="student_id" class="form-control" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Status <span class="text-danger">*</span></label>
                                <select id="edit_status" name="status" class="form-select" required>
                                    <option value="active">Active</option>
                                    <option value="inactive">Inactive</option>
                                </select>
                            </div>

                        </div>
                         <div class="row mb-3">
                                <label class="form-label">Created At <span class="text-danger">*</span></label>
                                <input type="text" id="edit_created_at" name="created_at" class="form-control" required>
                            </div>
                        
                        <div class="mt-4 d-flex justify-content-end gap-2">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                                <i class="fas fa-times"></i> Cancel
                            </button>
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-save"></i> Update Record
                            </button>
                        </div>
                    </form>
                </div>
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

    <script>
        $(document).ready(function() {
            // Show Add Student Modal when clicking Add More button
            $('#addMoreBtn').click(function() {
                $('#addStudentModal').modal('show');
            });
            
            // Handle Add Student Form submission
            $('#addStudentForm').on('submit', function(e) {
                // Form will submit normally to process_student_type.php
                console.log('Submitting form to process_student_type.php...');
            });
            
            // Handle edit button click
            $('.edit-btn').click(function() {
                var studentId = $(this).data('id');
                
                // Show loading state
                $('#editStudentModal .modal-body').addClass('loading-opacity');
                
                // Fetch student data via AJAX
                $.ajax({
                    url: window.location.href,
                    type: 'GET',
                    data: {
                        ajax: 'get_student',
                        id: studentId
                    },
                    dataType: 'json',
                    success: function(data) {
                        // Populate modal fields with student data
                        $('#edit_record_id').val(data.id);
                        $('#edit_student_id').val(data.student_id);
                        $('#edit_status').val(data.status);
                        $('#edit_created_at').val(data.created_at);
                        
                        // Show the modal
                        $('#editStudentModal').modal('show');
                        $('#editStudentModal .modal-body').removeClass('loading-opacity');
                    },
                    error: function(xhr, status, error) {
                        console.error('Error details:', error);
                        console.error('Response text:', xhr.responseText);
                        alert('Error loading student data. Please check the console for details.');
                        $('#editStudentModal .modal-body').removeClass('loading-opacity');
                    }
                });
            });
            
            // Handle edit form submission
            $('#editStudentForm').submit(function(e) {
                e.preventDefault();
                
                // Show loading
                var submitBtn = $(this).find('button[type="submit"]');
                var originalText = submitBtn.html();
                submitBtn.html('<i class="fas fa-spinner fa-spin"></i> Updating...').prop('disabled', true);
                
                $.ajax({
                    url: window.location.href + '&ajax=update_student',
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
                        console.error('Update error:', error);
                        alert('Error updating student record. Please try again.');
                        submitBtn.html(originalText).prop('disabled', false);
                    }
                });
            });
            
            // Clear edit modal when closed
            $('#editStudentModal').on('hidden.bs.modal', function() {
                $('#editStudentForm')[0].reset();
                $('#editStudentForm .loading-opacity').removeClass('loading-opacity');
            });
            
            // Clear add modal when closed
            $('#addStudentModal').on('hidden.bs.modal', function() {
                $('#addStudentForm')[0].reset();
            });
        });
    </script>
</body>
</html>