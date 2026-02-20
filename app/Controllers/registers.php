<?php

namespace App\Controllers;

use App\Libraries\PdfService;
use App\Services\RegisterService;
use App\Models\LabotestModel;
use App\Models\RegisterModel;
use App\Models\PerfilExamenModel;
use App\Models\MuestraModel;
use App\Models\AppConfigModel;
use CodeIgniter\HTTP\ResponseInterface;

/**
 * Registros de análisis de laboratorio.
 * Coordina request → service/model → response.
 */
class Registers extends SecureArea
{
    protected ?string $moduleId = 'registers';

    protected RegisterModel $registerModel;
    protected LabotestModel $labotestModel;
    protected RegisterService $registerService;

    public function __construct()
    {
        parent::__construct();
        helper('table');
        $this->registerModel  = model(RegisterModel::class);
        $this->labotestModel  = model(LabotestModel::class);
        $this->registerService = new RegisterService();
    }

    public function index()
    {
        $categories = $this->labotestModel->getGroupedByCategory();
        $perfiles = (model(PerfilExamenModel::class))->getAll();

        return view('registers/manage', [
            'categories'      => $categories,
            'perfiles'        => $perfiles ?? [],
            'allowed_modules' => $this->allowed_modules,
            'user_info'       => $this->user_info,
            'controller_name' => 'registers',
        ]);
    }

    public function lista()
    {
        $perPage    = 20;
        $page       = max(1, (int) ($this->request->getGet('page') ?? 1));
        $offset     = ($page - 1) * $perPage;
        $search     = trim((string) ($this->request->getGet('q') ?? ''));

        if ($search !== '') {
            $registros  = $this->registerModel->getAllAnalisisWithSearch($search, $perPage, $offset);
            $total      = $this->registerModel->countWithSearch($search);
        } else {
            $registros  = $this->registerModel->getAllAnalisis($perPage, $offset);
            $total      = $this->registerModel->countAll();
        }

        $manageTable = $this->buildRegistrosTable($registros);
        $totalPages  = $total > 0 ? (int) ceil($total / $perPage) : 1;

        return view('registers/lista', [
            'manage_table'    => $manageTable,
            'allowed_modules' => $this->allowed_modules,
            'user_info'       => $this->user_info,
            'controller_name' => 'registers',
            'page'            => $page,
            'totalPages'      => $totalPages,
            'total'           => $total,
            'perPage'         => $perPage,
            'search'          => $search,
        ]);
    }

    private function buildRegistrosTable(array $registros): string
    {
        $html = '<div class="table-responsive"><table class="table table-bordered table-striped"><thead><tr>';
        $html .= '<th>Código</th><th>Paciente</th><th>Doctor</th><th>Total</th><th>A cuenta</th><th>Saldo</th><th class="text-center">Acciones</th>';
        $html .= '</tr></thead><tbody>';
        foreach ($registros as $r) {
            $rid = (int) ($r->registro_id ?? 0);
            $html .= '<tr>';
            $html .= '<td>' . esc($r->registro_id ?? '') . '</td>';
            $html .= '<td>' . esc($r->paciente ?? '') . '</td>';
            $html .= '<td>' . esc($r->doctor ?? '') . '</td>';
            $html .= '<td>' . esc($r->total ?? '') . '</td>';
            $html .= '<td>' . esc($r->acuenta ?? '') . '</td>';
            $html .= '<td>' . esc($r->saldo ?? '') . '</td>';
            $html .= '<td class="text-center">';
            $hasRegvalues = isset($r->regvalues_count) && (int) $r->regvalues_count > 0;
            $btnTitle = $hasRegvalues ? 'Editar' : 'Agregar';
            $btnIcon = $hasRegvalues ? 'fa-pen' : 'fa-plus';
            $html .= '<a href="' . site_url('registers/view/' . $rid) . '" class="btn btn-sm btn-outline-primary" title="' . esc($btnTitle) . '"><i class="fa-solid ' . esc($btnIcon) . '"></i></a> ';
            $html .= '<a href="' . site_url('registers/viewreport/' . $rid) . '" class="btn btn-sm btn-secondary" title="Reporte">Reporte</a> ';
            $html .= '<a href="' . site_url('registers/pdf/' . $rid) . '" class="btn btn-sm btn-success" target="_blank" title="PDF">PDF</a> ';
            $html .= '<button type="button" class="btn btn-sm btn-outline-danger btn-eliminar-registro" data-id="' . $rid . '" title="Eliminar"><i class="fa-solid fa-trash"></i></button>';
            $html .= '</td>';
            $html .= '</tr>';
        }
        if (empty($registros)) {
            $html .= '<tr><td colspan="7">No hay registros.</td></tr>';
        }
        $html .= '</tbody></table></div>';
        return $html;
    }

    public function searchPaciente(): ResponseInterface
    {
        $search = $this->request->getPost('paciente') ?? $this->request->getPost('q') ?? '';
        $data   = $this->registerModel->searchPaciente($search);
        return $this->response->setJSON($data);
    }

    public function searchDoctor(): ResponseInterface
    {
        $search = $this->request->getPost('doctor') ?? $this->request->getPost('q') ?? '';
        $data   = $this->registerModel->searchDoctor($search);
        return $this->response->setJSON($data);
    }

    public function searchPrueba(): ResponseInterface
    {
        $search = $this->request->getPost('prueba') ?? $this->request->getPost('q') ?? '';
        $data   = $this->registerModel->searchPrueba($search);
        return $this->response->setJSON($data);
    }

    public function view($id = -1)
    {
        $id = (int) $id;
        if ($id < 1) {
            return redirect()->to('registers')->with('error', 'Registro no válido');
        }

        $registerInfo = $this->registerModel->getInfoRefill($id);
        if (!$registerInfo) {
            return redirect()->to('registers')->with('error', 'Registro no encontrado');
        }

        $pacienteType = $this->registerService->computePacienteType($registerInfo);
        $registerInfo->paciente = $pacienteType;
        $patientGender = isset($registerInfo->gender) ? (int) $registerInfo->gender : null;
        $matchingPoblacionIds = $this->registerService->getMatchingPoblacionIds($registerInfo->birthday ?? null, $patientGender);
        $pruebasInfo = $this->registerModel->getPruebasInput($registerInfo->pruebas ?? '', $matchingPoblacionIds, $patientGender);

        $muestraModel = model(MuestraModel::class);
        $muestra = $muestraModel->getByRegistro($id);
        $tiposMuestra = $muestraModel->getTiposMuestra();

        $decimalesSugerencia = (int) (model(AppConfigModel::class)->getValue('decimales_sugerencia') ?: 2);
        $decimalesSugerencia = max(0, min(10, $decimalesSugerencia));

        $analisis = $this->registerModel->getInfoAnalisis($id);

        return view('registers/formfill', [
            'muestra' => $muestra,
            'tipos_muestra' => $tiposMuestra,
            'controller_name'   => 'registers',
            'register_info'     => $registerInfo,
            'pruebas_info'      => $pruebasInfo,
            'analisis'          => $analisis,
            'labotests_namecate' => $id,
            'registerModel'     => $this->registerModel,
            'decimales_sugerencia' => $decimalesSugerencia,
            'allowed_modules'   => $this->allowed_modules,
            'user_info'         => $this->user_info,
        ]);
    }

    public function viewreport($id = -1)
    {
        $id = (int) $id;
        if ($id < 1) {
            return redirect()->to('registers')->with('error', 'Registro no válido');
        }

        $data = $this->registerService->prepareReportData($id);
        if (!$data) {
            return redirect()->to('registers')->with('error', 'Registro no encontrado');
        }

        return view('registers/viewreport', [
            'controller_name'   => 'registers',
            'register_info'     => $data['register_info'],
            'labotests_namecate' => $id,
            'paciente'          => $data['paciente'],
            'doctor'            => $data['doctor'],
            'analisis'          => $data['analisis'],
            'grupos'            => $data['grupos'],
            'registerModel'     => $this->registerModel,
            'allowed_modules'   => $this->allowed_modules,
            'user_info'         => $this->user_info,
        ]);
    }

    public function pdf($id = -1)
    {
        $id = (int) $id;
        if ($id < 1) {
            return redirect()->to('registers')->with('error', 'Registro no válido');
        }

        $data = $this->registerService->prepareReportData($id);
        if (!$data) {
            return redirect()->to('registers')->with('error', 'Registro no encontrado');
        }

        $labConfig = $this->registerService->getLabConfig();
        $html = view('registers/report_pdf', [
            'register_info' => $data['register_info'],
            'paciente'      => $data['paciente'],
            'doctor'        => $data['doctor'],
            'grupos'        => $data['grupos'],
            'lab_config'    => $labConfig,
        ]);

        $pdfService    = new PdfService();
        $pacienteNombre = trim(($data['paciente']->first_name ?? '') . '_' . ($data['paciente']->last_name_fa ?? ''));
        $filename      = 'Resultados_' . ($pacienteNombre ?: 'paciente') . '_' . $id . '_' . date('Y-m-d') . '.pdf';

        return $this->response
            ->setHeader('Content-Type', 'application/pdf')
            ->setHeader('Content-Disposition', 'attachment; filename="' . $filename . '"')
            ->setBody($pdfService->generate($html, $filename));
    }

    public function save(): ResponseInterface
    {
        try {
            $registro = $this->request->getPost('registro');
            $pagos    = $this->request->getPost('pagos');

            if (empty($registro['person_id']) || empty($registro['doctor_id'])) {
                return $this->response->setJSON([
                    'success' => false,
                    'message' => 'Debe seleccionar paciente y doctor.',
                ])->setStatusCode(400);
            }

            $registroData = [
                'person_id'  => $registro['person_id'] ?? null,
                'doctor_id'  => $registro['doctor_id'] ?? null,
                'pruebas'    => $registro['pruebas'] ?? null,
                'prioridad'  => (int) ($registro['prioridad'] ?? 0),
                'id_session' => session()->get('person_id'),
            ];

            $registroId = $this->registerModel->saveRegistro($registroData);
            \App\Models\AuditoriaModel::log('registers', 'crear', (string) $registroId);

            $pagosData = [
                'registro_id'  => $registroId,
                'total_reco'   => $pagos['total_reco'] ?? null,
                'total'        => $pagos['total'] ?? null,
                'monto_pagar'  => $pagos['monto_pagar'] ?? null,
                'tipopago'     => $pagos['tipopago'] ?? null,
                'saldo'        => $pagos['saldo'] ?? null,
                'comentarios'  => $pagos['comentarios'] ?? null,
            ];
            $this->registerModel->savePago($pagosData);

            return $this->response->setJSON([
                'success' => true,
                'message' => 'Datos guardados correctamente',
                'id'      => $registroId,
            ]);
        } catch (\Throwable $e) {
            log_message('error', 'Registers::save ' . $e->getMessage());
            return $this->response->setJSON([
                'success' => false,
                'message' => 'Error al guardar. Intente nuevamente.',
            ])->setStatusCode(500);
        }
    }

    public function delete($id): ResponseInterface
    {
        $id = (int) $id;
        if ($id < 1) {
            return $this->response->setJSON(['success' => false, 'message' => 'ID inválido'])->setStatusCode(400);
        }
        if (!$this->registerModel->existsRegistro($id)) {
            return $this->response->setJSON(['success' => false, 'message' => 'Registro no encontrado'])->setStatusCode(404);
        }
        try {
            $this->registerModel->deleteRegistro($id);
            \App\Models\AuditoriaModel::log('registers', 'eliminar', (string) $id);
            return $this->response->setJSON(['success' => true, 'message' => 'Registro eliminado']);
        } catch (\Throwable $e) {
            log_message('error', 'Registers::delete ' . $e->getMessage());
            return $this->response->setJSON(['success' => false, 'message' => 'Error al eliminar'])->setStatusCode(500);
        }
    }

    public function crearmuestra()
    {
        $registroId = (int) ($this->request->getPost('registro_id') ?? 0);
        if ($registroId < 1) return redirect()->back()->with('error', 'Registro no válido');
        $muestraModel = model(MuestraModel::class);
        if ($muestraModel->getByRegistro($registroId)) {
            return redirect()->to("registers/view/{$registroId}")->with('error', 'Ya existe una muestra para este registro');
        }
        $muestraModel->saveMuestra([
            'registro_id' => $registroId,
            'tipo_muestra_id' => (int) ($this->request->getPost('tipo_muestra_id') ?? 1),
            'estado' => 0,
            'fecha_tomada' => date('Y-m-d H:i:s'),
            'usuario_tomo' => session()->get('person_id'),
        ]);
        return redirect()->to("registers/view/{$registroId}")->with('success', 'Muestra creada');
    }

    public function cambiarestadomuestra($muestraId, $nuevoEstado)
    {
        $muestraId = (int) $muestraId;
        $nuevoEstado = (int) $nuevoEstado;
        $muestraModel = model(MuestraModel::class);
        $muestra = $muestraModel->getById($muestraId);
        if (!$muestra) return redirect()->back()->with('error', 'Muestra no encontrada');
        $muestraModel->cambiarEstado($muestraId, $nuevoEstado, (int) session()->get('person_id'));
        return redirect()->to("registers/view/" . (int)($muestra['registro_id'] ?? 0))->with('success', 'Estado actualizado');
    }

    public function saveregvalues(): ResponseInterface
    {
        $data = $this->request->getPost('data');
        $data = is_string($data) ? json_decode($data, true) : $data;

        if (!is_array($data)) {
            return $this->response->setJSON(['success' => false, 'message' => 'Datos inválidos'])->setStatusCode(400);
        }

        $registroId = null;
        foreach ($data as $item) {
            $rid = isset($item['registro_id']) ? (int) $item['registro_id'] : 0;
            if ($rid > 0) {
                $registroId = $rid;
                break;
            }
        }
        if ($registroId !== null) {
            $this->registerModel->deleteRegvaluesByRegistroId($registroId);
        }

        foreach ($data as $item) {
            $this->registerModel->saveRegvalues([
                'regvalues'   => $item['valor'] ?? null,
                'registro_id' => $item['registro_id'] ?? null,
                'name'        => $item['id'] ?? null,
                'id_session'  => session()->get('person_id'),
            ]);
        }
        return $this->response->setJSON(['success' => true, 'message' => 'Guardado exitoso']);
    }

    public function saveanalisiss(): ResponseInterface
    {
        $data = $this->request->getPost('data');
        $data = is_string($data) ? json_decode($data, true) : $data;

        if (!is_array($data)) {
            return $this->response->setJSON(['success' => false, 'message' => 'Datos inválidos'])->setStatusCode(400);
        }

        foreach ($data as $item) {
            $this->registerModel->saveAnalisis([
                'padre'       => $item['padre'] ?? null,
                'hijo'        => $item['hijo'] ?? null,
                'analisis'    => $item['analisis'] ?? null,
                'valor'       => $item['valor'] ?? null,
                'unidad'      => $item['unidad'] ?? null,
                'minimo'      => $item['minimo'] ?? null,
                'maximo'      => $item['maximo'] ?? null,
                'registro_id' => $item['registro_id'] ?? null,
                'id_session'  => session()->get('person_id'),
                'estado_id'   => 1,
            ]);
        }
        return $this->response->setJSON(['success' => true, 'message' => 'Guardado exitoso']);
    }

    public function validar()
    {
        $registroId = (int) ($this->request->getPost('registro_id') ?? 0);
        $tipo = $this->request->getPost('tipo') ?? '';
        $obs = trim($this->request->getPost('observaciones') ?? '') ?: null;
        if ($registroId < 1 || !in_array($tipo, ['tecnico', 'medico'])) {
            return redirect()->back()->with('error', 'Datos inválidos');
        }
        $this->registerModel->validarResultados($registroId, $tipo, $obs);
        $msg = $tipo === 'tecnico' ? 'Validación técnica registrada' : 'Validación médica registrada';
        return redirect()->to("registers/viewreport/{$registroId}")->with('success', $msg);
    }
}
