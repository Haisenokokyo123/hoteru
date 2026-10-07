<?php

session_start();


if(!isset($_SESSION['role']) || $_SESSION['role'] != "customer"){

    header("Location: login.php");
    exit();

}

?>


<!DOCTYPE html>

<html>

<head>

<title>
Customer Booking Portal
</title>

</head>


<body>


<h1>
Welcome Customer
</h1>


<p>
Logged in:

<?php echo $_SESSION['name']; ?>

</p>


<h2>
Booking Portal
</h2>


<a href="logout.php">
Logout
</a>


</body>

</html>