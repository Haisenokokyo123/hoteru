<?php
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/gcash.php';
require_role('customer');

header('Content-Type: application/json; charset=utf-8');
$token = is_string($_GET['token'] ?? null) ? $_GET['token'] : '';
if (!preg_match('/\A[a-f0-9]{64}\z/', $token)) {
    http_response_code(400);
    echo json_encode(['status' => 'invalid']);
    exit;
}
$conn = db_connect();
if (!payment_attempts_available($conn)) {
    http_response_code(503);
    echo json_encode(['status' => 'unavailable']);
    exit;
}
$customerId = (int) ($_SESSION['user_id'] ?? $_SESSION['id'] ?? 0);
$statement = $conn->prepare('SELECT status, reservation_id, expires_at FROM payment_attempts WHERE booking_token = ? AND customer_id = ? LIMIT 1');
$statement->bind_param('si', $token, $customerId);
$statement->execute();
$attempt = $statement->get_result()->fetch_assoc();
if (!$attempt) {
    http_response_code(404);
    echo json_encode(['status' => 'missing']);
    exit;
}
if ($attempt['status'] === 'pending' && strtotime($attempt['expires_at']) <= time()) {
    $statement = $conn->prepare("UPDATE payment_attempts SET status = 'expired' WHERE booking_token = ? AND status = 'pending'");
    $statement->bind_param('s', $token);
    $statement->execute();
    $attempt['status'] = 'expired';
}
$result = ['status' => $attempt['status']];
if ($attempt['status'] === 'paid' && $attempt['reservation_id']) {
    $result['receipt_url'] = 'receipt.php?id=' . (int) $attempt['reservation_id'];
}
echo json_encode($result);
