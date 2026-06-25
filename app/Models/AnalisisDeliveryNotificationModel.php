<?php

namespace App\Models;

use CodeIgniter\Model;

class AnalisisDeliveryNotificationModel extends Model
{
    protected $table            = 'analisis_delivery_notification';
    protected $primaryKey       = 'notification_id';
    protected $useAutoIncrement = true;
    protected $returnType       = 'array';
    protected $allowedFields    = [
        'registro_id',
        'prianacategoria_id',
        'validated_at',
        'status',
        'attended_at',
        'attended_by',
        'created_at',
    ];

    public function tableExists(): bool
    {
        return $this->db->tableExists($this->table);
    }

    public function countPending(): int
    {
        if (! $this->tableExists()) {
            return 0;
        }

        return (int) $this->db->table($this->table)
            ->where('status', 'pending')
            ->countAllResults();
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function listPending(int $limit = 500): array
    {
        if (! $this->tableExists()) {
            return [];
        }

        $n  = $this->db->prefixTable($this->table);
        $r  = $this->db->prefixTable('registro');
        $p  = $this->db->prefixTable('people');
        $pri = $this->db->prefixTable('prianacategoria');

        return $this->db->table("{$n} n")
            ->select("n.*, r.numero_orden, r.ingreso,
                p.first_name, p.last_name_fa, pri.name AS analisis_nombre")
            ->join("{$r} r", 'r.registro_id = n.registro_id', 'inner')
            ->join("{$p} p", 'p.person_id = r.person_id', 'left')
            ->join("{$pri} pri", 'pri.prianacategoria_id = n.prianacategoria_id', 'left')
            ->where('n.status', 'pending')
            ->orderBy('n.validated_at', 'ASC')
            ->orderBy('n.notification_id', 'ASC')
            ->limit($limit)
            ->get()
            ->getResultArray();
    }

    public function hasPendingForRegistro(int $registroId): bool
    {
        if ($registroId < 1 || ! $this->tableExists()) {
            return false;
        }

        return $this->db->table($this->table)
            ->where('registro_id', $registroId)
            ->where('status', 'pending')
            ->countAllResults() > 0;
    }

    public function hasAttendedForRegistro(int $registroId): bool
    {
        if ($registroId < 1 || ! $this->tableExists()) {
            return false;
        }

        return $this->db->table($this->table)
            ->where('registro_id', $registroId)
            ->where('status', 'attended')
            ->countAllResults() > 0;
    }

    /**
     * @param list<int> $registroIds
     * @return list<int>
     */
    public function filterRegistroIdsWithAttended(array $registroIds): array
    {
        if ($registroIds === [] || ! $this->tableExists()) {
            return [];
        }

        $ids = array_values(array_unique(array_filter(array_map(
            static fn ($id): int => (int) $id,
            $registroIds
        ), static fn (int $id): bool => $id > 0)));

        if ($ids === []) {
            return [];
        }

        $out = [];
        foreach ($this->db->table($this->table)
            ->select('registro_id')
            ->distinct()
            ->whereIn('registro_id', $ids)
            ->where('status', 'attended')
            ->get()
            ->getResultArray() as $row) {
            $rid = (int) ($row['registro_id'] ?? 0);
            if ($rid > 0) {
                $out[] = $rid;
            }
        }

        return $out;
    }

    /**
     * @param list<int> $registroIds
     * @param bool $requireNotificarEntrega Solo recepciones con notificar_entrega = 1
     * @return list<int> registro_id con al menos una notificación pendiente
     */
    public function filterRegistroIdsWithPending(array $registroIds, bool $requireNotificarEntrega = false): array
    {
        if ($registroIds === [] || ! $this->tableExists()) {
            return [];
        }

        $ids = array_values(array_unique(array_filter(array_map(
            static fn ($id): int => (int) $id,
            $registroIds
        ), static fn (int $id): bool => $id > 0)));

        if ($ids === []) {
            return [];
        }

        $n = $this->db->prefixTable($this->table);
        $builder = $this->db->table("{$n} n")
            ->select('n.registro_id')
            ->distinct()
            ->whereIn('n.registro_id', $ids)
            ->where('n.status', 'pending');

        if ($requireNotificarEntrega) {
            $r = $this->db->prefixTable('registro');
            $builder->join("{$r} r", 'r.registro_id = n.registro_id', 'inner')
                ->where('r.notificar_entrega', 1);
        }

        $rows = $builder->get()->getResultArray();

        $out = [];
        foreach ($rows as $row) {
            $rid = (int) ($row['registro_id'] ?? 0);
            if ($rid > 0) {
                $out[$rid] = $rid;
            }
        }

        return array_values($out);
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function listPendingForRegistro(int $registroId): array
    {
        if ($registroId < 1 || ! $this->tableExists()) {
            return [];
        }

        $n   = $this->db->prefixTable($this->table);
        $pri = $this->db->prefixTable('prianacategoria');

        return $this->db->table("{$n} n")
            ->select('n.*, pri.name AS analisis_nombre')
            ->join("{$pri} pri", 'pri.prianacategoria_id = n.prianacategoria_id', 'left')
            ->where('n.registro_id', $registroId)
            ->where('n.status', 'pending')
            ->orderBy('n.validated_at', 'ASC')
            ->orderBy('n.notification_id', 'ASC')
            ->get()
            ->getResultArray();
    }

    public function createPending(int $registroId, int $prianacategoriaId, string $validatedAt): bool
    {
        if ($registroId < 1 || $prianacategoriaId < 1 || ! $this->tableExists()) {
            return false;
        }

        $exists = $this->db->table($this->table)
            ->where('registro_id', $registroId)
            ->where('prianacategoria_id', $prianacategoriaId)
            ->countAllResults();

        if ($exists > 0) {
            return false;
        }

        return $this->db->table($this->table)->insert([
            'registro_id'          => $registroId,
            'prianacategoria_id' => $prianacategoriaId,
            'validated_at'         => $validatedAt,
            'status'               => 'pending',
            'created_at'           => $validatedAt,
        ]);
    }

    /**
     * @return list<int> notification_ids marcadas
     */
    public function markAttendedForRegistro(int $registroId, int $personId, ?int $prianacategoriaId = null): array
    {
        if ($registroId < 1 || ! $this->tableExists()) {
            return [];
        }

        $builder = $this->db->table($this->table)
            ->select('notification_id')
            ->where('registro_id', $registroId)
            ->where('status', 'pending');

        if ($prianacategoriaId !== null && $prianacategoriaId > 0) {
            $builder->where('prianacategoria_id', $prianacategoriaId);
        }

        $ids = array_map(
            static fn (array $row): int => (int) ($row['notification_id'] ?? 0),
            $builder->get()->getResultArray()
        );
        $ids = array_values(array_filter($ids, static fn (int $id): bool => $id > 0));

        if ($ids === []) {
            return [];
        }

        $now = \App\Services\RegisterService::mysqlNowForReport();
        $this->db->table($this->table)
            ->whereIn('notification_id', $ids)
            ->update([
                'status'      => 'attended',
                'attended_at' => $now,
                'attended_by' => $personId > 0 ? $personId : null,
            ]);

        return $ids;
    }
}
