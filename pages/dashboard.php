<?php
/** Student dashboard – shows data of the logged-in student from MySQL. */
require_once __DIR__ . '/../includes/bootstrap.php';
require_login();
if (is_admin()) {
    redirect('admin/dashboard.php');
}

$student = db_one('SELECT * FROM students WHERE user_id = ?', 'i', [current_user_id()]);
$myEvents = [];
if ($student) {
    $myEvents = db_all(
        'SELECT e.id, e.title, e.event_date, e.location, e.status, r.registered_at
           FROM registrations r JOIN events e ON e.id = r.event_id
          WHERE r.student_id = ?
          ORDER BY e.event_date ASC',
        'i',
        [$student['id']]
    );
}
$upcoming = db_all(
    'SELECT id, title, event_date, location FROM events
      WHERE event_date >= CURDATE() AND status = \'open\' ORDER BY event_date ASC LIMIT 3'
);

$pageTitle = 'Dashboard';
$activeNav = 'dashboard';
require ROOT_PATH . '/includes/header.php';
?>
<main id="main-content">

    <!-- ================= NOTIFICATION BANNER ================= -->
    <section class="notification-banner" id="notification-banner">
        <div class="container">
            <div class="notification-content">
                <p>📢 <strong>Notice:</strong> Mid-semester results have been updated!</p>
                <button type="button" id="close-notification" aria-label="Close notice">×</button>
            </div>
        </div>
    </section>

    <section class="breadcrumb">
        <div class="container">
            <p><a href="<?= e(url('index.php')) ?>">Home</a> &gt; <span>Dashboard</span></p>
        </div>
    </section>

    <section class="page-title">
        <div class="container">
            <h1>Dashboard</h1>
            <p id="greeting">Welcome to your dashboard.</p>
            <p><strong><?= e($_SESSION['user_name'] ?? '') ?></strong><?= $student ? ' &middot; ' . e($student['student_code']) : '' ?></p>
            <p>Today's Date: <span id="today"></span></p>
        </div>
    </section>

    <section class="quick-access">
        <div class="container">
            <div class="card-container">
                <a class="card" href="<?= e(url('pages/courses.php')) ?>">
                    <h3>📚 Courses</h3>
                    <p>Total Enrolled Courses : 6</p>
                </a>
                <a class="card" href="<?= e(url('pages/attendance.php')) ?>">
                    <h3>📅 Attendance</h3>
                    <p>Overall Attendance : 92%</p>
                </a>
                <a class="card" href="<?= e(url('pages/assignment.php')) ?>">
                    <h3>📝 Assignments</h3>
                    <p>Pending Assignments : 2</p>
                </a>
                <a class="card" href="<?= e(url('pages/result.php')) ?>">
                    <h3>📈 Results</h3>
                    <p>Latest GPA : 8.9</p>
                </a>
                <a class="card" href="<?= e(url('pages/events.php')) ?>">
                    <h3>🎉 My Events</h3>
                    <p>Registered events : <?= count($myEvents) ?></p>
                </a>
                <a class="card" href="<?= e(url('pages/profile.php')) ?>">
                    <h3>👤 Profile</h3>
                    <p>View your profile details.</p>
                </a>
                <div class="card">
                    <h3>🪪 Student ID</h3>
                    <p>View your student information.</p>
                    <button type="button" id="open-modal" class="btn primary-btn">View Student ID</button>
                </div>
            </div>
        </div>
    </section>

    <section class="section-block">
        <div class="container">
            <h2>My Event Registrations</h2>
            <?php if (!$myEvents): ?>
                <p class="muted">You have not registered for any event yet.
                    <a href="<?= e(url('pages/events.php')) ?>">Browse events</a>.</p>
            <?php else: ?>
                <div class="table-wrap">
                    <table class="data-table">
                        <thead><tr><th>Event</th><th>Date</th><th>Location</th><th>Status</th></tr></thead>
                        <tbody>
                        <?php foreach ($myEvents as $ev): ?>
                            <tr>
                                <td><?= e($ev['title']) ?></td>
                                <td><?= e(date('d M Y', strtotime($ev['event_date']))) ?></td>
                                <td><?= e($ev['location']) ?></td>
                                <td><span class="badge badge-<?= e($ev['status']) ?>"><?= e(ucfirst($ev['status'])) ?></span></td>
                            </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>

            <?php if ($upcoming): ?>
                <h2 class="spaced">Coming up</h2>
                <ul class="plain-list">
                    <?php foreach ($upcoming as $u): ?>
                        <li><strong><?= e($u['title']) ?></strong> – <?= e(date('d M Y', strtotime($u['event_date']))) ?>, <?= e($u['location']) ?></li>
                    <?php endforeach; ?>
                </ul>
            <?php endif; ?>
        </div>
    </section>

    <!-- ================= STUDENT ID MODAL ================= -->
    <div class="modal" id="student-modal" role="dialog" aria-modal="true" aria-labelledby="student-modal-title">
        <div class="modal-content">
            <button type="button" id="close-modal" class="close-modal" aria-label="Close">×</button>
            <h2 id="student-modal-title">Student ID</h2>
            <p><strong>Name:</strong> <?= e($_SESSION['user_name'] ?? '') ?></p>
            <p><strong>Course:</strong> <?= e($student['course'] ?? 'Not available') ?></p>
            <p><strong>Year:</strong> <?= $student ? e(year_label((int) $student['year'])) : 'Not available' ?></p>
            <p><strong>Student ID:</strong> <?= e($student['student_code'] ?? 'Not available') ?></p>
            <button type="button" id="modal-close-button" class="btn primary-btn">Close</button>
        </div>
    </div>

</main>
<?php require ROOT_PATH . '/includes/footer.php';
