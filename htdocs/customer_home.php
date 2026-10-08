<?php

include "config.php";

require_role("customer");




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

<?php echo e($_SESSION["name"]); ?>

</h1>


<p>

Browse rooms at Bongabong View Hotel. To check availability and book your stay, contact our front desk at <a href="tel:09228125061">0922 812 5061</a>.

</p>



</section>



<section class="rooms-section">


<h2>

Our Rooms

</h2>



<div class="rooms-grid">



<?php while($room=mysqli_fetch_assoc($rooms)){ ?>


<div class="room-card">


<img

src="<?php echo e(room_image($room)); ?>"

class="room-image"



>


<h3>

<?php echo e($room["room_name"]); ?>

</h3>


<p>

Room Type:

<?php echo e($room["room_type"]); ?>

</p>



<p>

Price:

<?php echo money($room["room_rate"]); ?>

</p>



<p>
To book this room, call <a href="tel:09228125061">0922 812 5061</a>
or visit the front desk at JP Rizal St. Bgy. Poblacion, Bongabong.
</p>



</div>


<?php } ?>


</div>


</section>



</body>

</html>
