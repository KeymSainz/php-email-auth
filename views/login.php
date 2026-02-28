<?php include 'templates/header.php'; ?>

<section class="card">
    <h2>Login</h2>
    <p>Use a verified account to continue.</p>

    <?php if (!empty($message)): ?>
        <div class="alert">
            <?php echo htmlspecialchars($message); ?>
        </div>
    <?php endif; ?>

    <form method="POST" action="index.php?action=login">
        <label for="login-email">Email</label>
        <input id="login-email" type="email" name="email" required>

        <label for="login-password">Password</label>
        <input id="login-password" type="password" name="password" required>

        <button type="submit">Login</button>
    </form>

    <p>New here? <a href="index.php?action=register">Create an account</a>.</p>
</section>

<?php include 'templates/footer.php'; ?>
