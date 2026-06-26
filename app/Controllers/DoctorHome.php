<?php

namespace App\Controllers;

use App\Libraries\PdfService;
use App\Models\DoctorModel;
use App\Models\RegisterModel;
use App\Models\DoctorCommissionModel;
use App\Services\DoctorClinicalInsightService;
use App\Services\PatientResultChartService;
use App\Services\RegisterService;

/**
 * Dashboard para doctores: solo ven historial de sus pacientes.
 */
class DoctorHome extends BaseController
{
    protected DoctorModel $doctorModel;
    protected RegisterModel $registerModel;
    protected RegisterService $registerService;
    protected DoctorClinicalInsightService $clinicalInsightService;
    protected PatientResultChartService $chartService;
    protected DoctorCommissionModel $commissionModel;
    protected ?object $doctorInfo = null;

    public function __construct()
    {
        helper(['url', 'form']);
    }

    public function initController(\CodeIgniter\HTTP\RequestInterface $request, \CodeIgniter\HTTP\ResponseInterface $response, \Psr\Log\LoggerInterface $logger)
    {
        parent::initController($request, $response, $logger);

        if (!session()->has('doctor_id') || !session()->get('doctor_id')) {
            redirect()->to(site_url('login'))->send();
            exit;
        }

        $this->doctorModel = model(DoctorModel::class);
        $this->registerModel = model(RegisterModel::class);
        $this->registerService = new RegisterService($this->registerModel);
        $this->clinicalInsightService = new DoctorClinicalInsightService();
        $this->chartService = new PatientResultChartService($this->clinicalInsightService);
        $this->commissionModel = model(DoctorCommissionModel::class);
        $this->doctorInfo = $this->doctorModel->getInfo((int) session()->get('doctor_id'));
    }

    public function index()
    {
        $doctorId = (int) session()->get('doctor_id');
        $perPage = 10;
        $page = max(1, (int) ($this->request->getGet('page') ?? 1));
        $offset = ($page - 1) * $perPage;

        $pacientes = $this->registerModel->getPacientesByDoctorId($doctorId);
        $clinicalFilters = $this->clinicalSearchFiltersFromRequest();
        $clinicalSearchActive = $this->hasClinicalSearchFilters($clinicalFilters);
        if ($clinicalSearchActive) {
            $registrosRecientes = $this->clinicalSearchResults($doctorId, $clinicalFilters);
            $totalRegistros = count($registrosRecientes);
            $totalPages = 1;
            $page = 1;
        } else {
            $registrosRecientes = $this->registerModel->getRegistrosByDoctorId($doctorId, $perPage, $offset);
            $totalRegistros = $this->registerModel->countRegistrosByDoctorId($doctorId);
            $totalPages = max(1, (int) ceil($totalRegistros / $perPage));
        }
        $clinicalSummaries = $this->summariesForRegisters($registrosRecientes);
        $doctorSummary = $this->buildDoctorDashboardSummary($doctorId);
        
        // Obtener resumen de comisiones solo si el doctor tiene habilitadas las comisiones
        $comisionSummary = null;
        $comisionesRecientes = [];
        $hideCommissionDetails = false;
        
        if ($this->doctorModel->hasColumn('has_commission') && isset($this->doctorInfo->has_commission)) {
            $hasCommission = (int) $this->doctorInfo->has_commission;
            if ($hasCommission == 1) {
                $hideCommissionDetails = $this->doctorModel->hasColumn('hide_commission_details')
                    && (int) ($this->doctorInfo->hide_commission_details ?? 0) === 1;
                $comisionSummary = $this->commissionModel->getCommissionSummaryByDoctor($doctorId);
                if (!$hideCommissionDetails) {
                    // Obtener comisiones pagadas con detalles completos
                    $comisionesRecientes = $this->commissionModel->getPaidCommissionsWithDetails($doctorId, 5);
                }
            }
        }

        return view('doctor/dashboard', [
            'doctor_info'       => $this->doctorInfo,
            'pacientes'         => $pacientes,
            'registros_recientes'=> $registrosRecientes,
            'page'              => $page,
            'perPage'           => $perPage,
            'totalRegistros'    => $totalRegistros,
            'totalPages'        => $totalPages,
            'comision_summary'  => $comisionSummary,
            'comisiones_recientes' => $comisionesRecientes,
            'hide_commission_details' => $hideCommissionDetails,
            'clinical_filters'  => $clinicalFilters,
            'clinical_search_active' => $clinicalSearchActive,
            'clinical_summaries' => $clinicalSummaries,
            'doctor_summary'    => $doctorSummary,
        ]);
    }

    /**
     * Expediente de un paciente (solo si el doctor tiene registros de ese paciente)
     */
    public function expediente(int $personId = 0)
    {
        $doctorId = (int) session()->get('doctor_id');
        if ($personId < 1) {
            return redirect()->to(site_url('doctor/home'))->with('error', 'Paciente no válido');
        }

        $paciente = $this->registerModel->getPatientById($personId);
        if (!$paciente) {
            return redirect()->to(site_url('doctor/home'))->with('error', 'Paciente no encontrado');
        }

        $perPage = 15;
        $hPage = max(1, (int) $this->request->getGet('h_page'));
        $aPage = max(1, (int) $this->request->getGet('a_page'));

        $totalRegistrosCount = $this->registerModel->countRegistrosByPersonAndDoctor($personId, $doctorId);
        $hTotalPages = $totalRegistrosCount > 0 ? (int) ceil($totalRegistrosCount / $perPage) : 0;
        if ($hTotalPages > 0) {
            $hPage = min($hPage, $hTotalPages);
        } else {
            $hPage = 1;
        }
        $registros = $this->registerModel->getRegistrosByPersonAndDoctor($personId, $doctorId, $perPage, ($hPage - 1) * $perPage);

        $totalAntecedentesCount = $this->registerModel->countAntecedentesPaciente($personId, 0, $doctorId);
        $aTotalPages = $totalAntecedentesCount > 0 ? (int) ceil($totalAntecedentesCount / $perPage) : 0;
        if ($aTotalPages > 0) {
            $aPage = min($aPage, $aTotalPages);
        } else {
            $aPage = 1;
        }
        $antecedentes = $totalAntecedentesCount > 0
            ? $this->registerModel->getAntecedentesPaciente($personId, 0, $perPage, $doctorId, ($aPage - 1) * $perPage)
            : [];
        $pruebasPaciente = $this->registerModel->getPruebasByPersonAndDoctor($personId, $doctorId);

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
            'doctor_info'              => $this->doctorInfo,
            'doctor_portal'            => true,
            'report_url_base'          => site_url('doctor/viewreport/'),
            'pdf_url_base'             => site_url('doctor/pdf/'),
            'back_url'                 => site_url('doctor/home'),
            'back_label'               => 'Volver al panel',
            'pruebas_paciente'         => $pruebasPaciente,
            'chart_data_url'           => site_url('doctor/expediente_chart/' . $personId),
            'total_registros_count'    => $totalRegistrosCount,
            'total_antecedentes_count' => $totalAntecedentesCount,
            'h_page'                   => $hPage,
            'a_page'                   => $aPage,
            'h_total_pages'            => $hTotalPages,
            'a_total_pages'            => $aTotalPages,
            'expediente_per_page'      => $perPage,
            'expediente_pager_base'    => site_url('doctor/expediente/' . $personId),
        ]);
    }

    /**
     * Datos para gráfico de evolución por prueba (paciente del doctor).
     */
    public function expedienteChart(int $personId = 0)
    {
        $doctorId = (int) session()->get('doctor_id');
        if ($personId < 1) {
            return $this->response->setJSON(['success' => false, 'message' => 'Paciente no válido'])->setStatusCode(400);
        }

        $prueba = trim((string) ($this->request->getGet('prueba') ?? ''));
        if ($prueba === '') {
            return $this->response->setJSON(['success' => false, 'message' => 'Seleccione una prueba'])->setStatusCode(400);
        }

        $series = $this->chartSeriesForRequest($personId, $doctorId, $prueba);
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
     * @return list<array<string, mixed>>
     */
    private function chartSeriesForRequest(int $personId, int $doctorId, string $primaryKey): array
    {
        $compareKey = trim((string) ($this->request->getGet('compare') ?? ''));
        $series = [];
        foreach ([$primaryKey, $compareKey] as $idx => $key) {
            if ($key === '' || ($idx === 1 && $key === $primaryKey)) {
                continue;
            }
            $rows = $this->registerModel->getSeriePruebaByPersonAndDoctor($personId, $doctorId, $key);
            $series[] = [
                'key' => $key,
                'label' => (string) ($rows[0]['label'] ?? $key),
                'rows' => $rows,
            ];
        }

        return $series;
    }

    private function toFloatOrNull($raw): ?float
    {
        if ($raw === null) return null;
        $str = trim((string) $raw);
        if ($str === '') return null;
        $str = str_replace(',', '.', $str);
        if (!is_numeric($str)) return null;
        return (float) $str;
    }

    /**
     * Reporte de resultados para doctor (solo de sus propios registros).
     */
    public function viewreport(int $id = 0)
    {
        $doctorId = (int) session()->get('doctor_id');
        if ($id < 1) {
            return redirect()->to(site_url('doctor/home'))->with('error', 'Registro no válido');
        }

        $master = $this->registerModel->getInforeport($id);
        if (!$master || (int) ($master->doctor_id ?? 0) !== $doctorId) {
            return redirect()->to(site_url('doctor/home'))->with('error', 'No tiene acceso a ese reporte');
        }
        if ($this->registerModel->isRegistroAnulado($id)) {
            return redirect()->to(site_url('doctor/home'))->with('error', 'Esta orden fue anulada y no está disponible.');
        }

        $data = $this->registerService->prepareReportData($id);
        if (!$data) {
            return redirect()->to(site_url('doctor/home'))->with('error', 'Registro no encontrado');
        }

        $publicToken = $this->registerModel->ensurePublicAccessToken($id);

        return view('doctor/viewreport', [
            'register_info'      => $data['register_info'],
            'labotests_namecate' => $id,
            'paciente'           => $data['paciente'],
            'doctor'             => $data['doctor'],
            'analisis'           => $data['analisis'],
            'grupos'             => $data['grupos'],
            'report_pria_tipo_muestra_nombre' => $data['report_pria_tipo_muestra_nombre'] ?? [],
            'report_pria_metodo_nombre'       => $data['report_pria_metodo_nombre'] ?? [],
            'report_lab_firmas'               => $data['report_lab_firmas'] ?? [],
            'report_pria_refs_consolidada'    => $data['report_pria_refs_consolidada'] ?? [],
            'registerModel'      => $this->registerModel,
            'doctor_info'        => $this->doctorInfo,
            'report_emitido_en'  => $this->registerService->reportEmitidoEnForView($id),
            'public_resultados_token' => $publicToken,
            'clinical_summary'   => $this->clinicalInsightService->summarizeReport($data['grupos'], $this->clinicalContextFromRegister($data['register_info'])),
        ]);
    }

    /**
     * Endpoint JSON de historial longitudinal por paciente del doctor.
     */
    public function history(int $personId = 0)
    {
        $doctorId = (int) session()->get('doctor_id');
        if ($personId < 1) {
            return $this->response->setJSON(['success' => false, 'message' => 'Paciente no válido'])->setStatusCode(400);
        }

        $paciente = $this->registerModel->getPatientById($personId);
        if (!$paciente) {
            return $this->response->setJSON(['success' => false, 'message' => 'Paciente no encontrado'])->setStatusCode(404);
        }

        $registros = $this->registerModel->getRegistrosByPersonAndDoctor($personId, $doctorId, 100);
        $history = [];
        foreach ($registros as $registro) {
            $registroId = (int) ($registro->registro_id ?? 0);
            $data = $registroId > 0 ? $this->registerService->prepareReportData($registroId) : null;
            $summary = $data ? $this->clinicalInsightService->summarizeReport($data['grupos'], $this->clinicalContextFromRegister($data['register_info'])) : null;
            $history[] = [
                'registro_id' => $registroId,
                'ingreso' => $registro->ingreso ?? null,
                'paciente' => $registro->paciente ?? '',
                'summary' => $summary,
            ];
        }

        return $this->response->setJSON([
            'success' => true,
            'patient' => [
                'person_id' => $personId,
                'name' => trim(($paciente->first_name ?? '') . ' ' . ($paciente->last_name_fa ?? '') . ' ' . ($paciente->last_name_mom ?? '')),
            ],
            'history' => $history,
        ]);
    }

    /**
     * PDF de resultados para doctor (solo de sus propios registros).
     */
    public function pdf(int $id = 0)
    {
        $doctorId = (int) session()->get('doctor_id');
        if ($id < 1) {
            return redirect()->to(site_url('doctor/home'))->with('error', 'Registro no válido');
        }

        $master = $this->registerModel->getInforeport($id);
        if (!$master || (int) ($master->doctor_id ?? 0) !== $doctorId) {
            return redirect()->to(site_url('doctor/home'))->with('error', 'No tiene acceso a ese PDF');
        }
        if ($this->registerModel->isRegistroAnulado($id)) {
            return redirect()->to(site_url('doctor/home'))->with('error', 'Esta orden fue anulada y no está disponible.');
        }

        $data = $this->registerService->prepareReportData($id);
        if (!$data) {
            return redirect()->to(site_url('doctor/home'))->with('error', 'Registro no encontrado');
        }

        helper('qr');
        $token     = $this->registerModel->ensurePublicAccessToken($id);
        $reportUrl = ($token !== null && $token !== '')
            ? site_url('resultados/' . $token)
            : site_url('doctor/viewreport/' . $id);
        $qrLayout  = (new \App\Services\ReportPdfLayoutService())->getActiveLayoutForRender();
        $qrPx      = \App\Services\ReportPdfLayoutService::qrImagePixelSizeFromLayout($qrLayout);
        $qrDataUri = qr_base64($reportUrl, $qrPx);
        $emitidoEn = $this->registerService->lockReportEmitidoEnForPrintOrPdf($id);
        $pacienteNombre = trim(($data['paciente']->first_name ?? '') . '_' . ($data['paciente']->last_name_fa ?? ''));
        $filename = 'Resultados_' . ($pacienteNombre ?: 'paciente') . '_' . $id . '_' . lab_filename_date() . '.pdf';
        $pdfBinary = $this->registerService->generateReportPdfBinary($data, $reportUrl, $qrDataUri, $emitidoEn, $qrLayout);

        return $this->response
            ->setHeader('Content-Type', 'application/pdf')
            ->setHeader('Content-Disposition', 'attachment; filename="' . $filename . '"')
            ->setBody($pdfBinary);
    }

    /**
     * Búsqueda de pacientes (solo los del doctor)
     */
    public function search()
    {
        $q = $this->request->getPost('q') ?? $this->request->getPost('paciente') ?? $this->request->getGet('q') ?? '';
        $doctorId = (int) session()->get('doctor_id');
        $suggestions = $this->registerModel->searchPacienteForDoctor($q, $doctorId);
        return $this->response->setJSON($suggestions);
    }

    /**
     * @return array<string, mixed>
     */
    private function clinicalSearchFiltersFromRequest(): array
    {
        $query = trim((string) ($this->request->getGet('clinical_query') ?? ''));
        $parsedQuery = $this->parseClinicalQuery($query);

        return [
            'q' => trim((string) ($this->request->getGet('q') ?? '')),
            'date_from' => trim((string) ($this->request->getGet('date_from') ?? '')),
            'date_to' => trim((string) ($this->request->getGet('date_to') ?? '')),
            'only_altered' => (int) ($this->request->getGet('only_altered') ?? 0) === 1,
            'clinical_query' => $query,
            'parsed_query' => $parsedQuery,
        ];
    }

    /**
     * @param array<string, mixed> $filters
     */
    private function hasClinicalSearchFilters(array $filters): bool
    {
        return trim((string) ($filters['q'] ?? '')) !== ''
            || trim((string) ($filters['date_from'] ?? '')) !== ''
            || trim((string) ($filters['date_to'] ?? '')) !== ''
            || !empty($filters['only_altered'])
            || trim((string) ($filters['clinical_query'] ?? '')) !== '';
    }

    /**
     * @param array<string, mixed> $filters
     * @return list<object>
     */
    private function clinicalSearchResults(int $doctorId, array $filters): array
    {
        $base = $this->registerModel->getRegistrosByDoctorAdvanced($doctorId, $filters, 150, 0);
        $out = [];
        $parsedQuery = $filters['parsed_query'] ?? null;
        foreach ($base as $registro) {
            $registroId = (int) ($registro->registro_id ?? 0);
            if ($registroId < 1) {
                continue;
            }
            $data = $this->registerService->prepareReportData($registroId);
            if (!$data) {
                continue;
            }
            $summary = $this->clinicalInsightService->summarizeReport($data['grupos'], $this->clinicalContextFromRegister($data['register_info']));
            if (!empty($filters['only_altered']) && (int) ($summary['altered_count'] ?? 0) < 1 && (int) ($summary['critical_count'] ?? 0) < 1) {
                continue;
            }
            if (is_array($parsedQuery) && !$this->reportMatchesClinicalQuery($data['grupos'], $parsedQuery)) {
                continue;
            }
            $registro->clinical_summary = $summary;
            $out[] = $registro;
            if (count($out) >= 50) {
                break;
            }
        }

        return $out;
    }

    /**
     * @param list<object> $registros
     * @return array<int, array<string, mixed>>
     */
    private function summariesForRegisters(array $registros): array
    {
        $summaries = [];
        foreach ($registros as $registro) {
            $registroId = (int) ($registro->registro_id ?? 0);
            if ($registroId < 1) {
                continue;
            }
            if (isset($registro->clinical_summary) && is_array($registro->clinical_summary)) {
                $summaries[$registroId] = $registro->clinical_summary;
                continue;
            }
            $data = $this->registerService->prepareReportData($registroId);
            if ($data) {
                $summaries[$registroId] = $this->clinicalInsightService->summarizeReport($data['grupos'], $this->clinicalContextFromRegister($data['register_info']));
            }
        }

        return $summaries;
    }

    /**
     * @return array<string, mixed>
     */
    private function buildDoctorDashboardSummary(int $doctorId): array
    {
        $recent = $this->registerModel->getRegistrosByDoctorAdvanced($doctorId, [], 25, 0);
        $altered = 0;
        $critical = 0;
        $top = [];
        foreach ($recent as $registro) {
            $registroId = (int) ($registro->registro_id ?? 0);
            $data = $registroId > 0 ? $this->registerService->prepareReportData($registroId) : null;
            if (!$data) {
                continue;
            }
            $summary = $this->clinicalInsightService->summarizeReport($data['grupos'], $this->clinicalContextFromRegister($data['register_info']));
            $altered += (int) ($summary['altered_count'] ?? 0);
            $critical += (int) ($summary['critical_count'] ?? 0);
            foreach (($summary['top_alterations'] ?? []) as $alt) {
                $alt['registro_id'] = $registroId;
                $alt['paciente'] = (string) ($registro->paciente ?? '');
                $top[] = $alt;
            }
        }
        usort($top, static function (array $a, array $b): int {
            return ((float) ($b['score'] ?? 0)) <=> ((float) ($a['score'] ?? 0));
        });
        $status = $critical > 0 ? 'Crítico' : ($altered > 0 ? 'Riesgo' : 'Normal');

        return [
            'sample_size' => count($recent),
            'altered_count' => $altered,
            'critical_count' => $critical,
            'status' => $status,
            'status_class' => $status === 'Crítico' ? 'danger' : ($status === 'Riesgo' ? 'warning' : 'success'),
            'top_alterations' => array_slice($top, 0, 5),
            'interpretation' => $critical > 0
                ? 'Hay resultados críticos recientes que requieren priorización.'
                : ($altered > 0 ? 'Hay alteraciones recientes para revisar en contexto clínico.' : 'No se detectan alteraciones recientes en las órdenes evaluadas.'),
        ];
    }

    /**
     * @return array<string, string>
     */
    private function clinicalContextFromRegister(object $registerInfo): array
    {
        return [
            'diagnostico_presuntivo' => (string) ($registerInfo->diagnostico_presuntivo ?? $registerInfo->diagnostico ?? ''),
            'motivo_estudio' => (string) ($registerInfo->motivo_estudio ?? $registerInfo->motivo ?? $registerInfo->comentario_resultado ?? ''),
        ];
    }

    /**
     * @return array{parameter:string,operator:string,value:float}|null
     */
    private function parseClinicalQuery(string $query): ?array
    {
        if (!preg_match('/^\s*(.+?)\s*(>=|<=|=|>|<)\s*(-?\d+(?:[\.,]\d+)?)\s*$/u', $query, $m)) {
            return null;
        }

        return [
            'parameter' => mb_strtolower(trim($m[1])),
            'operator' => $m[2],
            'value' => (float) str_replace(',', '.', $m[3]),
        ];
    }

    /**
     * @param array<string, list<object>> $grupos
     * @param array{parameter:string,operator:string,value:float} $query
     */
    private function reportMatchesClinicalQuery(array $grupos, array $query): bool
    {
        foreach ($grupos as $items) {
            foreach ($items as $item) {
                $row = is_array($item) ? (object) $item : $item;
                $label = mb_strtolower(trim((string) ($row->nombre ?? $row->hijo ?? '')));
                if ($label === '' || mb_strpos($label, $query['parameter']) === false) {
                    continue;
                }
                $value = $this->toFloatOrNull($row->regvalues ?? null);
                if ($value === null && trim((string) ($row->regvalues ?? '')) !== '') {
                    $value = $this->toFloatOrNull(preg_replace('/[^0-9,\.\-]/', '', (string) $row->regvalues));
                }
                if ($value === null) {
                    continue;
                }
                if ($this->compareClinicalValue($value, $query['operator'], (float) $query['value'])) {
                    return true;
                }
            }
        }

        return false;
    }

    private function compareClinicalValue(float $left, string $operator, float $right): bool
    {
        if ($operator === '>') return $left > $right;
        if ($operator === '<') return $left < $right;
        if ($operator === '>=') return $left >= $right;
        if ($operator === '<=') return $left <= $right;
        return abs($left - $right) < 0.0001;
    }

    public function logout()
    {
        session()->remove('doctor_id');
        session()->remove('user_type');
        return redirect()->to(site_url('login'));
    }
}
