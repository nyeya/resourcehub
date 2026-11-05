<?php
/**
 * Global Helper Functions
 */

if (!function_exists('e')) {
    function e($value) {
        return htmlspecialchars((string)($value ?? ''), ENT_QUOTES, 'UTF-8');
    }
}

if (!function_exists('view')) {
    function view($template, $data = []) {
        extract($data);
        $templatePath = (defined('RH_TEMPLATES') ? RH_TEMPLATES : dirname(__DIR__) . '/templates') . '/' . ltrim($template, '/') . '.php';
        if (!file_exists($templatePath)) {
            throw new RuntimeException("Template [{$template}] not found at {$templatePath}");
        }
        require $templatePath;
    }
}

if (!function_exists('csrf_token')) {
    function csrf_token() {
        if (session_status() !== PHP_SESSION_ACTIVE) {
            Auth::initSession();
        }
        if (empty($_SESSION['_csrf_token'])) {
            $_SESSION['_csrf_token'] = bin2hex(random_bytes(32));
        }
        return $_SESSION['_csrf_token'];
    }
}

if (!function_exists('csrf_field')) {
    function csrf_field() {
        return '<input type="hidden" name="csrf_token" value="' . e(csrf_token()) . '">';
    }
}

if (!function_exists('verify_csrf_token')) {
    function verify_csrf_token($token) {
        if (session_status() !== PHP_SESSION_ACTIVE) {
            Auth::initSession();
        }
        $sessionToken = $_SESSION['_csrf_token'] ?? '';
        if (!$token || !$sessionToken) {
            return false;
        }
        return hash_equals($sessionToken, $token);
    }
}

if (!function_exists('require_post_csrf')) {
    function require_post_csrf($fallbackUrl = 'index.php') {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            if (!verify_csrf_token($_POST['csrf_token'] ?? null)) {
                http_response_code(400);
                flash('danger', 'Security check failed. Please refresh and try again.');
                redirect($fallbackUrl);
            }
        }
    }
}

if (!function_exists('flash')) {
    function flash($type, $message) {
        if (session_status() !== PHP_SESSION_ACTIVE) {
            Auth::initSession();
        }
        $validTypes = ['success', 'danger', 'warning', 'info'];
        $_SESSION['_flashes'][] = [
            'type'    => in_array($type, $validTypes) ? $type : 'info',
            'message' => $message,
        ];
    }
}

if (!function_exists('get_flashes')) {
    function get_flashes() {
        if (session_status() !== PHP_SESSION_ACTIVE) {
            Auth::initSession();
        }
        $flashes = $_SESSION['_flashes'] ?? [];
        $_SESSION['_flashes'] = [];
        return $flashes;
    }
}

if (!function_exists('redirect')) {
    function redirect($url) {
        header('Location: ' . $url);
        exit;
    }
}

if (!function_exists('format_date')) {
    function format_date($date) {
        if (!$date) return '';
        $ts = strtotime($date);
        return $ts ? date('d M Y', $ts) : (string)$date;
    }
}

if (!function_exists('format_datetime')) {
    function format_datetime($datetime) {
        if (!$datetime) return '';
        $ts = strtotime($datetime);
        return $ts ? date('d M Y, H:i', $ts) : (string)$datetime;
    }
}

if (!function_exists('csv_neutralize')) {
    function csv_neutralize($value) {
        $str = (string)($value ?? '');
        if ($str !== '' && in_array($str[0], ['=', '+', '-', '@'], true)) {
            return "'" . $str;
        }
        return $str;
    }
}

if (!function_exists('asset_url')) {
    function asset_url($path) {
        return 'assets/' . ltrim($path, '/');
    }
}

if (!function_exists('base_url')) {
    function base_url($path = '') {
        $script = $_SERVER['SCRIPT_NAME'] ?? '';
        $scriptDir = str_replace('\\', '/', dirname($script));
        $base = ($scriptDir === '/' || $scriptDir === '.' || $scriptDir === '') ? '/' : rtrim($scriptDir, '/') . '/';
        return $base . ltrim($path, '/');
    }
}

/**
 * Clean path URL generator.
 * Generates semantic path URLs (e.g. 'dashboard', 'equipment', 'loans/request', 'equipment/5')
 * with zero query parameters for page routing.
 */
if (!function_exists('url')) {
    function url($route = '', $params = []) {
        $route = trim((string)$route, '/');
        if ($route === '' || $route === 'index') {
            $route = 'dashboard';
        }

        $action = isset($params['action']) ? (string)$params['action'] : null;
        $id = isset($params['id']) ? $params['id'] : null;
        unset($params['action'], $params['id']);

        $path = $route;
        if ($action !== null && $action !== '' && $action !== 'index') {
            if ($id !== null && $id !== '') {
                if ($action === 'view' || $action === 'show') {
                    $path = "{$route}/{$id}";
                } else {
                    $path = "{$route}/{$id}/{$action}";
                }
            } else {
                $path = "{$route}/{$action}";
            }
        } elseif ($id !== null && $id !== '') {
            $path = "{$route}/{$id}";
        }

        $filtered = array_filter($params, fn($v) => $v !== null && $v !== '');
        $query = !empty($filtered) ? '?' . http_build_query($filtered) : '';

        return $path . $query;
    }
}

