<?php
require_once __DIR__ . '/../core/auth.php';
require_once __DIR__ . '/../core/db.php';

$currentLang = $_SESSION['lang'] ?? $_COOKIE['lang'] ?? 'vi';
$catMap = [
    1  => ['name' => 'NAM',      'path' => '/shop-php/products/men.php'],
    2  => ['name' => 'NỮ',       'path' => '/shop-php/products/women.php'],
    3  => ['name' => 'PHỤ KIỆN', 'path' => '/shop-php/products/accessories.php'],
    13 => ['name' => 'SALE',     'path' => '/shop-php/products/sale.php'],
];
$rawCats    = DB::query("SELECT id, name FROM categories WHERE parentCategoryId IS NULL ORDER BY id");
$categories = [];
foreach ($rawCats as $cat) {
    $id = $cat['id'];
    $categories[] = [
        'id'   => $id,
        'name' => $catMap[$id]['name'] ?? $cat['name'],
        'path' => $catMap[$id]['path'] ?? "/shop-php/products/category.php?id=$id",
    ];
}

$user       = Auth::user();
$isLoggedIn = Auth::check();
$isAdmin    = Auth::isAdmin();

// Đếm giỏ hàng
$cartCount = 0;
if ($isLoggedIn) {
    $cartCount = DB::queryOne(
        "SELECT COUNT(*) as total FROM cart c
         JOIN product_skus s ON c.sku_id = s.id
         JOIN products p ON s.product_id = p.id
         WHERE c.user_id = ? AND p.deletedAt IS NULL",
        [$user['id']]
    )['total'] ?? 0;
}
?>
<!DOCTYPE html>
<html lang="<?= htmlspecialchars($currentLang) ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($pageTitle ?? 'PIXCAM', ENT_QUOTES, 'UTF-8') ?></title>
    <meta name="csrf-token" content="<?= htmlspecialchars(Auth::csrfToken(), ENT_QUOTES, 'UTF-8') ?>">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <link rel="stylesheet" href="/shop-php/assets/css/style.css">
    <link rel="stylesheet" href="/shop-php/assets/css/index.css">
    <style>
        .flash { padding:12px 20px; font-size:14px; text-align:center; }
        .flash-error   { background:#f8d7da; color:#842029; }
        .flash-success { background:#d1e7dd; color:#0f5132; }

        header {
            display: grid;
            grid-template-columns: auto 1fr auto;
            align-items: center;
            background: #000;
            padding: 0 40px;
            height: 70px;
            position: sticky;
            top: 0;
            z-index: 999;
            box-shadow: 0 2px 10px rgba(0,0,0,0.3);
        }
        .header-logo a { display:flex; align-items:center; gap:10px; text-decoration:none; }
        .header-logo img { height:40px; width:auto; }
        .header-logo span { color:#ee5022; font-size:20px; font-weight:800; letter-spacing:1px; }

        .header-nav { display:flex; align-items:center; gap:5px; justify-content:center; }
        .header-nav .nav-item { position:relative; }
        .header-nav .nav-link {
            color:#fff; text-decoration:none; font-size:15px; font-weight:600;
            padding:10px 18px; letter-spacing:0.5px; display:block;
            transition: color 0.2s;
        }
        .header-nav .nav-link:hover, .header-nav .nav-link.active { color:#ee5022; }
        .header-nav .nav-link.sale { color:#ee5022; }

        .mega-menu {
            display:none; position:absolute; top:100%; left:0;
            background:#fff; border-radius:6px; box-shadow:0 8px 24px rgba(0,0,0,0.15);
            min-width:180px; padding:8px 0; z-index:1000;
        }
        .nav-item:hover .mega-menu { display:block; }
        .mega-menu a {
            display:block; padding:10px 18px; font-size:13px;
            color:#333; text-decoration:none; transition:background 0.2s;
        }
        .mega-menu a:hover { background:#fff5f0; color:#ee5022; }

        .header-tools { display:flex; align-items:center; gap:12px; justify-content:flex-end; }

        /* Search */
        .search-form { display:flex; align-items:center; background:rgba(255,255,255,0.1); border-radius:20px; padding:0 12px; }
        .search-form input {
            background:none; border:none; outline:none; color:#fff;
            font-size:13px; padding:7px 8px; width:160px;
        }
        .search-form input::placeholder { color:rgba(255,255,255,0.5); }
        .search-form button { background:none; border:none; color:#fff; cursor:pointer; padding:0; }

        /* Cart icon */
        .cart-icon { position:relative; color:#fff; text-decoration:none; padding:6px; }
        .cart-icon:hover { color:#ee5022; }
        .cart-badge {
            position:absolute; top:-2px; right:-4px;
            background:#ee5022; color:#fff; font-size:10px; font-weight:700;
            border-radius:50%; width:16px; height:16px;
            display:flex; align-items:center; justify-content:center;
        }

        /* User dropdown */
        .user-menu { position:relative; }
        .user-btn {
            background:none; border:none; cursor:pointer;
            display:flex; align-items:center; gap:8px; color:#fff;
            padding:4px 8px; border-radius:6px; transition:background 0.2s;
        }
        .user-btn:hover { background:rgba(255,255,255,0.1); }
        .user-btn img { width:32px; height:32px; border-radius:50%; object-fit:cover; border:2px solid #ee5022; }
        .user-btn .user-name { font-size:13px; max-width:100px; overflow:hidden; text-overflow:ellipsis; white-space:nowrap; }

        .user-dropdown {
            display:none; position:absolute; top:calc(100% + 8px); right:0;
            background:#fff; border-radius:8px; box-shadow:0 8px 24px rgba(0,0,0,0.15);
            min-width:200px; overflow:hidden; z-index:1000;
        }
        .user-dropdown.open { display:block; }
        .user-dropdown-header { padding:15px; background:#f9f9f9; border-bottom:1px solid #eee; }
        .user-dropdown-header strong { display:block; font-size:14px; color:#333; }
        .user-dropdown-header span { font-size:12px; color:#999; }
        .user-dropdown a {
            display:flex; align-items:center; gap:10px;
            padding:11px 16px; font-size:13px; color:#333;
            text-decoration:none; transition:background 0.2s;
        }
        .user-dropdown a:hover { background:#fff5f0; color:#ee5022; }
        .user-dropdown a i { width:16px; color:#999; }
        .user-dropdown a:hover i { color:#ee5022; }
        .user-dropdown .divider { height:1px; background:#eee; margin:4px 0; }
        .user-dropdown .logout-link { color:#e74c3c !important; }
        .user-dropdown .logout-link i { color:#e74c3c !important; }

        /* Guest buttons */
        .btn-login {
            padding:7px 16px; border:1px solid rgba(255,255,255,0.4); border-radius:20px;
            color:#fff; text-decoration:none; font-size:13px; transition:all 0.2s;
        }
        .btn-login:hover { background:rgba(255,255,255,0.1); }
        .btn-register {
            padding:7px 16px; background:#ee5022; border-radius:20px;
            color:#fff; text-decoration:none; font-size:13px; transition:background 0.2s;
        }
        .btn-register:hover { background:#d9451b; }

        /* Mobile */
        .hamburger { display:none; background:none; border:none; color:#fff; font-size:20px; cursor:pointer; }
        @media (max-width:900px) {
            .hamburger { display:block; }
            .header-nav { display:none; }
            .search-form input { width:100px; }
            .user-btn .user-name { display:none; }
        }
    </style>
</head>
<body>
<script>
window.CSRF_TOKEN = document.querySelector('meta[name="csrf-token"]')?.content || '';
window.csrfHeaders = function(headers = {}) {
    return Object.assign({'X-CSRF-Token': window.CSRF_TOKEN}, headers);
};
window.escapeHtml = function(value) {
    return String(value ?? '').replace(/[&<>"']/g, function(ch) {
        return {'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#039;'}[ch];
    });
};
document.addEventListener('click', function(event) {
    const logoutLink = event.target.closest('a[href="/shop-php/api/logout.php"]');
    if (!logoutLink) return;
    event.preventDefault();
    const form = document.createElement('form');
    form.method = 'POST';
    form.action = '/shop-php/api/logout.php';
    const input = document.createElement('input');
    input.type = 'hidden';
    input.name = 'csrf_token';
    input.value = window.CSRF_TOKEN;
    form.appendChild(input);
    document.body.appendChild(form);
    form.submit();
});
</script>

<?php Auth::renderFlash(); ?>

<header>
    <!-- Logo -->
    <div class="header-logo">
        <a href="/shop-php/index.php">
            <img src="/shop-php/assets/img/home/logo.png" alt="PIXCAM"
                 onerror="this.style.display='none'; this.nextElementSibling.style.display='block';">
            <span style="display:none;">PIXCAM</span>
        </a>
    </div>

    <!-- Nav -->
    <nav class="header-nav">
        <div class="nav-item">
            <a href="/shop-php/index.php" class="nav-link <?= basename($_SERVER['PHP_SELF'])==='index.php'?'active':'' ?>">HOME</a>
        </div>
        <?php foreach ($categories as $cat): ?>
        <div class="nav-item">
            <a href="<?= htmlspecialchars($cat['path']) ?>"
               class="nav-link <?= $cat['name']==='SALE'?'sale':'' ?>">
                <?= htmlspecialchars($cat['name']) ?>
                <?php if ($cat['id'] !== 13): ?>
                <i class="fas fa-chevron-down" style="font-size:9px;margin-left:3px;"></i>
                <?php endif; ?>
            </a>
            <?php if ($cat['id'] !== 13): ?>
            <div class="mega-menu" id="mega-<?= $cat['id'] ?>">
                <div id="sub-<?= $cat['id'] ?>">
                    <span style="padding:10px 18px;color:#999;font-size:13px;display:block;">Đang tải...</span>
                </div>
            </div>
            <?php endif; ?>
        </div>
        <?php endforeach; ?>
    </nav>

    <!-- Tools -->
    <div class="header-tools">
        <!-- Tìm kiếm -->
        <form class="search-form" action="/shop-php/products/find-products.php" method="GET">
            <input type="text" name="keyword" placeholder="Tìm kiếm..."
                   value="<?= htmlspecialchars($_GET['keyword'] ?? $_GET['q'] ?? '') ?>">
            <button type="submit"><i class="fas fa-search" style="font-size:13px;"></i></button>
        </form>

        <?php if ($isLoggedIn): ?>
        <!-- Giỏ hàng -->
        <a href="/shop-php/cart.php" class="cart-icon" title="Giỏ hàng">
            <i class="fas fa-shopping-bag" style="font-size:20px;"></i>
            <?php if ($cartCount > 0): ?>
            <span class="cart-badge"><?= $cartCount > 99 ? '99+' : $cartCount ?></span>
            <?php endif; ?>
        </a>

        <!-- User dropdown -->
        <div class="user-menu">
            <button class="user-btn" id="userBtn" onclick="toggleUserMenu()">
                <?php if (!empty($user['avatar'])): ?>
                    <img src="<?= htmlspecialchars($user['avatar']) ?>" alt="Avatar">
                <?php else: ?>
                    <i class="fas fa-circle-user" style="font-size:28px;"></i>
                <?php endif; ?>
                <span class="user-name"><?= htmlspecialchars($user['name']) ?></span>
                <i class="fas fa-chevron-down" style="font-size:10px;opacity:0.7;"></i>
            </button>
            <div class="user-dropdown" id="userDropdown">
                <div class="user-dropdown-header">
                    <strong><?= htmlspecialchars($user['name']) ?></strong>
                    <span><?= htmlspecialchars($user['email']) ?></span>
                </div>
                <a href="/shop-php/profile.php"><i class="fas fa-user"></i> Hồ sơ cá nhân</a>
                <a href="/shop-php/cart.php"><i class="fas fa-shopping-bag"></i> Giỏ hàng
                    <?php if ($cartCount > 0): ?>
                    <span style="margin-left:auto;background:#ee5022;color:#fff;border-radius:10px;padding:1px 7px;font-size:11px;"><?= $cartCount ?></span>
                    <?php endif; ?>
                </a>
                <a href="/shop-php/order-history.php"><i class="fas fa-box"></i> Lịch sử mua hàng</a>
                <?php if ($isAdmin): ?>
                <div class="divider"></div>
                <a href="/shop-php/admin/dashboard.php"><i class="fas fa-cog"></i> Quản trị</a>
                <?php endif; ?>
                <div class="divider"></div>
                <a href="/shop-php/api/logout.php" class="logout-link"><i class="fas fa-sign-out-alt"></i> Đăng xuất</a>
            </div>
        </div>

        <?php else: ?>
        <!-- Chưa đăng nhập -->
        <a href="/shop-php/login.php" class="btn-login">Đăng nhập</a>
        <a href="/shop-php/register.php" class="btn-register">Đăng ký</a>
        <?php endif; ?>

        <button class="hamburger" onclick="toggleMobileMenu()">
            <i class="fas fa-bars"></i>
        </button>
    </div>
</header>

<script>
// User dropdown
function toggleUserMenu() {
    document.getElementById('userDropdown').classList.toggle('open');
}
document.addEventListener('click', e => {
    if (!e.target.closest('.user-menu')) {
        document.getElementById('userDropdown')?.classList.remove('open');
    }
});

// Subcategories
let loadedCats = {};
<?php foreach ($categories as $cat): ?>
<?php if ($cat['id'] !== 13): ?>
(function() {
    const navItem = document.querySelector('.nav-item:has(#mega-<?= $cat['id'] ?>)');
    navItem?.addEventListener('mouseenter', () => loadSubs(<?= $cat['id'] ?>));
})();
<?php endif; ?>
<?php endforeach; ?>

async function loadSubs(catId) {
    if (loadedCats[catId]) return;
    loadedCats[catId] = true;
    const pathMap = {1:'/shop-php/products/men.php',2:'/shop-php/products/women.php',3:'/shop-php/products/accessories.php'};
    const base = pathMap[catId] || '/shop-php/products/category.php';
    try {
        const res  = await fetch(`/shop-php/api/subcategories.php?cat_id=${catId}`);
        const data = await res.json();
        const el   = document.getElementById('sub-'+catId);
        if (!el) return;
        if (!data.error && data.data?.length) {
            el.innerHTML = data.data.map(s=>`<a href="${base}?category=${encodeURIComponent(s.id)}">${escapeHtml(s.name)}</a>`).join('');
        } else {
            el.innerHTML = '<span style="padding:10px 18px;color:#999;font-size:13px;display:block;">Không có danh mục con</span>';
        }
    } catch {}
}

// Mobile menu
function toggleMobileMenu() {
    const nav = document.querySelector('.header-nav');
    if (nav.style.display === 'flex') {
        nav.style.display = 'none';
    } else {
        nav.style.cssText = 'display:flex;flex-direction:column;position:fixed;top:65px;left:0;right:0;background:#000;padding:10px 0;z-index:998;';
    }
}
</script>
