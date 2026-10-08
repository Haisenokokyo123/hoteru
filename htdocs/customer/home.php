<?php
require_once dirname(__DIR__) . '/config.php';
start_app_session();
if (!isset($_SESSION['role'])) {
    header('Location: ../login.php');
    exit;
}
require_role('customer');
header('Location: ../customer_home.php');
exit;
