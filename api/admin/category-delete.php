<?php
require_once __DIR__ . '/../../core/auth.php';
Auth::requireAdmin();
header('Content-Type: application/json');
Auth::requireCsrf();

$body = json_decode(file_get_contents('php://input'), true) ?: [];
$id = intval($body['id'] ?? 0);
if ($id <= 0) {
    echo json_encode(['error' => true, 'message' => 'Invalid category id']);
    exit;
}

$hasChildren = DB::queryOne("SELECT id FROM categories WHERE parentCategoryId = ? LIMIT 1", [$id]);
if ($hasChildren) {
    echo json_encode(['error' => true, 'message' => 'Delete child categories first']);
    exit;
}

DB::execute("DELETE FROM categories WHERE id = ?", [$id]);
echo json_encode(['error' => false]);
