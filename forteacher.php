<?php
    if (session_status() === PHP_SESSION_NONE) {
        // session_start();
    }
    
    include "db.php";
    
    // ការពារ user ប្តូរ link
    if(!isset($_SESSION['id'])){
        header("Location: login.php");
        exit();
    }
    
    // ============================================
    // យកអ៊ីមែលពី Session
    // ============================================
    $logged_in_email = $_SESSION['email'] ?? '';
    
    // បើគ្មានអ៊ីមែលក្នុង Session ប្រើឈ្មោះ
    if(empty($logged_in_email)) {
        $logged_in_name = $_SESSION['name'] ?? '';
        $sql_teacher_info = "SELECT * FROM teachers WHERE name = '$logged_in_name'";
    } else {
        $sql_teacher_info = "SELECT * FROM teachers WHERE email = '$logged_in_email'";
    }
    
    $result_teacher_info = mysqli_query($conn, $sql_teacher_info);
    $teacher_info = mysqli_fetch_assoc($result_teacher_info);
    
    // ============================================
    // បើរកមិនឃើញ បង្ហាញកំហុស
    // ============================================
    if(!$teacher_info) {
        echo "<div style='padding:30px; background:#f8d7da; color:#721c24; border-radius:10px; margin:20px; font-family:Arial;'>
            <h3>⚠️ Teacher Not Found!</h3>
            <p><strong>Email from Session:</strong> " . htmlspecialchars($logged_in_email) . "</p>
            <p><strong>Name from Session:</strong> " . htmlspecialchars($_SESSION['name'] ?? 'N/A') . "</p>
            <p><strong>Role:</strong> " . htmlspecialchars($_SESSION['role'] ?? 'N/A') . "</p>
            <hr>
            <p><strong>Available teachers in database:</strong></p>
            <ul>";
        
        $all_teachers = mysqli_query($conn, "SELECT id, name, email FROM teachers");
        while($t = mysqli_fetch_assoc($all_teachers)) {
            echo "<li>ID: {$t['id']} - {$t['name']} ({$t['email']})</li>";
        }
        
        echo "</ul>
            <a href='logout.php' style='display:inline-block; padding:10px 20px; background:#dc3545; color:white; text-decoration:none; border-radius:5px;'>Logout</a>
        </div>";
        exit();
    }
    
    // ============================================
    // យកទិន្នន័យគ្រូ
    // ============================================
    $logged_in_teacher = $teacher_info['name'];
    $logged_in_id = $teacher_info['id'];
    $teacher_department = $teacher_info['department'] ?? 'IT';
    $teacher_subject = $teacher_info['subject'] ?? '';
    $teacher_phone = $teacher_info['phone'] ?? '';
    $teacher_email_db = $teacher_info['email'] ?? '';
    $teacher_gender = $teacher_info['gender'] ?? '';
    $teacher_dob = $teacher_info['dob'] ?? '';
    $teacher_salary = $teacher_info['salary'] ?? 0;
    $teacher_address = $teacher_info['address'] ?? '';
    $teacher_image = $teacher_info['image'] ?? '';
    
    // ============================================
    // គណនាស្ថិតិ
    // ============================================
    $sql_students_count = mysqli_query($conn, "SELECT COUNT(*) as total FROM students WHERE college = '$teacher_department'");
    $total_students_by_dept = mysqli_fetch_assoc($sql_students_count)['total'] ?? 0;
    
    $sql_total_students = mysqli_query($conn, "SELECT COUNT(*) as total FROM students");
    $total_students_all = mysqli_fetch_assoc($sql_total_students)['total'] ?? 0;
    
    $sql_total_college = mysqli_query($conn, "SELECT COUNT(DISTINCT college) as total FROM students");
    $total_colleges = mysqli_fetch_assoc($sql_total_college)['total'] ?? 0;
    
    // ============================================
    // យកកាលវិភាគដែលមានត្រងតាម Subject ឬ Teacher Name
    // ============================================
    // ទទួលតម្លៃ Filter ពី GET
    $filter_type = isset($_GET['filter_type']) ? $_GET['filter_type'] : 'subject';
    $filter_value = isset($_GET['filter_value']) ? $_GET['filter_value'] : '';
    
    // ប្រសិនបើគ្មានតម្លៃ Filter ប្រើ subject របស់គ្រូដែល Login
    if(empty($filter_value)) {
        $filter_value = $teacher_subject;
    }
    
    // បង្កើត Query តាមប្រភេទ Filter
    if($filter_type == 'subject') {
        $schedule_query = "SELECT * FROM schedule_class 
                           WHERE subject = '$filter_value' 
                           ORDER BY date DESC, time_star ASC LIMIT 10";
    } else if($filter_type == 'teacher') {
        // បើតារាង schedule_class មានជួរឈរ teacher_name
        // បើមិនមាន អាចប្រើ WHERE teacher_name = '$logged_in_teacher'
        $schedule_query = "SELECT * FROM schedule_class 
                           WHERE teacher_name = '$filter_value' 
                           ORDER BY date DESC, time_star ASC LIMIT 10";
    } else {
        // Default: បង្ហាញទាំងអស់ (ឬតាម department)
        $schedule_query = "SELECT * FROM schedule_class 
                           WHERE department = '$teacher_department' 
                           ORDER BY date DESC, time_star ASC LIMIT 10";
    }
    
    $schedule_result = mysqli_query($conn, $schedule_query);
    
    // ============================================
    // យកបញ្ជីមុខវិជ្ជាទាំងអស់សម្រាប់ Dropdown Filter
    // ============================================
    $subjects_query = "SELECT DISTINCT subject FROM schedule_class ORDER BY subject ASC";
    $subjects_result = mysqli_query($conn, $subjects_query);
    $subjects_list = [];
    while($sub = mysqli_fetch_assoc($subjects_result)) {
        $subjects_list[] = $sub['subject'];
    }
    
    // ============================================
    // យកបញ្ជីគ្រូទាំងអស់សម្រាប់ Dropdown Filter
    // ============================================
    // បើតារាង schedule_class មាន teacher_name
    $teachers_query = "SELECT DISTINCT teacher_name FROM schedule_class WHERE teacher_name IS NOT NULL AND teacher_name != '' ORDER BY teacher_name ASC";
    $teachers_result = mysqli_query($conn, $teachers_query);
    $teachers_list = [];
    while($tch = mysqli_fetch_assoc($teachers_result)) {
        $teachers_list[] = $tch['teacher_name'];
    }
    
    // ============================================
    // យកពិន្ទុសិស្ស
    // ============================================
    $progress_query = "SELECT s.*, st.college 
                       FROM scores s 
                       JOIN students st ON s.name = st.name 
                       WHERE s.subject = '$teacher_subject' 
                       ORDER BY s.score DESC 
                       LIMIT 5";
    $progress_result = mysqli_query($conn, $progress_query);
    
    $top_query = "SELECT s.*, st.college 
                  FROM scores s 
                  JOIN students st ON s.name = st.name 
                  WHERE s.subject = '$teacher_subject' AND s.Grade IN ('A','B') 
                  ORDER BY s.score DESC 
                  LIMIT 5";
    $top_result = mysqli_query($conn, $top_query);
    
    $sql_teachers_total = mysqli_query($conn, "SELECT COUNT(*) as total FROM teachers");
    $total_teachers = mysqli_fetch_assoc($sql_teachers_total)['total'] ?? 0;
    
    // ============================================
    // យកថ្នាក់ដែលគ្រូបង្រៀននៅថ្ងៃនេះ
    // ============================================
    $today = date('Y-m-d');
    $current_day = date('l');
    
    $today_classes_query = "SELECT tc.*, 
                            tl.id as log_id, 
                            tl.status as teaching_status,
                            tl.teaching_date,
                            tl.notes as teaching_notes
                            FROM teacher_classes tc
                            LEFT JOIN teaching_log tl ON tc.id = tl.class_id 
                                AND tl.teacher_id = tc.teacher_id 
                                AND tl.teaching_date = '$today'
                            WHERE tc.teacher_id = '$logged_in_id' 
                            AND tc.status = 'active'
                            AND (tc.schedule_day = '$current_day' OR tc.schedule_day IS NULL)
                            ORDER BY tc.start_time ASC";
    $today_classes_result = mysqli_query($conn, $today_classes_query);
    
    $completed_count = 0;
    $pending_count = 0;
    $classes_today = [];
    while($class = mysqli_fetch_assoc($today_classes_result)) {
        $classes_today[] = $class;
        if($class['teaching_status'] == 'completed') {
            $completed_count++;
        } else {
            $pending_count++;
        }
    }
    
    // ============================================
    // ដំណើរការសម្គាល់ថាបានបង្រៀនហើយ
    // ============================================
    if(isset($_POST['mark_taught'])) {
        $class_id = mysqli_real_escape_string($conn, $_POST['class_id']);
        $teaching_date = mysqli_real_escape_string($conn, $_POST['teaching_date']);
        $notes = mysqli_real_escape_string($conn, $_POST['notes'] ?? '');
        
        $check_sql = "SELECT id FROM teaching_log 
                      WHERE teacher_id = '$logged_in_id' 
                      AND class_id = '$class_id' 
                      AND teaching_date = '$teaching_date'";
        $check_result = mysqli_query($conn, $check_sql);
        
        if(mysqli_num_rows($check_result) > 0) {
            $update_sql = "UPDATE teaching_log SET 
                           status = 'completed',
                           notes = '$notes',
                           updated_at = NOW()
                           WHERE teacher_id = '$logged_in_id' 
                           AND class_id = '$class_id' 
                           AND teaching_date = '$teaching_date'";
            mysqli_query($conn, $update_sql);
        } else {
            $class_info_sql = "SELECT class_name, subject FROM teacher_classes WHERE id = '$class_id'";
            $class_info_result = mysqli_query($conn, $class_info_sql);
            $class_info = mysqli_fetch_assoc($class_info_result);
            
            $insert_sql = "INSERT INTO teaching_log (
                teacher_id, class_id, class_name, subject, 
                teaching_date, status, notes
            ) VALUES (
                '$logged_in_id', '$class_id', 
                '{$class_info['class_name']}', '{$class_info['subject']}',
                '$teaching_date', 'completed', '$notes'
            )";
            mysqli_query($conn, $insert_sql);
        }
        
        echo "<script>window.location='forteacher.php';</script>";
        exit();
    }
    
    // ============================================
    // ដំណើរការមិនទាន់បង្រៀន (Pending)
    // ============================================
    if(isset($_POST['mark_pending'])) {
        $class_id = mysqli_real_escape_string($conn, $_POST['class_id']);
        $teaching_date = mysqli_real_escape_string($conn, $_POST['teaching_date']);
        
        $update_sql = "UPDATE teaching_log SET 
                       status = 'pending'
                       WHERE teacher_id = '$logged_in_id' 
                       AND class_id = '$class_id' 
                       AND teaching_date = '$teaching_date'";
        mysqli_query($conn, $update_sql);
        
        echo "<script>window.location='forteacher.php';</script>";
        exit();
    }
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
    <title>Teacher Dashboard</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        a{ text-decoration: none; }
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
        .sidebar-header { padding: 25px 20px; border-bottom: 1px solid rgba(255, 255, 255, 0.1); margin-bottom: 20px; text-align: center; }
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
        .sidebar-header .teacher-name {
            font-size: 1.2rem;
            font-weight: 700;
            color: white;
            margin: 0;
            line-height: 1.3;
        }
        .sidebar-header .teacher-dept {
            font-size: 0.85rem;
            opacity: 0.85;
            margin: 4px 0 0 0;
        }
        .sidebar-header .teacher-subject {
            font-size: 0.75rem;
            opacity: 0.7;
            margin: 2px 0 0 0;
        }
        .nav-menu { flex: 1; padding: 0 16px; }
        .nav-item { display: flex; align-items: center; gap: 15px; padding: 12px 16px; margin-bottom: 8px; border-radius: 14px; cursor: pointer; transition: 0.3s; color: #cbd5e6; text-decoration: none; }
        .nav-item:hover { background: rgba(255, 255, 255, 0.1); color: white; }
        .nav-item.active { background: #fd0054; color: white; box-shadow: 0 4px 8px rgba(0, 0, 0, 0.2); }
        .nav-bottom { margin-top: 30px; padding-top: 20px; border-top: 1px solid rgba(255,255,255,0.1); }
        
        .main-content { flex: 1; padding: 28px 32px; background: #f8fafc; overflow-y: auto; height: 100vh; }
        .top-bar { display: flex; justify-content: space-between; align-items: center; padding: 12px 24px; background: white; border-radius: 60px; box-shadow: 0 1px 3px rgba(0,0,0,0.05); margin-bottom: 28px; flex-wrap: wrap; gap: 15px; }
        .page-title h2 { font-size: 1.3rem; font-weight: 600; color: #1e293b; margin: 0; }
        
        .kpi-row { display: flex; flex-wrap: wrap; gap: 20px; margin-bottom: 32px; color:black; }
        .kpi-card { border-radius: 20px; padding: 20px 24px; flex: 1; min-width: 200px; box-shadow: 0 10px 25px rgba(0,0,0,0.1); transition: transform 0.3s ease; color:black; background: white; border-bottom: 8px solid; text-align: left; }
        .kpi-card:hover { transform: translateY(-5px); }
        .kpi-title { font-size: 14px; text-transform: uppercase; letter-spacing: 1px; opacity: 0.9; margin-bottom: 10px; font-weight: 500; color:black; }
        .kpi-number { font-size: 30px; font-weight: bold; margin-bottom: 5px; }
        
        .chart-box { background: white; padding: 25px; border-radius: 20px; box-shadow: 0 5px 20px rgba(0,0,0,0.05); margin-top: 20px; }
        .stats-row { display: flex; gap: 20px; margin-top: 20px; }
        .stats-col { flex: 1; }
        canvas { max-height: 400px; width: 100%; }
        
        .table { font-size: 13px; margin-top: 10px; }
        .table th { background: #f1f5f9; font-weight: 600; }
        
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
        
        .badge-status {
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 0.75rem;
            font-weight: 600;
        }
        .badge-completed { background: #dcfce7; color: #166534; }
        .badge-pending { background: #fef3c7; color: #92400e; }
        
        @media (max-width: 768px) {
            .sidebar { width: 80px; }
            .sidebar-header h1, .sidebar-header p, .nav-item span { display: none; }
            .nav-item { justify-content: center; }
            .main-content { padding: 15px; }
            .kpi-card { min-width: 150px; }
            .kpi-number { font-size: 24px; }
            .stats-row { flex-direction: column; }
            .profile-card { flex-direction: column; text-align: center; }
            .profile-card .info .details { justify-content: center; }
            .sidebar-header .profile-img { width: 50px; height: 50px; }
            .sidebar-header .teacher-name { display: none; }
            .sidebar-header .teacher-dept { display: none; }
            .sidebar-header .teacher-subject { display: none; }
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
            <img src="<?php echo !empty($teacher_image) ? $teacher_image : $logged_in_teacher ; ?>" 
                 alt="Profile" 
                 class="profile-img" />
            <h1 class="teacher-name"><?php echo htmlspecialchars($logged_in_teacher); ?></h1>
            <p class="teacher-dept">
                <i class="fas fa-graduation-cap"></i> <?php echo htmlspecialchars($teacher_department); ?>
            </p>
            <p class="teacher-subject">
                <i class="fas fa-book"></i> <?php echo htmlspecialchars($teacher_subject); ?>
            </p>
        </div>

        <div class="nav-menu">
            <a href="forteacher.php" class="nav-item active">
                <i class="fas fa-tachometer-alt"></i>
                <span>Main Dashboard</span>
            </a>
            <a href="class.php" class="nav-item">
                <i class="fas fa-chalkboard-teacher"></i>
                <span>Class</span>
            </a>
            <a href="Request.php" class="nav-item">
                <i class="fas fa-file-signature"></i>
                <span>Request</span>
            </a>
            <a href="settings_teacher.php" class="nav-item">
                <i class="fas fa-cog"></i>
                <span>Setting</span>
            </a>
            <div class="nav-bottom">
                <a href="logout.php" class="nav-item" style="padding-left:8px;">
                    <i class="fas fa-sign-out-alt"></i>
                    <span>Logout</span>
                </a>
            </div>
        </div>
    </div>

    <div class="main-content">
        <div class="top-bar">
            <div class="page-title"><h2><i class="fas fa-chalkboard-teacher"></i> Teacher Dashboard</h2></div>
            <div class="date-time" id="currentDateTime"></div>
        </div>
        
        <!-- TEACHER PROFILE CARD -->
        <div class="profile-card">
            <div class="avatar">
                <i class="fas fa-user-tie"></i>
            </div>
            <div class="info">
                <h3><?php echo htmlspecialchars($logged_in_teacher); ?></h3>
                <p><i class="fas fa-envelope"></i> <?php echo htmlspecialchars($teacher_email_db); ?></p>
                <div class="details">
                    <span><i class="fas fa-id-card"></i> ID: <?php echo htmlspecialchars($logged_in_id); ?></span>
                    <span><i class="fas fa-phone"></i> <?php echo htmlspecialchars($teacher_phone); ?></span>
                    <span><i class="fas fa-venus-mars"></i> <?php echo htmlspecialchars($teacher_gender); ?></span>
                    <span><i class="fas fa-calendar"></i> DOB: <?php echo htmlspecialchars($teacher_dob); ?></span>
                    <span><i class="fas fa-graduation-cap"></i> Dept: <?php echo htmlspecialchars($teacher_department); ?></span>
                    <span><i class="fas fa-book"></i> Subject: <?php echo htmlspecialchars($teacher_subject); ?></span>
                </div>
            </div>
        </div>

        <!-- ============================================ -->
        <!-- TODAY'S CLASSES                              -->
        <!-- ============================================ -->
        <div class="kpi-row">
            <div class="kpi-card" style="border-bottom-color: #10b981; width: 100%;">
                <div class="kpi-title">
                    <i class="fas fa-calendar-day"></i> 
                    Today's Classes - <?php echo date('l, d F Y'); ?>
                    <span class="badge bg-success ms-2">Completed: <?php echo $completed_count; ?></span>
                    <span class="badge bg-warning ms-2">Pending: <?php echo $pending_count; ?></span>
                </div>
                <small>
                    <i class="fas fa-user"></i> Teacher: <strong><?php echo htmlspecialchars($logged_in_teacher); ?></strong>
                </small>
                
                <?php if(count($classes_today) > 0): ?>
                    <div class="table-responsive mt-3">
                        <table class="table table-bordered">
                            <thead>
                                <tr>
                                    <th>#</th>
                                    <th>Class</th>
                                    <th>Subject</th>
                                    <th>Room</th>
                                    <th>Time</th>
                                    <th>Status</th>
                                    <th>Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php 
                                $count = 1;
                                foreach($classes_today as $class): 
                                    $is_completed = ($class['teaching_status'] == 'completed');
                                    $status_class = $is_completed ? 'badge-completed' : 'badge-pending';
                                    $status_text = $is_completed ? '✅ Completed' : '⏳ Pending';
                                ?>
                                <tr>
                                    <td><?php echo $count++; ?></td>
                                    <td><strong><?php echo htmlspecialchars($class['class_name']); ?></strong></td>
                                    <td><?php echo htmlspecialchars($class['subject']); ?></td>
                                    <td><?php echo $class['room'] ? htmlspecialchars($class['room']) : '-'; ?></td>
                                    <td>
                                        <?php 
                                        if($class['start_time']) {
                                            echo date('h:i A', strtotime($class['start_time'])) . ' - ' . date('h:i A', strtotime($class['end_time']));
                                        } else {
                                            echo '-';
                                        }
                                        ?>
                                    </td>
                                    <td>
                                        <span class="badge-status <?php echo $status_class; ?>">
                                            <?php echo $status_text; ?>
                                        </span>
                                        <?php if($is_completed && !empty($class['teaching_notes'])): ?>
                                            <br><small class="text-muted">📝 <?php echo htmlspecialchars($class['teaching_notes']); ?></small>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?php if(!$is_completed): ?>
                                            <!-- ប៊ូតុងសម្គាល់ថាបានបង្រៀនហើយ -->
                                            <button type="button" class="btn btn-sm btn-success" 
                                                    data-bs-toggle="modal" 
                                                    data-bs-target="#markTaughtModal"
                                                    data-class-id="<?php echo $class['id']; ?>"
                                                    data-class-name="<?php echo htmlspecialchars($class['class_name']); ?>"
                                                    data-teaching-date="<?php echo $today; ?>">
                                                <i class="fas fa-check"></i> Mark Taught
                                            </button>
                                        <?php else: ?>
                                            <!-- ប៊ូតុងកំណត់ជា Pending វិញ -->
                                            <form method="POST" style="display:inline;">
                                                <input type="hidden" name="class_id" value="<?php echo $class['id']; ?>">
                                                <input type="hidden" name="teaching_date" value="<?php echo $today; ?>">
                                                <button type="submit" name="mark_pending" class="btn btn-sm btn-warning">
                                                    <i class="fas fa-undo"></i> Undo
                                                </button>
                                            </form>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php else: ?>
                    <div class="text-center py-4">
                        <i class="fas fa-calendar-check" style="font-size: 36px; color: #cbd5e1;"></i>
                        <p class="text-muted mt-2">No classes scheduled for today.</p>
                        <p class="text-muted">Enjoy your day off! 🎉</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- KPI CARDS -->
        <div class="kpi-row">
            <div class="kpi-card" style="border-bottom-color: #4f46e5;">
                <div class="kpi-title"><i class="fas fa-users"></i> Students in <?php echo strtoupper(htmlspecialchars($teacher_department)); ?> Dept</div>
                <div class="kpi-number"><?php echo $total_students_by_dept; ?></div>
                <small>Students from <?php echo htmlspecialchars($teacher_department); ?> department</small>
            </div>
            <div class="kpi-card" style="border-bottom-color: #10b981;">
                <div class="kpi-title"><i class="fas fa-book"></i> Total Colleges</div>
                <div class="kpi-number"><?php echo $total_colleges; ?></div>
                <small>All colleges in system</small>
            </div>
            <div class="kpi-card" style="border-bottom-color: #f59e0b;">
                <div class="kpi-title"><i class="fas fa-chalkboard-teacher"></i> Total Teachers</div>
                <div class="kpi-number"><?php echo $total_teachers; ?></div>
                <small>Active teachers in system</small>
            </div>
        </div>
        
        <!-- CLASS SCHEDULE -->
        <div class="kpi-row">
            <div class="kpi-card" style="border-bottom-color: #8b5cf6; width: 100%;">
                <div class="kpi-title">
                    <i class="fas fa-calendar-alt"></i> 
                    Class Schedule for <?php echo htmlspecialchars($teacher_department); ?> Department
                </div>
                <small>
                    <i class="fas fa-user"></i> Teacher: <strong><?php echo htmlspecialchars($logged_in_teacher); ?></strong>
                </small>
                <div class="table-responsive">
                    <table class="table table-bordered">
                        <thead>
                            <tr>
                                <th>Time Start</th>
                                <th>Time End</th>
                                <th>Subject</th>
                                <th>Department</th>
                                <th>Classroom</th>
                                <th>Shift</th>
                                <th>Year</th>
                                <th>Semester</th>
                                <th>Date</th>
                            </tr>
                        </thead>
                        <tbody>
                        <?php
                        if(mysqli_num_rows($schedule_result) > 0){
                            while($row = mysqli_fetch_assoc($schedule_result)){
                        ?>
                        <tr>
                            <td><?= htmlspecialchars($row['time_star'] ?? ''); ?></td>
                            <td><?= htmlspecialchars($row['time_end'] ?? ''); ?></td>
                            <td><?= htmlspecialchars($row['subject'] ?? ''); ?></td>
                            <td><?= htmlspecialchars($row['department'] ?? ''); ?></td>
                            <td><?= htmlspecialchars($row['class'] ?? ''); ?></td>
                            <td><?= htmlspecialchars($row['shift'] ?? ''); ?></td>
                            <td><?= htmlspecialchars($row['year'] ?? ''); ?></td>
                            <td><?= htmlspecialchars($row['semester'] ?? ''); ?></td>
                            <td><?= htmlspecialchars($row['date'] ?? ''); ?></td>
                        </tr>
                        <?php
                            }
                        } else {
                            echo '<tr><td colspan="9" class="text-center">No schedule found for your department</td></tr>';
                        }
                        ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        
        <!-- STUDENT PROGRESS & TOP STUDENTS -->
        <div class="kpi-row">
            <div class="kpi-card" style="border-bottom-color: #eb668c; flex: 1;">
                <div class="kpi-title">
                    <i class="fas fa-user-graduate"></i> 
                    Student Progress (<?php echo htmlspecialchars($teacher_subject); ?>)
                </div>
                <small>
                    <i class="fas fa-chalkboard-teacher"></i> Subject taught by: <strong><?php echo htmlspecialchars($logged_in_teacher); ?></strong>
                </small>
                <div class="table-responsive">
                    <table class="table table-bordered">
                        <thead>
                            <tr>
                                <th>Name</th>
                                <th>Subject</th>
                                <th>Score</th>
                                <th>Grade</th>
                                <th>Department</th>
                            </tr>
                        </thead>
                        <tbody>
                        <?php
                        if(mysqli_num_rows($progress_result) > 0){
                            while($row = mysqli_fetch_assoc($progress_result)){
                        ?>
                        <tr>
                            <td><?= htmlspecialchars($row['name'] ?? ''); ?></td>
                            <td><?= htmlspecialchars($row['subject'] ?? ''); ?></td>
                            <td><?= htmlspecialchars($row['score'] ?? ''); ?></td>
                            <td><?= htmlspecialchars($row['Grade'] ?? ''); ?></td>
                            <td><?= htmlspecialchars($row['college'] ?? ''); ?></td>
                        </tr>
                        <?php
                            }
                        } else {
                            echo '<tr><td colspan="5" class="text-center">No student data found for your subject</td></tr>';
                        }
                        ?>
                        </tbody>
                    </table>
                </div>
            </div>
            
            <div class="kpi-card" style="border-bottom-color: #40189D; flex: 1;">
                <div class="kpi-title">
                    <i class="fas fa-trophy"></i> 
                    Top Students (<?php echo htmlspecialchars($teacher_subject); ?>)
                </div>
                <small>
                    <i class="fas fa-star"></i> Students with A or B grades
                </small>
                <div class="table-responsive">
                    <table class="table table-bordered">
                        <thead>
                            <tr>
                                <th>Name</th>
                                <th>Subject</th>
                                <th>Score</th>
                                <th>Grade</th>
                                <th>Department</th>
                            </tr>
                        </thead>
                        <tbody>
                        <?php
                        if(mysqli_num_rows($top_result) > 0){
                            while($row = mysqli_fetch_assoc($top_result)){
                        ?>
                        <tr>
                            <td><?= htmlspecialchars($row['name'] ?? ''); ?></td>
                            <td><?= htmlspecialchars($row['subject'] ?? ''); ?></td>
                            <td><?= htmlspecialchars($row['score'] ?? ''); ?></td>
                            <td><?= htmlspecialchars($row['Grade'] ?? ''); ?></td>
                            <td><?= htmlspecialchars($row['college'] ?? ''); ?></td>
                        </tr>
                        <?php
                            }
                        } else {
                            echo '<tr><td colspan="5" class="text-center">No top students found for your subject</td></tr>';
                        }
                        ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        
      
        
        
        
    </div>
</div>

<!-- ============================================ -->
<!-- MARK TAUGHT MODAL                            -->
<!-- ============================================ -->
<div class="modal fade" id="markTaughtModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="fas fa-check-circle text-success"></i> Mark Class as Taught</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST">
                <div class="modal-body">
                    <p>Are you sure you have taught this class?</p>
                    <p><strong>Class:</strong> <span id="modalClassName"></span></p>
                    <input type="hidden" name="class_id" id="modalClassId">
                    <input type="hidden" name="teaching_date" id="modalTeachingDate">
                    <div class="mb-3">
                        <label class="form-label">Notes (Optional)</label>
                        <textarea class="form-control" name="notes" rows="3" placeholder="Add any notes about today's class..."></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" name="mark_taught" class="btn btn-success">
                        <i class="fas fa-check"></i> Yes, Mark as Taught
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
function updateDateTime() {
    const now = new Date();
    const formatted = now.toLocaleString('en-US', { 
        weekday: 'long', 
        year: 'numeric', 
        month: 'long', 
        day: 'numeric',
        hour: '2-digit',
        minute: '2-digit'
    });
    const dateTimeEl = document.getElementById('currentDateTime');
    if(dateTimeEl) dateTimeEl.innerHTML = '<i class="fas fa-calendar-alt"></i> ' + formatted;
}
updateDateTime();
setInterval(updateDateTime, 1000);

// ============================================
// Modal Script for Mark Taught
// ============================================
document.addEventListener('DOMContentLoaded', function() {
    const markTaughtModal = document.getElementById('markTaughtModal');
    if(markTaughtModal) {
        markTaughtModal.addEventListener('show.bs.modal', function(event) {
            const button = event.relatedTarget;
            const classId = button.getAttribute('data-class-id');
            const className = button.getAttribute('data-class-name');
            const teachingDate = button.getAttribute('data-teaching-date');
            
            document.getElementById('modalClassId').value = classId;
            document.getElementById('modalClassName').textContent = className;
            document.getElementById('modalTeachingDate').value = teachingDate;
        });
    }
});


</script>
</body>
</html>