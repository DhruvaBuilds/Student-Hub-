<?php
/** Practical 11 (intermediate) – export the current student list (with filters) as CSV. */
require_once __DIR__ . '/../includes/bootstrap.php';
require_once __DIR__ . '/../includes/students_repo.php';
require_role('admin');

$filters = student_filters($_GET);
$rows    = student_search($filters, 100000, 0);

audit_log('student_export', 'Exported ' . count($rows) . ' students to CSV');

header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename="students_' . date('Ymd_His') . '.csv"');

$out = fopen('php://output', 'w');
fwrite($out, "\xEF\xBB\xBF");   // UTF-8 BOM so Excel shows accents correctly
fputcsv($out, ['Student ID', 'Name', 'Email', 'Phone', 'Course', 'Year', 'Gender', 'Status', 'Created'], ',', '"', '\\');
foreach ($rows as $r) {
    fputcsv($out, array_map('csv_safe', [
        $r['student_code'], $r['name'], $r['email'], $r['phone'], $r['course'],
        (string) $r['year'], $r['gender'], $r['status'], $r['created_at'],
    ]), ',', '"', '\\');
}
fclose($out);
