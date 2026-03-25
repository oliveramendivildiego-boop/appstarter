<?php

namespace App\Controllers;

use App\Models\LeyendaModel;

class Leyendas extends SecureArea
{
    protected ?string $moduleId = 'leyendas';
    protected LeyendaModel $leyendaModel;

    public function __construct()
    {
        parent::__construct();
        $this->leyendaModel = model(LeyendaModel::class);
    }

    public function index()
    {
        $editar = (int)($this->request->getGet('editar') ?? 0);
        $leyendaEditar = $editar > 0 ? $this->leyendaModel->getById($editar) : null;

        return view('leyendas/manage', [
            'current_module' => 'leyendas',
            'leyendas'       => $this->leyendaModel->getAll(),
            'leyenda_editar' => $leyendaEditar,
            'allowed_modules'=> $this->allowed_modules,
            'user_info'      => $this->user_info,
        ]);
    }

    public function save()
    {
        $id = (int)($this->request->getPost('leyenda_id') ?? 0);
        $titulo = trim((string)($this->request->getPost('titulo') ?? ''));
        $mensaje = trim((string)($this->request->getPost('mensaje') ?? ''));
        $activo = (int)($this->request->getPost('activo') ?? 1);

        if ($titulo === '' || $mensaje === '') {
            return redirect()->back()->withInput()->with('error', 'Título y mensaje son obligatorios.');
        }

        $ok = $this->leyendaModel->saveLeyenda([
            'titulo'  => $titulo,
            'mensaje' => $mensaje,
            'activo'  => $activo ? 1 : 0,
        ], $id > 0 ? $id : null);

        if (!$ok) {
            return redirect()->back()->withInput()->with('error', 'No se pudo guardar la leyenda.');
        }
        \App\Models\AuditoriaModel::log('leyendas', $id > 0 ? 'actualizar' : 'crear', $id > 0 ? (string)$id : null);
        return redirect()->to('leyendas')->with('success', 'Leyenda guardada correctamente.');
    }

    public function delete($id)
    {
        $id = (int)$id;
        if ($id < 1) {
            return redirect()->to('leyendas')->with('error', 'ID inválido.');
        }
        $ok = $this->leyendaModel->softDeleteLeyenda($id);
        if ($ok) {
            \App\Models\AuditoriaModel::log('leyendas', 'eliminar', (string)$id);
            return redirect()->to('leyendas')->with('success', 'Leyenda eliminada.');
        }
        return redirect()->to('leyendas')->with('error', 'No se pudo eliminar.');
    }
}

