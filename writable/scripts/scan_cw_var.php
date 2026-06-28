<?php
declare(strict_types=1);
$root = dirname(__DIR__, 2);
$it = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($root . '/app'));
foreach ($it as $f) {
    if (! $f->isFile() || $f->getExtension() !== 'php') {
        continue;
    }
    $path = $f->getPathname();
    $c = file_get_contents($path);
    if ($c === false) {
        continue;
    }
    if (preg_match('/\$cw\b/', $c)) {
        echo $path . PHP_EOL;
    }
}
