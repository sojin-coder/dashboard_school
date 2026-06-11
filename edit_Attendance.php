<?php
include 'db.php';

// Validate ID exists
if (!isset($_GET['id']) || empty($_GET['id'])) {
    echo "<script>alert('Invalid ID!'); window.location.href='Attendance.php';</script>";
    exit();
}

$id = mysqli_real_escape_string($conn, $_GET['id']);

// ១. ទាញយកទិន្នន័យចាស់មកបង្ហាញក្នុង Form
$get_data = mysqli_query($conn, "SELECT * FROM attendance WHERE id = $id");
$row = mysqli_fetch_assoc($get_data);

// Check if record exists
if (!$row) {
    echo "<script>alert('Record not found!'); window.location.href='Attendance.php';</script>";
    exit();
}

// ២. ចាប់ផ្ដើម Update នៅពេលចុចប៊ូតុង Save Changes
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['update'])) {
    // Escape all inputs to prevent SQL injection
    $employee_id = mysqli_real_escape_string($conn, $_POST['employee_id']);
    $schedule_id = mysqli_real_escape_string($conn, $_POST['schedule_id']);
    $attendance_date = mysqli_real_escape_string($conn, $_POST['attendance_date']);
    $check_in = mysqli_real_escape_string($conn, $_POST['check_in']);
    $check_out = mysqli_real_escape_string($conn, $_POST['check_out']);
    $status = mysqli_real_escape_string($conn, $_POST['status']);
    $note = mysqli_real_escape_string($conn, $_POST['note']);

    $sql = "UPDATE attendance SET 
            employee_id='$employee_id', 
            schedule_id='$schedule_id', 
            attendance_date='$attendance_date', 
            check_in='$check_in', 
            check_out='$check_out', 
            status='$status',
            note='$note'
            WHERE id='$id'";

    if (mysqli_query($conn, $sql)) {
        echo "<script>alert('Update success!'); window.location.href='Attendance.php';</script>";
        exit();
    } else {
        echo "Error updating record: " . mysqli_error($conn);
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <title>Edit Attendance</title>
</head>
<body class="bg-light">
    <div class="container mt-5">
        <div class="card shadow">
            <div class="card-header bg-primary text-white">
                <h4>Edit Attendance Information</h4>
            </div>
            <div class="card-body">
                <form method="POST">
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label class="form-label">Employee ID:</label>
                            <input type="text" name="employee_id" class="form-control" value="<?php echo htmlspecialchars($row['employee_id']); ?>" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Schedule ID:</label>
                            <input type="number" name="schedule_id" class="form-control" value="<?php echo htmlspecialchars($row['schedule_id']); ?>" required>
                        </div>
                    </div>
                    
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label class="form-label">Attendance Date:</label>
                            <input type="date" name="attendance_date" class="form-control" value="<?php echo htmlspecialchars($row['attendance_date']); ?>" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Check In:</label>
                            <input type="time" name="check_in" class="form-control" value="<?php echo htmlspecialchars($row['check_in']); ?>">
                        </div>
                    </div>
                    
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label class="form-label">Check Out:</label>
                            <input type="time" name="check_out" class="form-control" value="<?php echo htmlspecialchars($row['check_out']); ?>">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Status:</label>
                            <select name="status" class="form-control" required>
                                <option value="Present" <?php echo ($row['status'] == 'Present') ? 'selected' : ''; ?>>Present</option>
                                <option value="Absent" <?php echo ($row['status'] == 'Absent') ? 'selected' : ''; ?>>Absent</option>
                                <option value="Late" <?php echo ($row['status'] == 'Late') ? 'selected' : ''; ?>>Late</option>
                                <option value="Half Day" <?php echo ($row['status'] == 'Half Day') ? 'selected' : ''; ?>>Half Day</option>
                                <option value="Holiday" <?php echo ($row['status'] == 'Holiday') ? 'selected' : ''; ?>>Holiday</option>
                            </select>
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Note:</label>
                        <textarea name="note" class="form-control" rows="3"><?php echo htmlspecialchars($row['note']); ?></textarea>
                    </div>

                    <div class="mt-4">
                        <button type="submit" name="update" class="btn btn-success px-4">Save Changes</button>
                        <a href="Attendance.php" class="btn btn-secondary px-4">Cancel</a>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>