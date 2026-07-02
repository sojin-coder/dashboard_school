<?php
// ============================================
// settings_student.php - Student Settings Page
// ============================================

// Include student authentication
include "student_auth.php";

// ============================================
// អថេរពី student_auth.php អាចប្រើបានហើយ:
// $logged_in_id, $logged_in_student, $student_department
// $student_email_db, $student_phone, $student_address
// $student_image, $student_grade, $student_shift
// $db_conn (database connection)
// ============================================

// យកព័ត៌មានសិស្សចុងក្រោយ (ក្នុងករណីមានការកែប្រែ)
$student_query = "SELECT * FROM students WHERE id = '$logged_in_id'";
$student_result = mysqli_query($db_conn, $student_query);
$student = mysqli_fetch_assoc($student_result);

// ប្រសិនបើរកមិនឃើញ ប្រើទិន្នន័យពី student_auth.php
if (!$student) {
    $student = [
        'id' => $logged_in_id,
        'name' => $logged_in_student,
        'email' => $student_email_db,
        'phone' => $student_phone,
        'address' => $student_address,
        'college' => $student_department,
        'grade' => $student_grade,
        'Shift' => $student_shift,
        'image' => $student_image
    ];
}

$message = '';
$message_type = '';

// ============================================
// Update Profile
// ============================================
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_profile'])) {
    $name = mysqli_real_escape_string($db_conn, trim($_POST['name']));
    $phone = mysqli_real_escape_string($db_conn, trim($_POST['phone']));
    $address = mysqli_real_escape_string($db_conn, trim($_POST['address']));
    
    // Validate
    $errors = [];
    if (empty($name)) $errors[] = "Name is required";
    if (!empty($phone) && !preg_match('/^[0-9\-+() ]+$/', $phone)) {
        $errors[] = "Invalid phone number";
    }
    
    if (empty($errors)) {
        $update_query = "UPDATE students SET name='$name', phone='$phone', address='$address' WHERE id='$logged_in_id'";
        if (mysqli_query($db_conn, $update_query)) {
            $message = "✅ Profile updated successfully!";
            $message_type = "success";
            // Update session
            $_SESSION['name'] = $name;
            // Refresh student data
            $student_result = mysqli_query($db_conn, $student_query);
            $student = mysqli_fetch_assoc($student_result);
        } else {
            $message = "❌ Error updating profile: " . mysqli_error($db_conn);
            $message_type = "danger";
        }
    } else {
        $message = "⚠️ " . implode(", ", $errors);
        $message_type = "warning";
    }
}

// ============================================
// Change Password
// ============================================
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['change_password'])) {
    $current_password = $_POST['current_password'] ?? '';
    $new_password = $_POST['new_password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';
    
    // Validate
    $errors = [];
    if (empty($current_password)) $errors[] = "Current password is required";
    if (empty($new_password)) $errors[] = "New password is required";
    if (strlen($new_password) < 6) $errors[] = "New password must be at least 6 characters";
    if ($new_password !== $confirm_password) $errors[] = "New passwords do not match";
    
    if (empty($errors)) {
        // Get current password from database
        $pass_query = "SELECT password FROM students WHERE id='$logged_in_id'";
        $pass_result = mysqli_query($db_conn, $pass_query);
        $pass_data = mysqli_fetch_assoc($pass_result);
        
        if ($pass_data && password_verify($current_password, $pass_data['password'])) {
            $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
            $update_pass = "UPDATE students SET password='$hashed_password' WHERE id='$logged_in_id'";
            if (mysqli_query($db_conn, $update_pass)) {
                $message = "✅ Password changed successfully!";
                $message_type = "success";
            } else {
                $message = "❌ Error changing password: " . mysqli_error($db_conn);
                $message_type = "danger";
            }
        } else {
            $message = "⚠️ Current password is incorrect!";
            $message_type = "warning";
        }
    } else {
        $message = "⚠️ " . implode(", ", $errors);
        $message_type = "warning";
    }
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
    <title>Settings - KRaksa Education</title>
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
            border: 4px solid rgba(255,255,255,0.8); 
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
        .top-bar { display: flex; justify-content: space-between; align-items: center; padding: 12px 24px; background: white; border-radius: 16px; box-shadow: 0 2px 8px rgba(0,0,0,0.05); margin-bottom: 28px; flex-wrap: wrap; gap: 15px; }
        .page-title h2 { font-size: 1.3rem; font-weight: 600; color: #1e293b; margin: 0; }
        
        .kpi-row { display: flex; flex-wrap: wrap; gap: 20px; margin-bottom: 32px; }
        .kpi-card { border-radius: 20px; padding: 20px 24px; flex: 1; min-width: 280px; box-shadow: 0 4px 12px rgba(0,0,0,0.08); transition: transform 0.3s ease; background: white; border-left: 6px solid; text-align: left; }
        .kpi-card:hover { transform: translateY(-3px); }
        .kpi-title { font-size: 13px; text-transform: uppercase; letter-spacing: 1px; opacity: 0.7; margin-bottom: 20px; font-weight: 600; }
        
        .form-control:focus {
            border-color: #4f46e5;
            box-shadow: 0 0 0 0.2rem rgba(79, 70, 229, 0.25);
        }
        
        .info-row {
            display: flex;
            flex-wrap: wrap;
            gap: 15px;
        }
        .info-item {
            flex: 1;
            min-width: 150px;
            padding: 10px;
            background: #f8fafc;
            border-radius: 10px;
        }
        .info-item strong {
            display: block;
            font-size: 12px;
            color: #64748b;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            margin-bottom: 5px;
        }
        .info-item p {
            margin: 0;
            font-size: 16px;
            font-weight: 600;
            color: #1e293b;
        }
        
        @media (max-width: 768px) {
            .sidebar { width: 70px; }
            .sidebar-header h1, .sidebar-header p, .nav-item span { display: none; }
            .nav-item { justify-content: center; }
            .main-content { padding: 15px; }
            .top-bar { flex-direction: column; align-items: flex-start; }
            .info-item { min-width: 100%; }
        }
    </style>
</head>
<body>

<div class="container">
    <div class="sidebar">
        <div class="sidebar-header">
            <img src="<?php echo !empty($student['image']) ? htmlspecialchars($student['image']) : 'https://i.pinimg.com/736x/be/dd/b8/beddb8c8c3c4c967cb821aae0cb796e3.jpg'; ?>" 
                 alt="Profile" 
                 class="profile-img" />
            <h1><?php echo htmlspecialchars($student['name'] ?? $logged_in_student); ?></h1>
            <p><?php echo htmlspecialchars($student['college'] ?? $student_department ?? 'Student'); ?></p>
        </div>
        <div class="nav-menu">
            <a href="forstudent.php" class="nav-item"><i class="fas fa-tachometer-alt"></i> <span>Main Dashboard</span></a>
            <a href="scores_stu.php" class="nav-item"><i class="fas fa-graduation-cap"></i> <span>Scores</span></a>
            <a href="attendance_student.php" class="nav-item"><i class="fas fa-calendar-check"></i> <span>Attendance</span></a>
            <a href="Requests.php" class="nav-item"><i class="fas fa-file-signature"></i> <span>Request</span></a>
            <a href="settings_student.php" class="nav-item active"><i class="fas fa-cog"></i> <span>Setting</span></a>
            <div class="nav-bottom">
                <a href="logout.php" class="nav-item" style="padding-left:8px;">
                    <i class="fas fa-sign-out-alt"></i> <span>Logout</span>
                </a>
            </div>
        </div>
    </div>

    <div class="main-content">
        <div class="top-bar">
            <div class="page-title"><h2><i class="fas fa-cog me-2"></i>Settings</h2></div>
            <div class="date-time" id="currentDateTime"></div>
        </div>
        
        <?php if ($message): ?>
            <div class="alert alert-<?php echo $message_type; ?> alert-dismissible fade show" role="alert">
                <?php echo $message; ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>
        
        <!-- PROFILE SETTINGS -->
        <div class="kpi-row">
            <div class="kpi-card" style="border-left-color: #4f46e5; flex: 1.5;">
                <div class="kpi-title"><i class="fas fa-user-edit"></i> Profile Settings</div>
                <form method="POST" action="">
                    <div class="mb-3">
                        <label class="form-label">Full Name <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" name="name" value="<?php echo htmlspecialchars($student['name'] ?? ''); ?>" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Email (Read Only)</label>
                        <input type="email" class="form-control" value="<?php echo htmlspecialchars($student['email'] ?? ''); ?>" disabled>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Phone</label>
                        <input type="text" class="form-control" name="phone" value="<?php echo htmlspecialchars($student['phone'] ?? ''); ?>" placeholder="Enter phone number">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Address</label>
                        <textarea class="form-control" name="address" rows="2" placeholder="Enter your address"><?php echo htmlspecialchars($student['address'] ?? ''); ?></textarea>
                    </div>
                    <button type="submit" name="update_profile" class="btn btn-primary">
                        <i class="fas fa-save"></i> Update Profile
                    </button>
                </form>
            </div>
            
            <!-- CHANGE PASSWORD -->
            <div class="kpi-card" style="border-left-color: #f59e0b; flex: 1;">
                <div class="kpi-title"><i class="fas fa-key"></i> Change Password</div>
                <form method="POST" action="">
                    <div class="mb-3">
                        <label class="form-label">Current Password <span class="text-danger">*</span></label>
                        <input type="password" class="form-control" name="current_password" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">New Password <span class="text-danger">*</span></label>
                        <input type="password" class="form-control" name="new_password" required minlength="6">
                        <small class="text-muted">Password must be at least 6 characters</small>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Confirm New Password <span class="text-danger">*</span></label>
                        <input type="password" class="form-control" name="confirm_password" required>
                    </div>
                    <button type="submit" name="change_password" class="btn btn-warning">
                        <i class="fas fa-key"></i> Change Password
                    </button>
                </form>
            </div>
        </div>
        
        <!-- ACCOUNT INFORMATION -->
        <div class="kpi-row">
            <div class="kpi-card" style="border-left-color: #10b981; width: 100%;">
                <div class="kpi-title"><i class="fas fa-info-circle"></i> Account Information</div>
                <div class="info-row">
                    <div class="info-item">
                        <strong>Student ID</strong>
                        <p>#<?php echo htmlspecialchars($student['id'] ?? $logged_in_id); ?></p>
                    </div>
                    <div class="info-item">
                        <strong>College</strong>
                        <p><?php echo htmlspecialchars($student['college'] ?? $student_department ?? 'N/A'); ?></p>
                    </div>
                    <div class="info-item">
                        <strong>Grade</strong>
                        <p><?php echo htmlspecialchars($student['grade'] ?? $student_grade ?? 'N/A'); ?></p>
                    </div>
                    <div class="info-item">
                        <strong>Shift</strong>
                        <p><?php echo htmlspecialchars($student['Shift'] ?? $student_shift ?? 'N/A'); ?></p>
                    </div>
                    <div class="info-item">
                        <strong>Year</strong>
                        <p><?php echo htmlspecialchars($student['year'] ?? $student_year ?? 'N/A'); ?></p>
                    </div>
                    <div class="info-item">
                        <strong>Skill</strong>
                        <p><?php echo htmlspecialchars($student['skill'] ?? $student_skill ?? 'N/A'); ?></p>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- DANGER ZONE -->
        <div class="kpi-row">
            <div class="kpi-card" style="border-left-color: #ef4444; width: 100%;">
                <div class="kpi-title"><i class="fas fa-exclamation-triangle text-danger"></i> Danger Zone</div>
                <div class="row">
                    <div class="col-md-6">
                        <div class="alert alert-danger">
                            <h5><i class="fas fa-sign-out-alt"></i> Logout</h5>
                            <p>Logout from your account</p>
                            <a href="logout.php" class="btn btn-danger">
                                <i class="fas fa-sign-out-alt"></i> Logout
                            </a>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="alert alert-warning">
                            <h5><i class="fas fa-trash-alt"></i> Delete Account</h5>
                            <p>This action cannot be undone</p>
                            <button class="btn btn-danger" onclick="alert('Please contact admin to delete your account')">
                                <i class="fas fa-trash-alt"></i> Delete Account
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

<script>
    function updateDateTime() {
        const now = new Date();
        const options = { weekday: 'long', year: 'numeric', month: 'long', day: 'numeric', hour: '2-digit', minute: '2-digit' };
        document.getElementById('currentDateTime').innerHTML = now.toLocaleDateString('en-US', options);
    }
    updateDateTime();
    setInterval(updateDateTime, 60000);
</script>
</body>
</html>