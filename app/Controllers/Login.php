<?php

namespace App\Controllers;

use App\Models\EmployeeModel;
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
            if (!class_exists('\Google_Client')) {
                return $this->response->setJSON(['success' => false, 'message' => lang('Login.login_google_not_configured')]);
            }
            $client = new \Google_Client(['client_id' => config('Google')->clientId]);
            $payload = $client->verifyIdToken($idToken);
            if (!$payload) {
                return $this->response->setJSON(['success' => false, 'message' => 'Token inválido']);
            }
            $email = $payload['email'] ?? '';
            $employeeModel = model(EmployeeModel::class);
            if ($employeeModel->loginByEmail($email)) {
                return $this->response->setJSON(['success' => true]);
            }
            return $this->response->setJSON(['success' => false, 'message' => lang('Login.login_google_no_account')]);
        } catch (\Throwable $e) {
            return $this->response->setJSON(['success' => false, 'message' => $e->getMessage()]);
        }
    }
}
