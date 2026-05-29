<?php
declare(strict_types=1);

use App\Services\EnvelopeRenderService;

/** @var array<string, mixed> $layout */
/** @var string $template_name */
/** @var int $registro_id */
/** @var list<array<string, mixed>> $render_cells */
/** @var EnvelopeRenderService $envelopeRender */

$widthMm  = (float) ($layout['width_mm'] ?? 220);
$heightMm = (float) ($layout['height_mm'] ?? 110);
$cols     = max(1, (int) ($layout['columns'] ?? 4));
$rows     = max(1, (int) ($layout['rows'] ?? 3));
$editorCssRel = 'css/envelope-editor.css';
$editorCssFs  = FCPATH . str_replace('/', DIRECTORY_SEPARATOR, $editorCssRel);
$editorCssVer = is_file($editorCssFs) ? (int) filemtime($editorCssFs) : (int) time();
$printCssRel  = 'css/envelope-print.css';
$printCssFs   = FCPATH . str_replace('/', DIRECTORY_SEPARATOR, $printCssRel);
$printCssVer  = is_file($printCssFs) ? (int) filemtime($printCssFs) : (int) time();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Imprimir sobre — <?= esc($template_name) ?></title>
    <link rel="stylesheet" href="<?= base_url($editorCssRel) ?>?v=<?= $editorCssVer ?>">
    <link rel="stylesheet" href="<?= base_url($printCssRel) ?>?v=<?= $printCssVer ?>">
    <style>
        @page {
            size: <?= esc((string) $widthMm) ?>mm <?= esc((string) $heightMm) ?>mm;
            margin: 0;
        }
        .envelope-print-stage .envelope-size-preview {
            width: <?= esc((string) $widthMm) ?>mm;
            height: <?= esc((string) $heightMm) ?>mm;
        }
        .envelope-print-stage .envelope-preview-inner {
            display: grid;
            grid-template-columns: repeat(<?= (int) $cols ?>, 1fr);
            grid-template-rows: repeat(<?= (int) $rows ?>, 1fr);
            align-content: stretch;
        }
    </style>
</head>
<body class="envelope-print-body">
<div class="envelope-print-toolbar no-print">
    <button type="button" class="envelope-print-btn envelope-print-btn-primary" id="btn_envelope_reprint">Imprimir de nuevo</button>
    <a href="<?= esc(site_url('registers/viewreport/' . $registro_id), 'attr') ?>" class="envelope-print-btn envelope-print-btn-secondary">Volver al reporte</a>
</div>

<div class="envelope-print-stage">
    <?php if (empty($render_cells)): ?>
    <div class="alert alert-warning envelope-print-empty no-print">
        <strong>La plantilla no tiene campos visibles.</strong>
        <p class="mb-0 small">Edite la plantilla en Configuración → Sobres, coloque campos en la matriz y guarde.</p>
    </div>
    <?php else: ?>
    <div class="envelope-editor-wrap envelope-print-wrap">
        <div class="envelope-size-preview mx-auto">
            <div class="envelope-preview-inner">
            <?php foreach ($render_cells as $cell): ?>
                <?= $envelopeRender->renderCellMarkup($cell) ?>
            <?php endforeach; ?>
            </div>
        </div>
    </div>
    <?php endif; ?>
</div>
<script>
(function () {
    var printScheduled = false;
    var params = new URLSearchParams(window.location.search);
    var autoPrint = params.get('auto') === '1';

    function openPrintDialog() {
        window.print();
    }

    var btnReprint = document.getElementById('btn_envelope_reprint');
    if (btnReprint) {
        btnReprint.addEventListener('click', openPrintDialog);
    }

    function scheduleAutoPrint() {
        if (printScheduled || !autoPrint) {
            return;
        }
        printScheduled = true;
        requestAnimationFrame(function () {
            setTimeout(openPrintDialog, 150);
        });
    }

    window.addEventListener('beforeprint', function () {
        document.body.classList.add('envelope-printing');
    });
    window.addEventListener('afterprint', function () {
        document.body.classList.remove('envelope-printing');
        if (window.opener && !window.opener.closed) {
            setTimeout(function () {
                window.close();
            }, 120);
        }
    });

    function boot() {
        scheduleAutoPrint();
    }

    if (document.readyState === 'complete') {
        boot();
    } else {
        window.addEventListener('load', boot);
    }
})();
</script>
</body>
</html>
