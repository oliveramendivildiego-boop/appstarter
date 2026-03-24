<?php if (!empty($grupos)): ?>
    <div class="container mt-4">
        <?php foreach ($grupos as $padre => $items): ?>
            <?php
            $conResultado = [];
            $sinResultado = [];
            foreach ($items as $it) {
                $valTmp = trim((string)($it->regvalues ?? ''));
                if ($valTmp === '' || $valTmp === '-') $sinResultado[] = $it;
                else $conResultado[] = $it;
            }
            $hasUnidad = !empty(array_filter($conResultado, fn($it) => trim($it->umedida ?? '') !== ''));
            $hasRango = !empty(array_filter($conResultado, fn($it) => (bool)($it->show_reference ?? false)));
            ?>
            <h4 class="mt-4"><?= esc($padre) ?> - <?= esc($items[0]->hijo ?? '') ?></h4>
            <div class="table-responsive">
            <table class="table">
                <thead class="thead-dark">
                    <tr>
                        <th>ANÁLISIS</th>
                        <th class="text-center">RESULTADO</th>
                        <?php if ($hasUnidad): ?><th class="text-center">UNID</th><?php endif; ?>
                        <?php if ($hasRango): ?><th class="text-center">RANGO REFERENCIAL</th><?php endif; ?>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($conResultado as $item): ?>
					<?php $aid = $item->secanacategoria_id ?? uniqid(); ?>
					<input type="hidden" id="analisis_<?= esc($aid) ?>" name="analisis_<?= esc($aid) ?>" class="analisis" padre="<?= esc($padre) ?>" hijo="<?= esc($items[0]->hijo ?? '') ?>" analisis="<?= esc($item->nombre ?? '') ?>" value="<?= esc($item->regvalues ?? '') ?>" unidad="<?= esc($item->umedida ?? '') ?>" min="<?= esc($item->valor_min ?? '') ?>" max="<?= esc($item->valor_max ?? '') ?>">
					<?php
						$val = $item->regvalues ?? '';
						if (!$val) { $item->regvalues = '-'; $val = '-'; }
						$valNorm = trim(strtolower($val));
						if (in_array($valNorm, ['positivo', 'reactivo'], true)) {
							$class = 'text-danger font-weight-bold';
						} elseif (is_numeric($val) && ($item->valor_min ?? '') !== '' && ($item->valor_max ?? '') !== '') {
							$class = ($val >= $item->valor_min && $val <= $item->valor_max) ? 'normal' : 'text-danger font-weight-bold';
						} else {
							$class = 'normal';
						}
						$mostrarRangoItem = (bool)($item->show_reference ?? false);
					?>
                        <?php if (is_object($item)): ?>
                            <tr>
                                <td><?= esc($item->nombre ?? '') ?></td>
                                <td class="text-center <?= $class ?>"><?= esc($item->regvalues ?? '') ?></td>
                                <?php if ($hasUnidad): ?><td class="text-center"><?= esc($item->umedida ?? '') ?></td><?php endif; ?>
                                <?php if ($hasRango): ?>
                                    <?php if ($mostrarRangoItem): ?>
                                        <?php $ref = trim(($item->valor_min ?? '') . ' - ' . ($item->valor_max ?? '')); if ($ref === '' || $ref === '-') $ref = '-'; ?>
                                        <td class="text-center"><?= esc($ref) ?></td>
                                    <?php else: ?>
                                        <td class="text-center">-</td>
                                    <?php endif; ?>
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
                                <th class="text-center">UNIDAD</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($sinResultado as $item): ?>
                                <?php if ((bool)($item->show_reference ?? false)): ?>
                                    <?php $ref = trim(($item->valor_min ?? '') . ' - ' . ($item->valor_max ?? '')); if ($ref === '' || $ref === '-') $ref = '-'; ?>
                                    <tr>
                                        <td><?= esc($item->nombre ?? '') ?></td>
                                        <td class="text-center"><?= esc($ref) ?></td>
                                        <td class="text-center"><?= esc($item->umedida ?? '') ?></td>
                                    </tr>
                                <?php endif; ?>
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