<?php
declare(strict_types=1);

require_once __DIR__ . '/app.php';

$host = getenv('DB_HOST') ?: '127.0.0.1';
$db   = getenv('DB_NAME') ?: 'hospital_management';
$user = getenv('DB_USER') ?: 'root';
$pass = getenv('DB_PASS') ?: '';
$charset = 'utf8mb4';
$dsn = "mysql:host={$host};dbname={$db};charset={$charset}";

try {
    $pdo = new PDO($dsn, $user, $pass, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
    ]);
} catch (PDOException $exception) {
    http_response_code(500);
    $message = htmlspecialchars($exception->getMessage(), ENT_QUOTES, 'UTF-8');
    exit('<h1>Database connection failed</h1><p>Check MySQL is running and the settings in <code>config/database.php</code>.</p><pre>' . $message . '</pre>');
}
