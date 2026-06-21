<?php
declare(strict_types=1);
$f = $argv[1] ?? 'writable/debug/validate_253_flow-continuous-signature-last-page.pdf';
$c = file_get_contents($f);
preg_match_all('/\/Type\s*\/Page[^s]/', $c, $m);
echo basename($f) . ' pages=' . count($m[0]) . PHP_EOL;
