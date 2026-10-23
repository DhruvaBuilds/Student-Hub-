<?php
/**
 * Shared page header + dynamic, role-based navigation menu.
 * Optional variables set by the page BEFORE including this file:
 *   $pageTitle  – text for <title>
 *   $activeNav  – key of the menu item to highlight
 *   $bodyClass  – extra CSS class on <body>
 */
$pageTitle = $pageTitle ?? 'Student Portal';
$activeNav = $activeNav ?? '';
$bodyClass = $bodyClass ?? '';

/** Menu depends on who is logged in (Practical 14: dynamic menu). */
function nav_items(): array
{
    if (is_admin()) {
        return [
            'home'      => ['Home', 'index.php'],
            'admin'     => ['Dashboard', 'admin/dashboard.php'],
            'students'  => ['Students', 'admin/students.php'],
            'events'    => ['Events', 'admin/events.php'],
            'users'     => ['Users', 'admin/users.php'],
            'audit'     => ['Audit Log', 'admin/audit_logs.php'],
            'messages'  => ['Messages', 'admin/contact_messages.php'],
        ];
    }
    if (is_logged_in()) {
        return [
            'home'       => ['Home', 'index.php'],
            'dashboard'  => ['Dashboard', 'pages/dashboard.php'],
            'courses'    => ['Courses', 'pages/courses.php'],
            'attendance' => ['Attendance', 'pages/attendance.php'],
            'assignment' => ['Assignments', 'pages/assignment.php'],
            'result'     => ['Results', 'pages/result.php'],
            'events'     => ['Events', 'pages/events.php'],
            'profile'    => ['Profile', 'pages/profile.php'],
            'contact'    => ['Contact', 'pages/contact.php'],
        ];
    }
    return [
        'home'    => ['Home', 'index.php'],
        'events'  => ['Events', 'pages/events.php'],
        'contact' => ['Contact', 'pages/contact.php'],
    ];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="<?= e(csrf_token()) ?>">
    <title><?= e($pageTitle) ?> | Student Portal</title>

    <link rel="stylesheet" href="<?= e(url('css/style.css')) ?>">

    <!-- Google Font -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
</head>
<body class="<?= e($bodyClass) ?>" data-base-url="<?= e(BASE_URL) ?>">

<a class="skip-link" href="#main-content">Skip to main content</a>

<header>
    <nav class="navbar" aria-label="Main navigation">

        <a class="logo" href="<?= e(url('index.php')) ?>">Student Portal</a>

        <button type="button" class="nav-toggle" id="nav-toggle"
                aria-expanded="false" aria-controls="nav-menu" aria-label="Toggle menu">☰</button>

        <div class="nav-menu" id="nav-menu">
            <ul class="nav-links">
                <?php foreach (nav_items() as $key => [$label, $path]): ?>
                    <li>
                        <a href="<?= e(url($path)) ?>"
                           class="<?= $activeNav === $key ? 'active' : '' ?>"
                           <?= $activeNav === $key ? 'aria-current="page"' : '' ?>><?= e($label) ?></a>
                    </li>
                <?php endforeach; ?>
            </ul>

            <div class="auth-buttons">
                <button type="button" id="theme-toggle" class="btn" aria-label="Switch light/dark theme">🌙</button>

                <?php if (is_logged_in()): ?>
                    <span class="nav-user" title="<?= e(current_role()) ?>">
                        👤 <?= e($_SESSION['user_name'] ?? '') ?>
                        <?php if (is_admin()): ?><span class="badge badge-admin">Admin</span><?php endif; ?>
                    </span>
                    <form method="post" action="<?= e(url('pages/logout.php')) ?>" class="inline-form">
                        <?= csrf_field() ?>
                        <button type="submit" class="btn register-btn">Logout</button>
                    </form>
                <?php else: ?>
                    <a href="<?= e(url('pages/login.php')) ?>" class="btn login-btn <?= $activeNav === 'login' ? 'active' : '' ?>">Login</a>
                    <a href="<?= e(url('pages/register.php')) ?>" class="btn register-btn <?= $activeNav === 'register' ? 'active' : '' ?>">Register</a>
                <?php endif; ?>
            </div>
        </div>

    </nav>
</header>

<?php $__flash = flash_render(); if ($__flash !== ''): ?>
    <div class="container flash-wrap"><?= $__flash ?></div>
<?php endif; ?>
