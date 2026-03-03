<?php include 'templates/header.php'; ?>

<section class="card">
    <h2>Create account</h2>
    <p>Register with your email, then verify it before login.</p>

    <?php if (!empty($message)): ?>
        <div class="alert">
            <?php echo htmlspecialchars($message); ?>
        </div>
    <?php endif; ?>

    <form method="POST" action="index.php?action=register">
        <label for="reg-fullname">Full name</label>
        <input id="reg-fullname" type="text" name="fullname" required>

        <label for="reg-email">Email</label>
        <input id="reg-email" type="email" name="email" required>

        <label for="reg-password">Password</label>
        <input id="reg-password" type="password" name="password" minlength="6" required>

        <button type="submit">Register</button>
    </form>

    <p>Already have an account? <a href="index.php?action=login">Login here</a>.</p>
</section>

<?php include 'templates/footer.php'; ?>
