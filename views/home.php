<?php include 'templates/header.php'; ?>

<section class="card">
    <h2>Home</h2>
    <p>You are logged in.</p>
    <p><strong>Email:</strong> <?php echo htmlspecialchars($_SESSION['user'] ?? ''); ?></p>

    <a class="btn-link" href="index.php?action=logout">Logout</a>
</section>

<?php include 'templates/footer.php'; ?>
