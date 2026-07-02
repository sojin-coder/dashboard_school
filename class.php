<?php

include "check_login.php";


$message = '';
$error = '';

if(isset($_POST['add_class'])) {
    $class_name = mysqli_real_escape_string($conn, $_POST['class_name']);
    $subject = mysqli_real_escape_string($conn, $_POST['subject']);
    $room = mysqli_real_escape_string($conn, $_POST['room']);
    $schedule_day = mysqli_real_escape_string($conn, $_POST['schedule_day']);
    $start_time = mysqli_real_escape_string($conn, $_POST['start_time']);
    $end_time = mysqli_real_escape_string($conn, $_POST['end_time']);
    $semester = mysqli_real_escape_string($conn, $_POST['semester']);
    $academic_year = mysqli_real_escape_string($conn, $_POST['academic_year']);
    $status = mysqli_real_escape_string($conn, $_POST['status']);
    
    if(empty($class_name) || empty($subject)) {
        $error = "Please fill in Class Name and Subject!";
    } else {
        $insert_sql = "INSERT INTO teacher_classes (
            teacher_id, teacher_name, class_name, subject, room,
            schedule_day, start_time, end_time, semester, academic_year, status
        ) VALUES (
            '$logged_in_id', '$logged_in_teacher', '$class_name', '$subject', '$room',
            " . ($schedule_day ? "'$schedule_day'" : "NULL") . ",
            " . ($start_time ? "'$start_time'" : "NULL") . ",
            " . ($end_time ? "'$end_time'" : "NULL") . ",
            '$semester', '$academic_year', '$status'
        )";
        
        if(mysqli_query($conn, $insert_sql)) {
         
            header("Location: class.php?success=added");
            exit();
        } else {
            $error = "❌ Error adding class: " . mysqli_error($conn);
        }
    }
}


if(isset($_POST['edit_class'])) {
    $class_id = mysqli_real_escape_string($conn, $_POST['class_id']);
    $class_name = mysqli_real_escape_string($conn, $_POST['class_name']);
    $subject = mysqli_real_escape_string($conn, $_POST['subject']);
    $room = mysqli_real_escape_string($conn, $_POST['room']);
    $schedule_day = mysqli_real_escape_string($conn, $_POST['schedule_day']);
    $start_time = mysqli_real_escape_string($conn, $_POST['start_time']);
    $end_time = mysqli_real_escape_string($conn, $_POST['end_time']);
    $semester = mysqli_real_escape_string($conn, $_POST['semester']);
    $academic_year = mysqli_real_escape_string($conn, $_POST['academic_year']);
    $status = mysqli_real_escape_string($conn, $_POST['status']);
    
    if(empty($class_name) || empty($subject)) {
        $error = "Please fill in Class Name and Subject!";
    } else {
        $update_sql = "UPDATE teacher_classes SET 
            class_name = '$class_name',
            subject = '$subject',
            room = '$room',
            schedule_day = " . ($schedule_day ? "'$schedule_day'" : "NULL") . ",
            start_time = " . ($start_time ? "'$start_time'" : "NULL") . ",
            end_time = " . ($end_time ? "'$end_time'" : "NULL") . ",
            semester = '$semester',
            academic_year = '$academic_year',
            status = '$status'
            WHERE id = '$class_id' AND teacher_id = '$logged_in_id'";
        
        if(mysqli_query($conn, $update_sql)) {
           
            header("Location: class.php?success=updated");
            exit();
        } else {
            $error = "❌ Error updating class: " . mysqli_error($conn);
        }
    }
}


if(isset($_GET['delete']) && is_numeric($_GET['delete'])) {
    $class_id = $_GET['delete'];
    $delete_sql = "DELETE FROM teacher_classes WHERE id = '$class_id' AND teacher_id = '$logged_in_id'";
    
    if(mysqli_query($conn, $delete_sql)) {
        header("Location: class.php?success=deleted");
        exit();
    } else {
        $error = "❌ Error deleting class: " . mysqli_error($conn);
    }
}


if(isset($_GET['toggle']) && is_numeric($_GET['toggle'])) {
    $class_id = $_GET['toggle'];
    $status_sql = "SELECT status FROM teacher_classes WHERE id = '$class_id' AND teacher_id = '$logged_in_id'";
    $status_result = mysqli_query($conn, $status_sql);
    $status_row = mysqli_fetch_assoc($status_result);
    
    if($status_row) {
        $new_status = ($status_row['status'] == 'active') ? 'inactive' : 'active';
        $update_sql = "UPDATE teacher_classes SET status = '$new_status' WHERE id = '$class_id' AND teacher_id = '$logged_in_id'";
        mysqli_query($conn, $update_sql);
        header("Location: class.php?success=toggled");
        exit();
    }
}


// if(isset($_GET['success'])) {
//     switch($_GET['success']) {
//         case 'added':
//             $message = "✅ Class added successfully!";
//             break;
//         case 'updated':
//             $message = "✅ Class updated successfully!";
//             break;
//         case 'deleted':
//             $message = "✅ Class deleted successfully!";
//             break;
//         case 'toggled':
//             $message = "✅ Class status updated!";
//             break;
//     }
// }


$classes_query = "SELECT * FROM teacher_classes 
                  WHERE teacher_id = '$logged_in_id' 
                  ORDER BY created_at DESC";
$classes_result = mysqli_query($conn, $classes_query);


$edit_class_data = null;
if(isset($_GET['edit']) && is_numeric($_GET['edit'])) {
    $edit_id = $_GET['edit'];
    $edit_query = "SELECT * FROM teacher_classes WHERE id = '$edit_id' AND teacher_id = '$logged_in_id'";
    $edit_result = mysqli_query($conn, $edit_query);
    $edit_class_data = mysqli_fetch_assoc($edit_result);
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>My Classes - Teacher</title>
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
            padding: 30px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.08);
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
        
        .form-control, .form-select {
            border-radius: 10px;
            padding: 10px 15px;
            border: 1px solid #cbd5e1;
        }
        .form-control:focus, .form-select:focus {
            border-color: #4f46e5;
            box-shadow: 0 0 0 3px rgba(79, 70, 229, 0.1);
        }
        
        .badge-status {
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 0.75rem;
            font-weight: 600;
        }
        .badge-active { background: #dcfce7; color: #166534; }
        .badge-inactive { background: #fee2e2; color: #991b1b; }
        .badge-completed { background: #fef3c7; color: #92400e; }
        
        .btn-action {
            border-radius: 10px;
            padding: 6px 14px;
            font-size: 0.8rem;
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
                <i class="fas fa-graduation-cap"></i> <?php echo htmlspecialchars($teacher_department); ?>
            </p>
            <p class="teacher-subject">
                <i class="fas fa-book"></i> <?php echo htmlspecialchars($teacher_subject); ?>
            </p>
        </div>
        <div class="nav-menu">
            <a href="forteacher.php" class="nav-item"><i class="fas fa-tachometer-alt"></i> <span>Main Dashboard</span></a>
            <a href="class.php" class="nav-item active"><i class="fas fa-chalkboard-teacher"></i> <span>Class</span></a>
            <a href="Request.php" class="nav-item"><i class="fas fa-file-signature"></i> <span>Request</span></a>
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
            <div class="page-title"><h2><i class="fas fa-chalkboard-teacher"></i> My Classes</h2></div>
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

  
        <div class="content-box">
            <h4>
                <?php if($edit_class_data): ?>
                    <i class="fas fa-edit"></i> Edit Class
                <?php else: ?>
                    <i class="fas fa-plus-circle"></i> Add New Class
                <?php endif; ?>
            </h4>
            <hr>
            
            <form method="POST">
                <?php if($edit_class_data): ?>
                    <input type="hidden" name="class_id" value="<?php echo $edit_class_data['id']; ?>">
                <?php endif; ?>
                
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label class="form-label"><i class="fas fa-users"></i> Class Name <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" name="class_name" 
                               placeholder="e.g. Computer Science Year 1" 
                               value="<?php echo $edit_class_data ? htmlspecialchars($edit_class_data['class_name']) : ''; ?>" required>
                    </div>
                    <div class="col-md-6 mb-3">
                        <label class="form-label"><i class="fas fa-book"></i> Subject <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" name="subject" 
                               placeholder="e.g. Programming, Mathematics" 
                               value="<?php echo $edit_class_data ? htmlspecialchars($edit_class_data['subject']) : ''; ?>" required>
                    </div>
                    <div class="col-md-4 mb-3">
                        <label class="form-label"><i class="fas fa-door-open"></i> Room</label>
                        <input type="text" class="form-control" name="room" 
                               placeholder="e.g. Room 201" 
                               value="<?php echo $edit_class_data ? htmlspecialchars($edit_class_data['room']) : ''; ?>">
                    </div>
                    <div class="col-md-4 mb-3">
                        <label class="form-label"><i class="fas fa-calendar-day"></i> Schedule Day</label>
                        <select class="form-select" name="schedule_day">
                            <option value="">Select Day</option>
                            <?php 
                            $days = ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday'];
                            foreach($days as $day):
                                $selected = ($edit_class_data && $edit_class_data['schedule_day'] == $day) ? 'selected' : '';
                            ?>
                                <option value="<?php echo $day; ?>" <?php echo $selected; ?>><?php echo $day; ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-2 mb-3">
                        <label class="form-label"><i class="fas fa-clock"></i> Start Time</label>
                        <input type="time" class="form-control" name="start_time" 
                               value="<?php echo $edit_class_data ? $edit_class_data['start_time'] : ''; ?>">
                    </div>
                    <div class="col-md-2 mb-3">
                        <label class="form-label"><i class="fas fa-clock"></i> End Time</label>
                        <input type="time" class="form-control" name="end_time" 
                               value="<?php echo $edit_class_data ? $edit_class_data['end_time'] : ''; ?>">
                    </div>
                    <div class="col-md-4 mb-3">
                        <label class="form-label"><i class="fas fa-graduation-cap"></i> Semester</label>
                        <input type="text" class="form-control" name="semester" 
                               placeholder="e.g. Semester 1" 
                               value="<?php echo $edit_class_data ? htmlspecialchars($edit_class_data['semester']) : ''; ?>">
                    </div>
                    <div class="col-md-4 mb-3">
                        <label class="form-label"><i class="fas fa-calendar-alt"></i> Academic Year</label>
                        <input type="text" class="form-control" name="academic_year" 
                               placeholder="e.g. 2024-2025" 
                               value="<?php echo $edit_class_data ? htmlspecialchars($edit_class_data['academic_year']) : ''; ?>">
                    </div>
                    <div class="col-md-4 mb-3">
                        <label class="form-label"><i class="fas fa-toggle-on"></i> Status</label>
                        <select class="form-select" name="status">
                            <option value="active" <?php echo ($edit_class_data && $edit_class_data['status'] == 'active') ? 'selected' : ''; ?>>Active</option>
                            <option value="inactive" <?php echo ($edit_class_data && $edit_class_data['status'] == 'inactive') ? 'selected' : ''; ?>>Inactive</option>
                            <option value="completed" <?php echo ($edit_class_data && $edit_class_data['status'] == 'completed') ? 'selected' : ''; ?>>Completed</option>
                        </select>
                    </div>
                    <div class="col-md-12">
                        <?php if($edit_class_data): ?>
                            <button type="submit" name="edit_class" class="btn-save" style="background: #f59e0b;">
                                <i class="fas fa-save"></i> Update Class
                            </button>
                            <a href="class.php" class="btn btn-secondary ms-2" style="border-radius:10px; padding:12px 30px;">
                                <i class="fas fa-times"></i> Cancel
                            </a>
                        <?php else: ?>
                            <button type="submit" name="add_class" class="btn-save">
                                <i class="fas fa-plus"></i> Add Class
                            </button>
                        <?php endif; ?>
                    </div>
                </div>
            </form>
        </div>


        <div class="content-box mt-4">
            <h4><i class="fas fa-list"></i> My Classes (<?php echo mysqli_num_rows($classes_result); ?>)</h4>
            <hr>
            
            <?php if(mysqli_num_rows($classes_result) > 0): ?>
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>#</th>
                                <th>Class / Subject</th>
                                <th>Room</th>
                                <th>Schedule</th>
                                <th>Semester</th>
                                <th>Status</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php 
                            $count = 1;
                            while($class = mysqli_fetch_assoc($classes_result)):
                                $status_class = $class['status'] == 'active' ? 'badge-active' : ($class['status'] == 'completed' ? 'badge-completed' : 'badge-inactive');
                            ?>
                            <tr>
                                <td><?php echo $count++; ?></td>
                                <td>
                                    <strong><?php echo htmlspecialchars($class['class_name']); ?></strong>
                                    <br>
                                    <small class="text-muted"><i class="fas fa-book"></i> <?php echo htmlspecialchars($class['subject']); ?></small>
                                </td>
                                <td>
                                    <?php echo $class['room'] ? htmlspecialchars($class['room']) : '<span class="text-muted">-</span>'; ?>
                                </td>
                                <td>
                                    <?php if($class['schedule_day']): ?>
                                        <span class="badge bg-primary"><?php echo $class['schedule_day']; ?></span>
                                        <?php if($class['start_time']): ?>
                                            <br><small><?php echo date('h:i A', strtotime($class['start_time'])); ?> - <?php echo date('h:i A', strtotime($class['end_time'])); ?></small>
                                        <?php endif; ?>
                                    <?php else: ?>
                                        <span class="text-muted">-</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php echo $class['semester'] ? htmlspecialchars($class['semester']) : '-'; ?>
                                    <br>
                                    <small class="text-muted"><?php echo $class['academic_year'] ? htmlspecialchars($class['academic_year']) : ''; ?></small>
                                </td>
                                <td>
                                    <span class="badge-status <?php echo $status_class; ?>">
                                        <?php echo ucfirst($class['status']); ?>
                                    </span>
                                </td>
                                <td>
                                    <div class="btn-group" role="group">
                                        <a href="?edit=<?php echo $class['id']; ?>" class="btn btn-sm btn-outline-primary btn-action" title="Edit">
                                            <i class="fas fa-edit"></i>
                                        </a>
                                        <a href="?toggle=<?php echo $class['id']; ?>" class="btn btn-sm btn-outline-warning btn-action" title="Toggle Status">
                                            <i class="fas fa-sync-alt"></i>
                                        </a>
                                        <a href="?delete=<?php echo $class['id']; ?>" class="btn btn-sm btn-outline-danger btn-action" title="Delete" onclick="return confirm('Are you sure you want to delete this class?')">
                                            <i class="fas fa-trash"></i>
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
                    <i class="fas fa-chalkboard-teacher" style="font-size: 48px; color: #cbd5e1;"></i>
                    <p class="text-muted mt-3">You haven't added any classes yet.</p>
                    <p class="text-muted">Use the form above to add your first class!</p>
                </div>
            <?php endif; ?>
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
</script>

</body>
</html>