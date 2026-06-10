<?php

if (!function_exists('asset_version')) {
    /**
     * Versión de un asset estático (mtime del archivo o valor de env asset.version).
     */
    function asset_version(?string $relativePath = null): string
    {
        static $globalVersion = null;

        if ($relativePath !== null && $relativePath !== '') {
            $fullPath = FCPATH . ltrim(str_replace('\\', '/', $relativePath), '/');
            if (is_file($fullPath)) {
                return (string) filemtime($fullPath);
            }
        }

        if ($globalVersion !== null) {
            return $globalVersion;
        }

        $fromEnv = env('asset.version');
        if ($fromEnv !== null && $fromEnv !== false && $fromEnv !== '') {
            $globalVersion = (string) $fromEnv;

            return $globalVersion;
        }

        $globalVersion = '1';

        return $globalVersion;
    }
}

if (!function_exists('asset_url')) {
    /**
     * URL pública de CSS/JS/imagen con ?v= para invalidar caché del navegador al cambiar el archivo.
     */
    function asset_url(string $relativePath): string
    {
        $relativePath = ltrim(str_replace('\\', '/', $relativePath), '/');

        return base_url($relativePath) . '?v=' . rawurlencode(asset_version($relativePath));
    }
}
