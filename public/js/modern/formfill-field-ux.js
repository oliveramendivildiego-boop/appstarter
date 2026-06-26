/**
 * Mejora UX de campos en registers/view (captura de resultados).
 * Inspirado en buenas prácticas de input field design (labels, helper, estados).
 */
(function () {
    'use strict';

    function stripTrailingColon(text) {
        return (text || '').replace(/\s*:\s*$/, '').trim();
    }

    function enhanceFormFieldLabels() {
        document.querySelectorAll('.register-form-field > .form-label').forEach(function (label) {
            if (label.dataset.enhanced === '1') {
                return;
            }
            label.dataset.enhanced = '1';

            var helperSmall = label.querySelector('small.text-muted');
            if (helperSmall) {
                var raw = (helperSmall.textContent || '').trim();
                var refText = raw.replace(/^\(?\s*Ref:\s*/i, '').replace(/\)\s*$/, '').trim();
                helperSmall.remove();
                if (refText !== '') {
                    var helperEl = document.createElement('div');
                    helperEl.className = 'register-form-helper';
                    helperEl.innerHTML = '<i class="fa-solid fa-ruler-horizontal" aria-hidden="true"></i><span>Rango ref.: ' + refText + '</span>';
                    label.insertAdjacentElement('afterend', helperEl);
                }
            }

            var badgeEl = label.querySelector('.badge-calculada');
            var badgeClone = badgeEl ? badgeEl.cloneNode(true) : null;
            var clone = label.cloneNode(true);
            clone.querySelectorAll('small, .badge-calculada').forEach(function (node) {
                node.remove();
            });
            var labelText = stripTrailingColon(clone.textContent);

            label.textContent = '';
            var titleSpan = document.createElement('span');
            titleSpan.className = 'register-form-label-text';
            titleSpan.textContent = labelText;
            label.appendChild(titleSpan);
            if (badgeClone) {
                label.appendChild(badgeClone);
            }
        });
    }

    function wrapFormControls() {
        document.querySelectorAll('.register-form-field .form-control, .register-form-field .form-select, .register-form-field textarea.form-control').forEach(function (el) {
            if (el.closest('.register-form-input-wrap') || el.closest('.note-editor')) {
                return;
            }
            if (el.classList.contains('input-texto-rico') || el.classList.contains('input-texto-fijo')) {
                return;
            }
            var wrap = document.createElement('div');
            wrap.className = 'register-form-input-wrap';
            if (el.tagName === 'SELECT') {
                wrap.classList.add('register-form-input-wrap--select');
            }
            if (el.tagName === 'TEXTAREA') {
                wrap.classList.add('register-form-input-wrap--textarea');
            }
            var umed = el.getAttribute('data-umedida');
            if (umed) {
                wrap.classList.add('register-form-input-wrap--unit');
            }
            el.parentNode.insertBefore(wrap, el);
            wrap.appendChild(el);
            if (umed) {
                var unitEl = document.createElement('span');
                unitEl.className = 'register-form-unit';
                unitEl.textContent = umed;
                unitEl.setAttribute('aria-hidden', 'true');
                wrap.appendChild(unitEl);
            }
        });
    }

    function styleRichTextEditors() {
        document.querySelectorAll('.register-form-field .note-editor.note-frame').forEach(function (editor) {
            editor.classList.add('register-form-rich-editor');
        });
    }

    function syncFieldVisualState(el) {
        if (!el || !el.classList.contains('input-con-ref')) {
            return;
        }
        var field = el.closest('.register-form-field');
        if (!field) {
            return;
        }
        var val = (el.value || '').trim();
        var isInvalid = el.classList.contains('is-invalid');

        field.classList.add('register-form-field--filled', 'register-form-field--valid');
        field.classList.toggle('register-form-field--has-value', val !== '');
        field.classList.toggle('register-form-field--error', isInvalid);
    }

    function bindFieldStateListeners() {
        document.querySelectorAll('.register-form-field .input-con-ref').forEach(function (el) {
            if (el.dataset.stateBound === '1') {
                return;
            }
            el.dataset.stateBound = '1';
            el.addEventListener('input', function () {
                syncFieldVisualState(el);
            });
            el.addEventListener('change', function () {
                syncFieldVisualState(el);
            });
        });
    }

    function initFieldStates() {
        document.querySelectorAll('.register-form-field').forEach(function (field) {
            field.classList.add('register-form-field--filled', 'register-form-field--valid');
        });
        bindFieldStateListeners();
        document.querySelectorAll('.register-form-field .input-con-ref').forEach(syncFieldVisualState);
    }

    function patchValidationHook() {
        if (typeof window.__formfillSyncFieldState === 'function') {
            return;
        }
        window.__formfillSyncFieldState = syncFieldVisualState;
    }

    function enhanceNotesSection() {
        var leyendaRow = document.querySelector('#leyenda_sugerida') && document.querySelector('#leyenda_sugerida').closest('.row');
        var comentario = document.getElementById('comentario_resultado');
        if (!comentario) {
            return;
        }
        var container = comentario.closest('.col-12') || comentario.parentElement;
        if (!container || container.classList.contains('register-form-notes')) {
            return;
        }
        container.classList.add('register-form-notes');
        comentario.classList.add('form-control');
        comentario.closest('.row')?.classList.add('register-form-notes-row');
    }

    window.registerFormEnhanceFields = function () {
        enhanceFormFieldLabels();
        wrapFormControls();
        styleRichTextEditors();
        initFieldStates();
    };

    document.addEventListener('DOMContentLoaded', function () {
        if (!document.getElementById('customer_basic_info')) {
            return;
        }
        window.registerFormEnhanceFields();
        enhanceNotesSection();
        patchValidationHook();
    });
})();
