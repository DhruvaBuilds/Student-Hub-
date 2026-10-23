<?php
/**
 * StudentHub – central configuration.
 * Change the DB_* values if your MySQL setup is different from the XAMPP default.
 */

define('ROOT_PATH', dirname(__DIR__));

// ---- Database (XAMPP defaults) ----
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'studenthub');

// ---- Security / session ----
define('SESSION_TIMEOUT', 900);          // 15 minutes of inactivity
define('REMEMBER_DAYS', 30);             // remember-me lifetime
define('REMEMBER_COOKIE', 'sh_remember');
define('MAX_LOGIN_ATTEMPTS', 5);         // per session, then 5 minute lock
define('LOGIN_LOCK_SECONDS', 300);
// A valid bcrypt hash used so that unknown e-mails take the same time as wrong passwords
define('DUMMY_HASH', '$2y$12$oogWKQ8W2eRO1DNMJQTGjeae5Xo2LOhM23IDNvd44U6A69IXt0JX.');

// ---- Uploads (Practical 12) ----
define('UPLOAD_DIR', ROOT_PATH . '/uploads/posters/');
define('MAX_POSTER_SIZE', 2 * 1024 * 1024);   // 2 MB

// ---- File storage (Practical 7) ----
define('STORAGE_DIR', ROOT_PATH . '/storage/');

// ---- Lists shared by forms, validation and filters ----
const COURSES = [
    'B.Tech Computer Engineering',
    'B.Tech Information Technology',
    'B.Tech Computer Science',
    'B.Tech Electronics & Communication',
];
const EVENT_CATEGORIES = ['Exam', 'Workshop', 'Sports', 'Seminar', 'Academic', 'Competition', 'Cultural'];

/**
 * Work out the URL prefix of the project automatically.
 *   http://localhost/StudentHub/   -> "/StudentHub"
 *   http://studenthub.local/       -> ""   (Apache virtual host)
 */
function detect_base_url(): string
{
    $docRoot = !empty($_SERVER['DOCUMENT_ROOT']) ? realpath($_SERVER['DOCUMENT_ROOT']) : false;
    $root    = realpath(ROOT_PATH);
    if ($docRoot && $root) {
        $docRoot = rtrim(str_replace('\\', '/', $docRoot), '/');
        $root    = str_replace('\\', '/', $root);
        if (stripos($root, $docRoot) === 0) {
            return rtrim(substr($root, strlen($docRoot)), '/');
        }
    }
    return '/StudentHub';
}
define('BASE_URL', detect_base_url());
