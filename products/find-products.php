<?php
require_once __DIR__ . '/../core/auth.php';

$keyword     = trim($_GET['keyword'] ?? $_GET['q'] ?? '');
$currentPage = max(1, intval($_GET['page'] ?? 1));
$sortOption  = $_GET['sort'] ?? 'LATEST';
$perPage     = 12;
$offset      = ($currentPage - 1) * $perPage;

$sortMap = [
    'PRICE_ASC'  => 'basePrice ASC',
    'PRICE_DESC' => 'basePrice DESC',
    'LATEST'     => 'createdAt DESC',
];
$orderSQL = $sortMap[$sortOption] ?? 'createdAt DESC';

$products   = [];
$totalItems = 0;
$totalPages = 0;

if ($keyword !== '') {
    $like = '%' . $keyword . '%';
    $products = DB::query(
        "SELECT * FROM products
         WHERE deletedAt IS NULL AND name LIKE ?
         ORDER BY $orderSQL LIMIT ? OFFSET ?",
        [$like, $perPage, $offset]
    );
    $totalItems = DB::queryOne(
        "SELECT COUNT(*) as total FROM products WHERE deletedAt IS NULL AND name LIKE ?",
        [$like]
    )['total'] ?? 0;
    $totalPages = $totalItems > 0 ? (int)ceil($totalItems/$perPage) : 0;
}

function formatCurrency(float $p): string { return number_format($p,0,',','.') . ' ₫'; }
function calcDiscount(float $b, float $v): int {
    if ($v<=0||$b<=0||$v<=$b) return 0;
    return (int)round(($v-$b)/$v*100);
}

$pageTitle = $keyword ? "Tìm kiếm: $keyword - PIXCAM" : 'Tìm kiếm sản phẩm - PIXCAM';
require_once __DIR__ . '/../includes/header.php';
?>

<link rel="stylesheet" href="/shop-php/assets/css/style.css">
<link rel="stylesheet" href="/shop-php/assets/css/CategoryPage.css">

<main class="content">
  <div class="content_top">

    <!-- Search bar lớn -->
    <div style="max-width:600px;margin:30px auto 20px;padding:0 20px;">
      <form method="GET" action="/shop-php/products/find-products.php">
        <div style="display:flex;gap:0;box-shadow:0 2px 12px rgba(0,0,0,0.1);border-radius:8px;overflow:hidden;">
          <input type="text" name="keyword" value="<?= htmlspecialchars($keyword) ?>"
                 placeholder="Tìm kiếm sản phẩm..."
                 style="flex:1;padding:14px 18px;border:none;font-size:15px;outline:none;">
          <button type="submit"
                  style="padding:14px 20px;background:#ee5022;color:#fff;border:none;cursor:pointer;font-size:18px;">
            <i class="fas fa-search"></i>
          </button>
        </div>
      </form>
    </div>

    <?php if ($keyword === ''): ?>
      <div style="text-align:center;padding:40px;color:#999;">
        <i class="fas fa-search" style="font-size:48px;margin-bottom:15px;display:block;"></i>
        <p>Nhập từ khóa để tìm kiếm sản phẩm</p>
      </div>

    <?php elseif (empty($products)): ?>
      <div style="text-align:center;padding:40px;color:#999;">
        <i class="fas fa-box-open" style="font-size:48px;margin-bottom:15px;display:block;"></i>
        <p>Không tìm thấy sản phẩm nào với từ khóa <strong>"<?= htmlspecialchars($keyword) ?>"</strong></p>
        <a href="/shop-php/index.php" style="display:inline-block;margin-top:15px;padding:10px 24px;background:#ee5022;color:#fff;border-radius:6px;text-decoration:none;">
          Về trang chủ
        </a>
      </div>

    <?php else: ?>
      <!-- Kết quả + sort -->
      <div class="contentProducts_navigate">
        <div class="navigate_shopAll">
          <p class="title_navigate">
            Kết quả tìm kiếm cho <strong>"<?= htmlspecialchars($keyword) ?>"</strong>
          </p>
        </div>
        <div class="filter_shopAlll">
          <p class="filter-results-text">Tìm thấy <?= $totalItems ?> sản phẩm</p>
          <div class="filter-dropdowns">
            <div class="custom-dropdown" id="sortDropdown">
              <div class="selected" onclick="toggleDropdown('sortDropdown')">
                <span><?= ['PRICE_ASC'=>'Giá: thấp → cao','PRICE_DESC'=>'Giá: cao → thấp','LATEST'=>'Mới nhất'][$sortOption]??'Mới nhất' ?></span>
                <span>&#9662;</span>
              </div>
              <ul class="options">
                <li onclick="changeSort('LATEST')">Mới nhất</li>
                <li onclick="changeSort('PRICE_ASC')">Giá: thấp → cao</li>
                <li onclick="changeSort('PRICE_DESC')">Giá: cao → thấp</li>
              </ul>
            </div>
          </div>
        </div>
      </div>

      <!-- Danh sách sản phẩm -->
      <div class="product_top">
        <div class="products_home">
          <?php foreach ($products as $product):
            $images   = json_decode($product['images']??'[]', true);
            $img      = $images[0] ?? '';
            $discount = calcDiscount($product['basePrice'], $product['virtualPrice']);
            $hasDiscount = $discount > 0;
          ?>
          <div class="item_products_home">
            <a href="/shop-php/products/detail.php?id=<?= $product['id'] ?>" class="image_home_item">
              <?php if ($hasDiscount): ?>
              <div class="product_sale"><p class="text_products_sale">-<?= $discount ?>%</p></div>
              <?php endif; ?>
              <img src="<?= htmlspecialchars($img) ?>"
                   alt="<?= htmlspecialchars($product['name']) ?>"
                   class="image_products_home"
                   onerror="this.src='https://via.placeholder.com/300x400/e0e0e0/666?text=No+Image'">
            </a>
            <div class="product-info-container">
              <h4 class="product-name"><?= htmlspecialchars($product['name']) ?></h4>
              <p class="product-price">
                <?php if ($hasDiscount): ?>
                  <span class="price-original"><?= formatCurrency($product['virtualPrice']) ?></span>
                  <span class="price-sale"><?= formatCurrency($product['basePrice']) ?></span>
                <?php else: ?>
                  <span><?= formatCurrency($product['basePrice']) ?></span>
                <?php endif; ?>
              </p>
            </div>
          </div>
          <?php endforeach; ?>
        </div>
      </div>

      <!-- Phân trang -->
      <?php if ($totalPages > 1): ?>
      <div class="pagination">
        <?php $base = "/shop-php/products/find-products.php?keyword=" . urlencode($keyword) . "&sort=$sortOption&page="; ?>
        <?php if ($currentPage > 1): ?>
          <a href="<?= $base.($currentPage-1) ?>"><i class="fas fa-angle-left"></i></a>
        <?php else: ?>
          <span class="disabled"><i class="fas fa-angle-left"></i></span>
        <?php endif; ?>
        <?php for ($p=1;$p<=$totalPages;$p++): ?>
          <?php if ($p===$currentPage): ?><span class="active"><?= $p ?></span>
          <?php elseif ($p===1||$p===$totalPages||abs($p-$currentPage)<=2): ?><a href="<?= $base.$p ?>"><?= $p ?></a>
          <?php elseif (abs($p-$currentPage)===3): ?><span class="disabled">...</span>
          <?php endif; ?>
        <?php endfor; ?>
        <?php if ($currentPage < $totalPages): ?>
          <a href="<?= $base.($currentPage+1) ?>"><i class="fas fa-angle-right"></i></a>
        <?php else: ?>
          <span class="disabled"><i class="fas fa-angle-right"></i></span>
        <?php endif; ?>
      </div>
      <?php endif; ?>
    <?php endif; ?>

  </div>
</main>

<script>
function toggleDropdown(id) {
    document.getElementById(id).classList.toggle('open');
}
document.addEventListener('click', e => {
    if (!e.target.closest('.custom-dropdown'))
        document.querySelectorAll('.custom-dropdown').forEach(d=>d.classList.remove('open'));
});
function changeSort(val) {
    const url = new URL(window.location.href);
    url.searchParams.set('sort', val);
    url.searchParams.set('page', 1);
    window.location.href = url.toString();
}
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>