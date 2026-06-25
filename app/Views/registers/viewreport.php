<?= $this->extend('layouts/main') ?>
<?= $this->section('title') ?>Reporte<?= $this->endSection() ?>

<?= $this->section('head_extra') ?>
<?php if (! empty($grupos ?? [])): ?>
<link rel="preload" href="<?= esc(site_url('registers/pdf/' . (int) ($labotests_namecate ?? 0) . '?inline=1'), 'attr') ?>" as="fetch" crossorigin="use-credentials">
<?php endif; ?>
<style>
.viewreport-actions-bar {
    background: #f8f9fa;
    border: 1px solid #dee2e6;
    border-radius: 8px;
    padding: 12px 16px;
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.06);
}
.viewreport-actions-bar--sticky {
    position: sticky;
    top: 0;
    z-index: 100;
    margin-bottom: 16px;
}
.viewreport-actions-bar--bottom {
    margin-top: 16px;
}
.report-pdfjs-viewer {
    border: 1px solid #dee2e6;
    border-radius: 8px;
    background: #525659;
    overflow: hidden;
    box-shadow: 0 2px 10px rgba(0, 0, 0, 0.14);
}
.report-pdfjs-toolbar {
    display: flex;
    flex-wrap: wrap;
    gap: 12px;
    align-items: center;
    justify-content: space-between;
    padding: 10px 14px;
    background: #f8f9fa;
    border-bottom: 1px solid #dee2e6;
}
.report-pdfjs-toolbar-group {
    display: flex;
    flex-wrap: wrap;
    gap: 8px;
    align-items: center;
}
.report-pdfjs-page-indicator,
.report-pdfjs-zoom-label {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    min-width: 72px;
    line-height: 1.2;
    white-space: nowrap;
}
.report-pdfjs-toolbar-note {
    flex: 1 1 220px;
    text-align: right;
}
.report-pdfjs-status {
    padding: 8px 14px;
    background: #f8f9fa;
    border-bottom: 1px solid #dee2e6;
}
.report-pdfjs-canvas-host {
    position: relative;
    max-height: calc(100vh - 220px);
    min-height: 480px;
    overflow: auto;
    padding: 16px;
    background: #525659;
}
.report-pdfjs-loading {
    position: absolute;
    inset: 0;
    z-index: 2;
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    gap: 12px;
    padding: 24px;
    text-align: center;
    background: rgba(45, 47, 49, 0.92);
    color: #f8f9fa;
}
.report-pdfjs-viewer.is-loading .report-pdfjs-loading {
    display: flex;
}
.report-pdfjs-viewer:not(.is-loading) .report-pdfjs-loading {
    display: none;
}
.report-pdfjs-loading-spinner {
    width: 42px;
    height: 42px;
    border: 3px solid rgba(255, 255, 255, 0.25);
    border-top-color: #fff;
    border-radius: 50%;
    animation: report-pdfjs-spin 0.85s linear infinite;
}
.report-pdfjs-loading-text {
    margin: 0;
    font-size: 1rem;
    font-weight: 600;
    color: #fff;
}
.report-pdfjs-loading-hint {
    margin: 0;
    font-size: 0.875rem;
    color: rgba(255, 255, 255, 0.72);
    max-width: 320px;
}
@keyframes report-pdfjs-spin {
    to { transform: rotate(360deg); }
}
.report-pdfjs-page {
    margin: 0 auto 16px;
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.35);
    background: #fff;
    width: fit-content;
}
.report-pdfjs-page:last-child {
    margin-bottom: 0;
}
.report-pdfjs-page-canvas {
    display: block;
    width: 100%;
    height: auto;
}
@media (max-width: 767.98px) {
    .report-pdfjs-toolbar-note {
        text-align: left;
        flex-basis: 100%;
    }
    .report-pdfjs-canvas-host {
        min-height: 360px;
    }
}
</style>
<?= $this->endSection() ?>

<?= $this->section('content') ?>
<?php if (session()->getFlashdata('success')): ?>
<div class="alert alert-success"><?= esc(session()->getFlashdata('success')) ?></div>
<?php endif; ?>
<?php if (session()->getFlashdata('error')): ?>
<div class="alert alert-danger"><?= esc(session()->getFlashdata('error')) ?></div>
<?php endif; ?>
<?= view('partial/breadcrumb_nav', ['items' => [
    ['label' => lang('Module.module_registers'), 'url' => site_url('registers')],
    ['label' => ($register_info->first_name ?? '') . ' ' . ($register_info->last_name_fa ?? ''), 'url' => site_url('registers/view/' . ($register_info->registro_id ?? ''))],
]]) ?>

<fieldset id="customer_basic_info">
<input type="hidden" name="registro_id" id="registro_id" value="<?= (int)($labotests_namecate ?? 0) ?>">

<?php
$grupos = $grupos ?? [];
$ridPdf = (int) ($labotests_namecate ?? 0);
$compOk = ! empty($comprobante_pdf_disponible ?? false);
$lblComp = ! empty($sin_billing_enabled ?? false) ? 'Factura' : 'Recibo (PDF)';
?>

<?= view('registers/partials/report_viewreport_actions_bar', [
    'ridPdf'                   => $ridPdf,
    'compOk'                   => $compOk,
    'lblComp'                  => $lblComp,
    'envelope_print_available' => $envelope_print_available ?? false,
    'delivery_show_notify_button'   => ! empty($delivery_show_notify_button ?? false),
    'delivery_pending_for_registro' => ! empty($delivery_pending_for_registro ?? false),
    'sticky'                   => true,
]) ?>

<?php if (! empty($delivery_pending_rows)): ?>
<?= view('registers/partials/report_delivery_pending_alert', ['delivery_pending_rows' => $delivery_pending_rows]) ?>
<?php endif; ?>

<?php if (empty($grupos)): ?>
<div class="alert alert-info mt-3">
    <i class="fa-solid fa-info-circle me-2"></i>No hay resultados cargados para esta orden. Complete los resultados en <a href="<?= site_url('registers/view/' . $ridPdf) ?>">Editar registro</a>.
</div>
<?php else: ?>
<?= view('registers/partials/report_viewreport_hidden_analisis', ['grupos' => $grupos]) ?>
<?= view('registers/partials/report_pdfjs_viewer', [
    'ridPdf'  => $ridPdf,
    'pdf_url' => site_url('registers/pdf/' . $ridPdf . '?inline=1'),
]) ?>
<?= view('registers/partials/report_viewreport_actions_bar', [
    'ridPdf'                   => $ridPdf,
    'compOk'                   => $compOk,
    'lblComp'                  => $lblComp,
    'envelope_print_available' => $envelope_print_available ?? false,
    'delivery_show_notify_button'   => ! empty($delivery_show_notify_button ?? false),
    'delivery_pending_for_registro' => ! empty($delivery_pending_for_registro ?? false),
    'sticky'                   => false,
]) ?>
<?php endif; ?>

<?php if (! $compOk && ! empty($grupos)): ?>
<div class="small text-muted text-center mt-2 w-100">
    <?php if (! empty($comprobante_pdf_sin_registro_pago ?? false)): ?>
        <i class="fa-solid fa-triangle-exclamation me-1 text-warning"></i>Sin registro de pago en base de datos: cree o vincule el pago desde la lista de registros.
    <?php elseif (! empty($comprobante_pdf_pendiente_pago ?? false)): ?>
        <i class="fa-solid fa-circle-info me-1"></i>La descarga del comprobante queda disponible cuando el saldo sea 0 o el monto pagado cubra el total (revise en lista de registros → historial de pagos).
    <?php else: ?>
        <i class="fa-solid fa-circle-info me-1"></i>Si no puede descargar el comprobante, verifique el pago de la orden.
    <?php endif; ?>
</div>
<?php endif; ?>
</fieldset>
<?= $this->endSection() ?>

<?= $this->section('scripts') ?>
<?php if (! empty($grupos)): ?>
<script src="<?= asset_url('js/vendor/pdfjs/pdf.min.js') ?>"></script>
<script src="<?= asset_url('js/report-pdfjs-viewer.js') ?>"></script>
<?php endif; ?>
<script>
document.addEventListener('DOMContentLoaded', function() {
    if (typeof window.initReportPdfJsViewer === 'function') {
        window.initReportPdfJsViewer('[data-report-pdfjs-viewer]');
    }

    function bindPrintWindow(selector, windowName) {
        document.querySelectorAll(selector).forEach(function(btn) {
            btn.addEventListener('click', function (e) {
                e.preventDefault();
                var url = btn.getAttribute('href');
                if (url.indexOf('auto=1') === -1) {
                    url += (url.indexOf('?') >= 0 ? '&' : '?') + 'auto=1';
                }
                var w = window.open(url, windowName, 'width=960,height=900');
                if (!w) {
                    window.location.href = url;
                }
            });
        });
    }
    bindPrintWindow('.js-viewreport-print', 'reportPrint');
    bindPrintWindow('.js-viewreport-envelope', 'envelopePrint');

    document.querySelectorAll('.js-viewreport-save').forEach(function(btn) {
        btn.addEventListener('click', function() {
            var datos = [];
            document.querySelectorAll('.analisis').forEach(function(el) {
                datos.push({
                    padre: el.getAttribute('padre'),
                    hijo: el.getAttribute('hijo'),
                    analisis: el.getAttribute('analisis'),
                    valor: el.getAttribute('value') || el.value,
                    unidad: el.getAttribute('unidad'),
                    minimo: el.getAttribute('min'),
                    maximo: el.getAttribute('max'),
                    registro_id: document.getElementById('registro_id').value
                });
            });
            fetch('<?= site_url('registers/saveanalisiss') ?>', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded', 'X-Requested-With': 'XMLHttpRequest' },
                body: 'data=' + encodeURIComponent(JSON.stringify(datos))
            })
            .then(function(r) { return r.json(); })
            .then(function() {
                window.location.href = '<?= site_url('registers') ?>';
            })
            .catch(function() { uiAlert('Error al guardar', 'Error'); });
        });
    });

    document.querySelectorAll('.js-viewreport-notify-delivery').forEach(function(btn) {
        btn.addEventListener('click', function() {
            var rid = btn.getAttribute('data-registro-id') || '';
            if (!rid) return;
            uiConfirm('¿Confirma que ya informó la entrega de estos resultados al médico o paciente?', 'Confirmar notificación').then(function(ok) {
                if (!ok) return;
                var csrfName = window.CI_CSRF_TOKEN_NAME || 'csrf_test_name';
                var csrfVal = window.CI_CSRF_TOKEN || '';
                var body = csrfName + '=' + encodeURIComponent(csrfVal);
                fetch('<?= site_url('registers/notifyDelivery') ?>/' + encodeURIComponent(rid), {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/x-www-form-urlencoded', 'X-Requested-With': 'XMLHttpRequest' },
                    body: body
                })
                .then(function(r) { return r.json(); })
                .then(function(res) {
                    if (res && res.csrf_token) {
                        window.CI_CSRF_TOKEN = res.csrf_token;
                        var meta = document.querySelector('meta[name="csrf-token"]');
                        if (meta) meta.setAttribute('content', res.csrf_token);
                    }
                    if (!res || !res.success) {
                        uiAlert((res && res.message) ? res.message : 'No se pudo registrar la notificación.', 'Error');
                        return;
                    }
                    document.querySelectorAll('.js-viewreport-notify-delivery').forEach(function(b) { b.remove(); });
                    document.querySelectorAll('.registro-delivery-pending-alert').forEach(function(el) { el.remove(); });
                    var headerNotif = document.querySelector('.header-delivery-notifications');
                    var n = parseInt(res.pending_count, 10);
                    var labelText = (!n || n < 1) ? 'Mis notificaciones' : (n === 1 ? '1 notificación' : (n + ' notificaciones'));
                    if (headerNotif) {
                        var labelEl = headerNotif.querySelector('.header-delivery-notifications__label');
                        if (labelEl) {
                            labelEl.textContent = labelText;
                            if (!n || n < 1) {
                                labelEl.classList.add('d-none', 'd-md-inline');
                            } else {
                                labelEl.classList.remove('d-none', 'd-md-inline');
                            }
                        }
                        if (!n || n < 1) {
                            headerNotif.classList.remove('header-delivery-notifications--active');
                            var dot = headerNotif.querySelector('.header-delivery-notifications__dot');
                            if (dot) dot.remove();
                            headerNotif.setAttribute('title', 'Ver notificaciones de entrega de análisis');
                        } else {
                            headerNotif.classList.add('header-delivery-notifications--active');
                            var iconWrap = headerNotif.querySelector('.header-delivery-notifications__icon');
                            if (iconWrap && !headerNotif.querySelector('.header-delivery-notifications__dot')) {
                                var dotEl = document.createElement('span');
                                dotEl.className = 'header-delivery-notifications__dot';
                                dotEl.setAttribute('aria-hidden', 'true');
                                iconWrap.appendChild(dotEl);
                            }
                            headerNotif.setAttribute('title', labelText);
                        }
                    }
                    var banner = document.querySelector('.delivery-pending-alert');
                    if (banner) {
                        if (!n || n < 1) {
                            banner.remove();
                        } else {
                            banner.innerHTML = '<span class="fw-semibold"><i class="fa-solid fa-bell me-1"></i> Tiene ' + labelText + '</span><span class="d-none d-md-inline"> — haga clic para ver el listado</span>';
                        }
                    }
                    if (typeof showToast === 'function') {
                        showToast(res.message || 'Entrega notificada.', 'success');
                    } else {
                        uiAlert(res.message || 'Entrega notificada.', 'Éxito');
                    }
                })
                .catch(function() { uiAlert('Error de conexión al registrar la notificación.', 'Error'); });
            });
        });
    });
});
</script>
<?= $this->endSection() ?>
