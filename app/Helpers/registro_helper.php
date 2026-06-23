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

if (! function_exists('registro_normalizar_valor_y_unidad_para_mostrar')) {
    /**
     * Evita duplicar número o unidad al armar el texto del reporte (p. ej. "60 60 ml").
     *
     * @return array{0: string, 1: string} [valor, unidad]
     */
    function registro_normalizar_valor_y_unidad_para_mostrar(string $valor, string $unidad): array
    {
        $v = trim($valor);
        $u = trim($unidad);
        if ($v === '' || $v === '-') {
            return [$v, $u];
        }

        if (preg_match('/^([\d]+(?:[.,]\d+)?)\s+\1$/u', $v, $m)) {
            $v = $m[1];
        }

        if ($u !== '') {
            $uPattern = preg_quote($u, '/');
            if (preg_match('/\s' . $uPattern . '$/iu', $v)) {
                return [$v, ''];
            }

            $vPattern = preg_quote($v, '/');
            if (preg_match('/^' . $vPattern . '\s+(.+)$/u', $u, $m)) {
                $u = trim($m[1]);
                if (preg_match('/\s' . preg_quote($u, '/') . '$/iu', $v)) {
                    return [$v, ''];
                }
            }
        }

        return [$v, $u];
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

        [$v, $u] = registro_normalizar_valor_y_unidad_para_mostrar($v, $u);

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

if (! function_exists('registro_rango_referencial_html')) {
    /**
     * HTML seguro para rango referencial: unidad y guion entre mín./máx. en negrita.
     *
     * @param mixed      $min
     * @param mixed      $max
     * @param mixed|null $unidad
     */
    function registro_rango_referencial_html($min, $max, $unidad = null): string
    {
        $min = trim((string) ($min ?? ''));
        $max = trim((string) ($max ?? ''));
        $u   = trim((string) ($unidad ?? ''));

        if ($min === '' && $max === '') {
            return esc('-');
        }

        if ($min !== '' && $max !== '') {
            $rangePart = esc($min) . ' <strong>-</strong> ' . esc($max);
        } else {
            $rangePart = esc($min !== '' ? $min : $max);
        }

        if ($u === '') {
            return $rangePart;
        }

        return $rangePart . ' <strong>' . esc($u) . '</strong>';
    }
}

if (! function_exists('registro_resultado_con_unidad_html')) {
    /**
     * Resultado con unidad en HTML seguro (unidad en negrita).
     *
     * @param mixed $valor
     * @param mixed $unidad
     */
    function registro_resultado_con_unidad_html($valor, $unidad): string
    {
        $v = trim((string) ($valor ?? ''));
        $u = trim((string) ($unidad ?? ''));
        if ($v === '' || $v === '-') {
            return esc('-');
        }

        [$v, $u] = registro_normalizar_valor_y_unidad_para_mostrar($v, $u);

        if ($u === '') {
            return esc($v);
        }

        return esc($v) . ' <strong>' . esc($u) . '</strong>';
    }
}

if (! function_exists('registro_opcion_es_texto_rico')) {
    function registro_opcion_es_texto_rico(int $opcionId): bool
    {
        return \App\Models\OpcionModel::isTextoRico($opcionId);
    }
}

if (! function_exists('registro_opcion_es_texto_fijo')) {
    function registro_opcion_es_texto_fijo(int $opcionId): bool
    {
        return \App\Models\OpcionModel::isTextoFijo($opcionId);
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

if (! function_exists('registro_personalizado_texto_fijo_html')) {
    /**
     * Muestra texto fijo de celda personalizada (plano o HTML enriquecido).
     */
    function registro_personalizado_texto_fijo_html(string $texto, string $fuente = 'normal'): string
    {
        $texto = trim($texto);
        if ($texto === '') {
            return '';
        }
        if ($fuente === 'enriquecido' || $texto !== strip_tags($texto)) {
            return registro_sanitizar_html_rico($texto);
        }

        return esc($texto);
    }
}

if (! function_exists('registro_normalizar_estilos_inline_html')) {
    /**
     * Convierte estilos inline de Summernote (span style=...) a etiquetas semánticas
     * para que sobrevivan al CSS del reporte (font-style: normal !important en td).
     */
    function registro_normalizar_estilos_inline_html(string $html): string
    {
        $html = trim($html);
        if ($html === '') {
            return '';
        }

        $prev = '';
        $guard = 0;
        while ($html !== $prev && $guard < 20) {
            $prev = $html;
            $guard++;
            $html = preg_replace_callback(
                '/<span\b([^>]*)>(.*?)<\/span>/is',
                static function (array $m): string {
                    $attrs = $m[1];
                    $inner = $m[2];
                    if (! preg_match('/style=(["\'])(.*?)\1/is', $attrs, $sm)) {
                        return $inner;
                    }
                    $style = strtolower(preg_replace('/\s+/', '', $sm[2]) ?? $sm[2]);
                    $out = $inner;
                    if (str_contains($style, 'font-style:italic') || str_contains($style, 'font-style:oblique')) {
                        $out = '<em>' . $out . '</em>';
                    }
                    if (preg_match('/font-weight:(bold|[6-9]00)/', $style)) {
                        $out = '<strong>' . $out . '</strong>';
                    }
                    if (str_contains($style, 'text-decoration:underline') || str_contains($style, 'text-decoration-line:underline')) {
                        $out = '<u>' . $out . '</u>';
                    }
                    if ($out === $inner) {
                        return $inner;
                    }

                    return $out;
                },
                $html
            ) ?? $html;
        }

        return $html;
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

        if (str_contains($html, '&lt;') || str_contains($html, '&gt;')) {
            $html = html_entity_decode($html, ENT_QUOTES | ENT_HTML5, 'UTF-8');
        }

        $html = registro_normalizar_estilos_inline_html($html);

        $allowed = '<p><br><strong><b><em><i><u><ul><ol><li><a><span><div>';
        $clean = strip_tags($html, $allowed);
        $clean = preg_replace('/\s+on\w+\s*=\s*(["\']).*?\1/i', '', $clean) ?? $clean;
        $clean = preg_replace('/javascript\s*:/i', '', $clean) ?? $clean;
        // Quitar style= residual en span (ya convertidos los formatos comunes a em/strong/u).
        $clean = preg_replace('/<span\b[^>]*\bstyle=(["\']).*?\1[^>]*>/i', '<span>', $clean) ?? $clean;

        return trim($clean);
    }
}

if (! function_exists('registro_texto_fijo_para_mostrar')) {
    /**
     * Valor a mostrar en captura/reporte para tipo texto fijo: prioriza la configuración del análisis.
     */
    function registro_texto_fijo_para_mostrar(string $valorGuardado, string $textoConfig): string
    {
        $cfg = trim($textoConfig);
        if ($cfg !== '') {
            return registro_sanitizar_html_rico($cfg);
        }
        $raw = trim($valorGuardado);
        if ($raw === '' || $raw === '-') {
            return '';
        }

        return registro_sanitizar_html_rico($raw);
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

if (! function_exists('registro_mostrar_medida_solo_en_referencia')) {
    /**
     * Indica si la unidad debe mostrarse solo en el rango referencial (no junto al resultado).
     *
     * @param object|array<string,mixed>|null $item
     */
    function registro_mostrar_medida_solo_en_referencia(object|array|null $item): bool
    {
        if ($item === null) {
            return false;
        }
        $flag = is_object($item)
            ? ($item->mostrar_medida ?? 0)
            : ($item['mostrar_medida'] ?? 0);

        return (int) $flag === 1;
    }
}

if (! function_exists('registro_unidad_para_resultado')) {
    /**
     * Unidad visible junto al valor del resultado en reportes.
     *
     * @param mixed $unidad
     */
    function registro_unidad_para_resultado($unidad, bool $mostrarMedidaSoloEnReferencia): string
    {
        if ($mostrarMedidaSoloEnReferencia) {
            return '';
        }

        return trim((string) ($unidad ?? ''));
    }
}

if (! function_exists('registro_valor_contiene_html_rico')) {
    function registro_valor_contiene_html_rico(string $valor): bool
    {
        $valor = trim($valor);
        if ($valor === '') {
            return false;
        }
        if (str_contains($valor, '&lt;') || str_contains($valor, '&gt;')) {
            $valor = html_entity_decode($valor, ENT_QUOTES | ENT_HTML5, 'UTF-8');
        }

        return $valor !== strip_tags($valor);
    }
}

if (! function_exists('registro_resultado_celda_html')) {
    /**
     * HTML seguro para mostrar un resultado en reportes (texto plano escapado o HTML enriquecido).
     *
     * @param mixed $valor
     * @param mixed $unidad
     */
    function registro_resultado_celda_html($valor, $unidad, int $opcionId = 3, bool $mostrarMedidaSoloEnReferencia = false): string
    {
        $u = registro_unidad_para_resultado($unidad, $mostrarMedidaSoloEnReferencia);
        $valorStr = (string) ($valor ?? '');

        if (registro_opcion_es_texto_rico($opcionId) || registro_opcion_es_texto_fijo($opcionId) || registro_valor_contiene_html_rico($valorStr)) {
            $html = registro_sanitizar_html_rico($valorStr);
            if ($html === '') {
                return esc('-');
            }
            $suffix = $u !== '' ? ' <strong>' . esc($u) . '</strong>' : '';
            $class = registro_opcion_es_texto_fijo($opcionId) ? 'resultado-texto-fijo resultado-texto-rico' : 'resultado-texto-rico';

            return '<div class="' . $class . ' text-start d-inline-block">' . $html . $suffix . '</div>';
        }

        return registro_resultado_con_unidad_html($valor, $u);
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

if (! function_exists('registro_extraer_rango_numerico_de_valor')) {
    /**
     * Extrae mín./máx. de un valor de select tipo rango (ej. "0 - 1" o "0 - 1 P.C.M").
     *
     * @return array{min: float, max: float}|null
     */
    function registro_extraer_rango_numerico_de_valor(string $valor, string $unidad = ''): ?array
    {
        $v = trim($valor);
        if ($v === '' || $v === '-') {
            return null;
        }

        $u = trim($unidad);
        if ($u !== '' && preg_match('/\s' . preg_quote($u, '/') . '$/iu', $v)) {
            $v = trim((string) preg_replace('/\s' . preg_quote($u, '/') . '$/iu', '', $v));
        }

        if (preg_match('/^([\d]+(?:[.,]\d+)?)\s*[-–—]\s*([\d]+(?:[.,]\d+)?)$/u', $v, $m)) {
            $selMin = (float) str_replace(',', '.', $m[1]);
            $selMax = (float) str_replace(',', '.', $m[2]);

            return ['min' => min($selMin, $selMax), 'max' => max($selMin, $selMax)];
        }

        $vNorm = str_replace(',', '.', $v);
        if (is_numeric($vNorm)) {
            $n = (float) $vNorm;

            return ['min' => $n, 'max' => $n];
        }

        // Soportar valores cualitativos numéricos con prefijos: ">30", ">=30", "<5", "<=5"
        if (preg_match('/^[<>]\s*=?\s*([\d]+(?:[.,]\d+)?)$/u', $v, $m)) {
            $n = (float) str_replace(',', '.', $m[1]);

            // Tratar como valor numérico puntual (min === max) para poder comparar
            return ['min' => $n, 'max' => $n];
        }

        return null;
    }
}

if (! function_exists('registro_interpretacion_referencial_desde_rango')) {
    /**
     * @return array{label: string, nivel: 'alto'|'normal'|'bajo'}
     */
    function registro_interpretacion_referencial_desde_rango(float $valMin, float $valMax, float $refMin, float $refMax): array
    {
        if ($valMax > $refMax) {
            return ['label' => 'Alto', 'nivel' => 'alto'];
        }
        if ($valMin < $refMin) {
            return ['label' => 'Bajo', 'nivel' => 'bajo'];
        }

        return ['label' => 'Normal', 'nivel' => 'normal'];
    }
}

if (! function_exists('registro_interpretacion_referencial_etiqueta')) {
    /**
     * Etiqueta Alto / Normal / Bajo para viewreport según valor numérico vs rango referencial.
     * Solo aplica con valor numérico y ambos límites definidos (misma regla que el coloreado en reporte).
     *
     * @return array{label: string, nivel: 'alto'|'normal'|'bajo'}|null
     */
    function registro_interpretacion_referencial_etiqueta($valor, $min, $max): ?array
    {
        $minStr = trim((string) ($min ?? ''));
        $maxStr = trim((string) ($max ?? ''));
        if ($minStr === '' || $maxStr === '') {
            return null;
        }

        $valorStr = trim((string) ($valor ?? ''));
        if ($valorStr === '' || $valorStr === '-') {
            return null;
        }

        $valorNorm = str_replace(',', '.', $valorStr);
        if (! is_numeric($valorNorm)) {
            return null;
        }

        $refMin = (float) str_replace(',', '.', $minStr);
        $refMax = (float) str_replace(',', '.', $maxStr);
        $v = (float) $valorNorm;

        return registro_interpretacion_referencial_desde_rango($v, $v, $refMin, $refMax);
    }
}

if (! function_exists('registro_interpretacion_referencial_etiqueta_viewreport')) {
    /**
     * Interpretación para viewreport: numérico directo o rango de un select (sin unidad de medida).
     *
     * @return array{label: string, nivel: 'alto'|'normal'|'bajo'}|null
     */
    function registro_interpretacion_referencial_etiqueta_viewreport($valor, $min, $max, $unidad = null, int $opcionId = 3): ?array
    {
        $minStr = trim((string) ($min ?? ''));
        $maxStr = trim((string) ($max ?? ''));
        if ($minStr === '' || $maxStr === '') {
            return null;
        }

        $refMin = (float) str_replace(',', '.', $minStr);
        $refMax = (float) str_replace(',', '.', $maxStr);

        if (registro_opcion_es_select($opcionId)) {
            $rango = registro_extraer_rango_numerico_de_valor((string) ($valor ?? ''), (string) ($unidad ?? ''));
            if ($rango === null) {
                return null;
            }

            return registro_interpretacion_referencial_desde_rango($rango['min'], $rango['max'], $refMin, $refMax);
        }

        return registro_interpretacion_referencial_etiqueta($valor, $min, $max);
    }
}

if (! function_exists('registro_interpretacion_referencial_clase_resultado')) {
    /**
     * Clase CSS del resultado en viewreport cuando la columna Interpretación está activa.
     */
    function registro_interpretacion_referencial_clase_resultado(?array $interpretacion): string
    {
        if ($interpretacion === null) {
            return 'normal';
        }

        return match ($interpretacion['nivel']) {
            'alto' => 'report-interpretacion-alto',
            'bajo' => 'report-interpretacion-bajo',
            default => 'normal',
        };
    }
}

if (! function_exists('registro_origen_prueba_es_derivacion')) {
    /**
     * Indica si la orden usa precios de derivación (origen_prueba = 1).
     *
     * @param object|array<string,mixed>|null $row
     */
    function registro_origen_prueba_es_derivacion(object|array|null $row): bool
    {
        if ($row === null) {
            return false;
        }
        $val = is_object($row) ? ($row->origen_prueba ?? 0) : ($row['origen_prueba'] ?? 0);

        return (int) $val === 1;
    }
}

if (! function_exists('registro_origen_prueba_label')) {
    /**
     * Etiqueta de origen de la orden para listados (propio vs derivación).
     *
     * @param object|array<string,mixed>|null $row
     */
    function registro_origen_prueba_label(object|array|null $row): string
    {
        return registro_origen_prueba_es_derivacion($row) ? 'Derivación' : 'Propio';
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

if (! function_exists('report_image_dompdf_src')) {
    /**
     * Ruta absoluta de imagen para Dompdf (evita base64 en el HTML).
     */
    function report_image_dompdf_src(string $relativePath): string
    {
        $relativePath = str_replace('\\', '/', trim($relativePath));
        if ($relativePath === '' || str_contains($relativePath, '..')) {
            return '';
        }
        $full = FCPATH . str_replace('/', DIRECTORY_SEPARATOR, $relativePath);
        if (! is_file($full) || ! is_readable($full)) {
            return '';
        }
        $resolved = realpath($full);

        return str_replace('\\', '/', $resolved !== false ? $resolved : $full);
    }
}

if (! function_exists('report_image_src_for_variant')) {
    /**
     * PDF Dompdf: ruta de archivo. Impresión HTML: data URI.
     */
    function report_image_src_for_variant(string $relativePath, string $variant = 'pdf'): string
    {
        $relativePath = trim($relativePath);
        if ($relativePath === '') {
            return '';
        }

        return ($variant === 'pdf')
            ? report_image_dompdf_src($relativePath)
            : report_image_data_uri($relativePath);
    }
}

if (! function_exists('report_pdf_img_src_attr')) {
    /**
     * Valor seguro para src="" sin codificar la ruta (Dompdf no lee &#x2F; en file paths).
     */
    function report_pdf_img_src_attr(string $src): string
    {
        $src = trim($src);
        if ($src === '') {
            return '';
        }

        return str_replace('"', '&quot;', $src);
    }
}
