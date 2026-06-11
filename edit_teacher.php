<?php
include 'db.php';

// =====================
// 1. GET DATA BY ID
// =====================
if (isset($_GET['id'])) {
    $id = $_GET['id'];

    $stmt = $conn->prepare("SELECT * FROM teachers WHERE id = ?");
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
    $image = $_POST['image'];
    $phone = $_POST['phone'];
    $gender = $_POST['gender'];
    $subject = $_POST['subject'];
    $address = $_POST['address'];
    $created_at = $_POST['created_at'];

    $stmt = $conn->prepare("UPDATE teachers SET 
        name=?, email=?,image=?, phone=?, gender=?, subject=?, address=?, created_at=? 
        WHERE id=?");

    $stmt->bind_param("ssssssssi", 
        $name, $email,$image, $phone, $gender, $subject, $address, $created_at, $id
    );

    if ($stmt->execute()) {
        echo "<script>alert('✅ Update success!'); window.location.href='teacher.php';</script>";
    } else {
        echo "❌ Error: " . $stmt->error;
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Edit Teacher</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">

<div class="container mt-5">
    <div class="card shadow">
        <div class="card-header bg-primary text-white">
            <h4>Edit Teacher</h4>
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
                <div class="mb-3">
                            <label class="form-label">Imgae (url)</label>
                            <input name="image" class="form-control" value="<?php echo $row['image']; ?>" required >
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
                            <option disabled>Select Gender</option>
                            <option value="Male" <?php if($row['gender']=="Male") echo "selected"; ?>>Male</option>
                            <option value="Female" <?php if($row['gender']=="Female") echo "selected"; ?>>Female</option>
                            <option value="Other" <?php if($row['gender']=="Other") echo "selected"; ?>>Other</option>
                        </select>
                    </div>
                </div>

                <div class="mb-3">
                    <label>Subject</label>
                    <select name="subject" class="form-select">
                            <option disabled>Select Gender</option>
                            <option value="Civil Engineering" <?php if($row['subject']=="Civil Engineering") echo "selected"; ?>>សំណង់ស៊ីវិល(Civil Engineering)</option>
                            <option value="Electronics" <?php if($row['subject']=="Electronics") echo "selected"; ?>>អេឡិចត្រូនិក(Electronics / Electronic Engineering)</option>
                            <option value="Electrical Engineering" <?php if($row['subject']=="Electrical Engineering") echo "selected"; ?>>អគ្គិសនី(Electrical Engineering)</option>
                            <option value="Accounting" <?php if($row['subject']=="Accounting") echo "selected"; ?>>គណនេយ្យ(Accounting)</option>
                            <option value="Marketing" <?php if($row['subject']=="Marketing") echo "selected"; ?>>ទីផ្សារ(Marketing)</option>
                            <option value="Management" <?php if($row['subject']=="Management") echo "selected"; ?>>គ្រប់គ្រង(Management)</option>
                            <option value="it" <?php if($row['subject']=="it") echo "selected"; ?>>ពត៏មានវិទ្យា(iT)</option>
                            
                        </select>
                </div>

                <div class="mb-3">
                    <label>Address</label>
                    <input type="text" name="address" class="form-control" 
                    value="<?php echo $row['address']; ?>">
                </div>

                <div class="mb-3">
                    <label>Created At</label>
                    <input type="date" name="created_at" class="form-control" 
                    value="<?php echo $row['created_at']; ?>">
                </div>

                <div class="mt-4">
                    <button type="submit" name="update" class="btn btn-success">
                        💾 Save Changes
                    </button>
                    <a href="teacher.php" class="btn btn-secondary">
                        Cancel
                    </a>
                </div>

            </form>
        </div>
    </div>
</div>

</body>
</html>