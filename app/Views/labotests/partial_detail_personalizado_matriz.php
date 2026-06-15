<?php
/**
 * Matriz configurable para pruebas tipo Personalizado.
 * Reutiliza el editor de cultivo con opciones extra por celda.
 *
 * @var object $labotests_info
 * @var array $personalizado_matriz
 * @var array<int, string> $opciones
 * @var array<int, array<string, mixed>> $leyendas_cultivo
 */
echo view('labotests/partial_detail_cultivo_matriz', array_merge([
    'labotests_info'   => $labotests_info,
    'cultivo_matriz'   => $personalizado_matriz ?? [],
    'opciones'         => $opciones ?? [],
    'leyendas_cultivo' => $leyendas_cultivo ?? [],
    'matriz_tipo'      => 'personalizado',
], array_intersect_key(get_defined_vars(), array_flip([
    'matriz_save_url',
    'matriz_entity_field',
    'matriz_entity_id',
    'matriz_show_export_import',
    'matriz_context_label',
    'matriz_export_url',
    'matriz_import_url',
    'matriz_import_confirm_message',
    'matriz_export_import_description',
    'matriz_export_import_badge',
]))));
