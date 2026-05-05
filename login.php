<?php
require_once __DIR__ . '/core/auth.php';
Auth::requireGuest('/shop-php/index.php');

$error      = '';
$redirectTo = $_SESSION['redirect_after_login'] ?? '/shop-php/index.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!Auth::verifyCsrf()) $error = 'Invalid form session.';
    $email    = trim($_POST['email']    ?? '');
    $password = trim($_POST['password'] ?? '');
    if ($error) {
        // CSRF failure already populated the message.
    } elseif (empty($email) || empty($password)) {
        $error = 'Vui lòng nhập đầy đủ email và mật khẩu.';
    } elseif (Auth::attempt($email, $password)) {
        unset($_SESSION['redirect_after_login']);
        Auth::flashSuccess('Đăng nhập thành công!');
        header("Location: $redirectTo"); exit;
    } else {
        $error = 'Email hoặc mật khẩu không đúng.';
    }
}

$pageTitle = 'Đăng nhập';
require_once __DIR__ . '/includes/header.php';
?>
<link rel="stylesheet" href="/shop-php/assets/css/login.css">
<div class="login-wrapper">
  <div class="login-box">
    <h2>Đăng nhập</h2>
    <?php if ($error): ?>
      <div class="flash flash-error" style="margin-bottom:15px;padding:10px;border-radius:4px;"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>
    <form method="POST" action="/shop-php/login.php" id="loginForm">
      <?= Auth::csrfField() ?>
      <input type="email" name="email" placeholder="Email" required value="<?= htmlspecialchars($_POST['email'] ?? '') ?>">
      <input type="password" name="password" placeholder="Mật khẩu" required>
      <button type="submit" id="loginBtn">Đăng nhập</button>
      <div class="forgot-password"><a href="/shop-php/forgot-password.php">Quên mật khẩu?</a></div>
    </form>
    <div class="register-link">Chưa có tài khoản? <a href="/shop-php/register.php">Đăng ký</a></div>
  </div>
</div>
<script>
document.getElementById('loginForm').addEventListener('submit', function() {
    const btn = document.getElementById('loginBtn');
    btn.disabled = true; btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i>';
});
</script>
<?php require_once __DIR__ . '/includes/footer.php'; ?>
