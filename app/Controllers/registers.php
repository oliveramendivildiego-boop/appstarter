<?php

namespace App\Controllers;

use App\Libraries\PdfService;
use App\Services\BillingDocumentService;
use App\Services\RegisterService;
use App\Services\WhatsAppService;
use App\Models\LabotestModel;
use App\Models\RegisterModel;
use App\Models\PerfilExamenModel;
use App\Models\MuestraModel;
use App\Models\AppConfigModel;
use App\Models\DoctorModel;
use App\Models\DoctorCommissionModel;
use App\Models\LeyendaModel;
use CodeIgniter\HTTP\ResponseInterface;

/**
 * Registros de análisis de laboratorio.
 * Coordina request → service/model → response.
 */
class Registers extends SecureArea
{
    protected ?string $moduleId = 'registers';

    protected RegisterModel $registerModel;
    protected LabotestModel $labotestModel;
    protected RegisterService $registerService;
    protected AppConfigModel $configModel;
    protected DoctorModel $doctorModel;
    protected DoctorCommissionModel $commissionModel;

    public function __construct()
    {
        parent::__construct();
        helper('table');
        $this->registerModel   = model(RegisterModel::class);
        $this->labotestModel   = model(LabotestModel::class);
        $this->registerService = new RegisterService();
        $this->configModel     = model(AppConfigModel::class);
        $this->doctorModel     = model(DoctorModel::class);
        $this->commissionModel = model(DoctorCommissionModel::class);
    }

    public function index()
    {
        $categories = $this->labotestModel->getGroupedByCategory();
        $perfiles = (model(PerfilExamenModel::class))->getAll();

        return view('registers/manage', [
            'current_module'  => 'registers',
            'categories'      => $categories,
            'perfiles'        => $perfiles ?? [],
            'allowed_modules' => $this->allowed_modules,
            'user_info'       => $this->user_info,
            'controller_name' => 'registers',
            'edit_registro'   => null,
            'edit_pago'       => null,
        ]);
    }

    /**
     * Edita un registro existente desde la pantalla de "crear orden" (agregar/quitar pruebas, ajustar costos).
     */
    public function edit($id = -1)
    {
        $id = (int) $id;
        if ($id < 1) {
            return redirect()->to('registers/lista')->with('error', 'Registro no válido');
        }

        if (!$this->registerModel->existsRegistro($id)) {
            return redirect()->to('registers/lista')->with('error', 'Registro no encontrado');
        }
        if ($this->registerModel->isRegistroAnulado($id)) {
            return redirect()->to('registers/anulada/' . $id);
        }

        $categories = $this->labotestModel->getGroupedByCategory();
        $perfiles = (model(PerfilExamenModel::class))->getAll();
        $info = $this->registerModel->getInfoRefill($id);
        $pago = $this->registerModel->getPagoByRegistroId($id);

        return view('registers/manage', [
            'current_module'  => 'registers',
            'categories'      => $categories,
            'perfiles'        => $perfiles ?? [],
            'allowed_modules' => $this->allowed_modules,
            'user_info'       => $this->user_info,
            'controller_name' => 'registers',
            'edit_registro'   => $info,
            'edit_pago'       => $pago,
        ]);
    }

    public function lista()
    {
        $perPage    = 15;
        $page       = max(1, (int) ($this->request->getGet('page') ?? 1));
        $offset     = ($page - 1) * $perPage;
        $search     = trim((string) ($this->request->getGet('q') ?? ''));
        $estado = trim((string) ($this->request->getGet('estado') ?? ''));
        if (!in_array($estado, ['completo', 'incompleto', 'anulado', 'activo', ''], true)) {
            $estado = '';
        }

        if ($search !== '') {
            $registros  = $this->registerModel->getAllAnalisisWithSearch($search, $perPage, $offset, $estado);
            $total      = $this->registerModel->countWithSearch($search, $estado);
        } else {
            $registros  = $this->registerModel->getAllAnalisis($perPage, $offset, $estado);
            $total      = $this->registerModel->countAll($estado);
        }

        $manageTable = $this->buildRegistrosTable($registros);
        $totalPages  = $total > 0 ? (int) ceil($total / $perPage) : 1;

        return view('registers/lista', [
            'manage_table'     => $manageTable,
            'allowed_modules'  => $this->allowed_modules,
            'user_info'        => $this->user_info,
            'current_module'   => 'registers',
            'controller_name'  => 'registers',
            'page'            => $page,
            'totalPages'      => $totalPages,
            'total'           => $total,
            'perPage'         => $perPage,
            'search'          => $search,
            'estado'          => $estado,
        ]);
    }

    /**
     * Consulta solo lectura de una orden anulada (pruebas, motivo, pagos e insumos registrados).
     */
    public function anulada($id = -1)
    {
        $id = (int) $id;
        if ($id < 1) {
            return redirect()->to('registers/lista')->with('error', 'Registro no válido');
        }
        if (!$this->registerModel->existsRegistro($id)) {
            return redirect()->to('registers/lista')->with('error', 'Registro no encontrado');
        }
        if (!$this->registerModel->isRegistroAnulado($id)) {
            return redirect()->to('registers/view/' . $id);
        }
        $hist = $this->registerModel->getHistorialRegistro($id);
        if (!$hist) {
            return redirect()->to('registers/lista')->with('error', 'Registro no encontrado');
        }
        $info = $this->registerModel->getInfoRefill($id);
        $nombreAnulo = '';
        if ($info && !empty($info->person_id_anulo)) {
            $nombreAnulo = $this->registerModel->getPersonShortDisplay((int) $info->person_id_anulo);
        }
        $insumos = model(\App\Models\ReactivoModel::class)->getInsumosPorRegistro($id);

        return view('registers/anulada', [
            'current_module'  => 'registers',
            'controller_name' => 'registers',
            'allowed_modules' => $this->allowed_modules,
            'user_info'       => $this->user_info,
            'historial'       => $hist,
            'info'            => $info,
            'nombre_anulo'    => $nombreAnulo,
            'insumos'         => $insumos,
        ]);
    }

    private static function getTipoPagoLabel(string $tipopago): string
    {
        $map = ['1' => 'Efectivo', '2' => 'QR', '3' => 'Transferencia', '4' => 'Pendiente'];
        return $map[trim($tipopago)] ?? trim($tipopago) ?: '-';
    }

    private function bloquearSiRegistroAnuladoJson(int $id): ?ResponseInterface
    {
        if ($this->registerModel->isRegistroAnulado($id)) {
            return $this->response->setJSON([
                'success' => false,
                'message' => 'Esta orden fue anulada y no puede modificarse.',
            ])->setStatusCode(403);
        }

        return null;
    }

    private function buildRegistrosTable(array $registros): string
    {
        $html = '<div class="table-responsive"><table class="table table-bordered table-striped"><thead><tr>';
        $html .= '<th>Código</th><th>Paciente</th><th>Doctor</th><th>Total</th><th>Tipo pago</th><th>Monto pagado</th><th>A cuenta</th><th>Saldo</th><th class="text-center">Acciones</th>';
        $html .= '</tr></thead><tbody>';

        $sumTotal = 0;
        $sumMontoPagado = 0;
        $sumAcuenta = 0;
        $sumSaldo = 0;

        foreach ($registros as $r) {
            $rid = (int) ($r->registro_id ?? 0);
            $totalNum = (float) ($r->total ?? 0);
            $saldoNum = (float) ($r->saldo ?? 0);
            $montoPagadoNum = (float) ($r->monto_pagar ?? 0);
            $acuenta = $totalNum - $saldoNum;
            $acuentaStr = $totalNum > 0 || $saldoNum !== 0.0 ? number_format($acuenta, 2) : '';
            $montoPagadoStr = $acuentaStr ?: number_format($montoPagadoNum, 2);
            $isAnulado = isset($r->anulado) && (int) $r->anulado === 1;

            if (!$isAnulado) {
                $sumTotal += $totalNum;
                $sumMontoPagado += $acuenta;
                $sumAcuenta += $acuenta;
                $sumSaldo += $saldoNum;
            }

            $html .= $isAnulado ? '<tr class="table-secondary">' : '<tr>';
            $ordenDisp = registro_orden_display($r);
            $html .= '<td title="ID interno: ' . esc((string) $rid) . '">' . esc($ordenDisp);
            if ($isAnulado) {
                $html .= ' <span class="badge bg-dark ms-1">Anulada</span>';
            }
            $html .= '</td>';
            $html .= '<td>' . esc($r->paciente ?? '') . '</td>';
            $html .= '<td>' . esc($r->doctor ?? '') . '</td>';
            $html .= '<td>' . esc($r->total ?? '') . '</td>';
            $html .= '<td>' . esc(self::getTipoPagoLabel($r->tipopago ?? '')) . '</td>';
            $html .= '<td>' . esc($montoPagadoStr) . '</td>';
            $html .= '<td>' . esc($acuentaStr) . '</td>';
            $html .= '<td>' . esc($r->saldo ?? '') . '</td>';
            $html .= '<td class="text-center">';
            if ($isAnulado) {
                $html .= '<a href="' . site_url('registers/anulada/' . $rid) . '" class="btn btn-sm btn-outline-secondary" title="Ver orden anulada (solo lectura)"><i class="fa-solid fa-lock me-1"></i>Ver</a>';
            } else {
                $hasRegvalues = isset($r->regvalues_count) && (int) $r->regvalues_count > 0;
                $btnTitle = $hasRegvalues ? 'Editar' : 'Agregar';
                $btnIcon = $hasRegvalues ? 'fa-pen' : 'fa-plus';
                $html .= '<a href="' . site_url('registers/view/' . $rid) . '" class="btn btn-sm btn-outline-primary" title="' . esc($btnTitle) . '"><i class="fa-solid ' . esc($btnIcon) . '"></i></a> ';
                if (!$hasRegvalues) {
                    $html .= '<a href="' . site_url('registers/edit/' . $rid) . '" class="btn btn-sm btn-outline-success" title="Editar prueba (orden)"><i class="fa-solid fa-flask"></i></a> ';
                    $html .= '<a href="' . site_url('registers/orden/' . $rid) . '" class="btn btn-sm btn-outline-secondary" title="Imprimir orden"><i class="fa-solid fa-print"></i></a> ';
                }
                if ($hasRegvalues) {
                    $html .= '<a href="' . site_url('registers/viewreport/' . $rid) . '" class="btn btn-sm btn-secondary" title="Reporte">Reporte</a> ';
                    $html .= '<a href="' . site_url('registers/pdf/' . $rid) . '" class="btn btn-sm btn-success" target="_blank" title="PDF">PDF</a> ';
                    $pacientePhone = trim($r->paciente_phone ?? '');
                    $doctorPhone   = trim($r->doctor_phone ?? '');
                    $html .= '<button type="button" class="btn btn-sm btn-success btn-whatsapp-pdf" data-id="' . $rid . '" data-paciente="' . esc($r->paciente ?? '') . '" data-doctor="' . esc($r->doctor ?? '') . '" data-paciente-phone="' . esc($pacientePhone) . '" data-doctor-phone="' . esc($doctorPhone) . '" data-ingreso="' . esc($r->ingreso ?? '') . '" title="Enviar PDF por WhatsApp"><i class="fa-brands fa-whatsapp"></i></button> ';
                }
                if ($saldoNum > 0) {
                    $html .= '<button type="button" class="btn btn-sm btn-outline-warning btn-agregar-pago" data-id="' . $rid . '" data-total="' . esc($r->total ?? '') . '" data-saldo="' . esc($r->saldo ?? '') . '" data-monto="' . esc($r->monto_pagar ?? '') . '" title="Agregar pago"><i class="fa-solid fa-money-bill-wave"></i> Pago</button> ';
                }
                $html .= '<button type="button" class="btn btn-sm btn-outline-info btn-historial" data-id="' . $rid . '" title="Historial de pagos y pruebas"><i class="fa-solid fa-clock-rotate-left"></i></button> ';
                $html .= '<button type="button" class="btn btn-sm btn-outline-danger btn-anular-registro" data-id="' . $rid . '" title="Anular orden (no borra de la base de datos)"><i class="fa-solid fa-ban"></i></button>';
            }
            $html .= '</td>';
            $html .= '</tr>';
        }
        if (empty($registros)) {
            $html .= '<tr><td colspan="9">No hay registros.</td></tr>';
        } else {
            $html .= '<tr class="table-secondary fw-bold"><td colspan="3">Total</td>';
            $html .= '<td>' . number_format($sumTotal, 2) . '</td>';
            $html .= '<td>—</td>';
            $html .= '<td>' . number_format($sumMontoPagado, 2) . '</td>';
            $html .= '<td>' . number_format($sumAcuenta, 2) . '</td>';
            $html .= '<td>' . number_format($sumSaldo, 2) . '</td>';
            $html .= '<td></td></tr>';
        }
        $html .= '</tbody></table></div>';
        return $html;
    }

    public function searchPaciente(): ResponseInterface
    {
        $search = $this->request->getPost('paciente') ?? $this->request->getPost('q') ?? '';
        $data   = $this->registerModel->searchPaciente($search);
        return $this->response->setJSON($data);
    }

    public function searchDoctor(): ResponseInterface
    {
        $search = $this->request->getPost('doctor') ?? $this->request->getPost('q') ?? '';
        $data   = $this->registerModel->searchDoctor($search);
        return $this->response->setJSON($data);
    }

    public function searchPrueba(): ResponseInterface
    {
        $search = $this->request->getPost('prueba') ?? $this->request->getPost('q') ?? '';
        $data   = $this->registerModel->searchPrueba($search);
        return $this->response->setJSON($data);
    }

    public function view($id = -1)
    {
        $id = (int) $id;
        if ($id < 1) {
            return redirect()->to('registers')->with('error', 'Registro no válido');
        }

        $registerInfo = $this->registerModel->getInfoRefill($id);
        if (!$registerInfo) {
            return redirect()->to('registers')->with('error', 'Registro no encontrado');
        }
        if ($this->registerModel->isRegistroAnulado($id)) {
            return redirect()->to('registers/anulada/' . $id);
        }

        $refIngreso = $registerInfo->ingreso ?? null;
        $pacienteType = $this->registerService->computePacienteType($registerInfo, $refIngreso);
        $registerInfo->paciente = $pacienteType;
        $patientGender = isset($registerInfo->gender) ? (int) $registerInfo->gender : null;
        $matchingPoblacionIds = $this->registerService->getMatchingPoblacionIds($registerInfo->birthday ?? null, $patientGender, $refIngreso);
        $pruebasInfo = $this->registerModel->getPruebasInput($registerInfo->pruebas ?? '', $matchingPoblacionIds, $patientGender);

        $muestraModel = model(MuestraModel::class);
        $muestra = $muestraModel->getByRegistro($id);
        $tiposMuestra = $muestraModel->getTiposMuestra();

        $decimalesSugerencia = (int) (model(AppConfigModel::class)->getValue('decimales_sugerencia') ?: 2);
        $decimalesSugerencia = max(0, min(10, $decimalesSugerencia));

        $analisis = $this->registerModel->getInfoAnalisis($id);

        return view('registers/formfill', [
            'current_module' => 'registers',
            'muestra' => $muestra,
            'tipos_muestra' => $tiposMuestra,
            'controller_name'   => 'registers',
            'register_info'     => $registerInfo,
            'pruebas_info'      => $pruebasInfo,
            'matching_poblacion_ids' => $matchingPoblacionIds,
            'analisis'          => $analisis,
            'labotests_namecate' => $id,
            'registerModel'     => $this->registerModel,
            'leyendas_activas'  => ($this->configModel->getValue('leyendas_enabled') === '1') ? model(LeyendaModel::class)->where('activo', 1)->where('deleted', 0)->orderBy('titulo', 'ASC')->findAll() : [],
            'leyendas_enabled'  => ($this->configModel->getValue('leyendas_enabled') === '1'),
            'decimales_sugerencia' => $decimalesSugerencia,
            'allowed_modules'   => $this->allowed_modules,
            'user_info'         => $this->user_info,
        ]);
    }

    public function viewreport($id = -1)
    {
        $id = (int) $id;
        if ($id < 1) {
            return redirect()->to('registers')->with('error', 'Registro no válido');
        }
        if ($this->registerModel->isRegistroAnulado($id)) {
            return redirect()->to('registers/anulada/' . $id);
        }

        $data = $this->registerService->prepareReportData($id);
        
        if (!$data) {
            return redirect()->to('registers')->with('error', 'Registro no encontrado');
        }

        $pago = $this->registerModel->getPagoByRegistroId($id);
        $billingService = new BillingDocumentService();
        $pagoCompleto   = $this->registerModel->isPagoCompletoPorRegistroId($id);

        return view('registers/viewreport', [
            'current_module'    => 'registers',
            'controller_name'  => 'registers',
            'register_info'     => $data['register_info'],
            'labotests_namecate' => $id,
            'paciente'          => $data['paciente'],
            'doctor'            => $data['doctor'],
            'analisis'          => $data['analisis'],
            'grupos'            => $data['grupos'],
            'report_pria_tipo_muestra_nombre' => $data['report_pria_tipo_muestra_nombre'] ?? [],
            'registerModel'     => $this->registerModel,
            'allowed_modules'   => $this->allowed_modules,
            'user_info'         => $this->user_info,
            'sin_billing_enabled' => $billingService->isSinBillingEnabled(),
            'comprobante_pdf_disponible' => $pago !== null && $pagoCompleto,
            'comprobante_pdf_pendiente_pago' => $pago !== null && !$pagoCompleto,
            'comprobante_pdf_sin_registro_pago' => $pago === null,
        ]);
    }

    /**
     * Vista lista para imprimir con la plantilla configurada para impresión (no la del PDF).
     */
    public function printreport($id = -1)
    {
        $id = (int) $id;
        if ($id < 1) {
            return redirect()->to('registers')->with('error', 'Registro no válido');
        }
        if ($this->registerModel->isRegistroAnulado($id)) {
            return redirect()->to('registers/lista')->with('error', 'La orden está anulada.');
        }

        $data = $this->registerService->prepareReportData($id);
        if (! $data) {
            return redirect()->to('registers')->with('error', 'Registro no encontrado');
        }

        helper('qr');
        $reportUrl = site_url('doctor/viewreport/' . $id);
        $qrDataUri = qr_base64($reportUrl, 100);
        $html      = $this->registerService->renderReportPrintHtml($data, $reportUrl, $qrDataUri, $id);

        return $this->response->setBody($html)->setContentType('text/html', 'UTF-8');
    }

    /**
     * Orden de trabajo (sin logo/QR): lista de pruebas a realizar.
     * Pensada para imprimir cuando la orden aún no tiene resultados cargados.
     */
    public function orden($id = -1)
    {
        $id = (int) $id;
        if ($id < 1) {
            return redirect()->to('registers/lista')->with('error', 'Registro no válido');
        }

        $registerInfo = $this->registerModel->getInfoRefill($id);
        if (!$registerInfo) {
            return redirect()->to('registers/lista')->with('error', 'Registro no encontrado');
        }
        if ($this->registerModel->isRegistroAnulado($id)) {
            return redirect()->to('registers/lista')->with('error', 'La orden está anulada; no se puede imprimir la orden de trabajo.');
        }

        $refIngreso = $registerInfo->ingreso ?? null;
        $pacienteType = $this->registerService->computePacienteType($registerInfo, $refIngreso);
        $registerInfo->paciente = $pacienteType;
        $patientGender = isset($registerInfo->gender) ? (int) $registerInfo->gender : null;
        $matchingPoblacionIds = $this->registerService->getMatchingPoblacionIds($registerInfo->birthday ?? null, $patientGender, $refIngreso);
        $pruebasInfo = $this->registerModel->getPruebasInput($registerInfo->pruebas ?? '', $matchingPoblacionIds, $patientGender);

        // Agrupar por "padre" para mostrar solo listado de pruebas
        $gruposPruebas = [];
        foreach ($pruebasInfo as $p) {
            $padre = trim((string)($p['padre'] ?? ''));
            if ($padre === '') $padre = 'Pruebas';
            $gruposPruebas[$padre][] = $p;
        }

        // Formato fecha similar al reporte
        try {
            $dt = new \DateTime($registerInfo->ingreso ?? 'now');
            $fecha = $dt->format('d/m/Y H:i');
        } catch (\Throwable $e) {
            $fecha = (string)($registerInfo->ingreso ?? '');
        }

        return view('registers/orden', [
            'current_module'    => 'registers',
            'controller_name'   => 'registers',
            'register_info'     => $registerInfo,
            'fecha'             => $fecha,
            'labotests_namecate' => $id,
            'grupos_pruebas'    => $gruposPruebas,
            'show_order_barcode' => ($this->configModel->getValue('show_order_barcode') !== '0'),
            'allowed_modules'   => $this->allowed_modules,
            'user_info'         => $this->user_info,
        ]);
    }

    /**
     * PDF de la orden de trabajo (sin logo/QR).
     */
    public function ordenPdf($id = -1)
    {
        $id = (int) $id;
        if ($id < 1) {
            return redirect()->to('registers/lista')->with('error', 'Registro no válido');
        }

        $registerInfo = $this->registerModel->getInfoRefill($id);
        if (!$registerInfo) {
            return redirect()->to('registers/lista')->with('error', 'Registro no encontrado');
        }
        if ($this->registerModel->isRegistroAnulado($id)) {
            return redirect()->to('registers/lista')->with('error', 'La orden está anulada; no se puede generar el PDF de orden.');
        }

        $refIngreso = $registerInfo->ingreso ?? null;
        $pacienteType = $this->registerService->computePacienteType($registerInfo, $refIngreso);
        $registerInfo->paciente = $pacienteType;
        $patientGender = isset($registerInfo->gender) ? (int) $registerInfo->gender : null;
        $matchingPoblacionIds = $this->registerService->getMatchingPoblacionIds($registerInfo->birthday ?? null, $patientGender, $refIngreso);
        $pruebasInfo = $this->registerModel->getPruebasInput($registerInfo->pruebas ?? '', $matchingPoblacionIds, $patientGender);

        $gruposPruebas = [];
        foreach ($pruebasInfo as $p) {
            $padre = trim((string)($p['padre'] ?? ''));
            if ($padre === '') $padre = 'Pruebas';
            $gruposPruebas[$padre][] = $p;
        }

        try {
            $dt = new \DateTime($registerInfo->ingreso ?? 'now');
            $fecha = $dt->format('d/m/Y H:i');
        } catch (\Throwable $e) {
            $fecha = (string)($registerInfo->ingreso ?? '');
        }

        $html = view('registers/orden_pdf', [
            'register_info'  => $registerInfo,
            'fecha'          => $fecha,
            'grupos_pruebas' => $gruposPruebas,
            'registro_id'    => $id,
        ]);

        $pdfService = new PdfService();
        $pacienteNombre = trim(($registerInfo->first_name ?? '') . '_' . ($registerInfo->last_name_fa ?? ''));
        $filename = 'Orden_' . ($pacienteNombre ?: 'paciente') . '_' . $id . '_' . date('Y-m-d') . '.pdf';

        return $this->response
            ->setHeader('Content-Type', 'application/pdf')
            ->setHeader('Content-Disposition', 'attachment; filename="' . $filename . '"')
            ->setBody($pdfService->generate($html, $filename));
    }

    /**
     * Muestra datos de la orden y todos los insumos consumidos.
     * Usado desde reactivos/lotes cuando se hace clic en el enlace de orden.
     */
    public function insumos($id = -1)
    {
        $id = (int) $id;
        if ($id < 1) {
            return redirect()->to('registers')->with('error', 'Registro no válido');
        }
        if ($this->registerModel->isRegistroAnulado($id)) {
            return redirect()->to('registers/anulada/' . $id);
        }

        $data = $this->registerService->prepareReportData($id);
        if (!$data) {
            return redirect()->to('registers')->with('error', 'Registro no encontrado');
        }

        $reactivoModel = model(\App\Models\ReactivoModel::class);
        $insumos = $reactivoModel->getInsumosPorRegistro($id);

        $pago = $this->registerModel->getPagoByRegistroId($id);

        return view('registers/insumos_orden', [
            'current_module'    => 'registers',
            'controller_name'   => 'registers',
            'register_info'     => $data['register_info'],
            'paciente'          => $data['paciente'],
            'doctor'            => $data['doctor'],
            'insumos'           => $insumos,
            'pago'              => $pago,
            'allowed_modules'   => $this->allowed_modules,
            'user_info'         => $this->user_info,
        ]);
    }

    public function pdf($id = -1)
    {
        $id = (int) $id;
        if ($id < 1) {
            return redirect()->to('registers')->with('error', 'Registro no válido');
        }
        if ($this->registerModel->isRegistroAnulado($id)) {
            return redirect()->to('registers/lista')->with('error', 'La orden está anulada; no se puede generar el PDF de resultados.');
        }

        $data = $this->registerService->prepareReportData($id);
        if (!$data) {
            return redirect()->to('registers')->with('error', 'Registro no encontrado');
        }

        helper('qr');
        $reportUrl = site_url('doctor/viewreport/' . $id);
        $qrDataUri = qr_base64($reportUrl, 100);
        $html      = $this->registerService->renderReportPdfHtml($data, $reportUrl, $qrDataUri);

        $pdfService    = new PdfService();
        $pacienteNombre = trim(($data['paciente']->first_name ?? '') . '_' . ($data['paciente']->last_name_fa ?? ''));
        $filename      = 'Resultados_' . ($pacienteNombre ?: 'paciente') . '_' . $id . '_' . date('Y-m-d') . '.pdf';

        return $this->response
            ->setHeader('Content-Type', 'application/pdf')
            ->setHeader('Content-Disposition', 'attachment; filename="' . $filename . '"')
            ->setBody($pdfService->generate($html, $filename));
    }

    /**
     * PDF de recibo (SIN deshabilitado) o factura de respaldo (SIN habilitado), según configuración.
     */
    public function comprobantePdf($id = -1)
    {
        $id = (int) $id;
        if ($id < 1) {
            return redirect()->to('registers')->with('error', 'Registro no válido');
        }
        if ($this->registerModel->isRegistroAnulado($id)) {
            return redirect()->to('registers/lista')->with('error', 'La orden está anulada; no se puede generar el comprobante.');
        }

        if (!$this->registerModel->isPagoCompletoPorRegistroId($id)) {
            return redirect()->to('registers/viewreport/' . $id)->with(
                'error',
                'El recibo o factura en PDF solo está disponible cuando la orden está totalmente pagada (sin saldo pendiente).'
            );
        }

        $billing = new BillingDocumentService();
        $factura = $billing->isSinBillingEnabled();
        $doc     = $billing->buildComprobante($id, $factura);
        if ($doc === null) {
            return redirect()->to('registers/viewreport/' . $id)->with('error', 'No hay datos de pago para esta orden; no se puede generar el comprobante.');
        }

        $html       = $billing->renderComprobanteHtml($doc, $factura);
        $pdfService = new PdfService();
        $tipo       = $factura ? 'Factura' : 'Recibo';
        $filename   = $tipo . '_orden_' . preg_replace('/[^A-Za-z0-9._-]+/', '_', $doc->ordenNumero) . '_' . date('Y-m-d') . '.pdf';

        return $this->response
            ->setHeader('Content-Type', 'application/pdf')
            ->setHeader('Content-Disposition', 'attachment; filename="' . $filename . '"')
            ->setBody($pdfService->generate($html, $filename));
    }

    public function save(): ResponseInterface
    {
        try {
            $validation = \Config\Services::validation();
            $validation->setRules(config('Validation')->registro ?? []);
            if (!$validation->withRequest($this->request)->run()) {
                return $this->response->setJSON([
                    'success' => false,
                    'message' => implode(' ', $validation->getErrors()),
                ])->setStatusCode(400);
            }

            $registro = $this->request->getPost('registro');
            $pagos    = $this->request->getPost('pagos');

            $registroData = [
                'person_id'  => $registro['person_id'] ?? null,
                'doctor_id'  => $registro['doctor_id'] ?? null,
                'pruebas'    => $registro['pruebas'] ?? null,
                'prioridad'  => (int) ($registro['prioridad'] ?? 0),
                'id_session' => session()->get('person_id'),
            ];

            $registroId = $this->registerModel->saveRegistro($registroData);
            \App\Models\AuditoriaModel::log('registers', 'crear', (string) $registroId, \App\Models\AuditoriaModel::detail([
                'paciente_id' => $registro['person_id'] ?? null,
                'doctor_id' => $registro['doctor_id'] ?? null,
                'pruebas' => $registro['pruebas'] ?? null,
                'prioridad' => (int)($registro['prioridad'] ?? 0),
                'total' => $pagos['total'] ?? null,
                'monto_pagar' => $pagos['monto_pagar'] ?? null,
            ], 'Nuevo registro de orden'));

            $pagosData = [
                'registro_id'  => $registroId,
                'total_reco'   => $pagos['total_reco'] ?? null,
                'total'        => $pagos['total'] ?? null,
                'monto_pagar'  => $pagos['monto_pagar'] ?? null,
                'tipopago'     => $pagos['tipopago'] ?? null,
                'saldo'        => $pagos['saldo'] ?? null,
                'comentarios'  => $pagos['comentarios'] ?? null,
            ];
            $this->registerModel->savePago($pagosData);
            $montoInicial = (float) ($pagos['monto_pagar'] ?? 0);
            if ($montoInicial > 0) {
                $this->registerModel->insertAbonoInicial($registroId, $montoInicial, trim($pagos['tipopago'] ?? '1'));
            }

            // Crear comisión para el doctor si aplica
            $doctorId = (int) ($registro['doctor_id'] ?? 0);
            if ($doctorId > 0) {
                $doctorInfo = $this->doctorModel->find($doctorId);
                if ($doctorInfo && $this->doctorModel->supportsCommissionColumn() && isset($doctorInfo->commission_percent)) {
                    // Verificar si el doctor tiene habilitado el uso de comisiones
                    $hasCommission = isset($doctorInfo->has_commission) ? (int) $doctorInfo->has_commission : 1; // Por defecto 1 para compatibilidad
                    if ($hasCommission == 1) {
                        $commissionPercent = (float) $doctorInfo->commission_percent;
                        if ($commissionPercent > 0) {
                            $totalAmount = (float) ($pagos['total'] ?? 0);
                            if ($totalAmount > 0) {
                                $this->commissionModel->createCommission($doctorId, $registroId, $totalAmount, $commissionPercent);
                            }
                        }
                    }
                }
            }

            return $this->response->setJSON([
                'success' => true,
                'message' => 'Datos guardados correctamente',
                'id'      => $registroId,
            ]);
        } catch (\Throwable $e) {
            log_message('error', 'Registers::save ' . $e->getMessage());
            return $this->response->setJSON([
                'success' => false,
                'message' => 'Error al guardar. Intente nuevamente.',
            ])->setStatusCode(500);
        }
    }

    /**
     * Actualiza pruebas/costos de un registro existente desde la pantalla de "crear orden".
     */
    public function update($id = -1): ResponseInterface
    {
        $id = (int) $id;
        if ($id < 1) {
            return $this->response->setJSON(['success' => false, 'message' => 'ID inválido'])->setStatusCode(400);
        }
        if (!$this->registerModel->existsRegistro($id)) {
            return $this->response->setJSON(['success' => false, 'message' => 'Registro no encontrado'])->setStatusCode(404);
        }
        $blocked = $this->bloquearSiRegistroAnuladoJson($id);
        if ($blocked !== null) {
            return $blocked;
        }

        try {
            $validation = \Config\Services::validation();
            $validation->setRules(config('Validation')->registro ?? []);
            if (!$validation->withRequest($this->request)->run()) {
                return $this->response->setJSON([
                    'success' => false,
                    'message' => implode(' ', $validation->getErrors()),
                ])->setStatusCode(400);
            }

            $registro = $this->request->getPost('registro');
            $pagos    = $this->request->getPost('pagos');

            $registroData = [
                'person_id'  => $registro['person_id'] ?? null,
                'doctor_id'  => $registro['doctor_id'] ?? null,
                'pruebas'    => $registro['pruebas'] ?? null,
                'prioridad'  => (int) ($registro['prioridad'] ?? 0),
                'id_session' => session()->get('person_id'),
            ];
            $this->registerModel->saveRegistro($registroData, $id);
            \App\Models\AuditoriaModel::log('registers', 'editar_orden', (string) $id, \App\Models\AuditoriaModel::detail([
                'paciente_id' => $registro['person_id'] ?? null,
                'doctor_id' => $registro['doctor_id'] ?? null,
                'pruebas' => $registro['pruebas'] ?? null,
                'prioridad' => (int)($registro['prioridad'] ?? 0),
                'total' => $pagos['total'] ?? null,
                'monto_pagar' => $pagos['monto_pagar'] ?? null,
            ], 'Edición de orden existente'));

            $pagosUpd = [
                'total_reco'  => $pagos['total_reco'] ?? null,
                'total'       => $pagos['total'] ?? null,
                'monto_pagar' => $pagos['monto_pagar'] ?? null,
                'tipopago'    => $pagos['tipopago'] ?? null,
                'saldo'       => $pagos['saldo'] ?? null,
                'comentarios' => $pagos['comentarios'] ?? null,
            ];
            $this->registerModel->updatePagoByRegistroId($id, $pagosUpd);

            return $this->response->setJSON([
                'success' => true,
                'message' => 'Registro actualizado',
                'id'      => $id,
            ]);
        } catch (\Throwable $e) {
            log_message('error', 'Registers::update ' . $e->getMessage());
            return $this->response->setJSON([
                'success' => false,
                'message' => 'Error al actualizar. Intente nuevamente.',
            ])->setStatusCode(500);
        }
    }

    public function delete($id): ResponseInterface
    {
        $id = (int) $id;
        if ($id < 1) {
            return $this->response->setJSON(['success' => false, 'message' => 'ID inválido'])->setStatusCode(400);
        }
        if (!$this->registerModel->existsRegistro($id)) {
            return $this->response->setJSON(['success' => false, 'message' => 'Registro no encontrado'])->setStatusCode(404);
        }
        if ($this->registerModel->isRegistroAnulado($id)) {
            return $this->response->setJSON(['success' => false, 'message' => 'Esta orden ya está anulada.'])->setStatusCode(400);
        }
        $motivo = trim((string) ($this->request->getPost('motivo_anulacion') ?? $this->request->getPost('motivo') ?? ''));
        if (mb_strlen($motivo) < 5) {
            return $this->response->setJSON(['success' => false, 'message' => 'Indique el motivo de anulación (mínimo 5 caracteres).'])->setStatusCode(400);
        }
        try {
            $infoBefore = $this->registerModel->getInfoRefill($id);
            $pacienteLabel = $infoBefore ? trim(($infoBefore->first_name ?? '') . ' ' . ($infoBefore->last_name_fa ?? '')) : '';
            $personId = session()->get('person_id') ? (int) session()->get('person_id') : null;
            if (!$this->registerModel->anularRegistro($id, $motivo, $personId)) {
                return $this->response->setJSON(['success' => false, 'message' => 'No se pudo anular. Verifique la migración de base de datos o el motivo indicado.'])->setStatusCode(500);
            }
            \App\Models\AuditoriaModel::log('registers', 'anular', (string) $id, \App\Models\AuditoriaModel::detail([
                'paciente' => $pacienteLabel,
                'motivo'   => $motivo,
            ], 'Orden anulada (registro conservado)'));

            return $this->response->setJSON(['success' => true, 'message' => 'Orden anulada correctamente']);
        } catch (\Throwable $e) {
            log_message('error', 'Registers::delete ' . $e->getMessage());
            return $this->response->setJSON(['success' => false, 'message' => 'Error al anular'])->setStatusCode(500);
        }
    }

    public function historial($id): ResponseInterface
    {
        $id = (int) $id;
        if ($id < 1) {
            return $this->response->setJSON(['success' => false, 'message' => 'ID inválido'])->setStatusCode(400);
        }
        $data = $this->registerModel->getHistorialRegistro($id);
        if (!$data) {
            return $this->response->setJSON(['success' => false, 'message' => 'Registro no encontrado'])->setStatusCode(404);
        }
        $billing = new BillingDocumentService();
        $out     = [
            'registro' => $data['registro'] ? (array) $data['registro'] : (object) [],
            'pago' => $data['pago'] ? (array) $data['pago'] : (object) [],
            'tipo_pago_nombre' => $data['tipo_pago_nombre'] ?? '',
            'abonos' => $data['abonos'] ?? [],
            'pruebas' => $data['pruebas'] ?? [],
            'tiene_resultados' => $data['tiene_resultados'] ?? false,
            'regvalues_count' => $data['regvalues_count'] ?? 0,
            'pago_completo' => $this->registerModel->isPagoCompletoPorRegistroId($id),
            'sin_billing_enabled' => $billing->isSinBillingEnabled(),
        ];
        return $this->response->setJSON(['success' => true, 'data' => $out]);
    }

    public function addpago($id): ResponseInterface
    {
        $id = (int) $id;
        if ($id < 1) {
            return $this->response->setJSON(['success' => false, 'message' => 'ID inválido'])->setStatusCode(400);
        }
        $pago = $this->registerModel->getPagoByRegistroId($id);
        if (!$pago) {
            return $this->response->setJSON(['success' => false, 'message' => 'Registro de pago no encontrado'])->setStatusCode(404);
        }
        $blocked = $this->bloquearSiRegistroAnuladoJson($id);
        if ($blocked !== null) {
            return $blocked;
        }
        $montoAgregar = (float) ($this->request->getPost('monto_pagar') ?? 0);
        $tipopago     = trim((string) ($this->request->getPost('tipopago') ?? '1'));
        if ($montoAgregar <= 0) {
            return $this->response->setJSON(['success' => false, 'message' => 'El monto a agregar debe ser mayor a 0'])->setStatusCode(400);
        }

        $ok = $this->registerModel->insertAbono($id, $montoAgregar, $tipopago);
        if ($ok) {
            \App\Models\AuditoriaModel::log('registers', 'agregar_pago', (string) $id, \App\Models\AuditoriaModel::detail([
                'monto' => $montoAgregar,
                'tipopago' => $tipopago,
                'saldo_anterior' => (float)($pago->saldo ?? 0),
            ]));
            return $this->response->setJSON(['success' => true, 'message' => 'Pago agregado correctamente']);
        }
        return $this->response->setJSON(['success' => false, 'message' => 'Error al agregar pago'])->setStatusCode(500);
    }

    public function crearmuestra()
    {
        $registroId = (int) ($this->request->getPost('registro_id') ?? 0);
        if ($registroId < 1) return redirect()->back()->with('error', 'Registro no válido');
        if ($this->registerModel->isRegistroAnulado($registroId)) {
            return redirect()->back()->with('error', 'No puede crear muestra en una orden anulada.');
        }
        $muestraModel = model(MuestraModel::class);
        if ($muestraModel->getByRegistro($registroId)) {
            return redirect()->to("registers/view/{$registroId}")->with('error', 'Ya existe una muestra para este registro');
        }
        $tipoMuestraId = (int) ($this->request->getPost('tipo_muestra_id') ?? 1);
        $muestraModel->saveMuestra([
            'registro_id' => $registroId,
            'tipo_muestra_id' => $tipoMuestraId,
            'estado' => 0,
            'fecha_tomada' => date('Y-m-d H:i:s'),
            'usuario_tomo' => session()->get('person_id'),
        ]);
        \App\Models\AuditoriaModel::log('registers', 'crear_muestra', (string) $registroId, \App\Models\AuditoriaModel::detail([
            'tipo_muestra_id' => $tipoMuestraId,
        ]));
        return redirect()->to("registers/view/{$registroId}")->with('success', 'Muestra creada');
    }

    public function cambiarestadomuestra($muestraId, $nuevoEstado)
    {
        $muestraId = (int) $muestraId;
        $nuevoEstado = (int) $nuevoEstado;
        $muestraModel = model(MuestraModel::class);
        $muestra = $muestraModel->getById($muestraId);
        if (!$muestra) return redirect()->back()->with('error', 'Muestra no encontrada');
        $regIdMuestra = (int) ($muestra['registro_id'] ?? 0);
        if ($regIdMuestra > 0 && $this->registerModel->isRegistroAnulado($regIdMuestra)) {
            return redirect()->back()->with('error', 'La orden está anulada; no se puede cambiar el estado de la muestra.');
        }
        $estadoAnterior = (int)($muestra['estado'] ?? 0);
        $muestraModel->cambiarEstado($muestraId, $nuevoEstado, (int) session()->get('person_id'));
        $estadosMuestra = [0 => 'Tomada', 1 => 'Recibida', 2 => 'Procesada', 3 => 'Validada'];
        \App\Models\AuditoriaModel::log('registers', 'cambiar_estado_muestra', (string)($muestra['registro_id'] ?? 0), \App\Models\AuditoriaModel::detail([
            'muestra_id' => $muestraId,
            'estado_anterior' => $estadosMuestra[$estadoAnterior] ?? $estadoAnterior,
            'estado_nuevo' => $estadosMuestra[$nuevoEstado] ?? $nuevoEstado,
        ]));
        return redirect()->to("registers/view/" . (int)($muestra['registro_id'] ?? 0))->with('success', 'Estado actualizado');
    }

    public function saveregvalues(): ResponseInterface
    {
        $data = $this->request->getPost('data');
        $data = is_string($data) ? json_decode($data, true) : $data;
        $comentario = trim((string)($this->request->getPost('comentario_resultado') ?? ''));
        $registroIdPost = (int)($this->request->getPost('registro_id') ?? 0);

        if (!is_array($data)) {
            $data = [];
        }

        $registroId = null;
        foreach ($data as $item) {
            $rid = isset($item['registro_id']) ? (int) $item['registro_id'] : 0;
            if ($rid > 0) {
                $registroId = $rid;
                break;
            }
        }
        if (($registroId === null || $registroId < 1) && $registroIdPost > 0) {
            $registroId = $registroIdPost;
        }
        if ($registroId !== null && $registroId > 0) {
            $blocked = $this->bloquearSiRegistroAnuladoJson($registroId);
            if ($blocked !== null) {
                return $blocked;
            }
        }
        if ($registroId !== null) {
            $this->registerModel->saveRegistro([
                'comentario_resultado' => ($comentario !== '' ? $comentario : null),
            ], $registroId);
            $this->registerModel->deleteRegvaluesByRegistroId($registroId);
        }

        $valCount = 0;
        foreach ($data as $item) {
            $this->registerModel->saveRegvalues([
                'regvalues'   => $item['valor'] ?? null,
                'registro_id' => $item['registro_id'] ?? null,
                'name'        => $item['id'] ?? null,
                'id_session'  => session()->get('person_id'),
            ]);
            $valCount++;
        }
        if ($registroId !== null) {
            \App\Models\AuditoriaModel::log('registers', 'guardar_resultados', (string) $registroId, \App\Models\AuditoriaModel::detail([
                'cantidad_valores' => $valCount,
                'tiene_comentario' => ($comentario !== ''),
            ]));
        }
        return $this->response->setJSON(['success' => true, 'message' => 'Guardado exitoso']);
    }

    public function saveanalisiss(): ResponseInterface
    {
        $data = $this->request->getPost('data');
        $data = is_string($data) ? json_decode($data, true) : $data;

        if (!is_array($data)) {
            return $this->response->setJSON(['success' => false, 'message' => 'Datos inválidos'])->setStatusCode(400);
        }

        $ridAn = 0;
        foreach ($data as $item) {
            $ridAn = (int) ($item['registro_id'] ?? 0);
            if ($ridAn > 0) {
                break;
            }
        }
        if ($ridAn > 0) {
            $blocked = $this->bloquearSiRegistroAnuladoJson($ridAn);
            if ($blocked !== null) {
                return $blocked;
            }
        }

        foreach ($data as $item) {
            $this->registerModel->saveAnalisis([
                'padre'       => $item['padre'] ?? null,
                'hijo'        => $item['hijo'] ?? null,
                'analisis'    => $item['analisis'] ?? null,
                'valor'       => $item['valor'] ?? null,
                'unidad'      => $item['unidad'] ?? null,
                'minimo'      => $item['minimo'] ?? null,
                'maximo'      => $item['maximo'] ?? null,
                'registro_id' => $item['registro_id'] ?? null,
                'id_session'  => session()->get('person_id'),
                'estado_id'   => 1,
            ]);
        }
        return $this->response->setJSON(['success' => true, 'message' => 'Guardado exitoso']);
    }

    public function validar()
    {
        $registroId = (int) ($this->request->getPost('registro_id') ?? 0);
        $tipo = $this->request->getPost('tipo') ?? '';
        $obs = trim($this->request->getPost('observaciones') ?? '') ?: null;
        if ($registroId < 1 || !in_array($tipo, ['tecnico', 'medico'])) {
            return redirect()->back()->with('error', 'Datos inválidos');
        }
        if ($this->registerModel->isRegistroAnulado($registroId)) {
            return redirect()->back()->with('error', 'La orden está anulada; no se puede validar.');
        }
        $this->registerModel->validarResultados($registroId, $tipo, $obs);
        \App\Models\AuditoriaModel::log('registers', 'validar_' . $tipo, (string) $registroId, \App\Models\AuditoriaModel::detail([
            'tipo' => $tipo,
            'observaciones' => $obs,
        ]));
        $msg = $tipo === 'tecnico' ? 'Validación técnica registrada' : 'Validación médica registrada';
        return redirect()->to("registers/viewreport/{$registroId}")->with('success', $msg);
    }

    /**
     * Envía el PDF por WhatsApp al paciente, doctor o ambos
     */
    public function sendWhatsapp(int $id = 0): ResponseInterface
    {
        $id = (int) $id;
        if ($id < 1) {
            return $this->response->setJSON(['success' => false, 'message' => 'Registro no válido'])->setStatusCode(400);
        }
        if ($this->registerModel->isRegistroAnulado($id)) {
            return $this->response->setJSON(['success' => false, 'message' => 'La orden está anulada; no se puede enviar el PDF.'])->setStatusCode(403);
        }

        $destinatarios = $this->request->getPost('destinatarios'); // paciente, doctor, ambos
        if (!in_array($destinatarios, ['paciente', 'doctor', 'ambos'], true)) {
            return $this->response->setJSON(['success' => false, 'message' => 'Seleccione destinatario'])->setStatusCode(400);
        }

        $data = $this->registerService->prepareReportData($id);
        if (!$data) {
            return $this->response->setJSON(['success' => false, 'message' => 'Registro no encontrado'])->setStatusCode(404);
        }

        $paciente = $data['paciente'] ?? null;
        $doctor   = $data['doctor'] ?? null;
        $registerInfo = $data['register_info'] ?? null;
        if (!$paciente || !$doctor || !$registerInfo) {
            return $this->response->setJSON(['success' => false, 'message' => 'Datos incompletos'])->setStatusCode(400);
        }

        $whatsappService = new WhatsAppService();
        if (!$whatsappService->isConfigured()) {
            return $this->response->setJSON(['success' => false, 'message' => 'Configure WhatsApp en Configuración > WhatsApp'])->setStatusCode(400);
        }

        $labConfig = $this->configModel->getValue('company') ?? 'Laboratorio';
        $pacienteNombre = trim(($paciente->first_name ?? '') . ' ' . ($paciente->last_name_fa ?? '') . ' ' . ($paciente->last_name_mom ?? ''));
        $doctorNombre   = trim($doctor->name ?? '');
        $ingreso        = $registerInfo->ingreso ?? date('Y-m-d');
        helper('registro');
        $orden          = registro_orden_display($registerInfo);

        $msgPaciente = $this->configModel->getValue('whatsapp_message_paciente') ?: 'Estimado/a {paciente}, adjuntamos los resultados de su análisis (Orden #{orden}, {fecha}). {laboratorio}';
        $msgDoctor   = $this->configModel->getValue('whatsapp_message_doctor') ?: 'Dr/a {doctor}, adjuntamos resultados del paciente {paciente} (Orden #{orden}, {fecha}). {laboratorio}';

        $templateData = [
            'paciente'   => $pacienteNombre,
            'doctor'     => $doctorNombre,
            'orden'      => $orden,
            'fecha'      => date('d/m/Y', strtotime($ingreso)),
            'laboratorio'=> $labConfig,
        ];

        helper('qr');
        $reportUrl = site_url('doctor/viewreport/' . $id);
        $qrDataUri = qr_base64($reportUrl, 100);
        $html      = $this->registerService->renderReportPdfHtml($data, $reportUrl, $qrDataUri);
        $pdfService = new PdfService();
        $filename   = 'Resultados_' . preg_replace('/\s+/', '_', $pacienteNombre) . '_' . $id . '.pdf';
        $pdfContent = $pdfService->generate($html, $filename);

        $token = bin2hex(random_bytes(16));
        $tempDir = WRITEPATH . 'temp' . DIRECTORY_SEPARATOR;
        if (!is_dir($tempDir)) {
            mkdir($tempDir, 0755, true);
        }
        $tempFile = $tempDir . 'wa_' . $token . '.pdf';
        if (file_put_contents($tempFile, $pdfContent) === false) {
            return $this->response->setJSON(['success' => false, 'message' => 'Error al generar PDF'])->setStatusCode(500);
        }

        $baseUrl = rtrim($this->configModel->getValue('whatsapp_base_url') ?: base_url(), '/');
        $pdfUrl  = $baseUrl . '/registers/servePdfFile/' . $token;

        $cache = \Config\Services::cache();
        $cache->save('wa_pdf_' . $token, ['path' => $tempFile], 3600);

        $results = [];
        if (in_array($destinatarios, ['paciente', 'ambos'], true)) {
            $pacientePhone = trim($paciente->phone_number ?? '');
            if ($pacientePhone === '') {
                $results[] = ['target' => 'paciente', 'success' => false, 'message' => 'Paciente sin número de teléfono'];
            } else {
                $msg = $whatsappService->applyTemplate($msgPaciente, $templateData);
                $res = $whatsappService->sendWithPdf($pacientePhone, $msg, $pdfUrl, $filename);
                $results[] = ['target' => 'paciente', 'success' => $res['success'], 'message' => $res['message']];
            }
        }
        if (in_array($destinatarios, ['doctor', 'ambos'], true)) {
            $doctorPhone = trim($doctor->phone_number ?? '');
            if ($doctorPhone === '') {
                $results[] = ['target' => 'doctor', 'success' => false, 'message' => 'Doctor sin número de teléfono'];
            } else {
                $msg = $whatsappService->applyTemplate($msgDoctor, $templateData);
                $res = $whatsappService->sendWithPdf($doctorPhone, $msg, $pdfUrl, $filename);
                $results[] = ['target' => 'doctor', 'success' => $res['success'], 'message' => $res['message']];
            }
        }

        // El PDF y el token permanecen 1h en cache para que Meta (Cloud API) pueda descargarlo

        $allOk = !empty($results) && array_reduce($results, fn($a, $r) => $a && ($r['success'] ?? false), true);
        $anyOk = array_reduce($results, fn($a, $r) => $a || ($r['success'] ?? false), false);
        $msg = $anyOk ? ($allOk ? 'Enviado correctamente.' : 'Algunos envíos fallaron.') : 'No se pudo enviar.';

        \App\Models\AuditoriaModel::log('registers', 'enviar_whatsapp', (string) $id, \App\Models\AuditoriaModel::detail([
            'destinatarios' => $destinatarios,
            'paciente' => $pacienteNombre,
            'doctor' => $doctorNombre,
            'resultado' => $allOk ? 'exitoso' : ($anyOk ? 'parcial' : 'fallido'),
        ]));

        return $this->response->setJSON([
            'success' => $anyOk,
            'message' => $msg,
            'results' => $results,
        ]);
    }

    /**
     * Sirve el PDF temporal por token (URL pública para que WhatsApp Cloud API lo descargue)
     */
    public function servePdfFile(string $token = '')
    {
        $token = preg_replace('/[^a-f0-9]/', '', $token);
        if (strlen($token) < 20) {
            return $this->response->setStatusCode(404)->setBody('No encontrado');
        }
        $cache = \Config\Services::cache();
        $cached = $cache->get('wa_pdf_' . $token);
        if (!$cached || !is_array($cached) || empty($cached['path']) || !is_file($cached['path'])) {
            return $this->response->setStatusCode(404)->setBody('Archivo no encontrado o expirado');
        }
        $path = $cached['path'];
        $content = file_get_contents($path);
        return $this->response
            ->setHeader('Content-Type', 'application/pdf')
            ->setHeader('Content-Disposition', 'inline; filename="resultados.pdf"')
            ->setBody($content);
    }
}
