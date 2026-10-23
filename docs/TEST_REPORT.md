# Test Report – StudentHub

**How to use:** run each case in the browser, fill the *Actual / Result* column and paste screenshots
into your submission. Cases are grouped by practical so you can attach them to the right lab file.
Test date: ____________  Tester: ____________  Browser: ____________

## P5 / P9 – Registration

| # | Input | Expected | Actual / Result |
|---|-------|----------|-----------------|
| R1 | All fields valid, new e-mail | Redirect to login, green "Registration successful" | |
| R2 | Empty full name | "Please enter your full name." under the field | |
| R3 | Name `John123` | "Name should contain only letters and spaces." | |
| R4 | E-mail `abc@` | "Please enter a valid email address." | |
| R5 | Mobile `12345` / `5123456789` | "Please enter a valid 10-digit mobile number." | |
| R6 | Password `abc` | Strength meter "Very weak" + password rule error | |
| R7 | Password ≠ confirm | "Passwords do not match." | |
| R8 | Terms not ticked | "Please accept the Terms and Conditions." | |
| R9 | **Duplicate**: register `rahul.sharma@email.com` | "This email is already registered…" (server message) | |
| R10 | Disable JavaScript, submit invalid data | Same errors come from PHP (server-side validation) | |
| R11 | phpMyAdmin → `users.password_hash` | Starts with `$2y$`, never the plain password | |

## P7 – Contact form (file storage)

| # | Input | Expected | Actual / Result |
|---|-------|----------|-----------------|
| C1 | Valid name/e-mail/subject/message | Success message; new entry in `storage/contacts.json` and `storage/contacts.csv` | |
| C2 | Empty message | Error below the message field; nothing saved | |
| C3 | Message `<script>alert(1)</script>` | Saved as text; shown escaped on Admin → Messages (no alert) | |
| C4 | Open `http://localhost/StudentHub/storage/contacts.json` | 403 Forbidden | |
| C5 | Message starting with `=1+1` | CSV cell is prefixed with `'` | |

## P10 – Login / sessions

| # | Action | Expected | Actual / Result |
|---|--------|----------|-----------------|
| L1 | Login as admin | Lands on Admin Dashboard | |
| L2 | Login as student | Lands on Student Dashboard | |
| L3 | Wrong password | "Invalid email or password." | |
| L4 | 5 wrong passwords | "Too many failed attempts…" lock for 5 minutes | |
| L5 | Logout button | Back to login, "logged out" message, browser Back does not show dashboard data | |
| L6 | Open `pages/dashboard.php` logged out | Redirect to login | |
| L7 | Student opens `admin/dashboard.php` | 403 page; entry `unauthorized_access` in audit log | |
| L8 | Change `SESSION_TIMEOUT` to 20, wait 25 s, reload | Redirect to login with "session expired" | |
| L9 | Login with *Remember me*, delete the `STUDENTHUB_SESSID` cookie, reload | Still logged in (cookie `sh_remember`); DB row in `remember_tokens` holds a hash | |
| L10 | Login as `inactive.student@email.com` | "account has been deactivated" | |
| L11 | Check session cookie in DevTools | `HttpOnly` and `SameSite=Lax`; session ID changes after login | |

## P11 – Student CRUD

| # | Action | Expected | Actual / Result |
|---|--------|----------|-----------------|
| S1 | Add student with valid data | Success message, appears in list | |
| S2 | Add with existing e-mail | "Another student already uses this email." | |
| S3 | Edit a student | Values saved, success message | |
| S4 | Search `priya`, `@email.com`, `Computer` | Matching rows only | |
| S5 | Filter course + year + status | Combined filter works; pagination keeps filters | |
| S6 | Delete → Cancel in confirm box | Nothing happens | |
| S7 | Delete → OK | Row disappears; appears under Status = Deleted | |
| S8 | Restore / Remove forever | Restored / permanently removed | |
| S9 | Export CSV | File opens in Excel with the filtered rows | |
| S10 | Search `' OR 1=1 --` | No error, no extra rows (prepared statements) | |

## P12 – Event CRUD & poster upload

| # | Action | Expected | Actual / Result |
|---|--------|----------|-----------------|
| E1 | Add event with valid JPG < 2 MB | Saved; thumbnail visible in list and on public Events page | |
| E2 | Upload `.pdf` / `.txt` | "Only JPG, PNG or WEBP images are allowed." | |
| E3 | Rename `test.php` to `test.jpg`, upload | "not a valid JPG, PNG or WEBP image" (content is checked) | |
| E4 | Upload image > 2 MB | "The poster is too large…" | |
| E5 | Edit event, replace poster | Old file removed from `uploads/posters/` | |
| E6 | Close / Reopen button | Status badge changes; Register button disappears for closed events | |
| E7 | Delete event | Row, poster file and its registrations removed | |
| E8 | Browse to `uploads/posters/` | Directory listing denied | |

## P6 / P13 – Events page & AJAX

| # | Action | Expected | Actual / Result |
|---|--------|----------|-----------------|
| A1 | Open Events page, DevTools → Network | `api/events.php?action=list` returns JSON 200 | |
| A2 | Search / category / sort / page buttons | List updates without reload; 6 cards per page | |
| A3 | Student clicks Register | Button turns to "Registered", POST returns 201 | |
| A4 | Click again | Registration cancelled | |
| A5 | Guest opens Events | Sees "Login to register" links | |
| A6 | Stop Apache, reload Events | Cached list shown with offline notice | |
| A7 | Admin → Students → AJAX version: add / edit / delete / restore | Table updates with no page reload | |
| A8 | Type quickly in AJAX search | Only one request after 300 ms pause | |
| A9 | Send POST without `X-CSRF-Token` (console) | 403 JSON error | |
| A10 | Student calls `api/students.php?action=list` | 403 JSON error | |

## P14 – Dashboard & audit log

| # | Action | Expected | Actual / Result |
|---|--------|----------|-----------------|
| D1 | Compare dashboard counts with phpMyAdmin | Equal | |
| D2 | Add a registration, refresh | Latest registrations + chart change | |
| D3 | Users → Deactivate a student | That student can no longer log in; entry `user_status` logged | |
| D4 | Deactivate own account | Refused | |
| D5 | Audit log filter by action | Only matching rows | |
| D6 | Menu as guest / student / admin | Three different menus | |
