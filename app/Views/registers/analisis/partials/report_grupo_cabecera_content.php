<?php

/**
 * Título, tipo de muestra y método de la cabecera de grupo (.report-pdf-grupo-cabecera).
 *
 * @var string $padre
 * @var string $hijo
 * @var string $tipo_muestra_linea
 * @var string $metodo_linea
 * @var string $variant 'web' | 'pdf' | 'screen_pdf' | 'browser_print'
 * @var array<string,mixed>|null $pdf_layout
 * @var string $web_title_mt Clase margin-top para vista web (p. ej. mt-4, mt-5)
 * @var int    $sub_idx Índice del subgrupo dentro del área (0 = primera prueba)
 * @var bool   $grupo_es_primero Si es el primer área/grupo del reporte
 */

$variant = $variant ?? 'web';

$usePdfChrome = in_array($variant, ['pdf', 'screen_pdf', 'browser_print'], true);

$pdfLayout = is_array($pdf_layout ?? null) ? $pdf_layout : [];

$tituloGrupo = \App\Services\ReportPdfLayoutService::buildGrupoPruebaCabeceraTitle(
    (string) ($padre ?? ''),
    (string) ($hijo ?? ''),
    $pdfLayout
);

$tipoMuestraLinea = trim((string) ($tipo_muestra_linea ?? ''));

$metodoLinea = trim((string) ($metodo_linea ?? ''));

$mostrarTipoMuestra = \App\Services\ReportPdfLayoutService::grupoCabeceraMostrarTipoMuestra($pdfLayout, $tipoMuestraLinea);

$mostrarMetodo = \App\Services\ReportPdfLayoutService::grupoCabeceraMostrarMetodo($pdfLayout, $metodoLinea);

$isFirstSubgrupoInArea = ((int) ($sub_idx ?? 0)) === 0;

$isFirstGrupoInReport = ! empty($grupo_es_primero);

$hasTitle = $tituloGrupo !== '';

$lastLine = $mostrarMetodo ? 'metodo' : ($mostrarTipoMuestra ? 'tipo' : ($hasTitle ? 'title' : ''));

?>

<?php if ($hasTitle): ?>

<?php if ($usePdfChrome): ?>
<div class="group-title report-pdf-grupo-cabecera-line report-pdf-grupo-cabecera-line--title<?= $lastLine === 'title' ? ' report-pdf-grupo-cabecera-line--last' : '' ?>" style="<?= esc(\App\Services\ReportPdfLayoutService::groupTitleStyleAttr(
    $pdfLayout,
    $lastLine === 'title',
    $isFirstSubgrupoInArea,
    $isFirstGrupoInReport
), 'attr') ?>"><?= esc($tituloGrupo) ?></div>
<?php else: ?>
<h4 style="<?= esc(\App\Services\ReportPdfLayoutService::groupTitleStyleAttr(
    $pdfLayout,
    $lastLine === 'title',
    $isFirstSubgrupoInArea,
    $isFirstGrupoInReport
), 'attr') ?>"><?= esc($tituloGrupo) ?></h4>
<?php endif; ?>

<?php endif; ?>

<?php if ($mostrarTipoMuestra): ?>

<?php if ($usePdfChrome): ?>
<div class="report-tipo-muestra report-pdf-grupo-cabecera-line report-pdf-grupo-cabecera-line--tipo<?= $lastLine === 'tipo' ? ' report-pdf-grupo-cabecera-line--last' : '' ?>" style="<?= esc(\App\Services\ReportPdfLayoutService::grupoCabeceraTipoMuestraStyleAttr(
    $pdfLayout,
    $lastLine === 'tipo',
    $isFirstSubgrupoInArea,
    $isFirstGrupoInReport
), 'attr') ?>">Tipo de Muestra: <?= esc($tipoMuestraLinea) ?></div>
<?php else: ?>
<p class="small text-muted mb-0" style="<?= esc(\App\Services\ReportPdfLayoutService::grupoCabeceraTipoMuestraStyleAttr(
    $pdfLayout,
    $lastLine === 'tipo',
    $isFirstSubgrupoInArea,
    $isFirstGrupoInReport
), 'attr') ?>">Tipo de Muestra: <?= esc($tipoMuestraLinea) ?></p>
<?php endif; ?>

<?php endif; ?>

<?php if ($mostrarMetodo): ?>

<?php if ($usePdfChrome): ?>
<div class="report-metodo-prueba report-pdf-grupo-cabecera-line report-pdf-grupo-cabecera-line--metodo report-pdf-grupo-cabecera-line--last" style="<?= esc(\App\Services\ReportPdfLayoutService::grupoCabeceraMetodoStyleAttr(
    $pdfLayout,
    true,
    $isFirstSubgrupoInArea,
    $isFirstGrupoInReport
), 'attr') ?>">Método: <?= esc($metodoLinea) ?></div>
<?php else: ?>
<p class="small text-muted mb-0" style="<?= esc(\App\Services\ReportPdfLayoutService::grupoCabeceraMetodoStyleAttr(
    $pdfLayout,
    true,
    $isFirstSubgrupoInArea,
    $isFirstGrupoInReport
), 'attr') ?>">Método: <?= esc($metodoLinea) ?></p>
<?php endif; ?>

<?php endif; ?>

