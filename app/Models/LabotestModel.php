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

    public const COMPLEJA_SIMPLE       = 0;
    public const COMPLEJA_COMPOUESTA   = 1;
    public const COMPLEJA_CULTIVO      = 2;
    public const COMPLEJA_PERSONALIZADO = 3;

    /** Sin gráfica de comparación seriada en reporte. */
    public const GRAFICAR_NO = 0;
    /** Solo tabla heatmap de comparación (M1/M2/M3). */
    public const GRAFICAR_SI = 1;
    /** Tabla seriada habitual y, al final, heatmap de comparación. */
    public const GRAFICAR_AMBOS = 2;

    public static function normalizeGraficar(int $value): int
    {
        return match ($value) {
            self::GRAFICAR_SI, self::GRAFICAR_AMBOS => $value,
            default => self::GRAFICAR_NO,
        };
    }

    /** Slug para exportación JSON según tipo de análisis. */
    public static function tipoAnalisisSlug(int $compleja): string
    {
        return match ($compleja) {
            self::COMPLEJA_COMPOUESTA   => 'tabla',
            self::COMPLEJA_CULTIVO      => 'cultivo',
            self::COMPLEJA_PERSONALIZADO => 'personalizado',
            default                     => 'simple',
        };
    }

    public static function esMatrizConfigurable(int $compleja): bool
    {
        return in_array($compleja, [self::COMPLEJA_CULTIVO, self::COMPLEJA_PERSONALIZADO], true);
    }

    /**
     * Obtiene categorías con sus análisis (prianacategoria)
     * @param string|null $search Filtra por nombre de grupo o de examen
     */
    public function getAllWithAnalysis(?string $search = null, ?int $categoriaId = null): array
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
            ->orderBy("{$ana}.anacategoria_id", 'ASC')
            ->orderBy("{$pri}.order", 'ASC')
            ->orderBy("{$pri}.prianacategoria_id", 'ASC');

        if ($this->hasColumn('prianacategoria', 'tipo_muestra_id')) {
            $builder->join('tipo_muestra tm', "{$pri}.tipo_muestra_id = tm.tipo_muestra_id", 'left');
        }
        if ($this->hasColumn('prianacategoria', 'metodo_id')) {
            $builder->join('metodo me', "{$pri}.metodo_id = me.metodo_id", 'left');
        }

        if ($categoriaId !== null && $categoriaId > 0) {
            $builder->where("{$ana}.anacategoria_id", $categoriaId);
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
    public function getGroupedByCategory(?string $search = null, ?int $categoriaId = null): array
    {
        $rows = $this->getAllWithAnalysis($search, $categoriaId);
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
                    'order'          => (int) ($row->pria_order ?? 0),
                    'cost'           => $row->cost,
                    'cost_deriv'     => $row->cost_deriv,
                    'compleja'       => $row->compleja,
                    'tipo_muestra'   => trim((string) ($row->tipo_muestra_nombre ?? '')),
                    'metodo'         => trim((string) ($row->metodo_nombre ?? '')),
                ];
            }
        }

        foreach ($grouped as &$category) {
            usort($category['items'], static function (array $a, array $b): int {
                $orderCmp = ((int) ($a['order'] ?? 0)) <=> ((int) ($b['order'] ?? 0));
                if ($orderCmp !== 0) {
                    return $orderCmp;
                }

                return ((int) ($a['id'] ?? 0)) <=> ((int) ($b['id'] ?? 0));
            });
        }
        unset($category);

        $out = array_values($grouped);
        usort($out, static function (array $a, array $b): int {
            $orderCmp = ((int) ($a['order'] ?? 0)) <=> ((int) ($b['order'] ?? 0));
            if ($orderCmp !== 0) {
                return $orderCmp;
            }

            return ((int) ($a['id'] ?? 0)) <=> ((int) ($b['id'] ?? 0));
        });

        return $out;
    }

    /**
     * Obtiene categorías agrupadas con paginación (6 cajas por página) y búsqueda
     */
    public function getGroupedByCategoryPaginated(int $perPage = 6, int $page = 1, ?string $search = null, ?int $categoriaId = null): array
    {
        $all = $this->getGroupedByCategory($search, $categoriaId);
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
                'graficar'          => 0,
                'tipo_muestra_id'   => null,
                'metodo_id'         => null,
            ];
        }
        $row = $this->db->table('prianacategoria')
            ->where('prianacategoria_id', (int) $id)
            ->where('(deleted = 0 OR deleted IS NULL)')
            ->get()
            ->getRow();
        return $row ?? (object) ['prianacategoria_id' => null, 'anacategoria_id' => $anacategoriaId, 'name' => '', 'order' => 0, 'compleja' => 0, 'mostrar_valores' => 0, 'graficar' => 0, 'tipo_muestra_id' => null, 'metodo_id' => null];
    }

    /** @var array<string, string> */
    public const CULTIVO_SECCION_LABELS = [
        'encabezado' => 'Encabezado',
        'cuerpo'     => 'Cuerpo',
        'pie'        => 'Pie',
    ];

    /**
     * Configuración por defecto de la matriz de cultivo (bloques encabezado, cuerpo, pie).
     *
     * @return array{version: int, bloques: list<array<string, mixed>>}
     */
    public function getDefaultCultivoMatrizConfig(): array
    {
        return [
            'version' => 2,
            'bloques' => [
                $this->defaultCultivoBloque('encabezado', 'encabezado'),
                $this->defaultCultivoBloque('cuerpo', 'cuerpo'),
                $this->defaultCultivoBloque('pie', 'pie'),
            ],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function defaultCultivoBloque(string $id, string $tipo): array
    {
        $bloque = [
            'id'       => $id,
            'tipo'     => $tipo,
            'filas'    => 1,
            'columnas' => 1,
            'titulos'  => [[]],
            'celdas'   => [[['modo' => 'texto']]],
        ];
        if ($tipo === 'cuerpo') {
            $bloque['valores_habilitado']  = false;
            $bloque['unidades_habilitado'] = false;
            $bloque['unidad']              = '';
            $bloque['alineacion_filas']    = 'centro';
        }

        return $bloque;
    }

    /**
     * @param array<string, mixed>|null $config
     * @return list<array<string, mixed>>
     */
    public static function resolveCultivoMatrizBloques(?array $config): array
    {
        if (! is_array($config)) {
            return model(self::class)->getDefaultCultivoMatrizConfig()['bloques'];
        }
        if (isset($config['bloques']) && is_array($config['bloques'])) {
            return model(self::class)->normalizeCultivoMatrizConfig($config)['bloques'];
        }

        return model(self::class)->migrateLegacyCultivoMatrizToBloques($config);
    }

    /**
     * @param array<string, mixed>|null $config
     * @return list<array<string, mixed>>
     */
    public static function resolvePersonalizadoMatrizBloques(?array $config): array
    {
        if (! is_array($config)) {
            return model(self::class)->getDefaultCultivoMatrizConfig()['bloques'];
        }

        return model(self::class)->normalizePersonalizadoMatrizConfig($config)['bloques'];
    }

    /**
     * @param array<string, mixed> $config
     * @return list<array<string, mixed>>
     */
    public function migrateLegacyCultivoMatrizToBloques(array $config): array
    {
        $bloques = [];
        foreach (array_keys(self::CULTIVO_SECCION_LABELS) as $tipo) {
            if (! isset($config[$tipo]) || ! is_array($config[$tipo])) {
                continue;
            }
            $bloques[] = array_merge(['id' => $tipo, 'tipo' => $tipo], $config[$tipo]);
        }
        if ($bloques === []) {
            return $this->getDefaultCultivoMatrizConfig()['bloques'];
        }

        return $this->normalizeCultivoMatrizConfig(['version' => 2, 'bloques' => $bloques])['bloques'];
    }

    /**
     * @param list<string> $existingIds
     */
    public static function generateCultivoBloqueId(string $tipo, array $existingIds): string
    {
        if (! isset(self::CULTIVO_SECCION_LABELS[$tipo])) {
            $tipo = 'encabezado';
        }
        if (! in_array($tipo, $existingIds, true)) {
            return $tipo;
        }
        $n = 2;
        while (in_array($tipo . '_' . $n, $existingIds, true)) {
            $n++;
        }

        return $tipo . '_' . $n;
    }

    /**
     * @param array<string, mixed> $bloque
     */
    public static function cultivoBloqueDisplayLabel(array $bloque, array $allBloques = []): string
    {
        $tipo = (string) ($bloque['tipo'] ?? 'encabezado');
        $base = self::CULTIVO_SECCION_LABELS[$tipo] ?? ucfirst($tipo);
        $id = (string) ($bloque['id'] ?? $tipo);
        if ($id === $tipo) {
            $sameTipo = 0;
            foreach ($allBloques as $b) {
                if (($b['tipo'] ?? '') === $tipo) {
                    $sameTipo++;
                }
            }
            if ($sameTipo <= 1) {
                return $base;
            }
        }
        if (preg_match('/^' . preg_quote($tipo, '/') . '_(\d+)$/', $id, $m)) {
            return $base . ' ' . $m[1];
        }

        return $base . ' (' . $id . ')';
    }

    /**
     * @param array<string, mixed> $bloque
     * @return array<string, mixed>
     */
    private function normalizeCultivoBloque(array $bloque): array
    {
        $tipo = (string) ($bloque['tipo'] ?? 'encabezado');
        if (! isset(self::CULTIVO_SECCION_LABELS[$tipo])) {
            $tipo = 'encabezado';
        }
        $id = trim((string) ($bloque['id'] ?? $tipo));
        if ($id === '' || ! preg_match('/^[a-z][a-z0-9_]{0,47}$/', $id)) {
            $id = $tipo;
        }

        $filas = max(0, min(50, (int) ($bloque['filas'] ?? 1)));
        $columnas = max(1, min(20, (int) ($bloque['columnas'] ?? 1)));
        $oldTitulos = is_array($bloque['titulos'] ?? null) ? $bloque['titulos'] : [];
        $oldCeldas = is_array($bloque['celdas'] ?? null) ? $bloque['celdas'] : [];
        $titulos = $this->normalizeCultivoTitulosPorColumna($oldTitulos, $columnas);

        $celdas = [];
        for ($r = 0; $r < $filas; $r++) {
            $celdas[$r] = [];
            for ($c = 0; $c < $columnas; $c++) {
                $celdas[$r][$c] = $this->normalizeCultivoCelda($oldCeldas[$r][$c] ?? ['modo' => 'texto']);
            }
        }

        $out = [
            'id'       => $id,
            'tipo'     => $tipo,
            'filas'    => $filas,
            'columnas' => $columnas,
            'titulos'  => $titulos,
            'celdas'   => $celdas,
        ];

        if ($tipo === 'cuerpo') {
            $out['valores_habilitado'] = (int) ($bloque['valores_habilitado'] ?? 0) === 1
                || ($bloque['valores_habilitado'] ?? false) === true;
            $out['unidades_habilitado'] = (int) ($bloque['unidades_habilitado'] ?? 0) === 1
                || ($bloque['unidades_habilitado'] ?? false) === true;
            $out['unidad'] = trim((string) ($bloque['unidad'] ?? ''));
            $aliRaw = trim((string) ($bloque['alineacion_filas'] ?? 'centro'));
            if ($aliRaw === 'cuerpo') {
                $aliRaw = 'centro';
            }
            $out['alineacion_filas'] = in_array($aliRaw, ['centro', 'bordes'], true) ? $aliRaw : 'centro';
        }

        return $out;
    }

    /**
     * @return array<string, mixed>
     */
    private function normalizeCultivoCelda(mixed $raw, bool $conExtrasPersonalizado = false): array
    {
        $valor = is_array($raw) ? trim((string) ($raw['valor'] ?? '')) : '';
        $out = ['modo' => 'texto'];

        if (is_array($raw)) {
            $modoRaw = (string) ($raw['modo'] ?? 'texto');
            if ($modoRaw === 'opcion') {
                $out = [
                    'modo'       => 'opcion',
                    'opcion_id'  => max(0, (int) ($raw['opcion_id'] ?? 0)),
                ];
            } elseif ($modoRaw === 'texto_rico') {
                $out = ['modo' => 'texto_rico'];
            } elseif ($modoRaw === 'texto_fijo') {
                $out = ['modo' => 'texto_fijo', 'rol' => 'titulo'];
            } elseif ($modoRaw === 'leyenda') {
                $out = [
                    'modo'                         => 'leyenda',
                    'leyenda_cultivo_categoria_id' => max(0, (int) ($raw['leyenda_cultivo_categoria_id'] ?? 0)),
                ];
            }
        } elseif (is_numeric($raw)) {
            $id = (int) $raw;
            if ($id > 0) {
                $out = ['modo' => 'opcion', 'opcion_id' => $id];
            }
        }

        if ($valor !== '') {
            $out['valor'] = $valor;
        }

        if ($conExtrasPersonalizado && is_array($raw)) {
            $ali = trim((string) ($raw['alineacion'] ?? 'izquierda'));
            if (! in_array($ali, ['izquierda', 'centro', 'derecha'], true)) {
                $ali = 'izquierda';
            }
            $out['alineacion'] = $ali;

            $fuente = trim((string) ($raw['fuente'] ?? 'normal'));
            if (! in_array($fuente, ['normal', 'negrita', 'titulo', 'enriquecido'], true)) {
                $fuente = 'normal';
            }
            $out['fuente'] = $fuente;

            $rol = trim((string) ($raw['rol'] ?? 'input'));
            if (! in_array($rol, ['input', 'titulo', 'etiqueta'], true)) {
                $rol = 'input';
            }
            $out['rol'] = $rol;

            $rowspan = max(1, min(50, (int) ($raw['rowspan'] ?? 1)));
            $out['rowspan'] = $rowspan;

            $colspan = max(1, min(20, (int) ($raw['colspan'] ?? 1)));
            $out['colspan'] = $colspan;

            $textoFijo = trim((string) ($raw['texto_fijo'] ?? ''));
            if ($textoFijo !== '' && ($fuente === 'enriquecido' || $textoFijo !== strip_tags($textoFijo))) {
                helper('registro');
                $textoFijo = registro_sanitizar_html_rico($textoFijo);
            }
            if ($textoFijo !== '' || in_array($rol, ['titulo', 'etiqueta'], true) || $fuente === 'enriquecido' || ($out['modo'] ?? '') === 'texto_fijo') {
                $out['texto_fijo'] = $textoFijo;
            }
            if (($out['modo'] ?? '') === 'texto_fijo') {
                $out['rol'] = 'titulo';
            }
        }

        return $out;
    }

    /**
     * Estilo de bloque personalizado en reporte (fondo tabla/títulos, bordes).
     *
     * @param array<string, mixed> $bloque
     * @return array{
     *   reporte_fondo_tabla: string,
     *   reporte_fondo_titulos: string,
     *   reporte_borde_modo: string,
     *   reporte_borde_ancho: int,
     *   reporte_borde_estilo: string,
     *   reporte_borde_color: string
     * }
     */
    public static function normalizePersonalizadoReporteEstiloBloque(array $bloque): array
    {
        $fondoTabla = trim((string) ($bloque['reporte_fondo_tabla'] ?? 'transparente'));
        if ($fondoTabla !== 'transparente' && ! preg_match('/^#[0-9A-Fa-f]{3,8}$/', $fondoTabla)) {
            $fondoTabla = 'transparente';
        }

        $fondoTitulos = trim((string) ($bloque['reporte_fondo_titulos'] ?? 'transparente'));
        if ($fondoTitulos !== 'transparente' && ! preg_match('/^#[0-9A-Fa-f]{3,8}$/', $fondoTitulos)) {
            $fondoTitulos = 'transparente';
        }

        $bordeModo = trim((string) ($bloque['reporte_borde_modo'] ?? 'default'));
        if (! in_array($bordeModo, ['default', 'none', 'custom'], true)) {
            $bordeModo = 'default';
        }

        $bordeAncho = max(0, min(10, (int) ($bloque['reporte_borde_ancho'] ?? 1)));
        $bordeEstilo = trim((string) ($bloque['reporte_borde_estilo'] ?? 'solid'));
        if (! in_array($bordeEstilo, ['solid', 'dashed', 'dotted', 'double'], true)) {
            $bordeEstilo = 'solid';
        }

        $bordeColor = trim((string) ($bloque['reporte_borde_color'] ?? '#cccccc'));
        if (! preg_match('/^#[0-9A-Fa-f]{3,8}$/', $bordeColor)) {
            $bordeColor = '#cccccc';
        }

        return [
            'reporte_fondo_tabla'   => $fondoTabla,
            'reporte_fondo_titulos' => $fondoTitulos,
            'reporte_borde_modo'    => $bordeModo,
            'reporte_borde_ancho'   => $bordeAncho,
            'reporte_borde_estilo'  => $bordeEstilo,
            'reporte_borde_color'   => $bordeColor,
        ];
    }

    /**
     * @param array<string, mixed> $cfg Celda personalizada (alineacion, fuente)
     */
    public static function buildPersonalizadoCeldaReporteStyle(array $cfg): string
    {
        $styles = [];
        $ali = trim((string) ($cfg['alineacion'] ?? 'izquierda'));
        $map = ['izquierda' => 'left', 'centro' => 'center', 'derecha' => 'right'];
        $styles[] = 'text-align:' . ($map[$ali] ?? 'left');

        $fuente = trim((string) ($cfg['fuente'] ?? 'normal'));
        if ($fuente === 'negrita') {
            $styles[] = 'font-weight:700';
        } elseif ($fuente === 'titulo') {
            $styles[] = 'font-weight:700';
            $styles[] = 'font-size:1.1em';
        }

        return implode(';', $styles);
    }

    /**
     * @param array<string, mixed> $estilo
     */
    public static function buildPersonalizadoReporteTableStyleAttr(array $estilo): string
    {
        $styles = [];
        $fondo = (string) ($estilo['reporte_fondo_tabla'] ?? 'transparente');
        if ($fondo !== 'transparente') {
            $styles[] = 'background-color:' . $fondo;
        }

        $bordeModo = (string) ($estilo['reporte_borde_modo'] ?? 'default');
        if ($bordeModo === 'none') {
            $styles[] = 'border:none';
            $styles[] = 'border-collapse:collapse';
        } elseif ($bordeModo === 'custom') {
            $styles[] = self::personalizadoReporteBorderCss($estilo);
            $styles[] = 'border-collapse:collapse';
        }

        return implode(';', $styles);
    }

    /**
     * @param array<string, mixed> $estilo
     */
    public static function buildPersonalizadoReporteThStyleAttr(array $estilo): string
    {
        $styles = [];
        $fondo = (string) ($estilo['reporte_fondo_titulos'] ?? 'transparente');
        if ($fondo !== 'transparente') {
            $styles[] = 'background-color:' . $fondo;
        }

        $bordeModo = (string) ($estilo['reporte_borde_modo'] ?? 'default');
        if ($bordeModo === 'none') {
            $styles[] = 'border:none';
        } elseif ($bordeModo === 'custom') {
            $styles[] = self::personalizadoReporteBorderCss($estilo);
        }

        return implode(';', $styles);
    }

    /**
     * @param array<string, mixed> $estilo
     */
    public static function buildPersonalizadoReporteTdBorderStyleAttr(array $estilo): string
    {
        $bordeModo = (string) ($estilo['reporte_borde_modo'] ?? 'default');
        if ($bordeModo === 'none') {
            return 'border:none';
        }
        if ($bordeModo === 'custom') {
            return self::personalizadoReporteBorderCss($estilo);
        }

        return '';
    }

    /**
     * @param array<string, mixed> $estilo
     */
    private static function personalizadoReporteBorderCss(array $estilo): string
    {
        $w = max(0, (int) ($estilo['reporte_borde_ancho'] ?? 1));
        $est = (string) ($estilo['reporte_borde_estilo'] ?? 'solid');
        $col = (string) ($estilo['reporte_borde_color'] ?? '#cccccc');

        return 'border:' . $w . 'px ' . $est . ' ' . $col;
    }

    /**
     * @return array<string, mixed>|null
     */
    private function decodeCultivoMatrizConfigRaw(int $prianacategoriaId): ?array
    {
        if (! $this->ensureCultivoMatrizConfigColumn()) {
            return null;
        }

        $row = $this->db->table('prianacategoria')
            ->select('cultivo_matriz_config')
            ->where('prianacategoria_id', $prianacategoriaId)
            ->where('(deleted = 0 OR deleted IS NULL)')
            ->get()
            ->getRow();

        if (! $row || trim((string) ($row->cultivo_matriz_config ?? '')) === '') {
            return null;
        }

        $decoded = json_decode((string) $row->cultivo_matriz_config, true);

        return is_array($decoded) ? $decoded : null;
    }

    /**
     * @param array<string, mixed>|null $config
     * @return array{version: int, bloques: list<array<string, mixed>>}
     */
    public function normalizePersonalizadoMatrizConfig(?array $config): array
    {
        $origById = [];
        $origBloques = [];
        if (is_array($config['bloques'] ?? null)) {
            foreach ($config['bloques'] as $bloqueRaw) {
                if (! is_array($bloqueRaw)) {
                    continue;
                }
                $origBloques[] = $bloqueRaw;
                $bid = (string) ($bloqueRaw['id'] ?? '');
                if ($bid !== '') {
                    $origById[$bid] = $bloqueRaw;
                }
            }
        }

        $normalized = $this->normalizeCultivoMatrizConfig($config);
        foreach ($normalized['bloques'] as $idx => &$bloque) {
            $bloqueId = (string) ($bloque['id'] ?? '');
            $origBloque = $origById[$bloqueId] ?? ($origBloques[$idx] ?? []);
            $origCeldas = is_array($origBloque['celdas'] ?? null) ? $origBloque['celdas'] : [];
            $filas = max(0, (int) ($bloque['filas'] ?? 0));
            $columnas = max(1, (int) ($bloque['columnas'] ?? 1));
            $celdas = [];
            for ($r = 0; $r < $filas; $r++) {
                $celdas[$r] = [];
                for ($c = 0; $c < $columnas; $c++) {
                    $rawCell = $origCeldas[$r][$c] ?? ($bloque['celdas'][$r][$c] ?? ['modo' => 'texto']);
                    $celdas[$r][$c] = $this->normalizeCultivoCelda($rawCell, true);
                }
            }
            $bloque['celdas'] = $celdas;
            $bloque = array_merge($bloque, self::normalizePersonalizadoReporteEstiloBloque($origBloque));
        }
        unset($bloque);

        return $normalized;
    }

    /**
     * @return array{version: int, bloques: list<array<string, mixed>>}
     */
    public function getPersonalizadoMatrizConfig(int $prianacategoriaId): array
    {
        $raw = $this->decodeCultivoMatrizConfigRaw($prianacategoriaId);

        return $this->normalizePersonalizadoMatrizConfig($raw ?? $this->getDefaultCultivoMatrizConfig());
    }

    /**
     * @param array<string, mixed> $config
     */
    public function savePersonalizadoMatrizConfig(int $prianacategoriaId, array $config): bool
    {
        if ($prianacategoriaId < 1 || ! $this->ensureCultivoMatrizConfigColumn()) {
            return false;
        }

        $normalized = $this->normalizePersonalizadoMatrizConfig($config);
        $json = json_encode($normalized, JSON_UNESCAPED_UNICODE);
        if ($json === false) {
            return false;
        }

        return $this->db->table('prianacategoria')
            ->where('prianacategoria_id', $prianacategoriaId)
            ->update(['cultivo_matriz_config' => $json]) !== false;
    }

    /**
     * Normaliza una celda de título de matriz cultivo.
     *
     * @return array{texto: string, colspan: int}
     */
    public static function normalizeCultivoTituloCelda(mixed $raw): array
    {
        if (is_array($raw)) {
            $texto = trim((string) ($raw['texto'] ?? $raw['text'] ?? ''));
            $colspan = max(1, min(20, (int) ($raw['colspan'] ?? 1)));

            return ['texto' => $texto, 'colspan' => $colspan];
        }

        return ['texto' => trim((string) $raw), 'colspan' => 1];
    }

    /**
     * @param list<mixed> $titulosRaw
     * @return list<list<array{texto: string, colspan: int}>>
     */
    public static function parseCultivoTitulosPorColumna(array $titulosRaw, int $columnas): array
    {
        $columnas = max(1, $columnas);
        $isFlat = $titulosRaw === [] || ! is_array($titulosRaw[0] ?? null);
        $out = [];

        for ($c = 0; $c < $columnas; $c++) {
            if ($isFlat) {
                $val = (string) ($titulosRaw[$c] ?? '');
                $out[$c] = $val === '' ? [] : [self::normalizeCultivoTituloCelda($val)];
            } else {
                $colRaw = $titulosRaw[$c] ?? [];
                if (! is_array($colRaw)) {
                    $val = (string) $colRaw;
                    $out[$c] = $val === '' ? [] : [self::normalizeCultivoTituloCelda($val)];
                } else {
                    $col = [];
                    foreach ($colRaw as $t) {
                        $col[] = self::normalizeCultivoTituloCelda($t);
                    }
                    $out[$c] = array_slice($col, 0, 20);
                }
            }
        }

        return $out;
    }

    /**
     * Máximo de filas de título según índices (conserva huecos al omitir títulos cubiertos).
     *
     * @param list<list<array{texto: string, colspan: int}>> $titulosPorCol
     */
    public static function maxCultivoTituloFilasPorColumna(array $titulosPorCol, int $columnas): int
    {
        $columnas = max(1, $columnas);
        $maxFilas = 0;
        for ($c = 0; $c < $columnas; $c++) {
            $col = $titulosPorCol[$c] ?? [];
            if (! is_array($col) || $col === []) {
                continue;
            }
            $maxFilas = max($maxFilas, max(array_keys($col)) + 1);
        }

        return $maxFilas;
    }

    /**
     * Filas de thead con colspan para títulos de matriz cultivo.
     *
     * @param list<list<array{texto: string, colspan: int}>> $titulosPorCol
     * @return list<list<array{texto: string, colspan: int}>>
     */
    public static function buildCultivoTituloFilasTabla(array $titulosPorCol, int $columnas): array
    {
        $columnas = max(1, $columnas);
        $maxFilas = self::maxCultivoTituloFilasPorColumna($titulosPorCol, $columnas);
        if ($maxFilas < 1) {
            return [];
        }

        $filas = [];
        for ($tr = 0; $tr < $maxFilas; $tr++) {
            $fila = [];
            $cubiertasHasta = 0;
            for ($c = 0; $c < $columnas; $c++) {
                if ($c < $cubiertasHasta) {
                    continue;
                }
                $cell = self::normalizeCultivoTituloCelda($titulosPorCol[$c][$tr] ?? null);
                $colspan = max(1, min($cell['colspan'], $columnas - $c));
                $fila[] = [
                    'texto'   => (string) $cell['texto'],
                    'colspan' => $colspan,
                ];
                $cubiertasHasta = $c + $colspan;
            }
            $filas[] = $fila;
        }

        return $filas;
    }

    public static function cultivoCeldaTieneValor(mixed $val): bool
    {
        return trim(strip_tags((string) $val)) !== '';
    }

    /**
     * Indica si el título en ($col, $tr) queda cubierto por colspan de otra columna.
     *
     * @param list<list<array{texto: string, colspan: int}>> $titulosPorCol
     */
    public static function cultivoTituloCeldaEstaCubierta(array $titulosPorCol, int $columnasTotal, int $col, int $tr): bool
    {
        $columnasTotal = max(1, $columnasTotal);
        $cubiertasHasta = 0;
        for ($c = 0; $c < $columnasTotal; $c++) {
            if ($c < $cubiertasHasta) {
                if ($c === $col) {
                    return true;
                }

                continue;
            }
            $cell = self::normalizeCultivoTituloCelda($titulosPorCol[$c][$tr] ?? null);
            $colspan = max(1, min((int) $cell['colspan'], $columnasTotal - $c));
            if ($c !== $col && $c <= $col && $col < $c + $colspan) {
                return true;
            }
            $cubiertasHasta = $c + $colspan;
        }

        return false;
    }

    /**
     * @param list<int> $columnasActivas
     * @param list<list<array{texto: string, colspan: int}>> $titulosPorCol
     * @return list<list<array{texto: string, colspan: int}>>
     */
    public static function remapCultivoTitulosColumnasActivas(array $titulosPorCol, array $columnasActivas, int $columnasTotal): array
    {
        if ($columnasActivas === []) {
            return [];
        }

        $activeSet = array_flip($columnasActivas);
        $newPorCol = [];

        foreach ($columnasActivas as $newC => $oldC) {
            $stack = is_array($titulosPorCol[$oldC] ?? null) ? $titulosPorCol[$oldC] : [];
            $newStack = [];
            foreach ($stack as $tr => $titleCell) {
                if (self::cultivoTituloCeldaEstaCubierta($titulosPorCol, $columnasTotal, $oldC, (int) $tr)) {
                    continue;
                }
                $cell = self::normalizeCultivoTituloCelda($titleCell);
                if ((string) $cell['texto'] === '') {
                    continue;
                }
                $spanStart = $oldC;
                $spanEnd = min($oldC + (int) $cell['colspan'] - 1, $columnasTotal - 1);
                $firstActiveInSpan = null;
                for ($oc = $spanStart; $oc <= $spanEnd; $oc++) {
                    if (isset($activeSet[$oc])) {
                        $firstActiveInSpan = $oc;
                        break;
                    }
                }
                if ($firstActiveInSpan !== $oldC) {
                    continue;
                }
                $activeCount = 0;
                for ($oc = $spanStart; $oc <= $spanEnd; $oc++) {
                    if (isset($activeSet[$oc])) {
                        $activeCount++;
                    }
                }
                $cell['colspan'] = max(1, $activeCount);
                $newStack[(int) $tr] = $cell;
            }
            $newPorCol[$newC] = $newStack;
        }

        for ($nc = 0, $n = count($columnasActivas); $nc < $n; $nc++) {
            if (! isset($newPorCol[$nc])) {
                $newPorCol[$nc] = [];
            }
        }
        ksort($newPorCol);

        return array_values($newPorCol);
    }

    /**
     * @param list<list<array{texto: string, colspan: int}>> $titulosFilas
     * @return list<list<array{texto: string, colspan: int}>>
     */
    public static function filterEmptyCultivoTituloFilas(array $titulosFilas): array
    {
        return array_values(array_filter($titulosFilas, static function (array $fila): bool {
            foreach ($fila as $th) {
                if (trim((string) ($th['texto'] ?? '')) !== '') {
                    return true;
                }
            }

            return false;
        }));
    }

    /**
     * Título con colspan que cubre la columna $col en la fila $tr.
     *
     * @param list<list<array{texto: string, colspan: int}>> $titulosPorCol
     * @return array{texto: string, colspan: int}|null
     */
    public static function findCultivoTituloSpanSobreColumna(array $titulosPorCol, int $columnasTotal, int $col, int $tr): ?array
    {
        $columnasTotal = max(1, $columnasTotal);
        $cubiertasHasta = 0;
        for ($c = 0; $c < $columnasTotal; $c++) {
            if ($c < $cubiertasHasta) {
                continue;
            }
            $cell = self::normalizeCultivoTituloCelda($titulosPorCol[$c][$tr] ?? null);
            if ((string) $cell['texto'] === '') {
                $cubiertasHasta = $c + 1;

                continue;
            }
            $colspan = max(1, min((int) $cell['colspan'], $columnasTotal - $c));
            if ($c <= $col && $col < $c + $colspan) {
                return $cell;
            }
            $cubiertasHasta = $c + $colspan;
        }

        return null;
    }

    /**
     * Columna donde inicia el título que cubre ($col, $tr).
     *
     * @param list<list<array{texto: string, colspan: int}>> $titulosPorCol
     */
    public static function findCultivoTituloSpanColumnaInicio(array $titulosPorCol, int $columnasTotal, int $col, int $tr): ?int
    {
        $columnasTotal = max(1, $columnasTotal);
        $cubiertasHasta = 0;
        for ($c = 0; $c < $columnasTotal; $c++) {
            if ($c < $cubiertasHasta) {
                continue;
            }
            $cell = self::normalizeCultivoTituloCelda($titulosPorCol[$c][$tr] ?? null);
            if ((string) $cell['texto'] === '') {
                $cubiertasHasta = $c + 1;

                continue;
            }
            $colspan = max(1, min((int) $cell['colspan'], $columnasTotal - $c));
            if ($c <= $col && $col < $c + $colspan) {
                return $c;
            }
            $cubiertasHasta = $c + $colspan;
        }

        return null;
    }

    /**
     * @param list<int> $columnasActivas
     */
    public static function countCultivoTituloColumnasActivasEnSpan(
        array $titulosPorCol,
        int $columnasTotal,
        int $colInicio,
        int $tr,
        array $columnasActivas
    ): int {
        $activeSet = array_flip($columnasActivas);
        $cell = self::normalizeCultivoTituloCelda($titulosPorCol[$colInicio][$tr] ?? null);
        if ((string) $cell['texto'] === '') {
            return 0;
        }
        $colspan = max(1, min((int) $cell['colspan'], $columnasTotal - $colInicio));
        $count = 0;
        for ($oc = $colInicio; $oc < $colInicio + $colspan; $oc++) {
            if (isset($activeSet[$oc])) {
                $count++;
            }
        }

        return $count;
    }

    /**
     * @param list<list<array{texto: string, colspan: int}>> $titulosPorCol
     * @param list<list<string>> $filas
     * @param list<int> $columnasActivas
     * @return array{
     *   titulos_banda: list<list<array{texto: string, colspan: int}>>,
     *   columnas_detalle: list<array{titulos_filas: list<list<array{texto: string, colspan: int}>>, valores: list<string>}>
     * }
     */
    public static function buildCultivoColumnasDetalleParaReporte(
        array $titulosPorCol,
        array $filas,
        array $columnasActivas,
        int $columnasTotal
    ): array {
        if ($columnasActivas === []) {
            return ['titulos_banda' => [], 'columnas_detalle' => []];
        }

        $maxTituloFilas = self::maxCultivoTituloFilasPorColumna($titulosPorCol, $columnasTotal);
        $titulosBanda = [];
        $trEnBanda = [];

        for ($tr = 0; $tr < $maxTituloFilas; $tr++) {
            $cubiertasHasta = 0;
            for ($c = 0; $c < $columnasTotal; $c++) {
                if ($c < $cubiertasHasta) {
                    continue;
                }
                $cell = self::normalizeCultivoTituloCelda($titulosPorCol[$c][$tr] ?? null);
                if ((string) $cell['texto'] === '') {
                    $cubiertasHasta = $c + 1;

                    continue;
                }
                $activeInSpan = self::countCultivoTituloColumnasActivasEnSpan(
                    $titulosPorCol,
                    $columnasTotal,
                    $c,
                    $tr,
                    $columnasActivas
                );
                if ($activeInSpan > 1) {
                    $titulosBanda[] = [[
                        'texto'   => (string) $cell['texto'],
                        'colspan' => $activeInSpan,
                    ]];
                    $trEnBanda[$tr] = true;
                }
                $colspan = max(1, min((int) $cell['colspan'], $columnasTotal - $c));
                $cubiertasHasta = $c + $colspan;
            }
        }

        $columnasDetalle = [];
        foreach ($columnasActivas as $oldC) {
            $valores = [];
            foreach ($filas as $row) {
                if (! is_array($row)) {
                    continue;
                }
                $val = $row[$oldC] ?? '';
                if (self::cultivoCeldaTieneValor($val)) {
                    $valores[] = (string) $val;
                }
            }

            $titulosFilasCol = [];
            for ($tr = 0; $tr < $maxTituloFilas; $tr++) {
                if (! empty($trEnBanda[$tr])) {
                    continue;
                }
                if (self::cultivoTituloCeldaEstaCubierta($titulosPorCol, $columnasTotal, $oldC, $tr)) {
                    $spanCell = self::findCultivoTituloSpanSobreColumna($titulosPorCol, $columnasTotal, $oldC, $tr);
                    $colInicio = self::findCultivoTituloSpanColumnaInicio($titulosPorCol, $columnasTotal, $oldC, $tr);
                    if ($spanCell !== null && $colInicio !== null) {
                        $activeInSpan = self::countCultivoTituloColumnasActivasEnSpan(
                            $titulosPorCol,
                            $columnasTotal,
                            $colInicio,
                            $tr,
                            $columnasActivas
                        );
                        if ($activeInSpan === 1 && (string) $spanCell['texto'] !== '') {
                            $titulosFilasCol[] = [[
                                'texto'   => (string) $spanCell['texto'],
                                'colspan' => 1,
                            ]];
                        }
                    }

                    continue;
                }
                $cell = self::normalizeCultivoTituloCelda($titulosPorCol[$oldC][$tr] ?? null);
                if ((string) $cell['texto'] !== '') {
                    $titulosFilasCol[] = [[
                        'texto'   => (string) $cell['texto'],
                        'colspan' => 1,
                    ]];
                }
            }
            $titulosFilasCol = self::filterEmptyCultivoTituloFilas($titulosFilasCol);

            if ($titulosFilasCol === [] && $valores === []) {
                continue;
            }

            $columnasDetalle[] = [
                'titulos_filas' => $titulosFilasCol,
                'valores'       => $valores,
            ];
        }

        return [
            'titulos_banda'    => $titulosBanda,
            'columnas_detalle' => $columnasDetalle,
        ];
    }

    /**
     * Compacta filas/columnas para reporte: sin filas vacías, sin columnas sin datos ni sus títulos.
     *
     * @param list<list<array{texto: string, colspan: int}>> $titulosPorCol
     * @param list<list<string>> $filas
     * @return array{
     *   columnas: int,
     *   titulos_por_col: list<list<array{texto: string, colspan: int}>>,
     *   titulos_filas: list<list<array{texto: string, colspan: int}>>,
     *   titulos_banda: list<list<array{texto: string, colspan: int}>>,
     *   columnas_detalle: list<array{titulos_filas: list<list<array{texto: string, colspan: int}>>, valores: list<string>}>,
     *   filas: list<list<string>>,
     *   max_titulo_filas: int
     * }|null
     */
    public static function compactCultivoSectionDisplayForReport(array $titulosPorCol, array $filas, int $columnasTotal): ?array
    {
        $columnasTotal = max(1, $columnasTotal);

        $columnasActivas = [];
        for ($c = 0; $c < $columnasTotal; $c++) {
            foreach ($filas as $row) {
                if (! is_array($row)) {
                    continue;
                }
                if (self::cultivoCeldaTieneValor($row[$c] ?? '')) {
                    $columnasActivas[] = $c;
                    break;
                }
            }
        }

        if ($columnasActivas === []) {
            return null;
        }

        $detalle = self::buildCultivoColumnasDetalleParaReporte(
            $titulosPorCol,
            $filas,
            $columnasActivas,
            $columnasTotal
        );

        if ($detalle['columnas_detalle'] === []) {
            return null;
        }

        $titulosPorColActivos = self::remapCultivoTitulosColumnasActivas($titulosPorCol, $columnasActivas, $columnasTotal);
        $columnasNuevas = count($columnasActivas);
        $titulosFilas = self::filterEmptyCultivoTituloFilas(
            self::buildCultivoTituloFilasTabla($titulosPorColActivos, $columnasNuevas)
        );

        return [
            'columnas'         => $columnasNuevas,
            'titulos_por_col'  => $titulosPorColActivos,
            'titulos_filas'    => $titulosFilas,
            'titulos_banda'    => $detalle['titulos_banda'],
            'columnas_detalle' => $detalle['columnas_detalle'],
            'filas'            => [],
            'max_titulo_filas' => count($titulosFilas),
        ];
    }

    /**
     * Normaliza títulos por columna (varios títulos apilados en cada columna).
     * Acepta formato legado: list<string> (un título por columna).
     *
     * @param list<mixed> $oldTitulos
     * @return list<list<array{texto: string, colspan: int}>>
     */
    private function normalizeCultivoTitulosPorColumna(array $oldTitulos, int $columnas): array
    {
        return self::parseCultivoTitulosPorColumna($oldTitulos, $columnas);
    }

    /**
     * @param array<string, mixed>|null $config
     * @return array{version: int, bloques: list<array<string, mixed>>}
     */
    public function normalizeCultivoMatrizConfig(?array $config): array
    {
        if (! is_array($config)) {
            return $this->getDefaultCultivoMatrizConfig();
        }

        $rawBloques = $config['bloques'] ?? null;
        if (! is_array($rawBloques)) {
            $rawBloques = $this->migrateLegacyCultivoMatrizToBloques($config);
        }

        $bloques = [];
        $usedIds = [];
        foreach ($rawBloques as $rawBloque) {
            if (! is_array($rawBloque)) {
                continue;
            }
            $normalized = $this->normalizeCultivoBloque($rawBloque);
            $id = $normalized['id'];
            if (in_array($id, $usedIds, true)) {
                $id = self::generateCultivoBloqueId($normalized['tipo'], $usedIds);
                $normalized['id'] = $id;
            }
            $usedIds[] = $id;
            $bloques[] = $normalized;
        }

        if ($bloques === []) {
            return $this->getDefaultCultivoMatrizConfig();
        }

        return [
            'version' => 2,
            'bloques' => $bloques,
        ];
    }

    /**
     * @return array{version: int, bloques: list<array<string, mixed>>}
     */
    public function getCultivoMatrizConfig(int $prianacategoriaId): array
    {
        if (! $this->ensureCultivoMatrizConfigColumn()) {
            return $this->getDefaultCultivoMatrizConfig();
        }

        $row = $this->db->table('prianacategoria')
            ->select('cultivo_matriz_config')
            ->where('prianacategoria_id', $prianacategoriaId)
            ->where('(deleted = 0 OR deleted IS NULL)')
            ->get()
            ->getRow();

        if (! $row || trim((string) ($row->cultivo_matriz_config ?? '')) === '') {
            return $this->getDefaultCultivoMatrizConfig();
        }

        $decoded = json_decode((string) $row->cultivo_matriz_config, true);

        return $this->normalizeCultivoMatrizConfig(is_array($decoded) ? $decoded : null);
    }

    /**
     * @param array<string, mixed> $config
     */
    public function saveCultivoMatrizConfig(int $prianacategoriaId, array $config): bool
    {
        if ($prianacategoriaId < 1 || ! $this->ensureCultivoMatrizConfigColumn()) {
            return false;
        }

        $normalized = $this->normalizeCultivoMatrizConfig($config);
        $json = json_encode($normalized, JSON_UNESCAPED_UNICODE);
        if ($json === false) {
            return false;
        }

        return $this->db->table('prianacategoria')
            ->where('prianacategoria_id', $prianacategoriaId)
            ->update(['cultivo_matriz_config' => $json]) !== false;
    }

    /**
     * Crea la columna cultivo_matriz_config si la migración aún no corrió en esta BD.
     */
    private function ensureCultivoMatrizConfigColumn(): bool
    {
        if ($this->hasColumn('prianacategoria', 'cultivo_matriz_config')) {
            return true;
        }

        $fullTable = $this->db->prefixTable('prianacategoria');
        if (! $this->db->tableExists($fullTable)) {
            return false;
        }

        try {
            $column = [
                'type' => 'MEDIUMTEXT',
                'null' => true,
            ];
            if ($this->hasColumn('prianacategoria', 'mostrar_valores')) {
                $column['after'] = 'mostrar_valores';
            } elseif ($this->hasColumn('prianacategoria', 'compleja')) {
                $column['after'] = 'compleja';
            }

            \Config\Database::forge($this->db)->addColumn('prianacategoria', [
                'cultivo_matriz_config' => $column,
            ]);

            return $this->hasColumn('prianacategoria', 'cultivo_matriz_config', true);
        } catch (\Throwable $e) {
            log_message('error', 'ensureCultivoMatrizConfigColumn: {err}', ['err' => $e->getMessage()]);

            return false;
        }
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
     * Retira un análisis del catálogo sin borrar su configuración ni datos históricos en órdenes.
     */
    public function retirePrianacategoria(int $prianacategoriaId): bool
    {
        return $this->db->table('prianacategoria')
            ->where('prianacategoria_id', $prianacategoriaId)
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
            ->orderBy("{$ana}.anacategoria_id", 'ASC')
            ->orderBy("{$pri}.order", 'ASC')
            ->orderBy("{$pri}.prianacategoria_id", 'ASC');

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
     * Crea la columna recomendaciones_previas si la migración aún no corrió en esta BD.
     */
    private function ensureRecomendacionesPreviasColumn(): bool
    {
        if ($this->hasColumn('prianacategoria', 'recomendaciones_previas')) {
            return true;
        }

        $fullTable = $this->db->prefixTable('prianacategoria');
        if (! $this->db->tableExists($fullTable)) {
            return false;
        }

        try {
            $column = [
                'type' => 'MEDIUMTEXT',
                'null' => true,
            ];
            if ($this->hasColumn('prianacategoria', 'cultivo_matriz_config')) {
                $column['after'] = 'cultivo_matriz_config';
            } elseif ($this->hasColumn('prianacategoria', 'metodo_id')) {
                $column['after'] = 'metodo_id';
            }

            \Config\Database::forge($this->db)->addColumn('prianacategoria', [
                'recomendaciones_previas' => $column,
            ]);

            return $this->hasColumn('prianacategoria', 'recomendaciones_previas', true);
        } catch (\Throwable $e) {
            log_message('error', 'ensureRecomendacionesPreviasColumn: {err}', ['err' => $e->getMessage()]);

            return false;
        }
    }

    /**
     * Obtiene las recomendaciones previas al examen (toma de muestra) de un análisis.
     */
    public function getRecomendacionesPrevias(int $prianacategoriaId): string
    {
        if ($prianacategoriaId < 1 || ! $this->ensureRecomendacionesPreviasColumn()) {
            return '';
        }

        try {
            $row = $this->db->table('prianacategoria')
                ->select('recomendaciones_previas')
                ->where('prianacategoria_id', $prianacategoriaId)
                ->where('(deleted = 0 OR deleted IS NULL)')
                ->get()
                ->getRowArray();

            return (string) ($row['recomendaciones_previas'] ?? '');
        } catch (\Throwable $e) {
            return '';
        }
    }

    /**
     * Guarda las recomendaciones previas al examen de un análisis.
     */
    public function saveRecomendacionesPrevias(int $prianacategoriaId, string $contenido): bool
    {
        if ($prianacategoriaId < 1 || ! $this->ensureRecomendacionesPreviasColumn()) {
            return false;
        }

        return $this->db->table('prianacategoria')
            ->where('prianacategoria_id', $prianacategoriaId)
            ->update(['recomendaciones_previas' => $contenido]) !== false;
    }

    /**
     * Indica si el HTML de recomendaciones tiene texto visible.
     */
    public static function recomendacionTieneContenido(?string $html): bool
    {
        return trim(strip_tags($html ?? '')) !== '';
    }

    /**
     * Mapa prianacategoria_id => HTML de recomendaciones (solo ítems con contenido).
     *
     * @param list<int> $prianacategoriaIds
     * @return array<int, string>
     */
    public function getRecomendacionesPreviasMap(array $prianacategoriaIds): array
    {
        $ids = array_values(array_unique(array_filter(
            array_map('intval', $prianacategoriaIds),
            static fn (int $id): bool => $id > 0
        )));
        if ($ids === [] || ! $this->ensureRecomendacionesPreviasColumn()) {
            return [];
        }

        try {
            $rows = $this->db->table('prianacategoria')
                ->select('prianacategoria_id, recomendaciones_previas')
                ->whereIn('prianacategoria_id', $ids)
                ->where('(deleted = 0 OR deleted IS NULL)')
                ->get()
                ->getResultArray();
        } catch (\Throwable $e) {
            return [];
        }

        $out = [];
        foreach ($rows as $row) {
            $html = (string) ($row['recomendaciones_previas'] ?? '');
            if (self::recomendacionTieneContenido($html)) {
                $out[(int) $row['prianacategoria_id']] = $html;
            }
        }

        return $out;
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
     * Normaliza HTML permitido para texto fijo (negrita, cursiva, listas, etc.).
     */
    private function sanitizeTextoFijoForSave(?string $raw): ?string
    {
        $texto = trim((string) ($raw ?? ''));
        if ($texto === '') {
            return null;
        }
        helper('registro');

        return registro_sanitizar_html_rico($texto);
    }

    /**
     * Comprueba si una columna existe en una tabla
     */
    private function hasColumn(string $table, string $column, bool $refresh = false): bool
    {
        static $cache = [];
        $key = $table . '.' . $column;
        if (! $refresh && array_key_exists($key, $cache)) {
            return $cache[$key];
        }
        try {
            $fullTable = $this->db->prefixTable($table);
            $cache[$key] = $this->db->fieldExists($column, $fullTable);
        } catch (\Throwable $e) {
            $cache[$key] = false;
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
        model(OpcionModel::class)->ensureSystemOpciones();
        $rows = $this->db->table('opciones')->orderBy('opciones', 'ASC')->get()->getResultArray();
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
            if ($this->hasColumn('secanacategoria', 'mostrar_medida')) {
                $save['mostrar_medida'] = 0;
            }
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
        if ($this->hasColumn('secanacategoria', 'mostrar_medida', true)) {
            $save['mostrar_medida'] = ! empty($data['mostrar_medida']) ? 1 : 0;
        }
        if ($this->hasColumn('secanacategoria', 'texto_fijo')) {
            $opcionId = (int) ($save['opcion_id'] ?? 3);
            $save['texto_fijo'] = \App\Models\OpcionModel::isTextoFijo($opcionId)
                ? $this->sanitizeTextoFijoForSave($data['texto_fijo'] ?? '')
                : null;
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
     * Duplica varias sub-clases, cada una N veces.
     *
     * @return array{inserted:int, items:int}
     */
    public function duplicateSecItemsBulk(int $prianacategoriaId, array $ids, int $copies = 1, string $nameMode = 'copia_numerada'): array
    {
        $ids = array_values(array_unique(array_filter(array_map('intval', $ids), static fn(int $v): bool => $v > 0)));
        $copies = max(1, min(100, $copies));
        $nameMode = in_array($nameMode, ['same', 'copia_numerada'], true) ? $nameMode : 'copia_numerada';

        if ($prianacategoriaId < 1 || $ids === []) {
            return ['inserted' => 0, 'items' => 0];
        }

        $totalInserted = 0;
        $itemsProcessed = 0;
        foreach ($ids as $id) {
            $sec = $this->getSecItemInfo($id);
            if (! $sec || (int) ($sec->prianacategoria_id ?? 0) !== $prianacategoriaId) {
                continue;
            }
            $result = $this->duplicateSecItemMany($id, $copies, $nameMode);
            $inserted = (int) ($result['inserted'] ?? 0);
            if ($inserted > 0) {
                $totalInserted += $inserted;
                $itemsProcessed++;
            }
        }

        return ['inserted' => $totalInserted, 'items' => $itemsProcessed];
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
        if ($this->hasColumn('priresultados', 'mostrar_medida', true)) {
            $save['mostrar_medida'] = ! empty($data['mostrar_medida']) ? 1 : 0;
        }
        if ($this->hasColumn('priresultados', 'texto_fijo')) {
            $opcionId = (int) ($save['opcion_id'] ?? 3);
            $save['texto_fijo'] = \App\Models\OpcionModel::isTextoFijo($opcionId)
                ? $this->sanitizeTextoFijoForSave($data['texto_fijo'] ?? '')
                : null;
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
            if (array_key_exists('mostrar_medida', $row) && $this->hasColumn('secanacategoria', 'mostrar_medida')) {
                $update['mostrar_medida'] = ! empty($row['mostrar_medida']) ? 1 : 0;
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
            if (array_key_exists('texto_fijo', $row) && $this->hasColumn('secanacategoria', 'texto_fijo')) {
                $update['texto_fijo'] = $this->sanitizeTextoFijoForSave($row['texto_fijo'] ?? '') ?? '';
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
            if (array_key_exists('mostrar_medida', $row) && $this->hasColumn('priresultados', 'mostrar_medida')) {
                $update['mostrar_medida'] = ! empty($row['mostrar_medida']) ? 1 : 0;
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
            if (array_key_exists('texto_fijo', $row) && $this->hasColumn('priresultados', 'texto_fijo')) {
                $update['texto_fijo'] = $this->sanitizeTextoFijoForSave($row['texto_fijo'] ?? '') ?? '';
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
        $order = (int) ($data['order'] ?? 0);
        $isUpdate = $id && $this->existsSub((int) $id);
        if (! $isUpdate && $order === 0) {
            $maxOrder = $this->db->table('prianacategoria')
                ->where('anacategoria_id', $anacategoriaId)
                ->where('(deleted = 0 OR deleted IS NULL)')
                ->selectMax('order', 'max_order')
                ->get()
                ->getRowArray();
            $order = 1 + (int) ($maxOrder['max_order'] ?? 0);
        }
        $save = [
            'name'           => $data['name'] ?? '',
            'order'          => $order,
            'anacategoria_id'=> $anacategoriaId,
            'compleja'       => (int) ($data['compleja'] ?? 0),
            'deleted'        => 0,
        ];
        if ($this->hasColumn('prianacategoria', 'mostrar_valores')) {
            $save['mostrar_valores'] = (int) ($data['mostrar_valores'] ?? 0);
        }
        if ($this->hasColumn('prianacategoria', 'graficar')) {
            $save['graficar'] = self::normalizeGraficar((int) ($data['graficar'] ?? 0));
        }
        if ($this->hasColumn('prianacategoria', 'tipo_muestra_id')) {
            $tid = (int) ($data['tipo_muestra_id'] ?? 0);
            $save['tipo_muestra_id'] = $tid > 0 ? $tid : null;
        }
        if ($this->hasColumn('prianacategoria', 'metodo_id')) {
            $mid = (int) ($data['metodo_id'] ?? 0);
            $save['metodo_id'] = $mid > 0 ? $mid : null;
        }
        if ($isUpdate) {
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
     * Lista ligera de categorías activas para reordenar en modal.
     *
     * @return list<array{id:int, name:string, items_count:int}>
     */
    public function getCategoriesForReorder(): array
    {
        $rows = $this->db->table('anacategoria')
            ->select('anacategoria_id, name')
            ->where('(deleted = 0 OR deleted IS NULL)')
            ->orderBy('order', 'ASC')
            ->orderBy('anacategoria_id', 'ASC')
            ->get()
            ->getResultArray();

        if ($rows === []) {
            return [];
        }

        $counts = [];
        $countRows = $this->db->table('prianacategoria')
            ->select('anacategoria_id, COUNT(*) as total', false)
            ->where('(deleted = 0 OR deleted IS NULL)')
            ->groupBy('anacategoria_id')
            ->get()
            ->getResultArray();
        foreach ($countRows as $row) {
            $counts[(int) ($row['anacategoria_id'] ?? 0)] = (int) ($row['total'] ?? 0);
        }

        $out = [];
        foreach ($rows as $row) {
            $id = (int) ($row['anacategoria_id'] ?? 0);
            if ($id < 1) {
                continue;
            }
            $out[] = [
                'id'           => $id,
                'name'         => (string) ($row['name'] ?? ''),
                'items_count'  => $counts[$id] ?? 0,
            ];
        }

        return $out;
    }

    /**
     * Reordena todas las categorías activas según la lista completa recibida.
     *
     * @param list<int> $orderedIds
     */
    public function updateAllCategoryOrder(array $orderedIds): bool
    {
        $orderedIds = array_values(array_unique(array_filter(array_map('intval', $orderedIds))));
        if ($orderedIds === []) {
            return false;
        }

        $allRows = $this->db->table('anacategoria')
            ->select('anacategoria_id')
            ->where('(deleted = 0 OR deleted IS NULL)')
            ->orderBy('order', 'ASC')
            ->orderBy('anacategoria_id', 'ASC')
            ->get()
            ->getResultArray();

        $allIds = array_values(array_filter(array_map(
            static fn(array $row): int => (int) ($row['anacategoria_id'] ?? 0),
            $allRows
        ), static fn(int $id): bool => $id > 0));

        if ($allIds === []) {
            return false;
        }

        $expected = $allIds;
        sort($expected);
        $received = $orderedIds;
        sort($received);
        if ($expected !== $received) {
            return false;
        }

        $this->db->transStart();
        foreach ($orderedIds as $position => $id) {
            $this->db->table('anacategoria')
                ->where('anacategoria_id', $id)
                ->update(['order' => $position + 1]);
        }
        $this->db->transComplete();

        return $this->db->transStatus();
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

            foreach ($children as $position => $childId) {
                if (! isset($validChildLookup[$childId])) {
                    continue;
                }

                $this->db->table('prianacategoria')
                    ->where('prianacategoria_id', $childId)
                    ->update([
                        'anacategoria_id' => (int) $parentId,
                        'order' => $position + 1,
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

            foreach (array_merge($desired, $remaining) as $position => $childId) {
                $this->db->table('prianacategoria')
                    ->where('prianacategoria_id', $childId)
                    ->update([
                        'anacategoria_id' => (int) $parentId,
                        'order' => $position + 1,
                    ]);
            }
        }
        $this->db->transComplete();

        return $this->db->transStatus();
    }

    /**
     * Ordena alfabéticamente los análisis de un grupo y asigna orden secuencial 1..n.
     */
    public function sortAnalysesAlphabeticallyInCategory(int $parentId): bool
    {
        if ($parentId < 1) {
            return false;
        }

        $parent = $this->db->table('anacategoria')
            ->select('anacategoria_id')
            ->where('anacategoria_id', $parentId)
            ->where('(deleted = 0 OR deleted IS NULL)')
            ->get()
            ->getRowArray();
        if (! $parent) {
            return false;
        }

        $rows = $this->db->table('prianacategoria')
            ->select('prianacategoria_id, name')
            ->where('anacategoria_id', $parentId)
            ->where('(deleted = 0 OR deleted IS NULL)')
            ->get()
            ->getResultArray();
        if ($rows === []) {
            return true;
        }

        usort($rows, static function (array $a, array $b): int {
            $nameCmp = strcasecmp(
                trim((string) ($a['name'] ?? '')),
                trim((string) ($b['name'] ?? ''))
            );
            if ($nameCmp !== 0) {
                return $nameCmp;
            }

            return ((int) ($a['prianacategoria_id'] ?? 0)) <=> ((int) ($b['prianacategoria_id'] ?? 0));
        });

        $this->db->transStart();
        foreach ($rows as $position => $row) {
            $this->db->table('prianacategoria')
                ->where('prianacategoria_id', (int) ($row['prianacategoria_id'] ?? 0))
                ->update(['order' => $position + 1]);
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

        $complejaVal = (int) ($subInfo->compleja ?? 0);
        $isCompleja = $complejaVal === self::COMPLEJA_COMPOUESTA;
        $payload = [
            'schema_version'    => 1,
            'exported_at'       => date('c'),
            'prianacategoria_id'=> (int) ($subInfo->prianacategoria_id ?? 0),
            'anacategoria_id'   => (int) ($subInfo->anacategoria_id ?? 0),
            'prueba_nombre'     => (string) ($subInfo->name ?? ''),
            'compleja'          => $isCompleja ? 1 : 0,
            'mostrar_valores'   => (int) ($subInfo->mostrar_valores ?? 0),
            'graficar'          => (int) ($subInfo->graficar ?? 0),
        ];

        if ($complejaVal === self::COMPLEJA_CULTIVO) {
            $payload['compleja'] = self::COMPLEJA_CULTIVO;
            $payload['matriz_tipo'] = 'cultivo';
            $payload['cultivo_matriz'] = $this->getCultivoMatrizConfig($prianacategoriaId);
        } elseif ($complejaVal === self::COMPLEJA_PERSONALIZADO) {
            $matriz = $this->getPersonalizadoMatrizConfig($prianacategoriaId);
            $payload['compleja'] = self::COMPLEJA_PERSONALIZADO;
            $payload['matriz_tipo'] = 'personalizado';
            $payload['personalizado_matriz'] = $matriz;
            $payload['cultivo_matriz'] = $matriz;
        } elseif ($isCompleja) {
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
                    'mostrar_medida' => (int) ($r['mostrar_medida'] ?? 0) === 1 ? 1 : 0,
                    'formulas_id'  => (int) ($r['formulas_id'] ?? 1),
                    'opcion_id'    => (int) ($r['opcion_id'] ?? 3),
                    'texto_fijo'   => (string) ($r['texto_fijo'] ?? ''),
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
                    'mostrar_medida' => (int) ($r['mostrar_medida'] ?? 0) === 1 ? 1 : 0,
                    'formulas_id'  => (int) ($r['formulas_id'] ?? 1),
                    'opcion_id'    => (int) ($r['opcion_id'] ?? 3),
                    'texto_fijo'   => (string) ($r['texto_fijo'] ?? ''),
                ];
            }, $rows);
        }

        return $payload;
    }

    /**
     * Extrae configuración de matriz desde un JSON de importación.
     *
     * @return array<string, mixed>|null
     */
    public static function extractMatrizFromDetailImportPayload(array $payload, int $targetTipo): ?array
    {
        $candidates = [];
        if ($targetTipo === self::COMPLEJA_PERSONALIZADO) {
            $candidates = [
                $payload['personalizado_matriz'] ?? null,
                $payload['cultivo_matriz'] ?? null,
            ];
        } elseif ($targetTipo === self::COMPLEJA_CULTIVO) {
            $candidates = [
                $payload['cultivo_matriz'] ?? null,
                $payload['personalizado_matriz'] ?? null,
            ];
        }

        if (isset($payload['bloques']) && is_array($payload['bloques'])) {
            $candidates[] = $payload;
        }

        foreach ($candidates as $raw) {
            if (! is_array($raw)) {
                continue;
            }
            if (isset($raw['bloques']) && is_array($raw['bloques'])) {
                return $raw;
            }
            if (isset($raw['encabezado']) || isset($raw['cuerpo']) || isset($raw['pie'])) {
                return $raw;
            }
        }

        return null;
    }

    /**
     * Valida si el JSON importado puede aplicarse a una prueba matriz (cultivo/personalizado).
     */
    public static function detailImportMatrizSourceEsCompatible(int $targetTipo, int $sourceTipo, ?array $matrizRaw): bool
    {
        if ($matrizRaw === null) {
            return false;
        }

        if ($targetTipo === self::COMPLEJA_PERSONALIZADO) {
            if ($sourceTipo === self::COMPLEJA_COMPOUESTA) {
                return false;
            }

            return in_array($sourceTipo, [0, self::COMPLEJA_CULTIVO, self::COMPLEJA_PERSONALIZADO], true);
        }

        if ($targetTipo === self::COMPLEJA_CULTIVO) {
            if ($sourceTipo === self::COMPLEJA_COMPOUESTA) {
                return false;
            }

            return in_array($sourceTipo, [0, self::COMPLEJA_CULTIVO, self::COMPLEJA_PERSONALIZADO], true);
        }

        return false;
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

        $targetTipo = (int) ($subInfo->compleja ?? 0);
        if ($targetTipo === self::COMPLEJA_CULTIVO) {
            $sourceTipo = (int) ($payload['compleja'] ?? 0);
            $matrizRaw = self::extractMatrizFromDetailImportPayload($payload, $targetTipo);
            if (! self::detailImportMatrizSourceEsCompatible($targetTipo, $sourceTipo, $matrizRaw)) {
                return ['success' => false, 'message' => 'El archivo no corresponde al tipo de análisis de esta prueba'];
            }
            if ($matrizRaw === null) {
                return ['success' => false, 'message' => 'El archivo no contiene matriz de cultivo para importar'];
            }

            $this->db->transStart();
            $ok = $this->saveCultivoMatrizConfig($prianacategoriaId, $matrizRaw);
            $this->db->transComplete();
            if (! $ok || ! $this->db->transStatus()) {
                return ['success' => false, 'message' => 'No se pudo guardar la matriz de cultivo'];
            }

            return [
                'success'  => true,
                'message'  => 'Matriz de cultivo importada correctamente',
                'imported' => 1,
            ];
        }

        if ($targetTipo === self::COMPLEJA_PERSONALIZADO) {
            $sourceTipo = (int) ($payload['compleja'] ?? 0);
            $matrizRaw = self::extractMatrizFromDetailImportPayload($payload, $targetTipo);
            if (! self::detailImportMatrizSourceEsCompatible($targetTipo, $sourceTipo, $matrizRaw)) {
                return ['success' => false, 'message' => 'El archivo no corresponde al tipo de análisis de esta prueba'];
            }
            if ($matrizRaw === null) {
                return ['success' => false, 'message' => 'El archivo no contiene matriz personalizada para importar'];
            }

            $this->db->transStart();
            $ok = $this->savePersonalizadoMatrizConfig($prianacategoriaId, $matrizRaw);
            $this->db->transComplete();
            if (! $ok || ! $this->db->transStatus()) {
                return ['success' => false, 'message' => 'No se pudo guardar la matriz personalizada'];
            }

            return [
                'success'  => true,
                'message'  => 'Matriz personalizada importada correctamente',
                'imported' => 1,
            ];
        }

        $targetCompleja = $targetTipo === self::COMPLEJA_COMPOUESTA;
        $sourceCompleja = (int) ($payload['compleja'] ?? ($targetCompleja ? 1 : 0)) === 1;
        if ($sourceCompleja !== $targetCompleja) {
            return ['success' => false, 'message' => 'El archivo no corresponde al tipo de análisis de esta prueba'];
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
                if ($this->hasColumn('secanacategoria', 'mostrar_medida')) {
                    $insert['mostrar_medida'] = $esSeparador ? 0 : (! empty($raw['mostrar_medida']) ? 1 : 0);
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
                if ($this->hasColumn('secanacategoria', 'texto_fijo')) {
                    $insert['texto_fijo'] = \App\Models\OpcionModel::isTextoFijo((int) ($insert['opcion_id'] ?? 0))
                        ? $this->sanitizeTextoFijoForSave($raw['texto_fijo'] ?? '')
                        : null;
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
                if ($this->hasColumn('priresultados', 'mostrar_medida')) {
                    $insert['mostrar_medida'] = ! empty($raw['mostrar_medida']) ? 1 : 0;
                }
                if ($this->hasColumn('priresultados', 'texto_fijo')) {
                    $insert['texto_fijo'] = \App\Models\OpcionModel::isTextoFijo((int) ($insert['opcion_id'] ?? 0))
                        ? $this->sanitizeTextoFijoForSave($raw['texto_fijo'] ?? '')
                        : null;
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

    /**
     * Aplica una transformación de texto a todos los nombres de grupos y análisis activos.
     *
     * @return array{success: bool, message: string, categories_updated: int, analyses_updated: int, unchanged: int}
     */
    public function transformAllNames(string $mode): array
    {
        $serviceClass = \App\Services\LabotestNameTransformService::class;
        if (! $serviceClass::isAllowedMode($mode)) {
            return [
                'success'             => false,
                'message'             => 'Modo de transformación inválido',
                'categories_updated'  => 0,
                'analyses_updated'    => 0,
                'unchanged'           => 0,
            ];
        }

        $categoriesUpdated = 0;
        $analysesUpdated   = 0;
        $unchanged         = 0;

        $categories = $this->db->table('anacategoria')
            ->select('anacategoria_id, name')
            ->where('(deleted = 0 OR deleted IS NULL)')
            ->get()
            ->getResultArray();

        foreach ($categories as $row) {
            $id = (int) ($row['anacategoria_id'] ?? 0);
            $original = trim((string) ($row['name'] ?? ''));
            if ($id < 1 || $original === '') {
                continue;
            }
            $transformed = $serviceClass::transform($original, $mode);
            if ($transformed === $original) {
                $unchanged++;
                continue;
            }
            $this->db->table('anacategoria')->where('anacategoria_id', $id)->update(['name' => $transformed]);
            $categoriesUpdated++;
        }

        $analyses = $this->db->table('prianacategoria')
            ->select('prianacategoria_id, name')
            ->where('(deleted = 0 OR deleted IS NULL)')
            ->get()
            ->getResultArray();

        foreach ($analyses as $row) {
            $id = (int) ($row['prianacategoria_id'] ?? 0);
            $original = trim((string) ($row['name'] ?? ''));
            if ($id < 1 || $original === '') {
                continue;
            }
            $transformed = $serviceClass::transform($original, $mode);
            if ($transformed === $original) {
                $unchanged++;
                continue;
            }
            $this->db->table('prianacategoria')->where('prianacategoria_id', $id)->update(['name' => $transformed]);
            $analysesUpdated++;
        }

        $totalUpdated = $categoriesUpdated + $analysesUpdated;
        $modeLabels = [
            $serviceClass::MODE_UPPERCASE => 'MAYÚSCULAS',
            $serviceClass::MODE_SENTENCE  => 'oración',
            $serviceClass::MODE_TITLE     => 'título',
            $serviceClass::MODE_SPELL     => 'ortografía',
        ];
        $label = $modeLabels[$mode] ?? $mode;

        return [
            'success'            => true,
            'message'            => $totalUpdated > 0
                ? "Se actualizaron {$categoriesUpdated} grupos y {$analysesUpdated} análisis (formato {$label})."
                : 'No hubo cambios: los nombres ya cumplen el formato seleccionado.',
            'categories_updated' => $categoriesUpdated,
            'analyses_updated'   => $analysesUpdated,
            'unchanged'          => $unchanged,
        ];
    }

    /**
     * Análisis con el mismo nombre (sin distinguir mayúsculas) y perfiles donde aparece cada uno.
     *
     * @param array<int, list<string>> $profileMap
     * @return list<array{name: string, normalized: string, count: int, entries: list<array{id: int, name: string, category_id: int, category_name: string, perfiles: list<string>}>}>
     */
    public function getDuplicateAnalysesWithProfiles(array $profileMap): array
    {
        $rows = $this->db->table('prianacategoria pri')
            ->select('pri.prianacategoria_id, pri.name, pri.anacategoria_id, ana.name AS category_name')
            ->join('anacategoria ana', 'ana.anacategoria_id = pri.anacategoria_id', 'inner')
            ->where('(pri.deleted = 0 OR pri.deleted IS NULL)')
            ->where('(ana.deleted = 0 OR ana.deleted IS NULL)')
            ->orderBy('pri.name', 'ASC')
            ->orderBy('ana.name', 'ASC')
            ->get()
            ->getResultArray();

        $byNormalized = [];
        foreach ($rows as $row) {
            $name = trim((string) ($row['name'] ?? ''));
            if ($name === '') {
                continue;
            }
            $key = mb_strtolower($name, 'UTF-8');
            $id = (int) ($row['prianacategoria_id'] ?? 0);
            if ($id < 1) {
                continue;
            }
            if (! isset($byNormalized[$key])) {
                $byNormalized[$key] = [
                    'display_name' => $name,
                    'entries'      => [],
                ];
            }
            $byNormalized[$key]['entries'][] = [
                'id'            => $id,
                'name'          => $name,
                'category_id'   => (int) ($row['anacategoria_id'] ?? 0),
                'category_name' => trim((string) ($row['category_name'] ?? '')),
                'perfiles'      => $profileMap[$id] ?? [],
            ];
        }

        $duplicates = [];
        foreach ($byNormalized as $key => $group) {
            if (count($group['entries']) < 2) {
                continue;
            }
            $duplicates[] = [
                'name'       => $group['display_name'],
                'normalized' => $key,
                'count'      => count($group['entries']),
                'entries'    => $group['entries'],
            ];
        }

        usort($duplicates, static fn(array $a, array $b): int => strcasecmp($a['name'], $b['name']));

        return $duplicates;
    }
}
