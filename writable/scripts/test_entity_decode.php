<?php
foreach (['&#x3B', '&#59;', '&semi;'] as $e) {
    $s = 'color:red' . $e;
    echo $s . ' => ' . html_entity_decode($s, ENT_QUOTES | ENT_HTML5, 'UTF-8') . PHP_EOL;
    echo $s . ' => ' . html_entity_decode($s, ENT_QUOTES, 'UTF-8') . PHP_EOL;
}
