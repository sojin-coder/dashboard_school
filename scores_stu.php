<?php

include "student_auth.php";


$student_query = "SELECT * FROM students WHERE id = '$logged_in_id'";
$student_result = mysqli_query($db_conn, $student_query);
$student = mysqli_fetch_assoc($student_result);


if (!$student) {
    $student = [
        'id' => $logged_in_id,
        'name' => $logged_in_student,
        'email' => $student_email_db,
        'college' => $student_department,
        'grade' => $student_grade,
        'Shift' => $student_shift,
        'image' => $student_image
    ];
}


$scores_query = "SELECT * FROM scores WHERE student_id = '$logged_in_id' ORDER BY subject ASC";
$scores_result = mysqli_query($db_conn, $scores_query);

$total_score = 0;
$count = 0;
$subject_count = [];
$scores_data = [];

// អថេរសម្រាប់គណនា Midterm, Final និង GPA
$midterm_scores = [];
$final_scores = [];
$gpa_data = [];
$total_credit_hours = 0;
$total_grade_points = 0;

if ($scores_result && mysqli_num_rows($scores_result) > 0) {
    while ($score = mysqli_fetch_assoc($scores_result)) {
        $scores_data[] = $score;
        $total_score += $score['score'];
        $count++;
        $subject_count[$score['subject']] = ($subject_count[$score['subject']] ?? 0) + 1;
        
        // ប្រមូលទិន្នន័យសម្រាប់ Midterm, Final និង GPA
        if (isset($score['type'])) {
            $type = strtolower(trim($score['type']));
            $subject = $score['subject'];
            $score_val = (float)$score['score'];
            
            // ប្រមូល Midterm និង Final ពិន្ទុ
            if ($type == 'midterm' || $type == 'mid term' || $type == 'mid-term') {
                if (!isset($midterm_scores[$subject])) {
                    $midterm_scores[$subject] = [];
                }
                $midterm_scores[$subject][] = $score_val;
            } elseif ($type == 'final' || $type == 'final exam' || $type == 'final-examination') {
                if (!isset($final_scores[$subject])) {
                    $final_scores[$subject] = [];
                }
                $final_scores[$subject][] = $score_val;
            }
            
            // គណនា GPA (ប្រើប្រាស់ Credit Hours ប្រសិនបើមាន)
            $credit_hours = isset($score['credit_hours']) ? (float)$score['credit_hours'] : 3; // default 3 credit hours
            $grade_point = 0;
            
            // បំប្លែងពិន្ទុទៅជា Grade Points
            if ($score_val >= 90) $grade_point = 4.0;
            elseif ($score_val >= 85) $grade_point = 3.7;
            elseif ($score_val >= 80) $grade_point = 3.3;
            elseif ($score_val >= 75) $grade_point = 3.0;
            elseif ($score_val >= 70) $grade_point = 2.7;
            elseif ($score_val >= 65) $grade_point = 2.3;
            elseif ($score_val >= 60) $grade_point = 2.0;
            elseif ($score_val >= 55) $grade_point = 1.7;
            elseif ($score_val >= 50) $grade_point = 1.0;
            else $grade_point = 0.0;
            
            $gpa_data[] = [
                'subject' => $subject,
                'score' => $score_val,
                'credit_hours' => $credit_hours,
                'grade_point' => $grade_point
            ];
            
            $total_credit_hours += $credit_hours;
            $total_grade_points += ($grade_point * $credit_hours);
        }
    }
}

// គណនា GPA
$gpa = $total_credit_hours > 0 ? round($total_grade_points / $total_credit_hours, 2) : 0;

// គណនាពិន្ទុសរុប Midterm និង Final តាមមុខវិជ្ជា
$midterm_totals = [];
$final_totals = [];

foreach ($midterm_scores as $subject => $scores) {
    $midterm_totals[$subject] = array_sum($scores);
}

foreach ($final_scores as $subject => $scores) {
    $final_totals[$subject] = array_sum($scores);
}

// គណនាពិន្ទុសរុប (Midterm + Final) តាមមុខវិជ្ជា
$subject_total_scores = [];
$all_subjects = array_unique(array_merge(array_keys($midterm_totals), array_keys($final_totals)));

foreach ($all_subjects as $subject) {
    $mid = $midterm_totals[$subject] ?? 0;
    $fin = $final_totals[$subject] ?? 0;
    $subject_total_scores[$subject] = [
        'midterm' => $mid,
        'final' => $fin,
        'total' => $mid + $fin,
        'average' => ($mid + $fin) / 2
    ];
}

// គណនាពិន្ទុសរុបទាំងអស់ Midterm + Final
$total_midterm_all = array_sum($midterm_totals);
$total_final_all = array_sum($final_totals);
$total_all_scores = $total_midterm_all + $total_final_all;
$count_midterm = count($midterm_totals);
$count_final = count($final_totals);

$avg_midterm = $count_midterm > 0 ? round($total_midterm_all / $count_midterm, 2) : 0;
$avg_final = $count_final > 0 ? round($total_final_all / $count_final, 2) : 0;
$avg_total = ($count_midterm + $count_final) > 0 ? round($total_all_scores / ($count_midterm + $count_final), 2) : 0;

$avg_score = $count > 0 ? round($total_score / $count, 2) : 0;

// Get highest score
$highest = 0;
foreach ($scores_data as $score) {
    if ($score['score'] > $highest) $highest = $score['score'];
}

// Get grade distribution
$grade_distribution = [
    'A' => 0, 'B' => 0, 'C' => 0, 'D' => 0, 'F' => 0
];

// Check if Grade column exists and has values
$grade_columns = ['Grade', 'grade', 'grade_level'];
$grade_found = false;

foreach ($scores_data as $score) {
    foreach ($grade_columns as $col) {
        if (isset($score[$col]) && !empty($score[$col])) {
            $grade_found = true;
            $grade_letter = strtoupper($score[$col]);
            if (isset($grade_distribution[$grade_letter])) {
                $grade_distribution[$grade_letter]++;
            }
            break;
        }
    }
}

// If no grade column found, calculate grade from score
if (!$grade_found) {
    foreach ($scores_data as $score) {
        if ($score['score'] >= 90) $grade_distribution['A']++;
        elseif ($score['score'] >= 80) $grade_distribution['B']++;
        elseif ($score['score'] >= 70) $grade_distribution['C']++;
        elseif ($score['score'] >= 60) $grade_distribution['D']++;
        else $grade_distribution['F']++;
    }
}

// គណនាថ្នាក់សរុបពី GPA
$overall_grade = '';
if ($gpa >= 3.7) $overall_grade = 'A';
elseif ($gpa >= 3.3) $overall_grade = 'B+';
elseif ($gpa >= 3.0) $overall_grade = 'B';
elseif ($gpa >= 2.7) $overall_grade = 'C+';
elseif ($gpa >= 2.0) $overall_grade = 'C';
elseif ($gpa >= 1.0) $overall_grade = 'D';
else $overall_grade = 'F';

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
    <title>My Scores - KRaksa Education</title>
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
        .kpi-card { border-radius: 20px; padding: 20px 24px; flex: 1; min-width: 180px; box-shadow: 0 4px 12px rgba(0,0,0,0.08); transition: transform 0.3s ease; background: white; border-left: 6px solid; text-align: left; }
        .kpi-card:hover { transform: translateY(-3px); }
        .kpi-title { font-size: 13px; text-transform: uppercase; letter-spacing: 1px; opacity: 0.7; margin-bottom: 10px; font-weight: 600; }
        .kpi-number { font-size: 28px; font-weight: bold; margin-bottom: 5px; }
        
        .gpa-box {
            background: linear-gradient(135deg, #4f46e5, #7c3aed);
            color: white;
            border-radius: 20px;
            padding: 20px 24px;
            flex: 1;
            min-width: 180px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.08);
            transition: transform 0.3s ease;
            text-align: center;
        }
        .gpa-box:hover { transform: translateY(-3px); }
        .gpa-number { font-size: 36px; font-weight: 800; }
        .gpa-grade { font-size: 18px; opacity: 0.9; }
        
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
        .grade-badge { display: inline-block; padding: 2px 12px; border-radius: 12px; font-weight: bold; color: white; }
        .grade-a { background: #10b981; }
        .grade-b { background: #3b82f6; }
        .grade-c { background: #f59e0b; }
        .grade-d { background: #f97316; }
        .grade-f { background: #ef4444; }
        
        .subject-total-card {
            background: #f8fafc;
            border-radius: 12px;
            padding: 15px 20px;
            margin-bottom: 10px;
            border-left: 4px solid #4f46e5;
        }
        .subject-total-card .subject-name { font-weight: 600; }
        .subject-total-card .score-detail { font-size: 14px; color: #64748b; }
        .subject-total-card .total-score { font-weight: 700; color: #1e293b; }
        
        canvas { max-height: 250px; width: 100%; }
        
        @media (max-width: 768px) {
            .sidebar { width: 70px; }
            .sidebar-header h1, .sidebar-header p, .nav-item span { display: none; }
            .nav-item { justify-content: center; }
            .main-content { padding: 15px; }
            .top-bar { flex-direction: column; align-items: flex-start; }
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
            <a href="scores_stu.php" class="nav-item active"><i class="fas fa-graduation-cap"></i> <span>Scores</span></a>
            <a href="attendance_student.php" class="nav-item"><i class="fas fa-calendar-check"></i> <span>Attendance</span></a>
            <a href="Requests.php" class="nav-item"><i class="fas fa-file-signature"></i> <span>Request</span></a>
            <a href="settings_student.php" class="nav-item"><i class="fas fa-cog"></i> <span>Setting</span></a>
            <div class="nav-bottom">
                <a href="logout.php" class="nav-item" style="padding-left:8px;">
                    <i class="fas fa-sign-out-alt"></i> <span>Logout</span>
                </a>
            </div>
        </div>
    </div>

    <div class="main-content">
        <div class="top-bar">
            <div class="page-title"><h2><i class="fas fa-star me-2"></i>My Scores</h2></div>
            <div class="date-time" id="currentDateTime"></div>
        </div>
        
        <!-- KPI CARDS + GPA -->
        <div class="kpi-row">
            <div class="kpi-card" style="border-left-color: #4f46e5;">
                <div class="kpi-title"><i class="fas fa-chart-line"></i> Average Score</div>
                <div class="kpi-number"><?php echo $avg_score; ?></div>
                <small>Overall academic performance</small>
            </div>
            <div class="kpi-card" style="border-left-color: #10b981;">
                <div class="kpi-title"><i class="fas fa-trophy"></i> Highest Score</div>
                <div class="kpi-number"><?php echo $highest; ?></div>
                <small>Best performance</small>
            </div>
            <div class="kpi-card" style="border-left-color: #f59e0b;">
                <div class="kpi-title"><i class="fas fa-book"></i> Subjects Taken</div>
                <div class="kpi-number"><?php echo count($subject_count); ?></div>
                <small>Total subjects</small>
            </div>
            <div class="kpi-card" style="border-left-color: #ef4444;">
                <div class="kpi-title"><i class="fas fa-clock"></i> Total Exams</div>
                <div class="kpi-number"><?php echo $count; ?></div>
                <small>Number of exams taken</small>
            </div>
            <div class="gpa-box">
                <div class="kpi-title" style="color: rgba(255,255,255,0.8);"><i class="fas fa-calculator"></i> Annual GPA</div>
                <div class="gpa-number"><?php echo $gpa; ?></div>
                <div class="gpa-grade">
                    <span class="badge bg-light text-dark">Grade: <?php echo $overall_grade; ?></span>
                </div>
                <small style="opacity:0.8;">Credit Hours: <?php echo $total_credit_hours; ?></small>
            </div>
        </div>
        
        <!-- MIDTERM & FINAL SUMMARY -->
        <div class="kpi-row">
            <div class="kpi-card" style="border-left-color: #8b5cf6; width: 100%;">
                <div class="kpi-title">
                    <i class="fas fa-chart-simple"></i> 
                    Midterm & Final Score Summary
                </div>
                <div class="row mb-3">
                    <div class="col-md-3 col-6">
                        <div class="p-2 border rounded text-center" style="background: #eff6ff;">
                            <strong class="text-primary">Midterm Total</strong>
                            <h4><?php echo $total_midterm_all; ?></h4>
                            <small>Avg: <?php echo $avg_midterm; ?></small>
                        </div>
                    </div>
                    <div class="col-md-3 col-6">
                        <div class="p-2 border rounded text-center" style="background: #fef3c7;">
                            <strong class="text-warning">Final Total</strong>
                            <h4><?php echo $total_final_all; ?></h4>
                            <small>Avg: <?php echo $avg_final; ?></small>
                        </div>
                    </div>
                    <div class="col-md-3 col-6">
                        <div class="p-2 border rounded text-center" style="background: #d1fae5;">
                            <strong class="text-success">Combined Total</strong>
                            <h4><?php echo $total_all_scores; ?></h4>
                            <small>Avg: <?php echo $avg_total; ?></small>
                        </div>
                    </div>
                    <div class="col-md-3 col-6">
                        <div class="p-2 border rounded text-center" style="background: #ede9fe;">
                            <strong class="text-purple">Overall Grade</strong>
                            <h4><?php echo $overall_grade; ?></h4>
                            <small>GPA: <?php echo $gpa; ?></small>
                        </div>
                    </div>
                </div>
                
                <h6 class="mt-3"><i class="fas fa-list-ul"></i> Score Breakdown by Subject</h6>
                <div class="row">
                    <?php foreach ($subject_total_scores as $subject => $scores): ?>
                    <div class="col-md-4 col-lg-3">
                        <div class="subject-total-card">
                            <div class="subject-name"><?php echo htmlspecialchars($subject); ?></div>
                            <div class="score-detail">
                                <span class="text-primary">Mid: <?php echo $scores['midterm']; ?></span> | 
                                <span class="text-warning">Fin: <?php echo $scores['final']; ?></span>
                            </div>
                            <div class="total-score">
                                Total: <?php echo $scores['total']; ?> 
                                <span class="badge bg-secondary">Avg: <?php echo round($scores['average'], 1); ?></span>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                    <?php if (empty($subject_total_scores)): ?>
                    <div class="col-12 text-muted text-center">
                        <i class="fas fa-info-circle"></i> No Midterm/Final data available
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        
        <!-- SCORES TABLE -->
        <div class="kpi-row">
            <div class="kpi-card" style="border-left-color: #8b5cf6; width: 100%;">
                <div class="kpi-title">
                    <i class="fas fa-list"></i> 
                    All Scores by Subject
                    <span class="badge bg-primary ms-2">Average: <?php echo $avg_score; ?></span>
                    <span class="badge bg-success ms-2">Highest: <?php echo $highest; ?></span>
                </div>
                <div class="table-responsive">
                    <table class="table table-bordered">
                        <thead>
                            <tr>
                                <th>#</th>
                                <th>Subject</th>
                                <th>Score</th>
                                <th>Grade</th>
                                <th>Type</th>
                                <th>Semester</th>
                                <th>Academic Year</th>
                                <th>Credit Hrs</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php 
                            $count_row = 1;
                            if (!empty($scores_data)):
                                foreach ($scores_data as $score):
                                    $scoreClass = $score['score'] >= 80 ? 'score-high' : 
                                                 ($score['score'] >= 50 ? 'score-medium' : 'score-low');
                                    
                                    $grade_value = 'N/A';
                                    $grade_columns = ['Grade', 'grade', 'grade_level'];
                                    foreach ($grade_columns as $col) {
                                        if (isset($score[$col]) && !empty($score[$col])) {
                                            $grade_value = $score[$col];
                                            break;
                                        }
                                    }
                                    
                                    if ($grade_value == 'N/A') {
                                        if ($score['score'] >= 90) $grade_value = 'A';
                                        elseif ($score['score'] >= 80) $grade_value = 'B';
                                        elseif ($score['score'] >= 70) $grade_value = 'C';
                                        elseif ($score['score'] >= 60) $grade_value = 'D';
                                        else $grade_value = 'F';
                                    }
                                    
                                    $gradeClass = strtolower($grade_value);
                            ?>
                            <tr>
                                <td><?php echo $count_row++; ?></td>
                                <td><strong><?php echo htmlspecialchars($score['subject']); ?></strong></td>
                                <td class="<?php echo $scoreClass; ?>"><?php echo htmlspecialchars($score['score']); ?></td>
                                <td>
                                    <span class="grade-badge grade-<?php echo $gradeClass; ?>">
                                        <?php echo htmlspecialchars($grade_value); ?>
                                    </span>
                                </td>
                                <td>
                                    <?php 
                                    $type = strtolower(trim($score['type'] ?? ''));
                                    if (strpos($type, 'mid') !== false) {
                                        echo '<span class="badge bg-primary">Midterm</span>';
                                    } elseif (strpos($type, 'final') !== false) {
                                        echo '<span class="badge bg-warning text-dark">Final</span>';
                                    } else {
                                        echo htmlspecialchars($score['type'] ?? 'N/A');
                                    }
                                    ?>
                                </td>
                                <td><?php echo htmlspecialchars($score['semester'] ?? 'N/A'); ?></td>
                                <td><?php echo htmlspecialchars($score['academic_year'] ?? 'N/A'); ?></td>
                                <td><?php echo htmlspecialchars($score['credit_hours'] ?? '3'); ?></td>
                            </tr>
                            <?php 
                                endforeach;
                            else:
                            ?>
                            <tr>
                                <td colspan="8" class="text-center text-muted">
                                    <i class="fas fa-info-circle"></i> No scores available
                                </td>
                            </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        
        <!-- CHARTS -->
        <div class="stats-row">
            <div class="stats-col">
                <div class="chart-box">
                    <h4><i class="fas fa-chart-bar"></i> Scores by Subject</h4>
                    <canvas id="scoresChart"></canvas>
                </div>
            </div>
            <div class="stats-col">
                <div class="chart-box">
                    <h4><i class="fas fa-chart-pie"></i> Grade Distribution</h4>
                    <canvas id="gradeChart"></canvas>
                </div>
            </div>
        </div>
        
        <!-- GRADE SUMMARY -->
        <div class="kpi-row">
            <div class="kpi-card" style="border-left-color: #10b981; width: 100%;">
                <div class="kpi-title">
                    <i class="fas fa-chart-simple"></i> 
                    Grade Summary
                </div>
                <div class="row">
                    <div class="col-md-2 col-6">
                        <div class="text-center p-2 border rounded m-1" style="background: #f0fdf4;">
                            <h3 class="text-success"><?php echo $grade_distribution['A']; ?></h3>
                            <small>A (Excellent)</small>
                        </div>
                    </div>
                    <div class="col-md-2 col-6">
                        <div class="text-center p-2 border rounded m-1" style="background: #eff6ff;">
                            <h3 class="text-primary"><?php echo $grade_distribution['B']; ?></h3>
                            <small>B (Good)</small>
                        </div>
                    </div>
                    <div class="col-md-2 col-6">
                        <div class="text-center p-2 border rounded m-1" style="background: #fffbeb;">
                            <h3 class="text-warning"><?php echo $grade_distribution['C']; ?></h3>
                            <small>C (Average)</small>
                        </div>
                    </div>
                    <div class="col-md-2 col-6">
                        <div class="text-center p-2 border rounded m-1" style="background: #fff7ed;">
                            <h3 class="text-orange" style="color: #f97316;"><?php echo $grade_distribution['D']; ?></h3>
                            <small>D (Below Average)</small>
                        </div>
                    </div>
                    <div class="col-md-2 col-6">
                        <div class="text-center p-2 border rounded m-1" style="background: #fef2f2;">
                            <h3 class="text-danger"><?php echo $grade_distribution['F']; ?></h3>
                            <small>F (Fail)</small>
                        </div>
                    </div>
                    <div class="col-md-2 col-6">
                        <div class="text-center p-2 border rounded m-1" style="background: #ede9fe;">
                            <h3 class="text-purple" style="color: #7c3aed;"><?php echo $gpa; ?></h3>
                            <small>GPA</small>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

<script>
    // Update date/time
    function updateDateTime() {
        const now = new Date();
        const options = { weekday: 'long', year: 'numeric', month: 'long', day: 'numeric', hour: '2-digit', minute: '2-digit' };
        document.getElementById('currentDateTime').innerHTML = now.toLocaleDateString('en-US', options);
    }
    updateDateTime();
    setInterval(updateDateTime, 60000);
    
    // Scores Chart
    const ctx = document.getElementById('scoresChart').getContext('2d');
    const scoresData = <?php 
        $labels = [];
        $values = [];
        $colors = [];
        foreach ($scores_data as $score) {
            $labels[] = $score['subject'] . ' (' . ($score['type'] ?? '') . ')';
            $values[] = (int)$score['score'];
            if ($score['score'] >= 80) $colors[] = '#10b981';
            elseif ($score['score'] >= 60) $colors[] = '#3b82f6';
            elseif ($score['score'] >= 50) $colors[] = '#f59e0b';
            else $colors[] = '#ef4444';
        }
        echo json_encode(['labels' => $labels, 'values' => $values, 'colors' => $colors]);
    ?>;
    
    new Chart(ctx, {
        type: 'bar',
        data: {
            labels: scoresData.labels.length > 0 ? scoresData.labels : ['No Data'],
            datasets: [{
                label: 'Score',
                data: scoresData.values.length > 0 ? scoresData.values : [0],
                backgroundColor: scoresData.colors.length > 0 ? scoresData.colors : ['#4f46e5'],
                borderRadius: 8
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: true,
            scales: { 
                y: { 
                    beginAtZero: true, 
                    max: 100,
                    title: { display: true, text: 'Score' }
                }
            },
            plugins: { 
                legend: { display: false },
                tooltip: {
                    callbacks: {
                        label: function(context) {
                            return 'Score: ' + context.parsed.y + '/100';
                        }
                    }
                }
            }
        }
    });
    
    // Grade Distribution Chart
    const gradeCtx = document.getElementById('gradeChart').getContext('2d');
    const gradeData = <?php echo json_encode($grade_distribution); ?>;
    
    new Chart(gradeCtx, {
        type: 'pie',
        data: {
            labels: ['A (Excellent)', 'B (Good)', 'C (Average)', 'D (Below Avg)', 'F (Fail)'],
            datasets: [{
                data: [gradeData.A, gradeData.B, gradeData.C, gradeData.D, gradeData.F],
                backgroundColor: ['#10b981', '#3b82f6', '#f59e0b', '#f97316', '#ef4444'],
                borderWidth: 2,
                borderColor: '#ffffff'
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: true,
            plugins: {
                legend: {
                    position: 'bottom',
                    labels: {
                        padding: 15,
                        usePointStyle: true,
                        pointStyle: 'circle'
                    }
                },
                tooltip: {
                    callbacks: {
                        label: function(context) {
                            let total = context.dataset.data.reduce((a, b) => a + b, 0);
                            let percentage = total > 0 ? Math.round((context.parsed / total) * 100) : 0;
                            return context.label + ': ' + context.parsed + ' (' + percentage + '%)';
                        }
                    }
                }
            }
        }
    });
</script>
</body>
</html>