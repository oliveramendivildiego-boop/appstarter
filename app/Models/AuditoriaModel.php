<?php

namespace App\Models;

use CodeIgniter\Model;

class AuditoriaModel extends Model
{
    protected $table = 'auditoria';

    /**
     * Registra una acción de auditoría con contexto detallado.
     * @param string      $modulo     Módulo (registers, employees, config, etc.)
     * @param string      $accion     Acción (crear, actualizar, eliminar, ver_reporte, etc.)
     * @param string|null $registroId ID del registro afectado
     * @param string|null $datos      Datos adicionales en texto o JSON
     */
    public static function log(string $modulo, string $accion, ?string $registroId = null, ?string $datos = null): void
    {
        try {
            $request = service('request');
            $db = \Config\Database::connect();
            $db->table('auditoria')->insert([
                'person_id'   => session()->get('person_id'),
                'modulo'      => $modulo,
                'accion'      => $accion,
                'registro_id' => $registroId,
                'ip'          => $request->getIPAddress(),
                'user_agent'  => mb_substr((string)($request->getUserAgent()->getAgentString() ?? ''), 0, 500),
                'datos'       => $datos,
            ]);
        } catch (\Throwable $e) {
            log_message('error', 'Auditoria::log ' . $e->getMessage());
        }
    }

    /**
     * Helper: construye un string JSON con los detalles de un cambio.
     * @param array       $data   Datos clave del registro
     * @param string|null $extra  Info extra legible
     */
    public static function detail(array $data, ?string $extra = null): string
    {
        if ($extra !== null) {
            $data['_info'] = $extra;
        }
        return json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    }

    public function getRecientes(int $limit = 100): array
    {
        return $this->db->table('auditoria')
            ->select('auditoria.*, people.first_name, people.last_name_fa')
            ->join('people', 'people.person_id = auditoria.person_id', 'left')
            ->orderBy('auditoria.fecha', 'DESC')
            ->limit($limit)
            ->get()
            ->getResultArray();
    }

    /**
     * Obtiene registros paginados con filtros opcionales.
     */
    public function getPaginados(int $perPage, int $offset, array $filters = []): array
    {
        $builder = $this->db->table('auditoria')
            ->select('auditoria.*, people.first_name, people.last_name_fa')
            ->join('people', 'people.person_id = auditoria.person_id', 'left');

        $this->applyFilters($builder, $filters);

        return $builder->orderBy('auditoria.fecha', 'DESC')
            ->limit($perPage, $offset)
            ->get()
            ->getResultArray();
    }

    /**
     * Cuenta total con filtros opcionales.
     */
    public function countFiltered(array $filters = []): int
    {
        $builder = $this->db->table('auditoria')
            ->join('people', 'people.person_id = auditoria.person_id', 'left');

        $this->applyFilters($builder, $filters);

        return (int) $builder->countAllResults();
    }

    public function countAll(): int
    {
        return (int) $this->db->table('auditoria')->countAllResults();
    }

    /**
     * Lista de módulos distintos para filtro.
     */
    public function getDistinctModulos(): array
    {
        $rows = $this->db->table('auditoria')
            ->select('modulo')
            ->distinct()
            ->orderBy('modulo', 'ASC')
            ->get()
            ->getResultArray();
        return array_column($rows, 'modulo');
    }

    /**
     * Lista de acciones distintas para filtro.
     */
    public function getDistinctAcciones(): array
    {
        $rows = $this->db->table('auditoria')
            ->select('accion')
            ->distinct()
            ->orderBy('accion', 'ASC')
            ->get()
            ->getResultArray();
        return array_column($rows, 'accion');
    }

    protected function applyFilters($builder, array $filters): void
    {
        if (!empty($filters['modulo'])) {
            $builder->where('auditoria.modulo', $filters['modulo']);
        }
        if (!empty($filters['accion'])) {
            $builder->where('auditoria.accion', $filters['accion']);
        }
        if (!empty($filters['fecha_desde'])) {
            $builder->where('auditoria.fecha >=', $filters['fecha_desde'] . ' 00:00:00');
        }
        if (!empty($filters['fecha_hasta'])) {
            $builder->where('auditoria.fecha <=', $filters['fecha_hasta'] . ' 23:59:59');
        }
        if (!empty($filters['usuario'])) {
            $q = '%' . $filters['usuario'] . '%';
            $builder->groupStart()
                ->like('people.first_name', $filters['usuario'])
                ->orLike('people.last_name_fa', $filters['usuario'])
                ->groupEnd();
        }
        if (!empty($filters['buscar'])) {
            $q = $filters['buscar'];
            $builder->groupStart()
                ->like('auditoria.modulo', $q)
                ->orLike('auditoria.accion', $q)
                ->orLike('auditoria.registro_id', $q)
                ->orLike('auditoria.datos', $q)
                ->orLike('people.first_name', $q)
                ->orLike('people.last_name_fa', $q)
                ->groupEnd();
        }
    }
}
