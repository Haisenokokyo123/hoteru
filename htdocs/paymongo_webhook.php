<?php
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/gcash.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !online_payment_webhook_is_configured()) {
    http_response_code(404);
    exit;
}

$payload = file_get_contents('php://input');
$signature = $_SERVER['HTTP_PAYMONGO_SIGNATURE'] ?? null;
if (!is_string($payload) || !paymongo_signature_is_valid($payload, $signature)) {
    http_response_code(400);
    exit('Invalid signature.');
}

$event = json_decode($payload, true);
$type = $event['data']['attributes']['type'] ?? '';
$resource = $event['data']['attributes']['data'] ?? [];
$checkoutId = $resource['id'] ?? null;
if (!is_string($checkoutId) || $checkoutId === '') {
    http_response_code(400);
    exit('Missing checkout ID.');
}

$conn = db_connect();
try {
    if ($type === 'checkout_session.payment.paid') {
        confirm_paid_online_payment_attempt($conn, $checkoutId, $event);
    } elseif (in_array($type, ['checkout_session.payment.failed', 'checkout_session.expired'], true)) {
        $status = $type === 'checkout_session.expired' ? 'expired' : 'failed';
        $encoded = json_encode($event, JSON_UNESCAPED_SLASHES);
        $statement = $conn->prepare("UPDATE payment_attempts SET status = ?, provider_payload = ? WHERE provider_checkout_id = ? AND status = 'pending'");
        $statement->bind_param('sss', $status, $encoded, $checkoutId);
        $statement->execute();
    }
    http_response_code(200);
    header('Content-Type: application/json');
    echo '{"received":true}';
} catch (Throwable $error) {
    error_log('PayMongo webhook failed: ' . $error->getMessage());
    http_response_code(500);
    echo 'Temporary webhook processing error.';
}
