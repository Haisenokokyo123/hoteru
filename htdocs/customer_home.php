<?php
require_once __DIR__ . '/config.php';
require_role('customer');

$conn = db_connect();
$rooms = mysqli_query($conn, 'SELECT * FROM rooms WHERE is_active=1 ORDER BY room_name');
$roomCount = mysqli_num_rows($rooms);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Your Stay — Bongabong View Hotel</title>
    <link rel="stylesheet" href="style.css?v=20261008">
    <link rel="stylesheet" href="portals.css?v=20261008">
</head>
<body class="portal-body">
<?php render_navbar(); ?>

<main class="page-wrapper portal-page" id="main-content">
    <section class="portal-guest-hero" aria-labelledby="guest-title">
        <div class="portal-hero-copy">
            <p class="portal-eyebrow">Bongabong View Hotel · Guest portal</p>
            <h1 id="guest-title">A warm welcome.<br>A stay to <em>remember.</em></h1>
            <p class="portal-intro">Find your place in Bongabong. Explore our rooms and let our front desk help you plan your next stay.</p>
            <a class="portal-button" href="#our-rooms">Explore our rooms <span aria-hidden="true">↗</span></a>
            <div class="portal-hero-location">
                <span class="portal-location-mark" aria-hidden="true">✧</span>
                <span>Bongabong, Oriental Mindoro<br><small>Your next stay starts here.</small></span>
            </div>
        </div>
        <figure class="portal-hero-photo">
            <img src="hoteru.png" alt="The entrance and exterior of Bongabong View Hotel" fetchpriority="high">
            <figcaption>Come on in. Make yourself at home.</figcaption>
        </figure>
    </section>

    <section class="portal-welcome" aria-label="Welcome and reservations">
        <div>
            <p class="portal-eyebrow">It’s good to see you</p>
            <h2>Welcome, <?php echo e($_SESSION['name']); ?>.</h2>
        </div>
        <p>Have a stay in mind? Contact our front desk to confirm availability and make a reservation.</p>
        <a class="portal-text-link" href="tel:09228125061">Call 0922 812 5061 <span aria-hidden="true">↗</span></a>
    </section>

    <section class="portal-rooms-section" id="our-rooms" aria-labelledby="rooms-title">
        <div class="portal-section-heading">
            <div>
                <p class="portal-eyebrow">Find your room</p>
                <h2 id="rooms-title">Make room for <em>your stay.</em></h2>
            </div>
            <p><?php echo $roomCount; ?> <?php echo $roomCount === 1 ? 'room' : 'rooms'; ?> to explore<br><span>Contact us for availability</span></p>
        </div>
        <?php if ($roomCount === 0) { ?>
            <div class="portal-empty">
                <h3>Let us help you find your room.</h3>
                <p>Room listings are being updated. Call our front desk for current availability and rates.</p>
                <a class="portal-button" href="tel:09228125061">Contact the front desk <span aria-hidden="true">↗</span></a>
            </div>
        <?php } else { ?>
            <div class="portal-room-grid">
            <?php while ($room = mysqli_fetch_assoc($rooms)) { ?>
                <article class="portal-room-card">
                    <?php render_room_visual($room, 'portal-room-visual'); ?>
                    <div class="portal-room-content">
                        <p class="portal-eyebrow"><?php echo e($room['room_type']); ?></p>
                        <h3><?php echo e($room['room_name']); ?></h3>
                        <div class="portal-room-bottom">
                            <p class="portal-room-rate"><?php echo money($room['room_rate']); ?><span>per night</span></p>
                            <a class="portal-room-inquire" href="tel:09228125061" aria-label="Call to inquire about <?php echo e($room['room_name']); ?>">Inquire <span aria-hidden="true">↗</span></a>
                        </div>
                    </div>
                </article>
            <?php } ?>
            </div>
        <?php } ?>
    </section>

    <section class="portal-contact" aria-labelledby="contact-title">
        <div class="portal-contact-intro">
            <p class="portal-eyebrow">Let’s plan your visit</p>
            <h2 id="contact-title">Your stay,<br><em>just a conversation away.</em></h2>
        </div>
        <div class="portal-contact-details">
            <p>Our front desk can help with room availability, current rates, and your reservation.</p>
            <a class="portal-contact-phone" href="tel:09228125061">0922 812 5061 <span aria-hidden="true">↗</span></a>
            <p class="portal-contact-address">JP Rizal St., Bgy. Poblacion<br>Bongabong, Oriental Mindoro</p>
        </div>
    </section>
</main>

<?php render_footer(); ?>
</body>
</html>
