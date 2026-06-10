<?php
/**
 * Assets comunes de los reportes analíticos: flatpickr (fechas) y Chart.js (gráficos).
 * Variables: $with_charts (bool, default false)
 */
$withCharts = ! empty($with_charts);
?>
<link rel="stylesheet" href="<?= base_url('css/vendor/flatpickr.min.css') ?>">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/themes/material_green.css">
<script src="<?= base_url('js/vendor/flatpickr.min.js') ?>"></script>
<script src="https://cdn.jsdelivr.net/npm/flatpickr@4.6.13/dist/l10n/es.js"></script>
<?php if ($withCharts): ?>
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<?php endif; ?>
<style>
.an-sort-asc::after { content: " \25B2"; font-size: .7em; }
.an-sort-desc::after { content: " \25BC"; font-size: .7em; }
@media print {
    a[href]:after { content: none !important; }
    .table { font-size: 9pt; }
}
</style>
