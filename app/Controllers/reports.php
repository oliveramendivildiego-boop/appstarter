<?php

namespace App\Controllers;

use App\Models\ReportModel;
use App\Models\ReactivoModel;
use App\Models\AppConfigModel;
use App\Models\ToquoteModel;

class Reports extends SecureArea
{
    protected ?string $moduleId = 'reports';

    protected ReportModel $reportModel;
    protected ToquoteModel $toquoteModel;

    public function __construct()
    {
        parent::__construct();
        $this->reportModel   = model(ReportModel::class);
        $this->toquoteModel  = model(ToquoteModel::class);
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

        $data    = $this->reportModel->getIngresosByDateRange($startDate, $endDate);
        $totales = $this->reportModel->getTotalesByDateRange($startDate, $endDate);

        return view('reports/ingresos_fecha', [
            'title'           => 'Reporte de ingresos por fecha',
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
        $totales   = $this->reportModel->getTotalesPagos($startDate, $endDate);

        $tipoPagoMap = ['1' => 'Efectivo', '2' => 'QR', '3' => 'Transferencia', '4' => 'Pendiente'];

        return view('reports/pagos', [
            'title'           => 'Reporte de pagos',
            'current_module'  => 'reports',
            'subtitle'        => date('d/m/Y', strtotime($startDate)) . ' - ' . date('d/m/Y', strtotime($endDate)),
            'todos'           => $todos,
            'pendientes'      => $pendientes,
            'totales'         => $totales,
            'tipoPagoMap'     => $tipoPagoMap,
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
        fputcsv($output, ['Categoría', 'Prueba', 'Precio', 'Precio Derivado', 'Diferencia']);

        $totalPrecio = 0;
        $totalDerivado = 0;
        $categoriaActual = null;

        foreach ($data as $item) {
            $precio = (float) ($item['precio'] ?? 0);
            $derivado = (float) ($item['precio_derivado'] ?? 0);
            $diferencia = $derivado - $precio;
            
            $totalPrecio += $precio;
            $totalDerivado += $derivado;
            
            fputcsv($output, [
                $item['categoria'],
                $item['prueba'],
                number_format($precio, 2, '.', ''),
                number_format($derivado, 2, '.', ''),
                number_format($diferencia, 2, '.', '')
            ]);
        }

        // Total general
        fputcsv($output, []);
        fputcsv($output, ['TOTAL GENERAL', '', number_format($totalPrecio, 2, '.', ''), number_format($totalDerivado, 2, '.', ''), number_format($totalDerivado - $totalPrecio, 2, '.', '')]);

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
