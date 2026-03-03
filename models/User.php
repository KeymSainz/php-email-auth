<?php
require_once __DIR__ . '/../config/env.php';

if (file_exists(__DIR__ . '/../vendor/autoload.php')) {
    require_once __DIR__ . '/../vendor/autoload.php';
} else {
    require_once __DIR__ . '/../PHPMailer-PHPMailer-3cd2a2a/src/Exception.php';
    require_once __DIR__ . '/../PHPMailer-PHPMailer-3cd2a2a/src/PHPMailer.php';
    require_once __DIR__ . '/../PHPMailer-PHPMailer-3cd2a2a/src/SMTP.php';
}

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

class User {
    private $conn;

    public function __construct($conn) {
        $this->conn = $conn;
    }

    public function register($fullname, $email, $password) {
        if (empty(trim($fullname ?? ''))) {
            return 'Full name is required.';
        }

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return 'Please enter a valid email address.';
        }

        if (strlen($password) < 6) {
            return 'Password must be at least 6 characters.';
        }

        $stmt = $this->conn->prepare('SELECT id FROM users WHERE email = ?');
        $stmt->bind_param('s', $email);
        $stmt->execute();
        $stmt->store_result();

        if ($stmt->num_rows > 0) {
            $stmt->close();
            return 'Email already registered.';
        }
        $stmt->close();

        $hashedPassword = password_hash($password, PASSWORD_BCRYPT);
        $fullname = trim($fullname);

        $stmt = $this->conn->prepare(
            'INSERT INTO users (fullname, email, password, is_verified) VALUES (?, ?, ?, 0)'
        );
        $stmt->bind_param('sss', $fullname, $email, $hashedPassword);

        if (!$stmt->execute()) {
            $stmt->close();
            return 'Registration failed. Please try again.';
        }

        $userId = $this->conn->insert_id;
        $stmt->close();

        $token = bin2hex(random_bytes(16));
        $stmt = $this->conn->prepare(
            'INSERT INTO email_verifications (user_id, token) VALUES (?, ?)'
        );
        $stmt->bind_param('is', $userId, $token);

        if (!$stmt->execute()) {
            $stmt->close();
            return 'Registration failed. Please try again.';
        }
        $stmt->close();

        return $this->sendVerificationEmail($email, $token);
    }

    public function verify($code) {
        $stmt = $this->conn->prepare(
            'SELECT user_id FROM email_verifications WHERE token = ? LIMIT 1'
        );
        $stmt->bind_param('s', $code);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows !== 1) {
            $stmt->close();
            return false;
        }

        $row = $result->fetch_assoc();
        $userId = $row['user_id'];
        $stmt->close();

        $stmt = $this->conn->prepare(
            'UPDATE users SET is_verified = 1 WHERE id = ? AND is_verified = 0'
        );
        $stmt->bind_param('i', $userId);
        $stmt->execute();
        $verified = $stmt->affected_rows > 0;
        $stmt->close();

        if ($verified) {
            $stmt = $this->conn->prepare('DELETE FROM email_verifications WHERE user_id = ?');
            $stmt->bind_param('i', $userId);
            $stmt->execute();
            $stmt->close();
        }

        return $verified;
    }

    public function login($email, $password) {
        $stmt = $this->conn->prepare('SELECT * FROM users WHERE email = ? LIMIT 1');
        $stmt->bind_param('s', $email);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows !== 1) {
            $stmt->close();
            return 'Email not found.';
        }

        $user = $result->fetch_assoc();
        $stmt->close();

        if (!password_verify($password, $user['password'])) {
            return 'Incorrect password.';
        }

        if ((int) $user['is_verified'] !== 1) {
            return 'Please verify your email first.';
        }

        return $user;
    }

    public function sendLoginOtp($email) {
        $stmt = $this->conn->prepare('SELECT id FROM users WHERE email = ? LIMIT 1');
        $stmt->bind_param('s', $email);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows !== 1) {
            $stmt->close();
            return 'Failed to generate OTP.';
        }

        $user = $result->fetch_assoc();
        $userId = $user['id'];
        $stmt->close();

        $otp = str_pad((string) random_int(0, 999999), 6, '0', STR_PAD_LEFT);

        $stmt = $this->conn->prepare(
            'INSERT INTO otp_codes (user_id, otp_code, expires_at) VALUES (?, ?, DATE_ADD(NOW(), INTERVAL 10 MINUTE))'
        );
        $stmt->bind_param('is', $userId, $otp);

        if (!$stmt->execute()) {
            $stmt->close();
            return 'Failed to generate OTP.';
        }
        $stmt->close();

        $result = $this->sendOtpEmail($email, $otp);
        if ($result !== true) {
            return $result;
        }

        return true;
    }

    public function verifyLoginOtp($email, $otp) {
        $otp = preg_replace('/\D/', '', $otp);
        if (strlen($otp) !== 6) {
            return false;
        }

        $stmt = $this->conn->prepare('SELECT id FROM users WHERE email = ? LIMIT 1');
        $stmt->bind_param('s', $email);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows !== 1) {
            $stmt->close();
            return false;
        }

        $user = $result->fetch_assoc();
        $userId = $user['id'];
        $stmt->close();

        $stmt = $this->conn->prepare(
            'SELECT id FROM otp_codes WHERE user_id = ? AND otp_code = ? AND expires_at > NOW() LIMIT 1'
        );
        $stmt->bind_param('is', $userId, $otp);
        $stmt->execute();
        $result = $stmt->get_result();
        $valid = $result->num_rows === 1;
        $stmt->close();

        if (!$valid) {
            return false;
        }

        $stmt = $this->conn->prepare('DELETE FROM otp_codes WHERE user_id = ?');
        $stmt->bind_param('i', $userId);
        $stmt->execute();
        $stmt->close();

        return true;
    }

    private function sendOtpEmail($email, $otp) {
        $mail = new PHPMailer(true);
        $smtpUser = $this->env('SMTP_USER', '');
        $smtpPass = $this->env('SMTP_PASS', '');
        $smtpFrom = $this->env('SMTP_FROM', $smtpUser);

        $configError = $this->validateSmtpConfig($smtpUser, $smtpPass, $smtpFrom);
        if ($configError !== null) {
            return $configError;
        }

        try {
            $mail->isSMTP();
            $mail->Host = $this->env('SMTP_HOST', 'smtp.gmail.com');
            $mail->SMTPAuth = true;
            $mail->Username = $smtpUser;
            $mail->Password = $smtpPass;
            $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
            $mail->Port = (int) $this->env('SMTP_PORT', '587');
            $mail->Timeout = 15;

            $mail->setFrom(
                $smtpFrom,
                $this->env('SMTP_FROM_NAME', 'Email Auth System')
            );
            $mail->addAddress($email);

            $mail->isHTML(true);
            $mail->Subject = 'Your Login OTP Code';
            $mail->Body = "
                <h3>Login Verification</h3>
                <p>Your one-time password is:</p>
                <p style='font-size:24px;font-weight:bold;letter-spacing:4px;'>{$otp}</p>
                <p>This code expires in 10 minutes. Do not share it with anyone.</p>
            ";

            $mail->send();
            return true;
        } catch (Exception $e) {
            return 'OTP could not be sent. SMTP Error: ' . $mail->ErrorInfo;
        }
    }

    private function sendVerificationEmail($email, $token) {
        $mail = new PHPMailer(true);
        $verificationLink = $this->buildVerificationLink($token);
        $smtpUser = $this->env('SMTP_USER', '');
        $smtpPass = $this->env('SMTP_PASS', '');
        $smtpFrom = $this->env('SMTP_FROM', $smtpUser);

        $configError = $this->validateSmtpConfig($smtpUser, $smtpPass, $smtpFrom);
        if ($configError !== null) {
            return $configError;
        }

        try {
            $mail->isSMTP();
            $mail->Host = $this->env('SMTP_HOST', 'smtp.gmail.com');
            $mail->SMTPAuth = true;
            $mail->Username = $smtpUser;
            $mail->Password = $smtpPass;
            $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
            $mail->Port = (int) $this->env('SMTP_PORT', '587');
            $mail->Timeout = 15;

            $mail->setFrom(
                $smtpFrom,
                $this->env('SMTP_FROM_NAME', 'Email Auth System')
            );
            $mail->addAddress($email);

            $mail->isHTML(true);
            $mail->Subject = 'Verify Your Email';
            $mail->Body = "
                <h3>Email Verification</h3>
                <p>Click below to verify your account:</p>
                <a href='{$verificationLink}'>Verify Account</a>
            ";

            $mail->send();
            return true;
        } catch (Exception $e) {
            return 'Registered, but email could not be sent. SMTP Error: ' . $mail->ErrorInfo;
        }
    }

    private function buildVerificationLink($token) {
        $appUrl = rtrim($this->env('APP_URL', ''), '/');
        if ($appUrl !== '') {
            return $appUrl . '/index.php?action=verify&code=' . urlencode($token);
        }

        $isHttps = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off';
        $scheme = $isHttps ? 'https' : 'http';
        $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
        $basePath = rtrim(str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? '/index.php')), '/');

        return "{$scheme}://{$host}{$basePath}/index.php?action=verify&code=" . urlencode($token);
    }

    private function env($key, $default = '') {
        return $_ENV[$key] ?? $default;
    }

    private function validateSmtpConfig($smtpUser, $smtpPass, $smtpFrom) {
        if ($smtpUser === '' || $smtpPass === '') {
            return 'SMTP is not configured. Set SMTP_USER and SMTP_PASS in .env.';
        }

        if ($smtpUser === 'your-email@gmail.com' || $smtpUser === 'your_real_gmail@gmail.com') {
            return 'SMTP_USER still has a placeholder value in .env. Set your real Gmail address.';
        }

        if ($smtpPass === 'your-16-char-app-password' || $smtpPass === 'your_actual_16char_app_password_without_spaces') {
            return 'SMTP_PASS still has a placeholder value in .env. Set your real Gmail App Password.';
        }

        if ($smtpFrom === 'your-email@gmail.com' || $smtpFrom === 'your_real_gmail@gmail.com') {
            return 'SMTP_FROM still has a placeholder value in .env. Set your real Gmail address.';
        }

        return null;
    }
}
?>