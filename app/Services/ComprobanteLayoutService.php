<?php

namespace App\Services;

/**
 * Diseño tipográfico y disposición de campos del comprobante de pago (PDF).
 */
class ComprobanteLayoutService
{
    public const LAYOUT_VERSION = 2;

    public const COLUMN_MIN = 1;

    public const COLUMN_MAX = 4;

    public const ROW_MIN = 1;

    public const ROW_MAX = 8;

    public const DOCUMENT_ROW_MIN = 6;

    public const DOCUMENT_ROW_MAX = 16;

    /**
     * Secciones de la matriz del comprobante (cada una con filas/columnas propias).
     *
     * @var array<string, array{label: string, default_columns: int, default_rows: int, row_min: int, row_max: int, col_min: int, col_max: int}>
     */
    public const MATRIX_SECTIONS = [
        'header' => [
            'label'           => 'Encabezado',
            'default_columns' => 2,
            'default_rows'    => 3,
            'row_min'         => 1,
            'row_max'         => 6,
            'col_min'         => 1,
            'col_max'         => 4,
        ],
        'client' => [
            'label'           => 'Títulos de sección y datos del cliente',
            'default_columns' => 2,
            'default_rows'    => 4,
            'row_min'         => 1,
            'row_max'         => 8,
            'col_min'         => 1,
            'col_max'         => 4,
        ],
        'table' => [
            'label'           => 'Tabla detalle de conceptos',
            'default_columns' => 2,
            'default_rows'    => 3,
            'row_min'         => 1,
            'row_max'         => 6,
            'col_min'         => 1,
            'col_max'         => 4,
        ],
        'totals' => [
            'label'           => 'Montos totales',
            'default_columns' => 2,
            'default_rows'    => 3,
            'row_min'         => 1,
            'row_max'         => 6,
            'col_min'         => 1,
            'col_max'         => 4,
        ],
        'footer' => [
            'label'           => 'Forma de pago y pie',
            'default_columns' => 2,
            'default_rows'    => 2,
            'row_min'         => 1,
            'row_max'         => 4,
            'col_min'         => 1,
            'col_max'         => 4,
        ],
    ];

    /**
     * Catálogo de elementos colocables en la matriz del comprobante.
     *
     * @var array<string, array{label: string, sample: string, style_key: string, can_delete: bool, default_label: string, palette_group: string, is_client_field: bool}>
     */
    public const MATRIX_ELEMENT_DEFINITIONS = [
        'brand_name' => [
            'label' => 'Nombre del laboratorio', 'sample' => '(nombre empresa)', 'style_key' => 'brand_name',
            'can_delete' => false, 'default_label' => '', 'palette_group' => 'header', 'is_client_field' => false,
        ],
        'brand_tagline' => [
            'label' => 'Subtítulo del laboratorio', 'sample' => 'Constancia de pago', 'style_key' => 'brand_tagline',
            'can_delete' => false, 'default_label' => '', 'palette_group' => 'header', 'is_client_field' => false,
        ],
        'receipt_badge_title' => [
            'label' => 'Título recibo (derecha)', 'sample' => 'Recibo de pago', 'style_key' => 'receipt_badge_title',
            'can_delete' => false, 'default_label' => 'Recibo de pago', 'palette_group' => 'header', 'is_client_field' => false,
            'default_align_h' => 'right', 'default_align_v' => 'top',
        ],
        'receipt_badge_orden' => [
            'label' => 'N.º de orden', 'sample' => 'N.º 2026-0042', 'style_key' => 'receipt_badge_orden',
            'can_delete' => false, 'default_label' => '', 'palette_group' => 'header', 'is_client_field' => false,
            'default_align_h' => 'right', 'default_align_v' => 'top',
        ],
        'receipt_badge_fecha' => [
            'label' => 'Fecha de emisión', 'sample' => '31/05/2026 10:30', 'style_key' => 'receipt_badge_fecha',
            'can_delete' => false, 'default_label' => '', 'palette_group' => 'header', 'is_client_field' => false,
            'default_align_h' => 'right', 'default_align_v' => 'top',
        ],
        'section_client' => [
            'label' => 'Título sección cliente', 'sample' => 'Cliente y atención', 'style_key' => 'section_title',
            'can_delete' => false, 'default_label' => 'Cliente y atención', 'palette_group' => 'client', 'is_client_field' => false,
        ],
        'paciente_nombre' => [
            'label' => 'Nombre del paciente', 'sample' => 'María Fernández López', 'style_key' => 'pair_value',
            'can_delete' => true, 'default_label' => 'Paciente:', 'palette_group' => 'client', 'is_client_field' => true,
        ],
        'paciente_ci' => [
            'label' => 'Documento de identidad', 'sample' => '1234567 LP', 'style_key' => 'pair_value',
            'can_delete' => true, 'default_label' => 'Documento de identidad:', 'palette_group' => 'client', 'is_client_field' => true,
        ],
        'paciente_telefono' => [
            'label' => 'Teléfono', 'sample' => '70123456', 'style_key' => 'pair_value',
            'can_delete' => true, 'default_label' => 'Teléfono:', 'palette_group' => 'client', 'is_client_field' => true,
        ],
        'medico' => [
            'label' => 'Médico referente', 'sample' => 'Dr. Juan Pérez', 'style_key' => 'pair_value',
            'can_delete' => true, 'default_label' => 'Médico referente:', 'palette_group' => 'client', 'is_client_field' => true,
        ],
        'institucion' => [
            'label' => 'Institución / procedencia', 'sample' => 'Clínica Central', 'style_key' => 'pair_value',
            'can_delete' => true, 'default_label' => 'Institución / procedencia:', 'palette_group' => 'client', 'is_client_field' => true,
        ],
        'section_items' => [
            'label' => 'Título sección conceptos', 'sample' => 'Detalle de conceptos', 'style_key' => 'section_title',
            'can_delete' => false, 'default_label' => 'Detalle de conceptos', 'palette_group' => 'table', 'is_client_field' => false,
        ],
        'items_header_desc' => [
            'label' => 'Columna descripción', 'sample' => 'Descripción', 'style_key' => 'items_header',
            'can_delete' => false, 'default_label' => 'Descripción', 'palette_group' => 'table', 'is_client_field' => false,
        ],
        'items_header_amount' => [
            'label' => 'Columna importe', 'sample' => 'Importe ($)', 'style_key' => 'items_header',
            'can_delete' => false, 'default_label' => 'Importe ($)', 'palette_group' => 'table', 'is_client_field' => false,
            'default_align_h' => 'right', 'default_align_v' => 'middle',
        ],
        'items_table' => [
            'label' => 'Tabla de líneas (conceptos)', 'sample' => '(filas de estudios)', 'style_key' => 'items_body',
            'can_delete' => false, 'default_label' => '', 'palette_group' => 'table', 'is_client_field' => false,
        ],
        'total_orden' => [
            'label' => 'Fila total orden', 'sample' => 'Total orden', 'style_key' => 'totals_label',
            'can_delete' => false, 'default_label' => 'Total orden', 'palette_group' => 'totals', 'is_client_field' => false,
        ],
        'total_pagado' => [
            'label' => 'Fila monto pagado', 'sample' => 'Monto pagado', 'style_key' => 'totals_label',
            'can_delete' => false, 'default_label' => 'Monto pagado', 'palette_group' => 'totals', 'is_client_field' => false,
        ],
        'total_saldo' => [
            'label' => 'Fila saldo', 'sample' => 'Saldo', 'style_key' => 'totals_final',
            'can_delete' => false, 'default_label' => 'Saldo', 'palette_group' => 'totals', 'is_client_field' => false,
        ],
        'totals_box' => [
            'label' => 'Bloque montos totales', 'sample' => '(caja de totales)', 'style_key' => 'totals_value',
            'can_delete' => false, 'default_label' => '', 'palette_group' => 'totals', 'is_client_field' => false,
        ],
        'pay_method' => [
            'label' => 'Forma de pago', 'sample' => 'Forma de pago: Efectivo', 'style_key' => 'pay_method',
            'can_delete' => true, 'default_label' => 'Forma de pago:', 'palette_group' => 'footer', 'is_client_field' => false,
        ],
        'footer' => [
            'label' => 'Pie del comprobante', 'sample' => '(texto legal)', 'style_key' => 'footer',
            'can_delete' => false, 'default_label' => '', 'palette_group' => 'footer', 'is_client_field' => false,
        ],
    ];

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

    /** @var list<string> */
    public const ALLOWED_ALIGN_H = ['left', 'center', 'right'];

    /** @var list<string> */
    public const ALLOWED_ALIGN_V = ['top', 'middle', 'bottom'];

    /** @var array<string, string> */
    public const ALIGN_H_LABELS = [
        'left'   => 'Izquierda',
        'center' => 'Centro',
        'right'  => 'Derecha',
    ];

    /** @var array<string, string> */
    public const ALIGN_V_LABELS = [
        'top'    => 'Arriba',
        'middle' => 'Centro (vertical)',
        'bottom' => 'Abajo',
    ];

    /**
     * Grupos de la matriz de estilos (secciones editables en Config → comprobante).
     *
     * @var list<array{id: string, label: string, style_keys: list<string>, dimensions: array<string, string>}>
     */
    public const STYLE_ELEMENT_GROUPS = [
        [
            'id'          => 'document',
            'label'       => 'Documento general',
            'style_keys'  => ['body'],
            'dimensions'  => [],
        ],
        [
            'id'          => 'lab_header',
            'label'       => 'Encabezado — columna 1 (laboratorio)',
            'style_keys'  => ['brand_name', 'brand_tagline'],
            'dimensions'  => [
                'header_lab_width_pct' => 'Ancho columna laboratorio (%)',
                'header_lab_bg_color'  => 'Fondo columna laboratorio (vacío = sin fondo)',
            ],
        ],
        [
            'id'          => 'receipt_badge',
            'label'       => 'Encabezado — columna 2 (recuadro recibo)',
            'style_keys'  => ['receipt_badge_title', 'receipt_badge_orden', 'receipt_badge_fecha'],
            'dimensions'  => [
                'receipt_badge_bg_color'         => 'Fondo del recuadro',
                'receipt_badge_border_color'     => 'Color del borde',
                'receipt_badge_border_width_pt'  => 'Grosor del borde (pt)',
                'receipt_badge_border_radius_pt' => 'Redondeo de esquinas (pt)',
                'receipt_badge_padding_pt'       => 'Relleno interior (pt)',
            ],
        ],
        [
            'id'          => 'sections_client',
            'label'       => 'Títulos de sección y datos del cliente',
            'style_keys'  => ['section_title', 'pair_label', 'pair_value'],
            'dimensions'  => [],
        ],
        [
            'id'          => 'items_table',
            'label'       => 'Tabla detalle de conceptos',
            'style_keys'  => ['section_title', 'items_header', 'items_body', 'items_num'],
            'dimensions'  => [
                'items_amount_col_pct'   => 'Ancho columna importes (%)',
                'items_cell_padding_pt'  => 'Espacio vertical dentro de cada celda de la tabla (pt)',
                'items_header_bg_color'  => 'Fondo encabezado tabla (vacío = color secundario global)',
            ],
        ],
        [
            'id'          => 'totals',
            'label'       => 'Montos totales',
            'style_keys'  => ['totals_label', 'totals_value', 'totals_final'],
            'dimensions'  => [
                'totals_box_width_pt'    => 'Ancho caja de totales (pt)',
                'totals_label_col_pct'   => 'Ancho columna etiquetas (%)',
            ],
        ],
        [
            'id'          => 'footer_area',
            'label'       => 'Forma de pago y pie',
            'style_keys'  => ['pay_method', 'footer'],
            'dimensions'  => [],
        ],
    ];

    /**
     * Grupos de estilos mostrados junto a la matriz de cada sección del comprobante.
     *
     * @var array<string, list<string>>
     */
    public const MATRIX_SECTION_STYLE_GROUPS = [
        'header' => ['document', 'lab_header', 'receipt_badge'],
        'client' => ['sections_client'],
        'table'  => ['items_table'],
        'totals' => ['totals'],
        'footer' => ['footer_area'],
    ];

    /** @var array<string, float|int|string> */
    public const DEFAULT_DIMENSIONS = [
        'header_lab_width_pct'           => 58,
        'header_lab_bg_color'            => '',
        'receipt_badge_bg_color'         => '#F0FDFA',
        'receipt_badge_border_color'     => '#0F766E',
        'receipt_badge_border_width_pt'  => 2,
        'receipt_badge_border_radius_pt' => 6,
        'receipt_badge_padding_pt'       => 10,
        'items_amount_col_pct'           => 26,
        'items_cell_padding_pt'          => 8,
        'items_header_bg_color'          => '',
        'totals_box_width_pt'            => 280,
        'totals_label_col_pct'           => 52,
    ];

    /** Estilos de separador entre secciones del comprobante. */
    public const SEPARATOR_STYLES = [
        'line'   => 'Línea continua',
        'dashed' => 'Línea discontinua',
        'dotted' => 'Punteada',
        'double' => 'Doble',
        'space'  => 'Solo espacio (sin línea)',
    ];

    /**
     * Espaciado entre filas, margen inferior y separador por sección (pt).
     *
     * @var array<string, array<string, bool|float|string>>
     */
    public const DEFAULT_SECTION_SPACING = [
        'header' => [
            'row_gap_pt' => 4.0, 'margin_bottom_pt' => 14.0,
            'separator_enabled' => false, 'separator_style' => 'line', 'separator_color' => '#CBD5E1',
            'separator_thickness_pt' => 1.0, 'separator_width_pct' => 100.0, 'separator_gap_pt' => 6.0,
        ],
        'client' => [
            'row_gap_pt' => 6.0, 'margin_bottom_pt' => 16.0,
            'separator_enabled' => false, 'separator_style' => 'line', 'separator_color' => '#CBD5E1',
            'separator_thickness_pt' => 1.0, 'separator_width_pct' => 100.0, 'separator_gap_pt' => 6.0,
        ],
        'table' => [
            'row_gap_pt' => 4.0, 'margin_bottom_pt' => 14.0,
            'separator_enabled' => false, 'separator_style' => 'line', 'separator_color' => '#CBD5E1',
            'separator_thickness_pt' => 1.0, 'separator_width_pct' => 100.0, 'separator_gap_pt' => 6.0,
        ],
        'totals' => [
            'row_gap_pt' => 4.0, 'margin_bottom_pt' => 12.0,
            'separator_enabled' => false, 'separator_style' => 'line', 'separator_color' => '#CBD5E1',
            'separator_thickness_pt' => 1.0, 'separator_width_pct' => 100.0, 'separator_gap_pt' => 6.0,
        ],
        'footer' => [
            'row_gap_pt' => 6.0, 'margin_bottom_pt' => 0.0,
            'separator_enabled' => false, 'separator_style' => 'line', 'separator_color' => '#CBD5E1',
            'separator_thickness_pt' => 1.0, 'separator_width_pct' => 100.0, 'separator_gap_pt' => 6.0,
        ],
    ];

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
        $sectionGrids = $this->buildDefaultSectionGrids();

        return array_merge($this->layoutWithDerivedGrids($sectionGrids), [
            'section_spacing' => $this->normalizeSectionSpacing(null),
        ]);
    }

    /**
     * @param array<string, array{columns: int, rows: int, items: list<array<string, mixed>>}> $sectionGrids
     *
     * @return array<string, mixed>
     */
    private function layoutWithDerivedGrids(array $sectionGrids): array
    {
        $documentGrid = $this->mergeSectionGridsToDocumentGrid($sectionGrids);

        return [
            'version'         => self::LAYOUT_VERSION,
            'styles'          => self::DEFAULT_STYLES,
            'section_styles'  => $this->buildDefaultSectionStyles(self::DEFAULT_STYLES),
            'dimensions'      => self::DEFAULT_DIMENSIONS,
            'section_grids'   => $sectionGrids,
            'document_grid'   => $documentGrid,
            'client_grid'     => $this->clientGridFromSectionGrid($sectionGrids['client'] ?? ['columns' => 2, 'rows' => 3, 'items' => []]),
        ];
    }

    /**
     * @return array<string, array{columns: int, rows: int, items: list<array<string, mixed>>}>
     */
    public function buildDefaultSectionGrids(): array
    {
        return [
            'header' => [
                'columns' => 2,
                'rows'    => 3,
                'items'   => [
                    ['uid' => 'h01', 'element_type' => 'brand_name', 'row' => 0, 'col' => 0, 'col_span' => 1, 'enabled' => true, 'show_label' => false, 'custom_label' => ''],
                    ['uid' => 'h02', 'element_type' => 'receipt_badge_title', 'row' => 0, 'col' => 1, 'col_span' => 1, 'enabled' => true, 'show_label' => false, 'custom_label' => 'Recibo de pago'],
                    ['uid' => 'h03', 'element_type' => 'brand_tagline', 'row' => 1, 'col' => 0, 'col_span' => 1, 'enabled' => true, 'show_label' => false, 'custom_label' => ''],
                    ['uid' => 'h04', 'element_type' => 'receipt_badge_orden', 'row' => 1, 'col' => 1, 'col_span' => 1, 'enabled' => true, 'show_label' => false, 'custom_label' => ''],
                    ['uid' => 'h05', 'element_type' => 'receipt_badge_fecha', 'row' => 2, 'col' => 1, 'col_span' => 1, 'enabled' => true, 'show_label' => false, 'custom_label' => ''],
                ],
            ],
            'client' => [
                'columns' => 2,
                'rows'    => 4,
                'items'   => [
                    ['uid' => 'c01', 'element_type' => 'section_client', 'row' => 0, 'col' => 0, 'col_span' => 2, 'enabled' => true, 'show_label' => false, 'custom_label' => 'Cliente y atención'],
                    ['uid' => 'c02', 'element_type' => 'paciente_nombre', 'row' => 1, 'col' => 0, 'col_span' => 1, 'enabled' => true, 'show_label' => true, 'custom_label' => 'Paciente:'],
                    ['uid' => 'c03', 'element_type' => 'paciente_ci', 'row' => 1, 'col' => 1, 'col_span' => 1, 'enabled' => true, 'show_label' => true, 'custom_label' => 'Documento de identidad:'],
                    ['uid' => 'c04', 'element_type' => 'paciente_telefono', 'row' => 2, 'col' => 0, 'col_span' => 1, 'enabled' => true, 'show_label' => true, 'custom_label' => 'Teléfono:'],
                    ['uid' => 'c05', 'element_type' => 'medico', 'row' => 2, 'col' => 1, 'col_span' => 1, 'enabled' => true, 'show_label' => true, 'custom_label' => 'Médico referente:'],
                    ['uid' => 'c06', 'element_type' => 'institucion', 'row' => 3, 'col' => 0, 'col_span' => 2, 'enabled' => true, 'show_label' => true, 'custom_label' => 'Institución / procedencia:'],
                ],
            ],
            'table' => [
                'columns' => 2,
                'rows'    => 3,
                'items'   => [
                    ['uid' => 't01', 'element_type' => 'section_items', 'row' => 0, 'col' => 0, 'col_span' => 2, 'enabled' => true, 'show_label' => false, 'custom_label' => 'Detalle de conceptos'],
                    ['uid' => 't02', 'element_type' => 'items_header_desc', 'row' => 1, 'col' => 0, 'col_span' => 1, 'enabled' => true, 'show_label' => false, 'custom_label' => 'Descripción'],
                    ['uid' => 't03', 'element_type' => 'items_header_amount', 'row' => 1, 'col' => 1, 'col_span' => 1, 'enabled' => true, 'show_label' => false, 'custom_label' => 'Importe ($)'],
                    ['uid' => 't04', 'element_type' => 'items_table', 'row' => 2, 'col' => 0, 'col_span' => 2, 'enabled' => true, 'show_label' => false, 'custom_label' => ''],
                ],
            ],
            'totals' => [
                'columns' => 2,
                'rows'    => 3,
                'items'   => [
                    ['uid' => 'o01', 'element_type' => 'totals_box', 'row' => 0, 'col' => 0, 'col_span' => 2, 'enabled' => true, 'show_label' => false, 'custom_label' => ''],
                    ['uid' => 'o02', 'element_type' => 'total_orden', 'row' => 1, 'col' => 0, 'col_span' => 1, 'enabled' => true, 'show_label' => false, 'custom_label' => 'Total orden'],
                    ['uid' => 'o03', 'element_type' => 'total_pagado', 'row' => 1, 'col' => 1, 'col_span' => 1, 'enabled' => true, 'show_label' => false, 'custom_label' => 'Monto pagado'],
                    ['uid' => 'o04', 'element_type' => 'total_saldo', 'row' => 2, 'col' => 0, 'col_span' => 2, 'enabled' => true, 'show_label' => false, 'custom_label' => 'Saldo'],
                ],
            ],
            'footer' => [
                'columns' => 2,
                'rows'    => 2,
                'items'   => [
                    ['uid' => 'f01', 'element_type' => 'pay_method', 'row' => 0, 'col' => 0, 'col_span' => 2, 'enabled' => true, 'show_label' => true, 'custom_label' => 'Forma de pago:'],
                    ['uid' => 'f02', 'element_type' => 'footer', 'row' => 1, 'col' => 0, 'col_span' => 2, 'enabled' => true, 'show_label' => false, 'custom_label' => ''],
                ],
            ],
        ];
    }

    /**
     * @return array{columns: int, rows: int, items: list<array<string, mixed>>}
     */
    public function buildDefaultDocumentGrid(): array
    {
        return $this->mergeSectionGridsToDocumentGrid($this->buildDefaultSectionGrids());
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

        $sectionGrids = $this->normalizeSectionGrids($decoded);

        return array_merge($this->layoutWithDerivedGrids($sectionGrids), [
            'styles'           => $styles,
            'section_styles'   => $this->normalizeSectionStyles($decoded, $styles),
            'dimensions'       => $this->normalizeDimensions($decoded['dimensions'] ?? null),
            'section_spacing'  => $this->normalizeSectionSpacing($decoded['section_spacing'] ?? null),
        ]);
    }

    /**
     * Estilos tipográficos por sección de la matriz (independientes entre encabezado, cliente, tabla, etc.).
     *
     * @param array<string, mixed> $globalStyles
     *
     * @return array<string, array<string, array{font_family: string, font_size_pt: float, font_weight: string, font_style: string, color: string, text_transform: string}>>
     */
    public function buildDefaultSectionStyles(array $globalStyles = self::DEFAULT_STYLES): array
    {
        $out = [];
        foreach (array_keys(self::MATRIX_SECTIONS) as $sectionId) {
            $keys = $this->styleKeysForMatrixSection($sectionId);
            if ($sectionId === 'header') {
                $keys[] = 'body';
                $keys = array_values(array_unique($keys));
            }
            $out[$sectionId] = [];
            foreach ($keys as $key) {
                $out[$sectionId][$key] = $this->normalizeStyleBlock(
                    $globalStyles[$key] ?? null,
                    self::DEFAULT_STYLES[$key] ?? self::DEFAULT_STYLES['body']
                );
            }
        }

        return $out;
    }

    /**
     * @param array<string, mixed> $decoded
     * @param array<string, array<string, mixed>> $globalStyles
     *
     * @return array<string, array<string, array{font_family: string, font_size_pt: float, font_weight: string, font_style: string, color: string, text_transform: string}>>
     */
    public function normalizeSectionStyles(array $decoded, array $globalStyles): array
    {
        $raw = is_array($decoded['section_styles'] ?? null) ? $decoded['section_styles'] : [];
        $out = [];
        foreach (array_keys(self::MATRIX_SECTIONS) as $sectionId) {
            $keys = $this->styleKeysForMatrixSection($sectionId);
            if ($sectionId === 'header') {
                $keys[] = 'body';
                $keys = array_values(array_unique($keys));
            }
            $secRaw = is_array($raw[$sectionId] ?? null) ? $raw[$sectionId] : [];
            $out[$sectionId] = [];
            foreach ($keys as $key) {
                $out[$sectionId][$key] = $this->normalizeStyleBlock(
                    $secRaw[$key] ?? $globalStyles[$key] ?? null,
                    self::DEFAULT_STYLES[$key] ?? self::DEFAULT_STYLES['body']
                );
            }
        }

        return $out;
    }

    /**
     * Resuelve un bloque de estilo para una sección concreta (matriz o PDF).
     *
     * @param array<string, mixed> $layout
     *
     * @return array{font_family: string, font_size_pt: float, font_weight: string, font_style: string, color: string, text_transform: string}
     */
    public function resolveStyle(array $layout, string $sectionId, string $styleKey): array
    {
        $sectionStyles = is_array($layout['section_styles'] ?? null) ? $layout['section_styles'] : [];
        $global = is_array($layout['styles'] ?? null) ? $layout['styles'] : self::DEFAULT_STYLES;
        $sec = is_array($sectionStyles[$sectionId] ?? null) ? $sectionStyles[$sectionId] : [];
        $block = $sec[$styleKey] ?? $global[$styleKey] ?? null;

        return $this->normalizeStyleBlock($block, self::DEFAULT_STYLES[$styleKey] ?? self::DEFAULT_STYLES['body']);
    }

    /**
     * CSS en línea para DomPDF (más fiable que reglas de clase en &lt;style&gt;).
     *
     * @param array{font_family?: string, font_size_pt?: float, font_weight?: string, font_style?: string, color?: string, text_transform?: string} $block
     * @param array<string, mixed>                                                                                                                      $overrides
     */
    public function inlineStyleCss(array $block, array $overrides = []): string
    {
        $merged = array_merge($block, $overrides);
        $family = (string) ($merged['font_family'] ?? 'DejaVu Sans');
        $size   = round(max(6.0, min(24.0, (float) ($merged['font_size_pt'] ?? 10))), 1);

        return sprintf(
            'font-family:%s,DejaVu Sans,Arial,sans-serif;font-size:%.1fpt;font-weight:%s;font-style:%s;color:%s;text-transform:%s',
            $family,
            $size,
            (string) ($merged['font_weight'] ?? 'normal'),
            (string) ($merged['font_style'] ?? 'normal'),
            (string) ($merged['color'] ?? '#1E293B'),
            (string) ($merged['text_transform'] ?? 'none')
        );
    }

    /**
     * @param array<string, mixed> $layout
     * @param array<string, mixed> $overrides
     */
    public function inlineStyleFor(array $layout, string $sectionId, string $styleKey, array $overrides = []): string
    {
        return $this->inlineStyleCss($this->resolveStyle($layout, $sectionId, $styleKey), $overrides);
    }

    /**
     * Espaciado vertical entre filas de matriz (DomPDF ignora border-spacing con frecuencia).
     */
    public function matrixCellGapPadding(float $gapPt, int $rowIndex, int $rowCount): string
    {
        if ($rowCount < 1) {
            return 'padding-top:2pt;padding-bottom:2pt;';
        }
        $half     = round(max(0.0, $gapPt) / 2, 1);
        $padTop   = $rowIndex > 0 ? $half : 2.0;
        $padBottom = $rowIndex < $rowCount - 1 ? $half : 2.0;

        return sprintf('padding-top:%.1fpt;padding-bottom:%.1fpt;', $padTop, $padBottom);
    }

    /**
     * Atributo style para tablas de matriz con separación entre filas.
     */
    public function matrixTableSpacingStyle(float $gapPt): string
    {
        $gap = round(max(0.0, $gapPt), 1);

        return 'border-collapse:separate;border-spacing:0 ' . $gap . 'pt;width:100%;table-layout:fixed;';
    }

    /**
     * @param mixed $raw
     *
     * @return array<string, array<string, bool|float|string>>
     */
    public function normalizeSectionSpacing($raw): array
    {
        $src = is_array($raw) ? $raw : [];
        $out = [];
        foreach (self::DEFAULT_SECTION_SPACING as $sectionId => $defaults) {
            $sec = is_array($src[$sectionId] ?? null) ? $src[$sectionId] : [];
            $sepStyle = strtolower(trim((string) ($sec['separator_style'] ?? $defaults['separator_style'] ?? 'line')));
            if (! array_key_exists($sepStyle, self::SEPARATOR_STYLES)) {
                $sepStyle = 'line';
            }
            $sepColor = strtoupper(trim((string) ($sec['separator_color'] ?? $defaults['separator_color'] ?? '#CBD5E1')));
            if (! preg_match('/^#[0-9A-F]{6}$/', $sepColor)) {
                $sepColor = '#CBD5E1';
            }
            $out[$sectionId] = [
                'row_gap_pt'             => round(max(0.0, min(24.0, (float) ($sec['row_gap_pt'] ?? $defaults['row_gap_pt']))), 1),
                'margin_bottom_pt'       => round(max(0.0, min(48.0, (float) ($sec['margin_bottom_pt'] ?? $defaults['margin_bottom_pt']))), 1),
                'separator_enabled'      => filter_var($sec['separator_enabled'] ?? $defaults['separator_enabled'] ?? false, FILTER_VALIDATE_BOOLEAN),
                'separator_style'        => $sepStyle,
                'separator_color'        => $sepColor,
                'separator_thickness_pt' => round(max(0.5, min(4.0, (float) ($sec['separator_thickness_pt'] ?? $defaults['separator_thickness_pt'] ?? 1))), 1),
                'separator_width_pct'    => round(max(20.0, min(100.0, (float) ($sec['separator_width_pct'] ?? $defaults['separator_width_pct'] ?? 100))), 1),
                'separator_gap_pt'       => round(max(0.0, min(24.0, (float) ($sec['separator_gap_pt'] ?? $defaults['separator_gap_pt'] ?? 6))), 1),
            ];
        }

        return $out;
    }

    /**
     * Margen inferior del contenedor de sección (comp-sec-* / comp-sec-block).
     *
     * @param array<string, mixed> $layout
     */
    public function sectionOuterStyle(array $layout, string $sectionId): string
    {
        $spacing = $this->normalizeSectionSpacing($layout['section_spacing'] ?? null);
        $mb      = (float) ($spacing[$sectionId]['margin_bottom_pt'] ?? 0);

        return 'margin-bottom:' . round($mb, 1) . 'pt;';
    }

    /**
     * Estilo de la tabla .doc-header (espacio entre filas + margen inferior configurable).
     * Si hay separador activo, el margen inferior va en el contenedor de sección (después del separador).
     *
     * @param array<string, mixed> $layout
     */
    public function docHeaderTableStyle(array $layout): string
    {
        $spacing = $this->normalizeSectionSpacing($layout['section_spacing'] ?? null);
        $header  = $spacing['header'] ?? [];
        $gap     = (float) ($header['row_gap_pt'] ?? 4);
        $mb      = (float) ($header['margin_bottom_pt'] ?? 14);
        $hasSep  = ! empty($header['separator_enabled']);
        $parts   = [$this->matrixTableSpacingStyle($gap)];
        if (! $hasSep && $mb > 0) {
            $parts[] = 'margin-bottom:' . round($mb, 1) . 'pt';
        }

        return implode(';', $parts);
    }

    /**
     * Contenedor encabezado: margen inferior después de tabla + separador (si aplica).
     *
     * @param array<string, mixed> $layout
     */
    public function headerSectionOuterStyle(array $layout): string
    {
        $spacing = $this->normalizeSectionSpacing($layout['section_spacing'] ?? null);
        $header  = $spacing['header'] ?? [];
        $mb      = (float) ($header['margin_bottom_pt'] ?? 14);
        if (empty($header['separator_enabled'])) {
            return '';
        }
        if ($mb <= 0) {
            return '';
        }

        return 'margin-bottom:' . round($mb, 1) . 'pt;';
    }

    /**
     * Separador visual al final de una sección (vista previa y PDF).
     *
     * @param array<string, mixed> $layout
     */
    public function renderSectionSeparatorHtml(array $layout, string $sectionId): string
    {
        $spacing = $this->normalizeSectionSpacing($layout['section_spacing'] ?? null);
        $sec     = $spacing[$sectionId] ?? [];
        if (empty($sec['separator_enabled'])) {
            return '';
        }
        $style = (string) ($sec['separator_style'] ?? 'line');
        $gap   = (float) ($sec['separator_gap_pt'] ?? 6);
        if ($style === 'space') {
            return '<div class="comp-section-sep comp-section-sep-space" style="height:' . round($gap, 1) . 'pt;line-height:0;font-size:0;"></div>';
        }
        $color  = (string) ($sec['separator_color'] ?? '#CBD5E1');
        $thick  = (float) ($sec['separator_thickness_pt'] ?? 1);
        $width  = (float) ($sec['separator_width_pct'] ?? 100);
        $border = match ($style) {
            'dashed' => 'dashed',
            'dotted' => 'dotted',
            'double' => 'double',
            default  => 'solid',
        };

        return '<div class="comp-section-sep" style="padding-top:' . round($gap, 1) . 'pt;line-height:0;font-size:0;">'
            . '<div style="border-top:' . round($thick, 1) . 'pt ' . $border . ' ' . $color . ';width:' . round($width, 1) . '%;margin:0 auto;"></div>'
            . '</div>';
    }

    /**
     * @param array<string, mixed> $decoded
     *
     * @return array<string, array{columns: int, rows: int, items: list<array<string, mixed>>}>
     */
    private function normalizeSectionGrids(array $decoded): array
    {
        $defaults = $this->buildDefaultSectionGrids();
        $raw      = is_array($decoded['section_grids'] ?? null) ? $decoded['section_grids'] : null;

        if ($raw === null) {
            if (is_array($decoded['document_grid'] ?? null)) {
                $raw = $this->splitDocumentGridIntoSections($decoded['document_grid']);
            } else {
                $legacy = $this->migrateLegacyClientGridToDocument($decoded);
                $raw    = $this->splitDocumentGridIntoSections($legacy);
            }
        }

        $out = [];
        foreach (self::MATRIX_SECTIONS as $sectionId => $sectionDef) {
            $out[$sectionId] = $this->normalizeOneSectionGrid(
                $sectionId,
                is_array($raw[$sectionId] ?? null) ? $raw[$sectionId] : null,
                $defaults[$sectionId]
            );
        }

        return $out;
    }

    /**
     * @param array{columns: int, rows: int, items: list<array<string, mixed>>} $documentGrid
     *
     * @return array<string, array{columns: int, rows: int, items: list<array<string, mixed>>>>
     */
    private function splitDocumentGridIntoSections(array $documentGrid): array
    {
        $sections = $this->buildDefaultSectionGrids();
        foreach ($sections as $sectionId => $grid) {
            $sections[$sectionId]['items'] = [];
        }

        foreach ($documentGrid['items'] ?? [] as $item) {
            if (! is_array($item)) {
                continue;
            }
            $type      = (string) ($item['element_type'] ?? '');
            $sectionId = $this->sectionIdForElementType($type);
            if ($sectionId === '' || ! isset($sections[$sectionId])) {
                continue;
            }
            $sections[$sectionId]['items'][] = $item;
        }

        foreach ($sections as $sectionId => $grid) {
            if ($grid['items'] === []) {
                $sections[$sectionId] = $this->buildDefaultSectionGrids()[$sectionId];
            }
        }

        return $sections;
    }

    /**
     * @param array<string, array{columns: int, rows: int, items: list<array<string, mixed>>}> $sectionGrids
     *
     * @return array{columns: int, rows: int, items: list<array<string, mixed>>}
     */
    public function mergeSectionGridsToDocumentGrid(array $sectionGrids): array
    {
        $items = [];
        $cols  = 2;
        $rows  = 0;
        foreach (self::MATRIX_SECTIONS as $sectionId => $_def) {
            $grid = $sectionGrids[$sectionId] ?? ['columns' => 2, 'rows' => 1, 'items' => []];
            $cols = max($cols, (int) ($grid['columns'] ?? 2));
            $rows += (int) ($grid['rows'] ?? 0);
            foreach ($grid['items'] ?? [] as $item) {
                if (is_array($item)) {
                    $items[] = $item;
                }
            }
        }

        return [
            'columns' => max(1, min(self::COLUMN_MAX, $cols)),
            'rows'    => max(1, $rows),
            'items'   => $items,
        ];
    }

    public function sectionIdForElementType(string $type): string
    {
        $def = self::MATRIX_ELEMENT_DEFINITIONS[$type] ?? null;
        if ($def === null) {
            return '';
        }
        $section = (string) ($def['palette_group'] ?? '');
        if ($section === 'other') {
            return 'footer';
        }

        return isset(self::MATRIX_SECTIONS[$section]) ? $section : '';
    }

    /**
     * Elementos de la paleta que pertenecen a una sección de la matriz.
     *
     * @return array<string, string> tipo => etiqueta
     */
    public function paletteLabelsForSection(string $sectionId): array
    {
        $out = [];
        foreach (self::MATRIX_ELEMENT_DEFINITIONS as $type => $def) {
            if ($this->sectionIdForElementType($type) !== $sectionId) {
                continue;
            }
            $out[$type] = (string) ($def['label'] ?? $type);
        }

        return $out;
    }

    /**
     * Grupos de la matriz tipográfica asociados a una sección (layout).
     *
     * @return list<array{id: string, label: string, style_keys: list<string>, dimensions: array<string, string>}>
     */
    public function styleGroupsForMatrixSection(string $sectionId): array
    {
        $ids = self::MATRIX_SECTION_STYLE_GROUPS[$sectionId] ?? [];
        $out   = [];
        foreach (self::STYLE_ELEMENT_GROUPS as $group) {
            if (in_array($group['id'], $ids, true)) {
                $out[] = $group;
            }
        }

        return $out;
    }

    /**
     * Claves de estilo usadas por los elementos colocables de una sección (sin duplicar).
     *
     * @return list<string>
     */
    public function styleKeysForMatrixSection(string $sectionId): array
    {
        $keys = [];
        foreach (self::MATRIX_ELEMENT_DEFINITIONS as $type => $def) {
            if ($this->sectionIdForElementType($type) !== $sectionId) {
                continue;
            }
            $sk = (string) ($def['style_key'] ?? '');
            if ($sk !== '' && ! in_array($sk, $keys, true)) {
                $keys[] = $sk;
            }
        }

        return $keys;
    }

    /**
     * @param array{columns: int, rows: int, items: list<array<string, mixed>>}|null $raw
     * @param array{columns: int, rows: int, items: list<array<string, mixed>>} $fallback
     *
     * @return array{columns: int, rows: int, items: list<array<string, mixed>>}
     */
    private function normalizeOneSectionGrid(string $sectionId, ?array $raw, array $fallback): array
    {
        $sectionDef = self::MATRIX_SECTIONS[$sectionId] ?? self::MATRIX_SECTIONS['header'];
        $cols       = max(
            (int) $sectionDef['col_min'],
            min((int) $sectionDef['col_max'], (int) ($raw['columns'] ?? $fallback['columns']))
        );
        $rows = max(
            (int) $sectionDef['row_min'],
            min((int) $sectionDef['row_max'], (int) ($raw['rows'] ?? $fallback['rows']))
        );

        $items = [];
        if (isset($raw['items']) && is_array($raw['items'])) {
            foreach ($raw['items'] as $item) {
                if (! is_array($item)) {
                    continue;
                }
                $norm = $this->normalizeDocumentItem($item, $cols, $rows);
                if ($norm !== null) {
                    $items[] = $norm;
                }
            }
        }
        if ($items === []) {
            return [
                'columns' => $cols,
                'rows'    => $rows,
                'items'   => $fallback['items'],
            ];
        }

        return [
            'columns' => $cols,
            'rows'    => $rows,
            'items'   => $items,
        ];
    }

    /**
     * @param array<string, mixed> $decoded
     *
     * @return array{columns: int, rows: int, items: list<array<string, mixed>>}
     */
    private function normalizeDocumentGrid(array $decoded): array
    {
        $def = $this->buildDefaultDocumentGrid();
        $gridRaw = is_array($decoded['document_grid'] ?? null) ? $decoded['document_grid'] : null;

        if ($gridRaw === null) {
            $gridRaw = $this->migrateLegacyClientGridToDocument($decoded);
        }

        $cols = max(self::COLUMN_MIN, min(self::COLUMN_MAX, (int) ($gridRaw['columns'] ?? $def['columns'])));
        $rows = max(self::DOCUMENT_ROW_MIN, min(self::DOCUMENT_ROW_MAX, (int) ($gridRaw['rows'] ?? $def['rows'])));

        $items = [];
        if (isset($gridRaw['items']) && is_array($gridRaw['items'])) {
            foreach ($gridRaw['items'] as $item) {
                if (! is_array($item)) {
                    continue;
                }
                $norm = $this->normalizeDocumentItem($item, $cols, $rows);
                if ($norm !== null) {
                    $items[] = $norm;
                }
            }
        }
        if ($items === []) {
            $items = $def['items'];
            $rows = $def['rows'];
            $cols = $def['columns'];
        }

        return [
            'columns' => $cols,
            'rows'    => $rows,
            'items'   => $items,
        ];
    }

    /**
     * @param array<string, mixed> $decoded
     *
     * @return array{columns: int, rows: int, items: list<array<string, mixed>>}
     */
    private function migrateLegacyClientGridToDocument(array $decoded): array
    {
        $base = $this->buildDefaultDocumentGrid();
        $legacy = is_array($decoded['client_grid'] ?? null) ? $decoded['client_grid'] : [];
        if (! isset($legacy['items']) || ! is_array($legacy['items']) || $legacy['items'] === []) {
            return $base;
        }

        $cols = max(self::COLUMN_MIN, min(self::COLUMN_MAX, (int) ($legacy['columns'] ?? 2)));
        $legacyRows = max(self::ROW_MIN, min(self::ROW_MAX, (int) ($legacy['rows'] ?? 3)));
        $clientStartRow = 4;

        $items = array_values(array_filter($base['items'], static function (array $it): bool {
            return ! (self::MATRIX_ELEMENT_DEFINITIONS[$it['element_type']]['is_client_field'] ?? false);
        }));

        foreach ($legacy['items'] as $item) {
            if (! is_array($item)) {
                continue;
            }
            $norm = $this->normalizeDocumentItem([
                'uid'          => $item['uid'] ?? null,
                'element_type' => $item['element_type'] ?? '',
                'row'          => $clientStartRow + (int) ($item['row'] ?? 0),
                'col'          => $item['col'] ?? 0,
                'col_span'     => $item['col_span'] ?? 1,
                'show_label'   => $item['show_label'] ?? true,
                'custom_label' => $item['custom_label'] ?? '',
                'enabled'      => true,
            ], $base['columns'], $base['rows']);
            if ($norm !== null) {
                $items[] = $norm;
            }
        }

        $rows = max($base['rows'], $clientStartRow + $legacyRows);

        return [
            'columns' => $base['columns'],
            'rows'    => min(self::DOCUMENT_ROW_MAX, $rows),
            'items'   => $items,
        ];
    }

    /**
     * @param array{columns: int, rows: int, items: list<array<string, mixed>>} $documentGrid
     *
     * @return array{columns: int, rows: int, items: list<array<string, mixed>>}
     */
    /**
     * @param array{columns: int, rows: int, items: list<array<string, mixed>>} $clientGrid
     *
     * @return array{columns: int, rows: int, items: list<array<string, mixed>>}
     */
    public function clientGridFromSectionGrid(array $clientGrid): array
    {
        $clientItems = [];
        $maxRow = 0;
        $maxCol = 0;
        foreach ($clientGrid['items'] ?? [] as $item) {
            $type = (string) ($item['element_type'] ?? '');
            if (! in_array($type, self::ALLOWED_FIELD_TYPES, true)) {
                continue;
            }
            if (($item['enabled'] ?? true) === false) {
                continue;
            }
            $r = (int) ($item['row'] ?? 0);
            $c = (int) ($item['col'] ?? 0);
            $clientItems[] = $item;
            $maxRow = max($maxRow, $r + max(1, (int) ($item['row_span'] ?? 1)) - 1);
            $maxCol = max($maxCol, $c + (int) ($item['col_span'] ?? 1) - 1);
        }
        if ($clientItems === []) {
            return [
                'columns' => 2,
                'rows'    => 3,
                'items'   => [],
            ];
        }
        $minRow = min(array_map(static fn (array $it): int => (int) ($it['row'] ?? 0), $clientItems));
        $normalized = [];
        foreach ($clientItems as $item) {
            $normalized[] = array_merge($item, [
                'row' => max(0, (int) ($item['row'] ?? 0) - $minRow),
            ]);
        }
        $rowSpan = max($maxRow - $minRow + 1, 1);

        return [
            'columns' => max(1, min(self::COLUMN_MAX, $maxCol + 1)),
            'rows'    => max(1, min(self::ROW_MAX, $rowSpan)),
            'items'   => $normalized,
        ];
    }

    /**
     * @param array{columns: int, rows: int, items: list<array<string, mixed>>} $documentGrid
     *
     * @return array{columns: int, rows: int, items: list<array<string, mixed>>}
     */
    public function clientGridFromDocumentGrid(array $documentGrid): array
    {
        $sections = $this->splitDocumentGridIntoSections($documentGrid);

        return $this->clientGridFromSectionGrid($sections['client'] ?? ['columns' => 2, 'rows' => 3, 'items' => []]);
    }

    /**
     * @param mixed $raw
     *
     * @return array<string, float|int|string>
     */
    public function normalizeDimensions($raw): array
    {
        $src = is_array($raw) ? $raw : [];
        $out = [];
        foreach (self::DEFAULT_DIMENSIONS as $key => $default) {
            if (str_ends_with($key, '_color')) {
                $out[$key] = $this->normalizeDimensionColor($src[$key] ?? null, (string) $default, $key === 'header_lab_bg_color' || $key === 'items_header_bg_color');

                continue;
            }
            $val = isset($src[$key]) ? (float) $src[$key] : (float) $default;
            $out[$key] = match ($key) {
                'header_lab_width_pct', 'items_amount_col_pct', 'totals_label_col_pct' => round(max(20, min(80, $val)), 1),
                'items_cell_padding_pt', 'receipt_badge_padding_pt' => round(max(2, min(20, $val)), 1),
                'receipt_badge_border_width_pt' => round(max(0.0, min(6.0, $val)), 1),
                'receipt_badge_border_radius_pt' => round(max(0.0, min(16.0, $val)), 1),
                'totals_box_width_pt' => round(max(160, min(420, $val)), 0),
                default => $val,
            };
        }

        return $out;
    }

    /**
     * @param mixed $raw
     */
    private function normalizeDimensionColor($raw, string $default, bool $allowEmpty): string
    {
        $val = strtoupper(trim((string) ($raw ?? $default)));
        if ($allowEmpty && ($val === '' || $val === 'TRANSPARENT' || $val === 'NONE')) {
            return '';
        }
        if (! preg_match('/^#[0-9A-F]{6}$/', $val)) {
            $val = strtoupper($default);
        }
        if ($allowEmpty && $val === '') {
            return '';
        }
        if (! preg_match('/^#[0-9A-F]{6}$/', $val)) {
            return '#CBD5E1';
        }

        return $val;
    }

    /**
     * Estilo en línea del recuadro «Recibo de pago» (columna 2 del encabezado).
     *
     * @param array<string, mixed> $layout
     */
    public function receiptBadgeBoxStyle(array $layout, string $fallbackBorderColor = '#0F766E'): string
    {
        $dim         = $this->normalizeDimensions($layout['dimensions'] ?? null);
        $borderColor = (string) ($dim['receipt_badge_border_color'] ?? $fallbackBorderColor);
        if ($borderColor === '') {
            $borderColor = $fallbackBorderColor;
        }
        $bg      = (string) ($dim['receipt_badge_bg_color'] ?? '#F0FDFA');
        $borderW = (float) ($dim['receipt_badge_border_width_pt'] ?? 2);
        $radius  = (float) ($dim['receipt_badge_border_radius_pt'] ?? 6);
        $pad     = (float) ($dim['receipt_badge_padding_pt'] ?? 10);
        $parts   = [
            'display:inline-block',
            'text-align:right',
            'padding:' . round($pad, 1) . 'pt',
            'border-radius:' . round($radius, 1) . 'pt',
        ];
        if ($borderW > 0) {
            $parts[] = 'border:' . round($borderW, 1) . 'pt solid ' . $borderColor;
        }
        if ($bg !== '') {
            $parts[] = 'background:' . $bg;
        }

        return implode(';', $parts);
    }

    /**
     * Fondo opcional de la celda columna laboratorio (columna 1).
     *
     * @param array<string, mixed> $layout
     */
    public function headerLabCellExtraStyle(array $layout): string
    {
        $dim = $this->normalizeDimensions($layout['dimensions'] ?? null);
        $bg  = (string) ($dim['header_lab_bg_color'] ?? '');
        if ($bg === '') {
            return '';
        }

        return 'background:' . $bg . ';';
    }

    /**
     * Color de fondo para encabezados de tabla de conceptos.
     *
     * @param array<string, mixed> $layout
     */
    public function itemsTableHeaderBgColor(array $layout, string $fallbackSecondary): string
    {
        $dim = $this->normalizeDimensions($layout['dimensions'] ?? null);
        $bg  = (string) ($dim['items_header_bg_color'] ?? '');
        if ($bg !== '') {
            return $bg;
        }

        return $fallbackSecondary;
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
    public function normalizeDocumentItem(array $item, int $cols, int $rows): ?array
    {
        $type = (string) ($item['element_type'] ?? '');
        $def = self::MATRIX_ELEMENT_DEFINITIONS[$type] ?? null;
        if ($def === null) {
            return null;
        }
        $row = max(0, min($rows - 1, (int) ($item['row'] ?? 0)));
        $col = max(0, min($cols - 1, (int) ($item['col'] ?? 0)));
        $colSpan = max(1, min($cols - $col, (int) ($item['col_span'] ?? 1)));
        $rowSpan = max(1, min($rows - $row, (int) ($item['row_span'] ?? 1)));
        $defaultLabel = (string) ($def['default_label'] ?? self::FIELD_DEFAULT_LABELS[$type] ?? '');

        $align = $this->normalizeItemAlignment($item, $def);

        return [
            'uid'          => trim((string) ($item['uid'] ?? '')) ?: 'c' . bin2hex(random_bytes(4)),
            'element_type' => $type,
            'row'          => $row,
            'col'          => $col,
            'col_span'     => $colSpan,
            'row_span'     => $rowSpan,
            'enabled'      => ($item['enabled'] ?? true) !== false,
            'show_label'   => ($item['show_label'] ?? true) !== false,
            'custom_label' => mb_substr(trim((string) ($item['custom_label'] ?? $defaultLabel)), 0, 80),
            'align_h'        => $align['align_h'],
            'align_v'        => $align['align_v'],
        ];
    }

    /**
     * @param array<string, mixed>      $item
     * @param array<string, mixed>|null $def
     *
     * @return array{align_h: string, align_v: string}
     */
    public function normalizeItemAlignment(array $item, ?array $def = null): array
    {
        $def = $def ?? [];
        $h   = strtolower(trim((string) ($item['align_h'] ?? '')));
        $v   = strtolower(trim((string) ($item['align_v'] ?? '')));
        if (! in_array($h, self::ALLOWED_ALIGN_H, true)) {
            $h = strtolower(trim((string) ($def['default_align_h'] ?? 'left')));
        }
        if (! in_array($v, self::ALLOWED_ALIGN_V, true)) {
            $v = strtolower(trim((string) ($def['default_align_v'] ?? 'top')));
        }

        return [
            'align_h' => in_array($h, self::ALLOWED_ALIGN_H, true) ? $h : 'left',
            'align_v' => in_array($v, self::ALLOWED_ALIGN_V, true) ? $v : 'top',
        ];
    }

    /**
     * @param array<string, mixed> $item
     */
    public function matrixCellAlignStyle(array $item): string
    {
        $type  = (string) ($item['element_type'] ?? '');
        $def   = self::MATRIX_ELEMENT_DEFINITIONS[$type] ?? [];
        $align = $this->normalizeItemAlignment($item, $def);
        $textAlign = match ($align['align_h']) {
            'right'  => 'right',
            'center' => 'center',
            default  => 'left',
        };
        $verticalAlign = match ($align['align_v']) {
            'bottom' => 'bottom',
            'middle' => 'middle',
            default  => 'top',
        };

        return 'text-align:' . $textAlign . ';vertical-align:' . $verticalAlign . ';';
    }

    /**
     * Envuelve el HTML del elemento para respetar alineación horizontal en vista previa/PDF.
     *
     * @param array<string, mixed> $item
     */
    public function wrapMatrixItemHtml(string $html, array $item): string
    {
        if ($html === '') {
            return '';
        }
        $align = $this->normalizeItemAlignment($item, self::MATRIX_ELEMENT_DEFINITIONS[(string) ($item['element_type'] ?? '')] ?? []);
        $textAlign = match ($align['align_h']) {
            'right'  => 'right',
            'center' => 'center',
            default  => 'left',
        };

        return '<div class="comp-matrix-cell-inner" style="width:100%;text-align:' . $textAlign . ';">' . $html . '</div>';
    }

    /**
     * @return array<string, string>
     */
    public function matrixElementLabels(): array
    {
        $out = [];
        foreach (self::MATRIX_ELEMENT_DEFINITIONS as $type => $def) {
            $out[$type] = (string) ($def['label'] ?? $type);
        }

        return $out;
    }

    /**
     * @return array<string, string>
     */
    public function matrixElementSamples(): array
    {
        $out = [];
        foreach (self::MATRIX_ELEMENT_DEFINITIONS as $type => $def) {
            $out[$type] = (string) ($def['sample'] ?? '');
        }

        return $out;
    }

    /**
     * @return array<string, array<string, mixed>>
     */
    public function matrixElementsForEditor(): array
    {
        $out = [];
        foreach (self::MATRIX_ELEMENT_DEFINITIONS as $type => $def) {
            $out[$type] = [
                'label'           => $def['label'],
                'sample'          => $def['sample'],
                'can_delete'      => $def['can_delete'],
                'default_label'   => $def['default_label'],
                'palette_group'   => $def['palette_group'],
                'section'         => $this->sectionIdForElementType($type),
                'style_key'       => $def['style_key'],
                'is_client_field' => $def['is_client_field'],
                'default_align_h' => (string) ($def['default_align_h'] ?? 'left'),
                'default_align_v' => (string) ($def['default_align_v'] ?? 'top'),
            ];
        }

        return $out;
    }

    /**
     * @return list<array{id: string, label: string}>
     */
    public function matrixPaletteGroups(): array
    {
        $out = [];
        foreach (self::MATRIX_SECTIONS as $id => $def) {
            $out[] = ['id' => $id, 'label' => $def['label']];
        }

        return $out;
    }

    /**
     * @return array<string, array{columns: int, rows: int, items: list<array<string, mixed>>}>
     */
    public function sectionGridsFromLayout(array $layout): array
    {
        if (is_array($layout['section_grids'] ?? null) && $layout['section_grids'] !== []) {
            return $layout['section_grids'];
        }

        return $this->splitDocumentGridIntoSections(
            is_array($layout['document_grid'] ?? null) ? $layout['document_grid'] : $this->buildDefaultDocumentGrid()
        );
    }

    /**
     * @param array<string, mixed> $layout
     */
    public function matrixItemEnabled(array $layout, string $type): bool
    {
        foreach ($this->documentGridItems($layout) as $item) {
            if (($item['element_type'] ?? '') === $type) {
                return ($item['enabled'] ?? true) !== false;
            }
        }

        return false;
    }

    /**
     * @param array<string, mixed> $layout
     */
    public function matrixText(array $layout, string $type, string $fallback): string
    {
        foreach ($this->documentGridItems($layout) as $item) {
            if (($item['element_type'] ?? '') !== $type) {
                continue;
            }
            if (($item['enabled'] ?? true) === false) {
                return '';
            }
            $custom = trim((string) ($item['custom_label'] ?? ''));

            return $custom !== '' ? $custom : $fallback;
        }

        return '';
    }

    /**
     * @param array<string, mixed> $layout
     *
     * @return list<array<string, mixed>>
     */
    public function documentGridItems(array $layout): array
    {
        $items = [];
        foreach ($this->sectionGridsFromLayout($layout) as $grid) {
            foreach ($grid['items'] ?? [] as $item) {
                if (is_array($item)) {
                    $items[] = $item;
                }
            }
        }

        return $items;
    }

    /**
     * @return array<string, string>
     */
    public function fieldTypeLabels(): array
    {
        $out = [];
        foreach (self::ALLOWED_FIELD_TYPES as $type) {
            $out[$type] = self::MATRIX_ELEMENT_DEFINITIONS[$type]['label'] ?? $type;
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
        $primary = strtoupper(trim((string) ($colors['primary'] ?? '#0F766E')));
        $secondary = strtoupper(trim((string) ($colors['secondary'] ?? '#134E4A')));
        $text = strtoupper(trim((string) ($colors['text'] ?? '#1E293B')));
        $dim = $this->normalizeDimensions($layout['dimensions'] ?? null);
        $spacing = $this->normalizeSectionSpacing($layout['section_spacing'] ?? null);
        $labPct = (float) ($dim['header_lab_width_pct'] ?? 58);
        $headerMb  = (float) ($spacing['header']['margin_bottom_pt'] ?? 14);
        $headerGap = (float) ($spacing['header']['row_gap_pt'] ?? 4);
        $clientGap = (float) ($spacing['client']['row_gap_pt'] ?? 6);
        $clientMb  = (float) ($spacing['client']['margin_bottom_pt'] ?? 16);
        $tableMb   = (float) ($spacing['table']['margin_bottom_pt'] ?? 14);
        $tableGap  = (float) ($spacing['table']['row_gap_pt'] ?? 4);
        $totalsMb  = (float) ($spacing['totals']['margin_bottom_pt'] ?? 12);
        $totalsGap = (float) ($spacing['totals']['row_gap_pt'] ?? 4);
        $footerGap = (float) ($spacing['footer']['row_gap_pt'] ?? 6);
        $footerMb  = (float) ($spacing['footer']['margin_bottom_pt'] ?? 0);
        $amountPct = (float) ($dim['items_amount_col_pct'] ?? 26);
        $cellPad = (float) ($dim['items_cell_padding_pt'] ?? 8);
        $totalsW = (float) ($dim['totals_box_width_pt'] ?? 280);
        $totalsLblPct = (float) ($dim['totals_label_col_pct'] ?? 52);

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

        $body = $this->resolveStyle($layout, 'header', 'body');
        $body['color'] = $text;
        $resolveSectionStyle = function (string $sectionId, string $styleKey, array $overrides = []) use ($layout): array {
            return array_merge($this->resolveStyle($layout, $sectionId, $styleKey), $overrides);
        };

        $lines = [
            '@page{margin:14mm 16mm;}',
            '*{box-sizing:border-box;}',
            $cssRule('body', $body),
            'body{line-height:1.45;margin:0;padding:0;}',
            '.accent-bar{height:5px;background:' . $primary . ';margin:0 0 16px 0;}',
            '.comp-sec-header .doc-header{width:100%;border-collapse:separate;border-spacing:0 ' . $headerGap . 'pt;table-layout:fixed;margin-bottom:0;}',
            '.comp-sec-header .doc-header td{vertical-align:top;padding:2pt 0;}',
            '.comp-sec-header .doc-header td.brand-col{width:' . $labPct . '%;}',
            '.comp-sec-header .doc-header td.receipt-badge{width:' . (100 - $labPct) . '%;}',
            $cssRule('.comp-sec-header .brand-name', $resolveSectionStyle('header', 'brand_name')),
            '.comp-sec-header .brand-name{letter-spacing:-0.02em;margin:0 0 4px 0;}',
            $cssRule('.comp-sec-header .brand-tagline', $resolveSectionStyle('header', 'brand_tagline')),
            '.comp-sec-header .brand-tagline{letter-spacing:0.12em;margin:0;}',
            '.comp-sec-header .receipt-badge{text-align:right;}',
            '.comp-sec-header .receipt-badge-inner{display:inline-block;text-align:right;}',
            $cssRule('.comp-sec-header .receipt-badge-title', $resolveSectionStyle('header', 'receipt_badge_title')),
            '.comp-sec-header .receipt-badge-title{letter-spacing:0.18em;margin:0 0 6px 0;}',
            $cssRule('.comp-sec-header .receipt-badge-orden', $resolveSectionStyle('header', 'receipt_badge_orden')),
            '.comp-sec-header .receipt-badge-orden{margin:0;}',
            $cssRule('.comp-sec-header .receipt-badge-fecha', $resolveSectionStyle('header', 'receipt_badge_fecha')),
            '.comp-sec-header .receipt-badge-fecha{margin:6px 0 0 0;}',
            '.comp-sec-client{margin-bottom:' . $clientMb . 'pt;}',
            $cssRule('.comp-sec-client .section-title', $resolveSectionStyle('client', 'section_title')),
            '.comp-sec-client .section-title{letter-spacing:0.14em;margin:0 0 8px 0;padding-bottom:4px;border-bottom:1px solid #cbd5e1;}',
            '.comp-sec-client .panel{background:#f8fafc;border:1px solid #e2e8f0;border-radius:6px;padding:12px 14px;}',
            '.comp-sec-client table.pair-table{width:100%;border-collapse:separate;border-spacing:0 ' . $clientGap . 'pt;}',
            '.comp-sec-client table.pair-table td{vertical-align:top;padding:2pt 16px 2pt 0;line-height:1.5;}',
            '.comp-sec-client table.pair-table td:nth-child(2){padding-right:0;padding-left:8px;}',
            '.comp-sec-client table.pair-table tr:first-child td{padding-top:4px;}',
            $cssRule('.comp-sec-client .pair-k', $resolveSectionStyle('client', 'pair_label')),
            $cssRule('.comp-sec-client .pair-v', $resolveSectionStyle('client', 'pair_value'), ['color' => $text]),
            '.comp-sec-table{margin-bottom:' . $tableMb . 'pt;}',
            $cssRule('.comp-sec-table .section-title', $resolveSectionStyle('table', 'section_title')),
            '.comp-sec-table .section-title{letter-spacing:0.14em;margin:0 0 8px 0;padding-bottom:4px;border-bottom:1px solid #cbd5e1;}',
            '.comp-sec-table table.tbl-items{width:100%;border-collapse:separate;border-spacing:0 ' . $tableGap . 'pt;margin:0;}',
            $cssRule('.comp-sec-table table.tbl-items thead th', $resolveSectionStyle('table', 'items_header'), ['color' => '#FFFFFF']),
            '.comp-sec-table table.tbl-items thead th{background:' . $secondary . ';letter-spacing:0.08em;padding:' . $cellPad . 'pt 10px;text-align:left;}',
            '.comp-sec-table table.tbl-items thead th:last-child{width:' . $amountPct . '%;text-align:right;}',
            $cssRule('.comp-sec-table table.tbl-items tbody td', $resolveSectionStyle('table', 'items_body')),
            '.comp-sec-table table.tbl-items tbody td{padding:' . $cellPad . 'pt 10px;border-bottom:1px solid #e2e8f0;vertical-align:top;}',
            '.comp-sec-table table.tbl-items tbody td.num{width:' . $amountPct . '%;}',
            '.comp-sec-table table.tbl-items tbody tr:nth-child(even) td{background:#fafafa;}',
            $cssRule('.comp-sec-table table.tbl-items tbody td.num', $resolveSectionStyle('table', 'items_num'), ['color' => $text]),
            '.comp-sec-table table.tbl-items tbody td.num{text-align:right;white-space:nowrap;font-variant-numeric:tabular-nums;}',
            '.comp-sec-totals .totals-wrap{width:100%;margin-top:4px;}',
            '.comp-sec-totals .totals-wrap td{vertical-align:top;}',
            '.comp-sec-totals .totals-box{width:' . $totalsW . 'pt;margin:0 0 ' . $totalsMb . 'pt auto;border:1px solid #cbd5e1;border-radius:6px;overflow:hidden;}',
            '.comp-sec-totals .totals-box .t-lbl{width:' . $totalsLblPct . '%;}',
            '.comp-sec-totals .totals-box .t-val{width:' . (100 - $totalsLblPct) . '%;}',
            '.comp-sec-totals .totals-box table{width:100%;border-collapse:collapse;}',
            '.comp-sec-totals .totals-box tr td{padding:6px 12px;border-bottom:1px solid #e2e8f0;}',
            '.comp-sec-totals .totals-box tr:last-child td{border-bottom:none;}',
            $cssRule('.comp-sec-totals .totals-box .t-lbl', $resolveSectionStyle('totals', 'totals_label')),
            '.comp-sec-totals .totals-box .t-lbl{text-align:left;}',
            $cssRule('.comp-sec-totals .totals-box .t-val', $resolveSectionStyle('totals', 'totals_value')),
            '.comp-sec-totals .totals-box .t-val{text-align:right;font-variant-numeric:tabular-nums;white-space:nowrap;}',
            $cssRule('.comp-sec-totals .totals-box tr.total-final td', $resolveSectionStyle('totals', 'totals_final'), ['color' => '#FFFFFF']),
            '.comp-sec-totals .totals-box tr.total-final td{background:' . $primary . ';padding:10px 12px;}',
            '.comp-sec-totals .totals-box tr.total-final .t-lbl{color:#ecfdf5;}',
            '.comp-sec-totals .totals-box tr.total-final .t-val{color:#fff;}',
            '.comp-sec-totals .totals-inner-table{width:100%;border-collapse:separate;border-spacing:0 ' . $totalsGap . 'pt;}',
            $cssRule('.comp-sec-footer .pay-method', $resolveSectionStyle('footer', 'pay_method')),
            '.comp-sec-footer .pay-method{margin-top:' . round($footerGap, 1) . 'pt;margin-bottom:' . $footerMb . 'pt;padding:10px 14px;background:#fffbeb;border:1px solid #fde68a;border-radius:6px;}',
            '.comp-sec-footer .pay-method strong{color:#92400e;}',
            $cssRule('.comp-sec-footer .foot', $resolveSectionStyle('footer', 'footer')),
            '.comp-sec-footer .foot{margin-top:22px;padding-top:14px;border-top:1px solid #e2e8f0;text-align:center;line-height:1.5;}',
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
     * @param array<string, mixed> $options tagline, text_color, show_doctor, footer_note, fallback_primary
     */
    public function renderSectionGridHtml(array $layout, string $sectionId, object $doc, array $options = []): string
    {
        if (! isset(self::MATRIX_SECTIONS[$sectionId])) {
            return '';
        }

        $sections = $this->sectionGridsFromLayout($layout);
        $grid     = $sections[$sectionId] ?? $this->buildDefaultSectionGrids()[$sectionId];
        $cols     = max(1, (int) ($grid['columns'] ?? 2));
        $rows     = max(1, (int) ($grid['rows'] ?? 2));
        $items    = is_array($grid['items'] ?? null) ? $grid['items'] : [];
        $spacing  = $this->normalizeSectionSpacing($layout['section_spacing'] ?? null);
        $rowGap   = (float) ($spacing[$sectionId]['row_gap_pt'] ?? 4);
        $dim      = $this->normalizeDimensions($layout['dimensions'] ?? null);
        $labPct   = (float) ($dim['header_lab_width_pct'] ?? 58);
        $tagline           = (string) ($options['tagline'] ?? 'Constancia de pago');
        $showDoctor        = (bool) ($options['show_doctor'] ?? true);
        $footerNote        = (string) ($options['footer_note'] ?? '');
        $fallbackPrimary   = (string) ($options['fallback_primary'] ?? '#0F766E');
        $badgeBoxStyle     = $this->receiptBadgeBoxStyle($layout, $fallbackPrimary);
        $labBgStyle        = $this->headerLabCellExtraStyle($layout);

        $hasContent = false;
        foreach ($items as $item) {
            if (is_array($item) && ($item['enabled'] ?? true) !== false && ($item['element_type'] ?? '') !== 'totals_box') {
                $hasContent = true;
                break;
            }
        }
        if (! $hasContent) {
            return '';
        }

        $tableClass = match ($sectionId) {
            'header' => 'doc-header',
            'client' => 'pair-table',
            default  => 'comp-sec-grid comp-sec-' . $sectionId,
        };
        $tableStyle = $sectionId === 'header'
            ? $this->docHeaderTableStyle($layout)
            : $this->matrixTableSpacingStyle($rowGap);

        $html = '<table class="' . $tableClass . '" style="' . $tableStyle . '"><tbody>';
        for ($r = 0; $r < $rows; $r++) {
            $html .= '<tr>';
            $c = 0;
            while ($c < $cols) {
                $cell = $this->matrixCellAt($items, $r, $c);
                if ($cell['covered']) {
                    $c++;
                    continue;
                }
                $item = $cell['item'];
                if ($item === null) {
                    $html .= '<td></td>';
                    $c++;
                    continue;
                }
                $colSpan = max(1, min($cols - $c, (int) ($item['col_span'] ?? 1)));
                $type    = (string) ($item['element_type'] ?? '');
                $inner   = $this->renderMatrixCellHtml($item, $layout, $sectionId, $doc, $tagline, $showDoctor, $footerNote);
                if ($inner === '') {
                    $html .= '<td></td>';
                    $c += $colSpan;
                    continue;
                }
                $inner = $this->wrapMatrixItemHtml($inner, $item);
                $cellPad = $this->matrixCellGapPadding($rowGap, $r, $rows);
                $alignStyle = $this->matrixCellAlignStyle($item);
                $spanAttr = $this->matrixSpanAttrs($item, $c, $cols, $r, $rows);

                if ($sectionId === 'header') {
                    $isBadgeCol = $this->shouldWrapReceiptBadge($type, $c, $cols);
                    if ($isBadgeCol) {
                        $inner = '<div class="receipt-badge"><div class="receipt-badge-inner" style="' . $badgeBoxStyle . '">' . $inner . '</div></div>';
                    }
                    $width = $this->headerColumnWidthPct($c, $colSpan, $cols, $labPct);
                    $widthAttr = $colSpan >= $cols ? '' : 'width:' . $width . '%;';
                    $cellBg = $isBadgeCol ? '' : $labBgStyle;
                    $html .= '<td style="' . $widthAttr . $cellPad . $cellBg . $alignStyle . '"' . $spanAttr . '>' . $inner . '</td>';
                } else {
                    $html .= '<td style="' . $cellPad . $alignStyle . '"' . $spanAttr . '>' . $inner . '</td>';
                }
                $c += $colSpan;
            }
            $html .= '</tr>';
        }
        $html .= '</tbody></table>';

        return $html;
    }

    /**
     * @param array<string, mixed> $item
     * @param array<string, mixed> $layout
     * @param object               $doc
     */
    public function renderMatrixCellHtml(
        array $item,
        array $layout,
        string $placementSectionId,
        object $doc,
        string $tagline = '',
        bool $showDoctor = true,
        string $footerNote = ''
    ): string {
        if (($item['enabled'] ?? true) === false) {
            return '';
        }

        $type      = (string) ($item['element_type'] ?? '');
        $styleSec  = $this->styleSectionForElementType($type, $placementSectionId);

        if (in_array($type, self::ALLOWED_FIELD_TYPES, true)) {
            if ($type === 'medico' && ! $showDoctor) {
                return '';
            }
            $value = $this->resolveFieldValue($type, $doc, $showDoctor);
            $label = ($item['show_label'] ?? true) !== false
                ? trim((string) ($item['custom_label'] ?? self::FIELD_DEFAULT_LABELS[$type] ?? ''))
                : '';
            $html = '';
            if ($label !== '') {
                $html .= '<span class="pair-k" style="' . $this->inlineStyleFor($layout, $styleSec, 'pair_label') . '">' . esc($label) . ' </span>';
            }

            return $html . '<span class="pair-v" style="' . $this->inlineStyleFor($layout, $styleSec, 'pair_value') . '">' . esc($value) . '</span>';
        }

        return match ($type) {
            'brand_name' => '<p class="brand-name" style="' . $this->inlineStyleFor($layout, $styleSec, 'brand_name') . '">' . esc(trim((string) ($doc->empresaNombre ?? ''))) . '</p>',
            'brand_tagline' => '<p class="brand-tagline" style="' . $this->inlineStyleFor($layout, $styleSec, 'brand_tagline') . '">' . esc($tagline) . '</p>',
            'receipt_badge_title' => $this->renderReceiptBadgeTitleHtml($layout),
            'receipt_badge_orden' => '<p class="receipt-badge-orden" style="' . $this->inlineStyleFor($layout, $styleSec, 'receipt_badge_orden') . '">N.º ' . esc(trim((string) ($doc->numeroRecibo ?? $doc->ordenNumero ?? ''))) . '</p>',
            'receipt_badge_fecha' => '<p class="receipt-badge-fecha" style="' . $this->inlineStyleFor($layout, $styleSec, 'receipt_badge_fecha') . '">' . esc(trim((string) ($doc->fechaEmision ?? ''))) . '</p>',
            'section_client', 'section_items' => $this->renderSectionTitleHtml($layout, $type),
            'items_header_desc', 'items_header_amount' => $this->renderItemsHeaderCellHtml($layout, $type, $item),
            'total_orden', 'total_pagado', 'total_saldo' => $this->renderTotalRowHtml($layout, $type, $doc, $item),
            'pay_method' => $this->renderPayMethodHtml($layout, $doc, $item),
            'footer' => $footerNote !== ''
                ? '<div class="foot" style="' . $this->inlineStyleFor($layout, $styleSec, 'footer') . '">' . esc($footerNote) . '</div>'
                : '',
            default => '',
        };
    }

    private function styleSectionForElementType(string $type, string $placementSectionId): string
    {
        $native = $this->sectionIdForElementType($type);

        return $native !== '' ? $native : $placementSectionId;
    }

    /**
     * @param array<string, mixed> $layout
     */
    private function renderSectionTitleHtml(array $layout, string $type): string
    {
        $styleSec = $this->styleSectionForElementType($type, $type === 'section_client' ? 'client' : 'table');
        $fallback = $type === 'section_client' ? 'Cliente y atención' : 'Detalle de conceptos';
        $txt      = $this->matrixText($layout, $type, $fallback);
        if ($txt === '') {
            return '';
        }

        return '<p class="section-title" style="' . $this->inlineStyleFor($layout, $styleSec, 'section_title') . '">' . esc($txt) . '</p>';
    }

    /**
     * @param array<string, mixed> $layout
     * @param array<string, mixed> $item
     */
    private function renderItemsHeaderCellHtml(array $layout, string $type, array $item): string
    {
        $fallback = $type === 'items_header_amount' ? 'Importe ($)' : 'Descripción';
        $txt      = $this->matrixText($layout, $type, $fallback);
        if ($txt === '') {
            return '';
        }
        $align = $this->normalizeItemAlignment($item, self::MATRIX_ELEMENT_DEFINITIONS[$type] ?? []);
        $textAlign = match ($align['align_h']) {
            'right'  => 'right',
            'center' => 'center',
            default  => 'left',
        };
        $dim = $this->normalizeDimensions($layout['dimensions'] ?? null);
        $pad = (float) ($dim['items_cell_padding_pt'] ?? 8);
        $bg  = $this->itemsTableHeaderBgColor($layout, '#134E4A');

        return '<span style="' . $this->inlineStyleFor($layout, 'table', 'items_header') . ';background:' . esc($bg) . ';display:block;text-align:' . $textAlign . ';padding:' . $pad . 'pt 8px;">' . esc($txt) . '</span>';
    }

    /**
     * @param array<string, mixed> $layout
     * @param array<string, mixed> $item
     * @param object               $doc
     */
    private function renderTotalRowHtml(array $layout, string $type, object $doc, array $item): string
    {
        $lbl = $this->matrixText($layout, $type, match ($type) {
            'total_pagado' => 'Monto pagado',
            'total_saldo'  => 'Saldo',
            default        => 'Total orden',
        });
        if ($lbl === '') {
            return '';
        }
        $sym = (string) ($doc->monedaSimbolo ?? '$');
        $fmt = static fn (float $n): string => number_format($n, 2, ',', '.');
        $val = match ($type) {
            'total_pagado' => $sym . ' ' . $fmt((float) ($doc->montoPagado ?? 0)),
            'total_saldo'  => $sym . ' ' . $fmt((float) ($doc->saldo ?? 0)),
            default        => $sym . ' ' . $fmt((float) ($doc->total ?? 0)),
        };
        $isFinal  = $type === 'total_saldo';
        $styleSec = 'totals';
        $lblStyle = $isFinal
            ? $this->inlineStyleFor($layout, $styleSec, 'totals_final', ['color' => '#FFFFFF'])
            : $this->inlineStyleFor($layout, $styleSec, 'totals_label');
        $valStyle = $isFinal
            ? $this->inlineStyleFor($layout, $styleSec, 'totals_final', ['color' => '#FFFFFF'])
            : $this->inlineStyleFor($layout, $styleSec, 'totals_value');
        $bg       = '';
        $dim       = $this->normalizeDimensions($layout['dimensions'] ?? null);
        $lblPct    = (float) ($dim['totals_label_col_pct'] ?? 52);
        $alignAttr = $this->matrixCellAlignStyle($item);

        return '<table style="width:100%;border-collapse:collapse;' . $alignAttr . '"><tr class="' . ($isFinal ? 'total-final' : '') . '">' .
            '<td class="t-lbl" style="width:' . $lblPct . '%;' . $lblStyle . ';' . $bg . '">' . esc($lbl) . '</td>' .
            '<td class="t-val" style="width:' . (100 - $lblPct) . '%;' . $valStyle . ';' . $bg . '">' . esc($val) . '</td></tr></table>';
    }

    /**
     * @param array<string, mixed> $layout
     * @param object               $doc
     * @param array<string, mixed> $item
     */
    private function renderPayMethodHtml(array $layout, object $doc, array $item): string
    {
        $lbl = $this->matrixText($layout, 'pay_method', 'Forma de pago:');
        if ($lbl === '') {
            return '';
        }

        return '<div class="pay-method" style="' . $this->inlineStyleFor($layout, 'footer', 'pay_method') . '"><strong>' . esc($lbl) . '</strong> ' . esc(trim((string) ($doc->formaPagoEtiqueta ?? ''))) . '</div>';
    }

    /**
     * @param array<string, mixed> $layout
     * @param object               $doc
     */
    public function renderClientGridHtml(array $layout, object $doc, bool $showDoctor = true, ?string $textColor = null): string
    {
        return $this->renderSectionGridHtml($layout, 'client', $doc, [
            'show_doctor' => $showDoctor,
            'text_color'  => $textColor,
        ]);
    }

    /**
     * @param list<array<string, mixed>> $items
     *
     * @return array{item: array<string, mixed>|null, covered: bool}
     */
    private function matrixCellAt(array $items, int $row, int $col): array
    {
        foreach ($items as $item) {
            if (! is_array($item) || ($item['enabled'] ?? true) === false) {
                continue;
            }
            $r0 = (int) ($item['row'] ?? 0);
            $c0 = (int) ($item['col'] ?? 0);
            $cs = max(1, (int) ($item['col_span'] ?? 1));
            $rs = max(1, (int) ($item['row_span'] ?? 1));
            if ($row >= $r0 && $row < $r0 + $rs && $col >= $c0 && $col < $c0 + $cs) {
                $isAnchor = $row === $r0 && $col === $c0;

                return [
                    'item'    => $isAnchor ? $item : null,
                    'covered' => ! $isAnchor,
                ];
            }
        }

        return ['item' => null, 'covered' => false];
    }

    /**
     * @param list<array<string, mixed>> $items
     */
    private function matrixSpanAttrs(array $item, int $col, int $cols, int $row, int $rows): string
    {
        $colSpan = max(1, min($cols - $col, (int) ($item['col_span'] ?? 1)));
        $rowSpan = max(1, min($rows - $row, (int) ($item['row_span'] ?? 1)));
        $attr    = '';
        if ($colSpan > 1) {
            $attr .= ' colspan="' . $colSpan . '"';
        }
        if ($rowSpan > 1) {
            $attr .= ' rowspan="' . $rowSpan . '"';
        }

        return $attr;
    }

    /**
     * Encabezado del comprobante según la matriz de la sección «header» (orden filas/columnas).
     *
     * @param array<string, mixed> $layout
     * @param object               $doc
     */
    public function renderHeaderGridHtml(array $layout, object $doc, string $tagline, ?string $textColor = null, string $fallbackPrimaryColor = '#0F766E'): string
    {
        return $this->renderSectionGridHtml($layout, 'header', $doc, [
            'tagline'          => $tagline,
            'text_color'       => $textColor,
            'fallback_primary' => $fallbackPrimaryColor,
        ]);
    }

    private function headerColumnWidthPct(int $col, int $colSpan, int $cols, float $labPct): float
    {
        if ($cols === 2) {
            if ($colSpan >= 2) {
                return 100.0;
            }

            return $col === 0 ? $labPct : (100 - $labPct);
        }

        return round((100 / max(1, $cols)) * max(1, $colSpan), 2);
    }

    private function isReceiptBadgeElementType(string $type): bool
    {
        return in_array($type, ['receipt_badge_title', 'receipt_badge_orden', 'receipt_badge_fecha'], true);
    }

    private function shouldWrapReceiptBadge(string $type, int $col, int $cols): bool
    {
        return $this->isReceiptBadgeElementType($type) && $cols >= 2 && $col === $cols - 1;
    }

    /**
     * @param array<string, mixed> $layout
     */
    private function renderReceiptBadgeTitleHtml(array $layout): string
    {
        $title = $this->matrixText($layout, 'receipt_badge_title', 'Recibo de pago');
        if ($title === '') {
            return '';
        }

        return '<p class="receipt-badge-title" style="' . $this->inlineStyleFor($layout, 'header', 'receipt_badge_title') . '">' . esc($title) . '</p>';
    }
}
