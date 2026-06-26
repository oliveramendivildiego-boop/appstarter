<?php
declare(strict_types=1);

require dirname(__DIR__, 2) . '/vendor/autoload.php';

use App\Libraries\Pdf\DompdfPdfRenderer;
use App\Services\RegisterService;
use App\Models\RegisterModel;
use App\Models\AppConfigModel;

$_SERVER['CI_ENVIRONMENT'] = 'development';
define('ENVIRONMENT', 'development');
define('FCPATH', dirname(__DIR__, 2) . DIRECTORY_SEPARATOR . 'public' . DIRECTORY_SEPARATOR);
chdir(FCPATH);
require FCPATH . '../app/Config/Paths.php';
$paths = new Config\Paths();
require $paths->systemDirectory . '/Boot.php';
CodeIgniter\Boot::bootConsole($paths);
helper('qr');

$registroId = (int) ($argv[1] ?? 308);
$rs = new App\Services\RegisterService(new App\Models\RegisterModel(), new App\Models\AppConfigModel());
$data = $rs->prepareReportData($registroId);
$html = $rs->renderReportPdfHtml($data, 'http://x', qr_base64('http://x', 120));

$renderer = new DompdfPdfRenderer();
$ref = new ReflectionClass($renderer);
$extract = $ref->getMethod('extractPaginationSlots');
$extract->setAccessible(true);
$slots = $extract->invoke($renderer, $html);
$slot = $slots[0] ?? null;

$paintY = $ref->getMethod('paginationFooterBaselineY');
$paintY->setAccessible(true);
$metrics = $ref->getMethod('paginationFooterCellMetrics');
$metrics->setAccessible(true);

$pageW = 612.0;
$pageH = 792.0;
$mmToPt = 72 / 25.4;
$ml = (float) ($slot['ml'] ?? 0) * $mmToPt;
$mr = (float) ($slot['mr'] ?? 0) * $mmToPt;

$y = $paintY->invoke($renderer, $slot, $pageH, (float) ($slot['fontSize'] ?? 10), 1, (float) ($slot['mb'] ?? 0) * $mmToPt, $mmToPt);
[$x0, $cw, $pad] = $metrics->invoke($renderer, $slot, $pageW, $ml, $mr, 72.0 / 96.0);

echo "slot ml={$slot['ml']} mr={$slot['mr']} col={$slot['gridColumn']} span={$slot['gridColumnSpan']}\n";
echo "computed Y page1: $y (pageH=$pageH)\n";
echo "cellX0=$x0 cellW=$cw pad=$pad\n";
echo "footerTopY=" . ($pageH - (float)$slot['mb']*$mmToPt - (float)$slot['footerReserveMm']*$mmToPt) . "\n";

// content-box metrics (no ml offset)
$cols = max(1, (int) $slot['footerColumns']);
$colW = $pageW / $cols;
$cbX0 = (int) $slot['gridColumn'] * $colW;
echo "content-box cellX0=$cbX0 cellW=" . ($colW * (int)$slot['gridColumnSpan']) . "\n";

// with padding restored on footer
$padMl = (float) $slot['ml'] * $mmToPt;
$contentW = $pageW - $padMl - (float)$slot['mr'] * $mmToPt;
$colW2 = $contentW / $cols;
$padX0 = $padMl + (int)$slot['gridColumn'] * $colW2;
echo "padded-footer cellX0=$padX0 cellW=" . ($colW2 * (int)$slot['gridColumnSpan']) . "\n";
