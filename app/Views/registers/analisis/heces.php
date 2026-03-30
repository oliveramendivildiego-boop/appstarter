<?php if (!empty($grupos)): ?>
    <div class="container mt-4">
        <?php foreach ($grupos as $padre => $items): ?>
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
            <h4 class="mt-4"><?= esc($padre) ?> - <?= esc($items[0]->hijo ?? '') ?></h4>
            <div class="table-responsive">
            <table class="table">
                <thead class="thead-dark">
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
					<?php $aid = $item->secanacategoria_id ?? uniqid(); ?>
					<input type="hidden" id="analisis_<?= esc($aid) ?>" name="analisis_<?= esc($aid) ?>" class="analisis" padre="<?= esc($padre) ?>" hijo="<?= esc($items[0]->hijo ?? '') ?>" analisis="<?= esc($item->nombre ?? '') ?>" value="<?= esc($item->regvalues ?? '') ?>" unidad="<?= esc($item->umedida ?? '') ?>" min="<?= esc($item->valor_min ?? '') ?>" max="<?= esc($item->valor_max ?? '') ?>">
					<?php
						$val = $item->regvalues ?? '';
						if (!$val) {
                            $item->regvalues = '-';
                            $val = '-';
                        }
						$valNorm = trim(strtolower((string) $val));
						if (in_array($valNorm, ['positivo', 'reactivo'], true)) {
							$class = 'text-danger font-weight-bold';
						} elseif (is_numeric($val) && ($item->valor_min ?? '') !== '' && ($item->valor_max ?? '') !== '') {
							$class = ($val >= $item->valor_min && $val <= $item->valor_max) ? 'normal' : 'text-danger font-weight-bold';
						} else {
							$class = 'normal';
						}
						$resMostrar = registro_resultado_con_unidad($item->regvalues ?? '', $item->umedida ?? '');
						$refMostrar = registro_rango_referencial_texto($item->valor_min ?? '', $item->valor_max ?? '', $item->umedida ?? '');
					?>
                        <?php if (is_object($item)): ?>
                            <?php $itemConRef = registro_tiene_rango_referencial($item->valor_min ?? '', $item->valor_max ?? ''); ?>
                            <tr>
                                <td><?= esc($item->nombre ?? '') ?></td>
                                <?php if (!$conRefEnConResultado): ?>
                                <td class="text-center <?= $class ?>"><?= esc($resMostrar) ?></td>
                                <?php elseif ($itemConRef): ?>
                                <td class="text-center <?= $class ?>"><?= esc($resMostrar) ?></td>
                                <td class="text-center"><?= esc($refMostrar) ?></td>
                                <?php else: ?>
                                <td class="text-center <?= $class ?>" colspan="2"><?= esc($resMostrar) ?></td>
                                <?php endif; ?>
                            </tr>
                        <?php endif; ?>
                    <?php endforeach; ?>
                </tbody>
            </table>
            </div>
            <?php if (!empty($sinResultado)): ?>
                <div class="table-responsive mt-2">
                    <table class="table table-sm table-bordered">
                        <thead>
                            <tr>
                                <th>ANÁLISIS</th>
                                <th class="text-center">RANGO REFERENCIAL</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($sinResultado as $item): ?>
                                <?php
                                $refMostrar = registro_rango_referencial_texto($item->valor_min ?? '', $item->valor_max ?? '', $item->umedida ?? '');
                                if ($refMostrar === '-' && trim((string)($item->umedida ?? '')) === '' && !((bool)($item->show_reference ?? false))) {
                                    continue;
                                }
                                ?>
                                    <tr>
                                        <td><?= esc($item->nombre ?? '') ?></td>
                                        <td class="text-center"><?= esc($refMostrar) ?></td>
                                    </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        <?php endforeach; ?>
    </div>
<?php else: ?>
    <p>No hay análisis disponibles.</p>
<?php endif; ?>
