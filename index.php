<?php

/**
 * Bootstrap para acceso vía subdirectorio (p. ej. http://localhost/laboratorio/).
 * En WAMP, el virtual host laboratorio.local debe apuntar directamente a public/.
 */

chdir(__DIR__ . DIRECTORY_SEPARATOR . 'public');
require __DIR__ . '/public/index.php';
