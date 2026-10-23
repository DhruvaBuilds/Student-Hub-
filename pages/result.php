<?php
require_once __DIR__ . '/../includes/bootstrap.php';
require_login();

$pageTitle = 'Results';
$activeNav = 'result';
require ROOT_PATH . '/includes/header.php';
?>
<main id="main-content">
        <section class="breadcrumb">
            <div class="container">
                <p>
                    <a href="<?= e(url('index.php')) ?>">Home</a> >
                    <span>Results</span>
                </p>
            </div>
        </section>
        <section class="page-title">
            <div class="container">
                <h1>Semester Results</h1>
                <p>View your examination performance.</p>
            </div>
        </section>
        <section class="quick-access">
            <div class="container">
                <div class="result-summary">
                    <div class="card">
                        <h2>CGPA</h2>
                        <h1>8.90</h1>
                        <p>Excellent Performance</p>
                    </div>
                </div>
                <br>
                <table class="attendance-table">
                    <thead>
                        <tr>
                            <th>Subject</th>
                            <th>Internal</th>
                            <th>External</th>
                            <th>Total</th>
                            <th>Grade</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td>Web Development</td>
                            <td>28</td>
                            <td>62</td>
                            <td>90</td>
                            <td>A+</td>
                        </tr>
                        <tr>
                            <td>Data Structures</td>
                            <td>27</td>
                            <td>58</td>
                            <td>85</td>
                            <td>A</td>
                        </tr>
                        <tr>
                            <td>Database Management</td>
                            <td>26</td>
                            <td>60</td>
                            <td>86</td>
                            <td>A</td>
                        </tr>
                        <tr>
                            <td>Computer Networks</td>
                            <td>29</td>
                            <td>61</td>
                            <td>90</td>
                            <td>A+</td>
                        </tr>
                        <tr>
                            <td>Operating Systems</td>
                            <td>25</td>
                            <td>57</td>
                            <td>82</td>
                            <td>B+</td>
                        </tr>
                        <tr>
                            <td>Discrete Mathematics</td>
                            <td>28</td>
                            <td>60</td>
                            <td>88</td>
                            <td>A</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </section>
    </main>
<?php require ROOT_PATH . '/includes/footer.php';
