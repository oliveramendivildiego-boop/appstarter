<?php

namespace App\Controllers;

use App\Models\EmployeeModel;
use App\Models\DoctorModel;
use CodeIgniter\HTTP\ResponseInterface;

class Login extends BaseController
{
    public function index()
    {
        $employeeModel = model(EmployeeModel::class);

        if ($employeeModel->isLoggedIn()) {
            return redirect()->to(site_url('home'));
        }

        if (strtolower($this->request->getMethod()) === 'post') {
            $username = $this->request->getPost('username');
            $password = $this->request->getPost('password');

            if ($employeeModel->login($username ?? '', $password ?? '')) {
                if ($this->request->isAJAX()) {
                    return $this->response->setJSON([
                        'success'  => true,
                        'redirect' => site_url('home'),
                    ]);
                }
                return redirect()->to(site_url('home'));
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
                    'redirect' => site_url('home'),
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
}
