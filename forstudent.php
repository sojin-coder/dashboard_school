<?php


include "db.php";

// ការពារ user ប្តូរ link
if(!isset($_SESSION['id'])){
    header("Location: login.php");
    exit();
}

$db_conn = $conn ?? $connection; 


$logged_in_user_id = $_SESSION['id'];


if (isset($_GET['search_id']) && !empty(trim($_GET['search_id']))) {
    $user_id = mysqli_real_escape_string($db_conn, trim($_GET['search_id']));
} else {
    $user_id = $logged_in_user_id; 
}


$student_query = "SELECT * FROM students WHERE id = '$user_id'";
$student_result = mysqli_query($db_conn, $student_query);

if(!$student_result){
    die("Query Error (students): " . mysqli_error($db_conn));
}

$student = mysqli_fetch_assoc($student_result);


$search_error = false;
if(!$student){
    $search_error = true;
    $user_id = $logged_in_user_id; 
    $student_query = "SELECT * FROM students WHERE id = '$user_id'";
    $student_result = mysqli_query($db_conn, $student_query);
    $student = mysqli_fetch_assoc($student_result);
}


$enroll_query = "SELECT course_id, enroll_date FROM enrollments WHERE student_id = '$user_id' LIMIT 5";
$enroll_result = mysqli_query($db_conn, $enroll_query);
if(!$enroll_result){ $enroll_result = false; }


$scores_query = "SELECT course_id, score FROM scores WHERE student_id = '$user_id' ORDER BY id DESC LIMIT 5";
$scores_result = mysqli_query($db_conn, $scores_query);
if(!$scores_result){ $scores_result = false; }

$attendance_query = "SELECT 
    COUNT(*) as total_days,
    SUM(CASE WHEN status = 'P' THEN 1 ELSE 0 END) as present_days 
    FROM studenattenden 
    WHERE student_id = '$user_id' 
    AND MONTH(attendance_date) = MONTH(CURRENT_DATE())";
$att_data_result = mysqli_query($db_conn, $attendance_query);

if($att_data_result && mysqli_num_rows($att_data_result) > 0){
    $att_data = mysqli_fetch_assoc($att_data_result);
    $attendance_rate = ($att_data['total_days'] ?? 0) > 0 ? round(($att_data['present_days'] / $att_data['total_days']) * 100) : 0;
} else {
    $attendance_rate = 0;
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
    <title>KRaksa Education - Student Dashboard</title>
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
        .sidebar-header img { width: 100px; height: 100px; border-radius: 50%; margin: auto; display: block; border: 4px solid white; object-fit: cover; }
        .sidebar-header h1 { font-size: 1.5rem; font-weight: 700; color: white; margin-top: 10px; }
        .sidebar-header p { font-size: 0.85rem; opacity: 0.8; }
        .nav-menu { flex: 1; padding: 0 16px; }
        .nav-item { display: flex; align-items: center; gap: 15px; padding: 12px 16px; margin-bottom: 8px; border-radius: 14px; cursor: pointer; transition: 0.3s; color: #cbd5e6; text-decoration: none; }
        .nav-item:hover { background: rgba(255, 255, 255, 0.1); color: white; }
        .nav-item.active { background: #fd0054; color: white; box-shadow: 0 4px 8px rgba(0, 0, 0, 0.2); }
        .nav-bottom { margin-top: 30px; padding-top: 20px; border-top: 1px solid rgba(255,255,255,0.1); }
        
        .main-content { flex: 1; padding: 28px 32px; background: #f0f4f8; overflow-y: auto; height: 100vh; }
        .top-bar { display: flex; justify-content: space-between; align-items: center; padding: 12px 24px; background: white; border-radius: 16px; box-shadow: 0 2px 8px rgba(0,0,0,0.05); margin-bottom: 28px; gap: 15px; flex-wrap: wrap; }
        .page-title h2 { font-size: 1.3rem; font-weight: 600; color: #1e293b; margin: 0; }
        
        /* Search Bar Styling */
        .search-box { display: flex; gap: 5px; max-width: 350px; width: 100%; }
        .search-box input { border-radius: 10px 0 0 10px; border: 1px solid #cbd5e1; padding: 6px 12px; font-size: 14px; width: 100%; }
        .search-box button { border-radius: 0 10px 10px 0; background: #4f46e5; color: white; border: none; padding: 6px 15px; font-size: 14px; transition: 0.2s; }
        .search-box button:hover { background: #3b35b3; }
        
        .profile-card { background: white; border-radius: 24px; padding: 24px; margin-bottom: 28px; box-shadow: 0 4px 12px rgba(0,0,0,0.08); display: flex; align-items: center; gap: 30px; flex-wrap: wrap; }
        .profile-avatar { width: 100px; height: 100px; background: linear-gradient(135deg, #4f46e5, #fd0054); border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 48px; color: white; }
        .profile-info h3 { font-size: 24px; font-weight: 700; margin-bottom: 8px; }
        .profile-info .badge { background: #e0e7ff; color: #4f46e5; padding: 4px 12px; border-radius: 20px; font-size: 12px; font-weight: 600; }
        .info-grid { display: flex; gap: 40px; flex-wrap: wrap; margin-top: 12px; }
        .info-item { display: flex; align-items: center; gap: 8px; font-size: 14px; color: #475569; }
        
        .kpi-row { display: flex; flex-wrap: wrap; gap: 20px; margin-bottom: 32px; }
        .kpi-card { border-radius: 20px; padding: 20px 24px; flex: 1; min-width: 180px; box-shadow: 0 4px 12px rgba(0,0,0,0.08); transition: transform 0.3s ease; background: white; border-left: 6px solid; text-align: left; }
        .kpi-card:hover { transform: translateY(-3px); }
        .kpi-title { font-size: 13px; text-transform: uppercase; letter-spacing: 1px; opacity: 0.7; margin-bottom: 10px; font-weight: 600; }
        .kpi-number { font-size: 28px; font-weight: bold; margin-bottom: 5px; }
        
        .stats-row { display: flex; gap: 24px; margin-bottom: 28px; flex-wrap: wrap; }
        .stats-col { flex: 1; min-width: 280px; }
        .chart-box { background: white; padding: 20px; border-radius: 20px; box-shadow: 0 4px 12px rgba(0,0,0,0.05); height: 100%; }
        .chart-box h4 { font-size: 16px; font-weight: 600; margin-bottom: 20px; color: #1e293b; }
        
        .info-table { width: 100%; background: white; border-radius: 16px; overflow: hidden; }
        .info-table th, .info-table td { padding: 12px 16px; text-align: left; border-bottom: 1px solid #e2e8f0; }
        .info-table th { background: #f8fafc; font-weight: 600; color: #475569; }
        .score-high { color: #10b981; font-weight: 700; }
        .score-low { color: #ef4444; font-weight: 700; }
        .score-medium { color: #f59e0b; font-weight: 700; }
        
        canvas { max-height: 280px; width: 100%; }
        
        @media (max-width: 768px) {
            .sidebar { width: 70px; }
            .sidebar-header h1, .sidebar-header p, .nav-item span { display: none; }
            .nav-item { justify-content: center; }
            .main-content { padding: 15px; }
            .profile-card { flex-direction: column; text-align: center; }
            .info-grid { justify-content: center; }
            .top-bar { flex-direction: column; align-items: flex-start; }
            .search-box { max-width: 100%; }
        }
    </style>
</head>
<body>

<div class="container">
    <div class="sidebar">
        <div class="sidebar-header">
            <img src="https://i.pinimg.com/736x/be/dd/b8/beddb8c8c3c4c967cb821aae0cb796e3.jpg" alt="Logo" />
            <h1><?php 
          
                $log_query = mysqli_query($db_conn, "SELECT name FROM students WHERE id = '$logged_in_user_id'");
                $log_user = mysqli_fetch_assoc($log_query);
                echo htmlspecialchars($log_user['name'] ?? 'Student'); 
            ?></h1>
            <p>Student Dashboard</p>
        </div>
        <div class="nav-menu">
            <a href="index.php" class="nav-item active"><i class="fas fa-tachometer-alt"></i> <span>Main Dashboard</span></a>
            <!-- <a href="teacher_fortea.php" class="nav-item"><i class="fas fa-chalkboard-teacher"></i> <span>Teachers</span></a> -->
            <a href="courses_stu.php" class="nav-item"><i class="fas fa-graduation-cap"></i> <span>Department</span></a>
            <!-- <a href="attendance_top_teacher.php" class="nav-item"><i class="fas fa-calendar-check"></i> <span>Attendance</span></a> -->
            
            <div class="nav-bottom">
                <a href="logout.php" class="nav-item" style="padding-left:8px;">
                    <i class="fas fa-sign-out-alt"></i> <span>Logout</span>
                </a>
            </div>
        </div>
    </div>

    <div class="main-content">
        <div class="top-bar">
            <div class="page-title"><h2><i class="fas fa-graduation-cap me-2"></i>My Dashboard</h2></div>
            
            <form method="GET" action="" class="search-box">
                <input type="text" name="search_id" placeholder="Search by Student ID..." value="<?php echo isset($_GET['search_id']) ? htmlspecialchars($_GET['search_id']) : ''; ?>">
                <button type="submit"><i class="fas fa-search"></i></button>
            </form>
            
            <div class="date-time" id="currentDateTime"></div>
        </div>
        
        <?php if($search_error): ?>
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <i class="fas fa-exclamation-triangle me-2"></i>  ID Not fount! 
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>
        
        <div class="profile-card">
            <div class="profile-avatar">
                <i class="fas fa-user-graduate"></i>
            </div>
            <div class="profile-info">
                <h3><?php echo htmlspecialchars($student['name']); ?></h3>
                <span class="badge">ID: <?php echo htmlspecialchars($student['id']); ?></span>
                <div class="info-grid">
                    <div class="info-item"><i class="fas fa-envelope"></i> <?php echo htmlspecialchars($student['email'] ?? 'N/A'); ?></div>
                    <div class="info-item"><i class="fas fa-phone"></i> <?php echo htmlspecialchars($student['phone'] ?? 'N/A'); ?></div>
                    <div class="info-item"><i class="fas fa-venus-mars"></i> <?php echo htmlspecialchars($student['gender'] ?? 'N/A'); ?></div>
                    <div class="info-item"><i class="fas fa-calendar-alt"></i> DOB: <?php echo htmlspecialchars($student['dob'] ?? 'N/A'); ?></div>
                    <div class="info-item"><i class="fas fa-school"></i> Grade: <?php echo htmlspecialchars($student['grade'] ?? 'N/A'); ?></div>
                </div>
            </div>
        </div>
        
        <div class="kpi-row">
            <div class="kpi-card" style="border-left-color: #4f46e5;">
                <div class="kpi-title"><i class="fas fa-chart-line"></i> Performance</div>
                <div class="kpi-number" id="gpaValue">--</div>
                <small>Average Score</small>
            </div>
            <div class="kpi-card" style="border-left-color: #10b981;">
                <div class="kpi-title"><i class="fas fa-calendar-check"></i> Attendance Rate</div>
                <div class="kpi-number"><?php echo $attendance_rate; ?>%</div>
                <small>This month (Status 'P')</small>
            </div>
            <div class="kpi-card" style="border-left-color: #f59e0b;">
                <div class="kpi-title"><i class="fas fa-book"></i> Enrolled Courses</div>
                <div class="kpi-number" id="coursesCount">--</div>
                <small>Active classes</small>
            </div>
            <div class="kpi-card" style="border-left-color: #ef4444;">
                <div class="kpi-title"><i class="fas fa-clock"></i> Shift</div>
                <div class="kpi-number" style="font-size: 20px; padding-top: 8px;"><?php echo htmlspecialchars($student['Shift'] ?? 'N/A'); ?></div>
                <small>Study Time</small>
            </div>
        </div>
        
        <div class="stats-row">
            <div class="stats-col">
                <div class="chart-box">
                    <h4><i class="fas fa-chart-pie"></i> My Performance by Subject</h4>
                    <canvas id="scoresChart" height="250"></canvas>
                </div>
            </div>
            <div class="stats-col">
                <div class="chart-box">
                    <h4><i class="fas fa-info-circle"></i> Study Info Summary</h4>
                    <p><strong>College/Major:</strong> <?php echo htmlspecialchars($student['college'] ?? 'N/A'); ?></p>
                    <p><strong>Skill/Focus:</strong> <?php echo htmlspecialchars($student['skill'] ?? 'N/A'); ?></p>
                    <p><strong>Year:</strong> <?php echo htmlspecialchars($student['year'] ?? 'N/A'); ?></p>
                    <p><strong>Address:</strong> <?php echo htmlspecialchars($student['address'] ?? 'N/A'); ?></p>
                </div>
            </div>
        </div>
        
        <div class="stats-row">
            <div class="stats-col">
                <div class="chart-box">
                    <h4><i class="fas fa-star"></i> Recent Exam Scores</h4>
                    <table class="info-table">
                        <thead>
                            <tr><th>Course ID</th><th>Score</th></tr>
                        </thead>
                        <tbody>
                            <?php 
                            if($scores_result && mysqli_num_rows($scores_result) > 0):
                                $total_score = 0;
                                $score_count = 0;
                                while($score = mysqli_fetch_assoc($scores_result)): 
                                    $total_score += $score['score'];
                                    $score_count++;
                                    $scoreClass = $score['score'] >= 80 ? 'score-high' : ($score['score'] >= 50 ? 'score-medium' : 'score-low');
                            ?>
                            <tr>
                                <td>Course #<?php echo htmlspecialchars($score['course_id']); ?></td>
                                <td class="<?php echo $scoreClass; ?>"><?php echo $score['score']; ?></td>
                            </tr>
                            <?php 
                                endwhile;
                                $avg_score = $score_count > 0 ? round($total_score / $score_count, 2) : 0;
                            else:
                                $avg_score = 0;
                            ?>
                            <tr><td colspan="2" class="text-center text-muted">No scores yet</td></tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
            <div class="stats-col">
                <div class="chart-box">
                    <h4><i class="fas fa-book-open"></i> My Enrolled Courses</h4>
                    <table class="info-table">
                        <thead><tr><th>Course ID</th><th>Enrolled Date</th></tr></thead>
                        <tbody>
                            <?php 
                            $course_count = 0;
                            if($enroll_result && mysqli_num_rows($enroll_result) > 0):
                                while($enroll = mysqli_fetch_assoc($enroll_result)): 
                                    $course_count++;
                            ?>
                            <tr>
                                <td>Course #<?php echo htmlspecialchars($enroll['course_id']); ?></td>
                                <td><?php echo (!empty($enroll['enroll_date'])) ? date('d/m/Y', strtotime($enroll['enroll_date'])) : 'N/A'; ?></td>
                            </tr>
                            <?php 
                                endwhile;
                            else:
                            ?>
                            <tr><td colspan="2" class="text-center text-muted">No courses enrolled</td></tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

<script>
    document.getElementById('gpaValue').innerText = "<?php echo $avg_score; ?>";
    document.getElementById('coursesCount').innerText = "<?php echo $course_count; ?>";
    
    function updateDateTime() {
        const now = new Date();
        const options = { weekday: 'long', year: 'numeric', month: 'long', day: 'numeric', hour: '2-digit', minute: '2-digit' };
        document.getElementById('currentDateTime').innerHTML = now.toLocaleDateString('en-US', options);
    }
    updateDateTime();
    setInterval(updateDateTime, 60000);
    
    const ctx = document.getElementById('scoresChart').getContext('2d');
    new Chart(ctx, {
        type: 'bar',
        data: {
            labels: [
                <?php 
                if($scores_result && mysqli_num_rows($scores_result) > 0) {
                    mysqli_data_seek($scores_result, 0); 
                    while($score = mysqli_fetch_assoc($scores_result)) {
                        echo "'Course #" . $score['course_id'] . "',";
                    }
                } else {
                    echo "'No Data'";
                }
                ?>
            ],
            datasets: [{
                label: 'Score',
                data: [
                    <?php 
                    if($scores_result && mysqli_num_rows($scores_result) > 0) {
                        mysqli_data_seek($scores_result, 0);
                        while($score = mysqli_fetch_assoc($scores_result)) {
                            echo $score['score'] . ",";
                        }
                    } else {
                        echo "0";
                    }
                    ?>
                ],
                backgroundColor: '#4f46e5',
                borderRadius: 8
            }]
        },
        options: {
            responsive: true,
            scales: { y: { beginAtZero: true, max: 100 } }
        }
    });
</script>
</body>
</html>