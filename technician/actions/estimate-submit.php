<?php
require_once __DIR__ . '/../../includes/config.php';
require_once __DIR__ . '/../../includes/db.php';
require_once __DIR__ . '/../../includes/functions.php';
require_once __DIR__ . '/../../includes/auth.php';

session_init();
require_role('admin', 'manager', 'technician');

$repairId = (int)($_POST['repair_id'] ?? 0);
$diagnosticNotes = trim($_POST['diagnostic_notes'] ?? '');
$sparePartsCost = (float)($_POST['spare_parts_cost'] ?? 0);
$labourCost = (float)($_POST['labour_cost'] ?? 0);
$taxAmount = (float)($_POST['tax_amount'] ?? 0);
$discount = (float)($_POST['discount'] ?? 0);
$total = max(0, ($sparePartsCost + $labourCost + $taxAmount) - $discount);

if ($repairId > 0 && verify_csrf()) {
    db_insert(
        "INSERT INTO repair_estimates (repair_id, spare_parts_cost, labour_cost, tax_amount, discount, total_estimate, diagnostic_notes, status, sent_at) VALUES (?, ?, ?, ?, ?, ?, ?, 'sent', NOW())",
        [$repairId, $sparePartsCost, $labourCost, $taxAmount, $discount, $total, $diagnosticNotes]
    );

    db_query("UPDATE repairs SET estimated_cost = ?, status = 'estimate_sent', updated_at = NOW() WHERE id = ?", [$total, $repairId]);

    db_query(
        "INSERT INTO repair_history (repair_id, status_from, status_to, notes, updated_by) VALUES (?, 'diagnosis_pending', 'estimate_sent', ?, ?)",
        [$repairId, "Estimate of " . format_currency($total) . " sent for approval: {$diagnosticNotes}", auth_id()]
    );

    set_flash('success', 'Diagnostic estimate generated and sent for customer approval.');
}

redirect('/technician/repair-detail.php?id=' . $repairId);

