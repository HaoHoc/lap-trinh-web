<?php
require_once __DIR__ . '/../core/auth.php';
header('Content-Type: application/json');

if (!Auth::check()) {
    echo json_encode(['error'=>true,'message'=>'Vui lòng đăng nhập.']);
    exit;
}

Auth::requireCsrf();
$body     = json_decode(file_get_contents('php://input'), true);
$skuId    = intval($body['skuId']    ?? 0);
$quantity = intval($body['quantity'] ?? 1);
$userId   = Auth::user()['id'];

if ($skuId <= 0 || $quantity <= 0) {
    echo json_encode(['error'=>true,'message'=>'Dữ liệu không hợp lệ.']);
    exit;
}

// Kiểm tra SKU tồn tại và còn hàng
$sku = DB::queryOne("SELECT * FROM product_skus WHERE id = ?", [$skuId]);
if (!$sku) { echo json_encode(['error'=>true,'message'=>'Sản phẩm không tồn tại.']); exit; }
if ($sku['stock'] < $quantity) { echo json_encode(['error'=>true,'message'=>'Không đủ hàng trong kho.']); exit; }

// Thêm hoặc cập nhật giỏ hàng
$existing = DB::queryOne("SELECT * FROM cart WHERE user_id=? AND sku_id=?", [$userId, $skuId]);
if ($existing) {
    $newQty = $existing['quantity'] + $quantity;
    if ($newQty > $sku['stock']) $newQty = $sku['stock'];
    DB::execute("UPDATE cart SET quantity=?, updatedAt=NOW() WHERE id=?", [$newQty, $existing['id']]);
} else {
    DB::execute("INSERT INTO cart (user_id, sku_id, quantity) VALUES (?,?,?)", [$userId, $skuId, $quantity]);
}

echo json_encode(['error'=>false,'message'=>'Đã thêm vào giỏ hàng!']);
