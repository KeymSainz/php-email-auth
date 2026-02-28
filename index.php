<?php
require_once 'config/env.php';
require_once 'config/db.php';
require_once 'controllers/AuthController.php';

$auth = new AuthController($conn);
$action = $_GET['action'] ?? 'login';
$message = null;

switch ($action) {
    case 'register':
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $email = trim($_POST['email'] ?? '');
            $password = $_POST['password'] ?? '';
            $result = $auth->register($email, $password);
            $message = $result === true
                ? 'Registration successful. Check your email to verify your account.'
                : $result;
        }
        include 'views/register.php';
        break;

    case 'verify':
        $code = $_GET['code'] ?? '';
        $result = $auth->verify($code);
        $message = $result
            ? 'Your email has been verified. You can now log in.'
            : 'Invalid or expired verification link.';
        include 'views/verify.php';
        break;

    case 'login':
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $email = trim($_POST['email'] ?? '');
            $password = $_POST['password'] ?? '';
            $message = $auth->login($email, $password);
        }
        include 'views/login.php';
        break;

    case 'home':
        if (!isset($_SESSION['user'])) {
            header('Location: index.php?action=login');
            exit();
        }
        include 'views/home.php';
        break;

    case 'logout':
        $auth->logout();
        break;

    default:
        header('Location: index.php?action=login');
        exit();
}
?>