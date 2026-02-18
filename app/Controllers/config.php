<?php

namespace App\Controllers;

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

    public function __construct()
    {
        parent::__construct();
        $this->configService  = new ConfigService();
        $this->poblacionModel = model(PoblacionModel::class);
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

        return view('config/manage', [
            'config'               => $config,
            'poblaciones'          => $poblaciones,
            'editar_poblacion'     => $editarPoblacion,
            'editar_poblacion_data'=> $editarPoblacionData,
            'timezone_options' => get_timezone_options(),
            'theme_palette'    => get_theme_color_palette(),
            'allowed_modules' => $this->allowed_modules,
            'user_info'       => $this->user_info,
        ]);
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
                    'success' => false,
                    'message' => implode(', ', $validation->getErrors()),
                ])
                ->setStatusCode(400);
        }

        $result = $this->configService->saveFromRequest(
            $this->request->getPost(),
            $this->request->getFile('logo_upload')
        );

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
            return redirect()->to('config')->with('error', 'El nombre del grupo es obligatorio.');
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
            return redirect()->to('config')->with('success', 'Población guardada correctamente.');
        } catch (\Throwable $e) {
            return redirect()->to('config' . ($id >= 0 ? '?editar=' . $id . '#form_poblacion' : ''))->with('error', 'Error al guardar. Ejecute la migración de poblacion si no lo ha hecho.');
        }
    }

    /**
     * Eliminar (soft) grupo de población
     */
    public function deletePoblacion($id)
    {
        $id = (int) $id;
        $this->poblacionModel->deletePoblacion($id);
        return redirect()->to('config')->with('success', 'Población eliminada.');
    }
}
