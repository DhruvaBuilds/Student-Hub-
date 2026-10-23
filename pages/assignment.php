<?php
require_once __DIR__ . '/../includes/bootstrap.php';
require_login();

$pageTitle = 'Assignments';
$activeNav = 'assignment';
require ROOT_PATH . '/includes/header.php';
?>
<main id="main-content">
        <section class="breadcrumb">
            <div class="container">
                <p>
                    <a href="<?= e(url('index.php')) ?>">Home</a> >
                    <span>Assignments</span>
                </p>
            </div>
        </section>
        <section class="page-title">
            <div class="container">
                <h1>Assignments</h1>
                <p>View your pending and submitted assignments.</p>
            </div>
        </section>
        <section class="quick-access">
            <div class="container">
                <div class="card-container">
                    <div class="card">
                        <h3>Web Development</h3>
                        <p><strong>Assignment:</strong> Build a Portfolio Website</p>
                        <p><strong>Due Date:</strong> 10 August 2026</p>
                        <p class="status pending">Pending</p>
                    </div>
                    <div class="card">
                        <h3>Data Structures</h3>
                        <p><strong>Assignment:</strong> Linked List Program</p>
                        <p><strong>Due Date:</strong> 12 August 2026</p>
                        <p class="status submitted">Submitted</p>
                    </div>
                    <div class="card">
                        <h3>Database Management</h3>
                        <p><strong>Assignment:</strong> SQL Queries</p>
                        <p><strong>Due Date:</strong> 14 August 2026</p>
                        <p class="status pending">Pending</p>
                    </div>
                    <div class="card">
                        <h3>Computer Networks</h3>
                        <p><strong>Assignment:</strong> Network Topologies Report</p>
                        <p><strong>Due Date:</strong> 16 August 2026</p>
                        <p class="status submitted">Submitted</p>
                    </div>
                </div>
            </div>
        </section>
    </main>
<?php require ROOT_PATH . '/includes/footer.php';
