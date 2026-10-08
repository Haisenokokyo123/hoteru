<?php
require_once __DIR__ . '/config.php';
require_role('customer');
header('Location: customer_home.php');
exit;
