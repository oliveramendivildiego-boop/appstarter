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
                    var msg = (d && d.message) ? d.message : (d && d.success ? 'Guardado correctamente' : 'Error al guardar');
                    showToast(msg, d && d.success ? 'success' : 'error');
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

document.addEventListener('DOMContentLoaded', function () {
    initAsyncForms();
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
