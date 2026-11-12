<?php
require_once __DIR__ . '/../includes/admin-header.php';

$id = (int)($_GET['id'] ?? 0);
if ($id <= 0) {
    set_flash('error', 'Invalid Repair Ticket ID.');
    redirect(url('/admin/repairs.php'));
}

$repair = db_fetch_one(
    "SELECT r.*, u.full_name AS technician_name, u.email AS technician_email
       FROM repairs r
  LEFT JOIN users u ON u.id = r.assigned_technician_id
      WHERE r.id = ?",
    [$id]
);

if (!$repair) {
    set_flash('error', 'Repair ticket not found.');
    redirect(url('/admin/repairs.php'));
}

// ── Handle Actions ────────────────────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $action = $_POST['action'] ?? '';

    if ($action === 'assign_technician') {
        $technician_id = !empty($_POST['technician_id']) ? (int)$_POST['technician_id'] : null;
        
        $tech_name = 'Unassigned';
        if ($technician_id) {
            $tech_user = db_fetch_one("SELECT full_name AS name FROM users WHERE id = ?", [$technician_id]);
            $tech_name = $tech_user['name'] ?? 'Technician';
        }

        db_query("UPDATE repairs SET assigned_technician_id = ? WHERE id = ?", [$technician_id, $id]);

        db_insert(
            "INSERT INTO repair_history (repair_id, status_from, status_to, notes, updated_by, created_at)
             VALUES (?, ?, ?, ?, ?, NOW())",
            [$id, $repair['status'], $repair['status'], "Assigned to {$tech_name}", auth_id()]
        );

        set_flash('success', "Technician assignment updated.");
        redirect(url('/admin/repair-detail.php?id=' . $id));
    }

    if ($action === 'update_status') {
        $new_status = $_POST['status'] ?? '';
        $notes      = trim($_POST['notes'] ?? '');

        $allowed = ['received', 'diagnosis_pending', 'estimate_sent', 'estimate_approved', 'in_repair', 'waiting_parts', 'qa_testing', 'ready_for_delivery', 'completed', 'cancelled'];
        if (in_array($new_status, $allowed, true)) {
            $old_status = $repair['status'];
            
            db_query("UPDATE repairs SET status = ?, updated_at = NOW() WHERE id = ?", [$new_status, $id]);

            db_insert(
                "INSERT INTO repair_history (repair_id, status_from, status_to, notes, updated_by, created_at)
                 VALUES (?, ?, ?, ?, ?, NOW())",
                [$id, $old_status, $new_status, $notes ?: "Status changed from {$old_status} to {$new_status}", auth_id()]
            );

            // If marked as completed, ensure final_cost and invoices can be settled
            if ($new_status === 'completed' && (float)$repair['final_cost'] <= 0 && (float)$repair['estimated_cost'] > 0) {
                db_query("UPDATE repairs SET final_cost = estimated_cost, balance_due = GREATEST(0, estimated_cost - advance_paid) WHERE id = ?", [$id]);
            }

            set_flash('success', "Ticket status updated to " . ucwords(str_replace('_', ' ', $new_status)));
        } else {
            set_flash('error', 'Invalid status selected.');
        }
        redirect(url('/admin/repair-detail.php?id=' . $id));
    }

    if ($action === 'approve_estimate') {
        $approved_discount = max(0.0, (float)($_POST['approved_discount'] ?? 0));
        
        $estimate = db_fetch_one("SELECT * FROM repair_estimates WHERE repair_id = ? ORDER BY id DESC LIMIT 1", [$id]);
        if ($estimate) {
            $final_total = max(0.0, (float)$estimate['spare_parts_cost'] + (float)$estimate['labour_cost'] + (float)$estimate['tax_amount'] - $approved_discount);

            db_query(
                "UPDATE repair_estimates SET discount = ?, total_estimate = ?, status = 'approved', responded_at = NOW() WHERE id = ?",
                [$approved_discount, $final_total, $estimate['id']]
            );

            $balance = max(0.0, $final_total - (float)$repair['advance_paid']);
            db_query(
                "UPDATE repairs SET estimated_cost = ?, final_cost = ?, discount_amount = ?, balance_due = ?, customer_approval_status = 'approved', customer_approval_at = NOW(), status = 'estimate_approved' WHERE id = ?",
                [$final_total, $final_total, $approved_discount, $balance, $id]
            );

            db_insert(
                "INSERT INTO repair_history (repair_id, status_from, status_to, notes, updated_by, created_at)
                 VALUES (?, ?, 'estimate_approved', ?, ?, NOW())",
                [$id, $repair['status'], "Estimate approved by admin. Total: ₹" . number_format($final_total, 2), auth_id()]
            );

            set_flash('success', 'Estimate approved successfully.');
        }
        redirect(url('/admin/repair-detail.php?id=' . $id));
    }

    if ($action === 'generate_invoice') {
        $existing = db_fetch_one("SELECT id, invoice_number FROM invoices WHERE invoice_type = 'repair' AND reference_id = ?", [$id]);
        if ($existing) {
            set_flash('info', "Invoice {$existing['invoice_number']} already exists for this repair.");
            redirect(url('/admin/invoice-view.php?id=' . $existing['id']));
        }

        $inv_num = 'INV-REP-' . date('Ymd') . '-' . strtoupper(bin2hex(random_bytes(2)));
        $total = (float)($repair['final_cost'] > 0 ? $repair['final_cost'] : $repair['estimated_cost']);
        $subtotal = round($total / 1.18, 2);
        $tax = round($total - $subtotal, 2);

        $inv_id = db_insert(
            "INSERT INTO invoices (invoice_number, invoice_type, reference_id, user_id, subtotal, tax_amount, discount_amount, grand_total, status, issued_at)
             VALUES (?, 'repair', ?, ?, ?, ?, ?, ?, 'paid', NOW())",
            [$inv_num, $id, $repair['user_id'] ?: null, $subtotal, $tax, (float)$repair['discount_amount'], $total]
        );

        set_flash('success', "Invoice {$inv_num} generated for this repair.");
        redirect(url('/admin/invoice-view.php?id=' . $inv_id));
    }
}

// Fetch technicians list
$tech_users = db_fetch_all(
    "SELECT u.id, u.full_name AS name, u.email 
       FROM users u 
       JOIN roles ro ON ro.id = u.role_id 
      WHERE ro.slug IN ('technician', 'admin') 
   ORDER BY u.full_name ASC"
);

// Fetch estimates
$estimates = db_fetch_all("SELECT * FROM repair_estimates WHERE repair_id = ? ORDER BY id DESC", [$id]);

// Fetch allocated parts
$parts = db_fetch_all("SELECT * FROM repair_parts WHERE repair_id = ? ORDER BY id ASC", [$id]);

// Fetch ticket history
$history = db_fetch_all(
    "SELECT rh.*, u.full_name AS author_name 
       FROM repair_history rh 
  LEFT JOIN users u ON u.id = rh.updated_by 
      WHERE rh.repair_id = ? 
   ORDER BY rh.created_at DESC",
    [$id]
);

// Check if invoice exists
$invoice = db_fetch_one("SELECT * FROM invoices WHERE invoice_type = 'repair' AND reference_id = ?", [$id]);

$pageTitle = 'Repair Ticket #' . $repair['repair_ticket_number'];
?>

<div class="space-y-6 max-w-5xl mx-auto">
    <!-- Header -->
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div class="flex items-center gap-3">
            <a href="<?= url('/admin/repairs.php') ?>" class="p-2 rounded-xl border border-slate-200 text-slate-500 hover:bg-slate-100 transition">
                <i data-lucide="arrow-left" class="w-4 h-4"></i>
            </a>
            <div>
                <div class="flex items-center gap-2">
                    <h1 class="text-xl font-black text-slate-900 tracking-tight"><?= e($repair['repair_ticket_number']) ?></h1>
                    <?php
                    $badge_class = match ($repair['status']) {
                        'ready_for_delivery', 'completed' => 'bg-emerald-50 text-emerald-700 border-emerald-100',
                        'cancelled'                       => 'bg-rose-50 text-rose-700 border-rose-100',
                        'in_repair', 'qa_testing'         => 'bg-blue-50 text-blue-700 border-blue-100',
                        'waiting_parts'                   => 'bg-purple-50 text-purple-700 border-purple-100',
                        default                           => 'bg-amber-50 text-amber-800 border-amber-100',
                    };
                    ?>
                    <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-xs font-bold border <?= $badge_class ?>">
                        <?= ucwords(str_replace('_', ' ', $repair['status'])) ?>
                    </span>
                    <?php if ($repair['priority'] === 'urgent'): ?>
                        <span class="px-2 py-0.5 bg-rose-600 text-white text-[10px] font-black rounded uppercase">Urgent</span>
                    <?php endif; ?>
                </div>
                <p class="text-xs text-slate-500 mt-0.5">Booked on <?= date('d M Y, h:i A', strtotime($repair['created_at'])) ?></p>
            </div>
        </div>

        <div class="flex items-center gap-2">
            <?php if ($invoice): ?>
                <a href="<?= url('/admin/invoice-view.php?id=' . (int)$invoice['id']) ?>" class="inline-flex items-center gap-1.5 px-3.5 py-2 rounded-xl bg-blue-50 text-blue-700 border border-blue-200 text-xs font-bold hover:bg-blue-100 transition">
                    <i data-lucide="receipt" class="w-4 h-4"></i>
                    <span>View Invoice (<?= e($invoice['invoice_number']) ?>)</span>
                </a>
            <?php elseif (in_array($repair['status'], ['ready_for_delivery', 'completed'])): ?>
                <form method="POST" action="<?= url('/admin/repair-detail.php?id=' . $id) ?>">
                    <?= csrf_field() ?>
                    <input type="hidden" name="action" value="generate_invoice">
                    <button type="submit" class="inline-flex items-center gap-1.5 px-4 py-2 rounded-xl bg-emerald-600 hover:bg-emerald-700 text-white text-xs font-bold shadow-sm transition">
                        <i data-lucide="file-text" class="w-4 h-4"></i>
                        <span>Generate Invoice</span>
                    </button>
                </form>
            <?php endif; ?>

            <button onclick="window.print()" class="p-2 rounded-xl border border-slate-200 hover:bg-slate-50 text-slate-600 transition" title="Print Ticket">
                <i data-lucide="printer" class="w-4 h-4"></i>
            </button>
        </div>
    </div>

    <!-- 2 Column Overview Grid -->
    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
        <!-- Customer & Service Mode -->
        <div class="p-5 rounded-2xl bg-white border border-slate-200/80 shadow-xs space-y-3">
            <h3 class="text-xs font-bold uppercase tracking-wider text-slate-400">Customer &amp; Service Mode</h3>
            <div class="space-y-1.5 text-xs">
                <p class="font-bold text-slate-900 text-sm"><?= e($repair['customer_name']) ?></p>
                <p class="text-slate-600 flex items-center gap-2">
                    <i data-lucide="phone" class="w-3.5 h-3.5 text-slate-400"></i>
                    <?= e($repair['customer_phone']) ?>
                </p>
                <p class="text-slate-600 flex items-center gap-2">
                    <i data-lucide="mail" class="w-3.5 h-3.5 text-slate-400"></i>
                    <?= e($repair['customer_email']) ?>
                </p>
                <div class="pt-2 border-t border-slate-100 flex items-center gap-2">
                    <span class="text-slate-400">Service Mode:</span>
                    <span class="font-bold text-slate-800 capitalize"><?= str_replace('_', ' ', $repair['service_mode']) ?></span>
                </div>
                <?php if (!empty($repair['pickup_address'])): ?>
                    <div class="text-slate-500 bg-slate-50 p-2.5 rounded-xl border border-slate-100 mt-2">
                        <span class="font-bold text-slate-700 block mb-0.5">Pickup Address:</span>
                        <?= e($repair['pickup_address']) ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Device & Reported Problem -->
        <div class="p-5 rounded-2xl bg-white border border-slate-200/80 shadow-xs space-y-3">
            <h3 class="text-xs font-bold uppercase tracking-wider text-slate-400">Device &amp; Diagnostics</h3>
            <div class="space-y-1.5 text-xs">
                <p class="font-bold text-slate-900 text-sm"><?= e($repair['device_brand']) ?> <?= e($repair['device_model']) ?></p>
                <div class="flex items-center gap-3 text-slate-500">
                    <span>Issue: <strong class="text-slate-800 capitalize"><?= str_replace('_', ' ', $repair['issue_type']) ?></strong></span>
                    <?php if (!empty($repair['device_imei_serial'])): ?>
                        <span>IMEI/Serial: <strong class="text-slate-800 font-mono"><?= e($repair['device_imei_serial']) ?></strong></span>
                    <?php endif; ?>
                </div>
                <div class="p-2.5 rounded-xl bg-slate-50 border border-slate-100 text-slate-700 whitespace-pre-wrap">
                    <?= e($repair['issue_description']) ?>
                </div>
                <?php if (!empty($repair['condition_notes'])): ?>
                    <p class="text-slate-500 text-[11px]">Condition notes: <?= e($repair['condition_notes']) ?></p>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Management Controls (Status & Technician) -->
    <div class="p-5 rounded-2xl bg-white border border-slate-200/80 shadow-xs grid grid-cols-1 md:grid-cols-2 gap-6">
        <!-- Assign Technician -->
        <div>
            <h3 class="text-xs font-bold uppercase tracking-wider text-slate-400 mb-2">Technician Assignment</h3>
            <form method="POST" action="<?= url('/admin/repair-detail.php?id=' . $id) ?>" class="flex gap-2">
                <?= csrf_field() ?>
                <input type="hidden" name="action" value="assign_technician">
                <select name="technician_id" class="flex-1 px-3 py-2 text-xs rounded-xl border border-slate-200 bg-white font-medium">
                    <option value="">-- Unassigned --</option>
                    <?php foreach ($tech_users as $tu): ?>
                        <option value="<?= (int)$tu['id'] ?>" <?= (int)$repair['assigned_technician_id'] === (int)$tu['id'] ? 'selected' : '' ?>>
                            <?= e($tu['name']) ?> (<?= e($tu['email']) ?>)
                        </option>
                    <?php endforeach; ?>
                </select>
                <button type="submit" class="px-4 py-2 rounded-xl bg-slate-900 hover:bg-slate-800 text-white font-bold text-xs transition">
                    Assign
                </button>
            </form>
            <p class="text-[11px] text-slate-400 mt-1.5">Technicians can view tickets and update diagnostic workbenches.</p>
        </div>

        <!-- Update Status -->
        <div>
            <h3 class="text-xs font-bold uppercase tracking-wider text-slate-400 mb-2">Change Ticket Status</h3>
            <form method="POST" action="<?= url('/admin/repair-detail.php?id=' . $id) ?>" class="space-y-2">
                <?= csrf_field() ?>
                <input type="hidden" name="action" value="update_status">
                <div class="flex gap-2">
                    <select name="status" class="flex-1 px-3 py-2 text-xs rounded-xl border border-slate-200 bg-white font-bold">
                        <?php
                        $all_statuses = [
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
                        foreach ($all_statuses as $k => $v):
                        ?>
                            <option value="<?= $k ?>" <?= $repair['status'] === $k ? 'selected' : '' ?>><?= $v ?></option>
                        <?php endforeach; ?>
                    </select>
                    <button type="submit" class="px-4 py-2 rounded-xl bg-blue-600 hover:bg-blue-700 text-white font-bold text-xs transition">
                        Update Status
                    </button>
                </div>
                <input type="text" name="notes" placeholder="Optional internal progress note..."
                       class="w-full px-3 py-1.5 text-xs rounded-xl border border-slate-200">
            </form>
        </div>
    </div>

    <!-- Diagnostic Estimates Section -->
    <div class="p-6 rounded-2xl bg-white border border-slate-200/80 shadow-xs space-y-4">
        <div class="flex items-center justify-between border-b border-slate-100 pb-3">
            <h3 class="text-sm font-bold text-slate-900 flex items-center gap-2">
                <i data-lucide="calculator" class="w-4 h-4 text-blue-600"></i>
                Diagnostic Estimates &amp; Quotes
            </h3>
            <span class="text-xs font-bold text-slate-600">
                Customer Status: <span class="uppercase text-<?= $repair['customer_approval_status'] === 'approved' ? 'emerald' : 'amber' ?>-600"><?= e($repair['customer_approval_status']) ?></span>
            </span>
        </div>

        <?php if (empty($estimates)): ?>
            <p class="text-xs text-slate-400 py-4 text-center">No diagnostic estimates have been submitted by the technician yet.</p>
        <?php else: ?>
            <?php foreach ($estimates as $est): ?>
                <div class="p-4 rounded-xl border border-slate-200 bg-slate-50/50 space-y-3">
                    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-2">
                        <div>
                            <span class="text-xs font-bold uppercase tracking-wider text-slate-500">Estimate Quote</span>
                            <span class="ml-2 px-2 py-0.5 rounded text-[10px] font-bold uppercase bg-<?= $est['status'] === 'approved' ? 'emerald' : 'amber' ?>-100 text-<?= $est['status'] === 'approved' ? 'emerald' : 'amber' ?>-800">
                                <?= e($est['status']) ?>
                            </span>
                        </div>
                        <div class="text-lg font-black text-slate-900">
                            <?= format_currency($est['total_estimate']) ?>
                        </div>
                    </div>

                    <?php if (!empty($est['diagnostic_notes'])): ?>
                        <p class="text-xs text-slate-600 bg-white p-2.5 rounded-lg border border-slate-200 whitespace-pre-wrap">
                            <?= e($est['diagnostic_notes']) ?>
                        </p>
                    <?php endif; ?>

                    <div class="grid grid-cols-2 sm:grid-cols-4 gap-2 text-xs pt-1">
                        <div><span class="text-slate-400">Spare Parts:</span> <strong class="text-slate-700"><?= format_currency($est['spare_parts_cost']) ?></strong></div>
                        <div><span class="text-slate-400">Labour:</span> <strong class="text-slate-700"><?= format_currency($est['labour_cost']) ?></strong></div>
                        <div><span class="text-slate-400">Taxes:</span> <strong class="text-slate-700"><?= format_currency($est['tax_amount']) ?></strong></div>
                        <div><span class="text-slate-400">Discount:</span> <strong class="text-slate-700"><?= format_currency($est['discount']) ?></strong></div>
                    </div>

                    <?php if ($est['status'] !== 'approved'): ?>
                        <form method="POST" action="<?= url('/admin/repair-detail.php?id=' . $id) ?>" class="flex items-center gap-3 pt-3 border-t border-slate-200">
                            <?= csrf_field() ?>
                            <input type="hidden" name="action" value="approve_estimate">
                            <label class="text-xs font-bold text-slate-700">Approve with Discount (₹):</label>
                            <input type="number" name="approved_discount" step="0.01" min="0" value="<?= e((string)$est['discount']) ?>"
                                   class="w-28 px-3 py-1.5 text-xs rounded-lg border border-slate-200">
                            <button type="submit" class="px-4 py-1.5 rounded-lg bg-emerald-600 hover:bg-emerald-700 text-white font-bold text-xs shadow-sm transition">
                                Approve Estimate &amp; Set Price
                            </button>
                        </form>
                    <?php endif; ?>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>

    <!-- Allocated Spare Parts -->
    <div class="rounded-2xl bg-white border border-slate-200/80 shadow-xs overflow-hidden">
        <div class="p-4 border-b border-slate-100 flex items-center justify-between">
            <h3 class="font-bold text-slate-900 text-sm">Spare Parts Used</h3>
            <span class="text-xs text-slate-400"><?= count($parts) ?> recorded</span>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full text-left text-xs">
                <thead class="bg-slate-50 border-b border-slate-200 text-[11px] font-bold uppercase tracking-wider text-slate-500">
                    <tr>
                        <th class="px-5 py-3">Part Description</th>
                        <th class="px-5 py-3 text-right">Quantity</th>
                        <th class="px-5 py-3 text-right">Unit Price</th>
                        <th class="px-5 py-3 text-right">Line Total</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 text-slate-700">
                    <?php if (empty($parts)): ?>
                        <tr><td colspan="4" class="px-5 py-6 text-center text-slate-400">No spare parts recorded for this repair.</td></tr>
                    <?php else: ?>
                        <?php foreach ($parts as $p): ?>
                            <tr>
                                <td class="px-5 py-3 font-bold text-slate-900"><?= e($p['part_name']) ?></td>
                                <td class="px-5 py-3 text-right font-medium"><?= (int)$p['quantity'] ?></td>
                                <td class="px-5 py-3 text-right text-slate-600"><?= format_currency($p['unit_price']) ?></td>
                                <td class="px-5 py-3 text-right font-bold text-slate-900"><?= format_currency((int)$p['quantity'] * (float)$p['unit_price']) ?></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Timeline History -->
    <div class="p-6 rounded-2xl bg-white border border-slate-200/80 shadow-xs space-y-4">
        <h3 class="text-sm font-bold text-slate-900">Repair History &amp; Audit Trail</h3>
        <?php if (empty($history)): ?>
            <p class="text-xs text-slate-400">No activity logged yet.</p>
        <?php else: ?>
            <ol class="relative border-l border-slate-200 ml-3 space-y-4 text-xs">
                <?php foreach ($history as $h): ?>
                    <li class="ml-4">
                        <div class="absolute -left-1.5 mt-1.5 w-3 h-3 rounded-full border-2 border-white bg-blue-600"></div>
                        <div class="flex items-center gap-2">
                            <span class="font-bold text-slate-900"><?= ucwords(str_replace('_', ' ', $h['status_to'])) ?></span>
                            <span class="text-[11px] text-slate-400"><?= date('d M Y, h:i A', strtotime($h['created_at'])) ?></span>
                            <?php if (!empty($h['author_name'])): ?>
                                <span class="text-[11px] text-slate-500 font-medium">by <?= e($h['author_name']) ?></span>
                            <?php endif; ?>
                        </div>
                        <?php if (!empty($h['notes'])): ?>
                            <p class="text-slate-600 mt-0.5"><?= e($h['notes']) ?></p>
                        <?php endif; ?>
                    </li>
                <?php endforeach; ?>
            </ol>
        <?php endif; ?>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/admin-footer.php'; ?>

