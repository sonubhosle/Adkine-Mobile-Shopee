<?php
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth.php';

session_init();
require_role('admin', 'manager', 'technician');

$repairId = (int)($_GET['id'] ?? 0);
$repair = db_fetch_one("SELECT * FROM repairs WHERE id = ?", [$repairId]);

if (!$repair) {
    set_flash('error', 'Repair ticket not found.');
    redirect('/technician/index.php');
}

$statusChoices = [
    'received' => ['diagnosis_pending'],
    'diagnosis_pending' => [],
    'estimate_sent' => [],
    'estimate_approved' => ['in_repair'],
    'in_repair' => ['waiting_parts', 'qa_testing'],
    'waiting_parts' => ['in_repair'],
    'qa_testing' => ['in_repair', 'ready_for_delivery'],
    'ready_for_delivery' => ['completed'],
];

$estimates = db_fetch_all("SELECT * FROM repair_estimates WHERE repair_id = ? ORDER BY id DESC", [$repairId]);
$history = db_fetch_all("SELECT rh.*, u.full_name as author_name FROM repair_history rh LEFT JOIN users u ON rh.updated_by = u.id WHERE rh.repair_id = ? ORDER BY rh.id ASC", [$repairId]);

$spareParts = db_fetch_all("SELECT p.*, COALESCE(i.current_stock, 0) as stock FROM products p LEFT JOIN inventory i ON p.id = i.product_id WHERE p.is_active = 1 ORDER BY p.name ASC");

require_once __DIR__ . '/../includes/technician-header.php';
?>

<div class="space-y-6">
    <div>
        <a href="<?= url('/technician/index.php') ?>" class="text-sm font-medium text-blue-700 hover:underline">&larr; Back to Workbench</a>
        <h1 class="mt-2 text-xl font-bold text-slate-900"><?= e($repair['repair_ticket_number']) ?></h1>
        <p class="mt-1 text-sm text-slate-500"><?= e($repair['customer_name']) ?> &bull; <?= e($repair['customer_phone']) ?> &bull; <?= e($repair['customer_email']) ?></p>
    </div>

    <section class="grid gap-6 border-y border-slate-200 py-5 md:grid-cols-2">
        <div>
            <h2 class="text-sm font-semibold text-slate-900">Device Diagnosis</h2>
            <p class="mt-2 text-sm text-slate-700"><?= e($repair['device_brand']) ?> <?= e($repair['device_model']) ?> &bull; <?= e(ucwords(str_replace('_', ' ', $repair['issue_type']))) ?></p>
            <p class="mt-2 whitespace-pre-wrap text-sm text-slate-600 bg-slate-50 p-3 rounded-xl border border-slate-100"><?= e($repair['issue_description']) ?></p>
            <p class="mt-3 text-xs text-slate-500">Current status: <strong class="text-slate-800"><?= e(ucwords(str_replace('_', ' ', $repair['status']))) ?></strong></p>
        </div>

        <div>
            <h2 class="text-sm font-semibold text-slate-900">Update Status</h2>
            <?php $nextStatuses = $statusChoices[$repair['status']] ?? []; ?>
            <?php if (!empty($nextStatuses)): ?>
                <form method="POST" action="<?= url('/technician/actions/status-update.php') ?>" class="mt-3 space-y-3">
                    <?= csrf_field() ?>
                    <input type="hidden" name="repair_id" value="<?= $repair['id'] ?>">
                    <label class="block text-xs font-medium text-slate-700">
                        Next Step
                        <select name="status" required class="mt-1 block w-full rounded-xl border border-slate-300 px-3 py-2 text-sm">
                            <?php foreach ($nextStatuses as $nextStatus): ?>
                                <option value="<?= e($nextStatus) ?>"><?= e(ucwords(str_replace('_', ' ', $nextStatus))) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </label>
                    <label class="block text-xs font-medium text-slate-700">
                        Work Notes
                        <textarea name="notes" maxlength="1000" rows="3" class="mt-1 block w-full rounded-xl border border-slate-300 px-3 py-2 text-sm" placeholder="Details of work completed..."></textarea>
                    </label>
                    <button class="rounded-xl bg-indigo-700 hover:bg-indigo-800 px-4 py-2 text-sm font-semibold text-white transition">
                        Update Ticket
                    </button>
                </form>
            <?php else: ?>
                <p class="mt-2 text-sm text-slate-500">No status progression available at this stage.</p>
            <?php endif; ?>
        </div>
    </section>

    <!-- Estimate Creator -->
    <?php if (in_array($repair['status'], ['received', 'diagnosis_pending'], true)): ?>
        <section class="border-y border-slate-200 py-5">
            <h2 class="text-sm font-semibold text-slate-900">Diagnostic Estimate</h2>
            <form method="POST" action="<?= url('/technician/actions/estimate-submit.php') ?>" class="mt-4 grid gap-3 md:grid-cols-2">
                <?= csrf_field() ?>
                <input type="hidden" name="repair_id" value="<?= $repair['id'] ?>">
                <label class="text-xs font-medium text-slate-700 md:col-span-2">
                    Diagnosis Notes
                    <textarea name="diagnostic_notes" required minlength="5" rows="3" class="mt-1 block w-full rounded-xl border border-slate-300 px-3 py-2 text-sm" placeholder="Describe fault findings..."></textarea>
                </label>
                <label class="text-xs font-medium text-slate-700">
                    Parts Estimate (₹)
                    <input type="number" name="spare_parts_cost" required min="0" step="0.01" value="0" class="mt-1 block w-full rounded-xl border border-slate-300 px-3 py-2 text-sm">
                </label>
                <label class="text-xs font-medium text-slate-700">
                    Labour Estimate (₹)
                    <input type="number" name="labour_cost" required min="0" step="0.01" value="0" class="mt-1 block w-full rounded-xl border border-slate-300 px-3 py-2 text-sm">
                </label>
                <div class="md:col-span-2">
                    <button class="rounded-xl bg-indigo-700 hover:bg-indigo-800 px-4 py-2 text-sm font-semibold text-white transition">
                        Send Estimate for Approval
                    </button>
                </div>
            </form>
        </section>
    <?php endif; ?>

    <!-- Part Allocation -->
    <?php if ($repair['status'] === 'in_repair'): ?>
        <section class="border-y border-slate-200 py-5">
            <h2 class="text-sm font-semibold text-slate-900">Allocate a Spare Part</h2>
            <form method="POST" action="<?= url('/technician/actions/part-allocate.php') ?>" class="mt-4 grid gap-3 md:grid-cols-3">
                <?= csrf_field() ?>
                <input type="hidden" name="repair_id" value="<?= $repair['id'] ?>">
                <label class="text-xs font-medium text-slate-700 md:col-span-2">
                    Part
                    <select name="product_id" required class="mt-1 block w-full rounded-xl border border-slate-300 px-3 py-2 text-sm">
                        <option value="">Select spare part</option>
                        <?php foreach ($spareParts as $part): ?>
                            <option value="<?= (int)$part['id'] ?>" <?= (int)$part['stock'] < 1 ? 'disabled' : '' ?>>
                                <?= e($part['name']) ?> &bull; Stock: <?= (int)$part['stock'] ?> &bull; <?= format_currency($part['sale_price'] ?? $part['base_price']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </label>
                <label class="text-xs font-medium text-slate-700">
                    Quantity
                    <input type="number" name="quantity" required min="1" value="1" class="mt-1 block w-full rounded-xl border border-slate-300 px-3 py-2 text-sm">
                </label>
                <div class="md:col-span-3">
                    <button class="rounded-xl bg-indigo-700 hover:bg-indigo-800 px-4 py-2 text-sm font-semibold text-white transition">
                        Allocate Part
                    </button>
                </div>
            </form>
        </section>
    <?php endif; ?>

    <!-- Estimates History -->
    <section class="space-y-3">
        <h2 class="text-sm font-semibold text-slate-900">Estimate History</h2>
        <?php foreach ($estimates as $estimate): ?>
            <article class="border border-slate-200 rounded-2xl p-4 bg-white">
                <div class="flex justify-between gap-3">
                    <span class="font-bold text-slate-900 uppercase text-xs"><?= e($estimate['status']) ?></span>
                    <span class="font-bold text-blue-600"><?= format_currency($estimate['total_estimate']) ?></span>
                </div>
                <p class="mt-1 text-sm text-slate-600"><?= e($estimate['diagnostic_notes']) ?></p>
            </article>
        <?php endforeach; ?>
        <?php if (empty($estimates)): ?>
            <p class="text-sm text-slate-400">No estimates submitted yet.</p>
        <?php endif; ?>
    </section>

    <!-- History -->
    <section class="space-y-3">
        <h2 class="text-sm font-semibold text-slate-900">Repair History</h2>
        <ol class="space-y-3 border-l-2 border-indigo-200 pl-4">
            <?php foreach ($history as $entry): ?>
                <li>
                    <p class="text-sm font-medium text-slate-900"><?= e(ucwords(str_replace('_', ' ', $entry['new_status'] ?? 'update'))) ?><?= !empty($entry['author_name']) ? ' &bull; ' . e($entry['author_name']) : '' ?></p>
                    <p class="text-xs text-slate-500"><?= date('d M Y, h:i A', strtotime($entry['created_at'])) ?><?= !empty($entry['comment']) ? ' &bull; ' . e($entry['comment']) : '' ?></p>
                </li>
            <?php endforeach; ?>
        </ol>
    </section>
</div>

</div>
</body>
</html>

