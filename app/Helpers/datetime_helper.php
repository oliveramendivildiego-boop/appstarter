<?php

declare(strict_types=1);

use App\Services\RegisterService;

/**
 * Fechas/horas del laboratorio según la zona horaria en /config (app_config.timezone).
 * Guardado: UTC o local según lab_datetime_storage. Visualización: siempre zona /config.
 */

if (! function_exists('lab_tz_apply')) {
    function lab_tz_apply(): void
    {
        RegisterService::applyRequestTimezone();
    }
}

if (! function_exists('lab_now_sql')) {
    /** DATETIME para INSERT/UPDATE en BD. */
    function lab_now_sql(): string
    {
        lab_tz_apply();

        return RegisterService::mysqlNowForReport();
    }
}

if (! function_exists('lab_today_ymd')) {
    /** Fecha de hoy (Y-m-d) en la zona del laboratorio. */
    function lab_today_ymd(): string
    {
        lab_tz_apply();

        return RegisterService::todayForReport();
    }
}

if (! function_exists('lab_date_ymd')) {
    /** Y-m-d relativo (p. ej. "+30 days", "monday this week") en zona del laboratorio. */
    function lab_date_ymd(string $modifier): string
    {
        lab_tz_apply();

        return RegisterService::reportDateFromModifier($modifier);
    }
}

if (! function_exists('lab_dt')) {
    /** DATETIME de BD → d/m/Y H:i:s en zona del laboratorio. */
    function lab_dt(?string $mysqlDatetime): string
    {
        if ($mysqlDatetime === null || trim($mysqlDatetime) === '') {
            return '—';
        }

        return RegisterService::formatStoredReporteFechaHora(trim($mysqlDatetime));
    }
}

if (! function_exists('lab_dt_short')) {
    /** DATETIME de BD → d/m/Y H:i en zona del laboratorio. */
    function lab_dt_short(?string $mysqlDatetime): string
    {
        return RegisterService::formatStoredReporteFechaCorta($mysqlDatetime);
    }
}

if (! function_exists('lab_now_form_datetime')) {
    /** Fecha/hora actual (Y-m-d H:i) en la zona del laboratorio, para flatpickr y similares. */
    function lab_now_form_datetime(): string
    {
        lab_tz_apply();

        return RegisterService::formatNowForFormInput();
    }
}

if (! function_exists('lab_stored_form_datetime')) {
    /** DATETIME de BD → Y-m-d H:i en zona del laboratorio, para flatpickr y similares. */
    function lab_stored_form_datetime(?string $mysqlDatetime): string
    {
        return RegisterService::formatStoredForFormInput($mysqlDatetime);
    }
}

if (! function_exists('lab_date')) {
    /** Y-m-d → d/m/Y (solo fecha, sin conversión de huso). */
    function lab_date(?string $ymdDate): string
    {
        return RegisterService::formatReportDate($ymdDate);
    }
}

if (! function_exists('lab_parse_user_datetime')) {
    /**
     * Fecha/hora ingresada por el usuario (Y-m-d H:i[:s]) → valor para guardar en BD.
     */
    function lab_parse_user_datetime(string $input): string
    {
        lab_tz_apply();

        return RegisterService::parseUserLabDatetimeToStorage($input);
    }
}

if (! function_exists('lab_filename_date')) {
    function lab_filename_date(): string
    {
        return lab_today_ymd();
    }
}

if (! function_exists('lab_filename_datetime')) {
    function lab_filename_datetime(): string
    {
        lab_tz_apply();

        return RegisterService::reportNow()->format('Y-m-d_His');
    }
}
