<?php
require_once __DIR__ . '/../core/auth.php';
header('Content-Type: application/json');

if (!Auth::check()) {
    echo json_encode(['error' => true, 'message' => 'Unauthorized']);
    exit;
}
Auth::requireCsrf();

$body = json_decode(file_get_contents('php://input'), true) ?: [];
$orderId = intval($body['orderId'] ?? 0);
if ($orderId <= 0) {
    echo json_encode(['error' => true, 'message' => 'Invalid order id']);
    exit;
}

try {
    DB::beginTransaction();
    $order = DB::queryOne(
        "SELECT id, status FROM orders WHERE id = ? AND user_id = ?",
        [$orderId, Auth::user()['id']]
    );
    if (!$order) {
        DB::rollback();
        echo json_encode(['error' => true, 'message' => 'Order not found']);
        exit;
    }
    if (!in_array($order['status'], ['PENDING_PAYMENT', 'PENDING_PICKUP'], true)) {
        DB::rollback();
        echo json_encode(['error' => true, 'message' => 'Order cannot be cancelled']);
        exit;
    }

    $items = DB::query("SELECT sku_id, quantity FROM order_items WHERE order_id = ?", [$orderId]);
    foreach ($items as $item) {
        DB::execute("UPDATE product_skus SET stock = stock + ? WHERE id = ?", [$item['quantity'], $item['sku_id']]);
    }
    DB::execute("UPDATE orders SET status = 'CANCELLED', updatedAt = NOW() WHERE id = ?", [$orderId]);
    DB::commit();
    echo json_encode(['error' => false, 'message' => 'Order cancelled']);
} catch (Exception $e) {
    DB::rollback();
    echo json_encode(['error' => true, 'message' => $e->getMessage()]);
}
