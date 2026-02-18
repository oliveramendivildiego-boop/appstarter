<?php if (!empty($grupos)): ?>
    <div class="container mt-4">
        <?php foreach ($grupos as $padre => $items): ?>
		  <?php //print_r($items); ?>
            <h4 class="mt-4"><?= $padre ?> - <?= $items[0]->hijo ?></h4>
            <table class="table">
                <thead class="thead-dark">
                    <tr>
                        <th>ANALISIS</th>
                        <th class="text-center">RESULTADO</th>
                        <th class="text-center">UNID</th>
                        <th class="text-center">RANGO REFERENCIAL</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($items as $item): ?>
					<?php $aid = $item->secanacategoria_id ?? uniqid(); ?>
					<input type="hidden" id="analisis_<?= esc($aid) ?>" name="analisis_<?= esc($aid) ?>" class="analisis" padre="<?= esc($padre) ?>" hijo="<?= esc($items[0]->hijo ?? '') ?>" analisis="<?= esc($item->nombre ?? '') ?>" value="<?= esc($item->regvalues ?? '') ?>" unidad="<?= esc($item->umedida ?? '') ?>" min="<?= esc($item->valor_min ?? '') ?>" max="<?= esc($item->valor_max ?? '') ?>">
					<?php if($item->regvalues >= $item->valor_min && $item->regvalues <= $item->valor_max){
						$class="normal";
					    }else{
						$class="text-danger font-weight-bold";						
					    } 
						if(!$item->regvalues){
						$item->regvalues='-';
					    }
					?>
                        <?php if(is_object($item)): ?>
                            <tr>
                                <td><?= $item->nombre ?></td>
                                <td class="text-center <?= $class ?>"><?= $item->regvalues ?></td>
								<td class="text-center"><?= $item->umedida ?></td>
                                <td class="text-center"><?= $item->valor_min ?> - <?= $item->valor_max ?></td>
          
                            </tr>
                        <?php endif; ?>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php endforeach; ?>
    </div>
<?php else: ?>
    <p>No hay análisis disponibles.</p>
<?php endif; ?>