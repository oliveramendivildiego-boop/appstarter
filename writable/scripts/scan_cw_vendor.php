<?php
declare(strict_types=1);
$root = dirname(__DIR__, 2) . '/vendor/mpdf';
$it = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($root));
foreach ($it as $f) {
    if (! $f->isFile() || $f->getExtension() !== 'php') {
        continue;
    }
    $c = file_get_contents($f->getPathname());
    if ($c !== false && preg_match('/\$cw\b/', $c)) {
        echo $f->getPathname() . PHP_EOL;
    }
}
