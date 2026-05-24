/**
 * Formatea un monto con el símbolo de moneda configurado en el sistema.
 */
function formatCurrencyAmount(amount, decimals) {
    var sym = (typeof window.APP_CURRENCY_SYMBOL === 'string' && window.APP_CURRENCY_SYMBOL !== '')
        ? window.APP_CURRENCY_SYMBOL
        : '$';
    var isRight = !!window.APP_CURRENCY_IS_RIGHT;
    var dec = (typeof decimals === 'number') ? decimals : 2;
    var n = Number(amount);
    if (!isFinite(n)) {
        n = 0;
    }
    var formatted = n.toLocaleString('es-BO', {
        minimumFractionDigits: dec,
        maximumFractionDigits: dec
    });
    return isRight ? (formatted + ' ' + sym) : (sym + ' ' + formatted);
}

/**
 * Posiciona el calendario flatpickr con arrowTop arrowLeft (clases consistentes en toda la web)
 */
function flatpickrPositionArrowTopLeft(instance) {
    if (!instance || !instance.calendarContainer) return;
    var el = instance.calendarContainer;
    el.classList.remove('arrowBottom', 'arrowRight');
    el.classList.add('arrowTop', 'arrowLeft');
}

/**
 * Toast de notificaciones en la web (sin alert)
 */
function showToast(message, type) {
    type = type || 'success';
    var container = document.getElementById('toast-container');
    if (!container) return;
    var bg = type === 'success' ? 'bg-success' : 'bg-danger';
    var icon = type === 'success' ? 'fa-check-circle' : 'fa-exclamation-circle';
    var el = document.createElement('div');
    el.className = 'toast-notif alert ' + bg + ' text-white shadow-lg d-flex align-items-center gap-2';
    el.setAttribute('role', 'alert');
    el.innerHTML = '<i class="fa-solid ' + icon + '"></i><span>' + (message || '') + '</span>';
    el.style.minWidth = '280px';
    el.style.animation = 'toastIn 0.3s ease';
    container.appendChild(el);
    setTimeout(function () {
        el.style.animation = 'toastOut 0.3s ease';
        setTimeout(function () { el.remove(); }, 300);
    }, 4000);
}

/**
 * Modal genérico (mensajes/confirmaciones) para reemplazar alert()/confirm()
 */
function uiAlert(text, title) {
    title = title || 'Mensaje';
    var modal = document.getElementById('globalModalMensaje');
    var modalTitulo = document.getElementById('globalModalMensajeTitulo');
    var modalTexto = document.getElementById('globalModalMensajeTexto');
    if (!modal || !modalTitulo || !modalTexto || typeof bootstrap === 'undefined' || !bootstrap.Modal) {
        if (typeof console !== 'undefined') console.warn('[uiAlert]', text);
        return;
    }
    modalTitulo.textContent = title;
    modalTexto.textContent = text == null ? '' : String(text);
    new bootstrap.Modal(modal).show();
}

/**
 * confirm modal -> Promise<boolean>
 */
function uiConfirm(message, title) {
    title = title || 'Confirmar';
    var modal = document.getElementById('globalModalConfirmacion');
    var modalTitulo = document.getElementById('globalModalConfirmacionTitulo');
    var modalTexto = document.getElementById('globalModalConfirmacionTexto');
    var btnAceptar = document.getElementById('globalModalConfirmacionAceptar');
    var btnCancelar = document.getElementById('globalModalConfirmacionCancelar');

    if (!modal || !modalTitulo || !modalTexto || !btnAceptar || !btnCancelar || typeof bootstrap === 'undefined' || !bootstrap.Modal) {
        if (typeof console !== 'undefined') console.warn('[uiConfirm]', message);
        return Promise.resolve(false);
    }

    modalTitulo.textContent = title;
    modalTexto.textContent = message == null ? '' : String(message);

    return new Promise(function(resolve) {
        var modalInstance = bootstrap.Modal.getInstance(modal) || new bootstrap.Modal(modal);

        btnAceptar.onclick = function() { resolve(true); modalInstance.hide(); };
        btnCancelar.onclick = function() { resolve(false); modalInstance.hide(); };
        modalInstance.show();
    });
}

// Para reemplazar confirm() en onclick de enlaces
function uiConfirmLink(anchorEl, message, title) {
    if (!anchorEl) return false;
    var href = anchorEl.getAttribute('href') || '';
    if (href === '') return false;

    uiConfirm(message, title || 'Confirmar').then(function(ok) {
        if (ok) window.location.href = href;
    });
    return false; // evita navegación inmediata
}

// Para reemplazar confirm() en onsubmit de formularios
function uiConfirmForm(formEl, message, title) {
    if (!formEl) return false;
    uiConfirm(message, title || 'Confirmar').then(function(ok) {
        if (ok) formEl.submit();
    });
    return false; // evita envío inmediato
}

/**
 * Inicializa formularios con data-async="1" para envío asíncrono
 */
function initAsyncForms() {
    var forms = document.querySelectorAll('form[data-async="1"]');
    forms.forEach(function (form) {
        if (form.dataset.asyncInit === '1') return;
        form.dataset.asyncInit = '1';
        form.addEventListener('submit', function (e) {
            e.preventDefault();
            var f = form;
            if (typeof $ !== 'undefined' && $(f).data('validator')) {
                var validator = $(f).validate();
                if (validator && !validator.form()) return;
            }
            var btn = f.querySelector('[type="submit"]');
            if (btn) {
                btn.disabled = true;
                btn.dataset.origText = (btn.tagName === 'INPUT' ? btn.value : btn.innerHTML) || '';
                if (btn.tagName === 'INPUT') btn.value = 'Enviando...';
                else btn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span>Enviando...';
            }
            var formData = new FormData(f);
            var csrfName = document.querySelector('input[name="csrf_token"]') ? 'csrf_token' : (document.querySelector('input[name="' + (typeof CI_CSRF_TOKEN_NAME !== 'undefined' ? CI_CSRF_TOKEN_NAME : 'csrf_test_name') + '"]') ? (typeof CI_CSRF_TOKEN_NAME !== 'undefined' ? CI_CSRF_TOKEN_NAME : 'csrf_test_name') : null);
            var headers = { 'X-Requested-With': 'XMLHttpRequest' };
            fetch(f.action, { method: 'POST', body: formData, headers: headers })
                .then(function (r) {
                    var ct = r.headers.get('content-type') || '';
                    if (ct.includes('application/json')) return r.json();
                    return r.text().then(function (t) { throw new Error(t || 'Error del servidor'); });
                })
                .then(function (d) {
                    if (btn) { btn.disabled = false; if (btn.tagName === 'INPUT') btn.value = btn.dataset.origText || ''; else btn.innerHTML = btn.dataset.origText || ''; }
                    if (d && d.csrf_token && d.csrf_name) {
                        window.CI_CSRF_TOKEN = d.csrf_token;
                        window.CI_CSRF_TOKEN_NAME = d.csrf_name;
                        var csrfInp = f.querySelector('input[name="' + d.csrf_name + '"]') || f.querySelector('input[name="csrf_test_name"]') || f.querySelector('input[name*="csrf"]');
                        if (csrfInp) { csrfInp.name = d.csrf_name; csrfInp.value = d.csrf_token; }
                    }
                    var msg = (d && d.message) ? d.message : (d && d.success ? 'Guardado correctamente' : 'Error al guardar');
                    showToast(msg, d && d.success ? 'success' : 'error');
                    if (d && d.success && f.getAttribute('data-reload-on-success') === '1') {
                        setTimeout(function () { window.location.reload(); }, 600);
                        return;
                    }
                    if (d && d.redirect_url) {
                        setTimeout(function () { window.location.href = d.redirect_url; }, 800);
                    }
                })
                .catch(function (err) {
                    if (btn) { btn.disabled = false; if (btn.tagName === 'INPUT') btn.value = btn.dataset.origText || ''; else btn.innerHTML = btn.dataset.origText || ''; }
                    showToast(err.message || 'Error en la petición', 'error');
                });
        });
    });
}

/**
 * Envuelve tablas en contenedor responsivo Bootstrap.
 * Se aplica una sola vez por tabla y permite exclusión con data-no-responsive.
 */
function makeTablesResponsive(root) {
    var scope = root || document;
    var tables = scope.querySelectorAll('table');
    tables.forEach(function (table) {
        if (!table || table.dataset.noResponsive === '1') return;
        if (table.closest('.table-responsive')) return;
        if (table.closest('table')) return; // evita tablas anidadas dentro de otra tabla

        var wrapper = document.createElement('div');
        wrapper.className = 'table-responsive';
        table.parentNode.insertBefore(wrapper, table);
        wrapper.appendChild(table);
    });
}

document.addEventListener('DOMContentLoaded', function () {
    initAsyncForms();
    makeTablesResponsive(document);

    // Cubre tablas que aparecen dinámicamente (AJAX/render tardío)
    var observerTimer = null;
    if (typeof MutationObserver !== 'undefined') {
        var observer = new MutationObserver(function () {
            if (observerTimer) clearTimeout(observerTimer);
            observerTimer = setTimeout(function () { makeTablesResponsive(document); }, 80);
        });
        observer.observe(document.body, { childList: true, subtree: true });
    }

    if (typeof $ !== 'undefined' && window.CI_CSRF_TOKEN_NAME && window.CI_CSRF_TOKEN) {
        $.ajaxSetup({
            beforeSend: function (xhr, opts) {
                if (opts.type !== 'POST' && opts.type !== 'PUT' && opts.type !== 'PATCH') return;
                var data = opts.data;
                if (data instanceof FormData) {
                    if (!data.has(window.CI_CSRF_TOKEN_NAME)) {
                        data.append(window.CI_CSRF_TOKEN_NAME, window.CI_CSRF_TOKEN);
                    }
                } else if (typeof data === 'string' && data.indexOf(window.CI_CSRF_TOKEN_NAME) === -1) {
                    opts.data = data + (data ? '&' : '') + window.CI_CSRF_TOKEN_NAME + '=' + encodeURIComponent(window.CI_CSRF_TOKEN);
                } else if (typeof data === 'object' && data !== null && !Array.isArray(data)) {
                    data[window.CI_CSRF_TOKEN_NAME] = window.CI_CSRF_TOKEN;
                }
            }
        });
    }
});
