<?php
declare(strict_types=1);
error_reporting(E_ALL);
set_error_handler(static function (int $s, string $m): bool {
    throw new ErrorException($m, 0, $s);
});
$nCols = 5;
$colPct = 20;
$colW = 2;
$colClr = '#ddd';
$s = ".mpdf-ft-root table.mpdf-ft-table[data-pdf-cols=\"{$nCols}\"] > colgroup > col { width: {$colPct}%; }\n";
echo "line204: $s";
$s2 = "\n.mpdf-ft-root .mpdf-ft-cell + .mpdf-ft-cell,\n.pdf-ft-block.footer-grid.mpdf-ft-root .mpdf-ft-cell + .mpdf-ft-cell { border-left: {$colW}px solid {$colClr}; }\n";
echo "line196: $s2";
