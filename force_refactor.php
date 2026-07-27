<?php
$dashboard = file_get_contents('c:\\xampp_new\\htdocs\\Final 2.0\\present\\student_dashboard.php');
$new_block = file_get_contents('c:\\xampp_new\\htdocs\\Final 2.0\\present\\new_assignments_block.php');

// Find the start of the subjects loop
$start_marker = "<!-- Start of Subjects Loop -->";
$end_marker = "<!-- End of Subjects Loop -->";

$start_pos = strpos($dashboard, $start_marker);
$end_pos = strpos($dashboard, $end_marker);

if ($start_pos !== false && $end_pos !== false) {
    // Both markers exist! Just replace between them.
    $before = substr($dashboard, 0, $start_pos + strlen($start_marker) . "\n");
    $after = substr($dashboard, $end_pos);
    
    $dashboard = $before . $new_block . "\n" . $after;
    file_put_contents('c:\\xampp_new\\htdocs\\Final 2.0\\present\\student_dashboard.php', $dashboard);
    echo "Fixed using markers.\n";
} else {
    // Markers are missing! Let's find the table headers
    $start_pattern = '/<thead>\s*<tr>\s*<th[^>]*>#<\/th>\s*<th[^>]*>Subject<\/th>\s*<\/tr>\s*<\/thead>\s*<tbody>/s';
    
    if (preg_match($start_pattern, $dashboard, $matches, PREG_OFFSET_CAPTURE)) {
        $start_idx = $matches[0][1] + strlen($matches[0][0]);
        
        // Find the end of this table body (the main one)
        // Since there are multiple nested tbodys, we need to be careful.
        // We know it ends right before <!-- ============================================ --> for LEAVE REQUESTS
        $end_pattern = '/<\/tbody>\s*<\/table>\s*<\/div>\s*<\/div>\s*<!-- ============================================ -->\s*<!-- 3. LEAVE REQUESTS PAGE/s';
        if (preg_match($end_pattern, $dashboard, $end_matches, PREG_OFFSET_CAPTURE)) {
            $end_idx = $end_matches[0][1];
            
            $before = substr($dashboard, 0, $start_idx);
            $after = substr($dashboard, $end_idx);
            
            file_put_contents('c:\\xampp_new\\htdocs\\Final 2.0\\present\\student_dashboard.php', $before . "\n" . $new_block . "\n" . $after);
            echo "Fixed using regex fallback.\n";
        } else {
            echo "Could not find end of table.\n";
        }
    } else {
        echo "Could not find start of table.\n";
    }
}
?>
