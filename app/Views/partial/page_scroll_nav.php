<?php
$scrollNavId = $scroll_nav_id ?? 'page-scroll-nav';
?>
<nav id="<?= esc($scrollNavId, 'attr') ?>" class="page-scroll-nav" aria-label="Navegación rápida de página" hidden>
    <button type="button" class="page-scroll-nav__btn" data-scroll-target="top" title="Ir arriba" aria-label="Ir arriba">
        <i class="fa-solid fa-chevron-up" aria-hidden="true"></i>
    </button>
    <button type="button" class="page-scroll-nav__btn" data-scroll-target="middle" title="Ir al medio" aria-label="Ir al medio">
        <i class="fa-solid fa-grip-lines" aria-hidden="true"></i>
    </button>
    <button type="button" class="page-scroll-nav__btn" data-scroll-target="bottom" title="Ir abajo" aria-label="Ir abajo">
        <i class="fa-solid fa-chevron-down" aria-hidden="true"></i>
    </button>
</nav>
<script>
(function() {
    var nav = document.getElementById(<?= json_encode($scrollNavId) ?>);
    if (!nav || nav.dataset.scrollNavReady === '1') return;
    nav.dataset.scrollNavReady = '1';

    var zoneRatio = 0.15;
    var buttons = {
        top: nav.querySelector('[data-scroll-target="top"]'),
        middle: nav.querySelector('[data-scroll-target="middle"]'),
        bottom: nav.querySelector('[data-scroll-target="bottom"]')
    };

    function getScrollMetrics() {
        var doc = document.documentElement;
        var scrollTop = window.pageYOffset || doc.scrollTop || 0;
        var scrollHeight = Math.max(doc.scrollHeight, document.body.scrollHeight);
        var clientHeight = window.innerHeight || doc.clientHeight;
        var maxScroll = Math.max(0, scrollHeight - clientHeight);
        return { scrollTop: scrollTop, maxScroll: maxScroll, clientHeight: clientHeight };
    }

    function getZone(scrollTop, maxScroll) {
        if (maxScroll <= 0) return 'top';
        var ratio = scrollTop / maxScroll;
        if (ratio < zoneRatio) return 'top';
        if (ratio > 1 - zoneRatio) return 'bottom';
        return 'middle';
    }

    function updateNav() {
        var metrics = getScrollMetrics();
        if (metrics.maxScroll < metrics.clientHeight) {
            nav.hidden = true;
            return;
        }
        nav.hidden = false;
        var zone = getZone(metrics.scrollTop, metrics.maxScroll);
        buttons.top.hidden = zone === 'top';
        buttons.middle.hidden = zone === 'middle';
        buttons.bottom.hidden = zone === 'bottom';
    }

    function scrollToTarget(target) {
        var metrics = getScrollMetrics();
        var top = 0;
        if (target === 'middle') top = metrics.maxScroll / 2;
        else if (target === 'bottom') top = metrics.maxScroll;
        window.scrollTo({ top: top, behavior: 'smooth' });
    }

    nav.addEventListener('click', function(e) {
        var btn = e.target.closest('[data-scroll-target]');
        if (!btn || btn.hidden) return;
        e.preventDefault();
        scrollToTarget(btn.getAttribute('data-scroll-target'));
    });

    window.addEventListener('scroll', updateNav, { passive: true });
    window.addEventListener('resize', updateNav);
    window.addEventListener('DOMContentLoaded', updateNav);
    window.addEventListener('load', updateNav);
    window.addEventListener('page-scroll-nav-refresh', updateNav);
    updateNav();
})();
</script>
