<?php

session_start();

if (!isset($_SESSION["role"])) {

    header("Location: ../login.php");
    exit();

}


if ($_SESSION["role"] != "staff") {

    header("Location: ../customer/home.php");
    exit();

}

?>
<?php

session_start();

include "../config.php";


// CHECK ADMIN ACCESS

if(!isset($_SESSION["role"]) || $_SESSION["role"] != "admin"){

    header("Location: ../login.php");
    exit();

}


$conn = db_connect();



// Count users

$total_users = mysqli_fetch_assoc(
    mysqli_query(
        $conn,
        "SELECT COUNT(*) as total FROM users"
    )
)["total"];



// Count staff

$total_staff = mysqli_fetch_assoc(
    mysqli_query(
        $conn,
        "SELECT COUNT(*) as total FROM users WHERE role='staff'"
    )
)["total"];



// Count customers

$total_customers = mysqli_fetch_assoc(
    mysqli_query(
        $conn,
        "SELECT COUNT(*) as total FROM users WHERE role='customer'"
    )
)["total"];



?>


<!DOCTYPE html>

<html>

<head>

<title>
Admin Dashboard - Hoteru
</title>


<link rel="stylesheet" href="../style.css?v=1003">


</head>


<body>


<div class="navbar">


<div class="brand">

<img src="../puno.png" class="brand-logo">

<span>
BONGABONG VIEW HOTEL
</span>


</div>



<div class="nav-links">


<a href="dashboard.php">
Dashboard
</a>


<a href="create_staff.php">
Create Staff
</a>


<a href="manage_users.php">
Users
</a>


<a href="../logout.php">
Logout
</a>



</div>


</div>





<div class="container">



<h1>
Admin Dashboard
</h1>


<p>
Welcome,
<?php echo $_SESSION["name"]; ?>
</p>





<div class="dashboard-cards">



<div class="card">

<h2>
<?php echo $total_users; ?>
</h2>

<p>
Total Users
</p>

</div>





<div class="card">

<h2>
<?php echo $total_staff; ?>
</h2>

<p>
Staff Accounts
</p>

</div>





<div class="card">

<h2>
<?php echo $total_customers; ?>
</h2>

<p>
Customers
</p>

</div>



</div>





<h2>
Admin Functions
</h2>



<div class="admin-buttons">


<a href="create_staff.php">

<button>
Create Staff Account
</button>

</a>




<a href="manage_users.php">

<button>
Manage Users
</button>

</a>



</div>




</div>




</body>

</html>