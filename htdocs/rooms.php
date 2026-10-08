<?php
include "config.php";
require_staff();

$conn = db_connect();

$filter = isset($_GET["filter"]) ? $_GET["filter"] : "active";
$search = isset($_GET["search"]) ? trim($_GET["search"]) : "";

$where = [];

if ($filter === "active") {
    $where[] = "is_active = 1";
} elseif ($filter === "disabled") {
    $where[] = "is_active = 0";
} elseif ($filter === "king") {
    $where[] = "is_active = 1";
    $where[] = "LOWER(room_type) LIKE '%king%'";
} elseif ($filter === "two_single") {
    $where[] = "is_active = 1";
    $where[] = "(LOWER(room_type) LIKE '%2 single%' OR LOWER(room_type) LIKE '%two single%' OR LOWER(room_type) LIKE '%single bed%')";
} elseif ($filter === "family") {
    $where[] = "is_active = 1";
    $where[] = "LOWER(room_type) LIKE '%family%'";
} elseif ($filter === "fan") {
    $where[] = "is_active = 1";
    $where[] = "LOWER(room_type) LIKE '%fan%'";
}

if ($search !== "") {
    $safeSearch = mysqli_real_escape_string($conn, $search);
    $where[] = "(room_name LIKE '%$safeSearch%' OR room_type LIKE '%$safeSearch%')";
}

$whereSql = count($where) > 0 ? "WHERE " . implode(" AND ", $where) : "";
$rooms = mysqli_query($conn, "SELECT * FROM rooms $whereSql ORDER BY is_active DESC, id ASC");

$msg = isset($_GET["msg"]) ? $_GET["msg"] : "";
$isError = in_array($msg, ["occupied", "invalid", "image_error", "save_error"], true);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Room Management - Bongabong View Hotel</title>
    <link rel="stylesheet" href="style.css?v=20261008">
</head>
<body>

<?php render_navbar(); ?>

<main class="page-wrapper" id="main-content">
    <div class="section-header-row page-heading">
        <div>
            <span class="eyebrow">The room collection</span>
            <h1 class="page-title">Room management</h1>
            <p class="page-description">Keep every room ready for a memorable stay. Manage rates, details, and availability.</p>
        </div>
        <a href="index.php#rooms" class="small-action-link">View room availability <span aria-hidden="true">↗</span></a>
    </div>

    <?php if ($msg !== "") { ?>
        <div class="<?php echo $isError ? "error-box" : "success-box"; ?> page-alert" role="status">
            <?php
                if ($msg === "added") echo "Room added successfully.";
                elseif ($msg === "updated") echo "Room updated successfully.";
                elseif ($msg === "disabled") echo "Room disabled successfully.";
                elseif ($msg === "enabled") echo "Room enabled successfully.";
                elseif ($msg === "occupied") echo "This room has a current or upcoming booking and cannot be disabled.";
                elseif ($msg === "invalid") echo "Please check the room details.";
                elseif ($msg === "save_error") echo "The room could not be saved. Please try again.";
                elseif ($msg === "image_error") echo "Image upload failed. Choose a JPG, PNG, or WEBP image up to 5 MB.";
                else echo "Action completed.";
            ?>
        </div>
    <?php } ?>

    <div class="management-grid">
        <div class="panel-box">
            <span class="eyebrow">Expand your collection</span>
            <h2>Add a room</h2>

            <form action="save_room.php" method="POST" enctype="multipart/form-data">
                <input type="hidden" name="csrf_token" value="<?php echo e(csrf_token()); ?>">
                <input type="hidden" name="action" value="add">

                <label for="new-room-name">Room name</label>
                <input id="new-room-name" type="text" name="room_name" maxlength="50" placeholder="e.g. King Room 3" required>

                <label for="new-room-type">Room type</label>
                <select id="new-room-type" name="room_type" required>
                    <option value="King Size Bed">King Size Bed</option>
                    <option value="2 Single Bed">2 Single Bed</option>
                    <option value="Family Room">Family Room</option>
                    <option value="Fan Room">Fan Room</option>
                </select>

                <label for="new-room-rate">Nightly rate (₱)</label>
                <input id="new-room-rate" type="number" name="room_rate" min="0.01" max="99999999.99" step="0.01" placeholder="0.00" required>

                <label for="new-room-image">Room photograph</label>
                <input id="new-room-image" type="file" name="image_file" accept="image/png, image/jpeg, image/jpg, image/webp" aria-describedby="new-image-help">
                <small id="new-image-help" class="form-note">Optional · JPG, PNG, or WEBP, up to 5 MB.</small>

                <button type="submit">Add Room</button>
            </form>
        </div>

        <div class="panel-box">
            <span class="eyebrow">Find your room</span>
            <h2>Search the collection</h2>
            <p class="panel-description">Browse by room category or review rooms currently hidden from booking.</p>

            <form method="GET" action="rooms.php" class="stacked-form">
                <label for="room-search">Room name or type</label>
                <input id="room-search" type="search" name="search" placeholder="Search the collection" value="<?php echo e($search); ?>">

                <label for="room-filter">Show rooms</label>
                <select id="room-filter" name="filter">
                    <option value="active" <?php if ($filter == "active") echo "selected"; ?>>Active Rooms</option>
                    <option value="all" <?php if ($filter == "all") echo "selected"; ?>>All Rooms</option>
                    <option value="disabled" <?php if ($filter == "disabled") echo "selected"; ?>>Disabled Rooms</option>
                    <option value="king" <?php if ($filter == "king") echo "selected"; ?>>King Size Bed</option>
                    <option value="two_single" <?php if ($filter == "two_single") echo "selected"; ?>>2 Single Bed</option>
                    <option value="family" <?php if ($filter == "family") echo "selected"; ?>>Family Room</option>
                    <option value="fan" <?php if ($filter == "fan") echo "selected"; ?>>Fan Room</option>
                </select>

                <div class="filter-actions">
                    <button type="submit">Apply filters</button>
                    <a href="rooms.php" class="reset-link">Clear filters</a>
                </div>
            </form>
        </div>
    </div>

    <div class="section-header-row room-list-title">
        <h2 class="section-title">Your rooms</h2>
        <span class="section-count"><?php echo $rooms ? mysqli_num_rows($rooms) : 0; ?> rooms found</span>
    </div>

    <div class="rooms-grid manage-room-grid">
        <?php if ($rooms && mysqli_num_rows($rooms) > 0) { ?>
            <?php while ($room = mysqli_fetch_assoc($rooms)) { ?>
                <?php
                    $activeReservation = get_active_reservation($conn, $room["id"]);
                    $isOccupied = $activeReservation !== null;
                    $isActive = (int)$room["is_active"] === 1;
                ?>

                <div class="room-card manage-room-card">
                    <?php render_room_visual($room, 'large-room-image'); ?>

                    <div class="room-info">
                        <h3><?php echo e($room["room_name"]); ?></h3>
                        <p class="room-rate"><?php echo money($room["room_rate"]); ?> <small>/ night</small></p>
                        <span class="type-pill"><?php echo e($room["room_type"]); ?></span>

                        <?php if (!$isActive) { ?>
                            <span class="status-pill status-disabled">Disabled</span>
                        <?php } elseif ($isOccupied) { ?>
                            <span class="status-pill status-occupied">Occupied</span>
                            <p class="room-description"><b>Guest:</b> <?php echo e($activeReservation["full_name"]); ?></p>
                        <?php } else { ?>
                            <span class="status-pill status-available">Available</span>
                        <?php } ?>

                        <?php if ($isActive) { ?>
                            <p><a class="receipt-link" href="index.php?room_id=<?php echo (int)$room["id"]; ?>#booking"><?php echo $isOccupied ? "Guest Details / Check Out" : "Book This Room"; ?></a></p>
                        <?php } ?>

                        <details class="room-edit-details">
                        <summary>Edit room details</summary>
                        <form action="save_room.php" method="POST" enctype="multipart/form-data" class="room-edit-form">
                            <input type="hidden" name="csrf_token" value="<?php echo e(csrf_token()); ?>">
                            <input type="hidden" name="action" value="update">
                            <input type="hidden" name="room_id" value="<?php echo (int)$room["id"]; ?>">

                            <label for="room-name-<?php echo (int)$room["id"]; ?>">Room name</label>
                            <input id="room-name-<?php echo (int)$room["id"]; ?>" type="text" name="room_name" maxlength="50" value="<?php echo e($room["room_name"]); ?>" required>

                            <label for="room-type-<?php echo (int)$room["id"]; ?>">Room type</label>
                            <select id="room-type-<?php echo (int)$room["id"]; ?>" name="room_type" required>
                                <option value="King Size Bed" <?php if ($room["room_type"] == "King Size Bed") echo "selected"; ?>>King Size Bed</option>
                                <option value="2 Single Bed" <?php if ($room["room_type"] == "2 Single Bed") echo "selected"; ?>>2 Single Bed</option>
                                <option value="Family Room" <?php if ($room["room_type"] == "Family Room") echo "selected"; ?>>Family Room</option>
                                <option value="Fan Room" <?php if ($room["room_type"] == "Fan Room") echo "selected"; ?>>Fan Room</option>
                            </select>

                            <label for="room-rate-<?php echo (int)$room["id"]; ?>">Nightly rate (₱)</label>
                            <input id="room-rate-<?php echo (int)$room["id"]; ?>" type="number" name="room_rate" min="0.01" max="99999999.99" step="0.01" value="<?php echo e($room["room_rate"]); ?>" required>

                            <label for="room-image-<?php echo (int)$room["id"]; ?>">Replace photograph</label>
                            <input id="room-image-<?php echo (int)$room["id"]; ?>" type="file" name="image_file" accept="image/png, image/jpeg, image/jpg, image/webp" aria-describedby="image-help-<?php echo (int)$room["id"]; ?>">
                            <small id="image-help-<?php echo (int)$room["id"]; ?>" class="form-note">Leave empty to keep the current photograph.</small>

                            <button type="submit">Save Changes</button>
                        </form>
                        </details>

                        <?php if ($isActive) { ?>
                            <form action="save_room.php" method="POST" onsubmit="return confirm('Disable this room? It will be hidden from booking.');">
                                <input type="hidden" name="csrf_token" value="<?php echo e(csrf_token()); ?>">
                                <input type="hidden" name="action" value="disable">
                                <input type="hidden" name="room_id" value="<?php echo (int)$room["id"]; ?>">
                                <button type="submit" class="danger-btn full-width-btn" <?php if ($isOccupied) echo "disabled"; ?>><?php echo $isOccupied ? "Check Out Guest Before Disabling" : "Disable Room"; ?></button>
                            </form>
                        <?php } else { ?>
                            <form action="save_room.php" method="POST">
                                <input type="hidden" name="csrf_token" value="<?php echo e(csrf_token()); ?>">
                                <input type="hidden" name="action" value="enable">
                                <input type="hidden" name="room_id" value="<?php echo (int)$room["id"]; ?>">
                                <button type="submit" class="checkout-btn full-width-btn">Enable Room</button>
                            </form>
                        <?php } ?>
                    </div>
                </div>
            <?php } ?>
        <?php } else { ?>
            <div class="empty-card"><h3>No rooms found</h3><p>Try another room name or clear your filters to see more rooms.</p><a href="rooms.php" class="small-action-link">Clear filters</a></div>
        <?php } ?>
    </div>
</main>

<?php render_footer(); ?>

</body>
</html>
