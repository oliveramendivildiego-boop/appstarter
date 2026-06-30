<?php

namespace App\Libraries\Pdf;

/**
 * Directorio temporal y caché de fuentes mPDF (writable/cache/mpdf/mpdf/ttfontdata).
 */
final class MpdfTempDirSupport
{
    public static function resolveTempDir(): string
    {
        $tempDir = trim((string) (config('Pdf')->mpdfTempDir ?? ''));
        if ($tempDir === '') {
            $tempDir = WRITEPATH . 'cache' . DIRECTORY_SEPARATOR . 'mpdf';
        }

        return rtrim($tempDir, '\\/');
    }

    public static function fontCacheDir(string $tempDir): string
    {
        return rtrim($tempDir, '\\/') . DIRECTORY_SEPARATOR . 'mpdf' . DIRECTORY_SEPARATOR . 'ttfontdata';
    }

    /**
     * mPDF escribe bajo tempDir/mpdf/ttfontdata; en hosting compartido suele fallar si falta o no es escribible.
     */
    public static function ensureWritableTree(?string $tempDir = null): void
    {
        $tempDir = $tempDir ?? self::resolveTempDir();
        $paths   = [
            $tempDir,
            $tempDir . DIRECTORY_SEPARATOR . 'mpdf',
            self::fontCacheDir($tempDir),
        ];

        foreach ($paths as $path) {
            if (is_dir($path)) {
                continue;
            }
            @mkdir($path, 0775, true);
        }
    }

    public static function isWritable(string $tempDir): bool
    {
        self::ensureWritableTree($tempDir);

        return is_dir($tempDir) && is_writable($tempDir)
            && is_dir(self::fontCacheDir($tempDir)) && is_writable(self::fontCacheDir($tempDir));
    }

    public static function clearFontCache(?string $tempDir = null): void
    {
        $ttDir = self::fontCacheDir($tempDir ?? self::resolveTempDir());
        if (! is_dir($ttDir)) {
            return;
        }

        foreach (glob($ttDir . DIRECTORY_SEPARATOR . '*') ?: [] as $entry) {
            if (is_file($entry)) {
                @unlink($entry);
            }
        }
    }

    public static function isRecoverableFontCacheError(\Throwable $e): bool
    {
        $message = strtolower($e->getMessage());
        $file    = strtolower($e->getFile());

        if (str_contains($message, 'ttfontdata')
            || str_contains($message, 'gsubgpostables')
            || str_contains($file, 'ttfontdata')) {
            return true;
        }

        if (str_contains($message, 'failed to open stream')
            && (str_contains($message, 'mpdf') || str_contains($message, '.dat'))) {
            return true;
        }

        if (str_contains($message, 'undefined variable $cw')) {
            return true;
        }

        return false;
    }
}
