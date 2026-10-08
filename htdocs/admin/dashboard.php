<?php
require_once dirname(__DIR__) . '/config.php';
start_app_session();
if (!isset($_SESSION['role'])) {
    header('Location: ../login.php');
    exit;
}
require_role('admin');

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


<a href="../index.php">
Manage Hotel
</a>


<a href="../rooms.php">
Rooms
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
<?php echo e($_SESSION["name"]); ?>
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


<a href="../index.php">

<button>
Manage Hotel
</button>

</a>




<a href="../rooms.php">

<button>
Manage Rooms
</button>

</a>



</div>




</div>




</body>

</html>
