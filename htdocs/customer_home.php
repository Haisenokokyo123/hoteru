<?php
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/gcash.php';
require_role('customer');

$conn = db_connect();
$rooms = mysqli_query($conn, 'SELECT * FROM rooms WHERE is_active=1 ORDER BY room_name');
$roomCount = mysqli_num_rows($rooms);
$guestBookingError = $_SESSION['guest_booking_error'] ?? '';
$guestBookingOld = $_SESSION['guest_booking_old'] ?? [];
$guestBookingSuccess = $_SESSION['guest_booking_success'] ?? '';
$failedGuestBookingRoomId = filter_var($_GET['room_id'] ?? null, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]) ?: 0;
unset($_SESSION['guest_booking_error'], $_SESSION['guest_booking_old'], $_SESSION['guest_booking_success']);
$heroPhoto = hotel_photo('pic2') ?: 'hoteru.png';
$propertyPhotos = [];
foreach (['pic1', 'pic2', 'pic3', 'pic4', 'pic5', 'pic6'] as $photoName) {
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
    <link rel="stylesheet" href="style.css?v=20261009a">
    <link rel="stylesheet" href="portals.css?v=<?php echo filemtime(__DIR__ . '/portals.css'); ?>">
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
        <?php if ($guestBookingSuccess !== '') { ?>
            <div class="portal-booking-message portal-booking-message--success" role="status"><?php echo e($guestBookingSuccess); ?></div>
        <?php } ?>
        <?php if ($guestBookingError !== '') { ?>
            <div class="portal-booking-message portal-booking-message--error" role="alert"><?php echo e($guestBookingError); ?></div>
        <?php } ?>
        <?php if ($roomCount === 0) { ?>
            <div class="portal-empty">
                <h3>Let us help you find your room.</h3>
                <p>Room listings are being updated. Call our front desk for current availability and rates.</p>
                <a class="portal-button" href="tel:09228125061">Contact the front desk <?php echo ui_icon('arrow-up'); ?></a>
            </div>
        <?php } else { ?>
            <div class="portal-room-grid">
            <?php while ($room = mysqli_fetch_assoc($rooms)) { ?>
                <?php
                    $activeReservation = get_active_reservation($conn, (int) $room['id']);
                    $paymentHold = !$activeReservation ? payment_hold_for_room($conn, (int) $room['id']) : null;
                ?>
                <article class="portal-room-card <?php echo ($activeReservation || $paymentHold) ? 'is-occupied' : ''; ?>" data-reveal>
                    <?php render_room_visual($room, 'portal-room-visual'); ?>
                    <div class="portal-room-content">
                        <p class="portal-eyebrow"><?php echo e($room['room_type']); ?></p>
                        <h3><?php echo e($room['room_name']); ?></h3>
                        <div class="portal-room-bottom">
                            <p class="portal-room-rate"><?php echo money($room['room_rate']); ?><span>per night</span></p>
                            <?php if ($activeReservation) { ?>
                                <span class="portal-room-status">Occupied</span>
                            <?php } elseif ($paymentHold) { ?>
                                <span class="portal-room-status">Online payment in progress</span>
                            <?php } else { ?>
                                <a class="portal-room-inquire" href="#book-room-<?php echo (int) $room['id']; ?>">Book now <?php echo ui_icon('arrow-up'); ?></a>
                            <?php } ?>
                        </div>
                        <?php if (!$activeReservation && !$paymentHold) { ?>
                        <details class="portal-booking-form" id="book-room-<?php echo (int) $room['id']; ?>"<?php echo $failedGuestBookingRoomId === (int) $room['id'] ? ' open' : ''; ?>>
                            <summary>Book <?php echo e($room['room_name']); ?> <span aria-hidden="true">+</span></summary>
                            <form action="save_guest_booking.php" method="POST" data-booking-form data-rate="<?php echo e($room['room_rate']); ?>">
                                <input type="hidden" name="csrf_token" value="<?php echo e(csrf_token()); ?>">
                                <input type="hidden" name="room_id" value="<?php echo (int) $room['id']; ?>">
                                <label>Full name<input type="text" name="full_name" autocomplete="name" maxlength="100" value="<?php echo e($guestBookingOld['full_name'] ?? $_SESSION['name']); ?>" required></label>
                                <label>Contact number<input type="tel" name="contact_number" autocomplete="tel" maxlength="50" value="<?php echo e($guestBookingOld['contact_number'] ?? ''); ?>" required></label>
                                <label>Address<textarea name="address" autocomplete="street-address" required><?php echo e($guestBookingOld['address'] ?? ''); ?></textarea></label>
                                <div class="portal-booking-fields">
                                    <label>Nights<input type="number" name="hours" min="1" max="365" step="1" value="<?php echo e($guestBookingOld['hours'] ?? '1'); ?>" required></label>
                                    <label>Payment method<select name="payment_method" required><option value="Online Payment" selected>Online payment (QRPh)</option></select></label>
                                </div>
                                <section class="portal-stay-summary" aria-label="Stay summary">
                                    <h4>Stay summary</h4>
                                    <p><span>Rate per night</span><strong data-rate-display><?php echo money($room['room_rate']); ?></strong></p>
                                    <p><span>Room charge</span><strong data-room-charge><?php echo money($room['room_rate'] * (int) ($guestBookingOld['hours'] ?? 1)); ?></strong></p>
                                    <p class="portal-stay-total"><span>Total amount</span><strong data-total-display><?php echo money($room['room_rate'] * (int) ($guestBookingOld['hours'] ?? 1)); ?></strong></p>
                                </section>
                                <button class="portal-button" type="submit">Continue to payment <?php echo ui_icon('arrow-up'); ?></button>
                                <p>QRPh opens an official secure checkout that can be scanned with compatible wallets, including GCash. The room is only booked after payment is confirmed.</p>
                            </form>
                        </details>
                        <?php } ?>
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
