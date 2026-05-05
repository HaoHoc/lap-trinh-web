<?php
require_once __DIR__ . '/../core/auth.php';
Auth::requireAdmin();
$pageTitle = 'Hỗ trợ chat - Admin';
require_once __DIR__ . '/../includes/header.php';
?>
<div class="wrapper"><main>
  <h2>💬 Hỗ trợ chat</h2>
  <div style="background:#fff;border-radius:8px;padding:40px;text-align:center;box-shadow:0 2px 8px rgba(0,0,0,0.05);">
    <div style="font-size:60px;margin-bottom:20px;">🚧</div>
    <h3 style="color:#666;">Tính năng đang phát triển</h3>
    <p style="color:#999;margin-top:10px;">Tính năng hỗ trợ chat trực tiếp sẽ được cập nhật trong phiên bản tới.</p>
    <p style="color:#999;font-size:14px;margin-top:10px;">Hiện tại bạn có thể liên hệ khách hàng qua email hoặc số điện thoại từ trang <a href="/shop-php/admin/accounts.php" style="color:#ee5022;">Quản lý tài khoản</a>.</p>
  </div>
</main></div>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>