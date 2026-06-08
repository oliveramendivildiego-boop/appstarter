/**
 * Canvas de trazabilidad (Cytoscape) — visualización de auditoría.
 */
(function () {
    'use strict';

    var boot = window.LAB_TRAZABILIDAD_BOOT || {};
    var container = document.getElementById('lab-traz-cy');
    if (!container || typeof cytoscape === 'undefined') return;

    var cy = cytoscape({
        container: container,
        elements: { nodes: [], edges: [] },
        style: [
            {
                selector: 'node',
                style: {
                    'label': 'data(label)',
                    'text-wrap': 'wrap',
                    'text-max-width': 120,
                    'font-size': 10,
                    'background-color': '#0d6efd',
                    'color': '#fff',
                    'text-valign': 'center',
                    'text-halign': 'center',
                    'width': 80,
                    'height': 50,
                    'shape': 'round-rectangle',
                    'padding': '6px'
                }
            },
            {
                selector: 'node[modulo = "registers"]',
                style: { 'background-color': '#198754' }
            },
            {
                selector: 'node[modulo = "config"]',
                style: { 'background-color': '#6f42c1' }
            },
            {
                selector: 'edge',
                style: {
                    'width': 2,
                    'line-color': '#adb5bd',
                    'target-arrow-color': '#adb5bd',
                    'target-arrow-shape': 'triangle',
                    'curve-style': 'bezier'
                }
            }
        ],
        layout: { name: 'breadthfirst', directed: true, padding: 30, spacingFactor: 1.2 },
        wheelSensitivity: 0.2
    });

    function loadGraph(params) {
        var url = boot.dataUrl || '';
        if (!url) return;
        var qs = new URLSearchParams(params || {}).toString();
        fetch(url + (qs ? '?' + qs : ''), { headers: { 'X-Requested-With': 'XMLHttpRequest' } })
            .then(function (r) { return r.json(); })
            .then(function (data) {
                cy.elements().remove();
                cy.add(data.nodes || []);
                cy.add(data.edges || []);
                cy.layout({ name: 'breadthfirst', directed: true, padding: 30 }).run();
            })
            .catch(function () { /* silencioso */ });
    }

    var regInput = document.getElementById('lab-traz-registro');
    var btnLoad = document.getElementById('lab-traz-load');
    if (btnLoad) {
        btnLoad.addEventListener('click', function () {
            loadGraph({ registro_id: regInput ? regInput.value.trim() : '' });
        });
    }

    var btnFit = document.getElementById('lab-traz-fit');
    if (btnFit) btnFit.addEventListener('click', function () { cy.fit(undefined, 40); });

    loadGraph(boot.initialFilters || {});
})();
