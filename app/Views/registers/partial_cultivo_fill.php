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



helper('registro');

$prianacategoriaId = (int) ($prianacategoria_id ?? 0);

$tituloPrueba = (string) ($titulo_prueba ?? '');

$existentes = is_array($existentes ?? null) ? $existentes : [];

$soloLectura = ! empty($solo_lectura);

$inputAttrsSoloLectura = $soloLectura ? ' readonly tabindex="-1" data-prueba-retirada="1"' : '';

$selectAttrsSoloLectura = $soloLectura ? ' disabled data-prueba-retirada="1"' : '';

$registerModel = $registerModel ?? null;



if ($prianacategoriaId < 1) {

    return;

}



$labotestModel = model(LabotestModel::class);

$leyendaCultivoModel = model(LeyendaCultivoModel::class);

$matrizConfigOverride = is_array($matriz_config_override ?? null) ? $matriz_config_override : null;
$valorKeyPrefix = trim((string) ($valor_key_prefix ?? 'cv'));
$fichaClinicaIdFill = max(0, (int) ($ficha_clinica_id ?? 0));
$buildCellKey = static function (string $suffix, int $priaId, string $bloqueId, int $r, int $c) use ($valorKeyPrefix, $fichaClinicaIdFill): string {
    if ($valorKeyPrefix === 'fc' && $fichaClinicaIdFill > 0) {
        $keyPrefix = ($suffix === 'cvn_') ? 'fcn_' : 'fc_';

        return $keyPrefix . $fichaClinicaIdFill . '_' . $priaId . '_' . $bloqueId . '_' . $r . '_' . $c;
    }

    return $suffix . $priaId . '_' . $bloqueId . '_' . $r . '_' . $c;
};

$esPersonalizado = ! empty($es_personalizado) || $matrizConfigOverride !== null;
if ($matrizConfigOverride !== null) {
    $matriz = $matrizConfigOverride;
} else {
    $matriz = $esPersonalizado
        ? $labotestModel->getPersonalizadoMatrizConfig($prianacategoriaId)
        : $labotestModel->getCultivoMatrizConfig($prianacategoriaId);
}

$bloquesMatriz = $esPersonalizado
    ? \App\Models\LabotestModel::resolvePersonalizadoMatrizBloques($matriz)
    : \App\Models\LabotestModel::resolveCultivoMatrizBloques($matriz);

$leyendasActivas = $leyendaCultivoModel->getActivas();

$leyendasPorId = [];

foreach ($leyendasActivas as $lcRow) {

    $lid = (int) ($lcRow['leyenda_cultivo_id'] ?? 0);

    if ($lid > 0) {

        $leyendasPorId[$lid] = $lcRow;

    }

}

$cultivoKey = ($valorKeyPrefix === 'fc' && $fichaClinicaIdFill > 0)
    ? ('ficha_clinica_' . $fichaClinicaIdFill . '_' . $prianacategoriaId)
    : ('cultivo_' . $prianacategoriaId);

$valoresGuardados = [];

if (! empty($existentes[$cultivoKey])) {

    $decoded = json_decode((string) $existentes[$cultivoKey], true);

    if (is_array($decoded)) {

        $valoresGuardados = $decoded;

    }

}



$secciones = \App\Models\LabotestModel::CULTIVO_SECCION_LABELS;



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



$normalizeCeldaCfg = static function ($raw) use ($esPersonalizado): array {
    $out = ['modo' => 'texto'];

    if (is_array($raw)) {
        $modo = (string) ($raw['modo'] ?? 'texto');
        if ($modo === 'opcion') {
            $out = ['modo' => 'opcion', 'opcion_id' => max(0, (int) ($raw['opcion_id'] ?? 0))];
        } elseif ($modo === 'texto_rico') {
            $out = ['modo' => 'texto_rico'];
        } elseif ($modo === 'texto_fijo') {
            $out = ['modo' => 'texto_fijo'];
        } elseif ($modo === 'leyenda') {
            $out = [
                'modo'                         => 'leyenda',
                'leyenda_cultivo_categoria_id' => max(0, (int) ($raw['leyenda_cultivo_categoria_id'] ?? 0)),
            ];
        } elseif ($modo === 'vacio') {
            return ['modo' => 'vacio'];
        }
    } elseif (is_numeric($raw) && (int) $raw > 0) {
        $out = ['modo' => 'opcion', 'opcion_id' => (int) $raw];
    }

    if ($esPersonalizado && is_array($raw) && ($out['modo'] ?? '') !== 'vacio') {
        $ali = \App\Models\LabotestModel::normalizarAlineacionPersonalizado((string) ($raw['alineacion'] ?? 'izquierda'));
        $out['alineacion'] = $ali;
        $fuente = trim((string) ($raw['fuente'] ?? 'normal'));
        if (! in_array($fuente, ['normal', 'negrita', 'titulo', 'enriquecido'], true)) {
            $fuente = 'normal';
        }
        $out['fuente'] = $fuente;
        $rol = trim((string) ($raw['rol'] ?? 'input'));
        if (! in_array($rol, ['input', 'titulo', 'etiqueta'], true)) {
            $rol = 'input';
        }
        $out['rol'] = $rol;
        $out['rowspan'] = max(1, min(50, (int) ($raw['rowspan'] ?? 1)));
        $out['colspan'] = max(1, min(20, (int) ($raw['colspan'] ?? 1)));
        $textoFijo = trim((string) ($raw['texto_fijo'] ?? ''));
        if (($out['modo'] ?? '') === 'texto_fijo') {
            $out['rol'] = 'titulo';
        }
        if ($textoFijo !== '') {
            $out['texto_fijo'] = $textoFijo;
        }
    }

    return $out;
};

$estiloCeldaPersonalizado = static function (array $celdaCfg): string {
    $styles = [];
    $ali = \App\Models\LabotestModel::normalizarAlineacionPersonalizado((string) ($celdaCfg['alineacion'] ?? 'izquierda'));
    $map = ['izquierda' => 'left', 'centro' => 'center', 'derecha' => 'right', 'justificado' => 'justify'];
    $styles[] = 'text-align:' . ($map[$ali] ?? 'left');
    $fuente = $celdaCfg['fuente'] ?? 'normal';
    if ($fuente === 'negrita') {
        $styles[] = 'font-weight:700';
    } elseif ($fuente === 'titulo') {
        $styles[] = 'font-weight:700';
        $styles[] = 'font-size:1.05em';
    }

    return $styles === [] ? '' : ' style="' . esc(implode(';', $styles), 'attr') . '"';
};

$celdaEsTituloFill = static function (array $cfg): bool {
    $rol = $cfg['rol'] ?? 'input';
    if (($cfg['modo'] ?? '') === 'texto_fijo') {
        return true;
    }

    return in_array($rol, ['titulo', 'etiqueta'], true);
};

$celdaEsInputFill = static function (array $cfg): bool {
    $rol = $cfg['rol'] ?? 'input';

    return ! in_array($rol, ['titulo', 'etiqueta'], true);
};

$calcColspanTextoRicoPersonalizado = static function (
    int $r,
    int $c,
    int $columnas,
    array $celdas,
    array $coveredRowspan,
    callable $normalizeCeldaCfg,
    callable $celdaEsInputFill
): int {
    for ($cc = $c + 1; $cc < $columnas; $cc++) {
        if (isset($coveredRowspan[$r . ',' . $cc])) {
            continue;
        }
        $cfg = $normalizeCeldaCfg($celdas[$r][$cc] ?? ['modo' => 'texto']);
        if ($celdaEsInputFill($cfg)) {
            return 1;
        }
    }

    return max(1, $columnas - $c);
};

$renderExtrasFill = static function (

    int $priaId,

    string $bloqueId,

    string $bloqueTipo,

    int $r,

    int $c,

    array $existentes,

    bool $mostrarValor,

    string $placeholderValor = ''

) use ($buildCellKey): string {

    if ($bloqueTipo !== 'cuerpo' || ! $mostrarValor) {

        return '';

    }

    $dataBase = ' data-prianacategoria-id="' . $priaId . '"'

        . ' data-bloque-id="' . esc($bloqueId, 'attr') . '"'

        . ' data-seccion="' . esc($bloqueId, 'attr') . '"'

        . ' data-fila="' . $r . '"'

        . ' data-columna="' . $c . '"';

    $idValor = $buildCellKey('cvn_', $priaId, $bloqueId, $r, $c);

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

.cultivo-fill-wrap.cultivo-fill-personalizado .table {
    width: 100%;
}

.cultivo-fill-wrap.cultivo-fill-personalizado .cultivo-fill-td-texto-rico {
    width: 100%;
}

.cultivo-fill-wrap.cultivo-fill-personalizado .cultivo-fill-texto-rico-row {
    display: block;
    width: 100%;
}

.cultivo-fill-wrap.cultivo-fill-personalizado .cultivo-fill-texto-rico-row .cultivo-fill-main {
    flex: none;
    width: 100%;
    max-width: 100%;
    min-width: 0;
}

.cultivo-fill-wrap.cultivo-fill-personalizado .cultivo-fill-texto-rico-row .note-editor.note-frame {
    width: 100% !important;
    max-width: 100%;
    box-sizing: border-box;
}

.cultivo-fill-wrap.cultivo-fill-personalizado .cultivo-fill-texto-rico-row .cultivo-celda-valor-fill {
    flex: none;
    width: 100%;
    max-width: 12rem;
    margin-top: 0.35rem;
}

.cultivo-fill-wrap.cultivo-fill-personalizado td[rowspan],
.cultivo-fill-wrap.cultivo-fill-personalizado td[colspan] {
    vertical-align: top;
}

.cultivo-fill-wrap.cultivo-fill-personalizado td[colspan] .cultivo-fill-celda-row,
.cultivo-fill-wrap.cultivo-fill-personalizado td.cultivo-fill-ali-centro .cultivo-fill-celda-row,
.cultivo-fill-wrap.cultivo-fill-personalizado td.cultivo-fill-ali-derecha .cultivo-fill-celda-row {
    width: 100%;
}

.cultivo-fill-wrap.cultivo-fill-personalizado td.cultivo-fill-ali-centro .cultivo-fill-celda-row {
    justify-content: center;
}

.cultivo-fill-wrap.cultivo-fill-personalizado td.cultivo-fill-ali-derecha .cultivo-fill-celda-row {
    justify-content: flex-end;
}

.cultivo-fill-wrap.cultivo-fill-personalizado td.cultivo-fill-ali-centro .cultivo-fill-main,
.cultivo-fill-wrap.cultivo-fill-personalizado td.cultivo-fill-ali-derecha .cultivo-fill-main {
    flex: 0 1 auto;
    max-width: 100%;
}

.cultivo-fill-wrap.cultivo-fill-personalizado td.cultivo-fill-ali-centro .cultivo-fill-texto-fijo {
    text-align: center;
    width: 100%;
}

.cultivo-fill-wrap.cultivo-fill-personalizado td.cultivo-fill-ali-derecha .cultivo-fill-texto-fijo {
    text-align: right;
    width: 100%;
}

.cultivo-fill-wrap.cultivo-fill-personalizado td.cultivo-fill-ali-justificado .cultivo-fill-celda-row,
.cultivo-fill-wrap.cultivo-fill-personalizado td.cultivo-fill-ali-justificado .cultivo-fill-texto-fijo {
    text-align: justify;
    width: 100%;
}

.cultivo-fill-wrap.cultivo-fill-personalizado td.cultivo-fill-ali-centro .cultivo-celda-valor-fill,
.cultivo-fill-wrap.cultivo-fill-personalizado td.cultivo-fill-ali-derecha .cultivo-celda-valor-fill {
    flex: 0 1 auto;
}

.cultivo-fill-texto-fijo p:last-child {
    margin-bottom: 0;
}

.cultivo-fill-texto-fijo ul,
.cultivo-fill-texto-fijo ol {
    margin-bottom: 0.35rem;
    padding-left: 1.25rem;
}

.cultivo-fill-texto-fijo strong,
.cultivo-fill-texto-fijo b {
    font-weight: 700;
}

.cultivo-fill-texto-fijo em,
.cultivo-fill-texto-fijo i {
    font-style: italic;
}

</style>

<div class="col-12 mb-3 cultivo-fill-wrap<?= $esPersonalizado ? ' cultivo-fill-personalizado' : '' ?>" data-prianacategoria-id="<?= $prianacategoriaId ?>">

    <div class="border rounded p-3 bg-white">

        <div class="d-flex flex-wrap align-items-center gap-2 mb-3">

            <strong><?= esc($tituloPrueba) ?></strong>

            <span class="badge <?= $esPersonalizado ? 'bg-primary' : 'bg-info text-dark' ?>"><?= $esPersonalizado ? 'Personalizado' : 'Cultivo' ?></span>

        </div>



        <?php foreach ($bloquesMatriz as $bloque):

            $bloqueId = (string) ($bloque['id'] ?? '');

            $bloqueTipo = (string) ($bloque['tipo'] ?? 'encabezado');

            if ($bloqueId === '') {

                continue;

            }

            $secLabel = \App\Models\LabotestModel::cultivoBloqueDisplayLabel($bloque, $bloquesMatriz);

            $sec = $bloque;

            $filas = max(0, (int) ($sec['filas'] ?? 0));

            $columnas = max(1, (int) ($sec['columnas'] ?? 1));

            $titulosPorCol = $normalizeTitulosCol($sec, $columnas);

            $titulosFilas = \App\Models\LabotestModel::buildCultivoTituloFilasTabla($titulosPorCol, $columnas);

            $celdas = is_array($sec['celdas'] ?? null) ? $sec['celdas'] : [];

            $mostrarValorSec = ($bloqueTipo === 'cuerpo') && ! empty($sec['valores_habilitado']);

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

                        <?php
                        $coveredRowspan = [];
                        $skipCols = [];
                        for ($r = 0; $r < $filas; $r++):
                            if ($esPersonalizado) {
                                $filaVisibleFill = false;
                                for ($cVis = 0; $cVis < $columnas; $cVis++) {
                                    if (! isset($coveredRowspan[$r . ',' . $cVis])) {
                                        $filaVisibleFill = true;
                                        break;
                                    }
                                }
                                if (! $filaVisibleFill) {
                                    continue;
                                }
                            }
                        ?>

                        <tr>

                            <?php for ($c = 0; $c < $columnas; $c++):
                                if (isset($coveredRowspan[$r . ',' . $c]) || isset($skipCols[$r . ',' . $c])) {
                                    continue;
                                }

                                $celdaRaw = $celdas[$r][$c] ?? ['modo' => 'texto'];

                                $celdaCfg = $normalizeCeldaCfg($celdaRaw);
                                $esTextoRicoFill = ($celdaCfg['modo'] ?? '') === 'texto_rico'
                                    || (($celdaCfg['modo'] ?? '') === 'opcion'
                                        && registro_opcion_es_texto_rico((int) ($celdaCfg['opcion_id'] ?? 0)));
                                $tdColspanAttr = '';
                                $tdClass = 'p-1';
                                $colspan = $esPersonalizado ? max(1, (int) ($celdaCfg['colspan'] ?? 1)) : 1;
                                if ($esPersonalizado) {
                                    $maxColspanFill = max(1, $columnas - $c);
                                    if ($colspan > $maxColspanFill) {
                                        $colspan = $maxColspanFill;
                                    }
                                }
                                if ($esPersonalizado) {
                                    $aliTd = \App\Models\LabotestModel::normalizarAlineacionPersonalizado((string) ($celdaCfg['alineacion'] ?? 'izquierda'));
                                    $tdClass .= ' cultivo-fill-ali-' . $aliTd;
                                }
                                if ($esPersonalizado && $colspan > 1) {
                                    $tdColspanAttr = ' colspan="' . (int) $colspan . '"';
                                    for ($cc = $c + 1; $cc < $c + $colspan; $cc++) {
                                        $skipCols[$r . ',' . $cc] = true;
                                    }
                                    if ($esTextoRicoFill) {
                                        $tdClass .= ' cultivo-fill-td-texto-rico';
                                    }
                                } elseif ($esPersonalizado && $esTextoRicoFill && $celdaEsInputFill($celdaCfg)) {
                                    $ricoColspan = $calcColspanTextoRicoPersonalizado(
                                        $r,
                                        $c,
                                        $columnas,
                                        $celdas,
                                        $coveredRowspan,
                                        $normalizeCeldaCfg,
                                        $celdaEsInputFill
                                    );
                                    if ($ricoColspan > 1) {
                                        $tdColspanAttr = ' colspan="' . (int) $ricoColspan . '"';
                                        for ($cc = $c + 1; $cc < $c + $ricoColspan; $cc++) {
                                            $skipCols[$r . ',' . $cc] = true;
                                        }
                                    }
                                    $tdClass .= ' cultivo-fill-td-texto-rico';
                                }
                                $rowspan = $esPersonalizado ? max(1, (int) ($celdaCfg['rowspan'] ?? 1)) : 1;
                                if ($esPersonalizado) {
                                    $maxRowspanFill = max(1, $filas - $r);
                                    if ($rowspan > $maxRowspanFill) {
                                        $rowspan = $maxRowspanFill;
                                    }
                                }
                                if ($rowspan > 1) {
                                    for ($rr = $r + 1; $rr < $r + $rowspan && $rr < $filas; $rr++) {
                                        for ($cc = $c; $cc < $c + $colspan; $cc++) {
                                            $coveredRowspan[$rr . ',' . $cc] = true;
                                        }
                                    }
                                }
                                $tdStyle = $esPersonalizado ? $estiloCeldaPersonalizado($celdaCfg) : '';
                                $tdRowspan = ($esPersonalizado && $rowspan > 1) ? ' rowspan="' . (int) $rowspan . '"' : '';

                                $valorPlaceholder = is_array($celdaRaw) ? trim((string) ($celdaRaw['valor'] ?? '')) : '';

                                $valorActual = (string) ($existentes[$buildCellKey('cv_', $prianacategoriaId, $bloqueId, $r, $c)] ?? '');

                                if ($valorActual === '') {

                                    $valorActual = $valorCelda($valoresGuardados, $bloqueId, $r, $c);

                                }

                                $inputId = $buildCellKey('cv_', $prianacategoriaId, $bloqueId, $r, $c);

                                if ($esTextoRicoFill && $valorActual !== '') {
                                    $valorActual = registro_sanitizar_html_rico($valorActual);
                                }

                                $dataAttrs = ' data-prianacategoria-id="' . $prianacategoriaId . '"'

                                    . ' data-bloque-id="' . esc($bloqueId, 'attr') . '"'

                                    . ' data-seccion="' . esc($bloqueId, 'attr') . '"'

                                    . ' data-fila="' . $r . '"'

                                    . ' data-columna="' . $c . '"';

                                $extrasHtml = $renderExtrasFill(

                                    $prianacategoriaId,

                                    $bloqueId,

                                    $bloqueTipo,

                                    $r,

                                    $c,

                                    $existentes,

                                    $mostrarValorSec,

                                    $valorPlaceholder

                                );

                            ?>

                            <td class="<?= esc($tdClass) ?>"<?= $tdStyle ?><?= $tdRowspan ?><?= $tdColspanAttr ?>>

                                <?php if (($celdaCfg['modo'] ?? '') === 'vacio'): ?>

                                <?php elseif ($esPersonalizado && $celdaEsTituloFill($celdaCfg)):
                                    $textoMostrar = trim((string) ($celdaCfg['texto_fijo'] ?? ''));
                                    $fuenteMostrar = (string) ($celdaCfg['fuente'] ?? 'normal');
                                ?>
                                    <div class="cultivo-fill-celda-row">
                                        <span class="cultivo-fill-texto-fijo"><?= registro_personalizado_texto_fijo_html($textoMostrar, $fuenteMostrar) ?></span>
                                    </div>
                                <?php elseif ($celdaCfg['modo'] === 'texto_rico'
                                    || ($celdaCfg['modo'] === 'opcion' && registro_opcion_es_texto_rico((int) ($celdaCfg['opcion_id'] ?? 0)))): ?>

                                    <div class="cultivo-fill-celda-row<?= ($esPersonalizado && $esTextoRicoFill) ? ' cultivo-fill-texto-rico-row' : '' ?>">

                                    <textarea id="<?= esc($inputId, 'attr') ?>"

                                              class="form-control<?= ($esPersonalizado && $esTextoRicoFill) ? '' : ' form-control-sm' ?> cultivo-celda-input cultivo-fill-main input-texto-rico"

                                              rows="<?= ($esPersonalizado && $esTextoRicoFill) ? '6' : '4' ?>"

                                              data-skip-ref-validation="1"

                                              <?= $dataAttrs ?><?= $inputAttrsSoloLectura ?>><?= registro_textarea_body_safe($valorActual) ?></textarea>

                                    <?= $extrasHtml ?>

                                    </div>

                                <?php elseif ($celdaCfg['modo'] === 'opcion'):

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

                                           <?= $dataAttrs ?><?= $inputAttrsSoloLectura ?>

                                           value="<?= esc($valorActual) ?>"

                                           placeholder="Sin opciones configuradas">

                                    <?php else: ?>

                                    <select id="<?= esc($inputId, 'attr') ?>"

                                            class="form-select form-select-sm cultivo-celda-input cultivo-fill-main"

                                            <?= $dataAttrs ?><?= $selectAttrsSoloLectura ?>>

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

                                            <?= $dataAttrs ?><?= $selectAttrsSoloLectura ?>

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

                                           <?= $dataAttrs ?><?= $inputAttrsSoloLectura ?>

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

