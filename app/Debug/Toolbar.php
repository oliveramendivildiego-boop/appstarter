<?php

namespace App\Debug;

use CodeIgniter\Debug\Toolbar as BaseToolbar;

/**
 * Toolbar que corrige inputs kint-search sin id/name para cumplir accesibilidad.
 */
class Toolbar extends BaseToolbar
{
    protected function format(string $data, string $format = 'html'): string
    {
        $output = parent::format($data, $format);

        if ($format === 'html' && str_contains($output, 'kint-search')) {
            $n = 0;
            $output = preg_replace_callback(
                '/<input\s+type="text"\s+class="kint-search"\s+value=""\s*\/?>/',
                static function () use (&$n) {
                    $n++;
                    return '<input type="text" id="kint-search-' . $n . '" name="kint_search_' . $n . '" class="kint-search" value="" autocomplete="off">';
                },
                $output
            );
        }

        return $output;
    }
}
