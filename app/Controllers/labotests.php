<?php

namespace App\Controllers;

use App\Models\LabotestModel;
use App\Services\LabotestNameTransformService;
use App\Services\PrianacategoriaReferenceService;
use App\Models\LabotestReactivoConfigModel;
use App\Models\OpcionModel;
use App\Models\PerfilExamenModel;
use App\Models\ReactivoModel;
use App\Models\LeyendaCultivoModel;
use App\Models\MetodoModel;
use App\Models\TipoMuestraModel;
use CodeIgniter\HTTP\ResponseInterface;

class Labotests extends SecureArea
{
    protected ?string $moduleId = 'labotests';

    protected LabotestModel $labotestModel;
    protected OpcionModel $opcionModel;
    protected TipoMuestraModel $tipoMuestraModel;
    protected MetodoModel $metodoModel;
    protected ReactivoModel $reactivoModel;
    protected LabotestReactivoConfigModel $labotestReactivoConfigModel;

    public function __construct()
    {
        parent::__construct();
        helper('form');
        $this->labotestModel    = model(LabotestModel::class);
        $this->opcionModel      = model(OpcionModel::class);
        $this->tipoMuestraModel = model(TipoMuestraModel::class);
        $this->metodoModel      = model(MetodoModel::class);
        $this->reactivoModel    = model(ReactivoModel::class);
        $this->labotestReactivoConfigModel = model(LabotestReactivoConfigModel::class);
    }

    /**
     * @return list<array{tipo_muestra_id: int, nombre: string, deleted?: int}>
     */
    private function loadTiposMuestraForForms(): array
    {
        try {
            return $this->tipoMuestraModel->getAllActive();
        } catch (\Throwable $e) {
            return [];
        }
    }

    /**
     * @return list<array{metodo_id: int, nombre: string, deleted?: int}>
     */
    private function loadMetodosForForms(): array
    {
        try {
            return $this->metodoModel->getAllActive();
        } catch (\Throwable $e) {
            return [];
        }
    }

    public function index()
    {
        $page   = max(1, (int) ($this->request->getGet('page') ?? 1));
        $search = $this->request->getGet('q');
        $search = is_string($search) ? trim($search) : null;
        if ($search === '') {
            $search = null;
        }
        $grupoId = (int) ($this->request->getGet('grupo') ?? 0);
        if ($grupoId < 1) {
            $grupoId = null;
        }

        $result = $this->labotestModel->getGroupedByCategoryPaginated(6, $page, $search, $grupoId);

        return view('labotests/manage', [
            'categories'       => $result['categories'],
            'all_categories'   => ($search === null && $grupoId === null) ? $this->labotestModel->getCategoriesForReorder() : [],
            'total'            => $result['total'],
            'page'             => $result['page'],
            'total_pages'      => $result['total_pages'],
            'search'           => $search,
            'grupo_filter'     => $grupoId,
            'category_options'  => $this->labotestModel->getCategoryOptions(),
            'allowed_modules'  => $this->allowed_modules,
            'user_info'        => $this->user_info,
            'current_module'   => 'labotests',
        ]);
    }

    /**
     * Formulario para agregar/editar grupo (categoría)
     */
    public function view($id = -1)
    {
        $id = (int) $id;
        $info = $this->labotestModel->getCategoryInfo($id);

        return view('labotests/form_group', [
            'labotests_info'   => $info,
            'controller_name'  => 'labotests',
            'allowed_modules'  => $this->allowed_modules,
            'user_info'        => $this->user_info,
            'current_module'   => 'labotests',
        ]);
    }

    /**
     * Formulario para agregar nuevo subgrupo (análisis) dentro de una categoría
     */
    public function subview($anacategoriaId)
    {
        $anacategoriaId = (int) $anacategoriaId;
        $catInfo = $this->labotestModel->getCategoryInfo($anacategoriaId);
        if (!$catInfo->anacategoria_id) {
            return redirect()->to('labotests')->with('error', 'Categoría no encontrada');
        }
        $subInfo = $this->labotestModel->getSubInfo(-1, $anacategoriaId);

        return view('labotests/form_subgroup', [
            'labotests_info'    => $subInfo,
            'labotests_master'  => $catInfo,
            'labotests_namecate'=> $anacategoriaId,
            'tipos_muestra'     => $this->loadTiposMuestraForForms(),
            'metodos_prueba'    => $this->loadMetodosForForms(),
            'controller_name'   => 'labotests',
            'allowed_modules'   => $this->allowed_modules,
            'user_info'         => $this->user_info,
            'current_module'    => 'labotests',
        ]);
    }

    /**
     * Formulario para editar subgrupo con precios (cost, cost_deriv) y configuración de valores
     */
    public function detail($prianacategoriaId)
    {
        $prianacategoriaId = (int) $prianacategoriaId;
        $subInfo = $this->labotestModel->getSubInfo($prianacategoriaId);
        if (!$subInfo->prianacategoria_id) {
            return redirect()->to('labotests')->with('error', 'Análisis no encontrado');
        }
        $catInfo = $this->labotestModel->getCategoryInfo($subInfo->anacategoria_id ?? 0);

        $compleja = (int) ($subInfo->compleja ?? 0);
        $subItems = [];
        $priresultados = [];
        $cultivoMatriz = [];
        $personalizadoMatriz = [];
        $poblaciones = $this->labotestModel->getPoblaciones();
        $formulas = $this->labotestModel->getFormulas();
        try {
            $formulasConExpresion = $this->labotestModel->getFormulasConExpresion();
        } catch (\Throwable $e) {
            $formulasConExpresion = [];
        }
        $formulasCreadas = [1 => $formulas[1] ?? 'Ninguno'];
        $formulasIdCanonical = [1 => 1];
        $formulasConExpresionDeduped = [];
        $nombresVistos = [];
        foreach ($formulasConExpresion ?? [] as $f) {
            $nombre = trim($f['nombre'] ?? '');
            $fid = (int) ($f['formulas_id'] ?? 0);
            if ($nombre === '' || $fid < 1) continue;
            if (!isset($nombresVistos[$nombre])) {
                $nombresVistos[$nombre] = $fid;
                $formulasCreadas[$fid] = $nombre;
                $formulasConExpresionDeduped[] = $f;
            }
            $formulasIdCanonical[$fid] = $nombresVistos[$nombre];
        }
        $opciones = $this->labotestModel->getOpciones();
        $leyendasCultivo = [];
        try {
            $leyendasCultivo = model(LeyendaCultivoModel::class)->getActivas();
        } catch (\Throwable $e) {
            $leyendasCultivo = [];
        }

        $editarSec = (int) ($this->request->getGet('editar') ?? 0);
        $editarPri = (int) ($this->request->getGet('editarpri') ?? 0);
        $editarSecData = [];
        $editarPriData = [];

        if ($compleja === LabotestModel::COMPLEJA_COMPOUESTA) {
            $subItems = $this->labotestModel->getSubItems($prianacategoriaId);
            if ($editarSec > 0) {
                $secRows = array_filter($subItems, fn($s) => (int)($s['secanacategoria_id'] ?? 0) === $editarSec);
                $editarSecData = $secRows ? reset($secRows) : [];
            }
        } elseif ($compleja === LabotestModel::COMPLEJA_CULTIVO) {
            $cultivoMatriz = $this->labotestModel->getCultivoMatrizConfig($prianacategoriaId);
        } elseif ($compleja === LabotestModel::COMPLEJA_PERSONALIZADO) {
            $personalizadoMatriz = $this->labotestModel->getPersonalizadoMatrizConfig($prianacategoriaId);
        } else {
            $priresultados = $this->labotestModel->getPriResultados($prianacategoriaId);
            if ($editarPri > 0) {
                $priRows = array_filter($priresultados, fn($p) => (int)($p['priresultados_id'] ?? 0) === $editarPri);
                $editarPriData = $priRows ? reset($priRows) : [];
            }
        }
        $fidSec = (int) ($editarSecData['formulas_id'] ?? 1);
        $fidPri = (int) ($editarPriData['formulas_id'] ?? 1);
        $nombresInvertidos = array_flip($formulasCreadas);
        foreach ([$fidSec, $fidPri] as $fid) {
            if ($fid > 1 && !isset($formulasCreadas[$fid]) && isset($formulas[$fid])) {
                $nombre = trim($formulas[$fid] ?? '');
                if ($nombre !== '' && isset($nombresInvertidos[$nombre])) {
                    $formulasIdCanonical[$fid] = $nombresInvertidos[$nombre];
                } else if ($nombre !== '') {
                    $formulasCreadas[$fid] = $nombre;
                    $formulasIdCanonical[$fid] = $fid;
                    $nombresInvertidos[$nombre] = $fid;
                }
            }
        }
        ksort($formulasCreadas);

        return view('labotests/form_detail', [
            'labotests_info'    => $subInfo,
            'labotests_master'  => $catInfo,
            'labotests_namecate'=> $subInfo->anacategoria_id,
            'controller_name'   => 'labotests',
            'current_module'    => 'labotests',
            'allowed_modules'   => $this->allowed_modules,
            'user_info'         => $this->user_info,
            'compleja'          => $compleja,
            'sub_items'         => $subItems,
            'priresultados'     => $priresultados,
            'cultivo_matriz'       => $cultivoMatriz ?? [],
            'personalizado_matriz' => $personalizadoMatriz ?? [],
            'poblaciones'       => $poblaciones,
            'formulas'               => $formulas,
            'formulas_creadas'       => $formulasCreadas,
            'formulas_id_canonical'  => $formulasIdCanonical,
            'formulas_con_expresion' => $formulasConExpresion ?? [],
            'formulas_con_expresion_deduped' => $formulasConExpresionDeduped,
            'opciones'               => $opciones,
            'leyendas_cultivo'       => $leyendasCultivo,
            'tipos_muestra'          => $this->loadTiposMuestraForForms(),
            'metodos_prueba'         => $this->loadMetodosForForms(),
            'editar_sec'        => $editarSec,
            'editar_sec_data'   => $editarSecData,
            'editar_pri'        => $editarPri,
            'editar_pri_data'   => $editarPriData,
            'reactivos_catalogo' => $this->reactivoModel->getAllWithStockAndTipo(),
            'reactivos_consumo_config' => $this->labotestReactivoConfigModel->getByPrianacategoria($prianacategoriaId),
            'reactivo_lote_policies' => [
                'fefo' => 'FEFO (vence primero)',
                'fifo' => 'FIFO (ingresa primero)',
                'lifo' => 'LIFO (ingresa ultimo)',
            ],
        ]);
    }

    public function saveReactivoConsumo()
    {
        $prianacategoriaId = (int) ($this->request->getPost('prianacategoria_id') ?? 0);
        $reactivoId        = (int) ($this->request->getPost('reactivo_id') ?? 0);
        $consumoDefault    = max(1, (int) ($this->request->getPost('consumo_default') ?? 1));
        $lotePolicy        = strtolower(trim((string) ($this->request->getPost('lote_policy') ?? 'fefo')));

        if ($prianacategoriaId < 1 || $reactivoId < 1) {
            return redirect()->back()->with('error', 'Debe seleccionar analisis y reactivo');
        }
        if (! in_array($lotePolicy, ['fefo', 'fifo', 'lifo'], true)) {
            $lotePolicy = 'fefo';
        }

        $exists = $this->labotestReactivoConfigModel
            ->where('prianacategoria_id', $prianacategoriaId)
            ->where('reactivo_id', $reactivoId)
            ->where('deleted', 0)
            ->first();

        $data = [
            'prianacategoria_id' => $prianacategoriaId,
            'reactivo_id'        => $reactivoId,
            'consumo_default'    => $consumoDefault,
            'lote_policy'        => $lotePolicy,
            'enabled'            => 1,
            'deleted'            => 0,
        ];

        if ($exists) {
            $this->labotestReactivoConfigModel->update((int) ($exists['config_id'] ?? 0), $data);
        } else {
            $this->labotestReactivoConfigModel->insert($data);
        }

        return redirect()->to("labotests/detail/{$prianacategoriaId}")
            ->with('success', 'Configuracion de consumo guardada');
    }

    public function deleteReactivoConsumo($configId)
    {
        $configId = (int) $configId;
        if ($configId < 1) {
            return redirect()->back()->with('error', 'Configuracion invalida');
        }

        $row = $this->labotestReactivoConfigModel->find($configId);
        if (! $row) {
            return redirect()->back()->with('error', 'Configuracion no encontrada');
        }

        $prianacategoriaId = (int) ($row['prianacategoria_id'] ?? 0);
        $this->labotestReactivoConfigModel->softDeleteConfig($configId);

        return redirect()->to("labotests/detail/{$prianacategoriaId}")
            ->with('success', 'Configuracion eliminada');
    }

    public function save($id = 0)
    {
        $id = (int) $id;
        $name  = $this->request->getPost('name') ?? '';
        $order = (int) ($this->request->getPost('order') ?? 0);

        if (trim($name) === '') {
            return redirect()->back()->withInput()->with('error', 'El nombre es obligatorio');
        }

        $this->labotestModel->saveCategory(['name' => $name, 'order' => $order], $id > 0 ? $id : null);
        \App\Models\AuditoriaModel::log('labotests', $id > 0 ? 'actualizar_categoria' : 'crear_categoria', (string)($id ?: ''), \App\Models\AuditoriaModel::detail(['nombre' => $name]));
        return redirect()->to('labotests')->with('success', 'Guardado correctamente');
    }

    public function savesubmain($anacategoriaId)
    {
        $anacategoriaId = (int) $anacategoriaId;
        $name     = $this->request->getPost('name') ?? '';
        $order    = (int) ($this->request->getPost('order') ?? 0);
        $compleja = (int) ($this->request->getPost('compleja') ?? 0);
        $cost     = (int) ($this->request->getPost('cost') ?? 0);
        $costDeriv= (int) ($this->request->getPost('cost_deriv') ?? 0);
        $mostrarValores = (int) ($this->request->getPost('mostrar_valores') ?? 0);
        $prianacategoriaId = (int) ($this->request->getPost('prianacategoria_id') ?? 0);

        if (trim($name) === '') {
            return redirect()->back()->withInput()->with('error', 'El nombre es obligatorio');
        }

        $data = [
            'name'           => $name,
            'order'          => $order,
            'cost'           => $cost,
            'cost_deriv'     => $costDeriv,
            'compleja'       => $compleja,
            'mostrar_valores'=> $mostrarValores ? 1 : 0,
            'anacategoria_id'=> $anacategoriaId,
            'tipo_muestra_id'=> (int) ($this->request->getPost('tipo_muestra_id') ?? 0),
            'metodo_id'      => (int) ($this->request->getPost('metodo_id') ?? 0),
        ];
        $this->labotestModel->saveSubCategory($data, $prianacategoriaId > 0 ? $prianacategoriaId : null);
        \App\Models\AuditoriaModel::log('labotests', $prianacategoriaId > 0 ? 'actualizar_analisis' : 'crear_analisis', (string)($prianacategoriaId ?: ''), \App\Models\AuditoriaModel::detail(['nombre' => $name, 'compleja' => $compleja]));
        return redirect()->to('labotests')->with('success', 'Análisis guardado correctamente');
    }

    public function savesub()
    {
        $prianacategoriaId = (int) ($this->request->getPost('prianacategoria_id') ?? 0);
        $name     = $this->request->getPost('name') ?? '';
        $order    = (int) ($this->request->getPost('order') ?? 0);
        $cost     = (int) ($this->request->getPost('cost') ?? 0);
        $costDeriv= (int) ($this->request->getPost('cost_deriv') ?? 0);
        $compleja = (int) ($this->request->getPost('compleja') ?? 0);
        $mostrarValores = (int) ($this->request->getPost('mostrar_valores') ?? 0);
        $anacategoriaId = (int) ($this->request->getPost('anacategoria_id') ?? 0);

        if (trim($name) === '' || $prianacategoriaId < 1) {
            if ($this->request->isAJAX()) {
                return $this->response->setJSON([
                    'success' => false,
                    'message' => 'Datos incompletos',
                    'csrf_token' => csrf_hash(),
                    'csrf_name' => csrf_token(),
                ])->setStatusCode(400);
            }
            return redirect()->back()->withInput()->with('error', 'Datos incompletos');
        }

        $data = [
            'name'       => $name,
            'order'      => $order,
            'cost'       => $cost,
            'cost_deriv' => $costDeriv,
            'compleja'   => $compleja,
            'mostrar_valores' => $mostrarValores ? 1 : 0,
            'anacategoria_id' => $anacategoriaId,
            'tipo_muestra_id' => (int) ($this->request->getPost('tipo_muestra_id') ?? 0),
            'metodo_id'       => (int) ($this->request->getPost('metodo_id') ?? 0),
        ];
        $this->labotestModel->saveSubCategory($data, $prianacategoriaId);
        \App\Models\AuditoriaModel::log('labotests', 'actualizar_analisis', (string)$prianacategoriaId, \App\Models\AuditoriaModel::detail(['nombre' => $name, 'costo' => $cost]));

        if ($this->request->isAJAX()) {
            return $this->response->setJSON([
                'success' => true,
                'message' => 'Análisis actualizado correctamente',
                'csrf_token' => csrf_hash(),
                'csrf_name' => csrf_token(),
            ]);
        }

        return redirect()->to("labotests/detail/{$prianacategoriaId}")->with('success', 'Análisis actualizado correctamente');
    }

    /**
     * Guardar sub-clase (secanacategoria) - prueba compuesta
     */
    public function savesecitem()
    {
        $prianacategoriaId = (int) ($this->request->getPost('prianacategoria_id') ?? 0);
        $id = (int) ($this->request->getPost('secanacategoria_id') ?? 0);
        $nombre = trim((string) ($this->request->getPost('nombre') ?? ''));
        $esSeparador = (int) ($this->request->getPost('es_separador') ?? 0) === 1;
        $pacienteId = (int) ($this->request->getPost('paciente_id') ?? 0);
        $sexo = trim((string) ($this->request->getPost('sexo') ?? ''));
        if ($prianacategoriaId < 1) {
            return redirect()->back()->with('error', 'Datos incompletos');
        }
        if ($nombre === '') {
            return redirect()->back()->with('error', 'El nombre de la sub-clase es obligatorio');
        }
        if (! $esSeparador) {
            if ($pacienteId < 1) {
                return redirect()->back()->with('error', 'La población es obligatoria');
            }
            if ($sexo === '' || ! in_array($sexo, ['ambos', 'masculino', 'femenino'], true)) {
                return redirect()->back()->with('error', 'El sexo es obligatorio');
            }
        } else {
            if ($pacienteId < 1) {
                $pacienteId = 3;
            }
            if ($sexo === '' || ! in_array($sexo, ['ambos', 'masculino', 'femenino'], true)) {
                $sexo = 'ambos';
            }
        }
        $formulasId        = $esSeparador ? 1 : (int) ($this->request->getPost('formulas_id') ?? 1);
        $formulaExpresion  = $esSeparador ? '' : trim($this->request->getPost('formula_expresion') ?? '');
        $data = [
            'prianacategoria_id' => $prianacategoriaId,
            'nombre'             => $nombre,
            'paciente_id'        => $pacienteId,
            'sexo'               => $sexo,
            'valor_min'          => $this->request->getPost('valor_min') ?? '',
            'valor_max'          => $this->request->getPost('valor_max') ?? '',
            'critico_min'        => $this->request->getPost('critico_min') ?? '',
            'critico_max'        => $this->request->getPost('critico_max') ?? '',
            'umedida'            => $this->request->getPost('umedida') ?? '',
            'mostrar_medida'     => (int) ($this->request->getPost('mostrar_medida') ?? 0) === 1 ? 1 : 0,
            'formulas_id'        => $formulasId,
            'formula_expresion'  => $formulaExpresion,
            'opcion_id'          => $esSeparador ? 3 : (int) ($this->request->getPost('opcion_id') ?? 3),
            'texto_fijo'         => trim($this->request->getPost('texto_fijo') ?? ''),
            'es_separador'       => $esSeparador ? 1 : 0,
        ];
        $this->labotestModel->saveSecItem($data, $id > 0 ? $id : null);
        \App\Models\AuditoriaModel::log('labotests', $id > 0 ? 'actualizar_subclase' : 'crear_subclase', (string)$prianacategoriaId, \App\Models\AuditoriaModel::detail(['nombre' => $nombre, 'secanacategoria_id' => $id ?: 'nuevo']));
        // Si es fórmula calculada (formulas_id > 1), actualizar la expresión en la tabla formulas
        // para que todas las sub-clases que usan esta fórmula (VCM, Formula Eritrocitos, etc.) vean el mismo cambio
        if ($formulasId > 1 && $formulaExpresion !== '') {
            $this->labotestModel->updateFormulaExpresion($formulasId, $formulaExpresion);
        }
        return redirect()->to("labotests/detail/{$prianacategoriaId}")->with('success', 'Sub-clase guardada correctamente');
    }

    /**
     * Guardar matriz de cultivo (encabezado, cuerpo, pie).
     */
    public function saveCultivoMatriz()
    {
        $prianacategoriaId = (int) ($this->request->getPost('prianacategoria_id') ?? 0);
        $subInfo = $this->labotestModel->getSubInfo($prianacategoriaId);
        $isAjax = $this->request->isAJAX();

        $respond = static function (bool $success, string $message, int $status = 200) use ($isAjax, $prianacategoriaId) {
            if ($isAjax) {
                return service('response')->setJSON([
                    'success'    => $success,
                    'message'    => $message,
                    'csrf_token' => csrf_hash(),
                    'csrf_name'  => csrf_token(),
                    'reload'     => $success,
                ])->setStatusCode($status);
            }

            return redirect()->to("labotests/detail/{$prianacategoriaId}")
                ->with($success ? 'success' : 'error', $message);
        };

        if ($prianacategoriaId < 1 || (int) ($subInfo->compleja ?? 0) !== LabotestModel::COMPLEJA_CULTIVO) {
            return $respond(false, 'La prueba no es de tipo cultivo', 400);
        }

        $json = $this->request->getPost('cultivo_matriz_json');
        if (! is_string($json) || trim($json) === '') {
            $json = (string) ($_POST['cultivo_matriz_json'] ?? '');
        }
        if (trim($json) === '') {
            $rawBody = (string) $this->request->getBody();
            if ($rawBody !== '' && str_contains($rawBody, 'cultivo_matriz_json=')) {
                parse_str($rawBody, $parsedBody);
                $json = (string) ($parsedBody['cultivo_matriz_json'] ?? '');
            }
        }

        $config = is_string($json) && trim($json) !== '' ? json_decode($json, true) : null;
        if (! is_array($config)) {
            $detail = json_last_error_msg();
            $msg = 'Configuración de matriz inválida';
            if (trim($json) === '') {
                $msg = 'No se recibió la configuración. Si la matriz es muy grande, aumente max_input_vars y post_max_size en PHP.';
            } elseif ($detail !== '' && $detail !== 'No error') {
                $msg .= ': ' . $detail;
            }
            return $respond(false, $msg, 400);
        }

        if (! $this->labotestModel->saveCultivoMatrizConfig($prianacategoriaId, $config)) {
            return $respond(false, 'No se pudo guardar la matriz. Verifique migraciones (cultivo_matriz_config).', 500);
        }

        \App\Models\AuditoriaModel::log(
            'labotests',
            'guardar_matriz_cultivo',
            (string) $prianacategoriaId,
            \App\Models\AuditoriaModel::detail(['nombre' => $subInfo->name ?? ''])
        );

        return $respond(true, 'Matriz de cultivo guardada correctamente');
    }

    /**
     * Guardar matriz personalizada (misma estructura que cultivo con extras por celda).
     */
    public function savePersonalizadoMatriz()
    {
        $prianacategoriaId = (int) ($this->request->getPost('prianacategoria_id') ?? 0);
        $subInfo = $this->labotestModel->getSubInfo($prianacategoriaId);
        $isAjax = $this->request->isAJAX();

        $respond = static function (bool $success, string $message, int $status = 200) use ($isAjax, $prianacategoriaId) {
            if ($isAjax) {
                return service('response')->setJSON([
                    'success'    => $success,
                    'message'    => $message,
                    'csrf_token' => csrf_hash(),
                    'csrf_name'  => csrf_token(),
                    'reload'     => $success,
                ])->setStatusCode($status);
            }

            return redirect()->to("labotests/detail/{$prianacategoriaId}")
                ->with($success ? 'success' : 'error', $message);
        };

        if ($prianacategoriaId < 1 || (int) ($subInfo->compleja ?? 0) !== LabotestModel::COMPLEJA_PERSONALIZADO) {
            return $respond(false, 'La prueba no es de tipo personalizado', 400);
        }

        $json = $this->request->getPost('cultivo_matriz_json');
        if (! is_string($json) || trim($json) === '') {
            $json = (string) ($_POST['cultivo_matriz_json'] ?? '');
        }
        if (trim($json) === '') {
            $rawBody = (string) $this->request->getBody();
            if ($rawBody !== '' && str_contains($rawBody, 'cultivo_matriz_json=')) {
                parse_str($rawBody, $parsedBody);
                $json = (string) ($parsedBody['cultivo_matriz_json'] ?? '');
            }
        }

        $config = is_string($json) && trim($json) !== '' ? json_decode($json, true) : null;
        if (! is_array($config)) {
            $detail = json_last_error_msg();
            $msg = 'Configuración de matriz inválida';
            if (trim($json) === '') {
                $msg = 'No se recibió la configuración. Si la matriz es muy grande, aumente max_input_vars y post_max_size en PHP.';
            } elseif ($detail !== '' && $detail !== 'No error') {
                $msg .= ': ' . $detail;
            }
            return $respond(false, $msg, 400);
        }

        if (! $this->labotestModel->savePersonalizadoMatrizConfig($prianacategoriaId, $config)) {
            return $respond(false, 'No se pudo guardar la matriz personalizada. Verifique migraciones (cultivo_matriz_config).', 500);
        }

        \App\Models\AuditoriaModel::log(
            'labotests',
            'guardar_matriz_personalizado',
            (string) $prianacategoriaId,
            \App\Models\AuditoriaModel::detail(['nombre' => $subInfo->name ?? ''])
        );

        return $respond(true, 'Matriz personalizada guardada correctamente');
    }

    /**
     * Actualizar orden de sub-clases (tabla de valores). Recibe prianacategoria_id y order/order[] (secanacategoria_id en orden).
     */
    public function orderSecItems(): ResponseInterface
    {
        $prianacategoriaId = (int) ($this->request->getPost('prianacategoria_id') ?? 0);
        $order = $this->request->getPost('order') ?? $this->request->getPost('order[]');
        if ($prianacategoriaId < 1) {
            return $this->response->setJSON(['success' => false, 'message' => 'ID de prueba inválido'])->setStatusCode(400);
        }
        if (! is_array($order)) {
            $order = is_string($order) ? json_decode($order, true) : [];
        }
        $order = array_values(array_filter(array_map('intval', (array) $order)));
        $this->labotestModel->updateSecItemsOrder($prianacategoriaId, $order);
        $json = ['success' => true, 'message' => 'Orden guardado'];
        if (function_exists('csrf_hash')) {
            $json['csrf_token'] = csrf_hash();
            $json['csrf_name'] = csrf_token();
        }
        return $this->response->setJSON($json);
    }

    /**
     * Reporte JSON de análisis con nombre duplicado y perfiles donde figura cada registro.
     */
    public function duplicateAnalysesReport(): ResponseInterface
    {
        $profileMap = model(PerfilExamenModel::class)->getAnalysisToProfilesMap();
        $duplicates = $this->labotestModel->getDuplicateAnalysesWithProfiles($profileMap);
        $totalEntries = 0;
        foreach ($duplicates as $group) {
            $totalEntries += (int) ($group['count'] ?? 0);
        }

        return $this->response->setJSON([
            'success'        => true,
            'total_groups'   => count($duplicates),
            'total_entries'  => $totalEntries,
            'duplicates'     => $duplicates,
        ]);
    }

    /**
     * Transforma en lote los nombres de grupos y análisis (mayúsculas, título, ortografía, etc.).
     */
    public function transformNames(): ResponseInterface
    {
        $mode = strtolower(trim((string) ($this->request->getPost('mode') ?? '')));
        if (! LabotestNameTransformService::isAllowedMode($mode)) {
            return $this->response->setJSON([
                'success' => false,
                'message' => 'Seleccione un formato válido',
                'csrf_token' => csrf_hash(),
                'csrf_name' => csrf_token(),
            ])->setStatusCode(400);
        }

        $result = $this->labotestModel->transformAllNames($mode);
        if ($result['success'] ?? false) {
            \App\Models\AuditoriaModel::log(
                'labotests',
                'transformar_nombres',
                '',
                \App\Models\AuditoriaModel::detail([
                    'modo' => $mode,
                    'grupos' => (int) ($result['categories_updated'] ?? 0),
                    'analisis' => (int) ($result['analyses_updated'] ?? 0),
                ])
            );
        }

        return $this->response->setJSON([
            'success' => (bool) ($result['success'] ?? false),
            'message' => (string) ($result['message'] ?? ''),
            'categories_updated' => (int) ($result['categories_updated'] ?? 0),
            'analyses_updated' => (int) ($result['analyses_updated'] ?? 0),
            'unchanged' => (int) ($result['unchanged'] ?? 0),
            'csrf_token' => csrf_hash(),
            'csrf_name' => csrf_token(),
        ])->setStatusCode(($result['success'] ?? false) ? 200 : 400);
    }

    /**
     * Reordena las categorías (grupos) con la lista completa enviada desde el modal.
     */
    public function reorderCategories(): ResponseInterface
    {
        $orderedIds = $this->request->getPost('ordered_ids');
        if (! is_array($orderedIds)) {
            $orderedIds = is_string($orderedIds) ? json_decode($orderedIds, true) : [];
        }

        if (! is_array($orderedIds) || $orderedIds === []) {
            return $this->response->setJSON([
                'success' => false,
                'message' => 'No se recibio el orden de los grupos',
                'csrf_token' => csrf_hash(),
                'csrf_name' => csrf_token(),
            ])->setStatusCode(400);
        }

        $saved = $this->labotestModel->updateAllCategoryOrder($orderedIds);
        if (! $saved) {
            return $this->response->setJSON([
                'success' => false,
                'message' => 'No se pudo guardar el orden de los grupos',
                'csrf_token' => csrf_hash(),
                'csrf_name' => csrf_token(),
            ])->setStatusCode(400);
        }

        \App\Models\AuditoriaModel::log(
            'labotests',
            'reordenar_grupos',
            '',
            \App\Models\AuditoriaModel::detail(['grupos' => count($orderedIds)])
        );

        return $this->response->setJSON([
            'success' => true,
            'message' => 'Orden de grupos guardado',
            'csrf_token' => csrf_hash(),
            'csrf_name' => csrf_token(),
        ]);
    }

    /**
     * Mueve análisis entre categorías y reordena los hijos visibles de cada padre.
     */
    public function reorderAnalysis(): ResponseInterface
    {
        $payload = $this->request->getPost('groups');
        if (! is_array($payload)) {
            $payload = is_string($payload) ? json_decode($payload, true) : [];
        }

        if (! is_array($payload) || $payload === []) {
            return $this->response->setJSON([
                'success' => false,
                'message' => 'No se recibio el orden de los analisis',
                'csrf_token' => csrf_hash(),
                'csrf_name' => csrf_token(),
            ])->setStatusCode(400);
        }

        $saved = $this->labotestModel->updateAnalysisPlacement($payload);
        if (! $saved) {
            return $this->response->setJSON([
                'success' => false,
                'message' => 'No se pudo guardar el nuevo orden',
                'csrf_token' => csrf_hash(),
                'csrf_name' => csrf_token(),
            ])->setStatusCode(400);
        }

        \App\Models\AuditoriaModel::log(
            'labotests',
            'reordenar_analisis',
            '',
            \App\Models\AuditoriaModel::detail(['grupos' => count($payload)])
        );

        return $this->response->setJSON([
            'success' => true,
            'message' => 'Orden guardado',
            'csrf_token' => csrf_hash(),
            'csrf_name' => csrf_token(),
        ]);
    }

    /**
     * Ordena alfabéticamente los análisis de un grupo.
     */
    public function sortAnalysisAlphabetic(): ResponseInterface
    {
        $categoryId = (int) ($this->request->getPost('category_id') ?? 0);
        if ($categoryId < 1) {
            return $this->response->setJSON([
                'success' => false,
                'message' => 'Grupo inválido',
                'csrf_token' => csrf_hash(),
                'csrf_name' => csrf_token(),
            ])->setStatusCode(400);
        }

        $saved = $this->labotestModel->sortAnalysesAlphabeticallyInCategory($categoryId);
        if (! $saved) {
            return $this->response->setJSON([
                'success' => false,
                'message' => 'No se pudo ordenar los análisis',
                'csrf_token' => csrf_hash(),
                'csrf_name' => csrf_token(),
            ])->setStatusCode(400);
        }

        \App\Models\AuditoriaModel::log(
            'labotests',
            'ordenar_analisis_alfabetico',
            (string) $categoryId,
            \App\Models\AuditoriaModel::detail(['grupo_id' => $categoryId])
        );

        return $this->response->setJSON([
            'success' => true,
            'message' => 'Análisis ordenados alfabéticamente',
            'csrf_token' => csrf_hash(),
            'csrf_name' => csrf_token(),
        ]);
    }

    /**
     * Duplica una prueba completa hacia otra categoría padre.
     */
    public function duplicateAnalysisToParent()
    {
        $sourceId = (int) ($this->request->getPost('prianacategoria_id') ?? 0);
        $targetParentId = (int) ($this->request->getPost('target_anacategoria_id') ?? 0);

        if ($sourceId < 1 || $targetParentId < 1) {
            return redirect()->to('labotests')->with('error', 'Debe seleccionar la prueba y el padre destino');
        }

        $source = $this->labotestModel->getSubInfo($sourceId, null);
        if (! $source || ! ($source->prianacategoria_id ?? null)) {
            return redirect()->to('labotests')->with('error', 'Prueba no encontrada');
        }

        if ((int) ($source->anacategoria_id ?? 0) === $targetParentId) {
            return redirect()->to('labotests')->with('error', 'Seleccione un padre diferente para duplicar la prueba');
        }

        $newId = $this->labotestModel->duplicateAnalysisToParent($sourceId, $targetParentId);
        if (! $newId) {
            return redirect()->to('labotests')->with('error', 'No se pudo duplicar la prueba');
        }

        \App\Models\AuditoriaModel::log(
            'labotests',
            'duplicar_analisis',
            (string) $newId,
            \App\Models\AuditoriaModel::detail([
                'origen' => $sourceId,
                'destino_padre' => $targetParentId,
                'nombre' => (string) ($source->name ?? ''),
            ])
        );

        return redirect()->to('labotests')->with('success', 'Prueba duplicada correctamente');
    }

    /**
     * Guardar o actualizar fórmula personalizada (nombre + expresión)
     * Si formulas_id > 0, actualiza la fórmula existente; si no, crea nueva.
     */
    public function saveFormula(): ResponseInterface
    {
        $nombre = trim($this->request->getPost('nombre_formula') ?? '');
        $expresion = trim($this->request->getPost('formula_expresion') ?? '');
        $formulasId = (int) ($this->request->getPost('formulas_id') ?? 0);
        if ($nombre === '' || $expresion === '') {
            return $this->response->setJSON(['success' => false, 'message' => 'Nombre y expresión son requeridos'])->setStatusCode(400);
        }
        $id = $this->labotestModel->saveFormula(['nombre' => $nombre, 'formula_expresion' => $expresion, 'formulas_id' => $formulasId]);
        return $this->response->setJSON([
            'success' => $id > 0,
            'message' => $id > 0 ? ($formulasId > 0 ? 'Fórmula actualizada' : 'Fórmula guardada') : 'Error al guardar',
            'formulas_id' => $id,
            'csrf_token' => csrf_hash(),
            'csrf_name' => csrf_token(),
        ]);
    }

    /**
     * Elimina una fórmula solo si no está en uso.
     */
    public function deleteFormula($id): ResponseInterface
    {
        $id = (int) $id;
        $result = $this->labotestModel->deleteFormulaIfUnused($id);
        if ($result['success']) {
            $result['csrf_token'] = csrf_hash();
            $result['csrf_name'] = csrf_token();
        }
        return $this->response->setJSON($result)->setStatusCode($result['success'] ? 200 : 400);
    }

    /**
     * Eliminar categoría (padre) y todos sus hijos con configuraciones
     */
    public function deletecategory($id)
    {
        $id = (int) $id;
        $cat = $this->labotestModel->getCategoryInfo($id);
        if (!$cat || !$cat->anacategoria_id) {
            return redirect()->to('labotests')->with('error', 'Categoría no encontrada');
        }
        $catName = $cat->name ?? '';
        $this->labotestModel->deleteCategoryWithAll($id);
        \App\Models\AuditoriaModel::log('labotests', 'eliminar_categoria', (string)$id, \App\Models\AuditoriaModel::detail(['nombre' => $catName]));
        return redirect()->to('labotests')->with('success', 'Categoría y todos sus análisis eliminados correctamente');
    }

    /**
     * Previsualiza el impacto de eliminar un análisis en órdenes registradas.
     */
    public function prianacategoriaDeletePreview($id): ResponseInterface
    {
        $id = (int) $id;
        $impact = (new PrianacategoriaReferenceService())->getDeleteImpact($id);
        if (! ($impact['success'] ?? false)) {
            return $this->response->setJSON([
                'success' => false,
                'message' => (string) ($impact['message'] ?? 'Análisis no encontrado'),
            ])->setStatusCode(404);
        }

        return $this->response->setJSON($impact);
    }

    /**
     * Eliminar análisis (hijo) y todas sus configuraciones.
     * Si existe un duplicado, migra referencias en /registers/ hacia la prueba que permanece.
     */
    public function deleteprianacategoria($id)
    {
        $id = (int) $id;
        $sub = $this->labotestModel->getSubInfo($id, null);
        if (! $sub || ! $sub->prianacategoria_id) {
            return redirect()->to('labotests')->with('error', 'Análisis no encontrado');
        }

        $subName = (string) ($sub->name ?? '');
        $referenceService = new PrianacategoriaReferenceService();
        $impact = $referenceService->getDeleteImpact($id);
        $migrateTo = (int) ($this->request->getPost('migrate_to_id') ?? $this->request->getGet('migrate_to') ?? 0);

        if ($migrateTo < 1 && ($impact['can_migrate'] ?? false)) {
            $migrateTo = (int) ($impact['migrate_to_id'] ?? 0);
        }

        $migrationStats = null;
        if ($migrateTo > 0) {
            if (! $referenceService->isValidMigrationTarget($id, $migrateTo)) {
                return redirect()->to('labotests')->with('error', 'No se pudo migrar: el análisis destino no es un duplicado válido');
            }
            $migrationStats = $referenceService->migrateReferences($id, $migrateTo);
            $this->labotestModel->deletePrianacategoriaWithAll($id);
        } else {
            $referenceService->removeFromPerfiles($id);
            $this->labotestModel->retirePrianacategoria($id);
        }

        $auditDetail = ['nombre' => $subName];
        if ($migrateTo > 0) {
            $auditDetail['modo'] = 'migrar_duplicado';
            $auditDetail['migrado_a'] = $migrateTo;
            $auditDetail['ordenes_actualizadas'] = (int) ($migrationStats['registros_updated'] ?? 0);
            $auditDetail['valores_actualizados'] = (int) ($migrationStats['regvalues_updated'] ?? 0);
        } else {
            $auditDetail['modo'] = 'retirar_catalogo';
            if ($impact['has_registered_values'] ?? false) {
                $auditDetail['ordenes_historicas'] = (int) ($impact['registros_count'] ?? 0);
                $auditDetail['conserva_historico'] = true;
            }
        }

        \App\Models\AuditoriaModel::log('labotests', 'eliminar_analisis', (string) $id, \App\Models\AuditoriaModel::detail($auditDetail));

        if ($migrateTo > 0) {
            $targetName = (string) ($impact['migrate_to_name'] ?? ('#' . $migrateTo));
            $orders = (int) ($migrationStats['registros_updated'] ?? 0);
            $values = (int) ($migrationStats['regvalues_updated'] ?? 0);
            $message = 'Análisis eliminado. Se actualizaron ' . $orders . ' orden(es) y ' . $values . ' valor(es) hacia "' . $targetName . '" (#' . $migrateTo . ').';

            return redirect()->to('labotests')->with('success', $message);
        }

        if ($impact['has_registered_values'] ?? false) {
            $orders = (int) ($impact['registros_count'] ?? 0);
            $message = 'Análisis retirado del catálogo. Se conservan ' . $orders . ' orden(es) histórica(s) con sus resultados (solo lectura).';

            return redirect()->to('labotests')->with('success', $message);
        }

        return redirect()->to('labotests')->with('success', 'Análisis retirado del catálogo correctamente');
    }

    /**
     * Índice de manuales: lista todos los análisis con sus manuales por proveedor
     * URL: labotests/manuales
     */
    public function manualesIndex()
    {
        $search = $this->request->getGet('q');
        $search = is_string($search) ? trim($search) : null;
        if ($search === '') {
            $search = null;
        }

        $tests = $this->labotestModel->getAllTestsWithManualCount($search);

        // Agrupar por categoría
        $grouped = [];
        foreach ($tests as $t) {
            $catId = (int) ($t['anacategoria_id'] ?? 0);
            if (!isset($grouped[$catId])) {
                $grouped[$catId] = [
                    'id'    => $catId,
                    'name'  => $t['cat_name'] ?? '',
                    'items' => [],
                ];
            }
            $grouped[$catId]['items'][] = $t;
        }

        return view('labotests/manuales_index', [
            'categories'       => array_values($grouped),
            'search'           => $search,
            'allowed_modules'  => $this->allowed_modules,
            'user_info'        => $this->user_info,
            'current_module'   => 'labotests',
        ]);
    }

    /**
     * Ver manuales/notas de un análisis
     */
    public function manuales($id)
    {
        $id = (int) $id;
        $sub = $this->labotestModel->getSubInfo($id, null);
        if (!$sub || !$sub->prianacategoria_id) {
            return redirect()->to('labotests')->with('error', 'Análisis no encontrado');
        }
        $manuals = $this->labotestModel->getManualsByPrianacategoria($id);
        return view('labotests/manuales', [
            'labotests_info'     => $sub,
            'prianacategoria_id' => $id,
            'manuals'            => $manuals,
            'allowed_modules'    => $this->allowed_modules,
            'user_info'          => $this->user_info,
            'current_module'     => 'labotests',
        ]);
    }

    /**
     * Guardar o actualizar manual
     */
    public function saveManual()
    {
        $prianacategoriaId = (int) ($this->request->getPost('prianacategoria_id') ?? 0);
        $manualsId = (int) ($this->request->getPost('manuals_id') ?? 0);
        $tittle = trim($this->request->getPost('tittle') ?? '');
        $manual = $this->request->getPost('manual') ?? '';
        if ($prianacategoriaId < 1 || $tittle === '') {
            return redirect()->back()->with('error', 'Datos incompletos');
        }
        $id = $this->labotestModel->saveManual([
            'manuals_id'         => $manualsId,
            'prianacategoria_id' => $prianacategoriaId,
            'tittle'             => $tittle,
            'manual'             => $manual,
        ]);
        $msg = $manualsId > 0 ? 'Manual actualizado' : 'Manual agregado';
        return redirect()->to("labotests/manuales/{$prianacategoriaId}")->with('success', $id > 0 ? $msg : 'Error al guardar');
    }

    /**
     * Eliminar manual
     */
    public function deleteManual($id)
    {
        $id = (int) $id;
        $m = $this->labotestModel->getManualById($id);
        if (!$m) {
            return redirect()->to('labotests')->with('error', 'Manual no encontrado');
        }
        $prianacategoriaId = (int) ($m['prianacategoria_id'] ?? 0);
        $this->labotestModel->deleteManual($id);
        return redirect()->to("labotests/manuales/{$prianacategoriaId}")->with('success', 'Manual eliminado');
    }

    /**
     * Editar recomendaciones previas al examen (toma de muestra)
     */
    public function recomendacionesPrevias($id)
    {
        $id = (int) $id;
        $sub = $this->labotestModel->getSubInfo($id, null);
        if (!$sub || !$sub->prianacategoria_id) {
            return redirect()->to('labotests')->with('error', 'Análisis no encontrado');
        }
        return view('labotests/recomendaciones_previas', [
            'labotests_info'         => $sub,
            'prianacategoria_id'     => $id,
            'recomendaciones_previas' => $this->labotestModel->getRecomendacionesPrevias($id),
            'allowed_modules'        => $this->allowed_modules,
            'user_info'              => $this->user_info,
            'current_module'         => 'labotests',
        ]);
    }

    /**
     * Guardar recomendaciones previas al examen
     */
    public function saveRecomendacionesPrevias()
    {
        $prianacategoriaId = (int) ($this->request->getPost('prianacategoria_id') ?? 0);
        $contenido = $this->request->getPost('recomendaciones_previas') ?? '';
        if ($prianacategoriaId < 1) {
            return redirect()->back()->with('error', 'Datos incompletos');
        }
        $sub = $this->labotestModel->getSubInfo($prianacategoriaId, null);
        if (!$sub || !$sub->prianacategoria_id) {
            return redirect()->to('labotests')->with('error', 'Análisis no encontrado');
        }
        $ok = $this->labotestModel->saveRecomendacionesPrevias($prianacategoriaId, $contenido);
        return redirect()->to("labotests/recomendacionesprevias/{$prianacategoriaId}")
            ->with($ok ? 'success' : 'error', $ok ? 'Recomendaciones guardadas' : 'Error al guardar');
    }

    /**
     * Duplicar sub-clase con los mismos datos
     */
    public function duplicatesecitem($id)
    {
        $id = (int) $id;
        $sec = $this->labotestModel->getSecItemInfo($id);
        if (!$sec) {
            if ($this->request->isAJAX()) {
                return $this->response->setJSON(['success' => false, 'message' => 'Sub-clase no encontrada'])->setStatusCode(404);
            }
            return redirect()->back()->with('error', 'Sub-clase no encontrada');
        }
        $prianacategoriaId = (int) ($sec->prianacategoria_id ?? 0);
        $copies = (int) ($this->request->getPost('copies') ?? $this->request->getGet('copies') ?? 1);
        $copies = max(1, min(100, $copies));
        $nameModeRaw = $this->request->getPost('name_mode');
        if ($nameModeRaw === null || $nameModeRaw === '') {
            $nameModeRaw = $this->request->getPost('duplicar_sec_name_mode');
        }
        if ($nameModeRaw === null || $nameModeRaw === '') {
            $nameModeRaw = $this->request->getGet('name_mode');
        }
        if ($nameModeRaw === null || $nameModeRaw === '') {
            $nameModeRaw = $this->request->getGet('duplicar_sec_name_mode');
        }
        if ($nameModeRaw === null || $nameModeRaw === '') {
            $nameModeRaw = $this->request->getHeaderLine('X-Name-Mode');
        }
        $nameMode = strtolower(trim((string) ($nameModeRaw ?? 'copia_numerada')));
        if (! in_array($nameMode, ['same', 'copia_numerada'], true)) {
            $nameMode = 'copia_numerada';
        }
        $result = $this->labotestModel->duplicateSecItemMany($id, $copies, $nameMode);
        $inserted = (int) ($result['inserted'] ?? 0);

        if ($inserted < 1) {
            if ($this->request->isAJAX()) {
                return $this->response->setJSON(['success' => false, 'message' => 'No se pudo duplicar la sub-clase'])->setStatusCode(400);
            }
            return redirect()->to("labotests/detail/{$prianacategoriaId}")->with('error', 'No se pudo duplicar la sub-clase');
        }

        $message = $inserted === 1
            ? 'Sub-clase duplicada correctamente'
            : ('Sub-clase duplicada ' . $inserted . ' veces correctamente');

        if ($this->request->isAJAX()) {
            $json = ['success' => true, 'message' => $message, 'inserted' => $inserted];
            if (function_exists('csrf_hash')) {
                $json['csrf_token'] = csrf_hash();
                $json['csrf_name'] = csrf_token();
            }
            return $this->response->setJSON($json);
        }

        return redirect()->to("labotests/detail/{$prianacategoriaId}")->with('success', $message);
    }

    /**
     * Exporta la configuración de detalle (compuesta/no compuesta) en JSON.
     */
    public function exportdetailconfig($id): ResponseInterface
    {
        $id = (int) $id;
        $payload = $this->labotestModel->buildDetailConfigExport($id);
        if (! $payload) {
            return redirect()->to('labotests')->with('error', 'Prueba no encontrada');
        }

        $json = json_encode($payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_INVALID_UTF8_SUBSTITUTE);
        if ($json === false) {
            return redirect()->to("labotests/detail/{$id}")->with('error', 'No se pudo generar el archivo de exportación');
        }

        $tipo = \App\Models\LabotestModel::tipoAnalisisSlug((int) ($payload['compleja'] ?? 0));
        $filename = 'labotest_' . $id . '_' . $tipo . '_' . date('Ymd_His') . '.json';

        return $this->response
            ->download($filename, $json)
            ->setContentType('application/json');
    }

    /**
     * Importa configuración de detalle desde JSON y reemplaza filas actuales.
     */
    public function importdetailconfig($id)
    {
        $id = (int) $id;
        if ($id < 1) {
            return redirect()->to('labotests')->with('error', 'Prueba inválida');
        }

        $file = $this->request->getFile('config_file');
        if (! $file || ! $file->isValid()) {
            return redirect()->to("labotests/detail/{$id}")->with('error', 'Debe seleccionar un archivo JSON válido');
        }

        $ext = strtolower((string) $file->getExtension());
        if ($ext !== 'json') {
            return redirect()->to("labotests/detail/{$id}")->with('error', 'El archivo debe ser .json');
        }

        $tmpPath = $file->getTempName();
        $content = is_string($tmpPath) && $tmpPath !== '' ? @file_get_contents($tmpPath) : false;
        if (! is_string($content) || trim($content) === '') {
            return redirect()->to("labotests/detail/{$id}")->with('error', 'El archivo está vacío');
        }

        $payload = json_decode($content, true);
        if (! is_array($payload)) {
            return redirect()->to("labotests/detail/{$id}")->with('error', 'JSON inválido');
        }

        $result = $this->labotestModel->importDetailConfig($id, $payload);
        if (! ($result['success'] ?? false)) {
            return redirect()->to("labotests/detail/{$id}")->with('error', (string) ($result['message'] ?? 'No se pudo importar'));
        }

        $imported = (int) ($result['imported'] ?? 0);
        \App\Models\AuditoriaModel::log('labotests', 'importar_configuracion_detalle', (string) $id, \App\Models\AuditoriaModel::detail([
            'filas_importadas' => $imported,
        ]));

        $successMsg = (string) ($result['message'] ?? 'Configuración importada correctamente');
        if ($successMsg === 'Configuración importada correctamente' && $imported > 0) {
            $subInfo = $this->labotestModel->getSubInfo($id);
            $tipo = (int) ($subInfo->compleja ?? 0);
            if (\App\Models\LabotestModel::esMatrizConfigurable($tipo)) {
                $successMsg = $tipo === \App\Models\LabotestModel::COMPLEJA_PERSONALIZADO
                    ? 'Matriz personalizada importada correctamente'
                    : 'Matriz de cultivo importada correctamente';
            } elseif ($imported > 0) {
                $successMsg = 'Configuración importada correctamente (' . $imported . ' filas)';
            }
        }

        return redirect()->to("labotests/detail/{$id}")
            ->with('success', $successMsg);
    }

    /**
     * Eliminar sub-clase
     */
    public function deletesecitem($id)
    {
        $id = (int) $id;
        $sec = $this->labotestModel->getSecItemInfo($id);
        if (!$sec) {
            return redirect()->back()->with('error', 'Sub-clase no encontrada');
        }
        $prianacategoriaId = (int) $sec->prianacategoria_id;
        $secNombre = $sec->nombre ?? '';
        $this->labotestModel->deleteSecItem($id);
        \App\Models\AuditoriaModel::log('labotests', 'eliminar_subclase', (string)$prianacategoriaId, \App\Models\AuditoriaModel::detail(['secanacategoria_id' => $id, 'nombre' => $secNombre]));
        return redirect()->to("labotests/detail/{$prianacategoriaId}")->with('success', 'Sub-clase eliminada');
    }

    /**
     * Eliminar sub-clases en lote
     */
    public function deletesecitemsbulk(): ResponseInterface
    {
        $prianacategoriaId = (int) ($this->request->getPost('prianacategoria_id') ?? 0);
        $ids = $this->request->getPost('secanacategoria_ids');
        $ids = is_array($ids) ? $ids : [];
        $ids = array_values(array_unique(array_filter(array_map('intval', $ids), static fn(int $v): bool => $v > 0)));

        if ($prianacategoriaId < 1 || $ids === []) {
            return $this->response->setJSON([
                'success' => false,
                'message' => 'Debe seleccionar al menos una sub-clase',
            ])->setStatusCode(400);
        }

        $deletedCount = $this->labotestModel->deleteSecItemsBulk($prianacategoriaId, $ids);
        if ($deletedCount > 0) {
            \App\Models\AuditoriaModel::log(
                'labotests',
                'eliminar_subclases_masivo',
                (string) $prianacategoriaId,
                \App\Models\AuditoriaModel::detail([
                    'total' => $deletedCount,
                    'ids' => $ids,
                ])
            );
        }

        $json = [
            'success' => $deletedCount > 0,
            'message' => $deletedCount > 0
                ? ('Se eliminaron ' . $deletedCount . ' sub-clases')
                : 'No se eliminaron sub-clases',
            'deleted' => $deletedCount,
        ];
        if (function_exists('csrf_hash')) {
            $json['csrf_token'] = csrf_hash();
            $json['csrf_name'] = csrf_token();
        }
        return $this->response->setJSON($json)->setStatusCode($deletedCount > 0 ? 200 : 400);
    }

    /**
     * Guardar resultado (priresultados) - prueba no compuesta
     */
    public function savepriresultado()
    {
        $prianacategoriaId = (int) ($this->request->getPost('prianacategoria_id') ?? 0);
        $id = (int) ($this->request->getPost('priresultados_id') ?? 0);
        if ($prianacategoriaId < 1) {
            return redirect()->back()->with('error', 'Datos incompletos');
        }
        $data = [
            'prianacategoria_id' => $prianacategoriaId,
            'id_poblacion'      => (int) ($this->request->getPost('id_poblacion') ?? 15),
            'sexo'              => $this->request->getPost('sexo') ?? 'ambos',
            'valor_min'         => $this->request->getPost('valor_min') ?? '',
            'valor_max'         => $this->request->getPost('valor_max') ?? '',
            'critico_min'       => $this->request->getPost('critico_min') ?? '',
            'critico_max'       => $this->request->getPost('critico_max') ?? '',
            'umedida'           => $this->request->getPost('umedida') ?? '',
            'mostrar_medida'    => (int) ($this->request->getPost('mostrar_medida') ?? 0) === 1 ? 1 : 0,
            'formulas_id'       => (int) ($this->request->getPost('formulas_id') ?? 1),
            'opcion_id'         => (int) ($this->request->getPost('opcion_id') ?? 3),
            'texto_fijo'        => trim($this->request->getPost('texto_fijo') ?? ''),
        ];
        $this->labotestModel->savePriResultado($data, $id > 0 ? $id : null);
        return redirect()->to("labotests/detail/{$prianacategoriaId}")->with('success', 'Valores de referencia guardados correctamente');
    }

    /**
     * Eliminar priresultado
     */
    public function deletepriresultado($id)
    {
        $id = (int) $id;
        $pr = $this->labotestModel->getPriResultadoInfo($id);
        if (!$pr) {
            return redirect()->back()->with('error', 'Registro no encontrado');
        }
        $prianacategoriaId = (int) $pr->prianacategoria_id;
        $this->labotestModel->deletePriResultado($id);
        return redirect()->to("labotests/detail/{$prianacategoriaId}")->with('success', 'Valores eliminados');
    }

    /**
     * Gestión de perfiles de exámenes
     */
    public function perfiles()
    {
        $perfilModel = model(PerfilExamenModel::class);
        $perfiles = $perfilModel->getAll();
        $categories = $this->labotestModel->getGroupedByCategory();

        return view('labotests/perfiles', [
            'perfiles'         => $perfiles,
            'categories'       => $categories,
            'allowed_modules'  => $this->allowed_modules,
            'user_info'        => $this->user_info,
            'current_module'   => 'labotests',
        ]);
    }

    public function saveperfil()
    {
        $nombre = trim($this->request->getPost('nombre') ?? '');
        $pruebas = $this->request->getPost('pruebas') ?? [];
        $id = (int) ($this->request->getPost('perfil_id') ?? 0);

        if ($nombre === '') {
            return redirect()->back()->with('error', 'El nombre es obligatorio');
        }
        $pruebas = is_array($pruebas) ? $pruebas : [$pruebas];
        $pruebas = array_filter(array_map('intval', $pruebas));

        $perfilModel = model(PerfilExamenModel::class);
        $perfilModel->savePerfil(['nombre' => $nombre, 'pruebas' => $pruebas], $id > 0 ? $id : null);
        return redirect()->to('labotests/perfiles')->with('success', 'Perfil guardado');
    }

    public function deleteperfil($id)
    {
        $id = (int) $id;
        model(PerfilExamenModel::class)->deletePerfil($id);
        return redirect()->to('labotests/perfiles')->with('success', 'Perfil eliminado');
    }

    /**
     * Administrar tipos de resultado (opciones para Tipo resultado en sub-clases)
     */
    public function opciones()
    {
        $this->opcionModel->ensureSystemOpciones();
        $opciones = $this->opcionModel->findAll();
        foreach ($opciones as &$o) {
            $tabla = trim($o['tabla'] ?? '');
            $o['valores'] = ($tabla === 'opcion_valores')
                ? $this->opcionModel->getValores((int) $o['opciones_id'])
                : ($this->opcionModel->getOpcionConValores((int) $o['opciones_id'])['valores'] ?? []);
            $o['usa_valores_genericos'] = ($tabla === 'opcion_valores');
            $o['usa_tabla_sistema']     = in_array($tabla, ['opcpositivo', 'opcreactivo'], true);
            $o['tabla_sistema']         = $o['usa_tabla_sistema'] ? $tabla : '';
            $o['editable'] = $this->opcionModel->isEditable((int) $o['opciones_id']);
        }

        return view('labotests/opciones', [
            'opciones'        => $opciones,
            'allowed_modules'  => $this->allowed_modules,
            'user_info'       => $this->user_info,
            'current_module'  => 'labotests',
            'base_url'        => 'labotests',
        ]);
    }

    public function saveOpcion(): ResponseInterface
    {
        $nombre = trim($this->request->getPost('opciones') ?? '');
        if ($nombre === '') {
            return redirect()->to('labotests/opciones')->with('error', 'El nombre es obligatorio.');
        }
        $id = (int) ($this->request->getPost('opciones_id') ?? 0);
        $tabla = 'opcion_valores';
        if ($id > 0) {
            $row = $this->opcionModel->find($id);
            $tabla = $row ? trim($row['tabla'] ?? 'opcion_valores') : 'opcion_valores';
        }
        $this->opcionModel->saveOpcion([
            'opciones_id' => $id,
            'opciones'    => $nombre,
            'tabla'       => $tabla,
        ]);
        return redirect()->to('labotests/opciones')->with('success', 'Tipo de resultado guardado.');
    }

    public function deleteOpcion($id): ResponseInterface
    {
        $id = (int) $id;
        $result = $this->opcionModel->deleteOpcionIfUnused($id);
        return redirect()->to('labotests/opciones')->with($result['success'] ? 'success' : 'error', $result['message']);
    }

    public function saveOpcionValor(): ResponseInterface
    {
        $opcionesId = (int) ($this->request->getPost('opciones_id') ?? 0);
        $valor = trim($this->request->getPost('valor') ?? '');
        if ($opcionesId < 1 || $valor === '') {
            return redirect()->to('labotests/opciones')->with('error', 'Datos incompletos.');
        }
        $row = $this->opcionModel->find($opcionesId);
        if (!$row || trim($row['tabla'] ?? '') !== 'opcion_valores') {
            return redirect()->to('labotests/opciones')->with('error', 'Solo se pueden editar valores en opciones personalizadas.');
        }
        $this->opcionModel->saveValor([
            'opcion_valor_id' => (int) ($this->request->getPost('opcion_valor_id') ?? 0),
            'opciones_id'     => $opcionesId,
            'valor'           => $valor,
            'orden'           => (int) ($this->request->getPost('orden') ?? 0),
        ]);
        return redirect()->to('labotests/opciones#' . $opcionesId)->with('success', 'Valor guardado.');
    }

    public function deleteOpcionValor($id): ResponseInterface
    {
        $id = (int) $id;
        $this->opcionModel->deleteValor($id);
        return redirect()->back()->with('success', 'Valor eliminado.');
    }

    public function saveValorTabla(): ResponseInterface
    {
        $tabla   = trim($this->request->getPost('tabla') ?? '');
        $valorId = (int) ($this->request->getPost('valor_id') ?? 0);
        $valor   = trim($this->request->getPost('valor') ?? '');
        if (!in_array($tabla, ['opcpositivo', 'opcreactivo'], true) || $valor === '') {
            return redirect()->to('labotests/opciones')->with('error', 'Datos incompletos.');
        }
        $this->opcionModel->saveValorTabla($tabla, $valorId, $valor);
        return redirect()->to('labotests/opciones')->with('success', 'Valor guardado.');
    }

    public function deleteValorTabla($tabla, $id): ResponseInterface
    {
        $tabla = in_array($tabla, ['opcpositivo', 'opcreactivo'], true) ? $tabla : '';
        $id    = (int) $id;
        if ($tabla === '') {
            return redirect()->to('labotests/opciones')->with('error', 'Parámetros inválidos.');
        }
        $this->opcionModel->deleteValorTabla($tabla, $id);
        return redirect()->back()->with('success', 'Valor eliminado.');
    }
}
