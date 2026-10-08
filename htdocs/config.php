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





function ui_icon($name, $class = '') {
    $paths = [
        'arrow' => '<path d="M5 12h14m-6-6 6 6-6 6"/>',
        'bed' => '<path d="M3 18v-7a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2v7M3 15h18M5 9V5h14v4M7 9V7h4v2m2 0V7h4v2M3 18v2m18-2v2"/>',
        'check' => '<path d="m5 12 4 4L19 6"/>',
        'grid' => '<rect x="3" y="3" width="7" height="7" rx="1"/><rect x="14" y="3" width="7" height="7" rx="1"/><rect x="3" y="14" width="7" height="7" rx="1"/><rect x="14" y="14" width="7" height="7" rx="1"/>',
        'clock' => '<circle cx="12" cy="12" r="9"/><path d="M12 7v5l3 2"/>',
        'users' => '<circle cx="9" cy="8" r="3"/><path d="M3 21v-3a6 6 0 0 1 12 0v3m2-16a3 3 0 0 1 0 6m1 4a5 5 0 0 1 3 4v2"/>',
        'plus' => '<path d="M12 5v14M5 12h14"/>',
        'chart' => '<path d="M4 4v16h16M8 16v-4m5 4V8m5 8V5"/>',
        'logout' => '<path d="M9 4H4v16h5m5-13 5 5-5 5m-6-5h11"/>',
        'menu' => '<path d="M4 6h16M4 12h16M4 18h16"/>',
        'key' => '<circle cx="8" cy="9" r="4"/><path d="m11 12 8 8m-4-4 3-3m0 6 3-3"/>',
        'location' => '<path d="M19 10c0 5-7 11-7 11S5 15 5 10a7 7 0 0 1 14 0Z"/><circle cx="12" cy="10" r="2"/>',
    ];
    return '<svg class="ui-icon ' . e($class) . '" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">' . ($paths[$name] ?? $paths['arrow']) . '</svg>';
}

function render_room_visual($room, $class = '') {
    $image = room_image($room);
    $type = strtolower($room['room_type'] ?? '');
    $variant = strpos($type, 'family') !== false ? 'family' : (strpos($type, 'fan') !== false ? 'fan' : (strpos($type, 'single') !== false ? 'twin' : 'king'));
    if ($image !== 'puno.png') {
        echo '<div class="room-visual ' . e($class) . '"><img src="' . e($image) . '" alt="' . e($room['room_name'] ?? $room['room_type'] ?? 'Hotel room') . '" loading="lazy"></div>';
        return;
    }
    echo '<div class="room-visual room-illustration room-illustration--' . $variant . ' ' . e($class) . '" aria-hidden="true"><span class="room-illustration-line"></span>' . ui_icon('bed') . '<span class="room-illustration-label">BONGABONG VIEW</span></div>';
}

function render_navbar($base = ''){
    start_app_session();
    $role = $_SESSION['role'] ?? '';
    $page = basename($_SERVER['SCRIPT_NAME'] ?? '');
    $links = $role === 'customer'
        ? ['customer_home.php' => 'Your stay']
        : ['index.php' => 'Overview', 'rooms.php' => 'Rooms', 'transactions.php' => 'Transactions', 'reports.php' => 'Reports'];
    if ($role === 'admin') $links['admin/dashboard.php'] = 'Administration';
    $home = $role === 'customer' ? 'customer_home.php' : 'index.php';
?>
<a class="skip-link" href="#main-content">Skip to content</a>
<header class="site-header">
    <div class="header-inner">
        <a class="wordmark" href="<?php echo e($base . $home); ?>" aria-label="Bongabong View Hotel home">
            <span class="brand-symbol" aria-hidden="true">BV</span>
            <span class="brand-name">Bongabong View<small>HOTEL &amp; HOSPITALITY</small></span>
        </a>
        <button class="nav-toggle" type="button" aria-expanded="false" aria-controls="main-navigation" aria-label="Open navigation"><?php echo ui_icon('menu'); ?></button>
        <nav class="main-nav" id="main-navigation" aria-label="Main navigation">
            <?php foreach ($links as $href => $label) { ?>
                <a href="<?php echo e($base . $href); ?>" <?php if ($page === basename($href)) echo 'class="is-active" aria-current="page"'; ?>><?php echo e($label); ?></a>
            <?php } ?>
            <a class="mobile-signout" href="<?php echo e($base); ?>logout.php">Sign out</a>
        </nav>
        <div class="header-account">
            <span class="account-role"><span class="presence-dot"></span><?php echo $role === 'staff' ? 'Front desk' : e(ucfirst($role)); ?></span>
            <a class="icon-button" href="<?php echo e($base); ?>logout.php" aria-label="Sign out" title="Sign out"><?php echo ui_icon('logout'); ?></a>
        </div>
    </div>
</header>
<script src="<?php echo e($base); ?>ui.js?v=20261008" defer></script>
<?php
}


function render_footer($base = ''){

?>

<footer class="site-footer">
    <span class="footer-brand">Bongabong View<span>Thoughtful hospitality, every day.</span></span>
    <span>Bongabong, Philippines <span class="footer-separator">/</span> <?php echo date('Y'); ?></span>
</footer>


<?php

}


?>
