<?php
// process_attendance.php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

include "db.php";

// ============================================
// ត្រួតពិនិត្យសិទ្ធិអ្នកប្រើ (សម្រាប់តែ Admin និង Teacher)
// ============================================
if (!isset($_SESSION['student_logged_in']) || $_SESSION['student_logged_in'] !== true) {
    header("Location: login.php");
    exit();
}

// អនុញ្ញាតតែ Admin និង Teacher ប៉ុណ្ណោះ
if ($_SESSION['role'] !== 'admin' && $_SESSION['role'] !== 'teacher') {
    $_SESSION['error'] = "You don't have permission to access this page.";
    header("Location: " . ($_SESSION['role'] == 'student' ? 'forstudent.php' : 'index.php'));
    exit();
}

// ============================================
// ដំណើរការ POST Request
// ============================================
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    
    // ============================================
    // 1. កត់ត្រាការចូលរៀន (Add Attendance)
    // ============================================
    if (isset($_POST['action']) && $_POST['action'] == 'add_attendance') {
        
        $student_id = isset($_POST['student_id']) ? intval($_POST['student_id']) : 0;
        $date = isset($_POST['date']) ? mysqli_real_escape_string($conn, $_POST['date']) : date('Y-m-d');
        $time = isset($_POST['time']) ? mysqli_real_escape_string($conn, $_POST['time']) : date('H:i:s');
        $subject = isset($_POST['subject']) ? mysqli_real_escape_string($conn, $_POST['subject']) : '';
        $status = isset($_POST['status']) ? mysqli_real_escape_string($conn, $_POST['status']) : 'present';
        $note = isset($_POST['note']) ? mysqli_real_escape_string($conn, $_POST['note']) : '';
        
        // ពិនិត្យទិន្នន័យចាំបាច់
        if ($student_id == 0 || empty($subject)) {
            $_SESSION['error'] = "Please select a student and subject.";
            header("Location: StudentAttendance.php");
            exit();
        }
        
        // ពិនិត្យមើលថាមានការចូលរៀននៅថ្ងៃនេះហើយឬនៅ
        $check_query = "SELECT * FROM attendance 
                        WHERE student_id = $student_id 
                        AND date = '$date' 
                        AND subject = '$subject'";
        $check_result = mysqli_query($conn, $check_query);
        
        if (mysqli_num_rows($check_result) > 0) {
            $_SESSION['error'] = "Attendance for this student on $date for subject '$subject' already exists.";
            header("Location: StudentAttendance.php");
            exit();
        }
        
        // បញ្ចូលទិន្នន័យ
        $insert_query = "INSERT INTO attendance (student_id, date, time, subject, status, note) 
                         VALUES ($student_id, '$date', '$time', '$subject', '$status', '$note')";
        
        if (mysqli_query($conn, $insert_query)) {
            $_SESSION['success'] = "Attendance recorded successfully!";
        } else {
            $_SESSION['error'] = "Error: " . mysqli_error($conn);
        }
        
        header("Location: StudentAttendance.php");
        exit();
    }
    
    // ============================================
    // 2. កែប្រែការចូលរៀន (Update Attendance)
    // ============================================
    if (isset($_POST['action']) && $_POST['action'] == 'update_attendance') {
        
        $id = isset($_POST['id']) ? intval($_POST['id']) : 0;
        $student_id = isset($_POST['student_id']) ? intval($_POST['student_id']) : 0;
        $date = isset($_POST['date']) ? mysqli_real_escape_string($conn, $_POST['date']) : '';
        $time = isset($_POST['time']) ? mysqli_real_escape_string($conn, $_POST['time']) : '';
        $subject = isset($_POST['subject']) ? mysqli_real_escape_string($conn, $_POST['subject']) : '';
        $status = isset($_POST['status']) ? mysqli_real_escape_string($conn, $_POST['status']) : 'present';
        $note = isset($_POST['note']) ? mysqli_real_escape_string($conn, $_POST['note']) : '';
        
        if ($id == 0 || $student_id == 0 || empty($subject) || empty($date)) {
            $_SESSION['error'] = "Please fill all required fields.";
            header("Location: StudentAttendance.php");
            exit();
        }
        
        $update_query = "UPDATE attendance SET 
                         student_id = $student_id,
                         date = '$date',
                         time = '$time',
                         subject = '$subject',
                         status = '$status',
                         note = '$note'
                         WHERE id = $id";
        
        if (mysqli_query($conn, $update_query)) {
            $_SESSION['success'] = "Attendance record updated successfully!";
        } else {
            $_SESSION['error'] = "Error: " . mysqli_error($conn);
        }
        
        header("Location: StudentAttendance.php");
        exit();
    }
    
    // ============================================
    // 3. លុបការចូលរៀន (Delete Attendance)
    // ============================================
    if (isset($_POST['action']) && $_POST['action'] == 'delete_attendance') {
        
        $id = isset($_POST['id']) ? intval($_POST['id']) : 0;
        
        if ($id == 0) {
            $_SESSION['error'] = "Invalid attendance record ID.";
            header("Location: StudentAttendance.php");
            exit();
        }
        
        $delete_query = "DELETE FROM attendance WHERE id = $id";
        
        if (mysqli_query($conn, $delete_query)) {
            $_SESSION['success'] = "Attendance record deleted successfully!";
        } else {
            $_SESSION['error'] = "Error: " . mysqli_error($conn);
        }
        
        header("Location: StudentAttendance.php");
        exit();
    }
    
    // ============================================
    // 4. កត់ត្រាការចូលរៀនច្រើន (Bulk Attendance)
    // ============================================
    if (isset($_POST['action']) && $_POST['action'] == 'bulk_attendance') {
        
        $date = isset($_POST['date']) ? mysqli_real_escape_string($conn, $_POST['date']) : date('Y-m-d');
        $subject = isset($_POST['subject']) ? mysqli_real_escape_string($conn, $_POST['subject']) : '';
        $students = isset($_POST['students']) ? $_POST['students'] : [];
        
        if (empty($subject) || empty($students)) {
            $_SESSION['error'] = "Please select subject and at least one student.";
            header("Location: StudentAttendance.php");
            exit();
        }
        
        $success_count = 0;
        $error_count = 0;
        
        foreach ($students as $student_id => $status) {
            // ពិនិត្យមើលថាមានការចូលរៀនហើយឬនៅ
            $check_query = "SELECT * FROM attendance 
                            WHERE student_id = $student_id 
                            AND date = '$date' 
                            AND subject = '$subject'";
            $check_result = mysqli_query($conn, $check_query);
            
            if (mysqli_num_rows($check_result) > 0) {
                // បើមានហើយ កែប្រែ
                $update_query = "UPDATE attendance SET 
                                 status = '$status',
                                 time = '" . date('H:i:s') . "'
                                 WHERE student_id = $student_id 
                                 AND date = '$date' 
                                 AND subject = '$subject'";
                if (mysqli_query($conn, $update_query)) {
                    $success_count++;
                } else {
                    $error_count++;
                }
            } else {
                // បើមិនទាន់មាន បញ្ចូលថ្មី
                $insert_query = "INSERT INTO attendance (student_id, date, time, subject, status) 
                                 VALUES ($student_id, '$date', '" . date('H:i:s') . "', '$subject', '$status')";
                if (mysqli_query($conn, $insert_query)) {
                    $success_count++;
                } else {
                    $error_count++;
                }
            }
        }
        
        if ($error_count == 0) {
            $_SESSION['success'] = "Bulk attendance recorded successfully! ($success_count students)";
        } else {
            $_SESSION['warning'] = "Recorded $success_count students, $error_count failed.";
        }
        
        header("Location: StudentAttendance.php");
        exit();
    }
    
    // ============================================
    // 5. AJAX: ទាញទិន្នន័យសិស្សសម្រាប់ Edit
    // ============================================
    if (isset($_POST['action']) && $_POST['action'] == 'get_attendance') {
        $id = isset($_POST['id']) ? intval($_POST['id']) : 0;
        
        if ($id == 0) {
            echo json_encode(['success' => false, 'message' => 'Invalid ID']);
            exit();
        }
        
        $query = "SELECT a.*, s.name as student_name 
                  FROM attendance a
                  LEFT JOIN students s ON a.student_id = s.id
                  WHERE a.id = $id";
        $result = mysqli_query($conn, $query);
        
        if ($row = mysqli_fetch_assoc($result)) {
            echo json_encode(['success' => true, 'data' => $row]);
        } else {
            echo json_encode(['success' => false, 'message' => 'Record not found']);
        }
        exit();
    }
    
    // ============================================
    // 6. AJAX: ទាញបញ្ជីសិស្សតាមជំនាញ
    // ============================================
    if (isset($_POST['action']) && $_POST['action'] == 'get_students_by_department') {
        $department = isset($_POST['department']) ? mysqli_real_escape_string($conn, $_POST['department']) : '';
        
        if (empty($department)) {
            echo json_encode(['success' => false, 'message' => 'Please select department']);
            exit();
        }
        
        $query = "SELECT id, name, student_id FROM students 
                  WHERE department = '$department' 
                  ORDER BY name";
        $result = mysqli_query($conn, $query);
        $students = [];
        
        while ($row = mysqli_fetch_assoc($result)) {
            $students[] = $row;
        }
        
        echo json_encode(['success' => true, 'data' => $students]);
        exit();
    }
}

// ============================================
// 7. GET: Export Attendance to CSV
// ============================================
if (isset($_GET['action']) && $_GET['action'] == 'export_csv') {
    
    $date_from = isset($_GET['date_from']) ? mysqli_real_escape_string($conn, $_GET['date_from']) : '';
    $date_to = isset($_GET['date_to']) ? mysqli_real_escape_string($conn, $_GET['date_to']) : '';
    $department = isset($_GET['department']) ? mysqli_real_escape_string($conn, $_GET['department']) : '';
    $subject = isset($_GET['subject']) ? mysqli_real_escape_string($conn, $_GET['subject']) : '';
    
    $where = "1=1";
    if (!empty($date_from)) {
        $where .= " AND a.date >= '$date_from'";
    }
    if (!empty($date_to)) {
        $where .= " AND a.date <= '$date_to'";
    }
    if (!empty($department)) {
        $where .= " AND s.department = '$department'";
    }
    if (!empty($subject)) {
        $where .= " AND a.subject = '$subject'";
    }
    
    $query = "SELECT a.*, s.name as student_name, s.department 
              FROM attendance a
              LEFT JOIN students s ON a.student_id = s.id
              WHERE $where
              ORDER BY a.date DESC, a.time DESC";
    $result = mysqli_query($conn, $query);
    
    // Set headers for CSV download
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="attendance_export_' . date('Y-m-d') . '.csv"');
    
    $output = fopen('php://output', 'w');
    
    // Add BOM for UTF-8
    fputs($output, "\xEF\xBB\xBF");
    
    // Headers
    fputcsv($output, ['ID', 'Student Name', 'Department', 'Subject', 'Date', 'Time', 'Status', 'Note']);
    
    // Data
    while ($row = mysqli_fetch_assoc($result)) {
        fputcsv($output, [
            $row['id'],
            $row['student_name'],
            $row['department'] ?? 'N/A',
            $row['subject'],
            $row['date'],
            $row['time'],
            ucfirst($row['status']),
            $row['note'] ?? ''
        ]);
    }
    
    fclose($output);
    exit();
}

// ============================================
// 8. GET: Export Attendance to PDF (ប្រើ HTML)
// ============================================
if (isset($_GET['action']) && $_GET['action'] == 'export_pdf') {
    // ប្រសិនបើអ្នកចង់ប្រើ PDF សូមដំឡើង Dompdf ឬ TCPDF
    // ឧទាហរណ៍នេះប្រើ HTML សម្រាប់បោះពុម្ព
    ?>
    <!DOCTYPE html>
    <html>
    <head>
        <title>Attendance Report</title>
        <style>
            body { font-family: Arial, sans-serif; padding: 20px; }
            h1 { text-align: center; color: #1e293b; }
            table { width: 100%; border-collapse: collapse; margin-top: 20px; }
            th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
            th { background: #f1f5f9; }
            .present { color: green; }
            .absent { color: red; }
            .late { color: orange; }
            .excused { color: blue; }
            .text-center { text-align: center; }
            .footer { margin-top: 30px; text-align: center; color: #94a3b8; font-size: 12px; }
        </style>
    </head>
    <body>
        <h1>Attendance Report</h1>
        <p class="text-center">Generated on: <?php echo date('d M Y H:i:s'); ?></p>
        
        <table>
            <thead>
                <tr>
                    <th>#</th>
                    <th>Student Name</th>
                    <th>Department</th>
                    <th>Subject</th>
                    <th>Date</th>
                    <th>Time</th>
                    <th>Status</th>
                    <th>Note</th>
                </tr>
            </thead>
            <tbody>
                <?php
                $query = "SELECT a.*, s.name as student_name, s.department 
                          FROM attendance a
                          LEFT JOIN students s ON a.student_id = s.id
                          ORDER BY a.date DESC, a.time DESC";
                $result = mysqli_query($conn, $query);
                $count = 1;
                while ($row = mysqli_fetch_assoc($result)) {
                    $status_class = strtolower($row['status']);
                ?>
                <tr>
                    <td><?php echo $count++; ?></td>
                    <td><?php echo htmlspecialchars($row['student_name'] ?? 'N/A'); ?></td>
                    <td><?php echo htmlspecialchars($row['department'] ?? 'N/A'); ?></td>
                    <td><?php echo htmlspecialchars($row['subject']); ?></td>
                    <td><?php echo date('d M Y', strtotime($row['date'])); ?></td>
                    <td><?php echo date('h:i A', strtotime($row['time'] ?? '00:00:00')); ?></td>
                    <td class="<?php echo $status_class; ?>">
                        <?php echo ucfirst($row['status']); ?>
                    </td>
                    <td><?php echo htmlspecialchars($row['note'] ?? '-'); ?></td>
                </tr>
                <?php } ?>
            </tbody>
        </table>
        
        <div class="footer">
            <p>Generated by KRaksa Education System</p>
        </div>
        
        <script>
            window.print();
        </script>
    </body>
    </html>
    <?php
    exit();
}

// ============================================
// ប្រសិនបើគ្មានសកម្មភាព បញ្ជូនត្រឡប់ទៅកាន់ Page Attendance
// ============================================
if (!isset($_POST['action']) && !isset($_GET['action'])) {
    header("Location: StudentAttendance.php");
    exit();
}
?>