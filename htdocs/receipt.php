<?php
include "config.php";
require_staff();

$conn = db_connect();

$id = isset($_GET["id"]) ? (int)$_GET["id"] : 0;

$result = mysqli_query($conn, "
    SELECT reservations.*, rooms.room_name, rooms.room_rate, rooms.room_type
    FROM reservations
    LEFT JOIN rooms ON reservations.room_id = rooms.id
    WHERE reservations.id = '$id'
");

$row = $result ? mysqli_fetch_assoc($result) : null;
if ($row === null) {
    http_response_code(404);
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Receipt - Bongabong View Hotel</title>
    <link rel="stylesheet" href="style.css?v=20261008">
</head>
<body>

<div class="no-print"><?php render_navbar(); ?></div>

<main class="receipt-page" id="main-content">

    <?php if ($row == null) { ?>

        <div class="receipt">
            <span class="eyebrow">Guest receipt</span>
            <h1>Receipt not found</h1>
            <p class="center">The transaction could not be found.</p>
        </div>

        <div class="receipt-actions no-print">
            <a href="transactions.php">Back to Transactions</a>
            <a href="index.php">Back to Dashboard</a>
        </div>

    <?php } else { ?>

        <?php
            $nights = (int)$row["hours"];
            // Receipts reflect the amount charged when booked, even if room rates change.
            $extra_bed = (float)($row["extra_bed"] ?? 0);
            $food = (float)($row["food"] ?? 0);
            $damages = (float)($row["damages"] ?? 0);
            $room_total = (float)$row["total_amount"] - $extra_bed - $food - $damages;
            $booked_rate = $nights > 0 ? $room_total / $nights : $room_total;
        ?>

        <article class="receipt">
            <header class="receipt-header">
                <img src="puno.png" class="receipt-logo" alt="Bongabong View Hotel">
                <div>
                    <span class="eyebrow">Bongabong View Hotel</span>
                    <h1>Your stay, in detail.</h1>
                    <p>JP Rizal St. Bgy. Poblacion<br>Bongabong, Philippines · 0922 812 5061</p>
                </div>
            </header>

            <div class="receipt-meta">
                <p><b>Receipt No:</b> <?php echo (int)$row["id"]; ?></p>
                <p><b>Date:</b> <?php echo e($row["date_created"]); ?></p>
                <p><b>Status:</b> <?php echo e(reservation_status($row)); ?></p>
            </div>

            <div class="receipt-detail-grid">
                <section class="receipt-section">
                    <h2>Guest details</h2>
                    <p><b>Guest Name:</b> <?php echo e($row["full_name"]); ?></p>
                    <p><b>Contact Number:</b> <?php echo e($row["contact_number"]); ?></p>
                    <p><b>Address:</b> <?php echo e($row["address"]); ?></p>
                </section>
                <section class="receipt-section">
                    <h2>Stay details</h2>
                    <p><b>Room:</b> <?php echo e($row["room_name"]); ?></p>
                    <p><b>Room Type:</b> <?php echo e($row["room_type"]); ?></p>
                    <p><b>Rate:</b> <?php echo money($booked_rate); ?> / night</p>
                    <p><b>Number of Nights:</b> <?php echo $nights; ?></p>
                    <p><b>Check In:</b> <?php echo e($row["check_in"]); ?></p>
                    <p><b>Check Out:</b> <?php echo e($row["check_out"]); ?></p>
                </section>
            </div>

            <section class="receipt-section receipt-charges">
            <h2>Payment summary</h2>
            <p class="receipt-line"><b>Room Total:</b> <span><?php echo money($room_total); ?></span></p>
            <?php if ($extra_bed != 0) { ?>
                <p class="receipt-line"><b>Extra Bed:</b> <span><?php echo money($extra_bed); ?></span></p>
            <?php } ?>
            <?php if ($food != 0) { ?>
                <p class="receipt-line"><b>Food:</b> <span><?php echo money($food); ?></span></p>
            <?php } ?>
            <?php if ($damages != 0) { ?>
                <p class="receipt-line"><b>Damages:</b> <span><?php echo money($damages); ?></span></p>
            <?php } ?>

            <h3 class="receipt-total">Total Amount: <?php echo money($row["total_amount"]); ?></h3>
            <p><b>Payment Method:</b> <?php echo e($row["payment_method"]); ?></p>
            </section>

            <p class="receipt-thanks">Thank you for making us part of your journey.</p>
        </article>

        <div class="receipt-actions no-print">
            <button type="button" onclick="window.print()">Print receipt <span aria-hidden="true">↗</span></button>
            <a href="transactions.php">Back to Transactions</a>
            <a href="index.php">Back to Dashboard</a>
        </div>

    <?php } ?>

</main>

</body>
</html>
