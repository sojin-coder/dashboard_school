<?php
include 'db.php';

// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Initialize variables
$row = [];

// =====================
// 1. GET DATA BY ID
// =====================
if (isset($_GET['id'])) {
    $id = $_GET['id'];

    // Use prepare statement to prevent SQL injection
    $stmt = $conn->prepare("SELECT * FROM students WHERE id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();

    if (!$row) {
        echo "<script>alert('No data found!'); window.location.href='student.php';</script>";
        exit();
    }
} else {
    echo "<script>alert('Invalid request!'); window.location.href='student.php';</script>";
    exit();
}

// =====================
// 2. UPDATE DATA
// =====================
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['update'])) {

    $id = $_POST['id'];
    $name = $_POST['name'];
    $email = $_POST['email'];
    $phone = $_POST['phone'];
    $gender = $_POST['gender'];
    $image = $_POST['image'];
    $age = $_POST['age'];
    $dob = $_POST['dob'];
    $address = $_POST['address'];
    $grade = $_POST['grade'];
    $college = $_POST['college'];
    $skill = $_POST['skill'];
    $Shift = $_POST['Shift'];

    // Validate required fields
    if (empty($name) || empty($email)) {
        echo "<script>alert('Name and Email are required!');</script>";
    } else {
        $stmt = $conn->prepare("UPDATE students SET 
            name=?, 
            email=?, 
            phone=?, 
            gender=?, 
            image=?, 
            age=?, 
            dob=?, 
            address=?, 
            grade=?, 
            college=?, 
            skill=?, 
            Shift=? 
            WHERE id=?");

        $stmt->bind_param("sssssissssssi", 
            $name, 
            $email, 
            $phone, 
            $gender,
            $image,
            $age,
            $dob,
            $address,
            $grade, 
            $college,
            $skill,
            $Shift, 
            $id
        );

        if ($stmt->execute()) {
            echo "<script>alert('✅ Update success!'); window.location.href='student.php';</script>";
            exit();
        } else {
            echo "<script>alert('❌ Error: " . $stmt->error . "');</script>";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Student - KRaksa</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        body {
            font-family: 'Inter', sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            padding: 40px 0;
        }
        .card {
            border: none;
            border-radius: 20px;
            box-shadow: 0 20px 40px rgba(0,0,0,0.1);
            overflow: hidden;
        }
        .card-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            padding: 20px 30px;
            border: none;
        }
        .card-header h4 {
            margin: 0;
            font-weight: 600;
        }
        .card-header h4 i {
            margin-right: 10px;
        }
        .card-body {
            padding: 30px;
        }
        .form-label {
            font-weight: 600;
            color: #2c3e50;
            margin-bottom: 8px;
        }
        .form-control, .form-select {
            border-radius: 10px;
            border: 1px solid #e0e4e8;
            padding: 10px 15px;
            transition: all 0.2s ease;
        }
        .form-control:focus, .form-select:focus {
            border-color: #667eea;
            box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
            outline: none;
        }
        .btn-success {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border: none;
            padding: 10px 25px;
            border-radius: 10px;
            font-weight: 600;
        }
        .btn-success:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(102, 126, 234, 0.4);
        }
        .btn-secondary {
            padding: 10px 25px;
            border-radius: 10px;
            font-weight: 600;
        }
        .required:after {
            content: " *";
            color: red;
        }
    </style>
</head>
<body>

<div class="container">
    <div class="row justify-content-center">
        <div class="col-lg-10">
            <div class="card shadow">
                <div class="card-header bg-primary text-white">
                    <h4>
                        <i class="fas fa-user-edit"></i> 
                        Edit Student Information
                    </h4>
                </div>

                <div class="card-body">
                    <form method="POST">
                        <!-- Hidden ID -->
                        <input type="hidden" name="id" value="<?php echo htmlspecialchars($row['id']); ?>">

                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label class="form-label required">Student Name</label>
                                <input type="text" name="name" class="form-control" 
                                    value="<?php echo htmlspecialchars($row['name']); ?>" required>
                            </div>

                            <div class="col-md-6">
                                <label class="form-label required">Email</label>
                                <input type="email" name="email" class="form-control" 
                                    value="<?php echo htmlspecialchars($row['email']); ?>" required>
                            </div>
                        </div>
                         <div class="mb-3">
                            <label class="form-label">Imgae (url)</label>
                            <input type="text" name="image" class="form-control"
                            value="<?php echo htmlspecialchars($row['image']); ?>" required >
                        </div>

                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label class="form-label">Phone Number</label>
                                <input type="text" name="phone" class="form-control" 
                                    value="<?php echo htmlspecialchars($row['phone']); ?>">
                            </div>

                            <div class="col-md-6">
                                <label class="form-label">Gender</label>
                                <select name="gender" class="form-select">
                                    <option value="Male" <?php echo ($row['gender'] == "Male") ? "selected" : ""; ?>>Male</option>
                                    <option value="Female" <?php echo ($row['gender'] == "Female") ? "selected" : ""; ?>>Female</option>
                                    <option value="Other" <?php echo ($row['gender'] == "Other") ? "selected" : ""; ?>>Other</option>
                                </select>
                            </div>
                        </div>

                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label class="form-label">Age</label>
                                <input type="number" name="age" class="form-control" 
                                    value="<?php echo htmlspecialchars($row['age']); ?>">
                            </div>

                            <div class="col-md-6">
                                <label class="form-label">Date of Birth</label>
                                <input type="date" name="dob" class="form-control" 
                                    value="<?php echo htmlspecialchars($row['dob']); ?>">
                            </div>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Address</label>
                            <textarea name="address" class="form-control" rows="2"><?php echo htmlspecialchars($row['address']); ?></textarea>
                        </div>

                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label class="form-label">Grade</label>
                                <select name="grade" class="form-select">
                                    <option value="Master's Degree" <?php echo ($row['grade'] == "Master's Degree") ? "selected" : ""; ?>>Master's Degree</option>
                                    <option value="Associate Degree" <?php echo ($row['grade'] == "Associate Degree") ? "selected" : ""; ?>>Associate Degree</option>
                                    <option value="Bachelor's Degree" <?php echo ($row['grade'] == "Bachelor's Degree") ? "selected" : ""; ?>>Bachelor's Degree</option>
                                    <option value="High School" <?php echo ($row['grade'] == "High School") ? "selected" : ""; ?>>High School</option>
                                </select>
                            </div>

                            <div class="col-md-6">
                                <label class="form-label">College</label>
                                <select name="college" class="form-select">
                                    <option value="Electrical Engineering" <?php echo ($row['college'] == "Electrical Engineering") ? "selected" : ""; ?>>Electrical Engineering</option>
                                    <option value="Business Science" <?php echo ($row['college'] == "Business Science") ? "selected" : ""; ?>>Business Science</option>
                                    <option value="Electronics" <?php echo ($row['college'] == "Electronics") ? "selected" : ""; ?>>Electronics</option>
                                    <option value="Civil Engineering" <?php echo ($row['college'] == "Civil Engineering") ? "selected" : ""; ?>>Civil Engineering</option>
                                    <option value="it" <?php echo ($row['college'] == "it") ? "selected" : ""; ?>>Information Technology (IT)</option>
                                </select>
                            </div>
                        </div>

                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label class="form-label">Skill</label>
                                <select name="skill" class="form-select">
                                    <option value="Accounting" <?php echo ($row['skill'] == "Accounting") ? "selected" : ""; ?>>Accounting</option>
                                    <option value="Marketing" <?php echo ($row['skill'] == "Marketing") ? "selected" : ""; ?>>Marketing</option>
                                    <option value="Management" <?php echo ($row['skill'] == "Management") ? "selected" : ""; ?>>Management</option>
                                    <option value="it" <?php echo ($row['skill'] == "it") ? "selected" : ""; ?>>Information Technology (IT)</option>
                                    <option value="Civil" <?php echo ($row['skill'] == "Civil") ? "selected" : ""; ?>>Civil Engineering</option>
                                    <option value="Electronics" <?php echo ($row['skill'] == "Electronics") ? "selected" : ""; ?>>Electronics</option>
                                    <option value="Electrical" <?php echo ($row['skill'] == "Electrical") ? "selected" : ""; ?>>Electrical Engineering</option>
                                </select>
                            </div>

                            <div class="col-md-6">
                                <label class="form-label">Shift</label>
                                <select name="Shift" class="form-select">
                                    <option value="morning" <?php echo ($row['Shift'] == "morning") ? "selected" : ""; ?>>Morning</option>
                                    <option value="evening" <?php echo ($row['Shift'] == "evening") ? "selected" : ""; ?>>Evening</option>
                                </select>
                            </div>
                        </div>

                        <div class="mt-4 d-flex gap-2">
                            <button type="submit" name="update" class="btn btn-success">
                                <i class="fas fa-save"></i> Save Changes
                            </button>
                            <a href="index.php" class="btn btn-secondary">
                                <i class="fas fa-times"></i> Cancel
                            </a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>