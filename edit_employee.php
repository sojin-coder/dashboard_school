<?php
include 'db.php';

// =====================
// 1. GET DATA BY ID
// =====================
if (isset($_GET['id'])) {
    $id = $_GET['id'];

    $stmt = $conn->prepare("SELECT * FROM employees WHERE id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();
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
    $positive = $_POST['positive'];
    $salary = $_POST['salary'];
    $hire_date = $_POST['hire_date'];
    $status = $_POST['status'];
    $address = $_POST['address'];

    $stmt = $conn->prepare("UPDATE employees SET 
        name=?, email=?, phone=?, gender=?, positive=?, salary=?, hire_date=?, status=?, address=? 
        WHERE id=?");

    $stmt->bind_param("sssssssssi",
        $name, $email, $phone, $gender, $positive, $salary, $hire_date, $status, $address, $id
    );

    if ($stmt->execute()) {
        echo "<script>alert('✅ Update success!'); window.location.href='Employees.php';</script>";
    } else {
        echo "❌ Error: " . $stmt->error;
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Edit Employee</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">

<div class="container mt-5">
    <div class="card shadow">
        <div class="card-header bg-primary text-white">
            <h4>Edit Employee</h4>
        </div>

        <div class="card-body">
            <form method="POST">

                <!-- Hidden ID -->
                <input type="hidden" name="id" value="<?php echo $row['id']; ?>">

                <div class="row mb-3">
                    <div class="col-md-6">
                        <label>Name</label>
                        <input type="text" name="name" class="form-control" 
                        value="<?php echo $row['name']; ?>" required>
                    </div>

                    <div class="col-md-6">
                        <label>Email</label>
                        <input type="email" name="email" class="form-control" 
                        value="<?php echo $row['email']; ?>" required>
                    </div>
                </div>

                <div class="row mb-3">
                    <div class="col-md-6">
                        <label>Phone</label>
                        <input type="text" name="phone" class="form-control" 
                        value="<?php echo $row['phone']; ?>">
                    </div>

                    <div class="col-md-6">
                        <label>Gender</label>
                        <select name="gender" class="form-select">
                            <option value="Male" <?php if($row['gender']=="Male") echo "selected"; ?>>Male</option>
                            <option value="Female" <?php if($row['gender']=="Female") echo "selected"; ?>>Female</option>
                        </select>
                    </div>
                </div>

                <div class="mb-3">
                    <label>Position</label>
                    <input type="text" name="positive" class="form-control" 
                    value="<?php echo $row['positive']; ?>">
                </div>

                <div class="mb-3">
                    <label>Salary</label>
                    <input type="number" name="salary" class="form-control" 
                    value="<?php echo $row['salary']; ?>">
                </div>

                <div class="mb-3">
                    <label>Hire Date</label>
                    <input type="date" name="hire_date" class="form-control" 
                    value="<?php echo $row['hire_date']; ?>">
                </div>

                <div class="mb-3">
                    <label>Status</label>
                    <select name="status" class="form-select">
                        <option value="Active" <?php if($row['status']=="Active") echo "selected"; ?>>Active</option>
                        <option value="Inactive" <?php if($row['status']=="Inactive") echo "selected"; ?>>Inactive</option>
                        <option value="Resigned" <?php if($row['status']=="Resigned") echo "selected"; ?>>Resigned</option>
                        <option value="Terminated" <?php if($row['status']=="Terminated") echo "selected"; ?>>Terminated</option>
                        <option value="On Leave" <?php if($row['status']=="On Leave") echo "selected"; ?>>On Leave</option>
                    </select>
                </div>

                <div class="mb-3">
                    <label>Address</label>
                    <textarea name="address" class="form-control"><?php echo $row['address']; ?></textarea>
                </div>

                <div class="mt-4">
                    <button type="submit" name="update" class="btn btn-success">
                        💾 Save Changes
                    </button>
                    <a href="Employees.php" class="btn btn-secondary">
                        Cancel
                    </a>
                </div>

            </form>
        </div>
    </div>
</div>

</body>
</html>