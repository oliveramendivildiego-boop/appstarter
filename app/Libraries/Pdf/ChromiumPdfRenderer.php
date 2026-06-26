<?php

namespace App\Libraries\Pdf;

use RuntimeException;

/**
 * Genera PDF con Chromium Headless (--print-to-pdf, sin capturas de pantalla).
 */
class ChromiumPdfRenderer implements PdfRendererInterface
{
    private const TOTAL_PAGES_TOKEN = '__PDF_TOTAL_PAGES__';

    public function renderHtml(string $html, PdfOptions $options): string
    {
        $sourceHtml            = $html;
        $needsFooterPagination = ChromiumPdfPaginationStamper::extractFooterPaginationSlots($html) !== [];

        $html = HtmlChromiumAdapter::adapt($html, $options);

        if ($needsFooterPagination) {
            $probePdf  = $this->renderAdaptedHtmlToPdf($html, $options);
            $pageCount = ChromiumPdfPaginationStamper::countPdfPages($probePdf);
            $html      = $this->injectPageCountIntoAdaptedHtml($html, $pageCount);
        }

        $pdf = $this->renderAdaptedHtmlToPdf($html, $options);

        if ($needsFooterPagination || str_contains($sourceHtml, 'data-order-sheet-from-page-two="1"')) {
            $pdf = ChromiumPdfPaginationStamper::stamp($pdf, $sourceHtml, $this->resolveTempDir());
        }

        return $pdf;
    }

    public function engineName(): string
    {
        return 'chromium';
    }

    private function injectPageCountIntoAdaptedHtml(string $html, int $pageCount): string
    {
        $pageCount = max(1, $pageCount);

        $html = str_replace(self::TOTAL_PAGES_TOKEN, (string) $pageCount, $html);

        return str_replace(
            'data-total="' . self::TOTAL_PAGES_TOKEN . '"',
            'data-total="' . $pageCount . '"',
            $html,
        );
    }

    private function renderAdaptedHtmlToPdf(string $html, PdfOptions $options): string
    {
        $tempDir  = $this->resolveTempDir();
        $htmlPath = $tempDir . DIRECTORY_SEPARATOR . 'input_' . bin2hex(random_bytes(8)) . '.html';
        $pdfPath  = $tempDir . DIRECTORY_SEPARATOR . 'output_' . bin2hex(random_bytes(8)) . '.pdf';

        if (file_put_contents($htmlPath, $html) === false) {
            throw new RuntimeException('No se pudo escribir el HTML temporal para Chromium.');
        }

        try {
            $this->runChromium($htmlPath, $pdfPath, $options);

            if (! is_file($pdfPath)) {
                throw new RuntimeException('Chromium no generó el archivo PDF.');
            }

            $binary = file_get_contents($pdfPath);
            if ($binary === false || $binary === '') {
                throw new RuntimeException('El PDF generado por Chromium está vacío.');
            }

            return $binary;
        } finally {
            @unlink($htmlPath);
            @unlink($pdfPath);
        }
    }

    private function runChromium(string $htmlPath, string $pdfPath, PdfOptions $options): void
    {
        $executable = $this->resolveExecutable();
        $fileUrl    = $this->pathToFileUrl($htmlPath);
        $timeout    = max(10, (int) (config('Pdf')->timeoutSeconds ?? 120));

        $args = [
            $executable,
            '--headless=new',
            '--disable-gpu',
            '--no-first-run',
            '--no-default-browser-check',
            '--disable-dev-shm-usage',
            '--run-all-compositor-stages-before-draw',
            '--virtual-time-budget=15000',
            '--print-to-pdf=' . $pdfPath,
            '--no-pdf-header-footer',
        ];

        if ($options->scale > 0 && abs($options->scale - 1.0) > 0.001) {
            $args[] = '--force-device-scale-factor=' . max(0.1, min(3.0, $options->scale));
        }

        $args[] = $fileUrl;

        $cmd = $this->buildCommandLine($args);
        $descriptors = [
            0 => ['pipe', 'r'],
            1 => ['pipe', 'w'],
            2 => ['pipe', 'w'],
        ];

        $process = proc_open($cmd, $descriptors, $pipes, $this->resolveTempDir());
        if (! is_resource($process)) {
            throw new RuntimeException('No se pudo iniciar el proceso Chromium.');
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

            if ((microtime(true) - $start) > $timeout) {
                $this->terminateProcess($process);
                proc_close($process);
                fclose($pipes[1]);
                fclose($pipes[2]);
                throw new RuntimeException('Chromium excedió el tiempo límite (' . $timeout . 's).');
            }

            usleep(100_000);
        }

        $stderr .= stream_get_contents($pipes[2]) ?: '';
        fclose($pipes[1]);
        fclose($pipes[2]);

        $exitCode = proc_close($process);
        if ($exitCode !== 0 && ! is_file($pdfPath)) {
            $msg = trim($stderr) !== '' ? trim($stderr) : 'código de salida ' . $exitCode;
            throw new RuntimeException('Chromium falló al generar el PDF: ' . $msg);
        }
    }

    private function terminateProcess($process): void
    {
        $status = proc_get_status($process);
        if (! empty($status['pid'])) {
            if (PHP_OS_FAMILY === 'Windows') {
                exec('taskkill /F /T /PID ' . (int) $status['pid']);
            } else {
                posix_kill((int) $status['pid'], SIGKILL);
            }
        }
    }

    /**
     * @param list<string> $args
     */
    private function buildCommandLine(array $args): string
    {
        return implode(' ', array_map(static fn (string $arg): string => escapeshellarg($arg), $args));
    }

    private function resolveExecutable(): string
    {
        $configured = trim((string) (config('Pdf')->executablePath ?? ''));
        if ($configured !== '' && $this->executablePathIsUsable($configured, true)) {
            return $configured;
        }

        foreach ($this->browserExecutableCandidates() as $path) {
            if ($this->executablePathIsUsable($path, false)) {
                return $path;
            }
        }

        throw new RuntimeException(
            'No se encontró Chrome/Chromium. Configure CHROME_EXECUTABLE_PATH en .env '
            . '(p. ej. C:\\Program Files\\Google\\Chrome\\Application\\chrome.exe).'
        );
    }

    /**
     * @return list<string>
     */
    private function browserExecutableCandidates(): array
    {
        if (PHP_OS_FAMILY !== 'Windows') {
            return [
                '/usr/bin/google-chrome',
                '/usr/bin/google-chrome-stable',
                '/usr/bin/chromium',
                '/usr/bin/chromium-browser',
                '/snap/bin/chromium',
            ];
        }

        $pf    = $this->windowsEnvPath('ProgramFiles', 'PROGRAMFILES');
        $pfX86 = $this->windowsEnvPath('ProgramFiles(x86)', 'PROGRAMFILES(X86)');
        $local = $this->windowsEnvPath('LOCALAPPDATA', 'LOCALAPPDATA');

        return array_values(array_unique(array_filter([
            $pf !== '' ? $pf . '\\Google\\Chrome\\Application\\chrome.exe' : '',
            $pfX86 !== '' ? $pfX86 . '\\Google\\Chrome\\Application\\chrome.exe' : '',
            $local !== '' ? $local . '\\Google\\Chrome\\Application\\chrome.exe' : '',
            'C:\\Program Files\\Google\\Chrome\\Application\\chrome.exe',
            'C:\\Program Files (x86)\\Google\\Chrome\\Application\\chrome.exe',
            'C:\\Program Files\\Chromium\\Application\\chrome.exe',
            $pf !== '' ? $pf . '\\Microsoft\\Edge\\Application\\msedge.exe' : '',
            $pfX86 !== '' ? $pfX86 . '\\Microsoft\\Edge\\Application\\msedge.exe' : '',
            'C:\\Program Files\\Microsoft\\Edge\\Application\\msedge.exe',
            'C:\\Program Files (x86)\\Microsoft\\Edge\\Application\\msedge.exe',
        ])));
    }

    private function windowsEnvPath(string $serverKey, string $getenvKey): string
    {
        $fromServer = trim((string) ($_SERVER[$serverKey] ?? ''));
        if ($fromServer !== '') {
            return rtrim(str_replace('/', '\\', $fromServer), '\\');
        }

        $fromEnv = getenv($getenvKey);
        if (is_string($fromEnv) && trim($fromEnv) !== '') {
            return rtrim(str_replace('/', '\\', trim($fromEnv)), '\\');
        }

        return '';
    }

    /**
     * @param bool $configured Si true, confía en la ruta aunque open_basedir bloquee is_file (típico en WAMP).
     */
    private function executablePathIsUsable(string $path, bool $configured): bool
    {
        $path = trim(str_replace('/', '\\', $path));
        if ($path === '') {
            return false;
        }

        if (@is_file($path)) {
            return true;
        }

        if ($configured) {
            return PHP_OS_FAMILY === 'Windows'
                ? (bool) preg_match('/\.exe$/i', $path)
                : is_executable($path);
        }

        // WAMP/open_basedir: is_file() falla fuera de www aunque Chrome exista; rutas canónicas siguen siendo válidas.
        if (PHP_OS_FAMILY === 'Windows' && preg_match('/\.exe$/i', $path)) {
            foreach ($this->browserExecutableCandidates() as $known) {
                if (strcasecmp(str_replace('/', '\\', $known), $path) === 0) {
                    return true;
                }
            }
        }

        return false;
    }

    private function resolveTempDir(): string
    {
        $dir = trim((string) (config('Pdf')->tempDir ?? ''));
        if ($dir === '') {
            $dir = WRITEPATH . 'cache' . DIRECTORY_SEPARATOR . 'chromium_pdf';
        }
        if (! is_dir($dir)) {
            @mkdir($dir, 0755, true);
        }

        return $dir;
    }

    private function pathToFileUrl(string $path): string
    {
        $real = realpath($path);
        if ($real === false) {
            throw new RuntimeException('Ruta HTML inválida para Chromium.');
        }

        $normalized = str_replace('\\', '/', $real);
        if (PHP_OS_FAMILY === 'Windows') {
            return 'file:///' . $normalized;
        }

        return 'file://' . $normalized;
    }
}
