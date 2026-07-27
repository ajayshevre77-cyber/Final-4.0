<?php
$content = file_get_contents('new_assignments_block.php');

$replace1 = <<<EOD
                                            <?php 
                                            \$has_assignments = false;
                                            ob_start();
                                            foreach (\$units_list as \$unit_num): 
EOD;
$content = preg_replace('/<\?php\s*foreach\s*\(\$units_list\s*as\s*\$unit_num\):\s*/', $replace1 . "\n", $content);

$replace2 = <<<EOD
                                                if (!\$sa_item) continue;
                                                \$has_assignments = true;
                                                
                                                // Check submission status
EOD;
$content = str_replace('// Check submission status', $replace2, $content);

$replace3 = <<<EOD
                                            <?php endforeach; 
                                                \$rows_html = ob_get_clean();
                                                
                                                if (!\$has_assignments) {
                                                    echo '<tr><td colspan="5" style="padding: 2rem; text-align: center; color: #94a3b8; font-weight: 500; font-size: 0.95rem;"><i class="fa-solid fa-folder-open" style="font-size: 2rem; color: #e2e8f0; margin-bottom: 0.5rem; display: block;"></i> No assignments published by faculty yet.</td></tr>';
                                                } else {
                                                    echo \$rows_html;
                                                }
                                            ?>
EOD;
$content = preg_replace('/<\?php\s*endforeach;\s*\?>/', $replace3, $content);

file_put_contents('new_assignments_block.php', $content);
echo "Fixed.\n";
?>
