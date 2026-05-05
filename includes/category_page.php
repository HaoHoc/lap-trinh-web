<?php
// includes/category_page.php — Template dùng MySQL trực tiếp
require_once __DIR__ . '/../core/auth.php';

$currentPage     = max(1, intval($_GET['page']     ?? 1));
$sortOption      = $_GET['sort']     ?? 'LATEST';
$selectedCatId   = intval($_GET['category'] ?? 0);
$productsPerPage = 12;
$offset          = ($currentPage - 1) * $productsPerPage;

$sortMap = [
    'PRICE_ASC'  => 'p.basePrice ASC',
    'PRICE_DESC' => 'p.basePrice DESC',
    'LATEST'     => 'p.createdAt DESC',
];
$orderSQL = $sortMap[$sortOption] ?? 'p.createdAt DESC';

// Fetch subcategories
$subCategories = DB::query("SELECT * FROM categories WHERE parentCategoryId = ? ORDER BY id", [$parentCategoryId]);

// Build WHERE
if ($selectedCatId > 0) {
    $whereSQL    = "AND pc.category_id = ?";
    $whereParams = [$selectedCatId];
} else {
    $catIds      = array_merge([$parentCategoryId], array_column($subCategories, 'id'));
    $placeholders = implode(',', array_fill(0, count($catIds), '?'));
    $whereSQL    = "AND pc.category_id IN ($placeholders)";
    $whereParams = $catIds;
}

$products = DB::query(
    "SELECT DISTINCT p.* FROM products p
     JOIN product_categories pc ON p.id = pc.product_id
     WHERE p.deletedAt IS NULL $whereSQL ORDER BY $orderSQL LIMIT ? OFFSET ?",
    array_merge($whereParams, [$productsPerPage, $offset])
);

$totalItems = DB::queryOne(
    "SELECT COUNT(DISTINCT p.id) as total FROM products p
     JOIN product_categories pc ON p.id = pc.product_id
     WHERE p.deletedAt IS NULL $whereSQL",
    $whereParams
)['total'] ?? 0;

$totalPages = $totalItems > 0 ? (int)ceil($totalItems / $productsPerPage) : 0;

function formatCurrency(float $p): string { return number_format($p, 0, ',', '.') . ' ₫'; }
function calcDiscount(float $b, float $v): int {
    if ($v <= 0 || $b <= 0 || $v <= $b) return 0;
    return (int)round(($v - $b) / $v * 100);
}
function getSortLabel(string $s): string {
    return ['PRICE_ASC'=>'Giá: thấp → cao','PRICE_DESC'=>'Giá: cao → thấp','LATEST'=>'Mới nhất'][$s] ?? 'Mới nhất';
}

$categoryLabel = 'Tất cả danh mục';
foreach ($subCategories as $cat) {
    if ($cat['id'] === $selectedCatId) { $categoryLabel = $cat['name']; break; }
}

$currentFile = basename($_SERVER['PHP_SELF']);
require_once __DIR__ . '/../includes/header.php';
?>

<link rel="stylesheet" href="/shop-php/assets/css/style.css">
<link rel="stylesheet" href="/shop-php/assets/css/CategoryPage.css">

<main class="content">
  <div class="content_top">
    <div class="contentProducts_navigate">
      <div class="navigate_shopAll">
        <p class="title_navigate">
          <a href="/shop-php/index.php" class="home_navigate">TRANG CHỦ</a> / <?= htmlspecialchars($pageLabel) ?>
        </p>
      </div>
      <div class="filter_shopAlll">
        <p class="filter-results-text">Hiển thị <?= count($products) ?> của <?= $totalItems ?> kết quả</p>
        <div class="filter-dropdowns">
          <?php if (!empty($subCategories)): ?>
          <div class="custom-dropdown" id="catDropdown">
            <div class="selected" onclick="toggleDropdown('catDropdown')">
              <span><?= htmlspecialchars($categoryLabel) ?></span><span>&#9662;</span>
            </div>
            <ul class="options">
              <li onclick="filterCategory(0)">Tất cả danh mục</li>
              <?php foreach ($subCategories as $cat): ?>
              <li onclick="filterCategory(<?= $cat['id'] ?>)"><?= htmlspecialchars($cat['name']) ?></li>
              <?php endforeach; ?>
            </ul>
          </div>
          <?php endif; ?>
          <div class="custom-dropdown" id="sortDropdown">
            <div class="selected" onclick="toggleDropdown('sortDropdown')">
              <span><?= getSortLabel($sortOption) ?></span><span>&#9662;</span>
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

    <?php if (empty($products)): ?>
      <div style="text-align:center;padding:60px 0;color:#999;">
        <i class="fas fa-box-open" style="font-size:48px;margin-bottom:15px;display:block;"></i>
        <p>Chưa có sản phẩm nào trong danh mục này.</p>
      </div>
    <?php else: ?>
    <div class="product_top">
      <div class="products_home">
        <?php foreach ($products as $product):
          $images     = json_decode($product['images'] ?? '[]', true);
          $img        = $images[0] ?? '';
          $discount   = calcDiscount($product['basePrice'], $product['virtualPrice']);
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
    <?php endif; ?>

    <?php if ($totalPages > 1): ?>
    <div class="pagination">
      <?php $base = "/shop-php/products/{$currentFile}?sort={$sortOption}&category={$selectedCatId}&page="; ?>
      <?php if ($currentPage > 1): ?>
        <a href="<?= $base.($currentPage-1) ?>"><i class="fas fa-angle-left"></i></a>
      <?php else: ?>
        <span class="disabled"><i class="fas fa-angle-left"></i></span>
      <?php endif; ?>
      <?php for ($pg = 1; $pg <= $totalPages; $pg++): ?>
        <?php if ($pg===$currentPage): ?><span class="active"><?= $pg ?></span>
        <?php elseif ($pg===1||$pg===$totalPages||abs($pg-$currentPage)<=2): ?><a href="<?= $base.$pg ?>"><?= $pg ?></a>
        <?php elseif (abs($pg-$currentPage)===3): ?><span class="disabled">...</span>
        <?php endif; ?>
      <?php endfor; ?>
      <?php if ($currentPage < $totalPages): ?>
        <a href="<?= $base.($currentPage+1) ?>"><i class="fas fa-angle-right"></i></a>
      <?php else: ?>
        <span class="disabled"><i class="fas fa-angle-right"></i></span>
      <?php endif; ?>
    </div>
    <?php endif; ?>
  </div>
</main>

<script>
function toggleDropdown(id) {
    document.getElementById(id).classList.toggle('open');
    document.querySelectorAll('.custom-dropdown').forEach(d => { if(d.id!==id) d.classList.remove('open'); });
}
document.addEventListener('click', e => {
    if (!e.target.closest('.custom-dropdown')) document.querySelectorAll('.custom-dropdown').forEach(d=>d.classList.remove('open'));
});
function filterCategory(id) {
    const url=new URL(window.location.href); url.searchParams.set('category',id); url.searchParams.set('page',1); window.location.href=url.toString();
}
function changeSort(val) {
    const url=new URL(window.location.href); url.searchParams.set('sort',val); url.searchParams.set('page',1); window.location.href=url.toString();
}
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>