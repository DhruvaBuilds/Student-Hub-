# StudentHub – Student Portal

Semester project for **Web Development Frameworks (ITUE203)**, CHARUSAT FTE, Semester 3 (2026-27).
A full-stack student portal built with HTML5, CSS3, JavaScript (ES6), PHP 8, MySQL and Apache (XAMPP).

## Features

| Area | What it does |
|------|--------------|
| Public site | Home, Events (search / filter / sort / pagination), Contact (+FAQ), Register, Login |
| Student area | Dashboard, Courses, Attendance, Assignments, Results, Profile, event registration |
| Admin area | Dashboard with live statistics + chart, Students CRUD, Events CRUD with poster upload, Users (activate/deactivate), Audit log, Contact messages |
| Security | `password_hash`/`password_verify`, prepared statements, CSRF tokens, output escaping, secure sessions, role-based access, session timeout, remember-me token (hashed), upload validation |
| UX | Responsive layout, light/dark theme (saved), hamburger menu, accessible forms, skip-link, ARIA live messages |

## Quick start (XAMPP)

1. Copy this folder to `C:\xampp\htdocs\StudentHub` (any folder name works – the base URL is auto-detected).
2. Start **Apache** and **MySQL** in the XAMPP control panel.
3. Open **phpMyAdmin** → *Import* → choose `database/studentHub.sql` → *Go*. (This creates the `studenthub` database with sample data.)
4. If your MySQL password is not empty, edit `includes/config.php` (`DB_USER`, `DB_PASS`).
5. Make sure `storage/` and `uploads/posters/` are writable (they are by default on XAMPP/Windows; on Linux: `chmod -R 775 storage uploads`).
6. Open <http://localhost/StudentHub/> – and <http://localhost/StudentHub/test_connection.php> to verify the database connection.

Requires PHP 8.0+ with the `mysqli`, `pdo_mysql`, `fileinfo` and `mbstring` extensions (all enabled in XAMPP by default).

### Demo accounts

| Role | Email | Password |
|------|-------|----------|
| Admin | `admin@studenthub.edu` | `Admin@123` |
| Student | `rahul.sharma@email.com` | `Student@123` |
| Student | `priya.patel@email.com` | `Student@123` |
| Inactive (cannot log in) | `inactive.student@email.com` | `Student@123` |

## Project structure

```
StudentHub/
├── index.php                 Home page
├── test_connection.php       DB connection test (localhost only)
├── pages/                    Public + student pages (login, register, contact, dashboard, events, ...)
├── admin/                    Admin pages (dashboard, students, events, users, audit log, messages)
├── api/                      JSON API (students.php, events.php) + shared bootstrap.php
├── includes/                 config, db (MySQLi + PDO), auth, helpers, repositories, header/footer
├── css/style.css             Single stylesheet (CSS variables, Grid/Flexbox, dark mode)
├── js/                       script.js, validation.js, events.js, students-ajax.js, admin-dashboard.js
├── data/                     events.json, students.json, faqs.json (Practical 6 sample data)
├── database/studentHub.sql   Schema + seed data (+ stored procedure)
├── storage/                  contacts.json / contacts.csv written by the Contact form (not web-accessible)
├── uploads/posters/          Uploaded event posters (PHP execution blocked)
├── docs/                     ER diagram, API routes, test report, checklists, deployment guide
└── images/
```

## Practical → file map

| # | Practical | Main files |
|---|-----------|-----------|
| 1 | Requirements, sitemap, wireframe, GitHub | `Requirement Analysis.docx`, `StudentHub_SiteMap.jpg`, `Wireframe.jpg`, this README |
| 2 | Semantic HTML5 + accessibility | `includes/header.php`, `pages/*.php` |
| 3 | Responsive UI | `css/style.css` |
| 4 | DOM / events / UI | `js/script.js` |
| 5 | Registration form + JS validation | `pages/register.php`, `js/validation.js` |
| 6 | Fetch API + JSON, search/filter/sort/pagination | `js/events.js`, `data/*.json` |
| 7 | PHP form processing, CSV/JSON storage, CSRF | `pages/contact.php`, `includes/helpers.php`, `admin/contact_messages.php` |
| 8 | MySQL schema, ER model, MySQLi + PDO | `database/studentHub.sql`, `includes/db.php`, `docs/ER_DIAGRAM.md`, `test_connection.php` |
| 9 | Secure registration | `pages/register.php` |
| 10 | Login/logout, sessions, RBAC, timeout, remember-me | `pages/login.php`, `pages/logout.php`, `includes/auth.php` |
| 11 | Student CRUD, search, filter, export, soft delete | `admin/students.php`, `admin/student_form.php`, `admin/students_export.php`, `includes/students_repo.php` |
| 12 | Event CRUD + poster upload | `admin/events.php`, `admin/event_form.php`, `includes/events_repo.php` |
| 13 | JSON API + AJAX CRUD | `api/*.php`, `admin/students_ajax.php`, `js/students-ajax.js` |
| 14 | Admin dashboard, RBAC menu, audit log | `admin/dashboard.php`, `admin/audit_logs.php`, `admin/users.php` |
| 15 | Integration, testing, docs, deployment | `docs/`, this README |

## Documentation

* [ER diagram](docs/ER_DIAGRAM.md)
* [API routes](docs/API_ROUTES.md)
* [Test report (test cases to execute)](docs/TEST_REPORT.md)
* [Security & accessibility checklist](docs/SECURITY_ACCESSIBILITY_CHECKLIST.md)
* [Deployment guide (XAMPP + virtual host)](docs/DEPLOYMENT.md)

## Troubleshooting

* **"Database connection failed"** – start MySQL, import `database/studentHub.sql`, check `includes/config.php`.
* **Styles / links broken** – open the site through `http://localhost/...`, not by double-clicking a file.
* **Poster upload fails** – check that `uploads/posters/` is writable and `upload_max_filesize` ≥ 2M in `php.ini`.
* **A 500 error after copying `.htaccess` files** – Apache needs `AllowOverride All` for the htdocs folder (XAMPP default).
* PHP errors are written to `storage/php-errors.log`.
