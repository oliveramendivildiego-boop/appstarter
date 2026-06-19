<?php

namespace Config;

/**
 * Mapa de colores y escalas ordinales para comparación seriada categórica.
 * Modifique estos arreglos para ajustar la semántica visual del heatmap.
 */
class CategoricalComparisonMap
{
    /** @var array<string, string> slug normalizado => clase CSS del badge */
    public const BADGE_CLASSES = [
        'neutral'     => 'catcmp-badge--neutral',
        'escaso'      => 'catcmp-badge--escaso',
        'moderado'    => 'catcmp-badge--moderado',
        'abundante'   => 'catcmp-badge--abundante',
        'presente'    => 'catcmp-badge--presente',
        'descriptivo' => 'catcmp-badge--descriptivo',
        'default'     => 'catcmp-badge--default',
    ];

    /**
     * Valores considerados neutros / ausencia de hallazgo.
     *
     * @var list<string>
     */
    public const NEUTRAL_VALUES = [
        'ausente',
        'negativo',
        'no reactivo',
        'no reactiva',
        'normal',
        'no observado',
        'no se observa',
        'no se observan',
        'sin hallazgos',
        '-',
    ];

    /**
     * Valores de presencia explícita (positivos).
     *
     * @var list<string>
     */
    public const PRESENCE_VALUES = [
        'presente',
        'positivo',
        'reactivo',
        'reactiva',
        'detectado',
        'detectada',
        'si',
        'sí',
    ];

    /**
     * Escala ordinal conocida (menor índice = menor intensidad).
     *
     * @var list<string>
     */
    public const ORDINAL_SCALE = [
        'escaso',
        'escasos',
        'escasa',
        'escasas',
        'moderado',
        'moderados',
        'moderada',
        'moderadas',
        'abundante',
        'abundantes',
    ];

    /**
     * Etiquetas de sección reconocidas en separadores de prueba seriada.
     *
     * @var array<string, string> clave interna => título visible
     */
    public const SECTION_LABELS = [
        'macroscopico'  => 'Examen macroscópico',
        'microscopico'  => 'Examen microscópico',
        'otros'         => 'Otros elementos',
    ];

    /** @var list<string> */
    public const SAMPLE_LABELS = ['M1', 'M2', 'M3'];

    /** @var list<string> */
    public const LEGEND_ITEMS = [
        ['class' => 'catcmp-badge--neutral', 'label' => 'Ausente / Negativo'],
        ['class' => 'catcmp-badge--escaso', 'label' => 'Escaso'],
        ['class' => 'catcmp-badge--moderado', 'label' => 'Moderado'],
        ['class' => 'catcmp-badge--abundante', 'label' => 'Abundante'],
        ['class' => 'catcmp-badge--presente', 'label' => 'Presente / Positivo'],
        ['class' => 'catcmp-badge--descriptivo', 'label' => 'Hallazgo descriptivo (Giardia, Entamoeba, etc.)'],
    ];
}
