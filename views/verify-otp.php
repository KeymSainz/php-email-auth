<?php include 'templates/header.php'; ?>

<section class="card">
    <h2>Enter OTP Code</h2>
    <p>We sent a 6-digit code to <strong><?php echo htmlspecialchars($_SESSION['pending_login_email'] ?? ''); ?></strong>. Enter it below to continue.</p>

    <?php if (!empty($message)): ?>
        <div class="alert">
            <?php echo htmlspecialchars($message); ?>
        </div>
    <?php endif; ?>

    <form method="POST" action="index.php?action=verify-otp">
        <label for="otp">OTP Code</label>
        <input id="otp" type="text" name="otp" inputmode="numeric" pattern="[0-9]*" maxlength="6" placeholder="000000" required autocomplete="one-time-code">

        <button type="submit">Verify & Continue</button>
    </form>

    <p><a href="index.php?action=login">Back to login</a></p>
</section>

<?php include 'templates/footer.php'; ?>
