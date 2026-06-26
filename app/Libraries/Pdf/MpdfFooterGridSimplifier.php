<?php

namespace App\Libraries\Pdf;

/**
 * Convierte la grilla de pie 5 columnas (2+1+2) en 3 columnas (40/20/40) para mPDF SetHTMLFooter.
 */
final class MpdfFooterGridSimplifier
{
    public static function simplify(string $html): string
    {
        if (! str_contains($html, 'mpdf-ft-table') || ! str_contains($html, 'data-pdf-cols="5"')) {
            return $html;
        }

        $prev = libxml_use_internal_errors(true);
        $dom  = new \DOMDocument('1.0', 'UTF-8');
        $wrap = '<?xml encoding="utf-8"><div id="mpdf-ft-wrap">' . $html . '</div>';
        if (! $dom->loadHTML($wrap, LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD)) {
            libxml_clear_errors();
            libxml_use_internal_errors($prev);

            return $html;
        }
        libxml_clear_errors();
        libxml_use_internal_errors($prev);

        $xpath = new \DOMXPath($dom);
        $tables = $xpath->query('//table[contains(@class,"mpdf-ft-table") and @data-pdf-cols="5"]');
        if ($tables === false || $tables->length === 0) {
            return $html;
        }

        foreach ($tables as $table) {
            if (! $table instanceof \DOMElement) {
                continue;
            }
            self::simplifyTable($table, $dom);
        }

        $root = $dom->getElementById('mpdf-ft-wrap');
        if ($root === null) {
            return $html;
        }

        $out = '';
        foreach ($root->childNodes as $child) {
            $out .= $dom->saveHTML($child);
        }

        return $out;
    }

    private static function simplifyTable(\DOMElement $table, \DOMDocument $dom): void
    {
        $table->setAttribute('data-pdf-cols', '3');

        $colgroup = null;
        foreach ($table->childNodes as $child) {
            if ($child instanceof \DOMElement && $child->nodeName === 'colgroup') {
                $colgroup = $child;
                break;
            }
        }
        if ($colgroup !== null) {
            while ($colgroup->firstChild !== null) {
                $colgroup->removeChild($colgroup->firstChild);
            }
        } else {
            $colgroup = $dom->createElement('colgroup');
            if ($table->firstChild !== null) {
                $table->insertBefore($colgroup, $table->firstChild);
            } else {
                $table->appendChild($colgroup);
            }
        }

        foreach (['40%', '20%', '40%'] as $pct) {
            $col = $dom->createElement('col');
            $col->setAttribute('width', $pct);
            $col->setAttribute('style', 'width:' . $pct);
            $colgroup->appendChild($col);
        }

        $tableStyle = $table->getAttribute('style');
        if (! str_contains($tableStyle, 'table-layout')) {
            $tableStyle = trim($tableStyle . ';table-layout:fixed;width:100%;border-collapse:collapse;', ';');
            $table->setAttribute('style', $tableStyle);
        }
        $table->setAttribute('width', '100%');

        $tbody = null;
        foreach ($table->childNodes as $child) {
            if ($child instanceof \DOMElement && $child->nodeName === 'tbody') {
                $tbody = $child;
                break;
            }
        }
        $rows = $tbody instanceof \DOMElement
            ? iterator_to_array($tbody->getElementsByTagName('tr'))
            : iterator_to_array($table->getElementsByTagName('tr'));

        foreach ($rows as $tr) {
            if (! $tr instanceof \DOMElement) {
                continue;
            }
            $tds = [];
            foreach ($tr->childNodes as $child) {
                if ($child instanceof \DOMElement && $child->nodeName === 'td') {
                    $tds[] = $child;
                }
            }
            if ($tds === []) {
                continue;
            }

            $spans = array_map(
                static fn (\DOMElement $td): int => max(1, (int) $td->getAttribute('colspan')),
                $tds,
            );

            if (count($tds) === 3 && $spans === [2, 1, 2]) {
                self::applyLogicalWidth($tds[0], '40%');
                self::applyLogicalWidth($tds[1], '20%');
                self::applyLogicalWidth($tds[2], '40%');
                foreach ($tds as $td) {
                    $td->removeAttribute('colspan');
                }
            } elseif (count($tds) === 2 && $spans === [2, 3]) {
                $tds[0]->setAttribute('colspan', '1');
                $tds[1]->setAttribute('colspan', '2');
                self::applyLogicalWidth($tds[0], '40%');
                self::applyLogicalWidth($tds[1], '60%');
            }

            foreach ($tds as $td) {
                $align = strtolower($td->getAttribute('align'));
                if ($align === 'left' || $align === 'center' || $align === 'right') {
                    $style = $td->getAttribute('style');
                    if (! str_contains($style, 'text-align')) {
                        $td->setAttribute('style', trim($style . ';text-align:' . $align . ' !important;', ';'));
                    }
                }
            }
        }
    }

  /**
   * @param list<\DOMElement> $tds
   */
    private static function applyLogicalWidth(\DOMElement $td, string $pct): void
    {
        $td->setAttribute('width', $pct);
        $style = $td->getAttribute('style');
        $style = preg_replace('/(?<![a-z-])\bwidth\s*:\s*[^;]+;?/i', '', $style) ?? $style;
        $style = preg_replace('/(?<![a-z-])\bmax-width\s*:\s*[^;]+;?/i', '', $style) ?? $style;
        $style = trim($style, " \t\n\r\0\x0B;");
        $extra = 'width:' . $pct . ';max-width:' . $pct . ';box-sizing:border-box;';
        $td->setAttribute('style', $style === '' ? $extra : ($style . ';' . $extra));
    }
}
