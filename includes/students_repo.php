<?php
/**
 * Practical 11 – everything the student CRUD needs (MySQLi prepared statements).
 * Used by the classic admin pages AND by the JSON API (Practical 13), so the
 * validation and SQL exist in exactly one place.
 */

const STUDENT_STATUSES = ['active', 'inactive', 'deleted'];

/** Validate + clean input. Returns [cleanData, errors]. */
function student_validate(array $in, ?int $ignoreId = null): array
{
    $clean = [
        'name'   => preg_replace('/\s+/', ' ', trim((string) ($in['name'] ?? ''))),
        'email'  => strtolower(trim((string) ($in['email'] ?? ''))),
        'phone'  => trim((string) ($in['phone'] ?? '')),
        'course' => trim((string) ($in['course'] ?? '')),
        'year'   => trim((string) ($in['year'] ?? '')),
        'gender' => trim((string) ($in['gender'] ?? '')),
        'status' => trim((string) ($in['status'] ?? 'active')),
    ];
    $errors = [];

    if ($clean['name'] === '')                 $errors['name'] = 'Name is required.';
    elseif (!valid_name($clean['name']))       $errors['name'] = 'Name should contain only letters and spaces.';

    if ($clean['email'] === '')                $errors['email'] = 'Email is required.';
    elseif (!valid_email($clean['email']))     $errors['email'] = 'Enter a valid email address.';

    if ($clean['phone'] === '')                $errors['phone'] = 'Mobile number is required.';
    elseif (!valid_phone($clean['phone']))     $errors['phone'] = 'Enter a valid 10-digit mobile number.';

    if (!in_array($clean['course'], COURSES, true))                         $errors['course'] = 'Select a valid course.';
    if (!valid_year($clean['year']))                                         $errors['year']   = 'Select a valid year (1-4).';
    if (!in_array($clean['gender'], ['male', 'female', 'other'], true))      $errors['gender'] = 'Select a gender.';
    if (!in_array($clean['status'], ['active', 'inactive'], true))           $errors['status'] = 'Status must be active or inactive.';

    if (!isset($errors['email'])) {
        $dup = db_one('SELECT id FROM students WHERE email = ? AND id <> ?', 'si', [$clean['email'], $ignoreId ?? 0]);
        if ($dup) {
            $errors['email'] = 'Another student already uses this email.';
        }
    }
    return [$clean, $errors];
}

/** Read search/filter values from $_GET (or any array) and whitelist them. */
function student_filters(array $src): array
{
    $status = (string) ($src['status'] ?? '');
    $course = (string) ($src['course'] ?? '');
    $year   = (string) ($src['year'] ?? '');
    return [
        'q'      => trim((string) ($src['q'] ?? '')),
        'course' => in_array($course, COURSES, true) ? $course : '',
        'year'   => valid_year($year) ? $year : '',
        'status' => in_array($status, ['active', 'inactive', 'deleted', 'all'], true) ? $status : '',
    ];
}

/** Build the WHERE clause (always parameterised). Returns [sql, types, params]. */
function student_where(array $f): array
{
    $where = [];
    $types = '';
    $params = [];

    if ($f['q'] !== '') {
        $like = '%' . addcslashes($f['q'], '%_\\') . '%';
        $where[] = '(name LIKE ? OR email LIKE ? OR course LIKE ? OR student_code LIKE ?)';
        $types .= 'ssss';
        array_push($params, $like, $like, $like, $like);
    }
    if ($f['course'] !== '') { $where[] = 'course = ?'; $types .= 's'; $params[] = $f['course']; }
    if ($f['year'] !== '')   { $where[] = 'year = ?';   $types .= 'i'; $params[] = (int) $f['year']; }

    if ($f['status'] === '')            { $where[] = "status <> 'deleted'"; }   // default: hide soft-deleted
    elseif ($f['status'] !== 'all')     { $where[] = 'status = ?'; $types .= 's'; $params[] = $f['status']; }

    return [$where ? 'WHERE ' . implode(' AND ', $where) : '', $types, $params];
}

function student_count(array $f): int
{
    [$w, $t, $p] = student_where($f);
    return (int) db_value("SELECT COUNT(*) FROM students $w", $t, $p);
}

function student_search(array $f, int $limit = 10, int $offset = 0): array
{
    [$w, $t, $p] = student_where($f);
    $t .= 'ii';
    $p[] = $limit;
    $p[] = $offset;
    return db_all(
        "SELECT id, user_id, student_code, name, email, phone, course, year, gender, status, created_at
           FROM students $w ORDER BY id DESC LIMIT ? OFFSET ?",
        $t,
        $p
    );
}

function student_find(int $id): ?array
{
    return db_one(
        'SELECT id, user_id, student_code, name, email, phone, course, year, gender, status, created_at
           FROM students WHERE id = ?',
        'i',
        [$id]
    );
}

function student_create(array $c): int
{
    db_exec(
        'INSERT INTO students (student_code, name, email, phone, course, year, gender, status)
         VALUES (?, ?, ?, ?, ?, ?, ?, ?)',
        'sssssiss',
        ['TMP' . bin2hex(random_bytes(6)), $c['name'], $c['email'], $c['phone'], $c['course'], (int) $c['year'], $c['gender'], $c['status']]
    );
    $id = db_insert_id();
    db_exec('UPDATE students SET student_code = ? WHERE id = ?', 'si', [sprintf('STU%d%03d', (int) date('Y'), $id), $id]);
    return $id;
}

function student_update(int $id, array $c): int
{
    return db_exec(
        'UPDATE students SET name = ?, email = ?, phone = ?, course = ?, year = ?, gender = ?, status = ? WHERE id = ?',
        'ssssissi',
        [$c['name'], $c['email'], $c['phone'], $c['course'], (int) $c['year'], $c['gender'], $c['status'], $id]
    );
}

/** Soft delete (Practical 11 advanced): keep the row, just mark it. */
function student_soft_delete(int $id): int
{
    return db_exec("UPDATE students SET status = 'deleted' WHERE id = ? AND status <> 'deleted'", 'i', [$id]);
}

function student_restore(int $id): int
{
    return db_exec("UPDATE students SET status = 'active' WHERE id = ? AND status = 'deleted'", 'i', [$id]);
}

/** Permanent delete – only allowed for rows that are already soft-deleted. */
function student_purge(int $id): int
{
    return db_exec("DELETE FROM students WHERE id = ? AND status = 'deleted'", 'i', [$id]);
}
