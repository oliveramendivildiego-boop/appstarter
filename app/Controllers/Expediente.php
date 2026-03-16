<?php

namespace App\Controllers;

use App\Models\RegisterModel;

/**
 * Historial/Expediente por paciente.
 * Coordina búsqueda y visualización del historial de estudios.
 */
class Expediente extends SecureArea
{
    protected ?string $moduleId = 'registers';

    protected RegisterModel $registerModel;

    public function __construct()
    {
        parent::__construct();
        $this->registerModel = model(RegisterModel::class);
    }

    public function index()
    {
        return view('expediente/search', [
            'allowed_modules'  => $this->allowed_modules,
            'user_info'        => $this->user_info,
            'current_module'   => 'expediente',
        ]);
    }

    /**
     * Historial de un paciente (todos sus registros)
     */
    public function view($personId = 0)
    {
        $personId = (int) $personId;
        if ($personId < 1) {
            return redirect()->to('expediente')->with('error', 'Paciente no válido');
        }

        $paciente = $this->registerModel->getPatientById($personId);
        if (!$paciente) {
            return redirect()->to('expediente')->with('error', 'Paciente no encontrado');
        }

        $registros = $this->registerModel->getRegistrosByPersonId($personId, 100);
        $antecedentes = $this->registerModel->getAntecedentesPaciente($personId, 0, 10);

        $pacienteNombre = trim(($paciente->first_name ?? '') . ' ' . ($paciente->last_name_fa ?? '') . ' ' . ($paciente->last_name_mom ?? ''));
        if ($paciente->birthday ?? null) {
            $fechaNac = new \DateTime($paciente->birthday);
            $hoy = new \DateTime();
            $edad = $fechaNac->diff($hoy);
            $paciente->edad_texto = $edad->y . ' años';
        } else {
            $paciente->edad_texto = '-';
        }

        return view('expediente/historial', [
            'paciente'        => $paciente,
            'pacienteNombre'  => $pacienteNombre,
            'registros'       => $registros,
            'antecedentes'    => $antecedentes,
            'allowed_modules' => $this->allowed_modules,
            'user_info'       => $this->user_info,
            'current_module'  => 'expediente',
        ]);
    }

    /**
     * Búsqueda AJAX de pacientes para autocompletado
     */
    public function search()
    {
        $q = $this->request->getPost('q') ?? $this->request->getPost('paciente') ?? $this->request->getGet('q') ?? '';
        $suggestions = $this->registerModel->searchPacienteForExpediente($q);
        
        // Devolver las sugerencias junto con el nuevo token CSRF regenerado
        return $this->response->setJSON([
            'suggestions' => $suggestions,
            'csrf_token' => csrf_hash(),
            'csrf_token_name' => csrf_token(),
        ]);
    }
}
