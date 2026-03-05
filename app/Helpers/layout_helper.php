<?php

/**
 * Helper para layout: obtiene la configuración (logo, tema, empresa)
 * usando LayoutService. Evita que las vistas llamen a model() directamente.
 */
if (!function_exists('layout_config')) {
    function layout_config(): array
    {
        $service = new \App\Services\LayoutService();
        return $service->getConfig();
    }
}
