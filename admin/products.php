<?php
// ============================================================
//  admin/products.php — Quản lý sản phẩm dùng MySQL trực tiếp
// ============================================================
require_once __DIR__ . '/../core/auth.php';
Auth::requireAdmin();

$currentPage     = max(1, intval($_GET['page'] ?? 1));
$productsPerPage = 12;
$offset          = ($currentPage - 1) * $productsPerPage;

$products   = DB::query(
    "SELECT * FROM products WHERE deletedAt IS NULL ORDER BY id ASC LIMIT ? OFFSET ?",
    [$productsPerPage, $offset]
);
$totalItems = DB::queryOne("SELECT COUNT(*) as total FROM products WHERE deletedAt IS NULL")['total'] ?? 0;
$totalPages = $totalItems > 0 ? (int)ceil($totalItems / $productsPerPage) : 0;
$allCategories = DB::query("SELECT * FROM categories ORDER BY parentCategoryId, id");

function formatDate(string $d): string { return date('d/m/Y H:i', strtotime($d)); }

$pageTitle = 'Quản lý sản phẩm - Admin';
require_once __DIR__ . '/../includes/header.php';
?>

<link rel="stylesheet" href="/shop-php/assets/css/adminProducts.css">
<link rel="stylesheet" href="/shop-php/assets/css/modal.css">

<div class="wrapper">
  <main>
    <h2>Danh sách sản phẩm</h2>
    <div class="action-bar">
      <button class="btn add-btn" onclick="openCreateModal()">
        <i class="fas fa-plus"></i> Thêm sản phẩm
      </button>
    </div>
    <div class="filter-info">
      <p>Hiển thị <?= count($products) ?> của <?= $totalItems ?> sản phẩm</p>
    </div>
    <div class="table-container">
      <table class="cinema-table">
        <thead>
          <tr><th>ID</th><th>Hình ảnh</th><th>Tên sản phẩm</th><th>Giá bán</th><th>Giá ảo</th><th>Ngày tạo</th><th>Hành động</th></tr>
        </thead>
        <tbody>
          <?php foreach ($products as $p):
            $images = json_decode($p['images'] ?? '[]', true);
            $img    = $images[0] ?? '';
          ?>
          <tr id="row-<?= $p['id'] ?>">
            <td><?= $p['id'] ?></td>
            <td>
              <?php if ($img): ?>
                <img src="<?= htmlspecialchars($img) ?>" style="width:50px;height:50px;object-fit:cover;border-radius:4px;">
              <?php else: ?>
                <span style="color:#ccc;font-size:12px;">Chưa có ảnh</span>
              <?php endif; ?>
            </td>
            <td><?= htmlspecialchars($p['name']) ?></td>
            <td><?= number_format($p['basePrice'], 0, ',', '.') ?> ₫</td>
            <td><?= number_format($p['virtualPrice'], 0, ',', '.') ?> ₫</td>
            <td><?= formatDate($p['createdAt']) ?></td>
            <td>
              <div class="action-buttons">
                <a href="/shop-php/products/detail.php?id=<?= $p['id'] ?>"
                   style="background:#17a2b8;color:white;border:none;padding:5px 8px;border-radius:3px;display:inline-flex;align-items:center;text-decoration:none;" title="Xem">
                  <i class="fas fa-eye"></i>
                </a>
                <button class="btn-edit" title="Sửa"
                        onclick='openEditModal(<?= htmlspecialchars(json_encode($p), ENT_QUOTES) ?>)'>
                  <i class="fas fa-edit"></i>
                </button>
                <button class="btn-delete" title="Xóa"
                        onclick="openDeleteModal(<?= $p['id'] ?>, '<?= htmlspecialchars(addslashes($p['name'])) ?>')">
                  <i class="fas fa-trash"></i>
                </button>
              </div>
            </td>
          </tr>
          <?php endforeach; ?>
          <?php if (empty($products)): ?>
          <tr><td colspan="7" style="text-align:center;padding:40px;color:#999;">
            Chưa có sản phẩm nào. Nhấn "Thêm sản phẩm" để bắt đầu! 🛍️
          </td></tr>
          <?php endif; ?>
        </tbody>
      </table>
    </div>

    <?php if ($totalPages > 1): ?>
    <nav class="admin-pagination">
      <?php if ($currentPage > 1): ?>
        <a href="?page=<?= $currentPage-1 ?>" class="admin-page-link"><i class="fas fa-angle-left"></i></a>
      <?php else: ?>
        <span class="admin-page-link admin-disabled"><i class="fas fa-angle-left"></i></span>
      <?php endif; ?>
      <?php for ($pg = 1; $pg <= $totalPages; $pg++): ?>
        <?php if ($pg===$currentPage): ?>
          <span class="admin-page-link admin-current"><?= $pg ?></span>
        <?php elseif ($pg===1||$pg===$totalPages||abs($pg-$currentPage)<=2): ?>
          <a href="?page=<?= $pg ?>" class="admin-page-link"><?= $pg ?></a>
        <?php elseif (abs($pg-$currentPage)===3): ?>
          <span class="admin-page-link admin-disabled">...</span>
        <?php endif; ?>
      <?php endfor; ?>
      <?php if ($currentPage < $totalPages): ?>
        <a href="?page=<?= $currentPage+1 ?>" class="admin-page-link"><i class="fas fa-angle-right"></i></a>
      <?php else: ?>
        <span class="admin-page-link admin-disabled"><i class="fas fa-angle-right"></i></span>
      <?php endif; ?>
    </nav>
    <?php endif; ?>
  </main>
</div>

<!-- Modal Thêm -->
<div id="createModal" class="modal-overlay" style="display:none;" onclick="closeModals()">
  <div class="modal-content" onclick="event.stopPropagation()" style="max-width:600px;max-height:90vh;overflow-y:auto;">
    <div class="modal-header"><h3>Thêm sản phẩm mới</h3><button class="close-btn" onclick="closeModals()">×</button></div>
    <div class="modal-body">
      <div class="form-group"><label>Tên sản phẩm *</label><input type="text" id="c-name" placeholder="Áo thun nam basic"></div>
      <div class="form-group"><label>Giá bán (basePrice) *</label><input type="number" id="c-basePrice" min="0" placeholder="150000"></div>
      <div class="form-group"><label>Giá ảo (virtualPrice) — tạo hiệu ứng giảm giá</label><input type="number" id="c-virtualPrice" min="0" placeholder="200000"></div>
      <div class="form-group">
        <label>Danh mục</label>
        <select id="c-category">
          <option value="">-- Chọn danh mục --</option>
          <?php foreach ($allCategories as $cat): ?>
          <option value="<?= $cat['id'] ?>"><?= $cat['parentCategoryId'] ? '&nbsp;&nbsp;└ ' : '' ?><?= htmlspecialchars($cat['name']) ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="form-group">
        <label>Hình ảnh</label>
        <div style="margin-bottom:8px;">
          <label style="display:inline-flex;align-items:center;gap:8px;background:#17a2b8;color:white;padding:8px 14px;border-radius:4px;cursor:pointer;font-size:13px;">
            <i class="fas fa-upload"></i> Chọn ảnh từ máy tính
            <input type="file" accept="image/*" multiple style="display:none;" onchange="uploadImages(this, 'c-images')">
          </label>
          <span id="c-upload-status" style="margin-left:10px;font-size:12px;color:#28a745;"></span>
        </div>
        <div id="c-image-preview" style="display:flex;flex-wrap:wrap;gap:8px;margin-bottom:8px;"></div>
        <textarea id="c-images" rows="2" placeholder="URL ảnh tự điền sau khi upload..."></textarea>
      </div>
      <hr style="margin:15px 0;">
      <h4 style="margin-bottom:10px;">⚙️ Biến thể & Kho hàng</h4>
      <div class="form-group"><label>Màu sắc (cách nhau dấu phẩy)</label><input type="text" id="c-colors" placeholder="Đỏ, Xanh, Đen, Trắng"></div>
      <div class="form-group"><label>Kích thước (cách nhau dấu phẩy)</label><input type="text" id="c-sizes" placeholder="S, M, L, XL, XXL"></div>
      <div class="form-group"><label>Giá mỗi SKU</label><input type="number" id="c-skuPrice" min="0" placeholder="150000"></div>
      <div class="form-group"><label>Số lượng tồn kho mỗi SKU</label><input type="number" id="c-skuStock" min="0" placeholder="100"></div>
    </div>
    <div class="modal-footer">
      <button class="btn-cancel" onclick="closeModals()">Hủy</button>
      <button class="btn-save" onclick="submitCreate()" id="createBtn"><i class="fas fa-plus"></i> Tạo sản phẩm</button>
    </div>
  </div>
</div>

<!-- Modal Sửa -->
<div id="editModal" class="modal-overlay" style="display:none;" onclick="closeModals()">
  <div class="modal-content" onclick="event.stopPropagation()" style="max-width:500px;">
    <div class="modal-header"><h3>Sửa sản phẩm <span id="edit-id-label"></span></h3><button class="close-btn" onclick="closeModals()">×</button></div>
    <div class="modal-body">
      <input type="hidden" id="e-id">
      <div class="form-group"><label>Tên *</label><input type="text" id="e-name"></div>
      <div class="form-group"><label>Giá bán *</label><input type="number" id="e-basePrice" min="0"></div>
      <div class="form-group"><label>Giá ảo</label><input type="number" id="e-virtualPrice" min="0"></div>
      <div class="form-group">
        <label>Hình ảnh</label>
        <div style="margin-bottom:8px;">
          <label style="display:inline-flex;align-items:center;gap:8px;background:#17a2b8;color:white;padding:8px 14px;border-radius:4px;cursor:pointer;font-size:13px;">
            <i class="fas fa-upload"></i> Chọn ảnh từ máy tính
            <input type="file" accept="image/*" multiple style="display:none;" onchange="uploadImages(this, 'e-images')">
          </label>
          <span id="e-upload-status" style="margin-left:10px;font-size:12px;color:#28a745;"></span>
        </div>
        <div id="e-image-preview" style="display:flex;flex-wrap:wrap;gap:8px;margin-bottom:8px;"></div>
        <textarea id="e-images" rows="2" placeholder="URL ảnh..."></textarea>
      </div>
    </div>
    <div class="modal-footer">
      <button class="btn-cancel" onclick="closeModals()">Hủy</button>
      <button class="btn-save" onclick="submitEdit()" id="editBtn"><i class="fas fa-save"></i> Lưu</button>
    </div>
  </div>
</div>

<!-- Modal Xóa -->
<div id="deleteModal" class="modal-overlay" style="display:none;" onclick="closeModals()">
  <div class="modal-content" onclick="event.stopPropagation()" style="max-width:400px;text-align:center;">
    <div class="modal-header"><h3>Xác nhận xóa</h3><button class="close-btn" onclick="closeModals()">×</button></div>
    <div class="modal-body">
      <p>Xóa sản phẩm <strong id="delete-name"></strong>?</p>
      <p style="color:#dc3545;font-size:13px;">Không thể hoàn tác!</p>
    </div>
    <div class="modal-footer" style="justify-content:center;gap:10px;">
      <button class="btn-cancel" onclick="closeModals()">Hủy</button>
      <button id="deleteBtn" onclick="submitDelete()"
              style="background:#dc3545;color:#fff;border:none;padding:8px 20px;border-radius:4px;cursor:pointer;">
        <i class="fas fa-trash"></i> Xóa
      </button>
    </div>
  </div>
</div>

<div id="toast" style="display:none;position:fixed;bottom:20px;right:20px;padding:12px 20px;border-radius:6px;color:#fff;font-size:14px;z-index:99999;"></div>

<script>
let currentId = null;
function showToast(msg, type='success') {
    const t = document.getElementById('toast');
    t.textContent = msg; t.style.background = type==='success'?'#28a745':'#dc3545';
    t.style.display='block'; setTimeout(()=>t.style.display='none',3000);
}
function closeModals() {
    ['createModal','editModal','deleteModal'].forEach(id=>document.getElementById(id).style.display='none');
    currentId=null;
}
function openCreateModal() { document.getElementById('createModal').style.display='flex'; }
async function submitCreate() {
    const name=document.getElementById('c-name').value.trim();
    const basePrice=parseFloat(document.getElementById('c-basePrice').value)||0;
    if(!name||!basePrice){showToast('Vui lòng nhập tên và giá!','error');return;}
    const btn=document.getElementById('createBtn');
    btn.disabled=true; btn.innerHTML='<i class="fas fa-spinner fa-spin"></i> Đang tạo...';
    const images=document.getElementById('c-images').value.trim().split('\n').map(s=>s.trim()).filter(Boolean);
    const colors=document.getElementById('c-colors').value.split(',').map(s=>s.trim()).filter(Boolean);
    const sizes=document.getElementById('c-sizes').value.split(',').map(s=>s.trim()).filter(Boolean);
    const res=await fetch('/shop-php/api/admin/product-save.php',{
        method:'POST',headers:csrfHeaders({'Content-Type':'application/json'}),
        body:JSON.stringify({name,basePrice,virtualPrice:parseFloat(document.getElementById('c-virtualPrice').value)||0,
            images,category:document.getElementById('c-category').value,colors,sizes,
            skuPrice:parseFloat(document.getElementById('c-skuPrice').value)||basePrice,
            skuStock:parseInt(document.getElementById('c-skuStock').value)||0})
    });
    const data=await res.json();
    if(!data.error){showToast('Tạo sản phẩm thành công!');closeModals();setTimeout(()=>location.reload(),1000);}
    else showToast(data.message||'Thất bại','error');
    btn.disabled=false; btn.innerHTML='<i class="fas fa-plus"></i> Tạo sản phẩm';
}
function openEditModal(p) {
    currentId=p.id;
    document.getElementById('e-id').value=p.id;
    document.getElementById('edit-id-label').textContent='#'+p.id;
    document.getElementById('e-name').value=p.name||'';
    document.getElementById('e-basePrice').value=p.basePrice||0;
    document.getElementById('e-virtualPrice').value=p.virtualPrice||0;
    document.getElementById('e-images').value=(JSON.parse(p.images||'[]')).join('\n');
    document.getElementById('editModal').style.display='flex';
}
async function submitEdit() {
    const id=document.getElementById('e-id').value;
    const name=document.getElementById('e-name').value.trim();
    const images=document.getElementById('e-images').value.trim().split('\n').map(s=>s.trim()).filter(Boolean);
    if(!name){showToast('Vui lòng nhập tên!','error');return;}
    const btn=document.getElementById('editBtn');
    btn.disabled=true; btn.innerHTML='<i class="fas fa-spinner fa-spin"></i>';
    const res=await fetch('/shop-php/api/admin/product-save.php',{
        method:'POST',headers:csrfHeaders({'Content-Type':'application/json'}),
        body:JSON.stringify({id:parseInt(id),name,basePrice:parseFloat(document.getElementById('e-basePrice').value)||0,
            virtualPrice:parseFloat(document.getElementById('e-virtualPrice').value)||0,images})
    });
    const data=await res.json();
    if(!data.error){showToast('Cập nhật thành công!');closeModals();setTimeout(()=>location.reload(),1000);}
    else showToast(data.message||'Thất bại','error');
    btn.disabled=false; btn.innerHTML='<i class="fas fa-save"></i> Lưu';
}
function openDeleteModal(id,name){
    currentId=id; document.getElementById('delete-name').textContent=name;
    document.getElementById('deleteModal').style.display='flex';
}
async function uploadImages(input, targetId) {
    const files = Array.from(input.files);
    const statusEl = document.getElementById(targetId === 'c-images' ? 'c-upload-status' : 'e-upload-status');
    const previewEl = document.getElementById(targetId === 'c-images' ? 'c-image-preview' : 'e-image-preview');
    const textarea = document.getElementById(targetId);

    statusEl.textContent = 'Đang upload ' + files.length + ' ảnh...';
    let uploaded = 0;

    for (const file of files) {
        const formData = new FormData();
        formData.append('image', file);
        try {
            const res = await fetch('/shop-php/api/admin/upload-image.php', { method: 'POST', headers: csrfHeaders(), body: formData });
            const data = await res.json();
            if (!data.error) {
                // Thêm URL vào textarea
                const current = textarea.value.trim();
                textarea.value = current ? current + '\n' + data.url : data.url;
                // Thêm preview
                const img = document.createElement('img');
                img.src = data.url;
                img.style = 'width:80px;height:80px;object-fit:cover;border-radius:4px;border:2px solid #17a2b8;';
                previewEl.appendChild(img);
                uploaded++;
            }
        } catch(e) { console.error(e); }
    }
    statusEl.textContent = '✅ Đã upload ' + uploaded + '/' + files.length + ' ảnh';
    input.value = '';
}

async function submitDelete() {
    const btn=document.getElementById('deleteBtn');
    btn.disabled=true; btn.textContent='Đang xóa...';
    const res=await fetch('/shop-php/api/admin/product-save.php',{
        method:'POST',headers:csrfHeaders({'Content-Type':'application/json'}),
        body:JSON.stringify({id:currentId,action:'delete'})
    });
    const data=await res.json();
    if(!data.error){showToast('Xóa thành công!');document.getElementById('row-'+currentId)?.remove();closeModals();}
    else showToast(data.message||'Thất bại','error');
    btn.disabled=false; btn.innerHTML='<i class="fas fa-trash"></i> Xóa';
}
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
