<?php


error_reporting(E_ALL);
ini_set('display_errors',1);


session_start();


require_once "config.php";



// Redirect already logged in users

if(isset($_SESSION['role'])){


    if($_SESSION['role']=="admin"){

        header("Location: admin_dashboard.php");
        exit();

    }


    elseif($_SESSION['role']=="staff"){

        header("Location: index.php");
        exit();

    }


    elseif($_SESSION['role']=="customer"){

        header("Location: customer_dashboard.php");
        exit();

    }

}




if(isset($_POST['login'])){


    $email = $_POST['email'];

    $password = $_POST['password'];



    $conn = db_connect();



    $stmt = $conn->prepare(
        "SELECT * FROM users WHERE email=? LIMIT 1"
    );



    $stmt->bind_param(
        "s",
        $email
    );



    $stmt->execute();



    $result = $stmt->get_result();




    if($result->num_rows > 0){



        $user = $result->fetch_assoc();




        // Your database currently uses plain passwords

        if($password == $user['password']){


            $_SESSION['id'] = $user['id'];

            $_SESSION['name'] = $user['name'];

            $_SESSION['role'] = $user['role'];




            if($user['role']=="admin"){


                header("Location: admin_dashboard.php");

                exit();


            }



            elseif($user['role']=="staff"){


                header("Location: index.php");

                exit();


            }



            elseif($user['role']=="customer"){


                header("Location: customer_dashboard.php");

                exit();


            }



        }

        else{


            $error = "Incorrect password";


        }




    }

    else{


        $error = "Account not found";


    }


}



?>



<!DOCTYPE html>

<html>

<head>


<title>
Bongabong View Hotel Login
</title>


<link rel="stylesheet" href="style.css">


</head>



<body class="login-body">



<div class="login-box">



<img src="puno.png" class="login-logo">



<h2>
Bongabong View Hotel
</h2>




<?php

if(isset($error)){

echo "<p style='color:red;'>$error</p>";

}

?>





<form method="POST">



<input

type="email"

name="email"

placeholder="Email"

required>



<br><br>




<input

type="password"

name="password"

placeholder="Password"

required>



<br><br>




<button name="login">

Login

</button>



</form>





<br>



<a href="customer_register.php">

Create Customer Account

</a>




</div>



</body>


</html>