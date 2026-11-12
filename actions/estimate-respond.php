<?php
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth.php';

session_init();

$repairId = (int)($_POST['repair_id'] ?? 0);
$estimateId = (int)($_POST['estimate_id'] ?? 0);
$action = $_POST['action'] ?? '';
$notes = trim($_POST['notes'] ?? '');
$ticket = $_POST['ticket'] ?? '';
$phone = $_POST['phone'] ?? '';

if ($repairId > 0 && verify_csrf()) {
    $newStatus = ($action === 'approve') ? 'approved' : 'rejected';
    db_query("UPDATE repair_estimates SET status = ?, responded_at = NOW() WHERE id = ? AND repair_id = ?", [$newStatus, $estimateId, $repairId]);

    $repairStatus = ($action === 'approve') ? 'estimate_approved' : 'cancelled';
    db_query("UPDATE repairs SET customer_approval_status = ?, status = ?, updated_at = NOW() WHERE id = ?", [$newStatus, $repairStatus, $repairId]);

    db_query(
        "INSERT INTO repair_history (repair_id, status_from, status_to, notes, updated_by) VALUES (?, 'estimate_sent', ?, ?, ?)",
        [$repairId, $repairStatus, "Customer {$newStatus} the repair estimate: {$notes}", auth_id()]
    );

    set_flash('success', "Your response has been saved ({$newStatus}).");
}

redirect('/repair-track.php?ticket=' . urlencode($ticket) . '&phone=' . urlencode($phone));

