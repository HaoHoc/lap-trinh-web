<?php
require_once __DIR__ . '/../../core/auth.php';
Auth::requireAdmin();
header('Content-Type: application/json');
Auth::requireCsrf();

$body = json_decode(file_get_contents('php://input'), true) ?: [];
$id = intval($body['id'] ?? 0);
$name = trim($body['name'] ?? '');
$logo = trim($body['logo'] ?? '');
$parentId = isset($body['parentCategoryId']) && $body['parentCategoryId'] !== null
    ? intval($body['parentCategoryId'])
    : null;

if ($id <= 0 || $name === '') {
    echo json_encode(['error' => true, 'message' => 'Invalid category data']);
    exit;
}
if ($parentId !== null && $parentId === $id) {
    echo json_encode(['error' => true, 'message' => 'Category cannot be its own parent']);
    exit;
}
if ($parentId !== null && !DB::queryOne("SELECT id FROM categories WHERE id = ?", [$parentId])) {
    echo json_encode(['error' => true, 'message' => 'Parent category not found']);
    exit;
}

DB::execute(
    "UPDATE categories SET name = ?, logo = ?, parentCategoryId = ?, updatedAt = NOW() WHERE id = ?",
    [$name, $logo, $parentId, $id]
);
echo json_encode(['error' => false]);
