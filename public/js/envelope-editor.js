/**
 * Editor de matriz para plantillas de sobres.
 */
(function () {
    'use strict';

    var DEFAULT_ENVELOPE_SIZES = {
        dl:      { width_mm: 220, height_mm: 110 },
        num10:   { width_mm: 241, height_mm: 105 },
        c5:      { width_mm: 229, height_mm: 162 },
        c4:      { width_mm: 324, height_mm: 229 },
        a4_fold: { width_mm: 210, height_mm: 99 },
        custom:  { width_mm: 220, height_mm: 110 }
    };

    var ALLOWED_TEXT_FLOWS = ['horizontal', 'vertical_down', 'vertical_up'];
    var PT_TO_MM = 25.4 / 72;
    var STACK_GAP_MM = 2;
    var QR_SIZE_MIN_MM = 15;
    var QR_SIZE_MAX_MM = 80;

    var boot = window.ENVELOPE_EDITOR_BOOT || {};
    var templateId = boot.templateId || 0;
    var elLabels = boot.elementLabels || {};
    var elSamples = boot.elementSamples || {};
    var envelopeSizes = boot.envelopeSizes || DEFAULT_ENVELOPE_SIZES;
    var imageBaseUrl = boot.imageBaseUrl || '';
    var state;

    function getEnvelopeSizes() {
        return envelopeSizes && typeof envelopeSizes === 'object' ? envelopeSizes : DEFAULT_ENVELOPE_SIZES;
    }

    function mmToPx(mm, scale) {
        return mm * scale;
    }

    function ptToPx(pt, scale) {
        return (pt || 10) * PT_TO_MM * scale;
    }

    function clampQrSizeMm(mm) {
        return Math.max(QR_SIZE_MIN_MM, Math.min(QR_SIZE_MAX_MM, mm || 35));
    }

    var pendingImageFiles = {};
    /** @type {Record<string, File>} */
    var pendingImageFileObjects = {};
    /** @type {{source: string, element_type?: string, uid?: string}|null} */
    var dragPayload = null;
    var activeDropCell = null;
    /** @type {import('sortablejs').Sortable[]} */
    var cellSortables = [];

    function uid() {
        var a = new Uint8Array(8);
        if (window.crypto && crypto.getRandomValues) {
            crypto.getRandomValues(a);
            return Array.from(a, function (b) { return b.toString(16).padStart(2, '0'); }).join('');
        }
        return 'e' + Date.now().toString(36) + Math.random().toString(36).slice(2, 10);
    }

    function normalizeLayout(raw) {
        var def = {
            version: 1,
            size_key: 'dl',
            width_mm: 220,
            height_mm: 110,
            columns: 4,
            rows: 3,
            items: [],
            merges: []
        };
        if (!raw || typeof raw !== 'object') {
            return JSON.parse(JSON.stringify(def));
        }
        var sk = raw.size_key || 'dl';
        var sizes = getEnvelopeSizes();
        var sz = sizes[sk] || sizes.dl || { width_mm: 220, height_mm: 110 };
        var cols = clamp(parseInt(raw.columns, 10) || 4, 1, 8);
        var rows = clamp(parseInt(raw.rows, 10) || 3, 1, 12);
        return {
            version: 1,
            size_key: sk,
            width_mm: sk === 'custom' ? parseFloat(raw.width_mm) || sz.width_mm : sz.width_mm,
            height_mm: sk === 'custom' ? parseFloat(raw.height_mm) || sz.height_mm : sz.height_mm,
            columns: cols,
            rows: rows,
            items: Array.isArray(raw.items)
                ? normalizeStackOrders(raw.items.map(function (it) { return normalizeItem(it, rows, cols); }).filter(Boolean))
                : [],
            merges: Array.isArray(raw.merges) ? raw.merges.map(normalizeMerge).filter(Boolean) : []
        };
    }

    function normalizeMerge(m) {
        if (!m || typeof m !== 'object') {
            return null;
        }
        var row = clamp(parseInt(m.row, 10) || 0, 0, 11);
        var col = clamp(parseInt(m.col, 10) || 0, 0, 7);
        var colSpan = clamp(parseInt(m.col_span, 10) || 1, 1, 8 - col);
        var rowSpan = clamp(parseInt(m.row_span, 10) || 1, 1, 12 - row);
        if (colSpan < 2 && rowSpan < 2) {
            return null;
        }
        return { row: row, col: col, col_span: colSpan, row_span: rowSpan };
    }

    function normalizeItem(it, gridRows, gridCols) {
        if (!it || !it.element_type) {
            return null;
        }
        gridRows = gridRows || 12;
        gridCols = gridCols || 8;
        var row = clamp(parseInt(it.row, 10) || 0, 0, gridRows - 1);
        var col = clamp(parseInt(it.col, 10) || 0, 0, gridCols - 1);
        var o = {
            uid: it.uid || uid(),
            element_type: it.element_type,
            row: row,
            col: col,
            col_span: clamp(parseInt(it.col_span, 10) || 1, 1, gridCols - col),
            row_span: clamp(parseInt(it.row_span, 10) || 1, 1, gridRows - row),
            enabled: it.enabled !== false,
            show_label: it.show_label !== false,
            custom_label: String(it.custom_label || ''),
            font_size_pt: clamp(parseInt(it.font_size_pt, 10) || 10, 6, 24),
            text_align: ['left', 'center', 'right'].indexOf(it.text_align) >= 0 ? it.text_align : 'left',
            vertical_align: ['top', 'middle', 'bottom'].indexOf(it.vertical_align) >= 0 ? it.vertical_align : 'top',
            margin_mm: normalizeMarginMm(it.margin_mm),
            text_flow: normalizeTextFlow(it.text_flow),
            font_weight: it.font_weight === 'bold' ? 'bold' : 'normal',
            stack_order: Math.max(0, parseInt(it.stack_order, 10) || 0)
        };
        if (it.element_type === 'custom_text') {
            o.custom_value = String(it.custom_value || '');
        }
        if (it.element_type === 'custom_image') {
            o.image_file = String(it.image_file || '');
            o.width_percent = clamp(parseInt(it.width_percent, 10) || 80, 5, 100);
            o.height_percent = clamp(parseInt(it.height_percent, 10) || 60, 5, 100);
        }
        if (it.element_type === 'qr') {
            o.qr_size_mm = clamp(parseInt(it.qr_size_mm, 10) || 35, 15, 80);
        }
        if (it.element_type === 'codigo_barras') {
            o.barcode_height_mm = clamp(parseInt(it.barcode_height_mm, 10) || 14, 8, 40);
            o.barcode_width_mm = clamp(parseInt(it.barcode_width_mm, 10) || 60, 25, 120);
        }
        return o;
    }

    function isNonTextField(type) {
        return type === 'custom_image' || type === 'qr' || type === 'codigo_barras';
    }

    function clamp(n, min, max) {
        return Math.max(min, Math.min(max, n));
    }

    /** Orden de apilado por celda (0 = primero / arriba). */
    function normalizeStackOrders(items) {
        if (!items || !items.length) {
            return items || [];
        }
        var byCell = {};
        items.forEach(function (it, idx) {
            var key = it.row + ',' + it.col;
            if (!byCell[key]) {
                byCell[key] = [];
            }
            byCell[key].push({
                idx: idx,
                order: it.stack_order || 0,
                seq: idx
            });
        });
        Object.keys(byCell).forEach(function (key) {
            byCell[key].sort(function (a, b) {
                if (a.order !== b.order) {
                    return a.order - b.order;
                }
                return a.seq - b.seq;
            });
            byCell[key].forEach(function (entry, pos) {
                items[entry.idx].stack_order = pos;
            });
        });
        return items;
    }

    function nextStackOrderInCell(row, col, excludeUid) {
        var max = -1;
        getItemsAtCell(row, col).forEach(function (it) {
            if (excludeUid && it.uid === excludeUid) {
                return;
            }
            max = Math.max(max, it.stack_order || 0);
        });
        return max + 1;
    }

    function normalizeTextFlow(raw) {
        var flow = String(raw || 'horizontal').toLowerCase();
        return ALLOWED_TEXT_FLOWS.indexOf(flow) >= 0 ? flow : 'horizontal';
    }

    function applyTextFlowStyles(el, flow) {
        el.classList.remove('envelope-text-vertical-down', 'envelope-text-vertical-up');
        el.style.writingMode = '';
        el.style.textOrientation = '';
        el.style.transform = '';
        flow = normalizeTextFlow(flow);
        if (flow === 'vertical_down') {
            el.classList.add('envelope-text-vertical-down');
        } else if (flow === 'vertical_up') {
            el.classList.add('envelope-text-vertical-up');
        }
    }

    function isVerticalTextFlow(it) {
        return normalizeTextFlow(it && it.text_flow) !== 'horizontal';
    }

    function getCellStackDirection(items) {
        var hasHorizontal = false;
        var hasVertical = false;
        items.forEach(function (it) {
            if (!it || isNonTextField(it.element_type)) {
                return;
            }
            if (isVerticalTextFlow(it)) {
                hasVertical = true;
            } else {
                hasHorizontal = true;
            }
        });
        if (hasVertical && !hasHorizontal) {
            return 'row';
        }
        return 'column';
    }

    function getCellAlignmentRef(items) {
        var i;
        for (i = 0; i < items.length; i++) {
            if (!isNonTextField(items[i].element_type)) {
                return items[i];
            }
        }
        return items[0];
    }

    function mapTextAlignToJustify(align) {
        return align === 'center' ? 'center' : align === 'right' ? 'flex-end' : 'flex-start';
    }

    function mapVerticalAlignToAlign(valign) {
        return valign === 'middle' ? 'center' : valign === 'bottom' ? 'flex-end' : 'flex-start';
    }

    function normalizeMarginMm(raw) {
        var sides = ['top', 'right', 'bottom', 'left'];
        var out = { top: 0, right: 0, bottom: 0, left: 0 };
        if (!raw || typeof raw !== 'object') {
            return out;
        }
        sides.forEach(function (side) {
            var v = parseFloat(raw[side]);
            out[side] = clamp(isNaN(v) ? 0 : v, 0, 20);
        });
        return out;
    }

    state = normalizeLayout(boot.layout || {});

    function readModalMargins() {
        return normalizeMarginMm({
            top: document.getElementById('env_modal_margin_top').value,
            right: document.getElementById('env_modal_margin_right').value,
            bottom: document.getElementById('env_modal_margin_bottom').value,
            left: document.getElementById('env_modal_margin_left').value
        });
    }

    function fillModalMargins(marginMm) {
        var m = normalizeMarginMm(marginMm);
        document.getElementById('env_modal_margin_top').value = m.top;
        document.getElementById('env_modal_margin_right').value = m.right;
        document.getElementById('env_modal_margin_bottom').value = m.bottom;
        document.getElementById('env_modal_margin_left').value = m.left;
    }

    function applyCellFlexAlignment(cell, it) {
        var align = ['left', 'center', 'right'].indexOf(it.text_align) >= 0 ? it.text_align : 'left';
        var valign = ['top', 'middle', 'bottom'].indexOf(it.vertical_align) >= 0 ? it.vertical_align : 'top';
        var vertical = isVerticalTextFlow(it);
        cell.style.display = 'flex';
        cell.style.boxSizing = 'border-box';
        cell.style.width = '100%';
        cell.style.height = '100%';
        cell.style.padding = '0';
        cell.style.margin = '0';
        if (vertical) {
            cell.classList.add('envelope-preview-cell-vertical');
            cell.style.flexDirection = 'row';
            cell.style.justifyContent = mapTextAlignToJustify(align);
            cell.style.alignItems = mapVerticalAlignToAlign(valign);
            cell.style.textAlign = 'left';
        } else {
            cell.classList.remove('envelope-preview-cell-vertical');
            cell.style.flexDirection = 'column';
            cell.style.alignItems = align === 'center' ? 'center' : align === 'right' ? 'flex-end' : 'flex-start';
            cell.style.justifyContent = valign === 'middle' ? 'center' : valign === 'bottom' ? 'flex-end' : 'flex-start';
            cell.style.textAlign = align;
        }
    }

    /** Estilo de un campo dentro de una pila (varios campos en la misma celda). */
    function applyStackItemStyle(block, it, scale, stackDirection) {
        var align = ['left', 'center', 'right'].indexOf(it.text_align) >= 0 ? it.text_align : 'left';
        var valign = ['top', 'middle', 'bottom'].indexOf(it.vertical_align) >= 0 ? it.vertical_align : 'top';
        var m = normalizeMarginMm(it.margin_mm);
        var vertical = isVerticalTextFlow(it);
        stackDirection = stackDirection || 'column';
        block.style.boxSizing = 'border-box';
        block.style.display = 'flex';
        block.style.flex = '0 0 auto';
        block.style.margin = mmToPx(m.top, scale) + 'px ' + mmToPx(m.right, scale) + 'px ' + mmToPx(m.bottom, scale) + 'px ' + mmToPx(m.left, scale) + 'px';
        block.style.padding = '0';
        block.style.overflow = 'visible';
        if (vertical) {
            block.classList.add('envelope-preview-stack-item-vertical');
            block.style.width = 'auto';
            block.style.maxWidth = 'none';
            block.style.flexDirection = 'column';
            block.style.textAlign = 'left';
            if (stackDirection === 'row') {
                block.style.alignSelf = mapVerticalAlignToAlign(valign);
            } else {
                block.style.width = '100%';
                block.style.flexDirection = 'row';
                block.style.justifyContent = mapTextAlignToJustify(align);
                block.style.alignSelf = 'stretch';
            }
        } else {
            block.style.width = '100%';
            block.style.maxWidth = '100%';
            block.style.flexDirection = 'column';
            block.style.justifyContent = 'flex-start';
            block.style.alignItems = align === 'center' ? 'center' : align === 'right' ? 'flex-end' : 'flex-start';
            block.style.alignSelf = 'stretch';
            block.style.textAlign = align;
        }
    }

    function applyPreviewStackLayout(stack, items, scale) {
        var dir = getCellStackDirection(items);
        var ref = getCellAlignmentRef(items);
        var align = ['left', 'center', 'right'].indexOf(ref.text_align) >= 0 ? ref.text_align : 'left';
        var valign = ['top', 'middle', 'bottom'].indexOf(ref.vertical_align) >= 0 ? ref.vertical_align : 'top';
        stack.classList.add(dir === 'row' ? 'envelope-preview-stack-row' : 'envelope-preview-stack-column');
        stack.style.flexDirection = dir;
        if (dir === 'row') {
            stack.style.justifyContent = mapTextAlignToJustify(align);
            stack.style.alignItems = mapVerticalAlignToAlign(valign);
        } else {
            stack.style.justifyContent = 'flex-start';
            stack.style.alignItems = align === 'center' ? 'center' : align === 'right' ? 'flex-end' : 'stretch';
        }
        return dir;
    }

    /** Filas de la matriz con la misma altura (1fr cada una). */
    function computePreviewRowWeights() {
        var rowH = [];
        var r;
        for (r = 0; r < state.rows; r++) {
            rowH[r] = 1;
        }
        return rowH;
    }

    function applyPreviewCellContainer(cell, items) {
        cell.style.boxSizing = 'border-box';
        cell.style.width = '100%';
        cell.style.height = '100%';
        cell.style.overflow = 'hidden';
        cell.style.padding = '0';
        if (items.length > 1) {
            cell.classList.add('envelope-preview-cell-multi');
            cell.style.display = 'flex';
            cell.style.flexDirection = 'column';
            cell.style.justifyContent = 'flex-start';
            cell.style.alignItems = 'stretch';
        } else {
            cell.classList.remove('envelope-preview-cell-multi');
            applyCellFlexAlignment(cell, items[0]);
        }
    }

    function createPreviewTextBlock(it, scale) {
        var block = document.createElement('div');
        block.className = 'envelope-preview-content';
        var vertical = isVerticalTextFlow(it);
        block.style.width = vertical ? 'auto' : '100%';
        block.style.textAlign = vertical ? 'left' : (['left', 'center', 'right'].indexOf(it.text_align) >= 0 ? it.text_align : 'left');
        block.style.fontSize = ptToPx(it.font_size_pt || 10, scale) + 'px';
        block.style.fontWeight = it.font_weight === 'bold' ? 'bold' : 'normal';
        block.style.lineHeight = '1.25';
        applyTextFlowStyles(block, it.text_flow);
        if (it.show_label) {
            var lbl = document.createElement('span');
            lbl.className = 'envelope-preview-lbl';
            lbl.textContent = (it.custom_label || labelFor(it.element_type)) + ': ';
            block.appendChild(lbl);
        }
        var val = document.createElement('span');
        val.textContent = sampleFor(it.element_type, it);
        block.appendChild(val);
        return block;
    }

    function labelFor(type) {
        return elLabels[type] || type;
    }

    function sampleFor(type, item) {
        if (type === 'custom_text' && item && item.custom_value) {
            return item.custom_value;
        }
        return elSamples[type] || '[' + type + ']';
    }

    function parseDimInput(el, current, min, max) {
        if (!el || el.value === '' || el.value === null) {
            return current;
        }
        var v = parseInt(el.value, 10);
        if (isNaN(v)) {
            return current;
        }
        return clamp(v, min, max);
    }

    function readControls() {
        readControlsStrict(false);
    }

    /** @param {boolean} forceDims Si true, lee filas/columnas aunque el input esté vacío (usa mínimo 1). */
    function readControlsStrict(forceDims) {
        var sizeEl = document.getElementById('env_size_key');
        var colsEl = document.getElementById('env_cols');
        var rowsEl = document.getElementById('env_rows');
        if (!sizeEl || !colsEl || !rowsEl) {
            return;
        }
        var sk = sizeEl.value;
        var sizes = getEnvelopeSizes();
        var sz = sizes[sk] || {};
        state.size_key = sk;

        if (forceDims) {
            var cv = parseInt(colsEl.value, 10);
            var rv = parseInt(rowsEl.value, 10);
            state.columns = clamp(isNaN(cv) ? state.columns : cv, 1, 8);
            state.rows = clamp(isNaN(rv) ? state.rows : rv, 1, 12);
        } else {
            state.columns = parseDimInput(colsEl, state.columns, 1, 8);
            state.rows = parseDimInput(rowsEl, state.rows, 1, 12);
        }

        if (sk === 'custom') {
            var wEl = document.getElementById('env_width_mm');
            var hEl = document.getElementById('env_height_mm');
            state.width_mm = wEl ? parseFloat(wEl.value) || 220 : 220;
            state.height_mm = hEl ? parseFloat(hEl.value) || 110 : 110;
        } else {
            state.width_mm = sz.width_mm || 220;
            state.height_mm = sz.height_mm || 110;
        }
    }

    function syncControlsFromState() {
        var sizeEl = document.getElementById('env_size_key');
        var colsEl = document.getElementById('env_cols');
        var rowsEl = document.getElementById('env_rows');
        var wEl = document.getElementById('env_width_mm');
        var hEl = document.getElementById('env_height_mm');
        var customWrap = document.getElementById('env_custom_size_wrap');
        if (sizeEl) {
            sizeEl.value = state.size_key;
        }
        if (colsEl) {
            colsEl.value = state.columns;
        }
        if (rowsEl) {
            rowsEl.value = state.rows;
        }
        if (wEl) {
            wEl.value = state.width_mm;
        }
        if (hEl) {
            hEl.value = state.height_mm;
        }
        if (customWrap) {
            customWrap.style.display = state.size_key === 'custom' ? '' : 'none';
        }
    }

    function syncLabelsFromDom() {
        var pal = document.getElementById('envelope-field-palette');
        if (!pal) {
            return;
        }
        pal.querySelectorAll('.envelope-palette-item[data-element-type]').forEach(function (chip) {
            var type = chip.getAttribute('data-element-type');
            if (!type) {
                return;
            }
            var txt = (chip.textContent || '').trim();
            if (txt) {
                elLabels[type] = txt;
            }
        });
    }

    function setDragPayload(payload) {
        dragPayload = payload;
        var json = JSON.stringify(payload);
        try {
            if (window.sessionStorage) {
                sessionStorage.setItem('envelope_drag_payload', json);
            }
        } catch (err) {
            // ignorar
        }
        return json;
    }

    function readDragPayload(dataTransfer) {
        var raw = null;
        if (dataTransfer) {
            try {
                raw = dataTransfer.getData('application/x-envelope-field');
                if (!raw) {
                    raw = dataTransfer.getData('text/plain');
                }
                if (!raw) {
                    raw = dataTransfer.getData('text');
                }
            } catch (err) {
                raw = null;
            }
        }
        if (raw) {
            try {
                return JSON.parse(raw);
            } catch (err) {
                // seguir
            }
        }
        if (dragPayload) {
            return dragPayload;
        }
        try {
            var stored = sessionStorage.getItem('envelope_drag_payload');
            if (stored) {
                return JSON.parse(stored);
            }
        } catch (err) {
            // ignorar
        }
        return null;
    }

    function clearDragPayload() {
        dragPayload = null;
        activeDropCell = null;
        try {
            sessionStorage.removeItem('envelope_drag_payload');
        } catch (err) {
            // ignorar
        }
        document.querySelectorAll('.envelope-grid-cell.is-drop-target').forEach(function (c) {
            c.classList.remove('is-drop-target');
        });
        document.querySelectorAll('.envelope-palette-item.is-dragging').forEach(function (c) {
            c.classList.remove('is-dragging');
        });
    }

    function bindPaletteDnD() {
        var pal = document.getElementById('envelope-field-palette');
        if (!pal || pal.getAttribute('data-dnd-bound') === '1') {
            return;
        }
        pal.setAttribute('data-dnd-bound', '1');
        pal.addEventListener('dragstart', onPaletteDragStart);
        pal.addEventListener('dragend', onPaletteDragEnd);
    }

    function buildPalette() {
        var pal = document.getElementById('envelope-field-palette');
        if (!pal) {
            return;
        }
        if (pal.querySelector('.envelope-palette-item')) {
            syncLabelsFromDom();
            pal.querySelectorAll('.envelope-palette-item').forEach(function (chip) {
                chip.setAttribute('draggable', 'true');
            });
            return;
        }
        var types = Object.keys(elLabels);
        if (types.length === 0) {
            return;
        }
        pal.innerHTML = '';
        types.forEach(function (type) {
            var chip = document.createElement('div');
            chip.className = 'envelope-palette-item';
            chip.setAttribute('data-element-type', type);
            chip.setAttribute('draggable', 'true');
            chip.setAttribute('role', 'button');
            chip.setAttribute('tabindex', '0');
            chip.textContent = elLabels[type];
            pal.appendChild(chip);
        });
    }

    function onPaletteDragStart(e) {
        var chip = e.target.closest('.envelope-palette-item');
        if (!chip || !e.dataTransfer) {
            return;
        }
        var t = chip.getAttribute('data-element-type');
        if (!t) {
            return;
        }
        var json = setDragPayload({ source: 'palette', element_type: t });
        e.dataTransfer.setData('application/x-envelope-field', json);
        e.dataTransfer.setData('text/plain', json);
        e.dataTransfer.setData('text', json);
        e.dataTransfer.effectAllowed = 'copy';
        chip.classList.add('is-dragging');
    }

    function onPaletteDragEnd() {
        clearDragPayload();
    }

    function onChipDragStart(e, itemUid) {
        if (!e.dataTransfer) {
            return;
        }
        var json = setDragPayload({ source: 'chip', uid: itemUid });
        e.dataTransfer.setData('application/x-envelope-field', json);
        e.dataTransfer.setData('text/plain', json);
        e.dataTransfer.setData('text', json);
        e.dataTransfer.effectAllowed = 'move';
        var chip = e.target.closest('.envelope-grid-chip');
        if (chip) {
            chip.classList.add('is-dragging');
        }
    }

    function onChipDragEnd() {
        clearDragPayload();
    }

    function bindMatrixDnD() {
        var wrap = document.getElementById('envelope-grid-editor');
        if (!wrap || wrap.getAttribute('data-dnd-bound') === '1') {
            return;
        }
        wrap.setAttribute('data-dnd-bound', '1');

        wrap.addEventListener('dragenter', function (e) {
            var cell = e.target.closest('.envelope-grid-cell');
            if (!cell || !wrap.contains(cell)) {
                return;
            }
            e.preventDefault();
        });

        wrap.addEventListener('dragover', function (e) {
            var cell = e.target.closest('.envelope-grid-cell');
            if (!cell || !wrap.contains(cell)) {
                return;
            }
            e.preventDefault();
            e.stopPropagation();
            if (e.dataTransfer) {
                e.dataTransfer.dropEffect = (dragPayload && dragPayload.source === 'chip') ? 'move' : 'copy';
            }
            if (activeDropCell && activeDropCell !== cell) {
                activeDropCell.classList.remove('is-drop-target');
            }
            activeDropCell = cell;
            cell.classList.add('is-drop-target');
        });

        wrap.addEventListener('dragleave', function (e) {
            var cell = e.target.closest('.envelope-grid-cell');
            if (!cell) {
                return;
            }
            var related = e.relatedTarget;
            if (related && cell.contains(related)) {
                return;
            }
            cell.classList.remove('is-drop-target');
            if (activeDropCell === cell) {
                activeDropCell = null;
            }
        });

        wrap.addEventListener('drop', function (e) {
            var cell = e.target.closest('.envelope-grid-cell');
            if (!cell || !wrap.contains(cell)) {
                return;
            }
            e.preventDefault();
            e.stopPropagation();
            handleCellDrop(cell, e.dataTransfer);
        });
    }

    function handleCellDrop(cell, dataTransfer) {
        clearDragPayload();
        var row = parseInt(cell.getAttribute('data-row'), 10);
        var col = parseInt(cell.getAttribute('data-col'), 10);
        if (isNaN(row) || isNaN(col)) {
            return;
        }
        var raw = readDragPayload(dataTransfer);
        if (!raw || !raw.source) {
            return;
        }
        if (raw.source === 'palette' && raw.element_type) {
            addItemAt(raw.element_type, row, col);
            renderGrid();
        } else if (raw.source === 'chip' && raw.uid) {
            moveItem(raw.uid, row, col);
            renderGrid();
        }
    }

    function updateMatrixBadge() {
        var badge = document.getElementById('envelope-matrix-dims-badge');
        if (badge) {
            badge.textContent = state.columns + ' columnas × ' + state.rows + ' filas';
        }
    }

    function bindCellDropZone() {
        // Delegación global en bindMatrixDnD()
    }

    function getMergeAt(row, col) {
        return (state.merges || []).find(function (m) {
            return m.row === row && m.col === col;
        }) || null;
    }

    function getSpanAt(row, col) {
        var items = getItemsAtCell(row, col);
        if (items.length > 0) {
            var it = items[0];
            return {
                col_span: it.col_span || 1,
                row_span: it.row_span || 1,
                source: 'item'
            };
        }
        var merge = getMergeAt(row, col);
        if (merge) {
            return {
                col_span: merge.col_span || 1,
                row_span: merge.row_span || 1,
                source: 'merge'
            };
        }
        return { col_span: 1, row_span: 1, source: 'none' };
    }

    function isCellCovered(row, col) {
        var i;
        for (i = 0; i < state.items.length; i++) {
            var it = state.items[i];
            if (row >= it.row && row < it.row + (it.row_span || 1) &&
                col >= it.col && col < it.col + (it.col_span || 1)) {
                return !(it.row === row && it.col === col);
            }
        }
        for (i = 0; i < (state.merges || []).length; i++) {
            var m = state.merges[i];
            if (row >= m.row && row < m.row + (m.row_span || 1) &&
                col >= m.col && col < m.col + (m.col_span || 1)) {
                return !(m.row === row && m.col === col);
            }
        }
        return false;
    }

    function clearRegion(row, col, colSpan, rowSpan, keepAnchorCell) {
        if (keepAnchorCell === undefined) {
            keepAnchorCell = true;
        }
        var r, c;
        for (r = row; r < row + rowSpan; r++) {
            for (c = col; c < col + colSpan; c++) {
                state.items = state.items.filter(function (it) {
                    if (keepAnchorCell && it.row === row && it.col === col) {
                        return true;
                    }
                    return !(it.row === r && it.col === c);
                });
                if (!(r === row && c === col)) {
                    state.merges = (state.merges || []).filter(function (m) {
                        return !(m.row === r && m.col === c);
                    });
                }
            }
        }
    }

    function setCellSpan(row, col, colSpan, rowSpan) {
        colSpan = clamp(colSpan, 1, state.columns - col);
        rowSpan = clamp(rowSpan, 1, state.rows - row);
        var items = getItemsAtCell(row, col);
        clearRegion(row, col, colSpan, rowSpan, true);
        if (items.length > 0) {
            items.forEach(function (it) {
                it.col_span = colSpan;
                it.row_span = rowSpan;
            });
            state.merges = (state.merges || []).filter(function (m) {
                return !(m.row === row && m.col === col);
            });
        } else if (colSpan > 1 || rowSpan > 1) {
            state.merges = (state.merges || []).filter(function (m) {
                return !(m.row === row && m.col === col);
            });
            state.merges.push({ row: row, col: col, col_span: colSpan, row_span: rowSpan });
        } else {
            state.merges = (state.merges || []).filter(function (m) {
                return !(m.row === row && m.col === col);
            });
        }
        renderGrid();
    }

    function buildMergeControls(row, col) {
        var span = getSpanAt(row, col);
        var tools = document.createElement('div');
        tools.className = 'envelope-cell-merge-tools';

        var lbl = document.createElement('span');
        lbl.className = 'small fw-semibold text-secondary';
        lbl.textContent = 'Combinar celdas';
        tools.appendChild(lbl);

        var rowInputs = document.createElement('div');
        rowInputs.className = 'd-flex flex-wrap gap-1 align-items-center justify-content-center mt-1';

        var colIn = document.createElement('input');
        colIn.type = 'number';
        colIn.className = 'form-control form-control-sm envelope-merge-cols';
        colIn.min = '1';
        colIn.max = String(state.columns - col);
        colIn.value = String(span.col_span);
        colIn.title = 'Columnas';
        colIn.style.width = '3rem';

        var times = document.createElement('span');
        times.className = 'small text-muted';
        times.textContent = '×';

        var rowIn = document.createElement('input');
        rowIn.type = 'number';
        rowIn.className = 'form-control form-control-sm envelope-merge-rows';
        rowIn.min = '1';
        rowIn.max = String(state.rows - row);
        rowIn.value = String(span.row_span);
        rowIn.title = 'Filas';
        rowIn.style.width = '3rem';

        var btnApply = document.createElement('button');
        btnApply.type = 'button';
        btnApply.className = 'btn btn-sm btn-outline-primary';
        btnApply.textContent = 'Aplicar';
        btnApply.addEventListener('click', function () {
            setCellSpan(row, col, parseInt(colIn.value, 10) || 1, parseInt(rowIn.value, 10) || 1);
        });

        rowInputs.appendChild(colIn);
        rowInputs.appendChild(times);
        rowInputs.appendChild(rowIn);
        rowInputs.appendChild(btnApply);

        if (span.col_span > 1 || span.row_span > 1) {
            var btnReset = document.createElement('button');
            btnReset.type = 'button';
            btnReset.className = 'btn btn-sm btn-outline-secondary';
            btnReset.textContent = '1×1';
            btnReset.title = 'Separar celdas';
            btnReset.addEventListener('click', function () {
                setCellSpan(row, col, 1, 1);
            });
            rowInputs.appendChild(btnReset);
        }

        tools.appendChild(rowInputs);
        return tools;
    }

    function buildAddFieldSelect(row, col, isFirstInCell) {
        var sel = document.createElement('select');
        sel.className = 'form-select form-select-sm envelope-grid-cell-add';
        sel.setAttribute('aria-label', 'Agregar campo en fila ' + (row + 1) + ' columna ' + (col + 1));
        var opt0 = document.createElement('option');
        opt0.value = '';
        opt0.textContent = isFirstInCell ? '+ Agregar campo…' : '+ Agregar otro campo…';
        sel.appendChild(opt0);
        Object.keys(elLabels).forEach(function (type) {
            var opt = document.createElement('option');
            opt.value = type;
            opt.textContent = elLabels[type];
            sel.appendChild(opt);
        });
        sel.addEventListener('change', function () {
            if (!sel.value) {
                return;
            }
            addItemAt(sel.value, row, col);
            renderGrid();
        });
        return sel;
    }

    function buildEmptyCellContent(row, col) {
        var wrap = document.createElement('div');
        wrap.className = 'envelope-grid-cell-empty';

        wrap.appendChild(buildMergeControls(row, col));

        var hint = document.createElement('span');
        hint.textContent = 'Soltar aquí';
        wrap.appendChild(hint);
        wrap.appendChild(buildAddFieldSelect(row, col, true));
        return wrap;
    }

    function fillCellElement(cell, row, col) {
        cell.innerHTML = '';
        var span = getSpanAt(row, col);
        var coord = document.createElement('span');
        coord.className = 'envelope-grid-cell-coord';
        coord.textContent = 'F' + (row + 1) + '·C' + (col + 1) +
            ((span.col_span > 1 || span.row_span > 1) ? ' (' + span.col_span + '×' + span.row_span + ')' : '');
        cell.appendChild(coord);
        if (span.col_span > 1 || span.row_span > 1) {
            cell.classList.add('envelope-grid-cell-merged');
        }
        bindCellDropZone(cell);
        var cellItems = getItemsAtCell(row, col);
        if (cellItems.length === 0) {
            cell.appendChild(buildEmptyCellContent(row, col));
        } else {
            cell.appendChild(buildMergeControls(row, col));
            var list = document.createElement('div');
            list.className = 'envelope-grid-cell-items';
            cellItems.forEach(function (it) {
                list.appendChild(buildChip(it));
            });
            cell.appendChild(list);
            var addWrap = document.createElement('div');
            addWrap.className = 'envelope-grid-cell-add-row';
            addWrap.appendChild(buildAddFieldSelect(row, col, false));
            cell.appendChild(addWrap);
        }
    }

    function renderGrid() {
        updateMatrixBadge();
        var wrap = document.getElementById('envelope-grid-editor');
        if (!wrap) {
            return;
        }
        var cols = state.columns;
        var rows = state.rows;
        wrap.dataset.cols = String(cols);
        wrap.dataset.rows = String(rows);

        var table = document.createElement('table');
        table.className = 'table table-bordered envelope-matrix-table mb-0';

        var thead = document.createElement('thead');
        var headTr = document.createElement('tr');
        var cornerTh = document.createElement('th');
        cornerTh.className = 'envelope-matrix-corner-th';
        headTr.appendChild(cornerTh);
        for (var hc = 0; hc < cols; hc++) {
            var th = document.createElement('th');
            th.className = 'envelope-matrix-col-th text-center';
            th.textContent = 'Col ' + (hc + 1);
            headTr.appendChild(th);
        }
        thead.appendChild(headTr);
        table.appendChild(thead);

        var tbody = document.createElement('tbody');
        for (var r = 0; r < rows; r++) {
            var tr = document.createElement('tr');
            var rowTh = document.createElement('th');
            rowTh.className = 'envelope-matrix-row-th text-center';
            rowTh.textContent = 'F' + (r + 1);
            tr.appendChild(rowTh);
            for (var c = 0; c < cols; c++) {
                if (isCellCovered(r, c)) {
                    continue;
                }
                var span = getSpanAt(r, c);
                var td = document.createElement('td');
                td.className = 'envelope-grid-cell';
                td.setAttribute('data-row', String(r));
                td.setAttribute('data-col', String(c));
                if (span.col_span > 1) {
                    td.colSpan = span.col_span;
                }
                if (span.row_span > 1) {
                    td.rowSpan = span.row_span;
                }
                fillCellElement(td, r, c);
                tr.appendChild(td);
            }
            tbody.appendChild(tr);
        }
        table.appendChild(tbody);

        wrap.innerHTML = '';
        wrap.appendChild(table);

        syncControlsFromState();

        initCellSortables();

        try {
            renderPreview();
        } catch (previewErr) {
            console.error('Envelope preview:', previewErr);
        }
    }

    function hydrateMatrixFromDom() {
        document.querySelectorAll('#envelope-grid-editor .envelope-grid-cell').forEach(function (cell) {
            var row = parseInt(cell.getAttribute('data-row'), 10);
            var col = parseInt(cell.getAttribute('data-col'), 10);
            if (isNaN(row) || isNaN(col)) {
                return;
            }
            bindCellDropZone(cell);
            var sel = cell.querySelector('.envelope-grid-cell-add');
            if (sel && sel.getAttribute('data-bound') !== '1') {
                sel.setAttribute('data-bound', '1');
                sel.addEventListener('change', function () {
                    if (!sel.value) {
                        return;
                    }
                    addItemAt(sel.value, row, col);
                    renderGrid();
                });
            }
            cell.querySelectorAll('.envelope-grid-chip[data-uid]').forEach(function (chip) {
                var itemUid = chip.getAttribute('data-uid');
                if (!itemUid || chip.getAttribute('data-bound') === '1') {
                    return;
                }
                chip.setAttribute('data-bound', '1');
                chip.setAttribute('draggable', 'true');
                chip.addEventListener('dragstart', function (e) { onChipDragStart(e, itemUid); });
                chip.addEventListener('click', function () { openItemModal(itemUid); });
            });
        });
        updateMatrixBadge();
    }

    function getItemsAtCell(r, c) {
        return state.items.filter(function (it) {
            return it.row === r && it.col === c;
        }).sort(function (a, b) {
            return (a.stack_order || 0) - (b.stack_order || 0);
        });
    }

    function buildChip(it) {
        var chip = document.createElement('div');
        chip.className = 'envelope-grid-chip';
        chip.setAttribute('data-uid', it.uid);

        var handle = document.createElement('span');
        handle.className = 'envelope-chip-sort-handle';
        handle.setAttribute('title', 'Arrastrar para ordenar');
        handle.innerHTML = '<i class="fa-solid fa-grip-vertical" aria-hidden="true"></i>';
        chip.appendChild(handle);

        var title = document.createElement('span');
        title.className = 'envelope-grid-chip-title';
        title.textContent = labelFor(it.element_type);
        title.addEventListener('click', function () { openItemModal(it.uid); });
        chip.appendChild(title);

        var btn = document.createElement('button');
        btn.type = 'button';
        btn.className = 'btn btn-link btn-sm p-0 text-secondary envelope-chip-no-drag';
        btn.innerHTML = '<i class="fa-solid fa-pen"></i>';
        btn.addEventListener('click', function (e) {
            e.stopPropagation();
            openItemModal(it.uid);
        });
        chip.appendChild(btn);
        return chip;
    }

    function destroyCellSortables() {
        cellSortables.forEach(function (s) {
            try {
                s.destroy();
            } catch (e) { /* ignore */ }
        });
        cellSortables = [];
    }

    function syncSortableListToState(listEl) {
        var cell = listEl.closest('.envelope-grid-cell');
        if (!cell) {
            return;
        }
        var row = parseInt(cell.getAttribute('data-row'), 10);
        var col = parseInt(cell.getAttribute('data-col'), 10);
        if (isNaN(row) || isNaN(col)) {
            return;
        }
        var chips = listEl.querySelectorAll('.envelope-grid-chip[data-uid]');
        var spanRef = null;
        if (chips.length > 0) {
            spanRef = state.items.find(function (x) {
                return x.uid === chips[0].getAttribute('data-uid');
            }) || null;
        }
        chips.forEach(function (chip, idx) {
            var itemUid = chip.getAttribute('data-uid');
            var it = state.items.find(function (x) { return x.uid === itemUid; });
            if (!it) {
                return;
            }
            it.row = row;
            it.col = col;
            it.stack_order = idx;
            if (spanRef) {
                it.col_span = spanRef.col_span || 1;
                it.row_span = spanRef.row_span || 1;
            }
        });
    }

    function onCellItemsSorted(evt) {
        if (!evt.from || !evt.to) {
            return;
        }
        syncSortableListToState(evt.from);
        if (evt.from !== evt.to) {
            syncSortableListToState(evt.to);
        }
        var crossCell = evt.from !== evt.to;
        if (crossCell) {
            renderGrid();
        } else {
            try {
                renderPreview();
            } catch (previewErr) {
                console.error('Envelope preview:', previewErr);
            }
        }
    }

    function initCellSortables() {
        destroyCellSortables();
        if (typeof Sortable === 'undefined') {
            return;
        }
        document.querySelectorAll('#envelope-grid-editor .envelope-grid-cell-items').forEach(function (list) {
            cellSortables.push(new Sortable(list, {
                group: 'envelope-cell-items',
                animation: 150,
                handle: '.envelope-chip-sort-handle',
                draggable: '.envelope-grid-chip',
                ghostClass: 'sortable-ghost',
                chosenClass: 'sortable-chosen',
                filter: '.envelope-chip-no-drag',
                preventOnFilter: true,
                onEnd: onCellItemsSorted
            }));
        });
    }

    function addItemAt(type, row, col) {
        var existing = getItemsAtCell(row, col);
        var item = {
            uid: uid(),
            element_type: type,
            row: row,
            col: col,
            col_span: 1,
            row_span: 1,
            enabled: true,
            show_label: type === 'codigo_barras' ? true : (type !== 'custom_image' && type !== 'qr'),
            custom_label: '',
            font_size_pt: 10,
            text_align: 'left',
            vertical_align: 'top',
            margin_mm: { top: 0, right: 0, bottom: 0, left: 0 },
            text_flow: 'horizontal',
            font_weight: 'normal'
        };
        if (type === 'custom_text') {
            item.custom_value = '';
        }
        if (type === 'custom_image') {
            item.image_file = '';
            item.width_percent = 80;
            item.height_percent = 60;
        }
        if (type === 'qr') {
            item.qr_size_mm = 35;
            item.show_label = false;
        }
        if (type === 'codigo_barras') {
            item.barcode_height_mm = 14;
            item.barcode_width_mm = 60;
            item.show_label = true;
        }
        item.stack_order = nextStackOrderInCell(row, col);
        if (existing.length > 0) {
            item.col_span = existing[0].col_span || 1;
            item.row_span = existing[0].row_span || 1;
            state.items.push(item);
            openItemModal(item.uid);
            return;
        }
        var existingSpan = getSpanAt(row, col);
        if (existingSpan.col_span > 1 || existingSpan.row_span > 1) {
            item.col_span = existingSpan.col_span;
            item.row_span = existingSpan.row_span;
        }
        state.merges = (state.merges || []).filter(function (m) {
            return !(m.row === row && m.col === col);
        });
        clearRegion(row, col, item.col_span, item.row_span, true);
        state.items.push(item);
        openItemModal(item.uid);
    }

    function moveItem(itemUid, row, col) {
        var it = state.items.find(function (x) { return x.uid === itemUid; });
        if (!it) {
            return;
        }
        var targetItems = getItemsAtCell(row, col).filter(function (x) { return x.uid !== itemUid; });
        if (targetItems.length > 0) {
            it.col_span = targetItems[0].col_span || 1;
            it.row_span = targetItems[0].row_span || 1;
        }
        it.stack_order = nextStackOrderInCell(row, col, itemUid);
        it.row = row;
        it.col = col;
    }

    function appendPreviewItemContent(host, it, scale) {
        if (it.element_type === 'custom_image') {
            var img = document.createElement('img');
            img.className = 'envelope-preview-img';
            img.alt = '';
            var src = it.image_file ? imageBaseUrl + it.image_file.split('/').pop() : '';
            if (pendingImageFiles[it.uid]) {
                src = pendingImageFiles[it.uid];
            }
            if (src) {
                img.src = src;
            }
            img.style.width = (it.width_percent || 80) + '%';
            img.style.height = (it.height_percent || 60) + '%';
            img.style.maxWidth = '100%';
            host.appendChild(img);
            return;
        }
        if (it.element_type === 'qr') {
            var qr = document.createElement('div');
            qr.className = 'envelope-preview-qr';
            var qrMm = clampQrSizeMm(it.qr_size_mm);
            qr.style.width = mmToPx(qrMm, scale) + 'px';
            qr.style.height = mmToPx(qrMm, scale) + 'px';
            qr.style.background = '#dee2e6';
            qr.style.flexShrink = '0';
            qr.title = 'QR';
            host.appendChild(qr);
            return;
        }
        if (it.element_type === 'codigo_barras') {
            appendBarcodePreview(host, it);
            return;
        }
        host.appendChild(createPreviewTextBlock(it, scale));
    }

    function appendBarcodePreview(host, it) {
        var wrap = document.createElement('div');
        wrap.className = 'envelope-barcode-block text-center';
        var showName = it.show_label !== false;
        if (showName) {
            var nameEl = document.createElement('div');
            nameEl.className = 'orden-barcode-patient-name';
            nameEl.textContent = elSamples.paciente_nombre || 'Juan Pérez García';
            wrap.appendChild(nameEl);
        }
        var box = document.createElement('div');
        box.className = 'orden-barcode-box envelope-barcode-box';
        var svg = document.createElementNS('http://www.w3.org/2000/svg', 'svg');
        svg.setAttribute('class', 'envelope-barcode-svg orden-barcode-label-svg');
        svg.setAttribute('data-barcode-value', elSamples.numero_orden || 'ORD-001');
        svg.setAttribute('data-barcode-height', String(it.barcode_height_mm || 14));
        svg.setAttribute('data-barcode-width', String(it.barcode_width_mm || 60));
        svg.setAttribute('data-barcode-size-pct', '100');
        box.style.maxWidth = (it.barcode_width_mm || 60) + 'mm';
        var fallback = document.createElement('div');
        fallback.className = 'envelope-barcode-fallback';
        fallback.style.display = 'none';
        fallback.textContent = elSamples.numero_orden || 'ORD-001';
        box.appendChild(svg);
        box.appendChild(fallback);
        wrap.appendChild(box);
        host.appendChild(wrap);
        if (typeof window.renderEnvelopeBarcodeSvg === 'function') {
            window.renderEnvelopeBarcodeSvg(svg);
        } else if (fallback) {
            fallback.style.display = '';
        }
    }

    function getPreviewLimits() {
        var wrap = document.querySelector('.envelope-editor-wrap');
        if (!wrap || !window.getComputedStyle) {
            return { maxW: 720, maxH: 460, cap: 2 };
        }
        var cs = getComputedStyle(wrap);
        return {
            maxW: parseFloat(cs.getPropertyValue('--env-preview-max-width')) || 720,
            maxH: parseFloat(cs.getPropertyValue('--env-preview-max-height')) || 460,
            cap: parseFloat(cs.getPropertyValue('--env-preview-scale-cap')) || 2
        };
    }

    function renderPreview() {
        var outer = document.getElementById('envelope-preview-outer');
        var inner = document.getElementById('envelope-preview-inner');
        var dimsEl = document.getElementById('envelope-preview-dims');
        if (!outer || !inner || !dimsEl) {
            return;
        }
        var limits = getPreviewLimits();
        var scale = Math.min(
            limits.maxW / state.width_mm,
            limits.maxH / state.height_mm,
            limits.cap
        );
        outer.style.width = Math.round(state.width_mm * scale) + 'px';
        outer.style.height = Math.round(state.height_mm * scale) + 'px';
        dimsEl.textContent = state.width_mm + ' × ' + state.height_mm + ' mm';

        inner.style.gridTemplateColumns = 'repeat(' + state.columns + ', 1fr)';
        inner.style.gridTemplateRows = 'repeat(' + state.rows + ', 1fr)';
        inner.style.alignContent = 'start';
        inner.innerHTML = '';

        var occupied = {};
        function markOccupiedExceptAnchor(row, col, cs, rs) {
            var rr, cc;
            for (rr = row; rr < row + rs; rr++) {
                for (cc = col; cc < col + cs; cc++) {
                    if (rr === row && cc === col) {
                        continue;
                    }
                    occupied[rr + ',' + cc] = true;
                }
            }
        }
        (state.merges || []).forEach(function (m) {
            markOccupiedExceptAnchor(m.row, m.col, m.col_span || 1, m.row_span || 1);
        });

        var cellGroups = {};
        state.items.forEach(function (it) {
            if (!it.enabled) {
                return;
            }
            var anchor = it.row + ',' + it.col;
            if (!cellGroups[anchor]) {
                cellGroups[anchor] = [];
            }
            cellGroups[anchor].push(it);
        });

        Object.keys(cellGroups).forEach(function (anchor) {
            var items = cellGroups[anchor].sort(function (a, b) {
                return (a.stack_order || 0) - (b.stack_order || 0);
            });
            if (!items.length) {
                return;
            }
            var it0 = items[0];
            if (occupied[anchor]) {
                return;
            }
            markOccupiedExceptAnchor(it0.row, it0.col, it0.col_span || 1, it0.row_span || 1);

            var cell = document.createElement('div');
            cell.className = 'envelope-preview-cell';
            cell.style.gridColumn = (it0.col + 1) + ' / span ' + (it0.col_span || 1);
            cell.style.gridRow = (it0.row + 1) + ' / span ' + (it0.row_span || 1);
            applyPreviewCellContainer(cell, items);

            if (items.length === 1) {
                var solo = document.createElement('div');
                solo.className = 'envelope-preview-stack-item';
                applyStackItemStyle(solo, items[0], scale, 'column');
                appendPreviewItemContent(solo, items[0], scale);
                cell.appendChild(solo);
            } else {
                var stack = document.createElement('div');
                stack.className = 'envelope-preview-stack';
                var stackDir = applyPreviewStackLayout(stack, items, scale);
                items.forEach(function (it) {
                    var block = document.createElement('div');
                    block.className = 'envelope-preview-stack-item';
                    applyStackItemStyle(block, it, scale, stackDir);
                    appendPreviewItemContent(block, it, scale);
                    stack.appendChild(block);
                });
                cell.appendChild(stack);
            }
            inner.appendChild(cell);
        });
        if (typeof window.initEnvelopeBarcodes === 'function') {
            window.initEnvelopeBarcodes(inner);
        }
    }

    var modalEl, modalBs;

    function openItemModal(itemUid) {
        var it = state.items.find(function (x) { return x.uid === itemUid; });
        if (!it) {
            return;
        }
        document.getElementById('env_modal_uid').value = it.uid;
        document.getElementById('env_modal_type_label').textContent = labelFor(it.element_type) + ' (' + it.element_type + ')';
        document.getElementById('env_modal_enabled').checked = it.enabled !== false;
        document.getElementById('env_modal_show_label').checked = it.show_label !== false;
        document.getElementById('env_modal_custom_label').value = it.custom_label || '';
        document.getElementById('env_modal_font_size').value = it.font_size_pt || 10;
        document.getElementById('env_modal_align').value = it.text_align || 'left';
        document.getElementById('env_modal_valign').value = it.vertical_align || 'top';
        fillModalMargins(it.margin_mm);
        document.getElementById('env_modal_bold').checked = it.font_weight === 'bold';
        document.getElementById('env_modal_text_flow').value = normalizeTextFlow(it.text_flow);
        document.getElementById('env_modal_col_span').value = it.col_span || 1;
        document.getElementById('env_modal_row_span').value = it.row_span || 1;
        document.getElementById('env_modal_col_span').max = String(state.columns - it.col);
        document.getElementById('env_modal_row_span').max = String(state.rows - it.row);

        var isImage = it.element_type === 'custom_image';
        var isQr = it.element_type === 'qr';
        var isBarcode = it.element_type === 'codigo_barras';
        var isCustomText = it.element_type === 'custom_text';
        var labelWrap = document.getElementById('env_modal_label_wrap');
        var showLabelLbl = document.getElementById('env_modal_show_label_lbl');
        document.getElementById('env_modal_text_wrap').style.display = isImage || isQr || isBarcode ? 'none' : '';
        document.getElementById('env_modal_custom_text_wrap').style.display = isCustomText ? '' : 'none';
        document.getElementById('env_modal_qr_wrap').style.display = isQr ? '' : 'none';
        document.getElementById('env_modal_barcode_wrap').style.display = isBarcode ? '' : 'none';
        document.getElementById('env_modal_image_wrap').style.display = isImage ? '' : 'none';
        var customLblRow = document.getElementById('env_modal_custom_label_row');
        if (isBarcode) {
            labelWrap.style.display = '';
            if (showLabelLbl) {
                showLabelLbl.textContent = 'Mostrar nombre del paciente (como en impresión de código de barras)';
            }
            if (customLblRow) {
                customLblRow.style.display = 'none';
            }
            document.getElementById('env_modal_barcode_width').value = it.barcode_width_mm || 60;
            document.getElementById('env_modal_barcode_height').value = it.barcode_height_mm || 14;
        } else {
            if (showLabelLbl) {
                showLabelLbl.textContent = 'Mostrar etiqueta';
            }
            labelWrap.style.display = isImage ? 'none' : '';
            if (customLblRow) {
                customLblRow.style.display = '';
            }
        }

        if (isCustomText) {
            document.getElementById('env_modal_custom_value').value = it.custom_value || '';
        }
        if (isQr) {
            document.getElementById('env_modal_qr_size').value = it.qr_size_mm || 35;
        }
        if (isImage) {
            var fileInput = document.getElementById('env_modal_image_file');
            fileInput.name = 'item_images[' + it.uid + ']';
            fileInput.value = '';
            document.getElementById('env_modal_img_width_pct').value = it.width_percent || 80;
            document.getElementById('env_modal_img_height_pct').value = it.height_percent || 60;
            document.getElementById('env_modal_img_width_val').textContent = (it.width_percent || 80) + '%';
            document.getElementById('env_modal_img_height_val').textContent = (it.height_percent || 60) + '%';
            var prev = document.getElementById('env_modal_image_preview');
            prev.innerHTML = '';
            var src = it.image_file ? imageBaseUrl + it.image_file.split('/').pop() : (pendingImageFiles[it.uid] || '');
            if (src) {
                var im = document.createElement('img');
                im.src = src;
                im.className = 'img-fluid border rounded';
                im.style.maxHeight = '120px';
                prev.appendChild(im);
            }
        }
        if (modalBs) {
            modalBs.show();
        }
    }

    function applyModal() {
        var itemUid = document.getElementById('env_modal_uid').value;
        var it = state.items.find(function (x) { return x.uid === itemUid; });
        if (!it) {
            return;
        }
        it.enabled = document.getElementById('env_modal_enabled').checked;
        it.show_label = document.getElementById('env_modal_show_label').checked;
        it.custom_label = document.getElementById('env_modal_custom_label').value.trim();
        it.font_size_pt = parseInt(document.getElementById('env_modal_font_size').value, 10) || 10;
        it.text_align = document.getElementById('env_modal_align').value;
        it.vertical_align = document.getElementById('env_modal_valign').value;
        it.margin_mm = readModalMargins();
        it.font_weight = document.getElementById('env_modal_bold').checked ? 'bold' : 'normal';
        if (!isNonTextField(it.element_type)) {
            it.text_flow = normalizeTextFlow(document.getElementById('env_modal_text_flow').value);
        }
        var newColSpan = clamp(parseInt(document.getElementById('env_modal_col_span').value, 10) || 1, 1, state.columns - it.col);
        var newRowSpan = clamp(parseInt(document.getElementById('env_modal_row_span').value, 10) || 1, 1, state.rows - it.row);
        clearRegion(it.row, it.col, newColSpan, newRowSpan, true);
        getItemsAtCell(it.row, it.col).forEach(function (cellIt) {
            cellIt.col_span = newColSpan;
            cellIt.row_span = newRowSpan;
        });
        it.col_span = newColSpan;
        it.row_span = newRowSpan;
        state.merges = (state.merges || []).filter(function (m) {
            return !(m.row === it.row && m.col === it.col);
        });

        if (it.element_type === 'custom_text') {
            it.custom_value = document.getElementById('env_modal_custom_value').value;
        }
        if (it.element_type === 'qr') {
            it.qr_size_mm = parseInt(document.getElementById('env_modal_qr_size').value, 10) || 35;
        }
        if (it.element_type === 'codigo_barras') {
            it.barcode_width_mm = clamp(parseInt(document.getElementById('env_modal_barcode_width').value, 10) || 60, 25, 120);
            it.barcode_height_mm = clamp(parseInt(document.getElementById('env_modal_barcode_height').value, 10) || 14, 8, 40);
        }
        if (it.element_type === 'custom_image') {
            it.width_percent = parseInt(document.getElementById('env_modal_img_width_pct').value, 10) || 80;
            it.height_percent = parseInt(document.getElementById('env_modal_img_height_pct').value, 10) || 60;
            var fileInput = document.getElementById('env_modal_image_file');
            if (fileInput.files && fileInput.files[0]) {
                pendingImageFileObjects[it.uid] = fileInput.files[0];
                var reader = new FileReader();
                reader.onload = function (ev) {
                    pendingImageFiles[it.uid] = ev.target.result;
                    renderGrid();
                };
                reader.readAsDataURL(fileInput.files[0]);
            }
        }
        if (modalBs) {
            modalBs.hide();
        }
        renderGrid();
    }

    function deleteModalItem() {
        var itemUid = document.getElementById('env_modal_uid').value;
        state.items = state.items.filter(function (x) { return x.uid !== itemUid; });
        delete pendingImageFiles[itemUid];
        delete pendingImageFileObjects[itemUid];
        if (modalBs) {
            modalBs.hide();
        }
        renderGrid();
    }

    function serializeLayoutForSave() {
        readControlsStrict(true);
        var json = JSON.stringify(state);
        var field = document.getElementById('layout_json');
        if (field) {
            field.value = json;
        }
        return json;
    }

    function mountImageInputsInForm() {
        var pool = document.getElementById('envelope-image-uploads');
        if (!pool) {
            return;
        }
        pool.innerHTML = '';
        Object.keys(pendingImageFileObjects).forEach(function (uid) {
            var file = pendingImageFileObjects[uid];
            if (!file) {
                return;
            }
            var clone = document.createElement('input');
            clone.type = 'file';
            clone.name = 'item_images[' + uid + ']';
            clone.className = 'd-none';
            try {
                var dt = new DataTransfer();
                dt.items.add(file);
                clone.files = dt.files;
            } catch (err) {
                return;
            }
            pool.appendChild(clone);
        });
    }

    window.envelopePrepareSave = function () {
        try {
            serializeLayoutForSave();
            mountImageInputsInForm();
            var field = document.getElementById('layout_json');
            if (!field || !field.value || field.value === '') {
                alert('No se pudo preparar el diseño. Recargue la página e intente de nuevo.');
                return false;
            }
            return true;
        } catch (err) {
            console.error(err);
            alert('Error al preparar el diseño: ' + (err.message || err));
            return false;
        }
    };

    function bindForm() {
        var form = document.getElementById('envelope_tpl_form');
        if (!form) {
            return;
        }
        form.addEventListener('submit', function () {
            window.envelopePrepareSave();
        }, true);
    }

    function onGridDimensionsChange() {
        readControlsStrict(true);
        var maxR = state.rows;
        var maxC = state.columns;
        state.items = state.items.filter(function (it) {
            return it.row < maxR && it.col < maxC;
        });
        state.merges = (state.merges || []).filter(function (m) {
            return m.row < maxR && m.col < maxC;
        });
        syncControlsFromState();
        renderGrid();
    }

    function tryApplyDimensionsFromInput(el, kind) {
        if (!el || el.value === '') {
            return;
        }
        var v = parseInt(el.value, 10);
        if (isNaN(v)) {
            return;
        }
        var min = 1;
        var max = kind === 'rows' ? 12 : 8;
        if (v < min || v > max) {
            return;
        }
        var prevR = state.rows;
        var prevC = state.columns;
        readControlsStrict(true);
        if (state.rows === prevR && state.columns === prevC) {
            return;
        }
        onGridDimensionsChange();
    }

    function bindControls() {
        var colsEl = document.getElementById('env_cols');
        var rowsEl = document.getElementById('env_rows');
        if (colsEl) {
            colsEl.addEventListener('input', function () { tryApplyDimensionsFromInput(colsEl, 'cols'); });
            colsEl.addEventListener('change', function () { onGridDimensionsChange(); });
        }
        if (rowsEl) {
            rowsEl.addEventListener('input', function () { tryApplyDimensionsFromInput(rowsEl, 'rows'); });
            rowsEl.addEventListener('change', function () { onGridDimensionsChange(); });
        }

        ['env_size_key', 'env_width_mm', 'env_height_mm'].forEach(function (id) {
            var el = document.getElementById(id);
            if (!el) {
                return;
            }
            el.addEventListener('change', function () {
                if (id === 'env_size_key') {
                    var sk = el.value;
                    var customWrap = document.getElementById('env_custom_size_wrap');
                    if (customWrap) {
                        customWrap.style.display = sk === 'custom' ? '' : 'none';
                    }
                    var sizes = getEnvelopeSizes();
                    if (sk !== 'custom' && sizes[sk]) {
                        var wEl = document.getElementById('env_width_mm');
                        var hEl = document.getElementById('env_height_mm');
                        if (wEl) {
                            wEl.value = sizes[sk].width_mm;
                        }
                        if (hEl) {
                            hEl.value = sizes[sk].height_mm;
                        }
                    }
                }
                readControls();
                renderGrid();
            });
        });

        var btnApply = document.getElementById('env_modal_apply');
        var btnDelete = document.getElementById('env_modal_delete');
        var imgW = document.getElementById('env_modal_img_width_pct');
        var imgH = document.getElementById('env_modal_img_height_pct');
        if (btnApply) {
            btnApply.addEventListener('click', applyModal);
        }
        if (btnDelete) {
            btnDelete.addEventListener('click', deleteModalItem);
        }
        if (imgW) {
            imgW.addEventListener('input', function () {
                var lbl = document.getElementById('env_modal_img_width_val');
                if (lbl) {
                    lbl.textContent = imgW.value + '%';
                }
            });
        }
        if (imgH) {
            imgH.addEventListener('input', function () {
                var lbl = document.getElementById('env_modal_img_height_val');
                if (lbl) {
                    lbl.textContent = imgH.value + '%';
                }
            });
        }
    }

    document.addEventListener('DOMContentLoaded', function () {
        try {
            var freshBoot = window.ENVELOPE_EDITOR_BOOT || {};
            state = normalizeLayout(freshBoot.layout || state);
            if (freshBoot.elementLabels && Object.keys(freshBoot.elementLabels).length) {
                elLabels = freshBoot.elementLabels;
            }
            if (freshBoot.elementSamples && Object.keys(freshBoot.elementSamples).length) {
                elSamples = freshBoot.elementSamples;
            }
            if (freshBoot.envelopeSizes && typeof freshBoot.envelopeSizes === 'object') {
                envelopeSizes = freshBoot.envelopeSizes;
            }
            if (freshBoot.imageBaseUrl) {
                imageBaseUrl = freshBoot.imageBaseUrl;
            }

            modalEl = document.getElementById('envItemModal');
            if (modalEl && window.bootstrap) {
                modalBs = new bootstrap.Modal(modalEl);
            }
            syncControlsFromState();
            bindPaletteDnD();
            buildPalette();
            bindMatrixDnD();
            bindForm();
            bindControls();

            renderGrid();
        } catch (err) {
            console.error('Envelope editor init:', err);
        }
    });
})();
