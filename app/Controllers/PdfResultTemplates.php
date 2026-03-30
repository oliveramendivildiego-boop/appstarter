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

        return view('config/pdf_templates_edit', [
            'template'                 => $template,
            'layout'                   => $layout,
            'block_labels'             => ReportPdfLayoutService::blockLabels(),
            'element_type_labels'      => ReportPdfLayoutService::elementTypeLabels(),
            'element_preview_samples'  => ReportPdfLayoutService::elementPreviewSamples(),
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

        if ($id < 1 || $name === '') {
            return redirect()->to('config/pdf-templates')->with('error', 'Datos inválidos.');
        }
        if (strlen($layoutJson) > 60000) {
            return redirect()->to('config/pdf-templates/edit/' . $id)->with('error', 'El diseño es demasiado grande.');
        }

        $model = model(ReportPdfTemplateModel::class);
        if (!$model->find($id)) {
            return redirect()->to('config/pdf-templates')->with('error', 'Plantilla no encontrada.');
        }

        $layoutService = new ReportPdfLayoutService();
        $normalized    = $layoutService->normalizeLayout($layoutJson);
        $jsonOut       = json_encode($normalized, JSON_UNESCAPED_UNICODE);

        $model->update($id, [
            'name'        => $name,
            'layout_json' => $jsonOut,
        ]);

        (new ConfigService())->invalidateCache();

        return redirect()->to('config/pdf-templates/edit/' . $id)->with('success', 'Plantilla guardada.');
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

        $model->delete($id);
        (new ConfigService())->invalidateCache();

        return redirect()->to('config/pdf-templates')->with('success', 'Plantilla eliminada.');
    }
}
