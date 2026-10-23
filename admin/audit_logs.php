<?php
/** Practical 14 – audit log viewer: who did what, when and from which IP. */
require_once __DIR__ . '/../includes/bootstrap.php';
require_role('admin');

$action = get('action');
$q      = get('q');

$where = []; $types = ''; $params = [];
if ($action !== '') { $where[] = 'a.action = ?'; $types .= 's'; $params[] = $action; }
if ($q !== '') {
    $like = '%' . addcslashes($q, '%_\\') . '%';
    $where[] = '(a.details LIKE ? OR u.name LIKE ? OR a.ip_address LIKE ?)';
    $types .= 'sss'; array_push($params, $like, $like, $like);
}
$w = $where ? 'WHERE ' . implode(' AND ', $where) : '';

$total = (int) db_value("SELECT COUNT(*) FROM audit_logs a LEFT JOIN users u ON u.id = a.user_id $w", $types, $params);
[$page, $perPage, $offset, $totalPages] = paginate($total, 15);
$logs = db_all(
    "SELECT a.id, a.action, a.details, a.ip_address, a.created_at, u.name AS user_name
       FROM audit_logs a LEFT JOIN users u ON u.id = a.user_id
       $w ORDER BY a.id DESC LIMIT ? OFFSET ?",
    $types . 'ii',
    array_merge($params, [$perPage, $offset])
);
$actions = array_column(db_all('SELECT DISTINCT action FROM audit_logs ORDER BY action'), 'action');

$pageTitle = 'Audit Log';
$activeNav = 'audit';
require ROOT_PATH . '/includes/header.php';
?>
<main id="main-content" class="admin-main">
    <section class="breadcrumb">
        <div class="container"><p><a href="<?= e(url('admin/dashboard.php')) ?>">Admin</a> &gt; <span>Audit Log</span></p></div>
    </section>

    <section class="admin-section">
        <div class="container">
            <div class="admin-head"><h1>Audit Log</h1></div>

            <form class="filter-bar" method="get" action="">
                <div><label for="q">Search</label>
                    <input type="search" id="q" name="q" value="<?= e($q) ?>" placeholder="Details, user or IP"></div>
                <div><label for="action">Action</label>
                    <select id="action" name="action">
                        <option value="">All actions</option>
                        <?php foreach ($actions as $a): ?>
                            <option value="<?= e($a) ?>" <?= $action === $a ? 'selected' : '' ?>><?= e($a) ?></option>
                        <?php endforeach; ?>
                    </select></div>
                <div class="filter-buttons">
                    <button type="submit" class="btn primary-btn">Apply</button>
                    <a class="btn btn-outline" href="<?= e(url('admin/audit_logs.php')) ?>">Reset</a>
                </div>
            </form>

            <p class="result-count"><?= $total ?> entr<?= $total === 1 ? 'y' : 'ies' ?></p>

            <div class="table-wrap">
                <table class="data-table">
                    <caption class="visually-hidden">Audit log entries</caption>
                    <thead><tr><th scope="col">#</th><th scope="col">When</th><th scope="col">User</th><th scope="col">Action</th>
                        <th scope="col">Details</th><th scope="col">IP</th></tr></thead>
                    <tbody>
                    <?php if (!$logs): ?><tr><td colspan="6" class="empty-row">No log entries.</td></tr><?php endif; ?>
                    <?php foreach ($logs as $l): ?>
                        <tr>
                            <td><?= (int) $l['id'] ?></td>
                            <td><?= e(date('d M Y, h:i:s A', strtotime($l['created_at']))) ?></td>
                            <td><?= e($l['user_name'] ?? 'guest') ?></td>
                            <td><code><?= e($l['action']) ?></code></td>
                            <td><?= e($l['details']) ?></td>
                            <td><?= e($l['ip_address']) ?></td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <?= pagination_links($page, $totalPages) ?>
        </div>
    </section>
</main>
<?php require ROOT_PATH . '/includes/footer.php';
