<?php

if (! function_exists('login_feature_highlights')) {
    /**
     * Destacados estáticos del panel izquierdo del login.
     *
     * @return list<array{title: string, text: string}>
     */
    function login_feature_highlights(): array
    {
        return [
            [
                'title' => 'Recepción y órdenes',
                'text'  => 'Pacientes, cotizaciones y ventanilla en un solo flujo.',
            ],
            [
                'title' => 'Resultados e informes',
                'text'  => 'Carga, validación y entrega de PDF profesionales.',
            ],
            [
                'title' => 'Inventario y calidad',
                'text'  => 'Reactivos, kardex, lotes y control Levey-Jennings.',
            ],
            [
                'title' => 'Portal médico',
                'text'  => 'Doctores y pacientes conectados con su laboratorio.',
            ],
        ];
    }
}

if (! function_exists('login_hex_is_dark')) {
    function login_hex_is_dark(string $hex, int $threshold = 55): bool
    {
        $hex = ltrim(trim($hex), '#');
        if (strlen($hex) === 3) {
            $hex = $hex[0] . $hex[0] . $hex[1] . $hex[1] . $hex[2] . $hex[2];
        }
        if (strlen($hex) !== 6 || ! ctype_xdigit($hex)) {
            return false;
        }

        $r = hexdec(substr($hex, 0, 2));
        $g = hexdec(substr($hex, 2, 2));
        $b = hexdec(substr($hex, 4, 2));

        return (0.299 * $r + 0.587 * $g + 0.114 * $b) < $threshold;
    }
}

if (! function_exists('login_matrix_rain_color')) {
    /**
     * Color visible para la lluvia Matrix sobre fondo oscuro.
     */
    function login_matrix_rain_color(string $themeColor, string $gradientEnd): string
    {
        if (! login_hex_is_dark($themeColor)) {
            return $themeColor;
        }

        if (! login_hex_is_dark($gradientEnd)) {
            return $gradientEnd;
        }

        return '#2dd4bf';
    }
}

if (! function_exists('login_hero_backgrounds')) {
    /**
     * Fotografías de laboratorio para el fondo del panel izquierdo.
     * Fuente: Pexels (uso libre).
     *
     * @return list<string> Rutas relativas a public/
     */
    function login_hero_backgrounds(): array
    {
        $files = [
            'images/login/bg-lab-1.jpg',
            'images/login/bg-lab-2.jpg',
            'images/login/bg-lab-3.jpg',
            'images/login/bg-lab-4.jpg',
        ];

        return array_values(array_filter($files, static function (string $path): bool {
            return is_file(FCPATH . $path);
        }));
    }
}
