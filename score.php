 <?php
        include 'db.php';
        error_reporting(E_ALL);
        ini_set('display_errors', 1);
        
        // Handle search
        $search = isset($_GET['search']) ? mysqli_real_escape_string($conn, $_GET['search']) : '';
        $sql = "SELECT * FROM scores ORDER BY id DESC";
        
        if (!empty($search)) {
            $sql = "SELECT * FROM scores WHERE name LIKE '%$search%' OR subject LIKE '%$search%' OR score LIKE '%$search%' OR Grade LIKE '%$search%' OR student_id LIKE '%$search%' OR course_id LIKE '%$search%' ORDER BY id DESC";
        }
        
        // Handle AJAX request for getting student data
        if(isset($_GET['ajax']) && $_GET['ajax'] == 'get_student' && isset($_GET['id'])) {
            $id = intval($_GET['id']);
            $query = "SELECT * FROM scores WHERE id = $id";
            $result = mysqli_query($conn, $query);
            if($row = mysqli_fetch_assoc($result)) {
                header('Content-Type: application/json');
                echo json_encode($row);
            }
            exit;
        }
        
        // Handle AJAX request for updating student
        if($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_GET['ajax']) && $_GET['ajax'] == 'update_scores') {
            $id = isset($_POST['id']) ? intval($_POST['id']) : 0;
            $student_id = isset($_POST['student_id']) ? intval($_POST['student_id']) : 0;
            $name = isset($_POST['name']) ? mysqli_real_escape_string($conn, $_POST['name']) : '';
            $course_id = isset($_POST['course_id']) ? intval($_POST['course_id']) : 0;
            $subject = isset($_POST['subject']) ? mysqli_real_escape_string($conn, $_POST['subject']) : '';
            $score = isset($_POST['score']) ? floatval($_POST['score']) : 0;
            $Grade = isset($_POST['Grade']) ? mysqli_real_escape_string($conn, $_POST['Grade']) : '';
            $Designation = isset($_POST['Designation']) ? mysqli_real_escape_string($conn, $_POST['Designation']) : '';
            
            // ពិនិត្យមើលថាមានកំហុសឬទេ
            if(empty($name) || empty($subject) || $score === '' || $student_id == 0 || $course_id == 0) {
                echo json_encode(['success' => false, 'message' => 'Please fill all required fields']);
                exit;
            }
            
            $update_sql = "UPDATE scores SET 
                           name='$name',
                           subject='$subject',
                           score='$score',
                           Grade='$Grade',
                           
                           WHERE id=$id";
            
            if(mysqli_query($conn, $update_sql)) {
                echo json_encode(['success' => true, 'message' => 'Score record updated successfully']);
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
    <title>KRaksa Education Suite - Student Scores Management</title>
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
    
    * { 
        margin: 0; 
        padding: 0; 
        box-sizing: border-box; 
    }
    
    a {
        text-decoration: none;
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
        margin-bottom: 20px;
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
        margin-bottom: 15px;
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
    
    .more {
        margin-left: 85%;
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
    
    .loading-opacity {
        opacity: 0.6;
        pointer-events: none;
    }
    
    .badge {
        padding: 5px 10px;
        font-size: 12px;
    }
    
    .score-excellent { background-color: #28a745; color: white; padding: 5px 10px; border-radius: 5px; }
    .score-good { background-color: #17a2b8; color: white; padding: 5px 10px; border-radius: 5px; }
    .score-average { background-color: #ffc107; color: #000; padding: 5px 10px; border-radius: 5px; }
    .score-poor { background-color: #dc3545; color: white; padding: 5px 10px; border-radius: 5px; }
    
    @media (max-width: 768px) {
        .sidebar { width: 80px; }
        .sidebar-header h1, .sidebar-header p, .nav-item span { display: none; }
        .nav-item { justify-content: center; }
        .main-content { padding: 15px; }
        .top-bar { flex-direction: column; gap: 10px; }
        .search-box { width: 100%; }
        .search-box input { flex: 1; }
        .more { margin-left: 70%; }
    }
</style>
</head>
<body>
    <div class="container">
        <!-- Sidebar -->
        <div class="sidebar">
            <div class="sidebar-header">
                <img src="https://i.pinimg.com/736x/6a/33/e1/6a33e1b0b8fbb948cbf04b7397c9b381.jpg" alt="Logo" />
                <h1>KRaksa</h1>
                <p>Education Suite</p>
            </div>
            <div class="nav-menu">
                <a href="index.php" class="nav-item">
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
                        <a href="score.php" class="nav-item sub-menu active">
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
                <a href="teacher.php" class="nav-item">
                    <i class="fas fa-chalkboard-teacher"></i> <span>Teachers</span>
                </a>
                <a href="Courses.php" class="nav-item">
                    <i class="fas fa-graduation-cap"></i> <span>Department</span>
                </a>
                 <a href="schedule.php" class="nav-item" data-page="schedule"> <i class="fas fa-calendar-alt"></i> <span>Schedule class</span></a>
                <a href="Employees.php" class="nav-item">
                    <i class="fas fa-user-friends"></i> <span>Employees</span>
                </a>
                <a href="StudentAttendance.php" class="nav-item">
                    <i class="fas fa-calendar-check"></i> <span>Attendance</span>
                </a>
                <div class="nav-bottom">
                    <a href="logout.php" class="nav-item">
                        <i class="fas fa-sign-out-alt"></i> <span>Logout</span>
                    </a>
                </div>
            </div>
        </div>

        <!-- Main Content -->
        <div class="main-content">
            <div class="top-bar">
                <div class="page-title">
                    <h2><i class="fas fa-percent"></i> Student Score Management</h2>
                </div>
            </div>

            <?php if(isset($_GET['success'])): ?>
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    <i class="fas fa-check-circle"></i> <?php echo htmlspecialchars($_GET['success']); ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>

            <?php if(isset($_GET['error'])): ?>
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    <i class="fas fa-exclamation-circle"></i> <?php echo htmlspecialchars($_GET['error']); ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>

            <div class="more">
                <button id="addMoreBtn">
                    <i class="fas fa-plus"></i> Add Score
                </button>
            </div>

            <form method="GET" action="" class="search-box">
                <input type="text" name="search" placeholder="Search by name, subject, student ID, course ID, score, or grade..." 
                       value="<?php echo htmlspecialchars($search); ?>">
                <button type="submit"><i class="fas fa-search"></i> Search</button>
                <?php if(!empty($search)): ?>
                    <a href="score.php" class="clear-search"><i class="fas fa-times"></i> Clear</a>
                <?php endif; ?>
            </form>

            <div class="table-container">
                <table class="table table-bordered table-striped">
                    <thead class="table-light">
                        <tr>
                            <th>ID</th>
                           
                            <th>Name</th>
                            
                            <th>Subject</th>
                            <th>Score</th>
                            <th>Grade</th>
                            
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (isset($result) && mysqli_num_rows($result) > 0) {
                            while($row = mysqli_fetch_assoc($result)) { ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($row['id']); ?></td>
                                   
                                    <td><?php echo htmlspecialchars($row['name']); ?></td>
                                    
                                    <td><?php echo htmlspecialchars($row['subject']); ?></td>
                                    <td>
                                        <?php 
                                        $score = floatval($row['score']);
                                        if($score >= 80) {
                                            echo "<span class='score-excellent'><i class='fas fa-award'></i> $score</span>";
                                        } elseif($score >= 60) {
                                            echo "<span class='score-good'><i class='fas fa-check-circle'></i> $score</span>";
                                        } elseif($score >= 40) {
                                            echo "<span class='score-average'><i class='fas fa-exclamation-triangle'></i> $score</span>";
                                        } else {
                                            echo "<span class='score-poor'><i class='fas fa-times-circle'></i> $score</span>";
                                        }
                                        ?>
                                    </td>
                                    <td><?php echo htmlspecialchars($row['Grade']); ?></td>
                                   
                                    <td>
                                        <button type='button' class='btn btn-warning btn-sm edit-btn' data-id='<?php echo $row['id']; ?>'>
                                            <i class='fas fa-edit'></i> Edit
                                        </button>
                                        <a href='delete_score.php?id=<?php echo $row['id']; ?>' class='btn btn-danger btn-sm' onclick='return confirm("Are you sure you want to delete this score record?")'>
                                            <i class='fas fa-trash'></i> Delete
                                        </a>
                                    </td>
                                </tr>
                            <?php }
                        } else { ?>
                            <tr>
                                <td colspan='9' class='text-center'>
                                    <i class='fas fa-chart-line'></i> No score records found.
                                </td>
                            </tr>
                        <?php } ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    
    <!-- Add Score Modal -->
    <div class="modal fade modal-custom" id="addStudentModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title"><i class="fas fa-plus-circle"></i> Add New Score Record</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <form action="process_student_score.php" method="POST" id="addStudentForm">
                        <div class="mb-3">
                            
                            
                                <label class="form-label">Name <span class="text-danger">*</span></label>
                                <input type="text" name="name" class="form-control" required>
                           
                        </div>
                        <div class="mb-3">
                            
                           
                                <label class="form-label">Subject <span class="text-danger">*</span></label>
                                <input type="text" name="subject" class="form-control" required>
                            
                        </div>
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label class="form-label">Score <span class="text-danger">*</span></label>
                                <input type="number" step="0.01" name="score" class="form-control" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Grade <span class="text-danger">*</span></label>
                                <input type="text" name="Grade" class="form-control" required>
                            </div>
                        </div>
                        
                        <div class="mt-4 d-flex justify-content-end gap-2">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                            <button type="submit" class="btn btn-primary">Save Record</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Edit Score Modal -->
    <div class="modal fade modal-custom" id="editStudentModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title"><i class="fas fa-edit"></i> Edit Score Record</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <form id="editStudentForm">
                        <input type="hidden" name="id" id="edit_record_id">
                        <div class=" mb-3">
                           
                           
                                <label class="form-label">Name <span class="text-danger">*</span></label>
                                <input type="text" name="name" id="edit_name" class="form-control" required>
                            
                        </div>
                        <div class=" mb-3">
                            
                            
                                <label class="form-label">Subject <span class="text-danger">*</span></label>
                                <input type="text" name="subject" id="edit_subject" class="form-control" required>
                            
                        </div>
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label class="form-label">Score <span class="text-danger">*</span></label>
                                <input type="number" step="0.01" name="score" id="edit_score" class="form-control" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Grade <span class="text-danger">*</span></label>
                                <input type="text" name="Grade" id="edit_Grade" class="form-control" required>
                            </div>
                        </div>
                       
                        <div class="mt-4 d-flex justify-content-end gap-2">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                            <button type="submit" class="btn btn-primary">Update Record</button>
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

        $(document).ready(function() {
            $('#addMoreBtn').click(function() {
                $('#addStudentModal').modal('show');
            });
            
            $('.edit-btn').click(function() {
                var recordId = $(this).data('id');
                $('#editStudentModal .modal-body').addClass('loading-opacity');
                
                $.ajax({
                    url: window.location.href,
                    type: 'GET',
                    data: {
                        ajax: 'get_student',
                        id: recordId
                    },
                    dataType: 'json',
                    success: function(data) {
                        $('#edit_record_id').val(data.id);
                        $('#edit_student_id').val(data.student_id);
                        $('#edit_name').val(data.name);
                        $('#edit_course_id').val(data.course_id);
                        $('#edit_subject').val(data.subject);
                        $('#edit_score').val(data.score);
                        $('#edit_Grade').val(data.Grade);
                        $('#edit_Designation').val(data.Designation);
                        
                        $('#editStudentModal').modal('show');
                        $('#editStudentModal .modal-body').removeClass('loading-opacity');
                    },
                    error: function(xhr, status, error) {
                        alert('Error loading score data. Please try again.');
                        $('#editStudentModal .modal-body').removeClass('loading-opacity');
                    }
                });
            });
            
            $('#editStudentForm').submit(function(e) {
                e.preventDefault();
                
                var submitBtn = $(this).find('button[type="submit"]');
                var originalText = submitBtn.html();
                submitBtn.html('<i class="fas fa-spinner fa-spin"></i> Updating...').prop('disabled', true);
                
                $.ajax({
                    url: window.location.href,
                    type: 'POST',
                    data: $(this).serialize() + '&ajax=update_scores',
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
                    error: function() {
                        alert('Error updating score record. Please try again.');
                        submitBtn.html(originalText).prop('disabled', false);
                    }
                });
            });
        });
    </script>
</body>
</html>