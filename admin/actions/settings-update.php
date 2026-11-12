<?php
/**
 * MobileShopFlat — POST-only: Settings Update
 * Receives: settings[key] = value array
 */

require_once __DIR__ . '/../../includes/admin-header.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect(url('/admin/settings.php'));
}

verify_csrf();

$data = $_POST['settings'] ?? [];
$tab  = trim((string)($_POST['redirect_tab'] ?? ''));

if (!is_array($data)) {
    set_flash('error', 'Invalid settings payload.');
    redirect(url('/admin/settings.php' . ($tab ? '?tab=' . urlencode($tab) : '')));
}

$updated = 0;
foreach ($data as $key => $value) {
    if (!is_string($key) || !is_scalar($value)) continue;

    $key   = trim($key);
    $value = trim((string)$value);

    if ($key === '' || strlen($value) > 10000) continue;

    // Only update keys that actually exist (security: no arbitrary key injection)
    $exists = db_fetch_value("SELECT id FROM settings WHERE setting_key = ?", [$key]);
    if (!$exists) continue;

    // For sensitive fields: skip if value is empty (keep old value)
    $sensitive = (bool)preg_match('/secret|password|pass|token|api[_-]?key/i', $key);
    if ($sensitive && $value === '') continue;

    db_query("UPDATE settings SET setting_value = ? WHERE setting_key = ?", [$value, $key]);
    $updated++;
}

settings_cache_clear();

set_flash('success', "Settings saved. {$updated} value(s) updated.");
redirect(url('/admin/settings.php' . ($tab ? '?tab=' . urlencode($tab) : '')));

