<?php
require_once __DIR__ . '/../core/auth.php';
header('Content-Type: application/json');

if (!Auth::check()) {
    echo json_encode(['error' => true, 'message' => 'Vui long dang nhap.']);
    exit;
}
Auth::requireCsrf();

$body = json_decode(file_get_contents('php://input'), true) ?: [];
$productId = intval($body['productId'] ?? 0);
$orderId = intval($body['orderId'] ?? 0);
$rating = intval($body['rating'] ?? 0);
$content = trim($body['content'] ?? '');

if ($productId <= 0 || $orderId <= 0 || $rating < 1 || $rating > 5 || $content === '') {
    echo json_encode(['error' => true, 'message' => 'Invalid review data']);
    exit;
}

$ordered = DB::queryOne(
    "SELECT oi.id FROM order_items oi
     JOIN orders o ON oi.order_id = o.id
     WHERE o.id = ? AND o.user_id = ? AND oi.product_id = ? AND o.status = 'DELIVERED'",
    [$orderId, Auth::user()['id'], $productId]
);
if (!$ordered) {
    echo json_encode(['error' => true, 'message' => 'Only delivered products can be reviewed.']);
    exit;
}

DB::execute(
    "INSERT INTO reviews (user_id, product_id, order_id, rating, content)
     VALUES (?, ?, ?, ?, ?)
     ON DUPLICATE KEY UPDATE rating = VALUES(rating), content = VALUES(content), updateCount = updateCount + 1",
    [Auth::user()['id'], $productId, $orderId, $rating, $content]
);
echo json_encode(['error' => false, 'message' => 'Review saved']);
