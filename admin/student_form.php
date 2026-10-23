<?php
/** Practical 11 – add / edit one student (same form, id in the URL means edit). */
require_once __DIR__ . '/../includes/bootstrap.php';
require_once __DIR__ . '/../includes/students_repo.php';
require_role('admin');

$id      = (int) ($_GET['id'] ?? 0);
$student = $id ? student_find($id) : null;
if ($id && (!$student || $student['status'] === 'deleted')) {
    flash_set('error', 'That student does not exist or was deleted.');
    redirect('admin/students.php');
}
$isEdit = $student !== null;

$errors = [];
$data = $student ?? ['name' => '', 'email' => '', 'phone' => '', 'course' => '', 'year' => '', 'gender' => '', 'status' => 'active'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_require_post();
    [$clean, $errors] = student_validate($_POST, $isEdit ? $id : null);
    $data = $clean;

    if (!$errors) {
        try {
            if ($isEdit) {
                student_update($id, $clean);
                audit_log('student_update', "Updated student #$id {$clean['name']}");
                flash_set('success', 'Student updated successfully.');
            } else {
                $newId = student_create($clean);
                audit_log('student_create', "Created student #$newId {$clean['name']}");
                flash_set('success', 'Student added successfully.');
            }
            redirect('admin/students.php');
        } catch (mysqli_sql_exception $e) {
            if ($e->getCode() === 1062) {
                $errors['email'] = 'Another student already uses this email.';
            } else {
                error_log('[StudentHub] student save failed: ' . $e->getMessage());
                $errors['form'] = 'Could not save the student. Please try again.';
            }
        }
    }
}

$pageTitle = $isEdit ? 'Edit Student' : 'Add Student';
$activeNav = 'students';
require ROOT_PATH . '/includes/header.php';
?>
<main id="main-content" class="admin-main">
    <section class="breadcrumb">
        <div class="container">
            <p><a href="<?= e(url('admin/dashboard.php')) ?>">Admin</a> &gt;
               <a href="<?= e(url('admin/students.php')) ?>">Students</a> &gt; <span><?= e($pageTitle) ?></span></p>
        </div>
    </section>

    <section class="form-section">
        <div class="container">
            <form class="form-card wide" method="post" action="" novalidate>
                <h1 class="form-title"><?= e($pageTitle) ?></h1>
                <?= csrf_field() ?>
                <?php if (!empty($errors['form'])): ?><div class="alert alert-error" role="alert"><?= e($errors['form']) ?></div><?php endif; ?>

                <div class="form-group">
                    <label for="name">Full Name</label>
                    <input type="text" id="name" name="name" value="<?= e($data['name']) ?>" required<?= field_attrs($errors, 'name') ?>>
                    <?= field_error($errors, 'name') ?>
                </div>
                <div class="form-group">
                    <label for="email">Email</label>
                    <input type="email" id="email" name="email" value="<?= e($data['email']) ?>" required<?= field_attrs($errors, 'email') ?>>
                    <?= field_error($errors, 'email') ?>
                </div>
                <div class="form-group">
                    <label for="phone">Mobile Number</label>
                    <input type="tel" id="phone" name="phone" value="<?= e($data['phone']) ?>" maxlength="10" required<?= field_attrs($errors, 'phone') ?>>
                    <?= field_error($errors, 'phone') ?>
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label for="course">Course</label>
                        <select id="course" name="course" required<?= field_attrs($errors, 'course') ?>>
                            <option value="">Select course</option>
                            <?php foreach (COURSES as $c): ?>
                                <option value="<?= e($c) ?>" <?= $data['course'] === $c ? 'selected' : '' ?>><?= e($c) ?></option>
                            <?php endforeach; ?>
                        </select>
                        <?= field_error($errors, 'course') ?>
                    </div>
                    <div class="form-group">
                        <label for="year">Year</label>
                        <select id="year" name="year" required<?= field_attrs($errors, 'year') ?>>
                            <option value="">Select year</option>
                            <?php for ($y = 1; $y <= 4; $y++): ?>
                                <option value="<?= $y ?>" <?= (string) $data['year'] === (string) $y ? 'selected' : '' ?>><?= e(year_label($y)) ?></option>
                            <?php endfor; ?>
                        </select>
                        <?= field_error($errors, 'year') ?>
                    </div>
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label for="gender">Gender</label>
                        <select id="gender" name="gender" required<?= field_attrs($errors, 'gender') ?>>
                            <option value="">Select gender</option>
                            <?php foreach (['male' => 'Male', 'female' => 'Female', 'other' => 'Other'] as $v => $l): ?>
                                <option value="<?= $v ?>" <?= $data['gender'] === $v ? 'selected' : '' ?>><?= $l ?></option>
                            <?php endforeach; ?>
                        </select>
                        <?= field_error($errors, 'gender') ?>
                    </div>
                    <div class="form-group">
                        <label for="status">Status</label>
                        <select id="status" name="status"<?= field_attrs($errors, 'status') ?>>
                            <option value="active"   <?= $data['status'] === 'active' ? 'selected' : '' ?>>Active</option>
                            <option value="inactive" <?= $data['status'] === 'inactive' ? 'selected' : '' ?>>Inactive</option>
                        </select>
                        <?= field_error($errors, 'status') ?>
                    </div>
                </div>

                <div class="form-buttons">
                    <button type="submit" class="btn primary-btn"><?= $isEdit ? 'Save Changes' : 'Add Student' ?></button>
                    <a class="btn btn-outline" href="<?= e(url('admin/students.php')) ?>">Cancel</a>
                </div>
            </form>
        </div>
    </section>
</main>
<?php require ROOT_PATH . '/includes/footer.php';
