<?php

namespace App\Controllers;

use App\Models\ReportPdfTemplateModel;
use App\Services\ConfigService;
use App\Services\ReportPdfLayoutService;
use CodeIgniter\HTTP\ResponseInterface;

/**
 * Editor de plantillas del PDF de resultados (orden y visibilidad de bloques).
 */
class PdfResultTemplates extends SecureArea
{
    protected ?string $moduleId = 'config';

    /** @var list<string> */
    private const PDF_TEMPLATE_EDIT_TABS = [
        'general',
        'header',
        'patient_doctor',
        'results',
        'notes',
        'lab_firmas',
        'footer',
    ];

    private function sanitizePdfTemplateEditTab(?string $tab): string
    {
        $tab = trim((string) $tab);

        return in_array($tab, self::PDF_TEMPLATE_EDIT_TABS, true) ? $tab : 'general';
    }

    private function pdfTemplateEditUrl(int $id, ?string $tab = null): string
    {
        $url = 'config/pdf-templates/edit/' . $id;
        $tab = $this->sanitizePdfTemplateEditTab($tab);
        if ($tab !== 'general') {
            $url .= '?tab=' . rawurlencode($tab);
        }

        return $url;
    }

    public function index()
    {
        $templates = [];
        $dbError   = null;
        try {
            $model     = model(ReportPdfTemplateModel::class);
            $templates = $model->orderBy('name', 'ASC')->findAll();
        } catch (\Throwable $e) {
            $dbError = $e->getMessage();
        }

        $activeId = 0;
        try {
            $activeId = (int) model(\App\Models\AppConfigModel::class)->getValue('pdf_result_template_id');
        } catch (\Throwable $e) {
            // ignorar
        }

        return view('config/pdf_templates_index', [
            'templates'          => $templates,
            'active_template_id' => $activeId,
            'db_error'           => $dbError,
            'allowed_modules'    => $this->allowed_modules,
            'user_info'          => $this->user_info,
            'current_module'     => 'config',
        ]);
    }

    public function edit(int $id = 0)
    {
        $id = max(0, $id);
        $model = model(ReportPdfTemplateModel::class);
        $template = $model->find($id);
        if (!$template) {
            return redirect()->to('config/pdf-templates')->with('error', 'Plantilla no encontrada.');
        }

        $layoutService = new ReportPdfLayoutService();
        $layout        = $layoutService->layoutJsonForEditor($template);
        $configModel   = model(\App\Models\AppConfigModel::class);
        $activePdfTplId    = (int) $configModel->getValue('pdf_result_template_id');
        $activePrintTplId  = (int) $configModel->getValue('print_result_template_id');
        if ($activePrintTplId < 1) {
            $activePrintTplId = $activePdfTplId;
        }

        return view('config/pdf_templates_edit', [
            'template'                 => $template,
            'layout'                   => $layout,
            'config_tab'               => $this->sanitizePdfTemplateEditTab($this->request->getGet('tab')),
            'active_pdf_template_id'   => $activePdfTplId,
            'active_print_template_id' => $activePrintTplId,
            'pdf_order_sheet_header_global' => \App\Services\ReportPdfLayoutService::isTenantOrderSheetHeaderGloballyEnabled(),
            'block_labels'             => ReportPdfLayoutService::blockLabels(),
            'element_type_labels'      => ReportPdfLayoutService::elementTypeLabels(),
            'element_preview_samples'  => ReportPdfLayoutService::elementPreviewSamples(),
            'pdf_style_allowlists'     => ReportPdfLayoutService::styleAllowlistsForClient(),
            'allowed_modules'          => $this->allowed_modules,
            'user_info'                => $this->user_info,
            'current_module'           => 'config',
        ]);
    }

    public function save(): ResponseInterface
    {
        $id = (int) $this->request->getPost('id');
        $name = trim((string) $this->request->getPost('name'));
        $layoutJson = (string) $this->request->getPost('layout_json');
        $configTab = $this->sanitizePdfTemplateEditTab($this->request->getPost('config_tab'));

        if ($id < 1 || $name === '') {
            return redirect()->to('config/pdf-templates')->with('error', 'Datos inválidos.');
        }
        if (strlen($layoutJson) > 60000) {
            return redirect()->to($this->pdfTemplateEditUrl($id, $configTab))->with('error', 'El diseño es demasiado grande.');
        }

        $model = model(ReportPdfTemplateModel::class);
        if (! $model->find($id)) {
            return redirect()->to('config/pdf-templates')->with('error', 'Plantilla no encontrada.');
        }

        $decoded = json_decode($layoutJson, true);
        if (! is_array($decoded) || empty($decoded['blocks']) || ! is_array($decoded['blocks'])) {
            return redirect()->to($this->pdfTemplateEditUrl($id, $configTab))->with('error', 'Diseño JSON inválido.');
        }

        $styleErr = ReportPdfLayoutService::validateLayoutDecodedStyles($decoded);
        if ($styleErr !== null) {
            return redirect()->to($this->pdfTemplateEditUrl($id, $configTab))->with('error', $styleErr);
        }

        if ($this->request->getPost('watermark_remove') === '1') {
            $oldRel = is_array($decoded['watermark'] ?? null) ? ($decoded['watermark']['file'] ?? null) : null;
            if (is_string($oldRel) && $oldRel !== '') {
                $safe = ReportPdfLayoutService::sanitizeWatermarkRelativePath($oldRel);
                if ($safe !== null) {
                    $full = WRITEPATH . str_replace('/', DIRECTORY_SEPARATOR, $safe);
                    if (is_file($full)) {
                        @unlink($full);
                    }
                }
            }
            $decoded['watermark'] = array_merge(
                is_array($decoded['watermark'] ?? null) ? $decoded['watermark'] : ReportPdfLayoutService::defaultWatermarkStatic(),
                [
                    'file'    => null,
                    'enabled' => false,
                ]
            );
        }

        $upload = $this->request->getFile('watermark_upload');
        if ($upload && $upload->isValid() && ! $upload->hasMoved()) {
            $ext = strtolower((string) $upload->getClientExtension());
            $allowed = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
            if (! in_array($ext, $allowed, true)) {
                return redirect()->to($this->pdfTemplateEditUrl($id, $configTab))->with('error', 'Marca de agua: use PNG, JPG, GIF o WebP.');
            }
            if ($upload->getSize() > 2097152) {
                return redirect()->to($this->pdfTemplateEditUrl($id, $configTab))->with('error', 'La imagen de marca de agua no debe superar 2 MB.');
            }

            $dir = WRITEPATH . 'uploads' . DIRECTORY_SEPARATOR . 'report_pdf_templates' . DIRECTORY_SEPARATOR . $id;
            if (! is_dir($dir)) {
                mkdir($dir, 0755, true);
            }
            $newName = 'wm_' . bin2hex(random_bytes(8)) . '.' . $ext;
            $upload->move($dir, $newName);
            if (! is_file($dir . DIRECTORY_SEPARATOR . $newName)) {
                return redirect()->to($this->pdfTemplateEditUrl($id, $configTab))->with('error', 'No se pudo guardar la marca de agua.');
            }
            $rel = 'uploads/report_pdf_templates/' . $id . '/' . $newName;
            $decoded['watermark'] = array_merge(
                is_array($decoded['watermark'] ?? null) ? $decoded['watermark'] : ReportPdfLayoutService::defaultWatermarkStatic(),
                [
                    'file'    => $rel,
                    'enabled' => true,
                ]
            );
        }

        $layoutService = new ReportPdfLayoutService();
        $normalized    = $layoutService->normalizeLayout(json_encode($decoded, JSON_UNESCAPED_UNICODE));
        $jsonOut       = json_encode($normalized, JSON_UNESCAPED_UNICODE);

        $model->update($id, [
            'name'        => $name,
            'layout_json' => $jsonOut,
        ]);

        (new ConfigService())->invalidateCache();

        return redirect()->to($this->pdfTemplateEditUrl($id, $configTab))->with('success', 'Plantilla guardada.');
    }

    public function create(): ResponseInterface
    {
        $name = trim((string) $this->request->getPost('name'));
        if ($name === '') {
            return redirect()->to('config/pdf-templates')->with('error', 'Indique un nombre para la plantilla.');
        }

        $layoutService = new ReportPdfLayoutService();
        $json          = json_encode($layoutService->getDefaultLayout(), JSON_UNESCAPED_UNICODE);

        $model = model(ReportPdfTemplateModel::class);
        $model->insert([
            'name'        => $name,
            'layout_json' => $json,
        ]);
        $newId = (int) $model->getInsertID();
        (new ConfigService())->invalidateCache();

        if ($newId < 1) {
            return redirect()->to('config/pdf-templates')->with('error', 'No se pudo crear la plantilla.');
        }

        return redirect()->to('config/pdf-templates/edit/' . $newId)->with('success', 'Plantilla creada. Arrastre los bloques y guarde.');
    }

    public function duplicate(int $id = 0): ResponseInterface
    {
        $id   = max(0, $id);
        $name = trim((string) $this->request->getPost('name'));
        if ($name === '') {
            return redirect()->to('config/pdf-templates')->with('error', 'Indique un nombre para la copia.');
        }

        $model  = model(ReportPdfTemplateModel::class);
        $source = $model->find($id);
        if (! $source) {
            return redirect()->to('config/pdf-templates')->with('error', 'Plantilla no encontrada.');
        }

        $layoutJson = (string) ($source->layout_json ?? '');
        $model->insert([
            'name'        => $name,
            'layout_json' => $layoutJson,
        ]);
        $newId = (int) $model->getInsertID();
        if ($newId < 1) {
            return redirect()->to('config/pdf-templates')->with('error', 'No se pudo duplicar la plantilla.');
        }

        $decoded = json_decode($layoutJson, true);
        if (is_array($decoded)) {
            $wm = is_array($decoded['watermark'] ?? null) ? $decoded['watermark'] : [];
            $wmFile = isset($wm['file']) ? (string) $wm['file'] : '';
            $safe   = ReportPdfLayoutService::sanitizeWatermarkRelativePath($wmFile);
            if ($safe !== null) {
                $srcFull = WRITEPATH . str_replace('/', DIRECTORY_SEPARATOR, $safe);
                if (is_file($srcFull)) {
                    $ext = strtolower((string) pathinfo($srcFull, PATHINFO_EXTENSION));
                    $dir = WRITEPATH . 'uploads' . DIRECTORY_SEPARATOR . 'report_pdf_templates' . DIRECTORY_SEPARATOR . $newId;
                    if (! is_dir($dir)) {
                        mkdir($dir, 0755, true);
                    }
                    $newFileName = 'wm_' . bin2hex(random_bytes(8)) . ($ext !== '' ? '.' . $ext : '');
                    $destFull    = $dir . DIRECTORY_SEPARATOR . $newFileName;
                    if (copy($srcFull, $destFull)) {
                        $decoded['watermark'] = array_merge(
                            ReportPdfLayoutService::defaultWatermarkStatic(),
                            $wm,
                            ['file' => 'uploads/report_pdf_templates/' . $newId . '/' . $newFileName]
                        );
                        $layoutService = new ReportPdfLayoutService();
                        $normalized    = $layoutService->normalizeLayout(json_encode($decoded, JSON_UNESCAPED_UNICODE));
                        $model->update($newId, [
                            'layout_json' => json_encode($normalized, JSON_UNESCAPED_UNICODE),
                        ]);
                    }
                }
            }
        }

        (new ConfigService())->invalidateCache();

        return redirect()->to('config/pdf-templates/edit/' . $newId)->with('success', 'Plantilla duplicada con toda su configuración.');
    }

    public function delete(int $id = 0)
    {
        $id = max(0, $id);
        $model = model(ReportPdfTemplateModel::class);
        $all = $model->orderBy('id', 'ASC')->findAll();
        if (count($all) <= 1) {
            return redirect()->to('config/pdf-templates')->with('error', 'Debe existir al menos una plantilla.');
        }

        $exists = false;
        foreach ($all as $t) {
            if ((int) $t->id === $id) {
                $exists = true;
                break;
            }
        }
        if (!$exists) {
            return redirect()->to('config/pdf-templates')->with('error', 'Plantilla no encontrada.');
        }

        $configModel = model(\App\Models\AppConfigModel::class);
        $activeId = (int) $configModel->getValue('pdf_result_template_id');
        if ($activeId === $id) {
            foreach ($all as $t) {
                if ((int) $t->id !== $id) {
                    $configModel->saveValue('pdf_result_template_id', (string) $t->id);
                    break;
                }
            }
        }
        $printId = (int) $configModel->getValue('print_result_template_id');
        if ($printId === $id) {
            foreach ($all as $t) {
                if ((int) $t->id !== $id) {
                    $configModel->saveValue('print_result_template_id', (string) $t->id);
                    break;
                }
            }
        }

        $model->delete($id);
        (new ConfigService())->invalidateCache();

        return redirect()->to('config/pdf-templates')->with('success', 'Plantilla eliminada.');
    }
}
