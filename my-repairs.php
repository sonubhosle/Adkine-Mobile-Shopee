<?php
require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/auth.php';

session_init();
require_auth();

$userId = auth_id();
$repairs = db_fetch_all(
    "SELECT * FROM repairs WHERE user_id = ? ORDER BY id DESC",
    [$userId]
);

$pageTitle = 'My Repairs';
$robotsMeta = 'noindex, nofollow';
require_once __DIR__ . '/includes/header.php';
?>

<div class="mx-auto max-w-5xl space-y-6 px-4 py-8">
    <header>
        <h1 class="text-2xl font-bold text-slate-900">My Repairs</h1>
        <p class="mt-1 text-sm text-slate-600">Your repair tickets and current diagnostic progress</p>
    </header>

    <div class="overflow-x-auto border-y border-slate-200">
        <table class="min-w-full divide-y divide-slate-200 text-left text-sm">
            <thead class="bg-slate-50 text-xs uppercase text-slate-500">
                <tr>
                    <th class="px-4 py-3">Ticket</th>
                    <th class="px-4 py-3">Device</th>
                    <th class="px-4 py-3">Status</th>
                    <th class="px-4 py-3">Estimate</th>
                    <th class="px-4 py-3">Opened</th>
                    <th class="px-4 py-3 text-right">Action</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100 bg-white">
                <?php foreach ($repairs as $repair): ?>
                    <tr>
                        <td class="px-4 py-3 font-semibold text-slate-900"><?= e($repair['repair_ticket_number']) ?></td>
                        <td class="px-4 py-3 text-slate-700"><?= e($repair['device_brand']) ?> <?= e($repair['device_model']) ?></td>
                        <td class="px-4 py-3">
                            <span class="px-2.5 py-0.5 rounded-full text-xs font-bold bg-indigo-50 text-blue-700">
                                <?= e(ucwords(str_replace('_', ' ', $repair['status']))) ?>
                            </span>
                        </td>
                        <td class="px-4 py-3 text-slate-700 font-bold"><?= format_currency($repair['estimated_cost'] ?? 0) ?></td>
                        <td class="px-4 py-3 text-slate-500 text-xs"><?= date('d M Y', strtotime($repair['created_at'])) ?></td>
                        <td class="px-4 py-3 text-right">
                            <a class="font-bold text-blue-600 hover:underline text-xs" href="<?= url('/repair-track.php?ticket=' . urlencode($repair['repair_ticket_number']) . '&phone=' . urlencode($repair['customer_phone'])) ?>">
                                Track Ticket &rarr;
                            </a>
                        </td>
                    </tr>
                <?php endforeach; ?>
                <?php if (empty($repairs)): ?>
                    <tr>
                        <td colspan="6" class="px-4 py-10 text-center text-sm text-slate-500">
                            No repair tickets found. Need a device fixed? <a href="<?= url('/repair-book.php') ?>" class="text-blue-600 font-bold hover:underline">Book a repair</a>.
                        </td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>

