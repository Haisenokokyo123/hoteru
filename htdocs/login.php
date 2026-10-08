<?php
require_once __DIR__ . '/config.php';
start_app_session();

if (isset($_SESSION['role'])) {
    header('Location: ' . role_home($_SESSION['role']));
    exit;
}

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $user = authenticate_user($_POST['email'] ?? '', $_POST['password'] ?? '');
    if ($user !== null) {
        establish_user_session($user);
        header('Location: ' . role_home($user['role']));
        exit;
    }
    $error = 'Incorrect email or password.';
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

if($error !== ''){

echo "<p style='color:red;'>" . e($error) . "</p>";

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
