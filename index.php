<?php
require_once __DIR__ . '/includes/bootstrap.php';

$pageTitle = 'Home';
$activeNav = 'home';
$loggedIn  = is_logged_in();
$target    = fn(string $path) => $loggedIn ? url($path) : url('pages/login.php');
require ROOT_PATH . '/includes/header.php';
?>
<main id="main-content">

    <!-- HERO SECTION -->
    <section class="hero">
        <div class="container">
            <div class="hero-content">
                <h1>Welcome to Student Portal</h1>
                <p>Your one-stop platform for managing courses, attendance, assignments, events and academic progress.</p>

                <div class="hero-buttons">
                    <?php if ($loggedIn): ?>
                        <a href="<?= e(url(home_for_role(current_role()))) ?>" class="btn primary-btn">Go to Dashboard</a>
                        <a href="<?= e(url('pages/events.php')) ?>" class="btn secondary-btn">View Events</a>
                    <?php else: ?>
                        <a href="<?= e(url('pages/login.php')) ?>" class="btn primary-btn">Login</a>
                        <a href="<?= e(url('pages/register.php')) ?>" class="btn secondary-btn">Register</a>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </section>

    <!-- QUICK ACCESS -->
    <section class="quick-access">
        <div class="container">
            <h2>Student Portal</h2>

            <div class="card-container">
                <a href="<?= e($target('pages/dashboard.php')) ?>" class="card">
                    <h3>📊 Dashboard</h3>
                    <p><?= $loggedIn ? 'Open your dashboard.' : 'Login to access your dashboard.' ?></p>
                </a>
                <a href="<?= e($target('pages/courses.php')) ?>" class="card">
                    <h3>📚 Courses</h3>
                    <p><?= $loggedIn ? 'View your enrolled courses.' : 'Login to view your enrolled courses.' ?></p>
                </a>
                <a href="<?= e($target('pages/attendance.php')) ?>" class="card">
                    <h3>📅 Attendance</h3>
                    <p><?= $loggedIn ? 'Check your attendance.' : 'Login to check your attendance.' ?></p>
                </a>
                <a href="<?= e($target('pages/assignment.php')) ?>" class="card">
                    <h3>📝 Assignments</h3>
                    <p><?= $loggedIn ? 'View your assignments.' : 'Login to view your assignments.' ?></p>
                </a>
                <a href="<?= e($target('pages/result.php')) ?>" class="card">
                    <h3>📈 Results</h3>
                    <p><?= $loggedIn ? 'Check your examination results.' : 'Login to check your examination results.' ?></p>
                </a>
                <a href="<?= e(url('pages/events.php')) ?>" class="card">
                    <h3>🎉 Events</h3>
                    <p>Browse upcoming college events and register.</p>
                </a>
                <a href="<?= e($target('pages/profile.php')) ?>" class="card">
                    <h3>👤 Profile</h3>
                    <p><?= $loggedIn ? 'View your student profile.' : 'Login to view your student profile.' ?></p>
                </a>
            </div>
        </div>
    </section>

</main>
<?php require ROOT_PATH . '/includes/footer.php';
