<?php
// splash.php
include "db.php";
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// ត្រួតពិនិត្យថាអ្នកប្រើបាន Login ឬនៅ
if (!isset($_SESSION['student_logged_in']) || $_SESSION['student_logged_in'] !== true) {
    header("Location: login.php");
    exit();
}


if (isset($_SESSION['splash_shown']) && $_SESSION['splash_shown'] === true) {
   
    $role = $_SESSION['role'] ?? 'student';
    $dashboard_urls = [
        'admin' => 'index.php',
        'teacher' => 'forteacher.php',
        'student' => 'forstudent.php'
    ];
    $dashboard_url = $dashboard_urls[$role] ?? 'forstudent.php';
    header("Location: $dashboard_url");
    exit();
}


$_SESSION['splash_shown'] = true;

// ទាញទិន្នន័យអ្នកប្រើពី Session
$student_name = $_SESSION['name'] ?? 'Student';
$student_id = $_SESSION['id'] ?? '';
$student_email = $_SESSION['email'] ?? '';
$student_role = $_SESSION['role'] ?? 'student';
// $student_image = $_SESSION['student_image'] ?? 'https://i.pinimg.com/1200x/0c/3b/a6/0c3ba6df9e70c306dc610829b6018578.jpg';


$dashboard_urls = [
    'admin' => 'index.php',
    'teacher' => 'forteacher.php',
    'student' => 'forstudent.php'
];
$dashboard_url = $dashboard_urls[$student_role] ?? 'forstudent.php';


$splash_duration = 6000; // 3 វិនាទី

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Welcome - KRaksa Education</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: "Inter", sans-serif;
            background: linear-gradient(135deg, #1e1b4b, #312e81, #4f46e5);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            overflow: hidden;
        }
        
        .splash-container {
            text-align: center;
            padding: 40px;
            animation: fadeInUp 0.8s ease;
        }
        
        .splash-logo {
            font-size: 100px;
            color: white;
            margin-bottom: 20px;
            animation: pulse 2s ease-in-out infinite;
            display: inline-block;
        }
        
        .splash-logo i {
            filter: drop-shadow(0 10px 30px rgba(79, 70, 229, 0.5));
        }
        
        .splash-title {
            color: white;
            font-size: 42px;
            font-weight: 800;
            letter-spacing: -1px;
            margin-bottom: 8px;
        }
        
        .splash-title span {
            background: linear-gradient(135deg, #a78bfa, #818cf8);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }
        
        .splash-subtitle {
            color: rgba(255, 255, 255, 0.7);
            font-size: 18px;
            font-weight: 400;
            margin-bottom: 30px;
        }
        
        .splash-profile {
            margin-bottom: 25px;
        }
        
        .splash-profile img {
            width: 120px;
            height: 120px;
            border-radius: 50%;
            border: 4px solid rgba(255, 255, 255, 0.3);
            object-fit: cover;
            box-shadow: 0 10px 40px rgba(0, 0, 0, 0.3);
            animation: scaleIn 1s ease;
        }
        
        .splash-profile .name {
            color: white;
            font-size: 24px;
            font-weight: 600;
            margin-top: 15px;
        }
        
        .splash-profile .role-badge {
            display: inline-block;
            padding: 4px 16px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 1px;
            margin-top: 8px;
        }
        
        .role-badge.admin {
            background: #ef4444;
            color: white;
        }
        .role-badge.teacher {
            background: #f59e0b;
            color: white;
        }
        .role-badge.student {
            background: #10b981;
            color: white;
        }
        
        .splash-stats {
            display: flex;
            justify-content: center;
            gap: 30px;
            margin: 25px 0 30px;
            flex-wrap: wrap;
        }
        
        .stat-item {
            background: rgba(255, 255, 255, 0.1);
            backdrop-filter: blur(10px);
            padding: 12px 25px;
            border-radius: 12px;
            border: 1px solid rgba(255, 255, 255, 0.1);
            min-width: 100px;
        }
        
        .stat-number {
            display: block;
            color: white;
            font-size: 24px;
            font-weight: 700;
        }
        
        .stat-label {
            display: block;
            color: rgba(255, 255, 255, 0.6);
            font-size: 11px;
            text-transform: uppercase;
            letter-spacing: 1px;
            margin-top: 2px;
        }
        
        .splash-loader {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 12px;
            margin-top: 20px;
        }
        
        .splash-loader .dot {
            width: 14px;
            height: 14px;
            border-radius: 50%;
            background: rgba(255, 255, 255, 0.2);
            animation: bounce 1.4s ease-in-out infinite both;
        }
        
        .splash-loader .dot:nth-child(1) {
            animation-delay: -0.32s;
            background: #818cf8;
        }
        .splash-loader .dot:nth-child(2) {
            animation-delay: -0.16s;
            background: #a78bfa;
        }
        .splash-loader .dot:nth-child(3) {
            animation-delay: 0s;
            background: #c4b5fd;
        }
        
        .splash-progress {
            width: 300px;
            max-width: 80%;
            height: 4px;
            background: rgba(255, 255, 255, 0.15);
            border-radius: 4px;
            margin: 20px auto 0;
            overflow: hidden;
        }
        
        .splash-progress-bar {
            height: 100%;
            width: 0%;
            background: linear-gradient(90deg, #818cf8, #a78bfa, #c4b5fd);
            border-radius: 4px;
            animation: progress 2.5s ease-in-out forwards;
        }
        
        .splash-status {
            color: rgba(255, 255, 255, 0.5);
            font-size: 13px;
            margin-top: 15px;
            letter-spacing: 2px;
            text-transform: uppercase;
        }
        
        /* Animations */
        @keyframes fadeInUp {
            from {
                opacity: 0;
                transform: translateY(30px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
        
        @keyframes pulse {
            0%, 100% { transform: scale(1); }
            50% { transform: scale(1.05); }
        }
        
        @keyframes scaleIn {
            from {
                opacity: 0;
                transform: scale(0.5);
            }
            to {
                opacity: 1;
                transform: scale(1);
            }
        }
        
        @keyframes bounce {
            0%, 80%, 100% { transform: scale(0); }
            40% { transform: scale(1); }
        }
        
        @keyframes progress {
            0% { width: 0%; }
            20% { width: 20%; }
            50% { width: 55%; }
            80% { width: 80%; }
            100% { width: 100%; }
        }
        
        /* Particles */
        .particles {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            pointer-events: none;
            z-index: -1;
            overflow: hidden;
        }
        
        .particle {
            position: absolute;
            width: 6px;
            height: 6px;
            background: rgba(255, 255, 255, 0.1);
            border-radius: 50%;
            animation: float 15s infinite linear;
        }
        
        .particle:nth-child(1) { left: 10%; animation-delay: 0s; animation-duration: 12s; }
        .particle:nth-child(2) { left: 20%; animation-delay: 2s; animation-duration: 18s; }
        .particle:nth-child(3) { left: 30%; animation-delay: 4s; animation-duration: 14s; }
        .particle:nth-child(4) { left: 40%; animation-delay: 1s; animation-duration: 16s; }
        .particle:nth-child(5) { left: 50%; animation-delay: 3s; animation-duration: 13s; }
        .particle:nth-child(6) { left: 60%; animation-delay: 5s; animation-duration: 19s; }
        .particle:nth-child(7) { left: 70%; animation-delay: 2s; animation-duration: 15s; }
        .particle:nth-child(8) { left: 80%; animation-delay: 4s; animation-duration: 17s; }
        .particle:nth-child(9) { left: 90%; animation-delay: 1s; animation-duration: 14s; }
        .particle:nth-child(10) { left: 15%; animation-delay: 3s; animation-duration: 20s; }
        .particle:nth-child(11) { left: 45%; animation-delay: 5s; animation-duration: 16s; }
        .particle:nth-child(12) { left: 75%; animation-delay: 2s; animation-duration: 18s; }
        .typing-container{
          position: absolute;
          top: 10%;
          left: 50%;
          transform: translate(-50%, -50%);
          z-index: 999;
        }

        #text{
            color: white;
            font-size: 60px;
            font-weight: bold;
            font-family: Arial, sans-serif;
            text-align: center;
            letter-spacing: 3px;
        }
        
        #text::after{
            content: "|";
            animation: blink .8s infinite;
        }
        
        @keyframes blink{
            50%{
                opacity: 0;
            }
        }
        
        @keyframes float {
            0% {
                transform: translateY(100vh) rotate(0deg);
                opacity: 0;
            }
            10% { opacity: 1; }
            90% { opacity: 1; }
            100% {
                transform: translateY(-10vh) rotate(720deg);
                opacity: 0;
            }
        }
        
        @media (max-width: 600px) {
            .splash-title {
                font-size: 28px;
            }
            .splash-logo {
                font-size: 70px;
            }
            .splash-profile img {
                width: 90px;
                height: 90px;
            }
            .splash-profile .name {
                font-size: 20px;
            }
            .splash-progress {
                width: 200px;
            }
            .splash-stats {
                gap: 15px;
            }
            .stat-item {
                padding: 10px 18px;
                min-width: 70px;
            }
            .stat-number {
                font-size: 18px;
            }
           
        
        
        }
    </style>
</head>
<body>

<!-- PARTICLES -->
 <div class="typing-container">
    <h1 id="text"></h1>
</div>

<div class="particles">
     
    <div class="particle"></div>
    <div class="particle"></div>
    <div class="particle"></div>
    <div class="particle"></div>
    <div class="particle"></div>
    <div class="particle"></div>
    <div class="particle"></div>
    <div class="particle"></div>
    <div class="particle"></div>
    <div class="particle"></div>
    <div class="particle"></div>
    <div class="particle"></div>
</div>

<!-- SPLASH CONTENT -->
<div class="splash-container">
    <!-- Logo -->
    <div class="splash-logo">
        <i class="fas fa-graduation-cap"></i>
    </div>
    
    <!-- Title -->
    <h1 class="splash-title">
        KRaksa <span>Education</span>
    </h1>
    <p class="splash-subtitle">
        <i class="fas fa-<?php echo $student_role == 'admin' ? 'user-shield' : ($student_role == 'teacher' ? 'chalkboard-user' : 'user-graduate'); ?>"></i>
        <?php echo ucfirst($student_role); ?> Dashboard
    </p>
    
    <!-- Profile -->
    <div class="splash-profile">
        <!-- <img src="<?php echo htmlspecialchars($student_image); ?>" alt="Profile" /> -->
        <div class="name"><?php echo htmlspecialchars($student_name); ?></div>
        <span class="role-badge <?php echo $student_role; ?>">
            <i class="fas fa-<?php echo $student_role == 'admin' ? 'crown' : ($student_role == 'teacher' ? 'chalkboard' : 'book'); ?>"></i>
            <?php echo ucfirst($student_role); ?>
        </span>
    </div>
    
    <!-- Stats (សម្រាប់ Student) -->
    <?php if ($student_role == 'student'): ?>
    <div class="splash-stats">
        <div class="stat-item">
            <span class="stat-number"><?php echo $avg_score; ?></span>
            <span class="stat-label">Avg Score</span>
        </div>
        <div class="stat-item">
            <span class="stat-number"><?php echo $total_subjects; ?></span>
            <span class="stat-label">Subjects</span>
        </div>
        <div class="stat-item">
            <span class="stat-number"><?php echo $attendance_percent; ?>%</span>
            <span class="stat-label">Attendance</span>
        </div>
    </div>
    <?php endif; ?>
    
    <!-- Loader -->
    <div class="splash-loader">
        <div class="dot"></div>
        <div class="dot"></div>
        <div class="dot"></div>
    </div>
    
    <!-- Progress Bar -->
    <div class="splash-progress">
        <div class="splash-progress-bar"></div>
    </div>
    
    <!-- Status -->
    <div class="splash-status">
        <i class="fas fa-spinner fa-spin"></i> Loading your dashboard...
    </div>
</div>

<!-- REDIRECT SCRIPT -->
<script>
const word = "Welcome To E-Education";
let index = 0;

function typing() {
    if (index < word.length) {
        document.getElementById("text").innerHTML += word.charAt(index);
        index++;
        setTimeout(typing, 300);
    }
}

typing();
</script>
<script>
    // បន្ទាប់ពីរយៈពេលកំណត់ ប្តូរទៅ Dashboard
    setTimeout(function() {
        window.location.href = "<?php echo $dashboard_url; ?>";
    }, <?php echo $splash_duration; ?>);
</script>

</body>
</html>