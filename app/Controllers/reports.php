<?php

namespace App\Controllers;

use App\Models\ReportModel;
use App\Models\ReactivoModel;
use App\Models\AppConfigModel;
use App\Models\ToquoteModel;
use App\Models\EmployeeModel;
use App\Models\PoblacionModel;
use App\Services\RegisterService;

class Reports extends SecureArea
{
    protected ?string $moduleId = 'reports';

    protected ReportModel $reportModel;
    protected ToquoteModel $toquoteModel;
    protected ReactivoModel $reactivoModel;

    public function __construct()
    {
        parent::__construct();
        $this->reportModel   = model(ReportModel::class);
        $this->toquoteModel  = model(ToquoteModel::class);
        $this->reactivoModel = model(ReactivoModel::class);
    }

    public function index()
    {
        return view('reports/listing', [
            'allowed_modules'  => $this->allowed_modules,
            'user_info'        => $this->user_info,
            'current_module'   => 'reports',
        ]);
    }

    /**
     * Reporte: todas las cotizaciones guardadas (con detalle y re-imprimir PDF)
     */
    public function cotizacionesGuardadas()
    {
        $perPage    = 25;
        $page       = max(1, (int) ($this->request->getGet('page') ?? 1));
        $offset     = ($page - 1) * $perPage;

        $data       = $this->toquoteModel->getAllCotizaciones($perPage, $offset);
        $total      = $this->toquoteModel->countAllCotizaciones();
        $totalPages = $total > 0 ? (int) ceil($total / $perPage) : 1;

        return view('reports/cotizaciones_guardadas', [
            'title'           => 'Todas las cotizaciones guardadas',
            'current_module'  => 'reports',
            'subtitle'        => 'Listado con detalle y opción de re-imprimir PDF',
            'data'            => $data,
            'page'            => $page,
            'totalPages'      => $totalPages,
            'total'           => $total,
            'perPage'         => $perPage,
            'allowed_modules' => $this->allowed_modules,
            'user_info'       => $this->user_info,
        ]);
    }

    public function registrosFecha()
    {
        $startDate = $this->request->getGet('start') ?? date('Y-m-d');
        $endDate   = $this->request->getGet('end') ?? date('Y-m-d');

        $data     = $this->reportModel->getRegistrosByDateRange($startDate, $endDate);
        $totales  = $this->reportModel->getTotalesByDateRange($startDate, $endDate);

        return view('reports/registros_fecha', [
            'title'           => 'Reporte de registros por fecha',
            'current_module'  => 'reports',
            'subtitle'        => date('d/m/Y', strtotime($startDate)) . ' - ' . date('d/m/Y', strtotime($endDate)),
            'data'            => $data,
            'totales'         => $totales,
            'startDate'       => $startDate,
            'endDate'         => $endDate,
            'allowed_modules' => $this->allowed_modules,
            'user_info'       => $this->user_info,
        ]);
    }

    public function ingresosFecha()
    {
        $startDate = $this->request->getGet('start') ?? date('Y-m-d');
        $endDate   = $this->request->getGet('end') ?? date('Y-m-d');

        $facturables = $this->reportModel->getIngresosByDateRange($startDate, $endDate);
        $anulPorDia  = $this->reportModel->getIngresosAnuladosPorDia($startDate, $endDate);
        $totales     = $this->reportModel->getTotalesByDateRange($startDate, $endDate);
        $totalesAnul = $this->reportModel->getTotalesAnuladosByDateRange($startDate, $endDate);

        $byFecha = [];
        foreach ($facturables as $r) {
            $f = $r['fecha'];
            $byFecha[$f] = [
                'fecha'               => $f,
                'cantidad'            => (int) ($r['cantidad'] ?? 0),
                'total'               => (float) ($r['total'] ?? 0),
                'cobrado'             => (float) ($r['cobrado'] ?? 0),
                'cantidad_anuladas'   => 0,
                'total_anulado_ref'   => 0.0,
                'cobrado_anulado_ref' => 0.0,
            ];
        }
        foreach ($anulPorDia as $r) {
            $f = $r['fecha'];
            if (! isset($byFecha[$f])) {
                $byFecha[$f] = [
                    'fecha'               => $f,
                    'cantidad'            => 0,
                    'total'               => 0.0,
                    'cobrado'             => 0.0,
                    'cantidad_anuladas'   => 0,
                    'total_anulado_ref'   => 0.0,
                    'cobrado_anulado_ref' => 0.0,
                ];
            }
            $byFecha[$f]['cantidad_anuladas']   = (int) ($r['cantidad'] ?? 0);
            $byFecha[$f]['total_anulado_ref']   = (float) ($r['total'] ?? 0);
            $byFecha[$f]['cobrado_anulado_ref'] = (float) ($r['cobrado'] ?? 0);
        }
        ksort($byFecha);
        $data = array_values($byFecha);

        return view('reports/ingresos_fecha', [
            'title'           => 'Reporte de ingresos por fecha',
            'current_module'  => 'reports',
            'subtitle'        => date('d/m/Y', strtotime($startDate)) . ' - ' . date('d/m/Y', strtotime($endDate)),
            'data'            => $data,
            'totales'         => $totales,
            'totalesAnulados' => $totalesAnul,
            'startDate'       => $startDate,
            'endDate'         => $endDate,
            'allowed_modules' => $this->allowed_modules,
            'user_info'       => $this->user_info,
        ]);
    }

    public function porDoctor()
    {
        $startDate = $this->request->getGet('start') ?? date('Y-m-d');
        $endDate   = $this->request->getGet('end') ?? date('Y-m-d');

        $data    = $this->reportModel->getRegistrosByDoctor($startDate, $endDate);
        $totales = $this->reportModel->getTotalesByDateRange($startDate, $endDate);

        return view('reports/por_doctor', [
            'title'           => 'Reporte de registros por doctor',
            'current_module'  => 'reports',
            'subtitle'        => date('d/m/Y', strtotime($startDate)) . ' - ' . date('d/m/Y', strtotime($endDate)),
            'data'            => $data,
            'totales'         => $totales,
            'startDate'       => $startDate,
            'endDate'         => $endDate,
            'allowed_modules' => $this->allowed_modules,
            'user_info'       => $this->user_info,
        ]);
    }

    public function pagos()
    {
        $startDate = $this->request->getGet('start') ?? date('Y-m-d');
        $endDate   = $this->request->getGet('end') ?? date('Y-m-d');

        $todos     = $this->reportModel->getReportePagos($startDate, $endDate);
        $pendientes = $this->reportModel->getPendientesPago($startDate, $endDate);
        $pagosPagados = $this->reportModel->getPagosPagadosDetalle($startDate, $endDate);
        $totales   = $this->reportModel->getTotalesPagos($startDate, $endDate);
        $resumenPagosPorTipo = $this->reportModel->getResumenPagosPorTipo($startDate, $endDate);
        $resumenPagosPorDia = $this->reportModel->getResumenPagosPorDia($startDate, $endDate);
        $resumenPagosPorDoctor = $this->reportModel->getResumenPagosPorDoctor($startDate, $endDate);

        $tipoPagoMap = ['1' => 'Efectivo', '2' => 'QR', '3' => 'Transferencia', '4' => 'Pendiente'];

        return view('reports/pagos', [
            'title'           => 'Reporte de pagos',
            'current_module'  => 'reports',
            'subtitle'        => date('d/m/Y', strtotime($startDate)) . ' - ' . date('d/m/Y', strtotime($endDate)),
            'todos'           => $todos,
            'pendientes'      => $pendientes,
            'pagosPagados'   => $pagosPagados,
            'totales'         => $totales,
            'tipoPagoMap'     => $tipoPagoMap,
            'resumenPagosPorTipo' => $resumenPagosPorTipo,
            'resumenPagosPorDia' => $resumenPagosPorDia,
            'resumenPagosPorDoctor' => $resumenPagosPorDoctor,
            'startDate'       => $startDate,
            'endDate'         => $endDate,
            'allowed_modules' => $this->allowed_modules,
            'user_info'       => $this->user_info,
        ]);
    }

    public function pruebasFecha()
    {
        $startDate = $this->request->getGet('start') ?? date('Y-m-d');
        $endDate   = $this->request->getGet('end') ?? date('Y-m-d');

        $data = $this->reportModel->getPruebasPorFecha($startDate, $endDate);

        return view('reports/pruebas_fecha', [
            'title'           => 'Reporte de pruebas realizadas por fecha',
            'current_module'  => 'reports',
            'subtitle'        => date('d/m/Y', strtotime($startDate)) . ' - ' . date('d/m/Y', strtotime($endDate)),
            'data'            => $data,
            'startDate'       => $startDate,
            'endDate'         => $endDate,
            'allowed_modules' => $this->allowed_modules,
            'user_info'       => $this->user_info,
        ]);
    }

    public function pruebasIncompletasFecha()
    {
        $startDate = $this->request->getGet('start') ?? date('Y-m-d');
        $endDate   = $this->request->getGet('end') ?? date('Y-m-d');

        $data = $this->reportModel->getPruebasIncompletasPorFecha($startDate, $endDate);

        return view('reports/pruebas_incompletas_fecha', [
            'title'           => 'Pruebas incompletas por fecha',
            'current_module'  => 'reports',
            'subtitle'        => date('d/m/Y', strtotime($startDate)) . ' - ' . date('d/m/Y', strtotime($endDate)),
            'data'            => $data,
            'startDate'       => $startDate,
            'endDate'         => $endDate,
            'allowed_modules' => $this->allowed_modules,
            'user_info'       => $this->user_info,
        ]);
    }

    public function pruebasAnuladasFecha()
    {
        $startDate = $this->request->getGet('start') ?? date('Y-m-d');
        $endDate   = $this->request->getGet('end') ?? date('Y-m-d');

        $data = $this->reportModel->getPruebasAnuladasPorFecha($startDate, $endDate);

        return view('reports/pruebas_anuladas_fecha', [
            'title'              => 'Pruebas anuladas por fecha',
            'current_module'     => 'reports',
            'subtitle'           => date('d/m/Y', strtotime($startDate)) . ' - ' . date('d/m/Y', strtotime($endDate)),
            'data'               => $data,
            'startDate'          => $startDate,
            'endDate'            => $endDate,
            'anulacionDisponible'=> $this->reportModel->tieneCampoAnuladoEnRegistro(),
            'allowed_modules'    => $this->allowed_modules,
            'user_info'          => $this->user_info,
        ]);
    }

    /**
     * Estadísticas en rango: pruebas, desglose por tipo, pacientes, grupo poblacional (config) y género.
     */
    public function estadisticasLaboratorio()
    {
        $startDate = $this->request->getGet('start') ?? date('Y-m-d');
        $endDate   = $this->request->getGet('end') ?? date('Y-m-d');

        $rows = $this->reportModel->getRegistrosEstadisticasLaboratorio($startDate, $endDate);

        $poblaciones = model(PoblacionModel::class)->getAll();
        $byPobId     = [];
        foreach ($poblaciones as $p) {
            $id = (int) ($p['id_poblacion'] ?? 0);
            if ($id > 0) {
                $byPobId[$id] = $p;
            }
        }

        $registerService = new RegisterService();
        $labelsPrueba    = $this->reportModel->getPrianacategoriaLabelsMap();

        $resumen = [
            'ordenes'               => 0,
            'pruebas_realizadas'    => 0,
            'pacientes_distintos'   => 0,
            'ordenes_sin_persona'   => 0,
        ];
        $porGeneroOrdenes       = ['1' => 0, '2' => 0, '_' => 0];
        $porGeneroPacientes     = ['1' => 0, '2' => 0, '_' => 0];
        $seenPid                = [];
        $porPoblacion           = [];
        $conteoPorPruebaId      = [];
        $pacientesPorPruebaId   = [];

        foreach ($rows as $row) {
            $resumen['ordenes']++;
            $pid = (int) ($row['person_id'] ?? 0);
            if ($pid <= 0) {
                $resumen['ordenes_sin_persona']++;
            }

            $pruebasStr = trim((string) ($row['pruebas'] ?? ''));
            $idsPrueba  = $registerService->extractPrianacategoriaIdsFromRegistroPruebas($pruebasStr);
            foreach ($idsPrueba as $prId) {
                if ($prId < 1) {
                    continue;
                }
                // Misma noción que el reporte «Pruebas por fecha»: IDs en registro.pruebas (no exige regvalues).
                $resumen['pruebas_realizadas']++;
                $conteoPorPruebaId[$prId] = ($conteoPorPruebaId[$prId] ?? 0) + 1;
                if ($pid > 0) {
                    if (!isset($pacientesPorPruebaId[$prId])) {
                        $pacientesPorPruebaId[$prId] = [];
                    }
                    $pacientesPorPruebaId[$prId][$pid] = true;
                }
            }

            $gk = $this->generoKeyReporte($row['gender'] ?? null);
            $porGeneroOrdenes[$gk]++;

            if ($pid > 0 && !isset($seenPid[$pid])) {
                $seenPid[$pid] = true;
                $resumen['pacientes_distintos']++;
                $porGeneroPacientes[$gk]++;
            }

            try {
                $ingreso = new \DateTime($row['ingreso']);
            } catch (\Throwable $e) {
                $ingreso = new \DateTime();
            }

            $birth = !empty($row['birthday']) ? (string) $row['birthday'] : null;
            $gen   = isset($row['gender']) && $row['gender'] !== '' ? (int) $row['gender'] : null;
            $matching = $registerService->getMatchingPoblacionIds($birth, $gen, $ingreso);
            $pobId    = $this->pickPrimaryPoblacionId($matching, $byPobId);
            $porPoblacion[$pobId] = ($porPoblacion[$pobId] ?? 0) + 1;
        }

        $porPruebaRows = [];
        foreach ($conteoPorPruebaId as $id => $cnt) {
            $meta = $labelsPrueba[$id] ?? null;
            $porPruebaRows[] = [
                'prianacategoria_id'  => $id,
                'prueba'              => $meta['prueba'] ?? ('ID ' . $id),
                'categoria'           => $meta['categoria'] ?? '',
                'ordenes_con_prueba'  => $cnt,
                'pacientes_distintos' => isset($pacientesPorPruebaId[$id]) ? count($pacientesPorPruebaId[$id]) : 0,
            ];
        }
        usort($porPruebaRows, static function (array $a, array $b): int {
            $c = ($b['ordenes_con_prueba'] ?? 0) <=> ($a['ordenes_con_prueba'] ?? 0);
            if ($c !== 0) {
                return $c;
            }
            $cc = strcasecmp((string) ($a['categoria'] ?? ''), (string) ($b['categoria'] ?? ''));
            if ($cc !== 0) {
                return $cc;
            }

            return strcasecmp((string) ($a['prueba'] ?? ''), (string) ($b['prueba'] ?? ''));
        });

        $poblacionGrupoRows = [];
        foreach ($poblaciones as $p) {
            $id = (int) ($p['id_poblacion'] ?? 0);
            if ($id < 1) {
                continue;
            }
            $poblacionGrupoRows[] = [
                'nombre'     => (string) ($p['name'] ?? ''),
                'rango_edad' => PoblacionModel::formatRangoEdad($p),
                'ordenes'    => (int) ($porPoblacion[$id] ?? 0),
            ];
        }
        foreach ($porPoblacion as $id => $cnt) {
            $id = (int) $id;
            if ($id < 1 || isset($byPobId[$id])) {
                continue;
            }
            $poblacionGrupoRows[] = [
                'nombre'     => 'Grupo #' . $id,
                'rango_edad' => '—',
                'ordenes'    => (int) $cnt,
            ];
        }

        return view('reports/estadisticas_laboratorio', [
            'title'                => 'Estadísticas de laboratorio por período',
            'current_module'       => 'reports',
            'subtitle'             => date('d/m/Y', strtotime($startDate)) . ' - ' . date('d/m/Y', strtotime($endDate)),
            'startDate'            => $startDate,
            'endDate'              => $endDate,
            'resumen'              => $resumen,
            'porGeneroOrdenes'     => $porGeneroOrdenes,
            'porGeneroPacientes'   => $porGeneroPacientes,
            'poblacionGrupoRows'  => $poblacionGrupoRows,
            'porPruebaRows'        => $porPruebaRows,
            'allowed_modules'      => $this->allowed_modules,
            'user_info'            => $this->user_info,
        ]);
    }

    private function generoKeyReporte($gender): string
    {
        $g = (string) $gender;

        return in_array($g, ['1', '2'], true) ? $g : '_';
    }

    /**
     * Elige un solo id_poblacion para contabilizar la orden (misma tabla que en Config → Población).
     *
     * @param array<int, array<string, mixed>> $byPobId
     */
    private function pickPrimaryPoblacionId(array $matchingIds, array $byPobId): int
    {
        $ids = array_values(array_unique(array_map('intval', $matchingIds)));
        if ($ids === []) {
            return 3;
        }
        usort($ids, static function (int $a, int $b) use ($byPobId): int {
            $oa = (int) ($byPobId[$a]['orden'] ?? 999);
            $ob = (int) ($byPobId[$b]['orden'] ?? 999);
            if ($oa !== $ob) {
                return $oa <=> $ob;
            }
            $aAd = $a === 3 ? 1 : 0;
            $bAd = $b === 3 ? 1 : 0;
            if ($aAd !== $bAd) {
                return $aAd <=> $bAd;
            }

            return $a <=> $b;
        });

        return $ids[0];
    }

    /**
     * Reporte de insumos por fecha de ingreso y vencimiento.
     * Marca en amarillo los que están por vencer (según config días_alerta_vencimiento)
     * y en rojo los ya vencidos.
     */
    public function insumosVencimiento()
    {
        $reactivoModel = model(ReactivoModel::class);
        $appConfig     = model(AppConfigModel::class);
        $diasAlerta    = max(1, (int) ($appConfig->getValue('dias_alerta_vencimiento') ?: 40));

        $lotes = $reactivoModel->getTodosLotesParaReporte();
        $hoy   = date('Y-m-d');
        $enX   = date('Y-m-d', strtotime("+{$diasAlerta} days"));

        foreach ($lotes as &$l) {
            $venc = $l['fecha_vencimiento'] ?? null;
            if (!$venc) {
                $l['estado']       = 'sin_fecha';
                $l['dias_restantes'] = null;
                $l['estado_label'] = '-';
            } else {
                $diff = (strtotime($venc) - strtotime($hoy)) / 86400;
                $l['dias_restantes'] = (int) round($diff);
                if ($diff < 0) {
                    $l['estado'] = 'vencido';
                    $l['estado_label'] = 'Vencido';
                } elseif ($venc <= $enX) {
                    $l['estado'] = 'por_vencer';
                    $l['estado_label'] = 'Por vencer (' . $l['dias_restantes'] . ' días)';
                } else {
                    $l['estado'] = 'ok';
                    $l['estado_label'] = $l['dias_restantes'] . ' días';
                }
            }
        }
        unset($l);

        return view('reports/insumos_vencimiento', [
            'title'           => 'Reporte de insumos por ingreso y vencimiento',
            'current_module'  => 'reports',
            'subtitle'        => 'Alerta configurada: ' . $diasAlerta . ' días antes del vencimiento',
            'data'            => $lotes,
            'dias_alerta'     => $diasAlerta,
            'allowed_modules' => $this->allowed_modules,
            'user_info'       => $this->user_info,
        ]);
    }

    /**
     * Reporte de costos de todas las pruebas (precio y precio derivado)
     */
    public function costosPruebas()
    {
        $busqueda = $this->request->getGet('busqueda') ?? '';
        $data = $this->reportModel->getCostosPruebas($busqueda);

        return view('reports/costos_pruebas', [
            'title'           => 'Reporte de costos de pruebas',
            'current_module'  => 'reports',
            'subtitle'        => 'Precio y precio derivado de todas las pruebas',
            'data'            => $data,
            'busqueda'        => $busqueda,
            'allowed_modules' => $this->allowed_modules,
            'user_info'       => $this->user_info,
        ]);
    }

    /**
     * Kardex de inventario.
     * Reutiliza el módulo de inventario para evitar duplicar lógica.
     */
    public function inventarioKardex()
    {
        $query = $this->request->getServer('QUERY_STRING');
        $url = 'inventario/kardex' . ($query ? ('?' . $query) : '');

        return redirect()->to($url);
    }

    /**
     * Exportar reporte de costos de pruebas a Excel/CSV
     */
    public function exportCostosPruebas()
    {
        $busqueda = $this->request->getGet('busqueda') ?? '';
        $data = $this->reportModel->getCostosPruebas($busqueda);

        $filename = 'costos_pruebas_' . date('Y-m-d_H-i-s') . '.csv';
        
        header('Content-Type: text/csv');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        header('Cache-Control: no-cache, must-revalidate');
        header('Expires: 0');

        $output = fopen('php://output', 'w');
        
        // Cabeceras en español
        fwrite($output, "\xEF\xBB\xBF"); // BOM para UTF-8
        fputcsv($output, ['Categoría', 'Prueba', 'Precio', 'Precio Derivado']);

        $totalPrecio = 0;
        $totalDerivado = 0;

        foreach ($data as $item) {
            $precio = (float) ($item['precio'] ?? 0);
            $derivado = (float) ($item['precio_derivado'] ?? 0);
            
            $totalPrecio += $precio;
            $totalDerivado += $derivado;
            
            fputcsv($output, [
                $item['categoria'],
                $item['prueba'],
                number_format($precio, 2, '.', ''),
                number_format($derivado, 2, '.', ''),
            ]);
        }

        // Total general
        fputcsv($output, []);
        fputcsv($output, ['TOTAL GENERAL', '', number_format($totalPrecio, 2, '.', ''), number_format($totalDerivado, 2, '.', '')]);

        fclose($output);
        exit;
    }

    /**
     * Reporte de valores de referencia de todas las pruebas con buscador
     */
    public function valoresReferencia()
    {
        $busqueda = $this->request->getGet('busqueda') ?? '';
        $data = $this->reportModel->getValoresReferencia($busqueda);

        // Depuración temporal - comparar con exportación
        error_log('=== VISTA DEBUG ===');
        error_log('Total registros en vista: ' . count($data));
        error_log('Búsqueda en vista: "' . $busqueda . '"');
        
        $conValores = 0;
        $sinValores = 0;
        foreach ($data as $item) {
            if (!empty($item['valor_min']) || !empty($item['valor_max'])) {
                $conValores++;
            } else {
                $sinValores++;
            }
        }
        error_log('Vista - Registros con valores: ' . $conValores);
        error_log('Vista - Registros sin valores: ' . $sinValores);
        error_log('=== FIN VISTA DEBUG ===');

        return view('reports/valores_referencia', [
            'title'           => 'Reporte de valores de referencia',
            'current_module'  => 'reports',
            'subtitle'        => 'Valores de referencia de todas las pruebas',
            'data'            => $data,
            'busqueda'        => $busqueda,
            'allowed_modules' => $this->allowed_modules,
            'user_info'       => $this->user_info,
        ]);
    }

    /**
     * Exportar reporte de valores de referencia a Excel/CSV
     */
    public function exportValoresReferencia()
    {
        $busqueda = $this->request->getGet('busqueda') ?? '';
        $data = $this->reportModel->getValoresReferencia($busqueda);

        // Depuración temporal
        error_log('=== EXPORTACIÓN DEBUG ===');
        error_log('Total registros obtenidos: ' . count($data));
        error_log('Búsqueda: "' . $busqueda . '"');
        
        $conValores = 0;
        $sinValores = 0;
        foreach ($data as $item) {
            if (!empty($item['valor_min']) || !empty($item['valor_max'])) {
                $conValores++;
            } else {
                $sinValores++;
            }
        }
        error_log('Registros con valores: ' . $conValores);
        error_log('Registros sin valores: ' . $sinValores);
        
        // Mostrar primeras 5 filas para depuración
        for ($i = 0; $i < min(5, count($data)); $i++) {
            error_log('Registro ' . ($i + 1) . ': ' . json_encode($data[$i]));
        }
        error_log('=== FIN EXPORTACIÓN DEBUG ===');

        $filename = 'valores_referencia_' . date('Y-m-d_H-i-s') . '.csv';
        
        header('Content-Type: text/csv');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        header('Cache-Control: no-cache, must-revalidate');
        header('Expires: 0');

        $output = fopen('php://output', 'w');
        
        // Cabeceras en español
        fwrite($output, "\xEF\xBB\xBF"); // BOM para UTF-8
        fputcsv($output, ['Categoría', 'Prueba', 'Análisis', 'Población', 'Sexo', 'Valor Mínimo', 'Valor Máximo', 'Unidad']);

        foreach ($data as $index => $item) {
            // Solo exportar pruebas que tienen valores
            if (empty($item['valor_min']) && empty($item['valor_max'])) {
                continue;
            }
            
            // Funciones helper para exportación
            $poblacion = $this->getPoblacionLabel($item['poblacion'] ?? 3);
            $sexo = $this->getSexoLabel($item['sexo'] ?? 'ambos');
            
            fputcsv($output, [
                $item['categoria'] ?? '',
                $item['prueba'] ?? '',
                $item['analisis'] ?? '',
                $poblacion,
                $sexo,
                $item['valor_min'] ?? '',
                $item['valor_max'] ?? '',
                $item['umedida'] ?? ''
            ]);
        }

        fclose($output);
        exit;
    }

    /**
     * Helper para obtener etiqueta de población
     */
    private function getPoblacionLabel($paciente_id)
    {
        // Manejar valores nulos o vacíos
        if ($paciente_id === null || $paciente_id === '' || $paciente_id === 0) {
            return 'Adulto'; // Valor por defecto
        }
        
        $poblaciones = [
            1 => 'Recién nacido',
            2 => 'Niño', 
            3 => 'Adulto',
            4 => 'Adulto mayor',
            5 => 'Embarazada'
        ];
        return $poblaciones[$paciente_id] ?? 'Adulto'; // Valor por defecto si no encuentra
    }

    /**
     * Helper para obtener etiqueta de sexo
     */
    private function getSexoLabel($sexo)
    {
        if ($sexo === 'masculino') return 'Masculino';
        if ($sexo === 'femenino') return 'Femenino';
        return 'Ambos';
    }
}
