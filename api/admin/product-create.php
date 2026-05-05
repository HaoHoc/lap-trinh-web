<?php
require_once __DIR__ . '/../../core/auth.php';
Auth::requireAdmin();
header('Content-Type: application/json');
Auth::requireCsrf();

echo json_encode([
    'error' => true,
    'message' => 'Use product-save.php for local product creation.',
]);
