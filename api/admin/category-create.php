<?php
require_once __DIR__ . '/../../core/auth.php';
Auth::requireAdmin();
header('Content-Type: application/json');
Auth::requireCsrf();

$body = json_decode(file_get_contents('php://input'), true) ?: [];
$name = trim($body['name'] ?? '');
$logo = trim($body['logo'] ?? '');
$parentId = isset($body['parentCategoryId']) && $body['parentCategoryId'] !== null
    ? intval($body['parentCategoryId'])
    : null;

if ($name === '') {
    echo json_encode(['error' => true, 'message' => 'Category name is required']);
    exit;
}

if ($parentId !== null && !DB::queryOne("SELECT id FROM categories WHERE id = ?", [$parentId])) {
    echo json_encode(['error' => true, 'message' => 'Parent category not found']);
    exit;
}

DB::execute(
    "INSERT INTO categories (name, logo, parentCategoryId) VALUES (?, ?, ?)",
    [$name, $logo, $parentId]
);
echo json_encode(['error' => false, 'id' => DB::lastInsertId()]);
