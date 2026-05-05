<?php
session_start();

$lang = $_GET['lang'] ?? 'vi';
$supported = ['vi', 'en'];

if (in_array($lang, $supported)) {
    $_SESSION['lang'] = $lang;
    setcookie('lang', $lang, time() + (365 * 24 * 3600), '/');
}

// Quay lại trang trước (giống window.location.reload())
$referer = $_SERVER['HTTP_REFERER'] ?? '/index.php';
header("Location: $referer");
exit;