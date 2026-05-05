<?php
require_once __DIR__ . '/core/auth.php';
Auth::requireGuest('/shop-php/index.php');

$error = $success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!Auth::verifyCsrf()) $error = 'Invalid form session.';
    $name     = trim($_POST['name']     ?? '');
    $email    = trim($_POST['email']    ?? '');
    $phone    = trim($_POST['phone']    ?? '');
    $password = trim($_POST['password'] ?? '');
    $confirm  = trim($_POST['confirm']  ?? '');

    if ($error) {
        // CSRF failure already populated the message.
    } elseif (!$name || !$email || !$password || !$confirm) {
        $error = 'Vui lòng nhập đầy đủ thông tin bắt buộc.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Email không hợp lệ.';
    } elseif (strlen($password) < 6) {
        $error = 'Mật khẩu phải có ít nhất 6 ký tự.';
    } elseif ($password !== $confirm) {
        $error = 'Mật khẩu xác nhận không khớp.';
    } elseif (DB::queryOne("SELECT id FROM users WHERE email = ?", [$email])) {
        $error = 'Email này đã được đăng ký.';
    } else {
        $hash   = password_hash($password, PASSWORD_BCRYPT);
        DB::execute(
            "INSERT INTO users (name, email, password, phoneNumber, role_id, status) VALUES (?, ?, ?, ?, 2, 'ACTIVE')",
            [$name, $email, $hash, $phone]
        );
        // Tự động đăng nhập sau khi đăng ký
        Auth::attempt($email, $password);
        Auth::flashSuccess('Đăng ký thành công! Chào mừng ' . $name . '!');
        header('Location: /shop-php/index.php');
        exit;
    }
}

$pageTitle = 'Đăng ký tài khoản - PIXCAM';
require_once __DIR__ . '/includes/header.php';
?>

<link rel="stylesheet" href="/shop-php/assets/css/login.css">

<div class="login-wrapper">
  <div class="login-box" style="max-width:480px;">
    <h2>Tạo tài khoản</h2>

    <?php if ($error): ?>
    <div class="flash flash-error" style="margin-bottom:15px;padding:10px;border-radius:4px;background:#fdecea;color:#c0392b;">
      <?= htmlspecialchars($error) ?>
    </div>
    <?php endif; ?>

    <form method="POST" action="/shop-php/register.php" id="registerForm">
      <?= Auth::csrfField() ?>
      <div style="margin-bottom:12px;">
        <input type="text" name="name" placeholder="Họ và tên *" required
               value="<?= htmlspecialchars($_POST['name'] ?? '') ?>"
               style="width:100%;padding:12px;border:1px solid #ddd;border-radius:6px;font-size:14px;box-sizing:border-box;">
      </div>
      <div style="margin-bottom:12px;">
        <input type="email" name="email" placeholder="Email *" required
               value="<?= htmlspecialchars($_POST['email'] ?? '') ?>"
               style="width:100%;padding:12px;border:1px solid #ddd;border-radius:6px;font-size:14px;box-sizing:border-box;">
      </div>
      <div style="margin-bottom:12px;">
        <input type="tel" name="phone" placeholder="Số điện thoại"
               value="<?= htmlspecialchars($_POST['phone'] ?? '') ?>"
               style="width:100%;padding:12px;border:1px solid #ddd;border-radius:6px;font-size:14px;box-sizing:border-box;">
      </div>
      <div style="margin-bottom:12px;">
        <input type="password" name="password" placeholder="Mật khẩu * (ít nhất 6 ký tự)" required
               style="width:100%;padding:12px;border:1px solid #ddd;border-radius:6px;font-size:14px;box-sizing:border-box;">
      </div>
      <div style="margin-bottom:20px;">
        <input type="password" name="confirm" placeholder="Xác nhận mật khẩu *" required
               style="width:100%;padding:12px;border:1px solid #ddd;border-radius:6px;font-size:14px;box-sizing:border-box;">
      </div>
      <button type="submit" id="registerBtn"
              style="width:100%;padding:13px;background:#ee5022;color:#fff;border:none;border-radius:6px;font-size:16px;font-weight:600;cursor:pointer;">
        Đăng ký
      </button>
    </form>

    <div class="register-link" style="text-align:center;margin-top:15px;font-size:14px;">
      Đã có tài khoản? <a href="/shop-php/login.php" style="color:#ee5022;">Đăng nhập</a>
    </div>
  </div>
</div>

<script>
document.getElementById('registerForm').addEventListener('submit', function() {
    const btn = document.getElementById('registerBtn');
    btn.disabled = true; btn.textContent = 'Đang xử lý...';
});
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
