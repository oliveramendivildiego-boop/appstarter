<?php

namespace App\Models;

/**
 * Datos estructurados para el PDF de recibo (cuando la facturación SIN está deshabilitada).
 */
class ReciboComprobanteModel
{
    /**
     * @param list<array{descripcion: string, importe: float}> $lineas
     */
    public function __construct(
        public string $empresaNombre,
        public string $ordenNumero,
        public string $fechaEmision,
        public string $pacienteNombre,
        public string $pacienteCi,
        public string $pacienteTelefono,
        public array $lineas,
        public float $totalRecomendado,
        public float $total,
        public float $montoPagado,
        public float $saldo,
        public string $formaPagoEtiqueta,
        public string $monedaSimbolo,
        public string $doctorNombre,
        public ?int $doctorGender = null,
        public string $institucionNombre = '',
        public float $institucionDescuentoPct = 0.0,
        public float $institucionDescuentoMonto = 0.0,
    ) {
    }
}
