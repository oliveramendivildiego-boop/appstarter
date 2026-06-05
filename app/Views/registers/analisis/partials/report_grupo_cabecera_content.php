<?php
/**
 * Título, tipo de muestra y método de la cabecera de grupo (.report-pdf-grupo-cabecera).
 *
 * @var string $padre
 * @var string $hijo
 * @var string $tipo_muestra_linea
 * @var string $metodo_linea
 * @var string $variant 'web' | 'pdf' | 'screen_pdf'
 * @var array<string,mixed>|null $pdf_layout
 * @var string $web_title_mt Clase margin-top para vista web (p. ej. mt-4, mt-5)
 * @var int    $sub_idx Índice del subgrupo dentro del área (0 = primera prueba)
 * @var bool   $grupo_es_primero Si es el primer área/grupo del reporte
 */
$variant = $variant ?? 'web';
$usePdfChrome = ($variant === 'pdf' || $variant === 'screen_pdf');
$pdfLayout = is_array($pdf_layout ?? null) ? $pdf_layout : [];
$webTitleMt = trim((string) ($web_title_mt ?? 'mt-4'));
if ($webTitleMt === '') {
    $webTitleMt = 'mt-4';
}

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
$groupTitleStyle = $usePdfChrome
    ? \App\Services\ReportPdfLayoutService::groupTitleMarginStyleAttr(
        $pdfLayout,
        $isFirstSubgrupoInArea,
        $isFirstGrupoInReport
    )
    : '';
?>
<?php if ($usePdfChrome): ?>
<?php if ($tituloGrupo !== ''): ?>
<div class="group-title"<?= $groupTitleStyle !== '' ? ' style="' . esc($groupTitleStyle, 'attr') . '"' : '' ?>><?= esc($tituloGrupo) ?></div>
<?php endif; ?>
<?php if ($mostrarTipoMuestra): ?>
<div class="report-tipo-muestra" style="font-size:9pt;color:#555;margin:0 0 10px 0;line-height:1.3;">Tipo de Muestra: <?= esc($tipoMuestraLinea) ?></div>
<?php endif; ?>
<?php if ($mostrarMetodo): ?>
<div class="report-metodo-prueba" style="font-size:9pt;color:#555;margin:0 0 10px 0;line-height:1.3;">Método: <?= esc($metodoLinea) ?></div>
<?php endif; ?>
<?php else: ?>
<?php if ($tituloGrupo !== ''): ?>
<h4 class="<?= esc($webTitleMt, 'attr') ?> mb-1"><?= esc($tituloGrupo) ?></h4>
<?php endif; ?>
<?php if ($mostrarTipoMuestra || $mostrarMetodo): ?>
<div class="small text-muted mb-3">
    <?php if ($mostrarTipoMuestra): ?>
    <p class="mb-0">Tipo de Muestra: <?= esc($tipoMuestraLinea) ?></p>
    <?php endif; ?>
    <?php if ($mostrarMetodo): ?>
    <p class="mb-0<?= $mostrarTipoMuestra ? ' mt-1' : '' ?>">Método: <?= esc($metodoLinea) ?></p>
    <?php endif; ?>
</div>
<?php endif; ?>
<?php endif; ?>
