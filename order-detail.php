<?php
require_once __DIR__ . '/core/auth.php';
Auth::requireLogin('/shop-php/login.php');

$orderId = intval($_GET['id'] ?? 0);
$order = DB::queryOne(
    "SELECT o.*, p.method as paymentMethod, p.status as paymentStatus
     FROM orders o JOIN payments p ON o.payment_id = p.id
     WHERE o.id = ? AND o.user_id = ?",
    [$orderId, Auth::user()['id']]
);

if (!$order) { header('Location: /shop-php/order-history.php'); exit; }

$items = DB::query("SELECT * FROM order_items WHERE order_id = ?", [$orderId]);
$subtotal = 0;
foreach ($items as $it) $subtotal += $it['skuPrice'] * $it['quantity'];
$total = $subtotal + 30000;

function fmt(float $n): string { return number_format($n,0,',','.') . ' ₫'; }

$statusLabel = [
    'PENDING_PAYMENT'  => ['label'=>'Chờ thanh toán', 'color'=>'#f39c12'],
    'PENDING_PICKUP'   => ['label'=>'Chờ lấy hàng',   'color'=>'#3498db'],
    'PENDING_DELIVERY' => ['label'=>'Đang giao',       'color'=>'#9b59b6'],
    'DELIVERED'        => ['label'=>'Đã giao',         'color'=>'#27ae60'],
    'RETURNED'         => ['label'=>'Đã trả hàng',     'color'=>'#e74c3c'],
    'CANCELLED'        => ['label'=>'Đã hủy',          'color'=>'#95a5a6'],
];
$st = $statusLabel[$order['status']] ?? ['label'=>$order['status'],'color'=>'#999'];

$pageTitle = 'Chi tiết đơn hàng #' . $orderId . ' - PIXCAM';
require_once __DIR__ . '/includes/header.php';
?>

<div style="max-width:900px;margin:30px auto;padding:0 20px;">
  <div style="display:flex;align-items:center;gap:15px;margin-bottom:20px;">
    <a href="/shop-php/order-history.php" style="color:#ee5022;text-decoration:none;">← Lịch sử đơn hàng</a>
    <h2 style="margin:0;">Đơn hàng #<?= $orderId ?></h2>
    <span style="padding:5px 12px;border-radius:20px;font-size:13px;font-weight:600;color:#fff;background:<?= $st['color'] ?>;"><?= $st['label'] ?></span>
  </div>

  <div style="display:grid;grid-template-columns:1fr 350px;gap:20px;">
    <div>
      <!-- Sản phẩm -->
      <div style="background:#fff;border-radius:8px;padding:20px;margin-bottom:15px;box-shadow:0 2px 8px rgba(0,0,0,0.05);">
        <h3 style="margin-bottom:15px;">Sản phẩm đã đặt</h3>
        <?php foreach ($items as $it): ?>
        <div style="display:flex;gap:12px;padding:12px 0;border-bottom:1px solid #f0f0f0;">
          <img src="<?= htmlspecialchars($it['image']) ?>"
               style="width:70px;height:70px;object-fit:cover;border-radius:4px;"
               onerror="this.src='https://via.placeholder.com/70'">
          <div style="flex:1;">
            <strong><?= htmlspecialchars($it['productName']) ?></strong>
            <p style="color:#999;font-size:13px;margin:4px 0;"><?= htmlspecialchars($it['skuValue']) ?></p>
            <p style="font-size:13px;margin:0;">Số lượng: <?= $it['quantity'] ?> × <?= fmt($it['skuPrice']) ?></p>
          </div>
          <strong style="color:#ee5022;"><?= fmt($it['skuPrice']*$it['quantity']) ?></strong>
        </div>
        <?php endforeach; ?>
        <div style="margin-top:15px;text-align:right;">
          <p style="font-size:14px;color:#666;">Tạm tính: <?= fmt($subtotal) ?></p>
          <p style="font-size:14px;color:#666;">Phí vận chuyển: <?= fmt(30000) ?></p>
          <p style="font-size:20px;font-weight:700;color:#ee5022;">Tổng: <?= fmt($total) ?></p>
        </div>
      </div>
    </div>

    <!-- Thông tin giao hàng -->
    <div>
      <div style="background:#fff;border-radius:8px;padding:20px;box-shadow:0 2px 8px rgba(0,0,0,0.05);">
        <h3 style="margin-bottom:15px;">Thông tin nhận hàng</h3>
        <p><strong>Người nhận:</strong> <?= htmlspecialchars($order['receiver_name']) ?></p>
        <p><strong>SĐT:</strong> <?= htmlspecialchars($order['receiver_phone']) ?></p>
        <p><strong>Email:</strong> <?= htmlspecialchars($order['receiver_email'] ?? '') ?></p>
        <p><strong>Địa chỉ:</strong> <?= htmlspecialchars($order['receiver_address']) ?></p>
        <?php if (!empty($order['receiver_note'])): ?>
        <p><strong>Ghi chú:</strong> <?= htmlspecialchars($order['receiver_note']) ?></p>
        <?php endif; ?>
        <hr style="margin:15px 0;">
        <p><strong>Thanh toán:</strong> <?= $order['paymentMethod']==='COD'?'💵 COD':'🏦 Chuyển khoản' ?></p>
        <p><strong>Ngày đặt:</strong> <?= date('d/m/Y H:i', strtotime($order['createdAt'])) ?></p>
      </div>
    </div>
  </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>