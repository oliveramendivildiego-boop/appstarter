<?php

namespace App\Controllers;

use App\Models\DoctorModel;
use App\Models\EmployeeModel;
use App\Services\GhostTenantAccessService;
use App\Services\TenantHandoffService;
use CodeIgniter\HTTP\ResponseInterface;

class Login extends BaseController
{
    public function index()
    {
        $employeeModel = model(EmployeeModel::class);

        if ($employeeModel->isLoggedIn()) {
            $personId = (int) session()->get('person_id');
            return redirect()->to($employeeModel->getDefaultLandingUrl($personId));
        }

        if (strtolower($this->request->getMethod()) === 'post') {
            $username = $this->request->getPost('username');
            $password = $this->request->getPost('password');

            if ($employeeModel->login($username ?? '', $password ?? '')) {
                $landing = $employeeModel->getDefaultLandingUrl((int) session()->get('person_id'));
                if ($this->request->isAJAX()) {
                    return $this->response->setJSON([
                        'success'  => true,
                        'redirect' => $landing,
                    ]);
                }
                return redirect()->to($landing);
            }

            $doctorModel = model(\App\Models\DoctorModel::class);
            if ($doctorModel->login($username ?? '', $password ?? '')) {
                if ($this->request->isAJAX()) {
                    return $this->response->setJSON([
                        'success'  => true,
                        'redirect' => site_url('doctor/home'),
                    ]);
                }
                return redirect()->to(site_url('doctor/home'));
            }

            $error = lang('Login.login_invalid_username_and_password');
            if ($this->request->isAJAX()) {
                return $this->response->setJSON([
                    'success' => false,
                    'message' => $error,
                ]);
            }
        } else {
            $error = '';
        }

        $googleClientId = config('Google')->clientId ?? '';

        helper('form');

        return view('login', [
            'error'          => $error,
            'googleClientId' => $googleClientId,
        ]);
    }

    public function googleLogin(): ResponseInterface
    {
        try {
            $idToken = $this->request->getPost('id_token');
            if (empty($idToken)) {
                return $this->response->setJSON(['success' => false, 'message' => lang('Login.login_google_no_account')]);
            }

            $clientId = trim((string) (config('Google')->clientId ?? ''));
            if ($clientId === '') {
                return $this->response->setJSON(['success' => false, 'message' => lang('Login.login_google_not_configured')]);
            }

            $verifySsl = filter_var((string) env('googleVerifySsl', 'true'), FILTER_VALIDATE_BOOLEAN);
            $http = service('curlrequest');
            $resp = $http->get('https://oauth2.googleapis.com/tokeninfo', [
                'query'  => ['id_token' => $idToken],
                'verify' => $verifySsl,
                'http_errors' => false,
            ]);

            if ($resp->getStatusCode() !== 200) {
                return $this->response->setJSON(['success' => false, 'message' => 'Token inválido']);
            }

            $payload = json_decode((string) $resp->getBody(), true);
            if (!is_array($payload)) {
                return $this->response->setJSON(['success' => false, 'message' => 'Token inválido']);
            }

            $aud = (string) ($payload['aud'] ?? '');
            if ($aud !== $clientId) {
                return $this->response->setJSON(['success' => false, 'message' => 'Token inválido para esta aplicación']);
            }

            $email = $payload['email'] ?? '';
            $emailVerified = (string) ($payload['email_verified'] ?? 'false');
            if (empty($email) || $emailVerified !== 'true') {
                return $this->response->setJSON(['success' => false, 'message' => 'No se pudo verificar el correo de Google']);
            }

            $employeeModel = model(EmployeeModel::class);
            if ($employeeModel->loginByEmail($email)) {
                return $this->response->setJSON([
                    'success'  => true,
                    'redirect' => $employeeModel->getDefaultLandingUrl((int) session()->get('person_id')),
                ]);
            }

            $doctorModel = model(DoctorModel::class);
            if ($doctorModel->loginByEmail($email)) {
                return $this->response->setJSON([
                    'success'  => true,
                    'redirect' => site_url('doctor/home'),
                ]);
            }

            return $this->response->setJSON(['success' => false, 'message' => lang('Login.login_google_no_account')]);
        } catch (\Throwable $e) {
            return $this->response->setJSON(['success' => false, 'message' => $e->getMessage()]);
        }
    }

    /**
     * Entrada en otro vhost (ej. quantum.local) con sesión y modo fantasma vía token de un solo uso.
     */
    public function tenantHandoff(string $token): ResponseInterface
    {
        $service = new TenantHandoffService();
        $payload = $service->consume($token);
        if ($payload === null) {
            return redirect()->to(site_url('login'))->with('error', 'Enlace de acceso inválido o vencido. Genérelo de nuevo desde Configuración → Tenants.');
        }
        $personId = (int) ($payload['person_id'] ?? 0);
        $expectUser = strtolower(trim((string) ($payload['username'] ?? '')));
        $tenantKey = trim((string) ($payload['ghost_target_tenant_key'] ?? ''));
        if ($personId < 1 || $expectUser === '' || $tenantKey === '') {
            return redirect()->to(site_url('login'))->with('error', 'Token de acceso no válido.');
        }
        $employeeModel = model(EmployeeModel::class);
        $row = $employeeModel->db->table('employees')
            ->select('person_id, username')
            ->where('person_id', $personId)
            ->where('deleted', 0)
            ->where('active', 1)
            ->get()
            ->getRow();
        if (! $row) {
            return redirect()->to(site_url('login'))->with('error', 'No hay un usuario activo en este laboratorio para el acceso de soporte. Verifique que el tenant esté aprovisionado.');
        }
        if (strtolower(trim((string) ($row->username ?? ''))) !== $expectUser) {
            return redirect()->to(site_url('login'))->with('error', 'El usuario de soporte no coincide con este laboratorio.');
        }

        $centralPersonId = (int) ($payload['ghost_central_person_id'] ?? 0);
        (new GhostTenantAccessService())->applyGhostSession(
            $centralPersonId,
            $tenantKey,
            ['person_id' => $personId, 'username' => $expectUser],
            $centralPersonId > 0 && $centralPersonId !== $personId
        );
        $agent = service('request')->getUserAgent();
        session()->set('login_user_agent', $agent ? $agent->getAgentString() : '');

        return redirect()->to(site_url('home?tenant=' . rawurlencode($tenantKey)));
    }
}
