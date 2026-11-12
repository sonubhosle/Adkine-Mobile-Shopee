<?php
require_once __DIR__ . '/../../includes/config.php';
require_once __DIR__ . '/../../includes/db.php';
require_once __DIR__ . '/../../includes/functions.php';
require_once __DIR__ . '/../../includes/auth.php';

session_init();
require_role('admin', 'manager', 'technician');

$repairId = (int)($_POST['repair_id'] ?? 0);
$status = trim($_POST['status'] ?? '');
$notes = trim($_POST['notes'] ?? '');

if ($repairId > 0 && !empty($status) && verify_csrf()) {
    $repair = db_fetch_one("SELECT status FROM repairs WHERE id = ?", [$repairId]);
    $oldStatus = $repair['status'] ?? null;

    db_query("UPDATE repairs SET status = ?, updated_at = NOW() WHERE id = ?", [$status, $repairId]);
    db_query(
        "INSERT INTO repair_history (repair_id, status_from, status_to, notes, updated_by) VALUES (?, ?, ?, ?, ?)",
        [$repairId, $oldStatus, $status, $notes, auth_id()]
    );

    set_flash('success', "Ticket status updated to " . ucwords(str_replace('_', ' ', $status)));
}

redirect('/technician/repair-detail.php?id=' . $repairId);

