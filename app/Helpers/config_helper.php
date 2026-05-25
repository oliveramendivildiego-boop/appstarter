<?php

if (!function_exists('safe_mb_trim')) {
    /**
     * Trim con soporte multibyte (PHP 8.4+ mb_trim) o trim como fallback.
     * Usar para texto con acentos/ñ en español.
     */
    function safe_mb_trim(string $string, ?string $characters = null): string
    {
        if (function_exists('mb_trim')) {
            return $characters !== null ? mb_trim($string, $characters) : mb_trim($string);
        }
        return $characters !== null ? trim($string, $characters) : trim($string);
    }
}

if (!function_exists('config_html_color_picker_value')) {
    /**
     * Valor válido para atributo value de <input type="color"> (#rrggbb en 6 hex).
     *
     * @see \App\Services\LayoutService::htmlColorPickerValue()
     */
    function config_html_color_picker_value(?string $raw, string $fallback): string
    {
        return \App\Services\LayoutService::htmlColorPickerValue($raw, $fallback);
    }
}

if (!function_exists('darken_hex_color')) {
    /**
     * Oscurece un color hex para hover/estados activos
     */
    function darken_hex_color(string $hex, int $percent = 12): string
    {
        $hex = ltrim($hex, '#');
        if (strlen($hex) === 3) {
            $hex = $hex[0].$hex[0].$hex[1].$hex[1].$hex[2].$hex[2];
        }
        if (strlen($hex) !== 6 || !ctype_xdigit($hex)) {
            return $hex;
        }
        $r = max(0, hexdec(substr($hex, 0, 2)) - (hexdec(substr($hex, 0, 2)) * $percent / 100));
        $g = max(0, hexdec(substr($hex, 2, 2)) - (hexdec(substr($hex, 2, 2)) * $percent / 100));
        $b = max(0, hexdec(substr($hex, 4, 2)) - (hexdec(substr($hex, 4, 2)) * $percent / 100));
        return sprintf('#%02x%02x%02x', $r, $g, $b);
    }
}

if (!function_exists('get_timezone_options')) {
    /**
     * Opciones de zona horaria para el select de configuración
     */
    function get_timezone_options(): array
    {
        return [
            'Pacific/Midway'       => '(GMT-11:00) Midway Island, Samoa',
            'America/Adak'         => '(GMT-10:00) Hawaii-Aleutian',
            'Etc/GMT+10'           => '(GMT-10:00) Hawaii',
            'Pacific/Marquesas'     => '(GMT-09:30) Marquesas Islands',
            'Pacific/Gambier'      => '(GMT-09:00) Gambier Islands',
            'America/Anchorage'     => '(GMT-09:00) Alaska',
            'America/Ensenada'     => '(GMT-08:00) Tijuana, Baja California',
            'Etc/GMT+8'            => '(GMT-08:00) Pitcairn Islands',
            'America/Los_Angeles'  => '(GMT-08:00) Pacific Time (US & Canada)',
            'America/Denver'        => '(GMT-07:00) Mountain Time (US & Canada)',
            'America/Chihuahua'    => '(GMT-07:00) Chihuahua, La Paz, Mazatlan',
            'America/Dawson_Creek' => '(GMT-07:00) Arizona',
            'America/Belize'       => '(GMT-06:00) Saskatchewan, Central America',
            'America/Cancun'       => '(GMT-06:00) Guadalajara, Mexico City, Monterrey',
            'Chile/EasterIsland'   => '(GMT-06:00) Easter Island',
            'America/Chicago'      => '(GMT-06:00) Central Time (US & Canada)',
            'America/New_York'     => '(GMT-05:00) Eastern Time (US & Canada)',
            'America/Havana'       => '(GMT-05:00) Cuba',
            'America/Bogota'       => '(GMT-05:00) Bogota, Lima, Quito, Rio Branco',
            'America/Caracas'      => '(GMT-04:30) Caracas',
            'America/Santiago'     => '(GMT-04:00) Santiago',
            'America/La_Paz'       => '(GMT-04:00) La Paz',
            'Atlantic/Stanley'     => '(GMT-04:00) Falkland Islands',
            'America/Campo_Grande' => '(GMT-04:00) Brazil',
            'America/Goose_Bay'    => '(GMT-04:00) Atlantic Time (Goose Bay)',
            'America/Glace_Bay'    => '(GMT-04:00) Atlantic Time (Canada)',
            'America/St_Johns'     => '(GMT-03:30) Newfoundland',
            'America/Araguaina'    => '(GMT-03:00) UTC-3',
            'America/Montevideo'   => '(GMT-03:00) Montevideo',
            'America/Miquelon'     => '(GMT-03:00) Miquelon, St. Pierre',
            'America/Godthab'      => '(GMT-03:00) Greenland',
            'America/Argentina/Buenos_Aires' => '(GMT-03:00) Buenos Aires',
            'America/Sao_Paulo'    => '(GMT-03:00) Brasilia',
            'America/Noronha'      => '(GMT-02:00) Mid-Atlantic',
            'Atlantic/Cape_Verde'  => '(GMT-01:00) Cape Verde Is.',
            'Atlantic/Azores'      => '(GMT-01:00) Azores',
            'Europe/Belfast'       => '(GMT) Greenwich Mean Time : Belfast',
            'Europe/Dublin'        => '(GMT) Greenwich Mean Time : Dublin',
            'Europe/Lisbon'        => '(GMT) Greenwich Mean Time : Lisbon',
            'Europe/London'        => '(GMT) Greenwich Mean Time : London',
            'Africa/Abidjan'       => '(GMT) Monrovia, Reykjavik',
            'Europe/Amsterdam'     => '(GMT+01:00) Amsterdam, Berlin, Bern, Rome, Stockholm, Vienna',
            'Europe/Belgrade'      => '(GMT+01:00) Belgrade, Bratislava, Budapest, Ljubljana, Prague',
            'Europe/Brussels'      => '(GMT+01:00) Brussels, Copenhagen, Madrid, Paris',
            'Africa/Algiers'       => '(GMT+01:00) West Central Africa',
            'Africa/Windhoek'      => '(GMT+01:00) Windhoek',
            'Asia/Beirut'          => '(GMT+02:00) Beirut',
            'Africa/Cairo'         => '(GMT+02:00) Cairo',
            'Asia/Gaza'            => '(GMT+02:00) Gaza',
            'Africa/Blantyre'      => '(GMT+02:00) Harare, Pretoria',
            'Asia/Jerusalem'       => '(GMT+02:00) Jerusalem',
            'Europe/Minsk'         => '(GMT+02:00) Minsk',
            'Asia/Damascus'        => '(GMT+02:00) Syria',
            'Europe/Moscow'        => '(GMT+03:00) Moscow, St. Petersburg, Volgograd',
            'Africa/Addis_Ababa'   => '(GMT+03:00) Nairobi',
            'Asia/Tehran'          => '(GMT+03:30) Tehran',
            'Asia/Dubai'           => '(GMT+04:00) Abu Dhabi, Muscat',
            'Asia/Yerevan'         => '(GMT+04:00) Yerevan',
            'Asia/Kabul'           => '(GMT+04:30) Kabul',
            'Asia/Baku'            => '(GMT+05:00) Baku',
            'Asia/Yekaterinburg'   => '(GMT+05:00) Ekaterinburg',
            'Asia/Tashkent'        => '(GMT+05:00) Tashkent',
            'Asia/Kolkata'         => '(GMT+05:30) Chennai, Kolkata, Mumbai, New Delhi',
            'Asia/Katmandu'        => '(GMT+05:45) Kathmandu',
            'Asia/Dhaka'           => '(GMT+06:00) Astana, Dhaka',
            'Asia/Novosibirsk'     => '(GMT+06:00) Novosibirsk',
            'Asia/Rangoon'         => '(GMT+06:30) Yangon (Rangoon)',
            'Asia/Bangkok'         => '(GMT+07:00) Bangkok, Hanoi, Jakarta',
            'Asia/Krasnoyarsk'     => '(GMT+07:00) Krasnoyarsk',
            'Asia/Hong_Kong'       => '(GMT+08:00) Beijing, Chongqing, Hong Kong, Urumqi',
            'Asia/Irkutsk'         => '(GMT+08:00) Irkutsk, Ulaan Bataar',
            'Australia/Perth'      => '(GMT+08:00) Perth',
            'Australia/Eucla'      => '(GMT+08:45) Eucla',
            'Asia/Tokyo'           => '(GMT+09:00) Osaka, Sapporo, Tokyo',
            'Asia/Seoul'           => '(GMT+09:00) Seoul',
            'Asia/Yakutsk'         => '(GMT+09:00) Yakutsk',
            'Australia/Adelaide'   => '(GMT+09:30) Adelaide',
            'Australia/Darwin'     => '(GMT+09:30) Darwin',
            'Australia/Brisbane'   => '(GMT+10:00) Brisbane',
            'Australia/Hobart'     => '(GMT+10:00) Hobart',
            'Asia/Vladivostok'     => '(GMT+10:00) Vladivostok',
            'Australia/Lord_Howe'  => '(GMT+10:30) Lord Howe Island',
            'Etc/GMT-11'           => '(GMT+11:00) Solomon Is., New Caledonia',
            'Asia/Magadan'         => '(GMT+11:00) Magadan',
            'Pacific/Norfolk'      => '(GMT+11:30) Norfolk Island',
            'Asia/Anadyr'          => '(GMT+12:00) Anadyr, Kamchatka',
            'Pacific/Auckland'     => '(GMT+12:00) Auckland, Wellington',
            'Etc/GMT-12'           => '(GMT+12:00) Fiji, Kamchatka, Marshall Is.',
            'Pacific/Chatham'      => '(GMT+12:45) Chatham Islands',
            'Pacific/Tongatapu'    => '(GMT+13:00) Nuku\'alofa',
            'Pacific/Kiritimati'   => '(GMT+14:00) Kiritimati',
        ];
    }
}

if (!function_exists('get_theme_color_palette')) {
    /**
     * Paleta de colores predefinidos - Bootstrap 5.3
     * @see https://getbootstrap.com/docs/5.3/customize/color/
     */
    function get_theme_color_palette(): array
    {
        return [
            '#FF7218' => 'Naranja',
            /* Colores del tema Bootstrap 5.3 */
            '#0d6efd' => 'Primary / Blue',
            '#6c757d' => 'Secondary',
            '#198754' => 'Success / Green',
            '#dc3545' => 'Danger / Red',
            '#ffc107' => 'Warning / Yellow',
            '#0dcaf0' => 'Info / Cyan',
            '#f8f9fa' => 'Light',
            '#212529' => 'Dark',
            /* Colores base adicionales */
            '#6610f2' => 'Indigo',
            '#6f42c1' => 'Purple',
            '#d63384' => 'Pink',
            '#fd7e14' => 'Orange',
            '#20c997' => 'Teal',
            '#adb5bd' => 'Gray',
        ];
    }
}

if (!function_exists('whatsapp_country_code')) {
    /**
     * Código de país para WhatsApp (solo dígitos). Configurable en Configuración > WhatsApp.
     */
    function whatsapp_country_code(): string
    {
        static $cached = null;
        if ($cached !== null) {
            return $cached;
        }

        $raw   = model(\App\Models\AppConfigModel::class)->getValue('whatsapp_country_code');
        $clean = preg_replace('/\D/', '', $raw ?: '591');
        $cached = $clean !== '' ? $clean : '591';

        return $cached;
    }
}

if (!function_exists('whatsapp_format_phone_number')) {
    /**
     * Formatea teléfono para enlaces wa.me y envío por API (sin + ni espacios).
     */
    function whatsapp_format_phone_number(string $phone): string
    {
        return \App\Services\WhatsAppService::formatPhoneWithCountryCode($phone, whatsapp_country_code());
    }
}
