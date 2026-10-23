<?php
/**
 * Practical 12 – Event management (admin): list, filter by category / open-closed, delete.
 * Add + edit with poster upload are in event_form.php.
 */
require_once __DIR__ . '/../includes/bootstrap.php';
require_once __DIR__ . '/../includes/events_repo.php';
require_role('admin');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_require_post();
    $id = (int) ($_POST['id'] ?? 0);
    $ev = $id ? event_find($id) : null;

    if (!$ev) {
        flash_set('error', 'Event not found.');
    } elseif (post('action') === 'delete') {
        event_delete($id);
        audit_log('event_delete', "Deleted event #$id {$ev['title']}");
        flash_set('success', "Event \"{$ev['title']}\" was deleted.");
    } elseif (post('action') === 'toggle') {
        $new = $ev['status'] === 'open' ? 'closed' : 'open';
        db_exec('UPDATE events SET status = ? WHERE id = ?', 'si', [$new, $id]);
        audit_log('event_status', "Event #$id {$ev['title']} set to $new");
        flash_set('success', "Event \"{$ev['title']}\" is now $new.");
    }
    $qs = $_SERVER['QUERY_STRING'] ?? '';
    header('Location: ' . url('admin/events.php') . ($qs !== '' ? '?' . $qs : ''));
    exit;
}

$filters = event_filters($_GET);
$total   = event_count($filters);
[$page, $perPage, $offset, $totalPages] = paginate($total, 8);
$events = event_search($filters, $perPage, $offset);

$pageTitle = 'Manage Events';
$activeNav = 'events';
require ROOT_PATH . '/includes/header.php';
?>
<main id="main-content" class="admin-main">
    <section class="breadcrumb">
        <div class="container">
            <p><a href="<?= e(url('admin/dashboard.php')) ?>">Admin</a> &gt; <span>Events</span></p>
        </div>
    </section>

    <section class="admin-section">
        <div class="container">
            <div class="admin-head">
                <h1>Event Management</h1>
                <div class="admin-actions">
                    <a class="btn primary-btn" href="<?= e(url('admin/event_form.php')) ?>">+ Add Event</a>
                </div>
            </div>

            <form class="filter-bar" method="get" action="">
                <div>
                    <label for="q">Search</label>
                    <input type="search" id="q" name="q" value="<?= e($filters['q']) ?>" placeholder="Title or location">
                </div>
                <div>
                    <label for="category">Category</label>
                    <select id="category" name="category">
                        <option value="">All categories</option>
                        <?php foreach (EVENT_CATEGORIES as $c): ?>
                            <option value="<?= e($c) ?>" <?= $filters['category'] === $c ? 'selected' : '' ?>><?= e($c) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div>
                    <label for="status">Open / closed</label>
                    <select id="status" name="status">
                        <option value="">All</option>
                        <option value="open"   <?= $filters['status'] === 'open' ? 'selected' : '' ?>>Open</option>
                        <option value="closed" <?= $filters['status'] === 'closed' ? 'selected' : '' ?>>Closed</option>
                    </select>
                </div>
                <div class="filter-buttons">
                    <button type="submit" class="btn primary-btn">Apply</button>
                    <a class="btn btn-outline" href="<?= e(url('admin/events.php')) ?>">Reset</a>
                </div>
            </form>

            <p class="result-count"><?= $total ?> event<?= $total === 1 ? '' : 's' ?> found</p>

            <div class="table-wrap">
                <table class="data-table">
                    <caption class="visually-hidden">List of events</caption>
                    <thead>
                        <tr><th scope="col">Poster</th><th scope="col">Title</th><th scope="col">Date</th><th scope="col">Location</th>
                            <th scope="col">Category</th><th scope="col">Status</th><th scope="col">Registered</th><th scope="col">Actions</th></tr>
                    </thead>
                    <tbody>
                    <?php if (!$events): ?><tr><td colspan="8" class="empty-row">No events found.</td></tr><?php endif; ?>
                    <?php foreach ($events as $ev): ?>
                        <tr>
                            <td>
                                <?php if ($ev['poster']): ?>
                                    <img class="thumb" src="<?= e(url('uploads/posters/' . $ev['poster'])) ?>" alt="Poster of <?= e($ev['title']) ?>">
                                <?php else: ?>
                                    <span class="muted">—</span>
                                <?php endif; ?>
                            </td>
                            <td><?= e($ev['title']) ?></td>
                            <td><?= e(date('d M Y', strtotime($ev['event_date']))) ?></td>
                            <td><?= e($ev['location']) ?></td>
                            <td><?= e($ev['category']) ?></td>
                            <td><span class="badge badge-<?= e($ev['status']) ?>"><?= e(ucfirst($ev['status'])) ?></span></td>
                            <td><?= (int) $ev['registrations'] ?></td>
                            <td class="actions">
                                <a class="btn btn-sm btn-outline" href="<?= e(url('admin/event_form.php?id=' . (int) $ev['id'])) ?>">Edit</a>
                                <form method="post" class="inline-form">
                                    <?= csrf_field() ?>
                                    <input type="hidden" name="id" value="<?= (int) $ev['id'] ?>">
                                    <button type="submit" name="action" value="toggle" class="btn btn-sm btn-outline"><?= $ev['status'] === 'open' ? 'Close' : 'Reopen' ?></button>
                                </form>
                                <form method="post" class="inline-form"
                                      data-confirm="Delete event <?= e($ev['title']) ?>? Registrations for it will also be removed.">
                                    <?= csrf_field() ?>
                                    <input type="hidden" name="id" value="<?= (int) $ev['id'] ?>">
                                    <button type="submit" name="action" value="delete" class="btn btn-sm btn-danger">Delete</button>
                                </form>
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
