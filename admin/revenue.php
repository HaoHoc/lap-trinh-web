<?php
require_once __DIR__ . '/../core/auth.php';
Auth::requireAdmin();

// Thống kê tổng quan
$stats = DB::queryOne(
    "SELECT 
        COUNT(*) as totalOrders,
        SUM(oi.skuPrice * oi.quantity) as totalRevenue,
        COUNT(DISTINCT o.user_id) as totalCustomers
     FROM orders o
     JOIN order_items oi ON o.id = oi.order_id
     WHERE o.status NOT IN ('CANCELLED','RETURNED')"
);

// Doanh thu theo tháng (6 tháng gần nhất)
$monthlyRevenue = DB::query(
    "SELECT 
        DATE_FORMAT(o.createdAt, '%m/%Y') as month,
        DATE_FORMAT(o.createdAt, '%Y-%m') as monthKey,
        SUM(oi.skuPrice * oi.quantity) as revenue,
        COUNT(DISTINCT o.id) as orders
     FROM orders o
     JOIN order_items oi ON o.id = oi.order_id
     WHERE o.status NOT IN ('CANCELLED','RETURNED')
       AND o.createdAt >= DATE_SUB(NOW(), INTERVAL 6 MONTH)
     GROUP BY DATE_FORMAT(o.createdAt, '%Y-%m'), DATE_FORMAT(o.createdAt, '%m/%Y')
     ORDER BY monthKey ASC"
);

// Top sản phẩm bán chạy
$topProducts = DB::query(
    "SELECT oi.productName, SUM(oi.quantity) as totalSold, SUM(oi.skuPrice * oi.quantity) as revenue
     FROM order_items oi
     JOIN orders o ON oi.order_id = o.id
     WHERE o.status NOT IN ('CANCELLED','RETURNED')
     GROUP BY oi.productName
     ORDER BY totalSold DESC LIMIT 5"
);

// Đơn hàng gần đây
$recentOrders = DB::query(
    "SELECT o.id, o.status, o.createdAt, u.name as userName,
            SUM(oi.skuPrice * oi.quantity) as total
     FROM orders o
     JOIN users u ON o.user_id = u.id
     JOIN order_items oi ON o.id = oi.order_id
     GROUP BY o.id, o.status, o.createdAt, u.name ORDER BY o.createdAt DESC LIMIT 10"
);

function fmt(float $n): string { return number_format($n,0,',','.') . ' ₫'; }

$pageTitle = 'Báo cáo doanh thu - Admin';
require_once __DIR__ . '/../includes/header.php';
?>

<link rel="stylesheet" href="/shop-php/assets/css/adminProducts.css">
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

<div class="wrapper">
  <main>
    <h2>📊 Báo cáo doanh thu</h2>

    <!-- Thống kê tổng quan -->
    <div style="display:grid;grid-template-columns:repeat(3,1fr);gap:20px;margin-bottom:30px;">
      <div style="background:#fff;padding:25px;border-radius:8px;box-shadow:0 2px 8px rgba(0,0,0,0.05);text-align:center;border-top:4px solid #ee5022;">
        <p style="color:#999;font-size:14px;margin-bottom:8px;">Tổng doanh thu</p>
        <h2 style="color:#ee5022;font-size:28px;"><?= fmt($stats['totalRevenue'] ?? 0) ?></h2>
      </div>
      <div style="background:#fff;padding:25px;border-radius:8px;box-shadow:0 2px 8px rgba(0,0,0,0.05);text-align:center;border-top:4px solid #28a745;">
        <p style="color:#999;font-size:14px;margin-bottom:8px;">Tổng đơn hàng</p>
        <h2 style="color:#28a745;font-size:28px;"><?= $stats['totalOrders'] ?? 0 ?></h2>
      </div>
      <div style="background:#fff;padding:25px;border-radius:8px;box-shadow:0 2px 8px rgba(0,0,0,0.05);text-align:center;border-top:4px solid #3498db;">
        <p style="color:#999;font-size:14px;margin-bottom:8px;">Khách hàng</p>
        <h2 style="color:#3498db;font-size:28px;"><?= $stats['totalCustomers'] ?? 0 ?></h2>
      </div>
    </div>

    <!-- Biểu đồ doanh thu -->
    <div style="background:#fff;padding:25px;border-radius:8px;box-shadow:0 2px 8px rgba(0,0,0,0.05);margin-bottom:20px;">
      <h3 style="margin-bottom:20px;">Doanh thu 6 tháng gần nhất</h3>
      <canvas id="revenueChart" height="100"></canvas>
    </div>

    <div style="display:grid;grid-template-columns:1fr 1fr;gap:20px;">
      <!-- Top sản phẩm -->
      <div style="background:#fff;padding:25px;border-radius:8px;box-shadow:0 2px 8px rgba(0,0,0,0.05);">
        <h3 style="margin-bottom:15px;">🏆 Top sản phẩm bán chạy</h3>
        <?php foreach ($topProducts as $i => $p): ?>
        <div style="display:flex;align-items:center;gap:12px;padding:10px 0;border-bottom:1px solid #f0f0f0;">
          <span style="width:24px;height:24px;border-radius:50%;background:<?= $i===0?'#ee5022':($i===1?'#f39c12':'#3498db') ?>;color:#fff;display:flex;align-items:center;justify-content:center;font-size:12px;font-weight:700;flex-shrink:0;"><?= $i+1 ?></span>
          <div style="flex:1;">
            <p style="font-size:14px;font-weight:500;margin:0;"><?= htmlspecialchars($p['productName']) ?></p>
            <p style="font-size:12px;color:#999;margin:2px 0 0;">Đã bán: <?= $p['totalSold'] ?> sản phẩm</p>
          </div>
          <strong style="color:#ee5022;font-size:13px;"><?= fmt($p['revenue']) ?></strong>
        </div>
        <?php endforeach; ?>
        <?php if (empty($topProducts)): ?>
        <p style="color:#999;text-align:center;padding:20px;">Chưa có dữ liệu</p>
        <?php endif; ?>
      </div>

      <!-- Đơn hàng gần đây -->
      <div style="background:#fff;padding:25px;border-radius:8px;box-shadow:0 2px 8px rgba(0,0,0,0.05);">
        <h3 style="margin-bottom:15px;">🕐 Đơn hàng gần đây</h3>
        <?php 
        $statusColors = ['PENDING_PAYMENT'=>'#f39c12','PENDING_PICKUP'=>'#3498db','PENDING_DELIVERY'=>'#9b59b6','DELIVERED'=>'#27ae60','RETURNED'=>'#e74c3c','CANCELLED'=>'#95a5a6'];
        $statusLabels = ['PENDING_PAYMENT'=>'Chờ TT','PENDING_PICKUP'=>'Chờ lấy','PENDING_DELIVERY'=>'Đang giao','DELIVERED'=>'Đã giao','RETURNED'=>'Trả hàng','CANCELLED'=>'Đã hủy'];
        foreach ($recentOrders as $o): 
          $color = $statusColors[$o['status']] ?? '#999';
          $label = $statusLabels[$o['status']] ?? $o['status'];
        ?>
        <div style="display:flex;align-items:center;gap:10px;padding:8px 0;border-bottom:1px solid #f0f0f0;">
          <span style="font-size:13px;color:#999;width:30px;">#<?= $o['id'] ?></span>
          <div style="flex:1;">
            <p style="font-size:13px;font-weight:500;margin:0;"><?= htmlspecialchars($o['userName']) ?></p>
            <p style="font-size:11px;color:#999;margin:0;"><?= date('d/m/Y', strtotime($o['createdAt'])) ?></p>
          </div>
          <span style="padding:3px 8px;border-radius:10px;font-size:11px;color:#fff;background:<?= $color ?>;"><?= $label ?></span>
          <strong style="font-size:13px;color:#ee5022;"><?= fmt($o['total']+30000) ?></strong>
        </div>
        <?php endforeach; ?>
        <?php if (empty($recentOrders)): ?>
        <p style="color:#999;text-align:center;padding:20px;">Chưa có đơn hàng</p>
        <?php endif; ?>
      </div>
    </div>
  </main>
</div>

<script>
const labels = <?= json_encode(array_column($monthlyRevenue, 'month')) ?>;
const data   = <?= json_encode(array_column($monthlyRevenue, 'revenue')) ?>;
new Chart(document.getElementById('revenueChart'), {
    type: 'bar',
    data: {
        labels: labels.length ? labels : ['Chưa có dữ liệu'],
        datasets: [{
            label: 'Doanh thu (₫)',
            data: data.length ? data : [0],
            backgroundColor: 'rgba(238,80,34,0.7)',
            borderColor: '#ee5022',
            borderWidth: 2,
            borderRadius: 6,
        }]
    },
    options: {
        responsive: true,
        plugins: { legend: { display: false } },
        scales: {
            y: { ticks: { callback: v => new Intl.NumberFormat('vi-VN').format(v) + ' ₫' } }
        }
    }
});
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>