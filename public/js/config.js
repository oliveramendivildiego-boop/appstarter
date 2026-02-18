/**
 * Módulo Config - Gestión de configuración del sistema
 * Paleta Bootstrap 5.3: https://getbootstrap.com/docs/5.3/customize/color/
 */
document.addEventListener('DOMContentLoaded', function () {
    var form = document.getElementById('config_form');
    var themeColor = document.getElementById('theme_color');
    var themeHex = document.getElementById('theme_color_hex');
    var paletteSelect = document.getElementById('theme_palette_select');

    function hexMatch(a, b) {
        var x = String(a || '').replace(/^#/, '').toLowerCase();
        var y = String(b || '').replace(/^#/, '').toLowerCase();
        return x === y;
    }

    if (paletteSelect && themeColor && themeHex) {
        paletteSelect.addEventListener('change', function () {
            var val = this.value;
            if (val) {
                themeColor.value = val;
                themeHex.value = val;
            }
        });
    }

    if (themeColor && themeHex) {
        themeColor.addEventListener('input', function () {
            var val = this.value;
            themeHex.value = val;
            if (paletteSelect) {
                var found = false;
                for (var i = 0; i < paletteSelect.options.length; i++) {
                    if (paletteSelect.options[i].value && hexMatch(paletteSelect.options[i].value, val)) {
                        paletteSelect.value = paletteSelect.options[i].value;
                        found = true;
                        break;
                    }
                }
                if (!found) paletteSelect.value = '';
            }
        });
    }

    /* El envío asíncrono y toast se manejan por common.js (form[data-async="1"]) */
});
