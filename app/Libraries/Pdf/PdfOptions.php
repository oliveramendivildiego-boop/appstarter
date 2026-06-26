<?php

namespace App\Libraries\Pdf;

/**
 * Opciones de renderizado PDF independientes del motor.
 */
class PdfOptions
{
    /**
     * @param array{top: ?float, right: ?float, bottom: ?float, left: ?float} $marginsMm
     */
    public function __construct(
        public readonly ?string $paperKey = 'letter',
        public readonly ?float $widthMm = null,
        public readonly ?float $heightMm = null,
        public readonly string $orientation = 'portrait',
        public readonly bool $printBackground = true,
        public readonly float $scale = 1.0,
        public readonly array $marginsMm = [
            'top'    => null,
            'right'  => null,
            'bottom' => null,
            'left'   => null,
        ],
        public readonly string $headerTemplate = '',
        public readonly string $footerTemplate = '',
    ) {
    }

    /**
     * Compatibilidad con ReportPdfLayoutService::resolveGlobalPageSizeMm().
     *
     * @param array<string, mixed>|null $pageSize
     */
    public static function fromLegacyPageSize(?array $pageSize): self
    {
        $pdfConfig = config('Pdf');

        if (! is_array($pageSize) || ! isset($pageSize['key'])) {
            return new self(
                paperKey: $pdfConfig->paperSize ?? 'letter',
                orientation: $pdfConfig->orientation ?? 'portrait',
                printBackground: (bool) ($pdfConfig->printBackground ?? true),
                scale: (float) ($pdfConfig->scale ?? 1.0),
                marginsMm: is_array($pdfConfig->marginsMm ?? null) ? $pdfConfig->marginsMm : [],
                headerTemplate: (string) ($pdfConfig->headerTemplate ?? ''),
                footerTemplate: (string) ($pdfConfig->footerTemplate ?? ''),
            );
        }

        $key = (string) $pageSize['key'];

        return new self(
            paperKey: $key,
            widthMm: $key === 'custom' ? (float) ($pageSize['width_mm'] ?? 0) : null,
            heightMm: $key === 'custom' ? (float) ($pageSize['height_mm'] ?? 0) : null,
            orientation: $pdfConfig->orientation ?? 'portrait',
            printBackground: (bool) ($pdfConfig->printBackground ?? true),
            scale: (float) ($pdfConfig->scale ?? 1.0),
            marginsMm: is_array($pdfConfig->marginsMm ?? null) ? $pdfConfig->marginsMm : [],
            headerTemplate: (string) ($pdfConfig->headerTemplate ?? ''),
            footerTemplate: (string) ($pdfConfig->footerTemplate ?? ''),
        );
    }

    /**
     * @return array<string, mixed>|null
     */
    public function toLegacyPageSizeArray(): ?array
    {
        $key = $this->paperKey ?? 'letter';
        if ($key === 'custom' && $this->widthMm !== null && $this->heightMm !== null) {
            return [
                'key'       => 'custom',
                'width_mm'  => $this->widthMm,
                'height_mm' => $this->heightMm,
            ];
        }

        return ['key' => $key];
    }
}
