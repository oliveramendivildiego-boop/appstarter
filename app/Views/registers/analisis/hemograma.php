<?php if (!empty($grupos)): ?>
    <div class="container mt-4">
        <?php foreach ($grupos as $padre => $items): ?>
            <?php
            $hasUnidad = false;
            $hasRango = false;
            foreach ($items as $it) {
                if (trim($it->umedida ?? '') !== '') $hasUnidad = true;
                if (trim($it->valor_min ?? '') !== '' || trim($it->valor_max ?? '') !== '') $hasRango = true;
            }
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
                    <?php foreach ($items as $item): ?>
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
					?>
                        <?php if (is_object($item)): ?>
                            <tr>
                                <td><?= esc($item->nombre ?? '') ?></td>
                                <td class="text-center <?= $class ?>"><?= esc($item->regvalues ?? '') ?></td>
                                <?php if ($hasUnidad): ?><td class="text-center"><?= esc($item->umedida ?? '') ?></td><?php endif; ?>
                                <?php if ($hasRango): ?><td class="text-center"><?= esc(trim(($item->valor_min ?? '') . ' - ' . ($item->valor_max ?? ''))) ?></td><?php endif; ?>
                            </tr>
                        <?php endif; ?>
                    <?php endforeach; ?>
                </tbody>
            </table>
            </div>
        <?php endforeach; ?>
    </div>
<?php else: ?>
    <p>No hay análisis disponibles.</p>
<?php endif; ?>