<?php
require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/repair-functions.php';

session_init();

$ticket = trim($_GET['ticket'] ?? '');
$phone = trim($_GET['phone'] ?? '');

$repairData = null;
if (!empty($ticket) && !empty($phone)) {
    $repairData = repair_get_details($ticket, $phone);
    if (!$repairData) {
        set_flash('error', 'No repair ticket found matching the provided search details.');
    }
}

$pageTitle = 'Track Repair Ticket Progress & Status';
$metaDescription = 'Enter your repair ticket number and mobile phone to track real-time diagnostics, technician estimates, and ready-for-collection alerts.';
$canonicalUrl = absolute_url('/repair-track.php');
if (!empty($ticket)) {
    $robotsMeta = 'noindex, follow';
}

require_once __DIR__ . '/includes/header.php';
?>

<div class="mx-auto max-w-4xl space-y-6 px-4 py-8">
    <div>
        <h1 class="text-2xl font-black text-slate-900 tracking-tight">Track Device Repair Progress</h1>
        <p class="mt-1 text-sm text-slate-600">Use the ticket number and booking phone number to view repair progress.</p>
    </div>

    <form method="GET" action="<?= url('/repair-track.php') ?>" class="grid gap-3 border-y border-slate-200 py-5 sm:grid-cols-3">
        <div>
            <label for="ticketInput" class="block text-sm font-medium text-slate-700 mb-1">
                Ticket number
            </label>
            <input id="ticketInput" name="ticket" required value="<?= e($ticket) ?>" placeholder="REP-202610-ABC12" class="block w-full rounded-xl border border-slate-300 px-3 py-2 text-sm focus-visible:ring-2 focus-visible:ring-blue-600">
        </div>
        <div>
            <label for="phoneInput" class="block text-sm font-medium text-slate-700 mb-1">
                Booking phone
            </label>
            <input id="phoneInput" name="phone" required value="<?= e($phone) ?>" placeholder="9876543210" class="block w-full rounded-xl border border-slate-300 px-3 py-2 text-sm focus-visible:ring-2 focus-visible:ring-blue-600">
        </div>
        <div class="flex items-end">
            <button type="submit" class="w-full rounded-xl bg-indigo-700 px-4 py-2.5 text-sm font-semibold text-white hover:bg-indigo-800 transition focus-visible:ring-2 focus-visible:ring-indigo-700">
                Find ticket
            </button>
        </div>
    </form>

    <?php if ($repairData): 
        $repair = $repairData['repair'];
        $estimates = $repairData['estimates'];
        $history = $repairData['history'];
        $canRespond = (auth_id() && (int)$repair['user_id'] === auth_id()) || !empty($phone);
    ?>
        <section class="space-y-5 border-y border-slate-200 py-5">
            <div class="flex flex-wrap items-start justify-between gap-3">
                <div>
                    <p class="text-xs font-semibold uppercase text-slate-500">Repair ticket</p>
                    <h2 class="mt-1 text-lg font-bold text-slate-900"><?= e($repair['repair_ticket_number']) ?></h2>
                </div>
                <span class="rounded-full px-3 py-1 text-xs font-semibold bg-indigo-50 text-blue-800 border border-blue-200">
                    <?= e(ucwords(str_replace('_', ' ', $repair['status']))) ?>
                </span>
            </div>

            <div class="grid gap-4 text-sm sm:grid-cols-2">
                <div>
                    <p class="text-slate-500">Device</p>
                    <p class="font-medium text-slate-900"><?= e($repair['device_brand']) ?> <?= e($repair['device_model']) ?></p>
                </div>
                <div>
                    <p class="text-slate-500">Technician</p>
                    <p class="font-medium text-slate-900"><?= e($repair['technician_name'] ?? 'Not assigned yet') ?></p>
                </div>
                <div>
                    <p class="text-slate-500">Reported issue</p>
                    <p class="font-medium text-slate-900"><?= e(ucwords(str_replace('_', ' ', $repair['issue_type']))) ?></p>
                </div>
                <div>
                    <p class="text-slate-500">Estimate</p>
                    <p class="font-medium text-slate-900"><?= format_currency($repair['estimated_cost'] ?? 0) ?></p>
                </div>
            </div>

            <p class="whitespace-pre-wrap text-sm text-slate-600 bg-slate-50 p-4 rounded-xl border border-slate-100"><?= e($repair['issue_description']) ?></p>
        </section>

        <?php if (!empty($estimates)): ?>
            <section class="space-y-4">
                <h2 class="text-sm font-semibold text-slate-900">Repair Estimates</h2>
                <?php foreach ($estimates as $estimate): ?>
                    <article class="border border-slate-200 rounded-2xl p-4 bg-white">
                        <div class="flex justify-between gap-3">
                            <h3 class="font-semibold text-slate-900 uppercase text-xs"><?= e($estimate['status']) ?></h3>
                            <span class="font-bold text-slate-900"><?= format_currency($estimate['total_estimate']) ?></span>
                        </div>
                        <p class="mt-2 text-sm text-slate-600"><?= e($estimate['diagnostic_notes'] ?? '') ?></p>
                        <p class="mt-2 text-xs text-slate-500">
                            Parts <?= format_currency($estimate['spare_parts_cost']) ?> &bull; Labour <?= format_currency($estimate['labour_cost']) ?> &bull; Tax <?= format_currency($estimate['tax_amount']) ?>
                        </p>

                        <?php if ($canRespond && $estimate['status'] === 'sent'): ?>
                            <form method="POST" action="<?= url('/actions/estimate-respond.php') ?>" class="mt-4 flex flex-wrap gap-2">
                                <?= csrf_field() ?>
                                <input type="hidden" name="repair_id" value="<?= $repair['id'] ?>">
                                <input type="hidden" name="estimate_id" value="<?= $estimate['id'] ?>">
                                <input type="hidden" name="ticket" value="<?= e($ticket) ?>">
                                <input type="hidden" name="phone" value="<?= e($phone) ?>">
                                <input name="notes" maxlength="500" placeholder="Optional note to technician" class="min-w-48 rounded-xl border border-slate-300 px-3 py-2 text-xs">
                                <button name="action" value="approve" class="rounded-xl bg-emerald-600 hover:bg-emerald-700 px-4 py-2 text-xs font-semibold text-white transition">
                                    Approve &amp; Proceed
                                </button>
                                <button name="action" value="reject" class="rounded-xl border border-slate-300 hover:bg-slate-50 px-4 py-2 text-xs font-semibold text-slate-700 transition">
                                    Decline
                                </button>
                            </form>
                        <?php endif; ?>
                    </article>
                <?php endforeach; ?>
            </section>
        <?php endif; ?>

        <?php if (!empty($history)): ?>
            <section class="space-y-3">
                <h2 class="text-sm font-semibold text-slate-900">Progress History</h2>
                <ol class="space-y-3 border-l-2 border-indigo-200 pl-4">
                    <?php foreach ($history as $entry): ?>
                        <li>
                            <p class="text-sm font-medium text-slate-900"><?= e(ucwords(str_replace('_', ' ', $entry['status_to'] ?? $entry['new_status'] ?? 'update'))) ?></p>
                            <p class="text-xs text-slate-500"><?= date('d M Y, h:i A', strtotime($entry['created_at'])) ?><?= !empty($entry['notes']) ? ' &bull; ' . e($entry['notes']) : (!empty($entry['comment']) ? ' &bull; ' . e($entry['comment']) : '') ?></p>
                        </li>
                    <?php endforeach; ?>
                </ol>
            </section>
        <?php endif; ?>
    <?php endif; ?>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>

