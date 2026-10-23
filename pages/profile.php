<?php
/** Student profile – read from the students table. */
require_once __DIR__ . '/../includes/bootstrap.php';
require_login();

$student = db_one('SELECT * FROM students WHERE user_id = ?', 'i', [current_user_id()]);
$user    = db_one('SELECT name, email, role, last_login, created_at FROM users WHERE id = ?', 'i', [current_user_id()]);

$pageTitle = 'Profile';
$activeNav = 'profile';
require ROOT_PATH . '/includes/header.php';
?>
<main id="main-content">

    <section class="breadcrumb">
        <div class="container">
            <p><a href="<?= e(url('index.php')) ?>">Home</a> &gt; <span>Profile</span></p>
        </div>
    </section>

    <section class="page-title">
        <div class="container">
            <h1>Student Profile</h1>
            <p>View your personal and academic information.</p>
        </div>
    </section>

    <section class="quick-access">
        <div class="container">
            <div class="profile-card">
                <div class="profile-image">
                    <img src="<?= e(url('images/profile-placeholder.png')) ?>" alt="Profile picture placeholder for <?= e($user['name'] ?? 'student') ?>">
                </div>
                <div class="profile-details">
                    <h2><?= e($user['name'] ?? '') ?></h2>
                    <?php if ($student): ?>
                        <p><strong>Student ID:</strong> <?= e($student['student_code']) ?></p>
                        <p><strong>Course:</strong> <?= e($student['course']) ?></p>
                        <p><strong>Year:</strong> <?= e(year_label((int) $student['year'])) ?></p>
                        <p><strong>Phone:</strong> <?= e($student['phone']) ?></p>
                        <p><strong>Gender:</strong> <?= e(ucfirst($student['gender'])) ?></p>
                    <?php else: ?>
                        <p class="muted">No student record is linked to this account.</p>
                    <?php endif; ?>
                    <p><strong>Email:</strong> <?= e($user['email'] ?? '') ?></p>
                    <p><strong>Account type:</strong> <?= e(ucfirst($user['role'] ?? '')) ?></p>
                    <p><strong>Member since:</strong> <?= e(date('d M Y', strtotime($user['created_at'] ?? 'now'))) ?></p>
                    <p><strong>Last login:</strong> <?= !empty($user['last_login']) ? e(date('d M Y, h:i A', strtotime($user['last_login']))) : 'First login' ?></p>
                </div>
            </div>
        </div>
    </section>

</main>
<?php require ROOT_PATH . '/includes/footer.php';
