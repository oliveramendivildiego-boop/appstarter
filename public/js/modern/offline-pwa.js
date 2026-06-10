/**
 * PWA ligera: cache de assets estáticos + indicador offline.
 * No intercepta peticiones POST ni HTML dinámico.
 */
(function () {
    'use strict';

    var banner = null;

    function ensureBanner() {
        if (banner) return banner;
        banner = document.createElement('div');
        banner.className = 'lab-offline-banner';
        banner.setAttribute('role', 'status');
        banner.textContent = 'Sin conexión — los datos se guardan al reconectar';
        document.body.appendChild(banner);
        return banner;
    }

    function setOnlineState(online) {
        var b = ensureBanner();
        if (online) b.classList.remove('show');
        else b.classList.add('show');
    }

    window.addEventListener('online', function () { setOnlineState(true); });
    window.addEventListener('offline', function () { setOnlineState(false); });
    setOnlineState(navigator.onLine);

    if ('serviceWorker' in navigator) {
        window.addEventListener('load', function () {
            var base = (window.BASE_URL || '/').replace(/\/?$/, '/');
            var swVer = window.ASSET_SW_VERSION || '';
            var swUrl = base + 'sw.js' + (swVer ? ('?v=' + encodeURIComponent(swVer)) : '');
            navigator.serviceWorker.register(swUrl, { scope: base })
                .catch(function () { /* silencioso — no afecta la app */ });
        });
    }
})();
