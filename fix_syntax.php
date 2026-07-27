<?php
$content = file_get_contents('c:\\xampp_new\\htdocs\\Final 2.0\\present\\student_dashboard.php');

$bad_chunk = <<<EOD
                            <?php endforeach; endif; ?>

</tbody>
                                            </table>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; endif; ?>
EOD;

$good_chunk = <<<EOD
                            <?php endforeach; endif; ?>
EOD;

$content = str_replace($bad_chunk, $good_chunk, $content);
file_put_contents('c:\\xampp_new\\htdocs\\Final 2.0\\present\\student_dashboard.php', $content);
echo "Fixed.\n";
?>
