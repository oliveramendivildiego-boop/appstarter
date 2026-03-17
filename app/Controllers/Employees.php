<?php

namespace App\Controllers;

use App\Models\EmployeeModel;
use App\Models\PersonModel;
use CodeIgniter\HTTP\ResponseInterface;

class Employees extends PersonController
{
    protected ?string $moduleId = 'employees';

    protected EmployeeModel $employeeModel;

    public function getFormWidth(): int
    {
        return 350;
    }

    public function __construct()
    {
        parent::__construct();
        $this->personModel = model(PersonModel::class);
        $this->employeeModel = model(EmployeeModel::class);
    }

    public function index()
    {
        helper('table');

        $perPage = 20;
        $page = (int) ($this->request->getGet('page') ?? 1);
        $offset = max(0, ($page - 1) * $perPage);

        $people = $this->employeeModel->getAll($perPage, $offset);

        return view('people/manage', [
            'controller_name'  => 'employees',
            'current_module'   => 'employees',
            'form_width'      => 350,
            'manage_table'    => get_people_manage_table($people, $this),
            'allowed_modules' => $this->allowed_modules,
            'user_info'       => $this->user_info,
        ]);
    }

    public function search(): ResponseInterface
    {
        helper('table');
        $search = $this->request->getPost('search') ?? '';
        $people = $this->employeeModel->search($search);
        return $this->response->setBody(get_people_manage_table_data_rows($people, $this));
    }

    public function suggest(): ResponseInterface
    {
        try {
            $q = $this->request->getPost('q') ?? '';
            $limit = min(max((int) ($this->request->getPost('limit') ?? 25), 1), 100);
            $suggestions = $this->employeeModel->getSearchSuggestions($q, $limit);
            return $this->response->setBody(implode("\n", $suggestions));
        } catch (\Throwable $e) {
            return $this->response->setBody('')->setStatusCode(200);
        }
    }

    /** Módulos disponibles (con controlador funcional). Los que no están aquí no se muestran en permisos. */
    private const MODULOS_DISPONIBLES = [
        'customers'      => 'Pacientes',
        'doctors'        => 'Doctores',
        'labotests'      => 'Análisis clínicos',
        'toquotes'       => 'Cotizar',
        'registers'      => 'Registros',
        'reports'        => 'Reportes',
        'controlcalidad' => 'Control de calidad',
        'reactivos'      => 'Reactivos',
        'equipos'        => 'Equipos',
        'auditoria'      => 'Auditoría',
        'employees'      => 'Empleados',
        'config'         => 'Configuración',
    ];

    public function view($employee_id = -1)
    {
        $employee_id = ($employee_id === -1 || $employee_id === '-1') ? -1 : (int) $employee_id;
        $employeeInfo = $this->employeeModel->getInfo($employee_id);

        $allFromDb = \Config\Database::connect()->table('modules')->orderBy('sort', 'ASC')->get()->getResult();
        $allModules = array_filter($allFromDb, fn($m) => isset(self::MODULOS_DISPONIBLES[$m->module_id ?? '']));
        usort($allModules, fn($a, $b) => (($a->sort ?? 0) <=> ($b->sort ?? 0)));

        $roles = \Config\Database::connect()->table('rol')->orderBy('rol_id')->get()->getResultArray();
        $modules = $employee_id > 0 ? $this->employeeModel->getAllowedModules($employee_id) : [];
        $perms = [];
        foreach ($modules as $m) {
            $id = $m->module_id ?? '';
            if ($id !== '') $perms[$id] = 1;
        }

        return view('employees/form', [
            'current_module'   => 'employees',
            'employee_info'    => $employeeInfo,
            'all_modules'      => array_values($allModules),
            'module_labels'    => self::MODULOS_DISPONIBLES,
            'user_permissions' => $perms,
            'roles' => $roles ?? [],
            'extra_head_links' => [
                '<script src="' . base_url('js/vendor/jquery.validate.min.js') . '"></script>',
                '<link rel="stylesheet" href="' . base_url('css/vendor/flatpickr.min.css') . '">',
                '<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/themes/material_green.css">',
                '<script src="' . base_url('js/vendor/flatpickr.min.js') . '"></script>',
                '<script src="https://cdn.jsdelivr.net/npm/flatpickr@4.6.13/dist/l10n/es.js"></script>',
            ],
            'allowed_modules'   => $this->allowed_modules,
            'user_info'        => $this->user_info,
        ]);
    }

    public function save($employee_id = -1)
    {
        $employee_id = ($employee_id === -1 || $employee_id === '-1') ? null : (int) $employee_id;

        $validation = \Config\Services::validation();
        $rules = config('Validation')->employees ?? [];
        if ($employee_id) {
            $rules['username']['rules'] = 'required|min_length[3]|max_length[50]';
            unset($rules['password']['rules']);
            $rules['password'] = ['rules' => 'permit_empty|min_length[4]'];
        }
        $validation->setRules($rules);

        if (!$validation->withRequest($this->request)->run()) {
            if ($this->request->isAJAX()) {
                return $this->response->setJSON([
                    'success' => false,
                    'message' => implode(' ', $validation->getErrors()),
                    'person_id' => -1,
                    'csrf_token' => csrf_hash(),
                    'csrf_name' => csrf_token(),
                ])->setStatusCode(400);
            }
            return redirect()->back()->withInput()->with('errors', $validation->getErrors());
        }

        $person_data = [
            'ci'           => $this->request->getPost('ci'),
            'first_name'   => $this->request->getPost('first_name'),
            'last_name_fa' => safe_mb_trim((string) ($this->request->getPost('last_name_fa') ?? '')),
            'last_name_mom'=> safe_mb_trim((string) ($this->request->getPost('last_name_mom') ?? '')),
            'email'        => $this->request->getPost('email'),
            'phone_number' => $this->request->getPost('phone_number'),
            'birthday'     => $this->request->getPost('birthday'),
            'gender'       => $this->request->getPost('gender'),
            'comments'     => $this->request->getPost('comments'),
        ];

        $employee_data = [
            'username' => trim($this->request->getPost('username') ?? ''),
            'password' => $this->request->getPost('password') ?? '',
            'rol_id'   => $this->request->getPost('rol_id') ?: null,
        ];

        if ($employee_id) {
            $existing = $this->employeeModel->getInfo($employee_id);
            if ($existing && $employee_data['username'] !== ($existing->username ?? '')) {
                if ($this->employeeModel->usernameExists($employee_data['username'])) {
                    $msg = 'El nombre de usuario ya está en uso.';
                    if ($this->request->isAJAX()) {
                        return $this->response->setJSON([
                            'success' => false,
                            'message' => $msg,
                            'csrf_token' => csrf_hash(),
                            'csrf_name' => csrf_token(),
                        ])->setStatusCode(400);
                    }
                    return redirect()->back()->withInput()->with('error', $msg);
                }
            }
        }

        $result = $this->employeeModel->saveEmployee($person_data, $employee_data, $employee_id);
        if ($result !== false) {
            $personId = (int) $result;
            $permIds = $this->request->getPost('permissions') ?? [];
            $permIds = is_array($permIds) ? $permIds : [];
            $db = \Config\Database::connect();
            $db->table('permissions')->where('person_id', $personId)->delete();
            foreach ($permIds as $modId) {
                $modId = trim($modId);
                if ($modId !== '') {
                    $db->table('permissions')->insert(['module_id' => $modId, 'person_id' => $personId]);
                }
            }
            session()->remove('user_info_' . $personId);
            session()->remove('allowed_modules_' . $personId);
            \App\Models\AuditoriaModel::log('employees', $employee_id === null ? 'crear' : 'actualizar', (string) $personId);

            $msg = $employee_id === null
                ? lang('Employees.employees_successful_adding') . ' ' . $person_data['first_name']
                : lang('Employees.employees_successful_updating') . ' ' . $person_data['first_name'];
            if ($this->request->isAJAX()) {
                return $this->response->setJSON([
                    'success' => true,
                    'message' => $msg,
                    'person_id' => $personId,
                    'redirect_url' => site_url('employees'),
                    'csrf_token' => csrf_hash(),
                    'csrf_name' => csrf_token(),
                ]);
            }
        } else {
            $msg = $employee_id === null
                ? 'Error al agregar empleado. Verifique que el usuario no exista.'
                : 'Error al actualizar empleado.';
            if ($this->request->isAJAX()) {
                return $this->response->setJSON([
                    'success' => false,
                    'message' => $msg,
                    'person_id' => -1,
                    'csrf_token' => csrf_hash(),
                    'csrf_name' => csrf_token(),
                ]);
            }
        }

        return redirect()->to(site_url('employees'));
    }

    public function delete(): ResponseInterface
    {
        $ids = $this->request->getPost('ids') ?? [];
        $ids = is_array($ids) ? $ids : [$ids];
        foreach ($ids as $id) {
            $this->employeeModel->deleteEmployee((int) $id);
        }
        return $this->response->setJSON([
            'success' => true,
            'message' => lang('Employees.employees_confirm_delete'),
        ]);
    }
}
