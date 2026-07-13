<?php
include 'db.php';
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Handle Add Payment Record
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['action']) && $_POST['action'] == 'add') {
    $student_id = mysqli_real_escape_string($conn, $_POST['student_id']);
    $amount = mysqli_real_escape_string($conn, $_POST['amount']);
    $payment_date = mysqli_real_escape_string($conn, $_POST['payment_date']);
    $month = mysqli_real_escape_string($conn, $_POST['month']);
    $status = mysqli_real_escape_string($conn, $_POST['status']);
    $created_at = mysqli_real_escape_string($conn, $_POST['created_at']);
    
    if (!empty($student_id) && !empty($amount)) {
        $insert_sql = "INSERT INTO student_payments (student_id, amount, payment_date, month, status, created_at) 
                       VALUES ('$student_id', '$amount', '$payment_date', '$month', '$status', '$created_at')";
        
        if(mysqli_query($conn, $insert_sql)) {
            $success_msg = "Payment record added successfully!";
        } else {
            $error_msg = "Error: " . mysqli_error($conn);
        }
    }
}

// Handle search
$search = isset($_GET['search']) ? mysqli_real_escape_string($conn, $_GET['search']) : '';
$sql = "SELECT * FROM student_payments ORDER BY id DESC";

if (!empty($search)) {
    $sql = "SELECT * FROM student_payments WHERE student_id LIKE '%$search%' OR status LIKE '%$search%' OR month LIKE '%$search%' ORDER BY id DESC";
}

// Handle AJAX request for getting payment data
if(isset($_GET['ajax']) && $_GET['ajax'] == 'get_payment' && isset($_GET['id'])) {
    $id = intval($_GET['id']);
    $query = "SELECT * FROM student_payments WHERE id = $id";
    $result = mysqli_query($conn, $query);
    if($row = mysqli_fetch_assoc($result)) {
        header('Content-Type: application/json');
        echo json_encode($row);
    }
    exit;
}

// Handle AJAX request for updating payment
if($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_GET['ajax']) && $_GET['ajax'] == 'update_payment') {
    $id = intval($_POST['id']);
    $student_id = mysqli_real_escape_string($conn, $_POST['student_id']);
    $amount = mysqli_real_escape_string($conn, $_POST['amount']);
    $payment_date = mysqli_real_escape_string($conn, $_POST['payment_date']);
    $month = mysqli_real_escape_string($conn, $_POST['month']);
    $status = mysqli_real_escape_string($conn, $_POST['status']);
    $created_at = mysqli_real_escape_string($conn, $_POST['created_at']);
    
    $update_sql = "UPDATE student_payments SET 
                   student_id='$student_id', 
                   amount='$amount',
                   payment_date='$payment_date',
                   month='$month',
                   status='$status',
                   created_at='$created_at'
                   WHERE id=$id";
    
    if(mysqli_query($conn, $update_sql)) {
        echo json_encode(['success' => true, 'message' => 'Payment record updated successfully']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Error: ' . mysqli_error($conn)]);
    }
    exit;
}

// Fetch all payment records from database
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
    <title>KRaksa Education Suite - Student Payments Management</title>
    <style>
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
            transition: 0.3s;
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
            background: linear-gradient(90deg, rgba(117, 82, 243, 1) 19%, rgba(64, 24, 157, 1) 95%);
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
            border-radius: 20px;
        }
        
        .bg-success {
            background-color: #28a745 !important;
        }
        
        .bg-danger {
            background-color: #dc3545 !important;
        }
        
        .bg-warning {
            background-color: #ffc107 !important;
            color: #000;
        }
        
        /* Alert messages */
        .custom-alert {
            position: fixed;
            top: 20px;
            right: 20px;
            z-index: 9999;
            min-width: 300px;
            animation: slideIn 0.3s ease;
        }
        
        @keyframes slideIn {
            from {
                transform: translateX(100%);
                opacity: 0;
            }
            to {
                transform: translateX(0);
                opacity: 1;
            }
        }
        
        @media (max-width: 768px) {
            .sidebar { width: 80px; }
            .sidebar-header h1, .sidebar-header p, .nav-item span { display: none; }
            .nav-item { justify-content: center; }
            .main-content { padding: 15px; }
            .top-bar { flex-direction: column; gap: 10px; }
            .search-box { width: 100%; }
            .search-box input { flex: 1; }
            .more { margin-left: auto; }
            .custom-alert { width: 90%; right: 5%; }
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
                            <i class="fas fa-users"></i> <span>Student List</span>
                        </a>
                        <a href="stutype.php" class="nav-item sub-menu">
                            <i class="fas fa-tags"></i> <span>Student Type</span>
                        </a>
                        <a href="stuviwe.php" class="nav-item sub-menu">
                            <i class="fas fa-eye"></i> <span>Student View</span>
                        </a>
                        <a href="grade.php" class="nav-item sub-menu">
                            <i class="fas fa-layer-group"></i> <span>Student Grades</span>
                        </a>
                        <a href="score.php" class="nav-item sub-menu">
                            <i class="fas fa-chart-line"></i> <span>Student Scores</span>
                        </a>
                        <a href="student_payments.php" class="nav-item sub-menu active">
                            <i class="fas fa-money-bill-wave"></i> <span>Student Payments</span>
                        </a>
                        <a href="card_stuIT.php" class="nav-item sub-menu">
                            <i class="fas fa-id-card"></i> <span>ID Card</span>
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
                 <div class="dropdown-container">
                <div class="nav-item dropdown-btn" onclick="toggleReportDropdown()">
                    <div>
                        <i class="fas fa-file-pdf"></i> 
                        <span class="m-2">Report</span>
                    </div>
                    <i class="fas fa-chevron-down dropdown-icon" id="reportDropdownIcon"></i>
                </div>
                <div class="dropdown-menus" id="reportDropdownMenu">
                    <a href="report_teacher.php" class="nav-item sub-menu"><i class="fas fa-chalkboard-teacher"></i><span>Teacher Report</span></a>
                    <a href="report_student.php" class="nav-item sub-menu"><i class="fas fa-users"></i><span>Student Report</span></a>
                    <a href="report_month.php" class="nav-item sub-menu"><i class="fas fa-chart-line"></i><span>Monthly Report</span></a>
                </div>
            </div>
            </div>
                <a href="Employees.php" class="nav-item">
                    <i class="fas fa-user-friends"></i> <span>Employees</span>
                </a>
                <!-- <a href="StudentAttendance.php" class="nav-item">
                    <i class="fas fa-calendar-check"></i> <span>Attendance</span>
                </a> -->
                <a href="attendance_admin.php" class="nav-item "><i class="fas fa-clipboard-check"></i> <span>Attendance Admin</span></a>
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
                    <h2>Student Payments Management</h2>
                </div>
            </div>

            <!-- Success/Error Messages -->
            <?php if(isset($success_msg)): ?>
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    <i class="fas fa-check-circle"></i> <?php echo $success_msg; ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>
            
            <?php if(isset($error_msg)): ?>
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    <i class="fas fa-exclamation-triangle"></i> <?php echo $error_msg; ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>

            <div class="more">
                <button id="addMoreBtn">
                    <i class="fas fa-plus"></i> Add Payment
                </button>
            </div>

            <?php if(!empty($search)): ?>
                <div class="result-count">
                    <i class="fas fa-search"></i> Search results for: <strong>"<?php echo htmlspecialchars($search); ?>"</strong>
                    (<?php echo mysqli_num_rows($result); ?> records found)
                </div>
            <?php endif; ?>

            <form method="GET" action="" class="search-box">
                <input type="text" name="search" placeholder="🔍 Search by student ID, status, or month..." 
                       value="<?php echo htmlspecialchars($search); ?>" autocomplete="off">
                <button type="submit"><i class="fas fa-search"></i> Search</button>
                <?php if(!empty($search)): ?>
                    <a href="student_payments.php" class="clear-search"><i class="fas fa-times"></i> Clear</a>
                <?php endif; ?>
            </form>

            <div class="table-container mt-4">
                <table class="table table-bordered table-striped">
                    <thead class="table-light">
                        <tr>
                            <th>ID</th>
                            <th>Student ID</th>
                            <th>Amount ($)</th>
                            <th>Payment Date</th>
                            <th>Month</th>
                            <th>Status</th>
                            <th>Created At</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (isset($result) && mysqli_num_rows($result) > 0) {
                            while($row = mysqli_fetch_assoc($result)) { ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($row['id']); ?></td>
                                    <td><?php echo htmlspecialchars($row['student_id']); ?></td>
                                    <td>$<?php echo number_format(htmlspecialchars($row['amount']), 2); ?></td>
                                    <td><?php echo htmlspecialchars($row['payment_date']); ?></td>
                                    <td><?php echo htmlspecialchars($row['month']); ?></td>
                                    <td>
                                        <?php 
                                        $status = strtolower(htmlspecialchars($row['status']));
                                        $badgeClass = '';
                                        if($status == 'paid') {
                                            $badgeClass = 'badge bg-success';
                                        } elseif($status == 'unpaid') {
                                            $badgeClass = 'badge bg-danger';
                                        } else {
                                            $badgeClass = 'badge bg-warning';
                                        }
                                        ?>
                                        <span class="<?php echo $badgeClass; ?>"><?php echo ucfirst($status); ?></span>
                                    </td>
                                    <td><?php echo htmlspecialchars($row['created_at']); ?></td>
                                    <td>
                                        <button type='button' class='btn btn-warning btn-sm edit-btn' data-id='<?php echo $row['id']; ?>'>
                                            <i class='fas fa-edit'></i> Edit
                                        </button>
                                        <a href='delete_student_payment.php?id=<?php echo $row['id']; ?>' class='btn btn-danger btn-sm' onclick='return confirm("Are you sure you want to delete this payment record?")'>
                                            <i class='fas fa-trash'></i> Delete
                                        </a>
                                    </td>
                                </tr>
                            <?php }
                        } else { ?>
                            <tr>
                                <td colspan='8' class='text-center'>
                                    <i class='fas fa-receipt'></i> No payment records found. 
                                    <?php echo !empty($search) ? "Please try another search term." : "Click 'Add Payment' to create a new record."; ?>
                                </td>
                            </tr>
                        <?php } ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    
    <!-- Add Payment Modal -->
    <div class="modal fade modal-custom" id="addPaymentModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">
                        <i class="fas fa-plus-circle"></i> Add New Payment Record
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <form method="POST" action="" id="addPaymentForm">
                        <input type="hidden" name="action" value="add">
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label class="form-label">Student ID <span class="text-danger">*</span></label>
                                <input type="text" name="student_id" class="form-control" placeholder="Enter student ID" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Amount ($) <span class="text-danger">*</span></label>
                                <input type="number" step="0.01" name="amount" class="form-control" placeholder="Enter amount" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Payment Date <span class="text-danger">*</span></label>
                                <input type="date" name="payment_date" class="form-control" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Month <span class="text-danger">*</span></label>
                                <select name="month" class="form-select" required>
                                    <option value="">Select Month</option>
                                    <option value="January">January</option>
                                    <option value="February">February</option>
                                    <option value="March">March</option>
                                    <option value="April">April</option>
                                    <option value="May">May</option>
                                    <option value="June">June</option>
                                    <option value="July">July</option>
                                    <option value="August">August</option>
                                    <option value="September">September</option>
                                    <option value="October">October</option>
                                    <option value="November">November</option>
                                    <option value="December">December</option>
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Status <span class="text-danger">*</span></label>
                                <select name="status" class="form-select" required>
                                    <option value="">Select Status</option>
                                    <option value="paid">Paid</option>
                                    <option value="unpaid">Unpaid</option>
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Created At</label>
                                <input type="date" name="created_at" class="form-control" value="<?php echo date('Y-m-d H:i:s'); ?>">
                            </div>
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
    
    <!-- Edit Payment Modal -->
    <div class="modal fade modal-custom" id="editPaymentModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">
                        <i class="fas fa-edit"></i> Edit Payment Record
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <form id="editPaymentForm">
                        <input type="hidden" id="edit_id" name="id">
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label class="form-label">Student ID <span class="text-danger">*</span></label>
                                <input type="text" id="edit_student_id" name="student_id" class="form-control" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Amount ($) <span class="text-danger">*</span></label>
                                <input type="number" step="0.01" id="edit_amount" name="amount" class="form-control" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Payment Date <span class="text-danger">*</span></label>
                                <input type="date" id="edit_payment_date" name="payment_date" class="form-control" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Month <span class="text-danger">*</span></label>
                                <select id="edit_month" name="month" class="form-select" required>
                                    <option value="January">January</option>
                                    <option value="February">February</option>
                                    <option value="March">March</option>
                                    <option value="April">April</option>
                                    <option value="May">May</option>
                                    <option value="June">June</option>
                                    <option value="July">July</option>
                                    <option value="August">August</option>
                                    <option value="September">September</option>
                                    <option value="October">October</option>
                                    <option value="November">November</option>
                                    <option value="December">December</option>
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Status <span class="text-danger">*</span></label>
                                <select id="edit_status" name="status" class="form-select" required>
                                    <option value="paid">Paid</option>
                                    <option value="unpaid">Unpaid</option>
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Created At</label>
                                <input type="datetime-local" id="edit_created_at" name="created_at" class="form-control">
                            </div>
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

         function toggleReportDropdown() {
            let menu = document.getElementById("reportDropdownMenu");
            let icon = document.getElementById("reportDropdownIcon");
            
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
        function toggleDropdown() {
            let menu = document.getElementById("studentDropdown");
            let icon = document.getElementById("dropdownIcon");
            if(menu.style.display === "block") {
                menu.style.display = "none";
                icon.style.transform = "rotate(0deg)";
            } else {
                menu.style.display = "block";
                icon.style.transform = "rotate(180deg)";
            }
        }

        function showAlert(message, type) {
            const alertDiv = $('<div class="alert alert-' + type + ' alert-dismissible fade show custom-alert" role="alert">' +
                '<i class="fas ' + (type === 'success' ? 'fa-check-circle' : 'fa-exclamation-triangle') + '"></i> ' +
                message +
                '<button type="button" class="btn-close" data-bs-dismiss="alert"></button>' +
                '</div>');
            $('body').append(alertDiv);
            setTimeout(function() {
                alertDiv.fadeOut('slow', function() { $(this).remove(); });
            }, 3000);
        }

        $(document).ready(function() {
            // Show Add Payment Modal
            $('#addMoreBtn').click(function() {
                $('#addPaymentModal').modal('show');
            });
            
            // Handle Add Payment Form submission
            $('#addPaymentForm').on('submit', function(e) {
                e.preventDefault();
                
                var submitBtn = $(this).find('button[type="submit"]');
                var originalText = submitBtn.html();
                submitBtn.html('<i class="fas fa-spinner fa-spin"></i> Saving...').prop('disabled', true);
                
                $.ajax({
                    url: window.location.href,
                    type: 'POST',
                    data: $(this).serialize(),
                    success: function(response) {
                        showAlert('Payment record added successfully!', 'success');
                        $('#addPaymentModal').modal('hide');
                        setTimeout(function() { location.reload(); }, 1000);
                    },
                    error: function() {
                        showAlert('Error adding payment record. Please try again.', 'danger');
                        submitBtn.html(originalText).prop('disabled', false);
                    }
                });
            });
            
            // Handle edit button click
            $('.edit-btn').click(function() {
                var paymentId = $(this).data('id');
                $('#editPaymentModal .modal-body').addClass('loading-opacity');
                
                $.ajax({
                    url: window.location.href,
                    type: 'GET',
                    data: {
                        ajax: 'get_payment',
                        id: paymentId
                    },
                    dataType: 'json',
                    success: function(data) {
                        $('#edit_id').val(data.id);
                        $('#edit_student_id').val(data.student_id);
                        $('#edit_amount').val(data.amount);
                        $('#edit_payment_date').val(data.payment_date);
                        $('#edit_month').val(data.month);
                        $('#edit_status').val(data.status);
                        $('#edit_created_at').val(data.created_at);
                        
                        $('#editPaymentModal').modal('show');
                        $('#editPaymentModal .modal-body').removeClass('loading-opacity');
                    },
                    error: function() {
                        showAlert('Error loading payment data. Please try again.', 'danger');
                        $('#editPaymentModal .modal-body').removeClass('loading-opacity');
                    }
                });
            });
            
            // Handle edit form submission
            $('#editPaymentForm').submit(function(e) {
                e.preventDefault();
                
                var submitBtn = $(this).find('button[type="submit"]');
                var originalText = submitBtn.html();
                submitBtn.html('<i class="fas fa-spinner fa-spin"></i> Updating...').prop('disabled', true);
                
                $.ajax({
                    url: window.location.href + '&ajax=update_payment',
                    type: 'POST',
                    data: $(this).serialize(),
                    dataType: 'json',
                    success: function(response) {
                        if (response.success) {
                            showAlert(response.message, 'success');
                            $('#editPaymentModal').modal('hide');
                            setTimeout(function() { location.reload(); }, 1000);
                        } else {
                            showAlert('Error: ' + response.message, 'danger');
                            submitBtn.html(originalText).prop('disabled', false);
                        }
                    },
                    error: function() {
                        showAlert('Error updating payment record. Please try again.', 'danger');
                        submitBtn.html(originalText).prop('disabled', false);
                    }
                });
            });
            
            // Clear modals when closed
            $('#addPaymentModal').on('hidden.bs.modal', function() {
                $('#addPaymentForm')[0].reset();
            });
            
            $('#editPaymentModal').on('hidden.bs.modal', function() {
                $('#editPaymentForm')[0].reset();
            });
        });
    </script>
</body>
</html>