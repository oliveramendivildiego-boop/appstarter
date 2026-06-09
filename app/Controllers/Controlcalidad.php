<?php

namespace App\Controllers;

use App\Libraries\PdfService;
use App\Libraries\QcStatistics;
use App\Models\ControlCalidadModel;
use CodeIgniter\HTTP\ResponseInterface;

class Controlcalidad extends SecureArea
{
    protected ?string $moduleId = 'controlcalidad';
    protected ControlCalidadModel $model;

    public function __construct()
    {
        parent::__construct();
        $this->model = model(ControlCalidadModel::class);
    }

    public function index()
    {
        $controles = $this->model->getAll();
        return view('controlcalidad/index', [
            'controles'        => $controles,
            'allowed_modules'  => $this->allowed_modules,
            'user_info'        => $this->user_info,
            'current_module'   => 'controlcalidad',
        ]);
    }

    public function grafica($controlId)
    {
        $controlId = (int) $controlId;
        $data = $this->buildGraficaData(
            $controlId,
            $this->request->getGet('fecha_ini'),
            $this->request->getGet('fecha_fin')
        );
        if ($data === null) {
            return redirect()->to('controlcalidad')->with('error', 'Control no encontrado');
        }

        return view('controlcalidad/grafica', array_merge($data, [
            'allowed_modules' => $this->allowed_modules,
            'current_module'  => 'controlcalidad',
            'user_info'       => $this->user_info,
        ]));
    }

    public function graficaPdf($controlId): ResponseInterface
    {
        $controlId = (int) $controlId;
        $data = $this->buildGraficaData(
            $controlId,
            $this->request->getGet('fecha_ini'),
            $this->request->getGet('fecha_fin')
        );
        if ($data === null) {
            return redirect()->to('controlcalidad')->with('error', 'Control no encontrado');
        }

        helper('layout');
        $layoutCfg = layout_config();

        $html = view('controlcalidad/grafica_pdf', array_merge($data, [
            'lab_config' => $layoutCfg,
            'generado_en' => \App\Services\RegisterService::formatNowForReportShort(),
        ]));

        $controlName = preg_replace('/[^a-zA-Z0-9_-]+/', '_', (string) ($data['control']['nombre'] ?? 'control')) ?: 'control';
        $filename = 'control_calidad_' . $controlName . '_' . lab_filename_date() . '.pdf';

        \App\Models\AuditoriaModel::log('controlcalidad', 'exportar_pdf', (string) $controlId);

        $pdfContent = (new PdfService())->generate($html, $filename);

        return $this->response
            ->setHeader('Content-Type', 'application/pdf')
            ->setHeader('Content-Disposition', 'attachment; filename="' . $filename . '"')
            ->setBody($pdfContent);
    }

    /**
     * @return array<string, mixed>|null
     */
    private function buildGraficaData(int $controlId, ?string $fechaIni, ?string $fechaFin): ?array
    {
        $control = $this->model->getById($controlId);
        if (!$control) {
            return null;
        }

        $fechaIni = $fechaIni ?? lab_date_ymd('-30 days');
        $fechaFin = $fechaFin ?? lab_today_ymd();
        $valores = $this->model->getValores($controlId, $fechaIni, $fechaFin);

        $numeric = array_map(static fn ($v) => (float) ($v['valor'] ?? 0), $valores);
        $qcStats = QcStatistics::sampleStats($numeric);

        $series = [];
        foreach ($valores as $v) {
            $series[] = [
                'fecha' => (string) ($v['fecha'] ?? ''),
                'valor' => (float) ($v['valor'] ?? 0),
            ];
        }

        $westgard = [];
        if ($qcStats !== null && $qcStats['sd'] > 0) {
            $westgard = QcStatistics::evaluateWestgard($series, $qcStats['mean'], $qcStats['sd']);
        }

        return [
            'control'   => $control,
            'valores'   => $valores,
            'fecha_ini' => $fechaIni,
            'fecha_fin' => $fechaFin,
            'qc_stats'  => $qcStats,
            'westgard'  => $westgard,
        ];
    }

    public function savecontrol()
    {
        $id = (int) ($this->request->getPost('control_id') ?? 0);
        $post = $this->request->getPost();
        $payload = [
            'nombre' => $post['nombre'] ?? '',
            'tipo'   => (int) ($post['tipo'] ?? 1),
        ];
        if (array_key_exists('sesgo', $post)) {
            $rawSesgo = $post['sesgo'];
            $payload['sesgo'] = ($rawSesgo === null || $rawSesgo === '') ? null : $rawSesgo;
        }
        $this->model->saveControl($payload, $id > 0 ? $id : null);
        \App\Models\AuditoriaModel::log('controlcalidad', $id > 0 ? 'actualizar' : 'crear', $id > 0 ? (string) $id : null);
        if ($id > 0) {
            return redirect()->to("controlcalidad/grafica/{$id}")->with('success', 'Control actualizado');
        }
        return redirect()->to('controlcalidad')->with('success', 'Control guardado');
    }

    public function savevalor()
    {
        $controlId = (int) ($this->request->getPost('control_id') ?? 0);
        $this->model->saveValor([
            'control_id'     => $controlId,
            'fecha'          => $this->request->getPost('fecha') ?? lab_today_ymd(),
            'valor'          => $this->request->getPost('valor') ?? 0,
            'esperado'       => $this->request->getPost('esperado') ?: null,
            'observaciones'  => $this->request->getPost('observaciones') ?? '',
        ]);
        \App\Models\AuditoriaModel::log('controlcalidad', 'valor_registrar', (string) $controlId);
        return redirect()->to("controlcalidad/grafica/{$controlId}")->with('success', 'Valor registrado');
    }
}
