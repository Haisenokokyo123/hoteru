<?php

include "config.php";

require_login();


$conn=db_connect();



if(!isset($_POST["reservation_id"])){

    header("Location:index.php");
    exit();

}



$id=(int)$_POST["reservation_id"];



mysqli_query($conn,"
UPDATE reservations

SET

check_out = NOW(),
is_archived = 1

WHERE id='$id'

");



header(
"Location:index.php?checkout=success"
);

exit();


?>