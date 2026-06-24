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
        this.renderedPages = {};
        this.lazyObserver = null;
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

    ReportPdfJsViewer.prototype.resetRenderedPages = function () {
        this.renderedPages = {};
        if (this.lazyObserver) {
            this.lazyObserver.disconnect();
            this.lazyObserver = null;
        }
    };

    ReportPdfJsViewer.prototype.ensurePagePlaceholder = function (pageNumber) {
        if (!this.canvasHost) {
            return null;
        }
        var existing = this.canvasHost.querySelector('.report-pdfjs-page[data-page-number="' + pageNumber + '"]');
        if (existing) {
            return existing;
        }
        var pageWrap = document.createElement('div');
        pageWrap.className = 'report-pdfjs-page report-pdfjs-page--pending';
        pageWrap.setAttribute('data-page-number', String(pageNumber));
        pageWrap.setAttribute('aria-label', 'Página ' + pageNumber);

        var pages = this.canvasHost.querySelectorAll('.report-pdfjs-page');
        var inserted = false;
        for (var i = 0; i < pages.length; i++) {
            var existingNum = parseInt(pages[i].getAttribute('data-page-number') || '0', 10);
            if (pageNumber < existingNum) {
                this.canvasHost.insertBefore(pageWrap, pages[i]);
                inserted = true;
                break;
            }
        }
        if (!inserted) {
            this.canvasHost.appendChild(pageWrap);
        }

        return pageWrap;
    };

    ReportPdfJsViewer.prototype.renderSinglePage = function (pageNumber, seq) {
        var self = this;
        if (!this.pdfDoc || !this.canvasHost || seq !== this.renderSeq) {
            return Promise.resolve();
        }
        if (this.renderedPages[pageNumber]) {
            return Promise.resolve();
        }

        return this.pdfDoc.getPage(pageNumber).then(function (page) {
            if (seq !== self.renderSeq) {
                return;
            }
            var viewport = page.getViewport({ scale: self.scale });
            var pageWrap = self.ensurePagePlaceholder(pageNumber);
            if (!pageWrap) {
                return;
            }
            pageWrap.classList.remove('report-pdfjs-page--pending');
            pageWrap.innerHTML = '';

            var canvas = document.createElement('canvas');
            canvas.className = 'report-pdfjs-page-canvas';
            canvas.width = viewport.width;
            canvas.height = viewport.height;
            canvas.setAttribute('data-page-number', String(pageNumber));
            canvas.setAttribute('aria-label', 'Página ' + pageNumber);
            pageWrap.appendChild(canvas);

            var ctx = canvas.getContext('2d', { alpha: false });
            self.renderTask = page.render({ canvasContext: ctx, viewport: viewport });
            return self.renderTask.promise.then(function () {
                if (seq === self.renderSeq) {
                    self.renderedPages[pageNumber] = true;
                }
            });
        });
    };

    ReportPdfJsViewer.prototype.prefetchNearbyPages = function (centerPage, seq) {
        var self = this;
        if (!this.pdfDoc) {
            return;
        }
        var total = this.pdfDoc.numPages;
        var targets = [centerPage, centerPage + 1, centerPage - 1, centerPage + 2];
        targets.forEach(function (pageNumber) {
            if (pageNumber < 1 || pageNumber > total || self.renderedPages[pageNumber]) {
                return;
            }
            self.ensurePagePlaceholder(pageNumber);
            self.renderSinglePage(pageNumber, seq).catch(function () {});
        });
    };

    ReportPdfJsViewer.prototype.bindLazyPageObserver = function (seq) {
        var self = this;
        if (!this.canvasHost || typeof global.IntersectionObserver !== 'function') {
            return;
        }
        if (this.lazyObserver) {
            this.lazyObserver.disconnect();
        }
        this.lazyObserver = new IntersectionObserver(function (entries) {
            entries.forEach(function (entry) {
                if (!entry.isIntersecting || seq !== self.renderSeq) {
                    return;
                }
                var pageNumber = parseInt(entry.target.getAttribute('data-page-number') || '0', 10);
                if (pageNumber > 0) {
                    self.renderSinglePage(pageNumber, seq).catch(function () {});
                }
            });
        }, {
            root: this.canvasHost,
            rootMargin: '240px 0px',
            threshold: 0.01
        });

        var pages = this.canvasHost.querySelectorAll('.report-pdfjs-page');
        pages.forEach(function (pageEl) {
            self.lazyObserver.observe(pageEl);
        });
    };

    ReportPdfJsViewer.prototype.renderInitialView = function () {
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
        this.resetRenderedPages();
        this.clearCanvases();
        this.setLoading(true, 'Renderizando página 1…');
        this.setStatus('', false);

        for (var placeholderPage = 1; placeholderPage <= totalPages; placeholderPage++) {
            this.ensurePagePlaceholder(placeholderPage);
        }

        return this.renderSinglePage(1, seq).then(function () {
            if (seq !== self.renderSeq) {
                return;
            }
            self.renderTask = null;
            self.setLoading(false);
            self.updateControls();
            var firstCanvas = self.canvasHost.querySelector('.report-pdfjs-page-canvas');
            if (firstCanvas && firstCanvas.offsetHeight > 0) {
                var pendingMinHeight = firstCanvas.offsetHeight + 'px';
                self.canvasHost.querySelectorAll('.report-pdfjs-page--pending').forEach(function (pageEl) {
                    pageEl.style.minHeight = pendingMinHeight;
                });
            }
            self.bindLazyPageObserver(seq);
            self.prefetchNearbyPages(1, seq);

            if (totalPages <= 1) {
                return;
            }

            self.setStatus('', false);
        }).catch(function (err) {
            if (err && err.name === 'RenderingCancelledException') {
                return;
            }
            self.setLoading(false);
            self.setStatus('No se pudo renderizar el PDF.', true);
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
        this.resetRenderedPages();
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
                for (var p = 1; p <= totalPages; p++) {
                    self.renderedPages[p] = true;
                }
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
        this.prefetchNearbyPages(this.pageNum, this.renderSeq);
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
                    self.prefetchNearbyPages(bestPage, self.renderSeq);
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
                return self.renderInitialView();
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
