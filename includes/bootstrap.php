<?php
/**
 * Loaded at the top of every page:  require_once __DIR__ . '/../includes/bootstrap.php';
 * Loads config, DB helpers, helpers, auth and starts the secure session.
 */
date_default_timezone_set('Asia/Kolkata');

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/helpers.php';
require_once __DIR__ . '/auth.php';

// Basic security headers
header('X-Content-Type-Options: nosniff');
header('X-Frame-Options: SAMEORIGIN');
header('Referrer-Policy: strict-origin-when-cross-origin');

ini_set('log_errors', '1');
ini_set('error_log', ROOT_PATH . '/storage/php-errors.log');

start_secure_session();
