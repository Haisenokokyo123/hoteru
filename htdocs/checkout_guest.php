<?php
require_once __DIR__ . '/config.php';
require_staff();
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: index.php');
    exit;
}
require_csrf();
$id = filter_var($_POST['reservation_id'] ?? null, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
if (!$id) {
    http_response_code(400);
    exit('Choose a valid reservation to check out.');
}
$conn = db_connect();
$stmt = $conn->prepare('UPDATE reservations SET check_out = NOW(), is_archived = 1 WHERE id = ? AND is_archived = 0 AND check_in <= NOW()');
$stmt->bind_param('i', $id);
$stmt->execute();
if ($stmt->affected_rows !== 1) {
    http_response_code(409);
    exit('This reservation is already checked out, has not started, or no longer exists.');
}
header('Location: index.php?checkout=success');
exit;
