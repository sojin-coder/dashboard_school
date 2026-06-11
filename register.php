<?php
include "db.php";

if(isset($_POST['register'])){

    $name = $_POST['name'];
    $email = $_POST['email'];
    $password = password_hash($_POST['password'], PASSWORD_DEFAULT);
    $role = $_POST['role'];

    $check = mysqli_query($conn,"SELECT * FROM logins WHERE email='$email'");

    if(mysqli_num_rows($check) > 0){
        echo "<script>alert('Email already exists');</script>";
    }else{

        $insert = mysqli_query($conn,"INSERT INTO logins(name,email,password,role)
        VALUES('$name','$email','$password','$role')");

        if($insert){
            header("Location: login.php");
        }
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Register</title>

    <style>

        *{
            margin:0;
            padding:0;
            box-sizing:border-box;
            font-family:Arial;
        }

        body{
            height:100vh;
            display:flex;
            justify-content:center;
            align-items:center;
            background:#0f172a;
        }

        .box{
            width:400px;
            background:white;
            padding:30px;
            border-radius:10px;
        }

        h2{
            text-align:center;
            margin-bottom:20px;
        }

        input,select{
            width:100%;
            padding:12px;
            margin-top:10px;
            border:1px solid #ccc;
            border-radius:5px;
        }

        button{
            width:100%;
            padding:12px;
            border:none;
            background:#2563eb;
            color:white;
            margin-top:15px;
            border-radius:5px;
            cursor:pointer;
        }

        a{
            text-decoration:none;
        }

    </style>

</head>
<body>

<div class="box">

    <h2>Register</h2>

    <form method="POST">

        <input type="text" name="name" placeholder="Name" required>

        <input type="email" name="email" placeholder="Email" required>

        <input type="password" name="password" placeholder="Password" required>

        <select name="role">
            <option value="admin">Admin</option>
            <option value="teacher">Teacher</option>
            <option value="student">Student</option>
        </select>

        <button name="register">Register</button>

    </form>

</div>

</body>
</html>