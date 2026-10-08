<?php

session_start();

include "config.php";


$error = "";
$success = "";


if ($_SERVER["REQUEST_METHOD"] == "POST") {


    $conn = db_connect();



    $name = mysqli_real_escape_string(
        $conn,
        $_POST["name"]
    );


    $email = mysqli_real_escape_string(
        $conn,
        $_POST["email"]
    );


    $password = mysqli_real_escape_string(
        $conn,
        $_POST["password"]
    );



    // Check if email already exists

    $check = mysqli_query(
        $conn,
        "
        SELECT *
        FROM users
        WHERE email='$email'
        "
    );



    if(mysqli_num_rows($check) > 0){


        $error = "Email already registered.";


    } else {



        $insert = mysqli_query(
            $conn,
            "
            INSERT INTO users
            (
                name,
                email,
                password,
                role
            )

            VALUES

            (
                '$name',
                '$email',
                '$password',
                'customer'
            )
            "
        );



        if($insert){


            $success = "Account created successfully. You can now login.";


        } else {


            $error = "Registration failed.";


        }


    }



}


?>


<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="theme-color" content="#183d34">
    <title>Create your account · Bongabong View Hotel</title>
    <link rel="stylesheet" href="style.css?v=20261008">
    <link rel="stylesheet" href="auth.css?v=20261008">
    <script src="auth.js?v=20261008" defer></script>
</head>
<body class="auth-page">
    <a class="auth-skip" href="#sign-in-form">Skip to registration</a>
    <main class="auth-shell auth-shell--register">
        <section class="auth-visual" aria-label="Bongabong View Hotel">
            <img class="auth-property-photo" src="hoteru.png" alt="The facade of Bongabong View Hotel" fetchpriority="high">
            <div class="auth-visual-shade"></div>
            <a class="auth-brand" href="login.php" aria-label="Bongabong View Hotel home">
                <span class="auth-monogram" aria-hidden="true">BV</span>
                <span class="auth-wordmark">Bongabong View<small>HOTEL &amp; RESTAURANT</small></span>
            </a>
            <div class="auth-visual-copy">
                <span class="auth-kicker">BONGABONG · ORIENTAL MINDORO</span>
                <h2>A new place.<br>A familiar feeling.</h2>
                <p>We look forward to welcoming you.</p>
            </div>
            <div class="auth-visual-footer">
                <span>A little closer to feeling at home.</span>
                <span class="auth-location-mark" aria-hidden="true">BV / PH</span>
            </div>
        </section>
        <section class="auth-content" aria-labelledby="auth-heading">
            <div class="auth-content-top"><span>A new beginning</span><span class="auth-content-mark" aria-hidden="true">✦</span></div>
            <div class="auth-form-wrap">
                <p class="auth-eyebrow">Guest registration</p>
                <h1 id="auth-heading">Make yourself<br>at home<span class="auth-heading-dot">.</span></h1>
                <p class="auth-description">Create your account and start planning a stay at Bongabong View.</p>
                <a class="auth-back-link" href="customer_login.php"><span aria-hidden="true">←</span> Back to guest sign in</a>
                <?php if ($error !== '') { ?>
                    <div class="auth-message auth-message--error" role="alert"><?php echo e($error); ?></div>
                <?php } ?>
                <?php if ($success !== '') { ?>
                    <div class="auth-message auth-message--success" role="status"><?php echo e($success); ?></div>
                <?php } ?>
                <form class="auth-form" id="sign-in-form" method="POST">
                    <div class="auth-field">
                        <label for="name">Full name</label>
                        <input id="name" type="text" name="name" autocomplete="name" placeholder="Your full name" value="<?php echo e($_POST['name'] ?? ''); ?>" required>
                    </div>
                    <div class="auth-field">
                        <label for="email">Email address</label>
                        <input id="email" type="email" name="email" autocomplete="email" placeholder="you@example.com" value="<?php echo e($_POST['email'] ?? ''); ?>" required>
                    </div>
                    <div class="auth-field">
                        <label for="password">Password</label>
                        <div class="auth-password-wrap">
                            <input id="password" type="password" name="password" autocomplete="new-password" placeholder="Create a password" required>
                            <button class="auth-password-toggle" type="button" aria-label="Show password" aria-controls="password" aria-pressed="false">Show</button>
                        </div>
                    </div>
                    <button class="auth-submit" type="submit"><span>Create guest account</span><svg viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M5 12h14m-5-5 5 5-5 5" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/></svg></button>
                </form>
                <p class="auth-account-note">Your account gives you access to rooms and reservations.</p>
                <div class="auth-divider"></div>
                <p class="auth-create-account">Already have an account?<br><a href="customer_login.php">Sign in <?php echo ui_icon('arrow'); ?></a></p>
            </div>
            <footer class="auth-content-footer"><span>Bongabong View Hotel</span><span>Stay a little closer.</span></footer>
        </section>
    </main>
</body>
</html>
