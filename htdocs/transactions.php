<?php
include "config.php";
require_staff();

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
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Transaction History - Bongabong View Hotel</title>
    <link rel="stylesheet" href="style.css?v=20261008">
</head>
<body>

<?php render_navbar(); ?>

<main class="page-wrapper" id="main-content">
    <div class="section-header-row page-heading">
        <div>
            <span class="eyebrow">The guest ledger</span>
            <h1 class="page-title">Transaction history</h1>
            <p class="page-description">Every stay, payment, and receipt, together in one place.</p>
        </div>
        <form action="clear_transactions.php" method="POST" class="clear-form" onsubmit="return confirm('Archive all completed transactions? Active occupied rooms will not be affected.');">
            <input type="hidden" name="csrf_token" value="<?php echo e(csrf_token()); ?>">
            <input type="hidden" name="redirect" value="transactions.php?clear=success">
            <button type="submit" class="secondary-btn">Archive completed</button>
        </form>
    </div>

    <?php if (isset($_GET["clear"]) && $_GET["clear"] == "success") { ?>
        <div class="success-box page-alert" role="status">Completed transactions archived successfully.</div>
    <?php } ?>

    <section class="dashboard simple-dashboard" aria-label="Filtered transaction totals">
        <div class="dash-card">
            <p>Matching transactions</p>
            <h2><?php echo (int)$totalBookings; ?></h2>
        </div>

        <div class="dash-card">
            <p>Total booking value</p>
            <h2><?php echo money($totalSales); ?></h2>
        </div>
    </section>

    <div class="panel-box">
        <h2>Find a transaction</h2>

        <form method="GET" action="transactions.php" class="filter-bar wide-filter">
            <div class="field-group">
                <label for="transaction-search">Guest, room, or payment</label>
                <input id="transaction-search" type="search" name="search" placeholder="Search transactions" value="<?php echo e($search); ?>">
            </div>
            <div class="field-group">
                <label for="date-from">Booked from</label>
                <input id="date-from" type="date" name="date_from" value="<?php echo e($date_from); ?>">
            </div>
            <div class="field-group">
                <label for="date-to">Booked until</label>
                <input id="date-to" type="date" name="date_to" value="<?php echo e($date_to); ?>">
            </div>
            <div class="field-group">
            <label for="archive-filter">History</label>
            <select id="archive-filter" name="archive_filter">
                <option value="not_archived" <?php if ($archive_filter == "not_archived") echo "selected"; ?>>Current History</option>
                <option value="archived" <?php if ($archive_filter == "archived") echo "selected"; ?>>Archived</option>
                <option value="all" <?php if ($archive_filter == "all") echo "selected"; ?>>All</option>
            </select>
            </div>

            <div class="filter-actions">
                <button type="submit">Apply filters</button>
                <a href="transactions.php" class="reset-link">Clear</a>
            </div>
        </form>
    </div>

    <div class="panel-box table-panel">
        <div class="section-header-row">
            <h2 id="transactions-heading">Guest transactions</h2>
            <span class="section-count">Latest 300 matching records</span>
        </div>

        <div class="table-scroll" role="region" aria-labelledby="transactions-heading" tabindex="0">
        <table>
            <thead>
            <tr>
                <th scope="col">Guest</th>
                <th scope="col">Room</th>
                <th scope="col">Check-in</th>
                <th scope="col">Check-out</th>
                <th scope="col">Payment</th>
                <th scope="col">Total</th>
                <th scope="col">Status</th>
                <th scope="col">Receipt</th>
            </tr>
            </thead>
            <tbody>

            <?php if ($transactions && mysqli_num_rows($transactions) > 0) { ?>
                <?php while ($row = mysqli_fetch_assoc($transactions)) { ?>
                    <?php $status = reservation_status($row); ?>
                    <tr>
                        <td><?php echo e($row["full_name"]); ?></td>
                        <td>
                            <?php if ($status === "Occupied") { ?>
                                <a href="index.php?room_id=<?php echo (int)$row["room_id"]; ?>#booking"><?php echo e($row["room_name"]); ?></a>
                            <?php } else { ?>
                                <?php echo e($row["room_name"] ?? "Removed room"); ?>
                            <?php } ?>
                        </td>
                        <td><?php echo e($row["check_in"]); ?></td>
                        <td><?php echo e($row["check_out"]); ?></td>
                        <td><?php echo e($row["payment_method"]); ?></td>
                        <td><?php echo money($row["total_amount"]); ?></td>
                        <td>
                            <span class="status-pill <?php echo $status === 'Occupied' ? 'status-occupied' : ($status === 'Archived' ? 'status-disabled' : ($status === 'Reserved' ? 'status-available' : 'status-neutral')); ?>">
                                <?php echo e($status); ?>
                            </span>
                        </td>
                        <td><a class="receipt-link" href="receipt.php?id=<?php echo (int)$row["id"]; ?>" aria-label="View receipt for <?php echo e($row["full_name"]); ?>">View <span aria-hidden="true">↗</span></a></td>
                    </tr>
                <?php } ?>
            <?php } else { ?>
                <tr>
                    <td colspan="8" class="empty-row">No transactions match your filters. Try another date range or search.</td>
                </tr>
            <?php } ?>
            </tbody>
        </table>
        </div>
    </div>
</main>

<?php render_footer(); ?>

</body>
</html>
