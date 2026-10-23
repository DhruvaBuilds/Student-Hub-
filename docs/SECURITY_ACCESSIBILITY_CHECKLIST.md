# Security & Accessibility Checklist

Tick each box after you have verified it in your own copy (✔ = implemented in the code, verify by testing).

## Security
- [x] Passwords stored with `password_hash()` (bcrypt) and checked with `password_verify()`
- [x] All SQL uses prepared statements (`db_query` / `db_all` / `db_exec`) – no string-built SQL with user input
- [x] Output escaped with `e()` (PHP) and `textContent` (JS) – XSS protection
- [x] CSRF token on every POST form and on all state-changing API calls (`X-CSRF-Token`)
- [x] `session_regenerate_id(true)` after login; session cookie `HttpOnly`, `SameSite=Lax`, `Secure` on HTTPS
- [x] Session idle timeout (15 min) and secure logout (session destroyed, remember token deleted)
- [x] Remember-me uses random selector + validator; only SHA-256 hash stored; token rotated on use
- [x] Login throttling and identical error message for unknown e-mail / wrong password
- [x] Role-based access (`require_role`) on every admin page and API; unauthorised attempts audit-logged
- [x] Deactivated users are blocked at login and on every protected request
- [x] File upload: size limit, extension check, real MIME check (`finfo`), `getimagesize`, random file name, PHP execution blocked in `uploads/`
- [x] `storage/`, `includes/`, `database/` blocked from the web with `.htaccess`
- [x] CSV export / storage protected against formula injection
- [x] Errors logged to `storage/php-errors.log`, never shown to visitors; API returns generic 500 message
- [x] Honeypot field on the contact form
- [ ] (Before public hosting) HTTPS, non-root DB user, delete `test_connection.php`

## Accessibility
- [x] Semantic landmarks: `header`, `nav` (labelled), `main`, `section`, `footer`
- [x] "Skip to main content" link, visible on keyboard focus
- [x] Every form control has a `<label>`; radio group uses `fieldset/legend`
- [x] Errors are next to the field, use `role="alert"`, `aria-invalid` and `aria-describedby`
- [x] Flash/status messages use `role="status"` / `aria-live`
- [x] Visible focus outline; hamburger and FAQ buttons use `aria-expanded`
- [x] Images have `alt` text; tables have `<caption>` (visually hidden) and `scope="col"`
- [x] Chart has a data table alternative
- [x] Works with keyboard only (modal closes with Escape, dialog uses native `<dialog>`)
- [x] Light/dark theme; responsive from mobile to desktop
- [ ] Run Lighthouse / WAVE on Home, Register and Dashboard and paste the scores here: ________
