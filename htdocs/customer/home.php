<?php

session_start();


if (!isset($_SESSION["role"])) {

    header("Location: ../login.php");
    exit();

}


if ($_SESSION["role"] != "customer") {

    header("Location: ../admin/dashboard.php");
    exit();

}

?>


<!DOCTYPE html>
<html>

<head>

<title>
Customer Home
</title>

</head>


<body>


<h1>
Welcome Customer
</h1>


<p>
You can view rooms and make reservations here.
</p>


<a href="../logout.php">
Logout
</a>


</body>

</html>