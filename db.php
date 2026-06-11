

<?php

$host = "localhost";
$user = "root";
$pass = "";
$db   = "school";
$port = 3307;

$conn = new mysqli($host, $user, $pass, $db, $port);

// Check Connection
if ($conn->connect_error) {
    die("Connection Failed : " . $conn->connect_error);
}

// UTF8
$conn->set_charset("utf8mb4");

// Session
session_start();

?>