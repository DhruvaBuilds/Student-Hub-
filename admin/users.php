<?php
/** Practical 14 (intermediate) – user accounts with an active / inactive status toggle. */
require_once __DIR__ . '/../includes/bootstrap.php';
require_role('admin');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_require_post();
    $id   = (int) ($_POST['id'] ?? 0);
    $user = $id ? db_one('SELECT id, name, status FROM users WHERE id = ?', 'i', [$id]) : null;

    if (!$user) {
        flash_set('error', 'User not found.');
    } elseif ($id === current_user_id()) {
        flash_set('error', 'You cannot deactivate your own account.');
    } else {
        $new = $user['status'] === 'active' ? 'inactive' : 'active';
        db_exec('UPDATE users SET status = ? WHERE id = ?', 'si', [$new, $id]);
        if ($new === 'inactive') {
            db_exec('DELETE FROM remember_tokens WHERE user_id = ?', 'i', [$id]);   // end remember-me sessions too
        }
        audit_log('user_status', "User #$id {$user['name']} set to $new");
        flash_set('success', "{$user['name']} is now $new.");
    }
    $qs = $_SERVER['QUERY_STRING'] ?? '';
    header('Location: ' . url('admin/users.php') . ($qs !== '' ? '?' . $qs : ''));
    exit;
}

$q      = get('q');
$role   = in_array(get('role'), ['admin', 'student'], true) ? get('role') : '';
$status = in_array(get('status'), ['active', 'inactive'], true) ? get('status') : '';

$where = []; $types = ''; $params = [];
if ($q !== '') {
    $like = '%' . addcslashes($q, '%_\\') . '%';
    $where[] = '(name LIKE ? OR email LIKE ?)'; $types .= 'ss'; array_push($params, $like, $like);
}
if ($role !== '')   { $where[] = 'role = ?';   $types .= 's'; $params[] = $role; }
if ($status !== '') { $where[] = 'status = ?'; $types .= 's'; $params[] = $status; }
$w = $where ? 'WHERE ' . implode(' AND ', $where) : '';

$total = (int) db_value("SELECT COUNT(*) FROM users $w", $types, $params);
[$page, $perPage, $offset, $totalPages] = paginate($total, 10);
$users = db_all(
    "SELECT id, name, email, role, status, last_login, created_at FROM users $w ORDER BY id ASC LIMIT ? OFFSET ?",
    $types . 'ii',
    array_merge($params, [$perPage, $offset])
);

$pageTitle = 'Manage Users';
$activeNav = 'users';
require ROOT_PATH . '/includes/header.php';
?>
<main id="main-content" class="admin-main">
    <section class="breadcrumb">
        <div class="container"><p><a href="<?= e(url('admin/dashboard.php')) ?>">Admin</a> &gt; <span>Users</span></p></div>
    </section>

    <section class="admin-section">
        <div class="container">
            <div class="admin-head"><h1>User Accounts</h1></div>

            <form class="filter-bar" method="get" action="">
                <div><label for="q">Search</label>
                    <input type="search" id="q" name="q" value="<?= e($q) ?>" placeholder="Name or email"></div>
                <div><label for="role">Role</label>
                    <select id="role" name="role">
                        <option value="">All roles</option>
                        <option value="admin" <?= $role === 'admin' ? 'selected' : '' ?>>Admin</option>
                        <option value="student" <?= $role === 'student' ? 'selected' : '' ?>>Student</option>
                    </select></div>
                <div><label for="status">Status</label>
                    <select id="status" name="status">
                        <option value="">All</option>
                        <option value="active" <?= $status === 'active' ? 'selected' : '' ?>>Active</option>
                        <option value="inactive" <?= $status === 'inactive' ? 'selected' : '' ?>>Inactive</option>
                    </select></div>
                <div class="filter-buttons">
                    <button type="submit" class="btn primary-btn">Apply</button>
                    <a class="btn btn-outline" href="<?= e(url('admin/users.php')) ?>">Reset</a>
                </div>
            </form>

            <p class="result-count"><?= $total ?> user<?= $total === 1 ? '' : 's' ?></p>

            <div class="table-wrap">
                <table class="data-table">
                    <caption class="visually-hidden">User accounts</caption>
                    <thead><tr><th scope="col">Name</th><th scope="col">Email</th><th scope="col">Role</th><th scope="col">Status</th>
                        <th scope="col">Last login</th><th scope="col">Joined</th><th scope="col">Action</th></tr></thead>
                    <tbody>
                    <?php if (!$users): ?><tr><td colspan="7" class="empty-row">No users found.</td></tr><?php endif; ?>
                    <?php foreach ($users as $u): ?>
                        <tr>
                            <td><?= e($u['name']) ?></td>
                            <td><?= e($u['email']) ?></td>
                            <td><span class="badge badge-<?= e($u['role']) ?>"><?= e(ucfirst($u['role'])) ?></span></td>
                            <td><span class="badge badge-<?= e($u['status']) ?>"><?= e(ucfirst($u['status'])) ?></span></td>
                            <td><?= $u['last_login'] ? e(date('d M Y, h:i A', strtotime($u['last_login']))) : '—' ?></td>
                            <td><?= e(date('d M Y', strtotime($u['created_at']))) ?></td>
                            <td class="actions">
                                <?php if ((int) $u['id'] === current_user_id()): ?>
                                    <span class="muted">You</span>
                                <?php else: ?>
                                    <form method="post" class="inline-form"
                                          data-confirm="<?= $u['status'] === 'active' ? 'Deactivate' : 'Activate' ?> <?= e($u['name']) ?>?">
                                        <?= csrf_field() ?>
                                        <input type="hidden" name="id" value="<?= (int) $u['id'] ?>">
                                        <button type="submit" class="btn btn-sm <?= $u['status'] === 'active' ? 'btn-danger' : 'btn-outline' ?>">
                                            <?= $u['status'] === 'active' ? 'Deactivate' : 'Activate' ?>
                                        </button>
                                    </form>
                                <?php endif; ?>
                            </td>
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
