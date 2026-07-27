<?php
require 'db.php';
$db = get_db();
foreach ($db['students'] as $s) {
    if ($s['student_name'] == 'SHEVARE AJAY VINOD') {
        var_dump($s['department']);
        break;
    }
}
var_dump(array_column($db['departments'], 'name'));
var_dump($db['subjects']);
?>
