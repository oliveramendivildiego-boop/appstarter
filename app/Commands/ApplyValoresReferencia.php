<?php

namespace App\Commands;

use App\Services\ValoresReferenciaInstaller;
use CodeIgniter\CLI\BaseCommand;
use CodeIgniter\CLI\CLI;
use Config\Database;

/**
 * Aplica valores de referencia del catálogo a todas las pruebas simples,
 * normaliza id_poblacion legacy y corrige subclases del hemograma compuesto.
 *
 * php spark lab:apply-valores-referencia
 */
class ApplyValoresReferencia extends BaseCommand
{
    protected $group       = 'Laboratorio';
    protected $name        = 'lab:apply-valores-referencia';
    protected $description = 'Inserta/actualiza valores de referencia (priresultados) y corrige hemograma compuesto';

    public function run(array $params)
    {
        $db = Database::connect();
        $db->transStart();

        ValoresReferenciaInstaller::apply($db);

        $db->transComplete();
        if ($db->transStatus() === false) {
            CLI::error('Transacción fallida.');

            return;
        }
        CLI::write('Valores de referencia aplicados correctamente.', 'green');
    }
}
