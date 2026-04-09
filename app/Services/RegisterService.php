<?php

namespace App\Services;

use App\Models\AppConfigModel;
use App\Models\PoblacionModel;
use App\Models\RegisterModel;

/**
 * Servicio de registros de análisis.
 * Lógica de negocio: grupos de resultados, tipo paciente, preparación PDF.
 */
class RegisterService
{
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
            return [3];
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
            return [3];
        }
        $result = array_values($matching);
        if (!in_array(3, $result, true)) {
            $result[] = 3;
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

        if ($edad->y < 13) {
            if ($edad->m <= 1) {
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
                $padre = $item->padre ?? '';
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
                ? $this->registerModel->getAnalisisComplejaConFiltros($analisisId, $matchingPoblacionIds, $gender)
                : $this->registerModel->getAnalisisNocompleja($analisisId);

            if (!$valores) {
                continue;
            }
            $valores = is_array($valores) ? $valores : [$valores];

            foreach ($valores as $item) {
                $item  = is_array($item) ? (object) $item : $item;
                $padre = $item->padre ?? '';
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
            foreach ($grupoActual as $it) {
                $sameTest = ((int)($it->prianacategoria_id ?? 0) === $priaId) || (trim((string)($it->hijo ?? '')) === $hijo);
                if ($sameTest) {
                    if ($isCompleja) {
                        $key = (int)($it->es_separador ?? 0) === 1
                            ? 's:' . (int)($it->secanacategoria_id ?? 0)
                            : trim((string)($it->nombre ?? ''));
                    } else {
                        $key = (string)((int)($it->priresultados_id ?? 0));
                    }
                    if ($key !== '') {
                        $resultadoPorPri[$key] = (string)($it->regvalues ?? '');
                    }
                    continue;
                }
                $otrosItems[] = $it;
            }

            $reconstruidos = [];
            foreach ($refs as $ref) {
                $item = (object) $ref;
                if (!$isCompleja) {
                    $item->nombre = $hijo;
                }
                if ($isCompleja) {
                    $key = (int)($ref['es_separador'] ?? 0) === 1
                        ? 's:' . (int)($ref['secanacategoria_id'] ?? 0)
                        : trim((string)($ref['nombre'] ?? ''));
                } else {
                    $key = (string)((int)($ref['priresultados_id'] ?? 0));
                }
                $val = trim((string)($resultadoPorPri[$key] ?? ''));
                $item->regvalues = ((int)($ref['es_separador'] ?? 0) === 1)
                    ? '-'
                    : (($val === '') ? '-' : $val);
                $item->show_reference = true;
                $reconstruidos[] = $item;
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
     * Aplica visibilidad de referencia por regla:
     * mostrar solo si prueba está en mostrar_valores=1 y tiene exactamente 1 valor ingresado.
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
        ];
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
        $dt = new \DateTime($ingresoRaw ?? 'now');
        $registerInfo->ingreso = $dt->format('d/m/Y');

        $master = $this->registerModel->getInforeport($registroId);
        $paciente = $master ? $this->registerModel->getInfoPaciente($master->person_id) : null;
        $doctor   = $master ? $this->registerModel->getInfoDoctor($master->doctor_id) : null;
        $analisis = $this->registerModel->getInfoAnalisis($registroId);

        $paciente = $this->preparePacienteParaReporte($paciente);
        $doctor   = $doctor ?? (object) ['name' => '-', 'gender' => 0];

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
        $enteredCounts = $this->countEnteredValuesByPrianacategoria($analisis);
        $eligiblePriaIds = [];
        $eligiblePriaConfig = [];
        foreach ($priasCfg as $cfg) {
            $pid = (int)($cfg['prianacategoria_id'] ?? 0);
            $mostrar = (int)($cfg['mostrar_valores'] ?? 0) === 1;
            if ($mostrar && (int)($enteredCounts[$pid] ?? 0) === 1) {
                $eligiblePriaIds[] = $pid;
                $eligiblePriaConfig[] = $cfg;
            }
        }
        $grupos = $this->appendMissingReferenceRows($grupos, $registerInfo, $eligiblePriaConfig, $matchingPoblacionIds, $patientGender);
        $grupos = $this->applyReferenceVisibility($grupos, $eligiblePriaIds);

        return [
            'register_info' => $registerInfo,
            'paciente'      => $paciente,
            'doctor'        => $doctor,
            'grupos'        => $grupos,
            'analisis'      => $analisis,
            'report_pria_tipo_muestra_nombre' => $reportPriaTipoMuestraNombre,
            'report_pria_metodo_nombre'       => $reportPriaMetodoNombre,
        ];
    }

    /**
     * HTML del PDF de resultados según la plantilla activa (orden y visibilidad de bloques).
     *
     * @param array<string, mixed> $reportData Retorno de prepareReportData()
     */
    public function renderReportPdfHtml(array $reportData, string $reportUrl, string $qrDataUri): string
    {
        $layoutService = new ReportPdfLayoutService();
        $pdf_layout    = $layoutService->getActiveLayoutForRender();

        return view('registers/report_pdf', [
            'register_info' => $reportData['register_info'],
            'paciente'      => $reportData['paciente'],
            'doctor'        => $reportData['doctor'],
            'grupos'        => $reportData['grupos'],
            'lab_config'    => $this->getLabConfig(),
            'report_url'    => $reportUrl,
            'qr_data_uri'   => $qrDataUri,
            'pdf_layout'    => $pdf_layout,
            'report_pria_tipo_muestra_nombre' => $reportData['report_pria_tipo_muestra_nombre'] ?? [],
            'report_pria_metodo_nombre'       => $reportData['report_pria_metodo_nombre'] ?? [],
        ]);
    }

    /**
     * HTML para imprimir desde el navegador (plantilla «impresión» en configuración).
     *
     * @param array<string, mixed> $reportData Retorno de prepareReportData()
     */
    public function renderReportPrintHtml(array $reportData, string $reportUrl, string $qrDataUri, int $registroId): string
    {
        $layoutService = new ReportPdfLayoutService();
        $pdf_layout    = $layoutService->getPrintLayoutForRender();

        return view('registers/report_print', [
            'register_info' => $reportData['register_info'],
            'paciente'      => $reportData['paciente'],
            'doctor'        => $reportData['doctor'],
            'grupos'        => $reportData['grupos'],
            'lab_config'    => $this->getLabConfig(),
            'report_url'    => $reportUrl,
            'qr_data_uri'   => $qrDataUri,
            'pdf_layout'    => $pdf_layout,
            'registro_id'   => $registroId,
            'report_pria_tipo_muestra_nombre' => $reportData['report_pria_tipo_muestra_nombre'] ?? [],
            'report_pria_metodo_nombre'       => $reportData['report_pria_metodo_nombre'] ?? [],
        ]);
    }
}
