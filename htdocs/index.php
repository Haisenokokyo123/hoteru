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
<html>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Bongabong View Hotel System</title>
    <link rel="stylesheet" href="style.css?v=1002">
</head>
<body>

<?php render_navbar(); ?>

<section class="hero">
    <div class="hero-overlay">
        <p class="small-title">FRONT DESK PANEL</p>
        <h1>Reservation Dashboard</h1>

        <div class="hero-buttons">
            <a href="#rooms" class="primary-btn">Select Room</a>
            <a href="#booking" class="secondary-btn">Booking Panel</a>
        </div>
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

<section class="dashboard">
    <div class="dash-card">
        <h2><?php echo $totalRooms; ?></h2>
        <p>Total Active Rooms</p>
    </div>

    <div class="dash-card">
        <h2><?php echo count($availableRooms); ?></h2>
        <p>Available Rooms</p>
    </div>

    <div class="dash-card">
        <h2><?php echo count($occupiedRooms); ?></h2>
        <p>Occupied Rooms</p>
    </div>

    <div class="dash-card">
        <h2><?php echo money($todayRevenue); ?></h2>
        <p>Revenue Today</p>
    </div>
</section>

<section class="rooms-section" id="rooms">
    <div class="section-header-row">
        <h2 class="section-title">Room Selection</h2>
        <a href="rooms.php" class="small-action-link">Manage Rooms</a>
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

                <a href="index.php?room_id=<?php echo (int)$targetRoomId; ?>#booking" class="<?php echo $cardClass . ' ' . $selectedClass; ?>">
                    <img src="<?php echo e($group["image"]); ?>" class="room-image">

                    <div class="room-info">
                        <h3><?php echo e($group["label"]); ?></h3>

                        <p class="room-rate"><?php echo money($group["room_rate"]); ?> / night</p>

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

                        <?php if ($group["occupied_count"] > 0) { ?>
                            <p class="room-description">
                                <b><?php echo (int)$group["occupied_count"]; ?></b> occupied
                            </p>
                        <?php } ?>

                        <?php if ($group["available_count"] > 0) { ?>
                            <p class="room-description">Click to book this room type</p>
                        <?php } else { ?>
                            <p class="room-description">View guest details or check out a guest</p>
                        <?php } ?>
                    </div>
                </a>
            <?php } ?>
        <?php } else { ?>
            <div class="empty-card">No room types found.</div>
        <?php } ?>
    </div>
</section>

<section class="rooms-section">
    <div class="panel-box">
        <h2>Choose a Specific Room</h2>
        <form action="index.php#booking" method="GET" class="filter-bar">
            <label for="selected_room">Room</label>
            <select name="room_id" id="selected_room" required>
                <option value="">Select an available or occupied room</option>
                <?php foreach ($allRooms as $entry) { ?>
                    <?php $room = $entry["room"]; ?>
                    <option value="<?php echo (int)$room["id"]; ?>" <?php if ($selected_room_id === (int)$room["id"]) echo "selected"; ?>>
                        <?php echo e($room["room_name"] . " — " . ($entry["reservation"] === null ? "Available" : "Occupied")); ?>
                    </option>
                <?php } ?>
            </select>
            <button type="submit">Open Room</button>
        </form>
    </div>
</section>

<section class="hallway-banner">
    <div class="hallway-content">
        <p class="small-title">BONGABONG VIEW HOTEL</p>
        <h2>Comfortable Stay, Simple Booking</h2>
        <p>Manage rooms, reservations, and guest transactions in one clean dashboard.</p>
    </div>
</section>

<section class="content-section">

    <div class="side-panel">
        <div class="panel-box">
            <div class="panel-header">
                <h2>Today’s Check-ins</h2>
                <a href="transactions.php" class="small-action-link">View All</a>
            </div>

            <table>
                <tr>
                    <th>Guest</th>
                    <th>Room</th>
                    <th>Payment</th>
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
            </table>
        </div>

        <div class="panel-box" id="transactions">
            <div class="panel-header">
                <h2>Recent Transactions</h2>

                <form action="clear_transactions.php" method="POST" class="clear-form" onsubmit="return confirm('Archive all completed transactions? Active occupied rooms will not be affected.');">
                    <input type="hidden" name="csrf_token" value="<?php echo e(csrf_token()); ?>">
                    <input type="hidden" name="redirect" value="index.php?clear=success#transactions">
                    <button type="submit" class="danger-btn">Archive Completed</button>
                </form>
            </div>

            <table>
                <tr>
                    <th>Guest</th>
                    <th>Total</th>
                    <th>Date</th>
                    <th>Receipt</th>
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
            </table>

            <a href="transactions.php" class="receipt-link">Open Transaction History</a>
        </div>
    </div>

    <div class="booking-box" id="booking">
        <?php if ($selectedRoom == null) { ?>

            <div class="empty-booking">
                <div class="empty-booking-icon">🛏️</div>
                <p class="empty-booking-tag">BOOKING PANEL</p>
                <h2>Select a Room Type</h2>
                <p>
                    Choose one of the room cards above to open the booking form,
                    view room details, and generate a billing preview.
                </p>
                <a href="#rooms" class="primary-btn empty-booking-btn">Browse Room Types</a>
            </div>

        <?php } elseif ($selectedReservation != null) { ?>

            <img src="<?php echo e($selectedRoomImage); ?>" class="selected-booking-image">

            <h2><?php echo e($selectedRoomLabel); ?> Guest Details</h2>

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

            <img src="<?php echo e($selectedRoomImage); ?>" class="selected-booking-image">

            <h2>New Booking - <?php echo e($selectedRoomLabel); ?></h2>

            <form action="save_reservation.php" method="POST">

                <input type="hidden" name="csrf_token" value="<?php echo e(csrf_token()); ?>">
                <input type="hidden" name="room_id" id="room_id" value="<?php echo (int)$selectedRoom["id"]; ?>">
                <input type="hidden" id="room_rate" value="<?php echo e($selectedRoom["room_rate"]); ?>">

                <h3>Guest Information</h3>

                <div class="form-row">
                    <div>
                        <label>Full Name</label>
                        <input type="text" name="full_name" maxlength="100" value="<?php echo e($bookingOld["full_name"] ?? ""); ?>" required>
                    </div>

                    <div>
                        <label>Contact Number</label>
                        <input type="text" name="contact_number" maxlength="50" value="<?php echo e($bookingOld["contact_number"] ?? ""); ?>" required>
                    </div>
                </div>

                <label>Address</label>
                <textarea name="address" required><?php echo e($bookingOld["address"] ?? ""); ?></textarea>

                <h3>Room Details</h3>

                <div class="selected-room-box">
                    <p><b>Selected Room Type:</b> <?php echo e($selectedRoomLabel); ?></p>
                    <p><b>Assigned Room:</b> <?php echo e($selectedRoom["room_name"]); ?></p>
                    <p><b>Rate:</b> <?php echo money($selectedRoom["room_rate"]); ?> / night</p>
                </div>

                <label>Number of Nights</label>
                <input type="number" name="hours" id="hours" min="1" max="365" step="1" value="<?php echo e($bookingOld["hours"] ?? "1"); ?>" required oninput="computeTotal()">

                <h3>Payment</h3>

                <label>Payment Method</label>
                <select name="payment_method" required>
                    <option value="Cash" <?php if (($bookingOld["payment_method"] ?? "Cash") === "Cash") echo "selected"; ?>>Cash</option>
                    <option value="GCash" <?php if (($bookingOld["payment_method"] ?? "") === "GCash") echo "selected"; ?>>GCash</option>
                </select>

                <div class="billing-preview">
                    <h3>Billing Preview</h3>
                    <p>Room Rate: ₱<span id="preview_rate"><?php echo number_format($selectedRoom["room_rate"], 2); ?></span> / night</p>
                    <p>Room Charge / Stay: ₱<span id="preview_room_charge">0.00</span></p>
                    <h2>Total: ₱<span id="preview_total">0.00</span></h2>
                </div>

                <button type="submit">Save and Generate Receipt</button>

            </form>

        <?php } ?>
    </div>

</section>

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

    document.getElementById("preview_room_charge").textContent = roomCharge.toFixed(2);
    document.getElementById("preview_total").textContent = total.toFixed(2);
}
computeTotal();
</script>

</body>
</html>
