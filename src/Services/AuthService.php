<?php
/**
 * Authentication and Session Management Service
 * Provides credential verification, session management, and RBAC permission checks.
 */

class AuthService {
    public static function initSession() {
        if (session_status() === PHP_SESSION_NONE) {
            session_set_cookie_params([
                'lifetime' => 86400,
                'path'     => '/',
                'domain'   => '',
                'secure'   => !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off',
                'httponly' => true,
                'samesite' => 'Lax',
            ]);
            session_start();
        }
    }

    public static function attempt($email, $password) {
        $db = Database::getConnection();
        $stmt = $db->prepare("SELECT * FROM users WHERE LOWER(email) = LOWER(:email) LIMIT 1");
        $stmt->execute([':email' => trim($email)]);
        $user = $stmt->fetch();

        // Check user existence, active status, and password
        if (!$user || empty($user['is_active'])) {
            return false;
        }

        if (!password_verify($password, $user['password_hash'])) {
            return false;
        }

        self::initSession();
        session_regenerate_id(true);

        $_SESSION['user_id'] = (int)$user['id'];
        $_SESSION['user_email'] = $user['email'];
        $_SESSION['user_name'] = $user['name'];
        $_SESSION['user_role'] = $user['role'];

        return true;
    }

    public static function logout() {
        self::initSession();
        $_SESSION = [];
        if (ini_get("session.use_cookies")) {
            $params = session_get_cookie_params();
            setcookie(
                session_name(),
                '',
                time() - 42000,
                $params["path"],
                $params["domain"],
                $params["secure"],
                $params["httponly"]
            );
        }
        session_destroy();
    }

    public static function check() {
        return !empty($_SESSION['user_id']);
    }

    public static function id() {
        return $_SESSION['user_id'] ?? null;
    }

    public static function role() {
        return $_SESSION['user_role'] ?? null;
    }

    public static function name() {
        return $_SESSION['user_name'] ?? null;
    }

    public static function email() {
        return $_SESSION['user_email'] ?? null;
    }

    public static function hasRole($roles) {
        if (!self::check()) {
            return false;
        }
        $currentRole = self::role();
        if (is_array($roles)) {
            return in_array($currentRole, $roles, true);
        }
        return $currentRole === $roles;
    }

    public static function requireLogin() {
        self::initSession();
        if (!self::check()) {
            flash('warning', 'Please sign in to access that page.');
            redirect(url('login'));
        }
    }

    public static function requireRole($roles) {
        self::requireLogin();
        if (!self::hasRole($roles)) {
            http_response_code(403);
            view('errors/403');
            exit;
        }
    }

    public static function isAdministrator() {
        return self::hasRole('administrator');
    }

    public static function isCoordinator() {
        return self::hasRole(['coordinator', 'administrator']);
    }

    public static function isStaff() {
        return self::hasRole('staff');
    }
}

// Aliased as Auth for convenient backward and forward usage
class Auth extends AuthService {}

