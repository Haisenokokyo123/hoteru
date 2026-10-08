<?php
require_once __DIR__ . '/config.php';
require_role('customer');

$conn = db_connect();
$rooms = mysqli_query($conn, 'SELECT * FROM rooms WHERE is_active=1 ORDER BY room_name');
$roomCount = mysqli_num_rows($rooms);
$heroPhoto = hotel_photo('pic2') ?: 'hoteru.png';
$propertyPhotos = [];
foreach (['pic1', 'pic2', 'pic3'] as $photoName) {
    $photoPath = hotel_photo($photoName);
    if ($photoPath !== null) {
        $propertyPhotos[] = $photoPath;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Your Stay — Bongabong View Hotel</title>
    <link rel="stylesheet" href="style.css?v=20261008b">
    <link rel="stylesheet" href="portals.css?v=20261008b">
</head>
<body class="portal-body portal-guest-page">
<?php render_navbar(); ?>

<main class="portal-page portal-guest-content" id="main-content">
    <section class="portal-guest-hero" aria-labelledby="guest-title">
        <div class="portal-hero-photo" aria-hidden="true">
            <img src="<?php echo e($heroPhoto); ?>" alt="" fetchpriority="high">
        </div>
        <div class="portal-container portal-hero-inner">
        <div class="portal-hero-copy" data-reveal>
            <p class="portal-eyebrow">Bongabong View Hotel · Guest portal</p>
            <h1 id="guest-title">A warm welcome.<br>A stay to <em>remember.</em></h1>
            <p class="portal-intro">Find your place in Bongabong. Explore our rooms and let our front desk help you plan your next stay.</p>
            <div class="portal-hero-actions">
                <a class="portal-button" href="#our-rooms">Explore our rooms <?php echo ui_icon('arrow-up'); ?></a>
                <a class="portal-hero-contact" href="#plan-your-stay">Plan your visit <?php echo ui_icon('arrow-up'); ?></a>
            </div>
            <div class="portal-hero-location">
                <span class="portal-location-mark" aria-hidden="true">✧</span>
                <span>Bongabong, Oriental Mindoro<br><small>Your next stay starts here.</small></span>
            </div>
        </div>
        <p class="portal-hero-note">Come on in.<br><em>Make yourself at home.</em></p>
        </div>
    </section>

    <section class="portal-container portal-welcome" aria-label="Welcome and reservations" data-reveal>
        <div>
            <p class="portal-eyebrow">It’s good to see you</p>
            <h2>Welcome, <?php echo e($_SESSION['name']); ?>.</h2>
        </div>
        <p>Have a stay in mind? Contact our front desk to confirm availability and make a reservation.</p>
        <a class="portal-text-link" href="tel:09228125061">Call 0922 812 5061 <?php echo ui_icon('arrow-up'); ?></a>
    </section>

    <section class="portal-container portal-rooms-section" id="our-rooms" aria-labelledby="rooms-title">
        <div class="portal-section-heading" data-reveal>
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
                <a class="portal-button" href="tel:09228125061">Contact the front desk <?php echo ui_icon('arrow-up'); ?></a>
            </div>
        <?php } else { ?>
            <div class="portal-room-grid">
            <?php while ($room = mysqli_fetch_assoc($rooms)) { ?>
                <article class="portal-room-card" data-reveal>
                    <?php render_room_visual($room, 'portal-room-visual'); ?>
                    <div class="portal-room-content">
                        <p class="portal-eyebrow"><?php echo e($room['room_type']); ?></p>
                        <h3><?php echo e($room['room_name']); ?></h3>
                        <div class="portal-room-bottom">
                            <p class="portal-room-rate"><?php echo money($room['room_rate']); ?><span>per night</span></p>
                            <a class="portal-room-inquire" href="tel:09228125061" aria-label="Call to inquire about <?php echo e($room['room_name']); ?>">Inquire <?php echo ui_icon('arrow-up'); ?></a>
                        </div>
                    </div>
                </article>
            <?php } ?>
            </div>
        <?php } ?>
    </section>

    <?php if ($propertyPhotos) { ?>
    <section class="portal-property-section" aria-labelledby="property-title">
        <div class="portal-container">
            <div class="portal-section-heading" data-reveal>
                <div>
                    <p class="portal-eyebrow">A little closer</p>
                    <h2 id="property-title">A glimpse of <em>Bongabong View.</em></h2>
                </div>
                <p>Get to know the place<br><span>We look forward to welcoming you.</span></p>
            </div>
            <div class="portal-property-gallery">
                <?php foreach ($propertyPhotos as $photoIndex => $photoPath) { ?>
                <figure class="portal-property-photo" data-reveal>
                    <img src="<?php echo e($photoPath); ?>" alt="Bongabong View Hotel — property photograph <?php echo $photoIndex + 1; ?>" loading="lazy" decoding="async">
                    <figcaption><span>Bongabong View</span><span><?php echo str_pad((string) ($photoIndex + 1), 2, '0', STR_PAD_LEFT); ?></span></figcaption>
                </figure>
                <?php } ?>
            </div>
        </div>
    </section>
    <?php } ?>

    <section class="portal-contact" id="plan-your-stay" aria-labelledby="contact-title">
        <div class="portal-container portal-contact-inner">
        <div class="portal-contact-intro" data-reveal>
            <p class="portal-eyebrow">Let’s plan your visit</p>
            <h2 id="contact-title">Your stay,<br><em>just a conversation away.</em></h2>
        </div>
        <div class="portal-contact-details" data-reveal>
            <p>Our front desk can help with room availability, current rates, and your reservation.</p>
            <a class="portal-contact-phone" href="tel:09228125061">0922 812 5061 <span aria-hidden="true"><?php echo ui_icon('arrow-up'); ?></span></a>
            <p class="portal-contact-address">JP Rizal St., Bgy. Poblacion<br>Bongabong, Oriental Mindoro</p>
        </div>
        </div>
    </section>
</main>

<?php render_footer(); ?>
</body>
</html>
