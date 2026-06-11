<?php
include 'db.php';
error_reporting(E_ALL);
ini_set('display_errors', 1);

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    
    // Debug: Show received data
    echo "<h3>Debug Information</h3>";
    echo "<strong>Received POST Data:</strong><br>";
    echo "<pre>";
    print_r($_POST);
    echo "</pre><hr>";
    
    // Escape all input values
    $name = mysqli_real_escape_string($conn, $_POST['name']);
    $gender = mysqli_real_escape_string($conn, $_POST['gender']);
    $image = mysqli_real_escape_string($conn, $_POST['image']);
    $age = !empty($_POST['age']) ? intval($_POST['age']) : 0;
    $dob = mysqli_real_escape_string($conn, $_POST['dob']);
    $email = mysqli_real_escape_string($conn, $_POST['email']);
    $phone = mysqli_real_escape_string($conn, $_POST['phone']);
    $address = mysqli_real_escape_string($conn, $_POST['address']);
    $grade = mysqli_real_escape_string($conn, $_POST['grade']);
    $college = mysqli_real_escape_string($conn, $_POST['college']);
    $skill = mysqli_real_escape_string($conn, $_POST['skill']);
    $year = mysqli_real_escape_string($conn, $_POST['year']);
    $shift = mysqli_real_escape_string($conn, $_POST['Shift']);
    
    // Show escaped values
    echo "<strong>Escaped Values:</strong><br>";
    echo "Name: $name<br>";
    echo "Gender: $gender<br>";
    echo "Age: $age<br>";
    echo "Email: $email<br>";
    echo "Skill: $skill<br>";
    echo "Year: $year<br>";
    echo "Shift: $shift<br><hr>";
    
    // Build SQL query
    $sql = "INSERT INTO students (name, gender, image, age, dob, email, phone, address, grade, college, skill, year, Shift) 
            VALUES ('$name', '$gender', '$image', $age, '$dob', '$email', '$phone', '$address', '$grade', '$college', '$skill', '$year', '$shift')";
    
    echo "<strong>SQL Query:</strong><br>";
    echo htmlspecialchars($sql);
    echo "<hr>";
    
    // Execute query
    if (mysqli_query($conn, $sql)) {
        echo "<span style='color: green; font-weight: bold;'>✅ Success! Student inserted successfully.</span><br>";
        echo "<a href='student.php'>Click here to go back to Student Page</a>";
        
        // Auto redirect after 3 seconds
        header("refresh:3;url=student.php?success=1");
    } else {
        echo "<span style='color: red; font-weight: bold;'>❌ Error!</span><br>";
        echo "<strong>MySQL Error:</strong> " . mysqli_error($conn) . "<br>";
        echo "<strong>Error Number:</strong> " . mysqli_errno($conn) . "<br>";
        echo "<br><a href='student.php'>Go back to Student Page</a>";
    }
    
    // Close connection
    mysqli_close($conn);
} else {
    header("Location: student.php");
    exit();
}
?>