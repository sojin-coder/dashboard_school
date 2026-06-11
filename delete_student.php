<?php
include 'db.php';

if (isset($_GET['id'])) {
    $id = $_GET['id'];
    
    // Prepare delete statement
    $stmt = $conn->prepare("DELETE FROM students WHERE id = ?");
    $stmt->bind_param("i", $id);
    
    if ($stmt->execute()) {
        echo "<script>alert('Student deleted successfully!'); window.location.href='student.php';</script>";
    } else {
        echo "<script>alert('Error deleting student: " . $stmt->error . "'); window.location.href='student.php';</script>";
    }
} else {
    echo "<script>alert('Invalid request!'); window.location.href='student.php';</script>";
}
?>