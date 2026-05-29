/**
 * Códigos de barras CODE128 para sobres (misma lógica que orden de trabajo).
 */
(function (global) {
    'use strict';

    var MM_TO_PX = 96 / 25.4;

    function scalePctVal(sizePct) {
        var p = parseInt(sizePct, 10) || 100;
        return Math.max(0.3, Math.min(2.5, p / 100));
    }

    function barcodeOpts(sizePct, heightMm) {
        var sz = scalePctVal(sizePct);
        var hScale = Math.max(0.5, Math.min(2, (heightMm || 14) / 14));
        return {
            format: 'CODE128',
            displayValue: true,
            fontSize: Math.max(8, Math.round(10 * sz)),
            height: Math.max(18, Math.round(42 * sz * hScale)),
            width: Math.max(0.5, Math.round(1.2 * sz * 10) / 10),
            margin: Math.max(0, Math.round(2 * sz))
        };
    }

    function readBarcodeMeta(svgEl) {
        return {
            value: (svgEl.getAttribute('data-barcode-value') || '').trim(),
            heightMm: Math.max(8, parseInt(svgEl.getAttribute('data-barcode-height'), 10) || 14),
            widthMm: Math.max(25, parseInt(svgEl.getAttribute('data-barcode-width'), 10) || 60),
            sizePct: parseInt(svgEl.getAttribute('data-barcode-size-pct'), 10) || 100,
            raster: svgEl.getAttribute('data-barcode-raster') === '1'
        };
    }

    function applySvgBoxMm(svgEl, widthMm, heightMm, sizePct) {
        if (!svgEl || !svgEl.getBBox) {
            return false;
        }
        var box = svgEl.getBBox();
        if (!box.width || !box.height) {
            return false;
        }
        var sz = scalePctVal(sizePct);
        var wMm = widthMm * sz;
        var aspect = box.height / box.width;
        var totalHMm = Math.max(heightMm * sz * 1.8, wMm * aspect);

        svgEl.setAttribute('viewBox', box.x + ' ' + box.y + ' ' + box.width + ' ' + box.height);
        svgEl.setAttribute('preserveAspectRatio', 'xMidYMid meet');
        svgEl.style.display = 'block';
        svgEl.style.width = wMm + 'mm';
        svgEl.style.maxWidth = '100%';
        svgEl.style.height = totalHMm + 'mm';
        svgEl.style.minHeight = (heightMm * sz) + 'mm';
        svgEl.style.margin = '0 auto';
        return true;
    }

  /**
     * Convierte SVG a PNG para impresión fiable (mismo tamaño mm que la vista previa).
     */
    function rasterizeSvgToImg(svgEl, meta) {
        return new Promise(function (resolve) {
            var box = svgEl.closest('.envelope-barcode-box');
            var fallback = box ? box.querySelector('.envelope-barcode-fallback') : null;
            var sz = scalePctVal(meta.sizePct);
            var wMm = meta.widthMm * sz;
            var targetW = Math.max(120, Math.round(wMm * MM_TO_PX * 2));
            var targetH = Math.max(40, Math.round(targetW * 0.35));

            try {
                var xml = new XMLSerializer().serializeToString(svgEl);
                var blob = new Blob([xml], { type: 'image/svg+xml;charset=utf-8' });
                var url = URL.createObjectURL(blob);
                var img = new Image();
                img.onload = function () {
                    var canvas = document.createElement('canvas');
                    canvas.width = targetW;
                    canvas.height = Math.max(targetH, Math.round(img.height * (targetW / img.width)));
                    var ctx = canvas.getContext('2d');
                    if (!ctx) {
                        URL.revokeObjectURL(url);
                        resolve(false);
                        return;
                    }
                    ctx.fillStyle = '#fff';
                    ctx.fillRect(0, 0, canvas.width, canvas.height);
                    ctx.drawImage(img, 0, 0, canvas.width, canvas.height);
                    var imgEl = document.createElement('img');
                    imgEl.className = 'envelope-barcode-raster';
                    imgEl.alt = meta.value;
                    imgEl.src = canvas.toDataURL('image/png');
                    imgEl.style.display = 'block';
                    imgEl.style.width = wMm + 'mm';
                    imgEl.style.maxWidth = '100%';
                    imgEl.style.height = 'auto';
                    imgEl.style.margin = '0 auto';
                    svgEl.replaceWith(imgEl);
                    if (fallback) {
                        fallback.style.display = 'none';
                    }
                    URL.revokeObjectURL(url);
                    resolve(true);
                };
                img.onerror = function () {
                    URL.revokeObjectURL(url);
                    resolve(false);
                };
                img.src = url;
            } catch (e) {
                console.warn('Envelope barcode raster:', e);
                resolve(false);
            }
        });
    }

    function renderBarcodeSvg(svgEl) {
        if (!svgEl) {
            return Promise.resolve(false);
        }
        var meta = readBarcodeMeta(svgEl);
        if (meta.value === '') {
            return Promise.resolve(false);
        }

        var block = svgEl.closest('.envelope-barcode-block');
        var fallback = block ? block.querySelector('.envelope-barcode-fallback') : null;

        if (typeof global.JsBarcode === 'undefined') {
            if (fallback) {
                fallback.style.display = '';
            }
            return Promise.resolve(false);
        }

        try {
            global.JsBarcode(svgEl, meta.value, barcodeOpts(meta.sizePct, meta.heightMm));
            var ok = applySvgBoxMm(svgEl, meta.widthMm, meta.heightMm, meta.sizePct);
            if (!ok) {
                if (fallback) {
                    fallback.style.display = '';
                }
                return Promise.resolve(false);
            }
            svgEl.setAttribute('data-barcode-rendered', '1');
            if (fallback) {
                fallback.style.display = 'none';
            }
            if (meta.raster) {
                return rasterizeSvgToImg(svgEl, meta).then(function (rasterOk) {
                    if (!rasterOk && fallback) {
                        fallback.style.display = '';
                    }
                    return rasterOk;
                });
            }
            return Promise.resolve(true);
        } catch (e) {
            console.warn('Envelope barcode:', e, meta.value);
            if (fallback) {
                fallback.style.display = '';
            }
            return Promise.resolve(false);
        }
    }

    function initEnvelopeBarcodes(root) {
        if (typeof global.JsBarcode === 'undefined') {
            return Promise.resolve(0);
        }
        var scope = root && root.querySelectorAll ? root : document;
        var nodes = scope.querySelectorAll('.envelope-barcode-svg[data-barcode-value]');
        if (!nodes.length) {
            return Promise.resolve(0);
        }
        var tasks = [];
        nodes.forEach(function (svgEl) {
            tasks.push(renderBarcodeSvg(svgEl));
        });
        return Promise.all(tasks).then(function (results) {
            var ok = 0;
            results.forEach(function (r) {
                if (r) {
                    ok++;
                }
            });
            return ok;
        });
    }

    function renderEnvelopeBarcodeSvg(svgEl) {
        renderBarcodeSvg(svgEl);
    }

    global.initEnvelopeBarcodes = initEnvelopeBarcodes;
    global.renderEnvelopeBarcodeSvg = renderEnvelopeBarcodeSvg;
})(typeof window !== 'undefined' ? window : this);
