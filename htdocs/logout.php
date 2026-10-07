<?php

session_start();


// Remove all session data

$_SESSION = array();


// Destroy the session

session_destroy();


// Return to login page

header("Location: login.php");

exit();

?>