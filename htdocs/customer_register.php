<?php

session_start();

include "config.php";


$error = "";
$success = "";


if ($_SERVER["REQUEST_METHOD"] == "POST") {


    $conn = db_connect();



    $name = mysqli_real_escape_string(
        $conn,
        $_POST["name"]
    );


    $email = mysqli_real_escape_string(
        $conn,
        $_POST["email"]
    );


    $password = mysqli_real_escape_string(
        $conn,
        $_POST["password"]
    );



    // Check if email already exists

    $check = mysqli_query(
        $conn,
        "
        SELECT *
        FROM users
        WHERE email='$email'
        "
    );



    if(mysqli_num_rows($check) > 0){


        $error = "Email already registered.";


    } else {



        $insert = mysqli_query(
            $conn,
            "
            INSERT INTO users
            (
                name,
                email,
                password,
                role
            )

            VALUES

            (
                '$name',
                '$email',
                '$password',
                'customer'
            )
            "
        );



        if($insert){


            $success = "Account created successfully. You can now login.";


        } else {


            $error = "Registration failed.";


        }


    }



}


?>


<!DOCTYPE html>

<html>

<head>

<title>
Customer Registration - Hoteru
</title>


<link rel="stylesheet" href="style.css?v=1003">


</head>


<body class="login-body">



<div class="login-box">



<img src="puno.png" class="login-logo">



<h2>
Bongabong View Hotel
</h2>


<p>
Create Customer Account
</p>




<?php if($error != "") { ?>

<div class="error-box">

<?php echo $error; ?>

</div>

<?php } ?>




<?php if($success != "") { ?>

<div class="success-box">

<?php echo $success; ?>

</div>

<?php } ?>






<form method="POST">



<label>
Full Name
</label>


<input
type="text"
name="name"
placeholder="Enter your name"
required>





<label>
Email
</label>


<input
type="email"
name="email"
placeholder="Enter your email"
required>





<label>
Password
</label>


<input
type="password"
name="password"
placeholder="Create password"
required>





<button type="submit">

Create Account

</button>



</form>




<br>


<a href="customer_login.php">

Already have an account? Login

</a>



<br><br>


<a href="login.php">

Back

</a>



</div>



</body>

</html>