<?php
require_once __DIR__ . '/config.php';
start_app_session();

if (isset($_SESSION['role'])) {
    header('Location: ' . role_home($_SESSION['role']));
    exit;
}

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $user = authenticate_user($_POST['email'] ?? '', $_POST['password'] ?? '', 'staff');
    if ($user !== null) {
        establish_user_session($user);
        header('Location: ' . role_home($user['role']));
        exit;
    }
    $error = 'Incorrect email or password.';
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="theme-color" content="#183d34">
    <title>Staff sign in · Bongabong View Hotel</title>
    <link rel="stylesheet" href="style.css?v=20261008">
    <link rel="stylesheet" href="auth.css?v=20261008">
    <script src="auth.js?v=20261008" defer></script>
</head>
<body class="auth-page">
    <a class="auth-skip" href="#sign-in-form">Skip to sign in</a>
    <main class="auth-shell">
        <section class="auth-visual" aria-label="Bongabong View Hotel">
            <img class="auth-property-photo" src="hoteru.png" alt="The facade of Bongabong View Hotel" fetchpriority="high">
            <div class="auth-visual-shade"></div>
            <a class="auth-brand" href="login.php" aria-label="Bongabong View Hotel home">
                <span class="auth-monogram" aria-hidden="true">BV</span>
                <span class="auth-wordmark">Bongabong View<small>HOTEL &amp; RESTAURANT</small></span>
            </a>
            <div class="auth-visual-copy">
                <span class="auth-kicker">BONGABONG · ORIENTAL MINDORO</span>
                <h2>Behind every stay,<br>a thoughtful team.</h2>
                <p>The little details make all the difference.</p>
            </div>
            <div class="auth-visual-footer">
                <span>A little closer to feeling at home.</span>
                <span class="auth-location-mark" aria-hidden="true">BV / PH</span>
            </div>
        </section>
        <section class="auth-content" aria-labelledby="auth-heading">
            <div class="auth-content-top"><span>A familiar welcome</span><span class="auth-content-mark" aria-hidden="true">✦</span></div>
            <div class="auth-form-wrap">
                <p class="auth-eyebrow">The staff workspace</p>
                <h1 id="auth-heading">Welcome back<span class="auth-heading-dot">.</span></h1>
                <p class="auth-description">Your front desk, reservations, and guest details. All in one place.</p>
                <nav class="auth-account-tabs" aria-label="Account type">
                    <a href="login.php">All accounts</a>
                    <a href="staff_login.php" class="is-active" aria-current="page">Staff</a>
                    <a href="customer_login.php">Guest</a>
                </nav>
                <?php if ($error !== '') { ?>
                    <div class="auth-message auth-message--error" role="alert"><?php echo e($error); ?></div>
                <?php } ?>

                <form class="auth-form" id="sign-in-form" method="POST">

                    <div class="auth-field">
                        <label for="email">Email address</label>
                        <input id="email" type="email" name="email" autocomplete="username" placeholder="you@hotel.com" value="<?php echo e($_POST['email'] ?? ''); ?>" required>
                    </div>
                    <div class="auth-field">
                        <label for="password">Password</label>
                        <div class="auth-password-wrap">
                            <input id="password" type="password" name="password" autocomplete="current-password" placeholder="Enter your password" required>
                            <button class="auth-password-toggle" type="button" aria-label="Show password" aria-controls="password" aria-pressed="false">Show</button>
                        </div>
                    </div>
                    <button class="auth-submit" type="submit"><span>Sign in to your workspace</span><svg viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M5 12h14m-5-5 5 5-5 5" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/></svg></button>
                </form>
                <p class="auth-account-note">For registered front desk staff.</p>
                <div class="auth-divider"></div>
                <p class="auth-create-account">Looking for another account?<br><a href="login.php">Back to all accounts <?php echo ui_icon('arrow'); ?></a></p>
            </div>
            <footer class="auth-content-footer"><span>Bongabong View Hotel</span><span>Stay a little closer.</span></footer>
        </section>
    </main>
</body>
</html>
