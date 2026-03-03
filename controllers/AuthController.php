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
            $sendResult = $this->userModel->sendLoginOtp($result['email']);
            if ($sendResult !== true) {
                return $sendResult;
            }
            $_SESSION['pending_login_email'] = $result['email'];
            header('Location: index.php?action=verify-otp');
            exit();
        }

        return $result;
    }

    public function verifyOtp($email, $otp) {
        if (empty($email) || empty($otp)) {
            return 'Please enter the OTP code.';
        }

        if (!isset($_SESSION['pending_login_email']) || $_SESSION['pending_login_email'] !== $email) {
            return 'Session expired. Please log in again.';
        }

        if (!$this->userModel->verifyLoginOtp($email, $otp)) {
            return 'Invalid or expired OTP code.';
        }

        unset($_SESSION['pending_login_email']);
        $_SESSION['user'] = $email;
        header('Location: index.php?action=home');
        exit();
    }

    public function logout() {
        $_SESSION = [];
        session_destroy();
        header('Location: index.php?action=login');
        exit();
    }
}
?>