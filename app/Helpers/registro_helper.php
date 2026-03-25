<?php

if (! function_exists('registro_orden_display')) {
    /**
     * Número de orden visible (folio personalizado) o, si no hay, el registro_id interno.
     */
    function registro_orden_display(object|array|null $row): string
    {
        if ($row === null) {
            return '';
        }
        if (is_object($row)) {
            $num = trim((string) ($row->numero_orden ?? ''));
            if ($num !== '') {
                return $num;
            }

            return (string) (int) ($row->registro_id ?? 0);
        }
        $num = trim((string) ($row['numero_orden'] ?? ''));
        if ($num !== '') {
            return $num;
        }

        return (string) (int) ($row['registro_id'] ?? 0);
    }
}

if (! function_exists('registro_resultado_con_unidad')) {
    /**
     * Resultado y unidad en un solo texto (ej. "80 mg/dl").
     *
     * @param mixed $valor
     * @param mixed $unidad
     */
    function registro_resultado_con_unidad($valor, $unidad): string
    {
        $v = trim((string) ($valor ?? ''));
        $u = trim((string) ($unidad ?? ''));
        if ($v === '' || $v === '-') {
            return '-';
        }
        if ($u === '') {
            return $v;
        }

        return $v . ' ' . $u;
    }
}

if (! function_exists('registro_rango_referencial_texto')) {
    /**
     * Texto de rango referencial (mín./máx.) y opcionalmente la unidad (ej. "70 - 115 mg/dl").
     *
     * @param mixed      $min
     * @param mixed      $max
     * @param mixed|null $unidad
     */
    function registro_rango_referencial_texto($min, $max, $unidad = null): string
    {
        $min = trim((string) ($min ?? ''));
        $max = trim((string) ($max ?? ''));
        $u   = trim((string) ($unidad ?? ''));

        if ($min === '' && $max === '') {
            return '-';
        }

        $rangePart = ($min !== '' && $max !== '')
            ? $min . ' - ' . $max
            : ($min !== '' ? $min : $max);

        if ($u === '') {
            return $rangePart;
        }

        return $rangePart . ' ' . $u;
    }
}
