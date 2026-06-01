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

if (!function_exists('app_report_timezone')) {
    /**
     * Zona horaria del laboratorio (app_config → App.php → UTC).
     */
    function app_report_timezone(): string
    {
        return \App\Services\RegisterService::reportDisplayTimezone();
    }
}

if (!function_exists('header_datetime_context')) {
    /**
     * Fecha/hora actual para el reloj del header (zona del sistema).
     *
     * @return array{display: string, iso: string, timezone: string, format: string, has_seconds: bool}
     */
    function header_datetime_context(): array
    {
        \App\Services\RegisterService::applyRequestTimezone();

        $tzId = app_report_timezone();
        try {
            $now = new \DateTimeImmutable('now', new \DateTimeZone($tzId));
        } catch (\Throwable $e) {
            $tzId = 'UTC';
            $now  = new \DateTimeImmutable('now', new \DateTimeZone('UTC'));
        }

        $layout    = layout_config();
        $formatKey = \App\Services\LayoutService::normalizeHeaderDatetimeFormat(
            (string) ($layout['header_datetime_format'] ?? '')
        );

        return [
            'display'     => \App\Services\LayoutService::formatHeaderDatetime($now, $formatKey),
            'iso'         => $now->format(\DateTimeInterface::ATOM),
            'timezone'    => $tzId,
            'format'      => $formatKey,
            'has_seconds' => \App\Services\LayoutService::headerDatetimeFormatHasSeconds($formatKey),
        ];
    }
}

if (!function_exists('app_header_datetime_format')) {
    function app_header_datetime_format(): string
    {
        $layout = layout_config();

        return \App\Services\LayoutService::normalizeHeaderDatetimeFormat(
            (string) ($layout['header_datetime_format'] ?? '')
        );
    }
}
