<?php

namespace App\Services;

use App\Models\ReportAnalyticsModel;

/**
 * Enriquece el payload de estadísticas de laboratorio con indicadores SNIS Bolivia.
 * Solo agrega datos; no altera la lógica base de conteos existente.
 */
class EstadisticasLaboratorioSnisService
{
  private ReportAnalyticsModel $analytics;

  public function __construct(?ReportAnalyticsModel $analytics = null)
  {
    $this->analytics = $analytics ?? model(ReportAnalyticsModel::class);
  }

  /**
   * @param array{
   *   resumen: array<string,mixed>,
   *   porGeneroOrdenes: array<string,int>,
   *   porGeneroPacientes: array<string,int>,
   *   poblacionGrupoRows: list<array<string,mixed>>,
   *   porPruebaRows: list<array<string,mixed>>
   * } $basePayload
   * @param list<array<string,mixed>> $rowsSolicitadas
   * @param list<array<string,mixed>> $rowsProcesadas
   *
   * @return array<string, mixed>
   */
  public function enrich(
    array $basePayload,
    string $startDate,
    string $endDate,
    array $rowsSolicitadas,
    array $rowsProcesadas
  ): array {
    $resumen       = $basePayload['resumen'] ?? [];
    $porPruebaRows = $basePayload['porPruebaRows'] ?? [];
    $limitaciones  = [];

    $ordenesSol   = (int) ($resumen['ordenes_solicitadas'] ?? 0);
    $ordenesProc  = (int) ($resumen['ordenes_procesadas'] ?? 0);
    $pruebasSol   = (int) ($resumen['pruebas_solicitadas'] ?? 0);
    $pruebasProc  = (int) ($resumen['pruebas_procesadas'] ?? 0);
    $pacientes    = (int) ($resumen['pacientes_distintos'] ?? 0);
    $diasPeriodo  = max(1, $this->diasEnPeriodo($startDate, $endDate));
    $tiposPrueba  = count($porPruebaRows);

    $pctProcesamiento = $pruebasSol > 0 ? round($pruebasProc * 100 / $pruebasSol, 2) : null;
    $promPruebasOrden = $ordenesSol > 0 ? round($pruebasSol / $ordenesSol, 2) : null;
    $promPruebasPac   = $pacientes > 0 ? round($pruebasSol / $pacientes, 2) : null;
    $promDiario       = round($pruebasSol / $diasPeriodo, 2);

    $evolucion = $this->buildEvolucionDiaria($rowsSolicitadas, $rowsProcesadas);
    $porCategoria = $this->buildPorCategoria($porPruebaRows);
    $top10 = array_slice($porPruebaRows, 0, 10);

    $pendientesData = $this->analytics->getPendientesValidacion($startDate, $endDate, 1);
    $ordenesPend    = (int) ($pendientesData['total'] ?? 0);

    $tatResumen = $this->analytics->getTiempoEntregaResumen($startDate, $endDate, 24);
    $tatMuestra = $this->analytics->getTiemposMuestraResumen($startDate, $endDate);

    $porMedico      = $this->buildProduccionPorMedico($rowsSolicitadas);
    $porInstitucion = $this->buildProduccionPorInstitucion($rowsSolicitadas);
    $tieneInst      = $this->tieneDatosInstitucion($porInstitucion);

    if (! $tieneInst) {
      $limitaciones[] = [
        'clave'    => 'establecimiento_remitente',
        'mensaje'  => 'No hay campo de establecimiento remitente en la orden. Se muestra la procedencia/institución del paciente (customers.institucion) cuando está registrada.',
      ];
    }

    $micro = $this->analytics->getIndicadoresMicrobiologia($startDate, $endDate);
    if ($micro === null) {
      $limitaciones[] = [
        'clave'   => 'positividad_microbiologia',
        'mensaje' => 'Sin resultados en grupos de microbiología en el período, o no hay grupos configurados con ese nombre.',
      ];
    }

    $limitaciones[] = [
      'clave'   => 'muestras_rechazadas',
      'mensaje' => 'El módulo de muestras no registra estado «rechazada» ni motivo de rechazo; solo flujo Tomada → Recibida → Procesada → Validada.',
    ];

    if ($tatResumen['promedio_validacion'] === null && $tatResumen['promedio_resultado'] === null) {
      $limitaciones[] = [
        'clave'   => 'tat_validacion',
        'mensaje' => 'Tiempos de validación/entrega dependen de eventos en auditoría o notificaciones de entrega; pueden estar incompletos en órdenes antiguas.',
      ];
    }

    $periodoAnterior = $this->calcularPeriodoAnterior($startDate, $endDate);
    $comparacion     = $this->buildComparacionPeriodo($periodoAnterior['start'], $periodoAnterior['end'], $resumen);

    $kpis = [
      'pacientes_atendidos'       => $pacientes,
      'ordenes_solicitadas'       => $ordenesSol,
      'ordenes_procesadas'        => $ordenesProc,
      'pruebas_solicitadas'       => $pruebasSol,
      'pruebas_procesadas'        => $pruebasProc,
      'porcentaje_procesamiento'  => $pctProcesamiento,
      'promedio_pruebas_orden'    => $promPruebasOrden,
      'promedio_pruebas_paciente' => $promPruebasPac,
      'promedio_diario_pruebas'   => $promDiario,
      'tipos_prueba_distintos'    => $tiposPrueba,
      'dias_periodo'              => $diasPeriodo,
    ];

    $indicadores = [
      'por_categoria'          => $porCategoria,
      'por_medico'             => $porMedico,
      'por_institucion'        => $tieneInst ? $porInstitucion : [],
      'tat'                    => $tatResumen,
      'tat_muestra'            => $tatMuestra,
      'ordenes_pendientes'     => $ordenesPend,
      'ordenes_anuladas'       => $this->analytics->countOrdenesAnuladasEnPeriodo($startDate, $endDate),
      'resultados_corregidos'  => $this->analytics->countResultadosCorregidosEnPeriodo($startDate, $endDate),
      'resultados_validados'   => $this->analytics->countResultadosValidadosEnPeriodo($startDate, $endDate),
      'microbiologia'          => $micro,
      'procesadas_vs_pendientes' => [
        'procesadas'  => $pruebasProc,
        'pendientes'  => max(0, $pruebasSol - $pruebasProc),
        'solicitadas' => $pruebasSol,
      ],
    ];

    $resumenEjecutivo = $this->buildResumenEjecutivo(
      $basePayload,
      $kpis,
      $porCategoria,
      $top10,
      $comparacion
    );

    return [
      'snis' => [
        'kpis'              => $kpis,
        'charts'            => [
          'evolucion_diaria'        => $evolucion,
          'top10'                   => $top10,
          'por_categoria'           => $porCategoria,
          'sexo_pacientes'          => $basePayload['porGeneroPacientes'] ?? [],
          'sexo_ordenes'            => $basePayload['porGeneroOrdenes'] ?? [],
          'grupos_etarios'          => $basePayload['poblacionGrupoRows'] ?? [],
          'procesadas_pendientes'   => $indicadores['procesadas_vs_pendientes'],
          'comparacion'             => $comparacion,
        ],
        'indicadores'       => $indicadores,
        'resumen_ejecutivo' => $resumenEjecutivo,
        'limitaciones'      => $limitaciones,
        'periodo_anterior'  => $periodoAnterior,
      ],
    ];
  }

  private function diasEnPeriodo(string $startDate, string $endDate): int
  {
    try {
      $ini = new \DateTimeImmutable($startDate);
      $fin = new \DateTimeImmutable($endDate);

      return (int) $ini->diff($fin)->days + 1;
    } catch (\Throwable $e) {
      return 1;
    }
  }

  /**
   * @param list<array<string,mixed>> $rowsSolicitadas
   * @param list<array<string,mixed>> $rowsProcesadas
   *
   * @return array{labels: list<string>, ordenes_solicitadas: list<int>, ordenes_procesadas: list<int>, pruebas_solicitadas: list<int>}
   */
  private function buildEvolucionDiaria(array $rowsSolicitadas, array $rowsProcesadas): array
  {
    $registerService = new RegisterService();
    $porDiaSol       = [];
    $porDiaProc      = [];
    $pruebasPorDia   = [];

    foreach ($rowsSolicitadas as $row) {
      $dia = $this->fechaLab($row['ingreso'] ?? '');
      if ($dia === '') {
        continue;
      }
      $porDiaSol[$dia] = ($porDiaSol[$dia] ?? 0) + 1;
      $pruebasPorDia[$dia] = ($pruebasPorDia[$dia] ?? 0)
        + count($registerService->extractPrianacategoriaIdsFromRegistroPruebas((string) ($row['pruebas'] ?? '')));
    }

    foreach ($rowsProcesadas as $row) {
      $dia = $this->fechaLab($row['ingreso'] ?? '');
      if ($dia === '') {
        continue;
      }
      $porDiaProc[$dia] = ($porDiaProc[$dia] ?? 0) + 1;
    }

    $labels = array_values(array_unique(array_merge(array_keys($porDiaSol), array_keys($porDiaProc))));
    sort($labels);

    $ordenesSol = [];
    $ordenesProc = [];
    $pruebasSol = [];
    foreach ($labels as $dia) {
      $ordenesSol[]  = (int) ($porDiaSol[$dia] ?? 0);
      $ordenesProc[] = (int) ($porDiaProc[$dia] ?? 0);
      $pruebasSol[]  = (int) ($pruebasPorDia[$dia] ?? 0);
    }

    return [
      'labels'              => $labels,
      'ordenes_solicitadas' => $ordenesSol,
      'ordenes_procesadas'  => $ordenesProc,
      'pruebas_solicitadas' => $pruebasSol,
    ];
  }

  /**
   * @param list<array<string,mixed>> $porPruebaRows
   *
   * @return list<array{categoria:string, solicitadas:int, procesadas:int}>
   */
  private function buildPorCategoria(array $porPruebaRows): array
  {
    $map = [];
    foreach ($porPruebaRows as $row) {
      $cat = trim((string) ($row['categoria'] ?? ''));
      $key = $cat !== '' ? $cat : 'Sin categoría';
      if (! isset($map[$key])) {
        $map[$key] = ['categoria' => $key, 'solicitadas' => 0, 'procesadas' => 0];
      }
      $map[$key]['solicitadas'] += (int) ($row['ordenes_con_prueba'] ?? 0);
      $map[$key]['procesadas']  += (int) ($row['ordenes_procesadas'] ?? 0);
    }

    $rows = array_values($map);
    usort($rows, static fn (array $a, array $b): int => ($b['solicitadas'] ?? 0) <=> ($a['solicitadas'] ?? 0));

    return $rows;
  }

  /**
   * @param list<array{institucion:string, ordenes:int}> $porInstitucion
   */
  private function tieneDatosInstitucion(array $porInstitucion): bool
  {
    foreach ($porInstitucion as $row) {
      $inst = trim((string) ($row['institucion'] ?? ''));
      if ($inst !== '' && $inst !== 'Sin procedencia registrada' && (int) ($row['ordenes'] ?? 0) > 0) {
        return true;
      }
    }

    return false;
  }

  /** @return array{start: string, end: string, dias: int} */
  private function calcularPeriodoAnterior(string $startDate, string $endDate): array
  {
    try {
      $ini = new \DateTimeImmutable($startDate);
      $fin = new \DateTimeImmutable($endDate);
      $dias = (int) $ini->diff($fin)->days + 1;
      $prevFin = $ini->modify('-1 day');
      $prevIni = $prevFin->modify('-' . ($dias - 1) . ' days');

      return [
        'start' => $prevIni->format('Y-m-d'),
        'end'   => $prevFin->format('Y-m-d'),
        'dias'  => $dias,
      ];
    } catch (\Throwable $e) {
      return ['start' => $startDate, 'end' => $endDate, 'dias' => 1];
    }
  }

  /**
   * @param array<string,mixed> $resumenActual
   *
   * @return array<string, mixed>
   */
  private function buildComparacionPeriodo(string $prevStart, string $prevEnd, array $resumenActual): array
  {
    $analytics = $this->analytics;
    $prevRows  = $analytics->getRegistrosSolicitudesEnPeriodo($prevStart, $prevEnd);

    if ($prevRows === []) {
      return ['disponible' => false, 'periodo' => ['start' => $prevStart, 'end' => $prevEnd]];
    }

    $registerService = new RegisterService();
    $prevOrdenes     = count($prevRows);
    $prevPruebas     = 0;
    foreach ($prevRows as $row) {
      $prevPruebas += count($registerService->extractPrianacategoriaIdsFromRegistroPruebas((string) ($row['pruebas'] ?? '')));
    }

    $actualOrdenes = (int) ($resumenActual['ordenes_solicitadas'] ?? 0);
    $actualPruebas = (int) ($resumenActual['pruebas_solicitadas'] ?? 0);

    return [
      'disponible'       => true,
      'periodo'          => ['start' => $prevStart, 'end' => $prevEnd],
      'ordenes_anterior' => $prevOrdenes,
      'ordenes_actual'   => $actualOrdenes,
      'ordenes_var_pct'  => $this->variacionPct($actualOrdenes, $prevOrdenes),
      'pruebas_anterior' => $prevPruebas,
      'pruebas_actual'   => $actualPruebas,
      'pruebas_var_pct'  => $this->variacionPct($actualPruebas, $prevPruebas),
    ];
  }

  private function variacionPct(int $actual, int $anterior): ?float
  {
    if ($anterior < 1) {
      return $actual > 0 ? 100.0 : null;
    }

    return round(($actual - $anterior) * 100 / $anterior, 2);
  }

  /**
   * @param array<string,mixed> $basePayload
   * @param array<string,mixed> $kpis
   * @param list<array<string,mixed>> $porCategoria
   * @param list<array<string,mixed>> $top10
   * @param array<string,mixed> $comparacion
   *
   * @return array{bullets: list<string>, productividad: string}
   */
  private function buildResumenEjecutivo(
    array $basePayload,
    array $kpis,
    array $porCategoria,
    array $top10,
    array $comparacion
  ): array {
    $areaMayor = $porCategoria[0]['categoria'] ?? '—';
    $pruebaTop = $top10[0]['prueba'] ?? '—';

    $genPac = $basePayload['porGeneroPacientes'] ?? [];
    $sexoPred = 'No indicado';
    $m = (int) ($genPac['1'] ?? 0);
    $f = (int) ($genPac['2'] ?? 0);
    if ($m > $f) {
      $sexoPred = 'Masculino';
    } elseif ($f > $m) {
      $sexoPred = 'Femenino';
    }

    $grupos = $basePayload['poblacionGrupoRows'] ?? [];
    $grupoPred = '—';
    $maxGrupo  = 0;
    foreach ($grupos as $g) {
      $cnt = (int) ($g['ordenes'] ?? 0);
      if ($cnt > $maxGrupo) {
        $maxGrupo  = $cnt;
        $grupoPred = (string) ($g['nombre'] ?? '—');
      }
    }

    $pct = $kpis['porcentaje_procesamiento'] ?? null;
    $productividad = $pct !== null
      ? "Tasa de procesamiento del {$pct}% sobre pruebas solicitadas."
      : 'Sin pruebas solicitadas en el período.';

    $bullets = [
      $productividad,
      "Área con mayor producción: {$areaMayor}.",
      "Prueba más solicitada: {$pruebaTop}.",
      "Sexo predominante (pacientes): {$sexoPred}.",
      "Grupo etario predominante (órdenes con resultado): {$grupoPred}.",
      'Promedio de pruebas por orden: ' . ($kpis['promedio_pruebas_orden'] ?? '—') . '.',
      'Promedio diario de pruebas: ' . ($kpis['promedio_diario_pruebas'] ?? '—') . '.',
    ];

    if (! empty($comparacion['disponible'])) {
      $var = $comparacion['pruebas_var_pct'] ?? null;
      if ($var !== null) {
        $signo = $var >= 0 ? '+' : '';
        $bullets[] = "Variación de pruebas solicitadas vs período anterior ({$comparacion['periodo']['start']} – {$comparacion['periodo']['end']}): {$signo}{$var}%.";
      }
    }

    return [
      'productividad' => $productividad,
      'bullets'       => $bullets,
      'area_mayor'    => $areaMayor,
      'prueba_top'    => $pruebaTop,
      'sexo_pred'     => $sexoPred,
      'grupo_pred'    => $grupoPred,
    ];
  }

  private function fechaLab(string $ingreso): string
  {
    $ingreso = trim($ingreso);
    if ($ingreso === '') {
      return '';
    }
    try {
      return (new \DateTimeImmutable($ingreso))->format('Y-m-d');
    } catch (\Throwable $e) {
      return substr($ingreso, 0, 10);
    }
  }

  /**
   * @param list<array<string,mixed>> $rowsSolicitadas
   *
   * @return list<array{doctor_id:int, doctor:string, ordenes:int, pruebas:int}>
   */
  private function buildProduccionPorMedico(array $rowsSolicitadas): array
  {
    $registerService = new RegisterService();
    $porMedico       = [];

    foreach ($rowsSolicitadas as $row) {
      $did = (int) ($row['doctor_id'] ?? 0);
      $key = $did > 0 ? (string) $did : '_sin';
      if (! isset($porMedico[$key])) {
        $porMedico[$key] = [
          'doctor_id' => $did,
          'doctor'    => trim((string) ($row['doctor'] ?? '')) !== '' ? trim((string) $row['doctor']) : 'Sin médico',
          'ordenes'   => 0,
          'pruebas'   => 0,
        ];
      }
      $porMedico[$key]['ordenes']++;
      $porMedico[$key]['pruebas'] += count($registerService->extractPrianacategoriaIdsFromRegistroPruebas((string) ($row['pruebas'] ?? '')));
    }

    $rows = array_values($porMedico);
    usort($rows, static fn (array $a, array $b): int => ($b['ordenes'] ?? 0) <=> ($a['ordenes'] ?? 0));

    return array_slice($rows, 0, 30);
  }

  /**
   * @param list<array<string,mixed>> $rowsSolicitadas
   *
   * @return list<array{institucion:string, ordenes:int, pruebas:int}>
   */
  private function buildProduccionPorInstitucion(array $rowsSolicitadas): array
  {
    $registerService = new RegisterService();
    $porInst         = [];

    foreach ($rowsSolicitadas as $row) {
      $inst = trim((string) ($row['institucion'] ?? ''));
      $key  = $inst !== '' ? $inst : '_sin';
      if (! isset($porInst[$key])) {
        $porInst[$key] = [
          'institucion' => $inst !== '' ? $inst : 'Sin procedencia registrada',
          'ordenes'     => 0,
          'pruebas'     => 0,
        ];
      }
      $porInst[$key]['ordenes']++;
      $porInst[$key]['pruebas'] += count($registerService->extractPrianacategoriaIdsFromRegistroPruebas((string) ($row['pruebas'] ?? '')));
    }

    $rows = array_values($porInst);
    usort($rows, static fn (array $a, array $b): int => ($b['ordenes'] ?? 0) <=> ($a['ordenes'] ?? 0));

    return array_slice($rows, 0, 30);
  }
}
