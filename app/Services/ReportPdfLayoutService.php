<?php

namespace App\Services;

use App\Models\AppConfigModel;
use App\Models\ReportPdfTemplateModel;

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
        'font_family'       => 'DejaVu Sans',
        'font_size_pt'      => 9.0,
        'font_weight'       => 'normal',
        'font_style'        => 'normal',
        'text_transform'    => 'none',
        'line_height'       => 1.35,
    ];
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
        'lab_phone',
        'lab_email',
        'lab_website',
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
        'qr',
        'paciente_nombre',
        'paciente_genero',
        'paciente_edad',
        'paciente_telefono',
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

    public const SECTION_COLUMN_MIN = 1;

    public const SECTION_COLUMN_MAX = 6;

    /** Márgenes por defecto del cuerpo del PDF (mm) */
    public const DEFAULT_MARGINS_MM = [
        'top'    => 15.0,
        'right'  => 15.0,
        'bottom' => 15.0,
        'left'   => 15.0,
    ];

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
     */
    public static function getWatermarkDataUriForLayout(array $layout): ?string
    {
        $w = $layout['watermark'] ?? [];
        if (empty($w['enabled']) || empty($w['file'])) {
            return null;
        }
        $rel = self::sanitizeWatermarkRelativePath((string) $w['file']);
        if ($rel === null) {
            return null;
        }
        $full = WRITEPATH . str_replace('/', DIRECTORY_SEPARATOR, $rel);
        if (! is_file($full) || ! is_readable($full)) {
            return null;
        }
        $data = @file_get_contents($full);
        if ($data === false) {
            return null;
        }
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mime  = $finfo ? finfo_file($finfo, $full) : false;
        if ($finfo) {
            finfo_close($finfo);
        }

        return 'data:' . ($mime ?: 'image/png') . ';base64,' . base64_encode($data);
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
            'lab_phone'    => '555-0100',
            'lab_email'    => 'contacto@lab.ejemplo',
            'lab_website'  => 'www.lab.ejemplo',
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

    /**
     * @return array<string, array{columns: int, line_height: float, column_align_h: list<string>, column_align_v: list<string>}>
     */
    public static function defaultSectionLayoutsStatic(): array
    {
        return [
            'header' => [
                'columns'        => 3,
                'line_height'    => 1.35,
                'column_align_h' => ['left', 'center', 'right'],
                'column_align_v' => ['top', 'top', 'top'],
            ],
            'patient_doctor' => [
                'columns'        => 2,
                'line_height'    => 1.35,
                'column_align_h' => ['left', 'right'],
                'column_align_v' => ['top', 'top'],
            ],
            'footer' => [
                'columns'        => 3,
                'line_height'    => 1.35,
                'column_align_h' => ['left', 'center', 'right'],
                'column_align_v' => ['top', 'top', 'top'],
            ],
            'lab_firmas' => [
                'columns'        => 3,
                'line_height'    => 1.35,
                'column_align_h' => ['left', 'center', 'right'],
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
            $out[] = in_array($v, $allowed, true) ? $v : self::columnAlign($i, $n);
        }

        return $out;
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
            $items[] = [
                'element_type' => $type,
                'column'       => $col,
                'column_span'  => $span,
                'text_style'   => self::normalizeTextStyle($inst['text_style'] ?? []),
            ];
        }

        return [$n, $items];
    }

    /**
     * @return array<string, array{columns: int, line_height: float, column_align_h: list<string>, column_align_v: list<string>}>
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

            $defLh = isset($def['line_height']) ? (float) $def['line_height'] : 1.35;
            $lh    = isset($rawSec['line_height']) ? (float) $rawSec['line_height'] : $defLh;
            $lh    = round(max(1.0, min(2.5, $lh)), 2);

            $hRaw = $rawSec['column_align_h'] ?? null;
            $vRaw = $rawSec['column_align_v'] ?? null;
            $out[$key] = [
                'columns'        => $n,
                'line_height'    => $lh,
                'column_align_h' => self::normalizeColumnAlignHArray(is_array($hRaw) ? $hRaw : [], $n),
                'column_align_v' => self::normalizeColumnAlignVArray(is_array($vRaw) ? $vRaw : [], $n),
            ];
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
            $textStyle = self::normalizeTextStyle($row['text_style'] ?? []);

            foreach ($typesToEmit as $emitType) {
                $uid = (string) ($row['uid'] ?? '');
                if ($uid === '' || count($typesToEmit) > 1) {
                    $uid = self::generateInstanceUid();
                }
                $out[] = [
                    'uid'           => $uid,
                    'element_type'  => $emitType,
                    'section'       => $section,
                    'enabled'       => $enabled,
                    'column'        => $col,
                    'column_span'   => $span,
                    'text_style'    => $textStyle,
                ];
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
     * Listas permitidas para validación en cliente y servidor.
     *
     * @return array{font_families: list<string>, font_weights: list<string>, font_styles: list<string>, text_transforms: list<string>, text_shadows: list<string>, segment_shadows: list<string>}
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
        }
        if (isset($ps['results_table'])) {
            $err = self::validateRawResultsTableStyleBlock($ps['results_table']);
            if ($err !== null) {
                return $err;
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
        ]);
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

        $base['label_qr_hint_text_color']   = self::normalizeLabFirmasBorderColor($s['label_qr_hint_text_color'] ?? null, $btc);
        $base['label_qr_hint_font_size_pt'] = $pickPieceFs('label_qr_hint_font_size_pt');
        $base['label_qr_hint_font_weight']  = $pickPieceFw('label_qr_hint_font_weight');
        $base['label_qr_hint_font_style']   = $pickPieceFst('label_qr_hint_font_style');

        foreach (self::HEADER_GRID_LABEL_DEFAULTS as $id => $fallback) {
            $base['label_' . $id] = self::clipLabFirmasLabel($s['label_' . $id] ?? null, $fallback);
            $base['show_label_' . $id] = self::labFirmasBool($s, 'show_label_' . $id, true);
            $base['label_' . $id . '_line_mode'] = self::labFirmasLineModeInline($s, 'label_' . $id . '_line_mode') ? 'inline' : 'stacked';
            $base['label_' . $id . '_text_color'] = self::normalizeLabFirmasBorderColor($s['label_' . $id . '_text_color'] ?? null, $btc);
            $base['label_' . $id . '_font_size_pt'] = $pickPieceFs('label_' . $id . '_font_size_pt');
            $base['label_' . $id . '_font_weight']  = $pickPieceFw('label_' . $id . '_font_weight');
            $base['label_' . $id . '_font_style']   = $pickPieceFst('label_' . $id . '_font_style');
        }

        return $base;
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
        foreach (self::PATIENT_DOCTOR_GRID_LABEL_DEFAULTS as $id => $fallback) {
            $base['label_' . $id] = self::clipLabFirmasLabel($s['label_' . $id] ?? null, $fallback);
            $base['show_label_' . $id] = self::labFirmasBool($s, 'show_label_' . $id, true);
            $base['label_' . $id . '_line_mode'] = self::labFirmasLineModeInline($s, 'label_' . $id . '_line_mode') ? 'inline' : 'stacked';
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

        $gridFs = (float) $base['font_size_pt'];
        $gridFw = (string) $base['font_weight'];
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
        $base['footer_company_font_size_pt']            = $pickPieceFs('footer_company_font_size_pt');
        $base['footer_company_font_weight']             = $pickPieceFw('footer_company_font_weight');
        $base['footer_company_font_style']              = $pickPieceFst('footer_company_font_style');
        $base['label_footer_generated_font_size_pt']    = $pickPieceFs('label_footer_generated_font_size_pt');
        $base['label_footer_generated_font_weight']      = $pickPieceFw('label_footer_generated_font_weight');
        $base['label_footer_generated_font_style']       = $pickPieceFst('label_footer_generated_font_style');
        $base['label_footer_datetime_font_size_pt']      = $pickPieceFs('label_footer_datetime_font_size_pt');
        $base['label_footer_datetime_font_weight']        = $pickPieceFw('label_footer_datetime_font_weight');
        $base['label_footer_datetime_font_style']         = $pickPieceFst('label_footer_datetime_font_style');
        $base['footer_policy_font_size_pt']              = $pickPieceFs('footer_policy_font_size_pt');
        $base['footer_policy_font_weight']               = $pickPieceFw('footer_policy_font_weight');
        $base['footer_policy_font_style']                = $pickPieceFst('footer_policy_font_style');

        return $base;
    }

    /**
     * CSS inline para textos del pie (color/tipo) — evita que td.pdf-cell o estilos globales tapen variables en vista/PDF.
     *
     * @param array<string, mixed> $ft footer_grid normalizado o bruto
     */
    public static function footerGridPieceStyleAttr(array $ft, string $piece): string
    {
        $ft = self::normalizeFooterGridStyle($ft);
        $fn   = (string) $ft['font_family'];
        $ffCss = (strpbrk($fn, ' ') !== false)
            ? '"' . str_replace(['"', '\\'], '', $fn) . '", sans-serif'
            : str_replace(['"', '\\'], '', $fn) . ', sans-serif';
        $lh   = (float) $ft['line_height'];
        $tt   = (string) $ft['text_transform'];
        $decl = static function (string $color, float $fs, string $fw, string $fst) use ($ffCss, $lh, $tt): string {
            return 'color:' . $color
                . ';font-family:' . $ffCss
                . ';font-size:' . (string) $fs . 'pt'
                . ';font-weight:' . $fw
                . ';font-style:' . $fst
                . ';text-transform:' . $tt
                . ';line-height:' . (string) $lh;
        };
        switch ($piece) {
            case 'company':
                return $decl(
                    (string) $ft['footer_company_text_color'],
                    (float) $ft['footer_company_font_size_pt'],
                    (string) $ft['footer_company_font_weight'],
                    (string) $ft['footer_company_font_style']
                );
            case 'label_generated':
                return $decl(
                    (string) $ft['label_footer_generated_color'],
                    (float) $ft['label_footer_generated_font_size_pt'],
                    (string) $ft['label_footer_generated_font_weight'],
                    (string) $ft['label_footer_generated_font_style']
                );
            case 'datetime':
                return $decl(
                    (string) $ft['label_footer_datetime_color'],
                    (float) $ft['label_footer_datetime_font_size_pt'],
                    (string) $ft['label_footer_datetime_font_weight'],
                    (string) $ft['label_footer_datetime_font_style']
                );
            case 'policy':
                return $decl(
                    (string) $ft['footer_policy_text_color'],
                    (float) $ft['footer_policy_font_size_pt'],
                    (string) $ft['footer_policy_font_weight'],
                    (string) $ft['footer_policy_font_style']
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
        $fn   = (string) $hg['font_family'];
        $ffCss = (strpbrk($fn, ' ') !== false)
            ? '"' . str_replace(['"', '\\'], '', $fn) . '", sans-serif'
            : str_replace(['"', '\\'], '', $fn) . ', sans-serif';
        $lh   = (float) $hg['line_height'];
        $tt   = (string) $hg['text_transform'];
        $decl = static function (string $color, float $fs, string $fw, string $fst) use ($ffCss, $lh, $tt): string {
            return 'color:' . $color
                . ';font-family:' . $ffCss
                . ';font-size:' . (string) $fs . 'pt'
                . ';font-weight:' . $fw
                . ';font-style:' . $fst
                . ';text-transform:' . $tt
                . ';line-height:' . (string) $lh;
        };
        if ($piece === 'qr_hint') {
            return $decl(
                (string) $hg['label_qr_hint_text_color'],
                (float) $hg['label_qr_hint_font_size_pt'],
                (string) $hg['label_qr_hint_font_weight'],
                (string) $hg['label_qr_hint_font_style']
            );
        }
        if (! array_key_exists($piece, self::HEADER_GRID_LABEL_DEFAULTS)) {
            return '';
        }

        return $decl(
            (string) $hg['label_' . $piece . '_text_color'],
            (float) $hg['label_' . $piece . '_font_size_pt'],
            (string) $hg['label_' . $piece . '_font_weight'],
            (string) $hg['label_' . $piece . '_font_style']
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
     * @return array{header_bg_color: string, header_text_color: string, body_bg_color: string, body_transparent: bool, body_text_color: string, border_color: string, segment_bg_color: string, segment_transparent: bool, segment_border_color: string, segment_border_width_px: int, segment_shadow: string, font_family: string, font_size_pt: float, font_weight: string, font_style: string, text_transform: string, line_height: float}
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
        $segBw = isset($s['segment_border_width_px']) ? (int) $s['segment_border_width_px'] : (int) $def['segment_border_width_px'];
        $segBw = max(0, min(4, $segBw));
        $segShadow = strtolower(trim((string) ($s['segment_shadow'] ?? $def['segment_shadow'])));
        if (! in_array($segShadow, self::ALLOWED_PDF_TEXT_SHADOWS, true)) {
            $segShadow = $def['segment_shadow'];
        }

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
            'font_family'       => $family,
            'font_size_pt'      => $size,
            'font_weight'       => $weight,
            'font_style'        => $style,
            'text_transform'    => $transform,
            'line_height'       => $lh,
        ];
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
        $enabled = $wants && $file !== null;
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
     * @return array{version: int, blocks: list<array{id: string, enabled: bool}>, section_layouts: array, instances: list<array{uid: string, element_type: string, section: string, enabled: bool, column: int}>, margins_mm: array, watermark: array}
     */
    protected function resolveLayoutForConfigKey(string $configKey): array
    {
        try {
            $configModel   = model(AppConfigModel::class);
            $templateModel = model(ReportPdfTemplateModel::class);
            $id            = (int) $configModel->getValue($configKey);
            if ($id < 1 && $configKey !== 'pdf_result_template_id') {
                $id = (int) $configModel->getValue('pdf_result_template_id');
            }
            if ($id > 0) {
                $row = $templateModel->find($id);
                if ($row && ! empty($row->layout_json)) {
                    return $this->normalizeLayout((string) $row->layout_json);
                }
            }
            $first = $templateModel->orderBy('id', 'ASC')->first();
            if ($first && ! empty($first->layout_json)) {
                return $this->normalizeLayout((string) $first->layout_json);
            }
        } catch (\Throwable $e) {
            // tabla inexistente o error de BD: layout por defecto
        }

        return $this->getDefaultLayout();
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
