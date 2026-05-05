<?php
require_once __DIR__ . '/../core/auth.php';
Auth::requireAdmin();

$success = $error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!Auth::verifyCsrf()) $error = 'Invalid form session.';
    $title   = trim($_POST['title']   ?? '');
    $content = trim($_POST['content'] ?? '');
    $type    = $_POST['type'] ?? 'INFO';
    
    if ($error) {
        // CSRF failure already populated the message.
    } elseif (!$title || !$content) {
        $error = 'Vui lòng nhập đầy đủ thông tin!';
    } else {
        // Gửi thông báo đến tất cả user
        $users = DB::query("SELECT id FROM users WHERE status='ACTIVE'");
        foreach ($users as $u) {
            DB::execute(
                "INSERT INTO notifications (user_id, title, content, type) VALUES (?,?,?,?)",
                [$u['id'], $title, $content, $type]
            );
        }
        $success = 'Đã gửi thông báo đến ' . count($users) . ' người dùng!';
    }
}

// Lịch sử thông báo gần đây
$recentNotifs = DB::query(
    "SELECT n.title, n.content, n.type, n.createdAt
     FROM notifications n
     GROUP BY n.title, n.content, n.type, n.createdAt
     ORDER BY n.createdAt DESC LIMIT 10"
);

$pageTitle = 'Thông báo hệ thống - Admin';
require_once __DIR__ . '/../includes/header.php';
?>

<link rel="stylesheet" href="/shop-php/assets/css/adminProducts.css">
<div class="wrapper"><main>
  <h2>📢 Gửi thông báo hệ thống</h2>

  <?php if ($error): ?>
    <div style="padding:12px;background:#fdecea;color:#c0392b;border-radius:6px;margin-bottom:15px;"><?= htmlspecialchars($error) ?></div>
  <?php endif; ?>
  <?php if ($success): ?>
    <div style="padding:12px;background:#eafaf1;color:#27ae60;border-radius:6px;margin-bottom:15px;"><?= htmlspecialchars($success) ?></div>
  <?php endif; ?>

  <div style="display:grid;grid-template-columns:1fr 1fr;gap:20px;">
    <!-- Form gửi thông báo -->
    <div style="background:#fff;padding:25px;border-radius:8px;box-shadow:0 2px 8px rgba(0,0,0,0.05);">
      <h3 style="margin-bottom:20px;">Tạo thông báo mới</h3>
      <form method="POST">
        <?= Auth::csrfField() ?>
        <div style="margin-bottom:15px;">
          <label style="display:block;margin-bottom:6px;font-weight:500;font-size:14px;">Loại thông báo</label>
          <select name="type" style="width:100%;padding:10px;border:1px solid #ddd;border-radius:6px;font-size:14px;">
            <option value="INFO">ℹ️ Thông tin</option>
            <option value="PROMO">🎉 Khuyến mãi</option>
            <option value="SYSTEM">⚙️ Hệ thống</option>
            <option value="WARNING">⚠️ Cảnh báo</option>
          </select>
        </div>
        <div style="margin-bottom:15px;">
          <label style="display:block;margin-bottom:6px;font-weight:500;font-size:14px;">Tiêu đề *</label>
          <input type="text" name="title" placeholder="Ví dụ: Khuyến mãi cuối tuần 30%..."
                 style="width:100%;padding:10px;border:1px solid #ddd;border-radius:6px;font-size:14px;box-sizing:border-box;">
        </div>
        <div style="margin-bottom:20px;">
          <label style="display:block;margin-bottom:6px;font-weight:500;font-size:14px;">Nội dung *</label>
          <textarea name="content" rows="5" placeholder="Nội dung thông báo..."
                    style="width:100%;padding:10px;border:1px solid #ddd;border-radius:6px;font-size:14px;box-sizing:border-box;font-family:inherit;resize:vertical;"></textarea>
        </div>
        <button type="submit" style="width:100%;padding:12px;background:#ee5022;color:#fff;border:none;border-radius:6px;font-size:15px;font-weight:600;cursor:pointer;">
          📢 Gửi đến tất cả người dùng
        </button>
      </form>
    </div>

    <!-- Lịch sử -->
    <div style="background:#fff;padding:25px;border-radius:8px;box-shadow:0 2px 8px rgba(0,0,0,0.05);">
      <h3 style="margin-bottom:15px;">Lịch sử thông báo</h3>
      <?php if (empty($recentNotifs)): ?>
        <p style="color:#999;text-align:center;padding:20px;">Chưa có thông báo nào</p>
      <?php else: ?>
        <?php foreach ($recentNotifs as $n): ?>
        <div style="padding:12px 0;border-bottom:1px solid #f0f0f0;">
          <div style="display:flex;justify-content:space-between;margin-bottom:4px;">
            <strong style="font-size:14px;"><?= htmlspecialchars($n['title']) ?></strong>
            <span style="font-size:12px;color:#999;"><?= date('d/m/Y H:i', strtotime($n['createdAt'])) ?></span>
          </div>
          <p style="font-size:13px;color:#666;margin:0;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;">
            <?= htmlspecialchars($n['content']) ?>
          </p>
        </div>
        <?php endforeach; ?>
      <?php endif; ?>
    </div>
  </div>
</main></div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
