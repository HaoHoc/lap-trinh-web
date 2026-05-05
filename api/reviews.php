<?php
require_once __DIR__ . '/../core/db.php';
header('Content-Type: application/json');

$productId = intval($_GET['product_id'] ?? 0);
$page = max(1, intval($_GET['page'] ?? 1));
$limit = min(50, max(1, intval($_GET['limit'] ?? 10)));
$offset = ($page - 1) * $limit;

if ($productId <= 0) {
    echo json_encode(['error' => true, 'message' => 'Invalid product id']);
    exit;
}

$reviews = DB::query(
    "SELECT r.*, u.name as userName, u.avatar as userAvatar
     FROM reviews r JOIN users u ON r.user_id = u.id
     WHERE r.product_id = ?
     ORDER BY r.createdAt DESC LIMIT ? OFFSET ?",
    [$productId, $limit, $offset]
);
echo json_encode(['error' => false, 'data' => $reviews]);
