<?php
session_start();
require_once 'config/env.php';
require 'vendor/autoload.php';

$googleClientId = $_ENV['GOOGLE_CLIENT_ID'] ?? '';
$googleClientSecret = $_ENV['GOOGLE_CLIENT_SECRET'] ?? '';
$googleRedirectUri = $_ENV['GOOGLE_REDIRECT_URI'] ?? '';

if ($googleClientId === '' || $googleClientSecret === '' || $googleRedirectUri === '') {
    echo 'Google OAuth is not configured. Please set GOOGLE_CLIENT_ID, GOOGLE_CLIENT_SECRET, and GOOGLE_REDIRECT_URI in .env.';
    exit();
}

$client = new Google_Client();
$client->setClientId($googleClientId);
$client->setClientSecret($googleClientSecret);
$client->setRedirectUri($googleRedirectUri);
$client->addScope('email');
$client->addScope('profile');

if (isset($_GET['code'])) {
    $token = $client->fetchAccessTokenWithAuthCode($_GET['code']);
    if (!isset($token['error'])) {
        $client->setAccessToken($token['access_token']);
        $google_oauth = new Google_Service_Oauth2($client);
        $google_account_info = $google_oauth->userinfo->get();
        $_SESSION['user'] = $google_account_info->email;
        $_SESSION['email'] = $google_account_info->email;
        $_SESSION['name'] = $google_account_info->name;
        header('Location: index.php?action=home');
        exit();
    } else {
        echo 'Error logging in with Google.';
    }
} else {
    header('Location: index.php?action=login');
    exit();
}