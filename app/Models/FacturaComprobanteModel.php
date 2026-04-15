<?php

namespace App\Models;

/**
 * Datos estructurados para el PDF de factura (cuando la facturación SIN está habilitada).
 * Documento de respaldo interno; la factura electrónica oficial depende de la integración con el SIN.
 */
class FacturaComprobanteModel extends ReciboComprobanteModel
{
    /**
     * @param list<array{descripcion: string, importe: float}> $lineas
     */
    public function __construct(
        string $empresaNombre,
        string $ordenNumero,
        string $fechaEmision,
        string $pacienteNombre,
        string $pacienteCi,
        string $pacienteTelefono,
        array $lineas,
        float $totalRecomendado,
        float $total,
        float $montoPagado,
        float $saldo,
        string $formaPagoEtiqueta,
        string $monedaSimbolo,
        string $doctorNombre,
        ?int $doctorGender,
        string $institucionNombre,
        float $institucionDescuentoPct,
        float $institucionDescuentoMonto,
        public string $nitEmpresa,
        public string $razonSocial,
        public string $codigoSucursal,
        public string $codigoActividad,
    ) {
        parent::__construct(
            $empresaNombre,
            $ordenNumero,
            $fechaEmision,
            $pacienteNombre,
            $pacienteCi,
            $pacienteTelefono,
            $lineas,
            $totalRecomendado,
            $total,
            $montoPagado,
            $saldo,
            $formaPagoEtiqueta,
            $monedaSimbolo,
            $doctorNombre,
            $doctorGender,
            $institucionNombre,
            $institucionDescuentoPct,
            $institucionDescuentoMonto,
        );
    }
}
