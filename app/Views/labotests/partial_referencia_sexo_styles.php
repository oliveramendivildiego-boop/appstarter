<?php
/**
 * Estilos dinámicos: un color por sexo del catálogo (valores de referencia).
 */
if (! function_exists('referencia_sexo_color_defs')) {
    helper('config');
}
$sexoColorDefs = referencia_sexo_color_defs();
?>
<style>
<?php foreach ($sexoColorDefs as $slug => $colors): ?>
.lab-sexo-<?= $slug ?> {
    background-color: <?= $colors['bg'] ?>;
    color: <?= $colors['text'] ?>;
    border: 1px solid <?= $colors['border'] ?>;
}
#tabla_pri_resultados tr[data-sexo="<?= $slug ?>"] > td:first-child,
#tabla_sub_items tr[data-sexo="<?= $slug ?>"]:not(.table-secondary) > td:first-child {
    box-shadow: inset 3px 0 0 <?= $colors['border'] ?>;
}
<?php endforeach; ?>
</style>
