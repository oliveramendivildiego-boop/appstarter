<?php

namespace App\Models;

use CodeIgniter\Model;

class AuditoriaModel extends Model
{
    protected $table = 'auditoria';

    public static function log(string $modulo, string $accion, ?string $registroId = null, ?string $datos = null): void
    {
        try {
            $db = \Config\Database::connect();
            $db->table('auditoria')->insert([
                'person_id'   => session()->get('person_id'),
                'modulo'      => $modulo,
                'accion'      => $accion,
                'registro_id' => $registroId,
                'ip'          => service('request')->getIPAddress(),
                'datos'       => $datos,
            ]);
        } catch (\Throwable $e) {
            log_message('error', 'Auditoria::log ' . $e->getMessage());
        }
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
     * Obtiene registros paginados de auditoría.
     */
    public function getPaginados(int $perPage, int $offset): array
    {
        return $this->db->table('auditoria')
            ->select('auditoria.*, people.first_name, people.last_name_fa')
            ->join('people', 'people.person_id = auditoria.person_id', 'left')
            ->orderBy('auditoria.fecha', 'DESC')
            ->limit($perPage, $offset)
            ->get()
            ->getResultArray();
    }

    /**
     * Cuenta el total de registros de auditoría.
     */
    public function countAll(): int
    {
        return (int) $this->db->table('auditoria')->countAllResults();
    }
}
