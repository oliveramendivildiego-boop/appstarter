<?php

namespace App\Services;

/**
 * Limpia el contenido de writable/cache (caché de archivos, PDFs de vista previa, etc.).
 */
class WritableCachePurgeService
{
    /** @var list<string> */
    private const PRESERVE_BASENAMES = ['index.html', '.gitkeep'];

    public function getCacheRoot(): string
    {
        return rtrim(WRITEPATH, '/\\') . DIRECTORY_SEPARATOR . 'cache';
    }

    /**
     * @return array{file_count: int, dir_count: int, bytes: int}
     */
    public function measure(): array
    {
        $root = $this->getCacheRoot();
        if (! is_dir($root)) {
            return ['file_count' => 0, 'dir_count' => 0, 'bytes' => 0];
        }

        $fileCount = 0;
        $dirCount  = 0;
        $bytes     = 0;
        $this->walk($root, static function (string $path, bool $isDir) use (&$fileCount, &$dirCount, &$bytes): void {
            if ($isDir) {
                ++$dirCount;

                return;
            }
            if (self::shouldPreserveBasename(basename($path))) {
                return;
            }
            ++$fileCount;
            $size = @filesize($path);
            if (is_int($size) && $size > 0) {
                $bytes += $size;
            }
        });

        return ['file_count' => $fileCount, 'dir_count' => $dirCount, 'bytes' => $bytes];
    }

    /**
     * @return array{success: bool, message: string, files_removed: int, dirs_removed: int, bytes_freed: int}
     */
    public function purge(): array
    {
        $root = $this->getCacheRoot();
        if (! is_dir($root)) {
            if (! @mkdir($root, 0755, true) && ! is_dir($root)) {
                return [
                    'success'       => false,
                    'message'       => 'No se pudo crear el directorio writable/cache.',
                    'files_removed' => 0,
                    'dirs_removed'  => 0,
                    'bytes_freed'   => 0,
                ];
            }

            return [
                'success'       => true,
                'message'       => 'El directorio de caché estaba vacío.',
                'files_removed' => 0,
                'dirs_removed'  => 0,
                'bytes_freed'   => 0,
            ];
        }

        $filesRemoved = 0;
        $dirsRemoved  = 0;
        $bytesFreed   = 0;

        $this->purgeDirectory($root, $filesRemoved, $dirsRemoved, $bytesFreed);

        try {
            \Config\Services::cache()->clean();
        } catch (\Throwable $e) {
            // El handler de caché puede no estar disponible en algunos entornos.
        }

        try {
            (new ConfigService())->invalidateCache();
        } catch (\Throwable $e) {
            // Sin BD o app_config
        }

        return [
            'success'       => true,
            'message'       => sprintf(
                'Caché borrada: %d archivo(s), %d carpeta(s), %s liberados.',
                $filesRemoved,
                $dirsRemoved,
                self::formatBytes($bytesFreed)
            ),
            'files_removed' => $filesRemoved,
            'dirs_removed'  => $dirsRemoved,
            'bytes_freed'   => $bytesFreed,
        ];
    }

    public static function formatBytes(int $bytes): string
    {
        if ($bytes < 1024) {
            return $bytes . ' B';
        }
        if ($bytes < 1048576) {
            return round($bytes / 1024, 1) . ' KB';
        }
        if ($bytes < 1073741824) {
            return round($bytes / 1048576, 1) . ' MB';
        }

        return round($bytes / 1073741824, 2) . ' GB';
    }

    private function purgeDirectory(string $dir, int &$filesRemoved, int &$dirsRemoved, int &$bytesFreed): void
    {
        $entries = @scandir($dir);
        if ($entries === false) {
            return;
        }

        foreach ($entries as $entry) {
            if ($entry === '.' || $entry === '..') {
                continue;
            }
            if (self::shouldPreserveBasename($entry)) {
                continue;
            }

            $path = $dir . DIRECTORY_SEPARATOR . $entry;
            if (is_dir($path)) {
                $this->purgeDirectory($path, $filesRemoved, $dirsRemoved, $bytesFreed);
                if (@rmdir($path)) {
                    ++$dirsRemoved;
                }

                continue;
            }

            $size = @filesize($path);
            if (is_int($size) && $size > 0) {
                $bytesFreed += $size;
            }
            if (@unlink($path)) {
                ++$filesRemoved;
            }
        }
    }

    /**
     * @param callable(string, bool): void $visitor
     */
    private function walk(string $dir, callable $visitor): void
    {
        $entries = @scandir($dir);
        if ($entries === false) {
            return;
        }

        foreach ($entries as $entry) {
            if ($entry === '.' || $entry === '..') {
                continue;
            }
            if (self::shouldPreserveBasename($entry)) {
                continue;
            }

            $path  = $dir . DIRECTORY_SEPARATOR . $entry;
            $isDir = is_dir($path);
            $visitor($path, $isDir);
            if ($isDir) {
                $this->walk($path, $visitor);
            }
        }
    }

    private static function shouldPreserveBasename(string $basename): bool
    {
        return in_array($basename, self::PRESERVE_BASENAMES, true);
    }
}
