<?php
require_once __DIR__ . '/../../core/auth.php';
Auth::requireAdmin();
header('Content-Type: application/json');

Auth::requireCsrf();
$body   = json_decode(file_get_contents('php://input'), true);
$id     = intval($body['id'] ?? 0);
$status = $body['status'] ?? '';

$allowed = ['PENDING_PAYMENT','PENDING_PICKUP','PENDING_DELIVERY','DELIVERED','RETURNED','CANCELLED'];
if (!in_array($status, $allowed)) { echo json_encode(['error'=>true,'message'=>'Trạng thái không hợp lệ']); exit; }

DB::execute("UPDATE orders SET status=?, updatedAt=NOW() WHERE id=?", [$status, $id]);
echo json_encode(['error'=>false,'message'=>'Đã cập nhật']);
