<?php
/** Practical 10 – logout. POST + CSRF token so another site cannot log you out. */
require_once __DIR__ . '/../includes/bootstrap.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && csrf_verify($_POST['csrf_token'] ?? null)) {
    logout_user();          // audit entry, remember-me token removed, session destroyed
    start_fresh_session();  // new empty session so we can show a message
    flash_set('success', 'You have been logged out successfully.');
    redirect('pages/login.php');
}
redirect('index.php');
