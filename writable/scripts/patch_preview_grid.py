# -*- coding: utf-8 -*-
"""Patch pdf_templates_edit.php: grid-based preview matching editable matrix."""
from pathlib import Path

path = Path(r"c:\wamp64\www\laboratorio\app\Views\config\pdf_templates_edit.php")
text = path.read_text(encoding="utf-8")

# 1) colsForList + rowsForSectionKey
old_cols = """    function colsForList(ul) {
        if (ul === headerList) return clampCols(secColsH.value);
        if (ul === patientList) return clampCols(secColsP.value);
        if (ul === labFirmasList) return clampCols(secColsL ? secColsL.value : '3');
        return clampCols(secColsF.value);
    }

    function rowsForSectionKey(sectionKey) {
        if (sectionKey === 'header') return clampRows(secRowsH ? secRowsH.value : '3');
        if (sectionKey === 'patient_doctor') return clampRows(secRowsP ? secRowsP.value : '4');
        if (sectionKey === 'lab_firmas') return clampRows(secRowsL ? secRowsL.value : '3');
        return clampRows(secRowsF ? secRowsF.value : '2');
    }"""

new_cols = """    function colsForList(ul) {
        if (!ul) return 3;
        var sectionKey = ul.getAttribute('data-section') || '';
        var sel = document.querySelector('.pdf-sec-cols[data-pdf-section="' + sectionKey + '"]');
        if (sel && String(sel.value || '').trim() !== '') return clampCols(sel.value);
        if (ul === headerList && secColsH) return clampCols(secColsH.value);
        if (ul === patientList && secColsP) return clampCols(secColsP.value);
        if (ul === labFirmasList && secColsL) return clampCols(secColsL.value);
        if (secColsF) return clampCols(secColsF.value);
        return 3;
    }

    function rowsForSectionKey(sectionKey) {
        var sel = document.querySelector('.pdf-sec-rows[data-pdf-section="' + sectionKey + '"]');
        if (sel && String(sel.value || '').trim() !== '') return clampRows(sel.value);
        if (sectionKey === 'header') return clampRows(secRowsH ? secRowsH.value : '3');
        if (sectionKey === 'patient_doctor') return clampRows(secRowsP ? secRowsP.value : '4');
        if (sectionKey === 'lab_firmas') return clampRows(secRowsL ? secRowsL.value : '3');
        return clampRows(secRowsF ? secRowsF.value : '2');
    }

    function collectSectionGridRegions(sectionKey) {
        var ul = listForSection(sectionKey);
        if (!ul) return { cols: 3, rows: 1, regions: {} };
        ensureGridPlacementForSection(sectionKey);
        var cols = colsForList(ul);
        var rows = rowsForSectionKey(sectionKey);
        var itemsByRegion = {};
        ul.querySelectorAll('.pdf-instance-item').forEach(function(li) {
            var colSel = li.querySelector('.instance-column');
            var enabledVal = colSel ? parseInt(colSel.value, 10) : -1;
            if (isNaN(enabledVal) || enabledVal < 0) return;
            var p = readGridPlacement(li, sectionKey);
            var row = p.row == null ? 0 : Math.max(0, Math.min(rows - 1, p.row));
            li.setAttribute('data-grid-row', String(row));
            var key = row + ':' + p.col + ':' + p.span;
            if (!itemsByRegion[key]) itemsByRegion[key] = [];
            itemsByRegion[key].push({ li: li, row: row, col: p.col, span: p.span, stack: p.stack == null ? 0 : p.stack });
        });
        Object.keys(itemsByRegion).forEach(function(key) {
            itemsByRegion[key].sort(function(a, b) { return a.stack - b.stack; });
        });
        return { cols: cols, rows: rows, regions: itemsByRegion };
    }"""

if old_cols not in text:
    raise SystemExit("colsForList block not found")
text = text.replace(old_cols, new_cols, 1)

# 2) buildSectionGridEditor - use collectSectionGridRegions
old_editor = """    function buildSectionGridEditor(sectionKey) {
        var ul = listForSection(sectionKey);
        var root = gridRootForSection(sectionKey);
        if (!ul || !root) return;
        ensureGridPlacementForSection(sectionKey);
        var cols = colsForList(ul);
        var rows = rowsForSectionKey(sectionKey);
        root.innerHTML = '';
        var itemsByRegion = {};
        ul.querySelectorAll('.pdf-instance-item').forEach(function(li) {
            var colSel = li.querySelector('.instance-column');
            var enabledVal = colSel ? parseInt(colSel.value, 10) : -1;
            if (isNaN(enabledVal) || enabledVal < 0) return;
            var p = readGridPlacement(li, sectionKey);
            var row = p.row == null ? 0 : Math.min(rows - 1, p.row);
            li.setAttribute('data-grid-row', String(row));
            var key = row + ':' + p.col + ':' + p.span;
            if (!itemsByRegion[key]) itemsByRegion[key] = [];
            itemsByRegion[key].push({ li: li, row: row, col: p.col, span: p.span, stack: p.stack == null ? 0 : p.stack });
        });
        Object.keys(itemsByRegion).forEach(function(key) {
            itemsByRegion[key].sort(function(a, b) { return a.stack - b.stack; });
        });

        for (var row = 0; row < rows; row++) {"""

new_editor = """    function buildSectionGridEditor(sectionKey) {
        var root = gridRootForSection(sectionKey);
        if (!root) return;
        var gridData = collectSectionGridRegions(sectionKey);
        var cols = gridData.cols;
        var rows = gridData.rows;
        var itemsByRegion = gridData.regions;
        root.innerHTML = '';

        for (var row = 0; row < rows; row++) {"""

if old_editor not in text:
    raise SystemExit("buildSectionGridEditor block not found")
text = text.replace(old_editor, new_editor, 1)

# 3) CSS
old_css = """.pdf-preview-grid-row { display: flex; gap: 8px; border-bottom: 1px solid #dee2e6; padding-bottom: 8px; }
.pdf-preview-grid-cell { flex: 1; min-width: 0; font-size: 0.75rem; }"""

new_css = """.pdf-preview-matrix { display: flex; flex-direction: column; gap: 0.35rem; width: 100%; }
.pdf-preview-scope .pdf-preview-grid-row {
    display: grid;
    gap: 0;
    width: 100%;
    min-height: 2.25rem;
}
.pdf-preview-scope .pdf-preview-grid-cell {
    min-width: 0;
    min-height: 2rem;
    box-sizing: border-box;
    word-wrap: break-word;
    overflow-wrap: break-word;
    border: 1px solid rgba(0, 0, 0, 0.1);
}
.pdf-preview-scope .pdf-preview-grid-cell--empty {
    background: rgba(0, 0, 0, 0.03);
}
.pdf-preview-scope .pdf-preview-grid-cell .pdf-el-item {
    width: 100%;
    box-sizing: border-box;
}
.pdf-preview-scope .pdf-el-item--h-left { text-align: left !important; }
.pdf-preview-scope .pdf-el-item--h-center { text-align: center !important; }
.pdf-preview-scope .pdf-el-item--h-right { text-align: right !important; }
.pdf-preview-scope .pdf-el-item--v-bottom { margin-top: auto !important; }"""

if old_css not in text:
    raise SystemExit("preview CSS block not found")
text = text.replace(old_css, new_css, 1)

# 4) Replace preview functions block
start = text.index("    function buildMatrixRowFromItems(rowItems, n) {")
end = text.index("    function rebuildAllPreviews() {")

new_block = r'''    var PREVIEW_PD_TYPES = ['paciente_nombre', 'paciente_genero', 'paciente_edad', 'paciente_telefono', 'diagnostico_presuntivo', 'medico', 'fecha_recepcion', 'fecha_reporte', 'numero_orden'];
    var PREVIEW_HEADER_LIKE_TYPES = ['logo', 'lab_company', 'lab_address', 'paciente_institucion', 'lab_phone', 'lab_email', 'lab_website', 'pdf_pages_total', 'pdf_pagination', 'qr'];

    function readPreviewStyleContext() {
        var ps = readCardHeaderStyleForJson();
        return { ftFooter: ps.footer_grid, hgHeader: ps.header_grid, pdHeader: ps.patient_doctor_grid };
    }

    function buildInstancePreviewHtml(li, sectionKey, ctx) {
        ctx = ctx || readPreviewStyleContext();
        var type = li.getAttribute('data-element-type') || '';
        var sample = (window._elementSamples && window._elementSamples[type]) ? window._elementSamples[type] : type;
        var shortL = (window._labelsShort && window._labelsShort[type]) ? window._labelsShort[type] : '';
        if (type === 'custom_text') {
            return '<div class="pdf-custom-text">' + buildCustomTextPreviewInnerHtml(readInstanceCustomText(li)) + '</div>';
        }
        if (sectionKey === 'footer' && ctx.ftFooter && (type === 'footer_company' || type === 'footer_generated' || type === 'footer_policy')) {
            return buildFooterPreviewPieceHtml(type, sample, ctx.ftFooter, readInstanceTextStyle(li));
        }
        var styleWrap = textStyleToInlineCss(readInstanceTextStyle(li));
        if ((sectionKey === 'patient_doctor' || sectionKey === 'footer') && PREVIEW_PD_TYPES.indexOf(type) >= 0 && ctx.pdHeader) {
            var pdHeader = ctx.pdHeader;
            var showLpd = !!pdHeader['show_label_' + type];
            var lblPd = String(pdHeader['label_' + type] != null ? pdHeader['label_' + type] : '').trim();
            var inlinePd = (pdHeader['label_' + type + '_line_mode'] === 'inline');
            var gapPd = parseInt(pdHeader['label_' + type + '_value_gap_px'], 10); if (isNaN(gapPd)) gapPd = 0;
            var mtPd = parseInt(pdHeader['label_' + type + '_space_above_px'], 10); if (isNaN(mtPd)) mtPd = 0;
            var mbPd = parseInt(pdHeader['label_' + type + '_space_below_px'], 10); if (isNaN(mbPd)) mbPd = 0;
            var ovGapEl = li.querySelector('.instance-pd-label-value-gap-px');
            var ovMtEl = li.querySelector('.instance-pd-space-above-px');
            var ovMbEl = li.querySelector('.instance-pd-space-below-px');
            if (ovGapEl) { var ovG = parseInt(ovGapEl.value, 10); if (!isNaN(ovG)) gapPd = Math.max(0, Math.min(40, ovG)); }
            if (ovMtEl) { var ovA = parseInt(ovMtEl.value, 10); if (!isNaN(ovA)) mtPd = Math.max(0, Math.min(40, ovA)); }
            if (ovMbEl) { var ovB = parseInt(ovMbEl.value, 10); if (!isNaN(ovB)) mbPd = Math.max(0, Math.min(40, ovB)); }
            var valPd = escapeHtml(sample);
            var lblEsc = escapeHtml(lblPd);
            var showLblText = showLpd && lblPd !== '';
            var stLblPd = 'color:' + (pdHeader['label_' + type + '_text_color'] || pdHeader.body_text_color || '#333')
                + ';font-size:' + (pdHeader['label_' + type + '_font_size_pt'] || pdHeader.font_size_pt || 9.5) + 'pt'
                + ';font-weight:' + (pdHeader['label_' + type + '_font_weight'] || pdHeader.font_weight || 'normal')
                + ';font-style:' + (pdHeader['label_' + type + '_font_style'] || pdHeader.font_style || 'normal')
                + ';text-transform:' + (pdHeader['label_' + type + '_text_transform'] || pdHeader.text_transform || 'none');
            if (inlinePd) {
                return '<div class="patient-line" style="padding-top:' + mtPd + 'px;padding-bottom:' + mbPd + 'px;">'
                    + (showLblText ? ('<span class="label" style="margin-right:' + gapPd + 'px;' + escapeHtml(stLblPd) + '">' + lblEsc + '</span>') : '')
                    + (showLblText ? ('<span style="' + escapeHtml(styleWrap) + '">' + valPd + '</span>') : ('<span style="margin-left:' + gapPd + 'px;display:inline-block;' + escapeHtml(styleWrap) + '">' + valPd + '</span>'))
                    + '</div>';
            }
            var valMtPd = showLblText ? 0 : (mtPd + gapPd);
            return (showLblText ? ('<div class="patient-line" style="padding-top:' + mtPd + 'px;padding-bottom:' + gapPd + 'px;"><span class="label" style="' + escapeHtml(stLblPd) + '">' + lblEsc + '</span></div>') : '')
                + '<div class="patient-line" style="padding-top:' + valMtPd + 'px;padding-bottom:' + mbPd + 'px;"><span style="' + escapeHtml(styleWrap) + '">' + valPd + '</span></div>';
        }
        if (sectionKey === 'footer' && PREVIEW_HEADER_LIKE_TYPES.indexOf(type) >= 0) {
            return buildHeaderPreviewPieceHtml(type, sample, ctx.hgHeader, readInstanceTextStyle(li));
        }
        if (sectionKey === 'header' && PREVIEW_HEADER_LIKE_TYPES.indexOf(type) >= 0) {
            return buildHeaderPreviewPieceHtml(type, sample, ctx.hgHeader, readInstanceTextStyle(li));
        }
        var rawLine = shortL ? ('<span class="pdf-preview-lbl">' + escapeHtml(shortL) + '</span> ' + escapeHtml(sample)) : escapeHtml(sample);
        return '<span style="' + escapeHtml(styleWrap) + '">' + rawLine + '</span>';
    }

    function cellPadCssForSection(sectionKey, rowGapPx) {
        if (sectionKey === 'footer') {
            var h = Math.max(0, Math.min(12, Math.round(rowGapPx / 2)));
            return h > 0 ? ('0 ' + h + 'px') : '0';
        }
        return '0 6px';
    }

    function emptyCellPadCssForSection(sectionKey, rowGapPx) {
        if (sectionKey === 'footer') {
            var h = Math.max(0, Math.min(12, Math.round(rowGapPx / 2)));
            return h > 0 ? ('0 ' + h + 'px') : '0';
        }
        return '0 4px';
    }

    function applyColumnBorderToCell(el, colIndex, gridStyle) {
        if (!el || !gridStyle || colIndex <= 0) return;
        var w = parseInt(gridStyle.column_border_width_px, 10);
        if (isNaN(w) || w <= 0) return;
        el.style.borderLeft = w + 'px solid ' + String(gridStyle.column_border_color || '#DDDDDD');
    }

    function applyPreviewSectionTheme(wrap, sectionKey, gridStyle) {
        if (!wrap) return;
        var varPrefix = null;
        if (sectionKey === 'header') varPrefix = 'pdf-hg';
        else if (sectionKey === 'patient_doctor') varPrefix = 'pdf-pd';
        else if (sectionKey === 'footer') varPrefix = 'pdf-ft';
        if (!varPrefix || !gridStyle) return;
        var bg = gridStyle.body_transparent ? 'transparent' : (gridStyle.body_bg_color || '#ffffff');
        wrap.style.setProperty('--' + varPrefix + '-body-bg', bg);
        if (gridStyle.body_text_color) wrap.style.setProperty('--' + varPrefix + '-body-color', gridStyle.body_text_color);
        var colW = parseInt(gridStyle.column_border_width_px, 10);
        if (isNaN(colW)) colW = 0;
        wrap.style.setProperty('--' + varPrefix + '-column-border-width', Math.max(0, colW) + 'px');
        wrap.style.setProperty('--' + varPrefix + '-column-border-color', gridStyle.column_border_color || '#DDDDDD');
        if (sectionKey === 'footer' && gridStyle.section_top_border_enabled) {
            var bwFt = parseInt(gridStyle.section_top_border_width_px, 10);
            if (isNaN(bwFt)) bwFt = 1;
            wrap.style.setProperty('--pdf-ft-section-top-border-width', Math.max(0, bwFt) + 'px');
            wrap.style.setProperty('--pdf-ft-section-top-border-color', gridStyle.section_top_border_color || '#DDDDDD');
        }
        if (sectionKey === 'header') {
            var sepEl = document.getElementById('hs_separator_color');
            if (sepEl && isValidPdfHexJs(sepEl.value)) wrap.style.setProperty('--pdf-header-separator-color', sepEl.value.trim());
        }
    }

    function applyPreviewGridCellChrome(cell, colIndex, sectionKey, gridStyle, secSt, rowGapPx, rowIndex, isEmpty) {
        var pad = isEmpty ? emptyCellPadCssForSection(sectionKey, rowGapPx) : cellPadCssForSection(sectionKey, rowGapPx);
        cell.style.padding = pad;
        cell.style.lineHeight = String(secSt.line_height);
        if (rowIndex > 0 && rowGapPx > 0) cell.style.paddingTop = rowGapPx + 'px';
        if (gridStyle && !isEmpty) {
            cell.style.backgroundColor = gridStyle.body_transparent ? 'transparent' : (gridStyle.body_bg_color || '#ffffff');
            if (gridStyle.body_text_color) cell.style.color = gridStyle.body_text_color;
        }
        applyColumnBorderToCell(cell, colIndex, gridStyle);
    }

    function applyPreviewGridCellAlign(cell, regionItems) {
        if (!regionItems || !regionItems.length) return;
        cell.style.display = 'flex';
        cell.style.flexDirection = 'column';
        cell.style.alignItems = 'stretch';
        if (regionItems.length === 1) {
            var al = readInstanceAlign(regionItems[0].li);
            cell.style.textAlign = al.align_h;
            cell.style.justifyContent = al.align_v === 'bottom' ? 'flex-end' : 'flex-start';
            if (al.align_v === 'bottom') cell.style.minHeight = '28px';
        } else {
            cell.style.textAlign = 'left';
            cell.style.justifyContent = 'flex-start';
        }
    }

    function findRegionAt(itemsByRegion, row, col) {
        var found = null;
        Object.keys(itemsByRegion).some(function(key) {
            var parts = key.split(':');
            if (parseInt(parts[0], 10) !== row || parseInt(parts[1], 10) !== col) return false;
            found = { span: parseInt(parts[2], 10), items: itemsByRegion[key] };
            return true;
        });
        return found;
    }

    function appendPreviewInstanceToCell(cell, li, sectionKey) {
        var instAlign = readInstanceAlign(li);
        var divM = document.createElement('div');
        divM.className = 'pdf-el-item';
        divM.style.lineHeight = 'inherit';
        divM.style.width = '100%';
        divM.style.boxSizing = 'border-box';
        applyItemAlign(divM, { align_h: instAlign.align_h, align_v: instAlign.align_v });
        divM.innerHTML = buildInstancePreviewHtml(li, sectionKey, readPreviewStyleContext());
        cell.appendChild(divM);
    }

    function rebuildGridPreview(ul, previewEl) {
        if (!previewEl || !ul) return;
        var sectionKey = ul.getAttribute('data-section') || 'header';
        var gridData = collectSectionGridRegions(sectionKey);
        var cols = gridData.cols;
        var rows = gridData.rows;
        var itemsByRegion = gridData.regions;
        var secSt = readSectionStyleFromDom(sectionKey);
        var ctx = readPreviewStyleContext();
        var gridStyle = null;
        if (sectionKey === 'footer') gridStyle = ctx.ftFooter;
        else if (sectionKey === 'header') gridStyle = ctx.hgHeader;
        else if (sectionKey === 'patient_doctor') gridStyle = ctx.pdHeader;
        var ftFooter = sectionKey === 'footer' ? ctx.ftFooter : null;

        previewEl.innerHTML = '';
        var wrap = document.createElement('div');
        wrap.className = 'pdf-preview-scope';
        if (sectionKey === 'header') wrap.classList.add('pdf-hg-block', 'header', 'header-grid');
        else if (sectionKey === 'patient_doctor') wrap.classList.add('pdf-pd-block', 'patient-columns', 'patient-columns-grid');
        else if (sectionKey === 'footer') wrap.classList.add('pdf-ft-block', 'footer-grid');
        else if (sectionKey === 'lab_firmas') wrap.classList.add('lab-firmas-pdf-block');
        if (ftFooter && ftFooter.section_top_border_enabled) {
            var bw = parseInt(ftFooter.section_top_border_width_px, 10);
            if (isNaN(bw)) bw = 1;
            bw = Math.max(0, Math.min(6, bw));
            if (bw > 0) wrap.style.borderTop = bw + 'px solid ' + String(ftFooter.section_top_border_color || '#DDDDDD');
            wrap.style.paddingTop = '8px';
        }
        if (sectionKey !== 'header' && sectionKey !== 'footer') {
            wrap.style.borderBottom = '1px solid #dee2e6';
            wrap.style.paddingBottom = '8px';
        }
        var rowGapPx = readSectionRowGapPx(sectionKey);
        applyPreviewSectionTheme(wrap, sectionKey, gridStyle);

        var matrixRoot = document.createElement('div');
        matrixRoot.className = 'pdf-preview-matrix';
        for (var row = 0; row < rows; row++) {
            var rowEl = document.createElement('div');
            rowEl.className = 'pdf-preview-grid-row';
            rowEl.style.gridTemplateColumns = 'repeat(' + cols + ', minmax(0, 1fr))';
            rowEl.setAttribute('data-pdf-row', String(row));
            var occupied = {};
            Object.keys(itemsByRegion).forEach(function(key) {
                var parts = key.split(':');
                if (parseInt(parts[0], 10) !== row) return;
                var c = parseInt(parts[1], 10);
                var s = parseInt(parts[2], 10);
                for (var x = c; x < c + s; x++) occupied[x] = true;
            });
            for (var col = 0; col < cols; col++) {
                if (occupied[col] && !findRegionAt(itemsByRegion, row, col)) continue;
                var region = findRegionAt(itemsByRegion, row, col);
                var cell = document.createElement('div');
                cell.className = 'pdf-preview-grid-cell';
                if (region) {
                    cell.style.gridColumn = String(col + 1) + ' / span ' + region.span;
                    applyPreviewGridCellChrome(cell, col, sectionKey, gridStyle, secSt, rowGapPx, row, false);
                    applyPreviewGridCellAlign(cell, region.items);
                    region.items.forEach(function(rec) { appendPreviewInstanceToCell(cell, rec.li, sectionKey); });
                    col += region.span - 1;
                } else {
                    cell.classList.add('pdf-preview-grid-cell--empty');
                    applyPreviewGridCellChrome(cell, col, sectionKey, gridStyle, secSt, rowGapPx, row, true);
                }
                rowEl.appendChild(cell);
            }
            matrixRoot.appendChild(rowEl);
        }
        wrap.appendChild(matrixRoot);
        previewEl.appendChild(wrap);
    }

'''

text = text[:start] + new_block + text[end:]

# 5) Wire pdf-sec-cols/rows if missing
if "pdf-sec-cols" not in text.split("rebuildAllPreviews")[1][:2000]:
    old_wire = """    [secColsH, secColsP, secColsL, secColsF].forEach(function(inp) {
        if (!inp) return;
        inp.addEventListener('change', function() {
            inp.value = String(clampCols(inp.value));
            rebuildColumnSelects();
            rebuildAllPreviews();
        });
    });
    [secRowsH, secRowsP, secRowsL, secRowsF].forEach(function(inp) {
        if (!inp) return;
        inp.addEventListener('change', function() {
            inp.value = String(clampRows(inp.value));
            rebuildAllPreviews();
        });
    });"""
    new_wire = """    document.querySelectorAll('.pdf-sec-cols').forEach(function(inp) {
        inp.addEventListener('change', function() {
            inp.value = String(clampCols(inp.value));
            rebuildColumnSelects();
            rebuildAllPreviews();
        });
        inp.addEventListener('input', function() {
            inp.value = String(clampCols(inp.value));
            rebuildColumnSelects();
            rebuildAllPreviews();
        });
    });
    document.querySelectorAll('.pdf-sec-rows').forEach(function(inp) {
        inp.addEventListener('change', function() {
            inp.value = String(clampRows(inp.value));
            rebuildAllPreviews();
        });
        inp.addEventListener('input', function() {
            inp.value = String(clampRows(inp.value));
            rebuildAllPreviews();
        });
    });
    [secColsH, secColsP, secColsL, secColsF].forEach(function(inp) {
        if (!inp) return;
        inp.addEventListener('change', function() {
            inp.value = String(clampCols(inp.value));
            rebuildColumnSelects();
            rebuildAllPreviews();
        });
    });
    [secRowsH, secRowsP, secRowsL, secRowsF].forEach(function(inp) {
        if (!inp) return;
        inp.addEventListener('change', function() {
            inp.value = String(clampRows(inp.value));
            rebuildAllPreviews();
        });
    });"""
    if old_wire in text:
        text = text.replace(old_wire, new_wire, 1)

path.write_text(text, encoding="utf-8")
print("OK")
