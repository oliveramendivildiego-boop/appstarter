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
$titleStyle = \App\Services\ReportPdfLayoutService::groupTitleMarginStyleAttr(
    $pdfLayout,
    $isFirstSubgrupoInArea,
    $isFirstGrupoInReport
);
$tipoStyle = \App\Services\ReportPdfLayoutService::grupoCabeceraTipoMuestraStyleAttr($pdfLayout);
$metodoStyle = \App\Services\ReportPdfLayoutService::grupoCabeceraMetodoStyleAttr($pdfLayout);
?>
<?php if ($usePdfChrome): ?>
<?php if ($tituloGrupo !== ''): ?>
<div class="group-title" style="<?= esc($titleStyle, 'attr') ?>"><?= esc($tituloGrupo) ?></div>
<?php endif; ?>
<?php if ($mostrarTipoMuestra): ?>
<div class="report-tipo-muestra" style="<?= esc($tipoStyle, 'attr') ?>">Tipo de Muestra: <?= esc($tipoMuestraLinea) ?></div>
<?php endif; ?>
<?php if ($mostrarMetodo): ?>
<div class="report-metodo-prueba" style="<?= esc($metodoStyle, 'attr') ?>">Método: <?= esc($metodoLinea) ?></div>
<?php endif; ?>
<?php else: ?>
<?php if ($tituloGrupo !== ''): ?>
<h4 style="<?= esc($titleStyle, 'attr') ?>"><?= esc($tituloGrupo) ?></h4>
<?php endif; ?>
<?php if ($mostrarTipoMuestra): ?>
<p class="small text-muted mb-0" style="<?= esc($tipoStyle, 'attr') ?>">Tipo de Muestra: <?= esc($tipoMuestraLinea) ?></p>
<?php endif; ?>
<?php if ($mostrarMetodo): ?>
<p class="small text-muted mb-0" style="<?= esc($metodoStyle, 'attr') ?>">Método: <?= esc($metodoLinea) ?></p>
<?php endif; ?>
<?php endif; ?>
