<?php
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/gcash.php';
require_staff();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: index.php');
    exit;
}
require_csrf();

function booking_failed($message, $room_id, $values) {
    $_SESSION['booking_error'] = $message;
    $_SESSION['booking_old'] = $values;
    header('Location: index.php?room_id=' . $room_id . '#booking');
    exit;
}

$values = [];
foreach (['full_name', 'contact_number', 'address', 'hours', 'payment_method'] as $field) {
    $values[$field] = is_string($_POST[$field] ?? null) ? trim($_POST[$field]) : '';
}
$room_id = filter_var($_POST['room_id'] ?? null, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]) ?: 0;
$nights = filter_var($values['hours'], FILTER_VALIDATE_INT, ['options' => ['min_range' => 1, 'max_range' => 365]]);
if (!$room_id || !$nights || $values['full_name'] === '' || $values['contact_number'] === '' || $values['address'] === '') {
    booking_failed('Choose a room, enter the guest details, and select 1 to 365 whole nights.', $room_id, $values);
}
if (strlen($values['full_name']) > 100 || strlen($values['contact_number']) > 50 || strlen($values['address']) > 65535 || !in_array($values['payment_method'], ['Cash', 'GCash'], true)) {
    booking_failed('Check the guest details and choose Cash or GCash as the payment method.', $room_id, $values);
}

$conn = db_connect();
try {
    $conn->begin_transaction();
    // Lock the room so simultaneous submissions cannot both reserve it.
    $stmt = $conn->prepare('SELECT room_rate, is_active FROM rooms WHERE id = ? FOR UPDATE');
    $stmt->bind_param('i', $room_id);
    $stmt->execute();
    $room = $stmt->get_result()->fetch_assoc();
    if (!$room || !(int)$room['is_active']) {
        $conn->rollback();
        booking_failed('This room is unavailable. Please choose another room.', $room_id, $values);
    }
    if (payment_hold_for_room($conn, $room_id)) {
        $conn->rollback();
        booking_failed('An online GCash payment is in progress for this room. Please choose another room or try again in a few minutes.', $room_id, $values);
    }

    $now = new DateTimeImmutable();
    $check_in = $now->format('Y-m-d H:i:s');
    $check_out = $now->modify('+' . $nights . ' days')->format('Y-m-d H:i:s');
    $stmt = $conn->prepare('SELECT id FROM reservations WHERE room_id = ? AND is_archived = 0 AND check_in < ? AND check_out > ? LIMIT 1 FOR UPDATE');
    $stmt->bind_param('iss', $room_id, $check_out, $check_in);
    $stmt->execute();
    if ($stmt->get_result()->fetch_assoc()) {
        $conn->rollback();
        booking_failed('This room already has a booking during the requested stay. Please choose another room.', $room_id, $values);
    }

    $total = round((float)$room['room_rate'] * $nights, 2);
    if ($total <= 0 || $total > 99999999.99) {
        $conn->rollback();
        booking_failed('The room rate or stay length produces an invalid total.', $room_id, $values);
    }
    // The supplied database stores the number of nights in its legacy hours column.
    $stmt = $conn->prepare("INSERT INTO reservations (room_id, full_name, contact_number, address, id_number, hours, extra_bed, food, damages, payment_method, total_amount, check_in, check_out, is_archived) VALUES (?, ?, ?, ?, '', ?, 0, 0, 0, ?, ?, ?, ?, 0)");
    $stmt->bind_param('isssisdss', $room_id, $values['full_name'], $values['contact_number'], $values['address'], $nights, $values['payment_method'], $total, $check_in, $check_out);
    $stmt->execute();
    $reservation_id = $conn->insert_id;
    $conn->commit();
    unset($_SESSION['booking_error'], $_SESSION['booking_old']);
    header('Location: receipt.php?id=' . $reservation_id);
    exit;
} catch (mysqli_sql_exception $e) {
    $conn->rollback();
    error_log('Booking failed: ' . $e->getMessage());
    booking_failed('The booking could not be saved. Please try again.', $room_id, $values);
}
