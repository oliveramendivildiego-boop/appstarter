<?php

namespace App\Models;

use CodeIgniter\Model;

/**
 * Modelo para gestionar tipos de resultado (opciones) y sus valores.
 * Las opciones se usan en análisis compuestos (sub-clases) como Tipo resultado.
 */
class OpcionModel extends Model
{
    protected $table            = 'opciones';
    protected $primaryKey       = 'opciones_id';
    protected $useAutoIncrement = true;
    protected $returnType       = 'array';
    protected $useSoftDeletes   = false;
    protected $allowedFields    = ['opciones', 'tabla'];

    /**
     * Obtiene todas las opciones para el select de Tipo resultado
     */
    public function getAllOpciones(): array
    {
        $rows = $this->orderBy('opciones_id', 'ASC')->findAll();
        $out = [];
        foreach ($rows as $r) {
            $out[(int) $r['opciones_id']] = $r['opciones'] ?? '';
        }
        return $out;
    }

    /**
     * Obtiene una opción por ID con sus valores
     */
    public function getOpcionConValores(int $opcionesId): ?array
    {
        $row = $this->find($opcionesId);
        if (!$row) {
            return null;
        }
        $tabla = trim($row['tabla'] ?? '');
        $valores = [];
        if ($tabla === 'opcion_valores') {
            $valores = $this->getValores($opcionesId);
        } elseif ($tabla === 'opcpositivo') {
            $valores = $this->getValoresTabla('opcpositivo');
        } elseif ($tabla === 'opcreactivo') {
            $valores = $this->getValoresTabla('opcreactivo');
        }
        $row['valores'] = $valores;
        $row['usa_valores_genericos'] = ($tabla === 'opcion_valores');
        return $row;
    }

    /**
     * Valores desde tabla opcion_valores (genérica)
     */
    public function getValores(int $opcionesId): array
    {
        $rows = $this->db->table('opcion_valores')
            ->where('opciones_id', $opcionesId)
            ->orderBy('orden', 'ASC')
            ->orderBy('valor', 'ASC')
            ->get()
            ->getResultArray();
        return $rows;
    }

    /**
     * Valores desde tablas específicas (opcpositivo, opcreactivo)
     */
    private function getValoresTabla(string $tabla): array
    {
        try {
            return $this->db->table($tabla)->orderBy($tabla . '_id', 'ASC')->get()->getResultArray();
        } catch (\Throwable $e) {
            return [];
        }
    }

    /**
     * Guarda un valor en opcpositivo u opcreactivo
     */
    public function saveValorTabla(string $tabla, int $valorId, string $valor): int
    {
        $tabla = in_array($tabla, ['opcpositivo', 'opcreactivo'], true) ? $tabla : '';
        if ($tabla === '' || trim($valor) === '') {
            return 0;
        }
        $colName = $tabla;
        $idCol   = $tabla . '_id';
        $payload = [$colName => trim($valor)];
        if ($valorId > 0) {
            $this->db->table($tabla)->where($idCol, $valorId)->update($payload);
            return $valorId;
        }
        $this->db->table($tabla)->insert($payload);
        return (int) $this->db->insertID();
    }

    /**
     * Elimina un valor de opcpositivo u opcreactivo
     */
    public function deleteValorTabla(string $tabla, int $valorId): bool
    {
        $tabla = in_array($tabla, ['opcpositivo', 'opcreactivo'], true) ? $tabla : '';
        if ($tabla === '' || $valorId < 1) {
            return false;
        }
        return $this->db->table($tabla)->where($tabla . '_id', $valorId)->delete() !== false;
    }

    /**
     * Guarda una opción (crear o actualizar)
     */
    public function saveOpcion(array $data): int
    {
        $id = (int) ($data['opciones_id'] ?? 0);
        $opciones = trim($data['opciones'] ?? '');
        if ($opciones === '') {
            return 0;
        }
        $tabla = trim($data['tabla'] ?? 'opcion_valores');
        if ($tabla === '' && $id !== 3) {
            $tabla = 'opcion_valores';
        }
        $payload = ['opciones' => $opciones, 'tabla' => $tabla];

        if ($id > 0) {
            $this->update($id, $payload);
            return $id;
        }
        return (int) $this->insert($payload);
    }

    /**
     * Agrega o actualiza un valor en opcion_valores
     */
    public function saveValor(array $data): int
    {
        $opcionesId = (int) ($data['opciones_id'] ?? 0);
        $valorId = (int) ($data['opcion_valor_id'] ?? 0);
        $valor = trim($data['valor'] ?? '');
        $orden = (int) ($data['orden'] ?? 0);
        if ($opcionesId < 1 || $valor === '') {
            return 0;
        }

        $payload = ['opciones_id' => $opcionesId, 'valor' => $valor, 'orden' => $orden];

        if ($valorId > 0) {
            $this->db->table('opcion_valores')->where('opcion_valor_id', $valorId)->update($payload);
            return $valorId;
        }
        $this->db->table('opcion_valores')->insert($payload);
        return (int) $this->db->insertID();
    }

    /**
     * Reordena los valores de una opción personalizada.
     *
     * @param int $opcionesId
     * @param list<int> $orderedIds
     */
    public function reorderValores(int $opcionesId, array $orderedIds): bool
    {
        if ($opcionesId < 1 || empty($orderedIds)) {
            return false;
        }

        $ids = array_values(array_unique(array_map('intval', $orderedIds)));
        $ids = array_values(array_filter($ids, static fn (int $id): bool => $id > 0));
        if (empty($ids)) {
            return false;
        }

        $db = $this->db;
        $db->transStart();
        foreach ($ids as $index => $valorId) {
            $db->table('opcion_valores')
                ->where('opcion_valor_id', $valorId)
                ->where('opciones_id', $opcionesId)
                ->update(['orden' => $index + 1]);
        }
        $db->transComplete();

        return $db->transStatus() !== false;
    }

    /**
     * Elimina un valor de opcion_valores
     */
    public function deleteValor(int $opcionValorId): bool
    {
        return $this->db->table('opcion_valores')
            ->where('opcion_valor_id', $opcionValorId)
            ->delete() !== false;
    }

    /**
     * Elimina una opción (solo si no está en uso y usa valores genéricos)
     */
    public function deleteOpcionIfUnused(int $opcionesId): array
    {
        $row = $this->find($opcionesId);
        if (!$row) {
            return ['success' => false, 'message' => 'Opción no encontrada.'];
        }
        if ((int) $row['opciones_id'] <= 3) {
            return ['success' => false, 'message' => 'No se puede eliminar las opciones del sistema.'];
        }
        $enUso = $this->db->table('secanacategoria')->where('opcion_id', $opcionesId)->countAllResults() > 0
            || $this->db->table('priresultados')->where('opcion_id', $opcionesId)->countAllResults() > 0;
        if ($enUso) {
            return ['success' => false, 'message' => 'La opción está en uso en análisis. No se puede eliminar.'];
        }
        $this->db->table('opcion_valores')->where('opciones_id', $opcionesId)->delete();
        $this->delete($opcionesId);
        return ['success' => true, 'message' => 'Opción eliminada.'];
    }

    /**
     * Comprueba si una opción puede ser editada (las del sistema 1,2,3 tienen estructura fija)
     */
    public function isEditable(int $opcionesId): bool
    {
        return $opcionesId > 3;
    }
}
