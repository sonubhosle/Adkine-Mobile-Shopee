<?php
/**
 * MobileShopFlat — Admin Settings
 */

require_once __DIR__ . '/../includes/admin-header.php';

// ── Seed default settings if table is empty ───────────────────────────────────
$count = (int)(db_fetch_value("SELECT COUNT(*) FROM settings") ?? 0);
if ($count === 0) {
    $seeds = [
        ['site_name',          'MobileShop',                   'general',   'Website name'],
        ['site_tagline',       'Your Mobile Destination',       'general',   'Site tagline'],
        ['site_email',         'admin@mobileshop.com',          'general',   'Contact email'],
        ['site_phone',         '+91 9999999999',                'general',   'Contact phone'],
        ['site_address',       '123 Main Street, City',         'general',   'Physical address'],
        ['currency_symbol',    '₹',                             'general',   'Currency symbol'],
        ['gst_rate',           '18',                            'general',   'Default GST rate (%)'],
        ['low_stock_threshold','5',                             'inventory', 'Default low stock alert threshold'],
        ['smtp_host',          '',                              'email',     'SMTP host'],
        ['smtp_port',          '587',                           'email',     'SMTP port'],
        ['smtp_user',          '',                              'email',     'SMTP username'],
        ['smtp_pass',          '',                              'email',     'SMTP password'],
        ['mail_from_name',     'MobileShop',                   'email',     'Mail sender name'],
        ['razorpay_enabled',   '1',                             'payment',   'Enable Razorpay payments'],
        ['razorpay_key_id',    '',                              'payment',   'Razorpay Key ID'],
        ['razorpay_key_secret','',                              'payment',   'Razorpay Key Secret'],
        ['cod_enabled',        '1',                             'payment',   'Enable Cash on Delivery'],
        ['facebook_url',       '',                              'social',    'Facebook page URL'],
        ['instagram_url',      '',                              'social',    'Instagram profile URL'],
        ['twitter_url',        '',                              'social',    'Twitter/X profile URL'],
    ];
    foreach ($seeds as $s) {
        db_query(
            "INSERT IGNORE INTO settings (`key`, value, `group`, description) VALUES (?, ?, ?, ?)",
            $s
        );
    }
}

// ── Load all settings grouped ─────────────────────────────────────────────────
$rows     = db_fetch_all("SELECT setting_key AS `key`, setting_value AS `value`, setting_group AS `group`, setting_key AS description FROM settings ORDER BY setting_group, setting_key");
$settings = [];
foreach ($rows as $row) {
    $settings[$row['group']][] = $row;
}

// Group display config
$groupMeta = [
    'general'   => ['label' => 'General',   'icon' => 'settings',      'color' => 'indigo'],
    'inventory' => ['label' => 'Inventory', 'icon' => 'boxes',         'color' => 'amber'],
    'email'     => ['label' => 'Email / SMTP','icon'=> 'mail',         'color' => 'blue'],
    'payment'   => ['label' => 'Payments',  'icon' => 'credit-card',   'color' => 'emerald'],
    'social'    => ['label' => 'Social Media','icon'=> 'share-2',      'color' => 'purple'],
];

// Sensitive key detection
function isSensitive(string $key): bool {
    return (bool)preg_match('/secret|password|pass|token|api[_-]?key/i', $key);
}

// Humanize key name
function humanizeKey(string $key): string {
    return ucwords(str_replace(['_', '-'], ' ', $key));
}

// Active tab
$activeGroup = $_GET['tab'] ?? array_key_first($settings);
?>

<div class="space-y-6">
    <!-- Page Header -->
    <div class="flex flex-wrap items-end justify-between gap-4">
        <div>
            <h1 class="text-xl font-bold text-slate-900">Store Settings</h1>
            <p class="mt-1 text-sm text-slate-500">Configure site-wide preferences and integrations</p>
        </div>
    </div>

    <!-- Tab Nav -->
    <div class="flex flex-wrap gap-2 border-b border-slate-200 pb-0">
        <?php foreach ($settings as $group => $groupRows): ?>
        <?php $meta = $groupMeta[$group] ?? ['label' => ucfirst($group), 'icon' => 'circle', 'color' => 'slate']; ?>
        <a href="?tab=<?= e($group) ?>"
           class="inline-flex items-center gap-1.5 px-4 py-2.5 text-sm font-semibold rounded-t-xl border border-b-0 transition -mb-px
               <?= $activeGroup === $group
                   ? 'bg-white text-indigo-600 border-slate-200 border-b-white'
                   : 'bg-slate-50 text-slate-500 border-transparent hover:text-slate-700 hover:bg-slate-100' ?>">
            <i data-lucide="<?= e($meta['icon']) ?>" class="w-3.5 h-3.5"></i>
            <?= e($meta['label']) ?>
            <span class="ml-1 text-[10px] px-1.5 py-0.5 rounded bg-slate-200 text-slate-500 font-bold"><?= count($groupRows) ?></span>
        </a>
        <?php endforeach; ?>
    </div>

    <!-- Settings Form for active group -->
    <?php foreach ($settings as $group => $groupRows): ?>
    <?php if ($group !== $activeGroup) continue; ?>
    <?php $meta = $groupMeta[$group] ?? ['label' => ucfirst($group), 'icon' => 'circle', 'color' => 'indigo']; ?>

    <form method="POST" action="<?= url('/admin/actions/settings-update.php') ?>">
        <?= csrf_field() ?>
        <input type="hidden" name="redirect_tab" value="<?= e($group) ?>">

        <div class="bg-white rounded-xl border border-slate-200 shadow-xs overflow-hidden">
            <div class="px-6 py-5 border-b border-slate-100 flex items-center gap-2">
                <i data-lucide="<?= e($meta['icon']) ?>" class="w-4 h-4 text-<?= e($meta['color']) ?>-500"></i>
                <h2 class="font-bold text-slate-800 text-sm"><?= e($meta['label']) ?> Settings</h2>
            </div>

            <div class="divide-y divide-slate-100">
                <?php foreach ($groupRows as $row): ?>
                <?php $sensitive = isSensitive($row['key']); ?>
                <div class="px-6 py-4 sm:grid sm:grid-cols-3 sm:gap-6 sm:items-start">
                    <div class="sm:pt-1.5">
                        <label for="setting_<?= e($row['key']) ?>" class="block text-sm font-semibold text-slate-700">
                            <?= e(humanizeKey($row['key'])) ?>
                            <?php if ($sensitive): ?>
                                <span class="ml-1 text-[10px] px-1 py-0.5 bg-amber-50 text-amber-600 border border-amber-200 rounded font-bold">Sensitive</span>
                            <?php endif; ?>
                        </label>
                        <?php if (!empty($row['description'])): ?>
                            <p class="mt-0.5 text-xs text-slate-400"><?= e($row['description']) ?></p>
                        <?php endif; ?>
                        <p class="mt-0.5 text-[10px] text-slate-300 font-mono"><?= e($row['key']) ?></p>
                    </div>
                    <div class="mt-2 sm:mt-0 sm:col-span-2">
                        <?php if ($sensitive): ?>
                        <input type="password"
                               id="setting_<?= e($row['key']) ?>"
                               name="settings[<?= e($row['key']) ?>]"
                               placeholder="Leave blank to keep current value"
                               autocomplete="new-password"
                               class="w-full rounded-xl border border-slate-300 px-3.5 py-2.5 text-sm focus:border-indigo-600 focus:ring-1 focus:ring-indigo-600 outline-none transition font-mono">
                        <p class="mt-1 text-xs text-amber-600 flex items-center gap-1">
                            <i data-lucide="shield" class="w-3 h-3"></i>
                            Current value is hidden for security. Enter a new value to change it.
                        </p>
                        <?php else: ?>
                        <input type="text"
                               id="setting_<?= e($row['key']) ?>"
                               name="settings[<?= e($row['key']) ?>]"
                               value="<?= e($row['value'] ?? '') ?>"
                               class="w-full rounded-xl border border-slate-300 px-3.5 py-2.5 text-sm focus:border-indigo-600 focus:ring-1 focus:ring-indigo-600 outline-none transition">
                        <?php endif; ?>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>

            <div class="px-6 py-4 bg-slate-50 border-t border-slate-100 flex items-center justify-between gap-3">
                <p class="text-xs text-slate-400"><?= count($groupRows) ?> setting<?= count($groupRows) !== 1 ? 's' : '' ?> in this group</p>
                <button type="submit"
                        class="inline-flex items-center gap-2 rounded-xl bg-indigo-600 px-5 py-2 text-sm font-semibold text-white hover:bg-indigo-700 transition">
                    <i data-lucide="save" class="w-4 h-4"></i> Save <?= e($meta['label']) ?> Settings
                </button>
            </div>
        </div>
    </form>
    <?php endforeach; ?>
</div>

<?php require_once __DIR__ . '/../includes/admin-footer.php'; ?>

