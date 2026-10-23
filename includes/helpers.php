<?php
/**
 * Small reusable helpers: escaping, URLs, flash messages, CSRF, validation,
 * audit logging, pagination and safe file storage.
 */

// ---------------------------------------------------------------------------
// Output / URLs
// ---------------------------------------------------------------------------

/** Escape for HTML output (prevents XSS). */
function e($value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

/** Build a URL relative to the project root, e.g. url('pages/login.php'). */
function url(string $path = ''): string
{
    return BASE_URL . '/' . ltrim($path, '/');
}

function redirect(string $path): void
{
    header('Location: ' . url($path));
    exit;
}

function client_ip(): string
{
    return $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
}

// ---------------------------------------------------------------------------
// Flash messages (one-time messages shown after a redirect)
// ---------------------------------------------------------------------------

function flash_set(string $type, string $message): void
{
    $_SESSION['flash'][] = ['type' => $type, 'message' => $message];
}

function flash_render(): string
{
    if (empty($_SESSION['flash'])) {
        return '';
    }
    $html = '';
    foreach ($_SESSION['flash'] as $f) {
        $role = $f['type'] === 'error' ? 'alert' : 'status';
        $html .= '<div class="alert alert-' . e($f['type']) . '" role="' . $role . '">'
               . e($f['message']) . '</div>';
    }
    unset($_SESSION['flash']);
    return $html;
}

// ---------------------------------------------------------------------------
// CSRF protection
// ---------------------------------------------------------------------------

function csrf_token(): string
{
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function csrf_field(): string
{
    return '<input type="hidden" name="csrf_token" value="' . e(csrf_token()) . '">';
}

function csrf_verify(?string $token): bool
{
    return !empty($_SESSION['csrf_token']) && is_string($token)
        && hash_equals($_SESSION['csrf_token'], $token);
}

/** Stop the request if the posted CSRF token is wrong. */
function csrf_require_post(): void
{
    if (!csrf_verify($_POST['csrf_token'] ?? null)) {
        http_response_code(419);
        exit('Invalid or expired form token. Go back, refresh the page and try again.');
    }
}

// ---------------------------------------------------------------------------
// Form helpers
// ---------------------------------------------------------------------------

/** Trimmed POST value. */
function post(string $key, string $default = ''): string
{
    $v = $_POST[$key] ?? $default;
    return is_string($v) ? trim($v) : $default;
}

/** Trimmed GET value. */
function get(string $key, string $default = ''): string
{
    $v = $_GET[$key] ?? $default;
    return is_string($v) ? trim($v) : $default;
}

/** Render the error message for one field (matches the JS error style). */
function field_error(array $errors, string $field): string
{
    if (empty($errors[$field])) {
        return '';
    }
    return '<p class="error-message" id="' . e($field) . '-error" role="alert">❌ ' . e($errors[$field]) . '</p>';
}

/** Add the error CSS class + ARIA attributes for an invalid field. */
function field_attrs(array $errors, string $field): string
{
    return empty($errors[$field]) ? '' : ' class="input-error" aria-invalid="true" aria-describedby="' . e($field) . '-error"';
}

// ---------------------------------------------------------------------------
// Validation (the SAME rules as js/validation.js so both sides agree)
// ---------------------------------------------------------------------------

function valid_name(string $v): bool     { return (bool) preg_match('/^[A-Za-z ]{2,100}$/', $v); }
function valid_email(string $v): bool    { return strlen($v) <= 150 && filter_var($v, FILTER_VALIDATE_EMAIL) !== false; }
function valid_phone(string $v): bool    { return (bool) preg_match('/^[6-9][0-9]{9}$/', $v); }
function valid_password(string $v): bool { return (bool) preg_match('/^(?=.*[A-Za-z])(?=.*\d).{8,72}$/', $v); }

function valid_year($v): bool
{
    return in_array((string) $v, ['1', '2', '3', '4'], true);
}

function year_label(int $year): string
{
    $suffix = [1 => 'st', 2 => 'nd', 3 => 'rd', 4 => 'th'];
    return $year . ($suffix[$year] ?? 'th') . ' Year';
}

// ---------------------------------------------------------------------------
// Audit log (Practical 14)
// ---------------------------------------------------------------------------

function audit_log(string $action, string $details = '', ?int $userId = null): void
{
    try {
        if ($userId === null && isset($_SESSION['user_id'])) {
            $userId = (int) $_SESSION['user_id'];
        }
        db_exec(
            'INSERT INTO audit_logs (user_id, action, details, ip_address) VALUES (?, ?, ?, ?)',
            'isss',
            [$userId, $action, mb_substr($details, 0, 255), client_ip()]
        );
    } catch (Throwable $e) {
        // Logging must never break the page.
        error_log('[StudentHub] audit_log failed: ' . $e->getMessage());
    }
}

// ---------------------------------------------------------------------------
// Pagination
// ---------------------------------------------------------------------------

/** Returns [page, perPage, offset, totalPages]. */
function paginate(int $total, int $perPage = 10): array
{
    $page = max(1, (int) ($_GET['page'] ?? 1));
    $totalPages = max(1, (int) ceil($total / $perPage));
    $page = min($page, $totalPages);
    return [$page, $perPage, ($page - 1) * $perPage, $totalPages];
}

/** Pagination links that keep the current search/filter query string. */
function pagination_links(int $page, int $totalPages): string
{
    if ($totalPages <= 1) {
        return '';
    }
    $query = $_GET;
    unset($query['page']);
    $build = function (int $p) use ($query): string {
        return '?' . http_build_query($query + ['page' => $p]);
    };
    $html = '<nav class="pagination" aria-label="Pagination">';
    if ($page > 1) {
        $html .= '<a href="' . e($build($page - 1)) . '" rel="prev">&laquo; Prev</a>';
    }
    $start = max(1, $page - 2);
    $end   = min($totalPages, $page + 2);
    for ($p = $start; $p <= $end; $p++) {
        if ($p === $page) {
            $html .= '<span class="current" aria-current="page">' . $p . '</span>';
        } else {
            $html .= '<a href="' . e($build($p)) . '">' . $p . '</a>';
        }
    }
    if ($page < $totalPages) {
        $html .= '<a href="' . e($build($page + 1)) . '" rel="next">Next &raquo;</a>';
    }
    return $html . '</nav>';
}

// ---------------------------------------------------------------------------
// CSV / JSON file storage (Practical 7)
// ---------------------------------------------------------------------------

/** Stop spreadsheet formula injection: =, +, -, @ at the start of a cell. */
function csv_safe(string $value): string
{
    return preg_match('/^[=+\-@\t\r]/', $value) ? "'" . $value : $value;
}

/** Append one record to a JSON array file safely (exclusive lock). */
function json_append(string $file, array $record): bool
{
    $fp = fopen($file, 'c+');
    if ($fp === false) {
        return false;
    }
    if (!flock($fp, LOCK_EX)) {
        fclose($fp);
        return false;
    }
    $raw  = stream_get_contents($fp);
    $data = $raw !== '' && $raw !== false ? json_decode($raw, true) : [];
    if (!is_array($data)) {
        $data = [];
    }
    $record = ['id' => count($data) + 1] + $record;
    $data[] = $record;
    ftruncate($fp, 0);
    rewind($fp);
    $ok = fwrite($fp, json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)) !== false;
    fflush($fp);
    flock($fp, LOCK_UN);
    fclose($fp);
    return $ok;
}

/** Append one row to a CSV file safely (adds the header the first time). */
function csv_append(string $file, array $headers, array $row): bool
{
    $isNew = !file_exists($file) || filesize($file) === 0;
    $fp = fopen($file, 'a');
    if ($fp === false) {
        return false;
    }
    if (!flock($fp, LOCK_EX)) {
        fclose($fp);
        return false;
    }
    if ($isNew) {
        fputcsv($fp, $headers, ',', '"', '\\');
    }
    $ok = fputcsv($fp, array_map(fn($v) => csv_safe((string) $v), $row), ',', '"', '\\') !== false;
    fflush($fp);
    flock($fp, LOCK_UN);
    fclose($fp);
    return $ok;
}
