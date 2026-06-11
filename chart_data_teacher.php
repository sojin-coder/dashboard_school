<?php
header('Content-Type: application/json');
include 'db.php';

// Set charset
mysqli_set_charset($conn, "utf8mb4");

// Check connection
if (!$conn) {
    echo json_encode(["error" => "Database connection failed"]);
    exit();
}

// ==============================
// FUNCTION: COUNT ALL
// ==============================
function countTable($conn, $table) {
    $sql = "SELECT COUNT(*) as total FROM `$table`";
    $result = mysqli_query($conn, $sql);
    if (!$result) {
        return 0;
    }
    $row = mysqli_fetch_assoc($result);
    return (int)$row['total'];
}

// ==============================
// FUNCTION: COUNT WITH CONDITION
// ==============================
function countWithCondition($conn, $table, $column, $value) {
    $escaped_value = mysqli_real_escape_string($conn, $value);
    $sql = "SELECT COUNT(*) as total FROM `$table` WHERE LOWER(TRIM(`$column`)) = LOWER(TRIM('$escaped_value'))";
    $result = mysqli_query($conn, $sql);
    if (!$result) {
        return 0;
    }
    $row = mysqli_fetch_assoc($result);
    return (int)$row['total'];
}

// ==============================
// FUNCTION: GET TEACHERS BY SUBJECT
// ==============================
function getTeachersBySubject($conn) {
    $sql = "SELECT subject, COUNT(*) as total FROM students WHERE subject IS NOT NULL AND subject != '' GROUP BY subject ORDER BY total DESC";
    $result = mysqli_query($conn, $sql);
    
    $teachersBySubject = [];
    if ($result && mysqli_num_rows($result) > 0) {
        while($row = mysqli_fetch_assoc($result)) {
            $teachersBySubject[] = [
                'subject' => $row['subject'] ? $row['subject'] : 'No Subject',
                'total' => (int)$row['total']
            ];
        }
    }
    return $teachersBySubject;
}

// ==============================
// GET ALL DATA FROM DATABASE
// ==============================

// Basic counts
$data['students'] = countTable($conn, 'students');
$data['teachers'] = countTable($conn, 'teachers');
$data['courses'] = countTable($conn, 'courses');

// Gender counts
$data['male'] = countWithCondition($conn, 'students', 'gender', 'Male');
$data['female'] = countWithCondition($conn, 'students', 'gender', 'Female');

// Student type counts (from student_type table)
$data['Transfer'] = countWithCondition($conn, 'student_type', 'student_type', 'Transfer');
$data['old'] = countWithCondition($conn, 'student_type', 'student_type', 'Old');

// Student status counts (from datastu table)
$data['Active'] = countWithCondition($conn, 'datastu', 'status', 'Active');
$data['Inactive'] = countWithCondition($conn, 'datastu', 'status', 'Inactive');
$data['Dropped'] = countWithCondition($conn, 'datastu', 'status', 'Dropped');

// Major counts by college (from students table)
$data['it'] = countWithCondition($conn, 'students', 'college', 'IT') + countWithCondition($conn, 'students', 'college', 'Information Technology');
$data['civil'] = countWithCondition($conn, 'students', 'college', 'Civil') + countWithCondition($conn, 'students', 'college', 'Civil Engineering');
$data['electronics'] = countWithCondition($conn, 'students', 'college', 'Electronics');
$data['business'] = countWithCondition($conn, 'students', 'college', 'Business') + countWithCondition($conn, 'students', 'college', 'Business Science') + countWithCondition($conn, 'students', 'college', 'Business Management');
$data['electrical'] = countWithCondition($conn, 'students', 'college', 'Electrical') + countWithCondition($conn, 'students', 'college', 'Electrical Engineering');

// Payment status (from student_payments table)
$data['paid'] = countWithCondition($conn, 'student_payments', 'status', 'Paid');
$data['unpaid'] = countWithCondition($conn, 'student_payments', 'status', 'Unpaid');
$data['partial'] = countWithCondition($conn, 'student_payments', 'status', 'Partial'); // Added partial

// Teachers by subject
$data['teachersBySubject'] = getTeachersBySubject($conn);

// Close connection
mysqli_close($conn);

// ==============================
// OUTPUT JSON
// ==============================
echo json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
?>