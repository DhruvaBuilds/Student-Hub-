<?php
require_once __DIR__ . '/../includes/bootstrap.php';
require_login();

$pageTitle = 'Courses';
$activeNav = 'courses';
require ROOT_PATH . '/includes/header.php';
?>
<main id="main-content">
        <section class="breadcrumb">
            <div class="container">
                <p>
                    <a href="<?= e(url('index.php')) ?>">Home</a>
                    >
                    <span>Courses</span>
                </p>
            </div>
        </section>
        <section class="page-title">
            <div class="container">
                <h1>Courses</h1>
                <p>
                    Browse all your enrolled courses.
                </p>
            </div>
        </section>
        <section class="quick-access">
            <div class="container">
                <div class="card-container">
                    <div class="card">
                        <h3>📚 Web Development</h3>
                        <p>
                            Learn HTML, CSS and JavaScript.
                        </p>
                    </div>
                    <div class="card">
                        <h3>💻 Data Structures</h3>
                        <p>
                            Arrays, Linked Lists, Trees and Graphs.
                        </p>
                    </div>
                    <div class="card">
                        <h3>🗄 Database Management</h3>
                        <p>
                            SQL and relational databases.
                        </p>
                    </div>
                    <div class="card">
                        <h3>🌐 Computer Networks</h3>
                        <p>
                            Networking concepts and protocols.
                        </p>
                    </div>
                    <div class="card">
                        <h3>⚙ Operating Systems</h3>
                        <p>
                            Memory management and processes.
                        </p>
                    </div>
                    <div class="card">
                        <h3>📐 Discrete Mathematics</h3>
                        <p>
                            Logic, Sets, Graphs and Relations.
                        </p>
                    </div>
                </div>
            </div>
        </section>
    </main>
<?php require ROOT_PATH . '/includes/footer.php';
