<?php
/**
 * Practical 10 – secure login.
 * password_verify(), session_regenerate_id(), role-based redirect, throttling, remember-me.
 */
require_once __DIR__ . '/../includes/bootstrap.php';

if (is_logged_in()) {
    redirect(home_for_role(current_role()));
}

$errors = [];
$email  = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email    = strtolower(post('email'));
    $password = $_POST['password'] ?? '';
    $remember = isset($_POST['remember']);

    if (!csrf_verify($_POST['csrf_token'] ?? null)) {
        $errors['form'] = 'Your form expired. Please try again.';
    } elseif (($wait = login_is_locked()) > 0) {
        $errors['form'] = 'Too many failed attempts. Try again in ' . ceil($wait / 60) . ' minute(s).';
    } elseif (!valid_email($email)) {
        $errors['email'] = 'Please enter a valid email address.';
    } elseif (!is_string($password) || $password === '') {
        $errors['password'] = 'Please enter your password.';
    } else {
        $user = db_one('SELECT id, name, email, password_hash, role, status FROM users WHERE email = ?', 's', [$email]);

        // Always run password_verify (even for unknown e-mails) so timing does not reveal valid accounts
        $passwordOk = password_verify($password, $user['password_hash'] ?? DUMMY_HASH) && $user !== null;

        if ($passwordOk && $user['status'] !== 'active') {
            audit_log('login_blocked', 'Inactive account tried to log in: ' . $email, (int) $user['id']);
            $errors['form'] = 'This account has been deactivated. Please contact the administrator.';
        } elseif ($passwordOk) {
            // Upgrade the hash silently if PHP's default cost/algorithm has changed
            if (password_needs_rehash($user['password_hash'], PASSWORD_DEFAULT)) {
                db_exec('UPDATE users SET password_hash = ? WHERE id = ?', 'si',
                        [password_hash($password, PASSWORD_DEFAULT), $user['id']]);
            }
            login_user($user, $remember);
            audit_log('login', 'Logged in as ' . $user['role'], (int) $user['id']);
            flash_set('success', 'Welcome back, ' . $user['name'] . '!');
            redirect(home_for_role($user['role']));
        } else {
            login_register_failure();
            audit_log('login_failed', 'Failed login for ' . mb_substr($email, 0, 100), $user ? (int) $user['id'] : null);
            $errors['form'] = 'Invalid email or password.';
        }
    }
}

$pageTitle = 'Login';
$activeNav = 'login';
require ROOT_PATH . '/includes/header.php';
?>
<main id="main-content">

    <section class="breadcrumb">
        <div class="container">
            <p><a href="<?= e(url('index.php')) ?>">Home</a> &gt; <span>Login</span></p>
        </div>
    </section>

    <section class="page-title">
        <div class="container">
            <h1>Student Login</h1>
            <p>Enter your credentials to access your account.</p>
        </div>
    </section>

    <section class="form-section">
        <div class="container">
            <form class="form-card login-form" method="post" action="" novalidate>
                <?= csrf_field() ?>

                <?php if (!empty($errors['form'])): ?>
                    <div class="alert alert-error" role="alert"><?= e($errors['form']) ?></div>
                <?php endif; ?>

                <div class="form-group">
                    <label for="email">Email Address</label>
                    <input type="email" id="email" name="email" placeholder="Enter your email"
                           value="<?= e($email) ?>" autocomplete="username" required<?= field_attrs($errors, 'email') ?>>
                    <?= field_error($errors, 'email') ?>
                </div>

                <div class="form-group">
                    <label for="password">Password</label>
                    <input type="password" id="password" name="password" placeholder="Enter your password"
                           autocomplete="current-password" required<?= field_attrs($errors, 'password') ?>>
                    <?= field_error($errors, 'password') ?>
                </div>

                <div class="form-group">
                    <label class="checkbox-label">
                        <input type="checkbox" name="remember" value="1"> Remember me for <?= (int) REMEMBER_DAYS ?> days
                    </label>
                </div>

                <button type="submit" class="btn primary-btn">Login</button>

                <p class="form-note">New here? <a href="<?= e(url('pages/register.php')) ?>">Create an account</a></p>
                <p class="form-note demo-note">Demo: <code>admin@studenthub.edu</code> / <code>Admin@123</code> &nbsp;|&nbsp;
                    <code>rahul.sharma@email.com</code> / <code>Student@123</code></p>
            </form>
        </div>
    </section>

</main>
<?php
$extraScripts = ['js/validation.js'];
require ROOT_PATH . '/includes/footer.php';
