<?php

namespace App\Controllers;

use App\Libraries\PdfService;
use App\Models\DoctorModel;
use App\Models\RegisterModel;
use App\Models\DoctorCommissionModel;
use App\Services\RegisterService;

/**
 * Dashboard para doctores: solo ven historial de sus pacientes.
 */
class DoctorHome extends BaseController
{
    protected DoctorModel $doctorModel;
    protected RegisterModel $registerModel;
    protected RegisterService $registerService;
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
        $registrosRecientes = $this->registerModel->getRegistrosByDoctorId($doctorId, $perPage, $offset);
        $totalRegistros = $this->registerModel->countRegistrosByDoctorId($doctorId);
        $totalPages = max(1, (int) ceil($totalRegistros / $perPage));
        
        // Obtener resumen de comisiones solo si el doctor tiene habilitadas las comisiones
        $comisionSummary = null;
        $comisionesRecientes = [];
        
        if ($this->doctorModel->hasColumn('has_commission') && isset($this->doctorInfo->has_commission)) {
            $hasCommission = (int) $this->doctorInfo->has_commission;
            if ($hasCommission == 1) {
                $comisionSummary = $this->commissionModel->getCommissionSummaryByDoctor($doctorId);
                // Obtener comisiones pagadas con detalles completos
                $comisionesRecientes = $this->commissionModel->getPaidCommissionsWithDetails($doctorId, 5);
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

        $registros = $this->registerModel->getRegistrosByPersonAndDoctor($personId, $doctorId, 100);
        $antecedentes = $this->registerModel->getAntecedentesPaciente($personId, 0, 10, $doctorId);
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
            'paciente'        => $paciente,
            'pacienteNombre'  => $pacienteNombre,
            'registros'       => $registros,
            'antecedentes'    => $antecedentes,
            'doctor_info'     => $this->doctorInfo,
            'doctor_portal'   => true,
            'report_url_base' => site_url('doctor/viewreport/'),
            'pdf_url_base'    => site_url('doctor/pdf/'),
            'back_url'        => site_url('doctor/home'),
            'back_label'      => 'Volver al panel',
            'pruebas_paciente'=> $pruebasPaciente,
            'chart_data_url'  => site_url('doctor/expediente_chart/' . $personId),
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

        $serie = $this->registerModel->getSeriePruebaByPersonAndDoctor($personId, $doctorId, $prueba);
        if (empty($serie)) {
            return $this->response->setJSON(['success' => true, 'labels' => [], 'valor' => [], 'minimo' => [], 'maximo' => []]);
        }

        $labels = [];
        $valor = [];
        $minimo = [];
        $maximo = [];

        foreach ($serie as $row) {
            $labels[] = !empty($row['ingreso']) ? date('d/m/Y', strtotime((string) $row['ingreso'])) : '-';
            $valor[] = $this->toFloatOrNull($row['valor'] ?? null);
            $minimo[] = $this->toFloatOrNull($row['minimo'] ?? null);
            $maximo[] = $this->toFloatOrNull($row['maximo'] ?? null);
        }

        return $this->response->setJSON([
            'success' => true,
            'labels'  => $labels,
            'valor'   => $valor,
            'minimo'  => $minimo,
            'maximo'  => $maximo,
        ]);
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

        return view('doctor/viewreport', [
            'register_info'      => $data['register_info'],
            'labotests_namecate' => $id,
            'paciente'           => $data['paciente'],
            'doctor'             => $data['doctor'],
            'analisis'           => $data['analisis'],
            'grupos'             => $data['grupos'],
            'registerModel'      => $this->registerModel,
            'doctor_info'        => $this->doctorInfo,
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
        $labConfig   = $this->registerService->getLabConfig();
        $reportUrl   = site_url('doctor/viewreport/' . $id);
        $qrDataUri   = qr_base64($reportUrl, 100);
        $html = view('registers/report_pdf', [
            'register_info' => $data['register_info'],
            'paciente'      => $data['paciente'],
            'doctor'        => $data['doctor'],
            'grupos'        => $data['grupos'],
            'lab_config'    => $labConfig,
            'report_url'    => $reportUrl,
            'qr_data_uri'   => $qrDataUri,
        ]);

        $pdfService = new PdfService();
        $pacienteNombre = trim(($data['paciente']->first_name ?? '') . '_' . ($data['paciente']->last_name_fa ?? ''));
        $filename = 'Resultados_' . ($pacienteNombre ?: 'paciente') . '_' . $id . '_' . date('Y-m-d') . '.pdf';

        return $this->response
            ->setHeader('Content-Type', 'application/pdf')
            ->setHeader('Content-Disposition', 'attachment; filename="' . $filename . '"')
            ->setBody($pdfService->generate($html, $filename));
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

    public function logout()
    {
        session()->remove('doctor_id');
        session()->remove('user_type');
        return redirect()->to(site_url('login'));
    }
}
