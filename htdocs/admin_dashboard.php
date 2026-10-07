<?php

session_start();

if(!isset($_SESSION['role']) || $_SESSION['role'] != "admin"){

    header("Location: login.php");
    exit();

}

?>


<!DOCTYPE html>

<html>

<head>

<title>
Admin Dashboard
</title>

</head>


<body>


<h1>
Welcome Admin
</h1>


<p>
You are logged in as:

<?php echo $_SESSION['name']; ?>

</p>


<a href="logout.php">
Logout
</a>


</body>

</html>