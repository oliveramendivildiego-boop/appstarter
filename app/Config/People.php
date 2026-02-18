<?php

namespace Config;

use CodeIgniter\Config\BaseConfig;

/**
 * Configuración del esquema de la tabla people.
 * Define si se usa last_name (único) o last_name_fa + last_name_mom (separados).
 *
 * En .env:
 *   people.useSplitNames = true  → usa last_name_fa, last_name_mom (laboratorio.sql)
 *   people.useSplitNames = false → usa last_name (OSPOS estándar, por defecto)
 */
class People extends BaseConfig
{
    public function __construct()
    {
        $this->useSplitNames = (bool) env('people.useSplitNames', false);
    }

    public bool $useSplitNames = false;
}
