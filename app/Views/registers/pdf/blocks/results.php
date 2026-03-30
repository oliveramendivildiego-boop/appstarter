<?php foreach ($grupos ?? [] as $padre => $items): ?>
    <?php
    $conResultado = [];
    $sinResultado = [];
    foreach ($items as $it) {
        $valTmp = trim((string)($it->regvalues ?? ''));
        if ($valTmp === '' || $valTmp === '-') {
            $sinResultado[] = $it;
        } else {
            $conResultado[] = $it;
        }
    }
    $conRefEnConResultado = false;
    foreach ($conResultado as $it) {
        if (registro_tiene_rango_referencial($it->valor_min ?? '', $it->valor_max ?? '')) {
            $conRefEnConResultado = true;
            break;
        }
    }
    ?>
    <div class="group-title"><?= esc($padre) ?> - <?= esc($items[0]->hijo ?? '') ?></div>
    <table class="results">
        <thead>
            <tr>
                <th>ANÁLISIS</th>
                <th class="text-center">RESULTADO</th>
                <?php if ($conRefEnConResultado): ?>
                <th class="text-center">RANGO REFERENCIAL</th>
                <?php endif; ?>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($conResultado as $item): ?>
                <?php
                $valor = $item->regvalues ?? '-';
                $min = $item->valor_min ?? '';
                $max = $item->valor_max ?? '';
                $itemConRef = registro_tiene_rango_referencial($min, $max);
                $isOut = false;
                $valNorm = is_string($valor) ? trim(strtolower($valor)) : '';
                if (in_array($valNorm, ['positivo', 'reactivo'], true)) {
                    $isOut = true;
                } elseif ($valor !== '-' && $valor !== '' && is_numeric($valor) && $min !== '' && $max !== '') {
                    $v = (float) $valor;
                    $mn = (float) $min;
                    $mx = (float) $max;
                    $isOut = ($v < $mn || $v > $mx);
                }
                $resMostrar = registro_resultado_con_unidad($item->regvalues ?? '', $item->umedida ?? '');
                $refRange = registro_rango_referencial_texto($min, $max, $item->umedida ?? '');
                ?>
                <tr>
                    <td><?= esc($item->nombre ?? '') ?></td>
                    <?php if (!$conRefEnConResultado): ?>
                    <td class="text-center <?= $isOut ? 'out-range' : '' ?>"><?= esc($resMostrar) ?></td>
                    <?php elseif ($itemConRef): ?>
                    <td class="text-center <?= $isOut ? 'out-range' : '' ?>"><?= esc($resMostrar) ?></td>
                    <td class="text-center ref-range"><?= esc($refRange) ?></td>
                    <?php else: ?>
                    <td class="text-center <?= $isOut ? 'out-range' : '' ?>" colspan="2"><?= esc($resMostrar) ?></td>
                    <?php endif; ?>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
    <?php if (!empty($sinResultado)): ?>
    <table class="results" style="margin-top:8px;">
        <thead>
            <tr>
                <th>ANÁLISIS</th>
                <th class="text-center">RANGO REFERENCIAL</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($sinResultado as $item): ?>
                <?php
                $refRange = registro_rango_referencial_texto($item->valor_min ?? '', $item->valor_max ?? '', $item->umedida ?? '');
                if ($refRange === '-' && trim((string)($item->umedida ?? '')) === '' && !((bool)($item->show_reference ?? false))) {
                    continue;
                }
                ?>
                    <tr>
                        <td><?= esc($item->nombre ?? '') ?></td>
                        <td class="text-center ref-range"><?= esc($refRange) ?></td>
                    </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
    <?php endif; ?>
<?php endforeach; ?>
