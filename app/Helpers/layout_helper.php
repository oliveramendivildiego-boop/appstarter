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

if (!function_exists('sidebar_module_icon_defs')) {
    /**
     * Iconos del menú lateral: glifo + variante de color (estilo app iOS / Meta).
     *
     * @return array<string, array{icon: string, tone: string}>
     */
    function sidebar_module_icon_defs(): array
    {
        return [
            'home'                 => ['icon' => 'fa-house', 'tone' => 'home'],
            'customers'            => ['icon' => 'fa-user-group', 'tone' => 'customers'],
            'doctors'              => ['icon' => 'fa-user-doctor', 'tone' => 'doctors'],
            'doctor_commissions'   => ['icon' => 'fa-hand-holding-dollar', 'tone' => 'commissions'],
            'labotests'            => ['icon' => 'fa-flask-vial', 'tone' => 'labotests'],
            'toquotes'             => ['icon' => 'fa-file-invoice-dollar', 'tone' => 'toquotes'],
            'registers'            => ['icon' => 'fa-clipboard-check', 'tone' => 'registers'],
            'registers_nuevo'      => ['icon' => 'fa-circle-plus', 'tone' => 'registers-nuevo'],
            'expediente'           => ['icon' => 'fa-clock-rotate-left', 'tone' => 'expediente'],
            'reports'              => ['icon' => 'fa-chart-pie', 'tone' => 'reports'],
            'controlcalidad'       => ['icon' => 'fa-shield-heart', 'tone' => 'controlcalidad'],
            'reactivos'            => ['icon' => 'fa-boxes-stacked', 'tone' => 'reactivos'],
            'equipos'              => ['icon' => 'fa-microscope', 'tone' => 'equipos'],
            'egresos'              => ['icon' => 'fa-wallet', 'tone' => 'egresos'],
            'leyendas'             => ['icon' => 'fa-comments', 'tone' => 'leyendas'],
            'auditoria'            => ['icon' => 'fa-list-check', 'tone' => 'auditoria'],
            'employees'            => ['icon' => 'fa-id-badge', 'tone' => 'employees'],
            'config'               => ['icon' => 'fa-sliders', 'tone' => 'config'],
            'tenant_subscription'  => ['icon' => 'fa-file-contract', 'tone' => 'subscription'],
            'account_password'     => ['icon' => 'fa-lock', 'tone' => 'password'],
            'logout'               => ['icon' => 'fa-arrow-right-from-bracket', 'tone' => 'logout'],
        ];
    }
}

if (!function_exists('sidebar_module_icon')) {
    /**
     * HTML del icono del menú lateral (tile de color + glifo blanco).
     */
    function sidebar_module_icon(string $moduleKey, bool $small = false): string
    {
        $defs = sidebar_module_icon_defs();
        $def  = $defs[$moduleKey] ?? ['icon' => 'fa-table-cells', 'tone' => 'default'];
        $tone = preg_replace('/[^a-z0-9-]/', '', (string) ($def['tone'] ?? 'default')) ?: 'default';
        $icon = preg_replace('/[^a-z0-9-]/', '', (string) ($def['icon'] ?? 'fa-circle')) ?: 'fa-circle';
        $size = $small ? ' sidebar-app-icon--sm' : '';

        return '<span class="sidebar-app-icon sidebar-app-icon--' . $tone . $size . '" aria-hidden="true">'
            . '<i class="fa-solid ' . $icon . '"></i></span>';
    }
}

if (!function_exists('header_datetime_icon')) {
    /**
     * Icono del reloj del header (tile de color estilo app iOS / Meta).
     */
    function header_datetime_icon(): string
    {
        return '<span class="header-datetime-icon me-1 me-sm-2" aria-hidden="true">'
            . '<i class="fa-solid fa-clock"></i></span>';
    }
}
