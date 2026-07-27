<?php
require 'db.php';
$db = get_db();

// Mock current student
$current_student = null;
foreach ($db['students'] as $s) {
    if ($s['student_name'] == 'SHEVARE AJAY VINOD' || strpos($s['name'] ?? '', 'SHEVARE') !== false || $s['id'] === '125UIT1080') {
        $current_student = $s;
        break;
    }
}
if (!$current_student) {
    // maybe 'username' == '125UIT1080'
    foreach ($db['students'] as $s) {
        if (($s['username'] ?? '') == '125UIT1080') {
            $current_student = $s;
            break;
        }
    }
}

var_dump($current_student['department'] ?? 'Not found');

$student_dept = $current_student['department'] ?? '';
$my_dept_id = null;
if (isset($db['departments'])) {
    foreach ($db['departments'] as $d) {
        if (strcasecmp($d['name'], $student_dept) === 0) {
            $my_dept_id = intval(str_replace('dept_', '', $d['id']));
            break;
        }
    }
}
var_dump($my_dept_id);

$my_subjects = [];
if (isset($db['subjects'])) {
    foreach ($db['subjects'] as $sub) {
        if ($my_dept_id !== null && intval($sub['department_id']) === $my_dept_id) {
            $my_subjects[] = $sub;
        }
    }
}
var_dump(count($my_subjects));
?>
