<?php

namespace App\Filters;

use CodeIgniter\Filters\FilterInterface;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;

/**
 * Corrige inputs kint-search sin id/name para cumplir accesibilidad (CSP/form autofill).
 * Kint (Debug Toolbar) genera <input class="kint-search"> sin atributos.
 */
class KintFormFixFilter implements FilterInterface
{
    public function before(RequestInterface $request, $arguments = null)
    {
        return null;
    }

    public function after(RequestInterface $request, ResponseInterface $response, $arguments = null)
    {
        $body = $response->getBody();

        if (empty($body) || strpos($body, 'class="kint-search"') === false) {
            return $response;
        }

        $n = 0;
        $body = preg_replace_callback(
            '/<input\s+type="text"\s+class="kint-search"\s+value="">/',
            static function () use (&$n) {
                $n++;
                return '<input type="text" id="kint-search-' . $n . '" name="kint_search_' . $n . '" class="kint-search" value="">';
            },
            $body
        );

        $response->setBody($body);

        return $response;
    }
}
