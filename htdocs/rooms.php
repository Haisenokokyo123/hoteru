<?php
include "config.php";
require_login();

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
?>

<!DOCTYPE html>
<html>
<head>
    <title>Room Management - Bongabong View Hotel</title>
    <link rel="stylesheet" href="style.css?v=500">
</head>
<body>

<?php render_navbar(); ?>

<div class="page-wrapper">
    <div class="section-header-row">
        <h1 class="page-title">Room Management</h1>
        <a href="index.php#rooms" class="small-action-link">Back to Room Selection</a>
    </div>

    <?php if ($msg !== "") { ?>
        <div class="success-box page-alert">
            <?php
                if ($msg === "added") echo "Room added successfully.";
                elseif ($msg === "updated") echo "Room updated successfully.";
                elseif ($msg === "disabled") echo "Room disabled successfully.";
                elseif ($msg === "enabled") echo "Room enabled successfully.";
                elseif ($msg === "occupied") echo "This room is currently occupied and cannot be disabled.";
                elseif ($msg === "invalid") echo "Please check the room details.";
                elseif ($msg === "image_error") echo "Image upload failed. Please use JPG, PNG, JPEG, or WEBP only.";
                else echo "Action completed.";
            ?>
        </div>
    <?php } ?>

    <div class="management-grid">
        <div class="panel-box">
            <h2>Add New Room</h2>

            <form action="save_room.php" method="POST" enctype="multipart/form-data">
                <input type="hidden" name="action" value="add">

                <label>Room Name</label>
                <input type="text" name="room_name" placeholder="Example: King Room 3" required>

                <label>Room Type</label>
                <select name="room_type" required>
                    <option value="King Size Bed">King Size Bed</option>
                    <option value="2 Single Bed">2 Single Bed</option>
                    <option value="Family Room">Family Room</option>
                    <option value="Fan Room">Fan Room</option>
                </select>

                <label>Room Rate Per Night</label>
                <input type="number" name="room_rate" min="1" step="0.01" required>

                <label>Room Image</label>
                <input type="file" name="image_file" accept="image/png, image/jpeg, image/jpg, image/webp">
                <small class="form-note">Upload JPG, PNG, or WEBP. If empty, default image will be used based on room type.</small>

                <button type="submit">Add Room</button>
            </form>
        </div>

        <div class="panel-box">
            <h2>Search and Filter</h2>

            <form method="GET" action="rooms.php" class="stacked-form">
                <label>Search</label>
                <input type="text" name="search" placeholder="Search room name or type" value="<?php echo e($search); ?>">

                <label>Filter</label>
                <select name="filter">
                    <option value="active" <?php if ($filter == "active") echo "selected"; ?>>Active Rooms</option>
                    <option value="all" <?php if ($filter == "all") echo "selected"; ?>>All Rooms</option>
                    <option value="disabled" <?php if ($filter == "disabled") echo "selected"; ?>>Disabled Rooms</option>
                    <option value="king" <?php if ($filter == "king") echo "selected"; ?>>King Size Bed</option>
                    <option value="two_single" <?php if ($filter == "two_single") echo "selected"; ?>>2 Single Bed</option>
                    <option value="family" <?php if ($filter == "family") echo "selected"; ?>>Family Room</option>
                    <option value="fan" <?php if ($filter == "fan") echo "selected"; ?>>Fan Room</option>
                </select>

                <button type="submit">Apply</button>
                <a href="rooms.php" class="reset-link">Reset Filter</a>
            </form>
        </div>
    </div>

    <h2 class="section-title room-list-title">Rooms List</h2>

    <div class="rooms-grid manage-room-grid">
        <?php if ($rooms && mysqli_num_rows($rooms) > 0) { ?>
            <?php while ($room = mysqli_fetch_assoc($rooms)) { ?>
                <?php
                    $activeReservation = get_active_reservation($conn, $room["id"]);
                    $isOccupied = $activeReservation !== null;
                    $isActive = (int)$room["is_active"] === 1;
                ?>

                <div class="room-card manage-room-card">
                    <img src="<?php echo e(room_image($room)); ?>" class="room-image large-room-image">

                    <div class="room-info">
                        <h3><?php echo e($room["room_name"]); ?></h3>
                        <p class="room-rate"><?php echo money($room["room_rate"]); ?> / night</p>
                        <span class="type-pill"><?php echo e($room["room_type"]); ?></span>

                        <?php if (!$isActive) { ?>
                            <span class="status-pill status-disabled">Disabled</span>
                        <?php } elseif ($isOccupied) { ?>
                            <span class="status-pill status-occupied">Occupied</span>
                            <p class="room-description"><b>Guest:</b> <?php echo e($activeReservation["full_name"]); ?></p>
                        <?php } else { ?>
                            <span class="status-pill status-available">Available</span>
                        <?php } ?>

                        <form action="save_room.php" method="POST" enctype="multipart/form-data" class="room-edit-form">
                            <input type="hidden" name="action" value="update">
                            <input type="hidden" name="room_id" value="<?php echo (int)$room["id"]; ?>">
                            <input type="hidden" name="current_image" value="<?php echo e($room["image"]); ?>">

                            <label>Room Name</label>
                            <input type="text" name="room_name" value="<?php echo e($room["room_name"]); ?>" required>

                            <label>Room Type</label>
                            <select name="room_type" required>
                                <option value="King Size Bed" <?php if ($room["room_type"] == "King Size Bed") echo "selected"; ?>>King Size Bed</option>
                                <option value="2 Single Bed" <?php if ($room["room_type"] == "2 Single Bed") echo "selected"; ?>>2 Single Bed</option>
                                <option value="Family Room" <?php if ($room["room_type"] == "Family Room") echo "selected"; ?>>Family Room</option>
                                <option value="Fan Room" <?php if ($room["room_type"] == "Fan Room") echo "selected"; ?>>Fan Room</option>
                            </select>

                            <label>Rate Per Night</label>
                            <input type="number" name="room_rate" min="1" step="0.01" value="<?php echo e($room["room_rate"]); ?>" required>

                            <label>Change Room Image</label>
                            <input type="file" name="image_file" accept="image/png, image/jpeg, image/jpg, image/webp">
                            <small class="form-note">Leave empty if you do not want to change the image.</small>

                            <button type="submit">Save Changes</button>
                        </form>

                        <?php if ($isActive) { ?>
                            <form action="save_room.php" method="POST" onsubmit="return confirm('Disable this room? It will be hidden from booking.');">
                                <input type="hidden" name="action" value="disable">
                                <input type="hidden" name="room_id" value="<?php echo (int)$room["id"]; ?>">
                                <button type="submit" class="danger-btn full-width-btn">Disable Room</button>
                            </form>
                        <?php } else { ?>
                            <form action="save_room.php" method="POST">
                                <input type="hidden" name="action" value="enable">
                                <input type="hidden" name="room_id" value="<?php echo (int)$room["id"]; ?>">
                                <button type="submit" class="checkout-btn full-width-btn">Enable Room</button>
                            </form>
                        <?php } ?>
                    </div>
                </div>
            <?php } ?>
        <?php } else { ?>
            <div class="empty-card">No rooms found.</div>
        <?php } ?>
    </div>
</div>

<?php render_footer(); ?>

</body>
</html>