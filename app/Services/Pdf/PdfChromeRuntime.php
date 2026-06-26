<?php

namespace App\Services\Pdf;

/**
 * Entorno de ejecución para Chromium portable en Linux (LD_LIBRARY_PATH).
 */
class PdfChromeRuntime
{
    private const LIBS_DIR = 'chrome' . DIRECTORY_SEPARATOR . 'libs';

    /**
     * Directorio con .so empaquetados por la instalación web.
     */
    public static function bundledLibsDir(): string
    {
        if (! defined('WRITEPATH')) {
            return '';
        }

        return str_replace('\\', '/', WRITEPATH . self::LIBS_DIR);
    }

    /**
     * Directorios donde Chrome for Testing incluye bibliotecas propias.
     *
     * @return list<string>
     */
    public static function chromeBundledLibDirs(string $executable): array
    {
        $executable = str_replace('\\', '/', trim($executable));
        if ($executable === '') {
            return [];
        }

        $dirs = [];
        $base = dirname($executable);
        foreach ([$base, $base . '/lib'] as $dir) {
            if (is_dir($dir)) {
                $dirs[] = $dir;
            }
        }

        return array_values(array_unique($dirs));
    }

    /**
     * Valor para LD_LIBRARY_PATH (vacío = no hace falta en Windows o sin libs empaquetadas).
     */
    public static function buildLdLibraryPath(string $executable = ''): string
    {
        if (PHP_OS_FAMILY === 'Windows') {
            return '';
        }

        $parts = [];
        $bundled = self::bundledLibsDir();
        if ($bundled !== '' && is_dir($bundled)) {
            $parts[] = $bundled;
        }

        if ($executable !== '') {
            foreach (self::chromeBundledLibDirs($executable) as $dir) {
                $parts[] = $dir;
            }
        }

        $existing = getenv('LD_LIBRARY_PATH');
        if (is_string($existing) && trim($existing) !== '') {
            $parts[] = trim($existing);
        }

        return implode(':', array_unique(array_filter($parts)));
    }

    /**
     * @return array<string, string>|null
     */
    public static function processEnvironment(string $executable = ''): ?array
    {
        if (PHP_OS_FAMILY === 'Windows') {
            return null;
        }

        $ld = self::buildLdLibraryPath($executable);
        if ($ld === '') {
            return null;
        }

        $env = getenv();
        if (! is_array($env)) {
            $env = [];
        }

        $env['LD_LIBRARY_PATH'] = $ld;

        return $env;
    }

    public static function isPortableExecutable(string $executable): bool
    {
        if (! defined('WRITEPATH')) {
            return false;
        }

        return str_contains(str_replace('\\', '/', $executable), str_replace('\\', '/', WRITEPATH . 'chrome'));
    }
}
