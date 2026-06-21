<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Imprimir resultados - <?= esc($paciente_nombre ?? 'Paciente') ?></title>
    <style>
        html, body {
            margin: 0;
            padding: 0;
            height: 100%;
            background: #525659;
        }
        body.report-print-pdf-autostart {
            background: #fff;
        }
        body.report-print-pdf-autostart .report-print-pdf-toolbar {
            display: none;
        }
        body.report-print-pdf-autostart .report-print-pdf-frame-wrap {
            height: 100vh;
        }
        .report-print-pdf-status {
            display: none;
            position: fixed;
            inset: 0;
            z-index: 5;
            background: #fff;
            color: #444;
            font: 14px/1.4 system-ui, sans-serif;
            align-items: center;
            justify-content: center;
            text-align: center;
            padding: 24px;
        }
        body.report-print-pdf-autostart .report-print-pdf-status {
            display: flex;
        }
        body.report-print-pdf-autostart.is-pdf-ready .report-print-pdf-status {
            display: none;
        }
        .report-print-pdf-toolbar {
            padding: 10px 12px;
            background: #f1f3f5;
            border-bottom: 1px solid #dee2e6;
            display: flex;
            flex-wrap: wrap;
            gap: 8px;
            align-items: center;
        }
        .report-print-pdf-toolbar button,
        .report-print-pdf-toolbar a {
            padding: 8px 16px;
            border-radius: 6px;
            font-size: 14px;
            cursor: pointer;
            text-decoration: none;
            display: inline-block;
        }
        .report-print-pdf-toolbar .report-print-btn-primary {
            background: #0d6efd;
            color: #fff;
            border: none;
        }
        .report-print-pdf-toolbar .report-print-btn-secondary {
            background: #fff;
            color: #333;
            border: 1px solid #ced4da;
        }
        .report-print-pdf-frame-wrap {
            height: calc(100vh - 52px);
        }
        #report-print-pdf-frame {
            width: 100%;
            height: 100%;
            border: 0;
            background: #525659;
        }
        @media print {
            .report-print-pdf-toolbar {
                display: none !important;
            }
            .report-print-pdf-frame-wrap {
                height: auto;
            }
            #report-print-pdf-frame {
                position: absolute;
                inset: 0;
                width: 100%;
                height: 100%;
            }
        }
    </style>
</head>
<body class="<?= ! empty($auto_print) ? 'report-print-pdf-autostart' : '' ?>">
<div class="report-print-pdf-status" aria-live="polite">Preparando impresión…</div>
<div class="report-print-pdf-toolbar no-print">
    <button type="button" class="report-print-btn-primary" id="report-print-pdf-btn">Imprimir</button>
    <?php if ((int) ($registro_id ?? 0) > 0): ?>
    <a href="<?= site_url('registers/viewreport/' . (int) $registro_id) ?>" class="report-print-btn-secondary">Volver al reporte</a>
    <?php endif; ?>
</div>
<div class="report-print-pdf-frame-wrap">
    <iframe id="report-print-pdf-frame" title="Resultados PDF"></iframe>
</div>
<script>
(function() {
    var pdfBase64 = <?= json_encode($pdf_base64 ?? '') ?>;
    var autoPrint = <?= ! empty($auto_print) ? 'true' : 'false' ?>;
    var iframe = document.getElementById('report-print-pdf-frame');
    var printBtn = document.getElementById('report-print-pdf-btn');
    var objectUrl = null;
    var printTriggered = false;
    var windowCloseScheduled = false;
    var printDismissArmed = false;
    var lastPrintCallAt = 0;

    function closeWindowAfterPrint() {
        if (!autoPrint || windowCloseScheduled) {
            return;
        }
        windowCloseScheduled = true;
        setTimeout(function() {
            window.close();
        }, 150);
    }

    function bindPrintCloseOnWindow(win) {
        if (!autoPrint || !win || win.__reportPrintCloseBound) {
            return;
        }
        try {
            win.__reportPrintCloseBound = true;
            win.addEventListener('afterprint', closeWindowAfterPrint);
        } catch (e) {}
    }

    /**
     * El visor PDF del iframe no dispara afterprint al cancelar.
     * blur + hasFocus detecta el cierre del diálogo sin afectar la impresión.
     */
    function armCloseAfterPrintDismiss(printWin) {
        if (!autoPrint || printDismissArmed) {
            return;
        }
        printDismissArmed = true;

        bindPrintCloseOnWindow(window);
        bindPrintCloseOnWindow(printWin);

        var sawBlur = false;
        var onBlur = function() {
            sawBlur = true;
        };
        window.addEventListener('blur', onBlur);

        var pollId = setInterval(function() {
            if (windowCloseScheduled) {
                clearInterval(pollId);
                window.removeEventListener('blur', onBlur);
                return;
            }
            if (Date.now() - lastPrintCallAt < 400) {
                return;
            }
            if (sawBlur && document.hasFocus()) {
                clearInterval(pollId);
                window.removeEventListener('blur', onBlur);
                closeWindowAfterPrint();
            }
        }, 150);

        if (window.matchMedia) {
            var mql = window.matchMedia('print');
            var onPrintMediaChange = function(e) {
                if (!e.matches) {
                    setTimeout(closeWindowAfterPrint, 300);
                }
            };
            if (typeof mql.addEventListener === 'function') {
                mql.addEventListener('change', onPrintMediaChange);
            } else if (typeof mql.addListener === 'function') {
                mql.addListener(onPrintMediaChange);
            }
        }
    }

    function markPdfReady() {
        document.body.classList.add('is-pdf-ready');
    }

    function triggerAutoPrintOnce() {
        if (!autoPrint || printTriggered) {
            return;
        }
        printTriggered = true;
        markPdfReady();
        setTimeout(printPdf, 120);
    }

    function base64ToUint8Array(base64) {
        var raw = atob(base64);
        var arr = new Uint8Array(raw.length);
        for (var i = 0; i < raw.length; i++) {
            arr[i] = raw.charCodeAt(i);
        }
        return arr;
    }

    function openPdfInFrame() {
        if (!pdfBase64 || !iframe) {
            return;
        }
        var blob = new Blob([base64ToUint8Array(pdfBase64)], { type: 'application/pdf' });
        objectUrl = URL.createObjectURL(blob);
        iframe.src = objectUrl;
    }

    function printPdf() {
        if (!iframe || !iframe.contentWindow) {
            return;
        }
        var printWin = iframe.contentWindow;

        if (autoPrint) {
            lastPrintCallAt = Date.now();
            armCloseAfterPrintDismiss(printWin);
        }

        try {
            printWin.focus();
            printWin.print();
        } catch (e) {
            window.print();
        }
    }

    if (printBtn) {
        printBtn.addEventListener('click', printPdf);
    }

    if (iframe) {
        iframe.addEventListener('load', function() {
            triggerAutoPrintOnce();
        });
    }

    if (autoPrint) {
        setTimeout(function() {
            triggerAutoPrintOnce();
        }, 1800);
    }

    window.addEventListener('beforeprint', function() {
        if (iframe && iframe.contentWindow) {
            try {
                iframe.contentWindow.focus();
            } catch (e) {}
        }
    });

    openPdfInFrame();

    window.addEventListener('unload', function() {
        if (objectUrl) {
            URL.revokeObjectURL(objectUrl);
        }
    });
})();
</script>
</body>
</html>
