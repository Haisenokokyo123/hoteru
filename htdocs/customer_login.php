<?php

session_start();

include "config.php";


$error = "";


if ($_SERVER["REQUEST_METHOD"] == "POST") {


    $conn = db_connect();


    $email = mysqli_real_escape_string(
        $conn,
        $_POST["email"]
    );


    $password = mysqli_real_escape_string(
        $conn,
        $_POST["password"]
    );



    $query = mysqli_query(
        $conn,
        "
        SELECT *
        FROM users
        WHERE email='$email'
        AND role='customer'
        LIMIT 1
        "
    );



    if ($query && mysqli_num_rows($query) > 0) {


        $user = mysqli_fetch_assoc($query);



        /*
        Temporary password checking.
        Later we will upgrade this
        using password_hash()
        */


        if ($password == $user["password"]) {



            $_SESSION["logged_in"] = true;

            $_SESSION["user_id"] = $user["id"];

            $_SESSION["name"] = $user["name"];

            $_SESSION["role"] = "customer";



            header("Location: customer_home.php");

            exit();



        } else {


            $error = "Incorrect password.";


        }



    } else {


        $error = "Customer account not found.";


    }



}


?>


<!DOCTYPE html>

<html>

<head>

<title>
Customer Login - Hoteru
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
Customer Login
</p>





<?php if($error != "") { ?>

<div class="error-box">

<?php echo $error; ?>

</div>

<?php } ?>






<form method="POST">



<label>
Email
</label>


<input
type="email"
name="email"
placeholder="Enter email"
required>




<label>
Password
</label>


<input
type="password"
name="password"
placeholder="Enter password"
required>




<button type="submit">

Login

</button>



</form>




<br>


<p>
Don't have an account?
</p>


<a href="customer_register.php">

Create Account

</a>



<br><br>


<a href="login.php">

Back

</a>



</div>



</body>

</html>