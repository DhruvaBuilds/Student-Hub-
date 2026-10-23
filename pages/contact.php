<?php
/**
 * Practical 7 – PHP form processing with server-side validation and CSV/JSON storage.
 *
 *  1. Form is submitted with POST.
 *  2. Every field is trimmed, validated and length-limited on the SERVER
 *     (JavaScript checks in js/validation.js are only a convenience).
 *  3. Output is escaped with e() when displayed – input is stored as typed,
 *     but never printed unescaped (prevents XSS).
 *  4. The record is appended to storage/contacts.json AND storage/contacts.csv
 *     using file locking (flock) so two visitors cannot corrupt the file.
 *  5. CSRF token (advanced) + hidden honeypot field to stop simple bots.
 *  6. Post/Redirect/Get: after success we redirect so refreshing does not re-submit.
 */
require_once __DIR__ . '/../includes/bootstrap.php';

$errors = [];
$old = ['name' => '', 'email' => '', 'subject' => '', 'message' => ''];

if (is_logged_in() && $_SERVER['REQUEST_METHOD'] !== 'POST') {
    $old['name']  = $_SESSION['user_name']  ?? '';
    $old['email'] = $_SESSION['user_email'] ?? '';
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $old['name']    = preg_replace('/\s+/', ' ', post('name'));
    $old['email']   = strtolower(post('email'));
    $old['subject'] = post('subject');
    $old['message'] = post('message');

    if (!csrf_verify($_POST['csrf_token'] ?? null)) {
        $errors['form'] = 'Your form expired. Please refresh the page and try again.';
    } elseif (post('website') !== '') {
        // Honeypot field is hidden for humans; bots fill it. Pretend success.
        flash_set('success', 'Thank you for contacting us! We will reply soon.');
        redirect('pages/contact.php');
    } else {
        if ($old['name'] === '')                    $errors['name'] = 'Please enter your name.';
        elseif (!valid_name($old['name']))          $errors['name'] = 'Name should contain only letters and spaces (2-100 characters).';

        if ($old['email'] === '')                   $errors['email'] = 'Please enter your email address.';
        elseif (!valid_email($old['email']))        $errors['email'] = 'Please enter a valid email address.';

        $subjectLen = mb_strlen($old['subject']);
        if ($subjectLen === 0)                      $errors['subject'] = 'Please enter a subject.';
        elseif ($subjectLen < 3 || $subjectLen > 120) $errors['subject'] = 'Subject must be 3 to 120 characters.';

        $messageLen = mb_strlen($old['message']);
        if ($messageLen === 0)                      $errors['message'] = 'Please write your message.';
        elseif ($messageLen < 10 || $messageLen > 1000) $errors['message'] = 'Message must be 10 to 1000 characters.';

        if (!$errors) {
            if (!is_dir(STORAGE_DIR) || !is_writable(STORAGE_DIR)) {
                $errors['form'] = 'Server storage is not writable. Please contact the administrator.';
            } else {
                $record = [
                    'name'       => $old['name'],
                    'email'      => $old['email'],
                    'subject'    => $old['subject'],
                    'message'    => $old['message'],
                    'ip'         => client_ip(),
                    'created_at' => date('Y-m-d H:i:s'),
                ];
                $savedJson = json_append(STORAGE_DIR . 'contacts.json', $record);
                $savedCsv  = csv_append(
                    STORAGE_DIR . 'contacts.csv',
                    ['name', 'email', 'subject', 'message', 'ip', 'created_at'],
                    array_values($record)
                );
                if ($savedJson && $savedCsv) {
                    audit_log('contact_submit', 'Contact message from ' . $old['email']);
                    flash_set('success', 'Thank you for contacting us! Your message has been saved.');
                    redirect('pages/contact.php');
                }
                $errors['form'] = 'Could not save your message. Please try again later.';
            }
        }
    }
}

$pageTitle = 'Contact';
$activeNav = 'contact';
require ROOT_PATH . '/includes/header.php';
?>
<main id="main-content">

    <section class="breadcrumb">
        <div class="container">
            <p><a href="<?= e(url('index.php')) ?>">Home</a> &gt; <span>Contact</span></p>
        </div>
    </section>

    <section class="page-title">
        <div class="container">
            <h1>Contact Us</h1>
            <p>We're here to help. Feel free to contact us.</p>
        </div>
    </section>

    <section class="quick-access">
        <div class="container">
            <div class="contact-container">

                <div class="contact-info">
                    <h2>College Information</h2>
                    <p><strong>Address:</strong> 123 College Road, New Delhi, India</p>
                    <p><strong>Email:</strong> info@studentportal.edu</p>
                    <p><strong>Phone:</strong> +91 98765 43210</p>
                    <p><strong>Office Hours:</strong> Monday - Friday, 9:00 AM - 5:00 PM</p>
                </div>

                <form class="form-card contact-form" method="post" action="" novalidate>
                    <?= csrf_field() ?>

                    <?php if (!empty($errors['form'])): ?>
                        <div class="alert alert-error" role="alert"><?= e($errors['form']) ?></div>
                    <?php endif; ?>

                    <!-- Honeypot: real users never see or fill this field -->
                    <div class="hp-field" aria-hidden="true">
                        <label for="website">Leave this field empty</label>
                        <input type="text" id="website" name="website" tabindex="-1" autocomplete="off">
                    </div>

                    <div class="form-group">
                        <label for="name">Full Name</label>
                        <input type="text" id="name" name="name" placeholder="Enter your full name"
                               value="<?= e($old['name']) ?>" required<?= field_attrs($errors, 'name') ?>>
                        <?= field_error($errors, 'name') ?>
                    </div>

                    <div class="form-group">
                        <label for="email">Email Address</label>
                        <input type="email" id="email" name="email" placeholder="Enter your email"
                               value="<?= e($old['email']) ?>" required<?= field_attrs($errors, 'email') ?>>
                        <?= field_error($errors, 'email') ?>
                    </div>

                    <div class="form-group">
                        <label for="subject">Subject</label>
                        <input type="text" id="subject" name="subject" placeholder="Enter subject"
                               value="<?= e($old['subject']) ?>" maxlength="120" required<?= field_attrs($errors, 'subject') ?>>
                        <?= field_error($errors, 'subject') ?>
                    </div>

                    <div class="form-group">
                        <label for="message">Message</label>
                        <textarea id="message" name="message" rows="5" maxlength="1000"
                                  placeholder="Write your message here..." required<?= field_attrs($errors, 'message') ?>><?= e($old['message']) ?></textarea>
                        <?= field_error($errors, 'message') ?>
                    </div>

                    <button type="submit" class="btn primary-btn">Send Message</button>
                </form>

            </div>
        </div>
    </section>

<!-- ================= FAQ ================= -->
        <section class="faq-section">
            <div class="container">
                <h2>Frequently Asked Questions</h2>
                <div class="faq-item">
                    <button class="faq-question">
                        How do I check my attendance?
                        <span>+</span>
                    </button>
                    <div class="faq-answer">
                        <p>
                            You can check your attendance by opening the
                            Attendance section from your dashboard.
                        </p>
                    </div>
                </div>
                <div class="faq-item">
                    <button class="faq-question">
                        Where can I see my results?
                        <span>+</span>
                    </button>
                    <div class="faq-answer">
                        <p>
                            Your latest academic results can be viewed
                            in the Results section of the Student Portal.
                        </p>
                    </div>
                </div>
                <div class="faq-item">
                    <button class="faq-question">
                        How do I submit an assignment?
                        <span>+</span>
                    </button>
                    <div class="faq-answer">
                        <p>
                            Open the Assignments section to view your
                            assignments and their submission status.
                        </p>
                    </div>
                </div>
                <div class="faq-item">
                    <button class="faq-question">
                        How can I contact the college?
                        <span>+</span>
                    </button>
                    <div class="faq-answer">
                        <p>
                            You can use the contact form above or contact
                            the college using the information provided.
                        </p>
                    </div>
                </div>
            </div>
        </section>

</main>
<?php
$extraScripts = ['js/validation.js'];
require ROOT_PATH . '/includes/footer.php';
