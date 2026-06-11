<?php
    // session_start();
    include "db.php";
    
    if(isset($_POST['login'])){
    
        $email = $_POST['email'];
        $password = $_POST['password'];
        $role = $_POST['role'];
    
        $sql = mysqli_query($conn,
        "SELECT * FROM logins 
        WHERE email='$email' AND role='$role'");
    
        if(mysqli_num_rows($sql) > 0){
    
            $row = mysqli_fetch_assoc($sql);
    
            if(password_verify($password,$row['password'])){
    
                $_SESSION['id'] = $row['id'];
                $_SESSION['name'] = $row['name'];
                $_SESSION['role'] = $row['role'];
    
                // LOGIN BY ROLE
                if($role == "admin"){
    
                    echo "
                    <script>
                        sessionStorage.setItem('login','true');
                        window.location='index.php';
                    </script>
                    ";
    
                }elseif($role == "teacher"){
    
                    echo "
                    <script>
                        sessionStorage.setItem('login', 'true');
                        window.location.href='forteacher.php';
                    </script>
                    ";
    
                }elseif($role == "student"){
    
                    echo "
                    <script>
                        sessionStorage.setItem('login','true');
                       window.location.href='forstudent.php';
                    </script>
                    ";
                }
    
            }else{
                echo "<script>alert('Password Incorrect')</script>";
            }
    
        }else{
            echo "<script>alert('Email or Role Incorrect')</script>";
        }
    }
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login By Role</title>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet">

    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">

    <style>

        *{
            margin:0;
            padding:0;
            box-sizing:border-box;
            font-family:Arial;
        }

        body{
            min-height:100vh;
            display:flex;
            justify-content:center;
            align-items:center;
            background:#0f172a;
            padding:20px;
        }

        .form_top{
            width:100%;
            max-width:1100px;
            background:white;
            border-radius:25px;
            overflow:hidden;
            display:flex;
            flex-wrap:wrap;
        }

        .img_form{
            flex:1;
            min-width:350px;
            background:#e2e8f0;
            display:flex;
            justify-content:center;
            align-items:center;
            padding:20px;
        }

        .img_form img{
            width:100%;
            max-width:490px;
            border-radius:20px;
        }

        .login-box{
            flex:1;
            min-width:350px;
            padding:40px;
        }

        .btn-group-custom{
            display:flex;
            gap:10px;
            background:#f1f5f9;
            padding:8px;
            border-radius:50px;
            margin-bottom:30px;
        }

        .role-btn{
            flex:1;
            border:none;
            padding:12px;
            border-radius:40px;
            background:transparent;
            cursor:pointer;
            font-weight:bold;
            transition:0.3s;
        }

        .role-btn.active{
            background:white;
            color:#2563eb;
            box-shadow:0 5px 15px rgba(0,0,0,0.1);
        }

        h2{
            margin-bottom:10px;
            color:#1e3a8a;
        }

        .login-subhead{
            color:gray;
            margin-bottom:25px;
        }

        .input-group-custom{
            position:relative;
            margin-bottom:20px;
        }

        .input-group-custom i{
            position:absolute;
            top:50%;
            left:15px;
            transform:translateY(-50%);
            color:gray;
        }

        .input-group-custom input{
            width:100%;
            padding:14px 14px 14px 45px;
            border:1px solid #cbd5e1;
            border-radius:15px;
            outline:none;
        }

        .input-group-custom input:focus{
            border-color:#2563eb;
        }

        .btn-login{
            width:100%;
            padding:14px;
            border:none;
            border-radius:40px;
            background:#2563eb;
            color:white;
            font-weight:bold;
            cursor:pointer;
            transition:0.3s;
        }

        .btn-login:hover{
            background:#1d4ed8;
        }

        .register-link{
            text-align:center;
            margin-top:20px;
        }

        .register-link a{
            text-decoration:none;
            color:#2563eb;
            font-weight:bold;
        }

        @media(max-width:850px){

            .form_top{
                flex-direction:column;
            }

            .img_form img{
                max-height:250px;
            }
        }

    </style>

</head>
<body>

<div class="form_top">

    <!-- IMAGE -->

    <div class="img_form">
        <img src="https://i.pinimg.com/736x/10/94/80/10948000785c904a6b9fa42e860c5b98.jpg">
    </div>

    <!-- LOGIN -->

    <div class="login-box">

        <!-- ROLE BUTTON -->

        <div class="btn-group-custom">

            <button type="button"
            class="role-btn active"
            id="adminBtn">

                <i class="fas fa-user-shield"></i>
                Admin

            </button>

            <button type="button"
            class="role-btn"
            id="teacherBtn">

                <i class="fas fa-chalkboard-user"></i>
                Teacher

            </button>

            <button type="button"
            class="role-btn"
            id="studentBtn">

                <i class="fas fa-graduation-cap"></i>
                Student

            </button>

        </div>

        <h2>Welcome Back</h2>

        <div class="login-subhead">
            Sign in to access your dashboard
        </div>

        <!-- FORM -->

        <form method="POST" id="loginForm">

            <!-- HIDDEN ROLE -->

            <input type="hidden"
            name="role"
            id="role"
            value="admin">

            <!-- EMAIL -->

            <div class="input-group-custom">

                <i class="fas fa-envelope"></i>

                <input type="email"
                name="email"
                placeholder="Email Address"
                required>

            </div>

            <!-- PASSWORD -->

            <div class="input-group-custom">

                <i class="fas fa-lock"></i>

                <input type="password"
                name="password"
                placeholder="Password"
                required>

            </div>

            <!-- BUTTON -->

            <button type="submit"
            name="login"
            class="btn-login">

                <i class="fas fa-right-to-bracket"></i>
                Login

            </button>

        </form>

        <div class="register-link">
            Don't have account ?
            <a href="register.php">
                Register
            </a>
        </div>

    </div>

</div>

<script>

    const adminBtn = document.getElementById('adminBtn');
    const teacherBtn = document.getElementById('teacherBtn');
    const studentBtn = document.getElementById('studentBtn');

    const roleInput = document.getElementById('role');

    const roleBtns = [
        adminBtn,
        teacherBtn,
        studentBtn
    ];

    function setActiveRole(activeBtn, role){

        roleBtns.forEach(btn => {
            btn.classList.remove('active');
        });

        activeBtn.classList.add('active');

        roleInput.value = role;
    }

    adminBtn.addEventListener('click', () => {
        setActiveRole(adminBtn,'admin');
    });

    teacherBtn.addEventListener('click', () => {
        setActiveRole(teacherBtn,'teacher');
    });

    studentBtn.addEventListener('click', () => {
        setActiveRole(studentBtn,'student');
    });

</script>

</body>
</html>