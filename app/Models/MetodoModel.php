<?php

namespace App\Models;

use CodeIgniter\Model;

/**
 * Catálogo de métodos de prueba (técnica empleada), configurable en Config.
 * Asignable por análisis en prianacategoria (como tipo de muestra).
 */
class MetodoModel extends Model
{
    protected $table              = 'metodo';
    protected $primaryKey         = 'metodo_id';
    protected $returnType         = 'array';
    protected $useAutoIncrement   = true;
    protected $allowedFields      = ['nombre', 'deleted'];
    protected $useTimestamps      = false;

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
            $id = (int) ($row['metodo_id'] ?? 0);
            $original = (string) ($row['nombre'] ?? '');
            $nuevo = OpcionModel::applyCaseTransform($original, $mode);
            if ($id > 0 && $nuevo !== '' && $nuevo !== $original) {
                $this->update($id, ['nombre' => $nuevo]);
                $cambiados++;
            }
        }

        return $cambiados;
    }

    public function countPrianacategoriaUsando(int $metodoId): int
    {
        if (! $this->db->tableExists('prianacategoria')) {
            return 0;
        }
        $fields = $this->db->getFieldNames($this->db->prefixTable('prianacategoria'));
        if (! in_array('metodo_id', $fields, true)) {
            return 0;
        }

        return (int) $this->db->table('prianacategoria')
            ->where('metodo_id', $metodoId)
            ->where('(deleted = 0 OR deleted IS NULL)')
            ->countAllResults();
    }

    /**
     * @return int|false ID guardado o false si el nombre está vacío
     */
    public function saveMetodo(string $nombre, ?int $id = null)
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
        if ($this->countPrianacategoriaUsando($id) > 0) {
            return [
                'success' => false,
                'message' => 'No se puede eliminar: hay análisis que usan este método.',
            ];
        }
        $this->update($id, ['deleted' => 1]);

        return ['success' => true, 'message' => 'Método eliminado.'];
    }
}
