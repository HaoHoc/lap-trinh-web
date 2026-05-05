<?php
require_once __DIR__ . '/../core/auth.php';
Auth::requireAdmin();

$currentPage = max(1, intval($_GET['page'] ?? 1));
$perPage     = 15;
$offset      = ($currentPage - 1) * $perPage;

$users = DB::query(
    "SELECT u.*, r.name as role_name FROM users u
     JOIN roles r ON u.role_id = r.id
     ORDER BY u.createdAt DESC LIMIT ? OFFSET ?",
    [$perPage, $offset]
);
$total      = DB::queryOne("SELECT COUNT(*) as t FROM users")['t'] ?? 0;
$totalPages = $total > 0 ? (int)ceil($total/$perPage) : 0;
$roles      = DB::query("SELECT * FROM roles ORDER BY id");

$pageTitle = 'Quản lý tài khoản - Admin';
require_once __DIR__ . '/../includes/header.php';
?>

<link rel="stylesheet" href="/shop-php/assets/css/adminProducts.css">
<link rel="stylesheet" href="/shop-php/assets/css/modal.css">

<div class="wrapper">
  <main>
    <h2>Quản lý tài khoản</h2>
    <div class="filter-info"><p>Tổng <?= $total ?> tài khoản</p></div>

    <div class="table-container">
      <table class="cinema-table">
        <thead>
          <tr><th>ID</th><th>Avatar</th><th>Họ tên</th><th>Email</th><th>SĐT</th><th>Vai trò</th><th>Trạng thái</th><th>Ngày tạo</th><th>Hành động</th></tr>
        </thead>
        <tbody>
          <?php foreach ($users as $u): ?>
          <tr id="urow-<?= $u['id'] ?>">
            <td><?= $u['id'] ?></td>
            <td>
              <?php if (!empty($u['avatar'])): ?>
                <img src="<?= htmlspecialchars($u['avatar']) ?>" style="width:36px;height:36px;border-radius:50%;object-fit:cover;">
              <?php else: ?>
                <i class="fas fa-user-circle" style="font-size:36px;color:#ccc;"></i>
              <?php endif; ?>
            </td>
            <td><?= htmlspecialchars($u['name']) ?></td>
            <td style="font-size:13px;"><?= htmlspecialchars($u['email']) ?></td>
            <td style="font-size:13px;"><?= htmlspecialchars($u['phoneNumber']??'-') ?></td>
            <td>
              <span style="padding:3px 8px;border-radius:12px;font-size:12px;color:#fff;background:<?= $u['role_name']==='ADMIN'?'#e74c3c':($u['role_name']==='SELLER'?'#f39c12':'#3498db') ?>;">
                <?= $u['role_name'] ?>
              </span>
            </td>
            <td>
              <span style="padding:3px 8px;border-radius:12px;font-size:12px;color:#fff;background:<?= $u['status']==='ACTIVE'?'#27ae60':'#95a5a6' ?>;">
                <?= $u['status']==='ACTIVE'?'Hoạt động':'Vô hiệu' ?>
              </span>
            </td>
            <td style="font-size:12px;"><?= date('d/m/Y', strtotime($u['createdAt'])) ?></td>
            <td>
              <div class="action-buttons">
                <button class="btn-edit" onclick='openEditUser(<?= htmlspecialchars(json_encode($u), ENT_QUOTES) ?>)'>
                  <i class="fas fa-edit"></i>
                </button>
                <?php if ($u['id'] != Auth::user()['id']): ?>
                <button class="btn-delete" onclick="openDeleteUser(<?= $u['id'] ?>, '<?= htmlspecialchars(addslashes($u['name'])) ?>')">
                  <i class="fas fa-trash"></i>
                </button>
                <?php endif; ?>
              </div>
            </td>
          </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>

    <?php if ($totalPages > 1): ?>
    <nav class="admin-pagination">
      <?php for ($p=1;$p<=$totalPages;$p++): ?>
        <?php if ($p===$currentPage): ?><span class="admin-page-link admin-current"><?= $p ?></span>
        <?php elseif ($p===1||$p===$totalPages||abs($p-$currentPage)<=2): ?><a href="?page=<?= $p ?>" class="admin-page-link"><?= $p ?></a>
        <?php elseif (abs($p-$currentPage)===3): ?><span class="admin-page-link admin-disabled">...</span>
        <?php endif; ?>
      <?php endfor; ?>
    </nav>
    <?php endif; ?>
  </main>
</div>

<!-- Modal Sửa user -->
<div id="editUserModal" class="modal-overlay" style="display:none;" onclick="closeModals()">
  <div class="modal-content" onclick="event.stopPropagation()" style="max-width:450px;">
    <div class="modal-header"><h3>Chỉnh sửa tài khoản</h3><button class="close-btn" onclick="closeModals()">×</button></div>
    <div class="modal-body">
      <input type="hidden" id="eu-id">
      <div class="form-group"><label>Họ tên</label><input type="text" id="eu-name"></div>
      <div class="form-group"><label>Số điện thoại</label><input type="tel" id="eu-phone"></div>
      <div class="form-group">
        <label>Vai trò</label>
        <select id="eu-role">
          <?php foreach ($roles as $r): ?>
          <option value="<?= $r['id'] ?>"><?= $r['name'] ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="form-group">
        <label>Trạng thái</label>
        <select id="eu-status">
          <option value="ACTIVE">Hoạt động</option>
          <option value="INACTIVE">Vô hiệu hóa</option>
        </select>
      </div>
      <div class="form-group">
        <label>Mật khẩu mới (để trống = không đổi)</label>
        <input type="password" id="eu-password" placeholder="Nhập mật khẩu mới nếu muốn đổi">
      </div>
    </div>
    <div class="modal-footer">
      <button class="btn-cancel" onclick="closeModals()">Hủy</button>
      <button class="btn-save" onclick="submitEditUser()" id="editUserBtn"><i class="fas fa-save"></i> Lưu</button>
    </div>
  </div>
</div>

<!-- Modal Xóa -->
<div id="deleteUserModal" class="modal-overlay" style="display:none;" onclick="closeModals()">
  <div class="modal-content" onclick="event.stopPropagation()" style="max-width:400px;text-align:center;">
    <div class="modal-header"><h3>Xác nhận xóa</h3><button class="close-btn" onclick="closeModals()">×</button></div>
    <div class="modal-body">
      <p>Xóa tài khoản <strong id="delete-uname"></strong>?</p>
      <p style="color:#dc3545;font-size:13px;">Tất cả dữ liệu liên quan sẽ bị xóa!</p>
    </div>
    <div class="modal-footer" style="justify-content:center;gap:10px;">
      <button class="btn-cancel" onclick="closeModals()">Hủy</button>
      <button id="deleteUserBtn" onclick="submitDeleteUser()" style="background:#dc3545;color:#fff;border:none;padding:8px 20px;border-radius:4px;cursor:pointer;">
        <i class="fas fa-trash"></i> Xóa
      </button>
    </div>
  </div>
</div>

<div id="toast" style="display:none;position:fixed;bottom:20px;right:20px;padding:12px 20px;border-radius:6px;color:#fff;font-size:14px;z-index:99999;"></div>

<script>
let currentUserId = null;
function showToast(msg, type='success') {
    const t=document.getElementById('toast');
    t.textContent=msg; t.style.background=type==='success'?'#28a745':'#dc3545';
    t.style.display='block'; setTimeout(()=>t.style.display='none',3000);
}
function closeModals() {
    ['editUserModal','deleteUserModal'].forEach(id=>document.getElementById(id).style.display='none');
    currentUserId=null;
}
function openEditUser(u) {
    currentUserId=u.id;
    document.getElementById('eu-id').value=u.id;
    document.getElementById('eu-name').value=u.name||'';
    document.getElementById('eu-phone').value=u.phoneNumber||'';
    document.getElementById('eu-role').value=u.role_id||2;
    document.getElementById('eu-status').value=u.status||'ACTIVE';
    document.getElementById('eu-password').value='';
    document.getElementById('editUserModal').style.display='flex';
}
async function submitEditUser() {
    const btn=document.getElementById('editUserBtn');
    btn.disabled=true; btn.innerHTML='<i class="fas fa-spinner fa-spin"></i>';
    const payload={
        id: parseInt(document.getElementById('eu-id').value),
        name: document.getElementById('eu-name').value.trim(),
        phone: document.getElementById('eu-phone').value.trim(),
        role_id: parseInt(document.getElementById('eu-role').value),
        status: document.getElementById('eu-status').value,
        password: document.getElementById('eu-password').value,
    };
    const res=await fetch('/shop-php/api/admin/user-update.php',{
        method:'POST',headers:csrfHeaders({'Content-Type':'application/json'}),body:JSON.stringify(payload)
    });
    const data=await res.json();
    if (!data.error) { showToast('Cập nhật thành công!'); closeModals(); setTimeout(()=>location.reload(),1000); }
    else showToast(data.message||'Thất bại','error');
    btn.disabled=false; btn.innerHTML='<i class="fas fa-save"></i> Lưu';
}
function openDeleteUser(id, name) {
    currentUserId=id;
    document.getElementById('delete-uname').textContent=name;
    document.getElementById('deleteUserModal').style.display='flex';
}
async function submitDeleteUser() {
    const btn=document.getElementById('deleteUserBtn');
    btn.disabled=true; btn.textContent='Đang xóa...';
    const res=await fetch('/shop-php/api/admin/user-delete.php',{
        method:'POST',headers:csrfHeaders({'Content-Type':'application/json'}),body:JSON.stringify({id:currentUserId})
    });
    const data=await res.json();
    if (!data.error) { showToast('Đã xóa!'); document.getElementById('urow-'+currentUserId)?.remove(); closeModals(); }
    else showToast(data.message||'Thất bại','error');
    btn.disabled=false; btn.innerHTML='<i class="fas fa-trash"></i> Xóa';
}
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
