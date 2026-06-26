#!/usr/bin/env python3
"""Estampa paginación del pie en PDF generado por Chromium (counter(page) no funciona en headless)."""
from __future__ import annotations

import json
import sys
from pathlib import Path


def mm_to_pt(mm: float) -> float:
    return mm * 72.0 / 25.4


def hex_to_rgb(color: str) -> tuple[float, float, float]:
    color = (color or '#333333').strip().lstrip('#')
    if len(color) != 6:
        return (0.2, 0.2, 0.2)
    return (
        int(color[0:2], 16) / 255.0,
        int(color[2:4], 16) / 255.0,
        int(color[4:6], 16) / 255.0,
    )


def build_text(slot: dict, page_number: int, page_count: int) -> str:
    fmt = slot.get('format') or 'page_of_total'
    prefix = slot.get('prefix') or ''
    if fmt == 'total_only':
        return str(page_count)
    return f'{prefix}{page_number} de {page_count}'


def compute_position(slot: dict, page_w: float, page_h: float, text_width: float) -> tuple[float, float]:
    mt = mm_to_pt(float(slot.get('mt') or 15))
    mr = mm_to_pt(float(slot.get('mr') or 15))
    mb = mm_to_pt(float(slot.get('mb') or 15))
    ml = mm_to_pt(float(slot.get('ml') or 15))
    font_size = float(slot.get('fontSize') or 10)
    line_height = max(1.0, float(slot.get('lineHeight') or 1.35))
    line_pt = font_size * line_height
    px_to_pt = 72.0 / 96.0

    if slot.get('zone') == 'footer' and int(slot.get('footerColumns') or 0) > 0:
        footer_cols = max(1, int(slot.get('footerColumns') or 1))
        grid_col = min(max(0, int(slot.get('gridColumn') or 0)), footer_cols - 1)
        grid_col_span = max(1, min(int(slot.get('gridColumnSpan') or 1), footer_cols - grid_col))
        grid_row = max(0, int(slot.get('gridRow') or 0))
        grid_stack = max(0, int(slot.get('gridStack') or 0))
        row_gap_pt = max(0.0, float(slot.get('footerRowGapPx') or 0)) * px_to_pt
        content_w = max(1.0, page_w - ml - mr)
        col_w = content_w / footer_cols
        cell_x0 = ml + (grid_col * col_w)
        cell_w = col_w * grid_col_span
        footer_reserve_pt = max(0.0, mm_to_pt(float(slot.get('footerReserveMm') or 0)))
        prepended_pt = max(0.0, mm_to_pt(float(slot.get('footerPrependedRowMm') or 0)))
        footer_top_y = page_h - mb - footer_reserve_pt
        pad_top_pt = 6.0 * px_to_pt
        row_offset = (grid_row * (line_pt + row_gap_pt)) + (grid_stack * line_pt)
        if slot.get('labelStacked'):
            row_offset += line_pt
        y_top = footer_top_y + pad_top_pt + prepended_pt + row_offset + (font_size * 0.82)
        y_top = max(mt + font_size, min(page_h - mb - font_size * 0.5, y_top))

        align = (slot.get('align') or 'left').lower()
        if align == 'right':
            x = cell_x0 + max(0.0, cell_w - text_width)
        elif align == 'center':
            x = cell_x0 + max(0.0, (cell_w - text_width) / 2)
        else:
            x = cell_x0
    else:
        align = (slot.get('align') or 'left').lower()
        if align == 'right':
            x = max(ml, page_w - mr - text_width)
        elif align == 'center':
            x = max(ml, (page_w - text_width) / 2)
        else:
            x = ml

        if slot.get('zone') == 'footer':
            footer_reserve_pt = max(0.0, mm_to_pt(float(slot.get('footerReserveMm') or 0)))
            band_pt = footer_reserve_pt if footer_reserve_pt > 0 else (font_size * 2.4)
            y_top = page_h - mb - (band_pt * 0.42) - (font_size * 0.15)
            y_top = max(mt + font_size, min(page_h - mb - font_size * 0.5, y_top))
        else:
            y_top = mt + (font_size * 0.85)

    return x, y_top


def main() -> int:
    if len(sys.argv) < 4:
        print('usage: stamp_chromium_footer_pagination.py <input.pdf> <output.pdf> <slots.json>', file=sys.stderr)
        return 1

    input_pdf = Path(sys.argv[1])
    output_pdf = Path(sys.argv[2])
    slots = json.loads(Path(sys.argv[3]).read_text(encoding='utf-8'))
    footer_slots = [s for s in slots if (s.get('zone') or 'header') == 'footer']
    if not footer_slots:
        output_pdf.write_bytes(input_pdf.read_bytes())
        return 0

    import fitz  # pymupdf

    doc = fitz.open(str(input_pdf))
    page_count = len(doc)

    for page_index in range(page_count):
        page = doc[page_index]
        page_w = float(page.rect.width)
        page_h = float(page.rect.height)
        page_number = page_index + 1

        for slot in footer_slots:
            text = build_text(slot, page_number, page_count)
            font_size = float(slot.get('fontSize') or 10)
            text_width = fitz.get_text_length(text, fontname='helv', fontsize=font_size)
            x, y = compute_position(slot, page_w, page_h, text_width)
            page.insert_text(
                (x, y),
                text,
                fontsize=font_size,
                fontname='helv',
                color=hex_to_rgb(slot.get('color') or '#333333'),
            )

    doc.save(str(output_pdf), garbage=4, deflate=True)
    doc.close()
    return 0


if __name__ == '__main__':
    raise SystemExit(main())
