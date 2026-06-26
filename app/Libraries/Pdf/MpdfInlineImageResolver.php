<?php

namespace App\Libraries\Pdf;

/**
 * Convierte src="data:image/...;base64,..." en rutas temporales antes de WriteHTML().
 * mPDF aplica regex sobre todo el HTML; URIs grandes (p. ej. logo del lab) superan pcre.backtrack_limit
 * y las imágenes inline no se pintan en documentos completos.
 */
final class MpdfInlineImageResolver
{
    public static function materializeDataUriImages(string $html): string
    {
        $pos = 0;
        $len = strlen($html);

        while ($pos < $len) {
            $start = stripos($html, 'src=', $pos);
            if ($start === false) {
                break;
            }

            $quotePos = $start + 4;
            if ($quotePos >= $len) {
                break;
            }

            $quote = $html[$quotePos];
            if ($quote !== '"' && $quote !== "'") {
                $pos = $start + 4;
                continue;
            }

            $uriStart = $quotePos + 1;
            if ($uriStart + 10 >= $len || stripos($html, 'data:image/', $uriStart) !== $uriStart) {
                $pos = $uriStart;
                continue;
            }

            $uriEnd = strpos($html, $quote, $uriStart);
            if ($uriEnd === false) {
                break;
            }

            $uri = substr($html, $uriStart, $uriEnd - $uriStart);
            $path = self::writeDataUriToTemp($uri);
            if ($path !== null) {
                $pathAttr = str_replace('\\', '/', $path);
                $html = substr($html, 0, $quotePos)
                    . $quote
                    . $pathAttr
                    . $quote
                    . substr($html, $uriEnd + 1);
                $pos = $quotePos + strlen($pathAttr) + 2;
                $len = strlen($html);
                continue;
            }

            $pos = $uriEnd + 1;
        }

        return $html;
    }

    private static function writeDataUriToTemp(string $uri): ?string
    {
        $uri = html_entity_decode(trim($uri), ENT_QUOTES | ENT_HTML5, 'UTF-8');
        if ($uri === '' || ! str_starts_with($uri, 'data:')) {
            return null;
        }

        if (! preg_match('#^data:image/(png|jpe?g|gif|webp);base64,(.+)$#i', $uri, $matches)) {
            return null;
        }

        $binary = base64_decode($matches[2], true);
        if ($binary === false || $binary === '') {
            return null;
        }

        $ext = strtolower($matches[1]) === 'jpeg' ? 'jpg' : strtolower($matches[1]);
        $dir = self::tempDir();
        if (! is_dir($dir)) {
            @mkdir($dir, 0755, true);
        }

        $temp = $dir . DIRECTORY_SEPARATOR . 'img_' . sha1($binary) . '.' . $ext;
        if (! is_file($temp) && @file_put_contents($temp, $binary) === false) {
            return null;
        }

        return is_file($temp) ? $temp : null;
    }

    private static function tempDir(): string
    {
        $tempDir = trim((string) (config('Pdf')->mpdfTempDir ?? ''));
        if ($tempDir === '') {
            $tempDir = WRITEPATH . 'cache' . DIRECTORY_SEPARATOR . 'mpdf';
        }

        return $tempDir . DIRECTORY_SEPARATOR . 'inline-images';
    }
}
