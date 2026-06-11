<?php
include 'db.php';

if (isset($_GET['id'])) {
    $id = $_GET['id'];

    // បង្កើត Query ដើម្បីលុប
    $sql = "DELETE FROM employees WHERE id = $id";

    if (mysqli_query($conn, $sql)) {
        // បើលុបជោគជ័យ ឱ្យត្រឡប់ទៅទំព័រមុនវិញ
        header("Location: " . $_SERVER['HTTP_REFERER']);
        exit();
    } else {
        echo "Error deleting record: " . mysqli_error($conn);
    }
}
?>