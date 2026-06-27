<?php

namespace App\Services;

use App\Libraries\PdfService;
use App\Models\AppConfigModel;
use App\Models\LabotestModel;
use App\Models\LeyendaCultivoModel;
use App\Models\PoblacionModel;
use App\Models\RegisterModel;
use App\Services\ConfigService;
use App\Services\ReportLayout\LayoutPlanApplier;
use App\Services\ReportLayout\ReportLayoutPlanService;
use App\Services\Report\ReportPipelineMetrics;
use Config\App as AppConfig;

/**
 * Servicio de registros de análisis.
 * Lógica de negocio: grupos de resultados, tipo paciente, preparación PDF.
 */
class RegisterService
{
    public const TOTAL_PAGES_TOKEN = '__PDF_TOTAL_PAGES__';

    /** Invalida caché inline de viewreport al cambiar el pipeline PDF. */
    private const REPORT_PDF_PREVIEW_CACHE_SALT = 'mpdf-native-v7';

    private ?\App\Services\Report\ReportDataCacheService $reportDataCache = null;

    private ?\App\Services\Report\ReportPdfHtmlCacheService $reportPdfHtmlCache = null;
    protected RegisterModel $registerModel;
    protected AppConfigModel $appConfigModel;

    /** @var array<int, mixed> */
    private array $reportBuildComplejaCache = [];

    /** @var array<int, mixed> */
    private array $reportBuildNocomplejaCache = [];

    /** @var array<string, mixed> */
    private array $reportBuildSecItemCache = [];

    /** @var array<int, object|null> */
    private array $reportBuildFormulaCache = [];

    public function __construct(?RegisterModel $registerModel = null, ?AppConfigModel $appConfigModel = null)
    {
        $this->registerModel  = $registerModel ?? model(RegisterModel::class);
        $this->appConfigModel = $appConfigModel ?? model(AppConfigModel::class);
    }

    /**
     * Obtiene los id_poblacion que coinciden con el paciente según configuración (edad) y sexo.
     * Usa edad_min, edad_max, unidad de la tabla poblacion; si no hay rango, usa lógica legacy.
     * @param string|null $birthday Fecha nacimiento (Y-m-d)
     * @param int|null $gender 1=masculino, 2=femenino
     * @param \DateTimeInterface|string|null $referenceDate Fecha de referencia para la edad (p. ej. ingreso del registro). Por defecto hoy.
     * @return int[] ids de poblacion que aplican al paciente
     */
    public function getMatchingPoblacionIds(?string $birthday, ?int $gender, $referenceDate = null): array
    {
        try {
            $poblacionModel = model(PoblacionModel::class);
            $poblaciones    = $poblacionModel->getAll();
        } catch (\Throwable $e) {
            return [15];
        }
        $matching        = [];

        $ref = $referenceDate;
        if ($ref === null) {
            $ref = new \DateTime();
        } elseif (is_string($ref)) {
            try {
                $ref = new \DateTime($ref);
            } catch (\Throwable $e) {
                $ref = new \DateTime();
            }
        }

        $edadEnDias = null;
        $edadEnMeses = null;
        $edadEnAnios = null;
        if ($birthday) {
            try {
                $fechaNac = new \DateTime($birthday);
                $diff     = $ref->diff($fechaNac);
                $edadEnDias  = $diff->days;
                $edadEnMeses = $diff->y * 12 + $diff->m + $diff->d / 30.0;
                $edadEnAnios = $diff->y + $diff->m / 12.0 + $diff->d / 365.0;
            } catch (\Throwable $e) {
                $birthday = null;
            }
        }

        foreach ($poblaciones as $p) {
            $id   = (int) ($p['id_poblacion'] ?? 0);
            $min  = (isset($p['edad_min']) && $p['edad_min'] !== '' && $p['edad_min'] !== null) ? (float) $p['edad_min'] : null;
            $max  = (isset($p['edad_max']) && $p['edad_max'] !== '' && $p['edad_max'] !== null) ? (float) $p['edad_max'] : null;
            $unidad = $p['unidad'] ?? null;
            if ($unidad !== null && $unidad !== '') {
                $unidad = strtolower($unidad);
            } else {
                $unidad = null;
            }

            $tieneRangoEdad = ($min !== null || $max !== null) && $unidad !== null;

            if (!$tieneRangoEdad) {
                if ($id === 3) {
                    $matching[] = $id;
                } elseif ($id === 1 && $gender === 1) {
                    $matching[] = $id;
                } elseif ($id === 2 && $gender === 2) {
                    $matching[] = $id;
                } elseif (in_array($id, [0, 4, 5], true)) {
                    $legacyType = $this->computePacienteType((object)['birthday' => $birthday, 'gender' => $gender ?? 0], $ref);
                    if ($legacyType === $id) {
                        $matching[] = $id;
                    }
                } else {
                    $matching[] = $id;
                }
                continue;
            }

            if (!$birthday) {
                continue;
            }

            $edadPaciente = null;
            if ($unidad === 'dias') {
                $edadPaciente = $edadEnDias;
            } elseif ($unidad === 'meses') {
                $edadPaciente = $edadEnMeses;
            } else {
                $edadPaciente = $edadEnAnios;
            }

            if ($edadPaciente === null) {
                continue;
            }

            $cumpleMin = ($min === null) || ($edadPaciente >= $min);
            $cumpleMax = ($max === null) || ($edadPaciente <= $max);
            if ($cumpleMin && $cumpleMax) {
                $matching[] = $id;
            }
        }

        $matching = array_unique($matching);
        if (empty($matching)) {
            return [15];
        }
        $result = array_values($matching);
        if (! in_array(15, $result, true)) {
            $result[] = 15;
        }
        return $result;
    }

    /**
     * Parsea "pruebas" del registro (ids separados por coma o contador_X).
     * @return int[]
     */
    public function extractPrianacategoriaIdsFromRegistroPruebas(string $pruebas): array
    {
        $parts = explode(',', $pruebas);
        $ids = [];
        foreach ($parts as $p) {
            $p = trim($p);
            if ($p === '') continue;
            $id = preg_match('/contador_(\d+)/', $p, $m) ? (int) $m[1] : (int) $p;
            if ($id > 0) $ids[] = $id;
        }
        return array_values(array_unique($ids));
    }

    /**
     * Determina el tipo de paciente según edad y género (para rangos de referencia).
     * 0=Niños, 1=Masculino, 2=Femenino, 3=Todos, 4=Recién nacido, 5=Lactante
     * @param \DateTimeInterface|string|null $referenceDate Fecha de referencia para la edad (p. ej. ingreso del registro). Por defecto hoy.
     */
    public function computePacienteType(object $registerInfo, $referenceDate = null): int
    {
        $birthday = $registerInfo->birthday ?? null;
        $gender   = (int) ($registerInfo->gender ?? 0);
        if (!$birthday) {
            return 3;
        }

        $fechaNac = new \DateTime($birthday);
        $ref      = $referenceDate;
        if ($ref === null) {
            $ref = new \DateTime();
        } elseif (is_string($ref)) {
            try {
                $ref = new \DateTime($ref);
            } catch (\Throwable $e) {
                $ref = new \DateTime();
            }
        }
        $edad = $ref->diff($fechaNac);
        $edadTotalDias = (int) ($edad->days ?? 0);

        if ($edad->y < 13) {
            // Recién nacido: 0-28 días (evita clasificar por "mes 1" casos > 28 días).
            if ($edadTotalDias <= 28) {
                return 4;
            }
            if ($edad->y <= 1) {
                return 5;
            }
            return 0;
        }
        if ($gender === 2) {
            return 2;
        }
        if ($gender === 1) {
            return 1;
        }
        return 3;
    }

    /**
     * Elimina filas duplicadas de regvalues que apuntan al mismo parámetro.
     * Prioriza claves c_* / noc_* sobre el formato legacy prianacategoria_id|nombre.
     *
     * @param list<array<string,mixed>> $analisis
     * @return list<array<string,mixed>>
     */
    protected function deduplicateAnalisisForReport(array $analisis, array $matchingPoblacionIds = [], ?int $gender = null): array
    {
        $indexed = [];

        foreach ($analisis as $prueba) {
            if (! is_array($prueba)) {
                continue;
            }
            $name = trim((string) ($prueba['name'] ?? ''));
            if ($name === '') {
                continue;
            }

            $val = trim((string) ($prueba['regvalues'] ?? $prueba['value'] ?? ''));
            $priority = 0;
            $canonicalKey = 'raw:' . $name;

            if (preg_match('/^(c|noc)_(\d+)$/', $name, $m) === 1) {
                $canonicalKey = $m[1] . ':' . $m[2];
                $priority = 3;
            } elseif (strpos($name, '|') !== false) {
                [$priaStr, $nombreParam] = explode('|', $name, 2);
                $priaId = (int) trim($priaStr);
                $nombreParam = trim($nombreParam);
                if ($priaId > 0 && $nombreParam !== '') {
                    $item = $this->registerModel->getSecItemByPrianacategoriaYNombre(
                        $priaId,
                        $nombreParam,
                        $matchingPoblacionIds,
                        $gender
                    );
                    $secId = $item ? (int) ($item->secanacategoria_id ?? 0) : 0;
                    if ($secId > 0) {
                        $canonicalKey = 'c:' . $secId;
                    } else {
                        $canonicalKey = 'pipe:' . $priaId . '|' . mb_strtolower($nombreParam);
                    }
                }
                $priority = 1;
            } elseif (str_starts_with($name, 'cv_') || str_starts_with($name, 'cvn_') || str_starts_with($name, 'cvu_')) {
                $priority = 2;
            } elseif (preg_match('/^lab_(val|app)_pri_\d+$/', $name) === 1) {
                $priority = 2;
            }

            $existing = $indexed[$canonicalKey] ?? null;
            if ($existing === null || $priority > (int) ($existing['priority'] ?? 0)) {
                $indexed[$canonicalKey] = [
                    'row'      => $prueba,
                    'priority' => $priority,
                    'name'     => $name,
                    'value'    => $val,
                ];
                continue;
            }

            if ($priority === (int) ($existing['priority'] ?? 0) && $val !== '' && $val !== ($existing['value'] ?? '')) {
                if (preg_match('/^(c|noc)_/', $name) === 1) {
                    $indexed[$canonicalKey] = [
                        'row'      => $prueba,
                        'priority' => $priority,
                        'name'     => $name,
                        'value'    => $val,
                    ];
                }
            }
        }

        $out = [];
        foreach ($indexed as $entry) {
            $row = $entry['row'];
            if (is_array($row)) {
                $out[] = $row;
            }
        }

        return $out;
    }

    /**
     * @param array<string, list<object>> $grupos
     * @return array<string, list<object>>
     */
    protected function deduplicateGrupoItemsByParametro(array $grupos): array
    {
        foreach ($grupos as $padre => $items) {
            $seen = [];
            $deduped = [];
            foreach ($items as $it) {
                $it = is_array($it) ? (object) $it : $it;
                $secId = (int) ($it->secanacategoria_id ?? 0);
                $nocId = (int) ($it->priresultados_id ?? 0);
                $isSep = (int) ($it->es_separador ?? 0) === 1;
                if ($isSep) {
                    $key = 'sep:' . $secId;
                } elseif ($secId > 0) {
                    $key = 'sec:' . $secId;
                } elseif ($nocId > 0) {
                    $key = 'noc:' . $nocId;
                } else {
                    $key = 'raw:' . md5(json_encode([
                        (string) ($it->nombre ?? ''),
                        (string) ($it->hijo ?? ''),
                        (string) ($it->regvalues ?? ''),
                    ], JSON_UNESCAPED_UNICODE));
                }

                if (isset($seen[$key])) {
                    continue;
                }
                $seen[$key] = true;
                $deduped[] = $it;
            }
            $grupos[$padre] = $deduped;
        }

        return $grupos;
    }

    /**
     * Construye los grupos de análisis para la vista de reporte/PDF
     */
    public function buildGruposParaReporte(int $registroId, array $analisis, array $matchingPoblacionIds = [], ?int $gender = null): array
    {
        $analisis = $this->deduplicateAnalisisForReport($analisis, $matchingPoblacionIds, $gender);
        $grupos = [];
        $cultivoExtracted = $this->extractCultivoCellValuesFromAnalisis($analisis);
        $cultivoValoresPorPria = $cultivoExtracted['cv'];
        $cultivoNumerosPorPria = $cultivoExtracted['cvn'];
        $cultivoUnidadesGlobalPorPria = $cultivoExtracted['cvu_global'];

        foreach ($analisis as $prueba) {
            $name = $prueba['name'] ?? '';
            if (!is_string($name)) {
                continue;
            }
            if (preg_match('/^lab_(val|app)_pri_\d+$/', $name) === 1) {
                continue;
            }
            if (str_starts_with($name, 'cv_') || str_starts_with($name, 'cvn_') || str_starts_with($name, 'cvu_')) {
                continue;
            }
            $regvalue = $prueba['regvalues'] ?? $prueba['value'] ?? '-';

            if (strpos($name, '|') !== false) {
                [$prianacategoriaIdStr, $nombre] = explode('|', $name, 2);
                $prianacategoriaId = (int) trim($prianacategoriaIdStr);
                $nombre = trim($nombre);
                if ($prianacategoriaId <= 0 || $nombre === '') {
                    continue;
                }
                $item = $this->getSecItemByPrianacategoriaYNombreCached($prianacategoriaId, $nombre, $matchingPoblacionIds, $gender);
                if (!$item) {
                    continue;
                }
                $item = is_array($item) ? (object) $item : $item;
                $padre = trim((string) ($item->padre ?? ''));
                if ($padre === '') {
                    continue;
                }
                if (!isset($grupos[$padre])) {
                    $grupos[$padre] = [];
                }
                $formId = (int) ($item->formulas_id ?? 0);
                $item->regvalues = $this->resolveRegvalue($formId, $regvalue, $registroId);
                $this->applyTextoFijoRegvalueForReport($item);
                $grupos[$padre][] = $item;
                continue;
            }

            if (strpos($name, '_') === false) {
                continue;
            }
            [$tipoAnalisis, $analisisIdStr] = explode('_', $name, 2);
            $analisisId = (int) $analisisIdStr;

            $valores = $tipoAnalisis === 'c'
                ? $this->getAnalisisComplejaCached($analisisId)
                : $this->getAnalisisNocomplejaCached($analisisId);

            if (!$valores) {
                continue;
            }
            $valores = is_array($valores) ? $valores : [$valores];

            foreach ($valores as $item) {
                $item  = is_array($item) ? (object) $item : $item;
                $padre = trim((string) ($item->padre ?? ''));
                if ($padre === '') {
                    continue;
                }
                if (!isset($grupos[$padre])) {
                    $grupos[$padre] = [];
                }
                $formId = (int) ($item->formulas_id ?? 0);
                $item->regvalues = $this->resolveRegvalue($formId, $regvalue, $registroId);
                $this->applyTextoFijoRegvalueForReport($item);
                $grupos[$padre][] = $item;
            }
        }

        foreach ($cultivoValoresPorPria as $priaId => $cellValues) {
            $cultivoItem = $this->buildCultivoMatrizReportItem(
                (int) $priaId,
                $cellValues,
                $cultivoNumerosPorPria[$priaId] ?? [],
                $cultivoUnidadesGlobalPorPria[$priaId] ?? null
            );
            if ($cultivoItem === null) {
                continue;
            }
            $padre = trim((string) ($cultivoItem->padre ?? ''));
            if ($padre === '') {
                continue;
            }
            if (! isset($grupos[$padre])) {
                $grupos[$padre] = [];
            }
            $grupos[$padre][] = $cultivoItem;
        }

        $grupos = $this->purgeLegacyRowsForCultivoGrupos($grupos);

        foreach ($grupos as $padre => $items) {
            usort($items, static function ($a, $b) {
                $aOrd = (int) ($a->orden ?? 0);
                $bOrd = (int) ($b->orden ?? 0);
                if ($aOrd !== $bOrd) {
                    return $aOrd <=> $bOrd;
                }
                return ((int) ($a->secanacategoria_id ?? 0)) <=> ((int) ($b->secanacategoria_id ?? 0));
            });
            $grupos[$padre] = $items;
        }

        return $this->deduplicateGrupoItemsByParametro($grupos);
    }

    /**
     * @param array<int, int> $matchingPoblacionIds
     */
    private function getSecItemByPrianacategoriaYNombreCached(
        int $prianacategoriaId,
        string $nombre,
        array $matchingPoblacionIds = [],
        ?int $gender = null,
    ): mixed {
        $cacheKey = $prianacategoriaId . "\0" . $nombre . "\0" . implode(',', $matchingPoblacionIds) . "\0" . (string) ($gender ?? '');
        if (! array_key_exists($cacheKey, $this->reportBuildSecItemCache)) {
            $this->reportBuildSecItemCache[$cacheKey] = $this->registerModel->getSecItemByPrianacategoriaYNombre(
                $prianacategoriaId,
                $nombre,
                $matchingPoblacionIds,
                $gender,
            );
        }

        return $this->reportBuildSecItemCache[$cacheKey];
    }

    private function getAnalisisComplejaCached(int $analisisId): mixed
    {
        if (! array_key_exists($analisisId, $this->reportBuildComplejaCache)) {
            $this->reportBuildComplejaCache[$analisisId] = $this->registerModel->getAnalisisCompleja($analisisId);
        }

        return $this->reportBuildComplejaCache[$analisisId];
    }

    private function getAnalisisNocomplejaCached(int $analisisId): mixed
    {
        if (! array_key_exists($analisisId, $this->reportBuildNocomplejaCache)) {
            $this->reportBuildNocomplejaCache[$analisisId] = $this->registerModel->getAnalisisNocompleja($analisisId);
        }

        return $this->reportBuildNocomplejaCache[$analisisId];
    }

    /**
     * @param list<array<string, mixed>> $analisis
     * @return array{
     *   cv: array<int, array<string, array<int, array<int, string>>>>,
     *   cvn: array<int, array<string, array<int, array<int, string>>>>,
     *   cvu: array<int, array<string, array<int, array<int, string>>>>,
     *   cvu_global: array<int, string>
     * }
     */
    protected function extractCultivoCellValuesFromAnalisis(array $analisis): array
    {
        $out = [];
        $outNum = [];
        $outUni = [];
        $outUniGlobal = [];
        foreach ($analisis as $row) {
            $name = trim((string) ($row['name'] ?? ''));
            if ($name === '') {
                continue;
            }
            if (preg_match('/^cvu_(\d+)$/', $name, $mGlobal)) {
                $priaGlobal = (int) $mGlobal[1];
                if ($priaGlobal > 0) {
                    $valGlobal = trim((string) ($row['regvalues'] ?? $row['value'] ?? ''));
                    if ($valGlobal !== '') {
                        $outUniGlobal[$priaGlobal] = $valGlobal;
                    }
                }
                continue;
            }
            $prefix = null;
            if (preg_match('/^cv_(\d+)_([a-z][a-z0-9_]*)_(\d+)_(\d+)$/', $name, $m)) {
                $prefix = 'cv';
            } elseif (preg_match('/^cvn_(\d+)_([a-z][a-z0-9_]*)_(\d+)_(\d+)$/', $name, $m)) {
                $prefix = 'cvn';
            } elseif (preg_match('/^cvu_(\d+)_([a-z][a-z0-9_]*)_(\d+)_(\d+)$/', $name, $m)) {
                $prefix = 'cvu';
            } else {
                continue;
            }
            $priaId = (int) $m[1];
            $sec = (string) $m[2];
            $fila = (int) $m[3];
            $col = (int) $m[4];
            if ($priaId < 1) {
                continue;
            }
            $val = trim((string) ($row['regvalues'] ?? $row['value'] ?? ''));
            if ($val === '') {
                continue;
            }
            if ($prefix === 'cvn') {
                $outNum[$priaId][$sec][$fila][$col] = $val;
            } elseif ($prefix === 'cvu') {
                $outUni[$priaId][$sec][$fila][$col] = $val;
            } else {
                $out[$priaId][$sec][$fila][$col] = $val;
            }
        }

        return ['cv' => $out, 'cvn' => $outNum, 'cvu' => $outUni, 'cvu_global' => $outUniGlobal];
    }

    /**
     * @param array<string, array<int, array<int, string>>> $cellValues
     * @param array<string, array<int, array<int, string>>> $cellNumeros
     * @param string|null $unidadGlobalRegistro
     */
    protected function buildCultivoMatrizReportItem(
        int $prianacategoriaId,
        array $cellValues,
        array $cellNumeros = [],
        ?string $unidadGlobalRegistro = null,
        bool $allowEmptyCells = false,
    ): ?object {
        if ($prianacategoriaId < 1) {
            return null;
        }
        if ($cellValues === [] && ! $allowEmptyCells) {
            return null;
        }

        $meta = $this->registerModel->getPrianacategoriaWithArea($prianacategoriaId)
            ?? $this->registerModel->getPrianacategoriaWithAreaIncludingRetired($prianacategoriaId);
        if ($meta === null || ! LabotestModel::esMatrizConfigurable((int) ($meta->compleja ?? 0))) {
            return null;
        }

        $labotestModel = model(LabotestModel::class);
        $esPersonalizado = (int) ($meta->compleja ?? 0) === LabotestModel::COMPLEJA_PERSONALIZADO;
        $matriz = $esPersonalizado
            ? $labotestModel->getPersonalizadoMatrizConfig($prianacategoriaId)
            : $labotestModel->getCultivoMatrizConfig($prianacategoriaId);
        $display = $this->formatCultivoMatrizForReport($matriz, $cellValues, $cellNumeros, $unidadGlobalRegistro, $esPersonalizado);

        return (object) [
            'es_cultivo_matriz'      => true,
            'es_personalizado_matriz'=> $esPersonalizado,
            'prianacategoria_id'     => $prianacategoriaId,
            'padre'                => trim((string) ($meta->padre ?? '')),
            'hijo'                 => trim((string) ($meta->hijo ?? '')),
            'tipo_muestra_nombre'  => trim((string) ($meta->tipo_muestra_nombre ?? '')),
            'metodo_nombre'        => trim((string) ($meta->metodo_nombre ?? '')),
            'cultivo_matriz'       => $matriz,
            'cultivo_valores'      => $cellValues,
            'cultivo_valores_num'  => $cellNumeros,
            'cultivo_unidad'       => $unidadGlobalRegistro,
            'cultivo_display'      => $display,
            'regvalues'            => '·',
        ];
    }

    /**
     * Elimina filas legadas (p. ej. cv_* mal interpretadas) cuando ya hay matriz cultivo.
     *
     * @param array<string, list<object>> $grupos
     * @return array<string, list<object>>
     */
    protected function purgeLegacyRowsForCultivoGrupos(array $grupos): array
    {
        foreach ($grupos as $padre => $items) {
            $cultivoPriaIds = [];
            foreach ($items as $it) {
                $obj = is_array($it) ? (object) $it : $it;
                if (! empty($obj->es_cultivo_matriz)) {
                    $pid = (int) ($obj->prianacategoria_id ?? 0);
                    if ($pid > 0) {
                        $cultivoPriaIds[$pid] = true;
                    }
                }
            }
            if ($cultivoPriaIds === []) {
                continue;
            }
            $grupos[$padre] = array_values(array_filter($items, static function ($it) use ($cultivoPriaIds) {
                $obj = is_array($it) ? (object) $it : $it;
                if (! empty($obj->es_cultivo_matriz)) {
                    return true;
                }
                $pid = (int) ($obj->prianacategoria_id ?? 0);

                return $pid < 1 || ! isset($cultivoPriaIds[$pid]);
            }));
        }

        return $grupos;
    }

    /**
     * @param array<string, array{filas: int, columnas: int, titulos: list<list<string>>, celdas: list<list<array<string, mixed>>>}> $matriz
     * @param array<string, array<int, array<int, string>>> $cellValues
     * @return list<array{seccion: string, label: string, columnas: int, titulos_por_col: list<list<string>>, filas: list<list<string>>}>
     */
    public function formatCultivoMatrizForReport(
        array $matriz,
        array $cellValues,
        array $cellNumeros = [],
        ?string $unidadGlobalRegistro = null,
        bool $esPersonalizado = false
    ): array {
        helper('registro');
        $bloques = $esPersonalizado
            ? LabotestModel::resolvePersonalizadoMatrizBloques($matriz)
            : LabotestModel::resolveCultivoMatrizBloques($matriz);
        $bloquesById = [];
        foreach ($bloques as $bloqueRow) {
            $bid = (string) ($bloqueRow['id'] ?? '');
            if ($bid !== '') {
                $bloquesById[$bid] = $bloqueRow;
            }
        }
        $leyendaModel = model(LeyendaCultivoModel::class);
        $leyendasCache = [];
        $resolveLeyenda = static function (int $leyendaId) use ($leyendaModel, &$leyendasCache): string {
            if ($leyendaId < 1) {
                return '';
            }
            if (! isset($leyendasCache[$leyendaId])) {
                $row = $leyendaModel->getById($leyendaId);
                $leyendasCache[$leyendaId] = is_array($row) ? (string) ($row['mensaje'] ?? '') : '';
            }

            return $leyendasCache[$leyendaId];
        };

        $normalizeCeldaCfg = static function ($raw) use ($esPersonalizado): array {
            $out = ['modo' => 'texto'];
            if (is_array($raw)) {
                $modo = (string) ($raw['modo'] ?? 'texto');
                if ($modo === 'opcion') {
                    $out = [
                        'modo'      => 'opcion',
                        'opcion_id' => max(0, (int) ($raw['opcion_id'] ?? 0)),
                    ];
                } elseif ($modo === 'texto_rico') {
                    $out = ['modo' => 'texto_rico'];
                } elseif ($modo === 'leyenda') {
                    $out = ['modo' => 'leyenda'];
                }
                $rol = trim((string) ($raw['rol'] ?? 'input'));
                if (in_array($rol, ['input', 'titulo', 'etiqueta'], true)) {
                    $out['rol'] = $rol;
                }
                $textoFijo = trim((string) ($raw['texto_fijo'] ?? ''));
                if ($textoFijo !== '') {
                    $out['texto_fijo'] = $textoFijo;
                }
                if ($esPersonalizado) {
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

                    $out['colspan'] = max(1, min(20, (int) ($raw['colspan'] ?? 1)));
                    $out['rowspan'] = max(1, min(50, (int) ($raw['rowspan'] ?? 1)));

                    $textoFijoCfg = trim((string) ($raw['texto_fijo'] ?? ''));
                    if ($textoFijoCfg !== '') {
                        $out['texto_fijo'] = $textoFijoCfg;
                    }
                }
            }

            return $out;
        };

        $cuerpoCfg = is_array($matriz['cuerpo'] ?? null) ? $matriz['cuerpo'] : [];
        $unidadConfigLegacy = trim((string) ($cuerpoCfg['unidad'] ?? ''));
        $alineacionCuerpoLegacy = trim((string) ($cuerpoCfg['alineacion_filas'] ?? 'centro'));
        if ($alineacionCuerpoLegacy === 'cuerpo') {
            $alineacionCuerpoLegacy = 'centro';
        }
        if (! in_array($alineacionCuerpoLegacy, ['centro', 'bordes'], true)) {
            $alineacionCuerpoLegacy = 'centro';
        }

        $esHtmlContenido = static function (string $texto): bool {
            $texto = trim($texto);
            if ($texto === '') {
                return false;
            }

            return $texto !== strip_tags($texto);
        };

        $escHtml = static function (string $texto): string {
            return htmlspecialchars($texto, ENT_QUOTES, 'UTF-8');
        };

        $resolvePrincipal = static function (array $celdaCfg, string $raw) use ($resolveLeyenda): string {
            $raw = trim($raw);
            if ($raw === '') {
                return '';
            }
            if (($celdaCfg['modo'] ?? '') === 'leyenda' && ctype_digit($raw)) {
                $html = $resolveLeyenda((int) $raw);

                return $html !== '' ? $html : $raw;
            }
            if (($celdaCfg['modo'] ?? '') === 'texto_rico') {
                return registro_sanitizar_html_rico($raw);
            }
            if (($celdaCfg['modo'] ?? '') === 'opcion'
                && registro_opcion_es_texto_rico((int) ($celdaCfg['opcion_id'] ?? 0))) {
                return registro_sanitizar_html_rico($raw);
            }
            if (($celdaCfg['fuente'] ?? '') === 'enriquecido' || $raw !== strip_tags($raw)) {
                return registro_sanitizar_html_rico($raw);
            }

            return $raw;
        };

        $resolveMedida = static function (string $blockId, int $r, int $c) use (
            $bloquesById,
            $cellNumeros,
            $unidadGlobalRegistro,
            $unidadConfigLegacy
        ): string {
            $bloqueCfg = $bloquesById[$blockId] ?? [];
            $bloqueTipo = (string) ($bloqueCfg['tipo'] ?? '');
            $valoresOn = ($bloqueTipo === 'cuerpo') && ! empty($bloqueCfg['valores_habilitado']);
            $unidadesOn = ($bloqueTipo === 'cuerpo') && ! empty($bloqueCfg['unidades_habilitado']);
            if (! $valoresOn) {
                return '';
            }
            $num = trim((string) ($cellNumeros[$blockId][$r][$c] ?? ''));
            if ($num === '') {
                return '';
            }
            $unidadMedida = trim((string) ($unidadGlobalRegistro ?? ''));
            if ($unidadMedida === '') {
                $unidadMedida = trim((string) ($bloqueCfg['unidad'] ?? ''));
            }
            if ($unidadMedida === '') {
                $unidadMedida = $unidadConfigLegacy;
            }
            if ($unidadesOn && $unidadMedida !== '') {
                return $num . ' ' . $unidadMedida;
            }

            return $num;
        };

        $buildCeldaReporte = static function (
            string $principal,
            string $medida,
            string $bloqueTipo,
            string $alineacion
        ) use ($esHtmlContenido, $escHtml): string {
            $principal = trim($principal);
            $medida = trim($medida);
            if ($principal === '' && $medida === '') {
                return '';
            }

            if ($bloqueTipo === 'cuerpo' && $alineacion === 'bordes') {
                $html = '<div class="cultivo-celda-bordes">';
                if ($principal !== '') {
                    $html .= '<span class="cultivo-celda-izq">'
                        . ($esHtmlContenido($principal) ? $principal : $escHtml($principal))
                        . '</span>';
                }
                if ($medida !== '') {
                    $html .= '<span class="cultivo-celda-der">'
                        . $escHtml($medida) . '</span>';
                }
                $html .= '</div>';

                return $html;
            }

            if ($principal === '') {
                return $medida;
            }
            if ($medida === '') {
                return $principal;
            }

            return $principal . ' ' . $medida;
        };

        $wrapPersonalizadoCelda = static function (string $html, array $cfg) use ($esPersonalizado): string {
            if (! $esPersonalizado || trim($html) === '') {
                return $html;
            }
            $style = LabotestModel::buildPersonalizadoCeldaReporteStyle($cfg);

            return '<div class="pers-celda-reporte" style="' . htmlspecialchars($style, ENT_QUOTES, 'UTF-8') . '">'
                . $html . '</div>';
        };

        $out = [];
        foreach ($bloques as $bloque) {
            $blockId = (string) ($bloque['id'] ?? '');
            $bloqueTipo = (string) ($bloque['tipo'] ?? 'encabezado');
            if ($blockId === '') {
                continue;
            }
            $secLabel = LabotestModel::cultivoBloqueDisplayLabel($bloque, $bloques);
            $filas = max(0, (int) ($bloque['filas'] ?? 0));
            $columnas = max(1, (int) ($bloque['columnas'] ?? 1));
            $titulosRaw = is_array($bloque['titulos'] ?? null) ? $bloque['titulos'] : [];
            $titulosPorCol = LabotestModel::parseCultivoTitulosPorColumna($titulosRaw, $columnas);
            $celdasCfg = is_array($bloque['celdas'] ?? null) ? $bloque['celdas'] : [];
            $alineacionSec = 'centro';
            if ($bloqueTipo === 'cuerpo') {
                $alineacionSec = trim((string) ($bloque['alineacion_filas'] ?? $alineacionCuerpoLegacy));
                if ($alineacionSec === 'cuerpo') {
                    $alineacionSec = 'centro';
                }
                if (! in_array($alineacionSec, ['centro', 'bordes'], true)) {
                    $alineacionSec = 'centro';
                }
            }
            if ($esPersonalizado) {
                $titulosFilasGrilla = LabotestModel::buildCultivoTituloFilasTabla($titulosPorCol, $columnas);
                $grillaFilas = [];
                $coveredRowspan = [];
                $skipCols = [];

                for ($r = 0; $r < $filas; $r++) {
                    $filaVisibleGrilla = false;
                    for ($cVis = 0; $cVis < $columnas; $cVis++) {
                        if (! isset($coveredRowspan[$r . ',' . $cVis])) {
                            $filaVisibleGrilla = true;
                            break;
                        }
                    }
                    if (! $filaVisibleGrilla) {
                        continue;
                    }

                    $rowCells = [];
                    for ($c = 0; $c < $columnas; $c++) {
                        if (isset($coveredRowspan[$r . ',' . $c]) || isset($skipCols[$r . ',' . $c])) {
                            continue;
                        }

                        $cfg = $normalizeCeldaCfg($celdasCfg[$r][$c] ?? ['modo' => 'texto']);
                        $colspan = max(1, min((int) ($cfg['colspan'] ?? 1), $columnas - $c));
                        $rowspan = max(1, min((int) ($cfg['rowspan'] ?? 1), $filas - $r));

                        for ($rr = $r + 1; $rr < $r + $rowspan && $rr < $filas; $rr++) {
                            for ($cc = $c; $cc < $c + $colspan; $cc++) {
                                $coveredRowspan[$rr . ',' . $cc] = true;
                            }
                        }
                        for ($cc = $c + 1; $cc < $c + $colspan; $cc++) {
                            $skipCols[$r . ',' . $cc] = true;
                        }

                        $rolCelda = (string) ($cfg['rol'] ?? 'input');
                        if (in_array($rolCelda, ['titulo', 'etiqueta'], true)) {
                            $celdaHtml = registro_personalizado_texto_fijo_html(
                                (string) ($cfg['texto_fijo'] ?? ''),
                                (string) ($cfg['fuente'] ?? 'normal')
                            );
                            if ($celdaHtml !== '') {
                                $celdaHtml = $wrapPersonalizadoCelda($celdaHtml, $cfg);
                            }
                        } else {
                            $rawVal = (string) ($cellValues[$blockId][$r][$c] ?? '');
                            $principal = $resolvePrincipal($cfg, $rawVal);
                            $medida = $resolveMedida($blockId, $r, $c);
                            $celdaHtml = $buildCeldaReporte($principal, $medida, $bloqueTipo, $alineacionSec);
                            $celdaHtml = $wrapPersonalizadoCelda($celdaHtml, $cfg);
                        }

                        $rowCells[] = [
                            'html'    => $celdaHtml,
                            'colspan' => $colspan,
                            'rowspan' => $rowspan,
                            'estilo'  => LabotestModel::buildPersonalizadoCeldaReporteStyle($cfg),
                        ];
                    }

                    if ($rowCells !== []) {
                        $grillaFilas[] = $rowCells;
                    }
                }

                if ($titulosFilasGrilla === [] && $grillaFilas === []) {
                    continue;
                }

                $secOut = [
                    'seccion'           => $blockId,
                    'tipo'              => $bloqueTipo,
                    'label'             => $secLabel,
                    'columnas'          => $columnas,
                    'titulos_por_col'   => $titulosPorCol,
                    'titulos_filas'     => $titulosFilasGrilla,
                    'titulos_banda'     => [],
                    'columnas_detalle'  => [],
                    'max_titulo_filas'  => count($titulosFilasGrilla),
                    'filas'             => $grillaFilas,
                    'alineacion_filas'  => $alineacionSec,
                    'grilla_reporte'    => [
                        'columnas'      => $columnas,
                        'titulos_filas' => $titulosFilasGrilla,
                        'filas'         => $grillaFilas,
                    ],
                    'reporte_estilo'    => LabotestModel::normalizePersonalizadoReporteEstiloBloque($bloque),
                ];
                $out[] = $secOut;

                continue;
            }

            $filasRaw = [];
            for ($r = 0; $r < $filas; $r++) {
                $rowOut = [];
                for ($c = 0; $c < $columnas; $c++) {
                    $cfg = $normalizeCeldaCfg($celdasCfg[$r][$c] ?? ['modo' => 'texto']);
                    $rawVal = (string) ($cellValues[$blockId][$r][$c] ?? '');
                    if ($rawVal === '' && in_array($cfg['rol'] ?? 'input', ['titulo', 'etiqueta'], true)) {
                        $rawVal = (string) ($cfg['texto_fijo'] ?? '');
                    }
                    $principal = $resolvePrincipal($cfg, $rawVal);
                    $medida = $resolveMedida($blockId, $r, $c);
                    $celdaHtml = $buildCeldaReporte($principal, $medida, $bloqueTipo, $alineacionSec);
                    $rowOut[] = $wrapPersonalizadoCelda($celdaHtml, $cfg);
                }
                $filasRaw[] = $rowOut;
            }

            $compacto = LabotestModel::compactCultivoSectionDisplayForReport($titulosPorCol, $filasRaw, $columnas);
            if ($compacto === null) {
                continue;
            }

            $secOut = [
                'seccion'           => $blockId,
                'tipo'              => $bloqueTipo,
                'label'             => $secLabel,
                'columnas'          => $compacto['columnas'],
                'titulos_por_col'   => $compacto['titulos_por_col'],
                'titulos_filas'     => $compacto['titulos_filas'],
                'titulos_banda'     => $compacto['titulos_banda'],
                'columnas_detalle'  => $compacto['columnas_detalle'],
                'max_titulo_filas'  => $compacto['max_titulo_filas'],
                'filas'             => $compacto['filas'],
                'alineacion_filas'  => $alineacionSec,
            ];
            $out[] = $secOut;
        }

        return $out;
    }

    protected function cultivoMatrizTieneValores(array $cellValues): bool
    {
        foreach ($cellValues as $sec) {
            if (! is_array($sec)) {
                continue;
            }
            foreach ($sec as $fila) {
                if (! is_array($fila)) {
                    continue;
                }
                foreach ($fila as $val) {
                    if (trim((string) $val) !== '') {
                        return true;
                    }
                }
            }
        }

        return false;
    }

    /**
     * Fichas clínicas con datos guardados, listas para mostrar en la hoja de trabajo.
     *
     * @param list<array<string, mixed>> $pruebasEnOrden
     * @return array<int, array{ficha_clinica_id: int, nombre: string, cultivo_item: object}>
     */
    public function buildFichasClinicasOrdenMap(int $registroId, array $pruebasEnOrden): array
    {
        if ($registroId < 1 || $pruebasEnOrden === []) {
            return [];
        }

        $regFichaModel = model(\App\Models\RegistroFichaClinicaModel::class);
        $fichaModel = model(\App\Models\FichaClinicaModel::class);
        $allData = $regFichaModel->getAllDataByRegistro($registroId);
        if ($allData === []) {
            return [];
        }

        $out = [];
        foreach ($pruebasEnOrden as $prueba) {
            $pid = (int) ($prueba['prianacategoria_id'] ?? 0);
            if ($pid < 1) {
                continue;
            }
            $data = $allData[$pid] ?? null;
            if ($data === null || empty($data['has_data'])) {
                continue;
            }

            $fichaId = (int) ($data['ficha_clinica_id'] ?? 0);
            if ($fichaId < 1) {
                continue;
            }

            $cultivoItem = $this->buildFichaClinicaOrdenItem(
                $pid,
                $fichaId,
                is_array($data['valores'] ?? null) ? $data['valores'] : []
            );
            if ($cultivoItem === null) {
                continue;
            }

            $fichaRow = $fichaModel->getById($fichaId);
            $out[$pid] = [
                'ficha_clinica_id' => $fichaId,
                'nombre'           => trim((string) ($fichaRow['nombre'] ?? 'Ficha clínica')),
                'cultivo_item'     => $cultivoItem,
            ];
        }

        return $out;
    }

    /**
     * @param array<string, string> $valoresFlat
     */
    public function buildFichaClinicaOrdenItem(int $prianacategoriaId, int $fichaClinicaId, array $valoresFlat): ?object
    {
        if ($prianacategoriaId < 1 || $fichaClinicaId < 1) {
            return null;
        }

        $extracted = $this->extractFichaCellValuesFromFlat($valoresFlat, $fichaClinicaId, $prianacategoriaId);
        $cellValues = $extracted['cv'];
        if (! $this->cultivoMatrizTieneValores($cellValues)) {
            return null;
        }

        $matriz = model(\App\Models\FichaClinicaModel::class)->getMatrizConfig($fichaClinicaId);
        $display = $this->formatCultivoMatrizForReport(
            $matriz,
            $cellValues,
            $extracted['cvn'],
            null,
            true
        );

        return (object) [
            'es_cultivo_matriz'       => true,
            'es_personalizado_matriz' => true,
            'prianacategoria_id'      => $prianacategoriaId,
            'padre'                   => '',
            'hijo'                    => '',
            'tipo_muestra_nombre'     => '',
            'metodo_nombre'           => '',
            'cultivo_display'         => $display,
        ];
    }

    /**
     * @param array<string, string> $valoresFlat
     * @return array{cv: array<string, array<int, array<int, string>>>, cvn: array<string, array<int, array<int, string>>>}
     */
    protected function extractFichaCellValuesFromFlat(array $valoresFlat, int $fichaClinicaId, int $prianacategoriaId): array
    {
        $out = [];
        $outNum = [];

        $mergeSection = static function (array &$target, string $sec, int $fila, int $col, string $val): void {
            if ($val === '') {
                return;
            }
            if (! isset($target[$sec])) {
                $target[$sec] = [];
            }
            if (! isset($target[$sec][$fila])) {
                $target[$sec][$fila] = [];
            }
            $target[$sec][$fila][$col] = $val;
        };

        foreach ($valoresFlat as $key => $val) {
            if (! is_string($key)) {
                continue;
            }
            $val = trim(is_scalar($val) ? (string) $val : '');
            if ($val === '') {
                continue;
            }
            if (preg_match('/^fc_(\d+)_(\d+)_([a-z][a-z0-9_]*)_(\d+)_(\d+)$/', $key, $m)) {
                if ((int) $m[1] !== $fichaClinicaId || (int) $m[2] !== $prianacategoriaId) {
                    continue;
                }
                $mergeSection($out, (string) $m[3], (int) $m[4], (int) $m[5], $val);
                continue;
            }
            if (preg_match('/^fcn_(\d+)_(\d+)_([a-z][a-z0-9_]*)_(\d+)_(\d+)$/', $key, $m)) {
                if ((int) $m[1] !== $fichaClinicaId || (int) $m[2] !== $prianacategoriaId) {
                    continue;
                }
                $mergeSection($outNum, (string) $m[3], (int) $m[4], (int) $m[5], $val);
            }
        }

        $blobKey = 'ficha_clinica_' . $fichaClinicaId . '_' . $prianacategoriaId;
        $blob = $valoresFlat[$blobKey] ?? null;
        if ($blob !== null && $blob !== '') {
            $decoded = is_array($blob) ? $blob : json_decode((string) $blob, true);
            if (is_array($decoded)) {
                foreach ($decoded as $sec => $filas) {
                    if (! is_array($filas)) {
                        continue;
                    }
                    foreach ($filas as $fila => $cols) {
                        if (! is_array($cols)) {
                            continue;
                        }
                        foreach ($cols as $col => $cellVal) {
                            $mergeSection(
                                $out,
                                (string) $sec,
                                (int) $fila,
                                (int) $col,
                                trim(is_scalar($cellVal) ? (string) $cellVal : '')
                            );
                        }
                    }
                }
            }
        }

        return ['cv' => $out, 'cvn' => $outNum];
    }

    /**
     * Los separadores (es_separador) no se guardan en regvalues; el reporte solo ve filas c_* con valor.
     * Reconstruye el orden completo de cada prueba compuesta desde secanacategoria e inserta títulos.
     *
     * @param array<string, list<object>> $grupos
     * @return array<string, list<object>>
     */
    protected function mergeSeparadoresYOrdenCompuestoDesdePlantilla(array $grupos, array $matchingPoblacionIds, ?int $gender): array
    {
        foreach ($grupos as $padre => $items) {
            if ($items === []) {
                continue;
            }
            $complejoPorPria = [];
            $otros = [];
            foreach ($items as $it) {
                $it = is_array($it) ? (object) $it : $it;
                $pid = (int) ($it->prianacategoria_id ?? 0);
                $sid = (int) ($it->secanacategoria_id ?? 0);
                if ($pid > 0 && $sid > 0) {
                    $complejoPorPria[$pid][] = $it;
                } else {
                    $otros[] = $it;
                }
            }
            if ($complejoPorPria === []) {
                continue;
            }
            $priaIds = array_keys($complejoPorPria);
            usort($priaIds, static function (int $a, int $b) use ($complejoPorPria): int {
                $minA = PHP_INT_MAX;
                foreach ($complejoPorPria[$a] as $row) {
                    $minA = min($minA, (int) ($row->orden ?? 0));
                }
                $minB = PHP_INT_MAX;
                foreach ($complejoPorPria[$b] as $row) {
                    $minB = min($minB, (int) ($row->orden ?? 0));
                }
                if ($minA !== $minB) {
                    return $minA <=> $minB;
                }

                return $a <=> $b;
            });
            $mergedComplejo = [];
            foreach ($priaIds as $priaId) {
                $mergedComplejo = array_merge(
                    $mergedComplejo,
                    $this->mergeUnPrianacategoriaConSeparadoresDesdePlantilla(
                        $priaId,
                        $complejoPorPria[$priaId],
                        $matchingPoblacionIds,
                        $gender
                    )
                );
            }
            $grupos[$padre] = array_merge($otros, $mergedComplejo);
        }

        return $grupos;
    }

    /**
     * @param list<object> $itemsObjects
     * @return list<object>
     */
    protected function mergeUnPrianacategoriaConSeparadoresDesdePlantilla(
        int $priaId,
        array $itemsObjects,
        array $matchingPoblacionIds,
        ?int $gender
    ): array {
        $refs = $this->registerModel->getAllSecItemsByPrianacategoriaForReport($priaId, $matchingPoblacionIds, $gender);
        if ($refs === []) {
            return $itemsObjects;
        }
        $bySecId = [];
        foreach ($itemsObjects as $it) {
            $sid = (int) ($it->secanacategoria_id ?? 0);
            if ($sid > 0) {
                $bySecId[$sid] = $it;
            }
        }
        $out = [];
        foreach ($refs as $ref) {
            $sid = (int) ($ref['secanacategoria_id'] ?? 0);
            if ((int) ($ref['es_separador'] ?? 0) === 1) {
                if (isset($bySecId[$sid])) {
                    $o = $bySecId[$sid];
                    $o->es_separador = 1;
                    unset($bySecId[$sid]);
                } else {
                    $o = (object) $ref;
                    $o->regvalues = '-';
                    $o->es_separador = 1;
                }
                $out[] = $o;

                continue;
            }
            if (isset($bySecId[$sid])) {
                $out[] = $bySecId[$sid];
                unset($bySecId[$sid]);
            } else {
                $o = (object) $ref;
                $o->regvalues = '-';
                $out[] = $o;
            }
        }
        foreach ($bySecId as $left) {
            $out[] = $left;
        }

        return $out;
    }

    /**
     * Cuenta cuántos resultados no vacíos se ingresaron por prianacategoria.
     * @return array<int,int> [prianacategoria_id => cantidad]
     */
    protected function countEnteredValuesByPrianacategoria(array $analisis): array
    {
        $counts = [];
        foreach ($analisis as $prueba) {
            $name = (string)($prueba['name'] ?? '');
            $val = trim((string)($prueba['regvalues'] ?? $prueba['value'] ?? ''));
            if ($name === '' || $val === '') {
                continue;
            }

            $priaId = 0;
            if (strpos($name, '|') !== false) {
                [$priaStr] = explode('|', $name, 2);
                $priaId = (int)trim($priaStr);
            } elseif (strpos($name, '_') !== false) {
                [$tipo, $idStr] = explode('_', $name, 2);
                $id = (int)$idStr;
                if ($id > 0) {
                    if ($tipo === 'noc') {
                        $item = $this->registerModel->getAnalisisNocompleja($id);
                        $priaId = (int)($item->prianacategoria_id ?? 0);
                    } elseif ($tipo === 'c') {
                        $item = $this->registerModel->getAnalisisCompleja($id);
                        $priaId = (int)($item->prianacategoria_id ?? 0);
                    }
                }
            }
            if ($priaId > 0) {
                $counts[$priaId] = ($counts[$priaId] ?? 0) + 1;
            }
        }
        return $counts;
    }

    /**
     * Completa filas faltantes de referencias cuando una prueba tiene mostrar_valores=1.
     * Se agregan como filas sin resultado ("-") para que aparezcan en el reporte.
     */
    protected function appendMissingReferenceRows(array $grupos, object $registerInfo, array $eligiblePriaConfig = [], array $matchingPoblacionIds = [], ?int $gender = null): array
    {
        foreach ($eligiblePriaConfig as $cfg) {
            $priaId = (int)($cfg['prianacategoria_id'] ?? 0);
            if ($priaId < 1) {
                continue;
            }
            $isCompleja = (int)($cfg['compleja'] ?? 0) === 1;
            if (LabotestModel::esMatrizConfigurable((int) ($cfg['compleja'] ?? 0))) {
                continue;
            }

            if ($isCompleja) {
                $refs = $this->registerModel->getAllSecItemsByPrianacategoriaForReport($priaId, $matchingPoblacionIds, $gender);
            } else {
                $refs = $this->registerModel->getAllPriResultadosByPrianacategoriaForReport($priaId);
            }
            if (empty($refs)) continue;

            $padre = trim((string)($refs[0]['padre'] ?? ''));
            $hijo = trim((string)($refs[0]['hijo'] ?? ''));
            if ($padre === '' || $hijo === '') {
                continue;
            }

            $grupoActual = $grupos[$padre] ?? [];
            $otrosItems = [];
            $resultadoPorPri = [];
            $itemPorKey = [];
            foreach ($grupoActual as $it) {
                $itPria = (int) ($it->prianacategoria_id ?? 0);
                // Solo coincidir por hijo si la fila no trae prianacategoria_id (datos antiguos); si no, otra prueba con el mismo nombre de categoría absorbería filas y duplicaría bloques.
                $sameTest = ($itPria === $priaId)
                    || ($itPria === 0 && $priaId > 0 && trim((string) ($it->hijo ?? '')) === $hijo);
                if ($sameTest) {
                    if ($isCompleja) {
                        // En complejas, usar secanacategoria_id evita perder valores cuando
                        // existen varias filas con el mismo nombre.
                        $key = (string) ((int) ($it->secanacategoria_id ?? 0));
                    } else {
                        $key = (string)((int)($it->priresultados_id ?? 0));
                    }
                    if ($key !== '') {
                        $resultadoPorPri[$key] = (string)($it->regvalues ?? '');
                        $itemPorKey[$key] = $it;
                    }
                    continue;
                }
                $otrosItems[] = $it;
            }

            $reconstruidos = [];
            foreach ($refs as $ref) {
                if ($isCompleja) {
                    $key = (string) ((int) ($ref['secanacategoria_id'] ?? 0));
                } else {
                    $key = (string)((int)($ref['priresultados_id'] ?? 0));
                }
                $item = isset($itemPorKey[$key]) ? $itemPorKey[$key] : (object) $ref;
                if (!$isCompleja) {
                    $item->nombre = $hijo;
                }
                $val = trim((string)($resultadoPorPri[$key] ?? ''));
                $item->regvalues = ((int)($ref['es_separador'] ?? 0) === 1)
                    ? '-'
                    : (($val === '') ? '-' : $val);
                $this->applyTextoFijoRegvalueForReport($item);
                $item->show_reference = true;
                $reconstruidos[] = $item;
                unset($itemPorKey[$key]);
            }

            // Mantener cualquier fila realmente cargada por el usuario aunque no
            // esté en la plantilla de referencias filtrada por población.
            foreach ($itemPorKey as $leftover) {
                $leftSec = (int) ($leftover->secanacategoria_id ?? 0);
                $leftNom = mb_strtolower(trim((string) ($leftover->nombre ?? '')));
                $duplicado = false;
                foreach ($reconstruidos as $ya) {
                    $yaSec = (int) ($ya->secanacategoria_id ?? 0);
                    $yaNom = mb_strtolower(trim((string) ($ya->nombre ?? '')));
                    if ($leftSec > 0 && $leftSec === $yaSec) {
                        $duplicado = true;
                        break;
                    }
                    if ($leftNom !== '' && $leftNom === $yaNom) {
                        $duplicado = true;
                        break;
                    }
                }
                if (! $duplicado) {
                    $reconstruidos[] = $leftover;
                }
            }

            usort($reconstruidos, static function ($a, $b) {
                $aVal = trim((string)($a->regvalues ?? ''));
                $bVal = trim((string)($b->regvalues ?? ''));
                $aSep = (int)($a->es_separador ?? 0) === 1;
                $bSep = (int)($b->es_separador ?? 0) === 1;
                $aEmpty = ! $aSep && ($aVal === '' || $aVal === '-');
                $bEmpty = ! $bSep && ($bVal === '' || $bVal === '-');
                if ($aEmpty !== $bEmpty) {
                    return $aEmpty ? 1 : -1;
                }
                $aOrd = (int)($a->orden ?? 0);
                $bOrd = (int)($b->orden ?? 0);
                if ($aOrd !== $bOrd) {
                    return $aOrd <=> $bOrd;
                }
                return ((int)($a->secanacategoria_id ?? 0)) <=> ((int)($b->secanacategoria_id ?? 0));
            });

            $grupos[$padre] = array_merge($otrosItems, $reconstruidos);
        }

        foreach ($grupos as $padre => $items) {
            usort($items, static function ($a, $b) {
                $aVal = trim((string)($a->regvalues ?? ''));
                $bVal = trim((string)($b->regvalues ?? ''));
                $aSep = (int)($a->es_separador ?? 0) === 1;
                $bSep = (int)($b->es_separador ?? 0) === 1;
                $aEmpty = ! $aSep && ($aVal === '' || $aVal === '-');
                $bEmpty = ! $bSep && ($bVal === '' || $bVal === '-');
                if ($aEmpty !== $bEmpty) {
                    return $aEmpty ? 1 : -1;
                }
                $aOrd = (int)($a->orden ?? 0);
                $bOrd = (int)($b->orden ?? 0);
                if ($aOrd !== $bOrd) {
                    return $aOrd <=> $bOrd;
                }
                return ((int)($a->secanacategoria_id ?? 0)) <=> ((int)($b->secanacategoria_id ?? 0));
            });
            $grupos[$padre] = $items;
        }

        return $grupos;
    }

    /**
     * Aplica visibilidad de referencia por prueba.
     * Se muestra cuando la prueba está en mostrar_valores=1.
     */
    protected function applyReferenceVisibility(array $grupos, array $eligiblePriaIds): array
    {
        $eligible = array_flip(array_map('intval', $eligiblePriaIds));
        foreach ($grupos as $padre => $items) {
            foreach ($items as $idx => $it) {
                $priaId = (int)($it->prianacategoria_id ?? 0);
                $items[$idx]->show_reference = isset($eligible[$priaId]);
            }
            $grupos[$padre] = $items;
        }
        return $grupos;
    }

    /**
     * Incluye en el reporte las pruebas del CSV de la orden que aún no tienen filas
     * (p. ej. matriz personalizada/cultivo sin cv_* guardados, como en registers/view).
     *
     * @param array<string, list<object|array<string, mixed>>> $grupos
     * @param array<int, int> $matchingPoblacionIds
     *
     * @return array<string, list<object|array<string, mixed>>>
     */
    protected function appendMissingPruebasFromRegistroOrder(
        array $grupos,
        string $pruebasCsv,
        array $matchingPoblacionIds = [],
        ?int $gender = null,
    ): array {
        $orderedIds = $this->extractPrianacategoriaIdsFromRegistroPruebas($pruebasCsv);
        if ($orderedIds === []) {
            return $grupos;
        }

        $presentPriaIds = $this->collectPrianacategoriaIdsFromGrupos($grupos);
        $missingIds = array_values(array_filter(
            $orderedIds,
            static fn (int $id): bool => ! isset($presentPriaIds[$id]),
        ));
        if ($missingIds === []) {
            return $grupos;
        }

        $cfgById = [];
        foreach ($this->registerModel->getPrianacategoriaConfigByIds($missingIds, true) as $cfgRow) {
            $pid = (int) ($cfgRow['prianacategoria_id'] ?? 0);
            if ($pid > 0) {
                $cfgById[$pid] = $cfgRow;
            }
        }

        foreach ($missingIds as $priaId) {
            $cfg = $cfgById[$priaId] ?? null;
            if ($cfg === null) {
                continue;
            }

            $compleja = (int) ($cfg['compleja'] ?? 0);
            if (LabotestModel::esMatrizConfigurable($compleja)) {
                $item = $this->buildCultivoMatrizReportItem($priaId, [], [], null, true);
                if ($item === null) {
                    continue;
                }
                $item->incluir_en_reporte_sin_valores = true;
                $padre = trim((string) ($item->padre ?? ''));
                if ($padre === '') {
                    continue;
                }
                $grupos[$padre] ??= [];
                $grupos[$padre][] = $item;
                continue;
            }

            foreach ($this->buildPlaceholderItemsForMissingPrueba($priaId, $cfg, $matchingPoblacionIds, $gender) as $item) {
                $item->incluir_en_reporte_sin_valores = true;
                $padre = trim((string) ($item->padre ?? ''));
                if ($padre === '') {
                    continue;
                }
                $grupos[$padre] ??= [];
                $grupos[$padre][] = $item;
            }
        }

        return $grupos;
    }

    /**
     * @param array<string, list<object|array<string, mixed>>> $grupos
     *
     * @return array<int, true>
     */
    protected function collectPrianacategoriaIdsFromGrupos(array $grupos): array
    {
        $out = [];
        foreach ($grupos as $items) {
            if (! is_array($items)) {
                continue;
            }
            foreach ($items as $raw) {
                $it = is_array($raw) ? (object) $raw : $raw;
                $pid = (int) ($it->prianacategoria_id ?? 0);
                if ($pid > 0) {
                    $out[$pid] = true;
                }
            }
        }

        return $out;
    }

    /**
     * Filas mínimas para una prueba de la orden sin resultados guardados aún.
     *
     * @param array<string, mixed> $cfg
     * @param array<int, int> $matchingPoblacionIds
     *
     * @return list<object>
     */
    protected function buildPlaceholderItemsForMissingPrueba(
        int $prianacategoriaId,
        array $cfg,
        array $matchingPoblacionIds = [],
        ?int $gender = null,
    ): array {
        $compleja = (int) ($cfg['compleja'] ?? 0);
        if ($compleja === LabotestModel::COMPLEJA_COMPOUESTA) {
            $refs = $this->registerModel->getAllSecItemsByPrianacategoriaForReport(
                $prianacategoriaId,
                $matchingPoblacionIds,
                $gender,
            );
            if ($refs === []) {
                return $this->buildSinglePruebaPlaceholderItem($prianacategoriaId, $cfg);
            }
            $out = [];
            foreach ($refs as $ref) {
                $item = (object) $ref;
                $item->regvalues = ((int) ($ref['es_separador'] ?? 0) === 1) ? '-' : '-';
                $this->applyTextoFijoRegvalueForReport($item);
                $out[] = $item;
            }

            return $out;
        }

        return $this->buildSinglePruebaPlaceholderItem($prianacategoriaId, $cfg);
    }

    /**
     * @param array<string, mixed> $cfg
     *
     * @return list<object>
     */
    protected function buildSinglePruebaPlaceholderItem(int $prianacategoriaId, array $cfg): array
    {
        $meta = $this->registerModel->getPrianacategoriaWithArea($prianacategoriaId)
            ?? $this->registerModel->getPrianacategoriaWithAreaIncludingRetired($prianacategoriaId);
        if ($meta === null) {
            return [];
        }

        $hijo = trim((string) ($cfg['name'] ?? ($meta->hijo ?? '')));
        $padre = trim((string) ($meta->padre ?? ''));
        if ($padre === '' || $hijo === '') {
            return [];
        }

        return [(object) [
            'prianacategoria_id' => $prianacategoriaId,
            'padre'              => $padre,
            'hijo'               => $hijo,
            'nombre'             => $hijo,
            'regvalues'          => '-',
            'orden'              => 0,
        ]];
    }

    /**
     * @param array<string, list<object|array<string, mixed>>> $grupos
     * @return array<string, list<object|array<string, mixed>>>
     */
    protected function dropGruposSinValorIngresado(array $grupos): array
    {
        foreach ($grupos as $padre => $items) {
            $itemsByPria = [];
            foreach ($items as $raw) {
                $it = is_array($raw) ? (object) $raw : $raw;
                $pid = (int) ($it->prianacategoria_id ?? 0);
                $itemsByPria[$pid][] = $raw;
            }

            $kept = [];
            foreach ($itemsByPria as $priaItems) {
                if ($this->grupoTieneAlgunValorIngresado($priaItems, false)) {
                    foreach ($priaItems as $priaItem) {
                        $kept[] = $priaItem;
                    }
                }
            }

            if ($kept === []) {
                unset($grupos[$padre]);
            } else {
                $grupos[$padre] = $kept;
            }
        }

        return $grupos;
    }

    /**
     * @param list<object|array<string, mixed>> $items
     */
    protected function grupoTieneAlgunValorIngresado(array $items, bool $considerarShowReference = false): bool
    {
        foreach ($items as $raw) {
            $it = is_array($raw) ? (object) $raw : $raw;
            if ((int) ($it->es_separador ?? 0) === 1) {
                continue;
            }
            if (! empty($it->es_cultivo_matriz)) {
                if ($this->cultivoMatrizTieneValores(is_array($it->cultivo_valores ?? null) ? $it->cultivo_valores : [])) {
                    return true;
                }
                continue;
            }
            if ($considerarShowReference && !empty($it->show_reference)) {
                return true;
            }
            $v = trim((string) ($it->regvalues ?? ''));
            if ($v !== '' && $v !== '-') {
                return true;
            }
        }

        return false;
    }

    protected function resolveRegvalue(int $formId, string $rawValue, int $registroId): string
    {
        if ($formId === 1 || $formId === 0) {
            return $rawValue;
        }
        if (! array_key_exists($formId, $this->reportBuildFormulaCache)) {
            $this->reportBuildFormulaCache[$formId] = $this->registerModel->getFormula($formId);
        }
        $formulaRow  = $this->reportBuildFormulaCache[$formId];
        $formulaName = is_object($formulaRow) ? ($formulaRow->nombre_fun ?? '') : '';
        if (is_string($formulaName) && function_exists($formulaName)) {
            return $formulaName($rawValue, $registroId);
        }
        return $rawValue;
    }

    /**
     * Si el parámetro es texto fijo y no hay valor guardado, usa el HTML definido en configuración.
     */
    protected function applyTextoFijoRegvalueForReport(object $item): void
    {
        if (! registro_opcion_es_texto_fijo((int) ($item->opcion_id ?? 0))) {
            return;
        }
        $resolved = registro_texto_fijo_para_mostrar(
            (string) ($item->regvalues ?? ''),
            (string) ($item->texto_fijo ?? '')
        );
        if ($resolved !== '') {
            $item->regvalues = $resolved;
        }
    }

    /**
     * Texto de edad (años, meses, días) respecto a una fecha de referencia (p. ej. ingreso de la orden).
     */
    public function formatEdadAlMomento(?string $birthday, $referenceDate = null): string
    {
        $birthday = $birthday !== null ? trim((string) $birthday) : '';
        if ($birthday === '') {
            return '-';
        }
        try {
            $fechaNac = new \DateTime($birthday);
        } catch (\Throwable $e) {
            return '-';
        }
        $ref = $referenceDate;
        if ($ref === null) {
            $ref = new \DateTime();
        } elseif (is_string($ref)) {
            try {
                $ref = new \DateTime($ref);
            } catch (\Throwable $e) {
                $ref = new \DateTime();
            }
        }
        $edad = $fechaNac->diff($ref);

        return $edad->y . ' años, ' . $edad->m . ' meses y ' . $edad->d . ' días';
    }

    /**
     * Prepara datos del paciente para reporte (edad calculada)
     */
    public function preparePacienteParaReporte(?object $paciente): object
    {
        $paciente = $paciente ?? (object) [
            'first_name'   => '',
            'last_name_fa' => '',
            'last_name_mom' => '',
            'edad'         => '-',
            'phone_number' => '',
            'paciente_institucion' => '',
        ];
        $paciente->paciente_institucion = trim((string) ($paciente->paciente_institucion ?? ''));
        if (!empty($paciente->birthday)) {
            $fechaNac = new \DateTime($paciente->birthday);
            $hoy      = new \DateTime();
            $edad     = $fechaNac->diff($hoy);
            $paciente->edad = $edad->y . ' años, ' . $edad->m . ' meses y ' . $edad->d . ' días';
        }
        helper('registro');
        $paciente->genero_texto = paciente_genero_texto($paciente);

        return $paciente;
    }

    /**
     * Obtiene configuración del lab como array (cacheada vía ConfigService).
     */
    public function getLabConfig(): array
    {
        return (new ConfigService())->getAllAsArray();
    }

    private function resetReportBuildCaches(): void
    {
        $this->reportBuildComplejaCache    = [];
        $this->reportBuildNocomplejaCache  = [];
        $this->reportBuildSecItemCache     = [];
        $this->reportBuildFormulaCache     = [];
    }

    /**
     * Datos mínimos para la shell de viewreport (visor PDF + campos ocultos Guardar).
     *
     * @return array{register_info: object, grupos: array<string, list<object|array<string, mixed>>>}|null
     */
    public function prepareViewreportPageData(int $registroId): ?array
    {
        $data = $this->prepareReportData($registroId, true);
        if ($data === null) {
            return null;
        }

        return [
            'register_info' => $data['register_info'],
            'grupos'        => $data['grupos'],
        ];
    }

    /**
     * Nombres de tipo de muestra por prianacategoria_id (consulta batch).
     *
     * @param list<array<string, mixed>> $priasCfg
     *
     * @return array<int, string>
     */
    private function buildReportPriaTipoMuestraNombreMap(array $priasCfg): array
    {
        $tipoIds = [];
        foreach ($priasCfg as $cfgRow) {
            $tid = (int) ($cfgRow['tipo_muestra_id'] ?? 0);
            if ($tid > 0) {
                $tipoIds[$tid] = $tid;
            }
        }
        if ($tipoIds === []) {
            return [];
        }

        $out = [];
        try {
            $rows = model(\App\Models\TipoMuestraModel::class)
                ->whereIn('tipo_muestra_id', array_values($tipoIds))
                ->where('deleted', 0)
                ->findAll();
            foreach ($rows as $tmRow) {
                $tid = (int) ($tmRow['tipo_muestra_id'] ?? 0);
                $nom = trim((string) ($tmRow['nombre'] ?? ''));
                if ($tid > 0 && $nom !== '') {
                    $out[$tid] = $nom;
                }
            }
        } catch (\Throwable $e) {
            return [];
        }

        $mapped = [];
        foreach ($priasCfg as $cfgRow) {
            $pId = (int) ($cfgRow['prianacategoria_id'] ?? 0);
            $tid = (int) ($cfgRow['tipo_muestra_id'] ?? 0);
            if ($pId > 0 && $tid > 0 && isset($out[$tid])) {
                $mapped[$pId] = $out[$tid];
            }
        }

        return $mapped;
    }

    /**
     * Nombres de método por prianacategoria_id (consulta batch).
     *
     * @param list<array<string, mixed>> $priasCfg
     *
     * @return array<int, string>
     */
    private function buildReportPriaMetodoNombreMap(array $priasCfg): array
    {
        $metodoIds = [];
        foreach ($priasCfg as $cfgRow) {
            $mid = (int) ($cfgRow['metodo_id'] ?? 0);
            if ($mid > 0) {
                $metodoIds[$mid] = $mid;
            }
        }
        if ($metodoIds === []) {
            return [];
        }

        $out = [];
        try {
            $rows = model(\App\Models\MetodoModel::class)
                ->whereIn('metodo_id', array_values($metodoIds))
                ->where('deleted', 0)
                ->findAll();
            foreach ($rows as $mRow) {
                $mid = (int) ($mRow['metodo_id'] ?? 0);
                $nom = trim((string) ($mRow['nombre'] ?? ''));
                if ($mid > 0 && $nom !== '') {
                    $out[$mid] = $nom;
                }
            }
        } catch (\Throwable $e) {
            return [];
        }

        $mapped = [];
        foreach ($priasCfg as $cfgRow) {
            $pId = (int) ($cfgRow['prianacategoria_id'] ?? 0);
            $mid = (int) ($cfgRow['metodo_id'] ?? 0);
            if ($pId > 0 && $mid > 0 && isset($out[$mid])) {
                $mapped[$pId] = $out[$mid];
            }
        }

        return $mapped;
    }

    /**
     * Zona horaria para fechas del reporte: clave `timezone` en app_config (Configuración del sistema),
     * luego `appTimezone` en app/Config/App.php, y por último UTC.
     */
    public static function reportDisplayTimezone(): string
    {
        $fromApp = trim((string) (config(AppConfig::class)->appTimezone ?? 'UTC'));
        if ($fromApp === '') {
            $fromApp = 'UTC';
        }

        try {
            $cfg = (new ConfigService())->getAllAsArray();
            $tz  = trim((string) ($cfg['timezone'] ?? ''));
            if ($tz !== '' && self::timezoneIdIsValid($tz)) {
                return $tz;
            }
        } catch (\Throwable $e) {
            // seguir con App.php
        }

        return self::timezoneIdIsValid($fromApp) ? $fromApp : 'UTC';
    }

    /**
     * Si es true, los DATETIME en BD se guardan en UTC y se muestran en la zona de /config
     * (independiente del reloj del PC). Si es false, se guardan como hora local del laboratorio (legado).
     */
    public static function usesUtcDatetimeStorage(): bool
    {
        try {
            $cfg = (new ConfigService())->getAllAsArray();

            return ($cfg['lab_datetime_storage'] ?? '1') === '1';
        } catch (\Throwable $e) {
            return true;
        }
    }

    /**
     * Resumen legible de la zona configurada (nombre + offset actual).
     */
    public static function labTimezoneSummary(): string
    {
        $tzId = self::reportDisplayTimezone();
        try {
            $now = self::nowInReportTimezone();
            $offset = $now->format('P');

            return $tzId . ' (UTC' . $offset . ')';
        } catch (\Throwable $e) {
            return $tzId;
        }
    }

    /**
     * Alinea PHP y MySQL con la política de fechas del laboratorio.
     * Idempotente: se ejecuta una vez por petición HTTP.
     */
    public static function applyRequestTimezone(): void
    {
        static $applied = false;
        if ($applied) {
            return;
        }
        $applied = true;

        $tzId = self::reportDisplayTimezone();
        try {
            date_default_timezone_set($tzId);
        } catch (\Throwable $e) {
            // ignorar TZ inválida en PHP
        }

        try {
            $db = \Config\Database::connect();
            if (! $db->connID) {
                return;
            }
            if (self::usesUtcDatetimeStorage()) {
                $db->simpleQuery("SET time_zone = '+00:00'");
            } else {
                $offset = (new \DateTimeImmutable('now', new \DateTimeZone($tzId)))->format('P');
                $db->simpleQuery('SET time_zone = ' . $db->escape($offset));
            }
        } catch (\Throwable $e) {
            log_message('debug', 'RegisterService::applyRequestTimezone MySQL: ' . $e->getMessage());
        }
    }

    private static function timezoneIdIsValid(string $tz): bool
    {
        try {
            new \DateTimeZone($tz);

            return true;
        } catch (\Throwable $e) {
            return false;
        }
    }

    private static function reportTimezoneObject(): \DateTimeZone
    {
        try {
            return new \DateTimeZone(self::reportDisplayTimezone());
        } catch (\Throwable $e) {
            return new \DateTimeZone('UTC');
        }
    }

    private static function nowInReportTimezone(): \DateTimeImmutable
    {
        return new \DateTimeImmutable('now', self::reportTimezoneObject());
    }

    /**
     * Instante actual en la zona del laboratorio.
     */
    public static function reportNow(): \DateTimeImmutable
    {
        return self::nowInReportTimezone();
    }

    /**
     * Fecha de hoy (Y-m-d) en la zona del laboratorio.
     */
    public static function todayForReport(): string
    {
        return self::nowInReportTimezone()->format('Y-m-d');
    }

    /**
     * Fecha relativa (p. ej. "-2 years", "monday this week") en Y-m-d según la zona del laboratorio.
     */
    public static function reportDateFromModifier(string $modifier): string
    {
        try {
            return self::nowInReportTimezone()->modify($modifier)->format('Y-m-d');
        } catch (\Throwable $e) {
            return self::todayForReport();
        }
    }

    /**
     * Límites de rango de fechas (día inclusive) para consultas SQL sobre columnas DATETIME.
     *
     * @return array{0: string, 1: string} [inicio inclusive, fin exclusive) en formato Y-m-d H:i:s
     */
    public static function labDateRangeToStorageBounds(string $dateFromYmd, string $dateToYmd): array
    {
        $tz    = self::reportTimezoneObject();
        $start = (new \DateTimeImmutable(substr(trim($dateFromYmd), 0, 10), $tz))->setTime(0, 0, 0);
        $end   = (new \DateTimeImmutable(substr(trim($dateToYmd), 0, 10), $tz))->modify('+1 day')->setTime(0, 0, 0);
        if (self::usesUtcDatetimeStorage()) {
            $utc   = new \DateTimeZone('UTC');
            $start = $start->setTimezone($utc);
            $end   = $end->setTimezone($utc);
        }

        return [$start->format('Y-m-d H:i:s'), $end->format('Y-m-d H:i:s')];
    }

    /**
     * Primer día del mes actual en Y-m-d según la zona del laboratorio.
     */
    public static function monthStartForReport(): string
    {
        return self::nowInReportTimezone()->modify('first day of this month')->format('Y-m-d');
    }

    /**
     * Formatea Y-m-d a d/m/Y en la zona del laboratorio.
     */
    public static function formatReportDate(?string $ymdDate): string
    {
        if ($ymdDate === null || trim($ymdDate) === '') {
            return '—';
        }
        $day = substr(trim($ymdDate), 0, 10);
        try {
            $dt = new \DateTimeImmutable($day, self::reportTimezoneObject());

            return $dt->format('d/m/Y');
        } catch (\Throwable $e) {
            return $day;
        }
    }

    /**
     * Subtítulo de rango para reportes: dd/mm/yyyy - dd/mm/yyyy.
     */
    public static function formatReportDateRangeSubtitle(string $startYmd, string $endYmd): string
    {
        return self::formatReportDate($startYmd) . ' - ' . self::formatReportDate($endYmd);
    }

    /**
     * Fecha/hora “ahora” para el reporte (vista, impresión, PDF) en {@see reportDisplayTimezone()}.
     */
    public static function formatNowForReport(): string
    {
        try {
            return self::nowInReportTimezone()->format('d/m/Y H:i:s');
        } catch (\Throwable $e) {
            return (new \DateTimeImmutable('now', new \DateTimeZone('UTC')))->format('d/m/Y H:i:s');
        }
    }

    /**
     * Fecha/hora actual corta (sin segundos) en la zona del laboratorio.
     */
    public static function formatNowForReportShort(): string
    {
        try {
            return self::nowInReportTimezone()->format('d/m/Y H:i');
        } catch (\Throwable $e) {
            return (new \DateTimeImmutable('now', new \DateTimeZone('UTC')))->format('d/m/Y H:i');
        }
    }

    /**
     * Fecha/hora actual para inputs de formulario (Y-m-d H:i) en la zona del laboratorio.
     */
    public static function formatNowForFormInput(): string
    {
        try {
            return self::nowInReportTimezone()->format('Y-m-d H:i');
        } catch (\Throwable $e) {
            return (new \DateTimeImmutable('now', new \DateTimeZone('UTC')))->format('Y-m-d H:i');
        }
    }

    /**
     * DATETIME de BD → Y-m-d H:i para inputs de formulario en la zona del laboratorio.
     */
    public static function formatStoredForFormInput(?string $mysqlDatetime): string
    {
        if ($mysqlDatetime === null || trim($mysqlDatetime) === '') {
            return self::formatNowForFormInput();
        }
        $raw = trim($mysqlDatetime);
        try {
            if (self::usesUtcDatetimeStorage()) {
                $dt = new \DateTimeImmutable($raw, new \DateTimeZone('UTC'));

                return $dt->setTimezone(self::reportTimezoneObject())->format('Y-m-d H:i');
            }
            $dt = new \DateTimeImmutable($raw, self::reportTimezoneObject());

            return $dt->format('Y-m-d H:i');
        } catch (\Throwable $e) {
            return strlen($raw) >= 16 ? substr($raw, 0, 16) : $raw;
        }
    }

    /**
     * Formatea un DATETIME de BD a texto en la zona del laboratorio (/config).
     */
    public static function formatStoredReporteFechaHora(string $mysqlDatetime): string
    {
        $raw = trim($mysqlDatetime);
        if ($raw === '') {
            return '—';
        }
        try {
            if (self::usesUtcDatetimeStorage()) {
                $dt = new \DateTimeImmutable($raw, new \DateTimeZone('UTC'));

                return $dt->setTimezone(self::reportTimezoneObject())->format('d/m/Y H:i:s');
            }
            $dt = new \DateTimeImmutable($raw, self::reportTimezoneObject());

            return $dt->format('d/m/Y H:i:s');
        } catch (\Throwable $e) {
            return $raw;
        }
    }

    /**
     * Formatea un DATETIME guardado en BD a d/m/Y H:i (sin segundos).
     */
    public static function formatStoredReporteFechaCorta(?string $mysqlDatetime): string
    {
        if ($mysqlDatetime === null || trim($mysqlDatetime) === '') {
            return '—';
        }
        $formatted = self::formatStoredReporteFechaHora(trim($mysqlDatetime));
        if ($formatted === '—') {
            return '—';
        }

        return strlen($formatted) >= 16 ? substr($formatted, 0, 16) : $formatted;
    }

    /**
     * DATETIME actual para guardar en BD (UTC o hora local del laboratorio según configuración).
     */
    public static function mysqlNowForReport(): string
    {
        $now = self::nowInReportTimezone();
        if (self::usesUtcDatetimeStorage()) {
            return $now->setTimezone(new \DateTimeZone('UTC'))->format('Y-m-d H:i:s');
        }

        return $now->format('Y-m-d H:i:s');
    }

    /**
     * Convierte fecha/hora capturada en formulario (reloj del laboratorio) al formato de guardado en BD.
     */
    public static function parseUserLabDatetimeToStorage(string $input): string
    {
        $input = trim($input);
        if ($input === '') {
            return self::mysqlNowForReport();
        }
        if (preg_match('/^\d{4}-\d{2}-\d{2} \d{2}:\d{2}$/', $input)) {
            $input .= ':00';
        }
        try {
            $dt = new \DateTimeImmutable($input, self::reportTimezoneObject());
            if (self::usesUtcDatetimeStorage()) {
                return $dt->setTimezone(new \DateTimeZone('UTC'))->format('Y-m-d H:i:s');
            }

            return $dt->format('Y-m-d H:i:s');
        } catch (\Throwable $e) {
            return self::mysqlNowForReport();
        }
    }

    private function mysqlNowForReportTimezone(): string
    {
        return self::mysqlNowForReport();
    }

    /**
     * “Fecha de reporte” en la vista web: valor fijado en BD tras imprimir/PDF, o la hora actual si aún no.
     */
    public function reportEmitidoEnForView(int $registroId): string
    {
        $raw = $this->registerModel->getReporteFechaHoraFijadaMysql($registroId);
        if ($raw !== null) {
            return self::formatStoredReporteFechaHora($raw);
        }

        return self::formatNowForReport();
    }

    /**
     * Al imprimir o exportar PDF: guarda la fecha/hora la primera vez y devuelve siempre el valor fijado.
     */
    public function lockReportEmitidoEnForPrintOrPdf(int $registroId): string
    {
        $mysql = $this->mysqlNowForReportTimezone();
        $raw   = $this->registerModel->lockReporteFechaHoraFijada($registroId, $mysql);
        if ($raw === null) {
            return self::formatNowForReport();
        }

        return self::formatStoredReporteFechaHora($raw);
    }

    /**
     * Formato estándar de fechas en reporte: día/mes/año y hora con segundos.
     */
    public function formatReportDateTimeDisplay(?string $dateTimeRaw): string
    {
        if ($dateTimeRaw === null || trim($dateTimeRaw) === '') {
            return '—';
        }

        return self::formatStoredReporteFechaHora(trim($dateTimeRaw));
    }

    /**
     * Tablas consolidadas de referencia (prueba compuesta + mostrar_valores) cuando hay más de una fila por parámetro (p. ej. Hemograma por grupo poblacional).
     *
     * @param list<array<string,mixed>> $eligiblePriaConfig
     * @return array<int, list<array<string,mixed>>> prianacategoria_id => filas
     */
    protected function buildReportPriaRefsConsolidada(array $eligiblePriaConfig): array
    {
        $out = [];
        foreach ($eligiblePriaConfig as $cfg) {
            if ((int) ($cfg['compleja'] ?? 0) !== 1) {
                continue;
            }
            $pid = (int) ($cfg['prianacategoria_id'] ?? 0);
            if ($pid < 1) {
                continue;
            }
            $rows = $this->registerModel->getSecReferenciasConsolidadasSinColapsar($pid);
            if (count($rows) < 2) {
                continue;
            }
            $countsPorNombre = [];
            foreach ($rows as $r) {
                $n = trim((string) ($r['nombre'] ?? ''));
                if ($n === '') {
                    continue;
                }
                $countsPorNombre[$n] = ($countsPorNombre[$n] ?? 0) + 1;
            }
            $hasMultiPoblacion = false;
            foreach ($countsPorNombre as $cnt) {
                if ($cnt > 1) {
                    $hasMultiPoblacion = true;
                    break;
                }
            }
            if (! $hasMultiPoblacion) {
                continue;
            }
            $out[$pid] = $rows;
        }

        return $out;
    }

    /**
     * Heatmap seriado y modo graficar por prianacategoria (sin SQL en render HTML).
     *
     * @param array<string, list<object|array<string, mixed>>> $grupos
     * @param list<array<string, mixed>>                        $priasCfg
     *
     * @return array{heatmaps: array<int, array<string, mixed>|null>, modos: array<int, int>}
     */
    private function buildReportCategoricalHeatmapMaps(array $grupos, array $priasCfg): array
    {
        $modos = [];
        foreach ($priasCfg as $cfg) {
            $pid = (int) ($cfg['prianacategoria_id'] ?? 0);
            if ($pid > 0) {
                $modos[$pid] = \App\Models\LabotestModel::normalizeGraficar((int) ($cfg['graficar'] ?? 0));
            }
        }

        $itemsByPria = [];
        foreach ($grupos as $items) {
            foreach ($items as $raw) {
                $it  = is_array($raw) ? (object) $raw : $raw;
                $pid = (int) ($it->prianacategoria_id ?? 0);
                if ($pid > 0) {
                    $itemsByPria[$pid][] = $raw;
                }
            }
        }

        $cmp      = new CategoricalSerialComparisonService();
        $heatmaps = [];
        foreach ($itemsByPria as $pid => $subItems) {
            $modo = $modos[$pid] ?? \App\Models\LabotestModel::GRAFICAR_NO;
            $heatmaps[$pid] = $cmp->buildFromReportItemsWithModo($subItems, $pid, $modo);
        }

        return ['heatmaps' => $heatmaps, 'modos' => $modos];
    }

    /**
     * Prepara datos para el reporte (viewreport / PDF)
     *
     * @param bool $forViewreportShell Si true, omite metadatos solo usados al generar el PDF.
     * @param bool $useCache           Si true, reutiliza caché de datos preparados (sin HTML/PDF).
     *
     * @return array<string, mixed>|null
     */
    public function prepareReportData(int $registroId, bool $forViewreportShell = false, bool $useCache = true): ?array
    {
        $dataCache = $this->reportDataCacheService();
        if ($useCache) {
            $fingerprint = $dataCache->computeFingerprint($registroId);
            $cached      = $dataCache->read($registroId, $fingerprint);
            if ($cached !== null) {
                \App\Services\Report\ReportPipelineMetrics::getInstance()->log(
                    'report_data_cache_hit',
                    0,
                    ['registro_id' => $registroId],
                );

                return $forViewreportShell
                    ? $this->sliceReportDataForViewreportShell($cached)
                    : $cached;
            }
            \App\Services\Report\ReportPipelineMetrics::getInstance()->log(
                'report_data_cache_miss',
                0,
                ['registro_id' => $registroId],
            );
        }

        $this->resetReportBuildCaches();

        $registerInfo = $this->registerModel->getInfoRefill($registroId);
        if (!$registerInfo) {
            return null;
        }
        if ($this->registerModel->isRegistroAnulado($registroId)) {
            return null;
        }

        self::applyRequestTimezone();

        $ingresoRaw = $registerInfo->ingreso ?? null;
        if ($ingresoRaw !== null && trim((string) $ingresoRaw) !== '') {
            $registerInfo->recepcion_fecha_hora = self::formatStoredReporteFechaHora((string) $ingresoRaw);
        } else {
            $registerInfo->recepcion_fecha_hora = self::formatNowForReport();
        }

        $master = $this->registerModel->getInforeport($registroId);
        $paciente = $master ? $this->registerModel->getInfoPaciente($master->person_id) : null;
        $doctorId = $master ? (int) ($master->doctor_id ?? 0) : 0;
        $doctor   = null;
        if ($doctorId > 0) {
            $doctorRow = model(\App\Models\DoctorModel::class)->getInfo($doctorId);
            if (is_object($doctorRow) && (int) ($doctorRow->doctor_id ?? 0) > 0) {
                $doctor = $doctorRow;
            }
        }
        $analisis = $this->registerModel->getInfoAnalisis($registroId);

        $paciente = $this->preparePacienteParaReporte($paciente);
        if ($doctorId < 1 || $doctor === null) {
            $cfg     = new ConfigService();
            $labelSd = trim((string) ($cfg->getAllAsArray()['label_sin_doctor'] ?? ''));
            if ($labelSd === '') {
                $labelSd = 'Sin doctor';
            }
            $doctor = (object) [
                'name'                     => $labelSd,
                'gender'                   => null,
                'report_sin_prefijo_medico' => true,
            ];
        }

        $pacienteType = $this->computePacienteType($registerInfo, $ingresoRaw);
        $registerInfo->paciente = $pacienteType;

        $birthday = $registerInfo->birthday ?? null;
        $patientGender = isset($registerInfo->gender) ? (int) $registerInfo->gender : null;
        $matchingPoblacionIds = $this->getMatchingPoblacionIds($birthday, $patientGender, $ingresoRaw);

        $grupos = $this->buildGruposParaReporte($registroId, $analisis, $matchingPoblacionIds, $patientGender);
        $grupos = $this->mergeSeparadoresYOrdenCompuestoDesdePlantilla($grupos, $matchingPoblacionIds, $patientGender);
        $pruebasIds = $this->extractPrianacategoriaIdsFromRegistroPruebas((string)($registerInfo->pruebas ?? ''));
        foreach ($analisis as $rvRow) {
            $rvName = trim((string) ($rvRow['name'] ?? ''));
            if ($rvName !== '' && preg_match('/^cv(n|u)?_(\d+)/', $rvName, $mCv)) {
                $pruebasIds[] = (int) $mCv[2];
            }
        }
        $pruebasIds = array_values(array_unique(array_filter(array_map('intval', $pruebasIds), static fn($x) => $x > 0)));
        $priasCfg = $this->registerModel->getPrianacategoriaConfigByIds($pruebasIds, true);
        $reportPriaTipoMuestraNombre = [];
        $reportPriaMetodoNombre      = [];
        if (! $forViewreportShell) {
            $reportPriaTipoMuestraNombre = $this->buildReportPriaTipoMuestraNombreMap($priasCfg);
            $reportPriaMetodoNombre      = $this->buildReportPriaMetodoNombreMap($priasCfg);
        }
        $eligiblePriaIds = [];
        $eligiblePriaConfig = [];
        foreach ($priasCfg as $cfg) {
            $pid = (int)($cfg['prianacategoria_id'] ?? 0);
            $mostrar = (int)($cfg['mostrar_valores'] ?? 0) === 1;
            if ($mostrar) {
                $eligiblePriaIds[] = $pid;
                $eligiblePriaConfig[] = $cfg;
            }
        }
        $grupos = $this->appendMissingReferenceRows($grupos, $registerInfo, $eligiblePriaConfig, $matchingPoblacionIds, $patientGender);
        $grupos = $this->applyReferenceVisibility($grupos, $eligiblePriaIds);
        $grupos = $this->deduplicateGrupoItemsByParametro($grupos);
        $grupos = $this->dropGruposSinValorIngresado($grupos);
        $grupos = $this->sortGruposByRegistroPruebasOrder($grupos, (string) ($registerInfo->pruebas ?? ''));

        $reportLabFirmas            = [];
        $reportPriaRefsConsolidada  = [];
        $reportCategoricalHeatmap   = [];
        $reportGraficarModo         = [];
        $pdfLayoutSnapshot          = [];
        if (! $forViewreportShell) {
            $reportLabFirmas           = $this->buildLabFirmasParaReporte($analisis, (string) ($registerInfo->pruebas ?? ''), array_keys($grupos));
            $reportPriaRefsConsolidada = $this->buildReportPriaRefsConsolidada($eligiblePriaConfig);
            $heatmapPack               = $this->buildReportCategoricalHeatmapMaps($grupos, $priasCfg);
            $reportCategoricalHeatmap  = $heatmapPack['heatmaps'];
            $reportGraficarModo        = $heatmapPack['modos'];
            $pdfLayoutSnapshot         = (new ReportPdfLayoutService())->getActiveLayoutForRender();
        }

        $result = [
            'register_info' => $registerInfo,
            'paciente'      => $paciente,
            'doctor'        => $doctor,
            'grupos'        => $grupos,
            'analisis'      => $analisis,
            'report_pria_tipo_muestra_nombre' => $reportPriaTipoMuestraNombre,
            'report_pria_metodo_nombre'       => $reportPriaMetodoNombre,
            'report_lab_firmas'               => $reportLabFirmas,
            'report_pria_refs_consolidada'    => $reportPriaRefsConsolidada,
            'report_categorical_heatmap'      => $reportCategoricalHeatmap,
            'report_graficar_modo'            => $reportGraficarModo,
            'pdf_layout'                      => $pdfLayoutSnapshot,
        ];

        if ($useCache && ! $forViewreportShell) {
            $dataCache->write($registroId, $dataCache->computeFingerprint($registroId), $result);
        }

        return $forViewreportShell ? $this->sliceReportDataForViewreportShell($result) : $result;
    }

    /**
     * @param array<string, mixed> $data
     *
     * @return array{register_info: object, grupos: array<string, list<object|array<string, mixed>>>}
     */
    private function sliceReportDataForViewreportShell(array $data): array
    {
        return [
            'register_info' => $data['register_info'],
            'grupos'        => $data['grupos'] ?? [],
        ];
    }

    private function reportDataCacheService(): \App\Services\Report\ReportDataCacheService
    {
        if ($this->reportDataCache === null) {
            $this->reportDataCache = new \App\Services\Report\ReportDataCacheService($this->registerModel);
        }

        return $this->reportDataCache;
    }

    public function clearReportDataCache(int $registroId): void
    {
        $this->reportDataCacheService()->clear($registroId);
    }

    /**
     * Ordena secciones y filas del reporte según el CSV de pruebas del registro.
     *
     * @param array<string, list<object|array<string, mixed>>> $grupos
     * @return array<string, list<object|array<string, mixed>>>
     */
    public function sortGruposByRegistroPruebasOrder(array $grupos, string $pruebasCsv): array
    {
        $orderedIds = $this->extractPrianacategoriaIdsFromRegistroPruebas($pruebasCsv);
        if ($orderedIds === [] || $grupos === []) {
            return $grupos;
        }

        $position = [];
        foreach ($orderedIds as $i => $id) {
            $position[$id] = $i;
        }

        $padreMinPos = [];
        foreach ($grupos as $padre => $items) {
            $min = PHP_INT_MAX;
            foreach ($items as $it) {
                $raw = is_array($it) ? $it : (array) $it;
                $pid = (int) ($raw['prianacategoria_id'] ?? 0);
                if ($pid > 0 && isset($position[$pid])) {
                    $min = min($min, $position[$pid]);
                }
            }
            $padreMinPos[$padre] = $min;
        }

        uksort($grupos, static function (string $a, string $b) use ($padreMinPos): int {
            $pa = $padreMinPos[$a] ?? PHP_INT_MAX;
            $pb = $padreMinPos[$b] ?? PHP_INT_MAX;
            if ($pa !== $pb) {
                return $pa <=> $pb;
            }

            return strcasecmp($a, $b);
        });

        foreach ($grupos as $padre => $items) {
            usort($items, static function ($a, $b) use ($position): int {
                $rawA = is_array($a) ? $a : (array) $a;
                $rawB = is_array($b) ? $b : (array) $b;
                $pa = (int) ($rawA['prianacategoria_id'] ?? 0);
                $pb = (int) ($rawB['prianacategoria_id'] ?? 0);
                $oa = $position[$pa] ?? PHP_INT_MAX;
                $ob = $position[$pb] ?? PHP_INT_MAX;
                if ($oa !== $ob) {
                    return $oa <=> $ob;
                }
                $aOrd = (int) ($rawA['orden'] ?? 0);
                $bOrd = (int) ($rawB['orden'] ?? 0);
                if ($aOrd !== $bOrd) {
                    return $aOrd <=> $bOrd;
                }

                return ((int) ($rawA['secanacategoria_id'] ?? 0)) <=> ((int) ($rawB['secanacategoria_id'] ?? 0));
            });
            $grupos[$padre] = $items;
        }

        return $grupos;
    }

    /**
     * Clave estable para validación por área (nombre del grupo / padre).
     */
    public static function labGrupoFirmaKey(string $padre): string
    {
        $p = trim($padre);
        if ($p === '') {
            return '';
        }

        return substr(hash('sha256', 'lab_grp|' . mb_strtolower($p, 'UTF-8')), 0, 16);
    }

    /**
     * Validación y aprobación por área (lab_val_grp_*, lab_app_grp_*) o por prueba (legacy lab_val_pri_*).
     *
     * @param list<string> $gruposPadreOrden Nombres de área en orden de reporte
     * @return list<array{prianacategoria_id:int, prueba_nombre:string, validator_name:string, approver_name:string, approver_cargo:string, approver_matricula:string, approver_seal:string, approver_signature:string}>
     */
    public function buildLabFirmasParaReporte(array $analisisRows, string $pruebasCsv, array $gruposPadreOrden = []): array
    {
        $byName = [];
        foreach ($analisisRows as $r) {
            $n = trim((string) ($r['name'] ?? ''));
            if ($n === '') {
                continue;
            }
            $byName[$n] = trim((string) ($r['regvalues'] ?? ''));
        }

        $configSvc = new ConfigService();
        $state     = $configSvc->getLabValidationStateForView();
        $valById   = [];
        foreach ($state['validators'] ?? [] as $v) {
            $id = (string) ($v['id'] ?? '');
            if ($id !== '') {
                $valById[$id] = $v;
            }
        }
        $appById = [];
        foreach ($state['approvers'] ?? [] as $a) {
            $id = (string) ($a['id'] ?? '');
            if ($id !== '') {
                $appById[$id] = $a;
            }
        }

        $resolveFirmaRow = static function (string $valId, string $appId) use ($valById, $appById): array {
            $vName = '';
            if ($valId !== '') {
                $vName = (string) ($valById[$valId]['name'] ?? '');
                if ($vName === '') {
                    $vName = $valId;
                }
            }
            $approver = ($appId !== '' && isset($appById[$appId])) ? $appById[$appId] : null;
            $aName      = '';
            $aCargo     = '';
            $aMatricula = '';
            $aSeal      = '';
            $aSig       = '';
            if ($appId !== '') {
                if (is_array($approver)) {
                    $aName      = trim((string) ($approver['name'] ?? ''));
                    $aCargo     = trim((string) ($approver['cargo'] ?? ''));
                    $aMatricula = trim((string) ($approver['matricula'] ?? ''));
                    $aSeal      = trim((string) ($approver['seal'] ?? ''));
                    $aSig       = trim((string) ($approver['signature'] ?? ''));
                }
                if ($aName === '') {
                    $aName = $appId;
                }
            }

            return [
                'validator_name'     => $vName,
                'approver_name'      => $aName,
                'approver_cargo'     => $aCargo,
                'approver_matricula' => $aMatricula,
                'approver_seal'      => $aSeal,
                'approver_signature' => $aSig,
            ];
        };

        $grpCandidates = [];
        foreach (array_keys($byName) as $k) {
            if (preg_match('/^lab_val_grp_([a-f0-9]{16})$/', $k, $m) === 1) {
                $grpCandidates[$m[1]] = true;
            }
            if (preg_match('/^lab_app_grp_([a-f0-9]{16})$/', $k, $m) === 1) {
                $grpCandidates[$m[1]] = true;
            }
        }

        if ($grpCandidates !== []) {
            $grpKeyToPadre = [];
            foreach ($gruposPadreOrden as $padre) {
                $padre = trim((string) $padre);
                $key   = self::labGrupoFirmaKey($padre);
                if ($key !== '') {
                    $grpKeyToPadre[$key] = $padre;
                }
            }
            foreach (array_keys($grpCandidates) as $key) {
                if (! isset($grpKeyToPadre[$key])) {
                    $grpKeyToPadre[$key] = 'Área';
                }
            }

            $orderedGrpKeys = [];
            foreach ($gruposPadreOrden as $padre) {
                $key = self::labGrupoFirmaKey(trim((string) $padre));
                if ($key !== '' && isset($grpCandidates[$key]) && ! in_array($key, $orderedGrpKeys, true)) {
                    $orderedGrpKeys[] = $key;
                }
            }
            foreach (array_keys($grpCandidates) as $key) {
                if (! in_array($key, $orderedGrpKeys, true)) {
                    $orderedGrpKeys[] = $key;
                }
            }

            $out = [];
            foreach ($orderedGrpKeys as $grpKey) {
                $valId = trim($byName['lab_val_grp_' . $grpKey] ?? '');
                $appId = trim($byName['lab_app_grp_' . $grpKey] ?? '');
                if ($valId === '' && $appId === '') {
                    continue;
                }
                $out[] = array_merge([
                    'prianacategoria_id' => 0,
                    'prueba_nombre'      => $grpKeyToPadre[$grpKey] ?? 'Área',
                ], $resolveFirmaRow($valId, $appId));
            }

            // En modo «validar por análisis» todas las áreas comparten la misma firma:
            // se muestra una sola vez (sin etiqueta de área) si son idénticas.
            if ($configSvc->getLabValidationMode() === 'analisis') {
                $sinNombre = array_map(
                    static fn (array $row): array => array_merge($row, ['prueba_nombre' => '']),
                    $out
                );
                $consolidado = $this->consolidarLabFirmasSiIguales($sinNombre);
                if (count($consolidado) === 1) {
                    return $consolidado;
                }
            }

            return $this->consolidarLabFirmasSiIguales($out);
        }

        $candidateIds = [];
        foreach (array_keys($byName) as $k) {
            if (preg_match('/^lab_val_pri_(\d+)$/', $k, $m) === 1) {
                $candidateIds[(int) $m[1]] = true;
            }
            if (preg_match('/^lab_app_pri_(\d+)$/', $k, $m) === 1) {
                $candidateIds[(int) $m[1]] = true;
            }
        }
        if ($candidateIds === []) {
            return [];
        }

        $orderedIds = [];
        foreach (array_filter(array_map('intval', explode(',', $pruebasCsv))) as $pid) {
            if ($pid > 0 && isset($candidateIds[$pid])) {
                $orderedIds[] = $pid;
            }
        }
        foreach (array_keys($candidateIds) as $pid) {
            if (! in_array($pid, $orderedIds, true)) {
                $orderedIds[] = $pid;
            }
        }

        $out = [];
        foreach ($orderedIds as $pid) {
            $valId = trim($byName['lab_val_pri_' . $pid] ?? '');
            $appId = trim($byName['lab_app_pri_' . $pid] ?? '');
            if ($valId === '' && $appId === '') {
                continue;
            }
            $pName = $this->registerModel->getPrianacategoriaNombre($pid);
            if ($pName === '') {
                $pName = 'Prueba #' . $pid;
            }
            $out[] = array_merge([
                'prianacategoria_id' => $pid,
                'prueba_nombre'      => $pName,
            ], $resolveFirmaRow($valId, $appId));
        }

        return $this->consolidarLabFirmasSiIguales($out);
    }

    /**
     * @param list<array<string, mixed>> $out
     * @return list<array<string, mixed>>
     */
    protected function consolidarLabFirmasSiIguales(array $out): array
    {
        if (count($out) <= 1) {
            return $out;
        }

        foreach ($out as $row) {
            if (trim((string) ($row['prueba_nombre'] ?? '')) !== '') {
                return $out;
            }
        }

        $firmaKey = static function (array $row): string {
            return implode("\0", [
                (string) ($row['validator_name'] ?? ''),
                (string) ($row['approver_name'] ?? ''),
                (string) ($row['approver_cargo'] ?? ''),
                (string) ($row['approver_matricula'] ?? ''),
                (string) ($row['approver_seal'] ?? ''),
                (string) ($row['approver_signature'] ?? ''),
            ]);
        };
        $ref     = $firmaKey($out[0]);
        $allSame = true;
        foreach ($out as $row) {
            if ($firmaKey($row) !== $ref) {
                $allSame = false;
                break;
            }
        }
        if ($allSame) {
            $first = $out[0];
            $first['prueba_nombre'] = '';

            return [$first];
        }

        return $out;
    }

    /**
     * LayoutPlan determinista para PDF/PRINT (ReportTree → LayoutEngine → Applier).
     *
     * @param array<string, mixed> $reportData
     * @param array<string, mixed> $pdfLayout
     *
     * @return array{plan: \App\Services\ReportLayout\LayoutPlan, tree: \App\Services\ReportLayout\ReportTree, applier: LayoutPlanApplier}
     */
    public function buildReportLayoutContext(array $reportData, array $pdfLayout, ?array $labConfig = null): array
    {
        $labConfig = $labConfig ?? $this->getLabConfig();
        $grupos = is_array($reportData['grupos'] ?? null) ? $reportData['grupos'] : [];

        return (new ReportLayoutPlanService())->buildContext(
            $grupos,
            is_array($reportData['report_pria_tipo_muestra_nombre'] ?? null)
                ? $reportData['report_pria_tipo_muestra_nombre']
                : [],
            is_array($reportData['report_pria_metodo_nombre'] ?? null)
                ? $reportData['report_pria_metodo_nombre']
                : [],
            is_array($reportData['report_pria_refs_consolidada'] ?? null)
                ? $reportData['report_pria_refs_consolidada']
                : [],
            is_array($reportData['report_lab_firmas'] ?? null)
                ? $reportData['report_lab_firmas']
                : [],
            $pdfLayout,
            $labConfig,
        );
    }

    /**
     * HTML del PDF de resultados según la plantilla activa (orden y visibilidad de bloques).
     *
     * @param array<string, mixed> $reportData Retorno de prepareReportData()
     * @param string               $reportEmitidoEn Fecha/hora de generación del PDF (d/m/Y H:i:s)
     */
    public function renderReportPdfHtml(
        array $reportData,
        string $reportUrl,
        string $qrDataUri,
        string $reportEmitidoEn = '',
        ?array $pdfLayoutOverride = null,
    ): string {
        $layoutService = new ReportPdfLayoutService();
        $pdf_layout    = $pdfLayoutOverride ?? $layoutService->getActiveLayoutForRender();
        if ($reportEmitidoEn === '') {
            $reportEmitidoEn = self::formatNowForReport();
        }

        $labConfig = is_array($reportData['lab_config'] ?? null) && $reportData['lab_config'] !== []
            ? $reportData['lab_config']
            : $this->getLabConfig();
        $layoutCtx = $this->buildReportLayoutContext($reportData, $pdf_layout, $labConfig);

        $html = view('registers/report_pdf', [
            'register_info' => $reportData['register_info'],
            'paciente'      => $reportData['paciente'],
            'doctor'        => $reportData['doctor'],
            'grupos'        => $reportData['grupos'],
            'lab_config'    => $labConfig,
            'report_url'    => $reportUrl,
            'qr_data_uri'   => $qrDataUri,
            'pdf_layout'    => $pdf_layout,
            'report_emitido_en' => $reportEmitidoEn,
            'report_pria_tipo_muestra_nombre' => $reportData['report_pria_tipo_muestra_nombre'] ?? [],
            'report_pria_metodo_nombre'       => $reportData['report_pria_metodo_nombre'] ?? [],
            'report_lab_firmas'               => $reportData['report_lab_firmas'] ?? [],
            'report_pria_refs_consolidada'    => $reportData['report_pria_refs_consolidada'] ?? [],
            'report_categorical_heatmap'      => $reportData['report_categorical_heatmap'] ?? [],
            'report_graficar_modo'            => $reportData['report_graficar_modo'] ?? [],
            'report_layout_plan'              => $layoutCtx['plan'],
            'report_layout_applier'           => $layoutCtx['applier'],
        ]);

        return $html;
    }

    /**
     * PDF binario según plantilla PDF activa (motor configurado en config/Pdf).
     *
     * @param array<string, mixed> $reportData
     */
    public function generateReportPdfBinary(
        array $reportData,
        string $reportUrl,
        string $qrDataUri,
        string $reportEmitidoEn = '',
        ?array $pdfLayoutOverride = null,
        ?int $expectedRegistroId = null,
    ): string {
        $registroId = $this->registroIdFromReportData($reportData);
        if ($expectedRegistroId !== null && $expectedRegistroId > 0) {
            if ($registroId !== $expectedRegistroId) {
                $this->clearReportPdfPreviewCache($expectedRegistroId);
            }
            $registroId = $expectedRegistroId;
        }
        $layoutService = new ReportPdfLayoutService();
        $pdfLayout     = $pdfLayoutOverride ?? $layoutService->getActiveLayoutForRender();
        if ($reportEmitidoEn === '') {
            $reportEmitidoEn = self::formatNowForReport();
        }

        $html = $this->getOrBuildReportPdfHtml(
            $registroId,
            $reportData,
            $reportUrl,
            $qrDataUri,
            $reportEmitidoEn,
            $pdfLayout,
        );
        $pageSize = ReportPdfLayoutService::resolveGlobalPageSizeMm($this->getLabConfig());

        return (new PdfService())->generate($html, 'resultados.pdf', $pageSize);
    }

    /**
     * HTML cacheado o generado una sola vez por huella (evita re-render en warm + pdf miss).
     *
     * @param array<string, mixed> $reportData
     */
    public function getOrBuildReportPdfHtml(
        int $registroId,
        array $reportData,
        string $reportUrl,
        string $qrDataUri,
        string $reportEmitidoEn,
        array $pdfLayout,
    ): string {
        $fingerprint = null;
        if ($registroId > 0 && $this->isPdfHtmlCacheEnabled()) {
            $fingerprint = $this->reportPdfPreviewCacheFingerprint(
                $registroId,
                $reportData,
                $reportEmitidoEn,
                $pdfLayout,
            );
            $cachedHtml = $this->reportPdfHtmlCache()->read($registroId, $fingerprint);
            if ($cachedHtml !== null) {
                ReportPipelineMetrics::getInstance()->log('report_pdf_html_cache_hit', 0, [
                    'registro_id' => $registroId,
                ]);

                return $cachedHtml;
            }
        }

        $html = $this->renderReportPdfHtml($reportData, $reportUrl, $qrDataUri, $reportEmitidoEn, $pdfLayout);

        if ($registroId > 0 && $this->isPdfHtmlCacheEnabled() && $fingerprint !== null) {
            $this->reportPdfHtmlCache()->write($registroId, $fingerprint, $html);
        }

        return $html;
    }

    private function isPdfHtmlCacheEnabled(): bool
    {
        return (bool) (config('Pdf')->htmlCacheEnabled ?? true);
    }

    /**
     * @param array<string, mixed> $reportData
     */
    private function registroIdFromReportData(array $reportData): int
    {
        $info = $reportData['register_info'] ?? null;
        if (is_object($info)) {
            return (int) ($info->registro_id ?? 0);
        }
        if (is_array($info)) {
            return (int) ($info['registro_id'] ?? 0);
        }

        return 0;
    }

    private function reportPdfHtmlCache(): \App\Services\Report\ReportPdfHtmlCacheService
    {
        if ($this->reportPdfHtmlCache === null) {
            $this->reportPdfHtmlCache = new \App\Services\Report\ReportPdfHtmlCacheService();
        }

        return $this->reportPdfHtmlCache;
    }

    /**
     * HTML para imprimir desde el navegador (plantilla «impresión» en configuración).
     *
     * @param array<string, mixed> $reportData Retorno de prepareReportData()
     * @param string               $reportEmitidoEn Fecha/hora al abrir la vista de impresión (d/m/Y H:i:s)
     */
    public function renderReportPrintHtml(
        array $reportData,
        string $reportUrl,
        string $qrDataUri,
        int $registroId,
        string $reportEmitidoEn = '',
        bool $layoutReportMode = false,
        ?array $pdfLayoutOverride = null,
    ): string {
        $layoutService = new ReportPdfLayoutService();
        $pdf_layout    = $pdfLayoutOverride ?? $layoutService->getPrintLayoutForRender();
        $templateBindings = $layoutService->getResultTemplateBindingsForReport();
        if ($reportEmitidoEn === '') {
            $reportEmitidoEn = self::formatNowForReport();
        }

        $labConfig = $this->getLabConfig();
        $layoutCtx = $this->buildReportLayoutContext($reportData, $pdf_layout, $labConfig);

        $html = view('registers/report_print', [
            'register_info' => $reportData['register_info'],
            'paciente'      => $reportData['paciente'],
            'doctor'        => $reportData['doctor'],
            'grupos'        => $reportData['grupos'],
            'lab_config'    => $labConfig,
            'report_url'    => $reportUrl,
            'qr_data_uri'   => $qrDataUri,
            'pdf_layout'              => $pdf_layout,
            'result_template_bindings' => $templateBindings,
            'registro_id'   => $registroId,
            'report_emitido_en' => $reportEmitidoEn,
            'layout_report_mode' => $layoutReportMode,
            'report_pria_tipo_muestra_nombre' => $reportData['report_pria_tipo_muestra_nombre'] ?? [],
            'report_pria_metodo_nombre'       => $reportData['report_pria_metodo_nombre'] ?? [],
            'report_lab_firmas'               => $reportData['report_lab_firmas'] ?? [],
            'report_pria_refs_consolidada'    => $reportData['report_pria_refs_consolidada'] ?? [],
            'report_layout_plan'              => $layoutCtx['plan'],
            'report_layout_applier'           => $layoutCtx['applier'],
        ]);

        return self::replaceTotalPagesTokenForBrowser($html);
    }

    /**
     * Sustituye el token de total de páginas por un marcador que el navegador puede rellenar con JS.
     * Usado en viewreport e impresión HTML (no en PdfService, que inyecta el número real).
     */
    public static function replaceTotalPagesTokenForBrowser(string $html): string
    {
        $html = str_replace(
            'data-total="' . self::TOTAL_PAGES_TOKEN . '"',
            'data-total="1"',
            $html,
        );

        return str_replace(self::TOTAL_PAGES_TOKEN, '<span class="pdf-counter-pages"></span>', $html);
    }

    /**
     * Huella para caché de vista previa PDF (inline en viewreport).
     *
     * @param array<string, mixed> $reportData
     * @param array<string, mixed> $pdfLayout
     */
    public function reportPdfPreviewCacheFingerprint(
        int $registroId,
        array $reportData,
        string $emitidoEn,
        array $pdfLayout,
    ): string {
        $parts = [(string) $registroId, $emitidoEn];

        foreach ($reportData['analisis'] ?? [] as $row) {
            if (! is_array($row)) {
                continue;
            }
            $parts[] = trim((string) ($row['name'] ?? '')) . '=' . trim((string) ($row['regvalues'] ?? ''));
        }

        $parts[] = $this->reportPdfDoctorFingerprintPart($reportData['doctor'] ?? null);
        $parts[] = $this->hashPdfLayoutForFingerprint($pdfLayout);
        $parts[] = (string) (config('Pdf')->renderer ?? 'mpdf');
        $parts[] = self::reportPdfEngineCacheRevision();
        $parts[] = self::REPORT_PDF_PREVIEW_CACHE_SALT;
        array_push($parts, ...$this->reportPdfPreviewFingerprintExtraParts($registroId, $reportData));

        return hash('sha256', implode("\n", $parts));
    }

    private static function reportPdfEngineCacheRevision(): string
    {
        return \App\Libraries\Pdf\HtmlMpdfAdapter::CACHE_REVISION;
    }

    /**
     * Huella ligera para cache hit temprano (sin prepareReportData).
     *
     * @param array<string, mixed> $pdfLayout
     */
    public function reportPdfPreviewCacheFingerprintLight(
        int $registroId,
        string $emitidoEn,
        array $pdfLayout,
    ): string {
        $parts = [(string) $registroId, $emitidoEn];

        foreach ($this->registerModel->getInfoAnalisis($registroId) as $row) {
            $parts[] = trim((string) ($row['name'] ?? '')) . '=' . trim((string) ($row['regvalues'] ?? ''));
        }

        $master = $this->registerModel->getInforeport($registroId);
        $doctorId = $master ? (int) ($master->doctor_id ?? 0) : 0;
        $doctor = ($doctorId > 0) ? model(\App\Models\DoctorModel::class)->getInfo($doctorId) : null;
        $parts[] = $this->reportPdfDoctorFingerprintPart($doctor);

        $parts[] = $this->hashPdfLayoutForFingerprint($pdfLayout);
        $parts[] = (string) (config('Pdf')->renderer ?? 'mpdf');
        $parts[] = self::reportPdfEngineCacheRevision();
        $parts[] = self::REPORT_PDF_PREVIEW_CACHE_SALT;
        array_push($parts, ...$this->reportPdfPreviewFingerprintExtraParts($registroId));

        return hash('sha256', implode("\n", $parts));
    }

    /**
     * Fragmentos extra de huella PDF/HTML: pruebas del registro, resumen de grupos y salt HTML.
     *
     * @param array<string, mixed> $reportData
     *
     * @return list<string>
     */
    private function reportPdfPreviewFingerprintExtraParts(int $registroId, array $reportData = []): array
    {
        $parts = [];

        $info = $reportData['register_info'] ?? null;
        if (is_object($info)) {
            $parts[] = 'pruebas=' . trim((string) ($info->pruebas ?? ''));
        } else {
            $refill = $this->registerModel->getInfoRefill($registroId);
            $parts[] = 'pruebas=' . trim((string) ($refill->pruebas ?? ''));
        }

        $grupos = $reportData['grupos'] ?? null;
        if (! is_array($grupos) || $grupos === []) {
            $dataCache = $this->reportDataCacheService();
            $dataFp    = $dataCache->computeFingerprint($registroId);
            $cachedData = $dataCache->read($registroId, $dataFp);
            if (is_array($cachedData)) {
                $grupos = $cachedData['grupos'] ?? [];
            }
        }
        if (is_array($grupos) && $grupos !== []) {
            $parts[] = $this->reportPdfGruposFingerprintPart($grupos);
        }

        $parts[] = \App\Services\Report\ReportPdfHtmlCacheService::salt();

        return $parts;
    }

    /**
     * @param array<string, list<object|array<string, mixed>>> $grupos
     */
    private function reportPdfGruposFingerprintPart(array $grupos): string
    {
        $lines = [];
        foreach ($grupos as $padre => $items) {
            if (! is_array($items)) {
                continue;
            }
            foreach ($items as $it) {
                $o = is_array($it) ? (object) $it : $it;
                $lines[] = trim((string) $padre)
                    . '|' . trim((string) ($o->hijo ?? ''))
                    . '|' . trim((string) ($o->regvalues ?? ''))
                    . '|' . (int) ($o->prianacategoria_id ?? 0);
            }
        }

        if ($lines === []) {
            return 'grupos=empty';
        }

        sort($lines);

        return 'grupos=' . hash('sha256', implode("\n", $lines));
    }

    /**
     * Fragmento de huella con preferencias del doctor que afectan el render del reporte.
     *
     * @param object|array<string,mixed>|null $doctor
     */
    public function reportPdfDoctorFingerprintPart($doctor): string
    {
        helper('registro');
        $labConfig = $this->getLabConfig();
        $doctorId = 0;
        $displayMode = 'clinico';

        if (is_object($doctor)) {
            $doctorId = (int) ($doctor->doctor_id ?? 0);
            $displayMode = trim((string) ($doctor->display_mode ?? 'clinico'));
        } elseif (is_array($doctor)) {
            $doctorId = (int) ($doctor['doctor_id'] ?? 0);
            $displayMode = trim((string) ($doctor['display_mode'] ?? 'clinico'));
        }

        if ($displayMode === '') {
            $displayMode = 'clinico';
        }

        $showInterp = registro_doctor_mostrar_interpretacion_col($doctor, $labConfig) ? '1' : '0';

        return 'doctor_id=' . $doctorId . '|interp=' . $showInterp . '|mode=' . $displayMode;
    }

    /**
     * Fecha fijada del reporte para fingerprint; null si aún no se imprimió/exportó PDF.
     */
    public function reportEmitidoEnForPreviewCache(int $registroId): ?string
    {
        $raw = $this->registerModel->getReporteFechaHoraFijadaMysql($registroId);

        return $raw !== null ? self::formatStoredReporteFechaHora($raw) : null;
    }

    /**
     * @param array<string, mixed> $pdfLayout
     */
    private function hashPdfLayoutForFingerprint(array $pdfLayout): string
    {
        return md5(json_encode($this->sortArrayKeysRecursive($pdfLayout), \JSON_UNESCAPED_UNICODE) ?: '');
    }

    /**
     * @param array<string, mixed> $data
     *
     * @return array<string, mixed>
     */
    private function sortArrayKeysRecursive(array $data): array
    {
        ksort($data);
        foreach ($data as $key => $value) {
            if (is_array($value)) {
                $data[$key] = $this->sortArrayKeysRecursive($value);
            }
        }

        return $data;
    }

    public function readReportPdfPreviewCache(int $registroId, string $fingerprint): ?string
    {
        $path = $this->reportPdfPreviewCachePath($registroId);
        $metaPath = $path . '.meta';

        if (! is_file($path) || ! is_file($metaPath)) {
            return null;
        }

        $stored = trim((string) file_get_contents($metaPath));
        if ($stored === '' || ! hash_equals($fingerprint, $stored)) {
            return null;
        }

        $binary = file_get_contents($path);

        return ($binary !== false && $binary !== '') ? $binary : null;
    }

    public function writeReportPdfPreviewCache(int $registroId, string $fingerprint, string $binary): void
    {
        if ($binary === '') {
            return;
        }

        $dir = $this->reportPdfPreviewCacheDir();
        if (! is_dir($dir)) {
            return;
        }

        $path = $this->reportPdfPreviewCachePath($registroId);
        $tmp  = $path . '.tmp.' . getmypid();

        if (@file_put_contents($tmp, $binary, LOCK_EX) === false) {
            return;
        }

        @file_put_contents($tmp . '.meta', $fingerprint, LOCK_EX);

        @rename($tmp, $path);
        @rename($tmp . '.meta', $path . '.meta');
    }

    public function clearReportPdfPreviewCache(int $registroId): void
    {
        if ($registroId < 1) {
            return;
        }

        $path = $this->reportPdfPreviewCachePath($registroId);
        if (is_file($path)) {
            @unlink($path);
        }
        if (is_file($path . '.meta')) {
            @unlink($path . '.meta');
        }

        $this->reportPdfHtmlCache()->clear($registroId);
    }

    /**
     * URL pública del reporte para QR / PDF (token o portal doctor).
     */
    public function publicReportViewerUrlForQr(int $registroId): string
    {
        $token = $this->registerModel->ensurePublicAccessToken($registroId);
        if ($token !== null && $token !== '') {
            return site_url('resultados/' . $token);
        }

        return site_url('doctor/viewreport/' . $registroId);
    }

    /**
     * Genera y guarda el PDF en caché para viewreport (miss → hit en la siguiente petición).
     */
    public function warmReportPdfPreviewCache(int $registroId): bool
    {
        if ($registroId < 1 || $this->registerModel->isRegistroAnulado($registroId)) {
            return false;
        }

        $data = $this->prepareReportData($registroId);
        if ($data === null || ($data['grupos'] ?? []) === []) {
            return false;
        }

        helper('qr');

        $layoutService = new ReportPdfLayoutService();
        $pdfLayout     = $layoutService->getActiveLayoutForRender();
        $emitidoEn     = $this->lockReportEmitidoEnForPrintOrPdf($registroId);
        $fingerprint   = $this->reportPdfPreviewCacheFingerprint($registroId, $data, $emitidoEn, $pdfLayout);

        if ($this->readReportPdfPreviewCache($registroId, $fingerprint) !== null) {
            return true;
        }

        $reportUrl = $this->publicReportViewerUrlForQr($registroId);
        $qrPx      = ReportPdfLayoutService::qrImagePixelSizeFromLayout($pdfLayout);
        $qrDataUri = qr_base64($reportUrl, $qrPx);
        $pdfBinary = $this->generateReportPdfBinary($data, $reportUrl, $qrDataUri, $emitidoEn, $pdfLayout);

        if ($pdfBinary === '') {
            return false;
        }

        $this->writeReportPdfPreviewCache($registroId, $fingerprint, $pdfBinary);

        return true;
    }

    /**
     * Pre-caché del PDF tras guardar, sin bloquear la respuesta HTTP actual.
     */
    public function scheduleReportPdfPreviewCacheWarm(int $registroId): void
    {
        if ($registroId < 1) {
            return;
        }

        register_shutdown_function(static function () use ($registroId): void {
            try {
                if (function_exists('fastcgi_finish_request')) {
                    @fastcgi_finish_request();
                }
                (new \App\Services\Report\ReportPipelineService())->warmSync($registroId);
            } catch (\Throwable $e) {
                log_message('error', 'scheduleReportPdfPreviewCacheWarm {id}: {msg}', [
                    'id'  => $registroId,
                    'msg' => $e->getMessage(),
                ]);
            }
        });
    }

    public function clearReportPdfPreviewCacheForDoctor(int $doctorId): void
    {
        if ($doctorId < 1) {
            return;
        }

        $rows = $this->registerModel->db->table('registro')
            ->select('registro_id')
            ->where('doctor_id', $doctorId)
            ->get()
            ->getResultArray();

        foreach ($rows as $row) {
            $rid = (int) ($row['registro_id'] ?? 0);
            $this->clearReportPdfPreviewCache($rid);
            $this->clearReportDataCache($rid);
        }
    }

    /**
     * Invalida todos los PDF/HTML cacheados de reportes (p. ej. tras guardar plantilla PDF).
     */
    public function clearAllReportPdfPreviewCaches(): void
    {
        $previewDir = WRITEPATH . 'cache' . DIRECTORY_SEPARATOR . 'report_pdf_preview';
        if (is_dir($previewDir)) {
            foreach (glob($previewDir . DIRECTORY_SEPARATOR . '*') ?: [] as $path) {
                if (is_file($path)) {
                    @unlink($path);
                }
            }
        }

        $htmlDir = WRITEPATH . 'cache' . DIRECTORY_SEPARATOR . 'report_pdf_html';
        if (is_dir($htmlDir)) {
            foreach (glob($htmlDir . DIRECTORY_SEPARATOR . '*') ?: [] as $path) {
                if (is_file($path)) {
                    @unlink($path);
                }
            }
        }
    }

    private function reportPdfPreviewCacheDir(): string
    {
        $dir = WRITEPATH . 'cache' . DIRECTORY_SEPARATOR . 'report_pdf_preview';
        if (! is_dir($dir)) {
            @mkdir($dir, 0755, true);
        }

        return $dir;
    }

    private function reportPdfPreviewCachePath(int $registroId): string
    {
        return $this->reportPdfPreviewCacheDir() . DIRECTORY_SEPARATOR . 'registro_' . $registroId . '.pdf';
    }
}
