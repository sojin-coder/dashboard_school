<?php
// អនុញ្ញាតឱ្យ React អាចទាញយកទិន្នន័យបាន (CORS)
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers: Content-Type");
header("Content-Type: application/json; charset=UTF-8");

include "../db.php"; // ពិនិត្យមើលផ្លូវទៅកាន់ db.php ឱ្យត្រូវឡើងវិញ
// session_start();

// ទទួលទិន្នន័យ JSON ពី React
$data = json_decode(file_get_contents("php://input"), true);

if (isset($data['email']) && isset($data['password']) && isset($data['role'])) {
    $email = mysqli_real_escape_string($conn, $data['email']);
    $password = $data['password'];
    $role = mysqli_real_escape_string($conn, $data['role']);

    $sql = mysqli_query($conn, "SELECT * FROM logins WHERE email='$email' AND role='$role'");

    if (mysqli_num_rows($sql) > 0) {
        $row = mysqli_fetch_assoc($sql);

        if (password_verify($password, $row['password'])) {
            // រក្សាទុកក្នុង Session
            $_SESSION['id'] = $row['id'];
            $_SESSION['name'] = $row['name'];
            $_SESSION['email'] = $row['email'];
            $_SESSION['role'] = $row['role'];
            $_SESSION['student_logged_in'] = true;
            $_SESSION['splash_shown'] = false;
            $_SESSION['student_image'] = $row['image'] ?? 'https://i.pinimg.com/736x/be/dd/b8/beddb8c8c3c4c967cb821aae0cb796e3.jpg';

            // ឆ្លើយតបទៅ React វិញថាជោគជ័យ
            echo json_encode(["success" => true, "message" => "Login Successful"]);
        } else {
            echo json_encode(["success" => false, "message" => "Password Incorrect"]);
        }
    } else {
        echo json_encode(["success" => false, "message" => "Email or Role Incorrect"]);
    }
} else {
    echo json_encode(["success" => false, "message" => "Invalid Request"]);
}
?>