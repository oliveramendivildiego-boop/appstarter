<?php

namespace App\Services;

use App\Models\AppConfigModel;
use App\Models\FacturaComprobanteModel;
use App\Models\ReciboComprobanteModel;
use App\Models\RegisterModel;
use App\Services\ConfigService;

/**
 * Arma los datos y el HTML para recibo o factura en PDF según configuración SIN.
 */
class BillingDocumentService
{
    private const TIPO_PAGO = [
        '1' => 'Efectivo',
        '2' => 'QR',
        '3' => 'Transferencia',
        '4' => 'Pendiente',
    ];

    public function __construct(
        private ?RegisterModel $registerModel = null,
        private ?AppConfigModel $appConfigModel = null,
        private ?LayoutService $layoutService = null,
        private ?ConfigService $configService = null,
    ) {
        $this->registerModel  = $registerModel ?? model(RegisterModel::class);
        $this->appConfigModel = $appConfigModel ?? model(AppConfigModel::class);
        $this->layoutService  = $layoutService ?? new LayoutService();
        $this->configService  = $configService ?? new ConfigService($this->appConfigModel);
    }

    public function isSinBillingEnabled(): bool
    {
        return $this->appConfigModel->getValue('sin_billing_enabled') === '1';
    }

    /**
     * @return ReciboComprobanteModel|FacturaComprobanteModel|null
     */
    public function buildComprobante(int $registroId, bool $comoFactura): ?object
    {
        $reg = $this->registerModel->getInfoRefill($registroId);
        if (!$reg) {
            return null;
        }
        $pago = $this->registerModel->getPagoByRegistroId($registroId);
        if (!$pago) {
            return null;
        }

        helper('registro');

        $layout = $this->layoutService->getConfig();
        $moneda = $layout['currency_symbol'] ?? 'Bs';

        try {
            $dt = new \DateTime($reg->ingreso ?? 'now');
            $fechaEmision = $dt->format('d/m/Y H:i');
        } catch (\Throwable) {
            $fechaEmision = (string) ($reg->ingreso ?? '');
        }

        $pacienteNombre = trim(
            ($reg->first_name ?? '') . ' ' . ($reg->last_name_fa ?? '') . ' ' . ($reg->last_name_mom ?? '')
        );
        $lineas = $this->registerModel->getPruebasLineasComerciales((string) ($reg->pruebas ?? ''));
        $total  = (float) ($pago->total ?? 0);
        $totalBruto = 0.0;
        foreach ($lineas as $ln) {
            $totalBruto += (float) ($ln['importe'] ?? 0);
        }
        if ($lineas === [] && $total > 0) {
            $lineas[] = ['descripcion' => 'Servicios de laboratorio', 'importe' => $total];
            $totalBruto = $total;
        }
        if ($lineas === []) {
            $lineas[] = ['descripcion' => 'Sin detalle de pruebas', 'importe' => 0.0];
        }

        $tip = trim((string) ($pago->tipopago ?? ''));
        $formaPago = self::TIPO_PAGO[$tip] ?? ($tip !== '' ? $tip : '-');

        $doctorNombre = trim((string) ($reg->doctor_name ?? ''));
        $doctorGender = isset($reg->doctor_gender) ? (int) $reg->doctor_gender : null;
        if ($doctorGender !== null && $doctorGender !== 1 && $doctorGender !== 2) {
            $doctorGender = null;
        }

        // Para el comprobante, "Total" debe reflejar la sumatoria bruta (sin descuento).
        $totalReco = $totalBruto > 0 ? $totalBruto : (float) ($pago->total_reco ?? 0);
        $montoPag  = (float) ($pago->monto_pagar ?? 0);
        $saldo     = (float) ($pago->saldo ?? 0);
        $institucionNombre = trim((string) ($reg->customer_institucion ?? ''));
        $institucionDescuentoPct = 0.0;
        if ($institucionNombre !== '') {
            $discounts = $this->configService->getCustomerInstitutionDiscounts();
            $needle = function_exists('mb_strtolower') ? mb_strtolower($institucionNombre, 'UTF-8') : strtolower($institucionNombre);
            foreach ($discounts as $instCfg => $pctCfg) {
                $cfgNeedle = function_exists('mb_strtolower') ? mb_strtolower(trim((string) $instCfg), 'UTF-8') : strtolower(trim((string) $instCfg));
                if ($cfgNeedle === $needle) {
                    $institucionDescuentoPct = max(0, min(100, (float) $pctCfg));
                    break;
                }
            }
        }
        $institucionDescuentoMonto = 0.0;
        if ($totalBruto > 0 && $totalBruto >= $total && $institucionDescuentoPct > 0) {
            $institucionDescuentoMonto = round($totalBruto - $total, 2);
        }

        $ordenNum = registro_orden_display($reg);
        $empresa  = trim((string) ($layout['company'] ?? 'Laboratorio'));

        if ($comoFactura) {
            $razon = trim((string) $this->appConfigModel->getValue('sin_business_name'));
            $nit   = trim((string) $this->appConfigModel->getValue('sin_nit'));
            $suc   = trim((string) $this->appConfigModel->getValue('sin_branch_code'));
            $caen  = trim((string) $this->appConfigModel->getValue('sin_activity_code'));
            $nombreFactura = $razon !== '' ? $razon : $empresa;

            return new FacturaComprobanteModel(
                $nombreFactura,
                $ordenNum,
                $fechaEmision,
                $pacienteNombre,
                trim((string) ($reg->ci ?? '')),
                trim((string) ($reg->phone_number ?? '')),
                $lineas,
                $totalReco,
                $total,
                $montoPag,
                $saldo,
                $formaPago,
                $moneda,
                $doctorNombre,
                $doctorGender,
                $institucionNombre,
                $institucionDescuentoPct,
                $institucionDescuentoMonto,
                $nit,
                $razon !== '' ? $razon : $empresa,
                $suc !== '' ? $suc : '1',
                $caen,
            );
        }

        return new ReciboComprobanteModel(
            $empresa,
            $ordenNum,
            $fechaEmision,
            $pacienteNombre,
            trim((string) ($reg->ci ?? '')),
            trim((string) ($reg->phone_number ?? '')),
            $lineas,
            $totalReco,
            $total,
            $montoPag,
            $saldo,
            $formaPago,
            $moneda,
            $doctorNombre,
            $doctorGender,
            $institucionNombre,
            $institucionDescuentoPct,
            $institucionDescuentoMonto,
        );
    }

    public function renderComprobanteHtml(object $doc, bool $comoFactura): string
    {
        if ($comoFactura && $doc instanceof FacturaComprobanteModel) {
            return (string) view('registers/billing/factura_pdf', ['doc' => $doc]);
        }

        return (string) view('registers/billing/recibo_pdf', ['doc' => $doc]);
    }
}
