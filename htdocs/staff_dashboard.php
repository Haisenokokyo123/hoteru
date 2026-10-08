<?php
require_once __DIR__ . '/config.php';
require_role('staff');
header('Location: index.php');
exit;
