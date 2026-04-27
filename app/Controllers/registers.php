<?php

namespace App\Controllers;

use App\Libraries\PdfService;
use App\Services\BillingDocumentService;
use App\Services\AutoReactivoConsumptionService;
use App\Services\ConfigService;
use App\Services\RegisterService;
use App\Services\ReportPdfLayoutService;
use App\Services\WhatsAppService;
use App\Models\LabotestModel;
use App\Models\RegisterModel;
use App\Models\CustomerModel;
use App\Models\PerfilExamenModel;
use App\Models\MuestraModel;
use App\Models\AppConfigModel;
use App\Models\DoctorModel;
use App\Models\DoctorCommissionModel;
use App\Models\LeyendaModel;
use CodeIgniter\HTTP\ResponseInterface;
use Endroid\QrCode\Builder\Builder;
use Endroid\QrCode\Color\Color;
use Endroid\QrCode\Encoding\Encoding;
use Endroid\QrCode\ErrorCorrectionLevel;
use Endroid\QrCode\Writer\PngWriter;

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
    protected CustomerModel $customerModel;
    protected ConfigService $configService;

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
        $this->customerModel   = model(CustomerModel::class);
        $this->configService   = new ConfigService();
    }

    /**
     * Texto configurado en Configuración cuando la orden no tiene médico del catálogo (lista, ayudas).
     */
    private function getLabelSinDoctorConfig(): string
    {
        $label = trim((string) ($this->configService->getAllAsArray()['label_sin_doctor'] ?? ''));

        return $label !== '' ? $label : 'Sin doctor';
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
            'edit_discount_info' => ['institucion' => '', 'descuento' => 0.0],
            'label_sin_doctor' => $this->getLabelSinDoctorConfig(),
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
        $regvaluesCount = count($this->registerModel->getInfoAnalisis($id));
        $discountInfo = $this->resolveInstitutionDiscountByPersonId((int) ($info->person_id ?? 0));

        return view('registers/manage', [
            'current_module'  => 'registers',
            'categories'      => $categories,
            'perfiles'        => $perfiles ?? [],
            'allowed_modules' => $this->allowed_modules,
            'user_info'       => $this->user_info,
            'controller_name' => 'registers',
            'edit_registro'   => $info,
            'edit_pago'       => $pago,
            'edit_regvalues_count' => $regvaluesCount,
            'edit_discount_info' => $discountInfo,
            'label_sin_doctor' => $this->getLabelSinDoctorConfig(),
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

        $whatsappOk  = (new WhatsAppService())->isConfigured();
        $manageTable = $this->buildRegistrosTable($registros, $whatsappOk);
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

    private function buildRegistrosTable(array $registros, bool $whatsappConfigured = false): string
    {
        $labelSinDoctor = $this->getLabelSinDoctorConfig();

        $html = '<div class="table-responsive"><table class="table table-bordered table-striped registros-table"><thead><tr>';
        $html .= '<th>Código</th><th>Paciente</th><th>Doctor</th><th>Total</th><th>Saldo</th><th class="text-end">Acciones</th>';
        $html .= '</tr></thead><tbody>';

        $sumTotal = 0;
        $sumSaldo = 0;

        foreach ($registros as $r) {
            $rid = (int) ($r->registro_id ?? 0);
            $totalNum = (float) ($r->total ?? 0);
            $montoPagadoNum = (float) ($r->monto_pagar ?? 0);
            $saldoNum = (float) ($r->saldo ?? 0);
            $isAnulado = isset($r->anulado) && (int) $r->anulado === 1;
            $isPrioridad = !$isAnulado && isset($r->prioridad) && (int) $r->prioridad === 1;
            $hasDoctor = (int) ($r->doctor_id ?? 0) > 0;

            if (!$isAnulado) {
                $sumTotal += $totalNum;
                $sumSaldo += $saldoNum;
            }

            $rowClass = $isAnulado ? 'table-secondary' : ($isPrioridad ? 'registro-prioridad table-danger fw-semibold' : '');
            $html .= '<tr' . ($rowClass !== '' ? ' class="' . $rowClass . '"' : '') . '>';
            $ordenDisp = registro_orden_display($r);
            $codigoTdClass = $isPrioridad ? ' class="registro-prioridad-codigo"' : '';
            $html .= '<td' . $codigoTdClass . ' title="ID interno: ' . esc((string) $rid) . '">' . esc($ordenDisp);
            if ($isAnulado) {
                $html .= ' <span class="badge bg-dark ms-1">Anulada</span>';
            } elseif ($isPrioridad) {
                $html .= ' <span class="badge bg-danger ms-1"><i class="fa-solid fa-triangle-exclamation me-1"></i>Urgente</span>';
            }
            $html .= '</td>';
            $html .= '<td>' . esc($r->paciente ?? '') . '</td>';
            $html .= '<td>' . ($hasDoctor ? esc($r->doctor ?? '') : '<span class="text-muted">' . esc($labelSinDoctor) . '</span>') . '</td>';
            $html .= '<td>' . esc($r->total ?? '') . '</td>';
            $html .= '<td>' . esc($r->saldo ?? '') . '</td>';
            $html .= '<td class="text-end registros-acciones-col">';
            if ($isAnulado) {
                $html .= '<div class="registros-acciones justify-content-end">';
                $html .= '<a href="' . site_url('registers/anulada/' . $rid) . '" class="btn btn-sm btn-outline-secondary btn-accion-texto" title="Ver orden anulada (solo lectura)"><i class="fa-solid fa-lock me-1"></i>Ver</a>';
                $html .= '</div>';
            } else {
                $html .= '<div class="registros-acciones">';
                $hasRegvalues = isset($r->regvalues_count) && (int) $r->regvalues_count > 0;
                $btnTitle = $hasRegvalues ? 'Editar' : 'Agregar';
                $btnIcon = $hasRegvalues ? 'fa-pen' : 'fa-plus';
                $html .= '<a href="' . site_url('registers/view/' . $rid) . '" class="btn btn-sm btn-outline-primary btn-icono-accion" title="' . esc($btnTitle) . '"><i class="fa-solid ' . esc($btnIcon) . '"></i></a> ';
                $editOrderTitle = $hasRegvalues ? 'Agregar más pruebas a la orden' : 'Editar prueba (orden)';
                $html .= '<a href="' . site_url('registers/edit/' . $rid) . '" class="btn btn-sm btn-outline-success btn-icono-accion" title="' . esc($editOrderTitle) . '"><i class="fa-solid fa-flask"></i></a>';
                $btnMasClass = ($saldoNum > 0) ? 'btn-warning' : 'btn-outline-secondary';
                $html .= '<div class="dropdown">';
                $html .= '<button class="btn btn-sm ' . $btnMasClass . ' dropdown-toggle btn-accion-mas" type="button" data-bs-toggle="dropdown" aria-expanded="false" title="Más acciones"><i class="fa-solid fa-ellipsis"></i><span class="ms-1 d-none d-md-inline">Más</span></button>';
                $html .= '<ul class="dropdown-menu dropdown-menu-end shadow-sm registros-acciones-menu">';
                $pacientePhone = trim($r->paciente_phone ?? '');
                $doctorPhone   = trim($r->doctor_phone ?? '');
                $html .= '<li><a class="dropdown-item" href="' . site_url('registers/view/' . $rid) . '"><i class="fa-solid ' . esc($btnIcon) . ' me-2 text-primary"></i>' . esc($btnTitle) . ' resultado</a></li>';
                $html .= '<li><a class="dropdown-item" href="' . site_url('registers/edit/' . $rid) . '"><i class="fa-solid fa-flask me-2 text-success"></i>' . esc($editOrderTitle) . '</a></li>';
                $html .= '<li><hr class="dropdown-divider"></li>';
                $html .= '<li><a class="dropdown-item" href="' . site_url('registers/orden/' . $rid) . '"><i class="fa-solid fa-print me-2 text-secondary"></i>Imprimir orden</a></li>';
                if ($hasRegvalues) {
                    $html .= '<li><a class="dropdown-item" href="' . site_url('registers/viewreport/' . $rid) . '"><i class="fa-solid fa-file-lines me-2 text-secondary"></i>Ver reporte</a></li>';
                    $html .= '<li><a class="dropdown-item" href="' . site_url('registers/printreport/' . $rid) . '" target="_blank"><i class="fa-solid fa-print me-2 text-secondary"></i>Imprimir reporte</a></li>';
                } else {
                    $html .= '<li><span class="dropdown-item text-muted disabled"><i class="fa-solid fa-file-lines me-2"></i>Ver reporte (sin resultados)</span></li>';
                    $html .= '<li><span class="dropdown-item text-muted disabled"><i class="fa-solid fa-print me-2"></i>Imprimir reporte (sin resultados)</span></li>';
                }
                if ($hasRegvalues) {
                    $html .= '<li><a class="dropdown-item" href="' . site_url('registers/pdf/' . $rid) . '" target="_blank"><i class="fa-solid fa-file-pdf me-2 text-success"></i>Descargar PDF</a></li>';
                } else {
                    $html .= '<li><span class="dropdown-item text-muted disabled"><i class="fa-solid fa-file-pdf me-2"></i>Descargar PDF (sin resultados)</span></li>';
                }
                if ($hasRegvalues) {
                    $html .= '<li><a class="dropdown-item" href="' . site_url('registers/qrResultadosPng/' . $rid) . '"><i class="fa-solid fa-qrcode me-2 text-dark"></i>Descargar QR resultados</a></li>';
                } else {
                    $html .= '<li><span class="dropdown-item text-muted disabled"><i class="fa-solid fa-qrcode me-2"></i>Descargar QR resultados (sin resultados)</span></li>';
                }
                if ($whatsappConfigured) {
                    if ($hasRegvalues) {
                        $docWa = $hasDoctor ? ($r->doctor ?? '') : $labelSinDoctor;
                        $html .= '<li><button type="button" class="dropdown-item btn-whatsapp-pdf" data-id="' . $rid . '" data-paciente="' . esc($r->paciente ?? '') . '" data-doctor="' . esc($docWa) . '" data-paciente-phone="' . esc($pacientePhone) . '" data-doctor-phone="' . esc($doctorPhone) . '" data-ingreso="' . esc($r->ingreso ?? '') . '"><i class="fa-brands fa-whatsapp me-2 text-success"></i>Enviar por WhatsApp</button></li>';
                    } else {
                        $html .= '<li><span class="dropdown-item text-muted disabled"><i class="fa-brands fa-whatsapp me-2"></i>Enviar por WhatsApp (sin resultados)</span></li>';
                    }
                }
                if ($saldoNum > 0) {
                    $html .= '<li><button type="button" class="dropdown-item btn-agregar-pago" data-id="' . $rid . '" data-total="' . esc($r->total ?? '') . '" data-saldo="' . esc($r->saldo ?? '') . '" data-monto="' . esc($r->monto_pagar ?? '') . '"><i class="fa-solid fa-money-bill-wave me-2 text-warning"></i>Agregar pago</button></li>';
                }
                $html .= '<li><a class="dropdown-item" href="' . site_url('registers/comprobantePdf/' . $rid) . '" target="_blank"><i class="fa-solid fa-file-invoice-dollar me-2 text-dark"></i>Descargar comprobante</a></li>';
                $html .= '<li><button type="button" class="dropdown-item btn-historial" data-id="' . $rid . '"><i class="fa-solid fa-clock-rotate-left me-2 text-info"></i>Historial de pagos y pruebas</button></li>';
                $html .= '<li><hr class="dropdown-divider"></li>';
                $html .= '<li><button type="button" class="dropdown-item text-danger btn-anular-registro" data-id="' . $rid . '"><i class="fa-solid fa-ban me-2 text-danger"></i>Anular orden</button></li>';
                $html .= '</ul>';
                $html .= '</div>';
                $html .= '</div>';
            }
            $html .= '</td>';
            $html .= '</tr>';
        }
        if (empty($registros)) {
            $html .= '<tr><td colspan="6">No hay registros.</td></tr>';
        } else {
            $html .= '<tr class="table-secondary fw-bold"><td colspan="3">Total</td>';
            $html .= '<td>' . number_format($sumTotal, 2) . '</td>';
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
        $discounts = $this->configService->getCustomerInstitutionDiscounts();
        $normalizeInst = static function (string $value): string {
            $v = trim(preg_replace('/\s+/', ' ', $value) ?? $value);
            return function_exists('mb_strtolower') ? mb_strtolower($v, 'UTF-8') : strtolower($v);
        };
        $discountsNorm = [];
        foreach ($discounts as $inst => $pct) {
            $k = $normalizeInst((string) $inst);
            if ($k !== '') {
                $discountsNorm[$k] = (float) $pct;
            }
        }

        foreach ($data as &$row) {
            $inst = trim((string) ($row['institucion'] ?? ''));
            $key = $normalizeInst($inst);
            $pct = ($key !== '' && array_key_exists($key, $discountsNorm)) ? (float) $discountsNorm[$key] : 0.0;
            $row['descuento'] = max(0, min(100, round($pct, 2)));
        }
        unset($row);

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
        $pruebasInfoFallback = [];
        if ($pruebasInfo === []) {
            $pruebasIds = $this->registerService->extractPrianacategoriaIdsFromRegistroPruebas((string) ($registerInfo->pruebas ?? ''));
            $cfgRows = $this->registerModel->getPrianacategoriaConfigByIds($pruebasIds);
            foreach ($cfgRows as $cfg) {
                $anacategoriaId = (int) ($cfg['anacategoria_id'] ?? 0);
                $catInfo = $anacategoriaId > 0 ? $this->labotestModel->getCategoryInfo($anacategoriaId) : null;
                $pruebasInfoFallback[] = [
                    'prianacategoria_id' => (int) ($cfg['prianacategoria_id'] ?? 0),
                    'hijo'               => (string) ($cfg['name'] ?? ''),
                    'padre'              => (string) ($catInfo->name ?? 'Sin categoría'),
                    'compleja'           => (int) ($cfg['compleja'] ?? 0),
                ];
            }
        }

        $muestraModel = model(MuestraModel::class);
        $muestra = $muestraModel->getByRegistro($id);
        $tiposMuestra = $muestraModel->getTiposMuestra();

        $decimalesSugerencia = (int) (model(AppConfigModel::class)->getValue('decimales_sugerencia') ?: 2);
        $decimalesSugerencia = max(0, min(10, $decimalesSugerencia));

        $analisis = $this->registerModel->getInfoAnalisis($id);

        $labValState = (new ConfigService())->getLabValidationStateForView();
        $poblacionesCatalogo = $this->labotestModel->getPoblaciones();

        return view('registers/formfill', [
            'current_module' => 'registers',
            'muestra' => $muestra,
            'tipos_muestra' => $tiposMuestra,
            'controller_name'   => 'registers',
            'register_info'     => $registerInfo,
            'pruebas_info'      => $pruebasInfo,
            'pruebas_info_fallback' => $pruebasInfoFallback,
            'matching_poblacion_ids' => $matchingPoblacionIds,
            'analisis'          => $analisis,
            'lab_validators'    => $labValState['validators'],
            'lab_approvers'     => $labValState['approvers'],
            'poblaciones_catalogo' => $poblacionesCatalogo,
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
        $publicToken    = $this->registerModel->ensurePublicAccessToken($id);
        $pdfLayout      = (new ReportPdfLayoutService())->getActiveLayoutForRender();
        $labConfig      = $this->registerService->getLabConfig();

        return view('registers/viewreport', [
            'current_module'    => 'registers',
            'controller_name'  => 'registers',
            'register_info'     => $data['register_info'],
            'labotests_namecate' => $id,
            'public_resultados_token' => $publicToken,
            'paciente'          => $data['paciente'],
            'doctor'            => $data['doctor'],
            'analisis'          => $data['analisis'],
            'grupos'            => $data['grupos'],
            'report_pria_tipo_muestra_nombre' => $data['report_pria_tipo_muestra_nombre'] ?? [],
            'report_pria_metodo_nombre'       => $data['report_pria_metodo_nombre'] ?? [],
            'report_lab_firmas'               => $data['report_lab_firmas'] ?? [],
            'report_pria_refs_consolidada'    => $data['report_pria_refs_consolidada'] ?? [],
            'registerModel'     => $this->registerModel,
            'allowed_modules'   => $this->allowed_modules,
            'user_info'         => $this->user_info,
            'report_emitido_en' => $this->registerService->reportEmitidoEnForView($id),
            'sin_billing_enabled' => $billingService->isSinBillingEnabled(),
            'comprobante_pdf_disponible' => $pago !== null && $pagoCompleto,
            'comprobante_pdf_pendiente_pago' => $pago !== null && !$pagoCompleto,
            'comprobante_pdf_sin_registro_pago' => $pago === null,
            'pdf_layout'        => $pdfLayout,
            'lab_config'        => $labConfig,
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
        $reportUrl = $this->publicReportViewerUrlForQr($id);
        $qrLayout  = (new \App\Services\ReportPdfLayoutService())->getPrintLayoutForRender();
        $qrPx      = \App\Services\ReportPdfLayoutService::qrImagePixelSizeFromLayout($qrLayout);
        $qrDataUri = qr_base64($reportUrl, $qrPx);
        $emitidoEn = $this->registerService->lockReportEmitidoEnForPrintOrPdf($id);
        $html      = $this->registerService->renderReportPrintHtml($data, $reportUrl, $qrDataUri, $id, $emitidoEn);

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

        $bcSizePct = (int) $this->configModel->getValue('order_barcode_print_size_percent');
        if ($bcSizePct < 1) {
            $bcSizePct = 100;
        }
        $bcSizePct = max(30, min(250, $bcSizePct));

        $edadPacienteOrden = $this->registerService->formatEdadAlMomento($registerInfo->birthday ?? null, $refIngreso);

        return view('registers/orden', [
            'current_module'    => 'registers',
            'controller_name'   => 'registers',
            'register_info'     => $registerInfo,
            'fecha'             => $fecha,
            'edad_paciente_orden' => $edadPacienteOrden,
            'labotests_namecate' => $id,
            'grupos_pruebas'    => $gruposPruebas,
            'label_sin_doctor'  => $this->getLabelSinDoctorConfig(),
            'show_order_barcode' => ($this->configModel->getValue('show_order_barcode') !== '0'),
            'order_barcode_print_layout' => (strtolower($this->configModel->getValue('order_barcode_print_layout')) === 'horizontal')
                ? 'horizontal'
                : 'vertical',
            'order_barcode_print_size_percent' => $bcSizePct,
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

        $edadPacienteOrden = $this->registerService->formatEdadAlMomento($registerInfo->birthday ?? null, $refIngreso);

        $html = view('registers/orden_pdf', [
            'register_info'    => $registerInfo,
            'fecha'            => $fecha,
            'edad_paciente_orden' => $edadPacienteOrden,
            'grupos_pruebas'   => $gruposPruebas,
            'registro_id'      => $id,
            'label_sin_doctor' => $this->getLabelSinDoctorConfig(),
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
        $reportUrl = $this->publicReportViewerUrlForQr($id);
        $qrLayout  = (new \App\Services\ReportPdfLayoutService())->getActiveLayoutForRender();
        $qrPx      = \App\Services\ReportPdfLayoutService::qrImagePixelSizeFromLayout($qrLayout);
        $qrDataUri = qr_base64($reportUrl, $qrPx);
        $emitidoEn = $this->registerService->lockReportEmitidoEnForPrintOrPdf($id);
        $html      = $this->registerService->renderReportPdfHtml($data, $reportUrl, $qrDataUri, $emitidoEn);

        $pdfService    = new PdfService();
        $pacienteNombre = trim(($data['paciente']->first_name ?? '') . '_' . ($data['paciente']->last_name_fa ?? ''));
        $filename      = 'Resultados_' . ($pacienteNombre ?: 'paciente') . '_' . $id . '_' . date('Y-m-d') . '.pdf';

        return $this->response
            ->setHeader('Content-Type', 'application/pdf')
            ->setHeader('Content-Disposition', 'attachment; filename="' . $filename . '"')
            ->setBody($pdfService->generate($html, $filename));
    }

    /**
     * URL para QR del reporte: visor público por token si existe columna en BD; si no, portal doctor.
     */
    private function publicReportViewerUrlForQr(int $registroId): string
    {
        $token = $this->registerModel->ensurePublicAccessToken($registroId);
        if ($token !== null && $token !== '') {
            return site_url('resultados/' . $token);
        }

        return site_url('doctor/viewreport/' . $registroId);
    }

    /**
     * PNG 500×500 del QR que apunta al visor de resultados (enlace público o portal doctor).
     * Nombre de archivo: paciente + código de orden (numero_orden o registro_id), como en la lista.
     */
    public function qrResultadosPng($id = -1)
    {
        $id = (int) $id;
        if ($id < 1) {
            return redirect()->to('registers/lista')->with('error', 'Registro no válido');
        }
        if ($this->registerModel->isRegistroAnulado($id)) {
            return redirect()->to('registers/lista')->with('error', 'La orden está anulada.');
        }
        $hist = $this->registerModel->getHistorialRegistro($id);
        if ($hist === null) {
            return redirect()->to('registers/lista')->with('error', 'Registro no encontrado');
        }
        if (empty($hist['tiene_resultados'])) {
            return redirect()->to('registers/lista')->with('error', 'El código QR solo está disponible cuando la orden ya tiene resultados (reporte y PDF).');
        }

        helper('registro');
        $reportUrl = $this->publicReportViewerUrlForQr($id);
        $paciente  = trim((string) ($hist['registro']->paciente ?? ''));
        $codigo    = registro_orden_display($hist['registro']);
        $nomPac    = $this->sanitizeFilenameSegment($paciente !== '' ? $paciente : 'paciente');
        $nomCod    = $this->sanitizeFilenameSegment($codigo !== '' ? $codigo : (string) $id);
        $base      = $nomPac . '_' . $nomCod;
        if (strlen($base) > 180) {
            $base = substr($base, 0, 180);
        }
        $filenameUtf8 = ($base !== '' ? $base : 'QR_resultados') . '.png';
        $asciiName    = 'QR_' . preg_replace('/[^a-zA-Z0-9._-]+/', '_', $nomCod) . '.png';
        if ($asciiName === 'QR_.png' || strlen($asciiName) < 6) {
            $asciiName = 'QR_ord' . $id . '.png';
        }

        try {
            // Fondo transparente: en Endroid/GD alpha 127 = totalmente transparente (0 = opaco).
            $builder = new Builder(
                writer: new PngWriter(),
                data: $reportUrl,
                encoding: new Encoding('UTF-8'),
                errorCorrectionLevel: ErrorCorrectionLevel::Low,
                size: 500,
                margin: 10,
                foregroundColor: new Color(0, 0, 0, 0),
                backgroundColor: new Color(255, 255, 255, 127),
            );
            $result = $builder->build();
        } catch (\Throwable $e) {
            return redirect()->to('registers/lista')->with('error', 'No se pudo generar el código QR.');
        }

        $filenameStar = "filename*=UTF-8''" . rawurlencode($filenameUtf8);

        return $this->response
            ->setHeader('Content-Type', $result->getMimeType())
            ->setHeader('Content-Disposition', 'attachment; filename="' . $asciiName . '"; ' . $filenameStar)
            ->setBody($result->getString());
    }

    private function sanitizeFilenameSegment(string $str): string
    {
        $str = preg_replace('/[\x00-\x1F\x7F<>:"\\/|?*]+/u', '', $str) ?? '';
        $str = preg_replace('/\s+/u', '_', trim($str)) ?? '';
        $str = preg_replace('/_+/u', '_', $str) ?? '';

        return $str;
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

    private function parseMoneyInput(mixed $value): float
    {
        if (is_int($value) || is_float($value)) {
            return (float) $value;
        }
        $s = trim((string) $value);
        if ($s === '') {
            return 0.0;
        }
        $s = str_replace(["\xc2\xa0", ' '], '', $s);
        if (str_contains($s, ',') && str_contains($s, '.')) {
            $lastComma = strrpos($s, ',');
            $lastDot = strrpos($s, '.');
            if ($lastComma !== false && $lastDot !== false && $lastComma > $lastDot) {
                $s = str_replace('.', '', $s);
                $s = str_replace(',', '.', $s);
            } else {
                $s = str_replace(',', '', $s);
            }
        } elseif (str_contains($s, ',')) {
            $s = str_replace(',', '.', $s);
        }

        return is_numeric($s) ? (float) $s : 0.0;
    }

    private function normalizeOptionalDoctorId(mixed $value): int
    {
        $doctorId = (int) $value;

        return $doctorId > 0 ? $doctorId : 0;
    }

    /**
     * @return array{institucion:string,descuento:float}
     */
    private function resolveInstitutionDiscountByPersonId(?int $personId): array
    {
        if (($personId ?? 0) < 1) {
            return ['institucion' => '', 'descuento' => 0.0];
        }
        $info = $this->registerModel->getPatientById((int) $personId);
        $institucion = '';
        if ($info) {
            $customer = $this->customerModel
                ->groupStart()
                    ->where('deleted', 0)
                    ->orWhere('deleted', null)
                ->groupEnd()
                ->where('person_id', (int) $personId)
                ->first();
            if (is_array($customer)) {
                $institucion = trim((string) ($customer['institucion'] ?? ''));
            } elseif (is_object($customer)) {
                $institucion = trim((string) ($customer->institucion ?? ''));
            }
        }
        if ($institucion === '') {
            return ['institucion' => '', 'descuento' => 0.0];
        }

        $discounts = $this->configService->getCustomerInstitutionDiscounts();
        $normalizeInst = static function (string $value): string {
            $v = trim(preg_replace('/\s+/', ' ', $value) ?? $value);
            return function_exists('mb_strtolower') ? mb_strtolower($v, 'UTF-8') : strtolower($v);
        };
        $key = $normalizeInst($institucion);
        $pct = 0.0;
        foreach ($discounts as $instCfg => $pctCfg) {
            $kCfg = $normalizeInst((string) $instCfg);
            if ($kCfg === $key) {
                $pct = (float) $pctCfg;
                break;
            }
        }

        return [
            'institucion' => $institucion,
            'descuento' => max(0, min(100, round($pct, 2))),
        ];
    }

    /**
     * @param array<string,mixed> $registro
     * @param array<string,mixed> $pagos
     * @return array<string,mixed>
     */
    private function buildNormalizedPagoData(array $registro, array $pagos): array
    {
        $lineas = $this->registerModel->getPruebasLineasComerciales((string) ($registro['pruebas'] ?? ''));
        $totalBruto = 0.0;
        foreach ($lineas as $ln) {
            $totalBruto += (float) ($ln['importe'] ?? 0);
        }
        $discountInfo = $this->resolveInstitutionDiscountByPersonId((int) ($registro['person_id'] ?? 0));
        $pct = (float) ($discountInfo['descuento'] ?? 0.0);
        $totalReco = $totalBruto > 0
            ? $totalBruto
            : $this->parseMoneyInput($pagos['total_reco'] ?? 0);
        $totalDefault = round($totalReco * (1 - ($pct / 100)), 2);
        $total = array_key_exists('total', $pagos)
            ? $this->parseMoneyInput($pagos['total'])
            : $totalDefault;
        if ($total < 0) {
            $total = 0.0;
        }

        $montoPagado = $this->parseMoneyInput($pagos['monto_pagar'] ?? 0);
        if ($montoPagado < 0) {
            $montoPagado = 0.0;
        }
        $tipopago = trim((string) ($pagos['tipopago'] ?? ''));
        if ($tipopago === '4' && trim((string) ($pagos['monto_pagar'] ?? '')) === '') {
            $montoPagado = 0.0;
        }

        $saldo = round($total - $montoPagado, 2);
        if ($saldo < 0) {
            $saldo = 0.0;
        }

        return [
            'total_reco' => number_format(max(0.0, $totalReco), 2, '.', ''),
            'total' => number_format($total, 2, '.', ''),
            'monto_pagar' => number_format($montoPagado, 2, '.', ''),
            'tipopago' => $tipopago,
            'saldo' => number_format($saldo, 2, '.', ''),
            'comentarios' => $pagos['comentarios'] ?? null,
            'descuento_institucion' => $discountInfo,
        ];
    }

    private function syncDoctorCommissionForRegistro(int $registroId, int $doctorId, float $totalAmount): void
    {
        try {
            $enabled = false;
            $commissionPercent = 0.0;

            if ($doctorId > 0) {
                $doctorInfo = $this->doctorModel->find($doctorId);
                if ($doctorInfo && $this->doctorModel->supportsCommissionColumn() && isset($doctorInfo->commission_percent)) {
                    $hasCommission = isset($doctorInfo->has_commission) ? (int) $doctorInfo->has_commission : 1;
                    $commissionPercent = (float) $doctorInfo->commission_percent;
                    $enabled = $hasCommission === 1 && $commissionPercent > 0;
                }
            }

            $this->commissionModel->syncPendingCommissionForRegistro(
                $registroId,
                $doctorId,
                $totalAmount,
                $commissionPercent,
                $enabled
            );
        } catch (\Throwable $e) {
            log_message('error', 'Registers::syncDoctorCommissionForRegistro ' . $e->getMessage());
        }
    }

    /**
     * Agrega campos clínicos opcionales solo si la migración correspondiente ya existe.
     *
     * @param array<string, mixed> $registroData
     * @param array<string, mixed> $registroPost
     * @return array<string, mixed>
     */
    private function withClinicalContextFields(array $registroData, array $registroPost): array
    {
        foreach (['diagnostico_presuntivo', 'motivo_estudio'] as $field) {
            if ($this->registerModel->hasRegistroColumn($field)) {
                $value = trim((string) ($registroPost[$field] ?? ''));
                $registroData[$field] = $value !== '' ? $value : null;
            }
        }

        return $registroData;
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
            $registro = is_array($registro) ? $registro : [];
            $pagos = is_array($pagos) ? $pagos : [];
            $doctorId = $this->normalizeOptionalDoctorId($registro['doctor_id'] ?? 0);

            $registroData = [
                'person_id'  => $registro['person_id'] ?? null,
                'doctor_id'  => $doctorId,
                'pruebas'    => $registro['pruebas'] ?? null,
                'prioridad'  => (int) ($registro['prioridad'] ?? 0),
                'id_session' => session()->get('person_id'),
            ];
            $registroData = $this->withClinicalContextFields($registroData, $registro);

            $registroId = $this->registerModel->saveRegistro($registroData);
            $pagosNormalizados = $this->buildNormalizedPagoData($registroData, $pagos);
            \App\Models\AuditoriaModel::log('registers', 'crear', (string) $registroId, \App\Models\AuditoriaModel::detail([
                'paciente_id' => $registro['person_id'] ?? null,
                'doctor_id' => $doctorId,
                'pruebas' => $registro['pruebas'] ?? null,
                'prioridad' => (int)($registro['prioridad'] ?? 0),
                'total' => $pagosNormalizados['total'] ?? null,
                'monto_pagar' => $pagosNormalizados['monto_pagar'] ?? null,
                'descuento_institucion' => $pagosNormalizados['descuento_institucion'] ?? null,
            ], 'Nuevo registro de orden'));

            $pagosData = [
                'registro_id'  => $registroId,
                'total_reco'   => $pagosNormalizados['total_reco'] ?? null,
                'total'        => $pagosNormalizados['total'] ?? null,
                'monto_pagar'  => $pagosNormalizados['monto_pagar'] ?? null,
                'tipopago'     => $pagosNormalizados['tipopago'] ?? null,
                'saldo'        => $pagosNormalizados['saldo'] ?? null,
                'comentarios'  => $pagosNormalizados['comentarios'] ?? null,
            ];
            $this->registerModel->savePago($pagosData);
            $montoInicial = (float) ($pagosNormalizados['monto_pagar'] ?? 0);
            if ($montoInicial > 0) {
                $this->registerModel->insertAbonoInicial($registroId, $montoInicial, trim((string) ($pagosNormalizados['tipopago'] ?? '1')));
            }

            $this->syncDoctorCommissionForRegistro(
                $registroId,
                $doctorId,
                (float) ($pagosNormalizados['total'] ?? 0)
            );

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
            $registro = is_array($registro) ? $registro : [];
            $pagos = is_array($pagos) ? $pagos : [];
            $doctorId = $this->normalizeOptionalDoctorId($registro['doctor_id'] ?? 0);
            $currentInfo = $this->registerModel->getInfoRefill($id);
            $hasRegvalues = count($this->registerModel->getInfoAnalisis($id)) > 0;
            if ($hasRegvalues) {
                $parsePruebas = static function (?string $csv): array {
                    $ids = [];
                    foreach (explode(',', (string) $csv) as $rawId) {
                        $idPrueba = (int) trim($rawId);
                        if ($idPrueba > 0) {
                            $ids[$idPrueba] = true;
                        }
                    }

                    return array_keys($ids);
                };
                $currentPruebas = $parsePruebas((string) ($currentInfo->pruebas ?? ''));
                $postedPruebas = $parsePruebas((string) ($registro['pruebas'] ?? ''));
                $missingExisting = array_diff($currentPruebas, $postedPruebas);
                if ($missingExisting !== []) {
                    return $this->response->setJSON([
                        'success' => false,
                        'message' => 'La orden ya tiene resultados. Puede agregar más pruebas, pero no quitar pruebas existentes.',
                    ])->setStatusCode(400);
                }
            }

            $registroData = [
                'person_id'  => $registro['person_id'] ?? null,
                'doctor_id'  => $doctorId,
                'pruebas'    => $registro['pruebas'] ?? null,
                'prioridad'  => (int) ($registro['prioridad'] ?? 0),
                'id_session' => session()->get('person_id'),
            ];
            $registroData = $this->withClinicalContextFields($registroData, $registro);
            $pagosNormalizados = $this->buildNormalizedPagoData($registroData, $pagos);
            $this->registerModel->saveRegistro($registroData, $id);
            \App\Models\AuditoriaModel::log('registers', 'editar_orden', (string) $id, \App\Models\AuditoriaModel::detail([
                'paciente_id' => $registro['person_id'] ?? null,
                'doctor_id' => $doctorId,
                'pruebas' => $registro['pruebas'] ?? null,
                'prioridad' => (int)($registro['prioridad'] ?? 0),
                'total' => $pagosNormalizados['total'] ?? null,
                'monto_pagar' => $pagosNormalizados['monto_pagar'] ?? null,
                'descuento_institucion' => $pagosNormalizados['descuento_institucion'] ?? null,
            ], 'Edición de orden existente'));

            $pagosUpd = [
                'total_reco'  => $pagosNormalizados['total_reco'] ?? null,
                'total'       => $pagosNormalizados['total'] ?? null,
                'monto_pagar' => $pagosNormalizados['monto_pagar'] ?? null,
                'tipopago'    => $pagosNormalizados['tipopago'] ?? null,
                'saldo'       => $pagosNormalizados['saldo'] ?? null,
                'comentarios' => $pagosNormalizados['comentarios'] ?? null,
            ];
            $this->registerModel->updatePagoByRegistroId($id, $pagosUpd);
            $this->syncDoctorCommissionForRegistro(
                $id,
                $doctorId,
                (float) ($pagosNormalizados['total'] ?? 0)
            );

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

        $autoStats = ['aplicados' => 0, 'omitidos' => 0, 'errores' => 0];
        if (($registroId ?? 0) > 0) {
            $autoService = new AutoReactivoConsumptionService();
            $autoStats = $autoService->applyFromRegValues(
                (int) $registroId,
                is_array($data) ? $data : [],
                (int) (session()->get('person_id') ?? 0)
            );
        }

        if ($registroId !== null) {
            \App\Models\AuditoriaModel::log('registers', 'guardar_resultados', (string) $registroId, \App\Models\AuditoriaModel::detail([
                'cantidad_valores' => $valCount,
                'tiene_comentario' => ($comentario !== ''),
                'consumo_auto_aplicados' => (int) ($autoStats['aplicados'] ?? 0),
                'consumo_auto_errores' => (int) ($autoStats['errores'] ?? 0),
            ]));
        }
        return $this->response->setJSON([
            'success' => true,
            'message' => 'Guardado exitoso',
            'consumo_auto' => $autoStats,
        ]);
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
        $reportUrl = $this->publicReportViewerUrlForQr($id);
        $qrLayout  = (new \App\Services\ReportPdfLayoutService())->getActiveLayoutForRender();
        $qrPx      = \App\Services\ReportPdfLayoutService::qrImagePixelSizeFromLayout($qrLayout);
        $qrDataUri = qr_base64($reportUrl, $qrPx);
        $emitidoEn = $this->registerService->reportEmitidoEnForView($id);
        $html      = $this->registerService->renderReportPdfHtml($data, $reportUrl, $qrDataUri, $emitidoEn);
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
