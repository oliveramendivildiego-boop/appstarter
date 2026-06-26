<?php

namespace Config;

use CodeIgniter\Config\BaseConfig;

class Pdf extends BaseConfig
{
    /**
     * Motor de renderizado: chromium
     * Variable de entorno: PDF_RENDERER (solo chromium)
     */
    public string $renderer = 'chromium';

    /**
     * Ruta al ejecutable de Chrome/Chromium.
     * Variable de entorno: CHROME_EXECUTABLE_PATH
     * Vacío = autodetección (Windows / Linux).
     */
    public string $executablePath = '';

    /**
     * Timeout en segundos para el proceso Chromium.
     */
    public int $timeoutSeconds = 120;

    /**
     * Tamaño de papel por defecto si no viene del layout: letter | a4 | legal
     */
    public string $paperSize = 'letter';

    /**
     * portrait | landscape
     */
    public string $orientation = 'portrait';

    /**
     * Imprimir fondos y colores (printBackground).
     */
    public bool $printBackground = true;

    /**
     * Escala de renderizado (1.0 = 100%).
     */
    public float $scale = 1.0;

    /**
     * Márgenes opcionales en mm (null = usar @page del CSS del reporte).
     *
     * @var array{top: ?float, right: ?float, bottom: ?float, left: ?float}
     */
    public array $marginsMm = [
        'top'    => null,
        'right'  => null,
        'bottom' => null,
        'left'   => null,
    ];

    /**
     * Plantillas HTML/PDF de cabecera y pie para Chromium (vacío = desactivado).
     */
    public string $headerTemplate = '';

    public string $footerTemplate = '';

    /**
     * Directorio temporal para HTML/PDF de Chromium (vacío = writable/cache/chromium_pdf).
     */
    public string $tempDir = '';

    public function __construct()
    {
        parent::__construct();

        $envRenderer = env('PDF_RENDERER');
        if (is_string($envRenderer) && $envRenderer !== '') {
            $normalized = strtolower(trim($envRenderer));
            if ($normalized !== 'chromium' && $normalized !== 'chrome') {
                log_message('warning', 'PDF_RENDERER={val} ignorado; solo chromium está soportado.', ['val' => $normalized]);
            }
            $this->renderer = 'chromium';
        }

        $this->executablePath = self::readChromeOverrideFile();

        if ($this->executablePath === '') {
            $this->executablePath = self::readChromeExecutableFromEnvironment();
        }

        if ($this->executablePath === '') {
            $this->executablePath = self::discoverChromeViaShell();
        }

        if ($this->executablePath === '') {
            $this->executablePath = self::defaultChromeExecutableForPlatform();
        }

        $envTimeout = env('PDF_CHROMIUM_TIMEOUT');
        if (is_string($envTimeout) && is_numeric($envTimeout)) {
            $this->timeoutSeconds = max(10, (int) $envTimeout);
        }
    }

    /**
     * Ruta escrita por la instalación web (writable/cache/pdf_chrome_executable.txt).
     */
    public static function readChromeOverrideFile(): string
    {
        if (! defined('WRITEPATH')) {
            return '';
        }

        $file = WRITEPATH . 'cache' . DIRECTORY_SEPARATOR . 'pdf_chrome_executable.txt';
        if (! is_file($file)) {
            return '';
        }

        $path = trim(str_replace('\\', '/', (string) file_get_contents($file)));
        if ($path === '') {
            return '';
        }

        if (@is_file($path) || @is_executable($path)) {
            return $path;
        }

        return '';
    }

    /**
     * Lee CHROME_EXECUTABLE_PATH desde .env / variables de servidor (Apache a veces solo expone $_SERVER).
     */
    public static function readChromeExecutableFromEnvironment(): string
    {
        $candidates = [
            env('CHROME_EXECUTABLE_PATH'),
            $_ENV['CHROME_EXECUTABLE_PATH'] ?? null,
            $_SERVER['CHROME_EXECUTABLE_PATH'] ?? null,
        ];

        $fromGetenv = getenv('CHROME_EXECUTABLE_PATH');
        if (is_string($fromGetenv)) {
            $candidates[] = $fromGetenv;
        }

        foreach ($candidates as $value) {
            if (! is_string($value)) {
                continue;
            }

            $path = trim($value, " \t\"'");
            if ($path === '' || in_array(strtolower($path), ['false', '0', 'null', 'none'], true)) {
                continue;
            }

            if (self::looksLikeWindowsPath($path) && ! self::isWindowsPlatform()) {
                log_message(
                    'warning',
                    'CHROME_EXECUTABLE_PATH parece ruta de Windows en un servidor Linux; se ignora. Use /usr/bin/chromium-browser.',
                );
                continue;
            }

            return self::isWindowsPlatform()
                ? str_replace('/', '\\', $path)
                : str_replace('\\', '/', $path);
        }

        return '';
    }

    /**
     * Busca chromium en PATH (Apache/www-data suele tener PATH mínimo; probamos rutas absolutas).
     */
    public static function discoverChromeViaShell(): string
    {
        if (self::isWindowsPlatform()) {
            return '';
        }

        if (function_exists('shell_exec')) {
            $disabled = array_map('trim', explode(',', (string) ini_get('disable_functions')));
            if (! in_array('shell_exec', $disabled, true)) {
                foreach (['chromium-browser', 'chromium', 'google-chrome-stable', 'google-chrome'] as $bin) {
                    foreach (['command -v ', 'which '] as $prefix) {
                        $out = shell_exec($prefix . escapeshellarg($bin) . ' 2>/dev/null');
                        if (! is_string($out)) {
                            continue;
                        }
                        $path = trim(str_replace('\\', '/', $out));
                        if ($path !== '' && (@is_file($path) || @is_executable($path))) {
                            return $path;
                        }
                    }
                }
            }
        }

        foreach (self::linuxChromeCandidatePaths() as $path) {
            if (@is_file($path) || @is_executable($path)) {
                return $path;
            }
        }

        return '';
    }

    /**
     * Busca un ejecutable Chromium/Chrome instalado en Linux (solo rutas que existen).
     *
     * @return list<string>
     */
    public static function linuxChromeCandidatePaths(): array
    {
        $paths = [];
        if (defined('WRITEPATH')) {
            $portable = WRITEPATH . 'chrome' . DIRECTORY_SEPARATOR . 'chrome-linux64' . DIRECTORY_SEPARATOR . 'chrome';
            $paths[] = str_replace('\\', '/', $portable);
        }

        return array_merge($paths, [
            '/usr/bin/chromium-browser',
            '/usr/bin/chromium',
            '/usr/bin/google-chrome-stable',
            '/usr/bin/google-chrome',
            '/snap/bin/chromium',
            '/opt/google/chrome/google-chrome',
        ]);
    }

    public static function isWindowsPlatform(): bool
    {
        return PHP_OS_FAMILY === 'Windows' || DIRECTORY_SEPARATOR === '\\';
    }

    /**
     * Ruta por defecto si no hay variable de entorno (solo si el binario existe).
     */
    public static function defaultChromeExecutableForPlatform(): string
    {
        if (self::isWindowsPlatform()) {
            return 'C:\\Program Files\\Google\\Chrome\\Application\\chrome.exe';
        }

        foreach (self::linuxChromeCandidatePaths() as $path) {
            if (@is_file($path) || @is_executable($path)) {
                return $path;
            }
        }

        return '';
    }

    private static function looksLikeWindowsPath(string $path): bool
    {
        return (bool) preg_match('#^[A-Za-z]:[/\\\\]#', $path);
    }
}
