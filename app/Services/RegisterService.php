<?php

namespace App\Services;

use App\Models\AppConfigModel;
use App\Models\PoblacionModel;
use App\Models\RegisterModel;
use App\Services\ConfigService;
use CodeIgniter\I18n\Time;
use Config\App as AppConfig;

/**
 * Servicio de registros de análisis.
 * Lógica de negocio: grupos de resultados, tipo paciente, preparación PDF.
 */
class RegisterService
{
    private const TOTAL_PAGES_TOKEN = '__PDF_TOTAL_PAGES__';
    protected RegisterModel $registerModel;
    protected AppConfigModel $appConfigModel;

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
     * Construye los grupos de análisis para la vista de reporte/PDF
     */
    public function buildGruposParaReporte(int $registroId, array $analisis, array $matchingPoblacionIds = [], ?int $gender = null): array
    {
        $grupos = [];
        foreach ($analisis as $prueba) {
            $name = $prueba['name'] ?? '';
            if (!is_string($name)) {
                continue;
            }
            if (preg_match('/^lab_(val|app)_pri_\d+$/', $name) === 1) {
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
                $item = $this->registerModel->getSecItemByPrianacategoriaYNombre($prianacategoriaId, $nombre, $matchingPoblacionIds, $gender);
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
                $grupos[$padre][] = $item;
                continue;
            }

            if (strpos($name, '_') === false) {
                continue;
            }
            [$tipoAnalisis, $analisisIdStr] = explode('_', $name, 2);
            $analisisId = (int) $analisisIdStr;

            $valores = $tipoAnalisis === 'c'
                // En reporte, si el resultado viene de regvalues c_* debe respetar
                // exactamente la sub-prueba guardada (secanacategoria_id), sin remap
                // por nombre/población para no perder resultados cargados.
                ? $this->registerModel->getAnalisisCompleja($analisisId)
                : $this->registerModel->getAnalisisNocompleja($analisisId);

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
                $grupos[$padre][] = $item;
            }
        }

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

        return $grupos;
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
                $item->show_reference = true;
                $reconstruidos[] = $item;
                unset($itemPorKey[$key]);
            }

            // Mantener cualquier fila realmente cargada por el usuario aunque no
            // esté en la plantilla de referencias filtrada por población.
            foreach ($itemPorKey as $leftover) {
                $reconstruidos[] = $leftover;
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
     * @param array<string, list<object|array<string, mixed>>> $grupos
     * @return array<string, list<object|array<string, mixed>>>
     */
    protected function dropGruposSinValorIngresado(array $grupos): array
    {
        foreach ($grupos as $padre => $items) {
            if (! $this->grupoTieneAlgunValorIngresado($items, true)) {
                unset($grupos[$padre]);
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
        $formulaRow  = $this->registerModel->getFormula($formId);
        $formulaName = $formulaRow->nombre_fun ?? '';
        if (is_string($formulaName) && function_exists($formulaName)) {
            return $formulaName($rawValue, $registroId);
        }
        return $rawValue;
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
        return $paciente;
    }

    /**
     * Obtiene configuración del lab como array
     */
    public function getLabConfig(): array
    {
        $rows = $this->appConfigModel->findAll();
        $config = [];
        foreach ($rows as $row) {
            $config[$row->key] = $row->value;
        }
        return $config;
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

    private static function timezoneIdIsValid(string $tz): bool
    {
        try {
            new \DateTimeZone($tz);

            return true;
        } catch (\Throwable $e) {
            return false;
        }
    }

    /**
     * Fecha/hora “ahora” para el reporte (vista, impresión, PDF) en {@see reportDisplayTimezone()}.
     */
    public static function formatNowForReport(): string
    {
        $tz = self::reportDisplayTimezone();
        try {
            return Time::now($tz)->format('d/m/Y H:i:s');
        } catch (\Throwable $e) {
            return Time::now('UTC')->format('d/m/Y H:i:s');
        }
    }

    /**
     * Formatea un DATETIME guardado en BD (interpretado en la zona del laboratorio) a texto del reporte.
     */
    public static function formatStoredReporteFechaHora(string $mysqlDatetime): string
    {
        $tz = self::reportDisplayTimezone();
        try {
            $tzObj = new \DateTimeZone($tz);
            $dt    = new \DateTimeImmutable($mysqlDatetime, $tzObj);
        } catch (\Throwable $e) {
            return $mysqlDatetime;
        }

        return $dt->format('d/m/Y H:i:s');
    }

    private function mysqlNowForReportTimezone(): string
    {
        $tz = self::reportDisplayTimezone();
        try {
            $tzObj = new \DateTimeZone($tz);
        } catch (\Throwable $e) {
            $tzObj = new \DateTimeZone('UTC');
        }

        return (new \DateTimeImmutable('now', $tzObj))->format('Y-m-d H:i:s');
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
        try {
            return (new \DateTime($dateTimeRaw))->format('d/m/Y H:i:s');
        } catch (\Throwable $e) {
            return '—';
        }
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
     * Prepara datos para el reporte (viewreport / PDF)
     */
    public function prepareReportData(int $registroId): ?array
    {
        $registerInfo = $this->registerModel->getInfoRefill($registroId);
        if (!$registerInfo) {
            return null;
        }
        if ($this->registerModel->isRegistroAnulado($registroId)) {
            return null;
        }

        $ingresoRaw = $registerInfo->ingreso ?? null;
        $tzReport   = self::reportDisplayTimezone();
        try {
            $tzObj = new \DateTimeZone($tzReport);
        } catch (\Throwable $e) {
            $tzObj = new \DateTimeZone('UTC');
        }
        $src = ($ingresoRaw !== null && trim((string) $ingresoRaw) !== '') ? (string) $ingresoRaw : 'now';
        try {
            $dtIngreso = new \DateTimeImmutable($src, $tzObj);
        } catch (\Throwable $e) {
            $dtIngreso = new \DateTimeImmutable('now', $tzObj);
        }
        $registerInfo->recepcion_fecha_hora = $dtIngreso->format('d/m/Y H:i:s');

        $master = $this->registerModel->getInforeport($registroId);
        $paciente = $master ? $this->registerModel->getInfoPaciente($master->person_id) : null;
        $doctorId = $master ? (int) ($master->doctor_id ?? 0) : 0;
        $doctor   = ($doctorId > 0) ? $this->registerModel->getInfoDoctor($doctorId) : null;
        $analisis = $this->registerModel->getInfoAnalisis($registroId);

        $paciente = $this->preparePacienteParaReporte($paciente);
        if ($doctor === null || $doctorId < 1) {
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
        $priasCfg = $this->registerModel->getPrianacategoriaConfigByIds($pruebasIds);
        $reportPriaTipoMuestraNombre = [];
        try {
            $tipoMuestraModel = model(\App\Models\TipoMuestraModel::class);
            foreach ($priasCfg as $cfgRow) {
                $pId = (int) ($cfgRow['prianacategoria_id'] ?? 0);
                $tid = (int) ($cfgRow['tipo_muestra_id'] ?? 0);
                if ($pId < 1 || $tid < 1) {
                    continue;
                }
                $tmRow = $tipoMuestraModel->find($tid);
                if (is_array($tmRow) && (int) ($tmRow['deleted'] ?? 0) === 0) {
                    $nom = trim((string) ($tmRow['nombre'] ?? ''));
                    if ($nom !== '') {
                        $reportPriaTipoMuestraNombre[$pId] = $nom;
                    }
                }
            }
        } catch (\Throwable $e) {
            $reportPriaTipoMuestraNombre = [];
        }
        $reportPriaMetodoNombre = [];
        try {
            $metodoModel = model(\App\Models\MetodoModel::class);
            foreach ($priasCfg as $cfgRow) {
                $pId = (int) ($cfgRow['prianacategoria_id'] ?? 0);
                $mid = (int) ($cfgRow['metodo_id'] ?? 0);
                if ($pId < 1 || $mid < 1) {
                    continue;
                }
                $mRow = $metodoModel->find($mid);
                if (is_array($mRow) && (int) ($mRow['deleted'] ?? 0) === 0) {
                    $nomM = trim((string) ($mRow['nombre'] ?? ''));
                    if ($nomM !== '') {
                        $reportPriaMetodoNombre[$pId] = $nomM;
                    }
                }
            }
        } catch (\Throwable $e) {
            $reportPriaMetodoNombre = [];
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
        $grupos = $this->dropGruposSinValorIngresado($grupos);

        $reportLabFirmas = $this->buildLabFirmasParaReporte($analisis, (string) ($registerInfo->pruebas ?? ''));
        $reportPriaRefsConsolidada = $this->buildReportPriaRefsConsolidada($eligiblePriaConfig);

        return [
            'register_info' => $registerInfo,
            'paciente'      => $paciente,
            'doctor'        => $doctor,
            'grupos'        => $grupos,
            'analisis'      => $analisis,
            'report_pria_tipo_muestra_nombre' => $reportPriaTipoMuestraNombre,
            'report_pria_metodo_nombre'       => $reportPriaMetodoNombre,
            'report_lab_firmas'               => $reportLabFirmas,
            'report_pria_refs_consolidada'    => $reportPriaRefsConsolidada,
        ];
    }

    /**
     * Validación y aprobación por prueba (regvalues lab_val_pri_*, lab_app_pri_* + config).
     *
     * @return list<array{prianacategoria_id:int, prueba_nombre:string, validator_name:string, approver_name:string, approver_cargo:string, approver_matricula:string, approver_seal:string, approver_signature:string}>
     */
    public function buildLabFirmasParaReporte(array $analisisRows, string $pruebasCsv): array
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
            $out[] = [
                'prianacategoria_id'   => $pid,
                'prueba_nombre'        => $pName,
                'validator_name'       => $vName,
                'approver_name'        => $aName,
                'approver_cargo'       => $aCargo,
                'approver_matricula'   => $aMatricula,
                'approver_seal'        => $aSeal,
                'approver_signature'   => $aSig,
            ];
        }

        if (count($out) > 1) {
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
            $ref = $firmaKey($out[0]);
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
        }

        return $out;
    }

    /**
     * HTML del PDF de resultados según la plantilla activa (orden y visibilidad de bloques).
     *
     * @param array<string, mixed> $reportData Retorno de prepareReportData()
     * @param string               $reportEmitidoEn Fecha/hora de generación del PDF (d/m/Y H:i:s)
     */
    public function renderReportPdfHtml(array $reportData, string $reportUrl, string $qrDataUri, string $reportEmitidoEn = ''): string
    {
        $layoutService = new ReportPdfLayoutService();
        $pdf_layout    = $layoutService->getActiveLayoutForRender();
        if ($reportEmitidoEn === '') {
            $reportEmitidoEn = self::formatNowForReport();
        }

        return view('registers/report_pdf', [
            'register_info' => $reportData['register_info'],
            'paciente'      => $reportData['paciente'],
            'doctor'        => $reportData['doctor'],
            'grupos'        => $reportData['grupos'],
            'lab_config'    => $this->getLabConfig(),
            'report_url'    => $reportUrl,
            'qr_data_uri'   => $qrDataUri,
            'pdf_layout'    => $pdf_layout,
            'report_emitido_en' => $reportEmitidoEn,
            'report_pria_tipo_muestra_nombre' => $reportData['report_pria_tipo_muestra_nombre'] ?? [],
            'report_pria_metodo_nombre'       => $reportData['report_pria_metodo_nombre'] ?? [],
            'report_lab_firmas'               => $reportData['report_lab_firmas'] ?? [],
            'report_pria_refs_consolidada'    => $reportData['report_pria_refs_consolidada'] ?? [],
        ]);
    }

    /**
     * HTML para imprimir desde el navegador (plantilla «impresión» en configuración).
     *
     * @param array<string, mixed> $reportData Retorno de prepareReportData()
     * @param string               $reportEmitidoEn Fecha/hora al abrir la vista de impresión (d/m/Y H:i:s)
     */
    public function renderReportPrintHtml(array $reportData, string $reportUrl, string $qrDataUri, int $registroId, string $reportEmitidoEn = ''): string
    {
        $layoutService = new ReportPdfLayoutService();
        $pdf_layout    = $layoutService->getPrintLayoutForRender();
        if ($reportEmitidoEn === '') {
            $reportEmitidoEn = self::formatNowForReport();
        }

        $html = view('registers/report_print', [
            'register_info' => $reportData['register_info'],
            'paciente'      => $reportData['paciente'],
            'doctor'        => $reportData['doctor'],
            'grupos'        => $reportData['grupos'],
            'lab_config'    => $this->getLabConfig(),
            'report_url'    => $reportUrl,
            'qr_data_uri'   => $qrDataUri,
            'pdf_layout'    => $pdf_layout,
            'registro_id'   => $registroId,
            'report_emitido_en' => $reportEmitidoEn,
            'report_pria_tipo_muestra_nombre' => $reportData['report_pria_tipo_muestra_nombre'] ?? [],
            'report_pria_metodo_nombre'       => $reportData['report_pria_metodo_nombre'] ?? [],
            'report_lab_firmas'               => $reportData['report_lab_firmas'] ?? [],
            'report_pria_refs_consolidada'    => $reportData['report_pria_refs_consolidada'] ?? [],
        ]);

        // En impresión directa (HTML + window.print) no se usa PdfService, así que el token
        // de total de páginas debe volver al contador CSS del navegador para no verse literal.
        return str_replace(self::TOTAL_PAGES_TOKEN, '<span class="pdf-counter-pages"></span>', $html);
    }
}
