<?php
$servername = "localhost";
$username = "root";
$password = ""; 
$dbname = "school"; 
$port = 3307; // បន្ថែមលេខ Port ដែលឃើញក្នុង XAMPP

// បន្ថែម $port ទៅក្នុង parameter ទី៥
$conn = mysqli_connect($servername, $username, $password, $dbname, $port);

if (!$conn) {
    die("Connection failed: " . mysqli_connect_error());
}

echo "Connected successfully!";
?>