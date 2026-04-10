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

if (! function_exists('registro_tiene_rango_referencial')) {
    /**
     * Indica si hay al menos un límite (mín. o máx.) de referencia configurado.
     */
    function registro_tiene_rango_referencial($min, $max): bool
    {
        return trim((string) ($min ?? '')) !== '' || trim((string) ($max ?? '')) !== '';
    }
}

if (! function_exists('paciente_genero_texto')) {
    /**
     * Texto de género según people.gender (1=Masculino, 2=Femenino).
     */
    function paciente_genero_texto(object|array|null $person): string
    {
        if ($person === null) {
            return '—';
        }
        $g = is_object($person)
            ? (int) ($person->gender ?? 0)
            : (int) ($person['gender'] ?? 0);

        return match ($g) {
            1 => 'Masculino',
            2 => 'Femenino',
            default => '—',
        };
    }
}

if (! function_exists('report_image_data_uri')) {
    /**
     * Data URI para imágenes en PDF (DomPDF) e impresión: lee desde FCPATH.
     */
    function report_image_data_uri(string $relativePath): string
    {
        $relativePath = str_replace('\\', '/', trim($relativePath));
        if ($relativePath === '' || str_contains($relativePath, '..')) {
            return '';
        }
        $full = FCPATH . str_replace('/', DIRECTORY_SEPARATOR, $relativePath);
        if (! is_file($full) || ! is_readable($full)) {
            return '';
        }
        $data = @file_get_contents($full);
        if ($data === false) {
            return '';
        }
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mime  = $finfo ? finfo_file($finfo, $full) : false;
        if ($finfo) {
            finfo_close($finfo);
        }

        return 'data:' . ($mime ?: 'image/png') . ';base64,' . base64_encode($data);
    }
}
