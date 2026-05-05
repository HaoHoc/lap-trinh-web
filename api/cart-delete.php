<?php
require_once __DIR__ . '/../core/auth.php';
header('Content-Type: application/json');
if (!Auth::check()) { echo json_encode(['error'=>true,'message'=>'Chưa đăng nhập']); exit; }
Auth::requireCsrf();
$body   = json_decode(file_get_contents('php://input'), true);
$ids    = array_map('intval', $body['ids'] ?? []);
$userId = Auth::user()['id'];
if (empty($ids)) { echo json_encode(['error'=>true,'message'=>'Không có item nào']); exit; }
$placeholders = implode(',', array_fill(0, count($ids), '?'));
DB::execute("DELETE FROM cart WHERE id IN ($placeholders) AND user_id=?", array_merge($ids, [$userId]));
echo json_encode(['error'=>false,'message'=>'Đã xóa']);
