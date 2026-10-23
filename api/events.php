<?php
/**
 * Practical 13 – Events JSON API.
 *
 *   GET  api/events.php?action=list                 public – all events (+ is_registered for logged-in students)
 *   POST api/events.php {"action":"register","event_id":3}     student – register for an open event
 *   POST api/events.php {"action":"unregister","event_id":3}   student – cancel registration
 */
require_once __DIR__ . '/bootstrap.php';

$method = api_method(['GET', 'POST']);

// Student record of the logged-in user (null for guests / admins)
function current_student_id(): ?int
{
    if (!is_logged_in() || is_admin()) {
        return null;
    }
    $id = db_value('SELECT id FROM students WHERE user_id = ? AND status <> \'deleted\'', 'i', [current_user_id()]);
    return $id ? (int) $id : null;
}

if ($method === 'GET') {
    if (get('action', 'list') !== 'list') {
        api_error(400, 'Unknown action.');
    }
    $studentId = current_student_id() ?? 0;

    $rows = db_all(
        'SELECT e.id, e.title, e.description, e.event_date AS date, e.location, e.category, e.status, e.poster,
                (SELECT COUNT(*) FROM registrations r WHERE r.event_id = e.id) AS registrations,
                (SELECT COUNT(*) FROM registrations r WHERE r.event_id = e.id AND r.student_id = ?) AS mine
           FROM events e ORDER BY e.event_date ASC, e.id ASC',
        'i',
        [$studentId]
    );
    foreach ($rows as &$r) {
        $r['id']            = (int) $r['id'];
        $r['registrations'] = (int) $r['registrations'];
        $r['is_registered'] = ((int) $r['mine']) > 0;
        unset($r['mine']);
    }
    unset($r);
    api_success($rows);
}

// ---------------- POST ----------------
api_require_login();
api_require_csrf();
$body    = api_body();
$action  = (string) ($body['action'] ?? '');
$eventId = api_int($body['event_id'] ?? 0);

if (!in_array($action, ['register', 'unregister'], true)) {
    api_error(400, 'Unknown action. Use register or unregister.');
}
if (!$eventId) {
    api_error(400, 'A valid event id is required.');
}
$studentId = current_student_id();
if ($studentId === null) {
    api_error(403, 'Only student accounts can register for events.');
}
$event = db_one('SELECT id, title, status FROM events WHERE id = ?', 'i', [$eventId]);
if (!$event) {
    api_error(404, 'Event not found.');
}

if ($action === 'register') {
    if ($event['status'] !== 'open') {
        api_error(409, 'Registration is closed for this event.');
    }
    db_exec('INSERT IGNORE INTO registrations (student_id, event_id) VALUES (?, ?)', 'ii', [$studentId, $eventId]);
    audit_log('event_register', "Registered for event #$eventId {$event['title']}");
    api_success(['event_id' => $eventId, 'is_registered' => true], 'You are registered for this event.', 201);
}

db_exec('DELETE FROM registrations WHERE student_id = ? AND event_id = ?', 'ii', [$studentId, $eventId]);
audit_log('event_unregister', "Cancelled registration for event #$eventId");
api_success(['event_id' => $eventId, 'is_registered' => false], 'Your registration was cancelled.');
