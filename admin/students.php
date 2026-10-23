<?php
/**
 * Practical 11 – Student management (list, search, filter, paginate, delete/restore).
 * Add / edit live in student_form.php. All SQL is in includes/students_repo.php (prepared statements).
 */
require_once __DIR__ . '/../includes/bootstrap.php';
require_once __DIR__ . '/../includes/students_repo.php';
require_role('admin');

// ---------- Delete / restore / purge (POST + CSRF, then redirect) ----------
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_require_post();
    $id     = (int) ($_POST['id'] ?? 0);
    $action = post('action');
    $student = $id ? student_find($id) : null;

    if (!$student) {
        flash_set('error', 'Student not found.');
    } elseif ($action === 'delete') {
        student_soft_delete($id);
        audit_log('student_delete', "Soft-deleted student #$id {$student['name']}");
        flash_set('success', "Student \"{$student['name']}\" was deleted. You can restore it from the Deleted filter.");
    } elseif ($action === 'restore') {
        student_restore($id);
        audit_log('student_restore', "Restored student #$id {$student['name']}");
        flash_set('success', "Student \"{$student['name']}\" was restored.");
    } elseif ($action === 'purge') {
        if (student_purge($id)) {
            audit_log('student_purge', "Permanently deleted student #$id {$student['name']}");
            flash_set('success', "Student \"{$student['name']}\" was permanently removed.");
        } else {
            flash_set('error', 'Only already-deleted students can be permanently removed.');
        }
    } else {
        flash_set('error', 'Unknown action.');
    }
    $qs = $_SERVER['QUERY_STRING'] ?? '';
    header('Location: ' . url('admin/students.php') . ($qs !== '' ? '?' . $qs : ''));
    exit;
}

// ---------- Search / filter / pagination ----------
$filters = student_filters($_GET);
$total   = student_count($filters);
[$page, $perPage, $offset, $totalPages] = paginate($total, 10);
$students = student_search($filters, $perPage, $offset);

$exportQuery = http_build_query(array_filter($filters, fn($v) => $v !== ''));

$pageTitle = 'Manage Students';
$activeNav = 'students';
require ROOT_PATH . '/includes/header.php';
?>
<main id="main-content" class="admin-main">
    <section class="breadcrumb">
        <div class="container">
            <p><a href="<?= e(url('admin/dashboard.php')) ?>">Admin</a> &gt; <span>Students</span></p>
        </div>
    </section>

    <section class="admin-section">
        <div class="container">

            <div class="admin-head">
                <h1>Student Management</h1>
                <div class="admin-actions">
                    <a class="btn primary-btn" href="<?= e(url('admin/student_form.php')) ?>">+ Add Student</a>
                    <a class="btn btn-outline" href="<?= e(url('admin/students_export.php') . ($exportQuery ? '?' . $exportQuery : '')) ?>">⬇ Export CSV</a>
                    <a class="btn btn-outline" href="<?= e(url('admin/students_ajax.php')) ?>">⚡ AJAX version</a>
                </div>
            </div>

            <form class="filter-bar" method="get" action="">
                <div>
                    <label for="q">Search</label>
                    <input type="search" id="q" name="q" value="<?= e($filters['q']) ?>" placeholder="Name, email, course or ID">
                </div>
                <div>
                    <label for="course">Course</label>
                    <select id="course" name="course">
                        <option value="">All courses</option>
                        <?php foreach (COURSES as $c): ?>
                            <option value="<?= e($c) ?>" <?= $filters['course'] === $c ? 'selected' : '' ?>><?= e($c) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div>
                    <label for="year">Year</label>
                    <select id="year" name="year">
                        <option value="">All years</option>
                        <?php for ($y = 1; $y <= 4; $y++): ?>
                            <option value="<?= $y ?>" <?= $filters['year'] === (string) $y ? 'selected' : '' ?>><?= e(year_label($y)) ?></option>
                        <?php endfor; ?>
                    </select>
                </div>
                <div>
                    <label for="status">Status</label>
                    <select id="status" name="status">
                        <option value="" <?= $filters['status'] === '' ? 'selected' : '' ?>>Active + inactive</option>
                        <option value="active"   <?= $filters['status'] === 'active' ? 'selected' : '' ?>>Active</option>
                        <option value="inactive" <?= $filters['status'] === 'inactive' ? 'selected' : '' ?>>Inactive</option>
                        <option value="deleted"  <?= $filters['status'] === 'deleted' ? 'selected' : '' ?>>Deleted</option>
                        <option value="all"      <?= $filters['status'] === 'all' ? 'selected' : '' ?>>Everything</option>
                    </select>
                </div>
                <div class="filter-buttons">
                    <button type="submit" class="btn primary-btn">Apply</button>
                    <a class="btn btn-outline" href="<?= e(url('admin/students.php')) ?>">Reset</a>
                </div>
            </form>

            <p class="result-count"><?= $total ?> student<?= $total === 1 ? '' : 's' ?> found</p>

            <div class="table-wrap">
                <table class="data-table">
                    <caption class="visually-hidden">List of students</caption>
                    <thead>
                        <tr>
                            <th scope="col">ID</th><th scope="col">Name</th><th scope="col">Email</th>
                            <th scope="col">Course</th><th scope="col">Year</th><th scope="col">Status</th>
                            <th scope="col">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php if (!$students): ?>
                        <tr><td colspan="7" class="empty-row">No students match your search.</td></tr>
                    <?php endif; ?>
                    <?php foreach ($students as $s): ?>
                        <tr class="<?= $s['status'] === 'deleted' ? 'row-deleted' : '' ?>">
                            <td><?= e($s['student_code']) ?></td>
                            <td><?= e($s['name']) ?></td>
                            <td><?= e($s['email']) ?></td>
                            <td><?= e($s['course']) ?></td>
                            <td><?= (int) $s['year'] ?></td>
                            <td><span class="badge badge-<?= e($s['status']) ?>"><?= e(ucfirst($s['status'])) ?></span></td>
                            <td class="actions">
                                <?php if ($s['status'] !== 'deleted'): ?>
                                    <a class="btn btn-sm btn-outline" href="<?= e(url('admin/student_form.php?id=' . (int) $s['id'])) ?>">Edit</a>
                                    <form method="post" class="inline-form"
                                          data-confirm="Delete student <?= e($s['name']) ?>? You can restore them later.">
                                        <?= csrf_field() ?>
                                        <input type="hidden" name="id" value="<?= (int) $s['id'] ?>">
                                        <button type="submit" name="action" value="delete" class="btn btn-sm btn-danger">Delete</button>
                                    </form>
                                <?php else: ?>
                                    <form method="post" class="inline-form">
                                        <?= csrf_field() ?>
                                        <input type="hidden" name="id" value="<?= (int) $s['id'] ?>">
                                        <button type="submit" name="action" value="restore" class="btn btn-sm btn-outline">Restore</button>
                                    </form>
                                    <form method="post" class="inline-form"
                                          data-confirm="PERMANENTLY remove <?= e($s['name']) ?>? This cannot be undone.">
                                        <?= csrf_field() ?>
                                        <input type="hidden" name="id" value="<?= (int) $s['id'] ?>">
                                        <button type="submit" name="action" value="purge" class="btn btn-sm btn-danger">Remove forever</button>
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
