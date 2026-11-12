<?php
/**
 * MobileShopFlat — Global Helper Functions
 */

// ─── Output Escaping ─────────────────────────────────────────────────────────

function e(string $v): string
{
    return htmlspecialchars($v, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

// ─── URL Helpers ─────────────────────────────────────────────────────────────

function url(string $path = ''): string
{
    return BASE_PATH . '/' . ltrim($path, '/');
}

function absolute_url(string $path = ''): string
{
    if (str_starts_with($path, 'http://') || str_starts_with($path, 'https://')) {
        return $path;
    }
    $base = defined('SITE_URL') ? rtrim(SITE_URL, '/') : 'http://localhost/MobileShop2';
    if (defined('BASE_PATH') && BASE_PATH !== '' && str_starts_with($path, BASE_PATH)) {
        $path = substr($path, strlen(BASE_PATH));
    }
    return $base . '/' . ltrim($path, '/');
}

function asset(string $path): string
{
    return BASE_PATH . '/' . ltrim($path, '/');
}

// ─── Currency ────────────────────────────────────────────────────────────────

function format_currency(float|int|string $amount): string
{
    return '₹' . number_format((float)$amount, 2, '.', ',');
}

// ─── Old Input (Repopulate Forms) ─────────────────────────────────────────────

function old(string $field, mixed $default = ''): mixed
{
    return $_POST[$field] ?? $_GET[$field] ?? $default;
}

// ─── CSRF ────────────────────────────────────────────────────────────────────

function csrf_token(): string
{
    if (empty($_SESSION['_csrf_token'])) {
        $_SESSION['_csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['_csrf_token'];
}

function csrf_field(): string
{
    return '<input type="hidden" name="_csrf_token" value="' . e(csrf_token()) . '">';
}

function verify_csrf(): bool
{
    $token = $_POST['_csrf_token'] ?? '';
    if (empty($token) || empty($_SESSION['_csrf_token'])) {
        return false;
    }
    return hash_equals($_SESSION['_csrf_token'], $token);
}

// ─── Settings (cached DB lookup with persistent file cache) ─────────────

function settings_cache_clear(): void
{
    $cacheFile = (defined('ROOT_PATH') ? ROOT_PATH : dirname(__DIR__)) . '/cache/settings_cache.php';
    if (file_exists($cacheFile)) {
        @unlink($cacheFile);
    }
}

function setting(string $key, mixed $default = null): mixed
{
    static $cache = null;

    if ($cache === null) {
        $cacheFile = (defined('ROOT_PATH') ? ROOT_PATH : dirname(__DIR__)) . '/cache/settings_cache.php';
        if (file_exists($cacheFile) && (time() - filemtime($cacheFile) < 86400)) {
            $loaded = @include $cacheFile;
            if (is_array($loaded)) {
                $cache = $loaded;
            }
        }

        if ($cache === null) {
            try {
                $rows  = db_fetch_all("SELECT `setting_key`, `setting_value` FROM settings");
                $cache = [];
                foreach ($rows as $row) {
                    $cache[$row['setting_key']] = $row['setting_value'];
                }
                $cacheDir = dirname($cacheFile);
                if (is_dir($cacheDir) && is_writable($cacheDir)) {
                    $content = "<?php\n// Auto-generated settings cache\nreturn " . var_export($cache, true) . ";\n";
                    @file_put_contents($cacheFile, $content, LOCK_EX);
                }
            } catch (\Throwable $e) {
                $cache = [];
            }
        }
    }

    return $cache[$key] ?? $default;
}

// ─── Request Helpers ─────────────────────────────────────────────────────────

function is_ajax(): bool
{
    return (($_SERVER['HTTP_X_REQUESTED_WITH'] ?? '') === 'XMLHttpRequest');
}

function redirect(string $url): void
{
    // Prepend BASE_PATH if the URL is relative (starts with /)
    if (str_starts_with($url, '/') && !str_starts_with($url, BASE_PATH)) {
        $url = BASE_PATH . $url;
    }
    header('Location: ' . $url, true, 302);
    exit;
}

function json_response(array $data, int $code = 200): void
{
    http_response_code($code);
    header('Content-Type: application/json; charset=UTF-8');
    echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

// ─── Product Helpers ─────────────────────────────────────────────────────────

function has_variants(int $productId): bool
{
    $count = db_fetch_value(
        "SELECT COUNT(*) FROM product_variants WHERE product_id = ? AND is_active = 1",
        [$productId]
    );
    return (int)$count > 0;
}

// ─── Network ─────────────────────────────────────────────────────────────────

function get_client_ip(): string
{
    return $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
}
