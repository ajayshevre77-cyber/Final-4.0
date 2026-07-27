<?php
$dashboard = file_get_contents('c:\\xampp_new\\htdocs\\Final 2.0\\present\\student_dashboard.php');
$new_block = file_get_contents('c:\\xampp_new\\htdocs\\Final 2.0\\present\\new_assignments_block.php');

// We want to replace everything from <tbody> after <th>Assignment Units</th> up to </tbody>
$pattern = '/(<th>Assignment Units<\/th>\s*<\/tr>\s*<\/thead>\s*<tbody>).*?(<\/tbody>)/s';

$dashboard = preg_replace($pattern, "$1\n" . $new_block . "\n$2", $dashboard, 1, $count);

if ($count > 0) {
    file_put_contents('c:\\xampp_new\\htdocs\\Final 2.0\\present\\student_dashboard.php', $dashboard);
    echo "Refactoring successful.\n";
} else {
    echo "Could not find the block in student_dashboard.php using regex.\n";
}
?>
