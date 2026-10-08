<?php
require_once __DIR__ . '/config.php';
start_app_session();

if (isset($_SESSION['role'])) {
    header('Location: ' . role_home($_SESSION['role']));
    exit;
}

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $user = authenticate_user($_POST['email'] ?? '', $_POST['password'] ?? '', 'customer');
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

<?php echo e($error); ?>

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
