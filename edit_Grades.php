<?php
include 'db.php';

// Validate ID exists
if (!isset($_GET['id']) || empty($_GET['id'])) {
    echo "<script>alert('Invalid ID!'); window.location.href='Grades.php';</script>";
    exit();
}

$id = mysqli_real_escape_string($conn, $_GET['id']);

// ១. ទាញយកទិន្នន័យចាស់មកបង្ហាញក្នុង Form
$get_data = mysqli_query($conn, "SELECT * FROM statistics WHERE id = $id");
$row = mysqli_fetch_assoc($get_data);

// Check if record exists
if (!$row) {
    echo "<script>alert('Record not found!'); window.location.href='Grades.php';</script>";
    exit();
}

// ២. ចាប់ផ្ដើម Update នៅពេលចុចប៊ូតុង Save Changes
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['update'])) {
    // Get ID from URL
    $id = mysqli_real_escape_string($conn, $_GET['id']);
    
    // Escape all inputs to prevent SQL injection
    $student_id = mysqli_real_escape_string($conn, $_POST['student_id']);
    $student_name = mysqli_real_escape_string($conn, $_POST['student_name']);
    $course_id = mysqli_real_escape_string($conn, $_POST['course_id']);
    $course_name = mysqli_real_escape_string($conn, $_POST['course_name']);
    $score = mysqli_real_escape_string($conn, $_POST['score']);
    $grade = mysqli_real_escape_string($conn, $_POST['grade']);
    $remark = mysqli_real_escape_string($conn, $_POST['remark']);
    $created_at = mysqli_real_escape_string($conn, $_POST['created_at']);

    // Calculate grade automatically based on score (optional but recommended)
    if ($score >= 90) {
        $grade = 'A';
    } elseif ($score >= 80) {
        $grade = 'B';
    } elseif ($score >= 70) {
        $grade = 'C';
    } elseif ($score >= 60) {
        $grade = 'D';
    } else {
        $grade = 'F';
    }

    $sql = "UPDATE statistics SET 
            student_id='$student_id', 
            student_name='$student_name', 
            course_id='$course_id', 
            course_name='$course_name', 
            score='$score', 
            grade='$grade',
            remark='$remark',
            created_at='$created_at'
            WHERE id='$id'";

    if (mysqli_query($conn, $sql)) {
        echo "<script>alert('Update success!'); window.location.href='Grades.php';</script>";
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
    <title>Edit Statistics</title>
</head>
<body class="bg-light">
    <div class="container mt-5">
        <div class="card shadow">
            <div class="card-header bg-primary text-white">
                <h4>Edit Statistics Information</h4>
            </div>
            <div class="card-body">
                <form method="POST">
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label class="form-label">Student ID:</label>
                            <input type="text" name="student_id" class="form-control" 
                            value="<?php echo htmlspecialchars($row['student_id']); ?>" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Student Name:</label>
                            <input type="text" name="student_name" class="form-control" 
                            value="<?php echo htmlspecialchars($row['student_name']); ?>" required>
                        </div>
                    </div>
                    
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label class="form-label">Course ID:</label>
                            <input type="number" name="course_id" class="form-control" 
                            value="<?php echo htmlspecialchars($row['course_id']); ?>" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Course Name:</label>
                            <input type="text" name="course_name" class="form-control" 
                            value="<?php echo htmlspecialchars($row['course_name']); ?>" required>
                        </div>
                    </div>
                    
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label class="form-label">Score:</label>
                            <input type="number" step="0.01" name="score" class="form-control" 
                            value="<?php echo htmlspecialchars($row['score']); ?>" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Grade:</label>
                            <input type="text" name="grade" class="form-control" 
                            value="<?php echo htmlspecialchars($row['grade']); ?>" readonly>
                            <small class="text-muted">Grade will be calculated automatically based on score</small>
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Remark:</label>
                        <textarea name="remark" class="form-control" rows="3"><?php echo htmlspecialchars($row['remark']); ?></textarea>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Created At:</label>
                        <input type="datetime-local" name="created_at" class="form-control"
                        value="<?php echo date('Y-m-d\TH:i', strtotime($row['created_at'])); ?>" required>
                    </div>

                    <div class="mt-4">
                        <button type="submit" name="update" class="btn btn-success px-4">Save Changes</button>
                        <a href="Grades.php" class="btn btn-secondary px-4">Cancel</a>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script>
        // Auto-calculate grade when score changes
        document.querySelector('input[name="score"]').addEventListener('change', function() {
            let score = parseFloat(this.value);
            let grade = '';
            
            if (score >= 90) {
                grade = 'A';
            } else if (score >= 80) {
                grade = 'B';
            } else if (score >= 70) {
                grade = 'C';
            } else if (score >= 60) {
                grade = 'D';
            } else {
                grade = 'F';
            }
            
            document.querySelector('input[name="grade"]').value = grade;
        });
    </script>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>