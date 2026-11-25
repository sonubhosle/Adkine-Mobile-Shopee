# Production deployment checklist

## Required runtime

- PHP 8.2+ with `mysqli`/`mysqlnd`, `curl`, `openssl`, `mbstring`, `json`, and `session` enabled.
- Apache with `mod_rewrite`, `mod_headers`, `mod_expires`, and HTTPS enabled.
- MySQL with a dedicated application user limited to the MobileShop database. Do not use the MySQL root account.
- Run `npm ci` followed by `npm run build:css` before publishing the application files.

## Configuration

Set these process environment variables in the web-server or hosting control panel. Do not put their values in Git:

```text
APP_ENV=production
APP_DEBUG=false
SITE_URL=https://your-domain.example
BASE_PATH=
DB_HOST=127.0.0.1
DB_PORT=3306
DB_NAME=mobileshop2
DB_USER=your_restricted_database_user
DB_PASS=your_database_password
RAZORPAY_KEY_ID=rzp_live_...
RAZORPAY_KEY_SECRET=...
RAZORPAY_WEBHOOK_SECRET=...
SMTP_HOST=...
SMTP_PORT=587
SMTP_USERNAME=...
SMTP_PASSWORD=...
SMTP_FROM_EMAIL=...
SMTP_FROM_NAME=Adkine Mobile Shopee
```

For local development, copy `includes/config.local.example.php` to `includes/config.local.php` and set local-only values.

## Before going live

1. Rotate the previously committed SMTP and Razorpay credentials in their provider dashboards.
2. Configure Razorpay's webhook to `https://your-domain.example/actions/payment-webhook.php` with the same webhook secret.
3. Import the schema and run all migrations using the production database account.
4. Confirm the live payment flow, password-reset email, COD order flow, upload handling, and repair-estimate flow on HTTPS.
5. Test English and Marathi at 320px, 375px, 768px, and desktop widths.
