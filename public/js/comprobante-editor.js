/**
 * Editor de matriz y estilos para comprobante de pago (Config → Estilo comprobante).
 */
(function () {
    'use strict';

    var DRAG_MIME = 'application/x-comprobante-field';

    var bootEl = document.getElementById('comprobante-editor-boot-json');
    var boot = {};
    try {
        boot = bootEl ? JSON.parse(bootEl.textContent || '{}') : (window.COMPROBANTE_EDITOR_BOOT || {});
    } catch (e) {
        console.error('Comprobante editor boot JSON:', e);
    }

    var fieldLabels = boot.fieldLabels || {};
    var fieldSamples = boot.fieldSamples || {};
    var matrixElements = boot.matrixElements || {};
    var matrixLabels = boot.matrixLabels || {};
    var matrixSamples = boot.matrixSamples || {};
    var styleLabels = boot.styleLabels || {};
    var companyName = boot.companyName || 'Laboratorio';
    var defaultStyles = boot.defaultStyles || {};
    var defaultDimensions = boot.defaultDimensions || {};
    var defaultSectionSpacing = boot.defaultSectionSpacing || {};
    var sectionStyleKeys = boot.sectionStyleKeys || {};
    var matrixSections = boot.matrixSections || {};

    var MATRIX_FALLBACKS = {
        receipt_badge_title: 'Recibo de pago',
        section_client: 'Cliente y atención',
        section_items: 'Detalle de conceptos',
        items_header_desc: 'Descripción',
        items_header_amount: 'Importe ($)',
        total_orden: 'Total orden',
        total_pagado: 'Monto pagado',
        total_saldo: 'Saldo',
        pay_method: 'Forma de pago:'
    };
    var sectionIds = Object.keys(matrixSections);
    if (sectionIds.length === 0) {
        sectionIds = ['header', 'client', 'table', 'totals', 'footer'];
    }

    var state = normalizeLayout(boot.layout || {});
    var dragPayload = null;
    var activeDropCell = null;
    var editingSectionId = null;
    var editingUid = null;
    var modalEl = document.getElementById('comp_field_modal');
    var modal = modalEl && window.bootstrap ? new bootstrap.Modal(modalEl) : null;

    function uid() {
        return 'c' + Date.now().toString(36) + Math.random().toString(36).slice(2, 8);
    }

    function clamp(n, min, max) {
        return Math.max(min, Math.min(max, n));
    }

    function elementMeta(type) {
        return matrixElements[type] || null;
    }

    function isClientField(type) {
        var m = elementMeta(type);
        return m ? !!m.is_client_field : !!fieldLabels[type];
    }

    function defaultLabel(type) {
        var m = elementMeta(type);
        if (m && m.default_label) {
            return m.default_label;
        }
        var defs = {
            paciente_nombre: 'Paciente:',
            paciente_ci: 'Documento de identidad:',
            paciente_telefono: 'Teléfono:',
            medico: 'Médico referente:',
            institucion: 'Institución / procedencia:'
        };
        return defs[type] || '';
    }

    function defaultAlignH(type) {
        var m = elementMeta(type);
        return (m && m.default_align_h) || 'left';
    }

    function defaultAlignV(type) {
        var m = elementMeta(type);
        return (m && m.default_align_v) || 'top';
    }

    function resolveItemAlignH(item) {
        var h = item.align_h;
        if (h === 'left' || h === 'center' || h === 'right') {
            return h;
        }
        return defaultAlignH(item.element_type);
    }

    function resolveItemAlignV(item) {
        var v = item.align_v;
        if (v === 'top' || v === 'middle' || v === 'bottom') {
            return v;
        }
        return defaultAlignV(item.element_type);
    }

    function matrixCellAlignStyle(item) {
        var h = resolveItemAlignH(item);
        var v = resolveItemAlignV(item);
        var textAlign = h === 'right' ? 'right' : h === 'center' ? 'center' : 'left';
        var vertAlign = v === 'bottom' ? 'bottom' : v === 'middle' ? 'middle' : 'top';
        return 'text-align:' + textAlign + ';vertical-align:' + vertAlign + ';';
    }

    function wrapMatrixCellContent(html, item) {
        if (!html) {
            return '';
        }
        var h = resolveItemAlignH(item);
        var textAlign = h === 'right' ? 'right' : h === 'center' ? 'center' : 'left';
        return '<div class="comp-matrix-cell-inner" style="width:100%;text-align:' + textAlign + ';">' + html + '</div>';
    }

    function alignHintLabel(item) {
        var h = resolveItemAlignH(item);
        var v = resolveItemAlignV(item);
        if (h === defaultAlignH(item.element_type) && v === defaultAlignV(item.element_type)) {
            return '';
        }
        var hShort = h === 'right' ? 'der' : h === 'center' ? 'cent' : 'izq';
        var vShort = v === 'bottom' ? 'abj' : v === 'middle' ? 'cent-v' : 'arr';
        return ' · ↔ ' + hShort + ' · ↕ ' + vShort;
    }

    function canDeleteType(type) {
        var m = elementMeta(type);
        return m ? m.can_delete !== false : true;
    }

    function sectionIdForType(type) {
        var m = elementMeta(type);
        if (m && m.section) {
            return m.section;
        }
        if (m && m.palette_group) {
            return m.palette_group === 'other' ? 'footer' : m.palette_group;
        }
        return '';
    }

    function getSectionDef(sectionId) {
        return matrixSections[sectionId] || { row_min: 1, row_max: 8, col_min: 1, col_max: 4, default_columns: 2, default_rows: 2 };
    }

    function getSectionGrid(sectionId) {
        if (!state.section_grids[sectionId]) {
            state.section_grids[sectionId] = { columns: 2, rows: 2, items: [] };
        }
        return state.section_grids[sectionId];
    }

    function defaultSectionGridsFromBoot() {
        if (boot.layout && boot.layout.section_grids) {
            return JSON.parse(JSON.stringify(boot.layout.section_grids));
        }
        return {};
    }

    function normalizeOneSectionGrid(sectionId, raw, fallback) {
        var def = getSectionDef(sectionId);
        var cols = clamp(parseInt(raw && raw.columns, 10) || fallback.columns || def.default_columns || 2, def.col_min || 1, def.col_max || 4);
        var rows = clamp(parseInt(raw && raw.rows, 10) || fallback.rows || def.default_rows || 2, def.row_min || 1, def.row_max || 8);
        var items = [];
        if (raw && Array.isArray(raw.items)) {
            raw.items.forEach(function (it) {
                var norm = normalizeDocumentItem(it, cols, rows);
                if (norm) {
                    items.push(norm);
                }
            });
        }
        if (!items.length && fallback && fallback.items) {
            items = JSON.parse(JSON.stringify(fallback.items));
        }
        return { columns: cols, rows: rows, items: items };
    }

    function normalizeLayout(raw) {
        var defaults = defaultSectionGridsFromBoot();
        var sectionGrids = {};
        sectionIds.forEach(function (sectionId) {
            var fb = defaults[sectionId] || { columns: 2, rows: 2, items: [] };
            var rawSec = raw && raw.section_grids ? raw.section_grids[sectionId] : null;
            sectionGrids[sectionId] = normalizeOneSectionGrid(sectionId, rawSec, fb);
        });
        if (!raw || typeof raw !== 'object') {
            var emptyStyles = normalizeAllStyles({});
            return {
                version: 2,
                styles: emptyStyles,
                section_styles: normalizeAllSectionStyles({}, emptyStyles),
                dimensions: normalizeDimensions({}),
                section_grids: sectionGrids,
                section_spacing: normalizeSectionSpacing({})
            };
        }
        if (!raw.section_grids && raw.document_grid && raw.document_grid.items) {
            sectionGrids = splitDocumentIntoSections(raw.document_grid, defaults);
        }
        var globalStyles = normalizeAllStyles(raw.styles || {});
        return {
            version: 2,
            styles: globalStyles,
            section_styles: normalizeAllSectionStyles(raw.section_styles || {}, globalStyles),
            dimensions: normalizeDimensions(raw.dimensions),
            section_grids: sectionGrids,
            section_spacing: normalizeSectionSpacing(raw.section_spacing)
        };
    }

    function splitDocumentIntoSections(documentGrid, defaults) {
        var sections = {};
        sectionIds.forEach(function (sid) {
            sections[sid] = JSON.parse(JSON.stringify(defaults[sid] || { columns: 2, rows: 2, items: [] }));
            sections[sid].items = [];
        });
        (documentGrid.items || []).forEach(function (it) {
            var sid = sectionIdForType(it.element_type);
            if (!sid || !sections[sid]) {
                return;
            }
            sections[sid].items.push(it);
        });
        sectionIds.forEach(function (sid) {
            if (!sections[sid].items.length && defaults[sid]) {
                sections[sid] = normalizeOneSectionGrid(sid, defaults[sid], defaults[sid]);
            } else {
                sections[sid] = normalizeOneSectionGrid(sid, sections[sid], defaults[sid] || { columns: 2, rows: 2, items: [] });
            }
        });
        return sections;
    }

    function normalizeAllStyles(raw) {
        var styles = {};
        Object.keys(styleLabels).forEach(function (key) {
            styles[key] = normalizeStyleBlock(raw[key], defaultStyles[key] || defaultStyles.body);
        });
        return styles;
    }

    function normalizeAllSectionStyles(raw, globalStyles) {
        var out = {};
        sectionIds.forEach(function (sectionId) {
            var keys = sectionStyleKeys[sectionId] || [];
            var secRaw = raw[sectionId] || {};
            out[sectionId] = {};
            keys.forEach(function (key) {
                out[sectionId][key] = normalizeStyleBlock(
                    secRaw[key] || globalStyles[key],
                    defaultStyles[key] || defaultStyles.body
                );
            });
        });
        return out;
    }

    function styleForSection(sectionId, styleKey) {
        if (!sectionId || !styleKey) {
            return defaultStyles.body;
        }
        var sec = state.section_styles && state.section_styles[sectionId];
        if (sec && sec[styleKey]) {
            return sec[styleKey];
        }
        return state.styles[styleKey] || defaultStyles[styleKey] || defaultStyles.body;
    }

    function normalizeDimensions(raw) {
        raw = raw || {};
        var out = {};
        Object.keys(defaultDimensions).forEach(function (key) {
            if (key.indexOf('_color') !== -1) {
                var allowEmpty = key === 'header_lab_bg_color' || key === 'items_header_bg_color';
                var c = String(raw[key] !== undefined ? raw[key] : (defaultDimensions[key] || '')).toUpperCase();
                if (allowEmpty && (c === '' || c === 'TRANSPARENT' || c === 'NONE')) {
                    out[key] = '';
                    return;
                }
                if (!/^#[0-9A-F]{6}$/.test(c)) {
                    c = String(defaultDimensions[key] || '#CBD5E1').toUpperCase();
                }
                out[key] = c;
                return;
            }
            var val = parseFloat(raw[key]);
            if (isNaN(val)) {
                val = parseFloat(defaultDimensions[key]) || 0;
            }
            if (key.indexOf('_pct') !== -1) {
                val = clamp(val, 20, 80);
            } else if (key === 'items_cell_padding_pt' || key === 'receipt_badge_padding_pt') {
                val = clamp(val, 2, 20);
            } else if (key === 'receipt_badge_border_width_pt') {
                val = clamp(val, 0, 6);
            } else if (key === 'receipt_badge_border_radius_pt') {
                val = clamp(val, 0, 16);
            } else if (key === 'totals_box_width_pt') {
                val = clamp(val, 160, 420);
            }
            out[key] = val;
        });
        return out;
    }

    function receiptBadgeBoxStyle(dim, colors) {
        dim = dim || {};
        colors = colors || {};
        var borderColor = dim.receipt_badge_border_color || colors.primary || '#0F766E';
        var bg = dim.receipt_badge_bg_color;
        if (bg === undefined || bg === null || bg === '') {
            bg = (defaultDimensions.receipt_badge_bg_color || '#F0FDFA');
        }
        var borderW = dim.receipt_badge_border_width_pt !== undefined ? dim.receipt_badge_border_width_pt : 2;
        var radius = dim.receipt_badge_border_radius_pt !== undefined ? dim.receipt_badge_border_radius_pt : 6;
        var pad = dim.receipt_badge_padding_pt !== undefined ? dim.receipt_badge_padding_pt : 10;
        var parts = [
            'display:inline-block',
            'text-align:right',
            'padding:' + pad + 'pt',
            'border-radius:' + radius + 'pt'
        ];
        if (borderW > 0) {
            parts.push('border:' + borderW + 'pt solid ' + borderColor);
        }
        if (bg) {
            parts.push('background:' + bg);
        }
        return parts.join(';');
    }

    function headerLabCellBgStyle(dim) {
        dim = dim || {};
        var bg = dim.header_lab_bg_color;
        return bg ? 'background:' + bg + ';' : '';
    }

    function itemsHeaderBgColor(dim, colors) {
        dim = dim || {};
        return dim.items_header_bg_color || colors.secondary || '#134e4a';
    }

    function normalizeSectionSpacing(raw) {
        raw = raw || {};
        var out = {};
        var sepStyles = ['line', 'dashed', 'dotted', 'double', 'space'];
        sectionIds.forEach(function (sectionId) {
            var def = defaultSectionSpacing[sectionId] || {};
            var sec = raw[sectionId] || {};
            var rowGap = parseFloat(sec.row_gap_pt);
            var marginBottom = parseFloat(sec.margin_bottom_pt);
            if (isNaN(rowGap)) {
                rowGap = parseFloat(def.row_gap_pt) || 4;
            }
            if (isNaN(marginBottom)) {
                marginBottom = parseFloat(def.margin_bottom_pt) || 12;
            }
            var sepStyle = String(sec.separator_style || def.separator_style || 'line').toLowerCase();
            if (sepStyles.indexOf(sepStyle) === -1) {
                sepStyle = 'line';
            }
            var sepColor = String(sec.separator_color || def.separator_color || '#CBD5E1').toUpperCase();
            if (!/^#[0-9A-F]{6}$/.test(sepColor)) {
                sepColor = '#CBD5E1';
            }
            out[sectionId] = {
                row_gap_pt: clamp(rowGap, 0, 24),
                margin_bottom_pt: clamp(marginBottom, 0, 48),
                separator_enabled: sec.separator_enabled === true || sec.separator_enabled === 1 || sec.separator_enabled === '1',
                separator_style: sepStyle,
                separator_color: sepColor,
                separator_thickness_pt: clamp(parseFloat(sec.separator_thickness_pt), 0.5, 4) || parseFloat(def.separator_thickness_pt) || 1,
                separator_width_pct: clamp(parseFloat(sec.separator_width_pct), 20, 100) || parseFloat(def.separator_width_pct) || 100,
                separator_gap_pt: clamp(parseFloat(sec.separator_gap_pt), 0, 24) || (isNaN(parseFloat(sec.separator_gap_pt)) ? (parseFloat(def.separator_gap_pt) || 6) : 0)
            };
        });
        return out;
    }

    function getSectionSpacing(sectionId) {
        if (!state.section_spacing) {
            state.section_spacing = normalizeSectionSpacing({});
        }
        return state.section_spacing[sectionId] || { row_gap_pt: 4, margin_bottom_pt: 12 };
    }

    function readSectionSpacingFromUi() {
        if (!state.section_spacing) {
            state.section_spacing = normalizeSectionSpacing({});
        }
        document.querySelectorAll('.comp-section-spacing').forEach(function (inp) {
            var sid = inp.getAttribute('data-section');
            var field = inp.getAttribute('data-spacing-field');
            if (!sid || !field) {
                return;
            }
            if (!state.section_spacing[sid]) {
                state.section_spacing[sid] = {};
            }
            if (inp.type === 'checkbox') {
                state.section_spacing[sid][field] = inp.checked;
            } else if (inp.tagName === 'SELECT') {
                state.section_spacing[sid][field] = inp.value;
            } else if (field === 'separator_color') {
                state.section_spacing[sid][field] = inp.value;
            } else {
                var val = parseFloat(inp.value);
                if (!isNaN(val)) {
                    state.section_spacing[sid][field] = val;
                }
            }
        });
        state.section_spacing = normalizeSectionSpacing(state.section_spacing);
    }

    function sectionTableLayoutStyle(sectionId) {
        var gap = Math.max(0, getSectionSpacing(sectionId).row_gap_pt);
        return 'width:100%;table-layout:fixed;border-collapse:separate;border-spacing:0 ' + gap + 'pt;';
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

    function normalizeDocumentItem(it, cols, rows) {
        if (!it || !it.element_type || !elementMeta(it.element_type)) {
            return null;
        }
        var row = clamp(parseInt(it.row, 10) || 0, 0, rows - 1);
        var col = clamp(parseInt(it.col, 10) || 0, 0, cols - 1);
        var normalized = {
            uid: it.uid || uid(),
            element_type: it.element_type,
            row: row,
            col: col,
            col_span: clamp(parseInt(it.col_span, 10) || 1, 1, cols - col),
            row_span: clamp(parseInt(it.row_span, 10) || 1, 1, rows - row),
            enabled: it.enabled !== false,
            show_label: it.show_label !== false,
            custom_label: String(it.custom_label != null ? it.custom_label : defaultLabel(it.element_type))
        };
        normalized.align_h = resolveItemAlignH(Object.assign({}, normalized, { align_h: it.align_h }));
        normalized.align_v = resolveItemAlignV(Object.assign({}, normalized, { align_v: it.align_v }));
        return normalized;
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

    function reciboNumeroPreview() {
        var chk = document.getElementById('comprobante_recibo_num_rango_activo');
        if (!chk || !chk.checked) {
            return boot.reciboNumeroPreview || '2026-0042';
        }
        var ini = parseInt((document.getElementById('comprobante_recibo_num_inicio') || {}).value, 10);
        var fin = parseInt((document.getElementById('comprobante_recibo_num_fin') || {}).value, 10);
        if (isNaN(ini) || isNaN(fin) || ini < 1 || fin < 1 || ini > fin) {
            return boot.reciboNumeroPreview || '2026-0042';
        }
        var width = Math.max(String(fin).length, String(ini).length);
        var sample = ini;
        var s = String(sample);
        while (s.length < width) {
            s = '0' + s;
        }
        return s;
    }

    function syncReciboRangoFieldsUi() {
        var chk = document.getElementById('comprobante_recibo_num_rango_activo');
        var wrap = document.getElementById('comp_recibo_rango_fields');
        if (!wrap || !chk) {
            return;
        }
        wrap.querySelectorAll('input[type="number"]').forEach(function (inp) {
            inp.disabled = !chk.checked;
        });
    }

    function matrixText(type, fallback) {
        if (fallback === undefined) {
            fallback = MATRIX_FALLBACKS[type] || '';
        }
        var item = findItemByType(type);
        if (!item) {
            return '';
        }
        if (item.enabled === false) {
            return '';
        }
        var custom = String(item.custom_label || '').trim();
        return custom !== '' ? custom : fallback;
    }

    function itemEnabled(type) {
        var item = findItemByType(type);
        return item ? item.enabled !== false : false;
    }

    function findItemByType(type) {
        var found = null;
        sectionIds.forEach(function (sid) {
            if (found) {
                return;
            }
            found = (getSectionGrid(sid).items || []).find(function (it) {
                return it.element_type === type;
            }) || null;
        });
        return found;
    }

    function itemAtCell(sectionId, row, col) {
        return (getSectionGrid(sectionId).items || []).find(function (it) {
            return it.row === row && it.col === col;
        }) || null;
    }

    function spanRangesOverlap(aStart, aEnd, bStart, bEnd) {
        return aStart <= bEnd && bStart <= aEnd;
    }

    function findSpanConflicts(grid, row, col, colSpan, rowSpan, exceptUid) {
        colSpan = colSpan || 1;
        rowSpan = rowSpan || 1;
        var colEnd = col + colSpan - 1;
        var rowEnd = row + rowSpan - 1;
        var conflicts = [];
        (grid.items || []).forEach(function (it) {
            if (exceptUid && it.uid === exceptUid) {
                return;
            }
            var itColSpan = it.col_span || 1;
            var itRowSpan = it.row_span || 1;
            var itColEnd = it.col + itColSpan - 1;
            var itRowEnd = it.row + itRowSpan - 1;
            if (spanRangesOverlap(col, colEnd, it.col, itColEnd) && spanRangesOverlap(row, rowEnd, it.row, itRowEnd)) {
                conflicts.push(it);
            }
        });
        return conflicts;
    }

    function validateItemSpan(grid, row, col, colSpan, rowSpan, exceptUid) {
        colSpan = colSpan || 1;
        rowSpan = rowSpan || 1;
        if (row + rowSpan > grid.rows) {
            return {
                ok: false,
                message: 'El alto de ' + rowSpan + ' fila(s) no cabe: desde F' + (row + 1) + ' solo hay ' + (grid.rows - row) + ' fila(s) disponible(s). Aumente las filas de la sección o reduzca el alto.'
            };
        }
        if (col + colSpan > grid.columns) {
            return {
                ok: false,
                message: 'El ancho de ' + colSpan + ' columna(s) no cabe: desde Col ' + (col + 1) + ' solo hay ' + (grid.columns - col) + ' columna(s) disponible(s).'
            };
        }
        var conflicts = findSpanConflicts(grid, row, col, colSpan, rowSpan, exceptUid);
        if (conflicts.length) {
            var names = conflicts.map(function (it) {
                return (matrixLabels[it.element_type] || it.element_type) + ' (F' + (it.row + 1) + ', Col ' + (it.col + 1) + ')';
            }).join('; ');
            return {
                ok: false,
                message: 'Ese tamaño ocupa celdas usadas por: ' + names + '. Mueva o quite esos elementos primero, o use menos filas/columnas de alto/ancho.'
            };
        }
        return { ok: true };
    }

    function showCompEditorAlert(message, type) {
        type = type || 'warning';
        var host = document.getElementById('comp-sections-editor');
        if (!host) {
            window.alert(message);
            return;
        }
        var box = document.getElementById('comp_editor_alert');
        if (!box) {
            box = document.createElement('div');
            box.id = 'comp_editor_alert';
            box.className = 'alert alert-warning alert-dismissible small py-2 mb-3';
            box.setAttribute('role', 'alert');
            box.innerHTML = '<span id="comp_editor_alert_text"></span><button type="button" class="btn-close btn-close-sm" data-bs-dismiss="alert"></button>';
            host.parentNode.insertBefore(box, host);
        }
        box.className = 'alert alert-' + type + ' alert-dismissible small py-2 mb-3';
        var textEl = document.getElementById('comp_editor_alert_text');
        if (textEl) {
            textEl.textContent = message;
        }
        box.style.display = '';
    }

    function removeOverlappingItems(grid, row, col, colSpan, rowSpan, exceptUid) {
        colSpan = colSpan || 1;
        rowSpan = rowSpan || 1;
        var colEnd = col + colSpan - 1;
        var rowEnd = row + rowSpan - 1;
        grid.items = (grid.items || []).filter(function (it) {
            if (exceptUid && it.uid === exceptUid) {
                return true;
            }
            var itColSpan = it.col_span || 1;
            var itRowSpan = it.row_span || 1;
            var itColEnd = it.col + itColSpan - 1;
            var itRowEnd = it.row + itRowSpan - 1;
            var rowOverlap = spanRangesOverlap(row, rowEnd, it.row, itRowEnd);
            var colOverlap = spanRangesOverlap(col, colEnd, it.col, itColEnd);
            return !(rowOverlap && colOverlap);
        });
    }

    function matrixCellAt(sectionId, row, col) {
        var items = getSectionGrid(sectionId).items || [];
        for (var i = 0; i < items.length; i++) {
            var it = items[i];
            if (it.enabled === false) {
                continue;
            }
            var cs = it.col_span || 1;
            var rs = it.row_span || 1;
            var r0 = it.row;
            var c0 = it.col;
            if (row >= r0 && row < r0 + rs && col >= c0 && col < c0 + cs) {
                if (row === r0 && col === c0) {
                    return { item: it, covered: false };
                }
                return { item: it, covered: true };
            }
        }
        return { item: null, covered: false };
    }

    function matrixSpanAttrs(item, col, cols, row, rows) {
        var colSpan = item ? clamp(item.col_span || 1, 1, cols - col) : 1;
        var rowSpan = item ? clamp(item.row_span || 1, 1, rows - row) : 1;
        var attr = '';
        if (colSpan > 1) {
            attr += ' colspan="' + colSpan + '"';
        }
        if (rowSpan > 1) {
            attr += ' rowspan="' + rowSpan + '"';
        }
        return { colSpan: colSpan, rowSpan: rowSpan, attr: attr };
    }

    function isReceiptBadgeType(type) {
        return type === 'receipt_badge_title' || type === 'receipt_badge_orden' || type === 'receipt_badge_fecha';
    }

    function shouldWrapReceiptBadge(item, col, cols) {
        return isReceiptBadgeType(item.element_type) && cols >= 2 && col === cols - 1;
    }

    function headerColWidthPct(col, span, cols, dim) {
        span = span || 1;
        if (cols === 2) {
            if (span >= 2) {
                return 100;
            }
            return col === 0 ? dim.lab : dim.receipt;
        }
        return (100 / cols) * span;
    }

    function findItemByUid(sectionId, itemUid) {
        return (getSectionGrid(sectionId).items || []).find(function (it) {
            return it.uid === itemUid;
        }) || null;
    }

    function setDragPayload(payload) {
        dragPayload = payload;
        try {
            sessionStorage.setItem('comprobante_drag_payload', JSON.stringify(payload));
        } catch (e) {
            // ignorar
        }
        return JSON.stringify(payload);
    }

    function readDragPayload(dataTransfer) {
        var raw = null;
        if (dataTransfer) {
            try {
                raw = dataTransfer.getData(DRAG_MIME);
                if (!raw) {
                    raw = dataTransfer.getData('text/plain');
                }
            } catch (e) {
                raw = null;
            }
        }
        if (raw) {
            try {
                return JSON.parse(raw);
            } catch (e) {
                // seguir
            }
        }
        if (dragPayload) {
            return dragPayload;
        }
        try {
            var stored = sessionStorage.getItem('comprobante_drag_payload');
            if (stored) {
                return JSON.parse(stored);
            }
        } catch (e) {
            // ignorar
        }
        return null;
    }

    function clearDragPayload() {
        dragPayload = null;
        activeDropCell = null;
        try {
            sessionStorage.removeItem('comprobante_drag_payload');
        } catch (e) {
            // ignorar
        }
        document.querySelectorAll('.comp-matrix-cell.comp-drop-target').forEach(function (c) {
            c.classList.remove('comp-drop-target');
        });
        document.querySelectorAll('.comp-palette-item.comp-dragging, .comp-matrix-item.comp-dragging').forEach(function (c) {
            c.classList.remove('comp-dragging');
        });
    }

    function readDimensionsFromMatrix() {
        document.querySelectorAll('.comp-dimension-input').forEach(function (inp) {
            var key = inp.getAttribute('data-dimension-key');
            if (!key) {
                return;
            }
            if (inp.type === 'color') {
                var clearEl = document.querySelector('.comp-dimension-color-clear[data-dimension-key="' + key + '"]:checked');
                state.dimensions[key] = clearEl ? '' : inp.value;
                return;
            }
            var val = parseFloat(inp.value);
            if (!isNaN(val)) {
                state.dimensions[key] = val;
            }
        });
        state.dimensions = normalizeDimensions(state.dimensions);
    }

    function readStylesFromMatrix() {
        if (!state.section_styles) {
            state.section_styles = normalizeAllSectionStyles({}, state.styles);
        }
        document.querySelectorAll('.comp-style-matrix [data-style-key].comp-style-block').forEach(function (block) {
            var key = block.getAttribute('data-style-key');
            var sectionId = block.getAttribute('data-section');
            if (!key || !sectionId) {
                return;
            }
            var styleBlock = {};
            block.querySelectorAll('.comp-style-input').forEach(function (inp) {
                var field = inp.getAttribute('data-style-field');
                if (!field) {
                    return;
                }
                styleBlock[field] = inp.type === 'number' ? parseFloat(inp.value) : inp.value;
            });
            if (!state.section_styles[sectionId]) {
                state.section_styles[sectionId] = {};
            }
            state.section_styles[sectionId][key] = normalizeStyleBlock(styleBlock, defaultStyles[key]);
        });
    }

    function applyStylePreviewSamples() {
        document.querySelectorAll('.comp-style-preview-sample').forEach(function (el) {
            var key = el.getAttribute('data-style-key');
            var block = el.closest('.comp-style-block');
            var sectionId = block ? block.getAttribute('data-section') : '';
            var st = styleForSection(sectionId, key);
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
        var styleKey = merged.style_key;
        if (styleKey && state.styles[styleKey]) {
            merged = Object.assign({}, state.styles[styleKey], merged);
        }
        return [
            'font-family:"' + (merged.font_family || 'DejaVu Sans') + '",Arial,sans-serif',
            'font-size:' + (merged.font_size_pt || 10) + 'pt',
            'font-weight:' + (merged.font_weight || 'normal'),
            'font-style:' + (merged.font_style || 'normal'),
            'color:' + (merged.color || '#1E293B'),
            'text-transform:' + (merged.text_transform || 'none')
        ].join(';');
    }

    function styleForElementType(type) {
        var m = elementMeta(type);
        var key = m && m.style_key ? m.style_key : 'body';
        return state.styles[key] || defaultStyles[key] || defaultStyles.body;
    }

    function displayName(item) {
        var custom = String(item.custom_label || '').trim();
        if (custom) {
            return custom;
        }
        return matrixLabels[item.element_type] || fieldLabels[item.element_type] || item.element_type;
    }

    function updateSectionBadge(sectionId) {
        var grid = getSectionGrid(sectionId);
        document.querySelectorAll('.comp-section-dims-badge[data-section="' + sectionId + '"]').forEach(function (badge) {
            badge.textContent = grid.columns + ' columnas × ' + grid.rows + ' filas';
        });
    }

    function rebuildSectionMatrix(sectionId) {
        var def = getSectionDef(sectionId);
        var colsIn = document.getElementById('comp_sec_' + sectionId + '_cols');
        var rowsIn = document.getElementById('comp_sec_' + sectionId + '_rows');
        var grid = getSectionGrid(sectionId);
        grid.columns = clamp(parseInt(colsIn && colsIn.value, 10) || grid.columns, def.col_min || 1, def.col_max || 4);
        grid.rows = clamp(parseInt(rowsIn && rowsIn.value, 10) || grid.rows, def.row_min || 1, def.row_max || 8);
        grid.items = (grid.items || [])
            .map(function (it) { return normalizeDocumentItem(it, grid.columns, grid.rows); })
            .filter(Boolean);

        var table = document.getElementById('comp_matrix_' + sectionId);
        if (!table) {
            return;
        }
        var cols = grid.columns;
        var rows = grid.rows;
        var thead = '<tr><th class="comp-matrix-corner"></th>';
        for (var c = 0; c < cols; c++) {
            thead += '<th class="comp-matrix-col-th text-center small">Col ' + (c + 1) + '</th>';
        }
        thead += '</tr>';
        table.querySelector('thead').innerHTML = thead;

        var tbody = '';
        for (var r = 0; r < rows; r++) {
            tbody += '<tr><th class="comp-matrix-row-th text-center small">F' + (r + 1) + '</th>';
            var c = 0;
            while (c < cols) {
                var cell = matrixCellAt(sectionId, r, c);
                if (cell.covered) {
                    c++;
                    continue;
                }
                var anchor = cell.item;
                var spans = matrixSpanAttrs(anchor, c, cols, r, rows);
                tbody += '<td class="comp-matrix-cell" data-section="' + sectionId + '" data-row="' + r + '" data-col="' + c + '" tabindex="0"' + spans.attr + '></td>';
                c += spans.colSpan;
            }
            tbody += '</tr>';
        }
        table.querySelector('tbody').innerHTML = tbody;
        updateSectionBadge(sectionId);
        renderSectionMatrixItems(sectionId);
    }

    function renderSectionMatrixItems(sectionId) {
        document.querySelectorAll('#comp_matrix_' + sectionId + ' .comp-matrix-cell').forEach(function (cell) {
            cell.innerHTML = '';
            cell.classList.remove('comp-has-item', 'comp-drop-target', 'comp-cell-disabled');
            var row = parseInt(cell.getAttribute('data-row'), 10);
            var col = parseInt(cell.getAttribute('data-col'), 10);
            var item = itemAtCell(sectionId, row, col);
            if (!item) {
                return;
            }
            cell.classList.add('comp-has-item');
            if (item.enabled === false) {
                cell.classList.add('comp-cell-disabled');
            }
            var label = displayName(item);
            var typeLabel = matrixLabels[item.element_type] || item.element_type;
            var sample = matrixSamples[item.element_type] || fieldSamples[item.element_type] || '';
            var colSpanVal = item.col_span || 1;
            var rowSpanVal = item.row_span || 1;
            var spanNote = '';
            if (colSpanVal > 1) {
                spanNote += ' · ' + colSpanVal + ' col';
            }
            if (rowSpanVal > 1) {
                spanNote += ' · ' + rowSpanVal + ' filas';
            }
            spanNote += alignHintLabel(item);
            cell.innerHTML =
                '<div class="comp-matrix-item" draggable="true" data-section="' + escapeHtml(sectionId) + '" data-uid="' + escapeHtml(item.uid) + '"' + (colSpanVal > 1 || rowSpanVal > 1 ? ' style="min-height:100%;width:100%;"' : '') + '>' +
                '<div class="comp-matrix-item__type">' + escapeHtml(label) + '</div>' +
                '<div class="comp-matrix-item__code">' + escapeHtml(typeLabel) + spanNote + '</div>' +
                (sample ? '<div class="comp-matrix-item__sample">' + escapeHtml(sample) + '</div>' : '') +
                (item.enabled === false ? '<div class="comp-matrix-item__off">Oculto</div>' : '') +
                '</div>';
        });
        document.querySelectorAll('#comp_matrix_' + sectionId + ' .comp-matrix-item').forEach(function (chip) {
            chip.addEventListener('dragstart', onChipDragStart);
            chip.addEventListener('dragend', onChipDragEnd);
        });
    }

    function renderAllSectionMatrices() {
        sectionIds.forEach(function (sid) {
            rebuildSectionMatrix(sid);
        });
    }

    function escapeHtml(s) {
        return String(s)
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;');
    }

    function onPaletteDragStart(e) {
        var chip = e.target.closest('.comp-palette-item');
        if (!chip || !e.dataTransfer) {
            return;
        }
        var t = chip.getAttribute('data-element-type');
        var sec = chip.getAttribute('data-section');
        if (!t) {
            return;
        }
        var json = setDragPayload({ source: 'palette', element_type: t, section: sec });
        e.dataTransfer.setData(DRAG_MIME, json);
        e.dataTransfer.setData('text/plain', json);
        e.dataTransfer.effectAllowed = 'copy';
        chip.classList.add('comp-dragging');
    }

    function onPaletteDragEnd() {
        clearDragPayload();
    }

    function onChipDragStart(e) {
        var chip = e.target.closest('.comp-matrix-item');
        if (!chip || !e.dataTransfer) {
            return;
        }
        var itemUid = chip.getAttribute('data-uid');
        var json = setDragPayload({ source: 'chip', uid: itemUid, section: chip.getAttribute('data-section') });
        e.dataTransfer.setData(DRAG_MIME, json);
        e.dataTransfer.setData('text/plain', json);
        e.dataTransfer.effectAllowed = 'move';
        chip.classList.add('comp-dragging');
    }

    function onChipDragEnd() {
        clearDragPayload();
    }

    function bindSectionsMatrixDnD() {
        var wrap = document.getElementById('comp-sections-editor');
        if (!wrap || wrap.getAttribute('data-dnd-bound') === '1') {
            return;
        }
        wrap.setAttribute('data-dnd-bound', '1');

        wrap.addEventListener('dragover', function (e) {
            var cell = e.target.closest('.comp-matrix-cell');
            if (!cell || !wrap.contains(cell)) {
                return;
            }
            e.preventDefault();
            e.stopPropagation();
            if (e.dataTransfer) {
                e.dataTransfer.dropEffect = (dragPayload && dragPayload.source === 'chip') ? 'move' : 'copy';
            }
            if (activeDropCell && activeDropCell !== cell) {
                activeDropCell.classList.remove('comp-drop-target');
            }
            activeDropCell = cell;
            cell.classList.add('comp-drop-target');
        });

        wrap.addEventListener('dragleave', function (e) {
            var cell = e.target.closest('.comp-matrix-cell');
            if (!cell) {
                return;
            }
            var related = e.relatedTarget;
            if (related && cell.contains(related)) {
                return;
            }
            cell.classList.remove('comp-drop-target');
            if (activeDropCell === cell) {
                activeDropCell = null;
            }
        });

        wrap.addEventListener('drop', function (e) {
            var cell = e.target.closest('.comp-matrix-cell');
            if (!cell || !wrap.contains(cell)) {
                return;
            }
            e.preventDefault();
            e.stopPropagation();
            handleCellDrop(cell, e.dataTransfer);
        });

        wrap.addEventListener('click', function (e) {
            var chip = e.target.closest('.comp-matrix-item');
            if (chip) {
                openItemModal(chip.getAttribute('data-section'), chip.getAttribute('data-uid'));
            }
        });
    }

    function handleCellDrop(cell, dataTransfer) {
        clearDragPayload();
        var sectionId = cell.getAttribute('data-section');
        var row = parseInt(cell.getAttribute('data-row'), 10);
        var col = parseInt(cell.getAttribute('data-col'), 10);
        if (!sectionId || isNaN(row) || isNaN(col)) {
            return;
        }
        var raw = readDragPayload(dataTransfer);
        if (!raw || !raw.source) {
            return;
        }
        if (raw.source === 'palette' && raw.element_type) {
            addItemAt(sectionId, raw.element_type, row, col);
        } else if (raw.source === 'chip' && raw.uid && raw.section) {
            moveItem(raw.section, raw.uid, sectionId, row, col);
        }
        rebuildSectionMatrix(sectionId);
        renderPreview();
    }

    function addItemAt(sectionId, type, row, col) {
        if (!elementMeta(type)) {
            return;
        }
        var grid = getSectionGrid(sectionId);
        removeOverlappingItems(grid, row, col, 1, 1, null);
        grid.items.push({
            uid: uid(),
            element_type: type,
            row: row,
            col: col,
            col_span: 1,
            row_span: 1,
            enabled: true,
            show_label: isClientField(type),
            custom_label: defaultLabel(type),
            align_h: defaultAlignH(type),
            align_v: defaultAlignV(type)
        });
    }

    function moveItem(fromSection, itemUid, toSection, row, col) {
        var fromGrid = getSectionGrid(fromSection);
        var item = fromGrid.items.find(function (it) { return it.uid === itemUid; });
        if (!item) {
            return;
        }
        fromGrid.items = fromGrid.items.filter(function (it) {
            return it.uid !== itemUid;
        });
        var toGrid = getSectionGrid(toSection);
        var colSpan = clamp(item.col_span || 1, 1, toGrid.columns - col);
        var rowSpan = clamp(item.row_span || 1, 1, toGrid.rows - row);
        removeOverlappingItems(toGrid, row, col, colSpan, rowSpan, item.uid);
        item.row = row;
        item.col = col;
        item.col_span = colSpan;
        item.row_span = rowSpan;
        toGrid.items.push(item);
        if (fromSection !== toSection) {
            rebuildSectionMatrix(fromSection);
        }
    }

    function bindPalette() {
        document.querySelectorAll('.comp-palette-item').forEach(function (el) {
            if (el.getAttribute('data-dnd-bound') === '1') {
                return;
            }
            el.setAttribute('data-dnd-bound', '1');
            el.addEventListener('dragstart', onPaletteDragStart);
            el.addEventListener('dragend', onPaletteDragEnd);
        });
    }

    function openItemModal(sectionId, itemUid) {
        var item = findItemByUid(sectionId, itemUid);
        if (!item || !modal) {
            return;
        }
        editingSectionId = sectionId;
        editingUid = itemUid;
        var typeLabel = matrixLabels[item.element_type] || item.element_type;
        document.getElementById('comp_modal_type_label').textContent = typeLabel;
        document.getElementById('comp_modal_type_code').textContent = item.element_type;
        document.getElementById('comp_modal_label').value = item.custom_label || '';
        document.getElementById('comp_modal_enabled').checked = item.enabled !== false;
        document.getElementById('comp_modal_show_label').checked = item.show_label !== false;
        var gridModal = getSectionGrid(sectionId);
        document.getElementById('comp_modal_col_span').value = item.col_span || 1;
        document.getElementById('comp_modal_col_span').max = gridModal.columns - item.col;
        document.getElementById('comp_modal_row_span').value = item.row_span || 1;
        document.getElementById('comp_modal_row_span').max = gridModal.rows - item.row;
        var alignH = document.getElementById('comp_modal_align_h');
        var alignV = document.getElementById('comp_modal_align_v');
        if (alignH) {
            alignH.value = resolveItemAlignH(item);
        }
        if (alignV) {
            alignV.value = resolveItemAlignV(item);
        }
        var showLabelWrap = document.getElementById('comp_modal_show_label_wrap');
        if (showLabelWrap) {
            showLabelWrap.style.display = isClientField(item.element_type) ? '' : 'none';
        }
        var removeBtn = document.getElementById('comp_modal_remove');
        if (removeBtn) {
            removeBtn.style.display = '';
            removeBtn.disabled = false;
        }
        modal.show();
    }

    function bindModal() {
        var saveBtn = document.getElementById('comp_modal_save');
        var removeBtn = document.getElementById('comp_modal_remove');
        if (saveBtn) {
            saveBtn.addEventListener('click', function () {
                if (!editingUid || !editingSectionId) {
                    return;
                }
                var item = findItemByUid(editingSectionId, editingUid);
                if (!item) {
                    return;
                }
                var grid = getSectionGrid(editingSectionId);
                item.custom_label = document.getElementById('comp_modal_label').value;
                item.enabled = document.getElementById('comp_modal_enabled').checked;
                item.show_label = document.getElementById('comp_modal_show_label').checked;
                var newColSpan = clamp(parseInt(document.getElementById('comp_modal_col_span').value, 10) || 1, 1, grid.columns - item.col);
                var newRowSpan = clamp(parseInt(document.getElementById('comp_modal_row_span').value, 10) || 1, 1, grid.rows - item.row);
                var check = validateItemSpan(grid, item.row, item.col, newColSpan, newRowSpan, item.uid);
                if (!check.ok) {
                    showCompEditorAlert(check.message, 'warning');
                    document.getElementById('comp_modal_col_span').value = item.col_span || 1;
                    document.getElementById('comp_modal_row_span').value = item.row_span || 1;
                    return;
                }
                item.col_span = newColSpan;
                item.row_span = newRowSpan;
                var alignHEl = document.getElementById('comp_modal_align_h');
                var alignVEl = document.getElementById('comp_modal_align_v');
                if (alignHEl) {
                    item.align_h = alignHEl.value;
                }
                if (alignVEl) {
                    item.align_v = alignVEl.value;
                }
                removeOverlappingItems(grid, item.row, item.col, item.col_span, item.row_span, item.uid);
                rebuildSectionMatrix(editingSectionId);
                renderPreview();
                if (modal) {
                    modal.hide();
                }
            });
        }
        if (removeBtn) {
            removeBtn.addEventListener('click', function () {
                if (!editingUid || !editingSectionId) {
                    return;
                }
                var sidRemove = editingSectionId;
                getSectionGrid(sidRemove).items = (getSectionGrid(sidRemove).items || []).filter(function (it) {
                    return it.uid !== editingUid;
                });
                editingUid = null;
                editingSectionId = null;
                if (modal) {
                    modal.hide();
                }
                rebuildSectionMatrix(sidRemove);
                renderPreview();
            });
        }
    }

    function clientGridFromSection() {
        var grid = getSectionGrid('client');
        var items = (grid.items || []).filter(function (it) {
            return isClientField(it.element_type) && it.element_type !== 'section_client' && it.enabled !== false;
        });
        if (!items.length) {
            return { columns: 2, rows: 3, items: [] };
        }
        var minRow = Math.min.apply(null, items.map(function (it) { return it.row; }));
        var maxRow = 0;
        var maxCol = 0;
        var normalized = items.map(function (it) {
            var r = it.row - minRow;
            var cs = it.col_span || 1;
            var rs = it.row_span || 1;
            maxRow = Math.max(maxRow, r + rs - 1);
            maxCol = Math.max(maxCol, it.col + cs - 1);
            return Object.assign({}, it, { row: r });
        });
        return {
            columns: clamp(maxCol + 1, 1, 4),
            rows: clamp(maxRow + 1, 1, 8),
            items: normalized
        };
    }

    function renderClientGridPreview(showDoctor) {
        var grid = clientGridFromSection();
        var sp = getSectionSpacing('client');
        var halfGap = Math.max(0, sp.row_gap_pt) / 2;
        var cellPad = 'padding-top:' + halfGap + 'pt;padding-bottom:' + halfGap + 'pt;';
        var cols = grid.columns;
        var rows = grid.rows;
        var cellMap = {};
        grid.items.forEach(function (item) {
            if (item.element_type === 'medico' && !showDoctor) {
                return;
            }
            cellMap[item.row + ',' + item.col] = item;
        });
        var html = '<table class="pair-table"><tbody>';
        for (var r = 0; r < rows; r++) {
            html += '<tr>';
            var c = 0;
            while (c < cols) {
                var item = cellMap[r + ',' + c];
                if (!item) {
                    html += '<td style="' + cellPad + '"></td>';
                    c++;
                    continue;
                }
                var span = item.col_span || 1;
                var label = item.show_label !== false ? (item.custom_label || defaultLabel(item.element_type)) : '';
                var sample = fieldSamples[item.element_type] || matrixSamples[item.element_type] || '—';
                if (item.element_type === 'medico' && !showDoctor) {
                    c += span;
                    continue;
                }
                html += '<td style="' + cellPad + '"' + (span > 1 ? ' colspan="' + span + '"' : '') + '>';
                if (label) {
                    html += '<span class="pair-k" style="' + cssForStyle(styleForSection('client', 'pair_label')) + '">' + escapeHtml(label) + ' </span>';
                }
                html += '<span class="pair-v" style="' + cssForStyle(styleForSection('client', 'pair_value')) + '">' + escapeHtml(sample) + '</span>';
                html += '</td>';
                c += span;
            }
            html += '</tr>';
        }
        html += '</tbody></table>';
        return html;
    }

    function rowGapPadding(sectionId) {
        return 'padding:2pt 4pt;';
    }

    function sectionBlockStyle(sectionId) {
        var sp = getSectionSpacing(sectionId);
        if (sectionId === 'header' && sp.separator_enabled) {
            return sp.margin_bottom_pt > 0 ? 'margin-bottom:' + sp.margin_bottom_pt + 'pt;' : '';
        }
        if (sectionId === 'header' && !sp.separator_enabled) {
            return '';
        }
        return 'margin-bottom:' + sp.margin_bottom_pt + 'pt;';
    }

    function docHeaderTableStyle() {
        var sp = getSectionSpacing('header');
        var parts = [sectionTableLayoutStyle('header')];
        if (!sp.separator_enabled && sp.margin_bottom_pt > 0) {
            parts.push('margin-bottom:' + sp.margin_bottom_pt + 'pt');
        } else {
            parts.push('margin-bottom:0');
        }
        return parts.join(';');
    }

    function renderSectionSeparatorHtml(sectionId) {
        var sp = getSectionSpacing(sectionId);
        if (!sp.separator_enabled) {
            return '';
        }
        var style = sp.separator_style || 'line';
        var gap = sp.separator_gap_pt || 0;
        if (style === 'space') {
            return '<div class="comp-section-sep comp-section-sep-space" style="height:' + gap + 'pt;line-height:0;font-size:0;"></div>';
        }
        var color = sp.separator_color || '#CBD5E1';
        var thick = sp.separator_thickness_pt || 1;
        var width = sp.separator_width_pct || 100;
        var borderStyle = style === 'dashed' ? 'dashed' : style === 'dotted' ? 'dotted' : style === 'double' ? 'double' : 'solid';
        return '<div class="comp-section-sep" style="padding-top:' + gap + 'pt;line-height:0;font-size:0;">' +
            '<div style="border-top:' + thick + 'pt ' + borderStyle + ' ' + color + ';width:' + width + '%;margin:0 auto;"></div></div>';
    }

    function wrapSectionBlock(innerHtml, sectionId) {
        if (!innerHtml) {
            return '';
        }
        return '<div class="comp-sec-block comp-sec-block-' + sectionId + '" style="' + sectionBlockStyle(sectionId) + '">' +
            innerHtml + renderSectionSeparatorHtml(sectionId) + '</div>';
    }

    function renderMatrixCell(item, ctx) {
        if (!item || item.enabled === false) {
            return '';
        }
        var t = item.element_type;
        var sid = ctx.sectionId || sectionIdForType(t) || 'header';
        var colors = ctx.colors;
        var dim = ctx.dim;
        var tagline = ctx.tagline;
        var footer = ctx.footer;
        var showDoctor = ctx.showDoctor;
        var secStyle = function (key, extra) {
            return cssForStyle(styleForSection(sid, key), extra);
        };

        if (t === 'brand_name') {
            return '<p class="brand-name" style="' + secStyle('brand_name') + ';margin:0;">' + escapeHtml(companyName) + '</p>';
        }
        if (t === 'brand_tagline') {
            return '<p class="brand-tagline" style="' + secStyle('brand_tagline') + ';margin:0;">' + escapeHtml(tagline) + '</p>';
        }
        if (t === 'receipt_badge_title') {
            var rt = matrixText('receipt_badge_title', 'Recibo de pago');
            return rt ? '<p class="receipt-badge-title" style="' + secStyle('receipt_badge_title') + ';margin:0 0 4px;">' + escapeHtml(rt) + '</p>' : '';
        }
        if (t === 'receipt_badge_orden') {
            return '<p class="receipt-badge-orden" style="' + secStyle('receipt_badge_orden') + ';margin:0;">N.º ' + escapeHtml(reciboNumeroPreview()) + '</p>';
        }
        if (t === 'receipt_badge_fecha') {
            return '<p class="receipt-badge-fecha" style="' + secStyle('receipt_badge_fecha') + ';margin:4px 0 0;">31/05/2026 10:30</p>';
        }
        if (t === 'section_client' || t === 'section_items') {
            var titleSid = sectionIdForType(t) || (t === 'section_client' ? 'client' : 'table');
            var txt = matrixText(t, MATRIX_FALLBACKS[t] || '');
            return txt ? '<p class="section-title" style="' + cssForStyle(styleForSection(titleSid, 'section_title')) + ';margin:0;">' + escapeHtml(txt) + '</p>' : '';
        }
        if (isClientField(t)) {
            if (t === 'medico' && !showDoctor) {
                return '';
            }
            var clientStyleSec = sectionIdForType(t) || 'client';
            var label = item.show_label !== false ? (item.custom_label || defaultLabel(t)) : '';
            var sample = fieldSamples[t] || matrixSamples[t] || '—';
            var h = '';
            if (label) {
                h += '<span class="pair-k" style="' + cssForStyle(styleForSection(clientStyleSec, 'pair_label')) + '">' + escapeHtml(label) + ' </span>';
            }
            h += '<span class="pair-v" style="' + cssForStyle(styleForSection(clientStyleSec, 'pair_value')) + '">' + escapeHtml(sample) + '</span>';
            return h;
        }
        if (t === 'items_header_desc' || t === 'items_header_amount') {
            var htxt = matrixText(t, MATRIX_FALLBACKS[t] || '');
            if (!htxt) {
                return '';
            }
            var hAlign = resolveItemAlignH(item);
            var align = 'text-align:' + (hAlign === 'right' ? 'right' : hAlign === 'center' ? 'center' : 'left') + ';';
            return '<span style="' + secStyle('items_header') + ';background:' + itemsHeaderBgColor(dim, colors) + ';display:block;' + align + 'padding:' + dim.cellPad + 'pt 8px;">' + escapeHtml(htxt) + '</span>';
        }
        if (t === 'items_table') {
            var colDesc = matrixText('items_header_desc', 'Descripción');
            var colAmt = matrixText('items_header_amount', 'Importe ($)');
            var separateHeaders = itemEnabled('items_header_desc') || itemEnabled('items_header_amount');
            var thPad = 'padding:' + dim.cellPad + 'pt 8px;';
            var tdPad = 'padding:' + dim.cellPad + 'pt 8px;';
            var tbl = '<table class="tbl-items" style="table-layout:fixed;width:100%;"><colgroup><col><col style="width:' + dim.amount + '%"></colgroup>';
            if (!separateHeaders && (colDesc || colAmt)) {
                tbl += '<thead><tr>';
                if (colDesc) {
                    tbl += '<th style="' + secStyle('items_header') + ';background:' + itemsHeaderBgColor(dim, colors) + ';' + thPad + '">' + escapeHtml(colDesc) + '</th>';
                } else {
                    tbl += '<th></th>';
                }
                if (colAmt) {
                    tbl += '<th style="' + secStyle('items_header') + ';background:' + itemsHeaderBgColor(dim, colors) + ';text-align:right;' + thPad + '">' + escapeHtml(colAmt) + '</th>';
                } else {
                    tbl += '<th></th>';
                }
                tbl += '</tr></thead>';
            }
            tbl += '<tbody>' +
                '<tr><td style="' + secStyle('items_body') + ';' + tdPad + '">Hemograma completo</td>' +
                '<td class="num" style="' + secStyle('items_num') + ';text-align:right;' + tdPad + '">85,00</td></tr>' +
                '<tr><td style="' + secStyle('items_body') + ';' + tdPad + '">Glucosa en ayunas</td>' +
                '<td class="num" style="' + secStyle('items_num') + ';text-align:right;' + tdPad + '">45,00</td></tr>' +
                '</tbody></table>';
            return tbl;
        }
        if (t === 'totals_box') {
            return '';
        }
        if (t === 'total_orden' || t === 'total_pagado' || t === 'total_saldo') {
            var lbl = matrixText(t, MATRIX_FALLBACKS[t] || '');
            if (!lbl) {
                return '';
            }
            var samples = { total_orden: '$ 130,00', total_pagado: '$ 130,00', total_saldo: '$ 0,00' };
            var isFinal = t === 'total_saldo';
            var lblStyle = isFinal ? cssForStyle(styleForSection('totals', 'totals_final'), { color: '#fff' }) + ';background:' + colors.primary + ';' : cssForStyle(styleForSection('totals', 'totals_label'));
            var valStyle = isFinal ? cssForStyle(styleForSection('totals', 'totals_final'), { color: '#fff' }) + ';background:' + colors.primary + ';' : cssForStyle(styleForSection('totals', 'totals_value'));
            var totalAlign = matrixCellAlignStyle(item);
            return '<table style="' + sectionTableLayoutStyle('totals') + '"><tr class="' + (isFinal ? 'total-final' : '') + '" style="' + totalAlign + '">' +
                '<td class="t-lbl" style="width:' + dim.totalsLbl + '%;' + lblStyle + ';">' + escapeHtml(lbl) + '</td>' +
                '<td class="t-val" style="width:' + dim.totalsVal + '%;' + valStyle + ';">' + escapeHtml(samples[t]) + '</td></tr></table>';
        }
        if (t === 'pay_method') {
            var payLbl = matrixText('pay_method', 'Forma de pago:');
            if (!payLbl) {
                return '';
            }
            return '<div class="pay-method" style="' + cssForStyle(styleForSection('footer', 'pay_method')) + ';"><strong>' + escapeHtml(payLbl) + '</strong> Efectivo</div>';
        }
        if (t === 'footer') {
            return '<div class="foot" style="' + cssForStyle(styleForSection('footer', 'footer')) + ';">' + escapeHtml(footer) + '</div>';
        }
        return '';
    }

    function renderSectionGridPreview(sectionId, ctx) {
        ctx = Object.assign({}, ctx, { sectionId: sectionId });
        var grid = getSectionGrid(sectionId);
        var cols = grid.columns;
        var rows = grid.rows;
        var hasContent = false;
        (grid.items || []).forEach(function (it) {
            if (it.enabled !== false && it.element_type !== 'totals_box') {
                hasContent = true;
            }
        });
        if (!hasContent) {
            return '';
        }
        var pad = rowGapPadding(sectionId);
        var tableClass = sectionId === 'header' ? 'doc-header' : sectionId === 'client' ? 'pair-table' : 'comp-sec-grid comp-sec-' + sectionId;
        var tableStyle = sectionId === 'header' ? docHeaderTableStyle() : sectionTableLayoutStyle(sectionId);
        var html = '<table class="' + tableClass + '" style="' + tableStyle + '"><tbody>';
        for (var r = 0; r < rows; r++) {
            html += '<tr>';
            var c = 0;
            while (c < cols) {
                var cell = matrixCellAt(sectionId, r, c);
                if (cell.covered) {
                    c++;
                    continue;
                }
                var item = cell.item;
                if (!item) {
                    html += '<td style="' + pad + '"></td>';
                    c++;
                    continue;
                }
                var spans = matrixSpanAttrs(item, c, cols, r, rows);
                var inner = renderMatrixCell(item, ctx);
                if (inner) {
                    inner = wrapMatrixCellContent(inner, item);
                }
                if (sectionId === 'header') {
                    var wrapBadge = shouldWrapReceiptBadge(item, c, cols);
                    if (inner && wrapBadge) {
                        inner = '<div class="receipt-badge"><div class="receipt-badge-inner" style="' + receiptBadgeBoxStyle(ctx.dim, ctx.colors) + '">' + inner + '</div></div>';
                    }
                    var widthPct = headerColWidthPct(c, spans.colSpan, cols, ctx.dim);
                    var widthStyle = spans.colSpan >= cols ? '' : 'width:' + widthPct + '%;';
                    var labBg = wrapBadge ? '' : headerLabCellBgStyle(ctx.dim);
                    html += '<td style="' + pad + widthStyle + labBg + matrixCellAlignStyle(item) + '"' + spans.attr + '>' + inner + '</td>';
                } else {
                    html += '<td style="' + pad + matrixCellAlignStyle(item) + '"' + spans.attr + '>' + inner + '</td>';
                }
                c += spans.colSpan;
            }
            html += '</tr>';
        }
        html += '</tbody></table>';
        if (sectionId === 'client') {
            html = '<div class="panel">' + html + '</div>';
        }
        if (sectionId === 'totals') {
            html = '<div class="totals-box" style="width:' + ctx.dim.totalsW + 'pt;margin-left:auto;">' + html + '</div>';
        }
        return wrapSectionBlock(html, sectionId);
    }

    function dimensionCss() {
        var d = normalizeDimensions(state.dimensions || {});
        state.dimensions = d;
        var lab = d.header_lab_width_pct || 58;
        return Object.assign({}, d, {
            lab: lab,
            receipt: 100 - lab,
            amount: d.items_amount_col_pct || 26,
            cellPad: d.items_cell_padding_pt || 8,
            totalsW: d.totals_box_width_pt || 280,
            totalsLbl: d.totals_label_col_pct || 52,
            totalsVal: 100 - (d.totals_label_col_pct || 52)
        });
    }

    function renderPreview() {
        readStylesFromMatrix();
        readDimensionsFromMatrix();
        readSectionSpacingFromUi();
        applyStylePreviewSamples();
        var el = document.getElementById('comp_receipt_preview');
        if (!el) {
            return;
        }
        var colors = getColors();
        var tagline = (document.getElementById('comprobante_tagline') || {}).value || 'Constancia de pago';
        var footer = (document.getElementById('comprobante_footer_note') || {}).value || '';
        var showDoctor = document.getElementById('comprobante_show_doctor') ? document.getElementById('comprobante_show_doctor').checked : true;
        var dim = dimensionCss();
        var ctx = { colors: colors, dim: dim, tagline: tagline, footer: footer, showDoctor: showDoctor, sectionId: 'header' };

        var parts = ['<div class="accent-bar" style="background:' + colors.primary + ';"></div>'];
        sectionIds.forEach(function (sid) {
            ctx.sectionId = sid;
            var block = renderSectionGridPreview(sid, ctx);
            if (block) {
                parts.push(block);
            }
        });

        el.innerHTML = parts.join('');
        el.style.cssText = cssForStyle(styleForSection('header', 'body'), { color: colors.text }) + ';line-height:1.45;';
    }

    function syncHiddenJson() {
        readStylesFromMatrix();
        readDimensionsFromMatrix();
        readSectionSpacingFromUi();
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
        document.querySelectorAll('.comp-style-input, .comp-dimension-input, .comp-dimension-color-clear, .comp-color-sync, .comp-preview-sync').forEach(function (el) {
            el.addEventListener('input', renderPreview);
            el.addEventListener('change', renderPreview);
        });
        document.querySelectorAll('.comp-section-cols, .comp-section-rows').forEach(function (el) {
            el.addEventListener('change', function () {
                var sid = el.getAttribute('data-section');
                if (sid) {
                    rebuildSectionMatrix(sid);
                    renderPreview();
                }
            });
        });
        document.querySelectorAll('.comp-section-spacing').forEach(function (el) {
            el.addEventListener('input', renderPreview);
            el.addEventListener('change', renderPreview);
        });
        document.querySelectorAll('.comp-section-spacing[data-spacing-field="separator_enabled"]').forEach(function (el) {
            el.addEventListener('change', renderPreview);
        });
    }

    function init() {
        if (!document.getElementById('tab-comprobante')) {
            return;
        }
        bindPalette();
        bindSectionsMatrixDnD();
        bindModal();
        bindForm();
        syncReciboRangoFieldsUi();
        var reciboRangoChk = document.getElementById('comprobante_recibo_num_rango_activo');
        if (reciboRangoChk) {
            reciboRangoChk.addEventListener('change', function () {
                syncReciboRangoFieldsUi();
                renderPreview();
            });
        }
        renderAllSectionMatrices();
        renderPreview();
        syncHiddenJson();
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
