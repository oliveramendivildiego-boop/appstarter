<?php

namespace App\Services;

/**
 * Diseño tipográfico y disposición de campos del comprobante de pago (PDF).
 */
class ComprobanteLayoutService
{
    public const LAYOUT_VERSION = 1;

    public const COLUMN_MIN = 1;

    public const COLUMN_MAX = 4;

    public const ROW_MIN = 1;

    public const ROW_MAX = 8;

    /** @var list<string> */
    public const ALLOWED_FIELD_TYPES = [
        'paciente_nombre',
        'paciente_ci',
        'paciente_telefono',
        'medico',
        'institucion',
    ];

    /** @var list<string> */
    public const ALLOWED_FONT_FAMILIES = ['DejaVu Sans', 'Helvetica', 'Arial', 'Times New Roman', 'Courier New'];

    /** @var list<string> */
    public const ALLOWED_FONT_WEIGHTS = ['normal', 'bold', '400', '500', '600', '700', '800'];

    /** @var list<string> */
    public const ALLOWED_FONT_STYLES = ['normal', 'italic', 'oblique'];

    /** @var list<string> */
    public const ALLOWED_TEXT_TRANSFORMS = ['none', 'uppercase', 'lowercase', 'capitalize'];

    /** Etiquetas legibles para la matriz de estilos. */
    public const STYLE_ELEMENT_LABELS = [
        'body'               => 'Texto base del documento',
        'brand_name'         => 'Nombre del laboratorio',
        'brand_tagline'      => 'Subtítulo bajo el nombre',
        'receipt_badge_title'=> 'Etiqueta del recuadro (Recibo de pago)',
        'receipt_badge_orden'=> 'Número de orden en recuadro',
        'receipt_badge_fecha'=> 'Fecha en recuadro',
        'section_title'      => 'Títulos de sección',
        'pair_label'         => 'Etiquetas de datos (Paciente:, etc.)',
        'pair_value'         => 'Valores de datos',
        'items_header'       => 'Encabezado tabla de conceptos',
        'items_body'         => 'Cuerpo tabla de conceptos',
        'items_num'          => 'Columna importes',
        'totals_label'       => 'Etiquetas de totales',
        'totals_value'       => 'Valores de totales',
        'totals_final'       => 'Fila saldo / total final',
        'pay_method'         => 'Forma de pago',
        'footer'             => 'Pie del comprobante',
    ];

    /** Etiquetas por defecto de campos arrastrables. */
    public const FIELD_DEFAULT_LABELS = [
        'paciente_nombre'  => 'Paciente:',
        'paciente_ci'      => 'Documento de identidad:',
        'paciente_telefono'=> 'Teléfono:',
        'medico'           => 'Médico referente:',
        'institucion'      => 'Institución / procedencia:',
    ];

    /** Datos de ejemplo para vista previa. */
    public const FIELD_PREVIEW_SAMPLES = [
        'paciente_nombre'  => 'María Fernández López',
        'paciente_ci'      => '1234567 LP',
        'paciente_telefono'=> '70123456',
        'medico'           => 'Dr. Juan Pérez',
        'institucion'      => 'Clínica Central — Descuento 10%',
    ];

    /** @var array<string, array<string, mixed>> */
    public const DEFAULT_STYLES = [
        'body' => [
            'font_family' => 'DejaVu Sans', 'font_size_pt' => 10.5, 'font_weight' => 'normal',
            'font_style' => 'normal', 'color' => '#1E293B', 'text_transform' => 'none',
        ],
        'brand_name' => [
            'font_family' => 'DejaVu Sans', 'font_size_pt' => 16.0, 'font_weight' => 'bold',
            'font_style' => 'normal', 'color' => '#1E293B', 'text_transform' => 'none',
        ],
        'brand_tagline' => [
            'font_family' => 'DejaVu Sans', 'font_size_pt' => 8.5, 'font_weight' => 'normal',
            'font_style' => 'normal', 'color' => '#64748B', 'text_transform' => 'uppercase',
        ],
        'receipt_badge_title' => [
            'font_family' => 'DejaVu Sans', 'font_size_pt' => 8.0, 'font_weight' => 'bold',
            'font_style' => 'normal', 'color' => '#0F766E', 'text_transform' => 'uppercase',
        ],
        'receipt_badge_orden' => [
            'font_family' => 'DejaVu Sans', 'font_size_pt' => 14.0, 'font_weight' => 'bold',
            'font_style' => 'normal', 'color' => '#134E4A', 'text_transform' => 'none',
        ],
        'receipt_badge_fecha' => [
            'font_family' => 'DejaVu Sans', 'font_size_pt' => 8.5, 'font_weight' => 'normal',
            'font_style' => 'normal', 'color' => '#475569', 'text_transform' => 'none',
        ],
        'section_title' => [
            'font_family' => 'DejaVu Sans', 'font_size_pt' => 7.5, 'font_weight' => 'bold',
            'font_style' => 'normal', 'color' => '#0F766E', 'text_transform' => 'uppercase',
        ],
        'pair_label' => [
            'font_family' => 'DejaVu Sans', 'font_size_pt' => 10.0, 'font_weight' => '600',
            'font_style' => 'normal', 'color' => '#475569', 'text_transform' => 'none',
        ],
        'pair_value' => [
            'font_family' => 'DejaVu Sans', 'font_size_pt' => 10.0, 'font_weight' => 'normal',
            'font_style' => 'normal', 'color' => '#1E293B', 'text_transform' => 'none',
        ],
        'items_header' => [
            'font_family' => 'DejaVu Sans', 'font_size_pt' => 7.5, 'font_weight' => 'bold',
            'font_style' => 'normal', 'color' => '#FFFFFF', 'text_transform' => 'uppercase',
        ],
        'items_body' => [
            'font_family' => 'DejaVu Sans', 'font_size_pt' => 9.5, 'font_weight' => 'normal',
            'font_style' => 'normal', 'color' => '#1E293B', 'text_transform' => 'none',
        ],
        'items_num' => [
            'font_family' => 'DejaVu Sans', 'font_size_pt' => 9.5, 'font_weight' => '600',
            'font_style' => 'normal', 'color' => '#1E293B', 'text_transform' => 'none',
        ],
        'totals_label' => [
            'font_family' => 'DejaVu Sans', 'font_size_pt' => 9.5, 'font_weight' => 'normal',
            'font_style' => 'normal', 'color' => '#64748B', 'text_transform' => 'none',
        ],
        'totals_value' => [
            'font_family' => 'DejaVu Sans', 'font_size_pt' => 9.5, 'font_weight' => '600',
            'font_style' => 'normal', 'color' => '#334155', 'text_transform' => 'none',
        ],
        'totals_final' => [
            'font_family' => 'DejaVu Sans', 'font_size_pt' => 10.5, 'font_weight' => 'bold',
            'font_style' => 'normal', 'color' => '#FFFFFF', 'text_transform' => 'none',
        ],
        'pay_method' => [
            'font_family' => 'DejaVu Sans', 'font_size_pt' => 9.5, 'font_weight' => 'normal',
            'font_style' => 'normal', 'color' => '#92400E', 'text_transform' => 'none',
        ],
        'footer' => [
            'font_family' => 'DejaVu Sans', 'font_size_pt' => 8.0, 'font_weight' => 'normal',
            'font_style' => 'normal', 'color' => '#64748B', 'text_transform' => 'none',
        ],
    ];

    /**
     * @return array<string, mixed>
     */
    public function getDefaultLayout(): array
    {
        return [
            'version'      => self::LAYOUT_VERSION,
            'styles'       => self::DEFAULT_STYLES,
            'client_grid'  => [
                'columns' => 2,
                'rows'    => 3,
                'items'   => [
                    ['uid' => 'd1', 'element_type' => 'paciente_nombre', 'row' => 0, 'col' => 0, 'col_span' => 1, 'show_label' => true, 'custom_label' => 'Paciente:'],
                    ['uid' => 'd2', 'element_type' => 'paciente_ci', 'row' => 0, 'col' => 1, 'col_span' => 1, 'show_label' => true, 'custom_label' => 'Documento de identidad:'],
                    ['uid' => 'd3', 'element_type' => 'paciente_telefono', 'row' => 1, 'col' => 0, 'col_span' => 1, 'show_label' => true, 'custom_label' => 'Teléfono:'],
                    ['uid' => 'd4', 'element_type' => 'medico', 'row' => 1, 'col' => 1, 'col_span' => 1, 'show_label' => true, 'custom_label' => 'Médico referente:'],
                    ['uid' => 'd5', 'element_type' => 'institucion', 'row' => 2, 'col' => 0, 'col_span' => 2, 'show_label' => true, 'custom_label' => 'Institución / procedencia:'],
                ],
            ],
        ];
    }

    /**
     * @param array<string, mixed> $config
     *
     * @return array<string, mixed>
     */
    public function layoutFromConfig(array $config): array
    {
        $raw = trim((string) ($config['comprobante_layout_json'] ?? ''));
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
        $styles = [];
        foreach (self::STYLE_ELEMENT_LABELS as $key => $_label) {
            $styles[$key] = $this->normalizeStyleBlock($decoded['styles'][$key] ?? null, self::DEFAULT_STYLES[$key] ?? self::DEFAULT_STYLES['body']);
        }

        $gridRaw = is_array($decoded['client_grid'] ?? null) ? $decoded['client_grid'] : [];
        $cols = max(self::COLUMN_MIN, min(self::COLUMN_MAX, (int) ($gridRaw['columns'] ?? $def['client_grid']['columns'])));
        $rows = max(self::ROW_MIN, min(self::ROW_MAX, (int) ($gridRaw['rows'] ?? $def['client_grid']['rows'])));

        $items = [];
        if (isset($gridRaw['items']) && is_array($gridRaw['items'])) {
            foreach ($gridRaw['items'] as $item) {
                if (! is_array($item)) {
                    continue;
                }
                $norm = $this->normalizeGridItem($item, $cols, $rows);
                if ($norm !== null) {
                    $items[] = $norm;
                }
            }
        }
        if ($items === []) {
            $items = $def['client_grid']['items'];
        }

        return [
            'version'     => self::LAYOUT_VERSION,
            'styles'      => $styles,
            'client_grid' => [
                'columns' => $cols,
                'rows'    => $rows,
                'items'   => $items,
            ],
        ];
    }

    /**
     * @param mixed $raw
     * @param array<string, mixed> $fallback
     *
     * @return array{font_family: string, font_size_pt: float, font_weight: string, font_style: string, color: string, text_transform: string}
     */
    public function normalizeStyleBlock($raw, array $fallback): array
    {
        $ts = is_array($raw) ? $raw : [];
        $family = (string) ($ts['font_family'] ?? $fallback['font_family'] ?? 'DejaVu Sans');
        if (! in_array($family, self::ALLOWED_FONT_FAMILIES, true)) {
            $family = (string) ($fallback['font_family'] ?? 'DejaVu Sans');
        }
        $size = isset($ts['font_size_pt']) ? (float) $ts['font_size_pt'] : (float) ($fallback['font_size_pt'] ?? 10.0);
        $size = round(max(6.0, min(24.0, $size)), 1);
        $weight = strtolower(trim((string) ($ts['font_weight'] ?? $fallback['font_weight'] ?? 'normal')));
        if (! in_array($weight, self::ALLOWED_FONT_WEIGHTS, true)) {
            $weight = (string) ($fallback['font_weight'] ?? 'normal');
        }
        $style = strtolower(trim((string) ($ts['font_style'] ?? $fallback['font_style'] ?? 'normal')));
        if (! in_array($style, self::ALLOWED_FONT_STYLES, true)) {
            $style = (string) ($fallback['font_style'] ?? 'normal');
        }
        $color = strtoupper(trim((string) ($ts['color'] ?? $fallback['color'] ?? '#1E293B')));
        if (! preg_match('/^#[0-9A-F]{6}$/', $color)) {
            $color = strtoupper((string) ($fallback['color'] ?? '#1E293B'));
        }
        $transform = strtolower(trim((string) ($ts['text_transform'] ?? $fallback['text_transform'] ?? 'none')));
        if (! in_array($transform, self::ALLOWED_TEXT_TRANSFORMS, true)) {
            $transform = (string) ($fallback['text_transform'] ?? 'none');
        }

        return [
            'font_family'    => $family,
            'font_size_pt'   => $size,
            'font_weight'    => $weight,
            'font_style'     => $style,
            'color'          => $color,
            'text_transform' => $transform,
        ];
    }

    /**
     * @param array<string, mixed> $item
     *
     * @return array<string, mixed>|null
     */
    private function normalizeGridItem(array $item, int $cols, int $rows): ?array
    {
        $type = (string) ($item['element_type'] ?? '');
        if (! in_array($type, self::ALLOWED_FIELD_TYPES, true)) {
            return null;
        }
        $row = max(0, min($rows - 1, (int) ($item['row'] ?? 0)));
        $col = max(0, min($cols - 1, (int) ($item['col'] ?? 0)));
        $colSpan = max(1, min($cols - $col, (int) ($item['col_span'] ?? 1)));

        return [
            'uid'          => trim((string) ($item['uid'] ?? '')) ?: 'c' . bin2hex(random_bytes(4)),
            'element_type' => $type,
            'row'          => $row,
            'col'          => $col,
            'col_span'     => $colSpan,
            'show_label'   => ($item['show_label'] ?? true) !== false,
            'custom_label' => mb_substr(trim((string) ($item['custom_label'] ?? self::FIELD_DEFAULT_LABELS[$type] ?? '')), 0, 80),
        ];
    }

    /**
     * @return array<string, string>
     */
    public function fieldTypeLabels(): array
    {
        $out = [];
        foreach (self::ALLOWED_FIELD_TYPES as $type) {
            $out[$type] = match ($type) {
                'paciente_nombre'   => 'Nombre del paciente',
                'paciente_ci'       => 'Documento de identidad',
                'paciente_telefono' => 'Teléfono',
                'medico'            => 'Médico referente',
                'institucion'       => 'Institución / procedencia',
                default             => $type,
            };
        }

        return $out;
    }

    /**
     * Genera reglas CSS para el PDF según el layout y colores globales.
     *
     * @param array<string, mixed> $layout
     * @param array{primary?:string,secondary?:string,text?:string} $colors
     *
     * @return string CSS sin etiqueta <style>
     */
    public function buildPdfCss(array $layout, array $colors = []): string
    {
        $styles = is_array($layout['styles'] ?? null) ? $layout['styles'] : self::DEFAULT_STYLES;
        $primary = strtoupper(trim((string) ($colors['primary'] ?? '#0F766E')));
        $secondary = strtoupper(trim((string) ($colors['secondary'] ?? '#134E4A')));
        $text = strtoupper(trim((string) ($colors['text'] ?? '#1E293B')));

        $cssRule = static function (string $selector, array $block, array $overrides = []): string {
            $merged = array_merge($block, $overrides);
            $parts = [
                'font-family:' . ($merged['font_family'] ?? 'DejaVu Sans') . ', Arial, sans-serif',
                'font-size:' . ($merged['font_size_pt'] ?? 10) . 'pt',
                'font-weight:' . ($merged['font_weight'] ?? 'normal'),
                'font-style:' . ($merged['font_style'] ?? 'normal'),
                'color:' . ($merged['color'] ?? '#1E293B'),
                'text-transform:' . ($merged['text_transform'] ?? 'none'),
            ];

            return $selector . '{' . implode(';', $parts) . ';}';
        };

        $body = $styles['body'] ?? self::DEFAULT_STYLES['body'];
        $body['color'] = $text;

        $lines = [
            '@page{margin:14mm 16mm;}',
            '*{box-sizing:border-box;}',
            $cssRule('body', $body),
            'body{line-height:1.45;margin:0;padding:0;}',
            '.accent-bar{height:5px;background:' . $primary . ';margin:0 0 16px 0;}',
            '.doc-header{width:100%;border-collapse:collapse;margin-bottom:18px;}',
            '.doc-header td{vertical-align:top;padding:0;}',
            $cssRule('.brand-name', $styles['brand_name'] ?? self::DEFAULT_STYLES['brand_name'], ['color' => $text]),
            '.brand-name{letter-spacing:-0.02em;margin:0 0 4px 0;}',
            $cssRule('.brand-tagline', $styles['brand_tagline'] ?? self::DEFAULT_STYLES['brand_tagline']),
            '.brand-tagline{letter-spacing:0.12em;margin:0;}',
            '.receipt-badge{text-align:right;}',
            '.receipt-badge-inner{display:inline-block;text-align:right;border:2px solid ' . $primary . ';border-radius:6px;padding:10px 14px;background:#f0fdfa;}',
            $cssRule('.receipt-badge-title', $styles['receipt_badge_title'] ?? self::DEFAULT_STYLES['receipt_badge_title'], ['color' => $primary]),
            '.receipt-badge-title{letter-spacing:0.18em;margin:0 0 6px 0;}',
            $cssRule('.receipt-badge-orden', $styles['receipt_badge_orden'] ?? self::DEFAULT_STYLES['receipt_badge_orden'], ['color' => $secondary]),
            '.receipt-badge-orden{margin:0;}',
            $cssRule('.receipt-badge-fecha', $styles['receipt_badge_fecha'] ?? self::DEFAULT_STYLES['receipt_badge_fecha']),
            '.receipt-badge-fecha{margin:6px 0 0 0;}',
            $cssRule('.section-title', $styles['section_title'] ?? self::DEFAULT_STYLES['section_title'], ['color' => $primary]),
            '.section-title{letter-spacing:0.14em;margin:0 0 8px 0;padding-bottom:4px;border-bottom:1px solid #cbd5e1;}',
            '.panel{background:#f8fafc;border:1px solid #e2e8f0;border-radius:6px;padding:12px 14px;margin-bottom:16px;}',
            'table.pair-table{width:100%;border-collapse:collapse;}',
            'table.pair-table td{vertical-align:top;padding:10px 16px 10px 0;line-height:1.5;}',
            'table.pair-table td:nth-child(2){padding-right:0;padding-left:8px;}',
            'table.pair-table tr:first-child td{padding-top:4px;}',
            $cssRule('.pair-k', $styles['pair_label'] ?? self::DEFAULT_STYLES['pair_label']),
            $cssRule('.pair-v', $styles['pair_value'] ?? self::DEFAULT_STYLES['pair_value'], ['color' => $text]),
            'table.tbl-items{width:100%;border-collapse:collapse;margin:0 0 14px 0;}',
            $cssRule('table.tbl-items thead th', $styles['items_header'] ?? self::DEFAULT_STYLES['items_header'], ['color' => '#FFFFFF']),
            'table.tbl-items thead th{background:' . $secondary . ';letter-spacing:0.08em;padding:9px 10px;text-align:left;}',
            'table.tbl-items thead th:last-child{text-align:right;}',
            $cssRule('table.tbl-items tbody td', $styles['items_body'] ?? self::DEFAULT_STYLES['items_body']),
            'table.tbl-items tbody td{padding:8px 10px;border-bottom:1px solid #e2e8f0;vertical-align:top;}',
            'table.tbl-items tbody tr:nth-child(even) td{background:#fafafa;}',
            $cssRule('table.tbl-items tbody td.num', $styles['items_num'] ?? self::DEFAULT_STYLES['items_num'], ['color' => $text]),
            'table.tbl-items tbody td.num{text-align:right;white-space:nowrap;font-variant-numeric:tabular-nums;}',
            '.totals-wrap{width:100%;margin-top:4px;}',
            '.totals-wrap td{vertical-align:top;}',
            '.totals-box{width:280px;margin-left:auto;border:1px solid #cbd5e1;border-radius:6px;overflow:hidden;}',
            '.totals-box table{width:100%;border-collapse:collapse;}',
            '.totals-box tr td{padding:6px 12px;border-bottom:1px solid #e2e8f0;}',
            '.totals-box tr:last-child td{border-bottom:none;}',
            $cssRule('.totals-box .t-lbl', $styles['totals_label'] ?? self::DEFAULT_STYLES['totals_label']),
            '.totals-box .t-lbl{text-align:left;}',
            $cssRule('.totals-box .t-val', $styles['totals_value'] ?? self::DEFAULT_STYLES['totals_value']),
            '.totals-box .t-val{text-align:right;font-variant-numeric:tabular-nums;white-space:nowrap;}',
            $cssRule('.totals-box tr.total-final td', $styles['totals_final'] ?? self::DEFAULT_STYLES['totals_final'], ['color' => '#FFFFFF']),
            '.totals-box tr.total-final td{background:' . $primary . ';padding:10px 12px;}',
            '.totals-box tr.total-final .t-lbl{color:#ecfdf5;}',
            '.totals-box tr.total-final .t-val{color:#fff;}',
            $cssRule('.pay-method', $styles['pay_method'] ?? self::DEFAULT_STYLES['pay_method']),
            '.pay-method{margin-top:12px;padding:10px 14px;background:#fffbeb;border:1px solid #fde68a;border-radius:6px;}',
            '.pay-method strong{color:#92400e;}',
            $cssRule('.foot', $styles['footer'] ?? self::DEFAULT_STYLES['footer']),
            '.foot{margin-top:22px;padding-top:14px;border-top:1px solid #e2e8f0;text-align:center;line-height:1.5;}',
        ];

        return implode("\n", $lines);
    }

    /**
     * Resuelve el valor de un campo del comprobante.
     *
     * @param object $doc ReciboComprobanteModel|FacturaComprobanteModel
     */
    public function resolveFieldValue(string $type, object $doc, bool $showDoctor = true): string
    {
        return match ($type) {
            'paciente_nombre' => trim((string) ($doc->pacienteNombre ?? '')),
            'paciente_ci' => trim((string) ($doc->pacienteCi ?? '')) !== '' ? trim((string) $doc->pacienteCi) : '—',
            'paciente_telefono' => trim((string) ($doc->pacienteTelefono ?? '')) !== '' ? trim((string) $doc->pacienteTelefono) : '—',
            'medico' => $this->resolveDoctorText($doc, $showDoctor),
            'institucion' => $this->resolveInstitucionText($doc),
            default => '',
        };
    }

    /**
     * @param object $doc
     */
    private function resolveDoctorText(object $doc, bool $showDoctor): string
    {
        if (! $showDoctor) {
            return '';
        }
        $g = (int) ($doc->doctorGender ?? 0);
        $titulo = $g === 1 ? 'Dr. ' : ($g === 2 ? 'Dra. ' : '');
        $nombre = trim((string) ($doc->doctorNombre ?? ''));

        return $nombre !== '' ? $titulo . $nombre : '—';
    }

    /**
     * @param object $doc
     */
    private function resolveInstitucionText(object $doc): string
    {
        $inst = trim((string) ($doc->institucionNombre ?? ''));
        if ($inst === '') {
            return '—';
        }
        $pct = (float) ($doc->institucionDescuentoPct ?? 0);
        if ($pct > 0) {
            return $inst . ' — Descuento aplicado: ' . number_format($pct, 2, '.', '') . '%';
        }

        return $inst;
    }

    /**
     * @param array<string, mixed> $layout
     * @param object               $doc
     */
    public function renderClientGridHtml(array $layout, object $doc, bool $showDoctor = true): string
    {
        $grid = is_array($layout['client_grid'] ?? null) ? $layout['client_grid'] : $this->getDefaultLayout()['client_grid'];
        $cols = max(1, (int) ($grid['columns'] ?? 2));
        $rows = max(1, (int) ($grid['rows'] ?? 3));
        $items = is_array($grid['items'] ?? null) ? $grid['items'] : [];

        /** @var array<int, array<int, list<array<string, mixed>>>> $cellMap */
        $cellMap = [];
        foreach ($items as $item) {
            if (! is_array($item)) {
                continue;
            }
            $type = (string) ($item['element_type'] ?? '');
            if ($type === 'medico' && ! $showDoctor) {
                continue;
            }
            $r = (int) ($item['row'] ?? 0);
            $c = (int) ($item['col'] ?? 0);
            if ($r >= $rows || $c >= $cols) {
                continue;
            }
            $cellMap[$r][$c][] = $item;
        }

        $html = '<table class="pair-table">';
        for ($r = 0; $r < $rows; $r++) {
            $html .= '<tr>';
            $c = 0;
            while ($c < $cols) {
                $cellItems = $cellMap[$r][$c] ?? [];
                if ($cellItems === []) {
                    $html .= '<td></td>';
                    $c++;
                    continue;
                }
                $item = $cellItems[0];
                $colSpan = max(1, min($cols - $c, (int) ($item['col_span'] ?? 1)));
                $type = (string) ($item['element_type'] ?? '');
                $value = $this->resolveFieldValue($type, $doc, $showDoctor);
                if ($type === 'medico' && ! $showDoctor) {
                    $c += $colSpan;
                    continue;
                }
                $label = ($item['show_label'] ?? true) !== false
                    ? trim((string) ($item['custom_label'] ?? self::FIELD_DEFAULT_LABELS[$type] ?? ''))
                    : '';
                $cell = '';
                if ($label !== '') {
                    $cell .= '<span class="pair-k">' . esc($label) . ' </span>';
                }
                $cell .= '<span class="pair-v">' . esc($value) . '</span>';
                $attr = $colSpan > 1 ? ' colspan="' . $colSpan . '"' : '';
                $html .= '<td' . $attr . '>' . $cell . '</td>';
                $c += $colSpan;
            }
            $html .= '</tr>';
        }
        $html .= '</table>';

        return $html;
    }
}
