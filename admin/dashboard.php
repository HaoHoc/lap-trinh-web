<?php
require_once __DIR__ . '/../core/auth.php';

// Chỉ Admin mới vào được (thay AdminRoute)
Auth::requireAdmin();

$pageTitle = 'Bảng điều khiển Admin - PIXCAM';
require_once __DIR__ . '/../includes/header.php';

// Danh sách cards (thay dashboardCards array)
$cards = [
    ['title' => 'Quản Lý Sản Phẩm',    'desc' => 'Thêm, sửa, xóa và quản lý kho sản phẩm',       'icon' => '📦', 'link' => '/shop-php/admin/products.php',  'color' => '#ee5022'],
    ['title' => 'Quản Lý Đơn Hàng',    'desc' => 'Theo dõi và xử lý các đơn hàng',                'icon' => '🛒', 'link' => '/shop-php/admin/orders.php',    'color' => '#28a745'],
    ['title' => 'Quản Lý Danh Mục',    'desc' => 'Tổ chức và phân loại sản phẩm',                 'icon' => '📂', 'link' => '/shop-php/admin/category.php',  'color' => '#17a2b8'],
    ['title' => 'Quản Lý Tài Khoản',   'desc' => 'Quản lý người dùng và phân quyền',              'icon' => '👥', 'link' => '/shop-php/admin/accounts.php',  'color' => '#6f42c1'],
    ['title' => 'Báo Cáo Doanh Thu',   'desc' => 'Thống kê và phân tích doanh thu',               'icon' => '📊', 'link' => '/shop-php/admin/revenue.php',   'color' => '#fd7e14'],
    ['title' => 'Hỗ Trợ Chat',         'desc' => 'Tương tác và hỗ trợ khách hàng',                'icon' => '💬', 'link' => '/shop-php/admin/chat.php',      'color' => '#e83e8c'],
    ['title' => 'Thông Báo Hệ Thống',  'desc' => 'Gửi thông báo đến toàn bộ người dùng',          'icon' => '📢', 'link' => '/shop-php/admin/broadcast.php', 'color' => '#20c997'],
];
?>

<link rel="stylesheet" href="/assets/css/adminDashboard.css">

<div class="admin-dashboard">

  <!-- Header -->
  <div class="dashboard-header">
    <div class="header-content">
      <h1 class="dashboard-title">
        <span class="title-icon">⚡</span>
        Bảng Điều Khiển Admin
      </h1>
      <p class="dashboard-subtitle">
        Chào mừng trở lại! Quản lý website PIXCAM của bạn
      </p>
    </div>
  </div>

  <!-- Dashboard Cards (thay dashboardCards.map) -->
  <div class="dashboard-content">
    <h2 class="content-title">Các Chức Năng Quản Lý</h2>
    <div class="dashboard-grid">
      <?php foreach ($cards as $card): ?>
      <a href="<?= htmlspecialchars($card['link']) ?>"
         class="dashboard-card"
         style="--card-color: <?= $card['color'] ?>;">
        <div class="card-header">
          <div class="card-icon"><?= $card['icon'] ?></div>
        </div>
        <div class="card-body">
          <h3 class="card-title"><?= htmlspecialchars($card['title']) ?></h3>
          <p class="card-description"><?= htmlspecialchars($card['desc']) ?></p>
        </div>
        <div class="card-footer">
          <span class="card-action">
            Truy cập ngay
            <span class="action-arrow">→</span>
          </span>
        </div>
      </a>
      <?php endforeach; ?>
    </div>
  </div>

</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>