<?php
require_once __DIR__ . '/config.php';
require_role('customer');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: customer_home.php');
    exit;
}
require_csrf();

function guest_booking_failed($message, $roomId, $values) {
    $_SESSION['guest_booking_error'] = $message;
    $_SESSION['guest_booking_old'] = $values;
    header('Location: customer_home.php?room_id=' . $roomId . '#book-room-' . $roomId);
    exit;
}

$values = [];
foreach (['full_name', 'contact_number', 'address', 'hours', 'payment_method'] as $field) {
    $values[$field] = is_string($_POST[$field] ?? null) ? trim($_POST[$field]) : '';
}
$roomId = filter_var($_POST['room_id'] ?? null, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]) ?: 0;
$nights = filter_var($values['hours'], FILTER_VALIDATE_INT, ['options' => ['min_range' => 1, 'max_range' => 365]]);

if (!$roomId || !$nights || $values['full_name'] === '' || $values['contact_number'] === '' || $values['address'] === '') {
    guest_booking_failed('Complete your contact details and choose 1 to 365 whole nights.', $roomId, $values);
}
if (strlen($values['full_name']) > 100 || strlen($values['contact_number']) > 50 || strlen($values['address']) > 65535 || !in_array($values['payment_method'], ['Cash', 'GCash'], true)) {
    guest_booking_failed('Check your booking details and choose Cash or GCash.', $roomId, $values);
}

$conn = db_connect();
try {
    $conn->begin_transaction();
    // Lock the room before checking availability, so simultaneous bookings cannot both succeed.
    $stmt = $conn->prepare('SELECT room_rate, is_active FROM rooms WHERE id = ? FOR UPDATE');
    $stmt->bind_param('i', $roomId);
    $stmt->execute();
    $room = $stmt->get_result()->fetch_assoc();
    if (!$room || !(int)$room['is_active']) {
        $conn->rollback();
        guest_booking_failed('This room is no longer available. Please choose another room.', $roomId, $values);
    }

    $now = new DateTimeImmutable();
    $checkIn = $now->format('Y-m-d H:i:s');
    $checkOut = $now->modify('+' . $nights . ' days')->format('Y-m-d H:i:s');
    $stmt = $conn->prepare('SELECT id FROM reservations WHERE room_id = ? AND is_archived = 0 AND check_in < ? AND check_out > ? LIMIT 1 FOR UPDATE');
    $stmt->bind_param('iss', $roomId, $checkOut, $checkIn);
    $stmt->execute();
    if ($stmt->get_result()->fetch_assoc()) {
        $conn->rollback();
        guest_booking_failed('Another guest has just booked this room. Please choose another room.', $roomId, $values);
    }

    $total = round((float)$room['room_rate'] * $nights, 2);
    if ($total <= 0 || $total > 99999999.99) {
        $conn->rollback();
        guest_booking_failed('This room cannot be booked at the current rate. Please contact the front desk.', $roomId, $values);
    }

    $stmt = $conn->prepare("INSERT INTO reservations (room_id, full_name, contact_number, address, id_number, hours, extra_bed, food, damages, payment_method, total_amount, check_in, check_out, is_archived) VALUES (?, ?, ?, ?, '', ?, 0, 0, 0, ?, ?, ?, ?, 0)");
    $stmt->bind_param('isssisdss', $roomId, $values['full_name'], $values['contact_number'], $values['address'], $nights, $values['payment_method'], $total, $checkIn, $checkOut);
    $stmt->execute();
    $conn->commit();
    unset($_SESSION['guest_booking_error'], $_SESSION['guest_booking_old']);
    $_SESSION['guest_booking_success'] = 'Your booking is confirmed. The room is now marked occupied.';
    header('Location: customer_home.php#our-rooms');
    exit;
} catch (mysqli_sql_exception $error) {
    $conn->rollback();
    error_log('Guest booking failed: ' . $error->getMessage());
    guest_booking_failed('Your booking could not be saved. Please try again.', $roomId, $values);
}
