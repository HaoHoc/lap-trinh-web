<?php
require_once __DIR__ . '/db.php';

class Auth {

    public static function user(): ?array {
        return $_SESSION['user'] ?? null;
    }

    public static function check(): bool {
        return self::user() !== null;
    }

    public static function isAdmin(): bool {
        $user = self::user();
        return $user && ($user['role_name'] ?? '') === 'ADMIN';
    }

    public static function requireLogin(string $redirect = '/shop-php/login.php'): void {
        if (!self::check()) {
            $_SESSION['redirect_after_login'] = $_SERVER['REQUEST_URI'];
            self::flashError('Vui lòng đăng nhập để tiếp tục.');
            header("Location: $redirect");
            exit;
        }
    }

    public static function requireAdmin(): void {
        self::requireLogin();
        if (!self::isAdmin()) {
            header('Location: /shop-php/index.php');
            exit;
        }
    }

    public static function requireGuest(string $redirect = '/shop-php/index.php'): void {
        if (self::check()) {
            header("Location: $redirect");
            exit;
        }
    }

    // ── Đăng nhập: kiểm tra DB + lưu session ──
    public static function attempt(string $email, string $password): bool {
        $user = DB::queryOne(
            "SELECT u.*, r.name as role_name
             FROM users u JOIN roles r ON u.role_id = r.id
             WHERE u.email = ? AND u.status = 'ACTIVE'",
            [$email]
        );
        if (!$user || !password_verify($password, $user['password'])) return false;
        unset($user['password']);
        session_regenerate_id(true);
        $_SESSION['user'] = $user;
        return true;
    }

    public static function logout(): void {
        $_SESSION = [];
        if (ini_get('session.use_cookies')) {
            $params = session_get_cookie_params();
            setcookie(session_name(), '', time() - 42000, $params['path'], $params['domain'], $params['secure'], $params['httponly']);
        }
        session_destroy();
        header('Location: /shop-php/login.php'); exit;
    }

    public static function csrfToken(): string {
        if (empty($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        }
        return $_SESSION['csrf_token'];
    }

    public static function csrfField(): string {
        return '<input type="hidden" name="csrf_token" value="' .
            htmlspecialchars(self::csrfToken(), ENT_QUOTES, 'UTF-8') . '">';
    }

    public static function verifyCsrf(?string $token = null): bool {
        if ($token === null) {
            $token = $_SERVER['HTTP_X_CSRF_TOKEN'] ?? ($_POST['csrf_token'] ?? '');
            if ($token === '') {
                $raw = file_get_contents('php://input');
                $body = json_decode($raw, true);
                if (is_array($body)) $token = $body['csrf_token'] ?? '';
            }
        }
        return is_string($token) && hash_equals($_SESSION['csrf_token'] ?? '', $token);
    }

    public static function requireCsrf(?string $token = null): void {
        if (!self::verifyCsrf($token)) {
            http_response_code(403);
            header('Content-Type: application/json');
            echo json_encode(['error' => true, 'message' => 'CSRF token không hợp lệ.']);
            exit;
        }
    }

    public static function flashError(string $msg): void   { $_SESSION['flash_error']   = $msg; }
    public static function flashSuccess(string $msg): void { $_SESSION['flash_success'] = $msg; }

    public static function renderFlash(): void {
        foreach (['flash_error' => 'flash-error', 'flash_success' => 'flash-success'] as $key => $cls) {
            if (!empty($_SESSION[$key])) {
                echo '<div class="flash ' . $cls . '">' . htmlspecialchars($_SESSION[$key]) . '</div>';
                unset($_SESSION[$key]);
            }
        }
    }
}
