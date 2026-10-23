<?php
/**
 * Practical 13 – Students JSON API (admin only).
 *
 *   GET  api/students.php?action=list&q=&course=&year=&status=&page=1&per_page=10
 *   GET  api/students.php?action=get&id=5
 *   POST api/students.php   {"action":"create","name":..,"email":..,"phone":..,"course":..,"year":..,"gender":..}
 *   POST api/students.php   {"action":"update","id":5, ...fields}
 *   POST api/students.php   {"action":"delete","id":5}      (soft delete)
 *   POST api/students.php   {"action":"restore","id":5}
 *
 * Status codes: 200 OK, 201 Created, 400 bad request, 401 not logged in,
 * 403 forbidden / bad CSRF, 404 not found, 405 wrong method, 409 conflict,
 * 422 validation failed, 500 server error.
 */
require_once __DIR__ . '/bootstrap.php';
require_once __DIR__ . '/../includes/students_repo.php';

api_require_admin();
$method = api_method(['GET', 'POST']);

if ($method === 'GET') {
    $action = get('action', 'list');

    if ($action === 'get') {
        $id = api_int($_GET['id'] ?? 0);
        if (!$id) api_error(400, 'A valid student id is required.');
        $student = student_find($id);
        if (!$student) api_error(404, 'Student not found.');
        api_success($student);
    }

    if ($action !== 'list') {
        api_error(400, 'Unknown action.');
    }

    $filters = student_filters($_GET);
    $perPage = min(50, max(1, (int) ($_GET['per_page'] ?? 10)));
    $total   = student_count($filters);
    $pages   = max(1, (int) ceil($total / $perPage));
    $page    = min($pages, max(1, (int) ($_GET['page'] ?? 1)));
    $items   = student_search($filters, $perPage, ($page - 1) * $perPage);

    api_success([
        'items'      => $items,
        'pagination' => ['page' => $page, 'per_page' => $perPage, 'total' => $total, 'total_pages' => $pages],
    ]);
}

// ---------------- POST: create / update / delete / restore ----------------
api_require_csrf();
$body   = api_body();
$action = (string) ($body['action'] ?? '');

try {
    switch ($action) {
        case 'create':
            [$clean, $errors] = student_validate($body);
            if ($errors) api_error(422, 'Please fix the highlighted fields.', $errors);
            $id = student_create($clean);
            audit_log('student_create', "Created student #$id {$clean['name']} (API)");
            api_success(student_find($id), 'Student added successfully.', 201);

        case 'update':
            $id = api_int($body['id'] ?? 0);
            if (!$id || !student_find($id)) api_error(404, 'Student not found.');
            [$clean, $errors] = student_validate($body, $id);
            if ($errors) api_error(422, 'Please fix the highlighted fields.', $errors);
            student_update($id, $clean);
            audit_log('student_update', "Updated student #$id {$clean['name']} (API)");
            api_success(student_find($id), 'Student updated successfully.');

        case 'delete':
            $id = api_int($body['id'] ?? 0);
            if (!$id) api_error(400, 'A valid student id is required.');
            if (!student_soft_delete($id)) api_error(404, 'Student not found or already deleted.');
            audit_log('student_delete', "Soft-deleted student #$id (API)");
            api_success(['id' => $id], 'Student deleted.');

        case 'restore':
            $id = api_int($body['id'] ?? 0);
            if (!$id) api_error(400, 'A valid student id is required.');
            if (!student_restore($id)) api_error(404, 'Student not found or not deleted.');
            audit_log('student_restore', "Restored student #$id (API)");
            api_success(['id' => $id], 'Student restored.');

        default:
            api_error(400, 'Unknown action. Use create, update, delete or restore.');
    }
} catch (mysqli_sql_exception $e) {
    if ($e->getCode() === 1062) {
        api_error(409, 'A student with this email already exists.', ['email' => 'Email already exists.']);
    }
    throw $e;   // handled by the central handler -> 500
}
