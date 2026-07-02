<?php

include "db.php";


if(!isset($_SESSION['id'])){
    header("Location: login.php");
    exit();
}


if($_SESSION['role'] != 'teacher'){
    header("Location: index.php");
    exit();
}

if(empty($logged_in_email)){
   
    $logged_in_name = $_SESSION['name'] ?? '';
    
    if(!empty($logged_in_name)){
        // ស្វែងរកគ្រូតាមឈ្មោះ
        $sql_teacher_info = "SELECT * FROM teachers WHERE name = '$logged_in_name'";
        $result_teacher_info = mysqli_query($conn, $sql_teacher_info);
        $teacher_info = mysqli_fetch_assoc($result_teacher_info);
        
        if($teacher_info){
            // រកឃើញគ្រូតាមឈ្មោះ
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
            
            // បន្តដំណើរការទំព័រ
            return;
        }
    }
    
    // បើរកមិនឃើញ → Logout
    echo "<script>alert('Session expired! Please login again.'); window.location='logout.php';</script>";
    exit();
}

$sql_teacher_info = "SELECT * FROM teachers WHERE email = '$logged_in_email'";
$result_teacher_info = mysqli_query($conn, $sql_teacher_info);
$teacher_info = mysqli_fetch_assoc($result_teacher_info);

if(!$teacher_info){
    $logged_in_name = $_SESSION['name'] ?? '';
    if(!empty($logged_in_name)){
        $sql_teacher_info = "SELECT * FROM teachers WHERE name = '$logged_in_name'";
        $result_teacher_info = mysqli_query($conn, $sql_teacher_info);
        $teacher_info = mysqli_fetch_assoc($result_teacher_info);
    }
}

if(!$teacher_info) {
    // បង្ហាញព័ត៌មាន Debug
    echo "<div style='padding:30px; background:#f8d7da; color:#721c24; border-radius:10px; margin:20px; font-family:Arial;'>
        <h3>⚠️ Teacher Not Found!</h3>
        <p><strong>Email from Session:</strong> " . htmlspecialchars($logged_in_email) . "</p>
        <p><strong>Name from Session:</strong> " . htmlspecialchars($_SESSION['name'] ?? 'N/A') . "</p>
        <p><strong>Role:</strong> " . htmlspecialchars($_SESSION['role'] ?? 'N/A') . "</p>
        <hr>
        <p><strong>Available teachers in database:</strong></p>
        <ul>";
    
    $all_teachers = mysqli_query($conn, "SELECT id, name, email FROM teachers");
    if(mysqli_num_rows($all_teachers) > 0){
        while($t = mysqli_fetch_assoc($all_teachers)) {
            echo "<li>ID: {$t['id']} - {$t['name']} ({$t['email']})</li>";
        }
    } else {
        echo "<li>No teachers found in database!</li>";
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
?>