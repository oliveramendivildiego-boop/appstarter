<?php

namespace App\Services;

use App\Models\AppConfigModel;
use App\Models\ReportPdfTemplateModel;
use App\Services\ReportLayout\ReportPaginationMode;

/**
 * Plantillas de orden de bloques para el PDF de resultados de registro.
 */
class ReportPdfLayoutService
{
    public const DEFAULT_TEXT_STYLE = [
        'font_family'       => 'DejaVu Sans',
        'font_size_pt'      => 10.0,
        'font_weight'       => 'normal',
        'font_color'        => '#333333',
        'font_style'        => 'normal',
        'text_transform'    => 'none',
        'letter_spacing_em' => 0.0,
        'line_height'       => 1.35,
        'text_shadow'       => 'none',
    ];

    public const DEFAULT_CARD_HEADER_STYLE = [
        'bg_color'        => '#E9ECEF',
        'text_color'      => '#212529',
        'bg_transparent'  => false,
        'font_family'     => 'DejaVu Sans',
        'font_size_pt'    => 10.0,
        'font_weight'     => '700',
        'font_style'      => 'normal',
        'text_transform'  => 'uppercase',
    ];
    public const DEFAULT_NOTES_STYLE = [
        'title_bg_color'    => '#FFF3CD',
        'title_text_color'  => '#664D03',
        'title_transparent' => false,
        'body_bg_color'     => '#FFFFFF',
        'body_text_color'   => '#333333',
        'body_transparent'  => false,
        'font_family'       => 'DejaVu Sans',
        'font_size_pt'      => 9.5,
        'font_weight'       => 'normal',
        'font_style'        => 'normal',
        'text_transform'    => 'none',
        'line_height'       => 1.4,
        'column_border_width_px' => 1,
        'column_border_color' => '#DDDDDD',
        'section_title'     => 'NOTAS',
        'show_section_title'=> true,
    ];

    /** Textos por defecto del bloque «Validación y aprobación» en el PDF. */
    public const DEFAULT_LAB_FIRMAS_LABELS = [
        'section_title'       => 'VALIDACIÓN Y APROBACIÓN',
        'show_section_title'  => true,
        'label_validator'     => 'Verificado por:',
        'show_label_validator'=> true,
        'validator_line_mode' => 'stacked',
        'label_seal'          => '',
        'show_label_seal'     => true,
        'label_seal_line_mode'=> 'stacked',
        'label_firma'         => 'ATENTAMENTE',
        'show_label_firma'    => true,
        'label_firma_line_mode'=> 'stacked',
        'label_approver'      => '',
        'show_label_approver' => true,
        'label_approver_line_mode' => 'stacked',
        'label_cargo'         => '',
        'show_label_cargo'    => true,
        'label_cargo_line_mode'=> 'stacked',
        'label_matricula'     => 'Matrícula:',
        'show_label_matricula'=> true,
        'label_matricula_line_mode' => 'stacked',
        'body_transparent'    => false,
        'column_border_width_px' => 1,
        'column_border_color' => '#DDDDDD',
        /** per_group | block_end | both */
        'placement'           => 'per_group',
        'show_area_heading'   => false,
        'area_heading_color'  => '#664D03',
        'area_heading_font_size_pt' => 9.0,
        'area_heading_font_weight'  => '600',
        'area_heading_text_transform' => 'uppercase',
        'inline_margin_top_pt'    => 8.0,
        'inline_margin_bottom_pt' => 6.0,
        'seal_max_height_px'      => 110,
        'signature_max_height_px' => 72,
        'signature_max_width_px'  => 220,
    ];

    public const LAB_FIRMAS_PLACEMENTS = ['per_group', 'block_end', 'both'];

    /** @var list<string> */
    public const GRUPO_PRUEBA_PAGE_BREAK_MODES = [
        'flow',
        'keep_segment',
        'keep_together_if_fits',
        'keep_together_if_fits_auto_order',
        'keep_together',
        'keep_together_compact',
    ];

    /** @var list<string> */
    public const REPORT_PAGINATION_MODES = ReportPaginationMode::ALL;

    /** @var array{mode: string, repeat_header_on_split: bool, compact_min_scale_percent: int, compact_cell_padding_px: int, compact_aggressive: bool, min_remaining_mm_to_force_break: float} */
    public const DEFAULT_GRUPO_PRUEBA_PAGE_BREAK = [
        'mode'                              => 'keep_together_if_fits',
        'repeat_header_on_split'            => true,
        'compact_min_scale_percent'         => 85,
        'compact_cell_padding_px'           => 0,
        'compact_aggressive'                => false,
        'min_remaining_mm_to_force_break'   => 0.0,
    ];

    /** @var array{enabled: bool} */
    public const DEFAULT_ORDER_SHEET_HEADER = [
        'enabled' => false,
    ];

    /**
     * Solo estos tipos se reinyectan si faltan (migración); no se fuerza título/validador/sello eliminados por el usuario.
     *
     * @var list<string>
     */
    public const LAB_FIRMAS_MERGE_MISSING_TYPES = [
        'lab_firmas_approver_signature',
        'lab_firmas_approver_name',
        'lab_firmas_approver_cargo',
        'lab_firmas_matricula',
    ];

    public const LAB_FIRMAS_TEXT_MAX_LEN = 120;

    public const CUSTOM_TEXT_LABEL_MAX_LEN = 200;

    public const CUSTOM_TEXT_VALUE_MAX_LEN = 500;

    /** Fondo/tipografía/bordes entre columnas: encabezado superior, paciente/médico, pie. */
    public const DEFAULT_SECTION_GRID_WRAP = [
        'body_bg_color'          => '#FFFFFF',
        'body_text_color'        => '#333333',
        'body_transparent'       => false,
        'font_family'            => 'DejaVu Sans',
        'font_size_pt'           => 9.5,
        'font_weight'            => 'normal',
        'font_style'             => 'normal',
        'text_transform'         => 'none',
        'line_height'            => 1.35,
        'column_border_width_px' => 0,
        'column_border_color'    => '#DDDDDD',
    ];

    /** @var array<string, string> */
    public const PATIENT_DOCTOR_GRID_LABEL_DEFAULTS = [
        'paciente_nombre'   => 'Paciente:',
        'paciente_genero'   => 'Género:',
        'paciente_edad'     => 'Edad:',
        'paciente_telefono' => 'Teléfono:',
        'diagnostico_presuntivo' => 'Diagnóstico presuntivo:',
        'medico'            => 'Médico:',
        'fecha_recepcion'   => 'Fecha de recepción:',
        'fecha_reporte'     => 'Fecha de reporte:',
        'numero_orden'      => 'No. Orden:',
    ];

    /**
     * Etiquetas opcionales del bloque encabezado (logo, datos de laboratorio). El QR usa label_qr_hint aparte.
     *
     * @var array<string, string>
     */
    public const HEADER_GRID_LABEL_DEFAULTS = [
        'logo'        => '',
        'lab_company' => '',
        'lab_address' => '',
        'lab_phone'   => 'Tel:',
        'lab_email'   => 'Email:',
        'lab_website' => '',
        'paciente_institucion' => 'Institución:',
        'pdf_pages_total'      => 'Páginas:',
        'pdf_pagination'       => 'Página',
    ];

    public const DEFAULT_RESULTS_TABLE_STYLE = [
        'header_bg_color'   => '#0066CC',
        'header_text_color' => '#FFFFFF',
        'body_bg_color'     => '#FFFFFF',
        'body_transparent'  => false,
        'body_text_color'   => '#333333',
        'border_color'      => '#DDDDDD',
        'segment_bg_color'  => '#E9ECEF',
        'segment_transparent' => false,
        'segment_border_color' => '#DDDDDD',
        'segment_border_width_px' => 1,
        'segment_shadow'    => 'none',
        'segment_padding_top_px'    => 6,
        'segment_padding_bottom_px' => 6,
        'font_family'       => 'DejaVu Sans',
        'font_size_pt'      => 9.0,
        'font_weight'       => 'normal',
        'font_style'        => 'normal',
        'text_transform'    => 'none',
        'line_height'       => 1.35,
        'cell_padding_v_px' => 6,
        'table_margin_top_px' => 15,
        'table_margin_bottom_px' => 15,
        'grupo_prueba_gap_px' => 10,
        'subgrupo_prueba_gap_px' => 18,
        'matrix_text_align' => 'center',
        'matrix_vertical_align' => 'middle',
        'matrix_text_color' => '#333333',
        'matrix_font_size_pt' => 8.0,
        'matrix_font_weight' => 'normal',
        'matrix_font_style' => 'normal',
        'matrix_text_transform' => 'none',
        'matrix_header_text_color' => '#1F2937',
        'matrix_header_font_family' => 'DejaVu Sans',
        'matrix_header_font_size_pt' => 8.0,
        'matrix_header_font_weight' => 'bold',
        'matrix_header_font_style' => 'normal',
        'matrix_header_text_transform' => 'uppercase',
        'matrix_col_population_align' => 'left',
        'matrix_col_parameter_align' => 'left',
        'matrix_col_sex_align' => 'center',
        'matrix_col_reference_align' => 'center',
        'matrix_hdr_population_align' => 'left',
        'matrix_hdr_parameter_align' => 'left',
        'matrix_hdr_sex_align' => 'center',
        'matrix_hdr_reference_align' => 'center',
        'results_hdr_analisis_align' => 'left',
        'results_col_analisis_align' => 'left',
        'results_hdr_resultado_align' => 'center',
        'results_col_resultado_align' => 'center',
        'results_hdr_rango_align' => 'center',
        'results_col_rango_align' => 'center',
        'results_hdr_interpretacion_align' => 'center',
        'results_col_interpretacion_align' => 'center',
        'grupo_cabecera_title_mode'        => 'grupo_analisis',
        'grupo_cabecera_show_tipo_muestra' => true,
        'grupo_cabecera_show_metodo'       => true,
        'grupo_cabecera_title_margin_top_px'    => 0,
        'grupo_cabecera_title_margin_bottom_px' => 6,
        'grupo_cabecera_tipo_muestra_margin_top_px'    => 0,
        'grupo_cabecera_tipo_muestra_margin_bottom_px' => 10,
        'grupo_cabecera_metodo_margin_top_px'    => 0,
        'grupo_cabecera_metodo_margin_bottom_px' => 10,
        'grupo_area_separator_enabled'     => false,
        'grupo_area_separator_color'       => '#DDDDDD',
        'grupo_area_separator_width_px'    => 1,
        'grupo_area_separator_font_size_pt' => 11.0,
        'grupo_area_separator_font_weight' => 'bold',
        'grupo_area_separator_margin_top_px'    => 10,
        'grupo_area_separator_margin_bottom_px' => 10,
    ];

    /** @var list<string> */
    public const ALLOWED_GRUPO_CABECERA_TITLE_MODES = ['grupo_analisis', 'solo_analisis', 'grupo_analisis_sin_cabecera_tabla'];

    public const DEFAULT_HEADER_SECTION_STYLE = [
        'separator_color'   => '#0066CC',
    ];

    /** @var list<string> */
    public const DEFAULT_BLOCK_ORDER = ['header', 'patient_doctor', 'results', 'notes', 'lab_firmas', 'footer'];

    /** @var list<string> Orden por defecto de cada dato dentro del bloque paciente/médico */
    public const PATIENT_DOCTOR_FIELD_ORDER = [
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

    /** Elementos del encabezado del PDF (logo, datos lab, QR) — columna 0=izq, 1=centro, 2=der (-1 = oculto en UI, se guarda como enabled false) */
    public const HEADER_FIELD_ORDER = [
        'logo',
        'lab_company',
        'lab_address',
        'paciente_institucion',
        'lab_phone',
        'lab_email',
        'lab_website',
        'pdf_pages_total',
        'pdf_pagination',
        'qr',
    ];

    /** Pie de página: columnas 0 / 1 / 2 como el encabezado */
    public const FOOTER_FIELD_ORDER = [
        'footer_company',
        'footer_generated',
        'footer_policy',
    ];

    /**
     * Tipos de elemento que pueden colocarse en cualquier sección (con duplicados).
     *
     * @var list<string>
     */
    public const ELEMENT_TYPES = [
        'logo',
        'lab_company',
        'lab_address',
        'lab_phone',
        'lab_email',
        'lab_website',
        'paciente_institucion',
        'pdf_pages_total',
        'pdf_pagination',
        'qr',
        'custom_text',
        'paciente_nombre',
        'paciente_genero',
        'paciente_edad',
        'paciente_telefono',
        'diagnostico_presuntivo',
        'medico',
        'fecha_recepcion',
        'fecha_reporte',
        'numero_orden',
        'footer_company',
        'footer_generated',
        'footer_policy',
        'lab_firmas_title',
        'lab_firmas_validator',
        'lab_firmas_seal',
        'lab_firmas_approver_signature',
        'lab_firmas_approver_name',
        'lab_firmas_approver_cargo',
        'lab_firmas_matricula',
    ];

    /** @var list<string> Elementos del bloque «Validación y aprobación» (solo sección lab_firmas). */
    public const LAB_FIRMAS_ELEMENT_TYPES = [
        'lab_firmas_title',
        'lab_firmas_validator',
        'lab_firmas_seal',
        'lab_firmas_approver_signature',
        'lab_firmas_approver_name',
        'lab_firmas_approver_cargo',
        'lab_firmas_matricula',
    ];

    /** @var list<string> */
    public const ALLOWED_PDF_FONT_FAMILIES = ['DejaVu Sans', 'Helvetica', 'Arial', 'Times New Roman', 'Courier New'];

    /** @var list<string> */
    public const ALLOWED_PDF_FONT_WEIGHTS = ['normal', 'bold', '100', '200', '300', '400', '500', '600', '700', '800', '900'];

    /** @var list<string> */
    public const ALLOWED_PDF_FONT_STYLES = ['normal', 'italic', 'oblique'];

    /** @var list<string> */
    public const ALLOWED_PDF_TEXT_TRANSFORMS = ['none', 'uppercase', 'lowercase', 'capitalize'];

    /** @var list<string> */
    public const ALLOWED_PDF_TEXT_SHADOWS = ['none', 'soft', 'medium', 'strong'];
    /** @var list<string> */
    public const ALLOWED_PDF_TEXT_ALIGNS = ['left', 'center', 'right', 'justify'];
    /** @var list<string> */
    public const ALLOWED_PDF_VERTICAL_ALIGNS = ['top', 'middle', 'bottom'];
    /** @var list<string> */
    public const ALLOWED_INSTANCE_ALIGN_H = ['left', 'center', 'right'];
    /** @var list<string> */
    public const ALLOWED_INSTANCE_ALIGN_V = ['top', 'middle', 'bottom'];

    public const SECTION_COLUMN_MIN = 1;

    public const SECTION_COLUMN_MAX = 6;

    /** Márgenes por defecto del cuerpo del PDF (mm) */
    public const DEFAULT_MARGINS_MM = [
        'top'    => 15.0,
        'right'  => 15.0,
        'bottom' => 15.0,
        'left'   => 15.0,
    ];

    /** Altura reservada para el pie fijo en Dompdf (mm), dentro del margen inferior de @page. */
    public const DEFAULT_PDF_FOOTER_RESERVE_MM = 22.0;

    /** Altura aproximada de la línea Paciente / No. Orden (mm). */
    public const ORDER_SHEET_HEADER_HEIGHT_MM = 4.5;

    /** Separación entre la cabecera orden y el bloque de pie (mm). */
    public const ORDER_SHEET_HEADER_GAP_ABOVE_FOOTER_MM = 1.5;

    /**
     * Marca de agua centrada (PDF e impresión). file = ruta relativa a WRITEPATH.
     *
     * @return array{enabled: bool, opacity: float, size_percent: int, file: ?string}
     */
    public static function defaultWatermarkStatic(): array
    {
        return [
            'enabled'      => false,
            'opacity'      => 0.12,
            'size_percent' => 45,
            'file'         => null,
        ];
    }

    /**
     * @return array{card_header: array{bg_color: string, text_color: string, font_family: string, font_size_pt: float, font_weight: string, font_style: string, text_transform: string}}
     */
    public static function defaultPageStyleStatic(): array
    {
        return [
            'card_header'         => self::DEFAULT_CARD_HEADER_STYLE,
            'notes'               => self::DEFAULT_NOTES_STYLE,
            'lab_firmas'          => array_merge(self::DEFAULT_NOTES_STYLE, self::DEFAULT_LAB_FIRMAS_LABELS),
            'results_table'       => self::DEFAULT_RESULTS_TABLE_STYLE,
            'header_section'      => self::DEFAULT_HEADER_SECTION_STYLE,
            'header_grid'         => self::normalizeHeaderGridStyle([]),
            'patient_doctor_grid' => self::normalizePatientDoctorGridStyle([]),
            'footer_grid'         => self::normalizeFooterGridStyle([]),
            'print_pagination'        => self::normalizePrintPaginationStyle([]),
            'pagination_mode'         => ReportPaginationMode::default(),
            'grupo_prueba_page_break' => self::normalizeGrupoPruebaPageBreakStyle([]),
            'order_sheet_header'      => self::normalizeOrderSheetHeaderStyle([]),
        ];
    }

    /**
     * Valida ruta relativa guardada en layout_json (sin ..).
     */
    public static function sanitizeWatermarkRelativePath(string $path): ?string
    {
        $path = str_replace('\\', '/', trim($path));
        if ($path === '' || str_contains($path, '..')) {
            return null;
        }
        if (! preg_match('#^uploads/report_pdf_templates/[0-9]+/[a-zA-Z0-9._-]+$#', $path)) {
            return null;
        }

        return $path;
    }

    /**
     * Data URI para CSS/HTML (dompdf y navegador).
     * Si la marca de agua está activa y no hay archivo válido, usa el logo del laboratorio.
     */
    public static function getWatermarkDataUriForLayout(array $layout, ?string $fallbackLogoDataUri = null): ?string
    {
        $w = is_array($layout['watermark'] ?? null) ? $layout['watermark'] : [];
        if (empty($w['enabled'])) {
            return null;
        }

        $fileRaw = isset($w['file']) ? (string) $w['file'] : '';
        if ($fileRaw !== '') {
            $rel = self::sanitizeWatermarkRelativePath($fileRaw);
            if ($rel !== null) {
                $full = WRITEPATH . str_replace('/', DIRECTORY_SEPARATOR, $rel);
                if (is_file($full) && is_readable($full)) {
                    $data = @file_get_contents($full);
                    if ($data !== false) {
                        $finfo = finfo_open(FILEINFO_MIME_TYPE);
                        $mime  = $finfo ? finfo_file($finfo, $full) : false;
                        if ($finfo) {
                            finfo_close($finfo);
                        }

                        return 'data:' . ($mime ?: 'image/png') . ';base64,' . base64_encode($data);
                    }
                }
            }
        }

        $fallbackLogoDataUri = trim((string) $fallbackLogoDataUri);

        return $fallbackLogoDataUri !== '' ? $fallbackLogoDataUri : null;
    }

    /**
     * @return array{uri: string, path: ?string, opacity: float, size_percent: int}|null
     */
    public static function watermarkRenderPayloadForLayout(
        array $layout,
        ?string $fallbackLogoDataUri = null,
        ?string $fallbackLogoPath = null
    ): ?array {
        $w = is_array($layout['watermark'] ?? null) ? $layout['watermark'] : [];
        $uri = self::getWatermarkDataUriForLayout($layout, $fallbackLogoDataUri);
        if ($uri === null || $uri === '') {
            return null;
        }

        $path = null;
        $fileRaw = isset($w['file']) ? (string) $w['file'] : '';
        if ($fileRaw !== '') {
            $rel = self::sanitizeWatermarkRelativePath($fileRaw);
            if ($rel !== null) {
                $full = WRITEPATH . str_replace('/', DIRECTORY_SEPARATOR, $rel);
                if (is_file($full) && is_readable($full)) {
                    $path = $full;
                }
            }
        }
        if ($path === null) {
            $fallbackLogoPath = trim((string) $fallbackLogoPath);
            if ($fallbackLogoPath !== '' && is_file($fallbackLogoPath) && is_readable($fallbackLogoPath)) {
                $path = $fallbackLogoPath;
            }
        }

        $opacity = isset($w['opacity']) ? (float) $w['opacity'] : 0.12;
        $opacity = round(max(0.05, min(0.9, $opacity)), 2);
        $size    = isset($w['size_percent']) ? (int) $w['size_percent'] : 45;
        $size    = max(10, min(95, $size));

        return [
            'uri'          => $uri,
            'path'         => $path,
            'opacity'      => $opacity,
            'size_percent' => $size,
        ];
    }

    public static function defaultColumnForPatientField(string $id): int
    {
        return in_array($id, ['paciente_nombre', 'paciente_genero', 'paciente_edad', 'paciente_telefono'], true) ? 0 : 1;
    }

    public static function defaultColumnForHeaderField(string $id): int
    {
        if ($id === 'logo') {
            return 0;
        }
        if ($id === 'qr') {
            return 2;
        }
        if ($id === 'pdf_pages_total' || $id === 'pdf_pagination') {
            return 2;
        }

        return 1;
    }

    /**
     * @return list<array{id: string, enabled: bool, column: int}>
     */
    public static function defaultHeaderFieldsStatic(): array
    {
        $out = [];
        foreach (self::HEADER_FIELD_ORDER as $id) {
            $out[] = [
                'id'      => $id,
                'enabled' => true,
                'column'  => self::defaultColumnForHeaderField($id),
            ];
        }

        return $out;
    }

    /**
     * @return array<string, string>
     */
    public static function headerFieldLabels(): array
    {
        return [
            'logo'         => 'Logo (imagen o nombre si no hay logo)',
            'lab_company'  => 'Nombre de la empresa / laboratorio',
            'lab_address'  => 'Dirección',
            'lab_phone'    => 'Teléfono',
            'lab_email'    => 'Correo electrónico',
            'lab_website'  => 'Sitio web',
            'paciente_institucion' => 'Institución del paciente',
            'pdf_pages_total'      => 'Número de páginas (total)',
            'pdf_pagination'       => 'Paginación (Página X de Y)',
            'qr'           => 'Código QR (enlace al reporte en línea)',
        ];
    }

    /**
     * @return array<string, string>
     */
    public static function headerPreviewSamples(): array
    {
        return [
            'logo'         => '[Logo]',
            'lab_company'  => 'Laboratorio Clínico Ejemplo',
            'lab_address'  => 'Calle Principal 123, Ciudad',
            'paciente_institucion' => 'Institución del paciente (Ej.)',
            'lab_phone'    => '555-0100',
            'lab_email'    => 'contacto@lab.ejemplo',
            'lab_website'  => 'www.lab.ejemplo',
            'pdf_pages_total' => '12',
            'pdf_pagination' => '1 de 3',
            'qr'           => '[QR]',
        ];
    }

    /**
     * @return list<array{id: string, enabled: bool, column: int}>
     */
    public static function defaultFooterFieldsStatic(): array
    {
        $out = [];
        foreach (self::FOOTER_FIELD_ORDER as $id) {
            $out[] = [
                'id'      => $id,
                'enabled' => true,
                'column'  => 1,
            ];
        }

        return $out;
    }

    /**
     * @return array<string, string>
     */
    public static function footerFieldLabels(): array
    {
        return [
            'footer_company'  => 'Nombre del laboratorio (empresa)',
            'footer_generated' => 'Línea “Resultados generados el …” (fecha y hora)',
            'footer_policy'   => 'Política de devolución / texto legal (si está configurada)',
        ];
    }

    /**
     * @return array<string, string>
     */
    public static function footerPreviewSamples(): array
    {
        return [
            'footer_company'  => 'Laboratorio Clínico Ejemplo',
            'footer_generated' => 'Resultados generados el 30/03/2026 14:30',
            'footer_policy'   => 'Política de devolución…',
        ];
    }

    public static function defaultColumnForFooterField(string $id): int
    {
        return 1;
    }

    /**
     * @return array{top: float, right: float, bottom: float, left: float}
     */
    public static function defaultMarginsMmStatic(): array
    {
        return self::DEFAULT_MARGINS_MM;
    }

    public static function defaultPdfFooterReserveMmStatic(): float
    {
        return self::DEFAULT_PDF_FOOTER_RESERVE_MM;
    }

    /**
     * Altura estimada del pie fijo (mm) según filas y tipografía de la plantilla activa.
     *
     * @param array<string, mixed> $layout
     */
    public static function estimatePdfFooterReserveMm(array $layout): float
    {
        $ps = is_array($layout['page_style'] ?? null) ? $layout['page_style'] : [];
        $ft = self::normalizeFooterGridStyle($ps['footer_grid'] ?? []);
        $fontPt     = max(6.0, (float) ($ft['font_size_pt'] ?? 8.0));
        $lineHeight = max(1.0, (float) ($ft['line_height'] ?? 1.35));
        $secLayouts = is_array($layout['section_layouts'] ?? null) ? $layout['section_layouts'] : [];
        $ftSec      = is_array($secLayouts['footer'] ?? null) ? $secLayouts['footer'] : [];
        $rowGapPx   = max(0, min(40, (int) ($ftSec['row_gap_px'] ?? 0)));

        $footerCols = max(1, (int) ($ftSec['columns'] ?? 3));
        $maxRow     = 0;
        $maxStack   = 0;
        $maxDepth   = 1;
        $enabled    = 0;
        /** @var array<string, int> $regionCounts */
        $regionCounts = [];
        foreach ($layout['instances'] ?? [] as $inst) {
            if (! is_array($inst) || ($inst['section'] ?? '') !== 'footer' || empty($inst['enabled'])) {
                continue;
            }
            $enabled++;
            $row = max(0, (int) ($inst['grid_row'] ?? 0));
            $col = max(0, min($footerCols - 1, (int) ($inst['column'] ?? 0)));
            $span = max(1, min($footerCols - $col, (int) ($inst['column_span'] ?? 1)));
            $regionKey = $row . ':' . $col . ':' . $span;
            $regionCounts[$regionKey] = ($regionCounts[$regionKey] ?? 0) + 1;
            $maxRow   = max($maxRow, $row);
            $maxStack = max($maxStack, max(0, (int) ($inst['grid_stack'] ?? 0)));
        }
        if ($enabled < 1) {
            return self::DEFAULT_PDF_FOOTER_RESERVE_MM;
        }

        foreach ($regionCounts as $count) {
            $maxDepth = max($maxDepth, max(1, (int) $count));
        }

        $rows   = max(1, $maxRow + 1, $maxStack + 1, $maxDepth);
        $lineMm = $fontPt * $lineHeight * 0.352778;
        $gapMm  = $rowGapPx * 0.264583;
        $padMm  = 6.0 * 0.264583 + 1.5;

        $estimate = $padMm + ($rows * $lineMm) + (max(0, $rows - 1) * $gapMm);

        if (self::isOrderSheetHeaderEnabledForLayout($layout)) {
            $estimate += self::ORDER_SHEET_HEADER_HEIGHT_MM
                + self::ORDER_SHEET_HEADER_GAP_ABOVE_FOOTER_MM
                + 1.5;
        }

        return max(self::DEFAULT_PDF_FOOTER_RESERVE_MM, min(40.0, round($estimate + 2.0, 1)));
    }

    /**
     * Altura estimada (mm) de bloques antes del primer grupo de resultados (página 1).
     * Equivalente a measureHeaderHeightPx() en impresión navegador.
     *
     * @param array<string, mixed> $layout
     */
    public static function estimatePdfHeaderBeforeResultsMm(array $layout): float
    {
        $totalMm = 0.0;
        foreach (is_array($layout['blocks'] ?? null) ? $layout['blocks'] : [] as $block) {
            $id = (string) ($block['id'] ?? '');
            if ($id === 'results') {
                break;
            }
            if (empty($block['enabled'])) {
                continue;
            }
            if (in_array($id, ['header', 'patient_doctor', 'notes'], true)) {
                $totalMm += self::estimatePdfSectionGridHeightMm($layout, $id);
            }
        }

        return round(max(0.0, $totalMm), 1);
    }

    /**
     * @param array<string, mixed> $layout
     */
    private static function estimatePdfSectionGridHeightMm(array $layout, string $section): float
    {
        $ps         = is_array($layout['page_style'] ?? null) ? $layout['page_style'] : [];
        $secLayouts = is_array($layout['section_layouts'] ?? null) ? $layout['section_layouts'] : [];
        $sec        = is_array($secLayouts[$section] ?? null) ? $secLayouts[$section] : [];
        $rowGapPx   = max(0, min(40, (int) ($sec['row_gap_px'] ?? 6)));

        $fontPt     = 9.0;
        $lineHeight = 1.35;
        if ($section === 'header') {
            $hg = self::normalizeHeaderGridStyle($ps['header_grid'] ?? []);
            $fontPt     = max(6.0, (float) ($hg['font_size_pt'] ?? 9.0));
            $lineHeight = max(1.0, (float) ($hg['line_height'] ?? 1.35));
        } elseif ($section === 'patient_doctor') {
            $pd = self::normalizePatientDoctorGridStyle($ps['patient_doctor_grid'] ?? []);
            $fontPt     = max(6.0, (float) ($pd['font_size_pt'] ?? 9.0));
            $lineHeight = max(1.0, (float) ($pd['line_height'] ?? 1.35));
        } elseif ($section === 'notes') {
            $ns = self::normalizeNotesStyle($ps['notes'] ?? []);
            $fontPt     = max(6.0, (float) ($ns['font_size_pt'] ?? 9.0));
            $lineHeight = max(1.0, (float) ($ns['line_height'] ?? 1.35));
        }

        $maxRow   = 0;
        $maxStack = 0;
        $enabled  = 0;
        foreach ($layout['instances'] ?? [] as $inst) {
            if (! is_array($inst) || ($inst['section'] ?? '') !== $section || empty($inst['enabled'])) {
                continue;
            }
            $enabled++;
            $maxRow   = max($maxRow, max(0, (int) ($inst['grid_row'] ?? 0)));
            $maxStack = max($maxStack, max(0, (int) ($inst['grid_stack'] ?? 0)));
        }
        if ($enabled < 1) {
            return 0.0;
        }

        $rows   = max(1, $maxRow + 1, $maxStack + 1);
        $lineMm = $fontPt * $lineHeight * 0.352778;
        $gapMm  = $rowGapPx * 0.264583;
        $padMm  = ($section === 'header' ? 8.0 : 4.0) * 0.264583;

        return $padMm + ($rows * $lineMm) + (max(0, $rows - 1) * $gapMm) + 2.0;
    }

    /**
     * @return array<string, array{columns: int, rows: int, line_height: float, column_align_h: list<string>, column_align_v: list<string>}>
     */
    public static function defaultSectionLayoutsStatic(): array
    {
        return [
            'header' => [
                'columns'        => 3,
                'rows'           => 3,
                'line_height'    => 1.35,
                'row_gap_px'     => 6,
                'column_align_h' => ['left', 'left', 'left'],
                'column_align_v' => ['top', 'top', 'top'],
            ],
            'patient_doctor' => [
                'columns'        => 2,
                'rows'           => 4,
                'line_height'    => 1.35,
                'row_gap_px'     => 2,
                'column_align_h' => ['left', 'left'],
                'column_align_v' => ['top', 'top'],
            ],
            'footer' => [
                'columns'        => 3,
                'rows'           => 2,
                'line_height'    => 1.35,
                'row_gap_px'     => 0,
                'column_align_h' => ['left', 'left', 'left'],
                'column_align_v' => ['top', 'top', 'top'],
            ],
            'lab_firmas' => [
                'columns'        => 3,
                'rows'           => 3,
                'line_height'    => 1.35,
                'row_gap_px'     => 6,
                'column_align_h' => ['left', 'left', 'left'],
                'column_align_v' => ['top', 'top', 'top'],
            ],
        ];
    }

    /**
     * Interlineado y alineaciones por columna acotados al número de columnas actual.
     *
     * @return array{line_height: float, column_align_h: list<string>, column_align_v: list<string>}
     */
    public static function resolveSectionLayoutStyle(array $sectionLayout, int $n): array
    {
        $n = max(self::SECTION_COLUMN_MIN, min(self::SECTION_COLUMN_MAX, $n));
        $lh = isset($sectionLayout['line_height']) ? (float) $sectionLayout['line_height'] : 1.35;
        $lh = round(max(1.0, min(2.5, $lh)), 2);
        $hRaw = $sectionLayout['column_align_h'] ?? [];
        $vRaw = $sectionLayout['column_align_v'] ?? [];

        return [
            'line_height'    => $lh,
            'column_align_h' => self::normalizeColumnAlignHArray(is_array($hRaw) ? $hRaw : [], $n),
            'column_align_v' => self::normalizeColumnAlignVArray(is_array($vRaw) ? $vRaw : [], $n),
        ];
    }

    /**
     * @param list<mixed> $raw
     *
     * @return list<string>
     */
    public static function normalizeColumnAlignHArray(array $raw, int $n): array
    {
        $allowed = ['left', 'center', 'right'];
        $out     = [];
        $raw     = array_values($raw);
        for ($i = 0; $i < $n; $i++) {
            $v = isset($raw[$i]) ? strtolower(trim((string) $raw[$i])) : '';
            $out[] = in_array($v, $allowed, true) ? $v : 'left';
        }

        return $out;
    }

    /**
     * @param array<string, mixed>|null $inst
     */
    public static function resolveInstanceAlignH(?array $inst, array $colAlignH, int $col): string
    {
        if (is_array($inst) && array_key_exists('align_h', $inst)) {
            $h = strtolower(trim((string) $inst['align_h']));
            if (in_array($h, self::ALLOWED_INSTANCE_ALIGN_H, true)) {
                return $h;
            }
        }

        return 'left';
    }

    /**
     * @param array<string, mixed>|null $inst
     */
    public static function resolveInstanceAlignV(?array $inst, array $colAlignV, int $col): string
    {
        if (is_array($inst) && array_key_exists('align_v', $inst)) {
            $v = strtolower(trim((string) $inst['align_v']));
            if (in_array($v, self::ALLOWED_INSTANCE_ALIGN_V, true)) {
                return $v;
            }
        }

        return 'top';
    }

    /**
     * @return string|null left|center|right o null si no está definido
     */
    public static function normalizeInstanceAlignH(mixed $raw): ?string
    {
        if ($raw === null || $raw === '') {
            return null;
        }
        $h = strtolower(trim((string) $raw));

        return in_array($h, self::ALLOWED_INSTANCE_ALIGN_H, true) ? $h : null;
    }

    /**
     * @return string|null top|middle|bottom o null si no está definido
     */
    public static function normalizeInstanceAlignV(mixed $raw): ?string
    {
        if ($raw === null || $raw === '') {
            return null;
        }
        $v = strtolower(trim((string) $raw));

        return in_array($v, self::ALLOWED_INSTANCE_ALIGN_V, true) ? $v : null;
    }

    /**
     * @param array<string, mixed> $item
     * @param list<string>         $colAlignH
     * @param list<string>         $colAlignV
     */
    public static function instanceAlignItemClasses(array $item, array $colAlignH, array $colAlignV, int $col): string
    {
        $h = self::resolveInstanceAlignH($item, $colAlignH, $col);
        $v = self::resolveInstanceAlignV($item, $colAlignV, $col);

        return 'pdf-el-item--h-' . $h . ' pdf-el-item--v-' . $v;
    }

    /**
     * @param array<string, mixed> $item
     * @param list<string>         $colAlignH
     * @param list<string>         $colAlignV
     */
    public static function instanceAlignInlineStyle(array $item, array $colAlignH, array $colAlignV, int $col, string $typography = ''): string
    {
        $h = self::resolveInstanceAlignH($item, $colAlignH, $col);
        $v = self::resolveInstanceAlignV($item, $colAlignV, $col);
        $style = 'text-align:' . $h . ' !important;';
        if ($v === 'bottom') {
            $style .= 'margin-top:auto !important;';
        } elseif ($v === 'middle') {
            $style .= 'margin-top:auto !important;margin-bottom:auto !important;';
        }

        return $style . $typography;
    }

    /**
     * @param list<mixed> $raw
     *
     * @return list<string>
     */
    public static function normalizeColumnAlignVArray(array $raw, int $n): array
    {
        $allowed = ['top', 'middle', 'bottom'];
        $out     = [];
        $raw     = array_values($raw);
        for ($i = 0; $i < $n; $i++) {
            $v = isset($raw[$i]) ? strtolower(trim((string) $raw[$i])) : '';
            $out[] = in_array($v, $allowed, true) ? $v : 'top';
        }

        return $out;
    }

    public static function generateInstanceUid(): string
    {
        return bin2hex(random_bytes(8));
    }

    /**
     * Etiquetas de todos los tipos de elemento (editor y vista previa).
     *
     * @return array<string, string>
     */
    public static function elementTypeLabels(): array
    {
        return array_merge(
            self::headerFieldLabels(),
            ['custom_text' => 'Texto libre (etiqueta y valor editables)'],
            self::patientDoctorFieldLabels(),
            self::footerFieldLabels(),
            self::labFirmasFieldLabels()
        );
    }

    /**
     * @return array<string, string>
     */
    public static function labFirmasFieldLabels(): array
    {
        return [
            'lab_firmas_title'                => 'Título del bloque (validación y aprobación)',
            'lab_firmas_validator'          => 'Verificado por (nombre)',
            'lab_firmas_seal'               => 'Sello (imagen)',
            'lab_firmas_approver_signature' => 'Firma del aprobador (imagen)',
            'lab_firmas_approver_name'      => 'Nombre del aprobador',
            'lab_firmas_approver_cargo'     => 'Cargo del aprobador',
            'lab_firmas_matricula'          => 'Matrícula del aprobador',
        ];
    }

    /**
     * @return list<array{uid: string, element_type: string, section: string, enabled: bool, column: int}>
     */
    public static function defaultInstancesStatic(): array
    {
        $layouts = self::defaultSectionLayoutsStatic();
        $hc      = max(self::SECTION_COLUMN_MIN, min(self::SECTION_COLUMN_MAX, (int) $layouts['header']['columns']));
        $pc      = max(self::SECTION_COLUMN_MIN, min(self::SECTION_COLUMN_MAX, (int) $layouts['patient_doctor']['columns']));
        $fc      = max(self::SECTION_COLUMN_MIN, min(self::SECTION_COLUMN_MAX, (int) $layouts['footer']['columns']));
        $lc      = max(self::SECTION_COLUMN_MIN, min(self::SECTION_COLUMN_MAX, (int) ($layouts['lab_firmas']['columns'] ?? 3)));
        $out     = [];
        foreach (self::defaultHeaderFieldsStatic() as $f) {
            $col = (int) ($f['column'] ?? 0);
            $out[] = [
                'uid'           => self::generateInstanceUid(),
                'element_type'  => (string) ($f['id'] ?? ''),
                'section'       => 'header',
                'enabled'       => ! empty($f['enabled']),
                'column'        => ! empty($f['enabled']) ? max(0, min($hc - 1, $col)) : max(0, min($hc - 1, $col)),
                'column_span'   => 1,
                'text_style'    => self::DEFAULT_TEXT_STYLE,
            ];
        }
        foreach (self::defaultPatientDoctorFieldsStatic() as $f) {
            $col = (int) ($f['column'] ?? 0);
            $out[] = [
                'uid'           => self::generateInstanceUid(),
                'element_type'  => (string) ($f['id'] ?? ''),
                'section'       => 'patient_doctor',
                'enabled'       => ! empty($f['enabled']),
                'column'        => ! empty($f['enabled']) ? max(0, min($pc - 1, $col)) : max(0, min($pc - 1, $col)),
                'column_span'   => 1,
                'text_style'    => self::DEFAULT_TEXT_STYLE,
            ];
        }
        foreach (self::defaultFooterFieldsStatic() as $f) {
            $col = (int) ($f['column'] ?? 0);
            $out[] = [
                'uid'           => self::generateInstanceUid(),
                'element_type'  => (string) ($f['id'] ?? ''),
                'section'       => 'footer',
                'enabled'       => ! empty($f['enabled']),
                'column'        => ! empty($f['enabled']) ? max(0, min($fc - 1, $col)) : max(0, min($fc - 1, $col)),
                'column_span'   => 1,
                'text_style'    => self::DEFAULT_TEXT_STYLE,
            ];
        }
        foreach (self::defaultLabFirmasInstancesForColumns($lc) as $inst) {
            $out[] = $inst;
        }

        return $out;
    }

    /**
     * Instancias por defecto del bloque firmas para un número de columnas dado.
     *
     * @return list<array{uid: string, element_type: string, section: string, enabled: bool, column: int, column_span: int, text_style: array<string, mixed>}>
     */
    public static function defaultLabFirmasInstancesForColumns(int $lc): array
    {
        $lc = max(self::SECTION_COLUMN_MIN, min(self::SECTION_COLUMN_MAX, $lc));
        $out = [
            [
                'uid'           => self::generateInstanceUid(),
                'element_type'  => 'lab_firmas_title',
                'section'       => 'lab_firmas',
                'enabled'       => true,
                'column'        => 0,
                'column_span'   => $lc,
                'text_style'    => self::DEFAULT_TEXT_STYLE,
            ],
        ];
        $types = [
            'lab_firmas_validator',
            'lab_firmas_seal',
            'lab_firmas_approver_signature',
            'lab_firmas_approver_name',
            'lab_firmas_approver_cargo',
            'lab_firmas_matricula',
        ];
        $colsByLc = [
            6 => [0, 1, 2, 3, 4, 5],
            5 => [0, 1, 2, 3, 4, 4],
            4 => [0, 1, 2, 3, 3, 3],
            3 => [0, 1, 2, 2, 2, 2],
            2 => [0, 1, 1, 1, 1, 1],
            1 => [0, 0, 0, 0, 0, 0],
        ];
        $lk = max(1, min(6, $lc));
        $cols = $colsByLc[$lk];
        foreach ($types as $idx => $tid) {
            $out[] = [
                'uid'           => self::generateInstanceUid(),
                'element_type'  => $tid,
                'section'       => 'lab_firmas',
                'enabled'       => true,
                'column'        => $cols[$idx],
                'column_span'   => 1,
                'text_style'    => self::DEFAULT_TEXT_STYLE,
            ];
        }

        return $out;
    }

    public static function columnAlign(int $index, int $total): string
    {
        if ($total <= 1) {
            return 'center';
        }
        if ($index === 0) {
            return 'left';
        }
        if ($index === $total - 1) {
            return 'right';
        }

        return 'center';
    }

    /**
     * Alineación del texto cuando un elemento ocupa varias columnas (índice 0-based).
     */
    public static function columnAlignForSpan(int $startColumn, int $span, int $totalColumns): string
    {
        if ($totalColumns <= 1) {
            return 'center';
        }
        $end = $startColumn + $span - 1;
        if ($startColumn === 0 && $end >= $totalColumns - 1) {
            return 'center';
        }
        if ($startColumn === 0) {
            return 'left';
        }
        if ($end >= $totalColumns - 1) {
            return 'right';
        }

        return 'center';
    }

    /**
     * Elementos de una sección en orden de lista, con columna inicial y anchura en columnas (para CSS grid).
     *
     * @return array{0: int, 1: list<array{element_type: string, column: int, column_span: int}>}
     */
    public static function gridItemsForSection(array $layout, string $section): array
    {
        $allowed = ['header', 'patient_doctor', 'footer', 'lab_firmas'];
        if (! in_array($section, $allowed, true)) {
            return [1, []];
        }
        $layouts = is_array($layout['section_layouts'] ?? null) ? $layout['section_layouts'] : [];
        $n       = (int) ($layouts[$section]['columns'] ?? 3);
        $n       = max(self::SECTION_COLUMN_MIN, min(self::SECTION_COLUMN_MAX, $n));
        $items   = [];
        foreach ($layout['instances'] ?? [] as $inst) {
            if (! is_array($inst)) {
                continue;
            }
            if (($inst['section'] ?? '') !== $section || empty($inst['enabled'])) {
                continue;
            }
            $type = (string) ($inst['element_type'] ?? '');
            if ($type === '') {
                continue;
            }
            $col = (int) ($inst['column'] ?? 0);
            $col = max(0, min($n - 1, $col));
            $span = isset($inst['column_span']) ? (int) $inst['column_span'] : 1;
            $maxSpan = max(1, $n - $col);
            $span = max(1, min($maxSpan, $span));
            $item = [
                'uid'          => (string) ($inst['uid'] ?? ''),
                'element_type' => $type,
                'column'       => $col,
                'column_span'  => $span,
                'text_style'   => self::normalizeTextStyle($inst['text_style'] ?? []),
            ];
            if (array_key_exists('grid_row', $inst) && $inst['grid_row'] !== null) {
                $item['grid_row'] = max(0, (int) $inst['grid_row']);
            }
            if (array_key_exists('grid_stack', $inst) && $inst['grid_stack'] !== null) {
                $item['grid_stack'] = max(0, (int) $inst['grid_stack']);
            }
            // Override de espaciado por instancia (cuando el usuario lo configura en el editor).
            foreach (['label_value_gap_px', 'label_space_above_px', 'label_space_below_px'] as $k) {
                if (array_key_exists($k, $inst) && $inst[$k] !== null) {
                    $v = (int) $inst[$k];
                    $v = max(0, min(40, $v));
                    $item[$k] = $v;
                }
            }
            if ($type === 'custom_text') {
                $item['custom_text'] = self::normalizeCustomTextPayload($inst['custom_text'] ?? []);
            }
            $alignH = self::normalizeInstanceAlignH($inst['align_h'] ?? null);
            $alignV = self::normalizeInstanceAlignV($inst['align_v'] ?? null);
            if ($alignH !== null) {
                $item['align_h'] = $alignH;
            }
            if ($alignV !== null) {
                $item['align_v'] = $alignV;
            }
            $items[] = $item;
        }

        return [$n, $items];
    }

    /**
     * @return array<string, array{columns: int, rows: int, line_height: float, column_align_h: list<string>, column_align_v: list<string>}>
     */
    protected function normalizeSectionLayouts(?array $decoded): array
    {
        $defaults = self::defaultSectionLayoutsStatic();
        $raw      = is_array($decoded) && isset($decoded['section_layouts']) && is_array($decoded['section_layouts'])
            ? $decoded['section_layouts']
            : [];
        $out = [];
        foreach ($defaults as $key => $def) {
            $rawSec = is_array($raw[$key] ?? null) ? $raw[$key] : [];
            $n      = isset($rawSec['columns']) ? (int) $rawSec['columns'] : (int) ($def['columns'] ?? 3);
            $n      = max(self::SECTION_COLUMN_MIN, min(self::SECTION_COLUMN_MAX, $n));
            $r      = isset($rawSec['rows']) ? (int) $rawSec['rows'] : (int) ($def['rows'] ?? 3);
            $r      = max(1, min(50, $r));

            $defLh = isset($def['line_height']) ? (float) $def['line_height'] : 1.35;
            $lh    = isset($rawSec['line_height']) ? (float) $rawSec['line_height'] : $defLh;
            $lh    = round(max(1.0, min(2.5, $lh)), 2);

            $hRaw = $rawSec['column_align_h'] ?? null;
            $vRaw = $rawSec['column_align_v'] ?? null;
            $entry = [
                'columns'        => $n,
                'rows'           => $r,
                'line_height'    => $lh,
                'column_align_h' => self::normalizeColumnAlignHArray(is_array($hRaw) ? $hRaw : [], $n),
                'column_align_v' => self::normalizeColumnAlignVArray(is_array($vRaw) ? $vRaw : [], $n),
            ];
            if ($key === 'patient_doctor') {
                $defRg = (int) ($def['row_gap_px'] ?? 2);
                $rg    = isset($rawSec['row_gap_px']) ? (int) $rawSec['row_gap_px'] : $defRg;
                $entry['row_gap_px'] = max(0, min(40, $rg));
            } else {
                $defRg = (int) ($def['row_gap_px'] ?? 6);
                $rg    = isset($rawSec['row_gap_px']) ? (int) $rawSec['row_gap_px'] : $defRg;
                $entry['row_gap_px'] = max(0, min(40, $rg));
            }
            $out[$key] = $entry;
        }

        return $out;
    }

    /**
     * @param list<mixed> $raw
     *
     * @return list<array{uid: string, element_type: string, section: string, enabled: bool, column: int}>
     */
    protected function normalizeInstances(array $raw, array $sectionLayouts, int $sourceLayoutVersion = 7): array
    {
        $allowed = self::ELEMENT_TYPES;
        $out     = [];
        foreach ($raw as $row) {
            if (! is_array($row)) {
                continue;
            }
            $type = (string) ($row['element_type'] ?? $row['type'] ?? $row['id'] ?? '');
            if ($type === '') {
                continue;
            }
            $section = (string) ($row['section'] ?? '');
            if (! in_array($section, ['header', 'patient_doctor', 'footer', 'lab_firmas'], true)) {
                continue;
            }
            $typesToEmit = [];
            if ($section === 'lab_firmas') {
                if ($type === 'lab_firmas_approver') {
                    $typesToEmit = [
                        'lab_firmas_approver_signature',
                        'lab_firmas_approver_name',
                        'lab_firmas_approver_cargo',
                    ];
                } elseif ($type === 'custom_text') {
                    $typesToEmit = ['custom_text'];
                } elseif (in_array($type, self::LAB_FIRMAS_ELEMENT_TYPES, true)) {
                    $typesToEmit = [$type];
                } else {
                    continue;
                }
            } else {
                if (in_array($type, self::LAB_FIRMAS_ELEMENT_TYPES, true)) {
                    continue;
                }
                if ($type === 'fecha_ingreso') {
                    $typesToEmit = ['fecha_recepcion', 'fecha_reporte'];
                } elseif ($type === 'paciente_nombre' && $sourceLayoutVersion < 6) {
                    $typesToEmit = ['paciente_nombre', 'paciente_genero'];
                } elseif (in_array($type, $allowed, true)) {
                    $typesToEmit = [$type];
                } else {
                    continue;
                }
            }
            $cols = (int) ($sectionLayouts[$section]['columns'] ?? 1);
            $cols = max(self::SECTION_COLUMN_MIN, min(self::SECTION_COLUMN_MAX, $cols));
            $enabled = array_key_exists('enabled', $row) ? ! empty($row['enabled']) : true;
            $colRaw  = isset($row['column']) ? (int) $row['column'] : 0;
            if ($colRaw < 0) {
                $enabled = false;
            }
            $col = $enabled ? max(0, min($cols - 1, $colRaw)) : max(0, min($cols - 1, max(0, $colRaw)));

            $spanRaw = isset($row['column_span']) ? (int) $row['column_span'] : 1;
            $maxSpan = max(1, $cols - $col);
            $span    = max(1, min($maxSpan, $spanRaw >= 1 ? $spanRaw : 1));

            $hasGridRow   = array_key_exists('grid_row', $row);
            $hasGridStack = array_key_exists('grid_stack', $row);
            $gridRow      = $hasGridRow ? max(0, (int) ($row['grid_row'] ?? 0)) : null;
            $gridStack    = $hasGridStack ? max(0, (int) ($row['grid_stack'] ?? 0)) : null;

            // Espacios opcionales por instancia (solo sección paciente/médico).
            $gapRaw = array_key_exists('label_value_gap_px', $row) ? (int) $row['label_value_gap_px'] : null;
            $mtRaw  = array_key_exists('label_space_above_px', $row) ? (int) $row['label_space_above_px'] : null;
            $mbRaw  = array_key_exists('label_space_below_px', $row) ? (int) $row['label_space_below_px'] : null;
            $gapVal = $gapRaw === null ? null : max(0, min(40, $gapRaw));
            $mtVal  = $mtRaw === null ? null : max(0, min(40, $mtRaw));
            $mbVal  = $mbRaw === null ? null : max(0, min(40, $mbRaw));
            $customTextPayload = ($type === 'custom_text')
                ? self::normalizeCustomTextPayload($row['custom_text'] ?? [])
                : null;
            $alignH = self::normalizeInstanceAlignH($row['align_h'] ?? null);
            $alignV = self::normalizeInstanceAlignV($row['align_v'] ?? null);

            foreach ($typesToEmit as $emitType) {
                $uid = (string) ($row['uid'] ?? '');
                if ($uid === '' || count($typesToEmit) > 1) {
                    $uid = self::generateInstanceUid();
                }
                $textStyle = ($emitType === 'custom_text')
                    ? self::DEFAULT_TEXT_STYLE
                    : self::normalizeTextStyle($row['text_style'] ?? []);
                $entry = [
                    'uid'           => $uid,
                    'element_type'  => $emitType,
                    'section'       => $section,
                    'enabled'       => $enabled,
                    'column'        => $col,
                    'column_span'   => $span,
                    'text_style'    => $textStyle,
                ];
                if ($hasGridRow && $gridRow !== null) {
                    $entry['grid_row'] = $gridRow;
                }
                if ($hasGridStack && $gridStack !== null) {
                    $entry['grid_stack'] = $gridStack;
                }
                if ($section === 'patient_doctor' && in_array($emitType, self::PATIENT_DOCTOR_FIELD_ORDER, true)) {
                    if ($gapVal !== null) $entry['label_value_gap_px'] = $gapVal;
                    if ($mtVal !== null) $entry['label_space_above_px'] = $mtVal;
                    if ($mbVal !== null) $entry['label_space_below_px'] = $mbVal;
                }
                if ($emitType === 'custom_text') {
                    $entry['custom_text'] = $customTextPayload ?? self::normalizeCustomTextPayload([]);
                }
                if ($alignH !== null) {
                    $entry['align_h'] = $alignH;
                }
                if ($alignV !== null) {
                    $entry['align_v'] = $alignV;
                }
                $out[] = $entry;
            }
        }

        return $out;
    }

    /**
     * Añade instancias por defecto de firmas si el JSON antiguo no las trae.
     *
     * @param list<array<string, mixed>> $instances
     *
     * @return list<array<string, mixed>>
     */
    protected function ensureLabFirmasInstances(array $instances, array $sectionLayouts): array
    {
        $hasLabFirmas = false;
        $presentTypes = [];
        foreach ($instances as $inst) {
            if (!is_array($inst) || ($inst['section'] ?? '') !== 'lab_firmas') {
                continue;
            }
            $hasLabFirmas = true;
            $t = (string) ($inst['element_type'] ?? '');
            if ($t !== '') {
                $presentTypes[$t] = true;
            }
        }
        $lc = (int) ($sectionLayouts['lab_firmas']['columns'] ?? 3);
        $lc = max(self::SECTION_COLUMN_MIN, min(self::SECTION_COLUMN_MAX, $lc));

        // Si no había sección de firmas, crea el bloque completo por defecto.
        if (!$hasLabFirmas) {
            foreach (self::defaultLabFirmasInstancesForColumns($lc) as $row) {
                $instances[] = $row;
            }
            return $instances;
        }

        // Si la sección existe, solo completar tipos de migración (no restaurar título/validador/sello quitados a mano).
        foreach (self::defaultLabFirmasInstancesForColumns($lc) as $row) {
            $t = (string) ($row['element_type'] ?? '');
            if ($t === '' || isset($presentTypes[$t]) || ! in_array($t, self::LAB_FIRMAS_MERGE_MISSING_TYPES, true)) {
                continue;
            }
            $instances[] = $row;
        }

        return $instances;
    }

    /**
     * @return array{section_layouts: array{header: array{columns: int}, patient_doctor: array{columns: int}, footer: array{columns: int}}, instances: list<array{uid: string, element_type: string, section: string, enabled: bool, column: int, column_span: int}>}
     */
    protected function migrateV4ToV5(array $decoded): array
    {
        $sectionLayouts = $this->normalizeSectionLayouts($decoded);
        $instances      = [];
        $hc             = (int) $sectionLayouts['header']['columns'];
        $pc             = (int) $sectionLayouts['patient_doctor']['columns'];
        $fc             = (int) $sectionLayouts['footer']['columns'];

        foreach ($this->normalizeHeaderFields($decoded) as $f) {
            $id      = (string) ($f['id'] ?? '');
            $enabled = ! empty($f['enabled']);
            $c       = (int) ($f['column'] ?? 0);
            $instances[] = [
                'uid'           => self::generateInstanceUid(),
                'element_type'  => $id,
                'section'       => 'header',
                'enabled'       => $enabled,
                'column'        => $enabled ? max(0, min($hc - 1, $c)) : max(0, min($hc - 1, $c)),
                'column_span'   => 1,
                'text_style'    => self::DEFAULT_TEXT_STYLE,
            ];
        }
        foreach ($this->normalizePatientDoctorFields($decoded) as $f) {
            $id      = (string) ($f['id'] ?? '');
            $enabled = ! empty($f['enabled']);
            $c       = (int) ($f['column'] ?? 0);
            $instances[] = [
                'uid'           => self::generateInstanceUid(),
                'element_type'  => $id,
                'section'       => 'patient_doctor',
                'enabled'       => $enabled,
                'column'        => $enabled ? max(0, min($pc - 1, $c)) : max(0, min($pc - 1, $c)),
                'column_span'   => 1,
                'text_style'    => self::DEFAULT_TEXT_STYLE,
            ];
        }
        foreach ($this->normalizeFooterFields($decoded) as $f) {
            $id      = (string) ($f['id'] ?? '');
            $enabled = ! empty($f['enabled']);
            $c       = (int) ($f['column'] ?? 0);
            $instances[] = [
                'uid'           => self::generateInstanceUid(),
                'element_type'  => $id,
                'section'       => 'footer',
                'enabled'       => $enabled,
                'column'        => $enabled ? max(0, min($fc - 1, $c)) : max(0, min($fc - 1, $c)),
                'column_span'   => 1,
                'text_style'    => self::DEFAULT_TEXT_STYLE,
            ];
        }

        return [
            'section_layouts' => $sectionLayouts,
            'instances'       => $instances,
        ];
    }

    /**
     * @return array{top: float, right: float, bottom: float, left: float}
     */
    protected function normalizeMarginsMm(?array $decoded): array
    {
        $defaults = self::DEFAULT_MARGINS_MM;
        $raw      = is_array($decoded) && isset($decoded['margins_mm']) && is_array($decoded['margins_mm'])
            ? $decoded['margins_mm']
            : [];
        $out = [];
        foreach (['top', 'right', 'bottom', 'left'] as $k) {
            $v = isset($raw[$k]) ? (float) $raw[$k] : (float) $defaults[$k];
            $out[$k] = round(max(0.0, min(50.0, $v)), 2);
        }

        return $out;
    }

    /**
     * @return list<array{id: string, enabled: bool, column: int}>
     */
    protected function normalizeFooterFields(?array $decoded): array
    {
        $allowed = self::FOOTER_FIELD_ORDER;
        $seen    = [];
        $fields  = [];
        $raw     = is_array($decoded) && isset($decoded['footer_fields']) && is_array($decoded['footer_fields'])
            ? $decoded['footer_fields']
            : [];
        foreach ($raw as $f) {
            if (! is_array($f)) {
                continue;
            }
            $id = (string) ($f['id'] ?? '');
            if ($id === '' || ! in_array($id, $allowed, true) || in_array($id, $seen, true)) {
                continue;
            }
            $seen[] = $id;
            $enabled = array_key_exists('enabled', $f) ? ! empty($f['enabled']) : true;
            $colRaw  = isset($f['column']) ? (int) $f['column'] : self::defaultColumnForFooterField($id);
            if ($colRaw < 0) {
                $enabled = false;
            }
            $col = $enabled ? max(0, min(2, $colRaw)) : self::defaultColumnForFooterField($id);
            $fields[] = [
                'id'      => $id,
                'enabled' => $enabled,
                'column'  => $col,
            ];
        }
        foreach ($allowed as $aid) {
            if (! in_array($aid, $seen, true)) {
                $fields[] = [
                    'id'      => $aid,
                    'enabled' => true,
                    'column'  => self::defaultColumnForFooterField($aid),
                ];
            }
        }

        return $fields;
    }

    /**
     * @param mixed $raw
     *
     * @return array{font_family: string, font_size_pt: float, font_weight: string, font_color: string, font_style: string, text_transform: string, letter_spacing_em: float, line_height: float, text_shadow: string}
     */
    public static function normalizeTextStyle($raw): array
    {
        $def = self::DEFAULT_TEXT_STYLE;
        $ts  = is_array($raw) ? $raw : [];
        $family = (string) ($ts['font_family'] ?? $def['font_family']);
        if (! in_array($family, self::ALLOWED_PDF_FONT_FAMILIES, true)) {
            $family = $def['font_family'];
        }
        $size = isset($ts['font_size_pt']) ? (float) $ts['font_size_pt'] : $def['font_size_pt'];
        $size = round(max(6.0, min(24.0, $size)), 2);
        $weight = strtolower(trim((string) ($ts['font_weight'] ?? $def['font_weight'])));
        if (! in_array($weight, self::ALLOWED_PDF_FONT_WEIGHTS, true)) {
            $weight = $def['font_weight'];
        }
        $color = strtoupper(trim((string) ($ts['font_color'] ?? $def['font_color'])));
        if (! preg_match('/^#[0-9A-F]{6}$/', $color)) {
            $color = $def['font_color'];
        }
        $style = strtolower(trim((string) ($ts['font_style'] ?? $def['font_style'])));
        if (! in_array($style, self::ALLOWED_PDF_FONT_STYLES, true)) {
            $style = $def['font_style'];
        }
        $transform = strtolower(trim((string) ($ts['text_transform'] ?? $def['text_transform'])));
        if (! in_array($transform, self::ALLOWED_PDF_TEXT_TRANSFORMS, true)) {
            $transform = $def['text_transform'];
        }
        $ls = isset($ts['letter_spacing_em']) ? (float) $ts['letter_spacing_em'] : $def['letter_spacing_em'];
        $ls = round(max(-0.2, min(1.0, $ls)), 2);
        $lh = isset($ts['line_height']) ? (float) $ts['line_height'] : $def['line_height'];
        $lh = round(max(1.0, min(3.0, $lh)), 2);
        $shadow = strtolower(trim((string) ($ts['text_shadow'] ?? $def['text_shadow'])));
        if (! in_array($shadow, self::ALLOWED_PDF_TEXT_SHADOWS, true)) {
            $shadow = $def['text_shadow'];
        }

        return [
            'font_family'       => $family,
            'font_size_pt'      => $size,
            'font_weight'       => $weight,
            'font_color'        => $color,
            'font_style'        => $style,
            'text_transform'    => $transform,
            'letter_spacing_em' => $ls,
            'line_height'       => $lh,
            'text_shadow'       => $shadow,
        ];
    }

    /**
     * CSS inline a partir de un estilo de texto ya normalizado (misma lógica que la cuadrícula PDF).
     *
     * @param array<string, mixed> $ts {@see normalizeTextStyle()}
     */
    public static function textStyleNormalizedToInlineCss(array $ts): string
    {
        $shadowMap = [
            'none'   => 'none',
            'soft'   => '0.4px 0.4px 1px rgba(0,0,0,0.28)',
            'medium' => '0.7px 0.7px 1.4px rgba(0,0,0,0.35)',
            'strong' => '1px 1px 2px rgba(0,0,0,0.45)',
        ];
        $sh = $shadowMap[$ts['text_shadow']] ?? 'none';

        return 'font-family:' . $ts['font_family'] . ';'
            . 'font-size:' . $ts['font_size_pt'] . 'pt;'
            . 'font-weight:' . $ts['font_weight'] . ';'
            . 'color:' . $ts['font_color'] . ';'
            . 'font-style:' . $ts['font_style'] . ';'
            . 'text-transform:' . $ts['text_transform'] . ';'
            . 'letter-spacing:' . $ts['letter_spacing_em'] . 'em;'
            . 'line-height:' . $ts['line_height'] . ';'
            . 'text-shadow:' . $sh . ';';
    }

    /**
     * @param array<string, mixed>|null $raw
     */
    public static function textStyleArrayToInlineCss(?array $raw): string
    {
        return self::textStyleNormalizedToInlineCss(self::normalizeTextStyle($raw ?? []));
    }

    /**
     * Texto libre por instancia: etiqueta + valor con estilos propios (no usa text_style global del elemento).
     *
     * @param mixed $raw
     *
     * @return array{label: string, value: string, show_label: bool, line_mode: string, label_style: array<string, mixed>, value_style: array<string, mixed>}
     */
    public static function normalizeCustomTextPayload($raw): array
    {
        $s = is_array($raw) ? $raw : [];
        $lab = trim((string) ($s['label'] ?? ''));
        if ($lab !== '') {
            $lab = function_exists('mb_substr')
                ? mb_substr($lab, 0, self::CUSTOM_TEXT_LABEL_MAX_LEN, 'UTF-8')
                : substr($lab, 0, self::CUSTOM_TEXT_LABEL_MAX_LEN);
        }
        $val = trim((string) ($s['value'] ?? ''));
        if ($val !== '') {
            $val = function_exists('mb_substr')
                ? mb_substr($val, 0, self::CUSTOM_TEXT_VALUE_MAX_LEN, 'UTF-8')
                : substr($val, 0, self::CUSTOM_TEXT_VALUE_MAX_LEN);
        }
        $show = array_key_exists('show_label', $s) ? self::labFirmasBool($s, 'show_label', true) : true;
        $inline = isset($s['line_mode']) && trim((string) $s['line_mode']) === 'inline';

        return [
            'label'        => $lab,
            'value'        => $val,
            'show_label'   => $show,
            'line_mode'    => $inline ? 'inline' : 'stacked',
            'label_style'  => self::normalizeTextStyle($s['label_style'] ?? []),
            'value_style'  => self::normalizeTextStyle($s['value_style'] ?? []),
        ];
    }

    /**
     * @param mixed $raw
     */
    public static function validateRawCustomTextPayload($raw): ?string
    {
        if (! is_array($raw)) {
            return 'El bloque «texto libre» (custom_text) debe ser un objeto JSON.';
        }
        foreach (['label_style', 'value_style'] as $sk) {
            $sub = $raw[$sk] ?? [];
            if (! is_array($sub)) {
                return 'Estilo inválido en texto libre (' . $sk . ').';
            }
            if ($sub === []) {
                continue;
            }
            $err = self::validateRawTextStyleArray($sub);
            if ($err !== null) {
                return $err;
            }
        }
        foreach (['label', 'value'] as $tk) {
            if (! array_key_exists($tk, $raw)) {
                continue;
            }
            $v = $raw[$tk];
            if (is_array($v) || is_object($v)) {
                return 'Texto libre: ' . $tk . ' debe ser cadena.';
            }
            $t = (string) $v;
            $len = function_exists('mb_strlen') ? mb_strlen($t, 'UTF-8') : strlen($t);
            $max = $tk === 'label' ? self::CUSTOM_TEXT_LABEL_MAX_LEN : self::CUSTOM_TEXT_VALUE_MAX_LEN;
            if ($len > $max) {
                return 'Texto libre: ' . $tk . ' supera ' . (string) $max . ' caracteres.';
            }
        }

        return null;
    }

    /**
     * Listas permitidas para validación en cliente y servidor.
     *
     * @return array{font_families: list<string>, font_weights: list<string>, font_styles: list<string>, text_transforms: list<string>, text_shadows: list<string>, segment_shadows: list<string>, text_aligns: list<string>, vertical_aligns: list<string>}
     */
    public static function styleAllowlistsForClient(): array
    {
        return [
            'font_families'   => self::ALLOWED_PDF_FONT_FAMILIES,
            'font_weights'    => self::ALLOWED_PDF_FONT_WEIGHTS,
            'font_styles'     => self::ALLOWED_PDF_FONT_STYLES,
            'text_transforms' => self::ALLOWED_PDF_TEXT_TRANSFORMS,
            'text_shadows'    => self::ALLOWED_PDF_TEXT_SHADOWS,
            'segment_shadows' => self::ALLOWED_PDF_TEXT_SHADOWS,
            'text_aligns'     => self::ALLOWED_PDF_TEXT_ALIGNS,
            'vertical_aligns' => self::ALLOWED_PDF_VERTICAL_ALIGNS,
            'grupo_cabecera_title_modes' => self::ALLOWED_GRUPO_CABECERA_TITLE_MODES,
        ];
    }

    public static function isValidPdfHexColor(string $v): bool
    {
        return (bool) preg_match('/^#[0-9A-Fa-f]{6}$/', trim($v));
    }

    /**
     * @param mixed $raw
     */
    public static function validateRawTextStyleArray($raw): ?string
    {
        if (! is_array($raw)) {
            return 'Los estilos de un elemento del diseño deben ser un objeto JSON.';
        }
        if (isset($raw['font_family']) && ! in_array((string) $raw['font_family'], self::ALLOWED_PDF_FONT_FAMILIES, true)) {
            return 'Familia de fuente no permitida en un elemento del diseño PDF.';
        }
        if (array_key_exists('font_size_pt', $raw)) {
            if (! is_numeric($raw['font_size_pt'])) {
                return 'El tamaño de fuente de un elemento debe ser numérico.';
            }
            $s = (float) $raw['font_size_pt'];
            if ($s < 6.0 || $s > 24.0) {
                return 'El tamaño de fuente de un elemento debe estar entre 6 y 24 pt.';
            }
        }
        if (isset($raw['font_weight']) && ! in_array(strtolower(trim((string) $raw['font_weight'])), self::ALLOWED_PDF_FONT_WEIGHTS, true)) {
            return 'Grosor de fuente no permitido en un elemento del diseño PDF.';
        }
        if (isset($raw['font_color']) && ! self::isValidPdfHexColor((string) $raw['font_color'])) {
            return 'Color de texto inválido en un elemento del diseño (use formato #RRGGBB).';
        }
        if (isset($raw['font_style']) && ! in_array(strtolower(trim((string) $raw['font_style'])), self::ALLOWED_PDF_FONT_STYLES, true)) {
            return 'Estilo de fuente no permitido en un elemento del diseño PDF.';
        }
        if (isset($raw['text_transform']) && ! in_array(strtolower(trim((string) $raw['text_transform'])), self::ALLOWED_PDF_TEXT_TRANSFORMS, true)) {
            return 'Transformación de texto no permitida en un elemento del diseño PDF.';
        }
        if (array_key_exists('letter_spacing_em', $raw)) {
            if (! is_numeric($raw['letter_spacing_em'])) {
                return 'El espaciado entre letras debe ser numérico.';
            }
            $ls = (float) $raw['letter_spacing_em'];
            if ($ls < -0.2 || $ls > 1.0) {
                return 'El espaciado entre letras debe estar entre -0,2 y 1 em.';
            }
        }
        if (array_key_exists('line_height', $raw)) {
            if (! is_numeric($raw['line_height'])) {
                return 'El interlineado debe ser numérico.';
            }
            $lh = (float) $raw['line_height'];
            if ($lh < 1.0 || $lh > 3.0) {
                return 'El interlineado de un elemento debe estar entre 1 y 3.';
            }
        }
        if (isset($raw['text_shadow']) && ! in_array(strtolower(trim((string) $raw['text_shadow'])), self::ALLOWED_PDF_TEXT_SHADOWS, true)) {
            return 'Tipo de sombra de texto no permitido en un elemento del diseño PDF.';
        }

        return null;
    }

    /**
     * @param mixed $raw
     */
    protected static function validateRawCardHeaderStyleBlock($raw): ?string
    {
        if (! is_array($raw)) {
            return 'La sección de estilo del encabezado tipo tarjeta (card header) es inválida.';
        }
        foreach (['bg_color', 'text_color'] as $k) {
            if (isset($raw[$k]) && ! self::isValidPdfHexColor((string) $raw[$k])) {
                return 'Color inválido en el estilo global de encabezados de sección (#RRGGBB).';
            }
        }
        if (isset($raw['font_family']) && ! in_array((string) $raw['font_family'], self::ALLOWED_PDF_FONT_FAMILIES, true)) {
            return 'Familia de fuente no permitida en el estilo de encabezados de sección.';
        }
        if (array_key_exists('font_size_pt', $raw)) {
            if (! is_numeric($raw['font_size_pt'])) {
                return 'El tamaño de fuente del encabezado de sección debe ser numérico.';
            }
            $s = (float) $raw['font_size_pt'];
            if ($s < 7.0 || $s > 20.0) {
                return 'El tamaño de fuente del encabezado de sección debe estar entre 7 y 20 pt.';
            }
        }
        if (isset($raw['font_weight']) && ! in_array(strtolower(trim((string) $raw['font_weight'])), self::ALLOWED_PDF_FONT_WEIGHTS, true)) {
            return 'Grosor de fuente no permitido en el encabezado de sección.';
        }
        if (isset($raw['font_style']) && ! in_array(strtolower(trim((string) $raw['font_style'])), self::ALLOWED_PDF_FONT_STYLES, true)) {
            return 'Estilo de fuente no permitido en el encabezado de sección.';
        }
        if (isset($raw['text_transform']) && ! in_array(strtolower(trim((string) $raw['text_transform'])), self::ALLOWED_PDF_TEXT_TRANSFORMS, true)) {
            return 'Transformación de texto no permitida en el encabezado de sección.';
        }

        return null;
    }

    /**
     * @param mixed $raw
     */
    protected static function validateRawNotesLikeStyleBlock($raw, string $contextLabel): ?string
    {
        if (! is_array($raw)) {
            return 'Estilo inválido en: ' . $contextLabel . '.';
        }
        foreach (['title_bg_color', 'title_text_color', 'body_bg_color', 'body_text_color'] as $k) {
            if (isset($raw[$k]) && ! self::isValidPdfHexColor((string) $raw[$k])) {
                return 'Color inválido en ' . $contextLabel . ' (use #RRGGBB).';
            }
        }
        if (isset($raw['font_family']) && ! in_array((string) $raw['font_family'], self::ALLOWED_PDF_FONT_FAMILIES, true)) {
            return 'Familia de fuente no permitida en ' . $contextLabel . '.';
        }
        if (array_key_exists('font_size_pt', $raw)) {
            if (! is_numeric($raw['font_size_pt'])) {
                return 'Tamaño de fuente inválido en ' . $contextLabel . '.';
            }
            $s = (float) $raw['font_size_pt'];
            if ($s < 7.0 || $s > 20.0) {
                return 'El tamaño de fuente en ' . $contextLabel . ' debe estar entre 7 y 20 pt.';
            }
        }
        if (isset($raw['font_weight']) && ! in_array(strtolower(trim((string) $raw['font_weight'])), self::ALLOWED_PDF_FONT_WEIGHTS, true)) {
            return 'Grosor de fuente no permitido en ' . $contextLabel . '.';
        }
        if (isset($raw['font_style']) && ! in_array(strtolower(trim((string) $raw['font_style'])), self::ALLOWED_PDF_FONT_STYLES, true)) {
            return 'Estilo de fuente no permitido en ' . $contextLabel . '.';
        }
        if (isset($raw['text_transform']) && ! in_array(strtolower(trim((string) $raw['text_transform'])), self::ALLOWED_PDF_TEXT_TRANSFORMS, true)) {
            return 'Transformación de texto no permitida en ' . $contextLabel . '.';
        }
        if (array_key_exists('line_height', $raw)) {
            if (! is_numeric($raw['line_height'])) {
                return 'Interlineado inválido en ' . $contextLabel . '.';
            }
            $lh = (float) $raw['line_height'];
            if ($lh < 1.0 || $lh > 3.0) {
                return 'El interlineado en ' . $contextLabel . ' debe estar entre 1 y 3.';
            }
        }

        return null;
    }

    /**
     * @param mixed $raw
     */
    protected static function validateRawGridSectionPageStyleBlock($raw, string $contextLabel): ?string
    {
        if (! is_array($raw)) {
            return 'Estilo inválido en: ' . $contextLabel . '.';
        }
        foreach (['body_bg_color', 'body_text_color'] as $k) {
            if (isset($raw[$k]) && ! self::isValidPdfHexColor((string) $raw[$k])) {
                return 'Color inválido en ' . $contextLabel . ' (use #RRGGBB).';
            }
        }
        if (isset($raw['font_family']) && ! in_array((string) $raw['font_family'], self::ALLOWED_PDF_FONT_FAMILIES, true)) {
            return 'Familia de fuente no permitida en ' . $contextLabel . '.';
        }
        if (array_key_exists('font_size_pt', $raw)) {
            if (! is_numeric($raw['font_size_pt'])) {
                return 'Tamaño de fuente inválido en ' . $contextLabel . '.';
            }
            $s = (float) $raw['font_size_pt'];
            if ($s < 7.0 || $s > 20.0) {
                return 'El tamaño de fuente en ' . $contextLabel . ' debe estar entre 7 y 20 pt.';
            }
        }
        if (isset($raw['font_weight']) && ! in_array(strtolower(trim((string) $raw['font_weight'])), self::ALLOWED_PDF_FONT_WEIGHTS, true)) {
            return 'Grosor de fuente no permitido en ' . $contextLabel . '.';
        }
        if (isset($raw['font_style']) && ! in_array(strtolower(trim((string) $raw['font_style'])), self::ALLOWED_PDF_FONT_STYLES, true)) {
            return 'Estilo de fuente no permitido en ' . $contextLabel . '.';
        }
        if (isset($raw['text_transform']) && ! in_array(strtolower(trim((string) $raw['text_transform'])), self::ALLOWED_PDF_TEXT_TRANSFORMS, true)) {
            return 'Transformación de texto no permitida en ' . $contextLabel . '.';
        }
        if (array_key_exists('line_height', $raw)) {
            if (! is_numeric($raw['line_height'])) {
                return 'Interlineado inválido en ' . $contextLabel . '.';
            }
            $lh = (float) $raw['line_height'];
            if ($lh < 1.0 || $lh > 3.0) {
                return 'El interlineado en ' . $contextLabel . ' debe estar entre 1 y 3.';
            }
        }
        if (array_key_exists('column_border_width_px', $raw)) {
            if (! is_numeric($raw['column_border_width_px'])) {
                return 'El grosor del borde en ' . $contextLabel . ' debe ser numérico.';
            }
            $w = (int) $raw['column_border_width_px'];
            if ($w < 0 || $w > 4) {
                return 'El grosor del borde en ' . $contextLabel . ' debe estar entre 0 y 4 px.';
            }
        }
        if (isset($raw['column_border_color']) && ! self::isValidPdfHexColor((string) $raw['column_border_color'])) {
            return 'Color de borde inválido en ' . $contextLabel . ' (#RRGGBB).';
        }

        return null;
    }

    /**
     * @param mixed $raw
     */
    protected static function validateRawNotesBlockExtras($raw): ?string
    {
        if (! is_array($raw)) {
            return null;
        }
        if (array_key_exists('column_border_width_px', $raw)) {
            if (! is_numeric($raw['column_border_width_px'])) {
                return 'El grosor del borde en notas del resultado debe ser numérico.';
            }
            $w = (int) $raw['column_border_width_px'];
            if ($w < 0 || $w > 4) {
                return 'El grosor del borde en notas del resultado debe estar entre 0 y 4 px.';
            }
        }
        if (isset($raw['column_border_color']) && ! self::isValidPdfHexColor((string) $raw['column_border_color'])) {
            return 'Color de borde inválido en notas del resultado (#RRGGBB).';
        }
        if (array_key_exists('section_title', $raw)) {
            $v = $raw['section_title'];
            if (is_array($v) || is_object($v)) {
                return 'El título del bloque de notas debe ser texto.';
            }
            $t = trim((string) $v);
            $len = function_exists('mb_strlen') ? mb_strlen($t, 'UTF-8') : strlen($t);
            if ($len > self::LAB_FIRMAS_TEXT_MAX_LEN) {
                return 'El título del bloque de notas supera los ' . self::LAB_FIRMAS_TEXT_MAX_LEN . ' caracteres.';
            }
        }

        return null;
    }

    /**
     * @param mixed $raw
     */
    protected static function validateRawGridLabelStrings($raw, array $keys): ?string
    {
        if (! is_array($raw)) {
            return null;
        }
        foreach ($keys as $k) {
            if (! array_key_exists($k, $raw)) {
                continue;
            }
            $v = $raw[$k];
            if (is_array($v) || is_object($v)) {
                return 'Las etiquetas de la plantilla PDF deben ser texto.';
            }
            $t = trim((string) $v);
            $len = function_exists('mb_strlen') ? mb_strlen($t, 'UTF-8') : strlen($t);
            if ($len > self::LAB_FIRMAS_TEXT_MAX_LEN) {
                return 'Un texto de etiqueta supera los ' . self::LAB_FIRMAS_TEXT_MAX_LEN . ' caracteres.';
            }
        }

        return null;
    }

    /**
     * @param mixed $raw
     */
    protected static function validateRawResultsTableStyleBlock($raw): ?string
    {
        if (! is_array($raw)) {
            return 'Estilo de la tabla de resultados inválido.';
        }
        foreach (['header_bg_color', 'header_text_color', 'body_bg_color', 'body_text_color', 'border_color', 'segment_bg_color', 'segment_border_color'] as $k) {
            if (isset($raw[$k]) && ! self::isValidPdfHexColor((string) $raw[$k])) {
                return 'Color inválido en la tabla de resultados (#RRGGBB).';
            }
        }
        if (isset($raw['font_family']) && ! in_array((string) $raw['font_family'], self::ALLOWED_PDF_FONT_FAMILIES, true)) {
            return 'Familia de fuente no permitida en la tabla de resultados.';
        }
        if (array_key_exists('font_size_pt', $raw)) {
            if (! is_numeric($raw['font_size_pt'])) {
                return 'Tamaño de fuente inválido en la tabla de resultados.';
            }
            $s = (float) $raw['font_size_pt'];
            if ($s < 7.0 || $s > 20.0) {
                return 'El tamaño de fuente en la tabla de resultados debe estar entre 7 y 20 pt.';
            }
        }
        if (isset($raw['font_weight']) && ! in_array(strtolower(trim((string) $raw['font_weight'])), self::ALLOWED_PDF_FONT_WEIGHTS, true)) {
            return 'Grosor de fuente no permitido en la tabla de resultados.';
        }
        if (isset($raw['font_style']) && ! in_array(strtolower(trim((string) $raw['font_style'])), self::ALLOWED_PDF_FONT_STYLES, true)) {
            return 'Estilo de fuente no permitido en la tabla de resultados.';
        }
        if (isset($raw['text_transform']) && ! in_array(strtolower(trim((string) $raw['text_transform'])), self::ALLOWED_PDF_TEXT_TRANSFORMS, true)) {
            return 'Transformación de texto no permitida en la tabla de resultados.';
        }
        if (array_key_exists('line_height', $raw)) {
            if (! is_numeric($raw['line_height'])) {
                return 'Interlineado inválido en la tabla de resultados.';
            }
            $lh = (float) $raw['line_height'];
            if ($lh < 1.0 || $lh > 3.0) {
                return 'El interlineado en la tabla de resultados debe estar entre 1 y 3.';
            }
        }
        if (array_key_exists('cell_padding_v_px', $raw)) {
            if (! is_numeric($raw['cell_padding_v_px'])) {
                return 'Relleno vertical de filas inválido en la tabla de resultados.';
            }
            $cp = (int) $raw['cell_padding_v_px'];
            if ($cp < 0 || $cp > 20) {
                return 'El relleno vertical de filas en la tabla de resultados debe estar entre 0 y 20 px.';
            }
        }
        foreach (['table_margin_top_px' => 'superior', 'table_margin_bottom_px' => 'inferior'] as $mk => $mlbl) {
            if (! array_key_exists($mk, $raw)) {
                continue;
            }
            if (! is_numeric($raw[$mk])) {
                return 'Margen ' . $mlbl . ' de table.results inválido.';
            }
            $mv = (int) $raw[$mk];
            if ($mv < 0 || $mv > 80) {
                return 'El margen ' . $mlbl . ' de table.results debe estar entre 0 y 80 px.';
            }
        }
        if (array_key_exists('grupo_prueba_gap_px', $raw)) {
            if (! is_numeric($raw['grupo_prueba_gap_px'])) {
                return 'Espacio entre grupos de prueba inválido.';
            }
            $gg = (int) $raw['grupo_prueba_gap_px'];
            if ($gg < 0 || $gg > 80) {
                return 'El espacio entre grupos de prueba debe estar entre 0 y 80 px.';
            }
        }
        if (array_key_exists('subgrupo_prueba_gap_px', $raw)) {
            if (! is_numeric($raw['subgrupo_prueba_gap_px'])) {
                return 'Espacio entre pruebas del mismo área inválido.';
            }
            $sg = (int) $raw['subgrupo_prueba_gap_px'];
            if ($sg < 0 || $sg > 80) {
                return 'El espacio entre pruebas del mismo área debe estar entre 0 y 80 px.';
            }
        }
        if (array_key_exists('segment_border_width_px', $raw)) {
            if (! is_numeric($raw['segment_border_width_px'])) {
                return 'El grosor del borde de segmento debe ser numérico.';
            }
            $w = (int) $raw['segment_border_width_px'];
            if ($w < 0 || $w > 4) {
                return 'El grosor del borde de segmento debe estar entre 0 y 4 px.';
            }
        }
        if (isset($raw['segment_shadow']) && ! in_array(strtolower(trim((string) $raw['segment_shadow'])), self::ALLOWED_PDF_TEXT_SHADOWS, true)) {
            return 'Sombra de segmento no permitida en la tabla de resultados.';
        }
        foreach (['segment_padding_top_px' => 'superior', 'segment_padding_bottom_px' => 'inferior'] as $pk => $label) {
            if (array_key_exists($pk, $raw)) {
                if (! is_numeric($raw[$pk])) {
                    return 'Relleno ' . $label . ' del texto en fila separadora inválido.';
                }
                $pv = (int) $raw[$pk];
                if ($pv < 0 || $pv > 40) {
                    return 'El relleno ' . $label . ' del texto en fila separadora debe estar entre 0 y 40 px.';
                }
            }
        }
        if (isset($raw['matrix_text_align']) && ! in_array(strtolower(trim((string) $raw['matrix_text_align'])), self::ALLOWED_PDF_TEXT_ALIGNS, true)) {
            return 'Alineación horizontal no permitida en la matriz de referencia.';
        }
        if (isset($raw['matrix_vertical_align']) && ! in_array(strtolower(trim((string) $raw['matrix_vertical_align'])), self::ALLOWED_PDF_VERTICAL_ALIGNS, true)) {
            return 'Alineación vertical no permitida en la matriz de referencia.';
        }
        if (isset($raw['matrix_text_color']) && ! self::isValidPdfHexColor((string) $raw['matrix_text_color'])) {
            return 'Color de texto inválido en la matriz de referencia (#RRGGBB).';
        }
        if (array_key_exists('matrix_font_size_pt', $raw)) {
            if (! is_numeric($raw['matrix_font_size_pt'])) {
                return 'Tamaño de fuente inválido en la matriz de referencia.';
            }
            $mfs = (float) $raw['matrix_font_size_pt'];
            if ($mfs < 7.0 || $mfs > 20.0) {
                return 'El tamaño de fuente en la matriz de referencia debe estar entre 7 y 20 pt.';
            }
        }
        if (isset($raw['matrix_font_weight']) && ! in_array(strtolower(trim((string) $raw['matrix_font_weight'])), self::ALLOWED_PDF_FONT_WEIGHTS, true)) {
            return 'Grosor de fuente no permitido en la matriz de referencia.';
        }
        if (isset($raw['matrix_font_style']) && ! in_array(strtolower(trim((string) $raw['matrix_font_style'])), self::ALLOWED_PDF_FONT_STYLES, true)) {
            return 'Estilo de fuente no permitido en la matriz de referencia.';
        }
        if (isset($raw['matrix_text_transform']) && ! in_array(strtolower(trim((string) $raw['matrix_text_transform'])), self::ALLOWED_PDF_TEXT_TRANSFORMS, true)) {
            return 'Transformación de texto no permitida en la matriz de referencia.';
        }
        if (isset($raw['matrix_header_text_color']) && ! self::isValidPdfHexColor((string) $raw['matrix_header_text_color'])) {
            return 'Color de texto inválido en los encabezados de la matriz de referencia (#RRGGBB).';
        }
        if (isset($raw['matrix_header_font_family']) && ! in_array((string) $raw['matrix_header_font_family'], self::ALLOWED_PDF_FONT_FAMILIES, true)) {
            return 'Familia de fuente no permitida en encabezados de la matriz de referencia.';
        }
        if (array_key_exists('matrix_header_font_size_pt', $raw)) {
            if (! is_numeric($raw['matrix_header_font_size_pt'])) {
                return 'Tamaño de fuente inválido en encabezados de la matriz de referencia.';
            }
            $mhfs = (float) $raw['matrix_header_font_size_pt'];
            if ($mhfs < 7.0 || $mhfs > 20.0) {
                return 'El tamaño de fuente en encabezados de la matriz de referencia debe estar entre 7 y 20 pt.';
            }
        }
        if (isset($raw['matrix_header_font_weight']) && ! in_array(strtolower(trim((string) $raw['matrix_header_font_weight'])), self::ALLOWED_PDF_FONT_WEIGHTS, true)) {
            return 'Grosor de fuente no permitido en encabezados de la matriz de referencia.';
        }
        if (isset($raw['matrix_header_font_style']) && ! in_array(strtolower(trim((string) $raw['matrix_header_font_style'])), self::ALLOWED_PDF_FONT_STYLES, true)) {
            return 'Estilo de fuente no permitido en encabezados de la matriz de referencia.';
        }
        if (isset($raw['matrix_header_text_transform']) && ! in_array(strtolower(trim((string) $raw['matrix_header_text_transform'])), self::ALLOWED_PDF_TEXT_TRANSFORMS, true)) {
            return 'Transformación de texto no permitida en encabezados de la matriz de referencia.';
        }
        foreach ([
            'matrix_col_population_align',
            'matrix_col_parameter_align',
            'matrix_col_sex_align',
            'matrix_col_reference_align',
            'matrix_hdr_population_align',
            'matrix_hdr_parameter_align',
            'matrix_hdr_sex_align',
            'matrix_hdr_reference_align',
            'results_col_analisis_align',
            'results_col_resultado_align',
            'results_col_rango_align',
            'results_col_interpretacion_align',
            'results_hdr_analisis_align',
            'results_hdr_resultado_align',
            'results_hdr_rango_align',
            'results_hdr_interpretacion_align',
        ] as $ak) {
            if (isset($raw[$ak]) && ! in_array(strtolower(trim((string) $raw[$ak])), self::ALLOWED_PDF_TEXT_ALIGNS, true)) {
                $msg = str_starts_with($ak, 'results_')
                    ? 'Alineación no permitida en columnas de la tabla principal de resultados (' . $ak . ').'
                    : 'Alineación no permitida en columnas de la matriz de referencia (' . $ak . ').';

                return $msg;
            }
        }
        if (isset($raw['grupo_cabecera_title_mode']) && ! in_array(
            self::normalizeGrupoCabeceraTitleMode($raw['grupo_cabecera_title_mode']),
            self::ALLOWED_GRUPO_CABECERA_TITLE_MODES,
            true
        )) {
            return 'Modo de título de cabecera de grupo no permitido.';
        }
        if (isset($raw['grupo_area_separator_color']) && ! self::isValidPdfHexColor((string) $raw['grupo_area_separator_color'])) {
            return 'Color inválido en separador de área (#RRGGBB).';
        }
        if (array_key_exists('grupo_area_separator_width_px', $raw)) {
            if (! is_numeric($raw['grupo_area_separator_width_px'])) {
                return 'Grosor de línea del separador de área inválido.';
            }
            $sw = (int) $raw['grupo_area_separator_width_px'];
            if ($sw < 0 || $sw > 4) {
                return 'El grosor de línea del separador de área debe estar entre 0 y 4 px.';
            }
        }
        if (array_key_exists('grupo_area_separator_font_size_pt', $raw)) {
            if (! is_numeric($raw['grupo_area_separator_font_size_pt'])) {
                return 'Tamaño de fuente del separador de área inválido.';
            }
            $sfs = (float) $raw['grupo_area_separator_font_size_pt'];
            if ($sfs < 7.0 || $sfs > 20.0) {
                return 'El tamaño del separador de área debe estar entre 7 y 20 pt.';
            }
        }
        if (isset($raw['grupo_area_separator_font_weight']) && ! in_array(strtolower(trim((string) $raw['grupo_area_separator_font_weight'])), self::ALLOWED_PDF_FONT_WEIGHTS, true)) {
            return 'Grosor de fuente no permitido en separador de área.';
        }
        if (array_key_exists('grupo_area_separator_margin_top_px', $raw)) {
            if (! is_numeric($raw['grupo_area_separator_margin_top_px'])) {
                return 'Margen superior del separador de área inválido.';
            }
            $stm = (int) $raw['grupo_area_separator_margin_top_px'];
            if ($stm < 0 || $stm > 80) {
                return 'El margen superior del separador de área debe estar entre 0 y 80 px.';
            }
        }
        if (array_key_exists('grupo_area_separator_margin_bottom_px', $raw)) {
            if (! is_numeric($raw['grupo_area_separator_margin_bottom_px'])) {
                return 'Margen inferior del separador de área inválido.';
            }
            $sm = (int) $raw['grupo_area_separator_margin_bottom_px'];
            if ($sm < 0 || $sm > 80) {
                return 'El margen inferior del separador de área debe estar entre 0 y 80 px.';
            }
        }
        foreach ([
            'grupo_cabecera_title_margin_top_px' => 'superior del nombre de análisis',
            'grupo_cabecera_title_margin_bottom_px' => 'inferior del nombre de análisis',
            'grupo_cabecera_tipo_muestra_margin_top_px' => 'superior del tipo de muestra',
            'grupo_cabecera_tipo_muestra_margin_bottom_px' => 'inferior del tipo de muestra',
            'grupo_cabecera_metodo_margin_top_px' => 'superior del método',
            'grupo_cabecera_metodo_margin_bottom_px' => 'inferior del método',
        ] as $pk => $label) {
            if (array_key_exists($pk, $raw)) {
                if (! is_numeric($raw[$pk])) {
                    return 'Espacio ' . $label . ' en separador de análisis inválido.';
                }
                $pv = (int) $raw[$pk];
                if ($pv < 0 || $pv > 40) {
                    return 'El espacio ' . $label . ' en separador de análisis debe estar entre 0 y 40 px.';
                }
            }
        }

        return null;
    }

    /**
     * Valida estilos del JSON de plantilla antes de normalizar (POST / API).
     *
     * @param array<string, mixed> $decoded
     */
    public static function validateLayoutDecodedStyles(array $decoded): ?string
    {
        if (isset($decoded['instances'])) {
            if (! is_array($decoded['instances'])) {
                return 'La lista de elementos del diseño es inválida.';
            }
            foreach ($decoded['instances'] as $inst) {
                if (! is_array($inst)) {
                    continue;
                }
                $et = (string) ($inst['element_type'] ?? '');
                if ($et === 'custom_text') {
                    $errCt = self::validateRawCustomTextPayload($inst['custom_text'] ?? null);
                    if ($errCt !== null) {
                        return $errCt;
                    }

                    continue;
                }
                $ts = $inst['text_style'] ?? [];
                if (! is_array($ts)) {
                    return 'Los estilos de un elemento del diseño deben ser un objeto JSON.';
                }
                if ($ts === []) {
                    continue;
                }
                $err = self::validateRawTextStyleArray($ts);
                if ($err !== null) {
                    return $err;
                }
            }
        }
        $ps = $decoded['page_style'] ?? null;
        if ($ps === null || $ps === []) {
            return null;
        }
        if (! is_array($ps)) {
            return 'El bloque de estilos globales (page_style) es inválido.';
        }
        if (isset($ps['card_header'])) {
            $err = self::validateRawCardHeaderStyleBlock($ps['card_header']);
            if ($err !== null) {
                return $err;
            }
        }
        $headerSec = $ps['header_section'] ?? null;
        if (is_array($headerSec) && array_key_exists('separator_color', $headerSec)) {
            $c = (string) $headerSec['separator_color'];
            if ($c !== '' && ! self::isValidPdfHexColor($c)) {
                return 'Color del separador de encabezado de sección inválido (#RRGGBB).';
            }
        }
        if (isset($ps['notes'])) {
            $err = self::validateRawNotesLikeStyleBlock($ps['notes'], 'notas del resultado');
            if ($err !== null) {
                return $err;
            }
            $err = self::validateRawNotesBlockExtras($ps['notes']);
            if ($err !== null) {
                return $err;
            }
        }
        if (isset($ps['header_grid'])) {
            $err = self::validateRawGridSectionPageStyleBlock($ps['header_grid'], 'cuadrícula de encabezado superior');
            if ($err !== null) {
                return $err;
            }
            $hgLabelKeys = ['label_qr_hint'];
            foreach (array_keys(self::HEADER_GRID_LABEL_DEFAULTS) as $hid) {
                $hgLabelKeys[] = 'label_' . $hid;
            }
            $err = self::validateRawGridLabelStrings($ps['header_grid'], $hgLabelKeys);
            if ($err !== null) {
                return $err;
            }
            $hgRaw = $ps['header_grid'];
            if (is_array($hgRaw)) {
                foreach (array_keys(self::HEADER_GRID_LABEL_DEFAULTS) as $hid) {
                    $ck = 'label_' . $hid . '_text_color';
                    if (isset($hgRaw[$ck]) && ! self::isValidPdfHexColor((string) $hgRaw[$ck])) {
                        return 'Color inválido en encabezado (#RRGGBB): ' . $ck . '.';
                    }
                }
                if (isset($hgRaw['label_qr_hint_text_color']) && ! self::isValidPdfHexColor((string) $hgRaw['label_qr_hint_text_color'])) {
                    return 'Color inválido en leyenda del QR (#RRGGBB).';
                }
                if (array_key_exists('qr_size_percent', $hgRaw)) {
                    if (! is_numeric($hgRaw['qr_size_percent'])) {
                        return 'Tamaño del QR (%) inválido en el encabezado.';
                    }
                    $qsp = (int) $hgRaw['qr_size_percent'];
                    if ($qsp < 50 || $qsp > 400) {
                        return 'El tamaño del QR en el encabezado debe estar entre 50 y 400 %.';
                    }
                }
                $hgFsKeys = ['label_qr_hint_font_size_pt'];
                foreach (array_keys(self::HEADER_GRID_LABEL_DEFAULTS) as $hid) {
                    $hgFsKeys[] = 'label_' . $hid . '_font_size_pt';
                }
                foreach ($hgFsKeys as $sk) {
                    if (! array_key_exists($sk, $hgRaw)) {
                        continue;
                    }
                    if (! is_numeric($hgRaw[$sk])) {
                        return 'Tamaño de fuente en encabezado inválido (' . $sk . ').';
                    }
                    $fs = (float) $hgRaw[$sk];
                    if ($fs < 7.0 || $fs > 20.0) {
                        return 'El tamaño en encabezado debe estar entre 7 y 20 pt (' . $sk . ').';
                    }
                }
                $hgFwKeys = ['label_qr_hint_font_weight'];
                foreach (array_keys(self::HEADER_GRID_LABEL_DEFAULTS) as $hid) {
                    $hgFwKeys[] = 'label_' . $hid . '_font_weight';
                }
                foreach ($hgFwKeys as $wk) {
                    if (! isset($hgRaw[$wk])) {
                        continue;
                    }
                    if (! in_array(strtolower(trim((string) $hgRaw[$wk])), self::ALLOWED_PDF_FONT_WEIGHTS, true)) {
                        return 'Grosor de fuente no permitido en encabezado (' . $wk . ').';
                    }
                }
                $hgFstKeys = ['label_qr_hint_font_style'];
                foreach (array_keys(self::HEADER_GRID_LABEL_DEFAULTS) as $hid) {
                    $hgFstKeys[] = 'label_' . $hid . '_font_style';
                }
                foreach ($hgFstKeys as $fk) {
                    if (! isset($hgRaw[$fk])) {
                        continue;
                    }
                    if (! in_array(strtolower(trim((string) $hgRaw[$fk])), self::ALLOWED_PDF_FONT_STYLES, true)) {
                        return 'Estilo de fuente no permitido en encabezado (' . $fk . ').';
                    }
                }
                $hgTtKeys = ['label_qr_hint_text_transform'];
                foreach (array_keys(self::HEADER_GRID_LABEL_DEFAULTS) as $hid) {
                    $hgTtKeys[] = 'label_' . $hid . '_text_transform';
                }
                foreach ($hgTtKeys as $tk) {
                    if (! isset($hgRaw[$tk])) {
                        continue;
                    }
                    if (! in_array(strtolower(trim((string) $hgRaw[$tk])), self::ALLOWED_PDF_TEXT_TRANSFORMS, true)) {
                        return 'Transformación de texto no permitida en encabezado (' . $tk . ').';
                    }
                }
            }
        }
        if (isset($ps['patient_doctor_grid'])) {
            $err = self::validateRawGridSectionPageStyleBlock($ps['patient_doctor_grid'], 'cuadrícula paciente / médico');
            if ($err !== null) {
                return $err;
            }
            $pk = [];
            foreach (array_keys(self::PATIENT_DOCTOR_GRID_LABEL_DEFAULTS) as $id) {
                $pk[] = 'label_' . $id;
            }
            $err = self::validateRawGridLabelStrings($ps['patient_doctor_grid'], $pk);
            if ($err !== null) {
                return $err;
            }
            $pdRaw = $ps['patient_doctor_grid'];
            if (is_array($pdRaw)) {
                foreach (array_keys(self::PATIENT_DOCTOR_GRID_LABEL_DEFAULTS) as $pid) {
                    $ck = 'label_' . $pid . '_text_color';
                    if (isset($pdRaw[$ck]) && ! self::isValidPdfHexColor((string) $pdRaw[$ck])) {
                        return 'Color inválido en paciente/médico (#RRGGBB): ' . $ck . '.';
                    }
                }
                $pdFsKeys = [];
                foreach (array_keys(self::PATIENT_DOCTOR_GRID_LABEL_DEFAULTS) as $pid) {
                    $pdFsKeys[] = 'label_' . $pid . '_font_size_pt';
                }
                foreach ($pdFsKeys as $sk) {
                    if (! array_key_exists($sk, $pdRaw)) {
                        continue;
                    }
                    if (! is_numeric($pdRaw[$sk])) {
                        return 'Tamaño de fuente en paciente/médico inválido (' . $sk . ').';
                    }
                    $fs = (float) $pdRaw[$sk];
                    if ($fs < 7.0 || $fs > 20.0) {
                        return 'El tamaño en paciente/médico debe estar entre 7 y 20 pt (' . $sk . ').';
                    }
                }
                $pdFwKeys = [];
                foreach (array_keys(self::PATIENT_DOCTOR_GRID_LABEL_DEFAULTS) as $pid) {
                    $pdFwKeys[] = 'label_' . $pid . '_font_weight';
                }
                foreach ($pdFwKeys as $wk) {
                    if (! isset($pdRaw[$wk])) {
                        continue;
                    }
                    if (! in_array(strtolower(trim((string) $pdRaw[$wk])), self::ALLOWED_PDF_FONT_WEIGHTS, true)) {
                        return 'Grosor de fuente no permitido en paciente/médico (' . $wk . ').';
                    }
                }
                $pdFstKeys = [];
                foreach (array_keys(self::PATIENT_DOCTOR_GRID_LABEL_DEFAULTS) as $pid) {
                    $pdFstKeys[] = 'label_' . $pid . '_font_style';
                }
                foreach ($pdFstKeys as $fk) {
                    if (! isset($pdRaw[$fk])) {
                        continue;
                    }
                    if (! in_array(strtolower(trim((string) $pdRaw[$fk])), self::ALLOWED_PDF_FONT_STYLES, true)) {
                        return 'Estilo de fuente no permitido en paciente/médico (' . $fk . ').';
                    }
                }
                $pdTtKeys = [];
                foreach (array_keys(self::PATIENT_DOCTOR_GRID_LABEL_DEFAULTS) as $pid) {
                    $pdTtKeys[] = 'label_' . $pid . '_text_transform';
                }
                foreach ($pdTtKeys as $tk) {
                    if (! isset($pdRaw[$tk])) {
                        continue;
                    }
                    if (! in_array(strtolower(trim((string) $pdRaw[$tk])), self::ALLOWED_PDF_TEXT_TRANSFORMS, true)) {
                        return 'Transformación de texto no permitida en paciente/médico (' . $tk . ').';
                    }
                }
            }
        }
        if (isset($ps['footer_grid'])) {
            $err = self::validateRawGridSectionPageStyleBlock($ps['footer_grid'], 'cuadrícula de pie de página');
            if ($err !== null) {
                return $err;
            }
            $err = self::validateRawGridLabelStrings($ps['footer_grid'], ['label_footer_generated']);
            if ($err !== null) {
                return $err;
            }
            foreach (['footer_company_text_color', 'label_footer_generated_color', 'label_footer_datetime_color', 'footer_policy_text_color', 'section_top_border_color'] as $ck) {
                if (isset($ps['footer_grid'][$ck]) && ! self::isValidPdfHexColor((string) $ps['footer_grid'][$ck])) {
                    return 'Color inválido en pie de página (#RRGGBB): ' . $ck . '.';
                }
            }
            if (array_key_exists('section_top_border_width_px', $ps['footer_grid'])) {
                if (! is_numeric($ps['footer_grid']['section_top_border_width_px'])) {
                    return 'El grosor del borde superior del pie debe ser numérico.';
                }
                $tw = (int) $ps['footer_grid']['section_top_border_width_px'];
                if ($tw < 0 || $tw > 6) {
                    return 'El borde superior del pie debe estar entre 0 y 6 px.';
                }
            }
            $fgFsKeys = ['footer_company_font_size_pt', 'label_footer_generated_font_size_pt', 'label_footer_datetime_font_size_pt', 'footer_policy_font_size_pt'];
            foreach ($fgFsKeys as $sk) {
                if (! array_key_exists($sk, $ps['footer_grid'])) {
                    continue;
                }
                if (! is_numeric($ps['footer_grid'][$sk])) {
                    return 'Tamaño de fuente en pie de página inválido (' . $sk . ').';
                }
                $fs = (float) $ps['footer_grid'][$sk];
                if ($fs < 7.0 || $fs > 20.0) {
                    return 'El tamaño en pie de página debe estar entre 7 y 20 pt (' . $sk . ').';
                }
            }
            $fgFwKeys = ['footer_company_font_weight', 'label_footer_generated_font_weight', 'label_footer_datetime_font_weight', 'footer_policy_font_weight'];
            foreach ($fgFwKeys as $wk) {
                if (! isset($ps['footer_grid'][$wk])) {
                    continue;
                }
                if (! in_array(strtolower(trim((string) $ps['footer_grid'][$wk])), self::ALLOWED_PDF_FONT_WEIGHTS, true)) {
                    return 'Grosor de fuente no permitido en pie de página (' . $wk . ').';
                }
            }
            $fgFstKeys = ['footer_company_font_style', 'label_footer_generated_font_style', 'label_footer_datetime_font_style', 'footer_policy_font_style'];
            foreach ($fgFstKeys as $fk) {
                if (! isset($ps['footer_grid'][$fk])) {
                    continue;
                }
                if (! in_array(strtolower(trim((string) $ps['footer_grid'][$fk])), self::ALLOWED_PDF_FONT_STYLES, true)) {
                    return 'Estilo de fuente no permitido en pie de página (' . $fk . ').';
                }
            }
            $fgTtKeys = ['footer_company_text_transform', 'label_footer_generated_text_transform', 'label_footer_datetime_text_transform', 'footer_policy_text_transform'];
            foreach ($fgTtKeys as $tk) {
                if (! isset($ps['footer_grid'][$tk])) {
                    continue;
                }
                if (! in_array(strtolower(trim((string) $ps['footer_grid'][$tk])), self::ALLOWED_PDF_TEXT_TRANSFORMS, true)) {
                    return 'Transformación de texto no permitida en pie de página (' . $tk . ').';
                }
            }
        }
        if (isset($ps['lab_firmas'])) {
            $err = self::validateRawNotesLikeStyleBlock($ps['lab_firmas'], 'firmas del laboratorio');
            if ($err !== null) {
                return $err;
            }
            $err = self::validateRawLabFirmasTextFields($ps['lab_firmas']);
            if ($err !== null) {
                return $err;
            }
            $err = self::validateRawLabFirmasColumnStyle($ps['lab_firmas']);
            if ($err !== null) {
                return $err;
            }
            $err = self::validateRawLabFirmasPlacementStyle($ps['lab_firmas']);
            if ($err !== null) {
                return $err;
            }
        }
        if (isset($ps['results_table'])) {
            $err = self::validateRawResultsTableStyleBlock($ps['results_table']);
            if ($err !== null) {
                return $err;
            }
        }
        if (isset($ps['print_pagination'])) {
            if (! is_array($ps['print_pagination'])) {
                return 'La configuración de paginación de impresión es inválida.';
            }
            $pp = $ps['print_pagination'];
            if (isset($pp['label_position'])) {
                $allowed = ['top-left', 'top-center', 'top-right', 'bottom-left', 'bottom-center', 'bottom-right'];
                if (! in_array(strtolower(trim((string) $pp['label_position'])), $allowed, true)) {
                    return 'Posición de etiqueta de paginación no válida.';
                }
            }
            if (isset($pp['value_position'])) {
                $allowed = ['top-left', 'top-center', 'top-right', 'bottom-left', 'bottom-center', 'bottom-right'];
                if (! in_array(strtolower(trim((string) $pp['value_position'])), $allowed, true)) {
                    return 'Posición de valor de paginación no válida.';
                }
            }
            if (isset($pp['label_text']) && ! is_scalar($pp['label_text'])) {
                return 'Texto de etiqueta de paginación inválido.';
            }
        }
        if (isset($ps['grupo_prueba_page_break'])) {
            if (! is_array($ps['grupo_prueba_page_break'])) {
                return 'La configuración de saltos de página en grupos de prueba es inválida.';
            }
            $gpb = $ps['grupo_prueba_page_break'];
            if (isset($gpb['mode']) && ! in_array(strtolower(trim((string) $gpb['mode'])), self::GRUPO_PRUEBA_PAGE_BREAK_MODES, true)) {
                return 'Modo de salto de página en grupos de prueba no válido.';
            }
            if (isset($gpb['compact_min_scale_percent']) && ! is_numeric($gpb['compact_min_scale_percent'])) {
                return 'Escala de compactación en grupos de prueba inválida.';
            }
            if (isset($gpb['compact_cell_padding_px']) && ! is_numeric($gpb['compact_cell_padding_px'])) {
                return 'Relleno de filas en compactación inválido.';
            }
            if (isset($gpb['min_remaining_mm_to_force_break']) && ! is_numeric($gpb['min_remaining_mm_to_force_break'])) {
                return 'Umbral de espacio restante para salto de página inválido.';
            }
        }

        return null;
    }

    /**
     * @param mixed $raw
     *
     * @return array{bg_color: string, text_color: string, font_family: string, font_size_pt: float, font_weight: string, font_style: string, text_transform: string}
     */
    public static function normalizeCardHeaderStyle($raw): array
    {
        $def = self::DEFAULT_CARD_HEADER_STYLE;
        $s   = is_array($raw) ? $raw : [];
        $bg  = strtoupper(trim((string) ($s['bg_color'] ?? $def['bg_color'])));
        $tc  = strtoupper(trim((string) ($s['text_color'] ?? $def['text_color'])));
        if (! preg_match('/^#[0-9A-F]{6}$/', $bg)) {
            $bg = $def['bg_color'];
        }
        if (! preg_match('/^#[0-9A-F]{6}$/', $tc)) {
            $tc = $def['text_color'];
        }
        $family = (string) ($s['font_family'] ?? $def['font_family']);
        if (! in_array($family, self::ALLOWED_PDF_FONT_FAMILIES, true)) {
            $family = $def['font_family'];
        }
        $size = isset($s['font_size_pt']) ? (float) $s['font_size_pt'] : $def['font_size_pt'];
        $size = round(max(7.0, min(20.0, $size)), 2);
        $weight = strtolower(trim((string) ($s['font_weight'] ?? $def['font_weight'])));
        if (! in_array($weight, self::ALLOWED_PDF_FONT_WEIGHTS, true)) {
            $weight = $def['font_weight'];
        }
        $style = strtolower(trim((string) ($s['font_style'] ?? $def['font_style'])));
        if (! in_array($style, self::ALLOWED_PDF_FONT_STYLES, true)) {
            $style = $def['font_style'];
        }
        $transform = strtolower(trim((string) ($s['text_transform'] ?? $def['text_transform'])));
        if (! in_array($transform, self::ALLOWED_PDF_TEXT_TRANSFORMS, true)) {
            $transform = $def['text_transform'];
        }

        return [
            'bg_color'        => $bg,
            'text_color'      => $tc,
            'bg_transparent'  => self::labFirmasBool($s, 'bg_transparent', ! empty($def['bg_transparent'])),
            'font_family'     => $family,
            'font_size_pt'    => $size,
            'font_weight'     => $weight,
            'font_style'      => $style,
            'text_transform'  => $transform,
        ];
    }

    /**
     * @param mixed $raw
     *
     * @return array{title_bg_color: string, title_text_color: string, body_bg_color: string, body_text_color: string, font_family: string, font_size_pt: float, font_weight: string, font_style: string, text_transform: string, line_height: float}
     */
    public static function normalizeNotesStyle($raw): array
    {
        $def = self::DEFAULT_NOTES_STYLE;
        $s   = is_array($raw) ? $raw : [];
        $pickColor = static function (string $k, string $fallback) use ($s): string {
            $v = strtoupper(trim((string) ($s[$k] ?? $fallback)));
            return preg_match('/^#[0-9A-F]{6}$/', $v) ? $v : $fallback;
        };
        $family = (string) ($s['font_family'] ?? $def['font_family']);
        if (! in_array($family, self::ALLOWED_PDF_FONT_FAMILIES, true)) {
            $family = $def['font_family'];
        }
        $size = isset($s['font_size_pt']) ? (float) $s['font_size_pt'] : $def['font_size_pt'];
        $size = round(max(7.0, min(20.0, $size)), 2);
        $weight = strtolower(trim((string) ($s['font_weight'] ?? $def['font_weight'])));
        if (! in_array($weight, self::ALLOWED_PDF_FONT_WEIGHTS, true)) {
            $weight = $def['font_weight'];
        }
        $style = strtolower(trim((string) ($s['font_style'] ?? $def['font_style'])));
        if (! in_array($style, self::ALLOWED_PDF_FONT_STYLES, true)) {
            $style = $def['font_style'];
        }
        $transform = strtolower(trim((string) ($s['text_transform'] ?? $def['text_transform'])));
        if (! in_array($transform, self::ALLOWED_PDF_TEXT_TRANSFORMS, true)) {
            $transform = $def['text_transform'];
        }
        $lh = isset($s['line_height']) ? (float) $s['line_height'] : $def['line_height'];
        $lh = round(max(1.0, min(3.0, $lh)), 2);

        return [
            'title_bg_color'   => $pickColor('title_bg_color', $def['title_bg_color']),
            'title_text_color' => $pickColor('title_text_color', $def['title_text_color']),
            'title_transparent'=> self::labFirmasBool($s, 'title_transparent', ! empty($def['title_transparent'])),
            'body_bg_color'    => $pickColor('body_bg_color', $def['body_bg_color']),
            'body_text_color'  => $pickColor('body_text_color', $def['body_text_color']),
            'body_transparent' => self::labFirmasBool($s, 'body_transparent', ! empty($def['body_transparent'])),
            'font_family'      => $family,
            'font_size_pt'     => $size,
            'font_weight'      => $weight,
            'font_style'       => $style,
            'text_transform'   => $transform,
            'line_height'      => $lh,
            'column_border_width_px' => self::normalizeLabFirmasColumnBorderWidthPx($s['column_border_width_px'] ?? null, (int) ($def['column_border_width_px'] ?? 1)),
            'column_border_color' => self::normalizeLabFirmasBorderColor($s['column_border_color'] ?? null, (string) ($def['column_border_color'] ?? '#DDDDDD')),
            'section_title'    => self::clipLabFirmasLabel(isset($s['section_title']) ? (string) $s['section_title'] : null, (string) ($def['section_title'] ?? 'NOTAS')),
            'show_section_title'=> self::labFirmasBool($s, 'show_section_title', (bool) ($def['show_section_title'] ?? true)),
        ];
    }

    /**
     * Recorta un texto de etiqueta del bloque de firmas (PDF).
     */
    public static function clipLabFirmasLabel(?string $value, string $fallback, int $maxLen = self::LAB_FIRMAS_TEXT_MAX_LEN): string
    {
        $t = trim((string) ($value ?? ''));
        if ($t === '') {
            return $fallback;
        }
        if (function_exists('mb_substr')) {
            return mb_substr($t, 0, $maxLen, 'UTF-8');
        }

        return substr($t, 0, $maxLen);
    }

    /**
     * Etiqueta que puede quedar vacía si el usuario la borra en la plantilla (p. ej. matrícula sin prefijo).
     */
    public static function clipLabFirmasLabelAllowEmpty(string $value, int $maxLen = self::LAB_FIRMAS_TEXT_MAX_LEN): string
    {
        $t = trim($value);
        if ($t === '') {
            return '';
        }
        if (function_exists('mb_substr')) {
            return mb_substr($t, 0, $maxLen, 'UTF-8');
        }

        return substr($t, 0, $maxLen);
    }

    /**
     * Plantillas antiguas guardaban «Validado por»; el término deseado en reporte es «Verificado por».
     * Solo sustituye si el texto (tras recorte) coincide exactamente con esa frase (con o sin «:»).
     */
    public static function mapLegacyValidatorLabel(string $clipped, string $default): string
    {
        $t = trim($clipped);
        if (preg_match('/^validado\s+por:?\s*$/iu', $t)) {
            return $default;
        }

        return $clipped;
    }

    /**
     * «Firma:» pasó a mostrarse como «ATENTAMENTE» sobre la imagen de firma.
     */
    public static function mapLegacyFirmaLabel(string $clipped, string $default): string
    {
        $t = trim($clipped);
        if (preg_match('/^firma:?\s*$/iu', $t)) {
            return $default;
        }

        return $clipped;
    }

    /**
     * Sin etiqueta sobre el nombre del aprobador; limpia textos antiguos.
     */
    public static function mapLegacyApproverNameLabel(string $clipped, string $default): string
    {
        $t = trim($clipped);
        if (preg_match('/^aprobado\s+por:?\s*$/iu', $t) || preg_match('/^atentamente:?\s*$/iu', $t)) {
            return $default;
        }

        return $clipped;
    }

    /**
     * Sin prefijo «Cargo:»; solo el valor.
     */
    public static function mapLegacyCargoLabel(string $clipped, string $default): string
    {
        $t = trim($clipped);
        if (preg_match('/^cargo:?\s*$/iu', $t)) {
            return $default;
        }

        return $clipped;
    }

    /**
     * Sin rótulo «Sello» sobre la imagen del sello.
     */
    public static function mapLegacySealLabel(string $clipped, string $default): string
    {
        $t = trim($clipped);
        if (preg_match('/^sello:?\s*$/iu', $t)) {
            return $default;
        }

        return $clipped;
    }

    /**
     * @param array<string, mixed> $s
     */
    public static function labFirmasBool(array $s, string $key, bool $default = true): bool
    {
        if (! array_key_exists($key, $s)) {
            return $default;
        }
        $v = $s[$key];
        if (is_bool($v)) {
            return $v;
        }
        if (is_int($v) || is_float($v)) {
            return (int) $v !== 0;
        }
        $t = strtolower(trim((string) $v));

        return in_array($t, ['1', 'true', 'yes', 'on'], true);
    }

    /**
     * @param array<string, mixed> $s
     */
    public static function labFirmasLineModeInline(array $s, string $key): bool
    {
        return isset($s[$key]) && trim((string) $s[$key]) === 'inline';
    }

    /**
     * Estilo + textos configurables del bloque de firmas (misma forma base que notas).
     *
     * @param mixed $raw
     *
     * @return array<string, mixed>
     */
    public static function normalizeLabFirmasStyle($raw): array
    {
        $n   = self::normalizeNotesStyle($raw);
        $s   = is_array($raw) ? $raw : [];
        $def = self::DEFAULT_LAB_FIRMAS_LABELS;

        $labelValidator = self::clipLabFirmasLabel(isset($s['label_validator']) ? (string) $s['label_validator'] : null, $def['label_validator']);
        $labelValidator = self::mapLegacyValidatorLabel($labelValidator, $def['label_validator']);

        $labelFirma = self::clipLabFirmasLabel(isset($s['label_firma']) ? (string) $s['label_firma'] : null, $def['label_firma']);
        $labelFirma = self::mapLegacyFirmaLabel($labelFirma, $def['label_firma']);

        $labelApprover = self::clipLabFirmasLabel(isset($s['label_approver']) ? (string) $s['label_approver'] : null, $def['label_approver']);
        $labelApprover = self::mapLegacyApproverNameLabel($labelApprover, $def['label_approver']);

        $labelCargo = self::clipLabFirmasLabel(isset($s['label_cargo']) ? (string) $s['label_cargo'] : null, $def['label_cargo']);
        $labelCargo = self::mapLegacyCargoLabel($labelCargo, $def['label_cargo']);

        $labelSeal = self::clipLabFirmasLabel(isset($s['label_seal']) ? (string) $s['label_seal'] : null, $def['label_seal']);
        $labelSeal = self::mapLegacySealLabel($labelSeal, $def['label_seal']);

        $areaWeight = strtolower(trim((string) ($s['area_heading_font_weight'] ?? $def['area_heading_font_weight'])));
        if (! in_array($areaWeight, self::ALLOWED_PDF_FONT_WEIGHTS, true)) {
            $areaWeight = (string) $def['area_heading_font_weight'];
        }
        $areaTransform = strtolower(trim((string) ($s['area_heading_text_transform'] ?? $def['area_heading_text_transform'])));
        if (! in_array($areaTransform, self::ALLOWED_PDF_TEXT_TRANSFORMS, true)) {
            $areaTransform = (string) $def['area_heading_text_transform'];
        }

        return array_merge($n, [
            'section_title'       => self::clipLabFirmasLabel(isset($s['section_title']) ? (string) $s['section_title'] : null, $def['section_title']),
            'show_section_title'  => self::labFirmasBool($s, 'show_section_title', (bool) $def['show_section_title']),
            'label_validator'     => $labelValidator,
            'show_label_validator'=> self::labFirmasBool($s, 'show_label_validator', (bool) $def['show_label_validator']),
            'validator_line_mode' => self::labFirmasLineModeInline($s, 'validator_line_mode') ? 'inline' : 'stacked',
            'label_seal'          => $labelSeal,
            'show_label_seal'     => self::labFirmasBool($s, 'show_label_seal', (bool) $def['show_label_seal']),
            'label_seal_line_mode'=> self::labFirmasLineModeInline($s, 'label_seal_line_mode') ? 'inline' : 'stacked',
            'label_firma'         => $labelFirma,
            'show_label_firma'    => self::labFirmasBool($s, 'show_label_firma', (bool) $def['show_label_firma']),
            'label_firma_line_mode'=> self::labFirmasLineModeInline($s, 'label_firma_line_mode') ? 'inline' : 'stacked',
            'label_approver'      => $labelApprover,
            'show_label_approver' => self::labFirmasBool($s, 'show_label_approver', (bool) $def['show_label_approver']),
            'label_approver_line_mode'=> self::labFirmasLineModeInline($s, 'label_approver_line_mode') ? 'inline' : 'stacked',
            'label_cargo'         => $labelCargo,
            'show_label_cargo'    => self::labFirmasBool($s, 'show_label_cargo', (bool) $def['show_label_cargo']),
            'label_cargo_line_mode'=> self::labFirmasLineModeInline($s, 'label_cargo_line_mode') ? 'inline' : 'stacked',
            'label_matricula'     => array_key_exists('label_matricula', $s)
                ? self::clipLabFirmasLabelAllowEmpty((string) $s['label_matricula'])
                : self::clipLabFirmasLabel(null, $def['label_matricula']),
            'show_label_matricula'=> self::labFirmasBool($s, 'show_label_matricula', (bool) $def['show_label_matricula']),
            'label_matricula_line_mode'=> self::labFirmasLineModeInline($s, 'label_matricula_line_mode') ? 'inline' : 'stacked',
            'body_transparent'    => self::labFirmasBool($s, 'body_transparent', (bool) $def['body_transparent']),
            'column_border_width_px' => self::normalizeLabFirmasColumnBorderWidthPx($s['column_border_width_px'] ?? null, (int) $def['column_border_width_px']),
            'column_border_color' => self::normalizeLabFirmasBorderColor($s['column_border_color'] ?? null, (string) $def['column_border_color']),
            'placement'           => self::normalizeLabFirmasPlacement($s['placement'] ?? null, (string) $def['placement']),
            'show_area_heading'   => self::labFirmasBool($s, 'show_area_heading', (bool) $def['show_area_heading']),
            'area_heading_color'  => self::normalizeLabFirmasBorderColor($s['area_heading_color'] ?? null, (string) $def['area_heading_color']),
            'area_heading_font_size_pt' => self::normalizeLabFirmasAreaHeadingFontSize($s['area_heading_font_size_pt'] ?? null, (float) $def['area_heading_font_size_pt']),
            'area_heading_font_weight'  => $areaWeight,
            'area_heading_text_transform' => $areaTransform,
            'inline_margin_top_pt'    => self::normalizeLabFirmasMarginPt($s['inline_margin_top_pt'] ?? null, (float) $def['inline_margin_top_pt']),
            'inline_margin_bottom_pt' => self::normalizeLabFirmasMarginPt($s['inline_margin_bottom_pt'] ?? null, (float) $def['inline_margin_bottom_pt']),
            'seal_max_height_px'      => self::normalizeLabFirmasImagePx($s['seal_max_height_px'] ?? null, (int) $def['seal_max_height_px'], 40, 200),
            'signature_max_height_px' => self::normalizeLabFirmasImagePx($s['signature_max_height_px'] ?? null, (int) $def['signature_max_height_px'], 30, 160),
            'signature_max_width_px'  => self::normalizeLabFirmasImagePx($s['signature_max_width_px'] ?? null, (int) $def['signature_max_width_px'], 80, 400),
        ]);
    }

    public static function normalizeLabFirmasPlacement($raw, string $fallback = 'per_group'): string
    {
        $v = strtolower(trim((string) ($raw ?? $fallback)));
        if (! in_array($v, self::LAB_FIRMAS_PLACEMENTS, true)) {
            $fb = strtolower(trim($fallback));

            return in_array($fb, self::LAB_FIRMAS_PLACEMENTS, true) ? $fb : 'per_group';
        }

        return $v;
    }

    /**
     * @param array<string, mixed> $lf normalizeLabFirmasStyle()
     */
    public static function labFirmasPlacementShowsPerGroup(array $lf): bool
    {
        $p = self::normalizeLabFirmasPlacement($lf['placement'] ?? 'per_group');

        return $p === 'per_group' || $p === 'both';
    }

    /**
     * @param array<string, mixed> $lf
     */
    public static function labFirmasPlacementShowsBlockEnd(array $lf): bool
    {
        $p = self::normalizeLabFirmasPlacement($lf['placement'] ?? 'per_group');

        return $p === 'block_end' || $p === 'both';
    }

    public static function normalizeLabFirmasAreaHeadingFontSize($raw, float $fallback): float
    {
        if (! is_numeric($raw)) {
            return max(7.0, min(16.0, $fallback));
        }

        return max(7.0, min(16.0, (float) $raw));
    }

    public static function normalizeLabFirmasMarginPt($raw, float $fallback): float
    {
        if (! is_numeric($raw)) {
            return max(0.0, min(24.0, $fallback));
        }

        return max(0.0, min(24.0, (float) $raw));
    }

    public static function normalizeLabFirmasImagePx($raw, int $fallback, int $min, int $max): int
    {
        if (! is_numeric($raw)) {
            return max($min, min($max, $fallback));
        }

        return max($min, min($max, (int) $raw));
    }

    /**
     * Bloque «Validación / firmas» activo en la plantilla.
     *
     * @param array<string, mixed> $layout
     */
    public static function isLabFirmasBlockEnabled(array $layout): bool
    {
        foreach (is_array($layout['blocks'] ?? null) ? $layout['blocks'] : [] as $block) {
            if (! empty($block['enabled']) && (string) ($block['id'] ?? '') === 'lab_firmas') {
                return true;
            }
        }

        return false;
    }

    /**
     * Grosor del borde entre columnas del bloque firmas (0 = sin borde).
     */
    public static function normalizeLabFirmasColumnBorderWidthPx($raw, int $fallback): int
    {
        if ($raw === null || $raw === '') {
            return max(0, min(4, $fallback));
        }
        if (! is_numeric($raw)) {
            return max(0, min(4, $fallback));
        }
        $w = (int) $raw;

        return max(0, min(4, $w));
    }

    public static function normalizeLabFirmasBorderColor($raw, string $fallback): string
    {
        $v = strtoupper(trim((string) ($raw ?? '')));
        if ($v !== '' && preg_match('/^#[0-9A-F]{6}$/', $v)) {
            return $v;
        }
        $fb = strtoupper(trim($fallback));

        return preg_match('/^#[0-9A-F]{6}$/', $fb) ? $fb : '#DDDDDD';
    }

    /**
     * Cuadrícula PDF: colores de celda, tipografía y bordes entre columnas.
     *
     * @param array<string, mixed> $raw
     * @param array<string, mixed> $def
     *
     * @return array<string, mixed>
     */
    public static function normalizeSectionGridWrap(array $raw, array $def): array
    {
        $s = $raw;
        $pickColor = static function (string $k, string $fallback) use ($s): string {
            $v = strtoupper(trim((string) ($s[$k] ?? $fallback)));

            return preg_match('/^#[0-9A-F]{6}$/', $v) ? $v : $fallback;
        };
        $family = (string) ($s['font_family'] ?? $def['font_family']);
        if (! in_array($family, self::ALLOWED_PDF_FONT_FAMILIES, true)) {
            $family = (string) $def['font_family'];
        }
        $size = isset($s['font_size_pt']) ? (float) $s['font_size_pt'] : (float) ($def['font_size_pt'] ?? 9.5);
        $size = round(max(7.0, min(20.0, $size)), 2);
        $weight = strtolower(trim((string) ($s['font_weight'] ?? $def['font_weight'])));
        if (! in_array($weight, self::ALLOWED_PDF_FONT_WEIGHTS, true)) {
            $weight = (string) $def['font_weight'];
        }
        $style = strtolower(trim((string) ($s['font_style'] ?? $def['font_style'])));
        if (! in_array($style, self::ALLOWED_PDF_FONT_STYLES, true)) {
            $style = (string) $def['font_style'];
        }
        $transform = strtolower(trim((string) ($s['text_transform'] ?? $def['text_transform'])));
        if (! in_array($transform, self::ALLOWED_PDF_TEXT_TRANSFORMS, true)) {
            $transform = (string) $def['text_transform'];
        }
        $lh = isset($s['line_height']) ? (float) $s['line_height'] : (float) ($def['line_height'] ?? 1.35);
        $lh = round(max(1.0, min(3.0, $lh)), 2);

        return [
            'body_bg_color'          => $pickColor('body_bg_color', (string) $def['body_bg_color']),
            'body_text_color'        => $pickColor('body_text_color', (string) $def['body_text_color']),
            'body_transparent'       => self::labFirmasBool($s, 'body_transparent', ! empty($def['body_transparent'])),
            'font_family'            => $family,
            'font_size_pt'           => $size,
            'font_weight'            => $weight,
            'font_style'             => $style,
            'text_transform'         => $transform,
            'line_height'            => $lh,
            'column_border_width_px' => self::normalizeLabFirmasColumnBorderWidthPx($s['column_border_width_px'] ?? null, (int) ($def['column_border_width_px'] ?? 0)),
            'column_border_color'    => self::normalizeLabFirmasBorderColor($s['column_border_color'] ?? null, (string) ($def['column_border_color'] ?? '#DDDDDD')),
        ];
    }

    /**
     * @param mixed $raw
     *
     * @return array<string, mixed>
     */
    public static function normalizeHeaderGridStyle($raw): array
    {
        $s    = is_array($raw) ? $raw : [];
        $base = self::normalizeSectionGridWrap($s, self::DEFAULT_SECTION_GRID_WRAP);
        $btc  = (string) $base['body_text_color'];
        $base['label_qr_hint'] = self::clipLabFirmasLabel($s['label_qr_hint'] ?? null, 'Escanee para ver sus resultados online');
        $base['show_label_qr_hint'] = self::labFirmasBool($s, 'show_label_qr_hint', true);
        $base['label_qr_hint_line_mode'] = self::labFirmasLineModeInline($s, 'label_qr_hint_line_mode') ? 'inline' : 'stacked';

        $gridFs  = (float) $base['font_size_pt'];
        $gridFw  = (string) $base['font_weight'];
        $gridFst = (string) $base['font_style'];
        $pickPieceFs = static function (string $key) use ($s, $gridFs): float {
            if (! array_key_exists($key, $s)) {
                return $gridFs;
            }
            $v = (float) $s[$key];

            return round(max(7.0, min(20.0, $v)), 2);
        };
        $pickPieceFw = static function (string $key) use ($s, $gridFw): string {
            $w = strtolower(trim((string) ($s[$key] ?? $gridFw)));

            return in_array($w, self::ALLOWED_PDF_FONT_WEIGHTS, true) ? $w : $gridFw;
        };
        $pickPieceFst = static function (string $key) use ($s, $gridFst): string {
            $st = strtolower(trim((string) ($s[$key] ?? $gridFst)));

            return in_array($st, self::ALLOWED_PDF_FONT_STYLES, true) ? $st : $gridFst;
        };
        $gridTt = (string) $base['text_transform'];
        $pickPieceTt = static function (string $key) use ($s, $gridTt): string {
            $tt = strtolower(trim((string) ($s[$key] ?? $gridTt)));

            return in_array($tt, self::ALLOWED_PDF_TEXT_TRANSFORMS, true) ? $tt : $gridTt;
        };

        $base['label_qr_hint_text_color']   = self::normalizeLabFirmasBorderColor($s['label_qr_hint_text_color'] ?? null, $btc);
        $base['label_qr_hint_font_size_pt'] = $pickPieceFs('label_qr_hint_font_size_pt');
        $base['label_qr_hint_font_weight']  = $pickPieceFw('label_qr_hint_font_weight');
        $base['label_qr_hint_font_style']   = $pickPieceFst('label_qr_hint_font_style');
        $base['label_qr_hint_text_transform'] = $pickPieceTt('label_qr_hint_text_transform');
        $qrPct = isset($s['qr_size_percent']) ? (int) $s['qr_size_percent'] : 100;
        $base['qr_size_percent'] = max(50, min(400, $qrPct));

        foreach (self::HEADER_GRID_LABEL_DEFAULTS as $id => $fallback) {
            $base['label_' . $id] = self::clipLabFirmasLabel($s['label_' . $id] ?? null, $fallback);
            $base['show_label_' . $id] = self::labFirmasBool($s, 'show_label_' . $id, true);
            $base['label_' . $id . '_line_mode'] = self::labFirmasLineModeInline($s, 'label_' . $id . '_line_mode') ? 'inline' : 'stacked';
            $base['label_' . $id . '_text_color'] = self::normalizeLabFirmasBorderColor($s['label_' . $id . '_text_color'] ?? null, $btc);
            $base['label_' . $id . '_font_size_pt'] = $pickPieceFs('label_' . $id . '_font_size_pt');
            $base['label_' . $id . '_font_weight']  = $pickPieceFw('label_' . $id . '_font_weight');
            $base['label_' . $id . '_font_style']   = $pickPieceFst('label_' . $id . '_font_style');
            $base['label_' . $id . '_text_transform'] = $pickPieceTt('label_' . $id . '_text_transform');
        }

        if (! array_key_exists('label_pdf_pagination_line_mode', $s)) {
            $base['label_pdf_pagination_line_mode'] = 'inline';
        }
        $base['label_pdf_pagination_line_mode'] = 'inline';

        return $base;
    }

    /**
     * Tamaño en píxeles del PNG del QR (50–500) a partir de la plantilla. Base de generación 100 px al 100 % (escala con qr_size_percent hasta 400 %).
     */
    public static function qrImagePixelSizeFromLayout(array $pdfLayout): int
    {
        $ps = is_array($pdfLayout['page_style'] ?? null) ? $pdfLayout['page_style'] : [];
        $hg = self::normalizeHeaderGridStyle($ps['header_grid'] ?? []);
        $pct = (int) ($hg['qr_size_percent'] ?? 100);

        return (int) max(50, min(500, (int) round(100 * $pct / 100)));
    }

    /**
     * @param mixed $raw
     *
     * @return array<string, mixed>
     */
    public static function normalizePatientDoctorGridStyle($raw): array
    {
        $s = is_array($raw) ? $raw : [];
        $wrapDef = array_merge(self::DEFAULT_SECTION_GRID_WRAP, ['body_bg_color' => '#F8F9FA']);
        $base    = self::normalizeSectionGridWrap($s, $wrapDef);
        $btc     = (string) $base['body_text_color'];
        $gridFs  = (float) $base['font_size_pt'];
        $gridFw  = (string) $base['font_weight'];
        $gridFst = (string) $base['font_style'];
        $gridTt  = (string) $base['text_transform'];
        $pickPieceFs = static function (string $key) use ($s, $gridFs): float {
            if (! array_key_exists($key, $s)) {
                return $gridFs;
            }
            $v = (float) $s[$key];

            return round(max(7.0, min(20.0, $v)), 2);
        };
        $pickPieceFw = static function (string $key) use ($s, $gridFw): string {
            $w = strtolower(trim((string) ($s[$key] ?? $gridFw)));

            return in_array($w, self::ALLOWED_PDF_FONT_WEIGHTS, true) ? $w : $gridFw;
        };
        $pickPieceFst = static function (string $key) use ($s, $gridFst): string {
            $st = strtolower(trim((string) ($s[$key] ?? $gridFst)));

            return in_array($st, self::ALLOWED_PDF_FONT_STYLES, true) ? $st : $gridFst;
        };
        $pickPieceTt = static function (string $key) use ($s, $gridTt): string {
            $tt = strtolower(trim((string) ($s[$key] ?? $gridTt)));

            return in_array($tt, self::ALLOWED_PDF_TEXT_TRANSFORMS, true) ? $tt : $gridTt;
        };
        foreach (self::PATIENT_DOCTOR_GRID_LABEL_DEFAULTS as $id => $fallback) {
            $base['label_' . $id] = self::clipLabFirmasLabel($s['label_' . $id] ?? null, $fallback);
            $base['show_label_' . $id] = self::labFirmasBool($s, 'show_label_' . $id, true);
            $base['label_' . $id . '_line_mode'] = self::labFirmasLineModeInline($s, 'label_' . $id . '_line_mode') ? 'inline' : 'stacked';
            $base['label_' . $id . '_text_color'] = self::normalizeLabFirmasBorderColor($s['label_' . $id . '_text_color'] ?? null, $btc);
            $base['label_' . $id . '_font_size_pt'] = $pickPieceFs('label_' . $id . '_font_size_pt');
            $base['label_' . $id . '_font_weight'] = $pickPieceFw('label_' . $id . '_font_weight');
            $base['label_' . $id . '_font_style'] = $pickPieceFst('label_' . $id . '_font_style');
            $base['label_' . $id . '_text_transform'] = $pickPieceTt('label_' . $id . '_text_transform');
            $gap = array_key_exists('label_' . $id . '_value_gap_px', $s) && is_numeric($s['label_' . $id . '_value_gap_px'])
                ? (int) $s['label_' . $id . '_value_gap_px'] : 0;
            $mt  = array_key_exists('label_' . $id . '_space_above_px', $s) && is_numeric($s['label_' . $id . '_space_above_px'])
                ? (int) $s['label_' . $id . '_space_above_px'] : 0;
            $mb  = array_key_exists('label_' . $id . '_space_below_px', $s) && is_numeric($s['label_' . $id . '_space_below_px'])
                ? (int) $s['label_' . $id . '_space_below_px'] : 0;
            $base['label_' . $id . '_value_gap_px']   = max(0, min(40, $gap));
            $base['label_' . $id . '_space_above_px'] = max(0, min(40, $mt));
            $base['label_' . $id . '_space_below_px'] = max(0, min(40, $mb));
        }

        return $base;
    }

    /**
     * @param mixed $raw
     *
     * @return array<string, mixed>
     */
    public static function normalizeFooterGridStyle($raw): array
    {
        $s    = is_array($raw) ? $raw : [];
        $base = self::normalizeSectionGridWrap($s, self::DEFAULT_SECTION_GRID_WRAP);
        $btc  = (string) $base['body_text_color'];
        $base['label_footer_generated'] = self::clipLabFirmasLabel($s['label_footer_generated'] ?? null, 'Resultados generados el');
        $base['show_label_footer_generated'] = self::labFirmasBool($s, 'show_label_footer_generated', true);
        $base['label_footer_generated_line_mode'] = self::labFirmasLineModeInline($s, 'label_footer_generated_line_mode') ? 'inline' : 'stacked';
        $base['footer_company_text_color']      = self::normalizeLabFirmasBorderColor($s['footer_company_text_color'] ?? null, $btc);
        $base['label_footer_generated_color']   = self::normalizeLabFirmasBorderColor($s['label_footer_generated_color'] ?? null, $btc);
        $base['label_footer_datetime_color']    = self::normalizeLabFirmasBorderColor($s['label_footer_datetime_color'] ?? null, $btc);
        $base['footer_policy_text_color']       = self::normalizeLabFirmasBorderColor($s['footer_policy_text_color'] ?? null, $btc);
        $base['section_top_border_enabled']      = self::labFirmasBool($s, 'section_top_border_enabled', true);
        $tw = isset($s['section_top_border_width_px']) ? (int) $s['section_top_border_width_px'] : 1;
        $base['section_top_border_width_px']     = max(0, min(6, $tw));
        $base['section_top_border_color']       = self::normalizeLabFirmasBorderColor($s['section_top_border_color'] ?? null, '#DDDDDD');

        $pickPieceFs = static function (string $key) use ($s): float {
            if (! array_key_exists($key, $s)) {
                return 8.0;
            }
            $v = (float) $s[$key];

            return round(max(7.0, min(20.0, $v)), 2);
        };
        $pickPieceFw = static function (string $key) use ($s): string {
            $w = strtolower(trim((string) ($s[$key] ?? 'normal')));

            return in_array($w, self::ALLOWED_PDF_FONT_WEIGHTS, true) ? $w : 'normal';
        };
        $pickPieceFst = static function (string $key) use ($s): string {
            $st = strtolower(trim((string) ($s[$key] ?? 'normal')));

            return in_array($st, self::ALLOWED_PDF_FONT_STYLES, true) ? $st : 'normal';
        };
        $gridTt = (string) $base['text_transform'];
        $pickPieceTt = static function (string $key) use ($s): string {
            $tt = strtolower(trim((string) ($s[$key] ?? 'none')));

            return in_array($tt, self::ALLOWED_PDF_TEXT_TRANSFORMS, true) ? $tt : 'none';
        };
        $base['footer_company_font_size_pt']            = $pickPieceFs('footer_company_font_size_pt');
        $base['footer_company_font_weight']             = $pickPieceFw('footer_company_font_weight');
        $base['footer_company_font_style']              = $pickPieceFst('footer_company_font_style');
        $base['footer_company_text_transform']          = $pickPieceTt('footer_company_text_transform');
        $base['label_footer_generated_font_size_pt']    = $pickPieceFs('label_footer_generated_font_size_pt');
        $base['label_footer_generated_font_weight']      = $pickPieceFw('label_footer_generated_font_weight');
        $base['label_footer_generated_font_style']       = $pickPieceFst('label_footer_generated_font_style');
        $base['label_footer_generated_text_transform']   = $pickPieceTt('label_footer_generated_text_transform');
        $base['label_footer_datetime_font_size_pt']      = $pickPieceFs('label_footer_datetime_font_size_pt');
        $base['label_footer_datetime_font_weight']        = $pickPieceFw('label_footer_datetime_font_weight');
        $base['label_footer_datetime_font_style']         = $pickPieceFst('label_footer_datetime_font_style');
        $base['label_footer_datetime_text_transform']     = $pickPieceTt('label_footer_datetime_text_transform');
        $base['footer_policy_font_size_pt']              = $pickPieceFs('footer_policy_font_size_pt');
        $base['footer_policy_font_weight']               = $pickPieceFw('footer_policy_font_weight');
        $base['footer_policy_font_style']                = $pickPieceFst('footer_policy_font_style');
        $base['footer_policy_text_transform']            = $pickPieceTt('footer_policy_text_transform');

        return $base;
    }

    /**
     * Paginación en impresión directa (navegador): posiciones separadas para etiqueta y valor.
     *
     * @param mixed $raw
     *
     * @return array{enabled: bool, label_text: string, label_position: string, value_position: string}
     */
    public static function normalizePrintPaginationStyle($raw): array
    {
        $s = is_array($raw) ? $raw : [];
        $allowedPos = ['top-left', 'top-center', 'top-right', 'bottom-left', 'bottom-center', 'bottom-right'];
        $lp = strtolower(trim((string) ($s['label_position'] ?? 'bottom-left')));
        if (! in_array($lp, $allowedPos, true)) {
            $lp = 'bottom-left';
        }
        $vp = strtolower(trim((string) ($s['value_position'] ?? 'bottom-right')));
        if (! in_array($vp, $allowedPos, true)) {
            $vp = 'bottom-right';
        }
        $txt = trim((string) ($s['label_text'] ?? 'Página'));
        if ($txt === '') {
            $txt = 'Página';
        }
        if (function_exists('mb_substr')) {
            $txt = mb_substr($txt, 0, 60, 'UTF-8');
        } else {
            $txt = substr($txt, 0, 60);
        }

        return [
            'enabled'        => self::labFirmasBool($s, 'enabled', false),
            'label_text'     => $txt,
            'label_position' => $lp,
            'value_position' => $vp,
        ];
    }

    /**
     * Cabecera fija en cada hoja: paciente (izquierda) y nº de orden (derecha).
     *
     * @param mixed $raw
     *
     * @return array{enabled: bool}
     */
    public static function normalizeOrderSheetHeaderStyle($raw): array
    {
        $s = is_array($raw) ? $raw : [];

        return [
            'enabled' => self::labFirmasBool($s, 'enabled', ! empty(self::DEFAULT_ORDER_SHEET_HEADER['enabled'])),
        ];
    }

    public static function isTenantOrderSheetHeaderGloballyEnabled(): bool
    {
        try {
            $val = model(AppConfigModel::class)->getValue('pdf_order_sheet_header_enabled');

            return trim((string) $val) === '1';
        } catch (\Throwable $e) {
            return false;
        }
    }

    /**
     * @param array<string, mixed> $layout
     */
    public static function isOrderSheetHeaderEnabledForLayout(array $layout): bool
    {
        if (self::isTenantOrderSheetHeaderGloballyEnabled()) {
            return true;
        }

        $ps = is_array($layout['page_style'] ?? null) ? $layout['page_style'] : self::defaultPageStyleStatic();

        return ! empty(self::normalizeOrderSheetHeaderStyle($ps['order_sheet_header'] ?? [])['enabled']);
    }

    /**
     * @return array{patient: string, order: string}
     */
    public static function buildOrderSheetHeaderDisplayLines(?object $paciente, ?object $registerInfo): array
    {
        helper('registro');

        $pacienteNombre = '';
        if (is_object($paciente)) {
            $pacienteNombre = trim(
                ($paciente->first_name ?? '') . ' '
                . ($paciente->last_name_fa ?? '') . ' '
                . ($paciente->last_name_mom ?? '')
            );
        }
        if ($pacienteNombre === '' && is_object($registerInfo)) {
            $pacienteNombre = trim(
                ($registerInfo->first_name ?? '') . ' '
                . ($registerInfo->last_name_fa ?? '') . ' '
                . ($registerInfo->last_name_mom ?? '')
            );
        }
        if ($pacienteNombre === '') {
            $pacienteNombre = '—';
        }

        $numeroOrden = registro_orden_display($registerInfo);
        if ($numeroOrden === '') {
            $numeroOrden = '—';
        }

        return [
            'patient' => 'Paciente: ' . $pacienteNombre,
            'order'   => 'No. Orden: ' . $numeroOrden,
        ];
    }

    /**
     * @param array<string, mixed> $layout
     */
    public static function isPdfFooterBlockEnabledForLayout(array $layout): bool
    {
        foreach (is_array($layout['blocks'] ?? null) ? $layout['blocks'] : [] as $fb) {
            if (! empty($fb['enabled']) && (string) ($fb['id'] ?? '') === 'footer') {
                return true;
            }
        }

        return false;
    }

    /**
     * Distancia desde el borde inferior de la hoja hasta la base de la banda Paciente / No. Orden (mm).
     *
     * @param array<string, mixed> $layout
     */
    public static function orderSheetHeaderBottomOffsetMm(array $layout): float
    {
        $mm = is_array($layout['margins_mm'] ?? null)
            ? $layout['margins_mm']
            : self::defaultMarginsMmStatic();
        $marginBottomMm = (float) ($mm['bottom'] ?? 15);
        $footerReserveMm  = self::isPdfFooterBlockEnabledForLayout($layout)
            ? self::estimatePdfFooterReserveMm($layout)
            : 0.0;

        return $marginBottomMm + $footerReserveMm + self::ORDER_SHEET_HEADER_GAP_ABOVE_FOOTER_MM;
    }

    /**
     * Espacio extra a descontar del área imprimible en hojas 2+ (impresión navegador)
     * para que el contenido no quede bajo la banda fija Paciente / No. Orden.
     */
    public static function orderSheetHeaderPaginationReserveMm(): float
    {
        return self::ORDER_SHEET_HEADER_HEIGHT_MM + 0.5;
    }

    /**
     * @param array<string, mixed> $layout
     */
    public static function orderSheetHeaderDompdfFixedStyleAttr(array $layout): string
    {
        $fmt    = static fn (float $v): string => rtrim(rtrim(number_format($v, 2, '.', ''), '0'), '.');
        $height = self::ORDER_SHEET_HEADER_HEIGHT_MM;
        $gap    = self::ORDER_SHEET_HEADER_GAP_ABOVE_FOOTER_MM;

        // Misma convención que el pie: bottom negativo respecto al borde inferior del área de contenido.
        if (self::isPdfFooterBlockEnabledForLayout($layout)) {
            $footerReserveMm = self::estimatePdfFooterReserveMm($layout);
            $bottomNegMm     = $footerReserveMm + $gap;

            return sprintf(
                'position:fixed;left:0;right:0;bottom:-%smm;min-height:%smm;z-index:3;margin:0;padding:0;background:#ffffff;box-sizing:border-box;width:100%%;',
                $fmt($bottomNegMm),
                $fmt($height)
            );
        }

        $mm             = is_array($layout['margins_mm'] ?? null)
            ? $layout['margins_mm']
            : self::defaultMarginsMmStatic();
        $marginBottomMm = (float) ($mm['bottom'] ?? 15);

        return sprintf(
            'position:fixed;left:0;right:0;bottom:%smm;min-height:%smm;z-index:3;margin:0;padding:0;background:#ffffff;box-sizing:border-box;width:100%%;',
            $fmt($marginBottomMm + $gap),
            $fmt($height)
        );
    }

    /**
     * Aplica la opción global del tenant (Config → Sistema) sobre el layout normalizado.
     *
     * @param array<string, mixed> $layout
     *
     * @return array<string, mixed>
     */
    public static function applyTenantOrderSheetHeaderOverride(array $layout): array
    {
        if (! self::isTenantOrderSheetHeaderGloballyEnabled()) {
            return $layout;
        }

        $pageStyle = is_array($layout['page_style'] ?? null)
            ? $layout['page_style']
            : self::defaultPageStyleStatic();
        $pageStyle['order_sheet_header'] = self::normalizeOrderSheetHeaderStyle(['enabled' => true]);
        $layout['page_style']            = $pageStyle;

        return $layout;
    }

    /**
     * @param mixed $raw
     *
     * @return array{mode: string, repeat_header_on_split: bool, compact_min_scale_percent: int, compact_cell_padding_px: int, compact_aggressive: bool, min_remaining_mm_to_force_break: float}
     */
    /**
     * Modo de paginación del LayoutEngine (plantilla PDF).
     *
     * @param mixed $raw
     */
    public static function normalizePaginationMode($raw): string
    {
        $mode = strtolower(trim((string) $raw));
        if (ReportPaginationMode::isValid($mode)) {
            return $mode;
        }

        return ReportPaginationMode::migrateFromLegacy($mode);
    }

    /**
     * Resuelve pagination_mode desde layout_json (migra modos legacy si falta).
     *
     * @param array<string, mixed> $layout
     */
    public static function resolvePaginationModeFromLayout(array $layout): string
    {
        $ps = is_array($layout['page_style'] ?? null) ? $layout['page_style'] : [];
        if (isset($ps['pagination_mode']) && trim((string) $ps['pagination_mode']) !== '') {
            return self::normalizePaginationMode($ps['pagination_mode']);
        }

        $gpb = is_array($ps['grupo_prueba_page_break'] ?? null) ? $ps['grupo_prueba_page_break'] : [];

        return self::normalizePaginationMode($gpb['mode'] ?? ReportPaginationMode::default());
    }

    /**
     * Tamaño de hoja global (/config): aplica a PDF y PRINT.
     *
     * @param array<string, mixed> $labConfig
     *
     * @return array{key: string, width_mm: float, height_mm: float, css_size: string}
     */
    public static function resolveGlobalPageSizeMm(array $labConfig): array
    {
        $paper = strtolower(trim((string) ($labConfig['print_paper_size'] ?? 'letter')));
        if (! in_array($paper, ['letter', 'a4', 'legal', 'custom'], true)) {
            $paper = 'letter';
        }

        $customW = max(50.0, min(999.0, (float) ($labConfig['print_paper_width_mm'] ?? 210)));
        $customH = max(50.0, min(999.0, (float) ($labConfig['print_paper_height_mm'] ?? 297)));

        return match ($paper) {
            'a4' => [
                'key' => 'a4',
                'width_mm' => 210.0,
                'height_mm' => 297.0,
                'css_size' => 'A4 portrait',
            ],
            'legal' => [
                'key' => 'legal',
                'width_mm' => 215.9,
                'height_mm' => 355.6,
                'css_size' => 'legal portrait',
            ],
            'custom' => [
                'key' => 'custom',
                'width_mm' => $customW,
                'height_mm' => $customH,
                'css_size' => $customW . 'mm ' . $customH . 'mm',
            ],
            default => [
                'key' => 'letter',
                'width_mm' => 215.9,
                'height_mm' => 279.4,
                'css_size' => 'letter portrait',
            ],
        };
    }

    public static function normalizeGrupoPruebaPageBreakStyle($raw): array
    {
        $def = self::DEFAULT_GRUPO_PRUEBA_PAGE_BREAK;
        $s   = is_array($raw) ? $raw : [];
        $mode = strtolower(trim((string) ($s['mode'] ?? $def['mode'])));
        if (! in_array($mode, self::GRUPO_PRUEBA_PAGE_BREAK_MODES, true)) {
            $mode = $def['mode'];
        }
        $scale = isset($s['compact_min_scale_percent']) ? (int) $s['compact_min_scale_percent'] : $def['compact_min_scale_percent'];
        $scale = max(75, min(100, $scale));
        $cellPad = isset($s['compact_cell_padding_px']) ? (int) $s['compact_cell_padding_px'] : (int) $def['compact_cell_padding_px'];
        $cellPad = max(0, min(20, $cellPad));
        $minRemMm = isset($s['min_remaining_mm_to_force_break']) ? (float) $s['min_remaining_mm_to_force_break'] : (float) $def['min_remaining_mm_to_force_break'];
        $minRemMm = round(max(0.0, min(120.0, $minRemMm)), 1);
        $repeatDefault = $mode !== 'flow' && ! empty($def['repeat_header_on_split']);

        return [
            'mode'                            => $mode,
            'repeat_header_on_split'          => self::labFirmasBool($s, 'repeat_header_on_split', $repeatDefault),
            'compact_min_scale_percent'       => $scale,
            'compact_cell_padding_px'         => $cellPad,
            'compact_aggressive'              => self::labFirmasBool($s, 'compact_aggressive', ! empty($def['compact_aggressive'])),
            'min_remaining_mm_to_force_break' => $minRemMm,
        ];
    }

    /**
     * Modos «grupo íntegro» puros: evitar corte del área completa (sin fallback por segmentos).
     *
     * @param array{mode?: string, min_remaining_mm_to_force_break?: float} $gpb
     */
    public static function grupoPruebaPageBreakUsesGrupoIntactCss(array $gpb): bool
    {
        $mode = (string) ($gpb['mode'] ?? 'flow');

        return $mode === 'keep_together' || $mode === 'keep_together_compact';
    }

    /**
     * «Grupo íntegro» puro: mover bloque entero (umbral 0). Si umbral &gt; 0, el JS usa fallback por segmentos.
     *
     * @param array{mode?: string, min_remaining_mm_to_force_break?: float} $gpb
     */
    public static function grupoPruebaPageBreakUsesPureGrupoIntact(array $gpb): bool
    {
        if (! self::grupoPruebaPageBreakUsesGrupoIntactCss($gpb)) {
            return false;
        }

        return ((float) ($gpb['min_remaining_mm_to_force_break'] ?? 0.0)) <= 0.0;
    }

    /**
     * Estilo inline en el contenedor .report-pdf-grupo-prueba (Dompdf no ejecuta JS).
     *
     * @param array<string, mixed> $layout
     */
    public static function grupoPruebaGrupoIntactStyleAttr(array $layout, bool $isFirstGrupo = true): string
    {
        // La paginación la resuelven las clases del motor PHP (split-segments-only, keep-on-page)
        // y las reglas CSS; el inline break-inside:avoid en el contenedor del área dejaba huecos
        // cuando los subgrupos internos debían seguir fluyendo.
        return '';
    }

    /**
     * Salto de hoja en el contenedor del área (impresión navegador, título + resultados juntos).
     *
     * @param array<string, mixed> $layout
     */
    public static function grupoPruebaBrowserPrintAreaStyleAttr(array $layout, bool $isFirstGrupo, string $variant = 'pdf'): string
    {
        if ($variant !== 'browser_print' || $isFirstGrupo || ! self::shouldRenderGrupoAreaPageLeader($layout, $isFirstGrupo)) {
            return '';
        }

        return 'page-break-before:always;break-before:page;';
    }

    /**
     * ¿Insertar líder de hoja antes del área (salto sin cortar el título)?
     *
     * @param array<string, mixed> $layout
     */
    public static function shouldRenderGrupoAreaPageLeader(array $layout, bool $isFirstGrupo): bool
    {
        if ($isFirstGrupo) {
            return false;
        }
        $ps  = is_array($layout['page_style'] ?? null) ? $layout['page_style'] : [];
        $gpb = self::normalizeGrupoPruebaPageBreakStyle($ps['grupo_prueba_page_break'] ?? []);

        return self::grupoPruebaPageBreakUsesGrupoIntactCss($gpb);
    }

    /**
     * ¿Insertar separador de salto entre áreas (2.º grupo en adelante)?
     * Aplica a modos íntegros y a keep_together_if_fits (+ auto_order).
     *
     * @param array<string, mixed> $layout
     */
    public static function shouldRenderGrupoInterPageBreak(array $layout, bool $isFirstGrupo): bool
    {
        if ($isFirstGrupo) {
            return false;
        }
        $ps  = is_array($layout['page_style'] ?? null) ? $layout['page_style'] : [];
        $gpb = self::normalizeGrupoPruebaPageBreakStyle($ps['grupo_prueba_page_break'] ?? []);

        return self::grupoPruebaPageBreakUsesGrupoIntactCss($gpb)
            || self::grupoPruebaPageBreakUsesIfFitsMode($gpb);
    }

    /**
     * Estilo inline del líder de hoja (.report-pdf-grupo-area-page-leader) — Dompdf + impresión.
     *
     * @param array<string, mixed> $layout
     */
    public static function grupoAreaPageLeaderStyleAttr(array $layout, bool $isFirstGrupo): string
    {
        if (! self::shouldRenderGrupoAreaPageLeader($layout, $isFirstGrupo)) {
            return '';
        }

        return 'page-break-before:always;break-before:page;height:0;margin:0;padding:0;border:0;line-height:0;font-size:0;overflow:hidden;';
    }

    /**
     * Salto de hoja al cerrar un área (Dompdf). break-after evita hojas en blanco vs break-before.
     */
    public static function grupoPruebaPdfAreaEndBreakStyleAttr(): string
    {
        return 'display:block;width:100%;height:0;min-height:0;margin:0;padding:0;border:0;'
            . 'line-height:0;font-size:0;overflow:hidden;clear:both;'
            . 'page-break-after:always;break-after:page;'
            . 'page-break-before:avoid;break-before:avoid;';
    }

    /**
     * Apertura de envoltorio tabla para nueva área en Dompdf (evita hojas en blanco de break-before en divs).
     */
    public static function grupoPruebaPdfAreaTableWrapOpenHtml(): string
    {
        return '<table class="report-pdf-grupo-page-table" style="width:100%;border-collapse:collapse;border-spacing:0;border:0;margin:0;padding:0;page-break-before:always;break-before:page;"><tbody><tr><td class="report-pdf-grupo-page-table-cell" style="border:0;margin:0;padding:0;vertical-align:top;">';
    }

    public static function grupoPruebaPdfAreaTableWrapCloseHtml(): string
    {
        return '</td></tr></tbody></table>';
    }

    /**
     * @deprecated Usar grupoPruebaPdfAreaTableWrapOpenHtml() para Dompdf.
     */
    public static function grupoPruebaPdfNewAreaStyleAttr(): string
    {
        return 'page-break-before:always;break-before:page;margin-top:0;padding-top:0;';
    }

    /**
     * Estilo inline del separador inter-área (.report-grupo-inter-page-break) para Dompdf.
     */
    public static function grupoInterPageBreakStyleAttr(): string
    {
        return 'display:block;width:100%;height:1px;min-height:1px;margin:0;padding:0;border:0;'
            . 'line-height:0;font-size:0;overflow:hidden;clear:both;'
            . 'page-break-before:always;break-before:page;'
            . 'page-break-after:avoid;break-after:avoid;';
    }

    /**
     * margin-top entre áreas (.report-pdf-grupo-prueba). Dompdf aplica mal reglas de hoja externa y var().
     *
     * @param array<string, mixed> $layout
     */
    public static function grupoPruebaGapMarginStyleAttr(array $layout, bool $isFirstGrupo): string
    {
        if ($isFirstGrupo) {
            return '';
        }
        $ps  = is_array($layout['page_style'] ?? null) ? $layout['page_style'] : [];
        $rs  = self::normalizeResultsTableStyle($ps['results_table'] ?? []);
        $gpb = self::normalizeGrupoPruebaPageBreakStyle($ps['grupo_prueba_page_break'] ?? []);
        $gap = max(0, min(80, (int) ($rs['grupo_prueba_gap_px'] ?? 10)));
        if (self::grupoPruebaPageBreakUsesGrupoIntactCss($gpb)) {
            if (self::grupoAreaSeparatorEnabled($layout)) {
                return '';
            }

            return 'padding-top:' . $gap . 'px;';
        }

        return 'margin-top:' . $gap . 'px;';
    }

    /**
     * @param array<string, mixed> $layout
     */
    /** Máx. filas de resultado en un subgrupo para mantenerlo íntegro en PDF (sin partir entre páginas). */
    public const SUBGRUPO_KEEP_INTACT_MAX_ROWS = 4;

    public static function subgrupoKeepIntactMaxRows(): int
    {
        return self::SUBGRUPO_KEEP_INTACT_MAX_ROWS;
    }

    /**
     * @param list<array{rows: int, has_title: bool, subgrupo_key: int, is_matrix: bool}> $units
     */
    public static function subgrupoShouldKeepIntact(array $units, int $subgrupoKey): bool
    {
        $total = 0;
        foreach ($units as $unit) {
            if ((int) ($unit['subgrupo_key'] ?? -1) !== $subgrupoKey) {
                continue;
            }
            $total += max(0, (int) ($unit['rows'] ?? 0));
        }

        return $total > 0 && $total <= self::SUBGRUPO_KEEP_INTACT_MAX_ROWS;
    }

    /**
     * Anchos de columna (%) para tablas de resultados; suman 100.
     *
     * @return list<int>
     */
    public static function resultsTableColumnWidthsPct(int $colCount): array
    {
        return match ($colCount) {
            4       => [34, 18, 28, 20],
            3       => [42, 25, 33],
            2       => [58, 42],
            default => [100],
        };
    }

    public static function resultsTableFixedLayoutAttrs(int $colCount): string
    {
        if ($colCount < 3) {
            return ' width="100%"';
        }

        return ' width="100%" style="table-layout:fixed;width:100%;"';
    }

    public static function resultsTableColgroupHtml(int $colCount): string
    {
        if ($colCount < 3) {
            return '';
        }
        $widths = self::resultsTableColumnWidthsPct($colCount);
        $html   = '<colgroup>';
        foreach ($widths as $w) {
            $html .= '<col width="' . (int) $w . '%" style="width:' . (int) $w . '%;">';
        }
        $html .= '</colgroup>';

        return $html;
    }

    public static function resultsTableThWidthStyleAttr(int $colIndex, int $colCount, bool $forPdf = true): string
    {
        if (! $forPdf || $colCount < 3) {
            return '';
        }
        $widths = self::resultsTableColumnWidthsPct($colCount);
        $w      = $widths[$colIndex] ?? null;
        if ($w === null) {
            return '';
        }

        return ' style="width:' . (int) $w . '%;"';
    }

    /**
     * Alineación horizontal de columnas de table.results (análisis, resultado, rango, interpretación).
     *
     * @param array<string, mixed> $layout
     */
    public static function resultsColumnTextAlignCss(array $layout, string $column, bool $isHeader): string
    {
        $allowedColumns = ['analisis', 'resultado', 'rango', 'interpretacion'];
        if (! in_array($column, $allowedColumns, true)) {
            return 'left';
        }
        $ps = is_array($layout['page_style'] ?? null) ? $layout['page_style'] : [];
        $rs = self::normalizeResultsTableStyle($ps['results_table'] ?? []);
        $key = ($isHeader ? 'results_hdr_' : 'results_col_') . $column . '_align';
        $default = (string) (self::DEFAULT_RESULTS_TABLE_STYLE[$key] ?? ($column === 'analisis' ? 'left' : 'center'));
        $align = strtolower(trim((string) ($rs[$key] ?? $default)));

        return in_array($align, self::ALLOWED_PDF_TEXT_ALIGNS, true) ? $align : $default;
    }

    /**
     * Clase de alineación para celdas de table.results (Dompdf respeta .text-center mejor que style text-align).
     *
     * @param array<string, mixed> $layout
     */
    public static function resultsColumnAlignClass(array $layout, string $column, bool $isHeader): string
    {
        return match (self::resultsColumnTextAlignCss($layout, $column, $isHeader)) {
            'center'  => 'text-center',
            'right'   => 'text-right',
            'left'    => 'text-left',
            default   => '',
        };
    }

    /**
     * Atributos align/style para celdas de table.results (ancho extra + justify).
     *
     * @param array<string, mixed> $layout
     */
    public static function resultsColumnCellMarkupAttrs(array $layout, string $column, bool $isHeader, string $extraCss = ''): string
    {
        $align = self::resultsColumnTextAlignCss($layout, $column, $isHeader);
        $css   = trim($extraCss);
        if ($align === 'justify') {
            $css = trim($css . ';text-align:justify', ';');
        }
        $out = $align !== 'justify' ? ' align="' . $align . '"' : '';
        if ($css !== '') {
            $out .= ' style="' . $css . '"';
        }

        return $out;
    }

    /**
     * @param array<string, mixed> $layout
     */
    public static function resultsColumnCellStyleAttr(array $layout, string $column, bool $isHeader, string $extraCss = ''): string
    {
        return self::resultsColumnCellMarkupAttrs($layout, $column, $isHeader, $extraCss);
    }

    public static function subgrupoPruebaGapPx(array $layout): int
    {
        $ps = is_array($layout['page_style'] ?? null) ? $layout['page_style'] : [];
        $rs = self::normalizeResultsTableStyle($ps['results_table'] ?? []);

        return max(0, min(80, (int) ($rs['subgrupo_prueba_gap_px'] ?? 18)));
    }

    /**
     * Separación entre pruebas dentro del mismo área (.report-pdf-subgrupo-block).
     * Dompdf suele ignorar margin-top; padding-top en inline es fiable.
     *
     * @param array<string, mixed> $layout
     */
    public static function subgrupoPruebaGapStyleAttr(array $layout, bool $needsGap): string
    {
        if (! $needsGap) {
            return '';
        }
        $gap = self::subgrupoPruebaGapPx($layout);
        if ($gap <= 0) {
            return '';
        }

        return 'padding-top:' . $gap . 'px;';
    }

    /**
     * Espaciado vertical del separador de análisis (.report-pdf-grupo-cabecera).
     *
     * @param array<string, mixed> $layout
     *
     * @return array{
     *   title_top: int, title_bottom: int,
     *   tipo_top: int, tipo_bottom: int,
     *   metodo_top: int, metodo_bottom: int
     * }
     */
    public static function grupoCabeceraSpacingFromLayout(array $layout): array
    {
        $ps = is_array($layout['page_style'] ?? null) ? $layout['page_style'] : [];
        $rs = self::normalizeResultsTableStyle($ps['results_table'] ?? []);
        $clamp = static fn (string $key, int $fallback): int => max(0, min(40, (int) ($rs[$key] ?? $fallback)));

        return [
            'title_top'    => $clamp('grupo_cabecera_title_margin_top_px', 0),
            'title_bottom' => $clamp('grupo_cabecera_title_margin_bottom_px', 6),
            'tipo_top'     => $clamp('grupo_cabecera_tipo_muestra_margin_top_px', 0),
            'tipo_bottom'  => $clamp('grupo_cabecera_tipo_muestra_margin_bottom_px', 10),
            'metodo_top'   => $clamp('grupo_cabecera_metodo_margin_top_px', 0),
            'metodo_bottom' => $clamp('grupo_cabecera_metodo_margin_bottom_px', 10),
        ];
    }

    /**
     * Márgenes de .group-title en cabecera de prueba.
     *
     * @param array<string, mixed> $layout
     */
    public static function groupTitleMarginStyleAttr(array $layout, bool $isFirstSubgrupoInArea, bool $isFirstGrupoInReport): string
    {
        $ps = is_array($layout['page_style'] ?? null) ? $layout['page_style'] : [];
        $rs = self::normalizeResultsTableStyle($ps['results_table'] ?? []);
        $spacing = self::grupoCabeceraSpacingFromLayout($layout);
        $marginTop = $spacing['title_top'];
        if ($marginTop === 0 && $isFirstSubgrupoInArea && $isFirstGrupoInReport && ! self::grupoAreaSeparatorEnabled($layout)) {
            $marginTop = max(0, min(80, (int) ($rs['grupo_prueba_gap_px'] ?? 10)));
        }

        return sprintf('margin-top:%dpx !important;margin-bottom:%dpx !important;', $marginTop, $spacing['title_bottom']);
    }

    /**
     * @param array<string, mixed> $layout
     */
    public static function grupoCabeceraTipoMuestraStyleAttr(array $layout): string
    {
        $spacing = self::grupoCabeceraSpacingFromLayout($layout);

        return sprintf(
            'font-size:9pt;color:#555;margin:%dpx 0 %dpx 0 !important;line-height:1.3;',
            $spacing['tipo_top'],
            $spacing['tipo_bottom']
        );
    }

    /**
     * @param array<string, mixed> $layout
     */
    public static function grupoCabeceraMetodoStyleAttr(array $layout): string
    {
        $spacing = self::grupoCabeceraSpacingFromLayout($layout);

        return sprintf(
            'font-size:9pt;color:#555;margin:%dpx 0 %dpx 0 !important;line-height:1.3;',
            $spacing['metodo_top'],
            $spacing['metodo_bottom']
        );
    }

    /**
     * Indica si algún ítem del reporte tiene rango referencial configurado.
     * Sirve para alinear columnas (ANÁLISIS / RESULTADO / RANGO) en todas las tablas del PDF.
     *
     * @param array<mixed, list<object|array<string, mixed>>> $grupos
     */
    public static function reportGruposTienenRangoReferencial(array $grupos): bool
    {
        helper('registro');
        foreach ($grupos as $items) {
            if (! is_array($items)) {
                continue;
            }
            foreach ($items as $raw) {
                $it = is_array($raw) ? (object) $raw : $raw;
                if (! is_object($it)) {
                    continue;
                }
                if (registro_tiene_rango_referencial($it->valor_min ?? '', $it->valor_max ?? '')) {
                    return true;
                }
            }
        }

        return false;
    }

    /**
     * Separador horizontal con nombre del área (.report-pdf-grupo-area-separator).
     *
     * @param array<string, mixed> $layout
     */
    public static function grupoAreaSeparatorEnabled(array $layout): bool
    {
        $ps = is_array($layout['page_style'] ?? null) ? $layout['page_style'] : [];
        $rs = self::normalizeResultsTableStyle($ps['results_table'] ?? []);

        return ! empty($rs['grupo_area_separator_enabled']);
    }

    /**
     * @param array<string, mixed> $layout
     */
    public static function grupoAreaSeparatorMarginStyleAttr(array $layout, string $variant = 'pdf'): string
    {
        $ps = is_array($layout['page_style'] ?? null) ? $layout['page_style'] : [];
        $rs = self::normalizeResultsTableStyle($ps['results_table'] ?? []);
        $marginTop    = max(0, min(80, (int) ($rs['grupo_area_separator_margin_top_px'] ?? 10)));
        $marginBottom = max(0, min(80, (int) ($rs['grupo_area_separator_margin_bottom_px'] ?? 10)));
        if ($variant === 'browser_print') {
            return sprintf('margin-bottom:%dpx;', $marginBottom);
        }

        return sprintf('margin-top:%dpx;margin-bottom:%dpx;', $marginTop, $marginBottom);
    }

    /**
     * @param array<string, mixed> $layout
     */
    public static function grupoAreaSeparatorInlineStyleAttr(array $layout): string
    {
        $ps = is_array($layout['page_style'] ?? null) ? $layout['page_style'] : [];
        $rs = self::normalizeResultsTableStyle($ps['results_table'] ?? []);
        $color = (string) ($rs['grupo_area_separator_color'] ?? '#DDDDDD');
        $width = max(0, min(4, (int) ($rs['grupo_area_separator_width_px'] ?? 1)));
        $fs    = (float) ($rs['grupo_area_separator_font_size_pt'] ?? 11.0);
        $fw    = (string) ($rs['grupo_area_separator_font_weight'] ?? 'bold');

        return 'color:#333333;font-size:' . $fs . 'pt;font-weight:' . $fw
            . ';--pdf-grupo-area-separator-color:' . $color
            . ';--pdf-grupo-area-separator-width:' . $width . 'px;';
    }

    /**
     * @param array<string, mixed> $layout
     */
    public static function buildGrupoAreaSeparatorTitle(string $padre, array $layout): string
    {
        return trim($padre);
    }

    /**
     * @param array<string, mixed> $layout
     */
    public static function footerDompdfFixedStyleAttr(array $layout): string
    {
        $ps = is_array($layout['page_style'] ?? null) ? $layout['page_style'] : [];
        $ft = self::normalizeFooterGridStyle($ps['footer_grid'] ?? []);
        $bg = ! empty($ft['body_transparent']) ? '#ffffff' : (string) $ft['body_bg_color'];

        $footerReserveMm = self::estimatePdfFooterReserveMm($layout);
        $fmt = static fn (float $v): string => rtrim(rtrim(number_format($v, 2, '.', ''), '0'), '.');

        // left/right:0 — @page ya aplica márgenes horizontales; repetir ml/mr desalinea el pie en Dompdf.
        return sprintf(
            'position:fixed;left:0;right:0;bottom:-%smm;min-height:%smm;z-index:2;margin:0;padding-top:6px;padding-bottom:0;background:%s;box-sizing:border-box;width:100%%;',
            $fmt($footerReserveMm),
            $fmt($footerReserveMm),
            $bg
        );
    }

    /**
     * Combina fragmentos de style="" para contenedores PDF (Dompdf).
     */
    public static function mergePdfInlineStyleAttrs(string ...$parts): string
    {
        $out = '';
        foreach ($parts as $part) {
            $part = trim($part);
            if ($part === '') {
                continue;
            }
            if ($out !== '' && ! str_ends_with($out, ';')) {
                $out .= ';';
            }
            $out .= $part;
        }

        return $out;
    }

    /**
     * ¿Modo «grupo íntegro con compactación»?
     *
     * @param array<string, mixed> $layout
     */
    public static function grupoPruebaUsesCompactMode(array $layout): bool
    {
        $ps  = is_array($layout['page_style'] ?? null) ? $layout['page_style'] : [];
        $gpb = self::normalizeGrupoPruebaPageBreakStyle($ps['grupo_prueba_page_break'] ?? []);

        return ($gpb['mode'] ?? '') === 'keep_together_compact';
    }

    /**
     * Clases y variables CSS de compactación para Dompdf (réplica de applyCompactToGrupo en JS).
     *
     * @param array<string, mixed> $layout
     *
     * @return array{class: string, style: string}
     */
    public static function grupoPruebaCompactPdfAttrs(array $layout, bool $applyCompact): array
    {
        if (! $applyCompact || ! self::grupoPruebaUsesCompactMode($layout)) {
            return ['class' => '', 'style' => ''];
        }

        $ps  = is_array($layout['page_style'] ?? null) ? $layout['page_style'] : [];
        $gpb = self::normalizeGrupoPruebaPageBreakStyle($ps['grupo_prueba_page_break'] ?? []);
        $scale = max(75, min(100, (int) ($gpb['compact_min_scale_percent'] ?? 85))) / 100;
        $classes = ['report-pdf-grupo-prueba-compact'];
        if (! empty($gpb['compact_aggressive'])) {
            $classes[] = 'report-pdf-grupo-prueba-compact-aggressive';
        }
        $styleParts = ['--pdf-gpb-compact-scale:' . rtrim(rtrim(number_format($scale, 3, '.', ''), '0'), '.')];
        $cellPad = max(0, min(20, (int) ($gpb['compact_cell_padding_px'] ?? 0)));
        if ($cellPad > 0) {
            $styleParts[] = '--pdf-gpb-compact-cell-padding-v:' . $cellPad . 'px';
        }

        return [
            'class' => implode(' ', $classes),
            'style' => implode(';', $styleParts) . ';',
        ];
    }

    /**
     * Estilo inline en .report-segment-table-wrap (Dompdf no ejecuta JS).
     *
     * @param array<string, mixed> $layout
     */
    public static function grupoPruebaPageBreakUsesIfFitsMode(array $gpb): bool
    {
        $mode = (string) ($gpb['mode'] ?? 'flow');

        return $mode === 'keep_together_if_fits' || $mode === 'keep_together_if_fits_auto_order';
    }

    public static function grupoPruebaSegmentIntactStyleAttr(array $layout): string
    {
        $ps  = is_array($layout['page_style'] ?? null) ? $layout['page_style'] : [];
        $gpb = self::normalizeGrupoPruebaPageBreakStyle($ps['grupo_prueba_page_break'] ?? []);
        $mode = (string) ($gpb['mode'] ?? 'flow');
        if ($mode !== 'keep_segment' && ! self::grupoPruebaPageBreakUsesIfFitsMode($gpb)) {
            return '';
        }

        return 'page-break-inside:avoid;break-inside:avoid-page;';
    }

    /**
     * @param array{mode?: string} $gpb
     */
    public static function grupoPruebaPageBreakUsesSegmentIntactCss(array $gpb): bool
    {
        $mode = (string) ($gpb['mode'] ?? 'flow');

        return $mode === 'keep_segment' || self::grupoPruebaPageBreakUsesIfFitsMode($gpb);
    }

    /**
     * break-inside para segmentos en @media print del navegador.
     *
     * @param array{mode?: string, min_remaining_mm_to_force_break?: float} $gpb
     */
    public static function grupoPruebaPrintSegmentBreakInside(array $gpb): string
    {
        if (self::grupoPruebaPageBreakUsesSegmentCss($gpb)) {
            return 'avoid';
        }
        if (self::grupoPruebaPageBreakUsesPureGrupoIntact($gpb)) {
            return 'avoid';
        }

        return 'auto';
    }

    /**
     * Modos que aplican reglas de segmento (PDF sin JS y fallback al rellenar la hoja).
     *
     * @param array{mode?: string, min_remaining_mm_to_force_break?: float} $gpb
     */
    public static function grupoPruebaPageBreakUsesSegmentCss(array $gpb): bool
    {
        $mode = (string) ($gpb['mode'] ?? 'flow');
        if ($mode === 'keep_segment' || self::grupoPruebaPageBreakUsesIfFitsMode($gpb)) {
            return true;
        }
        if ($mode === 'keep_together' || $mode === 'keep_together_compact') {
            return ((float) ($gpb['min_remaining_mm_to_force_break'] ?? 0.0)) > 0.0;
        }

        return false;
    }

    /**
     * Clases CSS para body según la configuración de saltos en .report-pdf-grupo-prueba.
     *
     * @param array<string, mixed> $layout
     */
    public static function grupoPruebaPageBreakBodyClass(array $layout): string
    {
        $mode = self::resolvePaginationModeFromLayout($layout);

        return 'pdf-layout-engine pdf-pagination-' . str_replace('_', '-', $mode);
    }

    /**
     * font-family sin comillas para atributos style="" (nombres con espacio no cortan el HTML).
     */
    public static function fontFamilyForInlineCssAttr(string $family): string
    {
        $fn = trim(str_replace(['"', '\\'], '', $family));

        return $fn !== '' ? $fn : 'DejaVu Sans';
    }

    /**
     * Tipografía de pieza de cuadrícula para style="" (misma convención que textStyleNormalizedToInlineCss).
     */
    public static function gridTypographyPieceInlineCss(
        string $color,
        float $fontSizePt,
        string $fontWeight,
        string $fontStyle,
        string $textTransform,
        string $fontFamily,
        float $lineHeight
    ): string {
        return 'color:' . $color
            . ';font-family:' . self::fontFamilyForInlineCssAttr($fontFamily)
            . ';font-size:' . (string) $fontSizePt . 'pt'
            . ';font-weight:' . $fontWeight
            . ';font-style:' . $fontStyle
            . ';text-transform:' . $textTransform
            . ';line-height:' . (string) $lineHeight;
    }

    /**
     * CSS inline para textos del pie (color/tipo) — evita que td.pdf-cell o estilos globales tapen variables en vista/PDF.
     *
     * @param array<string, mixed> $ft footer_grid normalizado o bruto
     */
    public static function footerGridPieceStyleAttr(array $ft, string $piece): string
    {
        $ft = self::normalizeFooterGridStyle($ft);
        $ff   = (string) $ft['font_family'];
        $lh   = (float) $ft['line_height'];
        $decl = static function (string $color, float $fs, string $fw, string $fst, string $tt) use ($ff, $lh): string {
            return self::gridTypographyPieceInlineCss($color, $fs, $fw, $fst, $tt, $ff, $lh);
        };
        switch ($piece) {
            case 'company':
                return $decl(
                    (string) $ft['footer_company_text_color'],
                    (float) $ft['footer_company_font_size_pt'],
                    (string) $ft['footer_company_font_weight'],
                    (string) $ft['footer_company_font_style'],
                    (string) $ft['footer_company_text_transform']
                );
            case 'label_generated':
                return $decl(
                    (string) $ft['label_footer_generated_color'],
                    (float) $ft['label_footer_generated_font_size_pt'],
                    (string) $ft['label_footer_generated_font_weight'],
                    (string) $ft['label_footer_generated_font_style'],
                    (string) $ft['label_footer_generated_text_transform']
                );
            case 'datetime':
                return $decl(
                    (string) $ft['label_footer_datetime_color'],
                    (float) $ft['label_footer_datetime_font_size_pt'],
                    (string) $ft['label_footer_datetime_font_weight'],
                    (string) $ft['label_footer_datetime_font_style'],
                    (string) $ft['label_footer_datetime_text_transform']
                );
            case 'policy':
                return $decl(
                    (string) $ft['footer_policy_text_color'],
                    (float) $ft['footer_policy_font_size_pt'],
                    (string) $ft['footer_policy_font_weight'],
                    (string) $ft['footer_policy_font_style'],
                    (string) $ft['footer_policy_text_transform']
                );
            default:
                return '';
        }
    }

    /**
     * CSS inline para etiquetas del encabezado (cuadrícula superior) y leyenda del QR.
     *
     * @param array<string, mixed> $hg header_grid normalizado o bruto
     * @param string               $piece id de campo (p. ej. lab_phone) o qr_hint
     */
    public static function headerGridLabelPieceStyleAttr(array $hg, string $piece): string
    {
        $hg = self::normalizeHeaderGridStyle($hg);
        $ff   = (string) $hg['font_family'];
        $lh   = (float) $hg['line_height'];
        $decl = static function (string $color, float $fs, string $fw, string $fst, string $tt) use ($ff, $lh): string {
            return self::gridTypographyPieceInlineCss($color, $fs, $fw, $fst, $tt, $ff, $lh);
        };
        if ($piece === 'qr_hint') {
            return $decl(
                (string) $hg['label_qr_hint_text_color'],
                (float) $hg['label_qr_hint_font_size_pt'],
                (string) $hg['label_qr_hint_font_weight'],
                (string) $hg['label_qr_hint_font_style'],
                (string) $hg['label_qr_hint_text_transform']
            );
        }
        if (! array_key_exists($piece, self::HEADER_GRID_LABEL_DEFAULTS)) {
            return '';
        }

        return $decl(
            (string) $hg['label_' . $piece . '_text_color'],
            (float) $hg['label_' . $piece . '_font_size_pt'],
            (string) $hg['label_' . $piece . '_font_weight'],
            (string) $hg['label_' . $piece . '_font_style'],
            (string) $hg['label_' . $piece . '_text_transform']
        );
    }

    /**
     * CSS inline para etiquetas de paciente/médico por campo.
     *
     * @param array<string, mixed> $pd patient_doctor_grid normalizado o bruto
     */
    public static function patientDoctorGridLabelStyleAttr(array $pd, string $piece): string
    {
        $pd = self::normalizePatientDoctorGridStyle($pd);
        if (! array_key_exists($piece, self::PATIENT_DOCTOR_GRID_LABEL_DEFAULTS)) {
            return '';
        }
        $ff = (string) $pd['font_family'];
        $lh = (float) $pd['line_height'];

        return self::gridTypographyPieceInlineCss(
            (string) $pd['label_' . $piece . '_text_color'],
            (float) $pd['label_' . $piece . '_font_size_pt'],
            (string) $pd['label_' . $piece . '_font_weight'],
            (string) $pd['label_' . $piece . '_font_style'],
            (string) $pd['label_' . $piece . '_text_transform'],
            $ff,
            $lh
        );
    }

    /**
     * @param mixed $raw
     */
    protected static function validateRawLabFirmasTextFields($raw): ?string
    {
        if (! is_array($raw)) {
            return null;
        }
        $keys = ['section_title', 'label_validator', 'label_seal', 'label_firma', 'label_approver', 'label_cargo', 'label_matricula'];
        foreach ($keys as $k) {
            if (! array_key_exists($k, $raw)) {
                continue;
            }
            $v = $raw[$k];
            if (is_array($v) || is_object($v)) {
                return 'Los textos del bloque de firmas deben ser cadenas.';
            }
            $t = trim((string) $v);
            $len = function_exists('mb_strlen') ? mb_strlen($t, 'UTF-8') : strlen($t);
            if ($len > self::LAB_FIRMAS_TEXT_MAX_LEN) {
                return 'Un texto del bloque de firmas supera los ' . self::LAB_FIRMAS_TEXT_MAX_LEN . ' caracteres.';
            }
        }

        return null;
    }

    /**
     * @param mixed $raw
     */
    protected static function validateRawLabFirmasPlacementStyle($raw): ?string
    {
        if (! is_array($raw)) {
            return null;
        }
        if (array_key_exists('placement', $raw)) {
            $p = strtolower(trim((string) $raw['placement']));
            if (! in_array($p, self::LAB_FIRMAS_PLACEMENTS, true)) {
                return 'Ubicación de firmas inválida (use: debajo de cada área, al final, o ambas).';
            }
        }
        foreach (['inline_margin_top_pt', 'inline_margin_bottom_pt'] as $k) {
            if (! array_key_exists($k, $raw) || ! is_numeric($raw[$k])) {
                continue;
            }
            $v = (float) $raw[$k];
            if ($v < 0 || $v > 24) {
                return 'Los márgenes de firmas por área deben estar entre 0 y 24 pt.';
            }
        }
        if (array_key_exists('area_heading_font_size_pt', $raw) && is_numeric($raw['area_heading_font_size_pt'])) {
            $fs = (float) $raw['area_heading_font_size_pt'];
            if ($fs < 7.0 || $fs > 16.0) {
                return 'El tamaño del título de área debe estar entre 7 y 16 pt.';
            }
        }
        if (isset($raw['area_heading_font_weight']) && ! in_array(strtolower(trim((string) $raw['area_heading_font_weight'])), self::ALLOWED_PDF_FONT_WEIGHTS, true)) {
            return 'Grosor de fuente no permitido en título de área.';
        }
        if (isset($raw['area_heading_text_transform']) && ! in_array(strtolower(trim((string) $raw['area_heading_text_transform'])), self::ALLOWED_PDF_TEXT_TRANSFORMS, true)) {
            return 'Transformación de texto no permitida en título de área.';
        }
        foreach (['seal_max_height_px', 'signature_max_height_px', 'signature_max_width_px'] as $ik) {
            if (! array_key_exists($ik, $raw) || ! is_numeric($raw[$ik])) {
                continue;
            }
            $px = (int) $raw[$ik];
            if ($px < 20 || $px > 400) {
                return 'Tamaño de imagen de sello/firma fuera de rango (20–400 px).';
            }
        }
        if (isset($raw['area_heading_color']) && ! self::isValidPdfHexColor((string) $raw['area_heading_color'])) {
            return 'Color del título de área inválido (#RRGGBB).';
        }

        return null;
    }

    /**
     * @param mixed $raw
     */
    protected static function validateRawLabFirmasColumnStyle($raw): ?string
    {
        if (! is_array($raw)) {
            return null;
        }
        if (array_key_exists('column_border_width_px', $raw)) {
            if (! is_numeric($raw['column_border_width_px'])) {
                return 'El grosor del borde en firmas del laboratorio debe ser numérico.';
            }
            $w = (int) $raw['column_border_width_px'];
            if ($w < 0 || $w > 4) {
                return 'El grosor del borde en firmas del laboratorio debe estar entre 0 y 4 px.';
            }
        }
        if (isset($raw['column_border_color']) && ! self::isValidPdfHexColor((string) $raw['column_border_color'])) {
            return 'Color de borde inválido en firmas del laboratorio (#RRGGBB).';
        }

        return null;
    }

    /**
     * @param mixed $raw
     *
     * @return array{header_bg_color: string, header_text_color: string, body_bg_color: string, body_transparent: bool, body_text_color: string, border_color: string, segment_bg_color: string, segment_transparent: bool, segment_border_color: string, segment_border_width_px: int, segment_shadow: string, font_family: string, font_size_pt: float, font_weight: string, font_style: string, text_transform: string, line_height: float, cell_padding_v_px: int, matrix_text_align: string, matrix_vertical_align: string, matrix_text_color: string, matrix_font_size_pt: float, matrix_font_weight: string, matrix_font_style: string, matrix_text_transform: string, matrix_header_text_color: string, matrix_header_font_family: string, matrix_header_font_size_pt: float, matrix_header_font_weight: string, matrix_header_font_style: string, matrix_header_text_transform: string, matrix_col_population_align: string, matrix_col_parameter_align: string, matrix_col_sex_align: string, matrix_col_reference_align: string, matrix_hdr_population_align: string, matrix_hdr_parameter_align: string, matrix_hdr_sex_align: string, matrix_hdr_reference_align: string}
     */
    public static function normalizeResultsTableStyle($raw): array
    {
        $def = self::DEFAULT_RESULTS_TABLE_STYLE;
        $s   = is_array($raw) ? $raw : [];
        $pickColor = static function (string $k, string $fallback) use ($s): string {
            $v = strtoupper(trim((string) ($s[$k] ?? $fallback)));
            return preg_match('/^#[0-9A-F]{6}$/', $v) ? $v : $fallback;
        };
        $family = (string) ($s['font_family'] ?? $def['font_family']);
        if (! in_array($family, self::ALLOWED_PDF_FONT_FAMILIES, true)) {
            $family = $def['font_family'];
        }
        $size = isset($s['font_size_pt']) ? (float) $s['font_size_pt'] : $def['font_size_pt'];
        $size = round(max(7.0, min(20.0, $size)), 2);
        $weight = strtolower(trim((string) ($s['font_weight'] ?? $def['font_weight'])));
        if (! in_array($weight, self::ALLOWED_PDF_FONT_WEIGHTS, true)) {
            $weight = $def['font_weight'];
        }
        $style = strtolower(trim((string) ($s['font_style'] ?? $def['font_style'])));
        if (! in_array($style, self::ALLOWED_PDF_FONT_STYLES, true)) {
            $style = $def['font_style'];
        }
        $transform = strtolower(trim((string) ($s['text_transform'] ?? $def['text_transform'])));
        if (! in_array($transform, self::ALLOWED_PDF_TEXT_TRANSFORMS, true)) {
            $transform = $def['text_transform'];
        }
        $lh = isset($s['line_height']) ? (float) $s['line_height'] : $def['line_height'];
        $lh = round(max(1.0, min(3.0, $lh)), 2);
        $cellPadV = isset($s['cell_padding_v_px']) ? (int) $s['cell_padding_v_px'] : (int) ($def['cell_padding_v_px'] ?? 6);
        $cellPadV = max(0, min(20, $cellPadV));
        $tableMt = isset($s['table_margin_top_px']) ? (int) $s['table_margin_top_px'] : (int) ($def['table_margin_top_px'] ?? 15);
        $tableMt = max(0, min(80, $tableMt));
        $tableMb = isset($s['table_margin_bottom_px']) ? (int) $s['table_margin_bottom_px'] : (int) ($def['table_margin_bottom_px'] ?? 15);
        $tableMb = max(0, min(80, $tableMb));
        $grupoGap = isset($s['grupo_prueba_gap_px']) ? (int) $s['grupo_prueba_gap_px'] : (int) ($def['grupo_prueba_gap_px'] ?? 10);
        $grupoGap = max(0, min(80, $grupoGap));
        $subgrupoGap = isset($s['subgrupo_prueba_gap_px']) ? (int) $s['subgrupo_prueba_gap_px'] : (int) ($def['subgrupo_prueba_gap_px'] ?? 18);
        $subgrupoGap = max(0, min(80, $subgrupoGap));
        $segBw = isset($s['segment_border_width_px']) ? (int) $s['segment_border_width_px'] : (int) $def['segment_border_width_px'];
        $segBw = max(0, min(4, $segBw));
        $segShadow = strtolower(trim((string) ($s['segment_shadow'] ?? $def['segment_shadow'])));
        if (! in_array($segShadow, self::ALLOWED_PDF_TEXT_SHADOWS, true)) {
            $segShadow = $def['segment_shadow'];
        }
        $segPadTop = isset($s['segment_padding_top_px']) ? (int) $s['segment_padding_top_px'] : (int) ($def['segment_padding_top_px'] ?? 6);
        $segPadTop = max(0, min(40, $segPadTop));
        $segPadBottom = isset($s['segment_padding_bottom_px']) ? (int) $s['segment_padding_bottom_px'] : (int) ($def['segment_padding_bottom_px'] ?? 6);
        $segPadBottom = max(0, min(40, $segPadBottom));
        $matrixAlign = strtolower(trim((string) ($s['matrix_text_align'] ?? $def['matrix_text_align'])));
        if (! in_array($matrixAlign, self::ALLOWED_PDF_TEXT_ALIGNS, true)) {
            $matrixAlign = $def['matrix_text_align'];
        }
        $matrixVAlign = strtolower(trim((string) ($s['matrix_vertical_align'] ?? $def['matrix_vertical_align'])));
        if (! in_array($matrixVAlign, self::ALLOWED_PDF_VERTICAL_ALIGNS, true)) {
            $matrixVAlign = $def['matrix_vertical_align'];
        }
        $matrixFs = isset($s['matrix_font_size_pt']) ? (float) $s['matrix_font_size_pt'] : (float) $def['matrix_font_size_pt'];
        $matrixFs = round(max(7.0, min(20.0, $matrixFs)), 2);
        $matrixFw = strtolower(trim((string) ($s['matrix_font_weight'] ?? $def['matrix_font_weight'])));
        if (! in_array($matrixFw, self::ALLOWED_PDF_FONT_WEIGHTS, true)) {
            $matrixFw = $def['matrix_font_weight'];
        }
        $matrixFst = strtolower(trim((string) ($s['matrix_font_style'] ?? $def['matrix_font_style'])));
        if (! in_array($matrixFst, self::ALLOWED_PDF_FONT_STYLES, true)) {
            $matrixFst = $def['matrix_font_style'];
        }
        $matrixTt = strtolower(trim((string) ($s['matrix_text_transform'] ?? $def['matrix_text_transform'])));
        if (! in_array($matrixTt, self::ALLOWED_PDF_TEXT_TRANSFORMS, true)) {
            $matrixTt = $def['matrix_text_transform'];
        }
        $mhFamily = (string) ($s['matrix_header_font_family'] ?? $def['matrix_header_font_family']);
        if (! in_array($mhFamily, self::ALLOWED_PDF_FONT_FAMILIES, true)) {
            $mhFamily = (string) $def['matrix_header_font_family'];
        }
        $mhSize = isset($s['matrix_header_font_size_pt']) ? (float) $s['matrix_header_font_size_pt'] : (float) $def['matrix_header_font_size_pt'];
        $mhSize = round(max(7.0, min(20.0, $mhSize)), 2);
        $mhWeight = strtolower(trim((string) ($s['matrix_header_font_weight'] ?? $def['matrix_header_font_weight'])));
        if (! in_array($mhWeight, self::ALLOWED_PDF_FONT_WEIGHTS, true)) {
            $mhWeight = (string) $def['matrix_header_font_weight'];
        }
        $mhStyle = strtolower(trim((string) ($s['matrix_header_font_style'] ?? $def['matrix_header_font_style'])));
        if (! in_array($mhStyle, self::ALLOWED_PDF_FONT_STYLES, true)) {
            $mhStyle = (string) $def['matrix_header_font_style'];
        }
        $mhTransform = strtolower(trim((string) ($s['matrix_header_text_transform'] ?? $def['matrix_header_text_transform'])));
        if (! in_array($mhTransform, self::ALLOWED_PDF_TEXT_TRANSFORMS, true)) {
            $mhTransform = (string) $def['matrix_header_text_transform'];
        }
        $pickColAlign = static function (string $key) use ($s, $def): string {
            $v = strtolower(trim((string) ($s[$key] ?? ($def[$key] ?? 'center'))));
            return in_array($v, self::ALLOWED_PDF_TEXT_ALIGNS, true) ? $v : (string) ($def[$key] ?? 'center');
        };

        return [
            'header_bg_color'   => $pickColor('header_bg_color', $def['header_bg_color']),
            'header_text_color' => $pickColor('header_text_color', $def['header_text_color']),
            'body_bg_color'     => $pickColor('body_bg_color', $def['body_bg_color']),
            'body_transparent'  => ! empty($s['body_transparent']),
            'body_text_color'   => $pickColor('body_text_color', $def['body_text_color']),
            'border_color'      => $pickColor('border_color', $def['border_color']),
            'segment_bg_color'  => $pickColor('segment_bg_color', $def['segment_bg_color']),
            'segment_transparent' => ! empty($s['segment_transparent']),
            'segment_border_color' => $pickColor('segment_border_color', $def['segment_border_color']),
            'segment_border_width_px' => $segBw,
            'segment_shadow'    => $segShadow,
            'segment_padding_top_px'    => $segPadTop,
            'segment_padding_bottom_px' => $segPadBottom,
            'font_family'       => $family,
            'font_size_pt'      => $size,
            'font_weight'       => $weight,
            'font_style'        => $style,
            'text_transform'    => $transform,
            'line_height'       => $lh,
            'cell_padding_v_px' => $cellPadV,
            'table_margin_top_px' => $tableMt,
            'table_margin_bottom_px' => $tableMb,
            'grupo_prueba_gap_px' => $grupoGap,
            'subgrupo_prueba_gap_px' => $subgrupoGap,
            'matrix_text_align' => $matrixAlign,
            'matrix_vertical_align' => $matrixVAlign,
            'matrix_text_color' => $pickColor('matrix_text_color', $def['matrix_text_color']),
            'matrix_font_size_pt' => $matrixFs,
            'matrix_font_weight' => $matrixFw,
            'matrix_font_style' => $matrixFst,
            'matrix_text_transform' => $matrixTt,
            'matrix_header_text_color' => $pickColor('matrix_header_text_color', $def['matrix_header_text_color']),
            'matrix_header_font_family' => $mhFamily,
            'matrix_header_font_size_pt' => $mhSize,
            'matrix_header_font_weight' => $mhWeight,
            'matrix_header_font_style' => $mhStyle,
            'matrix_header_text_transform' => $mhTransform,
            'matrix_col_population_align' => $pickColAlign('matrix_col_population_align'),
            'matrix_col_parameter_align' => $pickColAlign('matrix_col_parameter_align'),
            'matrix_col_sex_align' => $pickColAlign('matrix_col_sex_align'),
            'matrix_col_reference_align' => $pickColAlign('matrix_col_reference_align'),
            'matrix_hdr_population_align' => $pickColAlign('matrix_hdr_population_align'),
            'matrix_hdr_parameter_align' => $pickColAlign('matrix_hdr_parameter_align'),
            'matrix_hdr_sex_align' => $pickColAlign('matrix_hdr_sex_align'),
            'matrix_hdr_reference_align' => $pickColAlign('matrix_hdr_reference_align'),
            'results_hdr_analisis_align' => $pickColAlign('results_hdr_analisis_align'),
            'results_col_analisis_align' => $pickColAlign('results_col_analisis_align'),
            'results_hdr_resultado_align' => $pickColAlign('results_hdr_resultado_align'),
            'results_col_resultado_align' => $pickColAlign('results_col_resultado_align'),
            'results_hdr_rango_align' => $pickColAlign('results_hdr_rango_align'),
            'results_col_rango_align' => $pickColAlign('results_col_rango_align'),
            'results_hdr_interpretacion_align' => $pickColAlign('results_hdr_interpretacion_align'),
            'results_col_interpretacion_align' => $pickColAlign('results_col_interpretacion_align'),
            'grupo_cabecera_title_mode'        => self::normalizeGrupoCabeceraTitleMode($s['grupo_cabecera_title_mode'] ?? $def['grupo_cabecera_title_mode']),
            'grupo_cabecera_show_tipo_muestra' => array_key_exists('grupo_cabecera_show_tipo_muestra', $s)
                ? ! empty($s['grupo_cabecera_show_tipo_muestra'])
                : (bool) $def['grupo_cabecera_show_tipo_muestra'],
            'grupo_cabecera_show_metodo'       => array_key_exists('grupo_cabecera_show_metodo', $s)
                ? ! empty($s['grupo_cabecera_show_metodo'])
                : (bool) $def['grupo_cabecera_show_metodo'],
            'grupo_cabecera_title_margin_top_px'    => max(0, min(40, isset($s['grupo_cabecera_title_margin_top_px']) ? (int) $s['grupo_cabecera_title_margin_top_px'] : (int) ($def['grupo_cabecera_title_margin_top_px'] ?? 0))),
            'grupo_cabecera_title_margin_bottom_px' => max(0, min(40, isset($s['grupo_cabecera_title_margin_bottom_px']) ? (int) $s['grupo_cabecera_title_margin_bottom_px'] : (int) ($def['grupo_cabecera_title_margin_bottom_px'] ?? 6))),
            'grupo_cabecera_tipo_muestra_margin_top_px'    => max(0, min(40, isset($s['grupo_cabecera_tipo_muestra_margin_top_px']) ? (int) $s['grupo_cabecera_tipo_muestra_margin_top_px'] : (int) ($def['grupo_cabecera_tipo_muestra_margin_top_px'] ?? 0))),
            'grupo_cabecera_tipo_muestra_margin_bottom_px' => max(0, min(40, isset($s['grupo_cabecera_tipo_muestra_margin_bottom_px']) ? (int) $s['grupo_cabecera_tipo_muestra_margin_bottom_px'] : (int) ($def['grupo_cabecera_tipo_muestra_margin_bottom_px'] ?? 10))),
            'grupo_cabecera_metodo_margin_top_px'    => max(0, min(40, isset($s['grupo_cabecera_metodo_margin_top_px']) ? (int) $s['grupo_cabecera_metodo_margin_top_px'] : (int) ($def['grupo_cabecera_metodo_margin_top_px'] ?? 0))),
            'grupo_cabecera_metodo_margin_bottom_px' => max(0, min(40, isset($s['grupo_cabecera_metodo_margin_bottom_px']) ? (int) $s['grupo_cabecera_metodo_margin_bottom_px'] : (int) ($def['grupo_cabecera_metodo_margin_bottom_px'] ?? 10))),
            'grupo_area_separator_enabled'     => array_key_exists('grupo_area_separator_enabled', $s)
                ? ! empty($s['grupo_area_separator_enabled'])
                : (bool) ($def['grupo_area_separator_enabled'] ?? false),
            'grupo_area_separator_color'       => $pickColor('grupo_area_separator_color', (string) ($def['grupo_area_separator_color'] ?? '#DDDDDD')),
            'grupo_area_separator_width_px'    => max(0, min(4, isset($s['grupo_area_separator_width_px']) ? (int) $s['grupo_area_separator_width_px'] : (int) ($def['grupo_area_separator_width_px'] ?? 1))),
            'grupo_area_separator_font_size_pt' => round(max(7.0, min(20.0, isset($s['grupo_area_separator_font_size_pt']) ? (float) $s['grupo_area_separator_font_size_pt'] : (float) ($def['grupo_area_separator_font_size_pt'] ?? 11.0))), 2),
            'grupo_area_separator_font_weight' => (static function () use ($s, $def): string {
                $w = strtolower(trim((string) ($s['grupo_area_separator_font_weight'] ?? ($def['grupo_area_separator_font_weight'] ?? 'bold'))));
                return in_array($w, self::ALLOWED_PDF_FONT_WEIGHTS, true) ? $w : 'bold';
            })(),
            'grupo_area_separator_margin_top_px'    => max(0, min(80, isset($s['grupo_area_separator_margin_top_px']) ? (int) $s['grupo_area_separator_margin_top_px'] : (int) ($def['grupo_area_separator_margin_top_px'] ?? 10))),
            'grupo_area_separator_margin_bottom_px' => max(0, min(80, isset($s['grupo_area_separator_margin_bottom_px']) ? (int) $s['grupo_area_separator_margin_bottom_px'] : (int) ($def['grupo_area_separator_margin_bottom_px'] ?? 10))),
        ];
    }

    /**
     * @param mixed $raw
     */
    public static function normalizeGrupoCabeceraTitleMode($raw): string
    {
        $mode = strtolower(trim((string) $raw));

        return in_array($mode, self::ALLOWED_GRUPO_CABECERA_TITLE_MODES, true)
            ? $mode
            : self::DEFAULT_RESULTS_TABLE_STYLE['grupo_cabecera_title_mode'];
    }

    /**
     * @param array<string, mixed> $layout
     *
     * @return array{title_mode: string, show_tipo_muestra: bool, show_metodo: bool, area_separator_enabled: bool}
     */
    public static function grupoCabeceraDisplayFromLayout(array $layout): array
    {
        $ps = is_array($layout['page_style'] ?? null) ? $layout['page_style'] : [];
        $rs = self::normalizeResultsTableStyle($ps['results_table'] ?? []);

        return [
            'title_mode'             => (string) ($rs['grupo_cabecera_title_mode'] ?? 'grupo_analisis'),
            'show_tipo_muestra'      => ! empty($rs['grupo_cabecera_show_tipo_muestra']),
            'show_metodo'            => ! empty($rs['grupo_cabecera_show_metodo']),
            'area_separator_enabled' => ! empty($rs['grupo_area_separator_enabled']),
        ];
    }

    /**
     * @param array<string, mixed> $layout
     */
    public static function buildGrupoPruebaCabeceraTitle(string $padre, string $hijo, array $layout): string
    {
        $padre = trim($padre);
        $hijo  = trim($hijo);
        $cfg   = self::grupoCabeceraDisplayFromLayout($layout);

        if (! empty($cfg['area_separator_enabled'])) {
            if ($hijo !== '') {
                return $hijo;
            }

            return '';
        }

        if (($cfg['title_mode'] ?? 'grupo_analisis') === 'solo_analisis') {
            if ($hijo !== '') {
                return $hijo;
            }

            return $padre;
        }

        if ($padre !== '' && $hijo !== '') {
            return $padre . ' - ' . $hijo;
        }

        return $padre !== '' ? $padre : $hijo;
    }

    /**
     * @param array<string, mixed> $layout
     */
    public static function grupoCabeceraMostrarTipoMuestra(array $layout, string $linea): bool
    {
        $cfg = self::grupoCabeceraDisplayFromLayout($layout);

        return ! empty($cfg['show_tipo_muestra']) && trim($linea) !== '';
    }

    /**
     * @param array<string, mixed> $layout
     */
    public static function grupoCabeceraMostrarMetodo(array $layout, string $linea): bool
    {
        $cfg = self::grupoCabeceraDisplayFromLayout($layout);

        return ! empty($cfg['show_metodo']) && trim($linea) !== '';
    }

    /**
     * @param array<string, mixed> $layout
     */
    public static function grupoCabeceraOcultarTheadResultsTabla(array $layout): bool
    {
        $cfg = self::grupoCabeceraDisplayFromLayout($layout);

        return ($cfg['title_mode'] ?? '') === 'grupo_analisis_sin_cabecera_tabla';
    }

    /**
     * @param mixed $raw
     *
     * @return array{separator_color: string}
     */
    public static function normalizeHeaderSectionStyle($raw): array
    {
        $def = self::DEFAULT_HEADER_SECTION_STYLE;
        $s   = is_array($raw) ? $raw : [];
        $c   = strtoupper(trim((string) ($s['separator_color'] ?? $def['separator_color'])));
        if (! preg_match('/^#[0-9A-F]{6}$/', $c)) {
            $c = $def['separator_color'];
        }

        return [
            'separator_color' => $c,
        ];
    }

    /**
     * @return array<string, string>
     */
    public static function patientDoctorFieldLabels(): array
    {
        return [
            'paciente_nombre'   => 'Nombre del paciente',
            'paciente_genero'   => 'Género del paciente',
            'paciente_edad'     => 'Edad',
            'paciente_telefono' => 'Teléfono',
            'diagnostico_presuntivo' => 'Diagnóstico presuntivo',
            'medico'             => 'Médico tratante',
            'fecha_recepcion'    => 'Fecha de recepción',
            'fecha_reporte'      => 'Fecha de reporte',
            'numero_orden'       => 'Número de orden',
        ];
    }

    /**
     * @return list<array{id: string, enabled: bool}>
     */
    public static function defaultPatientDoctorFieldsStatic(): array
    {
        $out = [];
        foreach (self::PATIENT_DOCTOR_FIELD_ORDER as $id) {
            $out[] = [
                'id'      => $id,
                'enabled' => true,
                'column'  => self::defaultColumnForPatientField($id),
            ];
        }

        return $out;
    }

    /**
     * @return list<array{id: string, enabled: bool}>
     */
    public function getDefaultPatientDoctorFields(): array
    {
        return self::defaultPatientDoctorFieldsStatic();
    }

    /**
     * @return array<string, string>
     */
    public static function blockLabels(): array
    {
        return [
            'header'         => 'Encabezado (logo, datos del laboratorio y código QR)',
            'patient_doctor' => 'Datos del paciente y del médico',
            'results'        => 'Tablas de resultados por prueba',
            'notes'          => 'Notas del resultado (si existen)',
            'lab_firmas'     => 'Validación y aprobación (firmas)',
            'footer'         => 'Pie de página (fecha de generación y políticas)',
        ];
    }

    /**
     * @return array{version: int, blocks: list<array{id: string, enabled: bool}>, section_layouts: array, instances: list<array{uid: string, element_type: string, section: string, enabled: bool, column: int}>, margins_mm: array}
     */
    public function getDefaultLayout(): array
    {
        $blocks = [];
        foreach (self::DEFAULT_BLOCK_ORDER as $id) {
            $blocks[] = ['id' => $id, 'enabled' => true];
        }

        return [
            'version'          => 7,
            'blocks'           => $blocks,
            'section_layouts'  => self::defaultSectionLayoutsStatic(),
            'instances'        => self::defaultInstancesStatic(),
            'margins_mm'       => self::defaultMarginsMmStatic(),
            'watermark'        => self::defaultWatermarkStatic(),
            'page_style'       => self::defaultPageStyleStatic(),
        ];
    }

    /**
     * @return list<array{id: string, enabled: bool, column: int}>
     */
    protected function normalizePatientDoctorFields(?array $decoded): array
    {
        $allowed = self::PATIENT_DOCTOR_FIELD_ORDER;
        $seen    = [];
        $fields  = [];
        $raw     = is_array($decoded) && isset($decoded['patient_doctor_fields']) && is_array($decoded['patient_doctor_fields'])
            ? $decoded['patient_doctor_fields']
            : [];
        foreach ($raw as $f) {
            if (! is_array($f)) {
                continue;
            }
            $id = (string) ($f['id'] ?? '');
            if ($id === 'fecha_ingreso') {
                $enabled = array_key_exists('enabled', $f) ? ! empty($f['enabled']) : true;
                $colRaw  = isset($f['column']) ? (int) $f['column'] : self::defaultColumnForPatientField('fecha_recepcion');
                if ($colRaw < 0) {
                    $enabled = false;
                }
                $col = $enabled ? max(0, min(1, $colRaw)) : self::defaultColumnForPatientField('fecha_recepcion');
                foreach (['fecha_recepcion', 'fecha_reporte'] as $subId) {
                    if (in_array($subId, $seen, true)) {
                        continue;
                    }
                    $seen[] = $subId;
                    $fields[] = [
                        'id'      => $subId,
                        'enabled' => $enabled,
                        'column'  => $col,
                    ];
                }

                continue;
            }
            if ($id === 'paciente_nombre') {
                $enabled = array_key_exists('enabled', $f) ? ! empty($f['enabled']) : true;
                $colRaw  = isset($f['column']) ? (int) $f['column'] : self::defaultColumnForPatientField('paciente_nombre');
                if ($colRaw < 0) {
                    $enabled = false;
                }
                $col = $enabled ? max(0, min(1, $colRaw)) : self::defaultColumnForPatientField('paciente_nombre');
                foreach (['paciente_nombre', 'paciente_genero'] as $subId) {
                    if (in_array($subId, $seen, true)) {
                        continue;
                    }
                    $seen[] = $subId;
                    $fields[] = [
                        'id'      => $subId,
                        'enabled' => $enabled,
                        'column'  => $col,
                    ];
                }

                continue;
            }
            if ($id === '' || ! in_array($id, $allowed, true) || in_array($id, $seen, true)) {
                continue;
            }
            $seen[] = $id;
            $enabled = array_key_exists('enabled', $f) ? ! empty($f['enabled']) : true;
            $colRaw  = isset($f['column']) ? (int) $f['column'] : self::defaultColumnForPatientField($id);
            if ($colRaw < 0) {
                $enabled = false;
            }
            $col = $enabled ? max(0, min(1, $colRaw)) : self::defaultColumnForPatientField($id);
            $fields[] = [
                'id'      => $id,
                'enabled' => $enabled,
                'column'  => $col,
            ];
        }
        foreach ($allowed as $aid) {
            if (! in_array($aid, $seen, true)) {
                $fields[] = [
                    'id'      => $aid,
                    'enabled' => true,
                    'column'  => self::defaultColumnForPatientField($aid),
                ];
            }
        }

        return $fields;
    }

    /**
     * @return list<array{id: string, enabled: bool, column: int}>
     */
    protected function normalizeHeaderFields(?array $decoded): array
    {
        $allowed = self::HEADER_FIELD_ORDER;
        $seen    = [];
        $fields  = [];
        $raw     = is_array($decoded) && isset($decoded['header_fields']) && is_array($decoded['header_fields'])
            ? $decoded['header_fields']
            : [];
        foreach ($raw as $f) {
            if (! is_array($f)) {
                continue;
            }
            $id = (string) ($f['id'] ?? '');
            if ($id === '' || ! in_array($id, $allowed, true) || in_array($id, $seen, true)) {
                continue;
            }
            $seen[] = $id;
            $enabled = array_key_exists('enabled', $f) ? ! empty($f['enabled']) : true;
            $colRaw  = isset($f['column']) ? (int) $f['column'] : self::defaultColumnForHeaderField($id);
            if ($colRaw < 0) {
                $enabled = false;
            }
            $col = $enabled ? max(0, min(2, $colRaw)) : self::defaultColumnForHeaderField($id);
            $fields[] = [
                'id'      => $id,
                'enabled' => $enabled,
                'column'  => $col,
            ];
        }
        foreach ($allowed as $aid) {
            if (! in_array($aid, $seen, true)) {
                $fields[] = [
                    'id'      => $aid,
                    'enabled' => true,
                    'column'  => self::defaultColumnForHeaderField($aid),
                ];
            }
        }

        return $fields;
    }

    /**
     * @return array{version: int, blocks: list<array{id: string, enabled: bool}>, section_layouts: array, instances: list<array{uid: string, element_type: string, section: string, enabled: bool, column: int}>, margins_mm: array{top: float, right: float, bottom: float, left: float}}
     */
    public function normalizeLayout(?string $json): array
    {
        $default = $this->getDefaultLayout();
        if ($json === null || trim($json) === '') {
            return $default;
        }
        $decoded = json_decode($json, true);
        if (! is_array($decoded) || empty($decoded['blocks']) || ! is_array($decoded['blocks'])) {
            return $default;
        }

        $allowed = self::DEFAULT_BLOCK_ORDER;
        $seen    = [];
        $blocks  = [];
        foreach ($decoded['blocks'] as $b) {
            if (! is_array($b)) {
                continue;
            }
            $id = (string) ($b['id'] ?? '');
            if ($id === '' || ! in_array($id, $allowed, true) || in_array($id, $seen, true)) {
                continue;
            }
            $seen[]   = $id;
            $blocks[] = [
                'id'      => $id,
                'enabled' => ! empty($b['enabled']),
            ];
        }
        $defOrder = self::DEFAULT_BLOCK_ORDER;
        foreach ($defOrder as $aid) {
            if (in_array($aid, $seen, true)) {
                continue;
            }
            $insertIdx = count($blocks);
            $myPos     = array_search($aid, $defOrder, true);
            if ($myPos !== false) {
                for ($j = (int) $myPos + 1, $jMax = count($defOrder); $j < $jMax; $j++) {
                    $nextId = $defOrder[$j];
                    foreach ($blocks as $i => $existing) {
                        if (($existing['id'] ?? '') === $nextId) {
                            $insertIdx = $i;
                            break 2;
                        }
                    }
                }
            }
            array_splice($blocks, $insertIdx, 0, [['id' => $aid, 'enabled' => true]]);
            $seen[] = $aid;
        }

        $marginsMm = $this->normalizeMarginsMm($decoded);

        $hasInstancesArray = isset($decoded['instances']) && is_array($decoded['instances']);
        if ($hasInstancesArray) {
            $sectionLayouts   = $this->normalizeSectionLayouts($decoded);
            $sourceLayoutVer  = isset($decoded['version']) ? (int) $decoded['version'] : 0;
            $instances        = $this->normalizeInstances($decoded['instances'], $sectionLayouts, $sourceLayoutVer);
        } else {
            $migrated       = $this->migrateV4ToV5($decoded);
            $sectionLayouts = $migrated['section_layouts'];
            $instances      = $migrated['instances'];
        }
        $instances = $this->ensureLabFirmasInstances($instances, $sectionLayouts);

        $watermark = $this->normalizeWatermark($decoded);
        $pageStyleRaw = is_array($decoded['page_style'] ?? null) ? $decoded['page_style'] : [];
        $pageStyle = [
            'card_header'         => self::normalizeCardHeaderStyle($pageStyleRaw['card_header'] ?? []),
            'notes'               => self::normalizeNotesStyle($pageStyleRaw['notes'] ?? []),
            'lab_firmas'          => self::normalizeLabFirmasStyle($pageStyleRaw['lab_firmas'] ?? []),
            'results_table'       => self::normalizeResultsTableStyle($pageStyleRaw['results_table'] ?? []),
            'header_section'      => self::normalizeHeaderSectionStyle($pageStyleRaw['header_section'] ?? []),
            'header_grid'         => self::normalizeHeaderGridStyle($pageStyleRaw['header_grid'] ?? []),
            'patient_doctor_grid' => self::normalizePatientDoctorGridStyle($pageStyleRaw['patient_doctor_grid'] ?? []),
            'footer_grid'         => self::normalizeFooterGridStyle($pageStyleRaw['footer_grid'] ?? []),
            'print_pagination'        => self::normalizePrintPaginationStyle($pageStyleRaw['print_pagination'] ?? []),
            'pagination_mode'         => self::normalizePaginationMode($pageStyleRaw['pagination_mode'] ?? (
                is_array($pageStyleRaw['grupo_prueba_page_break'] ?? null)
                    ? ($pageStyleRaw['grupo_prueba_page_break']['mode'] ?? ReportPaginationMode::default())
                    : ReportPaginationMode::default()
            )),
            'grupo_prueba_page_break' => self::normalizeGrupoPruebaPageBreakStyle($pageStyleRaw['grupo_prueba_page_break'] ?? []),
            'order_sheet_header'      => self::normalizeOrderSheetHeaderStyle($pageStyleRaw['order_sheet_header'] ?? []),
        ];

        return [
            'version'         => 7,
            'blocks'          => $blocks,
            'section_layouts' => $sectionLayouts,
            'instances'       => $instances,
            'margins_mm'      => $marginsMm,
            'watermark'       => $watermark,
            'page_style'      => $pageStyle,
        ];
    }

    /**
     * @return array{enabled: bool, opacity: float, size_percent: int, file: ?string}
     */
    protected function normalizeWatermark(?array $decoded): array
    {
        $def = self::defaultWatermarkStatic();
        $raw = is_array($decoded) && isset($decoded['watermark']) && is_array($decoded['watermark'])
            ? $decoded['watermark'] : [];
        $fileRaw = isset($raw['file']) ? (string) $raw['file'] : '';
        $file    = self::sanitizeWatermarkRelativePath($fileRaw);
        if ($file !== null && ! is_file(WRITEPATH . str_replace('/', DIRECTORY_SEPARATOR, $file))) {
            $file = null;
        }
        $wants   = ! empty($raw['enabled']);
        $enabled = $wants;
        $opacity = isset($raw['opacity']) ? (float) $raw['opacity'] : $def['opacity'];
        $opacity = round(max(0.05, min(0.9, $opacity)), 2);
        $size    = isset($raw['size_percent']) ? (int) $raw['size_percent'] : $def['size_percent'];
        $size    = max(10, min(95, $size));

        return [
            'enabled'      => $enabled,
            'opacity'      => $opacity,
            'size_percent' => $size,
            'file'         => $file,
        ];
    }

    /**
     * Plantilla usada al generar el PDF (descarga / WhatsApp).
     */
    public function getActiveLayoutForRender(): array
    {
        return $this->resolveLayoutForConfigKey('pdf_result_template_id');
    }

    /**
     * Plantilla usada en la página «Imprimir» del reporte (navegador).
     */
    public function getPrintLayoutForRender(): array
    {
        return $this->resolveLayoutForConfigKey('print_result_template_id');
    }

    /**
     * True si la plantilla de impresión resuelve al mismo id que la plantilla PDF de resultados.
     */
    public function printResultTemplateMatchesPdfTemplate(): bool
    {
        $bindings = $this->getResultTemplateBindingsForReport();
        $pdfId    = (int) ($bindings['pdf']['resolved_template_id'] ?? 0);
        $printId  = (int) ($bindings['print']['resolved_template_id'] ?? 0);

        return $pdfId > 0 && $pdfId === $printId;
    }

    /**
     * Metadatos de las plantillas PDF e impresión configuradas en Sistema (informe de maquetación).
     *
     * @return array{
     *     print: array{config_key: string, config_template_id: int, resolved_template_id: int, name: string, margins_mm: array{top: float, right: float, bottom: float, left: float}, used_pdf_fallback: bool},
     *     pdf: array{config_key: string, config_template_id: int, resolved_template_id: int, name: string, margins_mm: array{top: float, right: float, bottom: float, left: float}, used_pdf_fallback: bool}
     * }
     */
    public function getResultTemplateBindingsForReport(): array
    {
        return [
            'print' => $this->resolveTemplateBindingForConfigKey('print_result_template_id'),
            'pdf'   => $this->resolveTemplateBindingForConfigKey('pdf_result_template_id'),
        ];
    }

    /**
     * @return array{
     *     config_key: string,
     *     config_template_id: int,
     *     resolved_template_id: int,
     *     name: string,
     *     margins_mm: array{top: float, right: float, bottom: float, left: float},
     *     used_pdf_fallback: bool
     * }
     */
    protected function resolveTemplateBindingForConfigKey(string $configKey): array
    {
        $defaults = self::defaultMarginsMmStatic();
        $empty    = [
            'config_key'           => $configKey,
            'config_template_id'   => 0,
            'resolved_template_id' => 0,
            'name'                 => '',
            'margins_mm'           => $defaults,
            'used_pdf_fallback'    => false,
        ];

        try {
            $configModel   = model(AppConfigModel::class);
            $templateModel = model(ReportPdfTemplateModel::class);
            $resolution    = $this->resolveTemplateIdForConfigKey($configKey, $configModel);
            $resolvedId    = (int) ($resolution['resolved_template_id'] ?? 0);
            if ($resolvedId < 1) {
                return array_merge($empty, [
                    'config_template_id'   => (int) ($resolution['config_template_id'] ?? 0),
                    'used_pdf_fallback'    => ! empty($resolution['used_pdf_fallback']),
                ]);
            }

            $row = $templateModel->find($resolvedId);
            if (! $row) {
                return array_merge($empty, [
                    'config_template_id'   => (int) ($resolution['config_template_id'] ?? 0),
                    'resolved_template_id' => $resolvedId,
                    'used_pdf_fallback'    => ! empty($resolution['used_pdf_fallback']),
                ]);
            }

            $layout  = ! empty($row->layout_json)
                ? $this->normalizeLayout((string) $row->layout_json)
                : $this->getDefaultLayout();
            $margins = is_array($layout['margins_mm'] ?? null) ? $layout['margins_mm'] : $defaults;

            return [
                'config_key'           => $configKey,
                'config_template_id'   => (int) ($resolution['config_template_id'] ?? 0),
                'resolved_template_id' => $resolvedId,
                'name'                 => (string) ($row->name ?? ''),
                'margins_mm'           => $margins,
                'used_pdf_fallback'    => ! empty($resolution['used_pdf_fallback']),
            ];
        } catch (\Throwable $e) {
            return $empty;
        }
    }

    /**
     * @return array{config_template_id: int, resolved_template_id: int, used_pdf_fallback: bool}
     */
    protected function resolveTemplateIdForConfigKey(string $configKey, ?AppConfigModel $configModel = null): array
    {
        $configModel   = $configModel ?? model(AppConfigModel::class);
        $configId      = (int) $configModel->getValue($configKey);
        $resolvedId    = $configId;
        $usedFallback  = false;
        if ($resolvedId < 1 && $configKey !== 'pdf_result_template_id') {
            $resolvedId = (int) $configModel->getValue('pdf_result_template_id');
            $usedFallback = $resolvedId > 0;
        }

        return [
            'config_template_id'   => $configId,
            'resolved_template_id' => $resolvedId,
            'used_pdf_fallback'    => $usedFallback,
        ];
    }

    /**
     * @return array{version: int, blocks: list<array{id: string, enabled: bool}>, section_layouts: array, instances: list<array{uid: string, element_type: string, section: string, enabled: bool, column: int}>, margins_mm: array, watermark: array}
     */
    protected function resolveLayoutForConfigKey(string $configKey): array
    {
        try {
            $configModel   = model(AppConfigModel::class);
            $templateModel = model(ReportPdfTemplateModel::class);
            $resolution    = $this->resolveTemplateIdForConfigKey($configKey, $configModel);
            $id            = (int) ($resolution['resolved_template_id'] ?? 0);
            if ($id > 0) {
                $row = $templateModel->find($id);
                if ($row && ! empty($row->layout_json)) {
                    return self::applyTenantOrderSheetHeaderOverride(
                        $this->normalizeLayout((string) $row->layout_json)
                    );
                }
            }
            $first = $templateModel->orderBy('id', 'ASC')->first();
            if ($first && ! empty($first->layout_json)) {
                return self::applyTenantOrderSheetHeaderOverride(
                    $this->normalizeLayout((string) $first->layout_json)
                );
            }
        } catch (\Throwable $e) {
            // tabla inexistente o error de BD: layout por defecto
        }

        return self::applyTenantOrderSheetHeaderOverride($this->getDefaultLayout());
    }

    public function layoutJsonForEditor(object $template): array
    {
        return $this->normalizeLayout($template->layout_json ?? null);
    }

    /**
     * Textos de ejemplo para la vista previa en el editor (no son datos reales).
     *
     * @return array<string, string>
     */
    public static function patientDoctorPreviewSamples(): array
    {
        return [
            'paciente_nombre'   => 'Juan Pérez García',
            'paciente_genero'   => 'Masculino',
            'paciente_edad'     => '42 años, 3 meses y 10 días',
            'paciente_telefono' => '+52 55 1234 5678',
            'diagnostico_presuntivo' => 'Diabetes mellitus en estudio',
            'medico'             => 'Dra. María López',
            'fecha_recepcion'    => '09/04/2026 10:15:30',
            'fecha_reporte'      => '10/04/2026 14:22:00',
            'numero_orden'       => 'A-2026-0150',
        ];
    }

    /**
     * Textos de ejemplo para la vista previa del editor (todos los tipos).
     *
     * @return array<string, string>
     */
    public static function elementPreviewSamples(): array
    {
        return array_merge(
            self::headerPreviewSamples(),
            ['custom_text' => ''],
            self::patientDoctorPreviewSamples(),
            self::footerPreviewSamples(),
            self::labFirmasPreviewSamples()
        );
    }

    /**
     * @return array<string, string>
     */
    public static function labFirmasPreviewSamples(): array
    {
        return [
            'lab_firmas_title'                => 'VALIDACIÓN Y APROBACIÓN',
            'lab_firmas_validator'          => 'Ana López Martínez',
            'lab_firmas_seal'               => '[Imagen]',
            'lab_firmas_approver_signature' => '[Firma]',
            'lab_firmas_approver_name'      => 'Dr. Carlos Ruiz',
            'lab_firmas_approver_cargo'     => 'Director técnico',
            'lab_firmas_matricula'          => 'MP 12345',
        ];
    }
}
