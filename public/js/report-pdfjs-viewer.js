/**
 * Visor PDF.js embebido para viewreport (mismo binario que Dompdf).
 */
(function (global) {
    'use strict';

    function clamp(value, min, max) {
        return Math.min(max, Math.max(min, value));
    }

    function ReportPdfJsViewer(root) {
        this.root = root;
        this.pdfUrl = root.getAttribute('data-pdf-url') || '';
        this.workerSrc = root.getAttribute('data-worker-src') || '';
        this.initialScale = parseFloat(root.getAttribute('data-initial-scale') || '1.35') || 1.35;

        this.canvasHost = root.querySelector('[data-pdfjs-canvas-host]');
        this.loadingEl = root.querySelector('[data-pdfjs-loading]');
        this.loadingTextEl = root.querySelector('[data-pdfjs-loading-text]');
        this.statusEl = root.querySelector('[data-pdfjs-status]');
        this.pageLabelEl = root.querySelector('[data-pdfjs-page-label]');
        this.pageCurrentEl = root.querySelector('[data-pdfjs-page-current]');
        this.pageTotalEl = root.querySelector('[data-pdfjs-page-total]');
        this.zoomLabelEl = root.querySelector('[data-pdfjs-zoom-label]');
        this.btnPrev = root.querySelector('[data-pdfjs-prev]');
        this.btnNext = root.querySelector('[data-pdfjs-next]');
        this.btnZoomOut = root.querySelector('[data-pdfjs-zoom-out]');
        this.btnZoomIn = root.querySelector('[data-pdfjs-zoom-in]');
        this.btnFitWidth = root.querySelector('[data-pdfjs-fit-width]');

        this.pdfDoc = null;
        this.pageNum = 1;
        this.scale = this.initialScale;
        this.renderTask = null;
        this.renderSeq = 0;
        this.uiBound = false;
    }

    ReportPdfJsViewer.prototype.setLoading = function (active, message) {
        this.root.classList.toggle('is-loading', !!active);
        if (this.loadingTextEl && message) {
            this.loadingTextEl.textContent = message;
        }
    };

    ReportPdfJsViewer.prototype.setStatus = function (message, isError) {
        if (!this.statusEl) {
            return;
        }
        this.statusEl.textContent = message || '';
        this.statusEl.hidden = !message;
        this.statusEl.classList.toggle('text-danger', !!isError);
        this.statusEl.classList.toggle('text-muted', !isError);
    };

    ReportPdfJsViewer.prototype.updateControls = function () {
        var total = this.pdfDoc ? this.pdfDoc.numPages : 0;
        if (this.pageLabelEl) {
            this.pageLabelEl.textContent = total > 0
                ? 'Pág. ' + this.pageNum + ' de ' + total
                : 'Pág. —';
        } else {
            if (this.pageCurrentEl) {
                this.pageCurrentEl.textContent = String(this.pageNum);
            }
            if (this.pageTotalEl) {
                this.pageTotalEl.textContent = String(total);
            }
        }
        if (this.zoomLabelEl) {
            this.zoomLabelEl.textContent = Math.round(this.scale * 100) + '%';
        }
        if (this.btnPrev) {
            this.btnPrev.disabled = this.pageNum <= 1;
        }
        if (this.btnNext) {
            this.btnNext.disabled = total === 0 || this.pageNum >= total;
        }
    };

    ReportPdfJsViewer.prototype.clearCanvases = function () {
        if (!this.canvasHost) {
            return;
        }
        var loading = this.loadingEl;
        while (this.canvasHost.firstChild) {
            if (this.canvasHost.firstChild === loading) {
                if (this.canvasHost.childNodes.length === 1) {
                    break;
                }
                this.canvasHost.removeChild(this.canvasHost.childNodes[1]);
                continue;
            }
            this.canvasHost.removeChild(this.canvasHost.firstChild);
        }
        if (loading && loading.parentNode !== this.canvasHost) {
            this.canvasHost.appendChild(loading);
        }
    };

    ReportPdfJsViewer.prototype.renderSinglePage = function (pageNumber, seq) {
        var self = this;
        if (!this.pdfDoc || !this.canvasHost || seq !== this.renderSeq) {
            return Promise.resolve();
        }

        return this.pdfDoc.getPage(pageNumber).then(function (page) {
            if (seq !== self.renderSeq) {
                return;
            }
            var viewport = page.getViewport({ scale: self.scale });
            var canvas = document.createElement('canvas');
            canvas.className = 'report-pdfjs-page-canvas';
            canvas.width = viewport.width;
            canvas.height = viewport.height;
            canvas.setAttribute('data-page-number', String(pageNumber));
            canvas.setAttribute('aria-label', 'Página ' + pageNumber);

            var pageWrap = document.createElement('div');
            pageWrap.className = 'report-pdfjs-page';
            pageWrap.setAttribute('data-page-number', String(pageNumber));
            pageWrap.appendChild(canvas);
            self.canvasHost.appendChild(pageWrap);

            var ctx = canvas.getContext('2d', { alpha: false });
            self.renderTask = page.render({ canvasContext: ctx, viewport: viewport });
            return self.renderTask.promise;
        });
    };

    ReportPdfJsViewer.prototype.renderAllPages = function () {
        var self = this;
        if (!this.pdfDoc || !this.canvasHost) {
            return Promise.resolve();
        }

        if (this.renderTask) {
            try {
                this.renderTask.cancel();
            } catch (e) {}
            this.renderTask = null;
        }

        var seq = ++this.renderSeq;
        var totalPages = this.pdfDoc.numPages;
        this.clearCanvases();
        this.setLoading(true, 'Renderizando página 1…');
        this.setStatus('', false);

        return this.renderSinglePage(1, seq).then(function () {
            if (seq !== self.renderSeq) {
                return;
            }
            self.renderTask = null;
            self.setLoading(false);
            self.updateControls();

            if (totalPages <= 1) {
                return;
            }

            self.setStatus('Cargando páginas 2–' + totalPages + '…', false);

            var chain = Promise.resolve();
            for (var pageNumber = 2; pageNumber <= totalPages; pageNumber++) {
                (function (num) {
                    chain = chain.then(function () {
                        if (seq !== self.renderSeq) {
                            return;
                        }
                        self.setStatus('Cargando página ' + num + ' de ' + totalPages + '…', false);
                        return self.renderSinglePage(num, seq);
                    });
                })(pageNumber);
            }

            return chain.then(function () {
                if (seq !== self.renderSeq) {
                    return;
                }
                self.renderTask = null;
                self.setStatus('', false);
                self.updateControls();
            });
        }).catch(function (err) {
            if (err && err.name === 'RenderingCancelledException') {
                return;
            }
            self.setLoading(false);
            self.setStatus('No se pudo renderizar el PDF.', true);
        });
    };

    ReportPdfJsViewer.prototype.scrollToPage = function (pageNumber) {
        if (!this.canvasHost) {
            return;
        }
        var target = this.canvasHost.querySelector('.report-pdfjs-page[data-page-number="' + pageNumber + '"]');
        if (target && typeof target.scrollIntoView === 'function') {
            target.scrollIntoView({ behavior: 'smooth', block: 'start' });
        }
    };

    ReportPdfJsViewer.prototype.goToPage = function (pageNumber) {
        if (!this.pdfDoc) {
            return;
        }
        this.pageNum = clamp(pageNumber, 1, this.pdfDoc.numPages);
        this.updateControls();
        this.scrollToPage(this.pageNum);
    };

    ReportPdfJsViewer.prototype.setScale = function (nextScale) {
        this.scale = clamp(nextScale, 0.5, 3);
        this.root.classList.remove('is-fit-width');
        this.updateControls();
        return this.renderAllPages();
    };

    ReportPdfJsViewer.prototype.fitWidth = function () {
        var self = this;
        if (!this.pdfDoc || !this.canvasHost) {
            return Promise.resolve();
        }
        return this.pdfDoc.getPage(1).then(function (page) {
            var viewport = page.getViewport({ scale: 1 });
            var available = self.canvasHost.clientWidth - 32;
            if (available < 120) {
                available = self.root.clientWidth - 32;
            }
            var nextScale = available > 0 ? available / viewport.width : 1;
            self.root.classList.add('is-fit-width');
            self.scale = clamp(nextScale, 0.5, 3);
            self.updateControls();
            return self.renderAllPages();
        });
    };

    ReportPdfJsViewer.prototype.bindUi = function () {
        if (this.uiBound) {
            return;
        }
        this.uiBound = true;

        var self = this;

        if (this.btnPrev) {
            this.btnPrev.addEventListener('click', function () {
                self.goToPage(self.pageNum - 1);
            });
        }
        if (this.btnNext) {
            this.btnNext.addEventListener('click', function () {
                self.goToPage(self.pageNum + 1);
            });
        }
        if (this.btnZoomOut) {
            this.btnZoomOut.addEventListener('click', function () {
                self.setScale(self.scale - 0.1);
            });
        }
        if (this.btnZoomIn) {
            this.btnZoomIn.addEventListener('click', function () {
                self.setScale(self.scale + 0.10);
            });
        }
        if (this.btnFitWidth) {
            this.btnFitWidth.addEventListener('click', function () {
                self.fitWidth();
            });
        }

        if (this.canvasHost) {
            this.canvasHost.addEventListener('scroll', function () {
                if (!self.pdfDoc) {
                    return;
                }
                var pages = self.canvasHost.querySelectorAll('.report-pdfjs-page');
                var hostRect = self.canvasHost.getBoundingClientRect();
                var bestPage = self.pageNum;
                var bestDistance = Infinity;
                pages.forEach(function (pageEl) {
                    var rect = pageEl.getBoundingClientRect();
                    var distance = Math.abs(rect.top - hostRect.top - 12);
                    if (distance < bestDistance) {
                        bestDistance = distance;
                        bestPage = parseInt(pageEl.getAttribute('data-page-number') || '1', 10);
                    }
                });
                if (bestPage !== self.pageNum) {
                    self.pageNum = bestPage;
                    self.updateControls();
                }
            });
        }

        var resizeTimer = null;
        window.addEventListener('resize', function () {
            clearTimeout(resizeTimer);
            resizeTimer = setTimeout(function () {
                if (self.root.classList.contains('is-fit-width')) {
                    self.fitWidth();
                }
            }, 180);
        });
    };

    ReportPdfJsViewer.prototype.load = function () {
        var self = this;
        if (!this.pdfUrl || !global.pdfjsLib) {
            this.setLoading(false);
            this.setStatus('Visor PDF no disponible.', true);
            return Promise.resolve();
        }

        if (this.workerSrc) {
            global.pdfjsLib.GlobalWorkerOptions.workerSrc = this.workerSrc;
        }

        this.setLoading(true, 'Generando vista previa del análisis clínico…');
        this.setStatus('', false);
        this.bindUi();

        var loadingTask = global.pdfjsLib.getDocument({ url: this.pdfUrl, withCredentials: true });

        loadingTask.onProgress = function (progress) {
            if (!progress || !progress.total) {
                self.setLoading(true, 'Generando vista previa del análisis clínico…');
                return;
            }
            var pct = Math.min(100, Math.round((progress.loaded / progress.total) * 100));
            self.setLoading(true, 'Descargando PDF… ' + pct + '%');
        };

        return loadingTask.promise
            .then(function (pdfDoc) {
                self.pdfDoc = pdfDoc;
                self.pageNum = 1;
                self.scale = self.initialScale;
                self.updateControls();
                self.setLoading(true, 'Renderizando página 1…');
                return self.renderAllPages();
            })
            .catch(function () {
                self.setLoading(false);
                self.setStatus('No se pudo cargar el PDF. Intente descargarlo o recargue la página.', true);
            });
    };

    global.ReportPdfJsViewer = ReportPdfJsViewer;

    global.initReportPdfJsViewer = function (selector) {
        var nodes = document.querySelectorAll(selector || '[data-report-pdfjs-viewer]');
        nodes.forEach(function (node) {
            if (node.__reportPdfJsViewer) {
                return;
            }
            var viewer = new ReportPdfJsViewer(node);
            node.__reportPdfJsViewer = viewer;
            viewer.load();
        });
    };
})(window);
