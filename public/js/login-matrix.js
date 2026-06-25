/**
 * Fondo estilo Matrix en el panel de login (color = --login-primary del tenant).
 */
(function () {
    var canvas = document.getElementById('login_matrix_canvas');
    var container = document.querySelector('.login-form-side');
    if (!canvas || !container) return;

    var ctx = canvas.getContext('2d');
    if (!ctx) return;

    var fontSize = 15;
    var mobileFontSize = 11;
    var columns = 0;
    var drops = [];
    var timer = null;
    var chars = 'アイウエオカキクケコサシスセソタチツテトナニヌネノハヒフヘホマミムメモ0123456789ABCDEF+-×÷LabHDL';

    function getPrimaryColor() {
        var value = getComputedStyle(document.documentElement).getPropertyValue('--login-primary').trim();
        return value || '#0d9488';
    }

    function parseHex(hex) {
        var raw = hex.replace('#', '').trim();
        if (raw.length === 3) {
            raw = raw[0] + raw[0] + raw[1] + raw[1] + raw[2] + raw[2];
        }
        if (raw.length !== 6) return { r: 13, g: 148, b: 136 };
        return {
            r: parseInt(raw.slice(0, 2), 16),
            g: parseInt(raw.slice(2, 4), 16),
            b: parseInt(raw.slice(4, 6), 16),
        };
    }

    function resize() {
        var rect = container.getBoundingClientRect();
        var dpr = Math.min(window.devicePixelRatio || 1, 2);
        canvas.width = Math.max(1, Math.floor(rect.width * dpr));
        canvas.height = Math.max(1, Math.floor(rect.height * dpr));
        canvas.style.width = rect.width + 'px';
        canvas.style.height = rect.height + 'px';
        ctx.setTransform(dpr, 0, 0, dpr, 0, 0);

        fontSize = rect.width < 900 ? mobileFontSize : 15;
        columns = Math.max(1, Math.floor(rect.width / fontSize));
        drops = [];
        for (var i = 0; i < columns; i++) {
            drops[i] = Math.floor(Math.random() * -40);
        }
    }

    function draw() {
        var rect = container.getBoundingClientRect();
        var rgb = parseHex(getPrimaryColor());

        ctx.fillStyle = 'rgba(4, 8, 12, 0.12)';
        ctx.fillRect(0, 0, rect.width, rect.height);

        ctx.font = '600 ' + fontSize + 'px ui-monospace, SFMono-Regular, Menlo, Consolas, monospace';

        for (var i = 0; i < drops.length; i++) {
            var text = chars.charAt(Math.floor(Math.random() * chars.length));
            var x = i * fontSize;
            var y = drops[i] * fontSize;
            var alpha = 0.25 + Math.random() * 0.75;

            if (Math.random() > 0.96) {
                alpha = 1;
            }

            ctx.fillStyle = 'rgba(' + rgb.r + ',' + rgb.g + ',' + rgb.b + ',' + alpha + ')';
            ctx.fillText(text, x, y);

            if (y > rect.height && Math.random() > 0.965) {
                drops[i] = 0;
            }

            drops[i] += 0.6 + Math.random() * 0.9;
        }
    }

    function start() {
        if (timer) return;
        resize();
        timer = window.setInterval(draw, 45);
    }

    function stop() {
        if (timer) {
            window.clearInterval(timer);
            timer = null;
        }
    }

    if (window.matchMedia('(prefers-reduced-motion: reduce)').matches) {
        resize();
        draw();
        return;
    }

    start();

    window.addEventListener('resize', function () {
        resize();
    });

    document.addEventListener('visibilitychange', function () {
        if (document.hidden) {
            stop();
        } else {
            start();
        }
    });
})();
