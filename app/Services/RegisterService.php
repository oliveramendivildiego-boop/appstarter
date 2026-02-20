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
     * @return int[] ids de poblacion que aplican al paciente
     */
    public function getMatchingPoblacionIds(?string $birthday, ?int $gender): array
    {
        try {
            $poblacionModel = model(PoblacionModel::class);
            $poblaciones    = $poblacionModel->getAll();
        } catch (\Throwable $e) {
            return [3];
        }
        $matching        = [];

        $edadEnDias = null;
        $edadEnMeses = null;
        $edadEnAnios = null;
        if ($birthday) {
            try {
                $fechaNac = new \DateTime($birthday);
                $hoy      = new \DateTime();
                $diff     = $hoy->diff($fechaNac);
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

        return [
            'register_info' => $registerInfo,
            'paciente'      => $paciente,
            'doctor'        => $doctor,
            'grupos'        => $grupos,
            'analisis'      => $analisis,
        ];
    }
}
