<?php
/**
 * MobileShopFlat — Admin Audit Logs
 */

require_once __DIR__ . '/../includes/admin-header.php';

// ── Filters ───────────────────────────────────────────────────────────────────
$filterUser   = trim((string)($_GET['user']      ?? ''));
$filterAction = trim((string)($_GET['action']    ?? ''));
$filterModule = trim((string)($_GET['module']    ?? ''));
$filterFrom   = trim((string)($_GET['date_from'] ?? ''));
$filterTo     = trim((string)($_GET['date_to']   ?? ''));

$page   = max(1, (int)($_GET['page'] ?? 1));
$limit  = 50;
$offset = ($page - 1) * $limit;

// Build WHERE
$whereParts = ['1=1'];
$params     = [];

if ($filterUser !== '') {
    $whereParts[] = "(u.full_name LIKE ? OR u.email LIKE ?)";
    $like = "%$filterUser%";
    $params[] = $like;
    $params[] = $like;
}
if ($filterAction !== '') {
    $whereParts[] = "al.action = ?";
    $params[] = $filterAction;
}
if ($filterModule !== '') {
    $whereParts[] = "al.module = ?";
    $params[] = $filterModule;
}
if ($filterFrom !== '') {
    $whereParts[] = "al.created_at >= ?";
    $params[] = $filterFrom . ' 00:00:00';
}
if ($filterTo !== '') {
    $whereParts[] = "al.created_at <= ?";
    $params[] = $filterTo . ' 23:59:59';
}

$where = 'WHERE ' . implode(' AND ', $whereParts);

$total = (int)(db_fetch_value(
    "SELECT COUNT(*) FROM audit_logs al LEFT JOIN users u ON u.id = al.user_id $where",
    $params
) ?? 0);

$pages = (int)ceil($total / $limit);

$logs = db_fetch_all(
    "SELECT al.*, u.full_name AS user_name, u.email AS user_email
     FROM audit_logs al
     LEFT JOIN users u ON u.id = al.user_id
     $where
     ORDER BY al.created_at DESC
     LIMIT $limit OFFSET $offset",
    $params
);

// Distinct action/module lists for filter dropdowns
$actions = db_fetch_all("SELECT DISTINCT action FROM audit_logs ORDER BY action");
$modules = db_fetch_all("SELECT DISTINCT module FROM audit_logs WHERE module IS NOT NULL ORDER BY module");

// Build pagination query string
function auditQs(array $overrides = []): string {
    global $filterUser, $filterAction, $filterModule, $filterFrom, $filterTo;
    $base = [
        'user'      => $filterUser,
        'action'    => $filterAction,
        'module'    => $filterModule,
        'date_from' => $filterFrom,
        'date_to'   => $filterTo,
    ];
    $merged = array_merge($base, $overrides);
    $merged = array_filter($merged, fn($v) => $v !== '');
    return $merged ? '&' . http_build_query($merged) : '';
}
?>

<div class="space-y-6">
    <!-- Page Header -->
    <div class="flex flex-wrap items-end justify-between gap-4">
        <div>
            <h1 class="text-xl font-bold text-slate-900">Security Audit Logs</h1>
            <p class="mt-1 text-sm text-slate-500">Read-only record of all system actions &bull; <?= number_format($total) ?> entries</p>
        </div>
        <?php if ($total > 0): ?>
        <span class="text-xs text-slate-400 bg-slate-100 px-3 py-1.5 rounded-xl font-medium"><?= number_format($total) ?> log entries</span>
        <?php endif; ?>
    </div>

    <!-- Filters -->
    <form method="GET" action="<?= url('/admin/audit-logs.php') ?>"
          class="bg-white rounded-xl border border-slate-200 shadow-xs p-4">
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-5 gap-3">
            <!-- User search -->
            <div class="relative">
                <i data-lucide="user" class="w-3.5 h-3.5 text-slate-400 absolute left-3 top-2.5"></i>
                <input name="user" value="<?= e($filterUser) ?>" placeholder="User name / email"
                       class="w-full rounded-xl border border-slate-300 bg-white px-3 py-2 pl-8 text-sm focus:border-indigo-600 focus:ring-1 focus:ring-indigo-600 outline-none">
            </div>
            <!-- Action -->
            <select name="action" class="rounded-xl border border-slate-300 bg-white px-3 py-2 text-sm focus:border-indigo-600 focus:ring-1 focus:ring-indigo-600 outline-none">
                <option value="">All Actions</option>
                <?php foreach ($actions as $a): ?>
                    <option value="<?= e($a['action']) ?>" <?= $filterAction === $a['action'] ? 'selected' : '' ?>>
                        <?= e($a['action']) ?>
                    </option>
                <?php endforeach; ?>
            </select>
            <!-- Module -->
            <select name="module" class="rounded-xl border border-slate-300 bg-white px-3 py-2 text-sm focus:border-indigo-600 focus:ring-1 focus:ring-indigo-600 outline-none">
                <option value="">All Modules</option>
                <?php foreach ($modules as $m): ?>
                    <option value="<?= e($m['module']) ?>" <?= $filterModule === $m['module'] ? 'selected' : '' ?>>
                        <?= e(ucfirst($m['module'])) ?>
                    </option>
                <?php endforeach; ?>
            </select>
            <!-- Date From -->
            <input type="date" name="date_from" value="<?= e($filterFrom) ?>"
                   class="rounded-xl border border-slate-300 bg-white px-3 py-2 text-sm focus:border-indigo-600 focus:ring-1 focus:ring-indigo-600 outline-none">
            <!-- Date To -->
            <input type="date" name="date_to" value="<?= e($filterTo) ?>"
                   class="rounded-xl border border-slate-300 bg-white px-3 py-2 text-sm focus:border-indigo-600 focus:ring-1 focus:ring-indigo-600 outline-none">
        </div>
        <div class="mt-3 flex gap-2">
            <button type="submit" class="rounded-xl bg-slate-900 px-4 py-2 text-sm font-semibold text-white hover:bg-slate-800 transition">
                <i data-lucide="filter" class="w-3.5 h-3.5 inline -mt-0.5 mr-1"></i>Apply Filters
            </button>
            <a href="<?= url('/admin/audit-logs.php') ?>"
               class="rounded-xl border border-slate-300 bg-white px-4 py-2 text-sm text-slate-600 hover:bg-slate-50 transition">Clear</a>
        </div>
    </form>

    <!-- Logs Table -->
    <div class="overflow-x-auto rounded-xl border border-slate-200 bg-white shadow-xs">
        <table class="min-w-full divide-y divide-slate-200 text-left text-sm">
            <thead class="bg-slate-50 text-xs uppercase tracking-wider text-slate-500 font-bold">
                <tr>
                    <th class="px-4 py-3">Timestamp</th>
                    <th class="px-4 py-3">User</th>
                    <th class="px-4 py-3">Action</th>
                    <th class="px-4 py-3">Module</th>
                    <th class="px-4 py-3">Description</th>
                    <th class="px-4 py-3">IP Address</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100 bg-white">
                <?php foreach ($logs as $log): ?>
                <tr class="hover:bg-slate-50/60 transition">
                    <td class="px-4 py-3 text-xs text-slate-500 whitespace-nowrap font-mono">
                        <?= date('d M Y H:i:s', strtotime($log['created_at'])) ?>
                    </td>
                    <td class="px-4 py-3">
                        <?php if ($log['user_name']): ?>
                            <div class="font-semibold text-slate-800 text-xs"><?= e($log['user_name']) ?></div>
                            <div class="text-[10px] text-slate-400"><?= e($log['user_email'] ?? '') ?></div>
                        <?php else: ?>
                            <span class="text-xs text-slate-400 italic">System / Guest</span>
                        <?php endif; ?>
                    </td>
                    <td class="px-4 py-3">
                        <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-bold bg-slate-100 text-slate-600 border border-slate-200 font-mono whitespace-nowrap">
                            <?= e($log['action']) ?>
                        </span>
                    </td>
                    <td class="px-4 py-3 text-xs text-slate-500 capitalize"><?= e($log['module'] ?? '—') ?></td>
                    <td class="px-4 py-3 text-xs text-slate-600 max-w-xs truncate" title="<?= e($log['description'] ?? '') ?>">
                        <?= e($log['description'] ?? '—') ?>
                    </td>
                    <td class="px-4 py-3 text-xs text-slate-400 font-mono whitespace-nowrap"><?= e($log['ip_address'] ?? '—') ?></td>
                </tr>
                <?php endforeach; ?>
                <?php if (!$logs): ?>
                <tr>
                    <td colspan="6" class="px-4 py-14 text-center text-sm text-slate-400">
                        <i data-lucide="activity" class="w-8 h-8 mx-auto text-slate-300 mb-2"></i>
                        No audit log entries found<?= ($filterUser || $filterAction || $filterModule || $filterFrom || $filterTo) ? ' for the selected filters.' : '.' ?>
                    </td>
                </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

    <!-- Pagination -->
    <div class="flex items-center justify-between text-xs text-slate-500">
        <p>Showing <?= count($logs) ?> of <?= number_format($total) ?> entries &bull; Page <?= $page ?> of <?= max(1, $pages) ?></p>
        <?php if ($pages > 1): ?>
        <div class="inline-flex gap-1">
            <?php if ($page > 1): ?>
                <a href="<?= url('/admin/audit-logs.php?page=' . ($page - 1) . auditQs()) ?>"
                   class="px-3 py-1.5 rounded-lg border border-slate-200 bg-white hover:bg-slate-50">Prev</a>
            <?php endif; ?>
            <?php for ($p = max(1, $page - 2); $p <= min($pages, $page + 2); $p++): ?>
                <a href="<?= url('/admin/audit-logs.php?page=' . $p . auditQs()) ?>"
                   class="px-3 py-1.5 rounded-lg border <?= $p === $page ? 'bg-indigo-600 text-white border-indigo-600 font-bold' : 'border-slate-200 bg-white hover:bg-slate-50' ?>">
                   <?= $p ?>
                </a>
            <?php endfor; ?>
            <?php if ($page < $pages): ?>
                <a href="<?= url('/admin/audit-logs.php?page=' . ($page + 1) . auditQs()) ?>"
                   class="px-3 py-1.5 rounded-lg border border-slate-200 bg-white hover:bg-slate-50">Next</a>
            <?php endif; ?>
        </div>
        <?php endif; ?>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/admin-footer.php'; ?>

