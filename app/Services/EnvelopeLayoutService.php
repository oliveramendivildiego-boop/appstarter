<?php

namespace App\Services;

/**
 * Diseño de plantillas de sobres (matriz + tamaños).
 * Los campos de datos reutilizan los mismos element_type que el PDF de resultados.
 */
class EnvelopeLayoutService
{
    public const LAYOUT_VERSION = 1;

    public const COLUMN_MIN = 1;

    public const COLUMN_MAX = 8;

    public const ROW_MIN = 1;

    public const ROW_MAX = 12;

    /** Separación uniforme entre campos apilados en la misma celda (mm). */
    public const STACK_GAP_MM = 2;

    /** Conversión tipográfica pt → mm. */
    public const PT_TO_MM = 25.4 / 72;

    /** @var list<string> */
    public const ALLOWED_TEXT_FLOWS = ['horizontal', 'vertical_down', 'vertical_up'];

    /** @var list<string> */
    public const ALLOWED_ELEMENT_TYPES = [
        'logo',
        'lab_company',
        'lab_address',
        'lab_phone',
        'lab_email',
        'lab_website',
        'paciente_institucion',
        'qr',
        'codigo_barras',
        'custom_text',
        'custom_image',
        'paciente_nombre',
        'paciente_genero',
        'paciente_edad',
        'paciente_telefono',
        'diagnostico_presuntivo',
        'medico',
        'fecha_recepcion',
        'fecha_reporte',
        'numero_orden',
    ];

    /**
     * Tamaños estándar de sobre (mm).
     *
     * @return array<string, array{label: string, width_mm: float, height_mm: float}>
     */
    public static function envelopeSizes(): array
    {
        return [
            'dl'      => ['label' => 'DL (220 × 110 mm)', 'width_mm' => 220, 'height_mm' => 110],
            'num10'   => ['label' => 'Comercial #10 (241 × 105 mm)', 'width_mm' => 241, 'height_mm' => 105],
            'c5'      => ['label' => 'C5 (229 × 162 mm)', 'width_mm' => 229, 'height_mm' => 162],
            'c4'      => ['label' => 'C4 (324 × 229 mm)', 'width_mm' => 324, 'height_mm' => 229],
            'a4_fold' => ['label' => 'A4 doblado ventana (210 × 99 mm)', 'width_mm' => 210, 'height_mm' => 99],
            'custom'  => ['label' => 'Personalizado', 'width_mm' => 220, 'height_mm' => 110],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function getDefaultLayout(): array
    {
        return [
            'version'   => self::LAYOUT_VERSION,
            'size_key'  => 'dl',
            'width_mm'  => 220,
            'height_mm' => 110,
            'columns'   => 4,
            'rows'      => 3,
            'items'     => [],
            'merges'    => [],
        ];
    }

    /**
     * @param object|array<string, mixed>|null $template
     *
     * @return array<string, mixed>
     */
    public function layoutJsonForEditor($template): array
    {
        $raw = '';
        if (is_object($template) && isset($template->layout_json)) {
            $raw = (string) $template->layout_json;
        } elseif (is_array($template) && isset($template['layout_json'])) {
            $raw = (string) $template['layout_json'];
        }

        if ($raw === '') {
            return $this->getDefaultLayout();
        }

        return $this->normalizeLayout($raw);
    }

    /**
     * @param string|array<string, mixed> $input
     *
     * @return array<string, mixed>
     */
    public function normalizeLayout($input): array
    {
        $decoded = is_array($input) ? $input : json_decode((string) $input, true);
        if (! is_array($decoded)) {
            return $this->getDefaultLayout();
        }

        $def = $this->getDefaultLayout();
        $sizes = self::envelopeSizes();
        $sizeKey = (string) ($decoded['size_key'] ?? $def['size_key']);
        if (! isset($sizes[$sizeKey])) {
            $sizeKey = 'dl';
        }

        $width = (float) ($decoded['width_mm'] ?? $sizes[$sizeKey]['width_mm']);
        $height = (float) ($decoded['height_mm'] ?? $sizes[$sizeKey]['height_mm']);
        if ($sizeKey !== 'custom') {
            $width  = (float) $sizes[$sizeKey]['width_mm'];
            $height = (float) $sizes[$sizeKey]['height_mm'];
        } else {
            $width  = max(50, min(500, $width));
            $height = max(50, min(500, $height));
        }

        $cols = max(self::COLUMN_MIN, min(self::COLUMN_MAX, (int) ($decoded['columns'] ?? $def['columns'])));
        $rows = max(self::ROW_MIN, min(self::ROW_MAX, (int) ($decoded['rows'] ?? $def['rows'])));

        $items = [];
        if (isset($decoded['items']) && is_array($decoded['items'])) {
            foreach ($decoded['items'] as $item) {
                if (! is_array($item)) {
                    continue;
                }
                $norm = $this->normalizeItem($item, $cols, $rows);
                if ($norm !== null) {
                    $items[] = $norm;
                }
            }
            $items = self::normalizeStackOrders($items);
        }

        $merges = [];
        if (isset($decoded['merges']) && is_array($decoded['merges'])) {
            foreach ($decoded['merges'] as $merge) {
                if (! is_array($merge)) {
                    continue;
                }
                $norm = self::normalizeMerge($merge, $cols, $rows);
                if ($norm !== null) {
                    $merges[] = $norm;
                }
            }
        }

        return [
            'version'   => self::LAYOUT_VERSION,
            'size_key'  => $sizeKey,
            'width_mm'  => $width,
            'height_mm' => $height,
            'columns'   => $cols,
            'rows'      => $rows,
            'items'     => $items,
            'merges'    => $merges,
        ];
    }

    /**
     * @param array<string, mixed> $merge
     *
     * @return array{row: int, col: int, col_span: int, row_span: int}|null
     */
    public static function normalizeMerge(array $merge, int $cols, int $rows): ?array
    {
        $row = max(0, min($rows - 1, (int) ($merge['row'] ?? 0)));
        $col = max(0, min($cols - 1, (int) ($merge['col'] ?? 0)));
        $colSpan = max(1, min($cols - $col, (int) ($merge['col_span'] ?? 1)));
        $rowSpan = max(1, min($rows - $row, (int) ($merge['row_span'] ?? 1)));
        if ($colSpan < 2 && $rowSpan < 2) {
            return null;
        }

        return [
            'row'       => $row,
            'col'       => $col,
            'col_span'  => $colSpan,
            'row_span'  => $rowSpan,
        ];
    }

    /**
     * Indica si la celda (r,c) está cubierta por un combine distinto al origen.
     */
    public static function isMatrixCellCovered(int $r, int $c, array $items, array $merges): bool
    {
        foreach ($items as $item) {
            if (! is_array($item)) {
                continue;
            }
            $ir = (int) ($item['row'] ?? 0);
            $ic = (int) ($item['col'] ?? 0);
            $cs = max(1, (int) ($item['col_span'] ?? 1));
            $rs = max(1, (int) ($item['row_span'] ?? 1));
            if ($r >= $ir && $r < $ir + $rs && $c >= $ic && $c < $ic + $cs) {
                return ! ($ir === $r && $ic === $c);
            }
        }
        foreach ($merges as $merge) {
            if (! is_array($merge)) {
                continue;
            }
            $mr = (int) ($merge['row'] ?? 0);
            $mc = (int) ($merge['col'] ?? 0);
            $cs = max(1, (int) ($merge['col_span'] ?? 1));
            $rs = max(1, (int) ($merge['row_span'] ?? 1));
            if ($r >= $mr && $r < $mr + $rs && $c >= $mc && $c < $mc + $cs) {
                return ! ($mr === $r && $mc === $c);
            }
        }

        return false;
    }

    /**
     * @return array{col_span: int, row_span: int}
     */
    public static function matrixCellSpan(int $r, int $c, array $items, array $merges): array
    {
        foreach ($items as $item) {
            if (! is_array($item)) {
                continue;
            }
            if ((int) ($item['row'] ?? -1) === $r && (int) ($item['col'] ?? -1) === $c) {
                return [
                    'col_span' => max(1, (int) ($item['col_span'] ?? 1)),
                    'row_span' => max(1, (int) ($item['row_span'] ?? 1)),
                ];
            }
        }
        foreach ($merges as $merge) {
            if (! is_array($merge)) {
                continue;
            }
            if ((int) ($merge['row'] ?? -1) === $r && (int) ($merge['col'] ?? -1) === $c) {
                return [
                    'col_span' => max(1, (int) ($merge['col_span'] ?? 1)),
                    'row_span' => max(1, (int) ($merge['row_span'] ?? 1)),
                ];
            }
        }

        return ['col_span' => 1, 'row_span' => 1];
    }

    /**
     * @param array<string, mixed> $item
     *
     * @return array<string, mixed>|null
     */
    private function normalizeItem(array $item, int $cols, int $rows): ?array
    {
        $type = (string) ($item['element_type'] ?? '');
        if (! in_array($type, self::ALLOWED_ELEMENT_TYPES, true)) {
            return null;
        }

        $row = max(0, min($rows - 1, (int) ($item['row'] ?? 0)));
        $col = max(0, min($cols - 1, (int) ($item['col'] ?? 0)));
        $colSpan = max(1, min($cols - $col, (int) ($item['col_span'] ?? 1)));
        $rowSpan = max(1, min($rows - $row, (int) ($item['row_span'] ?? 1)));

        $uid = trim((string) ($item['uid'] ?? ''));
        if ($uid === '') {
            $uid = ReportPdfLayoutService::generateInstanceUid();
        }

        $textAlign = strtolower(trim((string) ($item['text_align'] ?? 'left')));
        if (! in_array($textAlign, ['left', 'center', 'right'], true)) {
            $textAlign = 'left';
        }
        $verticalAlign = strtolower(trim((string) ($item['vertical_align'] ?? 'top')));
        if (! in_array($verticalAlign, ['top', 'middle', 'bottom'], true)) {
            $verticalAlign = 'top';
        }
        $textFlow = strtolower(trim((string) ($item['text_flow'] ?? 'horizontal')));
        if (! in_array($textFlow, self::ALLOWED_TEXT_FLOWS, true)) {
            $textFlow = 'horizontal';
        }

        $out = [
            'uid'            => $uid,
            'element_type'   => $type,
            'row'            => $row,
            'col'            => $col,
            'col_span'       => $colSpan,
            'row_span'       => $rowSpan,
            'enabled'        => ! isset($item['enabled']) || (bool) $item['enabled'],
            'show_label'     => ! isset($item['show_label']) || (bool) $item['show_label'],
            'custom_label'   => trim((string) ($item['custom_label'] ?? '')),
            'font_size_pt'   => max(6, min(24, (int) ($item['font_size_pt'] ?? 10))),
            'text_align'     => $textAlign,
            'vertical_align' => $verticalAlign,
            'text_flow'      => $textFlow,
            'margin_mm'      => self::normalizeMarginMm($item['margin_mm'] ?? null),
            'font_weight'    => ($item['font_weight'] ?? 'normal') === 'bold' ? 'bold' : 'normal',
            'stack_order'    => max(0, (int) ($item['stack_order'] ?? 0)),
        ];

        if ($type === 'custom_text') {
            $out['custom_value'] = trim((string) ($item['custom_value'] ?? ''));
        }

        if ($type === 'custom_image') {
            $rel = trim((string) ($item['image_file'] ?? ''));
            $out['image_file']     = self::sanitizeImageRelativePath($rel) ?? '';
            $out['width_percent']  = max(5, min(100, (int) ($item['width_percent'] ?? 80)));
            $out['height_percent'] = max(5, min(100, (int) ($item['height_percent'] ?? 60)));
        }

        if ($type === 'qr') {
            $out['qr_size_mm'] = max(15, min(80, (int) ($item['qr_size_mm'] ?? 35)));
        }

        if ($type === 'codigo_barras') {
            $out['barcode_height_mm'] = max(8, min(40, (int) ($item['barcode_height_mm'] ?? 14)));
            $out['barcode_width_mm']  = max(25, min(120, (int) ($item['barcode_width_mm'] ?? 60)));
        }

        return $out;
    }

    /**
     * Orden estable de campos apilados dentro de cada celda (0 = arriba / primero).
     *
     * @param list<array<string, mixed>> $items
     *
     * @return list<array<string, mixed>>
     */
    public static function normalizeStackOrders(array $items): array
    {
        if ($items === []) {
            return [];
        }

        $byCell = [];
        foreach ($items as $idx => $it) {
            $key = ((int) ($it['row'] ?? 0)) . ',' . ((int) ($it['col'] ?? 0));
            if (! isset($byCell[$key])) {
                $byCell[$key] = [];
            }
            $byCell[$key][] = [
                'idx'   => $idx,
                'order' => (int) ($it['stack_order'] ?? 0),
                'seq'   => $idx,
            ];
        }

        foreach ($byCell as $entries) {
            usort($entries, static function (array $a, array $b): int {
                if ($a['order'] !== $b['order']) {
                    return $a['order'] <=> $b['order'];
                }

                return $a['seq'] <=> $b['seq'];
            });
            foreach ($entries as $pos => $entry) {
                $items[$entry['idx']]['stack_order'] = $pos;
            }
        }

        return $items;
    }

    /**
     * @param mixed $raw
     *
     * @return array{top: float, right: float, bottom: float, left: float}
     */
    public static function normalizeMarginMm($raw): array
    {
        $out = ['top' => 0.0, 'right' => 0.0, 'bottom' => 0.0, 'left' => 0.0];
        if (! is_array($raw)) {
            return $out;
        }
        foreach (array_keys($out) as $side) {
            $out[$side] = max(0.0, min(20.0, (float) ($raw[$side] ?? 0)));
        }

        return $out;
    }

    public static function sanitizeImageRelativePath(string $rel): ?string
    {
        $rel = str_replace('\\', '/', trim($rel));
        if ($rel === '' || strpos($rel, '..') !== false) {
            return null;
        }
        if (! preg_match('#^uploads/envelope_templates/\d+/[a-zA-Z0-9_.-]+$#', $rel)) {
            return null;
        }

        return $rel;
    }

    /**
     * Etiquetas propias del editor (no dependen de otras clases).
     *
     * @return array<string, string>
     */
    public static function defaultFieldLabels(): array
    {
        return [
            'logo'                   => 'Logo del laboratorio',
            'lab_company'            => 'Nombre del laboratorio',
            'lab_address'            => 'Dirección',
            'lab_phone'                => 'Teléfono',
            'lab_email'                => 'Correo electrónico',
            'lab_website'              => 'Sitio web',
            'paciente_institucion'     => 'Institución del paciente',
            'qr'                       => 'Código QR de la prueba',
            'codigo_barras'            => 'Código de barras',
            'custom_text'              => 'Texto libre',
            'custom_image'             => 'Imagen personalizada',
            'paciente_nombre'          => 'Nombre del paciente',
            'paciente_genero'          => 'Género del paciente',
            'paciente_edad'            => 'Edad del paciente',
            'paciente_telefono'        => 'Teléfono del paciente',
            'diagnostico_presuntivo'   => 'Diagnóstico presuntivo',
            'medico'                   => 'Médico tratante',
            'fecha_recepcion'          => 'Fecha de recepción',
            'fecha_reporte'            => 'Fecha de reporte',
            'numero_orden'             => 'Número de orden',
        ];
    }

    /**
     * Etiquetas para paleta del editor (PDF + imagen, con respaldo local).
     *
     * @return array<string, string>
     */
    public static function elementTypeLabels(): array
    {
        $out = self::defaultFieldLabels();

        try {
            $all = ReportPdfLayoutService::elementTypeLabels();
            foreach (self::ALLOWED_ELEMENT_TYPES as $type) {
                if (isset($all[$type]) && $all[$type] !== '') {
                    $out[$type] = $all[$type];
                }
            }
        } catch (\Throwable $e) {
            // Mantener etiquetas por defecto
        }

        return $out;
    }

    /**
     * @return array<string, string>
     */
    public static function elementPreviewSamples(): array
    {
        $out = [
            'logo'                 => '[Logo]',
            'lab_company'          => 'Laboratorio Clínico',
            'lab_address'          => 'Calle 123, Ciudad',
            'lab_phone'            => '555-0100',
            'lab_email'            => 'info@lab.com',
            'lab_website'          => 'www.lab.com',
            'paciente_institucion' => 'Institución',
            'qr'                   => '[QR]',
            'codigo_barras'        => '[Código de barras]',
            'custom_text'          => 'Texto de ejemplo',
            'custom_image'         => '[Imagen]',
            'paciente_nombre'      => 'Juan Pérez García',
            'paciente_genero'      => 'Masculino',
            'paciente_edad'        => '35 años',
            'paciente_telefono'    => '555-1234',
            'diagnostico_presuntivo' => 'Control general',
            'medico'               => 'Dra. Ana López',
            'fecha_recepcion'      => '28/05/2026',
            'fecha_reporte'        => '28/05/2026',
            'numero_orden'         => 'ORD-001',
        ];

        try {
            $all = ReportPdfLayoutService::elementPreviewSamples();
            foreach (self::ALLOWED_ELEMENT_TYPES as $type) {
                if (isset($all[$type]) && $all[$type] !== '') {
                    $out[$type] = $all[$type];
                }
            }
        } catch (\Throwable $e) {
            // Mantener muestras por defecto
        }

        return $out;
    }

    /**
     * URL para servir imagen desde writable vía controlador.
     */
    public static function imagePublicUrl(int $templateId, string $relativePath): string
    {
        $safe = self::sanitizeImageRelativePath($relativePath);
        if ($safe === null || $templateId < 1) {
            return '';
        }

        return site_url('config/sobres/media/' . $templateId . '/' . basename($safe));
    }
}

