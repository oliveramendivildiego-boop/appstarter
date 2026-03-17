<?php

namespace App\Controllers;

use App\Models\CustomerModel;
use CodeIgniter\HTTP\ResponseInterface;

class Customers extends PersonController
{
    protected ?string $moduleId = 'customers';

    protected CustomerModel $customerModel;

    public function __construct()
    {
        parent::__construct();
        $this->customerModel = model(CustomerModel::class);
    }

    public function getFormWidth(): int
    {
        return 350;
    }

    public function index()
    {
        helper('table');

        $perPage = 20;
        $page = (int) ($this->request->getGet('page') ?? 1);
        $offset = max(0, ($page - 1) * $perPage);

        $people = $this->customerModel->getAll($perPage, $offset);

        return view('people/manage', [
            'controller_name'  => 'customers',
            'current_module'   => 'customers',
            'form_width'      => $this->getFormWidth(),
            'manage_table'    => get_people_manage_table($people, $this),
            'allowed_modules' => $this->allowed_modules,
            'user_info'       => $this->user_info,
        ]);
    }

    public function search(): ResponseInterface
    {
        helper('table');
        $search = $this->request->getPost('search') ?? '';
        $people = $this->customerModel->search($search);
        $dataRows = get_people_manage_table_data_rows($people, $this);
        return $this->response->setBody($dataRows);
    }

    public function suggest(): ResponseInterface
    {
        try {
            $q = $this->request->getPost('q') ?? '';
            $limit = (int) ($this->request->getPost('limit') ?? 25);
            $limit = min(max($limit, 1), 100);
            $suggestions = $this->customerModel->getSearchSuggestions($q, $limit);
            return $this->response->setBody(implode("\n", $suggestions));
        } catch (\Throwable $e) {
            log_message('error', 'Customers suggest: ' . $e->getMessage());
            return $this->response->setBody('')->setStatusCode(200);
        }
    }

    public function view(int|string $customer_id = -1)
    {
        $personInfo = $this->customerModel->getInfo($customer_id === -1 ? -1 : (int) $customer_id);

        return view('customers/form', [
            'current_module'   => 'customers',
            'person_info'      => $personInfo,
            'extra_head_links'  => [
                '<script src="' . base_url('js/vendor/jquery.validate.min.js') . '"></script>',
                '<link rel="stylesheet" href="' . base_url('css/vendor/flatpickr.min.css') . '">',
                '<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/themes/material_green.css">',
                '<script src="' . base_url('js/vendor/flatpickr.min.js') . '"></script>',
                '<script src="https://cdn.jsdelivr.net/npm/flatpickr@4.6.13/dist/l10n/es.js"></script>',
            ],
            'allowed_modules'  => $this->allowed_modules,
            'user_info'        => $this->user_info,
        ]);
    }

    public function save(int|string $customer_id = -1)
    {
        $validation = \Config\Services::validation();
        $validation->setRules(config('Validation')->customers ?? []);
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

        $ci = trim((string) ($this->request->getPost('ci') ?? ''));
        $id = ($customer_id === -1 || $customer_id === '0') ? null : (int) $customer_id;
        if ($ci !== '' && $this->customerModel->ciExistsForOtherCustomer($ci, $id)) {
            $msg = 'Ya existe un paciente con el CI ' . $ci . '.';
            if ($this->request->isAJAX()) {
                return $this->response->setJSON([
                    'success' => false,
                    'message' => $msg,
                    'person_id' => -1,
                    'csrf_token' => csrf_hash(),
                    'csrf_name' => csrf_token(),
                ])->setStatusCode(400);
            }
            return redirect()->back()->withInput()->with('error', $msg);
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
            'person_id'    => $this->request->getPost('person_id'),
        ];

        $customer_data = [
            'account_number' => $this->request->getPost('account_number') ?: null,
            'taxable'        => $this->request->getPost('taxable') ? 1 : 0,
            'seguro'         => trim($this->request->getPost('seguro') ?? '') ?: null,
            'institucion'    => trim($this->request->getPost('institucion') ?? '') ?: null,
        ];

        $id = ($customer_id === -1 || $customer_id === '0') ? null : (int) $customer_id;

        $result = $this->customerModel->saveCustomer($person_data, $customer_data, $id);
        if ($result !== false) {
            $personId = (int) $result;
            \App\Models\AuditoriaModel::log('customers', $id === null ? 'crear' : 'actualizar', (string) $personId);
            $apellidos = safe_mb_trim(($person_data['last_name_fa'] ?? '') . ' ' . ($person_data['last_name_mom'] ?? ''));
            $msg = $id === null
                ? lang('Customers.customers_successful_adding') . ' ' . $person_data['first_name'] . ' ' . $apellidos
                : lang('Customers.customers_successful_updating') . ' ' . $person_data['first_name'] . ' ' . $apellidos;
            if ($this->request->isAJAX()) {
                return $this->response->setJSON([
                    'success' => true,
                    'message' => $msg,
                    'person_id' => $personId,
                    'redirect_url' => site_url('customers'),
                    'csrf_token' => csrf_hash(),
                    'csrf_name' => csrf_token(),
                ]);
            }
        } else {
            $apellidos = safe_mb_trim(($person_data['last_name_fa'] ?? '') . ' ' . ($person_data['last_name_mom'] ?? ''));
            $msg = lang('Customers.customers_error_adding_updating') . ' ' . $person_data['first_name'] . ' ' . $apellidos;
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

        return redirect()->to(site_url('customers'));
    }

    public function delete(): ResponseInterface
    {
        $ids = $this->request->getPost('ids') ?? [];
        $ids = is_array($ids) ? $ids : [$ids];

        foreach ($ids as $id) {
            $idInt = (int) $id;
            $this->customerModel->deleteCustomer($idInt);
            \App\Models\AuditoriaModel::log('customers', 'eliminar', (string) $idInt);
        }

        return $this->response->setJSON([
            'success' => true,
            'message' => lang('Customers.customers_successful_deleted') . ' ' . count($ids) . ' ' . lang('Customers.customers_one_or_multiple'),
        ]);
    }
}
