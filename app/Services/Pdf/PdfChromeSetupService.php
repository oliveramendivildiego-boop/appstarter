<?php

namespace App\Services\Pdf;

use Config\Pdf as PdfConfig;
use RuntimeException;
use ZipArchive;

/**
 * Instala/configura Chromium para PDF sin SSH (descarga portable en writable/ o detecta el del sistema).
 */
class PdfChromeSetupService
{
    private const OVERRIDE_FILE = 'pdf_chrome_executable.txt';

    private const PORTABLE_DIR = 'chrome' . DIRECTORY_SEPARATOR . 'chrome-linux64';

    private const CHROME_FOR_TESTING_JSON = 'https://googlechromelabs.github.io/chrome-for-testing/last-known-good-versions-with-downloads.json';

    /**
     * @return array{ready: bool, executable: string, source: string, message: string, platform: string}
     */
    public function status(): array
    {
        $exe   = $this->resolveBestExecutable();
        $ready = $exe !== '' && $this->executableUsable($exe);

        if ($ready && ! PdfConfig::isWindowsPlatform() && PdfChromeRuntime::isPortableExecutable($exe)) {
            $ready = $this->testHeadlessPrint($exe);
        }

        $libsDir = PdfChromeRuntime::bundledLibsDir();
        $libsCount = ($libsDir !== '' && is_dir($libsDir)) ? count(glob($libsDir . '/*.so*') ?: []) : 0;

        return [
            'ready'      => $ready,
            'executable' => $exe,
            'source'     => $this->describeSource($exe),
            'libs_count' => $libsCount,
            'message'    => $ready
                ? 'Chromium listo para generar PDF.'
                : ($exe !== '' && PdfChromeRuntime::isPortableExecutable($exe)
                    ? 'Chromium instalado pero faltan bibliotecas. Pulse «Instalar PDF / Chromium» de nuevo.'
                    : 'Chromium no configurado. Use el botón «Instalar PDF / Chromium».'),
            'platform'   => PHP_OS_FAMILY,
        ];
    }

    /**
     * @return array{success: bool, message: string, executable: string, steps: list<string>}
     */
    public function ensureReady(): array
    {
        @set_time_limit(600);
        @ini_set('memory_limit', '512M');

        $steps      = [];
        $executable = $this->resolveBestExecutable();

        if (! PdfConfig::isWindowsPlatform()) {
            if ($executable === '' || ! $this->executableUsable($executable)) {
                $steps[] = 'Descargando Chromium portable (puede tardar varios minutos)…';
                try {
                    $executable = $this->installPortableChromeLinux();
                    $steps[]    = 'Chromium instalado en: ' . $executable;
                } catch (\Throwable $e) {
                    $steps[] = 'Error Chromium: ' . $e->getMessage();

                    return [
                        'success'    => false,
                        'message'    => 'No se pudo instalar Chromium. ' . $e->getMessage(),
                        'executable' => '',
                        'steps'      => $steps,
                    ];
                }
            } else {
                $steps[] = 'Chromium ya presente: ' . $executable;
            }

            $steps[] = 'Empaquetando bibliotecas del sistema (libatk, libnss, etc.)…';
            $libResult = (new PdfChromeLinuxLibsInstaller())->install();
            $steps     = array_merge($steps, $libResult['steps'] ?? []);
            if (! ($libResult['success'] ?? false)) {
                $steps[] = 'AVISO: ' . ($libResult['message'] ?? 'bibliotecas incompletas');
            }

            if ($this->testHeadlessPrint($executable)) {
                $this->persistExecutablePath($executable);
                $steps[] = 'Prueba headless OK';

                return [
                    'success'    => true,
                    'message'    => 'Chromium y bibliotecas listos. Recargue un reporte PDF.',
                    'executable' => $executable,
                    'steps'      => $steps,
                ];
            }

            $steps[] = 'La prueba headless falló; revise permisos exec y espacio en writable/';

            return [
                'success'    => false,
                'message'    => 'Chromium está instalado pero no arranca. Pida al hosting instalar '
                    . 'chromium-browser o las bibliotecas GTK/ATK del sistema.',
                'executable' => $executable,
                'steps'      => $steps,
            ];
        }

        if ($executable !== '' && $this->executableUsable($executable)) {
            $this->persistExecutablePath($executable);

            return [
                'success'    => true,
                'message'    => 'Chromium ya estaba instalado. PDF listo.',
                'executable' => $executable,
                'steps'      => $steps,
            ];
        }

        return [
            'success'    => false,
            'message'    => 'No se pudo configurar Chromium en este entorno.',
            'executable' => '',
            'steps'      => $steps,
        ];
    }

    public function resolveBestExecutable(): string
    {
        $override = $this->readOverridePath();
        if ($override !== '') {
            return $override;
        }

        $fromEnv = PdfConfig::readChromeExecutableFromEnvironment();
        if ($fromEnv !== '' && $this->executableUsable($fromEnv)) {
            return $fromEnv;
        }

        $portable = $this->portableChromePath();
        if ($portable !== '' && $this->executableUsable($portable)) {
            return $portable;
        }

        $discovered = PdfConfig::discoverChromeViaShell();
        if ($discovered !== '') {
            return $discovered;
        }

        return PdfConfig::defaultChromeExecutableForPlatform();
    }

    private function portableChromePath(): string
    {
        if (! defined('WRITEPATH')) {
            return '';
        }

        $base = WRITEPATH . self::PORTABLE_DIR;
        foreach (['chrome', 'google-chrome'] as $name) {
            $path = $base . DIRECTORY_SEPARATOR . $name;
            if ($this->executableUsable($path)) {
                return $path;
            }
        }

        return '';
    }

    private function installPortableChromeLinux(): string
    {
        if (! class_exists(ZipArchive::class)) {
            throw new RuntimeException('Falta la extensión PHP zip (ZipArchive).');
        }

        $url = $this->resolveChromeForTestingDownloadUrl();
        $dir = WRITEPATH . 'chrome';
        if (! is_dir($dir) && ! @mkdir($dir, 0755, true) && ! is_dir($dir)) {
            throw new RuntimeException('No se pudo crear ' . $dir);
        }

        $zipPath = $dir . DIRECTORY_SEPARATOR . 'chrome-linux64.zip';
        $this->downloadFile($url, $zipPath);

        $zip = new ZipArchive();
        if ($zip->open($zipPath) !== true) {
            throw new RuntimeException('No se pudo abrir el ZIP descargado.');
        }

        $target = WRITEPATH . self::PORTABLE_DIR;
        if (is_dir($target)) {
            $this->removeDirectory($target);
        }

        if (! $zip->extractTo($dir)) {
            $zip->close();
            throw new RuntimeException('No se pudo extraer Chromium.');
        }
        $zip->close();
        @unlink($zipPath);

        $chrome = $this->portableChromePath();
        if ($chrome === '') {
            throw new RuntimeException('Chromium extraído pero no se encontró el ejecutable.');
        }

        @chmod($chrome, 0755);

        return $chrome;
    }

    private function resolveChromeForTestingDownloadUrl(): string
    {
        $ctx = stream_context_create(['http' => ['timeout' => 30]]);
        $raw = @file_get_contents(self::CHROME_FOR_TESTING_JSON, false, $ctx);
        if (! is_string($raw) || $raw === '') {
            throw new RuntimeException('No se pudo consultar Chrome for Testing.');
        }

        $data = json_decode($raw, true);
        $downloads = $data['channels']['Stable']['downloads']['chrome'] ?? null;
        if (! is_array($downloads)) {
            throw new RuntimeException('Respuesta inválida de Chrome for Testing.');
        }

        foreach ($downloads as $item) {
            if (is_array($item) && ($item['platform'] ?? '') === 'linux64' && ! empty($item['url'])) {
                return (string) $item['url'];
            }
        }

        throw new RuntimeException('No hay build linux64 en Chrome for Testing.');
    }

    private function downloadFile(string $url, string $dest): void
    {
        if (function_exists('curl_init')) {
            $fp = @fopen($dest, 'wb');
            if ($fp === false) {
                throw new RuntimeException('No se pudo escribir en ' . $dest);
            }

            $ch = curl_init($url);
            if ($ch === false) {
                fclose($fp);
                throw new RuntimeException('curl no disponible.');
            }

            curl_setopt_array($ch, [
                CURLOPT_FILE           => $fp,
                CURLOPT_FOLLOWLOCATION => true,
                CURLOPT_TIMEOUT        => 600,
                CURLOPT_FAILONERROR    => true,
            ]);

            $ok = curl_exec($ch);
            curl_close($ch);
            fclose($fp);

            if ($ok === true && is_file($dest) && filesize($dest) > 1_000_000) {
                return;
            }
            @unlink($dest);
        }

        $ctx = stream_context_create(['http' => ['timeout' => 600]]);
        $raw = @file_get_contents($url, false, $ctx);
        if (! is_string($raw) || strlen($raw) < 1_000_000) {
            throw new RuntimeException('Descarga de Chromium incompleta.');
        }
        if (@file_put_contents($dest, $raw) === false) {
            throw new RuntimeException('No se pudo guardar Chromium en ' . $dest);
        }
    }

    private function persistExecutablePath(string $path): void
    {
        $path = str_replace('\\', '/', trim($path));
        $cacheFile = WRITEPATH . 'cache' . DIRECTORY_SEPARATOR . self::OVERRIDE_FILE;
        $cacheDir  = dirname($cacheFile);
        if (! is_dir($cacheDir)) {
            @mkdir($cacheDir, 0755, true);
        }
        @file_put_contents($cacheFile, $path, LOCK_EX);

        $this->updateDotEnvChromePath($path);
    }

    private function updateDotEnvChromePath(string $path): void
    {
        $envFile = ROOTPATH . '.env';
        if (! is_file($envFile) || ! is_writable($envFile)) {
            return;
        }

        $line = 'CHROME_EXECUTABLE_PATH=' . $path;
        $content = file_get_contents($envFile);
        if ($content === false) {
            return;
        }

        if (preg_match('/^CHROME_EXECUTABLE_PATH\s*=/m', $content)) {
            $content = preg_replace('/^CHROME_EXECUTABLE_PATH\s*=.*$/m', $line, $content) ?? $content;
        } else {
            $content = rtrim($content) . "\n\n# PDF Chromium\n" . $line . "\n";
        }

        @file_put_contents($envFile, $content, LOCK_EX);
    }

    private function readOverridePath(): string
    {
        if (! defined('WRITEPATH')) {
            return '';
        }

        $file = WRITEPATH . 'cache' . DIRECTORY_SEPARATOR . self::OVERRIDE_FILE;
        if (! is_file($file)) {
            return '';
        }

        return trim(str_replace('\\', '/', (string) file_get_contents($file)));
    }

    private function executableUsable(string $path): bool
    {
        $path = trim($path);
        if ($path === '') {
            return false;
        }

        if (@is_file($path) || @is_executable($path)) {
            return true;
        }

        return PdfConfig::isWindowsPlatform() && preg_match('/\.exe$/i', $path) === 1;
    }

    private function testHeadlessPrint(string $chrome): bool
    {
        if (! defined('WRITEPATH')) {
            return false;
        }

        $pdf = WRITEPATH . 'cache' . DIRECTORY_SEPARATOR . 'chrome_setup_test.pdf';
        @unlink($pdf);

        $args = [
            $chrome,
            '--headless=new',
            '--disable-gpu',
            '--no-sandbox',
            '--disable-dev-shm-usage',
            '--print-to-pdf=' . $pdf,
            'about:blank',
        ];

        $cmd = implode(' ', array_map(static fn (string $arg): string => escapeshellarg($arg), $args));
        $descriptors = [
            0 => ['pipe', 'r'],
            1 => ['pipe', 'w'],
            2 => ['pipe', 'w'],
        ];

        $env     = PdfChromeRuntime::processEnvironment($chrome);
        $process = proc_open($cmd, $descriptors, $pipes, WRITEPATH . 'cache', $env);
        if (! is_resource($process)) {
            return false;
        }

        fclose($pipes[0]);
        stream_set_blocking($pipes[1], false);
        stream_set_blocking($pipes[2], false);

        $stderr = '';
        $start  = microtime(true);
        while (true) {
            $status = proc_get_status($process);
            $stderr .= stream_get_contents($pipes[2]) ?: '';
            if (! $status['running']) {
                break;
            }
            if ((microtime(true) - $start) > 60) {
                proc_terminate($process);
                break;
            }
            usleep(100_000);
        }

        fclose($pipes[1]);
        fclose($pipes[2]);
        proc_close($process);

        if (is_file($pdf) && filesize($pdf) > 100) {
            @unlink($pdf);

            return true;
        }

        if ($stderr !== '') {
            log_message('error', 'PdfChromeSetup test headless: {err}', ['err' => trim($stderr)]);
        }

        return false;
    }

    private function describeSource(string $exe): string
    {
        if ($exe === '') {
            return 'none';
        }
        if (defined('WRITEPATH') && str_contains($exe, WRITEPATH . 'chrome')) {
            return 'portable';
        }

        return 'system';
    }

    private function removeDirectory(string $dir): void
    {
        if (! is_dir($dir)) {
            return;
        }

        $items = scandir($dir);
        if ($items === false) {
            return;
        }

        foreach ($items as $item) {
            if ($item === '.' || $item === '..') {
                continue;
            }
            $path = $dir . DIRECTORY_SEPARATOR . $item;
            if (is_dir($path)) {
                $this->removeDirectory($path);
            } else {
                @unlink($path);
            }
        }
        @rmdir($dir);
    }
}
