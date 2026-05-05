<?php
require_once __DIR__ . '/core/auth.php';
Auth::requireLogin('/shop-php/login.php');

$userId = Auth::user()['id'];
$currentPage = max(1, intval($_GET['page'] ?? 1));
$perPage = 10;
$offset  = ($currentPage - 1) * $perPage;

$orders = DB::query(
    "SELECT o.*, p.method as paymentMethod, p.status as paymentStatus,
            COUNT(oi.id) as itemCount,
            SUM(oi.skuPrice * oi.quantity) as subtotal
     FROM orders o
     JOIN payments p ON o.payment_id = p.id
     LEFT JOIN order_items oi ON o.id = oi.order_id
     WHERE o.user_id = ?
     GROUP BY o.id ORDER BY o.createdAt DESC LIMIT ? OFFSET ?",
    [$userId, $perPage, $offset]
);

$total     = DB::queryOne("SELECT COUNT(*) as t FROM orders WHERE user_id=?", [$userId])['t'] ?? 0;
$totalPages = $total > 0 ? (int)ceil($total/$perPage) : 0;

$statusLabel = [
    'PENDING_PAYMENT'  => ['label'=>'Chờ thanh toán', 'color'=>'#f39c12'],
    'PENDING_PICKUP'   => ['label'=>'Chờ lấy hàng',   'color'=>'#3498db'],
    'PENDING_DELIVERY' => ['label'=>'Đang giao',       'color'=>'#9b59b6'],
    'DELIVERED'        => ['label'=>'Đã giao',         'color'=>'#27ae60'],
    'RETURNED'         => ['label'=>'Đã trả hàng',     'color'=>'#e74c3c'],
    'CANCELLED'        => ['label'=>'Đã hủy',          'color'=>'#95a5a6'],
];

function fmt(float $n): string { return number_format($n,0,',','.') . ' ₫'; }

$pageTitle = 'Lịch sử đơn hàng - PIXCAM';
require_once __DIR__ . '/includes/header.php';
?>

<div style="max-width:1000px;margin:30px auto;padding:0 20px;">
  <h2 style="margin-bottom:20px;">📦 Lịch sử đơn hàng</h2>

  <?php if (empty($orders)): ?>
  <div style="text-align:center;padding:60px;background:#fff;border-radius:8px;box-shadow:0 2px 8px rgba(0,0,0,0.05);">
    <div style="font-size:60px;margin-bottom:15px;">📭</div>
    <h3 style="color:#999;">Bạn chưa có đơn hàng nào</h3>
    <a href="/shop-php/index.php" style="display:inline-block;margin-top:15px;padding:10px 24px;background:#ee5022;color:#fff;border-radius:6px;text-decoration:none;">Bắt đầu mua sắm</a>
  </div>
  <?php else: ?>

  <?php foreach ($orders as $order):
    $st = $statusLabel[$order['status']] ?? ['label'=>$order['status'],'color'=>'#999'];
    $total = ($order['subtotal'] ?? 0) + 30000;
  ?>
  <div style="background:#fff;border-radius:8px;padding:20px;margin-bottom:15px;box-shadow:0 2px 8px rgba(0,0,0,0.05);">
    <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:15px;flex-wrap:wrap;gap:10px;">
      <div>
        <strong style="font-size:16px;">Đơn hàng #<?= $order['id'] ?></strong>
        <span style="margin-left:12px;font-size:13px;color:#999;"><?= date('d/m/Y H:i', strtotime($order['createdAt'])) ?></span>
      </div>
      <span style="padding:5px 12px;border-radius:20px;font-size:13px;font-weight:600;color:#fff;background:<?= $st['color'] ?>;">
        <?= $st['label'] ?>
      </span>
    </div>
    <div style="display:flex;justify-content:space-between;align-items:center;flex-wrap:wrap;gap:10px;">
      <div style="font-size:14px;color:#666;">
        <span><?= $order['itemCount'] ?> sản phẩm</span>
        <span style="margin:0 8px;">•</span>
        <span><?= $order['paymentMethod']==='COD'?'💵 COD':'🏦 Chuyển khoản' ?></span>
        <span style="margin:0 8px;">•</span>
        <span>Địa chỉ: <?= htmlspecialchars($order['receiver_address']) ?></span>
      </div>
      <div style="display:flex;align-items:center;gap:15px;">
        <strong style="color:#ee5022;font-size:18px;"><?= fmt($total) ?></strong>
        <a href="/shop-php/order-detail.php?id=<?= $order['id'] ?>"
           style="padding:8px 16px;border:1px solid #ee5022;color:#ee5022;border-radius:6px;text-decoration:none;font-size:13px;">
          Xem chi tiết
        </a>
      </div>
    </div>
  </div>
  <?php endforeach; ?>

  <?php if ($totalPages > 1): ?>
  <div style="display:flex;justify-content:center;gap:8px;margin-top:20px;">
    <?php for ($p=1;$p<=$totalPages;$p++): ?>
      <?php if ($p===$currentPage): ?>
        <span style="padding:8px 14px;background:#ee5022;color:#fff;border-radius:4px;"><?= $p ?></span>
      <?php else: ?>
        <a href="?page=<?= $p ?>" style="padding:8px 14px;border:1px solid #ddd;border-radius:4px;text-decoration:none;color:#333;"><?= $p ?></a>
      <?php endif; ?>
    <?php endfor; ?>
  </div>
  <?php endif; ?>
  <?php endif; ?>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>