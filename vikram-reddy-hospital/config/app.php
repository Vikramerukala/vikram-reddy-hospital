<?php
declare(strict_types=1);

define('APP_NAME', 'Vikram Reddy Hospital');
define('APP_SHORT_NAME', 'VRH');
$configuredBaseUrl = getenv('APP_BASE_URL');
define('BASE_URL', $configuredBaseUrl !== false && $configuredBaseUrl !== '' ? rtrim($configuredBaseUrl, '/') : '/vikram-reddy-hospital'); // Set APP_BASE_URL or edit this when changing the folder name

date_default_timezone_set('Asia/Kolkata');
if (session_status() !== PHP_SESSION_ACTIVE) {
    $secure = !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off';
    session_set_cookie_params([
        'lifetime' => 0,
        'path' => '/',
        'secure' => $secure,
        'httponly' => true,
        'samesite' => 'Lax',
    ]);
    session_start();
}
