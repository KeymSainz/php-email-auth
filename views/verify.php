<?php include 'templates/header.php'; ?>

<section class="card">
    <h2>Email verification</h2>

    <?php if (!empty($message)): ?>
        <div class="alert">
            <?php echo htmlspecialchars($message); ?>
        </div>
    <?php endif; ?>

    <p><a href="index.php?action=login">Go to login</a></p>
    <p><a href="index.php?action=register">Back to register</a></p>
</section>

<?php include 'templates/footer.php'; ?>
