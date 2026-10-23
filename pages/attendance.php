<?php
require_once __DIR__ . '/../includes/bootstrap.php';
require_login();

$pageTitle = 'Attendance';
$activeNav = 'attendance';
require ROOT_PATH . '/includes/header.php';
?>
<main id="main-content">
        <section class="breadcrumb">
            <div class="container">
                <p>
                    <a href="<?= e(url('index.php')) ?>">Home</a> >
                    <span>Attendance</span>
                </p>
            </div>
        </section>
        <section class="page-title">
            <div class="container">
                <h1>Attendance</h1>
                <p>
                    Track your attendance across all subjects.
                </p>
            </div>
        </section>
        <section class="quick-access">
            <div class="container">
                <table class="attendance-table">
                    <thead>
                        <tr>
                            <th>Subject</th>
                            <th>Classes Held</th>
                            <th>Classes Attended</th>
                            <th>Attendance</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td>Web Development</td>
                            <td>40</td>
                            <td>38</td>
                            <td>95%</td>
                        </tr>
                        <tr>
                            <td>Data Structures</td>
                            <td>36</td>
                            <td>33</td>
                            <td>92%</td>
                        </tr>
                        <tr>
                            <td>Database Management</td>
                            <td>35</td>
                            <td>31</td>
                            <td>89%</td>
                        </tr>
                        <tr>
                            <td>Computer Networks</td>
                            <td>34</td>
                            <td>32</td>
                            <td>94%</td>
                        </tr>
                        <tr>
                            <td>Operating Systems</td>
                            <td>30</td>
                            <td>27</td>
                            <td>90%</td>
                        </tr>
                        <tr>
                            <td>Discrete Mathematics</td>
                            <td>32</td>
                            <td>30</td>
                            <td>94%</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </section>
    </main>
<?php require ROOT_PATH . '/includes/footer.php';
