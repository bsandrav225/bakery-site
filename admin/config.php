<?php
if (file_exists(__DIR__ . '/config.local.php')) {
    require_once __DIR__ . '/config.local.php';
}

if (!defined('DB_HOST')) {
    define('DB_HOST', 'localhost');
}
if (!defined('DB_NAME')) {
    define('DB_NAME', 'bakery');
}
if (!defined('DB_USER')) {
    define('DB_USER', 'root');
}
if (!defined('DB_PASS')) {
    define('DB_PASS', '');
}

if (!defined('SITE_URL')) {
    define('SITE_URL', bakeryDetectSiteUrl());
}

if (!defined('ADMIN_URL')) {
    define('ADMIN_URL', rtrim(SITE_URL, '/') . '/admin/');
}
if (!defined('UPLOAD_DIR')) {
    define('UPLOAD_DIR', __DIR__ . '/../uploads/');
}

if (!defined('SMTP_HOST')) {
    define('SMTP_HOST', 'smtp.yandex.ru');
}
if (!defined('SMTP_PORT')) {
    define('SMTP_PORT', 465);
}
if (!defined('SMTP_USER')) {
    define('SMTP_USER', '');
}
if (!defined('SMTP_PASS')) {
    define('SMTP_PASS', '');
}
if (!defined('SMTP_FROM')) {
    define('SMTP_FROM', '');
}

function bakeryDetectSiteUrl(): string
{
    $https = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
        || ((string)($_SERVER['SERVER_PORT'] ?? '') === '443');
    $scheme = $https ? 'https' : 'http';
    $host = $_SERVER['HTTP_HOST'] ?? 'localhost';

    $script = str_replace('\\', '/', (string)($_SERVER['SCRIPT_NAME'] ?? '/index.php'));
    $dir = str_replace('\\', '/', dirname($script));

    if (strlen($dir) >= 6 && substr($dir, -6) === '/admin') {
        $dir = substr($dir, 0, -6);
    }

    $dir = rtrim($dir, '/');
    if ($dir === '' || $dir === '.') {
        return $scheme . '://' . $host . '/';
    }

    return $scheme . '://' . $host . $dir . '/';
}

function bakeryIsLocalHost(): bool
{
    $host = strtolower((string)($_SERVER['HTTP_HOST'] ?? 'localhost'));
    return $host === 'localhost'
        || strpos($host, 'localhost:') === 0
        || $host === '127.0.0.1'
        || strpos($host, '127.0.0.1:') === 0;
}

try {
    $pdo = new PDO(
        'mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=utf8mb4',
        DB_USER,
        DB_PASS
    );
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    if (bakeryIsLocalHost()) {
        die('Не удалось подключиться к базе данных. Проверьте, что MySQL запущен и база `' . DB_NAME . '` создана.');
    }
    die('Сайт временно недоступен.');
}

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

if (bakeryIsLocalHost()) {
    error_reporting(E_ALL);
    ini_set('display_errors', '1');
} else {
    error_reporting(0);
    ini_set('display_errors', '0');
}
