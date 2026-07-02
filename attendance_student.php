<?php

     include "db.php";
     // attendance_student.php
     if (session_status() === PHP_SESSION_NONE) {
         session_start();
     }
     
     
     
     // ត្រួតពិនិត្យថាអ្នកប្រើបាន Login ឬនៅ
     if (!isset($_SESSION['student_logged_in']) || $_SESSION['student_logged_in'] !== true) {
         header("Location: login.php");
         exit();
     }
     
     // ត្រួតពិនិត្យថាជា Student ឬនៅ
     if ($_SESSION['role'] !== 'student') {
         header("Location: " . ($_SESSION['role'] == 'admin' ? 'index.php' : 'forteacher.php'));
         exit();
     }
     
     // ទាញទិន្នន័យសិស្ស
     $student_id = $_SESSION['id'];
     $student_name = $_SESSION['name'] ?? 'Student';
     $student_image = $_SESSION['student_image'] ?? 'https://i.pinimg.com/736x/be/dd/b8/beddb8c8c3c4c967cb821aae0cb796e3.jpg';
     
     $attendance_query = "SELECT * FROM attendance 
                           WHERE student_id = '$student_id' 
                           ORDER BY date DESC, time DESC";
     $attendance_result = mysqli_query($conn, $attendance_query);
     
     $attendance_records = [];
     $total_present = 0;
     $total_absent = 0;
     $total_late = 0;
     $total_excused = 0;
     $total_days = 0;
     
     if ($attendance_result && mysqli_num_rows($attendance_result) > 0) {
         while ($row = mysqli_fetch_assoc($attendance_result)) {
             $attendance_records[] = $row;
             $total_days++;
             
             $status = strtolower($row['status'] ?? '');
             if ($status == 'present' || $status == 'present') {
                 $total_present++;
             } elseif ($status == 'absent' || $status == 'absent') {
                 $total_absent++;
             } elseif ($status == 'late' || $status == 'late') {
                 $total_late++;
             } elseif ($status == 'excused' || $status == 'excused') {
                 $total_excused++;
             }
         }
     }
     
     // 2. គណនាភាគរយ
     $attendance_percent = $total_days > 0 ? round(($total_present / $total_days) * 100, 2) : 0;
     
     // 3. ទាញស្ថិតិតាមខែ
     $monthly_stats = [];
     $monthly_query = "SELECT 
                         DATE_FORMAT(date, '%Y-%m') as month,
                         COUNT(*) as total,
                         SUM(CASE WHEN LOWER(status) = 'present' THEN 1 ELSE 0 END) as present,
                         SUM(CASE WHEN LOWER(status) = 'absent' THEN 1 ELSE 0 END) as absent,
                         SUM(CASE WHEN LOWER(status) = 'late' THEN 1 ELSE 0 END) as late,
                         SUM(CASE WHEN LOWER(status) = 'excused' THEN 1 ELSE 0 END) as excused
                       FROM attendance 
                       WHERE student_id = '$student_id' 
                       GROUP BY DATE_FORMAT(date, '%Y-%m')
                       ORDER BY month DESC";
     $monthly_result = mysqli_query($conn, $monthly_query);
     if ($monthly_result) {
         while ($row = mysqli_fetch_assoc($monthly_result)) {
             $monthly_stats[] = $row;
         }
     }
     
     // 4. ទាញស្ថិតិតាមមុខវិជ្ជា (បើមាន)
     $subject_stats = [];
     $subject_query = "SELECT 
                         subject,
                         COUNT(*) as total,
                         SUM(CASE WHEN LOWER(status) = 'present' THEN 1 ELSE 0 END) as present,
                         SUM(CASE WHEN LOWER(status) = 'absent' THEN 1 ELSE 0 END) as absent,
                         SUM(CASE WHEN LOWER(status) = 'late' THEN 1 ELSE 0 END) as late
                       FROM attendance 
                       WHERE student_id = '$student_id' 
                       GROUP BY subject
                       ORDER BY subject";
     $subject_result = mysqli_query($conn, $subject_query);
     if ($subject_result) {
         while ($row = mysqli_fetch_assoc($subject_result)) {
             $subject_stats[] = $row;
         }
     }
     
     // 5. ទាញព័ត៌មានច្បាប់ (ប្រសិនបើមានតារាង rules)
     $rules = [];
     $rules_query = "SELECT * FROM attendance_rules WHERE status = 'active' ORDER BY id";
     $rules_result = mysqli_query($conn, $rules_query);
     if ($rules_result && mysqli_num_rows($rules_result) > 0) {
         while ($row = mysqli_fetch_assoc($rules_result)) {
             $rules[] = $row;
         }
     }
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>My Attendance - KRaksa Education</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        a { text-decoration: none; }
        body { font-family: "Inter", sans-serif; background: #c5e1fc; color: #0f172a; overflow-x: hidden; }
        
        .container { display: flex; min-height: 100vh; max-width: 100%; }
        
        .sidebar { 
            width: 280px; 
            background: linear-gradient(90deg,rgba(117, 82, 243, 1) 19%, rgba(64, 24, 157, 1) 95%);
            color: #e2e8f0; flex-shrink: 0; position: sticky; top: 0; height: 100vh; overflow-y: auto; margin-left: -12px;
            box-shadow: 4px 0 20px rgba(0, 0, 0, 0.08); z-index: 10;
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
        .sidebar-header h1 { font-size: 1.3rem; font-weight: 700; color: white; margin-top: 8px; }
        .sidebar-header p { font-size: 0.85rem; opacity: 0.8; }
        
        .nav-menu { flex: 1; padding: 0 16px; }
        .nav-item { display: flex; align-items: center; gap: 15px; padding: 12px 16px; margin-bottom: 8px; border-radius: 14px; cursor: pointer; transition: 0.3s; color: #cbd5e6; text-decoration: none; }
        .nav-item:hover { background: rgba(255, 255, 255, 0.1); color: white; }
        .nav-item.active { background: #fd0054; color: white; box-shadow: 0 4px 8px rgba(0, 0, 0, 0.2); }
        .nav-bottom { margin-top: 30px; padding-top: 20px; border-top: 1px solid rgba(255,255,255,0.1); }
        
        .main-content { flex: 1; padding: 28px 32px; background: #f0f4f8; overflow-y: auto; height: 100vh; }
        
        .top-bar { display: flex; justify-content: space-between; align-items: center; padding: 12px 24px; background: white; border-radius: 16px; box-shadow: 0 2px 8px rgba(0,0,0,0.05); margin-bottom: 28px; flex-wrap: wrap; gap: 15px; }
        .page-title h2 { font-size: 1.3rem; font-weight: 600; color: #1e293b; margin: 0; }
        
        /* KPI Cards */
        .kpi-row { display: flex; flex-wrap: wrap; gap: 20px; margin-bottom: 32px; }
        .kpi-card { border-radius: 20px; padding: 20px 24px; flex: 1; min-width: 150px; box-shadow: 0 4px 12px rgba(0,0,0,0.08); transition: transform 0.3s ease; background: white; border-left: 6px solid; }
        .kpi-card:hover { transform: translateY(-3px); }
        .kpi-title { font-size: 13px; text-transform: uppercase; letter-spacing: 1px; opacity: 0.7; margin-bottom: 10px; font-weight: 600; }
        .kpi-number { font-size: 28px; font-weight: bold; }
        
        /* Status Badges */
        .status-badge {
            display: inline-block;
            padding: 4px 14px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        .status-present { background: #d1fae5; color: #065f46; }
        .status-absent { background: #fee2e2; color: #991b1b; }
        .status-late { background: #fef3c7; color: #92400e; }
        .status-excused { background: #dbeafe; color: #1e40af; }
        
        /* Table */
        .info-table { width: 100%; background: white; border-radius: 16px; overflow: hidden; }
        .info-table th { background: #f8fafc; font-weight: 600; color: #475569; padding: 12px 16px; text-align: left; border-bottom: 2px solid #e2e8f0; }
        .info-table td { padding: 12px 16px; text-align: left; border-bottom: 1px solid #e2e8f0; }
        .info-table tr:hover { background: #f8fafc; }
        .info-table .no-data { text-align: center; color: #94a3b8; padding: 40px; }
        
        /* Stats Box */
        .stats-box {
            background: white;
            border-radius: 16px;
            padding: 20px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.05);
            height: 100%;
        }
        .stats-box h4 { font-size: 16px; font-weight: 600; margin-bottom: 15px; color: #1e293b; }
        
        /* Rules Box */
        .rules-box {
            background: white;
            border-radius: 16px;
            padding: 20px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.05);
            margin-bottom: 20px;
        }
        .rules-box h4 { font-size: 16px; font-weight: 600; margin-bottom: 15px; color: #1e293b; }
        .rule-item {
            display: flex;
            align-items: flex-start;
            gap: 12px;
            padding: 10px 0;
            border-bottom: 1px solid #f1f5f9;
        }
        .rule-item:last-child { border-bottom: none; }
        .rule-item .rule-icon { color: #4f46e5; font-size: 18px; margin-top: 2px; }
        .rule-item .rule-title { font-weight: 600; color: #1e293b; }
        .rule-item .rule-desc { color: #64748b; font-size: 14px; }
        
        .chart-container {
            position: relative;
            height: 250px;
        }
        
        /* Responsive */
        @media (max-width: 768px) {
            .sidebar { width: 70px; }
            .sidebar-header h1, .sidebar-header p, .nav-item span { display: none; }
            .nav-item { justify-content: center; }
            .main-content { padding: 15px; }
            .top-bar { flex-direction: column; align-items: flex-start; }
            .kpi-card { min-width: 120px; }
        }
    </style>
</head>
<body>

<div class="container">
    <!-- ===== SIDEBAR ===== -->
    <div class="sidebar">
        <div class="sidebar-header">
            <img src="<?php echo htmlspecialchars($student_image); ?>" alt="Profile" class="profile-img" />
            <h1><?php echo htmlspecialchars($student_name); ?></h1>
            <p>Student</p>
        </div>
        <div class="nav-menu">
            <a href="forstudent.php" class="nav-item"><i class="fas fa-tachometer-alt"></i> <span>Dashboard</span></a>
            <a href="scores_stu.php" class="nav-item"><i class="fas fa-graduation-cap"></i> <span>Scores</span></a>
            <a href="attendance_student.php" class="nav-item active"><i class="fas fa-calendar-check"></i> <span>Attendance</span></a>
            <a href="Requests.php" class="nav-item"><i class="fas fa-file-signature"></i> <span>Request</span></a>
            <a href="settings_student.php" class="nav-item"><i class="fas fa-cog"></i> <span>Setting</span></a>
            <div class="nav-bottom">
                <a href="logout.php" class="nav-item"><i class="fas fa-sign-out-alt"></i> <span>Logout</span></a>
            </div>
        </div>
    </div>

    <!-- ===== MAIN CONTENT ===== -->
    <div class="main-content">
        <!-- Top Bar -->
        <div class="top-bar">
            <div class="page-title"><h2><i class="fas fa-calendar-check me-2"></i>My Attendance</h2></div>
            <div class="date-time" id="currentDateTime"></div>
        </div>

        <!-- ===== KPI CARDS ===== -->
        <div class="kpi-row">
            <div class="kpi-card" style="border-left-color: #10b981;">
                <div class="kpi-title"><i class="fas fa-check-circle"></i> Present</div>
                <div class="kpi-number"><?php echo $total_present; ?></div>
                <small><?php echo $attendance_percent; ?>% attendance rate</small>
            </div>
            <div class="kpi-card" style="border-left-color: #ef4444;">
                <div class="kpi-title"><i class="fas fa-times-circle"></i> Absent</div>
                <div class="kpi-number"><?php echo $total_absent; ?></div>
                <small>Total days absent</small>
            </div>
            <div class="kpi-card" style="border-left-color: #f59e0b;">
                <div class="kpi-title"><i class="fas fa-clock"></i> Late</div>
                <div class="kpi-number"><?php echo $total_late; ?></div>
                <small>Arrived late</small>
            </div>
            <div class="kpi-card" style="border-left-color: #3b82f6;">
                <div class="kpi-title"><i class="fas fa-check-double"></i> Excused</div>
                <div class="kpi-number"><?php echo $total_excused; ?></div>
                <small>Excused absences</small>
            </div>
            <div class="kpi-card" style="border-left-color: #8b5cf6;">
                <div class="kpi-title"><i class="fas fa-calendar-day"></i> Total Days</div>
                <div class="kpi-number"><?php echo $total_days; ?></div>
                <small>Total attendance records</small>
            </div>
        </div>

        <!-- ===== RULES & STATISTICS ===== -->
        <div class="row mb-4">
            <!-- Rules -->
            <div class="col-lg-4 mb-4 mb-lg-0">
                <div class="rules-box">
                    <h4><i class="fas fa-gavel me-2"></i>Attendance Rules</h4>
                    <?php if (!empty($rules)): ?>
                        <?php foreach ($rules as $rule): ?>
                        <div class="rule-item">
                            <div class="rule-icon">
                                <i class="fas fa-<?php echo $rule['icon'] ?? 'circle-check'; ?>"></i>
                            </div>
                            <div>
                                <div class="rule-title"><?php echo htmlspecialchars($rule['title'] ?? 'Rule'); ?></div>
                                <div class="rule-desc"><?php echo htmlspecialchars($rule['description'] ?? ''); ?></div>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <div class="text-muted text-center py-3">
                            <i class="fas fa-info-circle"></i> No rules defined yet.
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Monthly Statistics -->
            <div class="col-lg-8">
                <div class="stats-box">
                    <h4><i class="fas fa-chart-bar me-2"></i>Monthly Statistics</h4>
                    <?php if (!empty($monthly_stats)): ?>
                    <div class="table-responsive">
                        <table class="table table-sm table-hover">
                            <thead>
                                <tr>
                                    <th>Month</th>
                                    <th>Present</th>
                                    <th>Absent</th>
                                    <th>Late</th>
                                    <th>Excused</th>
                                    <th>Total</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($monthly_stats as $stat): 
                                    $month_name = date('F Y', strtotime($stat['month'] . '-01'));
                                ?>
                                <tr>
                                    <td><?php echo $month_name; ?></td>
                                    <td><span class="text-success"><?php echo $stat['present']; ?></span></td>
                                    <td><span class="text-danger"><?php echo $stat['absent']; ?></span></td>
                                    <td><span class="text-warning"><?php echo $stat['late']; ?></span></td>
                                    <td><span class="text-primary"><?php echo $stat['excused']; ?></span></td>
                                    <td><strong><?php echo $stat['total']; ?></strong></td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                    <?php else: ?>
                    <div class="text-muted text-center py-3">
                        <i class="fas fa-info-circle"></i> No monthly data available.
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- ===== SUBJECT STATISTICS ===== -->
        <?php if (!empty($subject_stats)): ?>
        <div class="row mb-4">
            <div class="col-12">
                <div class="stats-box">
                    <h4><i class="fas fa-book me-2"></i>Attendance by Subject</h4>
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th>Subject</th>
                                    <th>Present</th>
                                    <th>Absent</th>
                                    <th>Late</th>
                                    <th>Total</th>
                                    <th>Rate</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($subject_stats as $stat): 
                                    $rate = $stat['total'] > 0 ? round(($stat['present'] / $stat['total']) * 100, 1) : 0;
                                    $bar_color = $rate >= 80 ? 'success' : ($rate >= 60 ? 'warning' : 'danger');
                                ?>
                                <tr>
                                    <td><strong><?php echo htmlspecialchars($stat['subject']); ?></strong></td>
                                    <td><span class="text-success"><?php echo $stat['present']; ?></span></td>
                                    <td><span class="text-danger"><?php echo $stat['absent']; ?></span></td>
                                    <td><span class="text-warning"><?php echo $stat['late']; ?></span></td>
                                    <td><?php echo $stat['total']; ?></td>
                                    <td>
                                        <div class="d-flex align-items-center gap-2">
                                            <span class="badge bg-<?php echo $bar_color; ?>"><?php echo $rate; ?>%</span>
                                            <div class="progress flex-grow-1" style="height: 6px;">
                                                <div class="progress-bar bg-<?php echo $bar_color; ?>" style="width: <?php echo $rate; ?>%"></div>
                                            </div>
                                        </div>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
        <?php endif; ?>

        <!-- ===== ATTENDANCE RECORDS TABLE ===== -->
        <div class="kpi-row">
            <div class="kpi-card" style="border-left-color: #8b5cf6; width: 100%;">
                <div class="kpi-title">
                    <i class="fas fa-list"></i> 
                    Attendance Records
                    <span class="badge bg-primary ms-2">Total: <?php echo $total_days; ?></span>
                </div>
                <div class="table-responsive">
                    <table class="info-table table">
                        <thead>
                            <tr>
                                <th>#</th>
                                <th>Date</th>
                                <th>Time</th>
                                <th>Subject</th>
                                <th>Status</th>
                                <th>Note</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (!empty($attendance_records)): 
                                $count = 1;
                                foreach ($attendance_records as $record):
                                    $status = strtolower($record['status'] ?? '');
                                    $status_class = 'status-' . $status;
                                    $status_icon = [
                                        'present' => 'fa-check-circle text-success',
                                        'absent' => 'fa-times-circle text-danger',
                                        'late' => 'fa-clock text-warning',
                                        'excused' => 'fa-check-double text-primary'
                                    ][$status] ?? 'fa-question-circle text-muted';
                            ?>
                            <tr>
                                <td><?php echo $count++; ?></td>
                                <td><?php echo date('d M Y', strtotime($record['date'])); ?></td>
                                <td><?php echo date('h:i A', strtotime($record['time'] ?? '00:00:00')); ?></td>
                                <td><?php echo htmlspecialchars($record['subject'] ?? 'N/A'); ?></td>
                                <td>
                                    <span class="status-badge <?php echo $status_class; ?>">
                                        <i class="fas <?php echo $status_icon; ?>"></i>
                                        <?php echo ucfirst($status); ?>
                                    </span>
                                </td>
                                <td><?php echo htmlspecialchars($record['note'] ?? '-'); ?></td>
                            </tr>
                            <?php endforeach; ?>
                            <?php else: ?>
                            <tr>
                                <td colspan="6" class="no-data">
                                    <i class="fas fa-calendar-times fa-3x d-block mb-3" style="color: #cbd5e1;"></i>
                                    No attendance records found.
                                </td>
                            </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- ===== CHART ===== -->
        <div class="row">
            <div class="col-12">
                <div class="stats-box">
                    <h4><i class="fas fa-chart-pie me-2"></i>Attendance Overview</h4>
                    <div class="chart-container">
                        <canvas id="attendanceChart"></canvas>
                    </div>
                </div>
            </div>
        </div>

    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

<script>
    // ===== UPDATE DATE/TIME =====
    function updateDateTime() {
        const now = new Date();
        const options = { 
            weekday: 'long', 
            year: 'numeric', 
            month: 'long', 
            day: 'numeric', 
            hour: '2-digit', 
            minute: '2-digit',
            second: '2-digit'
        };
        document.getElementById('currentDateTime').innerHTML = now.toLocaleDateString('en-US', options);
    }
    updateDateTime();
    setInterval(updateDateTime, 1000);

    // ===== ATTENDANCE CHART =====
    const ctx = document.getElementById('attendanceChart').getContext('2d');
    
    const chartData = {
        labels: ['Present', 'Absent', 'Late', 'Excused'],
        datasets: [{
            data: [
                <?php echo $total_present; ?>,
                <?php echo $total_absent; ?>,
                <?php echo $total_late; ?>,
                <?php echo $total_excused; ?>
            ],
            backgroundColor: ['#10b981', '#ef4444', '#f59e0b', '#3b82f6'],
            borderWidth: 2,
            borderColor: '#ffffff'
        }]
    };

    new Chart(ctx, {
        type: 'doughnut',
        data: chartData,
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    position: 'bottom',
                    labels: {
                        padding: 20,
                        usePointStyle: true,
                        pointStyle: 'circle',
                        font: { size: 13 }
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
            },
            cutout: '65%'
        }
    });
</script>

</body>
</html>