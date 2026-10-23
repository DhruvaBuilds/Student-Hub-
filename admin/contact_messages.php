<?php
/** Practical 7 (intermediate) – show the messages stored in storage/contacts.json on a web page. */
require_once __DIR__ . '/../includes/bootstrap.php';
require_role('admin');

$file = STORAGE_DIR . 'contacts.json';
$messages = [];
if (is_file($file)) {
    $decoded = json_decode((string) file_get_contents($file), true);
    if (is_array($decoded)) {
        $messages = array_reverse($decoded);       // newest first
    }
}

// Download the CSV copy
if (get('download') === 'csv') {
    $csv = STORAGE_DIR . 'contacts.csv';
    if (is_file($csv)) {
        audit_log('contact_export', 'Downloaded contacts.csv');
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="contact_messages.csv"');
        readfile($csv);
        exit;
    }
    flash_set('error', 'No CSV file exists yet.');
    redirect('admin/contact_messages.php');
}

[$page, $perPage, $offset, $totalPages] = paginate(count($messages), 10);
$pageItems = array_slice($messages, $offset, $perPage);

$pageTitle = 'Contact Messages';
$activeNav = 'messages';
require ROOT_PATH . '/includes/header.php';
?>
<main id="main-content" class="admin-main">
    <section class="breadcrumb">
        <div class="container"><p><a href="<?= e(url('admin/dashboard.php')) ?>">Admin</a> &gt; <span>Messages</span></p></div>
    </section>

    <section class="admin-section">
        <div class="container">
            <div class="admin-head">
                <h1>Contact Messages</h1>
                <div class="admin-actions">
                    <a class="btn btn-outline" href="?download=csv">⬇ Download CSV</a>
                </div>
            </div>
            <p class="muted">Read from <code>storage/contacts.json</code> – saved by the public Contact form.</p>
            <p class="result-count"><?= count($messages) ?> message<?= count($messages) === 1 ? '' : 's' ?></p>

            <div class="table-wrap">
                <table class="data-table">
                    <caption class="visually-hidden">Stored contact messages</caption>
                    <thead><tr><th scope="col">#</th><th scope="col">Date</th><th scope="col">Name</th><th scope="col">Email</th>
                        <th scope="col">Subject</th><th scope="col">Message</th></tr></thead>
                    <tbody>
                    <?php if (!$pageItems): ?><tr><td colspan="6" class="empty-row">No messages yet.</td></tr><?php endif; ?>
                    <?php foreach ($pageItems as $m): ?>
                        <tr>
                            <td><?= (int) ($m['id'] ?? 0) ?></td>
                            <td><?= e($m['created_at'] ?? '') ?></td>
                            <td><?= e($m['name'] ?? '') ?></td>
                            <td><?= e($m['email'] ?? '') ?></td>
                            <td><?= e($m['subject'] ?? '') ?></td>
                            <td class="msg-cell"><?= nl2br(e($m['message'] ?? '')) ?></td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <?= pagination_links($page, $totalPages) ?>
        </div>
    </section>
</main>
<?php require ROOT_PATH . '/includes/footer.php';
