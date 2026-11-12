<?php
/**
 * MobileShopFlat — Database Helpers (MySQLi Singleton + Query Utilities)
 */
require_once __DIR__ . '/config.php';

/**
 * Returns a singleton MySQLi connection.
 */
function db(): mysqli
{
    static $conn = null;
    if ($conn === null) {
        mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
        $conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME, DB_PORT);
        $conn->set_charset(DB_CHARSET);
    }
    return $conn;
}

/**
 * Infer MySQLi bind-type string from an array of PHP values.
 * i = integer, d = float/double, s = string, b = blob (not used automatically)
 */
function _db_types(array $params): string
{
    $types = '';
    foreach ($params as $p) {
        if (is_int($p)) {
            $types .= 'i';
        } elseif (is_float($p)) {
            $types .= 'd';
        } else {
            $types .= 's';
        }
    }
    return $types;
}

/**
 * Prepare and execute a statement, returning the mysqli_stmt.
 * Uses PHP 8.2 native prepared statement execution for speed and null-safety.
 */
function _db_execute(string $sql, array $params): mysqli_stmt
{
    $stmt = db()->prepare($sql);
    if (!empty($params)) {
        $stmt->execute(array_values($params));
    } else {
        $stmt->execute();
    }
    return $stmt;
}

/**
 * Fetch all matching rows as an associative array.
 */
function db_fetch_all(string $sql, array $params = []): array
{
    $stmt   = _db_execute($sql, $params);
    $result = $stmt->get_result();
    $rows   = $result->fetch_all(MYSQLI_ASSOC);
    $stmt->close();
    return $rows;
}

/**
 * Fetch a single row (first match) or null.
 */
function db_fetch_one(string $sql, array $params = []): ?array
{
    $stmt   = _db_execute($sql, $params);
    $result = $stmt->get_result();
    $row    = $result->fetch_assoc();
    $stmt->close();
    return $row ?: null;
}

/**
 * Fetch a single scalar value from the first column of the first row.
 */
function db_fetch_value(string $sql, array $params = []): mixed
{
    $stmt   = _db_execute($sql, $params);
    $result = $stmt->get_result();
    $row    = $result->fetch_array(MYSQLI_NUM);
    $stmt->close();
    return $row[0] ?? null;
}

/**
 * Execute INSERT / UPDATE / DELETE. Returns affected_rows count.
 */
function db_query(string $sql, array $params = []): int
{
    $stmt = _db_execute($sql, $params);
    $affected = $stmt->affected_rows;
    $stmt->close();
    return $affected;
}

/**
 * Execute an INSERT statement. Returns the last inserted ID.
 */
function db_insert(string $sql, array $params = []): int
{
    $stmt = _db_execute($sql, $params);
    $id   = (int) db()->insert_id;
    $stmt->close();
    return $id;
}

// ─── Transaction helpers ──────────────────────────────────────────────────────

function db_begin(): void    { db()->begin_transaction(); }
function db_commit(): void   { db()->commit(); }
function db_rollback(): void { db()->rollback(); }

/**
 * Execute a callback within an atomic transaction with automatic rollback on error.
 */
function db_transaction(callable $callback): mixed
{
    db_begin();
    try {
        $result = $callback();
        db_commit();
        return $result;
    } catch (\Throwable $e) {
        db_rollback();
        throw $e;
    }
}
