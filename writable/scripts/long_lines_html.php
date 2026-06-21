<?php
foreach (file(dirname(__DIR__) . '/debug/report_262.html') as $n => $line) {
    $l = strlen($line);
    if ($l > 3000) {
        echo ($n + 1) . ': ' . round($l / 1024, 1) . " KB\n";
    }
}
