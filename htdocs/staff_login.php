<?php
require_once __DIR__ . '/config.php';
start_app_session();

if (isset($_SESSION['role'])) {
    header('Location: ' . role_home($_SESSION['role']));
    exit;
}

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $user = authenticate_user($_POST['email'] ?? '', $_POST['password'] ?? '', 'staff');
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
Staff Login - Hoteru
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
Front Desk Staff Login
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
placeholder="Enter staff email"
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


<a href="login.php">

Back to Login Selection

</a>



</div>



</body>


</html>
