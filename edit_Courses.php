<?php
include 'db.php';

// 1. GET DATA BY ID
if (isset($_GET['id'])) {
    $id = $_GET['id'];
    $result = mysqli_query($conn, "SELECT * FROM courses WHERE id = $id");
    $row = mysqli_fetch_assoc($result);
} else {
    echo "No ID found!";
    exit;
}

// 2. UPDATE DATA
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['update'])) {

    $id = $_POST['id'];
    $name = $_POST['name'];
    $time_star = $_POST['time_star'];
    $time_end = $_POST['time_end'];
    $description = $_POST['description'];
    $teacher_id = $_POST['teacher_id'];
    $teacher_name = $_POST['teacher_name'];
    $teacher_phone = $_POST['teacher_phone'];
    $price = $_POST['price'];
    $duration = $_POST['duration'];

    $sql = "UPDATE courses SET 
        name='$name',
        time_star='$time_star',
        time_end='$time_end',
        description='$description',
        teacher_id='$teacher_id',
        teacher_name='$teacher_name',
        teacher_phone='$teacher_phone',
        price='$price',
        duration='$duration'
        WHERE id=$id";

    if (mysqli_query($conn, $sql)) {
        echo "<script>alert('Update Success!'); window.location.href='Courses.php';</script>";
    } else {
        echo "Error: " . mysqli_error($conn);
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Edit Course</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">

<div class="container mt-5">
    <div class="card shadow">
        <div class="card-header bg-primary text-white">
            <h4>Edit Course Information</h4>
        </div>

        <div class="card-body">
            <form method="POST">

                <!-- Hidden ID -->
                <input type="hidden" name="id" value="<?php echo $row['id']; ?>">
                 <div class="mb-3">
                    <label>name:</label>
                    <select name="name" class="form-select">
                            <option disabled>Select skill</option>
                            <option value="Civil Engineering" <?php if($row['name']=="Civil Engineering") echo "selected"; ?>>សំណង់ស៊ីវិល(Civil Engineering)</option>
                            <option value="Electronics " <?php if($row['name']=="Electronics") echo "selected"; ?>>អេឡិចត្រូនិក(Electronics / Electronic Engineering)</option>
                            <option value="Electrical Engineering" <?php if($row['name']=="Electrical Engineering") echo "selected"; ?>>អគ្គិសនី(Electrical Engineering)</option>
                            <option value="Accounting" <?php if($row['name']=="Accounting") echo "selected"; ?>>គណនេយ្យ(Accounting)</option>
                            <option value="Marketing" <?php if($row['name']=="Marketing") echo "selected"; ?>>ទីផ្សារ(Marketing)</option>
                            <option value="Management" <?php if($row['name']=="Management") echo "selected"; ?>>គ្រប់គ្រង(Management)</option>
                            <option value="it" <?php if($row['name']=="it") echo "selected"; ?>>ពត៏មានវិទ្យា(iT)</option>
                            <!-- <option value="" <?php if($row['skill']=="") echo "selected"; ?>></option> -->
                           
                            
                        </select>
                </div>

                <div class=" mb-3">
                    

                    
                        <label>Start Time</label>
                        <input type="date" name="time_star" class="form-control"
                               value="<?php echo $row['time_star']; ?>" required>
                    
                </div>

                <div class="row mb-3">
                    <div class="col-md-6">
                        <label>End Time</label>
                        <input type="date" name="time_end" class="form-control"
                               value="<?php echo $row['time_end']; ?>">
                    </div>

                    <div class="col-md-6">
                        <label>Description</label>
                        <input type="text" name="description" class="form-control"
                               value="<?php echo $row['description']; ?>">
                    </div>
                </div>

                <div class="mb-3">
                    <label>Teacher ID</label>
                    <input type="number" name="teacher_id" class="form-control"
                           value="<?php echo $row['teacher_id']; ?>">
                </div>

                <div class="mb-3">
                    <label>Teacher Name</label>
                    <input type="text" name="teacher_name" class="form-control"
                           value="<?php echo $row['teacher_name']; ?>">
                </div>

                <div class="mb-3">
                    <label>Teacher Phone</label>
                    <input type="text" name="teacher_phone" class="form-control"
                           value="<?php echo $row['teacher_phone']; ?>">
                </div>

                <div class="mb-3">
                    <label>Price</label>
                    <input type="text" name="price" class="form-control"
                           value="<?php echo $row['price']; ?>">
                </div>
                <div class="mb-3">
                   <label class="form-label">Shift:</label>
                        <select name="Shift" class="form-select">
                            <option disabled>Select Gender</option>
                            <option value="Morning" <?php if($row['Shift']=="Morning") echo "selected"; ?>>Morning</option>
                            <option value="Evening" <?php if($row['Shift']=="Evening") echo "selected"; ?>>Evening</option>
                            <!-- <option value="Other" <?php if($row['gender']=="Other") echo "selected"; ?>>Other</option> -->
                        </select>
                    </div>
              

                <div class="mb-3">
                    <label>Duration</label>
                    <input type="time" name="duration" class="form-control"
                           value="<?php echo $row['duration']; ?>">
                </div>

                <div class="mt-4">
                    <button type="submit" name="update" class="btn btn-success px-4">
                        Save Changes
                    </button>
                    <a href="Courses.php" class="btn btn-secondary px-4">
                        Cancel
                    </a>
                </div>

            </form>
        </div>
    </div>
</div>

</body>
</html>