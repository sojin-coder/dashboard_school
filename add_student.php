<?php
// add_student.php - បន្ថែមសិស្សថ្មីចូលក្នុងថ្នាក់រៀន
include "check_login.php";

$message = '';
$error = '';
$class_id = '';

// បង្កើតតារាង students ប្រសិនបើមិនទាន់មាន
function createStudentsTable($conn) {
    $create_table_sql = "CREATE TABLE IF NOT EXISTS students (
        id INT AUTO_INCREMENT PRIMARY KEY,
        class_id INT NOT NULL,
        student_id VARCHAR(50) UNIQUE NOT NULL,
        student_name VARCHAR(255) NOT NULL,
        email VARCHAR(255),
        phone VARCHAR(20),
        status ENUM('active', 'inactive') DEFAULT 'active',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        INDEX(class_id),
        INDEX(student_id),
        INDEX(status)
    )";
    
    return mysqli_query($conn, $create_table_sql);
}

// ពិនិត្យ និងបង្កើតតារាង
if (!createStudentsTable($conn)) {
    die("Error creating students table: " . mysqli_error($conn));
}

// ទាញយក class_id ពី URL ប្រសិនបើមាន
if(isset($_GET['class_id']) && is_numeric($_GET['class_id'])) {
    $class_id = $_GET['class_id'];
}

// ទទួលសារពី session
if(isset($_SESSION['message'])) {
    $message = $_SESSION['message'];
    unset($_SESSION['message']);
}
if(isset($_SESSION['error'])) {
    $error = $_SESSION['error'];
    unset($_SESSION['error']);
}

// ពិនិត្យមើលថាមានការបញ្ជូនទិន្នន័យមកឬអត់
if(isset($_POST['add_student'])) {
    
    // ទទួលទិន្នន័យពី form
    $class_id = mysqli_real_escape_string($conn, $_POST['class_id']);
    $student_id = mysqli_real_escape_string($conn, $_POST['student_id']);
    $student_name = mysqli_real_escape_string($conn, $_POST['student_name']);
    $email = mysqli_real_escape_string($conn, $_POST['email']);
    $phone = mysqli_real_escape_string($conn, $_POST['phone']);
    $status = mysqli_real_escape_string($conn, $_POST['status'] ?? 'active');
    
    // ពិនិត្យមើលថាបំពេញព័ត៌មានសំខាន់ៗគ្រប់ឬអត់
    if(empty($student_id) || empty($student_name)) {
        $error = "❌ Please fill in Student ID and Student Name!";
        $_SESSION['error'] = $error;
        header("Location: class.php?attendance=" . $class_id . "&error=empty_fields");
        exit();
    }
    
    // ពិនិត្យមើលថា class_id មានពិតប្រាកដ និងជារបស់គ្រូដែលកំពុង login
    $check_class_sql = "SELECT id, class_name FROM teacher_classes 
                        WHERE id = '$class_id' AND teacher_id = '$logged_in_id'";
    $check_class_result = mysqli_query($conn, $check_class_sql);
    
    if(!$check_class_result || mysqli_num_rows($check_class_result) == 0) {
        $error = "❌ Class not found or you don't have permission!";
        $_SESSION['error'] = $error;
        header("Location: class.php?error=class_not_found");
        exit();
    }
    
    $class_data = mysqli_fetch_assoc($check_class_result);
    $class_name = $class_data['class_name'];
    
    // ពិនិត្យមើលថាមាន student_id នេះរួចហើយឬនៅ
    $check_sql = "SELECT id, student_id FROM students WHERE student_id = '$student_id'";
    $check_result = mysqli_query($conn, $check_sql);
    
    if($check_result && mysqli_num_rows($check_result) > 0) {
        $error = "❌ Student ID '$student_id' already exists!";
        $_SESSION['error'] = $error;
        header("Location: class.php?attendance=" . $class_id . "&error=duplicate");
        exit();
    }
    
    // បញ្ចូលទិន្នន័យសិស្ស
    $insert_sql = "INSERT INTO students (class_id, student_id, student_name, email, phone, status) 
                   VALUES ('$class_id', '$student_id', '$student_name', '$email', '$phone', '$status')";
    
    if(mysqli_query($conn, $insert_sql)) {
        $message = "✅ Student '$student_name' added successfully to class '$class_name'!";
        $_SESSION['message'] = $message;
        header("Location: class.php?attendance=" . $class_id . "&success=student_added");
        exit();
    } else {
        $error = "❌ Error adding student: " . mysqli_error($conn);
        $_SESSION['error'] = $error;
        header("Location: class.php?attendance=" . $class_id . "&error=add_failed");
        exit();
    }
    
} else {
    // ប្រសិនបើគ្មានការបញ្ជូនទិន្នន័យមក បង្ហាញទម្រង់បន្ថែមសិស្ស
    // ទាញយកបញ្ជីថ្នាក់រៀនរបស់គ្រូ
    $classes_query = "SELECT id, class_name, subject FROM teacher_classes 
                      WHERE teacher_id = '$logged_in_id' AND status = 'active'
                      ORDER BY class_name ASC";
    $classes_result = mysqli_query($conn, $classes_query);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Add Student - Teacher</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        a { text-decoration: none; }
        body { font-family: "Inter", sans-serif; background: #f1f5f9; color: #0f172a; }
        
        .container {
            max-width: 600px;
            margin: 50px auto;
            padding: 0 20px;
        }
        
        .card {
            background: white;
            border-radius: 20px;
            padding: 40px;
            box-shadow: 0 10px 40px rgba(0,0,0,0.1);
        }
        
        .card-header {
            text-align: center;
            margin-bottom: 30px;
        }
        
        .card-header h2 {
            font-weight: 700;
            color: #1e293b;
        }
        
        .card-header p {
            color: #64748b;
            font-size: 0.95rem;
        }
        
        .form-control, .form-select {
            border-radius: 10px;
            padding: 12px 15px;
            border: 1px solid #e2e8f0;
            transition: 0.3s;
        }
        
        .form-control:focus, .form-select:focus {
            border-color: #4f46e5;
            box-shadow: 0 0 0 3px rgba(79, 70, 229, 0.1);
        }
        
        .btn-save {
            background: #4f46e5;
            color: white;
            border: none;
            padding: 12px 30px;
            border-radius: 10px;
            font-weight: 600;
            transition: 0.3s;
            width: 100%;
        }
        
        .btn-save:hover {
            background: #4338ca;
            color: white;
            transform: translateY(-2px);
        }
        
        .btn-back {
            background: #e2e8f0;
            color: #475569;
            border: none;
            padding: 10px 20px;
            border-radius: 10px;
            font-weight: 500;
            transition: 0.3s;
        }
        
        .btn-back:hover {
            background: #cbd5e1;
            color: #1e293b;
        }
        
        .alert-custom {
            border-radius: 10px;
            padding: 15px 20px;
        }
        
        .form-label {
            font-weight: 500;
            color: #334155;
        }
        
        .form-label .required {
            color: #ef4444;
        }
        
        .back-link {
            display: inline-block;
            margin-top: 20px;
            color: #4f46e5;
            font-weight: 500;
        }
        
        .back-link:hover {
            color: #4338ca;
        }
        
        .student-preview {
            background: #f8fafc;
            border-radius: 10px;
            padding: 15px;
            margin-top: 15px;
            display: none;
        }
        
        .student-preview.show {
            display: block;
        }
    </style>
</head>
<body>

<div class="container">
    <div class="card">
        <div class="card-header">
            <div class="mb-3">
                <i class="fas fa-user-plus" style="font-size: 48px; color: #4f46e5;"></i>
            </div>
            <h2>Add New Student</h2>
            <p>Add a student to your class</p>
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
        
        <form method="POST" id="addStudentForm">
            <!-- Class Selection -->
            <div class="mb-3">
                <label class="form-label">
                    <i class="fas fa-chalkboard"></i> Class <span class="required">*</span>
                </label>
                <?php if(!empty($class_id)): ?>
                    <?php
                    // ទាញយកឈ្មោះថ្នាក់
                    $class_name_sql = "SELECT class_name FROM teacher_classes WHERE id = '$class_id'";
                    $class_name_result = mysqli_query($conn, $class_name_sql);
                    $class_name_row = mysqli_fetch_assoc($class_name_result);
                    ?>
                    <input type="text" class="form-control" value="<?php echo htmlspecialchars($class_name_row['class_name'] ?? 'Class'); ?>" disabled>
                    <input type="hidden" name="class_id" value="<?php echo $class_id; ?>">
                <?php else: ?>
                    <select class="form-select" name="class_id" required>
                        <option value="">-- Select Class --</option>
                        <?php 
                        if($classes_result && mysqli_num_rows($classes_result) > 0):
                            while($class = mysqli_fetch_assoc($classes_result)):
                        ?>
                            <option value="<?php echo $class['id']; ?>">
                                <?php echo htmlspecialchars($class['class_name']); ?> 
                                (<?php echo htmlspecialchars($class['subject']); ?>)
                            </option>
                        <?php 
                            endwhile;
                        endif; 
                        ?>
                    </select>
                <?php endif; ?>
            </div>
            
            <!-- Student ID -->
            <div class="mb-3">
                <label class="form-label">
                    <i class="fas fa-id-card"></i> Student ID <span class="required">*</span>
                </label>
                <input type="text" class="form-control" name="student_id" 
                       placeholder="e.g. S001" required>
                <small class="text-muted">Must be unique for each student</small>
            </div>
            
            <!-- Student Name -->
            <div class="mb-3">
                <label class="form-label">
                    <i class="fas fa-user"></i> Student Name <span class="required">*</span>
                </label>
                <input type="text" class="form-control" name="student_name" 
                       placeholder="e.g. Sok Sopheap" required>
            </div>
            
            <!-- Email -->
            <div class="mb-3">
                <label class="form-label">
                    <i class="fas fa-envelope"></i> Email
                </label>
                <input type="email" class="form-control" name="email" 
                       placeholder="student@example.com">
            </div>
            
            <!-- Phone -->
            <div class="mb-3">
                <label class="form-label">
                    <i class="fas fa-phone"></i> Phone Number
                </label>
                <input type="text" class="form-control" name="phone" 
                       placeholder="e.g. 012345678">
            </div>
            
            <!-- Status -->
            <div class="mb-3">
                <label class="form-label">
                    <i class="fas fa-toggle-on"></i> Status
                </label>
                <select class="form-select" name="status">
                    <option value="active">Active</option>
                    <option value="inactive">Inactive</option>
                </select>
            </div>
            
            <!-- Student Preview -->
            <div id="studentPreview" class="student-preview">
                <h6 class="text-primary"><i class="fas fa-eye"></i> Preview</h6>
                <p id="previewName" class="mb-1"></p>
                <p id="previewID" class="mb-1 text-muted small"></p>
                <p id="previewEmail" class="text-muted small"></p>
            </div>
            
            <!-- Buttons -->
            <div class="d-flex gap-2 mt-4">
                <button type="submit" name="add_student" class="btn-save">
                    <i class="fas fa-plus"></i> Add Student
                </button>
            </div>
            
            <div class="text-center mt-3">
                <a href="class.php" class="back-link">
                    <i class="fas fa-arrow-left"></i> Back to Classes
                </a>
            </div>
        </form>
    </div>
</div>

<script>
// Preview student information before adding
document.getElementById('addStudentForm').addEventListener('input', function(e) {
    const studentId = document.querySelector('input[name="student_id"]').value;
    const studentName = document.querySelector('input[name="student_name"]').value;
    const email = document.querySelector('input[name="email"]').value;
    const preview = document.getElementById('studentPreview');
    
    if(studentId || studentName) {
        preview.classList.add('show');
        document.getElementById('previewID').textContent = 'ID: ' + (studentId || 'N/A');
        document.getElementById('previewName').textContent = 'Name: ' + (studentName || 'N/A');
        document.getElementById('previewEmail').textContent = 'Email: ' + (email || 'N/A');
    } else {
        preview.classList.remove('show');
    }
});

// Validation
document.querySelector('form').addEventListener('submit', function(e) {
    const studentId = document.querySelector('input[name="student_id"]').value.trim();
    const studentName = document.querySelector('input[name="student_name"]').value.trim();
    const classSelect = document.querySelector('select[name="class_id"]');
    const classHidden = document.querySelector('input[name="class_id"]');
    const classId = classSelect ? classSelect.value : (classHidden ? classHidden.value : '');
    
    if(!classId) {
        e.preventDefault();
        alert('Please select a class first!');
        return false;
    }
    
    if(!studentId || !studentName) {
        e.preventDefault();
        alert('Please fill in Student ID and Student Name!');
        return false;
    }
    
    // Check if student ID contains only allowed characters
    const allowedChars = /^[a-zA-Z0-9\-_]+$/;
    if(!allowedChars.test(studentId)) {
        e.preventDefault();
        alert('Student ID can only contain letters, numbers, dash (-), and underscore (_)');
        return false;
    }
    
    // Check student ID length
    if(studentId.length > 50) {
        e.preventDefault();
        alert('Student ID must be less than 50 characters');
        return false;
    }
    
    return true;
});
</script>

</body>
</html>
<?php
} // បិទ else
?>