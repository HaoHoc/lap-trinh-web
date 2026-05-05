<?php
require_once __DIR__ . '/../core/db.php';
header('Content-Type: application/json');

$body = json_decode(file_get_contents('php://input'), true) ?: [];
$email = trim($body['email'] ?? '');
$type = trim($body['type'] ?? 'REGISTER');

if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
    echo json_encode(['error' => true, 'message' => 'Invalid email']);
    exit;
}

$code = (string) random_int(100000, 999999);
DB::execute(
    "INSERT INTO otps (email, code, type, expiresAt) VALUES (?, ?, ?, DATE_ADD(NOW(), INTERVAL 10 MINUTE))",
    [$email, $code, $type]
);

echo json_encode([
    'error' => false,
    'message' => 'OTP created.',
    'dev_otp' => $code,
]);
