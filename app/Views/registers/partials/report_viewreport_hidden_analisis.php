<?php
/**
 * Campos ocultos para «Guardar» en viewreport (sin renderizar el HTML del reporte).
 *
 * @var array<string, list<object|array<string,mixed>>> $grupos
 */
$grupos = is_array($grupos ?? null) ? $grupos : [];
?>
<div class="visually-hidden" aria-hidden="true">
<?php foreach ($grupos as $padre => $items): ?>
    <?php
    $padreStr = trim((string) $padre);
    $hijo = '';
    foreach ($items as $rawHijo) {
        $itHijo = is_array($rawHijo) ? (object) $rawHijo : $rawHijo;
        if ((int) ($itHijo->es_separador ?? 0) === 1) {
            continue;
        }
        $h = trim((string) ($itHijo->hijo ?? ''));
        if ($h !== '') {
            $hijo = $h;
            break;
        }
    }
    if ($hijo === '' && ! empty($items[0])) {
        $firstObj = is_array($items[0]) ? (object) $items[0] : $items[0];
        $hijo = trim((string) ($firstObj->hijo ?? ''));
    }
    ?>
    <?php foreach ($items as $rawItem): ?>
        <?php
        $item = is_array($rawItem) ? (object) $rawItem : $rawItem;
        if ((int) ($item->es_separador ?? 0) === 1) {
            continue;
        }
        $aid = $item->secanacategoria_id ?? uniqid('', true);
        ?>
        <input type="hidden"
               id="analisis_<?= esc((string) $aid) ?>"
               name="analisis_<?= esc((string) $aid) ?>"
               class="analisis"
               padre="<?= esc($padreStr) ?>"
               hijo="<?= esc($hijo) ?>"
               analisis="<?= esc($item->nombre ?? '') ?>"
               value="<?= esc($item->regvalues ?? '') ?>"
               unidad="<?= esc($item->umedida ?? '') ?>"
               min="<?= esc($item->valor_min ?? '') ?>"
               max="<?= esc($item->valor_max ?? '') ?>">
    <?php endforeach; ?>
<?php endforeach; ?>
</div>
