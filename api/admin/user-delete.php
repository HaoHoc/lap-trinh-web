<?php
require_once __DIR__ . '/../../core/auth.php';
Auth::requireAdmin();
header('Content-Type: application/json');
Auth::requireCsrf();
$body = json_decode(file_get_contents('php://input'), true);
$id   = intval($body['id'] ?? 0);
if (!$id) { echo json_encode(['error'=>true,'message'=>'ID không hợp lệ']); exit; }
if ($id === Auth::user()['id']) { echo json_encode(['error'=>true,'message'=>'Không thể xóa chính mình']); exit; }
DB::execute("DELETE FROM users WHERE id=?", [$id]);
echo json_encode(['error'=>false,'message'=>'Đã xóa']);
