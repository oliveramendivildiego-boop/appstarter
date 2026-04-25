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

        $registros = $this->registerModel->getRegistrosByPersonId($personId, 100);
        $antecedentes = $this->registerModel->getAntecedentesPaciente($personId, 0, 10);
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
            'paciente'        => $paciente,
            'pacienteNombre'  => $pacienteNombre,
            'registros'       => $registros,
            'antecedentes'    => $antecedentes,
            'allowed_modules' => $this->allowed_modules,
            'user_info'       => $this->user_info,
            'current_module'  => 'expediente',
            'pruebas_paciente'=> $pruebasPaciente,
            'chart_data_url'  => site_url('expediente/chart/' . $personId),
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
