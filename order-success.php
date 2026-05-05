<?php
require_once __DIR__ . '/core/auth.php';
Auth::requireLogin('/shop-php/login.php');

$orderId = intval($_GET['order'] ?? 0);
$order = DB::queryOne(
    "SELECT o.*, p.method as paymentMethod, p.status as paymentStatus
     FROM orders o JOIN payments p ON o.payment_id = p.id
     WHERE o.id = ? AND o.user_id = ?",
    [$orderId, Auth::user()['id']]
);

if (!$order) { header('Location: /shop-php/index.php'); exit; }

$items = DB::query("SELECT * FROM order_items WHERE order_id = ?", [$orderId]);

$total = 0;
foreach ($items as $it) $total += $it['skuPrice'] * $it['quantity'];
$total += 30000;

function fmt(float $n): string { return number_format($n,0,',','.') . ' ₫'; }

$pageTitle = 'Đặt hàng thành công - PIXCAM';
require_once __DIR__ . '/includes/header.php';
?>

<div style="max-width:800px;margin:40px auto;padding:40px;background:#fff;border-radius:8px;text-align:center;box-shadow:0 2px 8px rgba(0,0,0,0.05);">
  <div style="font-size:80px;color:#28a745;margin-bottom:20px;">✓</div>
  <h1 style="color:#28a745;margin-bottom:10px;">Đặt hàng thành công!</h1>
  <p style="color:#666;margin-bottom:30px;">Cảm ơn bạn đã mua sắm tại PIXCAM. Đơn hàng <strong>#<?= $order['id'] ?></strong> đã được tiếp nhận.</p>

  <div style="text-align:left;background:#f9f9f9;padding:20px;border-radius:8px;margin-bottom:20px;">
    <h3 style="margin-bottom:15px;">Thông tin đơn hàng</h3>
    <p><strong>Mã đơn hàng:</strong> #<?= $order['id'] ?></p>
    <p><strong>Người nhận:</strong> <?= htmlspecialchars($order['receiver_name']) ?></p>
    <p><strong>Số điện thoại:</strong> <?= htmlspecialchars($order['receiver_phone']) ?></p>
    <p><strong>Địa chỉ:</strong> <?= htmlspecialchars($order['receiver_address']) ?></p>
    <p><strong>Phương thức:</strong> <?= $order['paymentMethod']==='COD'?'💵 Thanh toán khi nhận hàng':'🏦 Chuyển khoản' ?></p>
    <p><strong>Tổng tiền:</strong> <span style="color:#ee5022;font-size:18px;font-weight:bold;"><?= fmt($total) ?></span></p>
  </div>

  <div style="text-align:left;background:#f9f9f9;padding:20px;border-radius:8px;margin-bottom:30px;">
    <h3 style="margin-bottom:15px;">Sản phẩm đã đặt</h3>
    <?php foreach ($items as $it): ?>
    <div style="display:flex;gap:12px;padding:10px 0;border-bottom:1px solid #eee;">
      <img src="<?= htmlspecialchars($it['image']) ?>" style="width:60px;height:60px;object-fit:cover;border-radius:4px;"
           onerror="this.src='https://via.placeholder.com/60'">
      <div style="flex:1;">
        <strong><?= htmlspecialchars($it['productName']) ?></strong>
        <p style="color:#999;font-size:13px;margin:4px 0;"><?= htmlspecialchars($it['skuValue']) ?> × <?= $it['quantity'] ?></p>
      </div>
      <p style="color:#ee5022;font-weight:600;"><?= fmt($it['skuPrice']*$it['quantity']) ?></p>
    </div>
    <?php endforeach; ?>
  </div>

  <div style="display:flex;gap:10px;justify-content:center;">
    <a href="/shop-php/order-history.php" style="padding:12px 24px;background:#ee5022;color:#fff;text-decoration:none;border-radius:6px;">Xem lịch sử đơn hàng</a>
    <a href="/shop-php/index.php" style="padding:12px 24px;background:#fff;color:#333;border:1px solid #ddd;text-decoration:none;border-radius:6px;">Tiếp tục mua sắm</a>
  </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>