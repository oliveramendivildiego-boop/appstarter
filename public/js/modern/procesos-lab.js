/**
 * Diagrama de procesos de laboratorio (Cytoscape + dagre).
 */
(function () {
    'use strict';

    var container = document.getElementById('lab-proc-cy');
    if (!container || typeof cytoscape === 'undefined') return;

    if (typeof cytoscapeDagre !== 'undefined') {
        cytoscape.use(cytoscapeDagre);
    }

    var nodes = [
        { data: { id: 'recep', label: 'Recepción\norden' } },
        { data: { id: 'toma', label: 'Toma de\nmuestra' } },
        { data: { id: 'proc', label: 'Procesamiento\nanalítico' } },
        { data: { id: 'ctrl', label: 'Control de\ncalidad' } },
        { data: { id: 'valid', label: 'Validación\n técnica' } },
        { data: { id: 'entrega', label: 'Entrega de\nresultados' } }
    ];
    var edges = [
        { data: { source: 'recep', target: 'toma' } },
        { data: { source: 'toma', target: 'proc' } },
        { data: { source: 'proc', target: 'ctrl' } },
        { data: { source: 'ctrl', target: 'valid' } },
        { data: { source: 'valid', target: 'entrega' } }
    ];

    var cy = cytoscape({
        container: container,
        elements: nodes.concat(edges),
        style: [
            {
                selector: 'node',
                style: {
                    'label': 'data(label)',
                    'text-wrap': 'wrap',
                    'text-valign': 'center',
                    'text-halign': 'center',
                    'font-size': 11,
                    'background-color': '#0d6efd',
                    'color': '#fff',
                    'width': 100,
                    'height': 56,
                    'shape': 'round-rectangle'
                }
            },
            {
                selector: 'edge',
                style: {
                    'width': 3,
                    'line-color': '#6c757d',
                    'target-arrow-color': '#6c757d',
                    'target-arrow-shape': 'triangle',
                    'curve-style': 'bezier'
                }
            },
            { selector: 'node:selected', style: { 'border-width': 3, 'border-color': '#ffc107' } }
        ],
        layout: { name: (typeof dagre !== 'undefined' ? 'dagre' : 'breadthfirst'), rankDir: 'LR', padding: 40 },
        wheelSensitivity: 0.2
    });

    var links = {
        recep: (window.BASE_URL || '/') + 'registers',
        toma: (window.BASE_URL || '/') + 'registers/lista',
        proc: (window.BASE_URL || '/') + 'registers/lista',
        ctrl: (window.BASE_URL || '/') + 'controlcalidad',
        valid: (window.BASE_URL || '/') + 'registers/lista',
        entrega: (window.BASE_URL || '/') + 'reports'
    };

    cy.on('tap', 'node', function (ev) {
        var id = ev.target.id();
        var url = links[id];
        if (url) window.location.href = url;
    });

    document.getElementById('lab-proc-fit') && document.getElementById('lab-proc-fit').addEventListener('click', function () {
        cy.fit(undefined, 40);
    });
})();
