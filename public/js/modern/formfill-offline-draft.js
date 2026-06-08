/**
 * Borrador local opcional en formfill — no altera el envío al servidor.
 */
(function () {
    'use strict';

    var registroInput = document.getElementById('registro_id');
    if (!registroInput) return;

    var registroId = registroInput.value;
    if (!registroId) return;

    var storageKey = 'lab_formfill_draft_' + registroId;
    var DB_NAME = 'lab_offline_drafts';
    var STORE = 'drafts';

    function openDb() {
        return new Promise(function (resolve, reject) {
            if (!window.indexedDB) {
                reject(new Error('no indexedDB'));
                return;
            }
            var req = indexedDB.open(DB_NAME, 1);
            req.onupgradeneeded = function () {
                req.result.createObjectStore(STORE, { keyPath: 'key' });
            };
            req.onsuccess = function () { resolve(req.result); };
            req.onerror = function () { reject(req.error); };
        });
    }

    function collectFormData() {
        var data = {};
        document.querySelectorAll('#customer_basic_info input, #customer_basic_info textarea, #customer_basic_info select').forEach(function (el) {
            if (!el.name || el.type === 'hidden' && el.name.indexOf('csrf') !== -1) return;
            if (el.type === 'checkbox') data[el.name] = el.checked;
            else if (el.type === 'radio') { if (el.checked) data[el.name] = el.value; }
            else data[el.name] = el.value;
        });
        return data;
    }

    function applyFormData(data) {
        if (!data || typeof data !== 'object') return;
        Object.keys(data).forEach(function (name) {
            var els = document.querySelectorAll('[name="' + name.replace(/"/g, '\\"') + '"]');
            els.forEach(function (el) {
                if (el.type === 'checkbox') el.checked = !!data[name];
                else if (el.type === 'radio') el.checked = (el.value === data[name]);
                else if (el.tagName !== 'BUTTON') el.value = data[name];
            });
        });
    }

    function saveDraft() {
        var payload = { key: storageKey, registroId: registroId, data: collectFormData(), ts: Date.now() };
        openDb().then(function (db) {
            var tx = db.transaction(STORE, 'readwrite');
            tx.objectStore(STORE).put(payload);
        }).catch(function () {
            try { localStorage.setItem(storageKey, JSON.stringify(payload)); } catch (e) { /* ignore */ }
        });
    }

    function loadDraft() {
        return openDb().then(function (db) {
            return new Promise(function (resolve) {
                var tx = db.transaction(STORE, 'readonly');
                var req = tx.objectStore(STORE).get(storageKey);
                req.onsuccess = function () { resolve(req.result || null); };
                req.onerror = function () { resolve(null); };
            });
        }).catch(function () {
            try {
                var raw = localStorage.getItem(storageKey);
                return raw ? JSON.parse(raw) : null;
            } catch (e) { return null; }
        });
    }

    function clearDraft() {
        openDb().then(function (db) {
            var tx = db.transaction(STORE, 'readwrite');
            tx.objectStore(STORE).delete(storageKey);
        }).catch(function () {
            try { localStorage.removeItem(storageKey); } catch (e) { /* ignore */ }
        });
    }

    function showRestorePrompt(draft) {
        if (!draft || !draft.data) return;
        var toast = document.createElement('div');
        toast.className = 'alert alert-info lab-draft-toast shadow';
        toast.innerHTML = '<div class="small mb-2">Hay un borrador local sin enviar.</div>'
            + '<button type="button" class="btn btn-sm btn-primary me-1" data-action="restore">Restaurar</button>'
            + '<button type="button" class="btn btn-sm btn-outline-secondary" data-action="discard">Descartar</button>';
        document.body.appendChild(toast);
        toast.querySelector('[data-action="restore"]').addEventListener('click', function () {
            applyFormData(draft.data);
            toast.remove();
        });
        toast.querySelector('[data-action="discard"]').addEventListener('click', function () {
            clearDraft();
            toast.remove();
        });
    }

    var saveTimer = null;
    document.addEventListener('input', function (ev) {
        if (!ev.target.closest('#customer_basic_info')) return;
        clearTimeout(saveTimer);
        saveTimer = setTimeout(saveDraft, 800);
    });

    loadDraft().then(showRestorePrompt);

    /* Limpiar borrador tras envío exitoso detectado por redirect/flash — hook en submit existente */
    var origFetch = window.fetch;
    if (typeof origFetch === 'function') {
        window.fetch = function () {
            var reqUrl = '';
            try {
                var a0 = arguments[0];
                reqUrl = (a0 && a0.url) ? a0.url : String(a0 || '');
            } catch (e) { reqUrl = ''; }
            return origFetch.apply(this, arguments).then(function (res) {
                if (res && res.ok && (reqUrl.indexOf('saveregvalues') !== -1 || reqUrl.indexOf('saveanalisiss') !== -1)) {
                    clearDraft();
                }
                return res;
            });
        };
    }
})();
