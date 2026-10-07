<?php
include "config.php";
require_login();

$conn = db_connect();

$search = isset($_GET["search"]) ? trim($_GET["search"]) : "";
$date_from = isset($_GET["date_from"]) ? trim($_GET["date_from"]) : "";
$date_to = isset($_GET["date_to"]) ? trim($_GET["date_to"]) : "";
$archive_filter = isset($_GET["archive_filter"]) ? $_GET["archive_filter"] : "not_archived";

$where = [];

if ($archive_filter === "not_archived") {
    $where[] = "reservations.is_archived = 0";
} elseif ($archive_filter === "archived") {
    $where[] = "reservations.is_archived = 1";
}

if ($search !== "") {
    $safeSearch = mysqli_real_escape_string($conn, $search);
    $where[] = "(reservations.full_name LIKE '%$safeSearch%' OR rooms.room_name LIKE '%$safeSearch%' OR reservations.payment_method LIKE '%$safeSearch%')";
}

if ($date_from !== "") {
    $safeFrom = mysqli_real_escape_string($conn, $date_from);
    $where[] = "DATE(reservations.date_created) >= '$safeFrom'";
}

if ($date_to !== "") {
    $safeTo = mysqli_real_escape_string($conn, $date_to);
    $where[] = "DATE(reservations.date_created) <= '$safeTo'";
}

$whereSql = count($where) > 0 ? "WHERE " . implode(" AND ", $where) : "";

$transactions = mysqli_query($conn, "
    SELECT reservations.*, rooms.room_name, rooms.room_type
    FROM reservations
    LEFT JOIN rooms ON reservations.room_id = rooms.id
    $whereSql
    ORDER BY reservations.id DESC
    LIMIT 300
");

$totalQuery = mysqli_query($conn, "
    SELECT COUNT(*) AS bookings, SUM(total_amount) AS sales
    FROM reservations
    LEFT JOIN rooms ON reservations.room_id = rooms.id
    $whereSql
");
$totalRow = mysqli_fetch_assoc($totalQuery);
$totalBookings = $totalRow["bookings"] ? $totalRow["bookings"] : 0;
$totalSales = $totalRow["sales"] ? $totalRow["sales"] : 0;
?>

<!DOCTYPE html>
<html>
<head>
    <title>Transaction History - Bongabong View Hotel</title>
    <link rel="stylesheet" href="style.css?v=100">
</head>
<body>

<?php render_navbar(); ?>

<div class="page-wrapper">
    <div class="section-header-row">
        <h1 class="page-title">Transaction History</h1>
        <form action="clear_transactions.php" method="POST" class="clear-form" onsubmit="return confirm('Archive all completed transactions? Active occupied rooms will not be affected.');">
            <input type="hidden" name="redirect" value="transactions.php?clear=success">
            <button type="submit" class="danger-btn">Archive Completed</button>
        </form>
    </div>

    <?php if (isset($_GET["clear"]) && $_GET["clear"] == "success") { ?>
        <div class="success-box page-alert">Completed transactions archived successfully.</div>
    <?php } ?>

    <section class="dashboard simple-dashboard">
        <div class="dash-card">
            <h2><?php echo (int)$totalBookings; ?></h2>
            <p>Transactions Found</p>
        </div>

        <div class="dash-card">
            <h2><?php echo money($totalSales); ?></h2>
            <p>Total Sales Found</p>
        </div>
    </section>

    <div class="panel-box">
        <h2>Search / Filter Transactions</h2>

        <form method="GET" action="transactions.php" class="filter-bar wide-filter">
            <input type="text" name="search" placeholder="Search guest, room, or payment" value="<?php echo e($search); ?>">
            <input type="date" name="date_from" value="<?php echo e($date_from); ?>">
            <input type="date" name="date_to" value="<?php echo e($date_to); ?>">

            <select name="archive_filter">
                <option value="not_archived" <?php if ($archive_filter == "not_archived") echo "selected"; ?>>Current History</option>
                <option value="archived" <?php if ($archive_filter == "archived") echo "selected"; ?>>Archived</option>
                <option value="all" <?php if ($archive_filter == "all") echo "selected"; ?>>All</option>
            </select>

            <button type="submit">Apply</button>
            <a href="transactions.php" class="reset-link">Reset</a>
        </form>
    </div>

    <div class="panel-box table-panel">
        <h2>Transactions</h2>

        <table>
            <tr>
                <th>Guest</th>
                <th>Room</th>
                <th>Check In</th>
                <th>Check Out</th>
                <th>Payment</th>
                <th>Total</th>
                <th>Status</th>
                <th>Receipt</th>
            </tr>

            <?php if ($transactions && mysqli_num_rows($transactions) > 0) { ?>
                <?php while ($row = mysqli_fetch_assoc($transactions)) { ?>
                    <?php $status = reservation_status($row); ?>
                    <tr>
                        <td><?php echo e($row["full_name"]); ?></td>
                        <td><?php echo e($row["room_name"]); ?></td>
                        <td><?php echo e($row["check_in"]); ?></td>
                        <td><?php echo e($row["check_out"]); ?></td>
                        <td><?php echo e($row["payment_method"]); ?></td>
                        <td><?php echo money($row["total_amount"]); ?></td>
                        <td>
                            <span class="status-pill <?php echo $status == 'Active' ? 'status-available' : ($status == 'Archived' ? 'status-disabled' : 'status-neutral'); ?>">
                                <?php echo e($status); ?>
                            </span>
                        </td>
                        <td><a href="receipt.php?id=<?php echo (int)$row["id"]; ?>">View</a></td>
                    </tr>
                <?php } ?>
            <?php } else { ?>
                <tr>
                    <td colspan="8" class="empty-row">No transactions found.</td>
                </tr>
            <?php } ?>
        </table>
    </div>
</div>

<?php render_footer(); ?>

</body>
</html>