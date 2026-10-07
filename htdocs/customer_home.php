<?php

include "config.php";

require_login();


if($_SESSION["role"] != "customer"){

    header("Location: login.php");
    exit();

}


$conn = db_connect();



$rooms = mysqli_query($conn,

"SELECT * FROM rooms
WHERE is_active=1"

);



?>


<!DOCTYPE html>

<html>

<head>

<title>
Customer Booking Portal
</title>

<link rel="stylesheet" href="style.css">


</head>


<body>



<?php render_navbar(); ?>


<section class="dashboard">


<h1>

Welcome,

<?php echo $_SESSION["name"]; ?>

</h1>


<p>

Book your stay at Bongabong View Hotel

</p>



</section>



<section class="rooms-section">


<h2>

Available Rooms

</h2>



<div class="rooms-grid">



<?php while($room=mysqli_fetch_assoc($rooms)){ ?>


<div class="room-card">


<img 

src="<?php echo room_image($room); ?>"

class="room-image"



>


<h3>

<?php echo $room["room_name"]; ?>

</h3>


<p>

Room Type:

<?php echo $room["room_type"]; ?>

</p>



<p>

Price:

<?php echo money($room["room_rate"]); ?>

</p>



<a href="customer_booking.php?room_id=<?php echo $room["id"]; ?>">

<button>

Book Now

</button>


</a>



</div>


<?php } ?>


</div>


</section>



</body>

</html>