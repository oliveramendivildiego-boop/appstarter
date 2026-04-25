<?php

namespace App\Controllers;

use App\Models\AuditoriaModel;
use App\Models\EmployeeModel;

class Account extends SecureArea
{
    public function password()
    {
        return view('account/password', [
            'allowed_modules' => $this->allowed_modules,
            'user_info'       => $this->user_info,
            'current_module'  => 'account',
        ]);
    }

    public function updatePassword()
    {
        $validation = \Config\Services::validation();
        $validation->setRules([
            'current_password' => [
                'rules'  => 'required',
                'errors' => ['required' => 'Ingrese su contraseña actual.'],
            ],
            'new_password' => [
                'rules'  => 'required|min_length[4]',
                'errors' => [
                    'required'   => 'Ingrese la nueva contraseña.',
                    'min_length' => 'La nueva contraseña debe tener al menos 4 caracteres.',
                ],
            ],
            'confirm_password' => [
                'rules'  => 'required|matches[new_password]',
                'errors' => [
                    'required' => 'Confirme la nueva contraseña.',
                    'matches'  => 'La confirmación no coincide con la nueva contraseña.',
                ],
            ],
        ]);

        if (!$validation->withRequest($this->request)->run()) {
            return redirect()->back()->withInput()->with('errors', $validation->getErrors());
        }

        $personId = (int) session()->get('person_id');
        $employeeModel = model(EmployeeModel::class);
        $currentPassword = (string) $this->request->getPost('current_password');
        $newPassword = (string) $this->request->getPost('new_password');

        if (!$employeeModel->verifyPassword($personId, $currentPassword)) {
            return redirect()->back()->with('error', 'La contraseña actual no es correcta.');
        }

        if (!$employeeModel->updatePassword($personId, $newPassword)) {
            return redirect()->back()->with('error', 'No se pudo actualizar la contraseña. Intente nuevamente.');
        }

        AuditoriaModel::log('account', 'cambiar_password', (string) $personId, AuditoriaModel::detail([
            'usuario' => $this->user_info->username ?? '',
        ]));

        return redirect()->to(site_url('account/password'))->with('success', 'Contraseña actualizada correctamente.');
    }
}
