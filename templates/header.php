<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Email Auth</title>
    <link rel="stylesheet" href="public/style.css">
</head>
<body>
    <header class="topbar">
        <h1>Email Auth System</h1>
        <nav>
            <a href="index.php?action=home">Home</a>
            <?php if (isset($_SESSION['user'])): ?>
                <a href="index.php?action=logout">Logout</a>
            <?php else: ?>
                <a href="index.php?action=login">Login</a>
                <a href="index.php?action=register">Register</a>
            <?php endif; ?>
        </nav>
    </header>
    <main class="container">
