<?php

session_start();


if(!isset($_SESSION['role']) || $_SESSION['role'] != "staff"){

    header("Location: login.php");
    exit();

}

?>


<!DOCTYPE html>

<html>

<head>

<title>
Staff Dashboard
</title>

</head>


<body>


<h1>
Welcome Staff
</h1>


<p>
Logged in:

<?php echo $_SESSION['name']; ?>

</p>


<a href="logout.php">
Logout
</a>


</body>

</html>