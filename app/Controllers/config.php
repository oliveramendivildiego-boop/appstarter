<?php

namespace App\Controllers;

use App\Models\EmployeeModel;
use App\Models\OpcionModel;
use App\Models\PoblacionModel;
use App\Models\ReportPdfTemplateModel;
use App\Models\EnvelopeTemplateModel;
use App\Models\LeyendaCultivoModel;
use App\Models\LeyendaCultivoCategoriaModel;
use App\Models\FichaClinicaModel;
use App\Models\MetodoModel;
use App\Models\TipoMuestraModel;
use App\Models\GeneroModel;
use App\Models\CustomerModel;
use App\Libraries\TenantResolver;
use App\Services\ConfigService;
use App\Services\TenantBackupScheduleService;
use App\Services\TenantBackupService;
use App\Services\TenantConfigService;
use App\Services\GhostTenantAccessService;
use App\Services\TenantHandoffService;
use App\Services\TenantSubscriptionService;
use CodeIgniter\HTTP\ResponseInterface;

/**
 * Configuración del sistema.
 * Solo coordina: request → service → response.
 */
class Config extends SecureArea
{
    protected ?string $moduleId = 'config';

    protected ConfigService $configService;
    protected TenantConfigService $tenantConfigService;
    protected PoblacionModel $poblacionModel;
    protected OpcionModel $opcionModel;
    protected TipoMuestraModel $tipoMuestraModel;
    protected GeneroModel $generoModel;
    protected MetodoModel $metodoModel;
    protected LeyendaCultivoModel $leyendaCultivoModel;
    protected LeyendaCultivoCategoriaModel $leyendaCultivoCategoriaModel;
    protected FichaClinicaModel $fichaClinicaModel;
    protected CustomerModel $customerModel;
    protected TenantResolver $tenantResolver;

    public function __construct()
    {
        parent::__construct();
        $this->configService  = new ConfigService();
        $this->tenantConfigService = new TenantConfigService();
        $this->tenantResolver = new TenantResolver();
        $this->poblacionModel = model(PoblacionModel::class);
        $this->opcionModel        = model(OpcionModel::class);
        $this->tipoMuestraModel   = model(TipoMuestraModel::class);
        $this->generoModel        = model(GeneroModel::class);
        $this->metodoModel        = model(MetodoModel::class);
        $this->leyendaCultivoModel = model(LeyendaCultivoModel::class);
        $this->leyendaCultivoCategoriaModel = model(LeyendaCultivoCategoriaModel::class);
        $this->fichaClinicaModel = model(FichaClinicaModel::class);
        $this->customerModel      = model(CustomerModel::class);
    }

    public function index()
    {
        helper('config');

        $config = $this->configService->getAllAsArray();
        $institucionDiscountsMap = $this->configService->getCustomerInstitutionDiscounts();
        $institucionDiscounts = [];
        foreach ($institucionDiscountsMap as $inst => $pct) {
            $institucionDiscounts[] = [
                'institucion' => $inst,
                'descuento'   => round((float) $pct, 2),
            ];
        }
        $institucionesDisponibles = $this->customerModel->getInstituciones();
        foreach (array_keys($institucionDiscountsMap) as $instCfg) {
            if (!in_array($instCfg, $institucionesDisponibles, true)) {
                $institucionesDisponibles[] = $instCfg;
            }
        }
        usort($institucionesDisponibles, static fn (string $a, string $b): int => strcasecmp($a, $b));
        try {
            $poblaciones = $this->poblacionModel->getAll();
        } catch (\Throwable $e) {
            $poblaciones = [];
        }
        $editarGet = $this->request->getGet('editar');
        $editarPoblacion = ($editarGet !== null && $editarGet !== '') ? (int) $editarGet : -1;
        $editarPoblacionData = [];
        if ($editarPoblacion >= 0) {
            $row = $this->poblacionModel->getById($editarPoblacion);
            $editarPoblacionData = is_array($row) ? $row : [];
        }

        $editarTipoMuestraId = (int) ($this->request->getGet('editar_tipo') ?? 0);
        $editarTipoMuestraData = [];
        $tiposMuestraSort = strtolower(trim((string) ($this->request->getGet('tm_sort') ?? '')));
        $tiposMuestraSort = in_array($tiposMuestraSort, ['az', 'za'], true) ? $tiposMuestraSort : 'az';
        $tiposMuestraLista = [];
        try {
            $tiposMuestraLista = $this->tipoMuestraModel->getAllActive($tiposMuestraSort === 'za');
        } catch (\Throwable $e) {
            $tiposMuestraLista = [];
        }
        if ($editarTipoMuestraId > 0) {
            try {
                $rowTipo = $this->tipoMuestraModel->find($editarTipoMuestraId);
                if (is_array($rowTipo) && (int) ($rowTipo['deleted'] ?? 0) === 0) {
                    $editarTipoMuestraData = $rowTipo;
                } else {
                    $editarTipoMuestraId = 0;
                }
            } catch (\Throwable $e) {
                $editarTipoMuestraId = 0;
            }
        }

        $editarGeneroId = (int) ($this->request->getGet('editar_genero') ?? 0);
        $editarGeneroData = [];
        $generosLista = [];
        try {
            $generosLista = $this->generoModel->getAllActive();
        } catch (\Throwable $e) {
            $generosLista = [];
        }
        if ($editarGeneroId > 0) {
            try {
                $rowGenero = $this->generoModel->find($editarGeneroId);
                if (is_array($rowGenero) && (int) ($rowGenero['deleted'] ?? 0) === 0) {
                    $editarGeneroData = $rowGenero;
                } else {
                    $editarGeneroId = 0;
                }
            } catch (\Throwable $e) {
                $editarGeneroId = 0;
            }
        }

        $editarMetodoId = (int) ($this->request->getGet('editar_metodo') ?? 0);
        $editarMetodoData = [];
        $metodosSort = strtolower(trim((string) ($this->request->getGet('met_sort') ?? '')));
        $metodosSort = in_array($metodosSort, ['az', 'za'], true) ? $metodosSort : 'az';
        $metodosLista = [];
        try {
            $metodosLista = $this->metodoModel->getAllActive($metodosSort === 'za');
        } catch (\Throwable $e) {
            $metodosLista = [];
        }
        if ($editarMetodoId > 0) {
            try {
                $rowMet = $this->metodoModel->find($editarMetodoId);
                if (is_array($rowMet) && (int) ($rowMet['deleted'] ?? 0) === 0) {
                    $editarMetodoData = $rowMet;
                } else {
                    $editarMetodoId = 0;
                }
            } catch (\Throwable $e) {
                $editarMetodoId = 0;
            }
        }

        $editarLeyendaCultivoId = (int) ($this->request->getGet('editar_leyenda_cultivo') ?? 0);
        $editarLeyendaCultivoData = [];
        $leyendasCultivoLista = [];
        $leyendasCultivoCategorias = [];
        $editarLeyendaCultivoCategoriaId = (int) ($this->request->getGet('editar_leyenda_cultivo_categoria') ?? 0);
        $editarLeyendaCultivoCategoriaData = [];
        try {
            $this->leyendaCultivoCategoriaModel->ensureTable();
            $this->leyendaCultivoModel->ensureTable();
            $leyendasCultivoCategorias = $this->leyendaCultivoCategoriaModel->getAll();
            $leyendasCultivoLista = $this->leyendaCultivoModel->getAll();
        } catch (\Throwable $e) {
            $leyendasCultivoLista = [];
            $leyendasCultivoCategorias = [];
        }
        if ($editarLeyendaCultivoId > 0) {
            $rowLc = $this->leyendaCultivoModel->getById($editarLeyendaCultivoId);
            if (is_array($rowLc)) {
                $editarLeyendaCultivoData = $rowLc;
            } else {
                $editarLeyendaCultivoId = 0;
            }
        }
        if ($editarLeyendaCultivoCategoriaId > 0) {
            $rowCat = $this->leyendaCultivoCategoriaModel->getById($editarLeyendaCultivoCategoriaId);
            if (is_array($rowCat)) {
                $editarLeyendaCultivoCategoriaData = $rowCat;
            } else {
                $editarLeyendaCultivoCategoriaId = 0;
            }
        }

        $opcionesPageData = $this->loadOpcionesPageForView($this->resolveOpcionesPage(), 10, $this->resolveOpcionesSort());
        $opciones = $opcionesPageData['items'];
        $tenants = $this->tenantConfigService->getAll();
        $canManageTenants = $this->canManageTenants();
        $tenantEditId = (int) ($this->request->getGet('tenant_edit') ?? 0);
        $tenantEditData = [];
        if ($canManageTenants && $tenantEditId > 0) {
            foreach ($tenants as $tenant) {
                if ((int) ($tenant['id'] ?? 0) === $tenantEditId) {
                    $tenantEditData = $tenant;
                    break;
                }
            }
        }

        $tab = $this->request->getGet('tab') ?: 'sistema';
        if ($editarGet !== null && $editarGet !== '') {
            $tab = 'poblacion';
        }
        if ($canManageTenants && $tenantEditId > 0) {
            $tab = 'tenants';
        }
        if (!$canManageTenants && $tab === 'tenants') {
            $tab = 'sistema';
        }
        if ($editarTipoMuestraId > 0) {
            $tab = 'tipos_muestra';
        }
        if ($editarGeneroId > 0) {
            $tab = 'generos';
        }
        if ($editarMetodoId > 0) {
            $tab = 'metodos_prueba';
        }
        if ($editarLeyendaCultivoId > 0) {
            $tab = 'leyendas_cultivo';
        }
        if ($editarLeyendaCultivoCategoriaId > 0) {
            $tab = 'leyendas_cultivo';
        }
        if (($this->request->getGet('tab') ?: '') === 'leyendas_cultivo') {
            $tab = 'leyendas_cultivo';
        }

        $editarFichaClinicaId = (int) ($this->request->getGet('editar_ficha_clinica') ?? 0);
        $editarFichaClinicaData = [];
        $fichasClinicasLista = [];
        $fichaClinicaPruebasCatalog = [];
        $fichaClinicaPruebasLinked = [];
        $fichaClinicaPruebasCounts = [];
        try {
            $this->fichaClinicaModel->ensureTable();
            $this->fichaClinicaModel->ensurePruebasTable();
            $fichasClinicasLista = $this->fichaClinicaModel->getAll();
            $fichaClinicaPruebasCounts = $this->fichaClinicaModel->getPruebasCountByFicha();
        } catch (\Throwable $e) {
            $fichasClinicasLista = [];
            $fichaClinicaPruebasCounts = [];
        }
        if ($editarFichaClinicaId > 0) {
            $rowFc = $this->fichaClinicaModel->getById($editarFichaClinicaId);
            if (is_array($rowFc)) {
                $editarFichaClinicaData = $rowFc;
                $tab = 'ficha_clinica';
                try {
                    $fichaClinicaPruebasCatalog = model(\App\Models\LabotestModel::class)->getGroupedByCategory();
                    $fichaClinicaPruebasLinked = $this->fichaClinicaModel->getLinkedPruebas($editarFichaClinicaId);
                } catch (\Throwable $e) {
                    $fichaClinicaPruebasCatalog = [];
                    $fichaClinicaPruebasLinked = [];
                }
            } else {
                $editarFichaClinicaId = 0;
            }
        }
        if (($this->request->getGet('tab') ?: '') === 'ficha_clinica') {
            $tab = 'ficha_clinica';
        }

        $opcionesMap = [];
        try {
            $opcionesMap = model(\App\Models\LabotestModel::class)->getOpciones();
        } catch (\Throwable $e) {
            $opcionesMap = [];
        }
        if (($this->request->getGet('tab') ?: '') === 'tenant-subscriptions') {
            $tab = 'tenant_subscriptions';
        }
        if ($canManageTenants && $tenantEditId <= 0 && ($this->request->getGet('tab') ?: '') === 'tenant_subscriptions') {
            $tab = 'tenant_subscriptions';
        }
        if (! $canManageTenants && $tab === 'tenant_subscriptions') {
            $tab = 'sistema';
        }
        if (($this->request->getGet('tab') ?: '') === 'lab_validacion') {
            $tab = 'lab_validacion';
        }
        if (($this->request->getGet('tab') ?: '') === 'institucion_descuentos') {
            $tab = 'institucion_descuentos';
        }
        if (($this->request->getGet('tab') ?: '') === 'tenant_home_broadcast') {
            $tab = 'tenant_home_broadcast';
        }
        if (! $canManageTenants && $tab === 'tenant_home_broadcast') {
            $tab = 'sistema';
        }
        if (($this->request->getGet('tab') ?: '') === 'sobres') {
            $tab = 'sobres';
        }
        if (($this->request->getGet('tab') ?: '') === 'notificaciones_analisis') {
            $tab = 'notificaciones_analisis';
        }

        $subSvc                 = new TenantSubscriptionService();
        $subscription_payments  = $canManageTenants
            ? $subSvc->listPaymentsWithTenantNames($subSvc->listPaymentsForManagement())
            : [];
        $billable_tenants = $canManageTenants ? $subSvc->getBillableTenants() : [];
        $appCfg                 = model(\App\Models\AppConfigModel::class);
        $rawSubAlert            = $appCfg->getValue('dias_alerta_suscripcion_tenant');
        $tenant_subscription_alert_days_form = $rawSubAlert !== '' ? max(1, min(90, (int) $rawSubAlert)) : $subSvc->getSubscriptionWarningDays();

        $pdf_templates = [];
        try {
            $pdf_templates = model(ReportPdfTemplateModel::class)->orderBy('name', 'ASC')->findAll();
        } catch (\Throwable $e) {
            $pdf_templates = [];
        }

        $envelope_templates = [];
        $activeEnvelopeTemplateId = 0;
        $printEnvelopeTemplateId  = 0;
        $envelope_db_error = null;
        try {
            $envelope_templates = model(EnvelopeTemplateModel::class)->orderBy('name', 'ASC')->findAll();
            $envelopeRenderSvc          = new \App\Services\EnvelopeRenderService();
            $activeEnvelopeTemplateId   = (int) model(\App\Models\AppConfigModel::class)->getValue('active_envelope_template_id');
            $printEnvelopeTemplateId    = $envelopeRenderSvc->resolvePrintTemplateId();
        } catch (\Throwable $e) {
            $envelope_templates = [];
            $envelope_db_error = $e->getMessage();
        }

        $labValidation = $this->configService->getLabValidationStateForView();

        $tenantBackupSchedule     = [];
        $tenantBackupTimezoneId   = '';
        if ($canManageTenants) {
            $tbsSvc                 = new TenantBackupScheduleService();
            $tenantBackupSchedule   = $tbsSvc->getFormState();
            $tenantBackupTimezoneId = $tbsSvc->getResolvedTimezoneIdentifier();
        }

        $tenantHomeBroadcastForm = $canManageTenants
            ? (new \App\Services\TenantHomeBroadcastService())->getFormState()
            : [];

        return view('config/manage', [
            'config'               => $config,
            'lab_validators'       => $labValidation['validators'],
            'lab_approvers'        => $labValidation['approvers'],
            'pdf_templates'        => $pdf_templates,
            'envelope_templates'           => $envelope_templates,
            'active_envelope_template_id'  => $activeEnvelopeTemplateId,
            'print_envelope_template_id'   => $printEnvelopeTemplateId,
            'envelope_db_error'            => $envelope_db_error,
            'poblaciones'          => $poblaciones,
            'editar_poblacion'     => $editarPoblacion,
            'editar_poblacion_data'=> $editarPoblacionData,
            'tipos_muestra'        => $tiposMuestraLista,
            'tipos_muestra_sort'   => $tiposMuestraSort,
            'editar_tipo_muestra'  => $editarTipoMuestraId,
            'editar_tipo_muestra_data' => $editarTipoMuestraData,
            'generos'              => $generosLista,
            'editar_genero'        => $editarGeneroId,
            'editar_genero_data'   => $editarGeneroData,
            'metodos_prueba'       => $metodosLista,
            'metodos_sort'         => $metodosSort,
            'editar_metodo'        => $editarMetodoId,
            'editar_metodo_data'   => $editarMetodoData,
            'leyendas_cultivo'     => $leyendasCultivoLista,
            'leyendas_cultivo_categorias' => $leyendasCultivoCategorias,
            'editar_leyenda_cultivo' => $editarLeyendaCultivoId,
            'editar_leyenda_cultivo_data' => $editarLeyendaCultivoData,
            'editar_leyenda_cultivo_categoria' => $editarLeyendaCultivoCategoriaId,
            'editar_leyenda_cultivo_categoria_data' => $editarLeyendaCultivoCategoriaData,
            'fichas_clinicas'      => $fichasClinicasLista,
            'editar_ficha_clinica' => $editarFichaClinicaId,
            'editar_ficha_clinica_data' => $editarFichaClinicaData,
            'ficha_clinica_pruebas_catalog' => $fichaClinicaPruebasCatalog,
            'ficha_clinica_pruebas_linked'  => $fichaClinicaPruebasLinked,
            'ficha_clinica_pruebas_counts'  => $fichaClinicaPruebasCounts,
            'opciones_map'         => $opcionesMap,
            'opciones'             => $opciones,
            'opciones_pagination'  => $opcionesPageData['pagination'],
            'tenants'              => $tenants,
            'tenant_edit_data'     => $tenantEditData,
            'can_manage_tenants'   => $canManageTenants,
            'subscription_payments'=> $subscription_payments,
            'billable_tenants'     => $billable_tenants,
            'tenant_subscription_alert_days_form' => $tenant_subscription_alert_days_form,
            'tenant_subscription_resumen_admin'   => $canManageTenants && $subSvc->isMultiTenant()
                ? $subSvc->getBillableTenantsSuscripcionResumen($subSvc->getSubscriptionWarningDays())
                : [],
            'tenant_backup_schedule'   => $tenantBackupSchedule,
            'tenant_backup_timezone_id' => $tenantBackupTimezoneId,
            'tenant_backup_cron_url'    => $canManageTenants ? site_url('cron/tenant-backup-schedule') : '',
            'tenant_backup_cron_ready'  => $canManageTenants && trim((string) env('tenantBackup.cronKey', '')) !== '',
            'tenant_home_broadcast_form' => $tenantHomeBroadcastForm,
            'active_tab'           => $tab,
            'timezone_options'     => get_timezone_options(),
            'theme_palette'        => get_theme_color_palette(),
            'instituciones_disponibles' => $institucionesDisponibles,
            'institucion_descuentos'    => $institucionDiscounts,
            'delivery_notifications_enabled' => ($config[\App\Services\DeliveryNotificationService::KEY_ENABLED] ?? '0') === '1',
            'delivery_notifications_scope' => \App\Services\DeliveryNotificationService::normalizeScope(
                (string) ($config[\App\Services\DeliveryNotificationService::KEY_SCOPE] ?? 'all')
            ),
            'allowed_modules'      => $this->allowed_modules,
            'user_info'            => $this->user_info,
            'current_module'       => 'config',
        ]);
    }

    /**
     * Carga datos de opciones (tipos de resultado) para la vista
     */
    private function enrichOpcionesForView(array $opciones): array
    {
        foreach ($opciones as &$o) {
            $tabla = trim($o['tabla'] ?? '');
            $o['valores'] = ($tabla === 'opcion_valores')
                ? $this->opcionModel->getValores((int) $o['opciones_id'])
                : ($this->opcionModel->getOpcionConValores((int) $o['opciones_id'])['valores'] ?? []);
            $o['usa_valores_genericos'] = ($tabla === 'opcion_valores');
            $o['usa_tabla_sistema']     = in_array($tabla, ['opcpositivo', 'opcreactivo'], true);
            $o['tabla_sistema']         = $o['usa_tabla_sistema'] ? $tabla : '';
            $o['editable'] = $this->opcionModel->isEditable((int) $o['opciones_id']);
        }
        return $opciones;
    }

    private function loadOpcionesForView(): array
    {
        return $this->enrichOpcionesForView($this->opcionModel->findAll());
    }

    private function loadOpcionesPageForView(int $page, int $perPage = 10, string $sort = ''): array
    {
        $page = max(1, $page);
        $perPage = max(1, $perPage);
        $total = (int) $this->opcionModel->countAllResults();
        $pages = max(1, (int) ceil($total / $perPage));
        if ($page > $pages) {
            $page = $pages;
        }
        $offset = ($page - 1) * $perPage;
        if ($sort === 'az' || $sort === 'za') {
            $this->opcionModel->orderBy('opciones', $sort === 'za' ? 'DESC' : 'ASC');
        } else {
            $this->opcionModel->orderBy('opciones_id', 'ASC');
        }
        $rows = $this->opcionModel->findAll($perPage, $offset);

        return [
            'items' => $this->enrichOpcionesForView($rows),
            'pagination' => [
                'page' => $page,
                'per_page' => $perPage,
                'total' => $total,
                'pages' => $pages,
                'sort' => $sort,
            ],
        ];
    }

    private function resolveOpcionesPage(): int
    {
        $raw = $this->request->getPost('opciones_page');
        if ($raw === null || $raw === '') {
            $raw = $this->request->getGet('opciones_page');
        }
        $page = (int) $raw;
        return $page > 0 ? $page : 1;
    }

    private function resolveOpcionesSort(): string
    {
        $raw = $this->request->getPost('opciones_sort');
        if ($raw === null || $raw === '') {
            $raw = $this->request->getGet('opciones_sort');
        }
        $sort = strtolower(trim((string) $raw));
        return in_array($sort, ['az', 'za'], true) ? $sort : '';
    }

    private function opcionesTabUrl(?int $page = null): string
    {
        $targetPage = $page ?? $this->resolveOpcionesPage();
        $targetPage = max(1, (int) $targetPage);
        $sort = $this->resolveOpcionesSort();
        return 'config?tab=opciones&opciones_page=' . $targetPage
            . ($sort !== '' ? '&opciones_sort=' . $sort : '');
    }

    private function shouldReturnJson(): bool
    {
        $accept = strtolower((string) $this->request->getHeaderLine('Accept'));
        return $this->request->isAJAX()
            || strpos($accept, 'application/json') !== false;
    }

    private function buildOpcionesPayload(bool $success, string $message): array
    {
        $pageData = $this->loadOpcionesPageForView($this->resolveOpcionesPage(), 10, $this->resolveOpcionesSort());
        return [
            'success'    => $success,
            'message'    => $message,
            'html'       => view('config/partial_opciones', [
                'opciones' => $pageData['items'],
                'opciones_pagination' => $pageData['pagination'],
            ]),
            'csrf_token' => csrf_hash(),
            'csrf_name'  => csrf_token(),
        ];
    }

    public function save(): ResponseInterface
    {
        $validation = \Config\Services::validation();
        $validation->setRules([
            'company'  => 'required|min_length[2]|max_length[255]',
            'sucursal' => 'permit_empty|max_length[255]',
        ]);

        if (!$validation->withRequest($this->request)->run()) {
            return $this->response
                ->setJSON([
                    'success'    => false,
                    'message'    => implode(', ', $validation->getErrors()),
                    'csrf_token'  => csrf_hash(),
                    'csrf_name'   => csrf_token(),
                ])
                ->setStatusCode(400);
        }

        $result = $this->configService->saveFromRequest(
            $this->request->getPost(),
            $this->request->getFile('logo_upload')
        );

        if ($result['success'] ?? false) {
            \App\Models\AuditoriaModel::log('config', 'actualizar', null, 'company,logo,theme');
        }
        $result['csrf_token'] = csrf_hash();
        $result['csrf_name']  = csrf_token();
        $statusCode = $result['success'] ? 200 : 500;
        return $this->response
            ->setJSON($result)
            ->setStatusCode($statusCode);
    }

    /**
     * Guarda validadores y aprobadores (pestaña Validación y aprobación).
     */
    public function saveLabValidation(): ResponseInterface
    {
        if (!$this->request->is('post')) {
            return redirect()->to('config?tab=lab_validacion');
        }

        $result = $this->configService->saveLabValidationFromRequest($this->request->getPost(), $this->request);
        if ($result['success'] ?? false) {
            \App\Models\AuditoriaModel::log('config', 'lab_validacion_actualizar', null, 'lab_validators_json,lab_approvers_json');
        }

        if ($result['success'] ?? false) {
            return redirect()->to('config?tab=lab_validacion&saved=' . time())->with('success', $result['message']);
        }

        return redirect()->to('config?tab=lab_validacion')->with('error', $result['message']);
    }

    /**
     * Sube sello de un responsable al instante (AJAX).
     */
    public function uploadLabApproverSeal(): ResponseInterface
    {
        if (!$this->request->is('post')) {
            return $this->response->setJSON(['success' => false, 'message' => 'Método no permitido.'])
                ->setStatusCode(405);
        }

        $result = $this->configService->uploadLabApproverImageFromRequest(
            $this->request->getPost(),
            $this->request,
            'seal'
        );
        $result['csrf_token'] = csrf_hash();
        $result['csrf_name']  = csrf_token();

        return $this->response->setJSON($result)->setStatusCode(($result['success'] ?? false) ? 200 : 400);
    }

    /**
     * Sube firma de un responsable al instante (AJAX).
     */
    public function uploadLabApproverSignature(): ResponseInterface
    {
        if (!$this->request->is('post')) {
            return $this->response->setJSON(['success' => false, 'message' => 'Método no permitido.'])
                ->setStatusCode(405);
        }

        $result = $this->configService->uploadLabApproverImageFromRequest(
            $this->request->getPost(),
            $this->request,
            'signature'
        );
        $result['csrf_token'] = csrf_hash();
        $result['csrf_name']  = csrf_token();

        return $this->response->setJSON($result)->setStatusCode(($result['success'] ?? false) ? 200 : 400);
    }

    /**
     * Elimina sello de un responsable al instante (AJAX).
     */
    public function deleteLabApproverSeal(): ResponseInterface
    {
        if (!$this->request->is('post')) {
            return $this->response->setJSON(['success' => false, 'message' => 'Método no permitido.'])
                ->setStatusCode(405);
        }

        $result = $this->configService->deleteLabApproverImageFromRequest(
            $this->request->getPost(),
            'seal'
        );
        $result['csrf_token'] = csrf_hash();
        $result['csrf_name']  = csrf_token();

        return $this->response->setJSON($result)->setStatusCode(($result['success'] ?? false) ? 200 : 400);
    }

    /**
     * Elimina firma de un responsable al instante (AJAX).
     */
    public function deleteLabApproverSignature(): ResponseInterface
    {
        if (!$this->request->is('post')) {
            return $this->response->setJSON(['success' => false, 'message' => 'Método no permitido.'])
                ->setStatusCode(405);
        }

        $result = $this->configService->deleteLabApproverImageFromRequest(
            $this->request->getPost(),
            'signature'
        );
        $result['csrf_token'] = csrf_hash();
        $result['csrf_name']  = csrf_token();

        return $this->response->setJSON($result)->setStatusCode(($result['success'] ?? false) ? 200 : 400);
    }

    /**
     * Guarda apariencia del sistema (colores, fuente, menú).
     */
    public function saveUiStyle(): ResponseInterface
    {
        if (! $this->configService->saveUiStyleFromRequest($this->request->getPost())) {
            return redirect()->to('config?tab=estilo')->with('error', lang('Config.config_error'));
        }
        \App\Models\AuditoriaModel::log('config', 'apariencia_actualizar', null, 'ui_style');

        return redirect()->to('config?tab=estilo')->with('success', lang('Config.config_saved'));
    }

    /**
     * Guarda estilo del comprobante PDF (recibo/factura).
     */
    public function saveComprobanteStyle(): ResponseInterface
    {
        if (! $this->configService->saveComprobanteStyleFromRequest($this->request->getPost())) {
            return redirect()->to('config?tab=comprobante')->with('error', lang('Config.config_error'));
        }
        \App\Models\AuditoriaModel::log('config', 'comprobante_estilo_actualizar', null, 'comprobante_style');

        return redirect()->to('config?tab=comprobante')->with('success', lang('Config.config_saved'));
    }

    /**
     * Exporta el estilo del comprobante (colores, textos, matriz y numeración) como JSON.
     */
    public function exportComprobanteStyle(): ResponseInterface
    {
        $payload = [
            'schema'       => 'lab-config-comprobante-v1',
            'generated_at' => date('c'),
            'comprobante'  => $this->configService->buildComprobanteStyleExportData(),
        ];
        $json = json_encode($payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        if ($json === false) {
            return redirect()->to('config?tab=comprobante')->with('error', 'No se pudo generar el archivo de exportación.');
        }

        \App\Models\AuditoriaModel::log('config', 'comprobante_estilo_exportar', null);
        $filename = 'comprobante_config_' . date('Ymd_His') . '.json';

        return $this->response
            ->setHeader('Content-Type', 'application/json; charset=utf-8')
            ->setHeader('Content-Disposition', 'attachment; filename="' . $filename . '"')
            ->setBody($json);
    }

    /**
     * Importa estilo del comprobante desde un JSON exportado previamente.
     */
    public function importComprobanteStyle(): ResponseInterface
    {
        $file = $this->request->getFile('comprobante_file');
        if ($file === null || $file->getError() === UPLOAD_ERR_NO_FILE) {
            return redirect()->to('config?tab=comprobante')->with('error', 'Seleccione un archivo JSON para importar.');
        }
        if (! $file->isValid()) {
            return redirect()->to('config?tab=comprobante')->with('error', 'El archivo no se subió correctamente.');
        }
        if (strtolower((string) $file->getExtension()) !== 'json') {
            return redirect()->to('config?tab=comprobante')->with('error', 'Formato inválido. Debe ser un archivo .json.');
        }

        $raw = @file_get_contents($file->getTempName());
        if ($raw === false || trim($raw) === '') {
            return redirect()->to('config?tab=comprobante')->with('error', 'El archivo está vacío o no se pudo leer.');
        }

        $data = json_decode($raw, true);
        if (! is_array($data)) {
            return redirect()->to('config?tab=comprobante')->with('error', 'Archivo JSON inválido para importación del comprobante.');
        }

        $comprobante = null;
        if (isset($data['comprobante']) && is_array($data['comprobante'])) {
            $comprobante = $data['comprobante'];
        } elseif (isset($data['primary_color']) || isset($data['comprobante_primary_color']) || isset($data['layout'])) {
            $comprobante = $data;
        }

        if ($comprobante === null) {
            return redirect()->to('config?tab=comprobante')->with('error', 'El JSON no contiene datos de comprobante reconocibles.');
        }

        $schema = trim((string) ($data['schema'] ?? ''));
        if ($schema !== '' && $schema !== 'lab-config-comprobante-v1') {
            return redirect()->to('config?tab=comprobante')->with(
                'error',
                'Versión de archivo no compatible (' . $schema . '). Use un export generado por este sistema.'
            );
        }

        if (! $this->configService->importComprobanteStyleFromPayload($comprobante)) {
            return redirect()->to('config?tab=comprobante')->with('error', lang('Config.config_error'));
        }

        \App\Models\AuditoriaModel::log('config', 'comprobante_estilo_importar', null, 'comprobante_style');

        return redirect()->to('config?tab=comprobante')->with('success', 'Estilo de comprobante importado correctamente.');
    }

    /**
     * Genera respaldo SQL de la base de datos (solo para usuarios con permiso config)
     */
    public function backup()
    {
        $db = config('Database')->default;
        $host = $db['hostname'] ?? 'localhost';
        $user = $db['username'] ?? 'root';
        $pass = $db['password'] ?? '';
        $name = $db['database'] ?? 'laboratorio';
        $file = WRITEPATH . 'backup_' . date('Y-m-d_His') . '.sql';
        $passOpt = $pass ? ' -p' . str_replace(["'", '\\'], ["\\'", '\\\\'], $pass) : '';
        $cmd = sprintf('mysqldump -h %s -u %s%s %s > %s 2>nul', $host, $user, $passOpt, $name, $file);
        if (PHP_OS_FAMILY !== 'Windows') {
            $cmd = sprintf('mysqldump -h %s -u %s%s %s > %s 2>/dev/null', $host, $user, $passOpt, $name, $file);
        }
        @exec($cmd);
        if (is_file($file)) {
            $content = file_get_contents($file);
            @unlink($file);
            return $this->response
                ->setHeader('Content-Type', 'application/sql')
                ->setHeader('Content-Disposition', 'attachment; filename="respaldo_' . date('Y-m-d') . '.sql"')
                ->setBody($content)
                ->setStatusCode(200);
        }
        return redirect()->to('config')->with('error', 'No se pudo generar el respaldo. Verifique que mysqldump esté instalado.');
    }

    /**
     * Genera respaldo SQL de todos los tenants activos (incluido el default) en un ZIP.
     */
    public function backupTenants()
    {
        if (! $this->canManageTenants()) {
            return redirect()->to('config')->with('error', 'No tiene permiso para respaldar tenants.');
        }

        $svc    = new TenantBackupService();
        $result = $svc->buildZipBinary(false);
        if (! ($result['success'] ?? false) || ($result['binary'] ?? null) === null) {
            return redirect()->to('config?tab=tenants')->with('error', (string) ($result['message'] ?? 'No se pudo generar el respaldo.'));
        }

        $status = 'ok:' . (int) ($result['ok'] ?? 0) . ',fail:' . (int) ($result['fail'] ?? 0);
        \App\Models\AuditoriaModel::log('config', 'tenant_backup_descargar', null, $status);
        $filename = 'respaldo_tenants_' . date('Y-m-d_His') . '.zip';

        return $this->response
            ->setHeader('Content-Type', 'application/zip')
            ->setHeader('Content-Disposition', 'attachment; filename="' . $filename . '"')
            ->setBody((string) $result['binary'])
            ->setStatusCode(200);
    }

    /**
     * Guarda programación de respaldos automáticos (app_config).
     */
    public function saveTenantBackupSchedule(): ResponseInterface
    {
        if (! $this->canManageTenants()) {
            return redirect()->to('config')->with('error', 'No tiene permiso para modificar esta configuración.');
        }

        $svc    = new TenantBackupScheduleService();
        $result = $svc->saveFromPost($this->request->getPost());
        if ($result['success'] ?? false) {
            $this->configService->invalidateCache();
            \App\Models\AuditoriaModel::log('config', 'tenant_backup_schedule_guardar', null, (string) (($result['data'][TenantBackupScheduleService::$keyFrequency] ?? '') . '@' . ($result['data'][TenantBackupScheduleService::$keyTime] ?? '')));

            return redirect()->to('config?tab=tenants')->with('success', $result['message']);
        }

        return redirect()->to('config?tab=tenants')->with('error', (string) ($result['message'] ?? 'Error al guardar.'));
    }

    /**
     * Guardar o actualizar grupo de población
     */
    public function savePoblacion(): ResponseInterface
    {
        $idRaw = $this->request->getPost('id_poblacion');
        $id = $idRaw !== null && $idRaw !== '' ? (int) $idRaw : -1;
        $name = trim($this->request->getPost('name') ?? '');
        if ($name === '') {
            return redirect()->to('config?tab=poblacion')->with('error', 'El nombre del grupo es obligatorio.');
        }
        $data = [
            'id_poblacion' => $id >= 0 ? $id : $this->poblacionModel->getNextId(),
            'name'         => $name,
            'edad_min'     => $this->request->getPost('edad_min'),
            'edad_max'     => $this->request->getPost('edad_max'),
            'unidad'       => $this->request->getPost('unidad'),
            'orden'        => (int) ($this->request->getPost('orden') ?? 0),
        ];
        try {
            $this->poblacionModel->savePoblacion($data, $id >= 0 ? $id : null);
            \App\Models\AuditoriaModel::log('config', 'poblacion_' . ($id >= 0 ? 'actualizar' : 'crear'), (string) $data['id_poblacion']);
            return redirect()->to('config?tab=poblacion')->with('success', 'Población guardada correctamente.');
        } catch (\Throwable $e) {
            return redirect()->to('config?tab=poblacion' . ($id >= 0 ? '&editar=' . $id : ''))->with('error', 'Error al guardar. Ejecute la migración de poblacion si no lo ha hecho.');
        }
    }

    /**
     * Eliminar (soft) grupo de población
     */
    public function deletePoblacion($id)
    {
        $id = (int) $id;
        $this->poblacionModel->deletePoblacion($id);
        \App\Models\AuditoriaModel::log('config', 'poblacion_eliminar', (string) $id);
        return redirect()->to('config?tab=poblacion')->with('success', 'Población eliminada.');
    }

    public function saveTipoMuestra(): ResponseInterface
    {
        $nombre = trim($this->request->getPost('nombre') ?? '');
        $len    = function_exists('mb_strlen') ? mb_strlen($nombre, 'UTF-8') : strlen($nombre);
        if ($nombre === '' || $len > 128) {
            return redirect()->to('config?tab=tipos_muestra')->with('error', 'El nombre es obligatorio (máx. 128 caracteres).');
        }
        $id = (int) ($this->request->getPost('tipo_muestra_id') ?? 0);
        if ($id > 0) {
            $ex = $this->tipoMuestraModel->find($id);
            if (!is_array($ex) || (int) ($ex['deleted'] ?? 0) !== 0) {
                return redirect()->to('config?tab=tipos_muestra')->with('error', 'El tipo de muestra no existe o fue eliminado.');
            }
        }
        try {
            $saved = $this->tipoMuestraModel->saveTipo($nombre, $id > 0 ? $id : null);
            if ($saved === false) {
                return redirect()->to('config?tab=tipos_muestra')->with('error', 'No se pudo guardar el tipo de muestra.');
            }
            \App\Models\AuditoriaModel::log('config', $id > 0 ? 'tipo_muestra_actualizar' : 'tipo_muestra_crear', (string) $saved);
        } catch (\Throwable $e) {
            return redirect()->to('config?tab=tipos_muestra' . ($id > 0 ? '&editar_tipo=' . $id : ''))->with('error', 'Error al guardar. Ejecute las migraciones si la tabla tipo_muestra no existe.');
        }

        return redirect()->to('config?tab=tipos_muestra')->with('success', 'Tipo de muestra guardado.');
    }

    public function deleteTipoMuestra($id): ResponseInterface
    {
        $id = (int) $id;
        try {
            $result = $this->tipoMuestraModel->softDeleteIfUnused($id);
        } catch (\Throwable $e) {
            return redirect()->to('config?tab=tipos_muestra')->with('error', 'No se pudo eliminar.');
        }
        if ($result['success']) {
            \App\Models\AuditoriaModel::log('config', 'tipo_muestra_eliminar', (string) $id);
        }

        return redirect()->to('config?tab=tipos_muestra')->with($result['success'] ? 'success' : 'error', $result['message']);
    }

    public function saveGenero(): ResponseInterface
    {
        $nombre = trim($this->request->getPost('nombre') ?? '');
        $len    = function_exists('mb_strlen') ? mb_strlen($nombre, 'UTF-8') : strlen($nombre);
        if ($nombre === '' || $len > 64) {
            return redirect()->to('config?tab=generos')->with('error', 'El nombre es obligatorio (máx. 64 caracteres).');
        }
        $id = (int) ($this->request->getPost('genero_id') ?? 0);
        $orden = (int) ($this->request->getPost('orden') ?? 0);
        if ($id > 0) {
            $ex = $this->generoModel->find($id);
            if (!is_array($ex) || (int) ($ex['deleted'] ?? 0) !== 0) {
                return redirect()->to('config?tab=generos')->with('error', 'El género no existe o fue eliminado.');
            }
        }
        try {
            $saved = $this->generoModel->saveGenero($nombre, $orden, $id > 0 ? $id : null);
            if ($saved === false) {
                return redirect()->to('config?tab=generos')->with('error', 'No se pudo guardar el género.');
            }
            \App\Models\AuditoriaModel::log('config', $id > 0 ? 'genero_actualizar' : 'genero_crear', (string) $saved);
        } catch (\Throwable $e) {
            return redirect()->to('config?tab=generos' . ($id > 0 ? '&editar_genero=' . $id : ''))->with('error', 'Error al guardar. Ejecute las migraciones si la tabla genero no existe.');
        }

        return redirect()->to('config?tab=generos')->with('success', 'Género guardado.');
    }

    public function deleteGenero($id): ResponseInterface
    {
        $id = (int) $id;
        try {
            $result = $this->generoModel->softDeleteIfUnused($id);
        } catch (\Throwable $e) {
            return redirect()->to('config?tab=generos')->with('error', 'No se pudo eliminar.');
        }
        if ($result['success']) {
            \App\Models\AuditoriaModel::log('config', 'genero_eliminar', (string) $id);
        }

        return redirect()->to('config?tab=generos')->with($result['success'] ? 'success' : 'error', $result['message']);
    }

    public function saveMetodo(): ResponseInterface
    {
        $nombre = trim($this->request->getPost('nombre') ?? '');
        $len    = function_exists('mb_strlen') ? mb_strlen($nombre, 'UTF-8') : strlen($nombre);
        if ($nombre === '' || $len > 128) {
            return redirect()->to('config?tab=metodos_prueba')->with('error', 'El nombre es obligatorio (máx. 128 caracteres).');
        }
        $id = (int) ($this->request->getPost('metodo_id') ?? 0);
        if ($id > 0) {
            $ex = $this->metodoModel->find($id);
            if (! is_array($ex) || (int) ($ex['deleted'] ?? 0) !== 0) {
                return redirect()->to('config?tab=metodos_prueba')->with('error', 'El método no existe o fue eliminado.');
            }
        }
        try {
            $saved = $this->metodoModel->saveMetodo($nombre, $id > 0 ? $id : null);
            if ($saved === false) {
                return redirect()->to('config?tab=metodos_prueba')->with('error', 'No se pudo guardar el método.');
            }
            \App\Models\AuditoriaModel::log('config', $id > 0 ? 'metodo_actualizar' : 'metodo_crear', (string) $saved);
        } catch (\Throwable $e) {
            return redirect()->to('config?tab=metodos_prueba' . ($id > 0 ? '&editar_metodo=' . $id : ''))->with('error', 'Error al guardar. Ejecute las migraciones si la tabla metodo no existe.');
        }

        return redirect()->to('config?tab=metodos_prueba')->with('success', 'Método guardado.');
    }

    public function deleteMetodo($id): ResponseInterface
    {
        $id = (int) $id;
        try {
            $result = $this->metodoModel->softDeleteIfUnused($id);
        } catch (\Throwable $e) {
            return redirect()->to('config?tab=metodos_prueba')->with('error', 'No se pudo eliminar.');
        }
        if ($result['success']) {
            \App\Models\AuditoriaModel::log('config', 'metodo_eliminar', (string) $id);
        }

        return redirect()->to('config?tab=metodos_prueba')->with($result['success'] ? 'success' : 'error', $result['message']);
    }

    public function saveLeyendaCultivo()
    {
        $id = (int) ($this->request->getPost('leyenda_cultivo_id') ?? 0);
        $titulo = trim((string) ($this->request->getPost('titulo') ?? ''));
        $categoriaId = (int) ($this->request->getPost('leyenda_cultivo_categoria_id') ?? 0);
        if ($titulo === '') {
            return redirect()->to('config?tab=leyendas_cultivo')->with('error', 'El título es obligatorio.');
        }
        if ($categoriaId < 1) {
            return redirect()->to('config?tab=leyendas_cultivo' . ($id > 0 ? '&editar_leyenda_cultivo=' . $id : ''))
                ->with('error', 'Debe seleccionar una categoría.');
        }

        $data = [
            'leyenda_cultivo_categoria_id' => $categoriaId,
            'titulo'  => $titulo,
            'mensaje' => (string) ($this->request->getPost('mensaje') ?? ''),
            'activo'  => (int) ($this->request->getPost('activo') ?? 1),
        ];

        try {
            if (! $this->leyendaCultivoModel->ensureTable()) {
                return redirect()->to('config?tab=leyendas_cultivo' . ($id > 0 ? '&editar_leyenda_cultivo=' . $id : ''))
                    ->with('error', 'No se pudo preparar la tabla leyendas_cultivo. Revise permisos de base de datos o ejecute php spark migrate.');
            }
            $ok = $this->leyendaCultivoModel->saveLeyenda($data, $id > 0 ? $id : null);
        } catch (\Throwable $e) {
            log_message('error', 'saveLeyendaCultivo: {err}', ['err' => $e->getMessage()]);

            return redirect()->to('config?tab=leyendas_cultivo' . ($id > 0 ? '&editar_leyenda_cultivo=' . $id : ''))
                ->with('error', 'Error al guardar: ' . $e->getMessage());
        }

        if (! $ok) {
            return redirect()->to('config?tab=leyendas_cultivo' . ($id > 0 ? '&editar_leyenda_cultivo=' . $id : ''))
                ->with('error', 'No se pudo guardar la leyenda de cultivo.');
        }

        \App\Models\AuditoriaModel::log('config', $id > 0 ? 'leyenda_cultivo_actualizar' : 'leyenda_cultivo_crear', $id > 0 ? (string) $id : null);

        return redirect()->to('config?tab=leyendas_cultivo')->with('success', 'Leyenda de cultivo guardada correctamente.');
    }

    public function deleteLeyendaCultivo($id = 0)
    {
        $id = (int) $id;
        if ($id < 1) {
            return redirect()->to('config?tab=leyendas_cultivo')->with('error', 'ID inválido.');
        }

        try {
            $ok = $this->leyendaCultivoModel->softDelete($id);
        } catch (\Throwable $e) {
            return redirect()->to('config?tab=leyendas_cultivo')->with('error', 'No se pudo eliminar.');
        }

        if ($ok) {
            \App\Models\AuditoriaModel::log('config', 'leyenda_cultivo_eliminar', (string) $id);

            return redirect()->to('config?tab=leyendas_cultivo')->with('success', 'Leyenda de cultivo eliminada.');
        }

        return redirect()->to('config?tab=leyendas_cultivo')->with('error', 'No se pudo eliminar.');
    }

    public function saveLeyendaCultivoCategoria()
    {
        $id = (int) ($this->request->getPost('leyenda_cultivo_categoria_id') ?? 0);
        $nombre = trim((string) ($this->request->getPost('nombre') ?? ''));
        if ($nombre === '') {
            return redirect()->to('config?tab=leyendas_cultivo')->with('error', 'El nombre de la categoría es obligatorio.');
        }

        $data = [
            'nombre' => $nombre,
            'activo' => (int) ($this->request->getPost('activo') ?? 1),
        ];

        try {
            if (! $this->leyendaCultivoCategoriaModel->ensureTable()) {
                return redirect()->to('config?tab=leyendas_cultivo' . ($id > 0 ? '&editar_leyenda_cultivo_categoria=' . $id : ''))
                    ->with('error', 'No se pudo preparar la tabla de categorías.');
            }
            $ok = $this->leyendaCultivoCategoriaModel->saveCategoria($data, $id > 0 ? $id : null);
        } catch (\Throwable $e) {
            log_message('error', 'saveLeyendaCultivoCategoria: {err}', ['err' => $e->getMessage()]);

            return redirect()->to('config?tab=leyendas_cultivo' . ($id > 0 ? '&editar_leyenda_cultivo_categoria=' . $id : ''))
                ->with('error', 'Error al guardar: ' . $e->getMessage());
        }

        if (! $ok) {
            return redirect()->to('config?tab=leyendas_cultivo' . ($id > 0 ? '&editar_leyenda_cultivo_categoria=' . $id : ''))
                ->with('error', 'No se pudo guardar la categoría.');
        }

        \App\Models\AuditoriaModel::log('config', $id > 0 ? 'leyenda_cultivo_categoria_actualizar' : 'leyenda_cultivo_categoria_crear', $id > 0 ? (string) $id : null);

        return redirect()->to('config?tab=leyendas_cultivo')->with('success', 'Categoría guardada correctamente.');
    }

    public function deleteLeyendaCultivoCategoria($id = 0)
    {
        $id = (int) $id;
        if ($id < 1) {
            return redirect()->to('config?tab=leyendas_cultivo')->with('error', 'ID inválido.');
        }

        try {
            $count = $this->leyendaCultivoCategoriaModel->countLeyendasEnCategoria($id);
            if ($count > 0) {
                return redirect()->to('config?tab=leyendas_cultivo')
                    ->with('error', 'No se puede eliminar: la categoría tiene ' . $count . ' leyenda(s) asociada(s).');
            }
            $ok = $this->leyendaCultivoCategoriaModel->softDelete($id);
        } catch (\Throwable $e) {
            return redirect()->to('config?tab=leyendas_cultivo')->with('error', 'No se pudo eliminar.');
        }

        if ($ok) {
            \App\Models\AuditoriaModel::log('config', 'leyenda_cultivo_categoria_eliminar', (string) $id);

            return redirect()->to('config?tab=leyendas_cultivo')->with('success', 'Categoría eliminada.');
        }

        return redirect()->to('config?tab=leyendas_cultivo')->with('error', 'No se pudo eliminar.');
    }

    public function saveFichaClinica()
    {
        $id = (int) ($this->request->getPost('ficha_clinica_id') ?? 0);
        $nombre = trim((string) ($this->request->getPost('nombre') ?? ''));
        if ($nombre === '') {
            return redirect()->to('config?tab=ficha_clinica' . ($id > 0 ? '&editar_ficha_clinica=' . $id : ''))
                ->with('error', 'El nombre es obligatorio.');
        }

        $data = [
            'nombre' => $nombre,
            'activo' => (int) ($this->request->getPost('activo') ?? 1),
        ];

        try {
            if (! $this->fichaClinicaModel->ensureTable()) {
                return redirect()->to('config?tab=ficha_clinica' . ($id > 0 ? '&editar_ficha_clinica=' . $id : ''))
                    ->with('error', 'No se pudo preparar la tabla fichas_clinicas. Ejecute php spark migrate.');
            }
            $savedId = $this->fichaClinicaModel->saveFicha($data, $id > 0 ? $id : null);
        } catch (\Throwable $e) {
            log_message('error', 'saveFichaClinica: {err}', ['err' => $e->getMessage()]);

            return redirect()->to('config?tab=ficha_clinica' . ($id > 0 ? '&editar_ficha_clinica=' . $id : ''))
                ->with('error', 'Error al guardar: ' . $e->getMessage());
        }

        if ($savedId === false) {
            return redirect()->to('config?tab=ficha_clinica' . ($id > 0 ? '&editar_ficha_clinica=' . $id : ''))
                ->with('error', 'No se pudo guardar la ficha clínica.');
        }

        $finalId = is_int($savedId) ? $savedId : $id;
        \App\Models\AuditoriaModel::log('config', $id > 0 ? 'ficha_clinica_actualizar' : 'ficha_clinica_crear', (string) $finalId);

        return redirect()->to('config?tab=ficha_clinica&editar_ficha_clinica=' . $finalId)
            ->with('success', $id > 0 ? 'Ficha clínica actualizada.' : 'Ficha clínica creada. Configure la matriz abajo.');
    }

    public function deleteFichaClinica($id = 0)
    {
        $id = (int) $id;
        if ($id < 1) {
            return redirect()->to('config?tab=ficha_clinica')->with('error', 'ID inválido.');
        }

        try {
            $ok = $this->fichaClinicaModel->softDelete($id);
        } catch (\Throwable $e) {
            return redirect()->to('config?tab=ficha_clinica')->with('error', 'No se pudo eliminar.');
        }

        if ($ok) {
            \App\Models\AuditoriaModel::log('config', 'ficha_clinica_eliminar', (string) $id);

            return redirect()->to('config?tab=ficha_clinica')->with('success', 'Ficha clínica eliminada.');
        }

        return redirect()->to('config?tab=ficha_clinica')->with('error', 'No se pudo eliminar.');
    }

    public function saveFichaClinicaMatriz()
    {
        $fichaClinicaId = (int) ($this->request->getPost('ficha_clinica_id') ?? 0);
        $isAjax = $this->request->isAJAX();

        $respond = static function (bool $success, string $message, int $status = 200) use ($isAjax, $fichaClinicaId) {
            if ($isAjax) {
                return service('response')->setJSON([
                    'success'    => $success,
                    'message'    => $message,
                    'csrf_token' => csrf_hash(),
                    'csrf_name'  => csrf_token(),
                    'reload'     => $success,
                ])->setStatusCode($status);
            }

            return redirect()->to('config?tab=ficha_clinica&editar_ficha_clinica=' . $fichaClinicaId)
                ->with($success ? 'success' : 'error', $message);
        };

        if ($fichaClinicaId < 1 || $this->fichaClinicaModel->getById($fichaClinicaId) === null) {
            return $respond(false, 'Ficha clínica no encontrada', 400);
        }

        $json = $this->request->getPost('cultivo_matriz_json');
        if (! is_string($json) || trim($json) === '') {
            $json = (string) ($_POST['cultivo_matriz_json'] ?? '');
        }
        if (trim($json) === '') {
            $rawBody = (string) $this->request->getBody();
            if ($rawBody !== '' && str_contains($rawBody, 'cultivo_matriz_json=')) {
                parse_str($rawBody, $parsedBody);
                $json = (string) ($parsedBody['cultivo_matriz_json'] ?? '');
            }
        }

        $config = is_string($json) && trim($json) !== '' ? json_decode($json, true) : null;
        if (! is_array($config)) {
            $detail = json_last_error_msg();
            $msg = 'Configuración de matriz inválida';
            if (trim($json) === '') {
                $msg = 'No se recibió la configuración. Si la matriz es muy grande, aumente max_input_vars y post_max_size en PHP.';
            } elseif ($detail !== '' && $detail !== 'No error') {
                $msg .= ': ' . $detail;
            }

            return $respond(false, $msg, 400);
        }

        if (! $this->fichaClinicaModel->saveMatrizConfig($fichaClinicaId, $config)) {
            return $respond(false, 'No se pudo guardar la matriz de la ficha clínica', 500);
        }

        \App\Models\AuditoriaModel::log('config', 'ficha_clinica_matriz_guardar', (string) $fichaClinicaId);

        return $respond(true, 'Matriz de ficha clínica guardada correctamente');
    }

    public function exportFichaClinica($id): ResponseInterface
    {
        $id = (int) $id;
        $payload = $this->fichaClinicaModel->buildExportPayload($id);
        if ($payload === null) {
            return redirect()->to('config?tab=ficha_clinica')->with('error', 'Ficha clínica no encontrada');
        }

        $json = json_encode($payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_INVALID_UTF8_SUBSTITUTE);
        if ($json === false) {
            return redirect()->to('config?tab=ficha_clinica&editar_ficha_clinica=' . $id)
                ->with('error', 'No se pudo generar el archivo de exportación');
        }

        $slug = FichaClinicaModel::slugifyNombre((string) ($payload['nombre'] ?? 'ficha'));
        $filename = 'ficha_clinica_' . $id . '_' . $slug . '_' . date('Ymd_His') . '.json';

        return $this->response
            ->download($filename, $json)
            ->setContentType('application/json');
    }

    public function exportFichaClinicaMatriz($id): ResponseInterface
    {
        $id = (int) $id;
        $payload = $this->fichaClinicaModel->buildMatrizExportPayload($id);
        if ($payload === null) {
            return redirect()->to('config?tab=ficha_clinica')->with('error', 'Ficha clínica no encontrada');
        }

        $json = json_encode($payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_INVALID_UTF8_SUBSTITUTE);
        if ($json === false) {
            return redirect()->to('config?tab=ficha_clinica&editar_ficha_clinica=' . $id)
                ->with('error', 'No se pudo generar el archivo de exportación');
        }

        $row = $this->fichaClinicaModel->getById($id);
        $slug = FichaClinicaModel::slugifyNombre((string) ($row['nombre'] ?? 'ficha'));
        $filename = 'ficha_clinica_matriz_' . $id . '_' . $slug . '_' . date('Ymd_His') . '.json';

        return $this->response
            ->download($filename, $json)
            ->setContentType('application/json');
    }

    public function importFichaClinica()
    {
        $file = $this->request->getFile('config_file');
        if (! $file || ! $file->isValid()) {
            return redirect()->to('config?tab=ficha_clinica')->with('error', 'Debe seleccionar un archivo JSON válido');
        }

        if (strtolower((string) $file->getExtension()) !== 'json') {
            return redirect()->to('config?tab=ficha_clinica')->with('error', 'El archivo debe ser .json');
        }

        $payload = $this->parseFichaClinicaJsonFile($file);
        if ($payload === null) {
            return redirect()->to('config?tab=ficha_clinica')->with('error', 'JSON inválido o archivo vacío');
        }

        $result = $this->fichaClinicaModel->importFichaFromPayload($payload);
        if (! ($result['success'] ?? false)) {
            return redirect()->to('config?tab=ficha_clinica')
                ->with('error', (string) ($result['message'] ?? 'No se pudo importar la ficha clínica'));
        }

        $fichaId = (int) ($result['ficha_clinica_id'] ?? 0);
        \App\Models\AuditoriaModel::log('config', 'ficha_clinica_importar', $fichaId > 0 ? (string) $fichaId : null);

        return redirect()->to('config?tab=ficha_clinica&editar_ficha_clinica=' . $fichaId)
            ->with('success', (string) ($result['message'] ?? 'Ficha clínica importada correctamente'));
    }

    public function importFichaClinicaMatriz($id)
    {
        $id = (int) $id;
        if ($id < 1) {
            return redirect()->to('config?tab=ficha_clinica')->with('error', 'Ficha clínica inválida');
        }

        $file = $this->request->getFile('config_file');
        if (! $file || ! $file->isValid()) {
            return redirect()->to('config?tab=ficha_clinica&editar_ficha_clinica=' . $id)
                ->with('error', 'Debe seleccionar un archivo JSON válido');
        }

        if (strtolower((string) $file->getExtension()) !== 'json') {
            return redirect()->to('config?tab=ficha_clinica&editar_ficha_clinica=' . $id)
                ->with('error', 'El archivo debe ser .json');
        }

        $payload = $this->parseFichaClinicaJsonFile($file);
        if ($payload === null) {
            return redirect()->to('config?tab=ficha_clinica&editar_ficha_clinica=' . $id)
                ->with('error', 'JSON inválido o archivo vacío');
        }

        $result = $this->fichaClinicaModel->importMatrizFromPayload($id, $payload);
        if (! ($result['success'] ?? false)) {
            return redirect()->to('config?tab=ficha_clinica&editar_ficha_clinica=' . $id)
                ->with('error', (string) ($result['message'] ?? 'No se pudo importar la matriz'));
        }

        \App\Models\AuditoriaModel::log('config', 'ficha_clinica_matriz_importar', (string) $id);

        return redirect()->to('config?tab=ficha_clinica&editar_ficha_clinica=' . $id)
            ->with('success', (string) ($result['message'] ?? 'Matriz importada correctamente'));
    }

    /**
     * @return array<string, mixed>|null
     */
    private function parseFichaClinicaJsonFile($file): ?array
    {
        $tmpPath = $file->getTempName();
        $content = is_string($tmpPath) && $tmpPath !== '' ? @file_get_contents($tmpPath) : false;
        if (! is_string($content) || trim($content) === '') {
            return null;
        }

        $payload = json_decode($content, true);

        return is_array($payload) ? $payload : null;
    }

    public function saveFichaClinicaPruebas()
    {
        $fichaId = (int) ($this->request->getPost('ficha_clinica_id') ?? 0);
        if ($fichaId < 1) {
            return redirect()->to('config?tab=ficha_clinica')->with('error', 'Ficha clínica inválida.');
        }

        $rawIds = $this->request->getPost('prianacategoria_ids');
        $ids = is_array($rawIds) ? $rawIds : [];
        $ids = array_values(array_unique(array_filter(array_map('intval', $ids), static fn (int $v): bool => $v > 0)));

        try {
            if (! $this->fichaClinicaModel->ensurePruebasTable()) {
                return redirect()->to('config?tab=ficha_clinica&editar_ficha_clinica=' . $fichaId)
                    ->with('error', 'No se pudo preparar la tabla de enlaces. Ejecute php spark migrate.');
            }
            $ok = $this->fichaClinicaModel->saveLinkedPruebas($fichaId, $ids);
        } catch (\Throwable $e) {
            log_message('error', 'saveFichaClinicaPruebas: {err}', ['err' => $e->getMessage()]);

            return redirect()->to('config?tab=ficha_clinica&editar_ficha_clinica=' . $fichaId)
                ->with('error', 'Error al guardar: ' . $e->getMessage());
        }

        if (! $ok) {
            return redirect()->to('config?tab=ficha_clinica&editar_ficha_clinica=' . $fichaId)
                ->with('error', 'No se pudieron guardar las pruebas enlazadas.');
        }

        \App\Models\AuditoriaModel::log('config', 'ficha_clinica_pruebas_guardar', (string) $fichaId, \App\Models\AuditoriaModel::detail([
            'total_pruebas' => count($ids),
        ]));

        return redirect()->to('config?tab=ficha_clinica&editar_ficha_clinica=' . $fichaId)
            ->with('success', count($ids) > 0
                ? 'Se enlazaron ' . count($ids) . ' prueba(s) a la ficha clínica.'
                : 'Se quitaron todas las pruebas enlazadas.');
    }

    /**
     * Tipos de resultado - redirige a config con pestaña activa
     */
    public function opciones()
    {
        return redirect()->to($this->opcionesTabUrl());
    }

    public function exportOpciones(): ResponseInterface
    {
        $payload = [
            'schema' => 'lab-config-opciones-v1',
            'generated_at' => date('c'),
            'opciones' => $this->buildOpcionesExportData(),
        ];
        $json = json_encode($payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        if ($json === false) {
            return redirect()->to($this->opcionesTabUrl())->with('error', 'No se pudo generar el archivo de exportación.');
        }

        \App\Models\AuditoriaModel::log('config', 'opciones_exportar', null);
        $filename = 'opciones_config_' . date('Ymd_His') . '.json';
        return $this->response
            ->setHeader('Content-Type', 'application/json; charset=utf-8')
            ->setHeader('Content-Disposition', 'attachment; filename="' . $filename . '"')
            ->setBody($json);
    }

    public function importOpciones(): ResponseInterface
    {
        $file = $this->request->getFile('opciones_file');
        if ($file === null || $file->getError() === UPLOAD_ERR_NO_FILE) {
            return redirect()->to($this->opcionesTabUrl())->with('error', 'Seleccione un archivo JSON para importar.');
        }
        if (! $file->isValid()) {
            return redirect()->to($this->opcionesTabUrl())->with('error', 'El archivo no se subió correctamente.');
        }
        if (strtolower((string) $file->getExtension()) !== 'json') {
            return redirect()->to($this->opcionesTabUrl())->with('error', 'Formato inválido. Debe ser un archivo .json.');
        }

        $raw = @file_get_contents($file->getTempName());
        if ($raw === false || trim($raw) === '') {
            return redirect()->to($this->opcionesTabUrl())->with('error', 'El archivo está vacío o no se pudo leer.');
        }
        $data = json_decode($raw, true);
        if (!is_array($data) || !isset($data['opciones']) || !is_array($data['opciones'])) {
            return redirect()->to($this->opcionesTabUrl())->with('error', 'Archivo JSON inválido para importación de opciones.');
        }

        try {
            $summary = $this->applyOpcionesImport($data['opciones']);
        } catch (\Throwable $e) {
            return redirect()->to($this->opcionesTabUrl())->with('error', 'Error al importar opciones: ' . $e->getMessage());
        }

        \App\Models\AuditoriaModel::log('config', 'opciones_importar', null, json_encode($summary, JSON_UNESCAPED_UNICODE));
        return redirect()->to($this->opcionesTabUrl())->with(
            'success',
            'Importación completada. Tipos procesados: ' . (int) ($summary['opciones'] ?? 0) . ', valores agregados/actualizados: ' . (int) ($summary['valores'] ?? 0) . '.'
        );
    }

    public function saveOpcion(): ResponseInterface
    {
        $nombre = trim($this->request->getPost('opciones') ?? '');
        if ($nombre === '') {
            if ($this->shouldReturnJson()) {
                return $this->response->setJSON($this->buildOpcionesPayload(false, 'El nombre es obligatorio.'))->setStatusCode(400);
            }
            return redirect()->to($this->opcionesTabUrl())->with('error', 'El nombre es obligatorio.');
        }
        $id = (int) ($this->request->getPost('opciones_id') ?? 0);
        $tabla = 'opcion_valores';
        if ($id > 0) {
            $row = $this->opcionModel->find($id);
            $tabla = $row ? trim($row['tabla'] ?? 'opcion_valores') : 'opcion_valores';
        }
        $opcionesId = $this->opcionModel->saveOpcion([
            'opciones_id' => $id,
            'opciones'    => $nombre,
            'tabla'       => $tabla,
        ]);
        if ($opcionesId > 0 && $id <= 0) {
            \App\Models\AuditoriaModel::log('config', 'opcion_crear', (string) $opcionesId);
        }
        if ($this->shouldReturnJson()) {
            return $this->response->setJSON($this->buildOpcionesPayload(true, 'Tipo de resultado guardado.'));
        }
        return redirect()->to($this->opcionesTabUrl())->with('success', 'Tipo de resultado guardado.');
    }

    private function buildOpcionesExportData(): array
    {
        $rows = $this->loadOpcionesForView();
        $out = [];
        foreach ($rows as $row) {
            $tabla = trim((string) ($row['tabla'] ?? 'opcion_valores'));
            $valores = [];
            foreach (($row['valores'] ?? []) as $v) {
                if ($tabla === 'opcion_valores') {
                    $val = trim((string) ($v['valor'] ?? ''));
                } elseif ($tabla === 'opcpositivo') {
                    $val = trim((string) ($v['opcpositivo'] ?? ''));
                } elseif ($tabla === 'opcreactivo') {
                    $val = trim((string) ($v['opcreactivo'] ?? ''));
                } else {
                    $firstKey = array_key_first($v);
                    $val = trim((string) ($firstKey !== null ? ($v[$firstKey] ?? '') : ''));
                }
                if ($val !== '') {
                    $valores[] = $val;
                }
            }
            $out[] = [
                'opciones_id' => (int) ($row['opciones_id'] ?? 0),
                'opciones'    => trim((string) ($row['opciones'] ?? '')),
                'tabla'       => $tabla,
                'editable'    => (bool) ($row['editable'] ?? false),
                'valores'     => array_values(array_unique($valores)),
            ];
        }

        return $out;
    }

    private function applyOpcionesImport(array $items): array
    {
        $db = \Config\Database::connect();
        $db->transStart();

        $processedOpciones = 0;
        $processedValores = 0;

        foreach ($items as $item) {
            if (!is_array($item)) {
                continue;
            }
            $nombre = trim((string) ($item['opciones'] ?? ''));
            $tabla = trim((string) ($item['tabla'] ?? 'opcion_valores'));
            if ($nombre === '' || !in_array($tabla, ['opcion_valores', 'opcpositivo', 'opcreactivo'], true)) {
                continue;
            }

            $opcionId = $this->resolveImportOpcionId($nombre, $tabla, $item);
            if ($opcionId < 1) {
                continue;
            }
            $processedOpciones++;

            $valores = isset($item['valores']) && is_array($item['valores']) ? $item['valores'] : [];
            foreach ($valores as $val) {
                $valor = trim((string) $val);
                if ($valor === '') {
                    continue;
                }
                if ($tabla === 'opcion_valores') {
                    $exists = $db->table('opcion_valores')
                        ->where('opciones_id', $opcionId)
                        ->where('valor', $valor)
                        ->countAllResults();
                    if ($exists < 1) {
                        $this->opcionModel->saveValor([
                            'opcion_valor_id' => 0,
                            'opciones_id'     => $opcionId,
                            'valor'           => $valor,
                            'orden'           => 0,
                        ]);
                        $processedValores++;
                    }
                    continue;
                }

                $table = $tabla;
                $col = $table;
                $exists = $db->table($table)->where($col, $valor)->countAllResults();
                if ($exists < 1) {
                    $this->opcionModel->saveValorTabla($table, 0, $valor);
                    $processedValores++;
                }
            }
        }

        $db->transComplete();
        if (!$db->transStatus()) {
            throw new \RuntimeException('No se pudieron guardar los datos importados.');
        }

        return ['opciones' => $processedOpciones, 'valores' => $processedValores];
    }

    private function resolveImportOpcionId(string $nombre, string $tabla, array $item): int
    {
        $importId = (int) ($item['opciones_id'] ?? 0);
        if ($importId > 0) {
            $existing = $this->opcionModel->find($importId);
            if (is_array($existing)) {
                $this->opcionModel->saveOpcion([
                    'opciones_id' => $importId,
                    'opciones'    => $nombre,
                    'tabla'       => trim((string) ($existing['tabla'] ?? $tabla)),
                ]);
                return $importId;
            }
        }

        $foundByName = $this->opcionModel
            ->where('opciones', $nombre)
            ->where('tabla', $tabla)
            ->first();
        if (is_array($foundByName)) {
            return (int) ($foundByName['opciones_id'] ?? 0);
        }

        return (int) $this->opcionModel->saveOpcion([
            'opciones_id' => 0,
            'opciones'    => $nombre,
            'tabla'       => $tabla,
        ]);
    }

    public function deleteOpcion($id): ResponseInterface
    {
        $id = (int) $id;
        $result = $this->opcionModel->deleteOpcionIfUnused($id);
        if ($result['success']) {
            \App\Models\AuditoriaModel::log('config', 'opcion_eliminar', (string) $id);
        }
        if ($this->shouldReturnJson()) {
            $statusCode = $result['success'] ? 200 : 400;
            return $this->response->setJSON($this->buildOpcionesPayload((bool) $result['success'], (string) ($result['message'] ?? '')))->setStatusCode($statusCode);
        }
        return redirect()->to($this->opcionesTabUrl())->with($result['success'] ? 'success' : 'error', $result['message']);
    }

    public function saveOpcionValor(): ResponseInterface
    {
        $opcionesId = (int) ($this->request->getPost('opciones_id') ?? 0);
        $valor = trim($this->request->getPost('valor') ?? '');
        if ($opcionesId < 1 || $valor === '') {
            if ($this->shouldReturnJson()) {
                return $this->response->setJSON($this->buildOpcionesPayload(false, 'Datos incompletos.'))->setStatusCode(400);
            }
            return redirect()->to($this->opcionesTabUrl())->with('error', 'Datos incompletos.');
        }
        $row = $this->opcionModel->find($opcionesId);
        if (!$row || trim($row['tabla'] ?? '') !== 'opcion_valores') {
            if ($this->shouldReturnJson()) {
                return $this->response->setJSON($this->buildOpcionesPayload(false, 'Solo se pueden editar valores en opciones personalizadas.'))->setStatusCode(400);
            }
            return redirect()->to($this->opcionesTabUrl())->with('error', 'Solo se pueden editar valores en opciones personalizadas.');
        }
        $this->opcionModel->saveValor([
            'opcion_valor_id' => (int) ($this->request->getPost('opcion_valor_id') ?? 0),
            'opciones_id'     => $opcionesId,
            'valor'           => $valor,
            'orden'           => (int) ($this->request->getPost('orden') ?? 0),
        ]);
        if ($this->shouldReturnJson()) {
            return $this->response->setJSON($this->buildOpcionesPayload(true, 'Valor guardado.'));
        }
        return redirect()->to($this->opcionesTabUrl())->with('success', 'Valor guardado.');
    }

    public function deleteOpcionValor($id): ResponseInterface
    {
        $id = (int) $id;
        $this->opcionModel->deleteValor($id);
        if ($this->shouldReturnJson()) {
            return $this->response->setJSON($this->buildOpcionesPayload(true, 'Valor eliminado.'));
        }
        return redirect()->to($this->opcionesTabUrl())->with('success', 'Valor eliminado.');
    }

    public function reorderOpcionValores(): ResponseInterface
    {
        $opcionesId = (int) ($this->request->getPost('opciones_id') ?? 0);
        $rawOrder = (string) ($this->request->getPost('ordered_ids') ?? '');
        $orderedIds = array_values(array_filter(array_map('intval', explode(',', $rawOrder))));
        if ($opcionesId < 1 || empty($orderedIds)) {
            if ($this->shouldReturnJson()) {
                return $this->response->setJSON($this->buildOpcionesPayload(false, 'Orden inválido.'))->setStatusCode(400);
            }
            return redirect()->to($this->opcionesTabUrl())->with('error', 'Orden inválido.');
        }

        $row = $this->opcionModel->find($opcionesId);
        if (!$row || trim((string) ($row['tabla'] ?? '')) !== 'opcion_valores') {
            if ($this->shouldReturnJson()) {
                return $this->response->setJSON($this->buildOpcionesPayload(false, 'Solo se puede reordenar en opciones personalizadas.'))->setStatusCode(400);
            }
            return redirect()->to($this->opcionesTabUrl())->with('error', 'Solo se puede reordenar en opciones personalizadas.');
        }

        $ok = $this->opcionModel->reorderValores($opcionesId, $orderedIds);
        if (!$ok) {
            if ($this->shouldReturnJson()) {
                return $this->response->setJSON($this->buildOpcionesPayload(false, 'No se pudo guardar el orden.'))->setStatusCode(500);
            }
            return redirect()->to($this->opcionesTabUrl())->with('error', 'No se pudo guardar el orden.');
        }

        if ($this->shouldReturnJson()) {
            return $this->response->setJSON($this->buildOpcionesPayload(true, 'Orden actualizado.'));
        }

        return redirect()->to($this->opcionesTabUrl())->with('success', 'Orden actualizado.');
    }

    /**
     * Cambia mayúsculas/minúsculas de los nombres de los tipos de resultado (solo editables).
     */
    public function transformOpcionNombres(): ResponseInterface
    {
        $mode = strtolower(trim((string) ($this->request->getPost('mode') ?? '')));
        if (!in_array($mode, ['upper', 'first', 'title'], true)) {
            if ($this->shouldReturnJson()) {
                return $this->response->setJSON($this->buildOpcionesPayload(false, 'Operación inválida.'))->setStatusCode(400);
            }
            return redirect()->to($this->opcionesTabUrl())->with('error', 'Operación inválida.');
        }

        $cambiados = $this->opcionModel->transformNombresCase($mode);
        $message = $cambiados > 0
            ? 'Se actualizaron ' . $cambiados . ' nombre(s).'
            : 'Los nombres ya tenían ese formato (los tipos del sistema no se modifican).';
        if ($cambiados > 0) {
            \App\Models\AuditoriaModel::log('config', 'opciones_transformar_nombres', null, $mode);
        }
        if ($this->shouldReturnJson()) {
            return $this->response->setJSON($this->buildOpcionesPayload(true, $message));
        }
        return redirect()->to($this->opcionesTabUrl())->with('success', $message);
    }

    /**
     * Cambia mayúsculas/minúsculas de los nombres de tipos de muestra.
     */
    public function transformTiposMuestra(): ResponseInterface
    {
        $redirect = 'config?tab=tipos_muestra&tm_sort=' . ($this->request->getPost('tm_sort') === 'za' ? 'za' : 'az');
        $mode = strtolower(trim((string) ($this->request->getPost('mode') ?? '')));
        if (!in_array($mode, ['upper', 'first', 'title'], true)) {
            return redirect()->to($redirect)->with('error', 'Operación inválida.');
        }

        try {
            $cambiados = $this->tipoMuestraModel->transformNombresCase($mode);
        } catch (\Throwable $e) {
            return redirect()->to($redirect)->with('error', 'No se pudieron actualizar los nombres.');
        }
        if ($cambiados > 0) {
            \App\Models\AuditoriaModel::log('config', 'tipos_muestra_transformar_nombres', null, $mode);
        }

        return redirect()->to($redirect)->with(
            'success',
            $cambiados > 0 ? 'Se actualizaron ' . $cambiados . ' nombre(s).' : 'Los nombres ya tenían ese formato.'
        );
    }

    /**
     * Cambia mayúsculas/minúsculas de los nombres de métodos de prueba.
     */
    public function transformMetodos(): ResponseInterface
    {
        $redirect = 'config?tab=metodos_prueba&met_sort=' . ($this->request->getPost('met_sort') === 'za' ? 'za' : 'az');
        $mode = strtolower(trim((string) ($this->request->getPost('mode') ?? '')));
        if (!in_array($mode, ['upper', 'first', 'title'], true)) {
            return redirect()->to($redirect)->with('error', 'Operación inválida.');
        }

        try {
            $cambiados = $this->metodoModel->transformNombresCase($mode);
        } catch (\Throwable $e) {
            return redirect()->to($redirect)->with('error', 'No se pudieron actualizar los nombres.');
        }
        if ($cambiados > 0) {
            \App\Models\AuditoriaModel::log('config', 'metodos_transformar_nombres', null, $mode);
        }

        return redirect()->to($redirect)->with(
            'success',
            $cambiados > 0 ? 'Se actualizaron ' . $cambiados . ' nombre(s).' : 'Los nombres ya tenían ese formato.'
        );
    }

    public function saveValorTabla(): ResponseInterface
    {
        $tabla   = trim($this->request->getPost('tabla') ?? '');
        $valorId = (int) ($this->request->getPost('valor_id') ?? 0);
        $valor   = trim($this->request->getPost('valor') ?? '');
        if (!in_array($tabla, ['opcpositivo', 'opcreactivo'], true) || $valor === '') {
            if ($this->shouldReturnJson()) {
                return $this->response->setJSON($this->buildOpcionesPayload(false, 'Datos incompletos.'))->setStatusCode(400);
            }
            return redirect()->to($this->opcionesTabUrl())->with('error', 'Datos incompletos.');
        }
        $this->opcionModel->saveValorTabla($tabla, $valorId, $valor);
        if ($this->shouldReturnJson()) {
            return $this->response->setJSON($this->buildOpcionesPayload(true, 'Valor guardado.'));
        }
        return redirect()->to($this->opcionesTabUrl())->with('success', 'Valor guardado.');
    }

    public function deleteValorTabla($tabla, $id): ResponseInterface
    {
        $tabla = in_array($tabla, ['opcpositivo', 'opcreactivo'], true) ? $tabla : '';
        $id    = (int) $id;
        if ($tabla === '') {
            if ($this->shouldReturnJson()) {
                return $this->response->setJSON($this->buildOpcionesPayload(false, 'Parámetros inválidos.'))->setStatusCode(400);
            }
            return redirect()->to($this->opcionesTabUrl())->with('error', 'Parámetros inválidos.');
        }
        $this->opcionModel->deleteValorTabla($tabla, $id);
        if ($this->shouldReturnJson()) {
            return $this->response->setJSON($this->buildOpcionesPayload(true, 'Valor eliminado.'));
        }
        return redirect()->to($this->opcionesTabUrl())->with('success', 'Valor eliminado.');
    }

    /**
     * Guarda configuración de WhatsApp
     */
    public function saveWhatsapp(): ResponseInterface
    {
        $this->configService->saveWhatsappConfig($this->request->getPost());
        \App\Models\AuditoriaModel::log('config', 'whatsapp_actualizar', null);
        return redirect()->to('config?tab=whatsapp')->with('success', 'Configuración de WhatsApp guardada.');
    }

    public function saveDeliveryNotifications(): ResponseInterface
    {
        (new \App\Services\DeliveryNotificationService())->saveConfigFromPost($this->request->getPost());
        \App\Models\AuditoriaModel::log('config', 'delivery_notifications_actualizar', null);

        return redirect()->to('config?tab=notificaciones_analisis')->with('success', 'Configuración de notificaciones de análisis guardada.');
    }

    public function saveSin(): ResponseInterface
    {
        $result = $this->configService->saveSinConfig(
            $this->request->getPost(),
            $this->request->getFile('sin_certificate_p12')
        );
        if (! ($result['success'] ?? false)) {
            return redirect()->to('config?tab=sin')->with('error', (string) ($result['message'] ?? 'No se pudo guardar la configuración SIN.'));
        }
        \App\Models\AuditoriaModel::log('config', 'sin_billing_actualizar', null);

        return redirect()->to('config?tab=sin')->with('success', (string) ($result['message'] ?? 'Configuración de SIN guardada.'));
    }

    public function saveInstitucionDescuento(): ResponseInterface
    {
        $institucion = trim((string) $this->request->getPost('institucion'));
        $descuentoRaw = trim((string) $this->request->getPost('descuento'));
        if ($institucion === '') {
            return redirect()->to('config?tab=institucion_descuentos')->with('error', 'Debe seleccionar una institución.');
        }
        if ($descuentoRaw === '' || !is_numeric($descuentoRaw)) {
            return redirect()->to('config?tab=institucion_descuentos')->with('error', 'El descuento debe ser numérico.');
        }
        $descuento = max(0, min(100, (float) $descuentoRaw));

        $discounts = $this->configService->getCustomerInstitutionDiscounts();
        $needle = function_exists('mb_strtolower') ? mb_strtolower($institucion, 'UTF-8') : strtolower($institucion);
        foreach (array_keys($discounts) as $existingInst) {
            $existingNeedle = function_exists('mb_strtolower') ? mb_strtolower($existingInst, 'UTF-8') : strtolower($existingInst);
            if ($existingNeedle === $needle) {
                unset($discounts[$existingInst]);
            }
        }
        $discounts[$institucion] = $descuento;

        if (! $this->configService->saveCustomerInstitutionDiscounts($discounts)) {
            return redirect()->to('config?tab=institucion_descuentos')->with('error', 'No se pudo guardar el descuento.');
        }
        \App\Models\AuditoriaModel::log('config', 'institucion_descuento_guardar', null, $institucion . ':' . $descuento);

        return redirect()->to('config?tab=institucion_descuentos')->with('success', 'Descuento guardado correctamente.');
    }

    public function deleteInstitucionDescuento(): ResponseInterface
    {
        $institucion = trim((string) $this->request->getGet('institucion'));
        if ($institucion === '') {
            return redirect()->to('config?tab=institucion_descuentos')->with('error', 'Institución inválida.');
        }
        $discounts = $this->configService->getCustomerInstitutionDiscounts();
        $needle = function_exists('mb_strtolower') ? mb_strtolower($institucion, 'UTF-8') : strtolower($institucion);
        $removed = false;
        foreach (array_keys($discounts) as $existingInst) {
            $existingNeedle = function_exists('mb_strtolower') ? mb_strtolower($existingInst, 'UTF-8') : strtolower($existingInst);
            if ($existingNeedle === $needle) {
                unset($discounts[$existingInst]);
                $removed = true;
            }
        }
        if (! $removed) {
            return redirect()->to('config?tab=institucion_descuentos')->with('error', 'No se encontró la institución.');
        }
        if (! $this->configService->saveCustomerInstitutionDiscounts($discounts)) {
            return redirect()->to('config?tab=institucion_descuentos')->with('error', 'No se pudo eliminar el descuento.');
        }
        \App\Models\AuditoriaModel::log('config', 'institucion_descuento_eliminar', null, $institucion);

        return redirect()->to('config?tab=institucion_descuentos')->with('success', 'Descuento eliminado.');
    }

    public function saveTenant(): ResponseInterface
    {
        if (!$this->canManageTenants()) {
            return redirect()->to('config')->with('error', 'No tiene permiso para gestionar tenants.');
        }

        $result = $this->tenantConfigService->saveFromRequest($this->request->getPost());
        if ($result['success']) {
            \App\Models\AuditoriaModel::log('config', 'tenant_guardar', (string) ($this->request->getPost('tenant_key') ?? ''));
            return redirect()->to('config?tab=tenants')->with('success', $result['message']);
        }

        $editId = (int) ($this->request->getPost('tenant_id') ?? 0);
        $url = 'config?tab=tenants';
        if ($editId > 0) {
            $url .= '&tenant_edit=' . $editId;
        }
        return redirect()->to($url)->with('error', $result['message']);
    }

    public function deleteTenant($id): ResponseInterface
    {
        if (!$this->canManageTenants()) {
            return redirect()->to('config')->with('error', 'No tiene permiso para gestionar tenants.');
        }

        $tenantId = (int) $id;
        $result = $this->tenantConfigService->delete($tenantId);
        if ($result['success']) {
            \App\Models\AuditoriaModel::log('config', 'tenant_eliminar', (string) $tenantId);
            return redirect()->to('config?tab=tenants')->with('success', 'Tenant eliminado correctamente.');
        }

        return redirect()->to('config?tab=tenants')->with('error', $result['message']);
    }

    public function provisionTenant($id): ResponseInterface
    {
        if (!$this->canManageTenants()) {
            return redirect()->to('config')->with('error', 'No tiene permiso para gestionar tenants.');
        }

        $tenantId = (int) $id;
        $result = $this->tenantConfigService->provisionTenant($tenantId);
        if ($result['success']) {
            \App\Models\AuditoriaModel::log('config', 'tenant_provision', (string) $tenantId);
            return redirect()->to('config?tab=tenants')->with('success', $result['message']);
        }
        return redirect()->to('config?tab=tenants')->with('error', $result['message']);
    }

    /**
     * Aplica migraciones pendientes (p. ej. módulo home/dashboard) en la BD del laboratorio actual.
     */
    public function migrateCurrentDatabase(): ResponseInterface
    {
        if (!$this->canManageTenants()) {
            return redirect()->to('config')->with('error', 'No tiene permiso para gestionar tenants.');
        }

        $result = $this->tenantConfigService->runMigrationsOnDefaultDatabase();
        if ($result['success']) {
            \App\Models\AuditoriaModel::log('config', 'migrate_current_db', null, $result['message'] ?? '');
            return redirect()->to('config?tab=tenants')->with('success', $result['message']);
        }

        return redirect()->to('config?tab=tenants')->with('error', $result['message']);
    }

    /**
     * Aplica migraciones pendientes en cada tenant activo (sin recrear bases).
     */
    public function migrateAllTenants(): ResponseInterface
    {
        if (!$this->canManageTenants()) {
            return redirect()->to('config')->with('error', 'No tiene permiso para gestionar tenants.');
        }

        $result = $this->tenantConfigService->runMigrationsOnAllActiveTenants();
        $details = $result['details'] ?? [];
        $detailText = $details !== [] ? "\n" . implode("\n", $details) : '';

        if ($result['success']) {
            \App\Models\AuditoriaModel::log('config', 'migrate_all_tenants', null, ($result['message'] ?? '') . $detailText);
            return redirect()->to('config?tab=tenants')->with('success', ($result['message'] ?? '') . $detailText);
        }

        return redirect()->to('config?tab=tenants')->with('error', ($result['message'] ?? '') . $detailText);
    }

    /**
     * Acceso al tenant como superusuario: mantiene su usuario actual pero no escribe filas en auditoría del tenant.
     * El ID va en la URL (POST) para que el envío con target="_blank" sea fiable.
     */
    public function ghostEnterTenant($tenantIdFromRoute): ResponseInterface
    {
        if (! $this->canManageTenants()) {
            return redirect()->to('config')->with('error', 'No tiene permiso para gestionar tenants.');
        }

        $tenantId = (int) $tenantIdFromRoute;
        if ($tenantId < 1) {
            return redirect()->to('config?tab=tenants')->with('error', 'Tenant no válido.');
        }

        $row = null;
        foreach ($this->tenantConfigService->getAll() as $t) {
            if ((int) ($t['id'] ?? 0) === $tenantId) {
                $row = $t;
                break;
            }
        }
        if ($row === null || (int) ($row['is_active'] ?? 0) !== 1) {
            return redirect()->to('config?tab=tenants')->with('error', 'El tenant no existe o está inactivo.');
        }

        $tenantKey = trim((string) ($row['tenant_key'] ?? ''));
        if ($tenantKey === '') {
            return redirect()->to('config?tab=tenants')->with('error', 'Tenant sin clave configurada.');
        }

        if ($this->tenantResolver->resolveDatabaseConfig($tenantKey) === []) {
            return redirect()->to('config?tab=tenants')->with('error', 'No hay mapa de base de datos publicado para este tenant.');
        }

        $publicBase = $this->resolveTenantPublicBaseUrl($row);
        $currentHost = strtolower(explode(':', (string) service('request')->getServer('HTTP_HOST'), 2)[0]);
        $targetHost = $publicBase !== '' ? strtolower((string) parse_url($publicBase, PHP_URL_HOST)) : '';

        $centralPersonId = (int) session()->get('person_id');
        $emp = \Config\Database::connect('management')->table('employees')
            ->select('username')
            ->where('person_id', $centralPersonId)
            ->where('deleted', 0)
            ->get()
            ->getRow();
        $centralUsername = trim((string) ($emp->username ?? ''));
        if ($centralUsername === '') {
            return redirect()->to('config?tab=tenants')->with('error', 'No se pudo obtener su usuario del panel central.');
        }

        $ghostAccess = new GhostTenantAccessService();
        $tenantEmployee = $ghostAccess->resolveEmployeeForGhost($centralPersonId, $centralUsername, $tenantKey);
        if ($tenantEmployee === null) {
            return redirect()->to('config?tab=tenants')->with(
                'error',
                'No hay ningún empleado activo en la base de datos de este tenant. Aprovisione el laboratorio o cree al menos un usuario activo.'
            );
        }

        if ($publicBase !== '' && $targetHost !== '' && $targetHost !== $currentHost) {
            try {
                $token = (new TenantHandoffService())->create(
                    (int) $tenantEmployee['person_id'],
                    (string) $tenantEmployee['username'],
                    $tenantKey,
                    $centralPersonId
                );
            } catch (\Throwable $e) {
                log_message('error', 'TenantHandoff: ' . $e->getMessage());

                return redirect()->to('config?tab=tenants')->with('error', 'No se pudo generar el enlace de acceso. Revise permisos de writable/tenant_handoff.');
            }
            $handoffUrl = $publicBase . '/login/tenantHandoff/' . $token
                . '?tenant=' . rawurlencode($tenantKey);

            return redirect()->to($handoffUrl);
        }

        $ghostAccess->applyGhostSession(
            $centralPersonId,
            $tenantKey,
            $tenantEmployee,
            ($tenantEmployee['matched'] ?? '') === 'fallback'
        );

        $successMsg = 'Acceso al tenant sin registro de auditoría. Use «Salir» en la barra superior cuando termine.';
        if (($tenantEmployee['matched'] ?? '') === 'fallback') {
            $successMsg = 'Acceso al tenant como usuario «' . ($tenantEmployee['username'] ?? 'admin') . '» (soporte; no requiere su contraseña en este laboratorio). ' . $successMsg;
        }

        return redirect()->to(site_url('home?tenant=' . rawurlencode($tenantKey)))
            ->with('success', $successMsg);
    }

    /**
     * URL base pública del tenant (otro vhost). Columna public_base_url o .env tenancy.publicUrlTemplate.
     */
    private function resolveTenantPublicBaseUrl(array $row): string
    {
        $u = trim((string) ($row['public_base_url'] ?? ''));
        if ($u !== '') {
            return rtrim($u, '/');
        }
        $tpl = trim((string) env('tenancy.publicUrlTemplate', ''));
        if ($tpl === '') {
            return '';
        }
        $key = trim((string) ($row['tenant_key'] ?? ''));
        if ($key === '') {
            return '';
        }

        return rtrim(str_replace(['{tenant_key}', '{key}'], [$key, $key], $tpl), '/');
    }

    /**
     * Desactiva el modo sin auditoría y vuelve al tenant por defecto.
     */
    public function ghostExitTenant(): ResponseInterface
    {
        (new GhostTenantAccessService())->restoreCentralSessionAfterGhost();
        session()->remove('tenant_key');

        return redirect()->to(model(EmployeeModel::class)->getDefaultLandingUrl((int) session()->get('person_id')));
    }

    public function testSin(): ResponseInterface
    {
        if (! $this->request->is('post')) {
            return $this->response->setJSON([
                'success' => false,
                'message' => 'Método no permitido.',
            ])->setStatusCode(405);
        }

        try {
            $result = $this->configService->testSinConnection($this->request->getPost());
        } catch (\Throwable $e) {
            log_message('error', 'testSin: ' . $e->getMessage() . ' @ ' . $e->getFile() . ':' . $e->getLine());
            $result = [
                'success' => false,
                'message' => 'Error interno al probar SIN: ' . $e->getMessage(),
            ];
        }

        $result['csrf_token'] = csrf_hash();
        $result['csrf_name']  = csrf_token();

        return $this->response
            ->setStatusCode(200)
            ->setContentType('application/json')
            ->setJSON($result);
    }

    /**
     * Cierra todas las sesiones abiertas del usuario actual
     */
    public function closeAllSessions(): ResponseInterface
    {
        $personId = (int) session()->get('person_id');
        if ($personId <= 0) {
            return $this->response->setJSON([
                'success' => false,
                'message' => 'No hay sesión activa',
            ])->setStatusCode(401);
        }

        $db = \Config\Database::connect();
        $sessionTable = $db->prefixTable('ci_sessions');
        $result = (bool) $db->query(
            "DELETE FROM {$sessionTable} WHERE data LIKE ?",
            ['%person_id|i:' . $personId . ';%']
        );

        \App\Models\AuditoriaModel::log('config', 'cerrar_todas_sesiones', (string) $personId);

        return $this->response->setJSON([
            'success' => $result,
            'message' => $result ? 'Todas las sesiones han sido cerradas' : 'No se encontraron sesiones para cerrar',
        ]);
    }

    /**
     * Lista sesiones activas desde dom_ci_sessions y las asocia al usuario.
     */
    public function getActiveSessions(): ResponseInterface
    {
        $db = \Config\Database::connect();
        $sessionsVersion = $this->calculateSessionsVersion($db);
        $rows = $db->table('ci_sessions')
            ->select('id, ip_address, timestamp, data')
            ->orderBy('timestamp', 'DESC')
            ->get()
            ->getResultArray();

        $sessions = [];
        $now = time();
        $currentSessionId = session_id();

        foreach ($rows as $row) {
            $personId = $this->extractPersonIdFromSessionData($row['data'] ?? '');
            $doctorId = $this->extractDoctorIdFromSessionData($row['data'] ?? '');
            $userType = $this->extractUserTypeFromSessionData($row['data'] ?? '', $personId, $doctorId);
            $userAgent = $this->extractUserAgentFromSessionData($row['data'] ?? '');

            if ($personId <= 0 && $doctorId <= 0) {
                continue;
            }

            $user = null;
            if ($userType === 'doctor') {
                $user = $db->table('doctors')
                    ->select('doctor_id, username, name, email')
                    ->where('doctor_id', $doctorId)
                    ->get()
                    ->getRowArray();
            } else {
                $user = $db->table('employees e')
                    ->select('e.person_id, e.username, p.first_name, p.last_name_fa, p.last_name_mom, p.email')
                    ->join('people p', 'p.person_id = e.person_id', 'left')
                    ->where('e.person_id', $personId)
                    ->get()
                    ->getRowArray();
            }

            $lastActivityTs = $this->normalizeSessionTimestamp($row['timestamp'] ?? null);
            if (!$user) {
                continue;
            }

            $fullName = $userType === 'doctor'
                ? (string) ($user['name'] ?? '')
                : trim(($user['first_name'] ?? '') . ' ' . ($user['last_name_fa'] ?? '') . ' ' . ($user['last_name_mom'] ?? ''));

            $sessions[] = [
                'session_id'      => (string) ($row['id'] ?? ''),
                'person_id'       => (int) ($user['person_id'] ?? 0),
                'doctor_id'       => (int) ($user['doctor_id'] ?? 0),
                'user_type'       => $userType,
                'username'        => (string) ($user['username'] ?? ''),
                'full_name'       => $fullName,
                'email'           => (string) ($user['email'] ?? ''),
                'ip_address'      => (string) ($row['ip_address'] ?? ''),
                'user_agent'      => $userAgent,
                'last_activity'   => $lastActivityTs > 0 ? date('Y-m-d H:i:s', $lastActivityTs) : null,
                'seconds_inactive'=> max(0, $now - $lastActivityTs),
                'is_current'      => hash_equals((string) $currentSessionId, (string) ($row['id'] ?? '')),
            ];
        }

        return $this->response->setJSON([
            'success'  => true,
            'count'    => count($sessions),
            'version'  => $sessionsVersion,
            'sessions' => $sessions,
            'csrf_token' => csrf_hash(),
            'csrf_name'  => csrf_token(),
        ]);
    }

    /**
     * Devuelve una versión/hash de sesiones para detectar cambios.
     */
    public function getSessionsVersion(): ResponseInterface
    {
        $db = \Config\Database::connect();
        $version = $this->calculateSessionsVersion($db);

        return $this->response->setJSON([
            'success'    => true,
            'version'    => $version,
            'csrf_token' => csrf_hash(),
            'csrf_name'  => csrf_token(),
        ]);
    }

    /**
     * Cierra una sesión específica por ID.
     */
    public function killSession(): ResponseInterface
    {
        $sessionId = trim((string) ($this->request->getPost('session_id') ?? ''));
        if ($sessionId === '') {
            return $this->response->setJSON([
                'success' => false,
                'message' => 'session_id es requerido',
            ])->setStatusCode(400);
        }

        $db = \Config\Database::connect();
        $deleted = $db->table('ci_sessions')->where('id', $sessionId)->delete();

        return $this->response->setJSON([
            'success'    => (bool) $deleted,
            'message'    => $deleted ? 'Sesión cerrada correctamente' : 'No se pudo cerrar la sesión',
            'csrf_token' => csrf_hash(),
            'csrf_name'  => csrf_token(),
        ]);
    }

    /**
     * Endpoint para depurar sesiones activas.
     */
    public function debugSessions(): ResponseInterface
    {
        return $this->getActiveSessions();
    }

    private function extractPersonIdFromSessionData(string $rawData): int
    {
        return $this->extractSessionIntValue($rawData, 'person_id');
    }

    private function extractDoctorIdFromSessionData(string $rawData): int
    {
        return $this->extractSessionIntValue($rawData, 'doctor_id');
    }

    private function extractUserTypeFromSessionData(string $rawData, int $personId, int $doctorId): string
    {
        $rawType = strtolower(trim((string) $this->extractSessionStringValue($rawData, 'user_type')));
        if ($rawType !== '') {
            if ($rawType === 'doctor') {
                return 'doctor';
            }
            if ($rawType === 'employee') {
                return 'employee';
            }
        }

        if ($doctorId > 0) {
            return 'doctor';
        }
        if ($personId > 0) {
            return 'employee';
        }

        return 'unknown';
    }

    private function extractUserAgentFromSessionData(string $rawData): string
    {
        return $this->extractSessionStringValue($rawData, 'login_user_agent');
    }

    private function extractSessionIntValue(string $rawData, string $key): int
    {
        foreach ($this->sessionPayloadCandidates($rawData) as $payload) {
            if (preg_match('/' . preg_quote($key, '/') . '\|i:(\d+);/', $payload, $matches) === 1) {
                return (int) ($matches[1] ?? 0);
            }
            if (preg_match('/' . preg_quote($key, '/') . '\|s:\d+:"(\d+)";/', $payload, $matches) === 1) {
                return (int) ($matches[1] ?? 0);
            }
        }

        return 0;
    }

    private function extractSessionStringValue(string $rawData, string $key): string
    {
        foreach ($this->sessionPayloadCandidates($rawData) as $payload) {
            if (preg_match('/' . preg_quote($key, '/') . '\|s:\d+:"([^"]*)";/', $payload, $matches) === 1) {
                return (string) ($matches[1] ?? '');
            }
        }

        return '';
    }

    private function sessionPayloadCandidates(string $rawData): array
    {
        if ($rawData === '') {
            return [];
        }

        $candidates = [$rawData];
        $trimmed = trim($rawData);

        if ($trimmed !== '') {
            $decoded = base64_decode($trimmed, true);
            if ($decoded !== false && $decoded !== '') {
                $candidates[] = $decoded;
            }
        }

        return $candidates;
    }

    private function normalizeSessionTimestamp($rawTimestamp): int
    {
        if ($rawTimestamp === null || $rawTimestamp === '') {
            return 0;
        }

        if (is_numeric($rawTimestamp)) {
            $ts = (int) $rawTimestamp;
            // Si viene en milisegundos (13 dígitos), convertir a segundos.
            if ($ts > 2_000_000_000) {
                $ts = (int) floor($ts / 1000);
            }
            return $ts > 0 ? $ts : 0;
        }

        $parsed = strtotime((string) $rawTimestamp);
        return $parsed !== false ? (int) $parsed : 0;
    }

    private function calculateSessionsVersion($db): string
    {
        $rows = $db->table('ci_sessions')
            ->select('id, data')
            ->get()
            ->getResultArray();

        $tokens = [];
        foreach ($rows as $row) {
            $rawData = (string) ($row['data'] ?? '');
            $personId = $this->extractPersonIdFromSessionData($rawData);
            $doctorId = $this->extractDoctorIdFromSessionData($rawData);
            if ($personId <= 0 && $doctorId <= 0) {
                continue;
            }
            $userType = $this->extractUserTypeFromSessionData($rawData, $personId, $doctorId);
            $userId = $userType === 'doctor' ? $doctorId : $personId;
            $tokens[] = (string) ($row['id'] ?? '') . ':' . $userType . ':' . (string) $userId;
        }

        sort($tokens, SORT_STRING);
        return sha1(implode('|', $tokens));
    }

    public function saveTenantHomeBroadcast(): ResponseInterface
    {
        if (! $this->canManageTenants()) {
            return redirect()->to('config')->with('error', 'No tiene permiso para configurar el aviso del dashboard.');
        }

        $file   = $this->request->getFile('tenant_home_broadcast_image');
        $upload = null;
        if ($file !== null && $file->getError() !== UPLOAD_ERR_NO_FILE) {
            $upload = $file;
        }

        $svc    = new \App\Services\TenantHomeBroadcastService();
        $result = $svc->saveFromRequest($this->request->getPost(), $upload);
        if ($result['success']) {
            \App\Models\AuditoriaModel::log('config', 'tenant_home_broadcast', '1');

            return redirect()->to('config?tab=tenant_home_broadcast')->with('success', $result['message']);
        }

        return redirect()->to('config?tab=tenant_home_broadcast')->with('error', $result['message']);
    }

    public function saveTenantSubscriptionAlertDays(): ResponseInterface
    {
        if (! $this->canManageTenants()) {
            return redirect()->to('config')->with('error', 'No tiene permiso para modificar esta configuración.');
        }

        $d = (int) $this->request->getPost('dias_alerta_suscripcion_tenant');
        $d = max(1, min(90, $d));
        model(\App\Models\AppConfigModel::class)->saveValue('dias_alerta_suscripcion_tenant', (string) $d);
        \App\Models\AuditoriaModel::log('config', 'tenant_subscription_dias_alerta', (string) $d);

        return redirect()->to('config?tab=tenant_subscriptions')->with('success', 'Días de aviso de suscripción actualizados.');
    }

    public function saveTenantSubscriptionPayment(): ResponseInterface
    {
        if (! $this->canManageTenants()) {
            return redirect()->to('config')->with('error', 'No tiene permiso para registrar pagos de suscripción.');
        }

        $tenantId    = (int) $this->request->getPost('tenant_config_id');
        $periodStart = trim((string) $this->request->getPost('period_start'));
        $periodEnd   = trim((string) $this->request->getPost('period_end'));
        $amount      = (float) $this->request->getPost('amount');
        $currency    = trim((string) $this->request->getPost('currency'));
        $notes       = trim((string) $this->request->getPost('notes'));

        $file   = $this->request->getFile('voucher_pdf');
        $upload = null;
        if ($file !== null && $file->getError() !== UPLOAD_ERR_NO_FILE) {
            if (! $file->isValid()) {
                return redirect()->to('config?tab=tenant_subscriptions')->with('error', 'El PDF adjunto no se subió correctamente.');
            }
            $upload = $file;
        }

        $svc    = new TenantSubscriptionService();
        $result = $svc->createPaymentAndVoucher($tenantId, $periodStart, $periodEnd, $amount, $currency, $notes, $upload);
        if ($result['success']) {
            \App\Models\AuditoriaModel::log('config', 'tenant_subscription_pago', (string) ($result['id'] ?? ''));

            return redirect()->to('config?tab=tenant_subscriptions')->with('success', $result['message']);
        }

        return redirect()->to('config?tab=tenant_subscriptions')->with('error', $result['message']);
    }

    public function uploadTenantSubscriptionVoucher($id): ResponseInterface
    {
        if (! $this->canManageTenants()) {
            return redirect()->to('config')->with('error', 'No tiene permiso para adjuntar comprobantes.');
        }

        $payId = (int) $id;
        $file  = $this->request->getFile('voucher_pdf');
        if ($file === null || $file->getError() === UPLOAD_ERR_NO_FILE) {
            return redirect()->to('config?tab=tenant_subscriptions')->with('error', 'Seleccione un archivo PDF.');
        }
        if (! $file->isValid()) {
            return redirect()->to('config?tab=tenant_subscriptions')->with('error', 'El archivo no se subió correctamente.');
        }

        $svc    = new TenantSubscriptionService();
        $result = $svc->replaceVoucherPdf($payId, $file);
        if ($result['success']) {
            \App\Models\AuditoriaModel::log('config', 'tenant_subscription_pdf_adjunto', (string) $payId);

            return redirect()->to('config?tab=tenant_subscriptions')->with('success', $result['message']);
        }

        return redirect()->to('config?tab=tenant_subscriptions')->with('error', $result['message']);
    }

    public function downloadTenantSubscriptionVoucher($id): ResponseInterface
    {
        if (! $this->canManageTenants()) {
            return redirect()->to('config')->with('error', 'No tiene permiso para descargar este comprobante.');
        }

        $payId = (int) $id;
        $svc   = new TenantSubscriptionService();
        $p     = $svc->findPayment($payId);
        if (! $p) {
            return redirect()->to('config?tab=tenant_subscriptions')->with('error', 'Comprobante no encontrado.');
        }
        $fn = (string) ($p['voucher_filename'] ?? '');
        if ($fn === '' || ! preg_match('/^[a-zA-Z0-9._-]+$/', $fn)) {
            return redirect()->to('config?tab=tenant_subscriptions')->with('error', 'Archivo no disponible.');
        }
        $path = $svc->voucherPath($fn);
        if (! is_file($path) || ! is_readable($path)) {
            return redirect()->to('config?tab=tenant_subscriptions')->with('error', 'Archivo no encontrado en disco.');
        }
        $binary = @file_get_contents($path);
        if ($binary === false) {
            return redirect()->to('config?tab=tenant_subscriptions')->with('error', 'No se pudo leer el PDF.');
        }

        $downloadName = $svc->buildVoucherDownloadFilename($payId);
        $downloadName = str_replace(['"', "\r", "\n", '\\'], '', $downloadName);

        return $this->response
            ->setHeader('Content-Type', 'application/pdf')
            ->setHeader('Content-Disposition', 'attachment; filename="' . $downloadName . '"')
            ->setBody($binary);
    }

    private function canManageTenants(): bool
    {
        $defaultTenant = $this->tenantResolver->resolveDefaultTenantKey();
        if ($defaultTenant === null) {
            return true;
        }

        $currentTenant = session()->get('tenant_key');
        if ($currentTenant === null || $currentTenant === '') {
            $currentTenant = $this->tenantResolver->resolveTenantKey($this->request);
        }

        // Solo bloquear cuando hay tenant explícito y no coincide con el default.
        if ($currentTenant === null || $currentTenant === '') {
            return true;
        }

        return (string) $currentTenant === (string) $defaultTenant;
    }
}

