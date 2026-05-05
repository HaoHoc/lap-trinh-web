<?php
require_once __DIR__ . '/../core/auth.php';
header('Content-Type: application/json');

if (!Auth::check()) { echo json_encode(['error'=>true,'message'=>'Chưa đăng nhập']); exit; }

Auth::requireCsrf();
$body    = json_decode(file_get_contents('php://input'), true);
$userId  = Auth::user()['id'];
$cartIds = array_map('intval', $body['cartIds'] ?? []);
$method  = $body['payment_method'] ?? 'COD';

if (empty($cartIds)) { echo json_encode(['error'=>true,'message'=>'Không có sản phẩm']); exit; }
if (empty(trim($body['receiver_name']??'')) || empty(trim($body['receiver_phone']??'')) || empty(trim($body['receiver_address']??''))) {
    echo json_encode(['error'=>true,'message'=>'Thiếu thông tin bắt buộc']); exit;
}

try {
    DB::beginTransaction();

    $placeholders = implode(',', array_fill(0, count($cartIds), '?'));
    $items = DB::query(
        "SELECT c.*, s.value as skuValue, s.price, s.stock, s.image as skuImage,
                s.product_id as productId, p.name as productName, p.images as productImages
         FROM cart c
         JOIN product_skus s ON c.sku_id = s.id
         JOIN products p ON s.product_id = p.id
         WHERE c.id IN ($placeholders) AND c.user_id = ?",
        array_merge($cartIds, [$userId])
    );

    if (empty($items)) { DB::rollback(); echo json_encode(['error'=>true,'message'=>'Không tìm thấy sản phẩm']); exit; }

    foreach ($items as $it) {
        if ($it['quantity'] > $it['stock']) {
            DB::rollback();
            echo json_encode(['error'=>true,'message'=>"'{$it['productName']}' không đủ hàng"]);
            exit;
        }
    }

    // Lưu địa chỉ nếu được yêu cầu
    if (!empty($body['save_address'])) {
        $isDefault = !empty($body['is_default']) ? 1 : 0;
        if ($isDefault) {
            DB::execute("UPDATE addresses SET is_default=0 WHERE user_id=?", [$userId]);
        }
        // Kiểm tra địa chỉ đã tồn tại chưa
        $existing = DB::queryOne(
            "SELECT id FROM addresses WHERE user_id=? AND address=?",
            [$userId, trim($body['receiver_address'])]
        );
        if (!$existing) {
            DB::execute(
                "INSERT INTO addresses (user_id, name, phone, address, is_default) VALUES (?,?,?,?,?)",
                [$userId, trim($body['receiver_name']), trim($body['receiver_phone']),
                 trim($body['receiver_address']), $isDefault]
            );
        }
    }

    // Tạo payment
    DB::execute("INSERT INTO payments (method, status) VALUES (?, 'PENDING')", [$method]);
    $paymentId = DB::lastInsertId();

    // Tạo order
    DB::execute(
        "INSERT INTO orders (user_id, payment_id, status, receiver_name, receiver_phone,
                             receiver_email, receiver_address, receiver_note, isCOD)
         VALUES (?, ?, 'PENDING_PAYMENT', ?, ?, ?, ?, ?, ?)",
        [$userId, $paymentId, trim($body['receiver_name']), trim($body['receiver_phone']),
         trim($body['receiver_email']??''), trim($body['receiver_address']),
         trim($body['receiver_note']??''), $method==='COD'?1:0]
    );
    $orderId = DB::lastInsertId();

    // Tạo order items & giảm stock
    foreach ($items as $it) {
        $imgs = json_decode($it['productImages']??'[]', true);
        $img  = $it['skuImage'] ?: ($imgs[0]??'');
        DB::execute(
            "INSERT INTO order_items (order_id, product_id, sku_id, productName, skuValue, skuPrice, image, quantity)
             VALUES (?,?,?,?,?,?,?,?)",
            [$orderId, $it['productId'], $it['sku_id'], $it['productName'],
             $it['skuValue'], $it['price'], $img, $it['quantity']]
        );
        $updated = DB::execute(
            "UPDATE product_skus SET stock = stock - ? WHERE id = ? AND stock >= ?",
            [$it['quantity'], $it['sku_id'], $it['quantity']]
        );
        if ($updated !== 1) {
            DB::rollback();
            echo json_encode(['error'=>true,'message'=>"'{$it['productName']}' khÃ´ng Ä‘á»§ hÃ ng"]);
            exit;
        }
    }

    if ($method === 'COD') {
        DB::execute("UPDATE orders SET status='PENDING_PICKUP' WHERE id=?", [$orderId]);
    }

    DB::execute("DELETE FROM cart WHERE id IN ($placeholders) AND user_id=?", array_merge($cartIds, [$userId]));

    DB::commit();
    echo json_encode(['error'=>false, 'orderId'=>$orderId]);

} catch (Exception $e) {
    DB::rollback();
    echo json_encode(['error'=>true,'message'=>$e->getMessage()]);
}
