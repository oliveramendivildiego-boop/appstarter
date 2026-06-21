<?php
$h = file_get_contents(dirname(__DIR__) . '/debug/report_262.html');
echo 'len ' . strlen($h) . PHP_EOL;
echo 'entities ' . substr_count($h, '&#x') . PHP_EOL;
echo 'colon ent ' . substr_count($h, '&#x3A;') . PHP_EOL;
echo 'semicolon ent ' . substr_count($h, '&#x3B;') . PHP_EOL;
