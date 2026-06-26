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
        $exe    = $this->resolveBestExecutable();
        $ready  = $exe !== '' && $this->executableUsable($exe);

        return [
            'ready'      => $ready,
            'executable' => $exe,
            'source'     => $this->describeSource($exe),
            'message'    => $ready
                ? 'Chromium listo para generar PDF.'
                : 'Chromium no configurado. Use el botón «Instalar PDF / Chromium».',
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

        $steps = [];

        $existing = $this->resolveBestExecutable();
        if ($existing !== '' && $this->executableUsable($existing)) {
            $this->persistExecutablePath($existing);
            $steps[] = 'Chromium ya disponible: ' . $existing;

            return [
                'success'    => true,
                'message'    => 'Chromium ya estaba instalado. PDF listo.',
                'executable' => $existing,
                'steps'      => $steps,
            ];
        }

        if (! PdfConfig::isWindowsPlatform()) {
            $steps[] = 'Descargando Chromium portable (puede tardar varios minutos)…';
            try {
                $portable = $this->installPortableChromeLinux();
                $steps[]  = 'Instalado en: ' . $portable;
                $this->persistExecutablePath($portable);

                if ($this->testHeadlessPrint($portable)) {
                    $steps[] = 'Prueba headless OK';
                } else {
                    $steps[] = 'AVISO: prueba headless falló; pruebe un reporte PDF igualmente';
                }

                return [
                    'success'    => true,
                    'message'    => 'Chromium instalado correctamente. Recargue un reporte PDF.',
                    'executable' => $portable,
                    'steps'      => $steps,
                ];
            } catch (\Throwable $e) {
                $steps[] = 'Error portable: ' . $e->getMessage();
            }
        }

        return [
            'success'    => false,
            'message'    => 'No se pudo instalar Chromium automáticamente. '
                . implode(' ', $steps),
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

        if ($ok !== true || ! is_file($dest) || filesize($dest) < 1_000_000) {
            @unlink($dest);
            throw new RuntimeException('Descarga de Chromium incompleta.');
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
        $pdf = WRITEPATH . 'cache' . DIRECTORY_SEPARATOR . 'chrome_setup_test.pdf';
        $cmd = escapeshellarg($chrome)
            . ' --headless=new --disable-gpu --no-sandbox --print-to-pdf='
            . escapeshellarg($pdf)
            . ' about:blank 2>/dev/null';

        @exec($cmd, $out, $code);
        if (is_file($pdf) && filesize($pdf) > 100) {
            @unlink($pdf);

            return true;
        }

        return false;
    }

    private function describeSource(string $exe): string
    {
        if ($exe === '') {
            return 'none';
        }
        if (str_contains($exe, WRITEPATH . 'chrome')) {
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
