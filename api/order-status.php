<?php
require_once __DIR__ . '/../core/auth.php';
header('Content-Type: application/json');

if (!Auth::check()) {
    echo json_encode(['error' => true, 'message' => 'Unauthorized']);
    exit;
}

$orderId = intval($_GET['order_id'] ?? 0);
if ($orderId <= 0) {
    echo json_encode(['error' => true, 'message' => 'Invalid order id']);
    exit;
}

$order = DB::queryOne(
    "SELECT status FROM orders WHERE id = ? AND user_id = ?",
    [$orderId, Auth::user()['id']]
);
if (!$order) {
    echo json_encode(['error' => true, 'message' => 'Order not found']);
    exit;
}

echo json_encode(['error' => false, 'status' => $order['status']]);
