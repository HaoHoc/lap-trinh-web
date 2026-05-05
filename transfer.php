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
$subtotal = 0;
foreach ($items as $it) $subtotal += $it['skuPrice'] * $it['quantity'];
$total = $subtotal + 30000;

function fmt(float $n): string { return number_format($n,0,',','.') . ' ₫'; }

$bankName        = 'VPBank';
$bankAccount     = '150920067979';
$bankOwner       = 'NGUYEN VI THANH';
$transferContent = 'PIXCAM' . $orderId;

$pageTitle = 'Chuyển khoản - PIXCAM';
require_once __DIR__ . '/includes/header.php';
?>

<div style="max-width:600px;margin:30px auto;padding:0 20px;">

  <div style="background:#fff;border-radius:8px;padding:25px;margin-bottom:15px;box-shadow:0 2px 8px rgba(0,0,0,0.05);text-align:center;">
    <div style="font-size:50px;margin-bottom:10px;">🏦</div>
    <h2 style="color:#333;margin-bottom:5px;">Chuyển khoản ngân hàng</h2>
    <p style="color:#999;font-size:14px;">Vui lòng chuyển khoản để hoàn tất đơn hàng</p>
  </div>

  <div style="background:#fff;border-radius:8px;padding:25px;margin-bottom:15px;box-shadow:0 2px 8px rgba(0,0,0,0.05);">
    <h3 style="margin-bottom:20px;color:#333;">Thông tin chuyển khoản</h3>
    <div style="display:flex;flex-direction:column;gap:12px;">
      <div style="display:flex;justify-content:space-between;align-items:center;padding:12px;background:#f9f9f9;border-radius:6px;">
        <span style="color:#666;font-size:14px;">Ngân hàng</span>
        <strong style="color:#ee5022;"><?= $bankName ?></strong>
      </div>
      <div style="display:flex;justify-content:space-between;align-items:center;padding:12px;background:#f9f9f9;border-radius:6px;">
        <span style="color:#666;font-size:14px;">Số tài khoản</span>
        <div style="display:flex;align-items:center;gap:8px;">
          <strong><?= $bankAccount ?></strong>
          <button onclick="copyText('<?= $bankAccount ?>', this)"
                  style="background:#ee5022;color:#fff;border:none;padding:4px 10px;border-radius:4px;cursor:pointer;font-size:12px;">Copy</button>
        </div>
      </div>
      <div style="display:flex;justify-content:space-between;align-items:center;padding:12px;background:#f9f9f9;border-radius:6px;">
        <span style="color:#666;font-size:14px;">Chủ tài khoản</span>
        <strong><?= $bankOwner ?></strong>
      </div>
      <div style="display:flex;justify-content:space-between;align-items:center;padding:12px;background:#fff3cd;border-radius:6px;border:1px solid #ffc107;">
        <span style="color:#666;font-size:14px;">Số tiền</span>
        <strong style="color:#ee5022;font-size:18px;"><?= fmt($total) ?></strong>
      </div>
      <div style="display:flex;justify-content:space-between;align-items:center;padding:12px;background:#d1e7dd;border-radius:6px;border:1px solid #28a745;">
        <span style="color:#666;font-size:14px;">Nội dung CK <span style="color:red;">*</span></span>
        <div style="display:flex;align-items:center;gap:8px;">
          <strong style="color:#0f5132;"><?= $transferContent ?></strong>
          <button onclick="copyText('<?= $transferContent ?>', this)"
                  style="background:#28a745;color:#fff;border:none;padding:4px 10px;border-radius:4px;cursor:pointer;font-size:12px;">Copy</button>
        </div>
      </div>
    </div>
    <p style="color:#dc3545;font-size:13px;margin-top:15px;">
      ⚠️ Nhập đúng nội dung <strong><?= $transferContent ?></strong> để đơn hàng được xác nhận!
    </p>
  </div>

  <div style="background:#fff;border-radius:8px;padding:25px;margin-bottom:15px;box-shadow:0 2px 8px rgba(0,0,0,0.05);text-align:center;">
    <h3 style="margin-bottom:15px;color:#333;">Quét mã QR để chuyển khoản</h3>
    <img src="/shop-php/assets/img/qr-vpbank.jpg" alt="QR VPBank"
         style="width:250px;height:250px;object-fit:contain;border:1px solid #eee;border-radius:8px;padding:10px;"
         onerror="this.style.display='none';document.getElementById('noQR').style.display='block';">
    <div id="noQR" style="display:none;padding:20px;color:#999;">Ảnh QR không tìm thấy</div>
    <p style="color:#999;font-size:13px;margin-top:10px;">Mở app ngân hàng → Quét QR → Nhập số tiền và nội dung CK</p>
  </div>

  <div style="background:#fff;border-radius:8px;padding:20px;margin-bottom:15px;box-shadow:0 2px 8px rgba(0,0,0,0.05);text-align:center;">
    <p style="color:#856404;font-weight:500;background:#fff3cd;padding:12px;border-radius:6px;">
      ⏳ Đơn hàng sẽ được xác nhận trong 5-15 phút sau khi chuyển khoản thành công
    </p>
  </div>

  <div style="display:flex;gap:10px;">
    <a href="/shop-php/order-history.php"
       style="flex:1;display:block;text-align:center;padding:13px;background:#ee5022;color:#fff;border-radius:6px;text-decoration:none;font-weight:600;">
      Xem lịch sử đơn hàng
    </a>
    <a href="/shop-php/index.php"
       style="flex:1;display:block;text-align:center;padding:13px;background:#fff;color:#333;border:1px solid #ddd;border-radius:6px;text-decoration:none;">
      Tiếp tục mua sắm
    </a>
  </div>
</div>

<script>
function copyText(text, btn) {
    navigator.clipboard.writeText(text).then(() => {
        const orig = btn.textContent;
        btn.textContent = '✅ Đã copy';
        setTimeout(() => btn.textContent = orig, 2000);
    });
}
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>