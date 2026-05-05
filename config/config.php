<?php
if (defined('DB_HOST')) return; // Tránh include 2 lần

define('DB_HOST',    'localhost');
define('DB_PORT',    3306);
define('DB_NAME',    'shop_db');
define('DB_USER',    'root');
define('DB_PASS',    '');
define('DB_CHARSET', 'utf8mb4');

define('APP_URL',         'http://localhost:8888/shop-php');
define('DEFAULT_LANG',    'vi');
define('SUPPORTED_LANGS', ['vi', 'en']);

define('UPLOAD_DIR', __DIR__ . '/../assets/uploads/');
define('UPLOAD_URL', APP_URL . '/assets/uploads/');

define('BANK_CODE',    '');
define('BANK_ACCOUNT', '');
define('ORDER_PREFIX', 'PIXCAM');

define('JWT_SECRET',  'pixcam-secret-key-2024');
define('BCRYPT_COST', 10);

if (session_status() === PHP_SESSION_NONE) {
    $sessionPath = session_save_path();
    if (!$sessionPath || !is_dir($sessionPath) || !is_writable($sessionPath)) {
        $fallbackSessionPath = __DIR__ . '/../tmp/sessions';
        if (!is_dir($fallbackSessionPath)) mkdir($fallbackSessionPath, 0755, true);
        session_save_path($fallbackSessionPath);
    }
    session_start();
}
