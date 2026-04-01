<?php

namespace App\Controllers;

use App\Libraries\PdfService;
use App\Models\AppConfigModel;
use App\Models\ToquoteModel;
use CodeIgniter\HTTP\ResponseInterface;

class Toquotes extends SecureArea
{
    protected ?string $moduleId = 'toquotes';

    protected ToquoteModel $toquoteModel;

    public function __construct()
    {
        parent::__construct();
        $this->toquoteModel = model(ToquoteModel::class);
    }

    public function index()
    {
        return view('toquotes/manage', [
            'allowed_modules' => $this->allowed_modules,
            'user_info'       => $this->user_info,
            'current_module'  => 'toquotes',
        ]);
    }

    /**
     * Búsqueda de análisis para cotizar (JSON)
     */
    public function search(): ResponseInterface
    {
        $q = trim((string) ($this->request->getGet('q') ?? $this->request->getPost('q') ?? ''));
        if ($q === '') {
            return $this->response->setJSON(['items' => []]);
        }
        $items = $this->toquoteModel->searchForQuote($q);
        return $this->response->setJSON(['items' => $items]);
    }

    /**
     * Guarda la cotización
     */
    public function savetoquotelog(): ResponseInterface
    {
        $cotizo    = $this->request->getPost('cotizo') ?? '';
        $costo     = (int) ($this->request->getPost('costo') ?? 0);
        $refe      = (int) ($this->request->getPost('refe') ?? 0);
        $itemsJson = $this->request->getPost('items_json');

        if ($this->toquoteModel->saveLog($cotizo, $costo, $refe, $itemsJson)) {
            return $this->response->setJSON(['success' => true, 'message' => lang('Toquotes.toquotes_saved')]);
        }
        return $this->response->setJSON(['success' => false, 'message' => lang('Toquotes.toquotes_error')]);
    }

    /**
     * Exporta la cotización como PDF
     */
    public function exportPdf(): ResponseInterface
    {
        $itemsJson = $this->request->getPost('items_json') ?? $this->request->getGet('items_json');
        $items     = $itemsJson ? json_decode($itemsJson, true) : null;
        if (!is_array($items) || empty($items)) {
            if ($this->request->isAJAX()) {
                return $this->response->setStatusCode(400)->setJSON(['error' => lang('Toquotes.toquotes_select_items')]);
            }
            return redirect()->to('toquotes')->with('error', lang('Toquotes.toquotes_select_items'));
        }
        $pdfTipo   = (string) ($this->request->getPost('pdf_tipo') ?? $this->request->getGet('pdf_tipo') ?? 'ambos');
        $precioTipo = $this->normalizePdfPrecioTipo($pdfTipo);

        return $this->generatePdfResponse($items, $precioTipo);
    }

    /**
     * Re-exporta el PDF de una cotización guardada por ID
     * @param int $id ID de la cotización
     * @param string|null $tipo Opcional: 'refe' solo referencia, 'total' solo total, null ambos
     */
    public function exportPdfById(int $id, ?string $tipo = null): ResponseInterface
    {
        $cotiz = $this->toquoteModel->getById($id);
        if (!$cotiz) {
            return redirect()->back()->with('error', 'Cotización no encontrada');
        }
        $itemsJson = $cotiz['items_json'] ?? null;
        $items     = $itemsJson ? json_decode($itemsJson, true) : null;
        if (!is_array($items) || empty($items)) {
            return redirect()->back()->with('error', 'Esta cotización no tiene detalle para generar PDF');
        }
        $tipo = in_array($tipo, ['refe', 'total'], true) ? $tipo : null;
        return $this->generatePdfResponse($items, $tipo);
    }

    /**
     * @param string $pdfTipo ambos|costo|refe (también total|ref por compatibilidad con URLs guardadas)
     */
    private function normalizePdfPrecioTipo(string $pdfTipo): ?string
    {
        $pdfTipo = strtolower(trim($pdfTipo));
        if ($pdfTipo === 'refe' || $pdfTipo === 'ref') {
            return 'refe';
        }
        if ($pdfTipo === 'total' || $pdfTipo === 'costo') {
            return 'total';
        }

        return null;
    }

    private function pdfDownloadSlug(?string $precioTipo): string
    {
        if ($precioTipo === 'refe') {
            return 'ref';
        }
        if ($precioTipo === 'total') {
            return 'costo';
        }

        return 'ambos';
    }

    /**
     * Genera y retorna la respuesta PDF desde un array de items
     * @param array $items
     * @param string|null $precioTipo 'refe' solo ref, 'total' solo total, null ambos
     */
    private function generatePdfResponse(array $items, ?string $precioTipo = null): ResponseInterface
    {
        try {
            $labConfig = (model(AppConfigModel::class))->getMultiple([
                'company', 'address', 'phone', 'email', 'website',
            ]);
            $totalCost = 0;
            $totalRefe = 0;
            foreach ($items as $it) {
                $totalCost += (int) ($it['cost'] ?? 0);
                $totalRefe += (int) ($it['refe'] ?? 0);
            }

            $html = view('toquotes/quote_pdf', [
                'items'       => $items,
                'totalCost'   => $totalCost,
                'totalRefe'   => $totalRefe,
                'lab_config'  => $labConfig,
                'fecha'       => date('d/m/Y H:i'),
                'precioTipo'  => in_array($precioTipo, ['refe', 'total'], true) ? $precioTipo : null,
            ]);

            $pdfService = new PdfService();
            $pdfContent = $pdfService->generate($html, 'cotizacion.pdf');
            $slug         = $this->pdfDownloadSlug($precioTipo);

            return $this->response
                ->setHeader('Content-Type', 'application/pdf')
                ->setHeader('Content-Disposition', 'attachment; filename="cotizacion_' . $slug . '_' . date('Y-m-d_His') . '.pdf"')
                ->setBody($pdfContent);
        } catch (\Throwable $e) {
            log_message('error', 'Toquotes::generatePdfResponse: ' . $e->getMessage());
            return redirect()->back()->with('error', 'Error al generar el PDF');
        }
    }
}
