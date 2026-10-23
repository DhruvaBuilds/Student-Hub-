<?php
/**
 * Practical 12 – event data access + poster upload validation.
 */

const EVENT_STATUSES = ['open', 'closed'];

function event_validate(array $in): array
{
    $clean = [
        'title'       => preg_replace('/\s+/', ' ', trim((string) ($in['title'] ?? ''))),
        'description' => trim((string) ($in['description'] ?? '')),
        'event_date'  => trim((string) ($in['event_date'] ?? '')),
        'location'    => trim((string) ($in['location'] ?? '')),
        'category'    => trim((string) ($in['category'] ?? '')),
        'status'      => trim((string) ($in['status'] ?? 'open')),
    ];
    $errors = [];

    $len = mb_strlen($clean['title']);
    if ($len === 0)        $errors['title'] = 'Title is required.';
    elseif ($len < 3 || $len > 150) $errors['title'] = 'Title must be 3 to 150 characters.';

    if (mb_strlen($clean['description']) > 2000) $errors['description'] = 'Description can have at most 2000 characters.';

    $d = DateTime::createFromFormat('Y-m-d', $clean['event_date']);
    if ($clean['event_date'] === '')                                          $errors['event_date'] = 'Date is required.';
    elseif (!$d || $d->format('Y-m-d') !== $clean['event_date'])              $errors['event_date'] = 'Enter a valid date.';

    $len = mb_strlen($clean['location']);
    if ($len === 0)        $errors['location'] = 'Location is required.';
    elseif ($len > 150)    $errors['location'] = 'Location can have at most 150 characters.';

    if (!in_array($clean['category'], EVENT_CATEGORIES, true)) $errors['category'] = 'Select a valid category.';
    if (!in_array($clean['status'], EVENT_STATUSES, true))     $errors['status']   = 'Status must be open or closed.';

    return [$clean, $errors];
}

function event_filters(array $src): array
{
    $cat = (string) ($src['category'] ?? '');
    $st  = (string) ($src['status'] ?? '');
    return [
        'q'        => trim((string) ($src['q'] ?? '')),
        'category' => in_array($cat, EVENT_CATEGORIES, true) ? $cat : '',
        'status'   => in_array($st, EVENT_STATUSES, true) ? $st : '',
    ];
}

function event_where(array $f): array
{
    $where = []; $types = ''; $params = [];
    if ($f['q'] !== '') {
        $like = '%' . addcslashes($f['q'], '%_\\') . '%';
        $where[] = '(e.title LIKE ? OR e.location LIKE ?)';
        $types .= 'ss'; array_push($params, $like, $like);
    }
    if ($f['category'] !== '') { $where[] = 'e.category = ?'; $types .= 's'; $params[] = $f['category']; }
    if ($f['status'] !== '')   { $where[] = 'e.status = ?';   $types .= 's'; $params[] = $f['status']; }
    return [$where ? 'WHERE ' . implode(' AND ', $where) : '', $types, $params];
}

function event_count(array $f): int
{
    [$w, $t, $p] = event_where($f);
    return (int) db_value("SELECT COUNT(*) FROM events e $w", $t, $p);
}

function event_search(array $f, int $limit = 10, int $offset = 0): array
{
    [$w, $t, $p] = event_where($f);
    $t .= 'ii'; $p[] = $limit; $p[] = $offset;
    return db_all(
        "SELECT e.*, (SELECT COUNT(*) FROM registrations r WHERE r.event_id = e.id) AS registrations
           FROM events e $w ORDER BY e.event_date DESC, e.id DESC LIMIT ? OFFSET ?",
        $t,
        $p
    );
}

function event_find(int $id): ?array
{
    return db_one('SELECT * FROM events WHERE id = ?', 'i', [$id]);
}

function event_create(array $c, ?string $poster, int $userId): int
{
    db_exec(
        'INSERT INTO events (title, description, event_date, location, category, status, poster, created_by)
         VALUES (?, ?, ?, ?, ?, ?, ?, ?)',
        'sssssssi',
        [$c['title'], $c['description'], $c['event_date'], $c['location'], $c['category'], $c['status'], $poster, $userId]
    );
    return db_insert_id();
}

function event_update(int $id, array $c, ?string $poster): int
{
    return db_exec(
        'UPDATE events SET title = ?, description = ?, event_date = ?, location = ?, category = ?, status = ?, poster = ? WHERE id = ?',
        'sssssssi',
        [$c['title'], $c['description'], $c['event_date'], $c['location'], $c['category'], $c['status'], $poster, $id]
    );
}

function event_delete(int $id): int
{
    $ev = event_find($id);
    $n = db_exec('DELETE FROM events WHERE id = ?', 'i', [$id]);   // registrations cascade
    if ($n > 0 && $ev && $ev['poster']) {
        poster_remove_file($ev['poster']);
    }
    return $n;
}

// ---------------------------------------------------------------------------
// Poster upload (type + size + content validation, random file name)
// ---------------------------------------------------------------------------

function poster_remove_file(?string $name): void
{
    if (!$name) {
        return;
    }
    $path = UPLOAD_DIR . basename($name);     // basename() blocks path traversal
    if (is_file($path)) {
        @unlink($path);
    }
}

/**
 * Validate and store an uploaded poster.
 * Returns the stored file name, or null when nothing was uploaded / validation failed
 * (in that case $errors['poster'] is set).
 */
function poster_handle_upload(?array $file, array &$errors): ?string
{
    if ($file === null || ($file['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) {
        return null;
    }

    switch ($file['error']) {
        case UPLOAD_ERR_OK:
            break;
        case UPLOAD_ERR_INI_SIZE:
        case UPLOAD_ERR_FORM_SIZE:
            $errors['poster'] = 'The poster is too large. Maximum size is ' . (MAX_POSTER_SIZE / 1024 / 1024) . ' MB.';
            return null;
        default:
            $errors['poster'] = 'The poster could not be uploaded (error code ' . (int) $file['error'] . ').';
            return null;
    }

    if ($file['size'] > MAX_POSTER_SIZE) {
        $errors['poster'] = 'The poster is too large. Maximum size is ' . (MAX_POSTER_SIZE / 1024 / 1024) . ' MB.';
        return null;
    }
    if (!is_uploaded_file($file['tmp_name'])) {
        $errors['poster'] = 'Invalid upload.';
        return null;
    }

    // 1) Extension must be an image extension
    $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    if (!in_array($ext, ['jpg', 'jpeg', 'png', 'webp'], true)) {
        $errors['poster'] = 'Only JPG, PNG or WEBP images are allowed.';
        return null;
    }

    // 2) Real MIME type (looked at the file content, NOT the browser-supplied type)
    $allowed = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp'];
    $mime = (new finfo(FILEINFO_MIME_TYPE))->file($file['tmp_name']);
    if (!isset($allowed[$mime])) {
        $errors['poster'] = 'The file is not a valid JPG, PNG or WEBP image.';
        return null;
    }

    // 3) It must really decode as an image
    if (@getimagesize($file['tmp_name']) === false) {
        $errors['poster'] = 'The file is not a valid image.';
        return null;
    }

    // 4) Store with a random name – the original name is never trusted
    if (!is_dir(UPLOAD_DIR)) {
        @mkdir(UPLOAD_DIR, 0755, true);
    }
    $name = 'poster_' . bin2hex(random_bytes(8)) . '.' . $allowed[$mime];
    if (!move_uploaded_file($file['tmp_name'], UPLOAD_DIR . $name)) {
        $errors['poster'] = 'Could not save the poster. Check folder permissions on uploads/posters/.';
        return null;
    }
    return $name;
}
