<?php

namespace App\Libraries\Pdf;

/**
 * mPDF no interpreta custom properties (var(--x)). La plantilla PDF las define en :root.
 * Expande var(--nombre, fallback) a valores literales antes de renderizar.
 */
class MpdfCssVariablesResolver
{
    public static function resolveInHtml(string $html): string
    {
        $allCss = '';
        if (preg_match_all('/<style\b[^>]*>(.*?)<\/style>/is', $html, $styleMatches)) {
            $allCss = implode("\n", $styleMatches[1]);
        }

        $vars = self::extractVariableDefinitions($allCss);

        return preg_replace_callback(
            '/<style\b[^>]*>(.*?)<\/style>/is',
            static function (array $match) use ($vars): string {
                $resolved = self::resolveCss($match[1], $vars);
                $resolved = self::normalizeFontFamilies($resolved);

                return '<style>' . $resolved . '</style>';
            },
            $html,
        ) ?? $html;
    }

    public static function resolveCss(string $css, ?array $vars = null): string
    {
        $vars ??= self::extractVariableDefinitions($css);

        for ($pass = 0; $pass < 16; $pass++) {
            $vars  = array_merge($vars, self::extractVariableDefinitions($css));
            $next  = self::substituteVariables($css, $vars);
            $changed = $next !== $css;
            $css   = $next;
            if (! $changed && ! preg_match('/\bvar\s*\(/i', $css)) {
                break;
            }
        }

        return self::normalizeFontFamilies($css);
    }

    public static function normalizeFontFamilies(string $css): string
    {
        return MpdfFontMapper::normalizeCssFontFamilies($css);
    }

    /**
     * @return array<string, string>
     */
    private static function extractVariableDefinitions(string $css): array
    {
        $vars = [];
        if (preg_match_all('/--([a-zA-Z0-9_-]+)\s*:\s*((?:[^;{}]|var\([^)]*\))*)\s*;/', $css, $matches, PREG_SET_ORDER)) {
            foreach ($matches as $match) {
                $vars['--' . $match[1]] = trim($match[2]);
            }
        }

        return $vars;
    }

    /**
     * @param array<string, string> $vars
     */
    private static function substituteVariables(string $css, array $vars): string
    {
        $pos = 0;
        $len = strlen($css);
        $out = '';

        while ($pos < $len) {
            $varPos = stripos($css, 'var(', $pos);
            if ($varPos === false) {
                $out .= substr($css, $pos);

                break;
            }

            $out .= substr($css, $pos, $varPos - $pos);
            $parsed = self::parseVarFunction($css, $varPos);
            if ($parsed === null) {
                $out .= 'var(';
                $pos = $varPos + 4;

                continue;
            }

            [, $name, $fallback, $end] = $parsed;
            $replacement = '';
            if (isset($vars[$name])) {
                $replacement = $vars[$name];
            } elseif ($fallback !== '') {
                $replacement = $fallback;
            }

            $out .= $replacement;
            $pos = $end;
        }

        return $out;
    }

    /**
     * @return array{0: string, 1: string, 2: string, 3: int}|null
     */
    private static function parseVarFunction(string $css, int $start): ?array
    {
        if (strcasecmp(substr($css, $start, 4), 'var(') !== 0) {
            return null;
        }

        $len   = strlen($css);
        $depth = 1;
        $inner = '';
        $i     = $start + 4;

        while ($i < $len && $depth > 0) {
            $ch = $css[$i];
            if ($ch === '(') {
                $depth++;
            } elseif ($ch === ')') {
                $depth--;
                if ($depth === 0) {
                    break;
                }
            }
            if ($depth > 0) {
                $inner .= $ch;
            }
            $i++;
        }

        if ($depth !== 0) {
            return null;
        }

        $name     = trim($inner);
        $fallback = '';
        $depth    = 0;
        $innerLen = strlen($inner);

        for ($j = 0; $j < $innerLen; $j++) {
            $ch = $inner[$j];
            if ($ch === '(') {
                $depth++;
            } elseif ($ch === ')') {
                $depth--;
            } elseif ($ch === ',' && $depth === 0) {
                $name     = trim(substr($inner, 0, $j));
                $fallback = trim(substr($inner, $j + 1));
                break;
            }
        }

        return [substr($css, $start, $i - $start + 1), $name, $fallback, $i + 1];
    }
}
