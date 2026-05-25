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

if (!function_exists('currency_config')) {
    /**
     * @return array{symbol: string, is_right: bool}
     */
    function currency_config(): array
    {
        static $cached = null;
        if ($cached !== null) {
            return $cached;
        }

        $layout = layout_config();
        $symbol = trim((string) ($layout['currency_symbol'] ?? '$'));
        if ($symbol === '') {
            $symbol = '$';
        }

        $side = strtolower(trim((string) ($layout['currency_side'] ?? 'left')));
        $cached = [
            'symbol'   => $symbol,
            'is_right' => $side === 'right',
        ];

        return $cached;
    }
}

if (!function_exists('currency_symbol')) {
    function currency_symbol(): string
    {
        return currency_config()['symbol'];
    }
}

if (!function_exists('currency_is_right')) {
    function currency_is_right(): bool
    {
        return currency_config()['is_right'];
    }
}

if (!function_exists('format_currency')) {
    function format_currency($amount, int $decimals = 2): string
    {
        $cfg = currency_config();
        $formatted = number_format((float) $amount, $decimals);
        $symbol = esc($cfg['symbol']);

        return $cfg['is_right']
            ? ($formatted . ' ' . $symbol)
            : ($symbol . ' ' . $formatted);
    }
}

if (!function_exists('employee_landing_url')) {
    /**
     * Página de inicio del empleado logueado (dashboard o primer módulo permitido).
     */
    function employee_landing_url(): string
    {
        static $cached = null;
        if ($cached !== null) {
            return $cached;
        }

        $personId = (int) (session()->get('person_id') ?? 0);
        if ($personId < 1) {
            $cached = site_url('login');
            return $cached;
        }

        $cached = model(\App\Models\EmployeeModel::class)->getDefaultLandingUrl($personId);
        return $cached;
    }
}
