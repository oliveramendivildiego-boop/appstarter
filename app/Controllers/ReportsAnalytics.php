<?php

namespace App\Controllers;

use App\Libraries\ReportPdfDocument;
use App\Models\ReportAnalyticsModel;
use App\Services\RegisterService;
use CodeIgniter\HTTP\ResponseInterface;

/**
 * Controlador independiente para los 10 reportes analíticos nuevos.
 *
 * - No modifica ningún flujo existente: solo lecturas (SELECT) vía ReportAnalyticsModel.
 * - Usa el mismo permiso de módulo `reports` que el resto de reportes.
 * - Cada reporte ofrece: filtro por fecha, PDF, Excel (CSV UTF-8), impresión,
 *   totalizadores y tabla con buscador/ordenamiento/paginación (cliente).
 */
class ReportsAnalytics extends SecureArea
{
    protected ?string $moduleId = 'reports';

    protected ReportAnalyticsModel $analytics;

    public function __construct()
    {
        parent::__construct();
        $this->analytics = model(ReportAnalyticsModel::class);
    }

    // ------------------------------------------------------------------
    // Utilidades comunes
    // ------------------------------------------------------------------

    /** @return array{0: string, 1: string} [start, end] desde GET con defaults */
    private function getRangoFechas(string $defaultStartModifier = ''): array
    {
        $defaultStart = $defaultStartModifier !== ''
            ? RegisterService::reportDateFromModifier($defaultStartModifier)
            : RegisterService::todayForReport();
        $start = trim((string) ($this->request->getGet('start') ?? '')) ?: $defaultStart;
        $end   = trim((string) ($this->request->getGet('end') ?? '')) ?: RegisterService::todayForReport();

        return [$start, $end];
    }

    /** @return array<string, mixed> Datos comunes de todas las vistas */
    private function commonViewData(string $title, string $startDate, string $endDate): array
    {
        return [
            'title'           => $title,
            'subtitle'        => RegisterService::formatReportDateRangeSubtitle($startDate, $endDate),
            'startDate'       => $startDate,
            'endDate'         => $endDate,
            'current_module'  => 'reports',
            'allowed_modules' => $this->allowed_modules,
            'user_info'       => $this->user_info,
        ];
    }

    private function safePdfFilename(string $base): string
    {
        return preg_replace('/[^A-Za-z0-9._-]+/', '_', $base) . '_' . lab_filename_date() . '.pdf';
    }

    /**
     * Exportación Excel: CSV con BOM UTF-8 (mismo formato que catalogoPruebasExcel).
     *
     * @param list<string> $headers
     * @param list<list<mixed>> $rows
     */
    private function streamCsv(string $baseName, array $headers, array $rows): void
    {
        $filename = preg_replace('/[^A-Za-z0-9._-]+/', '_', $baseName) . '_' . lab_filename_datetime() . '.csv';

        header('Content-Type: text/csv; charset=UTF-8');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        header('Cache-Control: no-cache, must-revalidate');
        header('Expires: 0');

        $output = fopen('php://output', 'w');
        fwrite($output, "\xEF\xBB\xBF");
        fputcsv($output, $headers);
        foreach ($rows as $row) {
            fputcsv($output, $row);
        }
        fclose($output);
        exit;
    }

    /** Etiqueta legible para los estados de clasificación de valores. */
    public static function estadoValorLabel(string $estado): string
    {
        return match ($estado) {
            'critico_bajo'   => 'Crítico bajo',
            'critico_alto'   => 'Crítico alto',
            'bajo'           => 'Bajo',
            'alto'           => 'Alto',
            'normal'         => 'Normal',
            'sin_referencia' => 'Sin referencia',
            default          => 'No numérico',
        };
    }

    // ==================================================================
    // 1. PRUEBAS MÁS SOLICITADAS
    // ==================================================================

    /** @return array<string, mixed> */
    private function collectMasSolicitadasPayload(): array
    {
        [$start, $end] = $this->getRangoFechas('monday this week');
        $grupoId  = (int) ($this->request->getGet('grupo_id') ?? 0);
        $doctorId = (int) ($this->request->getGet('doctor_id') ?? 0);

        $resultado = $this->analytics->getPruebasMasSolicitadas($start, $end, $grupoId, $doctorId);

        return [
            'startDate' => $start,
            'endDate'   => $end,
            'grupoId'   => $grupoId,
            'doctorId'  => $doctorId,
            'rows'      => $resultado['rows'],
            'totales'   => [
                'total_pruebas'  => $resultado['total_pruebas'],
                'total_ingresos' => $resultado['total_ingresos'],
                'total_ordenes'  => $resultado['total_ordenes'],
            ],
        ];
    }

    public function pruebasMasSolicitadas()
    {
        $payload = $this->collectMasSolicitadasPayload();

        return view('reports/analytics/pruebas_mas_solicitadas', array_merge(
            $this->commonViewData('Pruebas más solicitadas', $payload['startDate'], $payload['endDate']),
            $payload,
            [
                'grupos'   => $this->analytics->getGruposAnalisis(),
                'doctores' => $this->analytics->getDoctores(),
            ]
        ));
    }

    public function pruebasMasSolicitadasPdf()
    {
        $payload = $this->collectMasSolicitadasPayload();
        ReportPdfDocument::download(
            $this->safePdfFilename('pruebas_mas_solicitadas'),
            'Pruebas más solicitadas',
            RegisterService::formatReportDateRangeSubtitle($payload['startDate'], $payload['endDate']),
            'reports/analytics/pdf/pruebas_mas_solicitadas',
            $payload
        );
    }

    public function pruebasMasSolicitadasExcel()
    {
        $payload = $this->collectMasSolicitadasPayload();
        $rows    = [];
        foreach ($payload['rows'] as $row) {
            $rows[] = [
                $row['ranking'], $row['codigo'], $row['prueba'], $row['grupo'],
                $row['cantidad'], $row['porcentaje'] . '%', number_format((float) $row['ingreso'], 2, '.', ''),
            ];
        }
        $rows[] = [];
        $rows[] = ['TOTALES', '', '', '', $payload['totales']['total_pruebas'], '100%', number_format((float) $payload['totales']['total_ingresos'], 2, '.', '')];
        $this->streamCsv('pruebas_mas_solicitadas', ['Ranking', 'Código', 'Prueba', 'Grupo', 'Cantidad', 'Porcentaje', 'Ingreso estimado'], $rows);
    }

    // ==================================================================
    // 2. TENDENCIA HISTÓRICA POR PACIENTE
    // ==================================================================

    /** @return array<string, mixed> */
    private function collectTendenciaPayload(): array
    {
        [$start, $end] = $this->getRangoFechas('-1 year');
        $personId = (int) ($this->request->getGet('paciente_id') ?? 0);
        $pruebaId = (int) ($this->request->getGet('prueba_id') ?? 0);
        $busqueda = trim((string) ($this->request->getGet('q') ?? ''));

        $paciente   = $personId > 0 ? $this->analytics->getPaciente($personId) : null;
        $resultados = $paciente !== null
            ? $this->analytics->getTendenciaPaciente($personId, $start, $end, $pruebaId)
            : [];

        return [
            'startDate'  => $start,
            'endDate'    => $end,
            'personId'   => $personId,
            'pruebaId'   => $pruebaId,
            'busqueda'   => $busqueda,
            'paciente'   => $paciente,
            'rows'       => $resultados,
            'candidatos' => ($paciente === null && $busqueda !== '') ? $this->analytics->searchPacientes($busqueda) : [],
        ];
    }

    public function tendenciaPaciente()
    {
        $payload = $this->collectTendenciaPayload();

        return view('reports/analytics/tendencia_paciente', array_merge(
            $this->commonViewData('Tendencia histórica por paciente', $payload['startDate'], $payload['endDate']),
            $payload,
            ['pruebas' => $this->analytics->getPruebasCatalogoListado()]
        ));
    }

    public function tendenciaPacientePdf()
    {
        $payload = $this->collectTendenciaPayload();
        $nombre  = $payload['paciente']['paciente'] ?? 'paciente';
        ReportPdfDocument::download(
            $this->safePdfFilename('tendencia_' . $nombre),
            'Tendencia histórica por paciente',
            ($nombre !== 'paciente' ? $nombre . ' · ' : '') . RegisterService::formatReportDateRangeSubtitle($payload['startDate'], $payload['endDate']),
            'reports/analytics/pdf/tendencia_paciente',
            $payload
        );
    }

    public function tendenciaPacienteExcel()
    {
        $payload = $this->collectTendenciaPayload();
        $rows    = [];
        foreach ($payload['rows'] as $row) {
            $rows[] = [
                $row['ingreso'], $row['numero_orden'] ?: $row['registro_id'], $row['grupo'], $row['prueba'], $row['parametro'],
                $row['valor'], $row['unidad'], $row['valor_min'], $row['valor_max'],
                self::estadoValorLabel((string) $row['estado']), $row['doctor'],
            ];
        }
        $this->streamCsv(
            'tendencia_paciente',
            ['Fecha', 'Orden', 'Grupo', 'Prueba', 'Parámetro', 'Resultado', 'Unidad', 'Ref. mín', 'Ref. máx', 'Estado', 'Médico solicitante'],
            $rows
        );
    }

    // ==================================================================
    // 3. VALORES CRÍTICOS O FUERA DE RANGO
    // ==================================================================

    /** @return array<string, mixed> */
    private function collectValoresCriticosPayload(): array
    {
        [$start, $end] = $this->getRangoFechas('monday this week');
        $grupoId  = (int) ($this->request->getGet('grupo_id') ?? 0);
        $pruebaId = (int) ($this->request->getGet('prueba_id') ?? 0);

        $resultado = $this->analytics->getValoresCriticos($start, $end, $grupoId, $pruebaId);

        return [
            'startDate' => $start,
            'endDate'   => $end,
            'grupoId'   => $grupoId,
            'pruebaId'  => $pruebaId,
            'rows'      => $resultado['rows'],
            'porEstado' => $resultado['porEstado'],
            'porPrueba' => $resultado['porPrueba'],
            'porGrupo'  => $resultado['porGrupo'],
            'total'     => $resultado['total'],
        ];
    }

    public function valoresCriticos()
    {
        $payload = $this->collectValoresCriticosPayload();

        return view('reports/analytics/valores_criticos', array_merge(
            $this->commonViewData('Valores críticos o fuera de rango', $payload['startDate'], $payload['endDate']),
            $payload,
            [
                'grupos'  => $this->analytics->getGruposAnalisis(),
                'pruebas' => $this->analytics->getPruebasCatalogoListado(),
            ]
        ));
    }

    public function valoresCriticosPdf()
    {
        $payload = $this->collectValoresCriticosPayload();
        ReportPdfDocument::download(
            $this->safePdfFilename('valores_criticos'),
            'Valores críticos o fuera de rango',
            RegisterService::formatReportDateRangeSubtitle($payload['startDate'], $payload['endDate']),
            'reports/analytics/pdf/valores_criticos',
            $payload
        );
    }

    public function valoresCriticosExcel()
    {
        $payload = $this->collectValoresCriticosPayload();
        $rows    = [];
        foreach ($payload['rows'] as $row) {
            $rows[] = [
                $row['ingreso'], $row['numero_orden'] ?: $row['registro_id'], $row['paciente'], $row['grupo'],
                $row['prueba'], $row['parametro'], $row['valor'], $row['unidad'],
                $row['valor_min'], $row['valor_max'],
                self::estadoValorLabel((string) $row['estado']),
            ];
        }
        $this->streamCsv(
            'valores_criticos',
            ['Fecha', 'Orden', 'Paciente', 'Grupo', 'Prueba', 'Parámetro', 'Resultado', 'Unidad', 'Ref. mín', 'Ref. máx', 'Estado'],
            $rows
        );
    }

    // ==================================================================
    // 4. TIEMPO DE ENTREGA (TAT)
    // ==================================================================

    /** @return array<string, mixed> */
    private function collectTiempoEntregaPayload(): array
    {
        [$start, $end] = $this->getRangoFechas('monday this week');
        $slaHoras = max(1, (int) ($this->request->getGet('sla') ?? 24));
        $grupoId  = (int) ($this->request->getGet('grupo_id') ?? 0);
        $pruebaId = (int) ($this->request->getGet('prueba_id') ?? 0);

        // Filtro por grupo/prueba: se traduce a lista de IDs de prueba (CSV de la orden)
        $pruebaIds = [];
        if ($pruebaId > 0) {
            $pruebaIds = [$pruebaId];
        } elseif ($grupoId > 0) {
            foreach ($this->analytics->getPruebasCatalogoMap() as $id => $info) {
                if ((int) ($info['grupo_id'] ?? 0) === $grupoId) {
                    $pruebaIds[] = $id;
                }
            }
            $pruebaIds = $pruebaIds !== [] ? $pruebaIds : [-1];
        }

        $rows    = $this->analytics->getTiempoEntrega($start, $end, $pruebaIds);
        $totales = $this->analytics->getTiempoEntregaResumen($start, $end, $slaHoras, $pruebaIds);

        return [
            'startDate' => $start,
            'endDate'   => $end,
            'slaHoras'  => $slaHoras,
            'grupoId'   => $grupoId,
            'pruebaId'  => $pruebaId,
            'rows'      => $rows,
            'totales'   => $totales,
        ];
    }

    public function tiempoEntrega()
    {
        $payload = $this->collectTiempoEntregaPayload();

        return view('reports/analytics/tiempo_entrega', array_merge(
            $this->commonViewData('Tiempo de entrega (TAT)', $payload['startDate'], $payload['endDate']),
            $payload,
            [
                'grupos'  => $this->analytics->getGruposAnalisis(),
                'pruebas' => $this->analytics->getPruebasCatalogoListado(),
            ]
        ));
    }

    public function tiempoEntregaPdf()
    {
        $payload = $this->collectTiempoEntregaPayload();
        ReportPdfDocument::download(
            $this->safePdfFilename('tiempo_entrega_tat'),
            'Tiempo de entrega (TAT)',
            RegisterService::formatReportDateRangeSubtitle($payload['startDate'], $payload['endDate']) . ' · SLA ' . $payload['slaHoras'] . ' h',
            'reports/analytics/pdf/tiempo_entrega',
            $payload
        );
    }

    public function tiempoEntregaExcel()
    {
        $payload = $this->collectTiempoEntregaPayload();
        $rows    = [];
        foreach ($payload['rows'] as $row) {
            $rows[] = [
                $row['numero_orden'] ?: $row['registro_id'], $row['paciente'], $row['ingreso'],
                $row['fecha_resultado'] ?? '', $row['fecha_validacion'] ?? '',
                $row['horas_resultado'] ?? '', $row['horas_validacion'] ?? '',
                $row['horas_resultado'] !== null
                    ? ((float) $row['horas_resultado'] <= $payload['slaHoras'] ? 'Dentro de SLA' : 'Retraso')
                    : 'Sin resultado',
            ];
        }
        $this->streamCsv(
            'tiempo_entrega_tat',
            ['Orden', 'Paciente', 'Recepción', 'Primer resultado', 'Primera validación', 'Horas a resultado', 'Horas a validación', 'SLA'],
            $rows
        );
    }

    // ==================================================================
    // 5. PRODUCTIVIDAD POR USUARIO
    // ==================================================================

    /** @return array<string, mixed> */
    private function collectProductividadPayload(): array
    {
        [$start, $end] = $this->getRangoFechas('monday this week');
        $personId = (int) ($this->request->getGet('person_id') ?? 0);

        $rows = $this->analytics->getProductividadUsuarios($start, $end, $personId);

        $totales = [
            'recepciones'         => array_sum(array_column($rows, 'recepciones')),
            'resultados_cargados' => array_sum(array_column($rows, 'resultados_cargados')),
            'modificaciones'      => array_sum(array_column($rows, 'modificaciones')),
            'validaciones'        => array_sum(array_column($rows, 'validaciones')),
            'impresiones'         => array_sum(array_column($rows, 'impresiones')),
            'envios_whatsapp'     => array_sum(array_column($rows, 'envios_whatsapp')),
            'usuarios'            => count($rows),
        ];

        return [
            'startDate' => $start,
            'endDate'   => $end,
            'personId'  => $personId,
            'rows'      => $rows,
            'totales'   => $totales,
        ];
    }

    public function productividadUsuarios()
    {
        $payload = $this->collectProductividadPayload();

        return view('reports/analytics/productividad_usuarios', array_merge(
            $this->commonViewData('Productividad por usuario', $payload['startDate'], $payload['endDate']),
            $payload,
            ['usuarios' => $this->analytics->getUsuariosEmpleados()]
        ));
    }

    public function productividadUsuariosPdf()
    {
        $payload = $this->collectProductividadPayload();
        ReportPdfDocument::download(
            $this->safePdfFilename('productividad_usuarios'),
            'Productividad por usuario',
            RegisterService::formatReportDateRangeSubtitle($payload['startDate'], $payload['endDate']),
            'reports/analytics/pdf/productividad_usuarios',
            $payload
        );
    }

    public function productividadUsuariosExcel()
    {
        $payload = $this->collectProductividadPayload();
        $rows    = [];
        foreach ($payload['rows'] as $row) {
            $rows[] = [
                $row['ranking'], $row['usuario'], $row['recepciones'], $row['resultados_cargados'],
                $row['modificaciones'], $row['validaciones'], $row['impresiones'], $row['envios_whatsapp'],
                $row['total_actividad'],
            ];
        }
        $this->streamCsv(
            'productividad_usuarios',
            ['Ranking', 'Usuario', 'Recepciones', 'Resultados cargados', 'Modificaciones', 'Validaciones', 'Impresiones', 'Envíos WhatsApp', 'Total actividad'],
            $rows
        );
    }

    // ==================================================================
    // 6. RESULTADOS CORREGIDOS (AUDITORÍA)
    // ==================================================================

    /** @return array<string, mixed> */
    private function collectCorregidosPayload(): array
    {
        [$start, $end] = $this->getRangoFechas('-1 month');
        $personId = (int) ($this->request->getGet('person_id') ?? 0);

        $rows = $this->analytics->getResultadosCorregidos($start, $end, $personId);

        $porUsuario = [];
        foreach ($rows as $row) {
            $porUsuario[(string) $row['usuario']] = ($porUsuario[(string) $row['usuario']] ?? 0) + 1;
        }
        arsort($porUsuario);

        return [
            'startDate'  => $start,
            'endDate'    => $end,
            'personId'   => $personId,
            'rows'       => $rows,
            'porUsuario' => $porUsuario,
            'total'      => count($rows),
        ];
    }

    public function resultadosCorregidos()
    {
        $payload = $this->collectCorregidosPayload();

        return view('reports/analytics/resultados_corregidos', array_merge(
            $this->commonViewData('Resultados corregidos (auditoría)', $payload['startDate'], $payload['endDate']),
            $payload,
            ['usuarios' => $this->analytics->getUsuariosEmpleados()]
        ));
    }

    public function resultadosCorregidosPdf()
    {
        $payload = $this->collectCorregidosPayload();
        ReportPdfDocument::download(
            $this->safePdfFilename('resultados_corregidos'),
            'Resultados corregidos (auditoría)',
            RegisterService::formatReportDateRangeSubtitle($payload['startDate'], $payload['endDate']),
            'reports/analytics/pdf/resultados_corregidos',
            $payload
        );
    }

    public function resultadosCorregidosExcel()
    {
        $payload = $this->collectCorregidosPayload();
        $rows    = [];
        foreach ($payload['rows'] as $row) {
            $rows[] = [
                $row['fecha'], $row['numero_orden'] ?: $row['registro_id'], $row['paciente'],
                $row['prueba'], $row['campo'], $row['valor_anterior'], $row['valor_nuevo'], $row['usuario'],
            ];
        }
        $this->streamCsv(
            'resultados_corregidos',
            ['Fecha', 'Orden', 'Paciente', 'Prueba', 'Campo', 'Resultado original', 'Resultado nuevo', 'Usuario'],
            $rows
        );
    }

    // ==================================================================
    // 7. RESULTADOS PENDIENTES DE VALIDAR
    // ==================================================================

    /** @return array<string, mixed> */
    private function collectPendientesPayload(): array
    {
        [$start, $end] = $this->getRangoFechas('-1 month');

        $resultado = $this->analytics->getPendientesValidacion($start, $end);
        $rows      = $resultado['rows'];

        // Indicador por área a partir del detalle (CSV de pruebas → grupo del catálogo)
        $catalogo = $this->analytics->getPruebasCatalogoMap();
        $porArea  = [];
        foreach ($rows as $row) {
            $areas = [];
            foreach (explode(',', (string) ($row['pruebas'] ?? '')) as $idStr) {
                $id = (int) trim($idStr);
                if ($id > 0 && ($catalogo[$id]['grupo'] ?? '') !== '') {
                    $areas[$catalogo[$id]['grupo']] = true;
                }
            }
            foreach (array_keys($areas) as $area) {
                $porArea[$area] = ($porArea[$area] ?? 0) + 1;
            }
        }
        arsort($porArea);

        return [
            'startDate'  => $start,
            'endDate'    => $end,
            'rows'       => $rows,
            'porUsuario' => $resultado['porUsuario'],
            'porArea'    => $porArea,
            'total'      => $resultado['total'],
        ];
    }

    public function pendientesValidacion()
    {
        $payload = $this->collectPendientesPayload();

        return view('reports/analytics/pendientes_validacion', array_merge(
            $this->commonViewData('Resultados pendientes de validar', $payload['startDate'], $payload['endDate']),
            $payload
        ));
    }

    public function pendientesValidacionPdf()
    {
        $payload = $this->collectPendientesPayload();
        ReportPdfDocument::download(
            $this->safePdfFilename('pendientes_validacion'),
            'Resultados pendientes de validar',
            RegisterService::formatReportDateRangeSubtitle($payload['startDate'], $payload['endDate']),
            'reports/analytics/pdf/pendientes_validacion',
            $payload
        );
    }

    public function pendientesValidacionExcel()
    {
        $payload = $this->collectPendientesPayload();
        $rows    = [];
        foreach ($payload['rows'] as $row) {
            $rows[] = [
                $row['numero_orden'] ?: $row['registro_id'], $row['paciente'], $row['ingreso'],
                $row['fecha_resultado'] ?? '', $row['usuario_cargo'],
                number_format((float) $row['horas_pendiente'], 1, '.', ''),
            ];
        }
        $this->streamCsv(
            'pendientes_validacion',
            ['Orden', 'Paciente', 'Fecha recepción', 'Primer resultado', 'Usuario que cargó', 'Horas pendiente'],
            $rows
        );
    }

    // ==================================================================
    // 8. CONSUMO DE INSUMOS POR PRUEBA
    // ==================================================================

    /** @return array<string, mixed> */
    private function collectConsumoPayload(): array
    {
        [$start, $end] = $this->getRangoFechas('-1 month');

        $porPrueba   = $this->analytics->getConsumoPorPrueba($start, $end);
        $porReactivo = $this->analytics->getConsumoPorReactivo($start, $end);
        $porDia      = $this->analytics->getConsumoPorPeriodo($start, $end, 'dia');
        $porMes      = $this->analytics->getConsumoPorPeriodo($start, $end, 'mes');

        return [
            'startDate'   => $start,
            'endDate'     => $end,
            'porPrueba'   => $porPrueba,
            'porReactivo' => $porReactivo,
            'porDia'      => $porDia,
            'porMes'      => $porMes,
            'totales'     => [
                'total_consumido'  => array_sum(array_map(static fn ($r) => (float) $r['cantidad_total'], $porReactivo)),
                'reactivos'        => count($porReactivo),
                'combinaciones'    => count($porPrueba),
            ],
        ];
    }

    public function consumoInsumos()
    {
        $payload = $this->collectConsumoPayload();

        return view('reports/analytics/consumo_insumos', array_merge(
            $this->commonViewData('Consumo de insumos por prueba', $payload['startDate'], $payload['endDate']),
            $payload
        ));
    }

    public function consumoInsumosPdf()
    {
        $payload = $this->collectConsumoPayload();
        ReportPdfDocument::download(
            $this->safePdfFilename('consumo_insumos'),
            'Consumo de insumos por prueba',
            RegisterService::formatReportDateRangeSubtitle($payload['startDate'], $payload['endDate']),
            'reports/analytics/pdf/consumo_insumos',
            $payload
        );
    }

    public function consumoInsumosExcel()
    {
        $payload = $this->collectConsumoPayload();
        $rows    = [['CONSUMO POR PRUEBA Y REACTIVO']];
        foreach ($payload['porPrueba'] as $row) {
            $rows[] = [$row['prueba'], $row['reactivo'], $row['unidad'], $row['eventos'], $row['cantidad_total']];
        }
        $rows[] = [];
        $rows[] = ['CONSUMO POR REACTIVO (salidas de inventario)'];
        foreach ($payload['porReactivo'] as $row) {
            $rows[] = ['', $row['reactivo'], $row['unidad'], $row['movimientos'], $row['cantidad_total']];
        }
        $this->streamCsv(
            'consumo_insumos',
            ['Prueba', 'Reactivo', 'Unidad', 'Eventos/Movimientos', 'Cantidad consumida'],
            $rows
        );
    }

    // ==================================================================
    // 9. PROYECCIÓN DE AGOTAMIENTO DE INSUMOS
    // ==================================================================

    /** @return array<string, mixed> */
    private function collectProyeccionPayload(): array
    {
        $ventana = max(7, min(365, (int) ($this->request->getGet('ventana') ?? 30)));
        $rows    = $this->analytics->getProyeccionInsumos($ventana);

        $porEstado = ['critico' => 0, 'advertencia' => 0, 'normal' => 0, 'sin_consumo' => 0, 'sin_stock' => 0];
        foreach ($rows as $row) {
            $estado = (string) ($row['estado'] ?? '');
            if (isset($porEstado[$estado])) {
                $porEstado[$estado]++;
            }
        }

        return [
            'ventana'   => $ventana,
            'rows'      => $rows,
            'porEstado' => $porEstado,
            'total'     => count($rows),
        ];
    }

    public function proyeccionInsumos()
    {
        $payload = $this->collectProyeccionPayload();
        $hoy     = RegisterService::todayForReport();

        return view('reports/analytics/proyeccion_insumos', array_merge(
            $this->commonViewData('Proyección de agotamiento de insumos', $hoy, $hoy),
            $payload,
            ['subtitle' => 'Consumo promedio de los últimos ' . $payload['ventana'] . ' días']
        ));
    }

    public function proyeccionInsumosPdf()
    {
        $payload = $this->collectProyeccionPayload();
        ReportPdfDocument::download(
            $this->safePdfFilename('proyeccion_insumos'),
            'Proyección de agotamiento de insumos',
            'Consumo promedio de los últimos ' . $payload['ventana'] . ' días',
            'reports/analytics/pdf/proyeccion_insumos',
            $payload
        );
    }

    public function proyeccionInsumosExcel()
    {
        $payload = $this->collectProyeccionPayload();
        $rows    = [];
        foreach ($payload['rows'] as $row) {
            $rows[] = [
                $row['nombre'], $row['unidad'], $row['stock_actual'], $row['stock_minimo'],
                $row['consumo_promedio_dia'], $row['dias_restantes'] ?? '',
                $row['fecha_agotamiento'] ?? '', ucfirst(str_replace('_', ' ', (string) $row['estado'])),
            ];
        }
        $this->streamCsv(
            'proyeccion_insumos',
            ['Reactivo', 'Unidad', 'Stock actual', 'Stock mínimo', 'Consumo promedio/día', 'Días restantes', 'Fecha estimada agotamiento', 'Estado'],
            $rows
        );
    }

    // ==================================================================
    // 10. COMPARATIVO MENSUAL DE INGRESOS Y PACIENTES
    // ==================================================================

    /** @return array<string, mixed> */
    private function collectComparativoPayload(): array
    {
        $defaultStart = RegisterService::reportDateFromModifier('first day of january last year');
        $start = trim((string) ($this->request->getGet('start') ?? '')) ?: $defaultStart;
        $end   = trim((string) ($this->request->getGet('end') ?? '')) ?: RegisterService::todayForReport();

        $rows = $this->analytics->getComparativoMensual($start, $end);

        $totales = [
            'meses'     => count($rows),
            'ordenes'   => array_sum(array_map(static fn ($r) => (int) $r['ordenes'], $rows)),
            'pacientes' => array_sum(array_map(static fn ($r) => (int) $r['pacientes'], $rows)),
            'pruebas'   => array_sum(array_map(static fn ($r) => (int) $r['pruebas'], $rows)),
            'facturado' => array_sum(array_map(static fn ($r) => (float) $r['facturado'], $rows)),
            'cobrado'   => array_sum(array_map(static fn ($r) => (float) $r['cobrado'], $rows)),
        ];

        return [
            'startDate' => $start,
            'endDate'   => $end,
            'rows'      => $rows,
            'totales'   => $totales,
        ];
    }

    public function comparativoMensual()
    {
        $payload = $this->collectComparativoPayload();

        return view('reports/analytics/comparativo_mensual', array_merge(
            $this->commonViewData('Comparativo mensual de ingresos y pacientes', $payload['startDate'], $payload['endDate']),
            $payload
        ));
    }

    public function comparativoMensualPdf()
    {
        $payload = $this->collectComparativoPayload();
        ReportPdfDocument::download(
            $this->safePdfFilename('comparativo_mensual'),
            'Comparativo mensual de ingresos y pacientes',
            RegisterService::formatReportDateRangeSubtitle($payload['startDate'], $payload['endDate']),
            'reports/analytics/pdf/comparativo_mensual',
            $payload
        );
    }

    public function comparativoMensualExcel()
    {
        $payload = $this->collectComparativoPayload();
        $rows    = [];
        foreach ($payload['rows'] as $row) {
            $rows[] = [
                $row['mes'], $row['pacientes'], $row['ordenes'], $row['pruebas'],
                number_format((float) $row['facturado'], 2, '.', ''),
                number_format((float) $row['cobrado'], 2, '.', ''),
                $row['var_mes_facturado'] !== null ? $row['var_mes_facturado'] . '%' : '',
                $row['var_anio_facturado'] !== null ? $row['var_anio_facturado'] . '%' : '',
                number_format((float) $row['acumulado_facturado'], 2, '.', ''),
            ];
        }
        $rows[] = [];
        $rows[] = [
            'TOTALES', $payload['totales']['pacientes'], $payload['totales']['ordenes'], $payload['totales']['pruebas'],
            number_format((float) $payload['totales']['facturado'], 2, '.', ''),
            number_format((float) $payload['totales']['cobrado'], 2, '.', ''), '', '', '',
        ];
        $this->streamCsv(
            'comparativo_mensual',
            ['Mes', 'Pacientes', 'Órdenes', 'Pruebas', 'Facturado', 'Cobrado', 'Var. vs mes anterior', 'Var. vs año anterior', 'Facturado acumulado'],
            $rows
        );
    }
}
