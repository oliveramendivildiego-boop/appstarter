<?php
/**
 * Formulario de ficha clínica enlazada a una prueba en /registers.
 *
 * @var int $prianacategoria_id
 * @var int $ficha_clinica_id
 * @var string $titulo_prueba
 * @var array<string, string> $existentes
 * @var array<string, mixed> $matriz_config
 * @var object|null $registerModel
 */
echo view('registers/partial_cultivo_fill', [
    'prianacategoria_id'     => (int) ($prianacategoria_id ?? 0),
    'titulo_prueba'          => (string) ($titulo_prueba ?? ''),
    'existentes'             => is_array($existentes ?? null) ? $existentes : [],
    'registerModel'          => $registerModel ?? null,
    'es_personalizado'       => true,
    'matriz_config_override' => is_array($matriz_config ?? null) ? $matriz_config : null,
    'valor_key_prefix'       => 'fc',
    'ficha_clinica_id'       => (int) ($ficha_clinica_id ?? 0),
]);
