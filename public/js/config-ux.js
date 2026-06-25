/**
 * UX compartida del módulo Configuración.
 */
(function () {
    'use strict';

    function storageGet(key) {
        try {
            return localStorage.getItem(key);
        } catch (e) {
            return null;
        }
    }

    function storageSet(key, val) {
        try {
            localStorage.setItem(key, val);
        } catch (e) { /* ignore */ }
    }

    function initHubGuide() {
        var card = document.getElementById('config_hub_guide');
        var dismiss = document.getElementById('btn_dismiss_config_hub_guide');
        if (!card) return;
        if (storageGet('configHubGuideDismissed') === '1') {
            card.style.display = 'none';
        }
        if (dismiss) {
            dismiss.addEventListener('click', function () {
                card.style.display = 'none';
                storageSet('configHubGuideDismissed', '1');
            });
        }
    }

    function applyTabGroupFilter(group) {
        var nav = document.getElementById('configTabs');
        if (!nav) return;
        document.querySelectorAll('[data-config-group-filter]').forEach(function (btn) {
            btn.classList.toggle('active', btn.getAttribute('data-config-group-filter') === group);
        });
        nav.querySelectorAll('.nav-item[data-config-group]').forEach(function (li) {
            var g = li.getAttribute('data-config-group');
            li.classList.toggle('config-tab-filter-hidden', group !== 'all' && g !== group);
        });
        try {
            localStorage.setItem('configTabGroupFilter', group);
        } catch (e) { /* ignore */ }
    }

    function initTabGroupFilter() {
        var nav = document.getElementById('configTabs');
        var buttons = document.querySelectorAll('[data-config-group-filter]');
        if (!nav || !buttons.length) return;

        buttons.forEach(function (btn) {
            btn.addEventListener('click', function () {
                applyTabGroupFilter(btn.getAttribute('data-config-group-filter') || 'all');
            });
        });

        var activeBtn = nav.querySelector('.nav-link.active');
        var activeGroup = null;
        if (activeBtn) {
            var parentLi = activeBtn.closest('[data-config-group]');
            if (parentLi) {
                activeGroup = parentLi.getAttribute('data-config-group');
            }
        }
        var saved = storageGet('configTabGroupFilter');
        var initial = activeGroup || saved || 'all';
        applyTabGroupFilter(initial);
    }

    function initTabFinder() {
        var inputs = [
            document.getElementById('config_tab_finder'),
            document.getElementById('config_tab_finder_inline'),
        ].filter(Boolean);
        var list = document.getElementById('config_tab_finder_list');
        if (!inputs.length || !list || !window._configTabIndex) return;

        var tabs = window._configTabIndex;

        function tabVisible(t) {
            if (t.adminOnly && !document.getElementById('tab-tenants-btn')) {
                return false;
            }
            return true;
        }

        function renderOptions(filter) {
            var q = String(filter || '').toLowerCase().trim();
            list.innerHTML = '';
            var hits = 0;
            tabs.forEach(function (t) {
                if (!tabVisible(t)) return;
                var hay = (t.id + ' ' + t.label + ' ' + (t.keywords || '')).toLowerCase();
                if (q !== '' && hay.indexOf(q) < 0) return;
                hits++;
                var opt = document.createElement('option');
                opt.value = t.label;
                opt.label = t.label;
                list.appendChild(opt);
            });
            if (hits === 0 && q !== '') {
                var empty = document.createElement('option');
                empty.value = '';
                empty.label = 'Sin coincidencias';
                list.appendChild(empty);
            }
        }

        function findTabButton(tabId) {
            if (!tabId) return null;
            var pane = document.getElementById('tab-' + tabId);
            if (pane) {
                return document.querySelector('[data-bs-target="#' + pane.id + '"]');
            }
            var hyphen = tabId.replace(/_/g, '-');
            pane = document.getElementById('tab-' + hyphen);
            if (pane) {
                return document.querySelector('[data-bs-target="#' + pane.id + '"]');
            }
            return document.getElementById('tab-' + tabId + '-btn')
                || document.getElementById('tab-' + hyphen + '-btn')
                || document.querySelector('[data-bs-target="#tab-' + tabId + '"]')
                || document.querySelector('[data-bs-target="#tab-' + hyphen + '"]');
        }

        function resolveTabId(query) {
            var q = String(query || '').trim();
            if (q === '') return null;
            var ql = q.toLowerCase();
            var i;
            for (i = 0; i < tabs.length; i++) {
                if (!tabVisible(tabs[i])) continue;
                if (tabs[i].id === ql || tabs[i].id.replace(/_/g, '-') === ql) {
                    return tabs[i].id;
                }
            }
            for (i = 0; i < tabs.length; i++) {
                if (!tabVisible(tabs[i])) continue;
                if (String(tabs[i].label || '').toLowerCase() === ql) {
                    return tabs[i].id;
                }
            }
            var best = null;
            var bestScore = -1;
            for (i = 0; i < tabs.length; i++) {
                var t = tabs[i];
                if (!tabVisible(t)) continue;
                var hay = (t.id + ' ' + t.label + ' ' + (t.keywords || '')).toLowerCase();
                if (hay.indexOf(ql) < 0) continue;
                var score = 0;
                if (t.id === ql || t.id.replace(/_/g, '-') === ql) score = 100;
                else if (String(t.label || '').toLowerCase() === ql) score = 90;
                else if (String(t.label || '').toLowerCase().indexOf(ql) === 0) score = 70;
                else if (String(t.id || '').toLowerCase().indexOf(ql) === 0) score = 60;
                else score = 10;
                if (score > bestScore) {
                    bestScore = score;
                    best = t.id;
                }
            }
            return best;
        }

        function goToTab(tabId) {
            if (!tabId) return false;
            var meta = null;
            for (var i = 0; i < tabs.length; i++) {
                if (tabs[i].id === tabId) {
                    meta = tabs[i];
                    break;
                }
            }
            if (meta && meta.group) {
                applyTabGroupFilter(meta.group);
            }
            var btn = findTabButton(tabId);
            if (!btn) return false;
            if (typeof bootstrap !== 'undefined' && bootstrap.Tab) {
                bootstrap.Tab.getOrCreateInstance(btn).show();
            } else {
                btn.click();
            }
            var url = new URL(window.location.href);
            url.searchParams.set('tab', tabId);
            history.replaceState(null, '', url.pathname + url.search);
            btn.scrollIntoView({ behavior: 'smooth', block: 'nearest', inline: 'center' });
            return true;
        }

        function submitFinder(input) {
            var tabId = resolveTabId(input.value);
            if (!tabId) return false;
            var ok = goToTab(tabId);
            if (ok) {
                input.value = '';
                renderOptions('');
            }
            return ok;
        }

        inputs.forEach(function (input) {
            input.addEventListener('input', function () {
                renderOptions(input.value);
                var tabId = resolveTabId(input.value);
                if (tabId) {
                    var i;
                    for (i = 0; i < tabs.length; i++) {
                        if (tabs[i].id === tabId && input.value === tabs[i].label) {
                            submitFinder(input);
                            break;
                        }
                    }
                }
            });
            input.addEventListener('change', function () {
                submitFinder(input);
            });
            input.addEventListener('keydown', function (ev) {
                if (ev.key === 'Enter') {
                    ev.preventDefault();
                    submitFinder(input);
                }
            });
        });

        document.querySelectorAll('.config-quick-link[data-config-tab]').forEach(function (a) {
            a.addEventListener('click', function (ev) {
                ev.preventDefault();
                goToTab(a.getAttribute('data-config-tab'));
            });
        });

        renderOptions('');
    }

    function paginateList(root) {
        var tbody = root.querySelector('tbody');
        if (!tbody) return null;

        var pageSize = parseInt(root.getAttribute('data-page-size') || '15', 10);
        if (isNaN(pageSize) || pageSize < 5) pageSize = 15;

        var searchInput = root.querySelector('.config-list-search');
        var metaEl = root.querySelector('.config-list-meta');
        var navEl = root.querySelector('.config-list-pagination-nav');
        var allRows = Array.prototype.slice.call(tbody.querySelectorAll('tr')).filter(function (tr) {
            return !tr.querySelector('td[colspan]');
        });
        var emptyRow = tbody.querySelector('tr td[colspan]');
        var currentPage = 1;

        function visibleRows() {
            var q = searchInput ? String(searchInput.value || '').toLowerCase().trim() : '';
            return allRows.filter(function (tr) {
                if (q === '') return true;
                return String(tr.textContent || '').toLowerCase().indexOf(q) >= 0;
            });
        }

        function render() {
            var rows = visibleRows();
            var total = rows.length;
            var pages = Math.max(1, Math.ceil(total / pageSize));
            if (currentPage > pages) currentPage = pages;
            if (currentPage < 1) currentPage = 1;

            allRows.forEach(function (tr) {
                tr.classList.add('config-list-page-hidden');
                tr.classList.remove('config-list-filter-hidden');
            });

            rows.forEach(function (tr, idx) {
                var page = Math.floor(idx / pageSize) + 1;
                if (page === currentPage) {
                    tr.classList.remove('config-list-page-hidden');
                }
            });

            allRows.forEach(function (tr) {
                if (rows.indexOf(tr) < 0) {
                    tr.classList.add('config-list-filter-hidden');
                }
            });

            if (emptyRow) {
                var emptyTr = emptyRow.closest('tr');
                if (emptyTr) {
                    emptyTr.style.display = total === 0 && allRows.length === 0 ? '' : 'none';
                }
            }

            if (metaEl) {
                if (total === 0) {
                    metaEl.textContent = allRows.length === 0 ? 'Sin registros' : 'Ningún resultado con ese filtro';
                } else {
                    var from = (currentPage - 1) * pageSize + 1;
                    var to = Math.min(currentPage * pageSize, total);
                    metaEl.textContent = 'Mostrando ' + from + '–' + to + ' de ' + total;
                }
            }

            if (!navEl) return;
            navEl.innerHTML = '';
            if (pages <= 1) return;

            var ul = document.createElement('ul');
            ul.className = 'pagination pagination-sm mb-0';

            function addItem(label, page, disabled, active) {
                var li = document.createElement('li');
                li.className = 'page-item' + (disabled ? ' disabled' : '') + (active ? ' active' : '');
                var a = document.createElement('a');
                a.className = 'page-link';
                a.href = '#';
                a.textContent = label;
                a.addEventListener('click', function (ev) {
                    ev.preventDefault();
                    if (disabled || active) return;
                    currentPage = page;
                    render();
                });
                li.appendChild(a);
                ul.appendChild(li);
            }

            addItem('«', currentPage - 1, currentPage <= 1, false);
            for (var p = 1; p <= pages; p++) {
                if (pages > 7 && p > 2 && p < pages - 1 && Math.abs(p - currentPage) > 1) {
                    if (p === 3 || p === pages - 2) {
                        var dots = document.createElement('li');
                        dots.className = 'page-item disabled';
                        dots.innerHTML = '<span class="page-link">…</span>';
                        ul.appendChild(dots);
                    }
                    continue;
                }
                addItem(String(p), p, false, p === currentPage);
            }
            addItem('»', currentPage + 1, currentPage >= pages, false);
            navEl.appendChild(ul);
        }

        if (searchInput) {
            searchInput.addEventListener('input', function () {
                currentPage = 1;
                render();
            });
        }

        function rebuild() {
            allRows = Array.prototype.slice.call(tbody.querySelectorAll('tr')).filter(function (tr) {
                return !tr.querySelector('td[colspan]');
            });
            emptyRow = tbody.querySelector('tr td[colspan]');
            currentPage = 1;
            render();
        }

        render();
        return { refresh: render, rebuild: rebuild };
    }

    function initPaginatedLists() {
        document.querySelectorAll('.config-paginated-list').forEach(function (root) {
            if (root._configPaginate) {
                root._configPaginate.rebuild();
            } else {
                root._configPaginate = paginateList(root);
            }
        });
    }

    function initSectionGuides() {
        document.querySelectorAll('[data-config-guide-dismiss]').forEach(function (btn) {
            var key = btn.getAttribute('data-config-guide-dismiss');
            var panel = btn.closest('.config-section-guide');
            if (!panel || !key) return;
            if (storageGet('configGuideDismissed_' + key) === '1') {
                panel.style.display = 'none';
            }
            btn.addEventListener('click', function () {
                panel.style.display = 'none';
                storageSet('configGuideDismissed_' + key, '1');
            });
        });
    }

    document.addEventListener('DOMContentLoaded', function () {
        initHubGuide();
        initTabGroupFilter();
        initTabFinder();
        initPaginatedLists();
        initSectionGuides();
    });

    window.initConfigPaginatedList = paginateList;
})();
