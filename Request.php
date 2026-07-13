<?php

        include "check_login.php";
        
        
        $message = '';
        $error = '';
        
        if(isset($_POST['add_rule'])) {
            $title = mysqli_real_escape_string($conn, $_POST['title']);
            $description = mysqli_real_escape_string($conn, $_POST['description']);
            $rule_type = mysqli_real_escape_string($conn, $_POST['rule_type']);
            $apply_date = mysqli_real_escape_string($conn, $_POST['apply_date']);
            $end_date = !empty($_POST['end_date']) ? mysqli_real_escape_string($conn, $_POST['end_date']) : NULL;
            $status = mysqli_real_escape_string($conn, $_POST['status']);
            $selected_days = isset($_POST['days']) ? $_POST['days'] : [];
            
            if(empty($title) || empty($description)) {
                $error = "Please fill in all required fields!";
            } else {
                // បញ្ចូលច្បាប់
                $insert_sql = "INSERT INTO rules (
                    teacher_id, teacher_name, title, description, rule_type, 
                    apply_date, end_date, status
                ) VALUES (
                    '$logged_in_id', '$logged_in_teacher', '$title', '$description', '$rule_type',
                    '$apply_date', " . ($end_date ? "'$end_date'" : "NULL") . ", '$status'
                )";
                
                if(mysqli_query($conn, $insert_sql)) {
                    $rule_id = mysqli_insert_id($conn);
                    
                    // បញ្ចូលថ្ងៃអនុវត្ត
                    if(!empty($selected_days)) {
                        foreach($selected_days as $day) {
                            $day_sql = "INSERT INTO rule_days (rule_id, day_of_week) VALUES ('$rule_id', '$day')";
                            mysqli_query($conn, $day_sql);
                        }
                    }
                    
                    $message = "✅ Rule added successfully!";
                } else {
                    $error = "❌ Error adding rule: " . mysqli_error($conn);
                }
            }
        }
        
        // ============================================
        // ដំណើរការលុបច្បាប់
        // ============================================
        if(isset($_GET['delete']) && is_numeric($_GET['delete'])) {
            $rule_id = $_GET['delete'];
            $delete_sql = "DELETE FROM rules WHERE id = '$rule_id' AND teacher_id = '$logged_in_id'";
            
            if(mysqli_query($conn, $delete_sql)) {
                $message = "✅ Rule deleted successfully!";
            } else {
                $error = "❌ Error deleting rule: " . mysqli_error($conn);
            }
        }
        
        
        if(isset($_GET['toggle']) && is_numeric($_GET['toggle'])) {
            $rule_id = $_GET['toggle'];
            $status_sql = "SELECT status FROM rules WHERE id = '$rule_id' AND teacher_id = '$logged_in_id'";
            $status_result = mysqli_query($conn, $status_sql);
            $status_row = mysqli_fetch_assoc($status_result);
            
            if($status_row) {
                $new_status = ($status_row['status'] == 'active') ? 'inactive' : 'active';
                $update_sql = "UPDATE rules SET status = '$new_status' WHERE id = '$rule_id' AND teacher_id = '$logged_in_id'";
                mysqli_query($conn, $update_sql);
                $message = "✅ Rule status updated!";
            }
        }
        
        
        $rules_query = "SELECT r.*, 
                        (SELECT COUNT(*) FROM rule_days WHERE rule_id = r.id) as day_count
                        FROM rules r 
                        WHERE r.teacher_id = '$logged_in_id' 
                        ORDER BY r.created_at DESC";
        $rules_result = mysqli_query($conn, $rules_query);
        
        $rule_days = [];
        $all_rules = mysqli_query($conn, "SELECT id FROM rules WHERE teacher_id = '$logged_in_id'");
        while($rule = mysqli_fetch_assoc($all_rules)) {
            $days_query = "SELECT day_of_week FROM rule_days WHERE rule_id = '{$rule['id']}'";
            $days_result = mysqli_query($conn, $days_query);
            $days = [];
            while($day = mysqli_fetch_assoc($days_result)) {
                $days[] = $day['day_of_week'];
            }
            $rule_days[$rule['id']] = $days;
        }
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Rules Management - Teacher</title>
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
        .sidebar-header { padding: 28px 24px; border-bottom: 1px solid rgba(255, 255, 255, 0.1); margin-bottom: 24px; text-align: center; }
        .sidebar-header img { width: 100px; height: 100px; border-radius: 50%; margin: auto; display: block; border: 4px solid white; object-fit: cover; }
        .sidebar-header h1 { font-size: 1.8rem; font-weight: 700; color: white; margin-top: 10px; }
        .sidebar-header p { font-size: 0.85rem; opacity: 0.8; }
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
        
        .rule-card {
            border-left: 4px solid #4f46e5;
            padding: 15px 20px;
            margin-bottom: 15px;
            background: #f8fafc;
            border-radius: 10px;
            transition: 0.3s;
        }
        .rule-card:hover {
            background: #f1f5f9;
            transform: translateX(5px);
        }
        .rule-card .rule-title {
            font-weight: 600;
            font-size: 1.1rem;
            color: #1e293b;
        }
        .rule-card .rule-meta {
            font-size: 0.85rem;
            color: #64748b;
            margin-top: 5px;
        }
        .rule-card .rule-days {
            display: flex;
            gap: 5px;
            flex-wrap: wrap;
            margin-top: 8px;
        }
        .rule-card .rule-days .day-badge {
            background: #e2e8f0;
            padding: 2px 10px;
            border-radius: 20px;
            font-size: 0.75rem;
            color: #334155;
        }
        .rule-card .rule-days .day-badge.active-day {
            background: #4f46e5;
            color: white;
        }
        .badge-status {
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 0.75rem;
            font-weight: 600;
        }
        .badge-active { background: #dcfce7; color: #166534; }
        .badge-inactive { background: #fee2e2; color: #991b1b; }
        .badge-expired { background: #fef3c7; color: #92400e; }
        
        .form-control, .form-select {
            border-radius: 10px;
            padding: 10px 15px;
            border: 1px solid #cbd5e1;
        }
        .form-control:focus, .form-select:focus {
            border-color: #4f46e5;
            box-shadow: 0 0 0 3px rgba(79, 70, 229, 0.1);
        }
        
        .day-checkbox {
            display: inline-flex;
            align-items: center;
            gap: 5px;
            margin-right: 15px;
            margin-bottom: 5px;
        }
        .day-checkbox input[type="checkbox"] {
            width: 18px;
            height: 18px;
            accent-color: #4f46e5;
        }
        
        .btn-rule {
            border-radius: 10px;
            padding: 8px 16px;
            font-size: 0.85rem;
        }
        
        .alert-custom { border-radius: 10px; padding: 15px 20px; }
        
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
    <img src="<?php echo !empty($teacher_image) ? $teacher_image : 'https://ui-avatars.com/api/?name=' . urlencode($logged_in_teacher) . '&size=120&background=4f46e5&color=fff'; ?>" 
         alt="Profile Image" 
         style="width: 100px; height: 100px; border-radius: 50%; margin: auto; display: block; border: 4px solid white; object-fit: cover;" />
    <h1 style="font-size: 1.2rem; font-weight: 700; color: white; margin-top: 10px;"><?php echo htmlspecialchars($logged_in_teacher); ?></h1>
    <p style="font-size: 0.85rem; opacity: 0.8;">
        <i class="fas fa-graduation-cap"></i> <?php echo htmlspecialchars($teacher_department); ?>
        <br>
        <span style="font-size: 0.75rem; opacity: 0.7;">
            <i class="fas fa-book"></i> <?php echo htmlspecialchars($teacher_subject); ?>
        </span>
    </p>
</div>
        <div class="nav-menu">
            <a href="forteacher.php" class="nav-item"><i class="fas fa-tachometer-alt"></i> <span>Main Dashboard</span></a>
            <a href="class.php" class="nav-item"><i class="fas fa-chalkboard-teacher"></i> <span>Class</span></a>
            <a href="Request.php" class="nav-item active"><i class="fas fa-file-signature"></i> <span>Request</span></a>
            <a href="substitute.php" class="nav-item">
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
            <div class="page-title"><h2><i class="fas fa-file-signature"></i> Rules Management</h2></div>
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
        <!-- ADD RULE FORM                                -->
        <!-- ============================================ -->
        <div class="content-box">
            <h4><i class="fas fa-plus-circle"></i> Add New Rule</h4>
            <hr>
            
            <form method="POST">
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label class="form-label"><i class="fas fa-tag"></i> Rule Title <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" name="title" placeholder="e.g. Attendance Policy" required>
                    </div>
                    <div class="col-md-6 mb-3">
                        <label class="form-label"><i class="fas fa-list"></i> Rule Type</label>
                        <select class="form-select" name="rule_type">
                            <option value="attendance">Attendance</option>
                            <option value="behavior">Behavior</option>
                            <option value="uniform">Uniform</option>
                            <option value="homework">Homework</option>
                            <option value="exam">Exam</option>
                            <option value="other">Other</option>
                        </select>
                    </div>
                    <div class="col-md-12 mb-3">
                        <label class="form-label"><i class="fas fa-align-left"></i> Description <span class="text-danger">*</span></label>
                        <textarea class="form-control" name="description" rows="3" placeholder="Describe the rule in detail..." required></textarea>
                    </div>
                    <div class="col-md-4 mb-3">
                        <label class="form-label"><i class="fas fa-calendar-start"></i> Apply Date <span class="text-danger">*</span></label>
                        <input type="date" class="form-control" name="apply_date" required>
                    </div>
                    <div class="col-md-4 mb-3">
                        <label class="form-label"><i class="fas fa-calendar-end"></i> End Date</label>
                        <input type="date" class="form-control" name="end_date">
                        <small class="text-muted">Leave empty if no end date</small>
                    </div>
                    <div class="col-md-4 mb-3">
                        <label class="form-label"><i class="fas fa-toggle-on"></i> Status</label>
                        <select class="form-select" name="status">
                            <option value="active">Active</option>
                            <option value="inactive">Inactive</option>
                        </select>
                    </div>
                    <div class="col-md-12 mb-3">
                        <label class="form-label"><i class="fas fa-calendar-week"></i> Apply on Days</label>
                        <div>
                            <label class="day-checkbox">
                                <input type="checkbox" name="days[]" value="Monday"> Monday
                            </label>
                            <label class="day-checkbox">
                                <input type="checkbox" name="days[]" value="Tuesday"> Tuesday
                            </label>
                            <label class="day-checkbox">
                                <input type="checkbox" name="days[]" value="Wednesday"> Wednesday
                            </label>
                            <label class="day-checkbox">
                                <input type="checkbox" name="days[]" value="Thursday"> Thursday
                            </label>
                            <label class="day-checkbox">
                                <input type="checkbox" name="days[]" value="Friday"> Friday
                            </label>
                            <label class="day-checkbox">
                                <input type="checkbox" name="days[]" value="Saturday"> Saturday
                            </label>
                            <label class="day-checkbox">
                                <input type="checkbox" name="days[]" value="Sunday"> Sunday
                            </label>
                        </div>
                        <small class="text-muted">Select which days this rule applies to</small>
                    </div>
                    <div class="col-md-12">
                        <button type="submit" name="add_rule" class="btn-save">
                            <i class="fas fa-plus"></i> Add Rule
                        </button>
                    </div>
                </div>
            </form>
        </div>

        <!-- ============================================ -->
        <!-- RULES LIST                                  -->
        <!-- ============================================ -->
        <div class="content-box mt-4">
            <h4><i class="fas fa-list"></i> My Rules</h4>
            <hr>
            
            <?php if(mysqli_num_rows($rules_result) > 0): ?>
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>#</th>
                                <th>Title</th>
                                <th>Type</th>
                                <th>Apply Date</th>
                                <th>Days</th>
                                <th>Status</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php 
                            $count = 1;
                            while($rule = mysqli_fetch_assoc($rules_result)): 
                                $days = $rule_days[$rule['id']] ?? [];
                                $status_class = $rule['status'] == 'active' ? 'badge-active' : ($rule['status'] == 'expired' ? 'badge-expired' : 'badge-inactive');
                            ?>
                            <tr>
                                <td><?php echo $count++; ?></td>
                                <td>
                                    <strong><?php echo htmlspecialchars($rule['title']); ?></strong>
                                    <br>
                                    <small class="text-muted"><?php echo htmlspecialchars(substr($rule['description'], 0, 50)) . (strlen($rule['description']) > 50 ? '...' : ''); ?></small>
                                </td>
                                <td><span class="badge bg-secondary"><?php echo ucfirst($rule['rule_type']); ?></span></td>
                                <td>
                                    <?php echo date('d/m/Y', strtotime($rule['apply_date'])); ?>
                                    <?php if($rule['end_date']): ?>
                                        <br><small class="text-muted">Until: <?php echo date('d/m/Y', strtotime($rule['end_date'])); ?></small>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php if(!empty($days)): ?>
                                        <div class="rule-days">
                                            <?php foreach($days as $day): ?>
                                                <span class="day-badge active-day"><?php echo substr($day, 0, 3); ?></span>
                                            <?php endforeach; ?>
                                        </div>
                                    <?php else: ?>
                                        <span class="text-muted">All days</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <span class="badge-status <?php echo $status_class; ?>">
                                        <?php echo ucfirst($rule['status']); ?>
                                    </span>
                                </td>
                                <td>
                                    <a href="?toggle=<?php echo $rule['id']; ?>" class="btn btn-sm btn-rule btn-outline-primary" title="Toggle Status">
                                        <i class="fas fa-sync-alt"></i>
                                    </a>
                                    <a href="?delete=<?php echo $rule['id']; ?>" class="btn btn-sm btn-rule btn-outline-danger" title="Delete" onclick="return confirm('Are you sure you want to delete this rule?')">
                                        <i class="fas fa-trash"></i>
                                    </a>
                                </td>
                            </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
            <?php else: ?>
                <div class="text-center py-4">
                    <i class="fas fa-file-signature" style="font-size: 48px; color: #cbd5e1;"></i>
                    <p class="text-muted mt-3">You haven't created any rules yet.</p>
                    <p class="text-muted">Use the form above to add your first rule!</p>
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