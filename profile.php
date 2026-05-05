<?php
require_once __DIR__ . '/core/auth.php';
Auth::requireLogin('/shop-php/login.php');

$user  = Auth::user();
$userId = $user['id'];
$error = $success = '';

// Xử lý cập nhật thông tin
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!Auth::verifyCsrf()) {
        $error = 'Invalid form session.';
    } else {
    $action = $_POST['action'] ?? 'update';

    if ($action === 'update') {
        $name  = trim($_POST['name']  ?? '');
        $phone = trim($_POST['phone'] ?? '');
        if (!$name) { $error = 'Tên không được để trống.'; }
        else {
            DB::execute("UPDATE users SET name=?, phoneNumber=?, updatedAt=NOW() WHERE id=?", [$name, $phone, $userId]);
            // Cập nhật session
            $_SESSION['user']['name'] = $name;
            $_SESSION['user']['phoneNumber'] = $phone;
            $success = 'Cập nhật thông tin thành công!';
            $user = Auth::user();
        }
    } elseif ($action === 'change_password') {
        $oldPass  = $_POST['old_password']  ?? '';
        $newPass  = $_POST['new_password']  ?? '';
        $confirm  = $_POST['confirm_password'] ?? '';
        $dbUser   = DB::queryOne("SELECT password FROM users WHERE id=?", [$userId]);
        if (!password_verify($oldPass, $dbUser['password'])) {
            $error = 'Mật khẩu hiện tại không đúng.';
        } elseif (strlen($newPass) < 6) {
            $error = 'Mật khẩu mới phải có ít nhất 6 ký tự.';
        } elseif ($newPass !== $confirm) {
            $error = 'Mật khẩu xác nhận không khớp.';
        } else {
            $hash = password_hash($newPass, PASSWORD_BCRYPT);
            DB::execute("UPDATE users SET password=?, updatedAt=NOW() WHERE id=?", [$hash, $userId]);
            $success = 'Đổi mật khẩu thành công!';
        }
    } elseif ($action === 'upload_avatar' && !empty($_FILES['avatar'])) {
        $file    = $_FILES['avatar'];
        $allowed = [
            'image/jpeg' => 'jpg',
            'image/png'  => 'png',
            'image/webp' => 'webp',
            'image/gif'  => 'gif',
        ];
        $finfo = new finfo(FILEINFO_MIME_TYPE);
        $mime = $finfo->file($file['tmp_name']);
        if ($file['error'] !== UPLOAD_ERR_OK || !isset($allowed[$mime]) || !getimagesize($file['tmp_name'])) {
            $error = 'Chỉ chấp nhận JPG, PNG, WEBP, GIF';
        } elseif ($file['size'] > 2*1024*1024) {
            $error = 'Ảnh tối đa 2MB';
        } else {
            $dir = $_SERVER['DOCUMENT_ROOT'] . '/shop-php/assets/uploads/avatars/';
            if (!is_dir($dir)) mkdir($dir, 0755, true);
            $name = 'avatar_' . $userId . '_' . bin2hex(random_bytes(8)) . '.' . $allowed[$mime];
            if (move_uploaded_file($file['tmp_name'], $dir . $name)) {
                $url = '/shop-php/assets/uploads/avatars/' . $name;
                DB::execute("UPDATE users SET avatar=?, updatedAt=NOW() WHERE id=?", [$url, $userId]);
                $_SESSION['user']['avatar'] = $url;
                $success = 'Cập nhật ảnh đại diện thành công!';
                $user = Auth::user();
            } else { $error = 'Không thể lưu ảnh.'; }
        }
    }
    }
}

// Lấy thống kê đơn hàng
$stats = DB::queryOne(
    "SELECT COUNT(*) as total,
            SUM(CASE WHEN status='DELIVERED' THEN 1 ELSE 0 END) as delivered,
            SUM(CASE WHEN status='PENDING_PICKUP' OR status='PENDING_DELIVERY' THEN 1 ELSE 0 END) as processing
     FROM orders WHERE user_id=?",
    [$userId]
);

$pageTitle = 'Hồ sơ cá nhân - PIXCAM';
require_once __DIR__ . '/includes/header.php';
?>

<div style="max-width:900px;margin:30px auto;padding:0 20px;">
  <h2 style="margin-bottom:20px;">👤 Hồ sơ cá nhân</h2>

  <?php if ($error): ?>
    <div style="padding:12px;background:#fdecea;color:#c0392b;border-radius:6px;margin-bottom:15px;"><?= htmlspecialchars($error) ?></div>
  <?php endif; ?>
  <?php if ($success): ?>
    <div style="padding:12px;background:#eafaf1;color:#27ae60;border-radius:6px;margin-bottom:15px;"><?= htmlspecialchars($success) ?></div>
  <?php endif; ?>

  <div style="display:grid;grid-template-columns:280px 1fr;gap:20px;">

    <!-- Ảnh đại diện + thống kê -->
    <div>
      <div style="background:#fff;border-radius:8px;padding:25px;text-align:center;box-shadow:0 2px 8px rgba(0,0,0,0.05);margin-bottom:15px;">
        <?php if (!empty($user['avatar'])): ?>
          <img src="<?= htmlspecialchars($user['avatar']) ?>"
               style="width:100px;height:100px;border-radius:50%;object-fit:cover;border:3px solid #ee5022;margin-bottom:12px;">
        <?php else: ?>
          <div style="width:100px;height:100px;border-radius:50%;background:#f0f0f0;display:flex;align-items:center;justify-content:center;margin:0 auto 12px;">
            <i class="fas fa-user" style="font-size:40px;color:#ccc;"></i>
          </div>
        <?php endif; ?>
        <h3 style="margin:0 0 5px;"><?= htmlspecialchars($user['name']) ?></h3>
        <p style="color:#999;font-size:13px;margin:0 0 15px;"><?= htmlspecialchars($user['email']) ?></p>

        <!-- Upload avatar -->
        <form method="POST" enctype="multipart/form-data">
          <?= Auth::csrfField() ?>
          <input type="hidden" name="action" value="upload_avatar">
          <label style="display:inline-flex;align-items:center;gap:6px;background:#17a2b8;color:white;padding:8px 14px;border-radius:4px;cursor:pointer;font-size:13px;">
            <i class="fas fa-camera"></i> Đổi ảnh
            <input type="file" name="avatar" accept="image/*" style="display:none;" onchange="this.form.submit()">
          </label>
        </form>
      </div>

      <!-- Thống kê -->
      <div style="background:#fff;border-radius:8px;padding:20px;box-shadow:0 2px 8px rgba(0,0,0,0.05);">
        <h4 style="margin:0 0 15px;">Thống kê đơn hàng</h4>
        <div style="display:flex;flex-direction:column;gap:10px;">
          <div style="display:flex;justify-content:space-between;padding:10px;background:#f9f9f9;border-radius:6px;">
            <span style="font-size:13px;">Tổng đơn hàng</span>
            <strong style="color:#ee5022;"><?= $stats['total'] ?? 0 ?></strong>
          </div>
          <div style="display:flex;justify-content:space-between;padding:10px;background:#f9f9f9;border-radius:6px;">
            <span style="font-size:13px;">Đang xử lý</span>
            <strong style="color:#3498db;"><?= $stats['processing'] ?? 0 ?></strong>
          </div>
          <div style="display:flex;justify-content:space-between;padding:10px;background:#f9f9f9;border-radius:6px;">
            <span style="font-size:13px;">Đã giao</span>
            <strong style="color:#27ae60;"><?= $stats['delivered'] ?? 0 ?></strong>
          </div>
        </div>
        <a href="/shop-php/order-history.php"
           style="display:block;text-align:center;margin-top:15px;padding:10px;background:#ee5022;color:#fff;border-radius:6px;text-decoration:none;font-size:14px;">
          Xem lịch sử đơn hàng
        </a>
      </div>
    </div>

    <!-- Form cập nhật -->
    <div>
      <!-- Thông tin cá nhân -->
      <div style="background:#fff;border-radius:8px;padding:25px;box-shadow:0 2px 8px rgba(0,0,0,0.05);margin-bottom:15px;">
        <h3 style="margin:0 0 20px;">Thông tin cá nhân</h3>
        <form method="POST">
          <?= Auth::csrfField() ?>
          <input type="hidden" name="action" value="update">
          <div style="margin-bottom:15px;">
            <label style="display:block;margin-bottom:6px;font-weight:500;font-size:14px;">Họ và tên *</label>
            <input type="text" name="name" required value="<?= htmlspecialchars($user['name']) ?>"
                   style="width:100%;padding:10px 12px;border:1px solid #ddd;border-radius:6px;font-size:14px;box-sizing:border-box;">
          </div>
          <div style="margin-bottom:15px;">
            <label style="display:block;margin-bottom:6px;font-weight:500;font-size:14px;">Email (không thể thay đổi)</label>
            <input type="email" value="<?= htmlspecialchars($user['email']) ?>" disabled
                   style="width:100%;padding:10px 12px;border:1px solid #ddd;border-radius:6px;font-size:14px;background:#f9f9f9;box-sizing:border-box;">
          </div>
          <div style="margin-bottom:20px;">
            <label style="display:block;margin-bottom:6px;font-weight:500;font-size:14px;">Số điện thoại</label>
            <input type="tel" name="phone" value="<?= htmlspecialchars($user['phoneNumber']??'') ?>"
                   placeholder="0912345678"
                   style="width:100%;padding:10px 12px;border:1px solid #ddd;border-radius:6px;font-size:14px;box-sizing:border-box;">
          </div>
          <button type="submit" style="padding:10px 24px;background:#ee5022;color:#fff;border:none;border-radius:6px;font-size:14px;cursor:pointer;">
            Lưu thay đổi
          </button>
        </form>
      </div>

      <!-- Đổi mật khẩu -->
      <div style="background:#fff;border-radius:8px;padding:25px;box-shadow:0 2px 8px rgba(0,0,0,0.05);">
        <h3 style="margin:0 0 20px;">Đổi mật khẩu</h3>
        <form method="POST">
          <?= Auth::csrfField() ?>
          <input type="hidden" name="action" value="change_password">
          <div style="margin-bottom:15px;">
            <label style="display:block;margin-bottom:6px;font-weight:500;font-size:14px;">Mật khẩu hiện tại</label>
            <input type="password" name="old_password" required
                   style="width:100%;padding:10px 12px;border:1px solid #ddd;border-radius:6px;font-size:14px;box-sizing:border-box;">
          </div>
          <div style="margin-bottom:15px;">
            <label style="display:block;margin-bottom:6px;font-weight:500;font-size:14px;">Mật khẩu mới</label>
            <input type="password" name="new_password" required minlength="6"
                   style="width:100%;padding:10px 12px;border:1px solid #ddd;border-radius:6px;font-size:14px;box-sizing:border-box;">
          </div>
          <div style="margin-bottom:20px;">
            <label style="display:block;margin-bottom:6px;font-weight:500;font-size:14px;">Xác nhận mật khẩu mới</label>
            <input type="password" name="confirm_password" required
                   style="width:100%;padding:10px 12px;border:1px solid #ddd;border-radius:6px;font-size:14px;box-sizing:border-box;">
          </div>
          <button type="submit" style="padding:10px 24px;background:#333;color:#fff;border:none;border-radius:6px;font-size:14px;cursor:pointer;">
            Đổi mật khẩu
          </button>
        </form>
      </div>
    </div>
  </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
