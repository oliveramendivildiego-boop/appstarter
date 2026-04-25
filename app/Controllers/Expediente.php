<?php

namespace App\Controllers;

use App\Models\RegisterModel;
use App\Services\DoctorClinicalInsightService;
use App\Services\PatientResultChartService;

/**
 * Historial/Expediente por paciente.
 * Coordina búsqueda y visualización del historial de estudios.
 */
class Expediente extends SecureArea
{
    protected ?string $moduleId = 'registers';

    protected $registerModel;
    protected $chartService;

    public function __construct()
    {
        parent::__construct();
        $this->registerModel = model(RegisterModel::class);
        $this->chartService = new PatientResultChartService(new DoctorClinicalInsightService());
    }

    public function index()
    {
        return view('expediente/search', [
            'allowed_modules'  => $this->allowed_modules,
            'user_info'        => $this->user_info,
            'current_module'   => 'expediente',
        ]);
    }

    /**
     * Historial de un paciente (todos sus registros)
     */
    public function view($personId = 0)
    {
        $personId = (int) $personId;
        if ($personId < 1) {
            return redirect()->to('expediente')->with('error', 'Paciente no válido');
        }

        $paciente = $this->registerModel->getPatientById($personId);
        if (!$paciente) {
            return redirect()->to('expediente')->with('error', 'Paciente no encontrado');
        }

        $perPage = 15;
        $hPage = max(1, (int) $this->request->getGet('h_page'));
        $aPage = max(1, (int) $this->request->getGet('a_page'));

        $totalRegistrosCount = $this->registerModel->countRegistrosByPersonId($personId);
        $hTotalPages = $totalRegistrosCount > 0 ? (int) ceil($totalRegistrosCount / $perPage) : 0;
        if ($hTotalPages > 0) {
            $hPage = min($hPage, $hTotalPages);
        } else {
            $hPage = 1;
        }
        $registros = $this->registerModel->getRegistrosByPersonId($personId, $perPage, ($hPage - 1) * $perPage);

        $totalAntecedentesCount = $this->registerModel->countAntecedentesPaciente($personId, 0, null);
        $aTotalPages = $totalAntecedentesCount > 0 ? (int) ceil($totalAntecedentesCount / $perPage) : 0;
        if ($aTotalPages > 0) {
            $aPage = min($aPage, $aTotalPages);
        } else {
            $aPage = 1;
        }
        $antecedentes = $totalAntecedentesCount > 0
            ? $this->registerModel->getAntecedentesPaciente($personId, 0, $perPage, null, ($aPage - 1) * $perPage)
            : [];
        $pruebasPaciente = $this->registerModel->getPruebasByPersonId($personId);

        $pacienteNombre = trim(($paciente->first_name ?? '') . ' ' . ($paciente->last_name_fa ?? '') . ' ' . ($paciente->last_name_mom ?? ''));
        if ($paciente->birthday ?? null) {
            $fechaNac = new \DateTime($paciente->birthday);
            $hoy = new \DateTime();
            $edad = $fechaNac->diff($hoy);
            $paciente->edad_texto = $edad->y . ' años';
        } else {
            $paciente->edad_texto = '-';
        }

        return view('expediente/historial', [
            'paciente'                 => $paciente,
            'pacienteNombre'           => $pacienteNombre,
            'registros'                => $registros,
            'antecedentes'             => $antecedentes,
            'allowed_modules'          => $this->allowed_modules,
            'user_info'                => $this->user_info,
            'current_module'           => 'expediente',
            'pruebas_paciente'         => $pruebasPaciente,
            'chart_data_url'           => site_url('expediente/chart/' . $personId),
            'total_registros_count'    => $totalRegistrosCount,
            'total_antecedentes_count' => $totalAntecedentesCount,
            'h_page'                   => $hPage,
            'a_page'                   => $aPage,
            'h_total_pages'            => $hTotalPages,
            'a_total_pages'            => $aTotalPages,
            'expediente_per_page'      => $perPage,
            'expediente_pager_base'    => site_url('expediente/view/' . $personId),
        ]);
    }

    /**
     * Datos enriquecidos para gráfica longitudinal del historial interno.
     */
    public function chart($personId = 0)
    {
        $personId = (int) $personId;
        if ($personId < 1) {
            return $this->response->setJSON(['success' => false, 'message' => 'Paciente no válido'])->setStatusCode(400);
        }

        $prueba = trim((string) ($this->request->getGet('prueba') ?? ''));
        if ($prueba === '') {
            return $this->response->setJSON(['success' => false, 'message' => 'Seleccione una prueba'])->setStatusCode(400);
        }

        $series = $this->chartSeriesForRequest($personId, null, $prueba);
        if (empty($series[0]['rows'])) {
            return $this->response->setJSON(['success' => true, 'labels' => [], 'valor' => [], 'minimo' => [], 'maximo' => [], 'series' => []]);
        }

        return $this->response->setJSON($this->chartService->buildPayload(
            $series,
            trim((string) ($this->request->getGet('range') ?? 'all')),
            trim((string) ($this->request->getGet('date_from') ?? '')),
            trim((string) ($this->request->getGet('date_to') ?? ''))
        ));
    }

    /**
     * Búsqueda AJAX de pacientes para autocompletado
     */
    public function search()
    {
        $q = $this->request->getPost('q') ?? $this->request->getPost('paciente') ?? $this->request->getGet('q') ?? '';
        $suggestions = $this->registerModel->searchPacienteForExpediente($q);
        
        // Devolver las sugerencias junto con el nuevo token CSRF regenerado
        return $this->response->setJSON([
            'suggestions' => $suggestions,
            'csrf_token' => csrf_hash(),
            'csrf_token_name' => csrf_token(),
        ]);
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function chartSeriesForRequest(int $personId, ?int $doctorId, string $primaryKey): array
    {
        $compareKey = trim((string) ($this->request->getGet('compare') ?? ''));
        $series = [];
        foreach ([$primaryKey, $compareKey] as $idx => $key) {
            if ($key === '' || ($idx === 1 && $key === $primaryKey)) {
                continue;
            }
            $rows = $doctorId === null
                ? $this->registerModel->getSeriePruebaByPersonId($personId, $key)
                : $this->registerModel->getSeriePruebaByPersonAndDoctor($personId, $doctorId, $key);
            $series[] = [
                'key' => $key,
                'label' => (string) ($rows[0]['label'] ?? $key),
                'rows' => $rows,
            ];
        }

        return $series;
    }
}
