<?php
require_once __DIR__ . '/../core/auth.php';
Auth::requireAdmin();

$currentPage = max(1, intval($_GET['page'] ?? 1));
$perPage     = 15;
$offset      = ($currentPage - 1) * $perPage;
$filterStatus = $_GET['status'] ?? '';

$where  = $filterStatus ? "WHERE o.status = ?" : "";
$params = $filterStatus ? [$filterStatus] : [];

$orders = DB::query(
    "SELECT o.*, u.name as userName, u.email as userEmail,
            p.method as paymentMethod, p.status as paymentStatus,
            COUNT(oi.id) as itemCount,
            SUM(oi.skuPrice * oi.quantity) as subtotal
     FROM orders o
     JOIN users u ON o.user_id = u.id
     JOIN payments p ON o.payment_id = p.id
     LEFT JOIN order_items oi ON o.id = oi.order_id
     $where
     GROUP BY o.id ORDER BY o.createdAt DESC LIMIT ? OFFSET ?",
    array_merge($params, [$perPage, $offset])
);

$total      = DB::queryOne("SELECT COUNT(*) as t FROM orders o $where", $params)['t'] ?? 0;
$totalPages = $total > 0 ? (int)ceil($total/$perPage) : 0;

$statusList = [
    'PENDING_PAYMENT'  => ['label'=>'Chờ thanh toán', 'color'=>'#f39c12'],
    'PENDING_PICKUP'   => ['label'=>'Chờ lấy hàng',   'color'=>'#3498db'],
    'PENDING_DELIVERY' => ['label'=>'Đang giao',       'color'=>'#9b59b6'],
    'DELIVERED'        => ['label'=>'Đã giao',         'color'=>'#27ae60'],
    'RETURNED'         => ['label'=>'Đã trả hàng',     'color'=>'#e74c3c'],
    'CANCELLED'        => ['label'=>'Đã hủy',          'color'=>'#95a5a6'],
];

function fmt(float $n): string { return number_format($n,0,',','.') . ' ₫'; }

$pageTitle = 'Quản lý đơn hàng - Admin';
require_once __DIR__ . '/../includes/header.php';
?>

<link rel="stylesheet" href="/shop-php/assets/css/adminProducts.css">

<div class="wrapper">
  <main>
    <h2>Quản lý đơn hàng</h2>

    <!-- Filter -->
    <div style="display:flex;gap:8px;flex-wrap:wrap;margin-bottom:20px;">
      <a href="?" style="padding:7px 14px;border-radius:20px;text-decoration:none;font-size:13px;border:1px solid <?= !$filterStatus?'#ee5022':'#ddd' ?>;color:<?= !$filterStatus?'#fff':'#333' ?>;background:<?= !$filterStatus?'#ee5022':'#fff' ?>;">
        Tất cả (<?= $total ?>)
      </a>
      <?php foreach ($statusList as $key => $s): ?>
      <a href="?status=<?= $key ?>" style="padding:7px 14px;border-radius:20px;text-decoration:none;font-size:13px;border:1px solid <?= $filterStatus===$key?$s['color']:'#ddd' ?>;color:<?= $filterStatus===$key?'#fff':'#333' ?>;background:<?= $filterStatus===$key?$s['color']:'#fff' ?>;">
        <?= $s['label'] ?>
      </a>
      <?php endforeach; ?>
    </div>

    <div class="table-container">
      <table class="cinema-table">
        <thead>
          <tr>
            <th>#</th><th>Khách hàng</th><th>Địa chỉ</th>
            <th>Sản phẩm</th><th>Tổng tiền</th>
            <th>Thanh toán</th><th>Trạng thái</th>
            <th>Ngày đặt</th><th>Hành động</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($orders as $o):
            $st = $statusList[$o['status']] ?? ['label'=>$o['status'],'color'=>'#999'];
            $total_price = ($o['subtotal'] ?? 0) + 30000;
          ?>
          <tr>
            <td><?= $o['id'] ?></td>
            <td>
              <strong><?= htmlspecialchars($o['userName']) ?></strong><br>
              <small style="color:#999;"><?= htmlspecialchars($o['userEmail']) ?></small><br>
              <small><?= htmlspecialchars($o['receiver_phone']) ?></small>
            </td>
            <td style="max-width:150px;font-size:13px;"><?= htmlspecialchars($o['receiver_address']) ?></td>
            <td style="text-align:center;"><?= $o['itemCount'] ?> món</td>
            <td><strong style="color:#ee5022;"><?= fmt($total_price) ?></strong></td>
            <td><span style="font-size:12px;"><?= $o['paymentMethod']==='COD'?'💵 COD':'🏦 Bank' ?></span></td>
            <td>
              <span style="padding:4px 10px;border-radius:12px;font-size:12px;color:#fff;background:<?= $st['color'] ?>;">
                <?= $st['label'] ?>
              </span>
            </td>
            <td style="font-size:13px;white-space:nowrap;"><?= date('d/m/Y H:i', strtotime($o['createdAt'])) ?></td>
            <td>
              <div style="display:flex;gap:5px;flex-wrap:wrap;">
                <button onclick="openDetailModal(<?= $o['id'] ?>)"
                        style="padding:5px 10px;background:#17a2b8;color:#fff;border:none;border-radius:4px;cursor:pointer;font-size:12px;">
                  <i class="fas fa-eye"></i> Chi tiết
                </button>
                <select onchange="updateStatus(<?= $o['id'] ?>, this.value)"
                        style="padding:5px;border:1px solid #ddd;border-radius:4px;font-size:12px;cursor:pointer;">
                  <?php foreach ($statusList as $key => $s): ?>
                  <option value="<?= $key ?>" <?= $o['status']===$key?'selected':'' ?>><?= $s['label'] ?></option>
                  <?php endforeach; ?>
                </select>
              </div>
            </td>
          </tr>
          <?php endforeach; ?>
          <?php if (empty($orders)): ?>
          <tr><td colspan="9" style="text-align:center;padding:40px;color:#999;">Không có đơn hàng nào</td></tr>
          <?php endif; ?>
        </tbody>
      </table>
    </div>

    <!-- Phân trang -->
    <?php if ($totalPages > 1): ?>
    <nav class="admin-pagination">
      <?php for ($p=1;$p<=$totalPages;$p++): ?>
        <?php if ($p===$currentPage): ?>
          <span class="admin-page-link admin-current"><?= $p ?></span>
        <?php elseif ($p===1||$p===$totalPages||abs($p-$currentPage)<=2): ?>
          <a href="?status=<?= $filterStatus ?>&page=<?= $p ?>" class="admin-page-link"><?= $p ?></a>
        <?php elseif (abs($p-$currentPage)===3): ?>
          <span class="admin-page-link admin-disabled">...</span>
        <?php endif; ?>
      <?php endfor; ?>
    </nav>
    <?php endif; ?>
  </main>
</div>

<!-- Modal chi tiết đơn hàng -->
<div id="detailModal" style="display:none;position:fixed;inset:0;background:rgba(0,0,0,0.5);z-index:9999;align-items:center;justify-content:center;">
  <div style="background:#fff;border-radius:8px;padding:30px;max-width:600px;width:90%;max-height:80vh;overflow-y:auto;">
    <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:20px;">
      <h3 id="modalTitle" style="margin:0;">Chi tiết đơn hàng</h3>
      <button onclick="closeModal()" style="background:none;border:none;font-size:20px;cursor:pointer;">×</button>
    </div>
    <div id="modalContent">Đang tải...</div>
  </div>
</div>

<div id="toast" style="display:none;position:fixed;bottom:20px;right:20px;padding:12px 20px;border-radius:6px;color:#fff;font-size:14px;z-index:99999;"></div>

<script>
function showToast(msg, type='success') {
    const t=document.getElementById('toast');
    t.textContent=msg; t.style.background=type==='success'?'#28a745':'#dc3545';
    t.style.display='block'; setTimeout(()=>t.style.display='none',3000);
}
const esc = window.escapeHtml || (value => String(value ?? ''));

async function openDetailModal(id) {
    document.getElementById('modalTitle').textContent = 'Đơn hàng #' + id;
    document.getElementById('modalContent').innerHTML = '<p style="text-align:center;padding:20px;">Đang tải...</p>';
    document.getElementById('detailModal').style.display = 'flex';

    const res  = await fetch('/shop-php/api/admin/order-get.php?id=' + id);
    const data = await res.json();
    if (data.error) { document.getElementById('modalContent').innerHTML = '<p style="color:red;">'+esc(data.message)+'</p>'; return; }

    const o = data.order; const items = data.items;
    ['receiver_name','receiver_phone','receiver_address','receiver_note'].forEach(key => {
        o[key] = esc(o[key]);
    });
    items.forEach(it => {
        ['image','productName','skuValue','quantity'].forEach(key => {
            it[key] = esc(it[key]);
        });
    });
    const total = (data.subtotal || 0) + 30000;
    const fmt = n => new Intl.NumberFormat('vi-VN').format(n) + ' ₫';

    let html = `
      <p><strong>Người nhận:</strong> ${o.receiver_name}</p>
      <p><strong>SĐT:</strong> ${o.receiver_phone}</p>
      <p><strong>Địa chỉ:</strong> ${o.receiver_address}</p>
      <p><strong>Ghi chú:</strong> ${o.receiver_note||'Không có'}</p>
      <p><strong>Thanh toán:</strong> ${o.paymentMethod==='COD'?'💵 COD':'🏦 Chuyển khoản'}</p>
      <hr style="margin:15px 0;">
      <h4>Sản phẩm:</h4>`;
    items.forEach(it => {
        html += `<div style="display:flex;gap:10px;padding:10px 0;border-bottom:1px solid #f0f0f0;">
          <img src="${it.image}" style="width:50px;height:50px;object-fit:cover;border-radius:4px;"
               onerror="this.src='https://via.placeholder.com/50'">
          <div style="flex:1;">
            <strong>${it.productName}</strong>
            <p style="color:#999;font-size:13px;margin:4px 0;">${it.skuValue} × ${it.quantity}</p>
          </div>
          <strong style="color:#ee5022;">${fmt(it.skuPrice*it.quantity)}</strong>
        </div>`;
    });
    html += `<div style="margin-top:15px;text-align:right;">
      <p>Tạm tính: ${fmt(data.subtotal||0)}</p>
      <p>Phí vận chuyển: ${fmt(30000)}</p>
      <p style="font-size:18px;font-weight:700;color:#ee5022;">Tổng: ${fmt(total)}</p>
    </div>`;
    document.getElementById('modalContent').innerHTML = html;
}

function closeModal() { document.getElementById('detailModal').style.display='none'; }

async function updateStatus(id, status) {
    const res  = await fetch('/shop-php/api/admin/order-update.php', {
        method:'POST', headers:csrfHeaders({'Content-Type':'application/json'}),
        body: JSON.stringify({id, status})
    });
    const data = await res.json();
    if (!data.error) showToast('Cập nhật trạng thái thành công!');
    else showToast(data.message||'Thất bại','error');
}
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
