<?php
/**
 * Practical 14 – admin dashboard.
 *  - every number comes from SQL aggregation (COUNT / GROUP BY), nothing is hard-coded
 *  - require_role('admin') blocks everyone else (students get a 403 page + audit entry)
 *  - menu in the header changes with the role (includes/header.php -> nav_items())
 */
require_once __DIR__ . '/../includes/bootstrap.php';
require_role('admin');

// ---- user statistics ----
$userStats = ['total' => 0, 'active' => 0, 'inactive' => 0, 'admins' => 0, 'students' => 0];
foreach (db_all('SELECT role, status, COUNT(*) AS n FROM users GROUP BY role, status') as $row) {
    $n = (int) $row['n'];
    $userStats['total'] += $n;
    $userStats[$row['status']] += $n;
    $userStats[$row['role'] === 'admin' ? 'admins' : 'students'] += $n;
}

$studentCount = (int) db_value("SELECT COUNT(*) FROM students WHERE status <> 'deleted'");
$eventCount   = (int) db_value('SELECT COUNT(*) FROM events');
$openEvents   = (int) db_value("SELECT COUNT(*) FROM events WHERE status = 'open'");
$regCount     = (int) db_value('SELECT COUNT(*) FROM registrations');

$latestRegistrations = db_all(
    'SELECT r.registered_at, s.name AS student_name, s.student_code, e.title AS event_title
       FROM registrations r
       JOIN students s ON s.id = r.student_id
       JOIN events e   ON e.id = r.event_id
      ORDER BY r.registered_at DESC, r.id DESC
      LIMIT 8'
);
$latestUsers = db_all('SELECT name, email, role, created_at FROM users ORDER BY created_at DESC, id DESC LIMIT 5');
$recentAudit = db_all(
    'SELECT a.action, a.details, a.created_at, u.name
       FROM audit_logs a LEFT JOIN users u ON u.id = a.user_id
      ORDER BY a.id DESC LIMIT 6'
);

// ---- registrations per month (latest 6 months that have data) ----
$monthly = db_all(
    "SELECT DATE_FORMAT(registered_at, '%Y-%m') AS ym, COUNT(*) AS n
       FROM registrations GROUP BY ym ORDER BY ym DESC LIMIT 6"
);
$monthly = array_reverse($monthly);
$chartData = array_map(fn($m) => [
    'label' => date('M Y', strtotime($m['ym'] . '-01')),
    'value' => (int) $m['n'],
], $monthly);

$pageTitle = 'Admin Dashboard';
$activeNav = 'admin';
require ROOT_PATH . '/includes/header.php';
?>
<main id="main-content" class="admin-main">

    <section class="breadcrumb">
        <div class="container"><p><a href="<?= e(url('index.php')) ?>">Home</a> &gt; <span>Admin Dashboard</span></p></div>
    </section>

    <section class="admin-section">
        <div class="container">
            <div class="admin-head">
                <h1>Admin Dashboard</h1>
                <p class="muted">Signed in as <?= e($_SESSION['user_name']) ?> &middot; <?= e(date('d M Y')) ?></p>
            </div>

            <div class="stat-grid">
                <a class="stat-card" href="<?= e(url('admin/users.php')) ?>">
                    <span class="stat-label">Users</span>
                    <span class="stat-value"><?= $userStats['total'] ?></span>
                    <span class="stat-sub"><?= $userStats['active'] ?> active &middot; <?= $userStats['inactive'] ?> inactive</span>
                </a>
                <a class="stat-card" href="<?= e(url('admin/students.php')) ?>">
                    <span class="stat-label">Students</span>
                    <span class="stat-value"><?= $studentCount ?></span>
                    <span class="stat-sub"><?= $userStats['students'] ?> with login accounts</span>
                </a>
                <a class="stat-card" href="<?= e(url('admin/events.php')) ?>">
                    <span class="stat-label">Events</span>
                    <span class="stat-value"><?= $eventCount ?></span>
                    <span class="stat-sub"><?= $openEvents ?> open for registration</span>
                </a>
                <div class="stat-card">
                    <span class="stat-label">Event registrations</span>
                    <span class="stat-value"><?= $regCount ?></span>
                    <span class="stat-sub">across all events</span>
                </div>
            </div>

            <div class="dash-grid">
                <section class="panel" aria-labelledby="chart-title">
                    <h2 id="chart-title">Event registrations by month</h2>
                    <?php if (!$chartData): ?>
                        <p class="muted">No registrations yet.</p>
                    <?php else: ?>
                        <canvas id="reg-chart" height="260" role="img"
                                aria-label="Bar chart of event registrations per month"
                                data-chart="<?= e(json_encode($chartData)) ?>"></canvas>
                        <table class="data-table compact">
                            <caption class="visually-hidden">Registrations per month (table version of the chart)</caption>
                            <thead><tr><th scope="col">Month</th><th scope="col">Registrations</th></tr></thead>
                            <tbody>
                            <?php foreach ($chartData as $c): ?>
                                <tr><td><?= e($c['label']) ?></td><td><?= (int) $c['value'] ?></td></tr>
                            <?php endforeach; ?>
                            </tbody>
                        </table>
                    <?php endif; ?>
                </section>

                <section class="panel" aria-labelledby="latest-title">
                    <h2 id="latest-title">Latest registrations</h2>
                    <?php if (!$latestRegistrations): ?>
                        <p class="muted">No registrations yet.</p>
                    <?php else: ?>
                        <div class="table-wrap">
                        <table class="data-table compact">
                            <thead><tr><th scope="col">Student</th><th scope="col">Event</th><th scope="col">When</th></tr></thead>
                            <tbody>
                            <?php foreach ($latestRegistrations as $r): ?>
                                <tr>
                                    <td><?= e($r['student_name']) ?><br><small class="muted"><?= e($r['student_code']) ?></small></td>
                                    <td><?= e($r['event_title']) ?></td>
                                    <td><?= e(date('d M, h:i A', strtotime($r['registered_at']))) ?></td>
                                </tr>
                            <?php endforeach; ?>
                            </tbody>
                        </table>
                        </div>
                    <?php endif; ?>
                </section>

                <section class="panel" aria-labelledby="users-title">
                    <h2 id="users-title">Newest user accounts</h2>
                    <ul class="plain-list">
                        <?php foreach ($latestUsers as $u): ?>
                            <li><strong><?= e($u['name']) ?></strong>
                                <span class="badge badge-<?= e($u['role']) ?>"><?= e($u['role']) ?></span><br>
                                <small class="muted"><?= e($u['email']) ?> &middot; <?= e(date('d M Y', strtotime($u['created_at']))) ?></small></li>
                        <?php endforeach; ?>
                    </ul>
                </section>

                <section class="panel" aria-labelledby="audit-title">
                    <h2 id="audit-title">Recent admin activity</h2>
                    <?php if (!$recentAudit): ?>
                        <p class="muted">Nothing logged yet.</p>
                    <?php else: ?>
                        <ul class="plain-list">
                            <?php foreach ($recentAudit as $a): ?>
                                <li><code><?= e($a['action']) ?></code> – <?= e($a['details']) ?><br>
                                    <small class="muted"><?= e($a['name'] ?? 'guest') ?> &middot; <?= e(date('d M, h:i A', strtotime($a['created_at']))) ?></small></li>
                            <?php endforeach; ?>
                        </ul>
                        <p><a href="<?= e(url('admin/audit_logs.php')) ?>">View full audit log &rarr;</a></p>
                    <?php endif; ?>
                </section>
            </div>
        </div>
    </section>
</main>
<?php
$extraScripts = ['js/admin-dashboard.js'];
require ROOT_PATH . '/includes/footer.php';
