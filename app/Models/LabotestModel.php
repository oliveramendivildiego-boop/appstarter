<?php

namespace App\Models;

use CodeIgniter\Model;

class LabotestModel extends Model
{
    protected $table            = 'anacategoria';
    protected $primaryKey       = 'anacategoria_id';
    protected $useAutoIncrement = true;
    protected $returnType       = 'object';

    /**
     * Obtiene categorías con sus análisis (prianacategoria)
     * @param string|null $search Filtra por nombre de grupo o de examen
     */
    public function getAllWithAnalysis(?string $search = null): array
    {
        $ana = $this->db->prefixTable('anacategoria');
        $pri = $this->db->prefixTable('prianacategoria');

        $sel = "{$ana}.anacategoria_id, {$ana}.name as cat_name, {$ana}.order as ana_order,
                {$pri}.prianacategoria_id, {$pri}.name as pria_nombre, {$pri}.order as pria_order, {$pri}.cost, {$pri}.cost_deriv, {$pri}.compleja";
        if ($this->hasColumn('prianacategoria', 'tipo_muestra_id')) {
            $sel .= ', tm.nombre AS tipo_muestra_nombre';
        } else {
            $sel .= ', NULL AS tipo_muestra_nombre';
        }
        if ($this->hasColumn('prianacategoria', 'metodo_id')) {
            $sel .= ', me.nombre AS metodo_nombre';
        } else {
            $sel .= ', NULL AS metodo_nombre';
        }

        $builder = $this->db->table('anacategoria')
            ->select($sel, false)
            ->join('prianacategoria', "{$ana}.anacategoria_id = {$pri}.anacategoria_id AND ({$pri}.deleted = 0 OR {$pri}.deleted IS NULL)", 'left')
            ->where("({$ana}.deleted = 0 OR {$ana}.deleted IS NULL)")
            ->orderBy("{$ana}.order", 'ASC')
            ->orderBy("{$pri}.order", 'ASC');

        if ($this->hasColumn('prianacategoria', 'tipo_muestra_id')) {
            $builder->join('tipo_muestra tm', "{$pri}.tipo_muestra_id = tm.tipo_muestra_id", 'left');
        }
        if ($this->hasColumn('prianacategoria', 'metodo_id')) {
            $builder->join('metodo me', "{$pri}.metodo_id = me.metodo_id", 'left');
        }

        if ($search !== null && trim($search) !== '') {
            $esc = $this->db->escapeLikeString(trim($search));
            $pat = "%{$esc}%";
            $builder->groupStart()
                ->like("{$ana}.name", $esc, 'both')
                ->orLike("{$pri}.name", $esc, 'both')
                ->groupEnd();
        }

        return $builder->get()->getResult();
    }

    /**
     * Agrupa por categoría para la vista
     */
    public function getGroupedByCategory(?string $search = null): array
    {
        $rows = $this->getAllWithAnalysis($search);
        $grouped = [];

        foreach ($rows as $row) {
            $catId = $row->anacategoria_id;
            if (!isset($grouped[$catId])) {
                $grouped[$catId] = [
                    'id'    => $row->anacategoria_id,
                    'name'  => $row->cat_name,
                    'order' => $row->ana_order,
                    'items' => [],
                ];
            }
            if ($row->prianacategoria_id) {
                $grouped[$catId]['items'][] = [
                    'id'             => $row->prianacategoria_id,
                    'name'           => $row->pria_nombre,
                    'cost'           => $row->cost,
                    'cost_deriv'     => $row->cost_deriv,
                    'compleja'       => $row->compleja,
                    'tipo_muestra'   => trim((string) ($row->tipo_muestra_nombre ?? '')),
                    'metodo'         => trim((string) ($row->metodo_nombre ?? '')),
                ];
            }
        }

        return array_values($grouped);
    }

    /**
     * Obtiene categorías agrupadas con paginación (6 cajas por página) y búsqueda
     */
    public function getGroupedByCategoryPaginated(int $perPage = 6, int $page = 1, ?string $search = null): array
    {
        $all = $this->getGroupedByCategory($search);
        $total = count($all);
        $offset = ($page - 1) * $perPage;
        $paged = array_slice($all, $offset, $perPage);
        return [
            'categories' => $paged,
            'total'      => $total,
            'per_page'   => $perPage,
            'page'       => $page,
            'total_pages'=> $total > 0 ? (int) ceil($total / $perPage) : 1,
        ];
    }

    /**
     * Obtiene info de una categoría (grupo)
     */
    public function getCategoryInfo($id)
    {
        if (!$id || $id < 1) {
            $obj = (object) ['anacategoria_id' => null, 'name' => '', 'order' => 0];
            return $obj;
        }
        $row = $this->db->table('anacategoria')
            ->where('anacategoria_id', (int) $id)
            ->where('(deleted = 0 OR deleted IS NULL)')
            ->get()
            ->getRow();
        return $row ?? (object) ['anacategoria_id' => null, 'name' => '', 'order' => 0];
    }

    /**
     * Obtiene info de un subgrupo (prianacategoria)
     */
    public function getSubInfo($id, $anacategoriaId = null)
    {
        if (!$id || $id < 1) {
            return (object) [
                'prianacategoria_id' => null,
                'anacategoria_id'   => $anacategoriaId,
                'name'              => '',
                'order'             => 0,
                'compleja'          => 0,
                'mostrar_valores'   => 0,
                'tipo_muestra_id'   => null,
                'metodo_id'         => null,
            ];
        }
        $row = $this->db->table('prianacategoria')
            ->where('prianacategoria_id', (int) $id)
            ->where('(deleted = 0 OR deleted IS NULL)')
            ->get()
            ->getRow();
        return $row ?? (object) ['prianacategoria_id' => null, 'anacategoria_id' => $anacategoriaId, 'name' => '', 'order' => 0, 'compleja' => 0, 'mostrar_valores' => 0, 'tipo_muestra_id' => null, 'metodo_id' => null];
    }

    public function existsCategory(int $id): bool
    {
        return $this->db->table('anacategoria')
            ->where('anacategoria_id', $id)
            ->countAllResults() === 1;
    }

    public function existsSub(int $id): bool
    {
        return $this->db->table('prianacategoria')
            ->where('prianacategoria_id', $id)
            ->countAllResults() === 1;
    }

    /**
     * Elimina categoría (padre) y todos sus hijos con todas las configuraciones (cascade soft-delete)
     */
    public function deleteCategoryWithAll(int $anacategoriaId): bool
    {
        $children = $this->db->table('prianacategoria')
            ->select('prianacategoria_id')
            ->where('anacategoria_id', $anacategoriaId)
            ->get()
            ->getResultArray();
        foreach ($children as $c) {
            $this->deletePrianacategoriaWithAll((int) ($c['prianacategoria_id'] ?? 0));
        }
        return $this->db->table('anacategoria')
            ->where('anacategoria_id', $anacategoriaId)
            ->update(['deleted' => 1]) !== false;
    }

    /**
     * Elimina prianacategoria (hijo) y todas sus configuraciones (secanacategoria, priresultados, manuals)
     */
    public function deletePrianacategoriaWithAll(int $prianacategoriaId): bool
    {
        $this->db->table('secanacategoria')
            ->where('prianacategoria_id', $prianacategoriaId)
            ->update(['deleted' => 1]);
        $this->db->table('priresultados')
            ->where('prianacategoria_id', $prianacategoriaId)
            ->update(['deleted' => 1]);
        try {
            $this->db->table('manuals')
                ->where('prianacategoria_id', (string) $prianacategoriaId)
                ->update(['deleted' => 1]);
        } catch (\Throwable $e) {
            // Tabla manuals puede no existir
        }
        return $this->db->table('prianacategoria')
            ->where('prianacategoria_id', $prianacategoriaId)
            ->update(['deleted' => 1]) !== false;
    }

    /**
     * Obtiene todos los análisis con su cantidad de manuales (para índice)
     */
    public function getAllTestsWithManualCount(?string $search = null): array
    {
        $ana = $this->db->prefixTable('anacategoria');
        $pri = $this->db->prefixTable('prianacategoria');
        $man = $this->db->prefixTable('manuals');

        $builder = $this->db->table("{$pri}")
            ->select("{$pri}.prianacategoria_id, {$pri}.name as pria_name, {$ana}.name as cat_name, {$ana}.anacategoria_id,
                      (SELECT COUNT(*) FROM {$man} m WHERE m.prianacategoria_id = {$pri}.prianacategoria_id AND (m.deleted = 0 OR m.deleted IS NULL)) as manual_count")
            ->join($ana, "{$ana}.anacategoria_id = {$pri}.anacategoria_id")
            ->where("({$pri}.deleted = 0 OR {$pri}.deleted IS NULL)")
            ->where("({$ana}.deleted = 0 OR {$ana}.deleted IS NULL)")
            ->orderBy("{$ana}.order", 'ASC')
            ->orderBy("{$pri}.order", 'ASC');

        if ($search !== null && trim($search) !== '') {
            $esc = $this->db->escapeLikeString(trim($search));
            $builder->groupStart()
                ->like("{$ana}.name", $esc, 'both')
                ->orLike("{$pri}.name", $esc, 'both')
                ->groupEnd();
        }

        $rows = $builder->get()->getResultArray();
        return array_map(function ($r) {
            $r['manual_count'] = (int) ($r['manual_count'] ?? 0);
            return $r;
        }, $rows);
    }

    /**
     * Obtiene manuales/notas de un análisis (prianacategoria)
     */
    public function getManualsByPrianacategoria(int $prianacategoriaId): array
    {
        try {
            return $this->db->table('manuals')
                ->where('prianacategoria_id', (string) $prianacategoriaId)
                ->where('(deleted = 0 OR deleted IS NULL)')
                ->orderBy('manuals_id')
                ->get()
                ->getResultArray();
        } catch (\Throwable $e) {
            return [];
        }
    }

    public function getManualById(int $manualsId): ?array
    {
        try {
            $row = $this->db->table('manuals')
                ->where('manuals_id', $manualsId)
                ->where('(deleted = 0 OR deleted IS NULL)')
                ->get()
                ->getRowArray();
            return $row ?: null;
        } catch (\Throwable $e) {
            return null;
        }
    }

    public function saveManual(array $data): int
    {
        $id = (int) ($data['manuals_id'] ?? 0);
        $save = [
            'prianacategoria_id' => (string) ($data['prianacategoria_id'] ?? ''),
            'tittle'             => trim($data['tittle'] ?? ''),
            'manual'             => $data['manual'] ?? '',
            'deleted'            => 0,
        ];
        try {
            if ($id > 0) {
                $this->db->table('manuals')->where('manuals_id', $id)->update($save);
                return $id;
            }
            $this->db->table('manuals')->insert($save);
            return (int) $this->db->insertID();
        } catch (\Throwable $e) {
            return 0;
        }
    }

    public function deleteManual(int $manualsId): bool
    {
        try {
            return $this->db->table('manuals')
                ->where('manuals_id', $manualsId)
                ->update(['deleted' => 1]) !== false;
        } catch (\Throwable $e) {
            return false;
        }
    }

    /**
     * Guardar categoría (grupo)
     */
    public function saveCategory(array $data, $id = null): bool
    {
        $save = [
            'name'   => $data['name'] ?? '',
            'order'  => (int) ($data['order'] ?? 0),
            'deleted'=> 0,
        ];
        if ($id && $this->existsCategory((int) $id)) {
            return $this->db->table('anacategoria')->where('anacategoria_id', $id)->update($save);
        }
        return $this->db->table('anacategoria')->insert($save) !== false;
    }

    /**
     * Obtiene sub-clases (secanacategoria) de una prueba compuesta.
     * Orden: por columna orden si existe, luego nombre, paciente_id.
     */
    public function getSubItems(int $prianacategoriaId): array
    {
        $builder = $this->db->table('secanacategoria')
            ->where('prianacategoria_id', $prianacategoriaId)
            ->where('(deleted = 0 OR deleted IS NULL)');
        if ($this->hasColumn('secanacategoria', 'orden')) {
            $builder->orderBy('orden', 'ASC');
        }
        $builder->orderBy('nombre')->orderBy('paciente_id');
        return $builder->get()->getResultArray();
    }

    /**
     * Obtiene resultados (priresultados) de una prueba no compuesta
     */
    public function getPriResultados(int $prianacategoriaId): array
    {
        return $this->db->table('priresultados')
            ->where('prianacategoria_id', $prianacategoriaId)
            ->where('(deleted = 0 OR deleted IS NULL)')
            ->orderBy('id_poblacion')
            ->get()
            ->getResultArray();
    }

    /**
     * Obtiene poblaciones (Niños, Masculino, Femenino, etc.)
     */
    public function getPoblaciones(): array
    {
        try {
            $rows = $this->db->table('poblacion')
                ->where('(deleted = 0 OR deleted IS NULL)')
                ->orderBy('orden', 'ASC')
                ->orderBy('id_poblacion', 'ASC')
                ->get()
                ->getResultArray();
        } catch (\Throwable $e) {
            $rows = $this->db->table('poblacion')
                ->orderBy('id_poblacion', 'ASC')
                ->get()
                ->getResultArray();
        }
        foreach ($rows as &$r) {
            $r['name'] = html_entity_decode($r['name'] ?? '', ENT_QUOTES | ENT_HTML5, 'UTF-8');
        }
        return $rows;
    }

    /**
     * Obtiene fórmulas para select
     */
    public function getFormulas(): array
    {
        $rows = $this->db->table('formulas')->orderBy('formulas_id')->get()->getResultArray();
        $out = [];
        foreach ($rows as $r) {
            $out[(int) $r['formulas_id']] = $r['nombre'] ?? '';
        }
        return $out;
    }

    /**
     * Comprueba si una columna existe en una tabla
     */
    private function hasColumn(string $table, string $column): bool
    {
        static $cache = [];
        $key = $table . '.' . $column;
        if (!isset($cache[$key])) {
            try {
                $fullTable = $this->db->prefixTable($table);
                $cols = $this->db->getFieldNames($fullTable);
                $cache[$key] = in_array($column, $cols, true);
            } catch (\Throwable $e) {
                $cache[$key] = false;
            }
        }
        return $cache[$key];
    }

    /**
     * Comprueba si la columna formula_expresion existe en una tabla
     */
    private function hasFormulaExpresionColumn(string $table): bool
    {
        return $this->hasColumn($table, 'formula_expresion');
    }

    /**
     * Obtiene fórmulas con expresión (guardadas por el usuario)
     * Si la columna formula_expresion no existe, devuelve fórmulas con nombre_fun='custom' como fallback.
     */
    public function getFormulasConExpresion(): array
    {
        if (!$this->hasFormulaExpresionColumn('formulas')) {
            $rows = $this->db->table('formulas')
                ->select('formulas_id, nombre')
                ->where('nombre_fun', 'custom')
                ->orderBy('nombre')
                ->get()
                ->getResultArray();
            $out = [];
            foreach ($rows as $r) {
                $out[] = [
                    'formulas_id'       => (int) ($r['formulas_id'] ?? 0),
                    'nombre'            => $r['nombre'] ?? '',
                    'formula_expresion' => '',
                ];
            }
            return $out;
        }
        try {
            $rows = $this->db->table('formulas')
                ->groupStart()
                ->where('formula_expresion IS NOT NULL')
                ->where("formula_expresion != ''")
                ->groupEnd()
                ->orWhere('nombre_fun', 'custom')
                ->orderBy('nombre')
                ->get()
                ->getResultArray();
        } catch (\Throwable $e) {
            return [];
        }
        $out = [];
        foreach ($rows as $r) {
            $out[] = [
                'formulas_id'       => (int) ($r['formulas_id'] ?? 0),
                'nombre'            => $r['nombre'] ?? '',
                'formula_expresion' => trim($r['formula_expresion'] ?? ''),
            ];
        }
        return $out;
    }

    /**
     * Guarda o actualiza una fórmula personalizada (nombre + expresión).
     * Si formulas_id > 0, actualiza esa fila. Si no, busca por nombre: si ya existe
     * una fórmula con el mismo nombre, la actualiza; si no, inserta nueva.
     * No se permiten dos fórmulas con el mismo nombre.
     */
    public function saveFormula(array $data): int
    {
        $nombre = trim($data['nombre'] ?? '');
        $expresion = trim($data['formula_expresion'] ?? '');
        $id = (int) ($data['formulas_id'] ?? 0);
        if ($nombre === '' || $expresion === '') {
            return 0;
        }
        $save = [
            'nombre'     => $nombre,
            'nombre_fun' => 'custom',
        ];
        if ($this->hasFormulaExpresionColumn('formulas')) {
            $save['formula_expresion'] = $expresion;
        }
        if ($id > 0) {
            $this->db->table('formulas')->where('formulas_id', $id)->update($save);
            return $id;
        }
        $existente = $this->db->table('formulas')
            ->where('nombre', $nombre)
            ->limit(1)
            ->get()
            ->getRowArray();
        if ($existente && ! empty($existente['formulas_id'])) {
            $idExistente = (int) $existente['formulas_id'];
            $this->db->table('formulas')->where('formulas_id', $idExistente)->update($save);
            return $idExistente;
        }
        $this->db->table('formulas')->insert($save);
        return (int) $this->db->insertID();
    }

    /**
     * Actualiza solo la expresión de una fórmula por formulas_id.
     * Aplica a todas las fórmulas (nuevas y antiguas); no depende del nombre.
     */
    public function updateFormulaExpresion(int $formulasId, string $expresion): bool
    {
        if ($formulasId < 1) {
            return false;
        }
        if (! $this->hasFormulaExpresionColumn('formulas')) {
            return false;
        }
        return $this->db->table('formulas')
            ->where('formulas_id', $formulasId)
            ->update(['formula_expresion' => trim($expresion)]) !== false;
    }

    /**
     * Obtiene opciones para select (Positivo, Reactivo, Texto)
     */
    public function getOpciones(): array
    {
        $rows = $this->db->table('opciones')->orderBy('opciones_id')->get()->getResultArray();
        $out = [];
        foreach ($rows as $r) {
            $out[(int) $r['opciones_id']] = $r['opciones'] ?? '';
        }
        return $out;
    }

    /**
     * Comprueba si una fórmula está en uso (secanacategoria o priresultados)
     */
    public function isFormulaInUse(int $formulasId): bool
    {
        if ($formulasId < 2) {
            return true;
        }
        $sec = $this->db->table('secanacategoria')
            ->where('formulas_id', $formulasId)
            ->where('(deleted = 0 OR deleted IS NULL)')
            ->countAllResults();
        if ($sec > 0) {
            return true;
        }
        $pri = $this->db->table('priresultados')
            ->where('formulas_id', $formulasId)
            ->where('(deleted = 0 OR deleted IS NULL)')
            ->countAllResults();
        return $pri > 0;
    }

    /**
     * Elimina una fórmula solo si no está en uso.
     * Retorna ['success' => bool, 'message' => string]
     */
    public function deleteFormulaIfUnused(int $formulasId): array
    {
        if ($formulasId < 2) {
            return ['success' => false, 'message' => 'No se puede eliminar la fórmula por defecto.'];
        }
        if ($this->isFormulaInUse($formulasId)) {
            return ['success' => false, 'message' => 'La fórmula está en uso por sub-clases o valores de referencia.'];
        }
        $this->db->table('formulas')->where('formulas_id', $formulasId)->delete();
        return ['success' => true, 'message' => 'Fórmula eliminada correctamente.'];
    }

    /**
     * Guardar sub-clase (secanacategoria)
     */
    public function saveSecItem(array $data, ?int $id = null): bool
    {
        $esSeparador = ! empty($data['es_separador']) && (int) $data['es_separador'] === 1;
        $save = [
            'prianacategoria_id' => (int) ($data['prianacategoria_id'] ?? 0),
            'nombre'             => trim($data['nombre'] ?? ''),
            'paciente_id'        => (int) ($data['paciente_id'] ?? 3),
            'valor_min'          => (string) ($data['valor_min'] ?? ''),
            'valor_max'          => (string) ($data['valor_max'] ?? ''),
            'critico_min'        => (string) ($data['critico_min'] ?? ''),
            'critico_max'        => (string) ($data['critico_max'] ?? ''),
            'umedida'            => (string) ($data['umedida'] ?? ''),
            'formulas_id'        => (int) ($data['formulas_id'] ?? 1),
            'opcion_id'          => (int) ($data['opcion_id'] ?? 3),
            'deleted'            => 0,
        ];
        if ($esSeparador) {
            $save['valor_min']   = '';
            $save['valor_max']   = '';
            $save['critico_min'] = '';
            $save['critico_max'] = '';
            $save['umedida']     = '';
            $save['formulas_id'] = 1;
            $save['opcion_id']   = 3;
        }
        if ($this->hasColumn('secanacategoria', 'es_separador')) {
            $save['es_separador'] = $esSeparador ? 1 : 0;
        }
        if ($this->hasFormulaExpresionColumn('secanacategoria')) {
            $save['formula_expresion'] = null;
        }
        if ($this->hasColumn('secanacategoria', 'sexo')) {
            $sexo = $data['sexo'] ?? 'ambos';
            $save['sexo'] = in_array($sexo, ['masculino', 'femenino'], true) ? $sexo : 'ambos';
        }
        if ($this->hasColumn('secanacategoria', 'orden')) {
            if ($id && $id > 0) {
                // mantener orden al editar
            } else {
                $max = $this->db->table('secanacategoria')
                    ->where('prianacategoria_id', (int)($save['prianacategoria_id']))
                    ->selectMax('orden')
                    ->get()->getRow();
                $save['orden'] = 1 + (int) ($max->orden ?? 0);
            }
        }
        if ($id && $id > 0) {
            return $this->db->table('secanacategoria')->where('secanacategoria_id', $id)->update($save);
        }
        return $this->db->table('secanacategoria')->insert($save) !== false;
    }

    /**
     * Actualiza el orden de las sub-clases. Recibe el prianacategoria_id y un array
     * de secanacategoria_id en el orden deseado (índice = orden).
     */
    public function updateSecItemsOrder(int $prianacategoriaId, array $secanacategoriaIds): bool
    {
        if (!$this->hasColumn('secanacategoria', 'orden')) {
            return true;
        }
        foreach ($secanacategoriaIds as $orden => $secId) {
            $secId = (int) $secId;
            if ($secId < 1) {
                continue;
            }
            $this->db->table('secanacategoria')
                ->where('secanacategoria_id', $secId)
                ->where('prianacategoria_id', $prianacategoriaId)
                ->update(['orden' => (int) $orden]);
        }
        return true;
    }

    /**
     * Duplica una sub-clase con los mismos datos (nombre con " (copia)")
     */
    public function duplicateSecItem(int $id): ?int
    {
        $row = $this->db->table('secanacategoria')
            ->where('secanacategoria_id', $id)
            ->where('(deleted = 0 OR deleted IS NULL)')
            ->get()
            ->getRowArray();
        if (!$row) {
            return null;
        }
        unset($row['secanacategoria_id']);
        $row['nombre'] = trim($row['nombre'] ?? '') . ' (copia)';
        $row['deleted'] = 0;
        $this->db->table('secanacategoria')->insert($row);
        return (int) $this->db->insertID();
    }

    /**
     * Obtiene info de una sub-clase (para redirección tras borrar)
     */
    public function getSecItemInfo(int $id): ?object
    {
        return $this->db->table('secanacategoria')
            ->where('secanacategoria_id', $id)
            ->get()
            ->getRow();
    }

    /**
     * Obtiene info de un priresultado (para redirección tras borrar)
     */
    public function getPriResultadoInfo(int $id): ?object
    {
        return $this->db->table('priresultados')
            ->where('priresultados_id', $id)
            ->get()
            ->getRow();
    }

    /**
     * Eliminar (soft) sub-clase
     */
    public function deleteSecItem(int $id): bool
    {
        return $this->db->table('secanacategoria')
            ->where('secanacategoria_id', $id)
            ->update(['deleted' => 1]);
    }

    /**
     * Guardar o actualizar resultado (priresultados)
     */
    public function savePriResultado(array $data, ?int $id = null): bool
    {
        $save = [
            'prianacategoria_id' => (int) ($data['prianacategoria_id'] ?? 0),
            'id_poblacion'      => (int) ($data['id_poblacion'] ?? 3),
            'valor_min'         => (string) ($data['valor_min'] ?? ''),
            'valor_max'         => (string) ($data['valor_max'] ?? ''),
            'critico_min'       => (string) ($data['critico_min'] ?? ''),
            'critico_max'       => (string) ($data['critico_max'] ?? ''),
            'umedida'           => (string) ($data['umedida'] ?? ''),
            'formulas_id'       => (int) ($data['formulas_id'] ?? 1),
            'opcion_id'         => (int) ($data['opcion_id'] ?? 3),
            'deleted'           => 0,
        ];
        if ($this->hasColumn('priresultados', 'sexo')) {
            $sexo = $data['sexo'] ?? 'ambos';
            $save['sexo'] = in_array($sexo, ['masculino', 'femenino'], true) ? $sexo : 'ambos';
        }
        if ($id && $id > 0) {
            return $this->db->table('priresultados')->where('priresultados_id', $id)->update($save);
        }
        return $this->db->table('priresultados')->insert($save) !== false;
    }

    /**
     * Eliminar (soft) priresultado
     */
    public function deletePriResultado(int $id): bool
    {
        return $this->db->table('priresultados')
            ->where('priresultados_id', $id)
            ->update(['deleted' => 1]);
    }

    /**
     * Guardar subgrupo (prianacategoria) - sin cost en esta ventana, se usa 0 por defecto
     */
    public function saveSubCategory(array $data, $id = null): bool
    {
        $anacategoriaId = (int) ($data['anacategoria_id'] ?? 0);
        if (!$anacategoriaId) {
            return false;
        }
        $save = [
            'name'           => $data['name'] ?? '',
            'order'          => (int) ($data['order'] ?? 0),
            'anacategoria_id'=> $anacategoriaId,
            'compleja'       => (int) ($data['compleja'] ?? 0),
            'deleted'        => 0,
        ];
        if ($this->hasColumn('prianacategoria', 'mostrar_valores')) {
            $save['mostrar_valores'] = (int) ($data['mostrar_valores'] ?? 0);
        }
        if ($this->hasColumn('prianacategoria', 'tipo_muestra_id')) {
            $tid = (int) ($data['tipo_muestra_id'] ?? 0);
            $save['tipo_muestra_id'] = $tid > 0 ? $tid : null;
        }
        if ($this->hasColumn('prianacategoria', 'metodo_id')) {
            $mid = (int) ($data['metodo_id'] ?? 0);
            $save['metodo_id'] = $mid > 0 ? $mid : null;
        }
        if ($id && $this->existsSub((int) $id)) {
            if (isset($data['cost'])) {
                $save['cost']      = (int) ($data['cost'] ?? 0);
                $save['cost_deriv']= (int) ($data['cost_deriv'] ?? 0);
            }
            return $this->db->table('prianacategoria')->where('prianacategoria_id', $id)->update($save);
        }
        $save['cost']      = (int) ($data['cost'] ?? 0);
        $save['cost_deriv']= (int) ($data['cost_deriv'] ?? 0);
        return $this->db->table('prianacategoria')->insert($save) !== false;
    }
}
