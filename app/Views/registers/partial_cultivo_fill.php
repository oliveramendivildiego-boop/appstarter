<?php

/**

 * Formulario de captura de resultados para prueba tipo cultivo (matriz).

 *

 * @var int $prianacategoria_id

 * @var string $titulo_prueba

 * @var array<string, string> $existentes

 * @var object|null $registerModel

 */

use App\Models\LabotestModel;

use App\Models\LeyendaCultivoModel;



$prianacategoriaId = (int) ($prianacategoria_id ?? 0);

$tituloPrueba = (string) ($titulo_prueba ?? '');

$existentes = is_array($existentes ?? null) ? $existentes : [];

$registerModel = $registerModel ?? null;



if ($prianacategoriaId < 1) {

    return;

}



$labotestModel = model(LabotestModel::class);

$leyendaCultivoModel = model(LeyendaCultivoModel::class);

$matriz = $labotestModel->getCultivoMatrizConfig($prianacategoriaId);

$cuerpoCfg = is_array($matriz['cuerpo'] ?? null) ? $matriz['cuerpo'] : [];

$valoresFillHabilitado = ! empty($cuerpoCfg['valores_habilitado']);

$leyendasActivas = $leyendaCultivoModel->getActivas();

$leyendasPorId = [];

foreach ($leyendasActivas as $lcRow) {

    $lid = (int) ($lcRow['leyenda_cultivo_id'] ?? 0);

    if ($lid > 0) {

        $leyendasPorId[$lid] = $lcRow;

    }

}

$cultivoKey = 'cultivo_' . $prianacategoriaId;

$valoresGuardados = [];

if (! empty($existentes[$cultivoKey])) {

    $decoded = json_decode((string) $existentes[$cultivoKey], true);

    if (is_array($decoded)) {

        $valoresGuardados = $decoded;

    }

}



$secciones = [

    'encabezado' => 'Encabezado',

    'cuerpo'     => 'Cuerpo',

    'pie'        => 'Pie',

];



$valorCelda = static function (array $guardados, string $sec, int $fila, int $col): string {

    $v = $guardados[$sec][$fila][$col] ?? '';

    return is_scalar($v) ? (string) $v : '';

};



$normalizeTitulosCol = static function (array $sec, int $columnas): array {

    return \App\Models\LabotestModel::parseCultivoTitulosPorColumna(

        is_array($sec['titulos'] ?? null) ? $sec['titulos'] : [],

        $columnas

    );

};



$normalizeCeldaCfg = static function ($raw): array {

    if (is_array($raw)) {

        $modo = (string) ($raw['modo'] ?? 'texto');

        if ($modo === 'opcion') {

            return ['modo' => 'opcion', 'opcion_id' => max(0, (int) ($raw['opcion_id'] ?? 0))];

        }

        if ($modo === 'leyenda') {

            return [

                'modo'                         => 'leyenda',

                'leyenda_cultivo_categoria_id' => max(0, (int) ($raw['leyenda_cultivo_categoria_id'] ?? 0)),

            ];

        }

        return ['modo' => 'texto'];

    }

    if (is_numeric($raw) && (int) $raw > 0) {

        return ['modo' => 'opcion', 'opcion_id' => (int) $raw];

    }

    return ['modo' => 'texto'];

};



$renderExtrasFill = static function (

    int $priaId,

    string $secId,

    int $r,

    int $c,

    array $existentes,

    bool $mostrarValor,

    string $placeholderValor = ''

): string {

    if ($secId !== 'cuerpo' || ! $mostrarValor) {

        return '';

    }

    $dataBase = ' data-prianacategoria-id="' . $priaId . '"'

        . ' data-seccion="' . esc($secId, 'attr') . '"'

        . ' data-fila="' . $r . '"'

        . ' data-columna="' . $c . '"';

    $idValor = 'cvn_' . $priaId . '_' . $secId . '_' . $r . '_' . $c;

    $valValor = trim((string) ($existentes[$idValor] ?? ''));

    $phValor = $placeholderValor !== '' ? $placeholderValor : 'Valor';

    $html = '';

    if ($mostrarValor) {

        $html .= '<input type="text"'

            . ' id="' . esc($idValor, 'attr') . '"'

            . ' class="form-control form-control-sm cultivo-celda-valor-fill"'

            . $dataBase

            . ' value="' . esc($valValor, 'attr') . '"'

            . ' placeholder="' . esc($phValor, 'attr') . '">';

    }



    return $html;

};



$leyendasJsFill = [];

$leyendasPorCategoriaFill = [];

foreach ($leyendasPorId as $lid => $lcRow) {

    $leyendasJsFill[$lid] = [

        'titulo'  => (string) ($lcRow['titulo'] ?? ''),

        'mensaje' => (string) ($lcRow['mensaje'] ?? ''),

    ];

    $catId = (int) ($lcRow['leyenda_cultivo_categoria_id'] ?? 0);

    $catNombre = trim((string) ($lcRow['categoria_nombre'] ?? ''));

    if ($catId < 1) {

        $catId = 0;

        $catNombre = 'Sin categoría';

    } elseif ($catNombre === '') {

        $catNombre = 'Categoría ' . $catId;

    }

    if (! isset($leyendasPorCategoriaFill[$catId])) {

        $leyendasPorCategoriaFill[$catId] = [

            'categoria_nombre' => $catNombre,

            'leyendas'         => [],

        ];

    }

    $leyendasPorCategoriaFill[$catId]['leyendas'][] = $lcRow;

}

?>

<style>

.cultivo-fill-wrap .cultivo-fill-seccion { margin-bottom: 1.25rem; }

.cultivo-fill-wrap .cultivo-fill-titulos th {

    background: #f8f9fa;

    font-weight: 600;

    font-size: 0.85rem;

    vertical-align: middle;

}

.cultivo-fill-wrap .table { margin-bottom: 0; }

.cultivo-fill-leyenda-preview {

    font-size: 0.8rem;

    margin-top: 0.35rem;

    padding: 0.35rem 0.5rem;

    border: 1px solid #dee2e6;

    border-radius: 0.25rem;

    background: #f8f9fa;

}

.cultivo-fill-celda-row {

    display: flex;

    gap: 0.35rem;

    align-items: flex-start;

    flex-wrap: wrap;

}

.cultivo-fill-celda-row .cultivo-fill-main {

    flex: 1 1 8rem;

    min-width: 6rem;

}

.cultivo-fill-celda-row .cultivo-celda-valor-fill {
    flex: 0 0 5.5rem;
    max-width: 7.5rem;
}

</style>

<div class="col-12 mb-3 cultivo-fill-wrap" data-prianacategoria-id="<?= $prianacategoriaId ?>">

    <div class="border rounded p-3 bg-white">

        <div class="d-flex flex-wrap align-items-center gap-2 mb-3">

            <strong><?= esc($tituloPrueba) ?></strong>

            <span class="badge bg-info text-dark">Cultivo</span>

        </div>



        <?php foreach ($secciones as $secId => $secLabel):

            $sec = $matriz[$secId] ?? ['filas' => 0, 'columnas' => 1, 'titulos' => [[]], 'celdas' => []];

            $filas = max(0, (int) ($sec['filas'] ?? 0));

            $columnas = max(1, (int) ($sec['columnas'] ?? 1));

            $titulosPorCol = $normalizeTitulosCol($sec, $columnas);

            $titulosFilas = \App\Models\LabotestModel::buildCultivoTituloFilasTabla($titulosPorCol, $columnas);

            $celdas = is_array($sec['celdas'] ?? null) ? $sec['celdas'] : [];

            $mostrarValorSec = ($secId === 'cuerpo') && $valoresFillHabilitado;

            $maxTituloFilas = count($titulosFilas);



            $tieneContenido = $maxTituloFilas > 0 || $filas > 0;

            if (! $tieneContenido) {

                continue;

            }

        ?>

        <div class="cultivo-fill-seccion">

            <h6 class="mb-2 text-secondary"><?= esc($secLabel) ?></h6>

            <div class="table-responsive">

                <table class="table table-bordered table-sm">

                    <?php if ($titulosFilas !== []): ?>

                    <thead>

                        <?php foreach ($titulosFilas as $filaTitulos): ?>

                        <tr class="cultivo-fill-titulos">

                            <?php foreach ($filaTitulos as $thCell):

                                $thTexto = (string) ($thCell['texto'] ?? '');

                                $thColspan = max(1, (int) ($thCell['colspan'] ?? 1));

                            ?>

                            <th class="p-2 text-center"<?= $thColspan > 1 ? ' colspan="' . (int) $thColspan . '"' : '' ?>><?= esc($thTexto) ?></th>

                            <?php endforeach; ?>

                        </tr>

                        <?php endforeach; ?>

                    </thead>

                    <?php endif; ?>

                    <?php if ($filas > 0): ?>

                    <tbody>

                        <?php for ($r = 0; $r < $filas; $r++): ?>

                        <tr>

                            <?php for ($c = 0; $c < $columnas; $c++):

                                $celdaRaw = $celdas[$r][$c] ?? ['modo' => 'texto'];

                                $celdaCfg = $normalizeCeldaCfg($celdaRaw);

                                $valorPlaceholder = is_array($celdaRaw) ? trim((string) ($celdaRaw['valor'] ?? '')) : '';

                                $valorActual = (string) ($existentes['cv_' . $prianacategoriaId . '_' . $secId . '_' . $r . '_' . $c] ?? '');

                                if ($valorActual === '') {

                                    $valorActual = $valorCelda($valoresGuardados, $secId, $r, $c);

                                }

                                $inputId = 'cv_' . $prianacategoriaId . '_' . $secId . '_' . $r . '_' . $c;

                                $dataAttrs = ' data-prianacategoria-id="' . $prianacategoriaId . '"'

                                    . ' data-seccion="' . esc($secId, 'attr') . '"'

                                    . ' data-fila="' . $r . '"'

                                    . ' data-columna="' . $c . '"';

                                $extrasHtml = $renderExtrasFill(

                                    $prianacategoriaId,

                                    $secId,

                                    $r,

                                    $c,

                                    $existentes,

                                    $mostrarValorSec,

                                    $valorPlaceholder

                                );

                            ?>

                            <td class="p-1">

                                <?php if ($celdaCfg['modo'] === 'opcion'):

                                    $opcionTipoId = (int) ($celdaCfg['opcion_id'] ?? 0);

                                    $opts = ($registerModel && $opcionTipoId > 0)

                                        ? $registerModel->getOpciones($opcionTipoId)

                                        : [];

                                ?>

                                    <div class="cultivo-fill-celda-row">

                                    <?php if ($opts === []): ?>

                                    <input type="text"

                                           id="<?= esc($inputId, 'attr') ?>"

                                           class="form-control form-control-sm cultivo-celda-input cultivo-fill-main"

                                           <?= $dataAttrs ?>

                                           value="<?= esc($valorActual) ?>"

                                           placeholder="Sin opciones configuradas">

                                    <?php else: ?>

                                    <select id="<?= esc($inputId, 'attr') ?>"

                                            class="form-select form-select-sm cultivo-celda-input cultivo-fill-main"

                                            <?= $dataAttrs ?>>

                                        <option value="">— Seleccione —</option>

                                        <?php foreach ($opts as $oid => $oname): ?>

                                        <option value="<?= esc((string) $oname, 'attr') ?>" <?= $valorActual === (string) $oname ? 'selected' : '' ?>><?= esc($oname) ?></option>

                                        <?php endforeach; ?>

                                    </select>

                                    <?php endif; ?>

                                    <?= $extrasHtml ?>

                                    </div>

                                <?php elseif ($celdaCfg['modo'] === 'leyenda'):

                                    $leyendaCatId = (int) ($celdaCfg['leyenda_cultivo_categoria_id'] ?? 0);

                                    $leyendaSelId = (int) $valorActual;

                                    $previewHtml = '';

                                    if ($leyendaSelId > 0 && isset($leyendasPorId[$leyendaSelId])) {

                                        $previewHtml = (string) ($leyendasPorId[$leyendaSelId]['mensaje'] ?? '');

                                    }

                                    $leyendasFiltradas = ($leyendaCatId > 0 && isset($leyendasPorCategoriaFill[$leyendaCatId]))

                                        ? ($leyendasPorCategoriaFill[$leyendaCatId]['leyendas'] ?? [])

                                        : [];

                                ?>

                                    <?php if ($leyendaCatId < 1): ?>

                                    <div class="cultivo-fill-celda-row">

                                    <input type="text"

                                           id="<?= esc($inputId, 'attr') ?>"

                                           class="form-control form-control-sm cultivo-celda-input cultivo-fill-main"

                                           <?= $dataAttrs ?>

                                           value=""

                                           readonly

                                           placeholder="Sin categoría configurada">

                                    <?= $extrasHtml ?>

                                    </div>

                                    <?php elseif ($leyendasFiltradas === []): ?>

                                    <div class="cultivo-fill-celda-row">

                                    <input type="text"

                                           id="<?= esc($inputId, 'attr') ?>"

                                           class="form-control form-control-sm cultivo-celda-input cultivo-fill-main"

                                           <?= $dataAttrs ?>

                                           value=""

                                           readonly

                                           placeholder="Sin leyendas en esta categoría">

                                    <?= $extrasHtml ?>

                                    </div>

                                    <?php else: ?>

                                    <div class="cultivo-fill-celda-row">

                                    <select id="<?= esc($inputId, 'attr') ?>"

                                            class="form-select form-select-sm cultivo-celda-input cultivo-leyenda-fill-select cultivo-fill-main"

                                            <?= $dataAttrs ?>

                                            data-categoria-id="<?= $leyendaCatId ?>">

                                        <option value="">— Seleccione título —</option>

                                        <?php foreach ($leyendasFiltradas as $lcRow):

                                            $lid = (int) ($lcRow['leyenda_cultivo_id'] ?? 0);

                                            if ($lid < 1) continue;

                                        ?>

                                        <option value="<?= $lid ?>" <?= $leyendaSelId === $lid ? 'selected' : '' ?>>

                                            <?= esc((string) ($lcRow['titulo'] ?? '')) ?>

                                        </option>

                                        <?php endforeach; ?>

                                    </select>

                                    <?= $extrasHtml ?>

                                    </div>

                                    <div class="cultivo-fill-leyenda-preview" data-preview-for="<?= esc($inputId, 'attr') ?>"><?= $previewHtml ?></div>

                                    <?php endif; ?>

                                <?php else: ?>

                                    <div class="cultivo-fill-celda-row">

                                    <input type="text"

                                           id="<?= esc($inputId, 'attr') ?>"

                                           class="form-control form-control-sm cultivo-celda-input cultivo-fill-main"

                                           <?= $dataAttrs ?>

                                           value="<?= esc($valorActual) ?>"

                                           placeholder="Resultado">

                                    <?= $extrasHtml ?>

                                    </div>

                                <?php endif; ?>

                            </td>

                            <?php endfor; ?>

                        </tr>

                        <?php endfor; ?>

                    </tbody>

                    <?php endif; ?>

                </table>

            </div>

        </div>

        <?php endforeach; ?>

    </div>

</div>

<script>

(function() {

    var leyendasMap = <?= json_encode($leyendasJsFill, JSON_UNESCAPED_UNICODE) ?>;

    function updateFillPreview(sel) {

        if (!sel) return;

        var id = sel.id || '';

        var preview = document.querySelector('[data-preview-for="' + id + '"]');

        if (!preview) return;

        var lid = parseInt(sel.value, 10);

        preview.innerHTML = (lid > 0 && leyendasMap[lid]) ? (leyendasMap[lid].mensaje || '') : '';

    }

    document.querySelectorAll('.cultivo-leyenda-fill-select').forEach(function(sel) {

        sel.addEventListener('change', function() { updateFillPreview(sel); });

        updateFillPreview(sel);

    });

})();

</script>

