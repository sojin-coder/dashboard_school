<?php
        
        include "check_login.php";
        
        $message = '';
        $error = '';
        
        // ========== CLASS CRUD ==========
        if(isset($_POST['add_class'])) {
            $class_name = mysqli_real_escape_string($conn, $_POST['class_name']);
            $department = mysqli_real_escape_string($conn, $_POST['department']);
            $room = mysqli_real_escape_string($conn, $_POST['room']);
            $schedule_day = mysqli_real_escape_string($conn, $_POST['schedule_day']);
            $start_time = mysqli_real_escape_string($conn, $_POST['start_time']);
            $end_time = mysqli_real_escape_string($conn, $_POST['end_time']);
            $semester = mysqli_real_escape_string($conn, $_POST['semester']);
            $academic_year = mysqli_real_escape_string($conn, $_POST['academic_year']);
            $status = mysqli_real_escape_string($conn, $_POST['status']);
            $class_year = mysqli_real_escape_string($conn, $_POST['class_year']);
            $shift = mysqli_real_escape_string($conn, $_POST['shift']);
            $date = mysqli_real_escape_string($conn, $_POST['date']);
            
            if(empty($class_name) || empty($department)) {
                $error = "Please fill in Class Name and Department!";
            } else {
                $insert_sql = "INSERT INTO teacher_classes (
                    teacher_id, teacher_name, class_name, subject, room,
                    schedule_day, start_time, end_time, semester, academic_year, status, year, shift, date
                ) VALUES (
                    '$logged_in_id', '$logged_in_teacher', '$class_name', '$department', '$room',
                    " . ($schedule_day ? "'$schedule_day'" : "NULL") . ",
                    " . ($start_time ? "'$start_time'" : "NULL") . ",
                    " . ($end_time ? "'$end_time'" : "NULL") . ",
                    '$semester', '$academic_year', '$status', '$class_year', '$shift',
                    " . ($date ? "'$date'" : "NULL") . "
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
            $department = mysqli_real_escape_string($conn, $_POST['department']);
            $room = mysqli_real_escape_string($conn, $_POST['room']);
            $schedule_day = mysqli_real_escape_string($conn, $_POST['schedule_day']);
            $start_time = mysqli_real_escape_string($conn, $_POST['start_time']);
            $end_time = mysqli_real_escape_string($conn, $_POST['end_time']);
            $semester = mysqli_real_escape_string($conn, $_POST['semester']);
            $academic_year = mysqli_real_escape_string($conn, $_POST['academic_year']);
            $status = mysqli_real_escape_string($conn, $_POST['status']);
            $class_year = mysqli_real_escape_string($conn, $_POST['class_year']);
            $shift = mysqli_real_escape_string($conn, $_POST['shift']);
            $date = mysqli_real_escape_string($conn, $_POST['date']);
            
            if(empty($class_name) || empty($department)) {
                $error = "Please fill in Class Name and Department!";
            } else {
                $update_sql = "UPDATE teacher_classes SET 
                    class_name = '$class_name',
                    subject = '$department',
                    room = '$room',
                    schedule_day = " . ($schedule_day ? "'$schedule_day'" : "NULL") . ",
                    start_time = " . ($start_time ? "'$start_time'" : "NULL") . ",
                    end_time = " . ($end_time ? "'$end_time'" : "NULL") . ",
                    semester = '$semester',
                    academic_year = '$academic_year',
                    status = '$status',
                    year = '$class_year',
                    shift = '$shift',
                    date = " . ($date ? "'$date'" : "NULL") . "
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
        
        // ========== ATTENDANCE FUNCTIONS ==========
        if(isset($_POST['save_attendance'])) {
            $class_id = mysqli_real_escape_string($conn, $_POST['class_id']);
            $attendance_date = mysqli_real_escape_string($conn, $_POST['attendance_date']);
            
            $check_sql = "SELECT id FROM attendance_student WHERE class_id = '$class_id' AND attendance_date = '$attendance_date'";
            $check_result = mysqli_query($conn, $check_sql);
            
            if($check_result && mysqli_num_rows($check_result) > 0) {
                foreach($_POST['student_status'] as $student_id => $status) {
                    $status = mysqli_real_escape_string($conn, $status);
                    $student_name = mysqli_real_escape_string($conn, $_POST['student_name'][$student_id]);
                    $update_sql = "UPDATE attendance_student SET 
                        status = '$status',
                        student_name = '$student_name',
                        updated_at = CURRENT_TIMESTAMP
                        WHERE class_id = '$class_id' 
                        AND student_id = '$student_id' 
                        AND attendance_date = '$attendance_date'";
                    mysqli_query($conn, $update_sql);
                }
                $message = "✅ Attendance updated successfully!";
            } else {
                foreach($_POST['student_status'] as $student_id => $status) {
                    $status = mysqli_real_escape_string($conn, $status);
                    $student_name = mysqli_real_escape_string($conn, $_POST['student_name'][$student_id]);
                    $insert_sql = "INSERT INTO attendance_student (class_id, student_id, student_name, attendance_date, status) 
                                   VALUES ('$class_id', '$student_id', '$student_name', '$attendance_date', '$status')";
                    mysqli_query($conn, $insert_sql);
                }
                $message = "✅ Attendance saved successfully!";
            }
            
            header("Location: class.php?attendance=$class_id&success=attendance_saved");
            exit();
        }
        
        if(isset($_GET['delete_attendance']) && is_numeric($_GET['delete_attendance'])) {
            $attendance_id = $_GET['delete_attendance'];
            $delete_sql = "DELETE FROM attendance_student WHERE id = '$attendance_id'";
            if(mysqli_query($conn, $delete_sql)) {
                header("Location: class.php?success=attendance_deleted");
                exit();
            }
        }
        
        // ========== GRADE MANAGEMENT FUNCTIONS ==========
        
        // បង្កើតតារាងប្រសិនបើមិនទាន់មាន
        $create_table_sql = "
        CREATE TABLE IF NOT EXISTS student_grades (
            id INT(11) PRIMARY KEY AUTO_INCREMENT,
            class_id INT(11) NOT NULL,
            student_id INT(11) NOT NULL,
            student_name VARCHAR(100) NOT NULL,
            subject VARCHAR(100) NOT NULL,
            academic_year VARCHAR(20),
            semester VARCHAR(20),
            assignment_score DECIMAL(5,2) DEFAULT 0,
            midterm_score DECIMAL(5,2) DEFAULT 0,
            final_score DECIMAL(5,2) DEFAULT 0,
            project_score DECIMAL(5,2) DEFAULT 0,
            quiz_score DECIMAL(5,2) DEFAULT 0,
            attendance_score DECIMAL(5,2) DEFAULT 0,
            total_score DECIMAL(5,2) DEFAULT 0,
            grade VARCHAR(2) DEFAULT NULL,
            remark TEXT,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            UNIQUE KEY unique_grade (class_id, student_id, subject, semester)
        )";
        mysqli_query($conn, $create_table_sql);
        
        // ដាក់ពិន្ទុសិស្ស
        if(isset($_POST['save_grades'])) {
            $class_id = mysqli_real_escape_string($conn, $_POST['class_id']);
            $subject = mysqli_real_escape_string($conn, $_POST['subject']);
            $semester = mysqli_real_escape_string($conn, $_POST['semester']);
            $academic_year = mysqli_real_escape_string($conn, $_POST['academic_year']);
            
            foreach($_POST['student_id'] as $student_id) {
                $student_name = mysqli_real_escape_string($conn, $_POST['student_name'][$student_id]);
                $assignment_score = isset($_POST['assignment_score'][$student_id]) ? floatval($_POST['assignment_score'][$student_id]) : 0;
                $midterm_score = isset($_POST['midterm_score'][$student_id]) ? floatval($_POST['midterm_score'][$student_id]) : 0;
                $final_score = isset($_POST['final_score'][$student_id]) ? floatval($_POST['final_score'][$student_id]) : 0;
                $project_score = isset($_POST['project_score'][$student_id]) ? floatval($_POST['project_score'][$student_id]) : 0;
                $quiz_score = isset($_POST['quiz_score'][$student_id]) ? floatval($_POST['quiz_score'][$student_id]) : 0;
                $attendance_score = isset($_POST['attendance_score'][$student_id]) ? floatval($_POST['attendance_score'][$student_id]) : 0;
                
                // គណនាពិន្ទុសរុប
                $total_score = ($assignment_score * 0.2) + ($midterm_score * 0.3) + ($final_score * 0.3) + ($project_score * 0.1) + ($quiz_score * 0.05) + ($attendance_score * 0.05);
                $total_score = round($total_score, 2);
                
                // កំណត់ថ្នាក់
                if($total_score >= 90) {
                    $grade = 'A';
                } elseif($total_score >= 80) {
                    $grade = 'B';
                } elseif($total_score >= 70) {
                    $grade = 'C';
                } elseif($total_score >= 60) {
                    $grade = 'D';
                } else {
                    $grade = 'F';
                }
                
                // ពិនិត្យមើលថាមានពិន្ទុរួចហើយឬនៅ
                $check_sql = "SELECT id FROM student_grades 
                              WHERE class_id = '$class_id' 
                              AND student_id = '$student_id' 
                              AND subject = '$subject' 
                              AND semester = '$semester'";
                $check_result = mysqli_query($conn, $check_sql);
                
                if($check_result && mysqli_num_rows($check_result) > 0) {
                    // កែប្រែពិន្ទុ
                    $update_sql = "UPDATE student_grades SET 
                        student_name = '$student_name',
                        assignment_score = '$assignment_score',
                        midterm_score = '$midterm_score',
                        final_score = '$final_score',
                        project_score = '$project_score',
                        quiz_score = '$quiz_score',
                        attendance_score = '$attendance_score',
                        total_score = '$total_score',
                        grade = '$grade'
                        WHERE class_id = '$class_id' 
                        AND student_id = '$student_id' 
                        AND subject = '$subject' 
                        AND semester = '$semester'";
                    mysqli_query($conn, $update_sql);
                } else {
                    // បញ្ចូលពិន្ទុថ្មី
                    $insert_sql = "INSERT INTO student_grades (
                        class_id, student_id, student_name, subject, 
                        academic_year, semester,
                        assignment_score, midterm_score, final_score, 
                        project_score, quiz_score, attendance_score,
                        total_score, grade
                    ) VALUES (
                        '$class_id', '$student_id', '$student_name', '$subject',
                        '$academic_year', '$semester',
                        '$assignment_score', '$midterm_score', '$final_score',
                        '$project_score', '$quiz_score', '$attendance_score',
                        '$total_score', '$grade'
                    )";
                    mysqli_query($conn, $insert_sql);
                }
            }
            
            header("Location: class.php?grades=$class_id&success=grades_saved");
            exit();
        }
        
        // ទាញយកពិន្ទុសិស្ស
        function getStudentGrades($class_id, $student_id = null) {
            global $conn;
            $sql = "SELECT * FROM student_grades WHERE class_id = '$class_id'";
            if($student_id) {
                $sql .= " AND student_id = '$student_id'";
            }
            $sql .= " ORDER BY student_name ASC";
            $result = mysqli_query($conn, $sql);
            
            if(!$result) {
                return array();
            }
            
            $grades = array();
            while($row = mysqli_fetch_assoc($result)) {
                $grades[$row['student_id']] = $row;
            }
            return $grades;
        }
        
        // ========== GET STUDENTS FOR CLASS ==========
        function getStudentsForClass($class_id) {
            global $conn, $teacher_department;
            
            $class_sql = "SELECT subject, year, shift FROM teacher_classes WHERE id = '$class_id'";
            $class_result = mysqli_query($conn, $class_sql);
            if(!$class_result) {
                return array();
            }
            $class_info = mysqli_fetch_assoc($class_result);
            
            if($class_info && !empty($class_info['subject'])) {
                $department = $class_info['subject'];
                $class_year = $class_info['year'] ?? '';
                $class_shift = $class_info['shift'] ?? '';
            } else {
                $department = $teacher_department;
                $class_year = '';
                $class_shift = '';
            }
            
            $sql = "SELECT * FROM students WHERE 1=1";
            
            if(!empty($department)) {
                $sql .= " AND college = '$department'";
            }
            
            if(!empty($class_year)) {
                $year_map = ['1' => 'year 1', '2' => 'year 2', '3' => 'year 3', '4' => 'year 4'];
                $year_value = isset($year_map[$class_year]) ? $year_map[$class_year] : $class_year;
                $sql .= " AND year = '$year_value'";
            }
            
            if(!empty($class_shift)) {
                $sql .= " AND Shift = '$class_shift'";
            }
            
            $sql .= " ORDER BY name ASC";
            
            $result = mysqli_query($conn, $sql);
            
            if(!$result) {
                return array();
            }
            
            if(mysqli_num_rows($result) == 0) {
                return array();
            }
            
            $students = array();
            while($row = mysqli_fetch_assoc($result)) {
                $students[] = $row;
            }
            return $students;
        }
        
        function getAttendanceByDate($class_id, $date) {
            global $conn;
            $sql = "SELECT * FROM attendance_student WHERE class_id = '$class_id' AND attendance_date = '$date'";
            $result = mysqli_query($conn, $sql);
            
            if(!$result) {
                return array();
            }
            
            $attendance = array();
            while($row = mysqli_fetch_assoc($result)) {
                $attendance[$row['student_id']] = $row;
            }
            return $attendance;
        }
        
        function getUniqueColleges() {
            global $conn;
            
            $sql = "SELECT DISTINCT college FROM students WHERE college IS NOT NULL AND college != '' ORDER BY college";
            $result = mysqli_query($conn, $sql);
            
            if(!$result) {
                return array();
            }
            
            $colleges = array();
            while($row = mysqli_fetch_assoc($result)) {
                $colleges[] = $row['college'];
            }
            return $colleges;
        }
        
        // ========== CLASS QUERIES ==========
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
        
        // ========== ATTENDANCE VIEW ==========
        $show_attendance = false;
        $selected_class_id = null;
        $attendance_date = date('Y-m-d');
        $students_list = array();
        $attendance_data = array();
        $class_info = null;
        $selected_department = '';
        $selected_year = '';
        $selected_shift = '';
        
        if(isset($_GET['attendance']) && is_numeric($_GET['attendance'])) {
            $selected_class_id = $_GET['attendance'];
            $show_attendance = true;
            
            $check_class = "SELECT * FROM teacher_classes WHERE id = '$selected_class_id' AND teacher_id = '$logged_in_id'";
            $check_result = mysqli_query($conn, $check_class);
            $class_info = mysqli_fetch_assoc($check_result);
            
            if($class_info) {
                $selected_department = $class_info['subject'];
                $selected_year = $class_info['year'] ?? '';
                $selected_shift = $class_info['shift'] ?? '';
                $students_list = getStudentsForClass($selected_class_id);
                
                if(isset($_GET['date'])) {
                    $attendance_date = mysqli_real_escape_string($conn, $_GET['date']);
                }
                $attendance_data = getAttendanceByDate($selected_class_id, $attendance_date);
            } else {
                $error = "You don't have permission to view this class.";
                $show_attendance = false;
            }
        }
        
        // ========== GRADE VIEW ==========
        $show_grades = false;
        $selected_grade_class_id = null;
        $grade_students_list = array();
        $grade_data = array();
        $grade_class_info = null;
        
        if(isset($_GET['grades']) && is_numeric($_GET['grades'])) {
            $selected_grade_class_id = $_GET['grades'];
            $show_grades = true;
            
            $check_class = "SELECT * FROM teacher_classes WHERE id = '$selected_grade_class_id' AND teacher_id = '$logged_in_id'";
            $check_result = mysqli_query($conn, $check_class);
            $grade_class_info = mysqli_fetch_assoc($check_result);
            
            if($grade_class_info) {
                $grade_students_list = getStudentsForClass($selected_grade_class_id);
                $grade_data = getStudentGrades($selected_grade_class_id);
            } else {
                $error = "You don't have permission to view this class.";
                $show_grades = false;
            }
        }
        
        // ========== SUCCESS MESSAGES ==========
        if(isset($_GET['success'])) {
            switch($_GET['success']) {
                case 'added':
                    $message = "✅ Class added successfully!";
                    break;
                case 'updated':
                    $message = "✅ Class updated successfully!";
                    break;
                case 'deleted':
                    $message = "✅ Class deleted successfully!";
                    break;
                case 'toggled':
                    $message = "✅ Class status updated!";
                    break;
                case 'attendance_deleted':
                    $message = "✅ Attendance record deleted!";
                    break;
                case 'student_added':
                    $message = "✅ Student added successfully!";
                    break;
                case 'attendance_saved':
                    $message = "✅ Attendance saved successfully!";
                    break;
                case 'grades_saved':
                    $message = "✅ Grades saved successfully!";
                    break;
            }
        }
        
        $college_list = getUniqueColleges();
        
        $year_options = [
            '1' => 'year 1',
            '2' => 'year 2', 
            '3' => 'year 3',
            '4' => 'year 4'
        ];
        
        $shift_options = ['morning' => 'Morning', 'evening' => 'Evening'];
        
        // កំណត់ស្ថានភាពថ្នាក់សម្រាប់បង្ហាញ
        $status_badge_class = [
            'active' => 'badge-active',
            'inactive' => 'badge-inactive',
            'completed' => 'badge-completed'
        ];
        
        $grade_badge_class = [
            'A' => 'badge-success',
            'B' => 'badge-primary',
            'C' => 'badge-info',
            'D' => 'badge-warning',
            'F' => 'badge-danger'
        ];
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
        
        .badge-present { background: #dcfce7; color: #166534; }
        .badge-absent { background: #fee2e2; color: #991b1b; }
        .badge-late { background: #fef3c7; color: #92400e; }
        .badge-excused { background: #e0e7ff; color: #3730a3; }
        
        .badge-success { background: #dcfce7; color: #166534; }
        .badge-primary { background: #dbeafe; color: #1e40af; }
        .badge-info { background: #cffafe; color: #0e7490; }
        .badge-warning { background: #fef3c7; color: #92400e; }
        .badge-danger { background: #fee2e2; color: #991b1b; }
        
        .badge-morning { background: #fef3c7; color: #92400e; }
        .badge-evening { background: #e0e7ff; color: #3730a3; }
        
        .alert-custom { border-radius: 10px; padding: 15px 20px; }
        
        .attendance-options .btn-group .btn-check:focus + .btn {
            box-shadow: none !important;
        }

        .attendance-options .btn-group .btn {
            padding: 6px 16px;
            font-size: 0.8rem;
            border-width: 2px;
            border-radius: 8px;
            font-weight: 500;
            transition: all 0.3s ease;
            min-width: 80px;
            text-align: center;
        }

        .attendance-options .btn-group .btn-att-present {
            background-color: #ffffff;
            color: #22c55e;
            border: 2px solid #22c55e;
        }
        .attendance-options .btn-group .btn-att-absent {
            background-color: #ffffff;
            color: #ef4444;
            border: 2px solid #ef4444;
        }
        .attendance-options .btn-group .btn-att-late {
            background-color: #ffffff;
            color: #f59e0b;
            border: 2px solid #f59e0b;
        }

        .attendance-options .btn-group .btn-att-present:hover {
            background-color: #22c55e;
            color: #ffffff;
            box-shadow: 0 4px 12px rgba(34, 197, 94, 0.3);
        }
        .attendance-options .btn-group .btn-att-absent:hover {
            background-color: #ef4444;
            color: #ffffff;
            box-shadow: 0 4px 12px rgba(239, 68, 68, 0.3);
        }
        .attendance-options .btn-group .btn-att-late:hover {
            background-color: #f59e0b;
            color: #ffffff;
            box-shadow: 0 4px 12px rgba(245, 158, 11, 0.3);
        }

        .attendance-options .btn-group .btn-check:checked + .btn-att-present {
            background-color: #22c55e !important;
            color: #ffffff !important;
            border-color: #16a34a !important;
            box-shadow: 0 4px 12px rgba(34, 197, 94, 0.4) !important;
            transform: scale(1.05);
        }
        .attendance-options .btn-group .btn-check:checked + .btn-att-absent {
            background-color: #ef4444 !important;
            color: #ffffff !important;
            border-color: #dc2626 !important;
            box-shadow: 0 4px 12px rgba(239, 68, 68, 0.4) !important;
            transform: scale(1.05);
        }
        .attendance-options .btn-group .btn-check:checked + .btn-att-late {
            background-color: #f59e0b !important;
            color: #ffffff !important;
            border-color: #d97706 !important;
            box-shadow: 0 4px 12px rgba(245, 158, 11, 0.4) !important;
            transform: scale(1.05);
        }

        .attendance-options .btn-group .btn i {
            margin-right: 4px;
        }

        @keyframes pulse {
            0% { transform: scale(1); }
            50% { transform: scale(1.1); }
            100% { transform: scale(1.05); }
        }
        .attendance-options .btn-group .btn-check:checked + .btn-att-present,
        .attendance-options .btn-group .btn-check:checked + .btn-att-absent,
        .attendance-options .btn-group .btn-check:checked + .btn-att-late {
            animation: pulse 0.3s ease;
        }
        .attendance-options .btn-group .btn:active {
            transform: scale(0.95) !important;
        }

        @media (max-width: 768px) {
            .attendance-options .btn-group .btn {
                padding: 4px 10px;
                font-size: 0.7rem;
                min-width: 60px;
            }
            .attendance-options .btn-group {
                gap: 4px !important;
            }
        }
        
        .class-card {
            border: 1px solid #e2e8f0;
            border-radius: 16px;
            padding: 20px;
            transition: 0.3s;
            background: white;
            height: 100%;
        }
        .class-card:hover {
            transform: translateY(-4px);
            box-shadow: 0 10px 30px rgba(0,0,0,0.1);
        }
        
        .dept-badge {
            display: inline-block;
            background: #e0e7ff;
            color: #3730a3;
            padding: 2px 12px;
            border-radius: 20px;
            font-size: 0.75rem;
            font-weight: 600;
        }
        
        .year-badge {
            display: inline-block;
            background: #fef3c7;
            color: #92400e;
            padding: 2px 12px;
            border-radius: 20px;
            font-size: 0.75rem;
            font-weight: 600;
        }
        
        .shift-badge {
            display: inline-block;
            padding: 2px 12px;
            border-radius: 20px;
            font-size: 0.75rem;
            font-weight: 600;
        }
        .shift-badge.morning {
            background: #fef3c7;
            color: #92400e;
        }
        .shift-badge.evening {
            background: #e0e7ff;
            color: #3730a3;
        }
        
        .student-count-badge {
            display: inline-block;
            background: #f1f5f9;
            color: #475569;
            padding: 2px 10px;
            border-radius: 20px;
            font-size: 0.7rem;
        }
        
        .grade-input {
            width: 70px !important;
            display: inline-block !important;
        }
        
        .summary-box {
            padding: 15px;
            border-radius: 10px;
            text-align: center;
            background: #f8fafc;
            border: 1px solid #e2e8f0;
        }
        .summary-box h3 {
            font-size: 2rem;
            font-weight: 700;
            margin: 0;
        }
        .summary-box p {
            margin: 0;
            color: #64748b;
            font-size: 0.85rem;
        }
        .summary-box .grade-A { color: #16a34a; }
        .summary-box .grade-B { color: #2563eb; }
        .summary-box .grade-C { color: #0891b2; }
        .summary-box .grade-D { color: #d97706; }
        .summary-box .grade-F { color: #dc2626; }
        
        @media (max-width: 768px) {
            .sidebar { width: 80px; }
            .sidebar-header h1, .sidebar-header p, .nav-item span { display: none; }
            .nav-item { justify-content: center; }
            .main-content { padding: 15px; }
            .sidebar-header .profile-img { width: 50px; height: 50px; }
            .sidebar-header .teacher-name { display: none; }
            .sidebar-header .teacher-dept { display: none; }
            .sidebar-header .teacher-subject { display: none; }
            .grade-input { width: 50px !important; }
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
            <a href="class.php" class="nav-item active"><i class="fas fa-chalkboard-teacher"></i> <span>Class</span></a>
            <a href="Request.php" class="nav-item"><i class="fas fa-file-signature"></i> <span>Request</span></a>
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

        <?php if($show_attendance): ?>
            <!-- ========== ATTENDANCE SECTION ========== -->
            <div class="content-box">
                <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-3">
                    <h4>
                        <i class="fas fa-clipboard-check"></i> 
                        Take Attendance
                        <span class="text-muted fs-6">(<?php echo htmlspecialchars($class_info['class_name'] ?? 'Class'); ?>)</span>
                    </h4>
                    <div>
                        <span class="dept-badge">
                            <i class="fas fa-building"></i> <?php echo htmlspecialchars($selected_department); ?>
                        </span>
                        <?php if(!empty($selected_year)): 
                            $year_display = isset($year_options[$selected_year]) ? $year_options[$selected_year] : $selected_year;
                        ?>
                        <span class="year-badge">
                            <i class="fas fa-graduation-cap"></i> <?php echo ucfirst($year_display); ?>
                        </span>
                        <?php endif; ?>
                        <?php if(!empty($selected_shift)): ?>
                        <span class="shift-badge <?php echo $selected_shift; ?>">
                            <i class="fas fa-clock"></i> <?php echo ucfirst($selected_shift); ?>
                        </span>
                        <?php endif; ?>
                        <span class="student-count-badge ms-2">
                            <i class="fas fa-users"></i> <?php echo count($students_list); ?> Students
                        </span>
                        <a href="class.php" class="btn btn-secondary btn-sm ms-2">
                            <i class="fas fa-arrow-left"></i> Back
                        </a>
                    </div>
                </div>
                
                <div class="alert alert-info alert-custom py-2 mb-3">
                    <i class="fas fa-info-circle"></i> 
                    Showing students from <strong>College: <?php echo htmlspecialchars($selected_department); ?></strong>
                    <?php if(!empty($selected_year)): 
                        $year_display = isset($year_options[$selected_year]) ? $year_options[$selected_year] : $selected_year;
                    ?>
                        for <strong><?php echo ucfirst($year_display); ?></strong>
                    <?php endif; ?>
                    <?php if(!empty($selected_shift)): ?>
                        in <strong><?php echo ucfirst($selected_shift); ?> Shift</strong>
                    <?php endif; ?>
                    <?php if(!empty($class_info['class_name'])): ?>
                        in class <strong><?php echo htmlspecialchars($class_info['class_name']); ?></strong>
                    <?php endif; ?>
                </div>
                
                <hr>
                
                <form method="POST">
                    <input type="hidden" name="class_id" value="<?php echo $selected_class_id; ?>">
                    
                    <div class="row mb-4">
                        <div class="col-md-6">
                            <label class="form-label"><i class="fas fa-calendar"></i> Date</label>
                            <input type="date" class="form-control" name="attendance_date" 
                                   value="<?php echo $attendance_date; ?>" required>
                        </div>
                        <div class="col-md-6 d-flex align-items-end">
                            <button type="submit" name="save_attendance" class="btn-save w-100">
                                <i class="fas fa-save"></i> Save All Attendance
                            </button>
                        </div>
                    </div>
                    
                    <?php if(empty($students_list)): ?>
                        <div class="alert alert-warning">
                            <i class="fas fa-exclamation-triangle"></i> 
                            No students found for <strong>College: <?php echo htmlspecialchars($selected_department); ?></strong>
                            <?php if(!empty($selected_year)): 
                                $year_display = isset($year_options[$selected_year]) ? $year_options[$selected_year] : $selected_year;
                            ?>
                                and <strong><?php echo ucfirst($year_display); ?></strong>
                            <?php endif; ?>
                            <?php if(!empty($selected_shift)): ?>
                                and <strong><?php echo ucfirst($selected_shift); ?> Shift</strong>
                            <?php endif; ?>
                            <hr>
                            <p class="mb-0"><strong>Available Colleges in Students Table:</strong></p>
                            <div class="department-list">
                                <?php
                                if(!empty($college_list)) {
                                    foreach($college_list as $college) {
                                        $is_match = strtolower($college) == strtolower($selected_department);
                                        $style = $is_match ? 'style="background: #22c55e; color: white;"' : '';
                                        echo '<span class="dept-tag" ' . $style . '>' . htmlspecialchars($college) . '</span>';
                                    }
                                } else {
                                    echo '<span class="text-muted">No colleges found in students table</span>';
                                }
                                ?>
                            </div>
                            <hr>
                            <p class="mb-0"><strong>Solutions:</strong></p>
                            <ul>
                                <li>Make sure the department name matches exactly with the college name in students table</li>
                                <li>Make sure students have the correct year assigned (year 1, year 2, year 3, year 4)</li>
                                <li>Make sure students have the correct shift (morning/evening)</li>
                                <li>Add students with college = '<?php echo htmlspecialchars($selected_department); ?>' and year = '<?php echo isset($year_options[$selected_year]) ? $year_options[$selected_year] : $selected_year; ?>' and shift = '<?php echo htmlspecialchars($selected_shift); ?>'</li>
                                <li>Or change the class department/year/shift to match existing students</li>
                            </ul>
                            <hr>
                            <a href="?attendance=<?php echo $selected_class_id; ?>" class="btn btn-sm btn-primary">
                                <i class="fas fa-sync"></i> Refresh
                            </a>
                            <button type="button" class="btn btn-sm btn-success" data-bs-toggle="modal" data-bs-target="#addStudentModal">
                                <i class="fas fa-user-plus"></i> Add Student
                            </button>
                        </div>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th>#</th>
                                        <th>Student Name</th>
                                        <th>Code</th>
                                        <th>Email</th>
                                        <th>College</th>
                                        <th>Year</th>
                                        <th>Shift</th>
                                        <th>Status</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php 
                                    $count = 1;
                                    foreach($students_list as $student):
                                        $existing = isset($attendance_data[$student['id']]) ? $attendance_data[$student['id']] : null;
                                        $status = $existing ? $existing['status'] : 'present';
                                        $student_year = isset($student['year']) ? $student['year'] : '-';
                                        $student_shift = isset($student['Shift']) ? $student['Shift'] : '-';
                                    ?>
                                    <tr>
                                        <td><?php echo $count++; ?></td>
                                        <td>
                                            <?php echo htmlspecialchars($student['name']); ?>
                                            <input type="hidden" name="student_name[<?php echo $student['id']; ?>]" 
                                                   value="<?php echo htmlspecialchars($student['name']); ?>">
                                        </td>
                                        <td><?php echo htmlspecialchars($student['student_code'] ?? 'N/A'); ?></td>
                                        <td><?php echo htmlspecialchars($student['email'] ?? '-'); ?></td>
                                        <td>
                                            <span class="dept-badge">
                                                <?php echo htmlspecialchars($student['college'] ?? '-'); ?>
                                            </span>
                                        </td>
                                        <td>
                                            <span class="year-badge">
                                                <?php echo htmlspecialchars($student_year); ?>
                                            </span>
                                        </td>
                                        <td>
                                            <span class="shift-badge <?php echo strtolower($student_shift); ?>">
                                                <?php echo htmlspecialchars($student_shift); ?>
                                            </span>
                                        </td>
                                        <td>
                                            <div class="attendance-options">
                                                <div class="btn-group btn-group-sm gap-2" role="group">
                                                    <input type="radio" class="btn-check" 
                                                           name="student_status[<?php echo $student['id']; ?>]" 
                                                           id="present_<?php echo $student['id']; ?>" 
                                                           value="present" <?php echo ($status == 'present') ? 'checked' : ''; ?>>
                                                    <label class="btn btn-att-present" 
                                                           for="present_<?php echo $student['id']; ?>">
                                                        <i class="fas fa-check"></i> Present
                                                    </label>
                                                    
                                                    <input type="radio" class="btn-check" 
                                                           name="student_status[<?php echo $student['id']; ?>]" 
                                                           id="absent_<?php echo $student['id']; ?>" 
                                                           value="absent" <?php echo ($status == 'absent') ? 'checked' : ''; ?>>
                                                    <label class="btn btn-att-absent" 
                                                           for="absent_<?php echo $student['id']; ?>">
                                                        <i class="fas fa-times"></i> Absent
                                                    </label>
                                                    
                                                    <input type="radio" class="btn-check" 
                                                           name="student_status[<?php echo $student['id']; ?>]" 
                                                           id="late_<?php echo $student['id']; ?>" 
                                                           value="late" <?php echo ($status == 'late') ? 'checked' : ''; ?>>
                                                    <label class="btn btn-att-late" 
                                                           for="late_<?php echo $student['id']; ?>">
                                                        <i class="fas fa-clock"></i> Late
                                                    </label>
                                                </div>
                                            </div>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                        
                        <div class="mt-3 d-flex justify-content-between align-items-center">
                            <span class="text-muted small">
                                <i class="fas fa-info-circle"></i> 
                                Showing <?php echo count($students_list); ?> students from <strong>College: <?php echo htmlspecialchars($selected_department); ?></strong>
                                <?php if(!empty($selected_year)): 
                                    $year_display = isset($year_options[$selected_year]) ? $year_options[$selected_year] : $selected_year;
                                ?>
                                    for <strong><?php echo ucfirst($year_display); ?></strong>
                                <?php endif; ?>
                                <?php if(!empty($selected_shift)): ?>
                                    in <strong><?php echo ucfirst($selected_shift); ?> Shift</strong>
                                <?php endif; ?>
                            </span>
                            <button type="submit" name="save_attendance" class="btn-save">
                                <i class="fas fa-save"></i> Save All Attendance
                            </button>
                        </div>
                    <?php endif; ?>
                </form>
            </div>
            
            <!-- ========== ATTENDANCE HISTORY ========== -->
            <div class="content-box">
                <h4><i class="fas fa-history"></i> Attendance History</h4>
                <hr>
                
                <?php
                // ========== កែប្រែ Query ដើម្បីយក class_name ពី teacher_classes ==========
                $history_sql = "SELECT 
                    a.attendance_date,
                    a.class_id,
                    tc.class_name,
                    tc.subject,
                    tc.shift,
                    tc.room,
                    tc.schedule_day,
                    tc.start_time,
                    tc.end_time,
                    COUNT(CASE WHEN a.status = 'present' THEN 1 END) as present_count,
                    COUNT(CASE WHEN a.status = 'absent' THEN 1 END) as absent_count,
                    COUNT(CASE WHEN a.status = 'late' THEN 1 END) as late_count,
                    COUNT(*) as total_count
                FROM attendance_student a
                LEFT JOIN teacher_classes tc ON a.class_id = tc.id
                WHERE a.class_id = '$selected_class_id'
                GROUP BY a.attendance_date
                ORDER BY a.attendance_date DESC 
                LIMIT 10";
                $history_result = mysqli_query($conn, $history_sql);
                ?>
                
                <?php if($history_result && mysqli_num_rows($history_result) > 0): ?>
                    <div class="table-responsive">
                        <table class="table table-sm">
                            <thead>
                                <tr>
                                    <th>Class Name</th>
                                    <th>Subject</th>
                                    <th>Shift</th>
                                    <th>Room</th>
                                    <th>Date</th>
                                    <th>Present</th>
                                    <th>Absent</th>
                                    <th>Late</th>
                                    <th>Total</th>
                                    <th>Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php while($history = mysqli_fetch_assoc($history_result)): 
                                    $date = $history['attendance_date'];
                                ?>
                                <tr>
                                    <td>
                                        <strong><?php echo htmlspecialchars($history['class_name'] ?? 'N/A'); ?></strong>
                                        <?php if(!empty($history['schedule_day'])): ?>
                                            <br><small class="text-muted"><i class="fas fa-calendar-day"></i> <?php echo htmlspecialchars($history['schedule_day']); ?></small>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?php echo htmlspecialchars($history['subject'] ?? 'N/A'); ?>
                                        <?php if(!empty($history['start_time']) && !empty($history['end_time'])): ?>
                                            <br><small class="text-muted">
                                                <i class="far fa-clock"></i> 
                                                <?php echo date('h:i A', strtotime($history['start_time'])); ?> - 
                                                <?php echo date('h:i A', strtotime($history['end_time'])); ?>
                                            </small>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <span class="shift-badge <?php echo strtolower($history['shift'] ?? ''); ?>">
                                            <?php echo htmlspecialchars($history['shift'] ?? 'N/A'); ?>
                                        </span>
                                    </td>
                                    <td>
                                        <?php echo htmlspecialchars($history['room'] ?? 'N/A'); ?>
                                    </td>
                                    <td><?php echo date('d M Y', strtotime($date)); ?></td>
                                    <td>
                                        <span class="badge badge-present">
                                            <?php echo $history['present_count'] ?? 0; ?>
                                        </span>
                                    </td>
                                    <td>
                                        <span class="badge badge-absent">
                                            <?php echo $history['absent_count'] ?? 0; ?>
                                        </span>
                                    </td>
                                    <td>
                                        <span class="badge badge-late">
                                            <?php echo $history['late_count'] ?? 0; ?>
                                        </span>
                                    </td>
                                    <td>
                                        <span class="badge bg-secondary">
                                            <?php echo $history['total_count'] ?? 0; ?>
                                        </span>
                                    </td>
                                    <td>
                                        <a href="?attendance=<?php echo $selected_class_id; ?>&date=<?php echo $date; ?>" 
                                           class="btn btn-sm btn-outline-primary">
                                            <i class="fas fa-eye"></i> View
                                        </a>
                                    </td>
                                </tr>
                                <?php endwhile; ?>
                            </tbody>
                        </table>
                    </div>
                <?php else: ?>
                    <div class="text-center py-3">
                        <p class="text-muted">No attendance records yet.</p>
                    </div>
                <?php endif; ?>
            </div>
            
        <?php elseif($show_grades): ?>
            <!-- ========== GRADE MANAGEMENT SECTION ========== -->
            <div class="content-box">
                <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-3">
                    <h4>
                        <i class="fas fa-star"></i> 
                        Grade Management
                        <span class="text-muted fs-6">(<?php echo htmlspecialchars($grade_class_info['class_name'] ?? 'Class'); ?>)</span>
                    </h4>
                    <div>
                        <span class="dept-badge">
                            <i class="fas fa-building"></i> <?php echo htmlspecialchars($grade_class_info['subject'] ?? ''); ?>
                        </span>
                        <?php if(!empty($grade_class_info['year'])): ?>
                        <span class="year-badge">
                            <i class="fas fa-graduation-cap"></i> <?php echo ucfirst($year_options[$grade_class_info['year']] ?? $grade_class_info['year']); ?>
                        </span>
                        <?php endif; ?>
                        <?php if(!empty($grade_class_info['shift'])): ?>
                        <span class="shift-badge <?php echo $grade_class_info['shift']; ?>">
                            <i class="fas fa-clock"></i> <?php echo ucfirst($grade_class_info['shift']); ?>
                        </span>
                        <?php endif; ?>
                        <span class="student-count-badge ms-2">
                            <i class="fas fa-users"></i> <?php echo count($grade_students_list); ?> Students
                        </span>
                        <a href="class.php" class="btn btn-secondary btn-sm ms-2">
                            <i class="fas fa-arrow-left"></i> Back
                        </a>
                    </div>
                </div>
                
                <div class="alert alert-info alert-custom py-2 mb-3">
                    <i class="fas fa-info-circle"></i> 
                    Enter scores for each student. Total score and grade will be calculated automatically.
                    <br>
                    <small>
                        <strong>Weight:</strong> 
                        Assignment (20%) | 
                        Midterm (30%) | 
                        Final (30%) | 
                        Project (10%) | 
                        Quiz (5%) | 
                        Attendance (5%)
                    </small>
                </div>
                
                <hr>
                
                <form method="POST">
                    <input type="hidden" name="class_id" value="<?php echo $selected_grade_class_id; ?>">
                    <input type="hidden" name="subject" value="<?php echo htmlspecialchars($grade_class_info['subject'] ?? ''); ?>">
                    <input type="hidden" name="semester" value="<?php echo htmlspecialchars($grade_class_info['semester'] ?? ''); ?>">
                    <input type="hidden" name="academic_year" value="<?php echo htmlspecialchars($grade_class_info['academic_year'] ?? ''); ?>">
                    
                    <div class="table-responsive">
                        <table class="table table-bordered table-hover" id="gradeTable">
                            <thead class="table-dark">
                                <tr>
                                    <th>#</th>
                                    <th>Student Name</th>
                                    <th>Assignment<br><small>(20%)</small></th>
                                    <th>Midterm<br><small>(30%)</small></th>
                                    <th>Final<br><small>(30%)</small></th>
                                    <th>Project<br><small>(10%)</small></th>
                                    <th>Quiz<br><small>(5%)</small></th>
                                    <th>Attendance<br><small>(5%)</small></th>
                                    <th>Total</th>
                                    <th>Grade</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if(empty($grade_students_list)): ?>
                                    <tr>
                                        <td colspan="10" class="text-center text-muted py-4">
                                            <i class="fas fa-users fa-2x d-block mb-2"></i>
                                            No students found in this class.
                                            <br>
                                            <small>Please add students to this class first.</small>
                                        </td>
                                    </tr>
                                <?php else: ?>
                                    <?php 
                                    $count = 1;
                                    foreach($grade_students_list as $student):
                                        $existing = isset($grade_data[$student['id']]) ? $grade_data[$student['id']] : null;
                                    ?>
                                    <tr>
                                        <td><?php echo $count++; ?></td>
                                        <td>
                                            <?php echo htmlspecialchars($student['name']); ?>
                                            <input type="hidden" name="student_id[]" value="<?php echo $student['id']; ?>">
                                            <input type="hidden" name="student_name[<?php echo $student['id']; ?>]" 
                                                   value="<?php echo htmlspecialchars($student['name']); ?>">
                                        </td>
                                        <td>
                                            <input type="number" class="form-control form-control-sm grade-input" 
                                                   name="assignment_score[<?php echo $student['id']; ?>]" 
                                                   value="<?php echo $existing ? $existing['assignment_score'] : 0; ?>"
                                                   min="0" max="100" step="0.5"
                                                   onchange="calculateTotal(this)">
                                        </td>
                                        <td>
                                            <input type="number" class="form-control form-control-sm grade-input" 
                                                   name="midterm_score[<?php echo $student['id']; ?>]" 
                                                   value="<?php echo $existing ? $existing['midterm_score'] : 0; ?>"
                                                   min="0" max="100" step="0.5"
                                                   onchange="calculateTotal(this)">
                                        </td>
                                        <td>
                                            <input type="number" class="form-control form-control-sm grade-input" 
                                                   name="final_score[<?php echo $student['id']; ?>]" 
                                                   value="<?php echo $existing ? $existing['final_score'] : 0; ?>"
                                                   min="0" max="100" step="0.5"
                                                   onchange="calculateTotal(this)">
                                        </td>
                                        <td>
                                            <input type="number" class="form-control form-control-sm grade-input" 
                                                   name="project_score[<?php echo $student['id']; ?>]" 
                                                   value="<?php echo $existing ? $existing['project_score'] : 0; ?>"
                                                   min="0" max="100" step="0.5"
                                                   onchange="calculateTotal(this)">
                                        </td>
                                        <td>
                                            <input type="number" class="form-control form-control-sm grade-input" 
                                                   name="quiz_score[<?php echo $student['id']; ?>]" 
                                                   value="<?php echo $existing ? $existing['quiz_score'] : 0; ?>"
                                                   min="0" max="100" step="0.5"
                                                   onchange="calculateTotal(this)">
                                        </td>
                                        <td>
                                            <input type="number" class="form-control form-control-sm grade-input" 
                                                   name="attendance_score[<?php echo $student['id']; ?>]" 
                                                   value="<?php echo $existing ? $existing['attendance_score'] : 0; ?>"
                                                   min="0" max="100" step="0.5"
                                                   onchange="calculateTotal(this)">
                                        </td>
                                        <td class="text-center fw-bold total-score" id="total_<?php echo $student['id']; ?>">
                                            <?php echo $existing ? $existing['total_score'] : '0.00'; ?>
                                        </td>
                                        <td class="text-center">
                                            <span class="badge <?php 
                                                if($existing) {
                                                    $grade = $existing['grade'];
                                                    echo $grade_badge_class[$grade] ?? 'badge-secondary';
                                                } else {
                                                    echo 'badge-secondary';
                                                }
                                            ?> grade-badge" id="grade_<?php echo $student['id']; ?>">
                                                <?php echo $existing ? $existing['grade'] : 'N/A'; ?>
                                            </span>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                    
                    <div class="mt-3 d-flex gap-2 flex-wrap">
                        <button type="submit" name="save_grades" class="btn-save">
                            <i class="fas fa-save"></i> Save All Grades
                        </button>
                        <button type="button" class="btn btn-secondary" onclick="calculateAllGrades()">
                            <i class="fas fa-calculator"></i> Calculate All
                        </button>
                        <button type="button" class="btn btn-secondary" onclick="resetAllGrades()">
                            <i class="fas fa-undo"></i> Reset All
                        </button>
                    </div>
                </form>
            </div>
            
            <!-- ========== GRADE SUMMARY ========== -->
            <div class="content-box">
                <h4><i class="fas fa-chart-bar"></i> Grade Summary</h4>
                <hr>
                
                <?php
                $summary_sql = "SELECT grade, COUNT(*) as count FROM student_grades 
                                WHERE class_id = '$selected_grade_class_id' 
                                GROUP BY grade";
                $summary_result = mysqli_query($conn, $summary_sql);
                $summary_data = array('A' => 0, 'B' => 0, 'C' => 0, 'D' => 0, 'F' => 0);
                if($summary_result) {
                    while($row = mysqli_fetch_assoc($summary_result)) {
                        if(isset($summary_data[$row['grade']])) {
                            $summary_data[$row['grade']] = $row['count'];
                        }
                    }
                }
                $total_students = array_sum($summary_data);
                ?>
                
                <div class="row g-3">
                    <div class="col-md-2 col-6">
                        <div class="summary-box">
                            <h3 class="grade-A">A</h3>
                            <p><?php echo $summary_data['A']; ?> Students</p>
                            <small class="text-muted"><?php echo $total_students > 0 ? round(($summary_data['A']/$total_students)*100, 1) : 0; ?>%</small>
                        </div>
                    </div>
                    <div class="col-md-2 col-6">
                        <div class="summary-box">
                            <h3 class="grade-B">B</h3>
                            <p><?php echo $summary_data['B']; ?> Students</p>
                            <small class="text-muted"><?php echo $total_students > 0 ? round(($summary_data['B']/$total_students)*100, 1) : 0; ?>%</small>
                        </div>
                    </div>
                    <div class="col-md-2 col-6">
                        <div class="summary-box">
                            <h3 class="grade-C">C</h3>
                            <p><?php echo $summary_data['C']; ?> Students</p>
                            <small class="text-muted"><?php echo $total_students > 0 ? round(($summary_data['C']/$total_students)*100, 1) : 0; ?>%</small>
                        </div>
                    </div>
                    <div class="col-md-2 col-6">
                        <div class="summary-box">
                            <h3 class="grade-D">D</h3>
                            <p><?php echo $summary_data['D']; ?> Students</p>
                            <small class="text-muted"><?php echo $total_students > 0 ? round(($summary_data['D']/$total_students)*100, 1) : 0; ?>%</small>
                        </div>
                    </div>
                    <div class="col-md-2 col-6">
                        <div class="summary-box">
                            <h3 class="grade-F">F</h3>
                            <p><?php echo $summary_data['F']; ?> Students</p>
                            <small class="text-muted"><?php echo $total_students > 0 ? round(($summary_data['F']/$total_students)*100, 1) : 0; ?>%</small>
                        </div>
                    </div>
                    <div class="col-md-2 col-6">
                        <div class="summary-box" style="background: #1e293b; color: white; border-color: #1e293b;">
                            <h3><?php echo $total_students; ?></h3>
                            <p>Total Students</p>
                            <small style="color: #94a3b8;">100%</small>
                        </div>
                    </div>
                </div>
            </div>
            
            <script>
            function calculateTotal(input) {
                // Get the row
                var row = input.closest('tr');
                var studentId = input.name.match(/\[(.*?)\]/)[1];
                
                // Get all score inputs in this row
                var assignment = parseFloat(row.querySelector('input[name="assignment_score[' + studentId + ']"]').value) || 0;
                var midterm = parseFloat(row.querySelector('input[name="midterm_score[' + studentId + ']"]').value) || 0;
                var final = parseFloat(row.querySelector('input[name="final_score[' + studentId + ']"]').value) || 0;
                var project = parseFloat(row.querySelector('input[name="project_score[' + studentId + ']"]').value) || 0;
                var quiz = parseFloat(row.querySelector('input[name="quiz_score[' + studentId + ']"]').value) || 0;
                var attendance = parseFloat(row.querySelector('input[name="attendance_score[' + studentId + ']"]').value) || 0;
                
                // Calculate total
                var total = (assignment * 0.2) + (midterm * 0.3) + (final * 0.3) + (project * 0.1) + (quiz * 0.05) + (attendance * 0.05);
                total = Math.round(total * 100) / 100;
                
                // Update total score
                document.getElementById('total_' + studentId).textContent = total.toFixed(2);
                
                // Calculate grade
                var grade = '';
                if(total >= 90) grade = 'A';
                else if(total >= 80) grade = 'B';
                else if(total >= 70) grade = 'C';
                else if(total >= 60) grade = 'D';
                else grade = 'F';
                
                // Update grade badge
                var gradeBadge = document.getElementById('grade_' + studentId);
                gradeBadge.textContent = grade;
                
                // Update badge color
                var gradeColors = {
                    'A': 'badge-success',
                    'B': 'badge-primary',
                    'C': 'badge-info',
                    'D': 'badge-warning',
                    'F': 'badge-danger'
                };
                gradeBadge.className = 'badge ' + (gradeColors[grade] || 'badge-secondary');
            }
            
            function calculateAllGrades() {
                var inputs = document.querySelectorAll('.grade-input');
                inputs.forEach(function(input) {
                    calculateTotal(input);
                });
                alert('All grades have been calculated!');
            }
            
            function resetAllGrades() {
                if(confirm('Are you sure you want to reset all grades to 0?')) {
                    document.querySelectorAll('.grade-input').forEach(function(input) {
                        input.value = 0;
                        calculateTotal(input);
                    });
                }
            }
            </script>
            
        <?php else: ?>
        
            <!-- ========== CLASS CRUD SECTION ========== -->
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
                                   placeholder="e.g. Computer Science" 
                                   value="<?php echo $edit_class_data ? htmlspecialchars($edit_class_data['class_name']) : ''; ?>" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label"><i class="fas fa-building"></i> College/Department <span class="text-danger">*</span></label>
                            <select class="form-select" name="department" required>
                                <option value="">Select College</option>
                                <?php
                                if(!empty($college_list)) {
                                    foreach($college_list as $college) {
                                        $selected = ($edit_class_data && $edit_class_data['subject'] == $college) ? 'selected' : '';
                                        echo '<option value="' . htmlspecialchars($college) . '" ' . $selected . '>' 
                                             . htmlspecialchars($college) . '</option>';
                                    }
                                } else {
                                    $default_depts = ['Civil Engineering', 'Electronics', 'Electrical', 'Business Science', 'it'];
                                    foreach($default_depts as $dept) {
                                        $selected = ($edit_class_data && $edit_class_data['subject'] == $dept) ? 'selected' : '';
                                        echo '<option value="' . $dept . '" ' . $selected . '>' . $dept . '</option>';
                                    }
                                }
                                ?>
                            </select>
                            <small class="text-muted">
                                <i class="fas fa-info-circle"></i> 
                                Select a college that exists in the students table
                            </small>
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
                        <div class="col-md-3 mb-3">
                            <label class="form-label"><i class="fas fa-graduation-cap"></i> Semester</label>
                            <input type="text" class="form-control" name="semester" 
                                   placeholder="e.g. Semester 1" 
                                   value="<?php echo $edit_class_data ? htmlspecialchars($edit_class_data['semester']) : ''; ?>">
                        </div>
                        <div class="col-md-3 mb-3">
                            <label class="form-label"><i class="fas fa-calendar-alt"></i> Academic Year</label>
                            <input type="text" class="form-control" name="academic_year" 
                                   placeholder="e.g. 2024-2025" 
                                   value="<?php echo $edit_class_data ? htmlspecialchars($edit_class_data['academic_year']) : ''; ?>">
                        </div>
                        <div class="col-md-3 mb-3">
                            <label class="form-label"><i class="fas fa-layer-group"></i> Year <span class="text-danger">*</span></label>
                            <select class="form-select" name="class_year" required>
                                <option value="">Select Year</option>
                                <?php foreach($year_options as $key => $year): ?>
                                    <option value="<?php echo $key; ?>" <?php echo ($edit_class_data && $edit_class_data['year'] == $key) ? 'selected' : ''; ?>>
                                        <?php echo ucfirst($year); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                            <small class="text-muted">
                                <i class="fas fa-info-circle"></i> 
                                Select the year this class is for
                            </small>
                        </div>
                        <div class="col-md-3 mb-3">
                            <label class="form-label"><i class="fas fa-clock"></i> Shift <span class="text-danger">*</span></label>
                            <select class="form-select" name="shift" required>
                                <option value="">Select Shift</option>
                                <?php foreach($shift_options as $key => $shift): ?>
                                    <option value="<?php echo $key; ?>" <?php echo ($edit_class_data && $edit_class_data['shift'] == $key) ? 'selected' : ''; ?>>
                                        <?php echo $shift; ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                            <small class="text-muted">
                                <i class="fas fa-info-circle"></i> 
                                Select shift for this class
                            </small>
                        </div>
                        <div class="col-md-3 mb-3">
                            <label class="form-label"><i class="fas fa-calendar-alt"></i> Date</label>
                            <input type="date" name="date" class="form-control" 
                                   value="<?php echo $edit_class_data && !empty($edit_class_data['date']) ? htmlspecialchars($edit_class_data['date']) : ''; ?>">
                        </div>
                        <div class="col-md-3 mb-3">
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

            <!-- ========== CLASSES LIST ========== -->
            <div class="content-box">
                <h4><i class="fas fa-list"></i> My Classes (<?php echo mysqli_num_rows($classes_result); ?>)</h4>
                <hr>
                
                <?php if(mysqli_num_rows($classes_result) > 0): ?>
                    <div class="row">
                        <?php while($class = mysqli_fetch_assoc($classes_result)):
                            $status_class = $status_badge_class[$class['status']] ?? 'badge-secondary';
                            $class_year = isset($class['year']) ? $class['year'] : '';
                            $class_shift = isset($class['shift']) ? $class['shift'] : '';
                            
                            $student_count = 0;
                            $students_for_class = getStudentsForClass($class['id']);
                            if(!empty($students_for_class)) {
                                $student_count = count($students_for_class);
                            }
                        ?>
                        <div class="col-md-6 col-lg-4 mb-3">
                            <div class="class-card">
                                <div class="d-flex justify-content-between align-items-start">
                                    <div>
                                        <h6 class="mb-1"><?php echo htmlspecialchars($class['class_name']); ?></h6>
                                        <p class="text-muted small mb-1">
                                            <i class="fas fa-building"></i> <?php echo htmlspecialchars($class['subject']); ?>
                                        </p>
                                        <?php if(!empty($class_year)): 
                                            $year_display = isset($year_options[$class_year]) ? $year_options[$class_year] : $class_year;
                                        ?>
                                        <p class="text-muted small mb-1">
                                            <i class="fas fa-graduation-cap"></i> <?php echo ucfirst($year_display); ?>
                                        </p>
                                        <?php endif; ?>
                                        <?php if(!empty($class_shift)): ?>
                                        <p class="text-muted small mb-1">
                                            <i class="fas fa-clock"></i> Shift: <?php echo ucfirst($class_shift); ?>
                                        </p>
                                        <?php endif; ?>
                                        <?php if(!empty($class['room'])): ?>
                                            <p class="text-muted small mb-1">
                                                <i class="fas fa-door-open"></i> <?php echo htmlspecialchars($class['room']); ?>
                                            </p>
                                        <?php endif; ?>
                                        <?php if(!empty($class['date'])): ?>
                                            <p class="text-muted small mb-1">
                                                <i class="fas fa-calendar-alt"></i> Date: <?php echo date('d M Y', strtotime($class['date'])); ?>
                                            </p>
                                        <?php endif; ?>
                                        <?php if(!empty($class['schedule_day'])): ?>
                                            <p class="text-muted small mb-1">
                                                <i class="fas fa-calendar-day"></i> <?php echo $class['schedule_day']; ?>
                                                <?php if(!empty($class['start_time'])): ?>
                                                    (<?php echo date('h:i A', strtotime($class['start_time'])); ?> - 
                                                    <?php echo date('h:i A', strtotime($class['end_time'])); ?>)
                                                <?php endif; ?>
                                            </p>
                                        <?php endif; ?>
                                        <p class="small mb-1">
                                            <span class="badge-status <?php echo $status_class; ?>">
                                                <?php echo ucfirst($class['status']); ?>
                                            </span>
                                            <span class="student-count-badge ms-1">
                                                <i class="fas fa-users"></i> <?php echo $student_count; ?> Students
                                            </span>
                                        </p>
                                    </div>
                                    <div class="text-end">
                                        <span class="badge bg-primary"><?php echo $class['semester'] ?? 'N/A'; ?></span>
                                        <br>
                                        <small class="text-muted"><?php echo $class['academic_year'] ?? ''; ?></small>
                                    </div>
                                </div>
                                <hr>
                                <div class="d-flex gap-2 flex-wrap">
                                    <a href="?attendance=<?php echo $class['id']; ?>" class="btn btn-sm btn-primary">
                                        <i class="fas fa-clipboard-check"></i> Attendance
                                    </a>
                                    <a href="?grades=<?php echo $class['id']; ?>" class="btn btn-sm btn-success">
                                        <i class="fas fa-star"></i> Grades
                                    </a>
                                    <a href="?edit=<?php echo $class['id']; ?>" class="btn btn-sm btn-outline-primary">
                                        <i class="fas fa-edit"></i>
                                    </a>
                                    <a href="?toggle=<?php echo $class['id']; ?>" class="btn btn-sm btn-outline-warning">
                                        <i class="fas fa-sync-alt"></i>
                                    </a>
                                    <a href="?delete=<?php echo $class['id']; ?>" class="btn btn-sm btn-outline-danger" 
                                       onclick="return confirm('Are you sure you want to delete this class?')">
                                        <i class="fas fa-trash"></i>
                                    </a>
                                </div>
                            </div>
                        </div>
                        <?php endwhile; ?>
                    </div>
                <?php else: ?>
                    <div class="text-center py-4">
                        <i class="fas fa-chalkboard-teacher" style="font-size: 48px; color: #cbd5e1;"></i>
                        <p class="text-muted mt-3">You haven't added any classes yet.</p>
                        <p class="text-muted">Use the form above to add your first class!</p>
                    </div>
                <?php endif; ?>
            </div>
            
        <?php endif; ?>
    </div>
</div>

<!-- ========== ADD STUDENT MODAL ========== -->
<div class="modal fade" id="addStudentModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="fas fa-user-plus"></i> Add Student to Class</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST" action="add_student.php">
                <div class="modal-body">
                    <input type="hidden" name="class_id" value="<?php echo $selected_class_id; ?>">
                    <div class="mb-3">
                        <label class="form-label">Student Code</label>
                        <input type="text" class="form-control" name="student_code" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Student Name</label>
                        <input type="text" class="form-control" name="name" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">College / Department</label>
                        <input type="text" class="form-control" name="college" 
                               value="<?php echo htmlspecialchars($selected_department); ?>" readonly>
                        <small class="text-muted">Student will be assigned to college: <?php echo htmlspecialchars($selected_department); ?></small>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Year</label>
                        <select class="form-select" name="year" required>
                            <option value="">Select Year</option>
                            <?php foreach($year_options as $key => $year): ?>
                                <option value="<?php echo $year; ?>" <?php echo (isset($selected_year) && $selected_year == $key) ? 'selected' : ''; ?>>
                                    <?php echo ucfirst($year); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <small class="text-muted">Student will be assigned to year: <?php echo isset($year_options[$selected_year]) ? ucfirst($year_options[$selected_year]) : ''; ?></small>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Shift</label>
                        <select class="form-select" name="shift" required>
                            <option value="">Select Shift</option>
                            <?php foreach($shift_options as $key => $shift): ?>
                                <option value="<?php echo $key; ?>" <?php echo ($selected_shift == $key) ? 'selected' : ''; ?>>
                                    <?php echo $shift; ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <small class="text-muted">Student will be assigned to shift: <?php echo ucfirst($selected_shift); ?></small>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Email</label>
                        <input type="email" class="form-control" name="email">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Phone</label>
                        <input type="text" class="form-control" name="phone">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Gender</label>
                        <select class="form-select" name="gender">
                            <option value="Male">Male</option>
                            <option value="Female">Female</option>
                        </select>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" name="add_student" class="btn btn-success">
                        <i class="fas fa-plus"></i> Add Student
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
</script>

</body>
</html>