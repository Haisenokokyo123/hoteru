<?php

include "config.php";

require_login();


$conn=db_connect();



mysqli_query($conn,"

UPDATE reservations

SET is_archived=1

WHERE check_out < NOW()

AND is_archived=0

");



$redirect =
isset($_POST["redirect"])
?
$_POST["redirect"]
:
"index.php";



header(
"Location: ".$redirect
);

exit();


?>