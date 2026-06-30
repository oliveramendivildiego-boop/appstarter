<?= $this->extend('layouts/main') ?>
<?= $this->section('title') ?>Reporte<?= $this->endSection() ?>

<?= $this->section('head_extra') ?>
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
.report-pdf-native-viewer {
    border: 1px solid #dee2e6;
    border-radius: 8px;
    background: #525659;
    overflow: hidden;
    box-shadow: 0 2px 10px rgba(0, 0, 0, 0.14);
}
.report-pdf-native-frame-host {
    position: relative;
    min-height: calc(100vh - 220px);
    background: #525659;
}
.report-pdf-native-frame {
    display: block;
    width: 100%;
    min-height: calc(100vh - 220px);
    border: 0;
    background: #fff;
}
.report-pdf-native-loading {
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
.report-pdf-native-viewer.is-loading .report-pdf-native-loading {
    display: flex;
}
.report-pdf-native-viewer:not(.is-loading) .report-pdf-native-loading {
    display: none;
}
.report-pdf-native-loading-spinner {
    width: 42px;
    height: 42px;
    border: 3px solid rgba(255, 255, 255, 0.25);
    border-top-color: #fff;
    border-radius: 50%;
    animation: report-pdf-native-spin 0.85s linear infinite;
}
.report-pdf-native-loading-text {
    margin: 0;
    font-size: 1rem;
    font-weight: 600;
    color: #fff;
}
.report-pdf-native-loading-hint {
    margin: 0;
    font-size: 0.875rem;
    color: rgba(255, 255, 255, 0.72);
    max-width: 320px;
}
.report-pdf-native-error {
    display: none;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    gap: 12px;
    padding: 32px 24px;
    text-align: center;
    background: #fff3cd;
    border: 1px solid #ffc107;
    border-radius: 8px;
    color: #664d03;
    min-height: 200px;
}
.report-pdf-native-viewer.is-error .report-pdf-native-error {
    display: flex;
}
.report-pdf-native-viewer.is-error .report-pdf-native-frame,
.report-pdf-native-viewer.is-error .report-pdf-native-loading {
    display: none !important;
}
@keyframes report-pdf-native-spin {
    to { transform: rotate(360deg); }
}
@media (max-width: 767.98px) {
    .report-pdf-native-frame-host,
    .report-pdf-native-frame {
        min-height: 70vh;
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
$lblComp = ! empty($sin_billing_enabled ?? false) ? 'Factura' : 'Recibo';
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
    <i class="fa-solid fa-info-circle me-2"></i>Las pruebas de esta orden están sin valores o no se ha guardado ningún resultado. Complete los resultados en <a href="<?= site_url('registers/view/' . $ridPdf) ?>">Editar registro</a>.
</div>
<?php else: ?>
<?= view('registers/partials/report_viewreport_hidden_analisis', ['grupos' => $grupos]) ?>
<?= view('registers/partials/report_pdf_native_viewer', [
    'ridPdf'  => $ridPdf,
    'pdf_url' => site_url('registers/pdf/' . $ridPdf . '?inline=1&v=' . rawurlencode(\App\Libraries\Pdf\HtmlMpdfAdapter::CACHE_REVISION) . '&rid=' . $ridPdf),
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
<script>
document.addEventListener('DOMContentLoaded', function() {
    document.querySelectorAll('[data-report-pdf-native-viewer]').forEach(function(root) {
        var frame = root.querySelector('[data-pdf-native-frame]');
        var pdfUrl = root.getAttribute('data-pdf-url') || '';
        if (!frame || pdfUrl === '') {
            root.classList.remove('is-loading');
            return;
        }
        var hideLoading = function() {
            root.classList.remove('is-loading');
        };
        var formatPdfLoadError = function(raw) {
            raw = (raw || '').trim();
            var lower = raw.toLowerCase();
            if (raw.indexOf('HTTP 401') !== -1 || lower.indexOf('sesión') !== -1 || lower.indexOf('session') !== -1) {
                return 'Sesión expirada o no válida. Recargue esta página e inicie sesión de nuevo. La URL del PDF solo funciona con sesión activa de empleado.';
            }
            if (raw === '' || raw === 'not-pdf' || raw === 'empty-pdf' || raw === 'invalid-pdf-bytes') {
                return 'El servidor no devolvió un PDF válido. Pulse «Reintentar» (debe tener sesión iniciada).';
            }
            if (raw.indexOf('HTTP 503') !== -1 || raw.indexOf('generation-failed') !== -1 || raw.indexOf('deploy-incomplete') !== -1) {
                return raw.length > 900 ? raw.substring(0, 900) + '…' : raw;
            }
            if (raw.indexOf('No se pudo generar el PDF') !== -1) {
                return raw.length > 700 ? raw.substring(0, 700) + '…' : raw;
            }
            if (lower.indexOf('iniciar sesión') !== -1) {
                return 'Se requiere iniciar sesión como empleado del laboratorio. Abra el reporte desde el sistema (no pegue la URL sin haber entrado).';
            }
            if (raw.charAt(0) === '<' || raw.indexOf('<div') !== -1 || raw.indexOf('<!DOCTYPE') !== -1) {
                return 'El servidor respondió con HTML en lugar de PDF. Si ve «Iniciar sesión», vuelva a entrar al sistema. Si no, revise writable/logs/ o ejecute php writable/scripts/pdf_health_check.php [registro]';
            }
            return raw.length > 500 ? raw.substring(0, 500) + '…' : raw;
        };
        var pdfBlobUrl = null;
        var showError = function(detail) {
            root.classList.remove('is-loading');
            root.classList.add('is-error');
            var detailEl = root.querySelector('[data-pdf-native-error-detail]');
            if (detailEl && detail) {
                detailEl.textContent = formatPdfLoadError(detail);
            }
        };
        var loadPdf = function(forceFresh) {
            root.classList.remove('is-error');
            root.classList.add('is-loading');
            if (pdfBlobUrl) {
                URL.revokeObjectURL(pdfBlobUrl);
                pdfBlobUrl = null;
            }
            frame.src = 'about:blank';
            var loadUrl = pdfUrl;
            if (forceFresh) {
                loadUrl += (loadUrl.indexOf('?') >= 0 ? '&' : '?') + 'purge_pdf=1&_=' + Date.now();
            }
            fetch(loadUrl, {
                method: 'GET',
                credentials: 'same-origin',
                headers: { 'Accept': 'application/pdf,application/octet-stream;q=0.9,*/*;q=0.8' },
                cache: forceFresh ? 'no-store' : 'default'
            })
            .then(function(res) {
                var errHdr = res.headers.get('X-Report-Pdf-Error') || '';
                var errDetail = res.headers.get('X-Report-Pdf-Error-Detail') || '';
                if (!res.ok) {
                    return res.text().then(function(t) {
                        var msg = (t || '').trim();
                        if (msg === '') {
                            msg = 'HTTP ' + res.status + (errHdr !== '' ? ' (' + errHdr + ')' : '');
                        }
                        if (errDetail !== '' && msg.indexOf(errDetail) === -1) {
                            msg = errDetail + '\n' + msg;
                        }
                        throw new Error(msg);
                    });
                }
                var ct = (res.headers.get('Content-Type') || '').toLowerCase();
                if (ct.indexOf('pdf') === -1 && ct.indexOf('octet-stream') === -1) {
                    return res.text().then(function(t) {
                        throw new Error(t || 'not-pdf');
                    });
                }
                return res.arrayBuffer().then(function(buf) {
                    if (!buf || buf.byteLength < 5) {
                        throw new Error('empty-pdf');
                    }
                    var bytes = new Uint8Array(buf);
                    if (String.fromCharCode(bytes[0], bytes[1], bytes[2], bytes[3]) !== '%PDF') {
                        var text = new TextDecoder().decode(buf.slice(0, Math.min(buf.byteLength, 1200)));
                        throw new Error(text || 'invalid-pdf-bytes');
                    }
                    return new Blob([buf], { type: 'application/pdf' });
                });
            })
            .then(function(blob) {
                pdfBlobUrl = URL.createObjectURL(blob);
                frame.src = pdfBlobUrl;
                frame.onload = function() {
                    hideLoading();
                };
            })
            .catch(function(err) {
                frame.src = 'about:blank';
                showError(err && err.message ? err.message : String(err));
            });
        };
        var retryBtn = root.querySelector('[data-pdf-native-retry]');
        if (retryBtn) {
            retryBtn.addEventListener('click', function() { loadPdf(true); });
        }
        root.__reloadPdf = function() { loadPdf(true); };
        loadPdf(false);
    });
});
</script>
<?php endif; ?>
<script>
document.addEventListener('DOMContentLoaded', function() {
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
            var saveBtns = document.querySelectorAll('.js-viewreport-save');
            var defaultHtml = btn.innerHTML;
            saveBtns.forEach(function(b) {
                b.disabled = true;
                b.innerHTML = '<span class="spinner-border spinner-border-sm me-1" role="status" aria-hidden="true"></span>Actualizando…';
            });
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
            var csrfName = window.CI_CSRF_TOKEN_NAME || 'csrf_test_name';
            var csrfVal = window.CI_CSRF_TOKEN || '';
            if (!csrfVal) {
                saveBtns.forEach(function(b) {
                    b.disabled = false;
                    b.innerHTML = defaultHtml;
                });
                uiAlert('Sesión de seguridad no disponible. Recargue la página (F5) e intente de nuevo.', 'Error');
                return;
            }
            var body = 'data=' + encodeURIComponent(JSON.stringify(datos));
            body += '&' + encodeURIComponent(csrfName) + '=' + encodeURIComponent(csrfVal);
            fetch('<?= site_url('registers/saveanalisiss') ?>', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                    'X-Requested-With': 'XMLHttpRequest',
                    'X-CSRF-TOKEN': csrfVal
                },
                credentials: 'same-origin',
                body: body
            })
            .then(function(r) {
                if (r.status === 403) {
                    return r.text().then(function(t) {
                        var msg = 'La sesión de seguridad expiró o no es válida. Recargue la página (F5) e intente de nuevo.';
                        if (t && t.indexOf('anulada') !== -1) {
                            msg = 'Esta orden fue anulada y no puede modificarse.';
                        }
                        throw new Error(msg);
                    });
                }
                return r.json();
            })
            .then(function(res) {
                saveBtns.forEach(function(b) {
                    b.disabled = false;
                    b.innerHTML = defaultHtml;
                });
                if (res && res.csrf_token) {
                    window.CI_CSRF_TOKEN = res.csrf_token;
                    var meta = document.querySelector('meta[name="csrf-token"]');
                    if (meta) meta.setAttribute('content', res.csrf_token);
                }
                if (!res || res.success === false) {
                    uiAlert((res && res.message) ? res.message : 'Error al actualizar', 'Error');
                    return;
                }
                var viewer = document.querySelector('[data-report-pdf-native-viewer]');
                if (viewer && typeof viewer.__reloadPdf === 'function') {
                    viewer.__reloadPdf();
                }
                if (typeof showToast === 'function') {
                    showToast(res.message || 'Reporte actualizado.', 'success');
                }
            })
            .catch(function(err) {
                saveBtns.forEach(function(b) {
                    b.disabled = false;
                    b.innerHTML = defaultHtml;
                });
                uiAlert(err && err.message ? err.message : 'Error al actualizar', 'Error');
            });
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
                    var labelText = (!n || n < 1) ? 'Sin pendientes' : (n === 1 ? '1 notificación' : (n + ' notificaciones'));
                    var hrefPendientes = '<?= site_url('registers/deliveryNotifications') ?>';
                    var hrefReporte = '<?= site_url('reports/notificacionesEntrega') ?>';
                    if (headerNotif) {
                        var labelEl = headerNotif.querySelector('.header-delivery-notifications__label');
                        if (labelEl) {
                            labelEl.textContent = labelText;
                        }
                        if (!n || n < 1) {
                            headerNotif.classList.remove('header-delivery-notifications--active');
                            var dot = headerNotif.querySelector('.header-delivery-notifications__dot');
                            if (dot) dot.remove();
                            headerNotif.setAttribute('title', 'Sin pendientes');
                            headerNotif.setAttribute('href', hrefReporte);
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
                            headerNotif.setAttribute('href', hrefPendientes);
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
