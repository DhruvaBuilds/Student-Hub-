<?php
/**
 * Practical 8 – database connection test page (take your screenshot here).
 * Only reachable from the local machine; delete this file before real deployment.
 */
require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/db.php';

if (!in_array($_SERVER['REMOTE_ADDR'] ?? '', ['127.0.0.1', '::1'], true)) {
    http_response_code(403);
    exit('This page is only available on localhost.');
}

$results = [];
function check(string $label, callable $fn): void
{
    global $results;
    try {
        $results[] = [$label, true, (string) $fn()];
    } catch (Throwable $e) {
        $results[] = [$label, false, $e->getMessage()];
    }
}

check('MySQLi connection', fn() => 'Connected to ' . DB_NAME . ' (server ' . db()->server_info . ')');
check('PDO connection', fn() => 'Connected, driver ' . pdo()->getAttribute(PDO::ATTR_DRIVER_NAME));
foreach (['users', 'students', 'events', 'registrations', 'audit_logs', 'remember_tokens'] as $t) {
    check("Table $t", fn() => db_value("SELECT COUNT(*) FROM `$t`") . ' rows');
}
check('Prepared statement (PDO)', function () {
    $st = pdo()->prepare('SELECT name FROM users WHERE role = ? LIMIT 1');
    $st->execute(['admin']);
    return 'First admin: ' . $st->fetchColumn();
});
check('Stored procedure sp_dashboard_stats()', function () {
    $st = pdo()->query('CALL sp_dashboard_stats()');
    $row = $st->fetch();
    $st->closeCursor();
    return json_encode($row);
});
?>
<!DOCTYPE html>
<html lang="en"><head><meta charset="UTF-8"><title>StudentHub – connection test</title>
<style>body{font-family:Arial,sans-serif;max-width:760px;margin:40px auto;padding:0 15px}
li{margin:6px 0}.ok{color:#15803d}.fail{color:#b91c1c}</style></head>
<body>
<h1>StudentHub – database connection test</h1>
<ul>
<?php foreach ($results as [$label, $ok, $msg]): ?>
    <li class="<?= $ok ? 'ok' : 'fail' ?>"><?= $ok ? '✔' : '✘' ?> <strong><?= htmlspecialchars($label) ?></strong> – <?= htmlspecialchars($msg) ?></li>
<?php endforeach; ?>
</ul>
<p><a href="index.php">Go to StudentHub</a></p>
</body></html>
