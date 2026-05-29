<?php

namespace App\Services;

use App\Models\AppConfigModel;
use App\Models\EnvelopeTemplateModel;

/**
 * Renderiza sobres con plantilla activa y datos de un registro/reporte.
 */
class EnvelopeRenderService
{
    public const CONFIG_PRINT_TEMPLATE_ID  = 'print_envelope_template_id';
    public const CONFIG_ACTIVE_TEMPLATE_ID = 'active_envelope_template_id';

    /**
     * ID de plantilla usada al imprimir sobres desde registros.
     */
    public function resolvePrintTemplateId(): int
    {
        try {
            $configModel = model(AppConfigModel::class);
            $model       = model(EnvelopeTemplateModel::class);
            $printId     = (int) $configModel->getValue(self::CONFIG_PRINT_TEMPLATE_ID);
            if ($printId > 0 && $model->find($printId)) {
                return $printId;
            }
            $activeId = (int) $configModel->getValue(self::CONFIG_ACTIVE_TEMPLATE_ID);
            if ($activeId > 0 && $model->find($activeId)) {
                return $activeId;
            }
            $first = $model->orderBy('id', 'ASC')->first();

            return $first ? (int) ($first->id ?? 0) : 0;
        } catch (\Throwable $e) {
            return 0;
        }
    }

    /**
     * @return object|null
     */
    public function getPrintTemplate()
    {
        $id = $this->resolvePrintTemplateId();
        if ($id < 1) {
            return null;
        }

        try {
            return model(EnvelopeTemplateModel::class)->find($id) ?: null;
        } catch (\Throwable $e) {
            return null;
        }
    }

    /**
     * Plantilla marcada como activa en el editor (referencia en listado).
     *
     * @return object|null
     */
    public function getActiveTemplate()
    {
        try {
            $id = (int) model(AppConfigModel::class)->getValue(self::CONFIG_ACTIVE_TEMPLATE_ID);
            if ($id < 1) {
                return null;
            }

            return model(EnvelopeTemplateModel::class)->find($id) ?: null;
        } catch (\Throwable $e) {
            return null;
        }
    }

    /**
     * @param array<string, mixed> $item
     */
    public static function isItemEnabled(array $item): bool
    {
        return ! isset($item['enabled']) || (bool) $item['enabled'];
    }

    /**
     * @param array<string, mixed> $reportData Salida de RegisterService::prepareReportData()
     *
     * @return array<string, mixed>
     */
    public function buildContext(array $reportData, int $registroId, string $reportUrl, string $qrDataUri, ?string $emitidoEn = null): array
    {
        helper('registro');

        return [
            'registro_id'            => $registroId,
            'register_info'          => $reportData['register_info'] ?? null,
            'paciente'               => $reportData['paciente'] ?? null,
            'doctor'                 => $reportData['doctor'] ?? null,
            'lab_config'             => is_array($reportData['lab_config'] ?? null) ? $reportData['lab_config'] : [],
            'report_url'             => $reportUrl,
            'qr_data_uri'            => $qrDataUri,
            'report_emitido_en'      => $emitidoEn ?? \App\Services\RegisterService::formatNowForReport(),
            'barcode_size_percent'   => $this->resolveBarcodeSizePercent(),
        ];
    }

    public function resolveBarcodeSizePercent(): int
    {
        try {
            $p = (int) model(AppConfigModel::class)->getValue('order_barcode_print_size_percent');
            if ($p < 30 || $p > 250) {
                return 100;
            }

            return $p;
        } catch (\Throwable $e) {
            return 100;
        }
    }

    /**
     * Nombre del paciente como en la impresión de código de barras de la orden.
     *
     * @param object|null $reg
     * @param object|null $paciente
     */
    public function resolvePacienteNombreOrden($reg, $paciente): string
    {
        if ($reg !== null) {
            $nom = trim(implode(' ', array_filter([
                (string) ($reg->last_name_fa ?? ''),
                (string) ($reg->last_name_mom ?? ''),
                (string) ($reg->first_name ?? ''),
            ])));
            if ($nom !== '') {
                return $nom;
            }
        }
        if ($paciente !== null) {
            $nom = trim(($paciente->first_name ?? '') . ' ' . ($paciente->last_name_fa ?? '') . ' ' . ($paciente->last_name_mom ?? ''));

            return $nom !== '' ? $nom : '';
        }

        return '';
    }

    /**
     * @param array<string, mixed> $reportData
     */
    public function renderForRegistro(int $registroId, array $reportData, string $reportUrl, string $qrDataUri, ?string $emitidoEn = null): string
    {
        $template = $this->getPrintTemplate();
        if ($template === null) {
            throw new \RuntimeException('No hay plantilla de sobre para impresión. Elija una en Configuración → Sobres.');
        }

        $layoutService = new EnvelopeLayoutService();
        $layout        = $layoutService->layoutJsonForEditor($template);
        $ctx           = $this->buildContext($reportData, $registroId, $reportUrl, $qrDataUri, $emitidoEn);
        $templateId    = (int) ($template->id ?? 0);

        return view('registers/envelope_print', [
            'layout'         => $layout,
            'template_id'    => $templateId,
            'template_name'  => (string) ($template->name ?? 'Sobre'),
            'ctx'            => $ctx,
            'registro_id'    => $registroId,
            'render_cells'   => $this->buildCellsForRender($layout, $templateId, $ctx),
            'envelopeRender' => $this,
        ], ['saveData' => false]);
    }

    /**
     * @param array<string, mixed> $layout
     * @param array<string, mixed> $ctx
     *
     * @return list<array<string, mixed>>
     */
    public function buildCellsForRender(array $layout, int $templateId, array $ctx): array
    {
        $items  = is_array($layout['items'] ?? null) ? $layout['items'] : [];
        $merges = is_array($layout['merges'] ?? null) ? $layout['merges'] : [];
        $cols   = max(1, (int) ($layout['columns'] ?? 4));
        $rows   = max(1, (int) ($layout['rows'] ?? 3));

        $groups = [];
        foreach ($items as $it) {
            if (! is_array($it) || ! self::isItemEnabled($it)) {
                continue;
            }
            $r = (int) ($it['row'] ?? 0);
            $c = (int) ($it['col'] ?? 0);
            $key = $r . ',' . $c;
            if (! isset($groups[$key])) {
                $groups[$key] = [];
            }
            $groups[$key][] = $it;
        }

        $occupied = [];
        foreach ($merges as $m) {
            if (! is_array($m)) {
                continue;
            }
            $this->markOccupiedExceptAnchor(
                $occupied,
                (int) $m['row'],
                (int) $m['col'],
                (int) ($m['col_span'] ?? 1),
                (int) ($m['row_span'] ?? 1)
            );
        }

        $out = [];
        foreach ($groups as $anchor => $cellItems) {
            if ($cellItems === []) {
                continue;
            }
            usort($cellItems, static function (array $a, array $b): int {
                return ((int) ($a['stack_order'] ?? 0)) <=> ((int) ($b['stack_order'] ?? 0));
            });

            $it0 = $cellItems[0];
            if (! empty($occupied[$anchor])) {
                continue;
            }
            $row = (int) ($it0['row'] ?? 0);
            $col = (int) ($it0['col'] ?? 0);
            $this->markOccupiedExceptAnchor(
                $occupied,
                $row,
                $col,
                (int) ($it0['col_span'] ?? 1),
                (int) ($it0['row_span'] ?? 1)
            );

            $stackDir = $this->getCellStackDirection($cellItems);
            $ref      = $this->getCellAlignmentRef($cellItems);

            $renderItems = [];
            foreach ($cellItems as $it) {
                $renderItems[] = [
                    'item'    => $it,
                    'html'    => $this->renderItemInnerHtml($it, $templateId, $ctx),
                    'classes' => $this->itemCssClasses($it),
                    'style'   => $this->itemInlineStyle($it, $stackDir),
                ];
            }

            $out[] = [
                'row'          => $row,
                'col'          => $col,
                'col_span'     => (int) ($it0['col_span'] ?? 1),
                'row_span'     => (int) ($it0['row_span'] ?? 1),
                'stack_dir'    => $stackDir,
                'text_align'   => (string) ($ref['text_align'] ?? 'left'),
                'vertical_align' => (string) ($ref['vertical_align'] ?? 'top'),
                'items'        => $renderItems,
            ];
        }

        usort($out, static function ($a, $b) {
            if ($a['row'] !== $b['row']) {
                return $a['row'] <=> $b['row'];
            }

            return $a['col'] <=> $b['col'];
        });

        return $out;
    }

    /**
     * @param array<string, mixed> $layout
     * @param array<string, mixed> $ctx
     *
     * @return list<float>
     */
    public function computeRowWeights(array $layout, array $ctx): array
    {
        $rows = max(1, (int) ($layout['rows'] ?? 3));

        return array_fill(0, $rows, 1.0);
    }

    /**
     * @param array<string, mixed> $cell
     */
    public function buildCellContainerClass(array $cell): string
    {
        $itemCount = 0;
        foreach ($cell['items'] ?? [] as $ri) {
            if (is_array($ri) && (string) ($ri['html'] ?? '') !== '') {
                $itemCount++;
            }
        }

        return 'envelope-preview-cell' . ($itemCount > 1 ? ' envelope-preview-cell-multi' : '');
    }

    /**
     * @param array<string, mixed> $cell
     */
    public function buildCellContainerStyle(array $cell): string
    {
        $itemCount = 0;
        foreach ($cell['items'] ?? [] as $ri) {
            if (is_array($ri) && (string) ($ri['html'] ?? '') !== '') {
                $itemCount++;
            }
        }
        $multi = $itemCount > 1;
        $style = 'grid-column:' . ((int) $cell['col'] + 1) . ' / span ' . max(1, (int) $cell['col_span'])
            . ';grid-row:' . ((int) $cell['row'] + 1) . ' / span ' . max(1, (int) $cell['row_span'])
            . ';box-sizing:border-box;width:100%;height:100%;overflow:hidden;padding:0;';

        if ($multi) {
            return $style . 'display:flex;flex-direction:column;justify-content:flex-start;align-items:stretch;';
        }

        $refItem = $cell['items'][0]['item'] ?? [];
        $isVert  = in_array((string) ($refItem['text_flow'] ?? ''), ['vertical_down', 'vertical_up'], true);
        $ta      = (string) ($cell['text_align'] ?? 'left');
        $va      = (string) ($cell['vertical_align'] ?? 'top');
        $style  .= 'display:flex;flex-direction:' . ($isVert ? 'row' : 'column') . ';';
        if ($isVert) {
            return $style . 'justify-content:' . $this->mapTextAlignToFlex($ta)
                . ';align-items:' . $this->mapVerticalAlignToFlex($va) . ';';
        }

        return $style . 'justify-content:' . $this->mapVerticalAlignToFlex($va)
            . ';align-items:' . $this->mapTextAlignToFlex($ta) . ';';
    }

    /**
     * @param array<string, mixed> $cell
     */
    public function buildStackClass(array $cell): string
    {
        $dir = (string) ($cell['stack_dir'] ?? 'column');

        return 'envelope-preview-stack envelope-preview-stack-' . ($dir === 'row' ? 'row' : 'column');
    }

    /**
     * @param array<string, mixed> $cell
     */
    public function buildStackStyle(array $cell): string
    {
        $dir = (string) ($cell['stack_dir'] ?? 'column');
        $ta  = (string) ($cell['text_align'] ?? 'left');
        $va  = (string) ($cell['vertical_align'] ?? 'top');

        if ($dir === 'row') {
            return 'justify-content:' . $this->mapTextAlignToFlex($ta)
                . ';align-items:' . $this->mapVerticalAlignToFlex($va) . ';';
        }

        $alignItems = match ($ta) {
            'center' => 'center',
            'right'  => 'flex-end',
            default  => 'stretch',
        };

        return 'justify-content:flex-start;align-items:' . $alignItems . ';';
    }

    /**
     * HTML de una celda de la matriz (misma estructura que la vista previa del editor).
     *
     * @param array<string, mixed> $cell
     */
    public function renderCellMarkup(array $cell): string
    {
        $renderItems = [];
        foreach ($cell['items'] ?? [] as $ri) {
            if (! is_array($ri) || (string) ($ri['html'] ?? '') === '') {
                continue;
            }
            $renderItems[] = $ri;
        }
        if ($renderItems === []) {
            return '';
        }

        $multi = count($renderItems) > 1;
        $html  = '<div class="' . esc($this->buildCellContainerClass($cell), 'attr') . '" style="'
            . esc($this->buildCellContainerStyle($cell), 'attr') . '">';

        if ($multi) {
            $stackStyle = $this->buildStackStyle($cell);
            $html      .= '<div class="' . esc($this->buildStackClass($cell), 'attr') . '"';
            if ($stackStyle !== '') {
                $html .= ' style="' . esc($stackStyle, 'attr') . '"';
            }
            $html .= '>';
            foreach ($renderItems as $ri) {
                $html .= $this->renderStackItemMarkup($ri);
            }
            $html .= '</div>';
        } else {
            $html .= $this->renderStackItemMarkup($renderItems[0]);
        }

        return $html . '</div>';
    }

    /**
     * @param array<string, mixed> $ri
     */
    public function renderStackItemMarkup(array $ri): string
    {
        $classes = implode(' ', $ri['classes'] ?? []);
        $style   = (string) ($ri['style'] ?? '');
        $inner   = (string) ($ri['html'] ?? '');

        return '<div class="' . esc($classes, 'attr') . '" style="' . esc($style, 'attr') . '">' . $inner . '</div>';
    }

    private function mapTextAlignToFlex(string $align): string
    {
        return match ($align) {
            'center' => 'center',
            'right'  => 'flex-end',
            default  => 'flex-start',
        };
    }

    private function mapVerticalAlignToFlex(string $valign): string
    {
        return match ($valign) {
            'middle' => 'center',
            'bottom' => 'flex-end',
            default  => 'flex-start',
        };
    }

    /**
     * @param array<string, mixed> $item
     * @param array<string, mixed> $ctx
     */
    public function resolveFieldValue(array $item, array $ctx): string
    {
        helper('registro');

        $type = (string) ($item['element_type'] ?? '');
        $paciente = $ctx['paciente'] ?? null;
        $doctor   = $ctx['doctor'] ?? null;
        $reg      = $ctx['register_info'] ?? null;
        $lab      = is_array($ctx['lab_config'] ?? null) ? $ctx['lab_config'] : [];
        $emitido  = (string) ($ctx['report_emitido_en'] ?? '');

        switch ($type) {
            case 'custom_text':
                return trim((string) ($item['custom_value'] ?? ''));
            case 'paciente_nombre':
                $nom = trim(($paciente->first_name ?? '') . ' ' . ($paciente->last_name_fa ?? '') . ' ' . ($paciente->last_name_mom ?? ''));

                return $nom !== '' ? $nom : '—';
            case 'paciente_genero':
                return paciente_genero_texto($paciente);
            case 'paciente_edad':
                return (string) ($paciente->edad ?? '-');
            case 'paciente_telefono':
                return (string) ($paciente->phone_number ?? '-');
            case 'paciente_institucion':
                $inst = trim((string) ($paciente->paciente_institucion ?? ''));

                return $inst !== '' ? $inst : '—';
            case 'diagnostico_presuntivo':
                return trim((string) ($reg->diagnostico_presuntivo ?? ''));
            case 'medico':
                $nombreMed = trim((string) ($doctor->name ?? ''));
                if (! empty($doctor->report_sin_prefijo_medico ?? false)) {
                    return $nombreMed !== '' ? $nombreMed : '—';
                }
                $tit = ((int) ($doctor->gender ?? 0) === 1) ? 'Dr.' : 'Dra.';

                return $tit . ' ' . ($nombreMed !== '' ? $nombreMed : '-');
            case 'fecha_recepcion':
                $rec = (string) ($reg->recepcion_fecha_hora ?? '');

                return $rec !== '' ? $rec : '—';
            case 'fecha_reporte':
                return $emitido !== '' ? $emitido : '—';
            case 'numero_orden':
                return registro_orden_display($reg);
            case 'lab_company':
                return (string) ($lab['company'] ?? 'Laboratorio');
            case 'lab_address':
                return (string) ($lab['address'] ?? '');
            case 'lab_phone':
                return (string) ($lab['phone'] ?? '');
            case 'lab_email':
                return (string) ($lab['email'] ?? '');
            case 'lab_website':
                return (string) ($lab['website'] ?? '');
            default:
                return '';
        }
    }

    /**
     * @param array<string, mixed> $item
     * @param array<string, mixed> $ctx
     */
    public function renderItemInnerHtml(array $item, int $templateId, array $ctx): string
    {
        $type = (string) ($item['element_type'] ?? '');
        if ($type === 'custom_image') {
            $rel = EnvelopeLayoutService::sanitizeImageRelativePath((string) ($item['image_file'] ?? ''));
            if ($rel === null) {
                return '';
            }
            $url = EnvelopeLayoutService::imagePublicUrl($templateId, $rel);
            $w   = max(5, min(100, (int) ($item['width_percent'] ?? 80)));
            $h   = max(5, min(100, (int) ($item['height_percent'] ?? 60)));

            return '<img class="envelope-preview-img" src="' . esc($url, 'attr') . '" alt="" style="width:' . $w . '%;height:' . $h . '%;max-width:100%;object-fit:contain;">';
        }
        if ($type === 'qr') {
            $uri = (string) ($ctx['qr_data_uri'] ?? '');
            if ($uri === '') {
                return '';
            }
            $mm = max(15, min(80, (int) ($item['qr_size_mm'] ?? 35)));

            return '<img class="envelope-preview-qr" src="' . esc($uri, 'attr') . '" alt="QR" style="width:' . $mm . 'mm;height:' . $mm . 'mm;max-width:100%;">';
        }
        if ($type === 'codigo_barras') {
            helper('registro');

            $reg   = $ctx['register_info'] ?? null;
            $orden = registro_orden_display($reg);
            if ($orden === '' || $orden === '0') {
                $orden = (string) ($ctx['registro_id'] ?? '');
            }
            if ($orden === '' || $orden === '0') {
                return '';
            }
            $paciente = $ctx['paciente'] ?? null;
            $nombre   = $this->resolvePacienteNombreOrden($reg, $paciente);
            $showName = ! isset($item['show_label']) || (bool) $item['show_label'];
            $barH     = max(8, min(40, (int) ($item['barcode_height_mm'] ?? 14)));
            $barW     = max(25, min(120, (int) ($item['barcode_width_mm'] ?? 60)));
            $sizePct  = max(30, min(250, (int) ($ctx['barcode_size_percent'] ?? 100)));

            $html = '<div class="envelope-barcode-block text-center">';
            if ($showName && $nombre !== '') {
                $html .= '<div class="orden-barcode-patient-name">' . esc($nombre) . '</div>';
            }
            $barcodeHtml = (new EnvelopeBarcodeImageService())->renderPrintHtml($orden, $barW, $barH, $sizePct);
            $html .= '<div class="orden-barcode-box envelope-barcode-box" style="width:' . $barW . 'mm;max-width:100%;margin:0 auto;">';
            $html .= $barcodeHtml !== '' ? $barcodeHtml : '<div class="envelope-barcode-fallback" style="font-family:Arial,Helvetica,sans-serif;font-size:11pt;font-weight:600;letter-spacing:0.05em;padding:2mm 0;text-align:center;">'
                . esc($orden) . '</div>';
            $html .= '</div></div>';

            return $html;
        }
        if ($type === 'logo') {
            $lab     = is_array($ctx['lab_config'] ?? null) ? $ctx['lab_config'] : [];
            $logoRel = (string) ($lab['logo'] ?? 'images/logo-john.png');
            $logoPath = FCPATH . str_replace('/', DIRECTORY_SEPARATOR, $logoRel);
            if (! is_file($logoPath)) {
                return '<span class="envelope-preview-logo-fallback" style="font-weight:700;">' . esc((string) ($lab['company'] ?? 'Laboratorio')) . '</span>';
            }
            $data = base64_encode((string) file_get_contents($logoPath));
            $finfo = finfo_open(FILEINFO_MIME_TYPE);
            $mime  = $finfo ? finfo_file($finfo, $logoPath) : false;
            if ($finfo) {
                finfo_close($finfo);
            }
            $uri = 'data:' . ($mime ?: 'image/png') . ';base64,' . $data;

            return '<img class="envelope-preview-img envelope-preview-logo" src="' . esc($uri, 'attr') . '" alt="Logo" style="max-width:100%;max-height:18mm;object-fit:contain;">';
        }

        $labels = EnvelopeLayoutService::elementTypeLabels();
        $val    = $this->resolveFieldValue($item, $ctx);
        $showLabel = ! isset($item['show_label']) || (bool) $item['show_label'];
        $lbl = trim((string) ($item['custom_label'] ?? ''));
        if ($lbl === '') {
            $lbl = (string) ($labels[$type] ?? $type);
        }
        $inner = '';
        if ($showLabel && $lbl !== '') {
            $inner = '<span class="envelope-preview-lbl">' . esc($lbl) . ': </span><span>' . esc($val) . '</span>';
        } else {
            $inner = '<span>' . esc($val) . '</span>';
        }

        return $this->wrapPreviewContent($inner, $item);
    }

    /**
     * @param array<string, mixed> $item
     */
    private function wrapPreviewContent(string $inner, array $item): string
    {
        $flow = (string) ($item['text_flow'] ?? 'horizontal');
        $class = 'envelope-preview-content';
        if ($flow === 'vertical_down') {
            $class .= ' envelope-text-vertical-down';
        } elseif ($flow === 'vertical_up') {
            $class .= ' envelope-text-vertical-up';
        }
        $align = (string) ($item['text_align'] ?? 'left');
        if (! in_array($align, ['left', 'center', 'right'], true)) {
            $align = 'left';
        }
        $fontPt = max(6, min(24, (int) ($item['font_size_pt'] ?? 10)));
        $weight = ($item['font_weight'] ?? '') === 'bold' ? 'bold' : 'normal';

        return '<div class="' . esc($class, 'attr') . '" style="text-align:' . esc($align, 'attr')
            . ';font-size:' . $fontPt . 'pt;font-weight:' . esc($weight, 'attr') . ';">' . $inner . '</div>';
    }

    /**
     * @param array<string, mixed> $item
     *
     * @return list<string>
     */
    public function itemCssClasses(array $item): array
    {
        $classes = ['envelope-preview-stack-item'];
        if ($this->isVerticalTextFlow($item)) {
            $classes[] = 'envelope-preview-stack-item-vertical';
        }

        return $classes;
    }

    /**
     * @param array<string, mixed> $item
     */
    public function itemInlineStyle(array $item, string $stackDir): string
    {
        $m      = EnvelopeLayoutService::normalizeMarginMm($item['margin_mm'] ?? null);
        $align  = (string) ($item['text_align'] ?? 'left');
        $valign = (string) ($item['vertical_align'] ?? 'top');
        $parts  = [
            'box-sizing:border-box',
            'display:flex',
            'flex:0 0 auto',
            'margin:' . $m['top'] . 'mm ' . $m['right'] . 'mm ' . $m['bottom'] . 'mm ' . $m['left'] . 'mm',
            'padding:0',
            'overflow:visible',
        ];

        if ($this->isVerticalTextFlow($item)) {
            if ($stackDir === 'row') {
                $parts[] = 'width:auto';
                $parts[] = 'max-width:none';
                $parts[] = 'flex-direction:column';
                $parts[] = 'text-align:left';
                $parts[] = 'align-self:' . $this->mapVerticalAlignToCss($valign);
            } else {
                $parts[] = 'width:100%';
                $parts[] = 'flex-direction:row';
                $parts[] = 'justify-content:' . $this->mapTextAlignToFlex($align);
                $parts[] = 'align-self:stretch';
                $parts[] = 'text-align:left';
            }
        } else {
            $parts[] = 'width:100%';
            $parts[] = 'max-width:100%';
            $parts[] = 'flex-direction:column';
            $parts[] = 'justify-content:flex-start';
            $parts[] = 'align-items:' . $this->mapTextAlignToFlex($align);
            $parts[] = 'align-self:stretch';
            $parts[] = 'text-align:' . (in_array($align, ['left', 'center', 'right'], true) ? $align : 'left');
        }

        return implode(';', $parts);
    }

    /**
     * @param list<array<string, mixed>> $items
     */
    private function getCellStackDirection(array $items): string
    {
        $hasHorizontal = false;
        $hasVertical   = false;
        foreach ($items as $it) {
            $type = (string) ($it['element_type'] ?? '');
            if ($type === 'custom_image' || $type === 'qr' || $type === 'codigo_barras') {
                continue;
            }
            if ($this->isVerticalTextFlow($it)) {
                $hasVertical = true;
            } else {
                $hasHorizontal = true;
            }
        }

        return ($hasVertical && ! $hasHorizontal) ? 'row' : 'column';
    }

    /**
     * @param list<array<string, mixed>> $items
     *
     * @return array<string, mixed>
     */
    private function getCellAlignmentRef(array $items): array
    {
        foreach ($items as $it) {
            $type = (string) ($it['element_type'] ?? '');
            if ($type !== 'custom_image' && $type !== 'qr' && $type !== 'codigo_barras') {
                return $it;
            }
        }

        return $items[0];
    }

    /**
     * @param array<string, mixed> $item
     */
    private function isVerticalTextFlow(array $item): bool
    {
        $flow = (string) ($item['text_flow'] ?? 'horizontal');

        return in_array($flow, ['vertical_down', 'vertical_up'], true);
    }

    private function mapVerticalAlignToCss(string $valign): string
    {
        return match ($valign) {
            'middle' => 'center',
            'bottom' => 'flex-end',
            default  => 'flex-start',
        };
    }

    /**
     * Marca celdas cubiertas por un combine, sin bloquear la celda origen (ancla).
     *
     * @param array<string, bool> $occupied
     */
    private function markOccupiedExceptAnchor(array &$occupied, int $row, int $col, int $colSpan, int $rowSpan): void
    {
        for ($r = $row; $r < $row + $rowSpan; $r++) {
            for ($c = $col; $c < $col + $colSpan; $c++) {
                if ($r === $row && $c === $col) {
                    continue;
                }
                $occupied[$r . ',' . $c] = true;
            }
        }
    }

}
