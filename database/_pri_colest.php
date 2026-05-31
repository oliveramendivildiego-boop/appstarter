<?php
$m = new mysqli('localhost', 'root', '', 'laboratorio');
foreach (['Colesterol', 'Urea', 'Amilasa', 'Acido Urico'] as $t) {
    echo "=== $t ===\n";
    $r = $m->query("SELECT pr.priresultados_id, pr.id_poblacion, pr.sexo, ana.name cat
      FROM dom_priresultados pr
      JOIN dom_prianacategoria pri ON pri.prianacategoria_id=pr.prianacategoria_id
      JOIN dom_anacategoria ana ON ana.anacategoria_id=pri.anacategoria_id
      WHERE pri.name LIKE '%$t%' AND (pr.deleted=0 OR pr.deleted IS NULL)
      ORDER BY pr.id_poblacion, pr.sexo LIMIT 20");
    while ($x = $r->fetch_assoc()) {
        echo json_encode($x, JSON_UNESCAPED_UNICODE) . "\n";
    }
}
