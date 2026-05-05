<?php
require_once __DIR__ . '/../core/auth.php';
Auth::requireAdmin();

// Fetch categories cấp 1 (thay fetchCategories)
$categories = DB::query("SELECT * FROM categories WHERE parentCategoryId IS NULL ORDER BY id");

function formatDate(string $d): string {
    return date('d/m/Y', strtotime($d));
}

$pageTitle = 'Quản lý danh mục - Admin';
require_once __DIR__ . '/../includes/header.php';
?>

<link rel="stylesheet" href="/assets/css/adminProducts.css">
<link rel="stylesheet" href="/assets/css/modal.css">

<div class="admin-container">
  <main class="admin-main">
    <div class="admin-header">
      <h1>Quản lý danh mục</h1>
      <button class="btn-create" onclick="openAddModal(null)">
        <i class="fas fa-plus"></i> Thêm danh mục
      </button>
    </div>

    <div class="admin-stats">
      <p>Hiển thị <?= count($categories) ?> danh mục chính</p>
    </div>

    <div class="table-container">
      <table class="cinema-table" id="categoryTable">
        <thead>
          <tr>
            <th>ID</th>
            <th>Tên danh mục</th>
            <th>Logo</th>
            <th>Cấp độ</th>
            <th>Ngày tạo</th>
            <th>Hành động</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($categories as $cat): ?>
          <!-- Hàng danh mục chính -->
          <tr id="cat-row-<?= $cat['id'] ?>">
            <td><?= $cat['id'] ?></td>
            <td>
              <div style="display:flex; align-items:center;">
                <!-- Nút toggle subcategories (thay toggleCategory) -->
                <button onclick="toggleSubcategories(<?= $cat['id'] ?>)"
                        style="background:none; border:none; cursor:pointer; margin-right:8px; font-size:12px;"
                        id="toggle-<?= $cat['id'] ?>">▶</button>
                <span class="tooltip" data-tooltip="<?= htmlspecialchars($cat['name']) ?>">
                  <?= htmlspecialchars($cat['name']) ?>
                </span>
              </div>
            </td>
            <td>
              <div style="width:40px; height:40px; background:#f0f0f0; border-radius:4px; display:flex; align-items:center; justify-content:center; font-size:12px; color:#666; overflow:hidden;">
                <?php if (!empty($cat['logo']) && str_starts_with($cat['logo'], 'http')): ?>
                  <img src="<?= htmlspecialchars($cat['logo']) ?>" style="width:100%; height:100%; object-fit:cover;">
                <?php else: ?>
                  <?= htmlspecialchars($cat['logo'] ?? '📁') ?>
                <?php endif; ?>
              </div>
            </td>
            <td>Danh mục chính</td>
            <td><?= formatDate($cat['createdAt']) ?></td>
            <td>
              <div class="action-buttons">
                <button class="btn-edit" title="Sửa"
                        onclick="openEditModal(<?= $cat['id'] ?>, '<?= htmlspecialchars(addslashes($cat['name'])) ?>', '<?= htmlspecialchars(addslashes($cat['logo'] ?? '')) ?>', null)">
                  <i class="fas fa-edit"></i>
                </button>
                <button title="Dịch" style="background:#28a745; color:white; border:none; padding:5px 8px; border-radius:3px; cursor:pointer; margin:0 2px;"
                        onclick="openTranslationModal(<?= $cat['id'] ?>, '<?= htmlspecialchars(addslashes($cat['name'])) ?>')">
                  <i class="fas fa-language"></i>
                </button>
                <button title="Thêm danh mục con" style="background:#17a2b8; color:white; border:none; padding:5px 8px; border-radius:3px; cursor:pointer; margin:0 2px;"
                        onclick="openAddModal(<?= $cat['id'] ?>)">
                  <i class="fas fa-plus-circle"></i>
                </button>
                <button class="btn-delete" title="Xóa"
                        onclick="openDeleteModal(<?= $cat['id'] ?>, '<?= htmlspecialchars(addslashes($cat['name'])) ?>')">
                  <i class="fas fa-trash"></i>
                </button>
              </div>
            </td>
          </tr>
          <!-- Hàng subcategories (ẩn mặc định, load AJAX khi toggle) -->
          <tr id="sub-container-<?= $cat['id'] ?>" style="display:none;">
            <td colspan="6" style="padding:0;">
              <table style="width:100%; border:none;">
                <tbody id="sub-list-<?= $cat['id'] ?>">
                  <tr><td colspan="6" style="text-align:center; padding:10px; color:#999;">Đang tải...</td></tr>
                </tbody>
              </table>
            </td>
          </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  </main>
</div>

<!-- ── Modal Thêm danh mục ── -->
<div id="addModal" class="modal-overlay" style="display:none;" onclick="closeModals()">
  <div class="modal-content" onclick="event.stopPropagation()" style="max-width:450px;">
    <div class="modal-header">
      <h3 id="addModalTitle">Thêm danh mục</h3>
      <button class="close-btn" onclick="closeModals()">×</button>
    </div>
    <div class="modal-body">
      <input type="hidden" id="add-parentId">
      <div class="form-group"><label>Tên danh mục *</label>
        <input type="text" id="add-name" placeholder="Tên danh mục"></div>
      <div class="form-group"><label>Logo (URL hoặc emoji)</label>
        <input type="text" id="add-logo" placeholder="https://... hoặc 📁"></div>
    </div>
    <div class="modal-footer">
      <button class="btn-cancel" onclick="closeModals()">Hủy</button>
      <button class="btn-save" onclick="submitAdd()"><i class="fas fa-plus"></i> Thêm</button>
    </div>
  </div>
</div>

<!-- ── Modal Sửa danh mục ── -->
<div id="editModal" class="modal-overlay" style="display:none;" onclick="closeModals()">
  <div class="modal-content" onclick="event.stopPropagation()" style="max-width:450px;">
    <div class="modal-header">
      <h3>Chỉnh sửa danh mục</h3>
      <button class="close-btn" onclick="closeModals()">×</button>
    </div>
    <div class="modal-body">
      <input type="hidden" id="edit-id">
      <input type="hidden" id="edit-parentId">
      <div class="form-group"><label>Tên danh mục *</label>
        <input type="text" id="edit-name"></div>
      <div class="form-group"><label>Logo (URL hoặc emoji)</label>
        <input type="text" id="edit-logo"></div>
    </div>
    <div class="modal-footer">
      <button class="btn-cancel" onclick="closeModals()">Hủy</button>
      <button class="btn-save" onclick="submitEdit()"><i class="fas fa-save"></i> Lưu</button>
    </div>
  </div>
</div>

<!-- ── Modal Dịch danh mục ── -->
<div id="translationModal" class="modal-overlay" style="display:none;" onclick="closeModals()">
  <div class="modal-content" onclick="event.stopPropagation()" style="max-width:450px;">
    <div class="modal-header">
      <h3>Dịch danh mục: <span id="trans-name"></span></h3>
      <button class="close-btn" onclick="closeModals()">×</button>
    </div>
    <div class="modal-body">
      <input type="hidden" id="trans-id">
      <div class="form-group"><label>Ngôn ngữ</label>
        <select id="trans-lang"><option value="en">English</option><option value="vi">Tiếng Việt</option></select></div>
      <div class="form-group"><label>Tên dịch *</label>
        <input type="text" id="trans-tname" placeholder="Tên đã dịch"></div>
    </div>
    <div class="modal-footer">
      <button class="btn-cancel" onclick="closeModals()">Hủy</button>
      <button class="btn-save" onclick="submitTranslation()"><i class="fas fa-language"></i> Lưu</button>
    </div>
  </div>
</div>

<!-- ── Modal Xóa danh mục ── -->
<div id="deleteModal" class="modal-overlay" style="display:none;" onclick="closeModals()">
  <div class="modal-content" onclick="event.stopPropagation()" style="max-width:400px; text-align:center;">
    <div class="modal-header"><h3>Xác nhận xóa</h3>
      <button class="close-btn" onclick="closeModals()">×</button></div>
    <div class="modal-body">
      <p>Bạn có chắc muốn xóa danh mục <strong id="delete-name"></strong>?</p>
      <p style="color:#dc3545; font-size:13px;">Hành động này không thể hoàn tác!</p>
    </div>
    <div class="modal-footer" style="justify-content:center; gap:10px;">
      <button class="btn-cancel" onclick="closeModals()">Hủy</button>
      <button onclick="submitDelete()" id="deleteConfirmBtn"
              style="background:#dc3545; color:#fff; border:none; padding:8px 20px; border-radius:4px; cursor:pointer;">
        <i class="fas fa-trash"></i> Xóa
      </button>
    </div>
  </div>
</div>

<div id="toastMsg" style="display:none; position:fixed; bottom:20px; right:20px; padding:12px 20px; border-radius:6px; color:#fff; font-size:14px; z-index:99999;"></div>

<script>
let currentCatId = null;
let loadedSubs   = {};

function showToast(msg, type='success') {
    const t = document.getElementById('toastMsg');
    t.textContent = msg; t.style.background = type==='success'?'#28a745':'#dc3545';
    t.style.display = 'block'; setTimeout(() => t.style.display='none', 3000);
}
const esc = window.escapeHtml || (value => String(value ?? ''));
function closeModals() {
    ['addModal','editModal','translationModal','deleteModal']
        .forEach(id => document.getElementById(id).style.display='none');
    currentCatId = null;
}

// ── Toggle subcategories (thay toggleCategory + fetchSubCategories) ──
async function toggleSubcategories(catId) {
    const container = document.getElementById(`sub-container-${catId}`);
    const toggle    = document.getElementById(`toggle-${catId}`);
    const isOpen    = container.style.display !== 'none';

    if (isOpen) {
        container.style.display = 'none';
        toggle.textContent = '▶';
    } else {
        container.style.display = '';
        toggle.textContent = '▼';
        if (!loadedSubs[catId]) {
            loadedSubs[catId] = true;
            const res  = await fetch(`/shop-php/api/admin/category-subs.php?parent_id=${catId}`);
            const data = await res.json();
            const subs = data.data || [];
            subs.forEach(sub => {
                sub.name = esc(sub.name);
                sub.logo = esc(sub.logo || '');
            });
            const tbody = document.getElementById(`sub-list-${catId}`);
            if (!subs.length) {
                tbody.innerHTML = '<tr><td colspan="6" style="padding:10px; color:#999; padding-left:40px;">Không có danh mục con</td></tr>';
            } else {
                tbody.innerHTML = subs.map(sub => `
                <tr id="cat-row-${sub.id}">
                  <td style="padding-left:40px;">${sub.id}</td>
                  <td><span style="margin-right:8px; color:#999;">└─</span>${sub.name}</td>
                  <td>${sub.logo?.startsWith('http') ? `<img src="${sub.logo}" style="width:40px;height:40px;object-fit:cover;border-radius:4px;">` : (sub.logo||'📁')}</td>
                  <td>Danh mục con</td>
                  <td>${new Date(sub.createdAt).toLocaleDateString('vi-VN')}</td>
                  <td>
                    <div class="action-buttons">
                      <button class="btn-edit" onclick="openEditModal(${sub.id},'${sub.name.replace(/'/g,"\\'")}','${(sub.logo||'').replace(/'/g,"\\'")}',${catId})"><i class="fas fa-edit"></i></button>
                      <button style="background:#28a745;color:white;border:none;padding:5px 8px;border-radius:3px;cursor:pointer;margin:0 2px;" onclick="openTranslationModal(${sub.id},'${sub.name.replace(/'/g,"\\'")}')"><i class="fas fa-language"></i></button>
                      <button class="btn-delete" onclick="openDeleteModal(${sub.id},'${sub.name.replace(/'/g,"\\'")}')"><i class="fas fa-trash"></i></button>
                    </div>
                  </td>
                </tr>`).join('');
            }
        }
    }
}

// ── Thêm danh mục ──
function openAddModal(parentId) {
    currentCatId = null;
    document.getElementById('add-parentId').value = parentId ?? '';
    document.getElementById('addModalTitle').textContent = parentId ? 'Thêm danh mục con' : 'Thêm danh mục';
    document.getElementById('add-name').value = '';
    document.getElementById('add-logo').value = '';
    document.getElementById('addModal').style.display = 'flex';
}
async function submitAdd() {
    const name     = document.getElementById('add-name').value.trim();
    const logo     = document.getElementById('add-logo').value.trim();
    const parentId = document.getElementById('add-parentId').value || null;
    if (!name) { showToast('Vui lòng nhập tên danh mục', 'error'); return; }
    const res  = await fetch('/shop-php/api/admin/category-create.php', {
        method:'POST', headers:csrfHeaders({'Content-Type':'application/json'}),
        body: JSON.stringify({ name, logo, parentCategoryId: parentId ? parseInt(parentId) : null }),
    });
    const data = await res.json();
    if (!data.error) { showToast('Thêm danh mục thành công!'); closeModals(); location.reload(); }
    else showToast(data.message||'Thêm thất bại','error');
}

// ── Sửa danh mục ──
function openEditModal(id, name, logo, parentId) {
    currentCatId = id;
    document.getElementById('edit-id').value       = id;
    document.getElementById('edit-parentId').value = parentId ?? '';
    document.getElementById('edit-name').value     = name;
    document.getElementById('edit-logo').value     = logo;
    document.getElementById('editModal').style.display = 'flex';
}
async function submitEdit() {
    const id       = document.getElementById('edit-id').value;
    const name     = document.getElementById('edit-name').value.trim();
    const logo     = document.getElementById('edit-logo').value.trim();
    const parentId = document.getElementById('edit-parentId').value || null;
    if (!name) { showToast('Vui lòng nhập tên','error'); return; }
    const res  = await fetch('/shop-php/api/admin/category-update.php', {
        method:'POST', headers:csrfHeaders({'Content-Type':'application/json'}),
        body: JSON.stringify({ id: parseInt(id), name, logo, parentCategoryId: parentId ? parseInt(parentId) : null }),
    });
    const data = await res.json();
    if (!data.error) { showToast('Cập nhật thành công!'); closeModals(); location.reload(); }
    else showToast(data.message||'Cập nhật thất bại','error');
}

// ── Dịch danh mục ──
function openTranslationModal(id, name) {
    currentCatId = id;
    document.getElementById('trans-id').value          = id;
    document.getElementById('trans-name').textContent  = name;
    document.getElementById('trans-tname').value       = '';
    document.getElementById('translationModal').style.display = 'flex';
}
async function submitTranslation() {
    const id   = document.getElementById('trans-id').value;
    const lang = document.getElementById('trans-lang').value;
    const name = document.getElementById('trans-tname').value.trim();
    if (!name) { showToast('Vui lòng nhập tên dịch','error'); return; }
    const res  = await fetch('/shop-php/api/admin/category-translation.php', {
        method:'POST', headers:csrfHeaders({'Content-Type':'application/json'}),
        body: JSON.stringify({ categoryId: parseInt(id), languageId: lang, name }),
    });
    const data = await res.json();
    if (!data.error) { showToast('Lưu bản dịch thành công!'); closeModals(); }
    else showToast(data.message||'Lưu thất bại','error');
}

// ── Xóa danh mục ──
function openDeleteModal(id, name) {
    currentCatId = id;
    document.getElementById('delete-name').textContent = name;
    document.getElementById('deleteModal').style.display = 'flex';
}
async function submitDelete() {
    const btn = document.getElementById('deleteConfirmBtn');
    btn.disabled = true; btn.textContent = 'Đang xóa...';
    const res  = await fetch('/shop-php/api/admin/category-delete.php', {
        method:'POST', headers:csrfHeaders({'Content-Type':'application/json'}),
        body: JSON.stringify({ id: currentCatId }),
    });
    const data = await res.json();
    if (!data.error) { showToast('Xóa thành công!'); closeModals(); location.reload(); }
    else showToast(data.message||'Xóa thất bại','error');
    btn.disabled = false; btn.innerHTML = '<i class="fas fa-trash"></i> Xóa';
}
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
