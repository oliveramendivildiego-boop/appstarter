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
                    $legacyType = $this->computePacienteType((object)['birthday' => $birthday, 'gender' => $gender ?? 0]);
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
     */
    public function computePacienteType(object $registerInfo): int
    {
        $birthday = $registerInfo->birthday ?? null;
        $gender   = (int) ($registerInfo->gender ?? 0);
        if (!$birthday) {
            return 3;
        }

        $fechaNac = new \DateTime($birthday);
        $hoy      = new \DateTime();
        $edad     = $hoy->diff($fechaNac);

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
    public function buildGruposParaReporte(int $registroId, array $analisis): array
    {
        $grupos = [];
        foreach ($analisis as $prueba) {
            $name = $prueba['name'] ?? '';
            if (!is_string($name)) {
                continue;
            }
            $regvalue = $prueba['regvalues'] ?? $prueba['value'] ?? '-';

            // Formato por nombre: "prianacategoria_id|nombre" (ej. 12|Eritrocitos)
            if (strpos($name, '|') !== false) {
                [$prianacategoriaIdStr, $nombre] = explode('|', $name, 2);
                $prianacategoriaId = (int) trim($prianacategoriaIdStr);
                $nombre = trim($nombre);
                if ($prianacategoriaId <= 0 || $nombre === '') {
                    continue;
                }
                $item = $this->registerModel->getSecItemByPrianacategoriaYNombre($prianacategoriaId, $nombre);
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

            // Formato legacy: c_XXX o noc_XXX
            if (strpos($name, '_') === false) {
                continue;
            }
            [$tipoAnalisis, $analisisIdStr] = explode('_', $name, 2);
            $analisisId = (int) $analisisIdStr;

            $valores = $tipoAnalisis === 'c'
                ? $this->registerModel->getAnalisisCompleja($analisisId)
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
        return $grupos;
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
    protected function appendMissingReferenceRows(array $grupos, object $registerInfo, array $eligiblePriaConfig = []): array
    {
        $patientGender = isset($registerInfo->gender) ? (int) $registerInfo->gender : null;
        $pacienteType = isset($registerInfo->paciente) ? (int)$registerInfo->paciente : $this->computePacienteType($registerInfo);

        foreach ($eligiblePriaConfig as $cfg) {
            $priaId = (int)($cfg['prianacategoria_id'] ?? 0);
            if ($priaId < 1) {
                continue;
            }
            $isCompleja = (int)($cfg['compleja'] ?? 0) === 1;

            if ($isCompleja) {
                // Para mostrar referencias en reporte de compuestas, incluir todas las sub-pruebas
                // aunque no tengan resultado cargado.
                $refs = $this->registerModel->getAllSecItemsByPrianacategoriaForReport($priaId);
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
                    $key = $isCompleja
                        ? trim((string)($it->nombre ?? ''))
                        : (string)((int)($it->priresultados_id ?? 0));
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
                $key = $isCompleja
                    ? trim((string)($ref['nombre'] ?? ''))
                    : (string)((int)($ref['priresultados_id'] ?? 0));
                $val = trim((string)($resultadoPorPri[$key] ?? ''));
                $item->regvalues = ($val === '') ? '-' : $val;
                $item->show_reference = true;
                $reconstruidos[] = $item;
            }

            // Primero resultados cargados, luego sin resultado.
            usort($reconstruidos, static function ($a, $b) {
                $aVal = trim((string)($a->regvalues ?? ''));
                $bVal = trim((string)($b->regvalues ?? ''));
                $aEmpty = ($aVal === '' || $aVal === '-');
                $bEmpty = ($bVal === '' || $bVal === '-');
                if ($aEmpty !== $bEmpty) {
                    return $aEmpty ? 1 : -1;
                }
                return ((int)($a->id_poblacion ?? 0)) <=> ((int)($b->id_poblacion ?? 0));
            });

            $grupos[$padre] = array_merge($otrosItems, $reconstruidos);
        }

        foreach ($grupos as $padre => $items) {
            usort($items, static function ($a, $b) {
                $aVal = trim((string)($a->regvalues ?? ''));
                $bVal = trim((string)($b->regvalues ?? ''));
                $aEmpty = ($aVal === '' || $aVal === '-');
                $bEmpty = ($bVal === '' || $bVal === '-');
                if ($aEmpty !== $bEmpty) {
                    return $aEmpty ? 1 : -1;
                }
                return strcmp((string)($a->nombre ?? ''), (string)($b->nombre ?? ''));
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

        $dt = new \DateTime($registerInfo->ingreso ?? 'now');
        $registerInfo->ingreso = $dt->format('d/m/Y');

        $master = $this->registerModel->getInforeport($registroId);
        $paciente = $master ? $this->registerModel->getInfoPaciente($master->person_id) : null;
        $doctor   = $master ? $this->registerModel->getInfoDoctor($master->doctor_id) : null;
        $analisis = $this->registerModel->getInfoAnalisis($registroId);

        $paciente = $this->preparePacienteParaReporte($paciente);
        $doctor   = $doctor ?? (object) ['name' => '-', 'gender' => 0];

        $pacienteType = $this->computePacienteType($registerInfo);
        $registerInfo->paciente = $pacienteType;

        $grupos = $this->buildGruposParaReporte($registroId, $analisis);
        $pruebasIds = $this->extractPrianacategoriaIdsFromRegistroPruebas((string)($registerInfo->pruebas ?? ''));
        $priasCfg = $this->registerModel->getPrianacategoriaConfigByIds($pruebasIds);
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
        $grupos = $this->appendMissingReferenceRows($grupos, $registerInfo, $eligiblePriaConfig);
        $grupos = $this->applyReferenceVisibility($grupos, $eligiblePriaIds);

        return [
            'register_info' => $registerInfo,
            'paciente'      => $paciente,
            'doctor'        => $doctor,
            'grupos'        => $grupos,
            'analisis'      => $analisis,
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
        ]);
    }
}
