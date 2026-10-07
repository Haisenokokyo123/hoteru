<?php

error_reporting(E_ALL);
ini_set('display_errors', 1);

include "config.php";

require_login();

$conn = db_connect();



if($_SERVER["REQUEST_METHOD"] != "POST"){

    header("Location:index.php");
    exit();

}



$room_id = isset($_POST["room_id"]) ? (int)$_POST["room_id"] : 0;


$full_name = mysqli_real_escape_string(
    $conn,
    $_POST["full_name"] ?? ""
);


$contact_number = mysqli_real_escape_string(
    $conn,
    $_POST["contact_number"] ?? ""
);


$address = mysqli_real_escape_string(
    $conn,
    $_POST["address"] ?? ""
);


$nights = isset($_POST["hours"]) ? (int)$_POST["hours"] : 0;


$payment_method = mysqli_real_escape_string(
    $conn,
    $_POST["payment_method"] ?? ""
);



if($room_id <= 0 || $nights <= 0){

    die("Invalid booking details.");

}





// Check if room exists

$roomQuery = mysqli_query(
    $conn,
    "
    SELECT *
    FROM rooms
    WHERE id='$room_id'
    AND is_active=1
    "
);



if(!$roomQuery){

    die(mysqli_error($conn));

}



if(mysqli_num_rows($roomQuery)==0){

    die("Room not found or disabled.");

}




$room = mysqli_fetch_assoc($roomQuery);





// Check if room is currently occupied

$checkRoom = mysqli_query(
    $conn,
    "
    SELECT *
    FROM reservations
    WHERE room_id='$room_id'
    AND is_archived=0
    AND NOW() BETWEEN check_in AND check_out
    "
);



if(!$checkRoom){

    die(mysqli_error($conn));

}



if(mysqli_num_rows($checkRoom)>0){

    echo "

    <script>

    alert('This room is already occupied.');

    window.location='index.php';

    </script>

    ";

    exit();

}






$room_rate = (float)$room["room_rate"];


$total_amount = $room_rate * $nights;



$check_in = date("Y-m-d H:i:s");


$check_out = date(
    "Y-m-d H:i:s",
    strtotime("+".$nights." days")
);





$id_number = "";

$extra_bed = 0;

$food = 0;

$damages = 0;





$sql = "

INSERT INTO reservations

(

room_id,

full_name,

contact_number,

address,

id_number,

hours,

extra_bed,

food,

damages,

payment_method,

total_amount,

check_in,

check_out,

is_archived

)


VALUES

(

'$room_id',

'$full_name',

'$contact_number',

'$address',

'$id_number',

'$nights',

'$extra_bed',

'$food',

'$damages',

'$payment_method',

'$total_amount',

'$check_in',

'$check_out',

0

)

";





$result = mysqli_query($conn,$sql);



if(!$result){

    die("DATABASE ERROR: " . mysqli_error($conn));

}





$reservation_id = mysqli_insert_id($conn);



header(
    "Location: receipt.php?id=".$reservation_id
);


exit();


?>