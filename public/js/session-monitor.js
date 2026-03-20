/**
 * Session Activity Monitor
 * Verifica periódicamente si el usuario sigue siendo activo
 * Si fue deshabilitado, lo saca de la sesión automáticamente
 */

(function() {
    'use strict';
    
    // Solo ejecutar en páginas que requieren login (no en login.php)
    if (window.location.pathname.includes('/login') || 
        window.location.pathname.includes('/google_login') ||
        window.location.pathname.includes('/qr')) {
        return;
    }
    
    var lastCheckTime = Date.now();
    var checkIntervalMs = 5000; // Verificar cada 5 segundos
    var isChecking = false;
    
    /**
     * Cierra la sesión del usuario en el cliente y redirige a login
     */
    function logoutUser(message) {
        console.log('🔴 CERRANDO SESIÓN:', message);
        
        // Mostrar notificación
        if (typeof showToast === 'function') {
            showToast(message || 'Tu sesión ha sido terminada', 'error');
        }
        
        // Limpiar localStorage/sessionStorage de cualquier cache
        try {
            localStorage.clear();
            sessionStorage.clear();
        } catch(e) {
            console.warn('No se pudo limpiar storage');
        }
        
        // Redirigir a login
        setTimeout(function() {
            window.location.href = window.location.origin + '/login';
        }, 800);
    }
    
    /**
     * Verifica si el usuario sigue siendo activo en el servidor
     */
    function checkUserActive() {
        if (isChecking) return; // Evitar múltiples requests simultáneos
        isChecking = true;
        lastCheckTime = Date.now();
        
        fetch(window.location.origin + '/status/checkEmployeeActive', {
            method: 'GET',
            headers: {
                'X-Requested-With': 'XMLHttpRequest'
            }
        })
        .then(function(response) { return response.json(); })
        .then(function(data) {
            isChecking = false;
            
            // Si el usuario NO está activo, sacar de sesión
            if (data.active !== true) {
                console.log('⚠️ Usuario deshabilitado o sesión expirada. Razón:', data.reason);
                logoutUser('Tu sesión ha sido cerrada por: ' + (data.reason || 'deshabilitación'));
                return;
            }
            
            console.log('✓ Usuario activo');
        })
        .catch(function(err) {
            isChecking = false;
            console.error('Error checking user activity:', err);
            // En caso de error, no hacer nada (podría ser un problema de conectividad)
        });
    }
    
    // Iniciar monitoreo solo cuando la página está lista
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', function() {
            startMonitoring();
        });
    } else {
        startMonitoring();
    }
    
    function startMonitoring() {
        console.log('🟢 Session Activity Monitor iniciado');
        
        // Hacer check inicial
        checkUserActive();
        
        // Hacer check periódico
        setInterval(function() {
            checkUserActive();
        }, checkIntervalMs);
        
        // También verificar cuando el usuario hace clic (retorna del idle)
        document.addEventListener('click', function() {
            var now = Date.now();
            if (now - lastCheckTime > checkIntervalMs) {
                checkUserActive();
                lastCheckTime = now;
            }
        }, true);
        
        // Y cuando se enfoca la ventana
        window.addEventListener('focus', function() {
            var now = Date.now();
            if (now - lastCheckTime > checkIntervalMs) {
                checkUserActive();
                lastCheckTime = now;
            }
        });
    }
})();
