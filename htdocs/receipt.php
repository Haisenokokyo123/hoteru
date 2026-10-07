<?php
include "config.php";
require_login();

$conn = db_connect();

$id = isset($_GET["id"]) ? (int)$_GET["id"] : 0;

$result = mysqli_query($conn, "
    SELECT reservations.*, rooms.room_name, rooms.room_rate, rooms.room_type
    FROM reservations
    LEFT JOIN rooms ON reservations.room_id = rooms.id
    WHERE reservations.id = '$id'
");

$row = $result ? mysqli_fetch_assoc($result) : null;
?>

<!DOCTYPE html>
<html>
<head>
    <title>Receipt - Bongabong View Hotel</title>
    <link rel="stylesheet" href="style.css?v=1002">
</head>
<body>

<div class="receipt-page">

    <?php if ($row == null) { ?>

        <div class="receipt">
            <h2>Receipt Not Found</h2>
            <p class="center">The transaction could not be found.</p>
        </div>

        <div class="receipt-actions no-print">
            <a href="transactions.php">Back to Transactions</a>
            <a href="index.php">Back to Dashboard</a>
        </div>

    <?php } else { ?>

        <?php
            $nights = (int)$row["hours"];
            $room_total = (float)$row["room_rate"] * $nights;
        ?>

        <div class="receipt">
            <img src="puno.png" class="receipt-logo">

            <h2>BONGABONG VIEW HOTEL</h2>
            <p class="center">JP Rizal St. Bgy. Poblacion</p>
            <p class="center">Bongabong, Philippines</p>
            <p class="center">0922 812 5061</p>

            <hr>

            <p><b>Receipt No:</b> <?php echo (int)$row["id"]; ?></p>
            <p><b>Date:</b> <?php echo e($row["date_created"]); ?></p>
            <p><b>Status:</b> <?php echo e(reservation_status($row)); ?></p>

            <hr>

            <p><b>Guest Name:</b> <?php echo e($row["full_name"]); ?></p>
            <p><b>Contact Number:</b> <?php echo e($row["contact_number"]); ?></p>
            <p><b>Address:</b> <?php echo e($row["address"]); ?></p>

            <hr>

            <p><b>Room:</b> <?php echo e($row["room_name"]); ?></p>
            <p><b>Room Type:</b> <?php echo e($row["room_type"]); ?></p>
            <p><b>Rate:</b> <?php echo money($row["room_rate"]); ?> / night</p>
            <p><b>Number of Nights:</b> <?php echo $nights; ?></p>
            <p><b>Check In:</b> <?php echo e($row["check_in"]); ?></p>
            <p><b>Check Out:</b> <?php echo e($row["check_out"]); ?></p>

            <hr>

            <p><b>Room Total:</b> <?php echo money($room_total); ?></p>

            <hr>

            <h3>Total Amount: <?php echo money($row["total_amount"]); ?></h3>
            <p><b>Payment Method:</b> <?php echo e($row["payment_method"]); ?></p>

            <hr>

            <p class="center">Thank you for staying with us!</p>
        </div>

        <div class="receipt-actions no-print">
            <button onclick="window.print()">Print Receipt</button>
            <a href="transactions.php">Back to Transactions</a>
            <a href="index.php">Back to Dashboard</a>
        </div>

    <?php } ?>

</div>

</body>
</html>