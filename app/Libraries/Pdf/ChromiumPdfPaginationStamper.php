<?php

namespace App\Libraries\Pdf;

/**
 * Paginación del pie en PDF Chromium: los contadores CSS no funcionan en headless print-to-pdf.
 */
class ChromiumPdfPaginationStamper
{
    private const TOTAL_PAGES_TOKEN = '__PDF_TOTAL_PAGES__';

    public static function htmlNeedsPaginationWorkaround(string $html): bool
    {
        return str_contains($html, 'pdf-ft-pagination-num')
            || str_contains($html, 'data-total="' . self::TOTAL_PAGES_TOKEN . '"')
            || str_contains($html, self::TOTAL_PAGES_TOKEN);
    }

    /**
     * @return list<array<string, mixed>>
     */
    public static function extractFooterPaginationSlots(string $html): array
    {
        if (! preg_match_all('/<!--\s*pdf-pagination:([A-Za-z0-9+\/=_-]+)\s*-->/', $html, $matches)) {
            return [];
        }

        $slots = [];
        foreach ($matches[1] as $encoded) {
            $json = base64_decode($encoded, true);
            if ($json === false) {
                continue;
            }
            $data = json_decode($json, true);
            if (! is_array($data)) {
                continue;
            }
            if (strtolower((string) ($data['zone'] ?? 'header')) !== 'footer') {
                continue;
            }
            $slots[] = $data;
        }

        return $slots;
    }

    public static function countPdfPages(string $binary): int
    {
        if (preg_match_all('/\/Type\s*\/Page[^s]/', $binary, $matches)) {
            return max(1, count($matches[0]));
        }

        return 1;
    }

    public static function stamp(string $pdfBinary, string $sourceHtml, string $tempDir): string
    {
        $slots = self::extractFooterPaginationSlots($sourceHtml);
        $orderSheetSlot = null;
        if (str_contains($sourceHtml, 'data-order-sheet-from-page-two="1"')) {
            $orderSheetSlot = ChromiumPdfOrderSheetStamper::extractSlot($sourceHtml);
        }

        if ($slots === [] && $orderSheetSlot === null) {
            return $pdfBinary;
        }

        $phpStamped = ChromiumPdfPaginationPhpStamper::stampBinary($pdfBinary, $slots, $tempDir, $orderSheetSlot);
        if (is_string($phpStamped) && $phpStamped !== '') {
            return $phpStamped;
        }

        $pythonStamped = self::stampWithPython($pdfBinary, $slots, $tempDir);
        if (is_string($pythonStamped) && $pythonStamped !== '') {
            log_message('info', 'ChromiumPdfPaginationStamper: fallback Python');

            return $pythonStamped;
        }

        log_message('warning', 'ChromiumPdfPaginationStamper: no se pudo estampar la paginación del pie.');

        return $pdfBinary;
    }

    /**
     * @param list<array<string, mixed>> $slots
     */
    private static function stampWithPython(string $pdfBinary, array $slots, string $tempDir): ?string
    {
        $script = ROOTPATH . 'writable' . DIRECTORY_SEPARATOR . 'scripts' . DIRECTORY_SEPARATOR . 'stamp_chromium_footer_pagination.py';
        if (! is_file($script)) {
            return null;
        }

        $python = self::resolvePythonExecutable();
        if ($python === null) {
            return null;
        }

        $inputPath  = $tempDir . DIRECTORY_SEPARATOR . 'stamp_in_' . bin2hex(random_bytes(6)) . '.pdf';
        $outputPath = $tempDir . DIRECTORY_SEPARATOR . 'stamp_out_' . bin2hex(random_bytes(6)) . '.pdf';
        $slotsPath  = $tempDir . DIRECTORY_SEPARATOR . 'stamp_slots_' . bin2hex(random_bytes(6)) . '.json';

        try {
            if (file_put_contents($inputPath, $pdfBinary) === false) {
                return null;
            }

            $slotsJson = json_encode($slots, JSON_UNESCAPED_UNICODE);
            if (! is_string($slotsJson) || file_put_contents($slotsPath, $slotsJson) === false) {
                return null;
            }

            $cmd = implode(' ', [
                escapeshellarg($python),
                escapeshellarg($script),
                escapeshellarg($inputPath),
                escapeshellarg($outputPath),
                escapeshellarg($slotsPath),
            ]);

            exec($cmd, $output, $exitCode);
            if ($exitCode !== 0 || ! is_file($outputPath)) {
                return null;
            }

            $stamped = file_get_contents($outputPath);

            return is_string($stamped) && $stamped !== '' ? $stamped : null;
        } finally {
            @unlink($inputPath);
            @unlink($outputPath);
            @unlink($slotsPath);
        }
    }

    private static function resolvePythonExecutable(): ?string
    {
        $candidates = ['python', 'python3', 'py'];
        foreach ($candidates as $candidate) {
            $cmd = PHP_OS_FAMILY === 'Windows'
                ? 'where ' . $candidate . ' 2>nul'
                : 'command -v ' . escapeshellarg($candidate) . ' 2>/dev/null';
            $path = shell_exec($cmd);
            if (! is_string($path)) {
                continue;
            }
            $path = trim(explode("\n", trim($path))[0] ?? '');
            if ($path !== '') {
                return $path;
            }
        }

        return null;
    }
}
