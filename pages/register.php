<?php
/**
 * Practical 9 – secure registration.
 *  - server-side validation that mirrors js/validation.js
 *  - duplicate e-mail check
 *  - password_hash() (bcrypt) – the plain password is never stored
 *  - MySQLi prepared statements + a transaction (users + students rows)
 *  - CSRF token and audit-log entry
 */
require_once __DIR__ . '/../includes/bootstrap.php';

if (is_logged_in()) {
    redirect(home_for_role(current_role()));
}

$errors = [];
$old = ['fullname' => '', 'email' => '', 'phone' => '', 'course' => '', 'year' => '', 'gender' => '', 'terms' => ''];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $old['fullname'] = preg_replace('/\s+/', ' ', post('fullname'));
    $old['email']    = strtolower(post('email'));
    $old['phone']    = post('phone');
    $old['course']   = post('course');
    $old['year']     = post('year');
    $old['gender']   = post('gender');
    $password        = $_POST['password'] ?? '';
    $confirm         = $_POST['confirm-password'] ?? '';
    $terms           = isset($_POST['terms']);

    if (!csrf_verify($_POST['csrf_token'] ?? null)) {
        $errors['form'] = 'Your form expired. Please try again.';
    }

    if ($old['fullname'] === '')                    $errors['fullname'] = 'Please enter your full name.';
    elseif (!valid_name($old['fullname']))          $errors['fullname'] = 'Name should contain only letters and spaces.';

    if ($old['email'] === '')                       $errors['email'] = 'Please enter your email address.';
    elseif (!valid_email($old['email']))            $errors['email'] = 'Please enter a valid email address.';

    if ($old['phone'] === '')                       $errors['phone'] = 'Please enter your mobile number.';
    elseif (!valid_phone($old['phone']))            $errors['phone'] = 'Please enter a valid 10-digit mobile number.';

    if (!in_array($old['course'], COURSES, true))   $errors['course'] = 'Please select your course.';
    if (!valid_year($old['year']))                  $errors['year']   = 'Please select your year.';
    if (!in_array($old['gender'], ['male', 'female', 'other'], true)) $errors['gender'] = 'Please select your gender.';

    if (!is_string($password) || $password === '')  $errors['password'] = 'Please enter a password.';
    elseif (!valid_password($password))             $errors['password'] = 'Password must be 8-72 characters and contain a letter and a number.';

    if (!is_string($confirm) || $confirm === '')    $errors['confirm-password'] = 'Please confirm your password.';
    elseif ($password !== $confirm)                 $errors['confirm-password'] = 'Passwords do not match.';

    if (!$terms)                                    $errors['terms'] = 'Please accept the Terms and Conditions.';

    // Duplicate e-mail check (only worth a query when the rest is valid)
    if (!$errors) {
        if (db_one('SELECT id FROM users WHERE email = ?', 's', [$old['email']])) {
            $errors['email'] = 'This email is already registered. Try logging in instead.';
        }
    }

    if (!$errors) {
        $conn = db();
        try {
            $conn->begin_transaction();

            db_exec(
                'INSERT INTO users (name, email, password_hash, role, status) VALUES (?, ?, ?, \'student\', \'active\')',
                'sss',
                [$old['fullname'], $old['email'], password_hash($password, PASSWORD_DEFAULT)]
            );
            $userId = db_insert_id();

            // If an admin already created a student record with this e-mail, link it instead of duplicating
            $existing = db_one('SELECT id FROM students WHERE email = ? AND user_id IS NULL', 's', [$old['email']]);
            if ($existing) {
                db_exec(
                    'UPDATE students SET user_id = ?, name = ?, phone = ?, course = ?, year = ?, gender = ?, status = \'active\' WHERE id = ?',
                    'isssisi',
                    [$userId, $old['fullname'], $old['phone'], $old['course'], (int) $old['year'], $old['gender'], $existing['id']]
                );
            } else {
                db_exec(
                    'INSERT INTO students (user_id, student_code, name, email, phone, course, year, gender, status)
                     VALUES (?, ?, ?, ?, ?, ?, ?, ?, \'active\')',
                    'isssssis',
                    [$userId, 'TMP' . bin2hex(random_bytes(6)), $old['fullname'], $old['email'], $old['phone'],
                     $old['course'], (int) $old['year'], $old['gender']]
                );
                $studentId = db_insert_id();
                db_exec('UPDATE students SET student_code = ? WHERE id = ?', 'si',
                        [sprintf('STU%d%03d', (int) date('Y'), $studentId), $studentId]);
            }

            $conn->commit();
            audit_log('register', 'New student account: ' . $old['email'], $userId);
            flash_set('success', 'Registration successful! You can now log in.');
            redirect('pages/login.php');
        } catch (mysqli_sql_exception $e) {
            $conn->rollback();
            if ($e->getCode() === 1062) {   // duplicate key – someone registered at the same moment
                $errors['email'] = 'This email is already registered. Try logging in instead.';
            } else {
                error_log('[StudentHub] register failed: ' . $e->getMessage());
                $errors['form'] = 'Something went wrong while creating your account. Please try again.';
            }
        }
    }
}

$pageTitle = 'Register';
$activeNav = 'register';
require ROOT_PATH . '/includes/header.php';
?>
<main id="main-content">

    <section class="breadcrumb">
        <div class="container">
            <p><a href="<?= e(url('index.php')) ?>">Home</a> &gt; <span>Register</span></p>
        </div>
    </section>

    <section class="page-title">
        <div class="container">
            <h1>Student Registration</h1>
            <p>Create a new student account.</p>
        </div>
    </section>

    <section class="form-section">
        <div class="container">
            <form class="form-card register-form" method="post" action="" novalidate>
                <?= csrf_field() ?>

                <?php if (!empty($errors['form'])): ?>
                    <div class="alert alert-error" role="alert"><?= e($errors['form']) ?></div>
                <?php endif; ?>

                <div class="form-group">
                    <label for="fullname">Full Name</label>
                    <input type="text" id="fullname" name="fullname" placeholder="Enter your full name"
                           value="<?= e($old['fullname']) ?>" autocomplete="name" required<?= field_attrs($errors, 'fullname') ?>>
                    <?= field_error($errors, 'fullname') ?>
                </div>

                <div class="form-group">
                    <label for="email">Email Address</label>
                    <input type="email" id="email" name="email" placeholder="Enter your email"
                           value="<?= e($old['email']) ?>" autocomplete="email" required<?= field_attrs($errors, 'email') ?>>
                    <?= field_error($errors, 'email') ?>
                </div>

                <div class="form-group">
                    <label for="phone">Mobile Number</label>
                    <input type="tel" id="phone" name="phone" placeholder="Enter your 10-digit mobile number"
                           value="<?= e($old['phone']) ?>" inputmode="numeric" maxlength="10" autocomplete="tel" required<?= field_attrs($errors, 'phone') ?>>
                    <?= field_error($errors, 'phone') ?>
                </div>

                <div class="form-group">
                    <label for="course">Course</label>
                    <select id="course" name="course" required<?= field_attrs($errors, 'course') ?>>
                        <option value="">Select your course</option>
                        <?php foreach (COURSES as $c): ?>
                            <option value="<?= e($c) ?>" <?= $old['course'] === $c ? 'selected' : '' ?>><?= e($c) ?></option>
                        <?php endforeach; ?>
                    </select>
                    <?= field_error($errors, 'course') ?>
                </div>

                <div class="form-group">
                    <label for="year">Year</label>
                    <select id="year" name="year" required<?= field_attrs($errors, 'year') ?>>
                        <option value="">Select your year</option>
                        <?php for ($y = 1; $y <= 4; $y++): ?>
                            <option value="<?= $y ?>" <?= $old['year'] === (string) $y ? 'selected' : '' ?>><?= e(year_label($y)) ?></option>
                        <?php endfor; ?>
                    </select>
                    <?= field_error($errors, 'year') ?>
                </div>

                <fieldset class="form-group">
                    <legend>Gender</legend>
                    <div class="radio-row">
                        <?php foreach (['male' => 'Male', 'female' => 'Female', 'other' => 'Other'] as $val => $label): ?>
                            <label><input type="radio" name="gender" value="<?= $val ?>" <?= $old['gender'] === $val ? 'checked' : '' ?>> <?= $label ?></label>
                        <?php endforeach; ?>
                    </div>
                    <?= field_error($errors, 'gender') ?>
                </fieldset>

                <div class="form-group">
                    <label for="password">Password</label>
                    <input type="password" id="password" name="password" placeholder="Create a password"
                           autocomplete="new-password" required<?= field_attrs($errors, 'password') ?>>
                    <div class="strength" id="password-strength" aria-live="polite" hidden>
                        <div class="strength-bar"><span id="strength-fill"></span></div>
                        <small id="strength-text"></small>
                    </div>
                    <?= field_error($errors, 'password') ?>
                </div>

                <div class="form-group">
                    <label for="confirm-password">Confirm Password</label>
                    <input type="password" id="confirm-password" name="confirm-password" placeholder="Confirm your password"
                           autocomplete="new-password" required<?= field_attrs($errors, 'confirm-password') ?>>
                    <?= field_error($errors, 'confirm-password') ?>
                </div>

                <div class="form-group">
                    <label class="checkbox-label">
                        <input type="checkbox" id="terms" name="terms" value="1" <?= isset($_POST['terms']) ? 'checked' : '' ?>>
                        I agree to the Terms and Conditions.
                    </label>
                    <?= field_error($errors, 'terms') ?>
                </div>

                <button type="submit" class="btn primary-btn">Register</button>
                <p class="form-note">Already registered? <a href="<?= e(url('pages/login.php')) ?>">Log in</a></p>
            </form>
        </div>
    </section>

</main>
<?php
$extraScripts = ['js/validation.js'];
require ROOT_PATH . '/includes/footer.php';
