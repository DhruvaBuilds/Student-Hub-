<?php
/**
 * Practical 10 – sessions, login/logout, role-based access, timeout, remember-me.
 */

// ---------------------------------------------------------------------------
// Session start (secure cookie settings + inactivity timeout)
// ---------------------------------------------------------------------------

function start_secure_session(): void
{
    if (session_status() === PHP_SESSION_ACTIVE) {
        return;
    }

    $https = !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off';

    ini_set('session.use_strict_mode', '1');   // refuse unknown session IDs
    ini_set('session.use_only_cookies', '1');  // never accept IDs from the URL
    session_name('STUDENTHUB_SESSID');
    session_set_cookie_params([
        'lifetime' => 0,                       // cookie dies when the browser closes
        'path'     => BASE_URL === '' ? '/' : BASE_URL . '/',
        'secure'   => $https,
        'httponly' => true,                    // JavaScript cannot read it
        'samesite' => 'Lax',                   // basic CSRF protection
    ]);
    session_start();

    if (isset($_SESSION['user_id'])) {
        // Logged in: enforce the inactivity timeout
        $idle = time() - ($_SESSION['last_activity'] ?? time());
        if ($idle > SESSION_TIMEOUT) {
            audit_log('session_timeout', 'Session expired after inactivity', (int) $_SESSION['user_id']);
            clear_remember_me((int) $_SESSION['user_id']);
            end_session();
            start_fresh_session();
            flash_set('error', 'Your session expired due to inactivity. Please log in again.');
            return;
        }
        $_SESSION['last_activity'] = time();
    } else {
        restore_from_remember_me();
    }
}

/** Open a brand-new (empty) session after the old one was destroyed. */
function start_fresh_session(): void
{
    session_start();
    session_regenerate_id(true);
}

/** Completely destroy the current session and its cookie. */
function end_session(): void
{
    $_SESSION = [];
    if (ini_get('session.use_cookies')) {
        $p = session_get_cookie_params();
        setcookie(session_name(), '', [
            'expires'  => time() - 42000,
            'path'     => $p['path'],
            'domain'   => $p['domain'],
            'secure'   => $p['secure'],
            'httponly' => $p['httponly'],
            'samesite' => $p['samesite'] ?: 'Lax',
        ]);
    }
    session_destroy();
}

// ---------------------------------------------------------------------------
// State helpers
// ---------------------------------------------------------------------------

function is_logged_in(): bool
{
    return !empty($_SESSION['user_id']);
}

function current_user_id(): ?int
{
    return is_logged_in() ? (int) $_SESSION['user_id'] : null;
}

function current_role(): ?string
{
    return $_SESSION['role'] ?? null;
}

function is_admin(): bool
{
    return current_role() === 'admin';
}

/** Where each role lands after login. */
function home_for_role(?string $role): string
{
    return $role === 'admin' ? 'admin/dashboard.php' : 'pages/dashboard.php';
}

// ---------------------------------------------------------------------------
// Login / logout
// ---------------------------------------------------------------------------

/** Start an authenticated session for a verified user row. */
function login_user(array $user, bool $remember = false): void
{
    session_regenerate_id(true);               // stops session fixation
    $_SESSION['user_id']       = (int) $user['id'];
    $_SESSION['user_name']     = $user['name'];
    $_SESSION['user_email']    = $user['email'];
    $_SESSION['role']          = $user['role'];
    $_SESSION['last_activity'] = time();
    unset($_SESSION['login_attempts'], $_SESSION['login_locked_until']);

    db_exec('UPDATE users SET last_login = NOW() WHERE id = ?', 'i', [$user['id']]);

    if ($remember) {
        issue_remember_me((int) $user['id']);
    }
}

function logout_user(): void
{
    $uid = current_user_id();
    if ($uid !== null) {
        audit_log('logout', 'User logged out', $uid);
        clear_remember_me($uid);
    }
    end_session();
}

// ---------------------------------------------------------------------------
// Remember-me: selector + random validator. Only SHA-256(validator) is stored.
// ---------------------------------------------------------------------------

function remember_cookie_options(int $expires): array
{
    return [
        'expires'  => $expires,
        'path'     => BASE_URL === '' ? '/' : BASE_URL . '/',
        'secure'   => !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off',
        'httponly' => true,
        'samesite' => 'Lax',
    ];
}

function issue_remember_me(int $userId): void
{
    $selector  = bin2hex(random_bytes(12));    // 24 hex chars
    $validator = bin2hex(random_bytes(32));    // 64 hex chars (never stored)
    $expires   = time() + REMEMBER_DAYS * 86400;

    db_exec(
        'INSERT INTO remember_tokens (user_id, selector, token_hash, expires_at) VALUES (?, ?, ?, ?)',
        'isss',
        [$userId, $selector, hash('sha256', $validator), date('Y-m-d H:i:s', $expires)]
    );
    setcookie(REMEMBER_COOKIE, $selector . ':' . $validator, remember_cookie_options($expires));
}

/** Delete the user's stored tokens and the cookie. */
function clear_remember_me(int $userId): void
{
    try {
        db_exec('DELETE FROM remember_tokens WHERE user_id = ?', 'i', [$userId]);
    } catch (Throwable $e) {
        error_log('[StudentHub] clear_remember_me: ' . $e->getMessage());
    }
    setcookie(REMEMBER_COOKIE, '', remember_cookie_options(time() - 3600));
    unset($_COOKIE[REMEMBER_COOKIE]);
}

/** Silent login from the remember-me cookie (rotates the token each time). */
function restore_from_remember_me(): void
{
    if (empty($_COOKIE[REMEMBER_COOKIE]) || !is_string($_COOKIE[REMEMBER_COOKIE])) {
        return;
    }
    $parts = explode(':', $_COOKIE[REMEMBER_COOKIE], 2);
    if (count($parts) !== 2 || !ctype_xdigit($parts[0]) || !ctype_xdigit($parts[1])) {
        setcookie(REMEMBER_COOKIE, '', remember_cookie_options(time() - 3600));
        return;
    }
    [$selector, $validator] = $parts;

    try {
        $row = db_one(
            'SELECT t.id, t.token_hash, u.id AS uid, u.name, u.email, u.role, u.status
               FROM remember_tokens t JOIN users u ON u.id = t.user_id
              WHERE t.selector = ? AND t.expires_at > NOW()',
            's',
            [$selector]
        );
        if (!$row) {
            setcookie(REMEMBER_COOKIE, '', remember_cookie_options(time() - 3600));
            return;
        }
        if (!hash_equals($row['token_hash'], hash('sha256', $validator))) {
            // Someone presented a wrong validator for a real selector: kill all tokens
            db_exec('DELETE FROM remember_tokens WHERE selector = ?', 's', [$selector]);
            setcookie(REMEMBER_COOKIE, '', remember_cookie_options(time() - 3600));
            return;
        }
        if ($row['status'] !== 'active') {
            return;
        }
        db_exec('DELETE FROM remember_tokens WHERE id = ?', 'i', [$row['id']]);
        login_user(
            ['id' => $row['uid'], 'name' => $row['name'], 'email' => $row['email'], 'role' => $row['role']],
            true
        );
        audit_log('login_remember', 'Logged in using remember-me token', (int) $row['uid']);
    } catch (Throwable $e) {
        error_log('[StudentHub] remember-me failed: ' . $e->getMessage());
    }
}

// ---------------------------------------------------------------------------
// Login throttling (per session) – slows down password guessing
// ---------------------------------------------------------------------------

function login_is_locked(): int
{
    $until = $_SESSION['login_locked_until'] ?? 0;
    return $until > time() ? $until - time() : 0;
}

function login_register_failure(): void
{
    $_SESSION['login_attempts'] = ($_SESSION['login_attempts'] ?? 0) + 1;
    if ($_SESSION['login_attempts'] >= MAX_LOGIN_ATTEMPTS) {
        $_SESSION['login_locked_until'] = time() + LOGIN_LOCK_SECONDS;
        $_SESSION['login_attempts'] = 0;
    }
}

// ---------------------------------------------------------------------------
// Access guards
// ---------------------------------------------------------------------------

/** Page needs a logged-in user. */
function require_login(): void
{
    if (!is_logged_in()) {
        flash_set('error', 'Please log in to continue.');
        redirect('pages/login.php');
    }
}

/**
 * Page needs one of the given roles. Logged-out users go to the login page,
 * logged-in users with the wrong role see a 403 page and the attempt is logged.
 */
function require_role(string ...$roles): void
{
    require_login();
    // Re-check the account is still active (admin may have deactivated it)
    $status = db_value('SELECT status FROM users WHERE id = ?', 'i', [current_user_id()]);
    if ($status !== 'active') {
        logout_user();
        start_fresh_session();
        flash_set('error', 'Your account is not active. Please contact the administrator.');
        redirect('pages/login.php');
    }
    if (!in_array(current_role(), $roles, true)) {
        audit_log('unauthorized_access', 'Tried to open ' . ($_SERVER['REQUEST_URI'] ?? ''));
        http_response_code(403);
        $pageTitle = 'Access denied';
        require ROOT_PATH . '/includes/header.php';
        echo '<main id="main-content"><section class="page-title"><div class="container">'
           . '<h1>403 – Access denied</h1><p>You do not have permission to open this page.</p>'
           . '<p><a class="btn primary-btn" href="' . e(url(home_for_role(current_role()))) . '">Go to my dashboard</a></p>'
           . '</div></section></main>';
        require ROOT_PATH . '/includes/footer.php';
        exit;
    }
}
