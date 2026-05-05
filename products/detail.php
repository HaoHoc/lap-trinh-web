<?php
require_once __DIR__ . '/../core/auth.php';

$productId = intval($_GET['id'] ?? 0);
if ($productId <= 0) { header('Location: /shop-php/index.php'); exit; }

$product  = DB::queryOne("SELECT * FROM products WHERE id = ? AND deletedAt IS NULL", [$productId]);
$skus     = DB::query("SELECT * FROM product_skus WHERE product_id = ? ORDER BY id", [$productId]);
$variants = DB::query("SELECT * FROM product_variants WHERE product_id = ? ORDER BY id", [$productId]);
$reviews  = DB::query(
    "SELECT r.*, u.name as userName, u.avatar as userAvatar
     FROM reviews r JOIN users u ON r.user_id = u.id
     WHERE r.product_id = ? ORDER BY r.createdAt DESC LIMIT 10",
    [$productId]
);

if (!$product) {
    $pageTitle = 'Không tìm thấy';
    require_once __DIR__ . '/../includes/header.php';
    echo '<div style="text-align:center;padding:60px;"><h2>Sản phẩm không tồn tại</h2><a href="/shop-php/index.php">Về trang chủ</a></div>';
    require_once __DIR__ . '/../includes/footer.php';
    exit;
}

$images      = json_decode($product['images'] ?? '[]', true);
$mainImage   = $images[0] ?? '';
$pageTitle   = $product['name'] . ' - PIXCAM';

function formatCurrency(float $a): string { return number_format($a,0,',','.') . ' ₫'; }
function calcDiscount(float $b, float $v): int {
    if ($v<=0||$b<=0||$v<=$b) return 0;
    return (int)round(($v-$b)/$v*100);
}

$discount    = calcDiscount($product['basePrice'], $product['virtualPrice']);
$hasDiscount = $discount > 0;

require_once __DIR__ . '/../includes/header.php';
?>

<link rel="stylesheet" href="/shop-php/assets/css/detailProduct.css">

<div class="content">
  <div class="content_detailProduct">

    <!-- Ảnh sản phẩm -->
    <div class="img_product">
      <img id="main-image" src="<?= htmlspecialchars($mainImage) ?>"
           alt="<?= htmlspecialchars($product['name']) ?>" class="image_shirt"
           onerror="this.src='https://via.placeholder.com/500x600/e0e0e0/666?text=No+Image'">
      <div class="image_detail_product">
        <?php foreach (array_filter($images) as $i => $url): ?>
        <img src="<?= htmlspecialchars($url) ?>" alt="thumbnail <?= $i+1 ?>"
             class="image_shirt_detail" style="cursor:pointer;"
             onclick="document.getElementById('main-image').src='<?= htmlspecialchars($url) ?>'">
        <?php endforeach; ?>
      </div>
    </div>

    <!-- Thông tin -->
    <div class="inf_product">
      <h2 class="title_inf_products"><?= htmlspecialchars($product['name']) ?></h2>

      <div class="price_inf_products">
        <?php if ($hasDiscount): ?>
          <span class="price-original"><?= formatCurrency($product['virtualPrice']) ?></span>
          <span class="price-sale" id="displayPrice"><?= formatCurrency($product['basePrice']) ?></span>
          <span class="discount-badge">-<?= $discount ?>%</span>
        <?php else: ?>
          <span id="displayPrice"><?= formatCurrency($product['basePrice']) ?></span>
        <?php endif; ?>
      </div>

      <p class="status_inf_products">
        Tình trạng: <span class="status_color_inf" id="stockStatus">Vui lòng chọn đầy đủ tùy chọn</span>
      </p>

      <?php foreach ($variants as $vi => $variant):
        $options = json_decode($variant['options'] ?? '[]', true);
        if (empty($options)) continue;
      ?>
      <p class="<?= $vi===0?'color_inf_products':'size_inf_products' ?>"><?= htmlspecialchars($variant['name']) ?>:</p>
      <div class="<?= $vi===0?'item_box_color':'box_option_size' ?>">
        <?php foreach ($options as $oi => $opt): ?>
        <div class="<?= $vi===0?'color-item':'size-item' ?>">
          <input type="radio" id="v<?= $vi ?>_<?= $oi ?>" name="variant_<?= $vi ?>"
                 value="<?= htmlspecialchars($opt) ?>" <?= $oi===0?'checked':'' ?> onchange="updateSku()">
          <label for="v<?= $vi ?>_<?= $oi ?>"><span class="<?= $vi===0?'color-name':'' ?>"><?= htmlspecialchars($opt) ?></span></label>
        </div>
        <?php endforeach; ?>
      </div>
      <?php endforeach; ?>

      <p class="quantity_inf_products">Số lượng:</p>
      <div class="quantity_box">
        <div class="detail_quatity">
          <button class="totalProducts" onclick="changeQty(-1)" id="btnMinus" disabled>−</button>
          <input id="input-qty" type="text" value="1" readonly>
          <button class="totalProducts" onclick="changeQty(1)" id="btnPlus" disabled>+</button>
        </div>
        <button type="button" class="btn_quantity_box" id="addToCartBtn" onclick="addToCart()" disabled>
          Thêm vào giỏ hàng
        </button>
      </div>
    </div>
  </div>

  <!-- Reviews -->
  <div class="reviews_section">
    <div class="reviews_header">
      <h3 class="reviews_title">Đánh giá sản phẩm</h3>
      <?php if (!empty($reviews)): ?>
        <span class="reviews_count">(<?= count($reviews) ?> đánh giá)</span>
      <?php endif; ?>
    </div>
    <?php if (empty($reviews)): ?>
      <div class="reviews_empty">Chưa có đánh giá nào.</div>
    <?php else: ?>
    <div class="reviews_list">
      <?php foreach ($reviews as $r): ?>
      <div class="review_item_compact" style="padding:15px 0;border-bottom:1px solid #f0f0f0;">
        <div style="display:flex;align-items:center;gap:10px;margin-bottom:8px;">
          <?php if (!empty($r['userAvatar'])): ?>
            <img src="<?= htmlspecialchars($r['userAvatar']) ?>" style="width:36px;height:36px;border-radius:50%;object-fit:cover;">
          <?php else: ?>
            <i class="fa-solid fa-circle-user" style="font-size:36px;color:#ccc;"></i>
          <?php endif; ?>
          <div>
            <strong style="font-size:14px;"><?= htmlspecialchars($r['userName']) ?></strong>
            <div><?php for ($s=1;$s<=5;$s++) echo '<i class="fa-solid fa-star" style="color:'.($s<=$r['rating']?'#ffc107':'#ddd').';font-size:12px;"></i>'; ?></div>
          </div>
          <span style="margin-left:auto;font-size:12px;color:#999;"><?= date('d/m/Y',strtotime($r['createdAt'])) ?></span>
        </div>
        <p style="font-size:14px;color:#333;margin:0;"><?= nl2br(htmlspecialchars($r['content']??'')) ?></p>
      </div>
      <?php endforeach; ?>
    </div>
    <?php endif; ?>
  </div>
</div>

<script>
const SKUS      = <?= json_encode($skus) ?>;
const IS_LOGGED = <?= Auth::check()?'true':'false' ?>;
let currentQty = 1, selectedSku = null;

function updateSku() {
    const names = [...new Set([...document.querySelectorAll('[name^="variant_"]')].map(e=>e.name))];
    const vals  = names.map(n => { const c=document.querySelector(`[name="${n}"]:checked`); return c?c.value:null; }).filter(Boolean);
    const skuValue = vals.join('-');
    selectedSku = SKUS.find(s=>s.value===skuValue)||null;
    currentQty  = 1;
    document.getElementById('input-qty').value = 1;

    const statusEl=document.getElementById('stockStatus');
    const addBtn=document.getElementById('addToCartBtn');
    const priceEl=document.getElementById('displayPrice');

    if (selectedSku) {
        if (priceEl) priceEl.textContent = new Intl.NumberFormat('vi-VN').format(selectedSku.price)+' ₫';
        if (selectedSku.stock>0) {
            statusEl.textContent=`Còn hàng (${selectedSku.stock} sản phẩm)`;
            document.getElementById('btnMinus').disabled=false;
            document.getElementById('btnPlus').disabled=false;
            addBtn.disabled=false; addBtn.textContent='Thêm vào giỏ hàng';
        } else {
            statusEl.textContent='Hết hàng';
            document.getElementById('btnMinus').disabled=true;
            document.getElementById('btnPlus').disabled=true;
            addBtn.disabled=true; addBtn.textContent='Hết hàng';
        }
    } else {
        statusEl.textContent='Vui lòng chọn đầy đủ tùy chọn';
        document.getElementById('btnMinus').disabled=true;
        document.getElementById('btnPlus').disabled=true;
        addBtn.disabled=true;
    }
}

function changeQty(a) {
    if (!selectedSku) return;
    const n=currentQty+a;
    if (n>=1&&n<=selectedSku.stock) { currentQty=n; document.getElementById('input-qty').value=n; }
}

async function addToCart() {
    if (!selectedSku||selectedSku.stock<=0) return;
    if (!IS_LOGGED) { alert('Vui lòng đăng nhập!'); window.location.href='/shop-php/login.php'; return; }
    const btn=document.getElementById('addToCartBtn');
    btn.disabled=true; btn.textContent='Đang thêm...';
    const res=await fetch('/shop-php/api/cart-add.php',{
        method:'POST',headers:csrfHeaders({'Content-Type':'application/json'}),
        body:JSON.stringify({skuId:selectedSku.id,quantity:currentQty})
    });
    const data=await res.json();
    if (!data.error) { btn.textContent='✅ Đã thêm vào giỏ!'; setTimeout(()=>{btn.disabled=false;btn.textContent='Thêm vào giỏ hàng';},2000); }
    else { alert(data.message||'Lỗi'); btn.disabled=false; btn.textContent='Thêm vào giỏ hàng'; }
}

document.addEventListener('DOMContentLoaded', updateSku);
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
