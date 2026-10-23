<?php
/**
 * Practical 13 – AJAX / Fetch CRUD for students: list, search (debounced), add, edit, delete
 * WITHOUT a page reload. All the work is done by js/students-ajax.js talking to api/students.php.
 */
require_once __DIR__ . '/../includes/bootstrap.php';
require_once __DIR__ . '/../includes/students_repo.php';
require_role('admin');

$pageTitle = 'Students (AJAX)';
$activeNav = 'students';
require ROOT_PATH . '/includes/header.php';
?>
<main id="main-content" class="admin-main">
    <section class="breadcrumb">
        <div class="container">
            <p><a href="<?= e(url('admin/dashboard.php')) ?>">Admin</a> &gt;
               <a href="<?= e(url('admin/students.php')) ?>">Students</a> &gt; <span>AJAX version</span></p>
        </div>
    </section>

    <section class="admin-section">
        <div class="container" id="ajax-app" data-api="<?= e(url('api/students.php')) ?>">

            <div class="admin-head">
                <h1>Students <small class="muted">(no page reload)</small></h1>
                <div class="admin-actions">
                    <button type="button" class="btn primary-btn" id="btn-add">+ Add Student</button>
                    <a class="btn btn-outline" href="<?= e(url('admin/students.php')) ?>">Classic version</a>
                </div>
            </div>

            <div class="filter-bar">
                <div>
                    <label for="ajax-q">Search (type to search)</label>
                    <input type="search" id="ajax-q" placeholder="Name, email, course or ID">
                </div>
                <div>
                    <label for="ajax-course">Course</label>
                    <select id="ajax-course">
                        <option value="">All courses</option>
                        <?php foreach (COURSES as $c): ?><option value="<?= e($c) ?>"><?= e($c) ?></option><?php endforeach; ?>
                    </select>
                </div>
                <div>
                    <label for="ajax-year">Year</label>
                    <select id="ajax-year">
                        <option value="">All years</option>
                        <?php for ($y = 1; $y <= 4; $y++): ?><option value="<?= $y ?>"><?= e(year_label($y)) ?></option><?php endfor; ?>
                    </select>
                </div>
                <div>
                    <label for="ajax-status">Status</label>
                    <select id="ajax-status">
                        <option value="">Active + inactive</option>
                        <option value="active">Active</option>
                        <option value="inactive">Inactive</option>
                        <option value="deleted">Deleted</option>
                    </select>
                </div>
            </div>

            <div id="ajax-message" class="alert" role="status" aria-live="polite" hidden></div>

            <p class="result-count" id="ajax-count"></p>

            <div class="table-wrap">
                <table class="data-table">
                    <caption class="visually-hidden">Students loaded with Fetch API</caption>
                    <thead>
                        <tr><th scope="col">ID</th><th scope="col">Name</th><th scope="col">Email</th>
                            <th scope="col">Course</th><th scope="col">Year</th><th scope="col">Status</th><th scope="col">Actions</th></tr>
                    </thead>
                    <tbody id="ajax-body"><tr><td colspan="7" class="empty-row">Loading…</td></tr></tbody>
                </table>
            </div>

            <nav class="pagination js-pagination" id="ajax-pagination" aria-label="Pagination"></nav>
        </div>
    </section>

    <!-- Add / edit dialog -->
    <dialog id="student-dialog" class="dialog" aria-labelledby="dialog-title">
        <form id="student-form" method="dialog" novalidate>
            <h2 id="dialog-title">Add Student</h2>
            <input type="hidden" id="f-id">
            <div class="form-group"><label for="f-name">Full Name</label><input type="text" id="f-name" required></div>
            <div class="form-group"><label for="f-email">Email</label><input type="email" id="f-email" required></div>
            <div class="form-group"><label for="f-phone">Mobile Number</label><input type="tel" id="f-phone" maxlength="10" required></div>
            <div class="form-row">
                <div class="form-group"><label for="f-course">Course</label>
                    <select id="f-course" required>
                        <option value="">Select course</option>
                        <?php foreach (COURSES as $c): ?><option value="<?= e($c) ?>"><?= e($c) ?></option><?php endforeach; ?>
                    </select></div>
                <div class="form-group"><label for="f-year">Year</label>
                    <select id="f-year" required>
                        <option value="">Select year</option>
                        <?php for ($y = 1; $y <= 4; $y++): ?><option value="<?= $y ?>"><?= e(year_label($y)) ?></option><?php endfor; ?>
                    </select></div>
            </div>
            <div class="form-row">
                <div class="form-group"><label for="f-gender">Gender</label>
                    <select id="f-gender" required>
                        <option value="">Select gender</option>
                        <option value="male">Male</option><option value="female">Female</option><option value="other">Other</option>
                    </select></div>
                <div class="form-group"><label for="f-status">Status</label>
                    <select id="f-status"><option value="active">Active</option><option value="inactive">Inactive</option></select></div>
            </div>
            <div class="dialog-buttons">
                <button type="submit" class="btn primary-btn" id="btn-save">Save</button>
                <button type="button" class="btn btn-outline" id="btn-cancel">Cancel</button>
            </div>
        </form>
    </dialog>
</main>
<?php
$extraScripts = ['js/students-ajax.js'];
require ROOT_PATH . '/includes/footer.php';
