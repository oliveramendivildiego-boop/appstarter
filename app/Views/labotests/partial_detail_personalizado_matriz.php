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
echo view('labotests/partial_detail_cultivo_matriz', [
    'labotests_info'   => $labotests_info,
    'cultivo_matriz'   => $personalizado_matriz ?? [],
    'opciones'         => $opciones ?? [],
    'leyendas_cultivo' => $leyendas_cultivo ?? [],
    'matriz_tipo'      => 'personalizado',
]);
