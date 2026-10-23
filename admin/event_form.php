<?php
/** Practical 12 – add / edit an event, with poster upload + validation. */
require_once __DIR__ . '/../includes/bootstrap.php';
require_once __DIR__ . '/../includes/events_repo.php';
require_role('admin');

$id    = (int) ($_GET['id'] ?? 0);
$event = $id ? event_find($id) : null;
if ($id && !$event) {
    flash_set('error', 'Event not found.');
    redirect('admin/events.php');
}
$isEdit = $event !== null;

$errors = [];
$data = $event ?? ['title' => '', 'description' => '', 'event_date' => '', 'location' => '', 'category' => '', 'status' => 'open', 'poster' => null];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_require_post();
    [$clean, $errors] = event_validate($_POST);
    $data = $clean + ['poster' => $event['poster'] ?? null];

    // Poster: validated even if other fields have errors, but only kept when everything is valid
    $newPoster = poster_handle_upload($_FILES['poster'] ?? null, $errors);

    if ($errors) {
        poster_remove_file($newPoster);        // do not leave an orphan file behind
    } else {
        $posterName = $event['poster'] ?? null;
        if ($newPoster !== null) {
            poster_remove_file($posterName);   // replaced
            $posterName = $newPoster;
        } elseif ($isEdit && isset($_POST['remove_poster'])) {
            poster_remove_file($posterName);
            $posterName = null;
        }
        try {
            if ($isEdit) {
                event_update($id, $clean, $posterName);
                audit_log('event_update', "Updated event #$id {$clean['title']}");
                flash_set('success', 'Event updated successfully.');
            } else {
                $newId = event_create($clean, $posterName, current_user_id());
                audit_log('event_create', "Created event #$newId {$clean['title']}");
                flash_set('success', 'Event added successfully.');
            }
            redirect('admin/events.php');
        } catch (mysqli_sql_exception $e) {
            error_log('[StudentHub] event save failed: ' . $e->getMessage());
            if ($newPoster !== null) poster_remove_file($newPoster);
            $errors['form'] = 'Could not save the event. Please try again.';
        }
    }
}

$pageTitle = $isEdit ? 'Edit Event' : 'Add Event';
$activeNav = 'events';
require ROOT_PATH . '/includes/header.php';
?>
<main id="main-content" class="admin-main">
    <section class="breadcrumb">
        <div class="container">
            <p><a href="<?= e(url('admin/dashboard.php')) ?>">Admin</a> &gt;
               <a href="<?= e(url('admin/events.php')) ?>">Events</a> &gt; <span><?= e($pageTitle) ?></span></p>
        </div>
    </section>

    <section class="form-section">
        <div class="container">
            <form class="form-card wide" method="post" action="" enctype="multipart/form-data" novalidate>
                <h1 class="form-title"><?= e($pageTitle) ?></h1>
                <?= csrf_field() ?>
                <input type="hidden" name="MAX_FILE_SIZE" value="<?= (int) MAX_POSTER_SIZE ?>">
                <?php if (!empty($errors['form'])): ?><div class="alert alert-error" role="alert"><?= e($errors['form']) ?></div><?php endif; ?>

                <div class="form-group">
                    <label for="title">Title</label>
                    <input type="text" id="title" name="title" value="<?= e($data['title']) ?>" maxlength="150" required<?= field_attrs($errors, 'title') ?>>
                    <?= field_error($errors, 'title') ?>
                </div>
                <div class="form-group">
                    <label for="description">Description</label>
                    <textarea id="description" name="description" rows="4" maxlength="2000"<?= field_attrs($errors, 'description') ?>><?= e($data['description']) ?></textarea>
                    <?= field_error($errors, 'description') ?>
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label for="event_date">Date</label>
                        <input type="date" id="event_date" name="event_date" value="<?= e($data['event_date']) ?>" required<?= field_attrs($errors, 'event_date') ?>>
                        <?= field_error($errors, 'event_date') ?>
                    </div>
                    <div class="form-group">
                        <label for="location">Location</label>
                        <input type="text" id="location" name="location" value="<?= e($data['location']) ?>" maxlength="150" required<?= field_attrs($errors, 'location') ?>>
                        <?= field_error($errors, 'location') ?>
                    </div>
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label for="category">Category</label>
                        <select id="category" name="category" required<?= field_attrs($errors, 'category') ?>>
                            <option value="">Select category</option>
                            <?php foreach (EVENT_CATEGORIES as $c): ?>
                                <option value="<?= e($c) ?>" <?= $data['category'] === $c ? 'selected' : '' ?>><?= e($c) ?></option>
                            <?php endforeach; ?>
                        </select>
                        <?= field_error($errors, 'category') ?>
                    </div>
                    <div class="form-group">
                        <label for="status">Registration</label>
                        <select id="status" name="status"<?= field_attrs($errors, 'status') ?>>
                            <option value="open"   <?= $data['status'] === 'open' ? 'selected' : '' ?>>Open</option>
                            <option value="closed" <?= $data['status'] === 'closed' ? 'selected' : '' ?>>Closed</option>
                        </select>
                        <?= field_error($errors, 'status') ?>
                    </div>
                </div>

                <div class="form-group">
                    <label for="poster">Poster (JPG, PNG or WEBP, max <?= (int) (MAX_POSTER_SIZE / 1024 / 1024) ?> MB)</label>
                    <input type="file" id="poster" name="poster" accept=".jpg,.jpeg,.png,.webp,image/jpeg,image/png,image/webp"<?= field_attrs($errors, 'poster') ?>>
                    <?= field_error($errors, 'poster') ?>
                    <?php if ($isEdit && !empty($event['poster'])): ?>
                        <div class="poster-current">
                            <img class="thumb thumb-lg" src="<?= e(url('uploads/posters/' . $event['poster'])) ?>" alt="Current poster">
                            <label class="checkbox-label"><input type="checkbox" name="remove_poster" value="1"> Remove current poster</label>
                        </div>
                    <?php endif; ?>
                </div>

                <div class="form-buttons">
                    <button type="submit" class="btn primary-btn"><?= $isEdit ? 'Save Changes' : 'Add Event' ?></button>
                    <a class="btn btn-outline" href="<?= e(url('admin/events.php')) ?>">Cancel</a>
                </div>
            </form>
        </div>
    </section>
</main>
<?php require ROOT_PATH . '/includes/footer.php';
