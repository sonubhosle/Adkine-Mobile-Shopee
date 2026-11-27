<?php
/**
 * Application configuration.
 * Plain PHP defines with optional environment variable overrides.
 */

function config_value(string $key, mixed $default = null): mixed
{
    $environmentValue = getenv($key);
    if ($environmentValue !== false && $environmentValue !== '') {
        return $environmentValue;
    }

    return $default;
}

function config_bool(string $key, bool $default = false): bool
{
    $value = config_value($key, $default ? 'true' : 'false');
    return filter_var($value, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE) ?? $default;
}

define('ROOT_PATH', dirname(__DIR__));

// Database
define('DB_HOST', (string) config_value('DB_HOST', '127.0.0.1'));
define('DB_USER', (string) config_value('DB_USER', 'root'));
define('DB_PASS', (string) config_value('DB_PASS', ''));
define('DB_NAME', (string) config_value('DB_NAME', 'mobileshop2'));
define('DB_PORT', (int) config_value('DB_PORT', 3306));
define('DB_CHARSET', 'utf8mb4');

// Application
define('APP_ENV', (string) config_value('APP_ENV', 'development'));
define('APP_DEBUG', config_bool('APP_DEBUG', true));
define('SITE_URL', rtrim((string) config_value('SITE_URL', 'http://localhost/MobileShop2'), '/'));
define('BASE_PATH', rtrim((string) config_value('BASE_PATH', '/MobileShop2'), '/'));

// Paths and session
define('UPLOAD_DIR', __DIR__ . '/../uploads/');
define('UPLOAD_MAX_SIZE', 5242880);
define('LOGS_PATH', ROOT_PATH . '/logs');
define('SESSION_NAME', (string) config_value('SESSION_NAME', 'APEX_SESSION'));
define('SESSION_LIFETIME', (int) config_value('SESSION_LIFETIME', 86400));

// Authentication
define('OTP_EXPIRY_MINUTES', (int) config_value('OTP_EXPIRY_MINUTES', 10));
define('OTP_MAX_ATTEMPTS', (int) config_value('OTP_MAX_ATTEMPTS', 4));

// Payments
define('RAZORPAY_KEY_ID', (string) config_value('RAZORPAY_KEY_ID', 'rzp_test_1DP5mmOlF5G5ag'));
define('RAZORPAY_KEY_SECRET', (string) config_value('RAZORPAY_KEY_SECRET', 's5y8G1KxK0d6G634iFv603O9'));
define('RAZORPAY_WEBHOOK_SECRET', (string) config_value('RAZORPAY_WEBHOOK_SECRET', 'whsec_ApexSecret2026'));

// Mail
define('SMTP_HOST', (string) config_value('SMTP_HOST', 'smtp.gmail.com'));
define('SMTP_PORT', (int) config_value('SMTP_PORT', 587));
define('SMTP_USERNAME', (string) config_value('SMTP_USERNAME', 'sbhosle1011@gmail.com'));
define('SMTP_PASSWORD', (string) config_value('SMTP_PASSWORD', 'mbtj tlwo luza frzm'));
define('SMTP_FROM_EMAIL', (string) config_value('SMTP_FROM_EMAIL', 'sbhosle1011@gmail.com'));
define('SMTP_FROM_NAME', (string) config_value('SMTP_FROM_NAME', 'Adkine Mobile Shopee'));
define('MAIL_DRIVER', (string) config_value('MAIL_DRIVER', 'smtp'));

if (!ini_get('zlib.output_compression')) {
    ini_set('zlib.output_compression', '1');
    ini_set('zlib.output_compression_level', '6');
}
