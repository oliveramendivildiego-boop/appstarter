<?php

namespace App\Models;

use CodeIgniter\Model;

class TipoMuestraModel extends Model
{
    protected $table            = 'tipo_muestra';
    protected $primaryKey     = 'tipo_muestra_id';
    protected $returnType     = 'array';
    protected $useAutoIncrement = true;
    protected $allowedFields    = ['nombre', 'deleted'];
    protected $useTimestamps    = false;

    public function getAllActive(bool $descendente = false): array
    {
        return $this->builder()
            ->where('(deleted = 0 OR deleted IS NULL)')
            ->orderBy('nombre', $descendente ? 'DESC' : 'ASC')
            ->get()
            ->getResultArray();
    }

    /**
     * Cambia mayúsculas/minúsculas de todos los nombres activos.
     *
     * @param string $mode upper|first|title (ver OpcionModel::applyCaseTransform)
     * @return int Cantidad de nombres modificados
     */
    public function transformNombresCase(string $mode): int
    {
        $cambiados = 0;
        foreach ($this->getAllActive() as $row) {
            $id = (int) ($row['tipo_muestra_id'] ?? 0);
            $original = (string) ($row['nombre'] ?? '');
            $nuevo = OpcionModel::applyCaseTransform($original, $mode);
            if ($id > 0 && $nuevo !== '' && $nuevo !== $original) {
                $this->update($id, ['nombre' => $nuevo]);
                $cambiados++;
            }
        }

        return $cambiados;
    }

    public function countMuestrasUsando(int $tipoId): int
    {
        if (!$this->db->tableExists('muestra')) {
            return 0;
        }

        return (int) $this->db->table('muestra')
            ->where('tipo_muestra_id', $tipoId)
            ->where('(deleted = 0 OR deleted IS NULL)')
            ->countAllResults();
    }

    /**
     * @return int|false ID guardado o false si el nombre está vacío
     */
    public function saveTipo(string $nombre, ?int $id = null)
    {
        $nombre = trim($nombre);
        if ($nombre === '') {
            return false;
        }
        $row = ['nombre' => $nombre, 'deleted' => 0];
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
        if ($this->countMuestrasUsando($id) > 0) {
            return [
                'success' => false,
                'message' => 'No se puede eliminar: hay muestras que usan este tipo.',
            ];
        }
        $this->update($id, ['deleted' => 1]);

        return ['success' => true, 'message' => 'Tipo de muestra eliminado.'];
    }
}
