<?php
require_once __DIR__ . '/../models/User.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

class AuthController {
    private $userModel;

    public function __construct($conn) {
        $this->userModel = new User($conn);
    }

    public function register($email, $password) {
        if (empty($email) || empty($password)) {
            return 'Email and password are required.';
        }

        return $this->userModel->register($email, $password);
    }

    public function verify($code) {
        if (empty($code)) {
            return false;
        }

        return $this->userModel->verify($code);
    }

    public function login($email, $password) {
        if (empty($email) || empty($password)) {
            return 'Email and password are required.';
        }

        $result = $this->userModel->login($email, $password);
        if (is_array($result)) {
            $_SESSION['user'] = $result['email'];
            header('Location: index.php?action=home');
            exit();
        }

        return $result;
    }

    public function logout() {
        $_SESSION = [];
        session_destroy();
        header('Location: index.php?action=login');
        exit();
    }
}
?>