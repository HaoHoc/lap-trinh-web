<?php
// ============================================================
//  index.php — Trang chủ dùng MySQL trực tiếp
// ============================================================
require_once __DIR__ . '/core/auth.php';

// Fetch sản phẩm mới nhất (thay productApi.getNewProducts)
$products = DB::query(
    "SELECT p.*, 
            (SELECT JSON_UNQUOTE(JSON_EXTRACT(p2.images, '$[0]')) 
             FROM products p2 WHERE p2.id = p.id) as firstImage
     FROM products p 
     WHERE p.deletedAt IS NULL 
     ORDER BY p.createdAt DESC 
     LIMIT 10"
);

function formatCurrency(float $amount): string {
    return number_format($amount, 0, ',', '.') . ' ₫';
}

$pageTitle = 'Trang chủ - PIXCAM';
require_once __DIR__ . '/includes/header.php';
?>

<link rel="stylesheet" href="/shop-php/assets/css/home.css">
<style>
/* Fix slider arrows - center vertically */
.hero-swiper {
    position: relative;
}
.hero-swiper .swiper-button-prev,
.hero-swiper .swiper-button-next {
    top: 50% !important;
    transform: translateY(-50%) !important;
    width: 48px !important;
    height: 48px !important;
    background: rgba(255,255,255,0.15) !important;
    backdrop-filter: blur(4px) !important;
    border-radius: 50% !important;
    border: 2px solid rgba(255,255,255,0.4) !important;
    transition: all 0.3s !important;
}
.hero-swiper .swiper-button-prev:hover,
.hero-swiper .swiper-button-next:hover {
    background: rgba(238,80,34,0.8) !important;
    border-color: #ee5022 !important;
}
.hero-swiper .swiper-button-prev::after,
.hero-swiper .swiper-button-next::after {
    font-size: 16px !important;
    color: white !important;
    font-weight: 900 !important;
}
.hero-swiper .swiper-button-prev { left: 20px !important; }
.hero-swiper .swiper-button-next { right: 20px !important; }
.slider-container { position: relative; overflow: hidden; }
.slider-container img { width: 100%; height: 500px; object-fit: cover; }

/* Fix poster/product swiper arrows */
.poster-swiper .swiper-button-prev,
.poster-swiper .swiper-button-next,
.product-swiper .swiper-button-prev,
.product-swiper .swiper-button-next {
    top: 50% !important;
    transform: translateY(-50%) !important;
    width: 40px !important;
    height: 40px !important;
    background: rgba(0,0,0,0.5) !important;
    border-radius: 50% !important;
    border: 2px solid rgba(255,255,255,0.3) !important;
    transition: all 0.3s !important;
}
.poster-swiper .swiper-button-prev:hover,
.poster-swiper .swiper-button-next:hover,
.product-swiper .swiper-button-prev:hover,
.product-swiper .swiper-button-next:hover {
    background: rgba(238,80,34,0.9) !important;
    border-color: #ee5022 !important;
}
.poster-swiper .swiper-button-prev::after,
.poster-swiper .swiper-button-next::after,
.product-swiper .swiper-button-prev::after,
.product-swiper .swiper-button-next::after {
    font-size: 14px !important;
    color: white !important;
    font-weight: 900 !important;
}
.poster-swiper { padding: 0 50px !important; }
.product-swiper { padding: 0 50px !important; }
</style>
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/swiper@11/swiper-bundle.min.css">

<main class="content home-page">

    <!-- Hero Slider -->
    <section class="slider-container">
        <div class="swiper hero-swiper">
            <div class="swiper-wrapper">
                <div class="swiper-slide slide">
                    <img src="/shop-php/assets/img/home/banner3.jpg" alt="Banner 1">
                </div>
                <div class="swiper-slide slide">
                    <img src="/shop-php/assets/img/home/banner1.jpg" alt="Banner 2">
                </div>
                <div class="swiper-slide slide">
                    <img src="/shop-php/assets/img/home/banner2.jpg" alt="Banner 3">
                </div>
            </div>
            <div class="swiper-pagination"></div>
            <div class="swiper-button-prev"></div>
            <div class="swiper-button-next"></div>
        </div>
    </section>

    <!-- Review Categories -->
    <section class="review_home">
        <div class="item_review" style="background-image: url('/shop-php/assets/img/home/review1.jpg')">
            <p>PHONG CÁCH NAM</p>
        </div>
        <div class="item_review" style="background-image: url('/shop-php/assets/img/home/review2.jpg')">
            <p>PHONG CÁCH NỮ</p>
        </div>
        <div class="item_review" style="background-image: url('/shop-php/assets/img/home/review_3.jpg')">
            <p>ĐIỂM NHẤN TINH TẾ</p>
        </div>
    </section>

    <!-- Sản phẩm mới -->
    <section class="products-section">
        <h1 class="section-title">HÀNG MỚI VỀ</h1>
        <?php if (empty($products)): ?>
            <div class="no-products">
                <p>Chưa có sản phẩm nào. <a href="/shop-php/admin/products.php">Thêm sản phẩm</a></p>
            </div>
        <?php else: ?>
        <div class="products-carousel-wrapper">
            <div class="swiper product-swiper">
                <div class="swiper-wrapper">
                    <?php foreach ($products as $product):
                        $images = json_decode($product['images'] ?? '[]', true);
                        $img    = $images[0] ?? 'https://via.placeholder.com/300x400/e0e0e0/666?text=No+Image';
                        $hasDiscount = $product['virtualPrice'] > $product['basePrice'];
                    ?>
                    <div class="swiper-slide product-item">
                        <a href="/shop-php/products/detail.php?id=<?= $product['id'] ?>" class="product-link">
                            <img src="<?= htmlspecialchars($img) ?>"
                                 alt="<?= htmlspecialchars($product['name']) ?>"
                                 class="image_product"
                                 onerror="this.src='https://via.placeholder.com/300x400/e0e0e0/666?text=No+Image'">
                            <div class="product-overlay">
                                <h4 class="product-name"><?= htmlspecialchars($product['name']) ?></h4>
                                <p class="product-price">
                                    <?php if ($hasDiscount): ?>
                                        <span class="price-sale"><?= formatCurrency($product['basePrice']) ?></span>
                                        <span class="price-original"><?= formatCurrency($product['virtualPrice']) ?></span>
                                    <?php else: ?>
                                        <span class="price-normal"><?= formatCurrency($product['virtualPrice']) ?></span>
                                    <?php endif; ?>
                                </p>
                            </div>
                        </a>
                    </div>
                    <?php endforeach; ?>
                </div>
                <div class="swiper-button-prev"></div>
                <div class="swiper-button-next"></div>
            </div>
        </div>
        <?php endif; ?>
    </section>

    <!-- Poster Carousel -->
    <section class="poster-section">
        <h1 class="section-title">Khám Phá Phong Cách Của Bạn</h1>
        <div class="poster-carousel-wrapper">
            <div class="swiper poster-swiper">
                <div class="swiper-wrapper">
                    <?php for ($i = 1; $i <= 5; $i++): ?>
                    <div class="swiper-slide poster-item">
                        <img src="/shop-php/assets/img/poster/hinh<?= $i ?>.jpg"
                             alt="Poster <?= $i ?>" class="image_poster">
                    </div>
                    <?php endfor; ?>
                </div>
                <div class="swiper-button-prev"></div>
                <div class="swiper-button-next"></div>
            </div>
        </div>
    </section>

    <!-- About Us -->
    <section class="about-us">
        <div class="about-content">
            <h2>Về Chúng Tôi</h2>
            <p>Chào mừng bạn đến với <strong>PIXCAM</strong> – nơi phong cách và cá tính được tôn vinh.</p>
            <p><strong>PIXCAM</strong> là người bạn đồng hành cùng bạn định hình phong cách mỗi ngày.</p>
        </div>
        <div class="about-image">
            <img src="/shop-php/assets/img/home/about.jpg" alt="PIXCAM">
        </div>
    </section>

    <!-- Fashion Inspiration -->
    <section class="fashion-inspiration">
        <h2 class="section-title">Góc Phối Đồ Cá Tính</h2>
        <div class="inspiration-cards">
            <a href="/shop-php/products/men.php" class="card">
                <img src="/shop-php/assets/img/poster/fashion1.jpg" alt="Nam">
                <div class="card-text">
                    <h3>For Him — Tối Giản &amp; Mạnh Mẽ</h3>
                    <p class="meta-info">Khám phá phong cách nam →</p>
                </div>
            </a>
            <a href="/shop-php/products/women.php" class="card">
                <img src="/shop-php/assets/img/poster/fashion2.jpg" alt="Nữ">
                <div class="card-text">
                    <h3>For Her — Thanh Lịch &amp; Cá Tính</h3>
                    <p class="meta-info">Gu thời trang nữ →</p>
                </div>
            </a>
            <a href="/shop-php/products/accessories.php" class="card">
                <img src="/shop-php/assets/img/poster/fashion3.jpg" alt="Phụ kiện">
                <div class="card-text">
                    <h3>Accessories — Điểm Nhấn Đắt Giá</h3>
                    <p class="meta-info">Phụ kiện tạo chất →</p>
                </div>
            </a>
        </div>
    </section>

    <!-- Charity -->
    <section class="charity-banner">
        <div class="charity-text">
            <h2>PIXCAM &amp; Hành Trình Yêu Thương</h2>
            <p><strong>PIXCAM</strong> trích một phần lợi nhuận để đồng hành cùng các hoạt động thiện nguyện.</p>
            <p class="highlight">✦ Mua sắm có ý nghĩa — Mặc đẹp và lan tỏa điều tốt đẹp ✦</p>
        </div>
        <div class="charity-image">
            <img src="/shop-php/assets/img/poster/thiennguyen2.jpg" alt="Thiện nguyện">
        </div>
    </section>

</main>

<script src="https://cdn.jsdelivr.net/npm/swiper@11/swiper-bundle.min.js"></script>
<script>
new Swiper('.hero-swiper', {
    loop: true, autoplay: { delay: 4000 }, speed: 500,
    pagination: { el: '.hero-swiper .swiper-pagination', clickable: true },
    navigation: { nextEl: '.hero-swiper .swiper-button-next', prevEl: '.hero-swiper .swiper-button-prev' },
});
new Swiper('.product-swiper', {
    loop: false, speed: 500, slidesPerView: 4, spaceBetween: 16,
    navigation: { nextEl: '.product-swiper .swiper-button-next', prevEl: '.product-swiper .swiper-button-prev' },
    breakpoints: { 0:{slidesPerView:1}, 768:{slidesPerView:2}, 1024:{slidesPerView:3}, 1280:{slidesPerView:4} },
});
new Swiper('.poster-swiper', {
    loop: true, autoplay: { delay: 3000 }, speed: 500, slidesPerView: 4, spaceBetween: 16,
    navigation: { nextEl: '.poster-swiper .swiper-button-next', prevEl: '.poster-swiper .swiper-button-prev' },
    breakpoints: { 0:{slidesPerView:1}, 768:{slidesPerView:2}, 1024:{slidesPerView:3}, 1280:{slidesPerView:4} },
});
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>