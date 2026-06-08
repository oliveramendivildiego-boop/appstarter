/**
 * Command Palette (Ctrl+K) — navegación rápida sin alterar rutas existentes.
 */
(function () {
    'use strict';

    var palette = document.getElementById('lab-command-palette');
    var input = document.getElementById('lab-cmd-input');
    var resultsEl = document.getElementById('lab-cmd-results');
    if (!palette || !input || !resultsEl) return;

    var items = [];
    var activeIndex = 0;
    var extraItems = [
        { label: 'Mapa de trazabilidad', url: (window.BASE_URL || '/') + 'lab-modern/trazabilidad', icon: 'fa-diagram-project', group: 'Herramientas' },
        { label: 'Procesos de laboratorio', url: (window.BASE_URL || '/') + 'lab-modern/procesos', icon: 'fa-sitemap', group: 'Herramientas' },
        { label: 'Paleta de comandos', url: '#', icon: 'fa-keyboard', group: 'Ayuda', action: 'help' }
    ];

    function normalizeBase() {
        var b = window.BASE_URL || '/';
        return b.endsWith('/') ? b : b + '/';
    }

    function collectFromSidebar() {
        var seen = {};
        document.querySelectorAll('#sidebarMenu a.nav-link[href]').forEach(function (a) {
            var href = a.getAttribute('href') || '';
            if (!href || href === '#' || href.indexOf('logout') !== -1) return;
            if (seen[href]) return;
            seen[href] = true;
            var iconEl = a.querySelector('i');
            var icon = iconEl ? iconEl.className.replace('fa-solid ', '').replace('fa-regular ', '') : 'fa-circle';
            var label = (a.textContent || '').replace(/\s+/g, ' ').trim();
            if (!label) return;
            items.push({ label: label, url: href, icon: icon, group: 'Menú' });
        });
    }

    function buildItems() {
        items = [];
        collectFromSidebar();
        extraItems.forEach(function (it) { items.push(it); });
    }

    function filterItems(q) {
        q = (q || '').toLowerCase().trim();
        if (!q) return items.slice(0, 20);
        return items.filter(function (it) {
            return it.label.toLowerCase().indexOf(q) !== -1
                || (it.group || '').toLowerCase().indexOf(q) !== -1;
        }).slice(0, 20);
    }

    function renderList(list) {
        resultsEl.innerHTML = '';
        if (!list.length) {
            resultsEl.innerHTML = '<li class="lab-cmd-empty">Sin resultados</li>';
            return;
        }
        list.forEach(function (it, idx) {
            var li = document.createElement('li');
            var btn = document.createElement('a');
            btn.href = it.url || '#';
            btn.className = 'lab-cmd-item' + (idx === activeIndex ? ' active' : '');
            btn.setAttribute('role', 'option');
            btn.innerHTML = '<i class="fa-solid ' + (it.icon || 'fa-circle') + '"></i>'
                + '<span>' + escapeHtml(it.label) + '</span>'
                + '<span class="lab-cmd-item-meta">' + escapeHtml(it.group || '') + '</span>';
            btn.addEventListener('click', function (ev) {
                if (it.action === 'help') {
                    ev.preventDefault();
                    return;
                }
                closePalette();
            });
            li.appendChild(btn);
            resultsEl.appendChild(li);
        });
    }

    function escapeHtml(s) {
        return String(s).replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;');
    }

    function openPalette() {
        buildItems();
        activeIndex = 0;
        input.value = '';
        palette.hidden = false;
        renderList(filterItems(''));
        setTimeout(function () { input.focus(); }, 50);
    }

    function closePalette() {
        palette.hidden = true;
        input.value = '';
    }

    function activateSelected(list) {
        if (!list.length) return;
        var it = list[activeIndex];
        if (!it) return;
        if (it.action === 'help') return;
        if (it.url && it.url !== '#') {
            window.location.href = it.url;
        }
        closePalette();
    }

    input.addEventListener('input', function () {
        activeIndex = 0;
        renderList(filterItems(input.value));
    });

    input.addEventListener('keydown', function (ev) {
        var list = filterItems(input.value);
        if (ev.key === 'Escape') {
            ev.preventDefault();
            closePalette();
        } else if (ev.key === 'ArrowDown') {
            ev.preventDefault();
            activeIndex = Math.min(activeIndex + 1, list.length - 1);
            renderList(list);
        } else if (ev.key === 'ArrowUp') {
            ev.preventDefault();
            activeIndex = Math.max(activeIndex - 1, 0);
            renderList(list);
        } else if (ev.key === 'Enter') {
            ev.preventDefault();
            activateSelected(list);
        }
    });

    palette.querySelector('.lab-cmd-backdrop').addEventListener('click', closePalette);

    document.addEventListener('keydown', function (ev) {
        var tag = (ev.target && ev.target.tagName) ? ev.target.tagName.toLowerCase() : '';
        var inField = tag === 'input' || tag === 'textarea' || tag === 'select' || (ev.target && ev.target.isContentEditable);
        if ((ev.ctrlKey || ev.metaKey) && ev.key.toLowerCase() === 'k') {
            ev.preventDefault();
            if (palette.hidden) openPalette();
            else closePalette();
            return;
        }
        if (!palette.hidden && ev.key === 'Escape') {
            closePalette();
        }
    });
})();
