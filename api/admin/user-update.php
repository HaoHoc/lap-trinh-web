<?php
require_once __DIR__ . '/../../core/auth.php';
Auth::requireAdmin();
header('Content-Type: application/json');
Auth::requireCsrf();
$body = json_decode(file_get_contents('php://input'), true);
$id   = intval($body['id'] ?? 0);
if (!$id) { echo json_encode(['error'=>true,'message'=>'ID không hợp lệ']); exit; }
$name     = trim($body['name']  ?? '');
$phone    = trim($body['phone'] ?? '');
$roleId   = intval($body['role_id'] ?? 2);
$status   = in_array($body['status']??'', ['ACTIVE','INACTIVE']) ? $body['status'] : 'ACTIVE';
$password = trim($body['password'] ?? '');
if (!$name) { echo json_encode(['error'=>true,'message'=>'Tên không được để trống']); exit; }
if ($password) {
    $hash = password_hash($password, PASSWORD_BCRYPT);
    DB::execute("UPDATE users SET name=?,phoneNumber=?,role_id=?,status=?,password=?,updatedAt=NOW() WHERE id=?",
        [$name,$phone,$roleId,$status,$hash,$id]);
} else {
    DB::execute("UPDATE users SET name=?,phoneNumber=?,role_id=?,status=?,updatedAt=NOW() WHERE id=?",
        [$name,$phone,$roleId,$status,$id]);
}
echo json_encode(['error'=>false,'message'=>'Đã cập nhật']);
