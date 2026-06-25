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

if (!function_exists('timezone_option_label')) {
    /**
     * Etiqueta con offset UTC real en este momento (evita confusión con horario de verano).
     */
    function timezone_option_label(string $tzId, string $name): string
    {
        try {
            $p = (new \DateTimeImmutable('now', new \DateTimeZone($tzId)))->format('P');

            return $name . ' — ahora UTC' . $p;
        } catch (\Throwable $e) {
            return $name;
        }
    }
}

if (!function_exists('get_timezone_options')) {
    /**
     * Opciones de zona horaria para el select de configuración (offset calculado al cargar la página).
     */
    function get_timezone_options(): array
    {
        $base = [
            'Pacific/Midway'       => 'Midway Island, Samoa',
            'America/Adak'         => 'Hawaii-Aleutian',
            'Etc/GMT+10'           => 'Hawaii',
            'Pacific/Marquesas'     => 'Marquesas Islands',
            'Pacific/Gambier'      => 'Gambier Islands',
            'America/Anchorage'     => 'Alaska',
            'America/Ensenada'     => 'Tijuana, Baja California',
            'Etc/GMT+8'            => 'Pitcairn Islands',
            'America/Los_Angeles'  => 'Pacific Time (US & Canada)',
            'America/Denver'        => 'Mountain Time (US & Canada)',
            'America/Chihuahua'    => 'Chihuahua, La Paz, Mazatlan',
            'America/Dawson_Creek' => 'Arizona',
            'America/Mexico_City'  => '★ Ciudad de México, Guadalajara, Monterrey (GMT-6 fijo)',
            'America/Guatemala'    => '★ Guatemala (GMT-6 fijo)',
            'America/El_Salvador'  => '★ El Salvador (GMT-6 fijo)',
            'America/Tegucigalpa'  => '★ Honduras (GMT-6 fijo)',
            'America/Managua'      => '★ Nicaragua (GMT-6 fijo)',
            'America/Costa_Rica'   => '★ Costa Rica (GMT-6 fijo)',
            'America/Belize'       => 'Belice (GMT-6 fijo)',
            'Chile/EasterIsland'   => 'Easter Island',
            'America/Chicago'      => 'EE.UU. hora central (cambia entre -5 y -6)',
            'America/Cancun'       => 'Cancún / Quintana Roo (GMT-5 fijo, no es CDMX)',
            'America/New_York'     => 'Eastern Time (US & Canada)',
            'America/Havana'       => 'Cuba',
            'America/Bogota'       => 'Bogotá, Lima, Quito (GMT-5 fijo)',
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
            'Pacific/Kiritimati'   => 'Kiritimati',
        ];

        $out = [];
        foreach ($base as $tzId => $name) {
            $out[$tzId] = timezone_option_label($tzId, $name);
        }

        return $out;
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

if (!function_exists('genero_dropdown_options')) {
    /**
     * Opciones para select de género en formularios de pacientes, empleados, etc.
     *
     * @return array<string, string>
     */
    function genero_dropdown_options(string $emptyLabel = '-- Seleccione --'): array
    {
        try {
            return model(\App\Models\GeneroModel::class)->getDropdownOptions($emptyLabel);
        } catch (\Throwable $e) {
            return ['' => $emptyLabel, '1' => 'Masculino', '2' => 'Femenino'];
        }
    }
}

if (!function_exists('referencia_sexo_value_for_genero')) {
    /**
     * Valor persistido en secanacategoria/priresultados.sexo según id de género del catálogo.
     */
    function referencia_sexo_value_for_genero(int $generoId): string
    {
        return match ($generoId) {
            1       => 'masculino',
            2       => 'femenino',
            default => 'g' . $generoId,
        };
    }
}

if (!function_exists('referencia_sexo_dropdown_options')) {
    /**
     * Opciones de sexo para valores de referencia (sub-clases, priresultados).
     *
     * @return array<string, string> value => label
     */
    function referencia_sexo_dropdown_options(): array
    {
        $options = ['ambos' => 'Todos'];
        try {
            foreach (model(\App\Models\GeneroModel::class)->getAllActive() as $row) {
                $id = (int) ($row['genero_id'] ?? 0);
                $nombre = trim((string) ($row['nombre'] ?? ''));
                if ($id > 0 && $nombre !== '') {
                    $options[referencia_sexo_value_for_genero($id)] = $nombre;
                }
            }
        } catch (\Throwable $e) {
            $options['masculino'] = 'Masculino';
            $options['femenino']  = 'Femenino';
        }

        return $options;
    }
}

if (!function_exists('referencia_sexo_label')) {
    /**
     * Etiqueta legible para secanacategoria/priresultados.sexo.
     */
    function referencia_sexo_label(?string $sexo): string
    {
        $sx = strtolower(trim((string) $sexo));
        if ($sx === '' || $sx === 'ambos') {
            return 'Todos';
        }
        $options = referencia_sexo_dropdown_options();

        return $options[$sx] ?? ucfirst($sx);
    }
}

if (!function_exists('referencia_sexo_short_label')) {
    /**
     * Abreviatura para listados (M, F, Todos, etc.).
     */
    function referencia_sexo_short_label(?string $sexo): string
    {
        $sx = strtolower(trim((string) $sexo));

        return match ($sx) {
            'masculino' => 'M',
            'femenino'  => 'F',
            'ambos', '' => 'Todos',
            default     => function_exists('mb_substr')
                ? mb_substr(referencia_sexo_label($sexo), 0, 1, 'UTF-8')
                : substr(referencia_sexo_label($sexo), 0, 1),
        };
    }
}

if (!function_exists('referencia_sexo_is_valid')) {
    function referencia_sexo_is_valid(?string $sexo): bool
    {
        $sx = strtolower(trim((string) $sexo));
        if ($sx === 'ambos' || $sx === 'masculino' || $sx === 'femenino') {
            return true;
        }
        if (preg_match('/^g(\d+)$/', $sx, $m) !== 1) {
            return false;
        }
        try {
            return model(\App\Models\GeneroModel::class)->getNombreById((int) $m[1]) !== null;
        } catch (\Throwable $e) {
            return false;
        }
    }
}

if (!function_exists('referencia_sexo_normalize_for_save')) {
    /**
     * Normaliza sexo para guardar sin alterar ambos/masculino/femenino existentes.
     */
    function referencia_sexo_normalize_for_save(?string $sexo): string
    {
        $sx = strtolower(trim((string) $sexo));
        if ($sx === 'masculino' || $sx === 'femenino') {
            return $sx;
        }
        if (preg_match('/^g(\d+)$/', $sx) === 1 && referencia_sexo_is_valid($sx)) {
            return $sx;
        }

        return 'ambos';
    }
}
