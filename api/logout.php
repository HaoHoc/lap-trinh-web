<?php
require_once __DIR__ . '/../core/auth.php';
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: /shop-php/index.php');
    exit;
}
Auth::requireCsrf($_POST['csrf_token'] ?? null);
Auth::logout();
