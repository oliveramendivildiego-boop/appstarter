<?php

namespace App\Services;

/**
 * Prepara series longitudinales para gráficas clínicas rápidas.
 */
class PatientResultChartService
{
    private $insights;

    public function __construct(?DoctorClinicalInsightService $insights = null)
    {
        $this->insights = $insights ?? new DoctorClinicalInsightService();
    }

    /**
     * @param list<array<string, mixed>> $series
     * @return array<string, mixed>
     */
    public function buildPayload(array $series, string $range = 'all', string $dateFrom = '', string $dateTo = ''): array
    {
        $labelsByKey = [];
        $prepared = [];

        foreach ($series as $serieConfig) {
            $rows = $this->filterRows((array) ($serieConfig['rows'] ?? []), $range, $dateFrom, $dateTo);
            $points = $this->pointsFromRows($rows);
            foreach ($points as $point) {
                $labelsByKey[(string) $point['date_key']] = (string) $point['date_label'];
            }

            $prepared[] = [
                'key' => (string) ($serieConfig['key'] ?? ''),
                'label' => (string) ($serieConfig['label'] ?? ($serieConfig['key'] ?? 'Resultado')),
                'points' => $points,
                'trend' => $this->insights->analyzeTrend($rows),
                'summary' => $this->summaryFromPoints($points, $this->insights->analyzeTrend($rows)),
            ];
        }

        ksort($labelsByKey);
        $labels = array_values($labelsByKey);
        $dateKeys = array_keys($labelsByKey);

        foreach ($prepared as &$serie) {
            $pointsByDate = [];
            foreach ($serie['points'] as $point) {
                $pointsByDate[(string) $point['date_key']] = $point;
            }
            $serie['valor'] = [];
            $serie['minimo'] = [];
            $serie['maximo'] = [];
            $serie['critico_min'] = [];
            $serie['critico_max'] = [];
            foreach ($dateKeys as $dateKey) {
                $point = $pointsByDate[$dateKey] ?? null;
                $serie['valor'][] = $point['value'] ?? null;
                $serie['minimo'][] = $point['min'] ?? null;
                $serie['maximo'][] = $point['max'] ?? null;
                $serie['critico_min'][] = $point['critical_min'] ?? null;
                $serie['critico_max'][] = $point['critical_max'] ?? null;
            }
        }
        unset($serie);

        $primary = $prepared[0] ?? ['valor' => [], 'minimo' => [], 'maximo' => [], 'trend' => null];

        return [
            'success' => true,
            'labels' => $labels,
            'date_keys' => $dateKeys,
            'valor' => $primary['valor'] ?? [],
            'minimo' => $primary['minimo'] ?? [],
            'maximo' => $primary['maximo'] ?? [],
            'trend' => $primary['trend'] ?? null,
            'summary' => $primary['summary'] ?? null,
            'series' => $prepared,
        ];
    }

    /**
     * @param list<array<string, mixed>> $rows
     * @return list<array<string, mixed>>
     */
    private function filterRows(array $rows, string $range, string $dateFrom, string $dateTo): array
    {
        $from = $this->parseDate($dateFrom);
        $to = $this->parseDate($dateTo);

        if ($from === null && $range !== 'all') {
            $days = ['30d' => 30, '90d' => 90, '180d' => 180, '1y' => 365][$range] ?? null;
            if ($days !== null) {
                $from = (new \DateTimeImmutable('today'))->modify('-' . $days . ' days');
            }
        }

        $filtered = [];
        foreach ($rows as $row) {
            $date = $this->parseDate((string) ($row['ingreso'] ?? ''));
            if ($date === null) {
                continue;
            }
            if ($from !== null && $date < $from) {
                continue;
            }
            if ($to !== null && $date > $to->setTime(23, 59, 59)) {
                continue;
            }
            $filtered[] = $row;
        }

        return $filtered;
    }

    /**
     * @param list<array<string, mixed>> $rows
     * @return list<array<string, mixed>>
     */
    private function pointsFromRows(array $rows): array
    {
        $points = [];
        $previousValue = null;

        foreach ($rows as $row) {
            $value = $this->toFloatOrNull($row['valor'] ?? null);
            if ($value === null) {
                continue;
            }

            $min = $this->toFloatOrNull($row['minimo'] ?? null);
            $max = $this->toFloatOrNull($row['maximo'] ?? null);
            $criticalMin = $this->toFloatOrNull($row['critico_min'] ?? null);
            $criticalMax = $this->toFloatOrNull($row['critico_max'] ?? null);
            $status = $this->statusForValue($value, $min, $max, $criticalMin, $criticalMax);
            $change = $this->changeFromPrevious($value, $previousValue);
            $date = $this->parseDate((string) ($row['ingreso'] ?? ''));

            if ($date === null) {
                continue;
            }

            $points[] = [
                'registro_id' => (int) ($row['registro_id'] ?? 0),
                'date_key' => $date->format('Y-m-d'),
                'date_label' => $date->format('d/m/Y'),
                'value' => $value,
                'raw_value' => trim((string) ($row['valor'] ?? '')),
                'min' => $min,
                'max' => $max,
                'critical_min' => $criticalMin,
                'critical_max' => $criticalMax,
                'status' => $status['label'],
                'status_class' => $status['class'],
                'interpretation' => $status['interpretation'],
                'change' => $change,
            ];
            $previousValue = $value;
        }

        return $points;
    }

    /**
     * @param list<array<string, mixed>> $points
     * @param array<string, mixed> $trend
     * @return array<string, mixed>|null
     */
    private function summaryFromPoints(array $points, array $trend): ?array
    {
        if (empty($points)) {
            return null;
        }

        $last = $points[count($points) - 1];

        return [
            'last_value' => $last['value'],
            'last_raw_value' => $last['raw_value'],
            'last_date' => $last['date_label'],
            'status' => $last['status'],
            'status_class' => $last['status_class'],
            'trend_direction' => $trend['direction'] ?? 'sin datos suficientes',
            'trend_interpretation' => $trend['interpretation'] ?? '',
            'change' => $last['change'],
            'clinically_relevant_change' => (bool) ($trend['clinically_relevant_change'] ?? false),
        ];
    }

    /**
     * @return array{label:string,class:string,interpretation:string}
     */
    private function statusForValue(float $value, ?float $min, ?float $max, ?float $criticalMin, ?float $criticalMax): array
    {
        if (($criticalMin !== null && $value < $criticalMin) || ($criticalMax !== null && $value > $criticalMax)) {
            return [
                'label' => 'Crítico',
                'class' => 'danger',
                'interpretation' => 'Valor en zona crítica configurada. Requiere revisión prioritaria.',
            ];
        }
        if ($min !== null && $value < $min) {
            return [
                'label' => 'Bajo',
                'class' => 'warning',
                'interpretation' => 'Resultado por debajo del rango de referencia.',
            ];
        }
        if ($max !== null && $value > $max) {
            return [
                'label' => 'Alto',
                'class' => 'warning',
                'interpretation' => 'Resultado por encima del rango de referencia.',
            ];
        }
        if ($min !== null || $max !== null) {
            return [
                'label' => 'Normal',
                'class' => 'success',
                'interpretation' => 'Resultado dentro del rango de referencia disponible.',
            ];
        }

        return [
            'label' => 'Sin rango',
            'class' => 'secondary',
            'interpretation' => 'No hay rango de referencia configurado para este analito.',
        ];
    }

    /**
     * @return array<string, mixed>|null
     */
    private function changeFromPrevious(float $value, ?float $previous): ?array
    {
        if ($previous === null) {
            return null;
        }

        $diff = $value - $previous;
        $percent = abs($diff) / max(abs($previous), 0.0001) * 100;
        $arrow = $diff > 0 ? '↑' : ($diff < 0 ? '↓' : '→');

        return [
            'previous' => $previous,
            'diff' => round($diff, 4),
            'percent' => round($percent, 1),
            'arrow' => $arrow,
            'label' => $arrow . ' ' . round($percent, 1) . '%',
        ];
    }

    private function parseDate(string $raw): ?\DateTimeImmutable
    {
        $raw = trim($raw);
        if ($raw === '') {
            return null;
        }

        try {
            return new \DateTimeImmutable($raw);
        } catch (\Throwable $e) {
            return null;
        }
    }

    private function toFloatOrNull($raw): ?float
    {
        if ($raw === null) {
            return null;
        }
        $str = trim((string) $raw);
        if ($str === '') {
            return null;
        }
        $str = str_replace(["\xc2\xa0", ' '], '', $str);
        if (str_contains($str, ',') && str_contains($str, '.')) {
            $str = strrpos($str, ',') > strrpos($str, '.') ? str_replace(['.', ','], ['', '.'], $str) : str_replace(',', '', $str);
        } elseif (str_contains($str, ',')) {
            $str = str_replace(',', '.', $str);
        }

        return is_numeric($str) ? (float) $str : null;
    }
}
