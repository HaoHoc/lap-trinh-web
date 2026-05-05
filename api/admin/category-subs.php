<?php
require_once __DIR__ . '/../../core/auth.php';
Auth::requireAdmin();
header('Content-Type: application/json');

$parentId = intval($_GET['parent_id'] ?? 0);
if ($parentId <= 0) {
    echo json_encode(['error' => true, 'message' => 'Invalid parent id', 'data' => []]);
    exit;
}

$subs = DB::query(
    "SELECT * FROM categories WHERE parentCategoryId = ? ORDER BY id",
    [$parentId]
);
echo json_encode(['error' => false, 'data' => $subs]);
