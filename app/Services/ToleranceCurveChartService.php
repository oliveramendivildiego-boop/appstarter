<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\LabotestModel;
use App\Models\OpcionModel;

/**
 * Construye datos para la curva de tolerancia (p. ej. glucosa basal + post-carga) en reportes.
 */
class ToleranceCurveChartService
{
    /**
     * @param list<object|array<string, mixed>> $items Filas del reporte (subItems de prueba compuesta)
     *
     * @return array<string, mixed>|null null si no aplica o no hay puntos comparables
     */
    public function buildFromReportItemsWithModo(array $items, int $prianacategoriaId, int $graficarModo): ?array
    {
        if ($prianacategoriaId < 1 || $items === [] || $graficarModo <= LabotestModel::GRAFICAR_NO) {
            return null;
        }

        return $this->buildFromParsedItems($items);
    }

    /**
     * @param list<object|array<string, mixed>> $items
     *
     * @return array<string, mixed>|null
     */
    private function buildFromParsedItems(array $items): ?array
    {
        \helper('registro');

        $points = [];
        $unit = '';

        foreach ($items as $raw) {
            $item = is_array($raw) ? (object) $raw : $raw;
            if ((int) ($item->es_separador ?? 0) === 1 || ! empty($item->es_cultivo_matriz)) {
                continue;
            }

            $opcionId = (int) ($item->opcion_id ?? 0);
            if (OpcionModel::isSelect($opcionId)) {
                continue;
            }

            $nombre = trim((string) ($item->nombre ?? $item->hijo ?? ''));
            if ($nombre === '') {
                continue;
            }

            $valorRaw = trim((string) ($item->regvalues ?? ''));
            if ($valorRaw === '' || $valorRaw === '-') {
                continue;
            }

            $rango = registro_extraer_rango_numerico_de_valor($valorRaw, (string) ($item->umedida ?? ''));
            if ($rango === null) {
                continue;
            }

            $value = ($rango['min'] + $rango['max']) / 2.0;

            $refMinRaw = trim((string) ($item->valor_min ?? ''));
            $refMaxRaw = trim((string) ($item->valor_max ?? ''));
            if (! registro_tiene_rango_referencial($refMinRaw, $refMaxRaw)) {
                continue;
            }

            $refMin = registro_extraer_comparacion_numerica($refMinRaw);
            $refMax = registro_extraer_comparacion_numerica($refMaxRaw);
            if ($refMin === null && $refMax === null) {
                $limMin = registro_extraer_limite_referencial($refMinRaw, true);
                $limMax = registro_extraer_limite_referencial($refMaxRaw, false);
                $refMin = $limMin['min'] ?? $limMin['max'] ?? null;
                $refMax = $limMax['max'] ?? $limMax['min'] ?? null;
            }
            if ($refMin === null || $refMax === null) {
                continue;
            }
            if ($refMin > $refMax) {
                [$refMin, $refMax] = [$refMax, $refMin];
            }

            $itemUnit = trim((string) ($item->umedida ?? ''));
            if ($itemUnit !== '' && $unit === '') {
                $unit = $itemUnit;
            }

            $timeH = $this->inferTimeHours($nombre, (int) ($item->orden ?? 0));
            $points[] = [
                'label'   => $this->shortAxisLabel($nombre, $timeH),
                'time_h'  => $timeH,
                'value'   => round($value, 1),
                'ref_min' => round($refMin, 1),
                'ref_max' => round($refMax, 1),
                'orden'   => (int) ($item->orden ?? 0),
            ];
        }

        if (count($points) < 2) {
            return null;
        }

        usort($points, static function (array $a, array $b): int {
            if ($a['time_h'] !== $b['time_h']) {
                return $a['time_h'] <=> $b['time_h'];
            }
            if ($a['orden'] !== $b['orden']) {
                return $a['orden'] <=> $b['orden'];
            }

            return strcmp((string) $a['label'], (string) $b['label']);
        });

        $yMin = PHP_FLOAT_MAX;
        $yMax = -PHP_FLOAT_MAX;
        foreach ($points as $pt) {
            $yMin = min($yMin, (float) $pt['ref_min'], (float) $pt['value']);
            $yMax = max($yMax, (float) $pt['ref_max'], (float) $pt['value']);
        }
        $padding = max(10.0, ($yMax - $yMin) * 0.12);
        $yMin = max(0.0, floor($yMin - $padding));
        $yMax = ceil($yMax + $padding);
        if ($yMax <= $yMin) {
            $yMax = $yMin + 20.0;
        }

        return [
            'title'  => 'Curva de tolerancia a la glucosa',
            'unit'   => $unit !== '' ? $unit : 'mg/dL',
            'points' => $points,
            'y_min'  => $yMin,
            'y_max'  => $yMax,
        ];
    }

    private function inferTimeHours(string $name, int $orden): float
    {
        $n = mb_strtolower(trim($name));

        if (preg_match('/\bbasal\b|\bayuna/u', $n)) {
            return 0.0;
        }
        if (preg_match('/\bprimera\b.*\bhora\b|\b1\s*ª?\s*hora\b|\b1\s*h\b|\b60\s*min/u', $n)) {
            return 1.0;
        }
        if (preg_match('/\bsegunda\b.*\bhora\b|\b2\s*ª?\s*hora\b|\b2\s*h\b|\b120\s*min/u', $n)) {
            return 2.0;
        }
        if (preg_match('/\btercera\b.*\bhora\b|\b3\s*ª?\s*hora\b|\b3\s*h\b|\b180\s*min/u', $n)) {
            return 3.0;
        }
        if (preg_match('/\bcuarta\b.*\bhora\b|\b4\s*ª?\s*hora\b|\b4\s*h\b/u', $n)) {
            return 4.0;
        }

        return max(0.0, (float) $orden);
    }

    private function shortAxisLabel(string $name, float $timeH): string
    {
        if ($timeH <= 0.0) {
            return 'Basal';
        }
        if (abs($timeH - round($timeH)) < 0.01) {
            return (int) round($timeH) . ' h';
        }

        $clean = trim(preg_replace('/\s+/u', ' ', $name) ?? $name);
        if (mb_strlen($clean) > 18) {
            return mb_substr($clean, 0, 16) . '…';
        }

        return $clean !== '' ? $clean : (string) $timeH;
    }
}
