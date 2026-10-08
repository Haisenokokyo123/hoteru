<?php


date_default_timezone_set('Asia/Manila');

function db_connect(){
    // Hosting defaults remain available; local development can override them.
    $servername = getenv('DB_HOST') !== false ? getenv('DB_HOST') : "sql113.infinityfree.com";
    $username = getenv('DB_USER') !== false ? getenv('DB_USER') : "if0_42883779";
    $password = getenv('DB_PASSWORD') !== false ? getenv('DB_PASSWORD') : "HwTGLjeaWJ";
    $database = getenv('DB_NAME') !== false ? getenv('DB_NAME') : "if0_42883779_hotel_db";
    $port = getenv('DB_PORT') !== false ? (int)getenv('DB_PORT') : 3306;

    $socket = getenv('DB_SOCKET') !== false ? getenv('DB_SOCKET') : null;
    $conn = mysqli_connect($servername, $username, $password, $database, $port, $socket);
    mysqli_set_charset($conn, 'utf8mb4');
    mysqli_query($conn, "SET time_zone = '+08:00'");
    return $conn;
}

function start_app_session(){
    if (session_status() !== PHP_SESSION_ACTIVE) {
        session_start();
    }
}

function require_login(){
    start_app_session();
    if (!isset($_SESSION['role']) || !in_array($_SESSION['role'], ['admin', 'staff', 'customer'], true)) {
        header('Location: login.php');
        exit;
    }
}

function require_staff(){
    require_login();
    if (!in_array($_SESSION['role'], ['admin', 'staff'], true)) {
        http_response_code(403);
        exit('Staff access is required.');
    }
}

function require_role($role){
    require_login();
    if ($_SESSION['role'] !== $role) {
        http_response_code(403);
        exit('You do not have access to this page.');
    }
}

function csrf_token(){
    start_app_session();
    if (!isset($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function require_csrf(){
    start_app_session();
    $token = $_POST['csrf_token'] ?? null;
    if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST' || !is_string($token) || !isset($_SESSION['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $token)) {
        http_response_code(403);
        exit('Your session could not be verified. Reload the page and try again.');
    }
}

function role_home($role){
    if ($role === 'admin') {
        return 'admin_dashboard.php';
    }
    return $role === 'customer' ? 'customer_home.php' : 'index.php';
}

function authenticate_user($email, $password, $role = null){
    if (!is_string($email) || !is_string($password)) {
        return null;
    }
    $conn = db_connect();
    $sql = 'SELECT id, name, password, role FROM users WHERE email=?';
    if ($role !== null) {
        $sql .= ' AND role=?';
    }
    $stmt = $conn->prepare($sql . ' LIMIT 1');
    if ($role !== null) {
        $stmt->bind_param('ss', $email, $role);
    } else {
        $stmt->bind_param('s', $email);
    }
    $stmt->execute();
    $user = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    $conn->close();
    if (!$user || !in_array($user['role'], ['admin', 'staff', 'customer'], true)) {
        return null;
    }
    // Support the existing accounts and accounts using PHP password hashes.
    $info = password_get_info($user['password']);
    $valid = $info['algoName'] !== 'unknown'
        ? password_verify($password, $user['password'])
        : hash_equals($user['password'], $password);
    return $valid ? $user : null;
}

function establish_user_session($user){
    start_app_session();
    session_regenerate_id(true);
    $_SESSION = [
        'logged_in' => true,
        'id' => (int)$user['id'],
        'user_id' => (int)$user['id'],
        'name' => $user['name'],
        'role' => $user['role'],
        'csrf_token' => bin2hex(random_bytes(32)),
    ];
}


function e($text){

    return htmlspecialchars((string)$text, ENT_QUOTES, 'UTF-8');

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
        AND check_in <= NOW()
        AND check_out > NOW()
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

        return "Archived";

    }


    if($now < $row["check_in"]){

        return "Reserved";

    }


    if(
        $now >= $row["check_in"] &&
        $now < $row["check_out"]
    ){

        return "Occupied";

    }


    return "Completed";

}





function room_image($room){


    if(
        isset($room["image"]) &&
        $room["image"] != "" &&
        is_file(__DIR__ . "/" . $room["image"])
    ){

        return $room["image"];

    }


    return "puno.png";

}





function render_navbar(){
    start_app_session();
?>
<nav>
<?php if (($_SESSION['role'] ?? '') === 'customer') { ?>
<a href="customer_home.php">My Booking Portal</a>
<?php } else { ?>
<a href="index.php">Dashboard</a>
<a href="rooms.php">Rooms</a>
<a href="transactions.php">Transactions</a>
<a href="reports.php">Reports</a>
<?php if (($_SESSION['role'] ?? '') === 'admin') { ?>
<a href="admin_dashboard.php">Admin</a>
<?php } ?>
<?php } ?>
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
