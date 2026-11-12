<?php
/**
 * MobileShopFlat — Application Configuration
 * Plain PHP defines loaded from .env values.
 * Do NOT expose this file via web; protected by .htaccess.
 */

// ─── Database ────────────────────────────────────────────────────────────────
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'mobileshop2');
define('DB_PORT',    3306);
define('DB_CHARSET', 'utf8mb4');

// ─── Application ─────────────────────────────────────────────────────────────
define('SITE_URL',   'http://localhost/MobileShop2');
define('BASE_PATH',  '/MobileShop2');
define('APP_DEBUG',  true);
define('APP_ENV',    'development');
define('ROOT_PATH',  dirname(__DIR__));

// ─── Output Compression & Performance ────────────────────────────────────────
if (!ini_get('zlib.output_compression')) {
    ini_set('zlib.output_compression', '1');
    ini_set('zlib.output_compression_level', '6');
}

// ─── Paths ───────────────────────────────────────────────────────────────────
define('UPLOAD_DIR',  __DIR__ . '/../uploads/');
define('UPLOAD_MAX_SIZE', 5242880); // 5 MB
define('LOGS_PATH',  ROOT_PATH . '/logs');

// ─── Session ─────────────────────────────────────────────────────────────────
define('SESSION_NAME',     'APEX_SESSION');
define('SESSION_LIFETIME', 86400); // 24 hours

// ─── OTP ─────────────────────────────────────────────────────────────────────
define('OTP_EXPIRY_MINUTES', 10);
define('OTP_MAX_ATTEMPTS',    4);

// ─── Razorpay ────────────────────────────────────────────────────────────────
define('RAZORPAY_KEY_ID',         'rzp_test_1DP5mmOlF5G5ag');
define('RAZORPAY_KEY_SECRET',     's5y8G1KxK0d6G634iFv603O9');
define('RAZORPAY_WEBHOOK_SECRET', 'whsec_ApexSecret2026');

// ─── SMTP / Mail ─────────────────────────────────────────────────────────────
define('SMTP_HOST',       'smtp.gmail.com');
define('SMTP_PORT',       587);
define('SMTP_USERNAME',   'sbhosle1011@gmail.com');   // Set your Gmail / SMTP username
define('SMTP_PASSWORD',   'mbtj tlwo luza frzm');   // Set your App Password
define('SMTP_FROM_EMAIL', 'sbhosle1011@gmail.com');
define('SMTP_FROM_NAME',  'Adkine Mobile Shopee');
define('MAIL_DRIVER',     'smtp');
