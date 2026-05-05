<?php
require_once __DIR__ . '/../core/auth.php';
header('Content-Type: application/json');
if (!Auth::check()) { echo json_encode(['error'=>true,'message'=>'Chưa đăng nhập']); exit; }
Auth::requireCsrf();
$body     = json_decode(file_get_contents('php://input'), true);
$cartId   = intval($body['cartId']   ?? 0);
$quantity = intval($body['quantity'] ?? 1);
$userId   = Auth::user()['id'];
if ($cartId<=0||$quantity<1) { echo json_encode(['error'=>true,'message'=>'Dữ liệu không hợp lệ']); exit; }
$item = DB::queryOne(
    "SELECT c.id, s.stock FROM cart c JOIN product_skus s ON c.sku_id = s.id WHERE c.id=? AND c.user_id=?",
    [$cartId, $userId]
);
if (!$item) { echo json_encode(['error'=>true,'message'=>'KhÃ´ng tÃ¬m tháº¥y sáº£n pháº©m']); exit; }
if ($quantity > (int)$item['stock']) { echo json_encode(['error'=>true,'message'=>'KhÃ´ng Ä‘á»§ hÃ ng trong kho']); exit; }
DB::execute("UPDATE cart SET quantity=?, updatedAt=NOW() WHERE id=? AND user_id=?", [$quantity, $cartId, $userId]);
echo json_encode(['error'=>false,'message'=>'Đã cập nhật']);
