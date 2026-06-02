<?php

namespace App\Services;

use App\Models\AppConfigModel;
use App\Models\RegisterModel;
use Config\Database;

/**
 * Número visible en el recibo de pago: rango propio (inicio–fin) o número de orden por defecto.
 */
class ComprobanteReciboNumeroService
{
    public function __construct(
        private ?AppConfigModel $appConfigModel = null,
        private ?RegisterModel $registerModel = null,
    ) {
        $this->appConfigModel = $appConfigModel ?? model(AppConfigModel::class);
        $this->registerModel  = $registerModel ?? model(RegisterModel::class);
    }

    /**
     * @param array<string, mixed>|null $config
     *
     * @return array{active: bool, inicio: int, fin: int}
     */
    public function rangoConfig(?array $config = null): array
    {
        if ($config === null) {
            $config = [];
            foreach (['comprobante_recibo_num_rango_activo', 'comprobante_recibo_num_inicio', 'comprobante_recibo_num_fin'] as $key) {
                $config[$key] = $this->appConfigModel->getValue($key);
            }
        }

        $active = ((string) ($config['comprobante_recibo_num_rango_activo'] ?? '0')) === '1';
        $inicio = max(0, (int) ($config['comprobante_recibo_num_inicio'] ?? 0));
        $fin    = max(0, (int) ($config['comprobante_recibo_num_fin'] ?? 0));

        if (! $active || $inicio < 1 || $fin < 1 || $inicio > $fin) {
            return ['active' => false, 'inicio' => 0, 'fin' => 0];
        }

        return ['active' => true, 'inicio' => $inicio, 'fin' => $fin];
    }

    /**
     * Número a imprimir en el recibo (badge «N.º …»).
     */
    public function resolveNumeroRecibo(int $registroId, object $reg, ?object $pago = null): string
    {
        helper('registro');

        $default = registro_orden_display($reg);
        $rango   = $this->rangoConfig();

        if (! $rango['active']) {
            return $default;
        }

        if ($pago === null) {
            $pago = $this->registerModel->getPagoByRegistroId($registroId);
        }
        if ($pago === null) {
            return $default;
        }

        $stored = trim((string) ($pago->numero_recibo ?? ''));
        if ($stored !== '') {
            return $stored;
        }

        $asignado = $this->asignarSiguienteEnRango($registroId, $rango['inicio'], $rango['fin']);
        if ($asignado === null) {
            return $default;
        }

        return $asignado;
    }

    /**
     * Vista previa en configuración (sin asignar en BD).
     */
    public function previewNumero(?array $config = null): string
    {
        $rango = $this->rangoConfig($config);
        if (! $rango['active']) {
            return '2026-0042';
        }

        $ultimo = $this->leerUltimoAsignado($rango['inicio'], $rango['fin']);
        $next   = $ultimo !== null ? min($rango['fin'], $ultimo + 1) : $rango['inicio'];
        if ($next > $rango['fin']) {
            return '2026-0042';
        }

        return $this->formatearNumero($next, $rango['fin']);
    }

    private function asignarSiguienteEnRango(int $registroId, int $inicio, int $fin): ?string
    {
        if (! $this->dbTieneColumnaNumeroRecibo()) {
            return null;
        }

        $db = Database::connect();
        $db->transBegin();
        try {
            $ultimo = $this->leerUltimoAsignado($inicio, $fin);
            $next   = $ultimo !== null ? $ultimo + 1 : $inicio;
            if ($next < $inicio) {
                $next = $inicio;
            }
            if ($next > $fin) {
                $db->transRollback();

                return null;
            }

            $formateado = $this->formatearNumero($next, $fin);
            $this->registerModel->setNumeroRecibo($registroId, $formateado);
            $this->appConfigModel->batchSave(['comprobante_recibo_ultimo_num' => (string) $next]);

            $db->transCommit();

            return $formateado;
        } catch (\Throwable $e) {
            $db->transRollback();
            throw $e;
        }
    }

    private function leerUltimoAsignado(int $inicio, int $fin): ?int
    {
        $cfgUltimo = (int) $this->appConfigModel->getValue('comprobante_recibo_ultimo_num');
        $maxDb     = $this->maxNumeroReciboEnPago();

        $candidatos = array_filter([$cfgUltimo, $maxDb], static fn (int $n): bool => $n >= $inicio && $n <= $fin);
        if ($candidatos === []) {
            return null;
        }

        return max($candidatos);
    }

    private function maxNumeroReciboEnPago(): int
    {
        if (! $this->dbTieneColumnaNumeroRecibo()) {
            return 0;
        }

        $db  = Database::connect();
        $tbl = $db->prefixTable('pago');
        $row = $db->query(
            "SELECT MAX(CAST(numero_recibo AS UNSIGNED)) AS m FROM {$tbl} WHERE numero_recibo IS NOT NULL AND numero_recibo REGEXP '^[0-9]+$'"
        )->getRowArray();

        return (int) ($row['m'] ?? 0);
    }

    public function formatearNumero(int $num, int $fin): string
    {
        $width = max(strlen((string) $fin), strlen((string) $num));

        return str_pad((string) $num, $width, '0', STR_PAD_LEFT);
    }

    private function dbTieneColumnaNumeroRecibo(): bool
    {
        $db = Database::connect();

        return $db->tableExists('pago') && $db->fieldExists('numero_recibo', 'pago');
    }
}
