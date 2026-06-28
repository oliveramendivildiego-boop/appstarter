<?php

namespace App\Commands;

use App\Models\ReportAnalyticsModel;
use CodeIgniter\CLI\BaseCommand;
use CodeIgniter\CLI\CLI;

/**
 * Smoke test de los 10 reportes analíticos nuevos (solo lectura).
 *
 *   php spark reports:analytics-smoke [start] [end]
 *
 * Ejecuta cada consulta del ReportAnalyticsModel y muestra filas devueltas
 * y tiempo de ejecución. No escribe nada en la base de datos.
 */
class ReportsAnalyticsSmoke extends BaseCommand
{
    protected $group       = 'Reportes';
    protected $name        = 'reports:analytics-smoke';
    protected $description = 'Ejecuta los 10 reportes analíticos nuevos y mide su rendimiento (solo SELECT).';

    public function run(array $params)
    {
        $start = $params[0] ?? date('Y-m-d', strtotime('-2 years'));
        $end   = $params[1] ?? date('Y-m-d');

        // Tercer parámetro opcional: nombre de BD alternativa (pruebas de rendimiento)
        if (! empty($params[2])) {
            config(\Config\Database::class)->default['database'] = (string) $params[2];
            CLI::write('Usando base de datos: ' . $params[2], 'yellow');
        }

        $model = model(ReportAnalyticsModel::class);

        $pruebas = [
            '1. Pruebas más solicitadas' => static fn () => count($model->getPruebasMasSolicitadas($start, $end)['rows']),
            '2. Tendencia por paciente (1er paciente con órdenes)' => static function () use ($model, $start, $end) {
                $row = \Config\Database::connect()->table('registro')->select('person_id')->limit(1)->get()->getRowArray();

                return $row ? count($model->getTendenciaPaciente((int) $row['person_id'], $start, $end)) : 0;
            },
            '3. Valores críticos (detalle + indicadores)' => static fn () => $model->getValoresCriticos($start, $end)['total'],
            '3b. Historial por prueba (1ª prueba del catálogo)' => static function () use ($model, $start, $end) {
                $row = \Config\Database::connect()->table('prianacategoria')->select('prianacategoria_id')->where('deleted', 0)->limit(1)->get()->getRowArray();

                return $row ? count($model->getHistorialPorPrueba((int) $row['prianacategoria_id'], $start, $end)) : 0;
            },
            '4a. Tiempo de entrega — detalle' => static fn () => count($model->getTiempoEntrega($start, $end)),
            '4b. Tiempo de entrega — resumen SLA' => static fn () => (int) $model->getTiempoEntregaResumen($start, $end, 24)['ordenes'],
            '5. Productividad por usuario' => static fn () => count($model->getProductividadUsuarios($start, $end)),
            '5b. Notificaciones de entrega' => static function () use ($model, $start, $end) {
                $det = count($model->getNotificacionesEntregaDetalle($start, $end));
                $aud = $model->countNotificacionesEntregaAuditoria($start, $end);

                return $det + $aud;
            },
            '6. Resultados corregidos' => static fn () => count($model->getResultadosCorregidos($start, $end)),
            '7. Pendientes de validar (detalle + total)' => static fn () => $model->getPendientesValidacion($start, $end)['total'],
            '8a. Consumo por prueba' => static fn () => count($model->getConsumoPorPrueba($start, $end)),
            '8b. Consumo por reactivo' => static fn () => count($model->getConsumoPorReactivo($start, $end)),
            '8c. Consumo por mes' => static fn () => count($model->getConsumoPorPeriodo($start, $end, 'mes')),
            '9. Proyección de agotamiento' => static fn () => count($model->getProyeccionInsumos(30)),
            '10. Comparativo mensual' => static fn () => count($model->getComparativoMensual($start, $end)),
        ];

        CLI::write("Rango: {$start} → {$end}", 'yellow');
        $todoOk = true;
        foreach ($pruebas as $nombre => $fn) {
            $t0 = microtime(true);
            try {
                $filas = $fn();
                $ms    = round((microtime(true) - $t0) * 1000, 1);
                $color = $ms < 5000 ? 'green' : 'red';
                CLI::write(sprintf('%-55s %6d filas  %8.1f ms', $nombre, $filas, $ms), $color);
            } catch (\Throwable $e) {
                $todoOk = false;
                CLI::error(sprintf('%-55s ERROR: %s', $nombre, $e->getMessage()));
            }
        }
        CLI::write($todoOk ? 'Smoke test completado sin errores.' : 'Smoke test con errores.', $todoOk ? 'green' : 'red');
    }
}
