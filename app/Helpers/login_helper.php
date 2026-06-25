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
