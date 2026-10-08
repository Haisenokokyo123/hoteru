<?php
require_once __DIR__ . '/config.php';
require_role('admin');
header('Location: admin/dashboard.php');
exit;
