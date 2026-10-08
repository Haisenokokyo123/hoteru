<?php

include "config.php";
require_staff();

$conn = db_connect();

$selected_room_id = isset($_GET["room_id"]) ? (int)$_GET["room_id"] : 0;
$bookingError = $_SESSION["booking_error"] ?? "";
$bookingOld = $_SESSION["booking_old"] ?? [];
unset($_SESSION["booking_error"], $_SESSION["booking_old"]);

$availableRooms = [];
$occupiedRooms = [];
$allRooms = [];
$totalRooms = 0;
$selectedRoom = null;
$selectedReservation = null;
$selectedRoomImage = "";
$selectedRoomLabel = "";

$roomGroups = [];

function room_group_key($room_type) {
    $type = strtolower($room_type);

    if (strpos($type, "king") !== false) {
        return "king";
    }

    if (
        strpos($type, "2 single") !== false ||
        strpos($type, "two single") !== false ||
        strpos($type, "single bed") !== false
    ) {
        return "two_single";
    }

    if (strpos($type, "family") !== false) {
        return "family";
    }

    if (strpos($type, "fan") !== false) {
        return "fan";
    }

    return preg_replace("/[^a-z0-9]+/", "_", $type);
}

function room_group_label($key, $room_type = "") {
    if ($key === "king") {
        return "King Size Bed";
    }

    if ($key === "two_single") {
        return "2 Single Bed";
    }

    if ($key === "family") {
        return "Family Room";
    }

    if ($key === "fan") {
        return "Fan Room";
    }

    return $room_type !== "" ? $room_type : "Room";
}

$roomsQuery = mysqli_query($conn, "SELECT * FROM rooms WHERE is_active = 1 ORDER BY id ASC");

while ($room = mysqli_fetch_assoc($roomsQuery)) {
    $totalRooms++;

    $room_id = (int)$room["id"];
    $activeReservation = get_active_reservation($conn, $room_id);
    $isOccupied = $activeReservation !== null;
    $allRooms[] = ["room" => $room, "reservation" => $activeReservation];

    if ($isOccupied) {
        $occupiedRooms[] = $room;
    } else {
        $availableRooms[] = $room;
    }

    if ($selected_room_id === $room_id) {
        $selectedRoom = $room;
        $selectedReservation = $activeReservation;
        $selectedRoomImage = room_image($room);
        $selectedRoomLabel = room_group_label(room_group_key($room["room_type"]), $room["room_type"]);
    }

    $groupKey = room_group_key($room["room_type"]);
    $groupLabel = room_group_label($groupKey, $room["room_type"]);

    if (!isset($roomGroups[$groupKey])) {
        $roomGroups[$groupKey] = [
            "key" => $groupKey,
            "label" => $groupLabel,
            "room_type" => $room["room_type"],
            "room_rate" => $room["room_rate"],
            "image" => room_image($room),
            "total_count" => 0,
            "available_count" => 0,
            "occupied_count" => 0,
            "first_available_room_id" => 0,
            "first_room_id" => $room_id,
            "occupied_guests" => []
        ];
    }

    $roomGroups[$groupKey]["total_count"]++;

    if ($isOccupied) {
        $roomGroups[$groupKey]["occupied_count"]++;
        $roomGroups[$groupKey]["occupied_guests"][] = $activeReservation["full_name"];
    } else {
        $roomGroups[$groupKey]["available_count"]++;

        if ($roomGroups[$groupKey]["first_available_room_id"] == 0) {
            $roomGroups[$groupKey]["first_available_room_id"] = $room_id;
        }
    }
}

$displayGroups = array_values($roomGroups);
$occupancyPercent = $totalRooms ? (int) round(count($occupiedRooms) / $totalRooms * 100) : 0;
$availablePercent = $totalRooms ? count($availableRooms) / $totalRooms * 100 : 0;
$staffHeroPhoto = hotel_photo('pic2') ?: 'hoteru.png';

$todayRevenueQuery = mysqli_query($conn, "
    SELECT SUM(total_amount) AS total FROM reservations
    WHERE DATE(date_created) = CURDATE()
");

$todayRevenueRow = mysqli_fetch_assoc($todayRevenueQuery);
$todayRevenue = $todayRevenueRow["total"] ? $todayRevenueRow["total"] : 0;

$todayCheckins = mysqli_query($conn, "
    SELECT reservations.*, rooms.room_name
    FROM reservations
    INNER JOIN rooms ON reservations.room_id = rooms.id
    WHERE DATE(reservations.date_created) = CURDATE()
    AND reservations.is_archived = 0
    ORDER BY reservations.id DESC
    LIMIT 5
");

$recentTransactions = mysqli_query($conn, "
    SELECT reservations.*, rooms.room_name
    FROM reservations
    INNER JOIN rooms ON reservations.room_id = rooms.id
    WHERE reservations.is_archived = 0
    ORDER BY reservations.id DESC
    LIMIT 5
");
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Bongabong View Hotel System</title>
    <link rel="stylesheet" href="style.css?v=20261008">
    <link rel="stylesheet" href="staff.css?v=<?php echo filemtime(__DIR__ . '/staff.css'); ?>">
    <script src="staff.js?v=<?php echo filemtime(__DIR__ . '/staff.js'); ?>" defer></script>
</head>
<body class="staff-page">

<?php render_navbar(); ?>

<main class="frontdesk-main" id="main-content">
<div class="context-row">
    <span>Hotel operations <span aria-hidden="true">&nbsp; / &nbsp;</span> Front desk</span>
    <span><?php echo ui_icon('clock'); ?><?php echo e(date('l, d F Y')); ?></span>
</div>
<section class="frontdesk-hero staff-hero" aria-labelledby="dashboard-title">
    <div class="staff-hero-scene" aria-hidden="true">
        <img class="staff-hero-image" src="<?php echo e($staffHeroPhoto); ?>" alt="" fetchpriority="high">
    </div>
    <div class="staff-hero-layout">
    <div class="hero-copy" data-staff-reveal>
        <p class="eyebrow">WELCOME BACK, <?php echo e($_SESSION['name'] ?? 'OUR TEAM'); ?></p>
        <h1 id="dashboard-title">A thoughtful welcome.<br><em>Every single stay.</em></h1>
        <p class="hero-description">A little care makes all the difference. Make room for your next arrival, and take care of every detail.</p>
        <div class="hero-buttons">
            <a href="#rooms" class="primary-btn"><?php echo ui_icon('plus'); ?>Create a booking</a>
            <a href="transactions.php" class="text-link">View transactions <?php echo ui_icon('arrow'); ?></a>
        </div>
    </div>
    <aside class="staff-hero-aside" aria-label="Current room availability" data-staff-reveal>
        <div class="staff-availability-panel">
            <p class="staff-panel-kicker">The hotel, at a glance</p>
            <div class="staff-availability-main">
                <div class="staff-occupancy-ring">
                    <svg viewBox="0 0 120 120" aria-hidden="true">
                        <circle class="staff-ring-track" cx="60" cy="60" r="50" fill="none" stroke-width="4" />
                        <circle class="staff-ring-progress" cx="60" cy="60" r="50" fill="none" stroke-width="4" pathLength="100" stroke-dasharray="100" style="stroke-dashoffset: <?php echo 100 - $occupancyPercent; ?>" />
                    </svg>
                    <div class="staff-ring-value"><strong><?php echo $occupancyPercent; ?>%</strong><small>occupied</small></div>
                </div>
                <div class="staff-availability-copy">
                    <strong><?php echo count($availableRooms); ?></strong>
                    <span><?php echo count($availableRooms) === 1 ? 'room available' : 'rooms available'; ?></span>
                </div>
            </div>
            <div class="staff-availability-track" aria-hidden="true"><span style="width: <?php echo $availablePercent; ?>%"></span></div>
            <div class="staff-availability-footer"><span><?php echo count($occupiedRooms); ?> occupied</span><span><?php echo $totalRooms; ?> rooms in total</span></div>
        </div>
    </aside>
    </div>
    <div class="staff-hero-bottom">
        <span><?php echo ui_icon('location'); ?>Bongabong, Oriental Mindoro</span>
        <span class="staff-hero-signature">Hospitality, thoughtfully managed.</span>
    </div>
</section>

<?php if (isset($_GET["checkout"]) && $_GET["checkout"] == "success") { ?>
    <div class="page-alert success-box">Guest checked out successfully. The room is now available.</div>
<?php } ?>

<?php if (isset($_GET["clear"]) && $_GET["clear"] == "success") { ?>
    <div class="page-alert success-box">Completed transactions were archived successfully.</div>
<?php } ?>

<?php if ($bookingError !== "") { ?>
    <div class="page-alert error-box" role="alert"><?php echo e($bookingError); ?></div>
<?php } ?>

<?php if ($selected_room_id > 0 && $selectedRoom === null) { ?>
    <div class="page-alert error-box" role="alert">This room is unavailable. Select another room below.</div>
<?php } ?>

<section class="dashboard" aria-label="Today at a glance">
    <div class="dash-card" data-staff-reveal>
        <div class="stat-topline">Total rooms <span class="staff-stat-icon"><?php echo ui_icon('grid'); ?></span></div>
        <h2><?php echo $totalRooms; ?></h2>
        <span class="stat-caption">Active in your property</span>
    </div>

    <div class="dash-card" data-staff-reveal>
        <div class="stat-topline">Available rooms <span class="staff-stat-icon"><?php echo ui_icon('key'); ?></span></div>
        <h2><?php echo count($availableRooms); ?></h2>
        <span class="stat-caption positive">Ready for a warm welcome</span>
    </div>

    <div class="dash-card" data-staff-reveal>
        <div class="stat-topline">Occupied rooms <span class="staff-stat-icon"><?php echo ui_icon('bed'); ?></span></div>
        <h2><?php echo count($occupiedRooms); ?></h2>
        <span class="stat-caption"><?php echo $occupancyPercent; ?>% of active rooms</span>
    </div>

    <div class="dash-card" data-staff-reveal>
        <div class="stat-topline">Today's revenue <span class="staff-stat-icon"><?php echo ui_icon('chart'); ?></span></div>
        <h2><?php echo money($todayRevenue); ?></h2>
        <span class="stat-caption">Recorded bookings today</span>
    </div>
</section>

<section class="rooms-section" id="rooms">
    <div class="section-header-row" data-staff-reveal>
        <div><p class="eyebrow">ROOMS &amp; AVAILABILITY</p><h2 class="section-title">Find the right stay.</h2><p class="section-description">Choose a room category to welcome your next guest.</p></div>
        <a href="rooms.php" class="small-action-link">Manage rooms <?php echo ui_icon('arrow'); ?></a>
    </div>

    <div class="rooms-grid">
        <?php if (count($displayGroups) > 0) { ?>
            <?php foreach ($displayGroups as $group) { ?>
                <?php
                    $hasAvailable = $group["available_count"] > 0;
                    $targetRoomId = $hasAvailable ? $group["first_available_room_id"] : $group["first_room_id"];
                    $selectedClass = "";

                    if ($selectedRoom != null && room_group_key($selectedRoom["room_type"]) === $group["key"]) {
                        $selectedClass = "selected-room";
                    }

                    $cardClass = $hasAvailable ? "room-card" : "room-card disabled-room-card";
                ?>

                <a href="index.php?room_id=<?php echo (int)$targetRoomId; ?>#booking" class="<?php echo $cardClass . ' ' . $selectedClass; ?>" data-staff-reveal>
                    <?php render_room_visual($group); ?>

                    <div class="room-info">
                        <h3><?php echo e($group["label"]); ?></h3>

                        <p class="room-rate"><strong><?php echo money($group["room_rate"]); ?></strong> / night</p>

                        <div class="room-statusline">
                        <span class="type-pill">
                            <?php echo (int)$group["total_count"]; ?> total rooms
                        </span>

                        <?php if ($group["available_count"] > 0) { ?>
                            <span class="status-pill status-available">
                                <?php echo (int)$group["available_count"]; ?> available
                            </span>
                        <?php } else { ?>
                            <span class="status-pill status-occupied">
                                Fully Occupied
                            </span>
                        <?php } ?>
                        </div>
                        <div class="room-card-footer"><span><?php echo $hasAvailable ? 'Select this room' : 'View guest details'; ?></span><?php echo ui_icon('arrow'); ?></div>
                    </div>
                </a>
            <?php } ?>
        <?php } else { ?>
            <div class="empty-card">No room types found.</div>
        <?php } ?>
    </div>
</section>

<section class="room-picker" aria-label="Choose a specific room" data-staff-reveal>
        <div class="room-picker-label"><?php echo ui_icon('key'); ?><div><strong>Looking for a specific room?</strong><small>Book a stay or manage a current guest.</small></div></div>
        <form action="index.php#booking" method="GET">
            <label for="selected_room" class="sr-only">Room</label>
            <select name="room_id" id="selected_room" required>
                <option value="">Select an available or occupied room</option>
                <?php foreach ($allRooms as $entry) { ?>
                    <?php $room = $entry["room"]; ?>
                    <option value="<?php echo (int)$room["id"]; ?>" <?php if ($selected_room_id === (int)$room["id"]) echo "selected"; ?>>
                        <?php echo e($room["room_name"] . " — " . ($entry["reservation"] === null ? "Available" : "Occupied")); ?>
                    </option>
                <?php } ?>
            </select>
            <button type="submit">Open room <?php echo ui_icon('arrow'); ?></button>
        </form>
</section>

<section class="content-section">

    <div class="side-panel">
        <div class="panel-box" data-staff-reveal>
            <div class="panel-header">
                <div><p class="eyebrow">ARRIVALS</p><h2>Today's check-ins</h2></div>
                <a href="transactions.php" class="small-action-link">View all</a>
            </div>

            <div class="table-scroll" role="region" aria-label="Today's check-ins" tabindex="0"><table>
                <tr>
                    <th scope="col">Guest</th>
                    <th scope="col">Room</th>
                    <th scope="col">Payment</th>
                </tr>

                <?php if ($todayCheckins && mysqli_num_rows($todayCheckins) > 0) { ?>
                    <?php while ($row = mysqli_fetch_assoc($todayCheckins)) { ?>
                        <tr>
                            <td><?php echo e($row["full_name"]); ?></td>
                            <td><?php echo e($row["room_name"]); ?></td>
                            <td><?php echo e($row["payment_method"]); ?></td>
                        </tr>
                    <?php } ?>
                <?php } else { ?>
                    <tr>
                        <td colspan="3" class="empty-row">No check-ins today.</td>
                    </tr>
                <?php } ?>
            </table></div>
        </div>

        <div class="panel-box" id="transactions" data-staff-reveal>
            <div class="panel-header">
                <div><p class="eyebrow">THE LATEST</p><h2>Recent transactions</h2></div>

                <form action="clear_transactions.php" method="POST" class="clear-form" onsubmit="return confirm('Archive all completed transactions? Active occupied rooms will not be affected.');">
                    <input type="hidden" name="csrf_token" value="<?php echo e(csrf_token()); ?>">
                    <input type="hidden" name="redirect" value="index.php?clear=success#transactions">
                    <button type="submit" class="danger-btn">Archive Completed</button>
                </form>
            </div>

            <div class="table-scroll" role="region" aria-label="Recent transactions" tabindex="0"><table>
                <tr>
                    <th scope="col">Guest</th>
                    <th scope="col">Total</th>
                    <th scope="col">Date</th>
                    <th scope="col">Receipt</th>
                </tr>

                <?php if ($recentTransactions && mysqli_num_rows($recentTransactions) > 0) { ?>
                    <?php while ($row = mysqli_fetch_assoc($recentTransactions)) { ?>
                        <tr>
                            <td><?php echo e($row["full_name"]); ?></td>
                            <td><?php echo money($row["total_amount"]); ?></td>
                            <td><?php echo e($row["date_created"]); ?></td>
                            <td><a href="receipt.php?id=<?php echo (int)$row["id"]; ?>">View</a></td>
                        </tr>
                    <?php } ?>
                <?php } else { ?>
                    <tr>
                        <td colspan="4" class="empty-row">No recent transactions found.</td>
                    </tr>
                <?php } ?>
            </table></div>

            <a href="transactions.php" class="receipt-link">Open Transaction History</a>
        </div>
    </div>

    <div class="booking-box" id="booking" data-staff-reveal>
        <?php if ($selectedRoom == null) { ?>

            <div class="empty-booking">
                <div class="empty-booking-icon"><?php echo ui_icon('bed'); ?></div>
                <p class="empty-booking-tag">YOUR NEXT WELCOME</p>
                <h2>A stay starts here.</h2>
                <p>
                    Select an available room to add guest details,
                    review the total, and create a booking.
                </p>
                <a href="#rooms" class="primary-btn empty-booking-btn">Explore available rooms <?php echo ui_icon('arrow'); ?></a>
            </div>

        <?php } elseif ($selectedReservation != null) { ?>

            <?php render_room_visual($selectedRoom); ?>
            <div class="booking-heading"><div><p class="eyebrow">IN HOUSE</p><h2><?php echo e($selectedRoom["room_name"]); ?></h2></div><span class="status-pill status-occupied">Occupied</span></div>

            <div class="occupied-details">
                <p><b>Status:</b> Occupied</p>
                <p><b>Room:</b> <?php echo e($selectedRoom["room_name"]); ?></p>
                <p><b>Guest:</b> <?php echo e($selectedReservation["full_name"]); ?></p>
                <p><b>Contact:</b> <?php echo e($selectedReservation["contact_number"]); ?></p>
                <p><b>Address:</b> <?php echo e($selectedReservation["address"]); ?></p>
                <p><b>Check In:</b> <?php echo e($selectedReservation["check_in"]); ?></p>
                <p><b>Check Out:</b> <?php echo e($selectedReservation["check_out"]); ?></p>
                <p><b>Total Paid:</b> <?php echo money($selectedReservation["total_amount"]); ?></p>
                <p><b>Payment:</b> <?php echo e($selectedReservation["payment_method"]); ?></p>
            </div>

            <div class="button-row">
                <a class="receipt-link" href="receipt.php?id=<?php echo (int)$selectedReservation["id"]; ?>">View Receipt</a>

                <form action="checkout_guest.php" method="POST" onsubmit="return confirm('Check out this guest now? This will make the room available immediately.');">
                    <input type="hidden" name="csrf_token" value="<?php echo e(csrf_token()); ?>">
                    <input type="hidden" name="reservation_id" value="<?php echo (int)$selectedReservation["id"]; ?>">
                    <button type="submit" class="checkout-btn">Check Out Guest</button>
                </form>
            </div>

        <?php } else { ?>

            <?php render_room_visual($selectedRoom); ?>
            <div class="booking-heading"><div><p class="eyebrow">NEW RESERVATION</p><h2><?php echo e($selectedRoom["room_name"]); ?></h2></div><span class="status-pill status-available">Available</span></div>

            <form action="save_reservation.php" method="POST">

                <input type="hidden" name="csrf_token" value="<?php echo e(csrf_token()); ?>">
                <input type="hidden" name="room_id" id="room_id" value="<?php echo (int)$selectedRoom["id"]; ?>">
                <input type="hidden" id="room_rate" value="<?php echo e($selectedRoom["room_rate"]); ?>">

                <h3><span class="step-number">01</span> Guest information</h3>

                <div class="form-row">
                    <div>
                        <label for="full_name">Full name</label>
                        <input type="text" name="full_name" id="full_name" autocomplete="name" placeholder="Guest's full name" maxlength="100" value="<?php echo e($bookingOld["full_name"] ?? ""); ?>" required>
                    </div>

                    <div>
                        <label for="contact_number">Contact number</label>
                        <input type="tel" name="contact_number" id="contact_number" autocomplete="tel" placeholder="09XX XXX XXXX" maxlength="50" value="<?php echo e($bookingOld["contact_number"] ?? ""); ?>" required>
                    </div>
                </div>

                <label for="address">Address</label>
                <textarea name="address" id="address" autocomplete="street-address" placeholder="Street, city or municipality, province" required><?php echo e($bookingOld["address"] ?? ""); ?></textarea>

                <h3><span class="step-number">02</span> Your guest's stay</h3>

                <div class="selected-room-box">
                    <p><b>Selected Room Type:</b> <?php echo e($selectedRoomLabel); ?></p>
                    <p><b>Assigned Room:</b> <?php echo e($selectedRoom["room_name"]); ?></p>
                    <p><b>Rate:</b> <?php echo money($selectedRoom["room_rate"]); ?> / night</p>
                </div>

                <div class="form-row stay-details"><div>
                <label for="hours">Number of nights</label>
                <input type="number" name="hours" id="hours" min="1" max="365" step="1" value="<?php echo e($bookingOld["hours"] ?? "1"); ?>" required oninput="computeTotal()">
                </div><div>
                <label for="payment_method">Payment method</label>
                <select name="payment_method" id="payment_method" required>
                    <option value="Cash" <?php if (($bookingOld["payment_method"] ?? "Cash") === "Cash") echo "selected"; ?>>Cash</option>
                    <option value="GCash" <?php if (($bookingOld["payment_method"] ?? "") === "GCash") echo "selected"; ?>>GCash</option>
                </select>
                </div></div>

                <div class="billing-preview">
                    <h3>STAY SUMMARY</h3>
                    <p><span>Rate per night</span><span>₱<span id="preview_rate"><?php echo number_format($selectedRoom["room_rate"], 2); ?></span></span></p>
                    <p><span>Room charge</span><span>₱<span id="preview_room_charge">0.00</span></span></p>
                    <h2 class="billing-total"><span class="total-label">Total amount</span><span>₱<span id="preview_total">0.00</span></span></h2>
                </div>

                <button type="submit">Save and Generate Receipt <?php echo ui_icon('arrow'); ?></button>
                <small class="form-note center">The room will be marked occupied once the booking is saved.</small>

            </form>

        <?php } ?>
    </div>

</section>

</main>
<?php render_footer(); ?>

<script>
function computeTotal() {
    var rateInput = document.getElementById("room_rate");

    if (!rateInput) {
        return;
    }

    var rate = parseFloat(rateInput.value) || 0;
    var nights = parseInt(document.getElementById("hours").value, 10) || 0;
    nights = Math.max(0, nights);

    var roomCharge = rate * nights;
    var total = roomCharge;

    var amountFormat = new Intl.NumberFormat("en-PH", { minimumFractionDigits: 2, maximumFractionDigits: 2 });
    document.getElementById("preview_room_charge").textContent = amountFormat.format(roomCharge);
    document.getElementById("preview_total").textContent = amountFormat.format(total);
}
computeTotal();
</script>

</body>
</html>
