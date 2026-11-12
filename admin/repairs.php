<?php
require_once __DIR__ . '/../includes/admin-header.php';

// ── Filters & Pagination ──────────────────────────────────────────────────────
$page     = max(1, (int)($_GET['page'] ?? 1));
$per_page = 20;
$offset   = ($page - 1) * $per_page;

$status     = trim($_GET['status'] ?? '');
$technician = (int)($_GET['technician'] ?? 0);
$priority   = trim($_GET['priority'] ?? '');
$search     = trim($_GET['search'] ?? '');

$where = [];
$params = [];

if ($status !== '') {
    $where[] = 'r.status = ?';
    $params[] = $status;
}
if ($technician > 0) {
    $where[] = 'r.assigned_technician_id = ?';
    $params[] = $technician;
}
if ($priority !== '' && in_array($priority, ['normal', 'urgent'], true)) {
    $where[] = 'r.priority = ?';
    $params[] = $priority;
}
if ($search !== '') {
    $where[] = '(r.repair_ticket_number LIKE ? OR r.customer_name LIKE ? OR r.customer_phone LIKE ? OR r.customer_email LIKE ? OR r.device_brand LIKE ? OR r.device_model LIKE ?)';
    $like = '%' . $search . '%';
    array_push($params, $like, $like, $like, $like, $like, $like);
}

$where_sql = $where ? 'WHERE ' . implode(' AND ', $where) : '';

$total_rows = (int)db_fetch_value("SELECT COUNT(*) FROM repairs r $where_sql", $params);
$total_pages = max(1, (int)ceil($total_rows / $per_page));

$repairs = db_fetch_all(
    "SELECT r.*, u.full_name AS technician_name
       FROM repairs r
  LEFT JOIN users u ON u.id = r.assigned_technician_id
       $where_sql
   ORDER BY r.created_at DESC
      LIMIT $per_page OFFSET $offset",
    $params
);

// Technicians for filter dropdown
$tech_users = db_fetch_all(
    "SELECT u.id, u.full_name AS name, u.email 
       FROM users u 
       JOIN roles ro ON ro.id = u.role_id 
      WHERE ro.slug IN ('technician', 'admin') 
   ORDER BY u.full_name ASC"
);

// Quick Stats
$count_open = (int)db_fetch_value("SELECT COUNT(*) FROM repairs WHERE status NOT IN ('completed', 'cancelled')");
$count_estimate = (int)db_fetch_value("SELECT COUNT(*) FROM repairs WHERE status IN ('diagnosis_pending', 'estimate_sent')");
$count_in_repair = (int)db_fetch_value("SELECT COUNT(*) FROM repairs WHERE status IN ('estimate_approved', 'in_repair', 'waiting_parts')");
$count_ready = (int)db_fetch_value("SELECT COUNT(*) FROM repairs WHERE status = 'ready_for_delivery'");

$pageTitle = 'Mobile Repairs Service Center';
?>

<div class="space-y-6 max-w-7xl mx-auto">
    <!-- Header -->
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <div class="flex items-center gap-2 text-xs font-bold uppercase tracking-wider text-emerald-600 mb-1">
                <i data-lucide="wrench" class="w-4 h-4"></i>
                Service Operations
            </div>
            <h1 class="text-2xl font-black text-slate-900 tracking-tight">Repair Tickets</h1>
            <p class="text-sm text-slate-500 mt-0.5">Device intake, technician diagnostics, quote approvals, and repair lifecycle.</p>
        </div>
        <div class="flex items-center gap-2.5">
            <a href="<?= url('/repair-book.php') ?>" target="_blank" class="inline-flex items-center gap-2 px-4 py-2.5 rounded-xl bg-emerald-600 hover:bg-emerald-700 text-white font-bold text-xs shadow-sm transition">
                <i data-lucide="plus" class="w-4 h-4"></i>
                <span>Book New Device Repair</span>
            </a>
            <a href="<?= url('/technician/index.php') ?>" class="inline-flex items-center gap-2 px-4 py-2.5 rounded-xl border border-slate-200 bg-white hover:bg-slate-50 text-slate-700 font-bold text-xs transition">
                <i data-lucide="cpu" class="w-4 h-4 text-slate-400"></i>
                <span>Technician Workbench</span>
            </a>
        </div>
    </div>

    <!-- Stats Cards -->
    <div class="grid grid-cols-1 sm:grid-cols-4 gap-4">
        <div class="p-4 rounded-2xl bg-white border border-slate-200/80 shadow-xs flex items-center gap-4">
            <div class="w-12 h-12 rounded-xl bg-blue-50 text-blue-600 flex items-center justify-center shrink-0">
                <i data-lucide="wrench" class="w-6 h-6"></i>
            </div>
            <div>
                <p class="text-xs font-bold text-slate-400 uppercase tracking-wider">Active Tickets</p>
                <p class="text-xl font-black text-slate-900 mt-0.5"><?= number_format($count_open) ?></p>
            </div>
        </div>
        <div class="p-4 rounded-2xl bg-white border border-slate-200/80 shadow-xs flex items-center gap-4">
            <div class="w-12 h-12 rounded-xl bg-amber-50 text-amber-600 flex items-center justify-center shrink-0">
                <i data-lucide="calculator" class="w-6 h-6"></i>
            </div>
            <div>
                <p class="text-xs font-bold text-slate-400 uppercase tracking-wider">Estimate Phase</p>
                <p class="text-xl font-black text-slate-900 mt-0.5"><?= number_format($count_estimate) ?></p>
            </div>
        </div>
        <div class="p-4 rounded-2xl bg-white border border-slate-200/80 shadow-xs flex items-center gap-4">
            <div class="w-12 h-12 rounded-xl bg-indigo-50 text-indigo-600 flex items-center justify-center shrink-0">
                <i data-lucide="cog" class="w-6 h-6"></i>
            </div>
            <div>
                <p class="text-xs font-bold text-slate-400 uppercase tracking-wider">In Repair</p>
                <p class="text-xl font-black text-slate-900 mt-0.5"><?= number_format($count_in_repair) ?></p>
            </div>
        </div>
        <div class="p-4 rounded-2xl bg-white border border-slate-200/80 shadow-xs flex items-center gap-4">
            <div class="w-12 h-12 rounded-xl bg-emerald-50 text-emerald-600 flex items-center justify-center shrink-0">
                <i data-lucide="check-circle" class="w-6 h-6"></i>
            </div>
            <div>
                <p class="text-xs font-bold text-slate-400 uppercase tracking-wider">Ready for Pickup</p>
                <p class="text-xl font-black text-slate-900 mt-0.5"><?= number_format($count_ready) ?></p>
            </div>
        </div>
    </div>

    <!-- Filters Bar -->
    <div class="p-4 rounded-2xl bg-white border border-slate-200/80 shadow-xs">
        <form method="GET" action="<?= url('/admin/repairs.php') ?>" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-5 gap-3 items-center">
            <div class="relative lg:col-span-2">
                <i data-lucide="search" class="w-4 h-4 text-slate-400 absolute left-3.5 top-1/2 -translate-y-1/2"></i>
                <input type="text" name="search" value="<?= e($search) ?>" placeholder="Search ticket #, customer, phone, model..."
                       class="w-full pl-10 pr-4 py-2 text-xs rounded-xl border border-slate-200 focus:outline-none focus:ring-2 focus:ring-blue-500/20 focus:border-blue-600 transition">
            </div>

            <select name="status" class="px-3 py-2 text-xs rounded-xl border border-slate-200 bg-white focus:outline-none focus:ring-2 focus:ring-blue-500/20 focus:border-blue-600 transition">
                <option value="">All Statuses</option>
                <?php
                $statuses = [
                    'received'           => 'Received',
                    'diagnosis_pending'  => 'Diagnosis Pending',
                    'estimate_sent'      => 'Estimate Sent',
                    'estimate_approved'  => 'Estimate Approved',
                    'in_repair'          => 'In Repair',
                    'waiting_parts'      => 'Waiting for Parts',
                    'qa_testing'         => 'QA & Testing',
                    'ready_for_delivery' => 'Ready for Pickup / Delivery',
                    'completed'          => 'Completed',
                    'cancelled'          => 'Cancelled'
                ];
                foreach ($statuses as $k => $v):
                ?>
                    <option value="<?= $k ?>" <?= $status === $k ? 'selected' : '' ?>><?= $v ?></option>
                <?php endforeach; ?>
            </select>

            <select name="technician" class="px-3 py-2 text-xs rounded-xl border border-slate-200 bg-white focus:outline-none focus:ring-2 focus:ring-blue-500/20 focus:border-blue-600 transition">
                <option value="">All Technicians</option>
                <?php foreach ($tech_users as $tu): ?>
                    <option value="<?= (int)$tu['id'] ?>" <?= $technician === (int)$tu['id'] ? 'selected' : '' ?>><?= e($tu['name']) ?></option>
                <?php endforeach; ?>
            </select>

            <div class="flex items-center gap-2">
                <button type="submit" class="flex-1 py-2 rounded-xl bg-slate-900 hover:bg-slate-800 text-white text-xs font-bold transition">
                    Filter
                </button>
                <?php if ($search !== '' || $status !== '' || $technician > 0 || $priority !== ''): ?>
                    <a href="<?= url('/admin/repairs.php') ?>" class="px-3 py-2 rounded-xl border border-slate-200 text-slate-600 hover:bg-slate-50 text-xs font-semibold transition" title="Clear">
                        <i data-lucide="rotate-ccw" class="w-3.5 h-3.5"></i>
                    </a>
                <?php endif; ?>
            </div>
        </form>
    </div>

    <!-- Repairs Table -->
    <div class="rounded-2xl bg-white border border-slate-200/80 shadow-xs overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-left text-xs">
                <thead class="bg-slate-50 border-b border-slate-200 text-[11px] font-bold uppercase tracking-wider text-slate-500">
                    <tr>
                        <th class="px-5 py-3.5">Ticket # &amp; Date</th>
                        <th class="px-5 py-3.5">Customer</th>
                        <th class="px-5 py-3.5">Device &amp; Issue</th>
                        <th class="px-5 py-3.5">Assigned Tech</th>
                        <th class="px-5 py-3.5">Status</th>
                        <th class="px-5 py-3.5">Cost &amp; Due</th>
                        <th class="px-5 py-3.5 text-right">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 text-slate-700">
                    <?php if (empty($repairs)): ?>
                        <tr>
                            <td colspan="7" class="px-5 py-12 text-center text-slate-400">
                                <i data-lucide="wrench" class="w-8 h-8 mx-auto mb-2 opacity-40"></i>
                                <p class="text-sm font-semibold">No repair tickets found.</p>
                                <p class="text-xs text-slate-400 mt-0.5">Device repair bookings will show up here automatically.</p>
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($repairs as $r): ?>
                            <?php
                            $badge_class = match ($r['status']) {
                                'ready_for_delivery', 'completed' => 'bg-emerald-50 text-emerald-700 border-emerald-100',
                                'cancelled'                       => 'bg-rose-50 text-rose-700 border-rose-100',
                                'in_repair', 'qa_testing'         => 'bg-blue-50 text-blue-700 border-blue-100',
                                'waiting_parts'                   => 'bg-purple-50 text-purple-700 border-purple-100',
                                default                           => 'bg-amber-50 text-amber-800 border-amber-100',
                            };
                            ?>
                            <tr class="hover:bg-slate-50/70 transition">
                                <td class="px-5 py-3.5">
                                    <div class="flex items-center gap-1.5">
                                        <a href="<?= url('/admin/repair-detail.php?id=' . (int)$r['id']) ?>" class="font-bold text-blue-600 hover:text-blue-800">
                                            <?= e($r['repair_ticket_number']) ?>
                                        </a>
                                        <?php if ($r['priority'] === 'urgent'): ?>
                                            <span class="px-1.5 py-0.2 bg-rose-100 text-rose-700 text-[10px] font-black rounded uppercase">Urgent</span>
                                        <?php endif; ?>
                                    </div>
                                    <div class="text-[11px] text-slate-400 mt-0.5">
                                        <?= date('d M Y, h:i A', strtotime($r['created_at'])) ?>
                                    </div>
                                </td>
                                <td class="px-5 py-3.5">
                                    <div class="font-bold text-slate-900"><?= e($r['customer_name']) ?></div>
                                    <div class="text-[11px] text-slate-500 font-medium"><?= e($r['customer_phone']) ?></div>
                                </td>
                                <td class="px-5 py-3.5">
                                    <div class="font-bold text-slate-900"><?= e($r['device_brand']) ?> <?= e($r['device_model']) ?></div>
                                    <div class="text-[11px] text-slate-500 capitalize"><?= str_replace('_', ' ', $r['issue_type']) ?></div>
                                </td>
                                <td class="px-5 py-3.5">
                                    <?php if (!empty($r['technician_name'])): ?>
                                        <span class="inline-flex items-center gap-1 font-semibold text-slate-800">
                                            <i data-lucide="user" class="w-3.5 h-3.5 text-slate-400"></i>
                                            <?= e($r['technician_name']) ?>
                                        </span>
                                    <?php else: ?>
                                        <span class="inline-flex items-center gap-1 text-[11px] font-semibold text-amber-600 bg-amber-50 px-2 py-0.5 rounded border border-amber-100">
                                            Unassigned
                                        </span>
                                    <?php endif; ?>
                                </td>
                                <td class="px-5 py-3.5">
                                    <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-[10px] font-bold border <?= $badge_class ?>">
                                        <?= ucwords(str_replace('_', ' ', $r['status'])) ?>
                                    </span>
                                </td>
                                <td class="px-5 py-3.5">
                                    <?php $cost = $r['final_cost'] > 0 ? $r['final_cost'] : $r['estimated_cost']; ?>
                                    <div class="font-bold text-slate-900"><?= format_currency($cost) ?></div>
                                    <?php if ($r['advance_paid'] > 0): ?>
                                        <div class="text-[10px] text-emerald-600 font-semibold">Adv: <?= format_currency($r['advance_paid']) ?></div>
                                    <?php endif; ?>
                                </td>
                                <td class="px-5 py-3.5 text-right">
                                    <a href="<?= url('/admin/repair-detail.php?id=' . (int)$r['id']) ?>"
                                       class="inline-flex items-center gap-1 px-3 py-1.5 rounded-lg border border-slate-200 hover:bg-slate-100 text-slate-700 font-semibold text-xs transition">
                                        <span>Manage</span>
                                        <i data-lucide="chevron-right" class="w-3.5 h-3.5"></i>
                                    </a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

        <!-- Pagination -->
        <?php if ($total_pages > 1): ?>
            <div class="p-4 border-t border-slate-100 flex items-center justify-between text-xs text-slate-500">
                <span>Showing page <?= $page ?> of <?= $total_pages ?> (<?= $total_rows ?> total repairs)</span>
                <div class="flex items-center gap-1">
                    <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                        <a href="?page=<?= $i ?>&status=<?= urlencode($status) ?>&technician=<?= $technician ?>&search=<?= urlencode($search) ?>"
                           class="px-2.5 py-1 rounded-lg border <?= $page === $i ? 'bg-blue-600 text-white border-blue-600 font-bold' : 'border-slate-200 text-slate-600 hover:bg-slate-50' ?>">
                            <?= $i ?>
                        </a>
                    <?php endfor; ?>
                </div>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/admin-footer.php'; ?>

