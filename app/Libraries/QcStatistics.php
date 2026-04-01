<?php

namespace App\Libraries;

/**
 * Estadísticos y reglas de Westgard para gráficas de control (Levey-Jennings).
 */
class QcStatistics
{
    /**
     * @param list<float|int> $numericValues
     * @return array{n:int,mean:float,sd:float,cv_percent:?float}|null
     */
    public static function sampleStats(array $numericValues): ?array
    {
        $vals = array_values(array_filter($numericValues, static fn ($v) => is_numeric($v)));
        $n = count($vals);
        if ($n < 1) {
            return null;
        }

        $mean = array_sum($vals) / $n;
        if ($n === 1) {
            return [
                'n'           => $n,
                'mean'        => (float) $mean,
                'sd'          => 0.0,
                'cv_percent'  => null,
            ];
        }

        $sumSq = 0.0;
        foreach ($vals as $x) {
            $sumSq += ($x - $mean) ** 2;
        }
        $sd = sqrt($sumSq / ($n - 1));
        $cv = ($mean != 0.0) ? ($sd / abs($mean)) * 100.0 : null;

        return [
            'n'           => $n,
            'mean'        => (float) $mean,
            'sd'          => (float) $sd,
            'cv_percent'  => $cv !== null ? (float) $cv : null,
        ];
    }

    /**
     * @param list<array{fecha:string,valor:float}> $series Orden cronológico ascendente
     * @return list<string> Mensajes de violación (fechas escapadas)
     */
    public static function evaluateWestgard(array $series, float $mean, float $sd): array
    {
        $violations = [];
        if ($sd <= 0.0 || $series === []) {
            return $violations;
        }

        $vals = array_map(static fn ($row) => (float) $row['valor'], $series);
        $n = count($vals);

        $fe = static fn (string $f): string => htmlspecialchars($f, ENT_QUOTES, 'UTF-8');

        for ($i = 0; $i < $n; $i++) {
            if (abs($vals[$i] - $mean) > 3 * $sd) {
                $violations[] = 'Regla 1-3s: en ' . $fe($series[$i]['fecha'])
                    . ' el resultado supera ±3 DE (rechazo).';
            }
        }

        for ($i = 1; $i < $n; $i++) {
            if ($vals[$i] > $mean + 2 * $sd && $vals[$i - 1] > $mean + 2 * $sd) {
                $violations[] = 'Regla 2-2s: dos consecutivos por encima de +2 DE ('
                    . $fe($series[$i - 1]['fecha']) . ', ' . $fe($series[$i]['fecha']) . ').';
            }
            if ($vals[$i] < $mean - 2 * $sd && $vals[$i - 1] < $mean - 2 * $sd) {
                $violations[] = 'Regla 2-2s: dos consecutivos por debajo de -2 DE ('
                    . $fe($series[$i - 1]['fecha']) . ', ' . $fe($series[$i]['fecha']) . ').';
            }
        }

        for ($i = 1; $i < $n; $i++) {
            if (abs($vals[$i] - $vals[$i - 1]) > 4 * $sd) {
                $violations[] = 'Regla R-4s: diferencia entre consecutivos > 4 DE ('
                    . $fe($series[$i - 1]['fecha']) . ' → ' . $fe($series[$i]['fecha']) . ').';
            }
        }

        for ($i = 3; $i < $n; $i++) {
            $slice = array_slice($vals, $i - 3, 4);
            $allAbove = true;
            $allBelow = true;
            foreach ($slice as $x) {
                if ($x <= $mean + $sd) {
                    $allAbove = false;
                }
                if ($x >= $mean - $sd) {
                    $allBelow = false;
                }
            }
            if ($allAbove) {
                $violations[] = 'Regla 4-1s: cuatro consecutivos por encima de +1 DE (termina en '
                    . $fe($series[$i]['fecha']) . ').';
            }
            if ($allBelow) {
                $violations[] = 'Regla 4-1s: cuatro consecutivos por debajo de -1 DE (termina en '
                    . $fe($series[$i]['fecha']) . ').';
            }
        }

        for ($i = 9; $i < $n; $i++) {
            $slice = array_slice($vals, $i - 9, 10);
            $above = 0;
            $below = 0;
            foreach ($slice as $x) {
                if ($x > $mean) {
                    $above++;
                }
                if ($x < $mean) {
                    $below++;
                }
            }
            if ($above === 10) {
                $violations[] = 'Regla 10x: diez consecutivos por encima de la media (termina en '
                    . $fe($series[$i]['fecha']) . ').';
            }
            if ($below === 10) {
                $violations[] = 'Regla 10x: diez consecutivos por debajo de la media (termina en '
                    . $fe($series[$i]['fecha']) . ').';
            }
        }

        return array_values(array_unique($violations));
    }
}
