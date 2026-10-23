<?php
/**
 * Practical 13 – shared plumbing for every JSON endpoint:
 *   • always answers with JSON + a proper HTTP status code
 *   • centralised error handling (uncaught exceptions become a JSON 500)
 *   • helpers for method checks, JSON bodies, CSRF and admin-only access
 *
 * Standard response shape:
 *   success: { "success": true,  "message": "...", "data": { ... } }
 *   failure: { "success": false, "message": "...", "errors": { "field": "..." } }
 */
define('API_REQUEST', true);

function json_response(int $status, array $payload): void
{
    http_response_code($status);
    echo json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

function api_success($data = [], string $message = 'OK', int $status = 200): void
{
    json_response($status, ['success' => true, 'message' => $message, 'data' => $data]);
}

function api_error(int $status, string $message, array $errors = []): void
{
    $payload = ['success' => false, 'message' => $message];
    if ($errors) {
        $payload['errors'] = $errors;
    }
    json_response($status, $payload);
}

// Centralised error handling: never leak stack traces to the browser
ini_set('display_errors', '0');
header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');
set_exception_handler(function (Throwable $e) {
    error_log('[StudentHub API] ' . get_class($e) . ': ' . $e->getMessage() . ' @ ' . $e->getFile() . ':' . $e->getLine());
    if (!headers_sent()) {
        http_response_code(500);
    }
    echo json_encode(['success' => false, 'message' => 'Internal server error. Please try again later.']);
});

require_once __DIR__ . '/../includes/bootstrap.php';

/** 405 unless the request method is one of $allowed. Returns the method. */
function api_method(array $allowed): string
{
    $method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
    if (!in_array($method, $allowed, true)) {
        header('Allow: ' . implode(', ', $allowed));
        api_error(405, 'Method not allowed. Use ' . implode(' or ', $allowed) . '.');
    }
    return $method;
}

/** Request body as an array (JSON body, or normal form fields). */
function api_body(): array
{
    $type = $_SERVER['CONTENT_TYPE'] ?? '';
    if (stripos($type, 'application/json') !== false) {
        $raw = file_get_contents('php://input');
        $data = json_decode($raw === false ? '' : $raw, true);
        if (!is_array($data)) {
            api_error(400, 'Request body must be valid JSON.');
        }
        return $data;
    }
    return $_POST;
}

/** State-changing requests must carry the CSRF token in the X-CSRF-Token header. */
function api_require_csrf(): void
{
    $token = $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '';
    if (!csrf_verify($token)) {
        api_error(403, 'Invalid or missing CSRF token. Refresh the page and try again.');
    }
}

function api_require_login(): void
{
    if (!is_logged_in()) {
        api_error(401, 'Please log in first.');
    }
}

function api_require_admin(): void
{
    api_require_login();
    if (db_value('SELECT status FROM users WHERE id = ?', 'i', [current_user_id()]) !== 'active') {
        api_error(401, 'Your account is not active.');
    }
    if (!is_admin()) {
        audit_log('unauthorized_access', 'API ' . ($_SERVER['REQUEST_URI'] ?? ''));
        api_error(403, 'Administrator access required.');
    }
}

function api_int($value): int
{
    return filter_var($value, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]) ?: 0;
}
