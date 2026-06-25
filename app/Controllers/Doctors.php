<?php

namespace App\Controllers;

use App\Models\DoctorModel;
use App\Services\LabotestNameTransformService;
use CodeIgniter\HTTP\RedirectResponse;
use CodeIgniter\HTTP\ResponseInterface;

class Doctors extends SecureArea
{
    protected ?string $moduleId = 'doctors';

    protected DoctorModel $doctorModel;

    public function __construct()
    {
        parent::__construct();
        $this->doctorModel = model(DoctorModel::class);
    }

    public function index()
    {
        helper('table');

        $perPage = 20;
        $page = (int) ($this->request->getGet('page') ?? 1);
        $offset = max(0, ($page - 1) * $perPage);

        $doctors = $this->doctorModel->getAll($perPage, $offset);

        $data = [
            'controller_name'  => 'doctors',
            'current_module'   => 'doctors',
            'manage_table'     => get_doctors_manage_table($doctors, $this),
            'allowed_modules'  => $this->allowed_modules,
            'user_info'        => $this->user_info,
        ];

        return view('doctors/manage', $data);
    }

    public function search(): ResponseInterface
    {
        helper('table');
        $search = trim($this->request->getPost('search') ?? '');
        // Autocomplete devuelve "Nombre (Especialidad)"; extraer solo el nombre para la búsqueda
        if (str_contains($search, ' (')) {
            $search = trim(substr($search, 0, strpos($search, ' (')));
        }
        $doctors = $this->doctorModel->search($search);
        $dataRows = get_doctors_manage_table_data_rows($doctors, $this);
        return $this->response->setBody($dataRows);
    }

    public function suggest(): ResponseInterface
    {
        try {
            $q = $this->request->getPost('q') ?? '';
            $limit = (int) ($this->request->getPost('limit') ?? 25);
            $limit = min(max($limit, 1), 100);
            $suggestions = $this->doctorModel->getSearchSuggestions($q, $limit);
            return $this->response->setBody(implode("\n", $suggestions));
        } catch (\Throwable $e) {
            log_message('error', 'Doctors suggest: ' . $e->getMessage());
            return $this->response->setBody('')->setStatusCode(200);
        }
    }

    public function view($doctor_id = -1)
    {
        $doctorInfo = $this->doctorModel->getInfo($doctor_id === -1 ? -1 : (int) $doctor_id);

        $data = [
            'current_module'   => 'doctors',
            'controller_name'  => 'doctors',
            'doctor_info'      => $doctorInfo,
            'allowed_modules'  => $this->allowed_modules,
            'user_info'        => $this->user_info,
            'extra_head_links' => [
                '<script src="' . base_url('js/vendor/jquery.validate.min.js') . '"></script>',
            ],
        ];

        return view('doctors/form', $data);
    }

    public function saves($doctor_id = -1)
    {
        $validation = \Config\Services::validation();
        $validation->setRules(config('Validation')->doctors ?? []);
        if (!$validation->withRequest($this->request)->run()) {
            if ($this->request->isAJAX()) {
                return $this->response->setJSON([
                    'success' => false,
                    'message' => implode(' ', $validation->getErrors()),
                    'doctor_id' => -1,
                    'csrf_token' => csrf_hash(),
                    'csrf_name' => csrf_token(),
                ])->setStatusCode(400);
            }
            return redirect()->back()->withInput()->with('errors', $validation->getErrors());
        }

        $doctor_data = [
            'name'         => $this->request->getPost('name'),
            'phone_number' => $this->request->getPost('phone_number'),
            'gender'       => $this->request->getPost('gender'),
            'speciality'   => $this->request->getPost('speciality'),
            'address'      => $this->request->getPost('address'),
            'comments'     => $this->request->getPost('comments'),
            'username'     => $this->request->getPost('username'),
            'password'     => $this->request->getPost('password'),
            'email'        => $this->request->getPost('email'),
            'display_mode' => $this->request->getPost('display_mode') ?? 'clinico',
            'interpretacion_enabled' => (int) ($this->request->getPost('interpretacion_enabled') ?? 0),
            'commission_percent' => $this->request->getPost('commission_percent') ?? 0.00,
            'has_commission' => $this->request->getPost('has_commission') ?? 0,
            'hide_commission_details' => $this->request->getPost('hide_commission_details') ?? 0,
        ];

        $id = ($doctor_id === -1 || $doctor_id === '-1') ? null : (int) $doctor_id;
        if ($id === null && trim((string) ($doctor_data['username'] ?? '')) === '' && trim((string) ($doctor_data['email'] ?? '')) === '') {
            $doctor_data['password'] = '';
        }

        if (
            !$this->doctorModel->supportsLoginColumns()
            && (
                trim((string) ($doctor_data['username'] ?? '')) !== ''
                || trim((string) ($doctor_data['password'] ?? '')) !== ''
                || trim((string) ($doctor_data['email'] ?? '')) !== ''
            )
        ) {
            $msg = 'Debe ejecutar la migración de base de datos para acceso de doctores: database/migration_doctors_login.sql';
            if ($this->request->isAJAX()) {
                return $this->response->setJSON([
                    'success' => false,
                    'message' => $msg,
                    'doctor_id' => -1,
                    'csrf_token' => csrf_hash(),
                    'csrf_name' => csrf_token(),
                ])->setStatusCode(400);
            }
            return redirect()->back()->withInput()->with('error', $msg);
        }

        // Debug: log incoming display_mode and if the DB column exists
        try {
            log_message('debug', 'Doctors::saves incoming display_mode => ' . (string) ($doctor_data['display_mode'] ?? ''));
            log_message('debug', 'Doctors::saves doctorModel hasColumn(display_mode) => ' . (int) $this->doctorModel->hasColumn('display_mode'));
        } catch (\Throwable $e) {
            // ignore logging errors
        }

        $result = $this->doctorModel->saveDoctor($doctor_data, $id);
        if ($result !== false) {
            $savedDoctorId = (int) $result;
            (new \App\Services\RegisterService())->clearReportPdfPreviewCacheForDoctor($savedDoctorId);
            \App\Models\AuditoriaModel::log('doctors', $id === null ? 'crear' : 'actualizar', (string) $savedDoctorId);
            $msg = $id === null
                ? lang('Doctors.doctors_successful_adding') . ' ' . $doctor_data['name']
                : lang('Doctors.doctors_successful_updating') . ' ' . $doctor_data['name'];
            if ($this->request->isAJAX()) {
                return $this->response->setJSON([
                    'success' => true,
                    'message' => $msg,
                    'doctor_id' => (int) $result,
                    'redirect_url' => site_url('doctors'),
                    'csrf_token' => csrf_hash(),
                    'csrf_name' => csrf_token(),
                ]);
            }
        } else {
            $msg = lang('Doctors.doctors_error_adding_updating') . ' ' . $doctor_data['name'];
            if ($this->request->isAJAX()) {
                return $this->response->setJSON([
                    'success' => false,
                    'message' => $msg,
                    'doctor_id' => -1,
                    'csrf_token' => csrf_hash(),
                    'csrf_name' => csrf_token(),
                ]);
            }
        }

        return redirect()->to(site_url('doctors'));
    }

    /**
     * Transforma en lote nombre y especialidad de todos los doctores (mayúsculas o título).
     */
    public function transformNames(): ResponseInterface
    {
        $mode = strtolower(trim((string) ($this->request->getPost('mode') ?? '')));
        $allowedModes = [
            LabotestNameTransformService::MODE_UPPERCASE,
            LabotestNameTransformService::MODE_TITLE,
        ];
        if (! in_array($mode, $allowedModes, true)) {
            return $this->response->setJSON([
                'success' => false,
                'message' => 'Seleccione un formato válido',
                'csrf_token' => csrf_hash(),
                'csrf_name' => csrf_token(),
            ])->setStatusCode(400);
        }

        $result = $this->doctorModel->transformAllNames($mode);
        if ($result['success'] ?? false) {
            \App\Models\AuditoriaModel::log(
                'doctors',
                'transformar_nombres',
                '',
                \App\Models\AuditoriaModel::detail([
                    'modo' => $mode,
                    'doctores' => (int) ($result['doctors_updated'] ?? 0),
                ])
            );
        }

        return $this->response->setJSON([
            'success' => (bool) ($result['success'] ?? false),
            'message' => (string) ($result['message'] ?? ''),
            'doctors_updated' => (int) ($result['doctors_updated'] ?? 0),
            'unchanged' => (int) ($result['unchanged'] ?? 0),
            'csrf_token' => csrf_hash(),
            'csrf_name' => csrf_token(),
        ])->setStatusCode(($result['success'] ?? false) ? 200 : 400);
    }

    /**
     * El botón Borrar usa href doctors/delete; el borrado va por POST desde manage_tables.js.
     * Si alguien abre esa URL con GET, redirigimos al listado en lugar de mostrar 404.
     */
    public function deleteRedirect($id = null): RedirectResponse
    {
        return redirect()->to(site_url('doctors'));
    }

    public function delete(): ResponseInterface
    {
        $ids = $this->request->getPost('ids') ?? [];
        $ids = is_array($ids) ? $ids : [$ids];

        foreach ($ids as $id) {
            $idInt = (int) $id;
            $this->doctorModel->deleteDoctor($idInt);
            \App\Models\AuditoriaModel::log('doctors', 'eliminar', (string) $idInt);
        }

        return $this->response->setJSON([
            'success' => true,
            'message' => lang('Doctors.doctors_successful_deleted') . ' ' . count($ids) . ' ' . lang('Doctors.doctors_one_or_multiple'),
        ]);
    }
}
