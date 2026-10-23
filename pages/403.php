<?php
require_once __DIR__ . '/../includes/bootstrap.php';
http_response_code(403);
$pageTitle = 'Access denied';
require ROOT_PATH . '/includes/header.php';
?>
<main id="main-content">
    <section class="page-title"><div class="container">
        <h1>403 – Access denied</h1>
        <p>You do not have permission to open this page.</p>
        <p><a class="btn primary-btn" href="<?= e(url('index.php')) ?>">Back to Home</a></p>
    </div></section>
</main>
<?php require ROOT_PATH . '/includes/footer.php';
