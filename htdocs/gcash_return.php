<?php
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/gcash.php';
require_role('customer');
$token = is_string($_GET['token'] ?? null) ? $_GET['token'] : '';
if (!preg_match('/\A[a-f0-9]{64}\z/', $token)) {
    header('Location: customer_home.php');
    exit;
}
$cancelled = isset($_GET['cancelled']);
if ($cancelled) {
    $conn = db_connect();
    if (payment_attempts_available($conn)) {
        $customerId = (int) ($_SESSION['user_id'] ?? $_SESSION['id'] ?? 0);
        $statement = $conn->prepare("UPDATE payment_attempts SET status = 'cancelled' WHERE booking_token = ? AND customer_id = ? AND status = 'pending'");
        $statement->bind_param('si', $token, $customerId);
        $statement->execute();
    }
}
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Confirming online payment — Bongabong View Hotel</title>
    <link rel="stylesheet" href="style.css?v=20261009a">
    <link rel="stylesheet" href="portals.css?v=<?php echo filemtime(__DIR__ . '/portals.css'); ?>">
</head>
<body class="portal-body portal-guest-page">
<?php render_navbar(); ?>
<main class="portal-page"><section class="portal-container portal-empty" aria-live="polite">
    <p class="portal-eyebrow">Secure online payment</p>
    <h1 id="payment-title"><?php echo $cancelled ? 'Payment not completed.' : 'Confirming your payment…'; ?></h1>
    <p id="payment-message"><?php echo $cancelled ? 'Your room has not been booked. You can return and try again.' : 'We will confirm your room as soon as PayMongo tells us that your QRPh payment is complete.'; ?></p>
    <a class="portal-button" id="payment-action" href="customer_home.php#our-rooms"><?php echo $cancelled ? 'Return to rooms' : 'Keep this page open'; ?></a>
</section></main>
<?php render_footer(); ?>
<?php if (!$cancelled) { ?>
<script>
(() => {
  const token = <?php echo json_encode($token); ?>;
  const title = document.getElementById('payment-title');
  const message = document.getElementById('payment-message');
  const action = document.getElementById('payment-action');
  const check = async () => {
    try {
      const response = await fetch('gcash_status.php?token=' + encodeURIComponent(token), {cache: 'no-store'});
      const data = await response.json();
      if (data.status === 'paid' && data.receipt_url) {
        title.textContent = 'Payment confirmed.';
        message.textContent = 'Your room is now booked and visible across the hotel system.';
        action.href = data.receipt_url;
        action.textContent = 'View booking receipt';
        window.location.replace(data.receipt_url);
        return;
      }
      if (['failed', 'expired', 'cancelled', 'missing'].includes(data.status)) {
        title.textContent = 'Payment was not confirmed.';
        message.textContent = 'Your room was not booked. Please return to the rooms and try again.';
        action.href = 'customer_home.php#our-rooms';
        action.textContent = 'Return to rooms';
        return;
      }
    } catch (_) {}
    window.setTimeout(check, 2000);
  };
  check();
})();
</script>
<?php } ?>
</body>
</html>
