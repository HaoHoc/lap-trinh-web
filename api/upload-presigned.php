<?php
require_once __DIR__ . '/../core/auth.php';
header('Content-Type: application/json');

if (!Auth::check()) {
    echo json_encode(['error' => true, 'message' => 'Unauthorized']);
    exit;
}
Auth::requireCsrf();

echo json_encode([
    'error' => true,
    'message' => 'Presigned upload is not configured for this local PHP app.',
]);
