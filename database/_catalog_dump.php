<?php
$m = new mysqli('localhost', 'root', '', 'laboratorio');
$m->set_charset('utf8mb4');
$r = $m->query("SELECT ana.name cat, pri.prianacategoria_id, pri.name prueba, pri.compleja,
  (SELECT COUNT(*) FROM dom_priresultados pr WHERE pr.prianacategoria_id=pri.prianacategoria_id AND (pr.deleted=0 OR pr.deleted IS NULL)) npri,
  (SELECT COUNT(*) FROM dom_secanacategoria s WHERE s.prianacategoria_id=pri.prianacategoria_id AND (s.deleted=0 OR s.deleted IS NULL)) nsec
  FROM dom_prianacategoria pri
  JOIN dom_anacategoria ana ON ana.anacategoria_id=pri.anacategoria_id
  WHERE (pri.deleted=0 OR pri.deleted IS NULL) AND (ana.deleted=0 OR ana.deleted IS NULL)
  ORDER BY ana.name, pri.name");
file_put_contents(__DIR__ . '/_catalog.txt', '');
while ($row = $r->fetch_assoc()) {
    file_put_contents(__DIR__ . '/_catalog.txt', 
        $row['cat'] . '|' . $row['prianacategoria_id'] . '|' . $row['prueba'] . '|c=' . $row['compleja'] . '|pri=' . $row['npri'] . '|sec=' . $row['nsec'] . "\n",
        FILE_APPEND);
}
