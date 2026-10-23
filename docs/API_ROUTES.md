# API Routes (JSON)

All responses use `Content-Type: application/json` and this shape:

```json
{ "success": true,  "message": "OK", "data": { } }
{ "success": false, "message": "Why it failed", "errors": { "field": "message" } }
```

State-changing requests (POST) must send the header `X-CSRF-Token: <value of <meta name="csrf-token">>`
and a valid session cookie. Bodies are JSON (`Content-Type: application/json`).

## `api/students.php` – admin only

| Method | Request | Success | Errors |
|--------|---------|---------|--------|
| GET | `?action=list&q=&course=&year=&status=&page=1&per_page=10` | 200 `{items, pagination}` | 401, 403 |
| GET | `?action=get&id=5` | 200 student | 400, 404 |
| POST | `{"action":"create", name, email, phone, course, year, gender, status}` | **201** student | 403 (CSRF), 409 (duplicate), **422** (validation) |
| POST | `{"action":"update", "id":5, ...fields}` | 200 student | 404, 409, 422 |
| POST | `{"action":"delete", "id":5}` (soft delete) | 200 | 404 |
| POST | `{"action":"restore", "id":5}` | 200 | 404 |

## `api/events.php`

| Method | Request | Who | Success | Errors |
|--------|---------|-----|---------|--------|
| GET | `?action=list` | everyone | 200 array of events (`is_registered` for logged-in students) | – |
| POST | `{"action":"register","event_id":3}` | student | **201** | 401, 403, 404, 409 (event closed) |
| POST | `{"action":"unregister","event_id":3}` | student | 200 | 401, 403, 404 |

## Status codes used

200 OK · 201 Created · 400 Bad request · 401 Not logged in · 403 Forbidden / bad CSRF · 404 Not found ·
405 Method not allowed · 409 Conflict · 422 Validation failed · 500 Server error (logged, never leaked).

## Quick test (browser console on any page while logged in as admin)

```js
fetch('/StudentHub/api/students.php?action=list&per_page=3').then(r => r.json()).then(console.log)
```
