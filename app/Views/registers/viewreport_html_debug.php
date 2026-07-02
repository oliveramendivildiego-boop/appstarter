<?php
/**
 * Inspector HTML/CSS del pipeline PDF (misma fuente que /registers/viewreport).
 *
 * @var int    $registro_id
 * @var string $html_raw
 * @var string $html_adapted
 * @var list<string> $styles_raw
 * @var list<string> $styles_adapted
 * @var string $cache_revision
 * @var string $html_fingerprint
 * @var bool   $purged
 * @var string $fragment_key
 * @var string $fragment_raw
 * @var string $fragment_adapted
 */
$rid = (int) ($registro_id ?? 0);
$previewUrl = site_url('registers/viewreport-html/' . $rid);
$inspectorBase = site_url('registers/viewreport-html/' . $rid . '?inspector=1');
$pdfUrl = site_url('registers/viewreport/' . $rid);
$fragmentKey = trim((string) ($fragment_key ?? ''));
$activeTab = trim((string) (service('request')->getGet('tab') ?? 'preview'));
if (! in_array($activeTab, ['preview', 'html', 'mpdf', 'css', 'fragment'], true)) {
    $activeTab = 'preview';
}
$styleBlocksRaw = is_array($styles_raw ?? null) ? $styles_raw : [];
$styleBlocksAdapted = is_array($styles_adapted ?? null) ? $styles_adapted : [];
$cssCombinedRaw = implode("\n\n/* --- bloque --- */\n\n", $styleBlocksRaw);
$cssCombinedAdapted = implode("\n\n/* --- bloque --- */\n\n", $styleBlocksAdapted);
?>
<?= $this->extend('layouts/main') ?>
<?= $this->section('title') ?>HTML reporte #<?= $rid ?><?= $this->endSection() ?>

<?= $this->section('head_extra') ?>
<style>
.report-html-debug-toolbar {
    background: #1e293b;
    color: #f8fafc;
    border-radius: 8px;
    padding: 12px 16px;
    margin-bottom: 16px;
}
.report-html-debug-toolbar a {
    color: #93c5fd;
}
.report-html-debug-toolbar .btn-light {
    color: #1e293b;
}
.report-html-debug-meta {
    font-family: ui-monospace, SFMono-Regular, Menlo, Consolas, monospace;
    font-size: 0.8rem;
    color: #cbd5e1;
}
.report-html-debug-preview-frame {
    width: 100%;
    min-height: 70vh;
    border: 1px solid #dee2e6;
    border-radius: 8px;
    background: #fff;
}
.report-html-debug-code {
    width: 100%;
    min-height: 65vh;
    font-family: ui-monospace, SFMono-Regular, Menlo, Consolas, monospace;
    font-size: 12px;
    line-height: 1.45;
    border-radius: 8px;
    border: 1px solid #ced4da;
    padding: 12px;
    resize: vertical;
    white-space: pre;
    overflow: auto;
}
.report-html-debug-nav .nav-link.active {
    font-weight: 600;
}
</style>
<?= $this->endSection() ?>

<?= $this->section('content') ?>
<div class="container-fluid py-3">
    <div class="report-html-debug-toolbar">
        <div class="d-flex flex-wrap align-items-center gap-2 mb-2">
            <strong class="me-2"><i class="fa-solid fa-code me-1"></i>Inspector HTML del reporte</strong>
            <span class="badge bg-warning text-dark">Solo reparación / depuración</span>
            <?php if (! empty($purged)): ?>
            <span class="badge bg-success">Caché limpiada en esta carga</span>
            <?php endif; ?>
        </div>
        <div class="d-flex flex-wrap gap-2 mb-2">
            <a href="<?= esc($previewUrl, 'attr') ?>" class="btn btn-sm btn-light" target="_blank" rel="noopener">Vista completa (como PDF)</a>
            <a href="<?= esc($pdfUrl, 'attr') ?>" class="btn btn-sm btn-outline-light">Ver PDF (viewreport)</a>
            <a href="<?= esc($inspectorBase . '&purge=1', 'attr') ?>" class="btn btn-sm btn-outline-light">Regenerar HTML</a>
            <a href="<?= esc($previewUrl . '?raw=1', 'attr') ?>" class="btn btn-sm btn-outline-light" target="_blank" rel="noopener">Abrir HTML crudo</a>
            <a href="<?= esc($previewUrl . '?raw=1&amp;adapted=1', 'attr') ?>" class="btn btn-sm btn-outline-light" target="_blank" rel="noopener">Abrir HTML mPDF</a>
        </div>
        <form class="row g-2 align-items-end" method="get" action="<?= esc($inspectorBase, 'attr') ?>">
            <div class="col-auto">
                <label class="form-label small mb-0 text-white-50" for="fragment">Fragmento (bloque cultivo)</label>
                <input type="text" class="form-control form-control-sm" id="fragment" name="fragment"
                    value="<?= esc($fragmentKey, 'attr') ?>" placeholder="cuerpo, cuerpo_2, cuerpo_3">
            </div>
            <div class="col-auto">
                <input type="hidden" name="tab" value="fragment">
                <button type="submit" class="btn btn-sm btn-primary">Extraer bloque</button>
            </div>
        </form>
        <div class="report-html-debug-meta mt-2">
            registro=<?= $rid ?>
            · bytes raw=<?= number_format(strlen((string) ($html_raw ?? ''))) ?>
            · bytes mPDF=<?= number_format(strlen((string) ($html_adapted ?? ''))) ?>
            · estilos raw=<?= count($styleBlocksRaw) ?>
            · CACHE_REVISION=<?= esc((string) ($cache_revision ?? ''), 'attr') ?>
            · fingerprint=<?= esc(substr((string) ($html_fingerprint ?? ''), 0, 16), 'attr') ?>…
        </div>
    </div>

    <ul class="nav nav-tabs report-html-debug-nav mb-3" role="tablist">
        <li class="nav-item" role="presentation">
            <a class="nav-link<?= $activeTab === 'preview' ? ' active' : '' ?>" href="<?= esc($inspectorBase . '&tab=preview', 'attr') ?>">Vista previa</a>
        </li>
        <li class="nav-item" role="presentation">
            <a class="nav-link<?= $activeTab === 'html' ? ' active' : '' ?>" href="<?= esc($inspectorBase . '&tab=html', 'attr') ?>">HTML generado</a>
        </li>
        <li class="nav-item" role="presentation">
            <a class="nav-link<?= $activeTab === 'mpdf' ? ' active' : '' ?>" href="<?= esc($inspectorBase . '&tab=mpdf', 'attr') ?>">HTML tras mPDF</a>
        </li>
        <li class="nav-item" role="presentation">
            <a class="nav-link<?= $activeTab === 'css' ? ' active' : '' ?>" href="<?= esc($inspectorBase . '&tab=css', 'attr') ?>">CSS</a>
        </li>
        <?php if ($fragmentKey !== ''): ?>
        <li class="nav-item" role="presentation">
            <a class="nav-link<?= $activeTab === 'fragment' ? ' active' : '' ?>" href="<?= esc($inspectorBase . '&fragment=' . rawurlencode($fragmentKey) . '&tab=fragment', 'attr') ?>">Bloque <?= esc($fragmentKey) ?></a>
        </li>
        <?php endif; ?>
    </ul>

    <?php if ($activeTab === 'preview'): ?>
    <p class="text-muted small">Mismo HTML adaptado por mPDF que usa el PDF. Abrí <a href="<?= esc($previewUrl, 'attr') ?>" target="_blank" rel="noopener">vista completa</a> para inspeccionar con F12 sin el layout de la app.</p>
    <iframe id="preview" class="report-html-debug-preview-frame" title="Vista previa HTML reporte"
        src="<?= esc($previewUrl . '?embed=1', 'attr') ?>"></iframe>
    <?php elseif ($activeTab === 'html'): ?>
    <p class="text-muted small">Código fuente completo antes de <code>HtmlMpdfAdapter::adapt()</code>.</p>
    <textarea class="report-html-debug-code" readonly spellcheck="false"><?= esc((string) ($html_raw ?? '')) ?></textarea>
    <?php elseif ($activeTab === 'mpdf'): ?>
    <p class="text-muted small">HTML después del adaptador mPDF (lo que recibe el motor al generar el PDF).</p>
    <textarea class="report-html-debug-code" readonly spellcheck="false"><?= esc((string) ($html_adapted ?? '')) ?></textarea>
    <?php elseif ($activeTab === 'fragment' && $fragmentKey !== ''): ?>
    <p class="text-muted small">Fragmento <code>report-cultivo-bloque-<?= esc($fragmentKey) ?></code> (raw y adaptado).</p>
    <div class="row g-3">
        <div class="col-lg-6">
            <h6>HTML raw del bloque</h6>
            <textarea class="report-html-debug-code" style="min-height:40vh" readonly spellcheck="false"><?= esc((string) ($fragment_raw ?? '')) ?></textarea>
        </div>
        <div class="col-lg-6">
            <h6>HTML del bloque tras mPDF</h6>
            <textarea class="report-html-debug-code" style="min-height:40vh" readonly spellcheck="false"><?= esc((string) ($fragment_adapted ?? '')) ?></textarea>
        </div>
    </div>
    <?php else: ?>
    <div class="row g-3">
        <div class="col-lg-6">
            <h6>CSS en HTML generado (<?= count($styleBlocksRaw) ?> bloques &lt;style&gt;)</h6>
            <textarea class="report-html-debug-code" readonly spellcheck="false"><?= esc($cssCombinedRaw) ?></textarea>
        </div>
        <div class="col-lg-6">
            <h6>CSS tras adaptador mPDF</h6>
            <textarea class="report-html-debug-code" readonly spellcheck="false"><?= esc($cssCombinedAdapted) ?></textarea>
        </div>
    </div>
    <?php endif; ?>
</div>
<?= $this->endSection() ?>
