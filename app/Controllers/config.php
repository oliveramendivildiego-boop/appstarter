<?php

namespace App\Controllers;

use App\Models\OpcionModel;
use App\Models\PoblacionModel;
use App\Models\ReportPdfTemplateModel;
use App\Libraries\TenantResolver;
use App\Services\ConfigService;
use App\Services\TenantConfigService;
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
    protected TenantResolver $tenantResolver;

    public function __construct()
    {
        parent::__construct();
        $this->configService  = new ConfigService();
        $this->tenantConfigService = new TenantConfigService();
        $this->tenantResolver = new TenantResolver();
        $this->poblacionModel = model(PoblacionModel::class);
        $this->opcionModel   = model(OpcionModel::class);
    }

    public function index()
    {
        helper('config');

        $config = $this->configService->getAllAsArray();
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

        $opciones = $this->loadOpcionesForView();
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

        $pdf_templates = [];
        try {
            $pdf_templates = model(ReportPdfTemplateModel::class)->orderBy('name', 'ASC')->findAll();
        } catch (\Throwable $e) {
            $pdf_templates = [];
        }

        return view('config/manage', [
            'config'               => $config,
            'pdf_templates'        => $pdf_templates,
            'poblaciones'          => $poblaciones,
            'editar_poblacion'     => $editarPoblacion,
            'editar_poblacion_data'=> $editarPoblacionData,
            'opciones'             => $opciones,
            'tenants'              => $tenants,
            'tenant_edit_data'     => $tenantEditData,
            'can_manage_tenants'   => $canManageTenants,
            'active_tab'           => $tab,
            'timezone_options'     => get_timezone_options(),
            'theme_palette'        => get_theme_color_palette(),
            'allowed_modules'      => $this->allowed_modules,
            'user_info'            => $this->user_info,
            'current_module'       => 'config',
        ]);
    }

    /**
     * Carga datos de opciones (tipos de resultado) para la vista
     */
    private function loadOpcionesForView(): array
    {
        $opciones = $this->opcionModel->findAll();
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

    public function save(): ResponseInterface
    {
        $validation = \Config\Services::validation();
        $validation->setRules([
            'company' => 'required|min_length[2]|max_length[255]',
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

    /**
     * Tipos de resultado - redirige a config con pestaña activa
     */
    public function opciones()
    {
        return redirect()->to('config?tab=opciones');
    }

    public function saveOpcion(): ResponseInterface
    {
        $nombre = trim($this->request->getPost('opciones') ?? '');
        if ($nombre === '') {
            return redirect()->to('config?tab=opciones')->with('error', 'El nombre es obligatorio.');
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
        return redirect()->to('config?tab=opciones')->with('success', 'Tipo de resultado guardado.');
    }

    public function deleteOpcion($id): ResponseInterface
    {
        $id = (int) $id;
        $result = $this->opcionModel->deleteOpcionIfUnused($id);
        if ($result['success']) {
            \App\Models\AuditoriaModel::log('config', 'opcion_eliminar', (string) $id);
        }
        return redirect()->to('config?tab=opciones')->with($result['success'] ? 'success' : 'error', $result['message']);
    }

    public function saveOpcionValor(): ResponseInterface
    {
        $opcionesId = (int) ($this->request->getPost('opciones_id') ?? 0);
        $valor = trim($this->request->getPost('valor') ?? '');
        if ($opcionesId < 1 || $valor === '') {
            return redirect()->to('config?tab=opciones')->with('error', 'Datos incompletos.');
        }
        $row = $this->opcionModel->find($opcionesId);
        if (!$row || trim($row['tabla'] ?? '') !== 'opcion_valores') {
            return redirect()->to('config?tab=opciones')->with('error', 'Solo se pueden editar valores en opciones personalizadas.');
        }
        $this->opcionModel->saveValor([
            'opcion_valor_id' => (int) ($this->request->getPost('opcion_valor_id') ?? 0),
            'opciones_id'     => $opcionesId,
            'valor'           => $valor,
            'orden'           => (int) ($this->request->getPost('orden') ?? 0),
        ]);
        return redirect()->to('config?tab=opciones')->with('success', 'Valor guardado.');
    }

    public function deleteOpcionValor($id): ResponseInterface
    {
        $id = (int) $id;
        $this->opcionModel->deleteValor($id);
        return redirect()->back()->with('success', 'Valor eliminado.');
    }

    public function saveValorTabla(): ResponseInterface
    {
        $tabla   = trim($this->request->getPost('tabla') ?? '');
        $valorId = (int) ($this->request->getPost('valor_id') ?? 0);
        $valor   = trim($this->request->getPost('valor') ?? '');
        if (!in_array($tabla, ['opcpositivo', 'opcreactivo'], true) || $valor === '') {
            return redirect()->to('config?tab=opciones')->with('error', 'Datos incompletos.');
        }
        $this->opcionModel->saveValorTabla($tabla, $valorId, $valor);
        return redirect()->to('config?tab=opciones')->with('success', 'Valor guardado.');
    }

    public function deleteValorTabla($tabla, $id): ResponseInterface
    {
        $tabla = in_array($tabla, ['opcpositivo', 'opcreactivo'], true) ? $tabla : '';
        $id    = (int) $id;
        if ($tabla === '') {
            return redirect()->to('config?tab=opciones')->with('error', 'Parámetros inválidos.');
        }
        $this->opcionModel->deleteValorTabla($tabla, $id);
        return redirect()->back()->with('success', 'Valor eliminado.');
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

    public function saveSin(): ResponseInterface
    {
        $this->configService->saveSinConfig($this->request->getPost());
        \App\Models\AuditoriaModel::log('config', 'sin_billing_actualizar', null);
        return redirect()->to('config?tab=sin')->with('success', 'Configuración de SIN guardada.');
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

    public function testSin(): ResponseInterface
    {
        $result = $this->configService->testSinConnection();
        return $this->response->setJSON($result);
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

