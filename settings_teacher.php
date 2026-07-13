<?php
        include "db.php";
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        
        
        // ការពារ user ប្តូរ link
        if(!isset($_SESSION['id'])){
            header("Location: login.php");
            exit();
        }
        
        // ពិនិត្យមើលតួនាទី
        if($_SESSION['role'] != 'teacher'){
            header("Location: index.php");
            exit();
        }
        
        // ============================================
        // យកអ៊ីមែលពី Session
        // ============================================
        $logged_in_email = $_SESSION['email'] ?? '';
        
        // យកព័ត៌មានគ្រូពីតារាង teachers
        $sql_teacher_info = "SELECT * FROM teachers WHERE email = '$logged_in_email'";
        $result_teacher_info = mysqli_query($conn, $sql_teacher_info);
        $teacher_info = mysqli_fetch_assoc($result_teacher_info);
        
        if(!$teacher_info) {
            echo "<script>alert('Teacher not found!'); window.location='logout.php';</script>";
            exit();
        }
        
        // ============================================
        // យកទិន្នន័យគ្រូ
        // ============================================
        $teacher_id = $teacher_info['id'];
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
        
        $message = '';
        $error = '';
        
        // ============================================
        // ដំណើរការពេលអ្នកប្រើចុចប៊ូតុង Update
        // ============================================
        if(isset($_POST['update_profile'])) {
            $name = mysqli_real_escape_string($conn, $_POST['name']);
            $phone = mysqli_real_escape_string($conn, $_POST['phone']);
            $gender = mysqli_real_escape_string($conn, $_POST['gender']);
            $dob = mysqli_real_escape_string($conn, $_POST['dob']);
            $address = mysqli_real_escape_string($conn, $_POST['address']);
            $subject = mysqli_real_escape_string($conn, $_POST['subject']);
            $department = mysqli_real_escape_string($conn, $_POST['department']);
            
            // ដំណើរការរូបភាព
            $image_path = $teacher_image;
            if(isset($_FILES['image']) && $_FILES['image']['error'] == 0) {
                $allowed = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
                $filename = $_FILES['image']['name'];
                $file_ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
                $file_size = $_FILES['image']['size'];
                $file_tmp = $_FILES['image']['tmp_name'];
                
                if(in_array($file_ext, $allowed)) {
                    if($file_size <= 5000000) { // 5MB
                        $new_filename = 'teacher_' . $teacher_id . '_' . time() . '.' . $file_ext;
                        $upload_path = 'uploads/teachers/' . $new_filename;
                        
                        // បង្កើត folder បើមិនទាន់មាន
                        if(!is_dir('uploads/teachers')) {
                            mkdir('uploads/teachers', 0777, true);
                        }
                        
                        if(move_uploaded_file($file_tmp, $upload_path)) {
                            // លុបរូបភាពចាស់
                            if(!empty($teacher_image) && file_exists($teacher_image)) {
                                unlink($teacher_image);
                            }
                            $image_path = $upload_path;
                        } else {
                            $error = "Failed to upload image!";
                        }
                    } else {
                        $error = "Image size too large! Max 5MB.";
                    }
                } else {
                    $error = "Invalid image format! Allowed: JPG, PNG, GIF, WEBP";
                }
            }
            
            // បើគ្មានកំហុស ធ្វើការកែប្រែ
            if(empty($error)) {
                $update_sql = "UPDATE teachers SET 
                    name = '$name',
                    phone = '$phone',
                    gender = '$gender',
                    dob = '$dob',
                    address = '$address',
                    subject = '$subject',
                    department = '$department',
                    image = '$image_path'
                    WHERE id = $teacher_id";
                
                if(mysqli_query($conn, $update_sql)) {
                    $message = "✅ Profile updated successfully!";
                    
                    // ធ្វើបច្ចុប្បន្នភាពអថេរ
                    $logged_in_teacher = $name;
                    $teacher_phone = $phone;
                    $teacher_gender = $gender;
                    $teacher_dob = $dob;
                    $teacher_address = $address;
                    $teacher_subject = $subject;
                    $teacher_department = $department;
                    $teacher_image = $image_path;
                    
                    // ធ្វើបច្ចុប្បន្នភាព Session name
                    $_SESSION['name'] = $name;
                } else {
                    $error = "❌ Error updating profile: " . mysqli_error($conn);
                }
            }
        }
        
        // ============================================
        // ដំណើរការផ្លាស់ប្តូរពាក្យសម្ងាត់
        // ============================================
        if(isset($_POST['change_password'])) {
            $current_password = $_POST['current_password'];
            $new_password = $_POST['new_password'];
            $confirm_password = $_POST['confirm_password'];
            
            // ពិនិត្យពាក្យសម្ងាត់បច្ចុប្បន្ន
            $check_sql = "SELECT password FROM logins WHERE email = '$logged_in_email'";
            $check_result = mysqli_query($conn, $check_sql);
            $check_row = mysqli_fetch_assoc($check_result);
            
            if(password_verify($current_password, $check_row['password'])) {
                if($new_password == $confirm_password) {
                    if(strlen($new_password) >= 6) {
                        $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
                        $update_pass_sql = "UPDATE logins SET password = '$hashed_password' WHERE email = '$logged_in_email'";
                        
                        if(mysqli_query($conn, $update_pass_sql)) {
                            $message = "✅ Password changed successfully!";
                        } else {
                            $error = "❌ Error changing password: " . mysqli_error($conn);
                        }
                    } else {
                        $error = "❌ New password must be at least 6 characters!";
                    }
                } else {
                    $error = "❌ Passwords do not match!";
                }
            } else {
                $error = "❌ Current password is incorrect!";
            }
        }
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Teacher Settings</title>
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
        
        .settings-container {
            background: white;
            border-radius: 20px;
            padding: 30px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.08);
        }
        .settings-container h3 {
            color: #1e293b;
            font-weight: 600;
            margin-bottom: 25px;
            border-bottom: 2px solid #e2e8f0;
            padding-bottom: 15px;
        }
        .settings-container .form-label { font-weight: 500; color: #334155; }
        .settings-container .form-control { border-radius: 10px; padding: 10px 15px; border: 1px solid #cbd5e1; }
        .settings-container .form-control:focus { border-color: #4f46e5; box-shadow: 0 0 0 3px rgba(79, 70, 229, 0.1); }
        .settings-container .form-control[readonly] { background: #f1f5f9; color: #64748b; cursor: not-allowed; }
        
        .profile-image-preview {
            width: 150px;
            height: 150px;
            border-radius: 50%;
            object-fit: cover;
            border: 4px solid #e2e8f0;
            margin-bottom: 15px;
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
        
        .alert-custom { border-radius: 10px; padding: 15px 20px; }
        .salary-display {
            background: #fef3c7;
            padding: 12px 20px;
            border-radius: 10px;
            border-left: 4px solid #f59e0b;
            color: #92400e;
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
            <a href="Request.php" class="nav-item"><i class="fas fa-file-signature"></i> <span>Request</span></a>
            <a href="substitute.php" class="nav-item">
                <i class="fas fa-people-arrows"></i>
                <span>Substitute Class</span>
            </a>
            <a href="settings_teacher.php" class="nav-item active"><i class="fas fa-cog"></i> <span>Setting</span></a>
            <div class="nav-bottom">
                <a href="logout.php" class="nav-item" style="padding-left:8px;">
                    <i class="fas fa-sign-out-alt"></i> <span>Logout</span>
                </a>
            </div>
        </div>
    </div>

    <div class="main-content">
        <div class="top-bar">
            <div class="page-title"><h2><i class="fas fa-cog"></i> Teacher Settings</h2></div>
            <div class="date-time" id="currentDateTime"></div>
        </div>

        <?php if(!empty($message)): ?>
            <div class="alert alert-success alert-custom"><i class="fas fa-check-circle"></i> <?php echo $message; ?></div>
        <?php endif; ?>
        <?php if(!empty($error)): ?>
            <div class="alert alert-danger alert-custom"><i class="fas fa-exclamation-circle"></i> <?php echo $error; ?></div>
        <?php endif; ?>

        <div class="settings-container">
            <h3><i class="fas fa-user-edit"></i> Edit Profile</h3>
            
            <form method="POST" enctype="multipart/form-data">
                <div class="row">
                    <!-- Profile Image -->
                    <div class="col-md-3 text-center">
                        <div>
                            <img src="<?php echo !empty($teacher_image) ? $teacher_image : 'https://ui-avatars.com/api/?name=' . urlencode($logged_in_teacher) . '&size=150&background=4f46e5&color=fff'; ?>" 
                                 class="profile-image-preview" id="imagePreview" alt="Profile Image">
                            <div class="mb-3">
                                <label for="image" class="form-label"><i class="fas fa-camera"></i> Change Photo</label>
                                <input type="file" class="form-control" id="image" name="image" accept="image/*" onchange="previewImage(event)">
                                <small class="text-muted">Max 5MB (JPG, PNG, GIF, WEBP)</small>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Profile Fields -->
                    <div class="col-md-9">
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label"><i class="fas fa-user"></i> Full Name</label>
                                <input type="text" class="form-control" name="name" value="<?php echo htmlspecialchars($logged_in_teacher); ?>" required>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label"><i class="fas fa-envelope"></i> Email</label>
                                <input type="email" class="form-control" value="<?php echo htmlspecialchars($teacher_email_db); ?>" readonly>
                                <small class="text-muted">Email cannot be changed</small>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label"><i class="fas fa-phone"></i> Phone</label>
                                <input type="text" class="form-control" name="phone" value="<?php echo htmlspecialchars($teacher_phone); ?>">
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label"><i class="fas fa-venus-mars"></i> Gender</label>
                                <select class="form-control" name="gender">
                                    <option value="Male" <?php echo $teacher_gender == 'Male' ? 'selected' : ''; ?>>Male</option>
                                    <option value="Female" <?php echo $teacher_gender == 'Female' ? 'selected' : ''; ?>>Female</option>
                                    <option value="Other" <?php echo $teacher_gender == 'Other' ? 'selected' : ''; ?>>Other</option>
                                </select>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label"><i class="fas fa-calendar"></i> Date of Birth</label>
                                <input type="date" class="form-control" name="dob" value="<?php echo htmlspecialchars($teacher_dob); ?>">
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label"><i class="fas fa-map-marker-alt"></i> Address</label>
                                <input type="text" class="form-control" name="address" value="<?php echo htmlspecialchars($teacher_address); ?>">
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label"><i class="fas fa-book"></i> Subject</label>
                                <input type="text" class="form-control" name="subject" value="<?php echo htmlspecialchars($teacher_subject); ?>">
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label"><i class="fas fa-graduation-cap"></i> Department</label>
                                <input type="text" class="form-control" name="department" value="<?php echo htmlspecialchars($teacher_department); ?>">
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Salary - Read Only -->
                <div class="row mt-3">
                    <div class="col-md-12">
                        <div class="salary-display">
                            <i class="fas fa-money-bill-wave"></i> 
                            <strong>Salary:</strong> $<?php echo number_format(floatval($teacher_salary), 2); ?>
                            <span class="text-muted ms-3"><i class="fas fa-lock"></i> (Cannot be changed)</span>
                        </div>
                    </div>
                </div>

                <!-- Submit Button -->
                <div class="row mt-4">
                    <div class="col-md-12">
                        <button type="submit" name="update_profile" class="btn-save">
                            <i class="fas fa-save"></i> Update Profile
                        </button>
                        <a href="forteacher.php" class="btn btn-secondary ms-2" style="border-radius:10px; padding:12px 30px;">
                            <i class="fas fa-arrow-left"></i> Back to Dashboard
                        </a>
                    </div>
                </div>
            </form>
        </div>

        <!-- Change Password Section -->
        <div class="settings-container mt-4">
            <h3><i class="fas fa-key"></i> Change Password</h3>
            
            <form method="POST">
                <div class="row">
                    <div class="col-md-4 mb-3">
                        <label class="form-label">Current Password</label>
                        <input type="password" class="form-control" name="current_password" required>
                    </div>
                    <div class="col-md-4 mb-3">
                        <label class="form-label">New Password</label>
                        <input type="password" class="form-control" name="new_password" required minlength="6">
                        <small class="text-muted">Minimum 6 characters</small>
                    </div>
                    <div class="col-md-4 mb-3">
                        <label class="form-label">Confirm Password</label>
                        <input type="password" class="form-control" name="confirm_password" required>
                    </div>
                </div>
                <button type="submit" name="change_password" class="btn-save" style="background: #ef4444;">
                    <i class="fas fa-key"></i> Change Password
                </button>
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

function previewImage(event) {
    const reader = new FileReader();
    reader.onload = function() {
        const output = document.getElementById('imagePreview');
        output.src = reader.result;
    }
    reader.readAsDataURL(event.target.files[0]);
}
</script>

</body>
</html>