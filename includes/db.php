<?php
/**
 * Practical 8 – database connectivity.
 *
 *  - db()   : MySQLi connection (used by most of the project – Practicals 9, 10, 11 ...)
 *  - pdo()  : PDO connection (Practical 8 intermediate: PDO + prepared statements)
 *  - db_all / db_one / db_exec : tiny helpers that ALWAYS use prepared statements,
 *    so user input is never concatenated into SQL (prevents SQL injection).
 *
 * Errors are turned into exceptions; the real message is written to the PHP error
 * log and the visitor only sees a friendly message.
 */

mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

function db(): mysqli
{
    static $conn = null;
    if ($conn instanceof mysqli) {
        return $conn;
    }
    try {
        $conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
        $conn->set_charset('utf8mb4');
    } catch (mysqli_sql_exception $e) {
        error_log('[StudentHub] DB connection failed: ' . $e->getMessage());
        http_response_code(500);
        if (defined('API_REQUEST')) {
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode(['success' => false, 'message' => 'Database connection failed.']);
        } else {
            echo '<h1>Database connection failed</h1>'
               . '<p>Start MySQL in XAMPP, import <code>database/studentHub.sql</code> '
               . 'and check <code>includes/config.php</code>.</p>';
        }
        exit;
    }
    return $conn;
}

/** PDO connection (same database) – demonstrates the PDO alternative. */
function pdo(): PDO
{
    static $pdo = null;
    if ($pdo instanceof PDO) {
        return $pdo;
    }
    $dsn = 'mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=utf8mb4';
    $pdo = new PDO($dsn, DB_USER, DB_PASS, [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES   => false,
    ]);
    return $pdo;
}

/**
 * Prepare + bind + execute.
 * $types follows mysqli::bind_param ("s" string, "i" int, "d" double).
 */
function db_query(string $sql, string $types = '', array $params = []): mysqli_stmt
{
    $stmt = db()->prepare($sql);
    if ($types !== '') {
        $stmt->bind_param($types, ...$params);
    }
    $stmt->execute();
    return $stmt;
}

/** All rows as an array of associative arrays. */
function db_all(string $sql, string $types = '', array $params = []): array
{
    $stmt = db_query($sql, $types, $params);
    $rows = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt->close();
    return $rows;
}

/** First row or null. */
function db_one(string $sql, string $types = '', array $params = []): ?array
{
    $rows = db_all($sql, $types, $params);
    return $rows[0] ?? null;
}

/** Single value (first column of first row) or null. */
function db_value(string $sql, string $types = '', array $params = [])
{
    $row = db_one($sql, $types, $params);
    return $row ? reset($row) : null;
}

/** INSERT / UPDATE / DELETE. Returns the number of affected rows. */
function db_exec(string $sql, string $types = '', array $params = []): int
{
    $stmt = db_query($sql, $types, $params);
    $affected = $stmt->affected_rows;
    $stmt->close();
    return $affected;
}

function db_insert_id(): int
{
    return (int) db()->insert_id;
}
