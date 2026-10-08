<?php
require_once dirname(__DIR__) . '/config.php';
start_app_session();
if (!isset($_SESSION['role'])) {
    header('Location: ../login.php');
    exit;
}
require_role('admin');

$conn = db_connect();
$total_users = mysqli_fetch_assoc(mysqli_query($conn, 'SELECT COUNT(*) AS total FROM users'))['total'];
$total_staff = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) AS total FROM users WHERE role='staff'"))['total'];
$total_customers = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) AS total FROM users WHERE role='customer'"))['total'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Administration — Bongabong View Hotel</title>
    <link rel="stylesheet" href="../style.css?v=20261008">
    <link rel="stylesheet" href="../portals.css?v=20261008">
</head>
<body class="portal-body">
<?php render_navbar('../'); ?>

<main class="page-wrapper portal-page portal-admin-page" id="main-content">
    <header class="portal-admin-heading">
        <div>
            <p class="portal-eyebrow">Bongabong View Hotel · Administration</p>
            <h1>A clear view of<br><em>your hotel.</em></h1>
            <p class="portal-intro">Welcome back, <?php echo e($_SESSION['name']); ?>. Your people, property, and daily operations, all in one place.</p>
        </div>
        <a class="portal-button" href="../index.php">Open hotel dashboard <span aria-hidden="true">↗</span></a>
    </header>

    <section class="portal-admin-stats" aria-label="Account overview">
        <article class="portal-stat portal-stat-featured">
            <div class="portal-stat-label"><span>Total accounts</span><svg viewBox="0 0 24 24" aria-hidden="true"><path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2m20 0v-2a4 4 0 0 0-3-3.87M16 3.13a4 4 0 0 1 0 7.75"/><circle cx="9" cy="7" r="4"/></svg></div>
            <p class="portal-stat-number"><?php echo (int) $total_users; ?></p>
            <p>Everyone connected to your hotel</p>
        </article>
        <article class="portal-stat">
            <div class="portal-stat-label"><span>Staff accounts</span><svg viewBox="0 0 24 24" aria-hidden="true"><rect x="3" y="7" width="18" height="14" rx="2"/><path d="M8 7V3h8v4M3 12h18M10 12v3h4v-3"/></svg></div>
            <p class="portal-stat-number"><?php echo (int) $total_staff; ?></p>
            <p>Your hotel operations team</p>
        </article>
        <article class="portal-stat">
            <div class="portal-stat-label"><span>Customer accounts</span><svg viewBox="0 0 24 24" aria-hidden="true"><circle cx="12" cy="7" r="4"/><path d="M4 21v-2a6 6 0 0 1 6-6h4a6 6 0 0 1 6 6v2"/></svg></div>
            <p class="portal-stat-number"><?php echo (int) $total_customers; ?></p>
            <p>Guests registered with your hotel</p>
        </article>
    </section>

    <section class="portal-operations" aria-labelledby="operations-title">
        <div class="portal-section-heading">
            <div>
                <p class="portal-eyebrow">Your workspace</p>
                <h2 id="operations-title">Manage the daily details.</h2>
            </div>
            <p>Everything you need<br><span>One thoughtfully organised workspace</span></p>
        </div>
        <div class="portal-action-grid">
            <a class="portal-action" href="../index.php">
                <div class="portal-action-top"><span class="portal-action-icon"><svg viewBox="0 0 24 24" aria-hidden="true"><rect x="4" y="3" width="16" height="18" rx="1"/><path d="M9 21v-5h6v5M8 7h2m4 0h2M8 11h2m4 0h2"/></svg></span><span class="portal-action-arrow" aria-hidden="true">↗</span></div>
                <h3>Hotel dashboard</h3>
                <p>Manage bookings, check room availability, and take care of guest checkouts.</p>
                <span class="portal-action-label">Manage hotel <span aria-hidden="true">→</span></span>
            </a>
            <a class="portal-action" href="../rooms.php">
                <div class="portal-action-top"><span class="portal-action-icon"><svg viewBox="0 0 24 24" aria-hidden="true"><path d="M3 18v3m18-3v3M3 12V5h18v7M2 18v-4a2 2 0 0 1 2-2h16a2 2 0 0 1 2 2v4H2Z"/><path d="M6 12V9h5v3m2 0V9h5v3"/></svg></span><span class="portal-action-arrow" aria-hidden="true">↗</span></div>
                <h3>Rooms &amp; rates</h3>
                <p>Keep your room collection, nightly rates, and availability up to date.</p>
                <span class="portal-action-label">Manage rooms <span aria-hidden="true">→</span></span>
            </a>
            <a class="portal-action" href="../transactions.php">
                <div class="portal-action-top"><span class="portal-action-icon"><svg viewBox="0 0 24 24" aria-hidden="true"><path d="M6 3h12v18l-3-2-3 2-3-2-3 2V3Zm3 4h6m-6 4h6m-6 4h3"/></svg></span><span class="portal-action-arrow" aria-hidden="true">↗</span></div>
                <h3>Transaction history</h3>
                <p>Review guest reservations, payment details, and individual receipts.</p>
                <span class="portal-action-label">View transactions <span aria-hidden="true">→</span></span>
            </a>
            <a class="portal-action" href="../reports.php">
                <div class="portal-action-top"><span class="portal-action-icon"><svg viewBox="0 0 24 24" aria-hidden="true"><path d="M3 3v18h18M7 17v-5m5 5V7m5 10V4"/></svg></span><span class="portal-action-arrow" aria-hidden="true">↗</span></div>
                <h3>Sales &amp; reports</h3>
                <p>Explore reservation totals and revenue across your selected dates.</p>
                <span class="portal-action-label">View reports <span aria-hidden="true">→</span></span>
            </a>
        </div>
    </section>
    <aside class="portal-admin-note">
        <span class="portal-note-mark" aria-hidden="true">✧</span>
        <div><h2>Great stays begin with the details.</h2><p>Head to the hotel dashboard to check today’s room status and help your next guest settle in.</p></div>
        <a class="portal-text-link" href="../index.php">Go to dashboard <span aria-hidden="true">↗</span></a>
    </aside>
</main>

<?php render_footer('../'); ?>
</body>
</html>
