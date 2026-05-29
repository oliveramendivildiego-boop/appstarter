<?php

namespace App\Controllers;

use App\Models\AppConfigModel;
use App\Models\EnvelopeTemplateModel;
use App\Services\ConfigService;
use App\Services\EnvelopeLayoutService;
use App\Services\EnvelopeRenderService;
use CodeIgniter\HTTP\ResponseInterface;

/**
 * Plantillas de sobres (matriz editable en configuración).
 */
class EnvelopeTemplates extends SecureArea
{
    protected ?string $moduleId = 'config';

    public function edit(int $id = 0): ResponseInterface|string
    {
        $id = max(0, $id);
        try {
            $model    = model(EnvelopeTemplateModel::class);
            $template = $model->find($id);
        } catch (\Throwable $e) {
            return redirect()->to('config?tab=sobres')->with(
                'error',
                'Tabla de sobres no disponible. Ejecute php spark migrate en la raíz del proyecto.'
            );
        }
        if (! $template) {
            return redirect()->to('config?tab=sobres')->with('error', 'Plantilla de sobre no encontrada.');
        }

        $layoutService = new EnvelopeLayoutService();
        $layout        = $layoutService->layoutJsonForEditor($template);
        $printTemplateId = (new EnvelopeRenderService())->resolvePrintTemplateId();

        $imageBaseUrl = site_url('config/sobres/media/' . $id . '/');

        return view('config/envelope_edit', [
            'template'                => $template,
            'layout'                  => $layout,
            'print_envelope_template_id' => $printTemplateId,
            'envelope_sizes'          => EnvelopeLayoutService::envelopeSizes(),
            'element_type_labels'     => EnvelopeLayoutService::elementTypeLabels(),
            'element_preview_samples' => EnvelopeLayoutService::elementPreviewSamples(),
            'image_base_url'          => $imageBaseUrl,
            'back_url'                => site_url('config?tab=sobres'),
            'allowed_modules'         => $this->allowed_modules,
            'user_info'               => $this->user_info,
            'current_module'          => 'config',
        ]);
    }

    public function save(): ResponseInterface
    {
        $id         = (int) $this->request->getPost('id');
        $name       = trim((string) $this->request->getPost('name'));
        $layoutJson = $this->request->getPost('layout_json');
        if (is_array($layoutJson)) {
            $layoutJson = json_encode($layoutJson, JSON_UNESCAPED_UNICODE);
        }
        $layoutJson = trim((string) $layoutJson);

        if ($id < 1 || $name === '') {
            return redirect()->to('config?tab=sobres')->with('error', 'Datos inválidos.');
        }

        $model = model(EnvelopeTemplateModel::class);
        $existing = $model->find($id);
        if (! $existing) {
            return redirect()->to('config?tab=sobres')->with('error', 'Plantilla no encontrada.');
        }

        if ($layoutJson === '') {
            $layoutJson = trim((string) ($existing->layout_json ?? ''));
        }
        if (strlen($layoutJson) > 80000) {
            return redirect()->to('config/sobres/edit/' . $id)->with('error', 'El diseño es demasiado grande.');
        }

        $decoded = json_decode($layoutJson, true);
        if (! is_array($decoded)) {
            $jsonErr = json_last_error_msg();
            log_message('error', 'Envelope save: JSON inválido (id={id}): {err} — fragmento: {frag}', [
                'id'   => $id,
                'err'  => $jsonErr,
                'frag' => substr($layoutJson, 0, 300),
            ]);

            return redirect()->to('config/sobres/edit/' . $id)->with(
                'error',
                'Diseño JSON inválido. Recargue la página, ajuste la matriz y guarde de nuevo.'
                . ($jsonErr !== 'No error' ? ' (' . $jsonErr . ')' : '')
            );
        }

        $layoutService = new EnvelopeLayoutService();
        $normalized    = $layoutService->normalizeLayout($decoded);

        $uploads = $this->request->getFiles();
        if (isset($uploads['item_images']) && is_array($uploads['item_images'])) {
            foreach ($uploads['item_images'] as $uid => $file) {
                if (! $file || ! $file->isValid() || $file->hasMoved()) {
                    continue;
                }
                $rel = $this->storeItemImage($id, $file);
                if ($rel === null) {
                    continue;
                }
                foreach ($normalized['items'] as &$item) {
                    if ((string) ($item['uid'] ?? '') === (string) $uid && ($item['element_type'] ?? '') === 'custom_image') {
                        $item['image_file'] = $rel;
                    }
                }
                unset($item);
            }
        }

        $jsonOut = json_encode($normalized, JSON_UNESCAPED_UNICODE);
        $model->update($id, [
            'name'        => $name,
            'layout_json' => $jsonOut,
        ]);

        if ($this->request->getPost('set_as_print_template') === '1') {
            model(AppConfigModel::class)->saveValue(
                EnvelopeRenderService::CONFIG_PRINT_TEMPLATE_ID,
                (string) $id
            );
        }

        (new ConfigService())->invalidateCache();

        $printId = (new EnvelopeRenderService())->resolvePrintTemplateId();
        $msg     = 'Plantilla de sobre guardada.';
        if ($printId === $id) {
            $msg .= ' Esta plantilla se usará al imprimir sobres desde los reportes.';
        }

        return redirect()->to('config/sobres/edit/' . $id)->with('success', $msg);
    }

    public function create(): ResponseInterface
    {
        $name = trim((string) $this->request->getPost('name'));
        if ($name === '') {
            return redirect()->to('config?tab=sobres')->with('error', 'Indique un nombre para la plantilla de sobre.');
        }

        $layoutService = new EnvelopeLayoutService();
        $json          = json_encode($layoutService->getDefaultLayout(), JSON_UNESCAPED_UNICODE);

        $model = model(EnvelopeTemplateModel::class);
        $model->insert([
            'name'        => $name,
            'layout_json' => $json,
        ]);
        $newId = (int) $model->getInsertID();
        (new ConfigService())->invalidateCache();

        if ($newId < 1) {
            return redirect()->to('config?tab=sobres')->with('error', 'No se pudo crear la plantilla.');
        }

        return redirect()->to('config/sobres/edit/' . $newId)->with('success', 'Plantilla creada. Configure el tamaño y la matriz.');
    }

    public function delete(int $id = 0): ResponseInterface
    {
        $id    = max(0, $id);
        $model = model(EnvelopeTemplateModel::class);
        $all   = $model->orderBy('id', 'ASC')->findAll();
        if (count($all) <= 1) {
            return redirect()->to('config?tab=sobres')->with('error', 'Debe existir al menos una plantilla de sobre.');
        }

        $exists = false;
        foreach ($all as $t) {
            if ((int) $t->id === $id) {
                $exists = true;
                break;
            }
        }
        if (! $exists) {
            return redirect()->to('config?tab=sobres')->with('error', 'Plantilla no encontrada.');
        }

        $configModel = model(AppConfigModel::class);
        $activeId    = (int) $configModel->getValue('active_envelope_template_id');
        if ($activeId === $id) {
            foreach ($all as $t) {
                if ((int) $t->id !== $id) {
                    $configModel->saveValue('active_envelope_template_id', (string) $t->id);
                    break;
                }
            }
        }
        $printId = (int) $configModel->getValue('print_envelope_template_id');
        if ($printId === $id) {
            foreach ($all as $t) {
                if ((int) $t->id !== $id) {
                    $configModel->saveValue('print_envelope_template_id', (string) $t->id);
                    break;
                }
            }
        }

        $this->deleteTemplateUploadDir($id);
        $model->delete($id);
        (new ConfigService())->invalidateCache();

        return redirect()->to('config?tab=sobres')->with('success', 'Plantilla de sobre eliminada.');
    }

    public function setActive(int $id = 0): ResponseInterface
    {
        $id = max(0, $id);
        $model = model(EnvelopeTemplateModel::class);
        if (! $model->find($id)) {
            return redirect()->to('config?tab=sobres')->with('error', 'Plantilla no encontrada.');
        }
        model(AppConfigModel::class)->saveValue('active_envelope_template_id', (string) $id);
        (new ConfigService())->invalidateCache();

        return redirect()->to('config?tab=sobres')->with('success', 'Plantilla activa actualizada.');
    }

    public function setPrintTemplate(): ResponseInterface
    {
        $id = (int) $this->request->getPost('print_envelope_template_id');
        if ($id < 1) {
            return redirect()->to('config?tab=sobres')->with('error', 'Seleccione una plantilla de sobre para impresión.');
        }

        $model = model(EnvelopeTemplateModel::class);
        if (! $model->find($id)) {
            return redirect()->to('config?tab=sobres')->with('error', 'Plantilla no encontrada.');
        }

        model(AppConfigModel::class)->saveValue(EnvelopeRenderService::CONFIG_PRINT_TEMPLATE_ID, (string) $id);
        (new ConfigService())->invalidateCache();

        return redirect()->to('config?tab=sobres')->with('success', 'Plantilla de impresión de sobres actualizada.');
    }

    /**
     * Sirve imágenes de sobres desde writable (solo rutas validadas).
     */
    public function media(int $templateId = 0, string $filename = ''): ResponseInterface
    {
        $templateId = max(0, $templateId);
        $filename   = basename($filename);
        if ($templateId < 1 || $filename === '' || preg_match('/[^a-zA-Z0-9_.-]/', $filename)) {
            return $this->response->setStatusCode(404);
        }

        $rel  = 'uploads/envelope_templates/' . $templateId . '/' . $filename;
        $safe = EnvelopeLayoutService::sanitizeImageRelativePath($rel);
        if ($safe === null) {
            return $this->response->setStatusCode(404);
        }

        $full = WRITEPATH . str_replace('/', DIRECTORY_SEPARATOR, $safe);
        if (! is_file($full)) {
            return $this->response->setStatusCode(404);
        }

        $mime = mime_content_type($full) ?: 'application/octet-stream';

        return $this->response
            ->setHeader('Content-Type', $mime)
            ->setBody(file_get_contents($full) ?: '');
    }

    /**
     * @param \CodeIgniter\HTTP\Files\UploadedFile $file
     */
    private function storeItemImage(int $templateId, $file): ?string
    {
        $ext     = strtolower((string) $file->getClientExtension());
        $allowed = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
        if (! in_array($ext, $allowed, true)) {
            return null;
        }
        if ($file->getSize() > 2097152) {
            return null;
        }

        $dir = WRITEPATH . 'uploads' . DIRECTORY_SEPARATOR . 'envelope_templates' . DIRECTORY_SEPARATOR . $templateId;
        if (! is_dir($dir)) {
            mkdir($dir, 0755, true);
        }
        $newName = 'img_' . bin2hex(random_bytes(8)) . '.' . $ext;
        $file->move($dir, $newName);
        if (! is_file($dir . DIRECTORY_SEPARATOR . $newName)) {
            return null;
        }

        return 'uploads/envelope_templates/' . $templateId . '/' . $newName;
    }

    private function deleteTemplateUploadDir(int $templateId): void
    {
        $dir = WRITEPATH . 'uploads' . DIRECTORY_SEPARATOR . 'envelope_templates' . DIRECTORY_SEPARATOR . $templateId;
        if (! is_dir($dir)) {
            return;
        }
        foreach (glob($dir . DIRECTORY_SEPARATOR . '*') ?: [] as $f) {
            if (is_file($f)) {
                @unlink($f);
            }
        }
        @rmdir($dir);
    }
}
