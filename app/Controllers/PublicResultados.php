<?php

namespace App\Controllers;

use App\Libraries\PdfService;
use App\Models\RegisterModel;
use App\Services\RegisterService;

/**
 * Visor de resultados por enlace secreto (sin sesión de empleado ni doctor).
 */
class PublicResultados extends BaseController
{
    public function __construct()
    {
        helper(['url', 'form']);
    }

    public function view(string $token)
    {
        $registerModel = model(RegisterModel::class);
        $id            = $registerModel->findRegistroIdByPublicToken($token);
        if ($id === null) {
            return $this->response->setStatusCode(404)->setBody(view('errors/html/public_link_invalid'));
        }
        if ($registerModel->isRegistroAnulado($id)) {
            return $this->response->setStatusCode(410)->setBody(view('errors/html/public_link_invalid', [
                'message' => 'Esta orden ya no está disponible.',
            ]));
        }

        $registerService = new RegisterService();
        $data            = $registerService->prepareReportData($id);
        if (! $data) {
            return $this->response->setStatusCode(404)->setBody(view('errors/html/public_link_invalid'));
        }

        return view('registers/public_viewreport', [
            'register_info'                   => $data['register_info'],
            'labotests_namecate'              => $id,
            'paciente'                        => $data['paciente'],
            'doctor'                          => $data['doctor'],
            'analisis'                        => $data['analisis'],
            'grupos'                          => $data['grupos'],
            'report_pria_tipo_muestra_nombre' => $data['report_pria_tipo_muestra_nombre'] ?? [],
            'report_pria_metodo_nombre'       => $data['report_pria_metodo_nombre'] ?? [],
            'report_lab_firmas'               => $data['report_lab_firmas'] ?? [],
            'report_pria_refs_consolidada'    => $data['report_pria_refs_consolidada'] ?? [],
            'report_emitido_en'               => $registerService->reportEmitidoEnForView($id),
            'public_resultados_token'         => strtolower((string) preg_replace('/[^a-f0-9]/', '', $token)),
            'doctor_assigned'                 => $registerModel->registroTieneDoctorAsignado($id),
        ]);
    }

    public function pdf(string $token)
    {
        $registerModel = model(RegisterModel::class);
        $id            = $registerModel->findRegistroIdByPublicToken($token);
        if ($id === null) {
            return $this->response->setStatusCode(404)->setBody('Enlace no válido.');
        }
        if ($registerModel->isRegistroAnulado($id)) {
            return $this->response->setStatusCode(410)->setBody('Esta orden ya no está disponible.');
        }

        $registerService = new RegisterService();
        $data            = $registerService->prepareReportData($id);
        if (! $data) {
            return $this->response->setStatusCode(404)->setBody('Registro no encontrado.');
        }
        if (! $registerModel->registroTieneDoctorAsignado($id)) {
            return $this->response->setStatusCode(403)->setBody('Debe asignarse un doctor antes de generar el PDF.');
        }

        $cleanToken = strtolower((string) preg_replace('/[^a-f0-9]/', '', $token));
        helper('qr');
        $reportUrl = site_url('resultados/' . $cleanToken);
        $qrDataUri = qr_base64($reportUrl, 100);
        $emitidoEn = $registerService->lockReportEmitidoEnForPrintOrPdf($id);
        $html      = $registerService->renderReportPdfHtml($data, $reportUrl, $qrDataUri, $emitidoEn);

        $pdfService     = new PdfService();
        $pacienteNombre = trim(($data['paciente']->first_name ?? '') . '_' . ($data['paciente']->last_name_fa ?? ''));
        $filename       = 'Resultados_' . ($pacienteNombre ?: 'paciente') . '_' . $id . '_' . date('Y-m-d') . '.pdf';

        return $this->response
            ->setHeader('Content-Type', 'application/pdf')
            ->setHeader('Content-Disposition', 'attachment; filename="' . $filename . '"')
            ->setBody($pdfService->generate($html, $filename));
    }
}
