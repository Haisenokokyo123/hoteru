<?php


function db_connect(){


    $servername = "sql113.infinityfree.com";
    $username = "if0_42883779";
    $password = "HwTGLjeaWJ";
    $database = "if0_42883779_hotel_db";


    $conn = mysqli_connect(
        $servername,
        $username,
        $password,
        $database
    );


    if(!$conn){

        die("Database connection failed: " . mysqli_connect_error());

    }


    return $conn;

}





function require_login(){


    if(!isset($_SESSION['role'])){

        header("Location: login.php");
        exit();

    }


}





function e($text){

    return htmlspecialchars($text, ENT_QUOTES, 'UTF-8');

}





function money($amount){

    return "₱" . number_format($amount,2);

}





function get_active_reservation($conn,$room_id){


    $stmt = $conn->prepare("
        SELECT *
        FROM reservations
        WHERE room_id=?
        AND is_archived=0
        AND NOW() BETWEEN check_in AND check_out
        ORDER BY id DESC
        LIMIT 1
    ");


    $stmt->bind_param("i",$room_id);

    $stmt->execute();


    $result = $stmt->get_result();


    if($result->num_rows > 0){

        return $result->fetch_assoc();

    }


    return null;

}





function reservation_status($row){


    $now = date("Y-m-d H:i:s");


    if($row["is_archived"] == 1){

        return "Completed";

    }


    if($now < $row["check_in"]){

        return "Reserved";

    }


    if(
        $now >= $row["check_in"] &&
        $now <= $row["check_out"]
    ){

        return "Occupied";

    }


    return "Completed";

}





function room_image($room){


    if(
        isset($room["image"]) &&
        $room["image"] != ""
    ){

        return $room["image"];

    }


    return "puno.png";

}





function render_navbar(){

?>

<nav>

<a href="index.php">Dashboard</a>

<a href="rooms.php">Rooms</a>

<a href="reservations.php">Reservations</a>

<a href="customers.php">Customers</a>

<a href="transactions.php">Transactions</a>

<a href="logout.php">Logout</a>

</nav>


<?php

}





function render_footer(){

?>

<footer>

<p>
Bongabong View Hotel System
</p>

</footer>


<?php

}


?>