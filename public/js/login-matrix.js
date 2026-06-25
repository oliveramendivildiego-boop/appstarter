/**
 * Fondo estilo Matrix en el panel de login.
 * Usa --login-matrix-color (si el tema es oscuro, cae al fin del degradado).
 */
(function () {
    var canvas = document.getElementById('login_matrix_canvas');
    var container = document.querySelector('.login-form-side');
    if (!canvas || !container) return;

    var ctx = canvas.getContext('2d');
    if (!ctx) return;

    var baseFontSize = 17;
    var mobileBaseFontSize = 14;
    var columnGap = 21;
    var mobileColumnGap = 18;
    var trailLength = 10;
    var fontSize = baseFontSize;
    var columns = 0;
    var columnStep = columnGap;
    var drops = [];
    var speeds = [];
    var timer = null;
    var chars = 'アイウエオカキクケコサシスセソタチツテトナニヌネノハヒフヘホマミムメモ0123456789ABCDEF+-×÷LabHDL';

    function getCssColor(name, fallback) {
        var value = getComputedStyle(document.documentElement).getPropertyValue(name).trim();
        return value || fallback;
    }

    function parseHex(hex) {
        var raw = hex.replace('#', '').trim();
        if (raw.length === 3) {
            raw = raw[0] + raw[0] + raw[1] + raw[1] + raw[2] + raw[2];
        }
        if (raw.length !== 6) return { r: 45, g: 212, b: 191 };
        return {
            r: parseInt(raw.slice(0, 2), 16),
            g: parseInt(raw.slice(2, 4), 16),
            b: parseInt(raw.slice(4, 6), 16),
        };
    }

    function relativeLuminance(rgb) {
        function channel(value) {
            var v = value / 255;
            return v <= 0.03928 ? v / 12.92 : Math.pow((v + 0.055) / 1.055, 2.4);
        }

        return 0.2126 * channel(rgb.r) + 0.7152 * channel(rgb.g) + 0.0722 * channel(rgb.b);
    }

    function getMatrixRgb() {
        var matrixColor = getCssColor('--login-matrix-color', '');
        if (matrixColor !== '') {
            return parseHex(matrixColor);
        }

        var primary = getCssColor('--login-primary', '#0d9488');
        var gradientEnd = getCssColor('--login-gradient-end', '#0284c7');
        var primaryRgb = parseHex(primary);

        if (relativeLuminance(primaryRgb) < 0.12) {
            var endRgb = parseHex(gradientEnd);
            if (relativeLuminance(endRgb) >= 0.12) {
                return endRgb;
            }
            return { r: 45, g: 212, b: 191 };
        }

        return primaryRgb;
    }

    function resize() {
        var rect = container.getBoundingClientRect();
        var dpr = Math.min(window.devicePixelRatio || 1, 2);
        canvas.width = Math.max(1, Math.floor(rect.width * dpr));
        canvas.height = Math.max(1, Math.floor(rect.height * dpr));
        canvas.style.width = rect.width + 'px';
        canvas.style.height = rect.height + 'px';
        ctx.setTransform(dpr, 0, 0, dpr, 0, 0);

        fontSize = rect.width < 900 ? mobileBaseFontSize : baseFontSize;
        columnStep = rect.width < 900 ? mobileColumnGap : columnGap;
        columns = Math.max(1, Math.floor(rect.width / columnStep));
        drops = [];
        speeds = [];
        for (var i = 0; i < columns; i++) {
            drops[i] = Math.random() * rect.height * -0.45;
            speeds[i] = 2.4 + Math.random() * 2.2;
        }
    }

    function draw() {
        var rect = container.getBoundingClientRect();
        var rgb = getMatrixRgb();

        ctx.fillStyle = 'rgba(4, 8, 12, 0.11)';
        ctx.fillRect(0, 0, rect.width, rect.height);

        ctx.font = '600 ' + fontSize + 'px ui-monospace, SFMono-Regular, Menlo, Consolas, monospace';

        for (var i = 0; i < drops.length; i++) {
            var x = i * columnStep + Math.max(0, Math.floor((columnStep - fontSize) / 2));
            var headY = drops[i];

            for (var t = 0; t < trailLength; t++) {
                var y = headY - t * fontSize;
                if (y < -fontSize || y > rect.height + fontSize) {
                    continue;
                }

                var text = chars.charAt(Math.floor(Math.random() * chars.length));
                var alpha = Math.max(0.08, 1 - t / trailLength);

                if (t === 0) {
                    alpha = 1;
                } else if (t === 1) {
                    alpha = 0.75;
                }

                ctx.fillStyle = 'rgba(' + rgb.r + ',' + rgb.g + ',' + rgb.b + ',' + alpha + ')';
                ctx.fillText(text, x, y);
            }

            drops[i] += speeds[i];

            if (headY > rect.height + fontSize * 2 && Math.random() > 0.955) {
                drops[i] = Math.random() * rect.height * -0.4;
                speeds[i] = 2.4 + Math.random() * 2.4;
            }
        }
    }

    function start() {
        if (timer) return;
        resize();
        timer = window.setInterval(draw, 36);
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
