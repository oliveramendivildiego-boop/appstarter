<?php

if (! function_exists('registro_codigo_recepcion_display')) {
    /**
     * Código de recepción (folio). Nunca muestra el registro_id interno.
     */
    function registro_codigo_recepcion_display(object|array|null $row, bool $persistIfMissing = true): string
    {
        if ($row === null) {
            return '';
        }

        $registroId  = (int) (is_object($row) ? ($row->registro_id ?? 0) : ($row['registro_id'] ?? 0));
        $numeroOrden = trim((string) (is_object($row) ? ($row->numero_orden ?? '') : ($row['numero_orden'] ?? '')));
        $ingreso     = (string) (is_object($row) ? ($row->ingreso ?? '') : ($row['ingreso'] ?? ''));
        $codigo      = trim((string) (is_object($row) ? ($row->codigo_recepcion ?? '') : ($row['codigo_recepcion'] ?? '')));
        if ($codigo !== '') {
            return $codigo;
        }

        return (new \App\Services\RegistroFolioService())->codigoRecepcionDisplay(
            $registroId,
            $numeroOrden,
            $ingreso,
            $persistIfMissing
        );
    }
}

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

if (! function_exists('registro_opcion_es_texto_rico')) {
    function registro_opcion_es_texto_rico(int $opcionId): bool
    {
        return \App\Models\OpcionModel::isTextoRico($opcionId);
    }
}

if (! function_exists('registro_opcion_es_texto_libre')) {
    function registro_opcion_es_texto_libre(int $opcionId): bool
    {
        return \App\Models\OpcionModel::isTextoLibre($opcionId);
    }
}

if (! function_exists('registro_opcion_es_select')) {
    function registro_opcion_es_select(int $opcionId): bool
    {
        return \App\Models\OpcionModel::isSelect($opcionId);
    }
}

if (! function_exists('registro_sanitizar_html_rico')) {
    /**
     * Limpia HTML de resultados enriquecidos (negrita, cursiva, listas, etc.).
     */
    function registro_sanitizar_html_rico(string $html): string
    {
        $html = trim($html);
        if ($html === '' || $html === '-') {
            return '';
        }

        $allowed = '<p><br><strong><b><em><i><u><ul><ol><li><a><span><div>';
        $clean = strip_tags($html, $allowed);
        $clean = preg_replace('/\s+on\w+\s*=\s*(["\']).*?\1/i', '', $clean) ?? $clean;
        $clean = preg_replace('/javascript\s*:/i', '', $clean) ?? $clean;

        return trim($clean);
    }
}

if (! function_exists('registro_textarea_body_safe')) {
    /**
     * Contenido seguro para pegar dentro de <textarea> (conserva HTML para Summernote).
     */
    function registro_textarea_body_safe(string $html): string
    {
        return str_replace('</textarea>', '&lt;/textarea&gt;', $html);
    }
}

if (! function_exists('registro_resultado_celda_html')) {
    /**
     * HTML seguro para mostrar un resultado en reportes (texto plano escapado o HTML enriquecido).
     *
     * @param mixed $valor
     * @param mixed $unidad
     */
    function registro_resultado_celda_html($valor, $unidad, int $opcionId = 3): string
    {
        if (registro_opcion_es_texto_rico($opcionId)) {
            $html = registro_sanitizar_html_rico((string) ($valor ?? ''));
            if ($html === '') {
                return esc('-');
            }
            $u = trim((string) ($unidad ?? ''));
            $suffix = $u !== '' ? ' <span class="text-muted">' . esc($u) . '</span>' : '';

            return '<div class="resultado-texto-rico text-start d-inline-block">' . $html . $suffix . '</div>';
        }

        return esc(registro_resultado_con_unidad($valor, $unidad));
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

if (! function_exists('paciente_nombre_display')) {
    /**
     * Nombre del paciente: apellido paterno, apellido materno, nombres.
     */
    function paciente_nombre_display(object|array|null $person): string
    {
        if ($person === null) {
            return '';
        }
        $lastFa = trim(is_object($person) ? (string) ($person->last_name_fa ?? '') : (string) ($person['last_name_fa'] ?? ''));
        $lastMom = trim(is_object($person) ? (string) ($person->last_name_mom ?? '') : (string) ($person['last_name_mom'] ?? ''));
        $first = trim(is_object($person) ? (string) ($person->first_name ?? '') : (string) ($person['first_name'] ?? ''));
        if ($lastFa !== '' || $lastMom !== '' || $first !== '') {
            return trim(implode(' ', array_filter([$lastFa, $lastMom, $first], static fn (string $p): bool => $p !== '')));
        }

        return trim(is_object($person) ? (string) ($person->paciente ?? '') : (string) ($person['paciente'] ?? ''));
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
