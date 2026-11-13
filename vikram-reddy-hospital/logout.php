<?php
declare(strict_types=1);
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/functions.php';
if ($_SERVER['REQUEST_METHOD'] !== 'POST') redirect('index.php');
verify_csrf();
if (current_user()) audit($pdo, 'LOGOUT', 'auth', (int)current_user()['id'], 'User signed out');
$_SESSION = [];
if (ini_get('session.use_cookies')) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000, $params['path'], $params['domain'], $params['secure'], $params['httponly']);
}
session_destroy();
header('Location: ' . url('login.php'));
exit;
