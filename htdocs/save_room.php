<?php
include "config.php";
require_login();

$conn = db_connect();

function default_room_image($room_type) {
    $type = strtolower($room_type);

    if (strpos($type, "king") !== false) {
        return "images/king.png";
    }

    if (
        strpos($type, "2 single") !== false ||
        strpos($type, "two single") !== false ||
        strpos($type, "single bed") !== false
    ) {
        return "images/2.png";
    }

    if (strpos($type, "family") !== false) {
        return "images/fam.png";
    }

    if (strpos($type, "fan") !== false) {
        return "images/fan.png";
    }

    return "images/king.png";
}

function upload_room_image($field_name, $room_type, $current_image = "") {
    if (!isset($_FILES[$field_name]) || $_FILES[$field_name]["error"] === UPLOAD_ERR_NO_FILE) {
        if ($current_image !== "") {
            return $current_image;
        }

        return default_room_image($room_type);
    }

    if ($_FILES[$field_name]["error"] !== UPLOAD_ERR_OK) {
        return false;
    }

    $allowedExtensions = ["jpg", "jpeg", "png", "webp"];
    $originalName = $_FILES[$field_name]["name"];
    $tmpName = $_FILES[$field_name]["tmp_name"];
    $extension = strtolower(pathinfo($originalName, PATHINFO_EXTENSION));

    if (!in_array($extension, $allowedExtensions)) {
        return false;
    }

    $uploadDir = "images/room_uploads/";

    if (!is_dir($uploadDir)) {
        mkdir($uploadDir, 0777, true);
    }

    $safeFileName = "room_" . time() . "_" . rand(1000, 9999) . "." . $extension;
    $destination = $uploadDir . $safeFileName;

    if (move_uploaded_file($tmpName, $destination)) {
        return $destination;
    }

    return false;
}

if ($_SERVER["REQUEST_METHOD"] != "POST") {
    header("Location: rooms.php");
    exit();
}

$action = isset($_POST["action"]) ? $_POST["action"] : "";

if ($action === "add") {
    $room_name = mysqli_real_escape_string($conn, $_POST["room_name"]);
    $room_type = mysqli_real_escape_string($conn, $_POST["room_type"]);
    $room_rate = isset($_POST["room_rate"]) ? (float)$_POST["room_rate"] : 0;

    if ($room_name === "" || $room_rate <= 0) {
        header("Location: rooms.php?msg=invalid");
        exit();
    }

    $image = upload_room_image("image_file", $room_type);

    if ($image === false) {
        header("Location: rooms.php?msg=image_error");
        exit();
    }

    $image = mysqli_real_escape_string($conn, $image);

    mysqli_query($conn, "
        INSERT INTO rooms (room_name, room_type, room_rate, image, is_active)
        VALUES ('$room_name', '$room_type', '$room_rate', '$image', 1)
    ");

    header("Location: rooms.php?msg=added");
    exit();
}

if ($action === "update") {
    $room_id = isset($_POST["room_id"]) ? (int)$_POST["room_id"] : 0;
    $room_name = mysqli_real_escape_string($conn, $_POST["room_name"]);
    $room_type = mysqli_real_escape_string($conn, $_POST["room_type"]);
    $room_rate = isset($_POST["room_rate"]) ? (float)$_POST["room_rate"] : 0;
    $current_image = isset($_POST["current_image"]) ? trim($_POST["current_image"]) : "";

    if ($room_id <= 0 || $room_name === "" || $room_rate <= 0) {
        header("Location: rooms.php?msg=invalid");
        exit();
    }

    $image = upload_room_image("image_file", $room_type, $current_image);

    if ($image === false) {
        header("Location: rooms.php?msg=image_error");
        exit();
    }

    $image = mysqli_real_escape_string($conn, $image);

    mysqli_query($conn, "
        UPDATE rooms
        SET room_name = '$room_name',
            room_type = '$room_type',
            room_rate = '$room_rate',
            image = '$image'
        WHERE id = '$room_id'
    ");

    header("Location: rooms.php?msg=updated");
    exit();
}

if ($action === "disable") {
    $room_id = isset($_POST["room_id"]) ? (int)$_POST["room_id"] : 0;

    $activeReservation = get_active_reservation($conn, $room_id);

    if ($activeReservation !== null) {
        header("Location: rooms.php?msg=occupied");
        exit();
    }

    mysqli_query($conn, "UPDATE rooms SET is_active = 0 WHERE id = '$room_id'");
    header("Location: rooms.php?msg=disabled");
    exit();
}

if ($action === "enable") {
    $room_id = isset($_POST["room_id"]) ? (int)$_POST["room_id"] : 0;

    mysqli_query($conn, "UPDATE rooms SET is_active = 1 WHERE id = '$room_id'");
    header("Location: rooms.php?msg=enabled");
    exit();
}

header("Location: rooms.php");
exit();
?>