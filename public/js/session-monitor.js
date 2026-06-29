/**
 * Comprueba en segundo plano si el empleado sigue activo.
 * Sin polling agresivo: solo reacciona cuando el estado pasa de activo → inactivo.
 * Otras pestañas se enteran vía localStorage al cambiar un empleado en Configuración.
 */
(function () {
    'use strict';

    if (window.__sessionMonitorStarted) {
        return;
    }

    var monitorDisabled = false;
    try {
        monitorDisabled = window.SESSION_MONITOR_DISABLED === true
            || window.SESSION_MONITOR_DISABLED === 'true'
            || localStorage.getItem('disableSessionMonitor') === '1';
    } catch (e) {
        monitorDisabled = window.SESSION_MONITOR_DISABLED === true
            || window.SESSION_MONITOR_DISABLED === 'true';
    }
    if (monitorDisabled) {
        return;
    }

    var path = window.location.pathname || '';
    if (path.includes('/login') || path.includes('/google_login') || path.includes('/qr')) {
        return;
    }

    window.__sessionMonitorStarted = true;

    var STATUS_REV_KEY = 'lab_employee_status_rev';
    var checkUrl = (typeof BASE_URL === 'string' && BASE_URL)
        ? (BASE_URL.replace(/\/?$/, '') + '/status/checkEmployeeActive')
        : (window.location.origin + '/status/checkEmployeeActive');

    var pollEnabled = window.SESSION_MONITOR_POLL === true || window.SESSION_MONITOR_POLL === 'true';
    var minIntervalMs = 60000;
    var configured = parseInt(window.SESSION_MONITOR_INTERVAL_MS, 10);
    var pollIntervalMs = (!isNaN(configured) && configured >= minIntervalMs) ? configured : 300000;

    var knownActive = null;
    var isChecking = false;
    var logoutPending = false;
    var pollTimer = null;
    var hiddenSince = null;

    function redirectToLogin(message) {
        if (logoutPending) {
            return;
        }
        logoutPending = true;
        if (typeof showToast === 'function') {
            showToast(message || 'Tu sesión ha sido cerrada', 'error');
        }
        setTimeout(function () {
            window.location.href = (typeof BASE_URL === 'string' && BASE_URL)
                ? BASE_URL.replace(/\/?$/, '') + '/login'
                : window.location.origin + '/login';
        }, 600);
    }

    function applyStatus(data) {
        var active = !!(data && data.active === true);

        if (knownActive === null) {
            knownActive = active;
            if (!active) {
                redirectToLogin('Tu sesión ya no es válida');
            }
            return;
        }

        if (knownActive === true && !active) {
            knownActive = false;
            var reason = (data && data.reason) ? String(data.reason) : 'cierre de sesión';
            redirectToLogin('Tu sesión ha sido cerrada (' + reason + ')');
            return;
        }

        knownActive = active;
    }

    function checkUserActive() {
        if (isChecking || logoutPending || document.hidden) {
            return;
        }
        isChecking = true;

        fetch(checkUrl, {
            method: 'GET',
            headers: { 'X-Requested-With': 'XMLHttpRequest' },
            credentials: 'same-origin',
        })
            .then(function (response) {
                if (!response.ok) {
                    throw new Error('status_' + response.status);
                }
                return response.json();
            })
            .then(function (data) {
                isChecking = false;
                if (data && data.csrf_name && data.csrf_token) {
                    window.CI_CSRF_TOKEN_NAME = data.csrf_name;
                    window.CI_CSRF_TOKEN = data.csrf_token;
                    document.querySelectorAll('input[name="' + data.csrf_name + '"], input[name*="csrf"]').forEach(function (inp) {
                        inp.name = data.csrf_name;
                        inp.value = data.csrf_token;
                    });
                }
                applyStatus(data);
            })
            .catch(function () {
                isChecking = false;
            });
    }

    function startPollTimer() {
        if (!pollEnabled || pollTimer !== null) {
            return;
        }
        pollTimer = setInterval(function () {
            if (!document.hidden) {
                checkUserActive();
            }
        }, pollIntervalMs);
    }

    function stopPollTimer() {
        if (pollTimer !== null) {
            clearInterval(pollTimer);
            pollTimer = null;
        }
    }

    window.checkEmployeeStatusNow = checkUserActive;
    window.notifyEmployeeStatusChanged = function () {
        try {
            localStorage.setItem(STATUS_REV_KEY, String(Date.now()));
        } catch (e) {
            checkUserActive();
        }
    };

    function init() {
        checkUserActive();
        startPollTimer();

        window.addEventListener('storage', function (e) {
            if (e.key === STATUS_REV_KEY) {
                checkUserActive();
            }
        });

        document.addEventListener('visibilitychange', function () {
            if (document.hidden) {
                hiddenSince = Date.now();
                stopPollTimer();
                return;
            }
            var awayMs = hiddenSince ? (Date.now() - hiddenSince) : 0;
            hiddenSince = null;
            startPollTimer();
            if (awayMs >= 30000) {
                checkUserActive();
            }
        });
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }
})();
