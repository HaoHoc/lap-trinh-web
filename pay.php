<?php
require_once __DIR__ . '/core/auth.php';
Auth::requireLogin('/shop-php/login.php');

$user    = Auth::user();
$cartIds = array_filter(array_map('intval', explode(',', $_GET['items'] ?? '')));
if (empty($cartIds)) { header('Location: /shop-php/cart.php'); exit; }

$placeholders = implode(',', array_fill(0, count($cartIds), '?'));
$items = DB::query(
    "SELECT c.*, s.value as skuValue, s.price, s.stock, s.image as skuImage,
            p.name as productName, p.images as productImages, p.id as productId
     FROM cart c
     JOIN product_skus s ON c.sku_id = s.id
     JOIN products p ON s.product_id = p.id
     WHERE c.id IN ($placeholders) AND c.user_id = ? AND p.deletedAt IS NULL",
    array_merge($cartIds, [$user['id']])
);

if (empty($items)) { header('Location: /shop-php/cart.php'); exit; }

// Lấy danh sách địa chỉ đã lưu
$savedAddresses = DB::query(
    "SELECT * FROM addresses WHERE user_id = ? ORDER BY is_default DESC, id DESC",
    [$user['id']]
);

$subtotal = 0;
foreach ($items as $it) $subtotal += $it['price'] * $it['quantity'];
$shippingFee = 30000;
$total       = $subtotal + $shippingFee;

function fmt(float $n): string { return number_format($n,0,',','.') . ' ₫'; }

$pageTitle = 'Thanh toán - PIXCAM';
require_once __DIR__ . '/includes/header.php';
?>

<div class="pay-wrapper">
  <div class="pay-container">
    <div class="pay-left">
      <h2>Thông tin nhận hàng</h2>

      <!-- Địa chỉ đã lưu -->
      <?php if (!empty($savedAddresses)): ?>
      <div style="margin-bottom:20px;">
        <label style="font-weight:600;font-size:14px;display:block;margin-bottom:10px;">
          📍 Địa chỉ đã lưu
        </label>
        <div style="display:flex;flex-direction:column;gap:8px;">
          <?php foreach ($savedAddresses as $addr): ?>
          <label style="display:flex;gap:10px;padding:12px;border:2px solid <?= $addr['is_default']?'#ee5022':'#eee' ?>;border-radius:8px;cursor:pointer;transition:border 0.2s;"
                 onclick="fillAddress(<?= htmlspecialchars(json_encode($addr), ENT_QUOTES) ?>)">
            <input type="radio" name="saved_address" value="<?= $addr['id'] ?>"
                   <?= $addr['is_default']?'checked':'' ?> style="margin-top:3px;">
            <div>
              <strong><?= htmlspecialchars($addr['name']) ?></strong>
              <span style="color:#999;margin-left:8px;font-size:13px;"><?= htmlspecialchars($addr['phone']) ?></span>
              <?php if ($addr['is_default']): ?>
              <span style="margin-left:8px;background:#ee5022;color:#fff;font-size:11px;padding:2px 6px;border-radius:10px;">Mặc định</span>
              <?php endif; ?>
              <p style="color:#666;font-size:13px;margin:4px 0 0;"><?= htmlspecialchars($addr['address']) ?></p>
            </div>
          </label>
          <?php endforeach; ?>
          <label style="display:flex;gap:10px;padding:12px;border:2px solid #eee;border-radius:8px;cursor:pointer;"
                 onclick="clearAddress()">
            <input type="radio" name="saved_address" value="new" style="margin-top:3px;">
            <span style="color:#ee5022;font-weight:500;">+ Nhập địa chỉ mới</span>
          </label>
        </div>
      </div>
      <?php endif; ?>

      <form id="checkoutForm">
        <div class="form-row"><label>Họ và tên <span style="color:red;">*</span></label>
          <input type="text" id="receiver_name" name="receiver_name" required
                 value="<?= htmlspecialchars($savedAddresses[0]['name'] ?? $user['name'] ?? '') ?>"></div>
        <div class="form-row"><label>Số điện thoại <span style="color:red;">*</span></label>
          <input type="tel" id="receiver_phone" name="receiver_phone" required
                 value="<?= htmlspecialchars($savedAddresses[0]['phone'] ?? $user['phoneNumber'] ?? '') ?>"
                 placeholder="0912345678"></div>
        <div class="form-row"><label>Email</label>
          <input type="email" name="receiver_email" value="<?= htmlspecialchars($user['email'] ?? '') ?>"></div>
        <div class="form-row"><label>Địa chỉ nhận hàng <span style="color:red;">*</span></label>
          <input type="text" id="receiver_address" name="receiver_address" required
                 value="<?= htmlspecialchars($savedAddresses[0]['address'] ?? '') ?>"
                 placeholder="Số nhà, đường, phường, quận, thành phố"></div>
        <div class="form-row"><label>Ghi chú (tùy chọn)</label>
          <textarea name="receiver_note" rows="2" placeholder="Ví dụ: Giao giờ hành chính..."></textarea></div>

        <!-- Lưu địa chỉ -->
        <div style="display:flex;align-items:center;gap:8px;margin-bottom:15px;">
          <input type="checkbox" id="saveAddress" name="save_address" value="1">
          <label for="saveAddress" style="font-size:14px;cursor:pointer;">Lưu địa chỉ này để dùng lần sau</label>
        </div>
        <div id="defaultAddressBox" style="display:none;margin-bottom:15px;padding-left:24px;">
          <input type="checkbox" id="isDefault" name="is_default" value="1">
          <label for="isDefault" style="font-size:13px;color:#666;cursor:pointer;">Đặt làm địa chỉ mặc định</label>
        </div>

        <h3 style="margin-top:20px;">Phương thức thanh toán</h3>
        <div class="payment-methods">
          <label class="payment-option">
            <input type="radio" name="payment_method" value="COD" checked>
            <div class="payment-info"><strong>💵 Thanh toán khi nhận hàng (COD)</strong><p>Trả tiền mặt khi shipper giao hàng</p></div>
          </label>
          <label class="payment-option">
            <input type="radio" name="payment_method" value="BANK">
            <div class="payment-info"><strong>🏦 Chuyển khoản ngân hàng</strong><p>Chuyển khoản qua QR code</p></div>
          </label>
        </div>
      </form>
    </div>

    <div class="pay-right">
      <h3>Đơn hàng của bạn</h3>
      <div class="order-items">
        <?php foreach ($items as $it):
          $imgs = json_decode($it['productImages']??'[]', true);
          $img  = $it['skuImage'] ?: ($imgs[0]??'');
        ?>
        <div class="order-item">
          <img src="<?= htmlspecialchars($img) ?>" onerror="this.src='https://via.placeholder.com/60x60/e0e0e0/666?text=No+Image'">
          <div class="order-item-info">
            <p class="order-item-name"><?= htmlspecialchars($it['productName']) ?></p>
            <p class="order-item-meta"><?= htmlspecialchars($it['skuValue']) ?> × <?= $it['quantity'] ?></p>
          </div>
          <p class="order-item-price"><?= fmt($it['price']*$it['quantity']) ?></p>
        </div>
        <?php endforeach; ?>
      </div>
      <div class="summary-lines">
        <div class="summary-row"><span>Tạm tính</span><span><?= fmt($subtotal) ?></span></div>
        <div class="summary-row"><span>Phí vận chuyển</span><span><?= fmt($shippingFee) ?></span></div>
        <div class="summary-row total"><span>Tổng cộng</span><span><?= fmt($total) ?></span></div>
      </div>
      <button type="button" class="btn-place-order" id="placeOrderBtn" onclick="placeOrder()">
        <i class="fas fa-check-circle"></i> Đặt hàng
      </button>
      <a href="/shop-php/cart.php" class="btn-back-cart">← Quay lại giỏ hàng</a>
    </div>
  </div>
</div>

<style>
.pay-wrapper { max-width:1200px; margin:30px auto; padding:0 20px; }
.pay-container { display:grid; grid-template-columns:1fr 400px; gap:30px; }
.pay-left, .pay-right { background:#fff; border-radius:8px; padding:25px; box-shadow:0 2px 8px rgba(0,0,0,0.05); }
.pay-left h2, .pay-left h3 { margin-bottom:15px; color:#333; }
.form-row { margin-bottom:15px; }
.form-row label { display:block; margin-bottom:6px; font-weight:500; font-size:14px; }
.form-row input, .form-row textarea { width:100%; padding:10px 12px; border:1px solid #ddd; border-radius:6px; font-size:14px; font-family:inherit; box-sizing:border-box; }
.form-row input:focus, .form-row textarea:focus { outline:none; border-color:#ee5022; }
.payment-methods { display:flex; flex-direction:column; gap:10px; }
.payment-option { display:flex; gap:12px; padding:15px; border:2px solid #eee; border-radius:8px; cursor:pointer; transition:all 0.2s; }
.payment-option:has(input:checked) { border-color:#ee5022; background:#fff5f0; }
.payment-option input { margin-top:4px; }
.payment-info strong { display:block; margin-bottom:4px; }
.payment-info p { font-size:13px; color:#666; margin:0; }
.pay-right h3 { margin-bottom:15px; color:#333; }
.order-items { max-height:300px; overflow-y:auto; margin-bottom:15px; }
.order-item { display:flex; gap:10px; padding:10px 0; border-bottom:1px solid #f0f0f0; }
.order-item img { width:50px; height:50px; object-fit:cover; border-radius:4px; }
.order-item-info { flex:1; min-width:0; }
.order-item-name { font-size:13px; font-weight:500; margin:0 0 4px 0; overflow:hidden; text-overflow:ellipsis; white-space:nowrap; }
.order-item-meta { font-size:12px; color:#999; margin:0; }
.order-item-price { font-size:13px; font-weight:600; color:#ee5022; white-space:nowrap; }
.summary-lines { padding:15px 0; border-top:1px solid #eee; border-bottom:1px solid #eee; margin-bottom:20px; }
.summary-row { display:flex; justify-content:space-between; margin-bottom:8px; font-size:14px; }
.summary-row.total { font-size:18px; font-weight:700; color:#ee5022; margin-top:10px; padding-top:10px; border-top:1px solid #eee; }
.btn-place-order { width:100%; padding:14px; background:#ee5022; color:#fff; border:none; border-radius:6px; font-size:16px; font-weight:600; cursor:pointer; display:flex; align-items:center; justify-content:center; gap:8px; }
.btn-place-order:hover { background:#d9451b; }
.btn-place-order:disabled { background:#ccc; cursor:not-allowed; }
.btn-back-cart { display:block; text-align:center; margin-top:12px; color:#666; text-decoration:none; font-size:14px; }
.btn-back-cart:hover { color:#ee5022; }
@media (max-width:900px) { .pay-container { grid-template-columns:1fr; } }
</style>

<script>
// Checkbox lưu địa chỉ
document.getElementById('saveAddress')?.addEventListener('change', function() {
    document.getElementById('defaultAddressBox').style.display = this.checked ? 'block' : 'none';
});

// Điền thông tin từ địa chỉ đã lưu
function fillAddress(addr) {
    document.getElementById('receiver_name').value    = addr.name    || '';
    document.getElementById('receiver_phone').value   = addr.phone   || '';
    document.getElementById('receiver_address').value = addr.address || '';
    // Uncheck "lưu địa chỉ" vì đang dùng địa chỉ cũ
    document.getElementById('saveAddress').checked = false;
    document.getElementById('defaultAddressBox').style.display = 'none';
}

function clearAddress() {
    document.getElementById('receiver_name').value    = '';
    document.getElementById('receiver_phone').value   = '';
    document.getElementById('receiver_address').value = '';
}

async function placeOrder() {
    const form    = document.getElementById('checkoutForm');
    const data    = new FormData(form);
    const name    = data.get('receiver_name').trim();
    const phone   = data.get('receiver_phone').trim();
    const address = data.get('receiver_address').trim();

    if (!name || !phone || !address) { alert('Vui lòng nhập đầy đủ thông tin bắt buộc!'); return; }

    const btn = document.getElementById('placeOrderBtn');
    btn.disabled = true; btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Đang xử lý...';

    const res = await fetch('/shop-php/api/order-create.php', {
        method:'POST', headers:csrfHeaders({'Content-Type':'application/json'}),
        body: JSON.stringify({
            cartIds: <?= json_encode(array_column($items, 'id')) ?>,
            receiver_name: name, receiver_phone: phone,
            receiver_email: data.get('receiver_email'),
            receiver_address: address,
            receiver_note: data.get('receiver_note'),
            payment_method: data.get('payment_method'),
            save_address: data.get('save_address') === '1',
            is_default: data.get('is_default') === '1',
        })
    });
    const result = await res.json();

    if (!result.error) {
        if (data.get('payment_method') === 'BANK') window.location.href = '/shop-php/transfer.php?order='+result.orderId;
        else window.location.href = '/shop-php/order-success.php?order='+result.orderId;
    } else {
        alert(result.message || 'Đặt hàng thất bại');
        btn.disabled = false; btn.innerHTML = '<i class="fas fa-check-circle"></i> Đặt hàng';
    }
}
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
