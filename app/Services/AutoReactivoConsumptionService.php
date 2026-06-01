<?php

namespace App\Services;

use App\Models\LabotestReactivoConfigModel;
use App\Services\RegisterService;
use App\Models\ReactivoModel;
use App\Models\RegisterModel;
use CodeIgniter\Database\BaseConnection;

class AutoReactivoConsumptionService
{
    private BaseConnection $db;
    private RegisterModel $registerModel;
    private ReactivoModel $reactivoModel;
    private LabotestReactivoConfigModel $configModel;

    public function __construct()
    {
        $this->db            = \Config\Database::connect();
        $this->registerModel = new RegisterModel();
        $this->reactivoModel = new ReactivoModel();
        $this->configModel   = new LabotestReactivoConfigModel();
    }

    /**
     * @param array<int, array<string, mixed>> $regValues
     * @return array<string, mixed>
     */
    public function applyFromRegValues(int $registroId, array $regValues, int $personId): array
    {
        if (! $this->db->tableExists('labotest_reactivo_config') || ! $this->db->tableExists('reactivo_consumo_auto')) {
            return ['aplicados' => 0, 'omitidos' => 0, 'errores' => 0];
        }

        $prianacategoriaIds = $this->extractPrianacategoriaIds($regValues);
        if (empty($prianacategoriaIds)) {
            return ['aplicados' => 0, 'omitidos' => 0, 'errores' => 0];
        }

        $configs = $this->configModel->getByPrianacategoriaIds($prianacategoriaIds);
        if (empty($configs)) {
            return ['aplicados' => 0, 'omitidos' => 0, 'errores' => 0];
        }

        $stats = ['aplicados' => 0, 'omitidos' => 0, 'errores' => 0];

        foreach ($configs as $cfg) {
            $prianacategoriaId = (int) ($cfg['prianacategoria_id'] ?? 0);
            $reactivoId        = (int) ($cfg['reactivo_id'] ?? 0);
            $cantidad          = max(1, (int) ($cfg['consumo_default'] ?? 1));
            $lotePolicy        = (string) ($cfg['lote_policy'] ?? 'fefo');

            if ($prianacategoriaId <= 0 || $reactivoId <= 0) {
                $stats['omitidos']++;
                continue;
            }

            $status = $this->getExistingStatus($registroId, $prianacategoriaId, $reactivoId);
            if ($status === 'aplicado') {
                $stats['omitidos']++;
                continue;
            }

            $loteId = $this->reactivoModel->getLoteIdForAutoSalida($reactivoId, $lotePolicy);
            if ($loteId === null) {
                $this->upsertTracking($registroId, $prianacategoriaId, $reactivoId, $cantidad, null, 'fallido', 'Sin lote con stock suficiente');
                $stats['errores']++;
                continue;
            }

            $observaciones = sprintf(
                'Consumo automatico por prueba. AUTO_TEST:%d CFG:%d',
                $prianacategoriaId,
                (int) ($cfg['config_id'] ?? 0)
            );

            $ok = $this->reactivoModel->registrarSalida($reactivoId, $cantidad, $personId, $observaciones, $registroId, $loteId);
            if (! $ok) {
                $this->upsertTracking($registroId, $prianacategoriaId, $reactivoId, $cantidad, $loteId, 'fallido', 'No se pudo registrar salida automatica');
                $stats['errores']++;
                continue;
            }

            $this->upsertTracking($registroId, $prianacategoriaId, $reactivoId, $cantidad, $loteId, 'aplicado', null);
            $stats['aplicados']++;
        }

        return $stats;
    }

    /**
     * @param array<int, array<string, mixed>> $regValues
     * @return array<int, int>
     */
    private function extractPrianacategoriaIds(array $regValues): array
    {
        $nocache = [];
        $cache   = [];
        $ids     = [];

        foreach ($regValues as $item) {
            $valor = trim((string) ($item['valor'] ?? ''));
            if ($valor === '') {
                continue;
            }

            $name = (string) ($item['name'] ?? $item['id'] ?? '');
            if ($name === '') {
                continue;
            }

            $id = $this->registerModel->resolvePrianacategoriaIdFromRegvalueName($name, $nocache, $cache);
            if ($id > 0) {
                $ids[] = $id;
            }
        }

        return array_values(array_unique($ids));
    }

    private function getExistingStatus(int $registroId, int $prianacategoriaId, int $reactivoId): ?string
    {
        $row = $this->db->table('reactivo_consumo_auto')
            ->select('estado')
            ->where('registro_id', $registroId)
            ->where('prianacategoria_id', $prianacategoriaId)
            ->where('reactivo_id', $reactivoId)
            ->get()
            ->getRowArray();

        return $row['estado'] ?? null;
    }

    private function upsertTracking(
        int $registroId,
        int $prianacategoriaId,
        int $reactivoId,
        int $cantidad,
        ?int $loteId,
        string $estado,
        ?string $mensaje
    ): void {
        $data = [
            'registro_id'       => $registroId,
            'prianacategoria_id' => $prianacategoriaId,
            'reactivo_id'       => $reactivoId,
            'cantidad'          => $cantidad,
            'lote_id'           => $loteId,
            'estado'            => $estado,
            'mensaje'           => $mensaje,
            'updated_at'        => RegisterService::mysqlNowForReport(),
        ];

        $existing = $this->db->table('reactivo_consumo_auto')
            ->select('auto_consumo_id')
            ->where('registro_id', $registroId)
            ->where('prianacategoria_id', $prianacategoriaId)
            ->where('reactivo_id', $reactivoId)
            ->get()
            ->getRowArray();

        if ($existing) {
            $this->db->table('reactivo_consumo_auto')
                ->where('auto_consumo_id', (int) $existing['auto_consumo_id'])
                ->update($data);
            return;
        }

        $data['created_at'] = $data['updated_at'];
        $this->db->table('reactivo_consumo_auto')->insert($data);
    }
}

