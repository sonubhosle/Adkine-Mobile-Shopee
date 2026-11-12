<?php
/**
 * MobileShopFlat — Repair Service Functions
 */

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/functions.php';

function repair_book(array $data, ?int $userId = null): array
{
    $ticketNumber = 'REP-' . date('Ym') . '-' . strtoupper(substr(bin2hex(random_bytes(3)), 0, 5));

    $sql = "INSERT INTO repairs (
        repair_ticket_number, user_id, customer_name, customer_phone, customer_email,
        device_brand, device_model, device_imei_serial, issue_type, issue_description,
        condition_notes, service_mode, pickup_address, priority, device_images, status
    ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'received')";

    $repairId = db_insert($sql, [
        $ticketNumber,
        $userId,
        $data['customer_name'],
        $data['customer_phone'],
        $data['customer_email'],
        $data['device_brand'],
        $data['device_model'],
        $data['device_imei_serial'] ?? null,
        $data['issue_type'],
        $data['issue_description'],
        $data['condition_notes'] ?? null,
        $data['service_mode'],
        $data['pickup_address'] ?? null,
        $data['priority'] ?? 'normal',
        $data['device_images'] ?? null,
    ]);

    db_query(
        "INSERT INTO repair_history (repair_id, status_from, status_to, notes, updated_by) VALUES (?, NULL, 'received', 'Device repair ticket submitted by customer.', ?)",
        [$repairId, $userId]
    );

    return [
        'status' => true,
        'repair_id' => $repairId,
        'ticket_number' => $ticketNumber,
    ];
}

function repair_get_details(string $ticket, string $phone): ?array
{
    $sql = "SELECT r.*, u.full_name as technician_name
            FROM repairs r
            LEFT JOIN users u ON r.assigned_technician_id = u.id
            WHERE r.repair_ticket_number = ? AND r.customer_phone = ? 
            LIMIT 1";
    $repair = db_fetch_one($sql, [$ticket, $phone]);
    if (!$repair) {
        return null;
    }

    $estimates = db_fetch_all("SELECT * FROM repair_estimates WHERE repair_id = ? ORDER BY id DESC", [$repair['id']]);
    $history = db_fetch_all("SELECT * FROM repair_history WHERE repair_id = ? ORDER BY id ASC", [$repair['id']]);
    $parts = db_fetch_all("SELECT * FROM repair_parts WHERE repair_id = ?", [$repair['id']]);

    return [
        'repair' => $repair,
        'estimates' => $estimates,
        'history' => $history,
        'parts' => $parts,
    ];
}

