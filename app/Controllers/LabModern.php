<?php

namespace App\Controllers;

use App\Models\AuditoriaModel;
use CodeIgniter\HTTP\ResponseInterface;

/**
 * Herramientas de visualización moderna (aditivas, no alteran flujos existentes).
 */
class LabModern extends SecureArea
{
    protected ?string $moduleId = 'home';

    public function trazabilidad()
    {
        return view('lab_modern/trazabilidad', [
            'allowed_modules' => $this->allowed_modules,
            'user_info'       => $this->user_info,
            'current_module'  => 'auditoria',
        ]);
    }

    public function procesos()
    {
        return view('lab_modern/procesos', [
            'allowed_modules' => $this->allowed_modules,
            'user_info'       => $this->user_info,
            'current_module'  => 'registers',
        ]);
    }

    public function trazabilidadData(): ResponseInterface
    {
        $registroId = trim((string) ($this->request->getGet('registro_id') ?? ''));
        $limit      = min(300, max(20, (int) ($this->request->getGet('limit') ?? 150)));

        $model = model(AuditoriaModel::class);
        $rows  = $registroId !== ''
            ? $model->getPorRegistro($registroId, $limit)
            : $model->getRecientes($limit);

        $nodes = [];
        $edges = [];
        $byRegistro = [];

        foreach ($rows as $i => $r) {
            $id = 'a' . ($r['auditoria_id'] ?? $i);
            $usuario = trim(($r['first_name'] ?? '') . ' ' . ($r['last_name_fa'] ?? ''));
            $label = ($r['accion'] ?? '') . "\n" . ($r['modulo'] ?? '');
            $nodes[] = [
                'data' => [
                    'id'    => $id,
                    'label' => $label,
                    'modulo'=> $r['modulo'] ?? '',
                ],
            ];

            $rid = trim((string) ($r['registro_id'] ?? ''));
            if ($rid !== '') {
                if (! isset($byRegistro[$rid])) {
                    $byRegistro[$rid] = [];
                }
                $byRegistro[$rid][] = $id;
            }
        }

        foreach ($byRegistro as $chain) {
            for ($j = 1, $c = count($chain); $j < $c; $j++) {
                $edges[] = [
                    'data' => [
                        'source' => $chain[$j - 1],
                        'target' => $chain[$j],
                    ],
                ];
            }
        }

        if (empty($edges) && count($nodes) > 1) {
            for ($k = 1, $n = count($nodes); $k < $n; $k++) {
                $edges[] = [
                    'data' => [
                        'source' => $nodes[$k - 1]['data']['id'],
                        'target' => $nodes[$k]['data']['id'],
                    ],
                ];
            }
        }

        return $this->response->setJSON([
            'nodes' => $nodes,
            'edges' => $edges,
            'total' => count($nodes),
        ]);
    }
}
