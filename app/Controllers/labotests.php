<?php

namespace App\Controllers;

use App\Models\LabotestModel;
use App\Models\LabotestReactivoConfigModel;
use App\Models\OpcionModel;
use App\Models\PerfilExamenModel;
use App\Models\ReactivoModel;
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

        $result = $this->labotestModel->getGroupedByCategoryPaginated(6, $page, $search);

        return view('labotests/manage', [
            'categories'       => $result['categories'],
            'total'            => $result['total'],
            'page'             => $result['page'],
            'total_pages'      => $result['total_pages'],
            'search'           => $search,
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

        $editarSec = (int) ($this->request->getGet('editar') ?? 0);
        $editarPri = (int) ($this->request->getGet('editarpri') ?? 0);
        $editarSecData = [];
        $editarPriData = [];

        if ($compleja) {
            $subItems = $this->labotestModel->getSubItems($prianacategoriaId);
            if ($editarSec > 0) {
                $secRows = array_filter($subItems, fn($s) => (int)($s['secanacategoria_id'] ?? 0) === $editarSec);
                $editarSecData = $secRows ? reset($secRows) : [];
            }
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
            'poblaciones'       => $poblaciones,
            'formulas'               => $formulas,
            'formulas_creadas'       => $formulasCreadas,
            'formulas_id_canonical'  => $formulasIdCanonical,
            'formulas_con_expresion' => $formulasConExpresion ?? [],
            'formulas_con_expresion_deduped' => $formulasConExpresionDeduped,
            'opciones'               => $opciones,
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
        $mostrarValores = (int) ($this->request->getPost('mostrar_valores') ?? 0);
        $prianacategoriaId = (int) ($this->request->getPost('prianacategoria_id') ?? 0);

        if (trim($name) === '') {
            return redirect()->back()->withInput()->with('error', 'El nombre es obligatorio');
        }

        $data = [
            'name'           => $name,
            'order'          => $order,
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
            'formulas_id'        => $formulasId,
            'formula_expresion'  => $formulaExpresion,
            'opcion_id'          => $esSeparador ? 3 : (int) ($this->request->getPost('opcion_id') ?? 3),
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
     * Eliminar análisis (hijo) y todas sus configuraciones
     */
    public function deleteprianacategoria($id)
    {
        $id = (int) $id;
        $sub = $this->labotestModel->getSubInfo($id, null);
        if (!$sub || !$sub->prianacategoria_id) {
            return redirect()->to('labotests')->with('error', 'Análisis no encontrado');
        }
        $subName = $sub->name ?? '';
        $this->labotestModel->deletePrianacategoriaWithAll($id);
        \App\Models\AuditoriaModel::log('labotests', 'eliminar_analisis', (string)$id, \App\Models\AuditoriaModel::detail(['nombre' => $subName]));
        return redirect()->to('labotests')->with('success', 'Análisis y configuraciones eliminados correctamente');
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

        $json = json_encode($payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        if ($json === false) {
            return redirect()->to("labotests/detail/{$id}")->with('error', 'No se pudo generar el archivo de exportación');
        }

        $tipo = ((int) ($payload['compleja'] ?? 0) === 1) ? 'compuesta' : 'simple';
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

        return redirect()->to("labotests/detail/{$id}")
            ->with('success', 'Configuración importada correctamente (' . $imported . ' filas)');
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
            'formulas_id'       => (int) ($this->request->getPost('formulas_id') ?? 1),
            'opcion_id'         => (int) ($this->request->getPost('opcion_id') ?? 3),
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
