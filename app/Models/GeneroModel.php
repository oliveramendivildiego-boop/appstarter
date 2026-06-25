<?php

namespace App\Models;

use CodeIgniter\Model;

class GeneroModel extends Model
{
    protected $table            = 'genero';
    protected $primaryKey       = 'genero_id';
    protected $returnType       = 'array';
    protected $useAutoIncrement = true;
    protected $allowedFields    = ['nombre', 'orden', 'deleted'];
    protected $useTimestamps    = false;

    /** @var array<int, string>|null */
    private static ?array $nombreCache = null;

    public function ensureTable(): bool
    {
        if ($this->db->tableExists($this->table)) {
            return true;
        }
        try {
            $forge = \Config\Database::forge($this->db);
            $forge->addField([
                'genero_id' => [
                    'type'           => 'INT',
                    'constraint'     => 11,
                    'unsigned'       => true,
                    'auto_increment' => true,
                ],
                'nombre' => [
                    'type'       => 'VARCHAR',
                    'constraint' => 64,
                    'null'       => false,
                ],
                'orden' => [
                    'type'       => 'INT',
                    'constraint' => 11,
                    'default'    => 0,
                    'null'       => false,
                ],
                'deleted' => [
                    'type'       => 'TINYINT',
                    'constraint' => 1,
                    'default'    => 0,
                    'null'       => false,
                ],
            ]);
            $forge->addKey('genero_id', true);
            $forge->createTable($this->table, true);
            $this->db->table($this->table)->insertBatch([
                ['genero_id' => 1, 'nombre' => 'Masculino', 'orden' => 1, 'deleted' => 0],
                ['genero_id' => 2, 'nombre' => 'Femenino', 'orden' => 2, 'deleted' => 0],
            ]);
        } catch (\Throwable $e) {
            return false;
        }

        return $this->db->tableExists($this->table);
    }

    public function getAllActive(): array
    {
        $this->ensureTable();
        return $this->builder()
            ->where('(deleted = 0 OR deleted IS NULL)')
            ->orderBy('orden', 'ASC')
            ->orderBy('nombre', 'ASC')
            ->get()
            ->getResultArray();
    }

    /**
     * @return array<string, string>
     */
    public function getDropdownOptions(string $emptyLabel = '-- Seleccione --'): array
    {
        $options = ['' => $emptyLabel];
        foreach ($this->getAllActive() as $row) {
            $id = (int) ($row['genero_id'] ?? 0);
            $nombre = trim((string) ($row['nombre'] ?? ''));
            if ($id > 0 && $nombre !== '') {
                $options[(string) $id] = $nombre;
            }
        }

        return $options;
    }

    /**
     * @return list<int>
     */
    public function getActiveIds(): array
    {
        $ids = [];
        foreach ($this->getAllActive() as $row) {
            $id = (int) ($row['genero_id'] ?? 0);
            if ($id > 0) {
                $ids[] = $id;
            }
        }

        return $ids;
    }

    public function getNombreById(int $id): ?string
    {
        if ($id <= 0) {
            return null;
        }
        $map = $this->getNombreMap();
        $nombre = $map[$id] ?? null;

        return ($nombre !== null && $nombre !== '') ? $nombre : null;
    }

    /**
     * @return array<int, string>
     */
    public function getNombreMap(): array
    {
        if (self::$nombreCache !== null) {
            return self::$nombreCache;
        }
        self::$nombreCache = [];
        foreach ($this->getAllActive() as $row) {
            $id = (int) ($row['genero_id'] ?? 0);
            $nombre = trim((string) ($row['nombre'] ?? ''));
            if ($id > 0 && $nombre !== '') {
                self::$nombreCache[$id] = $nombre;
            }
        }

        return self::$nombreCache;
    }

    public static function clearCache(): void
    {
        self::$nombreCache = null;
    }

    public function getValidationRule(): string
    {
        $ids = $this->getActiveIds();
        if ($ids === []) {
            return 'required|in_list[1,2]';
        }

        return 'required|in_list[' . implode(',', $ids) . ']';
    }

    /**
     * @param array<string, mixed> $rules
     */
    public function applyValidationRules(array &$rules): void
    {
        if (!isset($rules['gender'])) {
            return;
        }
        $rules['gender']['rules'] = $this->getValidationRule();
    }

    public function countPeopleUsing(int $generoId): int
    {
        if (!$this->db->tableExists('people')) {
            return 0;
        }

        return (int) $this->db->table('people')
            ->where('gender', $generoId)
            ->countAllResults();
    }

    public function countDoctorsUsing(int $generoId): int
    {
        if (!$this->db->tableExists('doctors')) {
            return 0;
        }

        return (int) $this->db->table('doctors')
            ->where('gender', $generoId)
            ->where('(deleted = 0 OR deleted IS NULL)')
            ->countAllResults();
    }

    /**
     * @return int|false ID guardado o false si el nombre está vacío
     */
    public function saveGenero(string $nombre, int $orden = 0, ?int $id = null)
    {
        $nombre = trim($nombre);
        if ($nombre === '') {
            return false;
        }
        self::clearCache();
        $row = [
            'nombre'  => $nombre,
            'orden'   => max(0, $orden),
            'deleted' => 0,
        ];
        if ($id !== null && $id > 0) {
            $this->update($id, $row);

            return $id;
        }
        $this->insert($row);

        return (int) $this->getInsertID();
    }

    /**
     * @return array{success: bool, message: string}
     */
    public function softDeleteIfUnused(int $id): array
    {
        if ($this->countPeopleUsing($id) > 0 || $this->countDoctorsUsing($id) > 0) {
            return [
                'success' => false,
                'message' => 'No se puede eliminar: hay pacientes o médicos que usan este género.',
            ];
        }
        self::clearCache();
        $this->update($id, ['deleted' => 1]);

        return ['success' => true, 'message' => 'Género eliminado.'];
    }
}
