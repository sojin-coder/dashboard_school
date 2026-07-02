<?php

include "student_auth.php";


$message = '';
$message_type = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $request_type = mysqli_real_escape_string($db_conn, $_POST['request_type'] ?? '');
    $subject = mysqli_real_escape_string($db_conn, $_POST['subject'] ?? '');
    $description = mysqli_real_escape_string($db_conn, $_POST['description'] ?? '');
    
    if (!empty($request_type) && !empty($subject) && !empty($description)) {
        $insert_query = "INSERT INTO requests (student_id, student_name, request_type, subject, description, status, created_at) 
                         VALUES ('$logged_in_id', '{$student_info['name']}', '$request_type', '$subject', '$description', 'pending', NOW())";
        
        if (mysqli_query($db_conn, $insert_query)) {
            $message = "✅ Request submitted successfully!";
            $message_type = "success";
        } else {
            $message = "❌ Error submitting request: " . mysqli_error($db_conn);
            $message_type = "danger";
        }
    } else {
        $message = "⚠️ Please fill in all fields";
        $message_type = "warning";
    }
}

// ============================================
// Get user's requests
// ============================================
$requests_query = "SELECT * FROM requests WHERE student_id = '$logged_in_id' ORDER BY created_at DESC";
$requests_result = mysqli_query($db_conn, $requests_query);

// Count requests by status
$pending_count = 0;
$approved_count = 0;
$rejected_count = 0;

if ($requests_result) {
    while ($req = mysqli_fetch_assoc($requests_result)) {
        if ($req['status'] == 'pending') $pending_count++;
        elseif ($req['status'] == 'approved') $approved_count++;
        elseif ($req['status'] == 'rejected') $rejected_count++;
    }
    // Reset pointer
    mysqli_data_seek($requests_result, 0);
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
    <title>My Requests - KRaksa Education</title>
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
            border: 4px solid rgba(255, 255, 255, 0.8); 
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
        
        .form-container { background: white; padding: 25px; border-radius: 20px; box-shadow: 0 4px 12px rgba(0,0,0,0.08); }
        .info-table { width: 100%; background: white; border-radius: 16px; overflow: hidden; }
        .info-table th, .info-table td { padding: 12px 16px; text-align: left; border-bottom: 1px solid #e2e8f0; }
        .info-table th { background: #f8fafc; font-weight: 600; color: #475569; }
        
        .status-pending { color: #f59e0b; font-weight: 700; }
        .status-approved { color: #10b981; font-weight: 700; }
        .status-rejected { color: #ef4444; font-weight: 700; }
        
        .form-control:focus {
            border-color: #4f46e5;
            box-shadow: 0 0 0 0.2rem rgba(79, 70, 229, 0.25);
        }
        
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
    <!-- ============================================ -->
    <!-- SIDEBAR                                      -->
    <!-- ============================================ -->
    <div class="sidebar">
        <div class="sidebar-header">
            <img src="<?php echo !empty($student_image) ? htmlspecialchars($student_image) : 'https://i.pinimg.com/736x/be/dd/b8/beddb8c8c3c4c967cb821aae0cb796e3.jpg'; ?>" 
                 alt="Profile" 
                 class="profile-img" />
            <h1><?php echo sanitize($logged_in_student); ?></h1>
            <p>
                <i class="fas fa-graduation-cap"></i> <?php echo sanitize($student_department); ?>
            </p>
            <p style="font-size: 0.75rem; opacity: 0.7;">
                <i class="fas fa-school"></i> Grade: <?php echo sanitize($student_grade); ?>
            </p>
        </div>
        <div class="nav-menu">
            <a href="forstudent.php" class="nav-item"><i class="fas fa-tachometer-alt"></i> <span>Main Dashboard</span></a>
            <a href="scores_stu.php" class="nav-item"><i class="fas fa-graduation-cap"></i> <span>Scores</span></a>
            <a href="attendance_student.php" class="nav-item"><i class="fas fa-calendar-check"></i> <span>Attendance</span></a>
            <a href="Request.php" class="nav-item active"><i class="fas fa-file-signature"></i> <span>Request</span></a>
            <a href="settings_student.php" class="nav-item"><i class="fas fa-cog"></i> <span>Setting</span></a>
            <div class="nav-bottom">
                <a href="logout.php" class="nav-item" style="padding-left:8px;">
                    <i class="fas fa-sign-out-alt"></i> <span>Logout</span>
                </a>
            </div>
        </div>
    </div>

    <!-- ============================================ -->
    <!-- MAIN CONTENT                                 -->
    <!-- ============================================ -->
    <div class="main-content">
        <div class="top-bar">
            <div class="page-title">
                <h2><i class="fas fa-file-signature me-2"></i>My Requests</h2>
            </div>
            <div class="date-time" id="currentDateTime"></div>
        </div>
        
        <!-- ============================================ -->
        <!-- ALERT MESSAGES                               -->
        <!-- ============================================ -->
        <?php if ($message): ?>
            <div class="alert alert-<?php echo $message_type; ?> alert-dismissible fade show" role="alert">
                <?php echo $message; ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>
        
        <!-- ============================================ -->
        <!-- KPI CARDS                                    -->
        <!-- ============================================ -->
        <div class="kpi-row">
            <div class="kpi-card" style="border-left-color: #f59e0b;">
                <div class="kpi-title"><i class="fas fa-clock"></i> Pending</div>
                <div class="kpi-number"><?php echo $pending_count; ?></div>
                <small>Requests waiting for approval</small>
            </div>
            <div class="kpi-card" style="border-left-color: #10b981;">
                <div class="kpi-title"><i class="fas fa-check-circle"></i> Approved</div>
                <div class="kpi-number"><?php echo $approved_count; ?></div>
                <small>Approved requests</small>
            </div>
            <div class="kpi-card" style="border-left-color: #ef4444;">
                <div class="kpi-title"><i class="fas fa-times-circle"></i> Rejected</div>
                <div class="kpi-number"><?php echo $rejected_count; ?></div>
                <small>Rejected requests</small>
            </div>
            <div class="kpi-card" style="border-left-color: #4f46e5;">
                <div class="kpi-title"><i class="fas fa-tasks"></i> Total</div>
                <div class="kpi-number"><?php echo $pending_count + $approved_count + $rejected_count; ?></div>
                <small>Total requests submitted</small>
            </div>
        </div>
        
        <!-- ============================================ -->
        <!-- REQUEST FORM                                 -->
        <!-- ============================================ -->
        <div class="kpi-row">
            <div class="kpi-card" style="border-left-color: #4f46e5; width: 100%;">
                <div class="kpi-title"><i class="fas fa-plus-circle"></i> Submit New Request</div>
                <div class="form-container">
                    <form method="POST" action="">
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Request Type <span class="text-danger">*</span></label>
                                <select class="form-select" name="request_type" required>
                                    <option value="">Select Request Type</option>
                                    <option value="leave">Leave Request</option>
                                    <option value="exam">Exam Request</option>
                                    <option value="certificate">Certificate Request</option>
                                    <option value="transcript">Transcript Request</option>
                                    <option value="scholarship">Scholarship Request</option>
                                    <option value="other">Other</option>
                                </select>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Subject <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" name="subject" placeholder="Enter subject" required>
                            </div>
                            <div class="col-12 mb-3">
                                <label class="form-label">Description <span class="text-danger">*</span></label>
                                <textarea class="form-control" name="description" rows="4" placeholder="Describe your request in detail..." required></textarea>
                            </div>
                            <div class="col-12">
                                <button type="submit" class="btn btn-primary">
                                    <i class="fas fa-paper-plane"></i> Submit Request
                                </button>
                                <button type="reset" class="btn btn-secondary">
                                    <i class="fas fa-undo"></i> Clear
                                </button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
        
        <!-- ============================================ -->
        <!-- REQUEST HISTORY                              -->
        <!-- ============================================ -->
        <div class="kpi-row">
            <div class="kpi-card" style="border-left-color: #8b5cf6; width: 100%;">
                <div class="kpi-title">
                    <i class="fas fa-history"></i> 
                    Request History
                </div>
                <div class="table-responsive">
                    <table class="table table-bordered">
                        <thead>
                            <tr>
                                <th>#</th>
                                <th>Request Type</th>
                                <th>Subject</th>
                                <th>Description</th>
                                <th>Status</th>
                                <th>Date Submitted</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php 
                            $count = 1;
                            if ($requests_result && mysqli_num_rows($requests_result) > 0):
                                while ($req = mysqli_fetch_assoc($requests_result)):
                                    $statusClass = $req['status'] == 'pending' ? 'status-pending' : 
                                                  ($req['status'] == 'approved' ? 'status-approved' : 'status-rejected');
                                    $statusIcon = $req['status'] == 'pending' ? '⏳' : 
                                                 ($req['status'] == 'approved' ? '✅' : '❌');
                                    $statusBadge = $req['status'] == 'pending' ? 'warning' : 
                                                  ($req['status'] == 'approved' ? 'success' : 'danger');
                            ?>
                            <tr>
                                <td><?php echo $count++; ?></td>
                                <td>
                                    <span class="badge bg-info">
                                        <?php echo ucfirst(str_replace('_', ' ', $req['request_type'])); ?>
                                    </span>
                                </td>
                                <td><strong><?php echo sanitize($req['subject']); ?></strong></td>
                                <td>
                                    <?php 
                                    $desc = sanitize($req['description']);
                                    echo strlen($desc) > 50 ? substr($desc, 0, 50) . '...' : $desc; 
                                    ?>
                                </td>
                                <td>
                                    <span class="badge bg-<?php echo $statusBadge; ?>">
                                        <?php echo $statusIcon . ' ' . ucfirst($req['status']); ?>
                                    </span>
                                </td>
                                <td><?php echo date('d/m/Y H:i', strtotime($req['created_at'])); ?></td>
                            </tr>
                            <?php 
                                endwhile;
                            else:
                            ?>
                            <tr>
                                <td colspan="6" class="text-center text-muted py-4">
                                    <i class="fas fa-inbox" style="font-size: 24px; display: block; margin-bottom: 10px;"></i>
                                    No requests found. Submit your first request above!
                                </td>
                            </tr>
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
    function updateDateTime() {
        const now = new Date();
        const options = { 
            weekday: 'long', 
            year: 'numeric', 
            month: 'long', 
            day: 'numeric', 
            hour: '2-digit', 
            minute: '2-digit' 
        };
        document.getElementById('currentDateTime').innerHTML = now.toLocaleDateString('en-US', options);
    }
    updateDateTime();
    setInterval(updateDateTime, 60000);
</script>
</body>
</html>