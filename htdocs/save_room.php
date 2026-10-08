<?php
require_once __DIR__ . '/config.php';
require_staff();
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: rooms.php');
    exit;
}
require_csrf();

function room_result($message) {
    header('Location: rooms.php?msg=' . $message);
    exit;
}

function upload_room_image($current_image = '') {
    if (!isset($_FILES['image_file']) || $_FILES['image_file']['error'] === UPLOAD_ERR_NO_FILE) {
        return $current_image !== '' ? $current_image : 'puno.png';
    }
    $file = $_FILES['image_file'];
    if ($file['error'] !== UPLOAD_ERR_OK || $file['size'] > 5 * 1024 * 1024) {
        return false;
    }
    $info = @getimagesize($file['tmp_name']);
    $extensions = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp'];
    if (!$info || !isset($extensions[$info['mime']])) {
        return false;
    }
    $upload_dir = __DIR__ . '/images/room_uploads';
    if (!is_dir($upload_dir) && !mkdir($upload_dir, 0755, true)) {
        return false;
    }
    $relative_path = 'images/room_uploads/room_' . bin2hex(random_bytes(16)) . '.' . $extensions[$info['mime']];
    return move_uploaded_file($file['tmp_name'], __DIR__ . '/' . $relative_path) ? $relative_path : false;
}

$action = $_POST['action'] ?? '';
if (!in_array($action, ['add', 'update', 'enable', 'disable'], true)) {
    room_result('invalid');
}
$room_id = filter_var($_POST['room_id'] ?? null, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
if ($action !== 'add' && !$room_id) {
    room_result('invalid');
}
if ($action === 'add' || $action === 'update') {
    $name = is_string($_POST['room_name'] ?? null) ? trim($_POST['room_name']) : '';
    $type = is_string($_POST['room_type'] ?? null) ? trim($_POST['room_type']) : '';
    $rate = filter_var($_POST['room_rate'] ?? null, FILTER_VALIDATE_FLOAT);
    if ($name === '' || strlen($name) > 50 || $type === '' || strlen($type) > 100 || $rate === false || $rate <= 0 || $rate > 99999999.99) {
        room_result('invalid');
    }
}
$conn = db_connect();
try {
    $conn->begin_transaction();
    $room = null;
    if ($action !== 'add') {
        // Use the same row lock as booking to avoid disabling a newly booked room.
        $stmt = $conn->prepare('SELECT * FROM rooms WHERE id = ? FOR UPDATE');
        $stmt->bind_param('i', $room_id);
        $stmt->execute();
        $room = $stmt->get_result()->fetch_assoc();
        if (!$room) {
            $conn->rollback();
            room_result('invalid');
        }
    }
    if ($action === 'disable') {
        $stmt = $conn->prepare('SELECT id FROM reservations WHERE room_id = ? AND is_archived = 0 AND check_out > NOW() LIMIT 1 FOR UPDATE');
        $stmt->bind_param('i', $room_id);
        $stmt->execute();
        if ($stmt->get_result()->fetch_assoc()) {
            $conn->rollback();
            room_result('occupied');
        }
    }
    if ($action === 'enable' || $action === 'disable') {
        $active = $action === 'enable' ? 1 : 0;
        $stmt = $conn->prepare('UPDATE rooms SET is_active = ? WHERE id = ?');
        $stmt->bind_param('ii', $active, $room_id);
    } else {
        $image = upload_room_image($room['image'] ?? '');
        if ($image === false) {
            $conn->rollback();
            room_result('image_error');
        }
        if ($action === 'add') {
            $stmt = $conn->prepare('INSERT INTO rooms (room_name, room_type, room_rate, image, is_active) VALUES (?, ?, ?, ?, 1)');
            $stmt->bind_param('ssds', $name, $type, $rate, $image);
        } else {
            $stmt = $conn->prepare('UPDATE rooms SET room_name = ?, room_type = ?, room_rate = ?, image = ? WHERE id = ?');
            $stmt->bind_param('ssdsi', $name, $type, $rate, $image, $room_id);
        }
    }
    $stmt->execute();
    $conn->commit();
    $messages = ['add' => 'added', 'update' => 'updated', 'enable' => 'enabled', 'disable' => 'disabled'];
    room_result($messages[$action]);
} catch (mysqli_sql_exception $e) {
    $conn->rollback();
    error_log('Room update failed: ' . $e->getMessage());
    room_result('save_error');
}
