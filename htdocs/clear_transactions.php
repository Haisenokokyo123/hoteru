<?php
require_once __DIR__ . '/config.php';
require_staff();
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: transactions.php');
    exit;
}
require_csrf();
$conn = db_connect();
$conn->query('UPDATE reservations SET is_archived = 1 WHERE check_out <= NOW() AND is_archived = 0');
$redirect = $_POST['redirect'] ?? '';
$allowed = ['index.php?clear=success', 'index.php?clear=success#transactions', 'transactions.php?clear=success'];
if (!in_array($redirect, $allowed, true)) {
    $redirect = 'transactions.php?clear=success';
}
header('Location: ' . $redirect);
exit;
