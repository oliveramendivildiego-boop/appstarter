<?php
/**
 * Ficha clínica llenada debajo de una prueba en la hoja de trabajo.
 *
 * @var object $cultivo_item
 * @var string $ficha_nombre
 * @var bool $for_pdf
 */
$forPdf = ! empty($for_pdf);
$fichaNombre = trim((string) ($ficha_nombre ?? ''));
?>
<div class="orden-prueba-ficha<?= $forPdf ? ' orden-prueba-ficha-pdf' : '' ?>">
    <div class="orden-prueba-ficha-titulo">
        <?php if (! $forPdf): ?>
            <i class="fa-solid fa-file-medical me-1" aria-hidden="true"></i>
        <?php endif; ?>
        Ficha clínica<?= $fichaNombre !== '' ? ': ' . esc($fichaNombre) : '' ?>
    </div>
    <div class="orden-prueba-ficha-body">
        <?= view('registers/analisis/partials/cultivo_matriz_reporte', [
            'cultivo_item'   => $cultivo_item,
            'variant'        => $forPdf ? 'pdf' : 'web',
            'padre'          => '',
            'hijo'           => '',
            'pria_id_titulo' => (int) ($cultivo_item->prianacategoria_id ?? 0),
            'sub_idx'        => 0,
        ]) ?>
    </div>
</div>
