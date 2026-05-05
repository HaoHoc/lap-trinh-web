<?php
// ============================================================
//  api/admin/upload-image.php — Upload ảnh lên server
// ============================================================
require_once __DIR__ . '/../../core/auth.php';
Auth::requireAdmin();

header('Content-Type: application/json');

Auth::requireCsrf();
if ($_SERVER['REQUEST_METHOD'] !== 'POST' || empty($_FILES['image'])) {
    echo json_encode(['error' => true, 'message' => 'Không có file được gửi lên']);
    exit;
}

$file     = $_FILES['image'];
$maxSize  = 5 * 1024 * 1024; // 5MB
$allowed  = [
    'image/jpeg' => 'jpg',
    'image/png'  => 'png',
    'image/webp' => 'webp',
    'image/gif'  => 'gif',
];

// Kiểm tra lỗi upload
if ($file['error'] !== UPLOAD_ERR_OK) {
    echo json_encode(['error' => true, 'message' => 'Lỗi upload file']);
    exit;
}

// Kiểm tra kích thước
if ($file['size'] > $maxSize) {
    echo json_encode(['error' => true, 'message' => 'File quá lớn (tối đa 5MB)']);
    exit;
}

// Kiểm tra loại file
$finfo = new finfo(FILEINFO_MIME_TYPE);
$mime = $finfo->file($file['tmp_name']);
if (!isset($allowed[$mime]) || !getimagesize($file['tmp_name'])) {
    echo json_encode(['error' => true, 'message' => 'Chỉ chấp nhận JPG, PNG, WEBP, GIF']);
    exit;
}

// Tạo folder nếu chưa có
$uploadDir = $_SERVER['DOCUMENT_ROOT'] . '/shop-php/assets/uploads/products/';
if (!is_dir($uploadDir)) {
    mkdir($uploadDir, 0755, true);
}

// Tạo tên file unique
$filename = bin2hex(random_bytes(16)) . '.' . $allowed[$mime];
$destPath = $uploadDir . $filename;

if (move_uploaded_file($file['tmp_name'], $destPath)) {
    $url = '/shop-php/assets/uploads/products/' . $filename;
    echo json_encode(['error' => false, 'url' => $url, 'filename' => $filename]);
} else {
    echo json_encode(['error' => true, 'message' => 'Không thể lưu file. Kiểm tra quyền folder.']);
}
