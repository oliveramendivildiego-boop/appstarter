<?php

namespace App\Services;

use App\Models\PerfilExamenModel;
use App\Models\RegisterModel;

class PrianacategoriaReferenceService
{
    protected $db;
    protected RegisterModel $registerModel;
    protected PerfilExamenModel $perfilModel;

    public function __construct()
    {
        $this->db = \Config\Database::connect();
        $this->registerModel = model(RegisterModel::class);
        $this->perfilModel = model(PerfilExamenModel::class);
    }

    /**
     * @return array{
     *   success: bool,
     *   analysis_id: int,
     *   analysis_name: string,
     *   category_name: string,
     *   registros_count: int,
     *   regvalues_with_data: int,
     *   has_registered_values: bool,
     *   can_migrate: bool,
     *   migrate_to_id: ?int,
     *   migrate_to_name: ?string,
     *   migrate_to_category: ?string,
     *   siblings: list<array{id:int,name:string,category_name:string}>
     * }
     */
    public function getDeleteImpact(int $prianacategoriaId): array
    {
        $analysis = $this->getAnalysisRow($prianacategoriaId);
        if ($analysis === null) {
            return ['success' => false, 'message' => 'Análisis no encontrado'];
        }

        $siblings = $this->findDuplicateSiblings($prianacategoriaId, trim((string) ($analysis['name'] ?? '')));
        $migrateTarget = $this->pickMigrationTarget($siblings);
        $usage = $this->countRegisterUsage($prianacategoriaId);

        return [
            'success'               => true,
            'analysis_id'           => $prianacategoriaId,
            'analysis_name'         => trim((string) ($analysis['name'] ?? '')),
            'category_name'         => trim((string) ($analysis['category_name'] ?? '')),
            'registros_count'       => $usage['registros_count'],
            'regvalues_with_data'   => $usage['regvalues_with_data'],
            'has_registered_values' => $usage['has_registered_values'],
            'can_migrate'           => $migrateTarget !== null,
            'migrate_to_id'         => $migrateTarget['id'] ?? null,
            'migrate_to_name'       => $migrateTarget['name'] ?? null,
            'migrate_to_category'   => $migrateTarget['category_name'] ?? null,
            'siblings'              => $siblings,
        ];
    }

    public function isValidMigrationTarget(int $sourceId, int $targetId): bool
    {
        if ($sourceId < 1 || $targetId < 1 || $sourceId === $targetId) {
            return false;
        }

        $source = $this->getAnalysisRow($sourceId);
        $target = $this->getAnalysisRow($targetId);
        if ($source === null || $target === null) {
            return false;
        }

        return $this->canMigrateByName($sourceId, $targetId);
    }

    private function canMigrateByName(int $sourceId, int $targetId): bool
    {
        if ($sourceId < 1 || $targetId < 1 || $sourceId === $targetId) {
            return false;
        }

        $source = $this->getAnalysisRowAny($sourceId);
        $target = $this->getAnalysisRow($targetId);
        if ($source === null || $target === null) {
            return false;
        }

        return $this->normalizeName((string) ($source['name'] ?? '')) === $this->normalizeName((string) ($target['name'] ?? ''));
    }

    /**
     * @return array{registros_updated:int,regvalues_updated:int,perfiles_updated:int,reactivo_tracking_updated:int}
     */
    public function migrateReferences(int $fromId, int $toId): array
    {
        if (! $this->isValidMigrationTarget($fromId, $toId)) {
            return [
                'registros_updated'          => 0,
                'regvalues_updated'          => 0,
                'perfiles_updated'           => 0,
                'reactivo_tracking_updated'  => 0,
            ];
        }

        $stats = [
            'registros_updated'          => 0,
            'regvalues_updated'          => 0,
            'perfiles_updated'           => 0,
            'reactivo_tracking_updated'  => 0,
        ];

        $secMap = $this->buildSecanacategoriaMap($fromId, $toId);
        $priResMap = $this->buildPriresultadosMap($fromId, $toId);
        $registroIds = $this->getRegistroIdsForMigration($fromId, $toId);

        // Primero regvalues (aún con claves del análisis origen), luego registro.pruebas.
        $stats['regvalues_updated'] = $this->migrateRegvalues($fromId, $toId, $secMap, $priResMap, $registroIds);
        $stats['registros_updated'] = $this->migrateRegistroPruebas($fromId, $toId, $registroIds);
        $stats['perfiles_updated'] = $this->migratePerfilPruebas($fromId, $toId);
        $stats['reactivo_tracking_updated'] = $this->migrateReactivoTracking($fromId, $toId);

        return $stats;
    }

    /**
     * Repara valores huérfanos cuando pruebas ya apuntan al duplicado pero regvalues quedaron con claves viejas.
     *
     * @return array{regvalues_updated:int}
     */
    public function repairMigratedRegvalues(int $fromId, int $toId): array
    {
        if (! $this->canMigrateByName($fromId, $toId)) {
            return ['regvalues_updated' => 0];
        }

        $secMap = $this->buildSecanacategoriaMap($fromId, $toId);
        $priResMap = $this->buildPriresultadosMap($fromId, $toId);
        $registroIds = $this->getRegistroIdsContainingPrueba($toId);

        return [
            'regvalues_updated' => $this->migrateRegvalues($fromId, $toId, $secMap, $priResMap, $registroIds),
        ];
    }

    /**
     * Corrige claves de regvalues que siguen apuntando a un análisis duplicado eliminado
     * mientras la orden ya referencia el análisis activo con el mismo nombre.
     */
    public function repairRegvaluesForRegistro(int $registroId): int
    {
        if ($registroId < 1) {
            return 0;
        }

        $registro = $this->db->table('registro')
            ->select('pruebas')
            ->where('registro_id', $registroId)
            ->get()
            ->getRowArray();
        if (! $registro) {
            return 0;
        }

        $activeIds = array_values(array_unique(array_filter(array_map(
            'intval',
            explode(',', (string) ($registro['pruebas'] ?? ''))
        ), static fn(int $id): bool => $id > 0)));
        if ($activeIds === []) {
            return 0;
        }

        $activeByName = [];
        foreach ($this->getAnalysisRowsByIds($activeIds) as $row) {
            $id = (int) ($row['prianacategoria_id'] ?? 0);
            $name = $this->normalizeName((string) ($row['name'] ?? ''));
            if ($id > 0 && $name !== '' && ! isset($activeByName[$name])) {
                $activeByName[$name] = $id;
            }
        }

        $updated = 0;
        $nocCache = [];
        $cCache = [];
        foreach ($this->registerModel->getInfoAnalisis($registroId) as $rvRow) {
            $regvaluesId = (int) ($rvRow['regvalues_id'] ?? 0);
            $name = (string) ($rvRow['name'] ?? '');
            if ($regvaluesId < 1 || trim($name) === '') {
                continue;
            }

            $sourceId = $this->registerModel->resolvePrianacategoriaIdFromRegvalueName($name, $nocCache, $cCache);
            if ($sourceId < 1) {
                $sourceId = $this->extractPrianacategoriaIdFromDirectName($name);
            }
            if ($sourceId < 1 || in_array($sourceId, $activeIds, true)) {
                continue;
            }

            $sourceRow = $this->getAnalysisRowAny($sourceId);
            if ($sourceRow === null) {
                continue;
            }

            $normalized = $this->normalizeName((string) ($sourceRow['name'] ?? ''));
            $targetId = (int) ($activeByName[$normalized] ?? 0);
            if ($targetId < 1 || $targetId === $sourceId || ! $this->canMigrateByName($sourceId, $targetId)) {
                continue;
            }

            $secMap = $this->buildSecanacategoriaMap($sourceId, $targetId);
            $priResMap = $this->buildPriresultadosMap($sourceId, $targetId);
            $newName = $this->mapRegvalueName($name, $sourceId, $targetId, $secMap, $priResMap);
            if ($newName === $name) {
                continue;
            }

            $this->db->table('regvalues')
                ->where('regvalues_id', $regvaluesId)
                ->update(['name' => $newName]);
            $updated++;
        }

        return $updated;
    }

    /**
     * Quita el análisis de órdenes registradas cuando no hay duplicado al cual migrar.
     *
     * @return array{registros_updated:int,regvalues_deleted:int}
     */
    public function detachFromRegisters(int $prianacategoriaId): array
    {
        $registroRows = $this->db->table('registro')
            ->select('registro_id, pruebas')
            ->where('FIND_IN_SET(' . (int) $prianacategoriaId . ', pruebas) > 0', null, false)
            ->get()
            ->getResultArray();

        $registrosUpdated = 0;
        $regvaluesDeleted = 0;

        foreach ($registroRows as $row) {
            $registroId = (int) ($row['registro_id'] ?? 0);
            if ($registroId < 1) {
                continue;
            }

            $ids = array_values(array_unique(array_filter(array_map('intval', explode(',', (string) ($row['pruebas'] ?? ''))))));
            $ids = array_values(array_filter($ids, static fn(int $id): bool => $id !== $prianacategoriaId));
            $this->db->table('registro')
                ->where('registro_id', $registroId)
                ->update(['pruebas' => implode(',', $ids)]);
            $registrosUpdated++;

            $regvaluesDeleted += $this->deleteRegvaluesForAnalysis($registroId, $prianacategoriaId);
        }

        return [
            'registros_updated'  => $registrosUpdated,
            'regvalues_deleted'  => $regvaluesDeleted,
        ];
    }

    public function removeFromPerfiles(int $prianacategoriaId): int
    {
        $updated = 0;
        foreach ($this->perfilModel->getAll() as $perfil) {
            $perfilId = (int) ($perfil['perfil_id'] ?? 0);
            if ($perfilId < 1) {
                continue;
            }
            $ids = array_values(array_unique(array_filter(array_map('intval', explode(',', (string) ($perfil['pruebas'] ?? ''))))));
            if (! in_array($prianacategoriaId, $ids, true)) {
                continue;
            }
            $ids = array_values(array_filter($ids, static fn(int $id): bool => $id !== $prianacategoriaId));
            $this->perfilModel->savePerfil([
                'nombre'  => (string) ($perfil['nombre'] ?? ''),
                'pruebas' => $ids,
            ], $perfilId);
            $updated++;
        }

        return $updated;
    }

    /**
     * @return ?array{prianacategoria_id:int,name:string,category_name:string}
     */
    private function getAnalysisRow(int $prianacategoriaId): ?array
    {
        if ($prianacategoriaId < 1) {
            return null;
        }

        $row = $this->db->table('prianacategoria pri')
            ->select('pri.prianacategoria_id, pri.name, ana.name AS category_name')
            ->join('anacategoria ana', 'ana.anacategoria_id = pri.anacategoria_id', 'inner')
            ->where('pri.prianacategoria_id', $prianacategoriaId)
            ->where('(pri.deleted = 0 OR pri.deleted IS NULL)')
            ->get()
            ->getRowArray();

        return $row ?: null;
    }

    /**
     * @return ?array{prianacategoria_id:int,name:string,category_name:string}
     */
    private function getAnalysisRowAny(int $prianacategoriaId): ?array
    {
        if ($prianacategoriaId < 1) {
            return null;
        }

        $row = $this->db->table('prianacategoria pri')
            ->select('pri.prianacategoria_id, pri.name, ana.name AS category_name')
            ->join('anacategoria ana', 'ana.anacategoria_id = pri.anacategoria_id', 'left')
            ->where('pri.prianacategoria_id', $prianacategoriaId)
            ->get()
            ->getRowArray();

        return $row ?: null;
    }

    /**
     * @param list<int> $ids
     * @return list<array{prianacategoria_id:int,name:string}>
     */
    private function getAnalysisRowsByIds(array $ids): array
    {
        $ids = array_values(array_unique(array_filter(array_map('intval', $ids), static fn(int $id): bool => $id > 0)));
        if ($ids === []) {
            return [];
        }

        return $this->db->table('prianacategoria')
            ->select('prianacategoria_id, name')
            ->whereIn('prianacategoria_id', $ids)
            ->get()
            ->getResultArray();
    }

    private function extractPrianacategoriaIdFromDirectName(string $name): int
    {
        $name = trim($name);
        if ($name === '') {
            return 0;
        }

        if (preg_match('/^lab_(val|app)_pri_(\d+)$/', $name, $m) === 1) {
            return (int) $m[2];
        }

        if (preg_match('/^cvu_(\d+)$/', $name, $m) === 1) {
            return (int) $m[1];
        }

        if (preg_match('/^(?:cv|cvn|cvu)_(\d+)_/', $name, $m) === 1) {
            return (int) $m[1];
        }

        if (strpos($name, '|') !== false) {
            [$priaStr] = explode('|', $name, 2);

            return (int) trim($priaStr);
        }

        return 0;
    }

    /**
     * @return list<array{id:int,name:string,category_name:string}>
     */
    private function findDuplicateSiblings(int $prianacategoriaId, string $name): array
    {
        $normalized = $this->normalizeName($name);
        if ($normalized === '') {
            return [];
        }

        $rows = $this->db->table('prianacategoria pri')
            ->select('pri.prianacategoria_id, pri.name, ana.name AS category_name')
            ->join('anacategoria ana', 'ana.anacategoria_id = pri.anacategoria_id', 'inner')
            ->where('(pri.deleted = 0 OR pri.deleted IS NULL)')
            ->where('(ana.deleted = 0 OR ana.deleted IS NULL)')
            ->orderBy('pri.prianacategoria_id', 'ASC')
            ->get()
            ->getResultArray();

        $siblings = [];
        foreach ($rows as $row) {
            $id = (int) ($row['prianacategoria_id'] ?? 0);
            if ($id < 1 || $id === $prianacategoriaId) {
                continue;
            }
            if ($this->normalizeName((string) ($row['name'] ?? '')) !== $normalized) {
                continue;
            }
            $siblings[] = [
                'id'            => $id,
                'name'          => trim((string) ($row['name'] ?? '')),
                'category_name' => trim((string) ($row['category_name'] ?? '')),
            ];
        }

        return $siblings;
    }

    /**
     * @param list<array{id:int,name:string,category_name:string}> $siblings
     * @return ?array{id:int,name:string,category_name:string}
     */
    private function pickMigrationTarget(array $siblings): ?array
    {
        if ($siblings === []) {
            return null;
        }

        usort($siblings, static fn(array $a, array $b): int => ($a['id'] ?? 0) <=> ($b['id'] ?? 0));

        return $siblings[0];
    }

    /**
     * @return array{registros_count:int,regvalues_with_data:int,has_registered_values:bool}
     */
    private function countRegisterUsage(int $prianacategoriaId): array
    {
        $registroRows = $this->db->table('registro')
            ->select('registro_id')
            ->where('FIND_IN_SET(' . (int) $prianacategoriaId . ', pruebas) > 0', null, false)
            ->get()
            ->getResultArray();

        $registroIds = array_values(array_unique(array_filter(array_map(
            static fn(array $row): int => (int) ($row['registro_id'] ?? 0),
            $registroRows
        ), static fn(int $id): bool => $id > 0)));

        $regvaluesWithData = 0;
        if ($registroIds !== []) {
            $nocCache = [];
            $cCache = [];
            $rvRows = $this->db->table('regvalues')
                ->select('name, regvalues')
                ->whereIn('registro_id', $registroIds)
                ->get()
                ->getResultArray();

            foreach ($rvRows as $row) {
                $value = trim((string) ($row['regvalues'] ?? ''));
                if ($value === '' || $value === '-') {
                    continue;
                }
                $name = (string) ($row['name'] ?? '');
                if ($this->regvalueBelongsToAnalysis($name, $prianacategoriaId, $nocCache, $cCache)) {
                    $regvaluesWithData++;
                }
            }
        }

        return [
            'registros_count'       => count($registroIds),
            'regvalues_with_data'   => $regvaluesWithData,
            'has_registered_values' => count($registroIds) > 0 || $regvaluesWithData > 0,
        ];
    }

    /**
     * @return list<int>
     */
    private function getRegistroIdsContainingPrueba(int $prianacategoriaId): array
    {
        if ($prianacategoriaId < 1) {
            return [];
        }

        $rows = $this->db->table('registro')
            ->select('registro_id')
            ->where('FIND_IN_SET(' . (int) $prianacategoriaId . ', pruebas) > 0', null, false)
            ->get()
            ->getResultArray();

        return array_values(array_unique(array_filter(array_map(
            static fn(array $row): int => (int) ($row['registro_id'] ?? 0),
            $rows
        ), static fn(int $id): bool => $id > 0)));
    }

    /**
     * @return list<int>
     */
    private function getRegistroIdsForMigration(int $fromId, int $toId): array
    {
        return array_values(array_unique(array_merge(
            $this->getRegistroIdsContainingPrueba($fromId),
            $this->getRegistroIdsContainingPrueba($toId)
        )));
    }

    /**
     * @param list<int> $registroIds
     */
    private function migrateRegistroPruebas(int $fromId, int $toId, array $registroIds = []): int
    {
        if ($registroIds === []) {
            $registroIds = $this->getRegistroIdsContainingPrueba($fromId);
        }

        if ($registroIds === []) {
            return 0;
        }

        $rows = $this->db->table('registro')
            ->select('registro_id, pruebas')
            ->whereIn('registro_id', $registroIds)
            ->where('FIND_IN_SET(' . (int) $fromId . ', pruebas) > 0', null, false)
            ->get()
            ->getResultArray();

        $updated = 0;
        foreach ($rows as $row) {
            $registroId = (int) ($row['registro_id'] ?? 0);
            if ($registroId < 1) {
                continue;
            }
            $newCsv = $this->replaceIdInCsv((string) ($row['pruebas'] ?? ''), $fromId, $toId);
            $this->db->table('registro')
                ->where('registro_id', $registroId)
                ->update(['pruebas' => $newCsv]);
            $updated++;
        }

        return $updated;
    }

    private function migratePerfilPruebas(int $fromId, int $toId): int
    {
        $updated = 0;
        foreach ($this->perfilModel->getAll() as $perfil) {
            $perfilId = (int) ($perfil['perfil_id'] ?? 0);
            if ($perfilId < 1) {
                continue;
            }
            $csv = (string) ($perfil['pruebas'] ?? '');
            if (! $this->csvContainsId($csv, $fromId)) {
                continue;
            }
            $this->perfilModel->savePerfil([
                'nombre'  => (string) ($perfil['nombre'] ?? ''),
                'pruebas' => $this->replaceIdInCsv($csv, $fromId, $toId),
            ], $perfilId);
            $updated++;
        }

        return $updated;
    }

    /**
     * @param array<int,int> $secMap
     * @param array<int,int> $priResMap
     * @param list<int>      $registroIds
     */
    private function migrateRegvalues(int $fromId, int $toId, array $secMap, array $priResMap, array $registroIds = []): int
    {
        if ($registroIds === []) {
            $registroIds = $this->getRegistroIdsForMigration($fromId, $toId);
        }

        if ($registroIds === []) {
            return 0;
        }

        $nocCache = [];
        $cCache = [];
        $updated = 0;
        $rows = $this->db->table('regvalues')
            ->select('regvalues_id, name')
            ->whereIn('registro_id', $registroIds)
            ->get()
            ->getResultArray();

        foreach ($rows as $row) {
            $regvaluesId = (int) ($row['regvalues_id'] ?? 0);
            $name = (string) ($row['name'] ?? '');
            if ($regvaluesId < 1 || ! $this->regvalueBelongsToAnalysis($name, $fromId, $nocCache, $cCache)) {
                continue;
            }
            $newName = $this->mapRegvalueName($name, $fromId, $toId, $secMap, $priResMap);
            if ($newName === $name) {
                continue;
            }
            $this->db->table('regvalues')
                ->where('regvalues_id', $regvaluesId)
                ->update(['name' => $newName]);
            $updated++;
        }

        return $updated;
    }

    private function migrateReactivoTracking(int $fromId, int $toId): int
    {
        if (! $this->db->tableExists('reactivo_consumo_auto')) {
            return 0;
        }

        $rows = $this->db->table('reactivo_consumo_auto')
            ->select('auto_consumo_id')
            ->where('prianacategoria_id', $fromId)
            ->get()
            ->getResultArray();

        $updated = 0;
        foreach ($rows as $row) {
            $autoId = (int) ($row['auto_consumo_id'] ?? 0);
            if ($autoId < 1) {
                continue;
            }
            $this->db->table('reactivo_consumo_auto')
                ->where('auto_consumo_id', $autoId)
                ->update(['prianacategoria_id' => $toId]);
            $updated++;
        }

        return $updated;
    }

    /**
     * @return array<int,int>
     */
    private function buildSecanacategoriaMap(int $fromId, int $toId): array
    {
        $oldRows = $this->db->table('secanacategoria')
            ->select('secanacategoria_id, nombre')
            ->where('prianacategoria_id', $fromId)
            ->get()
            ->getResultArray();
        $newRows = $this->db->table('secanacategoria')
            ->select('secanacategoria_id, nombre')
            ->where('prianacategoria_id', $toId)
            ->where('(deleted = 0 OR deleted IS NULL)')
            ->get()
            ->getResultArray();

        $newByName = [];
        foreach ($newRows as $row) {
            $key = $this->normalizeName((string) ($row['nombre'] ?? ''));
            $newId = (int) ($row['secanacategoria_id'] ?? 0);
            if ($key !== '' && $newId > 0 && ! isset($newByName[$key])) {
                $newByName[$key] = $newId;
            }
        }

        $map = [];
        foreach ($oldRows as $row) {
            $key = $this->normalizeName((string) ($row['nombre'] ?? ''));
            $oldId = (int) ($row['secanacategoria_id'] ?? 0);
            if ($key !== '' && $oldId > 0 && isset($newByName[$key])) {
                $map[$oldId] = $newByName[$key];
            }
        }

        if ($map === [] && count($oldRows) === 1 && count($newRows) === 1) {
            $oldId = (int) ($oldRows[0]['secanacategoria_id'] ?? 0);
            $newId = (int) ($newRows[0]['secanacategoria_id'] ?? 0);
            if ($oldId > 0 && $newId > 0) {
                $map[$oldId] = $newId;
            }
        }

        return $map;
    }

    /**
     * @return array<int,int>
     */
    private function buildPriresultadosMap(int $fromId, int $toId): array
    {
        $oldRows = $this->db->table('priresultados')
            ->select('priresultados_id, id_poblacion, sexo')
            ->where('prianacategoria_id', $fromId)
            ->get()
            ->getResultArray();
        $newRows = $this->db->table('priresultados')
            ->select('priresultados_id, id_poblacion, sexo')
            ->where('prianacategoria_id', $toId)
            ->where('(deleted = 0 OR deleted IS NULL)')
            ->get()
            ->getResultArray();

        $newByKey = [];
        foreach ($newRows as $row) {
            $key = $this->priresultadoKey((int) ($row['id_poblacion'] ?? 0), (string) ($row['sexo'] ?? ''));
            $newId = (int) ($row['priresultados_id'] ?? 0);
            if ($newId > 0 && ! isset($newByKey[$key])) {
                $newByKey[$key] = $newId;
            }
        }

        $map = [];
        foreach ($oldRows as $row) {
            $key = $this->priresultadoKey((int) ($row['id_poblacion'] ?? 0), (string) ($row['sexo'] ?? ''));
            $oldId = (int) ($row['priresultados_id'] ?? 0);
            if ($oldId > 0 && isset($newByKey[$key])) {
                $map[$oldId] = $newByKey[$key];
            }
        }

        if (count($oldRows) === 1 && count($newRows) === 1) {
            $oldId = (int) ($oldRows[0]['priresultados_id'] ?? 0);
            $newId = (int) ($newRows[0]['priresultados_id'] ?? 0);
            if ($oldId > 0 && $newId > 0) {
                $map[$oldId] = $newId;
            }
        }

        return $map;
    }

    /**
     * @param array<int,int> $secMap
     * @param array<int,int> $priResMap
     */
    private function mapRegvalueName(string $name, int $fromId, int $toId, array $secMap, array $priResMap): string
    {
        $name = trim($name);
        if ($name === '') {
            return $name;
        }

        if (preg_match('/^lab_(val|app)_pri_' . $fromId . '$/', $name, $m) === 1) {
            return 'lab_' . $m[1] . '_pri_' . $toId;
        }

        if (preg_match('/^cvu_' . $fromId . '$/', $name) === 1) {
            return 'cvu_' . $toId;
        }

        if (preg_match('/^(cv|cvn|cvu)_' . $fromId . '_(.+)$/', $name, $m) === 1) {
            return $m[1] . '_' . $toId . '_' . $m[2];
        }

        if (strpos($name, '|') !== false) {
            [$priaStr, $rest] = explode('|', $name, 2);
            if ((int) trim($priaStr) === $fromId) {
                return $toId . '|' . $rest;
            }
        }

        if (preg_match('/^c_(\d+)$/', $name, $m) === 1) {
            $oldSecId = (int) $m[1];
            if (isset($secMap[$oldSecId])) {
                return 'c_' . $secMap[$oldSecId];
            }
        }

        if (preg_match('/^noc_(\d+)$/', $name, $m) === 1) {
            $oldPriResId = (int) $m[1];
            if (isset($priResMap[$oldPriResId])) {
                return 'noc_' . $priResMap[$oldPriResId];
            }
        }

        return $name;
    }

    /**
     * @param array<int,int> $nocCache
     * @param array<int,int> $cCache
     */
    private function regvalueBelongsToAnalysis(string $name, int $prianacategoriaId, array &$nocCache, array &$cCache): bool
    {
        $name = trim($name);
        if ($name === '') {
            return false;
        }

        if (preg_match('/^lab_(val|app)_pri_' . $prianacategoriaId . '$/', $name) === 1) {
            return true;
        }

        if (preg_match('/^cvu_' . $prianacategoriaId . '$/', $name) === 1) {
            return true;
        }

        if (preg_match('/^(cv|cvn|cvu)_' . $prianacategoriaId . '_/', $name) === 1) {
            return true;
        }

        if (strpos($name, '|') !== false) {
            [$priaStr] = explode('|', $name, 2);

            return (int) trim($priaStr) === $prianacategoriaId;
        }

        return $this->registerModel->resolvePrianacategoriaIdFromRegvalueName($name, $nocCache, $cCache) === $prianacategoriaId;
    }

    private function replaceIdInCsv(string $csv, int $fromId, int $toId): string
    {
        $ids = array_values(array_unique(array_filter(array_map('intval', explode(',', $csv)))));
        $result = [];
        $hasTarget = false;

        foreach ($ids as $id) {
            if ($id === $fromId) {
                if (! $hasTarget) {
                    $result[] = $toId;
                    $hasTarget = true;
                }
                continue;
            }
            if ($id === $toId) {
                $hasTarget = true;
            }
            $result[] = $id;
        }

        return implode(',', array_values(array_unique($result)));
    }

    private function csvContainsId(string $csv, int $id): bool
    {
        $ids = array_filter(array_map('intval', explode(',', $csv)));

        return in_array($id, $ids, true);
    }

    private function deleteRegvaluesForAnalysis(int $registroId, int $prianacategoriaId): int
    {
        if ($registroId < 1 || $prianacategoriaId < 1) {
            return 0;
        }

        $idsToDelete = [];
        $nocCache = [];
        $cCache = [];
        foreach ($this->registerModel->getInfoAnalisis($registroId) as $row) {
            $regvaluesId = (int) ($row['regvalues_id'] ?? 0);
            $name = (string) ($row['name'] ?? '');
            if ($regvaluesId < 1 || ! $this->regvalueBelongsToAnalysis($name, $prianacategoriaId, $nocCache, $cCache)) {
                continue;
            }
            $idsToDelete[$regvaluesId] = true;
        }

        if ($idsToDelete === []) {
            return 0;
        }

        $this->db->table('regvalues')
            ->where('registro_id', $registroId)
            ->whereIn('regvalues_id', array_keys($idsToDelete))
            ->delete();

        return count($idsToDelete);
    }

    private function normalizeName(string $name): string
    {
        return mb_strtolower(trim($name), 'UTF-8');
    }

    private function priresultadoKey(int $poblacionId, string $sexo): string
    {
        return $poblacionId . ':' . mb_strtolower(trim($sexo), 'UTF-8');
    }
}
