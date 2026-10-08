<?php
include "config.php";
require_staff();

$conn = db_connect();

function report_row($conn, $where) {
    $query = mysqli_query($conn, "
        SELECT COUNT(*) AS bookings, SUM(total_amount) AS sales
        FROM reservations
        WHERE $where
    ");

    $row = mysqli_fetch_assoc($query);

    return [
        "bookings" => $row["bookings"] ? $row["bookings"] : 0,
        "sales" => $row["sales"] ? $row["sales"] : 0
    ];
}

$today = report_row($conn, "DATE(date_created) = CURDATE()");
$thisWeek = report_row($conn, "YEARWEEK(date_created, 1) = YEARWEEK(CURDATE(), 1)");
$thisMonth = report_row($conn, "YEAR(date_created) = YEAR(CURDATE()) AND MONTH(date_created) = MONTH(CURDATE())");

$cashToday = report_row($conn, "DATE(date_created) = CURDATE() AND payment_method = 'Cash'");
$gcashToday = report_row($conn, "DATE(date_created) = CURDATE() AND payment_method = 'GCash'");
$cashMonth = report_row($conn, "YEAR(date_created) = YEAR(CURDATE()) AND MONTH(date_created) = MONTH(CURDATE()) AND payment_method = 'Cash'");
$gcashMonth = report_row($conn, "YEAR(date_created) = YEAR(CURDATE()) AND MONTH(date_created) = MONTH(CURDATE()) AND payment_method = 'GCash'");

$activeRoomsQuery = mysqli_query($conn, "SELECT COUNT(*) AS total FROM rooms WHERE is_active = 1");
$activeRooms = mysqli_fetch_assoc($activeRoomsQuery)["total"];

$occupiedRoomsQuery = mysqli_query($conn, "
    SELECT COUNT(DISTINCT reservations.room_id) AS total
    FROM reservations
    INNER JOIN rooms ON reservations.room_id = rooms.id
    WHERE reservations.is_archived = 0
    AND rooms.is_active = 1
    AND reservations.check_in <= NOW()
    AND reservations.check_out > NOW()
");
$occupiedRooms = mysqli_fetch_assoc($occupiedRoomsQuery)["total"];
$availableRooms = $activeRooms - $occupiedRooms;

$dailySales = mysqli_query($conn, "
    SELECT DATE(date_created) AS report_date, COUNT(*) AS bookings, SUM(total_amount) AS sales
    FROM reservations
    WHERE date_created >= DATE_SUB(CURDATE(), INTERVAL 6 DAY)
    GROUP BY DATE(date_created)
    ORDER BY report_date DESC
");

$roomTypeSales = mysqli_query($conn, "
    SELECT rooms.room_type, COUNT(reservations.id) AS bookings, SUM(reservations.total_amount) AS sales
    FROM reservations
    INNER JOIN rooms ON reservations.room_id = rooms.id
    WHERE YEAR(reservations.date_created) = YEAR(CURDATE())
    AND MONTH(reservations.date_created) = MONTH(CURDATE())
    GROUP BY rooms.room_type
    ORDER BY sales DESC
");
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Sales Reports - Bongabong View Hotel</title>
    <link rel="stylesheet" href="style.css?v=20261008">
</head>
<body>

<?php render_navbar(); ?>

<main class="page-wrapper" id="main-content">
    <div class="page-heading">
        <span class="eyebrow">A clearer perspective</span>
        <h1 class="page-title">Sales reports</h1>
        <p class="page-description">Follow booking performance, payment trends, and today's room availability.</p>
    </div>

    <section class="dashboard simple-dashboard" aria-label="Sales overview">
        <div class="dash-card">
            <p>Sales today</p>
            <h2><?php echo money($today["sales"]); ?></h2>
        </div>

        <div class="dash-card">
            <p>Sales this week</p>
            <h2><?php echo money($thisWeek["sales"]); ?></h2>
        </div>

        <div class="dash-card">
            <p>Sales this month</p>
            <h2><?php echo money($thisMonth["sales"]); ?></h2>
        </div>

        <div class="dash-card">
            <p>Bookings this month</p>
            <h2><?php echo (int)$thisMonth["bookings"]; ?></h2>
        </div>
    </section>

    <section class="dashboard simple-dashboard" aria-label="Today's payments and occupancy">
        <div class="dash-card">
            <p>Cash today</p>
            <h2><?php echo money($cashToday["sales"]); ?></h2>
        </div>

        <div class="dash-card">
            <p>GCash today</p>
            <h2><?php echo money($gcashToday["sales"]); ?></h2>
        </div>

        <div class="dash-card">
            <p>Available rooms now</p>
            <h2><?php echo (int)$availableRooms; ?></h2>
        </div>

        <div class="dash-card">
            <p>Occupied rooms now</p>
            <h2><?php echo (int)$occupiedRooms; ?></h2>
        </div>
    </section>

    <div class="management-grid">
        <div class="panel-box table-panel">
            <h2 id="payment-heading">Payment breakdown</h2>

            <div class="table-scroll" role="region" aria-labelledby="payment-heading" tabindex="0">
            <table>
                <thead>
                <tr>
                    <th scope="col">Period</th>
                    <th scope="col">Cash</th>
                    <th scope="col">GCash</th>
                </tr>
                </thead>
                <tbody>
                <tr>
                    <th scope="row">Today</th>
                    <td><?php echo money($cashToday["sales"]); ?></td>
                    <td><?php echo money($gcashToday["sales"]); ?></td>
                </tr>
                <tr>
                    <th scope="row">This month</th>
                    <td><?php echo money($cashMonth["sales"]); ?></td>
                    <td><?php echo money($gcashMonth["sales"]); ?></td>
                </tr>
                </tbody>
            </table>
            </div>
        </div>

        <div class="panel-box table-panel">
            <h2 id="room-sales-heading">Room performance</h2>
            <p class="panel-description">Bookings and sales by room type this month.</p>

            <div class="table-scroll" role="region" aria-labelledby="room-sales-heading" tabindex="0">
            <table>
                <thead>
                <tr>
                    <th scope="col">Room type</th>
                    <th scope="col">Bookings</th>
                    <th scope="col">Sales</th>
                </tr>
                </thead>
                <tbody>

                <?php if ($roomTypeSales && mysqli_num_rows($roomTypeSales) > 0) { ?>
                    <?php while ($row = mysqli_fetch_assoc($roomTypeSales)) { ?>
                        <tr>
                            <td><?php echo e($row["room_type"]); ?></td>
                            <td><?php echo (int)$row["bookings"]; ?></td>
                            <td><?php echo money($row["sales"]); ?></td>
                        </tr>
                    <?php } ?>
                <?php } else { ?>
                    <tr>
                        <td colspan="3" class="empty-row">No sales yet this month.</td>
                    </tr>
                <?php } ?>
                </tbody>
            </table>
            </div>
        </div>
    </div>

    <div class="panel-box table-panel">
        <h2 id="daily-sales-heading">The past seven days</h2>
        <p class="panel-description">Daily booking volume and sales, based on the date each booking was made.</p>

        <div class="table-scroll" role="region" aria-labelledby="daily-sales-heading" tabindex="0">
        <table>
            <thead>
            <tr>
                <th scope="col">Date</th>
                <th scope="col">Bookings</th>
                <th scope="col">Sales</th>
            </tr>
            </thead>
            <tbody>

            <?php if ($dailySales && mysqli_num_rows($dailySales) > 0) { ?>
                <?php while ($row = mysqli_fetch_assoc($dailySales)) { ?>
                    <tr>
                        <td><?php echo e($row["report_date"]); ?></td>
                        <td><?php echo (int)$row["bookings"]; ?></td>
                        <td><?php echo money($row["sales"]); ?></td>
                    </tr>
                <?php } ?>
            <?php } else { ?>
                <tr>
                    <td colspan="3" class="empty-row">No sales data yet.</td>
                </tr>
            <?php } ?>
            </tbody>
        </table>
        </div>
    </div>
</main>

<?php render_footer(); ?>

</body>
</html>
