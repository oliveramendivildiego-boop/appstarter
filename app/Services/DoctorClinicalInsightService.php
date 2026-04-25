<?php

namespace App\Services;

/**
 * Calcula una lectura clínica rápida sobre los resultados ya preparados para reporte.
 * No reemplaza criterio médico; solo organiza señales objetivas del laboratorio.
 */
class DoctorClinicalInsightService
{
    private static $relevantChangeRatio = 0.20;

    /**
     * @param array<string, list<object>> $grupos
     * @return array<string, mixed>
     */
    public function summarizeReport(array $grupos, array $context = []): array
    {
        $alterations = [];
        $criticalCount = 0;
        $totalNumeric = 0;
        $microbiology = [];

        foreach ($grupos as $groupName => $items) {
            foreach ($items as $item) {
                $row = is_array($item) ? (object) $item : $item;
                if ((int) ($row->es_separador ?? 0) === 1) {
                    continue;
                }

                $valueRaw = trim((string) ($row->regvalues ?? ''));
                if ($valueRaw === '' || $valueRaw === '-') {
                    continue;
                }

                $micro = $this->interpretMicrobiology((string) ($row->nombre ?? $row->hijo ?? ''), $valueRaw);
                if ($micro !== null) {
                    $microbiology[] = $micro + ['grupo' => (string) $groupName];
                }

                $value = $this->toFloatOrNull($valueRaw);
                if ($value === null) {
                    continue;
                }
                $totalNumeric++;

                $min = $this->toFloatOrNull($row->valor_min ?? null);
                $max = $this->toFloatOrNull($row->valor_max ?? null);
                $criticalMin = $this->toFloatOrNull($row->critico_min ?? null);
                $criticalMax = $this->toFloatOrNull($row->critico_max ?? null);

                $direction = null;
                if ($min !== null && $value < $min) {
                    $direction = 'bajo';
                } elseif ($max !== null && $value > $max) {
                    $direction = 'alto';
                }

                $isCritical = ($criticalMin !== null && $value < $criticalMin)
                    || ($criticalMax !== null && $value > $criticalMax);

                if ($direction !== null || $isCritical) {
                    if ($isCritical) {
                        $criticalCount++;
                    }
                    $alterations[] = [
                        'nombre' => trim((string) ($row->nombre ?? $row->hijo ?? 'Resultado')),
                        'grupo' => (string) $groupName,
                        'valor' => $valueRaw,
                        'unidad' => trim((string) ($row->umedida ?? '')),
                        'minimo' => $min,
                        'maximo' => $max,
                        'direccion' => $direction ?? 'crítico',
                        'critico' => $isCritical,
                        'score' => $this->alterationScore($value, $min, $max, $isCritical),
                    ];
                }
            }
        }

        usort($alterations, static function (array $a, array $b): int {
            return ((float) ($b['score'] ?? 0)) <=> ((float) ($a['score'] ?? 0));
        });

        $topAlterations = array_slice($alterations, 0, 5);
        $status = $this->overallStatus(count($alterations), $criticalCount, $microbiology);

        return [
            'altered_count' => count($alterations),
            'critical_count' => $criticalCount,
            'total_numeric' => $totalNumeric,
            'top_alterations' => $topAlterations,
            'status' => $status,
            'status_class' => $this->statusClass($status),
            'global_interpretation' => $this->globalInterpretation($status, count($alterations), $criticalCount, $context, $microbiology),
            'recommendations' => $this->recommendations($status, $topAlterations, $context, $microbiology),
            'microbiology' => $microbiology,
        ];
    }

    /**
     * @param list<array<string, mixed>> $serie
     * @return array<string, mixed>
     */
    public function analyzeTrend(array $serie): array
    {
        $points = [];
        foreach ($serie as $row) {
            $value = $this->toFloatOrNull($row['valor'] ?? null);
            if ($value === null) {
                continue;
            }
            $points[] = [
                'registro_id' => (int) ($row['registro_id'] ?? 0),
                'ingreso' => (string) ($row['ingreso'] ?? ''),
                'valor' => $value,
                'minimo' => $this->toFloatOrNull($row['minimo'] ?? null),
                'maximo' => $this->toFloatOrNull($row['maximo'] ?? null),
            ];
        }

        $count = count($points);
        if ($count < 2) {
            return [
                'direction' => 'sin datos suficientes',
                'last_vs_previous' => null,
                'clinically_relevant_change' => false,
                'interpretation' => 'Se requiere al menos un resultado previo comparable.',
            ];
        }

        $last = $points[$count - 1];
        $previous = $points[$count - 2];
        $diff = $last['valor'] - $previous['valor'];
        $ratio = abs($diff) / max(abs((float) $previous['valor']), 0.0001);
        $direction = $diff > 0 ? 'ascendente' : ($diff < 0 ? 'descendente' : 'estable');
        $crossedRange = $this->isOutOfRange($last['valor'], $last['minimo'], $last['maximo'])
            !== $this->isOutOfRange($previous['valor'], $previous['minimo'], $previous['maximo']);
        $relevant = $ratio >= self::$relevantChangeRatio || $crossedRange;

        return [
            'direction' => $direction,
            'last_vs_previous' => [
                'last' => $last['valor'],
                'previous' => $previous['valor'],
                'diff' => $diff,
                'percent' => round($ratio * 100, 1),
            ],
            'clinically_relevant_change' => $relevant,
            'interpretation' => $this->trendInterpretation($direction, $ratio, $crossedRange),
        ];
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

    private function alterationScore(float $value, ?float $min, ?float $max, bool $isCritical): float
    {
        $score = $isCritical ? 100.0 : 0.0;
        if ($min !== null && $value < $min) {
            $score += (($min - $value) / max(abs($min), 0.0001)) * 100;
        }
        if ($max !== null && $value > $max) {
            $score += (($value - $max) / max(abs($max), 0.0001)) * 100;
        }

        return round($score, 2);
    }

    private function overallStatus(int $alteredCount, int $criticalCount, array $microbiology): string
    {
        foreach ($microbiology as $micro) {
            if (($micro['severity'] ?? '') === 'critical') {
                return 'Crítico';
            }
        }
        if ($criticalCount > 0) {
            return 'Crítico';
        }
        if ($alteredCount > 0) {
            return 'Riesgo';
        }

        return 'Normal';
    }

    private function statusClass(string $status): string
    {
        return $status === 'Crítico' ? 'danger' : ($status === 'Riesgo' ? 'warning' : 'success');
    }

    private function globalInterpretation(string $status, int $alteredCount, int $criticalCount, array $context, array $microbiology): string
    {
        $dx = trim((string) ($context['diagnostico_presuntivo'] ?? ''));
        $motivo = trim((string) ($context['motivo_estudio'] ?? ''));
        $contextText = $dx !== '' || $motivo !== ''
            ? ' Considerar junto con el contexto clínico registrado.'
            : ' No se registró diagnóstico presuntivo o motivo de estudio para correlación.';

        if ($status === 'Crítico') {
            return 'Se identifican resultados críticos o patrones microbiológicos de alto impacto. Requiere revisión médica prioritaria.' . $contextText;
        }
        if ($status === 'Riesgo') {
            return 'Se identifican ' . $alteredCount . ' valor(es) fuera de rango, sin criterio crítico automático.' . $contextText;
        }
        if (!empty($microbiology)) {
            return 'Resultados sin alteraciones numéricas relevantes; revisar hallazgos microbiológicos reportados.' . $contextText;
        }

        return 'No se detectan alteraciones numéricas relevantes en los parámetros evaluados.' . $contextText;
    }

    /**
     * @return list<array{priority:string,text:string}>
     */
    private function recommendations(string $status, array $topAlterations, array $context, array $microbiology): array
    {
        $recommendations = [];
        if ($status === 'Crítico') {
            $recommendations[] = ['priority' => 'Urgente', 'text' => 'Contactar al paciente o servicio tratante y correlacionar con signos/síntomas actuales.'];
            $recommendations[] = ['priority' => 'Urgente', 'text' => 'Confirmar identidad de muestra y considerar repetición inmediata si el resultado no concuerda con la clínica.'];
        } elseif ($status === 'Riesgo') {
            $recommendations[] = ['priority' => 'Sugerida', 'text' => 'Comparar con antecedentes del paciente y controlar evolución según criterio médico.'];
        } else {
            $recommendations[] = ['priority' => 'Opcional', 'text' => 'Mantener seguimiento clínico habitual si el motivo de estudio persiste.'];
        }

        foreach ($topAlterations as $alt) {
            $name = strtolower((string) ($alt['nombre'] ?? ''));
            if (str_contains($name, 'glucosa') && ($alt['direccion'] ?? '') === 'alto') {
                $recommendations[] = ['priority' => 'Sugerida', 'text' => 'Si corresponde, complementar con HbA1c o repetir glucosa en ayunas.'];
            }
            if (str_contains($name, 'creatin') || str_contains($name, 'urea')) {
                $recommendations[] = ['priority' => 'Sugerida', 'text' => 'Valorar función renal, hidratación y medicación concomitante.'];
            }
            if (str_contains($name, 'hemoglob') && ($alt['direccion'] ?? '') === 'bajo') {
                $recommendations[] = ['priority' => 'Sugerida', 'text' => 'Correlacionar con clínica de anemia y considerar perfil férrico según el caso.'];
            }
        }

        foreach ($microbiology as $micro) {
            if (($micro['pattern'] ?? '') !== '') {
                $recommendations[] = ['priority' => 'Urgente', 'text' => 'Patrón ' . $micro['pattern'] . ': revisar antibiograma y política local CLSI/EUCAST antes de indicar tratamiento.'];
            }
        }

        return $this->uniqueRecommendations($recommendations);
    }

    private function interpretMicrobiology(string $name, string $value): ?array
    {
        $haystack = strtoupper($name . ' ' . $value);
        $meaning = null;
        if (preg_match('/\bS\b|SENSIBLE/', $haystack) === 1) {
            $meaning = 'Sensible: probable respuesta con dosis estándar si el antibiótico es adecuado clínicamente.';
        } elseif (preg_match('/\bI\b|INTERMED/', $haystack) === 1) {
            $meaning = 'Intermedio: puede requerir mayor exposición, dosis optimizada o sitio de infección favorable.';
        } elseif (preg_match('/\bR\b|RESIST/', $haystack) === 1) {
            $meaning = 'Resistente: no se espera respuesta clínica con exposición estándar.';
        }

        $pattern = '';
        $severity = 'info';
        if (str_contains($haystack, 'BLEE') || str_contains($haystack, 'ESBL')) {
            $pattern = 'BLEE/ESBL';
            $severity = 'critical';
        } elseif (str_contains($haystack, 'MRSA') || str_contains($haystack, 'SARM')) {
            $pattern = 'MRSA/SARM';
            $severity = 'critical';
        }

        if ($meaning === null && $pattern === '') {
            return null;
        }

        return [
            'nombre' => $name,
            'valor' => $value,
            'meaning' => $meaning ?? 'Hallazgo microbiológico relevante.',
            'pattern' => $pattern,
            'severity' => $severity,
            'first_line' => $pattern === '' ? '' : 'Definir según foco, antibiograma y guía local vigente.',
            'alternatives' => 'Usar alternativas reportadas como sensibles y validadas por CLSI/EUCAST.',
        ];
    }

    private function uniqueRecommendations(array $recommendations): array
    {
        $seen = [];
        $out = [];
        foreach ($recommendations as $row) {
            $text = trim((string) ($row['text'] ?? ''));
            if ($text === '' || isset($seen[$text])) {
                continue;
            }
            $seen[$text] = true;
            $out[] = [
                'priority' => (string) ($row['priority'] ?? 'Sugerida'),
                'text' => $text,
            ];
        }

        return $out;
    }

    private function isOutOfRange(float $value, ?float $min, ?float $max): bool
    {
        return ($min !== null && $value < $min) || ($max !== null && $value > $max);
    }

    private function trendInterpretation(string $direction, float $ratio, bool $crossedRange): string
    {
        $pct = round($ratio * 100, 1);
        if ($crossedRange) {
            return 'Cambio clínicamente relevante: cruza límites de referencia respecto al resultado previo.';
        }
        if ($ratio >= self::$relevantChangeRatio) {
            return 'Cambio ' . $direction . ' de ' . $pct . '% respecto al resultado anterior.';
        }

        return 'Tendencia ' . $direction . ' sin cambio porcentual relevante automático.';
    }
}
