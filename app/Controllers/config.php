<?php

namespace App\Controllers;

use App\Models\OpcionModel;
use App\Models\PoblacionModel;
use App\Services\ConfigService;
use CodeIgniter\HTTP\ResponseInterface;

/**
 * Configuración del sistema.
 * Solo coordina: request → service → response.
 */
class Config extends SecureArea
{
    protected ?string $moduleId = 'config';

    protected ConfigService $configService;
    protected PoblacionModel $poblacionModel;
    protected OpcionModel $opcionModel;

    public function __construct()
    {
        parent::__construct();
        $this->configService  = new ConfigService();
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

        $tab = $this->request->getGet('tab') ?: 'sistema';
        if ($editarGet !== null && $editarGet !== '') {
            $tab = 'poblacion';
        }

        return view('config/manage', [
            'config'               => $config,
            'poblaciones'          => $poblaciones,
            'editar_poblacion'     => $editarPoblacion,
            'editar_poblacion_data'=> $editarPoblacionData,
            'opciones'             => $opciones,
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

        $employeeModel = model(\App\Models\EmployeeModel::class);
        $result = $employeeModel->logoutAllSessions($personId);

        \App\Models\AuditoriaModel::log('config', 'cerrar_todas_sesiones', (string) $personId);

        return $this->response->setJSON([
            'success' => $result,
            'message' => $result ? 'Todas las sesiones han sido cerradas' : 'No se encontraron sesiones para cerrar',
        ]);
    }
}

