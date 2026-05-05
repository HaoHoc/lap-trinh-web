<?php
require_once __DIR__ . '/../../core/auth.php';
Auth::requireAdmin();
header('Content-Type: application/json');
Auth::requireCsrf();

$body = json_decode(file_get_contents('php://input'), true) ?: [];
$categoryId = intval($body['categoryId'] ?? 0);
$languageId = trim($body['languageId'] ?? '');
$name = trim($body['name'] ?? '');

if ($categoryId <= 0 || !in_array($languageId, SUPPORTED_LANGS, true) || $name === '') {
    echo json_encode(['error' => true, 'message' => 'Invalid translation data']);
    exit;
}

DB::execute(
    "INSERT INTO category_translations (category_id, languageId, name)
     VALUES (?, ?, ?)
     ON DUPLICATE KEY UPDATE name = VALUES(name)",
    [$categoryId, $languageId, $name]
);
echo json_encode(['error' => false]);
