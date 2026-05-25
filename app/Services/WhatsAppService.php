<?php

namespace App\Services;

use App\Models\AppConfigModel;

/**
 * Servicio para enviar mensajes y documentos por WhatsApp.
 * Soporta WhatsApp Business API vía:
 * - Meta Cloud API (oficial, recomendado)
 * - Twilio (BSP alternativo)
 *
 * @see https://developers.facebook.com/docs/whatsapp/cloud-api
 * @see https://www.twilio.com/docs/whatsapp
 */
class WhatsAppService
{
    protected AppConfigModel $configModel;

    private const META_API_VERSION = 'v21.0';

    public function __construct(?AppConfigModel $configModel = null)
    {
        $this->configModel = $configModel ?? model(AppConfigModel::class);
    }

    /** @return 'meta'|'twilio'|'' */
    public function getProvider(): string
    {
        $p = trim($this->configModel->getValue('whatsapp_provider') ?? 'meta');
        return in_array($p, ['meta', 'twilio'], true) ? $p : 'meta';
    }

    public function isConfigured(): bool
    {
        $provider = $this->getProvider();
        if ($provider === 'meta') {
            $phoneId = trim($this->configModel->getValue('whatsapp_meta_phone_id') ?? '');
            $token   = trim($this->configModel->getValue('whatsapp_meta_token') ?? '');
            return $phoneId !== '' && $token !== '';
        }
        $sid   = trim($this->configModel->getValue('whatsapp_twilio_account_sid') ?? '');
        $token = trim($this->configModel->getValue('whatsapp_twilio_auth_token') ?? '');
        $from  = trim($this->configModel->getValue('whatsapp_twilio_from') ?? '');
        return $sid !== '' && $token !== '' && $from !== '';
    }

    /**
     * Código de país configurado (solo dígitos, sin +). Por defecto Bolivia (591).
     */
    public function getCountryCode(): string
    {
        $code = preg_replace('/\D/', '', $this->configModel->getValue('whatsapp_country_code') ?: '591');

        return $code !== '' ? $code : '591';
    }

    /**
     * Formatea número para WhatsApp (código país sin +, sin espacios).
     */
    public function formatPhoneForWhatsApp(string $phone): string
    {
        return self::formatPhoneWithCountryCode($phone, $this->getCountryCode());
    }

    /**
     * Formatea un teléfono local o internacional para wa.me / API de WhatsApp.
     */
    public static function formatPhoneWithCountryCode(string $phone, string $countryCode): string
    {
        $countryCode = preg_replace('/\D/', '', $countryCode);
        if ($countryCode === '') {
            $countryCode = '591';
        }

        $clean = preg_replace('/\D/', '', $phone);
        if ($clean === '') {
            return '';
        }

        if (str_starts_with($clean, $countryCode) && strlen($clean) > strlen($countryCode)) {
            return $clean;
        }

        if (strlen($clean) <= 9) {
            return $countryCode . ltrim($clean, '0');
        }

        return $clean;
    }

    /**
     * Envía PDF por WhatsApp
     */
    public function sendWithPdf(string $toPhone, string $message, string $pdfUrl, string $filename = 'resultados.pdf'): array
    {
        if (!$this->isConfigured()) {
            return ['success' => false, 'message' => 'WhatsApp no está configurado. Configure en Configuración > WhatsApp.'];
        }

        $toFormatted = $this->formatPhoneForWhatsApp($toPhone);
        if ($toFormatted === '') {
            return ['success' => false, 'message' => 'Número de teléfono inválido.'];
        }

        return $this->getProvider() === 'meta'
            ? $this->sendWithPdfMeta($toFormatted, $message, $pdfUrl, $filename)
            : $this->sendWithPdfTwilio($toFormatted, $message, $pdfUrl);
    }

    /**
     * Envía PDF vía WhatsApp Cloud API (Meta)
     * Intenta mensaje directo (ventana 24h). Si falla, usa plantilla (primer contacto).
     */
    protected function sendWithPdfMeta(string $toFormatted, string $message, string $pdfUrl, string $filename): array
    {
        $phoneId = trim($this->configModel->getValue('whatsapp_meta_phone_id') ?? '');
        $token   = trim($this->configModel->getValue('whatsapp_meta_token') ?? '');
        $template = trim($this->configModel->getValue('whatsapp_meta_template') ?? '');
        $lang    = trim($this->configModel->getValue('whatsapp_meta_lang') ?? 'es');

        $url = 'https://graph.facebook.com/' . self::META_API_VERSION . '/' . $phoneId . '/messages';

        // 1) Intentar mensaje directo (funciona si el usuario escribió en las últimas 24h)
        $payload = [
            'messaging_product' => 'whatsapp',
            'recipient_type'    => 'individual',
            'to'                => $toFormatted,
            'type'              => 'document',
            'document'          => [
                'link'     => $pdfUrl,
                'caption'  => $message,
                'filename' => $filename,
            ],
        ];

        $result = $this->postJson($url, $token, $payload);
        if ($result['success']) {
            return ['success' => true, 'message' => 'Mensaje enviado correctamente.'];
        }

        // 2) Si falla por ventana 24h (código 131047) y hay plantilla, usar plantilla
        $errorCode = $result['error_code'] ?? 0;
        $canUseTemplate = in_array($errorCode, [131047, 131026, 131031], true); // Re-engagement, session expired, etc.

        if ($canUseTemplate && $template !== '') {
            return $this->sendTemplateMeta($toFormatted, $pdfUrl, $message, $filename, $template, $lang);
        }

        $errMsg = $result['message'] ?? 'Error desconocido';
        if ($errorCode === 131047 || $errorCode === 131026) {
            $errMsg .= ' El destinatario no ha escrito en las últimas 24h. Cree una plantilla aprobada en Meta Business Manager para primer contacto.';
        }
        return ['success' => false, 'message' => $errMsg];
    }

    /**
     * Envía usando plantilla aprobada (primer contacto, fuera de ventana 24h)
     * La plantilla debe tener: header=document (dinámico), body con {{1}}, {{2}}, etc.
     */
    protected function sendTemplateMeta(string $to, string $pdfUrl, string $bodyText, string $filename, string $templateName, string $lang): array
    {
        $phoneId = trim($this->configModel->getValue('whatsapp_meta_phone_id') ?? '');
        $token   = trim($this->configModel->getValue('whatsapp_meta_token') ?? '');
        $url     = 'https://graph.facebook.com/' . self::META_API_VERSION . '/' . $phoneId . '/messages';

        $payload = [
            'messaging_product' => 'whatsapp',
            'to'                => $to,
            'type'              => 'template',
            'template'          => [
                'name'     => $templateName,
                'language' => ['code' => $lang],
                'components' => [
                    [
                        'type'       => 'header',
                        'parameters' => [
                            [
                                'type'     => 'document',
                                'document' => [
                                    'link'     => $pdfUrl,
                                    'filename' => $filename,
                                ],
                            ],
                        ],
                    ],
                    [
                        'type'       => 'body',
                        'parameters' => [
                            ['type' => 'text', 'text' => $bodyText],
                        ],
                    ],
                ],
            ],
        ];

        $result = $this->postJson($url, $token, $payload);
        if ($result['success']) {
            return ['success' => true, 'message' => 'Mensaje enviado correctamente.'];
        }
        return ['success' => false, 'message' => $result['message'] ?? 'Error al enviar plantilla.'];
    }

    /**
     * POST JSON a la API de Meta
     */
    protected function postJson(string $url, string $token, array $payload): array
    {
        try {
            $client   = \Config\Services::curlrequest();
            $response = $client->post($url, [
                'headers' => [
                    'Authorization' => 'Bearer ' . $token,
                    'Content-Type'  => 'application/json',
                ],
                'body'    => json_encode($payload),
                'timeout' => 60,
            ]);

            $body = json_decode($response->getBody(), true);
            $status = $response->getStatusCode();

            if ($status >= 200 && $status < 300) {
                return ['success' => true];
            }

            $error = $body['error'] ?? [];
            return [
                'success'     => false,
                'message'     => $error['message'] ?? 'Error HTTP ' . $status,
                'error_code'   => $error['code'] ?? $status,
            ];
        } catch (\Throwable $e) {
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    /**
     * Envía PDF vía Twilio
     */
    protected function sendWithPdfTwilio(string $toFormatted, string $message, string $pdfUrl): array
    {
        $sid   = trim($this->configModel->getValue('whatsapp_twilio_account_sid') ?? '');
        $token = trim($this->configModel->getValue('whatsapp_twilio_auth_token') ?? '');
        $from  = trim($this->configModel->getValue('whatsapp_twilio_from') ?? '');

        $fromFormatted = str_starts_with($from, 'whatsapp:') ? $from : 'whatsapp:' . $from;
        $toFormatted   = 'whatsapp:' . $toFormatted;

        $url = 'https://api.twilio.com/2010-04-01/Accounts/' . $sid . '/Messages.json';

        $params = http_build_query([
            'To'       => $toFormatted,
            'From'     => $fromFormatted,
            'MediaUrl' => $pdfUrl,
        ] + ($message !== '' ? ['Body' => $message] : []));

        try {
            $client   = \Config\Services::curlrequest();
            $response = $client->post($url, [
                'headers' => [
                    'Authorization' => 'Basic ' . base64_encode($sid . ':' . $token),
                    'Content-Type'  => 'application/x-www-form-urlencoded',
                ],
                'body'    => $params,
                'timeout' => 60,
            ]);

            $status = $response->getStatusCode();
            $body   = json_decode($response->getBody(), true);

            if ($status >= 200 && $status < 300) {
                return ['success' => true, 'message' => 'Mensaje enviado correctamente.'];
            }
            $errorMsg = $body['message'] ?? $body['error_message'] ?? "Error HTTP {$status}";
            return ['success' => false, 'message' => $errorMsg];
        } catch (\Throwable $e) {
            return ['success' => false, 'message' => 'Error Twilio: ' . $e->getMessage()];
        }
    }

    /**
     * Reemplaza placeholders en plantilla de mensaje
     */
    public function applyTemplate(string $template, array $data): string
    {
        $result = $template;
        foreach ($data as $key => $value) {
            $result = str_replace('{' . $key . '}', (string) $value, $result);
        }
        return $result;
    }
}
