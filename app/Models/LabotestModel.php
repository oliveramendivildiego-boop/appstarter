<?php

namespace App\Models;

use App\Services\RegisterService;
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
     * Opciones de categorías activas para selects.
     */
    public function getCategoryOptions(): array
    {
        return $this->db->table('anacategoria')
            ->select('anacategoria_id, name')
            ->where('(deleted = 0 OR deleted IS NULL)')
            ->orderBy('order', 'ASC')
            ->orderBy('name', 'ASC')
            ->get()
            ->getResultArray();
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
     * Orden en pantalla: por columna orden (drag-and-drop en labotests/detail), luego población, sexo e id.
     * Si varias filas empatan en orden (datos antiguos), se desempata como antes por población y sexo.
     */
    public function getSubItems(int $prianacategoriaId): array
    {
        $builder = $this->db->table('secanacategoria')
            ->where('prianacategoria_id', $prianacategoriaId)
            ->where('(deleted = 0 OR deleted IS NULL)');
        $rows = $builder->get()->getResultArray();

        $pobOrd = [];
        try {
            $pobRows = $this->db->table('poblacion')
                ->select('id_poblacion, orden')
                ->where('(deleted = 0 OR deleted IS NULL)')
                ->get()
                ->getResultArray();
        } catch (\Throwable $e) {
            $pobRows = $this->db->table('poblacion')
                ->select('id_poblacion, orden')
                ->get()
                ->getResultArray();
        }
        foreach ($pobRows as $p) {
            $pobOrd[(int) ($p['id_poblacion'] ?? 0)] = (int) ($p['orden'] ?? 9999);
        }

        $sexRank = static function (array $r): int {
            return match (strtolower(trim((string) ($r['sexo'] ?? '')))) {
                'masculino' => 0,
                'femenino'  => 1,
                default     => 2,
            };
        };

        usort($rows, static function (array $a, array $b) use ($pobOrd, $sexRank): int {
            $oA = (int) ($a['orden'] ?? 0);
            $oB = (int) ($b['orden'] ?? 0);
            if ($oA !== $oB) {
                return $oA <=> $oB;
            }
            $pidA = (int) ($a['paciente_id'] ?? 0);
            $pidB = (int) ($b['paciente_id'] ?? 0);
            $ordPA = $pobOrd[$pidA] ?? 9998;
            $ordPB = $pobOrd[$pidB] ?? 9998;
            if ($ordPA !== $ordPB) {
                return $ordPA <=> $ordPB;
            }
            $sx = $sexRank($a) <=> $sexRank($b);
            if ($sx !== 0) {
                return $sx;
            }
            $nom = strcmp(trim((string) ($a['nombre'] ?? '')), trim((string) ($b['nombre'] ?? '')));
            if ($nom !== 0) {
                return $nom;
            }

            return ((int) ($a['secanacategoria_id'] ?? 0)) <=> ((int) ($b['secanacategoria_id'] ?? 0));
        });

        return $rows;
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
            'paciente_id'        => (int) ($data['paciente_id'] ?? 15),
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
     * Duplica una sub-clase con los mismos datos.
     * Conserva compatibilidad devolviendo el ID de la primera copia creada.
     */
    public function duplicateSecItem(int $id): ?int
    {
        $result = $this->duplicateSecItemMany($id, 1, 'copia_numerada');
        return $result['first_id'] > 0 ? $result['first_id'] : null;
    }

    /**
     * Duplica una sub-clase N veces.
     *
     * @return array{inserted:int, first_id:int}
     */
    public function duplicateSecItemMany(int $id, int $copies = 1, string $nameMode = 'copia_numerada'): array
    {
        $copies = max(1, min(100, $copies));
        $nameMode = in_array($nameMode, ['same', 'copia_numerada'], true) ? $nameMode : 'copia_numerada';
        $row = $this->db->table('secanacategoria')
            ->where('secanacategoria_id', $id)
            ->where('(deleted = 0 OR deleted IS NULL)')
            ->get()
            ->getRowArray();
        if (!$row) {
            return ['inserted' => 0, 'first_id' => 0];
        }

        $hasOrden = $this->hasColumn('secanacategoria', 'orden');
        $nextOrden = 0;
        if ($hasOrden) {
            $max = $this->db->table('secanacategoria')
                ->where('prianacategoria_id', (int) ($row['prianacategoria_id'] ?? 0))
                ->selectMax('orden')
                ->get()
                ->getRow();
            $nextOrden = 1 + (int) ($max->orden ?? 0);
        }

        $firstId = 0;
        $inserted = 0;
        for ($i = 1; $i <= $copies; $i++) {
            $newRow = $row;
            unset($newRow['secanacategoria_id']);
            $baseName = trim((string) ($row['nombre'] ?? ''));
            $newRow['nombre'] = $nameMode === 'same'
                ? $baseName
                : ($baseName . ' (copia ' . $i . ')');
            $newRow['deleted'] = 0;
            if ($hasOrden) {
                $newRow['orden'] = $nextOrden++;
            }
            $ok = $this->db->table('secanacategoria')->insert($newRow);
            if ($ok !== false) {
                $inserted++;
                $newId = (int) $this->db->insertID();
                if ($firstId < 1) {
                    $firstId = $newId;
                }
            }
        }

        return ['inserted' => $inserted, 'first_id' => $firstId];
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
     * Eliminar (soft) sub-clases en lote para una prueba.
     */
    public function deleteSecItemsBulk(int $prianacategoriaId, array $ids): int
    {
        $ids = array_values(array_unique(array_filter(array_map('intval', $ids), static fn(int $v): bool => $v > 0)));
        if ($prianacategoriaId < 1 || $ids === []) {
            return 0;
        }

        $this->db->table('secanacategoria')
            ->where('prianacategoria_id', $prianacategoriaId)
            ->whereIn('secanacategoria_id', $ids)
            ->update(['deleted' => 1]);

        return $this->db->affectedRows();
    }

    /**
     * Guardar o actualizar resultado (priresultados)
     */
    public function savePriResultado(array $data, ?int $id = null): bool
    {
        $save = [
            'prianacategoria_id' => (int) ($data['prianacategoria_id'] ?? 0),
            'id_poblacion'      => (int) ($data['id_poblacion'] ?? 15),
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
     * Actualiza cost, cost_deriv y/o name de varias pruebas (prianacategoria_id => valores).
     *
     * @param array<int, array{cost?: int, cost_deriv?: int, name?: string}> $items
     * @return array{updated: int, skipped: int}
     */
    public function updateCostsBulk(array $items): array
    {
        $updated = 0;
        $skipped = 0;

        if ($items === []) {
            return ['updated' => 0, 'skipped' => 0];
        }

        $this->db->transStart();

        foreach ($items as $id => $row) {
            $id = (int) $id;
            if ($id < 1 || ! is_array($row)) {
                $skipped++;
                continue;
            }

            $update = [];
            if (array_key_exists('cost', $row)) {
                $update['cost'] = max(0, (int) $row['cost']);
            }
            if (array_key_exists('cost_deriv', $row)) {
                $update['cost_deriv'] = max(0, (int) $row['cost_deriv']);
            }
            if (array_key_exists('name', $row)) {
                $name = trim((string) $row['name']);
                if ($name === '') {
                    $skipped++;
                    continue;
                }
                $update['name'] = $name;
            }
            if ($update === []) {
                $skipped++;
                continue;
            }

            $ok = $this->db->table('prianacategoria')
                ->where('prianacategoria_id', $id)
                ->where('(deleted = 0 OR deleted IS NULL)', null, false)
                ->update($update);

            if (! $ok) {
                $skipped++;
                continue;
            }

            if ($this->db->affectedRows() > 0) {
                $updated++;
                continue;
            }

            $exists = $this->db->table('prianacategoria')
                ->where('prianacategoria_id', $id)
                ->where('(deleted = 0 OR deleted IS NULL)', null, false)
                ->countAllResults();

            if ($exists > 0) {
                $updated++;
            } else {
                $skipped++;
            }
        }

        $this->db->transComplete();

        if (! $this->db->transStatus()) {
            return ['updated' => 0, 'skipped' => count($items)];
        }

        return ['updated' => $updated, 'skipped' => $skipped];
    }

    /**
     * IDs de prianacategoria existentes y activos en la BD actual (tenant).
     *
     * @param list<int|string> $ids
     * @return list<int>
     */
    public function existingPrianacategoriaIds(array $ids): array
    {
        $ids = array_values(array_unique(array_filter(array_map(static fn ($id) => (int) $id, $ids), static fn (int $id) => $id > 0)));
        if ($ids === []) {
            return [];
        }

        $rows = $this->db->table('prianacategoria')
            ->select('prianacategoria_id')
            ->whereIn('prianacategoria_id', $ids)
            ->where('(deleted = 0 OR deleted IS NULL)', null, false)
            ->get()
            ->getResultArray();

        $found = [];
        foreach ($rows as $row) {
            $found[] = (int) ($row['prianacategoria_id'] ?? 0);
        }

        return array_values(array_filter($found, static fn (int $id) => $id > 0));
    }

    /**
     * IDs de secanacategoria existentes y activos.
     *
     * @param list<int|string> $ids
     * @return array<int, int> id => prianacategoria_id padre
     */
    public function existingSecanacategoriaMap(array $ids): array
    {
        $ids = array_values(array_unique(array_filter(array_map(static fn ($id) => (int) $id, $ids), static fn (int $id) => $id > 0)));
        if ($ids === []) {
            return [];
        }

        $rows = $this->db->table('secanacategoria')
            ->select('secanacategoria_id, prianacategoria_id')
            ->whereIn('secanacategoria_id', $ids)
            ->where('(deleted = 0 OR deleted IS NULL)', null, false)
            ->get()
            ->getResultArray();

        $map = [];
        foreach ($rows as $row) {
            $secId = (int) ($row['secanacategoria_id'] ?? 0);
            $priId = (int) ($row['prianacategoria_id'] ?? 0);
            if ($secId > 0 && $priId > 0) {
                $map[$secId] = $priId;
            }
        }

        return $map;
    }

    /**
     * IDs de priresultados existentes y activos.
     *
     * @param list<int|string> $ids
     * @return array<int, int> id => prianacategoria_id padre
     */
    public function existingPriresultadosMap(array $ids): array
    {
        $ids = array_values(array_unique(array_filter(array_map(static fn ($id) => (int) $id, $ids), static fn (int $id) => $id > 0)));
        if ($ids === []) {
            return [];
        }

        $rows = $this->db->table('priresultados')
            ->select('priresultados_id, prianacategoria_id')
            ->whereIn('priresultados_id', $ids)
            ->where('(deleted = 0 OR deleted IS NULL)', null, false)
            ->get()
            ->getResultArray();

        $map = [];
        foreach ($rows as $row) {
            $prId = (int) ($row['priresultados_id'] ?? 0);
            $priId = (int) ($row['prianacategoria_id'] ?? 0);
            if ($prId > 0 && $priId > 0) {
                $map[$prId] = $priId;
            }
        }

        return $map;
    }

    /**
     * IDs de tipos de resultado (tabla opciones) existentes.
     *
     * @param list<int|string> $ids
     * @return list<int>
     */
    public function existingOpcionesIds(array $ids): array
    {
        $ids = array_values(array_unique(array_filter(array_map(static fn ($id) => (int) $id, $ids), static fn (int $id) => $id > 0)));
        if ($ids === []) {
            return [];
        }

        $rows = $this->db->table('opciones')
            ->select('opciones_id')
            ->whereIn('opciones_id', $ids)
            ->get()
            ->getResultArray();

        $found = [];
        foreach ($rows as $row) {
            $found[] = (int) ($row['opciones_id'] ?? 0);
        }

        return array_values(array_filter($found, static fn (int $id) => $id > 0));
    }

    /**
     * Actualiza valores de referencia de sub-análisis (prueba compuesta).
     *
     * @param array<int, array<string, mixed>> $items secanacategoria_id => campos
     * @return array{updated: int, skipped: int}
     */
    public function updateValoresReferenciaSecBulk(array $items): array
    {
        $updated = 0;
        $skipped = 0;

        if ($items === []) {
            return ['updated' => 0, 'skipped' => 0];
        }

        $this->db->transStart();

        foreach ($items as $id => $row) {
            $id = (int) $id;
            if ($id < 1 || ! is_array($row)) {
                $skipped++;
                continue;
            }

            $update = [];
            if (array_key_exists('valor_min', $row)) {
                $update['valor_min'] = (string) $row['valor_min'];
            }
            if (array_key_exists('valor_max', $row)) {
                $update['valor_max'] = (string) $row['valor_max'];
            }
            if (array_key_exists('umedida', $row)) {
                $update['umedida'] = (string) $row['umedida'];
            }
            if (array_key_exists('paciente_id', $row)) {
                $update['paciente_id'] = max(0, (int) $row['paciente_id']);
            }
            if (array_key_exists('sexo', $row) && $this->hasColumn('secanacategoria', 'sexo')) {
                $sexo = (string) $row['sexo'];
                $update['sexo'] = in_array($sexo, ['masculino', 'femenino'], true) ? $sexo : 'ambos';
            }
            if (array_key_exists('opcion_id', $row)) {
                $update['opcion_id'] = max(1, (int) $row['opcion_id']);
            }

            if ($update === []) {
                $skipped++;
                continue;
            }

            $ok = $this->db->table('secanacategoria')
                ->where('secanacategoria_id', $id)
                ->where('(deleted = 0 OR deleted IS NULL)', null, false)
                ->update($update);

            if ($ok && $this->db->affectedRows() > 0) {
                $updated++;
            } elseif ($this->db->table('secanacategoria')->where('secanacategoria_id', $id)->where('(deleted = 0 OR deleted IS NULL)', null, false)->countAllResults() > 0) {
                $updated++;
            } else {
                $skipped++;
            }
        }

        $this->db->transComplete();

        if (! $this->db->transStatus()) {
            return ['updated' => 0, 'skipped' => count($items)];
        }

        return ['updated' => $updated, 'skipped' => $skipped];
    }

    /**
     * Actualiza valores de referencia de pruebas simples (priresultados).
     *
     * @param array<int, array<string, mixed>> $items priresultados_id => campos
     * @return array{updated: int, skipped: int}
     */
    public function updateValoresReferenciaPriBulk(array $items): array
    {
        $updated = 0;
        $skipped = 0;

        if ($items === []) {
            return ['updated' => 0, 'skipped' => 0];
        }

        $this->db->transStart();

        foreach ($items as $id => $row) {
            $id = (int) $id;
            if ($id < 1 || ! is_array($row)) {
                $skipped++;
                continue;
            }

            $update = [];
            if (array_key_exists('valor_min', $row)) {
                $update['valor_min'] = (string) $row['valor_min'];
            }
            if (array_key_exists('valor_max', $row)) {
                $update['valor_max'] = (string) $row['valor_max'];
            }
            if (array_key_exists('umedida', $row)) {
                $update['umedida'] = (string) $row['umedida'];
            }
            if (array_key_exists('id_poblacion', $row)) {
                $update['id_poblacion'] = max(0, (int) $row['id_poblacion']);
            }
            if (array_key_exists('sexo', $row) && $this->hasColumn('priresultados', 'sexo')) {
                $sexo = (string) $row['sexo'];
                $update['sexo'] = in_array($sexo, ['masculino', 'femenino'], true) ? $sexo : 'ambos';
            }
            if (array_key_exists('opcion_id', $row)) {
                $update['opcion_id'] = max(1, (int) $row['opcion_id']);
            }

            if ($update === []) {
                $skipped++;
                continue;
            }

            $ok = $this->db->table('priresultados')
                ->where('priresultados_id', $id)
                ->where('(deleted = 0 OR deleted IS NULL)', null, false)
                ->update($update);

            if ($ok && $this->db->affectedRows() > 0) {
                $updated++;
            } elseif ($this->db->table('priresultados')->where('priresultados_id', $id)->where('(deleted = 0 OR deleted IS NULL)', null, false)->countAllResults() > 0) {
                $updated++;
            } else {
                $skipped++;
            }
        }

        $this->db->transComplete();

        if (! $this->db->transStatus()) {
            return ['updated' => 0, 'skipped' => count($items)];
        }

        return ['updated' => $updated, 'skipped' => $skipped];
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

    /**
     * Duplica una prueba completa hacia otra categoría, conservando sus configuraciones.
     */
    public function duplicateAnalysisToParent(int $sourceId, int $targetParentId): ?int
    {
        $source = $this->db->table('prianacategoria')
            ->where('prianacategoria_id', $sourceId)
            ->where('(deleted = 0 OR deleted IS NULL)')
            ->get()
            ->getRowArray();
        if (! $source) {
            return null;
        }

        $sourceParentId = (int) ($source['anacategoria_id'] ?? 0);
        $targetParent = $this->getCategoryInfo($targetParentId);
        if ($targetParentId < 1 || $targetParentId === $sourceParentId || ! ($targetParent->anacategoria_id ?? null)) {
            return null;
        }

        $maxOrder = $this->db->table('prianacategoria')
            ->where('anacategoria_id', $targetParentId)
            ->where('(deleted = 0 OR deleted IS NULL)')
            ->selectMax('order', 'max_order')
            ->get()
            ->getRowArray();

        $newAnalysis = $source;
        unset($newAnalysis['prianacategoria_id']);
        $newAnalysis['anacategoria_id'] = $targetParentId;
        $newAnalysis['order'] = 1 + (int) ($maxOrder['max_order'] ?? 0);
        $newAnalysis['deleted'] = 0;

        $now = RegisterService::mysqlNowForReport();
        if (array_key_exists('created_at', $newAnalysis)) {
            $newAnalysis['created_at'] = $now;
        }
        if (array_key_exists('updated_at', $newAnalysis)) {
            $newAnalysis['updated_at'] = $now;
        }

        $this->db->transStart();
        $this->db->table('prianacategoria')->insert($newAnalysis);
        $newAnalysisId = (int) $this->db->insertID();

        if ($newAnalysisId > 0) {
            $this->duplicateAnalysisRows('secanacategoria', 'secanacategoria_id', $sourceId, $newAnalysisId);
            $this->duplicateAnalysisRows('priresultados', 'priresultados_id', $sourceId, $newAnalysisId);
            $this->duplicateAnalysisRows('manuals', 'manuals_id', $sourceId, $newAnalysisId);
            $this->duplicateAnalysisRows('labotest_reactivo_config', 'config_id', $sourceId, $newAnalysisId);
        }

        $this->db->transComplete();

        if (! $this->db->transStatus() || $newAnalysisId < 1) {
            return null;
        }

        return $newAnalysisId;
    }

    private function duplicateAnalysisRows(string $table, string $primaryKey, int $sourceId, int $newAnalysisId): void
    {
        try {
            $rows = $this->db->table($table)
                ->where('prianacategoria_id', $sourceId)
                ->where('(deleted = 0 OR deleted IS NULL)')
                ->get()
                ->getResultArray();
        } catch (\Throwable $e) {
            return;
        }

        $now = RegisterService::mysqlNowForReport();
        foreach ($rows as $row) {
            unset($row[$primaryKey]);
            $row['prianacategoria_id'] = $newAnalysisId;
            if (array_key_exists('created_at', $row)) {
                $row['created_at'] = $now;
            }
            if (array_key_exists('updated_at', $row)) {
                $row['updated_at'] = $now;
            }
            $this->db->table($table)->insert($row);
        }
    }

    /**
     * Reubica análisis entre categorías y actualiza únicamente su orden dentro de cada padre.
     *
     * @param array<int, array{parent_id:int, children:array<int,int>}> $groups
     */
    public function updateAnalysisPlacement(array $groups): bool
    {
        $cleanGroups = [];
        foreach ($groups as $group) {
            $parentId = (int) ($group['parent_id'] ?? 0);
            $children = array_values(array_unique(array_filter(array_map('intval', (array) ($group['children'] ?? [])))));
            if ($parentId < 1) {
                continue;
            }
            $cleanGroups[$parentId] = $children;
        }

        if ($cleanGroups === []) {
            return false;
        }

        $parentIds = array_keys($cleanGroups);
        $validParents = $this->db->table('anacategoria')
            ->select('anacategoria_id')
            ->whereIn('anacategoria_id', $parentIds)
            ->where('(deleted = 0 OR deleted IS NULL)')
            ->get()
            ->getResultArray();
        $validParentIds = array_map('intval', array_column($validParents, 'anacategoria_id'));
        if ($validParentIds === []) {
            return false;
        }

        $requestedChildIds = [];
        foreach ($cleanGroups as $children) {
            $requestedChildIds = array_merge($requestedChildIds, $children);
        }
        $requestedChildIds = array_values(array_unique(array_filter($requestedChildIds)));

        $validChildIds = [];
        if ($requestedChildIds !== []) {
            $validChildren = $this->db->table('prianacategoria')
                ->select('prianacategoria_id')
                ->whereIn('prianacategoria_id', $requestedChildIds)
                ->where('(deleted = 0 OR deleted IS NULL)')
                ->get()
                ->getResultArray();
            $validChildIds = array_map('intval', array_column($validChildren, 'prianacategoria_id'));
        }
        $validChildLookup = array_flip($validChildIds);

        $this->db->transStart();
        foreach ($cleanGroups as $parentId => $children) {
            if (! in_array((int) $parentId, $validParentIds, true)) {
                continue;
            }

            foreach ($children as $order => $childId) {
                if (! isset($validChildLookup[$childId])) {
                    continue;
                }

                $this->db->table('prianacategoria')
                    ->where('prianacategoria_id', $childId)
                    ->update([
                        'anacategoria_id' => (int) $parentId,
                        'order' => (int) $order,
                    ]);
            }
        }

        foreach ($cleanGroups as $parentId => $children) {
            if (! in_array((int) $parentId, $validParentIds, true)) {
                continue;
            }

            $desired = array_values(array_filter($children, static fn($childId) => isset($validChildLookup[$childId])));
            $desiredLookup = array_flip($desired);
            $currentRows = $this->db->table('prianacategoria')
                ->select('prianacategoria_id')
                ->where('anacategoria_id', (int) $parentId)
                ->where('(deleted = 0 OR deleted IS NULL)')
                ->orderBy('order', 'ASC')
                ->orderBy('prianacategoria_id', 'ASC')
                ->get()
                ->getResultArray();

            $remaining = [];
            foreach ($currentRows as $row) {
                $childId = (int) ($row['prianacategoria_id'] ?? 0);
                if ($childId > 0 && ! isset($desiredLookup[$childId])) {
                    $remaining[] = $childId;
                }
            }

            foreach (array_merge($desired, $remaining) as $order => $childId) {
                $this->db->table('prianacategoria')
                    ->where('prianacategoria_id', $childId)
                    ->update([
                        'anacategoria_id' => (int) $parentId,
                        'order' => (int) $order,
                    ]);
            }
        }
        $this->db->transComplete();

        return $this->db->transStatus();
    }

    /**
     * Construye payload exportable de la configuracion de detalle de una prueba.
     */
    public function buildDetailConfigExport(int $prianacategoriaId): ?array
    {
        $subInfo = $this->getSubInfo($prianacategoriaId, null);
        if (! $subInfo || ! ($subInfo->prianacategoria_id ?? null)) {
            return null;
        }

        $isCompleja = (int) ($subInfo->compleja ?? 0) === 1;
        $payload = [
            'schema_version'    => 1,
            'exported_at'       => date('c'),
            'prianacategoria_id'=> (int) ($subInfo->prianacategoria_id ?? 0),
            'anacategoria_id'   => (int) ($subInfo->anacategoria_id ?? 0),
            'prueba_nombre'     => (string) ($subInfo->name ?? ''),
            'compleja'          => $isCompleja ? 1 : 0,
            'mostrar_valores'   => (int) ($subInfo->mostrar_valores ?? 0),
        ];

        if ($isCompleja) {
            $rows = $this->getSubItems($prianacategoriaId);
            $payload['sub_items'] = array_map(static function (array $r): array {
                return [
                    'nombre'       => (string) ($r['nombre'] ?? ''),
                    'paciente_id'  => (int) ($r['paciente_id'] ?? 15),
                    'sexo'         => (string) ($r['sexo'] ?? 'ambos'),
                    'valor_min'    => (string) ($r['valor_min'] ?? ''),
                    'valor_max'    => (string) ($r['valor_max'] ?? ''),
                    'critico_min'  => (string) ($r['critico_min'] ?? ''),
                    'critico_max'  => (string) ($r['critico_max'] ?? ''),
                    'umedida'      => (string) ($r['umedida'] ?? ''),
                    'formulas_id'  => (int) ($r['formulas_id'] ?? 1),
                    'opcion_id'    => (int) ($r['opcion_id'] ?? 3),
                    'es_separador' => (int) ($r['es_separador'] ?? 0) === 1 ? 1 : 0,
                    'orden'        => (int) ($r['orden'] ?? 0),
                ];
            }, $rows);
        } else {
            $rows = $this->getPriResultados($prianacategoriaId);
            $payload['priresultados'] = array_map(static function (array $r): array {
                return [
                    'id_poblacion' => (int) ($r['id_poblacion'] ?? 15),
                    'sexo'         => (string) ($r['sexo'] ?? 'ambos'),
                    'valor_min'    => (string) ($r['valor_min'] ?? ''),
                    'valor_max'    => (string) ($r['valor_max'] ?? ''),
                    'critico_min'  => (string) ($r['critico_min'] ?? ''),
                    'critico_max'  => (string) ($r['critico_max'] ?? ''),
                    'umedida'      => (string) ($r['umedida'] ?? ''),
                    'formulas_id'  => (int) ($r['formulas_id'] ?? 1),
                    'opcion_id'    => (int) ($r['opcion_id'] ?? 3),
                ];
            }, $rows);
        }

        return $payload;
    }

    /**
     * Importa configuracion de detalle a una prueba existente.
     * Reemplaza completamente filas actuales (soft-delete + insert).
     *
     * @return array{success: bool, message: string, imported?: int}
     */
    public function importDetailConfig(int $prianacategoriaId, array $payload): array
    {
        $subInfo = $this->getSubInfo($prianacategoriaId, null);
        if (! $subInfo || ! ($subInfo->prianacategoria_id ?? null)) {
            return ['success' => false, 'message' => 'Prueba no encontrada'];
        }

        $targetCompleja = (int) ($subInfo->compleja ?? 0) === 1;
        $sourceCompleja = (int) ($payload['compleja'] ?? ($targetCompleja ? 1 : 0)) === 1;
        if ($sourceCompleja !== $targetCompleja) {
            return ['success' => false, 'message' => 'El archivo no corresponde al tipo de prueba (compuesta/no compuesta)'];
        }

        $rows = $targetCompleja
            ? (is_array($payload['sub_items'] ?? null) ? $payload['sub_items'] : [])
            : (is_array($payload['priresultados'] ?? null) ? $payload['priresultados'] : []);

        if (count($rows) === 0) {
            return ['success' => false, 'message' => 'El archivo no contiene filas para importar'];
        }

        $imported = 0;
        $this->db->transStart();
        if ($targetCompleja) {
            $this->db->table('secanacategoria')
                ->where('prianacategoria_id', $prianacategoriaId)
                ->update(['deleted' => 1]);

            foreach ($rows as $idx => $raw) {
                if (! is_array($raw)) {
                    continue;
                }
                $esSeparador = (int) ($raw['es_separador'] ?? 0) === 1;
                $formulaId = (int) ($raw['formulas_id'] ?? 1);
                if (! $this->formulaExists($formulaId)) {
                    $formulaId = 1;
                }
                $insert = [
                    'prianacategoria_id' => $prianacategoriaId,
                    'nombre'             => trim((string) ($raw['nombre'] ?? '')),
                    'paciente_id'        => (int) ($raw['paciente_id'] ?? 15),
                    'valor_min'          => (string) ($raw['valor_min'] ?? ''),
                    'valor_max'          => (string) ($raw['valor_max'] ?? ''),
                    'critico_min'        => (string) ($raw['critico_min'] ?? ''),
                    'critico_max'        => (string) ($raw['critico_max'] ?? ''),
                    'umedida'            => (string) ($raw['umedida'] ?? ''),
                    'formulas_id'        => $esSeparador ? 1 : $formulaId,
                    'opcion_id'          => $esSeparador ? 3 : (int) ($raw['opcion_id'] ?? 3),
                    'deleted'            => 0,
                ];
                if ($this->hasColumn('secanacategoria', 'sexo')) {
                    $sexo = strtolower(trim((string) ($raw['sexo'] ?? 'ambos')));
                    $insert['sexo'] = in_array($sexo, ['masculino', 'femenino'], true) ? $sexo : 'ambos';
                }
                if ($this->hasColumn('secanacategoria', 'es_separador')) {
                    $insert['es_separador'] = $esSeparador ? 1 : 0;
                }
                if ($esSeparador) {
                    $insert['valor_min']   = '';
                    $insert['valor_max']   = '';
                    $insert['critico_min'] = '';
                    $insert['critico_max'] = '';
                    $insert['umedida']     = '';
                }
                if ($this->hasColumn('secanacategoria', 'orden')) {
                    $insert['orden'] = (int) ($raw['orden'] ?? $idx);
                }
                $ok = $this->db->table('secanacategoria')->insert($insert);
                if ($ok !== false) {
                    $imported++;
                }
            }
        } else {
            $this->db->table('priresultados')
                ->where('prianacategoria_id', $prianacategoriaId)
                ->update(['deleted' => 1]);

            foreach ($rows as $raw) {
                if (! is_array($raw)) {
                    continue;
                }
                $formulaId = (int) ($raw['formulas_id'] ?? 1);
                if (! $this->formulaExists($formulaId)) {
                    $formulaId = 1;
                }
                $insert = [
                    'prianacategoria_id' => $prianacategoriaId,
                    'id_poblacion'       => (int) ($raw['id_poblacion'] ?? 15),
                    'valor_min'          => (string) ($raw['valor_min'] ?? ''),
                    'valor_max'          => (string) ($raw['valor_max'] ?? ''),
                    'critico_min'        => (string) ($raw['critico_min'] ?? ''),
                    'critico_max'        => (string) ($raw['critico_max'] ?? ''),
                    'umedida'            => (string) ($raw['umedida'] ?? ''),
                    'formulas_id'        => $formulaId,
                    'opcion_id'          => (int) ($raw['opcion_id'] ?? 3),
                    'deleted'            => 0,
                ];
                if ($this->hasColumn('priresultados', 'sexo')) {
                    $sexo = strtolower(trim((string) ($raw['sexo'] ?? 'ambos')));
                    $insert['sexo'] = in_array($sexo, ['masculino', 'femenino'], true) ? $sexo : 'ambos';
                }
                $ok = $this->db->table('priresultados')->insert($insert);
                if ($ok !== false) {
                    $imported++;
                }
            }
        }
        $this->db->transComplete();
        if (! $this->db->transStatus()) {
            return ['success' => false, 'message' => 'No se pudo completar la importación'];
        }

        return [
            'success'  => true,
            'message'  => 'Configuración importada correctamente',
            'imported' => $imported,
        ];
    }

    private function formulaExists(int $formulasId): bool
    {
        if ($formulasId < 1) {
            return false;
        }
        return $this->db->table('formulas')
            ->where('formulas_id', $formulasId)
            ->countAllResults() > 0;
    }
}
