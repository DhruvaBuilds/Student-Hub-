<?php
/**
 * Practical 6 + 13 – events page.
 * The list is fetched with the Fetch API from api/events.php (JSON), then
 * searched / filtered / sorted / paginated in the browser (js/events.js).
 */
require_once __DIR__ . '/../includes/bootstrap.php';

$canRegister = is_logged_in() && !is_admin();

$pageTitle = 'Events';
$activeNav = 'events';
require ROOT_PATH . '/includes/header.php';
?>
<main id="main-content">

    <section class="breadcrumb">
        <div class="container">
            <p><a href="<?= e(url('index.php')) ?>">Home</a> &gt; <span>Events</span></p>
        </div>
    </section>

    <section class="page-title">
        <div class="container">
            <h1>College Events</h1>
            <p>Upcoming college events and activities.</p>
        </div>
    </section>

    <section class="quick-access">
        <div class="container">

            <div class="event-controls">
                <label class="visually-hidden" for="event-search">Search events</label>
                <input type="search" id="event-search" placeholder="Search events...">

                <label class="visually-hidden" for="event-filter">Filter by category</label>
                <select id="event-filter">
                    <option value="all">All Categories</option>
                    <?php foreach (EVENT_CATEGORIES as $cat): ?>
                        <option value="<?= e($cat) ?>"><?= e($cat) ?></option>
                    <?php endforeach; ?>
                </select>

                <label class="visually-hidden" for="event-sort">Sort events</label>
                <select id="event-sort">
                    <option value="date-asc">Date: Oldest First</option>
                    <option value="date-desc">Date: Newest First</option>
                    <option value="title-asc">Title: A - Z</option>
                    <option value="title-desc">Title: Z - A</option>
                </select>
            </div>

            <div id="events-status" class="events-status" role="status" aria-live="polite"></div>

            <div id="events-container" class="card-container"
                 data-source="<?= e(url('api/events.php?action=list')) ?>"
                 data-fallback="<?= e(url('data/events.json')) ?>"
                 data-upload-base="<?= e(url('uploads/posters/')) ?>"
                 data-can-register="<?= $canRegister ? '1' : '0' ?>"
                 data-logged-in="<?= is_logged_in() ? '1' : '0' ?>"
                 data-login-url="<?= e(url('pages/login.php')) ?>">
                <p id="loading">Loading events...</p>
            </div>

            <nav id="events-pagination" class="pagination js-pagination" aria-label="Events pagination"></nav>

        </div>
    </section>

</main>
<?php
$extraScripts = ['js/events.js'];
require ROOT_PATH . '/includes/footer.php';
