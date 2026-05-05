<?php
require_once __DIR__ . '/core/auth.php';
Auth::requireLogin('/shop-php/login.php');

$userId      = Auth::user()['id'];
$currentPage = max(1, intval($_GET['page'] ?? 1));
$perPage     = 10;
$offset      = ($currentPage - 1) * $perPage;

$cartItems = DB::query(
    "SELECT c.*, s.value as skuValue, s.price, s.stock, s.image as skuImage,
            p.name as productName, p.images as productImages, p.id as productId
     FROM cart c
     JOIN product_skus s ON c.sku_id = s.id
     JOIN products p ON s.product_id = p.id
     WHERE c.user_id = ? AND p.deletedAt IS NULL
     ORDER BY c.updatedAt DESC LIMIT ? OFFSET ?",
    [$userId, $perPage, $offset]
);

$totalItems = DB::queryOne(
    "SELECT COUNT(*) as total FROM cart c
     JOIN product_skus s ON c.sku_id = s.id
     JOIN products p ON s.product_id = p.id
     WHERE c.user_id = ? AND p.deletedAt IS NULL",
    [$userId]
)['total'] ?? 0;

$totalPages = $totalItems > 0 ? (int)ceil($totalItems / $perPage) : 0;

function formatVN(float $n): string { return number_format($n,0,',','.') . ' VNĐ'; }

$pageTitle = 'Giỏ hàng - PIXCAM';
require_once __DIR__ . '/includes/header.php';
?>
<link rel="stylesheet" href="/shop-php/assets/css/Cart2.css">

<div class="wrapper">
  <div class="cinema-wrapper">
    <div class="cinema-card">
      <h2>Giỏ hàng của bạn</h2>
      <div class="filter-info">
        <p>Hiển thị <?= count($cartItems) ?> của <?= $totalItems ?> sản phẩm</p>
      </div>

      <?php if (empty($cartItems)): ?>
      <div class="empty-cart">
        <i class="fas fa-shopping-cart empty-icon"></i>
        <p>Giỏ hàng của bạn đang trống.</p>
        <a href="/shop-php/products/sale.php" class="btn-shopping">Tiếp tục mua sắm</a>
      </div>
      <?php else: ?>

      <div id="bulkDeleteBar" style="display:none;margin-bottom:20px;">
        <button class="btn-create" onclick="openDeleteModal()" style="background:#dc3545;">
          <i class="fas fa-trash"></i> Xóa <span id="selectedCount">0</span> sản phẩm đã chọn
        </button>
      </div>

      <div class="cart-content">
        <div class="cart-left">
          <div class="table-container">
            <table class="cinema-table">
              <thead>
                <tr>
                  <th><input type="checkbox" id="selectAll" onchange="toggleSelectAll(this)"></th>
                  <th>Sản phẩm</th><th>Phân loại</th><th>Giá</th>
                  <th>Số lượng</th><th>Tạm tính</th><th>Hành động</th>
                </tr>
              </thead>
              <tbody id="cartTableBody">
                <?php foreach ($cartItems as $item):
                  $imgs    = json_decode($item['productImages'] ?? '[]', true);
                  $imgUrl  = $item['skuImage'] ?: ($imgs[0] ?? '');
                ?>
                <tr id="row-<?= $item['id'] ?>">
                  <td>
                    <input type="checkbox" class="item-checkbox"
                           value="<?= $item['id'] ?>"
                           data-price="<?= $item['price'] ?>"
                           data-qty="<?= $item['quantity'] ?>"
                           data-sku="<?= $item['sku_id'] ?>"
                           data-name="<?= htmlspecialchars($item['productName']) ?>"
                           data-sku-value="<?= htmlspecialchars($item['skuValue']) ?>"
                           data-img="<?= htmlspecialchars($imgUrl) ?>"
                           data-product-id="<?= $item['productId'] ?>"
                           onchange="onCheckboxChange()">
                  </td>
                  <td>
                    <div class="cart-product">
                      <img src="<?= htmlspecialchars($imgUrl) ?>" class="cart-thumb"
                           alt="<?= htmlspecialchars($item['productName']) ?>"
                           onerror="this.src='https://via.placeholder.com/60x60/e0e0e0/666?text=No+Image'">
                      <span class="cart-name"><?= htmlspecialchars($item['productName']) ?></span>
                    </div>
                  </td>
                  <td><?= htmlspecialchars($item['skuValue']) ?></td>
                  <td><?= formatVN($item['price']) ?></td>
                  <td>
                    <div class="qty-form">
                      <button onclick="updateQty(<?= $item['id'] ?>, <?= $item['sku_id'] ?>, <?= $item['quantity']-1 ?>)"
                              <?= $item['quantity']<=1?'disabled':'' ?>>−</button>
                      <span id="qty-<?= $item['id'] ?>"><?= $item['quantity'] ?></span>
                      <button onclick="updateQty(<?= $item['id'] ?>, <?= $item['sku_id'] ?>, <?= $item['quantity']+1 ?>)"
                              <?= $item['quantity']>=$item['stock']?'disabled':'' ?>>+</button>
                    </div>
                  </td>
                  <td id="subtotal-<?= $item['id'] ?>"><?= formatVN($item['price'] * $item['quantity']) ?></td>
                  <td>
                    <button class="btn-delete" onclick="openDeleteSingle(<?= $item['id'] ?>)">
                      <i class="fas fa-trash"></i>
                    </button>
                  </td>
                </tr>
                <?php endforeach; ?>
              </tbody>
            </table>
          </div>
        </div>

        <div class="cart-right">
          <h3>Tổng giỏ hàng</h3>
          <div class="summary-line">
            <span>Sản phẩm đã chọn: <span id="summaryCount">0</span></span>
          </div>
          <div class="summary-line">
            <span>Tạm tính</span>
            <span id="summarySubtotal">0 VNĐ</span>
          </div>
          <div class="summary-line total">
            <span>Tổng cộng</span>
            <span id="summaryTotal">0 VNĐ</span>
          </div>
          <button class="btn-payment disabled" id="checkoutBtn" onclick="proceedToPayment()" disabled>
            Chọn sản phẩm để thanh toán
          </button>
        </div>
      </div>

      <?php if ($totalPages > 1): ?>
      <div class="pagination">
        <?php if ($currentPage > 1): ?>
          <a href="?page=<?= $currentPage-1 ?>" class="page-btn"><i class="fas fa-angle-left"></i></a>
        <?php else: ?>
          <span class="page-btn disabled"><i class="fas fa-angle-left"></i></span>
        <?php endif; ?>
        <?php for ($p=1;$p<=$totalPages;$p++): ?>
          <?php if ($p===$currentPage): ?><span class="page-btn current"><?= $p ?></span>
          <?php elseif ($p===1||$p===$totalPages||abs($p-$currentPage)<=2): ?><a href="?page=<?= $p ?>" class="page-btn"><?= $p ?></a>
          <?php elseif (abs($p-$currentPage)===3): ?><span class="page-btn disabled">...</span>
          <?php endif; ?>
        <?php endfor; ?>
        <?php if ($currentPage < $totalPages): ?>
          <a href="?page=<?= $currentPage+1 ?>" class="page-btn"><i class="fas fa-angle-right"></i></a>
        <?php else: ?>
          <span class="page-btn disabled"><i class="fas fa-angle-right"></i></span>
        <?php endif; ?>
      </div>
      <?php endif; ?>
      <?php endif; ?>
    </div>
  </div>
</div>

<!-- Modal xóa -->
<div id="deleteModal" style="display:none;position:fixed;inset:0;background:rgba(0,0,0,0.5);z-index:9999;align-items:center;justify-content:center;">
  <div style="background:#fff;border-radius:8px;padding:30px;max-width:400px;width:90%;text-align:center;">
    <h3 style="margin-bottom:10px;">Xác nhận xóa</h3>
    <p id="deleteMsg" style="color:#666;margin-bottom:20px;"></p>
    <div style="display:flex;gap:10px;justify-content:center;">
      <button onclick="closeDeleteModal()" style="padding:10px 24px;border:1px solid #ccc;border-radius:6px;background:#fff;cursor:pointer;">Hủy</button>
      <button onclick="confirmDelete()" id="confirmBtn" style="padding:10px 24px;border:none;border-radius:6px;background:#dc3545;color:#fff;cursor:pointer;">Xóa</button>
    </div>
  </div>
</div>

<script>
let itemsToDelete = [];

function formatVN(n) { return new Intl.NumberFormat('vi-VN').format(n) + ' VNĐ'; }

function recalcSummary() {
    const checked = document.querySelectorAll('.item-checkbox:checked');
    let total = 0;
    checked.forEach(cb => { total += parseFloat(cb.dataset.price) * parseFloat(cb.dataset.qty); });
    const count = checked.length;
    document.getElementById('summaryCount').textContent    = count;
    document.getElementById('summarySubtotal').textContent = formatVN(total);
    document.getElementById('summaryTotal').textContent    = formatVN(total);
    document.getElementById('selectedCount').textContent   = count;
    const btn = document.getElementById('checkoutBtn');
    btn.disabled = count === 0;
    btn.textContent = count === 0 ? 'Chọn sản phẩm để thanh toán' : 'Tiến hành thanh toán';
    btn.classList.toggle('disabled', count === 0);
    document.getElementById('bulkDeleteBar').style.display = count > 0 ? 'block' : 'none';
}

function toggleSelectAll(el) {
    document.querySelectorAll('.item-checkbox').forEach(cb => cb.checked = el.checked);
    recalcSummary();
}
function onCheckboxChange() {
    const all  = document.querySelectorAll('.item-checkbox');
    const done = document.querySelectorAll('.item-checkbox:checked');
    document.getElementById('selectAll').checked = all.length === done.length;
    recalcSummary();
}

async function updateQty(cartId, skuId, newQty) {
    if (newQty < 1) return;
    const res  = await fetch('/shop-php/api/cart-update.php', {
        method:'POST', headers:csrfHeaders({'Content-Type':'application/json'}),
        body: JSON.stringify({cartId, skuId, quantity: newQty})
    });
    const data = await res.json();
    if (!data.error) {
        document.getElementById(`qty-${cartId}`).textContent = newQty;
        const cb    = document.querySelector(`.item-checkbox[value="${cartId}"]`);
        const price = cb ? parseFloat(cb.dataset.price) : 0;
        document.getElementById(`subtotal-${cartId}`).textContent = formatVN(price * newQty);
        if (cb) cb.dataset.qty = newQty;
        const row    = document.getElementById(`row-${cartId}`);
        const btns   = row?.querySelectorAll('.qty-form button');
        if (btns) {
            btns[0].setAttribute('onclick', `updateQty(${cartId},${skuId},${newQty-1})`);
            btns[0].disabled = newQty <= 1;
            btns[1].setAttribute('onclick', `updateQty(${cartId},${skuId},${newQty+1})`);
        }
        recalcSummary();
    } else alert(data.message || 'Cập nhật thất bại');
}

function openDeleteModal() {
    itemsToDelete = Array.from(document.querySelectorAll('.item-checkbox:checked')).map(cb => parseInt(cb.value));
    if (!itemsToDelete.length) return;
    document.getElementById('deleteMsg').textContent = `Xóa ${itemsToDelete.length} sản phẩm khỏi giỏ hàng?`;
    document.getElementById('deleteModal').style.display = 'flex';
}
function openDeleteSingle(id) {
    itemsToDelete = [id];
    document.getElementById('deleteMsg').textContent = 'Xóa sản phẩm này khỏi giỏ hàng?';
    document.getElementById('deleteModal').style.display = 'flex';
}
function closeDeleteModal() {
    document.getElementById('deleteModal').style.display = 'none';
    itemsToDelete = [];
}
async function confirmDelete() {
    const btn = document.getElementById('confirmBtn');
    btn.disabled = true; btn.textContent = 'Đang xóa...';
    const res  = await fetch('/shop-php/api/cart-delete.php', {
        method:'POST', headers:csrfHeaders({'Content-Type':'application/json'}),
        body: JSON.stringify({ids: itemsToDelete})
    });
    const data = await res.json();
    if (!data.error) {
        itemsToDelete.forEach(id => document.getElementById(`row-${id}`)?.remove());
        closeDeleteModal(); recalcSummary();
        if (!document.querySelectorAll('#cartTableBody tr').length) location.reload();
    } else alert(data.message || 'Xóa thất bại');
    btn.disabled = false; btn.textContent = 'Xóa';
}

function proceedToPayment() {
    const checked = document.querySelectorAll('.item-checkbox:checked');
    if (!checked.length) { alert('Vui lòng chọn sản phẩm!'); return; }
    const items = Array.from(checked).map(cb => ({
        id: parseInt(cb.value),
        quantity: parseInt(cb.dataset.qty),
        sku: { id: parseInt(cb.dataset.sku), value: cb.dataset.skuValue, price: parseFloat(cb.dataset.price) },
        product: { id: parseInt(cb.dataset.productId), name: cb.dataset.name, images: [cb.dataset.img] }
    }));
    sessionStorage.setItem('selectedCartItems', JSON.stringify(items));
    const ids = Array.from(checked).map(cb => cb.value).join(',');
    window.location.href = `/shop-php/pay.php?items=${ids}`;
}

document.addEventListener('DOMContentLoaded', recalcSummary);
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
