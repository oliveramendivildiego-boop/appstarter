<?php

namespace App\Controllers;

use Endroid\QrCode\Builder\Builder;
use Endroid\QrCode\Encoding\Encoding;
use Endroid\QrCode\ErrorCorrectionLevel;
use Endroid\QrCode\Writer\PngWriter;

/**
 * Genera códigos QR localmente (sin APIs externas).
 */
class Qr extends BaseController
{
    public function generate()
    {
        $data = $this->request->getGet('data');
        $size = (int) ($this->request->getGet('size') ?? 120);
        $size = max(50, min(500, $size));

        if (empty($data)) {
            return $this->response
                ->setStatusCode(400)
                ->setBody('Parámetro "data" requerido');
        }

        try {
            $builder = new Builder(
                writer: new PngWriter(),
                data: $data,
                encoding: new Encoding('UTF-8'),
                errorCorrectionLevel: ErrorCorrectionLevel::Low,
                size: $size,
                margin: 10
            );
            $result = $builder->build();
        } catch (\Throwable $e) {
            return $this->response
                ->setStatusCode(500)
                ->setBody('Error generando QR');
        }

        return $this->response
            ->setHeader('Content-Type', $result->getMimeType())
            ->setBody($result->getString());
    }
}
