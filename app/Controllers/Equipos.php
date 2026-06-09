<?php

namespace App\Controllers;

use App\Libraries\PdfService;
use App\Models\EquipoModel;
use App\Services\RegisterService;

class Equipos extends SecureArea
{
    protected ?string $moduleId = 'equipos';
    protected EquipoModel $model;

    public function __construct()
    {
        parent::__construct();
        $this->model = model(EquipoModel::class);
    }

    public function index()
    {
        $equipos = $this->model->getAll();
        return view('equipos/index', [
            'equipos'         => $equipos,
            'allowed_modules' => $this->allowed_modules,
            'user_info'       => $this->user_info,
            'current_module'  => 'equipos',
        ]);
    }

    public function detalle($equipoId)
    {
        $equipoId = (int) $equipoId;
        $equipo = $this->model->getEquipo($equipoId);
        if (!$equipo) return redirect()->to('equipos')->with('error', 'Equipo no encontrado');
        $mantenimientos = $this->model->getMantenimientos($equipoId);
        return view('equipos/detalle', [
            'equipo'           => $equipo,
            'mantenimientos'   => $mantenimientos,
            'current_module'   => 'equipos',
            'extra_head_links' => [
                '<link rel="stylesheet" href="' . base_url('css/vendor/flatpickr.min.css') . '">',
                '<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/themes/material_green.css">',
                '<script src="' . base_url('js/vendor/flatpickr.min.js') . '"></script>',
                '<script src="https://cdn.jsdelivr.net/npm/flatpickr@4.6.13/dist/l10n/es.js"></script>',
            ],
            'allowed_modules' => $this->allowed_modules,
            'user_info'       => $this->user_info,
        ]);
    }

    public function saveequipo()
    {
        $id = (int) ($this->request->getPost('equipo_id') ?? 0);
        $this->model->saveEquipo([
            'nombre' => $this->request->getPost('nombre') ?? '',
            'codigo' => $this->request->getPost('codigo') ?? '',
            'ubicacion' => $this->request->getPost('ubicacion') ?? '',
        ], $id > 0 ? $id : null);
        \App\Models\AuditoriaModel::log('equipos', $id > 0 ? 'actualizar' : 'crear', $id > 0 ? (string) $id : null);
        return redirect()->to('equipos')->with('success', 'Equipo guardado');
    }

    public function savemantenimiento()
    {
        $equipoId = (int) ($this->request->getPost('equipo_id') ?? 0);
        $this->model->saveMantenimiento([
            'equipo_id' => $equipoId,
            'tipo' => (int) ($this->request->getPost('tipo') ?? 1),
            'fecha' => $this->request->getPost('fecha') ?? lab_today_ymd(),
            'descripcion' => $this->request->getPost('descripcion') ?? '',
            'realizado_por' => session()->get('person_id'),
        ]);
        \App\Models\AuditoriaModel::log('equipos', 'mantenimiento', (string) $equipoId);
        return redirect()->to("equipos/detalle/{$equipoId}")->with('success', 'Mantenimiento registrado');
    }

    /**
     * Vista del historial de mantenimientos lista para imprimir.
     */
    public function historialPrint($equipoId)
    {
        $data = $this->buildHistorialData((int) $equipoId);
        if ($data === null) {
            return redirect()->to('equipos')->with('error', 'Equipo no encontrado');
        }
        $data['show_toolbar'] = true;

        \App\Models\AuditoriaModel::log('equipos', 'imprimir', (string) (int) $equipoId);

        return view('equipos/historial_document', $data);
    }

    /**
     * Exporta el historial de mantenimientos a PDF.
     */
    public function historialPdf($equipoId)
    {
        $equipoId = (int) $equipoId;
        $data = $this->buildHistorialData($equipoId);
        if ($data === null) {
            return redirect()->to('equipos')->with('error', 'Equipo no encontrado');
        }
        $data['show_toolbar'] = false;

        $html = view('equipos/historial_document', $data);
        $slug = preg_replace('/[^a-zA-Z0-9_-]+/', '_', (string) ($data['equipo']['codigo'] ?? $data['equipo']['nombre'] ?? 'equipo')) ?: 'equipo';
        $filename = 'historial_equipo_' . $slug . '_' . lab_filename_date() . '.pdf';

        \App\Models\AuditoriaModel::log('equipos', 'exportar_pdf', (string) $equipoId);

        (new PdfService())->download($html, $filename);
    }

    /**
     * @return array<string, mixed>|null
     */
    private function buildHistorialData(int $equipoId): ?array
    {
        $equipo = $this->model->getEquipo($equipoId);
        if (!$equipo) {
            return null;
        }

        helper('layout');

        return [
            'equipo'           => $equipo,
            'mantenimientos'   => $this->model->getMantenimientos($equipoId),
            'company_name'     => layout_config()['company'] ?? 'Laboratorio',
            'generado_en'      => RegisterService::formatNowForReportShort(),
        ];
    }
}
