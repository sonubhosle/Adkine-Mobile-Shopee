<?php
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth.php';
session_init();
require_role('technician', 'admin', 'manager');

$user    = auth_user();
$userId  = (int)$user['id'];
$isSuper = in_array(strtolower($user['role_slug'] ?? ''), ['admin', 'manager'], true);

// Fetch repairs visible to this user
if ($isSuper) {
    $sql    = "SELECT r.*, u.full_name as tech_name
               FROM repairs r
               LEFT JOIN users u ON r.assigned_technician_id = u.id
               ORDER BY r.priority DESC, r.id DESC";
    $repairs = db_fetch_all($sql);
} else {
    $sql    = "SELECT r.*, u.full_name as tech_name
               FROM repairs r
               LEFT JOIN users u ON r.assigned_technician_id = u.id
               WHERE r.assigned_technician_id = ? OR r.assigned_technician_id IS NULL
               ORDER BY r.priority DESC, r.id DESC";
    $repairs = db_fetch_all($sql, [$userId]);
}

// Metrics
$totalJobs     = count($repairs);
$pendingJobs   = count(array_filter($repairs, fn($r) => in_array($r['status'], ['received', 'diagnosis_pending', 'in_repair', 'waiting_parts'])));
$qaJobs        = count(array_filter($repairs, fn($r) => $r['status'] === 'qa_testing'));
$completedJobs = count(array_filter($repairs, fn($r) => in_array($r['status'], ['ready_for_delivery', 'completed'])));

$metrics = [
    'total'     => $totalJobs,
    'pending'   => $pendingJobs,
    'qa'        => $qaJobs,
    'completed' => $completedJobs,
];

$title = 'Technician Workbench';
require_once __DIR__ . '/../includes/technician-header.php';
?>
<div class="space-y-6">
    <div>
        <h1 class="text-xl font-bold text-slate-900">Repair workbench</h1>
        <p class="mt-1 text-sm text-slate-500">Assigned repair tickets and intake queue</p>
    </div>

    <!-- Metrics -->
    <div class="grid grid-cols-2 gap-3 lg:grid-cols-4">
        <div class="border-y border-slate-200 py-3">
            <p class="text-xs uppercase text-slate-500">Visible tickets</p>
            <p class="mt-1 text-2xl font-bold text-slate-900"><?= (int)$metrics['total'] ?></p>
        </div>
        <div class="border-y border-slate-200 py-3">
            <p class="text-xs uppercase text-slate-500">Active work</p>
            <p class="mt-1 text-2xl font-bold text-amber-700"><?= (int)$metrics['pending'] ?></p>
        </div>
        <div class="border-y border-slate-200 py-3">
            <p class="text-xs uppercase text-slate-500">Quality check</p>
            <p class="mt-1 text-2xl font-bold text-blue-700"><?= (int)$metrics['qa'] ?></p>
        </div>
        <div class="border-y border-slate-200 py-3">
            <p class="text-xs uppercase text-slate-500">Ready / complete</p>
            <p class="mt-1 text-2xl font-bold text-emerald-700"><?= (int)$metrics['completed'] ?></p>
        </div>
    </div>

    <!-- Flash messages -->
    <?php if ($flash = flash_get('success')): ?>
        <div class="rounded-md bg-emerald-50 border border-emerald-200 px-4 py-3 text-sm text-emerald-800"><?= e($flash) ?></div>
    <?php endif; ?>
    <?php if ($flash = flash_get('error')): ?>
        <div class="rounded-md bg-rose-50 border border-rose-200 px-4 py-3 text-sm text-rose-800"><?= e($flash) ?></div>
    <?php endif; ?>

    <!-- Repairs table -->
    <div class="overflow-x-auto border-y border-slate-200">
        <table class="min-w-full divide-y divide-slate-200 text-left text-sm">
            <thead class="bg-slate-50 text-xs uppercase text-slate-500">
                <tr>
                    <th class="px-4 py-3">Ticket</th>
                    <th class="px-4 py-3">Customer</th>
                    <th class="px-4 py-3">Device / issue</th>
                    <th class="px-4 py-3">Assigned to</th>
                    <th class="px-4 py-3">Status</th>
                    <th class="px-4 py-3">Priority</th>
                    <th class="px-4 py-3"></th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100 bg-white">
                <?php foreach ($repairs as $repair): ?>
                <tr>
                    <td class="px-4 py-3">
                        <span class="font-semibold text-slate-900"><?= e($repair['repair_ticket_number']) ?></span>
                        <span class="block text-xs text-slate-500"><?= e($repair['created_at']) ?></span>
                    </td>
                    <td class="px-4 py-3 text-slate-700">
                        <?= e($repair['customer_name']) ?>
                        <span class="block text-xs text-slate-500"><?= e($repair['customer_phone']) ?></span>
                    </td>
                    <td class="px-4 py-3 text-slate-700">
                        <?= e($repair['device_brand']) ?> <?= e($repair['device_model']) ?>
                        <span class="block text-xs text-slate-500"><?= e(ucwords(str_replace('_', ' ', $repair['issue_type']))) ?></span>
                    </td>
                    <td class="px-4 py-3 text-slate-600"><?= e($repair['tech_name'] ?? 'Unassigned') ?></td>
                    <td class="px-4 py-3 text-slate-700"><?= e(ucwords(str_replace('_', ' ', $repair['status']))) ?></td>
                    <td class="px-4 py-3 text-slate-600"><?= e(ucfirst($repair['priority'])) ?></td>
                    <td class="px-4 py-3 text-right">
                        <a class="font-medium text-blue-700 hover:underline"
                           href="<?= url('/technician/repair-detail.php?id=' . (int)$repair['id']) ?>">Open</a>
                    </td>
                </tr>
                <?php endforeach; ?>
                <?php if (!$repairs): ?>
                <tr>
                    <td colspan="7" class="px-4 py-10 text-center text-sm text-slate-500">
                        No repair tickets are assigned to you.
                    </td>
                </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>
</main>
<script>lucide.createIcons();</script>
</body>
</html>
