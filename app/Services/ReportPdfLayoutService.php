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
        'bg_color'      => '#E9ECEF',
        'text_color'    => '#212529',
        'font_family'   => 'DejaVu Sans',
        'font_size_pt'  => 10.0,
        'font_weight'   => '700',
        'font_style'    => 'normal',
        'text_transform'=> 'uppercase',
    ];
    public const DEFAULT_NOTES_STYLE = [
        'title_bg_color'    => '#FFF3CD',
        'title_text_color'  => '#664D03',
        'body_bg_color'     => '#FFFFFF',
        'body_text_color'   => '#333333',
        'font_family'       => 'DejaVu Sans',
        'font_size_pt'      => 9.5,
        'font_weight'       => 'normal',
        'font_style'        => 'normal',
        'text_transform'    => 'none',
        'line_height'       => 1.4,
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
    public const DEFAULT_BLOCK_ORDER = ['header', 'patient_doctor', 'results', 'notes', 'footer'];

    /** @var list<string> Orden por defecto de cada dato dentro del bloque paciente/médico */
    public const PATIENT_DOCTOR_FIELD_ORDER = [
        'paciente_nombre',
        'paciente_edad',
        'paciente_telefono',
        'medico',
        'fecha_ingreso',
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
        'paciente_edad',
        'paciente_telefono',
        'medico',
        'fecha_ingreso',
        'numero_orden',
        'footer_company',
        'footer_generated',
        'footer_policy',
    ];

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
            'card_header' => self::DEFAULT_CARD_HEADER_STYLE,
            'notes'       => self::DEFAULT_NOTES_STYLE,
            'results_table' => self::DEFAULT_RESULTS_TABLE_STYLE,
            'header_section' => self::DEFAULT_HEADER_SECTION_STYLE,
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
        return in_array($id, ['paciente_nombre', 'paciente_edad', 'paciente_telefono'], true) ? 0 : 1;
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
            'lab_phone'    => 'Tel: 555-0100',
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
     * @return array{header: array{columns: int, line_height: float, column_align_h: list<string>, column_align_v: list<string>}, patient_doctor: array{columns: int, line_height: float, column_align_h: list<string>, column_align_v: list<string>}, footer: array{columns: int, line_height: float, column_align_h: list<string>, column_align_v: list<string>}}
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
            self::footerFieldLabels()
        );
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
        $allowed = ['header', 'patient_doctor', 'footer'];
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
     * @return array{header: array{columns: int}, patient_doctor: array{columns: int}, footer: array{columns: int}}
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
    protected function normalizeInstances(array $raw, array $sectionLayouts): array
    {
        $allowed = self::ELEMENT_TYPES;
        $out     = [];
        foreach ($raw as $row) {
            if (! is_array($row)) {
                continue;
            }
            $type = (string) ($row['element_type'] ?? $row['type'] ?? $row['id'] ?? '');
            if ($type === '' || ! in_array($type, $allowed, true)) {
                continue;
            }
            $section = (string) ($row['section'] ?? '');
            if (! in_array($section, ['header', 'patient_doctor', 'footer'], true)) {
                continue;
            }
            $cols = (int) ($sectionLayouts[$section]['columns'] ?? 1);
            $cols = max(self::SECTION_COLUMN_MIN, min(self::SECTION_COLUMN_MAX, $cols));
            $uid = (string) ($row['uid'] ?? '');
            if ($uid === '') {
                $uid = self::generateInstanceUid();
            }
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

            $out[] = [
                'uid'           => $uid,
                'element_type'  => $type,
                'section'       => $section,
                'enabled'       => $enabled,
                'column'        => $col,
                'column_span'   => $span,
                'text_style'    => $textStyle,
            ];
        }

        return $out;
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
        $allowedFamily = ['DejaVu Sans', 'Helvetica', 'Arial', 'Times New Roman', 'Courier New'];
        if (! in_array($family, $allowedFamily, true)) {
            $family = $def['font_family'];
        }
        $size = isset($ts['font_size_pt']) ? (float) $ts['font_size_pt'] : $def['font_size_pt'];
        $size = round(max(6.0, min(24.0, $size)), 2);
        $weight = strtolower(trim((string) ($ts['font_weight'] ?? $def['font_weight'])));
        $allowedWeight = ['normal', 'bold', '100', '200', '300', '400', '500', '600', '700', '800', '900'];
        if (! in_array($weight, $allowedWeight, true)) {
            $weight = $def['font_weight'];
        }
        $color = strtoupper(trim((string) ($ts['font_color'] ?? $def['font_color'])));
        if (! preg_match('/^#[0-9A-F]{6}$/', $color)) {
            $color = $def['font_color'];
        }
        $style = strtolower(trim((string) ($ts['font_style'] ?? $def['font_style'])));
        if (! in_array($style, ['normal', 'italic', 'oblique'], true)) {
            $style = $def['font_style'];
        }
        $transform = strtolower(trim((string) ($ts['text_transform'] ?? $def['text_transform'])));
        if (! in_array($transform, ['none', 'uppercase', 'lowercase', 'capitalize'], true)) {
            $transform = $def['text_transform'];
        }
        $ls = isset($ts['letter_spacing_em']) ? (float) $ts['letter_spacing_em'] : $def['letter_spacing_em'];
        $ls = round(max(-0.2, min(1.0, $ls)), 2);
        $lh = isset($ts['line_height']) ? (float) $ts['line_height'] : $def['line_height'];
        $lh = round(max(1.0, min(3.0, $lh)), 2);
        $shadow = strtolower(trim((string) ($ts['text_shadow'] ?? $def['text_shadow'])));
        if (! in_array($shadow, ['none', 'soft', 'medium', 'strong'], true)) {
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
        $allowedFamily = ['DejaVu Sans', 'Helvetica', 'Arial', 'Times New Roman', 'Courier New'];
        if (! in_array($family, $allowedFamily, true)) {
            $family = $def['font_family'];
        }
        $size = isset($s['font_size_pt']) ? (float) $s['font_size_pt'] : $def['font_size_pt'];
        $size = round(max(7.0, min(20.0, $size)), 2);
        $weight = strtolower(trim((string) ($s['font_weight'] ?? $def['font_weight'])));
        $allowedWeight = ['normal', 'bold', '100', '200', '300', '400', '500', '600', '700', '800', '900'];
        if (! in_array($weight, $allowedWeight, true)) {
            $weight = $def['font_weight'];
        }
        $style = strtolower(trim((string) ($s['font_style'] ?? $def['font_style'])));
        if (! in_array($style, ['normal', 'italic', 'oblique'], true)) {
            $style = $def['font_style'];
        }
        $transform = strtolower(trim((string) ($s['text_transform'] ?? $def['text_transform'])));
        if (! in_array($transform, ['none', 'uppercase', 'lowercase', 'capitalize'], true)) {
            $transform = $def['text_transform'];
        }

        return [
            'bg_color'       => $bg,
            'text_color'     => $tc,
            'font_family'    => $family,
            'font_size_pt'   => $size,
            'font_weight'    => $weight,
            'font_style'     => $style,
            'text_transform' => $transform,
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
        $allowedFamily = ['DejaVu Sans', 'Helvetica', 'Arial', 'Times New Roman', 'Courier New'];
        if (! in_array($family, $allowedFamily, true)) {
            $family = $def['font_family'];
        }
        $size = isset($s['font_size_pt']) ? (float) $s['font_size_pt'] : $def['font_size_pt'];
        $size = round(max(7.0, min(20.0, $size)), 2);
        $weight = strtolower(trim((string) ($s['font_weight'] ?? $def['font_weight'])));
        $allowedWeight = ['normal', 'bold', '100', '200', '300', '400', '500', '600', '700', '800', '900'];
        if (! in_array($weight, $allowedWeight, true)) {
            $weight = $def['font_weight'];
        }
        $style = strtolower(trim((string) ($s['font_style'] ?? $def['font_style'])));
        if (! in_array($style, ['normal', 'italic', 'oblique'], true)) {
            $style = $def['font_style'];
        }
        $transform = strtolower(trim((string) ($s['text_transform'] ?? $def['text_transform'])));
        if (! in_array($transform, ['none', 'uppercase', 'lowercase', 'capitalize'], true)) {
            $transform = $def['text_transform'];
        }
        $lh = isset($s['line_height']) ? (float) $s['line_height'] : $def['line_height'];
        $lh = round(max(1.0, min(3.0, $lh)), 2);

        return [
            'title_bg_color'   => $pickColor('title_bg_color', $def['title_bg_color']),
            'title_text_color' => $pickColor('title_text_color', $def['title_text_color']),
            'body_bg_color'    => $pickColor('body_bg_color', $def['body_bg_color']),
            'body_text_color'  => $pickColor('body_text_color', $def['body_text_color']),
            'font_family'      => $family,
            'font_size_pt'     => $size,
            'font_weight'      => $weight,
            'font_style'       => $style,
            'text_transform'   => $transform,
            'line_height'      => $lh,
        ];
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
        $allowedFamily = ['DejaVu Sans', 'Helvetica', 'Arial', 'Times New Roman', 'Courier New'];
        if (! in_array($family, $allowedFamily, true)) {
            $family = $def['font_family'];
        }
        $size = isset($s['font_size_pt']) ? (float) $s['font_size_pt'] : $def['font_size_pt'];
        $size = round(max(7.0, min(20.0, $size)), 2);
        $weight = strtolower(trim((string) ($s['font_weight'] ?? $def['font_weight'])));
        $allowedWeight = ['normal', 'bold', '100', '200', '300', '400', '500', '600', '700', '800', '900'];
        if (! in_array($weight, $allowedWeight, true)) {
            $weight = $def['font_weight'];
        }
        $style = strtolower(trim((string) ($s['font_style'] ?? $def['font_style'])));
        if (! in_array($style, ['normal', 'italic', 'oblique'], true)) {
            $style = $def['font_style'];
        }
        $transform = strtolower(trim((string) ($s['text_transform'] ?? $def['text_transform'])));
        if (! in_array($transform, ['none', 'uppercase', 'lowercase', 'capitalize'], true)) {
            $transform = $def['text_transform'];
        }
        $lh = isset($s['line_height']) ? (float) $s['line_height'] : $def['line_height'];
        $lh = round(max(1.0, min(3.0, $lh)), 2);
        $segBw = isset($s['segment_border_width_px']) ? (int) $s['segment_border_width_px'] : (int) $def['segment_border_width_px'];
        $segBw = max(0, min(4, $segBw));
        $segShadow = strtolower(trim((string) ($s['segment_shadow'] ?? $def['segment_shadow'])));
        if (! in_array($segShadow, ['none', 'soft', 'medium', 'strong'], true)) {
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
            'paciente_edad'     => 'Edad',
            'paciente_telefono' => 'Teléfono',
            'medico'             => 'Médico tratante',
            'fecha_ingreso'      => 'Fecha de ingreso',
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
            'version'          => 5,
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
        foreach (self::DEFAULT_BLOCK_ORDER as $aid) {
            if (! in_array($aid, $seen, true)) {
                $blocks[] = ['id' => $aid, 'enabled' => true];
            }
        }

        $marginsMm = $this->normalizeMarginsMm($decoded);

        $hasInstancesArray = isset($decoded['instances']) && is_array($decoded['instances']);
        if ($hasInstancesArray) {
            $sectionLayouts = $this->normalizeSectionLayouts($decoded);
            $instances      = $this->normalizeInstances($decoded['instances'], $sectionLayouts);
        } else {
            $migrated       = $this->migrateV4ToV5($decoded);
            $sectionLayouts = $migrated['section_layouts'];
            $instances      = $migrated['instances'];
        }

        $watermark = $this->normalizeWatermark($decoded);
        $pageStyleRaw = is_array($decoded['page_style'] ?? null) ? $decoded['page_style'] : [];
        $pageStyle = [
            'card_header' => self::normalizeCardHeaderStyle($pageStyleRaw['card_header'] ?? []),
            'notes'       => self::normalizeNotesStyle($pageStyleRaw['notes'] ?? []),
            'results_table' => self::normalizeResultsTableStyle($pageStyleRaw['results_table'] ?? []),
            'header_section' => self::normalizeHeaderSectionStyle($pageStyleRaw['header_section'] ?? []),
        ];

        return [
            'version'         => 5,
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
            'paciente_edad'     => '42 años, 3 meses y 10 días',
            'paciente_telefono' => '+52 55 1234 5678',
            'medico'             => 'Dra. María López',
            'fecha_ingreso'      => '30/03/2026',
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
            self::footerPreviewSamples()
        );
    }
}
