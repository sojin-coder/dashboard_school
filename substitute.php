 <?php
        include "check_login.php";
        
        $message = '';
        $error = '';
        
        // ============================================
        // ដំណើរការស្នើសុំបង្រៀនជំនួស
        // ============================================
        if(isset($_POST['submit_request'])) {
            $class_id = mysqli_real_escape_string($conn, $_POST['class_id']);
            $subject = mysqli_real_escape_string($conn, $_POST['subject']);
            $class_name = mysqli_real_escape_string($conn, $_POST['class_name']);
            $teaching_date = mysqli_real_escape_string($conn, $_POST['teaching_date']);
            $start_time = mysqli_real_escape_string($conn, $_POST['start_time']);
            $end_time = mysqli_real_escape_string($conn, $_POST['end_time']);
            $room = mysqli_real_escape_string($conn, $_POST['room']);
            $reason = mysqli_real_escape_string($conn, $_POST['reason']);
            $substitute_teacher_id = mysqli_real_escape_string($conn, $_POST['substitute_teacher_id']);
            
            // ទាញយកឈ្មោះគ្រូដែលត្រូវស្នើ
            $teacher_sql = "SELECT name FROM teachers WHERE id = '$substitute_teacher_id'";
            $teacher_result = mysqli_query($conn, $teacher_sql);
            $teacher_data = mysqli_fetch_assoc($teacher_result);
            $substitute_teacher_name = $teacher_data['name'] ?? '';
            
            if(empty($class_id) || empty($subject) || empty($teaching_date) || empty($substitute_teacher_id)) {
                $error = "❌ Please fill in all required fields!";
            } else {
                $insert_sql = "INSERT INTO substitute_requests (
                    class_id, subject, class_name,
                    original_teacher_id, original_teacher_name,
                    substitute_teacher_id, substitute_teacher_name,
                    teaching_date, start_time, end_time, room,
                    reason, status
                ) VALUES (
                    '$class_id', '$subject', '$class_name',
                    '$logged_in_id', '$logged_in_teacher',
                    '$substitute_teacher_id', '$substitute_teacher_name',
                    '$teaching_date', " . ($start_time ? "'$start_time'" : "NULL") . ",
                    " . ($end_time ? "'$end_time'" : "NULL") . ",
                    '$room', '$reason', 'pending'
                )";
                
                if(mysqli_query($conn, $insert_sql)) {
                    $request_id = mysqli_insert_id($conn);
                    
                    // បន្ថែមការជូនដំណឹងទៅគ្រូដែលត្រូវស្នើ
                    $notif_message = "📚 {$logged_in_teacher} is requesting you to teach {$subject} ({$class_name}) on " . date('d M Y', strtotime($teaching_date)) . " at " . date('h:i A', strtotime($start_time)) . ". Reason: {$reason}";
                    $notif_sql = "INSERT INTO substitute_notifications (request_id, teacher_id, message, notification_type) 
                                  VALUES ('$request_id', '$substitute_teacher_id', '$notif_message', 'request')";
                    mysqli_query($conn, $notif_sql);
                    
                    $message = "✅ Substitute request sent successfully!";
                } else {
                    $error = "❌ Error: " . mysqli_error($conn);
                }
            }
        }
        
        // ============================================
        // ដំណើរការ Accept/Decline
        // ============================================
        if(isset($_GET['action']) && isset($_GET['id']) && is_numeric($_GET['id'])) {
            $request_id = mysqli_real_escape_string($conn, $_GET['id']);
            $action = mysqli_real_escape_string($conn, $_GET['action']);
            
            $check_sql = "SELECT * FROM substitute_requests WHERE id = '$request_id' AND status = 'pending'";
            $check_result = mysqli_query($conn, $check_sql);
            $request_data = mysqli_fetch_assoc($check_result);
            
            if($request_data) {
                if($action == 'accept') {
                    $update_sql = "UPDATE substitute_requests SET 
                                   substitute_teacher_id = '$logged_in_id',
                                   substitute_teacher_name = '$logged_in_teacher',
                                   status = 'accepted',
                                   responded_at = NOW()
                                   WHERE id = '$request_id'";
                    
                    if(mysqli_query($conn, $update_sql)) {
                        $notif_message = "✅ {$logged_in_teacher} has accepted to teach {$request_data['subject']} on " . date('d M Y', strtotime($request_data['teaching_date']));
                        $notif_sql = "INSERT INTO substitute_notifications (request_id, teacher_id, message, notification_type) 
                                      VALUES ('$request_id', '{$request_data['original_teacher_id']}', '$notif_message', 'accepted')";
                        mysqli_query($conn, $notif_sql);
                        
                        $message = "✅ You have accepted to teach this class!";
                    } else {
                        $error = "❌ Error: " . mysqli_error($conn);
                    }
                } elseif($action == 'decline') {
                    $update_sql = "UPDATE substitute_requests SET 
                                   status = 'declined',
                                   responded_at = NOW()
                                   WHERE id = '$request_id'";
                    
                    if(mysqli_query($conn, $update_sql)) {
                        $notif_message = "❌ {$logged_in_teacher} has declined to teach {$request_data['subject']} on " . date('d M Y', strtotime($request_data['teaching_date']));
                        $notif_sql = "INSERT INTO substitute_notifications (request_id, teacher_id, message, notification_type) 
                                      VALUES ('$request_id', '{$request_data['original_teacher_id']}', '$notif_message', 'declined')";
                        mysqli_query($conn, $notif_sql);
                        
                        $message = "✅ You have declined this request.";
                    } else {
                        $error = "❌ Error: " . mysqli_error($conn);
                    }
                }
            } else {
                $error = "❌ This request is no longer available or has been processed.";
            }
        }
        
        // ============================================
        // ដំណើរការ Mark as Completed
        // ============================================
        if(isset($_POST['mark_completed']) && isset($_POST['request_id'])) {
            $request_id = mysqli_real_escape_string($conn, $_POST['request_id']);
            $notes = mysqli_real_escape_string($conn, $_POST['notes'] ?? '');
            
            $update_sql = "UPDATE substitute_requests SET 
                           status = 'completed',
                           notes = '$notes',
                           completed_at = NOW()
                           WHERE id = '$request_id' 
                           AND substitute_teacher_id = '$logged_in_id'";
            
            if(mysqli_query($conn, $update_sql)) {
                $message = "✅ Class marked as completed!";
            } else {
                $error = "❌ Error: " . mysqli_error($conn);
            }
        }
        
        // ============================================
        // ទាញយកបញ្ជីគ្រូទាំងអស់សម្រាប់ជ្រើសរើស
        // ============================================
        $teachers_list_query = "SELECT id, name, department, subject FROM teachers WHERE id != '$logged_in_id' ORDER BY name ASC";
        $teachers_list_result = mysqli_query($conn, $teachers_list_query);
        
        // ============================================
        // ទាញយកបញ្ជីថ្នាក់របស់គ្រូ
        // ============================================
        $my_classes_query = "SELECT id, class_name, subject, room FROM teacher_classes WHERE teacher_id = '$logged_in_id' AND status = 'active' ORDER BY class_name ASC";
        $my_classes_result = mysqli_query($conn, $my_classes_query);
        
        // ============================================
        // ទាញយកការស្នើសុំដែលកំពុងរង់ចាំ
        // ============================================
        $pending_requests_query = "SELECT sr.*, tc.room as class_room, tc.schedule_day 
                                   FROM substitute_requests sr
                                   LEFT JOIN teacher_classes tc ON sr.class_id = tc.id
                                   WHERE sr.status = 'pending' 
                                   AND sr.substitute_teacher_id = '$logged_in_id'
                                   ORDER BY sr.teaching_date ASC, sr.start_time ASC";
        $pending_requests_result = mysqli_query($conn, $pending_requests_query);
        
        // ============================================
        // ទាញយកការស្នើសុំដែលបានទទួលយក
        // ============================================
        $my_accepted_query = "SELECT sr.*, tc.room as class_room, tc.schedule_day 
                              FROM substitute_requests sr
                              LEFT JOIN teacher_classes tc ON sr.class_id = tc.id
                              WHERE sr.substitute_teacher_id = '$logged_in_id'
                              AND sr.status IN ('accepted', 'completed')
                              ORDER BY sr.teaching_date DESC, sr.start_time DESC";
        $my_accepted_result = mysqli_query($conn, $my_accepted_query);
        
        // ============================================
        // ទាញយកការស្នើសុំដែលខ្ញុំបានស្នើ
        // ============================================
        $my_requests_query = "SELECT sr.*, tc.room as class_room, tc.schedule_day 
                              FROM substitute_requests sr
                              LEFT JOIN teacher_classes tc ON sr.class_id = tc.id
                              WHERE sr.original_teacher_id = '$logged_in_id'
                              ORDER BY sr.created_at DESC
                              LIMIT 10";
        $my_requests_result = mysqli_query($conn, $my_requests_query);
        
        // ============================================
        // ទាញយកការជូនដំណឹង
        // ============================================
        $notifications_query = "SELECT * FROM substitute_notifications 
                                WHERE teacher_id = '$logged_in_id' 
                                ORDER BY created_at DESC 
                                LIMIT 10";
        $notifications_result = mysqli_query($conn, $notifications_query);
        
        $unread_query = "SELECT COUNT(*) as total FROM substitute_notifications 
                         WHERE teacher_id = '$logged_in_id' AND is_read = 0";
        $unread_result = mysqli_query($conn, $unread_query);
        $unread_count = mysqli_fetch_assoc($unread_result)['total'] ?? 0;
        
        // ============================================
        // សម្គាល់ការជូនដំណឹងថាបានអាន
        // ============================================
        if(isset($_POST['mark_notifications_read'])) {
            $update_sql = "UPDATE substitute_notifications SET 
                           is_read = 1, 
                           read_at = NOW() 
                           WHERE teacher_id = '$logged_in_id'";
            mysqli_query($conn, $update_sql);
            header("Location: substitute.php");
            exit();
        }
        
        // ============================================
        // ស្ថិតិ
        // ============================================
        $stats_query = "SELECT 
                            COUNT(*) as total_requests,
                            SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) as pending,
                            SUM(CASE WHEN status = 'accepted' THEN 1 ELSE 0 END) as accepted,
                            SUM(CASE WHEN status = 'completed' THEN 1 ELSE 0 END) as completed,
                            SUM(CASE WHEN status = 'declined' THEN 1 ELSE 0 END) as declined
                        FROM substitute_requests 
                        WHERE original_teacher_id = '$logged_in_id' OR substitute_teacher_id = '$logged_in_id'";
        $stats_result = mysqli_query($conn, $stats_query);
        $stats = mysqli_fetch_assoc($stats_result);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Substitute Teaching - Teacher</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        a { text-decoration: none; }
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
        
        .content-box {
            background: white;
            border-radius: 20px;
            padding: 25px 30px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.08);
            margin-bottom: 25px;
        }
        
        .btn-save {
            background: #4f46e5;
            color: white;
            border: none;
            padding: 12px 30px;
            border-radius: 10px;
            font-weight: 600;
            transition: 0.3s;
        }
        .btn-save:hover { background: #4338ca; color: white; transform: translateY(-2px); }
        
        .btn-accept {
            background: #22c55e;
            color: white;
            border: none;
            padding: 6px 16px;
            border-radius: 8px;
            font-weight: 600;
            transition: 0.3s;
        }
        .btn-accept:hover { background: #16a34a; color: white; transform: translateY(-2px); }
        
        .btn-decline {
            background: #ef4444;
            color: white;
            border: none;
            padding: 6px 16px;
            border-radius: 8px;
            font-weight: 600;
            transition: 0.3s;
        }
        .btn-decline:hover { background: #dc2626; color: white; transform: translateY(-2px); }
        
        .badge-status {
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 0.75rem;
            font-weight: 600;
        }
        .badge-pending { background: #fef3c7; color: #92400e; }
        .badge-accepted { background: #dbeafe; color: #1e40af; }
        .badge-completed { background: #dcfce7; color: #166534; }
        .badge-declined { background: #fee2e2; color: #991b1b; }
        .badge-cancelled { background: #f1f5f9; color: #475569; }
        
        .notification-item {
            padding: 12px 16px;
            border-radius: 10px;
            border-left: 4px solid #4f46e5;
            transition: 0.3s;
            margin-bottom: 10px;
            background: #f8fafc;
        }
        .notification-item.unread {
            background: #eff6ff;
            border-left-color: #2563eb;
        }
        .notification-item:hover {
            background: #f1f5f9;
        }
        
        .stats-row {
            display: flex;
            gap: 15px;
            flex-wrap: wrap;
        }
        .stat-box {
            flex: 1;
            min-width: 100px;
            padding: 15px;
            border-radius: 12px;
            text-align: center;
            background: #f8fafc;
            border: 1px solid #e2e8f0;
        }
        .stat-box .number {
            font-size: 28px;
            font-weight: 700;
        }
        .stat-box .label {
            font-size: 0.8rem;
            color: #64748b;
        }
        
        .form-control, .form-select {
            border-radius: 10px;
            padding: 10px 15px;
            border: 1px solid #cbd5e1;
        }
        .form-control:focus, .form-select:focus {
            border-color: #4f46e5;
            box-shadow: 0 0 0 3px rgba(79, 70, 229, 0.1);
        }
        
        .alert-custom { border-radius: 10px; padding: 15px 20px; }
        
        @media (max-width: 768px) {
            .sidebar { width: 80px; }
            .sidebar-header h1, .sidebar-header p, .nav-item span { display: none; }
            .nav-item { justify-content: center; }
            .main-content { padding: 15px; }
            .sidebar-header .profile-img { width: 50px; height: 50px; }
            .sidebar-header .teacher-name { display: none; }
            .sidebar-header .teacher-dept { display: none; }
            .sidebar-header .teacher-subject { display: none; }
        }
    </style>
</head>
<body>

<div class="container">
    <div class="sidebar">
        <div class="sidebar-header">
            <img src="<?php echo !empty($teacher_image) ? $teacher_image : 'https://ui-avatars.com/api/?name=' . urlencode($logged_in_teacher) . '&size=120&background=4f46e5&color=fff'; ?>" 
                 alt="Profile" 
                 class="profile-img" />
            <h1 class="teacher-name"><?php echo htmlspecialchars($logged_in_teacher); ?></h1>
            <p class="teacher-dept">
                <i class="fas fa-building"></i> <?php echo htmlspecialchars($teacher_department); ?>
            </p>
            <p class="teacher-subject">
                <i class="fas fa-book"></i> <?php echo htmlspecialchars($teacher_subject); ?>
            </p>
        </div>
        <div class="nav-menu">
            <a href="forteacher.php" class="nav-item"><i class="fas fa-tachometer-alt"></i> <span>Main Dashboard</span></a>
            <a href="class.php" class="nav-item"><i class="fas fa-chalkboard-teacher"></i> <span>Class</span></a>
            <a href="Request.php" class="nav-item"><i class="fas fa-file-signature"></i> <span>Request</span></a>
            <a href="substitute.php" class="nav-item active">
                <i class="fas fa-people-arrows"></i>
                <span>Substitute Class</span>
            </a>
            <a href="settings_teacher.php" class="nav-item"><i class="fas fa-cog"></i> <span>Setting</span></a>
            <div class="nav-bottom">
                <a href="logout.php" class="nav-item" style="padding-left:8px;">
                    <i class="fas fa-sign-out-alt"></i> <span>Logout</span>
                </a>
            </div>
        </div>
    </div>

    <div class="main-content">
        <div class="top-bar">
            <div class="page-title">
                <h2>
                    <i class="fas fa-user-friends"></i> Substitute Teaching
                    <?php if($unread_count > 0): ?>
                        <span class="badge bg-danger ms-2"><?php echo $unread_count; ?> New</span>
                    <?php endif; ?>
                </h2>
            </div>
            <div class="date-time" id="currentDateTime"></div>
        </div>

        <?php if(!empty($message)): ?>
            <div class="alert alert-success alert-custom">
                <i class="fas fa-check-circle"></i> <?php echo $message; ?>
            </div>
        <?php endif; ?>
        <?php if(!empty($error)): ?>
            <div class="alert alert-danger alert-custom">
                <i class="fas fa-exclamation-circle"></i> <?php echo $error; ?>
            </div>
        <?php endif; ?>

        <!-- ============================================ -->
        <!-- STATISTICS                                   -->
        <!-- ============================================ -->
        <div class="content-box">
            <h5><i class="fas fa-chart-bar"></i> Your Substitute Statistics</h5>
            <hr>
            <div class="stats-row">
                <div class="stat-box">
                    <div class="number text-warning"><?php echo $stats['pending'] ?? 0; ?></div>
                    <div class="label">Pending Requests</div>
                </div>
                <div class="stat-box">
                    <div class="number text-primary"><?php echo $stats['accepted'] ?? 0; ?></div>
                    <div class="label">Accepted</div>
                </div>
                <div class="stat-box">
                    <div class="number text-success"><?php echo $stats['completed'] ?? 0; ?></div>
                    <div class="label">Completed</div>
                </div>
                <div class="stat-box">
                    <div class="number text-danger"><?php echo $stats['declined'] ?? 0; ?></div>
                    <div class="label">Declined</div>
                </div>
                <div class="stat-box">
                    <div class="number text-secondary"><?php echo ($stats['total_requests'] ?? 0); ?></div>
                    <div class="label">Total Requests</div>
                </div>
            </div>
        </div>

        <!-- ============================================ -->
        <!-- REQUEST SUBSTITUTE FORM                      -->
        <!-- ============================================ -->
        <div class="content-box">
            <h5><i class="fas fa-paper-plane text-primary"></i> Request Substitute Teacher</h5>
            <hr>
            
            <div class="alert alert-info">
                <i class="fas fa-info-circle"></i> 
                Fill in the details below to request a substitute teacher for your class.
                The selected teacher will receive a notification and can accept or decline.
            </div>
            
            <form method="POST">
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label class="form-label"><i class="fas fa-chalkboard-teacher"></i> Select Class <span class="text-danger">*</span></label>
                        <select class="form-select" name="class_id" required onchange="updateClassInfo(this)">
                            <option value="">-- Select Class --</option>
                            <?php 
                            mysqli_data_seek($my_classes_result, 0);
                            while($class = mysqli_fetch_assoc($my_classes_result)):
                                $selected = (isset($_POST['class_id']) && $_POST['class_id'] == $class['id']) ? 'selected' : '';
                            ?>
                                <option value="<?php echo $class['id']; ?>" 
                                        data-subject="<?php echo htmlspecialchars($class['subject']); ?>"
                                        data-classname="<?php echo htmlspecialchars($class['class_name']); ?>"
                                        data-room="<?php echo htmlspecialchars($class['room'] ?? ''); ?>"
                                        <?php echo $selected; ?>>
                                    <?php echo htmlspecialchars($class['class_name']); ?> - <?php echo htmlspecialchars($class['subject']); ?>
                                </option>
                            <?php endwhile; ?>
                        </select>
                    </div>
                    <div class="col-md-6 mb-3">
                        <label class="form-label"><i class="fas fa-user-tie"></i> Substitute Teacher <span class="text-danger">*</span></label>
                        <select class="form-select" name="substitute_teacher_id" required>
                            <option value="">-- Select Teacher --</option>
                            <?php 
                            mysqli_data_seek($teachers_list_result, 0);
                            while($teacher = mysqli_fetch_assoc($teachers_list_result)):
                                $selected = (isset($_POST['substitute_teacher_id']) && $_POST['substitute_teacher_id'] == $teacher['id']) ? 'selected' : '';
                            ?>
                                <option value="<?php echo $teacher['id']; ?>" <?php echo $selected; ?>>
                                    <?php echo htmlspecialchars($teacher['name']); ?> 
                                    (<?php echo htmlspecialchars($teacher['department'] ?? 'N/A'); ?>)
                                </option>
                            <?php endwhile; ?>
                        </select>
                    </div>
                    <div class="col-md-6 mb-3">
                        <label class="form-label"><i class="fas fa-calendar-alt"></i> Teaching Date <span class="text-danger">*</span></label>
                        <input type="date" class="form-control" name="teaching_date" 
                               value="<?php echo isset($_POST['teaching_date']) ? $_POST['teaching_date'] : date('Y-m-d', strtotime('+1 day')); ?>" required>
                    </div>
                    <div class="col-md-3 mb-3">
                        <label class="form-label"><i class="fas fa-clock"></i> Start Time</label>
                        <input type="time" class="form-control" name="start_time" 
                               value="<?php echo isset($_POST['start_time']) ? $_POST['start_time'] : '08:00'; ?>">
                    </div>
                    <div class="col-md-3 mb-3">
                        <label class="form-label"><i class="fas fa-clock"></i> End Time</label>
                        <input type="time" class="form-control" name="end_time" 
                               value="<?php echo isset($_POST['end_time']) ? $_POST['end_time'] : '10:00'; ?>">
                    </div>
                    <div class="col-md-6 mb-3">
                        <label class="form-label"><i class="fas fa-door-open"></i> Room</label>
                        <input type="text" class="form-control" name="room" id="requestRoom" 
                               placeholder="e.g. Room 201" 
                               value="<?php echo isset($_POST['room']) ? $_POST['room'] : ''; ?>">
                    </div>
                    <div class="col-md-12 mb-3">
                        <label class="form-label"><i class="fas fa-comment"></i> Reason for Substitute <span class="text-danger">*</span></label>
                        <textarea class="form-control" name="reason" rows="3" required placeholder="e.g. I am on leave, training workshop, sick leave, etc."><?php echo isset($_POST['reason']) ? htmlspecialchars($_POST['reason']) : ''; ?></textarea>
                    </div>
                    <div class="col-md-12">
                        <input type="hidden" name="subject" id="requestSubject" value="">
                        <input type="hidden" name="class_name" id="requestClassName" value="">
                        <button type="submit" name="submit_request" class="btn-save">
                            <i class="fas fa-paper-plane"></i> Send Request
                        </button>
                        <button type="reset" class="btn btn-secondary ms-2" style="border-radius:10px; padding:12px 30px;">
                            <i class="fas fa-undo"></i> Reset
                        </button>
                    </div>
                </div>
            </form>
        </div>

        <!-- ============================================ -->
        <!-- PENDING REQUESTS (For Me)                    -->
        <!-- ============================================ -->
        <div class="content-box">
            <h5><i class="fas fa-clock text-warning"></i> Pending Substitute Requests (For You)</h5>
            <hr>
            
            <?php if(mysqli_num_rows($pending_requests_result) > 0): ?>
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>#</th>
                                <th>Class</th>
                                <th>Subject</th>
                                <th>From Teacher</th>
                                <th>Date</th>
                                <th>Time</th>
                                <th>Room</th>
                                <th>Reason</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php 
                            $count = 1;
                            while($request = mysqli_fetch_assoc($pending_requests_result)): 
                            ?>
                            <tr>
                                <td><?php echo $count++; ?></td>
                                <td><strong><?php echo htmlspecialchars($request['class_name']); ?></strong></td>
                                <td><?php echo htmlspecialchars($request['subject']); ?></td>
                                <td>
                                    <i class="fas fa-user text-primary"></i> 
                                    <?php echo htmlspecialchars($request['original_teacher_name']); ?>
                                </td>
                                <td><?php echo date('d M Y', strtotime($request['teaching_date'])); ?></td>
                                <td>
                                    <?php 
                                    if($request['start_time']) {
                                        echo date('h:i A', strtotime($request['start_time'])) . ' - ' . date('h:i A', strtotime($request['end_time']));
                                    } else {
                                        echo '-';
                                    }
                                    ?>
                                </td>
                                <td><?php echo htmlspecialchars($request['room'] ?? '-'); ?></td>
                                <td>
                                    <span class="badge bg-info text-dark">
                                        <i class="fas fa-info-circle"></i> 
                                        <?php echo htmlspecialchars($request['reason'] ?? 'No reason provided'); ?>
                                    </span>
                                </td>
                                <td>
                                    <div class="d-flex gap-2">
                                        <a href="?action=accept&id=<?php echo $request['id']; ?>" 
                                           class="btn-accept btn-sm"
                                           onclick="return confirm('Are you sure you want to accept this substitute teaching request?')">
                                            <i class="fas fa-check"></i> Accept
                                        </a>
                                        <a href="?action=decline&id=<?php echo $request['id']; ?>" 
                                           class="btn-decline btn-sm"
                                           onclick="return confirm('Are you sure you want to decline this substitute teaching request?')">
                                            <i class="fas fa-times"></i> Decline
                                        </a>
                                    </div>
                                </td>
                            </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
            <?php else: ?>
                <div class="text-center py-4">
                    <i class="fas fa-check-circle" style="font-size: 48px; color: #22c55e;"></i>
                    <p class="text-muted mt-3">No pending substitute requests for you at the moment.</p>
                    <p class="text-muted">You'll be notified when a teacher needs a substitute.</p>
                </div>
            <?php endif; ?>
        </div>

        <!-- ============================================ -->
        <!-- MY SUBSTITUTE CLASSES (Accepted/Completed)   -->
        <!-- ============================================ -->
        <div class="content-box">
            <h5><i class="fas fa-chalkboard-teacher text-success"></i> My Substitute Classes</h5>
            <hr>
            
            <?php if(mysqli_num_rows($my_accepted_result) > 0): ?>
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>#</th>
                                <th>Class</th>
                                <th>Subject</th>
                                <th>Original Teacher</th>
                                <th>Date</th>
                                <th>Time</th>
                                <th>Room</th>
                                <th>Status</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php 
                            $count = 1;
                            while($class = mysqli_fetch_assoc($my_accepted_result)): 
                                $status_class = 'badge-' . $class['status'];
                                $status_text = ucfirst($class['status']);
                            ?>
                            <tr>
                                <td><?php echo $count++; ?></td>
                                <td><strong><?php echo htmlspecialchars($class['class_name']); ?></strong></td>
                                <td><?php echo htmlspecialchars($class['subject']); ?></td>
                                <td>
                                    <i class="fas fa-user text-primary"></i> 
                                    <?php echo htmlspecialchars($class['original_teacher_name']); ?>
                                </td>
                                <td><?php echo date('d M Y', strtotime($class['teaching_date'])); ?></td>
                                <td>
                                    <?php 
                                    if($class['start_time']) {
                                        echo date('h:i A', strtotime($class['start_time'])) . ' - ' . date('h:i A', strtotime($class['end_time']));
                                    } else {
                                        echo '-';
                                    }
                                    ?>
                                </td>
                                <td><?php echo htmlspecialchars($class['room'] ?? '-'); ?></td>
                                <td>
                                    <span class="badge-status <?php echo $status_class; ?>">
                                        <?php echo $status_text; ?>
                                    </span>
                                    <?php if(!empty($class['notes'])): ?>
                                        <br><small class="text-muted">📝 <?php echo htmlspecialchars($class['notes']); ?></small>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php if($class['status'] == 'accepted'): ?>
                                        <button type="button" class="btn btn-sm btn-success" 
                                                data-bs-toggle="modal" 
                                                data-bs-target="#markCompletedModal"
                                                data-request-id="<?php echo $class['id']; ?>"
                                                data-class-name="<?php echo htmlspecialchars($class['class_name']); ?>">
                                            <i class="fas fa-check"></i> Complete
                                        </button>
                                    <?php else: ?>
                                        <span class="text-muted">-</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
            <?php else: ?>
                <div class="text-center py-4">
                    <i class="fas fa-users" style="font-size: 48px; color: #cbd5e1;"></i>
                    <p class="text-muted mt-3">You haven't accepted any substitute teaching requests yet.</p>
                </div>
            <?php endif; ?>
        </div>

        <!-- ============================================ -->
        <!-- MY REQUESTS (ដែលខ្ញុំបានស្នើ)               -->
        <!-- ============================================ -->
        <div class="content-box">
            <h5><i class="fas fa-paper-plane text-primary"></i> My Substitute Requests (Sent)</h5>
            <hr>
            
            <?php if(mysqli_num_rows($my_requests_result) > 0): ?>
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>#</th>
                                <th>Class</th>
                                <th>Subject</th>
                                <th>Requested Teacher</th>
                                <th>Date</th>
                                <th>Time</th>
                                <th>Status</th>
                                <th>Response</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php 
                            $count = 1;
                            while($request = mysqli_fetch_assoc($my_requests_result)): 
                                $status_class = 'badge-' . $request['status'];
                                $status_text = ucfirst($request['status']);
                            ?>
                            <tr>
                                <td><?php echo $count++; ?></td>
                                <td><strong><?php echo htmlspecialchars($request['class_name']); ?></strong></td>
                                <td><?php echo htmlspecialchars($request['subject']); ?></td>
                                <td>
                                    <i class="fas fa-user text-success"></i> 
                                    <?php echo htmlspecialchars($request['substitute_teacher_name'] ?? 'Not assigned'); ?>
                                </td>
                                <td><?php echo date('d M Y', strtotime($request['teaching_date'])); ?></td>
                                <td>
                                    <?php 
                                    if($request['start_time']) {
                                        echo date('h:i A', strtotime($request['start_time'])) . ' - ' . date('h:i A', strtotime($request['end_time']));
                                    } else {
                                        echo '-';
                                    }
                                    ?>
                                </td>
                                <td>
                                    <span class="badge-status <?php echo $status_class; ?>">
                                        <?php echo $status_text; ?>
                                    </span>
                                </td>
                                <td>
                                    <?php if($request['status'] == 'pending'): ?>
                                        <span class="text-warning"><i class="fas fa-clock"></i> Waiting...</span>
                                    <?php elseif($request['status'] == 'accepted'): ?>
                                        <span class="text-success"><i class="fas fa-check-circle"></i> Accepted</span>
                                    <?php elseif($request['status'] == 'declined'): ?>
                                        <span class="text-danger"><i class="fas fa-times-circle"></i> Declined</span>
                                    <?php elseif($request['status'] == 'completed'): ?>
                                        <span class="text-success"><i class="fas fa-check-double"></i> Completed</span>
                                    <?php else: ?>
                                        <span class="text-secondary">-</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
            <?php else: ?>
                <div class="text-center py-4">
                    <i class="fas fa-paper-plane" style="font-size: 48px; color: #cbd5e1;"></i>
                    <p class="text-muted mt-3">You haven't sent any substitute requests yet.</p>
                </div>
            <?php endif; ?>
        </div>

        <!-- ============================================ -->
        <!-- NOTIFICATIONS                                -->
        <!-- ============================================ -->
        <div class="content-box">
            <div class="d-flex justify-content-between align-items-center">
                <h5>
                    <i class="fas fa-bell text-warning"></i> Notifications
                    <?php if($unread_count > 0): ?>
                        <span class="badge bg-danger ms-2"><?php echo $unread_count; ?> unread</span>
                    <?php endif; ?>
                </h5>
                <?php if($unread_count > 0): ?>
                    <form method="POST">
                        <button type="submit" name="mark_notifications_read" class="btn btn-sm btn-outline-primary">
                            <i class="fas fa-check-double"></i> Mark all as read
                        </button>
                    </form>
                <?php endif; ?>
            </div>
            <hr>
            
            <?php if(mysqli_num_rows($notifications_result) > 0): ?>
                <?php while($notif = mysqli_fetch_assoc($notifications_result)): 
                    $is_unread = $notif['is_read'] == 0;
                    $notif_class = $is_unread ? 'unread' : '';
                    
                    $icon_type = '';
                    if($notif['notification_type'] == 'accepted') {
                        $icon_type = 'check-circle text-success';
                    } elseif($notif['notification_type'] == 'declined') {
                        $icon_type = 'times-circle text-danger';
                    } elseif($notif['notification_type'] == 'request') {
                        $icon_type = 'info-circle text-primary';
                    } else {
                        $icon_type = 'bell text-warning';
                    }
                ?>
                <div class="notification-item <?php echo $notif_class; ?>">
                    <div class="d-flex justify-content-between align-items-start">
                        <div>
                            <i class="fas fa-<?php echo $icon_type; ?>"></i>
                            <?php echo htmlspecialchars($notif['message']); ?>
                            <br>
                            <small class="text-muted">
                                <i class="fas fa-clock"></i> <?php echo date('d M Y, h:i A', strtotime($notif['created_at'])); ?>
                            </small>
                        </div>
                        <?php if($is_unread): ?>
                            <span class="badge bg-primary">New</span>
                        <?php endif; ?>
                    </div>
                </div>
                <?php endwhile; ?>
            <?php else: ?>
                <div class="text-center py-3">
                    <p class="text-muted">No notifications yet.</p>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- ============================================ -->
<!-- MARK COMPLETED MODAL                         -->
<!-- ============================================ -->
<div class="modal fade" id="markCompletedModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="fas fa-check-circle text-success"></i> Mark Class as Completed</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST">
                <div class="modal-body">
                    <p>Have you completed teaching this substitute class?</p>
                    <p><strong>Class:</strong> <span id="modalClassName"></span></p>
                    <input type="hidden" name="request_id" id="modalRequestId">
                    <div class="mb-3">
                        <label class="form-label">Notes (Optional)</label>
                        <textarea class="form-control" name="notes" rows="3" placeholder="Add any notes about this class..."></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" name="mark_completed" class="btn btn-success">
                        <i class="fas fa-check"></i> Yes, Complete
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
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
// Update Class Info when selecting class
// ============================================
function updateClassInfo(select) {
    const selectedOption = select.options[select.selectedIndex];
    if(selectedOption.value) {
        document.getElementById('requestSubject').value = selectedOption.getAttribute('data-subject') || '';
        document.getElementById('requestClassName').value = selectedOption.getAttribute('data-classname') || '';
        document.getElementById('requestRoom').value = selectedOption.getAttribute('data-room') || '';
    }
}

// ============================================
// Modal Script for Mark Completed
// ============================================
document.addEventListener('DOMContentLoaded', function() {
    const modal = document.getElementById('markCompletedModal');
    if(modal) {
        modal.addEventListener('show.bs.modal', function(event) {
            const button = event.relatedTarget;
            const requestId = button.getAttribute('data-request-id');
            const className = button.getAttribute('data-class-name');
            
            document.getElementById('modalRequestId').value = requestId;
            document.getElementById('modalClassName').textContent = className;
        });
    }
});
</script>
</body>
</html>