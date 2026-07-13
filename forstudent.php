<?php
// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    // session_start();
}

include "db.php";

// ការពារ user ប្តូរ link
if (!isset($_SESSION['id'])) {
    header("Location: login.php");
    exit();
}

$db_conn = $conn ?? $connection;

// ============================================
// យកអ៊ីមែលពី Session
// ============================================
$logged_in_email = $_SESSION['email'] ?? '';
$logged_in_user_id = (int)$_SESSION['id'];

// បើគ្មានអ៊ីមែលក្នុង Session ប្រើឈ្មោះ
if (empty($logged_in_email)) {
    $logged_in_name = $_SESSION['name'] ?? '';
    $sql_student_info = "SELECT * FROM students WHERE name = '$logged_in_name'";
} else {
    $sql_student_info = "SELECT * FROM students WHERE email = '$logged_in_email'";
}

$result_student_info = mysqli_query($db_conn, $sql_student_info);
$student_info = mysqli_fetch_assoc($result_student_info);

// ============================================
// បើរកមិនឃើញ បង្ហាញកំហុស
// ============================================
if (!$student_info) {
    echo "<div style='padding:30px; background:#f8d7da; color:#721c24; border-radius:10px; margin:20px; font-family:Arial;'>
        <h3>⚠️ Student Not Found!</h3>
        <p><strong>Email from Session:</strong> " . htmlspecialchars($logged_in_email) . "</p>
        <p><strong>Name from Session:</strong> " . htmlspecialchars($_SESSION['name'] ?? 'N/A') . "</p>
        <p><strong>Role:</strong> " . htmlspecialchars($_SESSION['role'] ?? 'N/A') . "</p>
        <hr>
        <p><strong>Available students in database:</strong></p>
        <ul>";
    
    $all_students = mysqli_query($db_conn, "SELECT id, name, email FROM students");
    while ($t = mysqli_fetch_assoc($all_students)) {
        echo "<li>ID: {$t['id']} - {$t['name']} ({$t['email']})</li>";
    }
    
    echo "</ul>
        <a href='logout.php' style='display:inline-block; padding:10px 20px; background:#dc3545; color:white; text-decoration:none; border-radius:5px;'>Logout</a>
    </div>";
    exit();
}

// ============================================
// យកទិន្នន័យសិស្ស
// ============================================
$logged_in_student = $student_info['name'];
$logged_in_id = $student_info['id'];
$student_department = $student_info['college'] ?? 'N/A';
$student_grade = $student_info['grade'] ?? 'N/A';
$student_phone = $student_info['phone'] ?? '';
$student_email_db = $student_info['email'] ?? '';
$student_gender = $student_info['gender'] ?? '';
$student_dob = $student_info['dob'] ?? '';
$student_address = $student_info['address'] ?? '';
$student_shift = $student_info['Shift'] ?? 'N/A'; // យក Shift របស់សិស្ស
$student_year = $student_info['year'] ?? 'N/A';
$student_skill = $student_info['skill'] ?? 'N/A';
$student_image = $student_info['image'] ?? '';

// ============================================
// កំណត់ user_id សម្រាប់ប្រើក្នុង Query
// ============================================
if (isset($_GET['search_id']) && !empty(trim($_GET['search_id']))) {
    $user_id = mysqli_real_escape_string($db_conn, trim($_GET['search_id']));
} else {
    $user_id = $logged_in_id;
}

// ============================================
// យកទិន្នន័យសិស្សតាម ID ដែលបានស្វែងរក
// ============================================
$student_query = "SELECT * FROM students WHERE id = '$user_id'";
$student_result = mysqli_query($db_conn, $student_query);

if (!$student_result) {
    die("Query Error (students): " . mysqli_error($db_conn));
}

$student = mysqli_fetch_assoc($student_result);

$search_error = false;
if (!$student) {
    $search_error = true;
    $user_id = $logged_in_id;
    $student_query = "SELECT * FROM students WHERE id = '$user_id'";
    $student_result = mysqli_query($db_conn, $student_query);
    $student = mysqli_fetch_assoc($student_result);
}

// ============================================
// យកទិន្នន័យផ្សេងៗ
// ============================================

// Enrollments
$enroll_query = "SELECT course_id, enroll_date FROM enrollments WHERE student_id = '$user_id' LIMIT 5";
$enroll_result = mysqli_query($db_conn, $enroll_query);
if (!$enroll_result) {
    $enroll_result = false;
}

// Scores
$scores_query = "SELECT course_id, score FROM scores WHERE student_id = '$user_id' ORDER BY id DESC LIMIT 5";
$scores_result = mysqli_query($db_conn, $scores_query);
if (!$scores_result) {
    $scores_result = false;
}

// ============================================
// TODAY'S CLASS SCHEDULE - ប្រើ teacher_classes តែតាម Shift របស់សិស្ស
// ============================================
$today = date('Y-m-d');
$day_of_week = date('l'); // Monday, Tuesday, etc.

// បង្កើត WHERE clause សម្រាប់តម្រង Shift
$shift_filter = "";
if (!empty($student_shift) && $student_shift != 'N/A') {
    // ប្រសិនបើ Shift របស់សិស្សជា 'Morning' ឬ 'Afternoon' ឬ 'Evening'
    // យើងត្រូវតម្រងតាម shift ក្នុង teacher_classes
    $shift_filter = "AND tc.shift = '" . mysqli_real_escape_string($db_conn, $student_shift) . "'";
}

$today_schedule_query = "SELECT 
    tc.*,
    t.name as teacher_name,
    t.email as teacher_email,
    t.phone as teacher_phone
FROM teacher_classes tc
LEFT JOIN teachers t ON tc.teacher_id = t.id
WHERE tc.schedule_day = '$day_of_week'
AND tc.status = 'active'
AND tc.date >= CURDATE()
$shift_filter
ORDER BY tc.start_time ASC
LIMIT 10";

$today_schedule_result = mysqli_query($db_conn, $today_schedule_query);
if (!$today_schedule_result) {
    $today_schedule_result = false;
}

// ============================================
// ATTENDANCE TODAY - ប្រើ attendance_student
// ============================================
$attendance_today_query = "SELECT * FROM attendance_student 
                           WHERE student_id = '$user_id' 
                           AND attendance_date = '$today'
                           ORDER BY id DESC LIMIT 1";
$attendance_today_result = mysqli_query($db_conn, $attendance_today_query);
$attendance_today = mysqli_fetch_assoc($attendance_today_result);

// ============================================
// Attendance Rate - ប្រើ attendance_student
// ============================================
$attendance_query = "SELECT 
    COUNT(*) as total_days,
    SUM(CASE WHEN status = 'present' THEN 1 ELSE 0 END) as present_days 
    FROM attendance_student 
    WHERE student_id = '$user_id' 
    AND MONTH(attendance_date) = MONTH(CURRENT_DATE())";
$att_data_result = mysqli_query($db_conn, $attendance_query);

if ($att_data_result && mysqli_num_rows($att_data_result) > 0) {
    $att_data = mysqli_fetch_assoc($att_data_result);
    $attendance_rate = ($att_data['total_days'] ?? 0) > 0 ? round(($att_data['present_days'] / $att_data['total_days']) * 100) : 0;
} else {
    $attendance_rate = 0;
}

// ============================================
// SUBJECTS WITH TEACHERS - ប្រើ teacher_classes តែតាម Shift របស់សិស្ស
// ============================================
$subjects_query = "SELECT DISTINCT
    tc.class_name,
    tc.subject,
    tc.room,
    tc.year,
    tc.shift,
    tc.semester,
    tc.academic_year,
    tc.schedule_day,
    tc.start_time,
    tc.end_time,
    tc.teacher_id,
    t.name as teacher_name,
    t.email as teacher_email,
    t.phone as teacher_phone,
    t.specialization
FROM teacher_classes tc
LEFT JOIN teachers t ON tc.teacher_id = t.id
WHERE tc.status = 'active'
AND tc.shift = '" . mysqli_real_escape_string($db_conn, $student_shift) . "'
AND tc.teacher_id IN (
    SELECT DISTINCT teacher_id 
    FROM teacher_classes 
    WHERE class_name IN (
        SELECT DISTINCT class_name 
        FROM enrollments e 
        JOIN teacher_classes tc2 ON e.course_id = tc2.id 
        WHERE e.student_id = '$user_id'
    )
)
ORDER BY tc.schedule_day, tc.start_time ASC";

$subjects_result = mysqli_query($db_conn, $subjects_query);
if (!$subjects_result) {
    $subjects_result = false;
}

// ============================================
// គណនាស្ថិតិ
// ============================================
$sql_total_students = mysqli_query($db_conn, "SELECT COUNT(*) as total FROM students");
$total_students_all = mysqli_fetch_assoc($sql_total_students)['total'] ?? 0;

$sql_total_colleges = mysqli_query($db_conn, "SELECT COUNT(DISTINCT college) as total FROM students");
$total_colleges = mysqli_fetch_assoc($sql_total_colleges)['total'] ?? 0;

$sql_same_dept = mysqli_query($db_conn, "SELECT COUNT(*) as total FROM students WHERE college = '$student_department'");
$total_same_dept = mysqli_fetch_assoc($sql_same_dept)['total'] ?? 0;

$sql_teachers_total = mysqli_query($db_conn, "SELECT COUNT(*) as total FROM teachers");
$total_teachers = mysqli_fetch_assoc($sql_teachers_total)['total'] ?? 0;
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <title>KRaksa Education - Student Dashboard</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        a{ text-decoration: none; }
        body { font-family: "Inter", sans-serif; background: #c5e1fc; color: #0f172a; overflow-x: hidden; }
        .container { display: flex; min-height: 100vh; max-width: 100%; }
        .sidebar { 
            width: 300px; 
            background: linear-gradient(90deg,rgba(117, 82, 243, 1) 19%, rgba(64, 24, 157, 1) 95%);
            color: #e2e8f0; flex-shrink: 0; position: sticky; top: 0; height: 100vh; overflow-y: auto; 
            box-shadow: 4px 0 20px rgba(0, 0, 0, 0.08); z-index: 10; margin-left: -12px;
        }
        .sidebar-header { padding: 28px 24px; border-bottom: 1px solid rgba(255, 255, 255, 0.1); margin-bottom: 24px; text-align: center; }
        .sidebar-header .profile-img { 
            width: 100px; 
            height: 100px; 
            border-radius: 50%; 
            margin: 0 auto 12px auto; 
            display: block; 
            border: 4px solid rgba(255, 255, 255, 0.8); 
            object-fit: cover; 
            box-shadow: 0 4px 15px rgba(0,0,0,0.2); 
        }
        .sidebar-header h1 { font-size: 1.5rem; font-weight: 700; color: white; margin-top: 10px; }
        .sidebar-header p { font-size: 0.85rem; opacity: 0.8; }
        .nav-menu { flex: 1; padding: 0 16px; }
        .nav-item { display: flex; align-items: center; gap: 15px; padding: 12px 16px; margin-bottom: 8px; border-radius: 14px; cursor: pointer; transition: 0.3s; color: #cbd5e6; text-decoration: none; }
        .nav-item:hover { background: rgba(255, 255, 255, 0.1); color: white; }
        .nav-item.active { background: #fd0054; color: white; box-shadow: 0 4px 8px rgba(0, 0, 0, 0.2); }
        .nav-bottom { margin-top: 30px; padding-top: 20px; border-top: 1px solid rgba(255,255,255,0.1); }
        
        .main-content { flex: 1; padding: 28px 32px; background: #f0f4f8; overflow-y: auto; height: 100vh; }
        .top-bar { display: flex; justify-content: space-between; align-items: center; padding: 12px 24px; background: white; border-radius: 16px; box-shadow: 0 2px 8px rgba(0,0,0,0.05); margin-bottom: 28px; gap: 15px; flex-wrap: wrap; }
        .page-title h2 { font-size: 1.3rem; font-weight: 600; color: #1e293b; margin: 0; }
        
        .search-box { display: flex; gap: 5px; max-width: 350px; width: 100%; }
        .search-box input { border-radius: 10px 0 0 10px; border: 1px solid #cbd5e1; padding: 6px 12px; font-size: 14px; width: 100%; }
        .search-box button { border-radius: 0 10px 10px 0; background: #4f46e5; color: white; border: none; padding: 6px 15px; font-size: 14px; transition: 0.2s; }
        .search-box button:hover { background: #3b35b3; }
        
        .profile-card { 
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border-radius: 20px;
            padding: 25px 30px;
            margin-bottom: 25px;
            color: white;
            box-shadow: 0 10px 30px rgba(102, 126, 234, 0.4);
            display: flex;
            align-items: center;
            gap: 30px;
            flex-wrap: wrap;
        }
        .profile-card .avatar {
            width: 80px;
            height: 80px;
            border-radius: 50%;
            background: rgba(255,255,255,0.2);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 35px;
            border: 3px solid white;
        }
        .profile-card .info h3 { margin: 0 0 5px 0; font-size: 1.5rem; }
        .profile-card .info p { margin: 0; opacity: 0.9; font-size: 0.95rem; }
        .profile-card .info .details { display: flex; gap: 25px; margin-top: 10px; flex-wrap: wrap; }
        .profile-card .info .details span { font-size: 0.85rem; opacity: 0.85; }
        
        .kpi-row { display: flex; flex-wrap: wrap; gap: 20px; margin-bottom: 32px; }
        .kpi-card { border-radius: 20px; padding: 20px 24px; flex: 1; min-width: 180px; box-shadow: 0 4px 12px rgba(0,0,0,0.08); transition: transform 0.3s ease; background: white; border-left: 6px solid; text-align: left; }
        .kpi-card:hover { transform: translateY(-3px); }
        .kpi-title { font-size: 13px; text-transform: uppercase; letter-spacing: 1px; opacity: 0.7; margin-bottom: 10px; font-weight: 600; }
        .kpi-number { font-size: 28px; font-weight: bold; margin-bottom: 5px; }
        
        .stats-row { display: flex; gap: 24px; margin-bottom: 28px; flex-wrap: wrap; }
        .stats-col { flex: 1; min-width: 280px; }
        .chart-box { background: white; padding: 20px; border-radius: 20px; box-shadow: 0 4px 12px rgba(0,0,0,0.05); height: 100%; }
        .chart-box h4 { font-size: 16px; font-weight: 600; margin-bottom: 20px; color: #1e293b; }
        
        .info-table { width: 100%; background: white; border-radius: 16px; overflow: hidden; }
        .info-table th, .info-table td { padding: 12px 16px; text-align: left; border-bottom: 1px solid #e2e8f0; }
        .info-table th { background: #f8fafc; font-weight: 600; color: #475569; }
        .score-high { color: #10b981; font-weight: 700; }
        .score-low { color: #ef4444; font-weight: 700; }
        .score-medium { color: #f59e0b; font-weight: 700; }
        
        .status-present { color: #10b981; font-weight: 600; }
        .status-absent { color: #ef4444; font-weight: 600; }
        .status-late { color: #f59e0b; font-weight: 600; }
        
        .schedule-card { 
            background: white; 
            border-radius: 16px; 
            padding: 15px 20px; 
            margin-bottom: 12px;
            border-left: 4px solid #4f46e5;
            box-shadow: 0 2px 8px rgba(0,0,0,0.05);
            transition: 0.2s;
        }
        .schedule-card:hover { transform: translateX(5px); }
        .schedule-card .time { font-weight: 600; color: #4f46e5; }
        .schedule-card .subject { font-weight: 500; }
        .schedule-card .teacher { color: #64748b; font-size: 0.9rem; }
        
        .teacher-badge {
            display: inline-block;
            background: #e0e7ff;
            color: #4338ca;
            padding: 2px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 500;
        }
        
        .schedule-day-badge {
            display: inline-block;
            background: #f1f5f9;
            color: #475569;
            padding: 2px 10px;
            border-radius: 12px;
            font-size: 11px;
            font-weight: 500;
        }
        
        .shift-badge {
            display: inline-block;
            background: #fef3c7;
            color: #92400e;
            padding: 2px 10px;
            border-radius: 12px;
            font-size: 11px;
            font-weight: 500;
        }
        
        canvas { max-height: 280px; width: 100%; }
        
        @media (max-width: 768px) {
            .sidebar { width: 70px; }
            .sidebar-header h1, .sidebar-header p, .nav-item span { display: none; }
            .nav-item { justify-content: center; }
            .main-content { padding: 15px; }
            .profile-card { flex-direction: column; text-align: center; }
            .profile-card .info .details { justify-content: center; }
            .top-bar { flex-direction: column; align-items: flex-start; }
            .search-box { max-width: 100%; }
            .sidebar-header .profile-img { width: 50px; height: 50px; }
        }
    </style>
</head>
<body>
    <script>
    if(sessionStorage.getItem('login') == null){
        window.location='login.php';
    }
</script>

<div class="container">
    <div class="sidebar">
        <div class="sidebar-header">
            <img src="<?php echo !empty($student_image) ? htmlspecialchars($student_image) : 'https://i.pinimg.com/736x/be/dd/b8/beddb8c8c3c4c967cb821aae0cb796e3.jpg'; ?>" 
                 alt="Profile" 
                 class="profile-img" />
            <h1><?php echo htmlspecialchars($logged_in_student); ?></h1>
            <p>
                <i class="fas fa-graduation-cap"></i> <?php echo htmlspecialchars($student_department); ?>
            </p>
            <p style="font-size: 0.75rem; opacity: 0.7;">
                <i class="fas fa-school"></i> Grade: <?php echo htmlspecialchars($student_grade); ?>
            </p>
        </div>
        <div class="nav-menu">
            <a href="forstudent.php" class="nav-item active"><i class="fas fa-tachometer-alt"></i> <span>Main Dashboard</span></a>
            <a href="scores_stu.php" class="nav-item"><i class="fas fa-graduation-cap"></i> <span>Scores</span></a>
            <a href="attendance_student.php" class="nav-item"><i class="fas fa-calendar-check"></i> <span>Attendance</span></a>
            <a href="Requests.php" class="nav-item"><i class="fas fa-calendar-check"></i> <span>Request</span></a>
            <a href="settings_student.php" class="nav-item"><i class="fas fa-cog"></i> <span>Setting</span></a>
            <div class="nav-bottom">
                <a href="logout.php" class="nav-item" style="padding-left:8px;">
                    <i class="fas fa-sign-out-alt"></i> <span>Logout</span>
                </a>
            </div>
        </div>
    </div>

    <div class="main-content">
        <div class="top-bar">
            <div class="page-title"><h2><i class="fas fa-user-graduate me-2"></i>Student Dashboard</h2></div>
            
                   
            <div class="date-time" id="currentDateTime"></div>
        </div>
        
        <!-- STUDENT PROFILE CARD -->
        <div class="profile-card">
            <div class="avatar">
                <i class="fas fa-user-graduate"></i>
            </div>
            <div class="info">
                <h3><?php echo htmlspecialchars($student['name']); ?></h3>
                <p><i class="fas fa-envelope"></i> <?php echo htmlspecialchars($student['email'] ?? 'N/A'); ?></p>
                <div class="details">
                    <span><i class="fas fa-id-card"></i> ID: <?php echo htmlspecialchars($student['id']); ?></span>
                    <span><i class="fas fa-phone"></i> <?php echo htmlspecialchars($student['phone'] ?? 'N/A'); ?></span>
                    <span><i class="fas fa-venus-mars"></i> <?php echo htmlspecialchars($student['gender'] ?? 'N/A'); ?></span>
                    <span><i class="fas fa-calendar"></i> DOB: <?php echo htmlspecialchars($student['dob'] ?? 'N/A'); ?></span>
                    <span><i class="fas fa-graduation-cap"></i> College: <?php echo htmlspecialchars($student['college'] ?? 'N/A'); ?></span>
                    <span><i class="fas fa-school"></i> Grade: <?php echo htmlspecialchars($student['grade'] ?? 'N/A'); ?></span>
                    <span><i class="fas fa-clock"></i> Shift: <?php echo htmlspecialchars($student['Shift'] ?? 'N/A'); ?></span>
                </div>
            </div>
        </div>
        
        <!-- KPI CARDS -->
        <div class="kpi-row">
            <div class="kpi-card" style="border-left-color: #4f46e5;">
                <div class="kpi-title"><i class="fas fa-chart-line"></i> Performance</div>
                <div class="kpi-number" id="gpaValue">--</div>
                <small>Average Score</small>
            </div>
            <div class="kpi-card" style="border-left-color: #10b981;">
                <div class="kpi-title"><i class="fas fa-calendar-check"></i> Attendance Rate</div>
                <div class="kpi-number"><?php echo $attendance_rate; ?>%</div>
                <small>This month (Status 'present')</small>
            </div>
            <div class="kpi-card" style="border-left-color: #f59e0b;">
                <div class="kpi-title"><i class="fas fa-book"></i> Enrolled Courses</div>
                <div class="kpi-number" id="coursesCount">--</div>
                <small>Active classes</small>
            </div>
            <div class="kpi-card" style="border-left-color: #ef4444;">
                <div class="kpi-title"><i class="fas fa-clock"></i> Shift</div>
                <div class="kpi-number" style="font-size: 20px; padding-top: 8px;">
                    <span class="shift-badge"><?php echo htmlspecialchars($student['Shift'] ?? 'N/A'); ?></span>
                </div>
                <small>Study Time</small>
            </div>
        </div>
        
        <!-- TODAY'S CLASS SCHEDULE -->
        <div class="stats-row">
            <div class="stats-col" style="flex: 2;">
                <div class="chart-box">
                    <h4>
                        <i class="fas fa-calendar-day"></i> Today's Class Schedule - <?php echo date('l, d/m/Y'); ?>
                        <?php if (!empty($student_shift) && $student_shift != 'N/A'): ?>
                            <span class="shift-badge ms-2"><i class="fas fa-clock"></i> <?php echo htmlspecialchars($student_shift); ?> Shift</span>
                        <?php endif; ?>
                    </h4>
                    <?php if ($today_schedule_result && mysqli_num_rows($today_schedule_result) > 0): ?>
                        <?php while ($schedule = mysqli_fetch_assoc($today_schedule_result)): ?>
                            <div class="schedule-card">
                                <div class="d-flex justify-content-between align-items-center flex-wrap">
                                    <div>
                                        <span class="time"><i class="far fa-clock"></i> <?php echo date('h:i A', strtotime($schedule['start_time'])); ?> - <?php echo date('h:i A', strtotime($schedule['end_time'])); ?></span>
                                        <span class="subject ms-3"><i class="fas fa-book"></i> <?php echo htmlspecialchars($schedule['class_name'] ?? $schedule['subject'] ?? 'N/A'); ?></span>
                                    </div>
                                    <div>
                                        <span class="teacher-badge">
                                            <i class="fas fa-chalkboard-teacher"></i> <?php echo htmlspecialchars($schedule['teacher_name'] ?? 'N/A'); ?>
                                        </span>
                                        <span class="badge bg-secondary ms-2"><?php echo htmlspecialchars($schedule['room'] ?? 'N/A'); ?></span>
                                        <?php if (!empty($schedule['shift'])): ?>
                                            <span class="badge bg-info ms-2"><?php echo htmlspecialchars($schedule['shift']); ?></span>
                                        <?php endif; ?>
                                    </div>
                                </div>
                                <?php if (!empty($schedule['subject'])): ?>
                                    <small class="text-muted d-block mt-1"><i class="fas fa-tag"></i> Subject: <?php echo htmlspecialchars($schedule['subject']); ?></small>
                                <?php endif; ?>
                                <?php if (!empty($schedule['year'])): ?>
                                    <small class="text-muted d-block">Year: <?php echo htmlspecialchars($schedule['year']); ?> | Semester: <?php echo htmlspecialchars($schedule['semester'] ?? 'N/A'); ?></small>
                                <?php endif; ?>
                            </div>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <div class="text-center text-muted py-4">
                            <i class="fas fa-clock fa-3x mb-3 d-block" style="opacity: 0.3;"></i>
                            <p>No classes scheduled for today <strong>(<?php echo htmlspecialchars($student_shift); ?> Shift)</strong>.</p>
                            <small>Enjoy your day off! 🎉</small>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
            
            <!-- ATTENDANCE TODAY -->
            <div class="stats-col" style="flex: 1;">
                <div class="chart-box">
                    <h4><i class="fas fa-clipboard-check"></i> Attendance Today</h4>
                    <?php if ($attendance_today): ?>
                        <div class="text-center py-3">
                            <div style="font-size: 3rem; margin-bottom: 10px;">
                                <?php 
                                $status = strtolower($attendance_today['status'] ?? '');
                                if ($status == 'present'): ?>
                                    <span class="status-present">✅</span>
                                <?php elseif ($status == 'late'): ?>
                                    <span class="status-late">⏰</span>
                                <?php elseif ($status == 'absent'): ?>
                                    <span class="status-absent">❌</span>
                                <?php else: ?>
                                    <span>❓</span>
                                <?php endif; ?>
                            </div>
                            <h5>
                                <?php 
                                    $status_text = ucfirst($attendance_today['status'] ?? 'Unknown');
                                    $status_class = '';
                                    if ($status == 'present') {
                                        $status_class = 'status-present';
                                    } elseif ($status == 'late') {
                                        $status_class = 'status-late';
                                    } elseif ($status == 'absent') {
                                        $status_class = 'status-absent';
                                    }
                                ?>
                                <span class="<?php echo $status_class; ?>"><?php echo htmlspecialchars($status_text); ?></span>
                            </h5>
                            <?php if (!empty($attendance_today['attendance_date'])): ?>
                                <small class="text-muted">Date: <?php echo date('d/m/Y', strtotime($attendance_today['attendance_date'])); ?></small>
                            <?php endif; ?>
                            <?php if (!empty($attendance_today['class_name'])): ?>
                                <p class="mt-1"><small class="text-muted">Class ID: <?php echo htmlspecialchars($attendance_today['class_name']); ?></small></p>
                            <?php endif; ?>
                            <?php if (!empty($attendance_today['student_name'])): ?>
                                <p class="mt-1"><small><i class="fas fa-user"></i> <?php echo htmlspecialchars($attendance_today['student_name']); ?></small></p>
                            <?php endif; ?>
                        </div>
                    <?php else: ?>
                        <div class="text-center text-muted py-4">
                            <i class="fas fa-question-circle fa-3x mb-3 d-block" style="opacity: 0.3;"></i>
                            <p>No attendance recorded for today.</p>
                            <small>Please check with your teacher.</small>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        
        
        <div class="stats-row">
            <div class="stats-col">
                <div class="chart-box">
                    <h4><i class="fas fa-info-circle"></i> Study Info Summary</h4>
                    <p><strong>College/Major:</strong> <?php echo htmlspecialchars($student['college'] ?? 'N/A'); ?></p>
                    <p><strong>Skill/Focus:</strong> <?php echo htmlspecialchars($student['skill'] ?? 'N/A'); ?></p>
                    <p><strong>Year:</strong> <?php echo htmlspecialchars($student['year'] ?? 'N/A'); ?></p>
                    <p><strong>Shift:</strong> <span class="shift-badge"><?php echo htmlspecialchars($student['Shift'] ?? 'N/A'); ?></span></p>
                    <p><strong>Address:</strong> <?php echo htmlspecialchars($student['address'] ?? 'N/A'); ?></p>
                </div>
            </div>
        </div>
        
        <div class="stats-row">
            <!-- <div class="stats-col">
                <div class="chart-box">
                    <h4><i class="fas fa-star"></i> Recent Exam Scores</h4>
                    <table class="info-table">
                        <thead>
                            <tr><th>Course ID</th><th>Score</th></tr>
                        </thead>
                        <tbody>
                            <?php 
                            if ($scores_result && mysqli_num_rows($scores_result) > 0):
                                $total_score = 0;
                                $score_count = 0;
                                while ($score = mysqli_fetch_assoc($scores_result)): 
                                    $total_score += $score['score'];
                                    $score_count++;
                                    $scoreClass = $score['score'] >= 80 ? 'score-high' : ($score['score'] >= 50 ? 'score-medium' : 'score-low');
                            ?>
                            <tr>
                                <td>Course #<?php echo htmlspecialchars($score['course_id']); ?></td>
                                <td class="<?php echo $scoreClass; ?>"><?php echo $score['score']; ?></td>
                            </tr>
                            <?php 
                                endwhile;
                                $avg_score = $score_count > 0 ? round($total_score / $score_count, 2) : 0;
                            else:
                                $avg_score = 0;
                            ?>
                            <tr><td colspan="2" class="text-center text-muted">No scores yet</td></tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div> -->
            <!-- <div class="stats-col">
                <div class="chart-box">
                    <h4><i class="fas fa-book-open"></i> My Enrolled Courses</h4>
                    <table class="info-table">
                        <thead><tr><th>Course ID</th><th>Enrolled Date</th></tr></thead>
                        <tbody>
                            <?php 
                            $course_count = 0;
                            if ($enroll_result && mysqli_num_rows($enroll_result) > 0):
                                while ($enroll = mysqli_fetch_assoc($enroll_result)): 
                                    $course_count++;
                            ?>
                            <tr>
                                <td>Course #<?php echo htmlspecialchars($enroll['course_id']); ?></td>
                                <td><?php echo (!empty($enroll['enroll_date'])) ? date('d/m/Y', strtotime($enroll['enroll_date'])) : 'N/A'; ?></td>
                            </tr>
                            <?php 
                                endwhile;
                            else:
                            ?>
                            <tr><td colspan="2" class="text-center text-muted">No courses enrolled</td></tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div> -->
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

<script>
    document.getElementById('gpaValue').innerText = "<?php echo $avg_score; ?>";
    document.getElementById('coursesCount').innerText = "<?php echo $course_count; ?>";
    
    function updateDateTime() {
        const now = new Date();
        const options = { weekday: 'long', year: 'numeric', month: 'long', day: 'numeric', hour: '2-digit', minute: '2-digit' };
        document.getElementById('currentDateTime').innerHTML = now.toLocaleDateString('en-US', options);
    }
    updateDateTime();
    setInterval(updateDateTime, 60000);
    
    const ctx = document.getElementById('scoresChart').getContext('2d');
    new Chart(ctx, {
        type: 'bar',
        data: {
            labels: [
                <?php 
                if ($scores_result && mysqli_num_rows($scores_result) > 0) {
                    mysqli_data_seek($scores_result, 0); 
                    while ($score = mysqli_fetch_assoc($scores_result)) {
                        echo "'Course #" . $score['course_id'] . "',";
                    }
                } else {
                    echo "'No Data'";
                }
                ?>
            ],
            datasets: [{
                label: 'Score',
                data: [
                    <?php 
                    if ($scores_result && mysqli_num_rows($scores_result) > 0) {
                        mysqli_data_seek($scores_result, 0);
                        while ($score = mysqli_fetch_assoc($scores_result)) {
                            echo $score['score'] . ",";
                        }
                    } else {
                        echo "0";
                    }
                    ?>
                ],
                backgroundColor: '#4f46e5',
                borderRadius: 8
            }]
        },
        options: {
            responsive: true,
            scales: { y: { beginAtZero: true, max: 100 } }
        }
    });
</script>
</body>
</html>