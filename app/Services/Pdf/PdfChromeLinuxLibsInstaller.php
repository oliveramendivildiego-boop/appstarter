<?php

namespace App\Services\Pdf;

use RuntimeException;

/**
 * Empaqueta bibliotecas .so de Ubuntu amd64 en writable/chrome/libs (hosting sin apt/sudo).
 */
class PdfChromeLinuxLibsInstaller
{
    /** @var list<string> */
    private const DEB_URLS = [
        'http://archive.ubuntu.com/ubuntu/pool/main/a/at-spi2-atk/libatk-bridge2.0-0_2.38.0-3_amd64.deb',
        'http://archive.ubuntu.com/ubuntu/pool/main/a/at-spi2-core/libatspi2.0-0_2.44.0-3_amd64.deb',
        'http://archive.ubuntu.com/ubuntu/pool/main/a/atk1.0/libatk1.0-0_2.36.0-3build1_amd64.deb',
        'http://archive.ubuntu.com/ubuntu/pool/main/c/cups/libcups2_2.4.1op1-1ubuntu4.12_amd64.deb',
        'http://archive.ubuntu.com/ubuntu/pool/main/d/dbus/libdbus-1-3_1.12.20-2ubuntu4.1_amd64.deb',
        'http://archive.ubuntu.com/ubuntu/pool/main/libd/libdrm/libdrm2_2.4.113-2ubuntu1_amd64.deb',
        'http://archive.ubuntu.com/ubuntu/pool/main/m/mesa/libgbm1_23.2.1-1ubuntu3.1~22.04.3_amd64.deb',
        'http://archive.ubuntu.com/ubuntu/pool/main/g/glib2.0/libglib2.0-0_2.72.4-0ubuntu2.6_amd64.deb',
        'http://archive.ubuntu.com/ubuntu/pool/main/g/gtk+3.0/libgtk-3-0_3.24.33-1ubuntu2.2_amd64.deb',
        'http://archive.ubuntu.com/ubuntu/pool/main/n/nspr/libnspr4_4.35-0ubuntu0.22.04.1_amd64.deb',
        'http://archive.ubuntu.com/ubuntu/pool/main/n/nss/libnss3_3.98-0ubuntu0.22.04.2_amd64.deb',
        'http://archive.ubuntu.com/ubuntu/pool/main/p/pango1.0/libpango-1.0-0_1.50.6+ds-2ubuntu1_amd64.deb',
        'http://archive.ubuntu.com/ubuntu/pool/main/libx/libxcomposite/libxcomposite1_0.4.5-1build2_amd64.deb',
        'http://archive.ubuntu.com/ubuntu/pool/main/libx/libxdamage/libxdamage1_1.1.5-2build2_amd64.deb',
        'http://archive.ubuntu.com/ubuntu/pool/main/libx/libxfixes/libxfixes3_6.0.0-1_amd64.deb',
        'http://archive.ubuntu.com/ubuntu/pool/main/x/xkbcommon/libxkbcommon0_1.4.0-1_amd64.deb',
        'http://archive.ubuntu.com/ubuntu/pool/main/x/xorg/libxrandr2_1.5.2-1build1_amd64.deb',
        'http://archive.ubuntu.com/ubuntu/pool/main/a/alsa-lib/libasound2_1.2.6.1-1ubuntu1_amd64.deb',
        'http://archive.ubuntu.com/ubuntu/pool/main/c/cairo/libcairo2_1.16.0-5ubuntu2_amd64.deb',
        'http://archive.ubuntu.com/ubuntu/pool/main/e/expat/libexpat1_2.4.7-1ubuntu0.6_amd64.deb',
        'http://archive.ubuntu.com/ubuntu/pool/main/f/fontconfig/libfontconfig1_2.13.1-4.2ubuntu5_amd64.deb',
        'http://archive.ubuntu.com/ubuntu/pool/main/f/freetype/libfreetype6_2.11.1+dfsg-1ubuntu0.3_amd64.deb',
        'http://archive.ubuntu.com/ubuntu/pool/main/libx/libxshmfence/libxshmfence1_1.3-1build4_amd64.deb',
        'http://archive.ubuntu.com/ubuntu/pool/main/w/wayland/libwayland-client0_1.20.0-1ubuntu0.1_amd64.deb',
    ];

    /**
     * @return array{success: bool, message: string, libs_dir: string, copied: int, steps: list<string>}
     */
    public function install(): array
    {
        if (PHP_OS_FAMILY === 'Windows') {
            return [
                'success'  => true,
                'message'  => 'No aplica en Windows.',
                'libs_dir' => '',
                'copied'   => 0,
                'steps'    => [],
            ];
        }

        if (! defined('WRITEPATH')) {
            throw new RuntimeException('WRITEPATH no definido.');
        }

        $libsDir = PdfChromeRuntime::bundledLibsDir();
        if ($libsDir === '') {
            throw new RuntimeException('No se pudo resolver el directorio de bibliotecas.');
        }

        if (! is_dir($libsDir) && ! @mkdir($libsDir, 0755, true) && ! is_dir($libsDir)) {
            throw new RuntimeException('No se pudo crear ' . $libsDir);
        }

        $debDir = WRITEPATH . 'chrome' . DIRECTORY_SEPARATOR . 'deb-cache';
        if (! is_dir($debDir) && ! @mkdir($debDir, 0755, true) && ! is_dir($debDir)) {
            throw new RuntimeException('No se pudo crear ' . $debDir);
        }

        $steps   = [];
        $copied  = 0;
        $skipped = 0;

        foreach (self::DEB_URLS as $url) {
            $name = basename(parse_url($url, PHP_URL_PATH) ?: 'pkg.deb');
            $debPath = $debDir . DIRECTORY_SEPARATOR . $name;

            try {
                if (! is_file($debPath) || filesize($debPath) < 1000) {
                    $this->downloadFile($url, $debPath);
                    $steps[] = 'Descargado ' . $name;
                }

                $added = $this->extractDebSharedObjects($debPath, $libsDir);
                $copied += $added;
                if ($added > 0) {
                    $steps[] = $name . ': ' . $added . ' biblioteca(s)';
                }
            } catch (\Throwable $e) {
                $skipped++;
                $steps[] = 'AVISO ' . $name . ': ' . $e->getMessage();
            }
        }

        $hasLibs = $this->countSharedObjects($libsDir) > 0;

        return [
            'success'  => $hasLibs,
            'message'  => $hasLibs
                ? 'Bibliotecas empaquetadas en writable/chrome/libs'
                : 'No se pudieron empaquetar bibliotecas del sistema.',
            'libs_dir' => $libsDir,
            'copied'   => $copied,
            'steps'    => $steps,
        ];
    }

    private function countSharedObjects(string $dir): int
    {
        $n = 0;
        foreach (glob($dir . '/*.so*') ?: [] as $file) {
            if (is_file($file)) {
                $n++;
            }
        }

        return $n;
    }

    private function extractDebSharedObjects(string $debPath, string $libsDir): int
    {
        $work = WRITEPATH . 'cache' . DIRECTORY_SEPARATOR . 'deb-extract-' . bin2hex(random_bytes(4));
        if (! @mkdir($work, 0755, true) && ! is_dir($work)) {
            throw new RuntimeException('No se pudo crear directorio temporal.');
        }

        try {
            if ($this->extractDebWithShell($debPath, $work)) {
                return $this->collectSharedObjectsFromTree($work, $libsDir);
            }

            if ($this->extractDebPurePhp($debPath, $work)) {
                return $this->collectSharedObjectsFromTree($work, $libsDir);
            }

            throw new RuntimeException('No se pudo extraer el paquete .deb');
        } finally {
            $this->removeDirectory($work);
        }
    }

    private function extractDebWithShell(string $debPath, string $workDir): bool
    {
        if (! function_exists('exec')) {
            return false;
        }

        $disabled = array_map('trim', explode(',', (string) ini_get('disable_functions')));
        if (in_array('exec', $disabled, true)) {
            return false;
        }

        $deb = escapeshellarg($debPath);
        $wd  = escapeshellarg($workDir);
        $cmd = 'cd ' . $wd . ' && ar x ' . $deb
            . ' && (tar xf data.tar.xz 2>/dev/null || tar xf data.tar.zst 2>/dev/null || tar xf data.tar.gz 2>/dev/null || tar xf data.tar 2>/dev/null)';

        @exec($cmd, $out, $code);

        return $code === 0 && (is_dir($workDir . '/usr/lib') || is_dir($workDir . '/lib'));
    }

    private function extractDebPurePhp(string $debPath, string $workDir): bool
    {
        $dataTar = $this->readDebDataTarBytes($debPath);
        if ($dataTar === null) {
            return false;
        }

        $tarPath = $workDir . DIRECTORY_SEPARATOR . 'data.tar';
        if (@file_put_contents($tarPath, $dataTar) === false) {
            return false;
        }

        if (class_exists(\PharData::class)) {
            try {
                $phar = new \PharData($tarPath);
                $phar->extractTo($workDir, null, true);

                return is_dir($workDir . '/usr/lib') || is_dir($workDir . '/lib');
            } catch (\Throwable) {
                // gzip/xz puede fallar según extensión
            }
        }

        return false;
    }

    private function readDebDataTarBytes(string $debPath): ?string
    {
        $fp = @fopen($debPath, 'rb');
        if ($fp === false) {
            return null;
        }

        $header = fread($fp, 8);
        if ($header !== "!<arch>\n") {
            fclose($fp);

            return null;
        }

        while (! feof($fp)) {
            $entryHeader = fread($fp, 60);
            if ($entryHeader === false || strlen($entryHeader) < 60) {
                break;
            }

            $name = trim(substr($entryHeader, 0, 16));
            $size = (int) trim(substr($entryHeader, 48, 10));
            if ($size <= 0) {
                continue;
            }

            $payload = fread($fp, $size);
            if ($payload === false) {
                break;
            }

            if (strlen($payload) % 2 === 1) {
                fread($fp, 1);
            }

            if ($name === 'data.tar.gz' || str_starts_with($name, 'data.tar.gz')) {
                fclose($fp);
                $decoded = @gzdecode($payload);

                return is_string($decoded) ? $decoded : null;
            }

            if ($name === 'data.tar.xz' || str_starts_with($name, 'data.tar.xz')) {
                fclose($fp);

                if (function_exists('xzdecode')) {
                    $decoded = @xzdecode($payload);

                    return is_string($decoded) ? $decoded : null;
                }

                return null;
            }

            if ($name === 'data.tar' || str_starts_with($name, 'data.tar')) {
                fclose($fp);

                return $payload;
            }
        }

        fclose($fp);

        return null;
    }

    private function collectSharedObjectsFromTree(string $root, string $libsDir): int
    {
        $copied = 0;
        $patterns = [
            $root . '/usr/lib/x86_64-linux-gnu',
            $root . '/usr/lib64',
            $root . '/lib/x86_64-linux-gnu',
        ];

        foreach ($patterns as $dir) {
            if (! is_dir($dir)) {
                continue;
            }

            foreach (scandir($dir) ?: [] as $item) {
                if ($item === '.' || $item === '..') {
                    continue;
                }
                if (! preg_match('/\.so(\.[0-9]+)*$/', $item)) {
                    continue;
                }

                $src = $dir . DIRECTORY_SEPARATOR . $item;
                if (! is_file($src)) {
                    continue;
                }

                $dest = $libsDir . DIRECTORY_SEPARATOR . $item;
                if (@copy($src, $dest)) {
                    @chmod($dest, 0755);
                    $copied++;
                }
            }
        }

        return $copied;
    }

    private function downloadFile(string $url, string $dest): void
    {
        if (function_exists('curl_init')) {
            $fp = @fopen($dest, 'wb');
            if ($fp !== false) {
                $ch = curl_init($url);
                if ($ch !== false) {
                    curl_setopt_array($ch, [
                        CURLOPT_FILE           => $fp,
                        CURLOPT_FOLLOWLOCATION => true,
                        CURLOPT_TIMEOUT        => 120,
                        CURLOPT_FAILONERROR    => true,
                    ]);
                    $ok = curl_exec($ch);
                    curl_close($ch);
                    fclose($fp);
                    if ($ok === true && is_file($dest) && filesize($dest) > 500) {
                        return;
                    }
                }
                @fclose($fp);
            }
            @unlink($dest);
        }

        $ctx = stream_context_create(['http' => ['timeout' => 120]]);
        $raw = @file_get_contents($url, false, $ctx);
        if (! is_string($raw) || strlen($raw) < 500) {
            throw new RuntimeException('Descarga fallida: ' . basename($dest));
        }
        if (@file_put_contents($dest, $raw) === false) {
            throw new RuntimeException('No se pudo guardar ' . $dest);
        }
    }

    private function removeDirectory(string $dir): void
    {
        if (! is_dir($dir)) {
            return;
        }

        foreach (scandir($dir) ?: [] as $item) {
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
