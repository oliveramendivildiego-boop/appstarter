/**
 * Editor de matriz y estilos para comprobante de pago (Config → Estilo comprobante).
 */
(function () {
    'use strict';

    var bootEl = document.getElementById('comprobante-editor-boot-json');
    var boot = {};
    try {
        boot = bootEl ? JSON.parse(bootEl.textContent || '{}') : (window.COMPROBANTE_EDITOR_BOOT || {});
    } catch (e) {
        console.error('Comprobante editor boot JSON:', e);
    }

    var fieldLabels = boot.fieldLabels || {};
    var fieldSamples = boot.fieldSamples || {};
    var styleLabels = boot.styleLabels || {};
    var companyName = boot.companyName || 'Laboratorio';
    var defaultStyles = boot.defaultStyles || {};

    var state = normalizeLayout(boot.layout || {});
    var dragPayload = null;
    var editingUid = null;
    var modalEl = document.getElementById('comp_field_modal');
    var modal = modalEl && window.bootstrap ? new bootstrap.Modal(modalEl) : null;

    function uid() {
        return 'c' + Date.now().toString(36) + Math.random().toString(36).slice(2, 8);
    }

    function clamp(n, min, max) {
        return Math.max(min, Math.min(max, n));
    }

    function normalizeLayout(raw) {
        var def = boot.layout && boot.layout.client_grid ? boot.layout : {
            version: 1,
            styles: defaultStyles,
            client_grid: { columns: 2, rows: 3, items: [] }
        };
        if (!raw || typeof raw !== 'object') {
            return JSON.parse(JSON.stringify(def));
        }
        var cols = clamp(parseInt(raw.client_grid && raw.client_grid.columns, 10) || 2, 1, 4);
        var rows = clamp(parseInt(raw.client_grid && raw.client_grid.rows, 10) || 3, 1, 8);
        var styles = {};
        Object.keys(styleLabels).forEach(function (key) {
            styles[key] = normalizeStyleBlock(raw.styles && raw.styles[key], defaultStyles[key] || defaultStyles.body);
        });
        var items = [];
        if (raw.client_grid && Array.isArray(raw.client_grid.items)) {
            raw.client_grid.items.forEach(function (it) {
                var norm = normalizeItem(it, cols, rows);
                if (norm) {
                    items.push(norm);
                }
            });
        }
        return {
            version: 1,
            styles: styles,
            client_grid: { columns: cols, rows: rows, items: items }
        };
    }

    function normalizeStyleBlock(raw, fallback) {
        fallback = fallback || {};
        raw = raw || {};
        return {
            font_family: raw.font_family || fallback.font_family || 'DejaVu Sans',
            font_size_pt: clamp(parseFloat(raw.font_size_pt) || parseFloat(fallback.font_size_pt) || 10, 6, 24),
            font_weight: raw.font_weight || fallback.font_weight || 'normal',
            font_style: raw.font_style || fallback.font_style || 'normal',
            color: (raw.color || fallback.color || '#1E293B').toUpperCase(),
            text_transform: raw.text_transform || fallback.text_transform || 'none'
        };
    }

    function normalizeItem(it, cols, rows) {
        if (!it || !it.element_type || !fieldLabels[it.element_type]) {
            return null;
        }
        var row = clamp(parseInt(it.row, 10) || 0, 0, rows - 1);
        var col = clamp(parseInt(it.col, 10) || 0, 0, cols - 1);
        return {
            uid: it.uid || uid(),
            element_type: it.element_type,
            row: row,
            col: col,
            col_span: clamp(parseInt(it.col_span, 10) || 1, 1, cols - col),
            show_label: it.show_label !== false,
            custom_label: String(it.custom_label || defaultLabel(it.element_type))
        };
    }

    function defaultLabel(type) {
        var defs = {
            paciente_nombre: 'Paciente:',
            paciente_ci: 'Documento de identidad:',
            paciente_telefono: 'Teléfono:',
            medico: 'Médico referente:',
            institucion: 'Institución / procedencia:'
        };
        return defs[type] || '';
    }

    function getColors() {
        return {
            primary: colorVal('comprobante_primary_color', '#0F766E'),
            secondary: colorVal('comprobante_secondary_color', '#134E4A'),
            text: colorVal('comprobante_text_color', '#1E293B')
        };
    }

    function colorVal(id, fallback) {
        var el = document.getElementById(id);
        if (!el || !el.value) {
            return fallback;
        }
        return el.value.toUpperCase();
    }

    function readStylesFromMatrix() {
        document.querySelectorAll('#comp_style_matrix tr[data-style-key]').forEach(function (tr) {
            var key = tr.getAttribute('data-style-key');
            if (!key) {
                return;
            }
            var block = {};
            tr.querySelectorAll('.comp-style-input').forEach(function (inp) {
                var field = inp.getAttribute('data-style-field');
                if (!field) {
                    return;
                }
                block[field] = inp.type === 'number' ? parseFloat(inp.value) : inp.value;
            });
            state.styles[key] = normalizeStyleBlock(block, defaultStyles[key]);
        });
    }

    function applyStylePreviewSamples() {
        document.querySelectorAll('.comp-style-preview-sample').forEach(function (el) {
            var key = el.getAttribute('data-style-key');
            var st = state.styles[key];
            if (!st) {
                return;
            }
            el.style.fontFamily = '"' + st.font_family + '", Arial, sans-serif';
            el.style.fontSize = st.font_size_pt + 'pt';
            el.style.fontWeight = st.font_weight;
            el.style.fontStyle = st.font_style;
            el.style.color = st.color;
            el.style.textTransform = st.text_transform;
        });
    }

    function cssForStyle(st, extra) {
        extra = extra || {};
        var merged = Object.assign({}, st, extra);
        return [
            'font-family:"' + merged.font_family + '",Arial,sans-serif',
            'font-size:' + merged.font_size_pt + 'pt',
            'font-weight:' + merged.font_weight,
            'font-style:' + merged.font_style,
            'color:' + merged.color,
            'text-transform:' + merged.text_transform
        ].join(';');
    }

    function itemAtCell(row, col) {
        return state.client_grid.items.find(function (it) {
            return it.row === row && it.col === col;
        }) || null;
    }

    function rebuildMatrixTable() {
        var colsIn = document.getElementById('comp_grid_cols');
        var rowsIn = document.getElementById('comp_grid_rows');
        var cols = clamp(parseInt(colsIn && colsIn.value, 10) || 2, 1, 4);
        var rows = clamp(parseInt(rowsIn && rowsIn.value, 10) || 3, 1, 8);
        state.client_grid.columns = cols;
        state.client_grid.rows = rows;
        state.client_grid.items = state.client_grid.items
            .map(function (it) { return normalizeItem(it, cols, rows); })
            .filter(Boolean);

        var table = document.getElementById('comp_client_matrix');
        if (!table) {
            return;
        }
        var thead = '<tr><th class="comp-matrix-corner"></th>';
        for (var c = 0; c < cols; c++) {
            thead += '<th class="comp-matrix-col-th text-center small">Col ' + (c + 1) + '</th>';
        }
        thead += '</tr>';
        table.querySelector('thead').innerHTML = thead;

        var tbody = '';
        for (var r = 0; r < rows; r++) {
            tbody += '<tr><th class="comp-matrix-row-th text-center small">F' + (r + 1) + '</th>';
            for (c = 0; c < cols; c++) {
                tbody += '<td class="comp-matrix-cell" data-row="' + r + '" data-col="' + c + '" tabindex="0"></td>';
            }
            tbody += '</tr>';
        }
        table.querySelector('tbody').innerHTML = tbody;
        bindMatrixCells();
        renderMatrixItems();
    }

    function renderMatrixItems() {
        document.querySelectorAll('.comp-matrix-cell').forEach(function (cell) {
            cell.innerHTML = '';
            cell.classList.remove('comp-has-item', 'comp-drop-target');
            var row = parseInt(cell.getAttribute('data-row'), 10);
            var col = parseInt(cell.getAttribute('data-col'), 10);
            var item = itemAtCell(row, col);
            if (!item) {
                return;
            }
            cell.classList.add('comp-has-item');
            var label = fieldLabels[item.element_type] || item.element_type;
            var sample = fieldSamples[item.element_type] || '';
            cell.innerHTML =
                '<div class="comp-matrix-item" data-uid="' + item.uid + '">' +
                '<div class="comp-matrix-item__type">' + escapeHtml(label) + '</div>' +
                '<div class="comp-matrix-item__code">' + escapeHtml(item.element_type) + '</div>' +
                (sample ? '<div class="comp-matrix-item__sample">' + escapeHtml(sample) + '</div>' : '') +
                '</div>';
        });
    }

    function escapeHtml(s) {
        return String(s)
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;');
    }

    function bindMatrixCells() {
        document.querySelectorAll('.comp-matrix-cell').forEach(function (cell) {
            cell.addEventListener('dragover', function (e) {
                e.preventDefault();
                cell.classList.add('comp-drop-target');
            });
            cell.addEventListener('dragleave', function () {
                cell.classList.remove('comp-drop-target');
            });
            cell.addEventListener('drop', function (e) {
                e.preventDefault();
                cell.classList.remove('comp-drop-target');
                var type = dragPayload && dragPayload.element_type;
                if (!type) {
                    return;
                }
                var row = parseInt(cell.getAttribute('data-row'), 10);
                var col = parseInt(cell.getAttribute('data-col'), 10);
                placeItem(type, row, col);
                dragPayload = null;
            });
            cell.addEventListener('click', function (e) {
                var chip = e.target.closest('.comp-matrix-item');
                if (chip) {
                    openItemModal(chip.getAttribute('data-uid'));
                    return;
                }
                if (dragPayload && dragPayload.element_type) {
                    placeItem(dragPayload.element_type, parseInt(cell.getAttribute('data-row'), 10), parseInt(cell.getAttribute('data-col'), 10));
                    dragPayload = null;
                }
            });
        });
    }

    function placeItem(type, row, col) {
        state.client_grid.items = state.client_grid.items.filter(function (it) {
            return !(it.row === row && it.col === col);
        });
        state.client_grid.items.push({
            uid: uid(),
            element_type: type,
            row: row,
            col: col,
            col_span: 1,
            show_label: true,
            custom_label: defaultLabel(type)
        });
        renderMatrixItems();
        renderPreview();
    }

    function openItemModal(itemUid) {
        var item = state.client_grid.items.find(function (it) { return it.uid === itemUid; });
        if (!item || !modal) {
            return;
        }
        editingUid = itemUid;
        document.getElementById('comp_modal_type_label').textContent = fieldLabels[item.element_type] || item.element_type;
        document.getElementById('comp_modal_type_code').textContent = item.element_type;
        document.getElementById('comp_modal_label').value = item.custom_label || '';
        document.getElementById('comp_modal_show_label').checked = item.show_label !== false;
        document.getElementById('comp_modal_col_span').value = item.col_span || 1;
        document.getElementById('comp_modal_col_span').max = state.client_grid.columns - item.col;
        modal.show();
    }

    function bindPalette() {
        document.querySelectorAll('#comp-field-palette .comp-palette-item').forEach(function (el) {
            el.addEventListener('dragstart', function (e) {
                dragPayload = { element_type: el.getAttribute('data-element-type') };
                el.classList.add('comp-dragging');
                if (e.dataTransfer) {
                    e.dataTransfer.setData('text/plain', dragPayload.element_type);
                    e.dataTransfer.effectAllowed = 'copy';
                }
            });
            el.addEventListener('dragend', function () {
                el.classList.remove('comp-dragging');
            });
            el.addEventListener('keydown', function (e) {
                if (e.key === 'Enter' || e.key === ' ') {
                    e.preventDefault();
                    dragPayload = { element_type: el.getAttribute('data-element-type') };
                }
            });
        });
    }

    function bindModal() {
        var saveBtn = document.getElementById('comp_modal_save');
        var removeBtn = document.getElementById('comp_modal_remove');
        if (saveBtn) {
            saveBtn.addEventListener('click', function () {
                if (!editingUid) {
                    return;
                }
                var item = state.client_grid.items.find(function (it) { return it.uid === editingUid; });
                if (!item) {
                    return;
                }
                item.custom_label = document.getElementById('comp_modal_label').value;
                item.show_label = document.getElementById('comp_modal_show_label').checked;
                item.col_span = clamp(parseInt(document.getElementById('comp_modal_col_span').value, 10) || 1, 1, state.client_grid.columns - item.col);
                renderMatrixItems();
                renderPreview();
            });
        }
        if (removeBtn) {
            removeBtn.addEventListener('click', function () {
                if (!editingUid) {
                    return;
                }
                state.client_grid.items = state.client_grid.items.filter(function (it) { return it.uid !== editingUid; });
                editingUid = null;
                if (modal) {
                    modal.hide();
                }
                renderMatrixItems();
                renderPreview();
            });
        }
    }

    function renderClientGridPreview(showDoctor) {
        var cols = state.client_grid.columns;
        var rows = state.client_grid.rows;
        var items = state.client_grid.items.slice();
        var cellMap = {};
        items.forEach(function (item) {
            if (item.element_type === 'medico' && !showDoctor) {
                return;
            }
            var key = item.row + ',' + item.col;
            cellMap[key] = item;
        });
        var html = '<table class="pair-table"><tbody>';
        for (var r = 0; r < rows; r++) {
            html += '<tr>';
            var c = 0;
            while (c < cols) {
                var item = cellMap[r + ',' + c];
                if (!item) {
                    html += '<td></td>';
                    c++;
                    continue;
                }
                var span = item.col_span || 1;
                var label = item.show_label !== false ? (item.custom_label || defaultLabel(item.element_type)) : '';
                var sample = fieldSamples[item.element_type] || '—';
                if (item.element_type === 'medico' && !showDoctor) {
                    c += span;
                    continue;
                }
                html += '<td' + (span > 1 ? ' colspan="' + span + '"' : '') + '>';
                if (label) {
                    html += '<span class="pair-k" style="' + cssForStyle(state.styles.pair_label) + '">' + escapeHtml(label) + ' </span>';
                }
                html += '<span class="pair-v" style="' + cssForStyle(state.styles.pair_value) + '">' + escapeHtml(sample) + '</span>';
                html += '</td>';
                c += span;
            }
            html += '</tr>';
        }
        html += '</tbody></table>';
        return html;
    }

    function renderPreview() {
        readStylesFromMatrix();
        applyStylePreviewSamples();
        var el = document.getElementById('comp_receipt_preview');
        if (!el) {
            return;
        }
        var colors = getColors();
        var tagline = (document.getElementById('comprobante_tagline') || {}).value || 'Constancia de pago';
        var footer = (document.getElementById('comprobante_footer_note') || {}).value || '';
        var showDoctor = document.getElementById('comprobante_show_doctor') ? document.getElementById('comprobante_show_doctor').checked : true;
        var st = state.styles;

        el.innerHTML =
            '<div class="accent-bar" style="background:' + colors.primary + ';"></div>' +
            '<table class="doc-header"><tr>' +
            '<td><p class="brand-name" style="' + cssForStyle(st.brand_name, { color: colors.text }) + ';margin:0 0 4px;">' + escapeHtml(companyName) + '</p>' +
            '<p class="brand-tagline" style="' + cssForStyle(st.brand_tagline) + ';margin:0;">' + escapeHtml(tagline) + '</p></td>' +
            '<td class="receipt-badge"><div class="receipt-badge-inner" style="border:2px solid ' + colors.primary + ';">' +
            '<p class="receipt-badge-title" style="' + cssForStyle(st.receipt_badge_title, { color: colors.primary }) + ';margin:0 0 4px;">Recibo de pago</p>' +
            '<p class="receipt-badge-orden" style="' + cssForStyle(st.receipt_badge_orden, { color: colors.secondary }) + ';margin:0;">N.º 2026-0042</p>' +
            '<p class="receipt-badge-fecha" style="' + cssForStyle(st.receipt_badge_fecha) + ';margin:4px 0 0;">31/05/2026 10:30</p>' +
            '</div></td></tr></table>' +
            '<p class="section-title" style="' + cssForStyle(st.section_title, { color: colors.primary }) + ';">Cliente y atención</p>' +
            '<div class="panel">' + renderClientGridPreview(showDoctor) + '</div>' +
            '<p class="section-title" style="' + cssForStyle(st.section_title, { color: colors.primary }) + ';">Detalle de conceptos</p>' +
            '<table class="tbl-items"><thead><tr>' +
            '<th style="' + cssForStyle(st.items_header, { color: '#fff' }) + ';background:' + colors.secondary + ';">Descripción</th>' +
            '<th style="' + cssForStyle(st.items_header, { color: '#fff' }) + ';background:' + colors.secondary + ';">Importe ($)</th>' +
            '</tr></thead><tbody>' +
            '<tr><td style="' + cssForStyle(st.items_body) + '">Hemograma completo</td><td class="num" style="' + cssForStyle(st.items_num, { color: colors.text }) + '">85,00</td></tr>' +
            '<tr><td style="' + cssForStyle(st.items_body) + '">Glucosa en ayunas</td><td class="num" style="' + cssForStyle(st.items_num, { color: colors.text }) + '">45,00</td></tr>' +
            '</tbody></table>' +
            '<div class="totals-box"><table>' +
            '<tr><td class="t-lbl" style="' + cssForStyle(st.totals_label) + ';">Total orden</td><td class="t-val" style="' + cssForStyle(st.totals_value) + ';text-align:right;">$ 130,00</td></tr>' +
            '<tr><td class="t-lbl" style="' + cssForStyle(st.totals_label) + ';">Monto pagado</td><td class="t-val" style="' + cssForStyle(st.totals_value) + ';text-align:right;">$ 130,00</td></tr>' +
            '<tr class="total-final"><td class="t-lbl" style="' + cssForStyle(st.totals_final, { color: '#fff' }) + ';background:' + colors.primary + ';">Saldo</td>' +
            '<td class="t-val" style="' + cssForStyle(st.totals_final, { color: '#fff' }) + ';background:' + colors.primary + ';text-align:right;">$ 0,00</td></tr>' +
            '</table></div>' +
            '<div class="pay-method" style="' + cssForStyle(st.pay_method) + ';"><strong>Forma de pago:</strong> Efectivo</div>' +
            '<div class="foot" style="' + cssForStyle(st.footer) + ';">' + escapeHtml(footer) + '</div>';

        el.style.cssText = cssForStyle(st.body, { color: colors.text }) + ';line-height:1.45;';
    }

    function syncHiddenJson() {
        readStylesFromMatrix();
        var hidden = document.getElementById('comprobante_layout_json');
        if (hidden) {
            hidden.value = JSON.stringify(state);
        }
    }

    function bindForm() {
        var form = document.getElementById('form_comprobante_style');
        if (form) {
            form.addEventListener('submit', function () {
                syncHiddenJson();
            });
        }
        document.querySelectorAll('.comp-style-input, .comp-color-sync, .comp-preview-sync').forEach(function (el) {
            el.addEventListener('input', renderPreview);
            el.addEventListener('change', renderPreview);
        });
        var colsIn = document.getElementById('comp_grid_cols');
        var rowsIn = document.getElementById('comp_grid_rows');
        if (colsIn) {
            colsIn.addEventListener('change', function () { rebuildMatrixTable(); renderPreview(); });
        }
        if (rowsIn) {
            rowsIn.addEventListener('change', function () { rebuildMatrixTable(); renderPreview(); });
        }
    }

    function init() {
        if (!document.getElementById('tab-comprobante')) {
            return;
        }
        bindPalette();
        bindMatrixCells();
        bindModal();
        bindForm();
        renderMatrixItems();
        renderPreview();
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }

    window.ComprobanteEditor = {
        getState: function () { return state; },
        renderPreview: renderPreview
    };
})();
